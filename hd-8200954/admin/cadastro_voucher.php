<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (!function_exists('validaArquivoImportacao')) {

    function validaArquivoImportacao($file){

    	if(!file_exists($file['tmp_name'])){
			return "Arquivo de importação não selecionado.";
		}

		if($file['type'] != 'text/csv' && $file['type'] != 'text/plain'){
			return "Extensão do arquivo de importação inválido.";
		}

		// Verifica quantos registros o arquivo possui para cada linha 
		//(Padrão arquivo de importação voucher são 7 registros por linha)
		$handle = fopen($file['tmp_name'], "r");
		while ($line = fgetcsv($handle, 1000, ";")) {
			$count = count($line)-1; 
			if($count != 7){
				fclose($handle);
				return "Layout do arquivo inválido, verifique as instruções do layout e tente novamente.";
			}
		}
		fclose($handle);
		return "";
    }
}

if(!function_exists('validaDadosImportacao')){

	function validaDadosImportacao($campos_validacao){

		global $con, $login_fabrica;

		if(empty($campos_validacao['codigo'])){
			return 'Erro na importação do voucher ' . $campos_validacao['codigo'] . ': Código não informado.';
		}

		if(empty($campos_validacao['status'])){
			return 'Erro na importação do voucher ' . $campos_validacao['codigo'] . ': Status não informado.';
		}else{
			if(!in_array(strtoupper($campos_validacao['status']) , ['ATIVO', 'INATIVO', 'UTILIZADO'])){
				return 'Erro na importação do voucher ' . $campos_validacao['codigo'] . ': Status informado inválido.';
			}
		}

		if(empty($campos_validacao['familia'])){
				return 'Erro na importação do voucher ' . $campos_validacao['codigo'] . ': Familia não informada.';
		}

		if(!empty($campos_validacao['familia'])){
			$sql = "SELECT 1 FROM tbl_familia WHERE fabrica = $login_fabrica AND (upper(descricao) = upper('{$campos_validacao['familia']}') OR codigo_familia = '{$campos_validacao['familia']}')";	
			$res = pg_query($con, $sql);
			if(pg_num_rows($res) == 0){
				return 'Erro na importação do voucher ' . $campos_validacao['codigo'] . ': Família informada não existe.';
			}
		}

		if(!empty($campos_validacao['hd_chamado'])){
			$sql = "SELECT 1 FROM tbl_hd_chamado WHERE fabrica_responsavel = $login_fabrica AND hd_chamado = {$campos_validacao['hd_chamado']}";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res) == 0){
				return 'Erro na importação do voucher ' . $campos_validacao['codigo'] . ': Protocolo informado não existe.';
			}
		}

		if(!empty($campos_validacao['cpf'])){
			$cpf = removeCaracteres($campos_validacao['cpf']);
		 	if (strlen($cpf) != 11 && strlen($cpf) != 14) {
				return 'Erro na importação do voucher ' . $campos_validacao['codigo'] . ': CPF/CNPJ informado inválido.';
		 	}
		}

		return "";
	}
}

if(!function_exists("getDadosArquivo")){

	function getDadosArquivo($line){

		return [
			'codigo' 		  => trim($line[0]),
			'status' 		  => trim($line[1]),
			'familia' 		  => trim($line[2]),
			'nome_consumidor' => trim($line[3]),
			'cpf' 			  => trim($line[4]),
			'hd_chamado'	  => trim($line[5]),
			'data' 		      => trim($line[6])
		];
	}
}

if(!function_exists("getDadosForm")){

	function getDadosForm($type = false){

		if($type){
			return [
				'nome_consumidor' => array($_POST['voucher_nome'], 'text'),
				'codigo'          => array($_POST['voucher_codigo'], 'text'), 
				'status'          => array($_POST['voucher_status'], 'text'), 
				'hd_chamado'      => array($_POST['voucher_protocolo'], 'int'),
				'data'		      => array($_POST['voucher_data'], 'date'),
				'familia'	 	  => array($_POST['voucher_familia'], 'int'),
				'cpf'		 	  => array(removeCaracteres($_POST['voucher_cpf']), 'text')
			];
		}else{
			return [
				'nome_consumidor' => $_POST['voucher_nome'],
				'codigo'          => $_POST['voucher_codigo'], 
				'status'          => $_POST['voucher_status'], 
				'hd_chamado'      => $_POST['voucher_protocolo'], 
				'data'		      => $_POST['voucher_data'], 
				'familia'	 	  => $_POST['voucher_familia'],
				'cpf'		 	  => removeCaracteres($_POST['voucher_cpf'])
			];
		}	
	}
}

if(!function_exists("prepareDadosInsert")){

	function prepareDadosInsert($data){

		global $login_fabrica;

		// Campos obrigatórios
		$data_insert = [
			'fabrica'         => $login_fabrica,
			'codigo'          => trim(addquotes($data['codigo'])),
			'status'  		  => strtoupper(trim(addquotes($data['status'])))
		];

		// Campos formato texto
		foreach(['cpf', 'nome_consumidor', 'data'] as $field){
			if(!empty($data[$field])){
				$data_insert[$field] = trim(addquotes($data[$field]));
			}
		}

		// Campos formato int
		foreach(['familia', 'hd_chamado'] as $field){
			if(!empty($data[$field])){
				$data_insert[$field] = $data[$field];
			}
		}

		return $data_insert;
	}
}

if(!function_exists("validaCamposForm")){

	function validaCamposForm($data){

		global $con, $login_fabrica;

		$campos_obrigatorios = ['codigo', 'status', 'familia'];
		$msg_erro = [];

		foreach($data as $field => $value){

			if(in_array($field, $campos_obrigatorios)){
				if(empty($value)){
					$msg_erro['msg'] = "Preencha os campos obrigatórios";
					$msg_erro['campos'][] = $field;
					return $msg_erro;
				}
			}
		}

		if(!empty($data['data'])){

			if ($data['data'] > date("Y-m-d")){
				$msg_erro['msg'] = "Data informada não poder ser maior que a data atual.";
				$msg_erro['campos'][] = 'data';
				return $msg_erro;
			}
		}

		if(!empty($data['hd_chamado'])){
			$sql = "SELECT 1 FROM tbl_hd_chamado WHERE fabrica_responsavel = $login_fabrica AND hd_chamado = {$data['hd_chamado']}";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res) == 0){
				$msg_erro ='Protocolo informado não existe.';
			}
		}

		return $msg_erro;
	}
}

if(!function_exists("limpaDadosForm")){

	function limpaDadosForm(){

		global $voucher, $voucher_codigo, $voucher_status, $voucher_protocolo, 
			   $voucher_data, $voucher_familia, $voucher_nome, $voucher_cpf;

		$voucher = '';
		$voucher_codigo = '';
		$voucher_status = '';
		$voucher_protocolo = '';
		$voucher_data = '';
		$voucher_familia = '';
		$voucher_nome = '';
		$voucher_cpf = '';

		unset($_POST);
	    header("Location: ".$_SERVER['PHP_SELF']);
	    exit;
	}
}

if(!function_exists("getIdFamilia")){

	function getIdFamilia($familia){

		global $con, $login_fabrica;

		$sql = "SELECT familia FROM tbl_familia WHERE fabrica = $login_fabrica AND (upper(descricao) = upper('$familia') OR codigo_familia = '$familia')";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
			return pg_fetch_result($res, 0, 'familia');
		}

		return false;
	}
}

if(!function_exists("getFamilias")){

	function getFamilias(){

		global $con, $login_fabrica;
		$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo = 't' ORDER BY descricao";
		$res = pg_query($con, $sql);
		return (pg_num_rows($res) > 0) ? pg_fetch_all($res) : false;
	}
}

if(!function_exists("getVouchers")){

	function getVouchers(){

		global $con, $login_fabrica;

		$sql = "SELECT tbl_voucher.voucher,
					   tbl_voucher.codigo,
					   tbl_voucher.status,
					   tbl_voucher.nome_consumidor,
					   tbl_voucher.cpf,
					   tbl_voucher.data_input,
					   to_char(tbl_voucher.data, 'DD/MM/YYYY') as data,
					   tbl_voucher.hd_chamado,
					   tbl_familia.familia as codigo_familia,
					   tbl_familia.descricao as familia
			 	FROM tbl_voucher 
				LEFT JOIN tbl_familia ON tbl_voucher.fabrica = tbl_familia.fabrica AND
										 tbl_voucher.familia = tbl_familia.familia
				WHERE tbl_voucher.fabrica = $login_fabrica ORDER BY data_input DESC LIMIT 500";

		$res = pg_query($con, $sql);
		return (pg_num_rows($res) > 0) ? pg_fetch_all($res) : false;
	}
}

if(!function_exists("getVoucher")){

	function getVoucher($id_voucher){

		global $con, $login_fabrica;

		$sql = "SELECT tbl_voucher.voucher,
					   tbl_voucher.codigo,
					   tbl_voucher.status,
					   tbl_voucher.nome_consumidor,
					   tbl_voucher.cpf,
					   to_char(tbl_voucher.data, 'DD/MM/YYYY') as data,
					   tbl_voucher.hd_chamado,
					   tbl_familia.familia as codigo_familia,
					   tbl_familia.descricao as familia
			 	FROM tbl_voucher 
				LEFT JOIN tbl_familia ON tbl_voucher.fabrica = tbl_familia.fabrica AND
										 tbl_voucher.familia = tbl_familia.familia
				WHERE tbl_voucher.fabrica = $login_fabrica AND tbl_voucher.voucher = $id_voucher";

		$res = pg_query($con, $sql);
		return (pg_num_rows($res) > 0) ? pg_fetch_all($res) : false;
	}
}

if(!function_exists("getStatus")){

	function getStatus(){

		return [
			"ATIVO"     => "Ativo",
			'INATIVO'   => "Inativo",
			'UTILIZADO' => "Utilizado"
		];
	}
}

if(!function_exists('addquotes')){

	function addquotes($string){
		return "'" . $string . "'";
	}
}

if(!function_exists('removeCaracteres')){

	function removeCaracteres($string){
		return preg_replace('/[^0-9]/', '', $string);
	}
}

if(!function_exists('formataData')){

	function formataData($data){

		if(!empty($data)){
			$data_voucher = str_replace("/", "-", $data);
			return date('Y-m-d', strtotime($data_voucher));
		}

		return "";
	}
}

$combo_familias = getFamilias(); 
$combo_status = getStatus();
$vouchers = getVouchers();

// Importa os dados do arquivo e mostra na tela
if (isset($_POST['btn_importar'])){

	$vouchers = [];
	$file = $_FILES['voucher_file'];
	$msg_erro = validaArquivoImportacao($file);

	if(empty($msg_erro)){
		
		// Percorre o arquivo importado e salva/valida os dados.
		$handle = fopen($file['tmp_name'], "r");
		while ($line = fgetcsv($handle, 1000, ";")) {

			$data = getDadosArquivo($line);
			$vouchers[] = $data;
			$msg_erro = validaDadosImportacao($data);
			
			if(!empty($msg_erro)){
				break;
			}
		}

		fclose($handle);
	}

	if(empty($msg_erro)){

		// Copia o arquivo importado para um arquivo temporário
		$file_content = file_get_contents($file['tmp_name']);
		$path = "/tmp/importa_voucher_fabrica_" . $login_fabrica . ".csv";

		if(file_exists($path)){
			unlink($path); 
		}

		$tmp_file = fopen ($path,"w");
		fputs ($tmp_file,$file_content);
		fclose($tmp_file);
		$registros_importados = true;
	}
}

// Grava os dados do arquivo no banco
if(isset($_POST['btn_gravar'])){

	// Recupera o arquivo temporário e o exclui
	$path = "/tmp/importa_voucher_fabrica_" . $login_fabrica . ".csv";
	$handle = fopen ($path,"r");
	unlink($path); 

	$row = 0;
	$rollback = false;
	$commit = false;

	// Percorre o arquivo temporário e grava os dados no banco
	while ($line = fgetcsv($handle, 1000, ";")) {

		$data = [
			'codigo'          => trim($line[0]),
			'status'          => trim($line[1]),
			'familia'         => getIdFamilia(trim($line[2])),
			'nome_consumidor' => trim($line[3]),
			'cpf' 	          => removeCaracteres(trim($line[4])),
			'hd_chamado'      => trim($line[5]),
			'data'            => formataData(trim($line[6]))
		];

		$data_insert = prepareDadosInsert($data);
		$fields = implode(',', array_keys($data_insert));
		$values = implode(',', array_values($data_insert));

		$sql = "INSERT INTO tbl_voucher (" . $fields . ") VALUES (" . $values . ") RETURNING voucher";

		pg_query("BEGIN");
		$res = pg_query($con, $sql);

	 	if (!$res) {
	 		$rollback = true; $commit = false;
		 	$msg_erro = "Ocorreu um erro durante a importação, verifique os dados do arquivo importado.";
		 	break;
	 	}else{
	 		$vouchers = getVouchers();
	 		$commit = true;
	 	}
	}

	if($rollback){
		pg_query("ROLLBACK");
		$msg_success = "";
	}

	if($commit){
		pg_query("COMMIT");
		$msg_success = "Registros importados com sucesso.";
		limpaDadosForm();
	}

	fclose($handle);
}

if(isset($_POST['btn_cadastrar'])){

	$data = getDadosForm();
	$msg_erro = validaCamposForm($data);

	if(empty($msg_erro)){
	
		$data_insert = prepareDadosInsert($data);
		$fields = implode(',', array_keys($data_insert));
		$values = implode(',', array_values($data_insert));

		$sql = "INSERT INTO tbl_voucher (" . $fields . ") VALUES (" . $values . ")";

		pg_query("BEGIN");
		$res = pg_query($con, $sql);

	 	if (pg_last_error($con) || !$res) {

	 		pg_query("ROLLBACK"); 
	 		$msg_erro = "Ocorreu um erro durante o cadastro."; 

	 	}else{

			pg_query("COMMIT");
			$msg_success = "Registro cadastrado com sucesso.";
	 		$vouchers = getVouchers();
	 		limpaDadosForm();	
	 	}
	 }
}

if(isset($_POST['btn_alterar'])){

	$mostra_botoes_edit = true;
	$id_voucher = $_POST['id_voucher'];

	if(empty($id_voucher)){
		session_start();
		$id_voucher = $_SESSION['id_voucher'];
	}

	if(!empty($id_voucher)){

		$data = getDadosForm();
		$msg_erro = validaCamposForm($data);

		if(empty($msg_erro)){

			if(empty($data['hd_chamado'])){
				$data['hd_chamado'] = 'NULL';
			}

			if(empty($data['familia'])){
				$data['familia'] = 'NULL';
			}

			$sql = "UPDATE tbl_voucher 
					SET codigo          = '{$data['codigo']}',
					    status          = '{$data['status']}',
			   	        nome_consumidor = '{$data['nome_consumidor']}',
				        hd_chamado      =  {$data['hd_chamado']},
				        familia         =  {$data['familia']},
				        cpf             = '{$data['cpf']}',";

			
			$sql .= (empty($data['data'])) ? "data = NULL" : "data = '{$data['data']}'";
			$sql .= " WHERE fabrica = $login_fabrica AND voucher = $id_voucher";

			pg_query("BEGIN");
			$res = pg_query($con, $sql);

		 	if (pg_last_error($con) || !$res) {
			 	pg_query("ROLLBACK");
			 	$msg_erro = "Ocorreu um erro durante a alteração.";
		 	}else{
		 		pg_query("COMMIT");
				$msg_success = "Registro alterado com sucesso.";
		 		$vouchers = getVouchers();
		 		$mostra_botoes_edit = false; 
		 		$mostra_botoes_insert = true;
		 		limpaDadosForm();
		 		session_unset('id_voucher');			
		 	}	
	 	}
 	}
}

if(isset($_POST['btn_pesquisar'])){

	$vouchers = [];
	$sql = array();
	$filters = getDadosForm(true);
	$filtros_informados = false;

	foreach($filters as $field => $data){
		if(!empty(trim($data[0]))){
			$filtros_informados = true;
			break;
		}
	}

	if($filtros_informados){

		$sql[] = "SELECT tbl_voucher.voucher,
					     tbl_voucher.codigo,
					     tbl_voucher.status,
					     tbl_voucher.nome_consumidor,
					     tbl_voucher.cpf,
					     to_char(tbl_voucher.data, 'DD/MM/YYYY') as data,
					     tbl_voucher.hd_chamado,
					     tbl_familia.descricao as familia
			 	FROM tbl_voucher 
				LEFT JOIN tbl_familia ON tbl_voucher.fabrica = tbl_familia.fabrica AND
										 tbl_voucher.familia = tbl_familia.familia
				WHERE tbl_voucher.fabrica = $login_fabrica";


		foreach($filters as $field => $data){

			$value = trim($data[0]); 
			$type = $data[1];

			if($field == 'familia'){
				$field = 'tbl_voucher.familia';
			}

			if(!empty($value)){

				switch($type){
					case "text" : $sql[] = " AND upper($field) = upper('$value') "; break;
					case "date" : $sql[] = " AND $field = '$value' "; break;
					default: $sql[] = " AND $field = $value ";
				}
			}
		}

		$sql = implode(" ",$sql);
		$res = pg_query($con, $sql);
		$mostra_botoes_insert = true;

		if(pg_num_rows($res) > 0){
			$vouchers = pg_fetch_all($res);
		}
	}else{
		$msg_erro = 'Para efetuar a consulta, ao menos um filtro deve ser preenchido.';
	}
}

if(isset($_POST['btn_remover'])){

	$vouchers = [];
	$id_voucher = $_POST['id_voucher'];

	if(!empty($id_voucher)){

		$sql = "DELETE FROM tbl_voucher WHERE fabrica = $login_fabrica AND voucher = $id_voucher";

		pg_query("BEGIN");
		$res = pg_query($con, $sql);

	 	if (pg_last_error($con) || !$res) {

		 	pg_query("ROLLBACK");
		 	$msg_erro = "Ocorreu um erro durante a exclusão.";

	 	}else{

	 		pg_query("COMMIT");
	 		$msg_success = "Registro excluído com sucesso.";
	 		limpaDadosForm();
	 		$vouchers = getVouchers();	
	 	}
 	}
}

if(isset($_GET['voucher'])){

	$data = getVoucher($_GET['voucher'])[0];

	if(!empty($data)){
		$voucher = $data['voucher'];
		$voucher_codigo = $data['codigo'];
		$voucher_status = $data['status'];
		$voucher_protocolo = $data['hd_chamado'];
		$voucher_data = formataData($data['data']);
		$voucher_familia = $data['codigo_familia'];
		$voucher_nome = $data['nome_consumidor'];
		$voucher_cpf = $data['cpf'];
		$mostra_botoes_edit = true;

	  	session_start();
	 	$_SESSION['id_voucher'] = $data['voucher'];
	}	
}

if(isset($_POST['btn_cancelar'])){
	limpaDadosForm();
}

$layout_menu = 'cadastro';
$title = 'Cadastro de Vouchers';
include 'cabecalho_new.php';

$plugins = array(
	"datatable_responsive",
	"maskedinput",
	"datepicker",
	"shadowbox"
);
include("plugin_loader.php");

?>

<style>
.files{padding-bottom: 20px;}
.files input {outline: 2px dashed #92b0b3; outline-offset: -10px; padding: 50px; text-align: center;margin: 0; width: 100%;line-height: 20px;}
.files label{font-weight: bold;margin-left: 10px;}
.row-voucher{width: 90%;margin-left: 5%;margin-bottom: 20px;}
.radio-tipo-doc{display: inline-flex;}
.radio-tipo-doc > input{margin: 5px;}
.radio-tipo-doc > label{padding-right: 5px;}
.info-layout{margin-top:20px;padding-left: 15px;}
.info-layout > span{background-color: #b94a48; padding: 3px; border-radius: 5px;color: #fff;font-weight: bold;font-size: 13px;}
.titulo_tabela{padding: 2px !important;}
.container{padding-bottom:10px;}
.container-voucher{margin-bottom:20px;}
.wrapper-btn-upload{padding-left: 70px;margin-top: 60px}
.center{text-align: -moz-center;text-align: -webkit-center;}
.btn-gravar{color: #fff; background-image: linear-gradient(to bottom, #ff821b, #dc5f16); background-color: #ff7401; text-shadow: none !important;}
.btn-gravar:hover{color: #fff;background-image: linear-gradient(to bottom, #ff821b, #f08444);background-color: #ff7401; background-position: 0 !important;}
.btn:focus{outline: none !important;}
.btn-form{background: #596d9b;color: #fff;text-shadow: none !important;}
.btn-form:hover{ background: #5f73a0;color: #fff;}
.btn-form-danger{background: #b94a48; color: #fff;text-shadow: none !important;}
.btn-form-danger:hover{background: #bf5250;color: #fff;}
#tbl_voucher_wrapper > .row-fluid{min-height: 40px !important;} 
select[name=tbl_voucher_length]{width: 80px !important;margin-bottom:0 !important;}
</style>

<? if (!empty($msg_erro)): ?>
    <div class="alert alert-error">
		<h4><?=is_array($msg_erro)? utf8_decode($msg_erro['msg']) : utf8_decode($msg_erro)?></h4>
    </div>
<? endif; ?>

<? if (!empty($msg_success)): ?>
    <div class="alert alert-success">
		<h4><?=utf8_decode($msg_success)?></h4>
    </div>
<? endif; ?>

<div class="container" id="container-page">

<? if(!$registros_importados):?>

<div class="container-voucher">
	<form name='frm_cad_voucher' id="frm_cad_voucher" method='post' enctype="multipart/form-data" action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	    <input type="hidden" name="id_voucher" id="id_voucher" value="<?=$voucher ?>">
	    <div class='titulo_tabela'>Cadastrar / Pesquisar Voucher </div>
	    <br/>
	    <div class="row-fluid row-voucher">
			<div class="span3">
				<div class='control-group <?=(in_array("codigo", $msg_erro["campos"])) ? "error" : ""?>'>
			        <label class="control-label" for="voucher_codigo">Voucher</label>
			        <div class="controls controls-row">
			            <div class="span12">
			            	<h5 class="asteristico">*</h5>
			                <input type="text" name="voucher_codigo" maxlength="10" class="span12" value="<?=$voucher_codigo?>">
			            </div>
			        </div>
			    </div>
			</div>
			<div class="span3">
				<div class='control-group <?=(in_array("status", $msg_erro["campos"])) ? "error" : ""?>'>
			        <label class="control-label" for="voucher_status">Status</label>
			        <div class="controls controls-row">
			        	<h5 class="asteristico">*</h5>
		                <select class="span12 select-voucher" name="voucher_status">
					  		<option value="">Selecione</option>
					  		<?foreach($combo_status as $key => $value):?>
								<?if(strtoupper($key) == strtoupper($voucher_status)):?>
									<option selected value="<?=$key?>"><?=$value?></option>
								<?else:?>
	                				<option value="<?=$key?>"><?=$value?></option>
	                			<?endif;?>
	                		<?endforeach;?>
						</select>
			        </div>
			    </div>
			</div>
			<div class="span3">
				<div class="control-group">
			        <label class="control-label" for="voucher_protocolo">Protocolo</label>
			        <div class="controls controls-row">
			            <div class="span10 input-append">
		                	<input type="text" name="voucher_protocolo" value="<?=$voucher_protocolo?>" class="span12">
		                	<span style="cursor:pointer;" class="add-on" onclick="abre_pesquisa_protocolo()"><i class="icon-search"></i></span>
			            </div>
			        </div>
			    </div>
			</div>
			<div class="span3">
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
			        <label class="control-label" for="voucher_data">Data</label>
			        <div class="controls controls-row">
			            <div class="span12">
			                <input type="date" name="voucher_data" value="<?=$voucher_data?>" class="span12">
			            </div>
			        </div>
			    </div>
			</div>
		</div>
	   	<div class="row-fluid row-voucher">
			<div class="span3" style="margin-top: 5px;">
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
			        <label class="control-label" for="voucher_familia"><?=utf8_decode("Família")?></label>
			        <div class="controls controls-row">
			        	<h5 class="asteristico">*</h5>
		                <select class="span12 select-voucher" name="voucher_familia">
            	 			<option value="">Selecione</option>
							<?foreach($combo_familias as $familia):?>
								<?if($familia['familia'] == $voucher_familia):?>
									<option selected value="<?=$familia['familia']?>"><?=$familia['descricao']?></option>
								<?else:?>
	                				<option value="<?=$familia['familia']?>"><?=$familia['descricao']?></option>
	                			<?endif;?>
	                		<?endforeach;?>
						</select>
			        </div>
			    </div>
			</div>
			<div class="span6" style="margin-top: 5px;">
				<div class="control-group">
			        <label class="control-label" for="voucher_nome">Nome</label>
			        <div class="controls controls-row">
			            <div class="span12">
			                <input type="text" name="voucher_nome" maxlength="50" value="<?=$voucher_nome?>" class="span12">
			            </div>
			        </div>
			    </div>
			</div>
			<div class="span3">
				<div class="control-group">
			        <span class="radio-tipo-doc"> 
			        	<input type="radio" id="doc_cpf" name="tipo_doc" value="cpf">
						<label for="doc_cpf">CPF</label><br>
			        	<input type="radio" name="tipo_doc" id="doc_cnpj" value="cnpj">
						<label for="doc_cnpj">CNPJ</label><br>
					</span>
			        <div class="controls controls-row">
			            <div class="span12">
			                <input type="text" name="voucher_cpf" id="voucher_cpf" maxlength="14" value="<?=$voucher_cpf?>" class="span12">
			            </div>
			        </div>
			    </div>
			</div>
		</div>
		<br />
		<div class="row-fluid">
			<div class="center">
				<?if($mostra_botoes_edit):?>
					<button type="submit" class="btn btn-form" name="btn_alterar" alt="Alterar" value="Alterar">Alterar</button>
					<button type="submit" class="btn btn-form" name="btn_cancelar" alt="Cancelar" value="Cadastrar">Cancelar</button>
					<button type="submit" class="btn btn-form-danger" name="btn_remover" alt="Remover" value="Remover">Remover</button>
				<?else:?>
					<button type="submit" class="btn btn-form" name="btn_cadastrar" alt="Cadastrar" value="Cadastrar">Cadastrar</button>
					<button type="submit" class="btn btn-form" name="btn_cancelar" alt="Cancelar" value="Cadastrar">Cancelar</button>
					<button type="submit" class="btn btn-form" name="btn_pesquisar" alt="Pesquisar" value="Pesquisar">Pesquisar</button>
				<?endif;?>
			</div>
		</div>
	</form>
</div>
<? endif; ?>
<? if(!$registros_importados) : ?>

<div class="container-voucher">
	<form name='frm_import_voucher' id="frm_import_voucher" method='post' enctype="multipart/form-data" action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	 	<input type="hidden" name="btn_importar">
	    <div class='titulo_tabela'>Fazer Upload dos Vouchers</div>
		<div class="row-fluid row-voucher" style="margin-top:10px;margin-bottom:0 !important"> 
			<div class="span12">
				<div class="control-group">
					<div class="controls controls-row">
		         	 	<div class="span12 files">
	         	 	   		<label>Arquivo CSV/TXT</label>
		                	<input type="file" accept=".csv,.txt" name="voucher_file" id="voucher_file" class="span12">
		            	</div>
		          	</div>
			  	</div>
			</div>
		</div>
		 <div class="row-fluid row-voucher">
	    	<div class="span12 info-layout">
	    		<span><?=utf8_decode("Layout do arquivo: Voucher, Status, Família do Produto, Nome, CPF, Protocolo, Data, separados por ponto e vírgula (;)")?></span>
	    	</div>
		</div>
	</form>
</div>
<? else : ?>
<div class="container-voucher">
	<form name='frm_import_voucher' id="frm_import_voucher" method='post' enctype="multipart/form-data" action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	    <div class='titulo_tabela'>Importar Registros</div>
	    <div class="row-fluid center" style="padding-top: 20px;">
			<small>
				<span style="color:red">*</span> 
				<strong><?=utf8_decode("Verifique na tabela abaixo os registros que serão importados, caso queira proceder com a importação, clique no botão gravar.")?></strong>
			</small>
		</div>
	    <div class="row-fluid">
	    	<div class="span12">
	    		<div class="center" style="padding-bottom:10px">
	    			<button type="submit" name="btn_gravar" class="btn btn-gravar">Gravar</button>
	    			<button type="submit" class="btn">Cancelar</button>
	    		</div>
    		</div>
		</div>
	</form>
</div>

<? endif; ?>


<?if(empty($vouchers)):?>

	<div class="alert"><h4>Nenhum registro encontrado</h4></div>
	
<?else:?>

	<div class="container-voucher">
		<table id="tbl_voucher" class='table table-striped table-bordered table-hover'>
			<thead>
				<tr class = 'titulo_coluna'>
					<th align='center' nowrap><?php echo utf8_decode("Voucher");?></th>
					<th align='center' nowrap><?php echo utf8_decode("Status");?></th>
					<th align='center' nowrap><?php echo utf8_decode("Família do Produto");?></th>
					<th align='center' nowrap><?php echo utf8_decode("Nome");?></th>
					<th align='center' nowrap><?php echo utf8_decode("CPF/CNPJ");?></th>
					<th align='center' nowrap><?php echo utf8_decode("Protocolo");?></th>
					<th align='center' nowrap><?php echo utf8_decode("Data");?></th>
				</tr>
			</thead>
			<tbody>
			  	<? foreach($vouchers as $v) : ?>
			  		<tr>
			  			<? if(!$registros_importados) : ?>
				      		<td align="left" nowrap>
				      			<a href="cadastro_voucher.php?voucher=<?=$v['voucher']?>"><?=$v['codigo']?></a>
				      		</td>
				      	<? else: ?>
				      		<td align="left" nowrap><?=$v['codigo']?></td>
			      		<?endif; ?>
				      	<td align="left" nowrap><?=strtoupper($v['status'])?></td>
				      	<td align="left" nowrap><?=strtoupper($v['familia'])?></td>
				      	<td align="left" nowrap><?=strtoupper($v['nome_consumidor'])?></td>
				      	<td align="left" nowrap><?=$v['cpf']?></td>
				      	<td align="left" nowrap><?=$v['hd_chamado']?></td>
				      	<td align="left" nowrap><?=$v['data']?></td>
				  </tr>
			  	<? endforeach; ?>
			</tbody>
		</table>
	</div>
<?endif;?>

</div>

<script type="text/javascript">

function abre_pesquisa_protocolo(){

	var protocolo = $("input[name=voucher_protocolo]").val();

	Shadowbox.open({
		content :   "atendimento_lupa_new.php?&descricao_pesquisa=" + protocolo,
		player: "iframe",
		width: 1000,
		height: 500
	});
}

function retorna_dados_atendimento(dados){

	if(dados){

		$("input[name=voucher_protocolo]").val(dados.atendimento);
		$("input[name=voucher_nome]").val(dados.nome_cliente);
		
		if(dados.cpf_cliente){
			verificaTipoDocumento(dados.cpf_cliente, true);
		}	
	}
}

function verificaTipoDocumento(cpf, change){

	var voucher_cpf  = $("#voucher_cpf");
	var cleanCpf = cpf.replace(/[^\d]+/g,'');

	if(cleanCpf.length <= 11){
		voucher_cpf.mask("999.999.999-99");
		$("#doc_cpf").prop('checked', true);
   	}else{
		voucher_cpf.mask("99.999.999/9999-99");
		$("#doc_cnpj").prop('checked', true);
   	}

   	if(change){
   		voucher_cpf.val(cpf);
   	}

	voucher_cpf.focus();
	voucher_cpf.blur();
}

$(document).ready(function(){

	Shadowbox.init();

	var frm_importar = $("#frm_import_voucher");
	var frm_cadastro = $('#frm_cad_voucher');
	var voucher_file = $("#voucher_file");
	var voucher_cpf  = $("#voucher_cpf");
	var voucher_cod  = $("input[name=voucher_codigo]");
	var btn_remover  = $("button[name=btn_remover]");
	var tipo_documento = $('input[type=radio][name=tipo_doc]');
	var tbl_voucher = $("#tbl_voucher");

	voucher_file.change(function(ev){

 		var fileName = ev.target.files[0].name;

		if(confirm("Deseja importar o arquivo: " + fileName + " ?")){;
			frm_importar.submit();
		}else{
			$(this).val('');
		}
	});

	btn_remover.on("click", function(e){

		e.preventDefault();

		var msg = "";

		msg += '<?=utf8_decode("Tem certeza que deseja excluir o voucher ")?>';
		msg += voucher_cod.val();
		msg += '<?=utf8_decode(" ? Essa ação não pode ser desfeita.")?>';

		if(confirm(msg)){
			frm_cadastro.append('<input type="hidden" name="btn_remover" />');
			frm_cadastro.submit();
		}
	})

	if(voucher_cpf[0] != undefined){
		verificaTipoDocumento(voucher_cpf.val());
		voucher_cod.focus();
	}
	
    tipo_documento.change(function() {

    	voucher_cpf.unmask();

    	if($(this).val() == 'cpf'){
 			voucher_cpf.mask("999.999.999-99");
    	}else if($(this).val() == 'cnpj'){
	 		voucher_cpf.mask("99.999.999/9999-99");
    	}
    	
    	voucher_cpf.focus();
	});

	if($('#tbl_voucher tbody tr').length > 10){

		tbl_voucher.DataTable({
	  		ordering: false,
	  		"language": {
		    "lengthMenu": '<?=utf8_decode("_MENU_ Registros por página")?>',
		    "search": "Procurar:",
	      	"info": "Mostrando _START_ de _END_ de _TOTAL_ registros",
	     	"paginate": {
		      	"next": '<?=utf8_decode("Próxima")?>',
		      	"previous" : "Anterior"
		    }}
    	});
	}
	if(frm_cadastro[0] != undefined){
		$('html,body').animate({
    	    scrollTop: frm_cadastro.offset().top-150
    	}, 'slow');
	}
});

</script>
      
