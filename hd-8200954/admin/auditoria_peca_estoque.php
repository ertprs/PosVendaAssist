<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia, call_center";

include "autentica_admin.php";
include "funcoes.php";
$programa_insert = $_SERVER['PHP_SELF'];

$btn_acao = $_POST['btn_acao'];

function validaOS($os, $posto = null){
	global $con, $login_fabrica;

	if($posto != null){
		$condicao = " AND posto = {$posto}";
	}else{
		$condicao = "";
	}

	$sql = "SELECT os FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica} {$condicao}";
	$res = pg_query($con,$sql);

	if(pg_num_fields($res) > 0){
		return true;
	}else{
		return false;
	}
}
if($btn_acao == 'Pesquisar'){
	$lista_todos     = $_POST['lista_todos'];
	$codigo_posto    = $_POST['codigo_posto'];
	$descricao_posto = $_POST['descricao_posto'];
	$estado          = $_POST['estado'];

	if(empty($lista_todos) && empty($descricao_posto) && empty($estado)){
		$msg_erro[] = "Preencha informações do posto, estado ou selecione a opção listar todas auditorias.";
	}

	if($lista_todos == 't'){
		$condicao = "";
	}

	if($codigo_posto != ""){
		$sql = "SELECT 	tbl_posto.posto
				FROM 	tbl_posto
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE 	tbl_posto.nome = '{$descricao_posto}'
				AND 	codigo_posto = '{$codigo_posto}'
				AND 	fabrica = {$login_fabrica}";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$posto = pg_fetch_result($res, 0, "posto");
			$condicao = " AND tbl_os.posto = {$posto} ";
		}else{
			$msg_erro[] = "Posto não encontrado.";
		}
	}

	if(strlen($estado) > 0){
		$condicao .= " AND tbl_posto.estado = '{$estado}' ";
	}

	if(count($msg_erro) == 0 ){
		$sql = "SELECT tbl_os.os,
				tbl_os.sua_os,
				tbl_os.consumidor_nome,
				tbl_posto.nome,
				tbl_posto.posto,
				tbl_os.data_abertura,
				tbl_os.nota_fiscal,
				tbl_os.data_nf,
				tbl_produto.descricao
			FROM tbl_os
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
					INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				WHERE tbl_os.fabrica = {$login_fabrica}
					AND (SELECT status_os FROM tbl_os_status
						WHERE tbl_os_status.os = tbl_os.os AND status_os IN (203,204,205) ORDER BY data DESC LIMIT 1
					) = 205
				{$condicao}";
		$resAuditoria = pg_query($con,$sql);

	}

}

if($btn_acao == "aprovaOS"){
	$os = $_POST['os'];

	if(validaOS($os)){
		pg_query($con, "BEGIN TRANSACTION");

		$sql = "INSERT INTO tbl_os_status (os, status_os, observacao, fabrica_status) VALUES
			({$os},204,'Aprovado auditoria por troca de peça',{$login_fabrica})";
		pg_query($con,$sql);

		if (strlen(pg_last_error()) > 0) {
			pg_query($con,"ROLLBACK");
			$resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar aprovação da auditoria da OS {$os}"));
		} else {
			pg_query($con,"COMMIT");
			$resposta = array("ok" => true);
		}
	}else{
		$resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao aprovar auditoria da OS {$os}"));
	}
	echo json_encode($resposta); exit;
}

if($btn_acao == "reprovaOS"){
	$os    = $_POST['os'];
	$posto = $_POST['posto'];
	$$justificativa = $_POST['justificativa'];

	if(validaOS($os,$posto)){
		pg_query($con, "BEGIN TRANSACTION");

		$sql = "INSERT INTO tbl_os_status (os, status_os, observacao, fabrica_status) VALUES
			({$os},203,'Reprovado auditoria por troca de peça',{$login_fabrica})";
		pg_query($con,$sql);

		if (strlen(pg_last_error()) > 0) {
			pg_query($con,"ROLLBACK");
			$resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao reprovar auditoria da OS {$os}"));
		} else {
			$sql = " INSERT INTO tbl_comunicado (
						fabrica,
						posto,
						obrigatorio_site,
						tipo,
						ativo,
						descricao,
						mensagem
					) VALUES (
						{$login_fabrica},
						{$posto},
						true,
						'Com. Unico Posto',
						true,
						'Auditoria da {$os} reprovada pela fábrica',
						'OS {$os} reprovada da auditoria Troca de Peça por motivo: ".utf8_decode($justificativa)."'
					)";
			pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				pg_query($con,"ROLLBACK");
				$resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar comunicado da auditoria na OS {$os}"));
			}else{
				unset($sql);
				$sql = " SELECT tbl_os_item.os_item FROM tbl_os_item
						INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
					WHERE tbl_os_produto.os = {$os} AND tbl_os_item.pedido IS NULL AND tbl_os_item.pedido_item IS NULL
						AND servico_realizado IN (SELECT servico_realizado FROM tbl_servico_realizado
							WHERE fabrica = {$login_fabrica} AND gera_pedido IS TRUE AND troca_de_peca IS TRUE)";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					$count = pg_num_rows($res);
					$resposta = "";

					for($i = 0; $i < $count; $i++){
						$os_item = pg_fetch_result($res, $i, "os_item");

						$sql = " UPDATE tbl_os_item SET
							servico_realizado = (SELECT servico_realizado FROM tbl_servico_realizado
								WHERE fabrica = {$login_fabrica} AND gera_pedido IS FALSE AND
								troca_de_peca IS FALSE AND ativo IS TRUE AND
								troca_produto IS FALSE AND peca_estoque IS FALSE AND
								garantia_acessorio IS FALSE AND posto_interno IS FALSE)
							WHERE tbl_os_item.os_item = {$os_item}";
						pg_query($con,$sql);

						if(strlen(pg_last_error()) > 0){
							pg_query($con, "ROLLBACK");
							$resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar serviço realizado na peça da OS {$os}"));
							break;
						}
					}
					if($resposta == ""){
						pg_query($con, "COMMIT");
						$resposta = array("ok" => true);
					}
				}else{
					pg_query($con, "COMMIT");
					$resposta = array("ok" => true);
				}
			}
		}
	}else{
		$resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao número informado da OS {$os}"));
	}
	echo json_encode($resposta); exit;
}

if ($_POST["interagir"] == true) {
	$interacao = utf8_decode(trim($_POST["interacao"]));
	$os        = $_POST["os"];

	if (!strlen($interagir)) {
		$retorno = array("erro" => utf8_encode("Digite a interação"));
	} else if (empty($os)) {
		$retorno = array("erro" => utf8_encode("OS não informada"));
	} else {
		$select = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$result = pg_query($con, $select);

		if (!pg_num_rows($result)) {
			$retorno = array("erro" => utf8_encode("OS não encontrada"));
		} else {
			$insert = "INSERT INTO tbl_os_interacao
					   (programa,os, admin, fabrica, comentario)
					   VALUES
					   ('$programa_insert',{$os}, {$login_admin}, {$login_fabrica}, '{$interacao}')";
			$result = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => utf8_encode("Erro ao interagir na OS"));
			} else {
				$retorno = array("ok" => true);
			}
		}
	}
	echo json_encode($retorno); exit;
}

$layout_menu = "callcenter";
$title = "ESTOQUE DO POSTO AUTORIZADO";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"maskedinput",
	"shadowbox",
	"alphanumeric"
);

include "plugin_loader.php";

?>

<style>
#mensagem_justificativa{
	margin-left: 80px;
}

.admin {
	background-color: #FF00FF;
}

.posto {
	background-color: #FFFF00;
}

</style>
<script>

$(function() {
	Shadowbox.init();
	$.autocompleteLoad(["posto", "peca"]);

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	$("button[id^=btReprovado_]").click(function(){
		var linha = this.id.replace(/\D/g, "");
		var os    = $("#os_"+linha).val();
		var posto = $("#posto_"+linha).val();
		$("input.numero_linha").val(linha);
		$("input.numero_os").val(os);
		$("input.numero_posto").val(posto);

		Shadowbox.open({
			content: $(".div_justificativa").html(),
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});
	});
});

function retorna_posto(retorno) {
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function aprovarOS(linha, os){
	if(confirm("Deseja realmente aprovar a OS?")){
		$("button[id=btAprovado_"+linha+"]").button('loading');

		var dataAjax = {
	        os: os,
	        btn_acao: "aprovaOS"
	    };

		$.ajax({
	        url: "<?php echo $_SERVER['PHP_SELF']; ?>",//'relatorio_auditoria_status.php',
	        type: "POST",
	        data: dataAjax,
	    }).done( function(data){
	    	var mensagem;
	    	data = JSON.parse(data);
	    	if(data.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
	    	}else{
	    		$("#mensagem").html();
	    		$("#resultado_posto > tbody > tr > td[id=status_"+linha+"]").html('<label class="label label-success">Aprovado</label>');
	    	}
    		$("button[id=btAprovado_"+linha+"]").button('reset');
		}).fail(function(data){
			if(data.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
	    	}
			$("button[id=btAprovado_"+linha+"]").button('reset');
		});
	}
}

function reprovarOS(){
	var resposta;
	resposta = confirm("Deseja realmente Reprovar a OS?");

	if(resposta){
		var linha = $("input.numero_linha").val();
		$("button[id=btReprovado_"+linha+"]").button('loading');

		var dataAjax = {
	        os: $("input.numero_os").val(),
	        posto: $("input.numero_posto").val(),
	        justificativa: $.trim($("#sb-container").find("textarea#justificativa").val()),
	        btn_acao: "reprovaOS"
	    };

		$.ajax({
	        url: "<?php echo $_SERVER['PHP_SELF']; ?>",//'relatorio_auditoria_status.php',
	        type: "POST",
	        data: dataAjax,
	    }).done( function(data){
	    	var mensagem;
	    	data = JSON.parse(data);
	    	if(data.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
	    	}else{
	    		$("#mensagem").html();
	    		$("#resultado_posto > tbody > tr > td[id=status_"+linha+"]").html('<label class="label label-important">Reprovado</label>');
	    	}
			$("button[id=btReprovado_"+linha+"]").button('reset');
			Shadowbox.close();

		}).fail(function(data){
			if(data.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
	    	}
			$("button[id=btReprovado_"+linha+"]").button('reset');
			Shadowbox.close();
		});
	}
}

</script>

<?php if (count($msg_erro) > 0) { ?>
    <div class="alert alert-error" >
		<h4><?=implode("<br />", $msg_erro)?></h4>
    </div>
<?php } ?>
<div class="div_justificativa" style="display:none; margin: 5px; padding-right: 20px;">
	<div id="mensagem_justificativa">
		<br/>
		<label>Justificativa</label>
		<textarea id="justificativa" name="justificativa" rows="10" cols="10" style="margin: 0px 0px 10px; width: 603px; height: 200px;"></textarea>
		<br/>
		<button type="button" style="position:rigth" class="btn btn-primary btn-sucess" data-loading-text="Salvando..." id="btJustificativa" onclick="reprovarOS();">Salvar</button>
	</div>
</div>
<form name="frm_auditoria_peca_estoque" method="POST" class="form-search form-inline tc_formulario" >
	<div id="mensagem"></div>
	<div class="titulo_tabela" >Parâmetros de Pesquisa</div>
	<br />
	<input type="hidden" class="numero_os" value="" />
	<input type="hidden" class="numero_linha" value="" />
	<input type="hidden" class="numero_posto" value="" />

	<div class="row-fluid" >
		<div class="span2" ></div>

		<div class="span4" >
			<div class="control-group" >
				<label class="control-label" for="codigo_posto" >Código Posto</label>

				<div class="controls controls-row" >
					<div class="span7 input-append" >
						<input type="text" name="codigo_posto" id="codigo_posto" class="span12" value="<? echo $codigo_posto ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>

		<div class="span4" >
			<div class="control-group" >
				<label class="control-label" for="descricao_posto" >Nome Posto</label>

				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="descricao_posto" id="descricao_posto" class="span12" value="<? echo $descricao_posto ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2" ></div>
	</div>

	<?php if(in_array($login_fabrica, array(151))){ ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="estado" >Estado</label>
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
		<? } ?>
	<div class="row-fluid" >
		<div class="span2" ></div>

		<div class="span4" >
			<input type='checkbox' <? if ($lista_todos == 't' ) echo " checked " ?> name='lista_todos' value='t'>
			<label>Listar todas auditorias</label>
		</div>
	</div>

	<p>
		<br/>
		<input type="submit" class="btn" name="btn_acao" type="button" value="Pesquisar">
		<!-- <input type="hidden" id="btn_click" name="btn_acao"/> -->
	</p>

	<br />

</form>

<?php

if(pg_num_rows($resAuditoria) > 0 ){

		?>
		<table id="resultado_posto" class='table table-striped table-bordered table-large' style="margin: 0 auto;" >
			<thead>
				<tr class='titulo_coluna'>
					<th>OS</th>
					<th>Consumidor</th>
					<th>Data</th>
					<th>POSTO</th>
					<th>Produto</th>
					<th>Nota Fiscal</th>
					<th>Data Nota</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php

				$count = pg_num_rows($resAuditoria);

				for($i=0; $i < $count; $i++){
					$os_status         = pg_fetch_result($resAuditoria, $i, "os_status");
					$os                = pg_fetch_result($resAuditoria, $i, "os");
					$sua_os            = pg_fetch_result($resAuditoria, $i, "sua_os");
					$consumidor_nome   = pg_fetch_result($resAuditoria, $i, "consumidor_nome");
					$nome_posto        = pg_fetch_result($resAuditoria, $i, "nome");
					$posto_codigo      = pg_fetch_result($resAuditoria, $i, "posto");
					$data_abertura     = pg_fetch_result($resAuditoria, $i, "data_abertura");
					$nota_fiscal       = pg_fetch_result($resAuditoria, $i, "nota_fiscal");
					$data_nf           = pg_fetch_result($resAuditoria, $i, "data_nf");
					$descricao_produto = pg_fetch_result($resAuditoria, $i, "descricao");

					list($a,$m,$d) = explode("-",$data_abertura);
					$data_abertura = $d."/".$m."/".$a;

					list($a,$m,$d) = explode("-",$data_nf);
					$data_nf = $d."/".$m."/".$a;

					?>
					<tr>
						<td>
							<input type="hidden" id="os_<?=$i?>" value="<?=$os?>">
							<input type="hidden" id="posto_<?=$i?>" value="<?=$posto_codigo?>">
							<a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a><br/>
						</td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$consumidor_nome?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$data_abertura?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$nome_posto?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$descricao_produto?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$nota_fiscal?></td>
						<td style="background-color: <?=$color?>; border-color: <?=$border_color?>"><?=$data_nf?></td>

						<td nowrap style="background-color: <?=$color?>; border-color: <?=$border_color?>" id="status_<?=$i?>" >
							<button type="button" class="btn btn-success btn-small" data-loading-text="Salvando..." id="btAprovado_<?=$i?>" onclick="aprovarOS(<?=$i?>,<?=$os?>);">Aprovar</button>
							<button type="button" class="btn btn-danger btn-small" data-loading-text="Salvando..." id="btReprovado_<?=$i?>">Reprovar</button>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?

}else if(strlen($btn_acao) > 0){ ?>
	<div class="container">
		<div class="alert">
			<h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
<?php
}
include "rodape.php";
?>
