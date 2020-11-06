<?php
$areaAdminRepresentante = preg_match('/\/admin_representante\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once 'fn_traducao.php';
if (!$moduloGestaoContrato) {
    echo "<meta http-equiv=refresh content=\"0;URL=menu_gerencia.php\">";
}

use GestaoContrato\TipoContrato;
use GestaoContrato\Contrato;
use GestaoContrato\ContratoStatus;
use GestaoContrato\TabelaPreco;
use GestaoContrato\ContratoStatusMovimento;
use GestaoContrato\Os;

$objContrato       = new Contrato($login_fabrica, $con);
$objTipoContrato   = new TipoContrato($login_fabrica, $con);
$objContratoStatus = new ContratoStatus($login_fabrica, $con);
$objTabelaPreco    = new TabelaPreco($login_fabrica, $con);
$objContratoStatusMovimento    = new ContratoStatusMovimento($login_fabrica, $con);
$objContratoOS    = new Os($login_fabrica, $con);


$tipo_contrato_array        = $objTipoContrato->get();
$status_contrato            = $objContratoStatus->get(null,"Aguardando Aprovação da Proposta");
$contrato_tabela_array      = $objTabelaPreco->get();
$array_status_contrato      = $objContratoStatus->get();

$contrato    = $_REQUEST["contrato"];

if (isset($_REQUEST["tipo"]) && $_REQUEST["tipo"] == "proposta") {
    $label_contrato = "da  Proposta";

    $exibe_status_contrato            = false;
    $exibe_posto            = false;
    $exibe_dias             = false;
    $exibe_descricao        = false;
    $exibe_data_vigencia    = false;
    $exibe_numero_contrato  = false;
    $proposta               = strlen($_REQUEST["proposta"]) > 0 ? $_REQUEST["proposta"] : true;
    $disabled_cliente       = "";
    $disabled_representante = "";

} else {
    $label_contrato = "do  Contrato";
    $disabled_cliente       = "disabled='disabled'";
    $disabled_representante = "disabled='disabled'";
    $exibe_posto            = true;
    $exibe_dias             = true;
    $exibe_dias             = true;
    $exibe_descricao        = true;
    $exibe_data_vigencia    = true;
    $exibe_status_contrato    = true;
    $exibe_numero_contrato  = true;
    $proposta               = strlen($_REQUEST["proposta"]) > 0 ? $_REQUEST["proposta"] : false;

}

if (isset($_REQUEST["contrato"]) && strlen($_REQUEST["contrato"]) > 0) {
    $retornoContrato = $objContrato->get($_REQUEST["contrato"]);
    if (count($retornoContrato) > 0) {
        extract($retornoContrato[0]);
        $campo_extra = json_decode($campo_extra,1);
        extract($campo_extra);
        $data_vigencia = strlen($data_vigencia_date) > 0 ? geraDataNormal($data_vigencia_date) : "";

        $ultimo_status_contrato = $objContratoStatusMovimento->getUltimoStatusByContrato($contrato);
        $contrato_status = $ultimo_status_contrato["contrato_status"];
    }
}

if ($_POST["tipo_acao"] == "add") {
    $posto_id           = $_POST["posto_id"];
    $numero_contrato    = $_POST["numero_contrato"];
    $data_vigencia      = $_POST["data_vigencia"];
    $genero_contrato    = $_POST["genero_contrato"];
    $tipo_contrato      = $_POST["tipo_contrato"];
    $descricao          = $_POST["descricao"];
    $cliente            = $_POST["cliente"];
    $cliente_email      = $_POST["cliente_email"];
    $representante      = $_POST["representante"];
    $contrato_tabela    = $_POST["contrato_tabela"];
    $dia_preventiva     = $_POST["dia_preventiva"];
    $posto_nome         = $_POST["posto_nome"];
    $posto_codigo       = $_POST["posto_codigo"];
    $qtde_corretiva     = $_POST["qtde_corretiva"];
    $qtde_preventiva     = $_POST["qtde_preventiva"];
    $mao_obra_fixa     = $_POST["mao_obra_fixa"];
    $valor_mao_obra_fixa     = $_POST["valor_mao_obra_fixa"];
    $desconto_representante     = $_POST["desconto_representante"];
    $valor_total_produtos     = $_POST["valor_total_produtos"];
    $valor_contrato_final     = $_POST["valor_contrato_final"];
    $desconto_representante_bd     = $_POST["desconto_representante_bd"];
    

    if ($mao_obra_fixa == "sim" AND strlen(trim($valor_mao_obra_fixa)) == 0){
        $msg_erro["msg"][] = "Campo Valor de mão de obra é obrigatório";
        $msg_erro["campos"][] = "error_valor_mao_obra_fixa";
    }

    if (strlen($descricao) == 0 && !$proposta) {
        $msg_erro["msg"][] = "Campo Descrição do Contrato é obrigatório";
        $msg_erro["campos"][] = "descricao";
    }

    if (strlen($contrato_tabela) == 0) {
        $msg_erro["msg"][] = "Campo Tabela de Preço é obrigatório";
        $msg_erro["campos"][] = "contrato_tabela";
    }

    if (strlen($representante) == 0) {
        $msg_erro["msg"][] = "Campo Nome/Razão Social Representante é obrigatório";
        $msg_erro["campos"][] = "representante_nome";
    }

    if (strlen($representante) == 0) {
        $msg_erro["msg"][] = "Campo Código Representante é obrigatório";
        $msg_erro["campos"][] = "representante_codigo";
    }

    if (strlen($posto_codigo) == 0 && !$proposta) {
        $msg_erro["msg"][] = "Campo Código Posto é obrigatório";
        $msg_erro["campos"][] = "posto_codigo";
    }

    if (strlen($posto_nome) == 0 && !$proposta) {
        $msg_erro["msg"][] = "Campo Nome Posto é obrigatório";
        $msg_erro["campos"][] = "posto_nome";
    }

    if (strlen($numero_contrato) == 0 && !$proposta) {
        $msg_erro["msg"][] = "Campo Seu Contrato é obrigatório";
        $msg_erro["campos"][] = "numero_contrato";
    }

    if (strlen($dia_preventiva) == 0 && strlen($qtde_preventiva) > 0 && !$proposta) {
        $msg_erro["msg"][] = "Campo Dia da Preventiva é obrigatório";
        $msg_erro["campos"][] = "dia_preventiva";
    }

    if (strlen($data_vigencia) == 0 && !$proposta) {
        $msg_erro["msg"][] = "Campo Data Vigência é obrigatório";
        $msg_erro["campos"][] = "data_vigencia";
    }

    if (strlen($genero_contrato) == 0) {
        $msg_erro["msg"][] = "Campo Genêro {$label_contrato} é obrigatório";
        $msg_erro["campos"][] = "genero_contrato";
    }    

    if (strlen($tipo_contrato) == 0) {
        $msg_erro["msg"][] = "Campo Tipo {$label_contrato} é obrigatório";
        $msg_erro["campos"][] = "tipo_contrato";
    }

    if (strlen($cliente_cpf) == 0) {
        $msg_erro["msg"][] = "Campo CPF/CNPJ cliente é obrigatório";
        $msg_erro["campos"][] = "cliente_cpf";
    }

    if (strlen($cliente_cpf) == 0) {
        $msg_erro["msg"][] = "Campo Nome/Razão Social cliente é obrigatório";
        $msg_erro["campos"][] = "cliente_nome";
    }

    if (strlen($cliente_email) == 0 && strlen($contrato) == 0) {
        $msg_erro["msg"][] = "Campo E-mail cliente é obrigatório";
        $msg_erro["campos"][] = "cliente_email";
    }

    if (strlen($cliente) == 0 && count($msg_erro["msg"]) == 0) {
        $dadosSaveCliente = [
                    "cnpj"   => str_replace(["-","/","."], "", $_POST["cliente_cpf"]),
                    "nome"   => $_POST["cliente_nome"],
                    "email"   => $_POST["cliente_email"],
                    "fone"              => $_POST["cliente_fone"],
                    "celular"           => $_POST["cliente_celular"],
                    "cep"               => str_replace(["-","/","."], "", $_POST["cliente_cep"]),
                    "endereco"          => substr($_POST["cliente_endereco"],0,30),
                    "numero"            => $_POST["cliente_numero"],
     		    "bairro"            => substr(trim($_POST['cliente_bairro']),0, 38),
                    "complemento"       => $_POST["cliente_complemento"],
                    "cidade"            => $_POST["cliente_cidade"],
                    "estado"            => $_POST["cliente_uf"],
                    "codigo"            => str_replace(["-","/","."], "", $_POST["cliente_cpf"]),
                    "codigo_representante"            => str_replace(["-","/","."], "", $_POST["cliente_cpf"]),
                    "login_fabrica"     => $login_fabrica,
                ];

        $retornoCliente   = $objContrato->addClienteAdmin($dadosSaveCliente);
        if ($retornoCliente["erro"]) {
            $msg_erro["msg"][] = $retornoCliente["msn"];
        } else {
            $cliente = $retornoCliente["cliente_admin"];
        }
    }
    if (count($msg_erro["msg"]) == 0) {
        $resB = pg_query($con,"BEGIN TRANSACTION");
        $edit = false;
        if ($proposta && strlen($contrato) == 0) {

            $dadosSave = [
                        "numero_contrato"   => 1,
                        "genero_contrato"   => $genero_contrato,
                        "representante"   => $representante,
                        "descricao"         =>  ' ',
                        "tipo_contrato"     => $tipo_contrato,
                        "contrato_tabela"   => $contrato_tabela,
                        "qtde_preventiva"   => strlen($qtde_preventiva) == 0 ? 0 : $qtde_preventiva,
                        "qtde_corretiva"    => strlen($qtde_corretiva) == 0 ? 0 : $qtde_corretiva,
                        "cliente"           => $cliente,
                        "contrato_status"   => $status_contrato["contrato_status"],
                       "desconto_representante"           => $desconto_representante,
                        "valor_total_produtos"           => $valor_total_produtos,
                        "valor_contrato_final"           => $valor_contrato_final,
                        "desconto_representante_bd"           => $desconto_representante_bd,
                    ];
            $retorno   = $objContrato->addProposta($dadosSave);

            $retornoStatus = $objContratoStatusMovimento->add($retorno["contrato"],$status_contrato["contrato_status"]);

            if (isset($retornoStatus["erro"]) && $retornoStatus["erro"] == true) {
                $msg_erro["msg"][] = $retornoStatus["msn"];
            } 
        } else {
            $edit = true;
            $dadosSave = [
                            "dia_preventiva"        => strlen($dia_preventiva) > 0 ? $dia_preventiva : "",
                            "descricao"     => strlen($descricao) > 0 ? $descricao : "",
                            "data_vigencia"   => strlen($data_vigencia) > 0 ? geraDataBD($data_vigencia) : "",
                            "qtde_preventiva"         => $qtde_preventiva,
                            "qtde_corretiva"   => $qtde_corretiva,
                            "mao_obra_fixa"   => $mao_obra_fixa,
                            "valor_mao_obra_fixa"   => $valor_mao_obra_fixa,
                            "posto_id"           => $posto_id,
                            "desconto_representante"           => $desconto_representante,
                            "valor_total_produtos"           => $valor_total_produtos,
                            "valor_contrato_final"           => $valor_contrato_final,
                            "desconto_representante_bd"           => $desconto_representante_bd,
                            "tipo_contrato"           => $tipo_contrato,
                        ];

            $retorno   = $objContrato->editContrato($contrato, $dadosSave);

        }

        if (isset($retorno["erro"]) && $retorno["erro"] == true) {
            $msg_erro["msg"][] = $retorno["msn"];
        } else {

            $total_contrato = [];

            for ($i=0; $i <= $_POST["total_produtos"]; $i++) { 

                $dadosProduto =  [];
                $produto_referencia = $_POST["produto_referencia_".$i];

                $dadosProduto = $objContrato->getProduto($produto_referencia);

                if (strlen($produto_referencia) == 0 OR (empty($dadosProduto) || isset($retornoItens["erro"]) && $retornoItens["erro"] == true)) {
                    continue;
                }

                $preco        = $_POST["preco_".$i];
                $horimetro    = empty($_POST["horimetro_".$i]) ? 0 : $_POST["horimetro_".$i];
                $preventiva   = $_POST["preventiva_".$i];
                $total_contrato[] = $preco;
                $dadosSaveItens = [
                    "contrato" => $retorno["contrato"],
                    "produto" => $dadosProduto["produto"],
                    "preco" => $preco,
                    "horimetro" => $horimetro,
                    "preventiva" => ($preventiva == "t") ? "t" : "f",
                ];
                
                $retornoItens = $objContrato->addItens($dadosSaveItens,$edit);

                if (isset($retornoItens["erro"]) && $retornoItens["erro"] == true) {
                    $msg_erro["msg"][] = $retornoItens["msn"];
                } 
            
            }

            //$atualizaTotalContrato = $objContrato->atualizaTotalContrato($retorno["contrato"], array_sum($total_contrato));

            if (count($msg_erro["msg"]) == 0) {
                $msg_sucesso["msg"][] = "Gravado com sucesso";
            }
        }

        //verifica status
        $statusAtualContrato = $objContrato->getUltimoStatusContrato($retorno["contrato"]);

        if (isset($statusAtualContrato["erro"]) && strlen($statusAtualContrato["msn"]) > 0) {
            $msg_erro["msg"][] = $statusAtualContrato["msn"];
        } else {

            $x_status_contrato = $objContratoStatus->get(null,"Aguardando Transporte");


            if (trim($x_status_contrato["contrato_status"]) == $_POST['contrato_status']) {
                //muda status
                $novo_status_contrato = $objContratoStatus->get(null,"Aguardando Transporte");
                $anexoValida = $objContrato->verificaAnexoAssinatura($retorno["contrato"]);
                if (!$anexoValida) {
                    $msg_erro["msg"][] = "Obrigatorio anexar o contrato assinado";
		    $msg_sucesso = [];

                } else { 

                $retorno_status_mov = $objContratoStatusMovimento->add($retorno["contrato"], $novo_status_contrato["contrato_status"]);

                if (isset($retorno_status_mov["erro"]) && strlen($retorno_status_mov["msn"]) > 0) {
                    $msg_erro["msg"][] = $retorno_status_mov["msn"];
                }
              }
            } elseif (strlen($_POST["contrato_status"]) > 0 && trim($statusAtualContrato["descricao"]) != $_POST["contrato_status"]) {

                $novo_status_contrato = $objContratoStatus->get($_POST["contrato_status"]);
                if (isset($novo_status_contrato["erro"]) && strlen($novo_status_contrato["msn"]) > 0) {
                    $msg_erro["msg"][] = $novo_status_contrato["msn"];

		    $msg_sucesso = [];
                } 

                $retorno_status_mov = $objContratoStatusMovimento->add($retorno["contrato"], $novo_status_contrato["contrato_status"]);
                if (isset($retorno_status_mov["erro"]) && strlen($retorno_status_mov["msn"]) > 0) {
                    $msg_erro["msg"][] = $retorno_status_mov["msn"];
		    $msg_sucesso = [];
                }

                //abre os entrega tec 
                if (count($msg_erro['msg']) == 0 && $novo_status_contrato["descricao"] == "Aguardando Treinamento Técnico" && in_array($login_fabrica, [190])) {

                    if (!$objContratoOS->temOs($retorno["contrato"])) {

                        $retorno_abre_os = $objContratoOS->abreOsEntregaTecnicaTreinamento($retorno["contrato"]);
                        if (isset($retorno_abre_os["erro"]) && strlen($retorno_abre_os["msn"]) > 0) {
                            $msg_erro["msg"][] = $retorno_abre_os["msn"];
		    $msg_sucesso = [];
                        }    

                    }
                }
            }
        }
        if (count($msg_erro['msg']) == 0) {
            $resB = pg_query($con,"COMMIT TRANSACTION");
            echo redireciona($_REQUEST["tipo"], $contrato);
        } else {
            $resB = pg_query($con,"ROLLBACK TRANSACTION");
        }
    } 
}


if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
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
            $retorno = array("error" => utf8_encode(traduz("nenhuma.cidade.encontrada.para.o.estado") . ": {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode(traduz("estado.nao.encontrado")));
    }

    exit(json_encode($retorno));
}


if (isset($_GET["ajax_remove_item"]) && $_GET["ajax_remove_item"] == true) {
  
    $contrato_item = $_POST["contrato_item"];
    $result   = $objContrato->deleteItem($contrato_item);
    if ($result) {
        exit(json_encode(["erro" => false, "msg" => "Item removido com sucesso"]));
    }
    exit(json_encode(["erro" => true, "msg" => "Não foi possível remover esse item"]));

}

if ($fabricaFileUploadOS) {
    if (!empty($contrato)) {
        $tempUniqueId = $contrato;
        $anexoNoHash = null;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

if ($areaAdminRepresentante) {
    $desconto_representante = (strlen($desconto_representante) == 0 || $desconto_representante == 0) ? $representante_admin_desconto : $desconto;
}

function geraDataBD($data) {
    list($dia, $mes, $ano) = explode("/", $data);
    return $ano."-".$mes."-".$dia;
}

function geraDataNormal($data) {
    list($ano, $mes, $dia) = explode("-", $data);
    return $dia."/".$mes."/".$ano;
}

function redireciona($tipo, $contrato)
{
    if ($tipo <> "proposta") {
        $tipo = "contrato";
    }
    echo "<meta http-equiv=refresh content=\"0;URL=print_contrato.php?tipo={$tipo}&contrato={$contrato}\">";

}

$layout_menu       = "gerencia";
$admin_privilegios = "gerencia";
$title             = traduz("Cadastro {$label_contrato}");

include 'cabecalho_new.php';

$plugins = array(
   "dataTable",
    "multiselect",
   "datepicker",
   "shadowbox",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet",
   "font_awesome",
   "autocomplete"

);

include("plugin_loader.php");
?>
<script type="text/javascript" src="../externos/institucional/lib/mask/mask.min.js"></script>
<style>
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
    .btn-remove-ajuste{
        float: right;
        margin-top: 23px;
    }
    .btn-small [class^="icon-"], .btn-small [class*=" icon-"] {
        margin-top: 3px;
    }
    .lupa_produto,.lupa_representante,.lupa_cliente{
        cursor: pointer;
    }
    .input_totais{
        height: 32px !important;
        font-size: 16px !important;
    }
    .input_totais_app{
        height: 22px !important;
        line-height: 23px !important;
        font-size: 14px !important;
        padding: 4px 15px !important;
    }
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });
        $("#cliente_cep").mask("99999-999",{placeholder:""});
        $("#cliente_celular").mask("(99) 99999-9999",{placeholder:""});
        $("#cliente_fone").mask("(99) 9999-9999",{placeholder:""});

        $("#valor_mao_obra_fixa").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });
        $(".numeric").numeric();
        $(".precos").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

        $(document).on("click", "span[rel=lupa]", function () {
            $.lupa($(this));
        });

        $(document).on("click", ".lupa_cliente", function () {
            var parametro    = $(this).data('parametro');
            var nome    = $("input[name=cliente_nome]").val();
            var cpf   = $("input[name=cliente_cpf]").val();
        
            if (parametro == "cpf") {
                if (cpf == "") {
                    alert("Digite o CPF/CNPJ");
                    $("input[name=cliente_cpf]").focus();
                    return false;
                } else if (cpf.length < 3) {
                    alert("Digite a ao menos 3 caracteres no CPF/CNPJ");
                    $("input[name=cliente_cpf]").focus();
                    return false;
                }
                var valor = cpf;

            }
            if (parametro == "nome") {
                if (nome == "") {
                    alert("Digite o Nome/Razão Social Cliente");
                    $("input[name=cliente_nome]").focus();
                    return false;
                } else if (nome.length < 3) {
                    alert("Digite a ao menos 3 caracteres no Nome/Razão Social Cliente");
                    $("input[name=cliente_nome]").focus();
                    return false;
                }
                var valor = nome;
            }

            Shadowbox.open({
                content: "cliente_admin_pesquisa_new.php?parametro="+parametro+"&valor="+valor,
                player: "iframe",
                title:  "Busca de Clientes ",
                width:  800,
                height: 500
            });

        });

        $(document).on("click", ".lupa_produto", function () {
            var posicao      = $(this).data('posicao');
            var parametro    = $(this).data('parametro');
            var descricao    = $("input[name=produto_descricao_"+posicao+"]").val();
            var referencia   = $("input[name=produto_referencia_"+posicao+"]").val();
            var contrato_tabela = $("select[name=contrato_tabela] option:selected").val();
            if (contrato_tabela == "") {
                alert("Selecione uma Tabela de Preço");
                return false;
            }
            if (parametro == "referencia") {
                if (referencia == "") {
                    alert("Digite a Referência");
                    $("input[name=produto_referencia_"+posicao+"]").focus();
                    return false;
                } else if (referencia.length < 3) {
                    alert("Digite a ao menos 3 caracteres na Referência");
                    $("input[name=produto_referencia_"+posicao+"]").focus();
                    return false;
                }
                var valor = referencia;

            }
            if (parametro == "descricao") {
                if (descricao == "") {
                    alert("Digite a Referência");
                    $("input[name=produto_descricao_"+posicao+"]").focus();
                    return false;
                } else if (descricao.length < 3) {
                    alert("Digite a ao menos 3 caracteres na Descrição");
                    $("input[name=produto_descricao_"+posicao+"]").focus();
                    return false;
                }
                var valor = descricao;
            }

            Shadowbox.open({
                content: "produto_contrato_lupa.php?contrato_tabela="+contrato_tabela+"&posicao="+posicao+"&parametro="+parametro+"&valor="+valor,
                player: "iframe",
                title:  "Busca de Produtos ",
                width:  800,
                height: 500
            });

        });

        $(document).on("click", ".lupa_representante", function () {
            var parametro    = $(this).data('parametro');
            var nome    = $("input[name=representante_nome]").val();
            var codigo   = $("input[name=representante_codigo]").val();
        
            if (parametro == "codigo") {
                if (codigo == "") {
                    alert("Digite o Código Representante");
                    $("input[name=representante_codigo]").focus();
                    return false;
                } else if (codigo.length < 3) {
                    alert("Digite a ao menos 3 caracteres no Código");
                    $("input[name=representante_codigo]").focus();
                    return false;
                }
                var valor = codigo;

            }
            if (parametro == "nome") {
                if (nome == "") {
                    alert("Digite o Nome/Razão Social Representante");
                    $("input[name=representante_nome]").focus();
                    return false;
                } else if (nome.length < 3) {
                    alert("Digite a ao menos 3 caracteres no Nome/Razão Social Representante");
                    $("input[name=representante_nome]").focus();
                    return false;
                }
                var valor = nome;
            }

            Shadowbox.open({
                content: "representante_pesquisa_new.php?parametro="+parametro+"&valor="+valor,
                player: "iframe",
                title:  "Busca de Representantes ",
                width:  800,
                height: 500
            });

        });

        $(document).on("change", "#desconto_representante, input[id^=preco_]", function () {
            calcula_total_contrato();
        });

        $("#data_vigencia").datepicker({ minDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

        $("#cliente_cep").change(function(){
            busca_cep($(this).val());

        });

  
        $("#cliente_cpf").focus(function(){
           $(this).unmask();
           $(this).mask("99999999999999");
        });
           
        $("#cliente_cpf").blur(function(){
           var el = $(this);
           el.unmask();
           
           if(el.val().length > 11){
               el.mask("99.999.999/9999-99");
           }

           if(el.val().length <= 11){
               el.mask("999.999.999-99");
           }
        });
          

        $(document).on("click", ".btn-remove-ajuste", function(){
            var posicao = $(this).data("posicao");
            $(".box-produto-"+posicao).remove();
        });
      

        $(document).on("click", "input[name=mao_obra_fixa]", function(){
            if ($(this).val() == "sim") {
                $(".mostra_valor_mao_obra_fixa").show();
            } else {
                $(".mostra_valor_mao_obra_fixa").hide();
            }

        });

        $(".btn-add-produto").on("click", function(){
            var posicao = parseInt($("#total_produtos").val())+1;
            var conteudo = '\
            <div class="row-fluid box-produto-'+posicao+'">\
                <div class="span1">\
                    <div class="control-group">\
                        <div class="controls controls-row">\
                            <div class="span12">\
                                <label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>\
                                <button type="button" data-posicao="'+posicao+'" class="btn btn-mini btn-danger btn-remove-ajuste"><i class="icon-white icon-remove"></i></button>\
                            </div>\
                        </div>\
                    </div>\
                </div>\
                <div class="span2">\
                    <div class="control-group">\
                        <label class="control-label" for="produto_referencia">Referencia</label>\
                        <div class="controls controls-row">\
                            <div class="span12">\
                                <div class="input-append">\
                                    <input type="text" id="produto_referencia_'+posicao+'" name="produto_referencia_'+posicao+'" class="span9">\
                                    <span class="add-on lupa_produto" data-posicao="'+posicao+'" data-parametro="referencia"><i class="icon-search"></i></span>\
                                    <input type="hidden" name="lupa_config" tipo="produto" posicao="'+posicao+'" parametro="referencia" />\
                                </div>\
                            </div>\
                        </div>\
                    </div>\
                </div>\
                <div class="span3">\
                    <div class="control-group">\
                        <label class="control-label" for="produto_descricao">Descrição</label>\
                        <div class="controls controls-row">\
                            <div class="span10">\
                                <div class="input-append">\
                                    <input type="text" name="produto_descricao_'+posicao+'" id="produto_descricao_'+posicao+'" class="span12">\
                                    <span class="add-on lupa_produto" data-posicao="'+posicao+'" data-parametro="descricao"><i class="icon-search"></i></span>\
                                    <input type="hidden" name="lupa_config" posicao="'+posicao+'" tipo="produto" parametro="descricao" />\
                                </div>\
                            </div>\
                        </div>\
                    </div>\
                </div>\
                <div class="span2">\
                    <div class="control-group">\
                        <label class="control-label" for="preco">Preço</label>\
                        <div class="controls controls-row">\
                            <div class="span10">\
                                <div class="input-prepend">\
                                    <span class="add-on">R$</span>\
                                    <input type="text" name="preco_'+posicao+'" id="preco_'+posicao+'" class="span12 precos">\
                                </div>\
                            </div>\
                        </div>\
                    </div>\
                </div>\
                <div class="span2">\
                    <div class="control-group">\
                        <label class="control-label" for="horimetro">Horimetro</label>\
                        <div class="controls controls-row">\
                            <div class="span10">\
                                <input type="text" name="horimetro_'+posicao+'" id="horimetro_'+posicao+'" class="span12">\
                            </div>\
                        </div>\
                    </div>\
                </div>\
                <div class="span1">\
                    <div class="control-group">\
                        <label class="control-label" for="preventiva">Preventiva</label>\
                        <div class="controls controls-row">\
                            <div class="span10">\
                                    <input type="checkbox" name="preventiva_'+posicao+'" id="preventiva_'+posicao+'" >\
                            </div>\
                        </div>\
                    </div>\
                </div>\
            </div>';
            $("#mais_produtos").append(conteudo);
            $("#total_produtos").val(posicao);

            
        });
    });

    function delete_item(contrato_item, posicao) {
        if (contrato_item == "") {
            alert("Produto não encontrado");
            return false;
        }
        if (confirm('Deseja remover esse Item?')) {

            $.ajax({
                url: 'cadastro_contrato.php?ajax_remove_item=true',
                type: 'POST',
                dataType: 'JSON',
                data: {contrato_item: contrato_item},
            })
            .done(function(data) {
                
                if (data.erro) {
                    alert(data.msg);
                    return false;
                } else {
                    alert(data.msg);
                    $(".box-produto-"+posicao).remove();
                }
            })
            .fail(function() {
                alert("Não foi possível remover item");
                return false;
            });
        }
        return false;

    }

    function busca_cep(cep, method) {
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
                url: "../ajax_cep.php",
                type: "GET",
                data: { ajax: true, cep: cep, method: method },
                beforeSend: function() {
                    $("#cliente_uf").next("img").remove();
                    $("#cliente_cidade").next("img").remove();
                    $("#cliente_bairro").next("img").remove();
                    $("#cliente_endereco").next("img").remove();
                    $("#cliente_uf").hide().after(img.clone());
                    $("#cliente_cidade").hide().after(img.clone());
                    $("#cliente_bairro").hide().after(img.clone());
                    $("#cliente_endereco").hide().after(img.clone());
                },
                error: function(xhr, status, error) {
                    busca_cep(cep, "database");
                },
                success: function(data) {
                    results = data.split(";");

                    if (results[0] != "ok") {
                        alert(results[0]);
                        $("#cliente_cidade").show().next().remove();
                    } else {
                        $("#cliente_uf").val(results[4]);
                        results[3] = results[3].replace(/[()]/g, '');

                        $("#cliente_cidade").val(retiraAcentos(results[3]).toUpperCase());

                        if (results[2].length > 0) {
                            $("#cliente_bairro").val(results[2]);
                        }

                        if (results[1].length > 0) {
                            $("#cliente_endereco").val(results[1]);
                        }
                    }

                    $("#cliente_uf").show().next().remove();
                    $("#cliente_cidade").show().next().remove();
                    $("#cliente_bairro").show().next().remove();
                    $("#cliente_endereco").show().next().remove();

                    if ($("#cliente_bairro").val().length == 0) {
                        $("#cliente_bairro").focus();
                    } else if ($("#cliente_endereco").val().length == 0) {
                        $("#cliente_endereco").focus();
                    } else if ($("#cliente_numero").val().length == 0) {
                        $("#cliente_numero").focus();
                    }

                    $.ajaxSetup({
                        timeout: 0
                    });
                }
            });
        }
    }

    function retorna_posto(retorno) {
        $("#posto_id").val(retorno.posto);
        $("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
        $("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
    }
    function retiraAcentos(palavra){
        if (!palavra) {
            return "";
        }

        var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
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

    function retorna_cliente(dados){
        $("#cliente").val(dados.cliente_admin);
        $("#cliente_cpf").val(dados.cnpj);
        $("#cliente_nome").val(dados.nome);
        $("#cliente_email").val(dados.email);
        $("#cliente_fone").val(dados.fone);
        $("#cliente_celular").val(dados.celular);
        $("#cliente_cep").val(dados.cep);
        $("#cliente_endereco").val(dados.endereco);
        $("#cliente_numero").val(dados.numero);
        $("#cliente_complemento").val(dados.complemento);
        $("#cliente_bairro").val(dados.bairro);
        $("#cliente_cidade").val(dados.cidade);
        $("#cliente_uf").val(dados.estado);
        $("#cliente_cpf").blur();
        $("#cliente_cep").mask("99999-999",{placeholder:""});
        $("#cliente_celular").mask("(99) 99999-9999",{placeholder:""});

    }

    function retorna_produto(dados){
        $("#produto_referencia_"+dados.posicao).val(dados.referencia);
        $("#produto_descricao_"+dados.posicao).val(dados.descricao);
        $("#preco_"+dados.posicao).val(dados.preco);
        if (dados.bloquea_horimetro == "false") {
            $("#horimetro_"+dados.posicao).attr("disabled", true);
        }
        setTimeout(function(){
            calcula_total_contrato();
        }, 2000);
        
    }

    function retorna_representante(dados){
        $("#representante_codigo").val(dados.codigo);
        $("#representante_nome").val(dados.nome);
        $("#representante").val(dados.representante);

    }

    function calcula_total_contrato() {
        var novo_total_produto = 0;
        var total_produto = 0;
        var desconto      = parseFloat($("#desconto_representante").val());
        $("#mais_produtos  input[id^=preco_]").each(function (index, element) {
            if (!isNaN(parseFloat($(element).val()))) {
                total_produto += parseFloat($(element).val());
            }
        });

        if (desconto > 0 || !isNaN(desconto)) {
            
            novo_total_produto = parseFloat(total_produto)-((desconto*total_produto)/100);
        } else {
            novo_total_produto = parseFloat(total_produto);
        }
            console.log(novo_total_produto)
        
        $("#valor_total_produtos").val(parseFloat(total_produto).toFixed(2));
        $("#valor_contrato_final").val(novo_total_produto.toFixed(2));
    }


</script>
    <?php if (count($msg_sucesso["msg"]) == 0 &&count($msg_erro["msg"]) > 0) {?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
        </div>
    <?php }?>

    <?php if (count($msg_erro["msg"]) == 0 && count($msg_sucesso["msg"]) > 0){?>
        <div class="alert alert-success">
            <h4><?php echo implode("<br />", $msg_sucesso["msg"]);?></h4>
        </div>
    <?php }?>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="contrato" value="<?php echo $contrato;?>">
        <input type="hidden" name="tipo_acao" value="add">
        <input type="hidden" name="proposta" value="<?php echo $proposta;?>">
        <input type="hidden" name="tipo" value="<?php echo $tipo;?>">
        <?php if (!$areaAdminRepresentante) {?>
        <div class='titulo_tabela '>Dados do Representante</div>
        <br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span3">
                <div class='control-group <?=(in_array("representante_codigo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="representante_codigo">Código Representante</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>
                            <div class="input-append">
                                <input type="text" id="representante_codigo" <?php echo $disabled_representante;?> value="<?php echo $representante_codigo;?>" name="representante_codigo" class="span9">
                                <span class="add-on lupa_representante" data-parametro="codigo"><i class="icon-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span7'>
                <div class='control-group <?=(in_array("representante_nome", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='representante_nome'>Nome/Razão Social Representante</label>
                    <div class="controls controls-row">
                        <div class="span11 input-append">
                            <h5 class='asteristico'>*</h5>
                            <input type="text"  name="representante_nome" <?php echo $disabled_representante;?> id="representante_nome" value="<?php echo $representante_nome;?>" class="span12">
                            <span class="add-on lupa_representante" data-parametro="nome"><i class="icon-search"></i></span>
                        </div>
                        <input type="hidden" value="<?php echo $representante;?>" name="representante" id="representante">
                    </div>
                </div>
            </div>
        </div><br>
        <?php } else {?>
            <input type="hidden" value="<?php echo $representante_admin;?>" name="representante" id="representante">
        <?php }?>
        <?php if ($exibe_posto) {?>
        <div class='titulo_tabela '>Dados do Posto Autorizado</div>
        <br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span2">
                <div class='control-group <?=(in_array("posto_codigo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="posto_codigo">Código Posto</label>
                    <div class="controls controls-row">
                        <div class="span12 input-append">
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="posto_codigo" value="<?php echo $posto_codigo;?>" name="posto_codigo" class="span9">
                            <span  class="add-on" rel="lupa"><i class="icon-search"></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto_nome", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='posto_nome'>Nome Posto</label>
                    <div class="controls controls-row">
                        <div class="span11 input-append">
                            <h5 class='asteristico'>*</h5>
                            <input type="text"  name="posto_nome" id="posto_nome" value="<?php echo $posto_nome;?>" class="span12">
                            <span  class="add-on" rel="lupa"><i class="icon-search"></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                        <input type="hidden" value="<?php echo $posto_id;?>" name="posto_id" id="posto_id">
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label' for='posto_nome'>M.O. Fixa?</label>
                    <div class="controls controls-row">
                        <input type="radio" name="mao_obra_fixa" <?php echo ($mao_obra_fixa == "sim") ? "checked" : "";?> value="sim"> Sim 
                        <input type="radio" name="mao_obra_fixa" <?php echo ($mao_obra_fixa == "nao") ? "checked" : "";?>  value="nao"> Não
                    </div>
                </div>
            </div>
            <div class='span2 mostra_valor_mao_obra_fixa' style='display:<?=($mao_obra_fixa == "sim") ? "" : "none"?>'>
                <div class='control-group <?=(in_array("error_valor_mao_obra_fixa", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='valor_mao_obra_fixa'>Valor M.O.</label>
                    <div class="controls controls-row">
                        <div class="span11 input-prepend">
                            <span  class="add-on">R$</span>
                            <input type="text"  name="valor_mao_obra_fixa" id="valor_mao_obra_fixa" value="<?php echo $valor_mao_obra_fixa;?>" class="span12">
                        </div>
                    </div>
                </div>
            </div>
        </div><br>


        <?php }?>
        <div class='titulo_tabela '>Informações <?php echo $label_contrato;?></div>
        <br/>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span2' <?php echo ($exibe_numero_contrato) ? "" : "style='display:none'";?>>
                <div class='control-group <?=(in_array("numero_contrato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Seu Contrato</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $numero_contrato;?>" name="numero_contrato" id="numero_contrato">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2' <?php echo ($exibe_data_vigencia) ? "" : "style='display:none'";?>>
                <div class='control-group <?=(in_array("data_vigencia", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Data Vigência</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo strlen($data_vigencia) > 0 ? $data_vigencia : "";?>" name="data_vigencia" id="data_vigencia">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("genero_contrato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Genêro <?php echo $label_contrato;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select class="span12" name="genero_contrato">
                                <option value="">Selecione...</option>
                                <option value="L" <?php echo ($genero_contrato == "L") ? "selected" : "";?>>Locação</option>
                                <option value="M" <?php echo ($genero_contrato == "M") ? "selected" : "";?>>Manutenção</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("tipo_contrato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Tipo <?php echo $label_contrato;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select class="span12" name="tipo_contrato">
                                <option value="">Selecione...</option>
                                <?php 
                                    foreach ($tipo_contrato_array as $key => $rows) {
                                        $selected =  ($tipo_contrato == $rows["tipo_contrato"]) ? "selected" : "";
                                        echo '<option '.$selected.' value="'.$rows["tipo_contrato"].'">'.$rows["codigo"].' - '.$rows["descricao"].'</option>';
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2' <?php //echo ($exibe_data_vigencia) ? "" : "style='display:none'";?>>
                <div class='control-group <?=(in_array("contrato_tabela", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Tabela de Preço</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select class="span12" name="contrato_tabela">
                                <option value="">Selecione...</option>
                                <?php 
                                    foreach ($contrato_tabela_array as $key => $rows) {
                                        $selected =  ($contrato_tabela == $rows["contrato_tabela"]) ? "selected" : "";
                                        echo '<option '.$selected .' value="'.$rows["contrato_tabela"].'">'.$rows["codigo"].' - '.$rows["descricao"].'</option>';
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span2' <?php //echo ($exibe_data_vigencia) ? "" : "style='display:none'";?>>
                <div class='control-group <?=(in_array("qtde_preventiva", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Qtde Preventivas</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12 numeric" value="<?php echo $qtde_preventiva;?>" name="qtde_preventiva" id="qtde_preventiva">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2' <?php echo ($exibe_dias) ? "" : "style='display:none'";?>>
                <div class='control-group <?=(in_array("dia_preventiva", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Dia da Preventiva</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12 numeric" value="<?php echo $dia_preventiva;?>" maxlength="2" name="dia_preventiva" id="dia_preventiva">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2' <?php // echo ($exibe_data_vigencia) ? "" : "style='display:none'";?>>
                <div class='control-group <?=(in_array("qtde_corretiva", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Qtde Corretivas</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12 numeric" value="<?php echo $qtde_corretiva;?>" name="qtde_corretiva" id="qtde_corretiva">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4' <?php  echo ($exibe_status_contrato) ? "" : "style='display:none'";?>>
                <div class='control-group <?=(in_array("contrato_status", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Status Contrato</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <select class="span12" name="contrato_status">
                                <option value=""><?php echo traduz("Selecione");?>...</option>
                                <?php 
                                    foreach ($array_status_contrato as $key => $rows) {
                                        $selected = ($contrato_status == $rows["contrato_status"]) ? "selected" : "";
                                        echo '<option '.$selected.' value="'.$rows["contrato_status"].'">'.$rows["descricao"].'</option>';
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid' <?php echo ($exibe_descricao) ? "" : "style='display:none'";?>>
            <div class='span1'></div>
            <div class='span10'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Descrição <?php echo $label_contrato;?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <textarea class="span12" name="descricao" id="descricao" rows="10"><?php echo $descricao;?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div><br/>
        <div class='titulo_tabela '>Dados do Cliente</div>
        <br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span4">
                <div class='control-group <?=(in_array("cliente_cpf", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="cliente_cpf">CPF/CNPJ</label>
                    <div class="controls controls-row">
                            <div class="span12 input-append">
                            <h5 class='asteristico'>*</h5>
                                <input type="text" value="<?php echo $cliente_cpf;?>" <?php echo $disabled_cliente;?> id="cliente_cpf" name="cliente_cpf" class="span11">
                                <span class="add-on lupa_cliente"  data-parametro="cpf"><i class="icon-search"></i></span>
                            </div>
                    </div>
                </div>
            </div>
            <div class='span5'>
                <div class='control-group <?=(in_array("cliente_nome", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='cliente_nome'>Nome/Razão Social</label>
                    <div class="controls controls-row">
                        <div class="span12 input-append">
                            <h5 class='asteristico'>*</h5>
                            <input type="text" value="<?php echo $cliente_nome;?>" <?php echo $disabled_cliente;?> name="cliente_nome" id="cliente_nome" class="span12">
                            <span class="add-on lupa_cliente"  data-parametro="nome"><i class="icon-search"></i></span>
                        </div>
                        <input type="hidden" value="<?php echo $cliente;?>" name="cliente" id="cliente">
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span4'>
                <div class='control-group  <?=(in_array("cliente_email", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>E-mail</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $cliente_email;?>" <?php echo $disabled_cliente;?> name="cliente_email" id="cliente_email">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label'>Telefone</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_fone;?>" <?php echo $disabled_cliente;?> name="cliente_fone" id="cliente_fone">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group'>
                    <label class='control-label'>Celular</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_celular;?>" <?php echo $disabled_cliente;?> name="cliente_celular" id="cliente_celular">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>CEP</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_cep;?>" <?php echo $disabled_cliente;?> name="cliente_cep" id="cliente_cep">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span6'>
                <div class='control-group'>
                    <label class='control-label'>Endereço</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_endereco;?>" <?php echo $disabled_cliente;?> name="cliente_endereco" id="cliente_endereco">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>Número</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_numero;?>" <?php echo $disabled_cliente;?> name="cliente_numero" id="cliente_numero">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>Complemento</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_complemento;?>" <?php echo $disabled_cliente;?> name="cliente_complemento" id="cliente_complemento">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>Bairro</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_bairro;?>" <?php echo $disabled_cliente;?> name="cliente_bairro" id="cliente_bairro">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span5'>
                <div class='control-group'>
                    <label class='control-label'>Cidade</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_cidade;?>" <?php echo $disabled_cliente;?> name="cliente_cidade" id="cliente_cidade">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span1'>
                <div class='control-group'>
                    <label class='control-label'>UF</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" class="span12" value="<?php echo $cliente_uf;?>" <?php echo $disabled_cliente;?> name="cliente_uf" id="cliente_uf">
                        </div>
                    </div>
                </div>
            </div>
        </div><br />
        <div class='titulo_tabela '>Informações do Produto/Serviço</div>
        <br/>
        <div id="mais_produtos">
        <?php 
            $dadosItens = $objContrato->getItens($contrato);
            if (count($dadosItens) > 0 && !isset($dadosItens["erro"])) {
                $qtde = count($dadosItens);
            } else {
                $qtde = 5;
            }
            echo '<input type="hidden" name="total_produtos" id="total_produtos" value="'.$qtde.'" />';
            $valor_total_produtos = 0;
            for ($i=0; $i < $qtde; $i++) { 
                if (count($dadosItens) > 0 && !isset($dadosItens["erro"])) {
                    $produto_referencia = $dadosItens[$i]["referencia_produto"];
                    $produto_descricao  = $dadosItens[$i]["nome_produto"];
                    $preco              = $dadosItens[$i]["preco"];
                    $horimetro          = $dadosItens[$i]["horimetro"];
                    $preventiva         = $dadosItens[$i]["preventiva"];
                    $contrato_item      = $dadosItens[$i]["contrato_item"];
                } else {
                    $produto_referencia = $_POST["produto_referencia_".$i];
                    $produto_descricao  = $_POST["produto_descricao_".$i];
                    $preco              = $_POST["preco_".$i];
                    $horimetro          = $_POST["horimetro_".$i];
                    $preventiva         = $_POST["preventiva_".$i];
                    $contrato_item = "";
                }
                $valor_total_produtos += $preco;
        ?>
            
            <div class="row-fluid box-produto-<?php echo $i;?>">
                <div class="span1">
                    <button type="button" <?php echo (strlen($contrato_item) > 0) ? "onclick='delete_item(".$contrato_item.", ".$i.");'" : "";?> data-posicao="<?php echo $i;?>" class="btn btn-mini btn-danger btn-remove-ajuste"><i class="icon-white icon-remove"></i></button>
                </div>
                <div class="span2">
                    <div class='control-group '>
                        <label class="control-label" for="produto_referencia">Referencia</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <div class="input-append">
                                    <input type="text" value="<?php echo $produto_referencia;?>" id="produto_referencia_<?php echo $i;?>" name="produto_referencia_<?php echo $i;?>" class="span9">
                                    <span class="add-on lupa_produto" data-posicao='<?php echo $i;?>' data-parametro="referencia"><i class="icon-search"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span3'>
                    <div class='control-group'>
                        <label class='control-label' for='produto_descricao'>Descrição</label>
                        <div class="controls controls-row">
                            <div class="span10">
                                <div class="input-append">
                                    <input type="text" value="<?php echo $produto_descricao;?>"  name="produto_descricao_<?php echo $i;?>" id="produto_descricao_<?php echo $i;?>" class="span12">
                                    <span class="add-on lupa_produto" data-posicao='<?php echo $i;?>' data-parametro="descricao"><i class="icon-search"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group'>
                        <label class='control-label' for='peca_descricao'>Preço</label>
                        <div class="controls controls-row">
                            <div class="span10">
                                <div class="input-prepend">
                                    <span class="add-on">R$</span>
                                    <input type="text" value="<?php echo $preco;?>" name="preco_<?php echo $i;?>" id="preco_<?php echo $i;?>" class="span12 precos">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'>
                    <div class='control-group'>
                        <label class='control-label' for='peca_descricao'>Horimetro</label>
                        <div class="controls controls-row">
                            <div class="span10">
                                <input type="text" value="<?php echo $horimetro;?>" name="horimetro_<?php echo $i;?>" id="horimetro_<?php echo $i;?>" class="span12">
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span1'>
                    <div class='control-group'>
                        <label class='control-label' for='peca_descricao'>Preventiva</label>
                        <div class="controls controls-row">
                            <div class="span10">
                                    <input type="checkbox" value="t" <?php echo ($preventiva == "t") ? "checked" : "";?> name="preventiva_<?php echo $i;?>" id="preventiva_<?php echo $i;?>" >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        </div><br>
        <button type="button" class="btn btn-primary btn-small btn-add-produto "> Adicionar Produto</button><br><br>
        <div class='titulo_tabela '>Valores do Contrato</div>
        <br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class='span3'>
                <div class='control-group '>
                    <label class='control-label' for='valor_total_produtos'>Valor total de Produtos</label>
                    <div class="controls controls-row">
                        <div class="span9 input-prepend">
                            <span  class="add-on input_totais_app">R$</span>
                            <input type="text" readonly name="valor_total_produtos" id="valor_total_produtos" value="<?php echo $valor_total_produtos;?>" class="span12 precos input_totais">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group '>
                    <label class='control-label' for='desconto_representante'>Desconto</label>
                    <div class="controls controls-row">
                        <div class="span9 input-append">
                            <input type="text"  name="desconto_representante" id="desconto_representante" value="<?php echo $desconto_representante;?>" class="span12 precos input_totais">
                            <input type="hidden"  name="desconto_representante_bd" value="<?php echo $desconto_representante;?>">
                            <span  class="add-on input_totais_app">%</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group '>
                    <label class='control-label' for='valor_contrato_final'>Total Final do Contrato</label>
                    <div class="controls controls-row">
                        <div class="span9 input-prepend">
                            <span  class="add-on input_totais_app">R$</span>
                            <input type="text" readonly name="valor_contrato_final" id="valor_contrato_final" value="<?php echo number_format(($valor_total_produtos-(($desconto_representante*$valor_total_produtos)/100)), 2, '.', '');?>" class="span12 precos input_totais">
                        </div>
                    </div>
                </div>
            </div>
        </div><br>

        <?php 
            if ($fabricaFileUploadOS && strlen($contrato) > 0 && !$proposta) {
                $boxUploader = array(
                    "div_id" => "div_anexos",
                    "prepend" => $anexo_prepend,
                    "context" => "contrato",
                    "unique_id" => $tempUniqueId,
                    "hash_temp" => $anexoNoHash,
                    "reference_id" => $tempUniqueId
                );

                include "box_uploader.php";
            }
        ?>
        <p><br/><br/>
                <button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                <a href="consulta_contrato.php" class="btn">Listagem</a>
            </p><br/>
    </form> <br />
</div> 
<?php include 'rodape.php';?>
