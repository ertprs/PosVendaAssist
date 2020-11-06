<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'autentica_admin.php';

    if (!function_exists('is_email')) {
        function is_email($email="") {  // False se não bate...
            if (!$email) return false;
            return (preg_match("/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+$/", $email));
        }
    }
    function eSupervisorHelpDesk($adminLogin) {
        global $con, $login_fabrica;

        if (!empty($adminLogin)) {
            $sql = " SELECT admin
                     FROM tbl_admin
                     WHERE fabrica = $login_fabrica
                     AND help_desk_supervisor IS TRUE
                     AND admin = $adminLogin
                     AND ativo IS TRUE";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                return true;
            }
        }

        return false;
    }

    //HD 223175: Habilitando questionário para todas as fábricas
    $habilita_questionario = true;

    //HD 7277 Paulo - tirar acento do arquivo upload
    if (!function_exists('retira_acentos')) {
        function retira_acentos($texto) {
            $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
            $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
            return str_replace( $array1, $array2, $texto );
        }
    }

    include_once '../class/aws/s3_config.php';
    include_once '../class/aws/aws_init.php';

    define('S3TEST',  (DEV_ENV) ? 'testes/': '');

    include_once '../class/aws/anexaS3.class.php';

    $s3requisito = new AmazonTC('requisitos', 10);

    function getAnexosRequisitos($hd_chamado, $idx){
        global $s3requisito;

        $anexo = $s3requisito->getObjectList("requisito_{$hd_chamado}_{$idx}.");

        if (count($anexo) > 0) {
            $anexo = $anexo[0];

            $anexo = $s3requisito->getLink(basename($anexo));

            return $anexo;
        } else {
            return false;
        }

    }


    if ($_GET ['tipo'])          $tipo          = trim($_GET['tipo']);
    if ($_GET ['posto'])          $posto          = trim($_GET['posto']);
    if ($_GET ['hd_chamado'])          $hd_chamado          = trim($_GET['hd_chamado']);
    if ($_POST['hd_chamado'])          $hd_chamado          = trim($_POST['hd_chamado']);
    if ($_GET ['btn_acao'])            $btn_acao            = trim($_GET['btn_acao']);
    if ($_POST['btn_acao'])            $btn_acao            = trim($_POST['btn_acao']);
    if ($_GET ['btn_resolvido'])       $btn_resolvido       = trim($_GET['btn_resolvido']);
    if ($_POST['btn_resolvido'])       $btn_resolvido       = trim($_POST['btn_resolvido']);
    if ($_GET ['aguardando_resposta']) $aguardando_resposta = trim($_GET['aguardando_resposta']);
    if ($_GET ['msg'])                 $msg                 = trim($_GET['msg']);

$ajax = @$_REQUEST['ajax'];

if($ajax == 'ajax'){
    $tipo_ajax = $_POST['tipo_ajax'];
    $msg_erro  = $_POST['msg_erro'];

    if($tipo_ajax == 'verificaErro'){
        $sql = "SELECT hd_chamado, tipo_chamado, categoria,  status, titulo FROM tbl_hd_chamado WHERE fabrica = 3 AND status NOT IN ('Cancelado','Resolvido') AND tipo_chamado = 5;";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0 AND empty($hd_chamado) AND empty($msg_erro)){
            echo pg_num_rows($res);
        }else{
            echo 0;
        }
    }


    exit;
}

if (strlen($hd_chamado) > 0) {

    $sql = "SELECT * from tbl_hd_chamado where hd_chamado=$hd_chamado and fabrica=$login_fabrica";
    $res = pg_exec($con,$sql);

    if (pg_numrows($res) == 0) {
        header("Location: http://www.telecontrol.com.br");
        exit;
    }

    if ($_GET['aprovaRequisitos'] == 'sim') {

        ///// controle chamados
        pg_query($con, "BEGIN");
        $select_status_chamado = "SELECT ts.status_chamado,
                                      ts.data_input,
                                      tc.status,
                                      tc.ordem
                                  FROM tbl_status_chamado ts
                                  JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status
                                  WHERE ts.hd_chamado = {$hd_chamado} 
                                  ORDER BY ts.data_input DESC;";
        $result = pg_query($con, $select_status_chamado);
        $ordem = pg_fetch_result($result, 0, 'ordem');
        $ordem++;

        $select_controle_chamado = "SELECT
                                        controle_status,
                                        dias
                                    FROM tbl_controle_status
                                    WHERE status = 'Requisitos'
                                    AND ordem = $ordem;";
        $result = pg_query($con, $select_controle_chamado);
        
        if (pg_numrows($result) > 0) {
            $controle = pg_fetch_result($result, 0, 'controle_status');
            $dias = pg_fetch_result($result, 0, 'dias');

            $time = time();
            $data_atual = date('Y-m-d H:i:s');

            $query = "SELECT COUNT(tbl_hd_chamado.hd_chamado) AS tantos,
                          tbl_admin.admin,
                          tbl_admin.nome_completo
                      FROM tbl_hd_chamado
                      JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
                      WHERE tbl_hd_chamado.fabrica_responsavel = 10
                      AND tbl_admin.grupo_admin = 1
                      AND tbl_admin.ativo IS TRUE
					  AND tbl_admin.parametros_adicionais !~'ultron'
                      GROUP BY tbl_admin.admin ORDER BY random();";
            $result = pg_query($con, $query);
            if (strlen(pg_last_error()) > 0) {
                $msg_erro .= pg_last_error();
            }
            $admin = pg_fetch_result($result, 0, 'admin');
            $adminResp = pg_fetch_result($result, 0, 'nome_completo');
            
            $params = [$hd_chamado, $admin, $controle, 'Requisitos', $data_atual];
            $query = "INSERT INTO tbl_status_chamado (
                          hd_chamado,
                          admin,
                          controle_status,
                          status,
                          data_inicio,
                          data_prazo
                      ) VALUES ($1, $2, $3, $4, $5, fn_calcula_previsao_retorno('{$data_atual}', '{$dias}', {$login_fabrica}));";
            pg_query_params($con, $query, $params);

            $qHdInfo = "SELECT admin
                        FROM tbl_admin
                        WHERE nome_completo ILIKE 'Suporte'";
            $rHdInfo = pg_query($con, $qHdInfo);
            $commentAdmin = pg_fetch_result($rHdInfo, 0, 'admin');

            $comment = "MENSAGEM AUTOMÁTICA - Chamado transferido automaticamente para <b>{$adminResp}</b> para Análise de Requisitos.";
            $params = [$hd_chamado, $comment, $commentAdmin, true];
            $qInteracao = "INSERT INTO tbl_hd_chamado_item (
                               hd_chamado,
                               comentario,
                               admin,
                               interno
                           ) VALUES ($1, $2, $3, $4)";
            $rInteracao = pg_query_params($con, $qInteracao, $params);

            $qTransfere = "UPDATE tbl_hd_chamado
                           SET atendente = {$admin},
                               login_admin = {$admin}
                           WHERE hd_chamado = {$hd_chamado}
                           AND fabrica = {$login_fabrica}";
            $rTransfere = pg_query($con, $qTransfere);
        }        

        if (strlen(pg_last_error()) > 0) {
            pg_query($con, "ROLLBACK");
        } else {
            pg_query($con, "COMMIT");
        }
        //////////////////

        $data = date('d-m-Y');
        $sql_nome_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
        $nome_completo  = pg_fetch_result(pg_query($con, $sql_nome_admin), 0, 'nome_completo');

        $sql = "UPDATE tbl_hd_chamado_requisito
                   SET (admin_requisito_aprova,data_requisito_aprova)
                        =
                       ($login_admin, CURRENT_TIMESTAMP)
                 WHERE hd_chamado = $hd_chamado
                   AND excluido IS FALSE";
        $res = pg_query($con, $sql);

        

        if (!is_resource($res) or pg_affected_rows($res)<1) {
            $msg_erro = "Erro ao aprovar os requisitos deste chamado.";
        } else {
            //SEM HD - Usuário que aprova requisitos interage ou 'responde' à solicitaçao.
            //         Adicionando "exigir_resposta = FALSE".
            if ($sistema_lingua == 'ES'){
                $mensagem_automatica = "MENSAJE AUTOMATICO - REQUISITOS APROBADOS EN $data POR EL CLIENTE $nome_completo";    
            }else{
            $mensagem_automatica = "MENSAGEM AUTOMÁTICA - REQUISITOS APROVADOS EM $data PELO USUÁRIO $nome_completo";
            }
            $sql = "UPDATE tbl_hd_chamado
                       SET status = CASE WHEN tipo_chamado IN(5,6)
                                         THEN 'Análise'
                                         ELSE 'Orçamento'
                                    END,
						   exigir_resposta = FALSE, 
							atendente = (select admin from tbl_admin , tbl_fabrica where tbl_fabrica.fabrica = tbl_hd_chamado.fabrica and grupo_admin = 1 and tbl_admin.ativo and tbl_admin.parametros_adicionais::jsonb->'equipe' ?  (tbl_fabrica.parametros_adicionais::jsonb->>'equipe')::text order by random() limit 1)  
                     WHERE hd_chamado   = $hd_chamado
                       AND status       NOT IN('Resolvido','Cancelado');
                    INSERT INTO tbl_hd_chamado_item (
                        hd_chamado,
                        comentario,
                        admin
                    ) VALUES (
                        $hd_chamado,
                        '$mensagem_automatica',
                        $login_admin
                )";

            
            $res = pg_query($con, $sql);
            if (!is_resource($res) or pg_affected_rows($res) <> 1)
                $msg_erro = "Erro ao aprovar os requisitos deste chamado.";
        }
        if (!$msg_erro){

            $sql = "SELECT titulo FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
            $res = pg_query($con,$sql);
            $titulo = pg_result($res, 0, titulo);
            $assunto = $hd_chamado.": ".$titulo." - Requisitos aprovados";
            $destinatario = "suporte.fabricantes@telecontrol.com.br";
            $mensagem = "Foram aprovados os requisitos do chamado $hd_chamado";

            // To send HTML mail, the Content-type header must be set
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // Additional headers
            $headers .= "To: $destinatario" . "\r\n";
            $headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";

            $mailer->sendMail($destinatario, $assunto, $mensagem, 'helpdesk@telecontrol.com.br');

            header("Location: " . $PHP_SELF . "?hd_chamado=$hd_chamado");
        }
    }
}

if (strlen($btn_resolvido) > 0) {
    $sql= "UPDATE tbl_hd_chamado
              SET resolvido       = CURRENT_TIMESTAMP,
                  exigir_resposta = NULL
            WHERE hd_chamado = $hd_chamado";
    $res = @pg_query($con,$sql);
    $msg = traduz('Chamado Resolvido');
    header ("Location: $PHP_SELF?hd_chamado=$hd_chamado&msg=$msg");
    exit;
}

if (strlen($hd_chamado) > 0 AND $aguardando_resposta == '1') {

    $sql= "UPDATE tbl_hd_chamado
              SET resolvido       = NULL,
                  exigir_resposta = 't'
            WHERE hd_chamado = $hd_chamado";

    $res = @pg_query($con,$sql);
    header("Location: chamado_lista.php?status=Análise&exigir_resposta=t");
    exit;
}

if (strlen ($btn_acao) > 0) {

    if ($_POST['comentario']) {
        $comentario      = trim($_POST['comentario']);
        $comentario = pg_escape_string($comentario);
    }
    if ($_POST['titulo'])               { $titulo               = trim($_POST['titulo']);}
    if ($_POST['categoria'])            { $categoria            = trim($_POST['categoria']);}
    if ($_POST['nome'])                 { $nome                 = trim($_POST['nome']);}
    if ($_POST['email'])                { $email                = trim($_POST['email']);}
    if ($_POST['fone'])                 { $fone                 = trim($_POST['fone']);}
    if ($_POST['status'])               { $status               = trim($_POST['status']);}
    if ($_POST['combo_tipo_chamado'])   { $xtipo_chamado        = trim($_POST['combo_tipo_chamado']);}
    if ($_POST['tipo_categoria_erro'])  { $xtipo_categoria_erro = trim($_POST['tipo_categoria_erro']);}

    if (strlen($xtipo_chamado)==0)     { $xtipo_chamado   = trim($_POST['combo_tipo_chamado_2']);}
    $necessidade    = trim(pg_escape_string($con,$_POST['necessidade']));
	$funciona_hoje  = trim(pg_escape_string($con,$_POST['funciona_hoje']));
    $objetivo       = trim($_POST['objetivo']);
    $local_menu     = trim($_POST['local_menu']);
    $http           = trim($_POST['http']);
    $tempo_espera   = intval(trim ($_POST['tempo_espera']));
    $impacto        = trim($_POST['impacto']);
    //$arquivo        = !empty($_FILES["arquivo"]['name']) ? $_FILES["arquivo"] : FALSE;    
    $arquivo        = $_FILES["arquivo"];    

    #Nos casos de Reabrir camado, inserir um novo chamado e enviar email para Samuel - HD 16445
    //HD 197505: O chamado somente gerará um novo chamado no caso do cliente já ter aprovado o chamado anteriormente
    //           Caso não tenha aprovado, gerará uma interação no mesmo chamado e devolverá para execução
    // 13/08/2010:  Ébano: SEM CHAMADO: A pedido do Samuel, se reabrir chamado, deve ser como antes: abrir novo
    //              chamado e deve ser de erro
    $hd_chamado = trim ($_POST['hd_chamado']);

    if (strlen($hd_chamado) > 0) {
        $sql= "SELECT
        hd_chamado,
        atendente,
        titulo,
        categoria,
        hd_chamado_anterior
        FROM
        tbl_hd_chamado

        WHERE
        hd_chamado = $hd_chamado
        AND status = 'Resolvido'
        AND data_resolvido IS NOT NULL
        ";
        $res = pg_query ($con,$sql);

        if (pg_numrows($res) > 0) {

			# HD 41597 - Francisco Ambrozio
			#   Quando o título anterior tinha aspas simples dava erro
			$titulo_anterior    = str_replace("'", "", pg_result($res,0,titulo));
			$hd_chamado_ant = pg_fetch_result($res,0,'hd_chamado_anterior');
			$atendente = pg_fetch_result($res,0, 'atendente');
			$hd_chamado_anterior= $hd_chamado;
			$hd_chamado         = "";
			$status_anterior    = "REABRIR";
			$xtipo_chamado      = 5; #Chamado de erro caso for reaberto
			$categoria      = 5;

			$titulo = $titulo_anterior;
			$hd_chamado_ant = (empty($hd_chamado_ant)) ? $hd_chamado_anterior : $hd_chamado_ant;
        }

    } else if ($habilita_questionario) {

        if (!in_array(intval($_POST["combo_tipo_chamado"]), array(5,8))) {

            if (strlen($necessidade) == 0) {
               $campos_erro[] = "necessidade";
            }  
            if (strlen($funciona_hoje) == 0){ 
                $campos_erro[] = "funciona_hoje";
            }
            $aux_correcao = 'alteração';

        } else {
            if (intval($_POST["combo_tipo_chamado"]) == 8) {
                $aux_correcao = 'alteração';
            } else {
                $aux_correcao = 'correção';
            }

        }

        if (strlen($local_menu) == 0) {   
            $campos_erro[] = "local_menu";
        }

        if (in_array($xtipo_categoria_erro, array('Erro em Tela', 'Processos')) or intval($_POST["combo_tipo_chamado"]) == 5) {

            if (strlen($http) < 10) {
                if($sistema_lingua == 'ES'){
                    $msg_erro .= "<br>Ingrese <u>Unbicación HTTP de la pantalla donde solicita cambiar:</u> al menos 10 caracteres";
                }else{
                    $msg_erro .= "<br>Digite <u>Endereço HTTP da tela aonde está sendo solicitada a alteração:</u> com no mínimo 10 caracteres";
                }
                    $campos_erro[] = "http";
            }

            $urlOK=preg_match("#^(https?://)?((esab|posvenda|ww2|urano|pedidoweb|pedido-web|compras.master)\.telecontrol\.com\.br)?(/\w+)+(.php)(\?.*)?$#", $http, $a_url);
			if(strpos($http, 'compras') !== false and strpos($http,'telecontrol') !== false) $urlOK= true;
            if (!$urlOK) { $msg_erro .= "<br>".traduz("O link ('Endereço HTTP') não é válido, confira e corrija.")."";
                $campos_erro[] = "http";
            }

            //HD 218848: Quando for chamado de erro, é obrigatório enviar o printscreen da tela
            if (intval($_POST["combo_tipo_chamado"]) == 5) {
                   if (strlen($arquivo['name'][0]) == 0 ) { 
                        if($sistema_lingua == 'ES'){
                            $msg_erro .= "<br />Para una llamada de error, adjunte una <i>PrintScreen</i> (imagen JPG, preferible) de la pantalla donde ocorrió el error.";
                        }else{
                            $msg_erro .= "<br />Para chamado de erro, por favor, anexe um <i>PrintScreen</i> (imagem JPG, de preferência) da tela aonde o erro ocorreu";
                        }
               }
            }
        }
    }

    if (strlen($xtipo_chamado) == 0) {
        $campos_erro[] = "tipo_chamado";
    }

    if($combo_tipo_chamado == 5 AND strlen($impacto_financeiro) == ''){
        $campos_erro[] = "impacto_financeiro";
    }

    if (strlen($titulo) == 0 and empty($hd_chamado)) {//HD 711738
        $campos_erro[] = "titulo";
    } else if (strlen($titulo) < 5 and empty($hd_chamado)) {//HD 711738
        $msg_erro .= "<br />".traduz('Título muito pequeno')."";
        $campos_erro[] = "titulo";
    }

    if (empty($xtipo_categoria_erro) AND empty($hd_chamado) AND $xtipo_chamado == 5) {//HD 711738
        $campos_erro[] = "tipo_categoria_erro";
    }

    //SETA P/ USUARIO "SUPORTE"
    $fabricante_responsavel = 10;
	if (strlen ($atendente) == 0) {
		$atendente = "435";
		if($xtipo_chamado == 5) {
			$sql = "SELECT tbl_admin.admin FROM tbl_admin where ativo and grupo_admin notnull and tbl_admin.parametros_adicionais::jsonb->>'equipe' ='ultron' order by random() limit 1"; 
		}else{
			$sql = "SELECT tbl_admin.admin FROM tbl_admin, tbl_fabrica WHERE tbl_fabrica.fabrica = $login_fabrica and tbl_admin.fabrica = $fabricante_responsavel and tbl_fabrica.parametros_adicionais::jsonb->>'equipe' = tbl_admin.parametros_adicionais::jsonb->>'equipe' and grupo_admin = 6 and ativo order by random() limit 1"; 
		}
		$res = pg_query($con, $sql);
		if(pg_numrows($res) > 0) {
			$atendente = pg_fetch_result($res,0,'admin');
		}else{
			$sql = "SELECT tbl_admin.admin FROM tbl_admin WHERE tbl_admin.fabrica = $fabricante_responsavel and grupo_admin = 6 and ativo order by random() limit 1"; 
			$res = pg_query($con, $sql);
			if(pg_numrows($res) > 0) {
				$atendente = pg_fetch_result($res,0,'admin');
			}

		}
	}

    if (strlen($comentario) < 2 and strlen($btn_resolvido) == 0) {
        $msg_erro .= "<br />".traduz('Comentário muito pequeno')."";
        $campos_erro[] = "comentario";
    } else {
        $comentario = str_replace($filtro, '', $comentario);
    }

    if ($xtipo_chamado == 5 ) {
        if ($_POST['impacto_financeiro']) {
            $impacto_financeiro = trim($_POST['impacto_financeiro']);

            if($login_fabrica == 159){                
                $campo_prioridade   = $_POST['prioridade'];

                if(strlen($campo_prioridade) == 0){
                    $campos_erro[] = "classPrioridade";
                }

                $campos_adicionais = json_encode(array("impacto_financeiro" => "$impacto_financeiro", "prioridade" => "$prioridade"));

                $field_campos_adicionais = ', campos_adicionais ';
                $value_campos_adicionais = ", '$campos_adicionais' ";

                $campo_previsao     = " previsao_termino_interna, ";
                if($campo_prioridade == "P1"){
                    $funcao_previsao    = " fn_prazo_termino(4::int4, CURRENT_TIMESTAMP), ";
                }else{
                    if($campo_prioridade == "P2"){
                        $dias = "1";
                    }
                    if($campo_prioridade == "P3"){
                        $dias = "3";
                    }
                    if($campo_prioridade == "P4"){
                        $dias = "5";
                    }
                    $dataInicio = date('Y-m-d H:i:s');
                    $funcao_previsao    = " fn_calcula_previsao_retorno('{$dataInicio}', '{$dias}', {$login_fabrica}),   ";
                }
            }else{
                $campos_adicionais = json_encode(array("impacto_financeiro" => "$impacto_financeiro"));

                $field_campos_adicionais = ', campos_adicionais ';
                $value_campos_adicionais = ", '$campos_adicionais' ";
            }
        }
    }

    if ($xtipo_chamado == 8 && empty($arquivo) && empty($hd_chamado)) {
        $msg_erro .= "<br />É obrigatório o anexo do Comprovante de Inscrição e Situação Cadastral. ";
    }

    if(count($campos_erro) > 0){
        $msg_erro .= "<br /> ".traduz('Preencha os campos obrigatórios')."";
    }

    if (strlen($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN TRANSACTION");

        if (strlen($hd_chamado) == 0) {

            $sql = "SELECT admin FROM  tbl_admin WHERE fabrica = $login_fabrica AND help_desk_supervisor IS TRUE;";
            $res = pg_query($con, $sql);
            //$tipo_chamado <> '5'  qdo eh 5  nao cai para aprovacao cai direto no hd 7863

            if (pg_numrows($res) > 0 and !in_array($xtipo_chamado,array(5,8))) {

                $sql2 = "SELECT help_desk_supervisor FROM  tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin";
                $res2 = pg_query($con, $sql2);

                $help_desk_supervisor = pg_result($res2, 0, 'help_desk_supervisor');

                if ($help_desk_supervisor == 't') {
                    //por causa da nova forma de help-desk, os chamados dos supervisores também irão para aprovação
                    $status='Requisitos';
                } else {
                    $status='Requisitos';
                }
	
				if(($telecontrol_distrib or $interno_telecontrol) and !$controle_distrib_telecontrol) {
					$sql = "select sum(h.hora_faturada)+sum(hora_desenvolvimento)  from tbl_hd_franquia h join tbl_fabrica using(fabrica) join tbl_hd_chamado using(fabrica)  where periodo_fim isnull and  ativo_fabrica and (parametros_adicionais ~'telecontrol_distrib' or parametros_adicionais ~'interno_telecontrol') and parametros_adicionais !~'controle_dis' and data_aprovacao < periodo_inicio and status not in ('Resolvido', 'Cancelado') and status !~'Aprova' and hora_desenvolvimento > 0; ";
					$res = pg_query($con, $sql);
					$horas_tc = pg_fetch_result($res,0,0);
					if($horas_tc > 50) $status = 'Aprovação';
				}

            } else {
                $status='Análise';
            }

            $prioridade = 'f';

            if ($status_anterior == 'REABRIR') {

                $prioridade = 't';
                $titulo     = $hd_chamado_anterior .'-'.$titulo_anterior ;

                $xcomentario=strtoupper($comentario);
                // HD 18929
                if (strlen($xcomentario) > 0) {

                    $sql = "CREATE TEMP TABLE tmp_comentario ( comentario text ); INSERT INTO tmp_comentario (comentario)values('$xcomentario');";
                    $res = pg_query($con,$sql);

                    $sql = "SELECT * from tmp_comentario where (comentario ilike '%OK%' or comentario ilike '%OBRIGAD%')";
                    $res = pg_query($con, $sql);

                    if (pg_numrows($res) > 0) {
                        $msg_erro = "Não é necessário responder o chamado com \"OK\" ou \"OBRIGADO\". Para reabrir o chamado basta colocar um comentário sem a palavra OK e Obrigado!";
                    }

                }

            }

            $hd_chamado_ant = (empty($hd_chamado_ant)) ? "null" : $hd_chamado_ant;
            // HD 20496 limitar o tamanho do titulo
            $titulo =  pg_escape_string(substr($titulo,0,50)); //HD 307589 - Sobre o erro de 'título muito pequeno'

            if ($xtipo_chamado == 5 && !empty($impacto_financeiro)  && in_array($login_fabrica, $fabricas_sla)) {
                $prioridade = $impacto_financeiro;
            }

            if (strlen($msg_erro) == 0) {

                $sql =  "INSERT INTO tbl_hd_chamado (
                            admin                                                        ,
                            fabrica                                                      ,
                            fabrica_responsavel                                          ,
                            titulo                                                       ,
                            atendente                                                    ,
                            tipo_chamado                                                 ,
                            prioridade                                                   ,
                            status                                                       ,
                            $campo_previsao 
                            categoria                                                    ,
                            hd_chamado_anterior
                            $field_campos_adicionais
                        ) VALUES (
                            $login_admin                                                 ,
                            $login_fabrica                                               ,
                            $fabricante_responsavel                                      ,
                            '$titulo'                                                    ,
                            $atendente                                                   ,
                            $xtipo_chamado                                               ,
                            '$prioridade'                                                ,
                            '$status'                                                    ,
                            $funcao_previsao 
                            '$xtipo_categoria_erro'                                      ,
                            $hd_chamado_ant
                            $value_campos_adicionais
                        );";
                $res       = pg_query($con, $sql);

                $msg_erro .= substr(pg_errormessage($con), 6);

                $res        = pg_query($con, "SELECT CURRVAL ('seq_hd_chamado')");
                $hd_chamado = pg_result($res, 0, 0);

                //HD 218848: Criação do questionário na abertura do Help Desk
                if ($habilita_questionario) {

                    $sql = " INSERT INTO tbl_hd_chamado_questionario (
                                hd_chamado      ,
                                necessidade     ,
                                funciona_hoje   ,
                                objetivo        ,
                                local_menu      ,
                                http            ,
                                tempo_espera    ,
                                impacto
                                ) VALUES (
                                $hd_chamado     ,
                                '$necessidade'  ,
                                '$funciona_hoje',
                                '$objetivo'     ,
                                '$local_menu'   ,
                                '$http'         ,
                                '$tempo_espera' ,
                                '$impacto'
                                )";
                                
                    $res = pg_query ($con,$sql);
                    $msg_erro .= substr(pg_errormessage($con), 6);
                }

                $dispara_email = "SIM";

            }//fim do inserir chamado

        }

        if (strlen($msg_erro) == 0) {

            if ($status_anterior == 'REABRIR') {

                $sql = "UPDATE tbl_hd_chamado SET resolvido=NOW() WHERE hd_chamado = $hd_chamado_anterior";
                $res = pg_query($con, $sql);

                if (!is_resource($res)) $msg_erro .= substr(pg_last_error($con), 6);

                $link = "<a href=\"adm_chamado_detalhe.php?hd_chamado=$hd_chamado_anterior&consulta=sim\" target=\"_blank\">$hd_chamado_anterior</a>";

                $sql =  "INSERT INTO tbl_hd_chamado_item (
                            hd_chamado                                                     ,
                            comentario                                                     ,
                            status_item                                                    ,
                            admin
                        ) VALUES (
                            $hd_chamado                                                    ,
                            E'Continuação de atendimento do chamado Nº $link'              ,
                            '$status'                                                      ,
                            435
                        );";

                $res = pg_query ($con, $sql);

                if (!is_resource($res)) $msg_erro .= substr(pg_last_error($con),6);

            }

            $sql = "INSERT INTO tbl_hd_chamado_item (
                        hd_chamado   ,
                        comentario   ,
                        status_item  ,
                        admin
                        ) VALUES (
                        $hd_chamado  ,
                        '$comentario',
                        '$status'    ,
                        $login_admin
                    );";


            $res = pg_query($con, $sql);

            if (!is_resource($res)) {

                $msg_erro .= substr(pg_last_error($con),6);

            } else {

                $res             = pg_query($con, "SELECT CURRVAL ('seq_hd_chamado_item')");
                $hd_chamado_item = pg_result($res, 0, 0);

                $sql = "SELECT * FROM tbl_admin
                         WHERE fabrica = $login_fabrica
                         AND help_desk_supervisor = 't'
                         AND ativo IS TRUE";

                $res = pg_query($con, $sql);

            }

            # HD 342829
            $sql    = "UPDATE tbl_hd_chamado SET
                            exigir_resposta = 'f'
                        WHERE hd_chamado = $hd_chamado
                        AND admin IN (SELECT admin FROM tbl_admin WHERE fabrica=tbl_hd_chamado.fabrica)
                        AND   exigir_resposta";

            $res = pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }

        //ROTINA DE UPLOAD DE ARQUIVO
        if (strlen ($msg_erro) == 0 and count($arquivo['name']) > 0) {
        
            // array_search with recursive searching, optional partial matches and optional search by key
            function array_rfind($needle, $haystack, $partial_matches = false, $search_keys = false) {
                if(!is_array($haystack)) return false;
                foreach($haystack as $key=>$value) {
                    $what = ($search_keys) ? $key : $value;
                    if($needle===$what) return $key;
                    else if($partial_matches && @strpos($what, $needle)!==false) return $key;
                    else if(is_array($value) && array_rfind($needle, $value, $partial_matches, $search_keys)!==false) return $key;
                }
                return false;
            }

            for ($i=0; $i < count($arquivo['name']); $i++) {                 
                if (strlen($arquivo['name'][$i]) == 0) {                    
                    continue;
                }                

                if ($arquivo['error'][$i]==1) {
                    $msg_erro.= 'O arquivo não pôde ser anexado.<br>';
                }

                if (strlen($arquivo["tmp_name"][$i]) > 0 && $arquivo["tmp_name"][$i] != "" and !$msg_erro) {

                    $a_tipos = array(
                        /* Imagens */
                        'bmp'   => 'image/bmp',
                        'gif'   => 'image/gif',
                        'ico'   => 'image/x-icon',
                        'jpg'   => 'image/jpeg;image/pjpeg',
                        'jpeg'  => 'image/jpeg;image/pjpeg',
                        'png'   => 'image/png;image/x-png',
                        'tif'   => 'image/tiff',
                        /* Texto */
                        'csv'   => 'text/comma-separated-values;text/csv;application/vnd.ms-excel',
                        'eps'   => 'application/postscript',
                        'pdf'   => 'application/pdf',
                        'ps'    => 'application/postscript',
                        'rtf'   => 'text/rtf',
                        'tsv'   => 'text/tab-separated-values;text/tsv;application/vnd.ms-excel',
                        'txt'   => 'text/plain',
                        /* Office */
                        'doc'   => 'application/msword',
                        'ppt'   => 'application/vnd.ms-powerpoint',
                        'xls'   => 'application/vnd.ms-excel',
                        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        /* Star/Open/BR/LibreOffice.org */
                        'odt'   => 'application/vnd.oasis.opendocument.text;application/x-vnd.oasis.opendocument.text',
                        'ods'   => 'application/vnd.oasis.opendocument.spreadsheet;application/x-vnd.oasis.opendocument.spreadsheet',
                        'odp'   => 'application/vnd.oasis.opendocument.presentation;application/x-vnd.oasis.opendocument.presentation',
                        /* Compactadores */
                        'sit'   => 'application/x-stuffit',
                        'hqx'   => 'application/mac-binhex40',
                        '7z'    => 'application/octet-stream',
                        'lha'   => 'application/octet-stream',
                        'lzh'   => 'application/octet-stream',
                        'rar'   => 'application/octet-stream;application/x-rar-compressed;application/x-compressed',
                        'zip'   => 'application/zip'
                    );

                    // Pega extensão do arquivo
                    $a_att_info   = pathinfo($arquivo['name'][$i]);
                    $ext          = $a_att_info['extension'];
                    $arquivo_nome = $a_att_info['filename']; // Tira a extensão do nome... PHP 5.2.0+
    				$arquivo_type = $arquivo['type'][$i];
                    $aux_extensao = "'$ext'";

                    if ($xtipo_chamado == 5 && empty($arquivo_nome)) {
                        if($sistema_lingua == 'ES'){
                            $msg_erro .= "<br /> Para una llamada de error, adjunte una <i>PrintScreen</i> (imagen JPG, preferible) de la pantalla donde ocorrió el error. <br />";
                        }else{
                            $msg_erro .= "<br /> Para chamado de erro, por favor, anexe um <i>PrintScreen</i> (imagem JPG, de preferência) da tela aonde o erro ocorreu. <br />";
                        }
                    }

                    // Verifica o mime-type do arquivo, ou a extensão
                    $tipo = ($arquivo['type'][$i] != '') ? array_rfind($arquivo_type, $a_tipos, true) : array_key_exists($ext, $a_tipos);
                    if ($arquivo['type'][$i] == 'application/octet-stream') {
                    // Tem navegadores que usam o 'application/octet-stream' para tipos desconhecidos...
                        $tipo = array_key_exists($ext, $a_tipos);
                    }

                    if (!$tipo) {// Verifica tamanho do arquivo

                        $msg_erro.= "Arquivo em formato inválido!<br>";

                    }

                    $caminho = "documentos/hd-".$hd_chamado."-itens";
                    if (!is_dir($caminho)) {
                        system("mkdir -m 777 $caminho");
                    }

                    if (strlen($msg_erro) == 0) { // Processa o arquivo
                        //  Substituir tudo q não for caracteres aceitos para nome de arquivo para '_'

                        $arquivo_nome = preg_replace("/[^a-zA-Z0-9_-]/", '_', retira_acentos($arquivo_nome));
                        $nome_anexo   = strtolower(dirname(__FILE__)."/documentos/hd-".$hd_chamado."-itens/" . $hd_chamado_item . '-' . $arquivo_nome . '.' . $ext);
                    }
                    
                    if (strlen($msg_erro) == 0) {                                         
                        if (!move_uploaded_file($arquivo["tmp_name"][$i], $nome_anexo)) $msg_erro = traduz("O arquivo não foi anexado!")."<br/>";
                    }
                }   
            }
        }//fim do upload

        //ENVIA EMAIL PARA SUPERVISOR DA FÁBRICA
        $sql="SELECT admin,
                     email
                FROM tbl_admin
                WHERE fabrica = $login_fabrica
                AND help_desk_supervisor IS TRUE
                AND ativo IS TRUE";

        @$res     = pg_query($con,$sql);
        $tot_sups = pg_num_rows($res);

        if ($tot_sups > 0 and strlen($msg_erro) == 0) {

            /* 08/09/2010 MLG - HD 291166 - Ao invés de enviar uma mensagem por e-mail, manda um só para o total de destinatários (normalmente tem 1, máx. 3)*/
            for ($i = 0 ; $i < $tot_sups; $i++) {
                $email_supervisor[] = trim(pg_result($res,$i,email));
            }

            $email_destino = implode(',', array_map('is_email', $email_supervisor));

            if ($email_destino != '' AND strlen($dispara_email) > 0 AND strlen($msg_erro) == 0) {

                $email_origem = "helpdesk@telecontrol.com.br";
                $assunto      = "Novo Chamado aberto";

                $body_top  = "MIME-Version: 1.0\n";
                $body_top .= "From: $email_origem\n"; // Mudei o local do 'From:', para ficar mais claro.
                //$body_top .= "--Message-Boundary\n";
                $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                $body_top .= "Content-transfer-encoding: 8BIT\n";
                $body_top .= "Content-description: Mail message body\n\n";

                $corpo  = "<br>Foi inserido um novo CHAMADO no HELP DESK do sistema TELECONTROL ASSIST e é necessário a sua análise para aprovação.<br>";
                $corpo .= "<br>Chamado n°: $hd_chamado\n\n";
                $corpo .= "<br>Titulo: $titulo\n";
                $corpo .= "<br>Solicitante: $nome <br>Email: $email\n\n";
                $corpo .= "<br><a href='http://posvenda.telecontrol.com.br/assist/helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a>";
                $corpo .= "<br><br>Telecontrol\n";
                $corpo .= "<br>www.telecontrol.com.br\n";
                $corpo .= "<br>_______________________________________________\n";
                $corpo .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

               if ($mailer->sendMail($email_destino, stripslashes($assunto), $corpo, $email_origem)) {

                } else {
                    $msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.<br>";
                }

            }

        }

        //ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

        if (strlen($dispara_email) > 0 AND strlen($msg_erro) == 0) {

            $email_origem  = "helpdesk@telecontrol.com.br";
            $email_destino = "helpdesk@telecontrol.com.br";

            // HD  24442
            if ($xtipo_chamado == '5') { // se for chamado de erro manda email diferenciado
                $assunto = "ERRO - Novo Chamado de ERRO aberto";
            }

            if ($status_anterior == 'REABRIR') {

                $assunto = "Chamado REABERTO - Referente ao Chamado ".$hd_chamado_anterior;

                $corpo  = "";
                $corpo .= "<br>O chamado ".$hd_chamado_anterior." que estava RESOLVIDO foi reaberto.\n\n";
                $corpo .= "<br>Foi aberto um novo chamado com n°: $hd_chamado<br>\n";
                $corpo .= "<br>Titulo: ".$titulo." \n";
                $corpo .= "<br>Solicitante: ".$nome." <br>Email: ".$email."\n\n";
                $corpo .= "<br>Comentário inserido: <br><p><i>".$comentario."</i></p><br>\n";

                $corpo .= "<br><a href='http://posvenda.telecontrol.com.br/assist/helpdesk/adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
                $corpo .= "<br><br>Telecontrol\n";
                $corpo .= "<br>www.telecontrol.com.br\n";
                $corpo .= "<br>_______________________________________________\n";
                $corpo .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

                $body_top  = "MIME-Version: 1.0\n";
                //$body_top  = "--Message-Boundary\n";
                $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                $body_top .= "Content-transfer-encoding: 8BIT\n";
                $body_top .= "Content-description: Mail message body\n\n";

                $cabecalho  = "MIME-Version: 1.0 \r";
                $cabecalho .= "Content-type: text/html; charset=iso-8859-1 \r";
                $cabecalho .= "From: email_origem";

                if ($mailer->sendMail($email_destino, stripslashes($assunto), $corpo, $email_origem)) {
                    if($sistema_lingua == 'ES'){
                        $msg .= "<br>Se ha enviado un correo electrónico a: ".$email_destino."<br>";
                    }else{
                        $msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
                    }
                } else {
                    if($sistema_lingua == 'ES'){
               #         $msg_erro.= "No se puede enviar un correo electrónico. Por favor, póngase en contacto con TELECONTROL.<br>";
                    }else{
              #          $msg_erro.= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.<br>";
                    }
                }
            }
        }

        //ENVIA EMAIL PARA ANALISTA CASO O CHAMADO ESTEJA COMO EXIGIR RESPOSTA
        //if ($exigir_resposta == 't') {
        //ENVIAR EMAIL SEMPRE QUE ALGUÉM FIZER INTERAÇÃO 21/05/2010 - Samuel

        /**
         * -> MELHORIA:
         * - Todo chamado com EXIGIR_RESPOSTA marcado
         * enviará email com o nome do atendente
         * no ASSUNTO do email
         */

        if (strlen($msg_erro) == 0 && strlen($_POST["hd_chamado"]) > 0) {

            $email_origem  = "helpdesk@telecontrol.com.br";
            $email_destino = "helpdesk@telecontrol.com.br";

            $sql = "SELECT  tbl_admin.email,
                            tbl_admin.nome_completo AS nome_atendente,
                            admin.email as admin_email,
                            admin.nome_completo,
                            tbl_fabrica.nome,
                            tbl_hd_chamado.titulo,
                            tbl_hd_chamado.exigir_resposta
                    FROM  tbl_admin
                    JOIN  tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_admin.admin
                    JOIN    tbl_admin admin ON tbl_hd_chamado.admin = admin.admin
                    JOIN    tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
                    WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";

            $res = pg_query($con, $sql);

            if (pg_numrows($res) > 0) {
                $email_destino      = trim(pg_result($res,0,0));
                $email              = trim(pg_result($res,0,'email'));
                $nome_atendente     = trim(pg_result($res,0,'nome_atendente'));
                $admin_email        = trim(pg_result($res,0,'admin_email'));
                $nome               = trim(pg_result($res,0,'nome_completo'));
                $fabrica            = trim(pg_result($res,0,'nome'));
                $titulo             = trim(pg_result($res,0,'titulo'));
                $exigir_resposta    = trim(pg_result($res,0,'exigir_resposta'));
            }

            if ($email_destino != "suporte.fabricantes@telecontrol.com.br") {
                $email_destino = (strlen($email_destino) > 0) ? "$email_destino, " : '';
                $email_destino.= 'suporte.fabricantes@telecontrol.com.br';
            }
            $assunto = ($exigir_resposta === true)
                ? $nome_atendente.", "
                : "";
            $assunto .= "Interação no chamado $hd_chamado - ".strtoupper($fabrica);

            $body_top  = "MIME-Version: 1.0\n";
            //$body_top  = "--Message-Boundary\n";
            $body_top .= "From: $email_origem\n";
            $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
            $body_top .= "Content-transfer-encoding: 8BIT\n";
            $body_top .= "Content-description: Mail message body\n\n";

            $corpo  = "";
            $corpo .= "<br>O chamado $hd_chamado que estava aguardando resposta recebeu uma interação por parte do admin.\n\n";
            $corpo .= "<br>Chamado n°: $hd_chamado\n\n";
            $corpo .= "<br>Titulo: $titulo\n";
            $corpo .= "<br>Solicitante: $nome <br>Email: $admin_email\n\n";
            $corpo .= "<br>Fabrica: $fabrica\n\n";
            $corpo .= "<br>Interação: $comentario\n\n";
            $corpo .= "<br><br>Telecontrol\n";
            $corpo .= "<br>www.telecontrol.com.br\n\n";
            $corpo .= "<br>_______________________________________________\n";
            $corpo .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

            if (mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top)) {
                if($sistema_lingua == 'ES'){
                    $msg .= "<br>Se ha enviado un correo electrónico a: ".$email_destino."<br>";
                }else{
                    $msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
                }
            }

        }

        if(strlen($msg_erro) == 0 && strlen($_POST["hd_chamado"]) == 0){

            $sql = "SELECT nome, parametros_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            $status_sla = false;

            if(pg_num_rows($res) > 0){

                $nome_fabrica = pg_fetch_result($res, 0, "nome");
                $parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

                $parametros_adicionais = json_decode($parametros_adicionais, true);

                if(isset($parametros_adicionais["fabricante_sla"])){

                    if($parametros_adicionais["fabricante_sla"] == "t"){

                        $status_sla = true;

                        $email_origem  = "helpdesk@telecontrol.com.br";

                        $email_destino = "joao.junior@telecontrol.com.br, ronaldo@telecontrol.com.br, ricardo.tamiao@telecontrol.com.br, suporte@telecontrol.com.br";

                        $assunto = "PRIORIDADE - Chamado {$hd_chamado} com SLA - {$nome_fabrica} ";

                        $corpo  = "";
                        $corpo .= "<br>O chamado ".$hd_chamado." foi aberto com SLA para a {$nome_fabrica}, por favor verificar com PRIORIDADE.\n\n";
                        $corpo .= "<br>Titulo: ".$titulo." \n";
                        $corpo .= "<br>Comentário inserido: <br><p><i>".$comentario."</i></p><br>\n";

                        $corpo .= "<br><a href='http://posvenda.telecontrol.com.br/assist/helpdesk/adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
                        $corpo .= "<br><br>Telecontrol\n";
                        $corpo .= "<br>www.telecontrol.com.br\n";
                        $corpo .= "<br>_______________________________________________\n";
                        $corpo .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

                        $body_top  = "MIME-Version: 1.0\n";
                        //$body_top  = "--Message-Boundary\n";
                        $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                        $body_top .= "Content-transfer-encoding: 8BIT\n";
                        $body_top .= "Content-description: Mail message body\n\n";

                        $cabecalho  = "MIME-Version: 1.0 \r";
                        $cabecalho .= "Content-type: text/html; charset=iso-8859-1 \r";
                        $cabecalho .= "From: $email_origem";

                        $mailer->sendMail($email_destino, stripslashes($assunto), $corpo, $email_origem);

                        $msg .= "<br /> Chamado aberto com Sucesso! <br />";

                    }
                }

            }

            if($status_sla == false){

                $email_origem  = "helpdesk@telecontrol.com.br";
                $email_destino = "helpdesk@telecontrol.com.br";

                $sql = "SELECT  tbl_admin.email,
                                tbl_admin.nome_completo AS nome_atendente,
                                admin.email as admin_email,
                                admin.nome_completo,
                                tbl_fabrica.nome,
                                tbl_hd_chamado.titulo,
                                tbl_hd_chamado.exigir_resposta
                        FROM  tbl_admin
                        JOIN  tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_admin.admin
                        JOIN    tbl_admin admin ON tbl_hd_chamado.admin = admin.admin
                        JOIN    tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
                        WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";

                $res = pg_query($con, $sql);

                if (pg_numrows($res) > 0) {
                    $email_destino      = trim(pg_result($res,0,0));
                    $email              = trim(pg_result($res,0,'email'));
                    $nome_atendente     = trim(pg_result($res,0,'nome_atendente'));
                    $admin_email        = trim(pg_result($res,0,'admin_email'));
                    $nome               = trim(pg_result($res,0,'nome_completo'));
                    $fabrica            = trim(pg_result($res,0,'nome'));
                    $titulo             = trim(pg_result($res,0,'titulo'));
                    $exigir_resposta    = trim(pg_result($res,0,'exigir_resposta'));
                }

                if ($email_destino != "suporte.fabricantes@telecontrol.com.br") {
                    $email_destino = (strlen($email_destino) > 0) ? "$email_destino, " : '';
                    $email_destino.= 'suporte.fabricantes@telecontrol.com.br';
                }
                $assunto = ($exigir_resposta === true)
                    ? $nome_atendente.", "
                    : "";
                $assunto .= "Interação no chamado $hd_chamado - ".strtoupper($fabrica);

                $body_top  = "MIME-Version: 1.0\n";
                //$body_top  = "--Message-Boundary\n";
                $body_top .= "From: $email_origem\n";
                $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                $body_top .= "Content-transfer-encoding: 8BIT\n";
                $body_top .= "Content-description: Mail message body\n\n";

                $corpo  = "";
                $corpo .= "<br>O chamado $hd_chamado que estava aguardando resposta recebeu uma interação por parte do admin.\n\n";
                $corpo .= "<br>Chamado n°: $hd_chamado\n\n";
                $corpo .= "<br>Titulo: $titulo\n";
                $corpo .= "<br>Solicitante: $nome <br>Email: $admin_email\n\n";
                $corpo .= "<br>Fabrica: $fabrica\n\n";
                $corpo .= "<br>Interação: $comentario\n\n";
                $corpo .= "<br><br>Telecontrol\n";
                $corpo .= "<br>www.telecontrol.com.br\n\n";
                $corpo .= "<br>_______________________________________________\n";
                $corpo .= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

                if (mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top)) {
                    if($sistema_lingua == 'ES'){
                        $msg .= "<br>Se ha enviado un correo electrónico a: ".$email_destino."<br>";
                    }else{
                        $msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
                    }
                }

            }
        }

        if (strlen($msg_erro) > 0) {
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
            if($sistema_lingua == 'ES'){
                $msg_erro .= ' No se puede insertar la llamada. <br />';
            }else{
                $msg_erro .= ' Não foi possível Inserir o Chamado. <br />';
            }
        } else {
            $res = pg_query($con,"COMMIT");
            header ("Location: $PHP_SELF?hd_chamado=$hd_chamado&msg=$msg");
            //exit;
        }

    }

}

if (strlen($hd_chamado) > 0) {
    //HD 197505 - Retiradas 16 linhas que faziam a atualização automática do campo tbl_hd_chamado.resolvido automaticamente
    //caso existam problemas, olhar arquivos nao_sync anteriores à resolução do chamado

    $sql= " SELECT tbl_hd_chamado.hd_chamado                              ,
                    tbl_hd_chamado.admin                                 ,
                    to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data   ,
                    tbl_hd_chamado.titulo                                ,
                    tbl_hd_chamado.categoria                             ,
                    tbl_hd_chamado.status                                ,
                    tbl_hd_chamado.prioridade                             ,
                    tbl_hd_chamado.atendente                             ,
                    tbl_hd_chamado.fabrica_responsavel                   ,
                    tbl_hd_chamado.resolvido                             ,
                    tbl_hd_chamado.tipo_chamado                          ,
                    tbl_fabrica.nome                                     ,
                    tbl_admin.login                                      ,
                    tbl_admin.nome_completo                              ,
                    tbl_admin.fone                                       ,
                    tbl_admin.email                                      ,
                    tbl_hd_chamado.campos_adicionais                     ,
                    (select nome_completo from tbl_hd_chamado_item join tbl_admin using(admin) where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado and (grupo_admin = 6 or tbl_admin.admin in (435,586,8820)) order by hd_chamado_item desc limit 1) AS atendente_nome
            FROM tbl_hd_chamado
            JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
            WHERE hd_chamado = $hd_chamado";

    $res = pg_query ($con,$sql);

    if (pg_numrows($res) > 0) {
        $hd_chamado           = pg_result($res,0,hd_chamado);
        $admin                = pg_result($res,0,admin);
        $data                 = pg_result($res,0,data);
        $titulo               = pg_result($res,0,titulo);
        $categoria            = pg_result($res,0,categoria);
        $status               = pg_result($res,0,status);
        $atendente            = pg_result($res,0,atendente);
        $resolvido            = pg_result($res,0,resolvido);
        $fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
        $nome                 = pg_result($res,0,nome_completo);
        $email                = pg_result($res,0,email);
        $fone                 = pg_result($res,0,fone);
        $fabrica_nome         = pg_result($res,0,nome);
        $login                = pg_result($res,0,login);
        $atendente_nome       = pg_result($res,0,atendente_nome);
        $atendente_nome       = (empty($atendente_nome)) ? "Suporte Telecontrol" : $atendente_nome;
        $tipo_chamado         = pg_result($res,0,tipo_chamado);
        $prioridade           = pg_result($res,0,prioridade);

        if($login_fabrica == 159){
            $campos_adicionais  = pg_fetch_result($res, 0, campos_adicionais);
            $campos_adicionais  = json_decode($campos_adicionais, true);
            $campo_prioridade   = $campos_adicionais['prioridade'];
        }

        if ($tipo_chamado == 5 && $prioridade == "1"  && in_array($login_fabrica, $fabricas_sla)) {
            $ximpacto_financeiro = 'Sim';
        }

        if ($tipo_chamado == 5 && $prioridade == "2"  && in_array($login_fabrica, $fabricas_sla)) {
            $ximpacto_financeiro = 'Não';
        }


        //HD 218848: Criação do questionário na abertura do Help Desk
        $sql = "SELECT * FROM tbl_hd_chamado_questionario WHERE hd_chamado=$hd_chamado";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
            $mostra_questionario = true;
            $necessidade    = pg_result($res, 0, necessidade);
            $funciona_hoje  = pg_result($res, 0, funciona_hoje);
            $objetivo       = pg_result($res, 0, objetivo);
            $local_menu     = pg_result($res, 0, local_menu);
            $http           = pg_result($res, 0, http);
            $tempo_espera   = pg_result($res, 0, tempo_espera);
            $impacto        = pg_result($res, 0, impacto);
        }

    } else {
        if($sistema_lingua == 'ES'){
            $msg_erro .= "Llamada no encontrada.";
        }else{
            $msg_erro .="Chamado não encontrado.";
        }
    }

} else {

    if ($habilita_questionario) {
        $mostra_questionario = true;
    }

    $login  = $login_login;
    $data   = date("d/m/Y");

    $sql    = "SELECT * FROM tbl_admin WHERE admin = $login_admin";
    $resX   = pg_query($con,$sql);

    $nome  = pg_result($resX, 0, 'nome_completo');
    $email = pg_result($resX, 0, 'email');
    $fone  = pg_result($resX, 0, 'fone');

}

$tc_com_hoje = strtotime('now');

$TITULO = ($sistema_lingua == 'ES') ? "Lista de Solicitudes - Telecontrol Help-Desk" : "Lista de Chamados - Telecontrol Help-Desk";
include "menu.php";

if ($tc_com_hoje > strtotime('01/30/2012 07:00:00') and $tc_com_hoje < strtotime('01/30/2012 22:59:00')) {
    include 'aviso_bloqueio_HelpDesk.html';
}?>

<style>
    .btn {
        font-size: 12px;
        font-family: Arial;
        color:#00CC00;
        font-weight: bold;
    }

    .error{
        /*background-color: #FA8072;*/
        border:1px solid #ff0000 !important;

    }

    .questionarioCaixa{
            border-right: #6699cc 1px solid;
            border-top: #6699cc 1px solid;
            font: 8pt arial ;
            border-left: #6699cc 1px solid;
            border-bottom: #6699cc 1px solid;
            background-color: #ffffff;
            width: 500px;
    }
    div#divReqAprova {
        margin:auto;
        margin-top: 1ex;
        width:740px;
        background-color:darkgreen;
        padding: 1px 0.5ex;
    }

    table.relatorio {
        font-family: Verdana;
        width: 750px;
        max-width: 750px;
        min-width: 750px;
        margin:auto;
        border-left: 1px solid #8BA4EB;
        border-right: 1px solid #8BA4EB;
    }
    .relatorio_titulo {
        font:bold 14px 'Verdana' !important;
        background: #3e83c9;
        color: #fff;
        padding: 2px 2px;
        text-align: center !important;
        border-left: 1px solid #8BA4EB;
        border-right: 1px solid #8BA4EB;
        line-height: 1.2;
        padding-top: 5px;
        padding-bottom: 5px;
    }

    table.relatorio th {
        font:bold 11px 'Verdana' !important;
        background: #3e83c9;
        color: #fff;
        padding: 2px 2px;
        text-align: left !important;
        padding-top: 5px;
        padding-bottom: 5px;
    }

    table.relatorio td {
        font-family: Verdana;
        font-size: 11px;
        padding: 1px 5px 5px 5px;
        border-bottom: 1px solid #95bce2;
        line-height: 15px;
    }

    table.relatorio tr:hover {
        background: #bcd4ec;
    }

    dt,dd {
        display:inline-block;
        font-size: 13px;
    }
    dt {
        width:150px;
        font-weight: bold;
    }
    dd {
        width: 50px;
        text-align: right;
    }
</style>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

<!-- jQuery / Plugin Editor -->
<script type='text/javascript' src='../admin/js/jquery-1.3.2.js'></script>
<script src="../plugins/ckeditor/ckeditor.js"></script>

<script type='text/javascript'>


    $(window).load(function(){
        CKEDITOR.replace("comentario", { enterMode : CKEDITOR.ENTER_BR, toolbar : 'Basic', uiColor : '#A0BFE0', width: 500 });
    });


    // Mensagem sobre hora técnica. Mostrar sempre que for aberto um chamado de DESENVOLVIMENTO
    var msgHoraTecnica = "*** ATENÇÃO ***\n\nA abertura de chamado para desenvolvimento tem o custo de uma hora técnica.\n\n";
        msgHoraTecnica+= "Após a gravação desse chamado não haverá possibilidade de cancelamento da TAXA DE ABERTURA, ";
        msgHoraTecnica+= "seu SUPERVISOR terá 30 dias para autorizar a Telecontrol, caso contrário o CHAMADO será baixado automaticamente.\n\n";
        msgHoraTecnica+= "Tem certeza que autoriza o faturamento e confirma esse chamado?";




    $(document).ready(function() {
        Shadowbox.init();
    });

    function verificaTipoChamado() {

        switch ($("#combo_tipo_chamado").val()) {

            case "5":
                chamadoErroAberto();
                $(".escondeparaerro").css("display", "none");
                $(".mostraparaerro").css("display", "table-row");
                $("#tempo_espera").val("0");
            break;

            case "8":
                $(".mostraparaerro").css("display", "none");
               $(".esconde").css("display", "none");
            break;
            default:
                $(".escondeparaerro").css("display", "table-row");
                $(".mostraparaerro").css("display", "none");
                $("#tempo_espera").val("7");
        }
    }

    function chamadoErroAberto(){
        $.ajax({
            url: "<?php echo $PHP_SELF;?>",
            type: "POST",
            data: "ajax=ajax&tipo_ajax=verificaErro&hd_chamado=<?php echo $hd_chamado;?>&msg_erro=<?php echo $msg_erro;?>",
            success: function(resposta){
                if(resposta > 0){
                    Shadowbox.open({
                        content:    "helpdesk_lista_chamado_erro.php",
                        player:     "iframe",
                        title:      '<?=traduz("Chamado de Erro")?>',
                        width:      800,
                        height:     500
                    });
                }
            }
        });
    }

    function novo_arquivo(el) {
        var inserirArquivo = "Arquivo <input type='file' name='arquivo[]' size='70' class='Caixa' /><br><br>";
        $('#novo_arquivo').append(inserirArquivo);
    }

    $(function() {
        //verificaTipoChamado();
        //document.frm_chamado.titulo.focus();

        $('#tipo_chamado_bloqueado').focus(function() {
            $('#window_box').fadeIn('normal');
        });
        $('#aprovaRequisitos').click(function(){
            var link = window.location.href;
            if (link.indexOf("?hd_chamado")<0) {
                alert("Nenhum chamado selecionado!");
                return true;
            }
            if (confirm("<?=traduz('Confirma a aprovação dos requisitos relacionados?')?>")) {
                window.location.href = link + "&aprovaRequisitos=sim";
            }
            return true;
        });
        $('#btn_enviar').click(function(){
            <?php if (in_array($login_fabrica, $fabricas_sla) and empty($hd_chamado)) {?>
            ///somente sla aparecer e obrigar

            /*if ($("#combo_tipo_chamado").val() == 5 && $("#impacto_financeiro").val() == '') {
                alert('Selecione o Impacto Financeiro');
                $("#impacto_financeiro").focus();
                return false;
            }*/

	        <?php }?>
            var msgBtn = $(this).val();
            $(this).attr('disabled', true);
            $(this).val('Enviando...');

            $('form[name=frm_chamado]').submit();
        });

        $(".classPrioridade").change(function(){
            var classPriori = $(".classPrioridade").val();
            var tamanho = '';

            if(classPriori != '' || classPriori.length > 0){            
                if(classPriori == 'P1'){
                    tamanho = 340;
                }else if(classPriori == 'P2'){
                    tamanho = 470;
                }else if(classPriori == 'P3'){
                    tamanho = 340;
                }else if(classPriori == 'P4'){
                    tamanho = 180;
                }
                Shadowbox.open({
                    content:    "helpdesk_classificacao_sla.php?classificacao="+classPriori,
                    player:     "iframe",
                    title:      "Classificação SLA",
                    width:      800,
                    height:     tamanho
                });
            }
        });
    });

    $(window).load(function(){
        verificaTipoChamado();
    });

</script>
<?php
    $queryString = "";
    if ($_GET['tipo'] == 8 && strlen($_GET['posto']) > 0) {
        $queryString = "?tipo=$tipo&posto=$posto";

        $sql = "SELECT tbl_posto_fabrica.codigo_posto,
                       tbl_posto.nome
                  FROM tbl_posto
                  JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                 WHERE tbl_posto.posto = $posto
                ";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $codigo_posto = pg_fetch_result($res, 0, "codigo_posto");
            $nome_posto   = pg_fetch_result($res, 0, "nome");
            if($sistema_lingua == 'ES'){
                $titulo = 'Cambio en el Registro del Puesto: ' . $codigo_posto . ' - ' . $nome_posto;
            }else{
                $titulo = 'Alteração Cadastral do Posto: ' . $codigo_posto . ' - ' . $nome_posto;
            }
        }
    }
?>
<form name='frm_chamado' action='<? echo $PHP_SELF.$queryString ?>' method='POST' enctype='multipart/form-data' >
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado ?>'>
<input type='hidden' name='status' value='<?= $status ?>'>

<table width = '750' align = 'center' border='0' cellpadding='2'  style='font-family: verdana ; font-size: 11px'>
<tr>
    <td width="140"bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong><?if($sistema_lingua == 'ES')echo "&nbsp;Apertura"; else echo "&nbsp;Abertura";?> </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $data ?> </td>

    <td  width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px' valign='middle'><strong><?if($sistema_lingua=='ES')echo "Llamado";else echo "Chamado";?></strong></td>
    <td     width="100"         bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;
    <? if(strlen($hd_chamado)>0){ ?>
    <font color='#CC1136'><strong>&nbsp;<?=$hd_chamado?> </strong></font>
    <?}?>
    </td>

</tr><?php


if (strlen ($hd_chamado) > 0) {

    if ($sistema_lingua == "ES") {

        if ($status == "Aguard.Admin")            $status = "Espera De Administrador";
        if ($status == "Aguard.Execução")         $status = "Espera Ejecución";
        if ($status == "Aprovação")               $status = "Aprobación:";
        if ($status == "Análise")                 $status = "Análisis";
        if ($status == "Correção")                $status = "Corrección";
        if ($status == "Documentação")            $status = "Documentación";
        if ($status == "EfetivaçãoHomologação")   $status = "Aprobación De Efectividad";
        if ($status == "Efetivação")              $status = "Efetividad";
        if ($status == "Execução")                $status = "Ejecución";
        if ($status == "Orçamento")               $status = "Presupuesto";
        if ($status == "Novo")                    $status = "Nuevo";
        if ($status == "Parado")                  $status = "Detenido";
        if ($status == "Resolvido")               $status = "Resuelto";
        if ($status == "Suspenso")                $status = "Suspendido";
        if ($status == "Validação")               $status = "Validación";
        if ($status == "ValidaçãoHomologação")    $status = "Aprobación De Validación";

    }?>

    <tr>
        <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?=traduz('Status')?> </strong></td>
        <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?= $status ?> </td>
        <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Analista </strong></td>
        <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?=$atendente_nome?> </strong></td>
    </tr><?php

}?>

<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<? if($sistema_lingua=='ES')echo "Nombre Usuario";else echo "Nome Usuário";?> </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=$nome ?></td>
    <td  bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Login </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=$login ?> </td>
</tr>
<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Correo";else echo "e-mail";?> </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=$email ?></td>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Contacto";else echo "Contato";?> </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=$fone ?></td>
</tr>

<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?=traduz('Título')?> </strong></td>
<?  if (strlen ($hd_chamado) > 0) { ?>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?=stripslashes($titulo)?></td>
<?  } else {    ?>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
        <input type='text' size='60' name='titulo' maxlength='50' value="<?=stripslashes($titulo)?>" 
        <?php if (strlen ($hd_chamado) > 0 || (isset($_GET['tipo']) && $_GET['tipo'] == 8)) { echo " readonly ";}?> class='Caixa <?=(in_array('titulo', $campos_erro)? 'error': '')?> ' valign='middle'>
    </td>
<?  }   ?>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Tipo </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
<?
    if ($sistema_lingua != 'ES') {
        $sql = "SELECT  tipo_chamado,
                        descricao
                FROM tbl_tipo_chamado
                WHERE  tipo_chamado NOT IN (2,3,6,7,9)
                ORDER BY descricao;";
        $res = @pg_query($con,$sql);
        if(pg_numrows($res)>0){
            $temp = pg_fetch_all($res);
            foreach($temp as $tipo_data) {
                $tipo_de_hd[$tipo_data['tipo_chamado']] = $tipo_data['descricao'];
            }
            unset($temp);
        }
    } else {
        $tipo_de_hd = array (
            1   => 'Alteración de datos',
            5   => 'Error en programa',
            4   => 'Nuevo progama o proceso',
            7   => 'Orientación al cliente',
            8   => 'Cambio en el Registro del Puesto',
        );
    }
    // die('<pre>'.print_r($a_tipo_de_hd, true).'</pre>');
        if (strlen ($hd_chamado) > 0 and strlen($tipo_chamado)>0) { // Se não é chamado novo e já tem o tipo (deveria...) ?>
            <input type='hidden' size='60' value='<?=$tipo_chamado?>' name='combo_tipo_chamado_2'>
            <input type='hidden' size='60' value='<?=$tipo_chamado?>' id='combo_tipo_chamado' disabled>
            <?=$tipo_de_hd[$tipo_chamado]?>

<?php
    } elseif ($tipo == 8 && strlen($posto) > 0) {
?>
        <input type='hidden' size='60' value='<?php echo $tipo;?>' name="combo_tipo_chamado" id='combo_tipo_chamado'>
        <?php echo $tipo_de_hd[$tipo];?>
<?php
    } else {
?>
            <select name="combo_tipo_chamado" id="combo_tipo_chamado" onchange='verificaTipoChamado()' class='<?=(in_array('tipo_chamado', $campos_erro)? 'error': '')?>'>
                <option value=''><?=traduz('Selecione')?></option> <!-- hd_chamado=2707422 -->
<?          foreach($tipo_de_hd as $tipo_tipo=>$tipo_desc) {
                $sel = ($tipo_tipo == $_POST['combo_tipo_chamado']) ? ' SELECTED' : '';

?>              <option value="<?=$tipo_tipo?>"<?=$sel?>><?=$tipo_desc?></option>
<?          }
?>          </select>
<?      }
?>
    </td>
</tr>
<?php 

//if (in_array($login_fabrica, $fabricas_sla)) { ?>
<tr class='mostraparaerro'>
    <?php if($login_fabrica == 159){ ?>
        <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
        <strong>&nbsp;<?=traduz('Prioridade')?> </strong>
    </td>

    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
        <?php
            if (empty($hd_chamado)) {
        ?>
        <select name='prioridade' class="classPrioridade <?=(in_array('classPrioridade', $campos_erro)? 'error': '')?>"  >
            <option value="">Selecione</option>
            <option value="P1" <?php if($campo_prioridade == 'P1'){ echo " selected "; } ?> >P1</option>
            <option value="P2" <?php if($campo_prioridade == 'P2'){ echo " selected "; } ?> >P2</option>
            <option value="P3" <?php if($campo_prioridade == 'P3'){ echo " selected "; } ?> >P3</option>
            <option value="P4" <?php if($campo_prioridade == 'P4'){ echo " selected "; } ?> >P4</option>
        </select>
        <?php }else{ 
            echo "&nbsp;".$campo_prioridade;
        } ?>
    </td>    
    <?php } ?>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
        <strong>&nbsp;<?=traduz('Impacto Financeiro')?> </strong>
    </td>

    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
        <?php
            if (empty($hd_chamado)) {
        ?>
            <select name='impacto_financeiro' id='impacto_financeiro' class="<?=(in_array('impacto_financeiro', $campos_erro)? 'error': '')?>" >
                <option value=''><?=traduz('Selecione')?></option>
                <option value='1' <?php if ($impacto_financeiro == 1) {echo 'selected="selected"';}?>><?=traduz('Sim')?></option>
                <option value='2' <?php if ($impacto_financeiro == 2) {echo 'selected="selected"';}?>><?=traduz('Não')?></option>
            </select>
        <?php } else {
                echo "<input type='hidden' name='impacto_financeiro' id='impacto_financeiro' value='$impacto_financeiro' />";
                echo $ximpacto_financeiro."&nbsp;";
              }
        ?>
    </td>
    <?php if($login_fabrica != 159){ ?>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;</td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;</td>
    <?php } ?>
</tr>
<?php// }?>
<?php
    $tipo_categoria_erro = array(
            traduz("Integração"),
            traduz("Erro em Tela"),
            traduz("Processos"),
            traduz("Parada do Sistema")
        );
?>
<tr class='mostraparaerro'>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;<?=traduz('Categoria')?> </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
        <?php if(empty($hd_chamado)){ ?>
            <select name='tipo_categoria_erro' id='tipo_categoria_erro' style='width: 380px;' class="<?=(in_array('tipo_categoria_erro', $campos_erro)? 'error': '')?>">
                <option value='' selected ><?=traduz('Selecione')?></option>
                <?php
                    foreach ($tipo_categoria_erro as $categoria_erro) {
                        $selected = ($categoria_erro == $xtipo_categoria_erro) ? " selected='selected' " : "";
                        echo "<option value='{$categoria_erro}' {$selected}>{$categoria_erro}</option>";
                    }
                ?>
            </select>
        <?php }else{
                echo "<input type='hidden' name='tipo_categoria_erro' id='tipo_categoria_erro' value='$categoria' />";
                echo $categoria."&nbsp;";
              } ?>
    </td>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;</td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;</td>
</tr>
<!-- HD 218848: Criação do questionário na abertura do Help Desk --><?php
$esconde = '';
$desabilita_campo = '';
if ($mostra_questionario) {

    if (strlen($hd_chamado)) {

        $desabilita_questionario       = "readonly";
        $desabilita_questionario_combo = "disabled";

    }
    if ($tipo == 8 && strlen($posto) > 0) {
        $desabilita_questionario_combo = "disabled";
        $desabilita_campo = "readonly";
        $esconde    = 'style="display: none !important;"';
        $local_menu = "admin_cadastro";
        echo '<input type="hidden" name="local_menu" value="'.$local_menu.'" />';
        $http = "https://posvenda.telecontrol.com.br/assist/admin/posto_cadastro.php?posto=$posto";
    }
?>
    <tr class="escondeparaerro">
        <td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
            <?php if($sistema_lingua == 'ES'){
                echo "<strong>&nbsp;¿Que necessitas hacer?</strong>";
            }else{
                echo "<strong>&nbsp;O que você precisa que seja feito?</strong>";
        } ?>
        </td>
    </tr>
    <tr class="escondeparaerro">
        <td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
            <textarea <?=$desabilita_questionario?> name='necessidade' id='necessidade' class='questionarioCaixa <?=(in_array('necessidade', $campos_erro)? 'error': '')?>' cols=90 rows=5><?=$necessidade;?></textarea>
        </td>
    </tr>
    <tr class="escondeparaerro esconde" <?php echo $esconde;?>>
        <td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
            <?php if($sistema_lingua == 'ES'){
                echo "<strong>&nbsp;¿Cómo funciona hoy?</strong>";
            }else{
                echo "<strong>&nbsp;Como funciona hoje?</strong>";
            } ?>
        </td>
    </tr>
    <tr class="escondeparaerro esconde" <?php echo $esconde;?>>
        <td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
            <textarea <?=$desabilita_questionario?> name='funciona_hoje' id='funciona_hoje' class='questionarioCaixa <?=(in_array('funciona_hoje', $campos_erro)? 'error': '')?>' cols=90 rows=5><?=$funciona_hoje;?></textarea>
        </td>
    </tr>
    <!-- <tr class="escondeparaerro">
        <td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
            <strong>&nbsp;Qual o objetivo desta solicitação? Que problema visa resolver?</strong>
        </td>
    </tr>
    <tr class="escondeparaerro">
        <td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
            <textarea <?=$desabilita_questionario?> name='objetivo' id='objetivo' class='questionarioCaixa' cols=90 rows=5><?=$objetivo; ?></textarea>
        </td>
    </tr> -->

    <!-- <tr class="escondeparaerro">
        <td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
            <strong>&nbsp;Esta rotina terá impacto financeiro para a empresa? Por quê?</strong>
        </td>
    </tr>
    <tr class="escondeparaerro">
        <td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
            <textarea <?=$desabilita_questionario?> name='impacto' id='impacto' class='questionarioCaixa' cols=90 rows=5><?=$impacto; ?></textarea>
        </td>
    </tr> -->
    <tr>
        <td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
            <?php if($sistema_lingua == 'ES'){
                echo "<strong>&nbsp;¿En que parte del sistema necessita cambiar?</strong>";
            }else{
                echo "<strong>&nbsp;Em que local do sistema você precisa de alteração?</strong>";
            } ?>
        </td>
    </tr>
    <tr>
        <td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
            <select <?=$desabilita_questionario_combo?> name="local_menu" id="local_menu" class="questionarioCaixa <?=(in_array('local_menu', $campos_erro)? 'error': '')?>" style='color: #000000;'>
                <option value="">..... <?=traduz('Escolha')?> .....</option>
                <option value="admin_gerencia" <? if ($local_menu == "admin_gerencia") echo "selected" ?>><?=traduz('Administração: Gerência') ?></option>
                <option value="admin_callcenter" <? if ($local_menu == "admin_callcenter") echo "selected" ?>><?=traduz('Administração: CallCenter') ?></option>
                <option value="admin_cadastro" <? if ($local_menu == "admin_cadastro") echo "selected" ?>><?=traduz('Administração: Cadastro') ?></option>
                <option value="admin_infotecnica" <? if ($local_menu == "admin_infotecnica") echo "selected" ?>><?=traduz('Administração: Info Técnica') ?></option>
                <option value="admin_financeiro" <? if ($local_menu == "admin_financeiro") echo "selected" ?>><?=traduz('Administração: Financeiro') ?></option>
                <option value="admin_auditoria" <? if ($local_menu == "admin_auditoria") echo "selected" ?>><?=traduz('Administração: Auditoria') ?></option>
                <option value="posto_os" <? if ($local_menu == "posto_os") echo "selected" ?>><?=traduz('Área do Posto: Ordem de Serviço') ?></option>
                <option value="posto_infotecnica" <? if ($local_menu == "posto_infotecnica") echo "selected" ?>><?=traduz('Área do Posto: Info Técnica') ?></option>
                <option value="posto_pedidos" <? if ($local_menu == "posto_pedidos") echo "selected" ?>><?=traduz('Área do Posto: Pedidos') ?></option>
                <option value="posto_cadastro" <? if ($local_menu == "posto_cadastro") echo "selected" ?>><?=traduz('Área do Posto: Cadastro') ?></option>
                <option value="posto_tabelapreco" <? if ($local_menu == "posto_tabelapreco") echo "selected" ?>><?=traduz('Área do Posto: Tabela Preço') ?></option>
            </select>
        </td>
    </tr>
    <!-- <tr class="escondeparaerro">
        <td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
            <strong>&nbsp;Quanto tempo é possível esperar por esta mudança?</strong>
        </td>
    </tr> -->
    <!-- <tr class="escondeparaerro">
        <td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
            <select <?=$desabilita_questionario_combo?> name="tempo_espera" id="tempo_espera" class='questionarioCaixa' style='color: #000000;'><?php
            if ($tempo_espera == "") $tempo_espera = "7";?>
                <option value="0" <? if ($tempo_espera == "0") echo "selected" ?>>Imediato</option>
                <option value="1" <? if ($tempo_espera == "1") echo "selected" ?>>1 Dia</option>
                <option value="2" <? if ($tempo_espera == "2") echo "selected" ?>>2 Dias</option>
                <option value="3" <? if ($tempo_espera == "3") echo "selected" ?>>3 Dias</option>
                <option value="4" <? if ($tempo_espera == "4") echo "selected" ?>>4 Dias</option>
                <option value="5" <? if ($tempo_espera == "5") echo "selected" ?>>5 Dias</option>
                <option value="6" <? if ($tempo_espera == "6") echo "selected" ?>>6 Dias</option>
                <option value="7" <? if ($tempo_espera == "7") echo "selected" ?>>1 Semana</option>
                <option value="14" <? if ($tempo_espera == "14") echo "selected" ?>>2 Semanas</option>
                <option value="21" <? if ($tempo_espera == "21") echo "selected" ?>>3 Semanas</option>
                <option value="30" <? if ($tempo_espera == "30") echo "selected" ?>>1 Mês</option>
                <option value="60" <? if ($tempo_espera == "60") echo "selected" ?>>2 Meses</option>
                <option value="90" <? if ($tempo_espera == "90") echo "selected" ?>>3 Meses</option>
                <option value="180" <? if ($tempo_espera == "180") echo "selected" ?>>6 Meses</option>
                <option value="360" <? if ($tempo_espera == "360") echo "selected" ?>>1 Ano</option>
            </select>
        </td>
    </tr> -->
    <tr>
        <td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
            <?php if($sistema_lingua == 'ES'){
                echo "<strong>&nbsp;Unbicación HTTP de la pantalla donde solicita cambiar:</strong>";
            }else{
                echo "<strong>&nbsp;Endereço HTTP da tela onde está sendo solicitada a alteração:</strong>";
            } ?>
        </td>
    </tr>
    <tr>
        <td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px;' align='center'>
            <input <?=$desabilita_questionario?> <?=$desabilita_campo?>  size=90 type="text" name='http' id='http' value='<?= $http ?>'
                placeholder='http://posvenda.telecontrol.com.br/...' class='questionarioCaixa  <?=(in_array('http', $campos_erro) ? 'error' : '') ?>' />
        </td>
    </tr><?php

}?>

</table><?php

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
        to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
                tbl_hd_chamado_item.comentario                            ,
                tbl_hd_chamado_item.admin                                 ,
                tbl_admin.nome_completo AS autor
        FROM tbl_hd_chamado_item
        JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
        WHERE hd_chamado = $hd_chamado
        AND interno is not true
        ORDER BY hd_chamado_item";
if(!empty($hd_chamado)){
    $res = pg_query ($con,$sql);
    $tem_registro = pg_num_rows($res);
}
if ($tem_registro > 0) {

    // HD  - MLG - Mostra levantamento de requisitos para aprovação ou aprovado

    $res_req = pg_query($con,
                "SELECT requisito,
                        admin_requisito_aprova,
                        TO_CHAR(data_requisito_aprova, 'DD/MM/YYYY às HH24:MI:SS') AS data_requisito_aprova,
                        interacao
                   FROM tbl_hd_chamado_requisito
                   JOIN tbl_hd_chamado USING(hd_chamado)
                  WHERE hd_chamado = $hd_chamado
                    AND excluido     IS FALSE
                    AND tipo_chamado NOT IN(5,6)
                  ORDER BY hd_chamado_requisito");
    if ($tot_reqs = pg_num_rows($res_req)) {
        $requisitos = pg_fetch_all($res_req);
        echo "<center><div id='divReqAprova'><h3 style='color:yellow'>&nbsp;".traduz('Requisitos do Chamado')."</h3>\n<ol style='background-color:#F2F7FF;font-size: 13px;text-align:left'>\n";
        foreach($requisitos as $requisito) {
            $texto_requisito        = nl2br($requisito['requisito']);
            $req_controle_admin[]   = $requisito['admin_requisito_aprova'];
            $req_controle_data[]    = $requisito['data_requisito_aprova'];
            $interacao  = $requisito['interacao'];
            echo "<li>".$texto_requisito."</li>\n";
            $anexos = getAnexosRequisitos($hd_chamado, $interacao);
            if($anexos !== false){
                echo "<p><a href='$anexos'>".traduz('Clique aqui para ver o anexo')."</a></p>";
            }
        }
        echo "</ol>\n";
        if (count(array_filter($req_controle_admin))==$tot_reqs) {
            $data_aprovadas = $req_controle_data[0];
            $sql_nome_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = " . $req_controle_admin[0];
            $res_nome_admin = pg_query($con, $sql_nome_admin);
            echo "<p style='color:white'>".traduz('Requisitos aprovados em')." <b>" . $data_aprovadas . "</b> por <b>" . pg_fetch_result($res_nome_admin, 0, 0) . '</b>';
        } elseif($login_admin == $admin || eSupervisorHelpDesk($login_admin)) {
            echo "<span style='float:right;margin-top:-10px'><button type='button' id='aprovaRequisitos'>".traduz('Aprovar Requisitos')."</button></span><br />\n";
        }
        echo "</div></center>\n";
    }
    echo "<BR>"; ?>
    <table border='0' cellpadding='0' cellspacing='1' class='relatorio'>
    <tr>
    <th colspan='5' class="relatorio_titulo"><b>
    <?
    if ($sistema_lingua == 'ES')
        echo "Interacciones";
    else
        echo "Interações";
    ?>
    </b></th>
    </tr>

    <tr >

        <th >Nº </th>

        <th>
            <?
            if ($sistema_lingua == 'ES') echo "Fecha";
            else                         echo "Data";
            ?>
        </th>

        <th >
            <?
            if ($sistema_lingua == 'ES')
                echo "Comentario";
            else
                echo "Coment&aacute;rio";
            ?>
        </th>

        <th>Anexo</th>
        <th>Autor</th>

    </tr>
    <?
    for ($i = 0; $i < pg_numrows($res) ; $i++) {

        $x = $i + 1;
        $hd_chamado_item = pg_result($res, $i, 'hd_chamado_item');
        $data_interacao  = pg_result($res, $i, 'data');
        $admin           = pg_result($res, $i, 'admin');
        $autor           = pg_result($res, $i, 'autor');
        $item_comentario = pg_result($res, $i, 'comentario');

        $sql2 = "SELECT fabrica FROM tbl_admin WHERE admin = $admin";
        $res2 = pg_query ($con,$sql2);

        $fabrica_autor = pg_result($res2, 0, 0);
        $cor = '#ffffff';

        if ($i % 2 == 0) $cor = '#F2F7FF';

        echo "<tr  style=' height='25' bgcolor='$cor'>";
            echo "<td nowrap width='20'>$x </td>";
            echo "<td nowrap>$data_interacao </td>";
            $xcomentario = strtoupper($item_comentario);
            if(strpos($xcomentario,"<DIV") > 0 or strpos($xcomentario,"<TR") > 0){
                $item_comentario = strip_tags($item_comentario,'<p><br><a>');
            }
            echo "<td >" . nl2br(str_replace($filtro,"", $item_comentario)) . "</td>";

            echo "<td>";
            $dir = "documentos/hd-$hd_chamado-itens/";

            if ($dh  = glob($dir."$hd_chamado_item-*") and !empty($hd_chamado_item)) {
                foreach($dh as $filename) {
                    $att_icon = (preg_match("/\.(gif|jpg|jpeg|png|tif|tiff|bmp)$/i", $filename)) ?
                                    "'$filename' width='24' height='24' " :
                                    "'imagem/clips.gif'";
                    echo "<!--ARQUIVO-I-->&nbsp;&nbsp;";
                    echo "<a href=$filename target='blank'><img src=$att_icon border='0'></a>";
                    echo "<a href=$filename rel='nozoom' target='blank'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
                }
            }

            echo "</td>";
            echo "<td nowrap >". $autor ."</td>";
        echo "</tr>";

    }


    echo "</table>";

}
$permissao = 'sim';
if (strlen($hd_chamado) > 0 AND $status == 'Resolvido') {

    $sql = "SELECT  TO_CHAR (data,'DD/MM HH24:MI') AS data,
                    CASE WHEN CURRENT_DATE - data::DATE > 14 THEN 'nao' ELSE 'sim' END AS permissao
            FROM tbl_hd_chamado_item
            WHERE hd_chamado = $hd_chamado
            AND interno IS NOT TRUE
            ORDER BY tbl_hd_chamado_item.data DESC
            LIMIT 1";

    $res = pg_query ($con,$sql);

    if (pg_numrows($res) > 0) {
        $data_ultima_interacao = pg_result($res,0,data);
        $permissao             = pg_result($res,0,permissao);
    }

}
if ($hd_chamado and $status == 'Cancelado') {
    $permissao = 'nao';
}
echo "<center>";

if (strlen ($hd_chamado) > 0) {

    echo "<br>";

    if ($status == 'Resolvido' || $status == 'Resuelto') {

        echo "<b><font face='verdana' color='#666666'>";
        if ($sistema_lingua == "ES") echo "Este llamado esta resuelto";
        else                         echo "Este chamado está resolvido.";
        echo "</font></b><br>";

        if ($permissao == 'sim') {

            echo "<b><font face='verdana' color='#6600FF' size='-1'>";

            if ($sistema_lingua == "ES") {
                echo "Si no concordas con la solución, puede reabrirlo digitando una mensaje abajo";
            } else {
                echo "Para novas interações sobre este chamado, digite uma mensagem abaixo.<br> ";
            }

            echo "</font><font face='verdana' color='#00CC00' size='-1'><br>";

            if (strlen($resolvido)== 0) {

                if ($sistema_lingua == "ES") {
                    echo "Si concordas con la solución haga un click no botón RESUELTO";
                } else {
                    echo "Se você concorda com a solução clique no botão RESOLVIDO";
                }

            }

            echo "</font></b><br>";

        }

    } else {

        echo "<b><font face='verdana' color='#666666'>";

        if ($sistema_lingua == 'ES') echo "Digite el texto para continuar el llamado";
        else                         echo "Digite o texto para dar continuidade ao chamado";
        echo "</font></b><br>";

        if ($status == 'Aprovação') {

            echo "<font color='red'>";
            if ($sistema_lingua == 'ES') echo "¡El responsable del 'Help-Desk' debe APROBAR la solicitud [chamado] para que Telecontrol pueda proseguir";
            else                         echo "O responsável pelo Help-Desk na Fábrica precisa APROVAR o chamado para o Telecontrol dar continuidade!";
            echo "</font>";

        }

    }

} else {

    echo "<b><font face='verdana' color='#666666'>";
    if($sistema_lingua == 'ES'){
        echo "Ingresse el texto de su llamada";
    }else{
        echo "Digite o texto do seu chamado";
    }
    echo "</b></font><br>";

}

//$permissao = 'sim';
if ($permissao == 'sim') {

    echo "<table width = '750' align = 'center' cellpadding='2'  style=' font-size: 11px'>";
    echo "<tr>";
    echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>";
    echo "<div style='width:506px' class='".(in_array('comentario', $campos_erro) ? 'error': '')."'> <textarea name='comentario' cols='90' rows='10' class='questionarioCaixa  wrap='VIRTUAL' style='width: 500px;'>".stripcslashes($comentario)."</textarea></div><br>";
    echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";

    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>";

    if ($tipo == 8 && strlen($posto) > 0) {
    echo "<font color='red' size=1>ANEXO DO COMPROVANTE DE INSCRIÇÃO E DE SITUAÇÃO CADASTRAL.</font> <BR />";
    }

    if ($sistema_lingua == 'ES') echo "Archivo";
    else                         echo "Arquivo ";

    echo "<input type='file' name='arquivo[]' size='70' class='Caixa' /><br><br>";
?>
    <div id="novo_arquivo"></div>
    <button type="button" onclick="novo_arquivo('novo_arquivo_l')"><?=traduz('Novo Arquivo')?></button><br><br>

<?
    //echo "<input type='button' name='inserir_arquivo' size='70' value='Novo Arquivo' /><br><br>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

}

if ($status == 'Resolvido' || $status == 'Resuelto' AND strlen($resolvido) == 0) {

    echo "<input type='submit' name='btn_resolvido' value='";
    if ($sistema_lingua == "ES") echo "RESUELTO - ESTOY DE ACUERDO CON LA SOLUCIÓN";
    else                         echo "RESOLVIDO - CONCORDO COM A SOLUÇÃO...";
    echo "' class='btn' ><br>";

}

if ($permissao == 'sim') {

    echo "
        <input name='btn_acao' type='hidden' value='Enviar Chamado' />
        <input type='button' id='btn_enviar' value='";
    if ($sistema_lingua == 'ES') echo "Enviar Solicitud";
    else                         echo "Enviar Chamado";
    echo "'>";
}

echo "</center>";?>
        </td>
    </tr>
</table>
</form>
<?
if ($hd_chamado == '' and $tc_com_hoje > strtotime('12/12/2011 07:00:00') and $tc_com_hoje < strtotime('01/08/2012 17:00:00')) {
    include 'aviso_bloqueio_hd_desenvolvimento.html';
}
include "rodape.php" ?>
