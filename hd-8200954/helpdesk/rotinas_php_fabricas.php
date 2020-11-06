<?php
$areaDevel = ("novodevel.telecontrol.com.br" == $_SERVER['SERVER_NAME'])? true : false;

if ($areaDevel === true) {
	$rota_rotinas = __DIR__."/../rotinas";
} else {
	$rota_rotinas = "/var/www/assist/www/rotinas";
}

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if ($_POST["ajax_executa_rotina"]) {
	$dir = $_POST["dir"];
	$rotina = $_POST["rotina"];
	$param = trim($_POST["param"]);

	if (empty($param)) {
		system("php {$rota_rotinas}/{$dir}/{$rotina}");
	} else {
		system("php {$rota_rotinas}/{$dir}/{$rotina} $param");
	}
	exit;
}

if ($_POST["ajax_lista_rotinas"]) {
	$dir = $_POST["dir"];

	$dir = opendir("/{$rota_rotinas}/{$dir}");

	$arquivos = array();

	while (false !== ($filename = readdir($dir))) {
		if (empty($filename) || $filename == "." || $filename == ".." || preg_match("/\.swp$/", $filename)) {
			continue;
		}

		$arquivos[] = $filename;
	}	
	
	exit(json_encode($arquivos));
}

$TITULO = "Suporte";
include "menu.php";

if ($login_fabrica != 10) {
	exit;
}
?>

<table style="width: 800px; margin: 0 auto; table-layout: fixed;" >

	<tr>
		<th>Fabricante</th>
		<th>Rotina</th>
		<th>Parâmetros de Execução</th>
		<th>&nbsp;</th>
	</tr>
	<tr>
		<td style='text-align: center;' >
			<select id="fabrica" >
				<option value="" selected >Selecione</option>
				<?php
				$sql = "
					SELECT fabrica,nome, REPLACE(LOWER(fn_retira_especiais(nome)), ' ', '') AS nome_rotina
					FROM tbl_fabrica
					WHERE ativo_fabrica IS TRUE
					ORDER BY nome ASC
				";
				$qry = pg_query($con, $sql);

				while ($row = pg_fetch_object($qry)) {
					if ($row->fabrica == 200) {
						$row->nome_rotina = "mgl";
					}elseif ($row->fabrica == 203) {
						$row->nome_rotina = "brotherInternational";
					}
					echo "<option value='{$row->nome_rotina}' >{$row->nome}</option>";
				}
				?>
			</select>
		</td>
		<td style='text-align: center;' >
			<select id="rotina" >
				<option value="" >Selecione</option>
			</select>
		</td>
		<td>
			<input type="text" id="param_exec" value="" />
		</td>
		<td style='text-align: center;' >
			<button type='button' id='executar' >Executar</button>
		</td>
	</tr>

</table>

<script src="https://code.jquery.com/jquery-migrate-1.4.1.min.js" ></script>

<script>

$("#fabrica").on("change", function() {
	var v = $(this).val();
	
	if (v.length == 0) {
		$("#rotina").find("option:first").nextAll().remove();
	} else {
		$.ajax({
			url: "rotinas_php_fabricas.php",
			type: "post",
			data: { ajax_lista_rotinas: true, dir: v },
			beforeSend: function() {
				$("#rotina").find("option:first").nextAll().remove();
			}
		}).done(function(r) {
			r = JSON.parse(r);

			r.forEach(function(rotina, i) {
				$("#rotina").append("<option value='"+rotina+"'>"+rotina+"</option>");
			});
		});
	}
	
});

$("#executar").on("click", function() {
	var dir = $("#fabrica").val();
	var rotina = $("#rotina").val();
	var param = $("#param_exec").val();

	if (dir.length == 0 || rotina.length == 0) {
		alert("Selecione o fabricante e a rotina");
	} else {
		$.ajax({
			url: "rotinas_php_fabricas.php",
			type: "post",
			data: { ajax_executa_rotina: true, dir: dir, rotina: rotina, param: param },
			beforeSend: function() {
				$("#executar").prop({ disabled: true }).text("Executando...");
			}
		}).done(function(r) {
			$("#executar").prop({ disabled: false }).text("Executar");
			alert("Rotina executada");
		});
	}
});

</script>
