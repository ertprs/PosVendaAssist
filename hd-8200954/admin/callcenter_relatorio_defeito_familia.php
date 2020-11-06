<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';
include 'helpdesk/mlg_funciones.php';

$btn_acao = $_POST['btn_acao'];
if ($_POST["btn_acao"] == "submit") {
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
	$status             = $_POST['status'];

	if(strlen($produto_referencia) > 0 and strlen($produto_descricao) == 0) {
		$msg_erro = traduz("Preencha a descrição do produto");
	} else if(strlen($produto_referencia) == 0 and strlen($produto_descricao) > 0) {
		$msg_erro = traduz("Preencha a referência do produto");
	}

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro = traduz("Data Inválida");
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro = traduz("Data Inválida");
	}

	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
	}

	if($xdata_inicial > $xdata_final)
		$msg_erro = traduz("Data Inválida");

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}
	$cond_2 = " tbl_hd_chamado.data BETWEEN '{$xdata_inicial} 00:00:00' AND '{$xdata_final} 23:59:59' ";

	/*HD - 4306135*/
	if ($login_fabrica == 149) {
		$array_familia = $_POST["select_familia"];

		if (!empty($array_familia)) {
			$cond_3 = " tbl_produto.familia IN(". implode(",", $array_familia). ") ";
		}
	}

	if ($_POST["gerar_excel"]) {
		$sql_csv = "SELECT
						tbl_hd_chamado.hd_chamado,
						TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY HH24:MI') AS data_abertura_chamado,
						tbl_hd_chamado_origem.descricao AS origem,
						CASE WHEN tbl_familia.descricao is null then 'Sem Familia' ELSE tbl_familia.descricao END as familia,
						CASE WHEN tbl_defeito_reclamado.descricao is null then 'Sem Defeito Selecionado' ELSE tbl_defeito_reclamado.descricao END as defeito_descricao
						FROM tbl_hd_chamado
						INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
						INNER JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem
						LEFT JOIN tbl_produto USING (produto)
						LEFT JOIN tbl_familia ON (tbl_familia.familia = tbl_produto.familia)
						LEFT JOIN tbl_defeito_reclamado ON tbl_hd_chamado_extra.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
						WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
						AND {$cond_1}
						AND {$cond_2}
						AND {$cond_3}";
		$res_csv = pg_query($con, $sql_csv);

		$result = pg_fetch_all($res_csv);

		$data = date("d-m-Y-H:i");
		$fileName = "relatorio_reclamacao_familia_produto-{$data}.csv";
		$file = fopen("/tmp/{$fileName}", "w");

		$titulo = array('Número chamado','Data abertura','Origem',
            'Familia','Defeito descrição'
        );

		fwrite($file, $titulo);
    	$linhas = implode(";", $titulo)."\r\n";

    	foreach ($result as $key => $value) {
            $linhas .= implode(";", $value)."\r\n";
        }

        fwrite($file, $linhas);
    	fclose($file);

    	if (file_exists("/tmp/{$fileName}")) {
			system("mv /tmp/{$fileName} xls/{$fileName}");

			echo "xls/{$fileName}";
		}
		exit;
	}

}
$layout_menu = "callcenter";
$title = traduz("RELATÓRIO RECLAMAÇÕES X FAMÍLIA DE PRODUTOS");

include "cabecalho_new.php";
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");
?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("#data_inicial").datepicker().mask("99/99/9999");
		$("#data_final").datepicker().mask("99/99/9999");

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$.autocompleteLoad(Array("produto"));

		$("#select_familia").multiselect({
           selectedText: "# of # selected"
        });

	});

	function retorna_produto(retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
</script>
<style type="text/css">
	.table th, .table td{
		width: 300px !important;
	}
</style>
<script language='javascript' src='../ajax.js'></script>

	<? if(strlen($msg_erro)>0){ ?>
		<div class='alert alert-danger'><h4><? echo $msg_erro; ?></h4></div>
	<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">* <?=traduz('Campos obrigatórios ')?></b>
</div>
<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
		<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
		<br />
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Data Inválida") ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input class="span5" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial;  ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Data Inválida") ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input class="span5" type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Preencha a referência do produto") ? "error" : ""?>'>
					<label class='control-label'><?=traduz('Ref. Produto')?></label>
						<div class='controls controls-row input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" size="12" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Preencha a descrição do produto") ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?=traduz('Descrição Produto')?></label>
						<div class='controls controls-row input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" size="12" class='frm' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<?php if ($login_fabrica == 149) { /*HD - 4306135*/?>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class='span4'>
					<div class='control-group <?=($msg_erro == "Preencha a descrição do produto") ? "error" : ""?>'>
						<label class='control-label' for='data_final'><?=traduz('Família')?></label>
							<div class='controls controls-row'>
								<select name="select_familia[]" id="select_familia" multiple="multiple">
									<?php
										$aux_sql = "
											SELECT  familia, descricao
									        FROM    tbl_familia
									        WHERE   tbl_familia.fabrica = $login_fabrica
									        AND     tbl_familia.ativo = TRUE
									        ORDER BY tbl_familia.descricao
									    ";
									    $aux_res = pg_query($con, $aux_sql);

									    for ($z = 0; $z < pg_num_rows($aux_res); $z++) { 
									    	$id_familia = pg_fetch_result($aux_res, $z, 'familia');
									    	$descricao  = pg_fetch_result($aux_res, $z, 'descricao');

									    	?> <option value="<?=$id_familia;?>"><?=$descricao;?></option> <?
									    } ?>
								</select>
							</div>
					</div>
				</div>
				<div class="span2"></div>
			</div>
		<?php } ?>
		<br />
		<!--<input class="btn" type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>-->
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
		<br /><br />
</FORM>


<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	if ( empty($msg_erro) ) {
		$sql = "SELECT 	CASE WHEN tbl_familia.descricao is null then 'Sem Familia' ELSE tbl_familia.descricao END as familia,
						CASE WHEN tbl_familia.familia is null then '0' ELSE tbl_familia.familia END as familia_id,
						CASE WHEN tbl_hd_chamado_extra.defeito_reclamado is null then '0' ELSE tbl_hd_chamado_extra.defeito_reclamado END AS defeito,
						CASE WHEN tbl_defeito_reclamado.descricao is null then 'Sem Defeito Selecionado' ELSE tbl_defeito_reclamado.descricao END as defeito_descricao,
						COUNT(1) as qtde
				FROM tbl_hd_chamado
				INNER JOIN tbl_hd_chamado_extra USING (hd_chamado)
				LEFT JOIN tbl_produto USING (produto)
				LEFT JOIN tbl_familia ON (tbl_familia.familia = tbl_produto.familia)
				LEFT JOIN tbl_defeito_reclamado ON (tbl_hd_chamado_extra.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado)
				WHERE 1=1
				AND tbl_hd_chamado.fabrica = {$login_fabrica}
				AND {$cond_1}
				AND {$cond_2}
				AND {$cond_3}
				and tbl_hd_chamado.posto is null 
				and  tbl_hd_chamado.status<>'Cancelado'
				GROUP BY tbl_familia.familia, tbl_familia.descricao,tbl_hd_chamado_extra.defeito_reclamado, tbl_defeito_reclamado.descricao";

		$res  = @pg_exec($con,$sql);

		$rows = array();
		if ( is_resource($res) ) {
			if(in_array($login_fabrica, array(169,170))){
				while( $row = pg_fetch_assoc($res) ) {
					$idx = $row['familia_id'];
					$rows[$idx][] = $row;

					if($row['familia_id'] == $familia_anterior){
						$rows[$idx]['total'] += $row['qtde'];
					}else{
						$rows[$idx]['total'] = $row['qtde'];
					}
					$familia_anterior = $row["familia_id"];

					$total_geral += $row['qtde'];
				}
				mrsort($rows,"total");
			}else{
				while( $row = pg_fetch_assoc($res) ) {
					$idx = $row['familia_id'];
					$rows[$idx][] = $row;
				}
			}
		}
	}
	?>
<script>
function AbreCallcenter(familia,defeito){
janela = window.open("callcenter_relatorio_defeito_produto_callcenter.php?data_inicial=<?php echo $xdata_inicial; ?>&data_final=<?php echo $xdata_final; ?>&familia=" +familia+"&reclamado="+defeito, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>

<p>&nbsp;</p>
	<?php if ( isset($rows) ): ?>
	<table class='table table-bordered table-fixed'>
		<?php if ( count($rows) <= 0 ): ?>
		<tr class='alert alert-warning'>
			<td class='alert alert-warning' style='text-align: center;'><h4><?=traduz('Nenhum Registro encontrado ')?></h4></td>
		</tr>
		<?php else: ?>
			<?php if(in_array($login_fabrica, array(169,170))){ ?>
			<tr class='titulo_coluna'><th><?=traduz('Qtde Geral: ')?><?=$total_geral?></th></tr>
			<?php } ?>
			<?php foreach ($rows as $familia_id=>$row): ?>
				<tr class='titulo_coluna'>
					<?php $familia = $row[0]['familia']; ?>
					<th> <strong><?php echo $familia; ?></strong> </th>
				</tr>
				<tr>
					<td>
					<table class='table' style='width: 100%;'>
					<?php if(in_array($login_fabrica, array(169,170))){ ?>
						<tr>
							<th class='titulo_coluna'><?=traduz('Defeito')?></th>
							<th class='titulo_coluna'><?=traduz('Qtde')?></th>
							<th class='titulo_coluna'><?=traduz('Porcentagem %')?></th>
						</tr>
					<?php
					}

						if(in_array($login_fabrica, array(169,170))){
							$total = 0;
							unset($row['total']);
							$xtotal = 0;
							foreach ($row as $idx=>$defeito){
								$xtotal += $defeito['qtde'];
							}
						}

						foreach ($row as $idx=>$defeito){
							if(in_array($login_fabrica, array(169,170))){
								$total += $defeito['qtde'];
								$porc = $defeito['qtde'];
								$porc2 = ($porc/$xtotal)*100;
								$porc2 = number_format($porc2,'2','.','.');

							}
						?>
						<tr bgcolor="<?php echo ($idx%2)?'#F7F5F0':'#F1F4FA'; ?>">
							<td class=''> <?php echo $defeito['defeito_descricao']; ?> </td>
							<td class='tac'> <a href="javascript: AbreCallcenter(<?php echo $defeito['familia_id']; ?>,<?php echo $defeito['defeito']; ?>);"><?php echo $defeito['qtde']; ?></a> </td>
							<?php if(in_array($login_fabrica, array(169,170))){ ?>
								<td class='tac'> <?php echo $porc2.' %'; ?> </td>
							<?php } ?>
						</tr>
					<?php }
						if(in_array($login_fabrica, array(169,170))){
							$porc3 = ($total/$total_geral)*100;
							$porc3 = number_format($porc3,'2','.','.');
					?>
						<tr class='titulo_coluna'>
							<th class='tac' width="80%"><?=traduz('Total')?></th>
							<th class='tac' width="20%"><?=$total?> </th>
							<th class='tac' width="80%"><?=traduz('% sobre Qtde Geral: ')?><?=$porc3?> %</th>
						</tr>
					<?php } ?>
					</table>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</table>
	<?php
		if(in_array($login_fabrica, array(169,170))){
			$jsonPOST = excelPostToJson($_POST);
			echo "<div id='gerar_excel' class='btn_excel'>
				<input type='hidden' id='jsonPOST' value='$jsonPOST' />
				<span><img src='imagens/excel.png' /></span>
				<span class='txt'>".traduz('Gerar Arquivo CSV')."</span>
			</div>";
		}
	?>
	<?php endif; ?>

	<?php

}

?>

<p>&nbsp;</p>

<? include "rodape.php" ?>
