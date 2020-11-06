<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_usuario.php';
include 'funcoes.php';

$url = "https://api2.telecontrol.com.br";
$companyHash = $parametros_adicionais_posto['company_hash'];

if($_POST['salvarElementos'] == true){

    $response_modificado    = $_POST['response_modificado'];
    $reference_id           = $_POST['reference_id'];
    $ticket                 = $_POST['ticket'];
    $dados['contexto']          = "OS";
    $dados['response_modificado'] = $response_modificado; 
    $dados['referenceId']       = $reference_id; 
    $dados['ticket']            = $ticket;

    $dados = json_encode($dados);   

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "$url/ticket-checkin/ticket-reagendar",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => $dados,
      CURLOPT_HTTPHEADER => array(
        "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
        "access-env: PRODUCTION",
        "cache-control: no-cache",
        "content-type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo $err;
    } else {
        $retorno = json_decode($response, true);
        if(isset($retorno['message'])){
            echo "Ticket atualizado com sucesso";
        }else{
            echo "Falha ao atualizar ticket - ".$retorno['exception'];
        }
    }
    exit;
}

if($_POST['adicionar'] == true){
    $itens      = $_POST['itens'];
    $ticket     = $_POST['ticket'];
    $response   = $_POST['response'];
    $responsemodificado   = $_POST['responsemodificado'];

    if(strlen(trim($responsemodificado))>0){
        $response = $responsemodificado;
    }
    $response = json_decode($response, true);

    $contexto   = $_POST['contexto'];
    $regiao     = strtolower($_POST["regiao"]);

    foreach($response as $chave => $value){
        if($value['name'] == $regiao){
            foreach($value['content'] as $valores){                
                //$valores['name'] = $contexto;
                if($valores['name'] == $contexto){
                    $valores['value'] = $itens;
                    $content[] = $valores;
                }else{
                    $content[] = $valores;
                }                
            }
            $response[$chave]['content'] = $content;  
        }        
    }

    if($contexto == 'produto'){
        foreach($itens['def'] as $chave => $valor){
            $valores = explode("-", $itens['def']["$chave"]);
            $dados['defeito_constatado']['key'] = $valores[0];
            $dados['defeito_constatado']['value'] = $valores[1];

            $valores_solucao = explode("-", $itens['sol']["$chave"]);
            $dados['solucao']['key'] = $valores_solucao[0];
            $dados['solucao']['value'] = $valores_solucao[1];
            
            $produto['name'] = "produto";
            $produto['valueArray'] = json_encode($dados);

            $arrProduto[] = $produto;        
        }    
    }elseif($contexto == 'lista_basica'){
        foreach($itens['peca'] as $chave => $valor){
            $referencia = explode("|", $itens['peca'][$chave]);
            $qtde = explode("|", $itens['qtde'][$chave]);
            $servico = explode("|", $itens['servico'][$chave]);

            $value['peca_referencia']['referencia'] = $referencia[0];
            $value['peca_referencia']['descricao'] = $referencia[1];
            $value['quantidade']['value'] = $qtde[0];
            $value['servico_realizado']['key'] = $servico[0];
            $value['servico_realizado']['value'] = $servico[1];

            $lista_basica['name'] = 'lista_basica';
            $lista_basica['valueArray']= json_encode($value);

            $arrListaBasica[] = $lista_basica;
        }
    }

    foreach($response as $chave => $itensResponse){
        if($itensResponse['name'] == 'produto' and count(array_filter($arrProduto))){
            $response[$chave]['content'] = $arrProduto;
            //array_push($response[$chave]['content'], array('name' => "horimetro", 'value' => $horimetro));
        }elseif($itensResponse['name'] == 'lista_basica' and count(array_filter($arrListaBasica))){
            $response[$chave]['content'] = $arrListaBasica; 
        }        
    }
    echo json_encode($response);    
exit;
}

if($_POST['carregaElementos'] == true){
    $ticket = $_POST["ticket"];
    $reference_id = $_POST['reference_id'];
    $contexto = $_POST['contexto'];
    $response = json_decode($_POST['dados'], true);
    $contex = '"'.$contexto.'"';

    foreach($response as $info){
        if($info['name'] == "produto" and $contexto == 'produto'){
            $count = 0;
            foreach($info['content'] as $content){
                if($content['name'] == 'horimeto'){
                    $horimeto = $content['value'];
                }
                if($content['name'] == 'produto'){
                    $jsonlinha = json_decode($content['valueArray'],true);



                    $linha .= "<tr class='linhatr_".$jsonlinha['defeito_constatado']['key']."_".$count."'><td  class='constatado' data-key=".$jsonlinha['defeito_constatado']['key'].">".utf8_decode($jsonlinha['defeito_constatado']['value'])."</td><td class='solucao' data-key=".$jsonlinha['solucao']['key'].">".utf8_decode($jsonlinha['solucao']['value'])."</td><td><button type='button' class='btn btn-danger' data-key='".$jsonlinha['defeito_constatado']['key']."' data-contexto='".$contexto."' onclick='excluir(".$jsonlinha['defeito_constatado']['key'].", $contex, $count)' >Excluir</button></td></tr>";
                }
                $count++;
            }
        }elseif($info['name'] == "lista_basica" and $contexto == 'lista_basica'){
            $count = 0;
            foreach($info['content'] as $content){                
                if($content['name'] == 'lista_basica'){
                    $jsonlinha = json_decode($content['valueArray'],true);
                    $reference = '"'.str_replace(".", "", $jsonlinha['peca_referencia']['referencia']).'"';
                    $referenceln = str_replace(".", "", $jsonlinha['peca_referencia']['referencia']);

                    $linha .= "<tr class='linhatr_".$referenceln."_".$count."'>
                                <td  class='peca_referencia' data-referencia=".utf8_decode($jsonlinha['peca_referencia']['descricao'])." data-key=".$jsonlinha['peca_referencia']['referencia'].">".$jsonlinha['peca_referencia']['referencia'].' - '. utf8_decode($jsonlinha['peca_referencia']['descricao']). "</td>

                                <td class='quantidade tac' data-key=".$jsonlinha['quantidade']['value'].">".$jsonlinha['quantidade']['value']."</td>

                                <td class='servico_realizado tac' data-key=".$jsonlinha['servico_realizado']['key'].">".utf8_decode($jsonlinha['servico_realizado']['value'])."</td>

                                <td><button type='button' class='btn btn-danger' onclick='excluir($reference , $contex, $count)'>Excluir</button></td></tr>";
                }
            $count++;
            }
        }
    }

    $sql = "SELECT tbl_os.produto, tbl_os.fabrica, tbl_produto.familia from tbl_os join tbl_produto on tbl_produto.produto = tbl_os.produto where os = $reference_id";
    $res = pg_query($con, $sql); 
    if(pg_num_rows($res)>0){
        $produto = pg_fetch_result($res, 0, 'produto');
        $familia = pg_fetch_result($res, 0, 'familia');
        $fabrica = pg_fetch_result($res, 0, 'fabrica');
    }

    if($contexto == 'produto'){
    $sql_defeito = "SELECT 
                DISTINCT tbl_defeito_constatado.defeito_constatado, 
                tbl_defeito_constatado.descricao                    
            FROM tbl_diagnostico 
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = ".$fabrica ." 
            JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = ".$fabrica ." 
            JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = ".$fabrica ." 
            WHERE tbl_diagnostico.fabrica = ".$fabrica ." 
            AND tbl_diagnostico.familia = ".$familia." 
            AND tbl_diagnostico.ativo IS TRUE 
            ORDER BY tbl_defeito_constatado.descricao ASC"; 
    $res_defeito = pg_query($con, $sql_defeito);

    for($def=0; $def<pg_num_rows($res_defeito); $def++){
        $defeito_constatado = pg_fetch_result($res_defeito, $def, 'defeito_constatado');
        $descricao          = pg_fetch_result($res_defeito, $def, 'descricao');

        $opt_constatado .= "<option value='$defeito_constatado|$descricao'>$descricao</option>";
    }        

    $sql_solucao = "SELECT solucao, descricao FROM tbl_solucao WHERE fabrica = ".$fabrica;
    $res_solucao = pg_query($con, $sql_solucao);
    for($sol=0; $sol<pg_num_rows($res_solucao); $sol++){
        $solucao    = pg_fetch_result($res_solucao, $sol, 'solucao');
        $descricao  = pg_fetch_result($res_solucao, $sol, 'descricao');

        $opt_solucao .= "<option value='$solucao|$descricao'>$descricao</option>";
    } 

    echo "<div class='titulo_coluna'>Produto</div>";
    echo "<form >";
      // echo "<div class='form-group line'>";
      //   echo "<label for='defeito_constatado'><b>Horímetro</b></label>";
      //   echo "<input type='text' class='horimetro' name='horimetro' value='$value_horimetro'>";
      // echo "</div>";
      echo "<div class='form-group line'>";
        echo "<label for='defeito_constatado'><b>Defeito Constatado</b></label>";
        echo "<select name='defeito_constatado' id='defeito_constatado'> 
                <option value=''>Defeito Constatado</option>
                $opt_constatado
            </select><br>
            <span class='msg_erro msg_constatado'></span>";
      echo "</div>";
      echo "<div class='form-group line'>";
        echo "<label for='solucao'><b>Solução</b></label>";
        echo "<select name='solucao' id='solucao'> 
                <option value=''>Solução</option>
                $opt_solucao
            </select><br>
            <span class='msg_erro msg_solucao'></span>";
      echo "</div>";
      echo "<div class='line'>";
      echo "<button type='button' class='btn btn-primary adicionar_defeito' onclick='adicionar()'>Adicionar</button>";
      echo "<input type='hidden' name='dados_defeito_solucao' id='dados_defeito_solucao' value=''>";
      echo "<input type='hidden' name='ticket' id='ticket' value='$ticket'>";
      echo "</div>";

    echo "</form>";

    echo "<table id='constatado_solucao' class='table table-striped' width='100%'>";
        echo "<tr  class='titulo_coluna'>";
            echo "<th class='titulo_coluna'>Defeito Constatado</th>";
            echo "<th class='titulo_coluna'>Solução</th>";
            echo "<th class='titulo_coluna'></th>";
        echo "</tr>";
        echo $linha;
    echo "</table>";

}elseif($contexto == 'lista_basica'){

    $sqlPeca = " SELECT tbl_lista_basica.qtde as qtde_maxima,
                        tbl_peca.referencia, 
                        tbl_peca.descricao 
                FROM tbl_lista_basica 
                join tbl_peca on tbl_peca.peca = tbl_lista_basica.peca
                WHERE tbl_lista_basica.produto = $produto 
                and tbl_lista_basica.fabrica = $fabrica ";
    $resPeca = pg_query($con, $sqlPeca);
    for($i=0; $i<pg_num_rows($resPeca); $i++){
        $referencia = pg_fetch_result($resPeca, $i, 'referencia');
        $descricao = pg_fetch_result($resPeca, $i, 'descricao');

        $opt_peca .= "<option value='$referencia|$descricao'>$descricao</option>";

    }

    $sqlServico = "SELECT servico_realizado, descricao from tbl_servico_realizado WHERE fabrica = ".$fabrica;
    $resServico = pg_query($con, $sqlServico);
    for($a=0; $a<pg_num_rows($resServico); $a++){
        $servico    = pg_fetch_result($resServico, $a, 'servico_realizado');
        $descricao  = pg_fetch_result($resServico, $a, 'descricao');

        $opt_servico .= "<option value='$servico|$descricao'>$descricao</option>";
    }


    echo "<div class='titulo_coluna'>Aba Lista Básica</div>";
    echo "<form >";
      echo "<div class='form-group line'>";
        echo "<label for='defeito_constatado'><b>Peça</b></label>";
        echo "<select name='peca' id='peca'> 
                <option value=''>Peça</option>
                $opt_peca
            </select><br>
            <span class='msg_erro msg_peca'></span>";
      echo "</div>";
      
      echo "<div class='form-group line'>";
        echo "<label for='defeito_constatado'><b>Quantidade</b></label>";
        echo "<input type='text' id='qtde' name='qtde' value=''> 
        <br> 
        <span class='msg_erro msg_qtde'></span>";
      echo "</div>";

      echo "<div class='form-group line'>";
        echo "<label for='solucao'><b>Serviço</b></label>";
        echo "<select name='servico' id='servico'> 
                <option value=''>Serviço</option>
                $opt_servico
            </select><br>
            <span class='msg_erro msg_servico'></span>";
      echo "</div>";
      echo "<div class='line'>";
      echo "<button type='button' class='btn btn-primary adicionar_defeito' onclick='adicionarListaBasica()'>Adicionar</button>";
      echo "<input type='hidden' name='dados_defeito_solucao' id='dados_defeito_solucao' value=''>";
      echo "<input type='hidden' name='ticket' id='ticket' value='$ticket'>";
      echo "</div>";

    echo "</form>";

    echo "<table id='constatado_solucao' class='table table-striped' width='100%'>";
        echo "<tr  class='titulo_coluna'>";
            echo "<th class='titulo_coluna'>Peça</th>";
            echo "<th class='titulo_coluna'>Qtde</th>";
            echo "<th class='titulo_coluna'>Serviço</th>";
            echo "<th class='titulo_coluna'></th>";
        echo "</tr>";
        echo $linha;
    echo "</table>";
}

exit;
}


if($_POST['monta_modal']== true){
    $dados = $_POST['dados'];
    $dados = json_decode($dados, true); 

    $reference_id   = $_POST['reference_id'];
    $ticket         = $_POST['ticket'];

    $sql = "SELECT tbl_peca.referencia, tbl_os_item.qtde, tbl_peca.descricao, tbl_servico_realizado.descricao as servico_realizado_descricao, tbl_servico_realizado.servico_realizado 
             FROM tbl_os_produto 
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
            JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
            join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
            where tbl_os_produto.os = $reference_id ";   
    $query              = pg_query($con, $sql);
    for($p = 0; $p<pg_num_rows($query); $p++){  
        $referencia = pg_fetch_result($query, $p, 'referencia');
        $descricao = pg_fetch_result($query, $p, 'descricao');
        $qtde = pg_fetch_result($query, $p, 'qtde');
        $servico_realizado_descricao = pg_fetch_result($query, $p, 'servico_realizado_descricao');

        $pecas[$referencia]['referencia'] = $referencia;
        $pecas[$referencia]['descricao'] = $descricao;
        $pecas[$referencia]['qtde'] = $qtde;
        $pecas[$referencia]['servico_realizado_descricao'] = utf8_encode($servico_realizado_descricao);
    }

    $sqlConstatado = "SELECT tbl_os_defeito_reclamado_constatado.os, tbl_defeito_constatado.defeito_constatado,  tbl_defeito_constatado.descricao as defeito_constatado_descricao, tbl_solucao.solucao, tbl_solucao.descricao as solucao_descricao from tbl_os_defeito_reclamado_constatado 
        left join tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado 
        left join tbl_solucao on tbl_solucao.solucao = tbl_os_defeito_reclamado_constatado.solucao 
        where tbl_os_defeito_reclamado_constatado.os = $reference_id ";
    $resConstatado = pg_query($con, $sqlConstatado);
    for($c = 0; $c<pg_num_rows($resConstatado); $c++){
        $defeito_constatado = pg_fetch_result($resConstatado, $c, 'defeito_constatado');
        $defeito_constatado_descricao = pg_fetch_result($resConstatado, $c, 'defeito_constatado_descricao');
        $solucao = pg_fetch_result($resConstatado, $c, 'solucao');
        $solucao_descricao = pg_fetch_result($resConstatado, $c, 'solucao_descricao');

        if(strlen($defeito_constatado)>0){
            //$defConst[$defeito_constatado]['defeito_constatado']  = $defeito_constatado;
            $defConst[$defeito_constatado]['defeito_constatado']  = utf8_encode($defeito_constatado_descricao);
        }else{
           // $defConst[$solucao]['solucao']  = $solucao;
            $defConst[$solucao]['solucao']  = utf8_encode($solucao_descricao);
        }
    }

    foreach($dados as $linha){
        $nome = ucwords(str_replace("_", " ",$linha['name']));
        if(empty($nome) OR in_array($nome, ['Status', 'External Id', 'Checklist', "Resumo", "Checklist Revisao", "Lista Basica"])){
            continue;
        }

        if($nome == 'Anexos'){
            $retornoAnexo = "<div class='titulo_coluna' ><b> $nome </b></div>";
            foreach($linha['content'] as $dadosAnexos){
                //print_r($dadosAnexos);
                $retornoAnexo .= " <div class='anexo'><img src='http://api2.telecontrol.com.br/tdocs/document/id/".$dadosAnexos['uniqueId']."' width='110'> </div>";
            }
        }elseif($nome == "Assinatura"){
            $retornoAss = "<br> <div class='clear titulo_coluna' ><b> $nome </b></div>";
            $retornoAss .= " <div class='anexo'><img src='http://api2.telecontrol.com.br/tdocs/document/id/".$linha['content'][0]['uniqueId']."' width='110'> </div>";
        }elseif($nome == "Assinatura Tecnico"){
            $retornoAss .= "<br> <div class='clear titulo_coluna' ><b> $nome </b></div>";
            $retornoAss .= " <div class='anexo'><img src='http://api2.telecontrol.com.br/tdocs/document/id/".$linha['content'][0]['uniqueId']."' width='110'> </div>";
        }else{
            $retorno .= "<div class='titulo_coluna' ><b> $nome </b></div>";    
        }

        foreach($linha['content'] as $campos){
            if(isset($campos['value'])){       
                $size = 'style="width:400px"';
                $contex = '"'.$campos['name'].'"';

                if(in_array($campos['name'], ['horimetro', 'pedagio', 'alimentacao'])){
                    $size = 'style="width:70px"';
                }

                if($campos['name'] == 'observacao'){
                    $retorno .=  "<div class='texto line'><b> ". ucwords(str_replace(array("_"), " ", $campos['name'] )) .": </b>  <textarea style='width:450px' rows='3' class='editavel obstextarea'  name='".$campos['name']."' data-regiao='observacao' readonly='true' onBlur='obstextarea()'> ".      utf8_decode($campos['value'])."</textarea>";
                }else{
                    $retorno .=  "<div class='texto line'><b> ". ucwords(str_replace(array("_"), " ", $campos['name'] )) .": </b>  <input $size type='text' class='editavel' name='".$campos['name']."' value='".      utf8_decode($campos['value'])."'data-regiao='$nome' readonly='true' onBlur='responseModificado(this.value, ".$contex.")'> </div>";
                }                
            }
            if(isset($campos['valueArray'])){
                $valueArray = json_decode($campos['valueArray'], true);
                if($nome == 'Lista Basica'){

                    //print_r($valueArray); 

                    foreach($valueArray as $key => $ljson){

                      //  echo $key; 

                        /*if(isset($valueArray[$key]['referencia'])){                        
                            $pecas[$valueArray[$key]['referencia']]['referencia'] = $valueArray[$key]['referencia'];
                            $pecas[$valueArray[$key]['referencia']]['descricao'] = $valueArray[$key]['descricao'];
                            $pecas[$valueArray[$key]['referencia']]['qtde'] = $valueArray['quantidade']['value'];
                            $pecas[$valueArray[$key]['referencia']]['servico_realizado_descricao'] = utf8_decode($valueArray['servico_realizado']['value']);
                        }*/                        


                       /* $pecas[$key] = $valueArray[$key];
                        echo "<pre>";
                        print_r($pecas); 
                        echo "</pre>";*/
                    }                    

                }else{
                    foreach($valueArray as $key => $ljson){
                        $key = ucwords(str_replace(array("_"), " ", $key));                    
                        //$retorno .=  "<div class='line'> <b>".$key."</b> ".utf8_decode($ljson['value']). "</div>";  

                        $ljson = str_replace("|", " - ", $ljson);

                        if($key == 'Defeito Constatado'){
                            $defConst[$ljson['key']]['defeito_constatado']  = $ljson;
                        }else{
                            $defConst[$ljson['key']]['solucao']  = $ljson;
                        }
                    }
                    //$retorno .=  "<div class='line'> - </div>";
                }
            }
            /*if(isset($campos['uniqueId'])){
                $retornoAnexo .= " <div class='anexo'><img src='http://api2.telecontrol.com.br/tdocs/document/id/".$campos['uniqueId']."' width='110'> </div>";
            }*/
            $retorno .= "<input type='hidden' class='dadosAlterar' name='dadosAlterar' value='$dadosAlterar'>";
        }
        if($nome == 'Produto' OR $nome == 'Lista Basica'){

            if($nome == 'Lista Basica'){
                /*foreach($pecas as $key => $ljson){
                        $retorno .=  "<div class='line'> <b>Peça</b> ".$pecas[$key]. "</div>";  
                        $retorno .=  "<div class='line'> <b>Quantidade</b> ".utf8_decode($pecas[$key]). "</div>";

                        $retorno .=  "<div class='line'> <b>Servico Realizado</b> ".utf8_decode($pecas[$key]). "</div>";
                        $retorno .=  "<div class='line'> - </div>";
                }*/
            }else{
                foreach($defConst as $key => $ljson){
                    $key = key($ljson);         

                    $retorno .=  "<div class='line'> <b>".ucwords(str_replace(array("_"), " ", $key))."</b> ".utf8_decode($ljson[$key]). "</div>";
                    $retorno .=  "<div class='line'> - </div>";
                }
            }


                    //$dadosAlterar = json_encode($dadosAlterar);
                    //$dadosAlterar = "'$dadosAlterar'";
            $contexto = strtolower(str_replace(" ", "_", $nome));
            $contexto = "'$contexto'";                    
            //$retorno .= '<div class="line"> <button class="btn btn-primary add" style="display:none" onclick=carregaElementos('.$ticket.','.$reference_id.','.$contexto.')>Adicionar/Remover</button></div>';
        }
    }
    $retorno .= $retornoAnexo . $retornoAss ;
    echo $retorno;

exit;
}

if($_POST['aprova_ticket'] == true){

    $reference_id   = $_POST['reference_id'];
    $ticket_id      = $_POST["ticket_id"];
    $msg_erro = "";

    $msg_rotina = exec('php rotinas/telecontrol/retorna_dados_tickets.php '.$reference_id. ' ' . $login_fabrica);

    $msg_rotina = json_decode($msg_rotina, true);

    if(isset($msg_rotina['erro'])){
        $msg_erro = $msg_rotina['erro'];
    }    
    if(strlen(trim($reference_id))==0){
        $msg_erro .= "Informe o referencia do ticket ";
    }

    if(strlen(trim($ticket_id))==0){
        $msg_erro .= "Informe o ticket";
    }

    if(strlen(trim($msg_erro))==0){
        $dados['reference_id'] = $reference_id;
        $dados['ticket_id'] = $ticket_id;
        $dados['admin_aprova'] = $login_posto;

        $dados = json_encode($dados);

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "$url/ticket-checkin/ticket-aprovacao",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => "$dados",
          CURLOPT_HTTPHEADER => array(
            "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
            "access-env: PRODUCTION",
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
           $retorno['erro'] = $err;
        } else {
          $retorno = json_decode($response, true);
        }

        if(strlen($retorno['exception'])>0){
            $retorno['erro'] = $retorno['exception'];
        }
    }else{
        $retorno['erro'] = $msg_erro;
    }

    echo json_encode($retorno);

exit;
}


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "$url/ticket-checkin/ticket-finalizado/companyHash/$companyHash",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_HTTPHEADER => array(
    "access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
    "access-env: PRODUCTION",
    "cache-control: no-cache",
    "content-type: application/json"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {    
    $dados_tickets = json_decode($response, true);    
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

$layout_menu = "callcenter";
$title = "APROVAÇÃO DE TICKETS";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
<style>
    .anexo{
        float:left;
    }
    .titulo{
        font-weight: 16px;
    }
    .modal{
        width: 70%;
        margin-left: -35% !important;

    }
    .titulo_coluna{
        line-height: 25px;
        font-size: 14px;
        margin-top: 20px;
        margin-bottom: 7px;

    }
    .line{
        text-align: center;
    }
    .msg_erro{
        color:red;
        font-weight: bold;
    }
    .clear{
        clear:both;
    }
    
</style>

<script type="text/javascript">
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();
        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $('#myModal').on('shown.bs.modal', function () {
          $('#myInput').trigger('focus')
        });

        $('.editar').on('click', function(){
          $(".editavel").attr('readonly', false);
          $(".add").show();
        });

	});

    function obstextarea(){
        var texto = $(".obstextarea").val();
        var contexto = $(".obstextarea").data('regiao');
        responseModificado(texto, contexto);
    }

    function adicionarListaBasica(){
        var peca = $("#peca").val();
        var qtde = $("#qtde").val();
        var servico = $("#servico").val(); 
        var contexto = 'lista_basica';       

        var qtdelinha = $("tr[class^='linhatr_']").length;

        $(".msg_erro").text('');

        if(peca.length == 0){
            $(".msg_peca").text("Informe a peça");
            return false;
        }
        if(qtde.length == 0){
            $(".msg_qtde").text("Informe a quantidade");
            return false;
        }
        if(servico.length == 0){
            $(".msg_servico").text("Informe a serviço");
            return false;
        }        

        peca = peca.split("|");
        servico = servico.split("|");
        $('#constatado_solucao').append('<tr class="linhatr_'+peca[0].replace(".", "")+'_'+qtdelinha+'"><td  class="peca_referencia" data-referencia='+peca[1]+' data-key='+peca[0]+'>'+peca[0]+' - '+peca[1]+'</td><td class="quantidade  tac" data-key='+qtde+'>'+qtde+'</td><td class="servico_realizado tac" data-key='+servico[0]+'>'+servico[1]+'</td><td><button type="button" class="btn btn-danger" onclick="excluir('+"'"+peca[0].replace(".", "")+"'"+', '+"'"+contexto+"'"+' ,'+qtdelinha+')">Excluir</button></td></tr>');

        var arraypeca       = [];
        var arrayservico    = [];
        var arrayqtde       = [];
    
        $("tr[class^='linhatr_']").each(function(index, elemento){
            arraypeca.push($(elemento).find('.peca_referencia').data('key')+'|'+$(elemento).find('.peca_referencia').data('referencia'));
            arrayqtde.push($(elemento).find('.quantidade').data('key')+'|'+$(elemento).find('.quantidade').text());
            arrayservico.push($(elemento).find('.servico_realizado').data('key')+'|'+$(elemento).find('.servico_realizado').text());
        });
        var element = {peca: arraypeca, qtde:arrayqtde, servico:arrayservico};
        responseModificado(element, contexto);
    }

    function adicionar(){
        var defeito = $("#defeito_constatado").val();
        var solucao = $("#solucao").val();   
        var contexto = 'produto';

        var qtde = $("tr[class^='linhatr_']").length;

        $(".msg_erro").text('');

        if(defeito.length == 0){
            $(".msg_constatado").text("Informe o defeito constatado");
            return false;
        }

        if(solucao.length == 0){
            $(".msg_solucao").text("Informe a solução");
            return false;
        }

        defeito = defeito.split("|");
        solucao = solucao.split("|");
        $('#constatado_solucao').append('<tr class="linhatr_'+defeito[0]+'_'+qtde+'"><td  class="constatado" data-key='+defeito[0]+'>'+defeito[1]+'</td><td class="solucao" data-key='+solucao[0]+'>'+solucao[1]+'</td><td><button type="button" class="btn btn-danger" onclick="excluir('+defeito[0]+', '+"'"+contexto+"'"+' ,'+qtde+')">Excluir</button></td></tr>');
        
        var arraydef = [];
        var arraysol = [];
    
        $("tr[class^='linhatr_']").each(function(index, elemento){
            arraydef.push($(elemento).find('.constatado').data('key')+'-'+$(elemento).find('.constatado').text());
            arraysol.push($(elemento).find('.solucao').data('key')+'-'+$(elemento).find('.solucao').text());
        });
        var element = {def: arraydef, sol:arraysol};
        responseModificado(element, contexto);
    }

    function responseModificado(element, contexto){
        var dados   = $("#dados_defeito_solucao").val();
        var ticket  = $(".valor_ticket").val();
        //var horimetro = $(".horimetro").val();
        var response = $("#ticket_"+ticket).val();
        var response_modificado = $(".response_modificado").val();
        var regiao  = $("[name='"+contexto+"']").data('regiao');

        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            data:{"adicionar": true, itens: element, ticket: ticket, response: response, contexto:contexto, regiao:regiao, responsemodificado: response_modificado },
            type: 'POST',
            complete: function(data) {
                data = data.responseText;
                $(".response_modificado").val(data);
                $(".salvar").show();
            }
        });     
    }
    function salvar(){
        var ticket = $(".valor_ticket").val();
        var response_modificado = $(".response_modificado").val();
        var reference_id    = $(".valor_reference_id").val();
        //$("#ticket_"+ticket).val(response_modificado);

        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            data:{"salvarElementos": true, ticket:ticket, response_modificado: response_modificado, reference_id:reference_id },
            type: 'POST',
            complete: function(data) {
                data = data.responseText;
                $(".salvar").hide();
                alert(data);
                //modal(ticket, reference_id);
            }
        });
    }

    function excluir(key, contexto, pos){
        $(".linhatr_"+key+'_'+pos).remove();

        var arraydef = [];
        var arraysol = [];

        $("tr[class^='linhatr_']").each(function(index, elemento){
            arraydef.push($(elemento).find('.constatado').data('key')+'-'+$(elemento).find('.constatado').text());
            arraysol.push($(elemento).find('.solucao').data('key')+'-'+$(elemento).find('.solucao').text());
        });
        var element = {def: arraydef, sol:arraysol};
        responseModificado(element, contexto);
    }

    function voltar(){
        var response_modificado        = $('.response_modificado').val();
        var ticket          = $(".valor_ticket").val();
        var reference_id    = $(".valor_reference_id").val();
        
        if(response_modificado.length == 0){
            modal(ticket, reference_id);
        }else{
            $("#ticket_"+ticket).val(response_modificado);
            $(".salvar").show();
            modal(ticket, reference_id);            
        }
    }
    
    
    function carregaElementos(ticket, reference_id, contexto){
        $(".voltar").show();
        $(".editar").hide();
        $(".modal-body"). text("Carregou elemento"+ reference_id +" "+ticket + " "+ contexto);
        var dados = $("#ticket_"+ticket).val();

        
        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            data:{"carregaElementos": true, contexto:contexto, dados: dados, reference_id:reference_id, ticket:ticket},
            type: 'POST',
            complete: function(data) {
                data = data.responseText;
                $(".modal-body"). text("");
                $(".modal-body").append(data);
            }
        });
    }    

    function modal(ticket, reference_id){

        var dados = $("#ticket_"+ticket).val();
        $(".valor_ticket").val(ticket);
        $(".valor_reference_id").val(reference_id);
        $(".editavel").attr('readonly', true);
        $(".add").hide();
        $(".voltar").hide();
        $(".editar").show();

        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            data:{"monta_modal": true, dados: dados, reference_id: reference_id, ticket: ticket},
            type: 'POST',
            complete: function(data) {
                data = data.responseText;
                $(".modal-body"). text("");
                $(".modal-body").append(data);
            }
        });
    }


    function aprova_ticket(ticket, reference_id){
        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            data:{"aprova_ticket": true, ticket_id: ticket, reference_id, reference_id},
            type: 'POST',
            beforeSend: function () {
                    $(".loading_"+ticket).show();
                    $(".aprova_"+ticket).hide();
                },
            complete: function(data) {
                data = data.responseText;
                data = $.parseJSON(data);                
                if(data.erro){
                    alert("Falha ao Aprovar Ticket \n"+ data.erro);
                    $(".loading_"+ticket).hide();
                    $(".aprova_"+ticket).show();
                }else{
                    alert(data.message);
                    $(".linha_"+ticket).remove();
                }
            }
        });
    }

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<!-- <script type="text/javascript" src="js/grafico/highcharts.js"></script> -->
<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>


<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Checkout Ticket</h5>
      </div>
      <div class="modal-body">

      </div>
      <div class="modal-footer">
        <input type="hidden" name="valor_ticket" class="valor_ticket" value="" >
        <input type="hidden" name="valor_reference_id" class="valor_reference_id" value="" >
        
        <input type="hidden" name="response_modificado" class="response_modificado" value="" >
        <button type="button" class="btn btn-success salvar" style="display: none" onclick="salvar()">Salvar</button> 
        <button type="button" class="btn btn-warning voltar" style="display: none" onclick="voltar()">Voltar</button> 
        <button type="button" class="btn btn-primary editar" >Editar</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<?php
    foreach($dados_tickets as $chave => $campos){
        if($campos['contexto'] == "OS"){

            $join_tecnico_agenda = '';
            $join_tecnico = '';

            if (in_array($login_fabrica, [148])) {
                /* HD-7074643 */
                $join_tecnico_agenda = "JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = tbl_os.fabrica";
                $join_tecnico = "JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico AND tbl_tecnico.fabrica = tbl_tecnico_agenda.fabrica";
            }

            $sql = "SELECT tbl_os.os, tbl_tipo_atendimento.descricao, consumidor_nome, consumidor_cidade, tbl_os.fabrica, (SELECT tbl_tecnico.nome 
                        FROM tbl_tecnico_agenda 
                        JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico 
                        WHERE tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = $login_fabrica
                        ORDER BY tecnico_agenda DESC LIMIT 1) AS nome_tecnico,
                        (SELECT TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY HH24:MI')
                        FROM tbl_tecnico_agenda
                        JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico 
                        WHERE tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = $login_fabrica
                        ORDER BY tecnico_agenda DESC LIMIT 1) AS data_agendameto 
                    FROM tbl_os 
                    JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                    WHERE tbl_os.os = ".$campos['reference_id']. "
                    AND tbl_os.fabrica = $login_fabrica ";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res)==0){
               unset($dados_tickets[$chave]);
               continue;                 
            }
            $dados[$campos['reference_id']] = pg_fetch_array($res, 0, PGSQL_NUM);
        }
    }

    if(count(array_filter($dados_tickets)) > 0 and strlen($dados_tickets['exception']) == 0 ){            
?>
<table class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <TR class='titulo_coluna'>
			<th>Ticket</TD>
			<th>OS</th>
            <th>Tipo Atendimento</th>
            <th>Nome Cliente</th>
            <th>Cidade Cliente</th>
            <?php if (in_array($login_fabrica, [148])): ?>
                <th>Nome Técnico</th>
                <th>Data Agendamento</th>
                <th>Data Finalizado</th>
            <?php endif; ?>
            <th colspan="2">Ações</th>            
        </TR >
    </thead>
    <tbody>
        <?php

        foreach($dados_tickets as $campos){            
            $reference_id = "<a href='os_press.php?os=".$campos['reference_id']."' target=_blank>".$campos['reference_id']."</a>";  

            if(strlen(trim($campos['response_modificado']))>0){
                $dados_resposta = $campos['response_modificado'];
            }else{
                $dados_resposta = $campos['response'];
            }

            echo "<TR class='linha_".$campos['ticket']."'>
                <td class='tac'>".$campos['ticket']."</td>
                <td class='tac'>".$reference_id."</td>
                <td class='tac'>".$dados[$campos['reference_id']][1]."</td>
                <td class='tal'>".$dados[$campos['reference_id']][2]."</td>
                <td class='tac'> ".$dados[$campos['reference_id']][3]."
                    <input type='hidden' name='ticket_".$campos['ticket']."' id='ticket_".$campos['ticket']."' value='".$dados_resposta."'>
                </td>";

            if (in_array($login_fabrica, [148])) {
                echo "<td>".$dados[$campos['reference_id']][5]."</td>
                    <td>".$dados[$campos['reference_id']][6]."</td>
                    <td>".date('d/m/Y h:i', strtotime($campos['data_finalizado']))."</td>";
            }

            echo "<td class='tac'>
                    <button type='button' class='btn btn-primary' data-toggle='modal' data-target='#exampleModal' onclick='modal(".$campos['ticket'].", ".$campos['reference_id'].")' >Detalhes</button></td>
                    <td class='tac'>
                        <img src='imagens/loading_img.gif' class='loading_".$campos['ticket']."' style='width:20px; height:20px; display:none; ' >
                        <button type='button' class='btn btn-success aprova_".$campos['ticket']."' onclick='aprova_ticket(".$campos['ticket'].", ".$campos['reference_id'].")'  >Aprovar</button>
                    </td>
            </TR>";
        }

        ?>        
    </tbody>    
</table>
		<br/>
            <?php

		}elseif(!isset($dados_tickets['exception'])){
            echo "<div class='container'>
                <div class='alert'>
                        <h4> Nenhum ticket encontrado.</h4>
                </div>
            </div>";
        }


        if(isset($dados_tickets['exception'])){
			echo "<div class='container'>
            <div class='alert'>
                    <h4>".utf8_decode($dados_tickets['exception'])."</h4>
            </div>
            </div>";        
		}
?>
<? include "rodape.php" ?>
