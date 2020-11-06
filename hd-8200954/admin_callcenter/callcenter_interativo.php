<?php
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include '../class/communicator.class.php';


$layout_menu = "callcenter";
$title = "CADASTRO DE ATENDIMENTO";

unset($msg_erro);
$msg_erro = array();

include "cabecalho.php";
$plugins = array(
            "autocomplete",
            "tooltip",
            "shadowbox"
        );
include "plugin_loader.php";

$acao    = trim($_REQUEST["acao"]);
$btnacao = trim($_REQUEST["btn_acao"]);

if($btnacao == "gravar"){
    $consumidor_nome    = $_POST['consumidor_nome'];
    $consumidor_fone1   = $_POST['consumidor_fone1'];
    $consumidor_fone2   = $_POST['consumidor_fone2'];
    $produto            = $_POST['produto'];
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $observacao         = $_POST['observacao'];
    $atendente_trans    = $_POST['transferir'];

    if($login_fabrica == 122){
        if(strlen(trim($_FILES['anexo']['name']))==0){
            $msg_erro["msg"]["obg"] = "Anexo obrigatório";
            $msg_erro["campos"][]   = "anexo";
        }
    }

    if(strlen($consumidor_nome) > 0){
        $aux_consumidor_nome = "'".strtoupper($consumidor_nome)."'";
    }else{
        $msg_erro["msg"]["obg"] = "Preencha o nome do consumidor";
        $msg_erro["campos"][]   = "consumidor_nome";
    }

    if(strlen($consumidor_fone1) == 0 && strlen($consumidor_fone2) == 0){
        $msg_erro["msg"]["obg"] = "Preencha pelo menos um número de telefone";
        $msg_erro["campos"][]   = "consumidor_fone1";
    }else{
        $aux_consumidor_fone1 = "'".$consumidor_fone1."'";
        $aux_consumidor_fone2 = "'".$consumidor_fone2."'";
    }

    if(strlen($produto) == 0){
        $msg_erro["msg"]["obg"] = "Preencha o produto relativo ao chamado";
        $msg_erro["campos"][]   = "produto_referencia";
    }

    if(strlen($atendente_trans) == 0){
        $msg_erro["msg"]["obg"] = "Preencha o atendente que seguirá com o chamado";
        $msg_erro["campos"][]   = "transferir";
    }else{
        $aux = explode("|",$atendente_trans);
        $atendente_admin = $aux[0];
        $atendente_email = $aux[1];
    }

    if(strlen($observacao) > 0){
        $aux_obs = "'".$observacao."'";
        $aux_obs = str_replace("'",'', $aux_obs);
        $aux_obs = str_replace('"','', $aux_obs);
        $aux_obs = str_replace('\\','',$aux_obs);
        $aux_obs = "'$aux_obs'";
    }else{
        $msg_erro["msg"]["obg"] = "Preencha o assunto do chamado";
        $msg_erro["campos"][]   = "observacao";
    }

    if (count($msg_erro["msg"]) == 0) {
        $res = pg_query ($con,"BEGIN TRANSACTION");

        $sql = "INSERT INTO tbl_hd_chamado(
                    admin               ,
                    atendente           ,
                    status              ,
                    fabrica             ,
                    fabrica_responsavel ,
                    titulo,
                    categoria
                ) VALUES (
                    $login_admin        ,
                    $atendente_admin    ,
                    'aberto'            ,
                    $login_fabrica      ,
                    $login_fabrica      ,
                    'Pré-atendimento $login_fabrica_nome',
                    'reclamacao_produto'
                ) RETURNING hd_chamado
        ";
        $res = pg_query($con,$sql);
        $hd_chamado = pg_result($res,0,0);

        if(!pg_last_error()){
            $sql2 = "INSERT INTO tbl_hd_chamado_extra(
                        hd_chamado              ,
                        produto                 ,
                        reclamado               ,
                        nome                    ,
                        fone                    ,
                        celular
                    ) VALUES (
                        $hd_chamado             ,
                        $produto                ,
                        $aux_obs                ,
                        $aux_consumidor_nome    ,
                        $aux_consumidor_fone1   ,
                        $aux_consumidor_fone2
                    )
            ";
            $res2 = pg_query($con,$sql2);

        }else{
            $msg_erro["msg"][] = "Problemas na gravação";
        }
    }

    if ($_FILES['anexo'] and empty($msg_erro["msg"])) {
	    require __DIR__ . '/../plugins/fileuploader/TdocsMirror.php';

		$tdocsMirror = new TdocsMirror();

        $types      = array('png', 'jpg', 'jpeg', 'bmp', 'pdf','doc','txt');
        $i          = 1; //apenas um anexo
        $file       = $_FILES[key($_FILES)];
        $type  = trim(strtolower(preg_replace('/.+\./', '', $file['name'])));

        if (count($_FILES) > 0) {
            if ($file['size'] <= 2097152) {

                if (strlen($file['tmp_name']) > 0 && $file['size'] > 0) {
                    if (!in_array($type, $types)) {
                       $retorno = array('erro' => utf8_encode('Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf'));
                    } else {
                        
						try {
							$uploadTdocs = $tdocsMirror->post($_FILES['anexo']['tmp_name']); 
						} catch (\Exception $e) {
							$uploadTdocs = null;
							$retorno = array('error' => $e->getMessage());
						}

						if(!is_array($uploadTdocs)) {
							$uploadTdocs = json_decode($uploadTdocs, true);
						}

						if (!empty($uploadTdocs) and is_array($uploadTdocs)) {
							$key = key($uploadTdocs[0]);
							$tdocs_id = $uploadTdocs[0][$key]['unique_id'];

							$obs = json_encode(
								array(
									array(
										"acao" => "anexar",
										"filename" => $hd_chamado . '.' . $type,
										"filesize"=> $_FILES['anexo'][size],
										"data" => date("Y-m-d\TH:i:s"),
										"fabrica" => $login_fabrica,
										"usuario" => array("admin" => $login_admin),
										"descricao" => "",
										"page" => "admin_callcenter/callcenter_interativo.php",
										"source" => "",
										"typeId" => ""
									)
								)
							);

							$ins_tdocs = "INSERT INTO tbl_tdocs (
								tdocs_id, fabrica, contexto, situacao, obs, referencia, referencia_id
							) VALUES (
								'$tdocs_id', $login_fabrica, 'callcenter', 'ativo', '$obs', 'callcenter', $hd_chamado
							)";
							$res_tdocs = pg_query($con, $ins_tdocs);
						}
                    }
                } else {
                    $retorno =  array('erro' => 'Erro ao fazer o upload do arquivo');
                }
                
            }else {
                $retorno = array('erro' => utf8_encode('O arquivo deve ter no máximo 2Mb'));
            }
        }
    }

    if (count($msg_erro["msg"]) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");

        $sqlMailAdmin = "
            SELECT  email
            FROM    tbl_admin
            WHERE   admin = $login_admin
        ";
        $resMailAdmin = pg_query($con,$sqlMailAdmin);
        $mailAdmin = pg_fetch_result($resMailAdmin,0,email);


        $msg = "Atendimento: ".$hd_chamado." - Gravado com Sucesso";

        $mailer = new TcComm('smtp@posvenda');

        $assunto = "Atendimento $login_fabrica_nome - $hd_chamado enviado";
        $mensagem = "Chamado: $hd_chamado\n";
        $mensagem .= "Atendente: $login_nome\n";
        $mensagem .= "Mensagem: $aux_obs";

        if ($login_fabrica == 122) {
            $emails = array(
                "gustavo.xavier@telecontrol.com.br",
                "rafael.souza@telecontrol.com.br",
            );

            foreach ($emails as $email) {
                $mailer->sendMail($email,$assunto,$mensagem,$mailAdmin);                
            }
        } else {
            $mailer->sendMail($atendente_email,$assunto,$mensagem,$mailAdmin);
        }

        unset($_POST);
    }else{
        $consumidor_nome    = $_POST['consumidor_nome'];
        $consumidor_fone1   = $_POST['consumidor_fone1'];
        $consumidor_fone2   = $_POST['consumidor_fone2'];
        $produto            = $_POST['produto'];
        $produto_referencia = $_POST['produto_referencia'];
        $produto_descricao  = $_POST['produto_descricao'];
        $observacao         = $_POST['observacao'];
        $atendente_trans    = $_POST['transferir'];

        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}

if(strlen($_GET['hd_chamado']) > 0){
    $hd_chamado = $_GET['hd_chamado'];

    $sql = "SELECT  tbl_hd_chamado.hd_chamado,
                    (tbl_hd_chamado.atendente||'|'||tbl_admin.email) AS transferir,
                    tbl_hd_chamado_extra.nome,
                    tbl_hd_chamado_extra.fone,
                    tbl_hd_chamado_extra.celular,
                    tbl_produto.produto   ,
                    tbl_produto.referencia   ,
                    tbl_produto.descricao   ,
                    tbl_hd_chamado.status
            FROM    tbl_hd_chamado
            JOIN    tbl_hd_chamado_extra    USING (hd_chamado)
            JOIN    tbl_produto             USING (produto)
            JOIN    tbl_admin               ON tbl_admin.admin = tbl_hd_chamado.atendente
            WHERE   tbl_hd_chamado.fabrica = $login_fabrica
            AND     tbl_hd_chamado.hd_chamado = $hd_chamado
    ";

    $res = pg_query($con,$sql);

    $_RESULT['hd_chamado'] = pg_fetch_result($res,0,hd_chamado);
    $_RESULT['produto'] = pg_fetch_result($res,0,produto);
    $_RESULT['consumidor_nome'] = pg_fetch_result($res,0,nome);
    $_RESULT['consumidor_fone1'] = pg_fetch_result($res,0,fone);
    $_RESULT['consumidor_fone2'] = pg_fetch_result($res,0,celular);
    $_RESULT['referencia'] = pg_fetch_result($res,0,referencia);
    $_RESULT['descricao'] = pg_fetch_result($res,0,descricao);
    $_RESULT['transferir'] = pg_fetch_result($res,0,transferir);
}

$hiddens = array(
    "hd_chamado",
    "produto"
);

$inputs = array(
    "consumidor_nome" => array(
        "id" => "consumidor_nome",
        "type" => "input/text",
        "label" => "Nome do Cliente",
        "span" => 8,
        "maxlength" => 80,
        "required" => true
    ),

    "consumidor_fone1" => array(
        "id" => "consumidor_fone1",
        "type" => "input/text",
        "label" => "Telefone 1 (Residencial)",
        "span" => 4,
        "width" => 15,
        "maxlength" => 12
    ),

    "consumidor_fone2" => array(
        "id" => "consumidor_fone2",
        "type" => "input/text",
        "label" => "Telefone 2 (Celular)",
        "span" => 4,
        "width" => 15,
        "maxlength" => 13
    ),

    "referencia" => array(
        "id" => "produto_referencia",
        "type" => "input/text",
        "label" => "Referência",
        "span" => 4,
        "width" =>6,
        "maxlength" => 20,
        "lupa" => array(
            "name" => "lupa",
            "tipo" => "produto",
            "parametro" => "referencia",
            "extra" => array(
                "produtoId" => "true"
            )
        ),
        "required" => true
    ),

    "descricao" => array(
        "id" => "produto_descricao",
        "type" => "input/text",
        "label" => "Descrição",
        "span" => 4,
        "maxlength" => 80,
        "lupa" => array(
            "name" => "lupa",
            "tipo" => "produto",
            "parametro" => "descricao",
            "extra" => array(
                "produtoId" => "true"
            )
        ),
        "required" => true
    ),

);
$inputs["transferir"] = array(
        "type" => "select",
        "label" => "Transferir ao atendente",
        "span" => 4,
        "width" => 6,
        "required" => true,
        "options" => array()
    );

$inputs["anexo"] = array(
        "type" => "input/file",
        "label" => "Anexo",
        "span" => 4,
        "width" => 12,
        "required" => false        
    );

if ($login_fabrica == 122) {
  $inputs["anexo"]["required"] = true;
}

$sql = "SELECT  admin,
                nome_completo,
                email
        FROM    tbl_admin
        WHERE   fabrica = $login_fabrica
        AND     responsabilidade='recebe_atendimento';";
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
    for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
        $aux_admin = trim(pg_fetch_result($res,$x,admin))."|".trim(pg_fetch_result($res,$x,email));
        $aux_nome  = trim(pg_fetch_result($res,$x,nome_completo));
        $inputs["transferir"]["options"][$aux_admin] = $aux_nome;
    }
}

if(strlen($hd_chamado) == 0){
$inputs["observacao"] = array(
        "type" => "textarea",
        "label" => "Assunto",
        "span" => 8,
        "cols" => 190,
        "rows" => 3,
        "required" => true
    );
}
?>

<script type="text/javascript">

$(function (){
    Shadowbox.init();

    /*
	* Pinsard - 20/02-2015 - chamado 2209620
     * 1: lupa() está definido como plugin do jQuery;
     * 2: $('span[rel="lupa"]) não funciona no IE8,
     *    por isso, o each a partir do seletor mais genérico;
     * 3: Movi a linha marcada como INCOMPATÍVEL COM IE8 para
     *    dentro do each por causa do mesmo problem de seletor;
     */

    var o = $('.add-on');
    $.each( o, function() {
        if( $(this).attr('rel') === 'lupa' ) {
            <? if($hd_chamado != ""){ ?>
                $(this).css("display","none");
            <? } else { ?>
                $(this).click( function() {
                    $.lupa( $(this), ["produtoId", "posicao", "voltagemForm"] );
                });
            <? } ?>
        }
    });

<?
    if($hd_chamado != ""){
?>
        $("input").prop("disabled","disabled");
        $("select").prop("disabled","disabled");
        $("button").css("display","none");
        // $("span[rel=lupa]").css("display","none");  /* INCOMPATÍVEL COM IE8 */
<?
    }
?>
});

function retorna_produto(json){
    $("#produto").val(json.produto);
    $("#produto_referencia").val(json.referencia);
    $("#produto_descricao").val(json.descricao);
}

</script>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<?
if ((count($msg_erro["msg"]) > 0) ) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<? if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) { ?>
    <div class="alert alert-success">
        <h4><? echo $msg; ?></h4>
    </div>
<? } ?>
<form name="frm_callcenter" method="post" action="<? $PHP_SELF ?>" <?php echo $onsubmit;?> class='form-search form-inline tc_formulario' enctype="multipart/form-data" >
    <div class="titulo_tabela">Cadastro</div>
<?
    echo montaForm($inputs, $hiddens);
    $onclick = "onclick=\"if (document.frm_callcenter.btn_acao.value == '' ) {  submitForm($(this).parents('form'),'gravar'); } else { alert ('Aguarde submissão'); } return false;\" ";
?>
    <input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
    <div class="row-fluid">
        <!-- margem -->
        <div class="span4"></div>

        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
                    <button type="button" class="btn" value="Gravar" alt="Gravar formulário" <?php echo $onclick;?> > Gravar</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?
include "../admin/rodape.php";
?>
