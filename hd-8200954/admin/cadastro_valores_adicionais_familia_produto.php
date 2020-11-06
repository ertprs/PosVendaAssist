<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

function mostraDados(){
	global $con, $login_fabrica;

	if(in_array($login_fabrica, [169,170])){
		$sql = "SELECT valores_adicionais, familia, descricao AS familia_desc
				FROM tbl_familia
				WHERE fabrica = $login_fabrica
				AND valores_adicionais IS NOT NULL
				ORDER BY descricao";
	}else{
		$sql = "SELECT DISTINCT tbl_produto.valores_adicionais, tbl_produto.familia, tbl_familia.descricao AS familia_desc
				FROM tbl_produto
				JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
				WHERE tbl_produto.fabrica_i = $login_fabrica
				AND tbl_produto.valores_adicionais IS NOT NULL
				ORDER BY tbl_familia.descricao";
	}

	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){

		$retorno = "<br><table class='table table-striped table-bordered table-fixed' width='700' align='center' class='tabela'>
						<tr class='titulo_coluna'>
							<th>".traduz("FamÌlia")."</th><th>".traduz("ServiÁo")."</th> <th>".traduz("Valor")."</th> <th>".traduz("AÁ„o")."</th>
						</tr>";
		for($j = 0; $j < pg_num_rows($res); $j++){
			$valor = pg_fetch_result($res, $j, 'valores_adicionais');
			$familia = pg_fetch_result($res, $j, 'familia');
			$familia_desc = pg_fetch_result($res, $j, 'familia_desc');
			$valores_adicionais = json_decode($valor,true);
			foreach ($valores_adicionais as $key =>$value) {
				unset($adicionar);
				$servico = utf8_decode($key);
				$valor   = $value;

				if(!in_array($login_fabrica, [169,170])){
					$sql = "SELECT count(1) FROM tbl_produto where fabrica_i = $login_fabrica and ativo and familia = $familia and (valores_adicionais !~'$servico' or valores_adicionais isnull)";
					$resx = pg_query($con, $sql);
					$faltante = pg_fetch_result($resx,0,0);
					if($faltante > 0) {
						$adicionar = "<br><button  class='btn btn-primary' onclick='atualizaRegistro(\"$servico\",\"$valor\",\"$familia\")'>Replicar para novos produtos</button>";
					}
				}

				$retorno .="<tr>
								<td>$familia_desc</td>
								<td>$servico</td>
								<td class='tac'>$valor</td>
								<td class='tac'><input class='btn btn-danger' type='button' value='Excluir' onclick='excluiRegistro(\"$servico\",\"$familia\")'>$adicionar</td>
							</tr>";
			}
		}

		$retorno .= "</table>";

		return $retorno;
	}
}

if($_GET['ajax']){
	$servico	= utf8_decode($_GET['servico']);
	$familia 	= $_GET['familia'];
	$valor 		= $_GET['valor'];

	$sql = "SELECT fn_retira_especiais('$servico')";
    	$res = pg_query($con,$sql);
    	$servico = utf8_encode(strtoupper(pg_fetch_result($res, 0, 0)));

    $valores = array($servico => $valor);

    if(in_array($login_fabrica,[169,170])){

    	$sql = "SELECT valores_adicionais FROM tbl_familia WHERE familia = {$familia} AND fabrica = {$login_fabrica}";
    	$res = pg_query($con,$sql);

    	$valores_familia = pg_fetch_result($res, 0, 'valores_adicionais');

    	if(strlen($valores_familia) > 0){
    		$valores_familia = json_decode($valores_familia,true);
    		$valores_familia = array_merge($valores_familia, $valores);
    	}else{
    		$valores_familia = $valores;
    	}

    	$valores_familia = json_encode($valores_familia);

    	$sql = "UPDATE tbl_familia SET valores_adicionais = '$valores_familia' WHERE familia = {$familia} AND fabrica = {$login_fabrica}";
    	$res = pg_query($con,$sql);
    }

	$sql = "SELECT DISTINCT valores_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND familia = $familia";
	$res = pg_query($con,$sql);

	$valor_existe = pg_fetch_result($res, 0, 'valores_adicionais');

	if(!empty($valor_existe) && $valor_existe != "null"){
		$valores_adicionais = json_decode($valor_existe,true);
		$valores_adicionais = array_merge($valores_adicionais, $valores);
	}else{
		$valores_adicionais = $valores;
	}


	$valores_adicionais = json_encode($valores_adicionais);
    	$sql = "UPDATE tbl_produto SET valores_adicionais = '$valores_adicionais' WHERE fabrica_i = $login_fabrica AND familia = $familia";

    	$res = pg_query($con,$sql);

	if(pg_last_error($con)){
		echo pg_last_error($con);
	}else{
		$retorno = mostraDados();
		$retorno = $retorno;
		echo "OK|$retorno";
	}

	exit;
}

if($_GET['ajax_atualiza']){
	$servico	= utf8_decode($_GET['servico']);
	$familia 	= $_GET['familia'];
	$valor 		= $_GET['valor'];

	$sql = "SELECT fn_retira_especiais('$servico')";
	$res = pg_query($con,$sql);
	$servico = utf8_encode(strtoupper(pg_fetch_result($res, 0, 0)));

	if(in_array($login_fabrica, [169,170])){
		$sql = "UPDATE tbl_familia SET valores_adicionais = jsonb_set(coalesce(valores_adicionais,'{}')::jsonb,'{".$servico."}','\"$valor\"') WHERE fabrica = $login_fabrica AND familia = $familia" ;
		$res = pg_query($con,$sql);
	}

	if(!empty($familia) and !empty($valor)) {
		$sql = "UPDATE tbl_produto SET valores_adicionais = jsonb_set(coalesce(valores_adicionais,'{}')::jsonb,'{".$servico."}','\"$valor\"') WHERE fabrica_i = $login_fabrica AND familia = $familia and ativo and (valores_adicionais !~'$servico' or valores_adicionais isnull)" ;
		$res = pg_query($con,$sql);
	}

	if(pg_last_error($con)){
		echo pg_last_error($con);
	}else{
		$retorno = mostraDados();
		$retorno = $retorno;
		echo "OK|$retorno";
	}

	exit;
}

if($_GET['ajax_exclui']){
	$servico 	= $_GET['servico'];
	$familia 	= $_GET['familia'];

	if(in_array($login_fabrica,[169,170])){

    	$sql = "SELECT valores_adicionais FROM tbl_familia WHERE familia = {$familia} AND fabrica = {$login_fabrica}";
    	$res = pg_query($con,$sql);

    	$valores_familia = pg_fetch_result($res, 0, 'valores_adicionais');

    	if(strlen($valores_familia) > 0){
    		$valores_familia = json_decode($valores_familia,true);
    		unset($valores_familia[$servico]);
    	}

    	if(!count($valores_familia)){
			$valores_familia = "null";
		}else{
			$valores_familia = "'".json_encode($valores_familia)."'";
		}

    	$sql = "UPDATE tbl_familia SET valores_adicionais = {$valores_familia} WHERE familia = {$familia} AND fabrica = {$login_fabrica}";
    	$res = pg_query($con,$sql);
    }

	$sql = "SELECT DISTINCT valores_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND familia = $familia";
	$res = pg_query($con,$sql);
	$valor = pg_fetch_result($res, 0, 'valores_adicionais');
	if(!empty($valor)){
		$valores_adicionais = json_decode($valor,true);
		unset($valores_adicionais[$servico]);
	}

	if(!count($valores_adicionais)){
		$valores_adicionais = "null";
	}else{
		$valores_adicionais = "'".json_encode($valores_adicionais)."'";
	}

	$sql = "UPDATE tbl_produto SET valores_adicionais = $valores_adicionais WHERE fabrica_i = $login_fabrica AND familia = $familia";
	$res = pg_query($con,$sql);

	if(pg_last_error($con)){
		echo pg_last_error($con);
	}else{
		$retorno = mostraDados();
		$retorno = utf8_encode($retorno);
		echo "OK|$retorno";
	}

	exit;
}

$layout_menu = "cadastro";
$title = traduz("CADASTRO DE VALORES ADICIONAIS POR FAMÕLIA");

include "cabecalho_new.php";

$plugins = array(
    "price_format"
);

include "plugin_loader.php";
?>
<script type="text/javascript">

	$(function(){
		$(".valor").priceFormat({
			prefix: '',
			thousandsSeparator: '',
			centsSeparator: ",",
			centsLimit: 2			
		});
	});

    function retiraAcentos(palavra){

        var com_acento = '·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
        var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
        var newPalavra = "";

        for(i = 0; i < palavra.length; i++) {
            if (com_acento.search(palavra.substr(i,1)) >= 0) {
                newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i,1)),1);
            }
            else{
                newPalavra += palavra.substr(i,1);
            }
        }

        return newPalavra.toUpperCase();
    }

	function gravaDados(){
		var servico 	= $("input[name=servico]").val();
		var familia 	= $("select[name=familia]").val();
		var valor 		= $("input[name=valor]").val();

		if(servico == "" || familia == "" || valor == ""){
			alert('<?=traduz("Informe todos os dados para gravar o registro")?>');
			return
		}
		$.ajax({
			url: "<?=$PHP_SELF?>",
			cache: false,
            type:'GET',
            data:{
                ajax:"sim",
                servico:servico,
                familia:familia,
                valor:valor
            }
        })
		.done(function(data) {
            retorno = data.split('|');

            if (retorno[0]=="OK") {
                $("input[name=servico]").val("").html("");
                $("input[name=valor]").val("").html("");
                $("#resultado").html(retorno[1]);
		$("#msg_success").text('<?=traduz("Valor Adicional cadastrado com sucesso!")?>');
                $('#msg_success').show();
                setTimeout(function(){
                    $('#msg_success').hide();
                }, 10000);

            } else {
                alert(retorno[0]);
            }
        });
	}

	function excluiRegistro(servico,familia){
		$.ajax({
			url: "<?=$PHP_SELF?>",
            type:'GET',
            cache: false,
            data:{
                ajax_exclui:"sim",
                servico:servico,
                familia:familia
            }
        })
		.done(function(data) {
            retorno = data.split('|');

            if (retorno[0]=="OK") {
                $("#resultado").html(retorno[1]);

                $('#msg_success').text('<?=traduz("Valor Adicional Excluido com Sucesso!")?>');
                $('#msg_success').show();

                setTimeout(function(){
                    $('#msg_success').hide();
                }, 10000);

            } else {
                alert(retorno[0]);
            }
		});
	}

	function atualizaRegistro(servico,valor,familia){
		$.ajax({
			url: "<?=$PHP_SELF?>",
            type:'GET',
            cache: false,
            data:{
                ajax_atualiza:"sim",
                servico:servico,
                valor:valor,
                familia:familia
            }
        })
		.done(function(data) {
            retorno = data.split('|');

            if (retorno[0]=="OK") {
                $("#resultado").html(retorno[1]);

                $('#msg_success').text("Valor Adicional Excluido com Sucesso!");
                $('#msg_success').show();

                setTimeout(function(){
                    $('#msg_success').hide();
                }, 10000);

            } else {
                alert(retorno[0]);
            }
		});
	}

	function carregaDados(servico,valor,edita){
		$("input[name=servico]").val(servico);
		$("input[name=servico]").attr("readonly","readonly");

		$("input[name=servico_id]").val(servico);
		$("input[name=valor]").val(valor);

		if(edita == "Sim"){
			$("#radio_sim").attr("checked","checked");
		}else{
			$("#radio_nao").attr("checked","checked");
		}
	}

</script>

<div style="background-color: green; color: #fff; font-size: 18px; padding-top: 5px; padding-bottom: 5px; display: none; width: 850px; margin: 0 auto; margin-bottom: 5px; text-align:center;font-weight:bold;" id="msg_success">Valor Adicional Cadastrado com Sucesso!</div>

<form class='form-search form-inline tc_formulario' name='frm_cadastro' method='post'>

		<div class='titulo_tabela'><?=traduz('Cadastro Valores Adicionais por Familia')?></div>
			<br />
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span4">
					<div class="control-group">
						<label class="control-label" for=''><?=traduz('Familia')?></label>
						<div class='controls controls-row'>
							<select name='familia' class='frm'>
								<option value=''><?=traduz('Selecione uma FamÌlia')?></option>
								<?php
									$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY descricao";
									$res = pg_query($con,$sql);
									if(pg_num_rows($res) > 0){
										for($i = 0; $i < pg_num_rows($res); $i++){
											$familia = pg_fetch_result($res, $i, 'familia');
											$descricao = pg_fetch_result($res, $i, 'descricao');

											echo "<option value='$familia'>$descricao</option>";
										}

									}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class="span4">
					<div class="control-group">
							<label class="control-label" for=''><?=traduz('ServiÁo')?></label>
						<div class='controls controls-row'>	
							<input type='text' name='servico' class='frm' size='25' onkeyup="javascript:somenteMaiusculaSemAcento(this);">
						</div>
					</div>
				</div>
				<div class="span2">
					<div class="control-group">
						<label class="control-label" for=''><?=traduz('Valor')?></label>
						<div class='controls controls-row'>
							<input class='span8 valor' type='text' name='valor' class='frm' size='8'>
						</div>
					</div>	
				</div>
				<div class="span1"></div>
			</div>					
				<input type='hidden' name='familia_id' value=''>
				<input class="btn" type='button' value='Gravar' onclick='javascript: gravaDados();'>
				<br /><br />			
</form>
<div id='resultado'><?php echo mostraDados();?></div>
<?php
include "rodape.php";
?>
