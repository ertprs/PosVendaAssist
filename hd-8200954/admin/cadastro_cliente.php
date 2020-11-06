<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$regras = array(
    "consumidor|nome" => array(
        "obrigatorio" => true
    ),
    "consumidor|cpf" => array(
        "obrigatorio" => true,
        "function" => array("valida_consumidor_cpf")
    ),
    "consumidor|email" => array(
        "regex" => "email"
    ),
);

if ($login_fabrica == 151) {
    $regras["consumidor|cep"]["obrigatorio"]      = true;
    $regras["consumidor|estado"]["obrigatorio"]   = true;
    $regras["consumidor|cidade"]["obrigatorio"]   = true;
    $regras["consumidor|bairro"]["obrigatorio"]   = true;
    $regras["consumidor|endereco"]["obrigatorio"] = true;
    $regras["consumidor|numero"]["obrigatorio"]   = true;
}

if ($login_fabrica == 158) {
    $regras["consumidor|codigo"]["obrigatorio"] = true;
    $regras["consumidor|cpf"]["obrigatorio"] = false;
}

if ($login_fabrica == 171) {
    $regras["consumidor|grupo"]["obrigatorio"] = true;
}

/**
 * Array de regex
 */
$regex = array(
    "cep"      => "/[0-9]{5}\-[0-9]{3}/",
    "email"    => "/^.[^@]+\@.[^@.]+\..[^@]+$/"
);

/**
 * FunÁ„o para validar o CPF do Consumidor
 */
function valida_consumidor_cpf() {
    global $con, $campos;

    $cpf = preg_replace("/\D/", "", $campos["consumidor"]["cpf"]);

    if (strlen($cpf) > 0) {
        $sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("CPF do Consumidor $cpf È inv·lido");
        }
    }
}

/**
 * FunÁ„o que valida os campos da os de acordo com o array $regras
 */
function valida_campos() {
    global $msg_erro, $regras, $campos, $label, $regex;
    
    $nao_obrigatorio = [];

    if ($campos['consumidor']['estado'] == "EX") {

        $nao_obrigatorio = array("cpf", "estado", "bairro", "endereco", "telefone", "cidade");
    }

    foreach ($regras as $campo => $array_regras) {
        list($key, $value) = explode("|", $campo);

        $input_valor = $campos[$key][$value];
       
        if (!in_array($value, $nao_obrigatorio)) {
            foreach ($array_regras as $tipo_regra => $regra) {
                switch ($tipo_regra) {
                    case 'obrigatorio':
                        if (empty($input_valor) && $regra === true) {
                            $msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatÛrios";
                            $msg_erro["campos"][]                 = "{$key}[{$value}]";
                        }
                        break;

                    case 'regex':
                        if (!empty($input_valor) && !preg_match($regex[$regra], $input_valor)) {
                            $msg_erro["msg"][]    = "{$label[$campo]} inv·lido";
                            $msg_erro["campos"][] = "{$key}[{$value}]";
                        }
                        break;

                    case 'function':
                        if (is_array($regra)) {
                            foreach ($regra as $function) {
                                try {
                                    call_user_func($function);
                                } catch(Exception $e) {
                                    $msg_erro["msg"][] = $e->getMessage();
                                    $msg_erro["campos"][] = "{$key}[{$value}]";
                                }
                            }
                        }
                        break;
                }
            }
        }
    }
}

/**
 * Area para colocar os AJAX
 */
if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados())) {
        $sql = "SELECT  fn_retira_especiais(cidade) AS cidade_value, cidade AS cidade_texto FROM (
                    SELECT UPPER(nome) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                    UNION (
                        SELECT UPPER(cidade) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade
                ORDER BY cidade ASC";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();
	$i = 0;
            while ($result = pg_fetch_object($res)) {
		    $array_cidades[$i]['cidade_value']= strtoupper(utf8_encode($result->cidade_value));
		    $array_cidades[$i]['cidade_texto']= utf8_encode($result->cidade_texto);
		    $i++;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {

        $retorno = array("cidades" => "");

        if ($estado != "EX") {
            
            $retorno = array("error" => utf8_encode("estado n„o encontrado"));
        }
    }

    exit(json_encode($retorno));
}

if(isset($_POST['ajax_busca_cep']) && !empty($_POST['cep'])){
    require_once __DIR__.'/../classes/cep.php';

    $cep = $_POST['cep'];

    try {
        $retorno = CEP::consulta($cep);
        $retorno = array_map(utf8_encode, $retorno);
    } catch(Exception $e) {
        if ($estado != "EX") {
            
            $retorno = array("error" => utf8_encode($e->getMessage()));
        }
    }

    exit(json_encode($retorno));
}

if ($_POST["gravar"] == "Gravar") {

    $campos = array(
        "consumidor"    => $_POST["consumidor"],
    );

    valida_campos();

    if (empty($msg_erro["msg"])) {
        try{
            pg_query($con, "BEGIN");

            if ($_REQUEST['consumidor']['estado'] == "EX") {

                $consumidor = $_REQUEST['consumidor']['cliente'];

            } else { 
                
                $sql = " SELECT *
                     FROM tbl_cliente
                     WHERE cpf = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."'";

                $result = pg_query($con,$sql);

                if (pg_num_rows($result) > 0) {
                    $consumidor = pg_fetch_result($result, 0, "cliente");
                } else {
                    $consumidor = $_GET["cliente"];
                }

            }
           
            $sql = "SELECT cidade from tbl_cidade where nome = trim('{$campos['consumidor']['cidade']}')";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
               
                $campos['consumidor']['cidade'] = pg_fetch_result($res,0, 'cidade');
            } else {
                
                if ($_REQUEST['consumidor']['estado'] == "EX") {

                    $cidade_consumidor = $campos['consumidor']['cidade_ex'];

                    $insert_cidade = "INSERT INTO tbl_cidade(nome, estado)  
                                      VALUES('$cidade_consumidor', 'EX') RETURNING cidade";

                    $res_cidade = pg_query($con,$insert_cidade);
                    
                    if (pg_num_rows($res_cidade) > 0) {

                        $campos['consumidor']['cidade'] = pg_fetch_result($res_cidade, 0, 'cidade');
                        
                    } else {

                        throw new Exception("Falha ao cadastrar cidade do exterior. ");
                    }

                } else {

                    throw new Exception("Cidade n„o encontrada. ");
                }
            }
            


            if (strlen($consumidor) > 0) {
                if (in_array($login_fabrica, array(158, 171))) {
                    if (empty($campos['consumidor']['grupo'])) {
                        $campos['consumidor']['grupo'] = "null";
                    }
                    $columnAdc = ", codigo_cliente = '{$campos['consumidor']['codigo']}'";
                    $columnAdc .= ", grupo_cliente = {$campos['consumidor']['grupo']}";
                }

				$bairro = str_replace("'","\'",$campos['consumidor']['bairro']);
                $sql = "UPDATE tbl_cliente  SET
                                nome        = '{$campos['consumidor']['nome']}',
                                nome_fantasia        = '{$campos['consumidor']['nome_fantasia']}',
                                cpf         = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."',
                                cep         = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',
                                cidade      = '{$campos['consumidor']['cidade']}',
                                bairro      = E'{$bairro}',
                                endereco    = '{$campos['consumidor']['endereco']}',
                                numero      = '{$campos['consumidor']['numero']}',
                                complemento = '{$campos['consumidor']['complemento']}',
                                fone        = '{$campos['consumidor']['telefone']}',
                                email       = '{$campos['consumidor']['email']}'
                                {$columnAdc}
                        WHERE cliente = $consumidor ";
 
                $resInsert = pg_query($con,$sql);
    
            } else {

                if (in_array($login_fabrica, array(158, 171))) {
                    if (empty($campos['consumidor']['grupo'])) {
                        $campos['consumidor']['grupo'] = "null";
                    }
                    $columnAdc = ", codigo_cliente";
                    $valuesAdc = ", '{$campos['consumidor']['codigo']}'";
                    $columnAdc .= ", contrato";
                    $valuesAdc .= ", TRUE";
                    $columnAdc .= ", grupo_cliente";
                    $valuesAdc .= ", {$campos['consumidor']['grupo']}";
                }

                $cpf = "'" . preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf']) . "'";
                
                if ($_REQUEST['consumidor']['estado'] == "EX") {
                    $cpf = "NULL";
                }

                $sql = "INSERT INTO tbl_cliente(
                                    nome,
                                    nome_fantasia,
                                    cpf,
                                    cep,
                                    cidade,
                                    bairro,
                                    endereco,
                                    numero,
                                    complemento,
                                    fone,
                                    email
                                    {$columnAdc}
                        )VALUES (
                                    '{$campos['consumidor']['nome']}' ,
                                    '{$campos['consumidor']['nome_fantasia']}' ,
                                    {$cpf},
                                    '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."' ,
                                    {$campos['consumidor']['cidade']} ,
                                    E'{$bairro}' ,
                                    '{$campos['consumidor']['endereco']}' ,
                                    '{$campos['consumidor']['numero']}' ,
                                    '{$campos['consumidor']['complemento']}' ,
                                    '{$campos['consumidor']['telefone']}' ,
                                    '{$campos['consumidor']['email']}'
                                    {$valuesAdc}
                        ) RETURNING cliente";
       
                $resInsert = pg_query($con,$sql);

                $consumidor = pg_fetch_result($resInsert,0, 'cliente');
            }

            if(strlen($consumidor) > 0 and empty($msg_erro) ){
                $sql = "SELECT cliente FROM tbl_fabrica_cliente WHERE cliente = {$consumidor} AND fabrica = {$login_fabrica}";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) == 0){
                    $sql = "INSERT INTO tbl_fabrica_cliente(cliente,fabrica) VALUES($consumidor,$login_fabrica)";
                    $res = pg_query($con,$sql);
                }
            }

            if ($login_fabrica == 151) {
                include "../os_cadastro_unico/fabricas/151/classes/Participante.php";

                $dadosParticipante = array();

                $xcnpj = preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf']);

                if (strlen($xcnpj) == 11) {
                    $tipoPessoa = "F";
                } else {
                    $tipoPessoa = "J";
                }

                $sqlCidade = "SELECT nome FROM tbl_cidade WHERE cidade = {$campos['consumidor']['cidade']}";
                $resCidade = pg_query($con, $sqlCidade);

                $cidade = pg_fetch_result($resCidade, 0, "nome");

                $dadosParticipante["SdEntParticipante"] = array(
                    "RelacionamentoCodigo"                  => "ConsumidorFinal",
                    "ParticipanteTipoPessoa"                => $tipoPessoa,
                    "ParticipanteFilialCPFCNPJ"             => $xcnpj,
                    "ParticipanteRazaoSocial"               => utf8_encode($campos['consumidor']['nome']),
                    "ParticipanteFilialNomeFantasia"        => utf8_encode($campos['consumidor']['nome']),
                    "ParticipanteStatus"                    => "A",
                    "Enderecos"                             => array( 0 =>
                        array(
                            "ParticipanteFilialEnderecoSequencia"   => 1,
                            "ParticipanteFilialEnderecoTipo"        => "Cobranca",
                            "ParticipanteFilialEnderecoCep"         => preg_replace("/\D/", "", $campos['consumidor']['cep']),
                            "ParticipanteFilialEnderecoLogradouro"  => utf8_encode($campos['consumidor']['endereco']),
                            "ParticipanteFilialEnderecoNumero"      => $campos['consumidor']['numero'],
                            "ParticipanteFilialEnderecoComplemento" => utf8_encode($campos['consumidor']['complemento']),
                            "ParticipanteFilialEnderecoBairro"      => utf8_encode($campos['consumidor']['bairro']),
                            "PaisCodigo"                            => 1058,
                            "PaisNome"                              => "Brasil",
                            "UnidadeFederativaCodigo"               => "",
                            "UnidadeFederativaNome"                 => utf8_encode($campos['consumidor']['estado']),
                            "MunicipioNome"                         => utf8_encode($cidade),
                            "ParticipanteFilialEnderecoStatus"      => "A",
                        )
                    ),
                    "Contatos"                              => array(
                        array(
                            "ParticipanteFilialEnderecoContatoEmail"        => utf8_encode($campos['consumidor']['email']),
                            "ParticipanteFilialEnderecoContatoTelefoneDDI"  => 55,
                            "ParticipanteFilialEnderecoContatoTelefone"     => $campos['consumidor']['fone']
                        )
                    )

                );

                if ($tipoPessoa == "F") {
                    $dadosParticipante["SdEntParticipante"]["Enderecos"][0]["InscricaoEstadual"] = "ISENTO";
                }

                $participante = new Participante();

                $participanteRet = $participante->gravaParticipante($dadosParticipante);

                if (!is_bool($participanteRet) || (is_bool($participanteRet) && $participanteRet !== true)) {
                    throw new Exception('Erro ao gravar dados, entrar em contato com TI da Mondial');
                }
            }
     
            if(pg_last_error() and empty($msg_erro) ){
                throw new Exception("Erro ao gravar consumidor.");
            }elseif(empty($msg_erro)){
                pg_query($con, "COMMIT");
                $msg = "Consumidor gravado com sucesso!";
            }

        }catch(Exception $e) {
            pg_query($con, "ROLLBACK");
            $msg_erro["msg"][] = $e->getMessage();
        }
    }
}

if ($_GET["listar"] == "todos" || !empty($_GET['cliente'])) {

    if(!empty($_GET['cliente'])){
        $cliente = $_GET['cliente'];
        $cond = "AND tbl_cliente.cliente = {$cliente}";
    }

	if($_GET["listar"] == "todos") {
		$ini = $_GET['ini']; 
		if(!empty($ini)) {
			$cond_ini = (strlen($ini) == 1) ? " AND tbl_cliente.nome ~* '^$ini' " : "AND tbl_cliente.nome !~* '^[a-z]'";
		}else{
			$cond_ini = " AND tbl_cliente.nome ~* '^A' ";
		}
	}

    $sql = "SELECT  tbl_cliente.cliente,
                    tbl_cliente.nome,
                    tbl_cliente.nome_fantasia,
                    tbl_cliente.cpf,
                    tbl_cliente.fone,
                    tbl_cliente.email,
                    tbl_cliente.cep,
                    tbl_cliente.endereco,
                    tbl_cliente.numero,
                    tbl_cliente.complemento,
                    tbl_cliente.bairro,
                    tbl_cidade.nome AS cidade,
                    tbl_cidade.estado,
                    tbl_cliente.codigo_cliente
                FROM tbl_cliente
                JOIN tbl_fabrica_cliente ON tbl_cliente.cliente = tbl_fabrica_cliente.cliente
                JOIN tbl_cidade ON tbl_cliente.cidade = tbl_cidade.cidade
				WHERE tbl_fabrica_cliente.fabrica = {$login_fabrica}
				$cond_ini
                {$cond}";
    $resSubmit = pg_query($con,$sql);
    $rows = pg_num_rows($resSubmit);

    if(pg_num_rows($resSubmit) > 0 AND !empty($cliente)){

        $_RESULT = array(
            "consumidor" => array(
                "cliente"     => pg_fetch_result($resSubmit, 0, 'cliente'),
                "nome"        => pg_fetch_result($resSubmit, 0, 'nome'),
                "nome_fantasia"        => pg_fetch_result($resSubmit, 0, 'nome_fantasia'),
                "cpf"         => pg_fetch_result($resSubmit, 0, 'cpf'),
                "telefone"    => pg_fetch_result($resSubmit, 0, 'fone'),
                "email"       => pg_fetch_result($resSubmit, 0, 'email'),
                "cidade"      => pg_fetch_result($resSubmit, 0, 'cidade'),
                "estado"      => pg_fetch_result($resSubmit, 0, 'estado'),
                "cep"         => pg_fetch_result($resSubmit, 0, 'cep'),
                "endereco"    => pg_fetch_result($resSubmit, 0, 'endereco'),
                "numero"      => pg_fetch_result($resSubmit, 0, 'numero'),
                "complemento" => pg_fetch_result($resSubmit, 0, 'complemento'),
                "bairro"      => pg_fetch_result($resSubmit, 0, 'bairro'),
                "codigo"      => pg_fetch_result($resSubmit, 0, 'codigo_cliente')
            )
        );

        if(strlen($_RESULT["consumidor"]['cpf']) == 11){
            $_RESULT["consumidor"]['cnpjCpf'] = "cpf";
        }else{
            $_RESULT["consumidor"]['cnpjCpf'] = "cnpj";
        }

    }

}

$layout_menu = "cadastro";
if ($login_fabrica == 171){
    $title = "Cadastro de clientes";
}else{
    $title = "Cadastro de consumidor";
}
include 'cabecalho_new.php';

$plugins = array(
    "mask",
    "maskedinput",
    "shadowbox",
    "dataTable"
    );

include("plugin_loader.php");
?>

<script type="text/javascript">

function validaExterior() {
    var estado = $("#consumidor_estado").val();

    if (estado === "EX") {
        $(".est_ex").show();
        $(".est_br").hide();

    } else {
        $(".est_ex").hide();
        $(".est_br").show();
    }
}

$(function() {

<?php if ($login_fabrica == 148) { ?>
    
    $("#consumidor_estado").change(function(){
        
        validaExterior();
    });

<?php } ?>

    $("#consumidor_cep").mask("99999-999");

    $("input[name='consumidor[cnpjCpf]']").change(function(){
        $("#consumidor_cpf").unmask();
        var tipo = $(this).val();
        if(tipo == 'cnpj'){
            $("#consumidor_cpf").mask("99.999.999/9999-99");
        }else{
            $("#consumidor_cpf").mask("999.999.999-99");
        }
    });

    if ($("input[name='consumidor[cnpjCpf]']:checked").val() == "cpf" || $("#consumidor_cpf").val().replace(/\.|\-|\//g, '').length == 11) {
        $("#consumidor_cpf").mask("999.999.999-99");
    } else {
        $("#consumidor_cpf").mask("99.999.999/9999-99");
    }

    Shadowbox.init();

    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

    $("select[id$=_estado]").change(function() {
        busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "consumidor");
    });

    /**
    ** Evento para buscar o endereÁo do cep digitado
    **/  
    $("input[id$=_cep]").blur(function() {
        if ($(this).attr("readonly") == undefined) {
            
            busca_cep($(this).val(), ($(this).attr("id") == "revenda_cep") ? "revenda" : "consumidor");
        }
    });

});

/**
 * FunÁ„o para retirar a acentuaÁ„o
 */
function retiraAcentos(palavra){
    var com_acento = '·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
        if (com_acento.search(palavra.substr(i, 1)) >= 0) {
            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
        } else {
            newPalavra += palavra.substr(i, 1);
        }
    }

    return newPalavra.toUpperCase();
}

/**
 * FunÁ„o que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, consumidor_revenda, cidade) {
    $("#"+consumidor_revenda+"_cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            async: false,
            url: "cadastro_cliente.php",
            type: "POST",
            data: { ajax_busca_cidade: true, estado: estado },
            beforeSend: function() {
                if ($("#"+consumidor_revenda+"_cidade").next("img").length == 0) {
                    $("#"+consumidor_revenda+"_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                }
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $.each(data.cidades, function(key, value) {
                        var option = $("<option></option>", { value: value.cidade_value, text: value.cidade_texto});

                        $("#"+consumidor_revenda+"_cidade").append(option);
                    });
                }


                $("#"+consumidor_revenda+"_cidade").show().next().remove();
            }
        });
    }

    if(typeof cidade != "undefined" && cidade.length > 0){

        $('#consumidor_cidade option[value='+cidade+']').attr('selected','selected');

    }

}

/**
 * * FunÁ„o que faz um ajax para buscar o cep nos correios
* */
function busca_cep(cep, consumidor_revenda, method) {

	if (cep.length > 0) {
		var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		if (typeof method == "undefined" || method.length == 0) {
			method = "webservice";

			$.ajaxSetup({
				timeout: 3000
			});
		} else {
			$.ajaxSetup({
				timeout: 5000
			});
		}

		$.ajax({
			async: true,
				url: "ajax_cep.php",
				type: "GET",
				data: { cep: cep, method: method, tela: 'cadastro_cliente'},
				beforeSend: function() {
					$("#"+consumidor_revenda+"_estado").next("img").remove();
					$("#"+consumidor_revenda+"_cidade").next("img").remove();
					$("#"+consumidor_revenda+"_bairro").next("img").remove();
					$("#"+consumidor_revenda+"_endereco").next("img").remove();

					$("#"+consumidor_revenda+"_estado").hide().after(img.clone());
					$("#"+consumidor_revenda+"_cidade").hide().after(img.clone());
					$("#"+consumidor_revenda+"_bairro").hide().after(img.clone());
					$("#"+consumidor_revenda+"_endereco").hide().after(img.clone());
				},
					error: function(xhr, status, error) {
						busca_cep(cep, consumidor_revenda, "database");
					},
					success: function(data) {
						results = data.split(";");

						if (results[0] != "ok") {
							alert(results[0]);
							$("#"+consumidor_revenda+"_cidade").show().next().remove();
						} else {
							$("#"+consumidor_revenda+"_estado").val(results[4]);

							busca_cidade(results[4], consumidor_revenda);

							$("#"+consumidor_revenda+"_cidade").val(retiraAcentos(results[3]).toUpperCase());

							if (results[2].length > 0) {
								$("#"+consumidor_revenda+"_bairro").val(results[2]);
							}

							if (results[1].length > 0) {
								$("#"+consumidor_revenda+"_endereco").val(results[1]);
							}
						}

						$("#"+consumidor_revenda+"_estado").show().next().remove();
						$("#"+consumidor_revenda+"_bairro").show().next().remove();
						$("#"+consumidor_revenda+"_endereco").show().next().remove();
						if ($("#"+consumidor_revenda+"_bairro").val().length == 0) {
							$("#"+consumidor_revenda+"_bairro").focus();
						} else if ($("#"+consumidor_revenda+"_endereco").val().length == 0) {
							$("#"+consumidor_revenda+"_endereco").focus();
						} else if ($("#"+consumidor_revenda+"_numero").val().length == 0) {
							$("#"+consumidor_revenda+"_numero").focus();
						}
					}
		});
	} 

}

/**
 * FunÁ„o de retorno da lupa do posto
 */
function retorna_consumidor(retorno) {
    /**
     * A funÁ„o define os campos cÛdigo e nome como readonly e esconde o bot„o
     * O posto somente pode ser alterado quando clicar no bot„o trocar_posto
     * O evento do bot„o trocar_posto remove o readonly dos campos e d· um show nas lupas
     */

    $("#consumidor_nome").val(retorno.nome);

    $("#cpf_cnpj").attr({ disabled: "disabled" });
    $("#cnpj_cpf").attr({ disabled: "disabled" });
    $("#consumidor_nome").find("span[rel=lupa]").hide();
    $("#consumidor_cpf").unmask();

    if(retorno.cpf.length > 11){
        $("#consumidor_cpf").mask("99.999.999/9999-99");
    }else{
        $("#consumidor_cpf").mask("999.999.999-99");
    }

    $("#consumidor_cpf").val(retorno.cpf);

    $("#consumidor_cpf").find("span[rel=lupa]").hide();

    if(retorno.cep.length > 0 && retorno.estado != "EX"){
        busca_cep(retorno.cep,"consumidor");
    }else{
        $("#consumidor_estado").val(retorno.estado);
        $("#consumidor_cidade").val(retorno.cidade);
        <?php if ($login_fabrica == 148) { ?>
            validaExterior();
            $("#consumidor_cidade_ex").val(retorno.consumidor_cidade);
            $("#consumidor_id").val(retorno.cliente);
        <?php } ?>
    }
    $("#consumidor_cep").val(retorno.cep);

    $("#consumidor_bairro").val(retorno.bairro);
    $("#consumidor_endereco").val(retorno.endereco);
    $("#consumidor_numero").val(retorno.numero);
    $("#consumidor_complemento").val(retorno.complemento);
    $("#consumidor_telefone").val(retorno.fone);
    $("#consumidor_email").val(retorno.email);
}

</script>

<?php

if (count($msg_erro["msg"]) > 0) {
    ?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]) ?></h4>
    </div>
    <?php
}

if (!empty($msg)) {
?>
    <div class="alert alert-success">
        <h4>
            <?=$msg?>
        </h4>
    </div>
<?php
}
?>

        <div class="row">
            <b class="obrigatorio pull-right">  * Campos obrigatÛrios </b>
        </div>

        <form name='frm_cadastro' onsubmit="retiraMascara(this);" METHOD='POST' lign='center' class='form-search form-inline tc_formulario' >

            <div class='titulo_tabela '>Cadastro</div>
            <br/>
            <div class="row-fluid">
                <div class="span1"></div>
                <? $span_nome = (in_array($login_fabrica, array(158))) ? "span4" : "span5"; ?>
                <div class="<?= $span_nome; ?>">
                    <div class='control-group <?=(in_array('consumidor[nome]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_nome">Nome</label>
                        <div class="controls controls-row">
                            <div class="span10 input-append">
                                    <h5 class='asteristico'>*</h5>
                                <input id="consumidor_nome" name="consumidor[nome]" class="span12" type="text" value="<?=getValue('consumidor[nome]')?>" <?=$readonly?> />
                                <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="consumidor" parametro="nome_consumidor" />
                               <input id="consumidor_id" name="consumidor[cliente]" class="span12 " type="hidden" value="<?=getValue('consumidor[cliente]')?>"/>
                            </div>
                        </div>
                    </div>
                </div>
                <? if (in_array($login_fabrica, array(158))) { ?>
                    <div class="span3">
                        <div class='control-group <?=(in_array('consumidor[nome_fantasia]', $msg_erro['campos'])) ? "error" : "" ?>' >
                            <label class="control-label" for="consumidor_nome_fantasia">Nome Fantasia</label>
                            <div class="controls controls-row">
                                <div class="span10 input-append">
                                        <h5 class='asteristico'>*</h5>
                                    <input id="consumidor_nome_fantasia" name="consumidor[nome_fantasia]" class="span12" type="text" value="<?=getValue('consumidor[nome_fantasia]')?>" <?=$readonly?> />
                                    <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                        <i class="icon-search"></i>
                                    </span>
                                    <input type="hidden" name="lupa_config" tipo="consumidor" parametro="nome_fantasia_consumidor" />
                                </div>
                            </div>
                        </div>
                    </div>
                <? } ?>

                <div class="span3">
                    <div class='control-group <?=(in_array('consumidor[cpf]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_cpf">
                            <?
                            if (strlen($cliente)){
                                echo 'CPF/CNPJ';
                            } else {
                               ?> CPF <input type="radio" id="cpf_cnpj" name="consumidor[cnpjCpf]" <? echo (getValue('consumidor[cnpjCpf]')=='cpf') ? 'checked="checked"': ''; ?> value="cpf" >
                               /CNPJ <input type="radio" id="cnpj_cpf" name="consumidor[cnpjCpf]" <? echo (getValue('consumidor[cnpjCpf]')=='cnpj') ? 'checked="checked"': ''; ?> value="cnpj" >
                               <?
                           }
                           ?>
                       </label>
                       <div class="controls controls-row">
                        <div class="span10 input-append">
                            <? if ($regras["consumidor|cpf"]["obrigatorio"] == true) { ?>
                                <h5 class='asteristico est_br'>*</h5>
                            <? } ?>
                            <input id="consumidor_cpf" name="consumidor[cpf]" class="span12 " type="text" value="<?=getValue('consumidor[cpf]')?>" <?=$readonly?> />
                            <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="consumidor" parametro="cnpj" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>

        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span2">
                <div class='control-group <?=(in_array('consumidor[cep]', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="consumidor_cep">CEP</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <? if ($login_fabrica == 151) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="consumidor_cep" name="consumidor[cep]" class="span12" type="text" value="<?=getValue('consumidor[cep]')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2">
                <div class="control-group <?=(in_array('consumidor[estado]', $msg_erro['campos'])) ? "error" : "" ?>">
                    <label class="control-label" for="consumidor_estado">Estado</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <? if ($login_fabrica == 151) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <select id="consumidor_estado" name="consumidor[estado]" class="span12">
                                    <option value="" >Selecione</option>
                                    <?php
                                        #O $array_estados() est· no arquivo funcoes.php
                                    foreach ($array_estados() as $sigla => $nome_estado) {
                                        $selected = ($sigla == getValue('consumidor[estado]')) ? "selected" : "";

                                        echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                                    }

                                    if ($login_fabrica == 148) {
                          /*              if (getValue('consumidor[estado]')) {
                                        
                                        }*/
                                        echo "<pre>";
                                        echo getValue('consumidor[estado]');
                                        ?>
                                        <option value="EX" <?= $selected ?> >Exterior</option>
                                    <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group <?=(in_array('consumidor[cidade]', $msg_erro['campos'])) ? "error" : "" ?>">
                    <label class="control-label" for="consumidor_cidade">Cidade</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <? if ($login_fabrica == 151) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <div class="est_br">
                            <select id="consumidor_cidade" name="consumidor[cidade]" class="span12">
                                <option value="" >Selecione</option>
                                <?php
                                if (strlen(getValue("consumidor[estado]")) > 0 && (getValue("consumidor[estado]") != "Ex")) {
                                    $sql = "SELECT DISTINCT * FROM (
                                                SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".getValue("consumidor[estado]")."')
                                                UNION (
                                                    SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".getValue("consumidor[estado]")."')
                                                )
                                            ) AS cidade
                                            ORDER BY cidade ASC";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected  = (trim($result->cidade) == trim(getValue("consumidor[cidade]"))) ? "SELECTED" : "";

                                            echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                        }
                                    }
                                }
                                ?>
                            </select>
                            </div>
                            <input name="consumidor[cidade_ex]" id="consumidor_cidade_ex" value="<?= $result->cidade?>" type="text" class="span12 est_ex" style="display:none">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class='control-group <?=(in_array('consumidor[bairro]', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="consumidor_bairro">Bairro</label>
                    <div class="controls controls-row">
                        <div class="span12">
                             <? if ($login_fabrica == 151) { ?>
                                <h5 class='asteristico'>*</h5>
                            <? } ?>
                            <input id="consumidor_bairro" name="consumidor[bairro]" class="span12" type="text" value="<?=getValue('consumidor[bairro]')?>"  />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
                <div class="span1"></div>
                <div class="span5">
                    <div class='control-group <?=(in_array('consumidor[endereco]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_endereco">EndereÁo</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <?php
                                if ($login_fabrica == 151) {
                                ?>
                                    <h5 class='asteristico'>*</h5>
                                <?php
                                }
                                ?>
                                <input id="consumidor_endereco" name="consumidor[endereco]" class="span12" type="text" value="<?=getValue('consumidor[endereco]')?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1">
                    <div class='control-group <?=(in_array('consumidor[numero]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_numero">N˙mero</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <?php
                                if ($login_fabrica == 151) {
                                ?>
                                    <h5 class='asteristico'>*</h5>
                                <?php
                                }
                                ?>
                                <input id="consumidor_numero" name="consumidor[numero]" class="span12" type="text" value="<?=getValue('consumidor[numero]')?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class='control-group <?=(in_array('consumidor[complemento]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_complemento">Complemento</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_complemento" name="consumidor[complemento]" class="span12" type="text" value="<?=getValue('consumidor[complemento]')?>" maxlength="20" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
            </div>

            <div class="row-fluid">
                <div class="span1"></div>

                <div class="span2">
                    <div class="control-group <?=(in_array('consumidor[telefone]', $msg_erro['campos'])) ? "error" : "" ?>">
                        <label class="control-label" for="consumidor_telefone">Telefone</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_telefone" name="consumidor[telefone]" class="span12" type="text" value="<?=getValue('consumidor[telefone]')?>"  maxlength="20" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span4">
                   <div class='control-group <?=(in_array('consumidor[email]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="consumidor_email">Email</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="consumidor_email" name="consumidor[email]" class="span12" type="text" value="<?=getValue('consumidor[email]')?>"  />
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                if (in_array($login_fabrica, array(158, 171))) {
                ?>
                    <div class="span2">
                       <div class='control-group <?=(in_array('consumidor[codigo]', $msg_erro['campos'])) ? "error" : "" ?>' >
                            <label class="control-label" for="consumidor_codigo">CÛdigo</label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <?php if ($login_fabrica == 158) { ?>
                                        <h5 class='asteristico'>*</h5>
                                    <?php } ?>
                                    <input id="consumidor_codigo" name="consumidor[codigo]" class="span12" type="text" value="<?=getValue('consumidor[codigo]')?>"  />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="span2">
                       <div class='control-group <?=(in_array('consumidor[grupo]', $msg_erro['campos'])) ? "error" : "" ?>' >
                            <label class="control-label" for="consumidor_grupo">Grupo</label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <h5 class='asteristico'>*</h5>
                                    <select id="consumidor_grupo" name="consumidor[grupo]" class="span12">
                                        <option value="">Selecione</option>
                                        <?
                                        $sql = "SELECT * FROM tbl_grupo_cliente WHERE fabrica = {$login_fabrica} AND ativo IS TRUE;";
                                        $res = pg_query($con, $sql);
                                        $grupos = pg_fetch_all($res);

                                        if (count($grupos) > 0)  {
                                            foreach ($grupos as $grupo) {
                                                $grupo_cliente = $grupo['grupo_cliente'];
                                                $descricao = $grupo['descricao'];
                                                $selected = ($grupo_cliente == getValue('consumidor[grupo]')) ? "SELECTED" : ""; ?>
                                                <option value="<?= $grupo_cliente; ?>" <?= $selected; ?>><?= $descricao; ?></option>
                                            <? }
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>

                <div class="span1"></div>
            </div>

        <p class="tac">
            <input type="submit" class="btn" name="gravar" value="Gravar" />
            <? if(in_array($login_fabrica, array(151,158))){ ?>
                <button type="button" name="listar" class="btn btn-info" onclick="window.location='cadastro_cliente.php?listar=todos'">Listar Todos</button>
            <? }

            if (strlen($cliente) > 0) { ?>
                <button type="reset" class="btn btn-warning"  id="limpar" onclick="window.location = 'cadastro_cliente.php';" >Limpar</button>
            <? } ?>
        </p>
        <br />
    </form>
</div>
	<? if($rows > 0 AND !empty($_GET['listar'])) {
		echo "<p style='text-align:center'>Clique na letra para ver os clientes<br>"; 
				foreach (range('A', 'Z') as $char) {
					 echo "<a href='$PHP_SELF?listar=todos&ini=$char'>$char</a> | ";
				}
		echo "<a href='$PHP_SELF?ini=other&listar=todos'>Outros</a></p>  ";

?>
            <table id="clientes_cadastrados" class='table table-striped table-bordered table-hover table-large' style="margin: 0 auto;" >
            <thead>
                <tr class="titulo_coluna" >
                    <?php
                    if ($login_fabrica == 158) {
                    ?>
                        <th>CÛdigo</th>
                    <?php
                    }
                    ?>
                    <th>Nome</th>
                    <? if(in_array($login_fabrica, array(158))){ ?>
                        <th>Nome Fantasia</th>
                    <? } ?>
                    <th>CPF/CNPJ</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
			<?
                for ($i = 0; $i < $rows; $i++) {
                    $codigo   = pg_fetch_result($resSubmit, $i, "codigo_cliente");
                    $cliente  = pg_fetch_result($resSubmit, $i, "cliente");
                    $nome     = pg_fetch_result($resSubmit, $i, "nome");
                    $nome_fantasia     = pg_fetch_result($resSubmit, $i, "nome_fantasia");
                    $cpf      = pg_fetch_result($resSubmit, $i, "cpf");
                    $email    = pg_fetch_result($resSubmit, $i, "email");
                    $fone     = pg_fetch_result($resSubmit, $i, "telefone");
                    $cidade   = pg_fetch_result($resSubmit, $i, "cidade");
                    $estado   = pg_fetch_result($resSubmit, $i, "estado"); ?>

                    <tr>
                        <?php
                        if ($login_fabrica == 158) {
                        ?>
                            <td><?=$codigo?></td>
                        <?php
                        }
                        ?>
                        <td><a href="<?= $_SERVER['PHP_SELF']."?cliente=".$cliente; ?>"><?= $nome; ?></a></td>
                        <? if(in_array($login_fabrica, array(158))){ ?>
                            <td><?= $nome_fantasia; ?></td>
                        <? } ?>
                        <td><?= $cpf; ?></td>
                        <td><?= $email; ?></td>
                        <td><?= $fone; ?></td>
                        <td><?= $cidade; ?></td>
                        <td><?= $estado; ?></td>
                    </tr>
                <? } ?>
            </tbody>
        </table>

        <script>
            $.dataTableLoad({ table: "#clientes_cadastrados" });
        </script>
    <? } ?>
</div>
<? include "rodape.php"; ?>
