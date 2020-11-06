<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {


	if ($login_fabrica == 148) {
		$linha = $_POST["linha"];

		if (empty($linha)) {
			$msg_erro["msg"][] = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][] = "linha";
		} else {
			$join = "INNER JOIN tbl_linha ON tbl_linha.linha = tbl_faq.linha AND tbl_linha.fabrica = {$login_fabrica}";
			$where = "tbl_faq.linha = $linha";
			$coluna = ", tbl_linha.nome AS linha";
		}
	} else {
		$produto = $_POST["produto"];

		if (empty($produto)) {
			$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][] = "produto";
		} else {
			$join = "INNER JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto AND tbl_produto.fabrica_i = {$login_fabrica}";
                        $where = "tbl_faq.produto = $produto";
			$coluna = ", tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto";
		}
	}

	if(count($msg_erro["msg"]) == 0){

		$sql = "SELECT  tbl_faq.situacao,
			tbl_faq.faq $coluna
			FROM	tbl_faq
			$join
			WHERE tbl_faq.fabrica = {$login_fabrica}
			AND $where";
		$resSubmit = pg_query($con, $sql);

	}
}

$layout_menu = "cadastro";
$title       = traduz("RELATÓRIO DE PERGUNTAS FREQUENTES");

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"shadowbox",
	"dataTable"
);

include("plugin_loader.php");

?>

<script>

	$(function() {

		$.autocompleteLoad(["produto"], ["produto"]);

		Shadowbox.init();

		$(document).on("click", "span[rel=lupa]", function () {
			$.lupa($(this),Array('posicao'));
		});

	});

	function retorna_produto (retorno) {
		$("#produto").val(retorno.produto);
		$("#referencia").val(retorno.referencia);
		$("#descricao").val(retorno.descricao);
	}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php
}
?>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<!-- <div class='span8'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto'>Produto</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<select name="produto" id="produto">
							<option value=""></option>
						<?php
							$sql = "SELECT  distinct tbl_produto.produto  ,
									tbl_produto.descricao
									FROM    tbl_produto
									JOIN    tbl_faq USING (produto)
									JOIN    tbl_linha on tbl_produto.linha = tbl_linha.linha
									WHERE   tbl_faq.produto = tbl_produto.produto
									AND     tbl_linha.fabrica = $login_fabrica
									ORDER BY tbl_produto.descricao";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
								$selected_produto = ( isset($produto) and ($produto == $key['produto']) ) ? "SELECTED" : '' ;
						?>
								<option value="<?php echo $key['produto']?>" <?php echo $selected_produto ?> ><?php echo $key['descricao']?></option>
						<?php
							}
						?>
						</select>
					</div>
				</div>
			</div>
		</div> -->

		<?php
		if ($login_fabrica == 148) {
		?>
		<div class="span4">
                        <div class='control-group <?=(in_array('linha', $msg_erro['campos'])) ? 'error' : ''; ?>'>
                                <label class='control-label' for='linha'><?=traduz('Linha')?></label>
                                <div class='controls controls-row'>
                                        <div class='span7 input-append'>
                                                <h5 class='asteristico'>*</h5>
						<select id="linha" name="linha" class="span12" >
							<option value=""><?=traduz('Selecione')?></option>
							<?php
							$sqlLinha = "SELECT linha, nome FROM tbl_linha WHERE fabrica = {$login_fabrica} ORDER BY nome ASC";
							$resLinha = pg_query($con, $sqlLinha);

							while($linha = pg_fetch_object($resLinha)) {
								$selected = ($linha->linha == getValue("linha")) ? "selected" : "";
								echo "<option value='{$linha->linha}' {$selected} >{$linha->nome}</option>";
							}
							?>
						</select>
                                        </div>
                                </div>
                        </div>
                </div>
		<?php
		} else {
		?>
		<div class="span4">
			<input type="hidden" name="produto" id="produto" value="<?=$produto?>" />
			<div class='control-group <?=(in_array('produto', $msg_erro['campos'])) ? 'error' : ''; ?>'>
				<label class='control-label' for='referencia'><?=traduz('Ref. Produto')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="referencia" name="referencia" class='span12' maxlength="20" value="<? echo $referencia ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>	
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array('produto', $msg_erro['campos'])) ? 'error' : ''; ?>">
				<label class='control-label' for='descricao'><?=traduz('Descrição Produto')?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="descricao" name="descricao" class='span12' value="<? echo $descricao ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>	
		</div>
		<?php
		}
		?>

		<div class="span2"></div>
	</div>
	<p><br/>
	<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
	<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>


<?

if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
?>
<BR>
<table id="faq_relatorio" class='table table-striped table-bordered table-hover table-large' style="width: 100%; table-layout: fixed;">
	<thead>
		<TR class='titulo_coluna'>
			<TH><?=traduz('Situação')?></TH>
			<?php
			if ($login_fabrica == 148) {
			?>
				<th><?=traduz('Linha')?></th>
			<?php
			} else {
			?>
				<th><?=traduz('Produto')?></th>
			<?php
			}
			?>
			<TH><?=traduz('Causas')?></TH>
			<TH><?=traduz('Soluções')?></TH>
		</TR>
	</thead>
	<tbody>

<?php
	for ($i = 0 ; $i < pg_numrows ($resSubmit) ; $i++) {

		$situacao   =  trim(pg_result($resSubmit,$i,"situacao"));
		$faq        =  trim(pg_result($resSubmit,$i,"faq"));
		if ($login_fabrica == 148) {
			$linha = pg_fetch_result($resSubmit, $i, "linha");
		} else {
			$produto = pg_fetch_result($resSubmit, $i, "produto");
		}
		echo "<tr>\n";
		echo "<td>{$situacao}</td>\n";
		echo "<td>".(($login_fabrica == 148) ? $linha : $produto)."</td>";

		$sql = "SELECT  tbl_faq_causa.faq_causa  ,
			tbl_faq_causa.causa
			FROM	tbl_faq_causa
			JOIN	tbl_faq on tbl_faq_causa.faq = tbl_faq.faq
			WHERE	tbl_faq_causa.faq = $faq";

		$res2 = pg_query($con,$sql);

		if (pg_num_rows($res2) > 0) {
			echo "<td valign='top'>\n\n";
			echo "<ul>";
			$aux2 = 0;
			for ($j = 0 ; $j < pg_numrows ($res2) ; $j++) {
				$aux++;

				$causa     	 = trim(pg_result($res2,$j,"causa"));
				$faq_causa[] = trim(pg_result($res2,$j,"faq_causa"));
				echo "<li>$causa</li>\n";
			}
			echo "</ul>";						
			echo "</td>\n";
			$faq_causas = implode(",", $faq_causa);
			unset($faq_causa);

			$sql = "SELECT  tbl_faq_solucao.faq_causa,
				tbl_faq_solucao.solucao   
				FROM    tbl_faq_solucao
				JOIN    tbl_faq_causa on tbl_faq_solucao.faq_causa = tbl_faq_causa.faq_causa
				JOIN	tbl_faq on tbl_faq_causa.faq=tbl_faq.faq
				WHERE   tbl_faq_causa.faq         = $faq
				AND     tbl_faq_solucao.faq_causa IN($faq_causas)";

			$res3 = @pg_exec ($con,$sql);

			if (pg_numrows ($res3) > 0) {
				echo "<td  valign='top'>\n\n";
				echo "<ul>\n";
				$aux3 = 0;
				for ($r = 0 ; $r < pg_numrows ($res3) ; $r++) {
					$aux++;

					$solucao   =  trim(pg_result($res3,$r,"solucao"));
					echo "<li>$solucao</li>\n";
				}
				echo "</ul>\n";
				echo "</td>\n\n";
			}
		}
		echo "</tr>\n";
	}
	echo "</tbody>\n";
	echo "</table>\n";
	}
}
?>
<p>
<? include "rodape.php"; ?>
