<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if($_GET['ajax']=='sim' AND $_GET['acao']=='cadastrar') {
    $enviar_confirmacao = "true";

    if (in_array($login_fabrica, array(169,170))){
        $cadastra_tecnico_admin = trim($_GET["cadastra_tecnico_admin"]);
        
        if ($cadastra_tecnico_admin == "sim"){
            $login_posto = trim($_GET["posto"]);
            $enviar_confirmacao = "false";
        }
    }

    
    $tecnico_nome = trim($_GET['tecnico_nome']);
    $tecnico      = trim($_GET['tecnico']);

    $letra = $tecnico_tipo_sanguineo[0];
    $sinal = $tecnico_tipo_sanguineo[1];
    $sinal = ($sinal == 1) ? "+" : "-";
    $tecnico_tipo_sanguineo = $letra.$sinal;

    if (in_array($login_fabrica, array(169,170))){

        $sql2="SELECT COUNT(tbl_treinamento_posto.treinamento_posto) AS qtd_incritos_posto
                FROM tbl_treinamento_posto
                WHERE tbl_treinamento_posto.posto=$login_posto
                AND tbl_treinamento_posto.treinamento=$treinamento
                AND tbl_treinamento_posto.tecnico IS NOT NULL
                AND tbl_treinamento_posto.ativo IS TRUE ";
        $res2 = pg_exec ($con,$sql2);
        
        $sql3 = "SELECT tbl_treinamento.qtde_participante
                FROM tbl_treinamento
                WHERE tbl_treinamento.fabrica = {$login_fabrica}
                AND tbl_treinamento.treinamento=$treinamento ";
        $res3 = pg_query($con, $sql3);
        
        $qtd_incritos_posto = trim(pg_result($res2,0,qtd_incritos_posto));
        $qtde_participante = pg_fetch_result($res3, 0, qtde_participante);

        $qtd_disponivel_vagas= $qtde_participante-$qtd_incritos_posto;
        
        if($qtd_disponivel_vagas<1){
            $msg_erro .='<center>Não existe vaga disponível</center>';
        }
    }

    if (strlen($treinamento) == 0){
        $msg_erro .= "Favor informar o treinamento escolhido<br>";
    }
    
    if (strlen($tecnico_nome) == 0){
        $msg_erro .= "Favor informar o nome do técnico<br>"      ;
    }else{

        if(!in_array($login_fabrica, array(20,169,170))){
            $funcao_T = "AND funcao = 'T'";
        }

        $sql = "SELECT  tbl_tecnico.nome,
                        tbl_tecnico.tecnico,
                        tbl_tecnico.rg,
                        tbl_tecnico.cpf,
                        tbl_tecnico.telefone,
                        to_char(data_nascimento, 'DD/MM/YYYY') AS data_nascimento
                        FROM tbl_tecnico
                        WHERE posto = $login_posto
                        AND fabrica = $login_fabrica
                        $funcao_T
                        AND tecnico = $tecnico_nome";
        $res = @pg_exec ($con,$sql);

        if (@pg_numrows($res) > 0) {
            for($i=0;$i < @pg_numrows($res);$i++){
                $tecnico_nome = trim(@pg_result($res,$i,'nome'));
                $tecnico      = trim(@pg_result($res,$i,'tecnico'));
                $tecnico_rg   = trim(@pg_result($res,$i,'rg'));
                $tecnico_cpf  = trim(@pg_result($res,$i,'cpf'));
                $tecnico_fone = trim(@pg_result($res,$i,'telefone'));
                $tecnico_data_nascimento = trim(@pg_result($res,$i,'data_nascimento'));
            }
        }
    }

    if ($enviar_confirmacao == "true"){
        if (!filter_var($posto_email,FILTER_VALIDATE_EMAIL))  $msg_erro .= "Email do POSTO inválido: $posto_email<br>";

        if($login_fabrica == $makita){
            if (!filter_var($tecnico_email,FILTER_VALIDATE_EMAIL))  $msg_erro .= "E-mail do Técnico inválido: $tecnico_email<br>";
        }
    }

    $tecnico_cpf = str_replace("-","",$tecnico_cpf);
    $tecnico_cpf = str_replace(".","",$tecnico_cpf);
    $tecnico_cpf = str_replace("/","",$tecnico_cpf);
    $tecnico_cpf = str_replace(" ","",$tecnico_cpf);
    $tecnico_cpf = trim(substr($tecnico_cpf,0,14));

    $tecnico_rg = str_replace("-","",$tecnico_rg);
    $tecnico_rg = str_replace(".","",$tecnico_rg);
    $tecnico_rg = str_replace("/","",$tecnico_rg);
    $tecnico_rg = str_replace(" ","",$tecnico_rg);


    $aux_tecnico_nome = pg_escape_literal($con,$tecnico_nome);
    if(strlen($tecnico_cpf) > 0){
        $aux_tecnico_cpf  = "'".$tecnico_cpf."'" ;
    }

	if ($tecnico_rg == 'null') {
		$aux_tecnico_rg  = "null" ;
	}else{
		$aux_tecnico_rg  = "'".$tecnico_rg."'" ;
	}

    $aux_tecnico_fone = "'".$tecnico_fone."'";

    $aux_promotor_treinamento = $promotor_treinamento;

    $tecnico_celular        = "'".$tecnico_celular."'";
    $tecnico_tipo_sanguineo = "'".$tecnico_tipo_sanguineo."'";
    $tecnico_doenca         = "'".$tecnico_doenca."'";
    $tecnico_medicamento    = "'".$tecnico_medicamento."'";
    $tecnico_necessidade    = "'".$tecnico_necessidade."'";

    if(strlen($promotor)==0) $aux_promotor = "null";
    else                     $aux_promotor = "'".$promotor."'";

    if(strlen($hotel)==0){
        $hotel = "'f'";
    }else{
        $hotel = "'t'";
    }

    if(strlen($tecnico_data_nascimento) > 0){
        $tecnico_data_nascimento = str_replace (" " , "" , $tecnico_data_nascimento);
        $tecnico_data_nascimento = str_replace ("-" , "" , $tecnico_data_nascimento);
        $tecnico_data_nascimento = str_replace ("/" , "" , $tecnico_data_nascimento);
        $tecnico_data_nascimento = str_replace ("." , "" , $tecnico_data_nascimento);

        if (strlen ($tecnico_data_nascimento) == 6) $tecnico_data_nascimento = substr ($tecnico_data_nascimento,0,4) . "20" . substr ($tecnico_data_nascimento,4,2);
        if (strlen ($tecnico_data_nascimento)   > 0) $tecnico_data_nascimento   = substr ($tecnico_data_nascimento,0,2)   . "/" . substr ($tecnico_data_nascimento,2,2)   . "/" . substr ($tecnico_data_nascimento,4,4);
        if (strlen ($tecnico_data_nascimento) < 10) $tecnico_data_nascimento = date ("d/m/Y");

        $x_tecnico_data_nascimento = substr ($tecnico_data_nascimento,6,4) . "-" . substr ($tecnico_data_nascimento,3,2) . "-" . substr ($tecnico_data_nascimento,0,2);
    }

    if(strlen($x_tecnico_data_nascimento)>0){
        $sql ="SELECT date'$x_tecnico_data_nascimento' > (current_date-interval'18 year')";
        $res = pg_exec ($con,$sql);
        if(pg_result($res,0,0)=='t'){
            $sql= "SELECT nome FROM tbl_fabrica WHERE fabrica={$login_fabrica}";
            $res = pg_query($con,$sql);
            $fabrica = pg_result($res,0,0);
            $msg_erro.='NÃO É PERMITIDO A PARTICIPAÇÃO DE MENORES DE 18 ANOS NO TREINAMENTO '.strtoupper($fabrica);
        }
		$x_tecnico_data_nascimento = "'".$x_tecnico_data_nascimento."'";
    }

    if(strlen($aux_tecnico_cpf)>0 OR strlen($tecnico_nome) >0){

        if(strlen($aux_tecnico_cpf) > 0){
            $cond_tecnico = "AND tbl_tecnico.cpf = $aux_tecnico_cpf ";
        }else{
            $cond_tecnico = " AND tbl_tecnico.nome ILIKE '%$tecnico_nome%'";
        }

        if (in_array($login_fabrica, array(169,170))){
            $cond_tecnico_ativo = " AND tbl_treinamento_posto.ativo IS TRUE ";
        }else{
            $cond_tecnico_ativo = "";
        }
        $sql = "SELECT tbl_tecnico.nome
                    FROM tbl_treinamento
                    JOIN tbl_treinamento_posto USING(treinamento)
                    JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
                    WHERE tbl_treinamento.treinamento = $treinamento
                    AND tbl_treinamento.fabrica = $login_fabrica
                    $cond_tecnico
                    $cond_tecnico_ativo";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            $msg_erro .= "Já existe um técnico cadastrado para este treinamento com o CPF informado<br>";
        }
    }

    if (strlen($msg_erro) > 0) {
        $msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
        $msg .= $msg_erro;
    }else {
        $listar = "ok";
    }

    if ($listar == "ok") {

        $res = @pg_exec($con,"BEGIN TRANSACTION");

        //--==== Controle de Quantidade de vagas existentes no treinamento ======================================
        $sql = "SELECT  count(treinamento_posto) AS total_inscritos,
                tbl_treinamento.vagas
            FROM tbl_treinamento
            JOIN tbl_treinamento_posto USING(treinamento)
            WHERE tbl_treinamento.treinamento = $treinamento
            AND   tbl_treinamento_posto.ativo IS TRUE
            AND tbl_treinamento_posto.tecnico IS NOT NULL
            GROUP BY tbl_treinamento.vagas;";
        $res = pg_exec ($con,$sql);

        if (pg_numrows($res) > 0) {
            $total_inscritos = trim(pg_result($res,0,total_inscritos))   ;
            $vagas           = trim(pg_result($res,0,vagas));
            if($total_inscritos >= $vagas) $msg_erro .= "Todas as Vagas estão preenchidas, procure uma nova data";
        }

       
        if(strlen($aux_tecnico_cpf) > 0){
            $cond_cpf = " AND cpf = $aux_tecnico_cpf ";
        }else{
            $cond_cpf = " AND nome ILIKE '%$tecnico_nome%' ";
        }

        $sql = "select tecnico from tbl_tecnico where fabrica  = $login_fabrica and posto = $login_posto $cond_cpf;";
        $resTecnico = pg_exec($con,$sql);

        if(pg_num_rows($resTecnico) > 0){
            $tecnico = pg_result($resTecnico,0,tecnico);
        }else{

            if(strlen($aux_tecnico_cpf) == 0){
                $aux_tecnico_cpf = 'null';
            }

            $sql = "INSERT INTO tbl_tecnico(
                                                fabrica,
                                                posto,
                                                nome,
                                                cpf,
                                                data_nascimento,";
            if (in_array($login_fabrica, [1])) {
                $sql .= "                       celular, ";
            } else {
                $sql .= "                       telefone, ";
            }
                $sql .= "
                                                rg
                ";
            if($login_fabrica == $makita){
                $sql .= "                       ,
                                                celular      ,
                                                email        ,
                                                calcado      ,
                                                passaporte   ,
                                                tipo_sanguineo,
                                                doencas       ,
                                                medicamento  ,
                                                necessidade_especial
                ";
            }
            $sql .= "                       ) VALUES (
                                                $login_fabrica,
                                                $login_posto,
                                                $aux_tecnico_nome,
                                                $aux_tecnico_cpf,
                                                $x_tecnico_data_nascimento,";
            if (in_array($login_fabrica, [1])) {
                $sql .= "                       $tecnico_celular       , ";
            } else {
                $sql .= "                       $aux_tecnico_fone, ";
            }
                $sql .= "
                                                $aux_tecnico_rg
                ";
            if($login_fabrica == $makita){
                $sql .= "                       ,
                                                $tecnico_celular       ,
                                                '$tecnico_email'         ,
                                                $tecnico_calcado       ,
                                                '$tecnico_passaporte'     ,
                                                $tecnico_tipo_sanguineo,
                                                $tecnico_doenca        ,
                                                $tecnico_medicamento    ,
                                                $tecnico_necessidade
                ";
            }
            $sql .= "                        ) RETURNING tecnico";
            $resTecnico = pg_exec($con,$sql);
			$tecnico = pg_result($resTecnico,0,tecnico);
        }
        

        if (in_array($login_fabrica, array(169,170))){
            $sql_inscrito = "
                SELECT posto, treinamento, treinamento_posto 
                FROM tbl_treinamento_posto 
                WHERE posto = {$login_posto} 
                AND treinamento = {$treinamento}
                AND tecnico IS NULL ";
            $res_inscrito = pg_query($con, $sql_inscrito);

            if (pg_num_rows($res_inscrito) > 0){
                $treinamento_posto = pg_fetch_result($res_inscrito, 0, 'treinamento_posto');
                $sql_delete = "DELETE FROM tbl_treinamento_posto WHERE treinamento_posto = {$treinamento_posto} AND treinamento = {$treinamento}";
                $res_delete = pg_query($con, $sql_delete);
            }
        }
        
        $sql = "INSERT INTO tbl_treinamento_posto (
                tecnico ,
                promotor     ,
                posto        ,
                hotel        ,
                treinamento  ,";
                if ($aux_promotor_treinamento) $sql .= " promotor_treinamento, ";
                $sql .= "
                observacao
            )VALUES(
                $tecnico        ,
                $aux_promotor    ,
                $login_posto     ,
                $hotel         ,
                '$treinamento'     ,";
                if ($aux_promotor_treinamento) $sql .= " $aux_promotor_treinamento, ";
                $sql .= "
                ".pg_escape_literal($con, $observacao)."
            )";
        $res = @pg_exec($con, $sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT CURRVAL ('seq_treinamento_posto')";
        $res = @pg_exec($con,$sql);
        $treinamento_posto =@ pg_result($res,0,0);
        
        $email = $posto_email;

        if($msg_erro==0){
            if ($enviar_confirmacao == "true"){
                $chave1 = md5($login_posto);
                $chave2 = md5($treinamento_posto);

                $sql=  "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
                $res = pg_exec ($con,$sql);
                $nome = pg_result($res,0,nome);

                $sql=  "SELECT  titulo                            ,
                        TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
                        TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim
                        FROM tbl_treinamento WHERE treinamento = $treinamento";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $titulo      = pg_fetch_result($res,0,titulo)     ;
                    $data_inicio = pg_fetch_result($res,0,data_inicio);
                    $data_fim    = pg_fetch_result($res,0,data_fim)   ;
                }
                //ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

                $email_origem  = "verificacao@telecontrol.com.br";
                $email_destino = "$email";
                $assunto       = "Confirmação de Presença no Treinamento";

                $corpo.= "Titulo: $titulo <br>\n";
                $corpo.= "Data Inicío: $data_inicio<br> \n";
                $corpo.= "Data Termino: $data_fim <p>\n";

                $corpo.="<br>Você recebeu esse email para confirmar a inscrição do técnico.\n\n";
                $corpo.="<br>Nome: $tecnico_nome \n";
                $corpo.="<br>RG:$tecnico_rg \n";
                $corpo.="<br>CPF: $tecnico_cpf \n";
                $corpo.="<br>Telefone de Contato: $tecnico_fone \n";

                if($login_fabrica == $makita){
                     $corpo.="<br>Telefone celular: {$tecnico_celular} \n";
                     $corpo.="<br>E-mail do técnico: {$tecnico_email}\n";
                     $corpo.="<br>Tamanho calçado: {$tecnico_calcado}\n";
                     $corpo.="<br>Passaporte: {$tecnico_passaporte}\n";
                     $corpo.="<br>Histórico de doenças: {$tecnico_doenca}\n";
                     $corpo.="<br>Medicamento: {$tecnico_medicamento}\n";
                     $corpo.="<br>Necessidades especiais: {$tecnico_necessidade}\n";
                     $corpo.="<br>Tipo Sanguíneo: {$tecnico_tipo_sanguineo}\n\n";
                }

                if($adicional) $corpo.="<br>$adicional: $observacao \n\n";
                $corpo.="<br>Email: $email\n\n";

                $host = $_SERVER['HTTP_HOST'];
                if(strstr($host, "devel.telecontrol") OR strstr($host, "homologacao.telecontrol")){
                    $corpo.="<br><br><a href='http://novodevel.telecontrol.com.br/~monteiro/Posvenda/treinamento_confirmacao.php?key1=$chave1&key2=$login_posto&key3=$chave2&key4=$treinamento_posto'>CLIQUE AQUI PARA CONFIRMAR PRESENÇA</a>.\n\n";
                }elseif($login_fabrica <> 1) {
                    $corpo.="<br><br><a href='http://posvenda.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$login_posto&key3=$chave2&key4=$treinamento_posto'>CLIQUE AQUI PARA CONFIRMAR PRESENÇA</a>.\n\n";
                }
            
                $corpo.="<br><br><br>Telecontrol\n";
                $corpo.="<br>www.telecontrol.com.br\n";
                $corpo.="<br>_______________________________________________\n";
                $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";


                $body_top = "MIME-Version: 1.0\r\n";
                $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
                $body_top .= "From: $email_origem\r\n";

                if ( @mail($email_destino, stripslashes($assunto), $corpo, $body_top ) ){
                    $msg = "$email";
                }else{
                    $msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
                }

                if ($aux_promotor_treinamento == '') $aux_promotor_treinamento = 0;
                $sql = "select nome, email
                        from tbl_promotor_treinamento
                        where promotor_treinamento = $aux_promotor_treinamento";
                $res = pg_exec($con,$sql);
                if(pg_numrows($res)>0){
                    $nome_promotor      = pg_result($res,0,nome)     ;
                    $email_promotor      = pg_result($res,0,email)     ;
                    if(strlen($email_promotor)>0){
                        $sql = "select nome, codigo_posto
                                from tbl_posto
                                join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
                                and tbl_posto_fabrica.fabrica = $login_fabrica
                                where tbl_posto.posto = $login_posto";
                        $res = pg_exec($con,$sql);
                        if(pg_numrows($res)>0){
                            $nome_posto      = pg_result($res,0,nome)        ;
                            $xcodigo_posto   = pg_result($res,0,codigo_posto);

                            $corpo = "";

                            $email_origem  = "verificacao@telecontrol.com.br";
                            $email_destino = "$email_promotor";
                            $assunto       = "Confirmação de Presença no Treinamento";
                            $corpo.="<br>Caro Promotor,";
                            $corpo.="<BR>Segue abaixo informações do posto e o treinamento solicitado\n<BR>";

                            $corpo.= "Titulo: $titulo <br>\n";
                            $corpo.= "Data Inicío: $data_inicio<br> \n";
                            $corpo.= "Data Termino: $data_fim <p>\n";
                            $corpo.="<BR>Posto: $xcodigo_posto - $nome_posto\n";
                            $corpo.="<br>Nome: $tecnico_nome \n";
                            $corpo.="<br>RG:$tecnico_rg \n";
                            $corpo.="<br>CPF: $tecnico_cpf \n";
                            $corpo.="<br>Telefone de Contato: $tecnico_fone \n\n";
                            if($adicional) $corpo.="<br>$adicional: $observacao \n\n";
                            $corpo.="<br>Email: $email\n\n";
                            $corpo.="<br><br><br>Telecontrol\n";
                            $corpo.="<br>www.telecontrol.com.br\n";
                            $corpo.="<br>_______________________________________________\n";
                            $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";


                            $body_top = "MIME-Version: 1.0\r\n";
                            $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
                            $body_top .= "From: $email_origem\r\n";

                            if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top ) ){
                                $msg = "$email";
                            }else{
                                $msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";

                            }
                        }
                    }
                }
            }else{
                if (in_array($login_fabrica, array(169,170)) AND $enviar_confirmacao == "false"){
                    $sql = "
                        UPDATE tbl_treinamento_posto SET confirma_inscricao = 't'
                        WHERE posto             = $login_posto
                        AND   treinamento_posto = $treinamento_posto ";
                    $res = @pg_exec ($con,$sql);
                }
            }
        }

        if (strlen($msg_erro) == 0 ) {
            $res = @pg_exec ($con,"COMMIT TRANSACTION");

            if (in_array($login_fabrica, [1])) {

                $sql2="SELECT   tbl_treinamento.treinamento,
                                tbl_treinamento.vagas - (
                                    SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                                        FROM tbl_treinamento_posto
                                        WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                            AND tbl_treinamento_posto.tecnico IS NOT NULL
                                            AND tbl_treinamento_posto.ativo is true
                                ) as vagas_geral,
                                tbl_treinamento.vaga_posto,
                                tbl_treinamento.vaga_posto - (
                                    SELECT COUNT(treinamento_posto) AS qtd_inscritos_posto
                                        FROM tbl_treinamento_posto
                                        WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                            AND tbl_treinamento_posto.tecnico IS NOT NULL
                                            AND tbl_treinamento_posto.ativo is true
                                            AND tbl_treinamento_posto.posto = {$login_posto}
                                ) as vaga_por_posto
                            FROM tbl_treinamento
                                JOIN tbl_admin USING(admin)
                                JOIN tbl_produto on (tbl_produto.linha = tbl_treinamento.linha OR tbl_produto.marca = tbl_treinamento.marca) AND tbl_produto.fabrica_i = {$login_fabrica}
                                JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
                                JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha
                                                  AND tbl_posto_linha.posto = $login_posto
                                JOIN tbl_treinamento_posto USING(treinamento)
                            WHERE tbl_treinamento.fabrica = {$login_fabrica}
                                AND tbl_treinamento.treinamento = {$treinamento}
                                AND tbl_treinamento.data_fim >= CURRENT_DATE 
                                GROUP BY tbl_treinamento.treinamento,tbl_treinamento.vaga_posto;";
                $res2 = pg_query($con,$sql2);

                if (pg_num_rows($res2) > 0) {
                    for ($l=0; $l < pg_num_rows($res2); $l++) { 
                        $vagas_geral = pg_fetch_result($res2, $l, vagas_geral);
                        $vaga_por_posto = pg_fetch_result($res2, $l, vaga_por_posto);
                        $valida_vaga_posto = pg_fetch_result($res2, $l, vaga_posto);

                        $tem_vaga = false;
                        if (empty($valida_vaga_posto)) {
                            if ($vagas_geral > 0) {
                                $tem_vaga = true;
                            }
                        } else {
                            if ($vagas_geral > 0 AND $vaga_por_posto > 0) {
                                $tem_vaga = true;
                            }
                        }
                    }
                }

                if ($tem_vaga) {
                    echo "ok|<center><font size='4'color='#009900'><b>Treinamento Agendado com sucesso!</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>Ver treinamentos</a> <br><button class='btn btn-inscreva-tecnico' type='button' onClick ='javascript:treinamento_formulario($treinamento)'>Inscreva outro Técnico</button></center>|$treinamento_posto";
                } else {
                    echo "ok|<center><font size='4'color='#009900'><b>Treinamento Agendado com sucesso!</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>Ver treinamentos</a> </center>|$treinamento_posto";
                }
            } else {
                if ($enviar_confirmacao == "true"){
                    echo "ok|<center><font size='4'color='#009900'><b>Treinamento Agendado com sucesso!</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>Ver treinamentos</a></center>";
                }else{
                    exit("ok|Técnico cadastrado com sucesso.");
                }
            }
            exit;
        }else{
            $res = @pg_exec ($con,"ROLLBACK TRANSACTION");
            echo  "2|<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s):</b><br> $msg_erro";
            exit;
        }

    }

    if (strlen($msg_erro) > 0) {
        echo "1|".$msg;
    }
    exit;

}
