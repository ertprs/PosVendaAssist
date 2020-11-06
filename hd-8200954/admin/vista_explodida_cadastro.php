<?php
//ini_set('memory_limit', '128M');
//phpinfo();exit;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "info_tecnica";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';
include_once 'funcoes.php';
include_once('class/tdocs.class.php');

if ($S3_sdk_OK) {
include_once S3CLASS;
$s3 = new anexaS3('ve', (int) $login_fabrica);
$S3_online = is_object($s3);
}


if (isset($_POST['excluir_comunicado'])) {

	$comunicado = $_POST['comunicado'];

	$update = "UPDATE tbl_comunicado 
			   SET fabrica = 0 
			   WHERE comunicado = $comunicado";

	$res = pg_query($con, $update);

	$msg['result'] = 'error';

	if (strlen(pg_last_error()) == 0) {
	
		$msg['result'] = 'success';
	}	

	$msg = json_encode($msg);

	exit($msg);
}
# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
$busca      = trim($_GET["busca"]);
$tipo_busca = trim($_GET["tipo_busca"]);
if (strlen($q)>2){
	if ($tipo_busca=="produto"){
		$sql = "SELECT tbl_produto.produto,";
				if($login_fabrica == 96)
					$sql .= "tbl_produto.referencia_fabrica AS referencia,";
				else
					$sql .= "tbl_produto.referencia,";
					$sql .= "tbl_produto.descricao
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE tbl_linha.fabrica = $login_fabrica ";

		if ($busca == "codigo"){
			if($login_fabrica != 96){
				$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_produto.referencia_fabrica) like UPPER('%$q%') OR UPPER(tbl_produto.referencia_pesquisa) like UPPER('%$q%')";
			}
		}else{
			$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$produto    = trim(pg_result($res,$i,produto));
				$referencia = trim(pg_result($res,$i,referencia));
				$descricao  = trim(pg_result($res,$i,descricao));
				echo "$produto|$descricao|$referencia";
				echo "\n";
			}
		}
	}
}

exit;
}

$produtoSemelhante = filter_input(INPUT_POST, 'produto_semelhante');
if( !empty($produtoSemelhante) ){

	$pgResource = pg_query($con, "SELECT produto, referencia, descricao FROM tbl_produto WHERE referencia ILIKE '{$produtoSemelhante}_' AND fabrica_i = $login_fabrica");

	$listaDeProdutos = pg_fetch_all($pgResource);

	if( $listaDeProdutos ){
		echo json_encode([
			'status' => 'success',
			'data' => $listaDeProdutos
		]);

		exit;
	}

	echo json_encode([
		'status' => 'error',
		'data' => null
	]);

	exit;
}

// Parâmetros de configuração
// movido para parâmetros adicionais. $usa_versao_produto = in_array($login_fabrica, array(151));
if ($usa_versao_produto and !isset($max_versao_produto))
$max_versao_produto = 3; // máx. no banco: 3.

$msg_erro = array();

$comunicado = trim($_REQUEST["comunicado"]);

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$ativo = trim($_POST['ativo']);

$replicar = $_POST['PickList'];

if (!empty($comunicado)) {
$tempUniqueId = $comunicado;
$anexoNoHash = null;
} else if (strlen($_POST["anexo_chave"]) > 0) {
$tempUniqueId = $_POST["anexo_chave"];
$anexoNoHash = true;
} else {
if ($areaAdmin === true) {
$tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
} else {
$tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
}

$anexoNoHash = true;
}

if (trim($btn_acao) == "gravar") {

$res = pg_query($con,"BEGIN TRANSACTION");

$produto_referencia     = trim($_POST['produto_referencia']);
$extensao               = trim($_POST['extensao']);
$tipo                   = trim($_POST['tipo']);
$versao                 = trim($_POST['versao']);
$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : false;
$ativo                  = trim($_POST['ativo']);
$linha                  = trim($_POST['linha']);
$descricao              = trim($_POST['descricao']);
$obrigatorio_os_produto = trim($_POST['obrigatorio_os_produto']);
$obrigatorio_site       = trim($_POST['obrigatorio_site']);
$produto_serie 		= trim($_POST['produto_serie']);
$ordem_producao 	= trim($_POST['ordem_producao']);
$data_documento		= trim($_POST['data_documento']);

if (in_array($login_fabrica, [148])) {
	if (empty($linha)) {
		$msg_erro["msg"][] = traduz("Informe uma linha");
		$msg_erro["campos"][] = "linha";
	}
}

if(($login_fabrica == 91 or $login_fabrica == 15) AND $tipo == "Vídeo"){
	$link_video       = trim($_POST['link_video']);
	if(empty($link_video)){
		$msg_erro["msg"][] = traduz("Informe o link do vídeo");
		$link_video = 'null';
	}
}

if($login_fabrica == 6){
	$mensagem = trim($_POST['mensagem']);
}
if (strlen($descricao) == 0) {
	$aux_descricao = "null";
} else {
	$aux_descricao = "'". $descricao ."'";
}

if (strlen($tipo_posto) == 0) {
	$aux_tipo_posto = "null";
} else {
	$aux_tipo_posto = "'". $tipo_posto ."'";
}

if (strlen($extensao) == 0) {
	$aux_extensao = "null";
} else {
	$aux_extensao = "'". $extensao ."'";
}

if (strlen($familia) == 0) {
	$aux_familia = "null";
} else {
	$aux_familia = $familia;
}

if (strlen($linha) == 0) {
	$aux_linha = "null";
} else {
	$aux_linha = $linha;
}

if ($usa_versao_produto and $versao != '') {

	if(in_array($login_fabrica, array(151))){
		$max_versao_produto = 3;
	}

	if (strlen($versao) > $max_versao_produto){
		$versao = substr($versao, 0, $max_versao_produto);
	}

	$versao_produto = $versao;
	$aux_versao = "'$versao'";
} else {
	$aux_versao = 'null';
}

if ($login_fabrica == 175){
	if (empty($ordem_producao)){
		$aux_versao = 'null';
	}else{
		$aux_versao = "'". $ordem_producao ."'";
	}
}

if( $login_fabrica == 3 AND strlen($arquivo['name']) <= 0 ){
	$msg_erro['msg'][] = 'Favor fazer upload do arquivo!';
	$msg_erro['campos'][] = 'arquivo';
}

if (strlen($tipo) == 0) {
	$aux_tipo = "null";
} else {
	$aux_tipo = "'". $tipo ."'";
}

if($aux_tipo == "null"){
	$msg_erro["msg"][] = traduz("Escolha um tipo de comunicado");
	$msg_erro["campos"][] = "tipo_comunicado";
}

//Quando selecionando o 'Aviso Posto Unico' faz a validacão para saber se entrou com os dados do posto
if (((strlen($posto_nome) == 0) || (strlen($codigo_posto) == 0)) AND (strlen($tipo == 'Com. Unico Posto'))) {
	$msg_erro["msg"][] = traduz("Por favor inserir os dados do posto");
}

if (strlen($mensagem) == 0) {
	$aux_mensagem = "null";
} else {
	$aux_mensagem = "'". $mensagem ."'";
}

if (strlen($obrigatorio_os_produto) == 0) {
	$aux_obrigatorio_os_produto = "'f'";
} else {
	$aux_obrigatorio_os_produto = "'t'";
}

if (strlen($obrigatorio_site) == 0) {
	$aux_obrigatorio_site = "'f'";
} else {
	$aux_obrigatorio_site = "'t'";
}

if (trim($ativo) != 't') {
	$aux_ativo = "'f'";
} else {
	$aux_ativo = "'t'";
}

if ($login_fabrica == 157 && $tipo == "Catálogo de Acessórios") {

	$produto = "null";
	$sem_produto = true;

	if (strlen($descricao) == 0) {
		$aux_descricao = "null";
		$msg_erro['msg'][] = traduz('Favor digite uma Descrição / Titulo!');
		$msg_erro['campos'][] = 'descricao';

	} else {
		$aux_descricao = "'". $descricao ."'";
	}

} else {
	$sem_produto = false;

	if ((strlen($linha) == 0 and strlen($descricao) == 0) or
		(in_array($login_fabrica, array(3, 11, 14, 15, 91, 161, 172)) and strlen($descricao)>0)) {//HD 198907
		if (strlen($produto_referencia) > 0){

			$cond = ($login_fabrica == 96) ? " OR  referencia_fabrica = '$produto_referencia'" : "";

			$sql = "SELECT produto, referencia FROM tbl_produto JOIN tbl_linha ON (tbl_linha.linha = tbl_produto.linha) WHERE (referencia = '$produto_referencia' $cond) AND tbl_linha.fabrica = $login_fabrica and fabrica_i = $login_fabrica; ";
			$res = pg_query ($con,$sql);

			if (pg_numrows ($res) == 0) $msg_erro["msg"][] = traduz("Produto % não cadastrado",null,null,[$produto_referencia]);
			else                        $produto = pg_result ($res,0,0);
		}else{
			//$msg_erro .= "Por favor informe o Produto!" ;
			$produto = "null";
		}
	}else{
		$produto = "null";
	}
	
	if (in_array($login_fabrica, [203]) && in_array($tipo, ['ITB Informativo Técnico Brother', 'ITB Informativo Tecnico Brother'])) {
		$produto 	 = "null";
		$sem_produto = true;
	}
}

if (in_array($login_fabrica, array(169,170))){
	if (strlen($produto_serie) > 0){
		$sql = "
			SELECT serie
			FROM tbl_numero_serie
			WHERE fabrica = {$login_fabrica}
			AND produto = {$produto}
			AND serie = UPPER('{$produto_serie}') OR serie = UPPER('S{$produto_serie}'))";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 0){
			$msg_erro["msg"][] = traduz("Número de Série % não encontrado.",null,null,[$produto_serie]);
			$msg_erro["campos"][] = "produto_serie";
		}else{
			$campo_serie = ", serie";
			$valor_serie = ",'$produto_serie'";
			$update_serie = ",serie = '$produto_serie'";
		}
	}else{
		$produto_serie = "null";
	}
}


if(in_array($login_fabrica,array(161)) AND strlen($data_documento) > 0){
	$informacoes_adicionais['data_documento'] = $data_documento;
	$informacoes_adicionais = ",'".json_encode($informacoes_adicionais)."'";
	$campo_informacoes_adicionais = ",parametros_adicionais";
}else{
	$informacoes_adicionais = "";
}

$multiplo = trim($_POST['radio_qtde_produtos']);

if ($multiplo == 'muitos'){
	$produto = "null";
}

$posto = "null";

if (count($msg_erro["msg"]) == 0) {

	if (strlen($comunicado) == 0) {

		$operacao = "insert";

		$sql = "INSERT INTO tbl_comunicado (
					produto                ,
					familia                ,
					linha                  ,
					versao                 ,
					extensao               ,
					descricao              ,
					mensagem               ,
					tipo                   ,
					fabrica                ,
					obrigatorio_os_produto ,
					obrigatorio_site       ,
					posto                  ,
					tipo_posto             ,
					ativo                  ,
					remetente_email        ,
					video
					$campo_serie
					$campo_informacoes_adicionais
				) VALUES (
					$produto                    ,
					$aux_familia                ,
					$aux_linha                  ,
					$aux_versao                 ,
					LOWER($aux_extensao)        ,
					$aux_descricao              ,
					$aux_mensagem               ,
					$aux_tipo                   ,
					$login_fabrica              ,
					$aux_obrigatorio_os_produto ,
					$aux_obrigatorio_site       ,
					$posto                      ,
					$aux_tipo_posto             ,
					$aux_ativo                  ,
					'$remetente_email'          ,
					'$link_video'
					$valor_serie
					$informacoes_adicionais
				) RETURNING comunicado;";
	}else{

		$operacao = "update";

		if(in_array($login_fabrica,array(161)) AND strlen($data_documento) > 0){
			
			$sql = "SELECT parametros_adicionais FROM tbl_comunicado WHERE comunicado = {$comunicado}";
			$res = pg_query($con,$sql);

			$informacoes_adicionais = json_decode(pg_fetch_result($res,'parametros_adicionais'),true);

			$informacoes_adicionais['data_documento'] = $data_documento;
			$informacoes_adicionais = json_encode($informacoes_adicionais);
			$campo_informacoes_adicionais = ", parametros_adicionais = '{$informacoes_adicionais}' ";
		}

		$sql = "DELETE FROM tbl_comunicado_posto_blackedecker
				WHERE comunicado = $comunicado;";

		$sql .= "UPDATE tbl_comunicado SET
					produto                = $produto                    ,
					familia                = $aux_familia                ,
					linha                  = $aux_linha                  ,
					versao                 = $aux_versao                 ,
					extensao               = LOWER($aux_extensao)        ,
					descricao              = $aux_descricao              ,
					mensagem               = $aux_mensagem               ,
					tipo                   = $aux_tipo                   ,
					obrigatorio_os_produto = $aux_obrigatorio_os_produto ,
					obrigatorio_site       = $aux_obrigatorio_site       ,
					posto                  = $posto                      ,
					ativo                  = $aux_ativo                  ,
					tipo_posto             = $aux_tipo_posto             ,
					remetente_email        = '$remetente_email'          ,
					data 				   = CURRENT_DATE                ,
					video                  = '$link_video'
					$update_serie
					$campo_informacoes_adicionais
				WHERE comunicado = $comunicado
				AND   fabrica    = $login_fabrica;";

	}
	$res = pg_query($con,$sql);

	if(strlen(pg_last_error($con)) > 0){
		$msg_erro["msg"][] = pg_last_error($con);
	}

}

if (count($msg_erro["msg"]) == 0) {
	if ($operacao == "insert") {
		$comunicado = pg_fetch_result($res, 0, "comunicado");
	}
}

if (count($msg_erro["msg"]) > 0 && strlen($comunicado) > 0) {

	$sql = "DELETE FROM tbl_comunicado_produto
			WHERE comunicado = $comunicado";
	$res = pg_query ($con,$sql);

	if(strlen(pg_last_error()) > 0){
		$msg_erro["msg"][] = pg_last_error();
	}

}

# Múltiplos Comunicados
# HD 6392
$replicar = $_POST['PickList'];
$numero_multiplos = 0;

$array_prods = array();
if (count($replicar) > 0 && $multiplo == 'muitos' && count($msg_erro["msg"]) == 0) {

	for ($i = 0; $i < count($replicar); $i++){
		$p = trim($replicar[$i]);
		if (strlen($p)==0) continue;
		$sql = "SELECT tbl_produto.produto
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE tbl_produto.referencia='$p'
				and tbl_linha.fabrica=$login_fabrica
				and tbl_produto.fabrica_i=$login_fabrica";
		$res = pg_query ($con,$sql);
		if (pg_numrows($res)==1){
			$prod = pg_result($res,0,0);
			$array_prods[] = $prod;
			$numero_multiplos++;
			$sql = "SELECT comunicado
					FROM tbl_comunicado_produto
					WHERE comunicado = $comunicado
					AND   produto    = $prod ";
			$res = pg_query ($con,$sql);
			if (pg_num_rows($res)==0){
				$sql = "INSERT INTO tbl_comunicado_produto (comunicado,produto )
						VALUES ($comunicado,$prod)";
				$res = pg_query ($con,$sql);

				if(strlen(pg_last_error()) > 0){
					$msg_erro["msg"][] = pg_last_error();
				}
			}
		}
	}
} else {
	$lista_produtos = array();
	for($x = 0; $x < count($replicar); $x++){
		$sql = "SELECT 	tbl_produto.produto,
						tbl_produto.referencia,
						tbl_produto.descricao
				FROM tbl_produto
				WHERE tbl_produto.referencia = '$replicar[$x]'
				AND tbl_produto.fabrica_i = $login_fabrica";
		$resProd = pg_query ($con,$sql);
		$mult_produto    = trim(pg_result($resProd,0,'produto'));
		$mult_referencia = trim(pg_result($resProd,0,'referencia'));
		$mult_descricao  = trim(pg_result($resProd,0,'descricao'));
		array_push($lista_produtos,array($mult_produto,$mult_referencia,$mult_descricao));

	}
}

if (strlen($linha) == 0 AND $produto == 'null' AND $numero_multiplos == 0 AND count($replicar) == 0 && !$sem_produto && $login_fabrica != 148) {
	$msg_erro["msg"][] = traduz("Informe o produto");
	$msg_erro["campos"][] = "produto";
	#$msg_erro .= "Linha ".$linha." PRODUTO ".$produto." MULTIPLO ".$numero_multiplos." REPLICAR ".$replicar;
}

if ( in_array($login_fabrica, array(11,172)) ) {
	if ($tipo == ''){
		$msg_erro["msg"][] = traduz("Informe o tipo de comunicado");
	}
	if ($aux_descricao=='null'){
		$msg_erro["msg"][] = traduz("Informe o Título");
	}
}

// Rotina que faz o upload do arquivo

if (count($msg_erro["msg"]) == 0 and $ativo == 't' && !in_array($login_fabrica, [148,183])) {
	// Formulário postado... executa as ações
	if(strlen($arquivo["tmp_name"]) == 0 and $tipo <> 'Vídeo'){

		$msg_erro['msg'][] = traduz('Favor fazer upload do arquivo !');
		$msg_erro['campos'][] = 'arquivo';

	} else if (strlen($arquivo['tmp_name']) > 0 && $arquivo['tmp_name'] != 'none' && empty($link_video)) {

		// Verifica o MIME-TYPE do arquivo
		if (!preg_match("/\/(rtf|ppt|mp4|txt|pdf|x-pdf|acrobat|msword|doc|text|vnd.msword|vnd.ms-word|winword|word|x-msw6|x-msword|zz-winassoc-doc|pjpeg|jpeg|jpg|png|gif|bmp|pps|vnd.ms-excel|xls|richtext|plain|vnd.ms-powerpoint|zip|wps-office.pdf|x-zip-compressed)$/", $arquivo["type"]) && !in_array($login_fabrica, [167, 203])){
			$msg_erro["msg"][] = traduz("Arquivo com formato inválido");
		}

		if (count($msg_erro["msg"]) == 0) {
			// Pega extensão do arquivo
			$ext = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
			$aux_extensao = "'$ext'";

			// Gera um nome único para a imagem
			$nome_anexo = $comunicado.".".$ext;

			// Caminho de onde a imagem ficará + extensao
			$imagem_dir = "../comunicados/".strtolower($nome_anexo);

			// Exclui anteriores, qquer extensao
			//@unlink($imagem_dir);
			//echo $arquivo["tmp_name"];
			// Faz o upload da imagem

			if (count($msg_erro["msg"]) == 0) {
				//move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
				if ($S3_online) {
					if ($s3->uploadFileS3($comunicado, $arquivo)) {
						$sql =	"UPDATE tbl_comunicado
									SET extensao   = LOWER($aux_extensao)
								  WHERE comunicado = $comunicado
									AND fabrica    = $login_fabrica";
						$res = pg_query($con,$sql);

						//estarta rotina que gera arquivos zips das vistas explodidas
						//echo $aux_tipo;
						if ($aux_tipo == "'Vista Explodida'" AND $login_fabrica == 1) {
							if (!empty($array_prods)) {
								$parametros = 'produtos='.implode(',', $array_prods);
							}else{
								$parametros = 'produtos='.$produto;
							}

							$sql_in = "INSERT INTO tbl_relatorio_agendamento (
										admin,
										programa,
										parametros,
										fabrica,
										titulo,
										agendado
								)VALUES(
									$login_admin,
									'/assist/admin/gera_zip_vista_explodida.php',
									'$parametros',
									$login_fabrica,
									'Atualiza ZIPs Vistas Explodidas',
									TRUE
								);";
							$res_in = pg_query($con,$sql_in);
						}

					} else {
						$msg_erro["msg"][] = traduz("O arquivo não foi enviado!!! ") . $s3->_erro; // . $erroS3;
					}

				} else {
					if (copy($arquivo["tmp_name"], $imagem_dir)) {
						$sql =	"UPDATE tbl_comunicado SET
									extensao  = LOWER($aux_extensao)
								WHERE comunicado = $comunicado
								AND   fabrica    = $login_fabrica";
						$res = pg_query($con,$sql);
						$msg_erro["msg"][] = pg_last_error($con);
					}else{
						$msg_erro["msg"][] = traduz("O arquivo não enviado");
					}
				}
			}
		}
	}
} else if (in_array($login_fabrica, [148,183]) && count($msg_erro["msg"]) == 0) {
	$anexo_chave = $_POST["anexo_chave"];

	if ($anexo_chave != $comunicado) {
		$tdocs = new TDocs($con, $login_fabrica, "comunicados");

		$anexos = $tdocs->getByHashTemp($anexo_chave);

		if (!empty($anexos)) {
			if (!$tdocs->updateHashTemp($anexo_chave, $comunicado)) {
				$msg_erro["msg"][] = traduz("Erro ao gravar anexos");
			}
		}
	}
}

///////////////////////////////////////////////////
if (count($msg_erro["msg"]) == 0) {

	$res = pg_query ($con,"COMMIT TRANSACTION");
	// HD 16279
	$msg = ($operacao == "insert") ? traduz("Cadastro realizado com Sucesso") : traduz("Cadastro alterado com Sucesso");

	$produto_referencia = "";
	$produto_descricao  = "";
	$descricao          = "";
	$linha              = "";
	$familia            = "";
	$extensao           = "";
	$versao_produto     = "";
	$tipo               = "";
	$link_video         = "";
	$ativo              = "";
	$versao             = "";
	$data_documento     = "";

}else{
	if ($operacao == "insert") {
		unset($comunicado);
	}

	$produto_serie      = $_POST["produto_serie"];
	$produto_referencia = $_POST["produto_referencia"];
	$produto_descricao  = $_POST["produto_descricao"];
	$descricao          = $_POST['descricao'];
	$linha              = $_POST['linha'];
	$familia            = $_POST['familia'];
	$extensao           = $_POST['extensao'];
	$versao_produto     = $_POST['versao_produto'];
	$tipo               = $_POST['tipo'];
	$link_video         = $_POST['link_video'];
	$ativo              = $_POST['ativo'];
	$versao             = $_POST['versao'];
	$data_documento     = $_POST['data_documento'];

	$res = pg_query ($con,"ROLLBACK TRANSACTION");

}

}

if (strlen($comunicado) > 0) {
$sql = "SELECT  tbl_produto.referencia AS prod_referencia,
				tbl_produto.descricao  AS prod_descricao ,
				tbl_comunicado.* ,
				tbl_comunicado.parametros_adicionais AS parametros_comunicado,
				tbl_posto.nome AS posto_nome ,
				tbl_posto_fabrica.codigo_posto
		FROM    tbl_comunicado
		LEFT JOIN tbl_produto USING (produto)
		LEFT JOIN tbl_posto   ON tbl_comunicado.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica ON tbl_comunicado.posto = tbl_posto_fabrica.posto AND tbl_comunicado.fabrica = tbl_posto_fabrica.fabrica
		WHERE   tbl_comunicado.comunicado = $comunicado
		AND     tbl_comunicado.fabrica    = $login_fabrica";
$res = pg_query ($con,$sql);

if (pg_numrows ($res) > 0) {
	$produto_serie 			= trim(pg_fetch_result($res,0, 'serie'));
	$produto_referencia     = trim(pg_result($res,0,'prod_referencia'));
	$produto_descricao      = trim(pg_result($res,0,'prod_descricao'));
	$descricao              = trim(pg_result($res,0,'descricao'));
	$extensao               = strtolower(trim(pg_result($res,0,'extensao')));
	$tipo                   = trim(pg_result($res,0,'tipo'));
	$link_video             = trim(pg_result($res,0,'video'));
	$ativo                  = trim(pg_result($res,0,'ativo'));
	$linha                  = trim(pg_result($res,0,'linha'));
	$familia                = trim(pg_result($res,0,'familia'));
	$versao_produto         = trim(pg_result($res,0,'versao'));
	$obrigatorio_os_produto = (pg_result($res,0,'obrigatorio_os_produto'));
	$obrigatorio_site		= (pg_result($res,0,'obrigatorio_site'));
	$tipo_posto             = trim(pg_fetch_result($res,0,'tipo_posto'));
	$parametros_comunicado  = pg_fetch_result($res,0,'parametros_comunicado');
	$parametros_comunicado  = json_decode($parametros_comunicado,true);
	$data_documento = $parametros_comunicado['data_documento']; 

	if($login_fabrica==6)	{
		$mensagem                = trim(pg_result($res,0,'mensagem'));
	}

	$btn_lista = "ok";
}

# Comunicados multiplos PRODUTOS
# HD 6392
$sql = "SELECT 	tbl_produto.produto,
				tbl_produto.referencia,
				tbl_produto.descricao
		FROM tbl_comunicado_produto
		JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
		WHERE tbl_comunicado_produto.comunicado = $comunicado";
$resProd = pg_query ($con,$sql);
$lista_produtos = array();
for ($i=0; $i<pg_numrows ($resProd); $i++){
	$mult_produto    = trim(pg_result($resProd,$i,produto));
	$mult_referencia = trim(pg_result($resProd,$i,referencia));
	$mult_descricao  = trim(pg_result($resProd,$i,descricao));
	array_push($lista_produtos,array($mult_produto,$mult_referencia,$mult_descricao));
}
}

if (trim($btn_acao) == "apagar") {
$res = pg_query ($con,"BEGIN TRANSACTION");

$comunicado = $_POST["apagar"];

$sql = "SELECT extensao FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
$res = pg_query ($con,$sql);
if (count($msg_erro["msg"]) == 0) {
	$extensao = @pg_result($res,0,0);
}

//hd 9892
$sql=" SELECT comunicado
		FROM tbl_comunicado_produto
		WHERE comunicado=$comunicado ";
$res=pg_query($con,$sql);
if(pg_numrows($res) > 0){
	$sql2 = "DELETE FROM tbl_comunicado_produto
			WHERE comunicado = $comunicado";
	$res2 = pg_query ($con,$sql2);
}
$sql = "DELETE  FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
$res = pg_query ($con,$sql);
if (count($msg_erro["msg"]) == 0) {
	if ($S3_online and $s3->temAnexos($comunicado)) {
		if (!$s3->excluiArquivoS3($s3->attachList[0]))
			$msg_erro["msg"][] = $s3->_erro;
	} else {
		$imagem_dir = "../comunicados/".$comunicado.".".$extensao;
		if (is_file($imagem_dir)){
			if (!unlink($imagem_dir)){
				$msg_erro["msg"][] = traduz("Não foi possível excluir arquivo");
			}
		}
	}
}
//var_dump($msg_erro);
if (count($msg_erro["msg"]) == 0){
	$res = pg_query ($con,"COMMIT TRANSACTION");
	header("Location: $PHP_SELF");
	exit;
}

$produto_referencia     = $_POST["produto_referencia"];
$familia                = $_POST["familia"];
$linha                  = $_POST["linha"];
$produto_descricao      = $_POST["produto_descricao"];
$descricao              = $_POST['descricao'];
$extensao               = $_POST['extensao'];
$tipo                   = $_POST['tipo'];
$mensagem               = $_POST['mensagem'];
$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
$obrigatorio_site       = $_POST['obrigatorio_site'];
$ativo                  = $_POST['ativo'];
$versao                 = $_POST['versao'];
$versao_produto         = $_POST['versao_produto'];
$link_video             = $_POST['link_video'];
$data_documento		= $_POST['data_documento'];

$res = pg_query ($con,"ROLLBACK TRANSACTION");
}

if (trim($btn_acao) == "apagararquivo") {

$comunicado = $_POST["apagar"];

if (is_object($s3)) {
	if (!$s3->excluiArquivoS3($comunicado)) {
		$msg_erro["msg"][] = $s3->_erro;	
	} else {
		$sql = "UPDATE tbl_comunicado SET extensao = NULL WHERE comunicado = $comunicado";
		$res = pg_query($con,$sql);

		header("Location: $PHP_SELF?comunicado=$comunicado");
		exit;
	}
} else {
	$sql = "SELECT extensao FROM tbl_comunicado WHERE comunicado = $comunicado";
	$res = pg_query($con,$sql);
	$imagem_dir = "../comunicados/".$comunicado.".".pg_result($res,0,0);
	if (is_file($imagem_dir)) {
		if (!unlink($imagem_dir)){
			$msg_erro["msg"][] = traduz("Não foi possível excluir arquivo");
			} else {
				$sql = "UPDATE tbl_comunicado SET extensao = NULL WHERE comunicado = $comunicado";
				$res = pg_query($con,$sql);

				header("Location: $PHP_SELF?comunicado=$comunicado");
			exit;
		}
	}
}
}

$layout_menu = "tecnica";
$titulo = traduz("Cadastramento de Comunicados / Vistas Explodidas / Fotos / Boletins");
$title =  traduz("CADASTRO DE COMUNICADOS / VISTAS EXPLODIDAS / FOTOS / BOLETINS");

include 'cabecalho_new.php';

$plugins = array(
"mask",
"shadowbox",
"tooltip",
"datepicker"
);

include("plugin_loader.php");

?>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script language='javascript'>

function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
}
}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
if (tipo == "codigo" ) {
	var xcampo = campo;
}

if (tipo == "nome" ) {
	var xcampo = campo2;
}

if (xcampo.value != "") {
	var url = "";
	url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
	janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
	janela.codigo  = campo;
	janela.nome    = campo2;
	janela.focus();
}
else{
	alert('<?=traduz("Informe toda ou parte da informação para realizar a pesquisa")?>');
	}
}

///////////////////////////////////////////////////////////

var singleSelect = true;  // Allows an item to be selected once only
var sortSelect = true;  // Only effective if above flag set to true
var sortPick = true;  // Will order the picklist in sort sequence

// Initialise - invoked on load
function initIt() {
	var pickList = document.getElementById("PickList");
	var pickOptions = pickList.options;
	pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
}

// Adds a selected item into the picklist
function addIt(arrayMult = null) {

	if (arrayMult == null) {
		if ($('#produto_referencia_multi').val()=='')
			return false;

		if ($('#produto_descricao_multi').val()=='')
			return false;


		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
		pickOptions[pickOLength] = new Option($('#produto_referencia_multi').val()+" - "+ $('#produto_descricao_multi').val());
		pickOptions[pickOLength].value = $('#produto_referencia_multi').val();

		$('#produto_referencia_multi').val("");
		$('#produto_descricao_multi').val("");

		if (sortPick) {
			var tempText;
			var tempValue;
			// Sort the pick list
			while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
				tempText = pickOptions[pickOLength-1].text;
				tempValue = pickOptions[pickOLength-1].value;
				pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
				pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
				pickOptions[pickOLength].text = tempText;
				pickOptions[pickOLength].value = tempValue;
				pickOLength = pickOLength - 1;
			}
		}

		pickOLength = pickOptions.length;
		$('#produto_referencia_multi').focus();
	} else {

		$.each(arrayMult, function (idProduto, dadosProd) {

			var pickList = document.getElementById("PickList");
			var pickOptions = pickList.options;
			var pickOLength = pickOptions.length;
			pickOptions[pickOLength] = new Option(dadosProd['referencia']+" - "+ dadosProd['descricao']);
			pickOptions[pickOLength].value = dadosProd['referencia'];

			$('#produto_referencia_multi').val("");
			$('#produto_descricao_multi').val("");

			if (sortPick) {
				var tempText;
				var tempValue;
				// Sort the pick list
				while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
					tempText = pickOptions[pickOLength-1].text;
					tempValue = pickOptions[pickOLength-1].value;
					pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
					pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
					pickOptions[pickOLength].text = tempText;
					pickOptions[pickOLength].value = tempValue;
					pickOLength = pickOLength - 1;
				}
			}
			pickOLength = pickOptions.length;
			$('#produto_referencia_multi').focus();
        });
	}
	pickOLength = pickOptions.length;
	$('#produto_referencia_multi').focus();
}

// Deletes an item from the picklist
function delIt() {
	var pickList = document.getElementById("PickList");
	var pickIndex = pickList.selectedIndex;
	var pickOptions = pickList.options;
	while (pickIndex > -1) {
		pickOptions[pickIndex] = null;
		pickIndex = pickList.selectedIndex;
	}
}
// Selection - invoked on submit
function selIt(btn) {
	var pickList = document.getElementById("PickList");
	var pickOptions = pickList.options;
	var pickOLength = pickOptions.length;
/*	if (pickOLength < 1) {
		alert("Nenhuma produto selecionado!");
		return false;
	}*/
	for (var i = 0; i < pickOLength; i++) {
		pickOptions[i].selected = true;
	}
/*	return true;*/
}

var usa_versao_produto = <?php echo ($usa_versao_produto) ? 'true' : 'false'; ?>;

$(function() {

$.datepickerLoad(["data_documento"]);

Shadowbox.init();
$("span[rel=lupa]").click(function () {
    $.lupa($(this), Array("posicao"));
});

	$("#tipo").change(function(){
		var tipo = $("#tipo").val();

		if (tipo == "Catálogo de Acessórios") {
			$("#id_um").hide();
			$("#box_produtos").hide();
			$("#box_catalogo").show();
		} else {
			$("#box_catalogo").hide();

			if (tipo == "Vídeo") {
				$("#video").show();
				$("#anexo").hide();
			} else {
				$("#video").hide();
				$("#anexo").show();
			}
			if (usa_versao_produto) {
				(tipo === 'Vista Explodida') ? $("#div_versao").css('visibility','visible').show() : $("#div_versao").css('visibility', 'hidden').hide();
			}
		}

	});

	if ($("#tipo").val() != ''){
		$("#tipo").change();
	}

	$("#btnPopover").popover();
	$("#btnPopover1").popover();

	$("#ordem_producao").numeric();
	$("#ordem_producao_pesquisa").numeric();
	
});

function retorna_produto (retorno) {

	if(retorno.posicao == "pesquisa"){
		$("#psq_produto_referencia").val(retorno.referencia);
		$("#psq_produto_nome").val(retorno.descricao);

	}else if(retorno.posicao == "um_produto"){

		$("#produto_referencia").val(retorno.referencia);
	$("#produto_descricao").val(retorno.descricao);

	}else if(retorno.posicao == "multi_produto"){

		$("#produto_referencia_multi").val(retorno.referencia);
	$("#produto_descricao_multi").val(retorno.descricao);

	}

}

function toogleProd(radio){

	var obj = document.getElementsByName('radio_qtde_produtos');
	/*for(var x=0 ; x<obj.length ; x++){*/

	if(!confirm('<?=traduz("Deseja continuar? As Informações de Número de Série e Produto serão perdidas.")?>')) {
	return false;
	}else{
		$("#produto_serie").val("");
		$("#produto_serie_multi").val("");
		$("#produto_referencia").val("");
		$("#produto_descricao").val("");
		$("#produto_descricao_multi").val("");
		$("#produto_referencia_multi").val("");
		$("#PickList > option").remove();

		if (obj[0].checked){
			$('#id_um').show("slow");
			$('#id_multi').hide("slow");
		}
		if (obj[1].checked){
			$('#id_um').hide("slow");
			$('#id_multi').show("slow");
		}
	}
	// $("#error").removeClass("error");

	// if (obj[0].checked){
	// 	$('#id_um').show("slow");
	// 	$('#id_multi').hide("slow");
	// }
	// if (obj[1].checked){
	// 	$('#id_um').hide("slow");
	// 	$('#id_multi').show("slow");
	// }

}
</script>
<style>

<?php
if (in_array($login_fabrica, [148])) { ?>

#box_produtos, #id_um, #id_multi, #produto_pesquisa {
	display: none;
}

<?php
} ?>

</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
<div class="alert alert-error">
<h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
</div>
<?php
}

if(strlen($msg) > 0){
?>
<div class="alert alert-success">
	<h4><?php echo $msg ?></h4>
</div>
<?php
}
?>

<div class="row"> 
<b class="obrigatorio pull-right"> * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form enctype="multipart/form-data" name="frm_comunicado" class="tc_formulario" method="post" action="<?php echo $PHP_SELF ?>">

<input type="hidden" name="comunicado"    value="<?php echo $comunicado ?>">
<input type='hidden' name='apagar'        value=''>
<input type='hidden' name='btn_acao'      value=''>
<input type="hidden" name="posto" value="<?php echo $posto ?>">

<div class="titulo_tabela"><?=traduz("Informações de Cadastro")?></div>

<br />

<div class="row-fluid">
	<div class="span2"></div>

	<div class="span4">
		<div class='control-group <?=(in_array("tipo_comunicado", $msg_erro["campos"])) ? "error" : ""?>'>

			<label class='control-label' for='tipo'><?=traduz('Tipo do Comunicado')?></label>
			<div class='controls controls-row'>
				<div class='span12 input-append'>
					<h5 class='asteristico'>*</h5>
					<?php
						$tipo_comunicado = include('menus/vista_tipo_array.php');
						if (in_array($login_fabrica, [30])) {
							unset($tipo_comunicado['Alterações Técnicas']);
							unset($tipo_comunicado['Manual Técnico']);
						}
						echo array2select('tipo', 'tipo', $tipo_comunicado, $tipo, "class='span12'", "Selecione");
						?>
				</div>

			</div>
		</div>
	</div>
	<?php if (in_array($login_fabrica, array(157))) {?>
	<div id="box_catalogo" style="display: none;" class="span4">
		<div class='control-group  <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>' id="error">
			<label class='control-label' for='descricao'><?=traduz("Descrição / Titulo")?></label>
			<div class='controls controls-row'>
				<div class='span12 input-append'>
					<input type='text' name='descricao' value='<?php echo $descricao; ?>' maxlength='50' class='span12'>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
	<?php if (in_array($login_fabrica, array(3, 11, 14, 15, 91, 161, 172))) {//HD 198907 ?>
		<div class="span4">
			<div class='control-group'>

				<label class='control-label' for='descricao'><?=traduz("Titulo")?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type='text' name='descricao' value='<?php echo $descricao; ?>' maxlength='50' class='span12'>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<?php if ($login_fabrica == 175){ ?>
		<div class="span4">
			<div class='control-group <?=(in_array("ordem_producao", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='ordem_producao'><?=traduz("Ordem de Produção")?></label>
				<div class='controls controls-row'>
					<div class='span8' >
						<!--<h5 class='asteristico'>*</h5>-->
						<input type='text' numeric="true" id="ordem_producao" name='ordem_producao' value='<?php echo $ordem_producao; ?>' maxlength='50' class='span12'>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
<?php if ($login_fabrica == 161){ ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class='control-group <?=(in_array("data_documento", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='ordem_producao'>Data Documento</label>
					<div class='controls controls-row'>
						<div class='span8' >
							<input type='text' numeric="true" id="data_documento" name='data_documento' value='<?php echo $data_documento; ?>' maxlength='50' class='span12'>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php }

if (count($lista_produtos) > 0){
	$display_um_produto    = "display:none";
	$display_multi_produto = "";
	$display_um            = "";
	$display_multi         = " CHECKED ";
}else{
	$display_um_produto    = "";
	$display_multi_produto = "display:none";
	$display_um            = " CHECKED ";
	$display_multi         = "";
}
?>
<div class="row-fluid" id="box_produtos">
	<div class="span2"></div>

	<div class="span8">

		<div class="row-fluid">

			<div class="span6">

				<?php
				$titulo_produto = traduz("
					Para selecionar vários produtos, clique na opção Vários Produtos e
					adicione os produtos a lista. Todos os produtos da lista serão
					referenciados ao comunicado. Para remover algum produto,
					selecione-o na lista e clique no botão Remover à seguir.
				");
				?>

				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>' id="error">

					<label class='control-label' for='codigo_posto'>
						<?=traduz("Para:")?>
						<i id="btnPopover1" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Informação" data-content="<?=$titulo_produto?>" class="icon-question-sign"></i>
					</label>
					<div class='controls controls-row'>
						<h5 class='asteristico' style="margin-top: 3px !important;">*</h5>
						<div class='span12 input-append'>
							<label class="checkbox">
								<input type="radio" name="radio_qtde_produtos" value=<?=traduz('um')?> <?=$display_um?> onClick='javascript:toogleProd(this)'>
							  <?=traduz("Um produto")?>
							</label>
						</div>

					</div>

				</div>

			</div>

			<div class="span6">

				<div class='control-group'>

					<label class='control-label' for='codigo_posto'>&nbsp;</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<label class="checkbox">
								<input type="radio" name="radio_qtde_produtos" value="muitos"<?=$display_multi?> onClick='javascript:toogleProd(this)'>
								<?=traduz("Vários Produtos")?>
							</label>
						</div>
					</div>
				</div>

			</div>

		</div>

	</div>

</div>



<div id='id_um' style='<?php echo $display_um_produto;?>'>

	<?php if (in_array($login_fabrica, array(169,170))){ ?>
	<div class='row-fluid'>
		<div class="span2"></div>
		<div class="span4">
	    <div class='control-group <?=(in_array('produto_serie', $msg_erro['campos'])) ? "error" : "" ?>' >
		<label class="control-label" for="produto_serie"><?=traduz("Número de Série")?></label>
		<div class="controls controls-row">
		    <div class="span12 input-append" id='fricon'>

			<input id="produto_serie" name="produto_serie" class="span10" type="text" value="<?=$produto_serie?>" maxlength="30" />
			<span class="add-on lupa_serie" rel="lupa" style='cursor: pointer;'>
				<i class='icon-search'></i>
			</span>
			<input type="hidden" name="lupa_config" tipo="produto" posicao="um_produto" parametro="numero_serie" <?=(in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""?> <?=($usaProdutoGenerico) ? "produto-generico='true'" : ""?> />
		    </div>
		</div>
	    </div>
	</div>
		<div class="span2"></div>
	</div>
	<?php } ?>

	<div class='row-fluid'>

	<div class='span2'></div>

	<div class='span3'>
	    <div class='control-group'>
		<label class='control-label' for='produto_referencia'><?=traduz("Ref. Produto")?></label>
		<div class='controls controls-row'>
		    <div class='span10 input-append'>
			<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?php echo $produto_referencia ?>" >
			<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
			<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posicao="um_produto" />
		    </div>
		</div>
	    </div>
	</div>
	<div class='span5'>
	    <div class='control-group'>
		<label class='control-label' for='produto_descricao'><?=traduz("Descrição Produto")?></label>
		<div class='controls controls-row'>
		    <div class='span11 input-append'>
			<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
			<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
			<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posicao="um_produto" />
		    </div>
		</div>
	    </div>
	</div>

	<div class='span2'></div>

    </div>

<?php if ($usa_versao_produto){ ?>
<div id="div_versao" style="visibility: hidden; display: none;">
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span3">

			<div class='control-group'>

				<?php

					if(in_array($login_fabrica, array(151))){
							$max_versao_produto = 3;
						}

				?>

					<label class='control-label' for='versao'><?=traduz("Versão")?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="versao" id="versao" class="span12" value="<?=$versao_produto?>" size="8" maxlength="<?=$max_versao_produto?>" title='<?=traduz("Digite um número ou letra para diferenciar versões do produto. Sem informação da versão, o documento será valido para todas as versões do produto. Com a informação da versão, este documento será válido (e apresentado) apenas para a versão selecionada.");?>' />
						</div>
					</div>

				</div>

			</div>
		</div>
	</div>
	<?php } ?>

</div>

<!-- Multi Produtos -->
<div id='id_multi' style='<?php echo $display_multi_produto;?>'>

<div class='row-fluid'>

	<div class='span2'></div>

	<div class='span2'>
	    <div class='control-group'>
		<label class='control-label' for='produto_referencia_multi'><?=traduz("Ref. Produto")?></label>
		<div class='controls controls-row'>
		    <div class='span10 input-append'>
			<input type="text" id="produto_referencia_multi" name="produto_referencia_multi" class='span12' maxlength="20" value="<?php echo $produto_referencia ?>" >
			<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
			<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posicao="multi_produto" />
		    </div>
		</div>
	    </div>
	</div>
	<div class='span4'>
	    <div class='control-group'>
		<label class='control-label' for='produto_descricao_multi'><?=traduz("Descrição Produto")?></label>
		<div class='controls controls-row'>
		    <div class='span11 input-append'>
			<input type="text" id="produto_descricao_multi" name="produto_descricao_multi" class='span12' value="<? echo $produto_descricao ?>" >
			<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
			<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posicao="multi_produto" />
		    </div>
		</div>
	    </div>
	</div>

	<div class='span2'>
		<label>&nbsp;</label>
		<input type='button' name='adicionar' id='adicionar' value='Adicionar' class='btn btn-success' onclick='addIt();' style="width: 100%;">
	</div>

	<div class='span2'></div>

    </div>

    <p class="tac">
	<?=traduz("Selecione o produto e clique em <strong>Adicionar</strong>")?>
    </p>

    <div class='row-fluid'>

	<div class='span2'></div>

	<div class='span8'>

		<select multiple size='6' id="PickList" name="PickList[]" class='span12'>

			<?php
				if (count($lista_produtos)>0){
					for ($i=0; $i<count($lista_produtos); $i++){
						$linha_prod = $lista_produtos[$i];
						echo "<option value='".$linha_prod[1]."'>".$linha_prod[1]." - ".$linha_prod[2]."</option>";
					}
				}
			?>

			</select>

			<p class="tac">
				<input type="button" value=<?=traduz("Remover")?> onclick="delIt();" class='btn btn-danger' style="width: 126px;">
			</p>

	</div>

	<div class='span2'></div>

    </div>

</div>

<?php
if (in_array($login_fabrica, [19, 148])) {
?>

<br />
<?php
if ($login_fabrica != 148) {
?>
	<div class="tac">
		<?=traduz("Preencha os campos abaixo para documentos de toda uma linha.")?>
	</div>
<?php
}
?>

<div class="row-fluid">

	<div class='span2'></div>

	<div class='span4'>
    <div class='control-group'>
	<label class='control-label' for='descricao'><?=traduz("Titulo / Descrição")?></label>
	<div class='controls controls-row'>
	    <div class='span10 input-append'>
		<input type="text" name="descricao" value="<?php echo $descricao ?>" maxlength="50" class='span12'>
	    </div>
	</div>
    </div>
</div>

<div class='span4'>
    <div class='control-group <?=(in_array('linha', $msg_erro['campos'])) ? "error" : "" ?>'>
	<label class='control-label' for='produto_referencia_multi'><?=traduz("Linha")?></label>
	<div class='controls controls-row'>
		<?php
		if ($login_fabrica == 148) { ?>
			<span class="asteristico">*</span>
		<?php
		}
		?>
	    <div class='span10 input-append'>
		<?php
					$sql = "SELECT  *
							FROM    tbl_linha
							WHERE   tbl_linha.fabrica = $login_fabrica
							AND     tbl_linha.ativo IS TRUE
							ORDER BY tbl_linha.nome;";
					$resX = pg_query ($con,$sql);

					if (pg_numrows($resX) > 0) {
						echo "<select class='span12' name='linha'>";
							echo "<option value=''>".traduz("Selecione")."</option>";

							for ($x = 0 ; $x < pg_numrows($resX) ; $x++){
								$aux_linha = trim(pg_result($resX,$x,linha));
								$aux_nome  = trim(pg_result($resX,$x,nome));

								echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>";
							}

						echo "</select>";
					}
					?>
	    </div>
	</div>
    </div>
</div>

</div>


<?php } 

if (in_array($login_fabrica, [148])) { 
	?>
	<div class="row-fluid">
		<div class="span2"></div>
		    <div class='span4'>
	    <div class='control-group'>
		<label class='control-label' for='produto_referencia_multi'><?=traduz("Tipo Posto")?></label>
		<div class='controls controls-row'>
		    <div class='span10 input-append'>
			<?php
						$sql_tipo_posto = "SELECT  descricao,tipo_posto
								FROM    tbl_tipo_posto
								WHERE   tbl_tipo_posto.fabrica = $login_fabrica
								AND     tbl_tipo_posto.ativo IS TRUE
								ORDER BY tbl_tipo_posto.descricao;";
						$res_tipo_posto = pg_query ($con,$sql_tipo_posto);

						if (pg_num_rows($res_tipo_posto) > 0) { ?>
							<select class='span12' name='tipo_posto'>
								<option value=''><?=traduz("Selecione")?></option>

								<?php
								while ($tp_posto = pg_fetch_array($res_tipo_posto)) {
									$tipo_posto_descricao = $tp_posto['descricao'];
									$tipo_posto_id  	  = $tp_posto['tipo_posto'];
								?>
									<option value="<?= $tipo_posto_id ?>" <?= ($tipo_posto_id == $tipo_posto) ? "selected" : "" ?> > <?= $tipo_posto_descricao ?></option>
								<?php	
								} ?>
								
							</select>
						<?php
						}
						?>
		    </div>
		</div>
	    </div>
	</div>
	</div>
<?php
} ?>

<div class="row-fluid">

	<div class='span2'></div>

<div class='span3'>
    <div class='control-group'>
	<label class='control-label' for='ativo'><?=traduz("Ativo / Inativo")?></label>
	<div class='controls controls-row'>
	    <div class='span10 input-append'>
		<label class="checkbox inline">
			<?=traduz("Ativo")?> <input type="radio" name="ativo" value='t' checked>
		</label>
		<label class="checkbox inline">
						<?=traduz("Inativo")?> <input type="radio" name="ativo" value='f' <?php if ($ativo == 'f'){ echo "checked"; } ?> >
					</label>
	    </div>
	</div>
    </div>
</div>

<?php if( in_array($login_fabrica, array(11,172)) ){ ?>

<div class='span5'>
    <div class='control-group'>
	<label class='control-label' for='obrigatorio_site'><?=traduz("Exibir na Tela de Entrada do Site?")?></label>
	<div class='controls controls-row'>
	    <div class='span10 input-append'>
		<label class="checkbox inline">
			Sim <input type='checkbox' name='obrigatorio_site' value='t' <?php if ($obrigatorio_site == "t") echo "checked"; ?> >
		</label>
	    </div>
	</div>
    </div>
</div>

<?php } ?>

<?php if(in_array($login_fabrica, array(6,14))){ ?>

<div class='span5'>
    <div class='control-group'>
	<label class='control-label' for='mensagem'><?=traduz("Mensagem")?></label>
	<div class='controls controls-row'>
	    <div class='span12 input-append'>
		<textarea name='mensagem' id="mensagem" rows='4' class='span12'><?php echo $mensagem?></textarea>
	    </div>
	</div>
    </div>
</div>

<?php } ?>

</div>

<?php
	if($tipo == "Vídeo"){
		$display_video = "";
		$display_anexo = "none";
	} else {
		$display_video = "none";
		$display_anexo = "";
	}

if (!in_array($login_fabrica, [148,183])) {
?>

	<!-- Anexo -->
	<div id="anexo" style="display:<?=$display_anexo?>;">

		<div class="row-fluid">

			<div class="span2"></div>

			<?php

			$titulo_arquivo = traduz("Para anexar uma VISTA EXPLODIDA você deve anexar apenas a imagem do produto em formato GIF, pois os sistema já informa para o posto as peças baseando-se na lista básica do produto.");

			?>

			<div class='span8'>
		    <div class='control-group'>
			<label class='control-label' for='arquivo'>
				<?=traduz("Anexo")?>
				<i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title=<?=traduz("Informação")?> data-content="<?=$titulo_arquivo?>" class="icon-question-sign"></i>
			</label>
			<div class='controls controls-row'>
			    <div class='span12 input-append'>
				<h5 class="asteristico">*</h5>
				<input type='file' name='arquivo' size='65' class='span12'>
			    </div>
			</div>
		    </div>
		</div>

		</div>

	</div>

	<!-- Link Video -->
	<div id="video" style="display:<?=$display_video?>;">

		<div class="row-fluid">

			<div class="span2"></div>

			<div class='span8'>
		    <div class='control-group'>
			<label class='control-label' for='link_video'><?=traduz("Link do Vídeo")?></label>
			<div class='controls controls-row'>
			    <div class='span12 input-append'>
				<h5 class="asteristico">*</h5>
				<input type='text' name='link_video' size='65' value="<?=$link_video?>" class='span12'>
			    </div>
			</div>
		    </div>
		</div>

		</div>

	</div>

<?php
} else {

$boxUploader = array(
    "div_id" => "div_anexos",
    "prepend" => $anexo_prepend,
    "context" => "comunicados",
    "unique_id" => $tempUniqueId,
    "hash_temp" => $anexoNoHash,
    "bootstrap" => true,
    "hidden_button" => false
);
include "../box_uploader.php";
}

?>

<div class="row-fluid">

	<div class="span2"></div>

	<div class='span4'>
    <div class='control-group'>

	<label class='control-label' for='obrigatorio_os_produto'><?=traduz("Obrigatório na OS")?></label>
	<label class='control-label' for='obrigatorio_os_produto'><?=traduz('Mostrar no cadastro da OS')?></label>
	<div class='controls controls-row'>
	    <div class='span12 input-append'>
		<label class="checkbox">
			<input type='checkbox' id="obrigatorio_os_produto" name='obrigatorio_os_produto' value='t' <?php if ($obrigatorio_os_produto == "t") echo "checked" ?> >
		</label>
	    </div>
	</div>
    </div>

</div>

<div class='span4'>
    <div class='control-group'>
	<label class='control-label' for='obrigatorio_site'><?=traduz("Exibir na Tela de Entrada do Site")?></label>
	<div class='controls controls-row'>
	    <div class='span12 input-append'>
		<label class="checkbox">
			<input type='checkbox' id="obrigatorio_site" name='obrigatorio_site' value='t' <?php if ($obrigatorio_site == "t") echo "checked" ?>>
		</label>
	    </div>
	</div>
    </div>
</div>
</div>

<?php
if (strlen($comunicado) > 0 AND strlen($extensao) > 0 && !in_array($login_fabrica, [148,183])) {

	$linkVE = "../comunicados/$comunicado." . $extensao;

	if ($S3_online /*and !file_exists($linkVE)*/) {
		if (!$s3->temAnexos($comunicado)):
			$linkVE = (file_exists($linkVE)) ? $linkVE:'#'; //Deshabilita o link se não existe local
		else:
			$linkVE = $s3->url;
		endif;
	}
?>

	<div class="tac"><?=traduz("Arquivo Anterior")?></div>

	<input type="hidden" name="extensao" value="<?php echo $extensao; ?>" />

	<div class="row-fluid">

		<div class="span2"></div>

		<div class="span4 tac">
			<input type="button" value=<?=traduz("Abrir Arquivo")?> class="btn" onclick="window.open('<?=$linkVE?>');" />
		</div>

		<div class="span4 tac">
			<input type='button' value=Apagar Arquivo' alt='<?=traduz('Clique aqui para excluir somente o arquivo anexado.')?>' class="btn" onclick='document.frm_comunicado.btn_acao.value="apagararquivo" ; document.frm_comunicado.apagar.value="<?php echo $comunicado; ?>" ; document.frm_comunicado.submit()' style='cursor:pointer;'>
		</div>

	</div>


	<P class="tac text-info">
		<?=traduz("A ação de alteração de um comunicado acarretará na exclusão do arquivo anteriormente enviado.<br>
		Para que isso não ocorra, lance um novo comunicado para este produto.")?>;
	</P>

<?php } ?>

<br />

<p class="tac">
	<input type="button" class="btn" value="Gravar" onclick='selIt();document.frm_comunicado.btn_acao.value="gravar"; document.frm_comunicado.submit();' >
	<?php if (strlen($comunicado) > 0) { ?>
		<input type="button" value='Apagar' style="margin-left: 80px;" alt=<?=traduz('Clique aqui para apagar.')?> class="btn btn-danger" onclick='document.frm_comunicado.btn_acao.value="apagar"; document.frm_comunicado.apagar.value="<?php echo $comunicado?>"; document.frm_comunicado.submit()' >
	<?php } ?>
</p>

<br />

</form>

<?php
if (strlen ($produto_referencia) > 0) {
	$sql = "SELECT  tbl_comunicado.comunicado                                    ,
					tbl_comunicado.familia                                       ,
					tbl_produto.referencia                    AS prod_referencia ,
					tbl_produto.descricao                     AS prod_descricao  ,
					tbl_comunicado.descricao                                     ,
					tbl_comunicado.extensao                                      ,
					tbl_comunicado.tipo                                          ,
					tbl_comunicado.mensagem                                      ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data            ,
					tbl_comunicado.obrigatorio_os_produto                        ,
					tbl_comunicado.obrigatorio_site
			FROM    tbl_comunicado
			JOIN    tbl_produto USING (produto)
			WHERE   tbl_produto.referencia = '$produto_referencia'
			ORDER BY tbl_comunicado.data DESC";
	$res = pg_query ($con,$sql);

	if (pg_numrows ($res) > 0) {

		echo "<table class='table table-bordered'>";
			echo "<tr class='titulo_coluna'>";
				if(in_array($login_fabrica, array(11, 15, 91, 172))){
					echo "<td class='tac'>".traduz("Descrição")."</td>";
				}
					if ($login_fabrica == 161) {
						echo "<td class='tac'>".traduz("Título")."</td>";
					}
					echo "<td class='tac'>".traduz("Tipo Comunicado")."</td>";
					echo "<td class='tac'>".traduz("Data")."</td>";
					echo "<td class='tac'>".traduz("Ação")."</td>";
				echo "</tr>";

			for ($i = 0; $i < pg_numrows ($res); $i++){
				$comunicado_prod        = trim(pg_result($res,$i,comunicado));
				$familia                = trim(pg_result($res,$i,familia));
				$produto_referencia_prod= trim(pg_result($res,$i,prod_referencia));
				$produto_descricao      = trim(pg_result($res,$i,prod_descricao));
				$descricao              = trim(pg_result($res,$i,descricao));
				$extensao               = trim(pg_result($res,$i,extensao));
				$tipo                   = trim(pg_result($res,$i,tipo));
				$mensagem               = trim(pg_result($res,$i,mensagem));
				$data                   = trim(pg_result($res,$i,data));
				$obrigatorio_os_produto = pg_result($res,$i,obrigatorio_os_produto);
				$obrigatorio_site       = pg_result($res,$i,obrigatorio_site);

				echo "<tr>";
					if(in_array($login_fabrica, array(11, 15, 91, 161, 172))){
						echo "<td class='tac'>$descricao</td>";
					}
					echo "<td class='tac' >$tipo</td>";
					echo "<td class='tac'>$data</td>";
				?>

					<td class='tac'><a href="vista_explodida_cadastro.php?comunicado=<?=$comunicado_prod?>" class="btn"><?=traduz("Alterar")?></a></td>
				<?php
				echo "</tr>";
			}
			echo "</table>";
		}
	}

	if($btn_acao == "pesquisar"){
		$tipo               	 = $_POST['psq_tipo'];
		$descricao          	 = $_POST['psq_descricao'];
		$produto_referencia 	 = $_POST['psq_produto_referencia'];
		$produto_descricao  	 = $_POST['psq_produto_nome'];
		$psq_produto_serie  	 = $_POST['psq_produto_serie'];
		$psq_familia        	 = $_POST['psq_familia'];
		$ordem_producao_pesquisa = $_POST['ordem_producao_pesquisa'];
		$linha 					 = $_POST['linha'];
	}else{
		$tipo               	 = "";
		$descricao          	 = "";
		$produto_referencia 	 = "";
		$produto_descricao  	 = "";
		$psq_produto_serie  	 = "";
		$psq_familia        	 = "";
		$ordem_producao_pesquisa = "";
		$linha 					 = "";
	}

?>

	<form name='frm_pesquisa' action='<?php echo $PHP_SELF; ?>' method='post' class="tc_formulario">

		<input type='hidden' name='btn_acao' value='pesquisar'>

		<div class="titulo_tabela"><?=traduz("Pesquisar Comunicados Cadastrados")?></div>

		<br />

		<div class="row-fluid">

			<div class="span2"></div>

	        <div class='span4'>
	            <div class='control-group'>
	                <label class='control-label' for='data_abertura'><?=traduz("Tipo")?></label>
	                <div class='controls controls-row'>
	                    <div class='span11'>
	                        <?=array2select('psq_tipo', 'psq_tipo', $tipo_comunicado, $tipo, ' class="span12"', ' ');?>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span4'>
	            <div class='control-group'>
	                <label class='control-label' for='data_inicio_analise'><?=traduz("Descrição / Título")?></label>
	                <div class='controls controls-row'>
	                    <div class='span12'>
	                        <input type='text' name='psq_descricao' size='41' value='<?php echo $psq_descricao; ?>' class='span12'>
	                    </div>
	                </div>
	            </div>
	        </div>

		</div>

		<?php if (in_array($login_fabrica, array(169,170))){ ?>
		<div class='row-fluid'>
			<div class="span2"></div>
			<div class="span4">
	            <div class='control-group <?=(in_array('produto_serie', $msg_erro['campos'])) ? "error" : "" ?>' >
	                <label class="control-label" for="psq_produto_serie"><?=traduz("Número de Série")?></label>
	                <div class="controls controls-row">
	                    <div class="span12 input-append" id='fricon'>

	                        <input id="psq_produto_serie" name="psq_produto_serie" class="span10" type="text" value="<?=$psq_produto_serie?>" maxlength="30" />
	                        <span class="add-on lupa_serie" rel="lupa"style='cursor: pointer;'>
	                     		<i class='icon-search'></i>
	                        </span>
	                        <input type="hidden" name="lupa_config" tipo="produto" posicao="								pesquisa" parametro="numero_serie" <?=(in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""?> <?=($usaProdutoGenerico) ? "produto-generico='true'" : ""?> />
	                    </div>
	                </div>
	            </div>
	        </div>
		<div class="span4">
                        <div class='control-group <?=(in_array('familia', $msg_erro['campos'])) ? "error" : "" ?>' >
                                <label class="control-label" for="psq_familia"><?=traduz("Família")?></label>
                                <div class="controls controls-row">
                                        <div class="span12 input-append" >
                                                <select class="span12" name="psq_familia">
                                                        <option value=''><?=traduz("Selecione")?></option>
                                                        <?php
                                                        $sqlFamilia = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY descricao";
                                                        $resFamilia = pg_query($con, $sqlFamilia);

                                                        while ($row = pg_fetch_object($resFamilia)) {
                                                                $selected = ($row->familia == $psq_familia) ? "selected" : "";
                                                                echo "<option value='{$row->familia}' {$selected} >{$row->descricao}</option>";
                                                        }
                                                        ?>
                                                </select>
                                        </div>
                                </div>
                        </div>
                </div>

			<div class="span2"></div>
		</div>
		<?php }

		if (in_array($login_fabrica, [148])) {
		?>
			<div class='row-fluid'>

		        <div class='span2'></div>

			    <div class='span4'>
		            <div class='control-group'>
		                <label class='control-label' for='produto_referencia_multi'><?=traduz("Tipo Posto")?></label>
		                <div class='controls controls-row'>
		                    <div class='span10 input-append'>
		                        <?php
								$sql_tipo_posto = "SELECT  descricao,tipo_posto
										FROM    tbl_tipo_posto
										WHERE   tbl_tipo_posto.fabrica = $login_fabrica
										AND     tbl_tipo_posto.ativo IS TRUE
										ORDER BY tbl_tipo_posto.descricao;";
								$res_tipo_posto = pg_query ($con,$sql_tipo_posto);

								if (pg_num_rows($res_tipo_posto) > 0) { ?>
									<select class='span12' name='tipo_posto'>
										<option value=''><?=traduz("Selecione")?></option>

										<?php
										while ($tp_posto = pg_fetch_array($res_tipo_posto)) {
											$tipo_posto_descricao = $tp_posto['descricao'];
											$tipo_posto_id  	  = $tp_posto['tipo_posto'];
										?>
											<option value="<?= $tipo_posto_id ?>" <?= ($tipo_posto_id == $tipo_posto) ? "selected" : "" ?> > <?= $tipo_posto_descricao ?></option>
										<?php	
										} ?>
										
									</select>
								<?php
								}
								?>
		                    </div>
		                </div>
		            </div>
		        </div>
                <div class='span4'>
		            <div class='control-group <?=(in_array('linha', $msg_erro['campos'])) ? "error" : "" ?>'>
		                <label class='control-label' for='produto_referencia_multi'>Linha</label>
		                <div class='controls controls-row'>
		                    <div class='span10 input-append'>
		                        <?php
								$sql = "SELECT  *
										FROM    tbl_linha
										WHERE   tbl_linha.fabrica = $login_fabrica
										AND     tbl_linha.ativo IS TRUE
										ORDER BY tbl_linha.nome;";
								$resX = pg_query ($con,$sql);

								if (pg_numrows($resX) > 0) {
									echo "<select class='span12' name='linha'>";
										echo "<option value=''>Selecione</option>";

										for ($x = 0 ; $x < pg_numrows($resX) ; $x++){
											$aux_linha = trim(pg_result($resX,$x,linha));
											$aux_nome  = trim(pg_result($resX,$x,nome));

											echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>";
										}

									echo "</select>";
								}
								?>
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class='span2'></div>
			</div>
	    <?php
		}
	    ?>

		<div class='row-fluid' id="produto_pesquisa">

	        <div class='span2'></div>

	        <div class='span3'>
	            <div class='control-group'>
	                <label class='control-label' for='psq_produto_referencia'><?=traduz("Ref. Produto")?></label>
	                <div class='controls controls-row'>
	                    <div class='span10 input-append'>
	                        <input type="text" id="psq_produto_referencia" name="psq_produto_referencia" class='span12' maxlength="20" value="<?php echo $produto_referencia ?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posicao="pesquisa" />
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span5'>
	            <div class='control-group'>
	                <label class='control-label' for='psq_produto_nome'><?=traduz("Descrição Produto")?></label>
	                <div class='controls controls-row'>
	                    <div class='span11 input-append'>
	                        <input type="text" id="psq_produto_nome" name="psq_produto_nome" class='span12' value="<? echo $produto_descricao ?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posicao="pesquisa" />
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span2'></div>

	    </div>

	    <?php if ($login_fabrica == 175){ ?>
	    <div class="row-fluid">
	    	<div class="span2"></div>
	    	<div class="span4">
				<div class='control-group <?=(in_array("ordem_producao_pesquisa", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='ordem_producao_pesquisa'><?=traduz("Ordem de Produção")?></label>
					<div class='controls controls-row'>
						<div class='span8' >
							<input type='text' numeric="true" id="ordem_producao_pesquisa" name='ordem_producao_pesquisa' value='<?php echo $ordem_producao_pesquisa; ?>' maxlength='50' class='span12'>
						</div>
					</div>
				</div>
			</div>	
			<div class="span6"></div>
		</div>
		<?php } ?>
	    <br />

	    <p class="tac">
	    	<input type="submit" class="btn" value="Continuar">
	    </p>

	    <br />

	</form>

<?php

	if (trim($btn_acao) == "pesquisar") {
		if (strlen($psq_familia) > 0) {
			$join_familia = "LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = $login_fabrica";
			$where_familia = "AND tbl_familia.familia = $psq_familia";
		}

		if ($login_fabrica == 175){
			if (!empty($ordem_producao_pesquisa)){
				$cond_ordem_producao = " AND tbl_comunicado.versao = '$ordem_producao_pesquisa' ";
			}
		}

		$cond_linha = '';

		if ($login_fabrica == 148) {
			
			if (!empty($linha)) {
			
				$cond_linha = " AND tbl_comunicado.linha = $linha ";
			}	
		}

		#--------------------------------------------------------
		#  Mostra todos os informativos cadastrados
		#--------------------------------------------------------
		$sql = "SELECT	tbl_comunicado.comunicado,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data,
						tbl_comunicado.tipo,
						tbl_comunicado.descricao,
						tbl_comunicado.linha   AS c_linha,
						tbl_comunicado.versao  AS p_versao,
						tbl_produto.referencia AS produto_referencia,
						tbl_produto.descricao  AS produto_descricao,
						tbl_produto.referencia_fabrica,
						tbl_comunicado.ativo,
						linha_comunicado.nome as nome_linha,
						tbl_tipo_posto.descricao as descricao_tipo_posto
				FROM	tbl_comunicado
				LEFT JOIN tbl_produto USING(produto)
				LEFT JOIN tbl_tipo_posto ON tbl_comunicado.tipo_posto = tbl_tipo_posto.tipo_posto
				LEFT JOIN tbl_linha linha_comunicado   ON tbl_comunicado.linha = linha_comunicado.linha
				$join_familia
				LEFT JOIN tbl_linha   on tbl_linha.linha = tbl_produto.linha
				WHERE	tbl_comunicado.fabrica = $login_fabrica
				AND tbl_comunicado.tipo in ('".implode("','",$tipo_comunicado)."') 
				$where_familia	
				$cond_linha
				$cond_ordem_producao	
		 ";

		if (strlen($tipo) > 0)       $sql .= " AND tbl_comunicado.tipo      = '$tipo' ";
		if (strlen($descricao) > 0)  $sql .= " AND tbl_comunicado.descricao ILIKE '%$descricao%' ";
		if (strlen($linha) > 0) 	 $sql .= " AND tbl_comunicado.linha = $linha";
		if (strlen($tipo_posto) > 0) $sql .= " AND tbl_comunicado.tipo_posto = $tipo_posto";

		if (strlen($produto_referencia) > 0){
			//HD 9919 PAULO
			$produto_referencia = str_replace("-", "", $produto_referencia);
			$produto_referencia = str_replace("/", "", $produto_referencia);
			$produto_referencia = str_replace("'", "", $produto_referencia);
			$produto_referencia = str_replace(".", "", $produto_referencia);
			//	MLG - Erro na pesquisa com espaço no meio na Lenoxx
			if ( in_array($login_fabrica, array(11,129,172)) ) $produto_referencia = str_replace(" ", "", $produto_referencia);

			$sqlx = "SELECT tbl_produto.produto
					   FROM tbl_produto
					   JOIN tbl_linha ON tbl_produto.linha  = tbl_linha.linha
					  WHERE (tbl_produto.referencia_pesquisa = '$produto_referencia'  or tbl_produto.referencia='$produto_referencia')
						AND tbl_linha.fabrica = $login_fabrica";
			$resx = pg_query ($con,$sqlx);
			if (pg_numrows($resx) > 0){
				$produto = pg_result ($resx,0,0);
				$sql .= " AND tbl_comunicado.produto = $produto ";
			}
		}

		$sql.= " UNION ";

		$sql.= " SELECT tbl_comunicado.comunicado,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data,
						tbl_comunicado.tipo,
						tbl_comunicado.descricao,
						tbl_comunicado.linha   AS c_linha,
						tbl_comunicado.versao  AS p_versao,
						tbl_produto.referencia AS produto_referencia,
						tbl_produto.referencia_fabrica,
						tbl_produto.descricao  AS produto_descricao,
						tbl_comunicado.ativo,
						linha_comunicado.nome as nome_linha,
						tbl_tipo_posto.descricao as descricao_tipo_posto
				   FROM tbl_comunicado
			  LEFT JOIN tbl_produto USING(produto)
			  LEFT JOIN tbl_linha    ON tbl_linha.linha = tbl_produto.linha
			  LEFT JOIN tbl_linha linha_comunicado   ON tbl_comunicado.linha = linha_comunicado.linha
			  LEFT JOIN tbl_tipo_posto ON tbl_comunicado.tipo_posto = tbl_tipo_posto.tipo_posto
				   JOIN tbl_comunicado_produto using(comunicado)
			  WHERE tbl_comunicado.fabrica = $login_fabrica 
				AND tbl_comunicado.tipo in ('".implode("','",$tipo_comunicado)."')  ";

		if (strlen($tipo) > 0)      $sql .= " AND tbl_comunicado.tipo      = '$tipo' ";
		if (strlen($descricao) > 0) $sql .= " AND tbl_comunicado.descricao ILIKE '%$descricao%' ";
		if (strlen($produto_referencia) > 0){

			$sqlx = "SELECT  tbl_produto.produto
					FROM     tbl_produto
					JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
					WHERE    tbl_produto.referencia_pesquisa = '$produto_referencia'
					AND      tbl_linha.fabrica = $login_fabrica";
			$resx = pg_query ($con,$sqlx);
			if (pg_numrows($resx) > 0){
				$produto = pg_result ($resx,0,0);
				$sql .= " AND tbl_comunicado_produto.produto = $produto ";
			}
		}

		//Hd 9922 paulo
		$sql .= ($login_fabrica == 5 ) ? " ORDER BY produto_referencia " : " ORDER BY data DESC";
		$res = pg_query ($con,$sql);

		if (pg_numrows($res) > 0){

			echo "<p><strong>(+)</strong> = ".traduz("Vários produtos")."</p>";

			echo "<table class='table table-bordered'>";
				echo "<tr class='titulo_coluna'>";
					echo "<td class='tac'>".traduz("Tipo")."</td>";
					if (in_array($login_fabrica,array(11, 15, 91, 148, 157, 161, 172))){//HD 198907
						echo "<td class='tac'>".traduz("Descrição/Titulo")."</td>";
					}
					if (in_array($login_fabrica,array(171))){
						echo "<td class='tac'>".traduz("Refêrencia Fábrica")."</td>";
					}
					if (!in_array($login_fabrica, [148])) {
						echo "<td class='tac'>".traduz("Refêrencia")."</td>";
						echo "<td class='tac'>".traduz("Produto")."</td>";
					} else {
						echo "<td class='tac'>".traduz("Tipo")."Posto</td>";
						echo "<td class='tac'>".traduz("Linha")."</td>";
					}
					echo "<td class='tac'>".traduz("Data")."</td>";
					echo "<td class='tac'>".traduz("Ativo")."</td>";
					echo "<td class='tac' width='85'>".traduz("Ação")."</td>";
				echo "</tr>";

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
					$descricao         = trim(pg_fetch_result ($res,$i,'descricao'));
					$referencia        = trim(pg_fetch_result ($res,$i,'produto_referencia'));
					$referencia_fabrica = trim(pg_fetch_result ($res,$i,'referencia_fabrica'));
					$comunicado        = trim(pg_fetch_result ($res,$i,'comunicado'));
					$produto_descricao = trim(pg_fetch_result ($res,$i,'produto_descricao'));
					$data              = trim(pg_fetch_result ($res,$i,'data'));
					$ativo             = trim(pg_fetch_result ($res,$i,'ativo'));
					$versao            = trim(pg_fetch_result ($res,$i,'p_versao'));
					$descricao_tipo_posto = trim(pg_fetch_result ($res,$i,'descricao_tipo_posto'));
					$nome_linha        = trim(pg_fetch_result ($res,$i,'nome_linha'));

					if (strlen($descricao)>0 && !in_array($login_fabrica, [148,157,161])) {
						$descricao = " <br>$descricao";
					} else {
						$descricao = "$descricao";
					}

					$sql2 = "SELECT tbl_comunicado_produto.comunicado,
									tbl_produto.referencia,
									tbl_produto.descricao
							FROM tbl_comunicado_produto
							JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
							WHERE comunicado = $comunicado";
					$res2 = pg_query ($con,$sql2);
					if (pg_numrows($res2)>0){
						$referencia        = trim(pg_fetch_result ($res2,0,'referencia'));
						$produto_descricao = trim(pg_fetch_result ($res2,0,'descricao'));
						$referencia        = $referencia."...";
						$produto_descricao = $produto_descricao."... (+)";
					}

					echo "<tr class='linha-". $comunicado . "'>";

						echo "<td>";
							echo pg_fetch_result ($res,$i,tipo);
						echo "</td>";

						if (in_array($login_fabrica,array(11, 15, 91, 148, 157, 161, 172))){//HD 198907
							echo "<td>";
								echo $descricao;
							echo "</td>";
						}
						if (in_array($login_fabrica,array(171))){
							echo "<td class='tac'>$referencia_fabrica</td>";
						}

						if (!in_array($login_fabrica, [148])) {
							echo "<td>";
								echo "<a href='$PHP_SELF?comunicado=" . $comunicado . "'>$referencia</a>";
							echo "</td>";

							echo "<td>";
								echo "<a href='$PHP_SELF?comunicado=" .$comunicado . "'>$produto_descricao</a>";
                                                       if ($versao){
                                                                if ($login_fabrica == 175){
                                                                        $label_versao = "Ordem de Produção";
                                                                }else{
                                                                        $label_versao = "versão";
                                                                }
                                                                echo "<span style='float:right;padding-right:2px'>$label_versao <strong>$versao</strong></span>";
                                                        }							
echo "</td>";
						} else { ?>
							<td class="tac">
								<?= $descricao_tipo_posto ?>
							</td>
							<td class="tac">
								<?= $nome_linha ?>
							</td>
						<?php
						}

						echo "<td class='tac'>";
							echo  $data;
						echo "</td>";

						echo "<td class='tac'>";

							$imagem_ativo = ($ativo == "t") ? 'status_verde.png' : 'status_vermelho.png';
							echo "<img src='imagens/".$imagem_ativo."' />";

						echo "</td>";

						if ($login_fabrica == 148) { 

							echo "<td class='tac' nowrap>";
								echo "<a href='$PHP_SELF?comunicado=" . $comunicado . "' class='btn btn-primary'>";
									echo "Alterar";
								echo "</a>   ";
								echo " <button id='excluir_comunicado' onclick='excluirComunicado(" . $comunicado . ")' class='btn btn-danger'> Excluir </button>";
							echo "</td>";

						} else { 

							echo "<td class='tac'>";
								echo "<a href='$PHP_SELF?comunicado=" . $comunicado . "' class='btn btn-primary'>";
									echo "Alterar";
							echo "</td>";
						}

					echo "</tr>";
				}
			echo "</table>";
		}else{
			echo "<center><h2>".traduz("Nenhum resultado encontrado");"</h2></center>";
		}
	}

include "rodape.php";
?>
<script type="text/javascript">
	

	function excluirComunicado(comunicado) 
	{
		let linha = this;

	    $.ajax({
	        
	        url:"<?=$PHP_SELF?>",
	        type:"POST",
	        dataType:"JSON",
	        data:{
	            excluir_comunicado: true,
	            comunicado: comunicado
	        }

	    }).done(function(data) {

	    	let linha = '.linha-' + comunicado; 
        	let conteudo = $(linha).html();

	        if(data.result == "success") {
	        	
	        	$(linha).html("<td bgcolor='#32CD32'><p>Comunicado removido com sucesso</p></td>");

	        	let html = "<td bgcolor='#32CD32' colspan='7' style='text-align:center;'><p style='color:white;'>Comunicado removido com sucesso</p></td>";

    	     	$(linha).html(html);

	        	setTimeout(function () {

	        		$(linha).detach();

	        	}, 3000);

	        } else {	        	

	        	let html = "<td bgcolor='tomato' colspan='7' style='text-align:center;'><p style='color:white;'>Erro ao remover comunicado</p></td>";
	        	
	        	$(linha).html(html);

	        	setTimeout(function () {

	        		$(linha).html(conteudo);

	        	}, 3000);
	        }

	    });

	}

</script>
