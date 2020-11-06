<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';

if (isset($_POST['ajaxfunc']) && $_POST['ajaxfunc'] == "sim") {

	/* ----------- SELECIONA PROMOTOR ------------ */
	if (isset($_POST['promotor'])) {
		if ($_POST['promotor'] !== "sim") {
			$where = $_POST['promotor'];
		}else{
			$where = "lower(email) = '".strtolower($_POST['email'])."' AND lower(nome) = '".strtolower($_POST['nome'])."'";
		}

		$sql = "SELECT 
					promotor_treinamento,
					nome,
					email,
					admin,
					ativo,
					pais,
					aprova_troca,
					tipo,
					escritorio_regional
				FROM tbl_promotor_treinamento
				WHERE $where
				AND fabrica = $login_fabrica";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$res = pg_fetch_assoc($res);
			$res = array_map(function($valor){
				return $valor;
			}, $res);

			exit(json_encode($res));
		} else {
			exit(json_encode(array("erro" => utf8_encode("Nenhum promotor encontrado"))));
		}
	}

	/* ----------- LISTA PROMOTOR ----------- */
	if (isset($_POST['listar']) && $_POST['listar'] == "sim") {
		$inativo = $_POST['inativo'];

		$where = 't';
		if($inativo == 1){
			$where = 'f';
		}
		$sql = "SELECT tbl_promotor_treinamento.promotor_treinamento,
							tbl_promotor_treinamento.nome,
							tbl_promotor_treinamento.email,
							tbl_promotor_treinamento.ativo,
							tbl_promotor_treinamento.aprova_troca,
							tbl_promotor_treinamento.admin,
							tbl_promotor_treinamento.pais,
							tbl_promotor_treinamento.tipo,
							tbl_admin.login                    AS login_admin,
							tbl_escritorio_regional.descricao
				FROM tbl_promotor_treinamento 
				LEFT JOIN tbl_escritorio_regional USING(escritorio_regional)
				LEFT JOIN tbl_admin          USING(admin)
				WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
				AND   tbl_promotor_treinamento.ativo = '$where'
				ORDER BY tbl_promotor_treinamento.nome";

		$res = @pg_exec($con,$sql);
		$tabela  = "";

		if (pg_numrows($res) > 0) {			
			$tabela  = "<table id='tbPromotor' class='table table-striped table-bordered table-hover table-fixed dataTable'>";
			$tabela .= "<thead>";
			$tabela .= "<tr class='titulo_tabela'>";
			$tabela .= "<td>".traduz('Nome')."</td>";
			$tabela .= "<td>E-mail</td>";
			if ($_POST['escritorio'] == 1) {
				$tabela .= "<td width='15%'>Escritório</td>";
			}			
			#HD 13940
			if ($login_fabrica==20){
				$tabela .= "<td>Admin</td>";
				$tabela .= "<td width='10%'>País</td>";
				$tabela .= "<td width='25%'>Aprova troca</td>"; #HD 25955
			}
			if(in_array($login_fabrica, array(169,170,193))){
				$tabela .= "<td>Função</td>";
			}
			$tabela .= "<td width='15%'>".traduz('Ativo')."</td>";
			$tabela .= "<td width='14%'>".traduz('Opções')."</td>";
			$tabela .= "</thead>";
			$tabela .= "</tr>";
			$tabela .= "<tbody>";

			for ($i=0; $i<pg_numrows($res); $i++){
				$promotor_treinamento = pg_fetch_result($res,$i,"promotor_treinamento");
				$nome                 = pg_fetch_result($res,$i,"nome");
				if (mb_check_encoding($nome, 'UTF-8')) {
					$nome = utf8_decode($nome);
				}
				$email                = utf8_decode(pg_fetch_result($res,$i,"email"))  ;
				$ativo                = pg_fetch_result($res,$i,"ativo")               ;
				$admin                = pg_fetch_result($res,$i,"admin")               ;
				$login_admin          = pg_fetch_result($res,$i,"login_admin")         ;
				$descricao            = pg_fetch_result($res,$i,"descricao")           ;
				$aprova_troca         = pg_fetch_result($res,$i,"aprova_troca")        ;
				if(in_array($login_fabrica, array(169,170,193))){
					$tipo = pg_fetch_result($res, $i, "tipo");
					switch($tipo)
					{
						case 1:
							$tipo = traduz('Promotor');
						break;
						case 2:
							$tipo = traduz('Instrutor');
						break;
						case 3:
							$tipo = traduz('Promotor & Instrutor');
						break;
					}
					//$tipo = pg_fetch_result($res, $i, "tipo") == 1? "Promotor" : "Instrutor";
				}

				#112413
				$pais                 = pg_fetch_result($res,$i,"pais")                ;
				if(strlen($pais)==0){
					$pais = "-";
				}

				if($ativo == 't'){
					$ativo   = "<img src='imagens_admin/status_verde.gif' id='img_ativo_$i'>";
					$x_ativo = traduz("Ativo");
				}
				else{
					$ativo = "<img src='imagens_admin/status_vermelho.gif' id='img_ativo_$i'>";
					$x_ativo = traduz("Cancelado");
				}

				if($cor=="#F1F4FA"){
					$cor = '#F7F5F0';
				}else{
				    $cor = '#F1F4FA';
				}

				$tabela .= "<tr>";
				$tabela .= "<td>$nome</td>";
				$tabela .= "<td>$email</td>";
				if ($_POST['escritorio'] == 1) {
					$tabela .= "<td>$descricao</td>";
				}

				#HD 13940
				if ($login_fabrica==20){
					$tabela .= "<td>$login_admin</td>";					
					$tabela .= "<td>$pais</td>"; #112413

					#HD 25955
					$tabela .= "<td>";
					if($aprova_troca=='t'){
						$tabela .= "Sim</td>";
					}else{
						$tabela .= "Não</td>";
					}			
				}

				if(in_array($login_fabrica, array(169,170,193))){
					$tabela .= "<td>$tipo</td>";
				}

				$tabela .= "<td>$ativo $x_ativo</td>";
				$tabela .= "<td><button type='button' class='btn btn-primary btn-small seleciona-promotor' data-promotor='$promotor_treinamento'>".traduz('Alterar')."</button>";
				$tabela .= "</td>";
				$tabela .= "</tr>";
			}
			$tabela .= "</tbody>";	
			$tabela .= "</table>";
			exit(json_encode(array("ok" => utf8_encode($tabela))));
		}else{
			exit(json_encode(array("erro" => "sem registro")));
		}
	}

	/* ----------- LISTA PAÍSES ----------- */
	if (isset($_POST['listaPais']) && $_POST['listaPais'] == 'sim') {
		$sql = "SELECT 
					pais, 
					substring(nome FROM 1 for 1) || 
						CASE strpos(nome, ' ') WHEN 0 THEN 
							lower(substring(nome FROM 2)) 
						ELSE 
							substring(nome FROM 2) 
						END AS nome 
				FROM tbl_pais ORDER BY pais;";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			$listaCidade = "<select name='pais' id='pais' class='Caixa'>\n";
			$listaCidade .= "<option value=''>ESCOLHA</option>\n";
			
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_pais = trim(pg_result($res,$x,pais));
				$aux_nome_pais = trim(pg_result($res,$x,nome));
				
				$listaCidade .= "<option value='$aux_pais'"; 
				if ($aux_pais == "BR"){
					$listaCidade .= " SELECTED "; 
				}
				$listaCidade .= ">$aux_nome_pais</option>\n";
			}
			$listaCidade .= "</select>\n";
			exit(json_encode(array("ok" => utf8_encode($listaCidade))));
		}else{
			exit(json_encode(array("erro" => utf8_encode("Ocorreu um erro ao tentar listar os países, recarregue a pagina..."))));
		}
	}

	/* ----------- LISTA ADMIN ----------- */
	if (isset($_POST['listaAdmin']) && $_POST['listaAdmin'] == "sim") {
		$sql = "SELECT admin, login FROM tbl_admin WHERE fabrica = $login_fabrica;";

		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$listaAdmin = "<select name='admin' id='admin' class='Caixa'>\n";
			$listaAdmin .= "<option value=''>ESCOLHA</option>\n";
			
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_admin = trim(pg_result($res,$x,admin));
				$aux_login = trim(pg_result($res,$x,login));
				
				$listaAdmin .= "<option value='$aux_admin'";				
				$listaAdmin .= ">$aux_login</option>\n";
			}
			$listaAdmin .= "</select>\n";
			exit(json_encode(array("ok" => utf8_encode($listaAdmin))));
		}else{
			exit(json_encode(array("erro" => utf8_encode(traduz("Não foi possível listar os admins!")))));
		}
	}

	/* ----------- GRAVA/ALTERA PROMOTOR ----------- */
	if (isset($_POST['altpromotor'])) {
		$msg_erro = array(
			"msg" => array(),
			"campos" => array()
		);

		/* Pegar informações do $_POST */
		$promotor     = $_POST["altpromotor"];	
		$nome         = trim(utf8_decode($_POST["nome"]));
		$email        = trim($_POST["email"]);
		$admin        = $_POST["admin"];	
		$aprova_troca = $_POST["Aprova_troca_produto"]; #HD 25955
		$ativo        = $_POST["ativo"];
		$tipo_posto   = $_POST["tipo_posto"];
		$escritorio_regional = $_POST["listaEscritorio"];
		$pais         = $_POST["pais"];
		if(in_array($login_fabrica, array(169,170,193))){
			$tipo = $_POST['tipo'];
		}

		/* Validar Informações */
		if(!strlen($nome)){
			$msg_erro["msg"]["obg"] = utf8_encode(traduz("Por favor, preencha os campos obrigatórios (*)!"));
			$msg_erro["campos"][] = "nome";
		}
		if (!strlen($email)) {
			$msg_erro["msg"]["obg"] = utf8_encode(traduz("Por favor, preencha os campos obrigatórios (*)!"));
			$msg_erro["campos"][] = "email";			
		}

		if (in_array($login_fabrica, array(20)) && !strlen($pais)) {
			$msg_erro["msg"]["obg"] = utf8_encode(traduz("Por favor, preencha os campos obrigatórios (*)!"));
			$msg_erro["campos"][] = "pais";
		}

		if ($email !=="" && filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
			$msg_erro["msg"]["obg"] = utf8_encode(traduz("Email incorreto, por favor informe um válido!"));
			$msg_erro["campos"][] = "email";
		}

		/* Ações de gravar e alterar se não houver erro */
		if (!count($msg_erro["msg"])) {

			/* Aplicar tratativas nas informações */
			if (!strlen($pais)){
				$pais = "BR";
			}

			if(strlen($escritorio_regional)==0){
				$escritorio_regional = "NULL";
			}

			if(strlen($aprova_troca)==0){
				$aprova_troca = "FALSE";
			}

			if(strlen($tipo_posto)==0){
				$tipo_posto="null";
			}

			if (strlen($admin)==0){
				$admin = " NULL ";
			}		

			$tipo_campo ="";
			$tipo_valor = "";
			if(in_array($login_fabrica, array(169,170,193))){
				$tipo_campo = "tipo, ";
				$tipo_valor = " $tipo, ";
			}

			if($promotor == 0 ){
				$sql = "INSERT INTO tbl_promotor_treinamento (
							nome,
							email,
							escritorio_regional,
							ativo,
							aprova_troca,
							admin,
							pais,
							$tipo_campo
							fabrica
						)VALUES(
							'$nome',
							'$email',
							$escritorio_regional,
							'$ativo',
							'$aprova_troca',
							$admin,
							'$pais',
							$tipo_valor
							$login_fabrica
						)";
			}else{
				$tipo_campo ="";
				if(in_array($login_fabrica, array(169,170,193))){
					$tipo_campo = "tipo = $tipo, ";
				}
				$sql = "UPDATE tbl_promotor_treinamento SET
						nome       = '$nome',
						email      = '$email',
						escritorio_regional = $escritorio_regional,
						admin      = $admin,
						pais       = '$pais',
						aprova_troca = '$aprova_troca',
						$tipo_campo
						ativo      = '$ativo' 
					WHERE promotor_treinamento = '".$promotor."'";
			}
			$res = pg_exec($con,$sql);
			exit(json_encode(array("ok" => "ok")));
		} else {
			exit(json_encode(array("erro" => $msg_erro)));
		}
	}
}

$layout_menu = "tecnica";
$title = traduz("PROMOTOR DE TREINAMENTO");

include "cabecalho_new.php";

$plugins = array(
    "ajaxform",
    "dataTable"
);

include "plugin_loader.php";
include "javascript_pesquisas.php";

$inputs = array(
	"nome" => array(
		"span"      => 4,
		"label"     => traduz("Nome"),
		"type"      => "input/text",
		"width"     => 9,
		"maxlength" => 30,
		"required"  => true
	),
	"email" => array(
		"span"      => 4,
		"label"     => "E-mail",
		"type"      => "input/text",
		"width"     => 12,
		"maxlength" => 40,
		"required"  => true
	)	
);

/* VERIFICA SE POSSUI ESCRITÓRIO */
$sql = "SELECT DISTINCT 
			descricao,
			escritorio_regional 
		FROM tbl_escritorio_regional WHERE fabrica=$login_fabrica AND ativo ORDER BY descricao;";

$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {
	$inputs['listaEscritorio'] = array(
			"span"      => 4,
			"label"     => "Escritório",
			"type"      => "select",
			"option"    => array(),
			"width"     => 9,
			"span"      => 4
	);	
}

if ($login_fabrica == 20) { #HD 13940
	$inputs['pais'] = array(
		"label"     => "País",
		"type"      => "select",
		"option"    => array(),
		"width"     => 12,
		"span"      => 4,
		"required"  => true			
	);	
	$inputs['admin'] = array(
		"label"     => "Admin",
		"type"      => "select",
		"option"    => array(),
		"width"     => 9,
		"span"      => 4,
    	'popover' => array(
	        'id' => 'btnPopover',
	        'msg'   => "Selecione o ADMIN para o promotor ter acesso ao Assist para aprovar / recusar OS's de cortesia"
    	)			
	);
	$inputs['Aprova_troca_produto'] = array(
		"label"     => "Aprova troca de produto",
		"type"      => "radio",
		"radios" => array(
			"t" => "Sim",
			"f" => "Não"
		),
		"span"  => 4			
	);
}

$inputs['ativo'] = array(
	"label"     => traduz("Ativo"),
	"type"      => "radio",
	"radios" => array(
		"t" => traduz("Sim"),
		"f" => traduz("Não")
	),
	"span"  => 4			
);

if(in_array($login_fabrica, array(169,170,193))){
	$inputs['tipo'] = array(
		"label"     => "Tipo",
		"type"      => "select",
		"options"    => [
			"1" => "Promotor",
			"2" => "Instrutor",
			"3" => "Promotor & Instrutor"
		],
		"width"     => 12,
		"span"      => 4,
		"required"  => true			
	);	
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<div id="alertaErro" class="alert alert-error" style="display: none;"><h4></h4></div>
<div id="alertaSucesso" class="alert alert-success" style="display: none;"><h4></h4></div>
<form class="form-search form-inline tc_formulario" name="frm_relatorio" id="frm_relatorio">
	<div class='titulo_tabela '><?=traduz('Cadastro de Promotor de Treinamento')?></div>
    <br/>
	<? echo montaForm($inputs, $hiddens); ?>
	</br>
	<center>		
		<input type="button" class="btn" name="consultar" value="<?=traduz('Gravar')?>" id="btnGravar"/>
		<input type="button" class="btn btn-warning" name="Limpar" value="<?=traduz('Limpar')?>" id="btnLimpar"/>
	</center>
	</br>
	<center>
		<input type='BUTTON' class='btn btn-primary' id='btnListagem' value='<?=traduz('Listar os promotores inativos')?>'>
	</center>
	</br>
</form>
<div id="tabelaPromotor"></div>
<p>
<? include "rodape.php"; ?>

<script language='javascript'>
	var codPromotor = 0;
	var camposErro = [];

	$(function(){
		if ($('input:radio[name=Aprova_troca_produto]').length !== 0) {
			$('input:radio[name=Aprova_troca_produto]')[0].checked = true;
		}
		$('input:radio[name=ativo]')[0].checked = true;
		$("#btnPopover").popover();

		CarregaTabela();
		CarregaPais();
		CarregaAdmin();
		CarregaEscritorio();
	});

	$('#btnGravar').on('click',function(){
		$("#alertaErro").hide().find("h4").html("");
		$("#alertaSucesso").hide().find("h4").html("");

		if (codPromotor == 0) {
			$.ajax({
				method: "POST",
				url: "treinamento_promotor.php",
				data: {ajaxfunc: "sim", promotor: "sim", email: $('#email').val(), nome: $('#nome').val() },
				timeout: 8000
			}).fail(function(data){
				$("#alertaErro").show().find("h4").html("Erro ao tentar inserir um novo promotor, tempo limite esgotado!");
			}).done(function(data){
				data = JSON.parse(data);
				if (data.erro == undefined) {
					$("#alertaErro").show().find("h4").html("Promotor já existe!");
				}else{
					GravaAlteraUsuario();
				}
			});
		}else{
			GravaAlteraUsuario();
		}
	});

	$(document).on('click', 'button.seleciona-promotor', function(){
		$('#btnLimpar').click();
		var promotor = $(this).data('promotor');
		codPromotor = promotor;
		var btn = $(this);
		$(btn).prop({disabled: true}).text("Buscando...");

		$.ajax({
			method: "POST",
			url: "treinamento_promotor.php",
			data: {ajaxfunc: "sim", promotor: 'promotor_treinamento = '+codPromotor},
			timeout: 8000
		}).fail(function(data){
			$("#alertaErro").show().find("h4").html("Erro ao selecionar o promotor, tempo limite esgotado!");
			$(btn).prop({disabled: false}).text("Alterar");
		}).done(function(data){
			data = JSON.parse(data);
			if (data.erro) {
				$("#alertaErro").show().find("h4").html(data.erro);
			}else{
				if (data.ativo == 't') {
					$('input:radio[name=ativo]')[0].checked = true;
				}else{
					$('input:radio[name=ativo]')[1].checked = true;
				}
				if ($('input:radio[name=Aprova_troca_produto]').length !== 0) {
					if (data.aprova_troca == 't') {
						$('input:radio[name=Aprova_troca_produto]')[0].checked = true;
					}else{
						$('input:radio[name=Aprova_troca_produto]')[1].checked = true;
					}
				}

				$('#nome').val(data.nome);
				$('#email').val(data.email);			
				$('#admin').val(data.admin);
				$('#listaEscritorio').val(data.escritorio_regional);
				$('#pais').val(data.pais);
				$('#aprova_troca').val(data.aprova_troca);	
				<?php
				if(in_array($login_fabrica, array(169,170,193))){
					?>
					$("#tipo").val(data.tipo);
					<?php
				}
				?>
			}
			$(btn).prop({disabled: false}).text("Alterar");
		});
	});

	$('#btnListagem').on('click', function(){
		if ($('#btnListagem').val().indexOf('inativos') >= 0) {
			$('#btnListagem').val("Listar os promotores ativos");
			CarregaTabela(1);
		}else{
			$('#btnListagem').val("Listar os promotores inativos");
			CarregaTabela();
		}
	});

	$('#btnLimpar').on('click', function(){
		$("#alertaErro").hide().find("h4").html("");
		$("#alertaSucesso").hide().find("h4").html("");
		LimpaForm();
	});

	function GravaAlteraUsuario(){
		var parametros = $("#frm_relatorio").serialize()+"&ajaxfunc=sim&altpromotor="+codPromotor;
		$.ajax({
			method: "POST",
			url: "treinamento_promotor.php",
			data: parametros,
			timeout: 5000
		}).fail(function(){
			if (codPromotor == 0) {
				$("#alertaErro").show().find("h4").html("Promotor não cadastrado, tempo limite esgotado!");
			}else{
				$("#alertaErro").show().find("h4").html("Promotor não alterado, tempo limite esgotado!");
			}
		}).done(function(data){
			data = JSON.parse(data);
			if (data.erro !== undefined) {
				var mensagens = [];
				camposErro = data.erro.campos;

				$.each(data.erro.msg, function(i, msg) {
					mensagens.push(msg);
				});

				$("#alertaErro").show().find("h4").html(mensagens.join("<br />"));

				$.each(camposErro, function(i, input) {
					var div = $("input[name="+input+"], select[name="+input+"]").parents("div.control-group");
					$(div).addClass("error");
				});
			} else {
				if (codPromotor == 0) {
					$("#alertaSucesso").show().find("h4").html("<?=traduz('Promotor cadastrado com sucesso!')?>");
				}else{
					$("#alertaSucesso").show().find("h4").html("<?=traduz('Promotor alterado com sucesso!')?>");
				}
				if ($('#btnListagem').val().indexOf('inativos') >= 0) {
					CarregaTabela();
				}else{
					CarregaTabela(1);
				}
				LimpaForm();
			}
		});			
	}

	function LimpaForm(){
		$('input:text').val('');
		$('select').val('');
		$('#pais').val('BR');

		if ($('input:radio[name=Aprova_troca_produto]').length !== 0) {
			$('input:radio[name=Aprova_troca_produto]')[0].checked = true;
		}
		$('input:radio[name=ativo]')[0].checked = true;

		$.each(camposErro, function(i, input) {
			var div = $("input[name="+input+"], select[name="+input+"]").parents("div.control-group");
			$(div).removeClass("error");
		});

		codPromotor = 0;
	}

	function CarregaTabela(inativo = 0){
		var escritorio = $('#listaEscritorio').length;
		$.ajax({
			method: "POST",
			url: "treinamento_promotor.php",
			data: {ajaxfunc: "sim", listar: "sim", inativo: inativo, escritorio: escritorio},
			timeout: 60000
		}).fail(function(){
			$("#alertaErro").show().find("h4").html("Erro ao tentar listar os promotores, recarregue a pagina...");
		}).done(function(data){
			data = JSON.parse(data);

			if (data.ok !== undefined) {
				$('#tabelaPromotor').html(data.ok);
			    var table = new Object();
			    table['table'] = '#tbPromotor';
			    $.dataTableLoad(table);
			}
		});
	}

	function CarregaPais(){
		$.ajax({
			method: "POST",
			url: "treinamento_promotor.php",
			data: {ajaxfunc: "sim", listaPais: "sim"},
			timeout: 5000
		}).fail(function(){
			$("#alertaErro").show().find("h4").html("Erro ao listar os países, tempo limite esgotado! Recarregue a pagina...");
		}).done(function(data){
			data = JSON.parse(data);
			if (data.ok !== undefined) {
				$('#pais').html(data.ok);
			}else{
				$("#alertaErro").show().find("h4").html(data.erro);
			}			
		});
	}	

	function CarregaAdmin(){
		if ($('#admin').length == 1) {
			$.ajax({
				method: "POST",
				url: "treinamento_promotor.php",
				data: {ajaxfunc: "sim", listaAdmin: "sim"},
				timeout: 8000
			}).fail(function(){
				$("#alertaErro").show().find("h4").html("Erro ao listar os admins, tempo limite esgotado! Recarregue a pagina...");
			}).done(function(data){
				data = JSON.parse(data);
				if (data.ok !== undefined) {
					$('#admin').html(data.ok);
				}else{
					$("#alertaErro").show().find("h4").html("Erro ao listar os admins! Recarregue a pagina...");
				}
			});
		}
	}

	function CarregaEscritorio(){
		$.ajax({
			method: "GET",
			url: "ajax_treinamento.php",
			data: {ajax: "sim", acao: "listaEscritorio"},
			timeout: 8000
		}).fail(function(){
			$("#alertaErro").show().find("h4").html("Erro ao verificar a lista de escritórios, tempo limite esgotado! Recarregue a pagina...");
		}).done(function(data){
			data = JSON.parse(data);
			if (data.ok !== undefined) {
				$('#listaEscritorio').html(data.ok);
			}
		});
	}
</script>
