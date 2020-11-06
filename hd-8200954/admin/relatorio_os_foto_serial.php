<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "call_center,gerencia";

include "autentica_admin.php";

$layout_menu = "callcenter";
$title       = "Relatório de OS com foto e serial LCD";

include "cabecalho.php";

//include_once 'class/aws/s3_config.php';

//if ($S3_sdk_OK) {
	include_once S3CLASS;
	//$s3 = new anexaS3('os', (int) $login_fabrica);

	$s3 = new AmazonTC("os", $login_fabrica);
//}

if ($_POST["pesquisar"]) {
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);
	$peca_referencia    = trim($_POST["peca_referencia"]);
	$peca_descricao     = trim($_POST["peca_descricao"]);
	$data_os            = $_POST["data_os"];
	$data_inicial       = $_POST["data_inicial"];
	$data_final         = $_POST["data_final"];
	$fornecedor         = $_POST["fornecedor"];
	$tipo_os            = $_POST["tipo_os"];
	$posto              = trim($_POST["posto"]);
	$paginacao          = trim($_POST["paginacao"]);

	if (!strlen($paginacao)) {
		$msg["erro"][] = "Digite um número de Linhas por página";
	} else {
		if ($paginacao < 10 || $paginacao > 50) {
			$msg["erro"][] = "Número de linhas por página não pode ser menor que 10 ou maior que 50";
		} else {
			$paginacao_correta = $paginacao;
		}
	}

	# Validação Produto
	if (strlen($produto_referencia) > 0 || strlen($produto_descricao) > 0) {
		$sql = "SELECT produto 
				FROM tbl_produto 
				WHERE fabrica_i = {$login_fabrica}
				AND (
					(UPPER(referencia) = UPPER('{$produto_referencia}'))
					OR
					(UPPER(descricao) = UPPER('{$produto_descricao}'))
				)";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$produto = pg_fetch_result($res, 0, "produto");
		} else {
			$msg["erro"][] = "Produto não encontrado";
		}
	}

	# Validação Peça
	if (strlen($peca_referencia) > 0 || strlen($peca_descricao) > 0) {
		$sql = "SELECT peca 
				FROM tbl_peca 
				WHERE fabrica = {$login_fabrica}
				AND (
					(UPPER(referencia) = UPPER('{$peca_referencia}'))
					OR
					(UPPER(descricao) = UPPER('{$peca_descricao}'))
				)";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$peca = pg_fetch_result($res, 0, "peca");
		} else {
			$msg["erro"][] = "Peça não encontrada";
		}
	}

	# Validação Data Fabricação da Peça e Posto
	if ($data_os == "fabricacao" && empty($posto)) {
		$msg["erro"][] = "Para pesquisar usando a data de fabricação informe um posto";
	}

	if (!empty($posto)) {
		$sql = "SELECT tbl_posto.posto 
				FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND tbl_posto.posto = {$posto}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$msg["erro"][] = "Posto não encontrado";
		}
	}

	# Validação Data
	if (!empty($data_inicial) && !empty($data_final)) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
			$msg["erro"][] = "Data inválida";
		} else {
			$data_inicial = "{$yi}-{$mi}-{$di}";
			$data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($data_final) < strtotime($data_inicial)) {
				$msg["erro"][] = "Data inicial não pode ser maior que data final";
			}

			if (strtotime($data_inicial.'+1 month') < strtotime($data_final) ) {
				$msg["erro"][] = "O intervalo entre as datas não pode ser maior que 1 mês";
			}
		}
	} else {
		$msg["erro"][] = "Digite a data inicial e a data final";
	}

	if (!count($msg["erro"])) {
		if (!empty($produto)) {
			$whereAdc[] = " AND tbl_produto.produto = {$produto}";
		}

		if (!empty($peca)) {
			$whereAdc[] = " AND tbl_peca.peca = {$peca}";
		}

		if (!empty($fornecedor)) {
			$whereAdc[] = " AND  tbl_produto_fornecedor.produto_fornecedor = {$fornecedor}";
		}

		switch ($data_os) {
			case "os":
				$whereData = " AND tbl_os.data_digitacao BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' ";
				break;
			
			case "peca":
				$whereData = " AND tbl_os_item.digitacao_item BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' ";
				break;

			case "fabricacao":
				$whereData  = " AND tbl_os_item.parametros_adicionais ILIKE '%\"data_fabricacao\"%' ";
				$wherePosto = " AND tbl_os.posto = {$posto}";
				break;
		}

		switch ($tipo_os) {
			case "upload_foto":
				$whereTipo = " AND tbl_os_item.parametros_adicionais ILIKE '%\"item_foto_upload\":\"t\"%' ";
				break;
			
			case "serial_lcd":
				$whereTipo = " AND tbl_os_item.peca_serie IS NOT NULL ";
				break;

			case "ambos":
				$whereTipo = " AND (
					(tbl_os_item.parametros_adicionais ILIKE '%\"item_foto_upload\":\"t\"%')
					OR
					(tbl_os_item.peca_serie IS NOT NULL)
				)";
				break;
		}

		if (count($whereAdc) > 0) {
			$whereAdc = implode("", $whereAdc);
		}

		$sqlOS = "
                SELECT  DISTINCT
                        tbl_os.os,
                        tbl_os.sua_os,
                        tbl_produto.referencia AS produto_referencia,
                        tbl_produto.descricao AS produto_descricao,
                        tbl_os.serie AS produto_serie,
                        tbl_produto_fornecedor.nome AS fornecedor,
                        tbl_produto_idioma.descricao AS fornecedor_descricao,
                        EXTRACT(YEAR FROM data_digitacao) AS year,
                        EXTRACT(MONTH FROM data_digitacao) AS month
                FROM    tbl_os
                JOIN    tbl_os_produto          ON  tbl_os_produto.os                           = tbl_os.os
                JOIN    tbl_os_item             ON  tbl_os_item.os_produto                      = tbl_os_produto.os_produto
                                                AND tbl_os_item.fabrica_i                       = $login_fabrica
                JOIN    tbl_peca                ON  tbl_peca.peca                               = tbl_os_item.peca
                                                AND tbl_peca.fabrica                            = {$login_fabrica}
                JOIN    tbl_produto             ON  tbl_produto.produto                         = tbl_os.produto
                                                AND tbl_produto.fabrica_i                       = {$login_fabrica}
           LEFT JOIN    tbl_produto_fornecedor  ON  tbl_produto_fornecedor.produto_fornecedor   = tbl_produto.produto_fornecedor
                                                AND tbl_produto_fornecedor.fabrica              = {$login_fabrica}
           LEFT JOIN    tbl_produto_idioma      ON  tbl_produto_idioma.produto                  = tbl_produto.produto
                WHERE   tbl_os.fabrica = {$login_fabrica}
				  {$whereData}
				  {$whereTipo}
				  {$whereAdc}
				  {$wherePosto}
          ORDER BY      tbl_os.sua_os DESC";
// 				  exit(nl2br($sqlOS));
		$resOS = pg_query($sqlOS);
// 		echo pg_last_error();
		
		if ($data_os == "fabricacao") {
			unset($osArray);

			for ($i = 0; $i < pg_num_rows($resOS); $i++) {
				$os = pg_fetch_result($resOS, $i, "os");

				$sqlFabricacao = "SELECT tbl_os_item.parametros_adicionais
								  FROM tbl_os_item
								  JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								  JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
								  WHERE tbl_os.os = {$os}
								  AND tbl_os.fabrica = {$login_fabrica}
								  AND tbl_os_item.parametros_adicionais ILIKE '%\"data_fabricacao\"%'";
				$resFabricacao = pg_query($con, $sqlFabricacao);

				if (!pg_num_rows($resFabricacao)) {
					continue;
				} else {
					$temDF = false;

					for ($z = 0; $z < pg_num_rows($resFabricacao); $z++) {
						$pa = pg_fetch_result($resFabricacao, $z, "parametros_adicionais");
						$pa = json_decode($pa, true);

						if (empty($pa["data_fabricacao"])) {
							continue;
						} else {
							if (strtotime($pa["data_fabricacao"]) >= strtotime($data_inicial) && strtotime($pa["data_fabricacao"]) <= strtotime($data_final)) {
								$temDF = true;
								break;
							} else {
								continue;
							}
						}
					}
				}

				if ($temDF == false) {
					continue;
				} else {
					$osArray[] = $os;
				}
			}
		}

		if (!pg_num_rows($resOS)) {
			$msg["erro"][] = "Nenhuma OS encontrada com esses parâmetros";
		} else {
			if ($data_os == "fabricacao" && !count($osArray)) {
				$msg["erro"][] = "Nenhuma OS encontrada com esses parâmetros";
			}
		}
	}
}

?>

<link type="text/css" href="../plugins/jquery/jpaginate/jquery-ui.css" rel="stylesheet" />
<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<link type="text/css" href="../plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all" />
<link type="text/css" href="../plugins/jquery/jpaginate/css/style.css" rel="stylesheet" />
<style>
.formulario {
	margin: 0 auto;
	border-collapse: collapse;
	width: 700px;	
	background-color: #D9E2EF;
}

.titulo {
	text-align: right;
	vertical-align: center;
	padding-right: 20px;
}

.inputs td {
	padding-bottom: 15px;
}

table tbody tr {
	padding-bottom: 10px;
	margin-bottom: 10px;
}

table tbody tr td {
	text-align: left;
}

.lupa {
	cursor: pointer;
}

.msg_erro {
	width: 700px;
	margin: 0 auto;
	background-color: #FF0000;
	font: bold 16px "Arial";
	color: #FFFFFF;
	text-align: center;
}

.resultado {
	margin: 0 auto;
	border-collapse: collapse;
	min-width: 700px;
}

.resultado thead tr {
	background-color: #596D9B;
	color: #FFF;
	font-family: verdana;
	font-size: 11px;
}

.resultado tbody tr {
	font-family: verdana;
	font-size: 11px;
}

.resultado tbody tr td, .resultado thead tr th {
	padding-left: 5px;
	padding-right: 5px;
}

.resultado tbody tr:nth-child(2n+1) {
  background-color: #F1F4FA;
}

.resultado tbody tr:nth-child(2n+2) {
  background-color: #FFF;
}

.pecas {
	border-collapse: collapse;
	width: 100%;
}

.pecas tbody tr {
	background-color: transparent !important;
}

.pecas tbody tr td {
	border-bottom: 1px solid #000;
	text-align: left;
}

.pecas tbody tr:last-child td {
	border-bottom: 0px;
}

.pecas_title th {
	background-color: #596D9B;
	color: #FFF;
}

span[name=peca] {
	background-color: #D76F2B;
	color: #FFF;
	font-weight: bold;
	cursor: pointer;
	text-align: center;
	display: block;
}

span[name=peca][rel=esconde] {
	display: none;
}

span[name=peca]:hover {
	color: #000;
}

tr[rel=posto] {
	display: none;
}

.peca_divisor {
	width: 100%;
	border-bottom: 1px solid #596D9B;
	margin-top: 5px;
	position: relative;
}

.peca_divisor:last-child {
	border-bottom: 0px;
}

.pagedemo{
	margin: 2px;
	padding: 10px 10px;
	text-align: center;
}

.demo{
	padding: 10px;
	margin: 0 auto;
}

.pages{
	width: 320px;
	position: relative;
	margin: 0 auto;
}
</style>

<script src="../js/jquery-1.7.2.js" ></script>
<script src="../plugins/jquery/jpaginate/jquery-ui.min.js" ></script>
<script src="../plugins/jquery/jpaginate/jquery.paginate.js" ></script>
<script src="js/jquery.mask.js" ></script>
<script src="../plugins/jquery/datepick/jquery.datepick.js" ></script>
<script src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js" ></script>
<script src="../plugins/shadowbox/shadowbox.js" ></script>
<script src='js/jquery.alphanumeric.js' ></script>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
<script>
$(function () {
	Shadowbox.init();
	setupZoom();

	var paginacao = parseInt($("#paginacao_correta").val());
	var rows      = parseInt($("#rows").val());
	var pages     = parseInt(rows / paginacao);
	var pages2    = parseInt(rows % paginacao);

	if (pages2 <= (paginacao - 1) && pages2 > 0) {
		pages2 = 1;
	}

	var pages_total = parseInt(pages + pages2);

	$("#pages").paginate({
		count: pages_total,
		start: 1,
		display: 10,
		border: false,
		text_color: '#495677',
		background_color: 'transparent',
		text_hover_color: '#FFB70F',
		background_hover_color: 'transparent',
		rotate: false,
		images: false,
		mouse: 'press',
		onChange: function (page) {
		  	$('._current').removeClass('_current').fadeOut();
			$('#p'+page).addClass('_current').fadeIn();
			$(window).delay(1000).scrollTop(180);
			$('#page_atual').html("Página "+page);
		}
	});

	$("input[rel=data]").mask("99/99/9999");
	$("input[rel=data]").datepick();
	$("#paginacao").numeric();

	$(".lupa").click(function () {
		var pesquisa = $(this).attr("rel");
		var tipo     = $(this).prev().attr("rel");
		var valor    = $.trim($(this).prev().val());
		var title;
		var content;
		var contentTipo;

		if (pesquisa == "produto") {
			title = "Produto";
		}

		if (pesquisa == "peca") {
			title = "Peça";
		}

		if (pesquisa == "posto") {
			title   = "Posto";

			switch (tipo) {
				case "codigo":
					contentTipo = "codigo="+valor;
					break;

				case "nome":
					contentTipo = "nome="+valor;
					break;
			}

			content = "posto_pesquisa_nv.php?"+contentTipo;
		} else {
			content = "pesquisa_os_foto_serial.php?pesquisa="+pesquisa+"&tipo="+tipo+"&valor="+valor;
		}

		if (valor.length > 0) {
			Shadowbox.open({
				content: content,
				player : "iframe",
				title  : "Pesquisa de "+title,
				width  : 800,
				height : 600

			});
		} else {
			alert("Digite toda ou parte de uma informação para pesquisar");
		}
	});

	$("span[name=peca]").click(function () {
		var rel = $(this).attr("rel");

		if (rel == "mostra") {
			$(this).hide();
			$(this).next().show().css({ "display": "block" }).next().show();
		} else {
			$(this).hide();
			$(this).prev().show();
			$(this).next().hide();
		}
	});

	$("select[name=data_os]").change(function () {
		var valor = $(this).val();

		if (valor == "fabricacao") {
			$("tr[rel=posto]").show("slow");
		} else {
			$("tr[rel=posto]").hide("slow");
			$("tr[rel=posto].inputs").find("input").val("");
		}
	});
});

function retorna_posto (posto, codigo_posto, nome, cnpj, pais, cidade, estado, nome_fantasia) {
	$("input[name=posto]").val(posto);
	$("input[name=codigo_posto]").val(codigo_posto);
	$("input[name=nome_posto]").val(nome);
}

function resultado_pesquisa (json) {
	json = $.parseJSON(json);

	$("input[name="+json.pesquisa+"_referencia]").val(json.referencia);
	$("input[name="+json.pesquisa+"_descricao]").val(json.descricao);
}
</script>

<?php
if (count($msg["erro"]) > 0) {
?>
	<div class="msg_erro" >
		<?php
			echo implode("<br />", $msg["erro"]);
		?>
	</div>
<?php
}
?>

<form name="frm_pesquisa" id="frm_pesquisa" method="POST" class='formulario'>
	<table border="0" style='margin: 0 auto; width: 700px;'>
		<tr class="titulo_tabela">
			<td style='text-align: center;' >
				Parâmetros de Pesquisa
			</td>
		</tr>
	</table>
	<table border="0" style='margin: 0 auto'>
		<tbody>
			<tr>
				<td>Ref. Produto</td>
				<td>Descrição Produto</td>
			</tr>
			<tr class="inputs" >
				<td>
					<input type="text" name="produto_referencia" rel="referencia" style="width: 140px;" value="<?=$_POST['produto_referencia']?>" class='frm' />
					<img src="imagens/lupa.png" class="lupa" rel="produto" />
				</td>
				<td>
					<input type="text" name="produto_descricao" rel="descricao" value ="<?=$_POST['produto_descricao']?>" class='frm'/>
					<img src="imagens/lupa.png" class="lupa" rel="produto" />
				</td>
			</tr>
			<tr>
				<td>Ref. Peca</td>
				<td>Descrição</td>
			</tr>
			<tr class="inputs" >
				<td>
					<input type="text" name="peca_referencia" rel="referencia" style="width: 140px;" class='frm' value="<?=$_POST['peca_referencia']?>" />
					<img src="imagens/lupa.png" class="lupa" rel="peca" />
				</td>
				<td>
					<input type="text" name="peca_descricao" rel="descricao" value="<?=$_POST['peca_descricao']?>" class='frm'/>
					<img src="imagens/lupa.png" class="lupa" rel="peca" />
				</td>
			</tr>
			<tr>
				<td>Data</td>
				<td>Data Inicial</td>
				<td>Data Final</td>
			</tr>
			<tr class="inputs" >
				<td>
					<select name="data_os" class='frm' >
						<option value="os" <?=($_POST["data_os"] == "os") ? "SELECTED" : ""?>>Digitação da OS</option>
						<option value="peca" <?=($_POST["data_os"] == "peca") ? "SELECTED" : ""?>>Digitação da Peça</option>
						<option value="fabricacao" <?=($_POST["data_os"] == "fabricacao") ? "SELECTED" : ""?>>Fabricação da Peça</option>
					</select>
				</td>
				<td>
					<input type="text" name="data_inicial" rel="data" style="width: 80px;" class='frm' value="<?=$_POST['data_inicial']?>" />
				</td>
				<td>
					<input type="text" name="data_final" rel ="data" style="width: 80px;" class='frm' value="<?=$_POST['data_final']?>" />
				</td>
			</tr>
			<?php
			$display = ($_POST["data_os"] == "fabricacao") ? "style='display: table-row;'" : "";
			?>
			<tr rel="posto" <?=$display?> >
				<td>Posto Código</td>
				<td>Posto Nome</td>
			</tr>
			<tr rel="posto" class="inputs" <?=$display?> >
				<td>
					<input type="hidden" name="posto" value="<?=$_POST['posto']?>" />
					<input class='frm' type="text" name="codigo_posto" rel="codigo" style="width: 140px;" value="<?=$_POST['codigo_posto']?>" />
					<img src="imagens/lupa.png" class="lupa" rel="posto" />
				</td>
				<td>
					<input class='frm' type="text" name="nome_posto" rel="nome" value="<?=$_POST['nome_posto']?>" />
					<img src="imagens/lupa.png" class="lupa" rel="posto" />
				</td>
			</tr>
			<tr>
				<td>Fornecedor</td>	
				<td>Linhas por página <span style="color: #F00;">(min. 10, max. 50)</span></td>	
			</tr>
			<tr class='inputs' >
				<td>
					<select name="fornecedor" class='frm' >
						<option></option>
						<?php
						$sql = "SELECT  produto_fornecedor, nome
								FROM tbl_produto_fornecedor
								WHERE fabrica = $login_fabrica
								ORDER BY nome";
						$res = pg_query($con, $sql);

						for ($i = 0; $i < pg_num_rows($res); $i++) {
							$produto_fornecedor = pg_fetch_result($res, $i, "produto_fornecedor");
							$nome               = pg_fetch_result($res, $i, "nome");

							$selected = ($produto_fornecedor == $_POST["fornecedor"]) ? "SELECTED" : "";

							echo "<option value='{$produto_fornecedor}' {$selected} >{$nome}</option>";
						}
						?>
					</select>
				</td>
				<td>
					<input class="frm" style="width: 30px;" maxlength="2" type="text" id="paginacao" name="paginacao" value="<?=$_POST['paginacao']?>" />
				</td>
			</tr>
			<tr>
				<td colspan="4" style="text-align: center;" >
					<input type="radio" name="tipo_os" id="upload_foto" value="upload_foto" <?=($_POST["tipo_os"] == "upload_foto") ? "CHECKED" : ""?> />
					<label for="upload_foto" >OS's com peças com fotos</label>

					<input type="radio" name="tipo_os" id="serial_lcd" value="serial_lcd" <?=($_POST["tipo_os"] == "serial_lcd") ? "CHECKED" : ""?> />
					<label for="serial_lcd" >OS's com serial de LCD</label>

					<input type="radio" name="tipo_os" id="ambos" value="ambos" <?=($_POST["tipo_os"] == "ambos" or !$_POST) ? "CHECKED" : ""?> />
					<label for="ambos" >Ambos</label>
				</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="3" style='text-align: center;'>
					<br />
					<input type="submit" name="pesquisar" value="Pesquisar" /><br />
					<br />
				</td>
			</tr>
		</tfoot>
	</table>
</form>

<?php
if (!count($msg["erro"]) && pg_num_rows($resOS) > 0) {
	//XLS Inicio
	$fp = fopen("/tmp/relatorio_os_foto_serial.xls", "w");

	fwrite($fp, "
	<table border='1' >
		<thead>
			<tr>
				<th bgcolor='#596D9B' ><font color='#FFFFFF'>OS</font></th>
				<th bgcolor='#596D9B' ><font color='#FFFFFF'>Produto</font></th>
				<th bgcolor='#596D9B' ><font color='#FFFFFF'>Produto Série</font></th>
				<th bgcolor='#596D9B' ><font color='#FFFFFF'>Fornecedor</font></th>
				<th bgcolor='#596D9B' ><font color='#FFFFFF'>Descrição Fornecedor</font></th>
				<th bgcolor='#596D9B' ><font color='#FFFFFF'>Peça</font></th>
				<th bgcolor='#596D9B' ><font color='#FFFFFF'>Data Fabricação</font></th>
				".(($tipo_os == "serial_lcd" || $tipo_os == "ambos") ? "<th bgcolor='#596D9B' ><font color='#FFFFFF'>Display LCD Série</font></th>" : "")."
			</tr>
		</thead>
		<tbody>
	");

	for ($i = 0; $i < pg_num_rows($resOS); $i++) {
		$os                   = pg_fetch_result($resOS, $i, "os");
		$sua_os               = pg_fetch_result($resOS, $i, "sua_os");
		$produto_referencia   = pg_fetch_result($resOS, $i, "produto_referencia");
		$produto_descricao    = pg_fetch_result($resOS, $i, "produto_descricao");
		$produto_serie        = pg_fetch_result($resOS, $i, "produto_serie");
		$fornecedor           = pg_fetch_result($resOS, $i, "fornecedor");
		$fornecedor_descricao = pg_fetch_result($resOS, $i, "fornecedor_descricao");

		if ($data_os == "fabricacao") {
			if (!in_array($os, $osArray)) {
				continue;
			}
		}

		$sqlPeca = "SELECT 
						tbl_peca.referencia, 
						tbl_peca.descricao, 
						tbl_os_item.peca_serie AS serial_lcd,
						tbl_os.os,
						tbl_os_item.os_item,
						tbl_os_item.parametros_adicionais
					FROM tbl_os_item
					JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
					JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
					WHERE tbl_os.os = {$os}";
		$resPeca = pg_query($con, $sqlPeca);

		$bgcolor = ($i % 2) ? "#F1F4FA" : "#FFFFFF";

		fwrite($fp, "
		<tr>
			<td valign='top' bgcolor='{$bgcolor}' >{$sua_os}</td>
			<td valign='top' bgcolor='{$bgcolor}' >{$produto_referencia} - {$produto_descricao}</td>
			<td valign='top' bgcolor='{$bgcolor}' >{$produto_serie}</td>
			<td valign='top' bgcolor='{$bgcolor}' >{$fornecedor}</td>
			<td valign='top' bgcolor='{$bgcolor}' >{$fornecedor_descricao}</td>
		");

			//Peça Inicio
			fwrite($fp, "
			<td valign='top' bgcolor='{$bgcolor}' >
			");

			for ($k = 0; $k < pg_num_rows($resPeca); $k++) {
				$ref  = pg_fetch_result($resPeca, $k, "referencia");
				$desc = pg_fetch_result($resPeca, $k, "descricao");

				fwrite($fp, "{$ref} - {$desc}<br />");
			}

			fwrite($fp, "
			</td>
			");
			//Peça Fim

			fwrite($fp, "
			<td valign='top' bgcolor='{$bgcolor}' >
			");

			for ($k = 0; $k < pg_num_rows($resPeca); $k++) {
				$parametros_adicionais = pg_fetch_result($resPeca, $k, "parametros_adicionais");
				$parametros_adicionais = json_decode($parametros_adicionais, true);

				fwrite($fp, $parametros_adicionais['data_fabricacao']."<br>");
			}

			fwrite($fp, "
			</td>
			");

			//Serial LCD Inicio
			if ($tipo_os == "serial_lcd" || $tipo_os == "ambos") {
				fwrite($fp, "
				<td valign='top' bgcolor='{$bgcolor}' >
				");

				for ($k = 0; $k < pg_num_rows($resPeca); $k++) {
					$ref        = pg_fetch_result($resPeca, $k, "referencia");
					$serial_lcd = pg_fetch_result($resPeca, $k, "serial_lcd");

					fwrite($fp, "{$serial_lcd}<br />");
				}

				fwrite($fp, "
				</td>
				");
			}
			//Serial LCD Fim

		fwrite($fp, "
		</tr>
		");
	}

	fwrite($fp, "
		</tbody>
	</table>
	");

	fclose($fp);

	$data = date("d-m-Y-H-i");

	if (file_exists("xls/relatorio_os_foto_serial-{$data}.xls")) {
		system("rm -rf xls/relatorio_os_foto_serial.xls");
	}

	system("mv /tmp/relatorio_os_foto_serial.xls xls/relatorio_os_foto_serial-{$data}.xls");

	if (file_exists("xls/relatorio_os_foto_serial-{$data}.xls")) {
		echo "<br /><a href='xls/relatorio_os_foto_serial-{$data}.xls'>Download do arquivo XLS</a><br />";
	}
	//XLS Fim

	echo "<input type='hidden' id='rows' value='".pg_num_rows($resOS)."' />";
	echo "<input type='hidden' id='paginacao_correta' value='{$paginacao_correta}' />";
?>
	<br />

			<?php
			echo "<p id='page_atual' style='color: #63798D; text-align: center;' >
				Página 1
			</p>";

			echo "<div id='pagination' class='demo'>";
            $arquivoHtml = "";
            $pastaTemp = "arqTemp_".date("Ymd_his");
            mkdir("/tmp/$pastaTemp/fotosTemp",0777,true);
			for ($i = 0; $i < pg_num_rows($resOS); $i++) {
				$os                   = pg_fetch_result($resOS, $i, "os");
				$sua_os               = pg_fetch_result($resOS, $i, "sua_os");
				$produto_referencia   = pg_fetch_result($resOS, $i, "produto_referencia");
				$produto_descricao    = pg_fetch_result($resOS, $i, "produto_descricao");
				$produto_serie        = pg_fetch_result($resOS, $i, "produto_serie");
				$fornecedor           = pg_fetch_result($resOS, $i, "fornecedor");
				$fornecedor_descricao = pg_fetch_result($resOS, $i, "fornecedor_descricao");
				$year                 = pg_fetch_result($resOS, $i, "year");
				$month                = pg_fetch_result($resOS, $i, "month");

				if ($data_os == "fabricacao") {
					if (!in_array($os, $osArray)) {
						continue;
					}
				}

				if ($i == $z) {
					if (empty($z)) {
						$class = "class='pagedemo _current'";
					} else {
						$class   = "class='pagedemo'";
						$display = "display: none;'";
					}

					$p = $p + 1; 
					$z = $z + $paginacao_correta;

					echo "<div id='p{$p}' {$class} style='{$display}' >
						<table border='1' class='resultado' >
							<thead>
								<tr>
									<th>OS</th>
									<th>Produto</th>
									<th>Produto Série</th>
									<th>Fornecedor</th>
									<th>Descrição Fornecedor</th>
									<th>Peça</th>
									<th>Data Fabricação </th>";
									
									if ($tipo_os == "serial_lcd" || $tipo_os == "ambos") {
										echo "<th>Display LCD Série</th>";
									}

									if ($tipo_os == "upload_foto" || $tipo_os == "ambos") {
										echo "<th>Fotos</th>";
									}

								echo "</tr>
							</thead>
							<tbody>";
                    $arquivoHtml .= "
                        <table border='1' class='resultado' >
                            <thead>
                                <tr>
                                    <th>OS</th>
                                    <th>Produto</th>
                                    <th>Produto Série</th>
                                    <th>Fornecedor</th>
                                    <th>Descrição Fornecedor</th>
                                    <th>Peça</th>
                                    <th>Data Fabricação</th>";

                                    if ($tipo_os == "serial_lcd" || $tipo_os == "ambos") {
                                        $arquivoHtml .= "<th>Display LCD Série</th>";
                                    }

                                    if ($tipo_os == "upload_foto" || $tipo_os == "ambos") {
                                        $arquivoHtml .= "<th>Fotos</th>";
                                    }

                                $arquivoHtml .= "</tr>
                            </thead>
                            <tbody>";
				}

				$sqlPeca = "SELECT 
								tbl_peca.referencia, 
								tbl_peca.descricao, 
								tbl_peca.parametros_adicionais AS peca_pa,
								tbl_os_item.peca_serie AS serial_lcd,
								tbl_os.os,
								tbl_os_item.os_item,
								tbl_os_item.parametros_adicionais
							FROM tbl_os_item
							JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
							JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
							WHERE tbl_os.os = {$os}
							ORDER BY tbl_os_item.os_item ASC";
				$resPeca = pg_query($con, $sqlPeca);

				echo "<tr>
					<td style='font-weight: bold; vertical-align: top;'><a href='os_press.php?os={$os}' target='_blank' style='cursor: pointer;' >{$sua_os}</a></td>
					<td style='vertical-align: top;' >{$produto_referencia} - {$produto_descricao}</td>
					<td style='vertical-align: top;' >{$produto_serie}</td>
					<td style='vertical-align: top;' >{$fornecedor}</td>
					<td style='vertical-align: top;' >{$fornecedor_descricao}</td>
					<td style='vertical-align: top;' nowrap >";
                $arquivoHtml .= "<tr>
                    <td style='font-weight: bold; vertical-align: top;'>{$sua_os}</td>
                    <td style='vertical-align: top;' >{$produto_referencia} - {$produto_descricao}</td>
                    <td style='vertical-align: top;' >{$produto_serie}</td>
                    <td style='vertical-align: top;' >{$fornecedor}</td>
                    <td style='vertical-align: top;' >{$fornecedor_descricao}</td>
                    <td style='vertical-align: top;' nowrap >
                ";

                for ($k = 0; $k < pg_num_rows($resPeca); $k++) {
                    $ref  = pg_fetch_result($resPeca, $k, "referencia");
                    $desc = pg_fetch_result($resPeca, $k, "descricao");
                    $os        = pg_fetch_result($resPeca, $k, "os");
                    $os_item   = pg_fetch_result($resPeca, $k, "os_item");

                    echo "{$ref} - {$desc}<br />";
                    $arquivoHtml .= "{$ref} - {$desc}<br />";
                }

                    echo "</td>";
					$arquivoHtml .= "</td>";
                echo "<td style='vertical-align: top;' nowrap >  ";
                $arquivoHtml .= "<td style='vertical-align: top;' nowrap >  ";

                for ($k = 0; $k < pg_num_rows($resPeca); $k++) {
					$parametros_adicionais = pg_fetch_result($resPeca, $k, "parametros_adicionais");
					$parametros_adicionais = json_decode($parametros_adicionais, true);

                    echo $parametros_adicionais['data_fabricacao']." <br />";
                    $arquivoHtml .= $parametros_adicionais['data_fabricacao']." <br />";
                }

                    echo "</td>";
					if ($tipo_os == "serial_lcd" || $tipo_os == "ambos") {
						echo "<td style='vertical-align: top;' nowrap >";
						$arquivoHtml .= "<td>";

								for ($k = 0; $k < pg_num_rows($resPeca); $k++) {
									$serial_lcd = pg_fetch_result($resPeca, $k, "serial_lcd");
									$os        = pg_fetch_result($resPeca, $k, "os");
									$os_item   = pg_fetch_result($resPeca, $k, "os_item");

									if (!strlen($serial_lcd)) {
										$serial_lcd = "&nbsp;";
									}

		                               echo "{$serial_lcd}<br />";
					       $arquivoHtml .= "{$serial_lcd}<br />";
								}

						echo "</td>";
						$arquivoHtml .= "</td>";
					}

					if ($tipo_os == "upload_foto" || $tipo_os == "ambos") {
                        echo "<td valign='top' nowrap>";
						$arquivoHtml .= "<td valign='top' nowrap>";
								for ($k = 0; $k < pg_num_rows($resPeca); $k++) {
									$referencia            = pg_fetch_result($resPeca, $k, "referencia");
									$os_item               = pg_fetch_result($resPeca, $k, "os_item");
									$parametros_adicionais = pg_fetch_result($resPeca, $k, "parametros_adicionais");

									$parametros_adicionais = json_decode($parametros_adicionais, true);

									if ($parametros_adicionais["item_foto_upload"] == "t") {
										$tem_upload = false;

										foreach ($parametros_adicionais["foto_upload"] as $key => $foto) {
											$upload = $foto["upload"];

											if ($upload == "t") {
												$tem_upload = true;
												break;
											}
										}

										if ($tem_upload === true) {
											echo $referencia.": ";

											foreach ($parametros_adicionais["foto_upload"] as $key => $foto) {
												if ($foto["upload"] <> "t" ) {
                                                    continue;
                                            	}
                                            	
												$ext = $foto["ext"];

												$thumb = $s3->getLink("thumb_{$os}-{$os_item}-{$key}.{$ext}", false, $year, $month);
												$full  = $s3->getLink("{$os}-{$os_item}-{$key}.{$ext}", false, $year, $month);

                                                system("wget '$thumb' -O /tmp/$pastaTemp/fotosTemp/thumb_{$os}-{$os_item}-{$key}.{$ext}");
												system("wget '$full' -O /tmp/$pastaTemp/fotosTemp/{$os}-{$os_item}-{$key}.{$ext}");

                                                $linkFull = "fotosTemp/{$os}-{$os_item}-{$key}.{$ext}";
												$linkThumb = "fotosTemp/thumb_{$os}-{$os_item}-{$key}.{$ext}";

                                                echo "<a href='{$full}' ><img src='{$thumb}' title='Clique para ver a imagem em uma escala maior' /></a>";
												$arquivoHtml .= "<a href='$linkFull' ><img src='$linkThumb' /></a>";
											}

                                            echo "<br />";
											$arquivoHtml .= "<br />";
										}
									}
								}
                        echo "</td>";
						$arquivoHtml .= "</td>";
					}
                echo "</tr>";
				$arquivoHtml .= "</tr>";

				if (($i == ($z - 1)) || ($i == (pg_num_rows($resOS) - 1))) {
                    echo "</tbody></table></div>";
				}
			}

			echo "<div id='pages' class='pages' ></div>";
			echo "</div>";

			$arquivoHtml .= "</tbody></table>";

            if (file_exists("xls/relatorio_fotos_envio.zip")) {
                system("rm -rf xls/relatorio_fotos_envio.zip");
            }


            $fd = fopen("/tmp/$pastaTemp/relatorio_fotos_envio.html", "w");
            fwrite($fd,$arquivoHtml);
            fclose($fd);
            system("zip -rq9 xls/relatorio_fotos_envio.zip /tmp/$pastaTemp");

?>
		</tbody>
	</table>

	<br />
<?php
    if (file_exists("xls/relatorio_fotos_envio.zip")) {
        echo "<br /><a href='xls/relatorio_fotos_envio.zip'>Download do arquivo ZIP</a><br />";
    }
}
?>
<br />
<?php
include "rodape.php";
?>
