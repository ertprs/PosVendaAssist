<?php 
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
include_once "../class/aws/s3_config.php";

include_once S3CLASS;

$s3 = new AmazonTC('callcenter', (int) $login_fabrica);


use PosvendaRest\Callcenter;

$objCallcenter              = new Callcenter($login_fabrica);
$dadosCallcenter            = $objCallcenter->buscaAtendimentoById();
$interacoesCallcenter       = $objCallcenter->buscaInteracaoAtendimento();
$origensCallcenter          = $objCallcenter->buscaOrigem();
$classificacaoCallcenter    = $objCallcenter->buscaClassicacao();
$providenciaCallcenter      = $objCallcenter->buscaProvidencia();

$layout_menu = "callcenter";

$title = traduz("Atendimento Callcenter");

include 'cabecalho_new.php';

$plugins = array(
    "bootstrap3",
    "shadowbox",
    "dataTable",
    "multiselect",
    "datepicker",
    "maskedinput",
    "alphanumeric",
    "ajaxform",
    "fancyzoom",
    "price_format",
    "tooltip",
    "select2",
    "leaflet",
    "ckeditor",
    "font_awesome",
    "autocomplete"
);

include("plugin_loader.php");

    
    $regras = [
        "consumidor" => [
            "nome" => [
                "obrigatorio" => true,
                "rule" => "empty"
            ],
            "cpf_cnpj" => [
                "obrigatorio" => false,
                "rule" => "validaCpfCnpj",
            ],
            "email" => [
                "obrigatorio" => false,
                "rule" => "validaEmail",
            ]
        ],
        "informacao" => [
            "tipo" => [
                "obrigatorio" => true,
                "rule" => "empty",
            ],
            "classificacao" => [
                "obrigatorio" => false,
                "rule" => "empty",
            ]
        ],
        "produto" => [
            "referencia" => [
                "obrigatorio" => true,
                "rule" => "empty",
            ],
            "descricao" => [
                "obrigatorio" => true,
                "rule" => "empty",
            ]
        ]
    ];

    $regrasJson = json_encode($regras);
if ($_POST) {
    echo "<pre>".print_r($_POST,1)."</pre>";exit;
}


?>
</div>
<link rel="stylesheet" href="callcenter/css/callcenter.css?v=<?php echo date("YmdHis");?>" />

<div class="container-fluid" style="background: #f5f5f5 !important;padding-top: 10px;">

<div class="pull-right" style="color: #a94442;padding-bottom: 10px;"> Campos obrigatórios</div>

<br>

<div class="top_painel2 mostra_protocolo" style="display: none;">
    <div class="row" style="line-height: 34px;">
        <div class="col-md-4">
            <b>Cadastro de Atendimento</b> 
        </div>
        <div class="col-md-4 tac">
            <b>Aberto por:</b> flaviozequin - flavio zequin <b style="margin-left: 20px;">Em:</b> 17/01/2020 09:53
        </div>
        <div class="col-md-4 tar">
            <b>Nº Protocolo </b><span class="label label-primary n_atendimento"> </span>
        </div>
    </div>
</div> 
<div class="top_painel2 oculta_protocolo">
    <div class="row" style="line-height: 34px;">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <b class="titulo-box">Cadastro de Atendimento</b> 
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6 tar a_atendimento">
            <button class="btn btn-xs btn-info" type="button" onclick="geraProtocolo();">GERAR PROTOCOLO</button>
        </div>
    </div>
</div><br>
<div class="alert alert-warning tac">
    <p> APRESENTAÇÃO</p>

        <?php
            $sql = "SELECT nome from tbl_fabrica where fabrica = $login_fabrica";
            $res = pg_query($con, $sql);
            $nome_da_fabrica = pg_fetch_result($res,0,0);
            $sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='1' AND fabrica = $login_fabrica";
            $pe  = pg_query($con, $sql);

            if (pg_num_rows($pe) > 0) {
                echo pg_fetch_result($pe, 0, 0);
            } else {
              
                 echo $nome_da_fabrica;
             
        ?>, 
        <?php echo ucfirst((!empty($login_nome_completo)) ? $login_nome_completo : $login_login);?>, 
        <?php echo saudacao();?>.<BR> O Sr.(a) já fez algum contato com a 
        <?php echo $nome_da_fabrica ;?> 
        ?
        <?php }?>

</div>
    <?php 
        include("callcenter/componentes/padrao/box_pesquisa.php");
        include("callcenter/componentes/padrao/box_consumidor.php");
        include("callcenter/componentes/padrao/box_info.php");
        include("callcenter/componentes/padrao/box_produto.php");
        //include("callcenter/componentes/padrao/box_mapa_rede.php");
        include("callcenter/componentes/padrao/box_revenda.php");
        include("callcenter/componentes/padrao/box_reclamacao.php");
        include("callcenter/componentes/padrao/box_anexo.php");
        echo '
        <div class="container_bc tac">
            <button type="submit" class="btn btn-success btn-lg">Gravar</button>
        </div>';
        include("callcenter/componentes/padrao/box_interacao.php");
    ?>
    
</div>
</form>

<script>
    var obrigatorios = JSON.parse('<?= $regrasJson ?>');
</script>
<script type="text/javascript" src="callcenter/js/padrao.js?v=<?php echo date('YmdHis');?>"></script>