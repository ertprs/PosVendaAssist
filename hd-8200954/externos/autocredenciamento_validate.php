<?php
    //externos/autocredenciamento.php
    header("Content-Type:text/html; charset=iso-8859-1");

    $caminho_imagem = dirname(__FILE__) . '/../autocredenciamento/fotos/';
    $caminho_path   = dirname($_SERVER['PHP_SELF']) . '/../autocredenciamento/fotos/';


    include dirname(__FILE__) . '/../dbconfig.php';
    include dirname(__FILE__) . '/../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../class_resize.php';
    include dirname(__FILE__) . '/../mlg/mlg_funciones.php';
    include dirname(__FILE__) . '/../trad_site/fn_ttext.php';

    $not_in_fabricas = '108, 93, 47, 89, 63, 92, 8, 14, 66, 5, 43, 61, 77, 76, 110, 78, 107, 112, 113, 75, 111, 109, 10,119,46,133, 159, 166';
    $not_in_marcas = '131, 189, 178, 177, 184,137,136,199';

    $debug = ($_COOKIE['debug'][0] == 't');

    /* 10/12/2009 MLG - Não funcionava a gravação de informação porque agora o $msg_erro é um array,
                        portanto a conferência tem que ser feita com count($msg_erro) e não com strlen()
    */

    /*  Tradução inicial, depois, no 'else' para mudar de formulário, tem outro array para ele  */

    if(!empty($_GET['fabrica'])){
        if((int)$_GET['fabrica']){
            $sql = "SELECT lower(replace(nome,' ','')) from tbl_fabrica where fabrica = ".$_GET['fabrica'];
            $res = pg_query($con,$sql);
            $fabrica_nome = pg_fetch_result($res,0,0);
        }
    }
    $a_labels = array(
        "autocredenciamento"    => array(
            "pt-br" => "Autocredenciamento",
            "es"    => "Auto-Regristro",
            "en"    => "Self-Register"
        ),
        "digite_CNPJ"=> array(
            "pt-br" => "Por favor, digite o CNPJ da sua Autorizada.",
            "es"    => "Por favor, escriba su Nº de Identificación Fiscal",
            "en"    => "Please, type your Tax ID"
        ),
        "Informe_CNPJ"=>array(
            "pt-br" => "CNPJ do Posto Autorizado:",
            "es"    => "Escriba su ID fiscal:",
            "en"    => "Enter your Tax ID:"
        ),
        "erro_CNPJ" => array (
            "pt-br" => "CNPJ digitado inválido",
            "es"    => "La ID Fiscal no es válida",
            "en"    => "TaxID is invalid",
            "de"    => "TaxID ist ungültig",
            "zh-cn" => "",
            "zh-tw" => ""
        ),
        "Consultar" => array(
            "pt-br" => "",
            "es"    => "",
            "en"    => "Search"
        ),
        "gravar"    => array(
            "pt-br" => "Gravar Formulário",
            "es"    => "Enviar Formulario",
            "en"    => "Submit Form"
        ),
        "tc_agradece"=>array(
            "pt-br" => "A Telecontrol agradece o seu cadastro!",
            "es"    => "¡Telecontrol agradece su alta!",
            "en"    => "Telecontrol thanks you for signing up!"
        ),
        "homepage"  => array(
            "pt-br" => "Página inicial",
            "es"    => "",
            "en"    => "Home Page"
        ),
        "termos_de _uso"    => array(
            "pt-br" => "Li e concordo",
            "es"    => "He leído y estoy de acuerdo",
            "en"    => "I have read and agree"
        ),
        "label_texto_informacao"=> array(
        "pt-br" => '<p class="texto_informativo_informacao_font_cabecalho"><b>O que é e para que serve o Autocredenciamento Telecontrol?</b></p><br>
        <p class="texto_informativo_informacao_font_conteudo">É um novo recurso que desenvolvemos para auxiliar nossos parceiros. Tem por finalidade, contribuir para que as indústrias ampliem sua Rede Autorizada e, por outro lado, possibilitar ao seu Posto Autorizado acesso a um canal rápido e eficaz para oferecer seus serviços.</p><br>
        <p class="texto_informativo_informacao_font_conteudo"><b>1º Passo -</b> O Posto Autorizado faz o cadastro no site da <b>Telecontrol</b> detalhando as informações importantes para que as Industrias possam analisar seu perfil (linhas de atendimento, cidades que atende, fotos da sua empresa, preferência por marcas, recursos a disposição - carro, estacionamento, etc).</p><br>
            <p class="texto_informativo_informacao_font_conteudo"><b>2º Passo -</b> A <b>Telecontrol</b> disponibilizará estas informações para as indústrias que utilizam nosso Sistema.</p><br>
        <p class="texto_informativo_informacao_font_conteudo"><b>3º Passo -</b> A indústria interessada entrará em contato para iniciar o processo de credenciamento do Posto Autorizado.</p><br>
        <p class="texto_informativo_informacao_font_cabecalho"><strong style="font-size: 17px;"> Não perca tempo, cadastre-se já!</strong></p><br>',
        "es"    => '<p class="texto_informativo_informacao_font_cabecalho"><b>¿Qué es y para qué sirve la Auto Acreditación Telecontrol?</b></p><br>
        <p class="texto_informativo_informacao_font_conteudo">Es una nueva herramienta que hemos desarrollado para ayudar a nuestros socios. Su propósito es contribuir a que nuestros clientes aumenten su Red Autorizada, y por otro lado permitir el acceso a los Servicios de Asistencia Técnica a un canal rápido y efectivo para ofrecer sus servicios a empresas de su preferencia.</p></center><br>
        <p class="texto_informativo_informacao_font_conteudo"><b>Paso 1 -</b> El Servicio de Asistencia Técnica se da de alta en nuestra web, facilitando informaciones para que las empresas puedan analizar su perfil (líneas telefónicas, ciudades cercanas que atiende, fotos de su negocio, su preferencia por algunas marcas, recursos disponibles (Estacionamiento, etc.)</p><br>
        <p class="texto_informativo_informacao_font_conteudo"><b>Paso 2 -</b><b> Telecontrol</b> proporcionará esta información a las empresas que utilizan nuestro sistema.</p><br>
        <p class="texto_informativo_informacao_font_conteudo"><b>Paso 3 -</b> La empresa en cuestión se comunicará con usted para iniciar el proceso de acreditación.</p><br>
        <p class="texto_informativo_informacao_font_conteudo">Es muy simple. ¡Dese prisa y regístrese ahora!</p><br>',
        "en"    => '<p class="texto_informativo_informacao_font_cabecalho"><b>What is and what is the Auto Accreditation Telecontrol?</b></p><br>
        <p class="texto_informativo_informacao_font_conteudo">It is a new tool that we have developed to help our partners. Its purpose is to help our clients increase their authorized network and on the other hand, allow Technical Support Services the access to a fast and effective channel to offer their services to companies of your choice.</p><br>
        <p class="texto_informativo_informacao_font_conteudo"><b>Step 1 -</b> Technical Assistance Service signs up on our website, providing information for companies to assess their profile (telephone lines, attending nearby cities, photos of your business, your preference for some brands, available resources (Parking , etc.).</p><br>
        <p class="texto_informativo_informacao_font_conteudo"><b>Step 2 -</b> <b>Telecontrol</b> will provide this information to companies that use our system.</p><br>
        <p class="texto_informativo_informacao_font_conteudo"><b>Step 3 -</b> A company that have an interest n your bussiness will contact you to start the accreditation process.</p><br>
        <p class="texto_informativo_informacao_font_conteudo">It is very simple. Hurry and sign up now!</p><br>'
        ),
    );

    function pg_array_quote($arr, $valType = 'string') {
        if (!is_array($arr)) return 'NULL';

        if (count($arr) == 0) return '\'{}\'';
        $ret = '{';
        switch ($valType) {
            case 'str':
            case 'string':
            case 'text':
                foreach($arr as $item) {
                    if      (is_bool($item)) $item = ($item) ? 'TRUE' : 'FALSE';
                    elseif  (is_null($item) or strtoupper($item) == 'NULL') $item = 'NULL';
                    elseif  (is_string($item) and strpos($item, ',') !== false) $item = "\"$item\"";
                    $quoted[] = $item;
                }
                $ret .= implode(',',$quoted) . '}';
                return $ret;
            break;
            case 'numeric':
            case 'int':
            case 'integer':
            case 'float':
            case 'boolean':
            case 'bool':
                foreach($arr as $item) {
                    if (is_string($item) and
                        ($item == 't' or $item == 'f') and
                        $valType == 'bool') $item = ($item == 't');
                    if  (is_bool($item)) $item = ($item) ? 'TRUE' : 'FALSE';
                    $quoted[] = $item;
                }
                $ret .= implode(',',$quoted) . '}';
                return $ret;
            break;
        }
        return 'NULL';
    }

    $btn_acao = $_POST['btn_acao'];
    if ($btn_acao == "Search") $btn_acao = "Cadastrar"; //  Para a versão em inglês do formulário...

    $outros_sistema = '';

    $html_titulo = ttext ($a_labels, "autocredenciamento", $cook_idioma);

    function checaCPF ($cpf,$return_str = true) {
        global $con;    // Para conectar com o banco...
        $cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
        if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

        $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
        if ($res_cpf === false) {
            return ($return_str) ? pg_last_error($con) : false;
        }
        return $cpf;
    }

    if(strtolower($btn_acao) == 'cadastrar'){
        $verifica_cnpj  = preg_replace("/\D/","",$cnpj);
        $verifica_email = trim($_POST['verifica_email']);

        if (is_numeric($verifica_cnpj)) {
            if (checaCPF($verifica_cnpj,false)===false) $msg_erro = 'CNPJ digitado inválido';
        } else $msg_erro = $cnpj;

        if(strlen($msg_erro) == 0){
            $xcnpj = str_replace(["-","/","."], "", $cnpj);
            $dados_posto = getPosto($xcnpj);

            if (!isset($dados_posto['exception']) && count($dados_posto) > 0) {
                $dados_posto = current($dados_posto);
                $posto                    = $dados_posto['posto'];
                $nome                     = utf8_decode($dados_posto['razao_social']);
                $nome_fantasia            = utf8_decode($dados_posto['nome_fantasia']);
                $cnpj                     = $dados_posto['cnpj'];
                $endereco                 = utf8_decode($dados_posto['endereco']);
                $numero                   = $dados_posto['numero'];
                $complemento              = utf8_decode($dados_posto['complemento']);
                $bairro                   = utf8_decode($dados_posto['bairro']);
                $cidade                   = utf8_decode($dados_posto['cidade']);
                $estado                   = $dados_posto['estado'];
                $cep                      = $dados_posto['cep'];
                $email                    = $dados_posto['email'];
                $telefone                 = $dados_posto['fone'];
                $fax                      = $dados_posto['fax'];
                $contato                  = $dados_posto['contato'];
                $ie                       = $dados_posto['ie'];
                $linhas                   = utf8_decode($dados_posto['linhas']);
                $funcionarios             = $dados_posto['funcionario_qtde'];
                $oss                      = $dados_posto['os_qtde'];
                $atende_cidade_proxima    = utf8_decode($dados_posto['atende_cidade_proxima']);
                $marca_nao_autorizada     = $dados_posto['marca_nao_autorizada'];
                $marca_ser_autorizada     = $dados_posto['marca_ser_autorizada'];
                $melhor_sistema           = $dados_posto['melhor_sistema'];
                $fabrica_credenciadas     = $dados_posto['fabrica_credenciada'];
                $marcas_credenciadas      = $dados_posto['marca_credenciada'];
                $observacao               = utf8_decode($dados_posto['observacao']);
                $fabricantes              = $dados_posto['outras_fabricas'];
                $informacao_sistema       = $dados_posto['informacao_sistema'];
                $informacao_marca         = $dados_posto['informacao_marca'];
                $informacao_vantagem      = $dados_posto['informacao_vantagem'];
                $informacao_comentario    = $dados_posto['informacao_comentario'];
                $visita_tecnica           = $dados_posto['visita_tecnica'];
                $atende_consumidor_balcao = $dados_posto['atende_consumidor_balcao'];
                $atende_revendas          = $dados_posto['atende_revendas'];

                $aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento

                list($info_sistema_1,$info_sistema_2,$info_sistema_3)  = explode('|', $informacao_sistema);
                list($info_marca_1,$info_marca_2,$info_marca_3)  = explode('|', $informacao_marca);
                list($info_vantagem_1,$info_vantagem_2,$info_vantagem_3)  = explode('|', $informacao_vantagem);
                $fabrica_credenciadas_2 = $fabrica_credenciadas;
                $fabrica_credenciadas_2 = str_replace("{", "", $fabrica_credenciadas_2);
                $fabrica_credenciadas_2 = str_replace("}", "", $fabrica_credenciadas_2);

                $sql_fabrica_ja_credenciada = "SELECT fabrica FROM tbl_posto_fabrica WHERE fabrica not in ($fabrica_credenciadas_2) and posto = $posto and credenciamento = 'CREDENCIADO'";
                $res_fabrica_cre = pg_query($con, $sql_fabrica_ja_credenciada);

                $array_fab = explode(",", $fabrica_credenciadas_2);

                while($data_fab = pg_fetch_object($res_fabrica_cre)){
                    array_push($array_fab, $data_fab->fabrica);

                }
                $fabrica_credenciadas =  implode(",", $array_fab);


                if(strlen($posto) > 0 && $posto !== '0'){
                    $sqlCredenciadas = "SELECT fabrica FROM tbl_posto_fabrica WHERE posto = $posto and credenciamento = 'CREDENCIADO'";
                    $resCredenciadas = pg_query($con, $sqlCredenciadas);
                    $arrayCred = pg_fetch_all_columns($resCredenciadas);
                }

            } else {
                $sql = "SELECT *
                        FROM tbl_posto
                        LEFT JOIN tbl_posto_extra using(posto)
                        WHERE cnpj = '$verifica_cnpj'
                        ORDER BY posto DESC LIMIT 1";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $posto                 = pg_fetch_result($res,0,posto);
                    $nome                  = pg_fetch_result($res,0,nome);
                    $nome_fantasia         = pg_fetch_result($res,0,nome_fantasia);
                    $cnpj                  = pg_fetch_result($res,0,cnpj);
                    $endereco              = pg_fetch_result($res,0,endereco);
                    $numero                = pg_fetch_result($res,0,numero);
                    $complemento           = pg_fetch_result($res,0,complemento);
                    $bairro                = pg_fetch_result($res,0,bairro);
                    $cidade                = pg_fetch_result($res,0,cidade);
                    $estado                = pg_fetch_result($res,0,estado);
                    $cep                   = pg_fetch_result($res,0,cep);
                    $email                 = pg_fetch_result($res,0,email);
                    $telefone              = pg_fetch_result($res,0,fone);
                    $fax                   = pg_fetch_result($res,0,fax);
                    $contato               = pg_fetch_result($res,0,contato);
                    $ie                    = pg_fetch_result($res,0,ie);
                    $pais                  = pg_fetch_result($res,0,pais);
                    $descricao             = pg_fetch_result($res,0,descricao);

                    $aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento

                    if(strlen($posto) > 0){
                        $sqlCredenciadas = "SELECT fabrica FROM tbl_posto_fabrica WHERE posto = $posto and credenciamento = 'CREDENCIADO'";
                        $resCredenciadas = pg_query($con, $sqlCredenciadas);
                        $arrayCred = pg_fetch_all_columns($resCredenciadas);
                    }

                }else{
                    $aux_cnpj = $verifica_cnpj; // Para ele poder carregar o formulário de autocredenciamento
                }
            }
        } else {
            $msg_erro = $msg_erro;
            #$msg_erro = '';
        }
    }

    function getPosto($cnpj) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api2.telecontrol.com.br/site/autoCredenciamento/cnpj/".$cnpj,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Access-Application-Key: 7e5bcb2c9c6284315c4bf3d09eb95bd06c52ba20",
            "Access-Env: PRODUCTION",
            "Content-Type: application/x-www-form-urlencoded"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "erro";
        } else {
          $response = json_decode($response, true);
          return $response;
        }
    }

    function getPostoCadastrado($cnpj) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api2.telecontrol.com.br/site/atualizaPosto/cnpj/".$cnpj,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Access-Application-Key: 7e5bcb2c9c6284315c4bf3d09eb95bd06c52ba20",
            "Access-Env: PRODUCTION",
            "Content-Type: application/x-www-form-urlencoded"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "erro";
        } else {
          $response = json_decode($response, true);
          return $response;
        }
    }

    function atualizaCredenciado($dados) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api2.telecontrol.com.br/site/autoCredenciamento",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => $dados,
          CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Access-Application-Key: 7e5bcb2c9c6284315c4bf3d09eb95bd06c52ba20",
            "Access-Env: PRODUCTION",
            "Content-Type: application/json"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "Erro ao Atualizar Posto";
        } else {
	  $response = json_decode($response, true);
          return $response;
        }
    }

    function atualizaPosto($dados) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api2.telecontrol.com.br/site/atualizaPosto",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => $dados,
          CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Access-Application-Key: 7e5bcb2c9c6284315c4bf3d09eb95bd06c52ba20",
            "Access-Env: PRODUCTION",
            "Content-Type: application/json"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "Erro ao Atualizar Posto";
        } else {
	  $response = json_decode($response, true);
          return $response;
        }
    }

    function gravaCredenciado($dados) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api2.telecontrol.com.br/site/autoCredenciamento",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $dados,
          CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Access-Application-Key: 7e5bcb2c9c6284315c4bf3d09eb95bd06c52ba20",
            "Access-Env: PRODUCTION",
            "Content-Type: application/json"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "Erro ao Gravar";
        } else {
	  $response = json_decode($response, true);
          return $response;
        }
    }

    //  Funções para o Banco de Dados
    function pg_begin() {
        global $con;
        $pg_res = pg_query($con,"BEGIN TRANSACTION");
        return (is_resource($pg_res)) ? $pg_res : pg_last_error($pg_res);
    }
    function pg_commit() {
        global $con;
        $pg_res = pg_query($con,"COMMIT TRANSACTION");
        return (is_resource($pg_res)) ? $pg_res : pg_last_error($pg_res);
    }
    function pg_rollback($loop = '') {
        global $con;
        $pg_res = pg_query($con,"ROLLBACK $loop TRANSACTION");
        return (is_resource($pg_res)) ? $pg_res : pg_last_error($pg_res);
    }

    $estados = array("AC" => "Acre",        "AL" => "Alagoas",  "AM" => "Amazonas",         "AP" => "Amapá",
                     "BA" => "Bahia",       "CE" => "Ceará",    "DF" => "Distrito Federal", "ES" => "Espírito Santo",
                     "GO" => "Goiás",       "MA" => "Maranhão", "MG" => "Minas Gerais",     "MS" => "Mato Grosso do Sul",
                     "MT" => "Mato Grosso", "PA" => "Pará",     "PB" => "Paraíba",          "PE" => "Pernambuco",
                     "PI" => "Piauí",       "PR" => "Paraná",   "RJ" => "Rio de Janeiro",   "RN" => "Rio Grande do Norte",
                     "RO" => "Rondônia",    "RR" => "Roraima",  "RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
                     "SE" => "Sergipe",     "SP" => "São Paulo","TO" => "Tocantins");

if($btn_acao == 'gravar') {
    //  Cada erro vai num item do array. Depois, na hora de mostrar, faz um 'implode'
    $msg_erro = array();
    $posto    = trim($_POST['posto']);
    if (!function_exists('anti_injection')) {
        function anti_injection($string) {
            $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
            return strtr(strip_tags(trim($string)), $a_limpa);
        }
    }

    if (!function_exists('is_email')) {
        function is_email($email=""){   // False se não bate...
            return (preg_match("/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+$/", $email));
        }
    }
    //  Função para conferir cada campo do $_POST, devolve 'false' ou o que colocar como último argumento
    if (!function_exists('check_post_field')) {
        function check_post_field($fieldname, $returns = false) {
            if (!isset($_POST[$fieldname])) return $returns;
            $data = anti_injection($_POST[$fieldname]);
        //  echo "<p><b>$fieldname</b>: $data</p>\n";
            return (strlen($data)==0) ? $returns : $data;
        }
    }
    //  Coloca aspas nos campo QUE PRECISAR. Usar só para campos BOOL ou string (char,varchar,text, etc.)
    //  Serve para, p.e., evitar que um valor 'null' seja NULL e não string 'null'
    if (!function_exists('pg_quote')) {
        function pg_quote($str, $type_numeric = false) {
            if (is_bool($str)) return ($str) ? 'true' : 'false';
            if (is_null($str)) return 'null';
            if (is_numeric($str) and $type_numeric) return $str;
            if (in_array($str,array('null','true','false','t','f'))) return $str;
            return "'".pg_escape_string($str)."'";
        }
    }

    $aux_cod_posto  = check_post_field('melhor_sistema');
    //  Campos não obrigatórios
    $aux_fax                    = check_post_field("fax","");
    $fabricantes                = check_post_field('fabricantes', '');
    $aux_atende_cidade_proxima  = check_post_field('atende_cidade_proxima','');
    $aux_marca_nao_autorizada   = check_post_field('marca_nao_autorizada', '');
    $aux_marca_ser_autorizada   = check_post_field('marca_ser_autorizada', '');
    $aux_melhor_sistema         = check_post_field('melhor_sistema', '');
    $aux_opcao_outras_fabricas  = check_post_field('fabricantes', '');
    $aux_observacao             = check_post_field('observacao', '');
    $opcao_outras_fabricas      = check_post_field('opcao_outras_fabricas', '');
    $aux_inf_comentario         = check_post_field('inf_comentario', '');

    //  Campos obrigatórios... quando é para dar um INSERT!

    if (checaCPF(anti_injection($_POST['cnpj']),false)  === false) $msg_erro[] = "Preencha/Verifique o campo CNPJ";
    if(($aux_nome           = check_post_field("nome")) === false) $msg_erro[] = "Preencha o campo Razão Social";
    if(($aux_cep            = check_post_field('cep')) === false) $msg_erro[] = "Preencha o campo CEP";
    if(($aux_endereco       = check_post_field('endereco')) === false) $msg_erro[] = "Preencha o campo Endereço";
    if(($aux_numero         = check_post_field('numero')) === false) $msg_erro[] = "Preencha o campo Número";
    if(($aux_bairro         = check_post_field('bairro')) === false) $msg_erro[] = "Preencha o campo Bairro";
    if(($aux_cidade         = check_post_field('cidade')) === false) $msg_erro[] = "Preencha o campo Cidade";
    if(($aux_estado         = check_post_field('estado')) === false) $msg_erro[] = "Preencha o campo Estado";
    if(($aux_complemento    = check_post_field("complemento"))  === false) {}; //$msg_erro[] = "Preencha o campo Complemento"; }
    if(($aux_email          = check_post_field('email')) === false) $msg_erro[] = "Preencha o campo E-Mail";
    if(($aux_telefone       = check_post_field('telefone')) === false) $msg_erro[] = "Preencha o campo Telefone";
    if(($aux_contato        = check_post_field("contato")) === false) $msg_erro[] = "Preencha o campo Contato";
    if(($aux_nome_fantasia  = check_post_field("nome_fantasia")) === false) $msg_erro[] = "Preencha o campo Nome Fantasia";
    $aux_ie                 = check_post_field("ie");
    // if(($aux_ie              = check_post_field("ie")) === false) $msg_erro[] = "Preencha o campo I.E";



    //Validações
    if(!is_email($aux_email)) $msg_erro[] = "O e-mail digitado ($aux_email) não é válido.";

    $descricao = check_post_field('descricao');

    if(($aux_funcionarios   = check_post_field('funcionarios','null')) != 'null') {
        if (!is_numeric($aux_funcionarios)) {
            $msg_erro[] = "Apenas números no campo Qtde. de funcionários ($aux_funcionarios).";
        }
    }
    if(($aux_oss = check_post_field("oss","null")) != "null") {
        if(!is_numeric($aux_oss)){
            $msg_erro[] = "Apenas números no campo Qtde. de Ordem de Serviço mensal.";
        }
    }

    if (checaCPF(anti_injection($_POST['cnpj']),false)!==false) $aux_cnpj = preg_replace('/\D/','',$_REQUEST['cnpj']);

    $aux_nome = $aux_nome;
    $aux_cep = $aux_cep;
    $aux_endereco = $aux_endereco;
    $aux_numero = $aux_numero;
    $aux_bairro = $aux_bairro;
    $aux_cidade = $aux_cidade;
    $aux_estado = $aux_estado;
    $aux_complemento = $aux_complemento;
    $aux_email = $aux_email;
    $aux_telefone = $aux_telefone;
    $aux_contato = $aux_contato;
    $aux_nome_fantasia = $aux_nome_fantasia;
    $aux_ie = $aux_ie;
    $descricao = $descricao;

    //  Atende linhas...
    $a_linhas[]= ($_POST['linha_1']) ? $linha_1 = $_POST['linha_1'] : "";
    $a_linhas[]= ($_POST['linha_2']) ? $linha_2 = $_POST['linha_2'] : "";
    $a_linhas[]= ($_POST['linha_3']) ? $linha_3 = $_POST['linha_3'] : "";
    $a_linhas[]= ($_POST['linha_4']) ? $linha_4 = $_POST['linha_4'] : "";
    $a_linhas[]= ($_POST['linha_5']) ? $linha_5 = $_POST['linha_5'] : "";
    $a_linhas[]= ($_POST['linha_6']) ? $linha_6 = $_POST['linha_6'] : "";
    $a_linhas[]= ($_POST['linha_7']) ? $linha_7 = $_POST['linha_7'] : "";
    $a_linhas[]= ($_POST['linha_6_obs']) ? $linha_6_obs = $_POST['linha_6_obs'] : "";
    if (count($a_linhas) == 0) $msg_erro[] = "Escolha ao menos uma LINHA de atuação.";
    $linhas = implode(",", array_filter($a_linhas));
    unset($a_linhas);

    $info_sistema_1 = $_POST['inf_sistema1'];
    $info_sistema_1 = str_replace("|","", $info_sistema_1);
    $info_sistema_2 = $_POST['inf_sistema2'];
    $info_sistema_2 = str_replace("|","", $info_sistema_2);
    $info_sistema_3 = $_POST['inf_sistema3'];
    $info_sistema_3 = str_replace("|","", $info_sistema_3);
    $info_sistema = $info_sistema_1."|".$info_sistema_2."|".$info_sistema_3;
    $info_sistema_verifica = $info_sistema_1.$info_sistema_2.$info_sistema_3;


    $info_marca_1 = $_POST['inf_marca1'];
    $info_marca_1 = str_replace("|","", $info_marca_1);
    $info_marca_2 = $_POST['inf_marca2'];
    $info_marca_2 = str_replace("|","", $info_marca_2);
    $info_marca_3 = $_POST['inf_marca3'];
    $info_marca_3 = str_replace("|","", $info_marca_3);
    $info_marca   = $info_marca_1."|".$info_marca_2."|".$info_marca_3;
    $info_marca_verifica = $info_marca_1.$info_marca_2.$info_marca_3;

    $info_vantagem_1 = $_POST['inf_vantagem1'];
    $info_vantagem_1 = str_replace("|","", $info_vantagem_1);
    $info_vantagem_2 = $_POST['inf_vantagem2'];
    $info_vantagem_2 = str_replace("|","", $info_vantagem_2);
    $info_vantagem_3 = $_POST['inf_vantagem3'];
    $info_vantagem_3 = str_replace("|","", $info_vantagem_3);
    $info_vantagem   = $info_vantagem_1."|".$info_vantagem_2."|".$info_vantagem_3;
    $info_vantagem_verifica = $info_vantagem_1.$info_vantagem_2.$info_vantagem_3;

    if (empty($_POST['outros_sistema']) or $_POST['outros_sistema'] == "N") {
        $info_sistema = '';
        $info_marca = '';
        $info_vantagem = '';
    }

    $sql = "SELECT posto
                FROM tbl_posto
                LEFT JOIN tbl_posto_extra using(posto)
                WHERE cnpj = '$aux_cnpj'
                ORDER BY posto DESC LIMIT 1";
    $res = pg_query($con,$sql);

    $posto = (is_resource($res) and @pg_numrows($res)==1) ? pg_fetch_result($res,0,posto) : '';

    $aux_cnpj          = preg_replace('/\D/','', $aux_cnpj);
    $aux_cep           = preg_replace('/\D/','', $aux_cep);
    $aux_telefone      = preg_replace('/(\d\d)(\d{4})(\d{4})/','($1) $2-$3', $aux_telefone);
    $aux_telefone      = str_replace(["(",")","-",],"", $aux_telefone);
    $aux_fax           = preg_replace('/(\d\d)(\d{4})(\d{4})/','($1) $2-$3', $aux_fax);
    $aux_fax           = str_replace(["(",")","-",],"", $aux_fax);

    if (!empty($_POST['total_fab'])) {
        $total_fab_post = (int) $_POST['total_fab'];
        $aux_fabrica = array();
        $aux_marca = array();
        for ($i = 0; $i <  $total_fab_post; $i++) {
            if (!empty($_POST['fabrica_' . $i])) {
                $tmp = explode(':', $_POST['fabrica_' . $i]);
                switch ($tmp[0]) {
                    case 'f':
                        $aux_fabrica[] = $tmp[1];
                        break;
                    case 'm':
                        $aux_marca[] = $tmp[1];
                        break;
                }
            }
        }
        if (empty($aux_fabrica) and empty($aux_marca)) {
            if ($opcao_outras_fabricas == "''") {
                $msg_erro[] = 'Selecione uma fabrica que gostaria de ser credenciado.';
            } else {
                $and_fabrica_credenciadas = "{}";
                $and_marcas_credenciadas = "{}";
            }
        } else {
            if (!empty($aux_fabrica)) {

                /*
                $sqlCred = "SELECT fabrica
                                FROM tbl_posto_fabrica
                                WHERE credenciamento = 'CREDENCIADO'
                                AND fabrica IN (81, 3, 35, 50, 101, 117, 156, 99, 86, 124, 90, 15, 140, 11, 72, 40, 151, 80, 24, 91) and
                                posto = $posto
                            ";
                $resCred = pg_query($con, $sqlCred);
                $arrayCred = pg_fetch_all_columns($resCred);

                $cred_fabrica = array_diff($aux_fabrica,$arrayCred);
                */

                $and_fabrica_credenciadas = "{" . implode(', ', $aux_fabrica) . "}";

            } else {
                $and_fabrica_credenciadas = "{}";
            }

            if (!empty($aux_marca)) {
                $and_marcas_credenciadas = "{" . implode(', ', $aux_marca) . "}";
            } else {
                $and_marcas_credenciadas = "{}";
            }
        }

    }

    $verifica_posto = 0;

    $sql_posto = "SELECT posto FROM tbl_posto WHERE cnpj = $aux_cnpj";
    $res_posto = pg_query($con, $sql_posto);
    if (pg_num_rows($res_posto) == 0) {
        $posto = '0';
    } else {
        $posto = pg_fetch_result($res_posto, 0, 'posto');
    }

    $sql_cpnj = "SELECT posto FROM tbl_posto_alteracao WHERE cnpj = '$aux_cnpj'";
    $res_cnpj = pg_query($con, $sql_cpnj);
    if (pg_num_rows($res_cnpj) > 0) {
        $verifica_posto = 1;
    }

    $condicao_1 = $_POST['condicao_1'];
    $condicao_2 = $_POST['condicao_2'];
    $condicao_3 = $_POST['condicao_3'];

    if(strlen($condicao_1) == 0 && strlen($condicao_2) == 0 && strlen($condicao_3) == 0){
        $msg_erro[] = "Escolha pelo menos uma opção em que o POSTO TEM CONDICÕES DE ATENDER";
    }

    $condicao_1 = (strlen($condicao_1) > 0) ? "t" : "f";
    $condicao_2 = (strlen($condicao_2) > 0) ? "t" : "f";
    $condicao_3 = (strlen($condicao_3) > 0) ? "t" : "f";

    if(count($msg_erro) == 0 AND $verifica_posto == 0) {

        $dados_post = [
                       "posto"=> $posto,
                       "fabrica"=> 10,
                       "razao_social"=> utf8_encode($aux_nome),
                       "cnpj"=> $aux_cnpj,
                       "ie"=> $aux_ie,
                       "endereco"=> utf8_encode($aux_endereco),
                       "numero"=> $aux_numero,
                       "complemento"=> utf8_encode($aux_complemento),
                       "bairro"=> utf8_encode($aux_bairro),
                       "cep"=> $aux_cep,
                       "cidade"=> utf8_encode($aux_cidade),
                       "estado"=> $aux_estado,
                       "email"=> $aux_email,
                       "fone"=> $aux_telefone,
                       "fax"=> $aux_fax,
                       "contato"=> $aux_contato,
                       "nome_fantasia"=> utf8_encode($aux_nome_fantasia),
                       "linhas"=> utf8_encode($linhas),
                       "funcionario_qtde"=> $aux_funcionarios,
                       "os_qtde"=> $aux_oss,
                       "atende_cidade_proxima"=> utf8_encode($aux_atende_cidade_proxima),
                       "marca_ser_autorizada"=> $opcao_outras_fabricas,
                       "marca_nao_autorizada"=> $aux_marca_nao_autorizada,
                       "melhor_sistema"=> utf8_encode($aux_cod_posto),
                       "outras_fabricas"=> utf8_encode($aux_opcao_outras_fabricas),
                       "fabrica_credenciada"=> $and_fabrica_credenciadas,
                       "marca_credenciada"=> utf8_encode($and_marcas_credenciadas),
                       "observacao"=> utf8_encode($aux_descricao),
                       "informacao_sistema"=> $info_sistema,
                       "informacao_marca"=> $info_marca,
                       "informacao_vantagem"=> $info_vantagem,
                       "informacao_comentario"=> utf8_encode($aux_inf_comentario),
                       "auto_credenciamento"=> "true",
                       "banner"=> "false",
                       "visita_tecnica"=> $condicao_1,
                       "atende_consumidor_balcao"=> $condicao_2,
                       "atende_revendas"=> $condicao_3
                      ];

        $dados_post = json_encode($dados_post);

        $return_gravar = gravaCredenciado($dados_post);

        if(strlen($msg_erro_insert) > 0){
            $msg_erro[] = "Erro ao gravar os dados no sistema.<br>Tente novamente."; // $sql - $msg_erro_insert";
            pg_rollback();
        }

    } else {

        if (count($msg_erro) == 0) {


            $dados_update = [
                                "razao_social"             => utf8_encode($aux_nome),
                                "posto"                    => $posto,
                                "cnpj"                     => $aux_cnpj,
                                "fabrica"                  => 10,
                                "ie"                       => $aux_ie,
                                "endereco"                 => utf8_encode($aux_endereco),
                                "numero"                   => $aux_numero,
                                "complemento"              => utf8_encode($aux_complemento),
                                "bairro"                   => utf8_encode($aux_bairro),
                                "cep"                      => $aux_cep,
                                "cidade"                   => utf8_encode($aux_cidade),
                                "estado"                   => $aux_estado,
                                "email"                    => $aux_email,
                                "fone"                     => $aux_telefone,
                                "fax"                      => $aux_fax,
                                "contato"                  => $aux_contato,
                                "nome_fantasia"            => utf8_encode($aux_nome_fantasia),
                                "linhas"                   => utf8_encode($linhas),
                                "funcionario_qtde"         => $aux_funcionarios,
                                "os_qtde"                  => $aux_oss,
                                "atende_cidade_proxima"    => utf8_encode($aux_atende_cidade_proxima),
                                "marca_nao_autorizada"     => $aux_marca_nao_autorizada,
                                "marca_ser_autorizada"     => $opcao_outras_fabricas,
                                "melhor_sistema"           => utf8_encode($aux_melhor_sistema),
                                "informacao_sistema"       => $info_sistema,
                                "informacao_marca"         => $info_marca,
                                "informacao_vantagem"      => $info_vantagem,
                                "informacao_comentario"    => $aux_inf_comentario,
                                "outras_fabricas"          => $aux_opcao_outras_fabricas ,
                                "fabrica_credenciada"      => $and_fabrica_credenciadas,
                                "marca_credenciada"        => $and_marcas_credenciadas,
                                "observacao"               => utf8_encode($aux_descricao),
                                "auto_credenciamento"      => 't',
                                "visita_tecnica"           => $condicao_1,
                                "atende_consumidor_balcao" => $condicao_2,
                                "atende_revendas"          => $condicao_3
                            ];

            $dados_update = json_encode($dados_update);

            $retun_put = atualizaCredenciado($dados_update);

            if (isset($retun_put["message"])) {
                    $msg_erro[] = $retun_put["message"];
                    pg_rollback();
            } else {

                $xcnpj = str_replace(["-","/","."], "", $aux_cnpj);
                $dados_posto = getPostoCadastrado($xcnpj);

                if (!isset($dados_posto["message"])) {
                    $dados_update_posto = [
                                            "endereco"    => utf8_encode($aux_endereco),
                                            "cnpj"        => $aux_cnpj,
                                            "numero"      => $aux_numero,
                                            "complemento" => utf8_encode($aux_complemento),
                                            "bairro"      => utf8_encode($aux_bairro),
                                            "cep"         => $aux_cep,
                                            "cidade"      => utf8_encode($aux_cidade),
                                            "estado"      => $aux_estado,
                                            "email"       => $aux_email,
                                            "fone"        => $aux_telefone,
                                            "fax"         => $aux_fax,
                                            "contato"     => $aux_contato
                                          ];

                    $dados_update_posto = json_encode($dados_update_posto);
                        
                    $retun_put = atualizaPosto($dados_update_posto);
                    
                } 

            }
        }

    }
    //echo nl2br($aux_descricao);
    #echo nl2br($sql);exit;
    if(count($msg_erro) == 0){
        $config["tamanho"] = 2*1024*1024;

        $nome_foto__cnpj    = preg_replace('/\D/','',utf8_decode($aux_cnpj));

        for($i = 1; $i < 4; $i++){
            if ($_FILES["arquivo$i"]['name']=='') continue; //  Próxima iteração se não há arquivo definido
            $arquivo    = $_FILES["arquivo$i"];
            if ($debug) {echo "<p>Imagem para o posto $posto, Erros: ".count($msg_erro)."<br><pre>".var_dump($arquivo)."</pre></p>";}

            // Formulário postado... executa as ações
            if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
                // Verifica o MIME-TYPE do arquivo
                if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
                    $msg_erro[] = "Arquivo em formato inválido!";
                }
                // Verifica tamanho do arquivo
                if ($arquivo["size"] > $config["tamanho"])
                    $msg_erro[] = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";

                if (count($msg_erro) == 0) {
                    // Pega extensão do arquivo
                    preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
                    $aux_extensao = "." . $ext[1];
                    $aux_extensao = strtolower($aux_extensao);
                    // Gera um nome único para a imagem
                    $nome_anexo = $nome_foto__cnpj . "_" .$i . $aux_extensao;

                if ($debug) echo "<p>Gravando a imagem $i como $nome_anexo...</p>";
                    // Exclui anteriores, qquer extensao
                    @unlink($imagem_dir);

                    // Faz o upload da imagem
                    if (count($msg_erro) == 0) {
                        $thumbail = new resize( "arquivo$i", 600, 400 );
                        $thumbail -> saveTo($nome_anexo,$caminho_imagem);
                    }
                }
            }
        }
    }

    if(count($msg_erro) == 0){
        pg_commit();
        $msg_ok = "OK";

        $fabricas_repl = preg_replace("/[\{\}']/", "", $and_fabrica_credenciadas);
        $marcas_repl = preg_replace("/[\{\}']/", "", $and_marcas_credenciadas);

        $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica IN ($fabricas_repl)";
        if (!empty($marcas_repl)) {
            $sql.= " UNION  SELECT nome FROM tbl_marca WHERE marca IN ($marcas_repl) ";
        }
        $sql.= " ORDER BY nome";
        $qry = pg_query($con, $sql);
        $fabricas_interesse = array();
        while ($fetch = pg_fetch_assoc($qry)) {
            $fabricas_interesse[] = $fetch['nome'];
        }

        if(!empty($_REQUEST['fabrica']) and ($_REQUEST['fabrica'] == 124 or $_REQUEST['fabrica'] == 126)) {
            $sql = "  select  array_to_string(array_agg(email),',') from tbl_admin where fabrica = ".$_REQUEST['fabrica']. " and privilegios='*' and help_desk_supervisor;";
            $res1 = pg_query($con,$sql);
            $email_destino = pg_fetch_result($res1,0,0);
        }else{
            $email_destino = "sac@telecontrol.com.br";
        }
               
        $email_origem  = "suporte.fabricantes@telecontrol.com.br";
        $assunto       = "AUTO CREDENCIAMENTO - " . $nome ;
        // $body_top = "--Message-Boundary\n";
        // $body_top .= "Content-type: text/html; charset=utf-8\n";
        // $body_top .= "Content-transfer-encoding: 7BIT\n";
        // $body_top .= "Content-description: Mail message body\n\n";
        $corpo = "Foi feito um auto cadastramento no Telecontrol, segue os dados:";
        $corpo.= "<br><br>Posto: <b>$nome</b>";
        $corpo.= "<br>CNPJ: <b>$cnpj</b>";
        $corpo.= "<br>Cidade: <b>$cidade</b>";
        $corpo.= "<br>Estado: <b>$estado</b>";
        $corpo.= "<br>Linhas: <b>$linhas</b>";
        $corpo.= '<br>Fabricas de interesse: <b>' . implode(', ', $fabricas_interesse);
        if ($opcao_outras_fabricas != "''") {
            if (!empty($fabricas_interesse)) {
                $corpo.= ', ';
            }
            $corpo.= str_replace("'", "", $opcao_outras_fabricas);
        }
        $corpo.= '</b>';
        $corpo.= "<br>Qtde. Funcionários: <b>$aux_funcionarios</b>";
        $corpo.= "<br>Qtde. OS / mês: <b>" . str_replace("null", "", $aux_oss) . "</b>";
        $corpo.= "<br><br>_______________________________________________\n";
        $corpo.= "<br><br>Telecontrol\n";
        $corpo.= "<br>www.telecontrol.com.br\n";
            
        require dirname(FILE) .'/../class/communicator.class.php';

        $mailTc = new TcComm('smtp@posvenda');

        $res = $mailTc->sendMail(
                $email_destino,
                $assunto,
                $corpo,
                $email_origem
            );

    } else {
        pg_rollback();
    }
}
?>
