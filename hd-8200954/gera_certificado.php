<?php
include "admin/dbconfig.php";
include "admin/includes/dbconnect-inc.php";
include 'admin/autentica_admin.php';
include 'class/ComunicatorMirror.php';

$comunicatorMirror = new ComunicatorMirror();

/* Midea */
if (in_array($login_fabrica, array(169,170,193))){
   
    /* CÓDIGO DE TERCEIROS: função para abreviar sobre nome */
    function abrevia($nome) {   
        $nome = explode(" ", $nome); 
        $num  = count($nome); 
        
        if($num == 2) { 
            return $nome; 
        }else {
            $count     = 0; 
            $novo_nome = '';
            
            foreach($nome as $var){
                
                if($count == 0) {$novo_nome .= $var.' ';}
                
                $count++;
                
                if(($count >= 2) && ($count < $num)) {
                    
                        $array = array('do', 'Do', 'DO', 'da', 'Da', 'DA', 'de', 'De', 'DE', 'dos', 'Dos', 'DOS', 'das', 'Das', 'DAS');
                        
                        if(in_array($var, $array)) {
                            $novo_nome .= $var.' ';
                        }else {                            
                            $novo_nome .= substr($var, 0, 1).'. '; // abreviou
                        }                            
                }
                if($count == $num) {$novo_nome .= $var;}
            }
            return $novo_nome;
        }
    }

    $treinamento_posto = trim($_POST['treinamento_posto']);
    $treinamento       = trim($_POST['treinamento']);
    $isConvidado       = trim($_POST['isConvidado']); 
    $returnLinkText    = trim($_POST['returnLinkText']);

    if (strlen($treinamento_posto) > 0){
        $treinamento_posto = trim($_POST['treinamento_posto']);
        $sqlBuscaTec       = "
                SELECT  tbl_treinamento_posto.tecnico
                FROM    tbl_treinamento_posto
                INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
                WHERE   tbl_treinamento_posto.treinamento_posto = $treinamento_posto
            ";
            $resBuscaTec   = pg_query($con,$sqlBuscaTec);
            $tecnico       = pg_fetch_result($resBuscaTec,0,tecnico);
    }else{
        $tecnico           = trim($_POST['tecnico']);
    }
    
    $sql_treinamento_dados    = "SELECT DISTINCT
                                    tbl_treinamento.titulo,
                                    tbl_treinamento.data_inicio,
                                    tbl_treinamento.data_fim,
                                    tbl_posto.nome AS nome_empresa,
                                    (tbl_treinamento.data_fim::DATE - tbl_treinamento.data_inicio::DATE) AS dia_treinamento, 
                                    tbl_treinamento_posto.dia_participou,
                                    tbl_cidade.nome AS cidade,
                                    tbl_cidade.estado AS estado_cidade,
                                    tbl_treinamento.estado,
                                    tbl_tecnico.nome AS tecnico_nome,
                                    tbl_tecnico.email AS tecnico_email,
                                    {$select_campos}
                                    tbl_tecnico.dados_complementares,
                                    tbl_treinamento_posto.aprovado,
                                    tbl_treinamento_posto.participou,
                                    tbl_treinamento_posto.treinamento_posto,
                                    tbl_treinamento_posto.nota_tecnico,
                                    tbl_treinamento_posto.aplicado,
                                    tbl_treinamento.parametros_adicionais,
                                    tbl_promotor_treinamento.nome AS instrutor_nome
                                FROM tbl_treinamento
                                    INNER JOIN tbl_treinamento_posto     ON tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                    INNER JOIN tbl_tecnico               ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
                                    
                                    INNER JOIN tbl_treinamento_instrutor ON tbl_treinamento_instrutor.treinamento = tbl_treinamento.treinamento
                                    INNER JOIN tbl_promotor_treinamento  ON tbl_promotor_treinamento.promotor_treinamento = tbl_treinamento_instrutor.instrutor_treinamento

                                    LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_treinamento_posto.posto 
                                    LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                                    INNER JOIN tbl_cidade  ON tbl_cidade.cidade   = tbl_treinamento.cidade
                                WHERE tbl_treinamento_posto.treinamento = {$treinamento}
                                    AND tbl_treinamento_posto.tecnico = {$tecnico} ";
    $res_treinamento_dados  = pg_query($con,$sql_treinamento_dados);
    if (pg_num_rows($res_treinamento_dados) > 0)
    {
        $nome_tecnico     = strtoupper(pg_fetch_result($res_treinamento_dados, 0, "tecnico_nome"));
        $email_tecnico    = strtoupper(pg_fetch_result($res_treinamento_dados, 0, "tecnico_email"));
        $nome_treinamento = strtoupper(pg_fetch_result($res_treinamento_dados, 0, "titulo"));
        $cidade                = pg_fetch_result($res_treinamento_dados, 0, "cidade");
        $estado_cidade         = pg_fetch_result($res_treinamento_dados, 0, "estado_cidade");
        $estado                = pg_fetch_result($res_treinamento_dados, 0, "estado");
        $participou            = pg_fetch_result($res_treinamento_dados, 0, "participou");
        $aprovado              = pg_fetch_result($res_treinamento_dados, 0, "aprovado");
        $nota_tecnico          = pg_fetch_result($res_treinamento_dados, 0, "nota_tecnico");
        $instrutor_nome        = pg_fetch_result($res_treinamento_dados, 0, "instrutor_nome");
        $dia_participou        = pg_fetch_result($res_treinamento_dados, 0, "dia_participou");
        $dia_treinamento       = pg_fetch_result($res_treinamento_dados, 0, "dia_treinamento")+1;
        $fez_prova             = pg_fetch_result($res_treinamento_dados, 0, "aplicado");
        $frequencia            = ($dia_participou * 100) / $dia_treinamento;
        $frequencia            = ($frequencia > 99) ? substr($frequencia, 0, 3) : substr($frequencia, 0, 2);
        $dados_complementares  = json_decode(pg_fetch_result($res_treinamento_dados, 0, "dados_complementares"));
        $parametros_adicionais = json_decode(pg_fetch_result($res_treinamento_dados, 0, "parametros_adicionais"));

        if (empty($dados_complementares->empresa) || $dados_complementares->empresa == NULL || $dados_complementares->empresa == ''){            
            $nome_empresa         = pg_fetch_result($res_treinamento_dados, 0, "nome_empresa");
        }else{
            $nome_empresa         = $dados_complementares->empresa;
        }
        
        if ($participou != 't' || $participou != true){
            $msg_erro = 'Técnico não participou do Evento!';
            exit(json_encode(array("error" => utf8_encode($msg_erro))));
        }

        if ($aprovado === 't' || $aprovado === true){
            $tipo_certificado = 'Certificado de Conclusão';
        }else{
            $tipo_certificado = 'Certificado de Participação';
        }

        if ($fez_prova === 't' || $fez_prova === true){
            $exibe_nota = "<span class='left'>Nota:          ".$nota_tecnico."       </span> <br />";
        }else{
            $exibe_nota = "";
        }

        $data_inicio   = pg_fetch_result($res_treinamento_dados, 0, "data_inicio");
        $data_fim      = pg_fetch_result($res_treinamento_dados, 0, "data_fim");

        $datatime_ini  = new DateTime($data_inicio);
        $datatime_fim  = new DateTime($data_fim);
        $diff          = $datatime_ini->diff($datatime_fim);
        $horas         = $diff->h + ($diff->days * 24);
        $data_inicio_e = explode(" ", $data_inicio);
        $data_inicio_e = explode("-", $data_inicio_e[0]);
        $data_fim_e    = explode(" ", $data_fim);
        $data_fim_e    = explode("-", $data_fim_e[0]);
        $periodo       = $data_inicio_e[2]." e ".$data_fim_e[2]."/".$data_fim_e[1]."/".$data_fim_e[0];
        $carga_horaria = $parametros_adicionais->carga_horaria;
        $carga_horaria = (!empty($carga_horaria)) ? $carga_horaria."h" : $carga_horaria;
        
        if($estado == ""){
            $estado = $estado_cidade;
        }

        if (in_array($login_fabrica, [169,170])) {
            $fundo_certificado = 'gera_certificado_fundo.png';
        } else {
            $fundo_certificado = 'fundo_certificado_198.jpg';
        }

        $conteudo = "<html>
                        <head>
                            <link href='https://fonts.googleapis.com/css?family=Montserra' rel='stylesheet'>
                            <style>
                                body {
                                    font-family: 'Montserrat', sans-serif;
                                    background-image: url('./{$fundo_certificado}');
                                    background-image-resize: 6;
                                    background-repeat: no-repeat;
                                    color: #404040;
                                }
                                .padd {
                                   padding-bottom: 30px;
                                }
                                .periodo {                           
                                    padding-top: 30px;
                                    padding-left: 700px;
                                }
                                .left {
                                    text-align: left !important;
                                }
                                .infos {
                                    line-height: 45px;
                                    padding-bottom: 10px;
                                    text-align: center !important;                                    
                                }
                                .m{
                                    font-size: 1.0em;
                                }
                                .gg{
                                    font-size: 1.5em;
                                    font-weight: bold;
                                }
                                
                            </style>
                        </head>
                        <body>
                            <div class='conteudo'>
                                <!-- Informacoes do Curso e do Aluno -->
                                <div class='infos'>
                                    <span class='gg padd'>PROGRAMA DE CAPACITAÇÃO PROFISSIONAL</span>        <br /><br />
                                    <span class='m'>Concede este</span>                  <br />
                                    <span class='gg'>".$tipo_certificado."</span>        <br />
                                    <span class='m'>no Curso de</span>                   <br />
                                    <span class='gg'>".$nome_treinamento."</span>        <br />
                                    <span class='m'>a</span>                             <br />
                                    <span class='gg'>".$nome_tecnico."</span>            <br />
                                    <span class='m'>da Empresa</span>                    <br />
                                    <span class='gg'>".$nome_empresa."</span>            <br />
                                </div>

                                <!-- Peroodo, Carga Horaria e Local -->
                                <div class='periodo'>
                                    <span class='left'>Período:       ".$periodo."            </span> <br />
                                    <span class='left'>Carga Horária: ".$carga_horaria."      </span> <br />
                                    <span class='left'>Local:         ".$cidade."/".$estado." </span> <br />
                                    <span class='left'>Instrutor:     ".$instrutor_nome."     </span> <br />
                                    {$exibe_nota}
                                    <span class='left'>Frequência:    ".$frequencia."%        </span> <br />
                                </div>
                            </div>
                        </body>
                    </html>";


                                
            /* gerando certificado */
            include "plugins/fileuploader/TdocsMirror.php";
            include "classes/mpdf61/mpdf.php";
                $mpdf = new mPDF(); 
                $mpdf->SetDisplayMode('fullpage');
                $mpdf->charset_in = 'windows-1252';
                $mpdf->AddPage('L');
                $mpdf->WriteHTML($conteudo);
                $mpdf->Output("/tmp/certificado_{$tecnico}.pdf", "F"); // GERA ARQUIVO EM UM CAMINHO    
                $caminho = "/tmp/certificado_{$tecnico}.pdf";
        
            /* gravando no TDocs */
            $tdocsMirror = new TdocsMirror();
            $response = $tdocsMirror->post($caminho);

            if(array_key_exists("exception", $response)){
                header('Content-Type: application/json');
                echo json_encode(array("exception" => "Ocorreu um erro ao realizar o upload: ".$response['message']));
                exit;
            }
            $file = $response[0];

            foreach ($file as $filename => $data) {
                $unique_id = $data['unique_id'];
                 $row = [array(
                    "acao" => "anexar",
                    "filename" => $caminho,
                    "data" => date("Y-m-d\TH:i:s"),
                    "fabrica" => $login_fabrica
                )];
            }

            if (in_array($login_fabrica, array(169,170,193))){
                $array = array("treinamento" => $treinamento);
                $obs   = json_encode($array);
            }

            $sql_verifica = "SELECT * 
                        FROM tbl_tdocs
                        WHERE fabrica = {$login_fabrica} 
                        AND contexto  = 'gera_certificado'
                        AND tdocs_id  = '$unique_id'";
            $res_verifica = pg_query($con,$sql_verifica);
            if (!pg_num_rows($res_verifica) > 0){
                $sql = "INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id) 
                    values('$unique_id', $login_fabrica, 'gera_certificado', 'ativo', '$obs', 'gera_certificado', $tecnico);";                
                $res = pg_query($con, $sql);   

                if ($isConvidado != false){

                    // gerando link do certificado 
                    $resposta         = $tdocsMirror->get($unique_id);
                    $link_certificado = $resposta["link"];
            
                    $titulo_email = "Certificado do treinamento - {$nome_treinamento} "; 
                    $msg_email    = "
                        Prezado, {$nome_tecnico} <br /><br />
                        Segue o <a href='$link_certificado'>Link de acesso</a> ao certificado referente ao treinamento {$nome_treinamento}. <br /><br /><br /><br />
                        <center>Obrigado pela participação!</center>
                    ";

                    if (!empty($email_tecnico)){
                        try {
                            $comunicatorMirror->post($email_tecnico, utf8_encode("$titulo_email"), utf8_encode("$msg_email"), "smtp@posvenda");
                        } catch (\Exception $e) {
                        }
                    }else{
                        $msg_erro["msg"][] = "Email com informações do $titulo_treinamento não enviado para o posto $nome_posto. Posto sem email cadastrado";
                    }
                }
            }
            $msg_erro = pg_last_error($con);

            if (strlen($msg_erro) > 0){
                exit(json_encode(array("error" => utf8_encode("Certificado Não Gerado"))));
            }else{
                if (strlen($returnLinkText) > 0){
                    exit(json_encode(array("ok" => $link_certificado)));
                }else{                                  
                    exit(json_encode(array("ok" => utf8_encode("Certificado Gerado com Sucesso"))));
                }
            }
            exit;
    }
}
?>