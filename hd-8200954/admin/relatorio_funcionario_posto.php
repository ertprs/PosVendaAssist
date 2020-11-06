<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$visual_black = "manutencao-admin";

$layout_menu = "gerencia";
$title = "RELATÓRIO FUNCIONÁRIO POSTO";


// array_funcao
// Estão no include array
include 'array_funcao.php';


$array_pais = array( "AR" => "Argentina",
						"BO" => "Bolívia",
						"BR" => "Brasil",
						"CL" => "Chile",
						"CO" => "Colômbia",
						"CR" => "Costa Rica",
						"EC" => "Equador",
						"GT" => "Guatemala",
						"HN" => "Honduras",
						"MX" => "México",
						"NI" => "Nicarágua",
						"PA" => "Panamá",
						"PE" => "Peru",
						"PY" => "Paraguai",
						"SV" => "El Salvador",
						"UY" => "Uruguai",
						"VE" => "Venezuela"
);
// echo "<pre>";
// print_r($_POST);
// echo "</pre>";

#POST - Listar
if ($_POST["listar"] == "Listar") {
	$posto_codigo   = $_POST['posto_codigo'];
	$posto_id    	= $_POST['posto_id'];
	$posto_nome  	= $_POST['posto_nome'];
	$funcao			= $_POST['funcao'];

	if($login_fabrica == 20){
		if($login_pais == "BR"){
			$pais = $_POST['pais'];
		}
	}
// Validação Campos Obrigatórios

	if (!strlen($posto_id) and (strlen($posto_codigo) or strlen($posto_nome))) {

		//$msg_erro["msg"][] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "codigo";
        $msg_erro["campos"][] = "nome";

	}

	if (count($msg_erro["campos"]) > 0) {
		$msg_erro["msg"][] = "Favor realizar a pesquisa na lupa.";
	}else{
		//Lista todos os Funcionários cadastrados do posto
		if ($funcao <> '' or $funcao <> null) {
			$sql_funcao = " AND tbl_tecnico.funcao = '".$funcao."'";
		}else{
			$sql_funcao = '';
		}

		//filtra por posto
		//Lista todos os Funcionários cadastrados do posto
		if ($posto_id <> '' or $posto_id <> null) {
			$sql_posto = " AND tbl_tecnico.posto = $posto_id";
		}else{
			$sql_posto = '';
		}

		if($login_fabrica == 20 AND $login_pais == "BR"){
			$JOIN = "JOIN tbl_posto ON tbl_tecnico.posto = tbl_posto.posto";
			if($pais <> '' or $pais <> null){
				$cond_pais = "AND tbl_posto.pais = '$pais'";
			}
		}


		$sql_func = "SELECT tbl_tecnico.tecnico,
					tbl_tecnico.posto,
					tbl_tecnico.fabrica,
					tbl_tecnico.nome,
					tbl_tecnico.cpf,
					tbl_tecnico.rg,
					tbl_tecnico.cep,
					tbl_tecnico.estado,
					tbl_tecnico.cidade,
					tbl_tecnico.bairro,
					tbl_tecnico.endereco,
					tbl_tecnico.numero,
					tbl_tecnico.complemento,
					tbl_tecnico.observacao,
					tbl_tecnico.formacao,
					tbl_tecnico.anos_experiencia,
					tbl_tecnico.funcao,
					tbl_tecnico.telefone,
					tbl_tecnico.celular,
					tbl_tecnico.dados_complementares,
					tbl_tecnico.email,
					to_char(tbl_tecnico.data_nascimento, 'DD/MM/YYYY') AS data_nascimento,
					to_char(tbl_tecnico.data_admissao, 'DD/MM/YYYY') AS data_admissao
				FROM tbl_tecnico
				$JOIN
				WHERE tbl_tecnico.fabrica = $login_fabrica
				AND tbl_tecnico.ativo = 't'
				$sql_posto
				$sql_funcao
				$cond_pais
				ORDER BY posto";
		$res_func = pg_query($con,$sql_func);


		if ($_POST["gerar_excel"]) {

			if (pg_num_rows($res_func) > 0) {
				$data = date("d-m-Y-H:i");
				$fileName = "relatorio_de_funcionarios-{$data}.xls";
				$file = fopen("/tmp/{$fileName}", "w");
				if ($login_fabrica == 20) {
					$colun_tabela = "colspan='23'";
				}else{
					$colun_tabela = "colspan='20'";
				}

				$thead = "
					<table border='1'>
						<thead>
							<tr>
								<th {$colun_tabela} bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									RELATÓRIO FUNCIONÁRIO POSTO
								</th>
							</tr>
							<tr>";
				if ($login_fabrica == 20) {
					$thead .= "	<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>";								
				}

				$thead .= 	   "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Nascimento</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Função</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CPF</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>RG</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Endereço</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Complemento</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Formação Acadêmica</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Anos de Experiência</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data admissão</th>";
				if($login_fabrica == 20){
					$thead .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº Calçado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº Camiseta</th>";
				}

				$thead .= 	   "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Celular</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Whatsapp</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>E-mail</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Observação</th>
							</tr>
						</thead>
						<tbody>
				";
				fwrite($file, $thead);
				for ($i = 0; $i < pg_num_rows($res_func); $i++) {
					$tecnico              = pg_fetch_result($res_func, $i, 'tecnico');
					$posto                = pg_fetch_result($res_func, $i, 'posto');
					$fabrica              = pg_fetch_result($res_func, $i, 'fabrica');
					$nome                 = pg_fetch_result($res_func, $i, 'nome');
					$cpf                  = pg_fetch_result($res_func, $i, 'cpf');
					$rg                   = pg_fetch_result($res_func, $i, 'rg');
					$cep                  = pg_fetch_result($res_func, $i, 'cep');
					$estado               = pg_fetch_result($res_func, $i, 'estado');
					$cidade               = pg_fetch_result($res_func, $i, 'cidade');
					$bairro               = pg_fetch_result($res_func, $i, 'bairro');
					$endereco             = pg_fetch_result($res_func, $i, 'endereco');
					$numero               = pg_fetch_result($res_func, $i, 'numero');
					$complemento          = pg_fetch_result($res_func, $i, 'complemento');
					$observacao           = pg_fetch_result($res_func, $i, 'observacao');
					$formacao             = pg_fetch_result($res_func, $i, 'formacao');
					$anos_experiencia     = pg_fetch_result($res_func, $i, 'anos_experiencia');
					$funcao               = pg_fetch_result($res_func, $i, 'funcao');
					$telefone             = pg_fetch_result($res_func, $i, 'telefone');
					$celular              = pg_fetch_result($res_func, $i, 'celular');
					$dados_complementares = pg_fetch_result($res_func, $i, 'dados_complementares');
					$email                = pg_fetch_result($res_func, $i, 'email');
					$data_nascimento      = pg_fetch_result($res_func, $i, 'data_nascimento');
					$data_admissao        = pg_fetch_result($res_func, $i, 'data_admissao');

					$dados_complementares = json_decode($dados_complementares);

					$whatsapp        = "";
					$numero_calcado  = "";
					$numero_camiseta = "";

					foreach ($dados_complementares as $key => $value) {
						switch ($key) {
							case 'whatsapp':
								$whatsapp = $value;
								break;
							case 'cep':
								$cep = $value;
							case 'numero_calcado':
								$numero_calcado = $value;
							case 'numero_camiseta':
								$numero_camiseta = $value;
								break;
						}
					}

					switch ($funcao) {
						case 'T':
							$funcao = "Técnico";
							break;
						case 'A':
							$funcao = "Administrativo";
							break;
						case 'G':
							$funcao = "Gerente AT";
							break;
						case 'P':
							$funcao = "Proprietário";
							break;
						case 'AB':
							$funcao = "Assistente/Contador";
							break;
					}
					$body .="
					<tr>";
					if ($login_fabrica == 20) {
						$sql_po = "SELECT tbl_posto_fabrica.codigo_posto,tbl_posto.nome
									FROM	tbl_tecnico JOIN tbl_posto_fabrica USING(posto)
											JOIN tbl_posto USING(posto)
									WHERE
										tbl_tecnico.tecnico = $tecnico
										AND tbl_posto_fabrica.fabrica = $login_fabrica";
						//echo $sql_func;
						$res_po = pg_query($con,$sql_po);

						$cd_posto_e			= pg_fetch_result($res_po, 0, 'codigo_posto');
						$nome_posto_e			= pg_fetch_result($res_po, 0, 'nome');
						
						$body .= "<td nowrap align='center' valign='top'>".$cd_posto_e."-".$nome_posto_e."</td>";
					}
					$body .= "
						<td nowrap align='center' valign='top'>{$nome}</td>
						<td nowrap align='center' valign='top'>{$data_nascimento}</td>
						<td nowrap align='center' valign='top'>{$funcao}</td>
						<td nowrap align='center' valign='top'>{$cpf}</td>
						<td nowrap align='center' valign='top'>{$rg}</td>
						<td nowrap align='center' valign='top'>{$cep}</td>
						<td nowrap align='center' valign='top'>{$endereco}</td>
						<td nowrap align='center' valign='top'>{$numero}</td>
						<td nowrap align='center' valign='top'>{$complemento}</td>
						<td nowrap align='center' valign='top'>{$bairro}</td>
						<td nowrap align='center' valign='top'>{$cidade}</td>
						<td nowrap align='center' valign='top'>{$estado}</td>
						<td nowrap align='center' valign='top'>{$formacao}</td>
						<td nowrap align='center' valign='top'>{$anos_experiencia}</td>
						<td nowrap align='center' valign='top'>{$data_admissao}</td>
						<td nowrap align='center' valign='top'>{$numero_calcado}</td>
						<td nowrap align='center' valign='top'>{$numero_camiseta}</td>
						<td nowrap align='center' valign='top'>{$telefone}</td>
						<td nowrap align='center' valign='top'>{$celular}</td>
						<td nowrap align='center' valign='top'>{$whatsapp}</td>
						<td nowrap align='center' valign='top'>{$email}</td>
						<td nowrap align='center' valign='top'>{$observacao}</td>
					</tr>";
				}
				fwrite($file, $body);
				fwrite($file, "
							<tr>
								<th {$colun_tabela} bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($res_func)." registros</th>
							</tr>
						</tbody>
					</table>
				");
				fclose($file);
				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}
			}
			exit;
		}
	}
}



/**
* Ajax que retorna todos os dados do Posto
**/
if(isset($_POST['ajax_posto'])){

	$posto = $_POST['ajax_posto'];

	$sql = "
		SELECT
			tbl_posto.nome,
			tbl_posto.cnpj,
			tbl_posto_fabrica.contato_cep,
			tbl_posto_fabrica.contato_estado,
			UPPER(TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN9')) AS contato_cidade,
			tbl_posto_fabrica.contato_bairro,
			tbl_posto_fabrica.contato_endereco,
			tbl_posto_fabrica.contato_numero,
			tbl_posto_fabrica.contato_complemento,
			tbl_posto_fabrica.contato_fone_comercial,
			tbl_posto_fabrica.contato_email
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		WHERE tbl_posto_fabrica.posto = {$posto}
	";
	$res = pg_query($con, $sql);

	$dados = "";

	$dados .= pg_fetch_result($res, 0, 'nome')."|";
	$dados .= pg_fetch_result($res, 0, 'cnpj')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_cep')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_estado')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_cidade')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_bairro')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_endereco')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_numero')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_complemento')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_fone_comercial')."|";
	$dados .= pg_fetch_result($res, 0, 'contato_email');

	echo $dados;

	exit;

}


include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric",
    "multiselect"
);

include("plugin_loader.php");
?>

<script type="text/javascript">

$(function() {
	$.dataTableLoad();

	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();


	/**
	 * Configurações do Alphanumeric
	 */
	$(".numeric").numeric();
	$("#consumidor_telefone, #revenda_telefone").numeric({ allow: "()- " });

	/**
	 * Evento que chama a função de lupa para a lupa clicada
	 */
	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});
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
* Função de retorna todos os dados do Posto
*/
function busca_dados_posto(posto){

	$.ajax({
		url : "<?php echo $_SERVER['PHP_SELF']; ?>",
		type: "POST",
		data: { ajax_posto : posto },
		complete: function(data){
			var arr_posto = new Array();
			var dados = data.responseText;

			arr_posto = dados.split("|");

			$("#consumidor_nome").val(arr_posto[0]);
			$("#consumidor_cnpj").val(arr_posto[1]);
			$("#consumidor_cep").val(arr_posto[2]);
			$("#consumidor_estado").val(arr_posto[3]);
			$("#consumidor_bairro").val(arr_posto[5]);
			$("#consumidor_endereco").val(arr_posto[6]);
			$("#consumidor_numero").val(arr_posto[7]);
			$("#consumidor_complemento").val(arr_posto[8]);
			$("#consumidor_telefone").val(arr_posto[9]);
			$("#consumidor_email").val(arr_posto[10]);

			busca_cidade(arr_posto[3], "consumidor", arr_posto[4]);

		}
	});

}

/**
 * Função de retorno da lupa do posto
 */
function retorna_posto(retorno) {
	/**
	 * A função define os campos código e nome como readonly e esconde o botão
	 * O posto somente pode ser alterado quando clicar no botão trocar_posto
	 * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
	 */
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo);
	//$("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
	$("#posto_nome").val(retorno.nome);
	//$("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
	$("#div_trocar_posto").show();
	//$("#div_informacoes_posto").find("span[rel=lupa]").hide();

	<?php


	if($areaAdmin === true && $login_fabrica == 143){
		?>
		busca_dados_posto(retorno.posto);
		<?php
	}

	if ($areaAdmin === true) {
	?>
		$("#posto_latitude").val(retorno.latitude);
		$("#posto_longitude").val(retorno.longitude);
		$("input[name=lupa_config][tipo=produto]").attr({ posto: retorno.posto });
	<?php
	}
	?>
}

function pesquisaTecnico(tecnico){

        Shadowbox.open({
            content:    "pesquisa_tecnico.php?tecnico="+tecnico,
            player: "iframe",
            width:  800,
            height: 600
        });
}




</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
   <div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
<?php
}
?>
<br />
<form name="frm_os" method="POST" class="form-search form-inline" enctype="multipart/form-data" >

		<div id="div_informacoes_posto" class="tc_formulario">
			<div class="titulo_tabela">Informações do Posto Autorizado</div>
			<br />
			<input type="hidden" id="posto_id" name="posto_id" value="<?=getValue('posto_id')?>" />
			<div class="row-fluid">
				<div class="span1"></div>

				<div class="span3">
					<div class='control-group <?=(in_array("codigo", $msg_erro["campos"])) ? "error" : ""?>' >
						<label class="control-label" for="posto_codigo">Código</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_codigo" name="posto_codigo" class="span12" type="text" value="<?=getValue('posto_codigo')?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
				</div>

				<div class="span4">
					<div class='control-group <?=(in_array('nome', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="posto_nome">Nome</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_nome" name="posto_nome" class="span12" type="text" value="<?=getValue('posto_nome')?>" />
								<span class="add-on" rel="lupa"  >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
				</div>

				<div class="span3">
					<div class='control-group <?=(in_array("funcao", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class="control-label" for="funcao">Função</label>
						<div class="controls controls-row">
							<div class="span12">
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
			<div class="span1"></div>
		</div>

		<?php if($login_fabrica == 20 AND $login_pais == "BR"){ ?>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span3">
					<div class='control-group <?=(in_array("pais", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class="control-label" for="funcao">Pais</label>
						<div class="controls controls-row">
							<div class="span12">
								<select id="pais" name="pais" class="span12">
									<option value="">Selecione</option>
									<?php
										#O $array_funcao
										foreach ($array_pais as $sigla => $sigla_pais) {
											$selected = ($sigla == getValue('pais')) ? "selected" : "";

											echo "<option value='{$sigla}' {$selected} >{$sigla_pais}</option>";
										}
										?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="span1"></div>
			</div>
		<?php } ?>

	<br />
	<br />

	<p class="tac">
		<input type="submit" class="btn" name="listar" value="Listar" />
	</p>
	<br />
	</div>
</form>
</div>
<?
	if(pg_num_rows($res_func) > 0){

?>
<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" role="grid">
	<table class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
		<tr class='titulo_coluna'>
		<td>Posto</td>
		<td>Nome</td>
		<td>CPF</td>
		<td>Data Nascimento</td>
		<td>Nº Calçado</td>
		<td>Nº Camiseta</td>
		<td>Telefone</td>
		<td>Celular</td>
		<td>Whatsapp</td>
		<td>E-mail</td>
		<td>Função</td>
		</tr>
		</thead>
		<tbody>
	<?
	for ($i = 0 ; $i < pg_num_rows($res_func) ; $i++) {

		$tecnico              = pg_fetch_result($res_func, $i, 'tecnico');
		$posto_tec            = pg_fetch_result($res_func, $i, 'posto');
		$fabrica_tec          = pg_fetch_result($res_func, $i, 'fabrica');
		$nome                 = pg_fetch_result($res_func, $i, 'nome');
		$cpf                  = pg_fetch_result($res_func, $i, 'cpf');
		$data_nascimento      = pg_fetch_result($res_func, $i, 'data_nascimento');
		$telefone             = pg_fetch_result($res_func, $i, 'telefone');
		$celular              = pg_fetch_result($res_func, $i, 'celular');
		$email                = pg_fetch_result($res_func, $i, 'email');
		$dados_complementares = pg_fetch_result($res_func, $i, 'dados_complementares');
		$funcao               = pg_fetch_result($res_func, $i, 'funcao');
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
				case 'numero_camiseta':
					$numero_camiseta = $value;
					break;
			}
		}

	?>
		<tr>
		<td><?
		$sql_p = "SELECT tbl_posto_fabrica.codigo_posto,tbl_posto.nome
					FROM	tbl_tecnico JOIN tbl_posto_fabrica USING(posto)
							JOIN tbl_posto USING(posto)
					WHERE
						tbl_tecnico.tecnico = $tecnico
						AND tbl_posto_fabrica.fabrica = $login_fabrica";
		//echo $sql_func;
		$res_p = pg_query($con,$sql_p);

		$cd_posto			= pg_fetch_result($res_p, 0, 'codigo_posto');
		$nome_posto			= pg_fetch_result($res_p, 0, 'nome');

		echo $cd_posto."-".$nome_posto

		?></td>
		<td>
			<a href="javascript: pesquisaTecnico('<?php echo $tecnico?>')">
				<?echo $nome?>
			</a>
		</td>
		<td><?echo $cpf?></td>
		<td><?echo $data_nascimento?></td>
		<td><?=$numero_calcado?></td>
		<td><?=$numero_camiseta?></td>
		<td><?echo $telefone?></td>
		<td><?echo $celular?></td>
		<td><?echo $whatsapp?></td>
		<td><?echo $email?></td>

		<?if ($funcao === "T" ) {
			echo "<td>Técnico</td>";
		}elseif ($funcao === "A") {
			echo "<td>Administrativo</td>";
		}elseif ($funcao === "G") {
			echo "<td>Gerente AT</td>";
		}elseif ($funcao === "P") {
			echo "<td>Proprietário</td>";
		}else{
			echo "<td></td>";
			}?>
		</tr>
	<?
	}
	?>
		</tbody>
	</table>
	<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
</div>
<?
}
?>

<?php
include "rodape.php";
?>
