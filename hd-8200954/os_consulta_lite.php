
<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';
include_once 'class/sms/sms.class.php';
include_once "class/aws/anexaS3.class.php";
include_once 'class/communicator.class.php';
include __DIR__ . '/class/ComunicatorMirror.php';


if ($usaNovaTelaConsultaOs) {
	$cond_pre = isset($btn_acao_pre_os) ? "?action=formulario_pre_os":"";
    header("Location: consulta_lite_new.php$cond_pre");
}

$vet_email_consumidor = array(11,14,43,59,66,117,172);
$envia_pesquisa_finaliza_os = array(161);

$fabrica_copia_os_excluida      = in_array($login_fabrica, array(30));

// SMS
if (SMS::getFabricasSms($login_fabrica)) {
	$sms = new SMS();
}


if ($login_fabrica == 175 && $LU_tecnico_posto == true){
    $sql = "SELECT tecnico FROM tbl_tecnico WHERE codigo_externo = '{$login_unico}' AND fabrica IS NULL AND posto = {$login_posto} ";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0){
        $login_tecnico_id = pg_fetch_result($res,0,'tecnico');        
    }
}

if ($usaPreOS == 't' OR $fabrica_pre_os == 't') {
    $fabrica_pre_os = $login_fabrica;
}

if ($login_fabrica == 1) {
    header ("Location: os_consulta_avancada.php");
    exit;
}
if ($login_fabrica == 74) {
        include "classes/FechamentoOS.php";
}

$ip_devel = $_SERVER['REMOTE_ADDR'];

# HD 2489168 (Tectoy) - Para verificar se OS tem NF
# HD 2851310 - adicionado Elgin
if (in_array($login_fabrica, array(6, 156, 171))) {
    include_once 'anexaNF_inc.php';
}
# Fim HD 2489168

if (in_array($login_fabrica, array(15,140))) {
    include "class/log/log.class.php";
    $email_consumidor = new Log();
}

/*
 *  HD 135436(+Mondial) HD 193563 (+Dynacom)
 *  Para adicionar ou excluir uma fábrica ou posto, alterar só essa condição aqui,
 *  na os_fechamento, os_press, admin/os_press e na admin/os_fechamento, sempre nesta função
*/
#HD 311411 - Adicionado Fábrica 6 (TecToy)
function usaDataConserto($posto, $fabrica) {

    if ($posto == '4311' or (( !in_array($fabrica, array(1,11,172)) ) and $posto==6359) or
        in_array($fabrica, array(2,3,5,6,7,11,14,15,20,43,45,35,40,172)) or $fabrica > 50) {

        return true;

    }

    return false;

}

function admin_fechou_com_pagamento($os) {
    global $con, $login_fabrica;

    if (!empty($os)) {
        $sql = "SELECT os FROM tbl_os_status WHERE os = $os AND fabrica_status = $login_fabrica AND observacao ilike 'OS fechada sem pagamento:%'";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            return false;
        } else {
            return true;
        }
    } else {
        return true;
    }

    return true;
}

if (!function_exists('verificaSelect')) {

    function verificaSelect($valor1, $valor2) {
        return ($valor1 == $valor2) ? " selected = 'selected' " : "";
    }
}

if ($_GET['ajax'] == 'busca_laudo_tecnico_os') {
    try {
        $os = $_GET['os'];
        
        if (empty($os)) {
            throw new \Exception('Ordem de Serviço não informada');
        }
        
        $sql = "
            SELECT tco.titulo, tco.observacao, o.sua_os
            FROM tbl_laudo_tecnico_os tco
            INNER JOIN tbl_os o ON o.os = tco.os AND o.fabrica = {$login_fabrica}
            WHERE tco.fabrica = {$login_fabrica}
            AND o.posto = {$login_posto}
            AND tco.os = {$os}
        ";
        $res = pg_query($con, $sql);
        
        if (!pg_num_rows($res)) {
            throw new \Exception('Ordem de Serviço inválida');
        }
        
        $laudo_tecnico = pg_fetch_assoc($res);
		$laudo_tecnico = array_map('utf8_encode',$laudo_tecnico);
        exit(json_encode($laudo_tecnico));
    } catch(\Exception $e) {
        exit(json_encode(array('erro' => utf8_encode($e->getMessage()))));
    }
}

if ($_GET['ajax'] == 'busca_laudo_tecnico') {
    try {
        $os = $_GET['os'];
        
        if (empty($os)) {
            throw new \Exception('Ordem de Serviço não informada');
        }
        
        $sql = "SELECT o.os, o.sua_os, op.produto, op.serie FROM tbl_os o INNER JOIN tbl_os_produto op ON op.os = o.os WHERE o.fabrica = {$login_fabrica} AND o.posto = {$login_posto} AND o.os = {$os}";
        $res = pg_query($con, $sql);
        
        if (!pg_num_rows($res)) {
            throw new \Exception('Ordem de Serviço inválida');
        }
        
        $os = pg_fetch_assoc($res);
        
        if (empty($os['produto']) || empty($os['serie'])) {
            throw new \Exception('Ordem de Serviço não possui produto ou número de série');
        }
        
        $ordem_producao = (int) substr($os["serie"], 0, 6);

        $sql = "
            SELECT xxx.* 
            FROM (
                SELECT DISTINCT ON(xx.ordem_producao) xx.laudo_tecnico, xx.comentario, xx.ordem_producao::float, CASE WHEN xx.proxima_ordem IS NULL THEN float8'+infinity' ELSE xx.proxima_ordem - 1 END AS proxima_ordem
                FROM (
                    SELECT x.laudo_tecnico, x.comentario, x.ordem_producao, lt.ordem_producao::integer AS proxima_ordem FROM(
                        SELECT laudo_tecnico, ordem_producao::integer, comentario
                        FROM tbl_laudo_tecnico
                        WHERE fabrica = {$login_fabrica}
                        AND produto = {$os['produto']}
                        AND afirmativa IS TRUE
                        ORDER BY ordem_producao ASC
                    ) x
                    LEFT JOIN tbl_laudo_tecnico lt ON lt.ordem_producao::integer > x.ordem_producao AND lt.fabrica = {$login_fabrica} AND lt.produto = {$os['produto']} AND afirmativa IS TRUE
                    ORDER BY x.ordem_producao ASC, proxima_ordem ASC
                ) xx
                ORDER BY xx.ordem_producao, xx.proxima_ordem ASC
            ) xxx
            WHERE '{$ordem_producao}'::float BETWEEN xxx.ordem_producao AND xxx.proxima_ordem
        ";
        $res = pg_query($con, $sql);
        
        if (!pg_num_rows($res)) {
            throw new \Exception('Não foi encontrado laudo técnico para o produto e número de série');
        }

        $laudo_tecnico = pg_fetch_assoc($res);

        $laudo_tecnico['comentario'] = utf8_encode($laudo_tecnico['comentario']);
        $laudo_tecnico['sua_os'] = $os['sua_os'];
        
        exit(json_encode($laudo_tecnico));
    } catch(\Exception $e) {
        exit(json_encode(array('erro' => utf8_encode($e->getMessage()))));
    }
}

if($_GET['excluir_pre_os']){

    $hd_chamado = $_GET['hd_chamado'];

    $sql_interacao = "INSERT INTO tbl_hd_chamado_item (hd_chamado, data, comentario, status_item) VALUES ($hd_chamado, current_timestamp, 'Pré-atendimento cancelado pelo Posto', 'Resolvido')";
    $res_interacao = pg_query($con, $sql_interacao);
    $msg_erro = pg_errormessage($con);

    if(strlen($msg_erro) == 0){
        $sql_status = "UPDATE tbl_hd_chamado SET status = 'Resolvido' WHERE hd_chamado = $hd_chamado AND fabrica = $login_fabrica ; UPDATE tbl_hd_chamado_extra SET abre_os = FALSE where hd_chamado = $hd_chamado ; ";
        $res_status = pg_query($con, $sql_status);
        $msg_erro = pg_errormessage($con);
    }

    if(strlen($msg_erro) > 0){
        $retorno['erro'][] = "ok";
    }else{
        $retorno['sucesso'] = "ok";
    }

    echo json_encode($retorno);
    exit;
}

$where_tbl_status_checkpoint = "";
if ($login_fabrica == 30) {
    $where_tbl_status_checkpoint = "AND fabricas isnull OR {$login_fabrica} = any(fabricas)";
}
if (in_array($login_fabrica, [175])) {
    $where_tbl_status_checkpoint = "AND status_checkpoint != 0";
}
// Permite cancelamento de OS, seja fábrica ou posto e fábrica
$fCancelaOS = isFabrica(3, 42);

#HD 234532
$sql_status   = "SELECT status_checkpoint,descricao,cor FROM tbl_status_checkpoint";
$sql_status .= (!empty($where_tbl_status_checkpoint)) ? " WHERE 1=1 {$where_tbl_status_checkpoint}" : "";
$res_status   = pg_query($con, $sql_status);
$total_status = pg_num_rows($res_status);

for ($i = 0; $i < $total_status; $i++) {

    $id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
    $cor_status = pg_fetch_result($res_status,$i,'cor');
    $descricao_status = pg_fetch_result($res_status,$i,'descricao');

    if ($telecontrol_distrib && (!isset($novaTelaOs) || (in_array($login_fabrica, [160]) or $replica_einhell)) && strtoupper($descricao_status) == 'AGUARD. ABASTECIMENTO ESTOQUE') {
        //Para o posto será exibido aguardando peças no lugar
        $cor_status = '#FAFF73';
    }

    #Array utilizado posteriormente para definir as cores dos status
    $array_cor_status[$id_status] = $cor_status;

}
#HD 234532
function exibeImagemStatusCheckpoint($status_checkpoint){

    global $array_cor_status;

    /*
    0 | Aberta Call-Center  (imagens/status_branco)
    1 | Aguardando Analise  (imagens/status_vermelho)
    2 | Aguardando Peças    (imagens/status_amarelo)
    3 | Aguardando Conserto (imagens/status_rosa)
    4 | Aguardando Retirada (imagens/status_azul)
    9 | Finalizada          (imagens/status_cinza)
    */

    if(strlen($status_checkpoint) > 0){
        echo '<span class="status_checkpoint" style="background-color:'.$array_cor_status[$status_checkpoint].'">&nbsp;</span>';
    }else{
        echo '<span class="status_checkpoint_sem">&nbsp;</span>';
    }

}

//30/08/2010 MLG HD 283928  Fábricas que mostram o status de Intervenção e o histórico. Adicionar 43 (Nova Comp.)
$historico_intervencao = (in_array($login_fabrica, array(1,2,3,6,11,14,25,30,35,43,45,50,172)) or $login_fabrica > 84);

// 11,45,15,3,43,66,14 ){

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0) $btn_acao = strtoupper($_GET["btn_acao"]);

if ($login_fabrica == 91 && empty($btn_acao)) {
    $btn_acao = strtoupper($_REQUEST["btn_acao"]);
}

if (strlen($_POST["btn_acao_pre_os"]) > 0) $btn_acao_pre_os = strtoupper($_POST["btn_acao_pre_os"]);
if (strlen($_GET["btn_acao_pre_os"]) > 0) $btn_acao_pre_os = strtoupper($_GET["btn_acao_pre_os"]);

# ---- excluir ---- #
$os = $_GET['excluir'];

if (strlen ($os) > 0) {
    // Busca a os
    $sql = "SELECT  tbl_os.os,
                    tbl_os.os_numero,
                    tbl_os.os_sequencia,                    
                    tbl_tipo_atendimento.codigo as tipo_atendimento, 
                    tbl_produto.linha,
            FROM tbl_os
            JOIN tbl_produto
                    ON tbl_produto.produto = tbl_os.produto
            WHERE sua_os = '$os'";

    $res = pg_query($con,$sql);

    if(pg_num_rows($res) != 1) {
        $msg_erro = "Não foi possí­vel excluir a OS";
    } else {
        $os = pg_fetch_result($res, 0, os);        

        $res = pg_query ($con,"BEGIN TRANSACTION");
        /**
         * Exclui os arquivos em anexo, se tiver
         **/
        include_once 'anexaNF_inc.php';
        if (count($anexos = temNF($os, 'path'))) { //'path' devolve um array com todos os anexos
            foreach ($anexos as $arquivoAnexo) {
                excluirNF($arquivoAnexo);
            }
        }  

        if ($login_fabrica == 50) {//HD 37007 5/9/2008

            $sql = "UPDATE tbl_os SET excluida = 't' WHERE os = $os AND fabrica = $login_fabrica";
            $res = @pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);

            $sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
            $res = @pg_query($con, $sql);
            $msg_erro = pg_errormessage($con);

            #158147 Paulo/Waldir desmarcar se for reincidente
            $sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
            #$res = pg_query($con, $sql);

        } else {

            $sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
            $res = @pg_query($con, $sql);
            $msg_erro = pg_errormessage($con);

        }

        if ($usaMobile) {
            try {
                $cockpit = new \Posvenda\Cockpit($login_fabrica);
                $cockpit->cancelaOsMobile($os, $con);
            } catch(\Exception $e) {
                $msg_erro = $e->getMessage();
            }
        }

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
		} else {
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
    }
}

if($_POST['ajax'] == "cancelar_os"){
    $os = $_POST['os'];
    $acao = $_POST['acao'];
    $motivo = utf8_decode($_POST['motivo']);

    $res = pg_query($con,"BEGIN TRANSACTION");
    if($acao == "liberar" || $acao == "reabrir"){
        $text = "Liberação";
        $sql = "INSERT INTO tbl_os_status (
                    os         ,
                    observacao ,
                    status_os  
                ) VALUES (
                    $os,
                    '$motivo' ,
                    17       
                );";
        $res = pg_query ($con,$sql);

        if ($acao == "reabrir") {
            $sql = "
                UPDATE  tbl_os SET
                    finalizada = null,
                    data_fechamento = null
                WHERE   os = $os
            ";
        }else{
            $sql = "
                UPDATE  tbl_os
                SET     excluida = FALSE
                WHERE   os = $os
            ";
        }
        $res = pg_query($con,$sql);
    }else{
        if($login_fabrica == 30){
            $status_os = 15;
            $sql_excluida = " excluida = TRUE ";
        }

        $sql = "INSERT INTO tbl_os_status (
                    os         ,
                    observacao ,
                    status_os  
                ) VALUES (
                    $os,
                    '$motivo' ,
                    $status_os  
                );";
        $res = pg_query ($con,$sql);
 
        $text = "Cancelamento";
        $sql = "
            UPDATE  tbl_os
            SET     $sql_excluida
            $status_checkpoint
            WHERE   os = $os
        ";
        $res = pg_query($con,$sql);
    }

    if(pg_last_error($con)){
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
    }else{
        if($motivo != ""){
            $sql = "INSERT INTO tbl_os_interacao
                    (programa,fabrica, os, comentario, interno, exigir_resposta)
                    VALUES
                    ('$programa_insert',$login_fabrica, $os, '$text de OS. Motivo: $motivo', TRUE, FALSE)";
            $res = pg_query($con,$sql);
        }

        if (in_array($login_fabrica, array(30))) {
            $sql = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = {$os};";

            $res = pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                $hd_chamado_deletar = pg_fetch_result($res, 0, "hd_chamado");
                $sql = "UPDATE tbl_hd_chamado_extra SET os = null WHERE hd_chamado = {$hd_chamado_deletar}";
                $res = pg_query($con,$sql);
                if(pg_last_error($con)){
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                    echo "erro";
                    exit;
                }else{
                    $sql = "INSERT INTO tbl_hd_chamado_item(
                                hd_chamado   ,
                                data         ,
                                comentario   ,
                                interno      ,
                                status_item
                            )VALUES(
                                $hd_chamado_deletar ,
                                current_timestamp ,
                                'Foi cancelado a OS {$os}, portanto desvinculado deste atendimento.',
                                't'  ,
                                'Aberto'
                            )";
                    $res = pg_query($con,$sql);
                    if(pg_last_error($con)){
                        $res = pg_query($con,"ROLLBACK TRANSACTION");
                        echo "erro";
                        exit;
                    }
                }
            }
        }

        $res = pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array("result"=>"ok"));
    }
    exit;
}

 //hd 88308 waldir

# ---- fechar ---- #
$os = $_GET['consertado'];

if (strlen ($os) > 0) {
    if (in_array($login_fabrica, array(11,172))) {
        //Alterando o valor de login_fabrica para a fabrica da OS em questão
        $sql = "SELECT fabrica
                FROM tbl_os
                WHERE os = {$os}";
        $res = pg_query($con, $sql);

        $login_fabrica = pg_fetch_result($res, 0, 'fabrica');
    }

    if($login_fabrica == 59){
        $sql_nome_tecnico = "SELECT tecnico FROM tbl_os WHERE os = $os";
        $res_nome_tecnico = pg_query($con, $sql_nome_tecnico);

        $nome_tecnico = pg_fetch_result($res_nome_tecnico, 0, 'tecnico');

        if(strlen($nome_tecnico) == 0){
            echo traduz("nome.do.tecnico");
            exit;
        }
    }

    $msg_erro = "";

    if (in_array($login_fabrica, array(11,172))) {

        $sqlD = "SELECT os
                FROM tbl_os
                WHERE os = $os
                AND fabrica  = $login_fabrica
                AND defeito_constatado IS NOT NULL
                AND solucao_os IS NOT NULL";

        $resD     = @pg_query($con, $sqlD);
        $msg_erro = pg_errormessage($con);

        if (pg_num_rows($resD) == 0) {
            $msg_erro = traduz("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma);
        }

    }

    if($login_fabrica == 74){
        $os_vinculada = false;
        try{

            $fechamentoOS = new FechamentoOS();
            $fechamentoOS->validaEConsertaOS($os);

            $os_vinculada = true;
        }catch(Exception $ex){
            $msg_erro = $ex->getMessage();

        }

    }

    if (in_array($login_fabrica, array(169,170))){
        $sql_constatado = "SELECT defeito_constatado AS defeito_constatado_os
                            FROM tbl_os_defeito_reclamado_constatado
                            WHERE os = {$os}
                            AND fabrica = {$login_fabrica}";
        $res_constatado = pg_query($con, $sql_constatado);

        if (pg_num_rows($res_constatado) == 0){
            $msg_erro =  traduz("os.sem.defeito.constatado.nao.pode.ser.consertada");
        }
    }

    if(in_array($login_fabrica, array(177))){
        $sql_constatado = "SELECT defeito_constatado FROM tbl_os where fabrica = {$login_fabrica} AND os = {$os}";
        $res_constatado = pg_query($con, $sql_constatado);
        $res_constatado = pg_fetch_array($res_constatado);

        if ($res_constatado['defeito_constatado'] == ""){
            $msg_erro =  traduz("os.sem.defeito.constatado.nao.pode.ser.consertada");
        }
    }

    if (strlen($msg_erro) == 0 && !$os_vinculada) {

        $res_consertado = pg_query ($con,"BEGIN TRANSACTION");
        $dataFechameto  = "";
        if ($login_fabrica == 35) {
            $dataFechameto = ", data_fechamento = '".date('Y-m-d')."'";
        }

        $sql = "
            UPDATE  tbl_os
            SET     data_conserto = CURRENT_TIMESTAMP
            $dataFechameto
            WHERE   os = $os";
        $res = @pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);

        if ($login_fabrica == 178){
            $sql = "
                SELECT os 
                FROM tbl_os 
                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
                WHERE tbl_os.os = {$os}
                AND tbl_tipo_atendimento.km_google IS TRUE
                AND tbl_tipo_atendimento.descricao ILIKE '%Garantia Domic%'";
            $res = pg_query($con, $sql);
            
            if (pg_num_rows($res) > 0){
                $sql = "
                    UPDATE tbl_os
                    SET status_checkpoint = 30
                    WHERE os = {$os}
                    AND fabrica = {$login_fabrica}
                    AND posto = {$login_posto}";
                $res = pg_query($con,$sql);
            }
        }

    }

    #hd-3730629
    if ($login_fabrica == 35) {
        $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
        $res = pg_query($con, $sql);
        $msg_erro = pg_last_error($con);


        $sql_auditoria = "SELECT * from tbl_auditoria_os where  os = $os and auditoria_status <> 2 and liberada is null";
        $res_auditoria = pg_query($con, $sql_auditoria);
        if(pg_num_rows($res_auditoria)>0){
            $observacao = pg_fetch_result($res_auditoria, 0, observacao);
            $msg_erro = traduz("o.s.em")." $observacao";
        }
    }

    if ($login_fabrica == 165) {

    	$sql = "
    		UPDATE tbl_os
    		SET status_checkpoint = (SELECT fn_os_status_checkpoint_os({$os}))
    		WHERE os = {$os}
    		AND fabrica = {$login_fabrica};
    	";

        $res = pg_query($con,$sql);

    }

    if (strlen($msg_erro) == 0) {

        if (in_array($login_fabrica, [104,123])) {
            $sql_sms = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
            $qry_sms = pg_query($con, $sql_sms);
            $campos_adicionais = array();
            $insert_campo_extra = false;

            if (pg_num_rows($qry_sms) == 0) {
                $insert_campo_extra = true;
            } else {
                $campos_adicionais = json_decode(pg_fetch_result($qry_sms, 0, 'campos_adicionais'), true);
            }

            if (!array_key_exists("enviou_msg_consertado", $campos_adicionais) or $campos_adicionais["enviou_msg_consertado"] <> "t") {
                $helper = new \Posvenda\Helpers\Os();

                $sql_contatos_consumidor = "
                    SELECT consumidor_email,
                        consumidor_celular,
                        referencia,
                        descricao,
                        tbl_posto.nome
                    FROM tbl_os
                    JOIN tbl_produto USING(produto)
                    JOIN tbl_posto USING(posto)
                    WHERE os = $os";
                $qry_contatos_consumidor = pg_query($con, $sql_contatos_consumidor);

                $consumidor_email = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_email');
                $consumidor_celular = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_celular');
                $produto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia') . ' - ' . pg_fetch_result($qry_contatos_consumidor, 0, 'descricao');
                $xref = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia');
                $posto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'nome');

                if ($login_fabrica == 104) {
                    $msg_conserto_os = traduz("produto.vonder.os")." $os. ".traduz("informamos.que.seu.produto")."  $produto_os  ".traduz("que.esta.em.nosso.posto"). " $posto_os ".traduz("ja.esta.consertado.solicitamos.sua.presenca.para.retirada.com.brevidade");

                    if (!empty($consumidor_email)) {
                        $helper->comunicaConsumidor($consumidor_email, $msg_conserto_os);
                    }
                } else {
                    $msg_conserto_os = traduz("O reparo do seu equipamento $xref foi concluído e encontra-se disponível para ser retirado. OS $os. Obrigada por escolher a nossa marca.");
                }

                if (!empty($consumidor_celular)) {
                    $helper->comunicaConsumidor($consumidor_celular, $msg_conserto_os, $login_fabrica, $os);
                }

                $campos_adicionais["enviou_msg_consertado"] = "t";
                $json_campos_adicionais = json_encode($campos_adicionais);

                if (true === $insert_campo_extra) {
                    $sql_msg_consertado = "
                        INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais)
                            VALUES ({$os}, {$login_fabrica}, '{$json_campos_adicionais}')";
                } else {
                    $sql_msg_consertado = "
                        UPDATE tbl_os_campo_extra SET
                            campos_adicionais = '{$json_campos_adicionais}'
                        WHERE os = $os";
                }

                $qry_msg_consertado = pg_query($con, $sql_msg_consertado);
            }
        }

        if (in_array($login_fabrica, $vet_email_consumidor) && $img_msg_erro=='')  {
            $novo_status_os = "CONSERTADO";
            include_once "os_email_consumidor.php";
        }

        if(in_array($login_fabrica, array(3))){
                $sql = "SELECT
                            tbl_os.data_conserto::date - tbl_os.data_abertura AS dias,
                            tbl_os.data_conserto        ,
                            tbl_os.consumidor_email     ,
                            tbl_os.sua_os               ,
                            tbl_os.consumidor_nome      ,
                            tbl_posto.nome AS nome_posto,
                            tbl_posto.endereco          ,
                            tbl_posto.numero            ,
                            tbl_posto.fone                          ,
                            tbl_marca.marca as id_marca             ,
                            tbl_marca.nome as marca
                            FROM tbl_os
                                JOIN tbl_produto USING(produto)
                            JOIN tbl_marca  ON tbl_produto.marca = tbl_marca.marca
                            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                        WHERE tbl_os.os = $os";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){
                    $data_conserto  = pg_fetch_result($res, 0, 'data_conserto');
                    $nome           = pg_fetch_result($res, 0, 'consumidor_nome');
                    $nome_posto     = pg_fetch_result($res, 0, 'nome_posto');
                    $endereco       = pg_fetch_result($res, 0, 'endereco');
                    $numero         = pg_fetch_result($res, 0, 'numero');
                    $fone           = pg_fetch_result($res, 0, 'fone');
                    $dias           = pg_fetch_result($res, 0, 'dias');
                    $email          = trim(pg_fetch_result($res, 0, 'consumidor_email'));
                    $id_marca       = pg_fetch_result($res, 0, 'id_marca');
                    $marca          = trim(pg_fetch_result($res, 0, 'marca'));
                    $sua_os         = strlen(pg_fetch_result($res, 0, 'sua_os')) == 0 ? $os : pg_fetch_result($res, 0, 'sua_os');

                    if(filter_var($email, FILTER_VALIDATE_EMAIL) AND $dias <= 29 AND strlen($data_conserto) > 4){
                        include_once 'class/email/mailer/class.phpmailer.php';
                        $mailer = new PHPMailer();

                        $mensagem = "Prezado(a) {$nome},<br/>Seu atendimento n° {$sua_os} foi concluído e o produto se encontra disponível para retirada o mais breve possí­vel.<br/><br/>Posto: {$nome_posto}<br/>Endereço: {$endereco}, {$numero}<br/>Tel: {$fone}<br/><br/>Este e-mail é gerado automaticamente.<br/><br/>SAC Britânia<br/>0800 4176 44<br/>sac@britania.com.br<br/><br/>SAC Philco<br/>0800 6458 300<br/>sac@philco.com.br<br/><br/>De segunda a sexta das 08:00 às 18:00";

                        $mailer->IsSMTP();
                        $mailer->IsHTML(true);
                        $mailer->AddReplyTo("sac@britania.com.br","SAC Britânia - Telecontrol Pós Venda");
                        $mailer->AddAddress($email);
                        if($id_marca == 110){
                            $mailer->AddAddress("produtoconsertado@philco.com.br");
                        }else{
                            $mailer->AddAddress("produtoconsertado@britania.com.br");
                        }
                        $mailer->Subject = "Atendimento $marca nº {$sua_os} - Concluido";
                        $mailer->Body = $mensagem;
                        $mailer->Send();
                        /*$headers  = "MIME-Version: 1.0 \r\n";
                        $headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
                        $headers .= "From: helpdesk@telecontrol.com.br \r\n";

                        $assunto =  "Atendimento $marca nº {$sua_os} - Concluido";*/

               # mail($email, utf8_encode($assunto), utf8_encode($mensagem), $headers);

                }
            }
        }

    }
    if(empty($msg_erro)){

        if($login_fabrica == 140){

            $sql_email = "  SELECT
                                tbl_os.consumidor_nome,
                                tbl_os.consumidor_email,
                                tbl_produto.referencia,
                                tbl_produto.descricao
                            FROM tbl_os
                            JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                            WHERE tbl_os.os = {$os}
                            AND tbl_os.fabrica = {$login_fabrica}";
            $res_email = pg_query($con, $sql_email);

            $nome               = pg_fetch_result($res_email, 0, 'consumidor_nome');
            $consumidor_email   = pg_fetch_result($res_email, 0, 'consumidor_email');
            $referencia         = pg_fetch_result($res_email, 0, 'referencia');
            $descricao          = pg_fetch_result($res_email, 0, 'descricao');

            if(!empty($consumidor_email)){

                $ip_devel = $_SERVER['REMOTE_ADDR'];
                $consumidor_email = ($ip_devel == "179.233.213.77") ? "guilherme.silva@telecontrol.com.br" : $consumidor_email;

                $email_consumidor->adicionaLog(array("titulo" => "Produto Consertado | Lavor - OS: ".$os));

                $mensagem_email = "
                    O produto {$referencia} - {$descricao} pode ser retirado. Favor apresentar a Ordem de Serviço nº {$os} para a retirada.
                    <br /> <br />
                    Data: ".date("d/m/Y")."
                    <br /> <br />
                    Serviço Lavor de Atendimento
                ";

                $email_consumidor->adicionaLog($mensagem_email);

                $email_consumidor->adicionaTituloEmail("Conserto do Produto na OS Lavor - ".$os);
                $email_consumidor->adicionaEmail($consumidor_email);
                $email_consumidor->enviaEmails();
                $email_consumidor->limpaDados();

            }

        }

        if ($login_fabrica == 141) {

            $sqlUltimaOs = "SELECT tbl_os.os,
                                   tbl_hd_chamado_extra.email,
                                   tbl_os.os_numero
                            FROM tbl_os
                            JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os.os_numero
                            JOIN tbl_hd_chamado_extra USING(os)
                            WHERE 
                            (
                                SELECT os_numero FROM tbl_os
                                WHERE os = {$os}
                                LIMIT 1
                            ) = tbl_os.os_numero
                            ORDER BY tbl_os.os_sequencia DESC
                            LIMIT 1";
            $resUltimaOs = pg_query($con, $sqlUltimaOs);

            $sua_os_pesquisa   = pg_fetch_result($resUltimaOs, 0, 'os_numero');
            $ultimaOsRevenda   = pg_fetch_result($resUltimaOs, 0, 'os');

            if (pg_num_rows($resUltimaOs) > 0 && $ultimaOsRevenda == $os) {

                $email_atendimento = pg_fetch_result($resUltimaOs, 0, 'email');
                
                $assunto = "Serviço de Atendimento UNICOBA";
                $mensagem = "Ordem de serviço {$sua_os_pesquisa} de revenda consertada de revenda consertada e aguardando expedição.";

                if(strlen(trim($email_atendimento))>0){
                    $mailTc = new TcComm('smtp@posvenda');

                    $mailTc->sendMail(
                        $email_atendimento,
                        $assunto,
                        $mensagem,
                        'noreply@telecontrol.com.br'
                    );
                }

            }

        }

        if (in_array($login_fabrica, array(144))) {

            $sql = "SELECT tbl_os.consumidor_email, tbl_produto.descricao, tbl_os.nota_fiscal
                    FROM tbl_os
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.os = {$os}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $consumidor_email  = pg_fetch_result($res, 0, "consumidor_email");
                $produto_descricao = pg_fetch_result($res, 0, "descricao");
                $nota_fiscal       = pg_fetch_result($res, 0, "nota_fiscal");

                if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {
                    $header  = "MIME-Version: 1.0 \r\n";
                    $header .= "Content-type: text/html; charset=iso-8859-1 \r\n";
                    $header .= "To: {$consumidor_email} \r\n";
                    $header .= "From: naoresponder@telecontrol.com.br\r\n";

                    $conteudo = "O produto {$produto_descricao} da Ordem de Serviço {$os} foi consertado.<br />
                                Para mais informações entre em contato com a assistência.";

                    $nome_fabrica = strtoupper($login_fabrica_nome);

                    mail($consumidor_email, "{$nome_fabrica} - Produto consertado", $conteudo, $header);
                }
            }
        }

        if (in_array($login_fabrica, array(151,169,170))) {

            if(in_array($login_fabrica, array(169,170))){
                $join_at = " JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
                             AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";

                $cond_at = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
                             AND tbl_tipo_atendimento.km_google IS NOT TRUE ";
            }

            $sqlConRev = "
                SELECT  tbl_os.consumidor_revenda
                FROM tbl_os
                $join_at
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.os = {$os}
                $cond_at
            ";
            $resConRev = pg_query($con, $sqlConRev);

            if(pg_num_rows($resConRev) > 0){
                $os_consumidor_revenda = strtoupper(pg_fetch_result($resConRev, 0, "consumidor_revenda"));
            }
        }

        if(in_array($login_fabrica,array(101,160)) || (in_array($login_fabrica, array(169,170)) && $os_consumidor_revenda == "C") ||  (in_array($login_fabrica,array(80)) and !in_array($login_posto, array(40222, 368942))) ) {

            $sql_celular = "SELECT consumidor_celular, sua_os, referencia, descricao, nome, os_troca
                FROM tbl_os
                JOIN tbl_produto USING(produto)
                JOIN tbl_posto USING(posto)
                LEFT JOIN tbl_os_troca USING(os)
                WHERE os = $os";

            $res_celular = pg_query($con, $sql_celular);
            $envia_sms = false;

            if (pg_num_rows($res_celular) > 0) {

                $consumidor_celular = pg_fetch_result($res_celular, 0, 'consumidor_celular');
                $sms_os             = pg_fetch_result($res_celular, 0, 'sua_os');
                $sms_produto        = pg_fetch_result($res_celular, 0, 'referencia') . ' - ' . pg_fetch_result($res_celular, 0, 'descricao');
                $sms_posto          = pg_fetch_result($res_celular, 0, 'nome');
                $sms_os_troca       = pg_fetch_result($res_celular, 0, 'os_troca');
                if (!empty($consumidor_celular)) {
                    $envia_sms = true;
                }

                $qry_enviou_sms = pg_query($con, "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os");
                if (pg_num_rows($qry_enviou_sms) > 0) {
                    $arr_campos_adicionais = json_decode(pg_fetch_result($qry_enviou_sms, 0, 'campos_adicionais'), true);
                    if (!empty($arr_campos_adicionais) and array_key_exists('enviou_sms', $arr_campos_adicionais)) {
                        if ($arr_campos_adicionais['enviou_sms'] == 't') {
                            $envia_sms = false;
                        }
                    }
                }

                if (in_array($login_fabrica, array(169,170)) AND strlen($sms_os_troca) > 0){
                    $envia_sms = false;
                }

                if (true === $envia_sms) {

                    if($login_fabrica == 101){

                        $sms_msg = traduz("conserto.de.produto.delonghi.kenwood.os")." {$sms_os}. ".traduz("informamos.que.seu.produto")." {$sms_produto} ".traduz("que.esta.no.posto.autorizado")." {$sms_posto}, ".traduz("ja.esta.consertado.por.favor.solicitamos.comparecer.ao.posto.para.retirada.atenciosamente.delonghi.kenwood");

                    }else if ($login_fabrica == 151) {
                                $sms_msg = "MONDIAL - OS {$sms_os}. ".traduz("informamos.que.o/a")." {$sms_produto} " . traduz("que.esta.no.posto.autorizado.esta.consertado");
                    }else{

                        $msg = traduz("informamos.que.seu.produto") . $sms_produto . " " . traduz("que.esta.no.posto.autorizado")." ". $sms_posto . ', ' . traduz("ja.esta.consertado.solicitamos.sua.presenca.para.retirada.com.brevidade");
                        $nome_fabrica = ($login_fabrica == 80) ? "Amvox" : $login_fabrica_nome;
                        $sms_msg = utf8_encode("Produto {$nome_fabrica} - OS " . $sms_os . ". " . $msg);

                    }

                    if ($sms->enviarMensagem($consumidor_celular, $sms_os, '', $sms_msg)) {
                        $sqlCamposAdicionais = "
                            SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os}
                        ";
                        $resCamposAdicionais = pg_query($con, $sqlCamposAdicionais);

                        if (!pg_num_rows($resCamposAdicionais)) {
                            $qry_campos_adicionais = "
                                INSERT INTO tbl_os_campo_extra
                                    (os, fabrica, campos_adicionais)
                                VALUES
                                    ($os, $login_fabrica, '{\"enviou_sms\": \"t\"}')
                            ";
                        } else {
                            $campos_adicionais = pg_fetch_result($resCamposAdicionais, 0, "campos_adicionais");
                            $campos_adicionais = json_decode($campos_adicionais, true);
                            $campos_adicionais["enviou_sms"] = "t";
                            $campos_adicionais = json_encode($campos_adicionais);

                            $qry_campos_adicionais = "
                                UPDATE tbl_os_campo_extra SET
                                    campos_adicionais = '{$campos_adicionais}'
                                WHERE os = {$os}
                                AND fabrica = {$login_fabrica}
                            ";
                        }
                        $qry_campos_adicionais = pg_query($con, $qry_campos_adicionais);
                    }
                }
            }

        }

        /* Não envia sms caso o posto for Aulik */
        if( in_array($login_fabrica, array(11,172)) && strlen($msg_erro) == 0 && ($login_posto != 14301 || $login_posto == 20321)) {

            $sql_celular = "SELECT consumidor_celular, sua_os FROM tbl_os WHERE os = $os";
            $res_celular = pg_query($con, $sql_celular);

            if(pg_num_rows($res_celular) > 0){

                $consumidor_celular     = pg_fetch_result($res_celular, 0, 'consumidor_celular');
                $consumidor_fone        = pg_fetch_result($res_celular, 0, 'consumidor_fone');
                $sua_os                 = pg_fetch_result($res_celular, 0, 'sua_os');

                $sua_os = (strlen($sua_os) > 0) ? $sua_os : $os;

                $destinatario = (strlen($consumidor_celular) > 0) ? $consumidor_celular : $consumidor_fone;

                if(strlen($destinatario) > 0){
                    if($sms->obterSaldo() <= 500) {
                        $sms->gravarSMSPendente($os);
                    }else{
                        $enviar = $sms->enviarMensagem($destinatario, $os, date('d/m/Y'));

                        if ($enviar == false and $sms->validaDestinatario($consumidor_celular)) {
                            $sms->gravarSMSPendente($login_fabrica, $os);
                        }

                    }

                }

            }

        } /* Fim - SMS */

    }

    if ($login_fabrica == 163 AND empty($msg_erro)) {
        //validação para verificar se a OS não é fora de garantia
        //vefifica se o Defeito constatado da OS é Obrigatorio lançar Peça.
        //ou fazer com array no resultado

        $sql_tipo_os = "SELECT  fora_garantia ,
                                tbl_defeito_constatado.lancar_peca,
                                ( SELECT COUNT( tbl_os_item.os_produto )
                                                    FROM tbl_os_item
                                                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                                    WHERE tbl_os_produto.os = tbl_os.os

                                                    GROUP BY tbl_os_item.os_produto
                                                ) as qtde_os_item
                            FROM tbl_os
                                JOIN tbl_tipo_atendimento USING(tipo_atendimento)

                                JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                    AND tbl_os_produto.os = {$os}
                                JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                                    AND tbl_produto.fabrica_i = {$login_fabrica}
                                JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
                                    AND tbl_familia.fabrica = {$login_fabrica}
                                JOIN tbl_diagnostico ON tbl_diagnostico.familia = tbl_familia.familia
                                    AND tbl_diagnostico.fabrica = {$login_fabrica}
                                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
                                    AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                            WHERE tbl_os.os = {$os};";
        $res_tipo_os = pg_query($con,$sql_tipo_os);

        if (pg_num_rows($res_tipo_os) > 0) {
            $fora_garantia = pg_fetch_result($res_tipo_os, 0, fora_garantia);
            $qtde_os_item = pg_fetch_result($res_tipo_os, 0, qtde_os_item);
            $lanca_peca = pg_fetch_result($res_tipo_os, 0, lancar_peca);

            if ($fora_garantia == 'f' AND $qtde_os_item < 1 AND $lanca_peca == 'f') {
                // validação posto não pode ser interno e nem revenda para a OS cair em auditoria
                $sql_posto_tipo = "SELECT tipo_revenda,posto_interno FROM tbl_posto_fabrica
                                        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                                        WHERE posto = $login_posto
                                        AND tbl_posto_fabrica.fabrica = $login_fabrica";
                $res_posto_tipo = pg_query($con, $sql_posto_tipo);

                if (pg_num_rows($res_posto_tipo) > 0) {

                    $posto_tipo_interno = pg_fetch_result($res_posto_tipo, 0, posto_interno);
                    $posto_tipo_revenda = pg_fetch_result($res_posto_tipo, 0, tipo_revenda);

                    if ($posto_tipo_interno !== "t" AND $posto_tipo_revenda !== "t") {
                        $auditoria_status = 6;

                        $sql_ac = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES ({$os}, $auditoria_status, 'Auditoria OS Consertada', true)";
                        $res_ac = pg_query($con, $sql_ac);

                        if (strlen(pg_last_error()) > 0) {
                            $msg_erro .= traduz("erro.ao.finalizar.a.os")." ".$os.".";
                        }
                    }
                } else {
                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro .= traduz("erro.ao.finalizar.a.os")." ".$os.".";
                    }
                }
            }
        } else {
            if (strlen(pg_last_error()) > 0) {
                $msg_erro .= traduz("erro.ao.finalizar.a.os")." ".$os.".";
            }
        }
    }

    if ( empty($msg_erro) AND in_array($login_fabrica, $envia_pesquisa_finaliza_os) ) {
        $sql_pesquisa = "SELECT pesquisa , categoria, texto_ajuda
                            FROM tbl_pesquisa
                            WHERE fabrica = {$login_fabrica}
                                AND ativo IS TRUE
                                AND categoria in ('ordem_de_servico_email')
                                AND ativo IS TRUE";
        $res_pesquisa = pg_query($con, $sql_pesquisa);

        if (pg_num_rows($res_pesquisa) > 0) {
            $texto_ajuda = pg_fetch_result($res_pesquisa, 0, texto_ajuda);

            $sql_envia = "SELECT  tbl_os.consumidor_email,
                            tbl_os.consumidor_nome,
                            tbl_produto.descricao,
                            tbl_produto.referencia
                        FROM tbl_os
                        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                        AND tbl_produto.fabrica_i = $login_fabrica
                        WHERE os = $os";
            $res_envia = pg_query($con,$sql_envia);

            //echo nl2br($sql_envia);

            if (pg_num_rows($res_envia) > 0) {
                $email_envia = pg_fetch_result($res_envia,0,'consumidor_email');
                $produto_referencia_envia = pg_fetch_result($res_envia,0,'referencia');
                $produto_nome_envia = pg_fetch_result($res_envia,0,'descricao');
                $consumidor_nome_envia = pg_fetch_result($res_envia,0,'consumidor_nome');
                //$link_temp_envia = explode("admin/",$HTTP_REFERER);
                $link_temp = explode("os_",$HTTP_REFERER);

                //if ($login_fabrica == 161) {
                $from_fabrica           = "no_reply@telecontrol.com.br";
                $from_fabrica_descricao = "Pós-Venda Cristófoli";
                $link_pesquisa = $link_temp[0]."externos/cristofoli/callcenter_pesquisa_satisfacao2.php?os=$os";
                $assunto  = "Pesquisa de Satisfação - Cristófoli";
                //}


                if(strlen($email_envia) > 0){
                    $valida_email = filter_var($email_envia,FILTER_VALIDATE_EMAIL);

                    if($valida_email !== false){

                        $mensagem = "Produto: $produto_referencia_envia - $produto_nome_envia <br>";
                        $mensagem .= "Ordem de Serviço: $os, <br>";
                        $mensagem .= "Prezado(a) $consumidor_nome_envia, <br>";
                        //$mensagem .= "Sua opinião é muito importante para melhorarmos nossos serviços<br>";
                        //$mensagem .= "Por favor, faça uma avaliação sobre nossos produtos e atendimento através do link abaixo: <br />";
                        $mensagem .= nl2br($texto_ajuda) ."<br>";
                        $mensagem .= "Pesquisa de Satisfação: <a href='$link_pesquisa' target='_blank'>Acesso Aqui</a> <br><br>Att <br>Equipe ".$login_fabrica_nome;

                        $headers  = "MIME-Version: 1.0 \r\n";
                        $headers .= "Content-type: text/html \r\n";
                        $headers .= "From: $from_fabrica_descricao <no_reply@telecontrol.com.br> \r\n";

                        if(!mail($consumidor_nome_envia .'<'.$email_envia.'>', $assunto, utf8_encode($mensagem), $headers)){
                            $msg_erro = traduz("erro.ao.enviar.email.de.pesquisa.satisfacao");
                        }
                    }
                }
            }
        }
    }

    if(strlen(trim($msg_erro))==0){

        if($login_fabrica == 3){
            $sqlBuscaOs25Dias = "SELECT os, consumidor_celular, sua_os, tbl_marca.nome as nome_marca
                                FROM tbl_os
                                INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
                                    INNER JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = $login_fabrica
                                WHERE tbl_os.fabrica = $login_fabrica
                                AND (CURRENT_DATE - data_abertura) > 25
                                AND (CURRENT_DATE - data_abertura) < 31
                                AND tbl_os.os = $os
								AND consumidor_celular notnull
                                AND data_conserto is not null ";


            $resBuscaOs25Dias = pg_query($con, $sqlBuscaOs25Dias);

            if(pg_num_rows($resBuscaOs25Dias)>0){

                $consumidor_celular = pg_fetch_result($resBuscaOs25Dias, 0, consumidor_celular);
                $sua_os             = pg_fetch_result($resBuscaOs25Dias, 0, sua_os);
                $os                 = pg_fetch_result($resBuscaOs25Dias, 0, os);
                $nome_marca         = pg_fetch_result($resBuscaOs25Dias, 0, nome_marca);

                $msg_sms = "Consumidor, seu produto OS $sua_os foi consertado e encontra-se disponí­vel para retirada no posto autorizado ou utilização. Atendimento $nome_marca";

                $sms = new SMS();

                $enviar = $sms->enviarMensagem($consumidor_celular,
                    $os,
                    ' ',
                    $msg_sms);

                if($enviar == false){
                    $sms->gravarSMSPendente($os);
                }
            }
        }

        if($login_fabrica == 35) {

            $sql = "SELECT consumidor_celular FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND consumidor_celular is not null ";
            $res = pg_query($con, $sql);
            
            if(pg_num_rows($res)>0){
                $consumidor_celular = pg_fetch_result($res, 0, 'consumidor_celular');

                $msg_sms = "Olá! Seu produto Cadence / Oster já está disponível para retirada na Assistência Técnica. Dúvidas: 0800 644 644 2.";
                $sms = new SMS(); 
                $enviar = $sms->enviarMensagem($consumidor_celular, $os,'', $msg_sms);
                
                if($enviar == false){
                    $sms->gravarSMSPendente($login_fabrica,$os);
                }
            }
		}
	}

	if (strlen($msg_erro) == 0) {
		$res_consertado = pg_query ($con,"COMMIT TRANSACTION");

		if (in_array($login_fabrica, [123,160]) or $replica_einhell) {
			if (data_corte_termo($os)) {
				echo "ok|ok|$os";        
			} else {
				echo "ok|ok";    
			}
		} else {
			echo "ok|ok";
		}
   } else {
	   $res_consertado = pg_query ($con,"ROLLBACK TRANSACTION");
        echo "erro|$msg_erro";
    }

    exit;

}

# ---- fechar ---- #

$os = $_GET['fechar'];


if (strlen ($os) > 0) {
    //  include "ajax_cabecalho.php";

    if (in_array($login_fabrica, [123,160])) {

        if (data_corte_termo($os)) {
            $tem_termo = false;
            unset($anexou_termo);

            $sql_termo = "SELECT obs FROM tbl_tdocs WHERE referencia_id = $os AND fabrica = $login_fabrica AND situacao = 'ativo'";
            $res_termo = pg_query($con, $sql_termo);
            if (pg_num_rows($res_termo) > 0) {
                for ($t=0; $t < pg_num_rows($res_termo); $t++) { 
                    $anexou_termo = pg_fetch_result($res_termo, $t, 'obs');
                    $anexou_termo = json_decode($anexou_termo, true);
                    if ($anexou_termo[0]['termo_devolucao'] == 'ok') {
                        $tem_termo = true;
                        break;
                    }
                }
            }

            if ($tem_termo === false) {
                echo "erro_termo";
                exit;
            }
        } 
    }

    if ($login_fabrica == 164) {            
        /*$sqlDataOs = "SELECT data_abertura FROM tbl_os WHERE os = $os AND fabrica = 164 AND data_abertura >= '2019-11-01'";*/
        $sqlDataOs = "SELECT os.os, sp.status_pedido, sp.descricao, cp.campos_adicionais::jsonb->>'troca_produto' AS troca_produto, oi.os_item, tbl_os_troca.os_troca
                    FROM tbl_os AS os
                    LEFT JOIN tbl_os_produto     AS op ON op.os           = os.os 
					LEFT JOIN tbl_os_item        AS oi ON oi.os_produto   = op.os_produto
					LEFT JOIN tbl_os_troca ON tbl_os_troca.os = os.os
                    LEFT JOIN tbl_pedido         AS p  ON oi.pedido       = p.pedido
                    LEFT JOIN tbl_status_pedido  AS sp ON p.status_pedido = sp.status_pedido
                    LEFT JOIN tbl_os_campo_extra AS cp ON cp.os           = os.os AND cp.fabrica = $login_fabrica
                WHERE os.os    = {$os}
                    AND os.fabrica = $login_fabrica
                    AND data_abertura >= '2019-11-01' order by 2";

        $resDataOs = pg_query($con,$sqlDataOs);

        if (pg_num_rows($resDataOs) > 0) {
            $status_pedido      = pg_fetch_result($resDataOs, 0, 'status_pedido');
            $status_pedido_desc = pg_fetch_result($resDataOs, 0, 'descricao');
            $os_item_sim        = pg_fetch_result($resDataOs, 0, 'os_item');
            $troca_produto      = (pg_fetch_result($resDataOs, 0, 'troca_produto') == true || pg_fetch_result($resDataOs, 0, 'troca_produto') == 't' || !empty(pg_fetch_result($resDataOs,0, 'os_troca'))) ? true : false; 

            $sqlDataEntrada = "SELECT sr.descricao, op.os_produto, oi.servico_realizado, oi.parametros_adicionais::jsonb->>'data_recebimento' as data_recebimento
                                FROM tbl_os_produto AS op 
                                INNER JOIN tbl_os_item AS oi ON oi.os_produto = op.os_produto AND oi.fabrica_i = 164 
                                INNER JOIN tbl_servico_realizado AS sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = 164
                                WHERE op.os = {$os}";
            $resDataEntrada   = pg_query($con, $sqlDataEntrada);
            
            if (pg_num_rows($resDataEntrada) > 0) {
                $data_recebimento          = pg_fetch_result($resDataEntrada, 0, data_recebimento);
                $os_servico_realizado      = pg_fetch_result($resDataEntrada, 0, servico_realizado);
                $os_servico_realizado_desc = strtolower(pg_fetch_result($resDataEntrada, 0, descricao));
                $servicoIsAjuste           = ($os_servico_realizado == 11233 || $os_servico_realizado_desc == 'ajuste') ? true : false;    
            }        

            $isCancelado = ($status_pedido == 14 || $status_pedido_desc == 'Cancelado Total') ? true : false;

            if ($servicoIsAjuste == false) {
                $sqlAnexo = "SELECT json_field('typeId', obs) as anexo 
                            FROM tbl_tdocs 
                            WHERE fabrica = {$login_fabrica} 
                            AND contexto = 'os'
                            AND referencia_id = {$os}
                            AND situacao = 'ativo'"; 
                $resAnexo = pg_query($con, $sqlAnexo);
                $rows     = pg_num_rows($resAnexo);
                $anexados = [];

                if ($rows > 0) {
                    for ($i = 0; $i < $rows; $i++) {
                        $anexados[] = pg_fetch_result($resAnexo, $i, 'anexo');
                    }
                }
                
                if (verifica_tipo_posto("posto_interno", "false")) {
                    if (!$data_recebimento && !in_array($os_servico_realizado, [11235,11237]) && !$troca_produto && $isCancelado == false and !empty($os_item_sim)) {
                        $msg_erro = "erro;".traduz('nao.e.possivel.finalizar.a.os.sem.a.data.de.conferencia.da.peca', $con, $cook_idioma);
                        echo $msg_erro;
                        exit;
                    }

                    if ( (!in_array('comprovante_entrada', $anexados) || !in_array('evidencia', $anexados) || !in_array('comprovante_saida', $anexados)) ) {
                        if ($isCancelado == false || $isCancelado == true && ($os_servico_realizado == 11237 || $troca_produto)) { 
                            $msg_erro = "erro;".traduz('para.finalizar.a.os.e.necessario.que.os.seguintes.anexos.sejam.inseridos.:.comprovante.de.entrada.,.evidencia.,.comprovante.de.saida', $con, $cook_idioma);
                            echo $msg_erro;
                            exit;
                        }
                    }
                }
            }
        }
	}

    if ($login_fabrica == 19) {
        $sql = "SELECT os FROM tbl_os WHERE os = $os AND finalizada IS NOT NULL";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            die('erro;OS já finalizada.');
        }
    }

    
    if (in_array($login_fabrica, array(11,172))) {
        //Alterando o valor de login_fabrica para a fabrica da OS em questão

        $sql = "SELECT fabrica
            FROM tbl_os
            WHERE os = {$os}";
        $res = pg_query($con, $sql);

        $login_fabrica = pg_fetch_result($res, 0, 'fabrica');

    }

    if(in_array($login_fabrica, array(153,160)) or $replica_einhell) {
        $sql_conserto = "SELECT data_conserto FROM  tbl_os WHERE os = $os";
        $res_conserto = pg_query($con, $sql_conserto);
        if(pg_num_rows($res_conserto)>0){
            $data_conserto = pg_fetch_result($res_conserto, 0, 'data_conserto');

            if(strlen(trim($data_conserto))==0){
                $retorno .= traduz("o.produto.deve.ser.consertado.antes.de.fechar.os");
                echo "erro;$retorno";
                exit;
            }
        }
    }

    if($login_fabrica == 24){
        $sql_comprovante = "SELECT 
                                    (SELECT tdocs_id 
                                    FROM tbl_tdocs
                                    WHERE contexto = 'comprovante_retirada'
                                    AND referencia_id = tbl_os.os) AS link
                            FROM tbl_os
                            JOIN tbl_os_troca USING(os)
                            WHERE tbl_os.fabrica = {$login_fabrica}
                            AND tbl_os.finalizada IS NULL
                            AND tbl_os.excluida IS NOT TRUE
                            AND tbl_os_troca.fabric = {$login_fabrica}
                            AND tbl_os_troca.gerar_pedido IS TRUE 
                            AND tbl_os.os = $os";

        $res_comprovante = pg_query($con, $sql_comprovante);

        if(pg_num_rows($res_comprovante) > 0){
            $link = pg_fetch_result($res_comprovante, 'link');
            
            if(strlen(trim($link)) == 0){
                $retorno .= traduz("os.sem.comprovante.de.retirada");
                echo "erro;$retorno";
                exit;
            }
        }

        $sql_auditoria_troca = "SELECT liberada, reprovada 
                                    FROM tbl_auditoria_os 
                                    WHERE tbl_auditoria_os.os = {$os}
                                    AND tbl_auditoria_os.auditoria_status = 3 
                                    AND tbl_auditoria_os.liberada IS NULL 
                                    AND tbl_auditoria_os.reprovada IS NULL 
                                    AND tbl_auditoria_os.observacao = 'PRODUTOS TROCADOS NA OS'";

        $res_auditoria_troca = pg_query($con, $sql_auditoria_troca);

        if(pg_num_rows($res_auditoria_troca) > 0){
            $liberada = pg_fetch_result($res_auditoria_troca, 'liberada');
            $reprovada = pg_fetch_result($res_auditoria_troca, 'reprovada');
            
            if((strlen(trim($liberada)) == 0) && strlen(trim($reprovada)) == 0){
                $retorno .= traduz("os.em.auditoria");
                echo "erro;$retorno";
                exit;
            }
        }
    }

    $msg_erro = "";

    if(in_array($login_fabrica, [24,157])){

        $sql_os_item = "SELECT os_item FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
        $res_os_item = pg_query($con, $sql_os_item);

        if(pg_num_rows($res_os_item) == 0){
            $msg_erro .= traduz("para.finalizar.a.os") . " {$os}, " . traduz("e.obrigatorio.o.lancamento.de.peca");
        }

    }

    if($login_fabrica == 42){

        $sql_os_canc = "SELECT cancelada FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res_os_canc = pg_query($con, $sql_os_canc);

        $os_cancelada = pg_fetch_result($res_os_canc, 0, "cancelada");

        if($os_cancelada == "t"){

            $msg_erro .= traduz("a.os") . " $os " . traduz("esta.cancelada.assim.nao.podendo.ser.fechada");

        }else{

            $sql_auditoria_cortesia = "SELECT liberada FROM tbl_auditoria_os WHERE os = {$os} AND observacao = 'Auditoria de Solicitação de Cortesia Comercial'";
            $res_auditoria_cortesia = pg_query($con, $sql_auditoria_cortesia);

            if(pg_num_rows($res_auditoria_cortesia) > 0){

                $liberado_cortesia = pg_fetch_result($res_auditoria_cortesia, 0, "liberada");

                if(strlen($liberado_cortesia) == 0){
                    $msg_erro .= traduz("a.os") . " $os " . traduz("esta.em.auditoria.de.solicitacao.de.cortesia.comercial");
                }

            }


            $sql_aud_media = "SELECT liberada FROM tbl_auditoria_os WHERE os = {$os} AND observacao = 'Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
            $res_aud_media = pg_query($con, $sql_aud_media);

            if (pg_num_rows($res_aud_media) > 0) {

                $liberado = pg_fetch_result($res_aud_media, 0, "liberada");

                if (strlen($liberado) == 0) {
                    $msg_erro .= traduz("a.os") . " $os " . traduz("esta.em.auditoria.e.aguardando.aprovacao.da.fabrica");
                }
            }


        }

    }

    if($login_fabrica == 59){

        $sql_nome_tecnico = "SELECT tecnico FROM tbl_os WHERE os = $os";
        $res_nome_tecnico = pg_query($con, $sql_nome_tecnico);

        $nome_tecnico = pg_fetch_result($res_nome_tecnico, 0, 'tecnico');

        if(strlen($nome_tecnico) == 0){
            echo traduz("nome.do.tecnico");
            exit;
        }

    }

    if(in_array($login_fabrica, array(164))) {

        $sql_conserto = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os = {$os} AND data_conserto ISNULL";
        $res_conserto = pg_query($con, $sql_conserto);

        if(strlen(pg_last_error($con)) > 0){
            $msg_erro .= traduz("erro.ao.gravar.data.de.conserto") . " \n";
        }


    }
    // hd-6101045
    if ($login_fabrica == 151) {
        $sql = "SELECT      tbl_os_extra.recolhimento,
                            TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
                            tbl_os.sua_os
                FROM    tbl_os
                JOIN    tbl_os_produto          ON  tbl_os.os                   = tbl_os_produto.os
                JOIN    tbl_os_item             ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
                JOIN    tbl_os_extra            ON  tbl_os_extra.os             = tbl_os.os
                JOIN    tbl_peca                ON  tbl_peca.peca               = tbl_os_item.peca
                                                AND tbl_os_extra.extrato        IS NULL
                JOIN    tbl_faturamento_item    ON  (
                                                    tbl_faturamento_item.pedido     = tbl_os_item.pedido
                                                OR  tbl_faturamento_item.os_item    = tbl_os_item.os_item
                                                )
                                                AND tbl_faturamento_item.peca   = tbl_os_item.peca
                JOIN    tbl_faturamento         ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                                AND tbl_faturamento.fabrica     = $login_fabrica
                                                
                 WHERE tbl_os.posto = $login_posto
                 AND tbl_os.fabrica = $login_fabrica
                 AND tbl_os.finalizada isnull
                 AND tbl_os.os = $os";
        $res_sql = pg_query($con,$sql);
        $sua_os         = pg_fetch_result($res_sql, 0, 'sua_os');
        $emissao_f      = pg_fetch_result($res_sql, 0, 'emissao');
        $recolhimento_f = pg_fetch_result($res_sql, 0, 'recolhimento');
        if (!empty($emissao_f)) {
            $sql_data = "SELECT ((cast('$emissao_f' AS DATE) + INTERVAL '30 DAYS') < CURRENT_DATE) as data";
            $res_data = pg_query($con,$sql_data);
            $new_emissao = pg_fetch_result($res_data, 0, 'data');
        }

        if ($new_emissao == 'f' && $recolhimento == 't') {
            $msg_erro .= traduz("A OS % não pode ser fechada, pois existem peças pendentes ! ",null,null,[$sua_os]);
        } elseif ($new_emissao == 't') {
            $sql_recolhimento = "UPDATE tbl_os_extra SET recolhimento = false, obs_fechamento = 'Faturamento excedeu 30 dias.' WHERE os = $os AND i_fabrica = $login_fabrica";
            $res_recolhimento = pg_query($con, $sql_recolhimento);
        }
    }

    $res = pg_query ($con,"BEGIN TRANSACTION");

    if (isset($_GET["acao_email_os"])) {
        $sqlAcao = "UPDATE arquivo_acao3_dados SET data_resposta = current_timestamp WHERE os = $os";
        $resAcao = pg_query($con, $sqlAcao);
    }

    //HD-2938154 - TRAVA CASO NAO TENHA "defeito reclamado, defeito constatado ou solução"
    if ($login_fabrica == 72 || $login_fabrica == 3) {

        $sqlValida = "SELECT
            tbl_os.defeito_reclamado_descricao,
            tbl_os.defeito_reclamado,
            tbl_os.defeito_constatado,
            tbl_os.solucao_os,
            tbl_os.sua_os
            FROM tbl_os
        WHERE tbl_os.fabrica ={$login_fabrica}
        AND tbl_os.os ={$os}";
        //echo $sqlValida;die;
        $resValida = pg_query($con, $sqlValida);

        if (pg_num_rows($resValida) > 0) {
            $rowValida = pg_fetch_array($resValida);
            $sua_os = $rowValida['sua_os'];

            if (!$rowValida['defeito_reclamado'] && empty($rowValida['defeito_reclamado_descricao'])) {
                $erroValidado = 'Defeito Reclamado';
                $msg_erro     .= traduz("a.os") ." {$sua_os} ".traduz("nao.pode.ser.fechada.pois")." {$erroValidado} ".traduz("e.obrigatorio")."\n";
            }
            if (!$rowValida['defeito_constatado']) {
                $erroValidado = 'Defeito Constatado';
                $msg_erro     .= traduz("a.os") ." {$sua_os} ".traduz("nao.pode.ser.fechada.pois")." {$erroValidado} ".traduz("e.obrigatorio")."\n";
            }
            if (!$rowValida['solucao_os']) {
                $erroValidado = 'Solução da OS';
                $msg_erro     .= traduz("a.os") ." {$sua_os} ".traduz("nao.pode.ser.fechada.pois")." {$erroValidado} ".traduz("e.obrigatorio")."\n";
            }
        }
    }

    //fputti hd-2892486
    if (in_array($login_fabrica, array(50))) {
        $sqlOSDec = "SELECT A.consumidor_nome_assinatura, to_char(B.termino_atendimento, 'DD/MM/YYYY')  termino_atendimento
                       FROM tbl_os A
                       JOIN tbl_os_extra B ON B.os=A.os
                      WHERE A.os={$os}";
        $resOSDec = pg_query($con, $sqlOSDec);
        $dataRecebimento = pg_fetch_result($resOSDec, 0, 'consumidor_nome_assinatura');
        $recebidoPor     = pg_fetch_result($resOSDec, 0, 'termino_atendimento');
        if (strlen($dataRecebimento) == 0 && strlen($recebidoPor) == 0) {
            $msg_erro .= traduz("e.obrigatorio.o.preenchimento.da.declaracao.na.os")." {$os}.";
        }
    }

    if($login_fabrica == 52){

        $sql = "SELECT current_date - data_abertura AS interval, motivo_atraso FROM tbl_os WHERE os = {$os}";
        $res = pg_query($con, $sql);

        $interval = pg_fetch_result($res, 0, "interval");
        $motivo_atraso = pg_fetch_result($res, 0, "motivo_atraso");

        /* maaior que 72 horas */
        if($interval > 3 && strlen($motivo_atraso) == 0){

           #  $msg_erro .= traduz("Favor informar o motivo de atraso para realizar o fechamento da OS",$con,$cook_idioma);

        }

    }

    if ($login_fabrica == 3) {

        $sql = "SELECT tbl_os_item.os_item , tbl_os_extra.obs_fechamento
                FROM tbl_os_produto
                JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
                JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                JOIN tbl_os_extra          ON tbl_os_produto.os             = tbl_os_extra.os
                LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
                WHERE tbl_os_produto.os = $os
                AND tbl_servico_realizado.gera_pedido IS TRUE
                AND tbl_faturamento_item.faturamento_item IS NULL
                LIMIT 1";

        $res = @pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {

            $os_item = trim(pg_fetch_result($res,0,os_item));
            $obs_fechamento = trim(pg_fetch_result($res,0,obs_fechamento));

            if (strlen($os_item) > 0 and strlen($obs_fechamento) == 0) {
                $msg_erro .= traduz("os.com.pecas.pendentes,.favor.informar.o.motivo.do.fechamento",$con,$cook_idioma);
            }

        }

        $sql = "SELECT tbl_os.os FROM tbl_os WHERE tbl_os.os = $os AND tbl_os.defeito_constatado IS NULL";
        $res = pg_query($con, $sql);

        if (pg_num_rows ($res) > 0) {

            $sql = "UPDATE tbl_os SET defeito_constatado = 0 WHERE tbl_os.os = $os";
            $res = pg_query ($con, $sql);

        }

        $sql = "SELECT tbl_os.os FROM tbl_os WHERE tbl_os.os = $os AND tbl_os.solucao_os IS NULL";
        $res = pg_query ($con, $sql);

        if (pg_num_rows ($res) > 0) {
            $sql = "UPDATE tbl_os SET solucao_os = 0 WHERE tbl_os.os = $os";
            $res = pg_query ($con, $sql);
        }

        $sql = "SELECT tbl_os.os FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os.os = $os AND tbl_os_item.peca_serie_trocada IS NULL";
        $res = pg_query ($con, $sql);

        if (pg_num_rows ($res) > 0) {

            $sql = "UPDATE tbl_os_item SET peca_serie_trocada = '0000000000000' FROM tbl_os_produto JOIN tbl_os USING (os) WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os.os = $os";
            $res = pg_query ($con, $sql);
        }

    }
    if($login_fabrica == 74){

        try{

            $fechamentoOS = new FechamentoOS();
            $fechamentoOS->validaEfechaOS($os);
            $res = pg_query ($con,"COMMIT TRANSACTION");
            $resp = "ok;";
        }catch(Exception $ex){
            $msg_erro = $ex->getMessage();
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
           $resp = "erro;".$msg_erro;
        }
        echo $resp;
        exit;
    }

	$cond_intervencao = "62,64,65,72,73,87,81,88,116,117,118,187,189,191,192,193,194,202";

	if($login_fabrica == 124) $cond_intervencao = str_replace('118,', '', $cond_intervencao);

    if($login_fabrica == 120 or $login_fabrica == 201){

        $sql = "SELECT
                    status_os
                FROM tbl_os_status
                WHERE
                    os = {$os}
                ORDER BY data DESC
                LIMIT 1";

    }else{

        $sql = "SELECT status_os
            FROM tbl_os_status
            WHERE os = $os
            AND status_os IN ($cond_intervencao)
            ORDER BY os_status DESC
            LIMIT 1";

    }
    $res = pg_query ($con,$sql);
    if (pg_num_rows($res)>0 && (strlen($msg_erro) == 0)) {
        $status_os = trim(pg_fetch_result($res,0,status_os));
        if (in_array($status_os,array(62,72,87,116,118))){
            if ($login_fabrica ==51) { // HD 59408
                $sql = " INSERT INTO tbl_os_status
                        (os,status_os,data,observacao)
                        VALUES ($os,64,current_timestamp,'OS Fechada pelo posto')";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

                $sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
                        WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
                        AND   tbl_os_produto.os = $os";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

                $sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
                        WHERE tbl_os.os = $os";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }else{
                $msg_erro .= "ftfff" . traduz("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma);
            }
        }else if($status_os == "192"){
            $msg_erro .= traduz("os.com.intervencao.de.troca,.nao.pode.ser.fechada.",$con,$cook_idioma);
        }else if($status_os == 189){
            $msg_erro .= traduz("esta.os.esta.sob.auditoria.de.nota.fiscal.e.nao.pode.ser.fechada");
        }else if($status_os == 102 && ($login_fabrica == 120 or $login_fabrica == 201)){
            $msg_erro .= traduz("os").": $os ". traduz("em.intervencao.de.numero.de.serie.nao.pode.ser.fechada");
        }
    }

    if ($login_fabrica == 94) {
        $verifica_pedido = temPedido($os);
        if (!empty($verifica_pedido)) {
            $msg_erro = $verifica_pedido;
        }
    }

    if ($login_fabrica == 6) {
        if(!temNF($os,'bool')) {
            $msg_erro = traduz("nao.foi.encontrada.a.nf.para.essa.os.favor.reenviar.a.mesma.e.tente.novamente");
        }
    }

    if(strlen($msg_erro) == 0 AND (in_array($login_fabrica, array(120,201, 139)) || isset($novaTelaOs))){
        try {
            if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
                include "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";

                $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
                if (in_array($login_fabrica, array(169, 170))) {
                    $osClass = new $className($login_fabrica, $os, $con);
                } else {
                    $osClass = new $className($login_fabrica, $os);
                }
            } else {
                $osClass = new \Posvenda\Os($login_fabrica, $os);
            }

            $calcula_os = true;

            if ($login_fabrica == 158) {
                $arr = json_decode($json_info_posto, true);
                $tp = array_keys($arr["tipo_posto"]);

                if ($arr["tipo_posto"][$tp[0]]["tecnico_proprio"] == true || $arr["tipo_posto"][$tp[0]]["posto_interno"] == true) {
                    $calcula_os = false;
                }
            }

            if (in_array($login_fabrica, array(169,170))) {
                $sql = "
                    SELECT
                        CASE WHEN COUNT(*) > 0 THEN 't' ELSE 'f' END
                    FROM tbl_os_defeito_reclamado_constatado
                    JOIN tbl_defeito_constatado USING(defeito_constatado,fabrica)
                    WHERE fabrica = {$login_fabrica}
                    AND os = {$os}
                    AND lista_garantia = 'fora_garantia';
                ";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $defeito_fora_garantia = pg_fetch_result($res, 0, 0);

                    if ($defeito_fora_garantia == "t") {
                        $calcula_os = false;
                    }
                }
            }

            if ($calcula_os == true && !in_array($login_fabrica, array(171))) {
                if ($login_fabrica == 145) {
                    $tipo_os = $osClass->verificaOsRevisao($os);

                    if ($tipo_os == true) {
                       $osClass->calculaMaoDeObraRevisao($os);
                    } else {
                       $osClass->calculaOs();
                    }
                } else {
                    $osClass->calculaOs();
                }
            }

            $atendimento_callcenter = NULL;

            if ($login_fabrica == 156 || $login_fabrica == 171) {
                $atendimento_callcenter = $osClass->verificaAtendimentoCallcenter($os);

                if ($atendimento_callcenter && $login_fabrica == 156) {
                    if (!temNF($os, 'bool')) {
                        throw new Exception(traduz("favor.anexar.a.os")." $os ".traduz("assinada"));
                    }
                } elseif ($login_fabrica == 171) {
                    if (!temNF($os, 'bool')) {
                        throw new Exception(traduz("favor.anexar.a.os")." $os ".traduz("assinada"));
                    }
                }
            }

            if ($login_fabrica == 145 && $tipo_os == true) {
                $osClass->finalizaRevisao($con);
            } else {
                //só finaliza a os quando o admin aprova a auditoria de fechamento GROHE
                if ($login_fabrica != 171) {
                    $osClass->finaliza($con);
                }

                if ($atendimento_callcenter && $login_fabrica != 171) {
                    $osClass->finalizaAtendimento($atendimento_callcenter);
                }

                if (in_array($login_fabrica, array(171))) {

                    if (!$osClass->_model->verificaDefeitoConstatado($con)) {
                        throw new Exception(traduz("a.os")." $os ".traduz("esta.sem.defeito.constatado"));
                    }

                    $pedidoPendente = $osClass->_model->verificaPedidoPecasNaoFaturadasOS($con);

                    if (!empty($pedidoPendente)) {
                        throw new Exception($pedidoPendente);
                    }

                    $sql = "SELECT liberada
                            FROM tbl_auditoria_os
                            WHERE liberada IS NULL
                            AND cancelada IS NULL
                            AND reprovada IS NULL
                            AND os = {$os}";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) == 0) {
                        $sql = "UPDATE tbl_os SET status_checkpoint = 14 WHERE os = {$os} AND fabrica = {$login_fabrica}";
                        pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0)
                            throw new Exception(traduz("ocorreu.um.erro.ao.tentar.finalizar.a.os").": {$os}");

                        $sql = "SELECT auditoria_os
                            FROM tbl_auditoria_os
                            WHERE os = {$os}
                            AND observacao = 'Auditoria de Fechamento'";
                        $res = pg_query($con, $sql);

                        if (pg_num_rows($res) == 0) {

                            $verifica_pedido = temPedido($os);

                            if (empty($verifica_pedido)) {
                                $sql = "INSERT INTO tbl_auditoria_os(os,auditoria_status,observacao) VALUES({$os},6,'Auditoria de Fechamento')";
                                pg_query($con, $sql);
                                if (strlen(pg_last_error()) > 0) {
                                    throw new Exception(traduz("ocorreu.um.erro.ao.tentar.finalizar.a.os").": {$os}");
                                } else {
                                    $msg_sucesso_grohe = traduz("os.s.em.auditoria.de.fechamento.e.aguardando.aprovacao.da.fabrica.para.ser.finalizada");

                                    if (!empty($atendimento_callcenter)) {
                                        $sql = "INSERT INTO tbl_hd_chamado_item (
                                                    hd_chamado,
                                                    data,
                                                    comentario,
                                                    status_item
                                                ) VALUES (
                                                    $atendimento_callcenter,
                                                    CURRENT_TIMESTAMP,
                                                    '$comentario',
                                                    'Resolvido'
                                                )";
                                        pg_query($con, $sql);

                                        $sql = "UPDATE tbl_hd_chamado SET status = 'Resolvido' WHERE hd_chamado = $atendimento_callcenter";
                                        pg_query($con, $sql);

                                        if (strlen(pg_last_error()) > 0) {
                                            throw new Exception(traduz("erro.ao.fechar.os"));
                                        }
                                    }

                                    echo "erro;$msg_sucesso_grohe.";
                                }
                            } else {
                                throw new Exception($verifica_pedido);
                            }
                        } else {
                            throw new Exception(traduz("os.s.em.auditoria.de.fechamento.aguarde.a.aprovacao.da.fabrica"));
                        }
                    } else {
                        throw new Exception(traduz("existem.auditorias.nao.aprovadas.pela.fabrica"));
                    }
                }

            }

            if ((in_array($login_fabrica, array(160))) && empty($erro)) {
                $enviaSms = new \Posvenda\Helpers\Os();

                $sqlOs = "SELECT tbl_os.consumidor_celular
                          FROM tbl_os
                          WHERE tbl_os.os = $os";
                $resOs = pg_query($con, $sqlOs);

                if (pg_num_rows($resOs) > 0) {
                    $consumidor_celular = pg_fetch_result($resOs, 0, 'consumidor_celular');

                    if (!empty($consumidor_celular)) {
                        $msg_conserto_os = "OS {$os} PROD.ENTREGUE: Que nota vc atribui, entre 0 (insatisfeito) a 5(satisfeito),quanto ao atendimento geral prestado?responda de 0 a 5 (SMS sem custo)";

                        $enviaSms->comunicaConsumidor($consumidor_celular, $msg_conserto_os, $login_fabrica, $os);

                    }

                }

            }

        } catch(Exception $e) {
            if (in_array($login_fabrica, array(120,201, 169, 170, 171))) {
                $msg_erro .= $e->getMessage();
            } else {
                $msg_erro .= utf8_encode($e->getMessage());
            }
        }
    }

    if (strlen ($msg_erro) == 0 && !(in_array($login_fabrica, array(171)))) {
        if ($login_fabrica == 85) {
            $updDataHoraFechamento = ",
                data_digitacao_fechamento = CURRENT_TIMESTAMP
            ";
        }
        $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP $updDataHoraFechamento WHERE os = $os AND fabrica = $login_fabrica;";
        $res = pg_query ($con,$sql);
        $msg_erro .= pg_errormessage($con);
    }

    if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
        $sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
        $res = @pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);
    }

    if (strlen ($msg_erro) == 0 && !(in_array($login_fabrica, array(120,201, 139)) || isset($novaTelaOs))) {
        if (in_array($login_fabrica, array(11, 172))) {
            $aux_sql = "
                SELECT DISTINCT(tbl_os_item.pedido)
                FROM tbl_os_item
                JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				WHERE tbl_os_produto.os = $os
				AND tbl_os_item.pedido notnull
            ";
            $aux_res      = pg_query($con, $aux_sql);
            $aux_total    = pg_num_rows($aux_res);
            $pedidos      = array();
            $pedido_itens = array();

            for ($x = 0; $x < $aux_total; $x++) {
                $temp_pedido = pg_fetch_result($aux_res, $x, 'pedido');
                if (!in_array($pedidos, $temp_pedido)) {
                    $pedidos[] = $temp_pedido;
                }
                unset($temp_pedido);
            }

            if (count($pedidos) > 0) {
                foreach ($pedidos as $pedido) {
                    $aux_sql   = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
                    $aux_res   = pg_query($con, $aux_sql);
                    $aux_total = pg_num_rows($aux_res);

                    for ($x = 0; $x < $aux_total; $x++) {
                        $temp_pedido_item = pg_fetch_result($aux_res, $x, 'pedido_item');
                        if (!in_array($pedido_itens, $temp_pedido_item)) {
                            $pedido_itens[] = $temp_pedido_item;
                        }
                        unset($temp_pedido_item);
                    }
                }

                if (count($pedido_itens) > 0) {
                    foreach ($pedido_itens as $pedido_item) {
                        $aux_sql = "
                            SELECT pedido, qtde, qtde_faturada, qtde_cancelada
                            FROM tbl_pedido_item
                            WHERE pedido_item = $pedido_item
                            LIMIT 1
                        ";
                        $aux_res        = pg_query($con, $aux_sql);
                        $pedido         = (int) pg_fetch_result($aux_res, 0, 'pedido');
                        $qtde           = (int) pg_fetch_result($aux_res, 0, 'qtde');
                        $qtde_cancelada = (int) pg_fetch_result($aux_res, 0, 'qtde_cancelada');
                        $qtde_faturada  = (int) pg_fetch_result($aux_res, 0, 'qtde_faturada');

                        if($qtde_cancelada == 0 && $qtde_faturada == 0) {
                            $sql_cancel = "
                                UPDATE tbl_pedido_item SET
                                qtde_cancelada = $qtde
                                WHERE pedido_item = $pedido_item;

                                SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);
                            ";
                            $res_cancel = pg_query($con, $sql_cancel);

                            if (pg_num_rows($res_cancel) <= 0) {
                                $msg_erro = traduz("erro.ao.excluir.o.pedido.pendente.da.os");
                            }
                        } else {
                            $msg_erro = traduz("o.pedido.da.os.possui.itens.cancelados.e/ou.faturados.por.isso.nao.pode.ser.excluida");
                        }
                    }
                }
            }
            unset($aux_sql, $aux_res, $aux_total, $pedidos, $pedido_itens);
        }

        $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
        $res = @pg_query ($con,$sql);

        $msg_erro = pg_errormessage($con) ;

        if ($login_fabrica == '132' and !empty($msg_erro)) {
            preg_match('/ERROR:.*/', $msg_erro, $matches);

            if (array_key_exists(0, $matches)) {
                $msg_erro = $matches[0];
            }
        }

        if($login_fabrica == 50 AND strlen(trim($msg_erro))==0){
            $sql_ver_peca_obrigatoria = "SELECT tbl_os.os, tbl_faturamento_item.pedido, tbl_faturamento_item.faturamento_item
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
                    INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                    INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                    left JOIN tbl_faturamento_item ON (tbl_faturamento_item.pedido = tbl_os_item.pedido OR tbl_faturamento_item.os_item = tbl_os_item.os_item )
                    AND tbl_os_item.peca = tbl_faturamento_item.peca
                    WHERE tbl_os.os = $os
                    AND tbl_os.fabrica = $login_fabrica
                    /*AND tbl_os_item.pedido is not null*/
                    AND tbl_os_item.peca_obrigatoria = 't'
                    AND tbl_servico_realizado.troca_de_peca is true ";

            $res_ver_peca_obrigatoria = pg_query($con, $sql_ver_peca_obrigatoria);
            if(pg_num_rows($res_ver_peca_obrigatoria) > 0){

                $sql = "SELECT os FROM tbl_os_campo_extra where os = $os AND fabrica = $login_fabrica";
                $res = pg_query($con, $sql);
                if(pg_num_rows($res)==0){

                    $campos_adicionais['data'] = date("Y-m-d");
                    $campos_adicionais['obs'] = utf8_encode(traduz("pendente.de.devolucao.de.pecas"));
                    $campos_adicionais = json_encode($campos_adicionais);

                    $sql_campo_extra = "INSERT INTO tbl_os_campo_extra (os, fabrica, os_bloqueada, campos_adicionais) VALUES ($os, $login_fabrica, true, '$campos_adicionais' )";
                }else{
                    $campos_adicionais = json_decode($campos_adicionais);
                    $campos_adicionais['data'] = date("Y-m-d H:i:s");
                    $campos_adicionais['obs'] = utf8_encode(traduz("pendente.de.devolucao.de.pecas"));
                    $campos_adicionais = json_encode($campos_adicionais);

                    $sql_campo_extra = "UPDATE tbl_os_campo_extra SET os_bloqueada = true , campos_adicionais = '$campos_adicionais' WHERE os = $os AND fabrica = $login_fabrica ";
                }
                $res_campo_extra = pg_query($con, $sql_campo_extra);

                include 'grava_faturamento_peca_estoque_colormaq.php';
            }
        }

        if (strlen ($msg_erro) == 0 and in_array($login_fabrica,[1,24,120,201])) {
            $sql = "SELECT fn_estoque_os($os, $login_fabrica)";
            $res = @pg_query ($con,$sql);
	    $msg_erro = pg_errormessage($con);

        }

        if ((in_array($login_fabrica, array(160)) || $replica_einhell || (in_array($login_fabrica,array(80)) and !in_array($login_posto, array(40222, 368942))) )&& empty($msg_erro)) {

            $sql_celular = "SELECT consumidor_celular, sua_os, referencia, descricao, nome
                FROM tbl_os
                JOIN tbl_produto USING(produto)
                JOIN tbl_posto USING(posto)
                WHERE os = $os";
            $res_celular = pg_query($con, $sql_celular);
            $envia_sms = false;

            if (pg_num_rows($res_celular) > 0) {

                $consumidor_celular = pg_fetch_result($res_celular, 0, 'consumidor_celular');
                $sms_os             = pg_fetch_result($res_celular, 0, 'sua_os');
                $sms_produto        = pg_fetch_result($res_celular, 0, 'referencia') . ' - ' . pg_fetch_result($res_celular, 0, 'descricao');
                $sms_posto          = pg_fetch_result($res_celular, 0, 'nome');
                $sms_produto_descricao= pg_fetch_result($res_celular, 0, 'descricao');

                if (!empty($consumidor_celular)) {
                    $envia_sms = true;
                }

                $qry_enviou_sms = pg_query($con, "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os");
                if (pg_num_rows($qry_enviou_sms) > 0) {
                    $arr_campos_adicionais = json_decode(pg_fetch_result($qry_enviou_sms, 0, 'campos_adicionais'), true);
                    if (!empty($arr_campos_adicionais) and array_key_exists('enviou_sms', $arr_campos_adicionais)) {
                        if ($arr_campos_adicionais['enviou_sms'] == 't') {
                            $envia_sms = false;
                        }
                    }
                }

                if (true === $envia_sms) {

                    if($login_fabrica == 101){

                        $sql_dc = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os = {$os} AND data_conserto ISNULL";
                        $res_dc = pg_query($con, $sql_dc);

                        $sms_msg = "Conserto de Produto DeLonghi-Kenwood - OS {$sms_os}. Informamos que seu produto {$sms_produto} que esta no Posto autorizado {$sms_posto}, já esta consertado. Por favor solicitamos comparecer ao Posto para retirada. Atenciosamente, DeLonghi Kenwood.";

                    } else if ($login_fabrica == 160 or $replica_einhell) {
                        $primeira_descricao = explode(" ",substr($sms_produto_descricao, 0, 14));

                        $sms_msg = "OS {$sms_os} CONCLUIDA: Seu produto ".$primeira_descricao[0]." esta PRONTO.Aguardamos você na autorizada {$sms_posto}, para retirada do produto";
                    } else {

                        $sms_msg = "Produto Amvox - OS $sms_os. ".
                        "Informamos que seu produto $sms_produto, que está em nosso Posto Autorizado $sms_posto, já está consertado." .
                        "Solicitamos sua presença para retirada com brevidade.";

                    }

                    if ($sms->enviarMensagem($consumidor_celular, $sms_os, '', $sms_msg)) {
                        $ins_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '{\"enviou_sms\": \"t\"}')";
                        $qry_campos_adicionais = pg_query($con, $ins_campos_adicionais);
                    }
                }
            }
        }

    }
    if (strlen ($msg_erro) == 0 and $login_fabrica==24) { //HD 3426
        $sql = "SELECT fn_estoque_os($os, $login_fabrica)";
        $res = @pg_query ($con,$sql);
    }
        //HD 11082 17347
    if(strlen($msg_erro) == 0 && in_array($login_fabrica, array(11,172)) && $login_posto == 14301){
        $sqlm="SELECT tbl_os.sua_os          ,
                     tbl_os.consumidor_email,
                     tbl_os.serie           ,
                     tbl_posto.nome         ,
                     tbl_produto.descricao  ,
                     to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
                from tbl_os
                join tbl_produto using(produto)
                join tbl_posto on tbl_os.posto = tbl_posto.posto
                where os=$os";
        $resm=pg_query($con,$sqlm);
        $msg_erro .= pg_errormessage($con) ;

        $sua_osm           = trim(pg_fetch_result($resm,0,sua_os));
        $consumidor_emailm = trim(pg_fetch_result($resm,0,consumidor_email));
        $seriem            = trim(pg_fetch_result($resm,0,serie));
        $data_fechamentom  = trim(pg_fetch_result($resm,0,data_fechamento));
        $nomem             = trim(pg_fetch_result($resm,0,nome));
        $descricaom        = trim(pg_fetch_result($resm,0,descricao));

        if(strlen($consumidor_emailm) > 0){

            $nome         = "TELECONTROL";
            $email_from   = "helpdesk@telecontrol.com.br";
            $assunto      = traduz("ordem.de.servico.fechada",$con,$cook_idioma);
            $destinatario = $consumidor_emailm;
            $boundary = "XYZ-" . date("dmYis") . "-ZYX";

            $mensagem = traduz("a.ordem.de.serviço.%.referente.ao.produto.%.com.número.de.série.%.foi.fechada.pelo.posto.%.no.dia.%",$con,$cook_idioma,array($sua_osm,$descricaom,$seriem,$nomem,$data_fechamentom));


            $body_top = "--Message-Boundary\n";
            $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
            $body_top .= "Content-transfer-encoding: 7BIT\n";
            $body_top .= "Content-description: Mail message body\n\n";
            @mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
        }
    }

    if($login_fabrica == 85 && empty($msg_erro)){

        $validaFechamento = new \Posvenda\Validacao\_85\FechamentoOs($os, $con);

        if (false === $validaFechamento->validaFechamento()) {
            $msg_erro = $validaFechamento->getErros();
        }
        if (empty($msg_erro)){

            $sql = "SELECT hd_chamado FROM tbl_os WHERE os = {$os}";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) > 0){

                $hd_chamado = pg_fetch_result($res, 0, 'hd_chamado');

                if (!empty($hd_chamado)) {
                    $xdata_hora_fechamento = date('Y-m-d H:i:s');
                    $sqlUp = "
                        UPDATE  tbl_os
                        SET     data_digitacao_fechamento = '$xdata_hora_fechamento'
                        WHERE   os = $os
                        AND     hd_chamado = $hd_chamado
                    ";
//                     echo nl2br($sqlUp);
                    $resUp = pg_query($con,$sqlUp);

                    $sql_admin = "
                        SELECT tbl_admin.email
                        FROM tbl_hd_chamado
                        JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
                        WHERE tbl_hd_chamado.hd_chamado = $hd_chamado
                    ";
                    $res_admin = pg_query($con, $sql_admin);

                    $email_atendente = pg_fetch_result($res_admin, 0, 'email');

                    include "class/log/log.class.php";

                    $log = new Log();

                    $log->adicionaLog(traduz("informamos.que.a.os")." $os ".traduz("foi.finalizada.pelo.posto"));

                    $log->adicionaTituloEmail(traduz("finalizacao.da.os")." $os ".traduz("pelo.posto"));

                    if($ip_devel == "201.76.81.229"){
                        $log->adicionaEmail("guilherme.silva@telecontrol.com.br");
                    }else{
                        $log->adicionaEmail($email_posto);
                    }

                    $log->enviaEmails();
                }


            }

        }

    }

    if(empty($msg_erro)){

        if ($login_fabrica == 30) {
            if (!empty($login_unico)) {
                $sql_lu = "UPDATE tbl_os_extra SET obs_fechamento = '$login_unico_nome' WHERE os = $os ;";
                $res_lu = pg_query($con,$sql_lu);
                $msg_erro .= pg_errormessage($con);
            }else{
                $sql_lu = "UPDATE tbl_os_extra SET obs_fechamento = '$login_codigo_posto' WHERE os = $os ;";
                $res_lu = pg_query($con,$sql_lu);
                $msg_erro .= pg_errormessage($con);
            }
        }

        if($login_fabrica == 50){
            $sql = "SELECT os from tbl_os_extra WHERE os = $os and i_fabrica = $login_fabrica";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res)>0){
                $sql_ce = "UPDATE tbl_os_extra SET obs_fechamento = '$login_codigo_posto' WHERE os = $os ;";
            }else{
                $sql_ce = "INSERT INTO tbl_os_extra (os, obs_fechamento) VALUES ($os, '$login_codigo_posto')";
            }
            $res_ce = pg_query ($con,$sql_ce);
            $msg_erro .= pg_errormessage($con);
        }

        if($login_fabrica == 15){

            $sql_os_hd_chamado = "SELECT atendente, hd_chamado FROM tbl_hd_chamado_extra JOIN tbl_hd_chamado USING(hd_chamado) WHERE os = {$os}";
            $res_os_hd_chamado = pg_query($con, $sql_os_hd_chamado);

            if(pg_num_rows($res_os_hd_chamado) > 0){

                $admin = pg_fetch_result($res_os_hd_chamado, 0, "atendente");
                $hd_chamado = pg_fetch_result($res_os_hd_chamado, 0, "hd_chamado");

                $sql_email = "SELECT email, nome_completo FROM tbl_admin WHERE admin = $admin";
                $res_email = pg_query($con, $sql_email);

                if(pg_num_rows($res_email) > 0){

                    $nome = pg_fetch_result($res_email, 0, "nome_completo");
                    $email = pg_fetch_result($res_email, 0, "email");

                    $email_consumidor->adicionaLog(array("titulo" => "OS Finalizada: ".$os));

                    $mensagem_email = "
                    Olá {$nome}, informamos que a Ordem de Serviço nº {$os} foi finalizada pelo posto,
                    por favor finalizar o chamado {$hd_chamado} no Call-Center. <br /> <br />
                    Email automático, favor não respoder.
                    ";

                    $email_consumidor->adicionaLog($mensagem_email);

                    $email_consumidor->adicionaTituloEmail("Finalização da OS Latina - ".$os);
                    $email_consumidor->adicionaEmail($email);
                    $email_consumidor->enviaEmails();
                    $email_consumidor->limpaDados();

                }

            }

        }

        if($login_fabrica == 140){

            $sql_email = "  SELECT
                                tbl_os.consumidor_nome,
                                tbl_os.consumidor_email,
                                tbl_produto.referencia,
                                tbl_produto.descricao
                            FROM tbl_os
                            JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                            WHERE tbl_os.os = {$os}
                            AND tbl_os.fabrica = {$login_fabrica}";
            $res_email = pg_query($con, $sql_email);

            $nome               = pg_fetch_result($res_email, 0, 'consumidor_nome');
            $consumidor_email   = pg_fetch_result($res_email, 0, 'consumidor_email');
            $referencia         = pg_fetch_result($res_email, 0, 'referencia');
            $descricao          = pg_fetch_result($res_email, 0, 'descricao');

            if(!empty($consumidor_email)){

                $ip_devel = $_SERVER['REMOTE_ADDR'];
                $consumidor_email = ($ip_devel == "179.233.213.77") ? "guilherme.silva@telecontrol.com.br" : $consumidor_email;

                $mensagem_email = "
                    Sua Ordem de Serviço nº {$os} foi finalizada.
                    <br /> <br />
                    O produto {$referencia} - {$descricao} está à  disposição caso não tenha sido retirado.
                    <br /> <br />
                    Favor apresentar a Ordem de Serviço nº {$os} para a retirada.
                    <br /> <br />
                    Serviço Lavor de Atendimento.
                ";

                $email_consumidor->adicionaLog($mensagem_email);

                $email_consumidor->adicionaTituloEmail("Finalização da OS Lavor - ".$os);
                $email_consumidor->adicionaEmail($consumidor_email);
                $email_consumidor->enviaEmails();
                $email_consumidor->limpaDados();

            }

        }

/*        if (in_array($login_fabrica, array(144))) {

            $sql = "SELECT tbl_os.consumidor_email, tbl_produto.descricao, tbl_os.nota_fiscal
                    FROM tbl_os
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.os = {$os}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $consumidor_email  = pg_fetch_result($res, 0, "consumidor_email");
                $produto_descricao = pg_fetch_result($res, 0, "descricao");
                $nota_fiscal       = pg_fetch_result($res, 0, "nota_fiscal");

                if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {
                    $header  = "MIME-Version: 1.0 \r\n";
                    $header .= "Content-type: text/html; charset=iso-8859-1 \r\n";
                    $header .= "To: {$consumidor_email} \r\n";
                    $header .= "From: naoresponder@telecontrol.com.br\r\n";

                    $conteudo = "Ordem de Serviço {$os} foi finalizada.<br />
                                Produto: {$produto_descricao}.<br />
                                Nota fiscal: {$nota_fiscal}.<br />
                                Se ainda não retirou o produto da assistência favor retirar, para mais informações entre em contato com a assistência.";

                    $nome_fabrica = ($login_fabrica == 141) ? "UNICOBA" : "HIKARI";

                    mail($consumidor_email, "{$nome_fabrica} - Finalização da Ordem de Serviço", $conteudo, $header);
                }
            }
        }*/

    }

    if ( empty($msg_erro) AND in_array($login_fabrica, $envia_pesquisa_finaliza_os) ) {
        $sql_pesquisa = "SELECT pesquisa , categoria, texto_ajuda
                            FROM tbl_pesquisa
                            WHERE fabrica = {$login_fabrica}
                                AND ativo IS TRUE
                                AND categoria in ('ordem_de_servico_email')
                                AND ativo IS TRUE";
        $res_pesquisa = pg_query($con, $sql_pesquisa);

        if (pg_num_rows($res_pesquisa) > 0) {
            $texto_ajuda = pg_fetch_result($res_pesquisa, 0, texto_ajuda);

            $sql_envia = "SELECT  tbl_os.consumidor_email,
                            tbl_os.consumidor_nome,
                            tbl_produto.descricao,
                            tbl_produto.referencia
                        FROM tbl_os
                        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                        AND tbl_produto.fabrica_i = $login_fabrica
                        WHERE os = $os";
            $res_envia = pg_query($con,$sql_envia);

            //echo nl2br($sql_envia);

            if (pg_num_rows($res_envia) > 0) {
                $email_envia = pg_fetch_result($res_envia,0,'consumidor_email');
                $produto_referencia_envia = pg_fetch_result($res_envia,0,'referencia');
                $produto_nome_envia = pg_fetch_result($res_envia,0,'descricao');
                $consumidor_nome_envia = pg_fetch_result($res_envia,0,'consumidor_nome');
                //$link_temp_envia = explode("admin/",$HTTP_REFERER);
                $link_temp = explode("os_",$HTTP_REFERER);

                //if ($login_fabrica == 161) {
                $from_fabrica           = "no_reply@telecontrol.com.br";
                $from_fabrica_descricao = "Pós-Venda Cristófoli";
                $link_pesquisa = $link_temp[0]."externos/cristofoli/callcenter_pesquisa_satisfacao2.php?os=$os";
                $assunto  = "Pesquisa de Satisfação - Cristófoli";
                //}


                if(strlen($email_envia) > 0){
                    $valida_email = filter_var($email_envia,FILTER_VALIDATE_EMAIL);

                    if($valida_email !== false){

                        $mensagem = "Produto: $produto_referencia_envia - $produto_nome_envia <br>";
                        $mensagem .= "Ordem de Serviço: $os, <br>";

                        $mensagem .= "Prezado(a) $consumidor_nome_envia, <br>";
                        /*$mensagem .= "Sua opinião é muito importante para melhorarmos nossos serviços<br>";
                        $mensagem .= "Por favor, faça uma avaliação sobre nossos produtos e atendimento através do link abaixo: <br />";*/
                        $mensagem .= nl2br($texto_ajuda) ."<br>";
                        $mensagem .= "Pesquisa de Satisfação: <a href='$link_pesquisa' target='_blank'>Acesso Aqui</a> <br><br>Att <br>Equipe ".$login_fabrica_nome;

                        $headers  = "MIME-Version: 1.0 \r\n";
                        $headers .= "Content-type: text/html \r\n";
                        $headers .= "From: $from_fabrica_descricao <no_reply@telecontrol.com.br> \r\n";

                        if(!mail($consumidor_nome_envia .'<'.$email_envia.'>', $assunto, utf8_encode($mensagem), $headers)){
                            $msg_erro = "Erro ao enviar email de pesquisa satisfação!";
                        }
                    }
                }
            }
        }
    }

    // Todas as OS finalizadas devem entrar em Auditoria de termo HD-6376083
    if (strlen($msg_erro) == 0 && in_array($login_fabrica, [123,160]) && data_corte_termo($os)) {
        $sql_auditoria_termo = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, 6, 'Auditoria de Termo')";
        $res_auditoria_termo = pg_query($con, $sql_auditoria_termo);

        if (pg_last_error()) {  
            $msg_erro .= pg_errormessage($con)."<br />";
        }
    }

    if (strlen ($msg_erro) == 0) {

        $res = pg_query ($con,"COMMIT TRANSACTION");
        //pg_query ($con,"ROLLBACK TRANSACTION");

        if ($login_fabrica == 104) {
            $sql_sms = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
            $qry_sms = pg_query($con, $sql_sms);
            $campos_adicionais = array();
            $insert_campo_extra = false;

            if (pg_num_rows($qry_sms) == 0) {
                $insert_campo_extra = true;
            } else {
                $campos_adicionais = json_decode(pg_fetch_result($qry_sms, 0, 'campos_adicionais'), true);
            }

            if (!array_key_exists("enviou_msg_consertado", $campos_adicionais) or $campos_adicionais["enviou_msg_consertado"] <> "t") {
                $helper = new \Posvenda\Helpers\Os();

                $sql_contatos_consumidor = "
                    SELECT consumidor_email,
                        consumidor_celular,
                        referencia,
                        descricao,
                        tbl_posto.nome
                    FROM tbl_os
                    JOIN tbl_produto USING(produto)
                    JOIN tbl_posto USING(posto)
                    WHERE os = $os";
                $qry_contatos_consumidor = pg_query($con, $sql_contatos_consumidor);

                $consumidor_email = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_email');
                $consumidor_celular = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_celular');
                $produto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia') . ' - ' . pg_fetch_result($qry_contatos_consumidor, 0, 'descricao');
                $posto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'nome');

                $msg_conserto_os = traduz("produto.vonder.os")." $os . ".traduz("informamos.que.seu.produto")." $produto_os ".traduz("que.esta.em.nosso.posto")." $posto_os ".traduz("ja.esta.consertado.solicitamos.sua.presenca.para.retirada.com.brevidade");

                if (!empty($consumidor_email)) {
                    $helper->comunicaConsumidor($consumidor_email, $msg_conserto_os);
                }

                if (!empty($consumidor_celular)) {
                    $helper->comunicaConsumidor($consumidor_celular, $msg_conserto_os, $login_fabrica, $os);
                }

                $campos_adicionais["enviou_msg_consertado"] = "t";
                $json_campos_adicionais = json_encode($campos_adicionais);

                if (true === $insert_campo_extra) {
                    $sql_msg_consertado = "
                        INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais)
                            VALUES ({$os}, {$fabrica}, '{$json_campos_adicionais}')";
                } else {
                    $sql_msg_consertado = "
                        UPDATE tbl_os_campo_extra SET
                            campos_adicionais = '{$json_campos_adicionais}'
                        WHERE os = $os";
                }

                $qry_msg_consertado = pg_query($con, $sql_msg_consertado);
            }
        }

        //Envia e-mail para o consumidor, avisando da abertura da OS - HD 150972
        if (($login_fabrica == 14) || ($login_fabrica == 43) || ($login_fabrica == 66)) {
            $novo_status_os = "FECHADA";
            include('os_email_consumidor.php');
        }

        if (in_array($login_fabrica, array(169,170))) {
            try {
                $sql = "
                    SELECT o.sua_os
                    FROM tbl_auditoria_os ao
                    INNER JOIN tbl_os o ON o.os = ao.os AND o.fabrica = {$login_fabrica}
                    WHERE ao.os = {$os}
                    AND ao.liberada IS NULL
                    AND ao.cancelada IS NULL
                    AND ao.reprovada IS NULL
                ";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0) {
                    $sqlTipoAtendimento = "
                        SELECT ta.fora_garantia, ta.grupo_atendimento
                        FROM tbl_os o
                        INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                        WHERE o.fabrica = {$login_fabrica}
                        AND o.os = {$os}
                    ";
                    $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

                    $tipoAtendimento = pg_fetch_assoc($resTipoAtendimento);

                    if (!($tipoAtendimento["fora_garantia"] == "t" && empty($tipoAtendimento["grupo_atendimento"]))) {
                        $notificacao = false;
                        $exportOs = false;

                        // Integração Notificação
                        $notaIntegracao = $osClass->getDadosNotaExport($os);
                        $notificacao = $osClass->exportNotificacao($notaIntegracao);

                        if ($notificacao === true) {
                            // Integração Ordem de Serviço
                            $osIntegracao = $osClass->getDadosOSExport($os);
                            $exportOs = $osClass->exportOS($osIntegracao);
                        }

                        if ($notificacao === false || $exportOs === false) {
                            $sql_up = "UPDATE tbl_os SET data_fechamento = null, finalizada = null WHERE os = {$os}";
                            $res_up = pg_query($con, $sql_up);
                            throw new Exception(traduz("nao.foi.possivel.finalizar.a.os.entre.em.contato.com.o.analista.de.garantia"));
                        }
                    }
                }
            } catch(Exception $e) {
                $sqlRollback = "
                    UPDATE tbl_os SET
                        finalizada = null,
                        data_fechamento = null
                    WHERE fabrica = {$login_fabrica}
                    AND os = {$os}
                ";
                $resRollback = pg_query($con, $sqlRollback);

                echo "erro;".utf8_encode($e->getMessage());
                flush();
                exit;
            }
        }

        if(in_array($login_fabrica, array(94))) {
            $excecaoMO = new \Posvenda\ExcecaoMobra($os,$login_fabrica);
            $excecaoMO->calculaExcecaoMobra();
        }

        if ($login_fabrica != 171) {
            if (!empty($msg_sucesso_auditoria)) {
                echo "ok;$msg_sucesso_auditoria";
            } else {
                echo "ok;XX$os";
            }
        } else {
            echo "ok;$msg_sucesso_grohe";
        }
    } else {
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");

        $msg_erro = str_replace(array("&atilde;", "&ccedil;", "&aacute;"), array("ã","ç","á"), $msg_erro);

        echo "erro;" . $msg_erro;
    }
    
    
    flush();
    exit;
}

#Motivo atraso fechamento
if ($_GET['motivoAtraso'] == 1) {
    $os_motivo = $_GET['idOS'];
    $motivo    = $_GET['motivo'];

    $sql = "SELECT OS FROM tbl_os_campo_extra WHERE os = $os_motivo AND fabrica = $login_fabrica";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        $sql = "UPDATE tbl_os_campo_extra SET motivo_atraso_fechamento=$motivo
            WHERE   os      = $os_motivo AND
                fabrica = $login_fabrica";
    }else{
        $sql = "INSERT INTO tbl_os_campo_extra(
                    os,
                    fabrica,
                    motivo_atraso_fechamento
                  ) VALUES (
                    $os_motivo,
                    $login_fabrica,
                    $motivo
             )";
    }
    $res = pg_query($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen($msg_erro) == 0) {
        echo "OK|".traduz("motivo.gravado.com.sucesso");
    } else {
        echo "NO|Erro: $msg_erro";
    }

    exit;

}

if (strlen($_GET['ajax_reabrir']) > 0) {
	$os = $_GET['os'];

	$sql = "SELECT os FROM tbl_os_extra WHERE os = $os AND extrato IS NOT NULL";
	$res = pg_query($con, $sql);

	if ( pg_num_rows ( $res ) ) {
		$msg_erro .= traduz("esta.os.nao.pode.ser.reaberta.pois.ja.entrou.em.extrato");
	}else{

		$sql = "SELECT count(*)
			FROM tbl_os_item
			JOIN tbl_os_produto USING(os_produto)
			JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			WHERE os = $os
			AND fabrica = $login_fabrica
			AND tbl_servico_realizado.troca_produto IS TRUE";

		$res = pg_query($con,$sql);

		if (pg_fetch_result($res,0,0) == 0) {

			$sql = "UPDATE tbl_os SET data_fechamento = null, finalizada = null
				WHERE tbl_os.os      = $os
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_os.posto   = $login_posto;";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

		} else {
			$msg_erro .= traduz("esta.os.nao.pode.ser.reaberta.pois.a.solucao.foi.a.troca.do.produto");
		}
	}

	echo $msg_erro;
	exit;

}

$msg_erro = "";


$meses = array(1 => traduz("janeiro",$con,$cook_idioma), traduz("fevereiro",$con,$cook_idioma), traduz("marco",$con,$cook_idioma), traduz("abril",$con,$cook_idioma), traduz("maio",$con,$cook_idioma), traduz("junho",$con,$cook_idioma), traduz("julho",$con,$cook_idioma), traduz("agosto",$con,$cook_idioma), traduz("setembro",$con,$cook_idioma), traduz("outubro",$con,$cook_idioma), traduz("novembro",$con,$cook_idioma), traduz("dezembro",$con,$cook_idioma));


if (strlen($btn_acao) > 0 ) {
    if ($login_fabrica == 30) {
        $sql = "SELECT tbl_tipo_posto.descricao FROM tbl_posto_fabrica LEFT JOIN tbl_tipo_posto ON(tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto) WHERE posto = {$login_posto} AND tbl_posto_fabrica.fabrica = {$login_fabrica};";

        $res = pg_query($con,$sql);
        $tipo_de_posto = trim(pg_fetch_result($res,0,descricao));
    }

    if($login_fabrica == 162){
        $imei = $_POST["imei"];
    }

    if($login_fabrica == 164){
        $cep = str_replace("-", "", $_POST["cep"]);
    }

    $dash = $_GET['dash'];
    if($login_fabrica == 3){
        $res_protocolo = $_POST['protocolo_atendimento'];
        if (strlen($res_protocolo)> 0){
            $sql =  "SELECT count(tbl_hd_chamado_extra.hd_chamado) AS hd_chamado
                    FROM tbl_hd_chamado_extra
                    JOIN tbl_hd_chamado
                    ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                    AND tbl_hd_chamado_extra.ordem_montagem = $res_protocolo
                    AND tbl_hd_chamado.fabrica = $login_fabrica
                    GROUP BY tbl_hd_chamado_extra.hd_chamado";
                $res = pg_query($con,$sql);
                if (pg_num_rows($res) == 0) {
                    $msg_erro = traduz("numero.do.protocolo.invalido");
                }
        }
    }
    //HD 211825: Filtro por tipo de OS: Consumidor/Revenda
    $consumidor_revenda_pesquisa = trim(strtoupper ($_POST['consumidor_revenda_pesquisa']));
    if (strlen($consumidor_revenda_pesquisa) == 0) $consumidor_revenda_pesquisa = trim(strtoupper($_GET['consumidor_revenda_pesquisa']));

    $os_off    = trim (strtoupper ($_POST['os_off']));
    if (strlen($os_off)==0) $os_off = trim(strtoupper($_GET['os_off']));
    $codigo_posto_off       = trim(strtoupper($_POST['codigo_posto_off']));
    if (strlen($codigo_posto_off)==0) $codigo_posto_off = trim(strtoupper($_GET['codigo_posto_off']));
    $posto_nome_off        = trim(strtoupper($_POST['posto_nome_off']));
    if (strlen($posto_nome_off)==0) $posto_nome_off = trim(strtoupper($_GET['posto_nome_off']));

    $marca     = trim ($_POST['marca']);
    if (strlen($marca)==0) $marca = trim($_GET['marca']);

    if ($login_fabrica == 91) {
        $sua_os = trim (strtoupper ($_REQUEST['sua_os']));
    } else {
        $sua_os = trim (strtoupper ($_POST['sua_os']));
    }

    if (strlen($sua_os) == 0)
        $sua_os    = trim (strtoupper ($_GET['sua_os']));

    $tipo_atendimento = trim(@$_REQUEST['tipo_atendimento']);
    $descricao_tipo_atendimento = trim(@$_REQUEST['tipo_atendimento']);
    if(strlen($sua_os)>0 AND strlen($sua_os)<4){
        $msg_erro = traduz("favor.digitar.no.minimo.4(quatro).caracteres",$con,$cook_idioma);
    }
    $serie     = trim (strtoupper ($_POST['serie']));
    if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));

    if ($login_fabrica == 158) {
        $patrimonio     = trim (strtoupper ($_POST['patrimonio']));
        if (strlen($patrimonio)==0) $patrimonio = trim(strtoupper($_GET['patrimonio']));
    }

    $nf_compra = trim (strtoupper ($_POST['nf_compra']));
    if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
    $consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));
    if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));

    $data_inicial = trim (strtoupper ($_POST['data_inicial']));
    if (strlen($data_inicial)==0) $data_inicial = trim(strtoupper($_GET['data_inicial']));

    $data_final = trim (strtoupper ($_POST['data_final']));
    if (strlen($data_final)==0) $data_final = trim(strtoupper($_GET['data_final']));

    $codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
    if (strlen($codigo_posto)==0) $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
    $posto_nome         = trim(strtoupper($_POST['posto_nome']));
    if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
    $consumidor_nome    = trim($_POST['consumidor_nome']);
    if (strlen($consumidor_nome)==0) $consumidor_nome = trim($_GET['consumidor_nome']);
    $produto_referencia = trim(strtoupper($_POST['produto_referencia']));
    if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
    $os_aberta          = trim(strtoupper($_POST['os_aberta']));
    if (strlen($os_aberta)==0) $os_aberta = trim(strtoupper($_GET['os_aberta']));

    if (in_array($login_fabrica, array(169,170))){
        $os_cortesia          = trim(strtoupper($_POST['os_cortesia']));
        if (strlen($os_cortesia)==0) $os_cortesia = trim(strtoupper($_GET['os_cortesia']));
    }


    $status_checkpoint          = trim(strtoupper($_POST['status_checkpoint']));
    if (strlen($status_checkpoint)==0) $status_checkpoint = trim(strtoupper($_GET['status_checkpoint']));

    $status_checkpoint_pesquisa = $status_checkpoint;

    if (!empty($_REQUEST["os_elgin_status"])) {
        $os_elgin_status = $_REQUEST["os_elgin_status"];

        $qry_status = pg_query($con, "SELECT status_os FROM tbl_status_os WHERE descricao = '{$os_elgin_status}'");

        $status_os_ultimo = '0';

        if (pg_num_rows($qry_status)) {
            $status_os_ultimo = pg_fetch_result($qry_status, 0, 'status_os');
        }
    }

    $revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));
    if (strlen($revenda_cnpj)==0) $revenda_cnpj = trim(strtoupper($_GET['revenda_cnpj']));

    $natureza = trim($_POST['natureza']); //HD 45630

    if ($login_e_distribuidor <> 't') $codigo_posto = $login_codigo_posto ;

    $consumidor_cpf = str_replace (".","",$consumidor_cpf);
    $consumidor_cpf = str_replace (" ","",$consumidor_cpf);
    $consumidor_cpf = str_replace ("-","",$consumidor_cpf);
    $consumidor_cpf = str_replace ("/","",$consumidor_cpf);
    if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
        #HD 17333
        $msg_erro = traduz("tamanho.do.cpf.do.consumidor.invalido",$con,$cook_idioma);
    }

    $revenda_cnpj = str_replace (".","",$revenda_cnpj);
    $revenda_cnpj = str_replace (" ","",$revenda_cnpj);
    $revenda_cnpj = str_replace ("-","",$revenda_cnpj);
    $revenda_cnpj = str_replace ("/","",$revenda_cnpj);
    if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
        $msg_erro = traduz("digite.os.8.primeiros.digitos.do.cnpj",$con,$cook_idioma);
    }


    if (strlen ($nf_compra) > 0 ) {
        if (($login_fabrica==19) and strlen($nf_compra) > 6) {
            $nf_compra = "0000000" . $nf_compra;
            $nf_compra = substr ($nf_compra,strlen ($nf_compra)-7);
        } else if(!in_array($login_fabrica, array(11,172))) {
            if($login_fabrica == 3){
                $nf_compra = $nf_compra;
            }else{
                if(strlen($nf_compra)<=6) {
                    $nf_compra = "000000" . $nf_compra;
                    $nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
                }
            }
        }
    }

    if($data_inicial && $data_final){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = traduz("data.invalida");

        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg_erro = traduz("data.invalida");

        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";

        if(strlen($msg_erro)==0){
            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
                $msg_erro = traduz("data.invalida");
            }
        }

    }else if(($data_inicial && !$data_final) || (!$data_inicial && $data_final)){
        $msg_erro = traduz("data.invalida");
    }

    $os_posto = trim (strtoupper ($_POST['os_posto']));
    if (strlen($os_posto)==0) $os_posto = trim(strtoupper($_GET['os_posto']));

    if (strlen($sua_os)==0 && strlen($rg_produto)==0 && strlen($serie)==0 && strlen($nf_compra)==0 && strlen($consumidor_cpf)==0 &&  strlen($consumidor_nome)==0 && strlen($produto_referencia)==0 && strlen($os_aberta)==0){

        if( ( empty($data_inicial) || empty($data_final ) ) && ( ($login_fabrica == 94 && strlen( trim($_POST['nome_tecnico'])) == 0 ) || $login_fabrica != 94 ) and strlen($msg_erro)==0){
            if ($login_fabrica == '35' and !empty($os_posto)) {
                $msg_erro = '';
            } else {
                $msg_erro = traduz("data.invalida");
            }
        }
        if($data_inicial && $data_final && $dash != 1){

            if(strlen($msg_erro)==0){
                if(in_array($login_fabrica, array(42,169,170))){
                    $qtd_mes = "";
                    if (in_array($login_fabrica,[169,170])) {
                        $data1 = new DateTime( "$aux_data_inicial 00:00:00" );
                        $data2 = new DateTime( "$aux_data_final 00:00:00" );
                        $intervalo2 = $data1->diff($data2);
                        $qtd_mes = $intervalo2->m;
                    }
                    if ($qtd_mes != 6) {
                        $meses = 6;
                        $sqlX = "SELECT '$aux_data_inicial'::date + interval '6 months' > '$aux_data_final 23:59:59'";

                        $resX = @pg_query($con,$sqlX);
                        $periodo_6meses = pg_fetch_result($resX,0,0);
                        
                        if($periodo_6meses == 'f'){
                            $msg_erro = traduz("O limite para a pesquisa é de 6 meses");
                        }
                    }
                }else{
                    $meses = ($login_fabrica == 166) ? 12 : 3;
                    $dias = ($login_fabrica == 166) ? "365" : "90";
                    if (strtotime($aux_data_inicial) < strtotime($aux_data_final . " -$meses month")) { //hd_chamado=2737551 alterando pesquisa p/ 90 dias
                        $msg_erro = traduz("Periodo não pode ser maior que $dias dias",$con,$cook_idioma);
                    }
                }

            }

        }

    }

    //HD-3073983
    if ($login_fabrica == 85 && isset($os_aberta) && strlen($os_aberta) > 0 && strlen($sua_os) == 0) {
        if (empty($data_inicial) && empty($data_final)) {
            $data_inicial =  date('d/m/Y', strtotime('-12 month', strtotime(date('Y-m-d'))));
            $data_final   = date('d/m/Y');

            list($di, $mi, $yi) = explode("/", $data_inicial);
            if(!checkdate($mi,$di,$yi))
            $msg_erro = traduz("data.invalida");

            list($df, $mf, $yf) = explode("/", $data_final);
            if(!checkdate($mf,$df,$yf))
            $msg_erro = traduz("data.invalida");

            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";
            $data_inicial = "";
            $data_final   = "";
        } else {
            $data1 = new DateTime( $aux_data_inicial );
            $data2 = new DateTime( date('Y-m-d'));

            $intervalo = $data1->diff( $data2 );
            if ($intervalo->y >= 1 && ($intervalo->m > 0 || $intervalo->d  > 0)) {
                $msg_erro = traduz("periodo.nao.pode.ser.maior.que.12.meses");
            }
        }
    }

    if ($login_fabrica == 42 && strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0) {
        $sqlDtI = "SELECT  current_date - interval '6 months' < '$aux_data_inicial 23:59:59'::date ";
        $resDtI = pg_query($con,$sqlDtI);
        $dtI = pg_fetch_result($resDtI,0,0);

        if ($dtI == 'f') {
            $msg_erro = traduz("O limite para a pesquisa é de 6 meses retroativo");
        }
    }

    if(strlen($posto_nome) > 0 AND strlen($posto_nome) < 4 ) {
        $msg_erro = traduz("digite.no.minimo.4.letras.para.o.nome.do.posto",$con,$cook_idioma);
    }

	if (strlen($sua_os) > 19) {
        $msg_erro = traduz("numero.de.os.invalida");
    }


    if (strlen($consumidor_nome) > 0 AND strlen($consumidor_nome) < 4) {
        $msg_erro = traduz("digite.no.minimo.4.letras.para.o.nome.do.consumidor",$con,$cook_idioma);
    }

    if (strlen($serie) > 0 AND strlen($serie) < 5) {
        $msg_erro = traduz("digite.no.minimo.5.letras.para.o.numero.de.serie",$con,$cook_idioma);
    }

    if($login_fabrica != 2){ // HD 81252
        if ( strlen ($os_posto) > 0 AND strlen ($os_posto) < 3) {
            $msg_erro = traduz("digite.no.minimo.3.digitos.para.os.posto");
        }
    }

    if($login_fabrica == 160 or $replica_einhell){
        $versao = $_POST["versao"];
    }

    if($login_fabrica == 164){
        $destinacao = $_POST["destinacao"];
    }

    if(strlen($msg_erro) == 0 && strlen($opcao2) > 0) {

        if(strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
        if(strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);
        if(strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
        if(strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);
        if(strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);

        if(strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
            $sql =  "SELECT tbl_posto.posto                ,
                            tbl_posto.nome                 ,
                            tbl_posto_fabrica.codigo_posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica USING (posto)
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                    AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
            $res = pg_query($con,$sql);
            if (pg_num_rows($res) == 1) {
                $posto        = trim(pg_fetch_result($res,0,posto));
                $posto_codigo = trim(pg_fetch_result($res,0,codigo_posto));
                $posto_nome   = trim(pg_fetch_result($res,0,nome));
            }else{
                $erro .= traduz("posto.nao.encontrado",$con,$cook_idioma);
            }
        }
    }
}

/*HD - 4206757*/
if($_POST['novo_fechar_os_cadence'] == 'true') {
    $aux_os  = $_POST["os"];

    try {
        if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
            include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
            $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
            $classOs = new $className($login_fabrica, $aux_os);
        } else {
            $classOs = new \Posvenda\Os($login_fabrica, $aux_os);
        }
        pg_query($con,"BEGIN");
            if (!empty($aux_os)) {
                $aux_sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP, data_fechamento= now() WHERE fabrica = $login_fabrica AND os = $aux_os";
                $aux_res = pg_query($con, $aux_sql);

                if (pg_last_error()) {
                    echo "KO|".traduz("erro.ao.fechar.a.o.s")." $aux_os";
                } else {
                    $aux_sql = "SELECT os , consumidor_celular FROM tbl_os_campo_extra join tbl_os using(os)  WHERE os = $aux_os LIMIT 1";
                    $aux_res = pg_query($con, $aux_sql);
                    $ver_os  = pg_fetch_result($aux_res, 0, 0);
                    $celular  = pg_fetch_result($aux_res, 0, 'consumidor_celular');
                    if (empty($ver_os)) {
                        $aux_admin["admin_finaliza_os"] = $cook_admin;
                        $aux_admin                      = json_encode($aux_admin);
                        
                        $aux_sql = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($aux_os, $login_fabrica, '$aux_admin') RETURNING campos_adicionais";
                    } else {
                        $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $aux_os";
                        $aux_res = pg_query($con, $aux_sql);

                        $aux_admin = pg_fetch_result($aux_res, 0, 0);
                        $aux_admin = (array) json_decode($aux_admin);
                        $aux_admin["admin_finaliza_os"] = $cook_admin;

                        $aux_admin = json_encode($aux_admin);
                        
                        $aux_sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$aux_admin' WHERE fabrica = $login_fabrica AND os = $aux_os RETURNING campos_adicionais";
                    }
                    $aux_res           = pg_query($con, $aux_sql);
                    $campos_adicionais = pg_fetch_result($aux_res, 0, 0);



                    if (empty($campos_adicionais)) {
                        echo "KO|".traduz("erro.ao.registrar.o.admin.responsavel.pelo.fechamento.da.o.s")." $os";
                        pg_query($con,"ROLLBACK");
                    } else {

                        $classOs->finaliza($con,false);
                		if(strlen(pg_last_error()) == 0){
                            gravaRespostaInitPesquisa($aux_os);
                            pg_query($con,"COMMIT");
							
							if(empty($cook_admin) and strlen(pg_last_error()) == 0){
								$sms     = new SMS();
								$sms_con = " ".pg_fetch_result($aux_res, 0, 'consumidor_nome');
								if ($login_fabrica == 35){
									$sms_msg = "Olá! Seu produto Cadence / Oster já está disponível para retirada na Assistência Técnica. Dúvidas: 0800 644 644 2.";
								}else{
									$sms_msg = "PREZADO(A)" . strtoupper($sms_con) . ", O SEU PRODUTO REFERENTE A ORDEM DE SERVIÇO NÚMERO $aux_os JÁ FOI REPARADO E ESTÁ DISPONÍVEL PARA RETIRADA NO POSTO AUTORIZADO.";
								}

								if(strlen($celular) >5) {	
									$sms->enviarMensagem($celular, $aux_os, '', $sms_msg);
								}
							}
                        	echo "OK|".traduz("a.os").". $aux_os ".traduz("foi.finalizada.com.sucesso");

                            # disparo de email no fechamento com pesquisa de satisfação #
                            // HD-7717990
                            if ($login_fabrica == 35 && 1==2) {

                                $sql_os = "SELECT o.os, 
                                                o.sua_os, 
                                                o.consumidor_nome, 
                                                o.consumidor_email, 
                                                pd.referencia, 
                                                pd.descricao, 
                                                o.fabrica, 
                                                posto.nome AS posto_autorizado,
                                                o.data_fechamento AS data_finalizacao
                                           FROM tbl_os o 
                                           JOIN tbl_os_produto op ON op.os = o.os 
                                           JOIN tbl_produto pd ON pd.produto = op.produto
                                           JOIN tbl_posto posto ON posto.posto = o.posto 
                                           WHERE o.os = $aux_os";

                                $os = pg_query($con, $sql_os);
                                $os = pg_fetch_object($os);

                                $sql = "SELECT pf.formulario, pf.pesquisa_formulario, p.descricao AS titulo, p.categoria, p.pesquisa, p.texto_ajuda AS texto_email
                                    FROM tbl_pesquisa p
                                    LEFT JOIN tbl_pesquisa_formulario pf ON (pf.pesquisa = p.pesquisa)
                                    WHERE p.fabrica = {$os->fabrica} AND p.categoria = 'os_email' AND p.ativo = 't'";
                                $pesquisa = pg_query($con, $sql);

                                if (pg_num_rows($pesquisa) > 0) {

                                    $pesquisa = pg_fetch_object($pesquisa);

                                    if (!empty($os->consumidor_email)) {

                                        $texto_email = $pesquisa->texto_email;

                                        if (preg_match('/\:os/', $texto_email)) {
                                            $texto_email = str_replace(':os', $os->sua_os, $texto_email);
                                        }

                                        if (preg_match('/\:finalizacao_os/', $texto_email)) {
                                            $texto_email = str_replace(':finalizacao_os', date("d/m/Y", strtotime($os->data_finalizacao)), $texto_email);
                                        }

                                        if (preg_match('/\:posto_autorizado/', $texto_email)) {
                                            $texto_email = str_replace(':posto_autorizado', $os->posto_autorizado, $texto_email);
                                        }
                                        
                                        if (preg_match('/\:nome_consumidor_os/', $texto_email)) {
                                            $texto_email = str_replace(':nome_consumidor_os', $os->consumidor_nome, $texto_email);
                                        }

                                        if (preg_match('/\:nome_consumidor_protocolo/', $texto_email)) {
                                            $texto_email = str_replace(':nome_consumidor_protocolo', "", $texto_email);
                                        }

                                        if (preg_match('/\:nome_produto_protocolo/', $texto_email)) {
                                            $texto_email = str_replace(':nome_produto_protocolo', "", $texto_email);
                                        }

                                        $token = sha1($os->fabrica . $os->os);

                                        if ($_serverEnvironment == 'development') {
                                            $url = "https://novodevel.telecontrol.com.br/~williamcastro/chamados/hd-6890195/externos/pesquisa_satisfacao_os_email.php?token={$token}&os={$os->os}&tipo=email";
                                        } else {
                                            $url = "https://posvenda.telecontrol.com.br/assist/externos/pesquisa_satisfacao_os_email.php?token={$token}&os={$os->os}&tipo=email";
                                        }
                                        
                                        $texto_email = str_replace(':link', "<a href='{$url}' target='_blank' >clique aqui</a>", $texto_email);

                                        $texto_email = str_replace("\n", '<br />', $texto_email);

                                        $email = $os->consumidor_email;

                                        $mailTc = new TcComm('cadence.telecontrol');
                                     
                                        try {
                                            
                                            $mailTc->sendMail(
                                                $email,
                                                utf8_encode($pesquisa->titulo),
                                                utf8_encode($texto_email),
                                                "pesquisa@jcsbrasil.com.br"
                                            );

                                        } catch (\Exception $e) {

                                            echo "KO|" . "Erro ao Enviar E-mail";
                                        }
                                    }
                                }
                            }
                                # --------------------------------------------------- #
                		}else{
                			echo "KO|".traduz("erro.ao.fechar.a.o.s")." $os";
                		}
            	    }
                }
            } else {
                echo "KO|".traduz("erro.ao.fechar.a.o.s")." $aux_os";
            }
    } catch(Exception $e) {
        $erro = utf8_decode($e->getMessage());
        echo "KO|".$erro;
    }

    exit;
}

if ($login_fabrica == 35) {
    require_once 'class/sms/sms.class.php';
}

if($_POST['enviar_sms_os'] == 'true') {
    $aux_os  = $_POST["os"];
    $aux_sql = "SELECT consumidor_celular, consumidor_nome, consumidor_revenda FROM tbl_os WHERE fabrica = $login_fabrica AND os = $aux_os";
    $aux_res = pg_query($con, $aux_sql);
    $celular = str_replace(array("(",")"," ","-"), "", pg_fetch_result($aux_res, 0, 'consumidor_celular'));
    $consumidor_revenda = pg_fetch_result($aux_res, 0, 'consumidor_revenda');

    $fones_invalidos = [
        "11111111111",
        "22222222222",
        "33333333333",
        "44444444444",
        "55555555555",
        "66666666666",
        "77777777777",
        "88888888888",
        "99999999999",
        "00000000000"
    ];

    if(in_array($celular,$fones_invalidos)){
        echo "KO|".traduz("a.os")." $os ".traduz("consumidor.nao.possui.um.celular.valido.para.enviar.sms")."|$consumidor_revenda|";
    }    

    if (empty($celular) ) {
        echo "KO|".traduz("a.os")." $os ".traduz("nao.possui.um.celular.vinculado.ao.consumidor")."|$consumidor_revenda";
    } else {
        $sms     = new SMS();
        $sms_con = " ".pg_fetch_result($aux_res, 0, 'consumidor_nome');
        if ($login_fabrica == 35){
            $sms_msg = "Olá! Seu produto Cadence / Oster já está disponí­vel para retirada na Assistência Técnica. Dúvidas: 0800 644 644 2.";   

            //Verifica se O.S tem atendimento callcenter e se o atendimento tem postagem
            //se tiver não pode enviar sms
            $enviarSMSCadence = true; 

            $sqlVerAtendimento = "SELECT tbl_hd_chamado_extra.os
                                    FROM tbl_hd_chamado_extra
                                    JOIN tbl_hd_chamado_postagem USING (hd_chamado)
                                    WHERE tbl_hd_chamado_extra.os = {$aux_os}";
            $resVerAtendimento = pg_query($con, $sqlVerAtendimento);
            if(pg_num_rows($resVerAtendimento)>0){
                $enviarSMSCadence = false;
            }
            if($enviarSMSCadence == true){
                $sms->enviarMensagem($celular, $aux_os, '', $sms_msg);
                echo "OK|".traduz("sms.enviado.com.sucesso");
            }
        }else{
            $sms_msg = "PREZADO(A)" . strtoupper($sms_con) . ", O SEU PRODUTO REFERENTE A ORDEM DE SERVIÇO NÚMERO $aux_os JÁ FOI REPARADO E ESTÁ DISPONÍVEL PARA RETIRADA NO POSTO AUTORIZADO.";
            $sms->enviarMensagem($celular, $aux_os, '', $sms_msg);
            echo "OK|".traduz("sms.enviado.com.sucesso");
        }        
    } 
    exit;
}

$layout_menu = "os";
$title = traduz("selecao.de.parametros.para.relacao.de.ordens.de.servicos.lancadas",$con,$cook_idioma);

include "cabecalho.php";

if (in_array($login_fabrica,array(94,141,144,156, 162, 164, 165, 167, 173, 177, 203))) {

    $sql = "SELECT  posto
            FROM    tbl_posto_fabrica
            JOIN    tbl_tipo_posto  ON  tbl_tipo_posto.tipo_posto   = tbl_posto_fabrica.tipo_posto
                                    AND tbl_tipo_posto.fabrica      = tbl_posto_fabrica.fabrica
                                    AND tbl_tipo_posto.posto_interno
            WHERE   tbl_posto_fabrica.fabrica   = " . $login_fabrica . "
            AND     tbl_posto_fabrica.posto     = " . $login_posto;
    $res = pg_query($con,$sql);

    if( pg_num_rows($res) ) {
        $posto_interno = TRUE;
    }

}
function verifica_tipo_posto($tipo, $valor) {
    global $con, $login_fabrica, $login_posto, $areaAdmin, $posto_id;

    if (empty($areaAdmin)) {
        $areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
    }

    $id_posto  = ($areaAdmin == true) ? $posto_id : $login_posto;
    $sql = "
        SELECT tbl_tipo_posto.tipo_posto
        FROM tbl_posto_fabrica
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
        AND tbl_posto_fabrica.posto = {$id_posto}
        AND tbl_tipo_posto.{$tipo} IS {$valor}
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }
}

function gravaRespostaInitPesquisa($os) {
    global $con, $login_fabrica, $login_admin;

    $sqlPesquisaSa = "SELECT o.os
                        FROM tbl_os o
                        JOIN tbl_resposta r ON r.os = o.os
                        JOIN tbl_pesquisa_formulario pf ON pf.pesquisa_formulario = r.pesquisa_formulario
                        JOIN tbl_pesquisa p ON p.pesquisa = r.pesquisa
                       WHERE o.os = {$os}
                         AND p.categoria='os'
                         AND p.ativo IS TRUE
                         AND p.fabrica={$login_fabrica}";
    $resPesquisaSa = pg_query($con, $sqlPesquisaSa);

    if (pg_num_rows($resPesquisaSa) == 0) {

        $sqlx = "SELECT tbl_pesquisa.pesquisa, 
                        tbl_pesquisa_formulario.pesquisa_formulario
                   FROM tbl_pesquisa
                   JOIN tbl_pesquisa_formulario ON tbl_pesquisa_formulario.pesquisa=tbl_pesquisa.pesquisa  AND tbl_pesquisa_formulario.ativo IS TRUE
                  WHERE tbl_pesquisa.categoria='os'
                    AND tbl_pesquisa.ativo IS TRUE
                    AND tbl_pesquisa.fabrica={$login_fabrica}";
        $resx = pg_query($con, $sqlx);

        if (pg_num_rows($resx) > 0) {
            $xpesquisa = pg_fetch_result($resx, 0, 'pesquisa');
            $xpesquisa_formulario = pg_fetch_result($resx, 0, 'pesquisa_formulario');

            $sqly = " INSERT INTO tbl_resposta (
                                    pesquisa_formulario,
                                    os,
                                    pesquisa,
                                    data_input,
                                    sem_resposta
                                ) VALUES (
                                    $xpesquisa_formulario,
                                    $os,
                                    $xpesquisa,
                                    CURRENT_TIMESTAMP,
                                    TRUE
                                )";
            $resy = pg_query($con, $sqly);
            if (pg_last_error($con)){
                return false;
            }
            return true;
        }
    }
}
?>

<style type="text/css">
.Titulo {
    text-align: center;
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
}
.Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
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
}


.msg_erro{
    background-color:#FF0000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.subtitulo{

    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.informacao{
    font: 14px Arial; color:rgb(89, 109, 155);
    background-color: #C7FBB5;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.espaco{
    padding-left:80px;
    width: 220px;
}
</style>


<style type="text/css">
    .status_checkpoint{width:9px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
    .status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;}

    .legenda_os_cor{width:75px;height:15px;border:1px solid #666;margin:2px 5px;padding:0 5px;}
    .legenda_os_texto{margin:2px 5px;padding:0 5px;font-weight: bold;}

    #dlg_motivo {
        display: none;
        position: fixed;
        text-align: left;
        top:   30%;
        left:  30%;
        width: 40%;
        height:30%;
        padding-top: 32px;
        border: 2px solid #999999;
        background-color: #FFFFFF;
        border-radius: 8px;
        -moz-border-radius: 8px;
        -webkit-border-radius: 8px;
        overflow: hidden;
        z-index: 100;
    }

    #dlg_motivo #motivo_header {
        position: absolute;
        top:    0;
        left:   0;
        margin: 0;
        width: 100%;
        height: 20px;
        text-align: center;
        background-color: #596D9B;
        padding: 2px 1em;
        color: #FFFFFF;
        font-size: 12px;
        font-weight: bold;
    }
    #dlg_motivo #motivo_container {
        margin: 0;
        padding: 20px 2em;
        overflow-y: auto;
        overflow-x: hidden;
        height: 100%;
        background-color: #FFFFFF;
        color: #000000;
    }
    #dlg_motivo #dlg_fechar {
        position: absolute;
        top: 3px;
        right: 5px;
        width: 16px;
        height:16px;
        font: normal bold 12px Verdana, Arial, Helvetica, sans-serif;
        color:white;
        cursor: pointer;
        margin:0;padding:0;
        vertical-align:top;
        text-align:center;
        color: #FFFFFF;
        background-color: #FF0000;
    }
    #dlg_motivo button {
        float: right;
        margin: 5px;
    }
    #dlg_motivo input {
        display: block;
        width: 100%;
    }

    #dlg_motivo_sms {
        display: none;
        position: fixed;
        text-align: left;
        top:   30%;
        left:  30%;
        width: 40%;
        height:30%;
        padding-top: 32px;
        border: 2px solid #999999;
        background-color: #FFFFFF;
        border-radius: 8px;
        -moz-border-radius: 8px;
        -webkit-border-radius: 8px;
        overflow: hidden;
        z-index: 100;
    }

    #dlg_motivo_sms #motivo_header_sms {
        position: absolute;
        top:    0;
        left:   0;
        margin: 0;
        width: 100%;
        height: 20px;
        text-align: center;
        background-color: #596D9B;
        padding: 2px 1em;
        color: #FFFFFF;
        font-size: 12px;
        font-weight: bold;
    }
    #dlg_motivo_sms #motivo_container_sms {
        margin: 0;
        padding: 20px 2em;
        overflow-y: auto;
        overflow-x: hidden;
        height: 100%;
        background-color: #FFFFFF;
        color: #000000;
    }
    #dlg_motivo_sms #dlg_fechar_sms {
        position: absolute;
        top: 3px;
        right: 5px;
        width: 16px;
        height:16px;
        font: normal bold 12px Verdana, Arial, Helvetica, sans-serif;
        color:white;
        cursor: pointer;
        margin:0;padding:0;
        vertical-align:top;
        text-align:center;
        color: #FFFFFF;
        background-color: #FF0000;
    }
    #dlg_motivo_sms button {
        float: right;
        margin: 5px;
    }
    #dlg_motivo_sms input {
        display: block;
        width: 100%;
    }

    table.tabela tbody tr td{
        padding:0 5px 0 5px;
    }

    #imprimir_varios{
        font-size: 12px;
        padding-top: 15px;
    }
</style>

<script>

function anexar_nf(os){

    var fabrica = "<?=$login_fabrica?>";
    if (os != ''){
        Shadowbox.open({
            content :   "upload_nf.php?fabrica="+fabrica+"&os="+os,
            player  :   "iframe",
            title   :   "<?php fecho('Upload.NF', $con, $cook_idioma);?>",
            width   :   800,
            height  :   200
        });
    }else
        alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");

}

function AgendarVisita(os){
    if (os != ''){
        Shadowbox.open({
            content :   "agendar_visita_os.php?os="+os,
            player  :   "iframe",
            title   :   "<?php fecho('agendar.visita', $con, $cook_idioma);?>",
            width   :   800,
            height  :   300
        });
    }    
}

function verifica_protocolo(url,valor) {
    var dec_valor;
    $().ready(function() {
        dec_valor = $.base64Decode(valor);
    });

    var num_protocolo = prompt("Informe o número do protocolo?", "")
    if(num_protocolo == null){
        return false;
    }else{
        if(num_protocolo == dec_valor){
            window.location.href = url;
        }else{
            alert("Protocolo '"+num_protocolo+"' Inválido.")
            return false;
        }
    }
}

function url(n){

    var url = $('#url_'+n).val();

    window.location.href = url;

}

</script>
<style type="text/css">
    @import "plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<link href="plugins/font_awesome/css/font-awesome.css" type="text/css" rel="stylesheet" />
<? // include "javascript_pesquisas.php"; ?>
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script> -->
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<? // include "javascript_calendario_new.php"; ?>
<script src="js/jquery-ui.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />
<script type="text/javascript" src="js/jquery.tooltip.min.js"></script>
<script type="text/javascript" src="js/jquery.base64.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<?/*    MLG 23/03/2010 - HD 205816 - Refiz o 'prompt' para evitar (novidade...) problemas com usuários do MSIE... */?>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script language='javascript'>

<?php if ($login_fabrica == 156 and true === $posto_interno): ?>
function fnc_pesquisa_posto(codigo, nome) {
	var codigo = jQuery.trim(codigo.value);
	var nome   = jQuery.trim(nome.value);

	if (codigo.length > 2 || nome.length > 2){
		Shadowbox.open({
			content:	"admin/posto_pesquisa_2_nv.php?codigo=" + codigo + "&nome=" + nome,
			player:	"iframe",
			title:		"Pesquisa Posto",
			width:	800,
			height:	500
		});
	}else{
		alert("<?php echo traduz("preencha.toda.ou.parte.da.informacao.para.realizar.a.pesquisa");?>");
	}
}

function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
    gravaDados("codigo_posto_externo",codigo_posto);
    gravaDados("posto_nome_externo",nome);
}

function gravaDados(name, valor){
    try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}
<?php endif ?>

function verificaNumero(e) {
    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
        return false;
    }
}

var exigir_motivo = "<?php echo ($login_fabrica != 1) ? 'ok' :''; ?>";

var aguarde_sub = false;

$(document).ready(function() {
    $('#data_inicial').datepick({startDate:'01/01/2000'});
    $('#data_final').datepick({startDate:'01/01/2000'});
    $('input[name^=data_fechamento_]').datepick({startDate:'01/01/2000'});
    $("#data_inicial").maskedinput("99/99/9999");
    $("#data_final").maskedinput("99/99/9999");
    $("input[name^=data_fechamento_]").maskedinput("99/99/9999");

    /*HD - 4373381*/
    $("#consumidor_cpf").click( function() {
        $("#consumidor_cpf").unmask();
    });

    $("#consumidor_cpf").blur(function(){
        $("#consumidor_cpf").unmask();
        var tamanho = $("#consumidor_cpf").val().length;

        if(tamanho <= 11){
            $("#consumidor_cpf").maskedinput("999.999.999-99");
        } else if(tamanho > 11){
            $("#consumidor_cpf").maskedinput("99.999.999/9999-99");
        }                   
    });

    <?php if($login_fabrica == 164){ ?>

        $("#cep").maskedinput("99999-999");
        $("#cep_pre_os").maskedinput("99999-999");
        $("#cpf_pre_os").maskedinput("999.999.999-99");

    <?php } ?>

    $("#protocolo_atendimento").keypress(verificaNumero);

    Shadowbox.init();
    $("button.lancar_observacao").click(function(){
        var os = $(this).parents("tr").find("input[name=numero_os]").val();

        Shadowbox.open({
            player: "iframe",
            content: "lancar_observacao.php?os="+os,
            title: '<?php echo traduz("lancar.observacao");?>',
            height: 200,
            width: 500
        });
    });

    soNumero($('#nf_compra'));
    soNumero($('#consumidor_cpf'));
    soNumero($('#revenda_cnpj'));
    //HD 371911
    $('.selecionaTodos').click(function(){
        if($(this).is(':checked')){
            $('.imprimir').attr('checked',true);
        }else{
            $('.imprimir').attr('checked',false);
        }
    });

    var selecionado = false;

    $('#imprimir_botao').click(function(){

        selecionado = false;

        $('#imprimir_varios input[type=hidden]').remove();

        $('.imprimir').each(function(){
            if($(this).is(':checked')){
                selecionado = true;
                //Insere no formulário de impressão de vários os í­tens selecionados
                $('#imprimir_varios').append('<input type="hidden" value="'+$(this).val()+'" name="imprime_os[]" checked="checked" />');
            }
        });

        if(selecionado){
            if(confirm('<?php echo traduz("deseja.mesmo.imprimir.todas.as.os.selecionadas");?>')){
                $('#imprimir_varios').submit();
            }
        }else{
            $('#erro_imprimir').fadeIn();
            setTimeout("$('#erro_imprimir').fadeOut()",5000);
        }
    });

});

   function pesquisaProduto(campo, tipo){
        var campo   = jQuery.trim(campo.value);

        var fabrica = "<?=$login_fabrica?>";
		var extra;
		<? if (in_array($login_fabrica, array(11,172))) { ?>
            extra = "l_mostra_produto=ok";
		<? } ?>

        if (campo.length > 2){
            Shadowbox.open({
                content :   "produto_pesquisa_2_nv.php?"+tipo+"="+campo+"&"+extra,
                player  :   "iframe",
                title   :   "<?php fecho('pesquisa.de.produto', $con, $cook_idioma);?>",
                width   :   800,
                height  :   500
            });
        }else
            alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
    }

    function retorna_dados_produto(produto,linha,descricao,nome_comercial,voltagem,referencia,referencia_fabrica,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao){
        gravaDados("produto_referencia",referencia);
        gravaDados("produto_descricao",descricao);
        gravaDados("produto_voltagem",voltagem);
    }


    function gravaDados(name, valor){
        try {
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }

var exigir_motivo = "<?php echo ($login_fabrica != 1) ? 'ok' :''; ?>";

var aguarde_sub = false;

$().ready(function() {
    $(".btn-fechar-os").click(function (){
        var posicao = $(this).data('posicao');
        var texto = $(this).data('texto');
        var shadowbox = $(this).data('shadowbox');
        var os = $(this).data('os');
        if (confirm(texto) == true) { 
            shadowbox_instalacao_louca(os, shadowbox, posicao);
        };
    });

    $(".btn-fechar-os-checklist").click(function (){
        let os = $(this).data('os');
        alert(`A OS ${os} não pode ser fechada, necessario informar o checklist`)
    });

    $('p[id^=excluir],img[id^=excluir]').css('cursor','pointer').click(function () {
        var os_sua_os;
        if ($(this).attr('alt') !='') os_sua_os = $(this).attr('alt').split(',');
        var os = os_sua_os[0];
        var sua_os = os_sua_os[1];

        if (confirm('<?=traduz("deseja.realmente.excluir.a.os",$con)?> (OS nº '+sua_os+')') == false) return false;

        if (exigir_motivo != "") {
            $('#dlg_motivo #motivo_os').text(sua_os).attr('alt',os);
            $('#dlg_motivo').show('fast');
        } else {

            if(aguarde_sub == true){
                alert('<?=traduz("aguarde.a.submissao");?>');
                return;
            }

            aguarde_sub = true;

            window.location='<?=$PHP_SELF?>?excluir='+os;
        }
    });

    $('#dlg_motivo #dlg_fechar,#dlg_motivo #dlg_btn_cancel').click(function () {
        $('#dlg_motivo input').val('');
        $('#dlg_motivo').hide('fast');
    });

    $('#dlg_motivo #dlg_btn_excluir').click(function () {
        var str_motivo = $.trim($('#dlg_motivo input').val());
        var os = $('#dlg_motivo #motivo_os').attr('alt');
        if (str_motivo != '') {

            if(aguarde_sub == true){
                alert('<?=traduz("aguarde.a.submissao");?>');
                return;
            }

            aguarde_sub = true;

            $.get('grava_obs_excluida.php',
                 {'motivo':str_motivo,'os':os},
                 function(resposta) {
                    if (resposta == 'ok') {
                        var os = $('#dlg_motivo #motivo_os').text();
                        $('#exclusao').show();
                        setTimeout(function(){
                            $('#dlg_motivo').hide('fast');
                            $('#dlg_motivo input').val('');
                            $('#exclusao').hide(); //hd_chamado=2904468
                        },2000);
                        // window.location='<?=$PHP_SELF?>';
                        $('#conteudo_'+os).remove(); /* Alterado para remover a linha da OS, e não atualizar a tela de consulta */
                        aguarde_sub = false;//hd_chamado=2904468
                    } else {
                        $('#dlg_motivo').hide('fast');
                        aguarde_sub = false; //hd_chamado=2904468
                        alert(resposta);
                        return false;
                    }
            });//END of GET
        } else {
            alert('<?=traduz("digite.um.motivo.ou.cancele.a.exclusao");?>');
        }
    });

});
    <?php
    if (in_array($login_fabrica,array(30))) {
    ?>

    function cancelarOs(os,acao)
    {
        var motivo;

        if(acao == "cancelar"){
            motivo = prompt("Digite o motivo do cancelamento da OS");
        }else{
            motivo = prompt("Digite o motivo da reabertura da OS");
        }

        if (motivo.length > 0) {
            $.ajax({
                url:"<?=$PHP_SELF?>",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:"cancelar_os",
                    os:os,
                    acao:acao,
                    motivo:motivo
                }
            })
            .done(function(data){
                if(data.result == "ok"){
                    if (acao == "liberar" || acao == "reabrir") {
                        alert("OS reaberta com sucesso");
                    } else {
                        alert("OS cancelada com sucesso");
                    }
                    $("#td_excluir_"+os).html("");
                }
            })
            .fail(function(){
                alert("Não foi possí­vel realizar a operação.");
            });
        } else {
            if (acao == "cancelar") {
                alert("Por favor informar o motivo para o cancelamento da OS. ");
            } else {
                alert("Por favor informar o motivo para a reabertura da OS. ");
            }
        }
    }

    <?
    }
    ?>

   function pesquisaProduto(campo, tipo){
        var campo   = jQuery.trim(campo.value);

        var fabrica = "<?=$login_fabrica?>";
		var extra
		<? if (in_array($login_fabrica, array(11,172))) { ?>
            extra = "l_mostra_produto=ok";
		<?	} ?>

        if (campo.length > 2){
            Shadowbox.open({
                content :   "produto_pesquisa_2_nv.php?"+tipo+"="+campo+"&"+extra,
                player  :   "iframe",
                title   :   "<?php fecho('pesquisa.de.produto', $con, $cook_idioma);?>",
                width   :   800,
                height  :   500
            });
        }else
            alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
    }

    function retorna_dados_produto(produto,linha,descricao,nome_comercial,voltagem,referencia,referencia_fabrica,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao){
        gravaDados("produto_referencia",referencia);
        gravaDados("produto_descricao",descricao);
        gravaDados("produto_voltagem",voltagem);
    }


    function gravaDados(name, valor){
        try {
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }



    /* HD 133499
    function disp_prompt(os, sua_os){
        var motivo =prompt("Qual o Motivo da Exclusão da os "+sua_os+" ?",'',"Motivo da Exclusão");
        if (motivo !=null && $.trim(motivo) !="" && motivo.length > 0 ){
            var resultado = $.ajax({
                type: "GET",
                url: 'grava_obs_excluida.php',
                data: 'motivo=' + motivo + '&os=' + os,
                cache: false,
                async: false,
                complete: function(resposta) {
                    verifica_res = resposta.responseText;
                    if (verifica_res =='ok'){
                        return true;
                    }
                }
             }).responseText;

            if (resultado =='ok'){
                return true;
            }else{
                alert(resultado,'Erro');
            }
        }else{
            alert('Digite um motivo por favor!','Erro');
            return false;
        }
    }
*/
function DataHora(evento, objeto){
    var keypress=(window.event)?event.keyCode:evento.which;
    campo = eval (objeto);
    if (campo.value == '00/00/0000')
    {
        campo.value=""
    }

    caracteres = '0123456789';
    separacao1 = '/';
    separacao2 = ' ';
    separacao3 = ':';
    conjunto1 = 2;
    conjunto2 = 5;
    conjunto3 = 10;
    conjunto4 = 13;
    conjunto5 = 16;
    if ((caracteres.search(String.fromCharCode (keypress))!=-1) && campo.value.length < (19))
    {
        if (campo.value.length == conjunto1 )
        campo.value = campo.value + separacao1;
        else if (campo.value.length == conjunto2)
        campo.value = campo.value + separacao1;
        else if (campo.value.length == conjunto3)
        campo.value = campo.value + separacao2;
        else if (campo.value.length == conjunto4)
        campo.value = campo.value + separacao3;
        else if (campo.value.length == conjunto5)
        campo.value = campo.value + separacao3;
    }
    else
        event.returnValue = false;
}

function soNumero(campo){
    $(campo).keypress(function(e) {
        var c = String.fromCharCode(e.which);
        var allowed = '1234567890-/.';
        if ((e.keyCode != 9 && e.keyCode != 8) && allowed.indexOf(c) < 0) return false;
    });
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
function _trim (s)
{
   //   /            open search
   //     ^            beginning of string
   //     \s           find White Space, space, TAB and Carriage Returns
   //     +            one or more
   //   |            logical OR
   //     \s           find White Space, space, TAB and Carriage Returns
   //     $            at end of string
   //   /            close search
   //   g            global search

   return s.replace(/^\s+|\s+$/g, "");
}

function retornaFechamentoOS (http , sinal, excluir, lancar,os) {

    if (http.readyState == 4) {

        if (http.status == 200) {
            results = http.responseText;

            <?php if ($login_fabrica == '132'): ?>
                var ret = http.responseText.split(';');

                if (ret[0] == 'erro') {
                    var erro = ret[1].split('====');
                    results = erro[1].replace('ERROR: ', '');
                }
                else if (ret[0] == 'ok') {
                    results = 'OS fechada com sucesso';
                }

                alert(results); return;
            <?php endif ?>

            results = http.responseText.split(";");

            if (typeof(results[0]) != 'undefined') {

                if (_trim(results[0]) == 'ok') {

                    alert ('OS <? fecho("fechada.com.sucesso",$con,$cook_idioma) ?>');

                    $(sinal).hide();
                    $(excluir).hide();
                    $(lancar).hide();

                  <?
                    if($login_fabrica == 117){ ?>

                        var a = $("<a>").attr({
                            href:"os_item.php?os="+os+"&reabrir=ok"
                        });

                        var img = $("<img>").attr({
                            border:'0',
                            src:"imagens/btn_reabriros.gif",
                            style:"display:block;"
                        });

                        a.append(img);

                        $(excluir).parents("tr").find("td[rel=td_reabrir_os]").append(a);
                        $("span[class=status_checkpoint]").attr("style","background-color:#8DFF70");
                  <?  } ?>
                } else {

                    if (http.responseText.indexOf ('de-obra para instala') > 0) {

                        alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.instalacao",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {

                        alert ('<? fecho("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('o-de-obra para atendimento') > 0) {

                        alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.este.atendimento",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {

                        alert ('<? fecho("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {

                        alert ('<? fecho("type.informado.para.o.produto.nao.e.valido",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('OS com peças pendentes') > 0) {

                        alert ('<? fecho("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('OS não pode ser fechada, Favor Informar a Kilometragem') > 0) {

                        alert ('<? fecho("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('OS não pode ser fechada, Kilometragem Recusada') > 0) {

                        alert ('<? fecho("os.nao.pode.ser.fechada,.kilometragem.recusada",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('em intervenção') > 0) {

                        alert ('<? fecho("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma) ?>');

                    }
                    else if (http.responseText.indexOf ('OS não pode ser fechada, aguardando aprovação de Kilometragem') > 0) {

                        alert ('<? fecho("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('Esta OS teve o número de série recusado e não pode ser finalizada') > 0) {

                        <? if ($login_fabrica == 50) { ?>
                            alert ('<? fecho("esta.os.teve.o.numero.de.serie.recusado.ou.esta.em.intervencao.e.nao.pode.ser.finalizada",$con,$cook_idioma) ?>');
                        <? } else { ?>
                            alert ('<? fecho("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada",$con,$cook_idioma) ?>');
                        <? } ?>

                    } else if (http.responseText.indexOf ('Informar defeito constatado (Reparo) para OS') > 0) {

                        alert ('<? fecho("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens", $con, $cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('Por favor, informar o conserto do produto na tela CONSERTADO') > 0) {

                        alert ('<? fecho("por.favor.informar.o.conserto.do.produto.na.tela.consertado",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('Favor informar solução tomada para a ordem de serviço') > 0) {

                        alert ('<? fecho("oss.sem.solucao.e.sem.itens.lancados",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('Favor informar o defeito constatado para a ordem de serviço') > 0) {

                        alert ('<? fecho("oss.sem.defeito.constatado",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('em intervenção por número de Série') > 0) {

                        alert ('<? fecho("em.intervencao.por.numero.de.serie",$con,$cook_idioma) ?>');

                    } else if (http.responseText.indexOf ('informe o motivo de atraso de fechamento na aba')>0) {

                        alert ('<?php echo traduz("favor.informar.o.motivo.de.atraso.de.fechamento.na.aba.o.servico");?>');

                    } else if (http.responseText.indexOf('OS não pode ser finalizada pois esta OS encontra-se em auditoria') > 0) {

                        alert ('<?php echo traduz("os.nao.pode.ser.finalizada.pois.esta.os.encontra.se.em.auditoria");?>');

                    } else if (http.responseText.indexOf('A OS não pode ser fechada, pois o pedido de peça está pendente') > 0) {

                        alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.o.pedido.de.peca.esta.pendente");?>');

                    } else if (http.responseText.indexOf('pois não há pedido gerado') > 0) {

                        alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.nao.ha.pedido.gerado");?>');

                    }else if (http.responseText.indexOf('pois pedido não foi faturado') > 0) {

                        alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.pedido.nao.foi.faturado");?>');

                    }else if (http.responseText.indexOf('pois pedido foi faturado a menos de sete dias') > 0) {

                        alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.pedido.foi.faturado.a.menos.de.sete.dias");?>');

                    }else if (http.responseText.indexOf('OS com pedido de peças Pendentes') > 0 || http.responseText.indexOf('pois o pedido de pe') > 0 ) {

                        alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.o.pedido.de.peca.esta.pendente");?>');

                    } else if (http.responseText.indexOf('Para fechar a OS') > 0) {

                        alert('<?php echo traduz("para.fechar.a.os.e.necessario.lancar.peca.e.o.servico.realizado");?>');

                    } else if (http.responseText.indexOf('Esta OS está em intervenção e não pode ser finalizada.') > 0) {

                        alert('<?php echo traduz("esta.os.esta.em.intervencao.e.nao.pode.ser.finalizada");?>');

                    } else if ((http.responseText.indexOf('número de Série do produto') > 0 || http.responseText.indexOf('Favor digitar o número de Série do produto na Ordem') > 0) && http.responseText.indexOf('anexo') == 0) {
                        alert('<? fecho("favor.digitar.o.numero.de.serie.na.ordem.de.servico",$con,$cook_idioma) ?>');

                    }else if (http.responseText.indexOf('aberta a mais de 48 horas') > 0) {

                        alert('<?php echo traduz("os.aberta.a.mais.de.48.horas.sem.motivo.do.atraso.informado");?>');

                        var motivo = document.getElementById('motivo_atraso_fechamento_'+os);
                        if(motivo.style.display == "none"){
                            motivo.style.display = "block";
                        }
                    } else if (http.responseText.indexOf('Informe a data de conserto para a OS') > 0) {

                        alert('<?php echo traduz("informe.a.data.de.conserto.da.os");?> : '+os);

                    } else if (http.responseText.indexOf('Favor entrar em contato com a') > 0 || http.responseText.indexOf('da reincid') > 0) {

                        alert('<?php echo traduz("esta.os.esta.em.aprovacao.de.reincidencia.e.nao.pode.ser.finalizada.favor.entrar.em.contato.com.a.fabrica");?>');

                    } else if (http.responseText.indexOf('informe a data de conserto para a OS') > 0 || http.responseText.indexOf('da reincid') > 0) {

                        alert('<?php echo traduz("informe.a.data.de.conserto.para.a.os");?>');

                    } else if (results[0] == 'nome do tecnico') {

                        alert('<?php echo traduz("informe.o.nome.do.tecnico.para.a.os");?>');

                    }else if(results[0] == "erro") {

                        var msg = http.responseText.split(";");
                        console.log(msg);
                        alert (msg[1]);

                    } else if (http.responseText.indexOf ('preencher o Check List') > 0) {
                        alert ('<?php echo traduz("para.finalizar.a.os.e.preciso.preencher.o.check.list");?>');
                    } else{
						if(http.responseText.indexOf('ERROR:') > 0 ) {
							var msg_erro = http.responseText.split('ERROR:');

							msg_erro = msg_erro[1].split('CONTEXT:');
							alert(msg_erro[0]);
						}else{
							alert(http.responseText);
						}

                    }

                }

            } else {

                alert ('<? fecho("fechamento.nao.processado",$con,$cook_idioma) ?>');

            }

        }

    }

}

function fechaOSnovo(linha) {


div = document.getElementById('div_fechar_'+linha);

div.style.display='block';

}

function retornaFechamentoOS2(http,sinal,excluir,lancar,linha,div_anterior) {
    var div;
    div = document.getElementById('div_fechar_'+linha);
    if (http.readyState == 4) {
        if (http.status == 200) {
            results = http.responseText.split(";");
            if (typeof (results[0]) != 'undefined'){
                if (_trim(results[0]) == 'ok') {
                    sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
                    sinal.src='/assist/imagens/pixel.gif';
                    excluir.src='/assist/imagens/pixel.gif';
                    div.style.display='none';
                    if(lancar){
                        lancar.src='/assist/imagens/pixel.gif';
                    }
                    alert('<? fecho("os.fechada.com.sucesso",$con,$cook_idioma) ?>');
                }
                else {
                    var msg = _trim(results[5]);
                    alert(msg);
                    div.innerHTML = div_anterior;
                    }
            }
        }
    }
}


function fechaOSnovo2(os,data,lancar,linha) {
    //$login_fabrica == 20
    var data_fechamento = data;
    var div = document.getElementById('div_fechar_'+linha);
    var divmostrar = document.getElementById('mostrar_'+linha);
    var sinal = document.getElementById('sinal_'+linha);
    var excluir = document.getElementById('excluir_'+linha);
    if(lancar){
        lancar = document.getElementById("lancar_"+linha);
    }
    var hora;
    var div_anterior;
    hora = new Date();


    div.style.display = "none";
    divmostrar.innerHTML = "<img src='admin/a_imagens/ajax-loader.gif'>"
    divmostrar.style.display = "block";

    var url = "ajax_fecha_os.php?fecharnovo=sim&os=" + escape(os) + '&data_fechamento='+data+'&cachebypass='+hora.getTime();
    var fecha = $.ajax({
                    type: "GET",
                    url: url,
                    cache: false,
                    async: false
     }).responseText;

    var fecha_array = 0;
    fecha_array = fecha.split(";");

        if (fecha_array[0]=='ok') {
            //sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
            sinal.src='imagens/pixel.gif';

            if(excluir){
                excluir.src='imagens/pixel.gif';
            }

            div.style.display='none';
            if(lancar){
                lancar.src='/assist/imagens/pixel.gif';
            }
            alert('<? fecho("os.fechada.com.sucesso",$con,$cook_idioma) ?>');
            divmostrar.style.display = "none";
            $('input[name=btn_acao]').click();

        }else{
            var msg               = fecha_array[1];
            if (msg == 'tbl_os&quot') {
                alert('<? fecho("por.favor.confira.a.data.digitada",$con,$cook_idioma) ?>');
            }
            if (msg.indexOf('fn_finaliza_os')!=-1) {
                alert('<? fecho("por.favor.confira.se.os.dados.na.tela.de.lancamento.de.itens.foram.preenchidos",$con,$cook_idioma) ?>');
            }else{
                var msg               = fecha_array[1];
                alert('<? fecho("por.favor.confira.a.data.digitada",$con,$cook_idioma) ?>');
            }

            divmostrar.style.display = "none";
            div.style.display = "block";
            $('#ajax_'+linha).val(fecha);
        }
}

function reabrirOS(os){

	$.ajax({
		url: "os_consulta_lite.php",
		type: "GET",
		data: {ajax_reabrir : 'sim', os : os},
		complete: function(data){
			var retorno = data.responseText;

			if(retorno == ""){
				window.location = 'cadastro_os.php?os_id='+os;
			}else{
				alert(retorno);
			}
		}
	});

}
function alertOSconfirmeLorenzetti(os , sinal , excluir , lancar, posicao) {
    fechaOS(os, document.getElementById('sinal_' + posicao), document.getElementById('excluir_' + posicao), document.getElementById('lancar_' + posicao), 0, posicao);
    fecharShadowbox();    
}
function fecharShadowbox(){
    Shadowbox.init();
    Shadowbox.close();
}

<?php if ($login_fabrica == 19): ?>
function shadowbox_instalacao_louca(os, conserto, posicao) {
    if (conserto == "928-15" || conserto == "928-16" || conserto == "20" || conserto == "928-26"){
        Shadowbox.init();
        Shadowbox.open({
            player: "iframe",
            content: "shadowbox_instalacao_louca.php?os="+os+"&posicao="+posicao,
            height: 300,
            width: 600
        });
    } else {
        fechaOS(os, document.getElementById('sinal_' + posicao), document.getElementById('excluir_' + posicao), document.getElementById('lancar_' + posicao), conserto, posicao);
    }

    return;
}
<?php endif ?>

function confirmaRecebimentoPeca(os, sinal, excluir, lancar, conserto, posicao){
    var login_fabrica = <?=$login_fabrica;?>;    

    if(login_fabrica == 131){
        Shadowbox.init();
        Shadowbox.open({
            player: "iframe",
            content: "confirma_recebimento_peca.php?os="+os,
            height: 220,
            width: 800,
            options: {
                modal: true,
                enableKeys: false,
                displayNav: false
            }
        });        
    }    

}

function fechaOS (os , sinal , excluir , lancar, conserto, posicao ) {
    /**
     * - Método antigo, passando para AJAX com jQuery
     */
    var curDateTime = new Date();
    var login_fabrica = <?=$login_fabrica;?>;

    $.ajax({
        url:"<?=$PHP_SELF?>",
        type:"GET",
        data:{
            fechar:os,
            dt:curDateTime
        }
    })
    .done(function(data){
        if ((login_fabrica == 123 || login_fabrica == 160 || login_fabrica == 188) && data == 'erro_termo') {
            alert ('Anexar o termo de retirada');
            return;
        }

        if(login_fabrica == 132){
            var ret = data.split(';');
            if (ret[0] == 'erro') {
                var erro = ret[1].split('====');
                results = erro[1].replace('ERROR: ', '');
            }else if (ret[0] == 'ok') {
                results = '<?php echo traduz("os.fechada.com.sucesso");?>';
            }
            alert(results);
            return;
        }

        if(login_fabrica == 153 || login_fabrica == 160 || login_fabrica == 164){
            var ret = data.split(';');
            if (ret[0] == 'erro') {
                results = ret[1];
                alert(results);
                return;
            }
        }

        results = data.split(";");

        if (typeof(results[0]) != 'undefined') {
            if (_trim(results[0]) == 'ok') {
                
                if (results[1].match(/^XX/)) {                    
                    alert('OS <? fecho("fechada.com.sucesso",$con,$cook_idioma) ?>');
                } else {
                    alert(results[1]);
                }

                //$(sinal).hide();
                //$(excluir).hide();
                //$(lancar).hide();
                //$(conserto).hide();

                $(sinal).parents('td').hide();
                $(excluir).parents('td').hide();
                $(lancar).parents('td').hide();
                $(conserto).parents('td').hide();

                if(login_fabrica == 117){

                    var a = $("<a>").attr({
                        href:"os_item.php?os="+os+"&reabrir=ok"
                    });

                    var img = $("<img>").attr({
                        border:'0',
                        src:"imagens/btn_reabriros.gif",
                        style:"display:block;"
                    });

                    a.append(img);

                    $(excluir).parents("tr").find("td[rel=td_reabrir_os]").append(a);
                    $("span[class=status_checkpoint]").attr("style","background-color:#8DFF70");
                }

                if(login_fabrica == 164){

                    $("#conteudo_"+os).find("img[id^='consertado_']").hide();

                }

                if (login_fabrica == 169 || login_fabrica == 170){
                    $("#lgr_correios").show();
                    $("#td_excluir_"+os).hide();
                }                
            } else {


                <?php if($login_fabrica == 74){ ?>
                    var msg_erro = results[1].split("ERROR:");
                <?php } else  { ?>                    
                    if (results[1].indexOf("====") !== -1) {
                        var msg_erro = results[1].split("====");
                    }else{
                        var msg_erro = [0, results[1]];
                    }
                <?php }?>



                <?php
                if($login_fabrica == 74){
                ?>
                    if(msg_erro[0].length > 0 && msg_erro[0].search(/OS não pode ser finalizada pois esta OS encontra-se em auditoria de KM/i) >= 0){
                        alert("<?php echo traduz("a.os");?> "+os+" <?php echo traduz("nao.pode.ser.finalizada.pois.encontra.se.em.auditoria.de.km");?>");
                    }
                <?php
                }
                ?>

                if (msg_erro[1].search(/de-obra para instala/i) >= 0) {

                    alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.instalacao",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/Nota Fiscal de Devol/i) >= 0) {

                    alert ('<? fecho("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/o-de-obra para atendimento/i) >= 0) {

                    alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.este.atendimento",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/Favor informar aparência do produto e acessórios/i) >= 0) {

                    alert ('<? fecho("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/Type informado para o produto não é válido/i) >= 0) {

                    alert ('<? fecho("type.informado.para.o.produto.nao.e.valido",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/OS com peças pendentes/i) >= 0) {

                    alert ('<? fecho("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/OS não pode ser fechada, Favor Informar a Kilometragem/i) >= 0) {

                    alert ('<? fecho("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/OS não pode ser fechada, Kilometragem Recusada/i) >= 0) {

                    alert ('<? fecho("os.nao.pode.ser.fechada,.kilometragem.recusada",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/em intervenção/i) >= 0) {

                    alert ('<? fecho("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma) ?>');

                }
                else if (msg_erro[1].search(/OS não pode ser fechada, aguardando aprovação de Kilometragem/i) >= 0) {

                    alert ('<? fecho("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/Esta OS teve o número de série recusado e não pode ser finalizada/i) >= 0) {

                    <? if ($login_fabrica == 50) { ?>
                        alert ('<? fecho("esta.os.teve.o.numero.de.serie.recusado.ou.esta.em.intervencao.e.nao.pode.ser.finalizada",$con,$cook_idioma) ?>');
                    <? } else { ?>
                        alert ('<? fecho("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada",$con,$cook_idioma) ?>');
                    <? } ?>

                } else if (msg_erro[1].search(/Informar defeito constatado (Reparo) para OS/i) >= 0) {

                    alert ('<? fecho("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens", $con, $cook_idioma) ?>');

                } else if (msg_erro[1].search(/Por favor, informar o conserto do produto na tela CONSERTADO/i) >= 0) {

                    alert ('<? fecho("por.favor.informar.o.conserto.do.produto.na.tela.consertado",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/Favor informar solução tomada para a ordem de serviço/i) >= 0) {

                    alert ('<? fecho("oss.sem.solucao.e.sem.itens.lancados",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/Favor informar o defeito constatado para a ordem de serviço/i) >= 0) {

                    alert ('<? fecho("oss.sem.defeito.constatado",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/em intervenção por número de Série/i) >= 0) {

                    alert ('<? fecho("em.intervencao.por.numero.de.serie",$con,$cook_idioma) ?>');

                } else if (msg_erro[1].search(/informe o motivo de atraso de fechamento na aba/i) >= 0) {

                    alert ('<?php echo traduz("favor.informar.o.motivo.de.atraso.de.fechamento.na.aba.o.servico");?>');

                } else if (msg_erro[1].search(/OS não pode ser finalizada pois esta OS encontra-se em auditoria/i) >= 0) {

                    alert ('<?php echo traduz("os.nao.pode.ser.finalizada.pois.esta.os.encontra.se.em.auditoria");?>');

                } else if (msg_erro[1].search(/A OS não pode ser fechada, pois o pedido de peça está pendente/i) >= 0) {

                    alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.o.pedido.de.peca.esta.pendente");?>');

                } else if (msg_erro[1].search(/pois não há pedido gerado/i) >= 0) {

                    alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.nao.ha.pedido.gerado");?>');

                }else if (msg_erro[1].search(/pois pedido não foi faturado/i) >= 0) {

                    alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.pedido.nao.foi.faturado");?>');

                }else if (msg_erro[1].search(/pois pedido foi faturado a menos de sete dias/i) >= 0) {

                    alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.pedido.foi.faturado.a.menos.de.sete.dias");?>');

                }else if (msg_erro[1].search(/OS com pedido de peças Pendentes/i) >= 0 || msg_erro[1].search(/pois o pedido de pe/i) >= 0 ) {

                    alert ('<?php echo traduz("a.os.nao.pode.ser.fechada.pois.o.pedido.de.peca.esta.pendente");?>');

                } else if (msg_erro[1].search(/Para fechar a OS/i) >= 0) {

                    alert('<?php echo traduz("para.fechar.a.os.e.necessario.lancar.peca.e.o.servico.realizado");?>');

                } else if (msg_erro[1].search(/com intervenção/i) >= 0) {

                    alert('<?php echo traduz("esta.os.esta.em.intervencao.e.nao.pode.ser.finalizada");?>');

                } else if ((msg_erro[1].search(/número de Série do produto/i) >= 0 || msg_erro[1].search(/Favor digitar o número de Série do produto na Ordem/i) >= 0) && msg_erro[1].search(/anexo/i) == 0) {

                    alert('<? fecho("favor.digitar.o.numero.de.serie.na.ordem.de.servico",$con,$cook_idioma) ?>');

                }else if (msg_erro[1].search(/aberta a mais de 48 horas/i) >= 0) {

                    alert('<?php echo traduz("os.aberta.a.mais.de.48.horas.sem.motivo.do.atraso.informado");?>');

                    var motivo = $('#motivo_atraso_fechamento_'+os);
                    if(motivo.css("display","none")){
                        motivo.css("display","block");
                    }
                } else if (msg_erro[1].search(/Informe a data de conserto para a OS/i) >= 0) {

                    alert('<?php echo traduz("informe.a.data.de.conserto.da.os");?> : '+os);

                }else if (msg_erro[1].search(/Os Com Intervencao De Troca, Nao Pode Ser Fechada/i) >= 0) {

                    alert('<?php echo traduz("os.com.intervencao.de.troca.nao.pode.ser.fechada");?>');
                }else if (msg_erro[1].search(/pois o pedido de p/i) >= 0) {

                    alert('<?php echo traduz("os.nao.pode.ser.fechada.pois.o.pedido.esta.pendente");?>');

                } else if (msg_erro[1].search(/Favor entrar em contato com a/i) >= 0 || msg_erro[1].search(/da reincid/i) >= 0) {

                    alert('<?php echo traduz("esta.os.esta.em.aprovacao.de.reincidencia.e.nao.pode.ser.finalizada.favor.entrar.em.contato.com.a.fabrica");?>');

                } else if (msg_erro[1].search(/informe a data de conserto para a OS/i) >= 0 || msg_erro[1].search(/da reincid/i) >= 0) {

                    alert('<?php echo traduz("informe.a.data.de.conserto.para.a.os");?>');

                } else if (results[0] == 'nome do tecnico') {

                    alert('<?php echo traduz("informe.o.nome.do.tecnico.para.a.os");?>');

                } else if (msg_erro[1].search(/preencher o Check List/i) >= 0) {

                    alert('<?php echo traduz("para.finalizar.a.os.e.preciso.preencher.o.check.list");?>');

                } else if (msg_erro[1].search(/abrir chamado junto a Gelopar/i) >= 0) {

                    alert(msg_erro[1]);

                } else if (msg_erro[1].search(/sob auditoria de Nota Fiscal/i) >= 0) {

                    alert(msg_erro[1]);

				} else {
					if(msg_erro[1].search(/ERROR:/) > 0 ) {
						msg_erro = msg_erro[1].split('ERROR:');

						msg_erro = msg_erro[1].split('CONTEXT:');
	                    alert(msg_erro[0]);
					}else if(msg_erro[1].search(/CONTEXT:/) > 0 ){
                        msg_erro = msg_erro[1].split('CONTEXT:');
                        alert(msg_erro[0]);
                    }else{
						if (login_fabrica == 151) { /*HD - 6185214*/
                          alert(utf8Decode(msg_erro[1]));
                        } else {
                            alert(msg_erro[1]);
                        }
					}
                }
            }

            if(login_fabrica == 131){
                window.location.reload();
            }

        } else {
            alert ('<? fecho("fechamento.nao.processado",$con,$cook_idioma) ?>');
        } 
    });
}

/*HD - 6185214*/
function utf8Decode(utf8String) {
    if (typeof utf8String != 'string') return unicodeString;
    
    const unicodeString = utf8String.replace(
        /[\u00e0-\u00ef][\u0080-\u00bf][\u0080-\u00bf]/g,
        function(c) {
            var cc = ((c.charCodeAt(0)&0x0f)<<12) | ((c.charCodeAt(1)&0x3f)<<6) | ( c.charCodeAt(2)&0x3f);
            return String.fromCharCode(cc); }
    ).replace(
        /[\u00c0-\u00df][\u0080-\u00bf]/g,
        function(c) { 
            var cc = (c.charCodeAt(0)&0x1f)<<6 | c.charCodeAt(1)&0x3f;
            return String.fromCharCode(cc); }
    );
    return unicodeString;
}

function retornaConsertadoOS (http ,botao, indice){
    if (http.readyState == 4) {
        if (http.status == 200) {
            var results = http.responseText.split("|");

            if (typeof (results[0]) != 'undefined'){
                if (_trim(results[0]) == 'ok') {
                    $("#consertado_"+indice).parent().fadeOut();
                    //botao.style.display='none';
                    <?php
                    #HD 311411
                    if($login_fabrica == 6):?>
                        ocultaBotoesOS(indice);
                    <?php endif;?>

                    <?php if(in_array($login_fabrica, array(11,172))): ?>
                        $('#consertado_'+indice).hide();
                    <?php endif; ?>

                    <?php if (in_array($login_fabrica, array(169,170,174))){ ?>
                        $("#lgr_correios").show();
                    <?php } ?>
                    <?php if (in_array($login_fabrica, [123,160])) { ?>
                            if (results[2] != '' && results[2] != undefined) {
                                window.location.href="termo_retirada.php?os="+results[2];
                            }
                    <?php } ?>
                }else{                    
                    if(results[1]){
                        if (results[1].indexOf('ERROR') !== -1){
                            results = results[1].split('ERROR:');
                            <?php if($login_fabrica == 35){ ?>
                                if (results[1].search(/informar o defeito constatado/i) >= 0) {
                                    alert('<?php echo traduz("favor.informar.o.defeito.constatado.e.solucao.para.a.ordem.de.servico");?>');
                                }
                            <?php } ?>
                            results = results[1].split('CONTEXT:');

                            alert(results[0]);
                        }else{
                            alert(results[1]);
                        }
					}else if(results[0] == 'nome do tecnico'){

                        alert('<?php echo traduz("informe.o.nome.do.tecnico.para.a.os");?>');

                    }else{
                        alert('<? fecho("acao.nao.concluida.tente.novamente",$con,$cook_idioma) ?>');
                    }
                }
            }else{
                alert ('<? fecho("acao.nao.foi.concluida.com.sucesso",$con,$cook_idioma) ?>');
            }
        }
    }
}

<?php
if ($usaLaudoTecnicoOs) {
?>
    window.addEventListener('message', function(e) {
        [action, data] = e.data.split("|");
        
        if (action == 'osConsertada') {
            $('#conteudo_'+data).find('img[id^=consertado_]').parent('a').parent('td').html("\
                <button type='button' class='btn-visualizar-laudo-tecnico' data-os='"+data+"' style='cursor: pointer;' >Laudo Técnico</button>\
            ");
            
            <?php
            if ($login_fabrica == 175) {
            ?>
                $('#conteudo_'+data).find('td[rel=td_reabrir_os]').html('');
                $('#conteudo_'+data).find('.btn-visualizar-laudo-tecnico').after("\
                    &nbsp;<button type='button' class='btn-certificado-calibracao' data-os='"+data+"' style='cursor: pointer;' >Certificado de Calibração</button>\
                ");
            <?php
            }
            ?>
        }
    });

    function consertadoOS(os, botao, indice) {
        Shadowbox.open({
            content: '<div style=\'text-align: center; background-color: #FFF;\' ><h1><i class=\'fa fa-spinner fa-pulse\'></i> Gerando Laudo Técnico</h1></div>',
            player: 'html',
            height: 48,
            options: {
                modal: true,
                enableKeys: false,
                displayNav: false
            }
        });
        
        setTimeout(function() {
            $.ajax({
                url: window.location,
                type: 'get',
                data: {
                    ajax: 'busca_laudo_tecnico',
                    os: os
                },
                async: true,
                timeout: 60000
            }).fail(function(res) {
                alert('Erro ao gerar laudo técnico');
                Shadowbox.close();
            }).done(function(res, req){
                if (req == 'success') {
                    res = JSON.parse(res);
                    
                    if (res.erro) {
                        alert(res.erro);
                        Shadowbox.close();
                    } else {
                        Shadowbox.close();
                        
                        setTimeout(function() {
                            Shadowbox.open({
                                content: '<div id=\'sb-player\' ></div>',
                                player: 'html',
                                height: window.innerHeight,
                                width: window.innerWidth,
                                options: {
                                    modal: true,
                                    enableKeys: false,
                                    onFinish: function() {
                                        let player = $('#sb-player');
                                        let iframe = $('<iframe></iframe>', { 
                                            src: 'os_laudo_tecnico.php?os='+os, 
                                            css: {
                                                height: '100%',
                                                width: '100%'
                                            }
                                        });
                                        
                                        $(iframe).on('load', function(e) {
                                            e.target.contentWindow.postMessage('setFbData|'+res.comentario, '*');
                                            
                                            let data = {
                                                edit: false,
                                                title: 'Laudo Técnico - OS '+res.sua_os,
                                                logo: $('#logo_fabrica').attr('src')
                                            };
                                            
                                            e.target.contentWindow.postMessage('toggleFbEdit|'+JSON.stringify(data), '*');
                                        });
                                        
                                        $(player).html(iframe);
                                        $(player).css({ overflow: 'hidden' });
                                    }
                                }
                            });
                        }, 1000);
                    }
                } else {
                    alert('Erro ao gerar laudo técnico');
                    Shadowbox.close();
                }
            });
        }, 1000);
    }
    
    $(function() {
        $(document).on('click', '.btn-visualizar-laudo-tecnico', function() {
            let os = $(this).data('os');
           
            Shadowbox.open({
                content: '<div style=\'text-align: center; background-color: #FFF;\' ><h1><i class=\'fa fa-spinner fa-pulse\'></i> Gerando Laudo Técnico</h1></div>',
                player: 'html',
                height: 48,
                options: {
                    modal: true,
                    enableKeys: false,
                    displayNav: false
                }
            });
            
            setTimeout(function() {
                $.ajax({
                    url: window.location,
                    type: 'get',
                    data: {
                        ajax: 'busca_laudo_tecnico_os',
                        os: os,
                        readonly: true
                    },
                    async: true,
                    timeout: 60000
                }).fail(function(res) {
                    alert('Erro ao gerar laudo técnico');
                    Shadowbox.close();
                }).done(function(res, req){
                    if (req == 'success') {
                        res = JSON.parse(res);
                        
                        if (res.erro) {
                            alert(res.erro);
                            Shadowbox.close();
                        } else {
                            Shadowbox.close();
                            
                            setTimeout(function() {
                                Shadowbox.open({
                                    content: '<div id=\'sb-player\' ></div>',
                                    player: 'html',
                                    height: window.innerHeight,
                                    width: window.innerWidth,
                                    options: {
                                        modal: true,
                                        enableKeys: false,
                                        onFinish: function() {
                                            let player = $('#sb-player');
                                            let iframe = $('<iframe></iframe>', { 
                                                src: 'os_laudo_tecnico.php?os='+os+'&readonly=true', 
                                                css: {
                                                    height: '100%',
                                                    width: '100%'
                                                }
                                            });
                                            
                                            $(iframe).on('load', function(e) {
                                                e.target.contentWindow.postMessage('setFbData|'+res.titulo, '*');
                                                
                                                let data = {
                                                    edit: false,
                                                    title: 'Laudo Técnico - OS '+res.sua_os,
                                                    logo: $('#logo_fabrica').attr('src'),
                                                    formData: res.observacao,
                                                    noActions: true
                                                };
                                                
                                                e.target.contentWindow.postMessage('toggleFbEdit|'+JSON.stringify(data), '*');
                                            });
                                            
                                            $(player).html(iframe);
                                            $(player).css({ overflow: 'hidden' });
                                        }
                                    }
                                });
                            }, 1000);
                        }
                    } else {
                        alert('Erro ao gerar laudo técnico');
                        Shadowbox.close();
                    }
                });
            }, 1000);
        });
        
        <?php
        if (in_array($login_fabrica, array(175))) {
        ?>
            $(document).on('click', '.btn-certificado-calibracao', function() {
                let os = $(this).data('os');
                
                window.open('certificado_calibracao.php?os='+os);
            });
        <?php
        }
        ?>
    });
<?php
} else {
?>
    function consertadoOS (os , botao, indice ) {
        var curDateTime = new Date();
        url = "<?= $PHP_SELF ?>?consertado=" + escape(os)+'&dt='+curDateTime ;
        http.open("GET", url , true);
        http.onreadystatechange = function () { retornaConsertadoOS (http , botao, indice) ; } ;
        http.send(null);
    }
<?php
}

#HD 311411
?>
function ocultaBotoesOS(indice){
    $('#lancar_'+indice).hide();
    $('#excluir_'+indice).hide();
}


function abreLaudo(os){
    window.open('os_laudo.php?os='+os,'laudo');
}

function motivoAtraso(os,motivo){
    if(confirm('Deseja gravar este motivo?')){
            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>?motivoAtraso=1&idOS="+os+"&motivo="+motivo,
                cache: false,
                success: function(data) {

                    retorno = data.split('|');

                    if (retorno[0]=="OK") {
                        alert(retorno[1]);
                        $("#motivo_atraso_fechamento_"+os).remove();
                    } else {
                        alert(retorno[1]);
                    }

                }
            });
    }
}

<?php if (in_array($login_fabrica, array(169,170,174))){ ?>
function retornoPostagem(status,hd_chamado){
    if(status == "true"){
        $("#lgr_correios").hide();
    }
    Shadowbox.close();
}

function solicitaPostagem(hd_chamado, codigo_posto) {
    Shadowbox.open({
        content :   "solicitacao_postagem_correios_produto.php?hd_chamado="+hd_chamado+"&codigo_posto="+codigo_posto,
        player  :   "iframe",
        title   :   "<?php echo traduz("solicitar.autorizacao.de.postagem");?>",
        width   :   1000,
        height  :   700,
        options: {
            modal: true,
            enableKeys: false,
            displayNav: false
        }
    });
}
<?php } ?>

function excluiPreOs(obj,hd_chamado){
    
    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>",
        data:{"excluir_pre_os": true, hd_chamado : hd_chamado },
        type: 'GET',
        beforeSend: function () {
            $("#loading_pre_os").show();
        },
        complete: function(data) {
            data = $.parseJSON(data.responseText);

            if(data.sucesso){
                    $(obj).parents('tr').hide();
                    alert("Pré-atendimento excluído com sucesso");
            } 
            if(data.erro){
                    alert("Falha ao excluir o Pré-atendimento");
            }

            $("#loading_pre_os").hide();
        }
    });
}
</script>

<?php /*HD - 4206757*/
if ($login_fabrica == 35) { ?>
<script>
    $( document ).ready(function() {
        $("#dlg_fechar_sms").click( function(){
            $("#dlg_motivo_sms").css("display", "none");
        });

        $("#dlg_btn_nao_sms").click( function(){
            $("#dlg_motivo_sms").css("display", "none");
        });

        $("#dlg_btn_sim_sms").click( function(){
            var os = $("#dlg_aux_os_sms").val();            
            dlgEnviarSMS(os);
        });
    });

    function novoFinalizarOsCadence(os) {      

        if (confirm('Deseja realmente finalizar a O.S. ' + os + ' ?') == false) return false;
        $.ajax({
            url : "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            data: {
                novo_fechar_os_cadence: 'true',
                os : os                
            },
            complete: function(data){

                var response = data.responseText;
                
                $("#dlg_pergunta_sms").css("display", "block");
                $("#sms_success").css("display", "none");

                response = response.split("|");
                
                if (response[0] == "OK"){
					dlgEnviarSMS(os);
                    $("#dlg_aux_os_sms").val(os);
                    $("#dlg_motivo_sms").css("display", "block");
                } else {
                    alert(response[1]);
                }
            }
        });
    }

    function dlgEnviarSMS(os) {        
        if (os == '') {
            alert("<?php echo traduz("erro.ao.identificar.a.o.s");?>");
        } else {
            $.ajax({
                url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                data: {
                    enviar_sms_os: 'true',
                    os : os
                },
                complete: function(data){
                    var response = data.responseText;                    
                    response = response.split("|");                                       
                    if (response[0] == "KO") {
						if (response[2] == "C") {
							$("#sms_erro").css("display", "block");
							$("#lbl_error_sms").html("<?php echo traduz("erro.ao.enviar.sms.para.o.consumidor");?>: " + response[1]);
						} else {
							$("#sms_erro").css("display", "none");
						}
                    } else {
                        $("#sms_success").css("display", "block");
                        $("#lbl_success_sms").html(response[1]);
                    }

                    $("#dlg_pergunta_sms").css("display", "none");
                }
            });
        }
    }
</script>

 <div id='dlg_motivo_sms'>
    <div id='motivo_header_sms'></div>
    <div id='dlg_fechar_sms'>X</div>
    <div id='motivo_container_sms'>
        <center>
            <p id="exclusao_sms" style='font-size:12px;font-weight:bold;color:green;'>
                <?php echo traduz("os.finalizada.com.sucesso");?>
            </p>
            <p id="sms_erro" style='display:none;font-size:12px;font-weight:bold;color:red;'>
                <label id="lbl_error_sms"></label>
            </p>
            <p id="sms_success" style='display:none;font-size:12px;font-weight:bold;color:green;'>
                <label id="lbl_success_sms"></label>
            </p>
        </center>
    </div>
</div>
<?php } ?>


<br>
<?
if (strlen($msg_erro) > 0) {
    $msg_erro = mb_detect_encoding($msg_erro, 'UTF-8', true) ? utf8_decode($msg_erro) : $msg_erro;
    echo "<div align='center'><div width='700' style='width:700px' class='error'>$msg_erro</div></div>";
}

 # HD 234532
        ##### LEGENDAS - INÍCIO - HD 234532 #####
        /*
         0 | Aberta Call-Center               | #D6D6D6
         1 | Aguardando Analise               | #FF8282
         2 | Aguardando Peças                 | #FAFF73
         3 | Aguardando Conserto              | #EF5CFF
         4 | Aguardando Retirada              | #9E8FFF
         9 | Finalizada                       | #8DFF70
         13| Pedido Cancelado                 | #EE9A00
        */
        #Se for Bosh Security modificar a condição para pegar outros status também.
        $condicao_status = ($login_fabrica == 96) ? '0,1,2,3,5,6,7,9' : '0,1,2,3,4,9';

        if(in_array($login_fabrica, array(51, 81, 114))){
            $condicao_status = '0,1,2,3,4,8,9';
        }

        if($login_fabrica == 30){
            $condicao_status = '0,1,2,3,15,16,17,18,4,8,9';
        }

        if (isset($novaTelaOs)) {
            $condicao_status = '0,1,2,3,4,9,8';
        }

        if ($login_fabrica == 141) {
            $condicao_status = '0,1,14,2,8,11,3,10,12,4,9';
        }

        if ($login_fabrica == 165 && $posto_interno) {
            $condicao_status = '0,1,14,2,8,11,3,12,4,9,29,30';
        }

        if ($login_fabrica == 144) {
            $condicao_status = '0,1,14,2,8,11,3,10,4,9';
        }

        if($login_fabrica == 3){
            $condicao_status = '0,1,2,3,4,9,10';
        }

        if($login_fabrica == 131){ // HD-2181938
          $condicao_status = '0,1,2,3,4,9,13';
        }

        if (in_array($login_fabrica, array(158))) {
            $condicao_status = '1,2,3,9,23,24,25,26,27';
        }

        if($login_fabrica == 148){ //hd_chamado=3049906
            $condicao_status = '0,1,2,3,4,8,9,28';
        }

    	if ($login_fabrica == 35) {
    	    $condicao_status = '1,2,3,8,9,34';
    	}

        if ($telecontrol_distrib && (!isset($novaTelaOs) || (in_array($login_fabrica, [160]) or $replica_einhell))) {
            $condicao_status .= ",36, 37, 39";
        }

        if ($cancelaOS) {
            $condicao_status .= ",28";
        }

        if (in_array($login_fabrica, array(171,175))) {
            $condicao_status .= ",14";
        }

        if (in_array($login_fabrica, array(169,170))) {
            $condicao_status = "0,1,2,3,4,8,9,14,28,30,45,46,47,48,49,50";
        }
        
        if (in_array($login_fabrica, array(177))) {
            $condicao_status .= ",14";
        }

        if (in_array($login_fabrica, [174])) {
            $condicao_status .= ",39";
        }

        if (in_array($login_fabrica, [167, 203])) {
            $condicao_status .= ",37";
        }

        if ($login_fabrica == 151) {
            $condicao_status .= ",54";
        }

        if (in_array($login_fabrica, [174])) {
            $sql_interno_aquarius = " SELECT posto_interno
                                    FROM tbl_posto_fabrica
                                        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                                    WHERE posto = $login_posto
                                        AND tbl_posto_fabrica.fabrica = $login_fabrica";
            $res_interno_aquarius = pg_query($con, $sql_interno_aquarius);
            $interno_aquarius = pg_fetch_result($res_interno_aquarius, 0, posto_interno);
            if ($interno_aquarius == 't') {
                $condicao_status .= ",40,41,42,43";
            }
        }

        if (in_array($login_fabrica, array(178))){
            $condicao_status .= ",30";
        }

if ($login_fabrica == 183) {
    $condicao_status = '0,1,2,3,8,9,28,30';
}

if ((strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) OR strlen($btn_acao_pre_os) > 0) {

    if (strlen($btn_acao_pre_os) > 0){

        if (!in_array($login_fabrica,array(7,30,52,96,151))) {

            if($login_fabrica == 164){

                $nome_pre_os = str_replace("-", "", $_POST["nome_pre_os"]);
                $cep_pre_os = str_replace("-", "", $_POST["cep_pre_os"]);
                $cpf_pre_os = str_replace(array("-", "."), "", $_POST["cpf_pre_os"]);
                $autorizacao_postagem = trim($_POST["autorizacao_postagem"]);

                if(strlen($nome_pre_os) > 0){
                    $cond_nome_pre_os = " AND tbl_hd_chamado_extra.nome ilike '%{$nome_pre_os}%' ";
                }

                if(strlen($cep_pre_os) > 0){
                    $cond_cep_pre_os = " AND tbl_hd_chamado_extra.cep = '{$cep_pre_os}' ";
                }

                if(strlen($cpf_pre_os) > 0){
                    $cond_cpf_pre_os = " AND tbl_hd_chamado_extra.cpf = '{$cpf_pre_os}' ";
                }

                if (!empty($autorizacao_postagem)) {
                    $join_tbl_hd_chamado_postagem = "INNER JOIN tbl_hd_chamado_postagem ON tbl_hd_chamado_postagem.hd_chamado = tbl_hd_chamado.hd_chamado";
                    $cond_autorizacao_postagem = "AND tbl_hd_chamado_postagem.numero_postagem = '{$autorizacao_postagem}'";
                }

            }

            if ($login_fabrica == 94 || isset($novaTelaOs)) {
                $sql_add = "AND tbl_hd_chamado.status != 'Cancelado'";
            }

            if ($login_fabrica == 24){
                $sql_add = " AND tbl_hd_chamado.data >= '2013-09-30' ";
            }

            if($login_fabrica == 59){

                if($consumidor_cpf > 0){
                    $consumidor_cpf = preg_replace("/[.-]/", "", $consumidor_cpf);
                    $sql_add = "AND tbl_hd_chamado_extra.cpf = '$consumidor_cpf'";
                }
            }

            if (!in_array($login_fabrica, array(169, 170))) {
                if ($login_fabrica == 35 ) {
                    // Data de corte
                    $data_corte = '2018-11-22'; 
                    $data_hj = date("Y-m-d "); 
                    if ($data_hj >= $data_corte) {
                        $sql_add = " AND tbl_hd_chamado.data::date >= '$data_corte'::date";
                    } else {
                        $whereStatusResolvido = " AND tbl_hd_chamado.status not in ('Resolvido','Cancelado')";    
                    }
                } else {
                    $whereStatusResolvido = " AND tbl_hd_chamado.status not in ('Resolvido','Cancelado')";
                }
            }

            $cond_fabrica_pesquisa = (in_array($login_fabrica, array(11,172))) ? " IN(11,172) " : " = {$login_fabrica} ";
            $distinct_os_pres = (in_array($login_fabrica, array(11,172))) ? " DISTINCT tbl_hd_chamado.fabrica, " : "";

            $sqlinf = "SELECT $distinct_os_pres tbl_hd_chamado.hd_chamado, '' as sua_os, serie, nota_fiscal    ,
            TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data               ,
            TO_CHAR(tbl_hd_chamado.data,'YYYY-MM-DD HH24:MI:SS') AS dt_hr_abertura ,
            tbl_hd_chamado_extra.posto                                        ,
            tbl_hd_chamado.cliente_admin                                      ,
            tbl_posto_fabrica.codigo_posto                                    ,
            tbl_posto.nome                              AS posto_nome         ,
            tbl_hd_chamado_extra.fone as consumidor_fone                      ,
            tbl_hd_chamado_extra.nome                                         ,
            tbl_hd_chamado_extra.ordem_montagem                               ,
            tbl_hd_chamado_extra.codigo_postagem                              ,
            tbl_hd_chamado_extra.tipo_atendimento                             ,
            tbl_hd_chamado_extra.numero_postagem,
            tbl_hd_chamado_extra.array_campos_adicionais,
            tbl_marca.nome as marca_nome                                      ,
            tbl_produto.referencia_fabrica AS produto_referencia_fabrica   ,
            tbl_produto.referencia                                            ,
            tbl_produto.descricao                                             ,
            tbl_hd_chamado_extra.ordem_montagem                               ,
            tbl_hd_chamado_extra.consumidor_revenda
            FROM tbl_hd_chamado_extra
            JOIN tbl_hd_chamado using(hd_chamado)
            LEFT JOIN tbl_produto on tbl_hd_chamado_extra.produto = tbl_produto.produto
            LEFT JOIN tbl_marca   on tbl_produto.marca = tbl_marca.marca
            LEFT JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_hd_chamado_extra.posto
            LEFT JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica {$cond_fabrica_pesquisa}
            {$join_tbl_hd_chamado_postagem}
            WHERE tbl_hd_chamado.fabrica {$cond_fabrica_pesquisa}
            AND tbl_hd_chamado_extra.abre_os = 't'
            AND tbl_hd_chamado_extra.posto = $login_posto
            $whereStatusResolvido
            $sql_add
            $sql_marca_ativa            
            $cond_nome_pre_os
            $cond_cep_pre_os
            $cond_cpf_pre_os
            {$cond_autorizacao_postagem}
            AND tbl_hd_chamado_extra.os is null";

        } else {
            if ($login_fabrica == 96) {
                $ordena = 'ORDER BY tbl_hd_chamado.hd_chamado DESC';
            }else{
                $ordena = ' ';
            }
            if ($login_fabrica == 151) {
                $campoExtra = "tbl_hd_chamado_item.nota_fiscal,";
            } else {
                $campoExtra = "tbl_hd_chamado_extra.nota_fiscal,";
            }
            $sqlinf = "SELECT DISTINCT
                    tbl_hd_chamado.hd_chamado,
                    tbl_hd_chamado_item.hd_chamado_item,
                    '' as sua_os,
                    tbl_hd_chamado_item.serie,
                    {$campoExtra}
                    TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data,
                    TO_CHAR(tbl_hd_chamado.data,'YYYY-MM-DD HH24:MI:SS') AS dt_hr_abertura ,
                    tbl_hd_chamado_extra.nome,
                    tbl_marca.nome as marca_nome,
                    tbl_hd_chamado_extra.tipo_atendimento                             ,
                    tbl_produto.referencia_fabrica AS produto_referencia_fabrica,
                    tbl_produto.referencia,
                    tbl_produto.descricao
                    
                    FROM tbl_hd_chamado
                    JOIN tbl_hd_chamado_extra using(hd_chamado)
                    LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado and tbl_hd_chamado_item.produto is not null
                    LEFT JOIN tbl_produto on (tbl_hd_chamado_item.produto = tbl_produto.produto or tbl_hd_chamado_extra.produto = tbl_produto.produto)
                    LEFT JOIN tbl_marca   on tbl_produto.marca = tbl_marca.marca
                    WHERE tbl_hd_chamado.fabrica = $login_fabrica
                    AND tbl_hd_chamado_extra.posto = $login_posto
                    AND tbl_hd_chamado_extra.abre_os = 't'
                    ".(($login_fabrica != 96) ? "AND tbl_hd_chamado.status not in ('Cancelado','Resolvido')" : "")."
                    /* HD 213171: Para a Fricon o número da OS é gravado em tbl_hd_chamado_item.os */
                    /*            Anteriormente era gravado em tbl_hd_chamado_extra.os */
                    AND tbl_hd_chamado_item.os is null
                    AND tbl_hd_chamado_extra.os is null
                    $ordena
                    ";
        }

         //echo nl2br($sqlinf); exit;        

        $res = pg_query ($con,$sqlinf);

    }else{

        if ($login_e_distribuidor <> 't') {
            $posto = $login_posto ;
        }

        if (isset($status_os_ultimo)) {
            $cond_os .= " AND tbl_os.status_os_ultimo = $status_os_ultimo ";
        }

        if(!in_array($login_fabrica,array(3,11,14,20,30,35,50,153,172)) && !$cancelaOS) {
            $cond_os .=" AND tbl_os.excluida IS NOT TRUE ";
        }

        if (in_array($login_fabrica, [19])) {
            $cond_os .= "AND tbl_os.tipo_atendimento != 339";
        }

        if ($cancelaOS) {
            $cond_os .= " AND (tbl_os.excluida IS NOT TRUE OR (tbl_os.excluida IS TRUE AND tbl_os.status_checkpoint = 28)) ";
        }

        if($login_fabrica == 7 && strlen($natureza)>0){
            $cond_os .= " AND tbl_os.tipo_atendimento = $natureza ";
        }

		if (strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0) {
				$tipo_fechada = $_GET['tipo_fechada'];
				if (!empty($status_checkpoint) and $status_checkpoint == 9 and !empty($tipo_fechada)) {
						$cond_os .= "   AND     tbl_os.data_digitacao between current_timestamp - interval '3 months' and current_timestamp AND tbl_os.status_checkpoint = $status_checkpoint";
						switch($tipo_fechada) {
							case '1' :
									$cond_os .= " AND tbl_os.finalizada - tbl_os.data_digitacao between '0 day' and '3 days' ";
									break;
							case '2' :
									$cond_os .= " AND tbl_os.finalizada - tbl_os.data_digitacao between '4 days' and '7 days' ";
									break;
							case '3' :
									$cond_os .= " AND tbl_os.finalizada - tbl_os.data_digitacao between '8 days' and '15 days' ";
									break;
							case '4' :
									$cond_os .= " AND tbl_os.finalizada - tbl_os.data_digitacao between '16 days' and '25 days' ";
									break;
							case '5' :
									$cond_os .= " AND tbl_os.finalizada - tbl_os.data_digitacao >  '25 days' ";
									break;
						}
        		}elseif($login_fabrica == 42 AND strlen($sua_os) == 0){
                    $cond_os .= " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";

                }elseif($login_fabrica != 42){
		            $cond_os .= " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
				}
        }elseif($login_fabrica == 42){
            $cond_os .= " AND tbl_os.data_digitacao BETWEEN '". date("Y-m-d", strtotime('-6 month')) ." 00:00:00' AND '".date("Y-m-d")." 23:59:59'";
        }


        #SISTEMA RG
        if(strlen($rg_produto)>0){
            if(in_array($login_fabrica, array(6,11,172))) {
                if($login_posto== 4262 || in_array($login_fabrica, array(11,172)) ) {
                    $cond_os .= " AND tbl_os.rg_produto = '$rg_produto'";
                }
            } else {
                $cond_os .= " AND tbl_os.os IN (SELECT os FROM tbl_produto_rg_item WHERE rg = '$rg_produto') ";
            }
        }
        if(strlen($os_posto)>0){
            if (in_array($login_fabrica,array(35,157,158))) {
                $os_posto = trim($os_posto);
                $cond_os.= " AND tbl_os.os_posto = '$os_posto' ";
            } else {
                $cond_os .= " AND tbl_os.os_posto LIKE '%".strtoupper($os_posto)."%'";
            }
        }

        if (strlen($admin) > 0) {
            $cond_os .= " AND tbl_os.admin = '$admin' ";
        }

        if (strlen($sua_os) > 0) {
            #A Black tem consulta separada(os_consulta_avancada.php).
            if ($login_fabrica == 1) {
                $pos = strpos($sua_os, "-");
                if ($pos === false) {
                    $pos = strlen($sua_os) - 5;
                }else{
                    $pos = $pos - 5;
                }
                $sua_os = substr($sua_os, $pos,strlen($sua_os));
            }
            $sua_os = strtoupper($sua_os);

            $pos = strpos($sua_os, "-");
            if ($pos === false && (!in_array($login_fabrica,array(42,121,137,138,142,144,145))) && !isset($novaTelaOs)) {
                $sua_os = preg_replace('/\D/','',$sua_os);
                if(!ctype_digit($sua_os)){
                    $cond_os .= " AND tbl_os.sua_os = '$sua_os' ";
                }else{
                    $cond_os .= " AND (tbl_os.os_numero = '$sua_os' or tbl_os.sua_os='$sua_os')  ";
                }
            }else{

                if($login_fabrica == 42){

                    $sua_os = preg_replace('/\D/','',$sua_os);
                    if(!ctype_digit($sua_os)){
                        $cond_os .= " AND tbl_os.sua_os = '$sua_os' ";
                        $cond_os .= " AND tbl_os.data_digitacao BETWEEN '". date("Y-m-d", strtotime('-6 month')) ." 00:00:00' AND '".date("Y-m-d")." 23:59:59'";
                    }else{
                        $cond_os .= " AND (tbl_os.os_numero = '$sua_os' or tbl_os.sua_os='$sua_os')  ";
                        $cond_os .= " AND tbl_os.data_digitacao BETWEEN '". date("Y-m-d", strtotime('-6 month')) ." 00:00:00' AND '".date("Y-m-d")." 23:59:59'";
                    }

                }elseif(in_array($login_fabrica,array(121,137,138,142,144,145)) || isset($novaTelaOs)){
                    $cond_os .= " AND tbl_os.sua_os like '$sua_os%'";
                                        
                }else{
                    $conteudo = explode("-", $sua_os);
                    $sua_os = preg_replace('/\D/','',$sua_os);
                    $os_numero    = preg_replace('/\D/','',$conteudo[0]);
                    $os_sequencia = preg_replace('/\D/','',$conteudo[1]);
                    if(!ctype_digit($os_sequencia)){
                        $cond_os .= " AND tbl_os.sua_os = '$sua_os' ";
                    }else{
                        $cond_os .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
                    }
                }
            }
        }

        //HD 211825: Filtro por tipo de OS: Consumidor/Revenda
        if (strlen($consumidor_revenda_pesquisa)) {
            $cond_os .= " AND tbl_os.consumidor_revenda='$consumidor_revenda_pesquisa'";
        }

        if (strlen($os_off) > 0) {
            $cond_os .= " AND (tbl_os.sua_os_offline LIKE '$os_off%' OR tbl_os.sua_os_offline LIKE '0$os_off%' OR tbl_os.sua_os_offline LIKE '00$os_off%') ";
        }

		if (strlen($serie) > 0) {
			if($login_fabrica == 94 ) {
				$cond_os .= " AND lpad(tbl_os.serie, 12, '0') = lpad('$serie', 12, '0') ";
			}else{
				$cond_os .= " AND tbl_os.serie = '$serie' ";
			}
        }

        if ($login_fabrica == 158 && !empty($patrimonio)) {
            $cond_os .= " AND UPPER(tbl_os_extra.serie_justificativa) = '$patrimonio' ";
        }

        if(strlen(trim($versao))>0){
             $cond_os .= " AND tbl_os.type = '$versao' ";
        }

        if($login_fabrica == 164){
            if(strlen($destinacao) > 0){
                $cond_os .= " AND tbl_os.segmento_atuacao = {$destinacao} ";
            }
        }

        /*  02/12/2009 - MLG HD 180918 - Se faz um strtoupper no $_POST, no WHERE fazer também um UPPER!
            Como a pesquisa com UPPER(campo) é mais lenta, só usar se a NF não é numérico... */
        if (strlen($nf_compra) > 0) {
            if (is_numeric($nf_compra)) {
                $cond_os .= " AND tbl_os.nota_fiscal = '$nf_compra'";
            } else {
                $cond_os .= " AND UPPER(tbl_os.nota_fiscal) = '$nf_compra'";
            }
        }
        if (strlen($consumidor_nome) > 0) {
            $consumidor_nome = $consumidor_nome;
            $cond_os .= " AND tbl_os.consumidor_nome LIKE '".strtoupper($consumidor_nome)."%'";
        }

        if (strlen($consumidor_cpf) > 0) {
            $cond_os .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
        }

        if (strlen($os_aberta) > 0) {
            $cond_os .= " AND tbl_os.os_fechada IS FALSE AND tbl_os.excluida IS NOT TRUE";
        }

        if (in_array($login_fabrica, array(169,170))){
            if (strlen($os_cortesia) > 0){
                $cond_os .= " AND tbl_os.cortesia IS TRUE";
            }
        }

        if (strlen($revenda_cnpj) > 0) {
            if($login_fabrica == 15){
                $cond_os .= " AND (tbl_os.data_fechamento IS NULL AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%') ";
            } else {
                $cond_os .= " AND (tbl_os.data_fechamento IS NULL AND tbl_os.consumidor_revenda = 'R' AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%') ";
            }
        }

        if($login_fabrica==1){
            $cond_os .= " AND tbl_os.consumidor_revenda = 'C' AND tbl_os.cortesia IS NOT TRUE ";
        }

        //HD 14927, HD 193563
        if (usaDataConserto($login_posto, $login_fabrica)) {
            $sql_data_conserto=" , TO_CHAR(tbl_os.data_conserto,'DD/MM/YYYY') as data_conserto ";
        }

        if ($login_fabrica == 3) {
            $campos_marca = " ,tbl_marca.marca ,
                               tbl_marca.nome as marca_nome ";
        }
        // HD 415550
        if(isset($_POST['nome_tecnico']) ) {
            $tecnico = trim ($_POST['nome_tecnico']);
            $cond_os .= (!empty($tecnico)) ? "AND tbl_os.tecnico_nome ILIKE '" . $tecnico . "%' " : '';
        }

        if ($login_fabrica == 24){
            $cond_data = " AND data_digitacao > '2013-10-01 00:00:00' ";
            $cond_cancelada = " AND tbl_os.cancelada IS NOT TRUE ";
        }

        if(in_array($login_fabrica, array(11,72,172))){
            $cond_cancelada = " AND tbl_os.cancelada IS NOT TRUE ";
        }
        //if($login_fabrica == 74){ //hd_chamado=2588542
            //$cond_cancelada = " AND tbl_os.cancelada IS NOT TRUE ";
            //Retirada esse condição no hd-3187654
        //}
        if ($login_fabrica == 86){
            $sql_sem_defeito = ", tbl_os.defeito_reclamado ";
        }

        if (in_array($login_fabrica, array(72,161,167,177,203))){
            $sql_sem_defeito = ", tbl_os.defeito_reclamado_descricao
                                , tbl_os.defeito_constatado
                                , tbl_os.solucao_os";
        }

        if (!isset($novaTelaOs)) {
            $column_serie = "tbl_os.serie,";
        }

	if (in_array($login_fabrica, array(131,138,145,169,170,171))) {
		$distinct_os = "DISTINCT ON(tmp_consulta.os)";
	}

    if($login_fabrica == 160 or $replica_einhell){
        $column_lote .= " tbl_os.type, ";
    }

    if(strlen(trim($imei)) > 0){
        $cond_imei = " and rg_produto = '$imei' ";
    }

    if(strlen(trim($cep)) > 0){
        $cond_cep = " and tbl_os.consumidor_cep = '$cep' ";
    }

    if(in_array($login_fabrica, array(156,162)) AND !empty($login_unico) AND $login_unico_master != "t"){
        $tecnico_busca = ($login_fabrica == 156) ? $login_unico_tecnico : $_POST['tecnico'];

        if (!empty($tecnico_busca)) {
            $cond_tecnico = " AND tbl_os.tecnico = $tecnico_busca  ";
        }
    } elseif (in_array($login_fabrica, array(156,162)) and $login_unico_master == "t" and !empty($_POST["tecnico"])) {
        $tecnico = $_POST['tecnico'];
        $cond_tecnico   = " AND tbl_os.tecnico = $tecnico ";
    }

    if ($login_fabrica == 162) {
        $campo_tecnico  = " tbl_tecnico.nome AS tecnico_nome_tbl, ";
        $join_tecnico   = " LEFT JOIN tbl_tecnico USING(tecnico) ";

        $hd_classificacao = filter_input(INPUT_POST,'hd_classificacao');

        if (!empty($hd_classificacao)) {
            $join_classificacao     = " LEFT JOIN tbl_hd_chamado    ON  tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado ";
            $where_classificacao    = " AND tbl_hd_chamado.hd_classificacao = $hd_classificacao ";

        }
    }

    if ($login_fabrica == 165 && !empty($_POST['tecnico'])) {
        $tecnico = $_POST['tecnico'];
        $cond_tecnico   = " AND tbl_os.tecnico = $tecnico ";
        $campo_tecnico  = " tbl_tecnico.nome AS tecnico_nome_tbl, ";
        $join_tecnico   = " LEFT JOIN tbl_tecnico USING(tecnico) ";
    }

	$intervalo1 = $_REQUEST['intervalo1'];
	$intervalo2 = $_REQUEST['intervalo2'];

	if(!empty($intervalo1) and !empty($intervalo2) and !empty($aux_data_final)) {
		$cond_dashboard = " AND   tbl_os.data_digitacao BETWEEN '$aux_data_final 00:00'::timestamp - interval '$intervalo1 days' AND '$aux_data_final 23:59:59'::timestamp - interval '$intervalo2 days'";
	}

    if ($usaPostoTecnico && !in_array($login_fabrica, [175])){
        $tecnico = $_POST['tecnico'];
        if (!empty($tecnico)){
            $cond_tecnico = "AND tbl_os.tecnico = $tecnico";
        }
    }
    if (in_array($login_fabrica, [173]) && $posto_interno === true && !empty($login_unico)) {
        
        if ($LU_tecnico_posto === true && $login_unico_master != 't' && !empty($LU_tecnico)) {
            $cond_tecnico   = " AND tbl_os.tecnico = {$LU_tecnico} ";
            $campo_tecnico  = " tbl_tecnico.nome AS tecnico_nome_tbl, ";
            $join_tecnico   = " JOIN tbl_tecnico USING(tecnico) ";
        }

    }

    $posto_externo = 0;

    if ($login_fabrica == 156 and true === $posto_interno) {
        $codigo_posto_externo = $_POST["codigo_posto_externo"];
        $posto_nome_externo = $_POST["posto_nome_externo"];

        if (!empty($codigo_posto_externo) and !empty($posto_nome_externo)) {
            $qry_posto_ext = pg_query($con, "
                SELECT tbl_posto.posto FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                  AND tbl_posto_fabrica.fabrica = $login_fabrica
                WHERE codigo_posto = '{$codigo_posto_externo}'
                AND nome = '{$posto_nome_externo}'
                ");

            if (pg_num_rows($qry_posto_ext) > 0) {
                $posto_externo = pg_fetch_result($qry_posto_ext, 0, 'posto');
            }
        }
    }

    $cond_posto_externo = '';
    $join_nf_recebimento = '';
    $os_campos_adicionais = '';
    $nf_recebimento = '';

    if ($login_fabrica == 74) {
        $campo_linha_nome = " , tbl_linha.nome as linha_nome ";
        $join_linha = "
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
        ";
    }

    if ($login_fabrica == 156 and !empty($_POST['nf_recebimento'])) {
        $nf_recebimento = $_POST['nf_recebimento'];
        $os_campos_adicionais = ' , tbl_os_campo_extra.campos_adicionais ';
        $join_nf_recebimento = ' JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
            AND tbl_os_campo_extra.campos_adicionais IS NOT NULL ';
    }

    if (in_array($login_fabrica,[74,151])) {
        $join_os_congeladas = "
            LEFT JOIN tbl_os_campo_extra    ON  tbl_os_campo_extra.os = tbl_os.os
                                            AND tbl_os_campo_extra.fabrica = tbl_os.fabrica
        ";
        $condOsCongeladas = "
            AND (
                    tbl_os_campo_extra.os_bloqueada IS NOT TRUE
                OR  tbl_os_campo_extra.os IS NULL
                )
        ";
    }

    if (!empty($posto_externo)) {
        $sql_os_externa = "SELECT os INTO TEMP temp_os_externa_consulta
            FROM tbl_os
            WHERE fabrica = $login_fabrica
            AND posto = $posto_externo
            $cond_os $cond_data";
        $qry_os_externa = pg_query($con, $sql_os_externa);

        $cond_posto_externo = " AND os_numero IN (SELECT os FROM temp_os_externa_consulta) ";
    }

     $os_troca = $_POST['os_troca'];
    if($os_troca == 1){
        $join_os_troca = " JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os ";
    }

    if($login_fabrica == 165){
        $linha_165 = " tbl_defeito_constatado.descricao AS defeito_constatado_descricao, ";
        $join_165 = " LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica} ";
        $column_serie = " tbl_os.serie AS serie_os, ";
        $sqlProduto_trocado .= " (pt.referencia || ' - ' || pt.descricao) AS produto_trocado,  ";

        $joinTrocaProduto = " LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
                     LEFT JOIN tbl_produto pt ON pt.produto = tbl_os_troca.produto  ";
    }

    if ($login_fabrica == 183 AND $login_tipo_posto_codigo == "Rep"){
        $join_representante = "JOIN tbl_representante ON tbl_representante.representante = tbl_os_extra.representante AND tbl_representante.cnpj = '{$login_cnpj}' AND tbl_representante.fabrica = {$login_fabrica}";
    }else{
        $cond_posto = "AND tbl_os.posto = $login_posto";
    }
    if($login_fabrica == 158){
        $campos_imbera = "tbl_os_extra.serie_justificativa,
                          tbl_os.serie,
                          tbl_posto_fabrica.codigo_posto,
                          tbl_posto.nome,
                          tbl_os.consumidor_endereco,
                          tbl_os.consumidor_bairro,
                          tbl_os.consumidor_fone,
                          tbl_os.consumidor_cep,
                          tbl_os.obs,
                          tbl_os.os_posto as os_kof,
                          tbl_defeito_reclamado.descricao as desc_defeito_reclamado,
                          tbl_hd_chamado_cockpit.dados as observacao_kof,
                          tbl_os_produto.produto,";
     
       $join_imbera = "left join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = 158
                       left join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
                       left join tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado and tbl_defeito_reclamado.fabrica = 158
                       left join tbl_os_produto on tbl_os_produto.os = tbl_os.os 
                       left join tbl_produto on tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i = 158
                       left join tbl_hd_chamado_cockpit on tbl_os.hd_chamado = tbl_hd_chamado_cockpit.hd_chamado";
     
    }

    if($login_fabrica != 158){
        $campos = " tbl_os.produto,";
    }
        // OS não excluí­da
        $sql =  "SELECT DISTINCT tbl_os.os,
                                tbl_os.sua_os ,
                                tbl_os.os_posto,
                                tbl_os.posto ,
                                tbl_os.sua_os_offline ,
                                {$campos}
                                LPAD(tbl_os.sua_os,20,'0') AS ordem ,
                                TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
                                TO_CHAR(tbl_os.data_digitacao,'YYYY-MM-DD') AS data_digitacao ,
                                tbl_os.data_abertura - tbl_os.data_nf AS tempo_para_defeito ,
                                TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
                                tbl_os.data_abertura ,
                                TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento ,
                                TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada ,
                                {$column_serie}
                                {$column_lote}
                                $campo_tecnico
                                tbl_os.excluida ,
                                {$linha_165}
                                $sqlProduto_trocado
                                tbl_os.motivo_atraso ,
                                tbl_os.tipo_os_cortesia ,
                                $campos_imbera
                                tbl_os.consumidor_revenda ,
                                tbl_os.consumidor_nome ,
                                tbl_os.consumidor_cidade ,
                                tbl_os.consumidor_estado ,
                                tbl_os.revenda_nome ,
                                tbl_os.revenda_cnpj,
                                tbl_os.tipo_atendimento ,
                                tbl_os.tecnico_nome ,
                                tbl_os.admin ,
                                tbl_os.rg_produto ,
                                tbl_os.os_reincidente AS reincidencia ,
                                tbl_os.valores_adicionais ,
                                tbl_os.nota_fiscal ,
                                tbl_os.nota_fiscal_saida ,
                                tbl_os.consumidor_email,
                                tbl_os.status_checkpoint,
				                tbl_os.os_numero,
                                tbl_os.cancelada,
                                tbl_os.tecnico,
                                (tbl_os.qtde_km ) AS valor_km
                                $sql_sem_defeito
                                $sql_data_conserto
                                $os_campos_adicionais
                                {$campo_linha_nome}
                        into TEMP temp_os_consulta_$login_posto
                        FROM    tbl_os
                        $join_tecnico
                        {$join_linha}
                        {$join_165}
                        $joinTrocaProduto
                        LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                        JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
                        $join_imbera ";
        if($dash == 1){
            $sql .= "
                        JOIN    tbl_posto_linha USING   (posto)
                        JOIN    tbl_linha       ON      tbl_linha.linha     = tbl_posto_linha.linha
                                                AND     tbl_linha.fabrica   = $login_fabrica
                        JOIN    tbl_produto     ON      tbl_produto.produto = tbl_os.produto
                                                AND     tbl_produto.linha   = tbl_linha.linha
            ";
        }

        $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN (11,172) " : " tbl_os.fabrica = $login_fabrica ";

        $sql .= "
            $join_representante
            $join_nf_recebimento
			$join_os_congeladas
			$join_os_troca
            WHERE {$cond_pesquisa_fabrica} ";

        if (in_array($login_fabrica, array(169,170))){
            if(isset($_GET['finalizada_index'])){// dados vem do arquivo admin/dashboard_novo.php
                if ($_GET['finalizada_index'] == 0){
                    $sql .= ' AND (EXTRACT(DAYS FROM (tbl_os.data_conserto - tbl_os.data_digitacao)) <= 10) AND tbl_os.data_conserto IS NOT NULL ';
                }

                if($_GET['finalizada_index'] == 1){
                    $sql .= ' AND (EXTRACT(DAYS FROM (tbl_os.data_conserto - tbl_os.data_digitacao)) >= 10) AND tbl_os.data_conserto IS NOT NULL ';
                }
            }

            if(isset($_GET['tipo_os'])){
                $tipoOS = $_GET['tipo_os'];

                if (!empty($tipo_os)){
                    $sql .= " AND tbl_os.consumidor_revenda = '$tipo_os' ";
                }
            }

        }

        if($login_fabrica == 148 && !empty($tipo_atendimento)){
            $sql_TipoAtendmiento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE descricao = '{$tipo_atendimento}' AND fabrica = {$login_fabrica}";
            $res_TipoAtendmiento = pg_query($con, $sql_TipoAtendmiento);
            $id_tipoatendimento = pg_fetch_result($res_TipoAtendmiento, 0, 'tipo_atendimento');
            $cond_tipoatendimento = " AND tbl_os.tipo_atendimento = {$id_tipoatendimento}";
        }

        $sql .="
            $cond_posto
            $cond_tipoatendimento
            $cond_os
            $cond_data
            $cond_cancelada
            $cond_tecnico
            $cond_imei
            $cond_cep
			$cond_dashboard
            $cond_posto_externo
            $condOsCongeladas;";

            if(empty($sua_os)){
            $sql .= "
                    CREATE INDEX temp_os_consulta_os_$login_posto ON temp_os_consulta_$login_posto(os);
                    CREATE INDEX temp_os_consulta_posto_$login_posto ON temp_os_consulta_$login_posto(posto);
            ";
            }

        $res = pg_query($con,$sql);

        if(isset($novaTelaOs)){
            $column_serie = "tbl_os.serie,";
            $join_produto = " LEFT JOIN tbl_os ON tbl_os.os = tmp_consulta.os
                              LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";
        }else{

	    unset($column_serie);
            $join_produto = " LEFT JOIN tbl_produto ON tbl_produto.produto = tmp_consulta.produto ";
        }

        if($login_fabrica == 85){
            $campo_array_chamado_extra = "tbl_hd_chamado_extra.array_campos_adicionais,";
        }

        if(in_array($login_fabrica,array(3,24,35,42,72,151,164,175))){ //hd_chamado=2816974 Adicionada fabrica 42
            $distinct_os = "DISTINCT";
        }

        if ($login_fabrica == 74) {
            $campo_linha_nome = " , linha_nome ";
        }

        if ($login_fabrica == 171) {
            $campo_auditoria = " tbl_auditoria_os.auditoria_status ,";
        }

        if (in_array($login_fabrica, [167,177,203]) && $posto_interno == true) {
            $campoOrcamento = ", tbl_status_os.descricao as descricao_orcamento";
        }

        $sql2 .= "SELECT $distinct_os tmp_consulta.* ,
                        ARRAY_TO_STRING(
                        ARRAY(
                        SELECT tbl_hd_chamado_item.hd_chamado
                        FROM tbl_hd_chamado_item
                        WHERE tbl_hd_chamado_item.os = tmp_consulta.os
                        )
                        ,'<br>'
                        ) AS hd_chamado ,
                        tbl_hd_chamado_extra.ordem_montagem ,
                        tbl_hd_chamado_extra.codigo_postagem ,
                        $campo_array_chamado_extra
                        tbl_posto_fabrica.codigo_posto ,
                        tbl_posto_fabrica.atendimento AS atendimento_posto,
                        tbl_posto.nome AS posto_nome ,
                        tbl_os_extra.impressa ,
                        tbl_os_extra.extrato ,
                        tbl_os_extra.os_reincidente ,
                        tbl_os_extra.recolhimento,
                        tbl_tipo_atendimento.descricao,
                        tbl_tipo_atendimento.grupo_atendimento,
                        tbl_produto.referencia_fabrica AS produto_referencia_fabrica,
                        tbl_produto.referencia AS produto_referencia,
                        tbl_produto.descricao AS produto_descricao ,
                        tbl_produto.voltagem AS produto_voltagem ,
                        $campo_auditoria
                        {$column_serie}
                        tbl_produto.linha
                        $campos_marca
                        $campo_linha_nome
                        $campoOrcamento
                FROM    tbl_posto
                JOIN    temp_os_consulta_$login_posto AS tmp_consulta ON (tbl_posto.posto = tmp_consulta.posto)";

                if ($login_fabrica == 171) {
                    $sql2 .= " LEFT JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tmp_consulta.os ";
                }

        $cond_hd_extra = ($login_fabrica == 30) ? " AND tbl_hd_chamado_extra.posto = tmp_consulta.posto " : "";
        $sql2  .= "
                JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica   = $login_fabrica
                $join_produto

           LEFT JOIN    tbl_hd_chamado_extra    ON  tbl_hd_chamado_extra.os     = tmp_consulta.os
                                                $cond_hd_extra
           $join_classificacao
            LEFT JOIN    tbl_os_extra            ON  tbl_os_extra.os             = tmp_consulta.os";

        $produtoOsCheck = explode(" ", $join_produto);
        if (in_array($login_fabrica, [167,203]) AND !in_array("tbl_os", $produtoOsCheck) AND $posto_interno == true) {
            $sql2 .= " LEFT JOIN tbl_os ON tmp_consulta.os = tbl_os.os ";
        }

        if (in_array($login_fabrica, [167,177,203]) && $posto_interno == true) {
            $sql2 .= " LEFT JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os.status_os_ultimo ";
        }

        if (strlen($os_situacao) > 0) {
            $sql2 .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
            if ($os_situacao == "PAGA")
                $sql2 .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
        }
        if ($login_fabrica == 3) {
            $sql2 .= " LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ";
        }

        if((in_array($login_fabrica, array(87, 94, 115, 116, 117, 120, 201, 145, 153, 158,163,169,170,171,174,175,176,177)) || in_array($login_fabrica, array(167,173,203)) && $posto_interno == true) AND !empty($descricao_tipo_atendimento)) {
            $sql2_cond_tipo_atendimento = " AND tbl_tipo_atendimento.tipo_atendimento = '$descricao_tipo_atendimento' ";
        }

        $sql2 .= "  LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tmp_consulta.posto
                    LEFT JOIN tbl_tipo_atendimento      ON tbl_tipo_atendimento.tipo_atendimento = tmp_consulta.tipo_atendimento
                    WHERE (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL)  $sql2_cond_tipo_atendimento $where_classificacao";


        if (strlen($posto_nome) > 0) {
            $posto_nome = strtoupper ($posto_nome);
            $sql2 .= " AND tbl_posto.nome LIKE '$posto_nome%' ";
        }

        if (strlen($produto_referencia) > 0) {
            $sql2.= " AND tbl_produto.referencia = '$produto_referencia' ";
        }

        if (in_array($login_fabrica, [167,177,203]) && !empty($_POST['status_orcamento'])) {
            $status_orcamento_desc = $_POST['status_orcamento'];

            $sql2.= " AND tbl_status_os.descricao = '$status_orcamento_desc'";
        }

        if(strlen($status_checkpoint) > 0){
			if($status_checkpoint == 9) {
				$cond_status = " or tmp_consulta.finalizada notnull";

			}
            if ($login_fabrica == 171 && $status_checkpoint == 'CALLCENTER') {
                $sql2.= " AND tbl_hd_chamado_extra.os IS NOT NULL ";
            } else {
                $sql2.= " AND (tmp_consulta.status_checkpoint = $status_checkpoint $cond_status )";
            }
        }

        #HD 13940 - Para mostrar as OS recusadas
        if($login_fabrica==20) {
            $sql2 .=" AND (tmp_consulta.excluida IS NOT TRUE OR tbl_os_extra.status_os = 94 ) ";
        }


        if($login_fabrica == 3 && strlen($marca)>0){
            $sql2 .= " AND tbl_marca.marca = $marca  ";
        }

        if($login_fabrica == 43) {
            if (strlen($_POST['ordem_montagem'])>0) {
                $ordem_montagem = $_POST['ordem_montagem'];
                $sql2 .= " AND tbl_hd_chamado_extra.ordem_montagem = '$ordem_montagem' ";
            }
        }

        if($login_fabrica == 3) {
            if (strlen($_POST['protocolo_atendimento'])>0) {
                $protocolo_atendimento = $_POST['protocolo_atendimento'];
                $sql2 .= " AND tbl_hd_chamado_extra.ordem_montagem = $protocolo_atendimento";
            }
        }

        if (!empty($nf_recebimento)) {
            $sql2 .= ' AND campos_adicionais LIKE \'%"nf_envio":"' . $nf_recebimento . '"%\' ';
        }

        if ($login_fabrica == 120 or $login_fabrica == 201) {
            $sql2 .= ' AND cancelada IS NOT TRUE ';
        }

        if ($login_fabrica == 7 || $login_fabrica == 43) {
            $sql2 .= " ORDER BY tmp_consulta.data_abertura ASC, LPAD(tmp_consulta.sua_os,20,'0') ASC";
        } else if($login_fabrica == 137 OR $login_fabrica == 121){
            $sql2 .= " ORDER BY tmp_consulta.os ASC";
        }else {
            $sql2 .= " ORDER BY tmp_consulta.os DESC";
        }
        
        $sqlT = preg_replace ("/\n|\t/"," ",$sql.$sql2) ;
        /**
         * @since HD 837962 - retirado
         */
        $resT = @pg_query ($con,"/* QUERY -> $sqlT  */");
        if ($_POST["gerar_excel"] == "t" AND ($login_fabrica == 165) || ($login_fabrica == 158)) {

            if($login_fabrica == 165){
                $campos_excel_165 = "temp_os_consulta_$login_posto.produto_trocado,
                                     temp_os_consulta_$login_posto.defeito_constatado_descricao,
                                     temp_os_consulta_$login_posto.serie_os,";
            }
            if($login_fabrica == 158){
                $campos_imbera_excel = "temp_os_consulta_$login_posto.serie_justificativa,
                                        temp_os_consulta_$login_posto.serie,
                                        temp_os_consulta_$login_posto.codigo_posto,
                                        temp_os_consulta_$login_posto.nome,
                                        temp_os_consulta_$login_posto.consumidor_endereco,
                                        temp_os_consulta_$login_posto.consumidor_bairro,
                                        temp_os_consulta_$login_posto.consumidor_fone,
                                        temp_os_consulta_$login_posto.consumidor_cep,
                                        temp_os_consulta_$login_posto.obs,
                                        temp_os_consulta_$login_posto.abertura,
                                        temp_os_consulta_$login_posto.fechamento,
                                        temp_os_consulta_$login_posto.observacao_kof,
                                        temp_os_consulta_$login_posto.desc_defeito_reclamado,
                                        temp_os_consulta_$login_posto.os_kof,";
            }
            $sqlexcell = "
                SELECT DISTINCT ON (temp_os_consulta_$login_posto.os)
                        temp_os_consulta_$login_posto.sua_os AS os_excell,
                        temp_os_consulta_$login_posto.produto,
                        tbl_produto.referencia_fabrica AS produto_referencia_fabrica,
                        tbl_produto.referencia AS produto_referencia,
                        tbl_produto.descricao AS produto_descricao,
                        temp_os_consulta_$login_posto.consumidor_revenda,
                        temp_os_consulta_$login_posto.consumidor_nome,
                        temp_os_consulta_$login_posto.revenda_nome,
                        temp_os_consulta_$login_posto.revenda_cnpj,
                        $campos_excel_165
                        $campos_imbera_excel
                        temp_os_consulta_$login_posto.nota_fiscal,
                        tbl_servico_realizado.descricao AS servico_descricao,
                        tbl_os_item.os_item
                FROM temp_os_consulta_$login_posto
                JOIN tbl_os_produto ON tbl_os_produto.os = temp_os_consulta_$login_posto.os
                LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
                LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
                LEFT JOIN tbl_produto ON tbl_produto.produto = temp_os_consulta_$login_posto.produto AND tbl_produto.fabrica_i = $login_fabrica
                WHERE temp_os_consulta_$login_posto.posto = $login_posto
                AND (tbl_servico_realizado.ativo IS TRUE OR tbl_os_item.os_item IS NULL)
                ORDER BY temp_os_consulta_$login_posto.os ASC";

            $resexcell = pg_query($con,$sqlexcell);
        }
        flush();
        ##### PAGINAÇÃO - INÍCIO #####
        $sqlCount  = "SELECT count(*) FROM (";
        $sqlCount .= $sql2;
        $sqlCount .= ") AS count";

        require "_class_paginacao.php";

        // definicoes de variaveis
        $max_links = 11;                // máximo de links à  serem exibidos
        $max_res   = 50;                // máximo de resultados à  serem exibidos por tela ou pagina
        $mult_pag= new Mult_Pag();  // cria um novo objeto navbar
        $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

        $res = $mult_pag->Executar($sql2, $sqlCount, $con, "otimizada", "pgsql");

        ##### PAGINAÇÃO - FIM #####
    }

    $resultados = pg_num_rows($res);
    if (pg_num_rows($res) > 0) {
        if (in_array($login_fabrica, array(169,170))) {
            $ordemStatus = "
                , CASE WHEN status_checkpoint = 0 THEN 0
                WHEN status_checkpoint = 1 THEN 1
                WHEN status_checkpoint = 2 THEN 2
                WHEN status_checkpoint = 8 THEN 3
                WHEN status_checkpoint = 45 THEN 4
                WHEN status_checkpoint = 46 THEN 5
                WHEN status_checkpoint = 47 THEN 6
                WHEN status_checkpoint = 3 THEN 7
                WHEN status_checkpoint = 4 THEN 8
                WHEN status_checkpoint = 14 THEN 9
                WHEN status_checkpoint = 30 THEN 10
                WHEN status_checkpoint = 9 THEN 11
                WHEN status_checkpoint = 48 THEN 12
                WHEN status_checkpoint = 49 THEN 13
                WHEN status_checkpoint = 50 THEN 14
                WHEN status_checkpoint = 28 THEN 15 END AS ordem
            ";
            $orderByStatus = "ORDER BY ordem ASC";
        }

        if (in_array($login_fabrica, [174])) {
            $ordemStatus = "
               ,CASE WHEN status_checkpoint = 0 THEN 0
                WHEN status_checkpoint = 40 THEN 1
                WHEN status_checkpoint = 1 THEN 2
                WHEN status_checkpoint = 2 THEN 3
                WHEN status_checkpoint = 3 THEN 4
                WHEN status_checkpoint = 41 THEN 5
                WHEN status_checkpoint = 42 THEN 6
                WHEN status_checkpoint = 43 THEN 7
                WHEN status_checkpoint = 4 THEN 8 
                END AS ordem
            ";
            $orderByStatus = "ORDER BY ordem ASC";
        } else if ($telecontrol_distrib) {
            $ordemStatus = "
               ,CASE WHEN status_checkpoint = 1 THEN 0
                WHEN status_checkpoint = 37 THEN 1
                WHEN status_checkpoint = 35 THEN 2
                WHEN status_checkpoint = 2 THEN 3
                WHEN status_checkpoint = 36 THEN 4
                WHEN status_checkpoint = 3 THEN 5
                WHEN status_checkpoint = 4 THEN 6
                WHEN status_checkpoint = 9 THEN 7
                WHEN status_checkpoint = 0 THEN 8
                WHEN status_checkpoint = 39 THEN 9
                END AS ordem
            ";
            $orderByStatus = "ORDER BY ordem ASC";
        }

        if ($telecontrol_distrib && (!isset($novaTelaOs) || (in_array($login_fabrica, [160]) or $replica_einhell))) {

            $campoDesc = ",CASE WHEN descricao = 'Aguardando Analise' 
                          THEN 'Aguardando Analise Posto'
                          ELSE descricao
                          END AS descricao";
        } else {
            $campoDesc = ', descricao';
        }

        $sql_status = "SELECT status_checkpoint {$campoDesc} ,cor $ordemStatus FROM tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.") {$where_tbl_status_checkpoint} $orderByStatus";

        $res_status = pg_query($con,$sql_status);
        $total_status = pg_num_rows($res_status);
        ?>

            <table border='0' cellspacing='0' cellpadding='0' width='700px' align='center'>
                <tr>
                    <td style='text-align: left; '  valign='bottom'>
                        <?php
                            if($login_fabrica == 96 AND strlen($btn_acao_pre_os) > 0){
                                //Retirar OS status para BOSCH HD - 669464
                            }else{
                                // INICIO STATUS DA OS?>
                                <div align='left' style='position:relative;left:25'>
                                    <h4><?php echo traduz("status.das.os");?></h4>
                                    <table border='0' cellspacing='0' cellpadding='0'>
                                    <?php
                                    for($i=0;$i<$total_status;$i++){

                                        $id_status        = pg_fetch_result($res_status,$i,'status_checkpoint');
                                        $cor_status       = pg_fetch_result($res_status,$i,'cor');
                                        $descricao_status = pg_fetch_result($res_status,$i,'descricao');

                                        #Array utilizado posteriormente para definir as cores dos status
                                        $array_cor_status[$id_status] = $cor_status;
                                        if($login_fabrica == 148 AND $id_status == 28){//3049906
                                            $descricao_status = "OS Cancelada2";
                                        }
                                        if($login_fabrica <> 87 OR ($login_fabrica == 87 AND $id_status != 0)){
                                        ?>

                                        <tr height='18'>
                                            <td width='18' >
                                                <div class="status_checkpoint" style="background-color:<?php echo $cor_status;?>">&nbsp;</div>
                                            </td>
                                            <td align='left'>
                                                <font size='1'>
                                                    <b>
                                                        <!-- <a href=\"javascript: filtro('vermelho')\"> -->
                                                            <? if ($login_fabrica == 165 && $posto_interno == true) {
                                                                switch ($descricao_status) {
                                                                    case "Aguardando Faturamento":
                                                                        $descricao_status = "Aguardando Expedição";
                                                                        break;
                                                                    default:
                                                                        $descricao_status = $descricao_status;
                                                                        break;
                                                                }
                                                            }
                                                            if($login_fabrica == 96 AND $id_status == 3){
                                                                $descricao_status = "Em conserto";
                                                            }
                                                            echo traduz($descricao_status); ?>
                                                        <!-- </a> -->
                                                    </b>
                                                </font>
                                            </td>
                                        </tr>
                                    <?php }}?>
                                    </table>
                                </div>
                        <?php } //FIM STATUS DA OS ?>
                    </td>
                    <td valign='bottom'><?php
                            ##### LEGENDAS - INÍCIO #####

                            echo "<div align='left' style='margin: 0 auto; width: 400px'>";
                            echo "<table border='0' cellspacing='0' cellpadding='0' width='100%' style='font-size: x-small'>";

                            if ($login_fabrica == 96 && strlen($btn_acao_pre_os) > 0) { //HD391024
                                echo "<tr height='18'>";
                                    echo "<td width='18' bgcolor='#C94040' class='legenda_os_cor'>&nbsp;</td>";
                                    echo "<td align='left' class='legenda_os_texto'>  ".traduz("fora.de.garantia")." </td>";
                                echo "</tr>";
                                echo "<tr height='3'><td colspan='2'></td></tr>";

                                echo "<tr height='18'>";
                                    echo "<td width='18' bgcolor='#FFFF66' class='legenda_os_cor'>&nbsp;</td>";
                                    echo "<td align='left' class='legenda_os_texto'>  ".traduz("garantia")."</td>";
                                echo "</tr>";
                                echo "<tr height='3'><td colspan='2'></td></tr>";

                                echo "<tr height='18'>";
                                    echo "<td width='18' bgcolor='#33CC00' class='legenda_os_cor'>&nbsp;</td>";
                                    echo "<td align='left' class='legenda_os_texto'>  ".traduz("retorno.de.garantia")." </td>";
                                echo "</tr>";
                                echo "<tr height='3'><td colspan='2'></td></tr>";
                            } else {

                                if ($excluida == "t") {
                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>";fecho("excluidas.do.sistema",$con,$cook_idioma);echo "</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                }

                                if ($login_fabrica != 1) {

                                    if ($login_fabrica == 87) $cor = "#40E0D0"; else $cor = "#D7FFE1";

                                    if(isset($novaTelaOs)) {
                                        $cor = "#ff9922";
                                    }

                                    if (in_array($login_fabrica, array(152,180,181,182))) {
                                        echo "<tr height='3'>";
                                            echo "<td width='55' bgcolor='#FF0000' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            fecho("os.cancelada",$con,$cook_idioma);
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";
                                    }

                                    echo "<tr height='3'>";
                                        echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'> ";
                                        fecho("reincidencia",$con,$cook_idioma);
                                        echo "</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";


                                    if ($login_fabrica <> 14) {

                                        if ($login_fabrica == 24) {
                                            echo "<tr height='18'>";
                                                echo "<td width='18' bgcolor='#54A8AE' class='legenda_os_cor'>&nbsp;</td>";
                                                echo "<td align='left' class='legenda_os_texto'>OS com mais de 7 dias sem lançamento de peças</td>";
                                            echo "</tr>";
                                            echo "<tr height='3'><td colspan='2'></td></tr>";
                                        }

                                        if ($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                                        echo "<tr height='3'>";
                                            echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            fecho("os.aberta.a.mais.de.25.dias",$con,$cook_idioma);
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";

                                        //HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
                                        if (in_array($login_fabrica, array(81, 114))) {

                                            echo "<tr height='18'>";
                                                echo "<td width='18' bgcolor='#d89988' class='legenda_os_cor'>&nbsp;</td>";
                                                echo "<td align='left' class='legenda_os_texto'> ";
                                                echo traduz("autorizacao.de.devolucao.de.venda");
                                                echo "</td>";
                                            echo "</tr>";
                                            echo "<tr height='3'><td colspan='2'></td></tr>";

                                        }

                                    }

                                    if ($login_fabrica == 50) {

                                        echo "<tr height='18'>";
                                            echo "<td width='18' bgcolor='#FF9933' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            fecho("os.recusada",$con,$cook_idioma);
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";
                                        echo "<tr height='18'>";
                                            echo "<td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'>";
                                            fecho("excluidas.do.sistema",$con,$cook_idioma);
                                            echo "</td>";
                                        //echo "13/03/2010</tr>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";

                                    }

                                    if ($login_fabrica == 35) {

                                        echo "<tr height='18'>";
                                            echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'>";
                                            fecho("excluidas.do.sistema",$con,$cook_idioma);
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";

                                    }

                                    if ($login_fabrica == 45 || isset($novaTelaOs)) {//HD 14584 26/2/2008

                                        echo "<tr height='3'><td colspan='2'></td></tr>";
                                        echo "<tr height='18'>";
                                            echo "<td width='18' bgcolor='#CCCCFF' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            fecho("os.com.ressarcimento.financeiro",$con,$cook_idioma);
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";
                                        echo "<tr height='18'>";
                                            echo "<td width='18' bgcolor='#FFCC66' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            fecho("os.com.troca.de.produto",$con,$cook_idioma);
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";
                                        if ($login_fabrica == 45) {
                                            echo "<tr height='18'>";
                                                echo "<td width='18' bgcolor='#FFCEFF' class='legenda_os_cor'>&nbsp;</td>";
                                                echo "<td align='left' class='legenda_os_texto'> ";
                                                fecho("os.consertada",$con,$cook_idioma);
                                                echo "</td>";
                                            echo "</tr>";
                                        }
                                    }

                                    if ($login_fabrica == 15) {

                                        echo "<tr height='18'>";
                                            echo "<td width='18' bgcolor='#999933' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            fecho("os.digitada.por.administrador",$con,$cook_idioma);
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";

                                    }

                                    if ($historico_intervencao) {

                                        if ($login_fabrica == 87) $cor = "#FFA5A4"; else $cor = "#FFCCCC";
                                        echo "<tr height='3'>";
                                            echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            fecho("os.com.intervencao.da.fabrica.aguardando.liberacao",$con,$cook_idioma);
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";

                                        if ($login_fabrica <> 98) {

                                            if ($login_fabrica <> 87) {

                                                echo "<tr height='3'>";
                                                    echo "<td width='55' bgcolor='#FFFF99' class='legenda_os_cor'>&nbsp;</td>";
                                                    echo "<td align='left' class='legenda_os_texto'> ";
                                                    fecho("os.com.intervencao.da.fabrica.reparo.na.fabrica",$con,$cook_idioma);
                                                    echo "</td>";
                                                echo "</tr>";
                                                echo "<tr height='3'><td colspan='2'></td></tr>";

                                            }

                                            if ($login_fabrica == 87) {
                                                $cor = "#FEFFA4";
                                            }elseif(in_array($login_fabrica, array(152,180,181,182))){
                                                $cor = "#CFCFCF";
                                            }elseif(isset($novaTelaOs)){
                                                $cor = "#AAFFAA";
                                            }else{
                                                $cor = "#CCFFFF";
                                            }
                                            echo "<tr height='3'>";
                                                echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'>&nbsp;</td>";
                                                echo "<td align='left' class='legenda_os_texto'> ";
                                                fecho("os.liberada.pela.fabrica",$con,$cook_idioma);
                                                echo "</td>";
                                            echo "</tr>";
                                            echo "<tr height='3'><td colspan='2'></td></tr>";

                                        }

                                    }

                                    if ($login_fabrica == 3 or $login_fabrica == 74) {
                                        echo "<tr height='18'>";
                                            echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            fecho("canceladas",$con,$cook_idioma); echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";
                                    }

                                } else if ($login_fabrica == 1) {

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#FFCC66' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>".fecho("oss.sem.lancamento.de.itens.a.mais.de.5.dias,.efetue.o.lancamento",$con,$cook_idioma)."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'> ".fecho("oss.que.excederam.o.prazo.limite.de.30.dias.para.fechamento,.informar.motivo\"",$con,$cook_idioma)."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#91C8FF' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'> ";
                                        fecho ("os.aberta.a.mais.de.25.dias",$con,$cook_idioma);
                                        echo "</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#D7FFE1' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'> ";
                                        fecho("reincidencia",$con,$cook_idioma);
                                        echo "</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                }

                                if (in_array($login_fabrica, array(11,172))) {
                                    echo "<tr height='18'>";
                                    echo "<td width='18' bgcolor='#FFE1E1' class='legenda_os_cor'>&nbsp;</td>";
                                    echo "<td align='left' class='legenda_os_texto'> "; fecho("excluidas.do.sistema",$con,$cook_idioma); echo" </td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                }

                                if ($login_fabrica == 20) {

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#CACACA' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'> ";
                                        fecho("os.reprovada.pelo.promotor",$con,$cook_idioma); echo"</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                }

                                if ($login_fabrica == 3) {

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#FFCC66' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'> ";
                                        fecho("os.com.troca.de.produto",$con,$cook_idioma);
                                        echo "</td>";
                                    echo "</tr>";

                                }

                                if ($login_fabrica != 98) {

                                    if($login_fabrica == 87) $cor = "#D2D2D2"; else $cor = "#CC9900";
                                    echo "<tr height='3'>";
                                        echo "<td width='55' bgcolor='$cor' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>";
                                        echo traduz("os.reincidente.e.aberta.a.mais.de.25.dias");
                                        echo " </td>";
                                    echo "</tr>";

                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                    if ($login_fabrica != 87) {

                                        echo "<tr height='3'>";
                                            if (isset($novaTelaOs)) {
                                                echo "<td width='55' bgcolor='#CCC' class='legenda_os_cor'>&nbsp;</td>";
                                            } else {
                                                echo "<td width='55' bgcolor='#FFAA33' class='legenda_os_cor'>&nbsp;</td>";
                                            }
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            echo traduz("os.com.ligacao.no.callcenter.da.fabrica");
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";

                                    }

                                    if ($login_fabrica == 131) { /*HD - 6840585*/
                                        $cor_os_reprovada_auditoria = "#FFB5C5";
                                        echo "<tr height='3'>";
                                            echo "<td width='55' bgcolor='{$cor_os_reprovada_auditoria}' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            echo traduz("os.reprovada.na.auditoria");
                                            echo "</td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";
                                    }

                                    if ($login_fabrica == 164 && $posto_interno == true) {

                                        echo "<tr height='3'>";
                                            echo "<td width='55' bgcolor='#D6D6D6' class='legenda_os_cor'>&nbsp;</td>";
                                            echo "<td align='left' class='legenda_os_texto'> ";
                                            echo traduz("os.com.troca.de.produto");
                                            echo " </td>";
                                        echo "</tr>";
                                        echo "<tr height='3'><td colspan='2'></td></tr>";

                                    }

                                }
                                if($login_fabrica == 151){?>
                                    <tr height='18'>
                                        <td width='18' bgcolor='#7CFC00' class='legenda_os_cor'>&nbsp;</td>
                                        <td align='left' class='legenda_os_texto'><?php echo traduz("encerrada.sem.reparo");?></td>
                                    </tr>
                                    <tr height='3'><td colspan='2'></td></tr>
                                <?php
                                }

                                if ($login_fabrica == 30){
                                    if(strlen($btn_acao_pre_os) > 0) {

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#FF0000' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>< ".traduz("os.abertas.a.mais.de.72.horas")." </td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#FFFF66' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>< ".traduz("os.abertas.a.mais.de.24.horas.e.menos.de.72.horas")." </td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#33CC00' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>< ".traduz("os.abertas.a.menos.de.24.horas")." </td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                    }

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#CF0000' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>".traduz("os.canceladas")."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                }

                                if ($login_fabrica == 85) { #HD 284058

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#AEAEFF' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>".traduz("peca.fora.da.garantia.aprovada.na.intervencao.da.os.para.gerar.pedido")."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                }

                                if ($login_fabrica == 40) { #HD 284058
                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#BFCDDB' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>< ".traduz("os.com.3.ou.mais.pecas")."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                }

                                if ($login_fabrica == 94) { #HD 785254
                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='silver' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>< ".traduz("os.foi.aberta.automaticamente.por.causa.de.uma.troca.gerada")."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                }

                                if ($login_fabrica == 3) {
                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#CB82FF' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>".traduz("os.com.pendencia.de.fotos")."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";

                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#A4A4A4' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>".traduz("os.com.intervencao.de.display")."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                }

                                 if ($login_fabrica == 91) {
                                    echo "<tr height='18'>";
                                        echo "<td width='18' bgcolor='#CB82FF' class='legenda_os_cor'>&nbsp;</td>";
                                        echo "<td align='left' class='legenda_os_texto'>".traduz("os.recusada.pela.fabrica")."</td>";
                                    echo "</tr>";
                                    echo "<tr height='3'><td colspan='2'></td></tr>";
                                }

                                if (in_array($login_fabrica, array(141,144))) { ?>
                                    <tr height="18" >
                                        <td width="18" bgcolor="#CB82FF" class="legenda_os_cor" >&nbsp;</td>
                                        <td align="left" class="legenda_os_texto" ><?php echo traduz("os.com.troca.de.produto.recusada");?></td>
                                    </tr>
                                    <tr height="3"><td colspan="2"></td></tr>
                                <?php
                                }

                            }

                            if (in_array($login_fabrica, array(148,157))) { ?>
                                <tr height="18" >
                                    <td width="18" bgcolor="#FF0000" class="legenda_os_cor" >&nbsp;</td>
                                    <td align="left" class="legenda_os_texto"><?php echo traduz("os.reprovada.da.auditoria");?></td>
                                </tr>
                                <tr height="3"><td colspan="2"></td></tr>
                            <?
                            }

                            echo "</table>";
                            echo "</div>";
                            ##### LEGENDAS - FIM #####
                        ?>
                    </td>
                </tr>
            </table><?php
        echo "<br>";
        ##### Motivo Exclusão - Diálogo #####?>

        <div id='dlg_motivo'>
            <div id='motivo_header'><?php echo traduz("informe.o.motivo.da.exclusao");?></div>
            <div id='dlg_fechar'>X</div>
            <div id='motivo_container'>
                <center><p id="exclusao" style='display:none;font-size:12px;font-weight:bold;color:green;'><?php echo traduz("os.excluida.com.sucesso");?></p></center>
                <p><?php echo traduz("qual.o.motivo.da.exclusao.da.os");?> <span id="motivo_os" alt=''></span>?</p>
                <input type="text" name="str_motivo" id="str_motivo" size='50'>
                <br>
                <button type="button" id="dlg_btn_excluir"><?php echo traduz("excluir");?></button>
                <button type="button" id="dlg_btn_cancel"><?php echo traduz("cancelar");?></button>
            </div>
        </div><?php

        ##### Motivo Exclusão -   FIM   #####

        if ($login_fabrica == 20) {
            echo "<table border='1' cellpadding='0' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' width='98%'>";
        }else{
            echo "<table border='0' cellpadding='2' cellspacing='1' class='tabela' width='80%'>";
        }

        $esconde_coluna_pre_os = false;

        if ($btn_acao_pre_os && in_array($login_fabrica, array(174))) {
            $esconde_coluna_pre_os = true;
        }

        for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
            $defeito_reclamado_descricao = "";

            if ($i % 50 == 0) {
                flush();
            }

            if ($i % 50 == 0) {
                echo "<tr class='titulo_coluna' height='25' >";
                //HD 371911

                if (!in_array($login_fabrica,array(1,30))) {
                    echo '<td><input type="checkbox" value="" class="selecionaTodos frm"/></td>';
                }

                //HD 214236: Legendas para auditoria de OS da Intelbras. Como vão ser auditadas todas as OS, criei uma coluna nova para que sinalize o status da auditoria
                if (in_array($login_fabrica, array(14, 43, 104))) {
                    echo "<td>".traduz("status")."</td>";
                }

                if ($esconde_coluna_pre_os) {
                    echo "<td width='150' nowrap>".traduz("Nº Chamado")."</td>";
                } elseif ($btn_acao_pre_os && in_array($login_fabrica, [169,170,177,184,200])) {
                    echo "<td width='150' nowrap>".traduz("Callcenter")."</td>";
                } elseif ($btn_acao_pre_os && in_array($login_fabrica, [191])) {
                    echo "<td width='150' nowrap>".traduz("origem")."</td>";
                }else {
                    echo "<td width='150' nowrap>".traduz("os")."</td>";
                }

                if($login_fabrica == 19){ //hd_chamado=2881143
                    echo "<td>".traduz("extrato")."</td>";
                }
                if (in_array($login_fabrica,array(50,52,80,30,43,14,96,50,3))) {
                    echo "<td>".traduz("atendimento.n")."</td>";
                }

                if($login_fabrica == 42){//hd_chamado=2816974 Adicionada fabrica 42
                    echo "<td>".traduz("atendimento.help.desk")."</td>";
                }

                if (in_array($login_fabrica, array(14, 43))) {
                    echo "<td>".traduz("ordem.de.montagem")."</td>";
                    echo "<td>".traduz("codigo.de.postagem")."</td>";
                }

                //HD 8431 OS interna para Argentina
                if (in_array($login_fabrica, array(10, 19, 158)) || ($login_fabrica == 20 AND $login_pais == 'AR')) {
                    if (in_array($login_fabrica, array(10, 19))) {
                        echo "<td>".traduz("os.off.line")."</td>";
                    } else if ($login_fabrica == 158) {
                        echo "<td>".traduz("os.cliente")."</td>";
                    } else {
                        echo "<td>".traduz("os.interna",$con,$cook_idioma)."</td>";
                    }
                }

                if (!in_array($login_fabrica, array(127,145))) { // HD-2296739
                    echo "<td width='150'>";
                        if ($login_fabrica == 35) {
                            echo "PO#";
                        } else if($login_fabrica == 137){
                            echo traduz("n.lote");
                        } elseif($login_fabrica == 160 or $replica_einhell){
                            fecho("nº.do.lote",$con,$cook_idioma);
                        }else {
                            fecho("serie",$con,$cook_idioma);
                        }
                    echo "</td>";
                }

                if ($login_fabrica == 160 or $replica_einhell) { // HD 92774
                    echo "<td>"; fecho ("Versão.Produto",$con,$cook_idioma); echo "</td>";
                }

                //hd 12737 31/1/2008
                if (!in_array($login_fabrica, array(11,172))) { // HD 92774
                    echo "<td>"; fecho ("nf",$con,$cook_idioma); echo "</td>";
                }
                echo "<td>"; fecho ("ab",$con,$cook_idioma); echo "</td>";

                if (in_array($login_fabrica, array(11,172))) { // HD 92774
                    echo "<td><acronym title='".traduz("Data do pedido",$con,$cook_idioma)."' style='cursor:help;'>DP</a></td>";
                }
                //HD 14927
                if (!$esconde_coluna_pre_os) {
                    if (usaDataConserto($login_posto, $login_fabrica)) {
                        echo "<td><acronym title='".traduz("data.de.conserto.do.produto",$con,$cook_idioma)."' style='cursor:help;'>DC</a></td>";
                    }

                    echo "<td><acronym title='".traduz("data.de.fechamento.registrada.pelo.sistema",$con,$cook_idioma)."' style='cursor:help;'>".traduz("fc",$con,$cook_idioma)."</a></td>";
                }

                if (in_array($login_fabrica, array(152,180,181,182))) {
                    echo "<td><acronym title='".traduz("data.de.abertura.registrada.pelo.sistema.x.data.nota.fiscal",$con,$cook_idioma)."' style='cursor:help;'>".traduz("tempo.defeito",$con,$cook_idioma)."</a></td>";
                }

                if (!$esconde_coluna_pre_os) {
                    if(in_array($login_fabrica, array(94, 115, 116, 117, 120, 201, 153,156,163,167,171,174,176,177,203)))
                        echo "<td>".strtoupper(traduz("tipo.de.atendimento"))."</td>";
                }

                if (in_array($login_fabrica, [167, 203]) && $posto_interno == true) {
                    echo "<td>".strtoupper(traduz("status.orcamento"))."</td>";
                }

                if (!$esconde_coluna_pre_os) {
                    //HD 211825: Filtrar por tipo de OS: Consumidor/Revenda
                    if($login_fabrica == 87)
                        echo "<td>".strtoupper(traduz("tipo.de.atendimento"))."</td>";
                    else
                        echo "<td nowrap>C / R</td>";
                }

                if (in_array($login_fabrica, array(169, 170))) {
               	 	echo "<td>".strtoupper(traduz("revenda",$con,$cook_idioma))."</td>";
                	echo "<td>".strtoupper(traduz("cnpj.revenda",$con,$cook_idioma))."</td>";
                }

                echo "<td>".strtoupper(traduz("Consumidor/Revenda",$con,$cook_idioma))."</td>";

                if ($login_fabrica == 158) {
?>
                    <td><?php echo traduz("consumidor.cidade");?></td>
                    <td><?php echo traduz("consumidor.estado");?></td>
<?php
                }
                if ($login_fabrica == 165 && $_POST['tecnico']) {
                    echo "<td>".strtoupper(traduz("tecnico",$con,$cook_idioma))."</td>";
                }
                if (in_array($login_fabrica, array(11,172))) { // HD 92774
                    echo "<td>".strtoupper(traduz("telefone",$con,$cook_idioma))."</td>";
                }
                if($login_fabrica==3){
                    echo "<td>".strtoupper(traduz("marca",$con,$cook_idioma))."</td>";
                }
                if($login_fabrica==171){
                    echo "<td>".strtoupper(traduz("referencia.fabrica",$con,$cook_idioma))."</td>";
                }
                echo "<td>";
                if (in_array($login_fabrica, array(11,172))) { // HD 92774
                    echo strtoupper(traduz("referência",$con,$cook_idioma));
                }else{
                    echo strtoupper(traduz("produto",$con,$cook_idioma));
                }

                if($login_fabrica == 165){
                    echo "<td>".strtoupper(traduz("produto trocado",$con,$cook_idioma))."</td>";
                }
                echo "</td>";
                if($login_fabrica == 56){
                    echo "<td>".strtoupper(traduz("atendimento",$con,$cook_idioma))."</td>";
                }
                if($login_fabrica == 104){
                    echo "<td>".strtoupper(traduz("origem",$con,$cook_idioma))."</td>";
                    echo "<td>".traduz("protocolo")."</td>"; //HD-3139131
                    echo "<td>".traduz("codigo.postagem")."</td>"; //HD-3139131
                }
                if($login_fabrica==19 || ( in_array($login_fabrica,array(94,162)) && $posto_interno === true )){
                    if($login_fabrica == 19)
                        echo "<td>".strtoupper(traduz("atendimento",$con,$cook_idioma))."</td>";
                    echo "<td nowrap>".strtoupper(traduz("tecnico",$con,$cook_idioma))."</td>";
                }
                #SISTEMA RG
                if(!in_array($login_fabrica, array(3,46,87,115,116,117,120,201,121,122,123,124,125,127,128,129,134,136,137,141,142,143,144,138)) && !isset($novaTelaOs)){
                    if(in_array($login_posto, array(6359,4311)) || ($login_posto == 4262 && $login_fabrica == 6) || in_array($login_fabrica, array(11,172))){
                        echo "<td>".traduz("rg.produto",$con,$cook_idioma)."</td>";
                    }
                }elseif($login_fabrica == 137){
                    echo "<td>".traduz("CFOP", $con, $cook_idioma)."</td>";
                    echo "<td>".traduz("valor.unitario", $con, $cook_idioma)."</td>";
                    echo "<td style='min-width: 80px !important;'>".traduz("valor.total.nota", $con, $cook_idioma)."</td>";
                }elseif(in_array($login_fabrica, array(143))){
                    echo "<td>".traduz("horimetro",$con,$cook_idioma)."</td>";
                }

                if(in_array($login_fabrica, array(11,172)) AND strlen($btn_acao_pre_os) > 0){
                    echo "<td>".traduz("numero.de.postagem",$con, $cook_idioma)."</td>";
                }
                if($login_fabrica == 35 AND strlen($btn_acao_pre_os) > 0){
                    echo "<td>".traduz("codigo.postagem")."</td>";
                    echo "<td>Número Atendimento</td>";                                        
                    echo "<td>Código Rastreio</td>";  
                    echo "<td>Imprimir</td>";  
                } else if(!in_array($login_fabrica, [104,139,152,158,167,175,178,180,181,182,183,184,200,203])){ 
                    echo "<td>".strtoupper(traduz("codigo.postagem",$con,$cook_idioma))."</td>";
                }

                if(in_array($login_fabrica, array(115,116,117,120,201))){
                    echo "<td>KM</td>";
                }

                if($login_fabrica != 35){
                    echo "<td><img border='0' src='imagens/img_impressora.gif' alt='".traduz("imprimir.os")."'></td>";                    
                }

                /* if($login_fabrica == 52 && empty($btn_acao_pre_os) ){
                    echo "<td>";
                    echo strtoupper(traduz("motivo atraso",$con,$cook_idioma));
                    echo "</td>";
                } */

                if($login_fabrica == 1 ){
                    echo "<td><img border='0' width='20' heigth='20' src='imagens/envelope.png' alt='".traduz("carta.registrada")."'></td>";
                }

                if($login_fabrica == 6 and strlen(trim($sua_os))>0){
                    $sql_status = "SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = $sua_os ORDER BY data DESC LIMIT 1";
                    $res_status = pg_query($con,$sql_status);

                    if (pg_num_rows($res_status) > 0) {
                        $status_os = pg_fetch_result($res_status, 0, 'status_os');
                    }
                }

                if ($login_fabrica == 1) {
                    echo "<td>".traduz("item",$con,$cook_idioma)."</td>";
                    $colspan = "8";
                }else if (($login_fabrica == 96) or $login_fabrica == 91) {
                    $colspan = "7";
                }else if (in_array($login_fabrica, array(30,50,144,169,170,174))) {
                    $colspan = "8";
                }else if(usaDataConserto($login_posto, $login_fabrica)){
                    if($status_os == 190 and $login_fabrica == 6){
                        $colspan = "7";
                    }else{
                        $colspan = "6";
                    }
                }else{
                    $colspan = "5";
                }

                if(in_array($login_fabrica, array(3, 20, 52))){
                    $colspan = "7";
                }

                if($login_fabrica == 30){
                    $colspan = "10";
                    /**
                     * - Bloqueio de postos da ESMALTEC
                     * situados no estado do CEARÁ
                     * de abrir e fechar OS
                     */
                    $sql_estado = "
                        SELECT  tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.parametros_adicionais
                        FROM    tbl_posto_fabrica
                        WHERE   tbl_posto_fabrica.posto     = $login_posto
                        AND     tbl_posto_fabrica.fabrica   = $login_fabrica
                    ";

                    $res_estado = pg_query($con,$sql_estado);
                    $resultContatoEstado = pg_fetch_result($res_estado,0,contato_estado);

                    $json_parametros_adicionais = pg_fetch_result($res_estado,0,parametros_adicionais);
                    $array_parametros_adicionais = json_decode($json_parametros_adicionais);

                    $posto_digita_os_consumidor = $array_parametros_adicionais->digita_os_consumidor;
                }

                /**
                 * hd-6010107
                 * @author William Castro
                 *
                 * Não permitir que o Posto realize o fechamento da OS
                 */

                if ($login_fabrica == 177) $colspan = "4";

                if ($login_fabrica == 35 && !empty($cook_admin)) $colspan = "7";

                if ($login_fabrica == 178)  $colspan = '5';
                echo "<td colspan='$colspan' style='text-transform: uppercase;'>";
                    echo strtoupper(traduz("acoes",$con,$cook_idioma));
                echo "</td>";

            }

            if (strlen($btn_acao_pre_os)) {

                $hd_chamado = trim(pg_fetch_result($res,$i,hd_chamado));

                if (in_array($login_fabrica,array(7,30,52,96,151))) {
                    $hd_chamado_item    = trim(pg_fetch_result($res,$i,hd_chamado_item));
                }

                $sua_os          = trim(pg_fetch_result($res, $i, 'sua_os'));
                $serie           = trim(pg_fetch_result($res, $i, 'serie'));
                $type           = trim(pg_fetch_result($res, $i, 'type'));
                $nota_fiscal     = trim(pg_fetch_result($res, $i, 'nota_fiscal'));
                $abertura        = trim(pg_fetch_result($res, $i, 'data'));
                $dt_hr_abertura  = trim(pg_fetch_result($res, $i, 'dt_hr_abertura'));
                $consumidor_nome = trim(pg_fetch_result($res, $i, 'nome'));
                $consumidor_fone = trim(pg_fetch_result($res, $i, 'consumidor_fone'));
                $cliente_admin = trim(pg_fetch_result($res, $i, 'cliente_admin'));
                if($login_fabrica == 85){
                    $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
                    if(!empty($array_campos_adicionais)){
                        $campos_adicionais = json_decode($array_campos_adicionais);
                        if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                            $consumidor_nome = $campos_adicionais->nome_fantasia;
                        }
                    }
                }
                if ($login_fabrica == 43 or $login_fabrica == 14) {
                    $ordem_montagem  = trim(pg_fetch_result($res, $i, 'ordem_montagem'));
                    $codigo_postagem = trim(pg_fetch_result($res, $i, 'codigo_postagem'));
                }

                $marca_nome         = trim(pg_fetch_result($res, $i, 'marca_nome'));
                $produto_referencia = trim(pg_fetch_result($res, $i, 'referencia'));
                $produto_referencia_fabrica = trim(pg_fetch_result($res, $i, 'produto_referencia_fabrica'));
                $produto_descricao  = trim(pg_fetch_result($res, $i, 'descricao'));
                $consumidor_revenda_callcenter = pg_fetch_result($res, $i, 'consumidor_revenda');

                if(in_array($login_fabrica, array(11,172)) && strlen($btn_acao_pre_os) > 0){
                    $numero_postagem = trim(pg_fetch_result($res, $i, 'numero_postagem'));
                }

                if ($login_fabrica == 3) {
                    $protocolo = trim(pg_fetch_result($res, $i, 'ordem_montagem'));
                }

                if (in_array($login_fabrica, array(96))) {
                    $tipo_atendimento = trim(pg_fetch_result($res, $i, 'tipo_atendimento'));
                }

            } else {

                $os                 = trim(pg_fetch_result($res, $i, 'os'));
                $os_posto           = trim(pg_fetch_result($res, $i, 'os_posto'));
                $hd_chamado         = trim(pg_fetch_result($res, $i, 'hd_chamado'));
                $ordem_montagem     = trim(pg_fetch_result($res, $i, 'ordem_montagem'));
                $codigo_postagem    = trim(pg_fetch_result($res, $i, 'codigo_postagem'));
                $sua_os             = trim(pg_fetch_result($res, $i, 'sua_os'));
                $digitacao          = trim(pg_fetch_result($res, $i, 'digitacao'));
                $abertura           = trim(pg_fetch_result($res, $i, 'abertura'));
                $fechamento         = trim(pg_fetch_result($res, $i, 'fechamento'));
                $finalizada         = trim(pg_fetch_result($res, $i, 'finalizada'));
                $serie              = trim(pg_fetch_result($res, $i, 'serie'));
                $type               = trim(pg_fetch_result($res, $i, 'type'));
                $excluida           = trim(pg_fetch_result($res, $i, 'excluida'));
                $motivo_atraso      = trim(pg_fetch_result($res, $i, 'motivo_atraso'));
                $tipo_os_cortesia   = trim(pg_fetch_result($res, $i, 'tipo_os_cortesia'));
                $consumidor_revenda = trim(pg_fetch_result($res, $i, 'consumidor_revenda'));
                $consumidor_nome    = trim(pg_fetch_result($res, $i, 'consumidor_nome'));
                $cons_nome          = trim(pg_fetch_result($res, $i, 'consumidor_nome'));
                $consumidor_cidade  = trim(pg_fetch_result($res, $i, 'consumidor_cidade'));
                $consumidor_estado  = trim(pg_fetch_result($res, $i, 'consumidor_estado'));
                if($login_fabrica == 85){
                    $array_campos_adicionais = pg_fetch_result($res,$i,array_campos_adicionais);
                    if(!empty($array_campos_adicionais)){
                        $campos_adicionais = json_decode($array_campos_adicionais);
                        if($campos_adicionais->consumidor_cpf_cnpj == 'R'){
                            $consumidor_nome = $campos_adicionais->nome_fantasia;
                        }
                    }
                }

                if ($login_fabrica == 161) {
                    $serie = strtoupper($serie);
                }


        		if (isset($novaTelaOs)) {
        			$os_reparo = pg_fetch_result($res, $i, "os_numero");
        		}
                $atendimento_posto  = pg_fetch_result($res, $i, 'atendimento_posto');
                $cancelada          = pg_fetch_result($res, $i, "cancelada");
                $revenda_nome       = trim(pg_fetch_result($res, $i, 'revenda_nome'));
                $cnpj_revenda       = trim(pg_fetch_result($res, $i, 'revenda_cnpj'));

                if($login_fabrica == 165){
                    $produto_trocado  = pg_fetch_result($res, $i, 'produto_trocado');
                }

                if ($login_fabrica == 171) {
                    $auditoria_status = pg_fetch_result($res, $i, 'auditoria_status');
                }

                $codigo_posto       = trim(pg_fetch_result($res, $i, 'codigo_posto'));
                $posto_nome         = trim(pg_fetch_result($res, $i, 'posto_nome'));
                $impressa           = trim(pg_fetch_result($res, $i, 'impressa'));
                $extrato            = trim(pg_fetch_result($res, $i, 'extrato'));
                $os_reincidente     = trim(pg_fetch_result($res, $i, 'os_reincidente'));
                $valores_adicionais = trim(pg_fetch_result($res, $i, 'valores_adicionais'));    //
                $nota_fiscal        = trim(pg_fetch_result($res, $i, 'nota_fiscal'));//hd 12737 31/1/2008
                $nota_fiscal_saida  = trim(pg_fetch_result($res, $i, 'nota_fiscal_saida')); //
                $reincidencia       = trim(pg_fetch_result($res, $i, 'reincidencia'));
                $produto_referencia = trim(pg_fetch_result($res, $i, 'produto_referencia'));
                $produto_referencia_fabrica = trim(pg_fetch_result($res, $i, 'produto_referencia_fabrica'));
                $produto_descricao  = trim(pg_fetch_result($res, $i, 'produto_descricao'));
                $produto_voltagem   = trim(pg_fetch_result($res, $i, 'produto_voltagem'));
                $produto_id         = pg_fetch_result($res, $i, 'produto');
                $linha_id           = pg_fetch_result($res, $i, 'linha');
                $tipo_atendimento   = trim(pg_fetch_result($res, $i, 'tipo_atendimento'));  //
                $grupo_atendimento  = pg_fetch_result($res, $i, "grupo_atendimento");
                $tecnico_nome       = trim(pg_fetch_result($res, $i, 'tecnico_nome'));
                $tecnico_nome       = (empty($tecnico_nome))
                    ? trim(pg_fetch_result($res, $i, 'tecnico_nome_tbl'))
                    : $tecnico_nome;
                $nome_atendimento   = trim(pg_fetch_result($res, $i, 'descricao'));
                $admin              = trim(pg_fetch_result($res, $i, 'admin'));
                $sua_os_offline     = trim(pg_fetch_result($res, $i, 'sua_os_offline'));
                $rg_produto         = trim(pg_fetch_result($res, $i, 'rg_produto'));
                $linha              = trim(pg_fetch_result($res, $i, 'linha'));
                $consumidor_email   = trim(pg_fetch_result($res, $i, consumidor_email));
                $protocolo          = trim(pg_fetch_result($res, $i, ordem_montagem));
                $valor_km           = trim(pg_fetch_result($res, $i, valor_km));
                if ($login_fabrica == 74) {
                    $linha_nome = pg_fetch_result($res, $i, "linha_nome");
                }

                $descricao_orcamento = pg_fetch_result($res, $i, 'descricao_orcamento');
                $data_digitacao_banco = pg_fetch_result($res, $i, 'data_digitacao');
                $tempo_para_defeito = pg_fetch_result($res, $i, 'tempo_para_defeito');

                //HD391024
                $status_checkpoint = trim(pg_fetch_result($res,$i,'status_checkpoint'));

                if ($login_fabrica == 162) {
                    $tecnico_os = pg_fetch_result($res,$i,tecnico);
                }

                if($consumidor_revenda == "R" && !in_array($login_fabrica, [156,178])){
                    $consumidor_nome = $revenda_nome;
                }

                $os_status_cancelada = $cancelada; //hd_chamado=3049906
                $display_button_cancelado = "style='display:block'";
                #HD 307124 INICIO
                if (in_array($login_fabrica, array(81, 114))) {

                    $sql_cancelada = "select cancelada from tbl_os where os=$os";

                    $res_cancelada = pg_query($con,$sql_cancelada);
                    $os_cancelada  = pg_result($res_cancelada, 0, 'cancelada');

                    if ($os_cancelada == 't'){
                        $display_button_cancelado = "style='display:none'";
                    } else {
                        $display_button_cancelado = "style='display:block'";
                    }

                }
                #HD 307124 FIM

                if($login_fabrica==3){
                    $marca     = trim(pg_fetch_result($res,$i,marca));
                    $marca_nome = trim(pg_fetch_result($res,$i,marca_nome));
                }
                if($login_fabrica==86){
                    $defeito_reclamado     = pg_fetch_result($res,$i,defeito_reclamado);
                }

                if(in_array($login_fabrica, array(72,161,167,177,203))){
                    $defeito_reclamado_descricao        = pg_fetch_result($res,$i,defeito_reclamado_descricao);
                    $defeito_constatado                 = pg_fetch_result($res,$i,defeito_constatado);
                    $solucao                            = pg_fetch_result($res,$i,solucao_os);
                }

                //HD 13239 14927
                if (usaDataConserto($login_posto, $login_fabrica)) {
                    $data_conserto=trim(pg_fetch_result($res,$i,data_conserto));
                }

                $recolhimento = pg_fetch_result($res, $i, 'recolhimento');

                /**
                 *
                 *  HD 739078 - pegar o último status entre os seguinte:
                 *
                 *       122 | OS aberta 90 dias - Justific.  |         2
                 *       123 | OS aberta 90 dias - Alteração  |         3
                 *       126 | OS aberta 90 dias - Cancelada  |         4
                 *       120 | OS aberta 90 dias - Bloqueada  |         1
                 *
                 */
                if ($login_fabrica == 15) {
                    $os_bloq_tipo = '120, 122, 123, 126';
                    $sql_status   = "SELECT status_os FROM tbl_os_status WHERE status_os IN ($os_bloq_tipo) AND os = $os ORDER BY data DESC LIMIT 1";
                } else {
                    //HD 379597
                    $sql_status = "SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = $os ORDER BY data DESC LIMIT 1";
                }

                $res_status = pg_query($con,$sql_status);

                if (pg_num_rows($res_status) > 0) {
                    $status_os = pg_fetch_result($res_status, 0, 'status_os');

                    if($status_os == 159){
                        $sql_status = "SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = $os AND status_os NOT IN (158,159,160) ORDER BY data DESC LIMIT 1";
                        $res_status = pg_query($con,$sql_status);
                        $status_os = pg_fetch_result($res_status, 0, 'status_os');
                    }

                } else {
                    $status_os = null;
                }

            }

            if(in_array($login_fabrica,array(115,116,117,120,201)) AND in_array($status_os, array(64,99,100,101))){
                if(in_array($status_os, array(99,101))){
                    $sql_status = "SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = $os AND status_os IN(62,64,81) ORDER BY data DESC LIMIT 1";
                }else{
                    $sql_status = "SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = $os AND status_os IN(98,99,100,101) ORDER BY data DESC LIMIT 1";
                }
                $res_status = pg_query($con,$sql_status);
                if(pg_num_rows($res_status) > 0){
                    $status_os = pg_fetch_result($res_status, 0, 'status_os');
                }
            }

            if ($i % 2 == 0) {
                $cor   = "#F1F4FA";
                $botao = "azul";
            } else {
                $cor   = "#F7F5F0";
                $botao = "amarelo";
            }

            ##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####

            if ($login_fabrica == 15 && strlen($fechamento) != 0 && $reincidencia != "t") {

                if (strlen($admin) > 0) $cor = "#999933";

            }

            unset($marca_reincidencia);

            if ($reincidencia =='t' and $status_os <> 86 and $login_fabrica <> 6) {

                if ($login_fabrica == 87) $cor = "#40E0D0";
                else                      $cor = "#D7FFE1";

                if (isset($novaTelaOs)) {
                    $cor = "#ff9922";
                }

                $marca_reincidencia = 'sim';

            }

            if ($excluida == "t" and $login_fabrica <> 6) $cor = "#FF0000";

            if ($login_fabrica == 20 AND $excluida == "t") {
                $cor = "#CACACA";
            }

            $vintecincodias = "";

            //hd 3646 28/08/07 tectoy nao aparece que é reincidente para posto
            // OSs abertas há mais de 25 dias sem data de fechamento
            if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {

                $aux_abertura = fnc_formata_data_pg($abertura);

                $sqlX         = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
                $resX         = pg_query($con, $sqlX);
                $aux_consulta = pg_fetch_result($resX,0,0);

                $sqlX      = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                $resX      = pg_query($con, $sqlX);
                $aux_atual = pg_fetch_result($resX,0,0);

                if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {

                    if ($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                    $vintecincodias = "sim";

                }

            }

            /*IGOR - HD: 44202 - 22/10/2008 */
            if ($login_fabrica == 3 AND strlen($os) > 0) {

                $sqlI = "SELECT  status_os
                        FROM    tbl_os_status
                        WHERE   os = $os
                        AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
                        ORDER BY data DESC LIMIT 1";

                $resI = pg_query($con, $sqlI);

                if (pg_num_rows($resI) > 0) {

                    $status_os = trim(pg_fetch_result($resI,0,status_os));

                    if ($status_os == 126 || $status_os == 143) {

                        $cor      = "#FF0000";
                        $excluida = "t";

                    }

                }

            }

            if ($status_os == "62")  $cor="#FFCCCC";
            if ($status_os == "72")  $cor="#FFCCCC";
            if ($status_os == "87")  $cor="#FFCCCC";
            if ($status_os == "116") $cor="#FFCCCC";

            if(($auditoria_unica == true or $login_fabrica == 42) and !empty($os)){
            	$sqlAuditoria = "SELECT tbl_auditoria_os.liberada,
			            tbl_auditoria_os.cancelada,
						tbl_auditoria_os.reprovada,
                        tbl_auditoria_os.observacao,
						tbl_auditoria_os.auditoria_status
			        FROM tbl_auditoria_os
			            JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
			            JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
			        WHERE tbl_auditoria_os.os = $os ORDER BY data_input DESC";
			    $resAuditoria = pg_query($con,$sqlAuditoria);

                if(pg_num_rows($resAuditoria) > 0){

                    if(in_array($login_fabrica, [167, 203])){
                        $resultAuditoria = pg_fetch_all($resAuditoria);
                    }

			        $liberada  = pg_fetch_result($resAuditoria, 0, "liberada");
                    $reprovada = pg_fetch_result($resAuditoria, 0, "reprovada");
                    $auditoria_status = pg_fetch_result($resAuditoria, 0, "auditoria_status");

                    if ($login_fabrica == 42) {

                        $observacao = pg_fetch_result($resAuditoria, 0, "observacao");
                        if (empty($liberada) && $observacao == 'Quantidade de OSs abertas no mês atual é maior que o dobro da média.') {
                        }

                    }

                    if($login_fabrica <> 42) {
						$datacancelada = pg_fetch_result($resAuditoria, 0, "cancelada");
						if($liberada == "" && $datacancelada == "" && $reprovada == ""){
							$cor="#FFCCCC";
						}else{
							if($liberada != "" && $datacancelada == "" && $reprovada == "" && (in_array($login_fabrica, array(152,180,181,182)))){
								$cor = "#CFCFCF";
							}elseif($liberada != "" && $datacancelada == "" && $reprovada == "" && isset($novaTelaOs)){
								$cor = "#AAFFAA";
							}elseif($liberada != "" && $datacancelada == "" && $reprovada == ""){
								$cor = "#CCFFFF";
							}else{
								$cor = "";
							}
						}
					}
				}else{
					$sem_auditorica = true;
				}

                if(in_array($login_fabrica, [167, 203])){
                    $sqlTipoAtendimento = "
                        SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento
                    ";
                    $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

                    if (pg_fetch_result($resTipoAtendimento, 0, 'descricao') == "Garantia Recusada") {
                        $bloqueia_itens = 'true';
                    } else {
                        $bloqueia_itens = 'false';
                        /*$sqlAuditoriaGarantia = "
                        SELECT liberada FROM tbl_auditoria_os WHERE os = $os AND auditoria_status = 6 AND observacao = 'Auditoria de Garantia' ORDER BY auditoria_os DESC limit 1";
                        $resAuditoriaGarantia = pg_query($con, $sqlAuditoriaGarantia);
                        if (pg_fetch_result($resAuditoriaGarantia, 0, 'liberada') != "") {
                            $bloqueia_itens = 'true';
                        } else {
                            $bloqueia_itens = 'false';
                        }*/
                    }

                    if($excluida == "t"){
                        $bloqueia_itens = 'true';
                    }


                }
	        }

            if(in_array($login_fabrica, [104,123])){ //hd_chamado=2517023
                $trava_finalizar_vonder = 'f';
                $trava_acoes_positec = 'f';
                $sqlAuditoria = "SELECT tbl_auditoria_os.liberada,
                        tbl_auditoria_os.cancelada,
                        tbl_auditoria_os.reprovada,
                        tbl_auditoria_os.os
                    FROM tbl_auditoria_os
                        JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = $login_fabrica
                        JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                    WHERE tbl_auditoria_os.os = $os
                    AND tbl_auditoria_os.liberada IS NULL
                    AND tbl_auditoria_os.cancelada IS NULL
                    AND tbl_auditoria_os.reprovada IS NULL
                    ORDER BY data_input DESC";
                $resAuditoria = pg_query($con,$sqlAuditoria);
                if(pg_num_rows($resAuditoria) > 0){                    

                    $liberada  = pg_fetch_result($resAuditoria, 0, "liberada");
                    $cancelada = pg_fetch_result($resAuditoria, 0, "cancelada");
                    $reprovada = pg_fetch_result($resAuditoria, 0, "reprovada");

                    if($login_fabrica == 104){
                        
                        $trava_finalizar_vonder = 't';

                        if($liberada == "" && $cancelada == "" && $reprovada == ""){
                            $cor="#FFCCCC";
                        }else{
                            if($liberada != "" && $cancelada == "" && $reprovada == ""){
                                $cor = "#CCFFFF";
                            }else{
                                $cor = "";
                            }
                        }
                    }else{
                        if(empty($liberada)){
                            $trava_acoes_positec = 't';                            
                        }
                    }
                    
                }
            }

            if ($login_fabrica == 163) {
                $trava_finalizar = 't';

                $sql_tipo_os = "SELECT fora_garantia
                            FROM tbl_os
                                JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                            WHERE tbl_os.os = {$os}";
                $res_tipo_os = pg_query($con,$sql_tipo_os);

                if (pg_num_rows($res_tipo_os) > 0) {
                    $fora_garantia = pg_fetch_result($res_tipo_os, 0, fora_garantia);
                }

                // validação posto não pode ser interno e nem revenda para a OS cair em auditoria
                $sql_posto_tipo = " SELECT tipo_revenda,posto_interno
                                        FROM tbl_posto_fabrica
                                            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                                        WHERE posto = $login_posto
                                            AND tbl_posto_fabrica.fabrica = $login_fabrica";
                $res_posto_tipo = pg_query($con, $sql_posto);

                if (pg_num_rows($res_posto_tipo) > 0) {
                    $posto_tipo_interno = pg_fetch_result($res_posto_tipo, 0, posto_interno);
                    $posto_tipo_revenda = pg_fetch_result($res_posto_tipo, 0, tipo_revenda);
                }

                if ($posto_tipo_interno == "t" OR $posto_tipo_revenda == "t") {
                    $trava_finalizar = 'f';
                }
            }

            if ($status_os == 179 && $login_fabrica == 91) {
                $cor="#FFCCCC";
            }

            if ($login_fabrica == 91 && $status_os == 13) {
                $cor = "#CB82FF";
            }

            if ($status_os == "120" || $status_os=="140")  $cor="#FFCCCC"; //HD: 44202 e 207142
            if ($status_os == "122" || $status_os=="141")  $cor="#FFCCCC"; //HD: 44202 e 207142

            if($login_fabrica == 87 AND $cor=="#FFCCCC") $cor = "#FFA5A4";

            if (in_array($status_os, array(158)))  $cor="#FFCCCC";

            if ($status_os == "64"  && strlen($fechamento) == 0) $cor = "#CCFFFF";
            if ($status_os == "73"  && strlen($fechamento) == 0) $cor = "#CCFFFF";
            if ($status_os == "117" && strlen($fechamento) == 0) $cor = "#CCFFFF";

            if (in_array($login_fabrica, array(141,144))) {
                switch ($status_os) {
                    case 192:
                        $cor = "#FFCCCC";
                        break;

                    case 193:
                        $cor = "#CCFFFF";
                        break;

                    case 194:
                        $cor = "#CB82FF";
                        break;
                }
            }

            if ($login_fabrica == 87 AND $cor == "#CCFFFF") $cor = "#FEFFA4";

            if ($status_os=="65") $cor="#FFFF99";
            //HD391024
            // if($login_fabrica == 96){
                // if($status_checkpoint == '1') $cor = "#CCCCFF";
                // if($status_checkpoint == '5') $cor = "#FFFF66";
                // if($status_checkpoint == '6') $cor = "#33CC00";
                // if($status_checkpoint == '7') $cor = "#C94040";
            // }

            //HD391024
            if($login_fabrica == 96 and strlen($btn_acao_pre_os) > 0){
                if($tipo_atendimento == '92') $cor = "#FFFF66";
                if($tipo_atendimento == '93') $cor = "#C94040";
                if($tipo_atendimento == '94') $cor = "#33CC00";
            }

            if($status_os == "175"){
                $cor = "#A4A4A4";
            }

            //HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
            if (in_array($login_fabrica, array(81, 114)) && strlen($os)) {
                $sql = "SELECT troca_revenda FROM tbl_os_troca WHERE os=$os";
                $res_troca_revenda = pg_query($con, $sql);

                if (pg_num_rows($res_troca_revenda)) {
                    $troca_revenda = pg_result($res_troca_revenda, 0, troca_revenda);
                } else {
                    $troca_revenda = "";
                }
            }

            if ($troca_revenda == 't') {
                $cor = "#d89988";
            }

            if($login_fabrica==50){
                $sqlI = "SELECT  status_os
                        FROM    tbl_os_status
                        WHERE   os = $os
                        AND status_os IN (101, 104)
                        ORDER BY data DESC LIMIT 1";
                $resI = @pg_query ($con,$sqlI);
                if (@pg_num_rows ($resI) > 0){
                    $status_os = trim(pg_fetch_result($resI,0,status_os));
                    if($status_os==103 or $status_os==104){
                        $cor="#FF9933";
                    }
                }

                if($excluida=='t'){
                    $cor="#FFE1E1";
                }
            }


            if($login_fabrica == 1){
                if(strlen($tipo_atendimento) > 0) $cor = "#FFCC66";
            }

            // CONDIÇÕES PARA NKS - INÍCIO
            if($login_fabrica == 45 || isset($novaTelaOs)){//HD 14584 26/2/2008
                if ($login_fabrica == 45) {
                    if(strlen($data_conserto)>0){
                        $cor = "#FFCEFF";
                    }
                }
                if (strlen(trim($os)) > 0){
                    $sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
                    $resX = pg_query($con,$sqlX);
                    if(pg_num_rows($resX)==1){
                        $cor = "#FFCC66";
                        if(pg_fetch_result($resX,0,ressarcimento)=='t'){
                            $cor = "#CCCCFF";
                        }
                    }
                }
            }
            // CONDIÇÕES PARA NKS - FIM

            // CONDIÇÕES PARA INTELBRÁS - INÍCIO
            if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
                $aux_abertura = fnc_formata_data_pg($abertura);

                $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
                $resX = pg_query($con,$sqlX);
                $aux_consulta = pg_fetch_result($resX,0,0);

                $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                $resX = pg_query($con,$sqlX);
                $aux_atual = pg_fetch_result($resX,0,0);

                if ($aux_consulta < $aux_atual && strlen($fechamento) == 0){
                    if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                }

                $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                $resX = pg_query($con,$sqlX);
                $aux_consulta = pg_fetch_result($resX,0,0);

                $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                $resX = pg_query ($con,$sqlX);
                $aux_atual = pg_fetch_result ($resX,0,0);

                if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
            }
            // CONDIÇÕES PARA INTELBRÁS - FIM

            // CONDIÇÕES PARA BLACK & DECKER - INÍCIO
            // Verifica se não possui itens com 5 dias de lançamento
            if ($login_fabrica == 1) {
                $aux_abertura = fnc_formata_data_pg($abertura);

                $sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
                $resX = pg_query($con,$sqlX);
                $data_hj_mais_5 = pg_fetch_result($resX,0,0);

                $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
                $resX = pg_query ($con,$sqlX);
                $aux_consulta = pg_fetch_result($resX,0,0);

                $sql = "SELECT COUNT(tbl_os_item.*) AS total_item
                        FROM tbl_os_item
                        JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
                        WHERE tbl_os.os = $os
                        AND   tbl_os.data_abertura::date >= '$aux_consulta'";
                $resItem = pg_query($con,$sql);

                $itens = pg_fetch_result($resItem,0,total_item);

                if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

                $mostra_motivo = 2;
            }

            // OS com mais de 7 dias sem lançamento de peças
            if ($login_fabrica == 24) {
                $sql_7_dias = "SELECT os FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica AND JSON_FIELD('os_7_dias_sem_peca', campos_adicionais) = 'true'";
                $res_7_dias = pg_query($con, $sql_7_dias);
                if (pg_num_rows($res_7_dias) > 0) {
                    $cor = "#54A8AE";    
                }
            }

            // Verifica se está sem fechamento há 20 dias ou mais da data de abertura
            if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
                $aux_abertura = fnc_formata_data_pg($abertura);

                $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
                $resX = pg_query($con,$sqlX);
                $aux_consulta = pg_fetch_result($resX,0,0);

                $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                $resX = pg_query($con,$sqlX);
                $aux_atual = pg_fetch_result($resX,0,0);

                if ($consumidor_revenda != "R") {
                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
                        $mostra_motivo = 1;
                        if($login_fabrica == 87) $cor = "#A4B3FF"; else $cor = "#91C8FF";
                    }
                }
            }

            if (strlen($btn_acao_pre_os) > 0) {

                // OSs abertas há menos de 24 horas sem data de fechamento
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 30) {

                    $sqlX = "SELECT TO_CHAR('$dt_hr_abertura'::timestamp + INTERVAL '24 hours','YYYY-MM-DD HH24:MI:SS')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_timestamp,'YYYY-MM-DD HH24:MI:SS');";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta >= $aux_atual) {
                        $cor = "#33CC00";
                        $vintequatrohoras = "sim";
                        $smile = 'admin/js/fckeditor/editor/images/smiley/msn/regular_smile.gif';
                    }

                }

                // OSs abertas há mais de 24 horas e menor que 72 sem data de fechamento
                // maior que 72 horas sem data de fechamento
                if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 30) {

                    //$dt_hr_abertura = '2010-06-11 16:04:23';//data de teste
                    $sqlX = "SELECT TO_CHAR('$dt_hr_abertura'::timestamp + INTERVAL '72 hours','YYYY-MM-DD HH24:MI:SS')";
                    $resX = pg_query ($con,$sqlX);
                    $aux_consulta = pg_fetch_result($resX,0,0);

                    $sqlX = "SELECT TO_CHAR(current_timestamp,'YYYY-MM-DD HH24:MI:SS');";
                    $resX = pg_query ($con,$sqlX);
                    $aux_atual = pg_fetch_result ($resX,0,0);

                    if ($aux_consulta <= $aux_atual) {
                        $cor = "#FF0000";//maior que 72
                        $smile = 'admin/js/fckeditor/editor/images/smiley/msn/angry_smile.gif';
                    } else if ($vintequatrohoras != 'sim' && $aux_consulta > $aux_atual) {
                        $cor = "#FFFF66";//menor que 72
                        $smile = 'admin/js/fckeditor/editor/images/smiley/msn/whatchutalkingabout_smile.gif';
                    }

                }

            }

            // Se estiver acima dos 30 dias, não exibirá os botões
            if (strlen($fechamento) == 0 && $login_fabrica == 1) {
                $aux_abertura = fnc_formata_data_pg($abertura);

                $sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
                $resX = pg_query($con,$sqlX);
                $aux_consulta = pg_fetch_result($resX,0,0);

                $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                $resX = pg_query($con,$sqlX);
                $aux_atual = pg_fetch_result($resX,0,0);

                if ($consumidor_revenda != "R"){
                    if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
                        $mostra_motivo = 1;
                        $cor = "#FF0000";
                    }
                }
            }
            // CONDIÇÕES PARA BLACK & DECKER - FIM

            //STATUS DE TROCA HD 72717
            if ($login_fabrica == 3 AND strlen($os) > 0) {

                $sqlT = "SELECT os_troca FROM tbl_os_troca WHERE os = $os";
                $resT = pg_query($con,$sqlT);

                if (pg_num_rows($resT) == 1) {
                    $cor = "#FFCC66";
                }

            }

            if ($login_fabrica == 94 AND strlen($os) > 0) {

                $sqlT = "SELECT os FROM tbl_os_campo_extra WHERE os = $os";
                $resT = pg_query($con, $sqlT);

                if (pg_num_rows($resT)) {
                    $cor = "silver";
                }

            }

            if ($vintecincodias == 'sim' and $marca_reincidencia == 'sim') {
                if($login_fabrica == 87) $cor = "#D2D2D2"; else $cor = "#CC9900";
            }
            if ($os) {
                if (in_array($login_fabrica, array(52, 151))){
                    $sql = "SELECT hd_chamado FROM tbl_os WHERE os=$os";
                    $res_callcenter = pg_query($con, $sql);
                    if (pg_num_rows($res_callcenter)) {
                        $hd_chamado = pg_result($res_callcenter, 0, hd_chamado);
						if(!empty($hd_chamado)) {
							$cor = "#FFAA33";
						}
                    }

                } else if (in_array($login_fabrica, array(30, 50))) {
                    $sql = "SELECT  tbl_hd_chamado.hd_chamado
                            FROM    tbl_hd_chamado
                            JOIN    tbl_hd_chamado_extra    ON  tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                                                            AND tbl_hd_chamado.titulo <> 'Help-Desk Posto'
                            WHERE   tbl_hd_chamado_extra.os = $os
                    ";
                    $res_callcenter = pg_query($con, $sql);
                    if (pg_num_rows($res_callcenter)) {
                        $cor = "#FFAA33";
                        $hd_chamado = pg_result($res_callcenter, 0, hd_chamado);
                    }
                }else{

                    $sql = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os=$os";
                    $res_callcenter = pg_query($con, $sql);
                    if (pg_num_rows($res_callcenter)) {

                        if($login_fabrica == 42){ //hd_chamado=2816974
                            $rows_hd = pg_num_rows($res_callcenter);
                            $hds = array();
                            for ($c=0; $c < $rows_hd; $c++) {
                                $hdChamado[] = pg_fetch_result($res_callcenter, $c, 'hd_chamado');
                            }
                        }else{
                            if (isset($novaTelaOs)) {
                                $cor = "#CCC";
                            } else {
                                $cor = "#FFAA33";
                            }
                            if ($hd_chamado == "") {
                                $hd_chamado = pg_result($res_callcenter, 0, hd_chamado);
                            }
                        }
                    }
                }

                if($login_fabrica == 164 && $posto_interno == true){

                    $sql_os_troca = "SELECT os_troca FROM tbl_os_troca WHERE os = {$os} AND fabric = {$login_fabrica} ";
                    $res_os_troca = pg_query($con, $sql_os_troca);

                    if(pg_num_rows($res_os_troca) > 0){
                        $cor = "#D6D6D6";
                    }

                }

            }
            // CONDIÇÕES PARA GELOPAR - INÍCIO
            if($login_fabrica==85 AND strlen($os)>0){
                $sqlG = "SELECT
                            interv.os
                        FROM (
                            SELECT
                            ultima.os,
                            (
                                SELECT status_os
                                FROM tbl_os_status
                                WHERE status_os IN (147)
                                AND tbl_os_status.os = ultima.os
                                ORDER BY data
                                DESC LIMIT 1
                            ) AS ultimo_status
                            FROM (
                                    SELECT os FROM tbl_os WHERE tbl_os.os = $os
                            ) ultima
                        ) interv
                        WHERE interv.ultimo_status IN (64,147);";
                        #echo nl2br($sqlG);
                $resG = pg_exec($con,$sqlG);

                if(pg_numrows($resG)>0){
                    $cor = "#AEAEFF";
                }
            }
            // CONDIÇÕES PARA GELOPAR - FIM

            /*
             *
             * HD 2611338 - Implantação Wap
             * Cor da linha para OS Reprovada da Auditoria de Fotos de Peças
             *
             */
           if (in_array($login_fabrica, array(157))) {
                $sqlReprov = "SELECT tbl_auditoria_os.reprovada
                                        FROM tbl_auditoria_os
                                        INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                                        WHERE tbl_auditoria_os.os = $os
                                        --AND tbl_auditoria_status.fabricante IS TRUE
                                        AND tbl_auditoria_os.reprovada IS NOT NULL
                                        --AND fn_retira_especiais(tbl_auditoria_os.observacao) = 'OS em Auditoria de Foto de Peca'
                                        ORDER BY tbl_auditoria_os.data_input DESC";
                                        
                $resReprov = pg_query($con, $sqlReprov);

                if (pg_num_rows($resReprov) > 0) {
                    $cor = "#FF0000";
                }
            }

            if ($login_fabrica == 148 && !empty($finalizada)) {
                $sqlReprov = "  SELECT tbl_auditoria_os.reprovada
                                FROM tbl_auditoria_os
                                JOIN tbl_os ON tbl_auditoria_os.os = tbl_os.os
                                WHERE tbl_auditoria_os.os = $os
                                AND tbl_auditoria_os.reprovada NOTNULL
                                ORDER BY tbl_auditoria_os.data_input DESC";
                                        
                $resReprov = pg_query($con, $sqlReprov);

                if (pg_num_rows($resReprov) > 0) {
                    $cor = "#FF0000";
                }
            }

            // Fim HD 2611338

            ##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

            if (strlen($sua_os) == 0) $sua_os = $os;
            if ($login_fabrica == 1) $xsua_os =  $codigo_posto.$sua_os ;

            if($login_fabrica == 40 AND $status_os == 118) $cor = "#BFCDDB";



            if ($login_fabrica == 3 && $status_os == 174) {
                $cor = "#CB82FF";
            }

            if($login_fabrica == 151 && $status_checkpoint == 9){
                $sql = "
                    SELECT  data_conserto
                    FROM    tbl_os
                    WHERE   os = $os
                ";
                $resDC = pg_query($con,$sql);
                $data_conserto = pg_fetch_result($resDC,0,data_conserto);
                if($data_conserto == ""){
                    $cor = "#7CFC00";
                }
            }

            if ($excluida == "t" and $login_fabrica <> 6 and !isset($cancelaOS)) $cor = "#FF0000";
            if ($excluida == "t" and $login_fabrica == 30) $cor = "#CF0000";
            
	    // Adicionando Britãnia HD 4027840
            if ($cancelada == 't' && isFabrica(3, 74, 152, 180, 181, 182)) {
                $cor = '#FF0000';
            }

            if ($login_fabrica == 131){
                $sqlReprovada = "SELECT tbl_auditoria_os.auditoria_os
                    FROM tbl_auditoria_os
                    WHERE os = $os
                    AND reprovada IS NOT NULL
                    ORDER BY reprovada DESC LIMIT 1";
                $resReprovada = pg_query($con, $sqlReprovada);

                if (pg_num_rows($resReprovada) > 0) {
                    $cor = $cor_os_reprovada_auditoria;
                }
            }

            echo "<tr class='Conteudo' id='conteudo_{$os}' height='15' bgcolor='$cor' align='left'>";

            //HD 371911
            if ($excluida == "f" || strlen($excluida) == 0 and strlen($btn_acao_pre_os)==0 and $login_fabrica <> 30) {
                echo '<td align="center"><input type="checkbox" value="'.$os.'" name="imprimir_os[]" id="impressao_'.$os.'" class="imprimir frm"/></td>';                
            }else{
                if($login_fabrica <> 30){
                    echo "<td>&nbsp;</td>";
                }
            }

            //HD 214236: Legendas para auditoria de OS da Intelbras. Como vão ser auditadas todas as OS, criei uma coluna nova para que sinalize o status da auditoria
            if ($login_fabrica == '50') {
                $sql = "SELECT * from tbl_os_status where os = $os and status_os = 20 order by data desc limit 1";
                $qry = pg_query($con, $sql);

                if (pg_num_rows($qry) > 0) {
                    $auditoria_travar_opcoes = true;
                }
            }

            if (in_array($login_fabrica,array(14,43,104,105))) {
                $sql = "
                SELECT
                liberado,
                cancelada,
                justificativa

                FROM
                tbl_os_auditar

                WHERE
                os_auditar IN (
                    SELECT
                    MAX(os_auditar)

                    FROM
                    tbl_os_auditar

                    WHERE
                    os=$os
                )
                ";
                $res_auditoria = @pg_query($con, $sql);

                if (strlen(pg_errormessage($con)) == 0 && pg_num_rows($res_auditoria)) {
                    $liberado      = pg_result($res_auditoria, 0, liberado);
                    $cancelada     = pg_result($res_auditoria, 0, cancelada);
                    $justificativa = pg_result($res_auditoria, 0, justificativa);

                    if ($liberado == 'f') {
                        if ($cancelada == 'f') {
                            $legenda_status = "em análise";
                            $cor_status = "#FFFF44";
                            $auditoria_travar_opcoes = true;
                        }
                        elseif ($cancelada == 't') {
                            $legenda_status = "reprovada";
                            $cor_status = "#FF7744";
                        }
                        else {
                            $legenda_status = "";
                            $cor_status = "";
                        }
                    }
                    elseif ($liberado == 't') {
                        $legenda_status = "aprovada";
                        $cor_status = "#44FF44";
                    }
                    else {
                        $legenda_status = "";
                        $cor_status = "";
                    }
                }
                else {
                    $legenda_status = "";
                    $cor_status = "";
                }

                echo "<td style='background:$cor_status' align='center' nowrap onclick='alert(\"$justificativa\");'><acronym style='cursor:help' title='".traduz("clique.aqui.para.ver.a.justificativa")."'>$legenda_status</acronym></td>";
            }
            //HD 214236::: FIM :::

            if ($esconde_coluna_pre_os) {
                echo "<td style='text-align: center; width: 100px;' >{$hd_chamado}</td>";
            } elseif ($btn_acao_pre_os && in_array($login_fabrica, [169,170,177,184,200])) {
                echo "<td style='text-align: center; width: 100px;'>{$hd_chamado}</td>";
            } else {
                echo "<td  width='100' nowrap>" ;

                // Verifica se OS está em AUD
                if (in_array($login_fabrica, [167, 203])) {
                    $sql_aud = "SELECT os FROM tbl_auditoria_os where os = $os AND liberada IS NULL AND cancelada IS NULL AND reprovada IS NULL";
                    $res_aud = pg_query($con, $sql_aud);
                    if (pg_num_rows($res_aud) > 0) {
                        $status_checkpoint = 37;
                    }
                }

                exibeImagemStatusCheckpoint($status_checkpoint);

                if ($login_fabrica == 1){ 
                    echo $xsua_os; 
                }else{ 
                    if (in_array($login_fabrica, array(178))){
                        $os_link = explode("-", $sua_os);
                        echo "<a target='_blank' href='os_revenda_press.php?os_revenda=".$os_link[0]."'> $sua_os </a>";
                    }else if ($login_fabrica == 183) {
                        $os_link = explode("-", $sua_os);
                        if (count($os_link) > 1) {
                            echo "<a target='_blank' href='os_revenda_press.php?os_revenda=".$os_link[0]."'> $sua_os </a>";
                        }else{
                            echo "<a target='_blank' href='os_press.php?os=".$sua_os."'> $sua_os </a>";
                        } 
                    }else{
                        if($login_fabrica == 191 AND strlen($btn_acao_pre_os) > 0){
                            echo (!empty($cliente_admin)) ? "Revenda" : "Callcenter";
                        }else{
                            echo $sua_os;
                        }
                    }
                }
                if ($fCancelaOS && $cancelada == "t") {
                    echo " (Cancelada)";
                }
                echo "</td>";
            } 
            if (in_array($login_fabrica,array(3,14,30,42,43,50,52,80,96))) { //hd_chamado=2816974 Adicionada fabrica 42
                if($login_fabrica == 3){
                    if (strlen($hd_chamado) > 0) {
                        echo "<td align='center'><a href='helpdesk_cadastrar.php?hd_chamado={$hd_chamado}' target='_blank'>{$hd_chamado}</a></td>";
                    }else{
                        echo "<td align='center'><a target='_blank' href='helpdesk_cadastrar.php?os={$sua_os}'><img border='0' src='imagens/btn_novo_azul.gif'></a></td>";
                    }

                }else{

                    if($login_fabrica == 42){ //hd_chamado=2816974
                        $hd_chamado = implode('<br>', $hdChamado);
                    }
                    echo "<td>$hd_chamado</td>";
                }
            }

            if ($login_fabrica == 14 or $login_fabrica == 43) {
                echo "<td>$ordem_montagem</td>";
                echo "<td>$codigo_postagem</td>";
            }

            //HD 8431 OS interna para Argentina
            if(in_array($login_fabrica, array(10, 19, 158)) || ($login_fabrica == 20 && $login_pais=='AR')){
                if ($login_fabrica == 158) {
                    echo "<td nowrap>" . $os_posto . "</td>";
               } else {
                    echo "<td nowrap>" . $sua_os_offline . "</td>";
               }
            }

            if($login_fabrica == 19){ // hd_chamado=2881143
                echo "<td nowrap><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto' target='_blank'>".$extrato."</a></td>";
            }

            if ( !in_array($login_fabrica, array(127,145))) { // HD-2296739
                echo "<td width='55' nowrap>" . $serie . "</td>";
            }

            if (in_array($login_fabrica, array(160)) or $replica_einhell) { // HD-2296739
                echo "<td width='55' nowrap>" . $type . "</td>";
            }

            //hd 12737 31/1/2008
            if (!in_array($login_fabrica, array(11,172))) { // HD 92774
                echo "<td nowrap>" ;
                echo $nota_fiscal;
                echo "</td>";
            }

            echo "<td nowrap align='center' ><acronym title='".traduz("data.abertura",$con,$cook_idioma).": $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";

            if ( in_array($login_fabrica, array(11,172)) ) { // HD 92774
                if(!empty($os)){
                    $sql_p = " SELECT to_char(tbl_pedido.data,'DD/MM/YYYY') as data_pedido
                                FROM tbl_os_produto
                                JOIN tbl_os_item USING(os_produto)
                                JOIN tbl_pedido  USING(pedido)
                                WHERE tbl_os_produto.os = $os
                                AND   tbl_pedido.fabrica = $login_fabrica
                                ORDER BY tbl_pedido.pedido ASC LIMIT 1 ";
                    $res_p = @pg_query($con,$sql_p);
                    //echo $sql_p;exit;
                    echo "<td nowrap align='center' >";
                    if (pg_num_rows($res_p) > 0) {
                        $data_pedido = pg_fetch_result($res_p,0,data_pedido);
                        echo "<acronym title='".traduz("data.pedido").": $data_pedido' style='cursor: help;'>" . substr($data_pedido,0,5) . "</acronym>";
                    }
                    echo "</td>";
                }else{
                    echo "<td></td>";
                }
            }

            if (!$esconde_coluna_pre_os) {
                //HD 14927
                if (usaDataConserto($login_posto, $login_fabrica)) {
                    echo "<td nowrap align='center'><acronym title='".traduz("data.do.conserto",$con,$cook_idioma).": $data_conserto' style='cursor: help;'>" . substr($data_conserto,0,5) . "</acronym></td>";
                }
            }

            if (!$esconde_coluna_pre_os) {
                if ($login_fabrica == 1) $aux_fechamento = $finalizada;
                else                     $aux_fechamento = $fechamento;

                //HD 204146: Fechamento automático de OS
                if ($login_fabrica == 3 and strlen($btn_acao_pre_os)==0) {
                    $sql = "SELECT sinalizador FROM tbl_os WHERE os=$os";
                    $res_sinalizador = pg_query($con, $sql);
                    $sinalizador = pg_result($res_sinalizador, 0, sinalizador);
                }

                if ($sinalizador == 18) {
                    echo "<td nowrap align='center'><acronym title='".traduz("data.fechamento",$con,$cook_idioma).": ";
                    echo "$aux_fechamento - ".traduz("fechamento.automatico")."' style='cursor: help; color:#FF0000; font-weight: bold;'>F. AUT</acronym></td>";
                }
                else {
                    echo "<td nowrap align='center'><acronym title='".traduz("data.fechamento",$con,$cook_idioma).": ";
                    echo "$aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
                }
            }

        if (!$esconde_coluna_pre_os) {
            if (in_array($login_fabrica, array(94, 115, 116, 117, 120, 201, 153,156, 158,163,167,171,174,176,177,203))) {
                if(!empty($tipo_atendimento)) {
                    $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                    $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
                    $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
                }
                if ($login_fabrica != 158) {
                    echo "<td nowrap>$desc_tipo_atendimento</td>";
                }
            }
        }

        if (in_array($login_fabrica, [167, 203]) && $posto_interno == true){
            echo "<td nowrap>{$descricao_orcamento}</td>";
        }

        if (in_array($login_fabrica, array(152,180,181,182))) {
            echo "<td width='120' nowrap> $tempo_para_defeito</td>";
        }
        if($login_fabrica == 87){
            if(!empty($tipo_atendimento)) {
                $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);
                $desc_tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
            }
                echo "<td>$desc_tipo_atendimento</td>";
        }else if (!$esconde_coluna_pre_os) {
            //HD 211825: Filtrar por tipo de OS: Consumidor/Revenda
            if ($login_fabrica == 178){
                switch ($consumidor_revenda) {
                    case "C":
                        echo "<td nowrap align='center'><acronym title='Consumidor' style='cursor: help;'>CONS</acronym></td>";
                    break;

                    case "R":
                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>REV</acronym></td>";
                    break;

                    case "A":
                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>ARQ/ENG</acronym></td>";
                    break;
                    
                    case "S":
                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>CONST</acronym></td>";
                    break;
                    
                    case "E":
                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>EQP. COMER</acronym></td>";
                    break;
                    
                    case "I":
                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>INST</acronym></td>";
                    break;
                    
                    case "P":
                        echo "<td nowrap align='center'><acronym title='Revenda' style='cursor: help;'>POSTO</acronym></td>";
                    break;
                    
                    case "":
                        echo"<td nowrap> &nbsp; </td>";
                    break;
                }
            }else{
                switch ($consumidor_revenda) {
                    case "C":
                        echo "<td nowrap align='center'><acronym title='".traduz("consumidor")."' style='cursor: help;'>CONS</acronym></td>";
                    break;

                    case "R":
                        echo "<td nowrap align='center'><acronym title='".traduz("revenda")."' style='cursor: help;'>REV</acronym></td>";
                    break;

                    case "S":
                        echo "<td nowrap align='center'><acronym title='".traduz("construtora")."' style='cursor: help;'>CONST</acronym></td>";
                        break;
                    case "":
                        echo "<td></td>";
                    break;
                }
            }
        }
            if($consumidor_revenda == "C"){
                $tipo_consumidor_revenda = "consumidor";
            }elseif($consumidor_revenda == "R"){
                $tipo_consumidor_revenda = "revenda";
            }
            if (in_array($login_fabrica, array(169, 170))) {
            	echo "<td width='120' nowrap>
            	<acronym title='".traduz("revenda",$con,$cook_idioma).": $revenda_nome' style='cursor: help;'>".$revenda_nome."</acronym>
            	</td>";
            	echo "<td width='120' nowrap>".$cnpj_revenda."</td>";
            }
            if ($login_fabrica == 80 && !empty($cons_nome)){
                echo "<td width='120' nowrap><acronym title='".traduz("consumidor",$con,$cook_idioma).": $cons_nome' style='cursor: help;'>";
            }elseif(in_array($login_fabrica, array(169, 170))){
                echo "<td width='120' nowrap><acronym title='".traduz("consumidor",$con,$cook_idioma).": $consumidor_nome' style='cursor: help;'>";
            }else{
                echo "<td width='120' nowrap><acronym title='".traduz("$tipo_consumidor_revenda",$con,$cook_idioma).": $consumidor_nome' style='cursor: help;'>";
            }

            if (strlen($smile) > 0) {
                echo '<img src="'.$smile.'" border="0" />&nbsp;';
            }
            // HD-4369591
            if ($login_fabrica == 80 && !empty($cons_nome)){
                echo substr($cons_nome,0,15) . "</acronym></td>";
            }elseif(in_array($login_fabrica, array(169, 170))){
                echo substr($consumidor_nome,0,15) . "</acronym></td>";
            }else{
                echo substr($consumidor_nome,0,15) . "</acronym></td>";
            }

            if ($login_fabrica == 158) {
?>
                <td nowrap><?=$consumidor_cidade?></td>
                <td><?=$consumidor_estado?></td>
<?php
            }

            if ( in_array($login_fabrica, array(11,172)) ) { // HD 92774
                echo "<td nowrap><acronym title='".traduz("telefone").": $consumidor_fone' style='cursor: help;'>" .$consumidor_fone. "</acronym></td>";
            }
            if ($login_fabrica == 165 && $_POST['tecnico']) {
                echo "<td>".$tecnico_nome."</td>";
            }

            if($login_fabrica==3){//TAKASHI HD925
                echo "<td nowrap>$marca_nome</td>";
            }

            if ($login_fabrica == 171) {
                echo "<td nowrap>$produto_referencia_fabrica</td>";
            }

            if ( in_array($login_fabrica, array(11,172)) ) { // HD 92774
                $produto = $produto_referencia;
            }else{
                $produto = $produto_referencia . " - " . $produto_descricao;
            }

            echo "<td nowrap><acronym title='";

            fecho ("referencia",$con,$cook_idioma);
            echo " : $produto_referencia ";
            fecho ("descricao",$con,$cook_idioma);
            echo " : $produto_descricao ";
            fecho ("voltagem",$con,$cook_idioma);
            echo ": $produto_voltagem' style='cursor: help;'>" . $produto . "</acronym></td>";

            if($login_fabrica == 165){
                echo  "<td nowrap> $produto_trocado</td> ";
            }

            if($login_fabrica == 56){
                echo"<td nowrap>$nome_atendimento</td>";
            }
            
            if (in_array($login_fabrica, array(35,104))) {

				if(!empty($hd_chamado)) {
					$sqlPostagem = "SELECT
										tbl_hd_chamado_postagem.numero_postagem
									 FROM
										tbl_hd_chamado_postagem
									 JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_postagem.hd_chamado AND tbl_hd_chamado_postagem.fabrica=$login_fabrica
									 WHERE
										tbl_hd_chamado_postagem.hd_chamado=$hd_chamado
									 AND
										tbl_hd_chamado_postagem.fabrica=$login_fabrica
								   ";

					$resPostagem = pg_query($con,$sqlPostagem);

					if (pg_num_rows($resPostagem) > 0) {
						$cod_postagem = pg_fetch_result($resPostagem, 0, "numero_postagem"); //HD-3139131
						if($login_fabrica != 35){
							echo"<td nowrap>".traduz("via.correios")."</td>";
							echo "<td nowrap>$hd_chamado</td>";//HD-3139131
						}
						echo "<td nowrap>$cod_postagem</td>";//HD-3139131                        
					} else {                    
						if($login_fabrica != 35){
							echo"<td nowrap>".traduz("via.consumidor")."</td>";
							echo "<td nowrap></td>";//HD-3139131
							echo "<td nowrap></td>";//HD-3139131                        
						} else {
							echo "<td nowrap>&nbsp;</td>"; 
						}                                                           
					}
				}else{
					if($login_fabrica != 35){
						echo"<td nowrap>".traduz("via.consumidor")."</td>";
						echo "<td nowrap></td>";//HD-3139131
						echo "<td nowrap></td>";//HD-3139131                        
					} else {
						echo "<td nowrap>&nbsp;</td>"; 
					}                                                           
				}
            }            //fputt

            if ($login_fabrica == 30) {
                echo "<td></td>";  
            }

            if($login_fabrica==19 || ( in_array($login_fabrica,array(94,162)) && $posto_interno === true ) ){ //HD 415550
                if($login_fabrica == 19)
                    echo"<td nowrap>$tipo_atendimento - $nome_atendimento </td>";
                echo"<td width='90' nowrap><acronym title='".traduz("nome.do.tecnico",$con,$cook_idioma).": $tecnico_nome' style='cursor: help;'>" . substr($tecnico_nome,0,11) . "</acronym></td>";
            }

            if(!in_array($login_fabrica, array(3,46,87,115,116,117,120,201,121,122,123,124,125,127,128,129,134,136,137,141,142,144,147)) && !isset($novaTelaOs)){
                if($login_posto==6359 OR $login_posto ==4311 or ($login_posto== 4262 and $login_fabrica==6) || in_array($login_fabrica, array(11,172))){
                    echo "<td>$rg_produto</td>";
                }
            }elseif($login_fabrica == 137){
                $dados = json_decode($rg_produto);
                echo "<td>".$dados->cfop."</td>";
                echo "<td>".$dados->vu."</td>";
                echo "<td>".$dados->vt."</td>";
            }

            if( in_array($login_fabrica, array(11,172)) && strlen($btn_acao_pre_os) > 0){
                echo "<td>$numero_postagem</td>";
            }

            if($login_fabrica == 115 OR $login_fabrica == 116 OR $login_fabrica == 117 OR $login_fabrica == 120 or $login_fabrica == 201){
                echo "<td>".number_format($valor_km,2,',','.')."</td>";
            }

            ##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
            if($login_fabrica != 35){
                echo "<td width='30' align='center'>";
                if (strlen($admin) > 0 and $login_fabrica == 19) echo "<img border='0' src='imagens/img_sac_lorenzetti.gif' alt='".traduz("os.lancada.pelo.sac.lorenzetti")."'>";
                else if (strlen($impressa) > 0)                  echo "<img border='0' src='imagens/img_ok.gif' alt='".traduz("os.ja.foi.impressa")."'>";            
                else
                    echo "<img border='0' src='imagens/img_impressora.gif' alt='".traduz("imprimir.os")."'>";
                echo "</td>";
            }

            ##### VERIFICAÇÃO SE A OS FOI ENVIADA CARTA REGISTRADA #####
            if($login_fabrica == 1 and $consumidor_revenda == 'C' ){
                echo "<td width='30' align='center'>";
                if(strlen($fechamento) == 0){
                    $sql_sedex = "SELECT SUM(current_date - data_abertura)as final FROM tbl_os WHERE os=$os ;";
                    $res_sedex = pg_query($con,$sql_sedex);
                    $sedex_dias = pg_fetch_result($res_sedex,0,'final');
                    if($sedex_dias > 15){
                        $sql_sedex = "SELECT sua_os_origem FROM tbl_os_sedex WHERE sua_os_origem = $os AND fabrica = $login_fabrica";
                        $res_sedex = pg_query($con,$sql_sedex);
                        if(pg_num_rows($res_sedex) == 0){
                            echo "<a href='carta_registrada.php?os=$os'><img border='0' width='20' heigth='20' src='imagens/envelope.png' alt='".traduz("inserir.informacoes.da.carta.registrada")."'></a>";
                        }else{
                            echo "<a href='carta_registrada.php?os=$os'><img border='0' width='20' heigth='20' src='imagens/img_ok.gif' alt='".traduz("visualizar.as.informacoes.da.carta.registrada")."'></a>";
                        }
                    }
                    echo "&nbsp;";
                }else{
                    echo "&nbsp;";
                }
                echo "</td>";
            }

            if(in_array($login_fabrica, array(35)) AND strlen($btn_acao_pre_os) > 0){                     
                    $sql_codigo_rastreio = "SELECT tbl_hd_chamado_postagem.numero_postagem
                                 FROM tbl_hd_chamado_postagem
                                 JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_postagem.hd_chamado AND tbl_hd_chamado_postagem.fabrica=$login_fabrica
                                 WHERE tbl_hd_chamado_postagem.hd_chamado=$hd_chamado AND tbl_hd_chamado_postagem.fabrica=$login_fabrica";
                    $resCodigoRastreio = pg_query($con, $sql_codigo_rastreio);                    
                    $n_codigo_rastreio = pg_fetch_result($resCodigoRastreio, 0, "numero_postagem");        

                    echo "<td align='center'>$hd_chamado</td>";
                    if(pg_num_rows($resCodigoRastreio) > 0){
                        $sql_conhecimento = "SELECT conhecimento AS conhecimento
                                  FROM tbl_faturamento_correio
                                 WHERE fabrica     = $login_fabrica
                                   AND numero_postagem = '$n_codigo_rastreio'";
                        $resconhecimento = pg_query($con, $sql_conhecimento);
                        $n_conhecimento = pg_fetch_result($resconhecimento,0, "conhecimento");
                        echo "<td align='center'><a href='./relatorio_faturamento_correios.php?conhecimento={$n_conhecimento}' rel='shadowbox'>$n_conhecimento</a></td>";
                    } else {
                        echo "<td>&nbsp;</td>";
                    }                    
            }


            if(strlen($btn_acao_pre_os)>0){
                ##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
                echo "<td width='30' align='center'>";
                if (strlen($admin) > 0 and $login_fabrica == 19) echo "<img border='0' src='imagens/img_sac_lorenzetti.gif' alt='".traduz("os.lancada.pelo.sac.lorenzetti")."'>";
                else if (strlen($impressa) > 0)                  echo "<img border='0' src='imagens/img_ok.gif' alt='".traduz("os.ja.foi.impressa")."'>";
                else                                             echo "<img border='0' src='imagens/img_impressora.gif' alt='".traduz("imprimir.os")."'>";
                echo "</td>";

                if($login_fabrica == 74){

                    echo "<td align='center' nowrap>";
                        echo "<input type='hidden' name='url_$i' id='url_$i' value='os_cadastro.php?pre_os=t&serie=$serie_hash&hd_chamado=$hd_chamado&hd_chamado_item=$hd_chamado_item' />";
                        echo "<button type='button' onclick='url($i)'>".traduz("abrir.pre-os",$con,$cook_idioma)."</button>";
                    echo "</td>";

                }else{

                    echo "<td align='center' nowrap>";
                    if (!in_array($login_fabrica, array(14,43,66,88))){

                        if (in_array($login_fabrica, [141]) && $consumidor_revenda_callcenter == 'R') { ?>
                            <a href="os_revenda.php?preos=<?= $hd_chamado ?>" target="_blank">
                        <?php
                        } else if($protocolo <> '' and $login_fabrica == 3){
                            $url = "os_cadastro.php?pre_os=t&serie=$serie&hd_chamado=$hd_chamado&hd_chamado_item=$hd_chamado_item";
                            $cod_protocolo = base64_encode($protocolo);
                            ?>
                            <a href='#' rel="stylesheet" onclick="verifica_protocolo('<?php echo $url;?>','<?php echo $cod_protocolo;?>');">
                            <?php
                        }else{
                            $serie_hash = str_replace("#","%23",$serie);
                            if($login_fabrica == 15){
                                echo "<a href='os_cadastro_tudo.php?pre_os=t&serie=$serie_hash&hd_chamado=$hd_chamado&hd_chamado_item=$hd_chamado_item'>";
                            }else if(isset($novaTelaOs) ){
                                $hd_chamado .= (in_array($login_fabrica, array(52,151))) ? "&hd_chamado_item=$hd_chamado_item" : "";
                                echo "<a href='cadastro_os.php?preos=$hd_chamado'>";
                            }else{
                                echo "<a href='os_cadastro.php?pre_os=t&serie=$serie_hash&hd_chamado=$hd_chamado&hd_chamado_item=$hd_chamado_item'>";
                            }

                        }
                    }
                    elseif ($login_fabrica == 43) {
                        echo "<a href='os_cadastro.php?pre_os=t&serie=$serie&hd_chamado=$hd_chamado'>";
                    } else {
                        echo "<a href='".parser_url('os_cadastro_intelbras_ajax.php?pre_os=t&serie=$serie&hd_chamado=$hd_chamado')."'>";
                    }
                    echo traduz("abrir.pre-os",$con,$cook_idioma)."</a>";

                    if(in_array($login_fabrica, array(191))){
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript: void(0)' onclick='excluiPreOs($(this),$hd_chamado)'>".traduz("excluir.pre-os",$con,$cook_idioma)."</a> <img src='imagens/loading_img.gif' style='display: none; height: 20px; width: 20px;'' id='loading_pre_os' />";
                    }

                    echo "</td>\n";

                }

    }else{

            ##### VERIFICAÇÃO SE TEM ITEM NA OS PARA A FÁBRICA 1 #####
            if ($login_fabrica == 1) {
                echo "<td width='30' align='center'>";
                if ($qtde_item > 0) echo "<img border='0' src='imagens/img_ok.gif' alt='".traduz("os.com.item")."'>";
                else                echo "&nbsp;";
                echo "</td>";
            }

            /* if($login_fabrica == 52){
                $dispaly = (!empty($data_conserto)) ? "block" : "none";
                echo "<td>";

                    $sqlM = "SELECT descricao
                            FROM tbl_motivo_atraso_fechamento
                            JOIN tbl_os_campo_extra ON tbl_motivo_atraso_fechamento.motivo_atraso_fechamento = tbl_os_campo_extra.motivo_atraso_fechamento AND tbl_os_campo_extra.fabrica = $login_fabrica
                            WHERE tbl_os_campo_extra.os = $os";
                    $resM = pg_query($con,$sqlM);
                    if(pg_num_rows($resM) > 0){
                        echo pg_result($resM,0,0);
                    } else {

                        $sqlM = "SELECT motivo_atraso_fechamento,
                                        descricao
                                        FROM tbl_motivo_atraso_fechamento
                                        WHERE fabrica = $login_fabrica
                                        AND ativo IS TRUE";
                        $resM = pg_query($con,$sqlM);

                        if(pg_num_rows($resM) > 0){
                            echo "<select name='motivo_atraso_fechamento_$os' id='motivo_atraso_fechamento_$os' class='frm' onchange='javascript: motivoAtraso($os,this.value)' style='display:$dispaly;'>";
                            echo "<option value=''>Selecione o motivo</option>";
                            for($k = 0; $k < pg_num_rows($resM); $k++){
                                $codigo_motivo = pg_result($resM,$k,'motivo_atraso_fechamento');
                                $desc_motivo   = pg_result($resM,$k,'descricao');

                                echo "<option value='$codigo_motivo'>$desc_motivo</option>";
                            }
                            echo "</select>";
                        }
                    }

                echo "</td>";
            } */

            if (in_array($login_fabrica, array(30,50))) {
                $valida_auditoria = 0;
                if ($login_fabrica == 30) {//Verifica se OS esta em auditoria de carência
                    $sqlAuditoria = "SELECT liberada FROM tbl_auditoria_os WHERE os = $os ;";
					$resAuditoria = pg_query($con, $sqlAuditoria);
					if(pg_num_rows($resAuditoria) > 0) {
						$liberada = pg_fetch_result($resAuditoria,0,'liberada');
						if(!empty($liberada)) $valida_auditoria = 1;
					}else{
						$valida_auditoria = 1;
					}
                }

                //verifica se tem itens na OS
                $sqlQtdItem = "SELECT count(*) as qtd
                         FROM tbl_os_produto
                         JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
                         where os=$os";
                $resQtde = pg_query($con,$sqlQtdItem);
                $qtd = pg_result($resQtde,0, 'qtd');
                //se não houver itens, mostra o botão para alterar cadastro
               

               
                if($qtd == 0 and empty($fechamento) && $valida_auditoria == 1){
                    
                    echo "<label><td>";
                    
                    if(($digita_os_consumidor == "t" && $consumidor_revenda =="C") || ($login_posto_digita_os =="t" && $consumidor_revenda =="R") && $status_checkpoint == 1) {
                    /*echo "<a href='os_cadastro_tudo.php?os=$os' target='_blank'> ".traduz("alterar.cadastro")."</a>";*/
                        
                        echo "<a href='os_cadastro_tudo.php?os=$os' target='_blank'><img border='0' src='imagens/btn_alterar_azul.gif'></a>";
                        
                    }

                    echo "</td></label>";
                } else {
                    echo "<label><td></td></label>";
                }
               

                echo "</td>";

                echo "<td>";
                    echo "<input name='numero_os' type='hidden' value='$os' />";
                    echo "<button class='lancar_observacao' type='button' style='cursor:pointer;'>".traduz("lancar.observacao")."</button>";
                echo "</td>";
            }

            // Adicionada antes das ações
            if (in_array($login_fabrica, array(169,170,174))){
                $postagem_coleta = "false";
                if (strlen(trim($hd_chamado)) > 0){
                    $sql_lgr_correios = "
                        SELECT hd_chamado_postagem, tbl_hd_chamado_postagem.admin
                        FROM tbl_hd_chamado_postagem
                        WHERE fabrica = $login_fabrica
                        AND hd_chamado = $hd_chamado
                        ORDER BY hd_chamado_postagem DESC LIMIT 1";
                    $res_lgr_correios = pg_query($con, $sql_lgr_correios);

                    if (pg_num_rows($res_lgr_correios) > 0){
                        $admin_postagem = pg_fetch_result($res_lgr_correios, 0, 'admin');

                        if(strlen($data_conserto) > 0 AND strlen($admin_postagem) > 0){
                            $postagem_coleta = "true";
                            $display_lgr = "";
                        }else{
                            $display_lgr = "display: none;";
                        }
                    }
                }

                if ($postagem_coleta == "false" AND strlen($fechamento) > 0){
                    $xtd_col_span = "colspan='1'";
                }else{
                    $xtd_col_span = "";
                }
            }
            echo "<td width='60' align='center'$xtd_col_span >";

            if ($excluida == "f" || strlen($excluida) == 0 and strlen($btn_acao_pre_os)==0) {
                if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
                    if($login_posto=="6359"){
                            echo "<a href='os_print.php?os=$os' target='_blank'>";
                    }else{
                        echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
                    //takashi alterou 03/11
                    }
                }else{
                    $sql = "SELECT os
                            FROM tbl_os_troca_motivo
                            WHERE os = $os ";
                    $resxxx = pg_query($con,$sql);
                    if($login_fabrica==20 AND pg_num_rows($resxxx)>0) {
                        #echo "<a href='os_finalizada.php?os=$os' target='_blank'>";
                        echo "<a href='os_print.php?os=$os' target='_blank'>";
                    }else{
                        if(!in_array($login_fabrica, array(96,178))){
                            echo "<a href='os_print.php?os=$os' target='_blank'>";
                        }else if ($login_fabrica == 178){
                            $os_link = explode("-", $sua_os);
                            echo "<a href='os_revenda_print.php?os_revenda=".$os_link[0]."' target='_blank'>";
                        }else{
                            if(!in_array($tipo_atendimento,array(92,94))){
                                echo "<a href='print_orcamento.php?os=$os&log_posto=posto' target='_blank'>";
                            }else{
                                echo "<a href='os_print.php?os=$os' target='_blank'>";
                            }
                        }
                    }
                }
                echo "<img border='0' src='imagens/btn_imprime.gif'></a>";
            }
            echo "</td>\n";
            // ------ fim 

            echo "<td width='60' align='center'>";
            if($sistema_lingua == "ES"){
                if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_busca.gif'></a>";
            }else{
                if (in_array($login_fabrica,array(145,152,180,181,182))) {
                    if ($grupo_atendimento == "R") {
                        $os_press_link = "os_press_revisao.php?os={$os}";
                    }elseif ($grupo_atendimento == "A") {
                        $os_press_link = "os_press_entrega_tecnica.php?os={$os}";
                    } else {
                        $os_press_link = "os_press.php?os={$os}";
                    }

                    echo "<a href='{$os_press_link}' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a>";
                } else {
                    echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a>";
                }
            }
            echo "</td>\n";

            if ($login_fabrica == 30) {
                echo "<td><button onclick='AgendarVisita($os)' style='cursor:pointer;'>".traduz("agendar.visita")."</button></td>";
            }

            // adicionada antes das ações / Deixei caso quebre para outra fábrica, voltar como estava.
            /*if (in_array($login_fabrica, array(169,170,174))){
                $postagem_coleta = "false";
                if (strlen(trim($hd_chamado)) > 0){
                    $sql_lgr_correios = "
                        SELECT hd_chamado_postagem, tbl_hd_chamado_postagem.admin
                        FROM tbl_hd_chamado_postagem
                        WHERE fabrica = $login_fabrica
                        AND hd_chamado = $hd_chamado
                        ORDER BY hd_chamado_postagem DESC LIMIT 1";
                    $res_lgr_correios = pg_query($con, $sql_lgr_correios);

                    if (pg_num_rows($res_lgr_correios) > 0){
                        $admin_postagem = pg_fetch_result($res_lgr_correios, 0, 'admin');

                        if(strlen($data_conserto) > 0 AND strlen($admin_postagem) > 0){
                            $postagem_coleta = "true";
                            $display_lgr = "";
                        }else{
                            $display_lgr = "display: none;";
                        }
                    }
                }

                if ($postagem_coleta == "false" AND strlen($fechamento) > 0){
                    $xtd_col_span = "colspan='1'";
                }else{
                    $xtd_col_span = "";
                }
            }
            echo "<td width='60' align='center'$xtd_col_span >";

            if ($excluida == "f" || strlen($excluida) == 0 and strlen($btn_acao_pre_os)==0) {
                if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
                    if($login_posto=="6359"){
                            echo "<a href='os_print.php?os=$os' target='_blank'>";
                    }else{
                        echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
                    //takashi alterou 03/11
                    }
                }else{
                    $sql = "SELECT os
                            FROM tbl_os_troca_motivo
                            WHERE os = $os ";
                    $resxxx = pg_query($con,$sql);
                    if($login_fabrica==20 AND pg_num_rows($resxxx)>0) {
                        #echo "<a href='os_finalizada.php?os=$os' target='_blank'>";
                        echo "<a href='os_print.php?os=$os' target='_blank'>";
                    }else{
                        if(!in_array($login_fabrica, array(96,178))){
                            echo "<a href='os_print.php?os=$os' target='_blank'>";
                        }else if ($login_fabrica == 178){
                            $os_link = explode("-", $sua_os);
                            echo "<a href='os_revenda_print.php?os_revenda=".$os_link[0]."' target='_blank'>";
                        }else{
                            if(!in_array($tipo_atendimento,array(92,94))){
                                echo "<a href='print_orcamento.php?os=$os&log_posto=posto' target='_blank'>";
                            }else{
                                echo "<a href='os_print.php?os=$os' target='_blank'>";
                            }
                        }
                    }
                }
                echo "<img border='0' src='imagens/btn_imprime.gif'></a>";
            }
            echo "</td>\n";*/


            if ($login_fabrica == 1) {
                echo "<td width='60' align='center'>";
                if (($excluida == "f" || strlen($excluida) == 0) && strlen($fechamento) == 0) {
                    if($tipo_atendimento <> 17 AND $tipo_atendimento <> 18 )
                        echo "<a href='os_cadastro.php?os=$os'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
                    else
                        if(strlen($valores_adicionais) == 0 AND strlen($nota_fiscal_saida) == 0)
                            echo "<a href='os_cadastro_troca.php?os=$os'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
                }else{
                    echo "&nbsp;";
                }
                echo "</td>\n";
            }

            $sql_critico = "select produto_critico from tbl_produto where referencia = '$produto_referencia'";
            $res_critico = pg_query($con,$sql_critico);

            if (pg_num_rows($res_critico)>0) {
                $produto_critico = pg_fetch_result($res_critico,0,produto_critico);
            }

            if ($login_fabrica == 42) {

                $sql_ta_et = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                $res_ta_et = pg_query($con, $sql_ta_et);

                $tipo_atendimento_et = pg_result($res_ta_et, 0, "entrega_tecnica");
            }

            if($login_fabrica == 90) {
                $sqlX = "SELECT status_os
                         FROM tbl_os_status
                         JOIN tbl_os_retorno ON tbl_os_retorno.os = $os AND
                                                tbl_os_retorno.data_nf_retorno IS NOT NULL
                         WHERE tbl_os_status.os        = $os AND
                               tbl_os_status.status_os = 64";
                $resX = pg_query($con, $sqlX);
            }

            // HD-962530
            if(!(pg_num_rows($resX)))
                $lancar_itens = true;
            else
                $lancar_itens = false;

            if ($login_fabrica == '131') {
                $sqlAud = "SELECT status_os from tbl_os_status where os = '{$os}' and status_os IN (171, 172) order by data desc limit 1";
                $qryAud = pg_query($con, $sqlAud);

                if (pg_num_rows($qryAud) > 0) {
                    $status_os_atual = pg_fetch_result($qryAud, 0, 'status_os');

                    if ($status_os_atual == '171') {
                        $auditoria_travar_opcoes = true;
                    }
                }

                # HD-2181938
                $sqlPedidoAudi = "SELECT status_os from tbl_os_status where os = '{$os}' and status_os IN (203, 204, 205) order by data desc limit 1";
                $resPedidoAudi = pg_query($con, $sqlPedidoAudi);

                if (pg_num_rows($resPedidoAudi) > 0) {
                    $audiPedido = pg_fetch_result($resPedidoAudi, 0, 'status_os');

                    if ($audiPedido == '205') {
                        $auditoria_travar_opcoes = true;
                    }
                }
                # FIM HD-2181938
            }

            if( in_array($login_fabrica, array(11,172)) ){
                $sql = "SELECT os from tbl_os_troca where os = {$os}";
                $rest = pg_query($con,$sql);
                // se houver OS na tbl_os_troca, não mostra botão de lançar itens.
                $rows_troca = pg_num_rows($rest);
                if($rows_troca==0){
                    echo "<td width='60' align='center' nowrap>
                            <a href='os_item.php?os=$os' target='_blank'>
                                <img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanca.gif'>
                            </a>
                        </td>";
                }
            }else{
                if ($tipo_atendimento_et <> "t") {

                    if (in_array($login_fabrica, array(169,170))){
                        if (strlen($fechamento) > 0){
                            $style_td = " style='display:none;' ";
                        }else{
                            $style_td = "";
                        }
                    }

                    $width_td = ($fCancelaOS && $cancelada == "t") ? "0" : "60";
                    echo "<td width='{$width_td}' $style_td align='center' nowrap rel='td_reabrir_os'>$grupo";

                    //HD 214236: Travar as opções quando a OS estiver em auditoria

                    if ((in_array($login_fabrica,array(14,43,104,105,131))) && ($auditoria_travar_opcoes)) {

                    }elseif ($login_fabrica == 171 && $auditoria_status == 6) {

                    }elseif(isset($novaTelaOs)){

                        if ((!$reparoNaFabrica || empty($os_reparo)) || ($reparoNaFabrica && !empty($os_reparo) && $login_posto_interno)) {
                                if ($status_checkpoint != 9 && $status_checkpoint != 8) {

                                    if(in_array($login_fabrica,array(145,152,180,181,182))) {
                                        if($grupo_atendimento == "R"){
                                            echo "<a href='cadastro_os_revisao.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_alterar_cinza.gif'></a>";
                                        }elseif($grupo_atendimento == "A"){
                                            echo "<a href='cadastro_os_entrega_tecnica.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_alterar_cinza.gif'></a>";
                                        }else{
                                            $sql_troca_produto = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(192,193,194) ORDER BY data DESC LIMIT 1";
                                            $res_troca_produto = pg_query($con, $sql_troca_produto);

                                            $status_troca_produto = pg_fetch_result($res_troca_produto, 0, "status_os");

                                            $sql_troca_peca = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND status_os IN(199,200,201) ORDER BY data DESC LIMIT 1";
                                            $res_troca_peca = pg_query($con, $sql_troca_peca);

                                            $status_troca_peca = pg_fetch_result($res_troca_peca, 0, "status_os");

                                            if($login_fabrica == 145){
                                                if ($status_troca_produto != 194) {
                                                    echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                                }
                                            }else{

                                                if ($status_troca_produto != 194 && $status_troca_peca != 201) {

                                                    if(in_array($login_fabrica, array(152,181,182)) and $cancelada != 't'){
                                                        echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                                    }
                                                    if(in_array($login_fabrica, array(180)) and $cancelada != 't'){
                                                        echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='admin_es/imagens/btn_editar_items.png'></a>";
                                                    }

                                                }
                                            }
                                        }
                                    }else{
                                        if ($login_fabrica <> 156 or $posto_interno === true) {
                                            if($login_fabrica == 148){//hd_chamado=3049906
                                                if($os_status_cancelada <> 't'){
                                                    echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                                }
                                            }else{
                                                if(in_array($login_fabrica, [167, 203])){
                                                    if($bloqueia_itens == 'false'){
                                                        echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                                    }
                                                }else{
                                                    if ($cancelaOS) {
                                                        if ($excluida != "t" && empty($fechamento)) {
                                                            echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                                        }
                                                    } else {

                                                        if (in_array($login_fabrica, [151])) {

                                                            $sqlStatus = "SELECT tbl_os.status_checkpoint
                                                                          FROM tbl_os
                                                                          JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
                                                                          WHERE tbl_os.os = {$os}
                                                                          AND tbl_status_checkpoint.descricao = 'Aguardando Analise Helpdesk'";
                                                            $resStatus = pg_query($con, $sqlStatus);

                                                            if (pg_num_rows($resStatus) == 0) {

                                                                echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";

                                                            }

                                                        } else {

                                                            echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";

                                                        }
                                                    }
                                                }
                                            }
                                        } elseif ($recolhimento <> 't') {
                                            echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                        }
                                    }

								}else if($login_fabrica == 164){

                                    $sql_os_troca = "SELECT
                                                        tbl_os_troca.os_troca
                                                    FROM tbl_os_troca
                                                    INNER JOIN tbl_os ON tbl_os.os = tbl_os_troca.os AND tbl_os.data_fechamento ISNULL AND tbl_os.fabrica = {$login_fabrica}
                                                    WHERE
                                                        tbl_os_troca.os = {$os}";
                                    $res_os_troca = pg_query($con, $sql_os_troca);

                                    if(pg_num_rows($res_os_troca) > 0){
                                        echo "<a href='cadastro_os.php?os_id=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                    }

                                }

							if(in_array($login_fabrica,array(52,153,161)) || ($login_fabrica == 158 && strtolower($desc_tipo_atendimento) == "piso")){
								if(strlen($fechamento) > 0 and strlen($extrato) == 0 and (strlen($excluida) == 0 OR $excluida == 'f')){
									echo "<a  href='javascript:reabrirOS($os)'><img border='0' $display_button_cancelado src='imagens/btn_reabriros.gif'></a>";
								}
							}
                        }
                    }elseif ( $troca_garantia == "t" or ((($status_os=="62" and $produto_critico <> 't') || in_array($status_os,array(20,65,158,87,72,116,120,122,126,140,141,143,167,203)) || ($status_os == '118' and !in_array($login_fabrica,[120,201]))) && $login_fabrica <> 86 )) {
                        if($login_fabrica == 114 and in_array($status_os,array(20,62))){
                            echo "<a href='os_item.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                        }
                        if ($login_fabrica == 3) {
                            echo "<a href='os_item.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                        }
                    }elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {

                        if ($excluida == "f" || strlen($excluida) == 0) {

                            #HD 311414 - BLOQUEIO DE BOTÕES QUANDO A OS POSSUIR REGISTROS NA TBL_OS_TROCA - INICIO

                                $tem_troca = 'f';
                                $sql_troca = "SELECT os from tbl_os_troca where os=$os";
                                $res_troca = pg_query($con,$sql_troca);

                                if (pg_num_rows($res_troca)>0){
                                    $tem_troca = 't';
                                }

                                if ($tem_troca == 't'){
                                    echo "&nbsp;";
                                }else{
                                    if($login_fabrica == 3 and $cancelada != 't'){
                                        echo "<a href='os_item.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                    }elseif($login_fabrica != 3){
                                        echo "<a href='os_item.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                    }                                    
                                }

                            #HD 311414 - BLOQUEIO DE BOTÕES QUANDO A OS POSSUIR REGISTROS NA TBL_OS_TROCA - FIM

                            #if($login_fabrica != 6 || strlen($data_conserto) == 0){
                            #   echo "<a href='os_item.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                            #}
                        }
                    }elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
                        if ($excluida == "f" || strlen($excluida) == 0) {
                            if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
                                if($login_posto=="6359"){
                                    echo "<a href='os_item.php?os=$os' target='_blank'>";
                                }else{
                                    echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
                                //takashi alterou 03/11
                                }
                            }else{
                                echo "<a href='os_item.php?os=$os' target='_blank'>";
                            }//
                            if($login_fabrica == 1 AND $tipo_atendimento <> 17 AND $tipo_atendimento <> 18)
                                echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                            else
                                echo "<p id='lancar_$i' border='0'></p></a>";
                        }
                    }elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 ) {
                        echo "<a href='os_filizola_valores.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                    }elseif (strlen($fechamento) == 0 ) {

                        if ($excluida == "f" OR strlen($excluida) == 0) {

                            if ($login_fabrica == 1) {
                                if($tipo_os_cortesia == "Compressor"){
                                    if($login_posto=="6359"){
                                        echo "<a href='os_item.php?os=$os' target='_blank'>";
                                    }else{
                                        echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
                                    //takashi alterou 03/11
                                    }
                                }
                                if(strlen($tipo_atendimento) == 0){
                                    echo "<a href='os_item.php?os=$os' target='_blank'>";
                                }

                            }else{

                                if (in_array($login_fabrica, array(141))) {
                                    $sql_laudo = "SELECT laudo_tecnico, data_conserto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
                                    $res_laudo = pg_query($con, $sql_laudo);

                                    if (pg_num_rows($res) > 0) {
                                        if (strlen(pg_fetch_result($res_laudo, 0, "laudo_tecnico")) > 0 && strlen(pg_fetch_result($res_laudo, 0, "data_conserto")) > 0) {
                                            $bloqueia_lancar_itens = true;
                                        }
                                    }
                                }

                                if($login_fabrica==19 || strtoupper($tipo_de_posto) == 'SAC' && $login_posto != 28332) {
                                    if($consumidor_revenda<>'R' && strtoupper($tipo_de_posto) != 'SAC'){

                                        echo "<a href='os_item.php?os=$os' target='_blank'>";
                                        if($sistema_lingua == "ES"){
                                            echo "<img id='lancar_$i' border='0' src='imagens/btn_lanzar.gif'></a>";
                                        }else{
                                            echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
                                        }
                                    }

                                }else{

                                    if (in_array($login_fabrica, array(52, 143))) {
                                        echo "<a href='cadastro_os.php?os_id=$os' target='_blank'>";
                                    } else {
                                        if (!(in_array($login_fabrica, array(141)) && $bloqueia_lancar_itens == true)) {
                                            if($login_fabrica == 74 and $cancelada == 't'){
                                                echo "";
                                            }else{
                                                echo "<a href='os_item.php?os=$os' target='_blank'>";
                                            }
                                        }
                                    }
                                                                
                                    if($sistema_lingua == "ES"){
                                        echo "<img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanzar.gif'>";
                                    }else{
                                        // $data_conserto > "03/11/2008" HD 50435
                                        $xdata_conserto = fnc_formata_data_pg($data_conserto);

                                        $sqlDC = "SELECT $xdata_conserto::date > '2008-11-03'::date AS data_anterior";
                                        #echo $sqlDC;
                                        $resDC = pg_query($con, $sqlDC);
                                        if(pg_num_rows($resDC)>0) $data_anterior = pg_fetch_result($resDC, 0, 0);

                                        if( in_array($login_fabrica, array(11,172)) && strlen($data_conserto)>0 AND $data_anterior == 't'){
                                            echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif' style='display:none'>";

                                        }else{
                                            
                                            $sqltroca = "SELECT os_troca from tbl_os_troca where os = $os";
                                            $restroca = pg_exec($sqltroca);

                                            if (pg_num_rows($restroca)>0) {
                                                $os_troca = pg_result($restroca,0,0);
                                            }else {
                                                $os_troca = '';
                                            }

                                            if (strlen($os_troca)>0) {
                                                echo "<img id='lancar_$i' border='0' $display_button_cancelado src='imagens/pixel.gif'>";
                                            } else {

                                                if ($login_fabrica == 35){
                                                    if ($status_os == 62 and $produto_critico == 't') {
                                                        echo "";
                                                    }else{
                                                        echo "<img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanca.gif'>";
                                                    }
                                                }else {
                                                    if(($login_fabrica == 90 and $lancar_itens) or $login_fabrica <> 90){
                                                        if (!(in_array($login_fabrica, array(141)) && $bloqueia_lancar_itens == true)) {
                                                            if($fCancelaOS){
																if($cancelada != "t"){
																	if(!empty($liberada) or $sem_auditorica)  {
																		echo "<img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanca.gif'>";
																	}
                                                                }
                                                            }else{

                                                                if($fCancelaOS){
                                                                    if($cancelada != 't'){
                                                                        echo "<img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanca.gif'>";
                                                                    }
                                                                }elseif($login_fabrica <> 30){
                                                                    /* HD - 3317939 */
                                                                    $mostrar_btn_lancar = true;

                                                                    if ($login_fabrica == 72) {
                                                                        $aux_sql = "SELECT status_os FROM tbl_os_status WHERE os = $os AND status_os IN (19,70)";
                                                                        $aux_res = pg_query($con, $aux_sql);
                                                                        $aux_tot = pg_num_rows($aux_res);

                                                                        if ($aux_tot == 1) {
                                                                            $aux_status = pg_fetch_result($aux_res, 0, 0);

                                                                            if ($aux_status == "70") {
                                                                                $mostrar_btn_lancar = false;
                                                                            }
                                                                        }
                                                                    }

																	if ($mostrar_btn_lancar === true) {
                                                                        if($trava_acoes_positec != 't'){
                                                                            echo " <img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanca.gif'>";
                                                                        }
                                                                    }
                                                                }

                                                                if ($login_fabrica == 30) {//HD-3027234

                                                                    //if($valida_auditoria == 1){
                                                                        $sqlStatusOS = "SELECT status_os 
                                                                                        FROM tbl_os_status 
                                                                                        WHERE tbl_os_status.os = $os 
                                                                                        AND status_os 
                                                                                        IN (102, 103, 104) 
                                                                                        ORDER BY data
                                                                                        DESC LIMIT 1";

                                                                        $resStatusOS = pg_query($con, $sqlStatusOS);

    																	if ($login_tipo_posto != 605 || $login_posto == 28332) {

    																		if (pg_num_rows($resStatusOS) > 0) {

    																			$statusNumeroSerie = pg_fetch_result($resStatusOS, 0, 'status_os');

    																			if (!in_array($statusNumeroSerie,array(102,104))) {

    																				echo "<img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanca.gif'>";
    																			}
    																		} else {

    																			echo "<img id='lancar_$i' border='0' $display_button_cancelado src='imagens/btn_lanca.gif'>";
    																		}
    																	}
                                                                    //}
                                                                }


                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if (!(in_array($login_fabrica, array(141)) && $bloqueia_lancar_itens == true)) {
                                        echo "</a>";
                                    }

                                }
                                //
                            }
                        }
                    }elseif (strlen($fechamento) > 0 && (strlen($extrato) == 0 or ($login_fabrica == 94 and $extrato == 0) ) AND strlen($rg_produto)==0) {
                        if ($excluida == "f" || strlen($excluida) == 0) {
                            if (strlen ($importacao_fabrica) == 0) {
                                if($login_fabrica == 20){
                                    /*if($status_os<>'13' AND ($tipo_atendimento<>13 and $tipo_atendimento <> 66))
                                        echo "<a href='os_cadastro.php?os=$os&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";*/
                                    // HD 61323
                                }
                                else if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)and ($login_fabrica == 80)) echo "&nbsp;";
                                    else{
                                        //HD 204146: Fechamento automático de OS
                                        if ($login_fabrica == 3) {
                                            $sql = "SELECT sinalizador FROM tbl_os WHERE os=$os";
                                            $res_sinalizador = pg_query($con, $sql);
                                            $sinalizador = pg_result($res_sinalizador, 0, sinalizador);
                                        }

                                        if ($sinalizador == 18 || $sinalizador == 19 || $sinalizador == 20) {

                                            echo "&nbsp;";
                                        } else {

                                            //HD 15368 - Raphael, se a os for troca não pode irá reabrir
                                            if (strlen(trim($os)) > 0){
                                                $sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
                                                $resX = @pg_query($con,$sqlX);
                                                if(@pg_num_rows($resX)==0) {
                                                    //HD-7359443 Fab 91
                                                    if( !in_array($login_fabrica, array(11,91,172)) || ($login_fabrica == 91 && admin_fechou_com_pagamento($os)) ){ // HD 45935

                                                        if (in_array($login_fabrica,[120,201]) && $status_os == 104) {
                                                            echo "&nbsp;";
                                                        } else {
                                                            if($login_fabrica == 90){
                                                                $sqlX = "SELECT status_os
                                                                     FROM tbl_os_status
                                                                     JOIN tbl_os_retorno ON tbl_os_retorno.os = $os AND
                                                                                            tbl_os_retorno.data_nf_retorno IS NOT NULL
                                                                     WHERE tbl_os_status.os        = $os AND
                                                                           tbl_os_status.status_os = 64";
                                                                $resX = pg_query($con, $sqlX);

                                                                // HD-962530
                                                                if(!(pg_num_rows($resX))) {

                                                                    echo "<a href='os_item.php?os=$os&reabrir=ok'><img border='0' $display_button_cancelado src='imagens/btn_reabriros.gif'></a>";
                                                                }
                                                            }else{
                                                                if($login_fabrica == 85 and $status_os != 212){
                                                                    echo "<a  href='os_item.php?os=$os&reabrir=ok'><img border='0' $display_button_cancelado src='imagens/btn_reabriros.gif'></a>";
    															}elseif(!in_array($login_fabrica, array(85,101))){
    																if($login_fabrica == 52) {
    																	echo "<a  href='javascript:reabrirOS($os)'><img border='0' $display_button_cancelado src='imagens/btn_reabriros.gif'></a>";
                                                                    }elseif(!in_array($login_fabrica, array(19,30,35,42,74))){
                                                                        if($login_fabrica != 104){
                                                                            echo "<a  href='os_item.php?os=$os&reabrir=ok'><img border='0' $display_button_cancelado src='imagens/btn_reabriros.gif'></a>";
                                                                        }
                                                                    }elseif(in_array($login_fabrica, array(19))){
                                                                        $sqlos = "SELECT    data_fechamento,
                                                                                            data_abertura,
                                                                                            tipo_atendimento
                                                                             FROM tbl_os
                                                                             WHERE os = $os
                                                                             and excluida <> 't'
									     and importacao_fabrica isnull
                                                                             and tipo_atendimento NOT IN(235,335)";
                                                                        $resos = pg_query($con, $sqlos);
                                                                        if(pg_num_rows($resos) > 0 ) {
                                                                            echo "<a  href='os_item.php?os=$os&reabrir=ok'><img border='0' $display_button_cancelado src='imagens/btn_reabriros.gif'></a>";
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }else{
                                                        echo "&nbsp;";
                                                    }
                                                }
                                            }
                                        }
                                    }
                            }
                        }
                    }else{
                        echo "&nbsp;";
                    }

                    echo "</td>\n";
                }
            }

            if ($login_fabrica == 94 && strlen ($fechamento) == 0 && $login_posto == '146534' ) {

                echo '<td><a href="os_cadastro.php?os='.$os.'"><img src="imagens/btn_alterar_cinza.gif" alt="'.traduz("alterar").'" /></a></td>';

            }

            if ($login_fabrica == 20 && empty($extrato) && strlen($fechamento) == 0) {
                if($atendimento_posto == 'n'){
                    echo '<td><a href="os_cadastro_unico.php?os='.$os.'&reabrir=sim" target="_blank"><img src="imagens/btn_alterar_cinza.gif" alt="'.traduz("alterar").'" /></a></td>';

                }elseif(strlen($fechamento) == 0 ) {
                    echo '<td><a href="os_cadastro.php?os='.$os.'" target="_blank"><img src="imagens/btn_alterar_cinza.gif" alt="'.traduz("alterar").'" /></a></td>';
                }
            }

            if ($login_fabrica == '91' && strlen($fechamento) == 0) {
                $hoje = new DateTime(date('Y-m-d'));
                $abriu_os = new DateTime($data_digitacao_banco);

                if ($abriu_os == $hoje and empty($os_troca)) {
                    echo '<td><a href="os_cadastro.php?os='.$os.'"><img src="imagens/btn_alterar_cinza.gif" alt="'.traduz("alterar").'" /></a></td>';
                }
            }

            if ($login_fabrica == 1) {
                echo "<td width='60' align='center'>";
                if (strlen($admin) == 0 AND strlen ($fechamento) == 0 AND ($excluida == "f" OR strlen($excluida) == 0) AND $mostra_motivo == 1) {
                    echo "<a href='os_motivo_atraso.php?os=$os' target='_blank'><img border='0' src='imagens/btn_motivo.gif'></a>";
                }else{
                    echo "&nbsp;";
                }
                echo "</td>\n";
            }

            // Botão de excluir OS
            if (!in_array($login_fabrica, array(173,174,178))) {
            echo "<td width='60' $style_td $style_174 align='center' id='td_excluir_$os'>";
            //HD 214236: Travar as opções quando a OS estiver em auditoria
            if (($login_fabrica == 14 || $login_fabrica == 43 or $login_fabrica == '131') && ($auditoria_travar_opcoes)) {
            }
            elseif (strlen($fechamento) == 0 && $status_checkpoint < 3) {
                if ((!in_array($status_os,array(20,62,65,158,72,87,116,120,122,126,140,141,143)) || ($status_os == 118 and in_array($login_fabrica,[120,201]) )) || ($reincidencia=='t')) {        
                    if ((($excluida == "f" || strlen($excluida) == 0 ) and !$reparoNaFabrica) or ($reparoNaFabrica and $aux_reparo_produto == "t") || $fabrica_copia_os_excluida)    {
                        if (strlen ($admin) == 0) {
                            if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18) AND $valores_adicionais > 0){
                                echo "<p id='excluir_$i' border='0' alt='$os,$sua_os' title='".traduz("excluir")." $sua_os'></p>";
                            }else{
                                # HD 311411 - Condição para não exibir botão excluir ao posto quando ele estiver com data de conserto
                                if($login_fabrica != 6 || strlen($data_conserto) == 0){
                                    if ($fCancelaOS) {
                                        if($cancelada != "t"){
                                            echo "<img id='excluir_$i' border='0' $display_button_cancelado src='imagens/btn_excluir.gif' alt='$os,$sua_os' title='".traduz("excluir")." $sua_os'>";
                                        }
                                    } elseif ($login_fabrica == 161 || in_array($login_fabrica, [167, 203])) {

                                        if (strlen(trim($defeito_constatado)) == 0) {
                                            echo "<img id='excluir_$i' border='0' $display_button_cancelado src='imagens/btn_excluir.gif' alt='$os,$sua_os' title='".traduz("excluir")." $sua_os'>";

                                        }
                                    } else if ($login_fabrica != 158) {

                                        if (in_array($login_fabrica, array(30))) {
                                            /*if($excluida == "t"){
                                                $botaoExcluir = "imagens/btn_reabriros.gif";
                                                $verbo = "reabrir";
                                                $acao = "liberar"; 

                                            }else{*/
                                                $botaoExcluir = "imagens/btn_cancelar.gif";
                                                $verbo = "cancelar";
                                                $acao = "cancelar";
                                            //}

                                            echo (empty($extrato)) ? "<a href='javascript:void(0);' onclick=\"if (confirm('Deseja realmente $verbo a os $sua_os2?') == true) cancelarOs({$os},'{$acao}');\">
                                            <img id='excluir_$i' border='0' src='$botaoExcluir'></a>" : "&nbsp;";

                                        } else {
											if ($login_fabrica == 19) {
												$sql_importa = "SELECT os FROM tbl_os WHERE os = $os AND importacao_fabrica IS NULL";
												$qry_importa = pg_query($con, $sql_importa);

												if (pg_num_rows($qry_importa) > 0) {
													echo "<img id='excluir_$i' border='0' $display_button_cancelado src='imagens/btn_excluir.gif' alt='$os,$sua_os' title='Excluir $sua_os'>";
												}
											} else {
												echo "<img id='excluir_$i' border='0' $display_button_cancelado src='imagens/btn_excluir.gif' alt='$os,$sua_os' title='Excluir $sua_os'>";
											}
                                        }
                                    }

                                }
                            }
                        }else{
                            if($login_fabrica == 20) { # 148322
                                echo "<img id='excluir_$i' border='0' src='imagens/btn_excluir.gif' alt='$os,$sua_os' title='".traduz("excluir")." $sua_os'>";
                            }else{
                                echo "<img id='excluir_$i' border='0' src='imagens/pixel.gif'>";
                            }
                        }
                    }
                }
            }else{
                if($login_fabrica == 15){
                    echo "<img id='excluir_$i' border='0' style='display:none;' src='imagens/btn_excluir.gif' alt='$os,$sua_os' title='".traduz("excluir")." $sua_os'>";
                }elseif($login_fabrica == 20 AND strlen($extrato == 0)){
                    echo "<img id='excluir_$i' border='0' src='imagens/btn_excluir.gif' alt='$os,$sua_os' title='".traduz("excluir")." $sua_os'>";
                }else{
                    echo "&nbsp;";
                }
            }
            echo "</td>\n";
            }

            if (!in_array($login_fabrica, array(173,174,175,176,177))) {
			echo "<td width='60' $style_td $style_174 align='center'>";

                //HD 214236: Travar as opções quando a OS estiver em auditoria
            if (($login_fabrica == 14 || $login_fabrica == 43 or $login_fabrica == '131') && ($auditoria_travar_opcoes)) {
            } else if (($login_fabrica == 171 && $auditoria_status == 6)) {
            } else if (
                !in_array($login_fabrica, array(158,167,203))
                && (
                    (strlen($status_os) == 0
                    || !in_array($status_os,array(20,62,65,72,87,116,81,120,122,126,140,141,143,158,174))
                    || ($status_os == 118 and ($login_fabrica == 120 or $login_fabrica == 201))
                    || ($status_os != 81 and $login_fabrica == 117))
                    && (empty($fechamento))
                )
            ) {

                # HD 2489168 (Tectoy) - Não permitir fechamento de OS caso a mesma não tenha nota fiscal
                if (
                    !in_array($login_fabrica, array(6,169,170))
                    || ($login_fabrica == 6 AND temNF($os, 'bool'))
                    || (in_array($login_fabrica, array(169,170)) && empty($fechamento))
                ) {
                # HD 2489168 --------------------------
                    if ($excluida == "f" || strlen($excluida) == 0) {
                        if ($login_fabrica == 1 && ($tipo_atendimento == 17 || $tipo_atendimento == 18)) {
                            if($nota_fiscal_saida > 0 OR ($valores_adicionais == 0 AND $nota_fiscal_saida == 0))
                                echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ('$os',document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                        } else {
                            if ($login_fabrica==19) {
                                if ($consumidor_revenda<>'R') {
                                    $sqlTipoAtendimento = "SELECT codigo
                                            FROM tbl_tipo_atendimento
                                            WHERE  tipo_atendimento = {$tipo_atendimento}";
                                    $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);
                                    $atendimento = pg_result($resTipoAtendimento, 0, codigo);
                                    if ($atendimento == 20) {
                                        $retornoShadow = "{$atendimento}";
                                    } else {
                                        $retornoShadow = "{$linha}-{$atendimento}";
                                    }
                                    $osSQL = "SELECT os_numero, os_sequencia FROM tbl_os WHERE os = $os";
                                    $resSQL = pg_query($con, $osSQL);
                                    $os_numero = pg_result($resSQL, 0, os_numero);
                                    $os_sequencia = pg_result($resSQL, 0, os_sequencia);
                                    $sql = "SELECT os
											FROM tbl_os
											JOIN tbl_os_extra USING(os)
                                            WHERE  os_numero = {$os_numero}
                                            and data_fechamento = data_abertura
											and excluida <> 't'
											and fabrica = $login_fabrica
                                            and (tipo_atendimento = 235 or tipo_atendimento = 335)
										    and tbl_os_extra.os_reincidente = $os
                                            and tbl_os.os <> $os";
                                    $resOS = pg_query($con, $sql);
                                    if (pg_num_rows($resOS) > 0 ){
                                        $retornoShadow = "nao_abrir_modal";
                                    }

                                    $sqlFechar = "SELECT defeito_constatado
                                                    FROM tbl_os
                                                    WHERE os = $os
                                                    AND tipo_atendimento <> 6
                                                    AND (defeito_constatado IS NULL OR tecnico_nome IS NULL)";
                                    $resFechar = pg_query($con, $sqlFechar);

                                    if (pg_num_rows($resFechar) > 0) {
                                        echo '&nbsp;';
                                    } else {

                                        if (verifica_checklist_tipo_atendimento($tipo_atendimento) && verifica_checklist_lancado($os)) {
                                            echo "<a class='btn-fechar-os-checklist' data-os='{$sua_os}'><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                        } else {
                                            echo "<a class='btn-fechar-os' data-texto='".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."' data-posicao='{$i}' data-shadowbox='{$retornoShadow}' data-os='{$os}'><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                        }
                                    }
                                                                   
                                }
                            } else {

                                if ($login_fabrica != 15) {
                                    if( in_array($login_fabrica, array(11,172)) && strlen($consumidor_email)>0 and $login_posto==14301){
                                        echo "<a href=\"javascript: if(confirm('".traduz("esta.os.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar';}\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                    } else {
                                        if ($login_fabrica == 20) {
                                            echo "<a href='#' onclick='fechaOSnovo($i);data_fechamento_$i.focus();'><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                        } else {
                                            //echo $consumidor_revenda;
                                            if($consumidor_revenda=='R' && ( in_array($login_fabrica, array(11,172)) || ($login_fabrica == 30 && $posto_digita_os_consumidor && $posto_digita_os_consumidor != 't'))) {

                                                #HD 111421 ----->
                                                $sua_os_x = $sua_os;
                                                $ache = "-";
                                                $posicao = strpos($sua_os_x,$ache);
                                                $sua_os_x = substr($sua_os_x,0,$posicao);
                                                #--------------->
                                                echo "<a href=\"javascript: if(confirm('".traduz("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os_x&btn_acao_pesquisa=continuar';}\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                            } else {
                                                if ($login_fabrica == 6){
                                                    #HD 311414 - BLOQUEIO DE BOTÕES QUANDO A OS POSSUIR REGISTROS NA TBL_OS_TROCA - INICIO


                                                        $tem_troca = 'f';
                                                        $sql_troca = "SELECT os from tbl_os_troca where os=$os";
                                                        $res_troca = pg_query($con,$sql_troca);

                                                        if (pg_num_rows($res_troca)>0){
                                                            $tem_troca = 't';
                                                        }

                                                        if ($tem_troca == 't'){
                                                            echo "&nbsp;";
                                                        }else{
                                                            echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                        }
                                                    #HD 311414 - BLOQUEIO DE BOTÕES QUANDO A OS POSSUIR REGISTROS NA TBL_OS_TROCA - FIM

                                                } else {

                                                    if (($login_fabrica == 86 AND $defeito_reclamado == NULL) || ($login_fabrica == 30 && $posto_digita_os_consumidor && $posto_digita_os_consumidor != 't')){
                                                          echo "&nbsp;";
                                                    } else if ($login_fabrica == 141) {
                                                            echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                    } else {

														if (($trava_finalizar_vonder != 't' and $login_fabrica != 163)  OR ($login_fabrica == 163 AND $trava_finalizar != 't' )) { //hd_chamado=2517023
                                                            //verificar se tem pedido
                                                            if($login_fabrica == 3 and $cancelada != 't'){
                                                                echo "<a href=\"javascript: if(confirm('".traduz("os.sera.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar';}\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";

                                                            } else if ($login_fabrica != 156 || ($posto_interno != true)) {
                                                                if($fCancelaOS){
                                                                    if($cancelada != "t"){
                                                                        echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                                    }
                                                                } else {
                                                                    if ($login_fabrica == 148) {//hd_chamado=3049906
                                                                        if ($os_status_cancelada != 't') {
                                                                            echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                                        }
                                                                    } else {
                                                                        if ($login_fabrica == 162 && $posto_interno === TRUE) {
                                                                            if ($login_unico_master == 't') {
                                                                                echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                                            } else if ($tecnico_os == $login_unico_tecnico) {
                                                                                echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                                            }
                                                                        } else if ($login_fabrica == 74) {
                                                                            if (!(strtoupper($linha_nome) == "FOGO" && !empty($hd_chamado))) {
                                                                                echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                                            }
                                                                        } else {
                                                                            if ($login_fabrica == 165 and $posto_interno === true) {
                                                                            } else {
																				if (in_array($login_fabrica, array(152,180,181,182))) {

																					if ($cancelada != 't') {
																						echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
																					}
																				} elseif (in_array($login_fabrica, array(180)) and $cancelada != 't') {
                                                                                    echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='admin_es/imagens/btn_cerrar.png'></a>";

                                                                                }else{
                                                                                    if ($login_fabrica == 30) {
                                                                                        $sqlValidaPosto = "select parametros_adicionais from tbl_posto_fabrica where fabrica = {$login_fabrica} and posto = {$login_posto}";
                                                                                        $resValidaPosto = pg_query($con, $sqlValidaPosto);
                                                                                        $parametros_adicionais = json_decode(pg_fetch_result($resValidaPosto, 0, parametros_adicionais));
                                                                                        if (isset($parametros_adicionais->digita_os_consumidor) || $parametros_adicionais->digita_os_consumidor == 't' ) {
                                                                                            $btn_fechar = "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                                                        }
                                                            							

                                                                                        $sql_os_bloqueada = "SELECT
                                                                                                    os_bloqueada
                                                                                                FROM tbl_os_campo_extra
                                                                                                WHERE fabrica = {$login_fabrica}
                                                                                                    AND os = {$os};";
                                                                                        $res_os_bloqueada = pg_query($con, $sql_os_bloqueada);
                                                                                        if (pg_num_rows($res_os_bloqueada) > 0) {
                                                                                            $os_bloqueada = pg_fetch_result($res_os_bloqueada, 0, "os_bloqueada");
                                                                                            if ($os_bloqueada == 't')
                                                                                                $btn_fechar = "";
                                                                                        }
                                                                                        echo $btn_fechar;
                                                                                    }
                                                                                    if (!in_array($login_fabrica, array(30,35,169,170, 177,178,183,184,186,200))) {
                                                                                        if (isset($novaTelaOs)) {
                                                                                            if($login_fabrica == 131){
                                                                                                    $chama_funcao = "confirmaRecebimentoPeca";    
                                                                                            } else {
                                                                                                $chama_funcao = "fechaOS";
                                                                                            }

                                                                                            if (in_array($login_fabrica, [151])) {

                                                                                                $sqlStatus = "SELECT tbl_os.status_checkpoint
                                                                                                              FROM tbl_os
                                                                                                              JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
                                                                                                              WHERE tbl_os.os = {$os}
                                                                                                              AND tbl_status_checkpoint.descricao = 'Aguardando Analise Helpdesk'";
                                                                                                $resStatus = pg_query($con, $sqlStatus);

                                                                                                if (pg_num_rows($resStatus) == 0) {

                                                                                                    echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { " . $chama_funcao . " ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i'), document.getElementById('consertado_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";

                                                                                                }

                                                                                            } else {

                                                                                                echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { " . $chama_funcao . " ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i'), document.getElementById('consertado_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";

                                                                                            }

                                                                                        } else {
                                                                                            if($trava_acoes_positec != 't'){
                                                                                            echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,document.getElementById('sinal_$i'),document.getElementById('excluir_$i'), document.getElementById('lancar_$i')) ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                    //redireciona para nova tela de  fechamento de os
                                                                                    if (in_array($login_fabrica, array(178,183,184,186,200))) {
                                                                                            echo "<a href=\"fechamento_os.php?sua_os=$sua_os&btn_acao=submit\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                                                                    }
																				}
                                                                            }
                                                                        }
                                                                    }
                                                                }

                                                            }
														}
													}
                                                }
                                            }
                                        }
                                    }
                                    //echo $consumidor_revenda;
                                }else{
                                    if($consumidor_revenda<>'R'){

                                        echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0'  src='imagens/btn_fecha.gif'></a>";
                                    }else{
                                        echo "<a href=\"javascript: if(confirm('".traduz("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar';}\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                if ($login_fabrica == 51 AND $status_os =='62') {
                    echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,'', '') ; }\"><img id='sinal_$i' border='0' src='imagens/btn_fecha.gif'></a>";
                } else {
                    //HD 204146: Fechamento automático de OS
                    if ($login_fabrica == 3) {
                        $sql = "SELECT sinalizador FROM tbl_os WHERE os=$os";
                        $res_sinalizador = pg_query($con, $sql);
                        $sinalizador = pg_result($res_sinalizador, 0, sinalizador);
                    }

                    if ($sinalizador == 18) {
                        echo "<img border='0' src='imagens/btn_fecha.gif' onclick='alert(\"".traduz("esta.os.foi.fechada.automaticamente.pelo.sistema.utilizar.a.tela.de.fechamento.de.os.para.informar.a.entrega.do.produto.ao.consumidor.e.enviar.a.nota.fiscal.para.a.fabrica.para.pagamento.da.mao.de.obra")."\")' style='cursor:pointer;'>";
                    }
                    else {
                        echo "&nbsp;";
                    }
                }
            }

            echo "</td>\n";

            }

            $botao_consertado = "";
            // retirar OS fixa assim q posto responder laudo
            if ((usaDataConserto($login_posto, $login_fabrica) && !in_array($login_fabrica, array(164,167,169,170,171,173,176,203)) && empty($finalizada)) || ($os == 53341755 && $login_fabrica == 175)) { //HD 13239

                if (empty($finalizada) || $login_fabrica == 175) {
                    if ($login_fabrica == 175) {
                        echo "<td align='center' $style_td nowrap >";
                    } else {
                        echo "<td width='60' align='center' $style_td >";
                    }
                }

		if (empty($finalizada)) {
                //HD:44202
                //HD 214236: Travar as opções quando a OS estiver em auditoria
                if (($login_fabrica == 14 || $login_fabrica == 43 or $login_fabrica == '131') && ($auditoria_travar_opcoes)) {
                } else if (
                    $login_fabrica == 3
                    && in_array($status_os,array(120,122,126,140,141,143,174))
                    || ($status_os == "62" && $login_fabrica == 6)
                    || (in_array($status_os,array(20,81)) && $login_fabrica == 101)
                    || in_array($login_fabrica, array(158))
                    || (in_array($login_fabrica, array(169,170)) && !empty($data_conserto))
                ){
                    echo "&nbsp;";
                } else {
                    $os_troca = false;

                    if ((strlen($data_conserto) == 0)) {
                        if ($login_fabrica == 6) {
                            #HD 311414 - BLOQUEIO DE BOTÕES QUANDO A OS POSSUIR REGISTROS NA TBL_OS_TROCA - INICIO
                                $tem_troca = 'f';
                                $sql_troca = "SELECT os from tbl_os_troca where os=$os";
                                $res_troca = pg_query($con,$sql_troca);

                                if (pg_num_rows($res_troca)>0){
                                    $tem_troca = 't';
                                }

                                if ($tem_troca == 't'){
                                    $botao_consertado = "&nbsp;";
                                }else{
                                    $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' src='imagens/btn_consertado.gif'></a>";
                                }
                            #HD 311414 - BLOQUEIO DE BOTÕES QUANDO A OS POSSUIR REGISTROS NA TBL_OS_TROCA - FIM
                            } else {
                                if($login_fabrica == 72){
                                    $botao_consertado = "";
                                    if(strlen(trim($defeito_reclamado_descricao))>0 and strlen(trim($defeito_constatado))>0 and strlen(trim($solucao))>0){
                                        $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";
                                    }
                                } else {

                                    if ($login_fabrica == 20) {
                                        if($status_checkpoint == 9){
                                            $botao_consertado = "";
                                        }else{
                                            $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";
                                        }
                                    } else {
                                        if ($login_fabrica == 148) {//hd_chamado=3049906
                                            if($os_status_cancelada <> 't'){
                                                $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";
                                            }
                                        } else {
                                            if (in_array($login_fabrica, array(152,180,181,182))) {
                                                if ($cancelada != 't') {
                                                    $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";
                                                }
                                            } else {
                                                if ($login_fabrica == 35) {
							                        $confirma = 0;
                                                	if ($tipo_atendimento == 100) {
                                                    		$sqlVerVisita = "
                                                        		SELECT  COUNT (tbl_tecnico_agenda.tecnico_agenda) AS visitas_confirmadas
                                                        		FROM    tbl_tecnico_agenda
                                                        		WHERE   tbl_tecnico_agenda.os = $os

                                                    		";
                                                    		$resVerVisita = pg_query($con,$sqlVerVisita);
                                                    		$visitas_confirmadas = pg_fetch_result($resVerVisita,0,visitas_confirmadas);

                                                    		if ($visitas_confirmadas > 0) {
                                                        		$sqlVerVisita = "
                                                            			SELECT  COUNT (tbl_tecnico_agenda.tecnico_agenda) AS visitas_confirmadas
                                                            			FROM    tbl_tecnico_agenda
                                                            			WHERE   tbl_tecnico_agenda.os = $os
                                                            			AND     tbl_tecnico_agenda.confirmado IS NULL
                                                        		";
                                                        		$resVerVisita           = pg_query($con,$sqlVerVisita);
                                                        		$visitas_confirmadas    = pg_fetch_result($resVerVisita,0,visitas_confirmadas);

                                                        		if ($visitas_confirmadas == 0) {
                                                            			$confirma += 1; 
                                                        		}
                                                    		}
                                                	}
                                                	if ($confirma != 0 || $tipo_atendimento != 100) {
								                        $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("clique.em.ok.para.encerrar.este.atendimento",$con,$cook_idioma)."\\n".traduz("lembre-se.de.avisar.o.consumidor.e.solicitar.a.retirada.do.produto",$con,$cook_idioma)."') == true) {novoFinalizarOsCadence($os);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";
                                                	}
                                                } else if (in_array($login_fabrica, array(175))) {
                                                    if ($LU_tecnico_posto === true) {          
                                                        $sql_treinamento = "
                                                            SELECT DISTINCT
                                                                tp.treinamento,
                                                                tp.aprovado
                                                            FROM tbl_treinamento t
                                                            INNER JOIN tbl_treinamento_tipo ON tbl_treinamento_tipo.treinamento_tipo = t.treinamento_tipo 
                                                                AND tbl_treinamento_tipo.nome = $1 
                                                                AND tbl_treinamento_tipo.fabrica = {$login_fabrica}
                                                            INNER JOIN tbl_treinamento_produto tpd ON tpd.treinamento = t.treinamento 
                                                                AND tpd.fabrica = {$login_fabrica} 
                                                                AND (tpd.produto = {$produto_id} OR tpd.linha = {$linha_id})
                                                            LEFT JOIN tbl_treinamento_posto tp ON tp.treinamento = t.treinamento 
                                                                AND tp.tecnico = {$login_tecnico_id}
                                                            WHERE t.fabrica = {$login_fabrica}
                                                            AND tp.aprovado IS TRUE
                                                        ";
                                                        $res_treinamento = pg_query_params($con,$sql_treinamento,array('Online'));

                                                        // ONLINES
                                                        if (pg_num_rows($res_treinamento) > 0){
                                                            for ($z=0; $z < pg_num_rows($res_treinamento); $z++){
                                                                unset($aprovado);
                                                                $aprovado = pg_fetch_result($res_treinamento,$z,'aprovado');
                                                            
                                                                if ($aprovado == 't'){
                                                                    $botao_consertado =  "<a href=\"javascript: consertadoOS ($os,document.consertado_$i,$i);\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";            
                                                                }
                                                            }
                                                        }else{
                                                            // PRESENCIAIS 
                                                            $sql_treinamento .= " AND tp.validade_certificado >= CURRENT_DATE; ";
                                                            $res_treinamento  = pg_query_params($con,$sql_treinamento,array('Presencial'));

                                                            if (pg_num_rows($res_treinamento) > 0){
                                                                for ($z=0; $z<pg_num_rows($res_treinamento); $z++){
                                                                    unset($aprovado);
                                                                    $aprovado = pg_fetch_result($res_treinamento,$z,'aprovado');
                                                                
                                                                    if ($aprovado == 't'){
                                                                        $botao_consertado =  "<a href=\"javascript: consertadoOS ($os,document.consertado_$i,$i);\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";            
                                                                    }
                                                                }
                                                            }    
                                                        }                                                        
                                                
                                                        if ($LU_tecnico_posto === true AND $status_checkpoint == 3) {
                                                            $botao_consertado =  "<a href=\"javascript: consertadoOS ($os,document.consertado_$i,$i);\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";
                                                        }else{
                                                            $botao_consertado = "";
                                                        }
                                                    }
                                                    
                                                } else {
                    							if($login_fabrica == 3 and $cancelada != 't'){
                    							    $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";
                	                                }elseif($login_fabrica != 3){
                                                        if ($login_fabrica == 177 && $defeito_constatado == NULL) {
                                                            $botao_consertado = "";
                                                        }else{
                                                            if($trava_acoes_positec != 't'){
                                                                
                                                                if ($login_fabrica == 160) {

                                                                     $queryDataConserto = " SELECT COUNT(tbl_os_item.peca) total
                                                                                            FROM tbl_os_item
                                                                                            JOIN tbl_os_produto USING(os_produto)
                                                                                            JOIN tbl_servico_realizado USING(servico_realizado)
                                                                                            LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item 
                                                                                            LEFT JOIN tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido 
                                                                                            AND (tbl_pedido_item.peca = tbl_faturamento_item.peca OR tbl_pedido_item.peca_alternativa = tbl_faturamento_item.peca)
                                                                                            LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                                                                            LEFT JOIN tbl_embarque_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
                                                                                            WHERE tbl_os_produto.os = {$os}
                                                                                            AND tbl_servico_realizado.troca_de_peca IS TRUE
                                                                                            AND tbl_servico_realizado.gera_pedido IS TRUE
                                                                                            AND (tbl_os_item.pedido IS NULL 
                                                                                                  OR (tbl_os_item.pedido IS NOT NULL AND tbl_faturamento_item.pedido IS NULL AND tbl_pedido_item.qtde > (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor)) 
                                                                                                  OR (tbl_faturamento.nota_fiscal = '000000' AND tbl_faturamento_item.pedido IS NOT NULL)
                                                                                                  OR (tbl_faturamento.nota_fiscal <> '000000' AND tbl_faturamento_item.pedido IS NOT NULL AND tbl_pedido_item.qtde > (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor))
                                                                                                  OR (tbl_os_item.pedido IS NOT NULL AND tbl_faturamento.nota_fiscal IS NULL AND tbl_faturamento_item.pedido IS NULL AND tbl_pedido_item.qtde > (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor))
                                                                                                  OR (tbl_os_item.pedido IS NOT NULL AND tbl_faturamento.nota_fiscal IS NULL AND tbl_faturamento_item.pedido IS NULL AND tbl_embarque_item.pedido_item IS NOT NULL)
                                                                                                )";

                                                                    $resDataConserto = pg_query($con, $queryDataConserto);

                                                                    $pedidos = pg_fetch_result($resDataConserto, 0, 'total');

                                                                    if ($pedidos == 0) {
                                                                        $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";

                                                                    } else {

                                                                        $botao_consertado = "&nbsp;";
                                                                    }

                                                                } else { 

                                                                    if (in_array($login_fabrica, [151])) {

                                                                        $sqlStatus = "SELECT tbl_os.status_checkpoint
                                                                                      FROM tbl_os
                                                                                      JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
                                                                                      WHERE tbl_os.os = {$os}
                                                                                      AND tbl_status_checkpoint.descricao = 'Aguardando Analise Helpdesk'";
                                                                        $resStatus = pg_query($con, $sqlStatus);

                                                                        if (pg_num_rows($resStatus) == 0) {

                                                                            $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";

                                                                        }

                                                                    } else {

                                                                        $botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,document.consertado_$i,$i);}\"><img id='consertado_$i' border='0' $display_button_cancelado src='imagens/btn_consertado.gif'></a>";

                                                                    }
                                                                }
                                                            }
                                                        }
                                	                }
                                                }
                                                if ($login_fabrica == 30) {
                                                    $sql = "SELECT
                                                                os_bloqueada
                                                            FROM tbl_os_campo_extra
                                                            WHERE fabrica = {$login_fabrica}
                                                                AND os = {$os};";

                                                    $res = pg_query($con, $sql);
                                                    if (pg_num_rows($res) > 0) {
                                                        $os_bloqueada = pg_fetch_result($res, 0, "os_bloqueada");
                                                        if ($os_bloqueada == 't')
                                                            $botao_consertado = "";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if ($cancelaOS && $excluida == "t") {
                                unset($botao_consertado);
                            }

                            if ($login_unico_tecnico_posto != "t" AND $login_fabrica == 175){
                                unset($botao_consertado);
                            }

                            if ( in_array($login_fabrica, array(11,172)) ){
                                $sqlX ="SELECT os_troca,ressarcimento
                                        FROM tbl_os_troca
                                        WHERE os = $os";
                                $resX = pg_query($con,$sqlX);
                                if(pg_num_rows($resX)==1){
                                    $os_troca = true;
                                }
                                if ($os_troca == false){
                                    echo $botao_consertado;
                                }
                            }else{
                                echo $botao_consertado;
                            }
                        }
                    }
                }
                if (!empty($data_conserto) && $usaLaudoTecnicoOs && $LU_tecnico_posto === true) {
                    $sqlLaudoTecnico = "
                        SELECT ordem FROM tbl_laudo_tecnico_os WHERE fabrica = {$login_fabrica} AND os = {$os}
                    ";
                    $resLaudoTecnico = pg_query($con, $sqlLaudoTecnico);
                    
                    if (pg_num_rows($resLaudoTecnico) > 0) {
                        ?>
                        <button type='button' class='btn-visualizar-laudo-tecnico' data-os='<?=$os?>' style='cursor: pointer;' >Laudo Técnico</button>
                        <?php
                        if ($login_fabrica == 175 && pg_fetch_result($resLaudoTecnico, 0, 'ordem') == $login_unico) {
                        ?>
                            &nbsp;
                            <button type='button' class='btn-certificado-calibracao' data-os='<?=$os?>' style='cursor: pointer;' >Certificado de Calibração</button>
                        <?php
                        }
                    }
                }

                echo "</td>";

            } else if (!empty($finalizada) && in_array($login_fabrica, [139,183,184,200])) {
                echo "<td></td>";
            } 
    
        }

        if ($login_fabrica == 35) {
            echo "<td></td>";  
        }

            if (in_array($login_fabrica, array(169,170,174))){
                if ($postagem_coleta == "true"){
            ?>
                <td colspan="4" style="width: 320px; text-align: center; <?=$display_lgr?>" id='lgr_correios'>
                    <button style="padding:5px; margin-top: 4px; margin-bottom: 4px; cursor: pointer;" type="button" onclick="javascript: solicitaPostagem('<?=$hd_chamado?>','<?=$codigo_posto;?>');"><?php echo traduz("solicitacao.postagem/coleta");?></button>
                </td>
            <?php
                }
            }
            
            if (in_array($login_fabrica, [144]) && $posto_interno && empty($btn_acao_pre_os)) {

                $sqlLaudo = "SELECT tbl_laudo_tecnico_os.laudo_tecnico_os
                             FROM tbl_laudo_tecnico_os 
                             WHERE tbl_laudo_tecnico_os.os = {$os}
                             AND tbl_laudo_tecnico_os.fabrica = {$login_fabrica}";
                $resLaudo = pg_query($con, $sqlLaudo);

                if (pg_num_rows($resLaudo) > 0) {

                    $desc_btn_recusa = "Alterar Laudo";

                } else {

                    $desc_btn_recusa = "Recusar Garantia";

                }

                ?>
                <td>
                    <?php
                    if (!$finalizada) { ?>
                        <a href="laudo_recusa_hikari.php?os=<?= $os ?>" target="_blank">
                            <button style="cursor: pointer;" type="button" class="btn-recusa-garantia"><?= traduz($desc_btn_recusa) ?></button>
                        </a>
                    <?php
                    } ?>
                </td>
                <td>
                    <?php
                    if (pg_num_rows($resLaudo) > 0) { ?>
                        <a href="laudo_recusa_hikari.php?os=<?= $os ?>&print=true" target="_blank">
                            <button style="cursor: pointer;" type="button" class="btn-recusa-garantia"><?= traduz("Imprimir Laudo") ?></button>
                        </a>
                    <?php
                    }
                    ?>
                </td>
            <?php
            }

            if($status_os == 190 and $login_fabrica == 6){
                echo "<td><button onclick='anexar_nf($os)' style='line-height:15px; padding:0; height:15px; width:80px; font-size:10px; font-weight:bold;'>".traduz("anexar.nf")."</button></td>";
            }

            if($login_fabrica == 96 && strtolower($btn_acao) == "pesquisar"){
                echo "<td>";
                    echo "<input type='button' value=' Laudo ' onclick='javascript: abreLaudo($os);' alt=' ".traduz("visualizar/gerar.laudo")." ' />";
                echo "</td>";
            }

            if($login_fabrica == 3){
                echo "<td style='padding: 5px;'>";
                    if(strlen($hd_chamado) == 0){
                        echo "<a href='helpdesk_cadastrar.php?os={$sua_os}' target='_blank'><input type='button' value=' Novo Atendimento ' alt=' ".traduz("novo.atendimento")." ' /></a>";
                    }
                echo "</td>";
            }

            echo "</tr>";
            if ($login_fabrica == 20) { //hd 88308 waldir HD 196744
                echo "<form name='frm_fechar' id='frm_fechar' method='post'>";
                echo "<tr>";
                    echo "<td colspan='14' align='center'><div id='mostrar_$i'></div>";
                    ?>
                        <div id='div_fechar_<?echo $i;?>' style='display: none ; background-color:#eeeeff ; width: 300px ; height: 25px ; text-align: right; border:solid 1px #330099 ' onkeypress="if(event.keyCode==27){div_fechar_<?echo $i;?>.style.display='none' ;}">
                        <div id="div_lanca_peca_fecha" style="float:right ; align:center ; width:20px ; background-color:#FFFFFF " onclick="div_fechar_<?echo $i;?>.style.display='none' ;" onmouseover="this.style.cursor='pointer'"><center><b>X</b></center>
                        </div>
                        <input type='hidden' size='12' name='os_fechar' id='os_fechar' value='<?echo $os;?>'>
                        <? fecho("data",$con,$cook_idioma); ?>
                        <input type='text' size='12' maxlength="10" name='data_fechamento_<? echo $i;?>' id='data_fechamento_<? echo $i?>' onKeyPress='DataHora(event, this)'> <input type='button' value='<? fecho("fechar.os",$con,$cook_idioma); ?>' onclick="javascript: if (data_fechamento_<? echo $i;?>.value.length<10){ alert('<?php echo traduz("digite.uma.data.no.formato");?> dd/mm/aaaa!');} else {fechaOSnovo2(<?php echo $os; ?>,data_fechamento_<? echo $i;?>.value,lancar_<? echo $i ?>,<?echo $i?>); }">
                        </div>
                    <?
                    echo "<td>";
                echo "</tr>";
                echo "</form>";
            }

        }

        //HD 371911
        ?>

    <?php echo "</table>"; ?>
    <? if(!in_array($login_fabrica,array(1,14,30,175))){?>
        <tr>
            <td colspan="19" class="titulo_coluna" style="text-align:left;">
                <form action="os_print_varios.php" id="imprimir_varios" name="imprimir_varios" target="_blank" method="post">
                    <?php echo traduz("formato.da.pagina");?>
                    <select name="formato_arquivo" class="frm">
                        <? if($login_fabrica == 180) { ?>
                            <option value="jacto"><?php echo traduz("impressora.de.tinta/laser");?></option>
                        <? } else { ?>
                            <option value="jacto"><?php echo traduz("jato.de.tinta/laser");?></option>
                        <? } ?>
                        <option value="matricial"><?php echo traduz("matricial");?></option>
                    </select>
                    <input type="button" value="Imprimir Selecionados" class="imprimir_botao" id="imprimir_botao" style="cursor:pointer" />
                    <span class="msg_erro" id="erro_imprimir" style="display:none;padding:2px 10px;"><?php echo traduz("nenhuma.os.selecionada");?></span>
                </form>
            </td>
        </tr>
        <?php }
        echo "</table>";
    } else {
        echo traduz("Nenhuma OS encontrada");
    }
    ?>

        <!-- -------------------------------------------------------------- -->

    <?


    ##### PAGINAÇÃO - INÍCIO #####
    echo "<br>";
    echo "<div>";

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
        echo "<div>";
        echo traduz("resultados.de")." <b>$resultado_inicial</b> a <b>$resultado_final</b> ".traduz("do.total.de")." <b>$registros</b> ".traduz("registros").".";
        echo "<font color='#cccccc' size='1'>";
        echo " (".traduz("pagina")." <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
        echo "</font>";
        echo "</div>";
    }
    ##### PAGINAÇÃO - FIM #####

    echo "<br><h4 style='color: #888888;'><b>".traduz("resultado").": $resultados ".traduz("registro(s)",$con,$cook_idioma).".</b></h4>";
}
?>

<?php

    if($_POST['gerar_excel'] == 't' AND ($login_fabrica == 165) || ($login_fabrica == 158)) {
        if(pg_num_rows($resexcell) > 0){
            $host   = $_SERVER['SCRIPT_NAME'];
            $host   = str_replace('/os_consulta_lite.php','',$host);
            $path_2 = getcwd();

            flush();
            $data = date ("d/m/Y H:i:s");

            $path             = "/xls/";
            #$path             = "/monteiro_teste/";
            $arquivo_nome    = "consulta-os-$login_posto.xls";
            $arquivo_completo = $path_2.$path.$arquivo_nome;
            $caminho_donwload = $host.$path.$arquivo_nome;
            $fp = fopen ($arquivo_completo,"w+");

            fputs ($fp,"<table border='1' bordercolor='#000000'  align='center' width='100%'>");
            if($login_fabrica == 158){
                fputs ($fp," <td nowrap><b>".strtoupper(traduz("os"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("patrimonio"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("serie"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("data.ab"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("data.fc"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("posto"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("cep"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("endereço"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("bairro"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("cidade"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("estado"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("cliente"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("telefone"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("defeito.reclamado"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("defeito.constatado"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("solucao"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("observacao"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("observacao.kof"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("os.kof"))."</b></td>
                    <td nowrap><b>".strtoupper(traduz("produto"))."</b></td>");
            }else {
                fputs ($fp,"<tr><td nowrap><b>".strtoupper(traduz("os"))."</b></td>
                        <td nowrap><b>".strtoupper(traduz("produto"))."</b></td>
                        <td nowrap><b>".strtoupper(traduz("serie"))."</b></td>
                        <td nowrap><b>".traduz("consumidor/revenda")."</b></td>");

                fputs ($fp," <td nowrap><b>".strtoupper(traduz("nf"))."</b></td>
                        <td nowrap><b>".strtoupper(traduz("defeito.constatado"))."</b></td>
                        <td nowrap><b>".strtoupper(traduz("servico.realizado"))."</b></td>");
                if($login_fabrica == 165){
                    fputs ($fp,"<td nowrap><b>".strtoupper(traduz("produto.trocado"))."</b></td>");
                }
            }
            

            

            fputs($fp, "</tr>");

            for($x =0;$x<pg_num_rows($resexcell);$x++) {
                $cor   = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
                $os_excell                      = pg_fetch_result($resexcell, $x, 'os_excell');
                $consumidor_revenda_excell      = pg_fetch_result($resexcell, $x, 'consumidor_revenda');
                $nota_fiscal_excell             = pg_fetch_result($resexcell, $x, 'nota_fiscal');
                $serie_excell                   = pg_fetch_result($resexcell, $x, 'serie_os');
                $descricao_excell               = pg_fetch_result($resexcell, $x, 'servico_descricao');
                $produto_referencia_excell      = pg_fetch_result($resexcell, $x, 'produto_referencia');
                $produto_descricao_excell       = pg_fetch_result($resexcell, $x, 'produto_descricao');
                $defeito_constatado_descricao   = pg_fetch_result($resexcell, $x, 'defeito_constatado_descricao');

                $consumidor_nome_excell         = pg_fetch_result($resexcell, $x, 'consumidor_nome');
                $revenda_nome_excell            = pg_fetch_result($resexcell, $x, 'revenda_nome');

                if($login_fabrica == 158){
                    $patrimonio             = pg_fetch_result($resexcell, $x, 'serie_justificativa');
                    $serie                  = pg_fetch_result($resexcell, $x, 'serie');
                    $codigo_posto           = pg_fetch_result($resexcell, $x, 'codigo_posto');
                    $nome                   = pg_fetch_result($resexcell, $x, 'nome');
                    $cep                    = pg_fetch_result($resexcell, $x, 'consumidor_cep');
                    $consumidor_endereco    = pg_fetch_result($resexcell, $x, 'consumidor_endereco');
                    $consumidor_bairro      = pg_fetch_result($resexcell, $x, 'consumidor_bairro');
                    $consumidor_fone        = pg_fetch_result($resexcell, $x, 'consumidor_fone');
                    $obs                    = pg_fetch_result($resexcell, $x, 'obs');
                    $abertura               = pg_fetch_result($resexcell, $x, 'abertura');
                    $fechamento             = pg_fetch_result($resexcell, $x, 'fechamento');
                    $observacao_kof         = json_decode(pg_fetch_result($resexcell, $x, 'observacao_kof'), true);
                    $obs_kof                = pg_fetch_result($resexcell, $x, 'obs_kof');
                    $os_kof                 = pg_fetch_result($resexcell, $x, 'os_kof');
                    $defeito_reclamado      = pg_fetch_result($resexcell, $x, 'desc_defeito_reclamado');                   
                }
                

                if($login_fabrica == 165){
                    $produto_trocado = pg_fetch_result($resexcell, $x, 'produto_trocado');
                }

                if($consumidor_revenda_excell == "C"){
                    $consumidor_revenda_excell = "Consumidor";
                    $nome_consumidor_revenda = $consumidor_nome_excell;
                }elseif($consumidor_revenda_excell == "R"){
                    $consumidor_revenda_excell = "Revenda";
                    $nome_consumidor_revenda = $revenda_nome_excell;
                }

                    if($login_fabrica == 158){
                        $sql_defeito = "SELECT tbl_os_defeito_reclamado_constatado.defeito_constatado, 
                                           tbl_defeito_constatado.descricao as descricao_defeito_constatado, 
                                           tbl_solucao.descricao as descricao_solucao, 
                                           tbl_os_defeito_reclamado_constatado.solucao 
                                           from tbl_os_defeito_reclamado_constatado 
                                           left join tbl_defeito_constatado using(defeito_constatado) 
                                           left join tbl_solucao using(solucao) 
                                           where os = $os_excell";

                        $res_defeito = pg_query($con, $sql_defeito);
                        if(pg_num_rows($res_defeito) > 0){
                            $todosdefeito = null;
                            $todasSolucao = null;
                            for($d =0; $d < pg_num_rows($res_defeito); $d++){
                                $todosdefeito[] = pg_fetch_result($res_defeito, $d, 'descricao_defeito_constatado');
                                $todasSolucao[] = pg_fetch_result($res_defeito, $d, 'descricao_solucao');
                            }

                                fputs ($fp,"<tr align='left'>");
                                fputs ($fp,"<td bgcolor='$cor'>".$os_excell."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$patrimonio."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$serie."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$abertura."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$fechamento."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$codigo_posto."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$cep."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_endereco."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_bairro."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_cidade."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_estado."</td>");
                                if($consumidor_nome_excell != ''){
                                    fputs ($fp,"<td bgcolor='$cor'>".$consumidor_nome_excell."</td>");
                                } else { 
                                    fputs($fp,"<td bgcolor='$cor'>".$revenda_nome_excell."</td>");
                                }
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_fone."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$defeito_reclamado."</td>");                                
                                fputs ($fp,"<td bgcolor='$cor'>".implode(' ', $todosdefeito)."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".implode(' ', $todasSolucao)."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$obs."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$observacao_kof['comentario']."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$os_kof."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$produto_referencia_excell.' - '.$produto_descricao_excell."</td>");
                                fputs ($fp,"</tr>");
                        }else{
                            fputs ($fp,"<tr align='left'>");
                                fputs ($fp,"<td bgcolor='$cor'>".$os_excell."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$patrimonio."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$serie."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$abertura."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$fechamento."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$codigo_posto."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$cep."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_endereco."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_bairro."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_cidade."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_estado."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$nome_consumidor_revenda."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$consumidor_fone."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$defeito_reclamado."</td>");      
                                fputs ($fp,"<td bgcolor='$cor'></td>");
                                fputs ($fp,"<td bgcolor='$cor'></td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$obs."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$observacao_kof['comentario']."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$os_kof."</td>");
                                fputs ($fp,"<td bgcolor='$cor'>".$produto_referencia_excell.' - '.$produto_descricao_excell."</td>");
                                fputs ($fp,"</tr>");
                        }
                    
                        
                    }else{
                        fputs ($fp,"<tr align='left'>");
                        fputs ($fp,"<td bgcolor='$cor'>".$os_excell."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$produto_referencia_excell." - ".$produto_descricao_excell."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$serie_excell."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$nome_consumidor_revenda."</td>");
    
                        fputs ($fp,"<td bgcolor='$cor'>".$nota_fiscal_excell."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$defeito_constatado_descricao."</td>");
                        fputs ($fp,"<td bgcolor='$cor'>".$descricao_excell."</td>");
                        if($login_fabrica == 165){
                            fputs ($fp,"<td nowrap bgcolor='$cor' >$produto_trocado</td>");
                        }
                    fputs ($fp,"</tr>");
                    }
            }
            fputs ($fp, "</table>");
            $resposta = "<br>";
            $resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
            $resposta .="<tr>";
            $resposta .= "<td align='center' style='border: 0; font: bold 14px \"Arial\";'><a href=\"$caminho_donwload\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"imagens/excel.png\" height=\"20px\" width=\"20px\" align=\"absmiddle\">&nbsp;&nbsp;&nbsp;".traduz("gerar.arquivo.excel")."</a></td>";
            $resposta .= "</tr>";
            $resposta .= "</table>";
            echo $resposta;
        }
    }


    $sua_os             = trim (strtoupper ($_POST['sua_os']));
    if (strlen($sua_os)==0) $sua_os = trim(strtoupper($_GET['sua_os']));
    $serie              = trim (strtoupper ($_POST['serie']));
    if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
    $nf_compra          = trim (strtoupper ($_POST['nf_compra']));
    if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
    $consumidor_cpf     = trim (strtoupper ($_POST['consumidor_cpf']));
    if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));
    $produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
    if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
    $produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));
    if (strlen($produto_descricao)==0) $produto_descricao = trim(strtoupper($_GET['produto_descricao']));

    $mes = trim (strtoupper ($_POST['mes']));
    $ano = trim (strtoupper ($_POST['ano']));

    $codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
    if (strlen($codigo_posto)==0) $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
    $posto_nome      = trim (strtoupper ($_POST['posto_nome']));
    if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
    $consumidor_nome = trim ($_POST['consumidor_nome']);
    if (strlen($consumidor_nome)==0) $consumidor_nome = trim($_GET['consumidor_nome']);
    $os_situacao     = trim (strtoupper ($_POST['os_situacao']));
    if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
?>


<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">
<table align="center" width="700" border="0">
    <tr>
        <td class="texto_avulso" align="center">
            <?php echo traduz("este.relatorio.considera.a.data.de.digitacao.da.os");?>
        </td>
    </tr>
</table>
<table align="center" class="formulario" width="700" border="0">
    <tr>
        <td class="titulo_tabela" align="center"><?php echo traduz("parametros.de.pesquisa");?></td>
    </tr>
</table>

<table align="center" class="formulario" width="700" border="0">
    <tr align='left'>
        <td class="espaco"><? fecho("numero.da.os",$con,$cook_idioma)?></td>
        <?
        if (in_array($login_fabrica, array(10,19)) || ($login_fabrica == 20 AND $login_pais =='AR')) {
            if (in_array($login_fabrica, array(10, 19))) {
                echo "<td>".traduz("os.off.line")."</td>";
            } else {
                echo "<td>".traduz("os.interna",$con,$cook_idioma)."</td>";
            }
        }

        if ($login_fabrica != 160 and !$replica_einhell) { ?>
            <td>
                <?
                if($login_fabrica==35){
                    echo "PO#";
                }else{
                    fecho("numero.de.serie",$con,$cook_idioma);
                }
                ?>
            </td>
        <? } ?>
        <td>
            <? fecho("nf.compra",$con,$cook_idioma); ?>
        </td>
    </tr>
    <tr align='left'>
        <td class="espaco"><input type="text" name="sua_os"    size="10" value="<?echo $sua_os?>"    class="frm"></td>
        <? if($login_fabrica==19 OR $login_fabrica==10 OR ($login_fabrica==20 AND $login_pais=='AR')){ ?>
            <td><input type="text" name="os_off" size="8" value="<?echo $os_off?>" class="frm"></td>
        <? } if($login_fabrica != 160){ ?>
            <td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
        <? } ?>
        <td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm" id='nf_compra'></td>
    </tr>
    <tr align='left'>
        <td class="espaco">
        <?

        if ($login_fabrica == 169) { /*HD - 4373381*/
            echo "CPF / ";
            fecho ("CNPJ.consumidor", $con, $cook_idioma);
        } else {
            fecho ("cpf.consumidor",$con,$cook_idioma);
        }

        ?>
        </td>
        <? if($login_fabrica==19 OR $login_fabrica==10 OR ($login_fabrica==20 AND $login_pais=='AR')){ ?>
        <td></td>
        <? } ?>

        <?php if(!in_array($login_fabrica,array(46,114,122,123,124,125,127,128,129,134,136,147,151,156))){ ?><td><? fecho("rg.do.produto",$con,$cook_idioma) ?></td><? } ?>

        <?php


        if (in_array($login_fabrica,array(35,157,158))){
            echo '<td>'.(($login_fabrica == 158) ? traduz("os.cliente") : traduz("os.interna")).'</td>';
        }


        $labelTecnico = ($login_fabrica == 165) ? traduz("tecnico") : traduz("nome.do.tecnico");

        if(($login_fabrica != 94 && $posto_interno !== true AND !$usaPostoTecnico) or $login_fabrica == 6)  { // HD 415550 ?>
            <td>
                <? if($login_fabrica==30) fecho("os.revendedor",$con,$cook_idioma) ; // HD 65178
                   if($login_fabrica==6) echo traduz("os.posto"); // HD 81252
                   if ($login_fabrica == 165) { echo $labelTecnico;}
                ?>
            </td>
        <?
        } else if($posto_interno === true AND !$usaPostoTecnico) {

            $nome_tecnico = true;

            if (in_array($login_fabrica,array(156)) and (empty($login_unico_master) or $login_unico_master <> 't')) {
                $nome_tecnico = false;
            }

            if (true === $nome_tecnico) {
                echo "<td nowrap>
                        $labelTecnico
                        </td>";
            }

        }
        ?>
        <?php if ($usaPostoTecnico){ ?>
            <td>Técnico</td>
        <?php } ?>
    </tr>
    <tr align='left'>
        <td class="espaco"><input type="text" name="consumidor_cpf" size="17" value="<?echo $consumidor_cpf?>" class="frm" id='consumidor_cpf'></td>
        <? if($login_fabrica==19 OR $login_fabrica==10 OR ($login_fabrica==20 AND $login_pais=='AR')){ ?>
        <td></td>
        <? } ?>
        <?php if(!in_array($login_fabrica,array(46,114,122,123,124,125,127,128,129,134,136,147,151,156))){ ?>
        <td>
            <input class="frm" type="text" name="rg_produto" size="15" maxlength="20" value="<?= $_POST['rg_produto'] ?>" >
        </td>
        <? }
        if (in_array($login_fabrica,array(35,157,158))) { ?>
            <td><input class="frm" type="text" name="os_posto" size="12" maxlength="20" value="<?= $_POST['os_posto'] ?>" ></td>
        <? }

        if (($login_fabrica != 94 && $posto_interno !== true AND !$usaPostoTecnico) or $login_fabrica == 6 ) { // HD 415550 ?>
            <td>
                <? if($login_fabrica == 30) { ?>
                    <input class="frm" type="text" name="os_posto" size="15" maxlength="20" value="<?= $_POST['os_posto'] ?>" >
                <? } else if ($login_fabrica == 6) { // HD 81252 ?>
                    <input class="frm" type="text" name="os_posto" size="12" maxlength="20" value="<?= $_POST['os_posto'] ?>" >
                <? } ?>
                <?php
                    if (in_array($login_fabrica,array(165))) {
                      $qry_tec = pg_query($con, "SELECT tecnico, nome
                                                FROM tbl_tecnico
                                                WHERE fabrica = {$login_fabrica}
                                                AND posto = {$login_posto}
                                                AND ativo IS TRUE
                                                ORDER BY nome;");

                      echo '<select name="tecnico" class="frm">';
                      echo '<option value=""></option>';
                      while ($fetch = pg_fetch_assoc($qry_tec)) {
                          echo '<option value="' . $fetch["tecnico"] . '"';
                          if ($_POST["tecnico"] == $fetch["tecnico"]) {
                              echo ' selected="selected"';
                          }
                          echo '>' , $fetch["nome"] , '</option>';
                      }
                      echo '</select>';
                  }
                ?>
            </td>
        <? } else if ($posto_interno === true and true === $nome_tecnico AND !$usaPostoTecnico) {
                 if (in_array($login_fabrica,array(156,162,165))) {

                    $condPosto = ($login_fabrica == 165) ? " AND posto = {$login_posto} AND ativo IS TRUE" : "";

                      $qry_tec = pg_query($con, "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = $login_fabrica $condPosto order by nome");

                      echo '<td>';
                      echo '<select name="tecnico" class="frm">';
                      echo '<option value=""></option>';
                      while ($fetch = pg_fetch_assoc($qry_tec)) {
                          echo '<option value="' . $fetch["tecnico"] . '"';
                          if ($_POST["tecnico"] == $fetch["tecnico"]) {
                              echo ' selected="selected"';
                          }
                          echo '>' , $fetch["nome"] , '</option>';
                      }
                      echo '</select>';
                      echo '</td>';
                  } else {
                      echo '<td><input type="text" name="nome_tecnico" maxlength="20" value="'.$_POST['nome_tecnico'].'" /></td>';
                  }
        } ?>

        <?php if ($usaPostoTecnico){ ?>
            <td>
                <select name="tecnico" class="frm">
                <option value=""></option>
                <?php 
                    $sql = "SELECT 
                                tecnico, 
                                nome,
                                codigo_externo,
                                ativo 
                            FROM tbl_tecnico 
                            WHERE posto = $login_posto 
                            AND ativo IS TRUE
                            AND fabrica IS NULL";
                    $res = pg_query($con, $sql);
                    if (pg_num_rows($res) > 0) {
                        while ($result = pg_fetch_object($res)) {
                            if (!empty($login_unico)){
                                $selected = ($result->codigo_externo == $login_unico AND $result->ativo == 't') ? "selected" : "";
                            }else{
                                $selected = ($result->tecnico == $_POST["tecnico"]) ? "selected" : "";
                            }
                        ?>
                            <option value='<?=$result->tecnico?>' <?=$selected?> >
                                <?=$result->nome?>
                            </option>
                        <?php 
                        }
                    }
                ?>
                </select>
            </td>
        <?php } ?>
    </tr>

    <!-- HD 211825: Filtrar por tipo de OS: Consumidor/Revenda -->
    <?php
    switch ($consumidor_revenda_pesquisa) {
        case "C":
            $selected_c = "SELECTED";
        break;

        case "R":
            $selected_r = "SELECTED";
        break;
    }
    ?>

    <tr align='left'>
        <td class="espaco">
            <?php
                if(in_array($login_fabrica, array(87,148))){
                    echo traduz("tipo.de.atendimento");
                }else{
                    echo traduz("tipo.de.os");
                }
            ?>
        </td>

        <?
        if(in_array($login_fabrica, array(94,115,116,117,120,201,145,153,158,163,171,174,175,176,177)) || (in_array($login_fabrica, array(167,173,203)) && $posto_interno)) { ?>
                <td><?php echo traduz("tipo.de.atendimento");?></td>
        <? } ?>

        <?if ($login_fabrica == 43) {?>
        <td><?php echo traduz("ordem.de.montagem");?></td>
        <?php
        } elseif ($login_fabrica == 156 and true === $posto_interno) {
            echo '<td>Status Elgin</td>';
        #HD 234532
        }elseif($login_fabrica != 96){?>
            <td><?php echo traduz("status.da.os");?></td>
<?php
        }
        if (in_array($login_fabrica,array(162))){
?>
            <td><?php echo traduz("classificacao.do.atendimento");?></td>
<?php
        }
?>

            <?php if($login_fabrica == 164 && $posto_interno == true){ ?>
            <td align="left" >
                <?php echo traduz("destinacao");?>
            </td>
            <?php } ?>

        <td colspan='2'></td>
    </tr>

    <tr align='left'>
        <td class="espaco">
        <?php if(in_array($login_fabrica, array(87,148))){
				if($login_fabrica == 148 and in_array($login_posto, [6359,390306])) { 
					$cond_outros = " or tipo_atendimento = 76977 " ;
				}
                $sql_tipo_atendimento = "SELECT DISTINCT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND (ativo IS TRUE $cond_outros ) ORDER BY descricao";
                $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento);
            ?>
            <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
            <?php
                if(pg_num_rows($res_tipo_atendimento)>0){
                    echo '<option value="" selected></option>';
                    for($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++){
                        $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);

                        echo "<option value='{$descricao}' ".verificaSelect($descricao, $descricao_tipo_atendimento).">{$descricao}</option>";
                    }
                }
            ?>
            </select>
        <?php }else{?>
            <select id="consumidor_revenda_pesquisa" name="consumidor_revenda_pesquisa" class='frm' style='width:95px'>
                <option value="">Todas</option>
                <option value="C" <?php echo $selected_c; ?>>Consumidor</option>
                <option value="R" <?php echo $selected_r; ?>>Revenda</option>
            </select>
        <?php }?>
        </td>

        <?php if(in_array($login_fabrica, array(94,115,116,117,120,201,145,153,158,163,171,174,175,176,177)) || (in_array($login_fabrica, array(167,173,203)) && $posto_interno)){
                $sql_tipo_atendimento = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
                $res_tipo_atendimento = pg_query($con,$sql_tipo_atendimento);
        ?>
                <td>
                    <select id="tipo_atendimento" name="tipo_atendimento" class='frm' style='width:160px'>
                    <?php
                        if(pg_num_rows($res_tipo_atendimento)>0){
                            echo '<option value="" selected></option>';
                            for($i=0;pg_num_rows($res_tipo_atendimento)>$i;$i++){
                                $descricao = pg_fetch_result($res_tipo_atendimento,$i,descricao);
                                $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,$i,tipo_atendimento);

                                echo "<option value='{$tipo_atendimento}' ".verificaSelect($tipo_atendimento, $descricao_tipo_atendimento).">{$descricao}</option>";
                            }
                        }
                    ?>
                    </select>
                </td>
        <? } ?>

        <?if ($login_fabrica == 43) {?>
        <td><input type='text' name='ordem_montagem' value='<?=$ordem_montagem;?>' class='frm' id='ordem_montagem'></td>
        <?php } elseif ($login_fabrica == 156 and true === $posto_interno) { ?>
        <td>
            <select id="os_elgin_status" name="os_elgin_status" class="frm">
                <option></option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Em analise') ? "SELECTED" : "" ; ?> value='Em analise' >Em analise</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Pendência de documento') ? "SELECTED" : "" ; ?> value='Pendência de documento' >Pendência de documento</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Aguardando NF') ? "SELECTED" : "" ; ?> value='Aguardando NF' >Aguardando NF</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Equip. env. p/ dep.') ? "SELECTED" : "" ; ?> value='Equip. env. p/ dep.' >Equip. env. p/ dep.</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Emitir Orçamento') ? "SELECTED" : "" ; ?> value='Emitir Orçamento' >Emitir Orçamento</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Manut. em Terceiro') ? "SELECTED" : "" ; ?> value='Manut. em Terceiro' >Manut. em Terceiro</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='NF Emitida') ? "SELECTED" : "" ; ?> value='NF Emitida' >NF Emitida</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='OS Encerrada') ? "SELECTED" : "" ; ?> value='OS Encerrada' >OS Encerrada</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Orçam. (Aprovação)') ? "SELECTED" : "" ; ?> value='Orçam. (Aprovação)' >Orçam. (Aprovação)</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Orçam. Aprovado') ? "SELECTED" : "" ; ?> value='Orçam. Aprovado' >Orçam. Aprovado</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Aguardando Pecas') ? "SELECTED" : "" ; ?> value='Aguardando Pecas' >Aguardando Pecas</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Troca, Ag. Analise ZPM') ? "SELECTED" : "" ; ?> value='Troca, Ag. Analise ZPM' >Troca, Ag. Analise ZPM</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Ag. Anal. MFD ZPM') ? "SELECTED" : "" ; ?> value='Ag. Anal. MFD ZPM' >Ag. Anal. MFD ZPM</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Ingênico, Orç Reprovado') ? "SELECTED" : "" ; ?> value='Ingênico, Orç Reprovado' >Ingênico, Orç Reprovado</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Pend. p/ pç GECARE') ? "SELECTED" : "" ; ?> value='Pend. p/ pç GECARE' >Pend. p/ pç GECARE</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Pend. p/ pç SECONT') ? "SELECTED" : "" ; ?> value='Pend. p/ pç SECONT' >Pend. p/ pç SECONT</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Pend. p/ pç ASSISTÊNCIA') ? "SELECTED" : "" ; ?> value='Pend. p/ pç ASSISTÊNCIA' >Pend. p/ pç ASSISTÊNCIA</option>
                <option <?= ($_REQUEST["os_elgin_status"]=='Em Solicitação') ? "SELECTED" : "" ; ?> value='Em Solicitação' >Em Solicitação</option>
            </select>
        </td>
        <?}elseif($login_fabrica != 96){
            $sql_status = "SELECT status_checkpoint,descricao,cor FROm tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.") {$where_tbl_status_checkpoint}";
            $res_status = pg_query($con,$sql_status);
            $total_status = pg_num_rows($res_status);
        ?>
            <td>
                <select id="status_checkpoint" name="status_checkpoint" class='frm'>
                    <option value=""></option>
                    <?php
                        for($i=0;$i<$total_status;$i++){

                            $id_status        = pg_fetch_result($res_status,$i,'status_checkpoint');
                            $cor_status       = pg_fetch_result($res_status,$i,'cor');
                            $descricao_status = pg_fetch_result($res_status,$i,'descricao');

                            $selected = ($status_checkpoint_pesquisa == $id_status) ? " selected ": " ";

                            if ($login_fabrica == 165 && $posto_interno == true || $login_fabrica == 171) {

                                if ($login_fabrica == 165) {
                                    switch ($descricao_status) {
                                        case "Aguardando Faturamento":
                                            $descricao_status = "Aguardando Expedição";
                                            break;
                                        default:
                                            $descricao_status = $descricao_status;
                                            break;
                                    }
                                } elseif ($login_fabrica == 171) {
                                    switch ($descricao_status) {
                                        case "Aberta Call-Center":
                                            $descricao_status = "Abertas pelo Call-Center";
                                            $id_status = "callcenter";
                                            break;
                                        default:
                                            $descricao_status = $descricao_status;
                                            $id_status        = $id_status;
                                            break;
                                    }
                                }
                            }

                            if ($login_fabrica == 175) {

                                $compara = $descricao_status;
                                
                                if ($compara != "Aberta Call-Center") {

                                    echo "<option value='$id_status' $selected >$descricao_status</option>";
                                }

                            } else { 

                                echo "<option value='$id_status' $selected >$descricao_status</option>";
                            }
                        }
                    ?>
                </select>
            </td>
<?php
        }
        if (in_array($login_fabrica,array(162))){
?>
        <TD class="table_line" style="text-align: left;" colspan="2">
            <select name="hd_classificacao" id='hd_classificacao' style='width:131px; font-size:11px' class='frm'>
                <option value=""></option>
                <?php
                    $sql = "SELECT  hd_classificacao,
                                    descricao
                            FROM    tbl_hd_classificacao
                            WHERE   fabrica = {$login_fabrica}
                            AND     ativo IS TRUE
                        ORDER BY      descricao";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){

                        for ($i=0; $i < pg_num_rows($res); $i++) {
                            $hd_classificacao   = pg_fetch_result($res, $i, 'hd_classificacao');
                            $classificacao      = pg_fetch_result($res, $i, 'descricao');

                            $selected = ($hd_classificacao == filter_input(INPUT_POST,'hd_classificacao')) ? " selected " : "";

                            echo "<option value='{$hd_classificacao}' $selected>{$classificacao}</option>";
                        }

                    }
                ?>
            </select>
        </TD>
        <?php
        }

        if($login_fabrica == 164 && $posto_interno == true){ ?>
        <td align="left">
            <select name="destinacao" class="frm">

                <option></option>

                <?php

                $sql_destinacao = "SELECT segmento_atuacao, descricao FROM tbl_segmento_atuacao WHERE fabrica = {$login_fabrica} AND ativo = true";
                $res_destinacao = pg_query($con, $sql_destinacao);

                if(pg_num_rows($res_destinacao) > 0){

                    for($i = 0; $i < pg_num_rows($res_destinacao); $i++){

                        $segmento_atuacao  = pg_fetch_result($res_destinacao, $i, "segmento_atuacao");
                        $descricao_atuacao = pg_fetch_result($res_destinacao, $i, "descricao");

                        $selected = ($_POST["destinacao"] == $segmento_atuacao) ? "selected" : "";

                        echo "<option value='{$segmento_atuacao}' {$selected} > {$descricao_atuacao} </option>";

                    }

                }

                ?>

            </select>
        </td>
        <?php } ?>

        <td colspan=2></td>
    </tr>
    <?php
    if (in_array($login_fabrica, [167,177,203]) && $posto_interno == true) {
        ?>
        <tr>
            <td class="espaco"><?php echo traduz("status.orcamento");?></td>
        </tr>
        <tr>
            <td class="espaco">
                <select name="status_orcamento" class="frm">
                    <option value=""></option>
                    <?php
                    $status_orcamento = array(
                        "Aguardando Análise" => "Aguardando Análise",
                        "Distribuição" => "Distribuição",
                        "Em analise" => "Em Analise",
                        "Orçam. (Aprovação)" => "Orçam. (Aprovação)",
                        "Aguardando Peças" => "Aguardando Peças",
                        "Em reparo" => "Em reparo",
                        "Reparado" => "Reparado",
                        "Orçamento Reprovado" => "Orçamento Reprovado",
                        "OS Encerrada" => "OS Encerrada"
                    );

                    foreach ($status_orcamento as $desc_orcamento => $value ) {
                        if($_POST["status_orcamento"] == $desc_orcamento){
                            $selectedTec = "SELECTED";
                        }else{
                            $selectedTec = "";
                        }

                        #$selectedTec = ($_RESULT["os"]["status_orcamento"] == $desc_orcamento) ? " SELECTED" : "";
                        #print_r($desc_orcamento);exit;
                    ?>
                        <option value="<?=$desc_orcamento?>" <?=$selectedTec?> > <?=$value;?> </option>
                    <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
    <?php
    }

    if ($login_fabrica == 158) {
    ?>
        <tr>
            <td class="espaco" ><?php echo traduz("patrimonio");?></td>
        </tr>
        <tr>
            <td class="espaco" ><input type="text" name="patrimonio" size="15" value="<?=$patrimonio?>" class="frm" /></td>
        </tr>
    <?php
    }
    ?>
       <?php if($login_fabrica == 160 or $replica_einhell){?>
         <tr align='left'>
            <td class="espaco"><?php echo traduz("lote");?></td>
            <td><?php echo traduz("versao.do.produto");?></td>
        </tr>
        <tr>
            <td class="espaco"><input type="text" name="serie"  size="15" value="<?echo $serie?>" class="frm"></td>
            <td><input type="text" name="versao" size="15" value="<?echo $versao?>" class="frm"></td>
        </tr>
    <?php } ?>
    <tr>
        <td colspan='3' align='center'><br><input type="submit" name="btn_acao" value="<? fecho ("pesquisar",$con,$cook_idioma); ?>" /><br><br>
        <?php if($login_fabrica == 165){ ?>
            &nbsp;&nbsp;<input type="checkbox" name="gerar_excel" value="t"> <?php echo traduz("gerar.excel");?>
            </td>
        <?php }else{?>
            </td>
        <?php } ?>
    </tr>
</table>

<table align="center" class="formulario" width="700" border="0">
    <tr align='left' class="subtitulo">
        <td colspan='3'>&nbsp;</td>
    </tr>

    <tr align='left'>
        <td class="espaco"><? fecho("data.inicial",$con,$cook_idioma); ?></td>
        <td><? fecho("data.final",$con,$cook_idioma); ?></td>
    </tr>
    <tr align='left'>
        <td class="espaco">
            <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo substr($data_inicial,0,10);?>" class="frm" />
        </td>
        <?php
            if (in_array($login_fabrica, array(169,170))){
                $label_style = "style='padding-left:20px;'";
            }else{
                $label_style = "style='padding-left:110px;'";
            }
        ?>
        <td>
            <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? echo substr($data_final,0,10);?>" class="frm">
            <label <?=$label_style?> >
                <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >
                <?php fecho ("apenas.os.em.aberto",$con,$cook_idioma);?>
            </label>
            <?php if (in_array($login_fabrica, array(169,170))){ ?>
	        <label <?=$label_style?>>
                 <input type='checkbox' name='os_cortesia' value='1' <? if (strlen ($os_cortesia) > 0 ) echo " checked " ?> >
                <?php fecho ("apenas.os.em.cortesia",$con,$cook_idioma);?>
            </label>
            <?php } ?>
        </td>
    </tr>

    <tr valign='top' align='left'>
        <td colspan="2">&nbsp;</td>
    </tr>

    <?
    if ($login_e_distribuidor == 't' and $login_fabrica == 3) {
    ?>
    <tr align='left'>
        <td class="espaco">
            <?
            fecho ("cod.posto",$con,$cook_idioma);
            ?>
        </td>
        <td>
            <?
            fecho ("nome.do.posto",$con,$cook_idioma);
            ?>
        </td>
    </tr>
    <tr align='left'>
        <td class="espaco">
            <input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
            <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="
            <?
            fecho ("clique.aqui.para.pesquisar.postos.pelo.codigo",$con,$cook_idioma);
            ?>" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')" />
        </td>
        <td>
            <input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
            <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="<?if($sistema_lingua == 'ES') echo "click aquí­ para efetuar la busca";else echo traduz("clique.aqui.para.pesquisar.postos.pelo.codigo");?>" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
        </td>
    </tr>
    <?
    }
    ?>

    <?php if ($login_fabrica == 156 and true === $posto_interno): ?>
    <tr align="left">
        <td class="espaco"><?php echo traduz("posto.externo");?></td>
        <td><?php echo traduz("nome.do.posto.externo");?></td>
    </tr>
    <tr align="left">
        <td class="espaco">
            <input type="text" name="codigo_posto_externo" id="codigo_posto_externo" size="8" value="<? echo $codigo_posto_externo ?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_externo, '')">
        </td>
        <td>
            <input type="text" name="posto_nome_externo" id="posto_nome_externo" size="30" value="<?echo $posto_nome_externo ?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', document.frm_consulta.posto_nome_externo)">
        </td>
    </tr>
    <?php endif ?>

    <tr align='left'>
        <td class="espaco">
        <?
            if($login_fabrica==3 or $multimarca == 't'){
                echo traduz("marca");
            }
            ?>
        </td>
        <td>
            <?
            fecho ("nome.do.consumidor",$con,$cook_idioma);
            ?>
        </td>
    </tr>

    <tr align='left'>
        <td class="espaco">
        <?
        if($login_fabrica==3 or $multimarca == 't'){
            echo "<select name='marca' size='1' class='frm' style='width:95px'>";
            echo "<option value=''></option>";
            $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica and tbl_marca.visivel = 't' order by nome";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res)>0){
                for($i=0;pg_num_rows($res)>$i;$i++){
                    $xmarca = pg_fetch_result($res,$i,marca);
                    $xnome = pg_fetch_result($res,$i,nome);
                    ?>
                    <option value="<?echo $xmarca;?>" <? //HD 73808 if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>
                    <?
                }

            }
            echo "</select>";
        }
        ?>
        </td>
	<td>
		<input type="text" name="consumidor_nome" size="30" onkeyup="somenteMaiusculaSemAcento(this)" value="<? echo $consumidor_nome; ?>" class="frm"> <img src='imagens/help.png' title='<?php echo traduz("clique.aqui.para.ajuda.na.busca.deste.campo");?>' onclick='mostrarMensagemBuscaNomes()'>
		<label">
			<input type='checkbox' name='os_troca' value='1' <? if (strlen ($os_troca) > 0 ) echo " checked " ?> ><?php fecho ("apenas.os.troca",$con,$cook_idioma);?>
		</label>
	</td>
    </tr>

    <tr align='left'>
        <td class="espaco">
            <? fecho ("ref.produto",$con,$cook_idioma); ?>
        </td>
        <td>
            <? fecho ("descricao.produto",$con,$cook_idioma); ?>
        </td>
    </tr>
    <tr align='left'>
        <td class="espaco">
            <input type='hidden' name='voltagem' value=''>
            <input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" id='produto_referencia'>
            &nbsp;
            <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: pesquisaProduto (document.frm_consulta.produto_referencia, 'referencia');">
        </td>

        <td>
            <input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" id='produto_descricao'>
            &nbsp;
            <img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: pesquisaProduto (document.frm_consulta.produto_descricao,'descricao');">
        </td>
    </tr>
    <?php if($login_fabrica == 162){?>
    <tr>
        <td class="espaco"><?php echo traduz("imei");?><td>
    </tr>
    <tr>
        <td class="espaco"><input type="text" name="imei" value="<?=$imei?>"></td>
    </tr>
    <?php } ?>

    <?php if($login_fabrica == 164){?>
    <tr>
        <td class="espaco"><?php echo traduz("cep");?><td>
    </tr>
    <tr>
        <td class="espaco"><input type="text" name="cep" id="cep" value="<?=$cep?>"> <br /> <br /> </td>
    </tr>
    <?php } ?>

<?
#Sistema de Informática para a Britãnia de Pre-OS
$sqllinha = "SELECT tbl_posto_linha.linha
        FROM    tbl_posto_linha
        JOIN    tbl_linha USING (linha)
        WHERE   tbl_posto_linha.posto = $login_posto
        AND     tbl_posto_linha.linha = 528
        AND     tbl_linha.fabrica = $login_fabrica";
$reslinha = pg_query($con,$sqllinha);

if (pg_num_rows($reslinha) > 0) {
    $linhainf = trim(pg_fetch_result($reslinha,0,linha)); //linha informatica para britania
}

?>

    <tr align='left' class="subtitulo">
        <td colspan='3'>&nbsp;</td>
    </tr>

    <tr align='left'>
        <td class="espaco">
            <?
            fecho ("os.em.aberto.da.revenda.=.cnpj",$con,$cook_idioma);
            ?>
        </td>
        <td>
            <input class="frm" type="text" name="revenda_cnpj" id='revenda_cnpj' size="12" maxlength="8" value="<? echo $revenda_cnpj ?>" >
            <? if ($sistema_lingua<>'ES'){?>
                 /0000-00
            <? } ?>

        </td>
    </tr>
    <?php if($login_fabrica==3){ ?>
    <tr>
        <td class="espaco">
            <?php echo traduz("protocolo");?>
        </td>
        <td>
            <input class="frm" type="text" name="protocolo_atendimento" id='protocolo_atendimento' size="8" value="<? echo $res_protocolo;?>" >
        </td>
    </tr>
    <?php } ?>
    <? if($login_fabrica==7){ ?>
        <tr align='left' class="subtitulo">
            <td colspan='2'>&nbsp;</td>
        </tr>
        <tr align='left'>
            <td colspan='2' class="espaco">
                <? fecho ("natureza",$con,$cook_idioma); ?>
                <select name="natureza" class="frm">
                    <option value='' selected></option>
                    <?
                    $sqlN = "SELECT *
                        FROM tbl_tipo_atendimento
                        WHERE fabrica = $login_fabrica
                        AND   ativo IS TRUE
                        ORDER BY tipo_atendimento";
                    $resN = pg_query ($con,$sqlN) ;

                    for ($z=0; $z<pg_num_rows($resN); $z++){
                        $xxtipo_atendimento = pg_fetch_result($resN,$z,tipo_atendimento);
                        $xxcodigo           = pg_fetch_result($resN,$z,codigo);
                        $xxdescricao        = pg_fetch_result($resN,$z,descricao);

                        echo "<option ";
                        $teste1 = $natureza;
                        $teste2 = $xxtipo_atendimento;
                        if($natureza==$xxtipo_atendimento) echo " selected ";
                        echo " value='" . $xxtipo_atendimento . "'" ;
                        echo " > ";
                        echo $xxcodigo . " - " . $xxdescricao;
                        echo "</option>\n";
                    }
                    ?>
                </select>
                <? #echo $teste1.' - '.$teste2; ?>
            </td>
        </tr>
    <? } ?>

</table>

<table align="center" class="formulario" width="700" border="0">
    <tr>
        <td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="<? fecho ("pesquisar",$con,$cook_idioma); ?>" /><br/>
        <?php if($login_fabrica == 165 or $login_fabrica == 158){ ?>
            <br/>
            &nbsp;&nbsp;<input type="checkbox" name="gerar_excel" value="t"> <?php echo traduz("gerar.excel");?>
            </td>
        <?php }else{?>
            </td>
        <?php } ?>

    </tr>
    <tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
        <td colspan='2'>&nbsp;</td>
    </tr>
</table>

<?php
if(($login_fabrica == 3 and $linhainf == 528) or (in_array($login_fabrica,array(80,91)) and $login_posto == 6359) or in_array($login_fabrica,array(7,11,14,24,30,43,46,50,52,59,66,81,89,94,96,101,114,115,116,117,120,201,122,123,124,125,127,128,129,131,132,134,136,139,172,$fabrica_pre_os)) && !in_array($login_fabrica,array(171))) {
?>

    <?php if($login_fabrica == 164){?>
    <table align="center" class="formulario" width="700" border="0" style="padding-top: 20px; padding-bottom: 20px;">
        <tr align='center' class="subtitulo">
            <td colspan="3"> <?php echo traduz("filtro.de.pre.ordem.de.servico");?> </td>
        </tr>
        <tr>
            <td class="espaco" style="padding-top: 20px;" ><?php echo traduz("nome");?></td>
            <td class="espaco" style="padding-top: 20px;" ><?php echo traduz("cpf");?></td>
            <td class="espaco" style="padding-top: 20px;" ><?php echo traduz("cep");?></td>
        </tr>
        <tr>
            <td class="espaco" ><input type="text" name="nome_pre_os" id="nome_pre_os" value="<?=$nome_pre_os?>"> </td>
            <td class="espaco" style="padding-left: 50px;"><input type="text" name="cpf_pre_os" id="cpf_pre_os" value="<?=$cpf_pre_os?>"> </td>
            <td class="espaco" style="padding-left: 40px;"><input type="text" name="cep_pre_os" id="cep_pre_os" value="<?=$cep_pre_os?>"> </td>
        </tr>
        <tr>
            <td class="espaco" style="padding-top: 20px;" ><?php echo traduz("autorizacao.de.postagem");?></td>
        </tr>
        <tr>
            <td class="espaco" ><input type="text" name="autorizacao_postagem" id="autorizacao_postagem" value="<?=$autorizacao_postagem?>"> </td>
        </tr>
    </table>
    <?php } ?>

    <table align="center" class="formulario" width="700" border="0">
        <tr>
            <td colspan='2' align='center'>
                <input type="submit" name="btn_acao_pre_os" value="<? fecho ("pre-ordem.de.servico",$con,$cook_idioma); ?>">
            </td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
            <td colspan='2'>&nbsp;</td>
        </tr>
    </table>
<?}?>

</form>

<? include "rodape.php" ?>

