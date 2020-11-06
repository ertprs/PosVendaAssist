<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';

if(strlen($os)>0 AND $ver=='endereco'){
?>
	<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
		

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>

	</head>

	<body>
<?
	$sql = "SELECT  PO.nome                  ,
					PF.contato_endereco      ,
					PF.contato_numero        ,
					PF.contato_complemento   ,
					PF.contato_bairro        ,
					PF.contato_cidade        ,
					PF.contato_estado        ,
					PF.contato_cep           ,
					OS.consumidor_nome       ,
					OS.consumidor_endereco   ,
					OS.consumidor_numero     ,
					OS.consumidor_complemento,
					OS.consumidor_bairro     ,
					OS.consumidor_cidade     ,
					OS.consumidor_estado     ,
					OS.consumidor_cep        ,
					OS.os                    ,
					OS.sua_os                ,
					OS.qtde_km
			FROM tbl_os            OS
			JOIN tbl_posto         PO ON PO.posto = OS.posto
			JOIN tbl_posto_fabrica PF ON PF.posto = OS.posto AND OS.fabrica = PF.fabrica
			WHERE OS.os      = $os
			AND   OS.fabrica = $login_fabrica";
	$res_os = pg_exec($con,$sql);
	if (pg_numrows($res_os)>0){
		$nome                   = pg_result($res_os,0,nome);
		$contato_endereco       = pg_result($res_os,0,contato_endereco);
		$contato_numero         = pg_result($res_os,0,contato_numero);
		$contato_complemento    = pg_result($res_os,0,contato_complemento);
		$contato_bairro         = pg_result($res_os,0,contato_bairro);
		$contato_cidade         = pg_result($res_os,0,contato_cidade);
		$contato_estado         = pg_result($res_os,0,contato_estado);
		$contato_cep            = pg_result($res_os,0,contato_cep);
		$consumidor_nome        = pg_result($res_os,0,consumidor_nome);
		$consumidor_endereco    = pg_result($res_os,0,consumidor_endereco);
		$consumidor_numero      = pg_result($res_os,0,consumidor_numero);
		$consumidor_complemento = pg_result($res_os,0,consumidor_complemento);
		$consumidor_bairro      = pg_result($res_os,0,consumidor_bairro);
		$consumidor_cidade      = pg_result($res_os,0,consumidor_cidade);
		$consumidor_estado      = pg_result($res_os,0,consumidor_estado);
		$consumidor_cep         = pg_result($res_os,0,consumidor_cep);
		$os                     = pg_result($res_os,0,os);
		$sua_os                 = pg_result($res_os,0,sua_os);
		$qtde_km                = number_format(pg_result($res_os,0,qtde_km),3,',','.');
		if(strlen($sua_os)==0) $sua_os = $os;
		?>
		<br />
		<table class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr>
					<th>OS</th>
					<th>Posto</th>
					<th>Distância(ida e volta) KM</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?=$sua_os?></th>
					<th><?=$nome?></th>
					<th><?=$qtde_km?> Km</th>
				</tr>
				<tr>
					<td colspan="3">
						<table class='table table-bordered table-fixed' >
							<thead>
								<tr>
									<th>Endereço</th>
									<th>Bairro</th>
									<th>Cidade</th>
									<th>Estado</th>
									<th>CEP</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?=$contato_endereco?>, <?=$contato_numero?></td>
									<td><?=$contato_bairro?></td>
									<td><?=$contato_cidade?></td>
									<td><?=$contato_estado?></td>
									<td><?=$contato_cep?></td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<br />
		<table class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<th>Consumidor</th>
			</thead>
			<tbody>
				<tr>
					<td><?=$consumidor_nome?></td>
				</tr>
				<tr>
					<td>
						<table class='table table-bordered table-fixed' >
							<thead>
								<th>Endereço</th>
								<th>Bairro</th>
								<th>Cidade</th>
								<th>Estado</th>
								<th>CEP</th>
							</thead>
							<tbody>
								<tr>
									<td><?=$consumidor_endereco?>, <?=$consumidor_numero?></td>
									<td><?=$consumidor_bairro?></td>
									<td><?=$consumidor_cidade?></td>
									<td><?=$consumidor_estado?></td>
									<td><?=$consumidor_cep?></td>
								</tr>
							</tbody>
						</table>
					</td>	
				</tr>
			</tbody>
		</table>	
	<?
	}
	?>
</body>
</html>
	<?
	exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if ($_POST["btn_acao"] == "submit") {

	$data_inicial 		= $_POST['data_inicial'];
	$data_final   		= $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$linha              = $_POST['linha'];


	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$xdata_inicial = "{$yi}-{$mi}-{$di} 00:00:00";
			$xdata_final   = "{$yf}-{$mf}-{$df} 23:59:59";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}else{
				$sql_add .= " AND tbl_os.data_digitacao BETWEEN '{$xdata_inicial}' AND '{$xdata_final}' ";
			}
		}
	}

	if (strlen($posto_codigo) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
			$sql_add .= " AND tbl_posto_fabrica.posto = '$posto' ";
		}
	}

	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
			$sql_add .= " AND tbl_produto.produto = $produto ";
		}
	}

	if (strlen($linha) > 0) {
		$sql_add .= " AND tbl_produto.linha = $linha ";
	}

	$sql_join = '';
	if ($login_fabrica == 30){
		$sql_join = " JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato";
		$sql_add .=" AND tbl_extrato_extra.exportado is not null";
	}else{
		$sql_add .=" AND tbl_extrato.exportado is null";
	}
	

	$sql =  "
		SELECT tbl_os.os ,
			tbl_os.sua_os ,
			tbl_os.consumidor_nome ,
			tbl_os.consumidor_cidade,
			tbl_os.qtde_km ,
			tbl_os.qtde_km_calculada ,
			tbl_os.autorizacao_domicilio ,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
			TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
			tbl_os.fabrica ,
			tbl_os.consumidor_nome ,
			tbl_os.nota_fiscal_saida ,
			to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
			tbl_posto.nome AS posto_nome ,
			tbl_posto_fabrica.codigo_posto ,
			tbl_posto_fabrica.contato_estado ,
			tbl_produto.referencia AS produto_referencia ,
			tbl_produto.descricao AS produto_descricao ,
			tbl_produto.voltagem,
			to_char(tbl_numero_serie.data_fabricacao,'DD/MM/YYYY')  AS data_fabricacao
		FROM tbl_os
		JOIN tbl_produto       ON tbl_produto.produto = tbl_os.produto
		JOIN tbl_posto         ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		JOIN tbl_os_extra      ON tbl_os_extra.os = tbl_os.os
		JOIN tbl_extrato       ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = {$login_fabrica}
		JOIN tbl_numero_serie  ON tbl_produto.produto = tbl_numero_serie.produto AND tbl_numero_serie.serie = tbl_os.serie
		$sql_join
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.qtde_km > 0
		{$sql_add}
		ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os";
	

	$resSubmit = pg_query($con, $sql);
}

$layout_menu = "financeiro";
$title = "Relatório de gastos com km";

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>


<script language="JavaScript">

$(function() {
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("produto", "peca", "posto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function retorna_produto (retorno) {
	$("#produto_referencia").val(retorno.referencia);
	$("#produto_descricao").val(retorno.descricao);
}

function ver(os) {
	var url = "<? echo $PHP_SELF ?>?ver=endereco&os="+os;
	Shadowbox.open({ content: url, player: "iframe", width: 900, height: 600  });
}
</script>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
<? if($login_fabrica == 50){ ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_referencia'>Ref. Produto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_descricao'>Descrição Produto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span8'>
			<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='linha'>Linha</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="linha" id="linha">
							<option value=""></option>
							<?php
							$sql = "SELECT linha, nome
									FROM tbl_linha
									WHERE fabrica = $login_fabrica
									AND ativo";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
								$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;

							?>
								<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

									<?php echo $key['nome']?>

								</option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<? } ?>
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>


<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

			if (pg_num_rows($resSubmit) > 500) {
				$count = 500;
				?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($resSubmit);
			}
		?>
		<table id="resultado_os" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>OS</th>
						<th>DATA <br>DIGITAÇÃO</th>
						<th>Posto</th>
						<th>Cidade Atendimento</th>
						<th>&nbsp;</th>
						<th>Produto</th>
						<th>Descrição</th>
						<th>Data de Fabricação</th>
						<th>KM</th>
						<th>VALOR KM</th>
					</tr>
				</thead>
				<tbody>
<?php
				for ($x=0; $x<pg_numrows($resSubmit);$x++){

					$os						= pg_result($resSubmit, $x, os);
					$sua_os					= pg_result($resSubmit, $x, sua_os);
					$codigo_posto			= pg_result($resSubmit, $x, codigo_posto);
					$posto_nome				= pg_result($resSubmit, $x, posto_nome);
					$qtde_km				= pg_result($resSubmit, $x, qtde_km);
					$qtde_km_calculada		= pg_result($resSubmit, $x, qtde_km_calculada);
					$autorizacao_domicilio	= pg_result($resSubmit, $x, autorizacao_domicilio);
					$consumidor_nome		= pg_result($resSubmit, $x, consumidor_nome);
					$consumidor_cidade		= pg_result($resSubmit, $x, consumidor_cidade);
					$produto_referencia		= pg_result($resSubmit, $x, produto_referencia);
					$produto_descricao		= pg_result($resSubmit, $x, produto_descricao);
					$produto_voltagem		= pg_result($resSubmit, $x, voltagem);
					$data_digitacao			= pg_result($resSubmit, $x, data_digitacao);
					$data_abertura			= pg_result($resSubmit, $x, data_abertura);
					$data_fabricacao		= pg_result($resSubmit, $x, data_fabricacao);
					$qtde_kmx = number_format($qtde_km,2,',','.');
					$cores++;
					$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
					if(strlen($sua_os)==o)$sua_os=$os;

					echo "<tr bgcolor='$cor' id='linha_$x'>";
					echo "<td class='tac'><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a></td>";
					echo "<td class='tac'>".$data_digitacao. "</td>";
					echo "<td class='tal' >".$codigo_posto." - ".$posto_nome."</td>";
					echo "<td class='tal'>$consumidor_cidade </td>";
					echo "<td class='tac'><a href='javascript:ver($os);'>Ver Endereços</a></td>";
					echo "<td class='tal'><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
					echo "<td class='tal'><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
					echo "<td class='tac'>".$data_fabricacao. "</td>";
					echo "<td>";
					echo "<input type='hidden' size='5' name='qtde_km_os_$x' value='$qtde_km'>{$qtde_kmx}</td>";
					echo "<td class='tar'>".number_format($qtde_km_calculada,2,',','.')."</td>";
					echo "</tr>";

				}
?>
			</tbody>
		</table>

		<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os" });
				</script>
			<?php
			}
	}else{
		echo '
		<div class="container">
		<div class="alert">
			    <h4>Nenhum resultado encontrado</h4>
		</div>
		</div>';
	}
}

include "rodape.php";

?>
