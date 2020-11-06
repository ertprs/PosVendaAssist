<?php
$admin_es = (preg_match("/\/admin_es\//", $_SERVER["PHP_SELF"]) ? true : false);
$admin_et = (preg_match("/\/admin_es\//", $_SERVER["PHP_SELF"]) ? true : false);
$admin = (preg_match("/\/admin\//", $_SERVER["PHP_SELF"]) ? true : false);

if ($admin_es) {
	include '../admin/dbconfig.php';
	include '../admin/includes/dbconnect-inc.php';
	include "../class/email/PHPMailer/PHPMailerAutoload.php";
	$admin_privilegios="info_tecnica";
	include 'autentica_admin.php';
} else if ($admin) {
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "../class/email/PHPMailer/PHPMailerAutoload.php";
	$admin_privilegios = "info_tecnica";
	include 'autentica_admin.php';
} else {
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "class/email/PHPMailer/PHPMailerAutoload.php";
	$admin_privilegios = "info_tecnica";
	include 'autentica_usuario.php';
	$posto = true;
}

include_once '../fn_traducao.php';
include '../helpdesk/mlg_funciones.php';

$mailer = new PHPMailer();

$msg_erro = array();
$msg = "";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

$array_estado = array(
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AM' => 'Amazonas',
    'AP' => 'Amapá',
    'BA' => 'Bahia',
    'CE' => 'Ceara',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MG' => 'Minas Gerais',
    'MS' => 'Mato Grosso do Sul',
    'MT' => 'Mato Grosso',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí­',
    'PR' => 'Paraná',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'RS' => 'Rio Grande do Sul',
    'SC' => 'Santa Catarina',
    'SE' => 'Sergipe',
    'SP' => 'São Paulo',
    'TO' => 'Tocantins'
);


if($login_fabrica == 1){
	$tela_video_explicativo_display  = "  display: none ";
}

// ##
$ajaxAdicionarComunicado = filter_input(INPUT_POST, 'ajax_adicionar_comunicado');
if( $ajax_adicionar_comunicado ){
        $requestData = $_POST;

        $response = [];
        // Verifica se foi passado o link da tela
        if( strlen($requestData['link_tela']) <= 0 ){
                $response['error'] = true;
                $response['message'] = "Preencha todos os campos";
                $response['inputs'][] = "link_tela";
        }

        foreach ($requestData['data'] as $formVideo) {
                // Verifica se os campos foram preenchidos corretamente
                foreach($formVideo as $inputName => $inputValue){
                        if( strlen($inputValue) <= 0 ){
                                $response['error'] = true;
                                $response['message'] = "Preencha todos os campos";
                                $response['inputs'][] = $inputName;
                        }
                }
        }

        if( $response['error'] == true ){
                echo json_encode($response);
                exit();
        }

		pg_query($con, "BEGIN TRANSACTION");
        foreach ($requestData['data'] as $formVideo) {
                // Ordena o array do menor para o maior para facilitar na hora de buscar o valor, visto que os indices sempre vão mudar
                // Conforme o numero de vídeos adicionados
                krsort($formVideo);

                // Depois de orderado é extraido apenas os valores, criando assim um array nao associativo com os valores
                // Podendo ser acessado pela posição
                $formVideo = array_values($formVideo);

                $tituloVideo = utf8_decode( trim( $formVideo[0] ));
                $statusVideo = trim( $formVideo[1] );
                $linkVideo   = trim( $formVideo[2] );

                $sql = "INSERT INTO tbl_comunicado (tipo, fabrica, ativo, descricao, video, programa) 
                                VALUES ( 'video_explicativos', {$login_fabrica}, '{$statusVideo}', '{$tituloVideo}', '{$linkVideo}', '{$requestData['link_tela']}' )";

                pg_query($con, $sql);
                $pg_error = pg_last_error();
        }

        if( strlen($pg_error) > 0 ){
                pg_query('ROLLBACK TRANSACTION');

                $response['error'] = true;
                $response['message'] = $pg_error;

                echo json_encode($response);
                exit();
        }else{
                pg_query('COMMIT TRANSACTION');
        }

        $response['error'] = false;
        $response['message'] = 'OK';

        echo json_encode($response);
        exit();
}

if (isset($_GET["q"])) {
    $busca      = $_GET["busca"];
    $tipo_busca = $_GET["tipo_busca"];

    if (strlen($q)>2) {

        if ($tipo_busca=="posto") {
            $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                      FROM tbl_posto
                      JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                     WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
            if ($busca == "codigo"){
                $sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
            }else{
                $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
            }

            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $cnpj         = trim(pg_fetch_result($res,$i,'cnpj'));
                    $nome         = trim(pg_fetch_result($res,$i,'nome'));
                    $codigo_posto = trim(pg_fetch_result($res,$i,'codigo_posto'));
                    echo "$cnpj|$nome|$codigo_posto";
                    echo "\n";
                }
            }
        }
        if ($tipo_busca=="produto"){
            if ($login_fabrica == 96) {
                $q = preg_replace('/\W/', '', trim(strtoupper($q)));
            }

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


            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $produto    = trim(pg_fetch_result($res,$i,'produto'));
                    $referencia = trim(pg_fetch_result($res,$i,'referencia'));
                    $descricao  = trim(pg_fetch_result($res,$i,'descricao'));
                    echo "$produto|$descricao|$referencia";
                    echo "\n";
                }
            }
        }
        if ($tipo_busca=="peca") {
            $sql = "SELECT tbl_peca.peca,
                            tbl_peca.referencia,
                            tbl_peca.descricao
                    FROM tbl_peca
                    WHERE tbl_peca.fabrica = $login_fabrica ";

            if ($busca == "codigo"){
                $sql .= " AND UPPER(tbl_peca.referencia) like UPPER('%$q%') ";
            }else{
                $sql .= " AND UPPER(tbl_peca.descricao) like UPPER('%$q%') ";
            }

            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $peca       = trim(pg_fetch_result($res,$i,'peca'));
                    $referencia = trim(pg_fetch_result($res,$i,'referencia'));
                    $descricao  = trim(pg_fetch_result($res,$i,'descricao'));
                    echo "$peca|$descricao|$referencia";
                    echo "\n";
                }
            }
        }
    }
    exit;
}

//HD 307110 - Gabriel Silveira - ajax para quando for fazer o submit de um comunicado do tipo 'Extrato', verificar se ja existe um comunicado ativo deste mesmo tipo e avisar ao admin caso exista.
if (isset($_GET['ajax']) and $_GET['ajax'] == 'ver_comunicado_extrato') {

	$sql = "SELECT comunicado
			  FROM tbl_comunicado
			 WHERE fabrica = $login_fabrica
			   AND tipo = 'Extrato'
			   AND posto IS NULL
			   AND ativo IS TRUE";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0) {
		echo "1";
	}else{
		echo "0";
	}

	exit;
}
if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3 = new anexaS3('co', (int) $login_fabrica, $comunicado);
	$S3_online = is_object($s3);
}


if (trim($_POST['comunicado']) > 0) $comunicado = trim($_POST['comunicado']);
if (trim($_GET['comunicado']) > 0)  $comunicado = trim($_GET['comunicado']);

$btn_acao = trim (strtolower ($_REQUEST['btn_acao']));

$ativo = trim($_POST['ativo']);

if (trim($btn_acao) == "gravar") {

	if (in_array($login_fabrica, [35]) && is_array($_POST['codigo_posto'])) {
		include '../class/communicator.class.php';
        $array_os  = $_POST["os_posto"];
		foreach ($_POST['codigo_posto'] as $posto_codigo) {
			
			$mailTc = new TcComm($externalId);
			$mailTc->setEmailSubject($_POST['descricao']);
			if ($posto_codigo == null || $posto_codigo == '') {
				continue;
			}
			$mensagem = $_POST['mensagem'];
        	$mensagem .= "</br>OSs:" . $array_os["'$posto_codigo'"] ;
   
			$sql = "SELECT contato_email, posto FROM tbl_posto_fabrica  where codigo_posto = '$posto_codigo'";
            $res = pg_query($con, $sql);
            $contato_email = pg_fetch_result($res, 0, 'contato_email');
            $posto = pg_fetch_result($res, 0, 'posto');
            
        	$mailTc->addToEmailBody($mensagem); 
            $mailTc->setEmailFrom($externalEmail);
            $mailTc->addEmailDest($contato_email);
            if($_serverEnvironment !== "development") {
            	$resultado = $mailTc->sendMail();
            }
            $sqlComunicado = "INSERT INTO tbl_comunicado (
						ativo,
						mensagem,
						descricao,
						tipo,
						fabrica,
						posto
						) VALUES (
						't',
						'$mensagem',
						'Comunicado',
						'Comunicado',
						$login_fabrica,
						$posto
					)";
			$resComunicado = pg_query($con, $sqlComunicado);
		}
	}

	if (strlen($tipo) > 0 or strlen($aux_tipo) > 0){
	    if (in_array($login_fabrica, array(42))) {
			if (strlen($_POST['descricao']) == 0) {
				$msg_erro["msg"][] = "Preencha os campos obrigatórios";
				$msg_erro["campos"][] = "titulo";
			}else{
				if (strlen($_POST['mensagem']) == 0 && strlen($_POST['link']) == 0 && strlen($_FILES['arquivo']['tmp_name']) == 0) {
					$msg_erro["msg"][] = "Preencha pelo menos um dos campos indicados em vermelho";
					$msg_erro["campos"][] = "mensagem";
					$msg_erro["campos"][] = "link";
					$msg_erro["campos"][] = "arquivo";
				}
			}
		}
		// A pedido da Fabíola, não permitir alterar este comunicado de procedimento em garantia
		// Chamado 12535 - 24/01/2008
		if ($login_fabrica == 1 AND $comunicado == "27969" AND $login_admin <> 155){
			$msg_erro["msg"][] = "Este comunicado não pode ser alterado.";
		}

		$peca_referencia        = trim($_POST['peca_referencia']);
		$produto_referencia     = trim($_POST['produto_referencia']);
		$familia                = $_POST['familia'];
		$linha                  = $_POST['linha'];
		$descricao              = trim($_POST['descricao']);
		$descricao              = pg_escape_string($descricao); 
		$extensao               = trim($_POST['extensao']);
		$tipo                   = trim($_POST['tipo']);
		$mensagem               = trim(pg_escape_string($_POST['mensagem']));
       	$video                  = trim($_POST['video']);
		$link					= trim($_POST['link']);
		$duracao				= trim($_POST['duracao']);
		$obrigatorio_os_produto = trim($_POST['obrigatorio_os_produto']);
		$obrigatorio_site       = trim($_POST['obrigatorio_site']);
		$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
		$codigo_posto           = trim($_POST['codigo_posto']);
		$posto_nome             = trim($_POST['posto_nome']);
		$tipo_posto             = trim($_POST['tipo_posto']);
		$remetente_email        = trim($_POST['remetente_email']);
		$estado                 = $_POST['estado'];
		$ativo                  = trim($_POST['ativo']);
		$pais                   = trim($_POST['pais']);
		$categoria_posto        = trim($_POST['categoria_posto']);
		$unidadenegocios        = $_POST['unidadenegocio'];
		$produto_serie 			= trim($_POST['produto_serie']);

		if(strlen(trim($categoria_posto))==0){
			$categoria_posto = null;
		}

		if ($login_fabrica == 175){
			$comunicado_destino = $_POST['comunicado_destino'];
		}

		if($login_fabrica == 42){
			$mural_avisos = $_POST['mural_avisos'];
			$mural_avisos = (strlen($mural_avisos) > 0 && $mural_avisos == "sim") ? "t" : "f";
			$campo_mural_avisos = ", suframa ";
			$valor_mural_avisos = ", '$mural_avisos' ";
			$update_mural_avisos = ", suframa = '$mural_avisos' ";
		}

		if($login_fabrica == 152 AND $tipo == "Contrato" and empty($_POST['hashBox'])){
			if(strlen($comunicado) > 0){
				if ($S3_online and $s3->temAnexos($comunicado)) {
				}else{
					$msg_erro["msg"][] = 'Campo Arquivo é obrigatório.';
				}
			}else{
				if($_FILES['arquivo']['name'] == ''){
					$msg_erro["msg"][] = 'Campo Arquivo é obrigatório.';
				}
			}

		}

		if (strlen($descricao) == 0)  $aux_descricao = "null";
		else                          $aux_descricao = "'". $descricao ."'";

		if (strlen($tipo_posto) == 0 )  $aux_tipo_posto = "null";
		else                            $aux_tipo_posto = "'". $tipo_posto ."'";

		if (strlen($extensao) == 0)   $aux_extensao = "null";
		else                          $aux_extensao = "'". $extensao ."'";

		if (empty($familia))    $aux_familia = "null";
		else {                         
			if(!is_array($familia)) {
				$aux_familia = "'". $familia ."'"; 
			} else {
				$aux_familia = $familia;
			}
		}

		if (strlen(trim($produto_referencia)) > 0){
			$aux_familia = "null";
		}

		if (empty($linha))    $aux_linha = "null";
		else  {                         
			if(!is_array($linha)) {
				 $aux_linha = "'". $linha ."'";
			} else {
					 $aux_linha = $linha;
			}
		}                       

		//ALTERAÇÃO FEITA POR RAPHAEL GIOVANINI PARA PODER ENVIAR CHAMADOS PARA OS POSTOS DO PARANÁ

		if ($login_fabrica == 177){
			$aux_estado = $estado;
		}else{
			if (strlen($estado) == 0)     $aux_estado = "null";
			else                          $aux_estado = "'". $estado ."'";
		}

		if (strlen($FEITA) == 0 && is_array($_POST['estado'])){
			$aux_estado = $_POST['estado'];
		}

		if (strlen($tipo) == 0)       $aux_tipo = "null";
		else                          $aux_tipo = "'". $tipo ."'";

		if (strlen($pais) == 0)       $aux_pais = "'BR'";
		else                          $aux_pais = "'". $pais ."'";

		if (strlen($pais) == 0 && is_array($_POST['pais'])){
			$aux_pais = $_POST['pais'];
		}

		//Quando selecionando o 'Aviso Posto Unico' faz a validacão para saber se entrou com os dados do posto
		if (((strlen($posto_nome) == 0) || (strlen($codigo_posto) == 0)) AND (strlen($tipo == 'Com. Unico Posto')) AND $tipo != "Extrato" AND $tipo != "Comunicado por tela"){
			$msg_erro["msg"][] = 'Inoforme os dados do posto';
			$msg_erro["campos"][] = "posto";
		}
		//Quando selecionando o 'Aviso Posto Unico' faz a validacão para saber se entrou com os dados do posto


		if (strlen($mensagem) == 0)   $aux_mensagem = "null";
		else                          $aux_mensagem = "'". $mensagem ."'";

		if (strlen($obrigatorio_os_produto) == 0) $aux_obrigatorio_os_produto = "'f'";
		else                                      $aux_obrigatorio_os_produto = "'t'";

		if (strlen($obrigatorio_site) == 0)       $aux_obrigatorio_site = "'f'";
		else                                      $aux_obrigatorio_site = "'t'";

		if (trim($ativo) == 'f')                  $aux_ativo = "'f'";
		else                                      $aux_ativo = "'t'";

		if($login_fabrica == 1 && $obrigatorio_os_produto == "t"){
			if(strlen($produto_referencia) == 0){
				$msg_erro["msg"][] = "Informe um produto para este comunicado";
				$msg_erro["campos"][] = "produto";
			}
		}

		if (strlen($peca_referencia) > 0){
			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica=$login_fabrica";
			$res = pg_query ($con,$sql);

			if (pg_num_rows ($res) == 0){
				 $msg_erro["msg"][] = "Peça $peca_referencia não cadastrada";
			} else {
				 $peca = pg_fetch_result ($res,0,0);
			}
		}else{
			$peca = "null";
		}

		if (strlen($produto_referencia) > 0 ){
			$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING(linha) WHERE (referencia = '$produto_referencia' OR referencia_fabrica = '$produto_referencia') AND fabrica=$login_fabrica";
			$res = pg_query ($con,$sql);

			if (pg_num_rows ($res) == 0) {
				$msg_erro["msg"][] = traduz("Produto % não cadastrado", null, null, [$produto_referencia]);
				$msg_erro["campos"][] = "produto";
			} else{
				$produto = pg_fetch_result ($res,0,0);
			}
		}else{
			$produto = "null";
		}

		if (in_array($login_fabrica, array(169,170))){
			if (strlen($produto_serie) > 0){
				$sql = "SELECT serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica}
						AND produto = {$produto}
						AND serie = UPPER('{$produto_serie}') OR serie = UPPER('S{$produto_serie}')";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) == 0){
					$msg_erro["msg"][] = traduz("Número de Série % não encontrado.", null, null, [$produto_serie]);
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

		$multiplo      = trim($_POST['radio_qtde_produtos']);
		$multiplo_peca = trim($_POST['radio_qtde_pecas']);

		if ($multiplo == 'muitos'){
			$produto = "null";
		}

		if ($multiplo_peca == 'muitos'){
			$peca          = "null";
		}

		//pega o codigo do posto========================================================

		$posto = "null";

		if(strlen($codigo_posto) > 0 and $tipo != 'Extrato' AND $tipo != "Comunicado por tela") {
			$sql = "SELECT  posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica ";
			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) <> 1) {
				$msg_erro["msg"][] = traduz("Código do posto (%) não encontrado", null, null, [$codigo_posto]);
			}else{
				$posto = pg_fetch_result ($res,0,posto);
			}
		}

		//pega o codigo do posto========================================================

		//HD 10983
		if($login_fabrica == 1){
			$pedido_faturado        = $_POST['pedido_faturado'];
			$pedido_em_garantia     = $_POST['pedido_em_garantia'];
			$digita_os              = $_POST['digita_os'];
			$reembolso_peca_estoque = $_POST['reembolso_peca_estoque'];
			$link_tela              = trim($_POST['link_tela']);
			$link_video             = trim($_POST['link_video']);
			$radio_ativo 			= $_POST["radio_ativo"];
			$radio_tela             = $_POST['radio_tela'];
			$titulo_video           = filter_input(INPUT_POST, 'titulo_video');

			if($tipo == "video_explicativos"){
				if(strlen($link_video)==0){
					$msg_erro["msg"][] .= "Informe o link do vídeo. ";
					$video_explicativo_display = " display: none ";
					$tela_video_explicativo_display  = "  display: block ";
				}

				if(strlen($link_tela)==0){
					$msg_erro["msg"][] .= "Informe o link de tela.";
					$video_explicativo_display = " display: none ";
					$tela_video_explicativo_display  = "  display: block ";
				}

				if( strlen($titulo_video) == 0 ){
                    $msg_erro['msg'][] .= "Informe o título do vídeo";
                    $video_explicativo_display = "display: none";
                    $tela_video_explicativo_display = "display: block";
                }
			}

			if(strlen($pedido_faturado) > 0){
				$pedido_faturado="'t'";
			}else{
				$pedido_faturado="'f'";
			}
			if(strlen($pedido_em_garantia)>0){
				$pedido_em_garantia="'t'";
			}else{
				$pedido_em_garantia="'f'";
			}
			if(strlen($digita_os)){
				$digita_os="'t'";
			}else{
				$digita_os="'f'";
			}
			if(strlen($reembolso_peca_estoque)>0){
				$reembolso_peca_estoque="'t'";
			}else{
				$reembolso_peca_estoque="'f'";
			}
			if(strlen($pedido_faturado) == 0 and strlen($pedido_em_garantia) == 0 and strlen($digita_os) == 0 and strlen($reembolso_peca_estoque) == 0 and $tipo != 'Extrato' AND $tipo != "Comunicado por tela"){
				$msg_erro["msg"][] = "Por favor,escolhe pelo menos um tipo de Posto pode Digitar ";
			}

			if($tipo == "Comunicado por tela"){
				if(empty($link_tela)){
					$msg_erro["msg"][] = "Por favor, infome a tela para o comunicado";
				}else{
					if($_serverEnvironment !== "development") {
						$urlOK=preg_match("#^(http://)?(https://)?(posvenda\.telecontrol\.com\.br)?(/assist/)?((admin/)?\w+)+(\.php)(\?.*)?$#", $link_tela,$a_url);
						
						if (!$urlOK){
							$msg_erro["msg"][] = "O link ('Endereço HTTP') não é válido, confira e corrija.";
						} else {
							$novo_link = $a_url[3].$a_url[4].$a_url[6];
						}
					} else {
						$novo_link = $link_tela;
					}
				}
			}

			if($digita_os == "'f'" AND $reembolso_peca_estoque == "'f'" AND $pedido_em_garantia == "'f'" AND $pedido_faturado == "'f'"){
				$digita_os = 'null';
				$reembolso_peca_estoque = 'null';
				$pedido_em_garantia = 'null';
				$pedido_faturado = 'null';
			}

		}else{
			$pedido_faturado        = "null";
			$pedido_em_garantia     = "null";
			$digita_os              = "'t'";
			$reembolso_peca_estoque = "'f'";
			$link_tela              = "null";
		}

		if ($login_fabrica == 1 and $tipo == 'Extrato'){
			$sql_updt_chamados_extrato = "
				UPDATE tbl_comunicado
				SET ativo = false
				WHERE fabrica=$login_fabrica
				AND   ativo is true
				AND   tipo = 'Extrato'
			";

			$res_updt_chamados_extrato = pg_query($con,$sql_updt_chamados_extrato);
		}

		if (count($msg_erro["msg"]) == 0) {

			$res = pg_query($con, "BEGIN TRANSACTION");

			if ($login_fabrica == 158 && strlen($comunicado) == 0 && count($unidadenegocios) > 0) {

	            foreach ($unidadenegocios as $k => $v) {
	                $unidade_negocios[] = "'$v'";
	            }

	            $sql = "SELECT DISTINCT dsp.posto
				                   FROM tbl_distribuidor_sla_posto dsp
				             INNER JOIN tbl_distribuidor_sla ds ON ds.distribuidor_sla = dsp.distribuidor_sla AND ds.fabrica = $login_fabrica
				                  WHERE dsp.fabrica = $login_fabrica
				                    AND ds.unidade_negocio IN (".implode(',', $unidade_negocios).");";

				$resUnidadeNegocio   = pg_query($con, $sql);
	            $countUnidadeNegocio = pg_num_rows($resUnidadeNegocio);

	            if ($countUnidadeNegocio > 0) {
	            	$postosUnidadeNegocio = pg_fetch_all($resUnidadeNegocio);

	            	foreach ($postosUnidadeNegocio as $key => $postoUni) {
	            		$comunicado = "";
	            		$msg_erro = insereAlteraComunicado($postoUni['posto']);

	            		if (count($msg_erro) > 0) {
	            			break;
	            		}
	            	}

	            }

			} else if (count($_POST['multi_posto']) > 0 && in_array($login_fabrica, [186])) { 
				foreach ($_POST['multi_posto'] as $key => $valorCodigo) {

					if (!empty($valorCodigo)) {

						$sqlPosto = "SELECT posto 
									 FROM tbl_posto_fabrica 
									 WHERE fabrica = {$login_fabrica}
									 AND codigo_posto = '{$valorCodigo}'";
						$resPosto = pg_query($con, $sqlPosto);

						$posto = pg_fetch_result($resPosto, 0, 'posto');

						if (!empty($posto)) {
							$comunicado = "";
							$msg_erro = insereAlteraComunicado();

							if (count($msg_erro) > 0) {
								break;
							}
						}

					}
				}
			} else {		
				if (in_array($login_fabrica, [20]) && is_array($aux_pais)) {
					$msg_erro = insereComunicadoBoschPais();
				}else if (in_array($login_fabrica, [20]) && is_array($aux_estado)) {
					$msg_erro = insereComunicadoBoschEstado();
				} else {
					$msg_erro = insereAlteraComunicado();
				}
			}

			if (count($msg_erro) == 0) {

				if(in_array($login_fabrica, array(35,148)) and $_POST['email_posto'] == 't'){

					if($login_fabrica == 148){
						if(is_array($familia) AND count($familia) > 0) {
							$sql = "SELECT DISTINCT posto 
									FROM tbl_posto_linha
									JOIN tbl_produto ON tbl_posto_linha.linha = tbl_produto.linha
									WHERE tbl_produto.fabrica_i = {$login_fabrica}
									AND tbl_produto.familia IN(".implode(",",$familia).")";
						}else if(is_array($linha) AND  count($linha) > 0){
							$sql = "SELECT DISTINCT posto 
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha
									WHERE tbl_linha.fabrica = {$login_fabrica}
									AND tbl_linha.linha IN(".implode(",",$linha).")";
						}

						$res = pg_query($con,$sql);
						
						for($k = 0 ; $k < pg_num_rows($res); $k++){
							$postos[] = pg_fetch_result($res, $k, "posto");
						}
					}

					if(strlen(trim($tipo_posto))>0){
						$condtipo_posto = " AND tbl_posto_fabrica.tipo_posto = $tipo_posto";
					}

					if(strlen(trim($posto))>0 AND $posto != 'null'){
						$condposto = " AND tbl_posto_fabrica.posto = $posto ";
					}else{

						if($login_fabrica == 148 AND count($postos) > 0){
							$condposto = " AND tbl_posto_fabrica.posto IN(".implode(",",$postos).") ";
						}

						$condCredenciado= " AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
					}

					$sqlEmail = "SELECT tbl_posto_fabrica.contato_email, tbl_posto.nome as razao_social
		        				 FROM tbl_posto_fabrica
		        				 JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		        				 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
		        				 $condCredenciado
		        				 $condposto
		        				 $condtipo_posto ";

					$resEmail = pg_query($con, $sqlEmail);	

					for($a=0; $a<pg_num_rows($resEmail); $a++){
						$email_posto 	= pg_fetch_result($resEmail, $a, 'contato_email');
						$razao_social	= pg_fetch_result($resEmail, $a, 'razao_social');

						$assunto = "Comunicado da Fábrica - ".$login_fabrica_nome;

						$mensagem = mb_strtoupper($razao_social)." <br><br> Foi cadastrado um novo comunicado: <b>".$_POST["descricao"]. "</b>";
							unset($mailer);
							$mailer = new PHPMailer();
							$mailer->IsHTML(true);

							$mailer->SetFrom("noreply@".strtolower($login_fabrica_nome).".com.br", $login_fabrica_nome);
							$mailer->AddAddress($email_posto);
							$mailer->Subject = $assunto;
							$mailer->Body = $mensagem;
							if($_serverEnvironment !== "development") {
								$mailer->Send();
							}
					}
				}

				if (in_array($login_fabrica, array(167,203)) && !empty($posto) && $posto != "null") {
					$sqlEmail = "SELECT contato_email
								 FROM tbl_posto_fabrica
								 WHERE fabrica = $login_fabrica
								 AND posto = $posto";
					$resEmail = pg_query($con, $sqlEmail);

					if (pg_num_rows($resEmail) > 0) {
						$email_posto = pg_fetch_result($resEmail, 0, 'contato_email');

						if (!empty($_POST["descricao"])) {
							$assunto = $_POST["descricao"];
						} else {
							$assunto = "Comunicado da Fábrica";
						}

						$mensagem = $_POST["mensagem"];

						$mailer->IsHTML(true);
						$mailer->SetFrom("noreply@telecontrol.com.br", "Brother");
						$mailer->AddAddress($email_posto);
						$mailer->AddAttachment($arquivo['tmp_name'],
						 $arquivo['name']);
						$mailer->Subject = $assunto;
						$mailer->Body = $mensagem;
						if($_serverEnvironment !== "development") {
							$mailer->Send();
						}
					}

				}

		        	$res = pg_query ($con,"COMMIT TRANSACTION");

		        $msg_success = ($tipo_gravacao == "alteracao") ? "Alterado com Sucesso" : "Gravado com Sucesso";

				header("Location: $PHP_SELF?msg={$msg_success}");

				exit;

			} else {

				if ($anexoS2OK === true)
					$s3->excluiArquivoS3($s3->attachList[0]); // Exlui o arquivo, abortada a gravação do comunicado.
				$res = pg_query ($con,"ROLLBACK TRANSACTION");

			}
		}

	}else{
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "tipo_comunicado";
		if (in_array($login_fabrica, array(42))) {
			if (strlen($_POST['descricao']) == 0) {
				$msg_erro["campos"][] = "titulo";
			}
		}
	}
}

function insereComunicadoBoschEstado ($postoUnidade = null) {
 	global $con, $msg_erro, $arquivo, $replicar_peca, $multiplo_peca, $replicar, $multiplo, $fabrica, $comunicado, $tipo, $peca, $produto, $aux_familia, $aux_linha, $aux_extensao, $aux_descricao, $aux_mensagem, $aux_tipo, $aux_obrigatorio_os_produto, $aux_obrigatorio_site, $posto, $aux_tipo_posto, $aux_ativo, $aux_estado, $aux_pais, $remetente_email, $pedido_faturado, $pedido_em_garantia, $digita_os, $reembolso_peca_estoque, $video, $link, $categoria_posto, $novo_link, $valor_mural_avisos, $campo_mural_avisos, $valor_mural_avisos, $update_mural_avisos, $login_fabrica, $s3, $campo_serie, $valor_serie, $update_serie;

 		
 	foreach ($aux_estado as $estado ) {
 			
		if (strlen($postoUnidade) > 0) {
			$posto = "";
			$posto = $postoUnidade;
		}

       $tipo_gravacao = "insercao";
		
		$sql = "INSERT INTO tbl_comunicado (
					peca                   ,
					produto                ,
					familia                ,
					linha                  ,
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
					estado                 ,
					pais                   ,
					remetente_email        ,
					pedido_faturado        ,
					pedido_em_garantia     ,
					digita_os              ,
					reembolso_peca_estoque ,
                    video                  ,
					link_externo           ,
					destinatario_especifico,
					programa
					$campo_mural_avisos
					$campo_serie
					$campo_duracao
					) VALUES (
					$peca                       ,
					$produto                    ,
					$aux_familia                ,
					$aux_linha                  ,
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
					'$estado'                   ,
					'BR'                        ,
					'$remetente_email'          ,
					$pedido_faturado            ,
					$pedido_em_garantia         ,
					$digita_os                  ,
					$reembolso_peca_estoque     ,
                    '$video'                    ,
					'$link'                     ,
					'$categoria_posto' 		    ,
					'$novo_link'
					$valor_mural_avisos
					$valor_serie
					$valor_duracao
				) RETURNING comunicado";

		

		//BLOQUEADO TEMPORTARIAMENTE PARA SUGGAR A PEDIDO DO TULIO EM 03/01/2013
		$res = pg_query ($con,$sql);
		$id = pg_fetch_result($res,0,'comunicado');	
		
		//anexo ficar em todos os comunicados
		$sqlSelTdocs = $sqltdocs = "SELECT * FROM tbl_tdocs WHERE hash_temp = '{$_POST['hashBox']}'";
		$resSelTdocs = pg_query($con,$sqlSelTdocs);
		$tdocs_id = pg_fetch_result ($resSelTdocs,0,tdocs_id);
		$contexto = pg_fetch_result ($resSelTdocs,0,contexto);
		$obs = pg_fetch_result ($resSelTdocs,0,obs);
		$referencia = pg_fetch_result ($resSelTdocs,0,referencia);
		$sqltdocs = "INSERT INTO tbl_tdocs(tdocs_id, fabrica, contexto, obs, referencia, referencia_id) 
					values ('{$tdocs_id}' , '20', '{$contexto}','{$obs}', '{$referencia}', '$id');";
		$resTdocs = pg_query($con,$sqltdocs);

		if(strlen(pg_last_error($con)) > 0){
			$msg_erro["msg"][] = pg_last_error($con);
		}

		if (count($msg_erro["msg"]) == 0) {
			if (strlen($comunicado) == 0) {
				$res        = pg_query ($con,"SELECT currval ('seq_comunicado')");
				$comunicado = pg_fetch_result ($res,0,0);
			}
		}

		# Múltiplos Comunicados
		# HD 6392
		$replicar         = $_POST['PickList'];
		$replicar_peca    = $_POST['PickListPeca'];
		//$replicar_linha   = $_POST['linha'];
		//$replicar_familia = $_POST['familia'];
		

		if (count($replicar) > 0 AND $multiplo == 'muitos'){

			for ($i=0;$i<count($replicar);$i++){
				$p = trim($replicar[$i]);

				$sql = "SELECT tbl_produto.produto
						FROM tbl_produto
						JOIN tbl_linha USING(linha)
						WHERE tbl_produto.referencia='$p'
						AND tbl_linha.fabrica=$login_fabrica";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res)==1){
					$prod = pg_fetch_result($res,0,0);
					$sql = "SELECT comunicado
							FROM tbl_comunicado_produto
							WHERE comunicado = $comunicado
							AND   produto    = $prod ";
					$res = pg_query ($con,$sql);
					if (pg_num_rows($res)==0){
						$sql = "INSERT INTO tbl_comunicado_produto (comunicado,produto)
								VALUES ($comunicado,$prod)";
						$res = pg_query ($con,$sql);

						if(strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = pg_last_error();
						}

					}
				}
			}
		} 

		if (count($replicar_peca)>0 AND $multiplo_peca=='muitos'){

			for ($i=0;$i<count($replicar_peca);$i++){
				$p = trim($replicar_peca[$i]);
				$sql = "SELECT tbl_peca.peca
						FROM tbl_peca
						WHERE tbl_peca.referencia='$p'
						AND tbl_peca.fabrica=$login_fabrica";
				$res = pg_query ($con,$sql);
				if (pg_num_rows($res)==1){
					$peca = pg_fetch_result($res,0,0);
					$sql = "SELECT comunicado
							FROM tbl_comunicado_peca
							WHERE comunicado = $comunicado
							AND   peca       = $peca ";
					$res = pg_query ($con,$sql);
					if (pg_num_rows($res)==0){
						$sql = "INSERT INTO tbl_comunicado_peca (comunicado, peca)
								VALUES ($comunicado, $peca)";
						$res = pg_query ($con,$sql);

						if(strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = pg_last_error();
						}

					}
				}
			}
		}

		$anexoS3OK = false;

	    if (strlen($arquivo['tmp_name']) > 0) {
	        if (count($msg_erro["msg"]) == 0) {

	            preg_match("/\.(od[tsp]|pdf|docx?|xlsx?|pptx?|pps|gif|bmp|png|jpe?g|rtf|txt|zip){1}$/i", $arquivo["name"], $ext);

	            $extensao_anexo = $ext[1];

	            if ($extensao_anexo == 'jpeg') $extensao_anexo = 'jpg';

	            $aux_extensao = strtolower("'$extensao_anexo'");

	            if (is_object($s3)) {

	                $s3->set_tipo_anexoS3(($tipo == "Laudo Tecnico") ? "co" : $tipo);
	                if (!$s3->uploadFileS3($comunicado, $arquivo, true)) {
	                    $msg_erro["msg"][] = $s3->_erro;
	                } else {
	                    $anexoS3OK = true;
	                    // var_dump($s3);die;
	                    // $aux_extensao = pathinfo($s3->attachList[0], PATHINFO_EXTENSION);

	                    $sql =	"UPDATE tbl_comunicado
	                                SET extensao   = $aux_extensao
	                                WHERE comunicado = $comunicado
	                                AND fabrica    = $login_fabrica";
	                    $res = @pg_query ($con,$sql);

	                    if(strlen(pg_last_error()) > 0){
	                        $msg_erro["msg"][] = pg_last_error($con);
	                    }

	                }

	            } else {

	                // Gera um nome único para a imagem
	                $nome_anexo = "$comunicado.$extensao_anexo";

	                // Caminho de onde a imagem ficará + extensao
	                $imagem_dir = "../comunicados/".strtolower($nome_anexo);

	                // Exclui anteriores, qquer extensao
	                //@unlink($imagem_dir);

	                // Faz o upload da imagem
	                if (count($msg_erro["msg"]) == 0) {
	                    //move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
	                    if (copy($arquivo["tmp_name"], $imagem_dir)) {
	                        $sql =	"UPDATE tbl_comunicado SET
	                            extensao  = LOWER($aux_extensao)
	                            WHERE comunicado = $comunicado
	                            AND   fabrica    = $login_fabrica";
	                        $res = @pg_query ($con,$sql);

	                        if(strlen(pg_last_error()) > 0){
	                            $msg_erro["msg"][] = pg_last_error($con);
	                        }
	                    }
	                }
	            }

			} else {
				$msg_erro["msg"][] = traduz("Erro no recebimento do arquivo {$arquivo['name']}.");
				$msg_erro["campos"][] = "arquivo";
	        }
	    }
	}
    return $msg_erro;
}

function insereComunicadoBoschPais ($postoUnidade = null) {
 	global $con, $msg_erro, $arquivo, $replicar_peca, $multiplo_peca, $replicar, $multiplo, $fabrica, $comunicado, $tipo, $peca, $produto, $aux_familia, $aux_linha, $aux_extensao, $aux_descricao, $aux_mensagem, $aux_tipo, $aux_obrigatorio_os_produto, $aux_obrigatorio_site, $posto, $aux_tipo_posto, $aux_ativo, $aux_estado, $aux_pais, $remetente_email, $pedido_faturado, $pedido_em_garantia, $digita_os, $reembolso_peca_estoque, $video, $link, $categoria_posto, $novo_link, $valor_mural_avisos, $campo_mural_avisos, $valor_mural_avisos, $update_mural_avisos, $login_fabrica, $s3, $campo_serie, $valor_serie, $update_serie;
 		
 	foreach ($aux_pais as $pais ) {
 			
		if (strlen($postoUnidade) > 0) {
			$posto = "";
			$posto = $postoUnidade;
		}

       $tipo_gravacao = "insercao";
		
		$sql = "INSERT INTO tbl_comunicado (
					peca                   ,
					produto                ,
					familia                ,
					linha                  ,
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
					estado                 ,
					pais                   ,
					remetente_email        ,
					pedido_faturado        ,
					pedido_em_garantia     ,
					digita_os              ,
					reembolso_peca_estoque ,
                    video                  ,
					link_externo           ,
					destinatario_especifico,
					programa
					$campo_mural_avisos
					$campo_serie
					$campo_duracao
					) VALUES (
					$peca                       ,
					$produto                    ,
					$aux_familia                ,
					$aux_linha                  ,
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
					$aux_estado                 ,
					'$pais'                     ,
					'$remetente_email'          ,
					$pedido_faturado            ,
					$pedido_em_garantia         ,
					$digita_os                  ,
					$reembolso_peca_estoque     ,
                    '$video'                    ,
					'$link'                     ,
					'$categoria_posto' 		    ,
					'$novo_link'
					$valor_mural_avisos
					$valor_serie
					$valor_duracao
				) RETURNING comunicado";

		

		//BLOQUEADO TEMPORTARIAMENTE PARA SUGGAR A PEDIDO DO TULIO EM 03/01/2013
		$res = pg_query ($con,$sql);
		$id = pg_fetch_result($res,0,'comunicado');	

		//anexo ficar em todos os comunicados
		$sqlSelTdocs = $sqltdocs = "SELECT * FROM tbl_tdocs WHERE hash_temp = '{$_POST['hashBox']}'";
		$resSelTdocs = pg_query($con,$sqlSelTdocs);
		$tdocs_id = pg_fetch_result ($resSelTdocs,0,tdocs_id);
		$contexto = pg_fetch_result ($resSelTdocs,0,contexto);
		$obs = pg_fetch_result ($resSelTdocs,0,obs);
		$referencia = pg_fetch_result ($resSelTdocs,0,referencia);
		$sqltdocs = "INSERT INTO tbl_tdocs(tdocs_id, fabrica, contexto, obs, referencia, referencia_id) 
					values ('{$tdocs_id}' , '20', '{$contexto}','{$obs}', '{$referencia}', '$id');";
		$resTdocs = pg_query($con,$sqltdocs);	

		if(strlen(pg_last_error($con)) > 0){
			$msg_erro["msg"][] = pg_last_error($con);
		}

		if (count($msg_erro["msg"]) == 0) {
			if (strlen($comunicado) == 0) {
				$res        = pg_query ($con,"SELECT currval ('seq_comunicado')");
				$comunicado = pg_fetch_result ($res,0,0);
			}
		}

		# Múltiplos Comunicados
		# HD 6392
		$replicar      = $_POST['PickList'];
		$replicar_peca = $_POST['PickListPeca'];

		if (count($replicar) > 0 AND $multiplo == 'muitos'){

			for ($i=0;$i<count($replicar);$i++){
				$p = trim($replicar[$i]);

				$sql = "SELECT tbl_produto.produto
						FROM tbl_produto
						JOIN tbl_linha USING(linha)
						WHERE tbl_produto.referencia='$p'
						AND tbl_linha.fabrica=$login_fabrica";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res)==1){
					$prod = pg_fetch_result($res,0,0);
					$sql = "SELECT comunicado
							FROM tbl_comunicado_produto
							WHERE comunicado = $comunicado
							AND   produto    = $prod ";
					$res = pg_query ($con,$sql);

					if (pg_num_rows($res)==0){
						$sql = "INSERT INTO tbl_comunicado_produto (comunicado,produto)
								VALUES ($comunicado,$prod)";
						$res = pg_query ($con,$sql);

						if(strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = pg_last_error();
						}

					}
				}
			}
		}

		if (count($replicar_peca)>0 AND $multiplo_peca=='muitos'){

			for ($i=0;$i<count($replicar_peca);$i++){
				$p = trim($replicar_peca[$i]);
				$sql = "SELECT tbl_peca.peca
						FROM tbl_peca
						WHERE tbl_peca.referencia='$p'
						AND tbl_peca.fabrica=$login_fabrica";
				$res = pg_query ($con,$sql);
				if (pg_num_rows($res)==1){
					$peca = pg_fetch_result($res,0,0);
					$sql = "SELECT comunicado
							FROM tbl_comunicado_peca
							WHERE comunicado = $comunicado
							AND   peca       = $peca ";
					$res = pg_query ($con,$sql);
					if (pg_num_rows($res)==0){
						$sql = "INSERT INTO tbl_comunicado_peca (comunicado, peca)
								VALUES ($comunicado, $peca)";
						$res = pg_query ($con,$sql);

						if(strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = pg_last_error();
						}

					}
				}
			}
		}

		$anexoS3OK = false;

	    if (strlen($arquivo['tmp_name']) > 0) {
	        if (count($msg_erro["msg"]) == 0) {

	            preg_match("/\.(od[tsp]|pdf|docx?|xlsx?|pptx?|pps|gif|bmp|png|jpe?g|rtf|txt|zip){1}$/i", $arquivo["name"], $ext);

	            $extensao_anexo = $ext[1];

	            if ($extensao_anexo == 'jpeg') $extensao_anexo = 'jpg';

	            $aux_extensao = strtolower("'$extensao_anexo'");

	            if (is_object($s3)) {

	                $s3->set_tipo_anexoS3(($tipo == "Laudo Tecnico") ? "co" : $tipo);
	                if (!$s3->uploadFileS3($comunicado, $arquivo, true)) {
	                    $msg_erro["msg"][] = $s3->_erro;
	                } else {
	                    $anexoS3OK = true;
	                    // var_dump($s3);die;
	                    // $aux_extensao = pathinfo($s3->attachList[0], PATHINFO_EXTENSION);

	                    $sql =	"UPDATE tbl_comunicado
	                                SET extensao   = $aux_extensao
	                                WHERE comunicado = $comunicado
	                                AND fabrica    = $login_fabrica";
	                    $res = @pg_query ($con,$sql);

	                    if(strlen(pg_last_error()) > 0){
	                        $msg_erro["msg"][] = pg_last_error($con);
	                    }

	                }

	            } else {

	                // Gera um nome único para a imagem
	                $nome_anexo = "$comunicado.$extensao_anexo";

	                // Caminho de onde a imagem ficará + extensao
	                $imagem_dir = "../comunicados/".strtolower($nome_anexo);

	                // Exclui anteriores, qquer extensao
	                //@unlink($imagem_dir);

	                // Faz o upload da imagem
	                if (count($msg_erro["msg"]) == 0) {
	                    //move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
	                    if (copy($arquivo["tmp_name"], $imagem_dir)) {
	                        $sql =	"UPDATE tbl_comunicado SET
	                            extensao  = LOWER($aux_extensao)
	                            WHERE comunicado = $comunicado
	                            AND   fabrica    = $login_fabrica";
	                        $res = @pg_query ($con,$sql);

	                        if(strlen(pg_last_error()) > 0){
	                            $msg_erro["msg"][] = pg_last_error($con);
	                        }
	                    }
	                }
	            }

			} else {
				$msg_erro["msg"][] = "Erro no recebimento do arquivo {$arquivo['name']}.";
				$msg_erro["campos"][] = "arquivo";
	        }
	    }
	}
    return $msg_erro;
}

function insereAlteraComunicado ($postoUnidade = null) {
        global $con, $msg_erro, $arquivo, $replicar_peca, $multiplo_peca, $replicar, $multiplo, $fabrica, $comunicado, $tipo, $peca, $produto, $aux_familia, $aux_linha, $aux_extensao, $aux_descricao, $aux_mensagem, $aux_tipo, $aux_obrigatorio_os_produto, $aux_obrigatorio_site, $posto, $aux_tipo_posto, $aux_ativo, $aux_estado, $aux_pais, $remetente_email, $pedido_faturado, $pedido_em_garantia, $digita_os, $reembolso_peca_estoque, $video, $link, $categoria_posto, $novo_link, $valor_mural_avisos, $campo_mural_avisos, $valor_mural_avisos, $update_mural_avisos, $login_fabrica, $s3, $campo_serie, $valor_serie, $update_serie, $comunicado_destino, $link_tela, $link_video, $radio_ativo;

        if(is_array($aux_familia)){
	 		$familias = $aux_familia;
	 		$aux_familia = "null";
	 	} 

	 	if(is_array($aux_linha)){
	 		$linhas = $aux_linha;
	 		$aux_linha = "null";
	 	} 

	 	if (is_array($aux_pais)) {
	 		$paises = $aux_pais;
	 		$aux_pais = "null";
	 	}

		if($tipo == 'Acessório' AND $login_fabrica == 1){
		    $sql = "SELECT comunicado, extensao FROM tbl_comunicado WHERE fabrica = {$login_fabrica} AND tipo = '{$tipo}' ORDER BY comunicado DESC LIMIT 1";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 1){
				$comunicado   = pg_fetch_result ($res,0,'comunicado');

				if(!isset($_FILES["arquivo"]))
					$aux_extensao = pg_fetch_result ($res,0,'extensao');

				$apagar_imagem = "true";
			}
		}

		if (strlen($postoUnidade) > 0) {
			$posto = "";
			$posto = $postoUnidade;
		}

		if($login_fabrica == 1 and $tipo = "video_explicativos"){
			$novo_link = $link_tela;
			$video = $link_video;
			$aux_ativo = "'".$radio_ativo."'";
		}

	 	$palavras_chave = null;
    	if($login_fabrica == 42 && isset($_POST['palavras_chave'])){
    		$palavras = $_POST['palavras_chave'];
    		if(count($palavras) > 0){
    			foreach($palavras as $key => $value){
					$palavras_chave .= strtolower($value) . ' ';
    			}
    		}
    	}
		
		if (strlen($comunicado) == 0) {
            if ($login_fabrica == 42 and $aux_tipo == "'FAQ Makita'") {
                $sql = "UPDATE tbl_comunicado SET ativo = 'f' WHERE fabrica = {$login_fabrica} AND lower(tipo) = 'faq makita' AND ativo = 't'";
                pg_query($con, $sql);
            }

            /*HD - 4060618*/
            if ($login_fabrica == 42 && in_array($_POST['tipo'], array('Video', 'Treinamento Telecontrol'))) {
            	if (!empty($_POST["duracao"])) {
            		$duracao["duracao_video"] = utf8_encode($_POST["duracao"]);
            		$duracao = json_encode($duracao);
            		$campo_duracao = " , parametros_adicionais ";
            		$valor_duracao = " , '$duracao' ";
            	}
            }
       
            if ($login_fabrica == 175){
	        	$campo_comunicado_destino = ", tecnico";
	        	$value_comunicado_destino = ", '$comunicado_destino'";
	        }

	        if ($login_fabrica == 177){
				if (count($aux_estado) > 0){
					$estados_array 				= array();
					$estados_array["estados"] 	= $aux_estado;
					$estados_json 				= json_encode($estados_array);
					
					$campo_parametros_adicionais = ", parametros_adicionais ";
					$valor_parametros_adicionais = ", '".$estados_json."' ";
				}
				$aux_estado 				= "null";
			}

	
			$tipo_gravacao = "insercao";

			$sql = "INSERT INTO tbl_comunicado (
						peca                   ,
						produto                ,
						familia                ,
						linha                  ,
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
						estado                 ,
						pais                   ,
						remetente_email        ,
						pedido_faturado        ,
						pedido_em_garantia     ,
						digita_os              ,
						reembolso_peca_estoque ,
						palavra_chave          ,
                        video                  ,
						link_externo           ,
						destinatario_especifico,
						programa
						$campo_mural_avisos
						$campo_serie
						$campo_duracao
						$campo_comunicado_destino
						$campo_parametros_adicionais
						) VALUES (
						$peca                       ,
						$produto                    ,
						$aux_familia                ,
						$aux_linha                  ,
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
						$aux_estado                 ,
						$aux_pais                   ,
						'$remetente_email'          ,
						$pedido_faturado            ,
						$pedido_em_garantia         ,
						$digita_os                  ,
						$reembolso_peca_estoque     ,
						'$palavras_chave'           ,
                        '$video'                    ,
						'$link'                     ,
						'$categoria_posto' 		    ,
						'$novo_link'
						$valor_mural_avisos
						$valor_serie
						$valor_duracao
						$value_comunicado_destino
						$valor_parametros_adicionais
					) RETURNING comunicado";
			$res = pg_query($con, $sql);

		}else{
            if ($login_fabrica == 42 && $aux_ativo == "'t'" and $aux_tipo == "'FAQ Makita'") {
                $sql = "UPDATE tbl_comunicado SET ativo = 'f' WHERE fabrica = {$login_fabrica} AND lower(tipo) = 'faq makita' AND ativo = 't'";
                pg_query($con, $sql);
            }

			/*HD - 4060618*/
            if ($login_fabrica == 42 && in_array($_POST['tipo'], array('Video', 'Treinamento Telecontrol'))) {
            	if (!empty($_POST["duracao"])) {
            		$aux_sql = "SELECT parametros_adicionais FROM tbl_comunicado WHERE comunicado = $comunicado LIMIT 1";
            		$aux_res = pg_query($con, $aux_sql);
            		
            		$parametros_adicionais = json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'), true);
            		
            		$parametros_adicionais["duracao_video"] = utf8_encode($_POST["duracao"]);

            		$parametros_adicionais = json_encode($parametros_adicionais);

            		$update_parametros_adicionais = " , parametros_adicionais = '$parametros_adicionais' ";
            	}
            }
			$tipo_gravacao = "alteracao";

			if ($login_fabrica == 175){
				$update_comunicado_destino = ", tecnico = '$comunicado_destino' ";
		    }

		    if ($login_fabrica == 177){
				if (count($aux_estado) > 0){
					$aux_sql = "SELECT parametros_adicionais FROM tbl_comunicado WHERE comunicado = $comunicado AND fabrica = {$login_fabrica}";
            		$aux_res = pg_query($con, $aux_sql);
            		$parametros_adicionais = json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'), true);
            		$parametros_adicionais["estados"] = $aux_estado;
            		$parametros_adicionais = json_encode($parametros_adicionais);
            		$update_parametros_adicionais = " , parametros_adicionais = '$parametros_adicionais' ";
				}
				$aux_estado = "null";
			}

			if( $login_fabrica == 1 AND $aux_tipo == "'video_explicativos'" ){
                $aux_descricao = filter_input(INPUT_POST, 'titulo_video');
                $aux_descricao = "'{$aux_descricao}'";
            }

			$sql = "UPDATE tbl_comunicado SET
						peca                   = $peca                       ,
						produto                = $produto                    ,
						familia                = $aux_familia                ,
						linha                  = $aux_linha                  ,
						extensao               = LOWER($aux_extensao)        ,
						descricao              = $aux_descricao              ,
						mensagem               = $aux_mensagem               ,
						tipo                   = $aux_tipo                   ,
						obrigatorio_os_produto = $aux_obrigatorio_os_produto ,
						obrigatorio_site       = $aux_obrigatorio_site       ,
						posto                  = $posto                      ,
						ativo                  = $aux_ativo                  ,
						tipo_posto             = $aux_tipo_posto             ,
						estado                 = $aux_estado                 ,
						pais                   = $aux_pais                   ,
						remetente_email        = '$remetente_email'          ,
						pedido_faturado        = $pedido_faturado            ,
						pedido_em_garantia     = $pedido_em_garantia         ,
						digita_os              = $digita_os                  ,
						reembolso_peca_estoque = $reembolso_peca_estoque     ,
						palavra_chave          = '$palavras_chave'           ,
                        video                  = '$video'                    ,
						link_externo           = '$link'                     ,
						destinatario_especifico = '$categoria_posto' ,
						programa               = '$novo_link'
						$update_mural_avisos
						$update_serie
						$update_parametros_adicionais
						$update_comunicado_destino
					WHERE comunicado = $comunicado
					  AND fabrica    = $login_fabrica";
			$res = pg_query ($con,$sql);
		}

		if(strlen(pg_last_error()) > 0){
			$msg_erro["msg"][] = pg_last_error($con);
		}

		if ($comunicado > 0) {
			$id = $comunicado;
		} else {
			$id = pg_fetch_result($res,0,'comunicado');			
		}

		if(count($msg_erro["msg"]) == 0 && $tipo != "video_explicativos"){
			$sqltdocs = "UPDATE tbl_tdocs SET referencia_id = $id WHERE hash_temp = '{$_POST['hashBox']}'";
			$resTdocs = pg_query($con,$sqltdocs);
		}

		if (count($msg_erro["msg"]) == 0) {
			if (strlen($comunicado) == 0) {
				$res        = pg_query ($con,"SELECT currval ('seq_comunicado')");
				$comunicado = pg_fetch_result ($res,0,0);
			}
		}

		if(strlen($comunicado)>0){//HD 83303
			$sql = "DELETE FROM tbl_comunicado_produto
					WHERE comunicado = $comunicado";
			$res = pg_query ($con,$sql);

			if(strlen(pg_last_error()) > 0){
				$msg_erro["msg"][] = pg_last_error();
			}

			$sql = "DELETE FROM tbl_comunicado_peca
					WHERE comunicado = $comunicado";
			$res = pg_query ($con,$sql);

			if(strlen(pg_last_error()) > 0){
				$msg_erro["msg"][] = pg_last_error();
			}

		}

		# Múltiplos Comunicados
		# HD 6392
		$replicar      = $_POST['PickList'];
		$replicar_peca = $_POST['PickLiadmin/comunicado_produto.phpstPeca'];
		$replicar_linha   = $linhas;
		$replicar_familia = $familias;
		$replicar_paises  = $paises;

		$produto2 = ($produto != "null") ? $produto : "";

		if (count($replicar) > 0 AND $multiplo == 'muitos'){

			for ($i=0;$i<count($replicar);$i++){
				$p = trim($replicar[$i]);

				$sql = "SELECT tbl_produto.produto, tbl_produto.linha, tbl_produto.familia
						FROM tbl_produto
						JOIN tbl_linha USING(linha)
						WHERE tbl_produto.referencia='$p'
						AND tbl_linha.fabrica=$login_fabrica";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res)==1){
					$prod = pg_fetch_result($res,0,0);
					$prod_linha = pg_fetch_result($res,0,1);
					$prod_familia = pg_fetch_result($res,0,2);

					$campo_linha = "";
					$campo_familia = "";

					if (!empty($prod_linha)) {
						$campo_linha = ", linha "; 
						$prod_linha = ", ".$prod_linha;
					}

					if (!empty($prod_familia)) {
						$campo_familia = ", familia "; 
						$prod_familia = ", ".$prod_familia;
					}

					$sql = "SELECT comunicado
							FROM tbl_comunicado_produto
							WHERE comunicado = $comunicado
							AND   produto    = $prod ";
					$res = pg_query ($con,$sql);

					if (pg_num_rows($res)==0){
						$sql = "INSERT INTO tbl_comunicado_produto (comunicado,produto $campo_linha $campo_familia)
								VALUES ($comunicado,$prod $prod_linha $prod_familia)";
						$res = pg_query ($con,$sql);

						if(strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = pg_last_error();
						}

					}
				}
			}
		}elseif(count($replicar_familia) > 0 and strlen($produto2) == 0 ) {
			for ($i=0;$i<count($replicar_familia);$i++){
				$fam = trim($replicar_familia[$i]);

					$sql = "SELECT comunicado
							FROM tbl_comunicado_produto
							WHERE comunicado = $comunicado
							AND   familia    = $fam ";
					$res = pg_query ($con,$sql);
					if (pg_num_rows($res)==0){
						$sql = "INSERT INTO tbl_comunicado_produto (comunicado,familia)
								VALUES ($comunicado,$fam)";
						$res = pg_query ($con,$sql);

						if(strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = pg_last_error();
						}
					}				
			}
		} elseif(count($replicar_linha) > 0 and strlen($produto2) == 0) {
			for ($i=0;$i<count($replicar_linha);$i++){
				$linhas = trim($replicar_linha[$i]);

					$sql = "SELECT comunicado
							FROM tbl_comunicado_produto
							WHERE comunicado = $comunicado
							AND   linha    = $linhas ";
					$res = pg_query ($con,$sql);
					if (pg_num_rows($res)==0){
						$sql = "INSERT INTO tbl_comunicado_produto (comunicado,linha)
								VALUES ($comunicado,$linhas)";
						$res = pg_query ($con,$sql);

						if(strlen(pg_last_error()) > 0){
							$msg_erro["msg"][] = pg_last_error();
						}
					}
				
			}
		}			

	if (count($replicar_peca)>0 AND $multiplo_peca=='muitos'){

		for ($i=0;$i<count($replicar_peca);$i++){
			$p = trim($replicar_peca[$i]);
			$sql = "SELECT tbl_peca.peca
					FROM tbl_peca
					WHERE tbl_peca.referencia='$p'
					AND tbl_peca.fabrica=$login_fabrica";
			$res = pg_query ($con,$sql);
			if (pg_num_rows($res)==1){
				$peca = pg_fetch_result($res,0,0);
				$sql = "SELECT comunicado
						FROM tbl_comunicado_peca
						WHERE comunicado = $comunicado
						AND   peca       = $peca ";
				$res = pg_query ($con,$sql);
				if (pg_num_rows($res)==0){
					$sql = "INSERT INTO tbl_comunicado_peca (comunicado, peca)
							VALUES ($comunicado, $peca)";
					$res = pg_query ($con,$sql);

					if(strlen(pg_last_error()) > 0){
						$msg_erro["msg"][] = pg_last_error();
					}

				}
			}
		}
	}

	if (count($replicar_paises)>0){

		$sql = "SELECT comunicado, produto, linha, familia
				FROM tbl_comunicado_produto
				WHERE comunicado = $comunicado
				AND pais IS NULL LIMIT 1";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$produto_pais = pg_fetch_result($res, 0, "produto");
			$linha_pais   = pg_fetch_result($res, 0, "linha");
			$familia_pais = pg_fetch_result($res, 0, "familia");

			if (!empty($produto_pais)) {
				$campo_prod = ", produto";
				$produto_pais = ", ".$produto_pais;
			}

			if (!empty($linha_pais)) {
				$campo_linha_pais = ", linha";
				$linha_pais = ", ".$linha_pais;
			}

			if (!empty($familia_pais)) {
				$campo_familia_pais = ", familia";
				$familia_pais = ", ".$familia_pais;
			}

		}

		for ($i=0;$i<count($replicar_paises);$i++){
			$xpais = trim($replicar_paises[$i]);

			$sql = "SELECT comunicado
					FROM tbl_comunicado_produto
					WHERE comunicado = $comunicado
					AND   pais    = '$xpais'";
			$res = pg_query ($con,$sql);
			if (pg_num_rows($res)==0){
				$sql = "INSERT INTO tbl_comunicado_produto (comunicado,pais $campo_prod $campo_linha_pais $campo_familia_pais)
						VALUES ($comunicado,'$xpais' $produto_pais $linha_pais $familia_pais)";
				$res = pg_query ($con,$sql);

				if(strlen(pg_last_error()) > 0){
					$msg_erro["msg"][] = pg_last_error();
				}
			}				
		}
	}

	$anexoS3OK = false;

    if (strlen($arquivo['tmp_name']) > 0) {
        if (count($msg_erro["msg"]) == 0) {

            preg_match("/\.(od[tsp]|pdf|docx?|xlsx?|pptx?|pps|gif|bmp|png|jpe?g|rtf|txt|zip){1}$/i", $arquivo["name"], $ext);

            $extensao_anexo = $ext[1];

            if ($extensao_anexo == 'jpeg') $extensao_anexo = 'jpg';

            $aux_extensao = strtolower("'$extensao_anexo'");

            if (is_object($s3)) {

                $s3->set_tipo_anexoS3(($tipo == "Laudo Tecnico") ? "co" : $tipo);
                if (!$s3->uploadFileS3($comunicado, $arquivo, true)) {
                    $msg_erro["msg"][] = $s3->_erro;
                } else {
                    $anexoS3OK = true;
                    // var_dump($s3);die;
                    // $aux_extensao = pathinfo($s3->attachList[0], PATHINFO_EXTENSION);

                    $sql =	"UPDATE tbl_comunicado
                                SET extensao   = $aux_extensao
                                WHERE comunicado = $comunicado
                                AND fabrica    = $login_fabrica";
                    $res = @pg_query ($con,$sql);

                    if(strlen(pg_last_error()) > 0){
                        $msg_erro["msg"][] = pg_last_error($con);
                    }

                }

            } else {

                // Gera um nome único para a imagem
                $nome_anexo = "$comunicado.$extensao_anexo";

                // Caminho de onde a imagem ficará + extensao
                $imagem_dir = "../comunicados/".strtolower($nome_anexo);

                // Exclui anteriores, qquer extensao
                //@unlink($imagem_dir);

                // Faz o upload da imagem
                if (count($msg_erro["msg"]) == 0) {
                    //move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
                    if (copy($arquivo["tmp_name"], $imagem_dir)) {
                        $sql =	"UPDATE tbl_comunicado SET
                            extensao  = LOWER($aux_extensao)
                            WHERE comunicado = $comunicado
                            AND   fabrica    = $login_fabrica";
                        $res = @pg_query ($con,$sql);

                        if(strlen(pg_last_error()) > 0){
                            $msg_erro["msg"][] = pg_last_error($con);
                        }
                    }
                }
            }

		} else {
			$msg_erro["msg"][] = traduz("Erro no recebimento do arquivo %.", null, null, [$arquivo['name']]);
			$msg_erro["campos"][] = "arquivo";
        }
    }
    
    return $msg_erro;
}

$produto_referencia     = $_POST["produto_referencia"];
$produto_descricao      = $_POST["produto_descricao"];
$descricao              = $_POST['descricao'];
$extensao               = $_POST['extensao'];
$tipo                   = $_POST['tipo'];
$mensagem               = $_POST['mensagem'];
$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
$obrigatorio_site       = $_POST['obrigatorio_site'];
$estado                 = $_POST['estado'];
$ativo                  = $_POST['ativo'];

if (strlen($comunicado) > 0) {

if($login_fabrica == 1){
	 $campos_black = "tbl_comunicado.pedido_em_garantia, tbl_comunicado.destinatario_especifico, ";
}
	$sql = "SELECT  tbl_produto.referencia AS prod_referencia,
					tbl_produto.descricao  AS prod_descricao ,
					tbl_peca.referencia    AS peca_referencia,
					tbl_peca.descricao     AS peca_descricao ,
					tbl_comunicado.* ,
					tbl_posto.nome AS posto_nome ,
					$campos_black
					tbl_posto_fabrica.codigo_posto
					$campos_parametros_adicionais
			FROM    tbl_comunicado
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_peca    USING (peca)
			LEFT JOIN tbl_posto   ON tbl_comunicado.posto = tbl_posto.posto
			LEFT JOIN tbl_posto_fabrica ON tbl_comunicado.posto = tbl_posto_fabrica.posto AND tbl_comunicado.fabrica = tbl_posto_fabrica.fabrica
			WHERE   tbl_comunicado.comunicado = $comunicado
			AND     tbl_comunicado.fabrica    = $login_fabrica";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) > 0) {
		$produto_serie 			= trim(pg_fetch_result($res,0, 'serie'));
		$peca_referencia        = trim(pg_fetch_result($res,0,'peca_referencia'));
		$peca_descricao         = trim(pg_fetch_result($res,0,'peca_descricao'));
		$produto_referencia     = trim(pg_fetch_result($res,0,'prod_referencia'));
		$familia                = trim(pg_fetch_result($res,0,'familia'));
		$linha                  = trim(pg_fetch_result($res,0,'linha'));
		$produto_descricao      = trim(pg_fetch_result($res,0,'prod_descricao'));
		$descricao              = trim(pg_fetch_result($res,0,'descricao'));
		$extensao               = strtolower(trim(pg_fetch_result($res,0,'extensao')));
		$tipo                   = trim(pg_fetch_result($res,0,'tipo'));
		$mensagem               = trim(pg_fetch_result($res,0,'mensagem'));
		$obrigatorio_os_produto = trim(pg_fetch_result($res,0,'obrigatorio_os_produto'));
		$obrigatorio_site       = trim(pg_fetch_result($res,0,'obrigatorio_site'));
		$posto                  = trim(pg_fetch_result($res,0,'posto'));
		$posto_nome             = trim(pg_fetch_result($res,0,'posto_nome'));
		$codigo_posto           = trim(pg_fetch_result($res,0,'codigo_posto'));
		$remetente_email        = trim(pg_fetch_result($res,0,'remetente_email'));
		$tipo_posto             = trim(pg_fetch_result($res,0,'tipo_posto'));
		$estado                 = trim(pg_fetch_result($res,0,'estado'));
		$ativo                  = trim(pg_fetch_result($res,0,'ativo'));
		$pedido_faturado        = trim(pg_fetch_result($res,0,'pedido_faturado'));
		$pedido_em_garantia     = trim(pg_fetch_result($res,0,'pedido_em_garantia'));
		$digita_os              = trim(pg_fetch_result($res,0,'digita_os'));
		$palavra_chave          = trim(pg_fetch_result($res,0,'palavra_chave'));
		$reembolso_peca_estoque = trim(pg_fetch_result($res,0,'reembolso_peca_estoque'));
        $video                  = trim(pg_fetch_result($res,0,'video'));
		$link                   = trim(pg_fetch_result($res,0,'link_externo'));
		$comunicado_destino   	= trim(pg_fetch_result($res,0, 'tecnico'));
		$pais                   = trim(pg_fetch_result($res,0,'pais'));

		if($login_fabrica == 1){
			$categoria_posto = trim(pg_fetch_result($res,0,'destinatario_especifico'));
			$pedido_garantia = trim(pg_fetch_result($res,0,'pedido_em_garantia'));
			if($tipo == "video_explicativos"){
				$video_explicativo_display = " display: none ";
				$tela_video_explicativo_display  = "  display: block ";
				$link_video = $video;
			}
		}

		if($login_fabrica == 42){
			$mural_avisos 		   = trim(pg_fetch_result($res,0,'suframa'));
			$parametros_adicionais = json_decode(trim(pg_fetch_result($res,0,'parametros_adicionais')), true);

			if (!empty($parametros_adicionais["duracao_video"])) {
				$duracao = $parametros_adicionais["duracao_video"];
			}
		}

		if ($login_fabrica == 177){
			$estados = json_decode(trim(pg_fetch_result($res,0,'parametros_adicionais')), true);
			$estado = $estados["estados"];
		}

		$url_completa = preg_match("#^(http://)?(https://)?(posvenda\.telecontrol\.com\.br)#", trim(pg_fetch_result($res,0,'programa')),$a_url);

		if ($url_completa) {
			$link_tela = trim(pg_fetch_result($res,0,'programa'));
		} else {
			if ($_serverEnvironment !== "development") {
				$link_tela = "http://posvenda.telecontrol.com.br".trim(pg_fetch_result($res,0,'programa'));
			} else {
				$link_tela = trim(pg_fetch_result($res,0,'programa'));
			}
		}

		if($tipo == "video_explicativos"){
			$link_tela = trim(pg_fetch_result($res,0,'programa'));
		}
		$btn_lista = "ok";
	}
	# Comunicados multiplos PRODUTOS
	# HD 6392
	$sql = "SELECT tbl_produto.produto,
				   tbl_produto.referencia,
				   tbl_produto.descricao,
				   tbl_comunicado_produto.linha,
				   tbl_comunicado_produto.familia
			  FROM tbl_comunicado_produto
			 LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
			 WHERE tbl_comunicado_produto.comunicado  = $comunicado";
	$resProd = pg_query ($con,$sql);
	$lista_produtos = array();

	$mult_linha[]    = $linha;
	$mult_familia[]  = $familia;
	for ($i=0; $i<pg_num_rows ($resProd); $i++){
		$mult_produto    = trim(pg_fetch_result($resProd,$i,'produto'));
		$mult_referencia = trim(pg_fetch_result($resProd,$i,'referencia'));
		$mult_descricao  = trim(pg_fetch_result($resProd,$i,'descricao'));
		$mult_linha[]    = trim(pg_fetch_result($resProd,$i,'linha'));
		$mult_familia[]  = trim(pg_fetch_result($resProd,$i,'familia'));
		if(!empty($mult_produto)){
			array_push($lista_produtos,array($mult_produto,$mult_referencia,$mult_descricao));	
		}		
	}

	# Comunicados multiplos PRODUTOS
	# HD 19052
	$sql = "SELECT 	tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao
			FROM tbl_comunicado_peca
			JOIN tbl_peca ON tbl_peca.peca = tbl_comunicado_peca.peca
			WHERE tbl_comunicado_peca.comunicado = $comunicado";
	$resPeca = pg_query ($con,$sql);
	$lista_pecas = array();
	for ($i=0; $i<pg_num_rows ($resPeca); $i++){
		$mult_peca       = trim(pg_fetch_result($resPeca,$i,'peca'));
		$mult_referencia = trim(pg_fetch_result($resPeca,$i,'referencia'));
		$mult_descricao  = trim(pg_fetch_result($resPeca,$i,'descricao'));
		array_push($lista_pecas,array($mult_peca,$mult_referencia,$mult_descricao));
	}
}

if (trim($btn_acao) == "apagar") {
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$comunicado = $_POST["apagar"];

	$sql = "SELECT extensao FROM tbl_comunicado WHERE tbl_comunicado.comunicado = $comunicado";
	$res = pg_query ($con,$sql);

	if(strlen(pg_last_error()) > 0){
		$msg_erro["msg"][] = pg_last_error();
	}

	if (count($msg_erro["msg"]) == 0) {
		$extensao = @pg_fetch_result($res,0,0);
	}
	//HD 9892
	$sql=" SELECT comunicado
			 FROM tbl_comunicado_produto
			WHERE comunicado=$comunicado ";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$sql2 = "DELETE FROM tbl_comunicado_produto
				WHERE comunicado = $comunicado";
		$res2 = pg_query ($con,$sql2);

		if(strlen(pg_last_error()) > 0){
			$msg_erro["msg"][] = pg_last_error();
		}

	}
	$sql = "UPDATE tbl_comunicado SET fabrica = 0 WHERE tbl_comunicado.comunicado = $comunicado";
	$res = @pg_query ($con,$sql);

	if(strlen(pg_last_error()) > 0){
		$msg_erro["msg"][] = pg_last_error();
	}

	if (count($msg_erro["msg"]) == 0) {
		if ($S3_online and $s3->temAnexos($comunicado)) {
			if (!$s3->excluiArquivoS3($s3->attachList[0])) {
				$msg_erro["msg"][] = $s3->_erro;
			}
		} else {
			$imagem_dir = "../comunicados/".$comunicado.".".$extensao;
			if (is_file($imagem_dir)){
				if (!unlink($imagem_dir)){
					$msg_erro["msg"][] = "Não foi possível excluir arquivo";
				}
			}
		}
	}

	if (count($msg_erro["msg"]) == 0){
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF?msg=Removido com sucesso");
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
    $video                  = $_POST['video'];
	$link                   = $_POST['link'];
	$obrigatorio_os_produto = $_POST['obrigatorio_os_produto'];
	$obrigatorio_site       = $_POST['obrigatorio_site'];
	$estado                 = $_POST['estado'];
	$ativo                  = $_POST['ativo'];
	$link_tela              = $_POST['link_tela'];

	$res = pg_query ($con,"ROLLBACK TRANSACTION");
}

if (trim($btn_acao) == "apagararquivo") {

	$comunicado = $_POST["apagar"];

	$sql = "SELECT extensao FROM tbl_comunicado WHERE comunicado = $comunicado";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) == 1) {

		if ($S3_online and $s3->temAnexos($comunicado)) {
			if (!$s3->excluiArquivoS3($s3->attachList[0])) {
				$msg_erro["msg"][] = $s3->_erro;
			}
		} else {
			$imagem_dir = "../comunicados/".$comunicado.".".pg_fetch_result($res,0,0);

			if (is_file($imagem_dir)){
				if (!unlink($imagem_dir)){
					$msg_erro["msg"][] = "Não foi possível excluir arquivo";
				}
			}
		}
		if (count($msg_erro["msg"]) == 0) {
			$sql = "UPDATE tbl_comunicado SET extensao = NULL WHERE comunicado = $comunicado";
			$res = pg_query($con,$sql);
			header("Location: $PHP_SELF?msg=Excluído com sucesso");
			exit;
		}
	}
}

if ($admin_es) {
	$layout_menu = "tecnica";
	$title = "Publicación de comunicados / fotos/ informativos";
	$titulo = "Publicación de comunicados / fotos/ informativos";
	echo "<link media='screen' type='text/css' rel='stylesheet' href='bootstrap/css/bootstrap.css' />";
    echo "<link media='screen' type='text/css' rel='stylesheet' href='bootstrap/css/extra.css' />";
    echo "<link media='screen' type='text/css' rel='stylesheet' href='css/tc_css.css' />";
    echo "<link media='screen' type='text/css' rel='stylesheet' href='plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css'>";
    echo "<link media='screen' type='text/css' rel='stylesheet' href='bootstrap/css/ajuste.css' />";

    echo "<script type='text/javascript' src='plugins/posvenda_jquery_ui/js/jquery-1.9.1.js'></script>";
    echo "<script type='text/javascript' src='plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js'></script>";
    echo "<script type='text/javascript' src='plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js'></script>";
    echo "<script type='text/javascript' src='plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js'></script>";
    echo "<script type='text/javascript' src='plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js'></script>";
    echo "<script type='text/javascript' src='plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js'></script>";
    echo "<script type='text/javascript' src='bootstrap/js/bootstrap.js'></script>";
	include 'cabecalho.php';

	echo "<script type='text/javascript' src='../admin/js/fckeditor/fckeditor.js'></script>";
	echo "<script type='text/javascript' src='../admin/js/jquery.mask.js'></script>";
	echo "<script src='../plugins/shadowbox/shadowbox.js' type='text/javascript'></script>";
	echo "<link rel='stylesheet' type='text/css' href='../plugins/shadowbox/shadowbox.css' media='all'>";
	echo "<script src='../plugins/select2/select2.js' type='text/javascript'></script>";
	echo "<link rel='stylesheet' type='text/css' href='../plugins/select2/select2.css' media='all'>";
	echo "<div class='container'>";
	$plugins = array(
	    "autocomplete",
	    "shadowbox",
	    "tooltip",
	   	"select2",
	    "datepicker",
	    "multiselect",
	);
	include("plugin_loader.php");
}else{
	$layout_menu = "tecnica";
	$titulo = traduz("CADASTRO DE COMUNICADOS /  FOTOS / BOLETINS");
	$title = traduz("CADASTRO DE COMUNICADOS /  FOTOS / BOLETINS");

	include 'cabecalho_new.php';

	$plugins = array(
	    "autocomplete",
	    "shadowbox",
	    "tooltip",
	   	"select2",
	    "datepicker",
	    "multiselect",
	);
	include("plugin_loader.php");
	echo "<script type='text/javascript' src='js/fckeditor/fckeditor.js'></script>";
	echo "<script type='text/javascript' src='js/jquery.mask.js'></script>";
}

?>
<script language='javascript'>

// Grava um ou mais vídeos no banco de dados
// ##
function gravarVideo(){
    var formData = [];
    var containerVideo = $('.container-video .box-video');
    $("#btn-gravar-video").prop("disabled", true);

    // Recupera todos os dados que serão enviados para o servidor
    containerVideo.each(function(i, boxVideo){
        var idBox = $(boxVideo).prop('data-id');
        var data = {
                'box-id': idBox
        };

        $(boxVideo).find('input').each(function(j, input){
                input = $(input);

                if( input.prop('type') == 'radio' && input.prop('checked') == true )
                        data[input.prop('name')] = input.val(); 
                
                if( input.prop('type') == 'text' )
                        data[input.prop('name')] = input.val(); 
        });

        // Adiciona os dados ao formulario que sera submetido
        formData.push(data);
    });

    $.post(window.location, {
        ajax_adicionar_comunicado: true, 
        data: formData,
        link_tela: $('#link_tela').val()
    }).done(function(response){
        response = JSON.parse(response);

        // Limpa todos os erros anteriores
        $('.help-inline').remove();
        $('.error').removeClass('error');

        if( response.error == true ){
            // Verifica os erros e adiciona as classes de erro
            response['inputs'].forEach(function(el, index){
                var parentContainer = $('#' + el).parent();
                parentContainer.append("<small class='help-inline' style='margin-top: -15px'> Este campo é obrigatório </small>");

                var parentControlGroup = $('#' + el).parent().parent().parent();
                parentControlGroup.addClass('error');
            });
        }else{
            alert('Cadastro efetuado com sucesso!');
            window.location.href = window.location;
            $("#btn-gravar-video").prop("disabled", false);
        }
    });
}

// ##
// Funcao para adicionar novo video 
function adicionarVideo(){
    // Container principal de todos os vídeos
    var containerVideo = $('.container-video');
    // Obtem um clone do ultimo blox adicionado
    var novoBoxVideo = $('.box-video').last().clone().addClass('adicionado-dinamicamente');

    // Remove as mensagens de erro caso tenha
    novoBoxVideo.find('.error').each(function(index, el){
        $(el).removeClass('error');
    });
    novoBoxVideo.find('.help-inline').remove();

    // Obtem o id
    var idVideo = novoBoxVideo.data('id');
    // Muda o id do box para o proximo número subsequente
    novoBoxVideo.attr('data-id', ++idVideo);

    // Configura os relacionados ao Titulo do Video
    var boxVideoTitulo = novoBoxVideo.find('.box-video-titulo');
    var videoTitulo = 'titulo_video_' + idVideo; 
    boxVideoTitulo.find('input')
	    .attr('id', videoTitulo)
	    .attr('name', videoTitulo)
	    .val('');
    boxVideoTitulo.find('label').attr('for', videoTitulo);

    // Configura os relacionados ao Link do Video
    var boxVideoLink = novoBoxVideo.find('.box-video-link');
    var linkVideo = 'link_video_' + idVideo;
    boxVideoLink.find('input')
        .attr('id', linkVideo)
        .attr('name', linkVideo)
        .val('');
    boxVideoLink.find('label').attr('for', linkVideo);

    // Configura os relacionados ao Status do Video
    var boxVideoStatus = novoBoxVideo.find('.box-video-status');
    var statusVideo = 'radio_ativo_' + idVideo; 
    boxVideoStatus.find('input').each(function(index, input){
        $(input)
            .attr('name', statusVideo)
            .attr('id', statusVideo);
    });
    boxVideoStatus.find('label').attr('for', statusVideo);

    // Adiciona um botão de remover para remover o box
    var btnRemover = document.createElement('button');
    btnRemover.classList = 'btn btn-danger btn-remover';
    btnRemover.textContent = 'Remover';
    btnRemover.style = 'margin-left: calc(50% - 50px)';
    btnRemover.onclick = function(){
        $("[data-id="+ idVideo +"]").remove()
    }

    // Remove os outros btn remover, como tudo esta sendo clonado, os próximos clones terão esse botão remover
    // Então é removido todos os btn remover e aidicionado o novo com as configurações certas
    novoBoxVideo.find('.btn-remover').remove();
    novoBoxVideo.append(btnRemover);
    
    // Adiciona o proximo box no container principal dos vídeos
    containerVideo.append(novoBoxVideo);
}

function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}
}

<?php if($login_fabrica == 152){ ?>
	function tipo_comunicado(){
		$("select[name=tipo]").change(function() {
			verifica_comunicado();
		});
	}

	function verifica_comunicado(){
		var tipo_comunicado = $('select[name=tipo] option:selected').val();

	    switch (tipo_comunicado) {
	      case 'Contrato':
	      	$("#obrigatorio_site").prop( "checked", true );
	      break;
	    }
	}

	$(document).ready(function() {
		tipo_comunicado();
	});

<?php } ?>

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
function addIt2() {
	var fabrica = <?=$login_fabrica?>;

	if ($('#codigo_posto').val()=='')
		return false;

	if ($('#descricao_posto').val()=='')
		return false;

	var pickList = document.getElementById("multi_posto");
	var pickOptions = pickList.options;
	var pickOLength = pickOptions.length;

	pickOptions[pickOLength] = new Option($('#codigo_posto').val()+" - "+ $('#descricao_posto').val());
	pickOptions[pickOLength].value = $('#codigo_posto').val();

	$('#codigo_posto').val("");
	$('#descricao_posto').val("");

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
	$('#codigo_posto').focus();

}

// Adds a selected item into the picklist
function addIt() {

	var fabrica = <?=$login_fabrica?>;

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

}

/*-----------------------------------------*/
function addItPeca() {

	if ($('#peca_referencia_multi').val()=='')
		return false;

	if ($('#peca_descricao_multi').val()=='')
		return false;


	var pickList = document.getElementById("PickListPeca");
	var pickOptions = pickList.options;
	var pickOLength = pickOptions.length;
	pickOptions[pickOLength] = new Option($('#peca_referencia_multi').val()+" - "+ $('#peca_descricao_multi').val());
	pickOptions[pickOLength].value = $('#peca_referencia_multi').val();

	$('#peca_referencia_multi').val("");
	$('#peca_descricao_multi').val("");

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
	$('#peca_referencia_multi').focus();

}
/*--------------------------------------*/
// Deletes an item from the picklist

function delIt2() {
  var pickList = document.getElementById("multi_posto");
  var pickIndex = pickList.selectedIndex;
  var pickOptions = pickList.options;
  while (pickIndex > -1) {
    pickOptions[pickIndex] = null;
    pickIndex = pickList.selectedIndex;
  }
}

function delIt() {
  var pickList = document.getElementById("PickList");
  var pickIndex = pickList.selectedIndex;
  var pickOptions = pickList.options;
  while (pickIndex > -1) {
    pickOptions[pickIndex] = null;
    pickIndex = pickList.selectedIndex;
  }
}

function delItPeca() {
  var pickList = document.getElementById("PickListPeca");
  var pickIndex = pickList.selectedIndex;
  var pickOptions = pickList.options;
  while (pickIndex > -1) {
    pickOptions[pickIndex] = null;
    pickIndex = pickList.selectedIndex;
  }
}

// Selection - invoked on submit
function selIt2(btn) {
	var pickList = document.getElementById("multi_posto");
	if (pickList == null) return true;
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

// Selection - invoked on submit
function selIt(btn) {
	var pickList = document.getElementById("PickList");
	if (pickList == null) return true;
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

// Selection - invoked on submit
function selItPeca(btn) {
	var pickList = document.getElementById("PickListPeca");
	var pickOptions = pickList.options;
	var pickOLength = pickOptions.length;
	for (var i = 0; i < pickOLength; i++) {
		pickOptions[i].selected = true;
	}
}

function verifica_comunicado_extrato(){

	if ( $('#tipo').val() == 'Extrato' ){
		if ($("input[name=ativo]:radio").val() == "t" ){
			$.get('<?=$PHP_SELF?>',
			  {'ajax': 'ver_comunicado_extrato'},
			  function(responseText) {

                    var response = responseText.split("|");

                    if (response[0]=="1"){
                    	if ( $("input[name=ativo]:radio:checked").val() == 't' ){

	                    	if (confirm('Ja existe um comunicado do tipo \'Extrato\' ativo, deseja realmente substituir?')) {
	                    		document.frm_comunicado.btn_acao.value="gravar";
								document.frm_comunicado.submit()
	                    	}

                    	}else{
                    		document.frm_comunicado.btn_acao.value="gravar";
							document.frm_comunicado.submit()
                    	}
                    }else{
                        document.frm_comunicado.btn_acao.value="gravar";
						document.frm_comunicado.submit()
                    }
			});

		}else{
			document.frm_comunicado.btn_acao.value="gravar";
			document.frm_comunicado.submit()
		}
	}else{
		document.frm_comunicado.btn_acao.value="gravar";
		document.frm_comunicado.submit()
	}

}

<?php if($login_fabrica == 1){?>
	window.onload = function(){
		var oFCKeditor = new FCKeditor( 'mensagem', 640 ) ;
		oFCKeditor.BasePath = "js/fckeditor/" ;
		oFCKeditor.ToolbarSet = 'Chamado' ;
		oFCKeditor.ReplaceTextarea();
	}
<?php } ?>

	$(function() {
		Shadowbox.init();
		$("#unidadenegocio").select2();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this), Array("posicao"));
        });

        $(".ver-comunicados").click(function(){

        	let comunicado = $(this).data("comunicado");

        	Shadowbox.open({
				content :   "exibe_anexos_boxuploader.php?comunicado=" + comunicado,
				player  :   "iframe",
				title   :   "Anexos do Comunicado",
				width   :   800,
				height  :   600
			});

        });

        <?php if ($login_fabrica == 177){ ?>
        	$("#estado").select2();
        <?php } ?>
		$('#tipo').change(function() {
            desbloqueiaFormulario($(this).val());
            exibirDuracaoVideo($(this).val());
		});

        function desbloqueiaFormulario(tipo) {
            if (['Extrato','Comunicado por tela','Tabela de precos'].indexOf(tipo)>-1) {
                $('input,select,radio').each(function() {
                    $("#tipo").attr('disabled',true);
                    $('#tipo,#ativo1,#ativo2,#arquivo').attr('disabled',false);
                    $("input[name=descricao]").attr('disabled',false);
                    $("input[type=button]").attr('disabled',false);
                    $("input[name=btn_acao]").attr('disabled',false);
                    $("input[name=comunicado]").attr('disabled',false);
                });

                if (tipo == 'Extrato'){
                    $('#linha_link_tela').hide();
                } else if(tipo == 'Comunicado por tela'){
                    $('#linha_link_tela').show();
                    $('input[name=link_tela]').attr('disabled',false);
                }

            }else{
                $('input,select,radio').each(function(){
                    $(this).attr('disabled',false);
                    $('#linha_link_tela').hide();
                });
            }
        }

        desbloqueiaFormulario($('#tipo').val());

        $("#btnPopover").popover();
        $("#btnPopover1").popover();
        $("#btnPopover2").popover();

        $.autocompleteLoad(["posto"]);

        $(".duracao_class").css("display", "none");
        $("#duracao").mask("00:00:00");

        exibirDuracaoVideo($("#tipo").val());

        $(".tipo_comunicado").change(function(){
        	var tipo_comunicado = $(this).val();
        	if(tipo_comunicado == "video_explicativos"){
        		/*$("#campos").val($(".testes").html());
				$(".testes").text("");*/

				// mostra div com tudo
				$(".container-video").show();

				$(".video_explicativo").css("display", "none");
				$(".tela").css("display", "block");
				// Limpa o campo link_tela
                $('#link_tela').val('');
                // Mostra o botão que de adicionar novo vídeo
                $('#btn-adicionar-video').css('display', 'block');
                // Remove todos os formularios de videos adicionados dinamicamentes
                $('.adicionado-dinamicamente').remove();
                // Mostra o botão de gravar que dispara a requisição ajax para realmente gravar os videos no banco de dados
                $('#btn-gravar-video').show();
                // Esconde o botão antigo de gravar
                $('#btn-gravar-old').hide();
                // Troca o name e o id do input de status
        	}else{
        		/*$(".testes").html($("#campos").val());
        		$("#campos").val("");*/
        		// tira div com tudo
				$(".container-video").hide();

        		$(".tela").css("display", "none");
        		$(".video_explicativo").css("display", "block");
        		// Limpa o campo link_tela
                $('#link_tela').val('');
                // Esconde o botão de adicionar video
                $('#btn-adicionar-video').css('display', 'none');
                // Remove todos os formularios de videos adicionados dinamicamentes
                $('.adicionado-dinamicamente').remove();
                // Mostra o botão de gravar antigo
                $('#btn-gravar-old').show();
                // Esconde o botão de gravar que dispara a requisição ajax
                $('#btn-gravar-video').hide();
                // Limpa todos os erros anteriores
                $('.help-inline').remove();
                $('.error').removeClass('error');
        	}
        });
    });

    // Limpa o campo link_tela
	$('#link_tela').val('');

    /*HD - 4060618*/
	function exibirDuracaoVideo(tipo) {
		var display = "none";
		
		if (tipo == "Treinamento Telecontrol" || tipo == "Video") {
			display = "block";
		}
		
		$(".duracao_class").css("display", display);
	}

	function retorna_produto (retorno) {
		console.log(retorno);
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

    function retorna_peca(retorno){

		var posicao = retorno.posicao;

        if(retorno.posicao == "uma_peca"){

			$("#peca_referencia").val(retorno.referencia);
        	$("#peca_descricao").val(retorno.descricao);

		}else if(retorno.posicao == "multi_peca"){

			$("#peca_referencia_multi").val(retorno.referencia);
        	$("#peca_descricao_multi").val(retorno.descricao);

		}

    }

    function retorna_posto(retorno){
	    $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	$(document).ready(function(){
		$("#tipo").change(function(){
			if($(this).val() == 'Acessório'){
				var pergunta = confirm("Este comunicado serve para avisar o posto sobre pedido de acessório, deseja cadastrar ou mudar o conteúdo cadastrado");
				if(!pergunta){
					$("#tipo").val("");
				}
			}

			verificaAcessorio();
		});

		verificaAcessorio();
	});

	function verificaAcessorio(){
		var tipoComunicado = $("#tipo").val();

		if(tipoComunicado == 'Acessório'){
			 $("#selecionarPosto").fadeOut('1000');
			 $("#tipoPosto").fadeOut('1000');
			 $("#postoPodeDigitar").fadeOut('1000');
			 $("#tr_produto").fadeOut('1000');
			 $("#tr_linha").fadeOut('1000');

			 $("#selecionarPosto input").val("");
			 $("#tipoPosto select").val("");
			 $("#tr_produto input").val("");
			 $("#tr_linha select").val("");
		}else{
			 $("#selecionarPosto").fadeIn('1000');
			 $("#tipoPosto").fadeIn('1000');
			 $("#postoPodeDigitar").fadeIn('1000');
			 $("#tr_produto").fadeIn('1000');
			 $("#tr_linha").fadeIn('1000');
		}
	}


function toogleProd(radio){

	var obj = document.getElementsByName('radio_qtde_produtos');
	/*for(var x=0 ; x<obj.length ; x++){*/

	if(!confirm('Deseja continuar? As Informações de Número de Série e Produto serão perdidas.')) {
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
}

function tooglePeca(radio){

	var obj = document.getElementsByName('radio_qtde_pecas');
	/*for(var x=0 ; x<obj.length ; x++){*/

	if (obj[0].checked){
		$('#id_dois').show("slow");
		$('#id_peca_multi').hide("slow");
	}
	if (obj[1].checked){
		$('#id_dois').hide("slow");
		$('#id_peca_multi').show("slow");
	}
}

function addPalavraChave(){

	if($('#palavra_chave').val() !== ''){

		var select = $('#palavras_chave');
		var texto = $('#palavra_chave').val().toLowerCase();

		var data = {
			value: str = texto.replace(/\s+/g, '')
		};

		var newOption = new Option(data.value, data.value, false, true);
		select.append(newOption).trigger('change');

		$('#palavra_chave').val('');
	}
}

$(function () {
	<?php
	if ($login_fabrica == 163) {
	?>
		$("#tipo").on("change", function() {
			if ($(this).val() == "Laudo Tecnico") {
				$("div.alert-laudo-tecnico").show();
			} else {
				$("div.alert-laudo-tecnico").hide();
			}
		});

		$("#tipo").trigger("change");
	<?php
	} if(in_array($login_fabrica,array(148, 161, 152, 180, 181, 182))) { 
	?>
		$("#linha").multiselect({
			selectedText: "selecionados # de #"			
		});

		$("#familia").multiselect({
			selectedText: "selecionados # de #"
		});
	<?php } ?>

    $("a[name=prod_ve]").click(function () {
	    var attr = $(this).attr("rel").split("/");
	    var comunicado = attr[0];
        var tipo = attr[1];
        var nova = true;

        $("#before-"+comunicado).html("<em>aguarde...</em>");

        $.ajaxSetup({
        	async: true
        });
        $.get("../verifica_s3_comunicado.php", { comunicado: comunicado,fabrica:"<?=$login_fabrica?>",tipo: tipo, tela: nova}, function (url) {
		  	if (url.length > 0) {

				Shadowbox.init();

				if(url.search(/.(pdf|xlsx?)/g) != -1){

					window.open(url, "_blank");

				}else{

                    Shadowbox.open({
                    	player  : "html",
                    	content : "<div style='overflow-y: scroll; width: 800px; height: 600px'><img src='"+url+"' style='width: 100%;'></div>",
                    	height: 600,
                    	width: 800
					});

				}

				$("#before-"+comunicado).html("");

			} else {
				$("#before-"+comunicado).html("");
                alert("Arquivo não encontrado!");
            }

        });
    });
});

var popupBlockerChecker = {
	check: function(popup_window) {
		var _scope = this;

		if (popup_window) {
			if (/chrome/.test(navigator.userAgent.toLowerCase())) {
				setTimeout(function() {
					_scope._is_popup_blocked(_scope, popup_window);
				}, 500);
			}else{
				popup_window.onload = function() {
					_scope._is_popup_blocked(_scope, popup_window);
				};
			}
		}else{
			_scope._displayMsg();
		}
	},
	_is_popup_blocked: function(scope, popup_window){
		if ((popup_window.screenX > 0) == false) {
			scope._displayMsg();
	    }
	},
	_displayMsg: function() {
		Shadowbox.init();

		Shadowbox.open({
			content :   "../popup_bloqueado.php",
			player  :   "iframe",
			title   :   "POPUP BLOQUEADO",
			width   :   800,
			height  :   600
		});
	}
};
</script>

<?php
 $msg = $_GET['msg'];
if ( $login_fabrica==3 && strlen($comunicado) > 0 && strlen ($produto_referencia)>0 )
	echo '<body onLoad="initIt();">';
else
	echo '<body>';

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br />", $msg_erro["msg"]); ?></h4>
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

$sql_admin = "SELECT privilegios FROM tbl_admin WHERE admin = $login_admin";
$res_admin = pg_query($con, $sql_admin);

if(pg_num_rows($res_admin)>0){
	$privilegios = pg_fetch_result($res_admin, 'privilegios');
}
?>

<style>

	.palavras-chave{
		padding-bottom: 15px;
	}

	.palavras-chave > span.select2-container{
		width: 100% !important;
	}

	.palavras-chave > span.select2-container > span.selection > span.select2-selection--multiple{
		min-height: 85px;
	}

	#select2-palavras_chave-results{
		display: none !important;
	}
	
</style>

<div class="row">
	<strong class="obrigatorio pull-right"> * <?=traduz('Campos obrigatórios')?> </strong>
</div>
<form enctype="multipart/form-data" name="frm_comunicado" method="post" action="<?= $PHP_SELF."?comunicado={$comunicado}"; ?>" class="tc_formulario">

	<input type="hidden" name="comunicado"    value="<?=$comunicado?>">
	<input type='hidden' name='apagar'        value=''>
	<input type='hidden' name='btn_acao'      value=''>
	<input type="hidden" name="posto" value="<?=$posto?>">
	<input type="hidden" name="extensao" value="<?=$extensao?>">

	<div class="titulo_tabela"><?php echo traduz("Informações de Cadastro"); ?></div>

	<br />

		<div class="row-fluid">
		<div class="span2"></div>
		<input type="hidden" name="campos" id="campos" value="">
		<div class="span4">
			<div class='control-group <?=(in_array("tipo_comunicado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'><?php echo traduz("Tipo do Comunicado") ?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<?php

							$sel_tipos = include('menus/comunicado_option_array.php');
							$new_tipos[] = '';
							foreach ($sel_tipos as $key => $value) {
								$new_tipos[$key] = $value;
							}
							
							if($login_fabrica == 117 and $privilegios != '*'){
								unset($new_tipos['Apresentação do Produto']);
								unset($new_tipos['Com. Unico Posto']);
								unset($new_tipos['Estrutura do Produto']);
								unset($new_tipos['Foto']);
								unset($new_tipos['Manual']);
								unset($new_tipos['Orientação de Serviço']);
								unset($new_tipos['Procedimentos']);
								unset($new_tipos['Promocao']);
								unset($new_tipos['Comunicado']);
							}
							if (in_array($login_fabrica, [30])) {
                                                                unset($new_tipos['Alterações Técnicas']);
                                                                unset($new_tipos['Descritivo técnico']);
                                                                unset($new_tipos['Manual Técnico']);
                                                                unset($new_tipos['Orientação de Serviço']);
                                                                unset($new_tipos['Procedimentos']);
                                                                unset($new_tipos['Contrato']);
                                                                unset($new_tipos['']);
                                                                unset($new_tipos['0']);
                                                                $new_tipos['Manual administrativo'] = "Manual administrativo";
                                                                $new_tipos['Defeito constatado'] = "Defeito constatado";
							}

							if (in_array($login_fabrica, [35])) {
								$new_tipos['Advertência'] = "Advertência";
							}

							asort($new_tipos);
							$usakey =  ($login_fabrica == 186) ? false : true;
							echo array2select('tipo', 'tipo', $new_tipos, $tipo, "class='span11 tipo_comunicado'", "Selecione",$usakey);
						?>
					</div>
				</div>
			</div>
		</div>
	
		<?php if (in_array($login_fabrica, array(148, 161, 152, 180, 181, 182))) { ?>
		</div>
			<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class='control-group familia <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'><?php echo traduz("Linha"); ?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<?php
								$sql = "SELECT tbl_linha.linha, tbl_linha.nome FROM tbl_linha WHERE tbl_linha.fabrica = " . $login_fabrica . " ORDER BY tbl_linha.nome ASC";

								$res = pg_query ($con,$sql);

								if (pg_num_rows($res) > 0) {
									echo "<select class='span12' name='linha[]' id='linha' multiple='multiple'>";			

									for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
										$aux_cod_linha 	= trim(pg_fetch_result($res, $x,"linha"));
										$aux_nome  = trim(pg_fetch_result($res, $x,"nome"));

										echo "<option value='$aux_cod_linha'"; 
										if (in_array($aux_cod_linha, $mult_linha)) {
											echo " SELECTED "; 
										}
										echo ">" . $aux_nome . "</option>";
									}
									echo "</select>";								}
							?>						
						</div>
					</div>
				</div>
			</div>

		<?php } if (!in_array($login_fabrica, array(117))) {//HD 198907 ?>

		<div class="span4 video_explicativo"  style="<?=$video_explicativo_display ?>"> 
			<div class='control-group'>

				<label class='control-label' for='familia'><?php echo traduz("Família"); ?></label>
				<div class='controls controls-row'>
					<div class='span12'>

						<?php

							if($login_fabrica == 30){
								$condFamilia = " AND tbl_familia.ativo is true";
							}
							
							if ($admin_es && $login_fabrica == 20) {
								$sql = "SELECT familia, tbl_familia_idioma.descricao FROM tbl_familia JOIN tbl_familia_idioma USING (familia) WHERE fabrica = $login_fabrica AND idioma = 'ES' ORDER BY tbl_familia_idioma.descricao";
							}else{
								$sql = "SELECT *
									FROM tbl_familia
									WHERE tbl_familia.fabrica = $login_fabrica
									$condFamilia
									ORDER BY tbl_familia.descricao;";
							}
							
							$res = pg_query ($con,$sql);

							if (pg_num_rows($res) > 0) {
								if(in_array($login_fabrica,array(148,161))) {
									echo "<select class='span12' name='familia[]' id='familia' multiple='multiple'>";
								} else {
									echo "<select class='span12' name='familia' id='familia'>";	
								}
								echo "<option value=''>Selecione</option>";
								for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
									$aux_familia 	= trim(pg_fetch_result($res, $x,"familia"));
									$aux_descricao  = trim(pg_fetch_result($res, $x,"descricao"));
									echo "<option value='$aux_familia'"; 

									if (in_array($aux_familia, $mult_familia)) {
										echo " SELECTED ";
									}
									echo ">" . $aux_descricao . "</option>";
								}															
								echo "</select>";
							}
						?>
					</div>
				</div>
			</div>
		</div>		
	
	<?php }
	 if(in_array($login_fabrica, array(148))) { 
	 	echo "</div>"; 
	 } else {
	 	echo "</div>";
	 }

	 if ($login_fabrica == 175){ ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class='control-group'>
					<label class='control-label' for='comunicado_destino'>Enviar para:</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select class='span12' name='comunicado_destino' id='comunicado_destino'>
								<?php 
									if ($comunicado_destino == 't'){
										$selected_d = "selected";
									}else{
										$selected_d = "";
									}

								?>
								<option value='f'>Posto Autorizado</option>;
								<option value='t' <?=$selected_d?> >Técnicos</option>;
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
	<?php
	if ($login_fabrica == 163) {
	?>
		<div class="row-fluid alert-laudo-tecnico" >
			<div class="span2" ></div>
			<div class="span8" >
				<div class="alert alert-info" >
					Para gravar o Laudo Técnico apenas informe o anexo
				</div>
			</div>
		</div>
	<?php
	}
	?>

	<?php
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

	<div class="row-fluid video_explicativo" style="<?=$video_explicativo_display ?>">
		<div class="span2"></div>

		<div class="span8">

			<div class="row-fluid">

				<div class="span6">

					<?php
					$titulo_produto = "
						Para selecionar vários produtos, clique na opção Vários Produtos e
						adicione os produtos a lista. Todos os produtos da lista serão
						referenciados ao comunicado. Para remover algum produto,
						selecione-o na lista e clique no botão Remover à seguir.
					";
					?>

					<div class='control-group'>
						<label class='control-label' for='codigo_posto'>
							<?php echo traduz("Comunicados para:"); ?>
							<i id="btnPopover2" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Informação" data-content="<?php echo $titulo_produto; ?>" class="icon-question-sign"></i>
						</label>
						<div class='controls controls-row ' >
							<div class='span12 input-append'>
								<label class="checkbox">
									<input type="radio" name="radio_qtde_produtos" id="radio_qtde_produtos1" value='um' <?=$display_um?> onClick='javascript:toogleProd(this)'>
								  	<?php echo traduz("Um produto"); ?>
								</label>
							</div>
						</div>
<?php if($login_fabrica == 1){ ?>
						<div class='controls controls-row tela'  style='<?=$tela_video_explicativo_display?>' >
							<div class='span12 input-append'>
								<label class="checkbox">
									<input type="radio" name="radio_tela" id="radio_tela" value='tela' >
								  	<?php echo traduz("Tela"); ?>
								</label>
							</div>

						</div>
<?php } ?>
					</div>

				</div>

				<div class="span6 "  style="<?=$video_explicativo_display ?>">

					<div class='control-group'>

						<label class='control-label' for='codigo_posto'>&nbsp;</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<label class="checkbox">
									<input type="radio" name="radio_qtde_produtos" id="radio_qtde_produtos2" value='muitos' <?=$display_multi?> onClick='toogleProd(this)'>
								  	<?php echo traduz("Vários Produtos"); ?>
								</label>
							</div>
						</div>
					</div>

				</div>

			</div>

		</div>

	</div>
<div class='video_explicativo'  style="<?=$video_explicativo_display ?>">
	<div id='id_um' style='<?php echo $display_um_produto;?>'>
		<?php if (in_array($login_fabrica, array(169,170))){ ?>
		<div class='row-fluid'>
			<div class="span2"></div>
			<div class="span4">
	            <div class='control-group <?=(in_array('produto_serie', $msg_erro['campos'])) ? "error" : "" ?>' >
	                <label class="control-label" for="produto_serie">Número de Série</label>
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
	                <label class='control-label' for='produto_referencia'><?php echo traduz("Ref. Produto"); ?></label>
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
	                <label class='control-label' for='produto_descricao'><?php echo traduz("Descrição Produto"); ?></label>
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

    </div>

    <!-- Multi Produtos -->
    <div id='id_multi' style='<?php echo $display_multi_produto;?>'>

    	<div class='row-fluid'>

	        <div class='span2'></div>

	        <div class='span2'>
	            <div class='control-group'>
	                <label class='control-label' for='produto_referencia_multi'><?php echo traduz("Ref. Produto"); ?></label>
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
	                <label class='control-label' for='produto_descricao_multi'><?php echo traduz("Descrição Produto"); ?></label>
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
	    	<?php echo traduz("(Selecione o produto e clique em <strong>Adicionar</strong>)"); ?>
	    </p>

	    <div class='row-fluid'>

	        <div class='span2'></div>

	        <div class='span8'>

	        	<select multiple size='5' id="PickList" name="PickList[]" class='span12'>

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
					<input type="button" value="Remover" onclick="delIt();" class='btn btn-danger' style="width: 126px;">
				</p>

	        </div>

	        <div class='span2'></div>

	    </div>

	</div>

	<?php

	if($login_fabrica == 50){

		if (count($lista_pecas) > 0){
			$display_um_pecas    = "display:none";
			$display_multi_pecas = "";
			$display_um            = "";
			$display_multi         = " CHECKED ";
		}else{
			$display_um_pecas    = "";
			$display_multi_pecas = "display:none";
			$display_um            = " CHECKED ";
			$display_multi         = "";
		}

	?>

		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span8">

				<div class="row-fluid">

					<div class="span6">

						<?php
						$titulo_peca = "
							Para selecionar várias peças, clique em Várias Peças e
							adicione as peças a lista. Todas as peças da lista serão
							referenciadas ao comunicado. Para remover alguma peça,
							selecione-a na lista e clique no botão Remover
						";
						?>

						<div class='control-group'>

							<label class='control-label' for='codigo_posto'>
								Comunicados para:
								<i id="btnPopover1" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Informação" data-content="<?php echo $titulo_peca; ?>" class="icon-question-sign"></i>
							</label>
							<div class='controls controls-row'>
								<h5 class='asteristico'>*</h5>
								<div class='span12 input-append breadcrumb'>
									<label class="checkbox">
										<input type="radio" name="radio_qtde_pecas" value='um' <?=$display_um?> onClick='javascript:tooglePeca(this)'>
									  	Uma peca
									</label>
								</div>

							</div>

						</div>

					</div>

					<div class="span6">

						<div class='control-group'>

							<label class='control-label' for='codigo_posto'>&nbsp;</label>
							<div class='controls controls-row'>
								<div class='span12 input-append breadcrumb'>
									<label class="checkbox">
										<input type="radio" name="radio_qtde_pecas" value='muitos' <?=$display_multi?> onClick='javascript:tooglePeca(this)'>
									  	Várias Peças
									</label>
								</div>
							</div>
						</div>

					</div>

				</div>

			</div>

		</div>

		<div id="id_dois" style='<?php echo $display_um_pecas;?>'>

			<div class="row-fluid">

				<div class="span2"></div>

				<div class="span3">
					<div class='control-group' >
						<label class="control-label">Ref. Peça</label>
						<div class="controls controls-row">
							<div class="span9 input-append">
								<input  name="peca_referencia" id="peca_referencia" class="span12" type="text" value="<?php echo $peca_referencia; ?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" posicao="uma_peca" />
							</div>
						</div>
					</div>
				</div>

				<div class="span5">
					<div class='control-group' >
						<label class="control-label" >Descrição Peça</label>
						<div class="controls controls-row">
							<div class="span11 input-append">
								<input name="peca_descricao" id="peca_descricao" class="span12" type="text" value="<?php echo $peca_descricao; ?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" posicao="uma_peca" />
							</div>
						</div>
					</div>
				</div>

			</div>

		</div>

		<div id="id_peca_multi" style='<?php echo $display_multi_pecas;?>'>

			<div class="row-fluid">

				<div class="span2"></div>

				<div class="span2">
					<div class='control-group' >
						<label class="control-label">Ref. Peça</label>
						<div class="controls controls-row">
							<div class="span9 input-append">
								<input  name="peca_referencia_multi" id="peca_referencia_multi" class="span12" type="text" value="<?php echo $peca_referencia; ?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" posicao="multi_peca" />
							</div>
						</div>
					</div>
				</div>

				<div class="span4">
					<div class='control-group' >
						<label class="control-label" >Descrição Peça</label>
						<div class="controls controls-row">
							<div class="span11 input-append">
								<input name="peca_descricao_multi" id="peca_descricao_multi" class="span12" type="text" value="<?php echo $peca_descricao; ?>" />
								<span class="add-on" rel="lupa" >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" posicao="multi_peca" />
							</div>
						</div>
					</div>
				</div>

				<div class="span2">
					<label>&nbsp;</label>
					<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='btn btn-success' onclick='addItPeca();' style="width: 100%;" />
				</div>

			</div>

			<p class="tac">
		    	(Selecione a peça e clique em <strong>Adicionar</strong>)
		    </p>

		    <div class='row-fluid'>

		        <div class='span2'></div>

		        <div class='span8'>

		        	<select multiple size='5' id="PickListPeca" name="PickListPeca[]" class='span12'>

					<?php
					if (count($lista_pecas)>0){
						for ($i=0; $i<count($lista_pecas); $i++){
							$linha_pecas = $lista_pecas[$i];
							echo "<option value='".$linha_pecas[1]."'>".$linha_pecas[1]." - ".$linha_pecas[2]."</option>";
						}
					}
					?>

					</select>

					<p class="tac">
						<input type="button" value="Remover" onclick="delItPeca();" class='btn btn-danger' style="width: 126px;">
					</p>

		        </div>

		        <div class='span2'></div>

		    </div>

		</div>

	<?php } ?>

	<div class="row-fluid">

		<div class='span2'></div>

        <div class='span8'>
            <div class='control-group <?=(in_array("titulo", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'><?php echo traduz("Titulo"); ?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
						<?php if(in_array($login_fabrica, array(42))){ ?>
						<h5 class='asteristico'>*</h5>
						<?php } ?>
                        <input type="text" name="descricao" value="<?php echo $descricao ?>" maxlength="50" class='span12'>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row-fluid">

     	<div class='span2'></div>

        <div class='span8'>
			<div class='control-group <?=(in_array("mensagem", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'><?php echo traduz("Mensagem"); ?></label>
                <?=(in_array("mensagem", $msg_erro["campos"])) ? "<h5 class='asteristico'>*</h5>" : ""?>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <textarea name='mensagem' rows='4' class='span12'><? echo $mensagem?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(in_array($login_fabrica, array(42))) : ?>

    <div class="row-fluid">
		<div class='span2'></div>
	        <div class='span8'>
				<div class='control-group'>
	                <label class='control-label' for='palavra_chave'><?php echo traduz("Palavra-chave"); ?></label>
	                <div class='controls controls-row'>
	                    <div class='span12 input-append'>
	                        <div class="input-group mb-3">
						  		<div class="input-group-append">
								  	<input type="text" class="form-control" name="palavra_chave" id="palavra_chave">
								    <button class="btn btn-outline-secondary" onclick="addPalavraChave();" type="button">Adicionar</button>
							  	</div>
						  	</div>
	                	</div>
	            	</div>
	        	</div>
	    	</div>
	    </div>
	</div>
  	<div class="row-fluid">
  		<div class='span2'></div>
 		<div class='span8 palavras-chave'>
        	<select name='palavras_chave[]' data-palavras="<?php echo $palavra_chave; ?>" multiple="multiple" id="palavras_chave"></select> 
        </div>
	</div>

    <?php endif; ?>

    <?php if(in_array($login_fabrica, array(1, 3, 117))){ ?>

    <div class="row-fluid">

		<div class='span2'></div>
		<?php if($login_fabrica == 1){?>
		<div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='produto_referencia_multi'>Categoria Posto</label>
                <div class='controls controls-row'>
                <select class='span12' name='categoria_posto'>
                	<option value="">Todos</option>
                	<option value="Autorizada" <?php echo ($categoria_posto == "Autorizada")? " selected ": "" ?> >Autorizada</option>
                    <option value="Locadora" <?php echo ($categoria_posto == "Locadora")? " selected ": "" ?>>Locadora</option>
                    <option value="Locadora Autorizada" <?php echo ($categoria_posto == "Locadora Autorizada")? " selected ": "" ?>>Locadora Autorizada</option>
                    <option value="Pre Cadastro" <?php echo ($categoria_posto == "Pre Cadastro")? " selected ": "" ?>>Pré Cadastro</option>
                    <option value="mega projeto" <?php echo ($categoria_posto == "mega projeto")? " selected ": "" ?>>Industria/Industria/Mega Projeto</option>
                </select>
                </div>
            </div>
        </div>
        <?php } ?>

        <?php
        if ($login_fabrica == 117) {?>
            <div class='span5'>
			    <div class='control-group'>
			        <label class='control-label' for='produto_referencia_multi'>Macro - Família</label>
			        <div class='controls controls-row'>
			            <div class='span10 input-append'>
			                <?php
                                $sql = "SELECT  DISTINCT tbl_linha.*
                                                FROM    tbl_linha
                                                        JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                        JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha AND tbl_macro_linha.ativo
                                                WHERE   tbl_linha.fabrica = $login_fabrica
                                                AND tbl_linha.ativo IS TRUE
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
        <?php
        } else { ?>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='produto_referencia_multi'>Linha</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <?php
						$sql = "SELECT  *
								FROM    tbl_linha
								WHERE   tbl_linha.fabrica = $login_fabrica
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
     	<?php
        }
        ?>
    </div>
    <?php } ?>

    <?php if(in_array($login_fabrica, array(11,50,172))){ ?>
    <div class="row-fluid">

     	<div class='span2'></div>

        <div class='span8'>
        	<div class='control-group'>
                <label class='control-label' for='video'>Vídeo <small class="text-info">(Digite ou copie o link direto - <strong>não o "Embed" e sim a "URL"</strong> - do vídeo no YouTube)</small></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                         <input type="text" accesskey="V" maxlength="255" name="video" size="85" class="span12" value='<?=$video?>'>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php } ?>

    <?php if(in_array($login_fabrica, array(42))){ ?>
    <div class="row-fluid">

     	<div class='span2'></div>

        <div class='span8'>
			<div class='control-group <?=(in_array("link", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='link'>Link <small class="text-info">(Digite ou copie o link direto)</small></label>
                <?=(in_array("link", $msg_erro["campos"])) ? "<h5 class='asteristico'>*</h5>" : ""?>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                         <input type="text" accesskey="V" maxlength="255" name="link" size="85" class="span12" value='<?=$link?>'>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="row-fluid duracao_class">
     	<div class='span2'></div>
        <div class='span8'>
			<div class='control-group <?=(in_array("duracao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='duracao'>Duração <small class="text-info">(Tempo de duração do vídeo)</small></label>
                <div class='controls controls-row'>
                   	<div class='span12 input-append'>
                     	<input type="text" maxlength="8" name="duracao" id="duracao" class="span4" value='<?=$duracao?>'>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>


	<?php 
	if (($login_fabrica == 35 && !empty($_POST['selecionar']))){ ?>
		<div class="span12">
			<div class='control-group'>
				<div class='controls controls-row'>
					<div class='span10 input-append tac'>
						<a class='btn btn-primary nova_linha_posto' data-acao="adicionar_posto">Adicionar</a>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			$('.nova_linha_posto').on('click', function(){
				var div = "<div class='row-fluid'>";
				div += "<div class='span2'></div>";
				div += "<div class='span3'>";
				div += "	<div class='control-group'>";
				div += "		<label class='control-label' for='codigo_posto'>Código Posto</label>";
				div += "		<div class='controls controls-row'>";
				div += "			<div class='span10 input-append'>";
				div += "				<input type='text' name='codigo_posto[]' id='codigo_posto' class='span12'  >";
				div += "				<span class='add-on' rel='lupa'>";
				div += "					<i class='icon-search' ></i>";
				div += "					<input type='hidden' name='lupa_config' tipo='posto' parametro='codigo' />";
				div += "				</span>";
				div += "			</div>";
				div += "		</div>";
				div += "	</div>";
				div += "</div>";
				div += "<div class='span5'>";
				div += "	<div class='control-group' >";
				div += "		<label class='control-label' for='descricao_posto'>Razão Social</label>";
				div += "		<div class='controls controls-row'>";
				div += "			<div class='span11 input-append'>";
				div += "				<input type='text' name='posto_nome' id='descricao_posto' class='span12'  >";
				div += "				<span class='add-on' rel='lupa'>";
				div += "					<i class='icon-search' ></i>";
				div += "					<input type='hidden' name='lupa_config' tipo='posto' parametro='nome' />";
				div += "				</span>";
				div += "			</div>";
				div += "		</div>";
				div += "</div>";
				div += "</div>";
				div += "</div>";
				$('.novo_posto_div').prepend(div);
				$("span[rel=lupa]").click(function () {
		        	$.lupa($(this), Array("posicao"));
		        });
			});
		</script>
		<div class="novo_posto_div">
			<div class="row-fluid">
				<div class="span2"></div>

				<div class="span3">
					<div class='control-group'>
						<label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
						<div class='controls controls-row'>
							<div class='span10 input-append'>

								<input type="text" name="codigo_posto[]" id="codigo_posto" class='span12'  >
								<span class='add-on' rel="lupa">
									<i class='icon-search' ></i>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
								</span>
							</div>

						</div>
					</div>
				</div>

				<div class="span5">

					<div class='control-group' >
						<label class='control-label' for='descricao_posto'><?php echo traduz("Razão Social"); ?></label>
						<div class='controls controls-row'>
							<div class='span11 input-append'>
								<input type="text" name="posto_nome" id="descricao_posto" class='span12'  >
								<span class='add-on' rel="lupa">
									<i class='icon-search' ></i>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php 
		foreach ($_POST['selecionar'] as $posto_codigo => $os) {
			$sql = "SELECT nome FROM tbl_posto_fabrica INNER JOIN tbl_posto USING(posto) where codigo_posto = '$posto_codigo'";
            $res = pg_query($con, $sql);
            $posto_nome = pg_fetch_result($res, 0, 'nome');	
            $string_os = implode(',', $os);
		?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span3">
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input readonly='readonly' type="text" name="codigo_posto[]" id="codigo_posto" class='span12' value="<?=$posto_codigo?>" >
						</div>

					</div>
				</div>
			</div>
			<div class="span5">
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'><?php echo traduz("Razão Social"); ?></label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input readonly='readonly' type="text" name="posto_nome" id="descricao_posto" class='span12' value="<?=$posto_nome?>" >
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span8">
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>OS's</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input readonly='readonly' type="text" name="os_posto['<?=$posto_codigo?>']" id="os_posto" class='span12' value="<?=$string_os?>" >
						</div>

					</div>
				</div>
				<hr/>
			</div>
		</div>

	<?php } 
	 } else { ?>
	    <div class="row-fluid">
			<div class="span2"></div>

			<div class="span2">
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>

							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?php echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</span>
						</div>

					</div>
				</div>
			</div>

			<div class="<?= (in_array($login_fabrica, [186]) && strlen($comunicado) === 0) ? 'span4' : 'span6' ?>">

				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<?php 
					if ($admin_es && $login_fabrica == 20) {
						echo "<label class='control-label' for='descricao_posto'>".traduz("Descrição")."</label>";
					} else {
						echo "<label class='control-label' for='descricao_posto'>".traduz("Razão Social")."</label>";
					} ?>					
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="posto_nome" id="descricao_posto" class='span12' value="<?php echo $posto_nome ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</span>
						</div>
					</div>
				</div>
			</div>
			<?php
			if (in_array($login_fabrica, [186]) && strlen($comunicado) === 0) { ?>
				<div class='span2'>
		        	<label>&nbsp;</label>
		        	<input type='button' name='adicionar' id='adicionar' value='Adicionar' class='btn btn-success' onclick='addIt2();' style="width: 100%;">
		        </div>
			<?php
			} ?>
		</div>
	<?php }
	if (in_array($login_fabrica, [186]) && strlen($comunicado) === 0) {	?>
		<p class="tac">
	    	(Selecione o posto e clique em <strong>Adicionar</strong>)
	    </p>
	    <div class='row-fluid'>
	        <div class='span2'></div>
	        <div class='span8'>
	        	<select multiple size='6' id="multi_posto" name="multi_posto[]" class='span12'>
					<?php if (count($lista_postos)>0){
						for ($i=0; $i<count($lista_postos); $i++){
							$linha_post = $lista_postos[$i];
							echo "<option value='".$linha_post[1]."'>".$linha_post[1]." - ".$linha_post[2]."</option>";
						}
					} ?>
				</select>
				<p class="tac">
					<input type="button" value="Remover" onclick="delIt2();" class='btn btn-danger' style="width: 126px;">
				</p>
	        </div>
	        <div class='span2'></div>
	    </div>
	<?php }
	if (strlen($remetente_email) == 0 AND strlen($comunicado) == 0) {
		$sql ="SELECT email FROM tbl_admin WHERE admin = $login_admin";
		$res = pg_query($con, $sql);
		$remetente_email = pg_fetch_result($res,0,email);
	} ?>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span5">
			<div class='control-group'>
				<label class='control-label' for='remetente_email'><?php echo traduz("Email para conferência de leitura"); ?></label>
				<div class='controls controls-row'>
                    <div class='span12 input-append'>
                         <input type="text" accesskey="V" maxlength="255" name="remetente_email" size="85" class="span12" value='<?=$remetente_email?>'>
                    </div>
                </div>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='remetente_email'><?php echo traduz("Tipo do Posto"); ?></label>
				<div class='controls controls-row'>
                    <div class='span12 input-append'>
						<select name='tipo_posto' id='tipo_posto' class='span11'>
							<?php $sql = "SELECT *
									FROM tbl_tipo_posto
									WHERE tbl_tipo_posto.fabrica = $login_fabrica
									AND tbl_tipo_posto.ativo = 't'
									ORDER BY tbl_tipo_posto.descricao";
								$res = pg_query ($con,$sql);
								echo "<option value=''>Todos</option>";
								for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
										echo "<option value='" . pg_fetch_result ($res,$i,tipo_posto) . "' ";
											if ($tipo_posto == pg_fetch_result ($res,$i,tipo_posto)) echo " selected ";
										echo ">";
										echo pg_fetch_result ($res,$i,descricao);
									echo "</option>";
								}

							?>
						</select>
                    </div>
                </div>
			</div>
		</div>

		<?php if (!in_array($login_fabrica, [20])){?>
		<div class="span2">
			<div class='control-group'>
				<label class='control-label' for='estado'><?php echo traduz("Estados dos Postos"); ?></label>
				<div class='controls controls-row'>
					<div class='span12'>
					<?php if ($login_fabrica == 177){ ?>
						<select id="estado" multiple="multiple" name="estado[]" >
							<option value="" >Selecione</option>
							<? foreach ($array_estado as $sigla => $nome_estado) {
								$selected = (in_array($sigla, $estado)) ? 'SELECTED' : '';
							?>
								<option value="<?= $sigla; ?>" <?= $selected; ?> ><?= $nome_estado; ?></option>
							<? } ?>
						</select>
					<?php }else{ ?>
						<select  name="estado" id="estado" class="span12" tabindex="0" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Escolha Unidade Federal (Estado).');">
							<option value=""></option>
							<?php
							$sql = "SELECT * FROM tbl_estado";
							if(!in_array($login_fabrica, [20])){
								$sql .= " WHERE pais = 'BR' AND regiao <> '' ";
							}
							$sql .= " ORDER BY estado ";
							$res = pg_query ($con,$sql);

								for ($i = 0; $i < pg_num_rows($res); $i++) {
									echo "<option ";
									if ($estado == pg_fetch_result ($res, $i, "estado"))
										echo " selected " ;
									echo " value='" . pg_fetch_result ($res, $i, "estado") . "'>";
									echo pg_fetch_result ($res, $i, "nome");
									echo "</option>";
								}
								?>
						</select>
						<?php } ?>
					</div>
                </div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
			<?php
		 	if ($login_fabrica == 161) { ?>
			 	<div class="row-fluid">
					<div class="span2"></div>
					<div class="span4">
						<div class='control-group'>
							<label class='control-label' for='pais'><?php echo traduz("Pais dos Postos"); ?></label>
							<div class='controls controls-row'>
			                    <div class='span12 input-append'>
			                         <select multiple name="pais[]" id="pais" class="span12" tabindex="0" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Escolha Um Pais.');">
										<?php
										$sqlPaises = "SELECT pais, nome
													  FROM tbl_pais";
										$resPaises = pg_query($con, $sqlPaises);

										if (!empty($comunicado)) {
											$sql_posto_comunicado = "SELECT pais FROM tbl_comunicado_produto WHERE comunicado = $comunicado AND pais NOTNULL";
											$res_posto_comunicado = pg_query($con, $sql_posto_comunicado);
											if (pg_num_rows($res_posto_comunicado) > 0) {
												$pais_comunicado = [];
												foreach (pg_fetch_all($res_posto_comunicado) as $key => $value) {
													$pais_comunicado[] = $value['pais'];
												}
											}
										}

										while ($dadosPaises = pg_fetch_object($resPaises)) { 

											$selected = (in_array($dadosPaises->pais, $_POST['pais']) || in_array($dadosPaises->pais, $pais_comunicado)) ? "selected" : "";

											?>
											<option value="<?= $dadosPaises->pais ?>" <?= $selected ?>><?= $dadosPaises->nome ?></option>
										<?php
										}
										?>
									</select>
			                    </div>
			                </div>
						</div>
					</div>
				</div>
			<?php 
			}

		 } else { ?>
		</div>
			<div class="row-fluid">
				<div class="span2">
				</div>
				<div class="span4">
					<div class='control-group'>
						<label class='control-label' for='estado'><?php echo traduz("Estados dos Postos"); ?></label>
						<div class='controls controls-row'>
		                    <div class='span12 input-append'>
		                         <select multiple name="estado[]" id="estado" class="span12" tabindex="0" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Escolha Unidade Federal (Estado).');">
									<?php
									$sql = "SELECT * FROM tbl_estado";
									$sql .= " WHERE pais = 'BR' AND regiao <> '' ";
									
									$sql .= " ORDER BY estado ";
									$res = pg_query ($con,$sql);

									for ($i = 0; $i < pg_num_rows($res); $i++) {
										echo "<option ";
										if ($estado == pg_fetch_result ($res, $i, "estado"))
											echo " selected " ;
										echo " value='" . pg_fetch_result ($res, $i, "estado") . "'>";
										echo pg_fetch_result ($res, $i, "nome");
										echo "</option>";
									}
									?>
								</select>
		                    </div>
		                </div>
					</div>
				</div>
				<div class="span4">
					<div class='control-group'>
						<label class='control-label' for='pais'><?php echo traduz("Pais dos Postos"); ?></label>
						<div class='controls controls-row'>
		                    <div class='span12 input-append'>
		                         <select multiple name="pais[]" id="pais" class="span12" tabindex="0" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Escolha Um Pais.');">
									<?php
									$sql = "SELECT * FROM tbl_pais";
									$sql .= " ORDER BY pais ";
									$res = pg_query ($con,$sql);

									for ($i = 0; $i < pg_num_rows($res); $i++) {
										echo "<option ";
										if ($pais == pg_fetch_result ($res, $i, "pais"))
											echo " selected " ;
										echo " value='" . pg_fetch_result ($res, $i, "pais") . "'>";
										echo pg_fetch_result ($res, $i, "nome");
										echo "</option>";
									}
									?>
								</select>
		                    </div>
		                </div>
					</div>
				</div>

			</div>
		<?php } ?>

	<?php
	if(in_array($login_fabrica, array(158))){
        $unidadenegocio = $_POST['unidadenegocio'];
    ?>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8" >
            <div class='control-group'>
                <label class="control-label" for="unidade_negocio" >Unidade de Negócio</label>
                <div class='controls controls-row'>
                    <select id="unidadenegocio" multiple="multiple" name="unidadenegocio[]" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "
                                SELECT DISTINCT
                                    ds.unidade_negocio,
                                    ds.unidade_negocio||' - '||c.nome AS descricao_unidade
                                FROM tbl_distribuidor_sla ds
                                JOIN tbl_cidade c USING(cidade)
                                WHERE ds.fabrica = {$login_fabrica};";
                            $resUnidadeNegocio = pg_query($con, $sql);
                            $countUnidadeNegocio = pg_num_rows($resUnidadeNegocio);

                            if ($countUnidadeNegocio > 0) {
                                for ($un = 0; $un < $countUnidadeNegocio; $un++) {
                                    $xunidade_negocio = pg_fetch_result($resUnidadeNegocio, $un, unidade_negocio);
                                    $xdesc_unidade = pg_fetch_result($resUnidadeNegocio, $un, descricao_unidade);
                                    $selected = (in_array($xunidade_negocio, $unidadenegocio)) ? "selected" : ""; ?>
                                    <option value="<?= $xunidade_negocio; ?>" <?= $selected; ?> ><?= $xdesc_unidade; ?></option>
                                <? }
                            } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
<? }?>
	<div class="row-fluid">

		<div class="span2"></div>

		<?php
		if(in_array($login_admin, array(364, 515, 588, 590))){
		?>

		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='pais' title="<?= traduz('Apenas os postos do país selecionado receberão o comunicado'); ?>"><?=traduz("Pais")?></label>
				<div class='controls controls-row'>
                    			<div class='span12 input-append'>

                    	<?php // MLG 2009-08-04 HD 136625
						    $sql = 'SELECT pais,nome FROM tbl_pais';
						    $res = pg_query($con,$sql);
						    $p_tot = pg_num_rows($res);
						    for ($i=0; $i<$p_tot; $i++) {
						        list($p_code,$p_nome) = pg_fetch_row($res, $i);
						    	$sel_paises .= "<option value='$p_code'";
						        $sel_paises .= ($pais==$p_code)?" selected":"";
						        $sel_paises .= ">$p_nome</option>";
						    }
						?>

                        			<select name='pais' id='pais' class='span11'><?php echo $sel_paises; ?></select>
                    			</div>
                		</div>
			</div>
		</div>

		<?php
		}
		?>
		<div class='span4'>
            		<div class='control-group'>
                	<label class='control-label' for='ativo'><?php echo traduz("Ativo"); ?> / <?php echo traduz("Inativo"); ?></label>
                		<div class='controls controls-row'>
                    			<div class='span10 input-append'>
                        			<label class="checkbox inline" style="margin-left: -20px;">
                        				<input type="radio" name="ativo" value='t' <?php if ($ativo == 't' || $ativo == ""){ echo "checked"; } ?> > <?php echo traduz("Ativo"); ?>
                       				 </label>
                        			<label class="checkbox inline">
							<input type="radio" name="ativo" value='f' <?php if ($ativo == 'f'){ echo "checked"; } ?> > <?php echo traduz("Inativo"); ?>
						</label>
                    			</div>
                		</div>
            		</div>
        	</div>
	</div>

	<div class="row-fluid">

		<div class="span2"></div>

		<div class='span4'>
            <div class='control-group'>
            	<?php
            	if ($novaTelaOs || $login_fabrica == 52) {
            	?>
                	<label class='control-label' for='obrigatorio_os_produto'>Mostrar no Cadastro de OS?</label>
                <?php
                } else {
                ?>
                	<label class='control-label' for='obrigatorio_os_produto'><?php echo traduz("Obrigatório na OS?"); ?></label>
                <?php
                }
                ?>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <label class="checkbox inline">
                        	<?php echo traduz("Sim"); ?> <input type='checkbox' name='obrigatorio_os_produto' id='obrigatorio_os_produto' value='t' <?php if ($obrigatorio_os_produto == "t") echo "checked" ?> >
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='obrigatorio_site'><?php echo traduz("Exibir na tela de entrada do site?"); ?></label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <label class="checkbox inline">
                        	<?php echo traduz("Sim"); ?> <input type='checkbox' name='obrigatorio_site' id='obrigatorio_site' value='t' <?php if ($obrigatorio_site == "t") echo "checked" ?> >
                        </label>
                    </div>
                </div>
            </div>
        </div>

	</div>
<?php
	if(in_array($login_fabrica,array(35,148))){
?>
	    <div class="row-fluid">
		<div class="span2"></div>
		<div class='span4'>
		    <div class='control-group'>
			<label class='control-label' for='email_posto'><?php echo traduz("Enviar E-mail Posto"); ?></label>
			<div class='controls controls-row'>
			    <div class='span10 input-append'>
				<div class='span10 input-append'>
				    <label class="checkbox inline" style="margin-left: -20px;">
					<input type="radio" name="email_posto" value='t' <?php if ($email_posto == 't' || $email_posto == ""){ echo "checked"; } ?> > <?php echo traduz("Sim"); ?>
				    </label>
				    <label class="checkbox inline">
					<input type="radio" name="email_posto" value='f' <?php if ($email_posto == 'f'){ echo "checked"; } ?> > <?php echo traduz("Não"); ?>
				    </label>
				</div>
			    </div>
			</div>
		    </div>
		</div>
		<div class="span2"></div>
	    </div>
<?php
	}
?>
	<div class="row-fluid">

		<div class="span2"></div>

		<?php
		$titulo_arquivo = "
			<p>Os tipos de arquivo aceitos são:</p>
			<ul>
				<li>Imagens (<code>JPG</code>,<code>PNG</code>,<code>GIF</code> e <code>BMP</code>),</li>
				<li>Office (6, 95, 2000+):
				  <ul>
					<li>Excel (<code>XLS, XLSX, ODS</code>),</li>
					<li>Word (<code>DOC, ODT, DOCX</code>),</li>
					<li>PowerPoint (<code>PPS, PPT, ODP</code>),</li>
				  </ul>
				</li>
				<li>Documentos de Texto:
				  <ul>
					<li>Rich-Text (<code>RTF</code>),</li>
					<li>Texto simples (<code>TXT</code>),</li>
					<li>Documentos <code>PDF</code></li>
				  </ul>
				</li>
				<li>Arquivos compactados em formato <code>ZIP</code>.</li>
			</ul>
			<strong>A TELECONTROL RECOMENDA OS FORMATOS:</strong> <code>JPG, GIF, PNG, ZIP</code> ou <code>PDF</code>.";
		?>

		<div class='span8'>
            <div class='control-group <?=(in_array("arquivo", $msg_erro["campos"])) ? "error" : ""?>'>
                <?php 
               	if (strlen($_GET["comunicado"]) > 0) {
			        $tempUniqueId = $_GET["comunicado"];
			        $anexoNoHash = null;
			    } else {
			        $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
			        $anexoNoHash = true;
			    }
                $boxUploader = array(
	                "div_id" => "div_anexos",
	                "prepend" => $anexo_prepend,
	                "context" => "comunicados",
	                "unique_id" => $tempUniqueId,
	                "hash_temp" => $anexoNoHash
	                
	            );
                echo "<input type='hidden' name='hashBox' id='hashBox' value='{$tempUniqueId}'/>";
	            include "../box_uploader.php";
	            ?>
            </div>
        </div>

        <?php if(in_array($login_fabrica, array(42))){ ?>

        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='mural_avisos'>Exibir no Mural de Avisos?</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <label class="checkbox inline">
                        	Sim <input type="checkbox" name="mural_avisos" value="sim" <?php echo ($mural_avisos == "t") ? "checked" : ""; ?> />
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <?php } ?>

	</div>

	<?php
	if ($_GET["comunicado"]) {
		$file_types = array("gif", "jpg", "pdf", "doc", "rtf", "xls", "ppt", "zip");

		$s3->set_tipo_anexoS3($tipo);

       	if($login_fabrica == 152){ //hd_chamado=2824422
       		if ($S3_online and $s3->temAnexos($comunicado)) {
				$display_none = "";
			}else{
				$display_none = "style='display:none;'";
			}
       	}

	?>

			<div class="row-fluid" <?=$display_none?>>
				<div class="span2"></div>
		        <div class='span8'>
		            <div class='control-group'>
		                <label class='control-label'>Anexo</label>
		                <div class='controls controls-row'>
		                    <div class='span12'>
		                        <?php
		                        if ($S3_online and $s3->temAnexos($comunicado)) {
									if (in_array($extensao, array('jpg', 'gif', 'jpeg', 'bmp', 'jpg2', 'png', 'pdf'))) {
										echo "<a href='JavaScript:void(0);' name='prod_ve' rel='$comunicado/$tipo'>";
									}else{
										echo "<a href='$s3->url'>";
									}

			        	            	echo "Clique aqui para abrir o anexo";
			                	    echo "</a>";
								} else {
									foreach ($file_types as $type) {
					                    if (file_exists("comunicados/$comunicado.$type")) {
					                    	echo "<a href='comunicados/$comunicado.$type' target='_blank'>";
					                    		echo "Clique aqui para abrir o anexo";
					                    	echo "</a>";
					                    }
					                }
								}
								?>
		                    </div>
		                </div>
		            </div>
		        </div>
			</div>

	<?php } ?>
</div>
<?php if($login_fabrica == 1) { ?>
	<!-- // ## -->
	<div class="row-fluid">
	    <div class="span2"></div>
	    <div class='span8'>
	        <div class='control-group'>
	            <label class='control-label' for="link_tela">Inserir Link da tela</label>
	            <h5 class='asteristico'>*</h5>
	            <div class='controls controls-row'>
	                <div class='span12'>
	                    <input type="text" name="link_tela" id="link_tela" value="<?=$link_tela?>" class="span12">
	                </div>
	            </div>
	        </div>
	        <span class="text-info">Ex: posvenda.telecontrol.com.br/assist/menu_inicial.php</span>
	    </div>
	</div>

	<div class="container-video">
	    <div class="box-video" data-id="1">
	        <hr>
	        <div class="row-fluid video tela box-video-titulo" style="<?= !empty($comunicado) ? 'display: block' : 'display: none' ?>">
                <div class="span2"></div>
	            <div class='span8'>
	                <div class='control-group'>
	                    <label class='control-label' for="titulo_video">Inserir Título do Vídeo</label>
	                    <h5 class='asteristico'>*</h5>
	                    <div class='controls controls-row'>
	                        <div class='span12'>
	                            <input type="text" name="titulo_video" id="titulo_video" value="<?= !empty($descricao) ? $descricao : null ?>" class="span12">
	                        </div>
	                    </div>
	                </div>
	                <span class="text-info">Ex: Como cadastrar uma Ordem de Serviço</span>
	            </div>
	        </div>

	        <div class="row-fluid video tela box-video-link" style='<?=$tela_video_explicativo_display?>'>
				<div class="span2"></div>
                <div class='span8'>
                    <div class='control-group'>
                        <label class='control-label' for="link_video">Inserir Link do Vídeo</label>
                        <h5 class='asteristico'>*</h5>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <input type="text" name="link_video" id="link_video" value="<?=$link_video?>" class="span12">
                            </div>
                        </div>
                    </div>
                    <span class="text-info">Ex: http://www.telecontrol.com.br</span>
                </div>
            </div>

            <div class="row-fluid video tela box-video-status" style="<?= !empty($comunicado) ? 'display: block' : 'display: none' ?>">
                <div class="span2"></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='ativo'><?php echo traduz("Ativo"); ?> / <?php echo traduz("Inativo"); ?></label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <label class="checkbox inline" style="margin-left: -20px;">
                                    <input type="radio" name="radio_ativo" id="radio_ativo" value='t' <?php if ($ativo == 't' || $ativo == ""){ echo "checked"; } ?> > <?php echo traduz("Ativo"); ?>
                                 </label>
                                <label class="checkbox inline">
                                    <input type="radio" name="radio_ativo" value='f' <?php if ($ativo == 'f'){ echo "checked"; } ?> > <?php echo traduz("Inativo"); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>

	<br />
	<div class="video_explicativo"  style="<?=$video_explicativo_display ?>">
        <div class="row-fluid">
            <div class="span2"></div>
    		<div class='span8'>
	            <div class='control-group'>
	                <label class='control-label'>O Posto pode:</label>
	                <div class='controls controls-row breadcrumb'>
	                	<div class='span12 input-append'>
	                        <label class="checkbox inline span5" style="margin-left: 2%">
	                                Pedido Faturado <input type='checkbox' name='pedido_faturado' id='pedido_faturado' value='t' <?php if ($pedido_faturado == "t") echo "checked" ?> >
	                        </label>

	                        <label class="checkbox inline span5" style="margin-left: 11%">
	                                Digitar OS <input type='checkbox' name='digita_os' id='digita_os' value='t' <?php if ($digita_os == "t" ) echo "checked" ?> >
	                        </label>
	                    </div>
	                    <div class='span12 input-append'>
	                        <label class="checkbox inline  span6">
	                                Reembolso de Peça do Estoque <input type='checkbox' name='reembolso_peca_estoque' id='reembolso_peca_estoque' value='t' <?php if ($reembolso_peca_estoque == "t" ) echo "checked" ?> >
	                        </label>
	                        <?php if($login_fabrica == 1){?>
	                        <label class="checkbox inline  span6">
	                                Pedido em garantia (manual) <input type='checkbox' name='pedido_em_garantia' id='pedido_em_garantia' value='t' <?php if ($pedido_garantia == "t" ) echo "checked" ?> >
	                        </label>
	                        <?php } ?>
	                    </div>
	                </div>
            	</div>
        	</div>
        </div>
	</div>
<?php } ?>

	<div class="tac" style="display: flex; justify-content: center;">

		<?php //HD 307110
			if ($login_fabrica == 1) {
				$js_button = "verifica_comunicado_extrato() ";
			}else {
				$js_button = "document.frm_comunicado.btn_acao.value=\"gravar\"; document.frm_comunicado.submit()";
			}
		?>

		<?php if( $login_fabrica == 1 ) { ?>
            <button type="button" class="btn" id="btn-adicionar-video" onclick="adicionarVideo()" style="display: none; margin-right: 5px;">+</button>
            <button type="button" class="btn" id="btn-gravar-video" onclick="gravarVideo()" style="display: none;">Gravar</button>
        <?php } ?>

		<input type="button" id="btn-gravar-old" style="cursor:pointer;" value="<?= traduz('Gravar'); ?>" onclick='selIt();<?= ($login_fabrica == 186) ? "selIt2();" : "" ?> <?php if($login_fabrica==50){ ?>selItPeca(); <?php } echo $js_button?> ' class="btn">

		<?php	if (strlen($comunicado) > 0 && count($msg_erro["msg"]) == 0) { ?>
				&nbsp; &nbsp; &nbsp;
				<input type="button" class="btn btn-danger" style="cursor:pointer;" value="Apagar" alt='Clique aqui para apagar' onclick='document.frm_comunicado.btn_acao.value="apagar"; document.frm_comunicado.apagar.value="<?echo $comunicado?>"; document.frm_comunicado.submit()' >
		<?php } ?>

	</div>

	<br />

</form>

<div class="alert alert-block alert-warning">
	* ** <?php echo traduz("Se"); ?> <strong><?php echo traduz("não"); ?></strong> <?php echo traduz("for selecionado o posto, todos os postos receberão o comunicado"); ?> <br/>
	** <?php echo traduz("Só será enviado o e-mail de confirmação se for selecionado o posto"); ?>
</div>

<?
	if (strlen ($produto_referencia) > 0) {
		$sql = "SELECT  tbl_comunicado.comunicado                                    ,
						tbl_comunicado.familia                                       ,
						tbl_comunicado.linha                                         ,
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
					AND tbl_comunicado.fabrica=$login_fabrica
				ORDER BY tbl_comunicado.data DESC";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) > 0) {
			echo "<table class='table table-bordered'>";
			for ($i = 0; $i < pg_num_rows ($res); $i++){
				$comunicado_proc        = trim(pg_fetch_result($res,$i,comunicado));
				$familia                = trim(pg_fetch_result($res,$i,familia));
				$linha                  = trim(pg_fetch_result($res,$i,linha));
				$produto_referencia2    = trim(pg_fetch_result($res,$i,prod_referencia));
				$produto_descricao      = trim(pg_fetch_result($res,$i,prod_descricao));
				$descricao              = trim(pg_fetch_result($res,$i,descricao));
				$extensao               = trim(pg_fetch_result($res,$i,extensao));
				$tipo                   = trim(pg_fetch_result($res,$i,tipo));
				$mensagem               = trim(pg_fetch_result($res,$i,mensagem));
				$data                   = trim(pg_fetch_result($res,$i,data));
				$obrigatorio_os_produto = trim(pg_fetch_result($res,$i,obrigatorio_os_produto));
				$obrigatorio_site       = trim(pg_fetch_result($res,$i,obrigatorio_site));

				echo "<tr>";
					echo "<td class='tac' ><strong>$descricao</strong></td>";
					echo "<td class='tac' ><strong>$data</strong></td>";
					echo "<td class='tac' ><a href='$PHP_SELF?comunicado=$comunicado_proc'>Listar</a></td>";
				echo "</tr>";

			}
			echo "</table>";
		}
	}

	if(trim($btn_acao) == "pesquisar"){
		$data_inicial 		= $_REQUEST['data_inicial'];
		$data_final   		= $_REQUEST['data_final'];
		$tipo               = $_REQUEST['psq_tipo'];
		$descricao          = $_REQUEST['psq_descricao'];
		$produto_referencia = $_REQUEST['psq_produto_referencia'];
		$produto_descricao  = $_REQUEST['psq_produto_nome'];
		$psq_pais           = $_REQUEST['psq_pais'];
		$psq_produto_serie  = $_REQUEST['psq_produto_serie'];
		$psq_familia        = $_REQUEST['psq_familia'];
		$psq_palavra_chave  = $_REQUEST['psq_palavra_chave'];

		if (!in_array($login_fabrica, [20,42])) {
			if (!strlen($data_inicial) or !strlen($data_final)) {
				$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
				$msg_erro["campos"][] = "data";
			} else {
				list($di, $mi, $yi) = explode("/", $data_inicial);
				list($df, $mf, $yf) = explode("/", $data_final);

				if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
					$msg_erro["msg"][]    = traduz("Data Inválida");
					$msg_erro["campos"][] = "data";
				} else {
					$xdata_inicial = "{$yi}-{$mi}-{$di}";
					$xdata_final   = "{$yf}-{$mf}-{$df}";

					if (strtotime($xdata_final) < strtotime($xdata_inicial)) {
						$msg_erro["msg"][]    = traduz("Data Final não pode ser menor que a Data Inicial");
						$msg_erro["campos"][] = "data";
					}
				}
			}
		}		

		if($login_fabrica == 42 ){

			if (strlen(trim($data_inicial)) > 0 AND strlen(trim($data_final)) > 0) {
				list($di, $mi, $yi) = explode("/", $data_inicial);
				list($df, $mf, $yf) = explode("/", $data_final);

				if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
					$msg_erro["msg"][]    = "Data Inválida";
					$msg_erro["campos"][] = "data";
				} else {
					$xdata_inicial = "{$yi}-{$mi}-{$di}";
					$xdata_final   = "{$yf}-{$mf}-{$df}";

					if (strtotime($xdata_final) < strtotime($xdata_inicial)) {
						$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
						$msg_erro["campos"][] = "data";
					}
				}
			}
		}

		if (empty($tipo) AND !in_array($login_fabrica, array(169,170))) {
			$msg_erro["campos"][] = 'psq_tipo';
			echo '<div class="alert alert-error"><h4>Preencha os campos obrigatórios</h4></div>';
			?>
			<script type="text/javascript">
				$(function(){
					window.location.href='#psq_tipo';
				});
			</script>
			<?php
		}
	}
?>
<div class="row">
	<strong class="obrigatorio pull-right"> * <?php echo traduz("Campos obrigatórios"); ?> </strong>
</div>
<form name='frm_pesquisa' class="tc_formulario" action='<?php echo $PHP_SELF; ?>' method='post'>

	<input type='hidden' name='btn_acao' value='pesquisar'>

	<div class="titulo_tabela"><?php echo traduz("Localizar Comunicados já Cadastrados"); ?></div>

	<br />
	<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial"); ?></label>
						<?php if($login_fabrica != 42){ ?>
							<h5 class='asteristico'>*</h5>
						<?php } ?>
						<div class='controls controls-row'>
							<div class='span4'>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?php echo traduz("Data Final"); ?></label>
					<?php if($login_fabrica != 42){ ?>
						<h5 class='asteristico'>*</h5>
					<?php } ?>
					<div class='controls controls-row'>
						<div class='span4'>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	<div class="row-fluid">
		<div class="span2"></div>

        <div class='span4'>
            <div class='control-group <?=(in_array("psq_tipo", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='psq_tipo'><?php echo traduz("Tipo"); ?></label>
                <?php if (!in_array($login_fabrica, array(169,170))){ ?>
                <h5 class='asteristico'>*</h5>
                <?php } ?>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <?
                        $sel_tipos = include('menus/comunicado_option_array.php');
						$new_tipos[] = '';
						if($login_fabrica == 117 and $privilegios != '*'){
							unset($sel_tipos['Apresentação do Produto']);
							unset($sel_tipos['Com. Unico Posto']);
							unset($sel_tipos['Estrutura do Produto']);
							unset($sel_tipos['Foto']);
							unset($sel_tipos['Manual']);
							unset($sel_tipos['Orientação de Serviço']);
							unset($sel_tipos['Procedimentos']);
							unset($sel_tipos['Promocao']);
							unset($sel_tipos['Comunicado']);
						}
                        if (in_array($login_fabrica, [30])) {
							unset($sel_tipos['Alterações Técnicas']);
							unset($sel_tipos['Descritivo técnico']);
							unset($sel_tipos['Manual Técnico']);
							unset($sel_tipos['Orientação de Serviço']);
							unset($sel_tipos['Procedimentos']);
							unset($sel_tipos['Contrato']);
							$sel_tipos['Manual administrativo'] = "Manual administrativo";
							$sel_tipos['Defeito constatado'] = "Defeito constatado";
							$sel_tipos[''] = "";
						}
						foreach ($sel_tipos as $key => $value) {
							$new_tipos[$key] = $value;
						}
						asort($new_tipos);
						$new_tipos = array_filter($new_tipos);
						echo array2select('psq_tipo', 'psq_tipo', $new_tipos, $tipo, ' class="span12"', ' ',true);
                        ?>

                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='data_inicio_analise'><?php echo traduz("Descrição"); ?> / <?php echo traduz("Título"); ?></label>
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
                <label class="control-label" for="psq_produto_serie">Número de Série</label>
                <div class="controls controls-row">
                    <div class="span12 input-append" id='fricon'>

                        <input id="psq_produto_serie" name="psq_produto_serie" class="span10" type="text" value="<?=$psq_produto_serie?>" maxlength="30" />
                        <span class="add-on lupa_serie" rel="lupa"style='cursor: pointer;'>
                     		<i class='icon-search'></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="produto" posicao="pesquisa" parametro="numero_serie" <?=(in_array($login_fabrica, array(169,170))) ? "mascara='true' grupo-atendimento='' fora-garantia='' km-google=''" : ""?> <?=($usaProdutoGenerico) ? "produto-generico='true'" : ""?> />
                    </div>
                </div>
            </div>
        </div>
		<div class="span4">
			<div class='control-group <?=(in_array('familia', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="psq_familia">Família</label>
				<div class="controls controls-row">
					<div class="span12 input-append" >
						<select class="span12" name="psq_familia">
							<option value=''>Selecione</option>
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
	<?php } ?>

	<div class='row-fluid'>

        <div class='span2'></div>

        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='psq_produto_referencia'><?php echo traduz("Ref. Produto"); ?> </label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="psq_produto_referencia" name="psq_produto_referencia" class='span12' maxlength="20" value="<?php echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posicao="pesquisa" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='psq_produto_nome'><?php echo traduz("Descrição Produto"); ?> </label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" id="psq_produto_nome" name="psq_produto_nome" class='span12' value="<?php echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posicao="pesquisa" />
                    </div>
                </div>
            </div>
        </div>

        <div class='span2'></div>

    </div>
    <?php
	if(in_array($login_admin, array(364, 515, 588, 590))){
	?>
	<div class='row-fluid'>

        <div class='span2'></div>

		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='pais' title="Apenas os postos do país selecionado receberão o comunicado">Pais</label>
				<div class='controls controls-row'>
                    <div class='span12 input-append'>

                    	<?php // MLG 2009-08-04 HD 136625
						    $sql = 'SELECT pais,nome FROM tbl_pais';
						    $res = pg_query($con,$sql);
						    $p_tot = pg_num_rows($res);
						    for ($i=0; $i<$p_tot; $i++) {
						        list($p_code,$p_nome) = pg_fetch_row($res, $i);
						    	$sel_paises .= "<option value='$p_code'";
						        $sel_paises .= ($pais==$p_code)?" selected":"";
						        $sel_paises .= ">$p_nome</option>";
						    }
						?>

                        <select name='pais' id='pais' class='span11'><?php echo $sel_paises; ?></select>
                    </div>
                </div>
			</div>
		</div>
	</div>

	<?php
	}
	?>

	<?php if(in_array($login_fabrica, array(42))) : ?>

	<div class="row-fluid">
		<div class="span2"></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='psq_palavra_chave'><?php echo traduz("Palavra-Chave"); ?></label>
                <div class='controls controls-row'>
                    <div class='span12'>
                    	<select name='psq_palavra_chave' id='psq_palavra_chave' data-palavra="<?php echo $psq_palavra_chave; ?>" class="span8"><option value=""></option></select> 
                    </div>
                </div>
            </div>
        </div>
    </div>

	<?php endif; ?>

    <br />

    <p class="tac">
    	<input type="submit" class="btn" value="<?php echo traduz('Continuar'); ?>" />
    </p>

    <br />

</form>

</div>

<?php
if (trim($btn_acao) == "pesquisar" && count($msg_erro) == 0) {

	#--------------------------------------------------------
	#  Mostra todos os informativos cadastrados
	#--------------------------------------------------------
	$sql = "SELECT DISTINCT tbl_comunicado.comunicado,
				   tbl_comunicado.descricao,
				   TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data,
				   tbl_comunicado.tipo,
				   tbl_produto.descricao AS produto_descricao,
				   tbl_produto.referencia,
				   tbl_produto.referencia_fabrica AS produto_referencia_fabrica,
				   tbl_posto.nome,
				   tbl_comunicado.video,
				   tbl_comunicado.serie,
				   tbl_comunicado.ativo,
				   tbl_comunicado.extensao,
				   tbl_comunicado.parametros_adicionais
			FROM tbl_comunicado
		 	LEFT JOIN tbl_posto   USING(posto)
		 	LEFT JOIN tbl_comunicado_produto ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
			LEFT JOIN tbl_produto ON (tbl_produto.produto = tbl_comunicado_produto.produto OR tbl_produto.produto =
		 	tbl_comunicado.produto AND tbl_produto.fabrica_i = $login_fabrica)
		 	LEFT JOIN tbl_linha   ON tbl_linha.linha = tbl_produto.linha
			WHERE tbl_comunicado.fabrica = $login_fabrica ";

	if ($login_fabrica == 20){
		if (strlen($psq_pais)> 0) $sql .= " AND tbl_comunicado.pais = '{$psq_pais}' ";
		else $sql  .= " AND tbl_comunicado.pais = '{$login_pais}' ";
	}

	if($login_fabrica == 42 && $psq_palavra_chave != ""){
		$psq_palavra_chave = strtolower($psq_palavra_chave);
		$sql .= " AND tbl_comunicado.palavra_chave @@ tsquery('{$psq_palavra_chave}')";
	}

	if (strlen($tipo) > 0){
		$sql .= " AND (tbl_comunicado.tipo      = fn_retira_especiais('$tipo') or tbl_comunicado.tipo = '$tipo' ) ";
	}else{
		$sql .= " AND tbl_comunicado.tipo <> 'Comunicado Automatico' ";
	}

	if (strlen($psq_familia) > 0) {
		$sql .= " AND tbl_comunicado.familia = $psq_familia ";
	}

	if (strlen($data_inicial) > 0 and !in_array($login_fabrica, [20])) {
		$sql .= " AND tbl_comunicado.data BETWEEN '{$xdata_inicial} 00:00:00' AND '{$xdata_final} 23:59:59'";
	}

	if (strlen($descricao) > 0) $sql .= " AND tbl_comunicado.descricao ILIKE '%$descricao%' ";
	// HD 16189
	if (strlen($produto_referencia) > 0){

		$sqlx = "SELECT   tbl_produto.produto, tbl_produto.familia
				FROM     tbl_produto
				JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
				WHERE    (tbl_produto.referencia_pesquisa = '$produto_referencia' OR tbl_produto.referencia = '$produto_referencia')
				AND      tbl_linha.fabrica = $login_fabrica";
		$resx = pg_query ($con,$sqlx);
		if (pg_num_rows($resx) > 0){
			$produto = pg_fetch_result ($resx,0,produto);
			$sql .= " AND ( (tbl_comunicado.produto = $produto OR tbl_comunicado_produto.produto=$produto) ";

			//hd 53987
			if ($login_fabrica == 3) {
				$familia = pg_fetch_result ($resx,0,familia);
				if (strlen($familia) > 0) {
					$sql .= "OR (tbl_comunicado.familia = $familia AND tbl_comunicado.produto IS NULL and tbl_comunicado_produto.produto isnull)  ";
				}
			}

			$sql .= ") ";
		}
	}


	$sql .= " ORDER BY comunicado DESC ";

    ##### PAGINAÇÃO - INÍCIO #####
    $sqlCount  = "SELECT count(*) FROM (";
    $sqlCount .= $sql;
    $sqlCount .= ") AS count";
    if ($posto) {
    	require "_class_paginacao.php";
    }else{
    	require "../_class_paginacao.php";	
    }
    

    // definicoes de variaveis
    $max_links = 11;                // máximo de links à serem exibidos
    $max_res   = 20;                // máximo de resultados à serem exibidos por tela ou pagina
    $mult_pag= new Mult_Pag();  // cria um novo objeto navbar
    $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

    $res = $mult_pag->Executar($sql, $sqlCount, $con, "otimizada", "pgsql");
    ##### PAGINAÇÃO - FIM #####

	//$res = pg_query ($con,$sql);
	$total = pg_num_rows ($res);

	if ($total > 0){
		echo "<table class='table table-bordered table-large' style='width: 1000px; margin: 0 auto;height: auto !important'>";

		echo "<thead>";

			echo "<tr class='titulo_tabela'>";
				echo "<td class='tac'>Tipo</td>";
				echo "<td class='tac'>Título</td>";
				if($login_fabrica == 171){
					echo "<td class='tac'>Referência Fábrica</td>";
				}
				if (in_array($login_fabrica, array(169,170))){
                    echo "<td class='tac'>Modelo do Produto</td>";
				}
				echo "<td class='tac'>Produto</td>";
				echo "<td class='tac'>Comunicado</td>";
				if($login_fabrica == 30){
					echo "<td class='tac'>Posto</td>";
				}
				if($tipo == 'pedido_faturado_parcial' and $login_fabrica == 104){
					echo "<td class='tac'>Pedido</td>";
				}
				echo "<td class='tac'>Data</td>";
				echo "<td class='tac'>Anexo</td>";
				if ( in_array($login_fabrica, array(11,172)) ) {
					echo "<td class='tac'>Vídeo</td>";
				}
				echo "<td class='tac'>Ativo</td>";
				echo "<td class='tac' width='85'>Ação</td>";
			echo "</tr>";

		echo "</thead>";

		echo "<tbody>";

			for ($i = 0 ; $i < $total ; $i++) {
				$nserie            = trim(pg_fetch_result($res, $i, 'serie'));
				$descricao         = trim(pg_fetch_result($res,$i,'descricao'));
				$produto_referencia = trim(pg_fetch_result($res,$i,'referencia'));
				$comunicado        = trim(pg_fetch_result($res,$i,'comunicado'));
				$produto_descricao = trim(pg_fetch_result($res,$i,'produto_descricao'));
				$produto_referencia_fabrica = trim(pg_fetch_result($res,$i,'produto_referencia_fabrica'));
				$data              = trim(pg_fetch_result($res,$i,'data'));
				$posto             = trim(pg_fetch_result($res,$i,'nome'));
				$tipo              = trim(pg_fetch_result($res,$i,'tipo'));
				$ativo             = trim(pg_fetch_result($res,$i,'ativo'));
				$video             = trim(pg_fetch_result($res,$i,'video'));
				$extensao          = pg_fetch_result($res, $i, "extensao");

				if($tipo == 'pedido_faturado_parcial' and $login_fabrica == 104){
					$parametros_adicionais = json_decode(pg_fetch_result($res, $i, 'parametros_adicionais'), true);
					$pedido = $parametros_adicionais['pedido'];
				}
				echo "<tr>";

					echo "<td>";
						echo $sel_tipos["$tipo"];
					echo "</td>";

					echo "<td>";
						if(strlen($descricao) > 0){
							echo "<a href='$PHP_SELF?comunicado=" . $comunicado . "'>";
								echo $descricao;
							echo "</a>";
						}else{
							echo "SEM DESCRIÇÃO";
						}
					echo "</td>";
					if($login_fabrica == 171){
						echo "<td class='tac'>".$produto_referencia_fabrica."</td>";
					}

					if (in_array($login_fabrica, array(169,170))){
                        echo "<td>";
                        echo (strlen($produto_referencia) > 0) ? $produto_referencia : "SEM PRODUTO";
                        echo "</td>";	
                    }

					echo "<td>";
						echo (strlen($produto_descricao) > 0) ? $produto_descricao : "SEM PRODUTO";
					echo "</td>";

					echo "<td>";
						echo $comunicado;
					echo "</td>";

					if($login_fabrica == 30){
						echo "<td>";
							echo $posto;
						echo "</td>";
					}
					if($tipo == 'pedido_faturado_parcial' and $login_fabrica == 104){
						echo "<td>".$pedido."</td>";
					}
					echo "<td>";
						echo $data;
					echo "</td>";

					$file_types = array("gif", "jpg", "pdf", "doc", "rtf", "xls", "ppt", "zip");
					echo "<td class='formulario_tabela' style='text-align: center;' nowrap>";
					if ($posto) {

					}else{
						if ($fabricaFileUploadOS) {
						//if ($extensao) {
							
							$sqlAnexo = "SELECT tdocs_id FROM tbl_tdocs 
										 WHERE contexto = 'comunicados' 
										 AND referencia_id = {$comunicado}
										 AND fabrica = {$login_fabrica}";
							$resAnexo = pg_query($con, $sqlAnexo);

							if (pg_num_rows($resAnexo) > 0) {
								echo "<button class='btn btn-primary btn-small ver-comunicados' data-comunicado='{$comunicado}'>".traduz("Ver anexos")."</button>";
							}

						} else {
							include_once S3CLASS;
							$s3 = new anexaS3('co', (int) $login_fabrica, $comunicado);
							$s3->set_tipo_anexoS3(($tipo == "Laudo Tecnico") ? "co" : $tipo);
							if ($S3_online and $s3->temAnexos($comunicado)) {
								//echo "<a href='JavaScript:void(0);' name='prod_ve' rel='$comunicado/$tipo'>";
									echo "Tem Anexo";
								//echo "</a>";
								//echo "<br /> <span id='before-$comunicado'></span>";
							} else {
								foreach ($file_types as $type) {

									if (file_exists("comunicados/$Xcomunicado.$type")) {
										//echo "<a href='comunicados/$Xcomunicado.$type' target='_blank'>";
											echo "Tem Anexo";
										//echo "</a>";
									}
								}
							}
						}
					}
					echo "</td>";
					if ( in_array($login_fabrica, array(11,172)) ) {
						echo "<td class='tac'>";
						if (!empty($video)) {
							echo "<a href=\"javascript:window.open('../video.php?video=$video','_blank','toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);\" >Abrir vídeo</a>";
						}
						echo "</td>";
					}
					echo "<td class='tac'>";

						$imagem_ativo = ($ativo == "t") ? 'status_verde.png' : 'status_vermelho.png';
						echo "<img src='imagens/".$imagem_ativo."' />";

					echo "</td>";

					echo "<td class='tac'>";
						if (($posto == '' || $posto)&& !$admin_es && !$admin) {
							echo "<a data-comunicado='" .$comunicado. "' class='btn btn-primary'>";
								echo "Detalhe";
							echo "</a>";
						}else{							
							echo "<a href='$PHP_SELF?comunicado=" .$comunicado. "' class='btn btn-primary'>";
								echo "Alterar";
							echo "</a>";
						}
					echo "</td>";

				echo "</tr>";
			}
			echo "</tbody>";
		echo "</table>";

	    ##### PAGINAÇÃO - INÍCIO #####
	    echo "<br>";
	    echo "<div style='margin:auto;text-align:center;'>";
	    if($pagina < $max_links) $paginacao = pagina + 1;
	    else                     $paginacao = pagina;

	    // pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	    if (strlen($btn_acao_pre_os) ==0) {
	        $todos_links = $mult_pag->Construir_Links("strings", "sim");
	    }

	    // função que limita a quantidade de links no rodape
	    if (strlen($btn_acao_pre_os) ==0) {
	        $links_limitados    = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
	    }

	    for ($n = 0; $n < count($links_limitados); $n++) {
	        echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	    }

	    echo "</div>";

	    $resultado_inicial = ($pagina * $max_res) + 1;
	    $resultado_final   = $max_res + ( $pagina * $max_res);
	    if (strlen($btn_acao_pre_os) ==0) {
	        $registros         = $mult_pag->Retorna_Resultado();
	    }

	    $valor_pagina   = $pagina + 1;
	    if (strlen($btn_acao_pre_os) ==0) {
	        $numero_paginas = intval(($registros / $max_res) + 1);
	    }
	    if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	    if ($registros > 0){
	        echo "<br>";
	        echo "<div style='margin:auto;text-align:center;'>";
	        echo traduz("Resultados de <b>%</b> a <b>%</b> do total de <b>%</b> registros.", null, null, [$resultado_inicial, $resultado_final, $registros]);
	        echo "<font color='#cccccc' size='1'>";
	        echo traduz("(Página <b>%</b> de <b>%</b>)", null, null, [$valor_pagina, $numero_paginas]);
	        echo "</font>";
	        echo "</div>";
	    }
	    ##### PAGINAÇÃO - FIM #####
	}else{

		echo "<div class='container'><div class='alert alert-warning'><h4>Nenhum resultado encontrado</h4></div></div>";

	}
}
echo "<br />";
include "rodape.php";

?>
<script type="text/javascript">
$(function(){
	$.datepickerLoad(Array("data_final", "data_inicial"));
<?php
	if($login_fabrica == 177){
?>
	$("#estado").multiselect({
		selectedText: "selecionados # de #"
	});
<?php
	}
?>
	$("#pais").multiselect({
		selectedText: "selecionados # de #"
	});
	$('[data-comunicado]').on('click', function(){
		Shadowbox.init();
		
		Shadowbox.open({
			content :   "shadowbox_view_comunicado.php?comunicado=" + $(this).data('comunicado'),
			player  :   "iframe",
			title   :   "Visualização de Comunicado",
			width   :   800,
			height  :   600
		});
	});
});

<?php if ($login_fabrica == 1) { ?>

	$(document).ready(function() {
		if ($('#tipo option:selected').val() == "video_explicativos") {
			$(".container-video").show()
		} else {
			$(".container-video").hide()
		}
	})

<?php } ?>


<?php if ($login_fabrica == 42) { ?>

	$(document).ready(function() {

		var select = $("#palavras_chave");
		var selectPsq = $('#psq_palavra_chave');

		select.select2(); 
		selectPsq.select2({
			tags: true,
			allowClear:true,
			placeholder:"", 
			language: {noResults: function(){return "";}}
		});

		var data = select.attr('data-palavras').split(" ");
		var dataPsq = selectPsq.attr('data-palavra');

		if(data != ""){
			$(data).each(function(index, text){
				var chave = text.replace(/'/g, '');
				var newOption = new Option(chave, chave, false, true);
				select.append(newOption).trigger('change');
			});
		}

		if(dataPsq != ""){
			var newOption = new Option(dataPsq, dataPsq, false, true);
			selectPsq.append(newOption).trigger('change');
		}
	})

<?php } ?>

</script>
