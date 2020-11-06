<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
if($_POST) {
	$extrato = $_POST['extrato'] ;
	$posto = $_POST['posto'];

	if(!empty($extrato) and !empty($posto)) {
		foreach($_POST['pecas_lgr'] as $key) {
			$pecas = explode('_',$key);
			if(count($pecas) > 0) {
				$sql = "update tbl_faturamento_item set extrato_devolucao = $extrato where peca = ".$pecas[0]. " and faturamento in ( " . $pecas[1] . " ) and extrato_devolucao isnull; update tbl_faturamento set   extrato_devolucao = $extrato, info_extra = '{\"admin_lgr\": \"$login_admin\",\"posto_devolver\":\"$posto\"}' where faturamento in ( ".$pecas[1] ." )  ;";
				$res = pg_query($con, $sql);
			}
		}


		$sql= "		INSERT INTO tbl_extrato_lgr (
                        extrato,
                        posto,
                        peca,
                        qtde
                    )
                    SELECT  tbl_extrato.extrato,
                            tbl_extrato.posto,
                            tbl_faturamento_item.peca,
                            SUM (tbl_faturamento_item.qtde)
                    FROM    tbl_extrato
                    JOIN    tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
                    WHERE   tbl_extrato.extrato = $extrato
              GROUP BY      tbl_extrato.extrato,
                            tbl_extrato.posto,
							tbl_faturamento_item.peca";
		$res = pg_query($con, $sql);
	}
}
$layout_menu = "financeiro";
$title = "GERAÇÃO DE PEÇAS LGR";

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

$(function(){
	$("#data_inicial").datepicker().mask("99/99/9999");
	$("#data_final").datepicker().mask("99/99/9999");

	$.autocompleteLoad(Array("posto"));

	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});



	$("button.visualizar-extrato").on("click", function() {

		var extrato = $(this).data("extrato");

		window.open("extrato_consulta_os.php?extrato="+extrato);
	});

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
	$("#id_posto").val(retorno.posto);
}

function checkAll(extrato) {

	$('.checkSingle_'+extrato).prop('checked', function (i, value) {
		    return !value;
	});
}

function gerarLGR(extrato) {

	if($('#posto_'+extrato+ ' :selected').val() !=''){
		$('#frm_lgr_'+extrato).submit();
	}else{
		alert('selecione onde devolver');
	}
}
</script>
<style>
div.extrato_lgr_pecas {
    background-color: #DDDDDD;
    text-align: left;
}

div.extrato_lgr_pecas a {
    text-decoration: none;
    color: #000000;
    font-weight: bold;
}



div.extrato_lgr_pecas button, div.extrato_lgr_pecas span.label {
    margin-top: 8px;
    margin-right: 5px;
}

div.extrato_lgr_pecas span.label {
    width: 170px;
    text-align: center;
}


</style>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<div class="form-search form-inline tc_formulario">
	<form name="frm_posto" style='margin-bottom:0' method="post" action="<? echo $PHP_SELF ?>">
		<div class='titulo_tabela'>Parâmetros de Pesquisa</div>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<input class='span12' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<?= $data_inicial ?>" >
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
							<input class='span12' type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<?= $data_final ?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<div class='controls controls-row'>
						<label class='control-label' for='codigo_posto'>Código Posto</label>
						<div class='controls controls-row'>
							<div class="span7 input-append">
								<input class='span12' type="text" name="codigo_posto" id="codigo_posto" value="<?= $codigo_posto ?>" class="Caixa">
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<div class='controls controls-row'>
						<label class='control-label' for='nome_posto'>Nome do Posto</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input  class='span12' type="text" name="posto_nome" id="descricao_posto"  value="<?= $posto_nome?>" class="Caixa">
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='span6 input-append'>
					<label class='control-label' for='extrato'>Extrato</label>
					<div class="row-fluid">
						<div class='span12 input-append'>
							<input  class='span12' type="text" name="extrato_busca" id="extrato"  value="<?= $extrato_busca?>" class="Caixa">
						</div>
					</div>
				</div>
			</div>
		</div>
		<p>
        <button class="btn" type="submit" name="acao" value="pesquisar" >Pesquisar</button>
		</p>
		<br/>
	</form>
</div>
<br/>
<?
if(strlen($_POST['acao'])  > 0) {
	$id_posto = $_POST['id_posto'];
	$data_inicial = $_POST['data_inicial'];
	$data_fiinal = $_POST['data_final'];
	$extrato_busca = $_POST['extrato_busca'];

	if(!empty($id_posto)) {
		$cond = " and tbl_extrato.posto = $id_posto ";
	}

	if(!empty($data_inicial) and !empty($data_final)) {
		$cond .= " and tbl_extrato.data_geracao between '$data_inicial 00:00' and '$data_final 23:59' ";
	}

	if(!empty($extrato_busca)) {
		$cond .= " and tbl_extrato.extrato = $extrato_busca ";
	}
	$sql = "SELECT	referencia,
					descricao,
					tbl_faturamento_item.peca,
					tbl_faturamento_item.faturamento_item,
					tbl_faturamento_item.faturamento,
					tbl_faturamento_item.qtde,
					tbl_os.sua_os,
					tbl_os_extra.extrato,
					tbl_faturamento.nota_fiscal,
					tbl_extrato.data_geracao,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto
			into temp tmp_lgr_$login_admin
			FROM tbl_faturamento_item
			join tbl_os_item using(pedido, pedido_item)
			join tbl_faturamento using(faturamento)
			join tbl_os_produto using(os_produto)
			join tbl_os_extra on tbl_os_produto.os = tbl_os_extra.os
			join tbl_os on tbl_os.os = tbl_os_extra.os
			join tbl_peca on tbl_os_item.peca = tbl_peca.peca
			join tbl_posto on tbl_os.posto = tbl_posto.posto
			join tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			join tbl_extrato on tbl_os_extra.extrato = tbl_extrato.extrato 
			where tbl_os_extra.extrato notnull
			and tbl_faturamento.info_extra->>'admin_lgr' isnull
			and tbl_extrato.aprovado isnull
			and tbl_peca.devolucao_obrigatoria
			and tbl_faturamento_item.extrato_devolucao isnull
			and tbl_faturamento.fabrica = $login_fabrica
			and tbl_os.fabrica = $login_fabrica
			and tbl_extrato.fabrica = $login_fabrica
			$cond
			;

			select distinct extrato, to_char(data_geracao,'DD/MM/YYYY') as data_geracao, nome, codigo_posto from tmp_lgr_$login_admin
	";

	$res = pg_query($con, $sql);

	if(pg_num_rows($res) >0) { ?>
		<div class="alert alert-info" >
			<strong>Clique no número do extrato para visualizar as peças</strong>
		</div>

		<div class="accordion" id="extrato_lgr">
			<?php

			for($i=0;$i<pg_num_rows($res);$i++) {
				$extrato = pg_fetch_result($res,$i,'extrato');
				$posto_nome = pg_fetch_result($res,$i,'nome');
				$data_geracao = pg_fetch_result($res,$i,'data_geracao');
				?>
				<div id="<?=$extrato?>" class="accordion-group" >
					<div class="accordion-heading extrato_lgr_pecas" >
						<a class="accordion-toggle" data-toggle="collapse" data-parent='#extrato_lgr' href="#extrato_<?=$extrato?>" >
							<span class="icon-resize-full" ></span>
							Extrato <?=$extrato?>
						</a>
					</div>
					<div id="extrato_<?=$extrato?>"  class="accordion-body collapse" >
						<div class="accordion-inner">
							<div class="row-fluid" >
								<div class="span2" >
									<div class="control-group" >
										<label class="control-label" >Data Geração</label>
										<div class="controls controls-row" >
											<?=$data_geracao?>
										</div>
									</div>
								</div>
								<div class="span4" >
									<div class="control-group" >
										<label class="control-label" >Posto</label>
										<div class="controls controls-row" >
											<?=$posto_nome?>
										</div>
									</div>
								</div>
								<div class="span2" >
										<div class="control-group" >
											<button type="button" class="btn btn-mini btn-primary pull-right visualizar-extrato" data-extrato="<?=$extrato?>" >
												<i class="icon-search icon-white"></i> Visualizar Extrato
											</button>
										</div>
								</div>
							</div>
							<form id="frm_lgr_<?=$extrato?>" name='frm_lgr' style='margin-bottom:0' method="post" action="<? echo $PHP_SELF ?>">
							<input type='hidden' name='extrato' value='<?=$extrato?>'>
							<table class="table table-bordered table-striped" id='pecas_<?=$extrato?>' >
								<thead>
									<tr class="titulo_coluna">
									<th><input type='checkbox'  onclick='checkAll(<?=$extrato?>)'></th>
										<th>Peça</th>
										<th>Descrição</th>
										<th>Qtde</th>
										<th>Notas Fiscais</th>
									</tr>
								</thead>
								<tbody>
									<?php
										$sql = "select peca, referencia, descricao,extrato,
													sum(qtde) as qtde,
													array_to_string(array_agg(nota_fiscal),',') as nfs,
													array_to_string(array_agg(faturamento),',') as fats
													 from tmp_lgr_$login_admin group by 1,2,3,4";
										$resx = pg_query($con,$sql);
										$pecas = pg_fetch_all($resx);
										foreach ($pecas as $peca) {
									?>
										<tr>
										<td><input type='checkbox' name='pecas_lgr[]' value='<?=$peca["peca"]."_".$peca['fats']. "_". $peca['qtde']?>' class='checkSingle_<?=$extrato?>'></td>
											<td><?=$peca["referencia"]?></td>
											<td><?=$peca["descricao"] ?></td>
											<td class="tac" ><?=$peca["qtde"]?></td>
											<td class="tac" ><?=$peca['nfs']?></td>
									   </tr>
									<?php
										}
									?>
								</tbody>
								<tfoot>
									<tr>
										<td colspan='2'><select name='posto' id='posto_<?=$extrato?>'><option value=''>Selecione</option>
											<?$sql = "select nome, contato_cidade , posto
													from tbl_posto_fabrica
													join tbl_posto using(posto)
													join tbl_tipo_posto using(tipo_posto, fabrica)
													where fabrica = $login_fabrica
													and (tbl_tipo_posto.descricao='LGR' or 1=1) limit 3";
												$resp = pg_query($con,$sql);
												for($p=0;$p<pg_num_rows($resp);$p++) {
													$posto = pg_fetch_result($resp,$p,'posto');
													$nome = pg_fetch_result($resp,$p,'nome');
													$contato_cidade = pg_fetch_result($resp,$p,'contato_cidade');
													echo "<option value='$posto'>$nome - $contato_cidade</option>";
												}
											?>
											</select>

										</td>
										<td class='tac' colspan='100%'><button class='btn' type='button' onclick='gerarLGR(<?=$extrato?>)'>Gerar LGR</button>
									</tr>
								</tfoot>
							</table>
							</form>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>

	<?php
	}else{
		echo "<h2 class='tac'>Nenhum resultado encontrado</h2>";
	}
}

    include 'rodape.php';
?>
