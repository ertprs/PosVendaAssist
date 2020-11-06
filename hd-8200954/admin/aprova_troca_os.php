<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";
include 'funcoes.php';


//echo print_r($_POST);
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $tipo_busca = $_GET["busca"];

    if (strlen($q)>2){
        $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
        if ($tipo_busca == "codigo"){
            $sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
        }else{
            $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        }

        $res = pg_exec($con,$sql);
        if (pg_numrows ($res) > 0) {
            for ($i=0; $i<pg_numrows ($res); $i++ ){
                $cnpj         = trim(pg_result($res,$i,cnpj));
                $nome         = trim(pg_result($res,$i,nome));
                $codigo_posto = trim(pg_result($res,$i,codigo_posto));
                echo "$cnpj|$nome|$codigo_posto";
                echo "\n";
            }
        }
    }
    exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao         = trim($_POST["btn_acao"]);
$select_acao      = trim($_POST["select_acao"]);

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){
    $qtde_os     = trim($_POST["qtde_os"]);
    $observacao  = trim($_POST["observacao"]);



    if($select_acao == "94" AND strlen($observacao) == 0){
        $msg_erro .= "Informe o motivo da reprovação OS.";
    }

    if(strlen($observacao) > 0){
        $observacao         = "' Observação: $observacao '";
        $observacao_email   = " Observação: $observacao ";
    }else{
        if($select_acao == '93') {
            $observacao = "'OS aprovada'";
        }
    }

    if (strlen($qtde_os)==0){
        $qtde_os = 0;
    }

    for ($x=0;$x<$qtde_os;$x++){

    //$motivo_ordem     = trim($_POST["motivo_ordem_".$x]);
    $troca_devolucao  = trim($_POST["troca_devolucao_".$x]);

    $campos_adicionais = array();
/*
    if(strlen($troca_devolucao) > 0){
      $campos_adicionais["troca_devolucao"] = utf8_decode($troca_devolucao);
    }*/

    /*if(strlen($motivo_ordem) > 0){
      $campos_adicionais["motivo_ordem"] = utf8_decode($motivo_ordem);
    }*/

        $xxos = trim($_POST["check_".$x]);

        if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

            $res_os = pg_exec($con,"BEGIN TRANSACTION");

            $sql = "SELECT contato_email,tbl_os.sua_os, tbl_os.posto, tbl_os.tipo_atendimento,tbl_posto_fabrica.contato_pais
                    FROM tbl_posto_fabrica
                    JOIN tbl_os            ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE tbl_os.os      = $xxos
                    AND   tbl_os.fabrica = $login_fabrica";
            $res_x = pg_exec($con,$sql);
            $posto_email = pg_result($res_x,0,contato_email);
            $posto_pais  = pg_result($res_x,0,contato_pais);
            $sua_os      = pg_result($res_x,0,sua_os);
            $posto       = pg_result($res_x,0,posto);

            $tipo_atendimento= pg_result($res_x,0,tipo_atendimento);

            if (strlen($login_admin) > 0){
                $sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin";
                $res_x = pg_exec($con,$sql);
                $promotor = pg_result($res_x,0,nome_completo);
            }

            $sqlx = "SELECT motivo                                         ,
                           tbl_causa_defeito.codigo        AS cd_codigo    ,
                           tbl_causa_defeito.descricao     AS cd_descricao ,
                           tbl_servico_realizado.descricao AS s_descricao  ,
                           tbl_os_troca_motivo.observacao
                    FROM tbl_os
                    JOIN tbl_os_troca_motivo USING (os)
                    JOIN tbl_causa_defeito ON tbl_os.causa_defeito = tbl_causa_defeito.causa_defeito
                    JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os.solucao_os
                    WHERE tbl_os.os      = $xxos
                    AND   tbl_os.fabrica = $login_fabrica";
            $res_m = pg_exec($con,$sqlx);
            if(pg_numrows($res_m) > 0) {
                $motivo       = pg_result($res_m,0,motivo);
                $cd_codigo    = pg_result($res_m,0,cd_codigo);
                $cd_descricao = pg_result($res_m,0,cd_descricao);
                $cd_descricao = str_replace("'",'"',$cd_descricao);
                $s_descricao  = pg_result($res_m,0,s_descricao);
                $m_observacao = pg_result($res_m,0,observacao);
            }
            $sql = "SELECT status_os
                    FROM tbl_os_status
                    WHERE status_os IN (92,93,94)
                    AND os = $xxos
                    ORDER BY data DESC
                    LIMIT 1";
            $res_os = pg_exec($con,$sql);

            if (pg_numrows($res_os)>0){
                $status_da_os = trim(pg_result($res_os,0,status_os));
                if ($status_da_os == 92){
                    //Aprovada
                    if($select_acao == "93"){
                        if($tipo_atendimento==13 or $tipo_atendimento==66){
                            /*HD: 87459 - GRAVAR UM EXTRATO POR OS DE TROCA NA BOSCH */
                            $sql = "SELECT extrato
                                    FROM tbl_os_extra
                                    WHERE os = $xxos
                                        and extrato is not null";
                            $res = pg_exec($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            if(pg_numrows($res )==0){
                                //--=== Cria um extrato para o posto ===--\\
                                /*$sql = "INSERT INTO tbl_extrato (posto, fabrica, avulso, total) VALUES ($posto,$login_fabrica, 0, 0)";
                                $res = pg_exec($con,$sql);
                                $msg_erro = pg_errormessage($con);

                                $sql = "SELECT CURRVAL ('seq_extrato')";
                                $res = pg_exec($con,$sql);
                                $msg_erro .= pg_errormessage($con);
                                $extrato  = pg_result ($res,0,0);

                                //--=== Insere as OS's no extrato ==--\\
                                $sql = "UPDATE tbl_os_extra SET extrato = $extrato WHERE os = $xxos";
                                $res = pg_exec($con,$sql);
                                $msg_erro .= pg_errormessage($con);

                                echo pg_last_error();
                                */

                                //--=== Calcula o extrato do posto ====--\\
                                /*$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
                                $res = pg_exec($con,$sql);
                                $msg_erro .= pg_errormessage($con);
                                */

                            }else{
                                //envia email, apresenta erro
                                if($sistema_lingua == 'ES') $msg_erro = "No es posible cerrar más de un extracto por día!";
                                else                        $msg_erro = "Não é possível fechar mais de um extrato por dia!";

                                $nome         = "TELECONTROL";
                                $email_from   = "helpdesk@telecontrol.com.br";
                                $assunto      = "OS DE TROCA";
                                $destinatario = "helpdesk@telecontrol.com.br";
                                $boundary = "XYZ-" . date("dmYis") . "-ZYX";

                                $mensagem = "Posto $login_posto - fabrica: $login_fabrica está tentando gravar a OS de troca: $xxos mas existe o problema de existir um extrato para esta OS. Programa: os_troca.php <br>
                                            Este email é enviado com o objetivo de tratar os problemas de duplicidade de extrato (lote) da Bosch.
                                            <br>Estava acontecendo de criar extrato com total em branco, possivelmente pelo posto voltar a página e reprocessar (F5). <br>
                                            Então colocamos para enviar email. Quem pegar este email deve pesquisar se existe algum problema nos extratos deste posto. <br>Só pode ter um extrato, com todas as OSs e o total tem que bater.";

                                $body_top = "--Message-Boundary\n";
                                $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                                $body_top .= "Content-transfer-encoding: 7BIT\n";
                                $body_top .= "Content-description: Mail message body\n\n";
                                @mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
                            }
                        }
                        $sql = "INSERT INTO tbl_os_status
                                (os,status_os,data,observacao,admin)
                                VALUES ($xxos,93,current_timestamp,$observacao,$login_admin)";
                        $res = pg_exec($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                        $email_origem  = "pt.garantia@br.bosch.com";
                        $email_destino = $posto_email;
                        $assunto       = "Troca Aprovada";

                        if($login_fabrica == 20){
                            $corpo ="<br>A OS n°$sua_os foi aprovada. \n\n";
                            $corpo.="<br>Promotor que concedeu a aprovação: $promotor\n\n";
                            $corpo.="<br><br>Motivo da troca: $motivo ";
                            if(strlen($m_observacao) > 0 ) {
                                $corpo.="<br>Observação: $m_observacao";
                            }
                            $corpo.="<br>Identificação do defeito e Defeito: ";
                            $corpo.="<br><br>$s_descricao &nbsp;&nbsp; $cd_codigo - $cd_descricao ";
                            $corpo.='<br><br>PROCEDIMENTO PARA TROCA DA FERRAMENTA:<br>
                                1. Imprima uma cópia da OS para anexar junto à ferramenta.<br>
                                2. Aguardar o período de 2 meses para realizar o descarte da ferramenta<br>
                                <br>
                                Não é mais gerado um extrato automático somente para trocas.<br>
                                <br>
                                Em caso de dúvidas, favor consultar a "CI_03_2016 - Extratos Automáticos de Troca de Máquinas".<br>';

                            $corpo.="<br>_______________________________________________\n";
                            $corpo_comunicado = $corpo;
                            $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

                            if ($_serverEnvironment == 'development')
                                $email_destino = 'joao.junior@telecontrol.com.br';

                            $body_top = "--Message-Boundary\n";
                            $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                            $body_top .= "Content-transfer-encoding: 7BIT\n";
                            $body_top .= "Content-description: Mail message body\n\n";
                            $descricao_comunicado = "Troca de OS Aprovada";
                            if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
                            }
                        }
                    }
                    //Recusada
                    if($select_acao == "94"){
                        $sql = "INSERT INTO tbl_os_status(
                                    os,
                                    status_os,
                                    data,
                                    observacao,
                                    admin
                                )VALUES (
                                    $xxos,
                                    94,
                                    current_timestamp,
                                    $observacao,
                                    $login_admin
                        )";
                        $res = pg_exec($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                        $sql = "UPDATE tbl_os SET
                                    excluida  = 't'
                                WHERE os = $xxos
                                AND fabrica = $login_fabrica ";
                        $res = pg_exec($con,$sql);

                        $sql = "UPDATE tbl_os_extra SET
                                    status_os = 94
                                WHERE os = $xxos";
                        $res = pg_exec($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                        $email_origem  = "pt.garantia@br.bosch.com";
                        $email_destino = "$posto_email ";
                        $assunto       = "Troca Reprovada";
//                      $observacao = str_replace("'", "", $observacao);
                        $corpo ="<br>A OS n°$sua_os foi reprovada.\n\n";
                        $corpo.="<br>Promotor que reprovou: $promotor\n\n";
                        $corpo.="<br>$observacao_email\n\n";
                        $corpo_comunicado = $corpo;
                        $corpo.="<br>_______________________________________________\n";
                        $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

                        $body_top = "--Message-Boundary\n";
                        $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                        $body_top .= "Content-transfer-encoding: 7BIT\n";
                        $body_top .= "Content-description: Mail message body\n\n";
                        $descricao_comunicado = "Troca de OS Reprovada";
                        if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){

                        }
                    }
                }
                if ($login_fabrica == 20){
                    $corpo_comunicado = str_replace("'","\'",$corpo_comunicado);
                    $sql = "INSERT INTO tbl_comunicado (
                        fabrica,
                        posto,
                        mensagem,
                        obrigatorio_site,
                        ativo,
                        pais,
                        tipo,
                        descricao
                    )VALUES(
                        $login_fabrica,
                        $posto,
                        E'$corpo_comunicado',
                        true,
                        true,
                        '$posto_pais',
                        'Comunicado',
                        '$descricao_comunicado'
                    )";
                    $resComunicado = pg_query($con,$sql);
                    if (pg_last_error($con)) {
                        $msg_erro = pg_last_error($con);
                    }
                }

            }

        $sql = "SELECT tbl_os_campo_extra.campos_adicionais
                FROM tbl_os_campo_extra
                WHERE os = $xxos
                AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if(pg_num_rows($res) > 0){
          $res_adicionais = pg_result($res,0,campos_adicionais);

          $campos_adicionais = json_decode($res_adicionais, true);
        }

        $campos_adicionais["troca_devolucao"] = utf8_encode($troca_devolucao);

        if(count($campos_adicionais) > 0){

          $campos_adicionais = json_encode($campos_adicionais);
			$campos_adicionais = str_replace("'","\'",$campos_adicionais);
          if(strlen($res_adicionais) > 0){

            $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = E'$campos_adicionais'
                    WHERE os = $xxos
                    AND fabrica = $login_fabrica";
          }else{
            $sql = "INSERT INTO tbl_os_campo_extra (fabrica, os, campos_adicionais) values ($login_fabrica, $xxos, '$campos_adicionais')";
          }
            $res = pg_query($con,$sql);
			$msg_erro  = pg_last_error();
        }


            if (strlen($msg_erro)==0){
                $res = pg_exec($con,"COMMIT TRANSACTION");
            }else{
                $res = pg_exec($con,"ROLLBACK TRANSACTION");
            }
        }
    }
}
if($btn_acao == 'Pesquisar'){

    $data_inicial       = trim($_POST['data_inicial']);
    $data_final         = trim($_POST['data_final']);
    $aprova             = trim($_POST['aprova']);
    $os                 = trim($_POST['os']);
    $produto_referencia = trim($_POST['produto_referencia']);
  $tipo_atendimento_bosch = trim($_POST['tipo_atendimento_bosch']);
    # HD 77122 - Não estava pesquisando por posto
    $posto_codigo = trim($_POST['posto_codigo']);
    $motivo_ordem_bosch = trim($_POST['motivo_ordem_bosch']);

    if (strlen($os)>0){
        $Xos = " AND os = $os ";
    }

    if(strlen($aprova) == 0){
        $aprova = "aprovacao";
        $aprovacao = "92";
    }elseif($aprova=="aprovacao"){
        $aprovacao = "92";
    }elseif($aprova=="aprovadas"){
        $aprovacao = "93";
    }elseif($aprova=="reprovadas"){
        $aprovacao = "94";
    }

    if (strlen($data_inicial) > 0) {
        $xdata_inicial = formata_data ($data_inicial);
        $xdata_inicial = $xdata_inicial." 00:00:00";
    }

    if (strlen($data_final) > 0) {
        $xdata_final = formata_data ($data_final);
        $xdata_final = $xdata_final." 23:59:59";
    }

    $whereMotivo = "";
    $joinMotivo  = "";
    if (strlen($motivo_ordem_bosch) > 0) {
        $whereMotivo = " AND lower(JSON_FIELD('motivo_ordem',tbl_os_campo_extra.campos_adicionais)) = '".strtolower($motivo_ordem_bosch)."'";
        $joinMotivo  = " LEFT JOIN tbl_os_campo_extra ON(tbl_os.os = tbl_os_campo_extra.os) AND tbl_os_campo_extra.fabrica = {$login_fabrica}";
    }
}

if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {

    if(strlen($promotor_treinamento)>0 AND $aprova == 'aprovacao') $sql_add = " AND tbl_promotor_treinamento.admin = $promotor_treinamento ";
    else                                                            $sql_add = " ";
// echo "asdf".$aprova;
// exit;
    if(strlen($promotor_treinamento)>0 AND $aprova != 'aprovacao') $quem_aprovou = " AND  interv.aprova_admin = $promotor_treinamento";
    else                                                            $quem_aprovou = "";
    # HD 77122
    if (strlen($posto_codigo) > 0){
        $sqlPosto = "SELECT posto FROM tbl_posto_fabrica
                    WHERE codigo_posto = '$posto_codigo' AND fabrica = $login_fabrica";
        $resPosto = pg_exec($con,$sqlPosto);
        if (pg_numrows($resPosto) == 1){
            $sqlCondPosto = "AND tbl_os.posto = ".pg_result($resPosto, 0, posto);
        }
    }

	if(empty($data_inicial)) {
		$cond_data = " and tbl_os_status.data > current_timestamp - interval '1 year' ";
	}
	if($login_fabrica == 20 and $aprova <> 'aprovacao' and !empty($data_inicial)) {
		$cond_data = " AND data BETWEEN '$xdata_inicial' AND '$xdata_final' ";

	}
    $sql =  "SELECT interv.os,data_status, aprova_admin as admin_aprovou, ultimo_status
            INTO TEMP tmp_interv_$login_admin
            FROM (
            SELECT
            ultima.os,
            (SELECT status_os FROM tbl_os_status WHERE status_os IN (92,93,94) AND tbl_os_status.os = ultima.os AND fabrica_status = $login_fabrica ORDER BY data DESC LIMIT 1) AS ultimo_status,
            (SELECT admin FROM tbl_os_status WHERE status_os IN (92,93,94) AND tbl_os_status.os = ultima.os AND fabrica_status = $login_fabrica ORDER BY data DESC LIMIT 1) AS aprova_admin,
            (SELECT data FROM tbl_os_status WHERE status_os IN (92,93,94) AND tbl_os_status.os = ultima.os AND fabrica_status = $login_fabrica ORDER BY data DESC LIMIT 1) AS data_status
            FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (92,93,94)  AND fabrica_status = $login_fabrica $cond_data) ultima
            ) interv
            WHERE interv.ultimo_status IN ($aprovacao)
            $quem_aprovou
            $Xos
            ;

            CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

            /* select os from  tmp_interv_$login_admin; */

            SELECT tbl_os.os                                                   ,
                    tbl_os.sua_os                                               ,
                    tbl_os.consumidor_nome                                      ,
          tbl_os.obs,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                    TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
                    tbl_os.fabrica                                              ,
                    tbl_os.consumidor_nome                                      ,
                    tbl_os.nota_fiscal_saida                                    ,
                    to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
                    tbl_posto.nome AS posto_nome            ,
                    tbl_posto_fabrica.codigo_posto                              ,
                    tbl_posto_fabrica.contato_estado                            ,
                    tbl_produto.referencia AS produto_referencia    ,
                    tbl_produto.descricao AS produto_descricao     ,
                    tbl_produto.voltagem                                        ,
					  tbl_promotor_treinamento.nome AS nome_promotor,
                    ultimo_status AS status_os         ,
                    (SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (92,93,94) AND fabrica_status = $login_fabrica ORDER BY data DESC LIMIT 1) AS status_observacao,
					admin_aprovou,
					  to_char(data_status, 'DD/MM/YYYY') AS data_status,
					tbl_status_os.descricao AS status_descricao
                FROM tmp_interv_$login_admin X
				JOIN tbl_os ON tbl_os.os = X.os
				JOIN tbl_status_os on ultimo_status = status_os
                JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                AND tbl_produto.fabrica_i = $login_fabrica
                JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                AND tbl_posto_fabrica.fabrica = $login_fabrica
                $joinMotivo
                JOIN tbl_promotor_treinamento ON tbl_promotor_treinamento.promotor_treinamento = tbl_os.promotor_treinamento
                $sql_add
                WHERE tbl_os.fabrica = $login_fabrica
                AND tbl_posto.pais = 'BR'
                AND tbl_os.tipo_atendimento in (13,66)
                $sqlCondPosto
                $whereMotivo
                ";
        //echo $sql;
    if($produto_referencia<>''){
        $sql .= " AND tbl_produto.referencia='$produto_referencia'";
    }

  if($login_fabrica == 20 AND strlen($tipo_atendimento_bosch) > 0){
    $sql .= " AND tipo_atendimento = '$tipo_atendimento_bosch'";
  }
	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
				if($login_fabrica == 20 and $aprova <> 'aprovacao') {
						$data_consulta = 'data_status';
				}else{
						$data_consulta = 'tbl_os.data_digitacao';
				}
		$sql .= " AND $data_consulta BETWEEN '$xdata_inicial' AND '$xdata_final'
				ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os ";
	}
	$resSubmit = pg_exec($con,$sql);
	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit)>0) {
			$data = date("d-m-Y-H-i");
			$fileName = (in_array($login_fabrica, array(20))) ? "relatorio_aprova_troca_os-{$data}.txt" : "relatorio_aprova_troca_os-{$data}.csv";
			$file = fopen("/tmp/assist/{$fileName}", "w");

			if($aprova == 'aprovacao'){
                $label_aprovacao = "EM APROVACAO";
                $label_data      = "DATA STATUS";
			}else if($aprova == 'aprovadas'){
                $label_aprovacao = "APROVADA POR";
                $label_data      = "DATA DE APROVACAO";
			}else{
                $label_aprovacao = "REPROVADA POR";
                $label_data      = "DATA DE REPROVACAO";
			}

			//if($aprova == 'aprovacao'){

			if(in_array($login_fabrica, array(20))){
                $head = "OS \t DATA DIGITACAO \t DATA ABERTURA \t CODIGO POSTO \t NOME POSTO \t PRODUTO \t DESCRICAO \t STATUS \t PROMOTOR \t ".$label_aprovacao." \t ".$label_data." \t MOTIVO TROCA \t OBS.CLIENTE \t OBS.PROMOTOR \t MOTIVO ORDEM \t TROCA/DEVOLUCAO \t NUMERO DO PEDIDO \t CODIGO DAS PECAS \t DESCRICAO DAS PECAS \t PROTOCOLO \t INFORME CI OU SOLICITANTE \t LINHA DE MEDICAO \t PEDIDO NAO FORNECIDO\r\n";
            }else{
                $head = "OS;DATA DIGITACAO;DATA ABERTURA;CODIGO POSTO;NOME POSTO;PRODUTO;DESCRICAO;STATUS;PROMOTOR;".$label_aprovacao.";".$label_data.";MOTIVO TROCA;OBS.CLIENTE;OBS.PROMOTOR;MOTIVO ORDEM;TROCA/DEVOLUCAO;NUMERO DO PEDIDO;CODIGO DAS PECAS;DESCRICAO DAS PECAS;PROTOCOLO;INFORME CI OU SOLICITANTE;LINHA DE MEDICAO;PEDIDO NAO FORNECIDO\r\n";
            }
            
			/*}else{
			  $head = "OS\tDATA DIGITACAO\tDATA ABERTURA\tCODIGO POSTO\tNOME POSTO\tPRODUTO\tDESCRICAO\tSTATUS\tPROMOTOR\t".$label_aprovacao."\t".$label_data."\tMOTIVO TROCA\tOBS.CLIENTE\tOBS.PROMOTOR\tTROCA/DEVOLUCAO\r\n";
			}*/

			fwrite($file, utf8_encode($head));
			$body = '';
			for ($x=0; $x<pg_num_rows($resSubmit);$x++){

				$os					= pg_result($resSubmit, $x, os);
				$sua_os				= pg_result($resSubmit, $x, sua_os);
				$codigo_posto		= pg_result($resSubmit, $x, codigo_posto);
				$posto_nome			= pg_result($resSubmit, $x, posto_nome);
				$consumidor_nome	= pg_result($resSubmit, $x, consumidor_nome);
				$produto_referencia	= pg_result($resSubmit, $x, produto_referencia);
				$produto_descricao	= pg_result($resSubmit, $x, produto_descricao);
				$produto_voltagem	= pg_result($resSubmit, $x, voltagem);
				$data_digitacao		= pg_result($resSubmit, $x, data_digitacao);
				$data_abertura		= pg_result($resSubmit, $x, data_abertura);
				$status_os			= pg_result($resSubmit, $x, status_os);
				$status_observacao	= pg_result($resSubmit, $x, status_observacao);
				$status_descricao	= pg_result($resSubmit, $x, status_descricao);
	      		$obs_cliente        = pg_result($resSubmit, $x, obs);
			    $nome_promotor = pg_result($resSubmit, $x, nome_promotor);
			    $admin_aprovou = pg_result($resSubmit, $x, admin_aprovou);
			    $data_status   = pg_result($resSubmit, $x, data_status);

				if(!empty($admin_aprovou)) {
					$sql_admin = "SELECT nome_completo
						FROM tbl_admin
						WHERE admin = $admin_aprovou";
					$res_admin = pg_exec($con,$sql_admin);

					if(pg_num_rows($res_admin) > 0){
						$nome_admin = pg_result($res_admin, 0, nome_completo);
					}else{
						$nome_admin = "Em aprovação";
					}
				}else{
					$nome_admin = "Em aprovação";
				}
        		// $sql_motivo = "SELECT motivo, replace(replace(observacao, '\\r',''),'\\n','') as observacao
     			//    		    	FROM tbl_os_troca_motivo
         		//        			WHERE os = $os";
        		// $res_motivo = pg_query($con,$sql_motivo);
	        	$sql_motivo = "SELECT motivo,observacao
	            		    	FROM tbl_os_troca_motivo
	                			WHERE os = $os";
	        	$res_motivo = pg_query($con,$sql_motivo);

	        	if(pg_num_rows($res_motivo) > 0){
	          		$troca_motivo    = pg_result($res_motivo, 0, motivo);
	          		$troca_descricao = pg_result($res_motivo, 0, observacao);

	          		$troca_descricao = preg_replace('/\v/', '', $troca_descricao);
	        	}

	      		$obs_cliente        = preg_replace('/\v/','',$obs_cliente);
	        	$sql_adicionais = "SELECT tbl_os_campo_extra.campos_adicionais
	            				    FROM tbl_os_campo_extra
	                				WHERE os = $os
	                				AND fabrica = $login_fabrica";
	        	$resAdicionais = pg_query($con,$sql_adicionais);
	        	$msg_erro .= pg_errormessage($con);

                $descricao_peca         = "";
                $codigo_peca            = "";
                $numero_pedido          = "";
                $protocolo              = "";
                $ci_solicitante         = "";
                $linha_medicao          = "";
                $pedido_nao_fornecido   = "";

                if(pg_num_rows($resAdicionais) > 0){
                    $res_adicionais = pg_result($resAdicionais,0,campos_adicionais);
                    $res_adicionais = json_decode($res_adicionais, true);

                    $res_troca_devolucao  = $res_adicionais['troca_devolucao'];
                    $res_motivo_ordem     = $res_adicionais['motivo_ordem'];

                    #$descricao_peca .= $res_adicionais['descricao_peca_1'] . "  ";
                    #$descricao_peca .= $res_adicionais['descricao_peca_2'] . "  ";
                    #$descricao_peca .= $res_adicionais['descricao_peca_3'] . "  ";

                    if(strlen(trim($res_adicionais['descricao_peca_1'])) > 0){
                    	$descricao_peca  .= $res_adicionais['descricao_peca_1'].'  ';
                    }
                    if(strlen(trim($res_adicionais['descricao_peca_2'])) > 0){
                    	$descricao_peca  .= $res_adicionais['descricao_peca_2'].'  ';
                    }
                    if(strlen(trim($res_adicionais['descricao_peca_3'])) > 0){
                    	$descricao_peca  .= $res_adicionais['descricao_peca_3'];
                    }

                    if(strlen(trim($res_adicionais['codigo_peca_1'])) > 0){
                    	$codigo_peca  .= $res_adicionais['codigo_peca_1'].'  ';
                    }
                    if(strlen(trim($res_adicionais['codigo_peca_2'])) > 0){
                    	$codigo_peca  .= $res_adicionais['codigo_peca_2'].'  ';
                    }
                    if(strlen(trim($res_adicionais['codigo_peca_3'])) > 0){
                    	$codigo_peca  .= $res_adicionais['codigo_peca_3'];
                    }

                    #$codigo_peca .= $res_adicionais['codigo_peca_1'] . "  ";
                    #$codigo_peca .= $res_adicionais['codigo_peca_2'] . "  ";
                    #$codigo_peca .= $res_adicionais['codigo_peca_3'] . "  ";

                    if(strlen(trim($res_adicionais['numero_pedido_1'])) > 0){
                    	$numero_pedido  .= $res_adicionais['numero_pedido_1'].'  ';
                    }
                    if(strlen(trim($res_adicionais['numero_pedido_2'])) > 0){
                    	$numero_pedido  .= $res_adicionais['numero_pedido_2'].'  ';
                    }
                    if(strlen(trim($res_adicionais['numero_pedido_3'])) > 0){
                    	$numero_pedido  .= $res_adicionais['numero_pedido_3'];
                    }

                    #$numero_pedido .= $res_adicionais['numero_pedido_1'] . "  ";
                    #$numero_pedido .= $res_adicionais['numero_pedido_2'] . "  ";
                    #$numero_pedido .= $res_adicionais['numero_pedido_3'] . "  ";

                    $protocolo              = $res_adicionais['protocolo'];
                    $ci_solicitante         = $res_adicionais['ci_solicitante'];
                    $linha_medicao          = $res_adicionais['linha_medicao'];
                    $pedido_nao_fornecido   = $res_adicionais['pedido_nao_fornecido'];


                    if(strlen($res_troca_devolucao) > 0){
                        $res_troca_devolucao = $res_troca_devolucao;
                    }else{
                        $res_troca_devolucao = "";
                    }

                    if(strlen($res_motivo_ordem) > 0){
                        $res_motivo_ordem = $res_motivo_ordem;
                        //HD-3200578
                        switch ($res_motivo_ordem) {
                            case 'Ameaca de Procon (XLR)':
                                    $res_motivo_ordem = "Ameaça de Procon (XLR)";
                                break;
                            case 'Linha de Medicao (XSD)':
                                    $res_motivo_ordem = "Linha de Medição (XSD)";
                                break;
                            case 'Nao existem pecas de reposicao (nao definidas) (XSD)':
                                    $res_motivo_ordem = "Não existem peças de reposição (não definidas) (XSD)";
                                break;
                            case 'Pecas nao disponiveis em estoque (XSS)':
                                    $res_motivo_ordem = "Peças não disponíveis em estoque (XSS)";
                                break;
                            case 'Pedido nao fornecido - Valor Minimo (XSS)':
                                    $res_motivo_ordem = "Pedido não fornecido - Valor Mínimo (XSS)";
                                break;
                            case 'Solicitacao de Fabrica (XQR)':
                                    $res_motivo_ordem = "Solicitação de Fábrica (XQR)";
                                break;

                        }
                    }else{
                        $res_motivo_ordem = "";
                    }
                }

                if(in_array($login_fabrica, array(20))){

                    $body .= $sua_os."\t";
                    $body .= $data_digitacao."\t";
                    $body .= $data_abertura."\t";
                    $body .= $codigo_posto."\t";
                    $posto_nome = str_replace(",","",substr($posto_nome,0,20));
                    $body .= $posto_nome."\t";
                    $body .= str_replace(" ", "", $produto_referencia)."\t";
                    $produto_descricao = str_replace(",", ".", $produto_descricao);
                    $body .= $produto_descricao."\t";
                    $body .= $status_descricao."\t";
                    $body .= $nome_promotor."\t";
                    $body .= $nome_admin."\t";
                    $body .= $data_status."\t";
                    $body .= $troca_descricao."\t";
                    $obs_cliente = str_replace(',', '', $obs_cliente);
                    $body .= $obs_cliente."\t";
                    $status_observacao = str_replace(",", " ", $status_observacao);
                    $body .= $status_observacao;

                    //if($aprova != 'aprovacao'){
                    $body .= "\t".$res_motivo_ordem."\t".$res_troca_devolucao;
                    //}

                    $body .= "\t$numero_pedido";
                    $body .= "\t$codigo_peca";
                    $body .= "\t$descricao_peca";
                    $body .= "\t$protocolo";
                    $body .= "\t$ci_solicitante";
                    $body .= "\t$linha_medicao";
                    $body .= "\t$pedido_nao_fornecido";

                }else{

                    $body .= $sua_os.";";
                    $body .= $data_digitacao.";";
                    $body .= $data_abertura.";";
                    $body .= $codigo_posto.";";
                    $posto_nome = str_replace(",","",substr($posto_nome,0,20));
                    $body .= $posto_nome.";";
                    $body .= str_replace(" ", "", $produto_referencia).";";
                    $produto_descricao = str_replace(",", ".", $produto_descricao);
                    $body .= $produto_descricao.";";
                    $body .= $status_descricao.";";
                    $body .= $nome_promotor.";";
                    $body .= $nome_admin.";";
                    $body .= $data_status.";";
                    $body .= $troca_descricao.";";
                    $obs_cliente = str_replace(',', '', $obs_cliente);
                    $body .= $obs_cliente.";";
                    $status_observacao = str_replace(",", " ", $status_observacao);
                    $body .= $status_observacao;

                    //if($aprova != 'aprovacao'){
                    $body .= ";".$res_motivo_ordem.";".$res_troca_devolucao;
                    //}

                    $body .= ";$numero_pedido";
                    $body .= ";$codigo_peca";
                    $body .= ";$descricao_peca";
                    $body .= ";$protocolo";
                    $body .= ";$ci_solicitante";
                    $body .= ";$linha_medicao";
                    $body .= ";$pedido_nao_fornecido";

                }

                $body .= "\r\n";

            }

		    fwrite($file, $body);
		    fclose($file);

            if (file_exists("/tmp/assist/{$fileName}")) {
                //$path = "/var/www/assist/www/admin/xls/";
                //$path = "/var/www/assist/www/admin/xls/";
                //$path         = "/var/www/assist/www/admin/xls/";
                $path = __DIR__."/xls/";
                $path_tmp     = "/tmp/assist/";

                //system("cd /tmp/; zip -o {$fileName} {$fileName} > /dev/null");
                //echo `cd /tmp/assist/; zip -o {$fileName}.zip {$fileName} > /dev/null; mv {fileName}.zip $path `;
                //echo `cd $path_tmp; rm -rf {$fileName}.zip; zip -o {$fileName}.zip {$fileName} > /dev/null ; mv  {$fileName}.zip $path `;
                echo `cd $path_tmp; mv  {$fileName} $path `;
                //system("cp /tmp/{$fileName}.zip xls/{$fileName}.zip");
                echo "xls/{$fileName}";
            }
        }
        exit;
    }
    $msg_erro = '';
}




$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "callcenter";
$title = "APROVAÇÃO ORDEM DE SERVIÇO DE TROCA";

include 'cabecalho_new.php';
$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
    );

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">

    $(function() {
        $("input[name=aprova]").change(function() {
        var label = $("#label_radio");

        switch($("input[name=aprova]:checked").val()) {
            case "reprovadas":
                $(label).text("Reprovadas pelo Promotor");
                break;

            case "aprovacao":
                $(label).text("Para aprovação do Promotor");
                break;

            case "aprovadas":
                $(label).text("Aprovadas pelo Promotor");
                break;
        }
    });


        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "peca", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () { $.lupa($(this));});

        function formatItem(row) {
            return row[2] + " - " + row[1];
        }

        function formatResult(row) {
            return row[2];
        }

    });


    function expande(ordem) {

        var elemento = document.getElementById('completo_' + ordem);
        var display = elemento.style.display;

        if (display == "none") {
          elemento.style.display = "";
          $('#icone_expande_' + ordem ).removeClass('icon-plus').addClass('icon-minus');
        } else {
          elemento.style.display = "none";
          $('#icone_expande_' + ordem ).removeClass('icon-minus').addClass('icon-plus');
        }

      }

    function retorna_posto(retorno){

        $("#posto_codigo").val(retorno.codigo);
        $("#posto_nome").val(retorno.nome);
    }

    function retorna_produto (retorno) {
        $("#produto").val(retorno.produto);
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    function date_onkeydown() {
      if (window.event.srcElement.readOnly) return;
      var key_code = window.event.keyCode;
      var oElement = window.event.srcElement;
      if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
            var d = new Date();
            oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                             String(d.getDate()).padL(2, "0") + "/" +
                             d.getFullYear();
            window.event.returnValue = 0;
        }
        if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
            if ((key_code > 47 && key_code < 58) ||
              (key_code > 95 && key_code < 106)) {
                if (key_code > 95) key_code -= (95-47);
                oElement.value =
                    oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
            }
            if (key_code == 8) {
                if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                    oElement.value = "dd/mm/aaaa";
                oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                    function ($0, $1, $2) {
                        var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                        if (idx >= 5) {
                            return $1 + "a" + $2;
                        } else if (idx >= 2) {
                            return $1 + "m" + $2;
                        } else {
                            return $1 + "d" + $2;
                        }
                    } );
                window.event.returnValue = 0;
            }
        }
        if (key_code != 9) {
            event.returnValue = false;
        }
    }

    function checkaTodos() {

        $("input[id^='check_']").each(function(){

            var linha = [];
            var id = $(this).attr("id");
            var linha_id = "";

            linha = id.split("_");
            linha_id = linha[1];

            if($(this).prop("checked")){
                $(this).prop("checked", false);
                $("#linha_"+linha_id+" > td").css({"background-color" : "#fff !important"});
            }else{
                $(this).prop("checked", true);
                $("#linha_"+linha_id+" > td").css({"background-color" : "#F5ECCE !important"});
            }
        });

    }

    function setCheck(theCheckbox,mudarcor,cor){

        if($("#"+theCheckbox).prop("checked")){
            $("#"+mudarcor+" > td").css({"background-color" : "#F5ECCE !important"});
        }else{
            $("#"+mudarcor+" > td").css({"background-color" : "#fff !important"});
        }

    }
</script>






<?

if(strlen($msg_erro) > 0){
    ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro?></h4> </div>
<?
}

?>

<!-- Nenum campo é Obrigatório
        Somente os RadionButton
<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
        -->

<form name="frm_consulta" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline">
    <div class="tc_formulario">
        <div class="titulo_tabela">APROVAÇÃO ORDEM DE SERVIÇO DE TROCA - Parâmetros para pesquisa</div>
        <br />
        <div class="container tc_container">
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span3'>
                    <div class='control-group'>
                        <label class='control-label' for='os'>Número da OS</label>
                        <div class='controls controls-row'>
                            <div class='span8'>
                                <input type="text" name="os" id="os" size="12" maxlength="10" class='span12' value= "<?=$os?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'></div>
            </div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='data_inicial'>Data Inicial</label>
                        <div class='controls controls-row'>
                            <div class='span6'>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='data_final'>Data Final</label>
                        <div class='controls controls-row'>
                            <div class='span6'>
                                <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='posto_codigo'>Código Posto</label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" id="posto_codigo" name="posto_codigo" class='span11' maxlength="20" value="<? echo $posto_codigo ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='posto_nome'>Nome Posto</label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" id="posto_nome" name="posto_nome" class='span11' value="<? echo $posto_nome ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" id="produto_referencia" name="produto_referencia" class='span11' maxlength="20" value="<? echo $produto_referencia ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" id="produto_descricao" name="produto_descricao" class='span11' value="<? echo $produto_descricao ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='tipo_atendimento_bosch'>Tipo de Atendimento</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <select name="tipo_atendimento_bosch" id="tipo_atendimento_bosch">
                                    <option></option>
                                    <option value='13' <? if ($tipo_atendimento_bosch == '13') echo " SELECTED "; ?> >Troca em Garantia</option>
                                    <option value='66' <? if ($tipo_atendimento_bosch == '66') echo " SELECTED "; ?> >OS Troca Fora de Garantia</option>
                                </select>
                            </div>
                            <div class='span2'></div>
                        </div>
                    </div>
                </div>

                <?php
                if (in_array($login_fabrica, array(20))) {
                    $array_motivo_ordem = array(
                      "Ameaca de Procon (XLR)" => "Ameaça de Procon (XLR)",
                      "Bloqueio financeiro (XSS)" => "Bloqueio financeiro (XSS)",
                      "Contato SAC (XLR)" => "Contato SAC (XLR)",
                      "Defeito reincidente (XQR)" => "Defeito reincidente (XQR)",
                      "Linha de Medicao (XSD)" => "Linha de Medição (XSD)",
                      "Nao existem pecas de reposicao (nao definidas) (XSD)" => "Não existem peças de reposição (não definidas) (XSD)",
                      "Pecas nao disponiveis em estoque (XSS)" => "Peças não disponíveis em estoque (XSS)",
                      "Pedido nao fornecido - Valor Minimo (XSS)" => "Pedido não fornecido - Valor Mínimo (XSS)",
                      "PROCON (XLR)" => "PROCON (XLR)",
                      "Solicitacao de Fabrica (XQR)" => "Solicitação de Fábrica (XQR)"
                    );
                ?>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='motivo_ordem_bosch'>Motivo Ordem</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <select name="motivo_ordem_bosch" id="motivo_ordem_bosch">
                                    <option></option>
                                    <?php
                                    foreach ($array_motivo_ordem as $descricao => $descricao_acento) {
                                        echo"<option value='$descricao'";
                                        if (strtolower($motivo_ordem_bosch) == strtolower($descricao)){
                                            echo " selected ";
                                        }
                                        echo ">$descricao_acento</option>\n";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <div class='span2'></div>
                <div class='span4'></div>
            </div>
            <br />
        </div>
    </div>
    <br />
    <div class="tc_formulario">
        <div class="container tc_container">
            <div class="titulo_tabela">Mostrar as OS:</div>
            <br />
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span3'>
                    <label class="radio">
                        <input type="radio" name="aprova" id="optionsRadios1" value="aprovacao" <?if($aprova=="aprovacao" or $aprova=="") echo "checked";?>>
                        Em aprovação
                    </label>
                </div>
                <div class='span3'>
                    <label class="radio">
                        <input type="radio" name="aprova" id="optionsRadios1" value="aprovadas" <?if($aprova=="aprovadas") echo "checked";?>>
                        Aprovadas
                    </label>
                </div>
                <div class='span3'>
                        <label class="radio">
                        <input type="radio" name="aprova" id="optionsRadios1" value="reprovadas" <?if($aprova=="reprovadas") echo "checked";?> >
                        Reprovadas
                    </label>
                </div>
                <div class='span1'></div>
            </div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='promotor_treinamento' id='label_radio'><?
                        switch($aprova) {
                            case "reprovadas":
                                echo "Reprovadas pelo Promotor";
                                break;

                            case "aprovacao":
                                echo "Para aprovação do Promotor";
                                break;

                            case "aprovadas":
                                echo "Aprovadas pelo Promotor";
                                break;
                            default:
                                echo "Para aprovação do Promotor";
                                break;
                        }
                        ?></label>
                        <div class='controls controls-row'>
                            <div class='span4'>
                                <select name="promotor_treinamento" id="promotor_treinamento">
                                    <option value=""></option>
                                    <?php

                                        $sql = "SELECT  tbl_admin.admin AS promotor_treinamento,
                                                        tbl_promotor_treinamento.nome,
                                                        tbl_promotor_treinamento.email,
                                                        tbl_promotor_treinamento.ativo,
                                                        tbl_escritorio_regional.descricao
                                            FROM tbl_promotor_treinamento
                                            JOIN tbl_escritorio_regional USING(escritorio_regional)
                                            JOIN tbl_admin               USING(admin)
                                            WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
                                            AND   tbl_promotor_treinamento.ativo ='t'
                                            AND   tbl_promotor_treinamento.pais = 'BR'
                                            ORDER BY tbl_promotor_treinamento.nome";
                                        $res = pg_exec ($con,$sql) ;

                                        for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
                                            $x_promotor_treinamento = pg_result ($res,$i,promotor_treinamento);
                                            $x_nome                 = pg_result ($res,$i,nome);

                                            echo "<option ";
                                            if ($promotor_treinamento == $x_promotor_treinamento ) echo " selected ";
                                            echo " value='$x_promotor_treinamento' >" ;
                                            echo $x_nome;
                                            echo "</option>\n";
                                        }

                                    ?>
                                </select>
                            </div>
                            <div class='span2'></div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <center>
                <button type="button" class='btn' onclick="javascript: if ( document.frm_consulta.btn_acao.value == '' ) { loading('show'); document.frm_consulta.btn_acao.value='Pesquisar'; document.frm_consulta.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt="Clique AQUI para pesquisar" value="Pesquisar">Pesquisar</button>
                <input type="hidden" name="btn_acao" value="">
            </center>
            <br />
        </div>
    </div>
</form></div>
<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        ?>
        <FORM name='frm_pesquisa2' METHOD='POST' ACTION=<?$PHP_SELF?>>
            <input type='hidden' name='data_inicial'   value=<?$data_inicial?>>
            <input type='hidden' name='data_final'     value=<?$data_final?>>
            <input type='hidden' name='aprova'         value=<?$aprova?>>
            <table class='table table-striped table-bordered table-hover table-large'>
                <thead>
                    <tr class='titulo_tabela'>
                        <?php if($login_fabrica == 20){
                            echo "<td></td>";
                        }?>
                        <?php
                        if($aprova == "aprovacao"){
                        ?>
                        <td nowrap>
                            <span onclick='checkaTodos()' style="cursor: pointer;">todas<i class="icon icon-chevron-down icon-white"></i></span>
                            <!-- <img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: pointer;' /> -->
                        </td>
                        <?php
                        }
                        ?>
                        <td>OS</td>
                        <td>Data Digitação</td>
                        <td>Data Abertura</td>
                        <td>Código Posto</td>
                        <td>Nome Posto</td>
                        <td>Produto</td>
                        <td>Descrição</td>
                        <td>Status</td>
                        <?
                        if($aprova == 'aprovacao'){
                            $label_aprovacao = "Em Aprovação";
                            $label_data = "Data Status";
                        }else if($aprova == 'aprovadas'){
                            $label_aprovacao = "Aprovada por";
                            $label_data = "Data de Aprovação";
                        }else{
                            $label_aprovacao = "Reprovada por";
                            $label_data = "Data de Reprovação";
                        }
                        ?>
                        <td>Promotor</td>
                        <td><?php echo $label_aprovacao;?></td>
                        <td><?php echo $label_data;?></td>
                        <td>Motivo Troca</td>
                        <td>Obs. Cliente</td>
                        <td>Obs. Promotor</td>
                        <!--<td>Motivo Ordem</td>-->
                        <td>Troca/Devolução</td>
                    </tr>
                </thead>
                <?
                $cores = '';
                $qtde_intervencao = 0;

                for ($x=0; $x<pg_numrows($resSubmit);$x++){

                    $os                 = pg_result($resSubmit, $x, os);
                    $sua_os             = pg_result($resSubmit, $x, sua_os);
                    $codigo_posto       = pg_result($resSubmit, $x, codigo_posto);
                    $posto_nome         = pg_result($resSubmit, $x, posto_nome);
                    $consumidor_nome    = pg_result($resSubmit, $x, consumidor_nome);
                    $produto_referencia = pg_result($resSubmit, $x, produto_referencia);
                    $produto_descricao  = pg_result($resSubmit, $x, produto_descricao);
                    $produto_voltagem   = pg_result($resSubmit, $x, voltagem);
                    $data_digitacao     = pg_result($resSubmit, $x, data_digitacao);
                    $data_abertura      = pg_result($resSubmit, $x, data_abertura);
                    $status_os          = pg_result($resSubmit, $x, status_os);
                    $status_observacao  = pg_result($resSubmit, $x, status_observacao);
                    $status_descricao   = pg_result($resSubmit, $x, status_descricao);
                    $obs_cliente        = pg_result($resSubmit, $x, obs);
                    $nome_promotor = pg_result($resSubmit, $x, nome_promotor);
                    $admin_aprovou = pg_result($resSubmit, $x, admin_aprovou);
                    $data_status   = pg_result($resSubmit, $x, data_status);


                    if(!empty($admin_aprovou)) {
                        $sql_admin = "SELECT nome_completo
                            FROM tbl_admin
                            WHERE admin = $admin_aprovou";
                        $res_admin = pg_exec($con,$sql_admin);

                        if(pg_num_rows($res_admin) > 0){
                            $nome_admin = pg_result($res_admin, 0, nome_completo);
                        }else{
                            $nome_admin = "Em aprovação";
                        }
                    }else{
                        $nome_admin = "Em aprovação";
                    }

                    $sql_motivo = "SELECT motivo, observacao
                            FROM tbl_os_troca_motivo
                            WHERE os = $os";
                    $res_motivo = pg_query($con,$sql_motivo);

                    if(pg_num_rows($res_motivo) > 0){
                      $troca_motivo    = pg_result($res_motivo, 0, motivo);
                      $troca_descricao = pg_result($res_motivo, 0, observacao);
                    }

                    $sql_adicionais = "SELECT tbl_os_campo_extra.campos_adicionais
                            FROM tbl_os_campo_extra
                            WHERE os = $os
                            AND fabrica = $login_fabrica";
                    $resAdicionais = pg_query($con,$sql_adicionais);

                    $msg_erro .= pg_errormessage($con);
                    $res_adicionais = "";

                    if(pg_num_rows($resAdicionais) > 0){
                        $res_adicionais = pg_result($resAdicionais,0,campos_adicionais);
                        $res_adicionais = json_decode($res_adicionais, true);
                        $res_troca_devolucao  = $res_adicionais['troca_devolucao'];
                        $res_motivo_ordem     = $res_adicionais['motivo_ordem'];

                        if(strlen($res_troca_devolucao) > 0){
                            $res_troca_devolucao = $res_troca_devolucao;
                        }else{
                            $res_troca_devolucao = "";
                        }

                        if(strlen($res_motivo_ordem) > 0){
                            $res_motivo_ordem = $res_motivo_ordem;
                        }else{
                            $res_motivo_ordem = "";
                        }
                    }

                    $cores++;
                    $cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
                    ?>
                <tbody>
                    <?
                    echo "<tr id='linha_$x'>";
                    if($login_fabrica == 20){
                        echo "<td onClick='expande($x)'> <i class='icon-plus' id='icone_expande_$x' > </i> </td>";
                    }
                    ?>

                        <?
                        if($status_os == 92){
                            if($aprova == "aprovacao"){
                                echo "<td class='tac'>";
                                    echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
                                    if (strlen($msg_erro)>0){
                                        if (strlen($_POST["check_".$x])>0){
                                            echo " CHECKED ";
                                        }
                                    }
                                    echo ">";
                                echo "</td>";
                            }
                        }
                        ?>

                        <?
                        echo "<td nowrap ><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a></td>";
                        echo "<td>".$data_digitacao. "</td>";
                        echo "<td>".$data_abertura. "</td>";
                        echo "<td align='left' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto."</td>";
                        echo "<td align='left' nowrap title='".$codigo_posto." - ".$posto_nome."'> ".substr($posto_nome,0,20) ."...</td>";
                        echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
                        echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
                        echo "<td nowrap><acronym title='Observação do Promotor: ".$status_observacao."'>".$status_descricao. "</acronym></td>";
                        echo "<td nowrap><acronym title='Nome Promotor: ".$nome_promotor."'>".$nome_promotor. "</acronym></td>";
                        echo "<td nowrap><acronym title='Aprovado Por: ".$nome_admin."'>".$nome_admin. "</acronym></td>";
                        echo "<td nowrap><acronym title='data: ".$data_status."'>".$data_status. "</acronym></td>";
                        echo "<td nowrap style='width: 200px !important'><acronym title='motivo: ".$troca_motivo."'>".$troca_descricao. "</acronym></td>";
                        echo "<td nowrap style='width: 200px !important'><acronym title='Observação: ".$obs_cliente."'>".$obs_cliente. "</acronym></td>";
                        echo "<td nowrap style='width: 200px !important'><acronym title='Observação Promotor: ".$status_observacao."'>".$status_observacao. "</acronym></td>";
                        /*if($aprova == "aprovacao"){
                            echo "<td >";
                                echo "<select name='motivo_ordem_$x' size='1' class='frm'>";
                                $array_motivo_ordem = array("","Não existem peças de reposição (não definidas) (XSD)",
                                    "Pecas não disponíveis em estoque (XSS)",
                                    "Linha de Medição (XSD)",
                                    "Solicitação de Fábrica (XQR)",
                                    "PROCON (XLR)",
                                    "Pedido nao fornecido - Valor Mínimo (XSS)");
                                for ($i=0; $i <= 6 ; $i++) {
                                    echo"<option value='".$array_motivo_ordem[$i]."'";
                                    if (strlen($array_motivo_ordem)) echo " selected";
                                        echo ">".$array_motivo_ordem[$i]."</option>\n";
                                }
                                echo "</select>";
                            echo "</td>";
                        }else{
                            echo "<td style='width: 200px !important'>";
                            echo $res_motivo_ordem;
                            echo "</td>";
                        }*/

                        if($aprova == "aprovacao"){
                            echo "<td>";
                                echo "<select name='troca_devolucao_$x' size='1' class='frm'>";
                                $array_troca_devolucao = array("", "Troca de Maquina","Devolucao de dinheiro");
                                for ($i=0; $i <=  2; $i++) {
                                    echo"<option value='".$array_troca_devolucao[$i]."'";
                                    if (strlen($array_troca_devolucao)) echo " selected";
                                        echo ">".$array_troca_devolucao[$i]."</option>\n";
                                }
                                echo "</select>";
                            echo "</td>";
                        }else{
                            echo "<td>";
                            echo $res_troca_devolucao;
                            echo "</td>";
                        }
                        ?>
            </tr>
            <?php if($login_fabrica == 20){ ?>
            <tr>
                <Td colspan='16' style="display: none;" id="completo_<?=$x ?>" >
                <?php

                    $chave_anterior = "";
                    $numeros = array("1", "2", "3");
                    foreach($res_adicionais as $chave => $valor){
                        $chave = str_replace("_", " ", $chave);
                        $chave = ucwords($chave);

                        $chave = str_replace($numeros, "", $chave);

                        if($chave == $chave_anterior){
                             echo " | ".$valor;
                        }else{
                            echo "<br><b>". $chave.":</b> ". $valor;
                        }

                        $chave_anterior = $chave;
                    }
                ?>
                </Td>
            </tr>
            <?php } ?>
            </tbody>
            <?
        }
        echo "<input type='hidden' name='qtde_os' value='$x'>";
        if($aprova == "aprovacao"){
            echo "<tfoot>";
            echo "<tr class='titulo_tabela'>";
            echo "<td colspan='100%' align='left'> ";
            if(trim($aprova) == 'aprovacao'){
                echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <B>COM MARCADOS:</B></font> &nbsp;";
                echo "<select name='select_acao' size='1' class='frm' style='margin: 0px;' >";
                echo "<option value=''></option>";
                if ($login_fabrica == 20) {
                    echo "<option value='93'";  if ($_POST["select_acao"] == "93")  echo " selected"; echo ">APROVADO OS</option>";
                } else {
                    echo "<option value='93'";  if ($_POST["select_acao"] == "93")  echo " selected"; echo ">APROVADO PARA PAGAMENTO</option>";
                }
                echo "<option value='94'";  if ($_POST["select_acao"] == "94")  echo " selected"; echo ">GARANTIA RECUSADA</option>";
                echo "</select>";
                echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Comentários:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' style='margin: 0px;' "; if ($_POST["select_acao"] == "19") echo " DISABLED "; echo ">";
                echo "&nbsp;&nbsp;";
                ?>
                <button type="button" class='btn' onclick="javascript: document.frm_pesquisa2.submit()" alt="Clique AQUI para Gravar" value="Pesquisar">Gravar</button>
                <input type="hidden" name="btn_gravar" value="">
                </td>
                </tfoot>
            <?
            }
        }
        echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
        echo "<input type='hidden' name='promotor_treinamento' value='$promotor_treinamento'>";
        echo "<input type='hidden' name='posto_codigo' value='$posto_codigo'>";
        echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
        echo "<input type='hidden' name='data_final' value='$data_final'>";
        echo "</table>";
        echo "</form>";
        ?>
        <br />

            <?php
                $jsonPOST = excelPostToJson($_POST);
            ?>

            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Gerar Download</span>
            </div>
        <?php
    }else{
        echo '
        <div class="container">
        <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
        </div>
        </div>';
    }
}

include "rodape.php";
?>
