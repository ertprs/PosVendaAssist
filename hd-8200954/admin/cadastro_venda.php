<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}

include 'funcoes.php';

if($login_pais == 'BR'){
    $obrigatorio = true;
}else{
    $obrigatorio = false;
}

$array_pais_estado = $array_pais_estado();

$regras = array(
    "posto|id" => array(
        "obrigatorio" => true
    ),
    "consumidor|email" => array(
        "obrigatorio" => true,
        "regex" => "email"
    ),
    "consumidor|nome" => array(
        "obrigatorio" => true
    ),
    "consumidor|estado" => array(
        "obrigatorio" => $obrigatorio
    ),
    "consumidor|cidade" => array(
        "obrigatorio" => $obrigatorio
    ),
    "consumidor|cep" => array(
    "obrigatorio" => $obrigatorio
    ),
    "consumidor|cpf" => array(
        "obrigatorio" => $obrigatorio ,
        "function"    => array("valida_cpf")
    ),
    "produto|id" => array(
        "obrigatorio" => true,
        "function"    => array("valida_posto_atende_produto_linha")
    ),
    "produto|serie" => array(
        "obrigatorio" => true,
        "function" => array("valida_numero_de_serie")
    ),
    "produto|serie_motor" => array(
        "obrigatorio" => true
    ),
    "produto|nota_fiscal" => array(
        "obrigatorio" => true
    ),
    "produto|data_nota_fiscal" => array(
        "obrigatorio" => true,
        "regex" => "date"
    ),
);

if ($login_fabrica == 148) {
    $regras["consumidor|endereco"] = [
            "obrigatorio" => true
        ];

    $regras["consumidor|telefone"] = [
            "obrigatorio" => true
        ];

    $regras["consumidor|bairro"] = [
            "obrigatorio" => true
				];

		$regras["consumidor|numero"] = [
					"obrigatorio" => true
			];
}

if ($login_fabrica == 161) {
    unset($regras["posto|id"]);
    unset($regras["produto|serie_motor"]);
}

/**
 * Array de regex
 */
$regex = array(
    "date"     => "/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/",
    "cep"      => "/[0-9]{5}\-[0-9]{3}/",
    "email"    => "/^.[^@]+\@.[^@.]+\..[^@]+$/"
);

$label = array(
    "posto|id"                  => "Posto",
    "consumidor|email"          => "Consumidor Email",
    "consumidor|nome"           => "Nome do Cliente",
    "consumidor|cpf"            => "CPF/CNPJ do Cliente",
    "produto|id"                => "Produto",
    "produto|serie"             => "Produto Série",
    "produto|serie_motor"       => "Produto Série do motor",
    "produto|serie_transmissao" => "Produto Série da transmissão",
    "produto|nota_fiscal"       => "Produto Nota Fiscal",
    "produto|data_nota_fiscal"  => "Produto Data da Nota Fiscal",
);


function tipo_posto_locadora($posto) {
    global $con, $login_fabrica;

    if (empty($posto)) {
        throw new Exception("Posto Autorizado não informado");
    }

    $sql = "SELECT tbl_tipo_posto.tipo_posto
            FROM tbl_posto_tipo_posto
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            WHERE tbl_posto_tipo_posto.fabrica = {$login_fabrica}
            AND tbl_posto_tipo_posto.posto = {$posto}
            AND tbl_tipo_posto.locadora IS TRUE";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Função para validar o CPF do Consumidor
 */
function valida_cpf() {
    global $con, $campos, $login_fabrica, $login_pais;

    $cpf = preg_replace("/\D/", "", $campos["consumidor"]["cpf"]);

    if (strlen($cpf) > 0 && $login_pais == "BR") {
        $sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("CPF/CNPJ do Cliente $cpf é inválido");
        } else {
            $sql = "SELECT tbl_posto.cnpj
                    FROM tbl_posto_fabrica
                    INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                    WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                    AND tbl_posto_fabrica.posto = '{$campos['posto']['id']}'";
            $res = pg_query($con, $sql);

            $posto_cnpj = pg_fetch_result($res, 0, "cnpj");

           // if ($posto_cnpj == $cpf && tipo_posto_locadora($campos["posto"]["id"]) === false) {
           //     throw new Exception("A venda não pode ser cadastrada para o mesmo CNPJ do Posto");
           // }
        }
    }
}

function valida_numero_de_serie() {
    global $con, $campos, $login_fabrica,$msg_erro;

    $produto_id = $campos["produto"]["id"];
    $produto_serie = $campos["produto"]["serie"];
    
    
    $validar = True;
    
    if ($login_fabrica == 148) {

        if (strlen($campos['venda']) > 0) {

            $validar = False;
        }
    }

    if ($validar) {

        if (strlen($produto_id) > 0 && !empty($produto_serie)) {
            $sql = "SELECT serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND serie  = '{$produto_serie}' AND produto = {$produto_id} ";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) == 0) {
                throw new Exception("Número de série não cadastrado");
            }
        }
    }
}

/**
 * Função chamada na valida_campos()
 *
 * Função para validar se o posto atende a linha do produto
 */
function valida_posto_atende_produto_linha() {
    global $con, $login_fabrica, $campos;

    $produto = $campos["produto"]["id"];
    $posto   = $campos["posto"]["id"];

    if (!empty($produto) && !empty($posto)) {
        $sql = "SELECT *
                FROM tbl_posto_fabrica
                INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
                INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                INNER JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.produto = {$produto}
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND tbl_posto_fabrica.posto = {$posto}";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Posto não atende a linha do produto selecionado");
        }
    }
}

/**
 * Função que valida os campos da os de acordo com o array $regras
 */
function valida_campos() {
    global $msg_erro, $regras, $campos, $label, $regex;
    
    $nao_obrigatorio = [];

    if ($login_fabrica == 148) {
        
        unset($regras["produto|serie"]);
    }

    if (isset($campos['consumidor']['nacionalExterior']) == "exterior") {

        $nao_obrigatorio = ["estado", "cidade", "cep", "cpf", "telefone", "bairro", "endereco", "numero"];
    }

    if (!empty($campos["consumidor"]["pais"]) && $campos["consumidor"]["pais"] != "BR") {
        $nao_obrigatorio = ["cep", "cpf", "telefone", "bairro", "endereco", "numero"];
    }
    
    foreach ($regras as $campo => $array_regras) {
        list($key, $value) = explode("|", $campo);

        $input_valor = $campos[$key][$value];
        
        if (!in_array($value, $nao_obrigatorio)) {
            
            foreach ($array_regras as $tipo_regra => $regra) {
                switch ($tipo_regra) {
                    case 'obrigatorio':
                        if (empty(trim($input_valor)) && $regra === true) {
                            $msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatórios";
                            $msg_erro["campos"][]                 = "{$key}[{$value}]";
                        }
                        break;

                    case 'regex':
                        if (!empty($input_valor) && !preg_match($regex[$regra], $input_valor)) {
                            $msg_erro["msg"][]    = "{$label[$campo]} inválido";
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
    $pais   = strtoupper($_POST["pais"]);

    if (!empty($pais) && $pais != "BR") {
        
        $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                FROM tbl_cidade 
                WHERE UPPER(estado_exterior) = UPPER('{$estado}')
                AND UPPER(pais) = UPPER('{$pais}')
                ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
        }

    } else {

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
                $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
            }
        } else {
            $retorno = array("error" => utf8_encode("Estado não encontrado"));
        }

    }

    exit(json_encode($retorno));
}

if(filter_input(INPUT_POST,'ajax_busca_cep') && filter_input(INPUT_POST,'cep')){
    require_once __DIR__.'/../classes/cep.php';
    $cep = $_POST['cep'];

    try {
        $retorno = CEP::consulta($cep);
    $retorno = array_map(utf8_encode, $retorno);
    } catch(Exception $e) {
        $retorno = array("error" => utf8_encode($e->getMessage()));
    }
    exit(json_encode($retorno));
}

if (isset($_POST['ajax_confirm_submit'])) {
    $sqlVSerie = "SELECT venda
                        FROM tbl_venda JOIN tbl_produto
                        USING (produto)
                        WHERE fabrica = {$login_fabrica}
                        AND tbl_venda.serie = '{$_POST['serie']}'
                        AND referencia = '{$_POST['referencia']}'";
     $resVSerie = pg_query($con, $sqlVSerie);
     if (pg_num_rows($resVSerie) > 0) {
        exit('true');
     }
     exit('false');
}

if ($_POST["gravar"] == "Gravar") {

    try{
        $venda = $_REQUEST["id"];

        $campos = array(
            "consumidor" => $_POST["consumidor"],
            "posto"      => $_POST["posto"],
            "produto"    => $_POST["produto"],
            "venda"      => $venda
        );

        valida_campos();
        pg_query($con, "BEGIN");
        
        if ($_REQUEST['consumidor']['nacionalExterior'] == "exterior" || (isset($campos["consumidor"]["pais"]) && $campos["consumidor"]["pais"] != "BR")) {
            
            $consumidor = $_REQUEST['consumidor']['cliente'];
            
            if (isset($_GET['id'])) {
                $sql = "SELECT cliente FROM tbl_venda WHERE venda = {$_GET['id']}";
            
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {

                    $consumidor = pg_fetch_result($res, 0, "cliente");
                } 
            }

        } else {

            $sql = " SELECT *
                 FROM tbl_cliente
                 WHERE cpf = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."'";

            $result = pg_query($con,$sql);

            if (pg_num_rows($result) > 0) {

                $consumidor = pg_fetch_result($result, 0, "cliente");
            } else {

                $consumidor = $_GET['id'];
            }

        }

        //hd_chamado=2715924 adicionando o fn_retira_especiais.
        if ($_REQUEST['consumidor']['nacionalExterior'] == "exterior") {

            $cidade = $campos['consumidor']['cidade_ex'];
        } else {

            $cidade = $campos['consumidor']['cidade'];
        }

        if (!empty($campos["consumidor"]["pais"])) {
            $wherePaisCidade = "AND pais = '".$campos["consumidor"]["pais"]."'";
		}else{
            $wherePaisCidade = "AND pais = '$login_pais'";
		}
        
        $sql = "SELECT cidade, nome from tbl_cidade where UPPER(nome) = UPPER('".$cidade."') {$wherePaisCidade}";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res)>0) { 

            $campos['consumidor']['cidade'] = pg_fetch_result($res,0, 'cidade');

        } else {
    
            if ($_REQUEST['consumidor']['nacionalExterior'] == "exterior") {

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
            
                throw new Exception("Cidade não encontrada. ");
            }
        }

        if (!count($msg_erro["msg"])) {
            if($login_fabrica == 148) {
                $auditor_antes = array();
                    if (strlen(pg_last_error()) == 0 && !empty($venda)) {
                        $sqlAntes = "SELECT tbl_venda.*,
                                     tbl_cliente.nome,
                                     tbl_cliente.endereco,
                                     tbl_cliente.cep,
                                     tbl_cliente.fone,
                                     tbl_cliente.complemento,
                                     tbl_cliente.bairro,
                                     tbl_cliente.cidade,
                                     tbl_cliente.numero,
                                     tbl_cliente.cpf,
                                     tbl_cliente.estado,
                                     tbl_cliente.email,
                                     tbl_produto.referencia as referencia,
                                     tbl_produto.descricao as descricao
                              FROM tbl_venda
                              LEFT JOIN tbl_cliente ON tbl_venda.cliente = tbl_cliente.cliente
                              LEFT JOIN tbl_produto ON tbl_venda.produto = tbl_produto.produto 
                              WHERE tbl_venda.fabrica = {$login_fabrica}  
                              AND tbl_venda.venda = {$venda}";
                        $resAntes = pg_query($con, $sqlAntes);
                        $auditor_antes = pg_fetch_assoc($resAntes);
                    }
            }

            $telefone = $campos['consumidor']['telefone']; 

            if ($_REQUEST['consumidor']['nacionalExterior'] == "exterior") {
                 $telefone = $campos['consumidor']['telefone_ex']; 
            }

            if(strlen($consumidor) > 0 ) {

                if ($_REQUEST['consumidor']['nacionalExterior'] != "exterior") {
                    $cliente = pg_fetch_result($result,0, 'cliente');
                    $cep_update = "cpf         = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."',
                                cep         = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',";
                } else {
                    $cliente = $consumidor;
                }
                
                $endereco = pg_escape_string($campos['consumidor']['endereco']);

                $sql = "UPDATE tbl_cliente SET
                                nome        = '{$campos['consumidor']['nome']}',
                                {$cep_update}
                                cidade      = '{$campos['consumidor']['cidade']}',
                                bairro      = '{$campos['consumidor']['bairro']}',
                                endereco    = '{$endereco}',
                                numero      = '{$campos['consumidor']['numero']}',
                                complemento = '{$campos['consumidor']['complemento']}',
                                fone        = '{$telefone}',
                                email       = '{$campos['consumidor']['email']}'
                        WHERE cliente = {$cliente}";

                $resUpdate = pg_query($con,$sql);
            } else {
                 
                $cpf = "'" . preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf']) . "'";
                
                if ($_REQUEST['consumidor']['nacionalExterior'] == "exterior") {
                    $cpf = "NULL";
                }

                $sql = "INSERT INTO tbl_cliente
                            (
                                nome,
                                cpf,
                                cep,
                                cidade,
                                bairro,
                                endereco,
                                numero,
                                complemento,
                                fone,
                                email
                            )
                        VALUES
                            (
                                '{$campos['consumidor']['nome']}',
                                {$cpf},
                                '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cep'])."',
                                '{$campos['consumidor']['cidade']}',
                                '{$campos['consumidor']['bairro']}',
                                '" . pg_escape_string($campos['consumidor']['endereco']) . "',
                                '{$campos['consumidor']['numero']}',
                                '{$campos['consumidor']['complemento']}',
                                '{$telefone}',
                                '{$campos['consumidor']['email']}'
                            )
                        RETURNING cliente
                        ";

                $resInsert = pg_query($con,$sql);
            }

            if (strlen(pg_last_error()) > 0) {
                 throw new Exception("Erro ao inserir Cliente.");
            } else if (empty($cliente)) {
                $cliente = pg_fetch_result($resInsert, 0, "cliente");
            }

            $posto             = $campos['posto']['id'];
            $produto           = $campos['produto']['id'];
            $serie             = $campos['produto']['serie'];
            $serie_motor       = $campos['produto']['serie_motor'];
            $serie_transmissao = $campos['produto']['serie_transmissao'];
            $nota_fiscal       = $campos['produto']['nota_fiscal'];

            $data_nf = $campos['produto']['data_nota_fiscal'];
            list($dia, $mes, $ano) = explode("/", $data_nf);
            $data_nf = "{$ano}-{$mes}-{$dia}";

            if (!empty($venda)) {
                $whereVenda = "AND venda NOT IN({$venda})";
            }

            $sql = "SELECT venda
                    FROM tbl_venda
                    WHERE fabrica = {$login_fabrica}
                    AND produto = {$produto}
                    AND serie = '{$serie}'
                    AND cliente = {$cliente}
                    AND nota_fiscal = '{$nota_fiscal}'
                    {$whereVenda}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                throw new Exception("Já existe uma venda cadastrada para este Produto, Número de Série e Nota Fiscal");
            }

            if ($login_fabrica == 148) {

                if (empty($venda)) {
                    $whereSerie   = "";
                    $whereSerieOS = "";
                } else {
                    $whereSerie   = " AND venda != {$venda}";
                    $whereSerieOS = " AND tbl_venda.venda != {$venda}";

                    $sqlVen    = "SELECT serie FROM tbl_venda WHERE fabrica = {$login_fabrica} AND venda = {$venda}";
                    $resVen    = pg_query($con, $sqlVen);
                    $xserieAnt = pg_fetch_result($resVen, 0, serie);

                    $sqlVOsAnt = "SELECT os
                            FROM tbl_os JOIN tbl_venda ON tbl_venda.serie=tbl_os.serie AND tbl_venda.fabrica={$login_fabrica}
                            WHERE tbl_os.fabrica = {$login_fabrica}
                            AND tbl_os.serie = '{$xserieAnt}'
                            $whereSerieOS";
                    $resVOsAnt = pg_query($con, $sqlVOsAnt);

                    if (pg_num_rows($resVOsAnt) > 0) {
                        throw new Exception("Número de série $xserieAnt já cadastrado na OS, não pode ser alterado");
                    }

                }

                $sqlVOs = "SELECT os
                        FROM tbl_os JOIN tbl_venda ON tbl_venda.serie=tbl_os.serie AND tbl_venda.fabrica={$login_fabrica}
                        WHERE tbl_os.fabrica = {$login_fabrica}
                        AND tbl_os.serie = '{$serie}'
                        AND tbl_os.nota_fiscal = '{$nota_fiscal}'
                        $whereSerieOS";
                $resVOs = pg_query($con, $sqlVOs);

                if (pg_num_rows($resVOs) > 0) {
                    throw new Exception("Número de série ou Nota Fiscal já cadastrados na OS");
                }
            }

            if ($areaAdmin === true) {
                $columnAdmin = ", admin";
                $valueAdmin  = ", {$login_admin}";
                $updateAdmin = ", admin = {$login_admin}";
            }

            if (empty($venda)) {
                $sql = "INSERT INTO tbl_venda
                            (
                                fabrica,";

                if ($login_fabrica <> 161) {
                    $sql .= "
                                posto,";
                }

                $sql .= "       produto,
                                serie,
                                serie_motor,
                                serie_transmissao,
                                nota_fiscal,
                                data_nf,
                                cliente
                                {$columnAdmin}
                            )
                        VALUES
                            (
                                {$login_fabrica},";

                if ($login_fabrica <> 161) {
                    $sql .= "
                                {$posto},";
                }

                $sql .= "
                                {$produto},
                                '{$serie}',
                                '{$serie_motor}',
                                '{$serie_transmissao}',
                                '{$nota_fiscal}',
                                '{$data_nf}',
                                {$cliente}
                                {$valueAdmin}
                            )";
            } else {

                $trocaPosto = '';

                if ($login_fabrica == 148) { 
                        
                    $trocaPosto   = " posto   = {$posto}, ";  
                }

                $sql = "UPDATE tbl_venda SET
                            produto = {$produto},
                            {$trocaPosto} 
                            cliente = {$cliente},
                            serie = '{$serie}',
                            serie_motor = '{$serie_motor}',
                            serie_transmissao = '{$serie_transmissao}',
                            data_nf = '{$data_nf}',
                            nota_fiscal = '{$nota_fiscal}'
                            {$updateAdmin}
                        WHERE fabrica = {$login_fabrica}
                        AND venda = {$venda}";
            }
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao cadastrar a Venda");
            }

            $auditor_depois = array();
            if (strlen(pg_last_error()) == 0 && !empty($venda) && $login_fabrica == 148){
                $sqlDepois = "SELECT tbl_venda.*,
                                     tbl_cliente.nome,
                                     tbl_cliente.endereco,
                                     tbl_cliente.cep,
                                     tbl_cliente.fone,
                                     tbl_cliente.complemento,
                                     tbl_cliente.bairro,
                                     tbl_cliente.cidade,
                                     tbl_cliente.numero,
                                     tbl_cliente.cpf,
                                     tbl_cliente.estado,
                                     tbl_cliente.email,
                                     tbl_produto.referencia as referencia,
                                     tbl_produto.descricao as descricao
                              FROM tbl_venda
                              LEFT JOIN tbl_cliente ON tbl_venda.cliente = tbl_cliente.cliente
                              LEFT JOIN tbl_produto ON tbl_venda.produto = tbl_produto.produto 
                              WHERE tbl_venda.fabrica = {$login_fabrica}  
                              AND tbl_venda.venda = {$venda}";
                $resDepois = pg_query($con, $sqlDepois);
                $auditor_depois = pg_fetch_assoc($resDepois);

                if(pg_num_rows($resDepois) > 0) {
                    auditorLog($venda,$auditor_antes,$auditor_depois,"tbl_venda",$PHP_SELF,'update');
                }
            }

            pg_query($con, "COMMIT");
            $msg_sucesso = "Venda gravada com sucesso";
            unset($_POST, $_RESULT, $venda);
        }
    } catch(Exception $e) {
        $msg_erro["msg"][] = $e->getMessage();
        pg_query($con, "ROLLBACK");
    }

}

$mascara_cnpj = "f";

if (!empty($_GET["id"])) {
    $venda = $_GET["id"];

    if ($areaAdmin === false) {
        $wherePosto = "AND tbl_venda.posto = {$login_posto}";
    }

    $sql = "SELECT
                tbl_venda.posto AS posto_id,
                tbl_posto.nome AS posto_nome,
                tbl_posto_fabrica.codigo_posto AS posto_codigo,
                tbl_cliente.nome AS consumidor_nome,
                tbl_cliente.cpf AS consumidor_cpf,
                tbl_cliente.cep AS consumidor_cep,
                COALESCE(tbl_cidade.estado, tbl_cidade.estado_exterior) AS consumidor_estado,
                tbl_cidade.nome AS consumidor_cidade,
                tbl_cidade.pais AS consumidor_pais,
                tbl_cliente.bairro AS consumidor_bairro,
                tbl_cliente.endereco AS consumidor_endereco,
                tbl_cliente.numero AS consumidor_numero,
                tbl_cliente.complemento AS consumidor_complemento,
                tbl_cliente.fone AS consumidor_telefone,
                tbl_cliente.email AS consumidor_email,
                tbl_venda.produto AS produto_id,
                tbl_produto.referencia AS produto_referencia,
                tbl_produto.descricao AS produto_descricao,
                tbl_produto.voltagem AS produto_voltagem,
                tbl_venda.serie,
                tbl_venda.serie_motor,
                tbl_venda.serie_transmissao,
                TO_CHAR(tbl_venda.data_nf, 'DD/MM/YYYY') AS data_nf,
                tbl_venda.nota_fiscal
            FROM tbl_venda
            LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_venda.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            INNER JOIN tbl_cliente ON tbl_cliente.cliente = tbl_venda.cliente
            INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_cliente.cidade
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_venda.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            WHERE tbl_venda.fabrica = {$login_fabrica}
            AND tbl_venda.venda = {$venda}
            {$wherePosto}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $_RESULT = array(
            "posto" => array(
                "id"     => pg_fetch_result($res, 0, "posto_id"),
                "nome"   => pg_fetch_result($res, 0, "posto_nome"),
                "codigo" => pg_fetch_result($res, 0, "posto_codigo")
            ),
            "consumidor" => array(
                "nome"        => pg_fetch_result($res, 0, "consumidor_nome"),
                "cpf"         => pg_fetch_result($res, 0, "consumidor_cpf"),
                "cep"         => pg_fetch_result($res, 0, "consumidor_cep"),
                "estado"      => pg_fetch_result($res, 0, "consumidor_estado"),
                "cidade"      => pg_fetch_result($res, 0, "consumidor_cidade"),
                "bairro"      => pg_fetch_result($res, 0, "consumidor_bairro"),
                "endereco"    => pg_fetch_result($res, 0, "consumidor_endereco"),
                "numero"      => pg_fetch_result($res, 0, "consumidor_numero"),
                "complemento" => pg_fetch_result($res, 0, "consumidor_complemento"),
                "telefone"    => pg_fetch_result($res, 0, "consumidor_telefone"),
                "email"       => pg_fetch_result($res, 0, "consumidor_email"),
            ),
            "produto" => array(
                "id"                => pg_fetch_result($res, 0, "produto_id"),
                "referencia"        => pg_fetch_result($res, 0, "produto_referencia"),
                "descricao"         => pg_fetch_result($res, 0, "produto_descricao"),
                "voltagem"          => pg_fetch_result($res, 0, "produto_voltagem"),
                "serie"             => pg_fetch_result($res, 0, "serie"),
                "serie_motor"       => pg_fetch_result($res, 0, "serie_motor"),
                "serie_transmissao" => pg_fetch_result($res, 0, "serie_transmissao"),
                "data_nota_fiscal"  => pg_fetch_result($res, 0, "data_nf"),
                "nota_fiscal"       => pg_fetch_result($res, 0, "nota_fiscal")
            )
        );

        if (in_array($login_fabrica, [161])) {
            $_RESULT["consumidor"]["pais"] = pg_fetch_result($res, 0, 'consumidor_pais');
        }

        if(strlen($_RESULT['consumidor']['cpf']) > 11) {
            $mascara_cnpj = "t";
        }
    } else {
        $msg_erro["msg"][] = "Venda não encontrada";
    }
}

if ($areaAdmin === true) {
    $layout_menu = "callcenter";
} else {
    $layout_menu = "cadastro";
}

$title = "Cadastro de Venda de Produto";

include 'cabecalho_new.php';

$plugins = array(
    "mask",
    "maskedinput",
    "shadowbox",
    "datepicker"
);

include "plugin_loader.php";
?>


<style>

#div_trocar_posto, #div_trocar_produto {
    display: none;
    height: 40px;
}

</style>

<script type="text/javascript">

function validaExterior(estado) {
    
    if (estado === "exterior") {
       
        $("#documento").hide();
        $(".obr_ex").hide();
        $(".est_br").hide();
        $(".est_ex").show();

    } else {

        $("#documento").show();
        $(".obr_ex").show();
        $(".est_br").show();        
        $(".est_ex").hide();
    }
}


$(function() {

    <?php 
    if (in_array($login_fabrica, [161])) { ?>

        var jsonPaisEstado = JSON.parse('<?= json_encode(array_map_recursive('utf8_encode', $array_pais_estado)) ?>');

        $("#consumidor_pais").change(function(){

            let sigla = $(this).val();

            if (sigla != "BR") {
                $("#documento label").hide();
                $(".cpf_exterior").show();
                $("#consumidor_cpf, #consumidor_cep").prev(".asteristico").hide();
                $("#consumidor_cpf, #consumidor_cep, #consumidor_telefone").unmask();
            } else {
                $("#documento label").show();
                $(".cpf_exterior").hide();
                $("#consumidor_cpf, #consumidor_cep").prev(".asteristico").show();
                $("#consumidor_cep").mask("99999-999");
                $("#consumidor_cpf").mask("999.999.999-99");
                $("#consumidor_telefone").mask("(99) 9999-9999?9");
            }

            $("#consumidor_estado > option:not(:first)").remove("");
            $("#consumidor_cidade > option:not(:first)").remove("");
            $("#consumidor_bairro, #consumidor_endereco, #consumidor_numero").val("");
            
            if (jsonPaisEstado[sigla] != undefined) {

                $.each(jsonPaisEstado[sigla], function(key, objEstado) {

                   $.each(objEstado, function(sigla, nome) {

                        var option = $("<option></option>", { value: sigla, text: nome});

                        $("#consumidor_estado").append(option);
                    });

                });

            }

        });

    <?php
    } ?>
   
    var mascara_cnpj = '<? echo $mascara_cnpj ;?>';
    $("#consumidor_cep").mask("99999-999");

    $("input[name=cnpjCpf]").change(function(){
        $("#consumidor_cpf").unmask();
        var tipo = $(this).val();
        if(tipo == 'cnpj'){
            $("#consumidor_cpf").mask("99.999.999/9999-99");
        }else{
            $("#consumidor_cpf").mask("999.999.999-99");
        }
    });

    $(".nacional_exterior").change(function() {
        
        var estado = $(this).val();
        validaExterior(estado);
    });

    $("#consumidor_telefone").mask("(99) 9999-9999?9");

    if($("#consumidor_cpf").val().replace(/\d/g, "").length > 11 || mascara_cnpj == 't'){
        $("input[name=cnpjCpf][value=cnpj]").prop("checked", true);
        $("input[name=cnpjCpf][value=cnpj]").change();
    }else if ($("#consumidor_cpf").val().replace(/\d/g, "").length == 11 || mascara_cnpj == 't') {
        $("input[name=cnpjCpf][value=cpf]").prop("checked", true);
        $("input[name=cnpjCpf][value=cpf]").change();
    } 

    <?php
    if (in_array($login_fabrica, [161])) { ?>
        if ($("#consumidor_pais option:selected").val() != "BR") {
            $("#documento label").hide();
            $(".cpf_exterior").show();
            $("#consumidor_cpf, #consumidor_cep").prev(".asteristico").hide();
            $("#consumidor_cpf, #consumidor_cep").unmask();
        } else {
            $("input[name=cnpjCpf]:checked").change();
        }
    <?php
    }
    ?>

    $("#data_nota_fiscal").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

    $("#trocar_posto").click(function() {
        $("#div_informacoes_posto").find("input").val("");
        $("#div_informacoes_posto").find("input[readonly=readonly]").removeAttr("readonly");
        $("#div_informacoes_posto").find("span[rel=lupa_posto]").show();
        $("#div_trocar_posto").hide();
    });

    $("#trocar_produto").click(function() {
        $("#produto_id").val("");
        $("#produto_referencia").prop("readonly", false);
        $("#produto_descricao").prop("readonly", false);
        $("#produto_referencia, #produto_descricao").val("");
        $("#div_informacoes_produto").find("span[rel=lupa_produto]").show();
        $("#div_trocar_produto").hide();
    });

    Shadowbox.init();

    $("span[rel=lupa]").click(function() {
        var tipo = $(this).next().attr("tipo");
        var parametros_adicionais = [];

        if (tipo == "cliente") {
            $(this).next().attr("nacionalidade", $(".nacional_exterior:checked").val());
            parametros_adicionais = ["nacionalidade"];
        }

        $.lupa($(this), parametros_adicionais);
    });

    $("span[rel=lupa_posto]").click(function() {
        $.lupa($(this), ["locadora-revenda"]);
    });

    $("span[rel=lupa_produto]").click(function() {
        $.lupa($(this), ["posto", "ativo"]);
    });

    $("select[id$=_estado]").change(function() {

        if ($("#consumidor_pais").length > 0) {
            var paisSelecionado = $("#consumidor_pais option:selected").val();
        } else {
            var paisSelecionado = "BR";
        }

        busca_cidade($(this).val(), ($(this).attr("id") == "revenda_estado") ? "revenda" : "consumidor", undefined, paisSelecionado);

    });

    $("input[id$=_cep]").blur(function() {

        if ($("#consumidor_pais").length == 0) {
            $("#consumidor_cidade").val(""); //hd_chamado=2715924
            $("#consumidor_bairro").val(""); //hd_chamado=2715924
            $("#consumidor_endereco").val(""); //hd_chamado=2715924
        }

        if ($(this).attr("readonly") == undefined) {
            busca_cep($(this).val(), ($(this).attr("id") == "revenda_cep") ? "revenda" : "consumidor");
        }
    });

    $('#btn-gravar').click(function() {
       $.ajax({
         url: "cadastro_venda.php",
            type: "POST",
            data: {
                ajax_confirm_submit: true,
                referencia: $('#produto_referencia').val(),
                serie: $('#produto_serie').val(),
            },
       }).done(function(data) {
        if (data == 'true') {
            confirmSubmit();
        } else {
            $("[name='frm_cadastro']").submit();
        }
       });
    });

    
});

function confirmSubmit() 
{
    Shadowbox.init();
    Shadowbox.open({
        content: '<div class="form-group"><h4>Número de série já cadastrado. Deseja prosseguir mesmo assim?</h4><div class="col-md-6 form-group" style="margin-top: 20px;"><div class="btn-group" data-toggle="buttons" style="margin-left: 250px;"><input type="button" onclick="clickSubmit()" value="Sim" class="btn btn-success btn-lg"><input type="button" onclick="Shadowbox.close()" value="Cancelar" class="btn btn-danger btn-lg"</div></div></div>',
        player: "html",
        title: "Confirmar Envio",
        height: 115,
        width: 650
    });
}

function clickSubmit()
{
    $("[name='frm_cadastro']").submit();
}
/**
 * Função para retirar a acentuação
 */
function retiraAcentos(palavra){
    var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    palavra = palavra.replace(/[{()}]/g, ''); //hd_chamado=2715924

    for(i = 0; i < palavra.length; i++) {
        if (com_acento.search(palavra.substr(i, 1)) >= 0) {
            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
        } else {
            newPalavra += palavra.substr(i, 1);
        }
    }

    return newPalavra.replace(/\'/g, "").toUpperCase();
}



/**
 * Função que busca as cidades do estado e popula o select cidade
 */
function busca_cidade(estado, consumidor_revenda, cidade, pais = "BR") {
    $("#"+consumidor_revenda+"_cidade").find("option").first().nextAll().remove();

    if (estado.length > 0) {
        $.ajax({
            async: false,
            url: "cadastro_venda.php",
            type: "POST",
            data: { ajax_busca_cidade: true, estado: estado , pais: pais},
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
                        var option = $("<option></option>", { value: value, text: value});

                        $("#"+consumidor_revenda+"_cidade").append(option);
                    });
                }


                $("#"+consumidor_revenda+"_cidade").show().next().remove();
            }
        });
    }

    if(typeof cidade != "undefined" && cidade.length > 0){

        $("#consumidor_cidade option[value='"+cidade+"']").attr('selected','selected');

    }

}

/**
 * Função que faz um ajax para buscar o cep nos correios
 */
function busca_cep(cep, consumidor_revenda) {

    var buscarCep = true;
    if ($("#consumidor_pais").length > 0 && $("#consumidor_pais").val() != "BR") {
        buscarCep = false;
    }

    if (cep.length > 0 && buscarCep) {
        var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

        $.ajax({
            async: true,
            url: "cadastro_venda.php",
            type: "POST",
            dataType:"JSON",
            data: {
                ajax_busca_cep: true,
                cep: cep
            },
            beforeSend: function() {
                $("#"+consumidor_revenda+"_estado").hide().after(img.clone());
                $("#"+consumidor_revenda+"_cidade").hide().after(img.clone());
                $("#"+consumidor_revenda+"_bairro").hide().after(img.clone());
                $("#"+consumidor_revenda+"_endereco").hide().after(img.clone());
            }
        })
        .done(function(data) {
//             data = $.parseJSON(data.responseText);

            if (data.error) {
                alert(data.error);
                $("#"+consumidor_revenda+"_cidade").show().next().remove();
            } else {
                $("#"+consumidor_revenda+"_estado").val(data.uf);

                busca_cidade(data.uf, consumidor_revenda,retiraAcentos(data.cidade).toUpperCase());

                if (data.bairro.length > 0) {
                    $("#"+consumidor_revenda+"_bairro").val(data.bairro);
                }

                if (data.end.length > 0) {
                    $("#"+consumidor_revenda+"_endereco").val(data.end);
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
        });
    }
}

function retorna_posto(retorno) {
    /**
     * A função define os campos código e nome como readonly e esconde o botão
     * O posto somente pode ser alterado quando clicar no botão trocar_posto
     * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
     */
    $("#posto_id").val(retorno.posto);
    $("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
    $("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
    $("#div_trocar_posto").show();
    $("#div_informacoes_posto").find("span[rel=lupa_posto]").hide();

    <?php
    if ($areaAdmin === true) {
    ?>
        $("input[name=lupa_config][tipo=produto]").attr({ posto: retorno.posto });
    <?php
    }
    ?>
}

/**
 * Função de retorno da lupa de produto ela já retorna as inforamções do subproduto caso tenha
 */
function retorna_produto(retorno) {

    $("#produto_id").val(retorno.produto);
    $("#produto_referencia").val(retorno.referencia).attr({ readonly: "readonly" });
    $("#produto_descricao").val(retorno.descricao).attr({ readonly: "readonly" });
    $("#produto_voltagem").val(retorno.voltagem);
    $("#div_trocar_produto").css({ display: "inline-block" });
    $("#div_informacoes_produto").find("span[rel=lupa_produto]").hide();

}

/**
 * Função de retorno da lupa do posto
 */
function retorna_cliente(retorno) {
    /**
     * A função define os campos código e nome como readonly e esconde o botão
     * O posto somente pode ser alterado quando clicar no botão trocar_posto
     * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
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
    } else {
  
        <?php if ($login_fabrica == 148 && retorno.estado == "EX") { ?>
            validaExterior("exterior");
            $("#consumidor_cidade_ex").val(retorno.nome_cidade);
            $("#consumidor_id").val(retorno.cliente);
            $("#consumidor_telefone_ex").val(retorno.telefone);
        <?php } else { ?>
            $("#consumidor_estado").val(retorno.estado);
            $("#consumidor_cidade").val(retorno.cidade);
        <?php } ?>
    }
    
    $("#consumidor_telefone_ex").val(retorno.telefone);
    $("#consumidor_id").val(retorno.cliente);
    $("#consumidor_cidade_ex").val(retorno.nome_cidade);


    $("#consumidor_cep").val(retorno.cep);

    $("#consumidor_bairro").val(retorno.bairro);
    $("#consumidor_endereco").val(retorno.endereco);
    $("#consumidor_numero").val(retorno.numero);
    $("#consumidor_complemento").val(retorno.complemento);
    $("#consumidor_telefone").val(retorno.telefone);
    $("#consumidor_email").val(retorno.email);
}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
    ?>
    <br />
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]) ?></h4>
    </div>
    <?php
}

if (!empty($msg_sucesso)) {
?>
    <br />
    <div class="alert alert-success">
        <h4><?=$msg_sucesso?></h4>
    </div>
<?php
}
?>
<?php 
    $url_venda = "";
    if (strlen($venda) > 0 && isset($_GET['acao'])) {
        $url_venda = "?id=$venda&acao=".$_GET['acao'];
    }
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<?php $usa_ob = ($login_fabrica == 148) ? "<h5 class='asteristico obr_ex'>*</h5>" : ""; ?>

<form name='frm_cadastro' method="POST" action="cadastro_venda.php<?php echo $url_venda;?>" class="form-search form-inline tc_formulario" >
    <input type="hidden" name="id" value="<?=$venda?>" />
    <?php
    if ($areaAdmin === true) {
        if (strlen(getValue("posto[id]")) > 0 && empty($venda)) {
            $posto_readonly     = "readonly='readonly'";
            $posto_esconde_lupa = "style='display: none;'";
            $posto_mostra_troca = "style='display: block;'";
        }

        if (!empty($venda) > 0 && $login_fabrica != 148) {
            $posto_readonly     = "readonly='readonly'";
            $posto_esconde_lupa = "style='display: none;'";
        }
        ?>

        <?php if ($login_fabrica <> 161): ?>
        <div id="div_informacoes_posto" class="tc_formulario">
            <div class="titulo_tabela">Informações do Posto Autorizado</div>

            <br />

            <input type="hidden" id="posto_id" name="posto[id]" value="<?=getValue('posto[id]')?>" />

            <div class="row-fluid">
                <div class="span1"></div>

                <div class="span4">
                    <div class='control-group <?=(in_array('posto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="posto_codigo">Código</label>
                        <div class="controls controls-row">
                            <div class="span10 input-append">
                                <h5 class="asteristico">*</h5>
                                <input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>" <?=$posto_readonly?> />
                                <span class="add-on" rel="lupa_posto" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" locadora-revenda="t" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span6">
                    <div class='control-group <?=(in_array('posto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="posto_nome">Nome</label>
                        <div class="controls controls-row">
                            <div class="span11 input-append">
                                <h5 class="asteristico">*</h5>
                                <input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>" <?=$posto_readonly?> />
                                <span class="add-on" rel="lupa_posto" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" locadora-revenda="t" />

                            </div>
                        </div>
                    </div>
                </div>

                <div class="span1"></div>
            </div>

            <div id="div_trocar_posto" class="row-fluid" <?=$posto_mostra_troca?> >
                <div class="span1"></div>
                <div class="span10">
                    <button type="button" id="trocar_posto" class="btn btn-danger" >Alterar Posto Autorizado</button>
                </div>
            </div>
        </div>
        <?php endif ?>
    <?php
    } else {
        echo "<input type='hidden' id='posto_id' name='posto[id]' value='{$login_posto}' />";
    }
    ?>

        <div class='titulo_tabela '>Informações do Cliente</div>
        <br/>
        <?php
        if (in_array($login_fabrica, [161])) { ?>
            <div class="row-fluid">
                <div class="span4"></div>
                <div class="span3">
                    <div class='control-group <?=(in_array('consumidor[pais]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label">
                            País
                            <select id="consumidor_pais" name="consumidor[pais]" class="span12" style="width: 200px;">
                                <?php
                                foreach ($array_paises() as $sigla => $nome_pais) {

                                    if (!empty(getValue('consumidor[pais]'))) {
                                        $selected = ($sigla == getValue('consumidor[pais]')) ? "selected" : "";
                                    } else {
                                        $selected = ($sigla == "BR") ? "selected" : "";
                                    }

                                    echo "<option value='{$sigla}' {$selected}>{$nome_pais}</option>";
                                } ?>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        <?php
        }

        if ($login_fabrica == 148) { ?>

            <div class="row-fluid">
                <div class="span1"></div>

                <div class="span3">
                    <div class='control-group <?=(in_array('consumidor[nacionalExterior]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="nacional_exterior">
                            <?php 
                            echo 'Nacionalidade da venda:' . '<br>'; 
                            
                            if (isset($_GET['id']) && $login_fabrica == 148) {

                                $id_venda = $_GET['id'];

                                $query = "SELECT tbl_cidade.nome AS nome, tbl_cidade.estado AS estado
                                          FROM tbl_venda 
                                          JOIN tbl_cliente ON tbl_cliente.cliente = tbl_venda.cliente
                                          JOIN tbl_cidade ON tbl_cliente.cidade = tbl_cidade.cidade
                                          WHERE venda = {$id_venda}";
       
                                $res_query = pg_query($con, $query);

                                $venda_obj = pg_fetch_object($res_query);
                            }

                            if (isset($venda_obj->estado) && $venda_obj->estado == "EX") { ?>
                                <label>
                                    Nacional <input class="nacional_exterior" type="radio" id="nacional_exterior" name="consumidor[nacionalExterior]" <? echo (getValue('consumidor[nacionalExterior]')=='nacional') ? 'checked="checked"': ''; ?> value="nacional" >
                                </label>
                                 <label>
                                    Exterior <input class="nacional_exterior" type="radio" id="exterior_nacional" name="consumidor[nacionalExterior]" <? echo (getValue('consumidor[nacionalExterior]')=='exterior') ? 'checked="checked"': ''; ?> value="exterior" checked>
                                 </label>

                            <?php } else { ?>
                                <label>
                                    Nacional <input class="nacional_exterior" type="radio" id="nacional_exterior" name="consumidor[nacionalExterior]" <? echo (getValue('consumidor[nacionalExterior]')=='nacional') ? 'checked="checked"': ''; ?> value="nacional" checked>
                                </label>
                                <label>
                                    Exterior <input class="nacional_exterior" type="radio" id="exterior_nacional" name="consumidor[nacionalExterior]" <? echo (getValue('consumidor[nacionalExterior]')=='exterior') ? 'checked="checked"': ''; ?> value="exterior" >
                                </label>

                            <?php } if (getValue('consumidor[nacionalExterior]') == 'exterior' || isset($venda_obj->estado) && $venda_obj->estado == "EX") { ?>
                                <input type="hidden" name="nacional_ex" id="nacional_ex" value="exterior">
                            <?php } else { ?>
                                <input type="hidden" name="nacional_ex" id="nacional_ex" value="nacional">
                            <?php  } ?>
                            
                        </label>

    
                    </div>
                </div>
            </div>

        <?php } ?>

        <div class="row-fluid">
        <div class="span1"></div>


            <div class="span3">
                <div class='control-group <?=(in_array('consumidor[nome]', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="consumidor_nome">Nome</label>
                    <div class="controls controls-row">
            <div class="span10 input-append">
                <h5 class='asteristico'>*</h5>
                            <input id="consumidor_nome" name="consumidor[nome]" class="span12" type="text" value="<?=getValue('consumidor[nome]')?>" maxlength="50" />
                            <span class="add-on" rel="lupa" >
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="cliente" parametro="nome" nacionalidade="" />
                            <input id="consumidor_id" name="consumidor[cliente]" class="span12 " type="hidden" value="<?=getValue('consumidor[cliente]')?>"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="span3" id='documento'>
                <div class='control-group <?=(in_array('consumidor[cpf]', $msg_erro['campos'])) ? "error" : "" ?>'>
                    <label class="control-label" for="consumidor_cpf">
                        <?
                        if (strlen($cliente)) {
                            echo 'CPF/CNPJ';
                        } else {

                            $check_cnpj = "";
                            $check_cpf  = "checked";

                            if (strlen($_REQUEST['consumidor']['cpf']) > 11) {


                                $check_cnpj = "checked";
                                $check_cpf  = "";

                            }

                        ?>
                            CPF <input type="radio" id="cpf_cnpj" name="cnpjCpf" <?= $check_cpf ?> value="cpf" >
                            CNPJ <input type="radio" id="cnpj_cpf" name="cnpjCpf" <?= $check_cnpj ?> value="cnpj" >
                        
                        <?
                        }
                        ?>
                    </label>
                    <label class="cpf_exterior" style="display: none;">
                        Identificador
                    </label>
                    <div class="controls controls-row">
            <div class="span10 input-append">
            <?php
          if($login_pais == 'BR'){ ?>
                 <h5 class='asteristico'>*</h5>
              <?php
          }
            ?>
                            <input id="consumidor_cpf" name="consumidor[cpf]" class="span12 " type="text" value="<?=getValue('consumidor[cpf]')?>" />
                            <span class="add-on" rel="lupa" ><i class="icon-search"></i></span>
                            <input type="hidden" name="lupa_config" tipo="cliente" parametro="cpf" />
                        </div>
                    </div>
                </div>
            </div>

        <div class="span2">
            <div class='control-group <?=(in_array('consumidor[cep]', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label" for="consumidor_cep">CEP</label>
                <div class="controls controls-row">
            <div class="span12">
            <?php
            if($login_pais == 'BR'){
            ?>
            <h5 class='asteristico obr_ex'>*</h5>
            <?php
            }
            ?>
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
            <?php if($login_pais == 'BR') { ?>
                <h5 class='asteristico obr_ex'>*</h5> 
            <?php } ?>
                <select id="consumidor_estado" name="consumidor[estado]" class="span12 est_br">
                        <option value="" >Selecione</option>
                        <?php
                        if (empty(getValue("consumidor[pais]"))) {
                            foreach ($array_estados() as $sigla => $nome_estado) {
                                $selected = ($sigla == getValue('consumidor[estado]')) ? "selected" : "";

                                echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                            }
                        } else {
                            foreach ($array_estados(getValue('consumidor[pais]')) as $sigla => $nome_estado) {
                                $selected = ($sigla == getValue('consumidor[estado]')) ? "selected" : "";

                                echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                            }
                        }
                        ?>
                </select>
                <input id="consumidor_estado_ex" value="EX - Exterior" readonly type="text" name="consumidor[estado_ex]" class="span12 est_ex" style="display:none"> 
            </div>
                </div>
            </div>
        </div>


        <div class="span1"></div>
    </div>

    <div class="row-fluid">
            <div class="span1"></div>


            <div class="span3">
                <div class="control-group <?=(in_array('consumidor[cidade]', $msg_erro['campos'])) ? "error" : "" ?>">
                    <label class="control-label" for="consumidor_cidade">Cidade</label>
                    <div class="controls controls-row">
            <div class="span12">
            <?php
            if($login_pais == 'BR'){
            ?>
            <h5 class='asteristico obr_ex'>*</h5>
            <?php
            }
            ?>
                            <select id="consumidor_cidade" name="consumidor[cidade]" class="span12 est_br">
                                <option value="" >Selecione</option>

                                <?php

                                if (strlen(getValue("consumidor[estado]")) > 0) {

                                    if (!empty(getValue("consumidor[pais]")) && getValue("consumidor[pais]") != "BR") {
        
                                        $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                                                FROM tbl_cidade 
                                                WHERE UPPER(estado_exterior) = UPPER('".getValue("consumidor[estado]")."')
                                                AND UPPER(pais) = UPPER('".getValue("consumidor[pais]")."')
                                                ";
                                        $res = pg_query($con, $sql);
                                       
                                    } else {

                                        $sql = "SELECT DISTINCT * FROM (
                                                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".getValue("consumidor[estado]")."')
                                                    UNION (
                                                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".getValue("consumidor[estado]")."')
                                                    )
                                                ) AS cidade
                                                ORDER BY cidade ASC";

                                    }

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
                            <?php 
                                if (isset($venda_obj->nome)) { ?>

                                    <input type="text" id="consumidor_cidade_ex" name="consumidor[cidade_ex]" class="span12 est_ex" style="display:none" value="<?php echo $venda_obj->nome; ?>">
                                <?php } else { ?>
                                    <input type="text" id="consumidor_cidade_ex" name="consumidor[cidade_ex]" class="span12 est_ex" value="<?php echo (getValue("consumidor[cidade]"))?>" style="display:none">
                                <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="span3">
                <div class='control-group <?=(in_array('consumidor[bairro]', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="consumidor_bairro">Bairro</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <?=$usa_ob?>
                            <input id="consumidor_bairro" name="consumidor[bairro]" class="span12" type="text" value="<?=getValue('consumidor[bairro]')?>" maxlength="30"  />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span4">
                <div class='control-group <?=(in_array('consumidor[endereco]', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="consumidor_endereco">Endereço</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <?=$usa_ob?>
                            <input id="consumidor_endereco" name="consumidor[endereco]" class="span12" type="text" value="<?=getValue('consumidor[endereco]')?>" maxlength="60" />
                        </div>
                    </div>
                </div>
            </div>


            <div class="span1"></div>
        </div>

        <div class="row-fluid">
            <div class="span1"></div>

             <div class="span1">
                <div class='control-group <?=(in_array('consumidor[numero]', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="consumidor_numero">Número</label>
                    <div class="controls controls-row">
												<div class="span12">
												<?=$usa_ob?>
                            <input id="consumidor_numero" name="consumidor[numero]" class="span12" type="text" value="<?=getValue('consumidor[numero]')?>" maxlength="20" />
                        </div>
                    </div>
                </div>
            </div>


            <div class="span3">
                <div class='control-group <?=(in_array('consumidor[complemento]', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="consumidor_complemento">Complemento</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <input id="consumidor_complemento" name="consumidor[complemento]" class="span12" type="text" value="<?=getValue('consumidor[complemento]')?>" maxlength="30" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span3">
                <div class="control-group <?=(in_array('consumidor[telefone]', $msg_erro['campos'])) ? "error" : "" ?>">
                    <label class="control-label" for="consumidor_telefone">Telefone</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <?=$usa_ob?>
                            <input id="consumidor_telefone" name="consumidor[telefone]" class="span12 est_br" type="text" value="<?=getValue('consumidor[telefone]')?>"  maxlength="20" />
                            <input id="consumidor_telefone_ex" name="consumidor[telefone_ex]" class="span12 est_ex" type="text" value="<?=getValue('consumidor[telefone]')?>"  maxlength="20" style="display:none"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="span3">
               <div class='control-group <?=(in_array('consumidor[email]', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="consumidor_email">Email</label>
                    <div class="controls controls-row">
                        <h5 class='asteristico'>*</h5>
                        <div class="span12">
                            <input id="consumidor_email" name="consumidor[email]" class="span12" type="text" value="<?=getValue('consumidor[email]')?>"  />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span1"></div>
        </div>

        <div id="div_informacoes_produto" class="tc_formulario">
            <div class="titulo_tabela">Informações do Produto</div>

            <?php

            if (strlen(getValue("produto[id]")) > 0 && empty($venda)) {
                $produto_readonly      = "readonly='readonly'";
                $produto_esconde_lupa  = "style='display: none;'";
                $produto_mostra_trocar = "style='display: inline-block;'";
            }

            if (!empty($venda) && $login_fabrica != 148) {
                $produto_readonly      = "readonly='readonly'";
                $produto_esconde_lupa  = "style='display: none;'";
                if ($login_fabrica == 148 && $_GET['acao'] == "alterar") {
                    $produto_mostra_trocar = "style='display: inline-block;'";
                }
            }

            ?>

            <br />

            <input type="hidden" id="produto_id" name="produto[id]" value="<?=getValue('produto[id]')?>" />

            <div class="row-fluid">
                <div class="span1"></div>

                <div class="span3">
                    <div class='control-group <?=(in_array('produto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="produto_referencia">Referência</label>
                        <div class="controls controls-row">
                            <h5 class='asteristico'>*</h5>
                            <div class="span10 input-append">
                                <input id="produto_referencia" name="produto[referencia]" class="span12" type="text" value="<?=getValue('produto[referencia]')?>" <?=$produto_readonly?> maxlength="20" />
                                <span class="add-on" rel="lupa_produto" <?=$produto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posto="<?=$login_posto?>" ativo="t" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3" >
                    <div class='control-group <?=(in_array('produto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="produto_descricao">Descrição</label>
                        <div class="controls controls-row">
                            <h5 class='asteristico'>*</h5>
                            <div class="span10 input-append">
                                <input id="produto_descricao" name="produto[descricao]" class="span12" type="text" value="<?=getValue('produto[descricao]')?>" <?=$produto_readonly?> maxlength="80" />
                                <span class="add-on" rel="lupa_produto" <?=$produto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posto="<?=$login_posto?>" ativo="t" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <div class='control-group <?=(in_array('produto[voltagem]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="produto_voltagem">Voltagem</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="produto_voltagem" name="produto[voltagem]" class="span12" type="text" value="<?=getValue('produto[voltagem]')?>" <?php echo ($login_fabrica == 143) ? "" : "readonly='readonly'"; ?> />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span1"></div>
            </div>
            <div class="row-fluid">
                <div class="span1"></div>

                <div class="span3">
                    <div class='control-group <?=(in_array('produto[serie]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="produto_serie">Série do Produto</label>
                        <div class="controls controls-row">
                            <h5 class='asteristico'>*</h5>
                            <div class="span12">
                                <input id="produto_serie" name="produto[serie]" class="span12" type="text" value="<?=getValue('produto[serie]')?>" maxlength="20" />
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($login_fabrica == 161): ?>
                <div class="span3">
                    <div class='control-group <?=(in_array('produto[data_nota_fiscal]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="data_nota_fiscal">Data da Nota Fiscal</label>
                        <div class="controls controls-row">
                            <div class="span6">
                                <h5 class='asteristico'>*</h5>
                                <input id="data_nota_fiscal" name="produto[data_nota_fiscal]" class="span12" type="text" value="<?=getValue('produto[data_nota_fiscal]')?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <div class='control-group <?=(in_array('produto[nota_fiscal]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="data_abertura">Nota Fiscal</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class='asteristico'>*</h5>
                                <input id="nota_fiscal" name="produto[nota_fiscal]" class="span12" type="text" value="<?=getValue('produto[nota_fiscal]')?>" maxlength="20" />
                            </div>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <div class="span3">
                    <div class='control-group <?=(in_array('produto[serie_motor]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="produto_serie">Série do Motor</label>
                        <div class="controls controls-row">
                            <h5 class='asteristico'>*</h5>
                            <div class="span12">
                                <input id="produto_serie" name="produto[serie_motor]" class="span12" type="text" value="<?=getValue('produto[serie_motor]')?>" maxlength="20" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <div class='control-group <?=(in_array('produto[serie_transmissao]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="produto_serie">Série da Transmissão</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <input id="produto_serie" name="produto[serie_transmissao]" class="span12" type="text" value="<?=getValue('produto[serie_transmissao]')?>" maxlength="30" />
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif ?>

                <div class="span1"></div>
            </div>

            <?php if ($login_fabrica <> 161): ?>
            <div class="row-fluid">
                <div class="span1"></div>

                <div class="span3">
                    <div class='control-group <?=(in_array('produto[data_nota_fiscal]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="data_nota_fiscal">Data da Nota Fiscal</label>
                        <div class="controls controls-row">
                            <div class="span6">
                                <h5 class='asteristico'>*</h5>
                                <input id="data_nota_fiscal" name="produto[data_nota_fiscal]" class="span12" type="text" value="<?=getValue('produto[data_nota_fiscal]')?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span3">
                    <div class='control-group <?=(in_array('produto[nota_fiscal]', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="data_abertura">Nota Fiscal</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class='asteristico'>*</h5>
                                <input id="nota_fiscal" name="produto[nota_fiscal]" class="span12" type="text" value="<?=getValue('produto[nota_fiscal]')?>" maxlength="20" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="span1"></div>
            </div>
            <?php endif ?>

            <div class='row-fluid' id="div_trocar_produto" <?=$produto_mostra_trocar?> >

                <div class="span1"></div>

                <div class="span10">
                    <div style='padding-top: 10px; padding-bottom: 10px;'>
                        <button type="button" id="trocar_produto" class="btn btn-danger" >Alterar Produto</button>
                    </div>
                </div>

            </div>

            <div style='clear: both;'></div>

        </div>
        <br />

    <p class="tac">
       <?php if ($login_fabrica == 148) { ?>
       <input type="hidden" name="gravar" value="Gravar"> 
            <input type="button" class="btn" id="btn-gravar" value="Gravar">
         <?php } else { ?>
            <input type="submit" class="btn" name="gravar" id="btn-gravar" value="Gravar" />
       <?php } ?>
    </p>
    <?php if (strlen($venda) > 0 && $areaAdmin) {?>

   

<?php }?>
    
    <br> 

</div>
<div class="container">
<center>
 <a rel='shadowbox' style="color:#63798D; font-size: 11px; font-weight:bold;" href='relatorio_log_alteracao.php?parametro=tbl_venda&id=<?php echo $venda; ?>'>Visualizar Log Auditor</a>
</center>
</div>
    <br />

</form>

<br>







<script type="text/javascript">
   $( function() {
        window.onload = function() {

    var es = $("#nacional_ex").val();
    
        validaExterior(es);
    }
   }); 

</script>
<?php

include "rodape.php";

?>
