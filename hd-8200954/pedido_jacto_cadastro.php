<?
 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

if (isset($_POST["ajax_valida_marca"])) {

	$marca = $_POST['marca'];

	$sql = "SELECT classe, classe_pedido, mensagem, prazo
			FROM tbl_classe_pedido
			WHERE ativo
			AND fabrica = {$login_fabrica}
			AND marca = {$marca}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		while ($dadosClasse = pg_fetch_object($res)) {

			$retorno[] = ["descricao" => utf8_encode($dadosClasse->classe), 
						  "valor"     => $dadosClasse->classe_pedido,
						  "mensagem"  => utf8_encode($dadosClasse->mensagem),
						  "prazo"     => $dadosClasse->prazo];

		}

	} else {

		$retorno["erro"] = true;

	}

	exit(json_encode($retorno));

}

if (isset($_POST["ajax_busca_condPag"])) {

	$marca = $_POST['marca'];

	$sql_padrao = " SELECT condicao, descricao
					FROM tbl_condicao 
					WHERE tbl_condicao.fabrica = $login_fabrica
					AND codigo_condicao IN ('VIS', 'PRZ')
					AND tbl_condicao.visivel IS TRUE 
					ORDER BY tbl_condicao.condicao ASC";

	$sql_posto_marca = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto AND parametros_adicionais NOTNULL";
	$res_posto_marca = pg_query($con, $sql_posto_marca);
	if (pg_num_rows($res_posto_marca) > 0) {
		$ppA = json_decode(pg_fetch_result($res_posto_marca, 0, 'parametros_adicionais'), true);

		if (isset($ppA["empresas"])) {
			$ppA = explode(";", $ppA['empresas']);

			$sql_emp_marca = "SELECT empresa FROM tbl_marca WHERE marca = $marca AND fabrica = $login_fabrica LIMIT 1";
			$res_emp_marca = pg_query($con, $sql_emp_marca);
			$emp = pg_fetch_result($res_emp_marca, 0, 'empresa');

			if (in_array($emp, $ppA)) {
				$sql1 = "SELECT  tbl_condicao.condicao,
						 		 tbl_condicao.descricao
						 FROM    tbl_condicao
						 WHERE tbl_condicao.visivel IS TRUE
						 AND tbl_condicao.fabrica     = $login_fabrica
						 --AND tbl_condicao.codigo_condicao <> 'PRZ'
						 ORDER BY tbl_condicao.condicao ASC ";
				$res = pg_query($con,$sql1);
			} else {
				$res = pg_query($con,$sql_padrao);
			}
		} else {
			$res = pg_query($con,$sql_padrao);
			
		}
	} else {
		$res = pg_query($con,$sql_padrao);
	}

	if (pg_num_rows($res) > 0) {
		while ($dadosClasse = pg_fetch_object($res)) {
			$retorno[] = ["descricao" => utf8_encode($dadosClasse->descricao), 
						  "valor"     => $dadosClasse->condicao
						];
		}
	} else {
		$retorno["erro"] = true;
	}
	exit(json_encode($retorno));
}

if(isset($_GET['linha_form'] ) && isset($_GET['produto_referencia'])) {
	if (empty($_GET['produto_referencia']) )
		exit;

	$referencia = $_GET['produto_referencia'];
	
	$sql = "  SELECT tbl_tabela_item.preco, tbl_peca.ipi
				FROM tbl_tabela_item
				JOIN tbl_tabela
			   USING (tabela)
				JOIN tbl_peca
			   USING (peca)
			   WHERE referencia         = '$referencia'
				 AND tbl_peca.fabrica   = $login_fabrica
				AND tbl_peca.ativo
				 AND tbl_tabela.fabrica = $login_fabrica ";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$preco = pg_fetch_result($res,0,preco);
		$ipi   = pg_fetch_result($res,0,ipi);
		$preco_ipi = $preco + (($preco * $ipi) / 100);
		echo $_GET['linha_form'] . '|' . $preco . '|' . $ipi . '|' . $preco_ipi;
	}else{
		echo $_GET['linha_form'] . '|0|0|0';
	}
	exit;
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>3){
		$sql = "SELECT 
				tbl_transportadora.cnpj, 
				tbl_transportadora.nome, 
				tbl_transportadora_fabrica.codigo_interno
			FROM tbl_transportadora
				JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
			WHERE 
				tbl_transportadora_fabrica.fabrica = $login_fabrica
			AND tbl_transportadora_fabrica.ativo IS TRUE ";
			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_transportadora_fabrica.codigo_interno = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_transportadora.nome) like UPPER('%$q%') ";
			}
		$sql .= " LIMIT 50;";
		//echo $sql;
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			for ($i=0; $i<pg_num_rows($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_interno));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}


if (strlen($_POST["pesquisaPeca"]) > 10){
	$referencia = $_POST["referencia"];
	$posicao = $_POST["posicao"];
	$marca_emp = $_POST["marca_emp"];
	/*
	** Retornos AJAX: 0 - Referencia Invalida; 1 - Mais de uma componente; 2 - Peça não existe;
	*/
	
	if (strlen($referencia)>0){
		$sql = "    SELECT 
						referencia,
						descricao,
						marca
					FROM 
						tbl_peca
					WHERE 
						referencia_pesquisa = '$referencia'
						AND fabrica = $login_fabrica
						AND (marca = $marca_emp OR marca IS NULL)
						AND ativo
					ORDER BY 
						referencia ASC LIMIT 1;";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
			$referencia     = trim(pg_fetch_result($res, 0, 'referencia'));
			$descricao      = trim(pg_fetch_result($res, 0, 'descricao'));
			$marca          = trim(pg_fetch_result($res, 0, 'marca'));

			$sql = "SELECT marca FROM tbl_depara JOIN tbl_peca ON tbl_peca.peca = tbl_depara.peca_para WHERE tbl_depara.de = '$referencia' AND tbl_depara.fabrica = $login_fabrica";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$marca = pg_fetch_result($res, 0, 'marca');
			}
	
			echo "$referencia|$descricao|$posicao|$marca";
		}elseif(pg_num_rows($res) > 1)
			echo 1;
		else
			echo 2;
	}else{
		echo 0;
	}
	exit;
}

if (strlen($_POST["verificaDePara"]) > 10){
	$referencia = $_POST["referencia"];
	$token      = $_POST['token'];

	if (strlen($referencia)>0){
		$sql = "
			SELECT
				tbl_depara.de AS de,
				tbl_depara.para AS para,
				tbl_peca.descricao AS descricao,
				tbl_peca.marca
			FROM 
				tbl_depara
				JOIN tbl_peca ON (tbl_peca.peca = tbl_depara.peca_para)
			WHERE tbl_depara.de = '$referencia'
			AND tbl_depara.fabrica  = $login_fabrica
			";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
			$de        = trim(pg_fetch_result($res, 0, 'de'));
			$para      = trim(pg_fetch_result($res, 0, 'para'));
			$descricao = trim(pg_fetch_result($res, 0, 'descricao'));
			$marca = pg_fetch_result($res, 0, 'marca');

			if (!empty($token)) {
				$up = "UPDATE tbl_token_pedido SET referencia = '$para' 
						WHERE referencia = '$de'
						AND token = '$token'
						AND fabrica = $login_fabrica";
				$qry = pg_query($con, $up);
			}

			echo "$de|$para|$descricao|$marca";
		}
		// echo 0;
	}else{
		echo 0;
	}
	exit;
}

if (strlen($_POST["verificaMultiplo"]) > 5){
	$referencia = $_POST["referencia"];
	$qtde       = $_POST["qtde"];
	$posicao    = $_POST["posicao"];
	$marca_emp  = $_POST["marca_emp"];

	if (strlen($referencia)>0){
		$sql = "
			SELECT
				multiplo
			FROM 
				tbl_peca
			WHERE 
				referencia = '$referencia'
				AND fabrica  = $login_fabrica
				AND (marca = $marca_emp OR marca IS NULL)
				LIMIT 1";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$multiplo = (empty(pg_fetch_result($res, 0, 'multiplo'))) ? 1 : trim(pg_fetch_result($res, 0, 'multiplo'));

			if($qtde % $multiplo != 0)
				echo "$multiplo|$posicao";
			else
				echo 1;
		}else
			echo 0;
	}else
		echo 0;
	exit;
}

if ($_POST["apagarPedido"] == 'apagarPedidoAJAX'){
	$pedido = $_POST["pedido"];
	$item   = $_POST["item"];

	if (strlen($pedido) > 0 AND strlen($pedido) > 0){
		$sql = "SELECT * FROM tbl_pedido WHERE pedido = $pedido AND posto = $login_posto AND fabrica = $login_fabrica";
		$qry = pg_query($con, $sql);

		if (pg_num_rows($qry) == 1) {
			$del = "DELETE FROM tbl_pedido_item WHERE pedido = $pedido AND pedido_item = $item";

			if(pg_query($con,$del))
				echo 1;
			else
				echo 0;
		} else {
			echo 0;
		}

	}
	exit;
}

if($_POST['geraToken']){
	$cnpj  = $_POST['cnpj'];
	$sql = "SELECT tbl_posto.nome,tbl_posto_fabrica.codigo_posto,tbl_posto_fabrica.contato_email 
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
					AND tbl_posto_fabrica.fabrica = $login_fabrica 
					WHERE cnpj = '$cnpj'";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$razao          = pg_fetch_result($res, 0, 'nome');
		$codigo_posto   = pg_fetch_result($res, 0, 'codigo_posto');
		$email          = pg_fetch_result($res, 0, 'email');

		$usuario = ($cookie_login['login_unico']) ? $email : $codigo_posto;
	}
	$token = md5(uniqid(rand(), true));
	$sql = "INSERT INTO tbl_token_pedido(
											fabrica,
											token,
											cnpj_cliente,
											razao_social,
											usuario
											) VALUES(
											$login_fabrica,
											'$token',
											'$cnpj',
											'$razao',
											$usuario)";
	$res = pg_query($con,$sql);
	echo (!pg_last_error($con)) ? "ok|$token" : pg_last_error($con);

	exit;
}

if($_POST['verificaToken']){
	$token  = $_POST['token'];
	$sql = "SELECT referencia, qtde FROM tbl_token_pedido WHERE token = '$token' and fabrica = $login_fabrica and referencia IS NOT NULL and qtde IS NOT NULL";
	$res = pg_query($con,$sql);

	$result = array('status' => 'false', 'result' => array());

	if (pg_num_rows($res) > 0) {
		$result['status'] = 'true';

		while ($fetch = pg_fetch_assoc($res)) {
			$result['result'][] = array(
										'referencia' => $fetch['referencia'],
										'qtde'       => $fetch['qtde'],
									);
		}
	}

	echo json_encode($result);

	exit;
}

function validaRegrajacto($condicao,$referencia,$posto,$linha) {
	$_GET['condicao'] = $condicao;
	$_GET['produto_referencia'] = $referencia;
	$_GET['posto'] = $posto;
	$_GET['linha_form'] = $linha;
	ob_start();
	include "jacto_valida_regras.php";
	ob_get_clean();
	$jacto_preco = number_format ($jacto_preco,2,".",".");
	return $jacto_preco;
}

session_start("pedido_jacto");
$dados_array = count($pedido_session);

if(!isset($_SESSION["pedido_session"])){
	$_SESSION['pedido_session'] = Array();
}

$pedido_session = $_SESSION['pedido_session'];
$login_bloqueio_pedido = $cookie_login['cook_bloqueio_pedido'];

$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_query($con,$sql);
if (pg_fetch_result($res,0,0) == 'f') {

	$title       = traduz('cadastro.de.pedidos.de.pecas', $con);
	$layout_menu = 'pedido';
	include "cabecalho.php";

	echo '<h4>' . traduz('cadastro.de.pedidos.faturados.bloqueado', $con) . '</h4>';

	include "rodape.php";
	exit;
}

#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;
$limit_pedidos = 2;

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";
$msg_debug = "";

if (!$_POST["qtde_item"]) {
	$qtde_item = 40;
}

//Seleciona os dados para existe na tela...
if(!$_GET[ 'delete']) {
	$sql = "
			SELECT tbl_pedido.pedido,
				   tbl_pedido.pedido_blackedecker,
				   tbl_pedido.condicao,
				   tbl_pedido.pedido_cliente,
				   tbl_pedido.tipo_pedido,
				   tbl_pedido.classe_pedido,
				   tbl_pedido.previsao_entrega,
				   tbl_condicao.descricao,
				   tbl_tipo_pedido.descricao                 AS tipo_pedido_descricao,
				   tbl_pedido.seu_pedido,
				   tbl_transportadora.nome                   AS transportadora_nome,
				   tbl_transportadora_fabrica.codigo_interno AS transportadora_codigo,
				   tbl_classe_pedido.marca
			  FROM tbl_pedido
			  LEFT JOIN tbl_condicao               USING (condicao)
			  JOIN tbl_tipo_pedido                 USING (tipo_pedido)
			  LEFT JOIN tbl_transportadora         ON    tbl_pedido.transportadora          = tbl_transportadora.transportadora
			  LEFT JOIN tbl_transportadora_fabrica ON    tbl_transportadora.transportadora  = tbl_transportadora_fabrica.transportadora
												   AND   tbl_transportadora_fabrica.fabrica = $login_fabrica
			  LEFT JOIN tbl_classe_pedido ON tbl_pedido.classe_pedido = tbl_classe_pedido.classe_pedido
			 WHERE tbl_pedido.exportado    IS NULL
			 --AND tbl_pedido.admin          IS NULL
			 AND tbl_pedido.posto          =  $login_posto
			 AND tbl_pedido.fabrica        =  $login_fabrica
			 AND tbl_pedido.finalizado     IS NULL
			 AND (tbl_pedido.status_pedido IS NULL OR tbl_pedido.status_pedido <> 14);";
	$res = pg_query ($con,$sql);
	
	if (pg_num_rows($res) > 0) {
		$cook_pedido           = trim(pg_fetch_result($res, 0, 'pedido'));
		$condicao              = trim(pg_fetch_result($res, 0, 'condicao'));
		$marca_empresa         = trim(pg_fetch_result($res, 0, 'marca'));
		$descricao_condicao    = trim(pg_fetch_result($res, 0, 'descricao'));
		$pedido_cliente        = trim(pg_fetch_result($res, 0, 'pedido_cliente'));
		$tipo_pedido           = trim(pg_fetch_result($res, 0, 'tipo_pedido'));     
		$tipo_pedido_descricao = trim(pg_fetch_result($res, 0, 'tipo_pedido_descricao'));
		$transportadora_nome   = trim(pg_fetch_result($res, 0, 'transportadora_nome'));
		$transportadora_codigo = trim(pg_fetch_result($res, 0, 'transportadora_codigo'));
		$classe_pedido_id      = trim(pg_fetch_result($res, 0, 'classe_pedido'));
		$classe_pedido_prazo   = trim(pg_fetch_result($res, 0, 'previsao_entrega'));
		if(strlen($classe_pedido_prazo) > 0){
			$classe_pedido_prazo = date('d/m/Y',strtotime($classe_pedido_prazo));   
		}else{
			$classe_pedido_prazo = "";
		}
	}
	
}
 
if ($btn_acao == "gravar"){

	if (strlen( $cook_pedido ) == 0 ){
		unset( $cook_pedido );
		setcookie ('cook_pedido');
		$cook_pedido = "";
	}
	
	$trans_nome        = $_POST['nome_transp'];
	$trans_codigo      = $_POST['codigo_transp'];
	$pedido            = $_POST['pedido'];
	$condicao          = $_POST['condicao'];
	$tipo_pedido       = $_POST['tipo_pedido']; 
	$pedido_cliente    = $_POST['pedido_cliente'];
	$transportadora    = $_POST['transportadora'];
	$linha             = $_POST['linha'];
	$observacao_pedido = $_POST['observacao_pedido'];
	$qtde_item         = $_POST['qtde_item'];
	$retirada_local    = $_POST['retirada_local'];
	$posto_filial      = $_POST['posto_filial'];
	$marca_empresa     = $_POST['marca'];

	if (empty($marca_empresa)) {
		$msg_erro = traduz("Selecione a empresa do grupo");
	}

	#classe pedido
	$classe_pedido_prazo    = $_POST['data_esperada'];
	$classe_pedido_codigo   = trim($_POST['classe_pedido']);
	$classe_pedido_mensagem = $_POST['mensagem_classe'];

	for( $i = 0; $i < 30 ; $i++ ){
		$peca_descricao[$i] = trim($_POST["peca_referencia" ]);
		$peca_descricao[$i];
	}
	
	if(empty($peca_descricao)){
	//  $msg_erro = "Não foi digitada a descrição ou referência do produto";
	}

	$aux_condicao          = (strlen($condicao)          == 0) ? "null" : $condicao ;
	$aux_pedido_cliente    = (strlen($pedido_cliente)    == 0) ? "null" : "'$pedido_cliente'";
	$aux_transportadora    = (strlen($transportadora)    == 0) ? "null" : $transportadora ;
	$aux_observacao_pedido = (strlen($observacao_pedido) == 0) ? "null" : "'$observacao_pedido'" ;

	//Valida Tipo de Pedido
	if (($tipo_pedido == 0 OR strlen($tipo_pedido) == 0) and strlen($cook_pedido)==0 and strlen($msg_erro)==0) {
		$msg_erro = traduz('selecione.um.tipo.de.pedido', $con);
	}
	
	//Valida Condicao de Pagamento
	if ((strlen($condicao) == 0 or $condicao == 0) and strlen($msg_erro)==0) {
		$msg_erro = traduz('selecione.uma.condicao.de.pagamento', $con);
	}

	if((strlen($classe_pedido_codigo) == 0 and strlen($msg_erro) == 0) || $classe_pedido_codigo == 'Selecione a empresa'){
		$msg_erro = "Informe a classe em que o pedido se adequa";
	}else{
		$sql = "SELECT * FROM tbl_classe_pedido where trim(codigo_classe) = trim('$classe_pedido_codigo') and fabrica = $login_fabrica AND marca = $marca_empresa";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$prazo = pg_result($res,0,prazo);

			$classe_pedido = pg_result($res,0,classe_pedido);

			if($prazo > 0){
				
				if($classe_pedido_prazo == ""){
					$msg_erro = "Defina uma data de espera";
				}else{
					//echo $prazo." - ";
					$timestamp_prazo_classe =  strtotime(date('Y/m/d').'+'.$prazo.' days');
					$data_aux = explode('/', $classe_pedido_prazo);                                     
					$timestamp_prazo_posto = strtotime($data_aux[2].'/'.$data_aux[1].'/'.$data_aux[0]);
					

					if($timestamp_prazo_posto < $timestamp_prazo_classe){
						$msg_erro = "A data definida não pode ser menor que ".$prazo." dias";   
					}

				}
			}
		}
	}


	// if(strlen($retirada_local) == 1){
	//  $aux_transportadora = "5412"; //HD 680542 Interação 12 item 1
	// }else{
	//  if(strlen($trans_codigo) == 0 and strlen($msg_erro)==0){
	//      $msg_erro = traduz('transportadora.invalida', $con);
	//  }

	//  if(strlen($trans_codigo) > 0 and strlen($msg_erro)==0){
	//      $sql = "SELECT transportadora FROM tbl_transportadora_fabrica WHERE codigo_interno = '$trans_codigo' AND fabrica = $login_fabrica AND ativo IS TRUE";
	//      $res = pg_query($con,$sql);
	//      if(pg_num_rows($res)==0){
	//          $msg_erro = traduz('transportadora.%.nao.encontrada', $con, $cook_idioma, (array) $trans_codigo);
	//      }else{
	//          $aux_transportadora = pg_fetch_result($res,0,transportadora);
	//      }
	//  }
	// }
	//Fim valida transportadora

	if (strlen($tipo_pedido) <> 0) {
		$aux_tipo_pedido = "'". $tipo_pedido ."'";
	}else{
		$sql = "SELECT  
					tipo_pedido
				FROM
					tbl_tipo_pedido
				WHERE   
					descricao IN ('Faturado','Venda')
					AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$aux_tipo_pedido = "'". pg_fetch_result($res,0,tipo_pedido) ."'";
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
	}else{
		$aux_linha = $linha ;
	}

	#----------- PEDIDO digitado pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";

	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_query($con,$sql);
			if (pg_num_rows($res) <> 1) {
				$msg_erro = traduz('posto.%.nao.cadastrado', $con, $cook_idioma, $codigo_posto);
				$posto = $login_posto;
			}else{
				$posto = pg_fetch_result($res,0,0);
				if ($posto <> $login_posto) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_query($con,$sql);
					if (pg_num_rows($res) <> 1) {
						$msg_erro = traduz('posto.%.nao.pertence.a.sua.regiao', $con, $cook_idioma, $codigo_posto);
						$posto = $login_posto;
					}else{
						$posto = pg_fetch_result($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}

	if(!empty($posto_filial)){
		$sql = "SELECT posto FROM tbl_posto_fabrica
				INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
					AND tbl_tipo_posto.fabrica = $login_fabrica
			WHERE tbl_posto_fabrica.posto = $posto_filial AND tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_tipo_posto.codigo = 'FILIAL'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){
			$msg_erro = "O posto selecionado não é mais uma Filial";
		}
	}

	if(strlen($msg_erro)==0){
		$res = pg_query($con,"BEGIN TRANSACTION");

		if ($aux_tipo_pedido == "'203'") {
			if($classe_pedido_prazo != ""){
				$validade_campo = ',previsao_entrega';
				$validade_valor = ",'".$classe_pedido_prazo."'";
				$update_validade = ",previsao_entrega = '$classe_pedido_prazo'";
			}else{
				$validade_campo = ',previsao_entrega';
				$validade_valor = ",'".date('Y-m-d')."'";
				$update_validade = ",previsao_entrega = '".date('Y-m-d')."'";
			}
			
			/*$validade_campo = ", previsao_entrega ";
			$validade_valor = ", (SELECT current_date + interval '30 days')";
			$update_validade = ", previsao_entrega = (SELECT current_date + interval '30 days')";*/
		} else {            
			if($classe_pedido_prazo != ""){
				$validade_campo = ',previsao_entrega';
				$validade_valor = ",'".$classe_pedido_prazo."'";
				$update_validade = ",previsao_entrega = '$classe_pedido_prazo'";
			}else{
				$validade_campo = ',previsao_entrega';
				$validade_valor = ",'".date('Y-m-d')."'";
				$update_validade = ",previsao_entrega = '".date('Y-m-d')."'";
			}
			
		}

		if(!empty($posto_filial)){
			if (strlen ($pedido) == 0 and strlen($cook_pedido)==0) {
				$sql_campo = ", filial_posto";
				$sql_valor = ", ".$posto_filial;
			}else{
				$update_validade = ", filial_posto = ".$posto_filial;
			}
		}else{
			$posto_filial = "null";
			$update_validade = ", filial_posto = ".$posto_filial;
		}

		if(strlen($cook_admin) > 0){
			$campo_admin = ", admin ";
			$value_admin = ", $cook_admin ";
		}

		// verificando se aquela peça já está salva


		if (strlen ($pedido) == 0 and strlen($cook_pedido)==0) {
			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						fabrica        ,
						condicao       ,
						pedido_cliente ,                        
						linha          ,
						tipo_pedido    ,
						digitacao_distribuidor,
						classe_pedido,
						obs
						$sql_campo
						$validade_campo
						$campo_admin 
					) VALUES (
						$posto              ,
						$login_fabrica      ,
						$aux_condicao       ,
						$aux_pedido_cliente ,                       
						$aux_linha          ,
						$aux_tipo_pedido    ,
						$digitacao_distribuidor,
						$classe_pedido,
						$aux_observacao_pedido
						$sql_valor
						$validade_valor
						$value_admin 
					)";

			
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
		
			if (strlen($msg_erro) == 0){
				$res = pg_query($con,"SELECT CURRVAL ('seq_pedido')");
				$cook_pedido = pg_fetch_result ($res,0,0);
			}
		}else{
			$sql = "UPDATE tbl_pedido SET
						condicao       = $aux_condicao       ,
						pedido_cliente = $aux_pedido_cliente ,                      
						linha          = $aux_linha          ,
						classe_pedido  = $classe_pedido
						$update_validade
					WHERE pedido  = $cook_pedido
					AND   posto   = $login_posto
					AND   fabrica = $login_fabrica";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		
		$msg_erro_peca = "";
		$erro_de_para = "";
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$erro_peca = "";

			$pedido_item     = trim($_POST['pedido_item_'.$i]);
			$peca_referencia = trim($_POST['peca_referencia_'.$i]);
			$peca_descricao  = trim($_POST['peca_descricao_'.$i]);
			$qtde            = (int)trim($_POST['qtde_'.$i]);
			$preco           = trim($_POST['preco_'. $i]);
			$marca_peca      = trim($_POST['marca_'. $i]);

			if ($marca_empresa != $marca_peca && !empty($peca_referencia)) {
				$pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"1");
				$erro_peca = "Empresa da peça $peca_referencia diferente da selecionada no formulário<br />";
			}

			if ($peca_descricao == 'Não encontrado' or $peca_descricao_ == traduz('nao.encontrado', $con)) {
				$peca_referencia = '';
			}
			

			if (strlen ($peca_referencia) > 0 AND (strlen($preco)==0 or $preco == '0,00')){
				$pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"1");
				$erro_peca = "Peça $peca_referencia sem preço <br />";
			}

			if((is_int($qtde) == false OR $qtde < 1) AND strlen($erro_peca) == 0 and strlen($peca_referencia) > 0){
				$pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"1");
				$erro_peca = "Peça $peca_referencia não colocada qtde";
			}

			if (strlen($peca_referencia) > 0){
				$sql = "
					SELECT
						multiplo
					FROM 
						tbl_peca
					WHERE 
						referencia = '$peca_referencia'
						AND fabrica  = $login_fabrica
						AND (marca = $marca_empresa OR marca IS NULL)
						and ativo;";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					$multiplo = (empty(pg_fetch_result($res, 0, 'multiplo'))) ? 1 : pg_fetch_result($res, 0, 'multiplo');

					if($qtde % $multiplo != 0){
						$pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"2");
						//$erro_peca = "Peça $peca_referencia não encontrada ou qtde não permitida";

					}
				}
			}

			if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0 || $_GET[ 'delete' ] ){
			

				$sql = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $pedido_item ";
				$res = pg_query($con,$sql);

				$sql = "SELECT pedido from tbl_pedido_item where pedido = $cook_pedido";                
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)==0) {
					$sql = "DELETE  FROM    tbl_pedido
						WHERE   pedido = $cook_pedido";
						$res = pg_query($con,$sql);
				}

				setcookie ($cook_pedido, "", time() - 3600);
				unset($cook_pedido);    
				header( "Location : $PHP_SELF" );
			}

			if (strlen ($peca_referencia) > 0 AND strlen($erro_peca) == 0) {
				$peca_referencia = trim (strtoupper ($peca_referencia));
				$peca_referencia = str_replace ("-","",$peca_referencia);
				$peca_referencia = str_replace (".","",$peca_referencia);
				$peca_referencia = str_replace ("/","",$peca_referencia);
				$peca_referencia = str_replace (" ","",$peca_referencia);
				
				$sql = "SELECT  tbl_peca.peca   ,
								tbl_peca.origem ,
								tbl_peca.promocao_site,
								tbl_peca.qtde_disponivel_site ,
								tbl_peca.qtde_max_site,
								tbl_peca.multiplo_site
						FROM    tbl_peca
						WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
						AND     tbl_peca.fabrica             = $login_fabrica
						AND     (tbl_peca.marca = $marca_empresa OR tbl_peca.marca IS NULL)
						AND ativo";
				$res = pg_query($con,$sql);


				if (pg_num_rows($res) == 0) {
					$peca = 0;
					$pedido_session[] = Array("referencia"=>"$peca_referencia","qtd"=>"$qtde","erro"=>"1");
					$erro_peca = "ERRO";
					//exit;
					$msg_erro = "Peça $peca_referencia não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca          = pg_fetch_result($res, 0, 'peca');
					$promocao_site = pg_fetch_result($res, 0, 'promocao_site');
					$qtde_disp     = pg_fetch_result($res, 0, 'qtde_disponivel_site');
					$qtde_max      = pg_fetch_result($res, 0, 'qtde_max_site');
					$qtde_multi    = pg_fetch_result($res, 0, 'multiplo_site');                 
					$origemi       = trim(pg_fetch_result($res, 0, 'origem'));
				}
				
				
				if (strlen($preco)== 0 or $preco == '0,00'){
					$preco = "null";
				}else{
					$preco = str_replace (".","",$preco);
					$preco = str_replace (",",".",$preco);
				}

				/** get pedido_item */

				$q = "SELECT peca, pedido_item 
					  FROM tbl_pedido_item 
					  WHERE peca = $peca 
					  AND pedido = $cook_pedido";

				$res_q = pg_query($con, $q);

				$array_pedidos = [];

				$array_pecas = [];

				for ($cont = 0; $cont < pg_fetch_row($res_q); $cont++) { 
					
					$p = pg_fetch_result($res_q, $cont, peca);
					
					$array_pedidos[$p] = pg_fetch_result($res_q, $cont, pedido_item);

					$array_pecas[] = $p;
				}

				if (in_array($peca, $array_pecas)) { 

					$pedido_item = $array_pedidos[$peca]; 
				}	

				// -----------------------

				if (strlen($msg_erro) == 0 AND strlen($peca) > 0 and strlen($erro_peca) == 0) {

					if ($peca <> 0 AND $preco != 'null'){
						if (strlen($pedido_item) == 0){
		
							 $sql = "INSERT INTO tbl_pedido_item (
										pedido ,
										peca   ,
										qtde   ,
										preco,
										estoque
									) VALUES (
										$cook_pedido ,
										$peca   ,
										$qtde   ,
										$preco,
										NULL
									)";
						}else{
							
							/** 
							update original
								$sql = "UPDATE tbl_pedido_item SET
											peca = $peca,
											qtde = $qtde
										WHERE pedido_item = $pedido_item";
							*/
										
							$get = "SELECT qtde
									FROM   tbl_pedido_item  
									WHERE  pedido_item = $pedido_item"; 

							$result = pg_query($con, $get);

							$qtde_salva  = pg_fetch_result($result, 0, qtde);

							$qtde_final  = $qtde_salva  + $qtde;

							$sql = "UPDATE tbl_pedido_item 
									SET  qtde = $qtde_final
									WHERE pedido_item = $pedido_item";

							$res = pg_query($sql);
							
						}

					}else{
						$erro_peca = traduz('a.peca.%.nao.foi.cadastrada', $con, $cook_idioma, $peca_referencia); 
					}

					$res = @pg_query($con,$sql);
					$msg_erro = pg_last_error($con);

					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0 AND strlen($erro_peca) == 0) {
						$res         = pg_query($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_fetch_result($res,0,0);
						$msg_erro = pg_last_error($con);
					}

					if (strlen($msg_erro) == 0 AND strlen($erro_peca) == 0) {
						$sql = "SELECT fn_valida_pedido_item($cook_pedido,$peca,$login_fabrica)";
						$res = @pg_query($con,$sql);
						$msg_erro = pg_last_error($con);
					}

					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}

			}
			$msg_erro_peca .= $erro_peca;
		} //fim for
		$_SESSION['pedido_session'] = $pedido_session;
	}

	if(strlen($msg_erro_peca) > 0 ) $msg_erro = $msg_erro_peca;


	if (strlen ($msg_erro) == 0) {  
		$res = pg_query($con,"COMMIT TRANSACTION");
		echo "<script type='text/javascript'>";
			echo "window.location= '$PHP_SELF';";
		echo "</script>";
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

if ( $_GET[ 'delete' ] ){
	$pedido = $_GET[ 'pedido' ];
	$pedido_item = $_GET[ 'delete' ];


	$sql = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $pedido_item ";
	$res = pg_query($con,$sql);
	
	$sql = "SELECT pedido from tbl_pedido_item where pedido = $pedido";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res)==0) {
		 $sql = "UPDATE tbl_pedido set fabrica = 0 WHERE pedido = $pedido";
		$res = pg_query($con,$sql);
	}
	
	setcookie ($cook_pedido, "", time() - 3600);
	unset($cook_pedido);            
	echo "<script>window.location.href='$PHP_SELF'</script>";
}

$btn_acao = $_GET['btn_acao'];

if (strlen($btn_acao=='Finalizar')) {
	
	if (strlen ($cook_pedido) > 0) {

		$pedido = $cook_pedido;

		// $sql = "SELECT a.oid ,
		//      a.* ,
		//      referencia,
		//      descricao,
		//      tbl_peca.ipi AS ipi_peca,
		//      round((a.preco+(a.preco*tbl_peca.ipi/100))::numeric,2) as preco_com_ipi,
		//      round((a.preco+(a.preco*tbl_peca.ipi/100))::numeric,2) * a.qtde as preco_com_ipi
		//      FROM tbl_peca
		//      JOIN (
		//      SELECT tbl_pedido_item.oid,
		//          tbl_pedido_item.pedido_item,
		//          tbl_pedido_item.preco,
		//          tbl_pedido_item.qtde,
		//          tbl_pedido.classe_pedido,
		//          tbl_pedido_item.peca
		//      FROM tbl_pedido_item
		//      JOIN tbl_pedido USING(pedido)
		//      WHERE pedido = $pedido
		//      AND fabrica = $login_fabrica
		//      )
		//      a ON tbl_peca.peca = a.peca
		//      ORDER BY a.pedido_item";        

		$sql = "SELECT 
				sum(round((a.preco+(a.preco*tbl_peca.ipi/100))::numeric,2) * a.qtde) as preco_com_ipi           
				FROM tbl_peca
				JOIN (
				SELECT
					tbl_pedido_item.pedido_item,
					tbl_pedido_item.preco,
					tbl_pedido_item.qtde,
					tbl_pedido.classe_pedido,
					tbl_pedido_item.peca
				FROM tbl_pedido_item
				JOIN tbl_pedido USING(pedido)
				WHERE pedido = $pedido
				AND fabrica = $login_fabrica
				)
				a ON tbl_peca.peca = a.peca             
				";
		
		$res = pg_query($con,$sql);
		$valor_total_pedido = pg_result($res,0,preco_com_ipi);      
		
		$sql = "SELECT classe_pedido from tbl_pedido where pedido = $pedido";
		$res = pg_query($con,$sql);
		$classe_pedido = pg_result($res,0,classe_pedido);       

		if($classe_pedido != ""){
			$sql = "SELECT valor_minimo,
						   tbl_marca.nome as nome_empresa
					FROM tbl_classe_pedido
					JOIN tbl_marca USING(marca)
					WHERE classe_pedido = $classe_pedido
					AND tbl_marca.fabrica = $login_fabrica";

			// $sql = "SELECT  cp.valor_minimo, 
			// 				c.limite_minimo, 
			// 				c.parcelas, 
			// 				m.nome AS nome_empresa 
			// 		from tbl_classe_pedido cp 
			// 		JOIN tbl_marca m on cp.marca = m.marca 
			// 		LEFT JOIN tbl_empresa e on m.empresa = e.empresa 
			// 		LEFT JOIN tbl_condicao_empresa ce on e.empresa = ce.empresa 
			// 		LEFT JOIN tbl_condicao c on ce.condicao = c.condicao 
			// 		WHERE cp.classe_pedido = $classe_pedido 
			// 		AND c.fabrica = $login_fabrica";
			 
			$resClassePedido = pg_query($con,$sql);
			if(pg_num_rows($resClassePedido) > 0){              
				$valor_minimo  = pg_fetch_result($resClassePedido,0,'valor_minimo');
				//$limite_minimo = pg_fetch_result($resClassePedido,0,'limite_minimo');
				//$parcelas      = pg_fetch_result($resClassePedido,0,'parcelas');
				$nome_empresa  = pg_fetch_result($resClassePedido,0,'nome_empresa');
			}else{
				$valor_minimo = "";
			}           

			if($valor_minimo > $valor_total_pedido){

				$msg_erro = "O Valor do pedido deve ser maior que ".number_format($valor_minimo,2,',','');

			} else {
				$sql_minimo = "SELECT parcelas, limite_minimo FROM tbl_condicao where condicao = $condicao AND fabrica = $login_fabrica";
				$res_minimo = pg_query($con, $sql_minimo);
				if (pg_num_rows($res_minimo) > 0) {
					$limite_minimo = pg_fetch_result($res_minimo,0,'limite_minimo');
					$parcelas      = pg_fetch_result($res_minimo,0,'parcelas');
					
					if (!empty($limite_minimo) && !empty($parcelas)) {
						if ($limite_minimo > ($valor_total_pedido/$parcelas)) {
							$msg_erro = "O Valor da parcela deve ser maior que ".number_format($limite_minimo,2,',','');
						}
					}
				}
			} 
		}

		if(strlen($msg_erro) == 0){
			$res = pg_query($con,"BEGIN TRANSACTION");
			$sql = "SELECT fn_pedido_finaliza($cook_pedido,$login_fabrica)";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_last_error($con);

			if (strlen ($msg_erro) == 0) {
				$res = pg_query($con,"COMMIT TRANSACTION");
			header ("Location: pedido_finalizado.php?pedido=$cook_pedido&loc=1");
			}   
		}
		
	}
}
#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if (strlen ($pedido) > 0 || !empty($cook_pedido)) {

	$pedido_consulta = (!empty($pedido)) ? $pedido : $cook_pedido;

	$sql = "SELECT  DISTINCT ON (tbl_pedido.pedido)
					TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY')    AS data                 ,
					tbl_pedido.tipo_frete                                             ,
					tbl_pedido.transportadora                                         ,
					tbl_transportadora.cnpj                   AS transportadora_cnpj  ,
					tbl_transportadora.nome                   AS transportadora_nome  ,
					tbl_transportadora_fabrica.codigo_interno AS transportadora_codigo,
					tbl_pedido.pedido_cliente                                         ,
					tbl_pedido.tipo_pedido                                            ,
					tbl_pedido.produto                                                ,
					tbl_produto.referencia                    AS produto_referencia   ,
					tbl_produto.descricao                     AS produto_descricao    ,
					tbl_pedido.linha                                                  ,
					tbl_pedido.condicao                                               ,
					tbl_pedido.obs                                                    ,
					tbl_pedido.exportado                                              ,
					tbl_pedido.total_original                                         ,
					tbl_pedido.filial_posto                                           ,
					tbl_pedido.permite_alteracao                                      ,
					tbl_classe_pedido.marca                                           ,
					tbl_classe_pedido.classe_pedido
			FROM    tbl_pedido
		  LEFT JOIN tbl_transportadora USING (transportadora)
		  LEFT JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = $login_fabrica
		  LEFT JOIN tbl_produto        USING (produto)
		  LEFT JOIN tbl_classe_pedido ON tbl_pedido.classe_pedido = tbl_classe_pedido.classe_pedido
			WHERE   tbl_pedido.pedido   = $pedido_consulta
			AND     tbl_pedido.posto    = $login_posto
			AND     tbl_pedido.fabrica  = $login_fabrica ";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$data                  = trim(pg_fetch_result($res, 0, 'data'));
		$transportadora        = trim(pg_fetch_result($res, 0, 'transportadora'));
		$transportadora_cnpj   = trim(pg_fetch_result($res, 0, 'transportadora_cnpj'));
		$transportadora_codigo = trim(pg_fetch_result($res, 0, 'transportadora_codigo'));
		$transportadora_nome   = trim(pg_fetch_result($res, 0, 'transportadora_nome'));
		$pedido_cliente        = trim(pg_fetch_result($res, 0, 'pedido_cliente'));
		$tipo_pedido           = trim(pg_fetch_result($res, 0, 'tipo_pedido'));
		$produto               = trim(pg_fetch_result($res, 0, 'produto'));
		$produto_referencia    = trim(pg_fetch_result($res, 0, 'produto_referencia'));
		$produto_descricao     = trim(pg_fetch_result($res, 0, 'produto_descricao'));
		$linha                 = trim(pg_fetch_result($res, 0, 'linha'));
		$condicao              = trim(pg_fetch_result($res, 0, 'condicao'));
		$exportado             = trim(pg_fetch_result($res, 0, 'exportado'));
		$total_original        = trim(pg_fetch_result($res, 0, 'total_original'));
		$permite_alteracao     = trim(pg_fetch_result($res, 0, 'permite_alteracao'));
		$posto_filial          = trim(pg_fetch_result($res, 0, 'posto_filial'));
		$marca_empresa         = trim(pg_fetch_result($res, 0, 'marca'));
		$observacao_pedido     = @pg_fetch_result($res, 0, 'obs');
		$classe_pedido_codigo  = trim(pg_fetch_result($res, 0, 'classe_pedido'));
	}
}


#---------------- Recarrega Form em caso de erro -------------

if (strlen ($msg_erro) > 0) {
	$pedido                = $_POST['pedido'];  
	if($_GET['btn_acao'] != "Finalizar"){
		$pedido_cliente        = $_POST['pedido_cliente'];  
		$tipo_pedido           = $_POST['tipo_pedido'];
		$condicao              = $_POST['condicao'];    
	}   
	$transportadora_codigo = $_POST['codigo_transp'];
	$transportadora_nome   = $_POST['nome_transp'];
	$linha                 = $_POST['linha'];
	$codigo_posto          = $_POST['codigo_posto'];
	#classe pedido
	$classe_pedido_prazo = $_POST['data_esperada'];

	if (!empty($_POST['classe_pedido'])) {
		$classe_pedido_codigo = $_POST['classe_pedido'];
	}

	$classe_pedido_mensagem = $_POST['mensagem_classe'];
	



	$sql = "SELECT classe_pedido FROM tbl_classe_pedido WHERE fabrica = 87 AND codigo_classe = '$classe_pedido_codigo'";    
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$classe_pedido_id = pg_result($res,0,classe_pedido);
	}
}

$title       = traduz('cadastro.de.pedidos.de.pecas', $con);
$layout_menu = 'pedido';

if(!empty($cook_pedido)) {
	
	$sql = "SELECT pedido
			FROM tbl_pedido
			WHERE pedido = $cook_pedido
			AND   fabrica = $login_fabrica";
	//echo $sql;
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) == 0){
		unset( $cook_pedido );
		setcookie ('cook_pedido');
		$cook_pedido = "";
	}
}

include "cabecalho.php";

?>
<style type="text/css">
	.menu_top { 
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; 
		font-size: 10px; 
		font-weight: bold; 
		border: 0px solid;
		color:'#ffffff';
		background-color: '#596D9B';
	}
	.table_line1 { 
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; 
		font-size: 11px;
		font-weight: normal;
		border: 0px solid;
	}

	#footer{
		clear: both;
	}

</style>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="screen">

<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
<?php 
	include "javascript_calendario.php"; 
?>
<script type="text/javascript" src="js/php.default.min.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.js'></script>
<script type='text/javascript' src='js/jquery.dimensions.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
<script type="text/javascript" src="plugins/jquery.form.js"></script>

<script type="text/javascript">

	var LF = chr(10);


	var traducao = { 
		acertar_quantidade:           "<?=traduz('acertar.quantidade', $con)?>",
		aguarde_submissao:            "<?=traduz('aguarde.submissao', $con)?>",
		apos_inserir_clique_finalizar:"<?=traduz('apos.inserir.todos.os.itens.desejados.clique.em.finalizar', $con)?>",
		aviso_condicao_de_pagamento:  "<?=traduz('atencao.a.condicao.de.pagamento.pode.influenciar.no.preco.das.pecas', $con)?>",
		aviso_perde_dados_digitados:  "<?=traduz('se.mudar.a.condicao.os.dados.digitados.serao.perdidos', $con)?>",
		click_em_acertar_qtde:        "<?=traduz('clique.em.acertar.quantidade.para.corrigir', $con)?>",
		codigo_de_substituido:        "<?=str_replace(chr(10), ' ', traduz(array('o.codigo','%','foi.substituido.pelo.codigo.acima'), $con, null, '%'))?>",
		confirma_condicao:            "<?=traduz('tem.certeza.que.deseja.a.condicao.%', $con)?>",
		erro_ao_excluir_peca:         "<?=traduz(array('erro.ao.excluir.a.peca', 'sep'=> ', ', 'tente.novamente'), $con)?>",
		informar_parte_para_pesquisa: "<?=traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con)?>",
		msg_cond_upload:              "<?=traduz(array('selecione.uma.condicao.de.pagamento','para.fazer.o.upload'), $con)?>",
		peca_deve_ser_multiplo:       "<?=traduz('a.peca.%.precisa.ser.multiplo.de.%', $con)?>",
		pecas_nao_encontradas:        "<?=traduz('as.seguintes.pecas.nao.foram.encontradas', $con)?>",
		tela_de_pesquisa:             "<?=traduz('tela.de.pesquisa', $con)?>",
		verificar_codigo_pecas:       "<?=traduz('favor.verificar.se.o.codigo.esta.correto', $con)?>"
	}

	
	$('input[name*="qtde_"]').numeric();
	
	$(function(){
		
		$('#data_esperada').datePicker({startDate:'01/01/2000'});

		 $("#classe_pedido").change(function(){
			$('#classe_pedido option:selected').each(function(){
				mensagem = this.getAttribute('mensagem');                  
				prazo = this.getAttribute('prazo');
				console.log(prazo);
				codico_classe = this.getAttribute('value');
			});

			$("#mensagem_classe").val(mensagem);
			if(prazo == true){              
				$("#data_esperada_linha").show();
			}else{
				$("#data_esperada_linha").hide();
				$("#data_esperada").val('');
			}
		 });
	});


	
	Shadowbox.init();
	
	//Ederson Sandre
	function buscaPeca(valor,posicao,tipo){
		var peca = valor.value ;
		//alert(peca);

		if (jQuery.trim(peca).length > 1){
			Shadowbox.open({
				content: "pesquisa_peca_jacto.php?"+tipo+"="+peca+"&posicao="+posicao+"&tipo="+tipo,
				player:  "iframe",
				title:   traducao.tela_de_pesquisa,
				width:   800,
				height:  500
			});
		}else
			alert(traducao.informar_parte_para_pesquisa);
	}

	function abrirPop(pagina,largura,altura) {
		w = screen.width;
		h = screen.height;

		meio_w = w/2;
		meio_h = h/2;

		altura2 = altura/2;
		largura2 = largura/2;
		meio1 = meio_h-altura2;
		meio2 = meio_w-largura2;

		// window.open(pagina,'pedido','height=' + altura + ', width=' + largura + ', top='+meio1+', left='+meio2+',scrollbars=yes, resizable=no, toolbar=no'); 
		window.open(pagina,'pedido','height=' + h + ', width=' + w + ',scrollbars=yes, resizable=no, toolbar=no'); 
	}


	function exibeTipo(){
		f = document.frm_pedido;
		if(f.linha.value == 3){
			f.tipo_pedido.disabled = false;
		}else{
			f.tipo_pedido.selectedIndex = 0;
			f.tipo_pedido.disabled = true;
		}
	}

	function confirmaCondicao(condicao) {
		var valida = $('#validacondicao');
		var condicaoanterior = $('#condicaoanterior');
		var msg = traducao.aviso_condicao_de_pagamento + LF;
			msg += traducao.confirma_condicao.replace('%', condicao) + LF;
			msg += traducao.aviso_perde_dados_digitados;

		if(confirm(msg)==true) {
			if (valida.val()=='sim') {
				var qtde = $('#qtde_item').val();
			}
			valida.val('sim');
			condicaoanterior.val($('#condicao').val());
		} else {
			if (valida.val()=='sim') {
				valida.val('nao');
				var seleciona = "option[value="+"'"+condicaoanterior.val()+"']";
				$("#condicao "+seleciona).attr('selected', 'selected');
			} else {
				valida.val('nao');
			}
		}
	}

	function confirmaClassePedido(op) {

		var mensagem = $(op).attr('mensagem');
		var txt = "Deseja aterar a classe de pedido para a seguinte condição:\n";
		txt += mensagem;

		if(confirm(txt)){

			$("#classe_anterior").val($(op).val())          
		}else{
			$("#classe_pedido option[value='"+$("#classe_anterior").val()+"']").attr('selected','selected');            
		}
		

		/*var valida = $('#validacondicao');
		var condicaoanterior = $('#condicaoanterior');
		var msg = traducao.aviso_condicao_de_pagamento + LF;
			msg += traducao.confirma_condicao.replace('%', condicao) + LF;
			msg += traducao.aviso_perde_dados_digitados;

		if(confirm(msg)==true) {
			if (valida.val()=='sim') {
				var qtde = $('#qtde_item').val();
			}
			valida.val('sim');
			condicaoanterior.val($('#condicao').val());
		} else {
			if (valida.val()=='sim') {
				valida.val('nao');
				var seleciona = "option[value="+"'"+condicaoanterior.val()+"']";
				$("#condicao "+seleciona).attr('selected', 'selected');
			} else {
				valida.val('nao');
			}
		}*/
	}

	function adicionarLinha(linha) {

		var total_input = $('#tabela_itens > tbody > tr[name=peca] > td > input[name*="peca_referencia_"]').size();
		linha = parseFloat(total_input);

		if($.trim($('#tabela_itens > tbody > tr[name=peca] > td > input[name^=peca_referencia_]').last("input[name^=peca_referencia_").val().length) > 0) {
			$("#tabela_itens > tbody").append("<tr name='peca' rel='"+linha+"'>"+$("tr[id=modelo]").clone().html().replace(/__modelo__/g, linha)+"</tr>");
			$("#tabela_itens > tbody").append("<tr>"+$("tr[id=modelo_mudou]").clone().html().replace(/__modelo__/g, linha)+"</tr>");

			$('#qtde_item').val(linha);
			return;

			/*se ainda na criou a linha de item */
			if (!document.getElementById('peca_referencia_'+linha)) {
				var tbl = document.getElementById('tabela_itens');

				/*Criar TR - Linha*/
				var nova_linha = document.createElement('tr');
				nova_linha.setAttribute('rel', linha);

				
				/********************* COLUNA APAGAR ****************************/
				var celula = criaCelula('');
				celula.style.cssText = 'width: 10px;';

				var linha_nova = $(celula).append("<a href='javascript:void(0);' onclick='apagaLinha("+linha+");'><img src='imagens/icone_deletar.png' alt='Excluir Item' width='10' style='padding: 0; margin: 0;' /></a>");
				celula.linha_nova;
				nova_linha.appendChild(celula);

		
				/********************* COLUNA 1 ****************************/
				/*Cria TD */
				var celula = criaCelula('');
				celula.style.cssText = 'text-align: left;' ;

				var linha_nova = $(celula).append("<input style='width: 80px; text-align: left;' type='text' class='frm' name='peca_referencia_"+linha+"' id='peca_referencia_"+linha+"' value='' onblur='pesquisaPeca(this,"+linha+");' onkeyup='pulaCampo(\"referencia\","+linha+",event); ' tabindex='"+(linha+1)+"' rel='"+linha+"' /> <img width='16px' border='0' align='absmiddle' style='cursor: pointer' onclick='buscaPeca(document.frm_pedido.peca_referencia_"+linha+","+linha+",\"referencia\");' src='imagens/lupa.png' />");

				celula.linha_nova;
				nova_linha.appendChild(celula);

				/*Cria TD */
				var celula = criaCelula('');
				celula.style.cssText = 'text-align: left;' ;

				var linha_nova = $(celula).append("<input style='width: 220px; text-align: left;' type='text' class='frm' name='peca_descricao_"+linha+"' id='peca_descricao_"+linha+"' value='' onkeyup='pulaCampo(\"descricao\","+linha+",event); ' tabindex='"+(linha+1)+"' rel='"+linha+"/> <img width='16px' border='0' align='absmiddle' style='cursor: pointer' onclick='buscaPeca(document.frm_pedido.peca_descricao_"+linha+","+linha+",\"descricao\");' src='imagens/lupa.png' />");

				celula.linha_nova;
				nova_linha.appendChild(celula);

				/*Cria TD */
				var celula = criaCelula('');
				celula.style.cssText = 'text-align: center;';

				var linha_nova = $(celula).append("<input style='width: 30px; text-align: left;' type='text' class='frm numeric' name='qtde_"+linha+"' id='qtde_"+linha+"' value='' onblur='fnc_jacto_preco("+linha+"); ' onkeyup='pulaCampo(\"quantidade\","+linha+",event); adicionarLinha("+linha+");' tabindex='"+(linha+1)+"' /><input type='hidden' class='frm' name='multiplo_"+linha+"' id='multiplo_"+linha+"' value='' />");
				celula.linha_nova;
				nova_linha.appendChild(celula);

				/*Cria TD */
				var celula = criaCelula('');
				celula.style.cssText = 'text-align: center;';

				var linha_nova = $(celula).append("<input style='width: 60px; text-align: right;' type='text' class='frm' name='preco_"+linha+"' id='preco_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' />");
				celula.linha_nova;
				nova_linha.appendChild(celula);

				/*Cria TD */
				var celula = criaCelula('');
				celula.style.cssText = 'text-align: center;';

				var linha_nova = $(celula).append("<input style='width: 60px; text-align: right;' type='text' class='frm' name='ipi_"+linha+"' id='ipi_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' rel='total_pecas' />");
				celula.linha_nova;
				nova_linha.appendChild(celula);

				/*Cria TD */
				var celula = criaCelula('');
				celula.style.cssText = 'text-align: center;';

				var linha_nova = $(celula).append("<input style='width: 60px; text-align: right;' type='text' class='frm' name='preco_ipi_"+linha+"' id='preco_ipi_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' rel='total_pecas' />");
				celula.linha_nova;

				nova_linha.appendChild(celula);

				/*Cria TD */
				var celula = criaCelula('');
				celula.style.cssText = 'text-align: center;';

				var linha_nova = $(celula).append("<input style='width: 60px; text-align: right;' type='text' class='frm' name='sub_total_"+linha+"' id='sub_total_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' rel='total_pecas' />");
				celula.linha_nova;

				nova_linha.appendChild(celula);

				var celula = criaCelula('');
				celula.style.cssText = 'text-align: center;';

				var linha_nova = $(celula).append("<input hidden style='width: 60px; text-align: right;' type='text' class='frm' name='marca_"+linha+"' id='marca_"+linha+"' readonly='readonly' onfocus='ignoraCampo("+linha+");' rel='total_pecas' />");
				celula.linha_nova;

				nova_linha.appendChild(celula);

				/************ FINALIZA LINHA DA TABELA ***********/
				var tbody = document.createElement('TBODY');
				tbody.appendChild(nova_linha);
				tbl.appendChild(tbody);

				$('#qtde_item').val(linha);

				adicionarLinha2(linha);
			
			}
		}
	}

	function criaCelula(texto) {
		var celula = document.createElement('td');
		var textoNode = document.createTextNode(texto);
		celula.appendChild(textoNode);
		return celula;
	}


	function adicionarLinha2(linha) {

		linha = parseInt(linha);

		var tbl = document.getElementById('tabela_itens');

		var nova_linha = document.createElement('tr');
		$(nova_linha).append('<td class="msgDuplicidade_'+linha+'" colspan="8"><div id="mudou_'+linha+'"></div></td>');

		var tbody = document.createElement('TBODY');
		tbody.appendChild(nova_linha);
		tbl.appendChild(tbody);
	}

	function Trim(s){
			var l=0;
			var r=s.length -1;

			while(l < s.length && s[l] == ' '){
				l++;
			}
			while(r > l && s[r] == ' '){
				r-=1;
			}
			return s.substring(l, r+1);
		}

	function importarPecas(campo) {

		var lote_pecas = jQuery.trim(campo.value);
		var condicao   = $('select#condicao').val();
		var posto      = <?echo $login_posto?>;
		var array_lote = new Array();
		var erros = '';
		array_lote = lote_pecas.split("\n");
		var j;

		if (condicao == 0) {
			alert(traducao.msg_cond_upload);
			document.getElementById('divAguarde').style.display='none';
		} else {
			for (i = 0; i < array_lote.length ; i++){

				j = 0;
				var array_peca = new Array();

					array_peca = array_lote[i].split("\t");
					
					var referencia = array_peca[0] ;
					referencia = Trim(referencia);
					var qtde   = array_peca[1];

					var total_input = $('#tabela_itens > tbody > tr[name=peca] > td > input[name*="peca_referencia_"]').size();
					linha = parseFloat(total_input);

					adicionarLinha(i-1);

					linha = parseFloat(i);
					
					url = 'jacto_valida_regras.php?linha_form=' + linha + '&posto=<?= $login_posto ?>&produto_referencia=' + referencia + '&condicao=' + condicao + '&cache_bypass=<?= $cache_bypass ?>';
					
					 var campos = $.ajax({
						type: "GET",
						url: url,
						cache: false,
						async: false
					 }).responseText;

					campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
					campos = campos.substring (0,campos.indexOf('</preco>'));
					campos_array = campos.split("|");

					preco      = campos_array[0] ;
					linha_form = campos_array[1] ;
					descricao  = campos_array[2] ;
					mudou      = campos_array[3] ;
					de         = campos_array[4] ;
					referencia = campos_array[5] ;
					ipi        = campos_array[6] ;
					preco_ipi  = campos_array[7] ;
					
					if(descricao.length > 0){
						
						$('#tabela_itens > tbody > tr[name=peca] > td > input[name*="peca_referencia_"]').each(function(indice){

							if($(this).val() == ''){
								$('#peca_referencia_'+j).val(referencia);
								$('#qtde_'+j).val(qtde);
								$('#preco_'+j).val(preco);
								$('#peca_descricao_'+j).val(descricao);
								$('#ipi_'+j).val(ipi);
								$('#preco_ipi_'+j).val(preco_ipi);
								3
								if (mudou == 'SIM') {
									$('#linhadiv_'+j).css('display','block');
									$('#mudou_'+j).css('display','block');
									$('#mudou_'+j).css('background-color','#F28C3E');
									$('#mudou_'+j).html(traducao.codigo_de_substituido.replace('%', de));
								}
								
								verificaMultiplo(j);

								fnc_calcula_total(j);
								return false;
							}
							j++;
						});

					}else{
						if (referencia.length>0) {
							$('#tabela_itens > tbody > tr[name=peca] > td > input[name*="peca_referencia_"]').each(function(indice){
								if($(this).val() == ''){
									$('#peca_referencia_'+j).val(referencia);
									$('#qtde_'+j).val(qtde);
									$('#peca_descricao_'+j).val(descricao);
									$('#peca_referencia_'+j).parent().parent().css('background-color','red');
									verificaMultiplo(j);
									fnc_calcula_total(j);
									return false;
								}
								j++;
							});
						}

						var erros = erros +", " + referencia;
						}
				//}
			}
			adicionarLinha(i);

			if (erros.length>0) {
				alert(traducao.pecas_nao_encontradas + "\n\n" + 
					  substr(erros,1) + "\n\n" +
					  traducao.verificar_codigo_pecas
				);

			}

		}
		document.getElementById('divAguarde').style.display='none';
	}

	function importaPecaViaCatalogo(lote_pecas){
		
		var posto    = <?= $login_posto ?>;
		var condicao = $('select#condicao').val();
		var array_lote = new Array();
		var erros = '';
		var j;

		array_lote = lote_pecas;//.split(",");
		
		for (i = 0; i < array_lote.length ; i++){

			j = 0;
			var array_peca = new Array();
			array_peca = array_lote[i].split("|");

			var referencia = array_peca[0] ;
			var qtde        = array_peca[1];

			adicionarLinha(i-1);

			linha = parseFloat(i);

			url = 'jacto_valida_regras.php?linha_form=' + linha + '&posto=<?= $login_posto ?>&produto_referencia=' + referencia + '&condicao=' + condicao + '&cache_bypass=<?= $cache_bypass ?>';

			 var campos = $.ajax({
							type: "GET",
							url: url,
							cache: false,
							async: false
						}).responseText;

			campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
			campos = campos.substring (0,campos.indexOf('</preco>'));
			campos_array = campos.split("|");

			preco      = campos_array[0] ;
			linha_form = campos_array[1] ;
			descricao  = campos_array[2] ;
			mudou      = campos_array[3] ;
			de         = campos_array[4] ;
			referencia = campos_array[5] ;
			ipi        = campos_array[6] ;
			preco_ipi  = campos_array[7] ;

			if (descricao.length>0) {

				$('#tabela_itens > tbody > tr[name=peca] > td > input[name*="peca_referencia_"]').each(function(indice){

					if($(this).val() == ''){
						$('#peca_referencia_'+j).val(referencia);
						$('#qtde_'+j).val(qtde);
						$('#preco_'+j).val(preco);
						$('#peca_descricao_'+j).val(descricao);
						$('#ipi_'+j).val(ipi);
						$('#preco_ipi_'+j).val(preco_ipi);

						if (mudou == 'SIM') {
							$('#linhadiv_'+j).css('display','block');
							$('#mudou_'+j).css('display','block');
							$('#mudou_'+j).css('background-color','#F28C3E');
							$('#mudou_'+j).html(traducao.codigo_de_substituido.replace('%', de));
						}

						verificaMultiplo(j);

						
						return false;
					}
					j++;
				});
				fnc_calcula_total(j);

			} else {
				if (referencia.length>0) {
					$('#tabela_itens > tbody > tr[name=peca] > td > input[name*="peca_referencia_"]').each(function(indice){
						if($(this).val() == ''){
							$('#peca_referencia_'+j).val(referencia);
							$('#qtde_'+j).val(qtde);
							$('#peca_descricao_'+j).val(descricao);
							$('#peca_referencia_'+j).parent().parent().css('background-color','red');
							
							if (mudou == 'SIM') {
								$('#linhadiv_'+j).css('display','block');
								$('#mudou_'+j).css('display','block');
								$('#mudou_'+j).css('background-color','red');
								$('#mudou_'+j).html(traducao.codigo_de_substituido.replace('%', de));
							}

							verificaMultiplo(j);
							
							fnc_calcula_total(j);
							return false;
						}
						j++;
					});
				}
				
				var erros = erros +", " + referencia;
			}
		}
		adicionarLinha(i);
		if (erros.length>0) {
			alert(traducao.pecas_nao_encontradas + "\n\n" + 
				  substr(erros,1) + "\n\n" +
				  traducao.verificar_codigo_pecas
			);
		}

		document.getElementById('divAguarde').style.display='none';

	}

	function retorna_peca(referencia,descricao,posicao,marca=null){
		eval("document.frm_pedido.peca_referencia_"+posicao+".value = '"+referencia+"';");
		eval("document.frm_pedido.peca_descricao_"+posicao+".value = '"+descricao+"';");
		eval("document.frm_pedido.marca_"+posicao+".value = '"+marca+"';");

		verificaDePara(referencia, posicao);
		verificaMarca(marca,referencia,posicao);
		verificaDuplicidade(posicao);

		//eval("document.frm_pedido.qtde_"+posicao+".focus();");
	}

	function verificaMarca(marca, referencia, posicao = null) {

		var marca_selecionada = $("select[name=marca]").val();

		$("#erro_peca_"+referencia).remove();

		if (marca_selecionada != undefined) {

			if (marca != marca_selecionada) {

				let span_erro_peca = "Atenção: as peças em destaque são de uma empresa diferente da selecionada no formulário. Retire-as manualmente antes de gravar ou clique <a onclick='removePecasEmpresa()'>aqui</a> para remover todas<br /></span>";
				$(".msg_erro_peca").show().find("td").html(span_erro_peca);

				if (posicao != null) {
					$("tr[name=peca][rel="+posicao+"]").css({
						'background-color': 'red'
					});
				}

			} else if ($(".msg_erro_peca span").length == 0) {

				$(".msg_erro_peca").hide();
				$('#peca_referencia_' + posicao).parent().parent().css('background-color','#D9E2EF');

			}

		} else {
			$(".msg_erro_peca").hide();
			$('#peca_referencia_' + posicao).parent().parent().css('background-color','#D9E2EF');
		}

	}

	function removePecasEmpresa() {

		var marca_selecionada = $("select[name=marca]").val();

		if (marca_selecionada != undefined) {

			$("tr[name=peca]").filter(function(){
				return $(this).find("input[name^=peca_referencia_]").val() != "";
			}).each(function(){

				let referencia_peca = $(this).find("input[name^=peca_referencia_]").val();
				let marca_peca      = $(this).find("input[name^=marca_]").val();
				let posicao         = $(this).attr("rel");

				if (marca_peca != marca_selecionada) {
					apagaLinha(posicao);
				}

				$(".msg_erro_peca").hide();

			});

		}

	}

	function verificaDePara(referencia, posicao){
		var token = $("input[name='token_pedido']").val();
		$.ajax({
			url: "<?php echo $PHP_SELF;?>",
			type: "POST",
			data: "verificaDePara=verificaDePara&referencia="+referencia+"&token="+token,
			success: function(resposta){
				if(resposta != 0){
					// echo "$de|$para|$descricao";

					dados = resposta.split("|");
					eval("document.frm_pedido.peca_referencia_"+posicao+".value = '"+dados[1]+"';");
					eval("document.frm_pedido.peca_descricao_"+posicao+".value = '"+dados[2]+"';");
					eval("document.frm_pedido.marca_"+posicao+".value = '"+dados[3]+"';");

					$('#mudou_'+posicao).css('display','block');
					$('#mudou_'+posicao).css('background-color','#F28C3E');
					$('#mudou_'+posicao).html(traducao.codigo_de_substituido.replace('%', dados[0]));

					//eval("document.frm_pedido.qtde_"+posicao+".focus();");
				}
			}
		});
	}

	$(function () {

		var tabela_itens = $("#tabela_itens tr td input.xtreferencia");

		$.each(tabela_itens, function( posicaoEach, elemento ) {

			$("#peca_referencia_" + posicaoEach).change(function() {

				verificaDuplicidade(posicaoEach);
			});
		});
	});

	function verificaDuplicidade(posicao) {

		var peca_referencia = $("#peca_referencia_" + posicao).val();

		var tabela_itens = $("#tabela_itens tr td input.xtreferencia");

		$.each(tabela_itens, function( posicaoEach, elemento ) {
  
  			var cod = $(elemento).val();

			if (cod.length > 0 && posicaoEach != posicao) { 

				if (peca_referencia == cod) {
					
					eval("document.frm_pedido.multiplo_" + posicaoEach + ".value ='"+peca_referencia+"';");

					var msg = "<font face='arial, verdana' color='#c09853' size='-1'><strong>Peça ja inserida. Para adicionar mais, altere a quantidade</strong></font>";

					var msgDuplicidade = "<div style='background-color : #fcf8e3; display: block'>" + msg + "</div>";

					$(".msgDuplicidade_" + posicaoEach).html("");

					$(".msgDuplicidade_" + posicaoEach).html(msgDuplicidade);

					apagaLinha(posicao);

					$("#qtde_" + posicaoEach).focus();

				}	
			}
		});
	}

	function verificaDuplicidadeSolicitado(posicao) {

		var peca_referencia = $("#peca_referencia_" + posicao).val();
		var tbl_resumo      = $("#resumo_pedido td");

		var pecas_salvas = [];
		var peca_qtd     = [];
		var cont         = 0;
		var chave        = 0;

		$.each(tbl_resumo, function(index, value) {
			
			let cod = $(value).text();

			if (cont == 0) {

				pecas_salvas.push(cod);

				chave = cod;

				cont = cont + 1;

			} else { 

				if (cont == 1 ) {
					peca_qtd[chave] = []

					peca_qtd[chave]["descricao"] = cod;
				}

				if (cont == 2 ) {

					peca_qtd[chave]["quantidade"] = parseInt(cod);
				}

				if (cont == 7) {

					cont = 0;

				} else {

					cont = cont + 1;
				}

			}
		});

		if (jQuery.inArray(peca_referencia, pecas_salvas) > -1) {

			var quantidade_solicitada = peca_qtd[peca_referencia]["quantidade"];

			eval("document.frm_pedido.multiplo_" + posicao + ".value ='"+peca_referencia+"';");

			var msgDuplicidade = "<div style='background-color : #F2F75F; display: block'>" + msg + "</div>";

			$(".msgDuplicidade_" + posicao).html("");

			$(".msgDuplicidade_" + posicao).append(msgDuplicidade);

		}		
	}

	function verificaMultiplo(posicao){
		var referencia = $('#peca_referencia_'+posicao).val();
		var qtde = $('#qtde_'+posicao).val();
		let marca_emp = $("select[name=marca] option:selected").val();
		
		if(referencia.length > 0 && qtde.length > 0){
			$.ajax({
				url: "<?php echo $PHP_SELF;?>",
				type: "POST",
				data: "verificaMultiplo=verificaMultiplo&referencia="+referencia+"&qtde="+qtde+"&posicao="+posicao+"&marca_emp="+marca_emp,
				success: function(resposta){
					if(resposta != 0 && resposta != 1){
						dados = resposta.split("|");
						eval("document.frm_pedido.multiplo_"+posicao+".value = '"+dados[0]+"';");

						$('#acerta_quantidade_todas').css('display','block');
						$('#mudou_'+posicao).css('display','block');
						$('#mudou_'+posicao).css('background-color','#118A3A');

						var msg = traducao.acertar_quantidade + '</a> - ' + LF +
							traducao.peca_deve_ser_multiplo + ' ' +
							traducao.click_em_acertar_qtde;

						var acertaPecas = "<a href='javascript:void(0);' onclick='acertarQuantidade("+posicao+")' style='color: #FFF;'>" +
							msg.replace('%', referencia).replace('%', dados[0]);
						$('#mudou_'+posicao).html(acertaPecas);

						eval("document.frm_pedido.peca_referencia_"+(parseInt(posicao)+1)+".focus();");
					}

					if(resposta == 1)
						$('#mudou_'+posicao).css('display','none');
				}
			});
		}
	}

	function acertarQuantidade(posicao){
		var qtde = $('#qtde_'+posicao).val();
		var referencia = $('#peca_referencia_'+posicao).val();
		var multiplo = $('#multiplo_'+posicao).val();
		
		if(referencia.length > 0 && qtde.length > 0 && multiplo.length > 0){
			var resultado = Math.ceil(qtde/multiplo)*multiplo;
			
			apagaLinhaAcertoPecas(posicao);
			$('#qtde_'+posicao).val(resultado);
			fnc_calcula_total(posicao);
		}

	}

	function acertaQuantidadeTodas(){
		var total = $('#qtde_item').val();

		for(i = 0; i < total; i++){
			acertarQuantidade(i);
		}

		$('#acerta_quantidade_todas').css('display','none');
	
	}

	function geraToken(){
		var cnpj = $("input[name=cnpj_posto]").val();
		$.ajax({
			url: "pedido_jacto_cadastro.php",
			type: "POST",
			data: "geraToken=sim&cnpj="+cnpj,
			success : function(data){
				retorno = data.split('|');
				if(retorno[0] == "ok"){
					$("input[name='token_pedido']").val(retorno[1]);
					abrirPop(" http://jacto.net.br/Token.aspx?Token="+retorno[1]+"&Const=OUTROSITERETORNO",750,600);
				}
			}
		});
	}

</script>

<style type="text/css">
	body {
		font: 80% Verdana,Arial,sans-serif;
		background: #FFF;
	}

	.titulo {
		background:#7392BF;
		width: 650px;
		text-align: center;
		padding: 1px 1px; /* padding greater than corner height|width */
		font-size:12px;
		color:#FFFFFF;
	}
	.titulo h1 {
		color:white;
		font-size: 120%;
	}

	.subtitulo {
		background:#FCF0D8;
		width: 600px;
		text-align: center;
		padding: 2px 2px; /* padding greater than corner height|width */
		margin: 10px auto;
		color:#392804;
		text-transform: uppercase;
	}
	.subtitulo h1 {
		color:black;
		font-size: 120%;
	}

	.content {
		background:#CDDBF1;
		width: 600px;
		text-align: center;
		padding: 5px; /* padding greater than corner height|width */
		margin: 1em 0.25em;
		color:black;
	}

	.content h1 {
		color:black;
		font-size: 120%;
	}

	.extra {
		background:#BFDCFB;
		width: 600px;
		text-align: center;
		padding: 2px 2px; /* padding greater than corner height|width */
		margin: 1em 0.25em;
		color:#000000;
		text-align:left;
	}
	.extra span {
		color:#FF0D13;
		font-size:14px;
		font-weight:bold;
		padding-left:30px;
	}

	.error {
		background:#ED1B1B;
		width: 600px;
		text-align: center;
		padding: 2px 2px; /* padding greater than corner height|width */
		margin: 1em 0.25em;
		color:#FFFFFF;
		font-size:12px;
	}
	.error h1 {
		color:#FFFFFF;
		font-size:14px;
		font-size:normal;
		text-transform: capitalize;
	}

	.inicio {
		background:#8BBEF8;
		width: 600px;
		text-align: center;
		padding: 1px 2px; /* padding greater than corner height|width */
		margin: 0;
		color:#FFFFFF;
	}
	.inicio h1 {
		color:white;
		font-size: 105%;
		font-weight:bold;
	}

	.subinicio {
		background:#E1EEFD;
		width: 550px;
		text-align: center;
		padding: 1px 2px; /* padding greater than corner height|width */
		margin: 0.0em 0.0em;
		color:#FFFFFF;
	}
	.subinicio h1 {
		color:white;
		font-size: 105%;
	}

	#tabela {
		font-size:12px;
	}
	#tabela td{
		font-weight:bold;
	}

	.xTabela{
		font-family: Verdana, Arial, Sans-serif;
		font-size:12px;
		padding:3px;
	}

	#layout{
		width: 700px;
		margin:0 auto;
	}

	ul#split, ul#split li{
		margin:0 auto;
		padding:0;
		width:700px;
		list-style:none
	}

	ul#split li{
		float:left;
		width:700px;
	}

	ul#split h3{
		font-size:14px;
		margin:0px;
		padding: 5px 0 0;
		text-align:center;
		font-weight:bold;
		color:white;
	}
	ul#split h4{
		font-size:90%
		margin:0px;
		padding-top: 1px;
		padding-bottom: 1px;
		text-align:center;
		font-weight:bold;
		color:white;
	}

	ul#split p{
		margin:0;
	}
	p{
		background-color:# D9E2EF;
	}
	ul#split div{
		background: #D9E2EF;
	}

	li#one{
		text-align:center;
	}

	li#one div{
		border:1px solid #D9E2EF
	}
	li#one h3{
		background: #D9E2EF
	}

	li#one h4{
		background: #D9E2EF
	}

	.coluna1{
		width:150px;
		font-weight:bold;
		font-size:11px;
		display: inline;
		float:left;

	}
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}


	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
		text-transform: capitalize;
	}

	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width: 700px;
		margin: 0 auto;
		padding: 2px 0;
		border:1px solid #596d9b;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
	}

	.subtitulo{
		background-color: #7092BE;
		font:bold 11px Arial;
		color: #FFFFFF;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;

	}

	.msg_sucesso{
		background-color: green;
		font: bold 16px "Arial";
		color: #FFFFFF;
		text-align:center;
		width: 700px;
		margin: 0 auto;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
		width: 700px;
		margin: 0 auto;
	}

	.condicao_venda p{
		color: #000;
		font-size: 12px;
		margin: 0;
		padding: 0;
	}

	.condicao_venda li{
		margin: 0;
		padding: 2px 12px;
		text-align: left;
		font-size: 11px;
		color: #000;
	}
</style>

<? include "javascript_pesquisas.php" ?>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script src="js/jquery.json-2.4.min.js"></script>
<script src="js/jquery.blockUI_2.39.js" ></script>
<script type="text/javascript">

	$(function(){



		$("select[name=marca]").change(function(){

			var marca_selecionada = $(this).val();
			let condPag = '<?=$condicao?>'

			if (marca_selecionada != "") {

				$("tr[name=peca]").filter(function(){
					return $(this).find("input[name^=peca_referencia_]").val() != "";
				}).each(function(){

					let referencia_peca = $(this).find("input[name^=peca_referencia_]").val();
					let marca_peca      = $(this).find("input[name^=marca_]").val();
					let posicao         = $(this).attr("rel");

					if (marca_peca != "") {
						verificaMarca(marca_peca, referencia_peca, posicao);
					}

				});

				condicao_marca(marca_selecionada);


			}

		});


	});

	function condicao_marca(marca){
		let condPag = '<?=$condicao?>';

		$.ajax({
		async: true,
			type: 'POST',
			dataType:"json",
			url: "pedido_jacto_cadastro.php",
			data: {
			ajax_valida_marca: true,
				marca: marca
		},
			success: function (data) {

				if (!data.erro) {
					let option_vazio = $("<option></option>", {
					value: ""
				});

					$("#classe_pedido").html(option_vazio)

						$.each(data, function(key, value) {

							$("#classe_pedido").append("<option value='"+value.valor+"' mensagem='"+value.mensagem+"' prazo='"+value.prazo+"'>"+value.descricao+"</option>");  
						});
				}

			}
		});

		$.ajax({
			async: true,
				type: 'POST',
				dataType:"json",
				url: "pedido_jacto_cadastro.php",
				data: {
				ajax_busca_condPag: true,
					marca: marca
		},
			success: function (data) {

				if (!data.erro) {
					let option_vazio = $("<option></option>", {
					value: ""
				});

					$("#condicao").html(option_vazio)

						$.each(data, function(key, value) {
							let selected_cond = ''

								if (value.valor == condPag) {
									selected_cond = "selected"
								}

							$("#condicao").append(`<option ${selected_cond} value='${value.valor}'>${value.descricao}</option>`);

						});
				}

			}
		});
	}

	function fnc_jacto_preco (linha_form) {
		var posto    = <?= $login_posto ?>;
		var referencia = jQuery.trim($("#peca_referencia_"+linha_form).val());
		var qtde = jQuery.trim($("#qtde_"+linha_form).val());

		if(referencia.length > 0){
			campo_preco     = 'preco_' + linha_form;
			campo_ipi       = 'ipi_' + linha_form;
			campo_preco_ipi = 'preco_ipi_' + linha_form;
			document.getElementById(campo_preco).value = "";
			
			$.ajax({
				url: "pedido_jacto_cadastro.php",
				data: "linha_form="+linha_form+"&produto_referencia="+referencia,
				type: "GET",
				async: false,
				success: function (campos) {
					campos_array    = campos.split("|");
					ipi             = campos_array[2] ;
					preco           = campos_array[1];
					preco           = number_format( preco, 2 , ',','.' ) ;
					preco_ipi   = campos_array[3];
					preco_ipi       = number_format(preco_ipi, 2 , ',','.' ) ;
					console.log(preco_ipi);
					linha_form      = campos_array[0] ;
					
					document.getElementById(campo_preco).value = preco;
					document.getElementById(campo_ipi).value = ipi;
					document.getElementById(campo_preco_ipi).value = preco_ipi;
				}
			});
		}

		fnc_calcula_total(linha_form);
	}

	function fnc_calcula_total (linha_form) {
		var total = 0;

		preco = document.getElementById('preco_ipi_'+linha_form).value;
		qtde = document.getElementById('qtde_'+linha_form).value;
		referencia = document.getElementById('peca_referencia_'+linha_form).value;

		preco = preco.replace(".", "");
		preco = preco.replace(",", "."); 
		var preco =  parseFloat(preco);

		if (qtde && preco) {
			total = qtde * preco;
			total = total.toFixed(2);

			total  = number_format( total, 2 , ',','.') ;
		}

		document.getElementById('sub_total_'+linha_form).value = total;
		calcula_total_pedido();     
	}

	function calcula_total_pedido () {
		var total = 0;

		$("#tabela_itens > tbody > tr[name=peca]").each(function () {
			var peca = 0;
			peca = $(this).find("input[name^=sub_total_]").val();
			peca = peca.replace(".", "");
			peca = peca.replace(",", ".");
			peca = parseFloat(peca);

			if (peca > 0) {
				total = total + peca;
			}
		});

		$("#total_pecas").val(number_format(total, 2, ",", "."));
	}

	function atualiza_proxima_linha(linha_form){
		var produto_referencia = document.getElementById('produto_referencia_'+linha_form).value;
		var produto_descricao  = document.getElementById('produto_descricao_'+linha_form).value;

		var proxima_linha = linha_form + 1;

		if (document.getElementById('produto_descricao_'+proxima_linha)){
			if (! document.getElementById('produto_descricao_'+proxima_linha).value){
				document.getElementById('produto_referencia_'+proxima_linha).value = produto_referencia;
				document.getElementById('produto_descricao_'+proxima_linha).value = produto_descricao;
			}
		}
	}

	$(document).ready(function() {

		condicao_marca();
		$(".btn-importa-excel").click(function(){
			Shadowbox.init();
			Shadowbox.open({
				content: "importa_peca_excel_jacto.php",
				player:  "iframe",
				width:   600,
				height:  240
			}); 
		});

		setInterval(function(){
			var token = $("input[name='token_pedido']").val();
			if(token != ""){
				verificaToken(token);
			}
		},2000);

		$('.completar').each(function(){

			linha = $(this).attr('rel');
			campo1 = 'peca_ref_descricao_'+linha;
			campo2 = 'peca_referencia_'+linha;
			conteudo = $(this).val();
			
			autocompletar_item(campo1,campo2,conteudo,linha) ;
			//$("#printTotalAjax").maskMoney({symbol:'R$ ', showSymbol:true, thousands:'.', decimal:',', symbolStay: true});

		});

		function formatItem(row) {
			return row[0] + " - " + row[1];
		}
		
		function formatResult(row) {
			return row[0];
		}
		
		/* Busca pelo Código */
		// $("#codigo_transp").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		//  minChars: 5,
		//  delay: 150,
		//  width: 350,
		//  matchContains: true,
		//  formatItem: formatItem,
		//  formatResult: function(row) {return row[0];}
		// });

		// $("#codigo_transp").result(function(event, data, formatted) {
		//  $("#posto_nome").val(data[1]) ;
		// });

		/* Busca pelo Nome */
		// $("#nome_transp").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		//  minChars: 5,
		//  delay: 150,
		//  width: 350,
		//  matchContains: true,
		//  formatItem: formatItem,
		//  formatResult: function(row) {return row[1];}
		// });

		// $("#nome_transp").result(function(event, data, formatted) {
		//  $("#codigo_transp").val(data[0]) ;
		//  //alert(data[2]);
		// });


		// $("#retirada_local").click(function(){
		//  if($(this).is(':checked')==true){
		//      $("input[name='codigo_transp']").val("00000000000000");
		//      $("input[name='nome_transp']").val("**_Retirar na Jacto_**");
		//  }else{
		//      $("input[name='codigo_transp']").val('');
		//      $("input[name='nome_transp']").val('');
		//  }
		// });
	});

	function pulaCampo(campo, posicao, e){
		
		var key = e.keyCode || e.which;

		if(campo == 'referencia'){
			if (key == '13') {
				$('#peca_descricao_'+posicao).focus();
			}
		}

		if(campo == 'descricao'){
			if (key == '13') {
				$('#qtde_'+posicao).focus();
			}
		}

		if(campo == 'quantidade'){
			var nova_posicao = posicao;
			var posicao = posicao + 1;

			if(posicao > 37)
				adicionarLinha(posicao-1);

			if (key == '13') {
				$("#peca_referencia_"+posicao).focus();
			}
		}
	}

	function ignoraCampo(posicao){
		var posicao = posicao;
		$('#peca_referencia_'+posicao).focus();
	}

	function apagaLinha(linha){
		var ref = $("input[name=peca_referencia_" + linha + "]").val();
		var token = $("input[name=token_pedido]").val();

		if (ref && token) { deletesRefbyToken(ref, token); };

		$("#mudou_"+linha).html('');
		$("#mudou_"+linha).css('display','none');

		$(".msgDuplicidade_"+linha).html('');
	
		$("input[name=pedido_item_"     + linha + "]").val('');
		$("input[name=peca_referencia_" + linha + "]").val('');
		$("input[name=peca_descricao_"  + linha + "]").val('');
		$("input[name=qtde_"            + linha + "]").val('');
		$("input[name=preco_"           + linha + "]").val('');
		$("input[name=ipi_"             + linha + "]").val('');
		$("input[name=preco_ipi_"       + linha + "]").val('');
		$("input[name=sub_total_"       + linha + "]").val('');
		$("input[name=marca_"           + linha + "]").val('');
		$('#peca_referencia_'           + linha).parent().parent().css('background-color','#D9E2EF');
		$("input[name=peca_referencia_" + linha + "]").focus();


	}

	function deletesRefbyToken(ref, token) {
		$.ajax({
			url:  "token_pedido.php",
			type: "POST",
			data: "del=true&ref=" + ref + "&token=" + token,

			success: function(resposta){
				return true;
			}
		});
	}

	function apagaLinhaAcertoPecas(linha){
		$("#mudou_"+linha).html('');
		$("#mudou_"+linha).css('display','none');

		$('#peca_referencia_'+linha).parent().parent().css('background-color','#D9E2EF');
		
		$("input[name=peca_referencia_"+(linha+1)+"]").focus();
	}


	// function fnc_pesquisa_peca (campo,form) {
	//  //alert(campo.value);
	//  if (campo.value != "") {
	//      var url = "";
	//      url = "pesquisa.php?campo=" + campo.value + "&form=" + form ;
	//      janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
	//      janela.focus();
	//  }
	// }

	function pesquisaPeca(campo, linha, marca=null){

		var referencia = campo;//$(campo).attr('value');
		var posicao = linha; //$(campo).attr('rel');
		let marca_emp = $("select[name=marca]").val();
		//alert(referencia);
		if (jQuery.trim(referencia.value).length > 1 && marca_emp.length > 0){
			$.ajax({
				url: "<?php echo $PHP_SELF;?>",
				type: "POST",
				data: "pesquisaPeca=pesquisaPeca&referencia="+referencia.value+"&posicao="+posicao+"&marca_emp="+marca_emp,

				success: function(resposta){
					/*
					if(resposta == 0){
						window.alert('Peça '+referencia.value+' não encontrada.');
						apagaLinha(posicao);
					}
					
					if(resposta == 1){
						buscaPeca(referencia,posicao,'referencia');
					}
					
					if(resposta == 2){
						window.alert('Referência inválida.');
						apagaLinha(posicao);
					}
					*/

					if (jQuery.trim(resposta).length > 3){

						var retorno = resposta.split('|'); 

						retorna_peca(retorno[0],retorno[1],retorno[2],retorno[3]);
					} else {
						alert("Peça não encontrada para a Linha do Produto");
						apagaLinha(posicao)
					}
				}
			});
		} else if (marca_emp.length <= 0) {
			alert("Selecionar a Linha de Produto");
			$("select[name=marca]").focus();

		}
	}

	function apagaItemPedido(pedido, item) {

		if (item > 0 && pedido > 0) {

			$.ajax({
				url: "<?php echo $PHP_SELF;?>",
				type: "POST",
				data: "apagarPedido=apagarPedidoAJAX&pedido="+pedido+"&item="+item,
				success: function(resposta){
					if (resposta == 1) {

						$("#"+item).remove();

						var total = 0;
						$('td.TotalAjax').each(function(index) {
							total = total + parseFloat(($(this).text().replace(".", "")).replace(",", "."));
						});

						if (total == 0) {
							$("#resumo_pedido").remove();
							$("#btn_resumo_pedido").remove();
						}

						$("#printTotalAjax").html(number_format(total, 2 , ',','.'));

						location.reload();

					} else {
						alert(traducao.erro_ao_excluir_peca);
					}
				}
			});

		}
	}

	function isNumeric(n) {
	  return !isNaN(parseFloat(n)) && isFinite(n);
	}

	function procuraPeca (ref) {
		var achou = false;

		$("#tabela_itens > tbody > tr").each(function () {
			if ($.trim($(this).find("input[name^=peca_referencia_]").val()) == ref) {
				achou = true;
			}
		});

		return achou;
	}

	function getLastTr () {
		var linha = -1;

		$("#tabela_itens > tbody > tr[name=peca]").each(function () {
			if ($.trim($(this).find("input[name^=peca_referencia_]").val()).length > 0) {
				linha = $(this).attr("rel");
			}
		});

		if (linha == -1) { return false; };

		linha++;

		return linha;
	}



	function importaExcel(conteudo) {
		$.blockUI({ message: "Importando peças aguarde..." });

		setTimeout(function () {
			var arrayPecas = conteudo;
			var pecas = {};
			var ref;
			var descricao_peca;
			var qtde;
			var linha;
			var totalPecas = 0;
			var marca_emp = $("select[name=marca] option:selected").val();

			

			if (arrayPecas.length > 500) {
				alert("O maximo de peças que pode ser importada é 500 peças");
				$.unblockUI();
				return;
			}

			
			for (var i in arrayPecas) {             
				if (arrayPecas[i].length > 0) {
					linha = arrayPecas[i];                  
					if (linha.length > 0) {
						ref = linha[0];
						descricao_peca = linha[1];
						qtde = linha[2];


						if (!isNumeric(qtde)) {
							qtde = 0;
						}

						if (procuraPeca(ref) == false) {
							
							pecas[i] = { ref: $.trim(ref), desc: $.trim(descricao_peca), qtde: $.trim(qtde), marca_emp: marca_emp };  

							totalPecas++;
						}
					}
				}
			}

			var linhas = 0;

			$("#tabela_itens > tbody > tr[name=peca]").each(function () {
				if ($.trim($(this).find("input[name^=peca_referencia_]").val()).length == 0) {
					linhas++;
				}
			});
			
			if (totalPecas > linhas) {
				$("#qtde_item").val(parseInt($("#qtde_item").val()) + ((totalPecas - linhas) + 1));
				var x = $("#tabela_itens > tbody > tr[name=peca]").length;

				for (var i = linhas; i <= totalPecas; i++) {
					$("#tabela_itens > tbody").append("<tr name='peca' rel='"+x+"'>"+$("tr[id=modelo]").clone().html().replace(/__modelo__/g, x)+"</tr>");
					$("#tabela_itens > tbody").append("<tr>"+$("tr[id=modelo_mudou]").clone().html().replace(/__modelo__/g, x)+"</tr>");
					x++;
				}
			} else if (totalPecas == linhas) {
				$("#qtde_item").val(parseInt($("#qtde_item").val()) + ((totalPecas - linhas) + 1));
				var x = $("#tabela_itens > tbody > tr[name=peca]").length + 1;

				$("#tabela_itens > tbody").append("<tr name='peca' rel='"+x+"'>"+$("tr[id=modelo]").clone().html().replace(/__modelo__/g, x)+"</tr>");
				$("#tabela_itens > tbody").append("<tr>"+$("tr[id=modelo_mudou]").clone().html().replace(/__modelo__/g, x)+"</tr>");
			}

			var last = getLastTr();

			if (last == false) { last = 0; };

			
			$.each(pecas, function (key, peca) {
					setTimeout(function () {
					if (window.navigator.appName == "Microsoft Internet Explorer" && window.navigator.appVersion.match(/MSIE 8.0/g) && key == "indexOf") {
						return;
					}

					$.ajax({
						url: "pecas_importa_excel.php",
						data: peca,
						dataType: "JSON",
						type: "GET",
						async: false,
						success: function (xpeca) {
							xpeca = JSON.parse(xpeca);
							if (window.navigator.appName != "Microsoft Internet Explorer" && !window.navigator.appVersion.match(/MSIE 8.0/g)) {
								var offset = $("#tabela_itens > tbody > tr[rel="+last+"]").offset();
								$(window).scrollTop(parseInt(offset.top) - 100);
							}

							if (!xpeca.erro) {                              
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_referencia_]").val(xpeca.ref);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=qtde_]").val(xpeca.qtde);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_descricao_]").val(xpeca.desc); 
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=preco_]").val(xpeca.preco);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=ipi_]").val(xpeca.ipi);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=marca_]").val(xpeca.marca);
								
								verificaDePara(xpeca.ref, last);
								verificaMarca(xpeca.marca, xpeca.ref, last);
								verificaMultiplo(last);
								fnc_jacto_preco(last);
							} else {
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_referencia_]").val(peca.ref);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_descricao_]").val(peca.desc);  
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=qtde_]").val(peca.qtde);
								$("#tabela_itens > tbody > tr[rel="+last+"]").css("background-color", "#f00");
							}

							last++;
						}
					});
				}, 500);
			});

			$.unblockUI();
		}, 500);
	}
	var teste;
	function importaCatalogo (arrayPecas) {
		// $.blockUI({ message: "Importando peças aguarde..." });

		setTimeout(function () {
			var pecas = {};
			var ref;
			var qtde;
			var linha;
			var totalPecas = 0;


			for (var i in arrayPecas) {
				if (typeof arrayPecas[i] == "object") {
					ref = arrayPecas[i].referencia;
					qtde = arrayPecas[i].qtde;

					if (!isNumeric(qtde)) {
						qtde = 0;
					}

					if (procuraPeca(ref) == false) {
						pecas[i] = { ref: $.trim(ref), qtde: $.trim(qtde) };    

						totalPecas++;
					}
				}
			}


			var linhas = 0;

			$("#tabela_itens > tbody > tr[name=peca]").each(function () {
				if ($.trim($(this).find("input[name^=peca_referencia_]").val()).length == 0) {
					linhas++;
				}
			});
			
			if (totalPecas > linhas) {
				$("#qtde_item").val(parseInt($("#qtde_item").val()) + ((totalPecas - linhas) + 1));
				var x = $("#tabela_itens > tbody > tr[name=peca]").length;

				for (var i = linhas; i <= totalPecas; i++) {
					$("#tabela_itens > tbody").append("<tr name='peca' rel='"+x+"'>"+$("tr[id=modelo]").clone().html().replace(/__modelo__/g, x)+"</tr>");
					$("#tabela_itens > tbody").append("<tr>"+$("tr[id=modelo_mudou]").clone().html().replace(/__modelo__/g, x)+"</tr>");
					x++;
				}
			} else if (totalPecas == linhas) {
				$("#qtde_item").val(parseInt($("#qtde_item").val()) + ((totalPecas - linhas) + 1));
				var x = $("#tabela_itens > tbody > tr[name=peca]").length + 1;

				$("#tabela_itens > tbody").append("<tr name='peca' rel='"+x+"'>"+$("tr[id=modelo]").clone().html().replace(/__modelo__/g, x)+"</tr>");
				$("#tabela_itens > tbody").append("<tr>"+$("tr[id=modelo_mudou]").clone().html().replace(/__modelo__/g, x)+"</tr>");
			}

			var last = getLastTr();

			if (last == false) {
				last = 0;
			}

			$.each(pecas, function (key, peca) {
				setTimeout(function () {
					if (window.navigator.appName == "Microsoft Internet Explorer" && window.navigator.appVersion.match(/MSIE 8.0/g) && key == "indexOf") {
						return;
					}

					$.ajax({
						url: "pecas_importa_excel.php",
						async: false,
						data: peca,
						dataType: "JSON",
						type: "GET",
						success: function (xpeca) {
							xpeca = JSON.parse(xpeca);
							if (window.navigator.appName != "Microsoft Internet Explorer" && !window.navigator.appVersion.match(/MSIE 8.0/g)) {
								var offset = $("#tabela_itens > tbody > tr[rel="+last+"]").offset();
								$(window).scrollTop(parseInt(offset.top) - 100);
							}

							if (!xpeca.erro) {
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_referencia_]").val(xpeca.ref);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=qtde_]").val(xpeca.qtde);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_descricao_]").val(xpeca.desc); 
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=preco_]").val(xpeca.preco);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=ipi_]").val(xpeca.ipi);
								
								verificaDePara(xpeca.ref, last);
								verificaMultiplo(last);
								fnc_jacto_preco(last);
							} else {
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=peca_referencia_]").val(peca.ref);
								$("#tabela_itens > tbody > tr[rel="+last+"]").find("input[name^=qtde_]").val(peca.qtde);
								$("#tabela_itens > tbody > tr[rel="+last+"]").css("background-color", "#f00");
							}

							last++;
						}
					});
				}, 500);
			});

			// $.unblockUI();

		}, 500);
	}
</script>

<?
if($dados_array > 0){
	if($dados_array == 1)
		$msg_erro = traduz('nao.foi.cadastrada.a.peca.no.pedido.verifique.a.quantidade.ou.referencia', $con);
	else
		$msg_erro = traduz('nao.foram.cadastradas.%.pecas.no.pedido.verifique.a.quantidade.ou.referencia', $con, $cook_idioma, $dados_array);
}

if (strlen ($msg_erro) > 0) {
	
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
		$msg_erro = traduz('esta.ordem.de.servico.ja.foi.cadastrada', $con);
	}
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
	
		$erro = traduz('foi.detectado.o.seguinte.erro', $con) . '<br />';
		$msg_erro = substr($msg_erro, 6);
	}
	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
} 

$sql = "SELECT  tbl_condicao.*
		FROM    tbl_condicao
		JOIN    tbl_posto_condicao USING (condicao)
		JOIN    tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_posto_condicao.posto = $login_posto
		AND     tbl_condicao.fabrica     = $login_fabrica
		AND     tbl_condicao.visivel IS TRUE
		AND     tbl_condicao.descricao ilike '%garantia%'
		AND     ( 
					(tbl_condicao.codigo_condicao = 'OUT' and tbl_posto_fabrica.tipo_posto = 236)
						OR
					(tbl_condicao.codigo_condicao <> 'OUT' and tbl_posto_fabrica.tipo_posto <> 236)
				)
		ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
$res = pg_query($con,$sql);

$frase = (pg_num_rows($res) > 0) ? 
		traduz('preencha.seu.pedido.de.compra.garantia', $con, $cook_idioma):
		traduz('preencha.seu.pedido.de.compra', $con, $cook_idioma);

// Texto JACTO
$jacto_cgv = array(
	'titulo' => array(
		'pt-br' => 'Condições Gerais de Venda',
		'es'    => 'Condiciones Generales de Venta',
		'en-us' => 'Global Sales Terms',
	),
	'item_1' => array(
		'pt-br' => 'Este pedido está sujeito a confirmação por parte da fábrica e não vale como recibo;',
		'es'    => 'Esta orden está sujeta a la confirmación por parte de la fábrica y no constituirá recepción.',
		'en-us' => '',
	),
	'item_2' => array(
		'pt-br' => 'Os preços constantes deste pedido são com frete incluso para entrega ao contratante e estão sujeitos a acréscimo de impostos e encargos financeiros para vendas a prazo;',
		'es'    => 'Los precios incluidos en esta aplicación se incluye con el envío para la entrega al contratista y están sujetos a la adición de los impuestos y las cargas financieras para las ventas a crédito;',
		'en-us' => '',
	),
	'item_3' => array(
		'pt-br' => 'Os preços estão sujeitos a reajustes sem aviso prévio;',
		'es'    => 'Los precios están sujetos a ajustes sin previo aviso.',
		'en-us' => '',
	),
	'item_4' => array(
		'pt-br' => 'Para pedidos, obedecer a quantidade mínima por embalagem indicada ou seus múltiplos;',
		'es'    => 'Para pedidos, obedecer a la cantidad mínima indicada por contenedor o múltiplos.',
		'en-us' => '',
	),
	'item_5' => array(
		'pt-br' => 'O status SIM/NÃO da disponibilidade é apenas informativo, não garante a disponibilidade do item no ato do aceite do pedido.',
		'es'    => 'El estado ON/OFF de la disponibilidad es sólo informativo, no garantiza la disponibilidad del artículo en el momento de la aceptación del pedido.',
		'en-us' => '',
	),
	'item_6' => array(
		'pt-br' => 'Esta cotação tem validade de 30 dias a partir da sua data de criação. Após esse período, será cancelada.',
		'es'    => 'Esta cita es válida por 30 días a partir de la fecha de su creación. Después de este período, será cancelada.',
		'en-us' => '',
	),
	
);

echo "<div id='layout'>";
	echo "<div class='texto_avulso' style='width: 700px; margin: 10px 0;'>";
		echo "<p style='padding: 5px; margin: 0;  font-size: 12px; color: #000; text-align: left;'><b>{$jacto_cgv['titulo'][$cook_idioma]}</b></p>";
		echo "<div class='condicao_venda'>
				<ol type='I'>
					<li>{$jacto_cgv['item_1'][$cook_idioma]}</li>
					<li>{$jacto_cgv['item_2'][$cook_idioma]}</li>
					<li>{$jacto_cgv['item_3'][$cook_idioma]}</li>
					<li>{$jacto_cgv['item_4'][$cook_idioma]}</li>
					<li>{$jacto_cgv['item_5'][$cook_idioma]}</li>
					<li>{$jacto_cgv['item_6'][$cook_idioma]}</li>
				</ol>
			</div>";
	echo "</div>";
	echo "<div class='texto_avulso' style='width: 700px; margin: 10px 0'>";
		fecho('pedidos.a.prazo.dependerao.de.analise.do.departamento.de.credito.', $con);
	echo "</div>";
echo "</div>";
?>

<form name="frm_pedido" method="post" action="<? echo $_SERVER['PHP_SELF']; ?>">
<input class="frm" type="hidden" name="pedido" value="<? echo $pedido; ?>">
<input class="frm" type="hidden" name="voltagem" value="<? echo $voltagem; ?>">

<? if ($distribuidor_digita == 't') { ?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr valign='top' style='font-size:12px'>
		<td nowrap align='center'>
			<?fecho('distribuidor.pode.digitar.pedidos.para.seus.postos', $con);?>
			<br>
			<?fecho('digite.o.codigo.do.posto', $con, $cook_idioma);?>
			<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
			<?fecho('ou.deixe.em.branco.para.seus.proprios.pedidos', $con);?>
		</td>
	</tr>
	</table>
<? } ?>

<ul id="split"  style="width:700px;margin: 0; padding: 0;" bgcolor="#D9E2EF">
<li id="one" style='margin: 0; padding: 0;'>

<?
	//VERIFICA SE POSTO PODE PEDIR PECA EM GARANTIA ANTECIPADA
	$sql = "SELECT garantia_antecipada FROM tbl_posto_fabrica WHERE fabrica=$login_fabrica AND posto=$login_posto";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		$garantia_antecipada = pg_fetch_result($res,0,0);
		if($garantia_antecipada <> "t") {
			$garantia_antecipada ="f";
		}
	}

	if(strlen($msg_erro) != 0){
		echo "<div class='msg_erro' style='background: #FF0000; padding: 2px 0'>";
			if (strpos ($msg_erro,"(mudou)") > 0) 
				fecho(array('atencao','<br />','verifique.as.referencias.abaixo.pois.ocorreram.mudancas.de.de.para'), $con);
			else
				echo $erro.$msg_erro;
		echo "</div>";
	}

	?>
	<table width='700' class='formulario' align='center' cellspacing='2' cellpadding='2' border='0'>
		<tr>
			<td class="titulo_tabela" colspan='3'><?fecho('cadastro.de.pedidos', $con)?></td>
		</tr>
		<tr class='subtitulo'>
			<td align="center" colspan='3'><? echo $frase; ?> </td>
		</tr>
		<tr>
			<td colspan="3">
				 <?= (!empty($cook_pedido)) ? "<span style='color: red;'>(Para alterar a empresa, exclua as peças atuais do pedido)</span>" : "" ?><br />
				Linha de Produto <br />
				<select name="marca" class="frm" style="width: 250px;">
					<?php
					$condMarca = "";
					if (!empty($cook_pedido) && !empty($marca_empresa)) {
						$condMarca = "AND (tbl_marca.marca = {$marca_empresa} OR (
							SELECT COUNT(*)
							FROM tbl_pedido_item
							WHERE tbl_pedido_item.pedido = {$cook_pedido}
							LIMIT 1
						) = 0)";
					} else { 
						// $sql_emps = "SELECT parametros_adicionais::jsonb->>'empresas' AS empresas FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
						// $res_emps = pg_query($con, $sql_emps);
						// if (!empty(pg_fetch_result($res_emps, 0, 'empresas'))) {
						// 	$emps = str_replace(";", ",", pg_fetch_result($res_emps, 0, 'empresas'));
						// 	$condMarca = " AND tbl_marca.empresa IN ($emps)";
						// } else {
						// 	$condMarca = "";
						// }
					?>
						<option value="">Selecione a empresa da peça</option>
					<?php
					}

					$sqlEmpresaPeca = "SELECT marca, codigo_marca, descricao
									   FROM tbl_marca
									   JOIN tbl_empresa using(empresa) 
									   WHERE tbl_marca.fabrica = {$login_fabrica}
									   AND tbl_marca.ativo
									   {$condMarca}
									   ";
					$resEmpresaPeca = pg_query($con, $sqlEmpresaPeca);

					while ($dadosEmpresa = pg_fetch_object($resEmpresaPeca)) {

						$selected = ($marca_empresa == $dadosEmpresa->marca) ? "selected" : "";

						?>

						<option value="<?= $dadosEmpresa->marca ?>" <?= $selected ?>>
							<?= $dadosEmpresa->descricao ?>
						</option>

					<?php
					} ?>
				</select>
			</td>
		</tr>
		<tr style='text-transform: capitalize'>
			<td style='padding-left: 50px; text-align: left'>
				<?fecho('ordem.de.compra', $con)?><br />
				<input class="frm" type="text" name="pedido_cliente" maxlength="20" value="<? echo $pedido_cliente ?>" style='width: 150px'>
			</td>
			<td style=' text-align: left'>
				<?fecho('tipo.de.pedido', $con)?><br />
			<?

				$cond_locadora = "AND tbl_tipo_pedido.tipo_pedido in (196)";
				$tipo_posto      = "";
				$option_disabled = "";

				$sql = "SELECT codigo FROM tbl_tipo_posto 
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_posto_fabrica.posto = {$login_posto}";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					$tipo_posto = pg_fetch_result($res, 0, "codigo");
				}

				if (pg_num_rows($res) > 0) {                    
					if($tipo_posto == "FILIAL"){
						$cond = "and tbl_tipo_pedido.tipo_pedido = 203 ";
					}

					echo "<select size='1' name='tipo_pedido' class='frm' style='width: 150px'>";
					$sql = "SELECT   *
							FROM     tbl_tipo_pedido
							WHERE    fabrica = $login_fabrica 
							AND tbl_tipo_pedido.tipo_pedido in (196,203)
							$cond ";
					$sql .= " ORDER BY tipo_pedido DESC ";
					$res = pg_query($con,$sql);

					if (strlen($cook_pedido) == 0 && $tipo_posto == ""){
						echo "<option value='0' selected>- " . traduz('selecione', $con) . "</option>";
					}

					for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {                     
						if($tipo_pedido == pg_fetch_result($res,$i,tipo_pedido)){
							$selected = "selected";                         
						}else{
							$selected = "";
						}

						if($tipo_posto == "FILIAL"){
							if(pg_fetch_result($res, $i, "descricao") == "Orçamento"){
								$selected = "selected";
								$option_disabled = "";
							}else{
								$option_disabled = "hidden";
							}
						}

						echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "' ".$selected." $option_disabled>" . pg_result ($res,$i,descricao). "</option>";
					}
					echo "</select>";                   
				}else{
					echo "<select size='1' name='tipo_pedido' class='frm' ";
					echo " style='width: 150px'>";
					$sql = "SELECT   *
							FROM    tbl_tipo_pedido
							WHERE   (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
							   OR   tbl_tipo_pedido.descricao ILIKE '%Venda%')
							AND     tbl_tipo_pedido.fabrica = $login_fabrica
							AND     (garantia_antecipada is false or garantia_antecipada is null)
							ORDER BY tipo_pedido;";

					#HD 47695
					if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't'){
						$sql = "SELECT   *
								FROM     tbl_tipo_pedido
								WHERE    fabrica = $login_fabrica ";
						if (strlen($tipo_pedido)>0){
							$sql .= " AND      tbl_tipo_pedido.tipo_pedido = $tipo_pedido ";
						}
						$sql .= " ORDER BY tipo_pedido;";
					}

					$res = pg_query($con,$sql);

					for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
						$selected = $tipo_pedido == pg_fetch_result($res,$i,tipo_pedido) ? " selected " : "";
						echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "' $selected>" . pg_result ($res,$i,descricao). "</option>";
					}

					if($garantia_antecipada=="t"){
						$sql = "SELECT   *
								FROM     tbl_tipo_pedido
								WHERE    fabrica = $login_fabrica
								AND garantia_antecipada is true
								ORDER BY tipo_pedido ";
						$res = pg_query($con,$sql);

						for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
							$selected = $tipo_pedido == pg_fetch_result($res,$i,tipo_pedido) ? " selected " : "";
							echo "<option value='" . pg_fetch_result($res,$i,tipo_pedido) . "' $selected>" . pg_result ($res,$i,descricao). "</option>";
						}
					}
					echo "</select>";
				}
			?>
			</td>
			<td style=' text-align: left'>
				<?fecho('condicao.de.pagamento', $con);?><br>
				<input type='hidden' id='validacondicao' name='validacondicao' value=''>
				<input type='hidden' id='condicaoanterior' name='condicaoanterior' value=''>
				<select size='1' id='condicao' name='condicao' class='frm'  onchange='confirmaCondicao(this.options[this.selectedIndex].text)' style='width: 150px' >
				<?
					 $sql1 = "SELECT  tbl_condicao.*
							FROM    tbl_condicao
							JOIN    tbl_posto_condicao USING (condicao)
							JOIN    tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE   tbl_posto_condicao.posto = $login_posto
							AND     tbl_condicao.fabrica     = $login_fabrica
							AND     tbl_condicao.visivel IS TRUE
							AND     tbl_condicao.descricao ILIKE '%garantia%'
							ORDER BY tbl_condicao.condicao ASC";
							
					$res = pg_query($con,$sql1);

					if (pg_num_rows($res) == 0 ) {
						$sql = "SELECT tbl_condicao.*
								FROM tbl_condicao 
								WHERE tbl_condicao.fabrica = $login_fabrica
								AND tbl_condicao.visivel IS TRUE
								ORDER BY tbl_condicao.condicao ASC";
						$res = pg_query($con,$sql);
					}
					
					if (strlen($cook_pedido)==0)
						echo "<option value='0' selected >- selecione</option>";

					for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
						$selected = $condicao == pg_fetch_result($res,$i,condicao) ? " selected " : "";
						echo "<option value='" . pg_fetch_result($res,$i,condicao) . "' $selected>" . pg_result ($res,$i,descricao). "</option>";
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<?#-------------------- Transportadora -------------------

			#HD 47695 - Para pedidos a serem alterados, nao mostrar a transportadora.
			if ($permite_alteracao != 't'){
				$sql = "SELECT  tbl_transportadora.transportadora        ,
								tbl_transportadora.cnpj                  ,
								tbl_transportadora.nome                  ,
								tbl_transportadora_fabrica.codigo_interno
						FROM    tbl_transportadora
						JOIN    tbl_transportadora_fabrica USING(transportadora)
						JOIN    tbl_fabrica USING(fabrica)
						WHERE   tbl_transportadora_fabrica.fabrica        = $login_fabrica
						AND     tbl_transportadora_fabrica.ativo          = 't'
						AND     tbl_fabrica.pedido_escolhe_transportadora = 't'";
				$res = pg_query($con,$sql);
				//echo $sql;
				if (pg_num_rows($res) > 0) {
				?>
					<p><span class='coluna1'><?echo ucwords(traduz('transportadora', $con));?></span>
					<?
						if (pg_num_rows($res) <= 20) {
							echo "<select name='transportadora' class='frm'>";
							echo "<option selected></option>";
							for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
								echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
								if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
								echo ">";
								echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
								echo "</option>\n";
							}
							echo "</select>";
						}else{
							echo "<input type='hidden' name='transportadora' value='' value='$transportadora'>";
							echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";

							echo "<input type='text' name='transportadora_codigo' size='5' maxlength='10' value='$transportadora_codigo' class='textbox' onblur='lupa_transportadora_codigo.click()'>&nbsp;<img id='lupa_transportadora_codigo' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

							echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
						}
						?>
					</p>
			<? }
			}?>
			<!-- <td style='padding-left: 50px; text-align: left'>
				<label for="codigo_transp"><?fecho('codigo.transportadora', $con)?></label><br>
				<input type='text' name='codigo_transp' id='codigo_transp' value='<? echo $transportadora_codigo; ?>' style='width: 150px' class='frm'>
			</td> -->
			<!-- <td colspan='2' style='text-align: left'>
				<?fecho('descricao.transportadora', $con);?><br>
				<input type='text' name='nome_transp' id='nome_transp' value='<? echo $transportadora_nome;?>' style='width: 385px' class='frm'>
			</td> -->
			<?php
			?>

			<td style='padding-left: 50px; text-align: left'>
				<label for="classe_pedido">Classe de Pedido</label><br>             
				<select id="classe_pedido" name="classe_pedido" style='width: 150px' class='frm' onchange='confirmaClassePedido(this.options[this.selectedIndex])'>
					<option value=""></option>
			<?php
					if(!empty($marca_empresa)) {
						$sql = "SELECT  * FROM tbl_classe_pedido 
								WHERE ativo is true 
								AND marca = {$marca_empresa}
								ORDER BY prioridade";
						$res = pg_query($con,$sql);
						
						//echo $sql;
						if (pg_num_rows($res) > 0) {
							$aparece_prazo = false;
							for($i=0;$i < pg_num_rows($res);$i++){

								$codigo_classe_id_aux = pg_result($res,$i,classe_pedido);
								$codigo_classe = pg_result($res,$i,codigo_classe);                      
								$classe = pg_result($res,$i,classe);
								$mensagem = pg_result($res,$i,mensagem);
								$prazo = pg_result($res,$i,prazo);                      
								if($prazo > 0){
									$prazo = true;
								}else{                          
									$prazo = false;
								}   
								
								$selected_classe = "";

								if((strlen($classe_pedido_codigo) > 0 || strlen(trim($classe_atual)) > 0) && ($classe_pedido_codigo == $codigo_classe_id_aux || $classe_atual == $codigo_classe_id_aux)){                                           

									if($prazo == true){
										$aparece_prazo = true;
									}                           
									$classe_pedido_mensagem = $mensagem;                                                                                    
									$classe_atual = $codigo_classe;
									$selected_classe = "SELECTED";
								}                          
								
								echo "<option $selected_classe value='".$codigo_classe."' mensagem='".$mensagem."' prazo='".$prazo."'>".$classe."</option>";
													   
							}
							
						}
					}
			?>                                  
				</select>
				<input type="hidden" id="classe_anterior" value="<?php echo $classe_atual ?>"/>
			</td>
			
			<td colspan='2' style='text-align: left'>
				<label for="classe_pedido">Mensagem</label><br>
				<input type='text' name='mensagem_classe' id='mensagem_classe' value='<?php echo $classe_pedido_mensagem; ?>' readonly style='width: 388px' class='frm'>
			</td>
		</tr>
		<?php
		if($tipo_posto == "REVENDA"){
			$sql = "SELECT tbl_posto_fabrica.posto, 
					tbl_posto_fabrica.codigo_posto, 
					tbl_posto_fabrica.nome_fantasia,
					tbl_posto.nome,
					tbl_posto.cidade,
					tbl_posto.estado
				FROM tbl_posto_fabrica
					INNER JOIN tbl_posto USING(posto)
					INNER JOIN tbl_posto_filial ON tbl_posto_filial.posto = $login_posto
						AND tbl_posto_filial.fabrica = $login_fabrica
						AND tbl_posto_filial.filial_posto = tbl_posto_fabrica.posto
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}";
			$resPostoFilial = pg_query($con,$sql);

			if(pg_num_rows($resPostoFilial) > 0){
				if(strlen($cook_pedido) > 0){
					$sqlPedido = "SELECT filial_posto FROM tbl_pedido WHERE pedido = $cook_pedido AND fabrica = $login_fabrica AND posto = $login_posto";
					$resPedido = pg_query($con,$sqlPedido);

					if(pg_num_rows($resPedido) > 0){
						$posto_filial = pg_fetch_result($resPedido, 0, "filial_posto");
					}
				}
				?>
				<tr>
					<td style="text-align: left"></td>
					<td colspan="2" style="text-align: left">
						<label for="classe_pedido">Revenda Filial</label><br>
						<select name="posto_filial" id="posto_filial" class="frm" style="width:390px;">
							<option value="">Selecione um Filial</option>
							<?php
								while($objeto_posto_filial = pg_fetch_object($resPostoFilial)){
									if($posto_filial == $objeto_posto_filial->posto){
										$selected = "selected='selected'";
									}else{
										$selected = "";
									}

									if(empty($objeto_posto_filial->nome_fantasia) || strlen($objeto_posto_filial->nome_fantasia)){
										$objeto_posto_filial->nome_fantasia = $objeto_posto_filial->nome;
									}
									?>
									<option value="<?=$objeto_posto_filial->posto?>" <?=$selected?>>
										<?php
										echo $objeto_posto_filial->codigo_posto . ' - ';
										echo $objeto_posto_filial->cidade . ', ' . $objeto_posto_filial->estado . ' - ';
										echo $objeto_posto_filial->nome_fantasia;
										?>
									</option>
									<?php
								}

							?>
						</select>
					</td>
				</tr>
				<?php
			}
		}
		?>
		<tr>
			<!-- <td style='padding-left: 50px'>&nbsp;</td>
			<td colspan='2' style='text-align: left;'>
			<input type='checkbox' name='retirada_local' id='retirada_local' value='1' id='retirada_local' <?php
				if ($transportadora_codigo == 0 AND strlen ($cook_pedido) > 0) echo "checked";
					?>><?fecho('clique.aqui.se.for.retirar.a.mercadoria.na.%', $con, $cook_idioma, 'Jacto'); // Fixo, por enquanto ?>
			</td> -->
			<?php 

			if($aparece_prazo == true){                             
				$style = "";        
			}else{              
				$classe_pedido_prazo = "";
				$style = ';display: none';
			}
			?>
			<td style='padding-left: 50px; text-align: left; <?php echo $style; ?>' id="data_esperada_linha" >
				<label for="data_esperada">Data Esperada</label><br>
				<input size="13" maxlength="10" style="text-align: center" type="text" name="data_esperada" id="data_esperada" class='frm' value="<?php echo $classe_pedido_prazo ?>" onclick="if (this.value == 'dd/mm/aaaa') { this.value=''; }">
			</td>
		</tr>
		<tr>
			<td colspan='3' style='text-align: center; padding: 10px 0;'>
				<input style='width: 180px; margin: 0 12px;cursor: pointer' class="btn-importa-excel" type='button' value='<?=ucwords(traduz('importa.pecas.do.excel', $con))?>' />     
				<input style='width: 180px; margin: 0 12px;cursor: pointer' type='button' value=' <?=ucwords(traduz('catalogo.de.pecas', $con))?> ' onclick='geraToken();' />
				<input style='width: 180px; margin: 0 12px;cursor: pointer' type='button' value='<?=ucfirst(traduz('gravar', $con))?>' onclick="if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; alert(traducao.apos_inserir_clique_finalizar); document.frm_pedido.submit() } else { alert (traducao.aguarde_submissao) }" border='0' />
				<?php
					$sqlCnpj = "SELECT cnpj FROM tbl_posto WHERE posto = $login_posto";
					$resCnpj = pg_query($con,$sqlCnpj);
					echo "<input type='hidden' name='cnpj_posto' value='".pg_fetch_result($resCnpj, 0, 'cnpj')."'>"
				?>
				<input type='hidden' name='token_pedido' value=''>
			</td>
		</tr>
		<tr>
			<td width='270'>&nbsp;</td>
			<td width='233'>&nbsp;</td>
			<td width='*'>&nbsp;</td>
		</tr>
		<tr class='subtitulo'>
			<td align="center"  colspan='3'> <?=traduz('pecas', $con)?> </td>
		</tr>
	</table>


		<?#-------------------- Linha do pedido -------------------

		#HD 47695 - Para alterar o pedido, mas nao a linha, por causa da tabela de preço
		if ($permite_alteracao == 't' and strlen($linha)>0){
			?><input type="hidden" name="linha" value="<? echo $linha; ?>"><?
		}else{
			$sql = "SELECT  tbl_linha.linha            ,
							tbl_linha.nome
					FROM    tbl_linha
					JOIN    tbl_fabrica USING(fabrica)
					JOIN    tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
											AND tbl_posto_linha.linha = tbl_linha.linha
					WHERE   tbl_fabrica.linha_pedido is true
					AND     tbl_linha.fabrica = $login_fabrica ";

			#permite_alteracao - HD 47695
			if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't' and strlen($linha)>0){
				$sql .= " AND tbl_linha.linha = $linha ";
			}
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
			?>
				<p><span class='coluna1'><?=traduz('linha', $con)?></span>
						<?
						echo "<select name='linha' class='frm' ";
						echo ">";
						echo "<option></option>";
						for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
							echo "<option value='".pg_fetch_result($res,$i,linha)."' ";
							if ($linha == pg_fetch_result($res,$i,linha) ) echo " selected";
							echo ">";
							echo pg_fetch_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
						?>
				</p>
			<?
			}
		}
		?>
		<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">

		<table border="0" width='700' cellspacing="1" cellpadding="0" align="center" class='formulario'  name="tabela_itens" id="tabela_itens">
			<thead>
				<tr height="20" class='titulo_coluna' nowrap>
					<th align='left' colspan='2' style='padding-left: 15px'><?=traduz('referencia', $con)?></th>
					<th align='left'><?=traduz('descricao.componente', $con)?></th>
					<th align='center'><?=traduz('qtde', $con)?></th>
					<th align='center'><?=traduz(array('preço', 'sem.ipi'), $con)?></th>
					<th align='center'>% IPI</th>
					<th align='center'><?=traduz(array('preço', 'com.ipi'), $con)?></th>
					<th align='center'><?=traduz('total', $con)?></th>
				</tr>
				<tr class="msg_erro_peca" hidden>
					<td colspan="8" style="background-color: red;color: white;font-weight: bolder;font-size: 15px;"></td>
				</tr>
			</thead>
			<tbody>
				<tr id="modelo" rel="__modelo__" style="visibility:hidden;">
					<td style='width: 10px'>
						<a href='javascript:void(0);' onclick="apagaLinha(__modelo__);";><img src='imagens/icone_deletar.png' alt='Excluir Item' width='10' style='padding: 0; margin: 0;' /></a>
					</td>
					<td align='left'>
						<input class="frm" type="text" name="peca_referencia___modelo__" id="peca_referencia___modelo__" value="" style='width: 80px'  onblur="pesquisaPeca(this, __modelo__);" onkeyup="pulaCampo('referencia',__modelo__,event);" rel='__modelo__'>
						<input type="hidden" name="posicao">
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="buscaPeca(document.frm_pedido.peca_referencia___modelo__,__modelo__,'referencia');" style='cursor: pointer' width='16px' />
					</td>
					<td align='left'>
						<input class="frm" type="text" id="peca_descricao___modelo__" name="peca_descricao___modelo__" value="" style='width: 220px'  onkeyup="pulaCampo('descricao',__modelo__,event);" rel='__modelo__'>
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="buscaPeca(document.frm_pedido.peca_descricao___modelo__,__modelo__,'descricao');" style='cursor: pointer' width='16px' />
					</td>
					<td align='center'>
						<?php
							if ($i > 37) {
								$comando = "adicionarLinha(__modelo__-1);";
							}
						?>
						<input class="frm numeric" type="text" name="qtde___modelo__" id="qtde___modelo__" style='width: 30px'  maxlength='5' value=""
						<? echo "onblur='verificaMultiplo(__modelo__); fnc_jacto_preco (__modelo__); $comando'"; ?> onkeyup="pulaCampo('quantidade',__modelo__,event);">
						<input class="frm" type="hidden" name="multiplo___modelo__" id="multiplo___modelo__" value="">
					</td>
					
					<td align='center'>
						<input class="frm" id="preco___modelo__" type="text" name="preco___modelo__" style='width: 60px'   value="" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(__modelo__);">
					</td>

					<td align='center'>
						<input class="frm" id="ipi___modelo__" type="text" name="ipi___modelo__" style='width: 60px'   value="" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(__modelo__);">
					</td>
					

					<td align='center'>
						<input class="frm" id="preco_ipi___modelo__" type="text" name="preco_ipi___modelo__" style='width: 60px'  value="" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(__modelo__);">
					</td>
					
					<td align='center'>
						<input class="frm" name="sub_total___modelo__" id="sub_total___modelo__" type="text" style='width: 60px'  rel='total_pecas' readonly  style='text-align:right; color:#000;' value='' onfocus="ignoraCampo(__modelo__);">
					</td>
					<td align='center' hidden>
						<input class="frm" name="marca___modelo__" id="marca___modelo__" type="text" style='width: 60px'  rel='marca' readonly  style='text-align:right; color:#000;' value='' />
					</td>
				</tr>
				<tr id='modelo_mudou' style="visibility:hidden;">
					<td colspan='8'><div id='mudou___modelo__' style='display: none; text-align: left; padding: 2px 10px' ></div></td>
				</tr>
			<?
			$total_geral = 0;

			echo "<input type='hidden' name='qtde_item' value='$qtde_item' id='qtde_item'>";
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				
					/*
						Esse script inserido trabalhalha com os campos das peças, ele apaga todos os campos 
						quando a descrição não está inserida, não deixa multiplicar a quantidade por preço caso a quantidade não seja
						digitada e limpa todos os campos caso seja apagada a descrição da peça.
					*/      
					echo "<script>
								$( document ).ready( function(){
									
									$('#total_pecas').each(function() {
										var total_pecas = $('#total_pecas').val();
										total_pecas = total_pecas.replace('.' ,','); 
										$('#total_pecas').val(total_pecas);
									
									});

									$('#qtde_$i').numeric();
										$( '#qtde_$i' ).blur( function(e){
											if( $( '#qtde_$i' ).val() == '' || $( '#qtde_$i' ).val() == null || $( '#qtde_$i' ).val() == 0 )
											{
												if( $( '#peca_referencia_$i' ).val() != '' && $( '#peca_referencia_$i' ).val() != null  && e.which  != 8 && e.which != 46 )
												{   
													$( '#qtde_$i' ).val( 1 );
													
												}
											}
										} );

								$( '#peca_referencia_$i' ).blur( function(){
								
									if( $( '#peca_referencia_$i' ).val() == '' )    {
										
										$( '#qtde_$i' ).val( '' );
										$( '#preco_$i' ).val( '' ) ;
										fnc_calcula_total($i);
										$( '#sub_total_$i' ).val( '' );
										$( '#produto_referencia_$i' ).val( '' );
										$( '#peca_referencia_$i' ).val( '' );
									}
								} )

							} );
					 </script>";
				
				
				if (strlen($pedido) > 0){   // AND strlen ($msg_erro) == 0
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_peca.referencia        ,
									tbl_peca.marca             ,
									tbl_peca.descricao         ,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco      ,
									tbl_pedido_item.ipi
							FROM  tbl_pedido
							JOIN  tbl_pedido_item USING (pedido)
							JOIN  tbl_peca        USING (peca)
							WHERE tbl_pedido_item.pedido = $pedido
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido_item.pedido_item";

					$res = pg_query($con,$sql);

					if (pg_num_rows($res) > 0) {
						$pedido_item     = trim(@pg_fetch_result($res,$i,pedido_item));
						$peca_referencia = trim(@pg_fetch_result($res,$i,referencia));
						$peca_descricao  = trim(@pg_fetch_result($res,$i,descricao));
						$qtde            = trim(@pg_fetch_result($res,$i,qtde));
						$preco           = trim(@pg_fetch_result($res,$i,preco));
						$ipi             = trim(@pg_fetch_result($res,$i,ipi));
						$marca_peca      = trim(@pg_fetch_result($res,$i,marca));

						if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');

						$produto_referencia = '';
						$produto_descricao  = '';
					}else{
						$produto_referencia = $_POST["produto_referencia_" . $i];
						$produto_descricao  = $_POST["produto_descricao_"  . $i];
						$pedido_item        = $_POST["pedido_item_"        . $i];
						$peca_referencia    = $_POST["peca_referencia_"    . $i];
						$peca_descricao     = $_POST["peca_descricao_"     . $i];
						$qtde               = $_POST["qtde_"               . $i];
						$preco              = $_POST["preco_"              . $i];
						$ipi                = $_POST["ipi_"                . $i];
						$preco_ipi          = $_POST["preco_ipi_"          . $i];
						$marca_peca         = $_POST["marca_"              . $i];
					}
				}else{
					$produto_referencia = $_POST["produto_referencia_" . $i];
					$produto_descricao  = $_POST["produto_descricao_"  . $i];
					$pedido_item        = $_POST["pedido_item_"        . $i];
					$peca_referencia    = $_POST["peca_referencia_"    . $i];
					$peca_descricao     = $_POST["peca_descricao_"     . $i];
					$qtde               = $_POST["qtde_"               . $i];
					$preco              = $_POST["preco_"              . $i];
					$ipi                = $_POST["ipi_"                . $i];
					$preco_ipi          = $_POST["preco_ipi_"          . $i];
					$marca_peca         = $_POST["marca_"              . $i];
				}

				$peca_referencia = trim ($peca_referencia);

				#--------------- Valida Peças em DE-PARA -----------------#
				$tem_obs = false;
				$linha_obs = "";

				$sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
				$resX = pg_query($con,$sql);

				if (pg_num_rows($resX) > 0) {
					$linha_obs = traduz('peca.original.%.mudou.para.o.codigo.acima', $con, $cook_idioma, (array) $peca_referencia);
					$peca_referencia = pg_fetch_result($resX,0,0);
					$tem_obs = true;
				}

				#--------------- Valida Peças Fora de Linha -----------------#
				$sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";

				$resX = pg_query($con,$sql);
				if (pg_num_rows($resX) > 0) {
					$libera_garantia = pg_fetch_result($resX,0,libera_garantia);
					$linha_obs .= traduz('peca.acima.esta.fora.de.linha', $con);
					$tem_obs = true;
				}

				if (strlen ($peca_referencia) > 0) {
					$sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
					$resX = pg_query($con,$sql);
					if (pg_num_rows($resX) > 0) {
						$peca_descricao = pg_fetch_result($resX,0,0);
					}
				}

				$peca_descricao = trim ($peca_descricao);

				$cor="";
				//if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				//if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				//if ($tem_obs) $cor='#FFCC33';

				$tabindex = $i + 1;
			?>
				<tr name="peca" rel="<?=$i?>" bgcolor="<? echo $cor ?>" nowrap>
					<td style='width: 10px'>
						<a href='javascript:void(0);' onclick="apagaLinha(<?=$i?>);";><img src='imagens/icone_deletar.png' alt='Excluir Item' width='10' style='padding: 0; margin: 0;' /></a>
					</td>
					<td align='left'>
						<input class="frm xtreferencia" type="text" name="peca_referencia_<?=$i?>" id="peca_referencia_<?=$i?>" value="<? echo $peca_referencia; ?>" style='width: 80px'  tabindex='<?php echo $tabindex;?>' onblur="pesquisaPeca(this, <?php echo $i?>);" onkeyup="pulaCampo('referencia',<?php echo $i?>,event);" rel='<?php echo $i?>'>
						<input type="hidden" name="posicao">
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="buscaPeca(document.frm_pedido.peca_referencia_<?php echo $i?>,<?php echo $i?>,'referencia');" style='cursor: pointer' width='16px' />
					</td>
					<td align='left'>
						<input class="frm" type="text" id="peca_descricao_<? echo $i ?>" name="peca_descricao_<? echo $i ?>" value="<? echo $peca_descricao ?>" style='width: 220px'  tabindex='<?php echo $tabindex;?>' onkeyup="pulaCampo('descricao',<?php echo $i?>,event);" rel='<?php echo $i?>'>
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="buscaPeca(document.frm_pedido.peca_descricao_<?php echo $i?>,<?php echo $i?>,'descricao');" style='cursor: pointer' width='16px' />
					</td>
					<td align='center'>
						<?php
							if ($i > 37) {
								$comando = "adicionarLinha($i-1);";
							}
						?>
						<input class="frm numeric" type="text" name="qtde_<? echo $i ?>" id="qtde_<? echo $i ?>" style='width: 30px'  maxlength='5' value="<? echo $qtde ?>"
						<? echo "onblur='verificaMultiplo($i);  fnc_jacto_preco ($i); $comando'"; ?> tabindex='<?php echo $tabindex;?>' onkeyup="pulaCampo('quantidade',<?php echo $i?>,event);">
						<input class="frm" type="hidden" name="multiplo_<? echo $i ?>" id="multiplo_<? echo $i ?>" value="">
					</td>
					
					<td align='center'>
						<input class="frm" id="preco_<? echo $i ?>" type="text" name="preco_<? echo $i ?>" style='width: 60px'   value="<? echo $preco ?>" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(<?php echo $i?>);">
					</td>

					<td align='center'>
						<input class="frm" id="ipi_<? echo $i ?>" type="text" name="ipi_<? echo $i ?>" style='width: 60px'   value="<? echo $ipi ?>" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(<?php echo $i?>);">
					</td>
					

					<td align='center'>
						<input class="frm" id="preco_ipi_<? echo $i ?>" type="text" name="preco_ipi_<? echo $i ?>" style='width: 60px'  value="<? echo $preco_ipi ?>" readonly  style='text-align:right; color:#000;' onfocus="ignoraCampo(<?php echo $i?>);">
					</td>
					
					<td align='center'>
						<input class="frm" name="sub_total_<? echo $i ?>" id="sub_total_<? echo $i ?>" type="text" style='width: 60px'  readonly  style='text-align:right; color:#000;' value='<?
							if ($qtde &&  $preco_ipi) { 
								if( $preco_ipi == '' || $preco_ipi == 0 || $preco_ipi == null ){
									$preco_ipi = 1;
								}
								$preco_ipi = str_replace(',', '.', str_replace('.', '', $preco_ipi));
								$total_geral += $preco_ipi * $qtde; 
								
								$preco_ipi = $preco_ipi * $qtde;
								$preco_ipi = number_format($preco_ipi,2,',','.');
								echo $preco_ipi;
							}
							?>' onfocus="ignoraCampo(<?php echo $i?>);">
							<?php ?>
					</td>
					<td align='center' hidden>
						<input class="frm" id="marca_<? echo $i ?>" type="text" name="marca_<? echo $i ?>" style='width: 60px'  value="<? echo $marca_peca ?>" readonly  style='text-align:right; color:#000;' />
					</td>

				</tr>

				<?
				if ($tem_obs) {
					echo "<tr bgcolor='$cor' style='font-size:12px'>";
						echo "<td colspan='8'>$linha_obs</td>";
					echo "</tr>";
				}
				echo "<tr>";
					echo "<td colspan='8'><div id='mudou_$i' style='display: none; text-align: left; padding: 2px 10px' ></div><div class='msgDuplicidade_$i'></div></td>";
				echo "</tr>";
			}
			?>
			</table>
			<?
				echo "<table border='0' cellspacing='0' cellpadding='2' align='center' class='xTabela formulario' width='700px'>";
				echo "<tr style='font-size:12px' align='right'>";
				echo "<td colspan='7' allign='right'><b>Total</b>: <INPUT TYPE='text' size='10' style='text-align:right;' class='frm' id='total_pecas'";
					if(strlen($total_geral) > 0){
						$total_geral = number_format($total_geral,2,',','.');
						echo " value='$total_geral'";
					} 
				echo "></td>";
				echo "</tr>";
				echo "</table>";
			?>

<p class='formulario' style='padding: 20px; text-align: center'>
		<input type='button' id='acerta_quantidade_todas' value='<?=traduz('acertar.quantidade.de.todas.as.pecas', $con)?>' onclick="acertaQuantidadeTodas();" border='0' style='cursor: pointer; display: none' />
			<input type="hidden" name="btn_acao" value="">
				<input type='button' value='<?=traduz('gravar', $con)?>' onclick="if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; alert(traducao.apos_inserir_clique_finalizar); document.frm_pedido.submit() } else { alert (traducao.aguarde_submissao) } " border='0' style='cursor: pointer' />
		</p>

		</li>
		</ul>
</form>

<div id='divAguarde' style='position:absolute; display:none; top:500px; left:350px; background-color: #99CCFF; width: 300px; height:100px;'>
	<center>
		<?=ucfirst(traduz(array('aguarde', 'carregando', 'sep'=>', '), $con))?><br>
		<img src='imagens/ajax-azul.gif'>
	</center>
</div>

<?php 
	
	if($dados_array > 0){
		?>
		<script type='text/javascript'>
			var retorno_pecas = new Array();
			var contador = 0;
		</script>
		<?
		for( $i = 0; $i < $dados_array ; $i++ ){
			$importa_dados = $pedido_session[$i]['referencia']."|".$pedido_session[$i]['qtd'];//Array();
			?>
			<script type='text/javascript'>
				retorno_pecas[contador] = '<?php echo $importa_dados;?>';
				contador ++;
			</script>
			<?
		}
		?>
		
		<?
		@session_destroy();
	}
	?>
		<script type='text/javascript'>
			function verificaToken(token){
				$.ajax({
					url: "pedido_jacto_cadastro.php",
					type: "POST",
					data: "verificaToken=sim&token="+token,
					success : function(retorno){
						var objRetorno = eval("(function(){return " + retorno + ";})()");

						if (objRetorno.status == "true") {
							importaCatalogo(objRetorno.result);
						};
						
					}
				});
			}           
		</script>
	<?
	$pedido = $cook_pedido;
	if (strlen ($cook_pedido) > 0 ) {
		$sql = "SELECT a.* ,
				referencia,
				descricao,
				tbl_peca.ipi AS ipi_peca,
				round((a.preco+(a.preco*tbl_peca.ipi/100))::numeric,2) as preco_com_ipi
				FROM tbl_peca
				JOIN (
				SELECT 
					tbl_pedido_item.pedido_item,
					tbl_pedido_item.preco,
					tbl_pedido_item.qtde,
					tbl_pedido_item.peca
				FROM tbl_pedido_item
				JOIN tbl_pedido USING(pedido)
				WHERE pedido = $pedido
				AND fabrica = $login_fabrica
				)
				a ON tbl_peca.peca = a.peca
				ORDER BY a.pedido_item";

	  //echo nl2br($sql); die;
		$res = @pg_query ($con,$sql);
		$total = 0;
		if( @pg_num_rows( $res ) > 0 )
		{
?>
</form>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center" class='texto_avulso'>
	<tr>
		<td align="center">
			<p><?=traduz('instrucoes.pedido.1', $con)?></p>
			<p><?=traduz('instrucoes.pedido.2', $con)?></p>
		</td>
	</tr>
</table>

<br>
<table width="700" border="0" cellpadding="3" class='tabela' cellspacing="1" align="center" id='resumo_pedido'>
	<thead>
		<tr>
			<th colspan="8" align="center" class='titulo_tabela'><?=traduz('resumo.do.pedido', $con)?></th>
		</tr>
		<tr class='titulo_coluna'>
			<th width="25%" align='center'><?=traduz('referencia', $con)?></th>
			<th width="40%" align='center'><?=traduz('descricao', $con)?></th>
			<th width="15%" align='center'><?=traduz('quantidade', $con)?></th>
			<th width="10%" align='center'><?=traduz(array('preco','sem.ipi'), $con)?></th>
			<th width="10%" align='center'>% IPI</th>
			<th width="10%" align='center'><?=traduz(array('preco','com.ipi'), $con)?></th>
			<th width="10%" align='center'><?=traduz(array('total','item'), $con)?></th>
			<th width="10%" align='center'><?=traduz('acao', $con)?></th>
		</tr>
	</thead>

<?php
	for ($i = 0 ; $i < @pg_num_rows ($res) ; $i++) {
		$pedido_item = pg_fetch_result ($res,$i,pedido_item);
		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor' id='$pedido_item'>";
		
		echo "<td width='25%' >";
			echo pg_fetch_result ($res,$i,referencia);
		echo "</td>";

		echo "<td width='50%' align='left'>";
			echo pg_fetch_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='15%' align='center'>";
			echo $qtde = pg_fetch_result ($res,$i,qtde);
		echo "</td>";

		echo "<td width='10%' align='right'>";
			$preco = number_format (pg_fetch_result ($res,$i,preco),2,",",".");
			//$preco = str_replace('.',',',$preco);
		echo $preco;
		echo "</td>";
		
		echo "<td width='10%' align='center'>";
			echo pg_fetch_result ($res,$i,ipi_peca);
		echo "</td>";

		echo "<td width='10%' align='right'>";
			$preco_com_ipi = pg_fetch_result ($res,$i,preco_com_ipi);
			echo number_format ($preco_com_ipi,2,",",".");
		echo "</td>";
		
		echo "<td width='10%' align='right' class='TotalAjax'>";
			$total_item = $preco_com_ipi*$qtde;
			echo number_format ($total_item ,2,",",".");
		echo "</td>";
		
		echo "<td width='10%' align='center' nowrap>";
			echo "<input type='button' value='" . traduz('excluir', $con) . "' onclick=\"apagaItemPedido($pedido,$pedido_item);\" />";
		echo "</td>";

		echo "</tr>";
		
		$total = $total + ($preco_com_ipi * pg_fetch_result($res,$i,qtde));
	}
?>
	<tr>
		<td align="center" colspan="6">
			T O T A L
		</td>
		<td align='right' style='text-align:right'>
			<b id='printTotalAjax'>
			<?php 
				$total = number_format ($total,2,",",".");
				//$total = str_replace('.',',',$total);
				echo $total;
			?>
			</b>
		</td>
	</tr>
</table>
<?php
}
?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class='formulario' id='btn_resumo_pedido'>
	<tr>
		<td align='center'>
			<br><input type="button" value="<?=traduz('finalizar', $con)?>" onclick="window.location.href=window.location.pathname+'?btn_acao=Finalizar'"><br><br>
		</td>
	</tr>
</table>

<?
}
?>

<script type="text/javascript">
	/*$(document).ready(function() {
		$("select[name=marca]").trigger("change")
	})*/
</script>

<? include "rodape.php"; ?>
