<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "cadastros";

include "autentica_admin.php";
include "funcoes.php";

$msg_success = false;

if ($_POST["ajax_altera_tabela"]) {
		if (!empty($_POST["familia"])) {
			$familia = $_POST["familia"];
			$cond_familia = " AND tbl_diagnostico.familia = $familia";
		}

		if (!empty($_POST["solucao"])) {
			$solucao = $_POST["solucao"];
			$cond_solucao = " AND tbl_solucao.solucao = $solucao";
		}

		if(!empty($_POST["produto_array"]))
		{
			$produto = $_POST["produto_array"];
			$produto = implode(",", $produto);
			$cond_produto = " AND tbl_diagnostico_produto.produto IN ($produto)";
		}
		
		if (!empty($_POST["produto"]) && empty($_POST["produto_array"])) {
			$produto = $_POST["produto"];
			$cond_produto = " AND tbl_diagnostico_produto.produto = $produto";
		}

	$sql = "SELECT
			tbl_diagnostico.diagnostico,
			tbl_solucao.descricao AS solucao,
			tbl_diagnostico.mao_de_obra,
			tbl_diagnostico.ativo,
			tbl_diagnostico.valor_hora,
			tbl_diagnostico.tempo_estimado,
			tbl_solucao.solucao
			, tbl_familia.descricao AS familia, tbl_produto.produto, tbl_produto.referencia AS referencia_produto, tbl_produto.descricao AS nome_produto
			FROM tbl_diagnostico
			INNER JOIN tbl_solucao ON tbl_solucao.fabrica = $login_fabrica AND tbl_solucao.solucao = tbl_diagnostico.solucao
			INNER JOIN tbl_familia ON tbl_familia.fabrica = $login_fabrica AND tbl_familia.familia = tbl_diagnostico.familia 
			LEFT JOIN tbl_diagnostico_produto ON tbl_diagnostico_produto.diagnostico = tbl_diagnostico.diagnostico AND tbl_diagnostico_produto.fabrica = $login_fabrica 
			LEFT JOIN tbl_produto ON tbl_produto.produto=tbl_diagnostico_produto.produto
			WHERE tbl_diagnostico.fabrica = $login_fabrica
			$cond_familia
			$cond_solucao
			$cond_produto
			ORDER BY tbl_solucao.descricao ASC";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		for ($i = 0; $i < pg_num_rows($res); $i++) {	
			$diagnostico = pg_fetch_result($res, $i, "diagnostico");
			$familia     = pg_fetch_result($res, $i, "familia");
			$solucao     = pg_fetch_result($res, $i, "solucao");
			$mao_de_obra = number_format(pg_fetch_result($res, $i, "mao_de_obra"), 2, ",", ".");
			$ativo       = pg_fetch_result($res, $i, "ativo");
			$produto = pg_fetch_result($res, $i, "produto");
			$valor_hora     = number_format(pg_fetch_result($res, $i, "valor_hora"), 2, ",", ".");
			$tempo_estimado = pg_fetch_result($res, $i, "tempo_estimado");

			if (strlen($produto) > 0) {

				$referencia_produto = pg_fetch_result($res, $i, "referencia_produto");
				$nome_produto       = pg_fetch_result($res, $i, "nome_produto");
				$xproduto = "$referencia_produto - $nome_produto";

			}
			?>
			<tr>
				<th><?= $familia ?></th>
				<td><?= $xproduto ?></td>
				<td><a href="<?=$_SERVER['PHP_SELF']?>?diagnostico=<?=$diagnostico?>" ><?=$solucao?></a></td>
				<td class='tar'> R$ <?= $mao_de_obra ?></td>

				<td class='tar' >R$ <?= $valor_hora ?></td>
				<td class='tar' ><?= $tempo_estimado ?> min</td>
				<td class="tac" ><img name="ativo" src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 't') ? 'Relacionamento ativo' : 'Relacionamento inativo'?>" /></td>
				<td class="tac">
					<input type="hidden" name="diagnostico" value="<?=$diagnostico?>" />
	                        <?php
	                        if ($ativo == "f") {
	                        	echo "<button type='button' name='ativar' rel='t' class='btn btn-small btn-success' title='Ativar relacionamento' >Ativar</button>";
	                        } else {
	                            echo "<button type='button' name='ativar' rel='f' class='btn btn-small btn-danger' title='Inativar relacionamento' >Inativar</button>";
	                        }
	                       ?>
	                        <button type='button' name='apagar' class='btn btn-small btn-danger' title='Apagar relacionamento' data-produto="<?=$produto?>">Apagar</button>

				</td>
			</tr>
			<?	
		}
	} else {
	?>
		<tr>
			<td colspan="8" class="tac">	
				<h5>Nenhum resultado encontrado</h5>
			</td>	
		</tr>
	<?	
	}	
	exit;		

}	

if ($_POST["btn_acao"] == "submit") {
	$diagnostico = $_POST["diagnostico"];
	$solucao     = $_POST["solucao"];
	$produto     = $_POST["produto"];
	if (!in_array($login_fabrica, array(149))) {
		$mao_de_obra = trim($_POST["mao_de_obra"]);
		$mao_de_obra = str_replace(".", "", $mao_de_obra);
		$mao_de_obra = str_replace(",", ".", $mao_de_obra);
	}
	$ativo = (isset($_POST["ativo"][0])) ? "t" : "f";

	if (in_array($login_fabrica, array(138, 148))) {
		$familia     = $_POST["familia"];
	} else {
		$linha       = $_POST["linha"];
	}

	# Validações
	if (!strlen($familia) && in_array($login_fabrica, array(138, 148, 191))) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "familia";
	}

	if (!strlen($linha) && !in_array($login_fabrica, array(138, 148, 191))) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "linha";
	}

	if (!strlen($solucao)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "solucao";
	}
	if (!strlen($mao_de_obra) && !in_array($login_fabrica, array(149))) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "mao_de_obra";
	}

	if(in_array($login_fabrica, array(148))){
		$tempo = trim($_POST["tempo"]);
		$valor_hora = trim($_POST["valor_hora"]);
		$valor_hora = str_replace(".", "", $valor_hora);
		$valor_hora = str_replace(",", ".", $valor_hora);
		$produto_array = $produto;
		$produto = implode(",", $produto);

		if (!in_array($familia, [5669,5672])) { 
			if (!strlen($valor_hora)) {
				$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
				$msg_erro["campos"][]   = "valor_hora";
			}
		} else  {
			$valor_hora  = "null";
			$mao_de_obra = "null";
		}

		if (!strlen($tempo)) {
			$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
			$msg_erro["campos"][]   = "tempo";
		}
		if(count($msg_erro["msg"]) == 0){
			/* VERIFICA SE JÁ POSSUI O DIAGNOSTICO CADASTRADO */			
			if (!empty($produto) && count($produto_array) == 1) {
				$where_diagnostico = " AND tbl_diagnostico_produto.produto = {$produto}";
			}else if(!empty($produto) && count($produto_array) >= 2){
				$where_diagnostico = " AND tbl_diagnostico_produto.produto IN ({$produto})";
			}else{
				$where_diagnostico = " AND diagnostico_produto IS NULL";
			}
			$sql = "SELECT diagnostico,ativo,diagnostico_produto FROM tbl_diagnostico LEFT JOIN tbl_diagnostico_produto USING(diagnostico) WHERE tbl_diagnostico.fabrica = {$login_fabrica} AND solucao = {$solucao} AND familia = {$familia} AND ativo = 't' {$where_diagnostico}";

			$res = pg_query($con, $sql);
			$contador = pg_num_rows($res);
			if ($contador > 0) {
				$msg_erro["msg"]["obg"] = "Cadastro já efetuado!";
				$msg_erro["campos"][]   = "familia";
				$msg_erro["campos"][]   = "solucao";
			}
		}
	}else{
		$valor_hora = 'null';
		$tempo      = 'null';
	}
	# Fim Validações

	if(count($msg_erro["msg"]) == 0){
		pg_query($con, "BEGIN;");

		if (!strlen($diagnostico)) {
			if (in_array($login_fabrica, array(138, 148, 191))) {
				$column = ", familia";
				$value  = ", {$familia}";
			} else {
				$column = ", linha";
				$value  = ", {$linha}";
			}

			if (!in_array($login_fabrica, array(149))) {
				$column .= ", mao_de_obra";
				$value  .= ", {$mao_de_obra}";
			}

			$sql = "INSERT INTO tbl_diagnostico (
						fabrica,
						solucao,
						ativo,
						valor_hora,
						tempo_estimado
						{$column}
					) VALUES (
						{$login_fabrica},
						{$solucao},
						'{$ativo}',
						{$valor_hora},
						{$tempo}
						{$value}
					) RETURNING diagnostico;";

		} else {
			if (in_array($login_fabrica, array(138, 148, 191))) {
				$column = ", familia = {$familia}";
			} else {
				$column = ", linha = {$linha}";
			}

			if (!in_array($login_fabrica, array(149))) {
				$column .= ", mao_de_obra = {$mao_de_obra}";
			}

			$sql = "UPDATE tbl_diagnostico
					SET
						solucao           = {$solucao},
						ativo             = '{$ativo}',
						valor_hora        = {$valor_hora},
						tempo_estimado    = {$tempo}
						{$column}
					WHERE diagnostico = {$diagnostico}
						AND fabrica       = {$login_fabrica} RETURNING diagnostico;";
		}

		if($login_fabrica != 148){
			$res = pg_query($con, $sql);
			$diagnostico = pg_fetch_result($res, 0, 0);
		}
		else{
			$sql_diagnostico = $sql;
		}
		
		if (in_array($login_fabrica, array(148)) && strlen($produto) > 0) {
			
			$msg_erro = array();

			foreach ($produto_array as $qtd_produto) {
				$produto_id = $qtd_produto;
				$res = pg_query($con, $sql_diagnostico);
				$diagnostico = pg_fetch_result($res, 0, 0);

				$sql_busca = "SELECT diagnostico_produto FROM tbl_diagnostico_produto WHERE diagnostico = $diagnostico AND fabrica = $login_fabrica AND produto = $produto_id";

				$res = pg_query($con, $sql_busca);

				if(pg_num_rows($res) == 0){
					$sqlDP = "INSERT INTO tbl_diagnostico_produto (
						diagnostico,
						fabrica,
						produto
					) VALUES (
						{$diagnostico},
						{$login_fabrica},
						{$produto_id}
					);";
				} else {
					$diagnostico_produto = pg_fetch_result($res, 0, diagnostico_produto);
			        $sqlDP = "UPDATE tbl_diagnostico_produto
						SET
							diagnostico 	= {$diagnostico},
							fabrica 	= {$login_fabrica},
							produto 	= {$produto_id}
						WHERE 
							diagnostico_produto  = {$diagnostico_produto}
						AND fabrica     = {$login_fabrica}";
				}

				$resDP = pg_query($con, $sqlDP);
				$existe_erro = pg_last_error();

				if(strlen($existe_erro) > 0){
					$msg_erro[] = true;
				}
			}
		}

		if (count($msg_erro) == 0) {
			pg_query($con, "COMMIT;");
			$msg_success = true;
			unset($_POST);
			unset($diagnostico);
		} else {
			$msg_erro["msg"][] = "Erro ao gravar relacionamento. ".pg_last_error().$sql;
			pg_query($con, "ROLLBACK;");
		}
	}
}

if ($_POST["btn_acao"] == "ativar") {
	$diagnostico = $_POST["diagnostico"];
	$ativo       = $_POST["ativo"];

	$sql = "SELECT diagnostico FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		pg_query($con, "BEGIN");

		$sql = "UPDATE tbl_diagnostico SET ativo = '$ativo' WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			pg_query($con, "COMMIT");
			echo "success";
		} else {
			pg_query($con, "ROLLBACK");
			echo "error";
		}
	}

	exit;
}

if ($_POST["btn_acao"] == "apagar") {
	$diagnostico = $_POST["diagnostico"];
	$produto_id = $_POST['produto'];

	$sql = "SELECT diagnostico FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		$sql = "SELECT diagnostico_produto FROM tbl_diagnostico_produto WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			pg_query($con, "BEGIN");

			$sql = "DELETE FROM tbl_diagnostico_produto where fabrica = {$login_fabrica} AND diagnostico = {$diagnostico} AND produto = {$produto_id}";
			$res = pg_query($con, $sql);
			$existe_erro = pg_last_error();

			if(!strlen($existe_erro) > 0){
				$sql = "DELETE FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
				$res = pg_query($con, $sql);
				$existe_erro = pg_last_error();

				if (!strlen($existe_erro) > 0) {
					pg_query($con, "COMMIT");
					echo "success";
				} else {
					pg_query($con, "ROLLBACK");
					echo "error";
				}
			}else {
					pg_query($con, "ROLLBACK");
					echo "error";
				}
		}else{
			pg_query($con, "BEGIN");

			$sql = "DELETE FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND diagnostico = {$diagnostico}";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				pg_query($con, "COMMIT");
				echo "success";
			} else {
				pg_query($con, "ROLLBACK");
				echo "error";
			}
		}
	}

	exit;
}

if (!empty($_GET["diagnostico"])) {
	$_RESULT["diagnostico"] = $_GET["diagnostico"];

	$leftJoin  = "";
	$camposExt = "";
	if ($login_fabrica == 148) {
		$camposExt = "tbl_diagnostico_produto.produto,";
		$leftJoin = " 
					LEFT JOIN tbl_diagnostico_produto ON tbl_diagnostico_produto.diagnostico = tbl_diagnostico.diagnostico AND tbl_diagnostico_produto.fabrica = {$login_fabrica}
					LEFT JOIN tbl_produto ON tbl_produto.produto=tbl_diagnostico_produto.produto";
	}

	$sql = "SELECT
				tbl_diagnostico.familia,
				tbl_diagnostico.linha,
				tbl_diagnostico.solucao,
				tbl_diagnostico.mao_de_obra,
				tbl_diagnostico.ativo,
				tbl_diagnostico.tempo_estimado,
				{$camposExt}
				tbl_diagnostico.valor_hora
			FROM tbl_diagnostico
			{$leftJoin}
			WHERE tbl_diagnostico.diagnostico = {$_RESULT['diagnostico']}
			AND tbl_diagnostico.fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT["solucao"]     = pg_fetch_result($res, 0, "solucao");
		$_RESULT["mao_de_obra"] = number_format(pg_fetch_result($res, 0, "mao_de_obra"), 2, ",", ".");
		$_RESULT["ativo"]       = pg_fetch_result($res, 0, "ativo");

		if ($login_fabrica == 138 || $login_fabrica == 148) {
			if ($login_fabrica == 148) {
				$_RESULT["produto"] = pg_fetch_result($res, 0, "produto");
			}

			$_RESULT["familia"]       = pg_fetch_result($res, 0, "familia");
		} else {
			$_RESULT["linha"]       = pg_fetch_result($res, 0, "linha");
		}
		$_RESULT["valor_hora"]       = number_format(pg_fetch_result($res, 0, "valor_hora"), 2, ",", ".");
		$_RESULT["tempo"]       = pg_fetch_result($res, 0, "tempo_estimado");
	} else {
		$msg_erro["msg"][] = "Relacionamento não encontrado";
	}
}

if ($_GET["ajax_busca_produto"] == true) {
	$familia = $_GET["id_familia"];

	$sql = "SELECT
				produto, descricao, referencia
			FROM tbl_produto
			WHERE familia = {$familia}
			AND fabrica_i = {$login_fabrica} ORDER BY referencia,descricao";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		for ($i=0; $i < pg_num_rows($res); $i++) { 
			$referencia= pg_fetch_result($res, $i, "referencia");
			$produto   = pg_fetch_result($res, $i, "produto");
			$descricao = pg_fetch_result($res, $i, "descricao");
			echo '<option value="'.$produto.'">'.$referencia.' - '.$descricao.'</option>';
		}
	} else {
		echo '<option value="">Nenhum produto encontrado</option>';
	}
	exit;
}


$layout_menu = "cadastro";

if (in_array($login_fabrica, array(138, 148))) {
	$title       = "RELACIONAMENTO FAMÍLIA X SOLUÇÃO X MÃO DE OBRA";
} else if(in_array($login_fabrica, array(149))) {
	$title       = "RELACIONAMENTO LINHA X SOLUÇÃO";
} else if(in_array($login_fabrica, array(191))){
	$title       = "RELACIONAMENTO FAMÍLIA X SERVIÇO REALIZADO X MÃO DE OBRA";
}else{
	$title       = "RELACIONAMENTO LINHA X SOLUÇÃO X MÃO DE OBRA";
}
$title_page  = "Cadastro de Relacionamento";

if ($_GET["diagnostico"] || strlen($diagnostico) > 0) {
	$title_page = "Alteração de Relacionamento";
}

include "cabecalho_new.php";

$plugins = array(
	"price_format",
	"alphanumeric",
	"multiselect",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">

$(function () {

	    <? if (in_array($login_fabrica,[148])) { ?>

	    if ($.inArray($("#familia").val(), ['5669','5672']) !== -1) {
			$("#mao_de_obra, #valor_hora").closest(".control-group").hide();
		} else {
			$("#mao_de_obra, #valor_hora").closest(".control-group").show();
		}

        $('#familia').change(function(){

            var familia = $(this).val();
            var solucao = $('#solucao').val();

			if ($.inArray(familia, ['5669','5672']) !== -1) {
				$("#mao_de_obra, #valor_hora").closest(".control-group").hide();
			} else {
				$("#mao_de_obra, #valor_hora").closest(".control-group").show();
			}

            $.ajax({
                url: "integridade_familia_solucao_mo.php",
                type: "post",
                async: false,
                data: {
                    familia: familia,
                    solucao: solucao,
                    ajax_altera_tabela: true
                }
            })
            .done(function(data) {
            	$("#corpo_tabela").html(data);
            });

        });
        
        $('#produto').change(function(){
        	var produto_array = [];
        	$('#produto > option:selected').each(function(){
        		produto_array.push($(this).val());
        	});
            var produto = $(this).val();
            var familia = $('#familia').val();
            var solucao = $('#solucao').val();

                $.ajax({
                    url: "integridade_familia_solucao_mo.php",
                    type: "post",
                    data: {
                        familia: familia,
                        produto: produto,
                        produto_array: produto_array,
                        solucao: solucao,
                        ajax_altera_tabela: true 
                    }
                })
                .done(function(data) {
                	$("#corpo_tabela").html(data);
                });

        });

        $('#solucao').change(function(){
            var produto = $('#produto').val();
            var familia = $('#familia').val();
            var solucao = $(this).val();

                $.ajax({
                    url: "integridade_familia_solucao_mo.php",
                    type: "post",
                    data: {
                        familia: familia,
                        produto: produto,
                        solucao_regra: true,
                        solucao: solucao,
                        ajax_altera_tabela: true
                    }
                })
                .done(function(data) {
                	$("#corpo_tabela").html(data);
                });

        });


    <?php
    }
    ?>

	$("#tempo").numeric();

	<?php if(in_array($login_fabrica, array(148))) { ?>

		$(function(){
			if ($('#familia').val() != "") {
				var id_familia = $('#familia').val();
				$("#produto").load('<?php echo $_SERVER["PHP_SELF"];?>?ajax_busca_produto=true&id_familia='+id_familia);
			};
		});

		$(document).on("change", "select[name=familia]", function () {
			$("#produto").prop("multiple", "multiple");
    	$("#produto").multiselect();
			var id_familia = $(this).val();
			$("#produto").load('<?php echo $_SERVER["PHP_SELF"];?>?ajax_busca_produto=true&id_familia='+id_familia);

            setTimeout(function(){
            	$("#produto").multiselect('refresh');
            }, 500);
		});

		$("#mao_de_obra").attr({readonly:"readonly"});

		$("#valor_hora , #tempo").blur( function() {

			var valor_hora = $("#valor_hora").val();
			var tempo = $("#tempo").val();
			valor_hora = valor_hora.replace(",", ".");


			if (isNaN(tempo)){
				tempo = 0;
			}
			if(isNaN(valor_hora)) {
				valor_hora = 0;
			}

			var result ;
			result = ( valor_hora / 60 ) * tempo ;
			result = result.toFixed(2);
			result = result.replace(".", ",");
			$("#mao_de_obra").val(result);
		});

	<?php } ?>

		$(document).on("click", "button[name=ativar]", function () {
		if (ajaxAction()) {
			var diagnostico = $(this).parent().find("input[name=diagnostico]").val();
			var that        = $(this);
			var ativo       = $(this).attr("rel");

			$.ajax({
				async: false,
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				dataType: "JSON",
				data: { btn_acao: "ativar", diagnostico: diagnostico, ativo: ativo },
				beforeSend: function () {
					loading("show");
				},
				complete: function (data) {
					data = data.responseText;

					if (data == "success") {
						if (ativo == "t") {
							$(that).removeClass("btn-success").addClass("btn-danger").attr({ rel: "f" });
							$(that).attr({ title: "Inativar relacionamento" });
							$(that).text("Inativar");
							$(that).parents("tr").find("img[name=ativo]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
						} else {
							$(that).removeClass("btn-danger").addClass("btn-success").attr({ rel: "t" });
							$(that).attr({ title: "Ativar relacionamento" });
							$(that).text("Ativar");
							$(that).parents("tr").find("img[name=ativo]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
						}
					}

					loading("hide");
				}
			});
		}
	});

	$(document).on("click", "button[name=apagar]", function () {
		if (ajaxAction()) {
			var diagnostico = $(this).parent().find("input[name=diagnostico]").val();
			var tr          = $(this).parents("tr");
			var produto_id = $(this).data("produto");

			$.ajax({
				async: false,
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				dataType: "JSON",
				data: { btn_acao: "apagar", diagnostico: diagnostico, produto : produto_id},
				beforeSend: function () {
					loading("show");
				},
				complete: function (data) {
					data = data.responseText;


					if (data == "error") {
						alert("Erro ao apagar relacionamento");
					} else {
						$(tr).html("<td colspan='5' ><div class='alert alert-error' style='margin-bottom: 0px !important;'>Relacionamento Apagado</div></td>");

						setTimeout(function () {
							$(tr).remove();
						}, 3000);
					}

					loading("hide");
				}
			});
		}
	});
});
</script>

<?php
if($login_fabrica == 148){
?>
        <div class="alert alert-warning"><center><b>Cadastro válido apenas para Ordens de Serviço com o Tipo de Atendimento Garantia</b></center></div>
<?php
	    }

if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4>Relacionamento, gravado com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<?php

$hiddens = array(
	"diagnostico"
);

$inputs = array();


$sql_solucao = "SELECT solucao AS value, upper(descricao) AS label FROM tbl_solucao WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao";

if(in_array($login_fabrica, array(148))){
	$sql_familia = "SELECT familia AS value, descricao AS label FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao";
	if ($diagnostico && $_RESULT["familia"]) {
		$familia = $_RESULT["familia"];
		$sql_produto = "SELECT produto AS value, descricao AS label FROM tbl_produto WHERE familia = {$familia} AND fabrica_i = {$login_fabrica} ORDER BY descricao";
	} else {
		$sql_produto = "";
	}

	$inputs =  array(
	"familia" => array(
		"span"      => 4,
		"label"     => "Família",
		"type"      => "select",
		"required"  => true,
		"options"   => array(
			"sql_query" => $sql_familia
		)
	),
	"produto[]" => array(
		"id" => "produto",
		"span"      => 4,
		"label"     => "Produto",
		"type"      => "select",
		"options"   => array(
			"teste"	=> teste,
			"sql_query" => $sql_produto
		)
	),
	"solucao" => array(
		"span"      => 4,
		"label"     => ($login_fabrica == 191) ? "Serviço Realizado" : "Solução",
		"type"      => "select",
		"required"  => true,
		"options"   => array(
			"sql_query" => $sql_solucao
		)
	),
	"mao_de_obra" => array(
		"span"     => 2,
		"label"    => "Mão de obra",
		"type"     => "input/text",
		"width"    => 8,
		"required" => true,
		"extra"    => array(
			"price" => "true"
		)
	),
	"valor_hora" => array(
		"span"     => 2,
		"label"    => "Valor da hora",
		"type"     => "input/text",
		"width"    => 8,
		"required" => true,
		"extra"    => array(
			"price" => "true"
		)
	),
	"tempo" => array(
		"span"     => 2,
		"label"    => "Tempo (min)",
		"type"     => "input/text",
		"width"    => 8,
		"required" => true,
	),
	"ativo" => array(
		"span"     => 1,
		"label"    => "Ativo",
		"type"     => "checkbox",
		"checks"    => array(
			"t" => ""
		)
	),
);
} else {

if (in_array($login_fabrica, array(138,191))) {
        $sql_familia = "SELECT familia AS value, descricao AS label FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE";
        $inputs["familia"] = array(
                "span"      => 4,
                "label"     => "Família",
                "type"      => "select",
                "required"  => true,
                "options"   => array(
                        "sql_query" => $sql_familia
                )
        );
} else {
        $sql_linha = "SELECT linha AS value, nome AS label FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo IS TRUE";
        $inputs["linha"] = array(
                "span"      => 4,
                "label"     => "Linha",
                "type"      => "select",
                "required"  => true,
                "options"   => array(
                        "sql_query" => $sql_linha
                )
        );
}


$inputs["solucao"] = array(
        "span"      => 4,
        "label"     => ($login_fabrica == 191) ? "Serviço Realizado" : "Solução",
        "type"      => "select",
        "required"  => true,
        "options"   => array(
                "sql_query" => $sql_solucao
        )
);
if(!in_array($login_fabrica, array(149))){

	$inputs["mao_de_obra"] = array(
	        "span"     => 2,
	        "label"    => "Mão de obra",
	        "type"     => "input/text",
	        "width"    => 8,
	        "required" => true,
	        "extra"    => array(
	                "price" => "true"
	        )
	);

}

$inputs["ativo"] = array(
        "span"     => 1,
        "label"    => "Ativo",
        "type"     => "checkbox",
        "checks"    => array(
                "t" => ""
        )
);

}
?>

<form name="frm_relacionamento" method="POST" class="form-search form-inline tc_formulario" action="integridade_familia_solucao_mo.php" >
	<div class='titulo_tabela '><?=$title_page?></div>
	<br/>

	<?php
		echo montaForm($inputs, $hiddens);
	?>

	<p><br/>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<button class='btn' type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<?php
		if (strlen($_GET["diagnostico"]) > 0 || strlen($diagnostico) > 0) {
		?>
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
		<?php
		}
		?>
	</p><br/>
</form>

<table id="relacionamentos_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class="titulo_coluna" >
			<?php
			if (!in_array($login_fabrica, array(148, 149, 191))) {
			?>
				<th>Família</th>
			<?php
			}
			?>
			<th><?= (in_array($login_fabrica, array(138, 148, 191))) ? "Família" : "Linha"?></th>
			<?php echo (in_array($login_fabrica, array(148))) ?  "<th>Produto</th>": "" ; ?>
			<th><?=($login_fabrica == 191) ? "Serviço Realizado" : "Solução"?></th>
			<?= (!in_array($login_fabrica, array(149))) ?  "<th>Mão de obra</th>": "" ; ?>

			<?= (in_array($login_fabrica, array(148))) ?  "<th>Valor da Hora</th> <th>Tempo</th> ": "" ; ?>
			<th>Ativo</th>
			<th>Ações</th>
		</tr>
	</thead>
	<tbody id="corpo_tabela">
	<?php
	if (in_array($login_fabrica, array(138, 148, 191))) {
	    $column = ", tbl_familia.descricao AS familia";
        $join = "INNER JOIN tbl_familia ON tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.familia = tbl_diagnostico.familia";
		if (in_array($login_fabrica, array(148))) {
	    	$column .= ", tbl_produto.produto, tbl_produto.referencia AS referencia_produto, tbl_produto.descricao AS nome_produto";
        	$join   .= " LEFT JOIN tbl_diagnostico_produto ON tbl_diagnostico_produto.diagnostico = tbl_diagnostico.diagnostico AND tbl_diagnostico_produto.fabrica = {$login_fabrica}";
        	$join   .= " LEFT JOIN tbl_produto ON tbl_produto.produto=tbl_diagnostico_produto.produto";
		}
	} else {
	    $column = ", tbl_linha.nome AS linha";
        $join = "INNER JOIN tbl_linha ON tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.linha = tbl_diagnostico.linha";
	}


	$sql = "SELECT
				tbl_diagnostico.diagnostico,
				tbl_solucao.descricao AS solucao,
				tbl_diagnostico.mao_de_obra,
				tbl_diagnostico.ativo,
				tbl_diagnostico.valor_hora,
				tbl_diagnostico.tempo_estimado
				{$column}
			FROM tbl_diagnostico
			INNER JOIN tbl_solucao ON tbl_solucao.fabrica = {$login_fabrica} AND tbl_solucao.solucao = tbl_diagnostico.solucao
			{$join}
			WHERE tbl_diagnostico.fabrica = {$login_fabrica}
			ORDER BY tbl_solucao.descricao ASC";
//echo $sql;
	$res = pg_query($con, $sql);

	for ($i = 0; $i < pg_num_rows($res); $i++) {
		$diagnostico = pg_fetch_result($res, $i, "diagnostico");
		$familia     = pg_fetch_result($res, $i, "familia");
		$solucao     = pg_fetch_result($res, $i, "solucao");
		$mao_de_obra = number_format(pg_fetch_result($res, $i, "mao_de_obra"), 2, ",", ".");
		$ativo       = pg_fetch_result($res, $i, "ativo");
		$xproduto    = "";
		if (in_array($login_fabrica, array(148))) {
			$produto = pg_fetch_result($res, $i, "produto");
			if (strlen($produto) > 0) {
				$referencia_produto = pg_fetch_result($res, $i, "referencia_produto");
				$nome_produto       = pg_fetch_result($res, $i, "nome_produto");
				$xproduto = "$referencia_produto - $nome_produto";
			}
	
			$valor_hora     = number_format(pg_fetch_result($res, $i, "valor_hora"), 2, ",", ".");
			$tempo_estimado = pg_fetch_result($res, $i, "tempo_estimado")  ;
		}

		if (in_array($login_fabrica, array(138, 148, 191))) {
                        $familia     = pg_fetch_result($res, $i, "familia");
		} else {
			$linha = pg_fetch_result($res, $i, "linha");
		}

		?>

		<tr>
			<th><?=(in_array($login_fabrica, array(138, 148, 191))) ? $familia: $linha?></th>
			<?php echo (in_array($login_fabrica, array(148))) ?  "<td>".$xproduto."</td>": "" ; ?>
			<td><a href="<?=$_SERVER['PHP_SELF']?>?diagnostico=<?=$diagnostico?>" ><?=$solucao?></a></td>
			<?= (!in_array($login_fabrica, array(149))) ?  "<td class='tar' >R$ $mao_de_obra </td>": "" ; ?>

			<?= (in_array($login_fabrica, array(148))) ?  "<td class='tar' >R$ $valor_hora</td>": "" ; ?>
			<?= (in_array($login_fabrica, array(148))) ?  "<td class='tar' >$tempo_estimado min</td>": ""; ?>

			<td class="tac" ><img name="ativo" src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 't') ? 'Relacionamento ativo' : 'Relacionamento inativo'?>" /></td>
			<td class="tac">
				<input type="hidden" name="diagnostico" value="<?=$diagnostico?>" />
                                                <?php
                                                if ($ativo == "f") {
                                                        echo "<button type='button' name='ativar' rel='t' class='btn btn-small btn-success' title='Ativar relacionamento' >Ativar</button>";
                                                } else {
                                                        echo "<button type='button' name='ativar' rel='f' class='btn btn-small btn-danger' title='Inativar relacionamento' >Inativar</button>";
                                                }
                                                ?>
                                                <button type='button' name='apagar' class='btn btn-small btn-danger' title='Apagar relacionamento' data-produto="<?=$produto?>">Apagar</button>

			</td>
		</tr>	
	<?php
	}
	?>
	</tbody>
</table>
<script>
	$.dataTableLoad({
        table: "#relacionamentos_cadastrados"
    });
</script>
<?php
include "rodape.php";
?>
