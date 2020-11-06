<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';

include_once '../../class/communicator.class.php';
include_once "../../class/aws/s3_config.php";
include_once S3CLASS;

$login_fabrica = 158;
$admin = 7901;
$dataHoje = date('Y-m-d');

$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica AND parametros_adicionais IS NOT NULL ";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
    $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true); // true para retornar ARRAY e não OBJETO
    extract($parametros_adicionais); // igual o foreach, mais eficiente (processo interno do PHP)

    if (!$externalId) {
        $externalId    = 'smtp@posvenda';
        $externalEmail = 'noreply@telecontrol.com.br';
    }
}

//157140760107
if (array_key_exists("numero_serie", $_GET)) {
    $sql = "SELECT tbl_produto.produto,referencia||' - '||descricao  as descricao, tbl_produto.voltagem
	FROM tbl_numero_serie
	JOIN tbl_produto ON tbl_numero_serie.produto = tbl_produto.produto
	JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
	WHERE tbl_linha.nome = 'REFRIGERADOR' AND tbl_numero_serie.serie = '" . $_GET['term'] . "' LIMIT 1;";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $produto = pg_fetch_array($res);

        echo json_encode(array(
            "produto" => $produto['produto'],
            "descricao" => utf8_encode($produto['descricao']),
            "voltagem" => utf8_encode($produto['voltagem']),
        ));
    } else {
        echo json_encode(array());
    }


    exit;
}

if (array_key_exists("produto", $_GET)) {


    $sql = "SELECT produto,referencia||' - '||descricao  as descricao
	FROM tbl_produto 
	JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha 
	WHERE tbl_linha.nome = 'REFRIGERADOR' AND (referencia ILIKE('%" . $_GET['term'] . "%') OR descricao ILIKE('%" . $_GET['term'] . "%')) LIMIT 20;";

    $res = pg_query($con, $sql);


    if (pg_num_rows($res) > 0) {

        $produtos = pg_fetch_all($res);

        $response = array();
        foreach ($produtos as $produto) {
            $response[] = array(
                "id" => $produto['produto'],
                "value" => utf8_encode($produto['descricao']),
            );
        }

    } else {
        echo json_encode(array());
    }

    echo json_encode($response);
    exit;
}

	if(isset($_POST["id"])){
			$produto = $_POST["id"];
			$option = "<option value=''>Selecione o defeito</option>";
			if (!empty($produto)) {
	 				$sql = "SELECT DISTINCT tbl_defeito_reclamado.descricao,
                            tbl_defeito_reclamado.defeito_reclamado
                        FROM tbl_diagnostico
                            JOIN tbl_defeito_reclamado
                                ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
                            JOIN tbl_produto
                                ON tbl_diagnostico.familia = tbl_produto.familia
                        WHERE tbl_diagnostico.fabrica = $login_fabrica
                            AND tbl_diagnostico.ativo IS TRUE
                            AND tbl_produto.produto = $produto
                        UNION
                        SELECT DISTINCT tbl_defeito_reclamado.descricao,
                            tbl_familia_defeito_reclamado.defeito_reclamado
                        FROM tbl_familia_defeito_reclamado
                            JOIN tbl_defeito_reclamado
                                ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado
                            AND tbl_defeito_reclamado.fabrica = $login_fabrica
                        ORDER BY 1";

                        $res = pg_query($con, $sql);

		                if( pg_num_rows($res) > 0 ){
		                    for( $i=0; $i<pg_num_rows($res); $i++ ){
		                        $defeito_reclamado = pg_result($res, $i, defeito_reclamado);
		                        $descricao         = pg_result($res, $i, descricao);
		                        $option .= "<option value='$defeito_reclamado'>$descricao</option>";
		                    }
		                }

            } else {
            	$option = '<option value="">Digite primeiro o Produto acima</option>';
            }

            exit($option);            
	}


/**
 * Area para colocar os AJAX
 */
if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados())) {
        $sql = "SELECT DISTINCT * FROM (
					SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
					UNION (
						SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
					)
				) AS cidade
				ORDER BY cidade ASC";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("estado não encontrado"));
    }

    exit(json_encode($retorno));
}

if (isset($_POST['ajax_busca_cep']) && !empty($_POST['cep'])) {

    require_once '../../classes/cep.php';
    $cep = $_POST['cep'];

    try {
        $retorno = CEP::consulta($cep);
        $cidade = $retorno['cidade'];
        $estado = $retorno['uf'];

        $sql = "SELECT DISTINCT * FROM (
                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade, cidade as cidade_id FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')AND UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}'))
                ) AS cidade
                ORDER BY cidade ASC";

        $res = pg_query($con, $sql);

        $cidade_id = pg_result($res, 0, cidade_id);

        $retorno["cidade_id"] = $cidade_id;

        $retorno = array_map('utf8_encode', $retorno);

    } catch (Exception $e) {
        $retorno = array("error" => utf8_encode($e->getMessage()));
    }



    exit(json_encode($retorno));
}


$array_estado = array(
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AM' => 'Amazonas',
    'AP' => 'Amapá',
    'BA' => 'Bahia',
    'CE' => 'Ceara',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MG' => 'Minas Gerais',
    'MS' => 'Mato Grosso do Sul',
    'MT' => 'Mato Grosso',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí­',
    'PR' => 'Paraná',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'RS' => 'Rio Grande do Sul',
    'SC' => 'Santa Catarina',
    'SE' => 'Sergipe',
    'SP' => 'São Paulo',
    'TO' => 'Tocantins'
);

function validaCep()
{
    global $_POST;

    $cep = $_POST["cep"];

    if (!empty($cep)) {
        try {
            $endereco = CEP::consulta($cep);

            if (!is_array($endereco)) {
                throw new Exception("CEP inválido");
            }
        } catch (Exception $e) {
            throw new Exception("CEP inválido");
        }
    }
}

function validaEstado()
{
    global $array_estado, $_POST;

    $estado = strtoupper($_POST["estado"]);

    if (!empty($estado) && !in_array($estado, array_keys($array_estado))) {
        throw new Exception("Estado inválido");
    }
}

function validaCidade()
{
    global $con, $_POST;

    $cidade = utf8_decode($_POST["cidade"]);
    $estado = strtoupper($_POST["estado"]);

    if (!empty($cidade) && !empty($estado)) {
        $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' AND UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}'))";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Cidade não encontrada" . $sql);
        }
    }
}

function validaEmail()
{
    global $_POST;

    $email = $_POST["email"];

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }
}

if ($_POST["ajax_enviar"]) {

    /* Parte que faz a escolha do admin, para cada atendimento é escolhido o admin que tem menor qtde de atendimento    */
    $sqlBuscaAdmin = "SELECT admin, email FROM tbl_admin WHERE fabrica = $login_fabrica AND cliente_admin_master IS TRUE AND ativo IS TRUE";
    $lista_emails = array(); /* HD-3856247 07/11/2017 */
    $resBuscaAdmin = pg_query($con, $sqlBuscaAdmin);
    for($a=0; $a<pg_num_rows($resBuscaAdmin); $a++){
        $adminId      = pg_fetch_result($resBuscaAdmin, $a, admin);
        $email        = pg_fetch_result($resBuscaAdmin, $a, email);
        $lista_emails[$a] = $email; 

        $sqlCount = "SELECT count(1) as qtde_atendimento from tbl_hd_chamado where fabrica = $login_fabrica and atendente = $adminId and data between '$dataHoje 00:00:00' and '$dataHoje 23:59:59'";
        $resCount = pg_query($con, $sqlCount);
            $dados["$adminId"] = pg_fetch_result($resCount, 0, qtde_atendimento);
            $dadosEmail["$adminId"] = $email;
    }
    asort($dados);
    $atendentes = array_keys($dados);
    $atendenteEscolhido = array_shift($atendentes);
    $email_admin_escolhido = $dadosEmail[$atendenteEscolhido];

    
    /*Fim escolha atendente.*/

    $regras = array(
        "nome",
        "cpf-cnpj",
        "email",
        "telefone",
        "telefone-celular",
        "cep",
        "endereco",
        "numero",
        "bairro",
        "estado",
        "cidade",
        "produto",        
        "problema",
        "defeito",
        "voltagem",
        "numero-serie"
    );

    $msg_erro = array(
        "msg" => array(),
        "campos" => array()
    );

    foreach ($regras as $campo) {
        $input = trim($_POST[$campo]);

        if (empty($input) || $input == "") {
            $msg_erro["msg"] = utf8_encode("Preencha todos os campos obrigatórios");
            $msg_erro["campos"][] = $campo;
        }
    }

    if (count($msg_erro["msg"]) > 0) {
        $retorno = array("erro" => $msg_erro);
    } else {
        $nome = trim($_POST["nome"]);
        $cpf_cnpj = trim($_POST["cpf-cnpj"]);
        $rg = trim($_POST["rg"]);
        $email = trim($_POST["email"]);
        $telefone = trim($_POST["telefone"]);
        $telefone_celular = trim($_POST["telefone-celular"]);
        $cep = trim($_POST["cep"]);
        $endereco = utf8_decode($_POST["endereco"]);
        $numero = trim($_POST["numero"]);
        $bairro = utf8_decode($_POST["bairro"]);
        $complemento = utf8_decode($_POST["complemento"]);
        $estado = trim($_POST["estado"]);
        $cidade = trim($_POST["cidade"]);
        $numero_serie = trim($_POST["numero-serie"]);
        $produto = trim($_POST["produto-id"]);
        $tensao = trim($_POST["tensao"]);
        $tempo_instalacao = trim($_POST["tempo-instalacao"]);
        $problema = utf8_decode($_POST["problema"]);
        $serie = $_POST['numero-serie'];
        $data_nf = $_POST['data-nf'];
        $numero_nf = $_POST['numero-nf'];
        $defeito = $_POST['defeito'];
        $voltagem = $_POST['voltagem'];

        if(strlen(trim($data_nf))>0){
            $data_sql =  "'". implode("-",array_reverse(explode("/",$data_nf)))."'";
        }else{
            $data_sql = 'null';
        }        

        $sql = "SELECT fn_valida_cnpj_cpf('{$cpf_cnpj}');";

        $res = pg_query($con, $sql);
        $res = pg_fetch_all($res);

        if ($res[0]['fn_valida_cnpj_cpf'] != 't') {
            $msg_erro["msg"] = utf8_encode("CPF/CNPJ Inválido");
            $msg_erro["campos"][] = "cpf-cnpj";

            $retorno = array("erro" => $msg_erro);
            exit(json_encode($retorno));

        }


//        $contato_setor = array(
//            "venda" => array("descricao" => "Vendas", "email" => "vendas@esab.com.br"),
//            "centro_suporte" => array("descricao" => "Centro de Suporte ao Cliente ESAB", "email" => "faleconosco@esab.com.br"),
//            "certificado" => array("descricao" => "Certificados de Consumiveis", "email" => "alexson.santos@esab.com.br"),
//            "revista" => array("descricao" => "Revista Solução", "email" => "marketing@esab.com.br"),
//            "outro" => array("descricao" => "Outros", "email" => "faleconosco@esab.com.br")
//        );


        $familia = "REFRIGERADOR";
	$descricao_produto = "REFRIGERADOR";
        $produto = utf8_decode(trim($_POST["produto-id"]));

        #if (!empty($familia)) {
        #    $sql = "SELECT descricao FROM tbl_familia WHERE familia = $familia";
        #    $res = pg_query($con, $sql);

        #    $descricao_familia = pg_fetch_result($res, 0, "descricao");
        #}

        if (!empty($produto)) {
            $sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
            $res = pg_query($con, $sql);

            $descricao_produto = pg_fetch_result($res, 0, "descricao");
        }

        try {
            pg_query($con, "BEGIN");

            $sql = "INSERT INTO tbl_hd_chamado (
							admin,
							data,
							atendente,
							fabrica_responsavel,
							fabrica,
							titulo,
							status
						) VALUES (
							$admin,
							CURRENT_TIMESTAMP,
							$atendenteEscolhido,
							{$login_fabrica},
							{$login_fabrica},
							'Atendimento Fale Conosco',
							'Aberto'
						) RETURNING hd_chamado";
            
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao abrir o atendimento...");
            }

            $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

            $sql = "SELECT cidade FROM tbl_cidade WHERE cidade = {$cidade}";
            $res = pg_query($con, $sql);


            if (pg_num_rows($res) > 0) {
                $cidade_id = pg_fetch_result($res, 0, "cidade");
            } else {
                throw new \Exception("Cidade não encontrada");
            }

            $cep = preg_replace("/\D/", "", $cep);

            $coluna = "";
            $value_coluna = "";


            $coluna = "array_campos_adicionais";
            $value_coluna = json_encode(array(
                "tensao" => $tensao,
                "tempo_instalacao" => $tempo_instalacao,
                "voltagem" => $voltagem
            ));

            $sql = "INSERT INTO tbl_hd_chamado_extra (
							hd_chamado,
							nome,
							email,
							fone,
							celular,
							cep,
							cidade,
							bairro,
							endereco,
							numero,
							complemento,
							produto,
                            serie,
                            data_nf,
                            nota_fiscal,
                            defeito_reclamado,
                            cpf,
							{$coluna}
						) VALUES (
							{$hd_chamado},
							'{$nome}',
							'{$email}',
							'{$telefone}',
							'{$telefone_celular}',
							'{$cep}',
							{$cidade_id},
							'{$bairro}',
							'{$endereco}',
							'{$numero}',
							'{$complemento}',
							{$produto},
                            '{$serie}',
                            $data_sql,
                            '{$numero_nf}',
                            {$defeito},
                            '{$cpf_cnpj}',
							'{$value_coluna}'
						)";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception(utf8_encode("Erro ao abrir o atendimento."));
            }

            $sql = "INSERT INTO tbl_hd_chamado_item (
							hd_chamado,
							admin,
							comentario,
                            voltagem
						) VALUES (
							{$hd_chamado},
							$admin,
							'{$problema}',
                            '{$voltagem}'
						)";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao abrir o atendimento..");
            }

            

            $s3 = new AmazonTC('callcenter', (int) $login_fabrica);

            if ($_FILES['anexo']) {
                $types      = array('pdf');
                $i          = 1; //apenas um anexo
                $file       = $_FILES[key($_FILES)];
                $type  = trim(strtolower(preg_replace('/.+\./', '', $file['name'])));

                if (count($_FILES) > 0) {
                    if ($file['size'] <= 2097152) {

                        if (strlen($file['tmp_name']) > 0 && $file['size'] > 0) {
                            if (!in_array($type, $types)) {
                               $retorno = array('erro' => utf8_encode('Formato inválido, aceito somente o  formato: PDF'));
                            } else {
                                $s3->upload("{$hd_chamado}-{$i}", $file, null, null);

                                if ($type != 'pdf') {
                                    $file_mini = $s3->getLink("thumb_{$hd_chamado}-{$i}.{$type}", false, null, null);
                                }
                                $file = $s3->getLink("{$hd_chamado}-{$i}.{$type}", false, null, null);
                            }
                        } else {
                            $retorno =  array('erro' => 'Erro ao fazer o upload do arquivo');
                        }
                        
                    }else {
                        $retorno = array('erro' => utf8_encode('O arquivo deve ter no máximo 2Mb'));
                    }
                }
            }

            if(!isset($retorno['erro'])){
                pg_query($con, "COMMIT");

                $mailTc = new TcComm($externalId);//classe
                $assunto = "Fale Conosco -Imbera";
                $mensagem = "O protocolo $hd_chamado foi aberto através do Fale Conosco.";

                foreach ($lista_emails as $atual_email) {
                    $res = $mailTc->sendMail(
                        $atual_email,
                        $assunto,
                        $mensagem,
                        $externalEmail
                    );
                }

                $retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
            }

            
        } catch (Exception $e) {
            $msg_erro["msg"][] = $e->getMessage();
            $retorno = array("erro" => $msg_erro);
            pg_query($con, "ROLLBACK");
        }
    }

    exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_cidades"]) {
    $estado = strtoupper(trim($_GET["estado"]));

    if (empty($estado)) {
        $retorno = array("erro" => utf8_encode("Estado não informado"));
    } else {
        $sql = "SELECT  DISTINCT ON(nome) cidade,nome FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' ORDER BY nome ASC";

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            $retorno = array("erro" => "Erro ao carregar cidades");
        } else {
            $retorno = array("cidades" => array());

            while ($cidade = pg_fetch_object($res)) {
                $retorno["cidades"][] = array("id" => $cidade->cidade, "text" => utf8_encode(strtoupper($cidade->nome)));
            }
        }
    }

    exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_produto"]) {
    $familia = trim($_GET["familia"]);

    if (empty($familia)) {
        $retorno = array("resultado" => false, "erro" => utf8_encode("Família não informado"));
    } else {
        $sql = "SELECT DISTINCT produto, descricao FROM tbl_produto WHERE familia = {$familia} ORDER BY descricao ASC";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
            $retorno = array("erro" => utf8_encode("Erro ao carregar os produtos da família"));
        } else {
            $retorno = array();
            while ($array_produto = pg_fetch_object($res)) {
                $retorno[] = array(
                    "produto" => $array_produto->produto,
                    "descricao" => utf8_encode($array_produto->descricao)
                );
            }
        }
    }

    exit(json_encode($retorno));
}
?>

<!DOCTYPE html />
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
    <meta name="language" content="pt-br"/>

    <!-- jQuery -->
    <script type="text/javascript" src="plugins/jquery-1.11.3.min.js"></script>



    <script type="text/javascript" src="../../plugins/jquery.form.js"></script>

    <!-- Bootstrap -->
    <script type="text/javascript" src="plugins/bootstrap/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="plugins/bootstrap/css/bootstrap.min.css"/>

    <!-- Plugins Adicionais -->
    <script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
    <script type="text/javascript" src="../../js/jquery.maskedinput2.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
    <script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
    <script type="text/javascript" src="../../plugins/select2/select2.js"></script>
    <script type="text/javascript" src="plugins/jquery-ui/jquery-ui.min.js"></script>

    <link rel="stylesheet" type="text/css" href="../../plugins/select2/select2.css"/>

    <link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css"/>
    <link rel="stylesheet" type="text/css" href="plugins/jquery-ui/jquery-ui.css"/>
    <!--    CSS EXTRAIDO DO SITE DO FABRICANTE-->
    <link rel="stylesheet" type="text/css" href="../css/callcenter_imbera.css"/>
    <!--    CSS EXTRAIDO DO SITE DO FABRICANTE-->

    <!--    AJUSTES-->
    <style>
        .select2-selection__rendered {
            background: #EFEFEF;
            height: 36px;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
        }

        .input-error {
            background: #EFC5C5 !important;
        }
    </style>

    <script>

        var cidade_id = null;
        $(function () {
            $("#telefone-celular").maskedinput("(99)99999-9999");
            $("#telefone").maskedinput("(99)9999-9999");
            $("#data-nf").maskedinput("99/99/9999");

            $("#estado").select2();
            $("#estado").select2().change(function () {
                carregaCidades($("#estado").val());
            });

            $("#numero-serie").change(function () {

                if ($(this).val().trim() != "") {
                    $.ajax("callcenter_cadastra_imbera.php?numero_serie", {
                        data: {
                            term: $("#numero-serie").val()
                        }
                    }).done(function (response) {
                        response = JSON.parse(response);

                        if (response.descricao != "") {
                            console.log(response.produto);
                            console.log($("#produto-id"));
                            $("#produto-id").val(response.produto);
                            $("#produto").val(response.descricao);
                            $("#tensao").val(response.voltagem);
                        }
                    });
                }
            });

            $("#produto").autocomplete({
                source: "callcenter_cadastra_imbera.php?produto",
                select: function (event, ui) {
                    console.log(ui);
                    $("#produto-id").val(ui.item.id);
                }
            });

//            $("#cep").change(function() {
//                if ($(this).attr("readonly") == undefined) {
//                    busca_cep($(this).val());
//                }
//            });

             $("#send").click(function () {
                 $("#send").val("Enviando...");

                 $("form").ajaxForm({
                    complete: function(data){
                        $("#send").val("ENVIAR INFORMAÇÕES");
                        data = $.parseJSON(data.responseText);
                        
                        if (data.erro) {
                            $("#alert-env").html("<div class='alert alert-warning'>" + data.erro.msg + "</div>");
                            if (data.erro.campos != undefined) {
                                $(data.erro.campos).each(function (idx, elem) {
                                    console.log(elem);
                                    setError("#" + elem);
                                });
                            }else{
                                $("#alert-env").html("<div class='alert alert-danger'>"+data.erro+"</b></div>");
                            }
                        }else{
                            $("#alert-env").html("<div class='alert alert-success'>Protocolo criado com sucesso: #<b>" + data.hd_chamado + "</b></div>");
                            $("input").val("");
                            $("textarea").val("");
                            $("textarea").html("");
                            $("#send").val("ENVIAR INFORMAÇÕES");                            
                        }

                     }
                }); 

                /*$.ajax("callcenter_cadastra_imbera.php", {
                    method: "POST",
                    data: {
                        ajax_enviar: true,
                        nome: $("#nome").val(),
                        "cpf-cnpj": $("#cpf-cnpj").val(),
                        rg: $("#rg").val(),
                        email: $("#email").val(),
                        telefone: $("#telefone").val(),
                        "telefone-celular": $("#telefone-celular").val(),
                        cep: $("#cep").val(),
                        endereco: $("#endereco").val(),
                        numero: $("#numero").val(),
                        bairro: $("#bairro").val(),
                        estado: $("#estado").val(),
                        cidade: $("#cidade").val(),
                        "numero-serie": $("#numero-serie").val(),
                        produto: $("#produto-id").val(),
                        tensao: $("#tensao").val(),
                        "tempo-instalacao": $("#tempo-instalacao").val(),
                        problema: $("#problema").val(),
                        "data-nf": $("#data-nf").val(),
                        "numero-nf": $("#numero-nf").val(),
                        defeito: $("#defeito").val(),
                        voltagem: $("#voltagem").val()
                    }
                }).done(function (response) {
                    $("#send").val("ENVIAR INFORMAÇÕES");
                    response = JSON.parse(response);
                    if (response.erro != undefined) {

                        $("#alert-env").html("<div class='alert alert-warning'>" + response.erro.msg + "</div>");

                        if (response.erro.campos != undefined) {
                            $(response.erro.campos).each(function (idx, elem) {
                                console.log(elem);
                                setError("#" + elem);
                            });
                        }

                    } else {
                        $("#alert-env").html("<div class='alert alert-success'>Protocolo criado com sucesso: #<b>" + response.hd_chamado + "</b></div>");
                        $("input").val("");
                        $("textarea").val("");
                        $("textarea").html("");
                        $("#send").val("ENVIAR INFORMAÇÕES");
                    }
                });*/
            });

            $("#cep").blur(function(){
                var cep = $(this).val();
                $.ajax("callcenter_cadastra_imbera.php",{
                    method: "POST",
                    data: {
                        ajax_busca_cep: true,
                        cep: cep
                    }
                }).done(function(response){
                    response = JSON.parse(response);
                    if (response.error == undefined) {
                        cidade_id = response.cidade_id;
                        $("#endereco").val(response.end);
                        $("#bairro").val(response.bairro);
                        $("#estado").select2("val", response.uf);
                    }else{
                        alert('CEP não localizado');
                    }

                });


            });


        });

        function retorna_defeito() {
	         	$.ajax("callcenter_cadastra_imbera.php", {
                    method: "POST",
                    data: {
                    id: $("#produto-id").val()
                    }
                }).done(function (response) {
                    $("#defeito").html(response);
                });    
        }

        function setError(input) {
            $(input).addClass("input-error");
            $(input).change(function () {
                if ($(input).val() != "") {
                    $(input).removeClass("input-error");
                }
            })
        }


        function carregaCidades(estado) {
            var select_cidade = $("#cidade");

            $.ajax({
                url: "callcenter_cadastra_imbera.php",
                type: "get",
                data: {ajax_carrega_cidades: true, estado: estado},
                beforeSend: function () {
                    $(select_cidade).find("option:first").nextAll().remove();
                    $("#cidade_label").html("Carregando Cidades do estado " + estado);
                }
            }).done(function (data) {
                $("#cidade_label").html("* Cidade");
                data = JSON.parse(data);

                if (data.erro) {
                    alert(data.erro);
                } else {
                    $("#cidade").select2({
                        data: data.cidades
                    });

                    $("#cidade").select2("val", cidade_id);
                    cidade_id = null;
                }
            });
        }

    </script>

</head>
<body>

<div id="contacto-form" style='margin-left: 10px'>
    <div class="row">
        <div class="col-md-1">
        </div>
        <div class="col-md-10" id="alert-env">

        </div>
    </div>
    <div class="col-md-12"><h3 class="text-center">Informações Cadastrais</h3></div>
    <form name="formContacto" id="formContacto" method="post" enctype="multipart/form-data" action="callcenter_cadastra_imbera.php">
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Nome
                    </div>
                    <input id="nome" name="nome" class="forminput" maxlength="50" type="text">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        CPF/CNPJ
                    </div>
                    <input id="cpf-cnpj" name="cpf-cnpj" maxlength="14" class="forminput" type="text">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">
                        IE
                    </div>
                    <input id="rg" name="rg" maxlength="30" class="forminput" type="text">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Telefone
                    </div>
                    <input id="telefone" name="telefone" maxlength="20" class="forminput" type="text">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Celular
                    </div>
                    <input id="telefone-celular" name="telefone-celular" maxlength="20" class="forminput" type="text">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Email
                    </div>
                    <input id="email" name="email" class="forminput" type="text">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        CEP
                    </div>
                    <input id="cep" name="cep" maxlength="8" class="forminput" type="text">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Endereço
                    </div>
                    <input id="endereco" name="endereco" maxlength="60" class="forminput" type="text">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Número
                    </div>
                    <input id="numero" name="numero" maxlength="20" class="forminput" type="text">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">
                        Complemento
                    </div>
                    <input id="complemento" name="complemento" maxlength="40" class="forminput" type="text">
                </div>
            </div>
        </div>
        <?
        ?>
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Bairro
                    </div>
                    <input id="bairro" name="bairro" maxlength="60" class="forminput" type="text">
                </div>
            </div>
            <div class="col-md-5">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Estado
                    </div>
                    <select id="estado" name="estado" class="forminput">
                        <option value=""></option>
                        <?php
                        foreach ($array_estado as $key => $value) {
                            ?>
                            <option value="<?= $key ?>"><?= $value ?></option><?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div id="cidade_label" class="">*
                        Cidade
                    </div>
                    <select name="cidade" id="cidade" class=""></select>
                </div>
            </div>
        </div>


        <div class="col-md-12"><h3 class="text-center">Informações do Equipamento</h3></div>
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock control-group">
                    <div class="">
                        Data da NF
                    </div>
                    <input id="data-nf" name="data-nf" class="forminput" type="text">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">
                        Número da NF
                    </div>
                    <input id="numero-nf" name="numero-nf" class="forminput" type="text">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock control-group">
                    <div class="">*
                        Equipamento
                    </div>
                    <input id="produto" name="produto" onblur="retorna_defeito()" class="forminput" type="text">
                    <input id="produto-id" name="produto-id" class="forminput" type="hidden">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">
                        Tensão
                    </div>
                    <input id="tensao" name="tensao" class="forminput" type="text">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Voltagem
                    </div>
                    <input id="voltagem" name="voltagem" class="forminput" type="text">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock control-group">
                    <div class="">*
                        Número de Série
                    </div>
                    <input id="numero-serie" name="numero-serie" class="forminput" type="text">
                </div>
            </div>        
        </div>    
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">
                        Tempo de Instalação do Equipamento
                    </div>
                    <input id="tempo-instalacao" name="tempo-instalacao" class="forminput" type="text">
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">* Defeito Reclamado</div>
                        
                        <div id='div_defeitos'>
                            <select id="defeito" name="defeito">
                                
                            </select>
                        </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">*
                        Descreva o problema encontrado
                    </div>
                    <textarea id="problema" name="problema" class="forminput" type="text" placeholder="Mensagem"></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div id="form-name" class="formblock">
                    <div class="">
                        Anexo (Formato válido do arquivo .pdf)
                    </div>
                    <input id="anexo" name='anexo' class="forminput" type="file" >
                </div>
            </div>

        </div>

        <div id="form-send" class="formblock">
            <input type="hidden" name="ajax_enviar" value="true">
            <input id="send" type="submit" value="Enviar informações">
        </div>

    </form>
</div>
