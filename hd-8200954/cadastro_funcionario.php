 <?php
include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_usuario.php';
include __DIR__.'/funcoes.php';

function validaHora($hora){

	$t = explode(":",$hora);
	if ($t == "")
		return false;

	$h = $t[0];
	$m = $t[1];

	if (!is_numeric($h) || !is_numeric($m) )
		return false;

	if ($h < 0 || $h > 24)
		return false;

	if ($m < 0 || $m > 59)
		return false;

	return true;
}

if($_POST['calcula_exp'] == "calcular_exp"){
	$data_admissao 	= $_POST['data_admissao'];
	$tecnico 		= $_POST['tecnico'];
	$posto 			= $_POST['posto'];

	$data_admissao = explode("/", $data_admissao);
	$data_admissao = "{$data_admissao[2]}-{$data_admissao[1]}-{$data_admissao[0]}";
	$date = date('Y-m-d');
	$diferenca = strtotime($date) - strtotime($data_admissao);
	$dias = floor($diferenca / (60*60*24));
	$experiencia_total = $dias / 365;

	list($ano, $resto) = explode('.', $experiencia_total);
	$experiencia_total = $ano;
	$result = array('status' => "ok", 'experiencia_total' => $experiencia_total);
	echo json_encode($result);
	die;
}

#POST - Gravar
if ($_POST["gravar"] == "Gravar" || $_POST["gravar"] == "Registrar") {

	$funcionario_nome        = $_POST['funcionario_nome'];
	$data_nascimento         = $_POST['data_nascimento'];
	$funcao                  = $_POST['funcao'];
	$funcionario_cep         = $_POST['funcionario_cep'];
	$funcionario_estado      = $_POST['funcionario_estado'];
	$funcionario_cidade      = $_POST['funcionario_cidade'];
	$funcionario_bairro      = $_POST['funcionario_bairro'];
	$funcionario_endereco    = $_POST['funcionario_endereco'];
	$funcionario_numero      = $_POST['funcionario_numero'];
	$funcionario_complemento = $_POST['funcionario_complemento'];
	$funcionario_telefone    = $_POST['funcionario_telefone'];
	$funcionario_celular     = $_POST['funcionario_celular'];
	$funcionario_cpf         = $_POST['funcionario_cpf'];
	$funcionario_rg          = $_POST['funcionario_rg'];
	$funcionario_email       = $_POST['funcionario_email'];
	$observacao              = $_POST['observacao'];
	$formacao_academica      = $_POST['formacao_academica'];
	$anos_experiencia        = $_POST['anos_experiencia'];
	$data_admissao           = $_POST['data_admissao'];
	$numero_whatsapp         = $_POST["numero_whatsapp"];

	if($login_fabrica == 20){
		$numero_calcado  = $_POST["numero_calcado"];
		$numero_camiseta = $_POST["numero_camiseta"];
	}

	if (in_array($login_fabrica, array(158))) {
		$inicio_trabalho			= $_POST['inicio_trabalho'];
		$fim_trabalho				= $_POST['fim_trabalho'];

		if (!strlen($inicio_trabalho)) {
			$msg_erro["campos"][] = "inicio_trabalho";
			$msg_erro["msg"][] = "Informe o Início do Trabalho";
		}

		if (!strlen($fim_trabalho)) {
			$msg_erro["campos"][] = "fim_trabalho";
			$msg_erro["msg"][] = "Informe o Fim do Trabalho";
		}
	}

	$array_dados_complementares = array(
		"whatsapp" => $numero_whatsapp,
		"cep"      => $funcionario_cep,
	);

	if($login_fabrica == 20){
		$array_dados_complementares["numero_calcado"]  = $numero_calcado;
		$array_dados_complementares["numero_camiseta"] = $numero_camiseta;
	}

	foreach ($array_dados_complementares as $key => $value) {
		if (!is_array($value)) {
			$array_dados_complementares[$key] = utf8_encode($value);
		}
	}
	$array_dados_complementares = str_replace("\\", "\\\\", json_encode($array_dados_complementares));

	if(empty($anos_experiencia)) {
		$anos_experiencia = 'null';
	}

	if($cook_idioma == "pt-br") {
		$funcionario_rg = str_replace(".", "",$funcionario_rg);
		$funcionario_rg = str_replace("-", "",$funcionario_rg);
	}

	//unset($_POST);

	// Validação Campos Obrigatórios
	if($cook_idioma == "pt-br"){
		if(!strlen($funcionario_cpf)){
			$msg_erro["campos"][] = "cpf";
		}
		if(!strlen($funcionario_cep)){
			$msg_erro["campos"][] = "cep";
		}
		if(!strlen($funcionario_estado)){
			$msg_erro["campos"][] = "estado";
		}
		if(!strlen($funcionario_numero)){
			$msg_erro["campos"][] = "numero";
		}
		if(!strlen($funcionario_rg)){
			$msg_erro["campos"][] = "rg";
		} else if (strlen($funcionario_rg) > 15){
			$msg_erro["msg"][] = "Tamanho do RG inválido";
		}
	}

	if(!strlen($funcionario_nome)){
		$msg_erro["campos"][] = "nome";
	}

	if(!strlen($funcao)){
		$msg_erro["campos"][] = "funcao";
	}

	if(!strlen($funcionario_cidade)){
		//$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "cidade";
	}
	if(!strlen($funcionario_bairro)){
		//$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "bairro";
	}
	if(!strlen($funcionario_endereco)){
		//$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "endereco";
	}

	//Validação da Data
	if (strlen($data_nascimento) > 0 ) {
		list($di, $mi, $yi) = explode("/", $data_nascimento);
		if (!checkdate($mi, $di, $yi)) {
			$msg_erro["msg"][] = "Data Inválida";
		}
		$data_nascimento = "'".formata_data($data_nascimento)."'";
	}else{
		$data_nascimento = "null";
	}

	//Validação da Data Admissão
	if(!strlen($data_admissao)){
		$msg_erro["data_admissao"][] = "data_admissao";
	}
	if (strlen($data_admissao) > 0 ) {

		list($di, $mi, $yi) = explode("/", $data_admissao);

		if (!checkdate($mi, $di, $yi)) {
			$msg_erro["msg"][] = "Data Inválida";
		}
		$data_admissao = "'".formata_data($data_admissao)."'";
	}else{
		$msg_erro["campos"][] = "data_admissao";
	}

	if($cook_idioma == "pt-br"){
		if (count($msg_erro["campos"]) > 0) {
			$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		}

		if (strlen($funcionario_bairro) > 80) {
			$msg_erro["msg"][] = "O campo Bairro não pode ter mais de 80 caracteres";
			$msg_erro["campos"][] = "funcionario_bairro";
		}
		if (strlen($funcionario_endereco) > 80) {
			$msg_erro["msg"][] = "O campo Endereço não pode ter mais de 80 caracteres";
			$msg_erro["campos"][] = "funcionario_endereco";
		}

		if (strlen($formacao_academica) > 100) {
			$msg_erro["msg"][] = "O campo Formação Acadêmica não pode ter mais de 100 caracteres";
			$msg_erro["campos"][] = "formacao_academica";
		}
	}else{
		if (count($msg_erro["campos"]) > 0) {
			$msg_erro["msg"][] = "Rellene los campos obligatorios";
		}
		if (strlen($funcionario_bairro) > 80) {
			$msg_erro["msg"][] = "El campo de distrito no puede ser superior a 80 caracteres";
			$msg_erro["campos"][] = "funcionario_bairro";
		}
		if (strlen($funcionario_endereco) > 80) {
			$msg_erro["msg"][] = "El campo Dirección no puede contener más de 80 caracteres";
			$msg_erro["campos"][] = "funcionario_endereco";
		}

		if (strlen($formacao_academica) > 100) {
			$msg_erro["msg"][] = "El campo de la educación no puede exceder de 100 caracteres";
			$msg_erro["campos"][] = "formacao_academica";
		}
	}

	if (in_array($login_fabrica, array(158))) {
		if (strlen($inicio_trabalho) > 0 || strlen($fim_trabalho) > 0) {
			$inicio_valido = validaHora($inicio_trabalho);
			$fim_valido = validaHora($fim_trabalho);

			if (!$inicio_valido) {
				$msg_erro['msg'][] = "Hora de Trabalho inicial inválida";
				$msg_erro['campos'][] = 'inicio_trabalho';
			}

			if (!$fim_valido) {
				$msg_erro['msg'][] = "Hora de Trabalho Final inválida";
				$msg_erro['campos'][] = 'fim_trabalho';
			}
		}

		if (count($msg_erro['msg']) === 0) {
			$inicio_trabalho = "'".$inicio_trabalho.":00"."'";
			$fim_trabalho = "'".$fim_trabalho.":00"."'";
		}
	}else{
		$inicio_trabalho = "null";
		$fim_trabalho = "null";
	}

	// Cadastramento de Funcionário
	if (count($msg_erro["msg"]) === 0){
		
		if (!empty($tecnico)) {

			if($cook_idioma == "pt-br"){
				$sql = "SELECT cpf
					FROM tbl_tecnico
					WHERE cpf = '".preg_replace("/[\.\-\/]/", "",$funcionario_cpf)."'
					AND tecnico <> $tecnico
					AND (posto = $login_posto or posto isnull)
					AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
			}else{
				$sql = "SELECT rg
					FROM tbl_tecnico
					WHERE rg = '".preg_replace("/[\.\-\/]/", "",$funcionario_rg)."'
					AND tecnico <> $tecnico
					AND (posto = $login_posto or posto isnull)
					AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
			}

			// echo $sql;exit;
			// exit;
			if (pg_num_rows($res) == 0) {
				if ($cook_idioma != "pt-br") {
					$sql_update = "UPDATE tbl_tecnico SET
						tecnico              = '$tecnico',
						nome                 = '$funcionario_nome',
						data_nascimento      = $data_nascimento,
						funcao               = '$funcao',
						cidade               = '$funcionario_cidade',
						bairro               = '$funcionario_bairro',
						endereco             = '$funcionario_endereco',
						telefone             = '$funcionario_telefone',
						celular              = '$funcionario_celular',
						cpf                  = '".preg_replace("/[\.\-\/]/", "",$funcionario_cpf)."',
						rg                   = '".preg_replace("/[\.\-\/]/", "",$funcionario_rg)."',
						email                = '$funcionario_email',
						observacao           = '$observacao',
						data_admissao        = $data_admissao,
						formacao             = '$formacao_academica',
						anos_experiencia     = $anos_experiencia,
						dados_complementares = '$array_dados_complementares'
					WHERE tecnico        = $tecnico";
				} else {

					$sql_update = "UPDATE tbl_tecnico SET
							tecnico              = '$tecnico',
							nome                 = '$funcionario_nome',
							data_nascimento      = $data_nascimento,
							funcao               = '$funcao',
							cep                  = '".preg_replace("/[\.\-\/]/", "",$funcionario_cep)."',
							estado               = '$funcionario_estado',
							cidade               = '$funcionario_cidade',
							bairro               = '$funcionario_bairro',
							endereco             = '$funcionario_endereco',
							numero               = '$funcionario_numero',
							complemento          ='$funcionario_complemento',
							telefone             = '$funcionario_telefone',
							celular              = '$funcionario_celular',
							cpf                  = '".preg_replace("/[\.\-\/]/", "",$funcionario_cpf)."',
							rg                   = '".preg_replace("/[\.\-\/]/", "",$funcionario_rg)."',
							email                = '$funcionario_email',
							observacao           = '$observacao',
							data_admissao        = $data_admissao,
							formacao             = '$formacao_academica',
							inicio_trabalho      = $inicio_trabalho,
							fim_trabalho         = $fim_trabalho,
							anos_experiencia     = $anos_experiencia,
							dados_complementares = '$array_dados_complementares'
						WHERE tecnico        = $tecnico";
				}

				$res_update = pg_query($con,$sql_update);

				if (pg_last_error($con)) {
					$msg_erro["msg"][] = "Erro ao atualizar funcionário.";
					$msg_erro["campos"][] = "update";
				}else{
					header("Location: cadastro_funcionario.php?msg=ok");
				}

			}else{
				if($cook_idioma == "pt-br"){
					$msg_erro["msg"][]    = "Não foi possível inserir o funcionário. Funcionário já existente";
				}else{
					$msg_erro["msg"][]    = "No se pudo insertar el empleado. Empleado actual";
				}
				$msg_erro["campos"][] = "insert";
			}
		} else {
			if($cook_idioma == "pt-br"){
				$sql = "SELECT cpf
					FROM tbl_tecnico
					WHERE cpf = '".preg_replace("/[\.\-\/]/", "",$funcionario_cpf)."'
					AND (posto = $login_posto or posto isnull)
					AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
			}else{
				$sql = "SELECT rg
					FROM tbl_tecnico
					WHERE rg = '".preg_replace("/[\.\-\/]/", "",$funcionario_rg)."'
					AND (posto = $login_posto or posto isnull)
					AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
			}

			if (pg_num_rows($res) == 0) {
				###INSERE NOVO REGISTRO
				if($cook_idioma != "pt-br"){
					$sql = "INSERT INTO tbl_tecnico (
							fabrica,
							posto,
							nome,
							data_nascimento,
							funcao,
							rg,
							cidade,
							endereco,
							telefone,
							celular,
							email,
							observacao,
							data_admissao,
							formacao,
							anos_experiencia,
							dados_complementares
						) VALUES (
							$login_fabrica,
							$login_posto,
							'$funcionario_nome',
							$data_nascimento,
							'$funcao',
							'".preg_replace("/[\.\-\/]/", "",$funcionario_rg)."',
							'$funcionario_cidade',
							'$funcionario_endereco',
							'$funcionario_telefone',
							'$funcionario_celular',
							'$funcionario_email',
							'$observacao',
							$data_admissao,
							'$formacao_academica',
							$anos_experiencia,
							'$array_dados_complementares'
						)";
					//echo nl2br($sql);exit;
				} else {
					$sql = "INSERT INTO tbl_tecnico (
							fabrica,
							posto,
							nome,
							data_nascimento,
							funcao,
							cpf,
							rg,
							cep,
							estado,
							cidade,
							bairro,
							endereco,
							numero,
							complemento,
							telefone,
							celular,
							email,
							observacao,
							data_admissao,
							inicio_trabalho,
							fim_trabalho,
							formacao,
							anos_experiencia,
							dados_complementares
						) VALUES (
							$login_fabrica,
							$login_posto,
							'$funcionario_nome',
							$data_nascimento,
							'$funcao',
							'".preg_replace("/[\.\-\/]/", "",$funcionario_cpf)."',
							'".preg_replace("/[\.\-\/]/", "",$funcionario_rg)."',
							'".preg_replace("/[\.\-\/]/", "",$funcionario_cep)."',
							'$funcionario_estado',
							'$funcionario_cidade',
							'$funcionario_bairro',
							'$funcionario_endereco',
							'$funcionario_numero',
							'$funcionario_complemento',
							'$funcionario_telefone',
							'$funcionario_celular',
							'$funcionario_email',
							'$observacao',
							$data_admissao,
							$inicio_trabalho,
							$fim_trabalho,
							'$formacao_academica',
							$anos_experiencia,
							'$array_dados_complementares'
						)";
 				}

				$res = pg_query($con,$sql);

				if (pg_last_error($con)) {
					$msg_erro["msg"][]    = "Erro ao inserir Funcionário";
					$msg_erro["campos"][] = "insert";
				}else{
					header("Location: cadastro_funcionario.php?msg=ok");
				}
			}else{
				$msg_erro["msg"][] = "Não foi possível inserir o funcionário. Funcionário já existente";
				$msg_erro["campos"][] = "insert";
			}

		}

	}

}

if ($_GET["tecnico"] != "") {
	$sql_ed = "SELECT fabrica,
				posto,
				tecnico,
				nome,
				to_char(data_nascimento, 'DD/MM/YYYY') AS data_nascimento,
				funcao,
				cpf,
				rg,
				cep,
				estado,
				cidade,
				bairro,
				endereco,
				numero,
				complemento,
				telefone,
				celular,
				email,
				observacao,
				to_char(data_admissao, 'DD/MM/YYYY') AS data_admissao,
				formacao,
				anos_experiencia,
				inicio_trabalho,
				fim_trabalho,
				dados_complementares
			FROM tbl_tecnico
			WHERE tecnico = $tecnico";
	$res_ed = pg_query($con,$sql_ed);

	$_RESULT["tecnico"]				= pg_fetch_result($res_ed,0, 'tecnico');
	$_RESULT["funcionario_nome"]   		= pg_fetch_result($res_ed,0, 'nome');
	$_RESULT["data_nascimento"]    		= pg_fetch_result($res_ed,0, 'data_nascimento');
	$_RESULT["funcao"]  				= pg_fetch_result($res_ed,0, 'funcao');
	$_RESULT["funcionario_cep"]			= pg_fetch_result($res_ed,0, 'cep');
	$_RESULT["funcionario_estado"] 		= pg_fetch_result($res_ed,0, 'estado');
	$_RESULT["funcionario_cidade"] 		= pg_fetch_result($res_ed,0, 'cidade');
	$_RESULT["funcionario_bairro"] 		= pg_fetch_result($res_ed,0, 'bairro');
	$_RESULT["funcionario_endereco"]		= pg_fetch_result($res_ed,0, 'endereco');
	$_RESULT["funcionario_numero"] 		= pg_fetch_result($res_ed,0, 'numero');
	$_RESULT["funcionario_complemento"]	= pg_fetch_result($res_ed,0, 'complemento');
	$_RESULT["funcionario_telefone"] 		= pg_fetch_result($res_ed,0, 'telefone');
	$_RESULT["funcionario_celular"]		= pg_fetch_result($res_ed,0, 'celular');
	$_RESULT["funcionario_cpf"]			= pg_fetch_result($res_ed,0, 'cpf');
	$_RESULT["funcionario_rg"]			= pg_fetch_result($res_ed,0, 'rg');
	$_RESULT["funcionario_email"]		= pg_fetch_result($res_ed,0, 'email');
	$_RESULT["observacao"]			= pg_fetch_result($res_ed,0, 'observacao');
	$_RESULT["data_admissao"]			= pg_fetch_result($res_ed,0, 'data_admissao');
	$_RESULT["formacao_academica"]		= pg_fetch_result($res_ed,0, 'formacao');
	$_RESULT["anos_experiencia"]		= pg_fetch_result($res_ed,0, 'anos_experiencia');
	$_RESULT["inicio_trabalho"]			= pg_fetch_result($res_ed,0, 'inicio_trabalho');
	$_RESULT["fim_trabalho"]			= pg_fetch_result($res_ed,0, 'fim_trabalho');
	$dados_complementares			= pg_fetch_result($res_ed,0, 'dados_complementares');
}

$dados_complementares = json_decode($dados_complementares);

foreach ($dados_complementares as $key => $value) {
	switch ($key) {
		case 'whatsapp':
			$numero_whatsapp = $value;
			break;
		case 'cep':
			$funcionario_cep = $value;
			break;
		case 'numero_calcado':
			$numero_calcado = $value;
			break;
		case 'numero_camiseta':
			$numero_camiseta = $value;
			break;
	}
}

// array_funcao
// Estão no include arrays_bosch
include 'admin/array_funcao.php';

#Arquivo com o array para montar o formulário
#include __DIR__."/os_cadastro_unico/fabricas/{$login_fabrica}/form.php";

$layout_menu = "cadastro";

if($cook_idioma == "pt-br"){
	$title = "CADASTRO DE FUNCIONÁRIO";
}else{
	$title = "REGISTRO DE PERSONAL";
}

include __DIR__.'/cabecalho_new.php';

$plugins = array(
	"datepicker",
	"shadowbox",
	"maskedinput",
	"alphanumeric",
	"ajaxform"
);

include __DIR__."/admin/plugin_loader.php"; ?>

<script>

	$(function() {
		/**
		* Carrega o datepicker já com as mascara para os campos
		*/
		//$("#data_nascimento").datepicker({ maxDate: 0, minDate: "-6d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_nascimento").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_admissao").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

		/**
		* datepicker para as horas de início e fim do trabalho
		*/

		$("#inicio_trabalho").mask("99:99");
		$("#fim_trabalho").mask("99:99");

		/**
		* Inicia o shadowbox, obrigatório para a lupa funcionar
		*/
		Shadowbox.init();

		/**
		* Configurações do Alphanumeric
		*/
		$(".numeric").numeric();
		$("#funcionario_telefone, #funcionario_celular").numeric({ allow: "()- " });

		/**
		* Evento que chama a função de lupa para a lupa clicada
		*/
		$("span[rel=lupa]").click(function() {
			$.lupa($(this));
		});

		$("#data_admissao").change(function () {
			var data_admissao = $("#data_admissao").val();
			var tecnico = $("#tecnico").val();
			var posto = <?= $login_posto; ?>;
			$.ajax({
				async: false,
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				dataType: "JSON",
				data: { calcula_exp: "calcular_exp",
					data_admissao: data_admissao,
					tecnico: tecnico,
					posto: posto
				},
				complete: function (data) {
					var retorno = $.parseJSON(data.responseText);
					if(retorno.status == "ok") {
						$("#anos_experiencia").val(retorno.experiencia_total);
					}
				}
			});
		});

		/**
		* Mascaras
		*/
		<? if ($cook_idioma == "pt-br") { ?>
			$("#funcionario_cep").mask("99999-999");
		<? }
		if(strlen(getValue('funcionario_cpf')) > 0){
			if(strlen(getValue('funcionario_cpf')) > 14){ ?>
				$("#funcionario_cpf").mask("99.999.999/9999-99");
				$("label[for=funcionario_cpf]").html("CNPJ");
			<? } else { ?>
				$("#funcionario_cpf").mask("999.999.999-99");
				<? if ($cook_idioma == "pt-br") { ?>
					$("label[for=funcionario_cpf]").html("CPF");
				<? } else { ?>
					$("label[for=funcionario_cpf]").html("Numero de Identificacion");
				<? }
			}
		} ?>

		/**
		* Evento de keypress do campo funcionario_cpf
		* Irá verificar o tamanho do campo, se o tamanho já for 14(CPF) irá alterar a máscara para CNPJ e alterar o Label
		*/
		$("#funcionario_cpf").blur(function(){
			var tamanho = $(this).val().replace(/\D/g, '');

			$("#funcionario_cpf").mask("999.999.999-99");
			<? if ($cook_idioma == "pt-br") { ?>
				$("label[for=funcionario_cpf]").html("CPF");
			<? } else { ?>
				$("label[for=funcionario_cpf]").html("Numero de Identificacion");
			<? } ?>
		});

		<?php
			if($login_fabrica == 20){
				?>
				$("#numero_calcado").keyup(function(){
					$("#numero_calcado").val($(this).val().replace(/\D/g, ""));

				});
				<?php
			}
		?>

		$("#funcionario_cpf").focus(function(){
			$(this).unmask();
		});


		/**
		* Evento para quando alterar o estado carregar as cidades do estado
		*/
		$("select[id$=_estado]").change(function() {
			busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "funcionario");
		});

		/**
		* Evento para buscar o endereço do cep digitado
		*/
		<? if ($cook_idioma == "pt-br") { ?>
			$("input[id$=_cep]").blur(function() {
				if ($(this).attr("readonly") == undefined) {
					busca_cep($(this).val(), ($(this).attr("id") == "revenda_cep") ? "revenda" : "funcionario");
				}
			});
		<? } ?>
	});

	/**
	 * Função para retirar a acentuação
	 */
	function retiraAcentos(palavra){
		var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
		var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
		var newPalavra = "";

		for(i = 0; i < palavra.length; i++) {
			if (com_acento.search(palavra.substr(i, 1)) >= 0) {
				newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
			} else {
				newPalavra += palavra.substr(i, 1);
			}
		}

		return newPalavra.toUpperCase();
	}

	/**
	 * Função que busca as cidades do estado e popula o select cidade
	 */
	function busca_cidade(estado, funcionario_revenda, cidade) {
		$("#"+funcionario_revenda+"_cidade").find("option").first().nextAll().remove();

		if (estado.length > 0) {
			$.ajax({
				async: false,
				url: "cadastro_os.php",
				type: "POST",
				data: { ajax_busca_cidade: true, estado: estado },
				beforeSend: function() {
					if ($("#"+funcionario_revenda+"_cidade").next("img").length == 0) {
						$("#"+funcionario_revenda+"_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
					}
				},
				complete: function(data) {
					data = $.parseJSON(data.responseText);

					if (data.error) {
						alert(data.error);
					} else {
						$.each(data.cidades, function(key, value) {
							var option = $("<option></option>", { value: value, text: value});

							$("#"+funcionario_revenda+"_cidade").append(option);
						});
					}


					$("#"+funcionario_revenda+"_cidade").show().next().remove();
				}
			});
		}

		if(typeof cidade != "undefined" && cidade.length > 0){
			$('#funcionario_cidade option[value='+cidade+']').attr('selected','selected');
		}

	}

	/**
	 * Função que faz um ajax para buscar o cep nos correios
	 */
	<? if ($cook_idioma == "pt-br") { ?>
		function busca_cep(cep, consumidor_revenda, method) {
			if (cep.length > 0) {
				var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });
				if (typeof method == "undefined" || method.length == 0) {
					method = "webservice";
					$.ajaxSetup({
						timeout: 3000
					});
				} else {
					$.ajaxSetup({
						timeout: 5000
					});
				}

				$.ajax({
					async: true,
					url: "ajax_cep.php",
					type: "GET",
					data: { cep: cep, method: method },
					beforeSend: function() {
						$("#"+consumidor_revenda+"_estado").next("img").remove();
						$("#"+consumidor_revenda+"_cidade").next("img").remove();
						$("#"+consumidor_revenda+"_bairro").next("img").remove();
						$("#"+consumidor_revenda+"_endereco").next("img").remove();

						$("#"+consumidor_revenda+"_estado").hide().after(img.clone());
						$("#"+consumidor_revenda+"_cidade").hide().after(img.clone());
						$("#"+consumidor_revenda+"_bairro").hide().after(img.clone());
						$("#"+consumidor_revenda+"_endereco").hide().after(img.clone());
					},
					error: function(xhr, status, error) {
						busca_cep(cep, consumidor_revenda, "database");
					},
					success: function(data) {
						results = data.split(";");

						if (results[0] != "ok") {
							alert(results[0]);
							$("#"+consumidor_revenda+"_cidade").show().next().remove();
						} else {
							$("#"+consumidor_revenda+"_estado").val(results[4]);
							busca_cidade(results[4], consumidor_revenda);
							$("#"+consumidor_revenda+"_cidade").val(retiraAcentos(results[3]).toUpperCase());
							if (results[2].length > 0) {
								$("#"+consumidor_revenda+"_bairro").val(results[2]);
							}
							if (results[1].length > 0) {
								$("#"+consumidor_revenda+"_endereco").val(results[1]);
							}
						}

						$("#"+consumidor_revenda+"_estado").show().next().remove();
						$("#"+consumidor_revenda+"_bairro").show().next().remove();
						$("#"+consumidor_revenda+"_endereco").show().next().remove();

						if ($("#"+consumidor_revenda+"_bairro").val().length == 0) {
							$("#"+consumidor_revenda+"_bairro").focus();
						} else if ($("#"+consumidor_revenda+"_endereco").val().length == 0) {
							$("#"+consumidor_revenda+"_endereco").focus();
						} else if ($("#"+consumidor_revenda+"_numero").val().length == 0) {
							$("#"+consumidor_revenda+"_numero").focus();
						}

						$.ajaxSetup({
							timeout: 0
						});
					}
				});
			}
		}
	<? } ?>

	//Ecluir
	function exclui_funcionario(id_funcionario){
		$.ajax({
			url : "cadastro_funcionario_ajax.php",
			type: "POST",
			data: { tecnico : id_funcionario },
			dataType:"json"
		})
		.done(function(data){
			if (data['result'] == 'false') {
					var that = $('#'+id_funcionario).find('input.btn-success');
					$(that).removeClass("btn btn-small btn-success").addClass("btn btn-small btn-danger");
					$(that).attr({ "value": "Ativar", "alt": "Ativar", "title": "Ativar" });
					$(that).text("Ativar");
					//$(that).parents("tr").find("font").attr("color","#336633").text("Ativo");

					<?php if($cook_idioma == "pt-br"){?>
						alert('Funcionário inativado com sucesso.');
					<?php }else{?>
						alert('Con éxito inactivado empleado..');
					<?php } ?>
					window.location.reload();
				}
				if (data['result'] == 'true') {
					var that = $('#'+id_funcionario).find('input.btn-danger');
					$(that).removeClass("btn btn-small btn-danger").addClass("btn btn-small btn-success");
					$(that).attr({ "value": "Excluir", "alt": "Excluir", "title": "Excluir" });
					$(that).text("Excluir");
					//window.location.reload();
					//$(that).parents("tr").find("font").attr("color","#CC0033").text("Ativo");

					<?php if($cook_idioma == "pt-br"){?>
						alert('Funcionário ativado com sucesso.');
					<?php }else{?>
						alert('Con éxito habilitado empleado.');
					<?php } ?>
					window.location.reload();
				}
		});
	}

	// <input type='button' value='Ativar' id='incluir' alt='Ativar' title='Ativar' class='btn btn-small btn-success'>
	// <input type='button' value='Excluir' id='excluir' alt='Excluir' title='Excluir' class='btn btn-small btn-danger'>
	/**
	* Função de retorna todos os dados do Posto
	*/
	function busca_treinamentos(treinamentos){

		$.ajax({
			url : "<?php echo $_SERVER['PHP_SELF']; ?>",
			type: "POST",
			data: { ajax_treinamentos : treinamentos },
			complete: function(data){
				var arr_treinamento = new Array();
				var dados = data.responseText;

				arr_treinamento = dados.split("|");

				$("#treinamento").val(arr_posto[0]);
				$("#titulo").val(arr_posto[1]);
				$("#data_inicio").val(arr_posto[2]);
				$("#data_fim").val(arr_posto[3]);
				$("#vagas").val(arr_posto[5]);
				$("#linha").val(arr_posto[6]);
				$("#familia").val(arr_posto[7]);
				$("#admin").val(arr_posto[8]);
				$("#ativo").val(arr_posto[9]);
				$("#consumidor_email").val(arr_posto[10]);

				busca_cidade(arr_posto[3], "consumidor", arr_posto[4]);

			}
		});

	}

	function pesquisaTreinamento(tecnico){
	        Shadowbox.open({
	            content:    "cadastro_funcionario_treinamento.php?tecnico="+tecnico,
	            player: "iframe",
	            width:  800,
	            height: 500
	        });
	}
</script>

<? if (count($msg_erro["msg"]) > 0) { ?>
	<br />
	<div class="alert alert-error">
		<h4><?= implode("<br />", $msg_erro["msg"]); ?></h4>
	</div>
<? }
if (strlen($_GET['msg']) > 0) {
	if($cook_idioma == "pt-br"){
		$msg = "Funcionário cadastrado/atualizado com sucesso";
	}else{
		$msg = "Empleado registrado/actualizado correctamente";
	} ?>
	<br />
	<div class="alert alert-success">
		<h4><?= $msg;?></h4>
	</div>
<? } ?>

<br />
<div class="row"> <b class="obrigatorio pull-right"> <?= ($cook_idioma == "pt-br") ? "* Campos obrigatórios" : "* Campos requeridos"; ?></b></div>

<form name="frm_os" method="POST" class="form-search form-inline" enctype="multipart/form-data">
<div id="div_informacoes_funcionario" class="tc_formulario">
	<input type="hidden" id="tecnico" name="tecnico" value="<?=getValue('tecnico')?>"/>
	<div class="titulo_tabela">
		<? if($cook_idioma == "pt-br"){
			echo "Informações do Funcionário";
		}else{
			echo "Información del empleado";
		} ?>
	</div>
	<br />

	<? if ($cook_idioma == "pt-br") { ?>
		<!-- Nome / Data Nascimento / Data Admissão / Função -->
		<div class="row-fluid">
			<div class="span1"></div>
			<!-- Nome -->
			<div class="span4">
				<div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>' >
					<label class="control-label" for="funcionario_nome">Nome</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="funcionario_nome" name="funcionario_nome" class="span12" type="text" value="<?=getValue('funcionario_nome')?>" maxlength="100" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Nome -->

			<!-- Data Nascimento -->
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_nascimento">Data Nascimento</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_nascimento" name="data_nascimento" class="span12" type="text" value="<?=getValue('data_nascimento')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Data Nascimento -->

			<!-- Data Admissão -->
			<div class="span2">
				<div class='control-group <?=(in_array("data_admissao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="data_admissao">Data Admissão</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_admissao" name="data_admissao" class="span12" type="text" value="<?=getValue('data_admissao')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Data Admissão -->

			<!-- Função -->
			<div class="span2">
				<div class='control-group <?=(in_array("funcao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="funcao">Função</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<select id="funcao" name="funcao" class="span12">
								<option value="">Selecione</option>
								<?php
									#O $array_funcao
									foreach ($array_funcao as $sigla => $nome_funcao) {
										$selected = ($sigla == getValue('funcao')) ? "selected" : "";

										echo "<option value='{$sigla}' {$selected} >{$nome_funcao}</option>";
									}
									?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Função -->
			<div class="span1"></div>
		</div>
		<!-- //// -->

		<!-- CPF / RG / Anos Experiência / Formação Acadêmica -->
		<div class="row-fluid">
			<div class="span1"></div>
			<!-- CPF -->
			<div class="span2">
				<div class='control-group <?=(in_array('cpf', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_cpf">CPF</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="funcionario_cpf" name="funcionario_cpf" class="span12 numeric" type="text" value="<?=getValue('funcionario_cpf')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim CPF -->
			<!-- RG -->
			<div class="span2">
				<div class='control-group <?=(in_array('rg', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_rg">RG</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="funcionario_rg" name="funcionario_rg" class="span12" type="text" value="<?=getValue('funcionario_rg')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim RG -->

			<!-- Anos Experiência -->
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="anos_experiencia">Anos Experiência</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="anos_experiencia" name="anos_experiencia" class="span12 numeric" type="text" readonly value="<?=getValue('anos_experiencia')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Anos Experiencia -->

			<!-- Formação Acadêmica -->
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="formacao_academica">Formação Acadêmica</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="formacao_academica" name="formacao_academica" class="span12" type="text" value="<?=getValue('formacao_academica')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Formação Acadêmica -->
			<div class="span1"></div>
		</div>
		<!-- //// -->

		<!-- CEP / Estado / Cidade / Bairro -->
		<div class="row-fluid">
			<div class="span1"></div>

			<!-- CEP -->
			<div class="span2">
				<div class='control-group <?=(in_array('cep', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_cep">CEP</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="funcionario_cep" name="funcionario_cep" class="span12" type="text" value="<?=getValue('funcionario_cep')?>"/>
						</div>
					</div>
				</div>
			</div>
			<!-- Fim CEP -->

			<!-- Estado -->
			<div class="span2">
				<div class="control-group <?=(in_array('estado', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="funcionario_estado">Estado</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
								<select id="funcionario_estado" name="funcionario_estado" class="span12">
									<option value="" >Selecione</option>
									<?php
									#O $array_estados está no arquivo funcoes.php
									foreach ($array_estados() as $sigla => $nome_estado) {
										$selected = ($sigla == getValue('funcionario_estado')) ? "selected" : "";

										echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
									}
									?>
								</select>
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Estado -->

			<!-- Cidade -->
			<div class="span3">
				<div class="control-group <?=(in_array('cidade', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="funcionario_cidade">Cidade</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<select id="funcionario_cidade" name="funcionario_cidade" class="span12">
								<option value="" >Selecione</option>
								<?php
									if (strlen(getValue("funcionario_estado")) > 0) {
										$sql = "SELECT DISTINCT * FROM (
												SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".getValue("funcionario_estado")."')
													UNION (
														SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".getValue("funcionario_estado")."')
													)
												) AS cidade
												ORDER BY cidade ASC";
										$res = pg_query($con, $sql);

										if (pg_num_rows($res) > 0) {
											while ($result = pg_fetch_object($res)) {
												$selected  = (trim($result->cidade) == trim(getValue("funcionario_cidade"))) ? "SELECTED" : "";

												echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
											}
										}
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Cidade -->

			<!-- Bairro -->
			<div class="span3">
				<div class='control-group <?=(in_array('bairro', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_bairro">Bairro</label>
					<div class="controls controls-row">
						<div class="span12">
						<h5 class='asteristico'>*</h5>
							<input id="funcionario_bairro" name="funcionario_bairro" class="span12" type="text" maxlength="80" value="<?=getValue('funcionario_bairro')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Bairro -->
			<div class="span1"></div>
		</div>
		<!-- //// -->

		<!-- Endereço / Numero / Complemento / Telefone Fixo -->
		<div class="row-fluid">
			<div class="span1"></div>

			<!-- Endereço -->
			<div class="span3">
				<div class='control-group <?=(in_array('endereco', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_endereco">Endereço</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="funcionario_endereco" name="funcionario_endereco" class="span12" type="text" value="<?=getValue('funcionario_endereco')?>" maxlength="80" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Endereço -->

			<!-- Numero -->
			<div class="span2">
				<div class='control-group <?=(in_array('numero', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_numero">Número</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="funcionario_numero" name="funcionario_numero" class="span12" type="text" value="<?=getValue('funcionario_numero')?>" maxlength="10" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Numero -->

			<!-- Complemento -->
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="funcionario_complemento">Complemento</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_complemento" name="funcionario_complemento" class="span12" type="text" value="<?=getValue('funcionario_complemento')?>" maxlength="40" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Complemento -->
			<!-- Telefone Fixo -->
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="funcionario_telefone">Telefone Fixo</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_telefone" class="span12" type="text" value="<?=getValue('funcionario_telefone')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Telefone Fixo -->
			<div class="span1"></div>
		</div>
		<!-- //// -->

		<!-- Telefone Celular / Whatsapp / Email -->
		<div class="row-fluid">
			<div class="span1"></div>

			<!-- Telefone Celular -->
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="funcionario_celular">Telefone Celular</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_celular" class="span12" type="text" value="<?=getValue('funcionario_celular')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Telefone Celular -->

			<!-- Whatsapp -->
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="numero_whatsapp">Numero Whatsapp</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="numero_whatsapp" name="numero_whatsapp" class="span12" type="text" value="<?= $numero_whatsapp ?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Whatsapp -->

			<!-- Email -->
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="funcionario_email">Email</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_email" name="funcionario_email" class="span12" type="text" value="<?=getValue('funcionario_email')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Email -->
			<div class="span1"></div>
		</div>
		<!-- //// -->

		<?php
		if($login_fabrica == 20){
			?>
			<!-- Número do Calçado / Número da Camiseta  -->
			<div class="row-fluid">
				<div class="span1"></div>

				<!-- Nº Calçado -->
				<div class="span2">
					<div class='control-group'>
						<label class="control-label" for="numero_calcado">Nº Calçado</label>
						<div class="controls controls-row">
							<div class="span12">
								<input id="numero_calcado" name="numero_calcado" class="span12" maxlength="2" type="text" value="<?=$numero_calcado?>"/>
							</div>
						</div>
					</div>
				</div>
				<!-- Fim Nº Calçado -->

				<!-- Nº Camiseta -->
				<div class="span2">
					<div class='control-group'>
						<label class="control-label" for="numero_camiseta">Nº Camiseta</label>
						<div class="controls controls-row">
							<div class="span12">
								<input id="numero_camiseta" name="numero_camiseta" class="span12" maxlength="2" type="text" value="<?=$numero_camiseta?>"/>
							</div>
						</div>
					</div>
				</div>
				<!-- Fim Nº Camiseta -->
			</div>
			<!-- //// -->
			<?php
		}
		?>

		<!-- Período de Trabalho -->
		<? if (in_array($login_fabrica, array(158))) { ?>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span2">
					<div class='control-group <?=(in_array('inicio_trabalho', $msg_erro['campos'])) ? "error" : "" ?>'>
						<label class="control-label" for="inicio_trabalho">Início do Trabalho</label>
						<div class="controls controls-row">
							<div class="span8">
								<h5 class='asteristico'>*</h5>
								<input id="inicio_trabalho" name="inicio_trabalho" class="span12" type="text" value="<?= getValue('inicio_trabalho'); ?>" />
							</div>
						</div>
					</div>
				</div>
				<div class="span2">
					<div class='control-group <?=(in_array('fim_trabalho', $msg_erro['campos'])) ? "error" : "" ?>'>
						<label class="control-label" for="fim_trabalho">Fim do Trabalho</label>
						<div class="controls controls-row">
							<div class="span8">
								<h5 class='asteristico'>*</h5>
								<input id="fim_trabalho" name="fim_trabalho" class="span12" type="text" value="<?= getValue('fim_trabalho'); ?>" />
							</div>
						</div>
					</div>
				</div>
				<div class="span1"></div>
			</div>
		<? } ?>
		<!-- //// -->

		<!-- Observações -->
		<div class="titulo_tabela">Observações</div>
		<br />
		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span10">
				<div class='control-group' >
					<div class="controls controls-row">
						<div class="span12">
							<textarea id="observacao" name="observacao" class="span12" style="height: 50px;" maxlength="100"><?=getValue("observacao")?></textarea>
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<!-- Fim Observações -->
	<? } else { ?>
		<!-- FORM ESPANHOL -->

		<!-- Nome / Data Nascimento / Data Admissão / Função -->
		<div class="row-fluid">
			<div class="span1"></div>
			<!-- Nome -->
			<div class="span4">
				<div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>' >
					<label class="control-label" for="funcionario_nome">Nombre</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="funcionario_nome" name="funcionario_nome" class="span12" type="text" value="<?=getValue('funcionario_nome')?>" maxlength="100" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Nome -->

			<!-- Data Nascimento -->
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_nascimento">Fecha Nascimiento</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_nascimento" name="data_nascimento" class="span12" type="text" value="<?=getValue('data_nascimento')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Data Nascimento -->

			<!-- Data Admissão -->
			<div class="span2">
				<div class='control-group <?=(in_array("data_admissao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="data_admissao">Fecha Admisión</label>
					<div class="controls controls-row">
						<div class="span12"><h5 class='asteristico'>*</h5>
							<input id="data_admissao" name="data_admissao" class="span12" type="text" value="<?=getValue('data_admissao')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Data Admissão -->

			<!-- Função -->
			<div class="span2">
				<div class='control-group <?=(in_array("funcao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="funcao">Funcción</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<select id="funcao" name="funcao" class="span12">
								<option value="">Selecione</option>
								<?php
									#O $array_funcao
									foreach ($array_funcao as $sigla => $nome_funcao) {
										$selected = ($sigla == getValue('funcao')) ? "selected" : "";

										echo "<option value='{$sigla}' {$selected} >{$nome_funcao}</option>";
									}
									?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Função -->
			<div class="span1"></div>
		</div>
		<!-- //// -->

		<!-- CPF / RG / Anos Experiência / Formação Acadêmica -->
		<div class="row-fluid">
			<div class="span1"></div>
			<!-- CPF -->
			<div class="span4">
				<div class='control-group <?=(in_array('rg', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_rg">Numero de Identificacion</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="funcionario_rg" name="funcionario_rg" class="span12" type="text" value="<?=getValue('funcionario_rg')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim CPF -->

			<!-- Anos Experiência -->
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="anos_experiencia">Años de Experiencia</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="anos_experiencia" name="anos_experiencia" class="span12 numeric" type="text" readonly value="<?=getValue('anos_experiencia')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Anos Experiencia -->

			<!-- Formação Acadêmica -->
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="formacao_academica">Formación Academica</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="formacao_academica" name="formacao_academica" class="span12" type="text" value="<?=getValue('formacao_academica')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Formação Acadêmica -->
			<div class="span1"></div>
		</div>
		<!-- //// -->

		<!-- CEP / Estado / Cidade / Bairro -->
		<div class="row-fluid">
			<div class="span1"></div>

			<!-- Codigo Postal -->
			<div class="span2">
				<div class='control-group <?=(in_array('cep', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_cep">Código Postal</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_cep" name="funcionario_cep" class="span12" type="text" value="<?=$funcionario_cep?>"/>
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Codigo Postal -->

			<!-- Direction -->
			<div class="span4">
				<div class='control-group <?=(in_array('endereco', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_endereco">Dirección</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="funcionario_endereco" name="funcionario_endereco" class="span12" type="text" value="<?=getValue('funcionario_endereco')?>" maxlength="80" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Direction -->


			<!-- Cidade -->
			<div class="span4">
				<div class="control-group <?=(in_array('cidade', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="funcionario_cidade">Ciudad</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="funcionario_cidade" name="funcionario_cidade" class="span12" type="text" maxlength="40" value="<?=getValue('funcionario_cidade')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Cidade -->

			<div class="span1"></div>
		</div>
		<!-- //// -->

		<div class="row-fluid">
			<div class="span1"></div>
			<!-- Bairro -->
			<div class="span4">
				<div class='control-group <?=(in_array('bairro', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="funcionario_bairro">Provincia/Departamento</label>
					<div class="controls controls-row">
						<div class="span12">
						<h5 class='asteristico'>*</h5>
							<input id="funcionario_bairro" name="funcionario_bairro" class="span12" type="text" maxlength="80" value="<?=getValue('funcionario_bairro')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Bairro -->

			<!-- Telefone Fixo -->
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="funcionario_telefone">Telefono Fijo</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_telefone" class="span12" type="text" value="<?=getValue('funcionario_telefone')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Telefone Fixo -->

			<!-- Telefone Celular -->
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="funcionario_celular">Telefono Movil</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_telefone" name="funcionario_celular" class="span12" type="text" value="<?=getValue('funcionario_celular')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Telefone Celular -->
			<div class="span1"></div>
		</div>

		<div class="row-fluid">
			<div class="span1"></div>
			<!-- Whatsapp -->
			<div class="span2">
				<div class="control-group">
					<label class="control-label" for="numero_whatsapp">Numero de Whatsaap</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="numero_whatsapp" name="numero_whatsapp" class="span12" type="text" value="<?= $numero_whatsapp ?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Whatsapp -->

			<!-- Email -->
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="funcionario_email">Correo Electronico</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="funcionario_email" name="funcionario_email" class="span12" type="text" value="<?=getValue('funcionario_email')?>" />
						</div>
					</div>
				</div>
			</div>
			<!-- Fim Email -->

			<?php
			if($login_fabrica == 20){
				?>
				<!-- Nº Calçado -->
				<div class="span2">
					<div class='control-group'>
						<label class="control-label" for="numero_calcado">Numero del calzado</label>
						<div class="controls controls-row">
							<div class="span12">
								<input id="numero_calcado" name="numero_calcado" class="span12" maxlength="2" type="text" value="<?=$numero_calcado?>"/>
							</div>
						</div>
					</div>
				</div>
				<!-- Fim Nº Calçado -->

				<!-- Nº Camiseta -->
				<div class="span2">
					<div class='control-group'>
						<label class="control-label" for="numero_camiseta">Tamaño del camiseta</label>
						<div class="controls controls-row">
							<div class="span12">
								<input id="numero_camiseta" name="numero_camiseta" class="span12" maxlength="2" type="text" value="<?=$numero_camiseta?>"/>
							</div>
						</div>
					</div>
				</div>
				<!-- Fim Nº Camiseta -->
				<?php
			}
			?>
			<div class="span1"></div>
		</div>
		<?php
	}
	?>
	<br />
	<p class="tac">
		<? $botao = ($cook_idioma == "pt-br") ? "Gravar" : "Registrar"; ?>
		<input type="submit" class="btn" name="gravar" value="<?= $botao; ?>" />
	</p>
	<br />
</div>
<br />
</form>
</div>
<br />

<!-- Tabela -->
<?
//Lista todos os Funcionários cadastrados do posto
	$sql_func = "SELECT tecnico,
				nome,
				cpf,
				funcao,
				telefone,
				celular,
				ativo,
				anos_experiencia,
				formacao,
				dados_complementares,
				(current_date - data_admissao) / 365 AS exp_total
			FROM tbl_tecnico
			WHERE fabrica = $login_fabrica
			AND posto = $login_posto
			ORDER BY ativo DESC";

	$res_func = pg_query($con,$sql_func);

if(pg_num_rows($res_func) > 0){ ?>
<form name="frm_tab" method="GET" class="form-search form-inline" enctype="multipart/form-data" >
	<table class='table table-striped table-bordered table-hover table-fixed'>
	<? if ($cook_idioma == "pt-br") { ?>
		<thead>
		<tr class='titulo_coluna'>
		<td>Nome</td>
		<td>CPF</td>
		<td>Função</td>
		<td>Experiência</td>
		<td>Formação Acadêmica</td>
		<td>Nº Calçado</td>
		<td>Nº Camiseta</td>
		<td>Telefone Fixo</td>
		<td>Telefone Celular</td>
		<td>Numero Whatsapp</td>
		<td class="tac">Ações</td>
		</tr>
		</thead>
		<tbody>
	<? } else { ?>
		<thead>
		<tr class='titulo_coluna'>
		<td>Nombre</td>
		<td>Numero de Identificacion</td>
		<td>Funcción</td>
		<td>Años de Experiencia</td>
		<td>Formación Academica</td>
		<td>Numero del calzado</td>
		<td>Tamaño del camiseta</td>
		<td>Telefono Fijo</td>
		<td>Telefono Movil</td>
		<td>Numero de Whatsaap</td>
		<td class="tac">Acciones</td>
		</tr>
		</thead>
		<tbody>
	<? }
	for ($i = 0; $i < pg_num_rows($res_func); $i++) {
		$tecnico              = pg_fetch_result($res_func, $i, 'tecnico');
		$nome                 = pg_fetch_result($res_func, $i, 'nome');
		$cpf                  = pg_fetch_result($res_func, $i, 'cpf');
		$funcao               = pg_fetch_result($res_func, $i, 'funcao');
		$telefone             = pg_fetch_result($res_func, $i, 'telefone');
		$celular              = pg_fetch_result($res_func, $i, 'celular');
		$ativo                = pg_fetch_result($res_func, $i, 'ativo');
		$formacao             = pg_fetch_result($res_func, $i, 'formacao');
		$experiencia          = pg_fetch_result($res_func, $i, 'anos_experiencia');
		$dados_complementares = pg_fetch_result($res_func, $i, 'dados_complementares');
		$experiencia_total    = pg_fetch_result($res_func, $i, 'exp_total');
		$dados_complementares = json_decode($dados_complementares);

		$whatsapp        = "";
		$numero_calcado  = "";
		$numero_camiseta = "";

		foreach ($dados_complementares as $key => $value) {
			switch ($key) {
				case 'whatsapp':
					$whatsapp = $value;
					break;
				case 'numero_calcado':
					$numero_calcado = $value;
					break;
				case 'numero_camiseta':
					$numero_camiseta = $value;
					break;
			}
		}

		if(!empty($experiencia)){
			if($experiencia != "1"){
				$experiencia = $experiencia.' Anos';
			}else{
				$experiencia = $experiencia.' Ano';
			}
		}
		if($experiencia_total > $experiencia){
			$sqlExp = "UPDATE tbl_tecnico SET anos_experiencia = $experiencia_total WHERE tecnico = $tecnico AND fabrica = 20";
			$resExp = pg_query($con, $sqlExp);
		} ?>
		<tr id="<?= $tecnico?>">
		<td><?echo $nome?></td>
		<td><?echo $cpf?></td>
		<?if ($funcao === "T" ) {
			echo "<td>Técnico</td>";
		}elseif ($funcao === "A") {
			echo "<td>Administrativo</td>";
		}elseif ($funcao === "G") {
			echo "<td>Gerente AT</td>";
		}elseif ($funcao === "P") {
			echo "<td>Proprietário</td>";
		}elseif	($funcao ==="AB") {
			echo "<td>Atendente Balcao</td>";
		}else{
			echo "<td></td>";
		} ?>
		<td class="tac"><?php echo $experiencia; ?></td>
		<td><?php echo $formacao; ?></td>
		<td><?=$numero_calcado;?></td>
		<td><?=$numero_camiseta;?></td>
		<td><?echo $telefone?></td>
		<td><?echo $celular?></td>
		<td><?php echo $whatsapp; ?></td>
		<td nowrap>

			<?php
				if($cook_idioma == "pt-br"){
			?>
				<a href="javascript: if (confirm ('Deseja alterar o funcionário <?=$nome?> ?') == true) { window.location='<?=$PHP_SELF?>?tecnico=<?=$tecnico?>' }">
					<input type='button' value='Editar' alt='Editar' title='Editar' class='btn btn-small '>
				</a>
			<?php
				}else{
			?>
				<a href="javascript: if (confirm ('Quieres cambiar el empleado <?=$nome?> ?') == true) { window.location='<?=$PHP_SELF?>?tecnico=<?=$tecnico?>' }">
					<input type='button' value='Editar' alt='Editar' title='Editar' class='btn btn-small '>
				</a>
			<?php
				}
			?>

			<!-- Alteração chamado 3301309 !-->
			<?if ($funcao === "T" && !in_array($login_fabrica, array(20, 158))) {
				if($cook_idioma == "pt-br"){
			?>
				<a href="javascript: pesquisaTreinamento('<?php echo $tecnico?>')">
				<input type='button' value='Treinamentos' alt='Treinamentos' title='Treinamentos' class='btn btn-small btn-warning'>
				</a>
			<?
				}
			}else if($login_fabrica==20){
				if($cook_idioma == "pt-br"){
			?>
				<a href="javascript: pesquisaTreinamento('<?php echo $tecnico?>')">
				<input type='button' value='Treinamentos' alt='Treinamentos' title='Treinamentos' class='btn btn-small btn-warning'>
				</a>
			<?
				}
			}
			if ($ativo === 't') {
				if($cook_idioma == "pt-br"){
			?>
				<a href="javascript: if (confirm ('Deseja inativar o funcionário <?=$nome?> ?') == true) { exclui_funcionario('<?php echo $tecnico?>') }">
				<input type='button' value='Inativar' id='inativar' alt='Inativar' title='Inativar' class='btn btn-small btn-danger'>
				</a>
			<?
				}else{
			?>
				<a href="javascript: if (confirm ('Usted quiere inactivar el empleado <?=$nome?> ?') == true) { exclui_funcionario('<?php echo $tecnico?>') }">
				<input type='button' value='Inactivar' id='inactivar' alt='Inactivar' title='Inactivar' class='btn btn-small btn-danger'>
				</a>
			<?php
				}
			}else {
				if($cook_idioma == "pt-br"){
			?>
				<a href="javascript: if (confirm ('Deseja ativar o funcionário <?=$nome?> ?') == true) { exclui_funcionario('<?php echo $tecnico?>') }">
				<input type='button' value='Ativar' id='incluir' alt='Ativar' title='Ativar' class='btn btn-small btn-success'>
				</a>
			<?
				}else{
			?>
				<a href="javascript: if (confirm ('Quieres permitir que los empleados <?=$nome?> ?') == true) { exclui_funcionario('<?php echo $tecnico?>') }">
				<input type='button' value='Activar' id='incluir' alt='Activar' title='Activar' class='btn btn-small btn-success'>
				</a>
			<?php
				}
			}?>

		</td>
		</tr>
	<?
	}
	?>
		</tbody>
	</table>
</form>
<br />
<?
}
?>



<?php

include "rodape.php";

?>
