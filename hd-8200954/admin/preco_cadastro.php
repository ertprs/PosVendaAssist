<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';

if($_GET['ajax']){
	$tabela_item = $_GET['tabela_item'];
	$peca = $_GET['peca'];

	$sqlLog = "SELECT *
			FROM tbl_tabela_item
			WHERE peca = $peca";
	$auditorLog = new AuditorLog;
	$auditorLog->retornaDadosSelect($sqlLog);


	$sql = "DELETE FROM tbl_tabela_item WHERE tabela_item = $tabela_item";
	$res = pg_query($con,$sql);

	if(!pg_last_error($con)){
		$auditorLog->retornaDadosSelect()->enviarLog('delete', 'tbl_tabela_item', $login_fabrica."*".$peca);
		echo "ok";
	}else{
		echo "erro";
	}

	exit;
}

if($_POST["acao"] == "pesquisar_todos" && $telecontrol_distrib == "t"){

	$acao = "pesquisar_todos";

	if($_POST["tipo"] == "ambos"){
		$tipo = "";
	}else{
		$tipo = " AND tbl_peca.produto_acabado ";
		$tipo .= ($_POST["tipo"] == "pecas") ? " IS FALSE " : " IS TRUE ";
	}

	$sql_pecas = "SELECT
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					tbl_peca.referencia_fabrica
				FROM tbl_peca
				WHERE
					tbl_peca.fabrica = {$login_fabrica}
					{$tipo}
					AND tbl_peca.ativo IS TRUE
				ORDER BY tbl_peca.referencia";

	$res_pecas = pg_query($con, $sql_pecas);

	if(isset($_POST["gerar_excel"])){

		if(pg_num_rows($res_pecas) > 0){

			$file     = "xls/relatorio-preco-cadastro-{$login_fabrica}.xls";
	        $fileTemp = "/tmp/relatorio-preco-cadastro-{$login_fabrica}.xls" ;
	        $fp       = fopen($fileTemp,'w');

	        $sql_tabelas = "SELECT tabela, descricao FROM tbl_tabela WHERE fabrica = {$login_fabrica} AND ativa IS TRUE";
			$res_tabelas = pg_query($con, $sql_tabelas);

			$info_tabelas = array();
			$tabelas_precos = "";

			for($i = 0; $i < pg_num_rows($res_tabelas); $i++){

				$tabela       = pg_fetch_result($res_tabelas, $i, "tabela");
				$tabela_desc  = pg_fetch_result($res_tabelas, $i, "descricao");

				$info_tabelas[] = array("tabela" => $tabela, "descricao" => $tabela_desc);
				$tabelas_precos .= "<th><font color='#FFFFFF'>{$tabela_desc}</font></th>";

			}

		if($login_fabrica == 187){
			$ref_fabrica = "<th><font color='#FFFFFF'>".traduz("Referência MCASSAB")."</font></th>";
		}

	        $head = "
            <table border='1'>
                <thead>
                    <tr bgcolor='#596D9B'>
			<th><font color='#FFFFFF'>".traduz("Referência")."</font></th>
			{$ref_fabrica}
                        <th><font color='#FFFFFF'>".traduz("Descrição")."</font></th>
                        <th><font color='#FFFFFF'>".traduz("IPI")."</font></th>
                        {$tabelas_precos}
                    </tr>
                </thead>
                <tbody>";
        	fwrite($fp, $head);

	        for($i = 0; $i < pg_num_rows($res_pecas); $i++){

	        	$body = "<tr>";

        			$peca_id    = pg_fetch_result($res_pecas, $i, "peca");
					$referencia = pg_fetch_result($res_pecas, $i, "referencia");
					$descricao  = pg_fetch_result($res_pecas, $i, "descricao");
					$ipi        = pg_fetch_result($res_pecas, $i, "ipi");
					$referencia_fabrica = pg_fetch_result($res_pecas, $i, "referencia_fabrica");

					$body .= "<td>" . $referencia . "</td>";
					if($login_fabrica == 187){
						$body .= "<td>" . $referencia_fabrica . "</td>";
					}
					$body .= "<td>" . $descricao . "</td>";
					$body .= "<td>" . $ipi . " %</td>";

					foreach ($info_tabelas as $value) {

						$tabela_id = $value["tabela"];

						$sql_peca_tabela = "SELECT preco FROM tbl_tabela_item WHERE peca = {$peca_id} AND tabela = {$tabela_id}";
						$res_peca_tabela = pg_query($con, $sql_peca_tabela);

						if(pg_num_rows($res_peca_tabela) > 0){

							$preco = pg_fetch_result($res_peca_tabela, 0, "preco");
							$preco = number_format($preco, 2, ",", ".");

							$body .= "<td align='center'>" . $real . $preco . "</td>";

						}else{
							$body .= "<td align='center'>".traduz("Sem cadastro")."</td>";
						}

					}

				$body .= "</tr>";

                fwrite($fp, $body);

	        }

	        fwrite($fp, '</tbody></table>');
	        fclose($fp);

	        if(file_exists($fileTemp)){
	            system("mv $fileTemp $file");

	            if(file_exists($file)){
	                echo $file;
	            }
	        }

	        exit;

	    }

	}

}

if ($_POST["btn_acao"] == "submit") {
	$acao 			 = $_POST['acao'];
	$peca       	 = $_POST['peca'];
	$peca_referencia = $_POST['peca_referencia'];
	$peca_descricao  = $_POST['peca_descricao'];
	$preco 			 = $_POST['preco'];
    $tabela          = $_POST['tabela'];
	$reembolso 		 = $_POST['reembolso'];

	if($acao == "pesquisar"){
		if(empty($peca_referencia)){
			$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][] = "peca";
		}

		if (!empty($peca_descricao)){
			$cond_pc = "AND UPPER(fn_retira_especiais(tbl_peca.descricao)) ILIKE UPPER('%' || fn_retira_especiais('{$peca_descricao}') || '%') ";
		}

		if(count($msg_erro) == 0){
			if(empty($peca)){
				$sql = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$peca_referencia' $cond_pc ";
				$res = pg_query($con,$sql);
				if (!pg_num_rows($res)) {
					$msg_erro["msg"][]    = traduz("Peça não encontrada");
					$msg_erro["campos"][] = "peca";
				} else {
					$peca = pg_fetch_result($res, 0, "peca");
				}
			}

		}
	}else if($acao == "cadastrar"){


		if(empty($preco)){
			$msg_erro["msg"][]    = traduz("Informe o preço");
			$msg_erro["campos"][] = "preco";
		}

        if(empty($tabela)){
            $msg_erro["msg"][]    = traduz("Informe a tabela");
            $msg_erro["campos"][] = "tabela";
        }

		if(empty($reembolso)){
            if($login_fabrica == 134){
                $msg_erro["msg"][]    = traduz("Informe o percentual de reembolso");
                $msg_erro["campos"][] = "reembolso";
            }else{
                $reembolso = 0.00;
            }
		}

		if(count($msg_erro) == 0){
            $preco      = moneyDB($preco);
			$reembolso  = moneyDB($reembolso);

			$sql = "SELECT preco
					FROM tbl_tabela_item
					WHERE peca = $peca
					AND tabela = $tabela";
			$res = pg_query($con,$sql);

			$sqlLog = "SELECT *
					FROM tbl_tabela_item
					WHERE peca = $peca";
			$auditorLog = new AuditorLog;
			$auditorLog->retornaDadosSelect($sqlLog);
			
			if (pg_num_rows($res) == 0) {
				###INSERE NOVO REGISTRO
				$sql = "INSERT INTO tbl_tabela_item (
							tabela,
							peca  ,
							preco
						) VALUES (
							$tabela,
							$peca      ,
							$preco
						)";
				$res = pg_query($con,$sql);
				if (pg_last_error($con)) {
					$msg_erro["msg"][]    = traduz("Erro ao cadastrar o preço");
					$msg_erro["campos"][] = "preco";
				}else{
					$msg = "cadastrado";
					$auditorLog->retornaDadosSelect($sqlLog)->enviarLog('insert', 'tbl_tabela_item', $login_fabrica."*".$peca);
				}
			}else{

				$preco_ant = pg_fetch_result($res,0,'preco');

				###ALTERA REGISTRO
				$sql = "UPDATE  tbl_tabela_item SET
								preco  = $preco
						WHERE   tbl_tabela_item.tabela      = $tabela
						AND     tbl_tabela_item.peca = $peca";
				$res = pg_query($con,$sql);
				if (pg_last_error($con)) {
					$msg_erro["msg"][]    = traduz("Erro ao atualizar o preço");
					$msg_erro["campos"][] = "preco";
				}else{
					$msg = "atualizado";
					$nome_servidor = $_SERVER['SERVER_NAME'];
					$nome_uri = $_SERVER['REQUEST_URI'];
					$nome_url = $nome_servidor.$nome_uri;

					$auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_tabela_item', $login_fabrica."*".$peca);

				}
			}

			if($login_fabrica == 134){
                /**
                * hd-1853743 - Será feito o UPDATE da peça
                * com o valor do REEMBOLSO que a fábrica
                * cobrará do posto
                *
                * @author William Ap. Brandino
                *
                */
                $sql = "UPDATE  tbl_peca
                        SET     percentual_reembolso = $reembolso
                        WHERE   peca = $peca
                ";
                $res = pg_query($con,$sql);

                if (pg_last_error($con)) {
                    $msg_erro["msg"][]    = traduz("Erro ao atualizar o reembolso");
                    $msg_erro["campos"][] = "reembolso";
                }
			}

			if(count($msg_erro) == 0){
				header("Location: preco_cadastro.php?peca=$peca&msg=$msg");
			}
		}

	}
}

$peca = ($_GET['peca']) ? $_GET['peca'] : $peca;
if($peca){
	if ($login_fabrica == 86){
		$sel_ipi = ", tbl_peca.ipi ";
	}

	$sql = "SELECT  tbl_tabela_item.tabela_item         ,
					tbl_tabela.tabela                   ,
					tbl_tabela.sigla_tabela             ,
					tbl_tabela.descricao AS tabela_desc ,
					tbl_tabela.ativa                    ,
					tbl_tabela_item.preco               ,
					tbl_peca.referencia                 ,
					tbl_peca.descricao                  ,
					tbl_peca.percentual_reembolso
					$sel_ipi
			FROM    tbl_tabela
			JOIN    tbl_tabela_item USING (tabela)
			JOIN    tbl_peca        ON tbl_peca.peca = tbl_tabela_item.peca
			WHERE   tbl_tabela_item.peca = $peca
			AND     tbl_tabela.fabrica   = $login_fabrica
			ORDER BY tbl_tabela.ativa, tbl_tabela.sigla_tabela DESC";
	$resPreco = pg_query ($con,$sql);
	if(pg_num_rows($resPreco) > 0){
		$peca_referencia        = pg_fetch_result($resPreco, 0, 'referencia');
		$referencia_anterior    = pg_fetch_result($resPreco, 0, 'referencia');
		$peca_descricao         = pg_fetch_result($resPreco, 0, 'descricao');
		$reembolso              = pg_fetch_result($resPreco, 0, 'percentual_reembolso');
	}else{
		$sql = "SELECT 	tbl_peca.referencia             ,
                        tbl_peca.descricao              ,
                        tbl_peca.percentual_reembolso
                FROM    tbl_peca
                WHERE   tbl_peca.peca = $peca";

        $resPeca = pg_query ($con,$sql);

		$peca_referencia = pg_fetch_result($resPeca, 0, 'referencia');
        $peca_descricao  = pg_fetch_result($resPeca, 0, 'descricao');
		$reembolso       = pg_fetch_result($resPeca, 0, 'percentual_reembolso');


	}
}

$layout_menu = "cadastro";
$title = traduz("CADASTRO DE PREÇOS DE MERCADORIAS");
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"shadowbox",
	"price_format",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();
    var login_fabrica = <?=$login_fabrica?>;
	$(function() {
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this), ["pesquisa_produto_acabado", "sem-de-para"]);
		});

		$(".remove").click(function(){
			if (confirm('<?=traduz("Deseja realmente deletar este registro?")?>') == false) {
				return;
			}

			var tabela_item = $(this).attr("rel");
			var linha = $(this);
			$.ajax({
				url: "preco_cadastro.php?ajax=sim&tabela_item="+tabela_item+"&peca="+$('#peca').val(),
				complete: function(data){
					if(data.responseText == "ok"){
						$(linha).parents("tr").remove();

						if( $(".alert").is(":visible") ){
							$(".alert").hide();
						}
						$("#exclui").show();
					}else{
						alert('<?=traduz("Erro ao excluir registro")?>');
					}
				}
			});
		});

        if(login_fabrica == 134){
            $("#reembolso").css("text-align","right");
            $("#reembolso").priceFormat({
                prefix: '',
                centsSeparator: ',',
                thousandsSeparator: '.'
            });
        }
	});

	function retorna_peca(retorno){
		$("#peca").val(retorno.peca);
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

    function set_peca_input(peca, referencia){
    	$("#peca_referencia").val(peca);
    	$("#peca_descricao").val(referenciac);

    	 $('html, body').animate({
	     	scrollTop: $("#form_pesquisa").offset().top
	     }, 500);

    }

</script>

<style>
	.desc_peca{
		text-transform: uppercase;
	}
</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<?php
if (!empty($msg)) {
?>
    <div class="alert alert-success">
		<h4><?php
				if($msg == "cadastrado"){
					echo traduz("Preço cadastrado com sucesso");
				}else{
					echo traduz("Preço atualizado com sucesso");
				}
		?></h4>
    </div>
	<?php
	if ($login_fabrica == 120 or $login_fabrica == 201) {
		$sql_n = "SELECT tabela, descricao
					FROM tbl_tabela
					WHERE fabrica = $login_fabrica
					AND (tabela_garantia IS TRUE OR tabela_garantia IS NULL)";
		$res_s = pg_query($con, $sql_n);
		$legenda = true;

		if (pg_num_rows($res_s)> 0) {
			for ($i=0; $i < pg_num_rows($res_s) ; $i++) {
				$tabela_s 	 = pg_fetch_result($res_s, $i, tabela);
				$tabela_desc = pg_fetch_result($res_s, $i, descricao);

				$sql_n_t = "SELECT DISTINCT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
								FROM tbl_peca
								LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
								AND tbl_tabela_item.tabela in($tabela_s) WHERE tbl_peca.fabrica = $login_fabrica
								AND tbl_peca.produto_acabado IS NOT TRUE
								AND tbl_peca.ativo IS TRUE
								AND tbl_tabela_item.peca = $peca;";
								//echo $sql_n_t;
				$res_n_t = pg_query($con,$sql_n_t);
				if (!pg_num_rows($res_n_t)> 0) {
					if ($legenda == true) {
						$tabela_desc_sem = traduz("Peça sem preço na(s) tabela(s): ").$tabela_desc;
						$legenda = false;
					}else{
						$tabela_desc_sem .= ", ".$tabela_desc;
					}
				}
			}
		}
	}
	if (strlen($tabela_desc_sem)) {
	?>
	    <div class="alert alert-error">
			<h4><?=$tabela_desc_sem?></h4>
	    </div>
	<?php
	}
}
?>

<div class="alert alert-success" id="exclui" style="display:none;">
	<h4><?=traduz('Preço excluído com sucesso')?></h4>
</div>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form name='frm_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' id="form_pesquisa">
	<input type="hidden" name="acao"  value="pesquisar">
	<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
	<br/>
	<div class="row-fluid">

		<div class="span2"></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'><?=traduz('Ref. Peças')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_descricao'><?=traduz('Descrição Peça')?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2"></div>

	</div>

	<p>
		<br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p>

	<br />
</form>

<?php
if($telecontrol_distrib == "t"){
?>

<form method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

	<input type="hidden" name="acao" value="pesquisar_todos">
	<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa de peças / produtos(acabados)')?></div>

	<br />

	<p>
		<input type="radio" name="tipo" value="pecas" <?php echo ($_POST["tipo"] == "pecas") ? "checked" : ""; ?> /> <?=traduz('Peças')?> &nbsp;
		<input type="radio" name="tipo" value="produtos" <?php echo ($_POST["tipo"] == "produtos") ? "checked" : ""; ?> /><?=traduz('Produtos (acabados)')?> &nbsp;
		<input type="radio" name="tipo" value="ambos" <?php echo ($_POST["tipo"] == "ambos") ? "checked" : ((!isset($_POST["tipo"])) ? "checked" : ""); ?> /> <?=traduz('Ambos')?>
		&nbsp; &nbsp; &nbsp;
		<button type="submit" class="btn"><?=traduz('Pesquisar Todos')?></button>
	</p>

	<br />

</form>

<?php
}
?>

<?php

if($telecontrol_distrib == "t"){

	if(isset($_POST["acao"]) && $_POST["acao"] == "pesquisar_todos"){

		if(pg_num_rows($res_pecas) > 0){

			$sql_tabelas = "SELECT tabela, descricao FROM tbl_tabela WHERE fabrica = {$login_fabrica} AND ativa IS TRUE";
			$res_tabelas = pg_query($con, $sql_tabelas);

			$info_tabelas = array();

			if(pg_num_rows($res_tabelas) > 0){
				for($i = 0; $i < pg_num_rows($res_tabelas); $i++){

					$tabela       = pg_fetch_result($res_tabelas, $i, "tabela");
					$tabela_desc  = pg_fetch_result($res_tabelas, $i, "descricao");

					$info_tabelas[] = array("tabela" => $tabela, "descricao" => $tabela_desc);

				}
			}

			?>

			<table class="table table-bordered" id="listagem" style="width: 100%;">
				<thead>
					<tr class="titulo_tabela">
						<th colspan="<?php echo count($info_tabelas) + 4; ?>"><?=traduz('Lista de Peças / Produtos (acabados)')?></th>
					</tr>
					<tr class="titulo_coluna">
						<th><?=traduz('Referência')?></th>
						<?php if($login_fabrica == 187){ ?>
							<th><?=traduz('Referência MCASSAB')?></th>
						<?php } ?>
						<th><?=traduz('Descrição')?></th>
						<th><?=traduz('IPI')?></th>
						<?php
						foreach ($info_tabelas as $value) {
							echo "<th>".$value["descricao"]."</th>";
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php

					for($i = 0; $i < pg_num_rows($res_pecas); $i++){

						$peca_id    = pg_fetch_result($res_pecas, $i, "peca");
						$referencia = pg_fetch_result($res_pecas, $i, "referencia");
						$descricao  = pg_fetch_result($res_pecas, $i, "descricao");
						$ipi        = pg_fetch_result($res_pecas, $i, "ipi");
						$referencia_fabrica = pg_fetch_result($res_pecas, $i, "referencia_fabrica");

						echo "<tr>";

						echo "<td><a href='javascript: set_peca_input({$referencia}, \"{$descricao}\");'>{$referencia}</a></td>";
						if($login_fabrica == 187){
							echo "<td><a href='javascript: set_peca_input({$referencia}, \"{$descricao}\");'>{$referencia_fabrica}</a></td>";
						}
							echo "<td class='desc_peca'>{$descricao}</td>";
							echo "<td class='tac'>{$ipi} %</td>";

							foreach ($info_tabelas as $value) {

								$tabela_id = $value["tabela"];

								$sql_peca_tabela = "SELECT preco FROM tbl_tabela_item WHERE peca = {$peca_id} AND tabela = {$tabela_id}";
								$res_peca_tabela = pg_query($con, $sql_peca_tabela);

								if(pg_num_rows($res_peca_tabela) > 0){

									$preco = pg_fetch_result($res_peca_tabela, 0, "preco");
									$preco = number_format($preco, 2, ",", ".");

									echo "<td class='tac'>{$real} . {$preco}</td>";

								}else{
									echo "<td class='tac'>".traduz("Sem cadastro")."</td>";
								}

							}

						echo "</tr>";

					}

					?>
				</tbody>
			</table>

			<br />

			<?php

            $arr_excel = array(
				"acao" => "pesquisar_todos",
				"tipo" => $_POST["tipo"]
            );

            ?>

            <div id='gerar_excel' class="btn_excel">
		        <input type="hidden" id="jsonPOST" value='<?=json_encode($arr_excel)?>' />
		        <span><img src='imagens/excel.png' /></span>
		        <span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
		    </div>

		    <br />

			<?php

			if(pg_num_rows($res_pecas) > 50){
				?>

				<script>
	                $.dataTableLoad({
	                    table : "#listagem"
	                });
	            </script>

				<?php
			}

		}else{
			?>
			<div class="alert alert-warning">
				<h4><?=traduz('Nenhum registro encontrado')?></h4>
			</div>
			<br />
			<?php
		}

	}

}

?>

<?php
if ( ($acao == "pesquisar" and count($msg_erro) == 0) OR !empty($peca) ) {

?>
		<form name='frm_peca_gravar' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
			<input type="hidden" name="peca" id="peca" value="<? echo $peca ?>">
			<input type="hidden" name="referencia_anterior" id="referencia_anterior" value="<? echo $referencia_anterior ?>">
			<input type="hidden" name="acao"  value="cadastrar">
			<div class='titulo_tabela '><?=traduz('Cadastro')?></div>
			<br/>
			<div class="row-fluid">

				<div class="span2"></div>

				<div class='span4'>
					<div class='control-group <?=(in_array("tabela", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='tabela'><?=traduz('Tabela de preços')?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<select name="tabela" id="tabela">
									<option value=""></option>
									<?php
									$sql = "SELECT  tbl_tabela.tabela       ,
													tbl_tabela.sigla_tabela ,
													tbl_tabela.descricao
											FROM        tbl_tabela
											WHERE       tbl_tabela.fabrica = $login_fabrica
											ORDER BY    tbl_tabela.sigla_tabela";
									$res = pg_query($con,$sql);

									foreach (pg_fetch_all($res) as $key) {
										$selected_tabela = ( isset($tabela) and ($tabela == $key['tabela']) ) ? "SELECTED" : '' ;

									?>
										<option value="<?php echo $key['tabela']?>" <?php echo $selected_tabela ?> >

											<?php echo $key['sigla_tabela']." - ".$key['descricao']?>

										</option>
									<?php
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class='span4'>
					<div class='control-group <?=(in_array("preco", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='preco'><?=traduz('Preço')?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>

								<?
								if (in_array($login_fabrica, array(81,122,123,125,114,128))){
								?>
									<input type="text" name="preco" id="preco" price="true" pricecents="4" size="12" maxlength="10" class='span12' value="<? echo priceFormat($preco);?>" >
								<?
								}else{
								?>
									<input type="text" name="preco" id="preco" price="true" size="12" maxlength="10" class='span12' value="<? echo priceFormat($preco);?>" >
								<?
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="span2"></div>

			</div>
<?
if($login_fabrica == 134){
?>
            <div class="row-fluid">

                <div class="span2"></div>
                <div class='span8'>
                    <div class='control-group'>
                        <label class='control-label' for='reembolso'><?=traduz('Percentual Reembolso')?> (%)</label>
                        <div class='controls controls-row'>
                            <div class="span4">
                                <h5 class='asteristico'>*</h5>
                                <input type="text" name="reembolso" id="reembolso" class="span12" size="12" maxlength="6" value="<?=priceFormat($reembolso)?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
<?
}
?>
			<p><br />
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Gravar')?></button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p><br/>
		</form>
<?php
		$total_preco = pg_num_rows($resPreco);

		if($total_preco > 0){
?>
		<table class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th><?=traduz('Tabela')?></th>
					<th><?=traduz('Status')?></th>
					<th><?=traduz('Preço')?></th>
<?php
			if ($login_fabrica == 86){
				echo "	<th>IPI</th>
						<th>Total</th>";
			}


?>
					<th><?=traduz('Ações')?></th>
				</tr>
			</thead>

			<tbody>
<?php

				for($y = 0; $y< $total_preco; $y++){

					$tabela_item     = pg_result($resPreco,$y,'tabela_item');
					$tabela          = pg_result($resPreco,$y,'tabela');
					$sigla           = pg_result($resPreco,$y,'sigla_tabela');
					$tabela_desc     = pg_result($resPreco,$y,'tabela_desc');
					$ativa           = pg_result($resPreco,$y,'ativa');
					$preco           = pg_result($resPreco,$y,'preco');
					$ipi           	 = pg_result($resPreco,$y,'ipi');
					$peca_referencia = pg_result($resPreco,$y,'referencia');
					$peca_descricao  = pg_result($resPreco,$y,'descricao');

					$status = ($ativa == 't') ? traduz("Tabela ativa") : traduz("Tabela Inativa");

					if (in_array($login_fabrica, array(81,122,123,125,114,128))){

						echo "	<tr>
								<td class='tac'>{$sigla} - {$tabela_desc}</td>
								<td class='tac'>{$status}</td>
								<td class='tac'> ". $real .number_format($preco,4,",",".")."</td>
								<td class='tac'><input type='button' value='".traduz("Apagar")."' rel='$tabela_item' class='btn btn-small remove'></td>
							</tr>";

					}else{
						if ($login_fabrica == 86 ){
							$total = $preco+($preco*($ipi/100)) ;

							$ipi_total = "	<td class='tac'>".$ipi." %</td>
											<td class='tac'> ". $real .number_format($total,2,",",".")."</td>";
						}

					echo "	<tr>
								<td class='tac'>{$sigla} - {$tabela_desc}</td>
								<td class='tac'>{$status}</td>
								<td class='tac'> " . $real .number_format($preco,2,",",".")."</td>
								$ipi_total
								<td class='tac'><input type='button' value='".traduz("Apagar")."' rel='$tabela_item' class='btn btn-small remove'></td>
							</tr>";
					}
				}
?>
			</tbody>
		</table>
<?php

	}
	?>
	<br />
	<center>
	<div class='tac'>
		<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_tabela_item&id=<?php echo $peca; ?>'><?=traduz('Visualizar Log Auditor')?></a>
	</div>
	</center>
	<br>
	<?php
}

echo "</div>";
include "rodape.php";
?>
