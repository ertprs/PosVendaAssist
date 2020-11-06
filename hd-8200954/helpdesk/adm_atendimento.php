<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
    header ("Location: index.php");
}

//HD 7277 Paulo - tirar acento do arquivo upload
function retira_acentos( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

if($_POST['btn_tranferir']) $btn_tranferir = trim ($_POST['btn_tranferir']);

if (strlen ($btn_tranferir) > 0) {
if($_POST['transfere'])           { $transfere         = trim ($_POST['transfere']);}
        $sql =" UPDATE tbl_hd_chamado
                   SET status    = '$status',
                       titulo    = '$titulo',
                       atendente = $transfere
                 WHERE hd_chamado = $hd_chamado";
        $res = pg_exec ($con,$sql);
        //if($login_admin ==568)    echo "sql1 $sql<br>";
}


if (strlen ($btn_acao) > 0 AND $btn_acao == "SetarHoraDesenvolvimento") {

    if($_POST['hora_desenvolvimento']){
        $hora_desenvolvimento = trim ($_POST['hora_desenvolvimento']);
        $cobrar               = trim ($_POST['cobrar']);
    }

    if (strlen($hora_desenvolvimento)==0){
        $hora_desenvolvimento = " NULL ";
    }

    if (strlen($cobrar)>0){
        $cobrar = 't';
    }else{
        $cobrar = 'f';
    }

    $sql =" UPDATE tbl_hd_chamado
            SET     hora_desenvolvimento = $hora_desenvolvimento,
                    cobrar               = '$cobrar'
            WHERE hd_chamado = $hd_chamado";
    $res = pg_exec ($con,$sql);
    $msg_erro .= pg_errormessage($con);
    header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
    exit;
}

if (strlen ($btn_acao) > 0 AND $btn_acao == "Atendimento") {

    if($_POST['comentario'])          { $comentario      = trim ($_POST['comentario']);}
    if($_POST['titulo'])              { $titulo          = trim ($_POST['titulo']);}
    if($_POST['status'])              { $status          = trim ($_POST['status']);}
    if($_POST['transfere'])           { $transfere       = trim ($_POST['transfere']);}
    if($_POST['categoria'])           { $categoria       = trim ($_POST['categoria']);}
    if($_POST['sequencia'])           { $sequencia       = trim ($_POST['sequencia']);}
    if($_POST['duracao'])             { $duracao         = trim ($_POST['duracao']);}
    if($_POST['interno'])             { $interno         = trim ($_POST['interno']);}
    if($_POST['prazo_horas'])         { $prazo_horas     = trim ($_POST['prazo_horas']);}
    if($_POST['prioridade'])          { $prioridade      = trim ($_POST['prioridade']);}
    if($_POST['cobrar'])              { $cobrar          = trim ($_POST['cobrar']);}
    if($_POST['hora_desenvolvimento']){$hora_desenvolvimento= trim ($_POST['hora_desenvolvimento']);}

    if($_POST['previsao_termino'])    { $previsao_termino= trim ($_POST['previsao_termino']);}
    if($_POST['previsao_termino_interna'])    { $previsao_termino_interna= trim ($_POST['previsao_termino_interna']);}


    if($_POST['email'])               { $email           = trim ($_POST['email']);}
    if($_POST['titulo'])              { $titulo          = trim ($_POST['titulo']);}
    if($_POST['nome'])                { $nome            = trim ($_POST['nome']);}

    if($_POST['exigir_resposta'])     { $exigir_resposta = trim ($_POST['exigir_resposta']);}

    $arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

    if (strlen($cobrar)>0){
        $cobrar = 't';
    }else{
        $cobrar = 'f';
    }

    if (strlen($previsao_termino)>0){
        $previsao_termino_aux = "'". substr($previsao_termino,6,4)."-". substr($previsao_termino,3,2)."-". substr($previsao_termino,0,2)." ". substr($previsao_termino,11,5).":00'";
    }else{
        $previsao_termino_aux = " NULL ";
    }

    if (strlen($previsao_termino_interna)>0){
        $previsao_termino_interna_aux = "'". substr($previsao_termino_interna,6,4)."-". substr($previsao_termino_interna,3,2)."-". substr($previsao_termino_interna,0,2)." ". substr($previsao_termino_interna,11,5).":00'";
    }else{
        $previsao_termino_interna_aux = " NULL ";
    }

    if(strlen($prioridade)==0) $prioridade=5;

    if (strlen($hora_desenvolvimento)==0){
        $hora_desenvolvimento = " NULL ";
    }

    if (strlen($msg_erro) == 0){
        $res = pg_exec($con,"BEGIN TRANSACTION");

        //EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
        $sql =" UPDATE tbl_hd_chamado_item
                SET termino = current_timestamp
                WHERE hd_chamado_item in(SELECT hd_chamado_item
                             FROM tbl_hd_chamado_item
                             WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
                                AND termino IS NULL
                             ORDER BY hd_chamado_item desc
                             LIMIT 1 );";

        //if($login_admin ==568)    echo "sql2 $sql<br>";

        $res = pg_exec ($con,$sql);
        $msg_erro = pg_errormessage($con);

/*      if(strlen($hd_chamado_atendente)>0){
            echo "passou no if loko - hd_chamado_atendente" ;
            $sql = "UPDATE tbl_hd_chamado_atendente
                            SET data_termino = CURRENT_TIMESTAMP
                            WHERE hd_chamado_atendente = $hd_chamado_atendente
                            AND   admin               =  $login_admin
                            AND   data_termino IS NULL
                            ";
            $res = pg_exec ($con,$sql);
            $msg_erro = pg_errormessage($con);
        }
*/

        $sql = "SELECT  hd_chamado_atendente,
                        hd_chamado
                FROM tbl_hd_chamado_atendente
                WHERE admin = $login_admin
                AND   data_termino IS NULL
                ORDER BY hd_chamado_atendente DESC LIMIT 1";
        //if($login_admin ==568)    echo "sql3 $sql<br>";
        $res = pg_exec ($con,$sql);
        $msg_erro = pg_errormessage($con);
        if (pg_numrows($res) > 0) {
            $hd_chamado_atendente =  pg_result($res,0,hd_chamado_atendente);
            $hd_chamado_atual     = pg_result($res,0,hd_chamado);
        }

        if($hd_chamado_atual <> $hd_chamado){

            //EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
            $sql =" UPDATE tbl_hd_chamado_item
                    SET termino = current_timestamp
                    WHERE hd_chamado_item in(
                                SELECT hd_chamado_item
                                 FROM tbl_hd_chamado_item
                                 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
                                    AND termino IS NULL
                                 ORDER BY hd_chamado_item desc
                                 LIMIT 1
                    );";
        //if($login_admin ==568)    echo "sql4 $sql<br>";
            $res = pg_exec ($con,$sql);
            $msg_erro = pg_errormessage($con);

            if(strlen($hd_chamado_atendente)>0){
                $sql = "UPDATE tbl_hd_chamado_atendente
                        SET data_termino = CURRENT_TIMESTAMP
                        WHERE hd_chamado_atendente = $hd_chamado_atendente
                        AND   admin               =  $login_admin
                        AND   data_termino IS NULL
                                ";
        //if($login_admin ==568)    echo "sql5 $sql<br>";
                $res = pg_exec ($con,$sql);
                $msg_erro = pg_errormessage($con);
            }

            $sql = "INSERT INTO tbl_hd_chamado_atendente(
                        hd_chamado ,
                        admin      ,
                        data_inicio
                    )
                    VALUES(
                        $hd_chamado       ,
                        $login_admin      ,
                        CURRENT_TIMESTAMP
                    )";
        //if($login_admin ==568)    echo "sql6 $sql<br>";
            $res = pg_exec ($con,$sql);
            $msg_erro = pg_errormessage($con);
            $sql="SELECT CURRVAL('seq_hd_chamado_atendente');";
        //if($login_admin ==568)    echo "sql7 $sql<br>";
            $res = pg_exec ($con,$sql);
            $hd_chamado_atendente =  pg_result($res,0,0);
        }

        if(strlen($hd_chamado)==0){
            if (strlen($titulo) < 2){
                $msg_erro="Título muito pequeno";
            }
            $sql =  "INSERT INTO tbl_hd_chamado (
                        admin                                                        ,
                        fabrica_responsavel                                          ,
                        titulo                                                       ,
                        categoria                                                    ,
                        atendente                                                    ,
                        status                                                       ,
                        prioridade                                                   ,
                        cobrar                                                       ,
                        previsao_termino                                             ,
                        previsao_termino_interna                                     ,
                        hora_desenvolvimento
                    ) VALUES (
                        $login_admin                                                 ,
                        '10'                                                         ,
                        '$titulo'                                                    ,
                        '$categoria'                                                 ,
                        '435'                                                        ,
                        'Novo'                                                       ,
                        $prioridade                                                  ,
                        '$cobrar'                                                    ,
                        $previsao_termino_aux                                        ,
                        $previsao_termino_interna_aux                                ,
                        $hora_desenvolvimento
                    );";
            //echo $sql;
            //if($login_admin ==568)    echo "sql7 $sql<br>";
            $res = pg_exec ($con,$sql);

            $msg_erro = pg_errormessage($con);
            $msg_erro = substr($msg_erro,6);

            $res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
        //if($login_admin ==568)    echo "sql8 $sql<br>";
            $hd_chamado  = pg_result ($res,0,0);
        }else{

            $sql = "SELECT categoria ,
                            status
                    FROM tbl_hd_chamado
                    WHERE hd_chamado = $hd_chamado";
        //if($login_admin ==568)    echo "sql9 $sql<br>";
            $res = pg_exec ($con,$sql);
            $categoria_anterior = pg_result ($res,0,categoria);
            $status_anterior    = pg_result ($res,0,status);

            #-------- De Análise para Execução -------
            if (strlen ($sequencia) == 0 AND $status == "Análise" AND $status_anterior == "Análise") {
                $msg_erro = "Escolha a seqüência da tarefa. Ou continua em análise, ou vai para Execução.";
            }
            if ($sequencia == "SEGUE" AND $status_anterior == "Análise") $status = "Aguard.Execução" ;

            if ($sequencia == "SEGUE" AND $status_anterior == "Execução") $status = "Execução" ;

            if (($status == "Execução" OR $status == "Aguard.Execução") AND strlen ($duracao) == 0) {
//              $msg_erro .= "Informe o tempo de duração previsto para execução deste chamado.";
            }

            $duracao = str_replace (',','.',$duracao);
            if (strlen ($duracao) == 0) $duracao = "null";

            $prazo_horas = str_replace (',','.',$prazo_horas);

            if (strlen ($prazo_horas) == 0 AND ($status == "Execução" OR $status == "Aguard.Execução")) {
                $msg_erro = "Por favor de um prazo para o atendimento!";
            }

            if (strlen($prazo_horas)==0){
                $prazo_horas = " NULL ";
            }


            #-------- De Execução para Resolvido -------
            if (strlen ($sequencia) == 0 AND $status == "Execução" AND $status_anterior == "Execução") {
                $msg_erro = "Escolha a seqüência da tarefa. Ou continua em execução ou está resolvido.";
            }
            if ($sequencia == "SEGUE" AND $status_anterior == "Execução") $status = "Resolvido" ;

            if ($status == "Novo" AND $status_anterior == "Novo") {
                $status = "Análise";
            }

            if (strlen ($exigir_resposta) > 0) {
                $exigir_resposta = 't';
            }
            else $exigir_resposta = 'f';

            if (strlen ($interno) > 0) $xinterno = 't';
            else $xinterno = 'f';

            if (strlen($msg_erro) == 0){
                $sql =" UPDATE tbl_hd_chamado
                        SET status           = '$status' ,
                            titulo           = '$titulo',
                            categoria        = '$categoria',
                            duracao          = $duracao,
                            exigir_resposta  = '$exigir_resposta',
                            atendente        = $transfere,
                            prioridade       = $prioridade,
                            prazo_horas      = $prazo_horas,
                            cobrar           = '$cobrar',
                            previsao_termino = $previsao_termino_aux,
                            previsao_termino_interna = $previsao_termino_interna_aux,
                            hora_desenvolvimento = $hora_desenvolvimento
                        WHERE hd_chamado = $hd_chamado";
                //if($login_admin ==568)    echo "sql10 $sql<br>";
                $res = pg_exec ($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }
            if($status == 'Resolvido'){
                //EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
                $sql =" UPDATE tbl_hd_chamado_item
                        SET termino = current_timestamp
                        WHERE hd_chamado_item in(SELECT hd_chamado_item
                                     FROM tbl_hd_chamado_item
                                     WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
                                        AND termino IS NULL
                                     ORDER BY hd_chamado_item desc
                                     LIMIT 1 );";
                $res = pg_exec ($con,$sql);
                $msg_erro .= pg_errormessage($con);

                $sql = "UPDATE tbl_hd_chamado_atendente
                    SET data_termino = CURRENT_TIMESTAMP
                    WHERE admin                = $login_admin
                    AND   hd_chamado           = $hd_chamado
                    AND   hd_chamado_atendente = $hd_chamado_atendente";
                $res = pg_exec ($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }

        }

        if ($categoria <> $categoria_anterior) {
            $sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Categoria Alterada de $categoria_anterior para $categoria',$login_admin, 't')";
        //if($login_admin ==568)    echo "sql11 $sql<br>";
            $res = pg_exec ($con,$sql);
        }

        if ($status == "Resolvido" AND $status_anterior == "Execução") {
            $sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Chamado resolvido. Se você não concordar com a solução basta inserir novo comentário para reabrir o chamado.',$login_admin)";
        //if($login_admin ==568)    echo "sql12 $sql<br>";
            $res = pg_exec ($con,$sql);
        }

        if (strlen ($comentario) > 0) {
            $sql ="INSERT INTO tbl_hd_chamado_item (
                        hd_chamado                                                   ,
                        comentario                                                   ,
                        admin                                                        ,
                        status_item                                                  ,
                        interno
                    ) VALUES (
                        $hd_chamado                                                  ,
                        '$comentario'                                                ,
                        $login_admin                                                 ,
                        '$status'                                                    ,
                        '$xinterno'
                    );";
        //if($login_admin ==568)    echo "sql13 $sql<br>";
            //echo $sql;
            $res = pg_exec ($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
            //if($login_admin ==568)    echo "sqlcurval: SELECT CURRVAL ('seq_hd_chamado_item')<br>";
            $hd_chamado_item  = pg_result ($res,0,0);
        }


        //ROTINA DE UPLOAD DE ARQUIVO


        if (strlen ($msg_erro) == 0 and strlen($hd_chamado_item) > 0) {

            $config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)

            if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

                // Verifica o mime-type do arquivo
                if (!preg_match("/\/(zip|pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain|html)$/", $arquivo["type"])){
                    $msg_erro = "Arquivo em formato inválido!";
                } else { // Verifica tamanho do arquivo
                    if ($arquivo["size"] > $config["tamanho"])
                        $msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
                }
                if (strlen($msg_erro) == 0) {
                    // Pega extensão do arquivo
                    preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt){1}$/i", $arquivo["name"], $ext);
                    $aux_extensao = "'".$ext[1]."'";

                    $arquivo["name"]=retira_acentos($arquivo["name"]);

                    $nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

                    // Gera um nome único para a imagem
                    $nome_anexo = "/www/assist/www/helpdesk/documentos/" . $hd_chamado_item."-".strtolower ($nome_sem_espaco);

                    // Faz o upload da imagem
                    if (strlen($msg_erro) == 0) {
                        if (copy($arquivo["tmp_name"], $nome_anexo)) {
                        }else{
                            $msg_erro = "Arquivo não foi enviado!!!";
                        }
                    }//fim do upload da imagem
                }//fim da verificação de erro
            }//fim da verificação de existencia no apache
        }//fim de todo o upload



        if(strlen($msg_erro) > 0){
            $res = @pg_exec ($con,"ROLLBACK TRANSACTION");
            //if($login_admin ==568)    echo "sql ROLLBACK;<br>";
            $msg_erro .= 'Não foi possível Inserir o Chamado';
        }else{
            $res = @pg_exec($con,"COMMIT");
            //if($login_admin ==568)    echo "sql COMMIT<br>";
            if($status <>'Resolvido' and $exigir_resposta == 't'){
                $sql="SELECT nome_completo,email,tbl_admin.admin FROM tbl_admin JOIN tbl_hd_chamado ON tbl_hd_chamado.admin = tbl_admin.admin WHERE hd_chamado = $hd_chamado";
                $res = pg_exec ($con,$sql);
                $email                = pg_result($res,0,email);
                $nome                 = pg_result($res,0,nome_completo);
                $adm                  = pg_result($res,0,admin);

                $chave1=md5($hd_chamado);
                $chave2=md5($adm);
                $email_origem  = "suporte@telecontrol.com.br";
                $email_destino = $email;
                $assunto       = "Seu chamado n° $hd_chamado foi RESOLVIDO";
                $corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
                        NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
                        <P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
                        <STRONG>$titulo</STRONG>&nbsp; </P>
                        <P align=left>$nome,</P>
                        <P align=justify>Seu chamado foi&nbsp;<FONT
                        color=#006600><STRONG>resolvido</STRONG></FONT> pelo suporte Telecontrol, você
                        tem <U>3(três) dias para concordar com a solução do chamado</U>. Caso
                        <STRONG>não haja manifestação</STRONG>&nbsp;será
                        considerado&nbsp;<STRONG>resolvido automaticamente. </STRONG>Caso
                        não concorde&nbsp;com a resolução do chamado <STRONG>insira um
                        comentário</STRONG> para <STRONG>reabrir o chamado</STRONG>.</P>
                        <P align=justify>Se após este prazo o problema/dúvida continuar, abra um novo
                        chamado.</P>
                        <P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
                        <P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
                        </P>";
                if($exigir_resposta=='t' and $status<>'Resolvido' ){
                    $assunto       = "Seu chamado n° $hd_chamado está aguardando sua resposta";

                    $corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
                            NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
                            <P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
                            <STRONG>$titulo</STRONG>&nbsp; </P>
                            <P align=left>$nome,</P>

                            <P align=justify>
                            Precisamos de sua posição para continuarmos atendendo o chamado.
                            </P>
                            <P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
                            <P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
                            </P>";
                }

                $body_top = "--Message-Boundary\n";
                $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                $body_top .= "Content-transfer-encoding: 7BIT\n";
                $body_top .= "Content-description: Mail message body\n\n";


                if ($mailer->sendMail($email_destino, stripslashes($assunto), $corpo, $email_origem)) {
                    $msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
                } else {
                    $msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
                }
            }
            header ("Location: adm_chamado_detalhe.php?hd_chamado=$hd_chamado");
        }
    }
}



if(strlen($hd_chamado) > 0){
    $sql = "UPDATE tbl_hd_chamado
            SET atendente = $login_admin
            WHERE hd_chamado = $hd_chamado AND atendente IS NULL";
    $res = pg_exec ($con,$sql);

    $sql= " SELECT tbl_hd_chamado.hd_chamado                             ,
                    tbl_hd_chamado.admin                                 ,
                    to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
                    tbl_hd_chamado.titulo                                ,
                    tbl_hd_chamado.categoria                             ,
                    tbl_hd_chamado.status                                ,
                    tbl_hd_chamado.duracao                               ,
                    tbl_hd_chamado.atendente                             ,
                    tbl_hd_chamado.fabrica_responsavel                   ,
                    tbl_hd_chamado.prazo_horas                           ,
                    tbl_hd_chamado.prioridade                            ,
                    tbl_hd_chamado.duracao                               ,
                    tbl_hd_chamado.cobrar                                ,
                    tbl_hd_chamado.hora_desenvolvimento                  ,
                    to_char (tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI') AS previsao_termino,
                    to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM/YYYY HH24:MI') AS previsao_termino_interna,
                    tbl_fabrica.nome   AS fabrica_nome                   ,
                    tbl_admin.login                                      ,
                    tbl_admin.nome_completo                              ,
                    tbl_admin.fone                                       ,
                    tbl_admin.email                                      ,
                    atend.nome_completo AS atendente_nome
            FROM tbl_hd_chamado
            JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
            LEFT JOIN tbl_admin atend ON tbl_hd_chamado.atendente = atend.admin
            WHERE hd_chamado = $hd_chamado";
    $res = pg_exec ($con,$sql);

    if (pg_numrows($res) > 0) {
        $admin                = pg_result($res,0,admin);
        $data                 = pg_result($res,0,data);
        $titulo               = pg_result($res,0,titulo);
        $categoria            = pg_result($res,0,categoria);
        $status               = pg_result($res,0,status);
        $duracao              = pg_result($res,0,duracao);
        $atendente            = pg_result($res,0,atendente);
        $prazo_horas          = pg_result($res,0,prazo_horas);
        $atendente_nome       = pg_result($res,0,atendente_nome);
        $fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
        $nome                 = pg_result($res,0,nome_completo);
        $email                = pg_result($res,0,email);
        $fone                 = pg_result($res,0,fone);
        $nome_completo        = pg_result($res,0,nome_completo);
        $fabrica_nome         = pg_result($res,0,fabrica_nome);
        $login                = pg_result($res,0,login);
        $prioridade           = pg_result($res,0,prioridade);
        $duracao              = pg_result($res,0,duracao);
        $cobrar               = pg_result($res,0,cobrar);
        $hora_desenvolvimento= pg_result($res,0,hora_desenvolvimento);
        $previsao_termino     = pg_result($res,0,previsao_termino);
        $previsao_termino_interna = pg_result($res,0,previsao_termino_interna);
    }else{
        $msg_erro="Chamado não encontrado";
    }
}



$TITULO = "ADM - Responder Chamado";

include "menu.php";
?>

<script type="text/javascript" charset="utf-8">
    $(function(){
        $("#previsao_termino").maskedinput("99/99/9999 99:99");
        $("#previsao_termino_interna").maskedinput("99/99/9999 99:99");
    });
</script>

<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='post' enctype='multipart/form-data'>
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>

<br>
<table width = '750' align = 'center'  cellpadding='2'  style='font-family: arial ; font-size: 12px'>
<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px;'><strong>&nbsp;Título </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='5'>&nbsp;<input type='text' size='30' name='titulo' value='<?= $titulo ?>' class='caixa'> </td>
    <td  bgcolor="#E5EAED" rowspan='2' style='border-style: solid; border-color: #6699CC; border-width=1px' align='center' valign='middle'>CHAMADO N° <br><b style='font-size:18px'><?=$hd_chamado?></b></td>
</tr>
<tr>
    <td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px '><strong>&nbsp;Login </strong></td>
    <td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<?= $login ?> </td>
    <td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Abertura </strong></td>
    <td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $data ?> </td>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Analista </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $atendente_nome ?></td>
</tr>
</table>
<br>
<center><a href='#responder'>INTERAGIR</a></center>
<br>
<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
        to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
        to_char((tbl_hd_chamado_item.TERMINO -tbl_hd_chamado_item.DATA), 'HH24:MI') AS tempo_trabalho,
                tbl_hd_chamado_item.comentario                            ,
                tbl_hd_chamado_item.interno                               ,
                tbl_admin.nome_completo AS autor                          ,
                tbl_hd_chamado_item.status_item
        FROM tbl_hd_chamado_item
        JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
        WHERE hd_chamado = $hd_chamado
        ORDER BY hd_chamado_item";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
    echo "<table width = '750' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
    echo "<tr>";
    echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";

    echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Interações</b></td>";

    echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
    echo "</tr>";

    echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
    echo "<td ><strong><font size='2'>Nº</font></strong></td>";
    echo "<td nowrap><strong><font size='2'>Data e Hora</font></strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
    echo "<td nowrap><strong><font size='2'>Tmp Trab.</font></strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
    echo "<td ><strong><font size='2'>  Coment&aacute;rio </strong></font></td>";
    echo "<td ><strong><font size='2'> Anexo </strong></font></td>";
    echo "<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><font size='2'><strong>Autor </strong></font></td>";
    echo "</tr>";

    for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
        $x=$i+1;
        $hd_chamado_item = pg_result($res,$i,hd_chamado_item);
        $data_interacao  = pg_result($res,$i,data);
        $autor           = pg_result($res,$i,autor);
        $item_comentario = pg_result($res,$i,comentario);
        $status_item     = pg_result($res,$i,status_item);
        $interno         = pg_result($res,$i,interno);
        $tempo_trabalho= pg_result($res,$i,tempo_trabalho);

        if ($interno == 't'){
            if($cor == '#FFFFCC') $cor = '#FFFFEE';
            else                  $cor = '#FFFFCC';
        }else{
            $cor='#ffffff';
            if ($i % 2 == 0)     $cor = '#F2F7FF';
        }
        if ($status_item == 'Resolvido')$cor2 = '#82FFA2';

        if($interno == 't'){
            echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor'>";
            echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
            echo "<td colspan='6' align='center'><b>Chamado interno</b></td>";
            echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9' align='bottom'></td>";
            echo "</tr>";
        }

        echo "<tr  style='font-family: arial ; font-size: 11px' height='25' bgcolor='$cor'>";
        echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
        echo "<td nowrap width='50'>$x </td>";
        echo "<td nowrap>$data_interacao </td>";
        echo "<td nowrap>$tempo_trabalho</td>";
        echo "<td >" . nl2br ($item_comentario) . "</td>";

        echo "<td>";
        $dir = "documentos/";
        $dh  = opendir($dir);

        while (false !== ($filename = readdir($dh))) {
            if (strpos($filename,"$hd_chamado_item") !== false){
            //echo "$filename\n\n";
                $po = strlen($hd_chamado_item);
                if(substr($filename, 0,$po)==$hd_chamado_item){

                    echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$filename target='blank'><img src='imagem/clips.gif' border='0'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
                }

            }
        }
        echo "</td>";
        echo "<td nowrap >$autor</td>";
        echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9' align='bottom'></td>";
        echo "</tr>";
        if ($status_item == 'Resolvido'){
            echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor2'>";
            echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
            echo "<td colspan='6' align='center'><b>Chamado foi resolvido nesta interação</b></td>";
            echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9' align='bottom'></td>";
            echo "</tr>";
        }
    }

    echo "<tr>";
    echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
    echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' width='100%'></td>";
    echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
    echo "</tr>";

    echo "</table>";
}

echo "<center>";

?>
<table width = '750' align = 'center'  cellpadding='2'  style='font-family: arial ; font-size: 12px' >
<a name='#responder'>
<br>
<br>
<tr>
    <td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px '><strong>&nbsp;Login </strong></td>
    <td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<?= $login ?> </td>
    <td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Abertura </strong></td>
    <td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $data ?> </td>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Prioridade </strong></td>

    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'align='center' >
    <?
    if    ($prioridade==0) echo "<font color='#FF0000' ><img src='/assist/admin/imagens_admin/status_vermelho.gif'> <b>ALTA</b></font>";
    elseif($prioridade==5) echo "<font color='#006600' ><img src='/assist/admin/imagens_admin/status_verde.gif'> <b>NORMAL</b></font>";
    else echo "NORMAL";
    ?>
    </td>

</tr>


<?
if (strlen ($hd_chamado) > 0) {
?>
<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Status </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>
        <select name="status" size="1">
        <!--<option value=''></option>-->
        <option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
        <option value='Análise'   <? if($status=='Análise')   echo ' SELECTED '?> >Análise</option>
        <option value='Aguard.Execução'  <? if($status=='Aguard.Execução')  echo ' SELECTED '?> >Aguard.Execução</option>
        <option value='Execução'  <? if($status=='Execução')  echo ' SELECTED '?> >Execução</option>
        <option value='Aprovação' <? if($status=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
        <option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
        <option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
        </select>
    </td>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Analista </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $atendente_nome ?></td>
    <td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' rowspan='2'colspan='2'align='center' valign='middle'>CHAMADO N°<br><h1><?=$hd_chamado?></h1></td>
</tr>
<? } ?>


<?
if ($status == "Análise") {
?>
<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Seqüência </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>
        <input type='radio' name='sequencia' value='CONTINUA'>Continua em Análise
        <br>
        <input type='radio' name='sequencia' value='SEGUE'>Vai para Execução

    </td>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Duração </strong></td>
    <td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>
    &nbsp;
    <input type='text' size='5' name='duracao' value='<?= $duracao ?>' >
    <br>
    <font size='-2' color='#333333'>Em hora decimal. <br>Ex.: Uma hora e meia = 1,5</font>
    </td>
</tr>
<? } ?>

<?
if ($status == "Aguard.Execução") {
?>
<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Seqüência </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>
        <input type='radio' name='sequencia' value='CONTINUA' checked>Continua Aguard.Execução
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type='radio' name='sequencia' value='SEGUE'>Vai para Execução

    </td>
</tr>
<? }

if ($status == "Execução") {
?>
<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Seqüência </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>
        <input type='radio' name='sequencia' value='CONTINUA' checked>Continua em Execução
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type='radio' name='sequencia' value='SEGUE'>Resolvido

    </td>
</tr>
<? } ?>

<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px;'><strong>&nbsp;Título </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' >&nbsp;<input type='text' size='30' name='titulo' value='<?= $titulo ?>' class='caixa'> </td>


    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Categoria </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;
        <select name="categoria" size="1" >
        <option></option>
        <option value='Ajax' <? if($categoria=='Ajax') echo ' SELECTED '?> >Ajax, JavaScript</option>
        <option value='Design' <? if($categoria=='Design') echo ' SELECTED '?> >Design</option>
        <option value='Implantação' <? if($categoria=='Implantação') echo ' SELECTED '?> >Implantação</option>
        <option value='Integração' <? if($categoria=='Integração') echo ' SELECTED '?> >Integração (ODBC, Perl)</option>
        <option value='Linux' <? if($categoria=='Linux') echo ' SELECTED '?> >Linux, Hardware, Data-Center</option>
        <option value='Novos' <? if($categoria=='Novos') echo ' SELECTED '?> >Novos Projetos</option>
        <option value='SQL' <? if($categoria=='SQL') echo ' SELECTED '?> >Otimização de SQL e Views</option>
        <option value='PHP' <? if($categoria=='PHP') echo ' SELECTED '?> >PHP</option>
        <option value='PL' <? if($categoria=='PL') echo ' SELECTED '?> >PL/PgSQL, functions e triggers</option>
        <option value='Postgres' <? if($categoria=='Postgres') echo ' SELECTED '?> >Postgres</option>
        <option value='Suporte Telefone' <? if($categoria=='Suporte Telefone') echo ' SELECTED '?> >Suporte Telefone</option>
        </select>
        <br>Cobrar <input type="checkbox" name="cobrar" value='t' <? if  ($cobrar == 't') echo " CHECKED "; ?>>
    </td>
</tr>
<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Nome </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;<input type='text' size='50' name='nome' value='<?= $nome ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> class='caixa'></td>
    </tr>

<tr>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Email </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='28' name='email' value='<?= $email ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> class='caixa'></td>
    <td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Fone </strong></td>
    <td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='20' name='fone' value='<?=$fone ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> class='caixa'></td>
</tr>

</table>

<?
echo "<b><font face='arial' color='#666666'>Resposta ao chamado</font></b>";
echo "<br>";
echo "<table cellpadding='0'border='0' width='750'><tr>";
echo "<td align='left'>";

//resposta do chamado
echo "<table width = '525' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";

echo "<tr>";
echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Atendente</strong></td>";
echo "<td colspan='3' bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='left' >";
$sql = "SELECT  *
        FROM    tbl_admin
        WHERE   tbl_admin.fabrica = 10
        ORDER BY tbl_admin.nome_completo;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
    echo "<select class='frm' style='width: 200px;' name='transfere'>\n";
    echo "<option value=''>- ESCOLHA -</option>\n";

    for ($x = 0 ; $x < pg_numrows($res) ; $x++){
        $aux_admin = trim(pg_result($res,$x,admin));
        $aux_nome_completo  = trim(pg_result($res,$x,nome_completo));

        echo "<option value='$aux_admin'"; if ($atendente == $aux_admin) echo " SELECTED "; echo "> $aux_nome_completo</option>\n";
    }

    echo "</select>\n";
}
echo "</td>";
echo "</tr>";



//PRAZO PARA RESOULÇÃO DO CHAMADO
echo "<tr>";


echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Prazo Interno</strong></td>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='left' ><input type='text' size='18' name='previsao_termino_interna' id='previsao_termino_interna' maxlength='16' value='".$previsao_termino_interna."'></td>";

echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Prazo </strong></td>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='center' ><input type='text' size='2' maxlength='4' name='prazo_horas' value='$prazo_horas' class='caixa' > <font size='2'>Hrs</font></td>";

echo "</tr>";

echo "<tr>";

echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Prazo de Término</strong></td>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='left' ><input type='text' size='18' name='previsao_termino' id='previsao_termino' maxlength='16' value='".$previsao_termino."'></td>";

echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'></td>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' align='center' ></td>";

echo "</tr>";

echo "<tr>";

//--== CAMPO DO COMENTÁRIO ==========================================--\\
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='center' rowspan='2' colspan='3'>";
echo "<textarea name='comentario' id='comentario'cols='53' rows='10' wrap='VIRTUAL' class='caixa'>$comentario</textarea><br>";
echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";
//--=================================================================--\\

//--== TIPOS DE RESPOSTAS ===========================================--\\
echo "<input type='checkbox' name='exigir_resposta' value='1' class='caixa'> Exigir resposta do usuário ";
echo "<input type='checkbox' name='interno' value='t' class='caixa'> Chamado Interno ";
echo "</td>";
//--=================================================================--\\

echo "  <td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px' height='10' align='center'width='60' ><strong>Prioridade </strong></td>";

echo "</tr>";
echo "<tr>";

echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='right' >";

echo "Alta<INPUT TYPE='radio' NAME='prioridade' value='0' ";  if($prioridade=='0') echo "CHECKED"; echo "><br>";
echo "Normal<INPUT TYPE='radio' NAME='prioridade' value='5' ";if($prioridade=='5') echo "CHECKED";echo ">";
echo "</td>";
echo "</tr>";


//--== INSERIR ARQUIVO ==============================================--\\
echo "<tr>";
echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Arquivo</strong></td>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'><input type='file' name='arquivo' size='50' class='frm'></td>";
echo "<tr><td colspan='4' bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='center'><input type='submit' name='btn_acao' value='Atendimento'></td>";
echo"</tr>";
//--=================================================================--\\


echo "</table>";
echo "</form>";

echo "<br>";
echo "<br>";

//resposta do chamado
echo "<form name='frm_chamada' action='$PHP_SELF' method='post' enctype='multipart/form-data'>";
echo "<input type='hidden' name='hd_chamado' value='$hd_chamado'>";
echo "<table width = '525' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
echo "<tr>";
echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>Horas Desenvolvimento</strong></td>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='left' ><input type='text' size='5' name='hora_desenvolvimento' id='hora_desenvolvimento' maxlength='3' value='".$hora_desenvolvimento."'> horas
<input type='hidden' name='btn_acao' value='SetarHoraDesenvolvimento'>
&nbsp;&nbsp;
Cobrar <input type='checkbox' name='cobrar' value='t'";if  ($cobrar == 't') echo " CHECKED "; echo ">
&nbsp;&nbsp;
<input type='submit' name='btn_gravar' value='Gravar'>
</td>";
echo "</tr>";
echo "</table>";
echo "</form>";


echo "</td><td valign='top'>";


//--== TABELA DE ATENDENTES =========================================--\\

$sql = "SELECT DISTINCT tbl_admin.nome_completo         ,
                        tbl_hd_chamado.atendente
            FROM tbl_hd_chamado
            JOIN tbl_admin ON tbl_admin.admin=tbl_hd_chamado.atendente
            WHERE   tbl_admin.fabrica = 10
            AND tbl_admin.admin  NOT IN(29)
            ORDER BY tbl_admin.nome_completo ASC
";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

    echo "<table width = '225' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
    echo "<tr>";
    echo"<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3' align='center'><strong>PREVISÃO DE TÉRMINO </strong></td>";

    echo"</tr>";

    for ($x = 0 ; $x < pg_numrows($res) ; $x++){
        $pt = trim(pg_result($res,$x,atendente));
        $pt_nome_completo  = trim(pg_result($res,$x,nome_completo));


        echo "<tr>";
        echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='left' ><a href='adm_chamado_lista.php?atendente_busca=$pt' target='_blank'>$pt_nome_completo</a></td>";

        $sql2=" SELECT  SUM(prazo_horas) as total         ,
                        count(hd_chamado) as total_tarefas
                FROM tbl_hd_chamado
                WHERE atendente = $pt
                and tbl_hd_chamado.fabrica_responsavel = 10
                AND STATUS    NOT IN('Resolvido','Cancelado')
                AND resolvido IS NULL";

        $res2 = pg_exec ($con,$sql2);
        $pt_total        = trim(pg_result($res2,0,total));
        $pt_total_tarefa = trim(pg_result($res2,0,total_tarefas));

        if($pt_total_tarefa==0)
            $frase = "$pt_nome_completo não tem nenhuma tarefa";
        elseif($pt_total_tarefa==1)
            $frase = "$pt_nome_completo tem $pt_total_tarefa tarefa pendente, e ficará livre em $pt_total hora(s)";
        else
            $frase = "$pt_nome_completo tem $pt_total_tarefa tarefas pendentes, e ficará livre em $pt_total hora(s)";

        if($pt_total=='')$pt_total="<font size='2'color='#006600'><b>LIVRE</b></font>";
        else $pt_total= $pt_total." Hrs";

        echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='center' width='40' title='$frase'>&nbsp;$pt_total </td>";

        echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='center' width='20' title='$frase'>&nbsp;$pt_total_tarefa </td>";
    }
    echo "</tr>";
    echo "</table>";

}
echo "</td></tr></table>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "</center>";




?>

<? include "rodape.php" ?>
