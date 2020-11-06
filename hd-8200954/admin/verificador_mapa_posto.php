<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
$admin_privilegios = "gerencia";
include_once "autentica_admin.php";
include_once "funcoes.php";

if($_GET['buscaCidade']){
    $uf = $_GET['estado'];
    $cidade_selected = $_GET['cidade_selected'];
    $estado = "and contato_estado = '$uf' ";

    $sql = "SELECT DISTINCT UPPER(fn_retira_especiais(TRIM(contato_cidade))) AS contato_cidade, UPPER(fn_retira_especiais(TRIM(contato_estado))) AS contato_estado FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica $estado ORDER BY contato_cidade,contato_estado";

    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
        $retorno = "<option value=''></option>";
        $retorno .= "<option value='t_cidades'>" . total("Todas cidades") . "</option>";
        for($i = 0; $i < pg_numrows($res); $i++){
            $cidade = pg_result($res,$i,'contato_cidade');
            $estado = pg_result($res,$i,'contato_estado');

            $selec = ($cidade == $cidade_selected) ? "SELECTED" : "";

            $retorno .= "<option value='$cidade' $selec>$cidade</option>";
        }
    } else {
        $retorno .= "<option value=''>" . traduz("Cidade não encontrada") . "</option>";
    }

    echo $retorno;
    exit;
}

if($login_fabrica == 10){
	$sql = "SELECT fabrica, nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE ORDER BY nome";
	$resFabrica = pg_query($con, $sql);

	if(isset($_POST["campo_fabrica"])){
		$campo_fabrica = $_POST["campo_fabrica"];

		if(empty($campo_fabrica)){
			$msg_error[] = traduz("Fábrica não selecionada");
		}
	}
}else{
	$campo_fabrica = $login_fabrica;
	if($_POST) {
		$credenciamento = $_POST['credenciamento'];
		if(!empty($credenciamento)) {
			$cond = " AND tbl_posto_fabrica.credenciamento = '$credenciamento' ";
		}

		$estado = $_POST['estado'];
		if(!empty($estado)) {
			$cond .= " AND tbl_posto_fabrica.contato_estado = '$estado' ";
		}

		$cidade = $_POST['cidade'];
		if(!empty($cidade)) {
			$cond .= " AND tbl_posto_fabrica.contato_cidade = '$cidade' ";
		}
	
		$linha_id = $_POST['linha'];
		if (!empty($linha_id)) {
			$join = " LEFT JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto ";
			$cond .= " AND tbl_posto_linha.linha = $linha_id ";	
		}
	}
}

if(!empty($campo_fabrica)){
	$sql = "SELECT tbl_posto.nome,
			tbl_posto_fabrica.posto,
			tbl_posto_fabrica.contato_endereco,
			tbl_posto_fabrica.contato_numero,
			tbl_posto_fabrica.contato_bairro,
			tbl_posto_fabrica.contato_cidade,
			tbl_posto_fabrica.contato_estado,
			tbl_posto_fabrica.contato_cep,
			tbl_posto_fabrica.latitude,
			tbl_posto_fabrica.longitude
		FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			$join
			WHERE tbl_posto_fabrica.fabrica = {$campo_fabrica} {$cond} ORDER BY tbl_posto_fabrica.contato_estado";
	$resPosto = pg_query($con,$sql);
}

/*
	EXEMPLO DE GEOCODE POR MEIO DE URL
	http://maps.google.com/maps/api/geocode/json?address=-38.6007254,-141.1127514&sensor=false.
*/

$title = traduz("Mapa dos Postos");
include __DIR__.'/cabecalho_new.php';

function consultarMapsApi($lat, $lng){
	$url = "http://maps.google.com/maps/api/geocode/json?address={$lat},{$lng}&sensor=false";

	$ch = curl_init();
	$timeout = 10;
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$file_contents = curl_exec($ch);
	curl_close($ch);

	$output = json_decode($file_contents);
	$return = false;

	foreach ($output->results[0]->address_components as $key => $value) {
		if($value->short_name == "BR"){
			$return = true;
			break;
		}
	}

	return $return;
}

$plugins = array(
   "shadowbox"
);

include __DIR__."/plugin_loader.php";


if(in_array($login_fabrica, array(152, 161, 180, 181, 182))){
	$distancia_km = 300 * 1000;
} else if($login_fabrica == 125){
    $distancia_km = 5000 * 1000;
} else if($login_fabrica == 74){
    $distancia_km = 2000 * 1000;
} else if($login_fabrica == 30){
    $distancia_km = 200 * 1000;
} else if($login_fabrica == 177){
    $distancia_km = 50 * 1000;
} else if(in_array($login_fabrica, array(169,170))){
	$distancia_km = 30 * 1000;
}else {
    $distancia_km = 100 * 1000;
}

?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<title><?= $title; ?></title>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<!-- <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script> -->

<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js" ></script>		
<script src="plugins/leaflet/map.js" ></script>

<style type="text/css">
	#map {
		position:relative;
		top:0;
		bottom:0;
		width:700px;
		height:500px;
		margin-left: 10%;
	}

	.formulario {
		text-align: center;
	}

	select {
		margin-top: 10px;
	}

	#tabela_posto_errado{
		text-align: center;
		margin-top: 10px;
	}
	#posto_errado, #posto_correto {
		margin-top: 10px;
		margin-bottom: 10px;
		margin-left: 40%;
	}
	#sb-container {
		z-index: 9999 !important;
	}
</style>

<script type="text/javascript" language='javascript'>
var map = null;
var markers = null;

$(function(){
	Shadowbox.init();

	$("button[id^=btAlterar_]").click(function(){
		var posto     = this.id.replace(/\D/g, "");

        Shadowbox.open({
            content: "atualiza_localizacao_posto.php?posto="+posto,
            player: "iframe",

            options: {
                enableKeys: false
            }
        });
	});

	$("button[id^=btMostrarPosto_]").click(function(){
		var posto = this.id.replace(/\D/g, "");
		var latitude  = $("#latitude_"+posto).val();
		var longitude = $("#longitude_"+posto).val();

		mostrarMapa(latitude, longitude);
	});
});

function addRow(resultado){
	resultado = resultado.posto[0];
	var row = "<tr id='posto_"+resultado.posto+"'> \
			<td>"+resultado.nome+" \
				<input type='hidden' id='posto_"+resultado.posto+"' value='"+resultado.posto+"'>\
				<input type='hidden' id='nome_"+resultado.posto+"' value='"+resultado.nome+"'>\
				<input type='hidden' id='cep_"+resultado.posto+"' value='"+resultado.contato_cep+"'>\
				<input type='hidden' id='endereco_"+resultado.posto+"' value='"+resultado.contato_endereco+"'>\
				<input type='hidden' id='numero_"+resultado.posto+"' value='"+resultado.contato_numero+"'>\
				<input type='hidden' id='bairro_"+resultado.posto+"' value='"+resultado.contato_bairro+"'>\
				<input type='hidden' id='cidade_"+resultado.posto+"' value='"+resultado.contato_cidade+"'>\
				<input type='hidden' id='estado_"+resultado.posto+"' value='"+resultado.contato_estado+"'>\
				<input type='hidden' id='latitude_"+resultado.posto+"' value='"+resultado.latitude+"'>\
				<input type='hidden' id='longitude_"+resultado.posto+"' value='"+resultado.longitude+"'>\
			</td> \
			<td>"+resultado.contato_cep+"</td> \
			<td>"+resultado.contato_endereco+"</td> \
			<td>"+resultado.contato_numero+"</td> \
			<td>"+resultado.contato_bairro+"</td> \
			<td>"+resultado.contato_cidade+"</td> \
			<td>"+resultado.contato_estado+"</td> \
			<td id='cel_latitude_"+resultado.posto+"'>"+resultado.latitude+"</td> \
			<td id='cel_longitude_"+resultado.posto+"'>"+resultado.longitude+"</td> \
			<td id='alterar_"+resultado.posto+"'><button type='button' class='btn btn-default btn-mini' id='btAlterar_"+resultado.posto+"'>Alterar</button></td> \
			<td id='mostrarPosto_"+resultado.posto+"'><button type='button' class='btn btn-default btn-mini' id='btMostrarPosto_"+resultado.posto+"' disabled>[ver mapa]</button></td> \
		</tr>";
	$("#tabela_posto_errado > tbody").append(row);
}

function addMarker(latitude, longitude, resultado){

	/*
		ADICIONAR MARCADOR MAPBOX
	*/
	markers.add(latitude, longitude, "blue", resultado.posto[0].nome, null, resultado.posto[0]);
	markers.clear();
	markers.render();

	soma_posto_correto();
}

function soma_posto_errado(){
	var num = $("#total_posto_errado").val();

	if(num == ""){
		num = 0;
	}else{
		num = parseInt(num);
	}
	num++;

	$("#total_posto_errado").val(num);
	$("#posto_errado").html("<?php traduz("Total de Postos com informação errada:");?>"+num);
}

function soma_posto_correto(){
	var num = $("#total_posto_correto").val();

	if(num == ""){
		num = 0;
	}else{
		num = parseInt(num);
	}
	num++;

	$("#total_posto_correto").val(num);
	$("#posto_correto").html("<?php traduz("Total de Postos Corretos:");?>"+num);
}

function posto_atualizado(posto, latitude, longitude) {
	$("#latitude_"+posto).val(latitude);
	$("#longitude_"+posto).val(longitude);

	$("#cel_latitude_"+posto).html(latitude);
	$("#cel_longitude_"+posto).html(longitude);

    $("#alterar_"+posto).html("");
    $("#alterar_"+posto).html("<label class='label label-info'>" + <?php echo traduz("Atualizado");?> + "</label>");

    var informacao = {posto:[]};

    informacao.posto.push({
    	posto     		 : posto,
		nome      		 : $("#nome_"+posto).val(),
		contato_endereco : $("#endereco_"+posto).val(),
		contato_numero   : $("#numero_"+posto).val(),
		contato_bairro   : $("#bairro_"+posto).val(),
		contato_cidade   : $("#cidade_"+posto).val(),
		contato_estado   : $("#estado_"+posto).val(),
		contato_cep    	 : $("#cep_"+posto).val(),
		latitude  		 : latitude,
		longitude 		 : longitude
	});

	markers.add(latitude, longitude, "blue", $("#nome_"+posto).val(), null, informacao);
	markers.clear();
	markers.render();
	L.circle([latitude, longitude], <?=$distancia_km?>, { weight: 0, fillColor: '#F3CC15', fillOpacity: 0.3 }).addTo(map.map);
	mostrarMapa(latitude, longitude);
    $("#btMostrarPosto_"+posto).attr("disabled", false);

    var num = $("#total_posto_errado").val();

	if(num == ""){
		num = 0;
	}else{
		num = parseInt(num);
	}
	num--;

	$("#total_posto_errado").val(num);
	$("#posto_errado").html("<?php echo traduz("Total de Postos com informação errada:");?>" +num);
}


function montaComboCidade(estado){

	var cidade_selected = "";

	if ($("#cidade_selected").val() != "" || $("#cidade_selected").val() != undefined) {
		cidade_selected = $("#cidade_selected").val();
	}

    $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado+"&cidade_selected="+cidade_selected,
            cache: false,
            success: function(data) {
                $('#cidade').html(data);
            }

        });

}


</script>

<form name="fm_verificador_mapa_posto" method="POST" action="<?=$PHP_SELF?>">
	<?php
		$class = "";
		$mensagem = "";
		if(count($msg_error) > 0){
			$class = "alert alert-error";
			$mensagem = $msg_error[0];
		}
	?>
	<div id="mensagem" class="<?=$class?>"><?=$mensagem?></div>
	<div class="formulario">
	<?php
		if($login_fabrica == 10 && pg_num_rows($resFabrica) > 0){
			?>
			<h4><?php echo traduz("Mapa dos postos da Fábrica");?></h4>
			<select id="campo_fabrica" name="campo_fabrica">
				<option value=""><?php echo traduz("Selecione a Fábrica");?></option>
			<?php
			while($objeto_fabrica = pg_fetch_object($resFabrica)){
				if($campo_fabrica == $objeto_fabrica->fabrica){
					$selected = "selected";
				}else{
					$selected = "";
				}
			?>
				<option value="<?=$objeto_fabrica->fabrica?>" <?=$selected?>><?=strtoupper($objeto_fabrica->nome)?> - <?=$objeto_fabrica->fabrica?></option>
			<?php
			}
			?>
			</select>
			<button type="submit" class="btn"><?php echo traduz("Pesquisar");?></button>
		<?php
		}else{
			
			$array_estados = $array_estados(); 

		?>

			<h4><?=traduz('Mapa dos postos da Fábrica')?></h4>
			Estado <select id='estado' name='estado' class="span2" onchange='montaComboCidade(this.value)'>
				<option value="" ><?=traduz('Selecione')?></option>
				<?php
				#O $array_estados está no arquivo funcoes.php
				foreach ($array_estados as $sigla => $nome_estado) {
					$selected = ($sigla == $consumidor_estado) ? "selected" : "";

					echo "<option value='{$sigla}' {$selected} >" . $nome_estado . "</option>";
				}
				?>
			</select>&nbsp;&nbsp;&nbsp;
			Cidade <select id='cidade' name='cidade' class="span2" >
				<option value="" ><?=traduz('Selecione Estado')?></option>
			</select>&nbsp;&nbsp;&nbsp;
			Status <select id="credenciamento" name="credenciamento">
				<option value=""><?=traduz('Selecione')?></option>
				<option value="CREDENCIADO"><?=traduz('CREDENCIADO')?></option>
				<option value="DESCREDENCIADO"><?=traduz('DESCREDENCIADO')?></option>
				<option value="EM CREDENCIAMENTO"><?=traduz('EM CREDENCIAMENTO')?></option>
				<option value="EM DESCREDENCIAMENTO"><?=traduz('EM DESCREDENCIAMENTO')?></option>
			</select> 
			<button type="submit" class="btn"><?=traduz('Pesquisar')?></button>		

			<?php if (in_array($login_fabrica, [167, 203])) { ?>
				<div class="row">
					<div class="span1"></div>
					<div class="span3">
						<label><?php echo traduz("Linha"); ?></label>
						<select id='linha' name='linha' class="span2">
							<option value="" ><?php echo traduz("Selecione"); ?></option>
							<?php
							$sql_linha = "	SELECT  linha, nome
								            FROM    tbl_linha
								            WHERE   tbl_linha.fabrica = $login_fabrica
								            AND tbl_linha.ativo IS TRUE
								            ORDER BY tbl_linha.nome";
							$res_linha = pg_query ($con,$sql_linha);
							$linhas = pg_fetch_all($res_linha);
							foreach ($linhas as $p => $linha) {

								$selected = ($linha['linha'] == $linha_id) ? "selected" : "";

								echo "<option value='".$linha['linha']."' {$selected} >" . $linha['nome'] . "</option>";
							}
							?>
						</select>		
					</div>
				</div>				
			<?php } ?>	
			<br />
			<div class="row">
				<div class="span12 tac">
					<button type="submit" class="btn"><?php echo traduz("Pesquisar"); ?></button>			
				</div>
			</div>			
		<?
		}
	?>
	</div>
	<br/>
	<label class="label label-success" id="posto_correto"></label>
	<br/>
	<div id='map'></div>
	<script>
			
		map = new Map("map");
		map.load();
		markers = new Markers(map);

		function mostrarMapa(latitude, longitude){
			map.setView(latitude, longitude, 12);
		}

	</script>
	<?php
		if(pg_num_rows($resPosto) > 0){
		?>
			<input type="hidden" id="total_posto_correto" value="0">
			<input type="hidden" id="total_posto_errado" value="0">
			<label class="label label-important" id="posto_errado"></label>
			<table id="tabela_posto_errado" class="table table-striped table-bordered table-large">
				<thead>
					<tr>
						<th><?php echo traduz("Posto");?></th>
						<th><?php echo traduz("CEP");?></th>
						<th><?php echo traduz("Endereço");?></th>
						<th><?php echo traduz("nº");?></th>
						<th><?php echo traduz("Bairro");?></th>
						<th><?php echo traduz("Cidade");?></th>
						<th><?php echo traduz("Estado");?></th>
						<th><?php echo traduz("Latitude");?></th>
						<th><?php echo traduz("Longitude");?></th>
						<th><?php echo traduz("Alterar Informação");?></th>
						<th><?php echo traduz("Mapa");?></th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
			<?php
			$count = 0;
			while($objeto_posto = pg_fetch_object($resPosto)){
				if(strripos($objeto_posto->nome, '"') == true){
					$objeto_posto->nome = str_replace('"', '', $objeto_posto->nome);
				}
				?>
				<script>
					var posto_errado = {posto:[]};
					posto_errado.posto.push({
						posto            : "<?=$objeto_posto->posto?>",
						nome             : "<?=$objeto_posto->nome?>",
						contato_endereco : "<?=$objeto_posto->contato_endereco?>",
						contato_numero   : "<?=$objeto_posto->contato_numero?>",
						contato_bairro   : "<?=$objeto_posto->contato_bairro?>",
						contato_cidade   : "<?=$objeto_posto->contato_cidade?>",
						contato_estado   : "<?=$objeto_posto->contato_estado?>",
						contato_cep      : "<?=$objeto_posto->contato_cep?>",
						latitude         : "<?=$objeto_posto->latitude?>",
						longitude        : "<?=$objeto_posto->longitude?>"
					});

					<?php
					if($objeto_posto->latitude == "" || $objeto_posto->longitude == ""){
					?>
						soma_posto_errado();
						addRow(posto_errado);
					<?php
					}else {
						?>
						markers.add("<?=$objeto_posto->latitude?>","<?=$objeto_posto->longitude?>", "blue", "<?=$objeto_posto->nome?>", null, posto_errado);
						L.circle([<?=$objeto_posto->latitude?>, <?=$objeto_posto->longitude?>], <?=$distancia_km?>, { weight: 0, fillColor: '#F3CC15', fillOpacity: 0.3 }).addTo(map.map);
					<?php
					}
					?>
				</script>
			<?php
			}
			?>
			<script>
				markers.render();
				markers.focus();
			</script>
		<?php
		}
	?>
</form>

<script>
	$(document).ready(function() {
		if ($("#estado option:selected").val() != "" && $("#estado option:selected").val() != undefined) {
			montaComboCidade($("#estado option:selected").val());
		}
	});
</script>

<?php
include "rodape.php";
?>
