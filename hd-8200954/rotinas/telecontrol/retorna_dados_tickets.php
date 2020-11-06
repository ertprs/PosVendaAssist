<?php 

use Ticket\Ticket;
use Posvenda\Os;
use Ticket\Ticket_OS;

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../../funcoes.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
    include_once dirname(__FILE__) . '/../../fn_traducao.php';

    define('APP', 'Retorna Dados Tickets');
	define('ENV','devel');

	$logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Importa Dados Ticket")); // Titulo
    $logClass->adicionaEmail("lucas.carlos@telecontrol.com.br"); // email

	$log 		= "";
    $aplicativo = true;

    if ($argv[1]) {
        $os = $argv[1] ;
    }

	if ($argv[2]) {
        $login_fabrica = $argv[2] ;
        $fabrica = $argv[2] ;
    }

    function getPeca($dados, $fabrica){
        global $con; 

        $peca_referencia = explode("|", $dados['peca_referencia']);
        $servico_realizado = explode("|", $dados['servico_realizado']);

        $referencia_p = $peca_referencia[0];
        $descricao_p = $peca_referencia[1];
        $codigo_servico_p = $servico_realizado[0];
        $descricao_servico_p = $servico_realizado[1];

        $sqlPeca            = "SELECT peca FROM Tbl_peca WHERE referencia = '".$referencia_p."' and fabrica = $fabrica ";
        $resPeca  = pg_query($con, $sqlPeca);
        if(pg_num_rows($resPeca)>0){
            $peca = pg_fetch_result($resPeca, 0, 'peca');
        }

        $retorno['id'] = $peca;
        $retorno['referencia'] = $dados['peca_referencia']['referencia'];
        $retorno['servico_realizado'] = $codigo_servico_p;
        $retorno['qtde'] = $dados['quantidade']['value']; 

        return $retorno; 
    }
    function gravarAnexo($name, $uniqueId, $numOs, $fabrica, $typeId){

        global $con, $log; 

        $obs[0]['acao'] = "anexar";
        $obs[0]["filename"] = $name;
        $obs[0]["data"] = date("Y-m-d h:i:s");
        $obs[0]["fabrica"] = $fabrica;
        $obs[0]["descricao"] = "";
        $obs[0]["page"]   = "uploader.php";
        $obs[0]["source"] = "telecontrol-file-uploader";
        $obs[0]["typeId"] = $typeId;

        $obs = json_encode($obs);
        $sql = "INSERT INTO tbl_tdocs (tdocs_id, fabrica, contexto, situacao, referencia, referencia_id, obs) VALUES ('".$uniqueId."', ".$fabrica.", 'os', 'ativo', 'os', '".$numOs."', '$obs')";
        $resTdocs    = pg_query($con, $sql);

        if(strlen(pg_last_error($con))>0){        
            $log[] = "Falha ao gravar Anexo ";
        }
    }

    function getDadosOS($os, $fabrica){
        global $con; 
        $sql = "SELECT tipo_atendimento, consumidor_revenda FROM tbl_os where os = $os and fabrica = $fabrica";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            $dados['tipo_atendimento'] = pg_fetch_result($res, 0, 'tipo_atendimento');
            $dados['consumidor_revenda'] = pg_fetch_result($res, 0, 'consumidor_revenda');
        }

        return $dados;

    }

	$classTicket = new Ticket($fabrica);

    $dadosRetorno = $classTicket->getRetornoTicket("OS", $os); 

    if(isset($dadosRetorno['error'])){
        $log[] = $dadosRetorno['error'];
    }

    $dadosOS = getDadosOS($os, $fabrica);


    $_POST['os']['tipo_atendimento'] = $dadosOS['tipo_atendimento']; 
    $_POST['os']['consumidor_revenda'] = $dadosOS['consumidor_revenda'];

    foreach($dadosRetorno as $dados){

        $dadosTicket['ticket'] = $dados['ticket'];

        if($dados['contexto'] == "OS"){            
            if(strlen(trim($dados['reference_id']))>0){
                $numOs = $dados['reference_id'];     
 
                $camposApp['gravar'] = "Gravar";
                $dados['response'] = json_decode($dados['response'], true);         

                if(strlen(trim($dados['response_modificado'])) > 0){
                    $dados['response'] = json_decode($dados['response_modificado'], true);
                }
             
                foreach($dados['response'] as $linha){

                    foreach($linha['content'] as $campos){

                        if($campos['name'] == "defeito_reclamado" and strlen(trim($campos['value'])) > 0 ){
                            $camposApp['os']['defeito_reclamado'] = utf8_decode($campos['value']);
                        }
                        
                        if($campos['name'] == "horimetro" and strlen(trim($campos['value'])) > 0 ){
                            $camposApp['produto']['horimetro'] = $campos['value'];
                        }

                        if($campos['name'] == "observacao"){
                            $camposApp['os']['observacoes'] = utf8_decode($campos['value']);
                        }                        

                        $_POST['peca_reposicao'] = "true";
                        if($campos['name'] == "produto"){
                            $defeitoConstatadoMultiplo = 't';
                            $valores = json_decode($campos['valueArray'], true);

                            if(count(array_filter($valores))>0){
                                $defeito_constatado_arr = explode("|", $valores['defeito_constatado']);
                                $solucao_arr = explode("|", $valores['solucao']);

                                $defeitos_constatados_multiplos_key[] = $defeito_constatado_arr[0];
                                $solucao[] = $solucao_arr[0];    
                            }                                   
                        }
                    }


                    if($linha['name'] == "lista_basica"){
                        foreach($linha['content'] as $dpecas){
                            $dadosListaBasica = json_decode($dpecas['valueArray'], true);
                            $camposApp['produto_pecas'][] = getPeca($dadosListaBasica, $fabrica);
                        }
                        foreach($linha['deleted']['inputs'] as $deletedPecas){
                            $deletedPecas = json_decode($deletedPecas, true);
                            $deleted[] = getPeca($deletedPecas, $fabrica);
                        }


                    }

                    if($linha['name'] == "status"){
                        foreach($linha['content'] as $dadosStatus){
                            if($dadosStatus['status'] == 'checkin'){

                                $dadosTicket['checkin'] = $dadosStatus['dateTime'];
                            }
                            if($dadosStatus['status'] == 'checkout'){
                                $dadosTicket['checkout'] = $dadosStatus['dateTime'];
                            }
                            
                            
                        }
                    }

                    if($linha['name'] == 'adicionais'){
                        $obs = "";
                        foreach ($linha['content'] as $adicionais) {
                            $obs .= ucfirst($adicionais['name']).": R$".$adicionais['value']."<Br>";
                        }
                        $camposApp['os']['observacoes'] .= "<br> $obs ";
                    }

                    if($linha['name'] == 'anexos'){

                        $dadosAnexo = $linha['content'];
                        foreach($linha['content'] as $anexos){
                            $name = $anexos['name'];
                            $uniqueId  = $anexos['uniqueId'];

                            $camposAnexo[] = [
                                    'name' => $name,
                                    'uniqueId' => $uniqueId,
                                    'numOs' => $numOs,
                                    'fabrica' => $fabrica,
                                    'typeId' => 'anexos'
                            ];
                         }
                    }


                    if($linha['name'] == 'resumo'){                        
                    
                        $name = 'resumo.pdf';
                        $uniqueId  = $linha['content']['uniqueId'];

                        $camposAnexo[] = [
                                'name' => $name,
                                'uniqueId' => $uniqueId,
                                'numOs' => $numOs,
                                'fabrica' => $fabrica,
                                'typeId' => 'anexos'
                        ];
                    
                    }

                    if($linha['name'] == 'checklist'){
                        $name = 'checklist.pdf';
                        $uniqueId  = $linha['content']['uniqueId'];
                        $camposAnexo[] = [
                                'name' => $name,
                                'uniqueId' => $uniqueId,
                                'numOs' => $numOs,
                                'fabrica' => $fabrica,
                                'typeId' => 'anexos'
                        ];
                    }


                    if($linha['name'] == 'assinatura'){
                        foreach($linha['content'] as $anexos){
                            $name = $anexos['name'];
                            $uniqueId  = $anexos['uniqueId'];

                            $camposAnexo[] = [
                                    'name' => $name,
                                    'uniqueId' => $uniqueId,
                                    'numOs' => $numOs,
                                    'fabrica' => $fabrica,
                                    'typeId' => 'assinatura'
                            ];
                        }
                    }

                    if($linha['name'] == 'assinatura_tecnico'){
                        foreach($linha['content'] as $anexos){
                            $name = $anexos['name'];
                            $uniqueId  = $anexos['uniqueId'];

                            $camposAnexo[] = [
                                    'name' => $name,
                                    'uniqueId' => $uniqueId,
                                    'numOs' => $numOs,
                                    'fabrica' => $fabrica,
                                    'typeId' => 'assinatura_tecnico'
                            ];                            
                        }
                    }

                    $camposApp['produto']['defeitos_constatados_multiplos'] = implode(",", $defeitos_constatados_multiplos_key);
                    $camposApp['produto']['solucoes_multiplos'] = implode(",", $solucao);
                }

                $classOs = new OS($fabrica); 
                $classOs->setOs($numOs);

                if($login_fabrica == 166){
                    $valida_anexo_boxuploader = "valida_anexo_boxuploader"; 
                }



                require_once dirname(__FILE__). "/../../os_cadastro_unico/fabricas/os.php";

                if(isset($msg_erro['msg']['campo_obrigatorio'])){
                    
                    foreach($msg_erro['campos'] as $key =>  $cmp){
                        $texto = $cmp;
                        preg_match('#\[(.*)\]#',$texto, $match); 
                        if($key > 0 ){
                            $campo .= ", ".ucwords(str_replace("_", " ", $match[1]));
                        }else{
                            $campo = ucwords(str_replace("_", " ", $match[1]));
                        }
                    }
                }                

                $log[] = $msg_erro['msg'];
            }
        }


        $log = array_filter($log);
        if(count(array_filter($msg_erro['msg'])) == 0 and count($log) ==0 ){

            foreach($camposAnexo as $anex){
                if(strlen(trim($anex['uniqueId']))==0){
                    continue;
                }
                gravarAnexo($anex['name'], $anex['uniqueId'], $anex['numOs'], $anex['fabrica'], $anex['typeId'] );
            }
            $log_email .= "O.S ". $numOs ." integrada com sucesso. <br><br>";
            $classTicket->setIntegrado($dados['ticket'], $numOs, $dadosTicket);
            $classTicket->removePecaOS($deleted, $numOs);
            echo json_encode(array('sucesso' => "O.S ". $numOs ." integrada com sucesso. "));
        }else{
            $log_email .= "Falha na integração O.S". $numOs ." - ". implode(", ", $msg_erro['msg']). "<br><br>";
            echo json_encode(array('erro' => "\n Falha na integracao O.S $numOs - ".utf8_encode(implode("; \n\n", $msg_erro['msg']) ) .": ". $campo ) ) ;
        }
    }

    if(!empty($log_email) and !$argv[1]){

        $logClass->adicionaLog($log_email);

        if($logClass->enviaEmails() == "200"){
          echo "Log enviado com Sucesso!";
        }else{
          echo $logClass->enviaEmails();
        }

        $fp = fopen("/tmp/retorna-ticket-".date("dmYH").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y H:i:s") . "\n");
        fwrite($fp, $log_email . "\n \n");
        fclose($fp);

    }
	
} catch (Exception $e) {
    echo $e->getMessage();
}



?>

