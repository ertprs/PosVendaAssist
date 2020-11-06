<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$title = "Cadastro de Processos";
$cabecalho = "Cadastro de Processos";
$layout_menu = "gerencia";
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
if ($login_fabrica == 24) {
	include_once __DIR__ . '/../class/AuditorLog.php';
}

include_once S3CLASS;
    $s3 = new AmazonTC("processos", $login_fabrica);

if(isset($_POST['remover_lembrete']) == true){

	$alerta 		= $_POST['evento_alerta'];
	$processo 		= $_POST['processo'];

	$sql = " DELETE from tbl_evento_alerta where evento_alerta = $alerta and fabrica = $login_fabrica and registro_id = $processo ";
	$res = pg_query($con, $sql);
	if(strlen(pg_last_error($con))>0){
		echo "Falha ao excluir";
	}
	exit;
}

if(isset($_REQUEST['lembrete'])){

	$inserir = $_POST["inserir"];

	if($inserir){
		$campo = array();

		$tipo_lembrete 				= $_POST["tipo_lembrete"];
	    $data_lembrete 				= $_POST["data_lembrete"];
	    $mensagem_lembrete 			= pg_escape_string($_POST["mensagem_lembrete"]);
	    $data_audiencia_acordo 		= $_POST["data_audiencia_acordo"];
	    $email_destinatario 		= $_POST["email_destinatario"];
	    $nome_destinatario 			= htmlentities(mb_convert_encoding($_POST["nome_destinatario"], 'ISO-8859-1', 'UTF-8'));
	    $processo 					= $_POST["processo"];

	    if($tipo_lembrete == 'data_audiencia'){
	    	$tipo = "Data de Audiência";
	    	$contexto = 3;
	    }else{
	    	$tipo = "Data de Acordo ou Sentença";
	    	$contexto = 4; 
	    }

	    if(strlen(trim($data_lembrete))==0){
	    	$msg_erro .= "O campo Data Lembrete deve ser preenchido. \n";
	    }

	    if ($login_fabrica == 183){
	    	if (!empty($data_lembrete)){
		    	list($di, $mi, $yi) = explode("/", $data_lembrete);

		    	if (!checkdate($mi, $di, $yi)) {
					$msg_erro.= "Data lembrete inválida";
				}
				$data_lembrete = formata_data($data_lembrete);
			}


			if (!empty($data_audiencia_acordo)){
				list($di, $mi, $yi) = explode("/", $data_audiencia_acordo);

		    	if (!checkdate($mi, $di, $yi)) {
					$msg_erro.= "Data audiencia inválida";
				}
				$data_audiencia_acordo = formata_data($data_audiencia_acordo);
			}
		}

	    if(strlen(trim($email_destinatario))==0){
	    	$msg_erro .= "O campo E-mail Destinatário deve ser preenchido. \n";
	    }

	    if(strlen(trim($nome_destinatario))==0){
	    	$msg_erro .= "O campo Nome Destinatário deve ser preenchido. \n";
	    }

	    if(strlen(trim($mensagem_lembrete))==0){
	    	$msg_erro .= "O campo Mensagem deve ser preenchido. \n";
	    }

	    if ($login_fabrica <> 183){
	    	if(strlen(trim($data_audiencia_acordo))==0){	    	
		    	$msg_erro .= "$tipo não encontrada. \n";
		    }
	    }

	    if(strlen(trim($msg_erro))>0){
	    	echo json_encode(array('erro' => utf8_encode($msg_erro)));
	    	exit;
	    }

	    $campo['type'] = 'email';
	    $campo['name'] = $nome_destinatario;
	    $campo['email']= $email_destinatario;

	    $campo = html_entity_decode(json_encode($campo));

	    $sql_contexto = "INSERT INTO tbl_evento_alerta (fabrica, admin, contexto, data_aviso, registro_id, contato, mensagem)  values($login_fabrica, $login_admin, $contexto, '$data_lembrete', '$processo', '$campo', '$mensagem_lembrete') returning contexto ";
	    $res_contexto = pg_query($con, $sql_contexto);
	    
	    if(strlen(pg_last_error($con))>0){
	    	$msg_erro .= pg_last_error($con);
	    }

	    echo json_encode('ok');
	    exit;
	}else{

		$sql_busca_lembrete = "
			SELECT 
				contato->>'name' AS nome, 
				contato->>'email' AS email, 
				evento_alerta, 
				tbl_contexto.descricao AS tipo_lembrete,
				TO_CHAR(data_aviso, 'DD/MM/YYYY') AS data_aviso
			FROM tbl_evento_alerta 
			JOIN tbl_contexto ON tbl_contexto.contexto = tbl_evento_alerta.contexto
			WHERE fabrica = $login_fabrica 
			AND registro_id = $processo";
		$res_busca_lembrete = pg_query($con, $sql_busca_lembrete);

		for($a=0; $a<pg_num_rows($res_busca_lembrete); $a++){
			$nome = pg_fetch_result($res_busca_lembrete, $a, nome);
			$email = pg_fetch_result($res_busca_lembrete, $a, email);
			$descricao_tipo_lembrete = pg_fetch_result($res_busca_lembrete, $a, tipo_lembrete);
			$evento_alerta = pg_fetch_result($res_busca_lembrete, $a, evento_alerta);
			$data_aviso = pg_fetch_result($res_busca_lembrete, $a, data_aviso);

			$conteudo .= "<tr>
				<td style='text-align:center'>$data_aviso</td>
				<td style='text-align:center'>$descricao_tipo_lembrete</td>
				<td style='text-align:center'>$nome</td>
				<td style='text-align:center'>$email</td>
				<td style='text-align:center'><button type='button' class='btn btn-danger btn-mini' onclick='remover($evento_alerta)' class='remover'>Remover</button></td>
				</tr>";
		}
	}

	exit($conteudo);
}


if(isset($_REQUEST['busca_data_lembrete'])){

	$data_lembrete = $_POST["data_lembrete"];

	if(empty($data_lembrete)){
		exit;
	}

	$data_lembrete = formata_data($data_lembrete);

	$tipo_lembrete = $_POST["tipo_lembrete"];

	$processo = $_POST["processo"];
	
	if($tipo_lembrete == 'data_audiencia'){
		$tipo = "Data de Audiência";

		$sql = "SELECT processo, data_audiencia1, data_audiencia2 from tbl_processo_item where processo = $processo and (data_audiencia1 > '$data_lembrete 00:00:00' or data_audiencia2 > '$data_lembrete 00:00:00');";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
			$data_audiencia1 = pg_fetch_result($res, 0, data_audiencia1);
			$data_audiencia2 = pg_fetch_result($res, 0, data_audiencia2);

			if(strlen(trim($data_audiencia2))>0){
				$data = $data_audiencia2;
			}else{
				$data = $data_audiencia1;
			}
		}
		
	}else{
		$tipo = "Data de Acordo ou Sentença";

		$sql = " SELECT tbl_processo.processo, tbl_processo.data_sentenca, tbl_processo_item.data_acordo from tbl_processo 
			join tbl_processo_item on tbl_processo_item.processo = tbl_processo.processo
		where tbl_processo.processo = $processo and (tbl_processo.data_sentenca > '$data_lembrete 00:00:00' or tbl_processo_item.data_acordo > '$data_lembrete 00:00:00');";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
			$data_sentenca = pg_fetch_result($res, 0, data_sentenca);
			$data_acordo = pg_fetch_result($res, 0, data_acordo);

			if(strlen(trim($data_acordo))>0){
				$data = $data_acordo;
			}else{
				$data = $data_sentenca;
			}
		}
	}

	if(strlen(trim($data))>0){
		$dados = array('data' => substr(mostra_data($data), 0, 16));
	}else{
		if ($login_fabrica <> 183){
			$dados = array('erro' => utf8_encode("$tipo não encontrada"));
		}
	}	
	
	echo json_encode($dados);

exit;
}

if(isset($_POST['busca_os'])){
	$os = (int)$_POST["os"];

	$sql = "SELECT tbl_os.consumidor_nome, tbl_os.consumidor_cep, tbl_os.consumidor_email, tbl_os.consumidor_fone, tbl_os.consumidor_cpf, tbl_os.consumidor_celular, tbl_os.consumidor_numero, tbl_os.consumidor_complemento, 
			tbl_produto.referencia, tbl_produto.descricao, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
			FROM tbl_os 
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
			WHERE tbl_os.os = $os ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$referencia 			= pg_fetch_result($res, 0, referencia);
		$descricao 				= pg_fetch_result($res, 0, descricao);
		$nome 					= pg_fetch_result($res, 0, nome);
		$codigo_posto 			= pg_fetch_result($res, 0, codigo_posto);

		$numero 				= pg_fetch_result($res, 0, consumidor_numero);
		$nome_cliente 			= pg_fetch_result($res, 0, consumidor_nome);
		$email 					= pg_fetch_result($res, 0, consumidor_email);
		$fone 					= pg_fetch_result($res, 0, consumidor_fone);
		$celular				= pg_fetch_result($res, 0, consumidor_celular);
		$cpf 					= pg_fetch_result($res, 0, consumidor_cpf);
		$cep 					= pg_fetch_result($res, 0, consumidor_cep);
		$complemento 			= pg_fetch_result($res, 0, consumidor_complemento);

	}

	$dados = array('referencia' => $referencia, 'descricao' => $descricao, 'nome' => $nome, 'codigo_posto' => $codigo_posto, 'nome_cliente' => $nome_cliente, 'numero' => $numero, 'email' => $email, 'fone' => $fone, 'celular' => $celular, 'bairro' => $bairro, 'cpf' => $cpf, 'cep' => $cep, 'complemento' => $complemento );

	echo json_encode($dados);

	exit;
}



if(isset($_POST["busca_atendimento"])){
	$atendimento = (int)$_POST["atendimento"];

	$sql = "SELECT 
			tbl_hd_chamado.status, 
			tbl_hd_chamado_extra.os, 
			tbl_hd_chamado_extra.posto, 
			tbl_produto.referencia, 
			tbl_produto.descricao, 
			tbl_posto.nome, 
			tbl_posto_fabrica.codigo_posto, 
			tbl_admin.nome_completo, 
			tbl_hd_chamado_extra.nome AS nome_cliente, 
			tbl_hd_chamado_extra.numero, 
			tbl_hd_chamado_extra.complemento, 
			tbl_hd_chamado_extra.bairro, 
			tbl_hd_chamado_extra.cep, 
			tbl_hd_chamado_extra.fone, 
			tbl_hd_chamado_extra.celular, 
			tbl_hd_chamado_extra.email, 
			tbl_hd_chamado_extra.cpf 
		FROM tbl_hd_chamado 
		INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
		INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_hd_chamado_extra.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		INNER JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
		INNER JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = $login_fabrica 
		WHERE tbl_hd_chamado.hd_chamado = $atendimento ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$status 				= pg_fetch_result($res, 0, status);
		$posto 					= pg_fetch_result($res, 0, posto);
		$referencia 			= pg_fetch_result($res, 0, referencia);
		$descricao 				= pg_fetch_result($res, 0, descricao);
		$nome 					= pg_fetch_result($res, 0, nome);
		$codigo_posto 			= pg_fetch_result($res, 0, codigo_posto);
		$nome_completo 			= pg_fetch_result($res, 0, nome_completo);

		$numero 				= pg_fetch_result($res, 0, numero);
		$nome_cliente 			= pg_fetch_result($res, 0, nome_cliente);
		$email 					= pg_fetch_result($res, 0, email);
		$fone 					= pg_fetch_result($res, 0, fone);
		$celular				= pg_fetch_result($res, 0, celular);
		$bairro 				= pg_fetch_result($res, 0, bairro);
		$cpf 					= pg_fetch_result($res, 0, cpf);
		$cep 					= pg_fetch_result($res, 0, cep);
		$complemento 			= pg_fetch_result($res, 0, complemento);
		$os 					= pg_fetch_result($res, 0, os);
	}

	$dados = array('nome_completo' => $nome_completo, 'status'=> $status, 'posto' => $posto, 'referencia' => $referencia, 'descricao' => $descricao, 'nome' => $nome, 'codigo_posto' => $codigo_posto, 'nome_cliente' => $nome_cliente, 'numero' => $numero, 'email' => $email, 'fone' => $fone, 'celular' => $celular, 'bairro' => $bairro, 'cpf' => $cpf, 'cep' => $cep, 'complemento' => $complemento, 'os' => $os );

	echo json_encode($dados);

	exit;
}


if ($_GET["num_processo"]<>"") {
	$num_processo = $_GET["num_processo"];
	$processo_id  = $_GET["num_processo"];

	if(in_array($login_fabrica,array(11,42,172,183))){
		$sql_campos_extra = "tbl_processo.comarca, tbl_processo.partes_adversas, tbl_processo.valor_causa, tbl_processo.processo as processo_id, tbl_processo.data_transito_julgado, tbl_processo.data_sentenca, tbl_processo.data_execucao, tbl_processo.fase_processual, tbl_processo.status_processo, tbl_processo.houve_acordo,  ";
	}

	$sql_get = "SELECT
					tbl_processo.processo AS id_processo,
					tbl_processo.numero_processo as processo,
					tbl_processo.fabrica as fabrica,
					tbl_os.os as os,
					tbl_os.serie as serie,
					tbl_hd_chamado.hd_chamado as hd_chamado,
					tbl_admin.nome_completo as atendente,
					tbl_hd_chamado.status as status,
					tbl_posto_fabrica.codigo_posto as codigo_posto,
					tbl_posto.nome as nome,
					orgao,
					tbl_processo.consumidor_nome as consumidor_nome,
					tbl_processo.consumidor_cpf_cnpj as consumidor_cpf,
					tbl_processo.consumidor_fone1 as consumidor_fone1,
					tbl_processo.consumidor_fone2 as consumidor_fone2,
					tbl_processo.consumidor_email as consumidor_email,
					tbl_processo.consumidor_endereco as consumidor_endereco,
					tbl_processo.consumidor_bairro as consumidor_bairro,
					tbl_processo.consumidor_numero as consumidor_numero,
					tbl_processo.consumidor_complemento as consumidor_complemento,
					tbl_cidade.nome as cidade,
					tbl_cidade.estado as estado,
					tbl_processo.consumidor_cep as consumidor_cep,
					to_char(data_notificacao, 'DD/MM/YYYY') AS data_notificacao,
					to_char(data_audiencia1, 'DD/MM/YYYY') AS data_audiencia1,
					to_char(data_audiencia2, 'DD/MM/YYYY') AS data_audiencia2,
					to_char(data_solucao, 'DD/MM/YYYY') AS data_solucao,
					advogado_nome,
					advogado_celular,
					advogado_email,
					solucao,
					valor_cliente,
					custo_advogado,
					historico,
					motivo_processo,
					$sql_campos_extra
					tbl_produto.referencia as produto,
					tbl_produto.descricao as descricao,
					tbl_processo.admin as admin,
					tbl_processo.observacao AS observacao_audiencia,
					to_char(tbl_processo.data_input	, 'DD/MM/YYYY hh24:mm') AS data_input,
					tbl_processo.campos_adicionais
				FROM tbl_processo
				LEFT JOIN tbl_produto on (tbl_processo.produto = tbl_produto.produto)
				LEFT JOIN tbl_hd_chamado on (tbl_processo.hd_chamado = tbl_hd_chamado.hd_chamado)
				LEFT JOIN tbl_os on (tbl_processo.os = tbl_os.os)
				LEFT JOIN tbl_posto on (tbl_os.posto = tbl_posto.posto)
				LEFT JOIN tbl_posto_fabrica on (tbl_posto.posto = tbl_posto_fabrica.posto) AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_admin on (tbl_hd_chamado.admin = tbl_admin.admin )
				LEFT JOIN tbl_cidade on (tbl_processo.cidade = tbl_cidade.cidade)
				WHERE tbl_processo.fabrica = $login_fabrica AND processo = '$num_processo' ;";
				
	$res_get = pg_query($con,$sql_get);

	$_RESULT["numero_processo_telecontrol"] = pg_fetch_result($res_get, 0, "id_processo");
	$_RESULT["num_processo"]		= pg_fetch_result($res_get,0, 'processo');
	$_RESULT["chamado_referencia"]  = pg_fetch_result($res_get,0, 'hd_chamado');
	$_RESULT["chamado_atendente"]  	= pg_fetch_result($res_get,0, 'atendente');
	$_RESULT["status_chamado"]  	= pg_fetch_result($res_get,0, 'status');
	$_RESULT["os_posto"]		  	= pg_fetch_result($res_get,0, 'os');
	$_RESULT["orgao_processo"]  	= pg_fetch_result($res_get,0, 'orgao');
	$_RESULT["cli_nome"]			= pg_fetch_result($res_get,0, 'consumidor_nome');
	$_RESULT["consumidor_cpf"] 		= pg_fetch_result($res_get,0, 'consumidor_cpf');
	$_RESULT["cli_tel_fix"] 		= pg_fetch_result($res_get,0, 'consumidor_fone1');
	$_RESULT["cli_tel_cel"] 		= pg_fetch_result($res_get,0, 'consumidor_fone2');
	$_RESULT["cli_email"] 			= pg_fetch_result($res_get,0, 'consumidor_email');
	$_RESULT["cli_endereco"]		= pg_fetch_result($res_get,0, 'consumidor_endereco');
	$_RESULT["cli_bairro"]     		= pg_fetch_result($res_get,0, 'consumidor_bairro');
	$_RESULT["cli_numero"]			= pg_fetch_result($res_get,0, 'consumidor_numero');
	$_RESULT["cli_end_complemento"]	= pg_fetch_result($res_get,0, 'consumidor_complemento');
	$_RESULT["cli_cidade"]			= pg_fetch_result($res_get,0, 'cidade');
	$_RESULT["cli_estado"]			= pg_fetch_result($res_get,0, 'estado');
	$_RESULT["cli_cep"]				= pg_fetch_result($res_get,0, 'consumidor_cep');
	$_RESULT["produto_referencia"]	= pg_fetch_result($res_get,0, 'produto');
	$_RESULT["data_notificacao"]	= pg_fetch_result($res_get,0, 'data_notificacao');
	$_RESULT["data_audiencia"]		= pg_fetch_result($res_get,0, 'data_audiencia1');
	$_RESULT["data_audiencia2"]		= pg_fetch_result($res_get,0, 'data_audiencia2');
	$_RESULT["data_solucao"]		= pg_fetch_result($res_get,0, 'data_solucao');
	$_RESULT["nome_adv"]			= pg_fetch_result($res_get,0, 'advogado_nome');
	$_RESULT["adv_Tel_cel"]			= pg_fetch_result($res_get,0, 'advogado_celular');
	$_RESULT["adv_mail"]			= pg_fetch_result($res_get,0, 'advogado_email');
	$_RESULT["solucao_audiencia"]	= pg_fetch_result($res_get,0, 'solucao');
	$_RESULT["valor_cliente"]		= pg_fetch_result($res_get,0, 'valor_cliente');
	$_RESULT["custo_adv"]			= pg_fetch_result($res_get,0, 'custo_advogado');
	$_RESULT["observacao"]			= pg_fetch_result($res_get,0, 'historico');
	$_RESULT["motivo_principal"]	= pg_fetch_result($res_get,0, 'motivo_processo');
	$_RESULT["produto_descricao"]	= pg_fetch_result($res_get,0, 'descricao');
	$_RESULT["data_input"]			= pg_fetch_result($res_get,0, 'data_input');
	$_RESULT["codigo_posto"]		= pg_fetch_result($res_get,0, 'codigo_posto');
	$_RESULT["descricao_posto"]		= pg_fetch_result($res_get,0, 'nome');
	$_RESULT["observacao_audiencia"] = pg_fetch_result($res_get,0, 'observacao_audiencia'); //HD-3251974
	if(in_array($login_fabrica,array(11,42,172,183))){
		$_RESULT["valor_causa"] = number_format(pg_fetch_result($res_get,0, 'valor_causa'), 2, '.', ''); 
		$_RESULT["partes_adversas"] = pg_fetch_result($res_get,0, 'partes_adversas'); 
		$_RESULT["comarca"] = pg_fetch_result($res_get,0, 'comarca'); 
		$_RESULT["data_transito_julgado"] = mostra_data(pg_fetch_result($res_get,0, 'data_transito_julgado')); 
		$_RESULT["data_sentenca"] = mostra_data(pg_fetch_result($res_get,0, 'data_sentenca')); 
		$_RESULT["data_execucao"] = mostra_data(pg_fetch_result($res_get,0, 'data_execucao')); 

		$status_processo = pg_fetch_result($res_get,0, 'status_processo');
		$fase_processual = pg_fetch_result($res_get,0, 'fase_processual'); 
		$houve_cumprimento_acordo = pg_fetch_result($res_get,0, 'houve_acordo'); 

		$_RESULT["advogado"] = pg_fetch_result($res_get,0, 'advogado_nome'); 
		$_RESULT["telefone_advogado"] = pg_fetch_result($res_get,0, 'advogado_celular'); 
		$_RESULT["valor_cliente_advogado"] =  number_format(pg_fetch_result($res_get,0, 'valor_cliente'), 2, '.', ''); 
		$_RESULT["custo_advogado"] = number_format(pg_fetch_result($res_get,0, 'custo_advogado'), 2, '.', ''); 

		$processo_id = pg_fetch_result($res_get,0, 'processo_id'); 

		if ($login_fabrica == 183){
			$_RESULT["campos_adicionais"] = pg_fetch_result($res_get, 0, 'campos_adicionais');
			$campos_adicionais = json_decode($_RESULT["campos_adicionais"], true);
			extract($campos_adicionais);
		}

		$sql_itens = "SELECT * FROM tbl_processo_item WHERE processo = ".$processo_id ;
		$res_itens = pg_query($con, $sql_itens);
		$qtde_info_gerais = (pg_num_rows($res_itens)-1);
		if($qtde_info_gerais < 0 ){
			$qtde_info_gerais = 0;
		}

		$dados_itens = array();
		for($i =0; $i<pg_num_rows($res_itens); $i++){
			$dados_itens[$i]['processo_item'] 				= pg_fetch_result($res_itens, $i, 'processo_item');
			$dados_itens[$i]['tipo_documento'] 			= pg_fetch_result($res_itens, $i, 'tipo_documento');
			$dados_itens[$i]['data_notificacao'] 			= mostra_data(pg_fetch_result($res_itens, $i, 'data_notificacao'));
			$dados_itens[$i]['data_audiencia'] 			= mostra_data_hora(pg_fetch_result($res_itens, $i, 'data_audiencia1'));
			$dados_itens[$i]['data_audiencia2'] 			= mostra_data_hora(pg_fetch_result($res_itens, $i, 'data_audiencia2'));
			$dados_itens[$i]['processo_pedido_cliente'] 	= pg_fetch_result($res_itens, $i, 'processo_pedido_cliente');
			$dados_itens[$i]['custo_etapa'] 				= number_format(pg_fetch_result($res_itens, $i, 'custo_etapa'), 2, '.', '');
			$dados_itens[$i]['data_acordo']				= mostra_data(pg_fetch_result($res_itens, $i, 'data_acordo'));
			$dados_itens[$i]['data_cumprimento_acordo'] 	= mostra_data(pg_fetch_result($res_itens, $i, 'data_cumprimento_acordo'));
			$dados_itens[$i]['valor_acordo'] 				= number_format(pg_fetch_result($res_itens, $i, 'valor_acordo'), 2, '.', '');
			$dados_itens[$i]['proposta_acordo'] 			= pg_fetch_result($res_itens, $i, 'proposta_acordo');
			$dados_itens[$i]['acordo'] 					= pg_fetch_result($res_itens, $i, 'obs_acordo');

		}
	}

	$admin_res = pg_fetch_result($res_get,0, 'admin');
	$sql_ad = "SELECT nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$admin_res};";
	$res_ad = pg_query($con,$sql_ad);
	$_RESULT["admin_responsavel"] = pg_fetch_result($res_ad,0, 'nome_completo');

}


// //Excluir Anexo
// if ($_POST["excluir"] == "Excluir") {
// 	// echo "<pre>";
// 	// print_r($_POST);
// 	// echo "</pre>";
// 	$nome_anexo = $_POST["anexo"];
// 	$num_processo = $_POST["processo"];
// exit;
// 	$anexo = $s3->getObjectList("{$nome_anexo}");
// 	if (count($anexo) > 0) {
// 		$anexo = basename($anexo[0]);

// 		$s3->deleteObject($anexo);
// 		header("Location: cadastro_processos.php?num_processo={$num_processo}&excluir=ok");
// 	}
// }

if ($_POST["gravar"] == "Gravar" || $_POST["alterar"] == "Alterar") {

	$num_processo = $_POST['num_processo'];
	$orgao_processo = $_POST['orgao_processo'];
	$cli_nome = $_POST['cli_nome'];
	$cli_cpf = $_POST['consumidor_cpf'];
	$cli_tel_fix = $_POST['cli_tel_fix'];
	$cli_tel_cel = $_POST['cli_tel_cel'];
	$cli_email = $_POST['cli_email'];
	$cep = $_POST['cli_cep'];
	$estado = $_POST['cli_estado'];
	$cidade = $_POST['cli_cidade'];
	$bairro = $_POST['cli_bairro'];
	$endereco = $_POST['cli_endereco'];
	$cli_numero_end = $_POST['cli_numero'];
	$cli_end_complemento = $_POST['cli_end_complemento'];
	$chamado_referencia = $_POST['chamado_referencia'];
	$status_chamado = $_POST['status_chamado'];
	$chamado_atendente = $_POST['chamado_atendente'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao = $_POST['produto_descricao'];
	$ns_produto = $_POST['ns_produto'];
	$codigo_posto = $_POST['codigo_posto'];
	$descricao_posto = $_POST['descricao_posto'];
	$os_posto = $_POST['os_posto'];
	$motivo_principal = $_POST['motivo_principal'];
	$data_notificacao = $_POST['data_notificacao'];
	$data_audiencia = $_POST['data_audiencia'];
	$data_audiencia2 = $_POST['data_audiencia2'];
	$solucao_audiencia = $_POST['solucao_audiencia'];
	$data_solucao = $_POST['data_solucao'];
	
	$adv_mail = $_POST['adv_mail'];

	if(in_array($login_fabrica,array(11,42,172,183))){

		$qtde_info_gerais = $_POST["qtde_info_gerais"];
		$processo_id  		= $_POST["processo_id"];
		$comarca 			= $_POST["comarca"];
		$valor_causa 		= $_POST["valor_causa"];
		$partes_adversas 	= $_POST["partes_adversas"];
		$nome_adv           = $_POST["advogado"];
		$adv_Tel_cel		= $_POST["telefone_advogado"];
		$valor_cliente		= $_POST["valor_cliente_advogado"];
		$custo_adv			= $_POST["custo_advogado"];
		
		$status_processo 	= $_POST["status_processo"];
		$fase_processual 	= $_POST["fase_processual"];
		$houve_cumprimento_acordo 	= $_POST["houve_cumprimento_acordo"];

		$data_transito_julgado 		= $_POST["data_transito_julgado"];
		$data_sentenca 		= $_POST["data_sentenca"];
		$data_execucao 		= $_POST["data_execucao"];

		$observacao_audiencia = $_POST['observacao'];

		if(strlen(trim($observacao_audiencia)) == 0 ){
			$observacao_audiencia = "null";
		}

		if ($login_fabrica == 183){
			$produto_nota_fiscal = $_POST["produto_nota_fiscal"];
			$data_nota_fiscal    = $_POST["data_nota_fiscal"];
			$garantia 			 = $_POST["garantia"];

			$campos_adicionais = array(
				"produto_nota_fiscal" => "$produto_nota_fiscal",
				"data_nota_fiscal" => "$data_nota_fiscal",
				"garantia" => "$garantia"
			);
			
			$campos_adicionais = json_encode($campos_adicionais);
			
			$sql_status_processo = "SELECT status_processo FROM tbl_status_processo WHERE fabrica = {$login_fabrica} AND status_processo = {$status_processo} AND finaliza_processo IS TRUE";
			$res_status_processo = pg_query($con, $sql_status_processo); 
			
			if (pg_num_rows($res_status_processo) > 0 AND strlen(trim($data_solucao)) == 0){
				$msg_erro["msg"][] = "O status do processo selecionado obriga o preenchimento do campo data solução";
				$msg_erro["campos"][] = "data_solucao";
			}
		}

		$status_processo = ($status_processo == "") ? "NULL" : $status_processo;
		$fase_processual = ($fase_processual == "") ? "NULL" : $fase_processual;
		$data_sentenca = (formata_data($data_sentenca) == "") ? "NULL" : "'".formata_data($data_sentenca)."'";
		$data_execucao = (formata_data($data_execucao) == "") ? "NULL" : "'".formata_data($data_execucao)."'";
		$data_transito_julgado = (formata_data($data_transito_julgado) == "") ? "NULL" : "'".formata_data($data_transito_julgado)."'";

		if(strlen(trim($valor_causa))==0){
			$valor_causa = 0;
		}else{
			$valor_causa      	= moneyDB($valor_causa);	

		}	

		for($a = 0; $a<=$qtde_info_gerais; $a++){
			$dados_itens[$a]['processo_item'] 			= (int)$_POST["processo_item"][$a];
			$dados_itens[$a]['tipo_documento'] 			= $_POST["tipo_documento"][$a];
			$dados_itens[$a]['data_notificacao'] 		= $_POST["data_notificacao"][$a];
			$dados_itens[$a]['data_audiencia'] 			= $_POST["data_audiencia"][$a];
			$dados_itens[$a]['data_audiencia2'] 		= $_POST["data_audiencia2"][$a];
			$dados_itens[$a]['processo_pedido_cliente'] = $_POST["processo_pedido_cliente"][$a];
			$dados_itens[$a]['custo_etapa'] 			= $_POST["custo_etapa"][$a];
			$dados_itens[$a]['data_acordo']				= $_POST["data_acordo"][$a];
			$dados_itens[$a]['data_cumprimento_acordo'] = $_POST["data_cumprimento_acordo"][$a];
			$dados_itens[$a]['valor_acordo'] 			= $_POST["valor_acordo"][$a];
			$dados_itens[$a]['proposta_acordo'] 		= $_POST["proposta_acordo"][$a];
			$dados_itens[$a]['acordo'] 					= trim($_POST["acordo"][$a]);
			$dados_itens[$a]['valor_acordo'] = moneyDB($dados_itens[$a]['valor_acordo']);
			$dados_itens[$a]['custo_etapa'] = moneyDB($dados_itens[$a]['custo_etapa']);


			if(strlen(trim($dados_itens[$a]['tipo_documento']))>0){

				if (strlen($dados_itens[$a]['processo_pedido_cliente'])==0) {
			        $msg_erro["campos"][] = "processo_pedido_cliente_".$a;
			    }

			    if ($login_fabrica <> 183){
			    	if (strlen($dados_itens[$a]['proposta_acordo'])==0) {
				        $msg_erro["campos"][] = "proposta_acordo_".$a;
				    }	
			    }
			}
		}

		if($orgao_processo != 'consumidor'){
			if (!strlen($num_processo)) {
		        $msg_erro["campos"][] = "num_processo";
		    }
		    if (!strlen($orgao_processo)) {
		        $msg_erro["campos"][] = "orgao_processo";
		    }
		    
		    if ($login_fabrica != 183){
		    	if (!strlen($comarca)) {
		            $msg_erro["campos"][] = "comarca";
		    	}
		    	if (!strlen($nome_adv)) {
			        $msg_erro["campos"][] = "advogado";
			    }

			    if (!strlen($adv_Tel_cel)) {
			        $msg_erro["campos"][] = "telefone_advogado";
			    }

			    if (!strlen($valor_cliente) or $valor_cliente == '0.00') {
			        $msg_erro["campos"][] = "valor_cliente_advogado";
			    }

			    if (!strlen($custo_adv) or $valor_cliente == '0.00') {
			        $msg_erro["campos"][] = "custo_advogado";
			    }

			    if (!strlen($adv_mail)) {
			        $msg_erro["campos"][] = "adv_mail";
			    }
		    }
		}


	    if ($login_fabrica == 183 && (!strlen($status_processo) || $status_processo == 'NULL')) {
			$msg_erro["campos"][] = "status_processo";
	    }
		
	   	/* 
		   	if (!strlen($status_processo)) {
		        $msg_erro["campos"][] = "status_processo";
		    }
		    if (!strlen($fase_processual)) {
		        $msg_erro["campos"][] = "fase_processual";
		    }
		    if (strlen(trim($data_execucao))==0) {
		        $msg_erro["campos"][] = "data_execucao";
		    }
		    if (strlen(trim($data_sentenca)) ==0) {
		        $msg_erro["campos"][] = "data_sentenca";
		    }
		    if (strlen(trim($data_transito_julgado)) ==0 ) {
		        $msg_erro["campos"][] = "data_transito_julgado";
		    }
		*/

	}else{
		$valor_cliente = $_POST['valor_cliente'];
		$nome_adv = $_POST['nome_adv'];
		$adv_Tel_cel = $_POST['adv_Tel_cel'];	
		$custo_adv = $_POST['custo_adv'];

		$observacao_audiencia = $_POST['observacao'];

		if (!strlen($num_processo)) {
	        $msg_erro["campos"][] = "num_processo";
	    }
	    if (!strlen($orgao_processo)) {
	        $msg_erro["campos"][] = "orgao_processo";
	    }  

		if (!strlen($nome_adv)) {
        	$msg_erro["campos"][] = "nome_adv";
	    }
	    if (!strlen($adv_Tel_cel)) {
	        $msg_erro["campos"][] = "adv_Tel_cel";
	    }
	    if (!strlen($adv_mail)) {
	        $msg_erro["campos"][] = "adv_mail";
	    }
	}

	if(strlen(trim($custo_adv))==0){
		$custo_adv = 0;
	}else{
		$custo_adv      	= moneyDB($custo_adv);	
	}

	if(strlen(trim($valor_cliente))==0){
		$valor_cliente = 0;
	}else{
		$valor_cliente      	= moneyDB($valor_cliente);	
	}

	$anexo_processo  = $_POST["anexo"];
	$anexo_processo_s3  = $_POST["anexo_s3"];


	//Validações campos vazios
	if (!strlen($cli_nome)) {
        $msg_erro["campos"][] = "cli_nome";
    }
	if (!strlen($cli_cpf)) {
        $msg_erro["campos"][] = "consumidor_cpf";
    }
	
	if (!strlen($cli_tel_fix) AND !strlen($cli_tel_cel)) {
        $msg_erro["campos"][] = "cli_tel_fix";
        $msg_erro["campos"][] = "cli_tel_cel";
    }

	if (!strlen($cep)) {
        $msg_erro["campos"][] = "cep";
    }
	if (!strlen($estado)) {
        $msg_erro["campos"][] = "estado";
    }
	if (!strlen($cidade)) {
        $msg_erro["campos"][] = "cidade";
    }
	if (!strlen($bairro)) {
        $msg_erro["campos"][] = "bairro";
    }
	if (!strlen($endereco)) {
        $msg_erro["campos"][] = "endereco";
    }
	if (!strlen($cli_numero_end)) {
        $msg_erro["campos"][] = "cli_numero";
    }
    
    //Fim validação campos Vazios

    //Mensagens de erro.
    //Validação Data
    if (strlen($data_notificacao) > 0 ) {

		list($di, $mi, $yi) = explode("/", $data_notificacao);

		if (!checkdate($mi, $di, $yi)) {
			$msg_erro["msg"][] = "Data Inválida";

		}
	}
	if (strlen($data_audiencia) > 0 ) {

		list($di, $mi, $yi) = explode("/", $data_audiencia);

		if (!checkdate($mi, $di, $yi)) {
			$msg_erro["msg"][] = "Data Inválida";

		}
	}
	if (strlen($data_audiencia2) > 0 ) {

		list($di, $mi, $yi) = explode("/", $data_audiencia2);

		if (!checkdate($mi, $di, $yi)) {
			$msg_erro["msg"][] = "Data Inválida";

		}
	}

	if (strlen($data_solucao) > 0 ) {

		list($di, $mi, $yi) = explode("/", $data_solucao);

		if (!checkdate($mi, $di, $yi)) {
			$msg_erro["msg"][] = "Data Inválida";

		}
	}

	if (count($msg_erro["campos"]) > 0) {
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
	}

	if (!empty($os_posto)) {
		$sql_os = "SELECT * FROM tbl_os WHERE os = $os_posto AND fabrica = $login_fabrica";
		$qry_os = pg_query($con, $sql_os);

		if (pg_num_rows($qry_os) == 0) {
			$msg_erro['msg'][] = "Ordem de serviço não encontrada";
		}
	}

	$erro_email = 0;
	if (strlen($cli_email)) {
		if(!filter_var($cli_email, FILTER_VALIDATE_EMAIL) ){
			$erro_email = 1;
			$msg_erro["campos"][] = "cli_email";
		}
	}

	if (strlen($adv_mail)) {
		if(!filter_var($adv_mail, FILTER_VALIDATE_EMAIL) ){
			$erro_email = 1;
			$msg_erro["campos"][] = "adv_mail";
		}
	}

	if ($erro_email == 1) {
		$msg_erro["msg"][] = "E-mail Inválido.";
	}


	if (strlen($cli_bairro) > 80) {
		$msg_erro["msg"][] = "O campo Bairro não pode ter mais de 80 caracteres";
        $msg_erro["campos"][] = "cli_bairro";
	}

	if (strlen($cli_endereco) > 80) {
		$msg_erro["msg"][] = "O campo Endereço não pode ter mais de 80 caracteres";
        $msg_erro["campos"][] = "cli_endereco";

	}
	//Fim mensagens de erro.
	//Grava no branco.

	if (count($msg_erro["msg"]) == 0) {
		
		$sql = "BEGIN TRANSACTION";
	    $resultX = pg_query($con, $sql);

		if(!empty($processo_id)) {		
			$sql_proc = "SELECT numero_processo as processo
						  FROM tbl_processo
						 WHERE processo = $processo_id
						AND fabrica = $login_fabrica;";
			$res_proc = pg_query($con,$sql_proc);
		}

		if (strlen($cidade)) {

			$sql_cit = "SELECT cidade FROM tbl_cidade WHERE UPPER(nome) = UPPER ('$cidade');";
			$res_cit = pg_query($con,$sql_cit);
			$cod_cidade = pg_fetch_result($res_cit,0, 'cidade');

		}

		if (strlen($produto_referencia)) {
			$sql_prod = "SELECT produto FROM tbl_produto WHERE UPPER(referencia) = UPPER ('$produto_referencia') AND fabrica_i = $login_fabrica;";
			$res_prod = pg_query($con,$sql_prod);
			$produto = pg_fetch_result($res_prod,0, 'produto');
		}else{
			$produto = "NULL";
		}
		if (strlen($chamado_referencia)) {
			$sql_c = "SELECT hd_chamado
		        FROM tbl_hd_chamado
		        WHERE hd_chamado = $chamado_referencia
		        AND fabrica = $login_fabrica;";
			$res_c = pg_query($con,$sql_c);
			if (pg_num_rows($res_c) == 0 ) {
				$msg_erro["msg"][] = "Atendimento não encontrato.";
			}
		}

			//Tratando campos vazios para o UPDATE
		$os_posto = ($os_posto == "") ? "NULL" : $os_posto;
		$motivo_principal = ($motivo_principal == "") ? "NULL" : $motivo_principal;
		$chamado_referencia = ($chamado_referencia == "") ? "NULL" : $chamado_referencia;

		//if($login_fabrica == 42){
			$data_notificacao = (formata_data($data_notificacao) == "") ? "NULL" : "'".formata_data($data_notificacao)."'";
			$data_audiencia = (formata_data($data_audiencia) == "") ? "NULL" : "'".formata_data($data_audiencia)."'";
			$data_audiencia2 = (formata_data($data_audiencia2) == "") ? "NULL" : "'".formata_data($data_audiencia2)."'";
			$data_solucao = (formata_data($data_solucao) == "") ? "NULL" :  "'".formata_data($data_solucao)."'";
		//}		
		if(pg_num_rows($res_proc) > 0){
			// Update do Processo

			if ($login_fabrica == 24) {
				$auditorLog = new AuditorLog();
				$auditorLog->retornaDadosSelect("SELECT tbl_processo.processo, 
													   	tbl_processo.fabrica, 
													   	tbl_processo.os, 
													   	tbl_processo.hd_chamado AS atendimento,
													   	tbl_processo.orgao AS orgao_do_processo,
													   	tbl_processo.consumidor_nome AS nome,
													   	tbl_processo.consumidor_cpf_cnpj AS cpf,
													   	tbl_processo.consumidor_fone1 AS telefone_1,
													   	tbl_processo.consumidor_fone2 AS telefone_2,
													   	tbl_processo.consumidor_email AS email,
													   	tbl_processo.consumidor_endereco AS endereco,
													   	tbl_processo.consumidor_bairro AS bairro,
													   	tbl_processo.consumidor_numero AS numero,
													   	tbl_processo.consumidor_complemento AS complemento,
													   	tbl_processo.cidade,
													   	tbl_processo.consumidor_cep AS cep,
													   	tbl_processo.produto,
													   	tbl_processo.data_notificacao,
														tbl_processo.data_audiencia1 AS data_audiencia_1,
														tbl_processo.data_audiencia2 AS data_audiencia_2,
														tbl_processo.data_solucao,
														tbl_processo.advogado_nome AS advogado, 
														tbl_processo.advogado_celular AS advogado_telefone,
														tbl_processo.advogado_email,
														tbl_processo.solucao, 
														tbl_processo.valor_cliente,
														tbl_processo.custo_advogado,
														tbl_processo.historico, 
														tbl_motivo_processo.descricao AS motivo_principal,
														tbl_processo.numero_processo AS numero_do_processo,
														tbl_processo.observacao, 
														tbl_processo.status_processo, 
														tbl_processo.fase_processual,
														tbl_processo.houve_acordo ,
														tbl_processo.data_transito_julgado, 
														tbl_processo.data_sentenca, 
														tbl_processo.data_execucao, 
														tbl_processo.comarca, 
														tbl_processo.valor_causa, 
														tbl_processo.partes_adversas,
														tbl_processo.data_input AS data_cadastro,
														tbl_admin.nome_completo
													FROM tbl_processo
													LEFT JOIN tbl_admin USING(admin)
													LEFT JOIN tbl_motivo_processo USING(motivo_processo)
													WHERE tbl_processo.processo = {$processo_id}
													AND tbl_processo.fabrica = {$login_fabrica}
												");
				$tpAuditor = "update";
			}

			if (!count($msg_erro["msg"]) > 0) {

				if (count($anexo_processo)>0) {
					$sql_inp = "SELECT data_input FROM tbl_processo WHERE processo = $processo_id;";
					$res_inp = pg_query($con,$sql_inp);
					if (pg_num_rows($res_inp)> 0) {
						$data_inp = pg_fetch_result($res_inp, 0, data_input);

						list($data_inp, $hora_inp) = explode(" ",$data_inp);
						list($ano,$mes,$dia) = explode("-",$data_inp) ;

					}
					//list($dia, $mes, $ano) =  explode("/",date('d/m/Y')) ;

					$arquivos = array();

					foreach ($anexo_processo as $key => $value) {
						if ($anexo_processo_s3[$key] != "t" && strlen($value) > 0) {
							$ext = preg_replace("/.+\./", "", $value);
							$arquivos[] = array(
								"file_temp" => $value,
								"file_new"  => "{$login_fabrica}_{$processo_id}_{$key}.{$ext}"
							);
						}
					}

					if (count($arquivos) > 0) {
						$s3->moveTempToBucket($arquivos, $ano, $mes, false);
					}
				}

				if(strlen(trim($observacao_audiencia)) > 0){//HD-3251974
					$xobservacao_audiencia = "'".$observacao_audiencia."'";
				}else{
					$xobservacao_audiencia = "NULL";
				}

				if (!empty(trim($produto))) {
					$xproduto = $produto;
				} else {
					$xproduto = 'null';
				}

				if(in_array($login_fabrica,array(11,42,172,183))){
					$campos_update = ", comarca = '$comarca', valor_causa = '$valor_causa', partes_adversas = '$partes_adversas',  data_execucao = $data_execucao, data_sentenca = $data_sentenca, data_transito_julgado = $data_transito_julgado, fase_processual = $fase_processual, houve_acordo = '$houve_cumprimento_acordo', status_processo = $status_processo  ";
					
					if ($login_fabrica == 183){
						$campos_update .= ",campos_adicionais = '{$campos_adicionais}'";
					}
				}

				$sql_up = "UPDATE tbl_processo
							SET
								numero_processo			= '$num_processo',
								fabrica				 	= '$login_fabrica',
								os  					= $os_posto,
								hd_chamado 				= $chamado_referencia,
								orgao					= '$orgao_processo',
								consumidor_nome 		= '$cli_nome',
								consumidor_cpf_cnpj		= '".preg_replace("/[\.\-\/]/", "",$cli_cpf)."',
								consumidor_fone1 		= '$cli_tel_fix',
								consumidor_fone2 		= '$cli_tel_cel',
								consumidor_email 		= '$cli_email',
								consumidor_endereco 	= '$endereco',
								consumidor_bairro 		= '$bairro',
								consumidor_numero 		= '$cli_numero_end',
								consumidor_complemento 	= '$cli_end_complemento',
								cidade 					= '$cod_cidade',
								consumidor_cep 			= '".preg_replace("/[\.\-\/]/", "",$cep)."',
								produto 				= $xproduto,
								data_notificacao 		= $data_notificacao,
								data_audiencia1 		= $data_audiencia,
								data_audiencia2 		= $data_audiencia2,
								data_solucao 			= $data_solucao,
								advogado_nome 			= '$nome_adv',
								advogado_celular		= '$adv_Tel_cel',
								advogado_email 			= '$adv_mail',
								solucao 				= '$solucao_audiencia',
								valor_cliente 			= '$valor_cliente',
								custo_advogado 			= '$custo_adv',
								historico 				= '$observacao',
								motivo_processo 		= $motivo_principal,
								observacao 				= $xobservacao_audiencia,
								admin 					= $login_admin
								$campos_update
			            	WHERE processo = '$processo_id';";
			    $res_up = pg_query($con,$sql_up);
			}
		}else{

			if ($login_fabrica == 24) {
				$auditorLog = new AuditorLog('insert');
				$tpAuditor = "insert";
			}
			
			$data_input = date('Y-m-d H:i');

			if(strlen(trim($observacao_audiencia)) > 0){//HD-3251974
				$xobservacao_audiencia = "'".$observacao_audiencia."'";
			}else{
				$xobservacao_audiencia = "NULL";
			}

			if (!count($msg_erro["msg"]) > 0) {

				/*Campos para makita*/
				if(in_array($login_fabrica,array(11,42,172,183))){
					$campos_insert = ", comarca, valor_causa, partes_adversas,  data_execucao, data_sentenca, data_transito_julgado, fase_processual, houve_acordo, status_processo  ";
					$values_insert = ", '$comarca', '$valor_causa', '$partes_adversas', $data_execucao, $data_sentenca, $data_transito_julgado, $fase_processual, '$houve_cumprimento_acordo', $status_processo  ";
					
					if ($login_fabrica == 183){
						$campos_insert .= ",campos_adicionais";
						$values_insert .= ", '{$campos_adicionais}'";
					}
				}
				$sql_ins = "INSERT 	INTO tbl_processo(
									numero_processo,
									fabrica,
									os,
									hd_chamado,
									orgao,
									consumidor_nome,
									consumidor_cpf_cnpj,
									consumidor_fone1,
									consumidor_fone2,
									consumidor_email,
									consumidor_endereco,
									consumidor_bairro,
									consumidor_numero,
									consumidor_complemento,
									cidade,
									consumidor_cep,
									produto,
									data_notificacao,
									data_audiencia1,
									data_audiencia2,
									data_solucao,
									advogado_nome,
									advogado_celular,
									advogado_email,
									solucao,
									valor_cliente,
									custo_advogado,
									historico,
									motivo_processo,
									admin,
									data_input,
									observacao
									$campos_insert
								) VALUES (
				              		'$num_processo',
				              		$login_fabrica,
				              		$os_posto,
				              		$chamado_referencia,
				              		'$orgao_processo',
				              		'$cli_nome',
				              		'".preg_replace("/[\.\-\/]/", "",$cli_cpf)."',
				              		'$cli_tel_fix',
				              		'$cli_tel_cel',
				              		'$cli_email',
				              		'$endereco',
				              		'$bairro',
				              		'$cli_numero_end',
				              		'$cli_end_complemento',
				              		$cod_cidade,
				              		'".preg_replace("/[\.\-\/]/", "",$cep)."',
				              		$produto,
									$data_notificacao,
									$data_audiencia,
									$data_audiencia2,
									$data_solucao,
									'$nome_adv',
									'$adv_Tel_cel',
									'$adv_mail',
									'$solucao_audiencia',
									$valor_cliente,
									$custo_adv,
									'$observacao',
									$motivo_principal,
									$login_admin,
									'$data_input',
									$xobservacao_audiencia
									$values_insert
			              		)returning processo ;";
			    $res_ins = pg_query($con,$sql_ins);
				$processo_id = pg_fetch_result($res_ins, 0, processo);

				//Anexo
				if (count($anexo_processo)>0) {
					list($dia, $mes, $ano) =  explode("/",date('d/m/Y')) ;

					$arquivos = array();

					foreach ($anexo_processo as $key => $value) {
						if ($anexo_processo_s3[$key] != "t" && strlen($value) > 0) {
							$ext = preg_replace("/.+\./", "", $value);
							$arquivos[] = array(
								"file_temp" => $value,
								"file_new"  => "{$login_fabrica}_{$processo_id}_{$key}.{$ext}"
							);
						}
					}

					if (count($arquivos) > 0) {
						$s3->moveTempToBucket($arquivos, $ano, $mes, false);
					}
				}
			}
		}

		if(in_array($login_fabrica,array(11,42,172,183))){

			for($a = 0; $a<=$qtde_info_gerais; $a++){
				$dados_itens[$a]['processo_item'] 			= (int)$_POST["processo_item"][$a];
				$dados_itens[$a]['tipo_documento'] 			= $_POST["tipo_documento"][$a];
				$dados_itens[$a]['data_notificacao'] 		= formata_data($_POST["data_notificacao"][$a]);
				

				if(strlen(trim($_POST["data_audiencia"][$a]))==0){
					$dados_itens[$a]['data_audiencia'] = 'null';
				}else{
					$dados_itens[$a]['data_audiencia'] 			= fnc_formata_data_hora_pg($_POST["data_audiencia"][$a]);
				}

				if(strlen(trim($_POST["data_audiencia2"][$a]))==0){
					$dados_itens[$a]['data_audiencia2'] = 'null';
				}else{
					$dados_itens[$a]['data_audiencia2'] 		= fnc_formata_data_hora_pg($_POST["data_audiencia2"][$a]);
				}
				
				$dados_itens[$a]['processo_pedido_cliente'] = $_POST["processo_pedido_cliente"][$a];
				$dados_itens[$a]['custo_etapa'] 			= $_POST["custo_etapa"][$a];
				$dados_itens[$a]['data_acordo']				= $_POST["data_acordo"][$a];

				if(strlen(trim($dados_itens[$a]['data_acordo']))==0){
					$dados_itens[$a]['data_acordo'] = 'null';
				}else{
					$dados_itens[$a]['data_acordo'] = "'".formata_data($dados_itens[$a]['data_acordo'])."'";
				}

				if(strlen(trim($dados_itens[$a]['data_cumprimento_acordo']))==0){
					$dados_itens[$a]['data_cumprimento_acordo'] = 'null';
				}else{
					$dados_itens[$a]['data_cumprimento_acordo'] = "'".formata_data($dados_itens[$a]['data_cumprimento_acordo'])."'";
				}

				$dados_itens[$a]['valor_acordo'] 			= $_POST["valor_acordo"][$a];
				$dados_itens[$a]['proposta_acordo'] 		= $_POST["proposta_acordo"][$a];
				$dados_itens[$a]['acordo'] 					= trim($_POST["acordo"][$a]);


				if(strlen(trim($dados_itens[$a]['valor_acordo']))==0){
					$dados_itens[$a]['valor_acordo'] = 0;
				}else{
					$dados_itens[$a]['valor_acordo'] = moneyDB($dados_itens[$a]['valor_acordo']);
				}
				
				if(strlen(trim($dados_itens[$a]['custo_etapa']))==0){
					$dados_itens[$a]['custo_etapa'] = 0;
				}else{
					$dados_itens[$a]['custo_etapa'] = "'".moneyDB($dados_itens[$a]['custo_etapa'])."'";	
				}	
				
				if(strlen(trim($dados_itens[$a]['data_notificacao']))==0){
					$data_notificacao = "null";
				}else{
					$data_notificacao = "'".($dados_itens[$a]['data_notificacao'])."'";	
				}	

				if (strlen(trim($dados_itens[$a]['proposta_acordo'])) == 0){
					$proposta_acordo = "null";
				}else{
					$proposta_acordo = $dados_itens[$a]['proposta_acordo'];
				}

				if(strlen($dados_itens[$a]['tipo_documento']) > 0 ){
					if($dados_itens[$a]['processo_item'] == 0){
						$sql_itens = "INSERT INTO tbl_processo_item (processo, tipo_documento, processo_pedido_cliente, proposta_acordo, data_notificacao, data_audiencia1,  data_audiencia2, custo_etapa, data_acordo, data_cumprimento_acordo, valor_acordo, obs_acordo) 
						values
						($processo_id, ".$dados_itens[$a]['tipo_documento'].", ".$dados_itens[$a]['processo_pedido_cliente'].", ".$proposta_acordo.", $data_notificacao, ".$dados_itens[$a]['data_audiencia'].", ".$dados_itens[$a]['data_audiencia2'].", ".$dados_itens[$a]['custo_etapa'] .", ".$dados_itens[$a]['data_acordo'].", ".$dados_itens[$a]['data_cumprimento_acordo'].", '".$dados_itens[$a]['valor_acordo'] ."', '".$dados_itens[$a]['acordo']."') ";				
					}else{
						$sql_itens = "UPDATE tbl_processo_item SET 
						 tipo_documento = ".$dados_itens[$a]['tipo_documento'].",
						  processo_pedido_cliente = ".$dados_itens[$a]['processo_pedido_cliente'].",
						  proposta_acordo = ".$proposta_acordo.", 
						  data_notificacao = $data_notificacao, 
						  data_audiencia1 = ".$dados_itens[$a]['data_audiencia'].",  
						  data_audiencia2 = ".$dados_itens[$a]['data_audiencia2'].", 
						  custo_etapa = ".$dados_itens[$a]['custo_etapa'] .", 
						  data_acordo = ".$dados_itens[$a]['data_acordo'].", 
						  data_cumprimento_acordo = ".$dados_itens[$a]['data_cumprimento_acordo'].", 
						  valor_acordo = '".$dados_itens[$a]['valor_acordo'] ."', 
						  obs_acordo = '".$dados_itens[$a]['acordo']."' 
						  WHERE processo_item = ".$dados_itens[$a]['processo_item'];
					}
					$res_itens = pg_query($con, $sql_itens);
				}		
			}
		}

		if(strlen(pg_last_error($con))==0){
			$sql = "COMMIT TRANSACTION";
        	$resultX = pg_query($con, $sql);
        	if ($login_fabrica == 24) {
				$auditorLog->retornaDadosSelect("SELECT tbl_processo.processo, 
													   	tbl_processo.fabrica, 
													   	tbl_processo.os, 
													   	tbl_processo.hd_chamado AS atendimento,
													   	tbl_processo.orgao AS orgao_do_processo,
													   	tbl_processo.consumidor_nome AS nome,
													   	tbl_processo.consumidor_cpf_cnpj AS cpf,
													   	tbl_processo.consumidor_fone1 AS telefone_1,
													   	tbl_processo.consumidor_fone2 AS telefone_2,
													   	tbl_processo.consumidor_email AS email,
													   	tbl_processo.consumidor_endereco AS endereco,
													   	tbl_processo.consumidor_bairro AS bairro,
													   	tbl_processo.consumidor_numero AS numero,
													   	tbl_processo.consumidor_complemento AS complemento,
													   	tbl_processo.cidade,
													   	tbl_processo.consumidor_cep AS cep,
													   	tbl_processo.produto,
													   	tbl_processo.data_notificacao,
														tbl_processo.data_audiencia1 AS data_audiencia_1,
														tbl_processo.data_audiencia2 AS data_audiencia_2,
														tbl_processo.data_solucao,
														tbl_processo.advogado_nome AS advogado, 
														tbl_processo.advogado_celular AS advogado_telefone,
														tbl_processo.advogado_email,
														tbl_processo.solucao, 
														tbl_processo.valor_cliente,
														tbl_processo.custo_advogado,
														tbl_processo.historico, 
														tbl_motivo_processo.descricao AS motivo_principal,
														tbl_processo.numero_processo AS numero_do_processo,
														tbl_processo.observacao, 
														tbl_processo.status_processo, 
														tbl_processo.fase_processual,
														tbl_processo.houve_acordo ,
														tbl_processo.data_transito_julgado, 
														tbl_processo.data_sentenca, 
														tbl_processo.data_execucao, 
														tbl_processo.comarca, 
														tbl_processo.valor_causa, 
														tbl_processo.partes_adversas,
														tbl_processo.data_input AS data_cadastro,
														tbl_admin.nome_completo
													FROM tbl_processo
													LEFT JOIN tbl_admin USING(admin)
													LEFT JOIN tbl_motivo_processo USING(motivo_processo)
													WHERE tbl_processo.processo = {$processo_id}
													AND tbl_processo.fabrica = {$login_fabrica}
												")->enviarLog($tpAuditor, 'tbl_processo', "$login_fabrica*$processo_id");
				if ($tpAuditor == 'update') {
					header("Location: cadastro_processos.php?num_processo={$processo_id}&msg=ok");
				} else {
					header("Location: cadastro_processos.php?num_processo={$processo_id}&msg=oki");	
				}
        	} else {
        		header("Location: cadastro_processos.php?num_processo={$processo_id}&msg=oki");
        	}
		}else{
			$sql = "ROLLBACK TRANSACTION";
        	$resultX = pg_query($con, $sql);
        	$msg_erro["msg"][] = "Erro ao inserir Processo";
		}	
	}
}

/**
 * Area para colocar os AJAX
 */

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
	$estado = strtoupper($_POST["estado"]);

	if (array_key_exists($estado, $array_estados())) {
		$sql = "SELECT DISTINCT * FROM (
					SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
					UNION (
						SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
					)
				) AS cidade
				ORDER BY cidade ASC";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$array_cidades = array();

			while ($result = pg_fetch_object($res)) {
				$array_cidades[] = $result->cidade;
			}

			$retorno = array("cidades" => $array_cidades);
		} else {
			$retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
		}
	} else {
		$retorno = array("error" => utf8_encode("estado não encontrado"));
	}

	exit(json_encode($retorno));
}

if(isset($_POST['ajax_busca_cep']) && !empty($_POST['cep'])){
	require_once __DIR__.'/../classes/cep.php';

	$cep = $_POST['cep'];

	try {
		$retorno = CEP::consulta($cep);
		$retorno = array_map(utf8_encode, $retorno);
	} catch(Exception $e) {
		$retorno = array("error" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

/**
* Cria a chave do anexo
*/
if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
} else {
    $anexo_chave = getValue("anexo_chave");
}
/**
* Inclui o arquivo no s3
*/
if (isset($_POST["ajax_anexo_upload"])) {
    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if ($ext == "jpeg") {
        $ext = "jpg";
    }

    if (strlen($arquivo["tmp_name"]) > 0) {
        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
            $retorno = array("error" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx"));
        } else {
            $arquivo_nome = "{$chave}_{$posicao}";

            $s3->tempUpload("{$arquivo_nome}", $arquivo);

            if($ext == "pdf"){
            	$link = "imagens/pdf_icone.png";
            } else if(in_array($ext, array("doc", "docx"))) {
            	$link = "imagens/docx_icone.png";
            } else {
	            $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
	        }

	        $href = $s3->getLink("{$arquivo_nome}.{$ext}", true);

            if (!strlen($link)) {
                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
            } else {
                $retorno = array("link" => $link, "arquivo_nome" => "{$arquivo_nome}.{$ext}", "href" => $href, "ext" => $ext);
            }
        }
    } else {
        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
    }

    $retorno["posicao"] = $posicao;

    exit(json_encode($retorno));
}

/**
* Excluir anexo
*/
if (isset($_POST["ajax_anexo_exclui"])) {
	$anexo_nome_excluir = $_POST['anexo_nome_excluir'];
	$numero_processo = $_POST['numero_processo'];

	$sql_ex = "SELECT data_input FROM tbl_processo WHERE processo = $numero_processo;";
	$res_ex = pg_query($con,$sql_ex);
	if (pg_num_rows($res_ex)> 0) {
		$data_ex = pg_fetch_result($res_ex, 0, data_input);

		list($data_ex, $hora_ex) = explode(" ",$data_ex);
		list($ano_ex,$mes_ex,$dia_ex) = explode("-",$data_ex) ;

	}

	if (count($anexo_nome_excluir) > 0) {
		$s3->deleteObject($anexo_nome_excluir, false, $ano_ex, $mes_ex);
		$retorno = array("ok" => utf8_encode("Excluído com sucesso!"));
	}else{
		$retorno = array("error" => utf8_encode("Erro ao excluir arquivo"));
	}
	 exit(json_encode($retorno));
}

include 'cabecalho_new.php';

$plugins = array(
   	"datepicker",
   	"shadowbox",
   	"maskedinput",
   	"alphanumeric",
   	"ajaxform",
	"price_format"
);

include 'plugin_loader.php';
?>
<script src="../plugins/FancyZoom/FancyZoom.js"></script>
<script src="../plugins/FancyZoom/FancyZoomHTML.js"></script>
<script type="text/javascript">

$(function() {

	<?php if(in_array($login_fabrica,array(11,42,172,183)) and !empty($num_processo)){?>
		CarregaDestinatarios('<?=$num_processo?>');
	<?php } ?>
	/**
	 * Carrega o datepicker já com as mascara para os campos
	 */
	//$("#data_nascimento").datepicker({ maxDate: 0, minDate: "-6d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_audiencia").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_transito_julgado").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_execucao").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_sentenca").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_notificacao").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_audiencia2").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_solucao").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	<?php if(in_array($login_fabrica,array(11,42,172,183))){ ?>
		$("#telefone_advogado").mask("(99)9999-9999");
		$("#cli_tel_fix").mask("(99)9999-9999");
		$("#cli_tel_cel").mask("(99)99999-9999");		
		$(".data_notificacao").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$(".data_audiencia").mask("99/99/9999 99:99");
		$(".data_audiencia2").mask("99/99/9999 99:99");
		$(".data_acordo").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$(".data_cumprimento_acordo").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");

		$("#data_lembrete").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		
		$("#orgao_processo").change(function(){
			var orgao_processo = $(this).val();
			if(orgao_processo == 'consumidor'){
				$(".obrigatorios_consumidor_retira").hide();
			}else{
			         <?php if ($login_fabrica <> 183){ ?>
					$(".obrigatorios_consumidor_retira").show();
				<?php } ?>
			}
		});
		
		<?php if ($login_fabrica == 183){ ?>
			$(".obrigatorios_consumidor_retira").hide();
			$("#data_nota_fiscal").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		<?php } ?>

		<?php if($orgao_processo == 'consumidor' || $_RESULT["orgao_processo"] == 'consumidor'){ ?>
			$(".obrigatorios_consumidor_retira").hide();
		<?php } ?>

		$("#os_posto").blur(function (){
			
			var os_posto = $("#os_posto").val();

			if((os_posto.length) >= 3){
				$.ajax({
					url: "cadastro_processos.php",
					type: "POST",
					data: { busca_os: true, os: os_posto },
					beforeSend: function() {
						//$("#div_anexo_"+posicao).find("button").hide();
						//$("#div_anexo_"+posicao).find("img.anexo_thumb").hide();
						//$("#div_anexo_"+posicao).find("img.anexo_loading").show();
					},
					complete: function(data) {
						data = $.parseJSON(data.responseText);
						if (data.error) {
							alert(data.error);
						} else {
							$("#codigo_posto").val(data.codigo_posto);
							$("#descricao_posto").val(data.nome);
							$("#produto_referencia").val(data.referencia);
							$("#produto_descricao").val(data.descricao);
							$("#status_chamado").val(data.status);
							$("#chamado_atendente").val(data.nome_completo);

							$("#cli_nome").val(data.nome_cliente);
							$("#consumidor_cpf").val(data.cpf);
							$("#cli_tel_fix").val(data.fone);
							$("#cli_tel_cel").val(data.celular);
							$("#cli_email").val(data.email);
							$("#cli_cep").val(data.cep);
							$("#cli_numero").val(data.numero);
							$("#cli_end_complemento").val(data.complemento);

							$("#cli_cep").blur();
						}
					}
				});
			}

		});

		$("#chamado_referencia").blur(function (){
			
			var atendimento = $("#chamado_referencia").val();

			if((atendimento.length) >= 3){
				$.ajax({
					url: "cadastro_processos.php",
					type: "POST",
					data: { busca_atendimento: true, atendimento: atendimento },
					beforeSend: function() {
						//$("#div_anexo_"+posicao).find("button").hide();
						//$("#div_anexo_"+posicao).find("img.anexo_thumb").hide();
						//$("#div_anexo_"+posicao).find("img.anexo_loading").show();
					},
					complete: function(data) {
						data = $.parseJSON(data.responseText);
						if (data.error) {
							alert(data.error);
						} else {
							$("#codigo_posto").val(data.codigo_posto);
							$("#descricao_posto").val(data.nome);
							$("#produto_referencia").val(data.referencia);
							$("#produto_descricao").val(data.descricao);
							$("#status_chamado").val(data.status);
							$("#chamado_atendente").val(data.nome_completo);

							$("#os_posto").val(data.os);
							$("#cli_nome").val(data.nome_cliente);
							$("#consumidor_cpf").val(data.cpf);
							$("#cli_tel_fix").val(data.fone);
							$("#cli_tel_cel").val(data.celular);
							$("#cli_email").val(data.email);
							$("#cli_cep").val(data.cep);
							$("#cli_numero").val(data.numero);
							$("#cli_end_complemento").val(data.complemento);

							$("#cli_cep").blur();
						}
					}
				});
			}

		});

	<?php } ?>
	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();

	/**
	 * Configurações do Alphanumeric
	 */
	$(".numeric").numeric();
	$("#cli_tel_fix, #cli_tel_cel, #adv_Tel_cel").numeric({ allow: "()- " });

	/**
	 * Evento que chama a função de lupa para a lupa clicada
	 */
	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	/**
	 * Mascaras
	 */
	$("#cli_cep").mask("99999-999");
	$("#cep_consulta").mask("99999-999");
	$("#cpf_consulta").mask("999.999.999-99");
	$("#cnpj_consulta").mask("99.999.999/9999-99");

	<?php
		if(strlen(getValue('consumidor_cpf')) > 0){
			if(strlen(getValue('consumidor_cpf')) > 14){
	?>
				$("#consumidor_cpf").mask("99.999.999/9999-99");
				$("label[for=consumidor_cpf]").html("CNPJ");
	<?php
			}else{
	?>
				$("#consumidor_cpf").mask("999.999.999-99");
				$("label[for=consumidor_cpf]").html("CPF");
	<?php
			}
	?>
	<?php
		}
	?>
	/**
	 * Evento de keypress do campo consumidor_cpf
	 * Irá verificar o tamanho do campo, se o tamanho já for 14(CPF) irá alterar a máscara para CNPJ e alterar o Label
	 */
	$("#consumidor_cpf").blur(function(){
		var tamanho = $(this).val().replace(/\D/g, '');

		if(tamanho.length > 11){
			$("#consumidor_cpf").mask("99.999.999/9999-99");
			$("label[for=consumidor_cpf]").html("CNPJ");
		}else{
			$("#consumidor_cpf").mask("999.999.999-99");
			$("label[for=consumidor_cpf]").html("CPF");
		}
	});

	$("#consumidor_cpf").focus(function(){
		$(this).unmask();
	});

	/**
	 * Evento para quando alterar o estado carregar as cidades do estado
	 */
	$("select[id$=_estado]").change(function() {
		busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "cli");
	});

	/**
	 * Evento para buscar o endereço do cep digitado
	 */
	$("input[id$=_cep]").blur(function() {
		if ($(this).attr("readonly") == undefined) {
			busca_cep($(this).val(), ($(this).attr("id") == "revenda_cep") ? "revenda" : "cli");
		}
	});

	/**
    * Eventos para anexar/excluir imagem
    */
    $("button.btn_acao_anexo").click(function(){
		var name = $(this).attr("name");

		if (name == "anexar") {
			$(this).trigger("anexar_s3", [$(this)]);
		}else{
			$(this).trigger("excluir_s3", [$(this)]);
		}
	});

    $("button.btn_acao_anexo").bind("anexar_s3",function(){

    	var posicao = $(this).attr("rel");

    	var button = $(this);

		$("input[name=anexo_upload_"+posicao+"]").click();
    });

    $("button.btn_acao_anexo").bind("excluir_s3",function(){

		var posicao = $(this).attr("rel");
		var numero_processo = $("#processo").val();

		var button = $(this);
		var nome_an_p = $("input[name='anexo["+posicao+"]']").val();
		// alert(nome_an_p);
		// return;
		$.ajax({
			url: "cadastro_processos.php",
			type: "POST",
			data: { ajax_anexo_exclui: true, anexo_nome_excluir: nome_an_p, numero_processo: numero_processo },
			beforeSend: function() {
				$("#div_anexo_"+posicao).find("button").hide();
				$("#div_anexo_"+posicao).find("img.anexo_thumb").hide();
				$("#div_anexo_"+posicao).find("img.anexo_loading").show();
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {
					$("#div_anexo_"+posicao).find("a[target='_blank']").remove();
					$("#baixar_"+posicao).remove();
					$(button).text("Anexar").attr({
						id:"anexar_"+posicao,
						class:"btn btn-mini btn-primary btn-block",
						name: "anexar"
					});
					$("input[name='anexo["+posicao+"]']").val("f");
					$("#div_anexo_"+posicao).prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

					$("#div_anexo_"+posicao).find("img.anexo_loading").hide();
					$("#div_anexo_"+posicao).find("button").show();
					$("#div_anexo_"+posicao).find("img.anexo_thumb").show();
			  		alert(data.ok);
				}

			}
		});
    });

	/**
    * Eventos para anexar imagem
    */
    $("form[name=form_anexo]").ajaxForm({
        complete: function(data) {
			data = $.parseJSON(data.responseText);

			if (data.error) {
				alert(data.error);
			} else {
				var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
				$(imagem).attr({ src: data.link });

				$("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

				var link = $("<a></a>", {
					href: data.href,
					target: "_blank"
				});

				$(link).html(imagem);

				$("#div_anexo_"+data.posicao).prepend(link);

				if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
					setupZoom();
				}

		        $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
			}

			$("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
			$("#div_anexo_"+data.posicao).find("button").show();
			$("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
    	}
    });
	$("input[name^=anexo_upload_]").change(function() {
		var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

		$("#div_anexo_"+i).find("button").hide();
		$("#div_anexo_"+i).find("img.anexo_thumb").hide();
		$("#div_anexo_"+i).find("img.anexo_loading").show();

		$(this).parent("form").submit();
    });

	/**
	* Bloquear os campos da tela
	*/
	<?php
	if(strlen( getValue('data_solucao') ) > 0 AND count($msg_erro["msg"]) == 0 AND !in_array($login_fabrica,array(11,42,172,183))){
	?>
	$("#div_consulta").hide();

	$("input").each(function(){
		//$(this).attr("disabled",'true');
		$(this).attr("readonly",'true');
	});

	$("select").each(function(){
		//$(this).attr("disabled",'true');
		$(this).attr("readonly",'true');
	});
	$("textarea").each(function(){
		//$(this).attr("disabled",'true');
		$(this).attr("readonly",'true');
	});
	$("span[rel=lupa]").each(function(){
		$(this).hide();
	})
	<?php
	}elseif(strlen($num_processo)>0 AND count($msg_erro["msg"]) == 0 AND !in_array($login_fabrica,array(11,42,172,183))) {
	//}elseif($_GET["num_processo"]<>""){
	?>
		$("#div_consulta").hide();

		$("#div_cadastro_processo input").each(function(){
			//$(this).attr("disabled",'true');
			$(this).attr("readonly",'true');
		});
		$("#div_cadastro_processo select").each(function(){
			$(this).attr("readonly",'true');
			$(this).css('pointer-events','none');
		});

		$("#div_informacoes_cliente input").each(function(){
			//$(this).attr("disabled",'true');
			$(this).attr("readonly",'true');
		});
		$("#div_informacoes_cliente select").each(function(){
			$(this).attr("readonly",'true');
			$(this).css('pointer-events','none');
		});

		$("#div_informacoes_atendimento input").each(function(){
			//$(this).attr("disabled",'true');
			$(this).attr("readonly",'true');
		});
		$("#div_informacoes_atendimento select").each(function(){
			$(this).attr("readonly",'true');
			$(this).css('pointer-events','none');
		});

	<?php
	}
	?>

	$("#adicionar").click(function(){
		var qtde_info_gerais = parseInt($("#qtde_info_gerais").val());
		qtde_info_gerais = qtde_info_gerais +1;				
		$("#qtde_info_gerais").val(qtde_info_gerais);

		var qtde_etapa = parseInt(qtde_info_gerais) + 1;

		$(".data_notificacao").removeClass('hasDatepicker').datepicker("destroy");
		$(".data_acordo").removeClass('hasDatepicker').datepicker("destroy");
		$(".data_cumprimento_acordo").removeClass('hasDatepicker').datepicker("destroy");

		var campos_extra = $(".info_gerais:first").clone();
		$(".campos_extra").append(campos_extra);
		$(".info_gerais:last").find("input").attr({value:''}).removeAttr('id').val("");
		//$(".campos_extra").find("input").attr({value:''}).removeAttr('id').val("");
		$(".info_gerais:last").find("select").val("");
		$(".info_gerais:last").find("textarea").val("");
		$(".campos_extra").find("select").attr('readonly',false);
		$(".campos_extra").find("select").css('pointer-events','all');

		$(".campos_extra").find("input").removeProp('readonly');

		$(".info_gerais:last").find(".separador").html("<div class='row-fluid'><div class='span1'></div><div class='span10'>						<br><b><span class='etapa_0'>Etapa "+qtde_etapa+"</span></b><hr class='hr' style='line-height:10px'></div><div class='span1'></div></div>");
		
		
		$(".data_notificacao").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$(".data_acordo").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$(".data_cumprimento_acordo").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");	

		$(".data_audiencia").mask("99/99/9999 99:99");
		$(".data_audiencia2").mask("99/99/9999 99:99");
		
			

		$('.valor_acordo, .custo_etapa').priceFormat({
			prefix: '',
            thousandsSeparator: '.',
            centsSeparator: ',',
            centsLimit: parseInt(2)
		});				
	});

	$("#tipo_lembrete").change(function(){
		var tipo_lembrete = $("#tipo_lembrete").val();
		if(tipo_lembrete == 'data_audiencia'){
			$("#label_data_lembrete").text('Data da Audiência')
			$("#data_audiencia_acordo").mask("99/99/9999 99:99");
		}
		if(tipo_lembrete == 'acordo_setenca'){
			$("#label_data_lembrete").text('Acordo/Sentença')
			$("#data_audiencia_acordo").mask("99/99/9999");
		}
	});

	$("#data_lembrete").change(function(){
		
		var tipo_lembrete = $("#tipo_lembrete").val();
		var processo = $("#processo").val();
		var data_lembrete = $("#data_lembrete").val();

		$.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: 'POST',
            data: {
            	busca_data_lembrete:true,            	
            	tipo_lembrete:tipo_lembrete,
            	data_lembrete:data_lembrete,
            	processo: processo
            },
            complete: function(data) {
            	var data = $.parseJSON(data.responseText);
            	if(data.erro){
            		alert(data.erro);
            		return false;
            	}
            	$("#data_audiencia_acordo").val(data.data);
            }
        });
	});

	$("#adicionar_lembrete").click(function(){

		var tipo_lembrete 	= $("#tipo_lembrete").val();
		var data_lembrete 	= $("#data_lembrete").val();
		var mensagem_lembrete = $("#mensagem_lembrete").val();
		var data_audiencia_acordo = $("#data_audiencia_acordo").val();
		var processo 				= $("#processo").val();

		var email_destinatario = $("#email_destinatario").val();
		var nome_destinatario = $("#nome_destinatario").val();

		$.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: 'POST',
            data: {
            	lembrete:true,
            	inserir:true,
            	tipo_lembrete:tipo_lembrete,
            	data_lembrete:data_lembrete,
            	mensagem_lembrete:mensagem_lembrete,
            	data_audiencia_acordo:data_audiencia_acordo,
            	email_destinatario:email_destinatario,
            	processo:processo,
            	nome_destinatario:nome_destinatario
            },
            complete: function(data) {
            	var data = $.parseJSON(data.responseText);
            	if(data.erro){
            		alert(data.erro);
            		return false;
            	}
                CarregaDestinatarios(processo);
            }
        });
	});
});


function CarregaDestinatarios(processo){
	$("#carrega_destinatario").load('cadastro_processos.php?processo='+processo+"&lembrete=true");
}


function remover(alerta){
	 var processo = $("#processo").val();
	 $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>",
        type: 'POST',
        data: {
        	remover_lembrete:true,
        	evento_alerta:alerta,
        	processo:processo
        },
        complete: function(data) {
        	data = data.responseText;
        	if((data.length) > 0){
        		alert(data);
        	}
            CarregaDestinatarios(processo);
        }
    });
}

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

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function retorna_produto (retorno) {
	$("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

function retorna_processo(retorno) {

	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);
}

/**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, cli_revenda, cidade) {
	$("#"+cli_revenda+"_cidade").find("option").first().nextAll().remove();

	if (estado.length > 0) {
		$.ajax({
			async: false,
			//url: "cadastro_processos.php",
			url: "cadastro_processos.php",
			type: "POST",
			data: { ajax_busca_cidade: true, estado: estado },
			beforeSend: function() {
				if ($("#"+cli_revenda+"_cidade").next("img").length == 0) {
					$("#"+cli_revenda+"_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
				}
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {
					$.each(data.cidades, function(key, value) {
						var option = $("<option></option>", { value: value, text: value});

						$("#"+cli_revenda+"_cidade").append(option);
					});
				}


				$("#"+cli_revenda+"_cidade").show().next().remove();
			}
		});
	}

	if(typeof cidade != "undefined" && cidade.length > 0){

		$('#cli_cidade option[value='+cidade+']').attr('selected','selected');

	}

}

/**
 * Função que faz um ajax para buscar o cep nos correios
 */
function busca_cep(cep, cli_revenda) {
	if (cep.length > 0) {
		var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		$.ajax({
			async: false,
			//url: "cadastro_processos.php",
			url: "cadastro_processos.php",
			type: "POST",
			data: { ajax_busca_cep: true, cep: cep },
			beforeSend: function() {
				$("#"+cli_revenda+"_estado").hide().after(img.clone());
				$("#"+cli_revenda+"_cidade").hide().after(img.clone());
				$("#"+cli_revenda+"_bairro").hide().after(img.clone());
				$("#"+cli_revenda+"_endereco").hide().after(img.clone());
			},
			complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
					$("#"+cli_revenda+"_cidade").show().next().remove();
				} else {
					$("#"+cli_revenda+"_estado").val(data.uf);

					busca_cidade(data.uf, cli_revenda);

					$("#"+cli_revenda+"_cidade").val(retiraAcentos(data.cidade).toUpperCase());

					if (data.bairro.length > 0) {
						$("#"+cli_revenda+"_bairro").val(data.bairro);
					}

					if (data.end.length > 0) {
						$("#"+cli_revenda+"_endereco").val(data.end);
					}
				}

				$("#"+cli_revenda+"_estado").show().next().remove();
				$("#"+cli_revenda+"_bairro").show().next().remove();
				$("#"+cli_revenda+"_endereco").show().next().remove();

				if ($("#"+cli_revenda+"_bairro").val().length == 0) {
					$("#"+cli_revenda+"_bairro").focus();
				} else if ($("#"+cli_revenda+"_endereco").val().length == 0) {
					$("#"+cli_revenda+"_endereco").focus();
				} else if ($("#"+cli_revenda+"_numero").val().length == 0) {
					$("#"+cli_revenda+"_numero").focus();
				}
			}
		});
	}
}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
<br />
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php
}
?>

<?php
if (strlen($_GET['msg']) > 0) {
	$msg_ok = $_GET["msg"];
	if ($msg_ok == 'oki') {
		if ($login_fabrica == 183){
			$xnum_processo = getValue('numero_processo_telecontrol');
		}
		$msg = "Processo cadastrado com sucesso {$xnum_processo}";
	}elseif ($msg_ok == 'ok') {
		$msg = "Processo atualizado com sucesso";
	}
?>
<br />
    <div class="alert alert-success">
		<h4> <? echo $msg;?></h4>
    </div>
<?php
}
if (strlen($_GET['excluir']) > 0) {
	$msg = "Anexo Excluído com Sucesso";

?>
<br />
    <div class="alert alert-success">
		<h4> <? echo $msg;?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='frm_pesquisa_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
<div id="div_consulta" class="tc_formulario">
<div class="titulo_tabela">Consulta</div>
<br>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span3'>
			<div class='control-group'>
				<label class='control-label'>Nome</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" name="nome_consulta" id="nome_consulta"  class='span12' value= "<?=$nome_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>CPF</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" name="cpf_consulta" id="cpf_consulta" class='span12' value= "<?=$cpf_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="cpf" />
					</div>
				</div>
   			</div>
   		</div>
		<div class="span3">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>CNPJ</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" name="cnpj_consulta" id="cnpj_consulta" class='span12' value= "<?=$cnpj_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="cnpj" />
					</div>
				</div>
   			</div>
   		</div>
   		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Numero Série</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" name="num_serie" id="num_serie" class='span12' value= "<?=$num_serie?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="serie" />
					</div>
				</div>
   			</div>
   		</div>
   		<div class="span1"></div>
   	</div>
   	<div class='row-fluid'>
		<div class='span1'></div>
		<div class="span3">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>CEP</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" name="cep_consulta" id="cep_consulta" class='span12' value= "<?=$cep_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="cep" />
					</div>
				</div>
   			</div>
   		</div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Ordem de Serviço</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" name="os_consulta" id="os_consulta" class='span12' value= "<?=$os_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="os" />
					</div>
				</div>
			</div>
		</div>
		<div class="span3">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Telefone</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" name="tel_consulta" id="tel_consulta" class='span12' value= "<?=$tel_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="telefone" />
					</div>
				</div>
   			</div>
   		</div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Protocolo</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" name="hd_consulta" id="hd_consulta" class='span12' value= "<?=$hd_consulta?>">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="processo" parametro="atendimento" />
					</div>
				</div>
   			</div>
   		</div>
   		<div class="span1"></div>
   	</div>
</div>
<br />
<?
if (strlen($admin_res)) {
?>
<div id="div_adm_responsavel" class="tc_formulario">
<div class="titulo_tabela">ADMIN RESPOSÁVEL</div>
<br />
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group'>
			<label class="control-label" for="admin_responsavel">Nome Completo</label>
			<div class="controls controls-row">
				<div class="span12">
					<input id="admin_responsavel" name="admin_responsavel" readonly class="span12 visibilidade" type="text" value="<?=getValue('admin_responsavel')?>"/>
				</div>
			</div>
		</div>
	</div>
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group' >
			<label class="control-label" for="data_input">Data de Cadastro</label>
			<div class="controls controls-row">
				<div class="span12">
					<input id="data_input" name="data_input" readonly class="span12 visibilidade" type="text" value="<?=getValue('data_input')?>"/>
				</div>
			</div>
		</div>
	</div>
	<div class="span3"></div>
	<div class="span1"></div>
</div>
</div>
<br />
<?
}
?>
<div id="div_cadastro_processo" class="tc_formulario">
<div class="titulo_tabela">Cadastro de Processo</div>
<br />
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group <?=(in_array("num_processo", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="num_processo">Número do Processo</label>
			<div class="controls controls-row">
				<div class="span12"><h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
					<input id="num_processo" name="num_processo" class="span12 visibilidade" type="text" value="<?=getValue('num_processo')?>" maxlength="100" />
				</div>
			</div>
		</div>
	</div>
	<?php if ($login_fabrica == 183){ ?>
		<div class="span3">
			<div class='control-group <?=(in_array("numero_processo_telecontrol", $msg_erro["campos"])) ? "error" : ""?>' >
				<label class="control-label" for="numero_processo_telecontrol">Número do Processo Telecontrol</label>
				<div class="controls controls-row">
					<div class="span12">
						<input readonly="true" id="numero_processo_telecontrol" name="numero_processo_telecontrol" class="span12 visibilidade" type="text" value="<?=getValue('numero_processo_telecontrol')?>" maxlength="100" />
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
	<div class="span3">
		<div class='control-group <?=(in_array("orgao_processo", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="orgao_processo">Orgão do Processo</label>
			<div class="controls controls-row">
				<div class="span12"><h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
					<?
					$array_orgao = array(	"Juizado"  => "Juizado",
						"Procon"  => "Procon");

					if(in_array($login_fabrica,array(11,42,172,183))){
						$array_orgao['consumidor'] = "Consumidor";
					}
					?>
					<select id="orgao_processo" name="orgao_processo" class="span12 visibilidade">
						<option value="">Selecione</option>
						<?
						foreach ($array_orgao as $sigla => $nome_orgao) {
							$selected = ($sigla == getValue('orgao_processo')) ? "selected" : "";

										echo "<option value='{$sigla}' {$selected} >{$nome_orgao}</option>";
						}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<?php if(in_array($login_fabrica,array(11,42,172))){ ?>
		<div class="span3">
			<div class='control-group <?=(in_array("comarca", $msg_erro["campos"])) ? "error" : ""?>' >
				<label class="control-label" for="comarca">Comarca</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
						<input type="text" maxlength="30" name="comarca" class='span12' id="comarca" value="<?=getValue('comarca')?>">
					</div>
				</div>
			</div>
		</div>
	<?php }else{?>
		<div class="span3"></div>
	<?php } ?>
	<div class="span1"></div>
</div>
<?php if(in_array($login_fabrica,array(11,42,172,183))){ ?>
<div class="row-fluid">
	<div class="span1"></div>
	<?php if ($login_fabrica == 183){ ?>
		<div class="span3">
			<div class='control-group <?=(in_array("comarca", $msg_erro["campos"])) ? "error" : ""?>' >
				<label class="control-label" for="comarca">Comarca</label>
				<div class="controls controls-row">
					<div class="span12">
						<input type="text" maxlength="30" name="comarca" class='span12' id="comarca" value="<?=getValue('comarca')?>">
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
	<div class="span3">
		<div class='control-group <?=(in_array("valor_causa", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="valor_causa">Valor da Causa</label>
			<div class="controls controls-row">
				<div class="span12">
					<input id="valor_causa" name="valor_causa" price="true" class="span12" type="text" value="<?=getValue('valor_causa')?>" maxlength="30" />
				</div>
			</div>
		</div>
	</div>
	<div class="span3">
		<div class='control-group <?=(in_array("Partes Adversas", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="partes_adversas">Partes Adversas</label>
			<div class="controls controls-row">
				<div class="span12">
					<input type="text" name="partes_adversas" class='span12' maxlength="30" id="partes_adversas" value="<?=getValue('partes_adversas')?>">
				</div>
			</div>
		</div>
	</div>
	<div class="span1"></div>
</div>
<?php	}?>
</div>
<?php if(in_array($login_fabrica,array(11,42,172,183))){ ?>
<div id="div_cadastro_processo" class="tc_formulario">
<div class="titulo_tabela">Cadastro do Advogado/Preposto</div>
<br />
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span4">
		<div class='control-group <?=(in_array("advogado", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="valor_causa">Advogado</label>
			<div class="controls controls-row"><h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
				<div class="span12">
					<input id="advogado" name="advogado" class="span12" type="text" value="<?=getValue('advogado')?>" maxlength="30" />
				</div>
			</div>
		</div>
	</div>
	<div class="span3">
		<div class='control-group <?=(in_array("telefone_advogado", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="valor_causa">Telefone</label>
			<div class="controls controls-row"><h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
				<div class="span12">
					<input id="telefone_advogado" name="telefone_advogado" class="span12" type="text" value="<?=getValue('telefone_advogado')?>" maxlength="30" />
				</div>
			</div>
		</div>
	</div>
	<div class="span3">
		<div class='control-group <?=(in_array("valor_cliente_advogado", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="partes_adversas">Valor Cliente</label>
			<div class="controls controls-row"><h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
				<div class="span12">
					<input type="text" name="valor_cliente_advogado" class='span12' price="true" maxlength="30" id="valor_cliente_advogado" value="<?=getValue('valor_cliente_advogado')?>">
				</div>
			</div>
		</div>
	</div>
	<div class="span1"></div>
</div>
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group <?=(in_array("custo_advogado", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="partes_adversas">Custo Advogado</label>
			<div class="controls controls-row"><h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
				<div class="span12">
					<input type="text" name="custo_advogado" class='span12' price="true" maxlength="30" id="custo_advogado" value="<?=getValue('custo_advogado')?>">
				</div>
			</div>
		</div>
	</div>
	<div class="span4">
		<div class="control-group  <?=(in_array('adv_mail', $msg_erro['campos'])) ? "error" : "" ?>" >
			<label class="control-label" for="adv_mail">E-mail</label>
			<div class="controls controls-row">
				<div class="span12">
					<h5 class='asteristico obrigatorios_consumidor_retira'>*</h5>
					<input id="adv_mail" name="adv_mail" class="span12" type="text" value="<?=getValue('adv_mail')?>" />
				</div>
			</div>
		</div>
	</div>
</div>

<?php } ?>




<br />
<div id="div_informacoes_atendimento" class="tc_formulario">
		<div class="titulo_tabela">Informações Detalhadas</div>
		<br />
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
	            <div class='control-group'>
	                <label class='control-label' for='chamado_referencia'>Protocolo</label>
	                <div class='controls controls-row'>
	                    <div class='span12 input-append'>
	                        <input id="chamado_referencia" name="chamado_referencia" class="span12 numeric visibilidade" type="text" value="<?=getValue('chamado_referencia')?>" />
	                    </div>
	                </div>
	            </div>
        	</div>
        	<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="status_chamado">Status</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="status_chamado" name="status_chamado" class="span12 visibilidade" type="text" value="<?=getValue('status_chamado')?>" maxlength="40" />
						</div>
					</div>
				</div>
			</div>
			<div class="span5">
				<div class='control-group' >
					<label class="control-label" for="chamado_atendente">Atendente</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="chamado_atendente" name="chamado_atendente" class="span12 visibilidade" type="text" value="<?=getValue('chamado_atendente')?>" maxlength="40" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12 visibilidade' value="<?=getValue('codigo_posto')?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12 visibilidade' value="<?=getValue('descricao_posto')?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class="span3">
				<div class="control-group">
					<label class="control-label" for="os_posto">Ordem de Serviço</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="os_posto" name="os_posto" class="span12 visibilidade" type="text" value="<?=getValue('os_posto')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span3'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
	                <div class='controls controls-row'>
	                    <div class='span10 input-append'>
	                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12 visibilidade' maxlength="20" value="<?=getValue('produto_referencia')?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
	                    </div>
	                </div>
	            </div>
        	</div>
	        <div class='span4'>
	            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
	                <div class='controls controls-row'>
	                    <div class='span11 input-append'>
	                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12 visibilidade' value="<?=getValue('produto_descricao')?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
	                    </div>
	                </div>
	            </div>
	        </div>
			<div class="span4"></div>
		</div>
		<?php if ($login_fabrica == 183){ ?>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class='span2'>
		            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
		                <label class='control-label' for='produto_nota_fiscal'>Nota Fiscal</label>
		                <div class='controls controls-row'>
		                    <div class='span12'>
		                        <input type="text" id="produto_nota_fiscal" name="produto_nota_fiscal" class='span12 visibilidade' maxlength="20" value="<?=$produto_nota_fiscal?>" >
		                    </div>
		                </div>
		            </div>
	        	</div>
		        <div class='span2'>
		            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
		                <label class='control-label' for='data_nota_fiscal'>Data Nota Fiscal</label>
		                <div class='controls controls-row'>
		                    <div class='span12'>
		                        <input type="text" id="data_nota_fiscal" name="data_nota_fiscal" class='span12 visibilidade' value="<?=$data_nota_fiscal?>" >
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class="span3">
					<div class="control-group ">
						<label class="control-label" for="garantia">Garantia</label>
						<div class="controls controls-row">
							<div class="span12">
								<select id="garantia" name="garantia" class="span12 visibilidade">
									<option <?=($garantia == "sim") ?"selected" : ""?> value="sim">SIM</option>
									<option <?=($garantia == "nao") ?"selected" : ""?> value="nao">NÃO</option>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="span1"></div>
			</div>
		<?php } ?>
	</div>
<br />

<br>
<div id="div_informacoes_cliente" class="tc_formulario">
<div class="titulo_tabela">Informações do Cliente</div>
<br />
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span4">
		<div class='control-group <?=(in_array("cli_nome", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="cli_nome">Nome</label>
			<div class="controls controls-row">
				<div class="span12"><h5 class='asteristico'>*</h5>
					<input id="cli_nome" name="cli_nome" class="span12 visibilidade" type="text" value="<?=getValue('cli_nome')?>" maxlength="100" />
				</div>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class='control-group <?=(in_array('consumidor_cpf', $msg_erro['campos'])) ? "error" : "" ?>' >
			<label class="control-label" for="consumidor_cpf">CPF</label>
			<div class="controls controls-row">
				<div class="span12"><h5 class='asteristico'>*</h5>
					<input id="consumidor_cpf" name="consumidor_cpf" class="span12 numeric visibilidade" type="text" value="<?=getValue('consumidor_cpf')?>" <?=$readonly?> />
				</div>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class='control-group <?=(in_array('cli_tel_fix', $msg_erro['campos'])) ? "error" : "" ?>'>
			<label class="control-label" for="cli_tel_fix">Telefone </label>
			<div class="controls controls-row">
				<div class="span12">
					<h5 class='asteristico'>*</h5>
					<input id="cli_tel_fix" name="cli_tel_fix" class="span12 visibilidade" type="text" value="<?=getValue('cli_tel_fix')?>" />
				</div>
			</div>
		</div>
	</div>

	<div class="span2">
		<div class='control-group'>
			<label class="control-label" for="cli_tel_cel">Celular</label>
			<div class="controls controls-row">
				<div class="span12">
					<input id="cli_tel_cel" name="cli_tel_cel" class="span12 visibilidade" type="text" value="<?=getValue('cli_tel_cel')?>" />
				</div>
			</div>
		</div>
	</div>
	<div class="span1"></div>
</div>

		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span4">
				<div class='control-group'>
					<label class="control-label" for="cli_email">Email</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="cli_email" name="cli_email" class="span12 visibilidade" type="text" value="<?=getValue('cli_email')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group <?=(in_array('cep', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="cli_cep">CEP</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="cli_cep" name="cli_cep" class="span12 visibilidade" type="text" value="<?=getValue('cli_cep')?>"/>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class="control-group <?=(in_array('estado', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="cli_estado">Estado</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
								<select id="cli_estado" name="cli_estado" class="span12 visibilidade">
									<option value="" >Selecione</option>
									<?php
									#O $array_estados está no arquivo funcoes.php
									foreach ($array_estados() as $sigla => $nome_estado) {
										$selected = ($sigla == getValue('cli_estado')) ? "selected" : "";
										if (mb_check_encoding($nome_estado, "UTF-8")) {
											$nome_estado = utf8_decode($nome_estado);	
										}
										echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
									}
									?>
								</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class="control-group <?=(in_array('cidade', $msg_erro['campos'])) ? "error" : "" ?>">
					<label class="control-label" for="cli_cidade">Cidade</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<select id="cli_cidade" name="cli_cidade" class="span12 visibilidade">
								<option value="" >Selecione</option>
								<?php

									if (strlen(getValue("cli_estado")) > 0) {
										$sql = "SELECT DISTINCT * FROM (
												SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".getValue("cli_estado")."')
													UNION (
														SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".getValue("cli_estado")."')
													)
												) AS cidade
												ORDER BY cidade ASC";
										$res = pg_query($con, $sql);

										if (pg_num_rows($res) > 0) {
											while ($result = pg_fetch_object($res)) {
												$selected  = (trim($result->cidade) == trim(getValue("cli_cidade"))) ? "SELECTED" : "";

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

			<div class="span1"></div>

		</div>

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span3">
				<div class='control-group <?=(in_array('bairro', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="cli_bairro">Bairro</label>
					<div class="controls controls-row">
						<div class="span12">
						<h5 class='asteristico'>*</h5>
							<input id="cli_bairro" name="cli_bairro" class="span12 visibilidade" type="text" maxlength="80" value="<?=getValue('cli_bairro')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class='control-group <?=(in_array('endereco', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="cli_endereco">Endereço</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="cli_endereco" name="cli_endereco" class="span12 visibilidade" type="text" value="<?=getValue('cli_endereco')?>" maxlength="80" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array('cli_numero', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="cli_numero">Número</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="cli_numero" name="cli_numero" class="span12 visibilidade" type="text" value="<?=getValue('cli_numero')?>" maxlength="10" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="cli_end_complemento">Complemento</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="cli_end_complemento" name="cli_end_complemento" class="span12 visibilidade" type="text" value="<?=getValue('cli_end_complemento')?>" maxlength="40" />
						</div>
					</div>
				</div>
			</div>

			<div class="span1"></div>
		</div>
	</div>
		<br />
	<div id="div_informacoes_gerais" class="tc_formulario">
		<div class="titulo_tabela">Informações Gerais</div>
		<br />
		<?php if(in_array($login_fabrica,array(11,42,172,183))){?>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10">
					<div class='control-group <?=(in_array("motivo_principal", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class="control-label" for="motivo_principal">Motivo Principal</label>
						<div class="controls controls-row">
							<div class="span12">
								<select id="motivo_principal" name="motivo_principal" class="span12">
									<option value="" >Selecione</option>
									<?php
									$sql_mot = "SELECT motivo_processo,descricao FROM tbl_motivo_processo WHERE fabrica = $login_fabrica AND ativo = 't'";
									$res_mot = pg_query($con,$sql_mot);
									if(pg_num_rows($res_mot)>0){
										while ($result = pg_fetch_object($res_mot)) {
													$selected  = (trim($result->motivo_processo) == trim(getValue("motivo_principal"))) ? "SELECTED" : "";

													echo "<option value='{$result->motivo_processo}' {$selected} >{$result->descricao} </option>";
												}
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class='info_gerais'>
				<div class='separador'>
					
				</div>
				<div class="row-fluid">
					<div class="span1"></div>
					<div class="span4">
						<div class='control-group <?=(in_array("tipo_documento", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class="control-label" for="tipo_documento">Tipo Documento</label>
							<div class="controls controls-row">
								<div class="span12">
								<h5 class='asteristico'>*</h5>
									<select name="tipo_documento[]" class="tipo_documento_0 span12">
										<option value="" >Selecione</option>
										<?php
										$sql_mot = "SELECT tipo_documento,descricao FROM tbl_tipo_documento WHERE fabrica = $login_fabrica AND ativo = 't'";
										$res_mot = pg_query($con,$sql_mot);
										if(pg_num_rows($res_mot)>0){
											while ($result = pg_fetch_object($res_mot)) {
														$selected  = (trim($result->tipo_documento) == trim($dados_itens[0]['tipo_documento'])) ? "SELECTED" : "";

														echo "<option value='{$result->tipo_documento}' {$selected} >{$result->descricao} </option>";
													}
										}
										?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<label class="control-label" for="data_notificacao">Data Notificação</label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="data_notificacao[]" class="span12 data_notificacao" type="text" value="<?=$dados_itens[0]["data_notificacao"]?>" />
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<label class="control-label" for="data_audiencia">Data Audiência 1</label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="data_audiencia[]" class="span12 data_audiencia" type="text" value="<?=$dados_itens[0]["data_audiencia"]?>" />
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<label class="control-label" for="data_audiencia2">Data Audiência 2</label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="data_audiencia2[]" class="span12 data_audiencia2" type="text" value="<?=$dados_itens[0]["data_audiencia2"]?>" />
								</div>
							</div>
						</div>
					</div>
					<div class="span1"></div>
				</div>
				<div class="row-fluid">
					<div class="span1"></div>
					<div class="span5"> 
						<div class='control-group <?=(in_array("processo_pedido_cliente_0", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class="control-label" for="processo_pedido_cliente">Pedido Cliente</label>
							<div class="controls controls-row">
								<div class="span12">
									<?php if ($login_fabrica != 183) { ?>
										<h5 class='asteristico'>*</h5>
									<?php } ?>
									<select name="processo_pedido_cliente[]" class="processo_pedido_cliente span12">
										<option value="" >Selecione</option>
										<?php
										$sql_mot = "SELECT processo_pedido_cliente,descricao FROM tbl_processo_pedido_cliente WHERE fabrica = $login_fabrica AND ativo = 't'";
										$res_mot = pg_query($con,$sql_mot);
										if(pg_num_rows($res_mot)>0){
											while ($result = pg_fetch_object($res_mot)) {
														$selected  = (trim($result->processo_pedido_cliente) == trim($dados_itens[0]["processo_pedido_cliente"])) ? "SELECTED" : "";

														echo "<option value='{$result->processo_pedido_cliente}' {$selected} >{$result->descricao} </option>";
													}
										}
										?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="span5"> 
						<div class='control-group <?=(in_array("proposta_acordo_0", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class="control-label" for="proposta_acordo">Proposta Acordo</label>
							<div class="controls controls-row">
								<div class="span12">
									<?php if ($login_fabrica != 183) { ?>
										<h5 class='asteristico'>*</h5>
									<?php } ?>
									<select name="proposta_acordo[]" class="proposta_acordo span12">
										<option value="" >Selecione</option>
										<?php
										$sql_mot = "SELECT proposta_acordo,descricao FROM tbl_proposta_acordo WHERE fabrica = $login_fabrica AND ativo = 't'";
										$res_mot = pg_query($con,$sql_mot);
										if(pg_num_rows($res_mot)>0){
											while ($result = pg_fetch_object($res_mot)) {
												$selected  = (trim($result->proposta_acordo) == trim($dados_itens[0]['proposta_acordo'])) ? "SELECTED" : "";

												echo "<option value='{$result->proposta_acordo}' {$selected} >{$result->descricao} </option>";
											}
										}
										?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="span1"></div>
				</div>
				<div class="row-fluid">
					<div class="span1"></div>
					<div class="span3">
						<div class='control-group' >
							<label class="control-label" for="custo_etapa">Custo Etapa</label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="custo_etapa[]" price="true" class="custo_etapa span12" type="text" value="<?=$dados_itens[0]["custo_etapa"]?>" />
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<label class="control-label" for="data_acordo">Data do Acordo</label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="data_acordo[]" class="data_acordo span12" type="text" value="<?=$dados_itens[0]["data_acordo"]?>" />
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<label class="control-label" for="data_cumprimento_acordo">Cumprimento Acordo </label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="data_cumprimento_acordo[]" class="data_cumprimento_acordo span12" type="text" value="<?=$dados_itens[0]["data_cumprimento_acordo"]?>" />
								</div>
							</div>
						</div>
					</div>
					<div class="span3">
						<div class='control-group' >
							<label class="control-label" for="valor_acordo"> Valor do acordo </label>
							<div class="controls controls-row">
								<div class="span12">
									<input name="valor_acordo[]" price="true" class="valor_acordo span12" type="text" value="<?=$dados_itens[0]["valor_acordo"]?>" />
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row-fluid">
					<div class="span1"></div>
					<div class="span10">
						<div class='control-group' >
							<label class="control-label" for="acordo">Acordo</label>
							<div class="controls controls-row">
								<div class="span12">
									<textarea name="acordo[]" class="acordo span12" > <?=$dados_itens[0]['acordo']?> </textarea>
								</div>
							</div>
						</div>
					</div>
					<input name="processo_item[]" class="processo_item span12" type="hidden" value="<?=$dados_itens[0]["processo_item"]?>" />
					<div class="span1"></div>
				</div>
				
			</div>
			<?php if($qtde_info_gerais > 0){ 
				for($b = 1; $b<=$qtde_info_gerais; $b++){ ?>
					<div class='row-fluid'><div class='span1'></div><div class='span10'>						<br><b><span>Etapa <?=($b+1)?></span></b><hr class='hr' style='line-height:10px'></div><div class='span1'></div></div>
					<div class='info_gerais'>
						<div class="row-fluid">
							<div class="span1"></div>
							<div class="span4">
								<div class='control-group <?=(in_array("tipo_documento_$b", $msg_erro["campos"])) ? "error" : ""?>'>
									<label class="control-label" for="tipo_documento">Tipo Documento</label>
									<div class="controls controls-row">
										<div class="span12">
											<select name="tipo_documento[]" class="tipo_documento span12">
												<option value="" >Selecione</option>
												<?php
												$sql_mot = "SELECT tipo_documento,descricao FROM tbl_tipo_documento WHERE fabrica = $login_fabrica AND ativo = 't'";
												$res_mot = pg_query($con,$sql_mot);
												if(pg_num_rows($res_mot)>0){
													while ($result = pg_fetch_object($res_mot)) {
															$selected  = (trim($result->tipo_documento) == trim($dados_itens[$b]['tipo_documento'])) ? "SELECTED" : "";
																echo "<option value='{$result->tipo_documento}' {$selected} >{$result->descricao} </option>";
															}
												}
												?>
											</select>
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label" for="data_notificacao">Data Notificação</label>
									<div class="controls controls-row">
										<div class="span12">
											<input name="data_notificacao[]" class="span12 data_notificacao" type="text" value="<?=$dados_itens[$b]["data_notificacao"]?>" />
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label" for="data_audiencia">Data Audiência 1</label>
									<div class="controls controls-row">
										<div class="span12">
											<input name="data_audiencia[]" class="span12 data_audiencia" type="text" value="<?=$dados_itens[$b]["data_audiencia"]?>" />
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label" for="data_audiencia2">Data Audiência 2</label>
									<div class="controls controls-row">
										<div class="span12">
											<input name="data_audiencia2[]" class="span12 data_audiencia2" type="text" value="<?=$dados_itens[$b]["data_audiencia2"]?>" />
										</div>
									</div>
								</div>
							</div>
							<div class="span1"></div>
						</div>
						<div class="row-fluid">
							<div class="span1"></div>
							<div class="span5"> 
								<div class='control-group <?=(in_array("processo_pedido_cliente_$b", $msg_erro["campos"])) ? "error" : ""?>'>
									<label class="control-label" for="processo_pedido_cliente">Pedido Cliente</label>
									<div class="controls controls-row">
										<div class="span12">
											<select name="processo_pedido_cliente[]" class="processo_pedido_cliente span12">
												<option value="" >Selecione</option>
												<?php
												$sql_mot = "SELECT processo_pedido_cliente,descricao FROM tbl_processo_pedido_cliente WHERE fabrica = $login_fabrica AND ativo = 't'";
												$res_mot = pg_query($con,$sql_mot);
												if(pg_num_rows($res_mot)>0){
													while ($result = pg_fetch_object($res_mot)) {
																$selected  = (trim($result->processo_pedido_cliente) == trim($dados_itens[$b]["processo_pedido_cliente"])) ? "SELECTED" : "";

																echo "<option value='{$result->processo_pedido_cliente}' {$selected} >{$result->descricao} </option>";
															}
												}
												?>
											</select>
										</div>
									</div>
								</div>
							</div>
							<div class="span5"> 
								<div class='control-group <?=(in_array("proposta_acordo_$b", $msg_erro["campos"])) ? "error" : ""?>'>
									<label class="control-label" for="proposta_acordo">Proposta Acordo</label>
									<div class="controls controls-row">
										<div class="span12">
											<select name="proposta_acordo[]" class="proposta_acordo span12">
												<option value="" >Selecione</option>
												<?php
												$sql_mot = "SELECT proposta_acordo,descricao FROM tbl_proposta_acordo WHERE fabrica = $login_fabrica AND ativo = 't'";
												$res_mot = pg_query($con,$sql_mot);
												if(pg_num_rows($res_mot)>0){
													while ($result = pg_fetch_object($res_mot)) {
														$selected  = (trim($result->proposta_acordo) == trim($dados_itens[$b]["proposta_acordo"])) ? "SELECTED" : "";

														echo "<option value='{$result->proposta_acordo}' {$selected} >{$result->descricao} </option>";
													}
												}
												?>
											</select>
										</div>
									</div>
								</div>
							</div>
							<div class="span1"></div>
						</div>
						<div class="row-fluid">
							<div class="span1"></div>
							<div class="span3">
								<div class='control-group' >
									<label class="control-label" for="custo_etapa">Custo Etapa</label>
									<div class="controls controls-row">
										<div class="span12">
											<input name="custo_etapa[]" price="true" class="custo_etapa span12" type="text" value="<?=$dados_itens[$b]["custo_etapa"]?>" />
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label" for="data_acordo">Data do Acordo</label>
									<div class="controls controls-row">
										<div class="span12">
											<input name="data_acordo[]" class="data_acordo span12" type="text" value="<?=$dados_itens[$b]["data_acordo"]?>" />
										</div>
									</div>
								</div>
							</div>
							<div class="span2">
								<div class='control-group' >
									<label class="control-label" for="data_cumprimento_acordo">Cumprimento Acordo </label>
									<div class="controls controls-row">
										<div class="span12">
											<input name="data_cumprimento_acordo[]" class="data_cumprimento_acordo span12" type="text" value="<?=$dados_itens[$b]["data_cumprimento_acordo"]?>" />
										</div>
									</div>
								</div>
							</div>
							<div class="span3">
								<div class='control-group' >
									<label class="control-label" for="valor_acordo"> Valor do acordo </label>
									<div class="controls controls-row">
										<div class="span12">
											<input name="valor_acordo[]" price="true" class="valor_acordo span12" type="text" value="<?=$dados_itens[$b]["valor_acordo"]?>" />
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row-fluid">
							<div class="span1"></div>
							<div class="span10">
								<div class='control-group' >
									<label class="control-label" for="acordo">Acordo</label>
									<div class="controls controls-row">
										<div class="span12">
											<textarea name="acordo[]" class="acordo span12"  > <?=$dados_itens[$b]['acordo']?> </textarea>
										</div>
									</div>
								</div>
							</div>
							<div class="span1"></div>
						</div>
					</div>
					<input name="processo_item[]" class="processo_item span12" type="hidden" value="<?=$dados_itens[$b]["processo_item"]?>" />

			<?php } } ?>

			<div class="campos_extra">	
			</div>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10" style="text-align:right"><br> 
				<input type="hidden" name="qtde_info_gerais" id="qtde_info_gerais" value="<?=(empty($qtde_info_gerais)? "0": $qtde_info_gerais)?>">
					<button id="adicionar" class="btn" type="button">Adicionar</button> </div>
				<div class="span1" ></div>
			</div>


		<?php }else{ ?>
	    <div class="row-fluid">
			<div class="span1"></div>
			<div class="span4">
				<div class='control-group <?=(in_array("motivo_principal", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="motivo_principal">Motivo Principal</label>
					<div class="controls controls-row">
						<div class="span12">
							<select id="motivo_principal" name="motivo_principal" class="span12">
								<option value="" >Selecione</option>
								<?php
								$sql_mot = "SELECT motivo_processo,descricao FROM tbl_motivo_processo WHERE fabrica = $login_fabrica AND ativo = 't'";
								$res_mot = pg_query($con,$sql_mot);
								if(pg_num_rows($res_mot)>0){
									while ($result = pg_fetch_object($res_mot)) {
												$selected  = (trim($result->motivo_processo) == trim(getValue("motivo_principal"))) ? "SELECTED" : "";

												echo "<option value='{$result->motivo_processo}' {$selected} >{$result->descricao} </option>";
											}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_notificacao">Data Notificação</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_notificacao" name="data_notificacao" class="span12" type="text" value="<?=getValue('data_notificacao')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_audiencia">Data Audiência 1</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_audiencia" name="data_audiencia" class="span12" type="text" value="<?=getValue('data_audiencia')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="data_audiencia2">Data Audiência 2</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_audiencia2" name="data_audiencia2" class="span12" type="text" value="<?=getValue('data_audiencia2')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span8">
				<div class="control-group">
					<label class="control-label" for="solucao_audiencia">Solução</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="solucao_audiencia" name="solucao_audiencia" class="span12" type="text" value="<?=getValue('solucao_audiencia')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">

				<div class='control-group' >
					<label class="control-label" for="data_solucao">Data Solução</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_solucao" name="data_solucao" class="span12" type="text" value="<?=getValue('data_solucao')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span4">
				<div class='control-group <?=(in_array("nome_adv", $msg_erro["campos"])) ? "error" : ""?>' >
					<label class="control-label" for="nome_adv">Advogado/Preposto/CIP</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="nome_adv" name="nome_adv" class="span12" type="text" value="<?=getValue('nome_adv')?>" maxlength="100" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2">
				<div class="control-group  <?=(in_array('adv_Tel_cel', $msg_erro['campos'])) ? "error" : "" ?>" >
					<label class="control-label" for="adv_Tel_cel">Telefone Celular</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="adv_Tel_cel" name="adv_Tel_cel" class="span12" type="text" value="<?=getValue('adv_Tel_cel')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group  <?=(in_array('adv_mail', $msg_erro['campos'])) ? "error" : "" ?>" >
					<label class="control-label" for="adv_mail">E-mail</label>
					<div class="controls controls-row">
						<div class="span12">
							<h5 class='asteristico'>*</h5>
							<input id="adv_mail" name="adv_mail" class="span12" type="text" value="<?=getValue('adv_mail')?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>


		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span3">
				<div class='control-group' >
					<label class="control-label" for="valor_cliente">Valor Cliente</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="valor_cliente" name="valor_cliente" price="true" class="span12" type="text" value="<?= priceFormat(getValue('valor_cliente'));?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span3">
				<div class='control-group' >
					<label class="control-label" for="custo_adv">Custo Advogado</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="custo_adv" name="custo_adv" price="true" class="span12" type="text" value="<?= priceFormat(getValue('custo_adv'));?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span10">
				<div class='control-group' >
					<label class="control-label" for="observacao">Histórico</label>
					<div class="controls controls-row">
						<div class="span12">
							<textarea id="observacao" name="observacao" class="span12" style="height: 100px;" ><?=getValue("observacao")?></textarea>
						</div>
					</div>
				</div>
			</div>
			<div class="span1"></div>
		</div>
		<br />
		<?php if($login_fabrica == 81){//HD-3251974 ?>
			<div class="row-fluid">
				<div class="span1"></div>

				<div class="span10">
					<div class='control-group' >
						<label class="control-label" for="observacao">Observação Audiência</label>
						<div class="controls controls-row">
							<div class="span12">
								<textarea id="observacao_audiencia" name="observacao_audiencia" class="span12" style="height: 100px;" ><?=getValue("observacao_audiencia")?></textarea>
							</div>
						</div>
					</div>
				</div>
				<div class="span1"></div>
			</div>
			<br />
		<?php } 

	}	

		?>
<?php if(in_array($login_fabrica,array(11,42,172,183))){ ?>
<div id="div_informacoes_cliente" class="tc_formulario">
<div class="titulo_tabela">Informações Andamento Processo</div>
<br />
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group <?=(in_array("status_processo", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="cli_nome">Status Processo</label>
			
				<div class="span12">
					<?php if ($login_fabrica == 183) { ?>
						<h5 class='asteristico'>*</h5>
					<?php } ?>
					<select name="status_processo" class="span12">
						<option value="">Status Processo</option>
						<?php 
							$sql = "SELECT * from tbl_status_processo WHERE fabrica = $login_fabrica";
							$res = pg_query($con, $sql);
							for($i =0; $i<pg_num_rows($res); $i++){
								$status_processo_bd 	= pg_fetch_result($res, $i, status_processo);
								$descricao 			= pg_fetch_result($res, $i, descricao);

								$selected = ($status_processo_bd == $status_processo) ? " selected " : "";

								echo "<option value='$status_processo_bd' $selected >$descricao</option>";	
							}
						?>
					</select>
				</div>
			
		</div>
	</div>
	<div class="span3">
		<div class='control-group <?=(in_array("fase_processual", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="cli_nome"> Fase Processual</label>
			<div class="controls controls-row">
				<div class="span12">
					<select name="fase_processual" class="span12">
						<option value="">Fase Processual</option>
						<?php 
							$sql = "SELECT * from tbl_fase_processual WHERE fabrica = $login_fabrica";
							$res = pg_query($con, $sql);
							for($i =0; $i<pg_num_rows($res); $i++){
								$fase_processual_db 	= pg_fetch_result($res, $i, fase_processual);
								$descricao 			= pg_fetch_result($res, $i, descricao);

								$selected = ($fase_processual_db == $fase_processual) ? " selected " : "";

								echo "<option value='$fase_processual_db' $selected >$descricao</option>";	
							}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class="span4">
		<div class='control-group <?=(in_array('houve_cumprimento_acordo', $msg_erro['campos'])) ? "error" : "" ?>'>
			<label class="control-label" for="data_sentenca">Houve cumprimento do acordo</label>
			<div class="controls controls-row">
				<div class="span12">
					<label class="checkbox inline">
					  <input type="radio" name="houve_cumprimento_acordo" id="houve_cumprimento_acordo" <?php echo ($houve_cumprimento_acordo == 'sim') ? " checked " : ""; ?> value="sim"> Sim
					</label>
					<label class="checkbox inline">
					  <input type="radio" name="houve_cumprimento_acordo" id="houve_cumprimento_acordo" <?php echo ($houve_cumprimento_acordo == 'nao') ? " checked " : ""; ?> value="nao"> Não
					</label>
					<label class="checkbox inline">
					  <input type="radio" name="houve_cumprimento_acordo" id="houve_cumprimento_acordo" <?php echo ($houve_cumprimento_acordo == 'nao_acordo') ? " checked " : ""; ?> value="nao_acordo"> Não houve acordo
					</label>
				</div>
			</div>
		</div>
	</div>
	<div class="span1"></div>
</div>
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span3">
		<div class='control-group <?=(in_array('data_transito_julgado', $msg_erro['campos'])) ? "error" : "" ?>' >
			<label class="control-label" for="data_transito_julgado">Data do Transito Julgado</label>
			<div class="controls controls-row">
				<div class="span12">
					<input id="data_transito_julgado" name="data_transito_julgado" class="span12" type="text" value="<?=getValue('data_transito_julgado')?>" <?=$readonly?> />
				</div>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class='control-group <?=(in_array('data_sentenca', $msg_erro['campos'])) ? "error" : "" ?>'>
			<label class="control-label" for="data_sentenca">Data da Sentença</label>
			<div class="controls controls-row">
				<div class="span12">
					
					<input id="data_sentenca" name="data_sentenca" class="span12" type="text" value="<?=getValue('data_sentenca')?>" />
				</div>
			</div>
		</div>
	</div>

	<div class="span2">
		<div class='control-group <?=(in_array('data_execucao', $msg_erro['campos'])) ? "error" : "" ?>'>
			<label class="control-label" for="data_execucao">Data da Execução</label>
			<div class="controls controls-row">
				<div class="span12">
					
					<input id="data_execucao" name="data_execucao" class="span12" type="text" value="<?=getValue('data_execucao')?>" />
				</div>
			</div>
		</div>
	</div>

	<?php if ($login_fabrica == 183){ ?>
	<div class="span2">
		<div class='control-group <?=(in_array("data_solucao", $msg_erro["campos"])) ? "error" : ""?>' >
			<label class="control-label" for="data_solucao">Data Solução</label>
			<div class="controls controls-row">
				<div class="span12">
					<input id="data_solucao" name="data_solucao" class="span12" type="text" value="<?=getValue('data_solucao')?>" />
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
	<div class="span1"></div>
</div>
</div>
<div class="row-fluid">
	<div class="span1"></div>
	<div class="span10">
		<div class='control-group' >
			<label class="control-label" for="observacao">Observação</label>
			<div class="controls controls-row">
				<div class="span12">
					<textarea id="observacao" name="observacao" class="span12" style="height: 100px;" ><?=getValue("observacao")?></textarea>
				</div>
			</div>
		</div>
	</div>
	<div class="span1"></div>	

</div>
<?php } ?>
<?php if(in_array($login_fabrica,array(11,42,172,183)) and !empty($processo_id) ){ ?>

<br>		
<div id="div_lembretes" class="tc_formulario">

	<div class="titulo_tabela">Lembretes - Audiência e Cumprimento de Acordo/Sentença</div>
	<br>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span3">
			<div class='control-group <?=(in_array('tipo_lembrete', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="tipo_lembrete">Tipo Lembrete</label>
				<div class="controls controls-row">
					<div class="span12">
						<select id="tipo_lembrete" name="tipo_lembrete" class="span12">
							<option value=""></option>
							<option value="acordo_setenca">Cumprimento de Acordo/Sentença</option>
							<option value="data_audiencia">Data Audiência</option>
							<option value="lembrete_notificacao">Lembrete - Notificação</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class='control-group <?=(in_array('tipo_lembrete', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="labellembrete" >Data Lembrete</label>
				<div class="controls controls-row">
					<div class="span12">
						<input type="text" name="data_lembrete" id='data_lembrete' value="" class="span12">
					</div>
				</div>			
			</div>
		</div>
		<div class="span2">
			<div class='control-group <?=(in_array('tipo_lembrete', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" id="label_data_lembrete">Data</label>
				<div class="controls controls-row">
					<div class="span12">
						<input type="text" name="data_audiencia_acordo" id='data_audiencia_acordo' value="" class="span12" readonly="true">
					</div>
				</div>			
			</div>
		</div>

		<div class="span4">
			<div class='control-group <?=(in_array('tipo_lembrete', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="mensagem_lembrete">Mensagem</label>
				<div class="controls controls-row">
					<div class="span12">
						<textarea name='mensagem_lembrete' id='mensagem_lembrete'></textarea>
					</div>
				</div>			
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span3">
			<label class="control-label" for="email_destinatario">E-mail Destinatário</label>
			<div class="controls controls-row">
				<div class="span12">
					<input type="text" name="email_destinatario" id='email_destinatario' value="" class="span12">
				</div>
			</div>
		</div>
		<div class="span4">
			<label class="control-label" for="nome_destinatario">Nome Destinatário</label>
			<div class="controls controls-row">
				<div class="span12">
					<input type="text" name="nome_destinatario" id='nome_destinatario' value="" class="span12">
				</div>
			</div>
		</div>
		<div class="span4">
			<label class="control-label" for="nome_destinatario"></label>
			<div class="controls controls-row">
				<div class="span12">
					<button type='button' id="adicionar_lembrete" class='btn btn-success'>Adicionar</button>
				</div>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span10 tac">
			[ Informe o(s) Destinatário(s) e clique em Adicionar ]
		</div>
		<div class="span1"></div>
	</div>


	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span10" >
			<table width="700" align="center" class="table table-striped table-bordered table-fixed">				
				<tr class="titulo_coluna">
					<th>Data Lembrete</th>
					<th>Tipo Lembrete</th>
					<th>Nome Destinatário</th>
					<th>E-mail Destinatário</th>
					<th>Ações</th>
				</tr>
				<tbody id="carrega_destinatario">
				<!-- Esta utlizando função ajax carregadestinatario(num_processo)-->
				</tbody>
			</table>

		</div>
		<div class="span1"></div>
	</div>
<?php } ?>
<br>
		<!-- ANexo -->
		<div id="div_anexos" class="tc_formulario">
			<div class="titulo_tabela">Anexo(s)</div>
			
			<br>
			<div class="tac" >
			<?php
			$fabrica_qtde_anexos = 5;
			if ($fabrica_qtde_anexos > 0) {
				if (strlen(getValue("data_input"))> 0) {
					list($data_inp, $hora_inp) = explode(" ",getValue("data_input"));
					list($dia,$mes,$ano) = explode("/",$data_inp) ;
					//echo $dia."/".$mes."//".$ano;
				}

				echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

				$int_processo = (int) $_GET['num_processo'];
				$alt_num_processo = 0;

				if (!empty($int_processo)) {
					$sql_num_processo = "SELECT numero_processo FROM tbl_processo WHERE processo = $int_processo";
					$res_num_processo = pg_query($con, $sql_num_processo);

					$alt_num_processo = pg_fetch_result($res_num_processo, 0, 'numero_processo');
				}

				for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
					unset($anexo_link);

					$anexo_imagem = "imagens/imagem_upload.png";
					$anexo_s3     = false;
					$anexo        = "";

					if (strlen(getValue("anexo[{$i}]")) > 0 && getValue("anexo_s3[{$i}]") != "t") {

					 	$anexos       = $s3->getObjectList(getValue("anexo[{$i}]"), true);

					 	$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

					 	if ($ext == "pdf") {
					 		$anexo_imagem = "imagens/pdf_icone.png";
					 	} else if (in_array($ext, array("doc", "docx"))) {
					 		$anexo_imagem = "imagens/docx_icone.png";
					 	} else {
					 		$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
					 	}

					 	$anexo_link = $s3->getLink(basename($anexos[0]), true);

					 	$anexo        = getValue("anexo[$i]");
					 } else if(strlen($num_processo) > 0) {

					    $anexos = $s3->getObjectList("{$login_fabrica}_{$num_processo}_{$i}", false, $ano, $mes);

						if (empty($anexos) and !empty($alt_num_processo)) {
							$anexos = $s3->getObjectList("{$login_fabrica}_{$alt_num_processo}_{$i}", false, $ano, $mes);
						}

					    if (count($anexos) > 0) {

					 		$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
					 		if ($ext == "pdf") {
					 			$anexo_imagem = "imagens/pdf_icone.png";
					 		} else if (in_array($ext, array("doc", "docx"))) {
					 			$anexo_imagem = "imagens/docx_icone.png";
					 		} else {
					 			$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
					 		}

					 		$anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);

					 		$anexo        = basename($anexos[0]);
					 		$anexo_s3     = true;
					    }
					}
					?>
					<div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
						<?php if (isset($anexo_link)) { ?>
							<a href="<?=$anexo_link?>" target="_blank" >
						<?php } ?>

						<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

						<?php if (isset($anexo_link)) { ?>
							</a>
							<script>setupZoom();</script>
						<?php } ?>

						<?php
						if ($anexo_s3 === false) {
						?>
						    <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" >Anexar</button>
						<?php
						}
						?>

						<img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

						<input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
						<input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
						<?php
						if ($anexo_s3 === true) {?>
							<button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button>
							<button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>

		            	<?php
		            	}
		            	?>
					</div>
	            <?php
				}
	        }
			?>
			</div>
			<br />
		</div>
		<!-- Fim anexo-->
		<br />
		<p class="tac">
			<?php
				if ($login_fabrica == 24 && ( isset($_GET['num_processo']) && $_GET['num_processo'] != "")) {
			?>
					<input type="submit" class="btn btn-primary" name="alterar" value="Alterar" />
			<?php
				} else {
			?>
					<input type="submit" class="btn" name="gravar" value="Gravar" />
			<?php 
				}
			?>
			<input type="hidden" name='processo_id' id='processo' value='<?=$processo_id?>'>
		</p>
		<br />
		<?php
			if ($login_fabrica == 24) {
		?>
				<div class='row-fluid'>
					<div class='span9'></div>
					<div class='span3'>
						<div class="control-group">
			            	<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_processo&id=<?php echo $num_processo; ?>' name="btnAuditorLog">Visualizar Log Auditor</a>
			        </div>
					</div>
				</div>	
		<?php
			}
		?>
	</div>
</FORM>
<?php
//Inicio anexo
if ($fabrica_qtde_anexos > 0) {
	for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
    ?>
		<form name="form_anexo" method="post" action="cadastro_processos.php" enctype="multipart/form-data" style="display: none;" >
			<input type="file" name="anexo_upload_<?=$i?>" value="" />

			<input type="hidden" name="ajax_anexo_upload" value="t" />
			<input type="hidden" name="anexo_posicao" value="<?=$i?>" />
			<input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
		</form>
	<?php
	}
}
//Fim anexo
?>

<br />

<?php

	if ($login_fabrica == 24 && (isset($_GET['num_processo']) && $_GET['num_processo'] != "")) { 
?>
		<script type="text/javascript">
			window.onload = function() {
				$(".visibilidade").attr("readonly",false);
		        $(".visibilidade").css('pointer-events','all');
		    }		
		</script>
<?php 
	}

include "rodape.php";

?>
