<?php
include_once 'src/config.php';

session_start();
$_header = $_SESSION['header'];

if (!empty($_POST['module'])) {
    if (in_array($_POST['module'], array_keys($modulos)))
        $_SESSION['module'] = $_POST['module'];
}

$module = $_SESSION['module'];

if (!$module)
    $module = 'pecas';

$error = Array();

// Valida UPLOAD
if (!empty($_FILES['file']['tmp_name'])) {
    $file = $_FILES['file']['name'];
    $layout = $_POST['layout'];
    $headedCSV = isset($_POST['hasHeaders']);
    $module = $_POST['module'];

    if (!in_array($module, array_keys($modulos)))
        $error['Configuração'][] = "Não existe o módulo de API $module. Revise o programa.";

    if (!strpos(' ' . mime_content_type($_FILES['file']['tmp_name']), 'text'))
        $error['Arquivo'][] = "'{$file}' não possui um formato válido!";

    $filename = date('Ymd_His') . '.txt';
    $src_file = $file;

    if (count($error) == 0) {
        // importa o arquivo
        $path = "file/{$_header['client_code']}/$module/";
        @mkdir($path, 0777, true);

        if (!is_writable($path)) {
            $error['Arquivo'][] = "Diretório '{$path}' não tem permissão de escrita ou não existe!";
        } else {
            $arquivo = $path . $filename;
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $arquivo)) {
                $error['Arquivo'][] = "Não foi possível fazer o upload do arquivo '{$file}'";
            } else {
                if (filesize($arquivo) == 0)
                    $error['Arquivo'][] = "Arquivo '{$file}' está vazio e não pode ser integrado!";

                if (count($error) == 0):
                    //Cada linha do arquivo vira um ítem do Array
                    $file = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                    //Verfica se a primeira linha tem a mesma quantidade de campos do layout
                    //$layout = explode(';',$layout);

                    if (!$layout and $headedCSV) {
                        $layout = $file[0];
                    }

                    $layout = preg_split("/(,|;|\||#|\t)/", $layout);



                    // Valida que exista o campo-chave da API no arquivo                    
                    $key_field = $modulos[$module]['key_field'];
                    if (!in_array($key_field, $layout)) {
                        $error['layout'][] = "O campo-chave <strong class='label label-important'>$key_field</strong> não foi localizado no arquivo.";
                    }


                    // Se o arquivo veio com cabeçalho, tira ele
                    if ($headedCSV)
                        array_shift($file);

                    foreach ($layout as $indice => $colname) {
                        $layout[$indice] = preg_replace('/^[\'"]|[\'"]$/', '', trim($colname));
                    }

                    $column_count = count(preg_split('/[,;|#\t]/', $file[0]));
                    $layout_count = count($layout);

                    if (!count($error) and $column_count == $layout_count):
                        $arr = Array();
                        foreach ($file as $ln => $record) :
                            $cols = preg_split("/[,;|#]|\t/", $record);

                            if (count($cols) != $layout_count)
                                $error['layout'][] = "Registro " . ($ln + 1) . " não contém a quantidade de campos do layout do arquivo.";

                            if (is_array($cols)) {
                                for ($i = 0; $i < $layout_count; $i++):
                                    //if($col[$i])
                                    $arr[$ln][trim($layout[$i])] = preg_replace('/^[\'"]|[\'"]$/', '', trim($cols[$i]));
                                endfor;
                            }

                        //echo $referencia;
                        endforeach;

                        if (!count($error) and count($arr)):
                            $path_integracao = $path . '/integracao/';
                            @mkdir($path_integracao, 0777, true);

                            //Grava o arquivo em formato JSON
                            file_put_contents($path_integracao . $filename, json_encode($arr));

                            //Salva os itens na sessão para gerar a GRID
                            $_SESSION['integracao'][$module]['file'] = $filename;
                            $_SESSION['integracao'][$module]['user_filename'] = $src_file;
                            $_SESSION['integracao'][$module]['path'] = $path_integracao;
                            if (!count(array_filter($error)))
                                header("Location: {$_SERVER['PHP_SELF']}");
                        endif;

                    else :
                        if ($column_count != $layout_count)
                            $error['layout'][] = "Layout não contém a quantidade de campos do arquivo.";
                    endif;


                endif;
            }
        }
    }
}


if (isset($_POST['ajax2'])) {
    parse_str(file_get_contents("php://input"), $data);

    $action = $data['action'];
    $dados = $data['data'];
    
//    Validaçoes Iniciais
    if (empty($_SESSION['header']) and !strpos($_SERVER['PHP_SELF'], 'config.php')) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'Session Expired',
            'description' => 'Tempo limite da sessão. Autentique novamente. Obrigado.',
            'statusNumber' => 403,
            'statusText' => 'Forbidden',
                        )
        ));
    }

    if (empty($_SESSION['header']) and !strpos($_SERVER['PHP_SELF'], 'config.php')) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'Session Expired',
            'description' => 'Tempo limite da sessão. Autentique novamente. Obrigado.',
            'statusNumber' => 403,
            'statusText' => 'Forbidden',
                        )
        ));
    }


    if (!in_array($module, array_keys($modulos))) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'interpretar os parâmetros',
            'description' => 'Não existe o módulo de API $module. Revise o programa.',
            'statusNumber' => 400,
            'statusText' => 'Bad Request',
                        )
        ));
    }


    if (!in_array($action, $modulos[$module]['RESTallowed'])) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'processar a requisição',
            'description' => "Ação $action não permitida!",
            'statusNumber' => 405,
            'statusText' => 'Method Not Allowed',
                        )
        ));
    }
//    Fim das Validaçoes Iniciais
//    Configurações    
    $url = URI . $module;

    $i = 0;
    foreach ($_header as $key => $value) {
        $headers[$i] = $key . ":" . $value;
        $i +=1;
    }


    $validaTabela = new validaTabela($module);
    $colunaReferencia = $validaTabela->getIndiceDelete($module);


    $curl = new curlApi();
//    Envio de requisições
    switch ($action) {
        case "POST":            
            $retorno = $curl->post($url, $headers, json_encode($dados));                                                
            die($retorno['body']);
            break;
        case "PUT":
            for ($i = 0; $i < count($dados); $i++) {
                $indiceRegistro = $dados[$i][$colunaReferencia];
                unset($dados[$i][$colunaReferencia]);
                $aux = $curl->put($url . "/" . $colunaReferencia . "/" . $indiceRegistro, $headers, $dados[$i]);
                $retornos[$indiceRegistro] = json_decode($aux['body']);
            }

            die(json_encode($retornos));
            break;
        case "DELETE":
            for ($i = 0; $i < count($dados); $i++) {
                if ($module == 'tabela_preco') {
                    $linkDelete = $url . "/" . $colunaReferencia . "/" . $dados[$i][$colunaReferencia] . "/peca/" . $dados[$i]['peca'];
                } else {
                    $linkDelete = $url . "/" . $colunaReferencia . "/" . $dados[$i][$colunaReferencia];
                }
                $aux = $curl->delete($linkDelete, $headers);
                $retornos[$dados[$i][$colunaReferencia]] = json_decode($aux['body']);
            }

            die(json_encode($retornos));
            break;            
        case "GET":
            echo "GET";
            break;
    }
}



if (isset($_POST['ajax'])) {

    $action = $_POST['action'];
    $data = $_POST['data'];
    header("Content-Type: application/json");


    #Validações
    if (empty($_SESSION['header']) and !strpos($_SERVER['PHP_SELF'], 'config.php')) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'Session Expired',
            'description' => 'Tempo limite da sessão. Autentique novamente. Obrigado.',
            'statusNumber' => 403,
            'statusText' => 'Forbidden',
                        )
        ));
    }

    if (empty($_SESSION['header']) and !strpos($_SERVER['PHP_SELF'], 'config.php')) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'Session Expired',
            'description' => 'Tempo limite da sessão. Autentique novamente. Obrigado.',
            'statusNumber' => 403,
            'statusText' => 'Forbidden',
                        )
        ));
    }


    if (!in_array($module, array_keys($modulos))) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'interpretar os parâmetros',
            'description' => 'Não existe o módulo de API $module. Revise o programa.',
            'statusNumber' => 400,
            'statusText' => 'Bad Request',
                        )
        ));
    }


    if (!in_array($action, $modulos[$module]['RESTallowed'])) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'processar a requisição',
            'description' => "Ação $action não permitida!",
            'statusNumber' => 405,
            'statusText' => 'Method Not Allowed',
                        )
        ));
    }

    #----------
    #Pre configuração
    $url = URI . $module;

    if (is_array($data) and count($data)) {
        extract($modulos[$module], EXTR_PREFIX_ALL, 'api');

        if (!isset($api_max_req_session)) {
            $api_max_req_session = 20;
        }
    }

    if (!in_array($api_key_field, array_keys($data[0]))) {
        die(json_encode(array(
            'Result' => false,
            'error' => 'interpretar os parâmetros',
            'description' => 'Campo chave "' . $api_key_field . '" não recebido.',
            'statusNumber' => 400,
            'statusText' => 'Bad Request',
                        )
        ));
    }



    $i = 0;
    foreach ($_header as $key => $value) {
        $cabecalho[$i] = $key . ":" . $value;
        $i +=1;
    }

    if (count($data) > $api_max_req_session and $action != "PUT" and $action != "DELETE") {
        $rest = new curlApi();

        switch ($action) {
            case 'POST':

                $retorno = $rest->post($url, $cabecalho, json_encode($data));
                break;
            default:
                die("AÇÃO $action NÃO RECONHECIDA!!!");
        }



        $response = $rest->finalResult;
        $response['type'] = 'multiply';
        $response['Result'] = true;
        $response['Acao'] = 'CADASTRAR';
        //$body = json_encode($response,true);					
        //$response['body'] = $body;
        //die(json_encode($response));

        $success = (in_array($log[0]['Status'], array(201, 202))) ? 'OK' : 'KO';
        die(json_encode(
                        $response
                )
        );
        // die(json_encode(
        // 		array(
        // 			'Result' => true,
        // 			'Type'   => 'multiply',
        // 			'Status' => 201,
        // 			'data'   => $response,					
        // 		)
        // 	)
        // );
    } else {
        foreach ($data as $indice => $rec) {

            $referencia = $rec[$api_key_field];
            $log[$indice][$api_key_field] = $referencia;

            $curlApi = new curlApi();

            switch ($module) {
                case 'postos':
                    $retGet = $curlApi->get($url . "/cnpj/$referencia", $cabecalho);
                    break;
                case 'familias':
                    $retGet = $curlApi->get($url . "/codigo/$referencia", $cabecalho);
                    break;
                case 'listas_basicas':

                    $i = 0;
                    $parametros = "";
                    foreach ($rec as $chave => $valor) {
                        $parametros .= "$chave=" . utf8_decode($valor) . "&";
                        $colunas[$i] = $chave;
                        $i += 1;
                    }

                    $rest = new curlApi();
                    if ($action == 'POST') {
                        $retorno = $rest->post($url, $cabecalho, $parametros);
                    } elseif ($action == 'PUT') {
                        $retorno = $rest->put($url . "/tabela/" . $rec['tabela'] . "/peca/" . $rec['peca'], $cabecalho, $parametros);
                    } elseif ($action == 'DELETE') {
                        $retorno = $rest->delete($url . "/id/" . $rec['lista_basica'], $cabecalho);
                    }

                    die($retorno['body']);

                    break;
                case 'tabela_preco':

                    $i = 0;
                    $parametros = "";
                    foreach ($rec as $chave => $valor) {
                        $parametros .= "$chave=" . utf8_decode($valor) . "&";
                        $colunas[$i] = $chave;
                        $i += 1;
                    }

                    $rest = new curlApi();
                    if ($action == 'POST') {
                        $retorno = $rest->post($url, $cabecalho, $parametros);
                    } elseif ($action == 'PUT') {
                        $retorno = $rest->put($url . "/tabela/" . $rec['tabela'] . "/peca/" . $rec['peca'], $cabecalho, $parametros);
                    } elseif ($action == 'DELETE') {
                        $retorno = $rest->delete($url . "/tabela/" . $rec['tabela'] . "/peca/" . $rec['peca'], $cabecalho);
                    }

                    die($retorno['body']);

                    break;
                default:
                    $retGet = $curlApi->get($url . "/referencia/$referencia", $cabecalho);
                    break;
            }


            //$parametros = "referencia=556&descricao=Peca 556&ipi=1&multiplo=2&devolucao_obrigatoria=false&garantia_diferenciada=1&acessorio=false&item_aparencia=false&origem=Nac&ativo=true";


            if (is_array($retGet)) {
                $i = 0;
                $parametros = "";
                foreach ($rec as $chave => $valor) {
                    $parametros .= "$chave=" . utf8_decode($valor) . "&";
                    $colunas[$i] = $chave;
                    $i += 1;
                }

                $validaTabela = new validaTabela($module);
                $validaTabela->validarColunas($colunas);

                if ($retGet['http_code'] == 0) {
                    die(json_encode(array(
                        'Result' => false,
                        'error' => 'contactar com a API',
                        'description' => 'O servidor não responde, provávelmente está off-line. ' .
                        var_export($restGET->responseHeaders, true),
                        'statusNumber' => 500,
                        'statusText' => 'Internal Server Error',
                                    )
                    ));
                }

                switch ($retGet['http_code']) {
                    case 404:

                        die(json_encode(array(
                            'Result' => false,
                            'error' => 'Invalid link',
                            'description' => 'Não foi encontrada a URL. Verifique a configuração do programa.',
                            'statusNumber' => 404,
                            'statusText' => 'Not Found',
                                        )
                        ));
                        break;
                    case 400:

                        $resposta = json_decode($retGet['body']);
                        $respostaString = $resposta->error;

                        $log[$indice]['Status'] = 400;
                        $log[$indice]['requestHeaders'] = $retGet['req_header'];
                        $log[$indice]['Data'] = $retGet['body'];
                        //$log[$indice]['Header']  = $restGET->responseHeaders;
                        $log[$indice]['Acao'] = 'conferir registro';

                        if ($respostaString == 'Nenhum resultado encontrado') {
                            if ($action == 'DELETE' or ($action == 'PUT' and $api_POSTnotGET)) {

                                $log[$indice]['Acao'] = ($action == 'DELETE') ? 'EXCLUIR' : 'ALTERAR';
                                $log[$indice]['Status'] = 400;
                                $log[$indice]['requestHeaders'] = $retGet['req_header'];
                                $log[$indice]['Data'] = json_encode(array('error' => 'Recurso NÃO existe', 'Status' => 204));

                                break;
                            } else if ($action == 'POST' or ($action == 'PUT' and $api_POSTnotGET)) {
                                $log[$indice]['Acao'] = 'CADASTRAR';
                                $rest = new curlApi();
                                $retPost = $rest->post($url, $cabecalho, $parametros);

                                break;
                            } else {
                                die(json_encode(array(
                                    'Result' => false,
                                    'error' => 'validar identidade',
                                    'description' => 'Os dados de autenticação foram rejeitados pelo Servidor.',
                                    'statusNumber' => 401,
                                    'statusText' => 'Unauthorized',
                                                )
                                ));
                            }
                        }

                        if ($respostaString == 'Invalid auth') {
                            $log[$indice]['Acao'] = "API Login";
                        }

                        break;

                    case 204:

                        if ($action == 'DELETE' or ($action == 'PUT' and !$api_POSTnotGET)) {
                            $log[$indice]['Acao'] = ($action == 'DELETE') ? 'EXCLUIR' : 'ALTERAR';
                            $log[$indice]['Status'] = 400;
                            $log[$indice]['requestHeaders'] = $retGet['req_header'];
                            $log[$indice]['Data'] = json_encode(array('error' => 'Recurso NÃO existe', 'Status' => 204));
                        } else {
                            $log[$indice]['Acao'] = 'CADASTRAR';
                            $rest = new curlApi();
                            $retPost = $rest->post($url, $cabecalho, $parametros);
                        }

                        break;

                    case 200: // Found												;						
                        if ($action == 'PUT') {
                            $rest = new curlApi();
                            $retorno = $rest->put($url . "/referencia/$referencia", $cabecalho, $parametros);
                            $log[$indice]['Acao'] = 'ALTERAR';
                        } else if ($action == 'DELETE') {
                            $rest = new curlApi();
                            $retorno = $rest->delete($url . "/referencia/$referencia", $cabecalho);
                            $log[$indice]['Acao'] = 'EXCLUIR';
                        }

                        break;

                    case 304: //Not Changed

                        if ($action == 'POST' and !$apiPUTifExist) {
                            $log[$indice]['Acao'] = 'CADASTRAR';
                            $log[$indice]['Status'] = 400;
                            $log[$indice]['requestHeaders'] = $retGET['req_header'];
                            $log[$indice]['Data'] = json_encode(array('error' => 'Recurso já existe', 'Status' => 400));
                        } else {
                            if ($action == 'POST') {
                                $action = 'PUT';
                            }

                            $log[$indice]['Acao'] = ($action == 'DELETE') ? 'EXCLUIR' : 'ALTERAR';

                            $rest = new curlApi();



                            if ($action == 'PUT') {
                                $rest->put($url, $cabecalho, $parametros);
                            } else {
                                $rest->delete($url, $cabecalho, $parametros);
                            }
                        }

                        break;



                    default:
                        die("KO|processar os dados {$retGET['http_code']}|A0D");
                        break;
                }
                if ($rest->finalResult != null) {
                    $response = $rest->finalResult;

                    $body = json_decode($response['body'], true);

                    $log[$indice]['Status'] = $response['http_code'];
                    $log[$indice]['Data'] = $body;
                    $log[$indice]['Header'] = $response['req_header'];
                    $log[$indice]['requestHeaders'] = $response['req_header'];
                    $log[$indice]['Resultado/Erro'] = $body['status_code'];
                }
            } else {

                die(json_encode(array(
                    'Result' => false,
                    'error' => 'contactar com a API',
                    'description' => 'O servidor não responde, provávelmente está off-line',
                    'statusNumber' => 500,
                    'statusText' => 'Internal Server Error',
                                )
                ));
            }
        } //Foreache 1
    }
    #----------------

    if (count($data) == 1) {

        $success = (in_array($log[0]['Status'], array(201, 202))) ? 'OK' : 'KO';
        die(json_encode(
                        array(
                            'Result' => in_array($log[0]['Status'], array(201, 202)),
                            'Type' => 'single',
                            'Status' => $log[0]['Status'],
                            'data' => $log[0],
                        )
                )
        );
    }

    if (count($data) > 1) {
        if ($action == "POST" && count($data) > $api_max_req_session) {
            $log[$idx]['Status'] = $response[0]['http_code'];
        } else {
            $log[$idx]['Status'] = $response['http_code'];
        }

        $log[$idx]['Data'] = json_decode($response['body'], true);
        $log[$idx]['Header'] = $retHead;
        $log[$idx]['requestHeaders'] = $response['req_header'];
        die(json_encode(
                        array(
                            'Result' => true,
                            'Type' => (count($data) > $api_max_req_session) ? 'multiJSON' : 'multi',
                            'Status' => $log[0]['Status'],
                            'data' => $log,
                        )
                )
        );
    }



    echo "OK|<span class='alert alert-notice'>Arquivo processado. Pode baixar o arquivo de log: <a href='$log_file_link' class='btn btn-link'>LOG</a></span>";
    exit;
}

if (!empty($_SESSION['integracao'][$module])) {
    $path = $_SESSION['integracao'][$module]['path'];
    $file = $_SESSION['integracao'][$module]['file'];
    $srcfn = $_SESSION['integracao'][$module]['user_filename'];

    $data = json_decode(file_get_contents($path . $file));

    //retorna todos os indide da primeira linha do Object
    //"<input type='checkbox' name='all' id='checkall' />
    //<label for='checkall' style='font-weight:bold'>Tudo</label>",
    $header_th = array_merge(
            (array)
            array_keys((Array) $data[0]), (array) 'Ações');
    $header_th = str_replace('_', ' ', $header_th);

    //echo "<pre>";  			print_r($data[0]);
    foreach ($data as $key => $ln) {
        $grid[] = "<tr>";
        $grid[] = "<td style='text-align: center'>
							<input type='checkbox' class='check-line' name='check[{$key}][]' />
							<input type='hidden' class='data' name='data[{$key}][]' value='" . json_encode($ln) . "' />
					   </td>";

        foreach ($ln as $value)
            $grid[] = "<td>&nbsp; " . $value . "</td>";

        $grid[] = "<td style=' text-align: center'>
							<button type='button' class='btn btn-client btn-acao'>
								<span class='icon-circle-arrow-up'></span>
							</button>
					   </td>";
        $grid[] = "</tr>";
    }
}


include_once 'inc/header.php';
?>

<!-- conteudo		 -->
<div class="span10">
    <h2 id="titulo-pagina">Integração de Dados via API Pós-Venda</h2>
    <form class="form-horizontal" action=""  enctype="multipart/form-data" method="POST">
        <div  class="row-fluid line-form">
            <div id='env-alerta'>

            </div>
            <?php echo msgError($error); ?>
            <div class="span5">                            
                <label>Modulo da Api</label>
                <select name="module" id="APImodule" class="span6">
                    <?php
                    foreach ($modulos as $valor => $params) {
                        $mod_nome = $params['displayStr'];
                        $mod_sel = ($module == $valor) ? 'selected' : '';
                        $disabled = ($params['disabled']) ? "disabled='disabled'" : '';
                        echo "<option value='$valor' $mod_sel $disabled>$mod_nome</option>\n";
                    }
                    ?>                            
                </select>
            </div>
            <div class="span5">
                <div class="controls">
                    <label>Arquivo</label>
                    <input class="input-file" id="csv" name="file" type="file" accept="text/plain,text/csv" required="">
                    <span class="help-block">
                        O Formato do arquivo deve ser <acronym title="Comma Separated Values: ''Valores separados por vírgula'">'CSV'</acronym>
                        com extensão recomendada 'txt' e como delimitador ';' ou &lt;TAB&gt;. 
                        É importante que a codificação do arquivo seja em formato <b>UTF-8</b>. 
                    </span>
                </div>                
            </div>                
        </div>        
        <div class="row-fluid line-form">
            <div class="span5" id="hasHeadersGroup">
                <label class="checkbox">
                    <input id="hasHeaders" name="hasHeaders" type="checkbox">
                    Arquivo com Cabeçalhos
                </label>                                
                <span class="help-block">
                    <p>Se o arquivo já contém o cabeçalho com os nomes certos dos campos, selecione esta opção.</p>
                    <p>Pode então deixar o Layout em branco e o programa irá utilizar o cabeçalho ou,
                    <p>se preencher o campo acima do Layout, o programa ignorará a primeira linha do arquivo.</p>
                </span>

            </div>
            <div class="span5">
                <textarea class="span12" id="layout" name="layout" ></textarea>   
                <span class="help-block">
                    <p>É a posição de cada item enviado no arquivo texto, o nome das posições deve ser o mesmo que está na documentação</p>
                    <p>O layout deve ter a mesma quantidade de campos enviado em cada linha do arquivo txt</p>
                    <p>Use ponto-e-vírgula para separar os campos.</p>
                </span>
            </div>
        </div>
        <div class="row-fluid"><div class="span12"><p><b>Dica:</b> Para visualizar o retorno da API observe o quadro azul abaixo da tabela</p></div></div>
        <div class="row-fluid line-form">
            <div class="alert alert-info" id="fileHeaders">

            </div>
        </div>
        <div class="row-fluid line-form">
            <div class="span5">
                <div class="control-group">		                            
                    <button type="submit" id="sendFile" class="btn btn-primary">Importar</button>		                            
                </div>
            </div>
        </div>
    </form>		
    <div class="row-fluid line-form">
        <?php
        if (!empty($_SESSION['integracao'][$module])) {
            ?>

            <div class="row-fluid">    		    		
                <div class="span7">    			    			
                    <div class="btn-group margin-bottom"  >
                        <input type='button' id='btn-acao-selecionados' class='btn' value='Enviar Selecionados'>
                        <a class="btn dropdown-toggle" id="btn-seleciona-acao" data-toggle="dropdown" href="#">
                            Ação
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">		    		  				
                            <?php
                            $li_template = '<li><a tabindex="-1" class="metodo-options" href="javascript:" data-help="%s">%s</a></li>';
                            foreach ($modulos[$module]['RESTallowed'] as $action_name) {
                                $action_hint = $RestActionsHints[$action_name]['hint'];
                                printf($li_template, $action_hint, $action_name);
                            }
                            ?>					  							
                        </ul>					  					  					  	
                    </div>					
                    <div class="span1">
                        <span id='metodoSelecionado' class='label label-info'></span>
                    </div>
                </div>						    		    		
                <div id="env-alerta-erro"  class="span5">

                </div>
            </div>

            <div class="row-fluid">
                <div class='span12'>
                    <span class='label margin-bottomP'>Arquivo: <?php echo $srcfn ?></span>

                    <table class="table table-hover table-bordered table-striped table-condensed">	    			
                        <thead>
                            <tr style='font-weight:bold;text-transform:capitalize;vertical-align:top'>
                                <th><input type="checkbox" id="checkAll" > </th>
                                <?php
                                for ($i = 0; $i < count($header_th); $i++) {
                                    echo "<th>" . $header_th[$i] . "</th>";
                                }
                                ?>															
                            </tr>
                        </thead>
                        <tbody class='itens'>
                            <?= implode('', $grid); ?>
                        </tbody>
                    </table>    				
                </div>
            </div>
            <di class="row-fluid">
                <div class="span12 alert alert-info">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Retorno da API: </strong> <p id="retornoApi"></p>
                </div>
        </div>
        <?php
    }
    ?>
</div>
</div>
<!-- conteudo		 -->

<?php
include_once 'inc/footer.php';
?>

