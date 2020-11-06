<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include "autentica_admin.php";
include __DIR__.'/funcoes.php';

if(isset($_POST["btn_acao"])){
	$btn_acao = $_POST["btn_acao"];
}

if ($btn_acao == "aprovar") {
	$faturamento = trim($_POST['faturamento']);

	pg_query($con, "BEGIN TRANSACTION");

	$sql = "UPDATE tbl_faturamento SET garantia_antecipada = 'true'
        WHERE faturamento = {$faturamento} AND fabrica = {$login_fabrica}";
	pg_query($con, $sql);

	if(strlen(pg_last_error()) > 0){
		pg_query($con, "ROLLBACK");
		$resposta = array("resultado" => false, "mensagem" => "Erro ao aprovar a auditoria.");
	}else{

        $sql = "SELECT tbl_pedido.posto,
                    tbl_pedido.tipo_pedido,
                    tbl_pedido.condicao,
                    tbl_pedido.pedido
                FROM tbl_pedido
            WHERE tbl_pedido.pedido in (SELECT DISTINCT tbl_faturamento_item.pedido FROM tbl_faturamento_item
            	INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
					AND tbl_faturamento.fabrica = {$login_fabrica}
				WHERE tbl_faturamento_item.faturamento = {$faturamento}) order by pedido desc limit 1";
        $res = pg_query($con,$sql);


        if(pg_num_rows($res) > 0){

        	while($objeto_pedidos = pg_fetch_object($res)){

	            $sql = "INSERT INTO tbl_pedido (fabrica, posto, tipo_pedido, condicao, status_pedido) VALUES
	                ({$login_fabrica},{$objeto_pedidos->posto}, {$objeto_pedidos->tipo_pedido}, {$objeto_pedidos->condicao}, 1) RETURNING pedido";
	            $pedido = pg_query($con,$sql);

	            if(strlen(pg_last_error()) > 0){
	                pg_query($con, "ROLLBACK");
	                $resposta = array("resultado" => false, "mensagem" => utf8_encode("Não foi possível gerar um novo pedido com as peças que estão faltando."));
	            }else{
	                $sql = "SELECT
							tbl_faturamento_item.peca,
							tbl_faturamento_item.qtde_quebrada,
							tbl_faturamento_item.preco,
							tbl_faturamento_item.preco * tbl_faturamento_item.qtde_quebrada as total_item
						FROM tbl_faturamento
							JOIN tbl_faturamento_item using(faturamento)
						WHERE tbl_faturamento_item.qtde_quebrada > 0
							AND tbl_faturamento_item.faturamento = $faturamento
							AND tbl_faturamento.fabrica = $login_fabrica";
	                $resPedido_item = pg_query($con,$sql);

	                if(pg_num_rows($resPedido_item) > 0){
						$count  = pg_num_rows($resPedido_item);
						$pedido = pg_fetch_result($pedido, 0, "pedido");
						$aux    = false;

	                    for($i=0; $i < $count; $i++){
	                        $peca           = pg_fetch_result($resPedido_item, $i, "peca");
	                        $qtde           = pg_fetch_result($resPedido_item, $i, "qtde_quebrada");
	                        $preco          = pg_fetch_result($resPedido_item, $i, "preco");
	                        $total_item          = pg_fetch_result($resPedido_item, $i, "total_item");

	                        $sql = "INSERT INTO tbl_pedido_item (pedido, peca, qtde,preco, total_item) VALUES
	                            ({$pedido}, {$peca}, {$qtde}, {$preco}, {$total_item})";
	                        pg_query($con,$sql);
	                        if(strlen(pg_last_error()) > 0){
	                            pg_query($con,"ROLLBACK");
	                            $resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar as informações da peça no novo pedido."));
	                            break;
	                        }else{
	                            $aux = true;
	                        }
	                    }

	                    if($aux == true){
	                        pg_query($con, "COMMIT");
	                        //pg_query($con, "ROLLBACK");
	                        $resposta = array("resultado" => true);
	                    }
	                }else{
	                    pg_query($con, "ROLLBACK");
	                    $resultadoposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao buscar as informações sobre as peças faltantes."));
	                }
	            }
	        }
        }else{
            pg_query($con, "ROLLBACK");
            $resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao buscar informações sobre o faturamento para nova geração de pedido das peças faltantes."));
        }

	}
	echo json_encode($resposta); exit;
}


if($btn_acao == "pesquisar"){
	$status_auditoria = trim($_POST['status_auditoria']);
	$numero_nf        = trim($_POST['numero_nf']);
	$data_inicial     = trim($_POST["data_inicial"]);
	$data_final       = trim($_POST["data_final"]);
	$posto_codigo     = trim($_POST["posto"]["codigo"]);
	$posto_nome       = trim($_POST["posto"]["nome"]);
	$estado           = trim($_POST["estado"]);

	if(strlen($numero_nf) == 0){

		if($status_auditoria == "" || empty($data_inicial) || empty($data_final)){
			$msg_erro['msg'][] = "Preencha os campos obrigatórios!";
			$msg_erro["campos"][] = "status_auditoria";
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		}

		if(count($msg_erro['msg']) == 0){
			try {
				validaData($data_inicial, $data_final, 1);

				list($dia, $mes, $ano) = explode("/", $data_inicial);
	            $aux_data_inicial      = $ano."-".$mes."-".$dia;

	            list($dia, $mes, $ano) = explode("/", $data_final);
	            $aux_data_final        = $ano."-".$mes."-".$dia;

	            $condicao = " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			} catch (Exception $e) {
				$msg_erro["msg"][] = $e->getMessage();
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
			}
		}
	}

	if(count($msg_erro["msg"]) == 0){

		if(strlen($estado) > 0){
			$condEstado .= " AND tbl_posto.estado = '$estado' ";
		}

		if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
			$sql = "SELECT tbl_posto_fabrica.posto FROM tbl_posto
						JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
						AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}')) OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$posto_nome}'), 'LATIN-9'))
					)";
			$res = pg_query($con ,$sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]   .= "Posto não encontrado";
				$msg_erro["campos"][] = "posto";
			} else {
				$posto = pg_fetch_result($res, 0, "posto");
			}
		}

		if(count($msg_erro["msg"]) == 0){
			$condicao = "";

			switch ($status_auditoria) {
				// PENDENTE
				case 1: $condicao = " AND tbl_faturamento.garantia_antecipada IS NULL
				AND tbl_faturamento_item.qtde_quebrada > 0
				";
				break;

				// APROVADO
				case 2: $condicao = " AND tbl_faturamento.garantia_antecipada IS TRUE ";
					break;

				// REPROVADO
				case 3: $condicao = " AND tbl_faturamento.garantia_antecipada IS FALSE ";
					break;
			}

	       	if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
				$condicao .= " AND tbl_posto_fabrica.posto = $posto";
			}

			if(strlen($numero_nf) > 0){
				$condicao .= " AND tbl_faturamento.nota_fiscal = '$numero_nf' ";
			}else{
				$condicao .= " AND tbl_faturamento.emissao BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
			}

			$sql = "SELECT
					tbl_faturamento.faturamento,
					tbl_faturamento.emissao,
					tbl_faturamento.nota_fiscal,
					tbl_faturamento.serie,
					sum(tbl_faturamento_item.qtde_quebrada) as  total_faltante,
					sum(tbl_faturamento_item.qtde) as total_faturada,
					tbl_faturamento.obs,
					tbl_posto.nome
				FROM tbl_faturamento
					JOIN tbl_faturamento_item using(faturamento)
					JOIN tbl_posto using(posto)
				WHERE tbl_faturamento.fabrica = $login_fabrica
					{$condicao}
				GROUP BY tbl_faturamento.faturamento,
					tbl_faturamento.emissao,
					tbl_faturamento.nota_fiscal,
					tbl_faturamento.serie,
					tbl_faturamento.obs,
					tbl_posto.nome
				ORDER BY tbl_faturamento.emissao";
				//echo nl2br($sql);
			$resConsulta = pg_query($con,$sql);
		}
	}
}

$layout_menu = "auditoria";
$title = "AUDITORIA DE CONFERÊNCIA DE RECEBIMENTO";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "price_format"
);

include __DIR__."/plugin_loader.php";

?>
<style>
.admin {
	background-color: #FF00FF;
}

.posto {
	background-color: #FFFF00;
}

#numero_nf {
	width: 100px;
}

select.status_auditoria {
	width: 175px;

}

div.div_justificativa {
    display: none;
    margin: 5px;
    padding-right: 20px;
}

textarea {
    margin: 0px 0px 10px;
    width: 603px;
    height: 200px;
}
</style>
<script type="text/javascript">
$(function() {
	$("#data_inicial").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	Shadowbox.init();

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

    $("button[id=btn_limpar]").on("click",function(){
    	window.location = "<?=$_SERVER['PHP_SELF']?>";
    });

    $("button[id^=btAprovado_]").on("click",function(){
        var acao = confirm("Deseja realmente aprovar a auditoria? Depois de aprovado, não é possível realizar alteração!")

        if(acao){
            var linha = this.id.replace(/\D/g, "");
            var faturamento = $("#faturamento_"+linha).val();
            $("button[id=btAprovado_"+linha+"]").button('Salvando...');

            $.ajax({
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "post",
                data: {
                    faturamento: faturamento,
                    btn_acao: "aprovar"
                }
            }).done(function(data){
                data = JSON.parse(data);
                if(data.resultado == false){
                    $("div.mensagem-erro").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
                }else{
                    $("div.mensagem-erro").html("");
                    $("#resultado_auditoria > tbody > tr > td[id=status_"+linha+"]").html('<label class="label label-success">Aprovado</label>');
                }
                $("button[id=btAprovado_"+linha+"]").button('reset');
            }).fail(function(data){
                if(data.resultado == false){
                    $("div.mensagem-erro").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
                }
                $("button[id=btAprovado_"+linha+"]").button('reset');
            });
        }
    });

    $("button[id^=btReprovado_]").on("click",function(){
        var linha = this.id.replace(/\D/g, "");
        var faturamento = $("#faturamento_"+linha).val();
        var nota_fiscal = $("#nota_fiscal_"+linha).val();
        var serie       = $("#serie_"+linha).val();

        Shadowbox.open({
            content: "auditoria_conferencia_justificativa.php?faturamento="+faturamento+"&nf="+nota_fiscal+"&serie="+serie+"&linha="+linha,
            player: "iframe",
            width: 800,
            height: 450,
            options: {
                enableKeys: false
            }
        });
    });

    $("label[id^=qtde_falta_]").on("click", function(){
        var linha       = this.id.replace(/\D/g, "");
        var faturamento = $("#faturamento_"+linha).val();
        var nota_fiscal = $("#nota_fiscal_"+linha).val();
        var serie       = $("#serie_"+linha).val();

        Shadowbox.open({
            content: "auditoria_conferencia_peca.php?faturamento="+faturamento+"&nf="+nota_fiscal+"&serie="+serie,
            player: "iframe",
            width: 900,
            height: 400,

            options: {
                enableKeys: false
            }
        });
    });
});

function retorna_posto(retorno) {
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);
}

function retorno_grava_conferencia(faturamento) {
    var tr = $("#"+faturamento);
    $(tr).find("td").last().html("<span class='label label-important'>Reprovado</span>");
    Shadowbox.close();
}
</script>

<? if (count($msg_erro['msg']) > 0) { ?>
	<br/>
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
	<br/>
<? } ?>
<div class="mensagem-erro"></div>
<form name="frm_auditoria_conferencia" method="POST" action="<?echo $PHP_SELF?>" align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<div class='row-fluid'>
			<div class="span2"></div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_inicial">Data Inicial</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_inicial" name="data_inicial" class="span12" type="text" value="<?=$data_inicial?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('data_final', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_final">Data Final</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_final" name="data_final" class="span12" type="text" value="<?=$data_final?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span3">
				<div class='control-group <?=(in_array('status_auditoria', $msg_erro['campos'])) ? "error" : "" ?>'>
					<label class="control-label" for="data_final">Status da auditória</label>
					<div class="controls controls-row">
						<div class="span6"><h5 class='asteristico'>*</h5>
							<select name="status_auditoria" class="status_auditoria">
								<option value="">Selecione um status</option>
								<option value="1" <?php echo $status_auditoria == 1 ? "selected" : ""; ?>>Pendente</option>
								<option value="2" <?php echo $status_auditoria == 2 ? "selected" : ""; ?>>Aprovado</option>
								<option value="3" <?php echo $status_auditoria == 3 ? "selected" : ""; ?>>Reprovado</option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="numero_nf">Nº da Nota Fiscal</label>
					<div class="controls controls-row">
						<div class="span6 input-append">
							<input type="text" id="numero_nf" name="numero_nf" class="span4" value="<?=$numero_nf?>"/>
						</div>
					</div>
				</div>
			</div>
			<input type="hidden" id="posto" name="posto" value="<?=$posto?>" />
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="posto_codigo">Código do Posto</label>
					<div class="controls controls-row">
						<div class="span10 input-append">
							<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>"/>
							<span class="add-on" rel="lupa">
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="posto_nome">Nome do Posto</label>
					<div class="controls controls-row">
						<div class="span10 input-append">
							<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>"/>
							<span class="add-on" rel="lupa">
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="estado" >Estado/Região</label>
					<div class="controls control-row">
						<select id="estado" name="estado" class="span12" >
							<option value="" ></option>
							<?php

							foreach ($array_estados() as $sigla => $estado_nome) {
								$selected = ($estado == $sigla) ? "selected" : "";

								echo "<option value='{$sigla}' {$selected} >{$estado_nome}</option>";
							}

							?>
						</select>
					</div>
				</div>
			</div>
		</div>


		<p>
			<button type="submit" class='btn tac' id="btn_acao" type="button" >Pesquisar</button>
			<button type="button" class='btn btn-primary' id="btn_limpar" type="button" >Limpar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='pesquisar' />
		</p>
		<br />
</form>
<br>
<?php

if($btn_acao == "pesquisar"){
	if (pg_num_rows($resConsulta) > 0) {

?>
		<table id="resultado_auditoria" class='table table-striped table-bordered table-large' style="margin: 0 auto;" >
			<thead>
				<tr class='titulo_coluna'>
					<th>Posto Autorizado</th>
					<th>Data</th>
					<th>Nota Fiscal</th>
					<th>Série</th>
					<th>Quantidade Faturada</th>
					<th>Quantidade Faltante</th>
					<?php if($status_auditoria == 3 || !empty($numero_nf)){ ?>
						<th>Observação</th>
					<? } ?>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$count = pg_num_rows($resConsulta);
				for ($i = 0 ; $i < $count; $i++) {
					$faturamento         = pg_fetch_result($resConsulta,$i,'faturamento');
					$garantia_antecipada = pg_fetch_result($resConsulta,$i,'garantia_antecipada');
					$posto_nome          = pg_fetch_result($resConsulta,$i,'nome');
					$data_emissao        = pg_fetch_result($resConsulta,$i,'emissao');
					$nota_fiscal         = pg_fetch_result($resConsulta,$i,'nota_fiscal');
					$serie               = pg_fetch_result($resConsulta,$i,'serie');
					$qtde_faturada       = pg_fetch_result($resConsulta,$i,'total_faturada');
					$qtde_faltante       = pg_fetch_result($resConsulta,$i,'total_faltante');

					if(empty($garantia_antecipada)){
						$status_auditoria = 1;
					}else if($garantia_antecipada == "t"){
						$status_auditoria = 2;
					}else{
						$status_auditoria = 3;
					}

					if($status_auditoria == 3){
						$observacao	= utf8_decode(pg_fetch_result($resConsulta,$i,'obs'));
					}

					list($ano,$mes,$dia) = explode("-",$data_emissao);
					$data_emissao = $dia."/".$mes."/".$ano;
					?>
					<tr id="<?=$faturamento?>" >
						<td class="tac">
                            <input type="hidden" id="faturamento_<?=$i?>" value="<?=$faturamento?>">
                            <input type="hidden" id="nota_fiscal_<?=$i?>" value="<?=$nota_fiscal?>">
							<input type="hidden" id="serie_<?=$i?>" value="<?=$serie?>">
							<?=$posto_nome?>
						</td>
						<td class="tac"><?=$data_emissao?></td>
						<td class="tac"><?=$nota_fiscal?></td>
						<td class="tac"><?=$serie?></td>
						<td class="tac"><?=$qtde_faturada?></td>
						<td class="tac"><label id="qtde_falta_<?=$i?>" class="label label-info" ><?=$qtde_faltante?></label></td>
						<?php
						/*
							CASO SEJA FEITO UMA PESQUISA POR NÚMERO DA NOTA FISCAL, DEVE APARECER O CAMPO OBSERVAÇÃO
						*/
						if($status_auditoria == 3 || !empty($numero_nf)){
						?>
							<td class="tac"><?=$observacao?></td>
						<?php
						}
						?>

						<td nowrap class="tac" id="status_<?=$i?>" >
						<?php if($status_auditoria == 1) { ?>
							<button type="button" class="btn btn-success btn-small" data-loading-text="Salvando..." id="btAprovado_<?=$i?>">Aprovar</button>
							<button type="button" class="btn btn-danger btn-small" data-loading-text="Salvando..." id="btReprovado_<?=$i?>">Reprovar</button>
						<?php }else if($status_auditoria == 2){ ?>
							<label class="label label-success">Aprovado</label>
						<?php }else{ ?>
							<label class="label label-danger">Reprovado</label>
						<?php } ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php }else{ ?>
		<div class="container">
			<div class="alert">
				<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}

include "rodape.php";
?>
