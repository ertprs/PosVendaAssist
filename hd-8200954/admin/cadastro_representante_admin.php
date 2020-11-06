<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';
include_once __DIR__ . '/../class/AuditorLog.php';


if (strlen($_POST["representante"]) > 0) $representante  = trim($_POST["representante"]);
if (strlen($_GET["representante"]) > 0) $representante   = trim($_GET["representante"]);

$btn_acao = $_POST['btn_acao'];

$url_redir         = "<meta http-equiv=refresh content=\"2;URL=cadastro_representante_admin.php?listar=todos\">";

if ($btn_acao == 'gravar') {
    unset($_GET["listar"]);
    $msg_erro = array();
    $codigo      = trim($_POST['codigo']);
    $nome        = trim($_POST['razao_social']);
    $cnpj        = preg_replace('/\D/', '', trim($_POST['cnpj']));
    $ie          = preg_replace('/\D/', '', trim($_POST['ie']));
    $endereco    = trim($_POST['endereco']);
    $bairro      = substr(trim($_POST['bairro']),0, 28);
    $cidade      = trim($_POST['cidade']);
    $estado      = trim($_POST['estado']);
    $cep         = preg_replace('/\D/', '', trim($_POST['cep']));
    $fone        = trim($_POST['fone']);
    $contato     = trim($_POST['contato']);
    $fax         = trim($_POST['celular']);
    $fax     	 = trim($_POST['celular']);
    $login_representante_admin         = trim($_POST['login_representante_admin']);
    $senha_representante_admin         = trim($_POST['senha_representante_admin']);
    $representante = trim($_POST['representante']);
    $desconto = (strlen($_POST['desconto']) == 0) ? 0.00 : $_POST['desconto'];

    $numero      = trim($_POST['numero']);
    $complemento = trim($_POST['complemento']);

    if (strlen($nome)==0 && empty($representante)) {
    	$msg_erro['msg'][]      = traduz('Por Favor digite o RAZÃO SOCIA');
        $msg_erro['campos'][]	= 'razao_social';
    }

    if ((strlen($cnpj) == 0 || !valida_cpf_cnpj($cnpj)) && empty($representante)) {
        $msg_erro['msg'][] 		= traduz('Por Favor digite um CNPJ válido');
        $msg_erro['campos'][]	= 'cnpj';
    }

    if (strlen($fone) == 0) {
    	$msg_erro['msg'][]      = traduz('Por Favor digite o TELEFONE ');
        $msg_erro['campos'][]	= 'fone';

    }
    if (strlen($endereco)==0) {
    	$msg_erro['msg'][]      = traduz('Por Favor digite o ENDEREÇO');
        $msg_erro['campos'][]	= 'endereco';
    }

    if (strlen($numero)==0) {
     	$msg_erro['msg'][]      = traduz('Por Favor digite o NÚMERO');
        $msg_erro['campos'][]	= 'numero';
   }

    if (strlen($bairro)==0) {
    	$msg_erro['msg'][]      = traduz('Por Favor digite o BAIRRO');
        $msg_erro['campos'][]	= 'bairro';
    }

    if (strlen($cep) != 8) {
    	$msg_erro['msg'][]      = traduz('Por Favor digite o CEP');
        $msg_erro['campos'][]	= 'cep';
    }

    if (strlen($cidade)==0) {
    	$msg_erro['msg'][]      = traduz('Por Favor digite o CIDADE');
        $msg_erro['campos'][]	= 'cidade';
    }

    if (strlen($estado)==0) {
    	$msg_erro['msg'][]      = traduz('Por Favor digite o ESTADO');
        $msg_erro['campos'][]	= 'estado';
    }


    if (strlen($login_representante_admin)==0) {
        $msg_erro['msg'][]      = traduz('Por Favor digite o LOGIN');
        $msg_erro['campos'][]   = 'login_representante_admin';
    }

        
    if (strlen($senha_representante_admin)==0) {
        $msg_erro['msg'][]      = traduz('Por Favor digite o SENHA');
        $msg_erro['campos'][]   = 'senha_representante_admin';
    }

          
    if (strlen($email)==0) {
        $msg_erro['msg'][]      = traduz('Por Favor digite o E-MAIL');
        $msg_erro['campos'][]   = 'email';
    }

    
    if (count($msg_erro["msg"]) == 0) {
        //$resB = pg_query($con,"BEGIN TRANSACTION");

        if (strlen($representante) > 0) {
            $sql = "UPDATE tbl_representante
                       SET codigo   = '$codigo',
                           ie       = '$ie',
                           email       = '$email',
                           endereco = '$endereco',
                           bairro   = '$bairro',
                           cidade   = '$cidade',
                           estado   = '$estado',
                           cep      = '$cep',
                           fone     = '$fone',
                           desconto     = '$desconto',
                           numero  = '$numero',
                           complemento  = '$complemento',
                           contato  = '$contato',
                           fabrica  = '$login_fabrica',
                           fax      = '$fax'
                     WHERE representante = {$representante} 
                       AND fabrica  = {$login_fabrica}";

        } else {

            $sql = "INSERT INTO tbl_representante (
						codigo,
						email,
                        numero,
						complemento,
						nome,
						cnpj,
						ie,
						endereco,
						bairro,
						cidade,
						estado,
						cep,
						fone,
                        contato,
						desconto,
						fax,
						fabrica
                    ) VALUES (
                        '$codigo',
                        '$email',
                        '$numero',
                        '$complemento',
                        '$razao_social',
                        '$cnpj',
                        '$ie',
                        '$endereco',
                        '$bairro',
                        '$cidade',
                        '$estado',
                        '$cep',
                        '$fone',
                        '$contato',
                        '$desconto',
                        '$fax',
                        $login_fabrica
            		) RETURNING representante";
        }

        $res = pg_query($con,$sql);

        if(pg_last_error()) {
            $msg_erro['msg'][] = traduz("Erro ao gravar representante #1 <br>".pg_last_error());
        } else {

            if (strlen($representante) == 0) {
                $representante = pg_fetch_result($res, 0, 'representante');
            }

            $sqlAdm = "SELECT admin FROM tbl_admin WHERE representante_admin = {$representante} AND fabrica = {$login_fabrica}";
            $resAdm = pg_query($con, $sqlAdm);

            if(pg_num_rows($resAdm) == 0){


                $sqlAdm2 = "SELECT admin FROM tbl_admin WHERE login = '$login_representante_admin'  AND fabrica = {$login_fabrica}";
                $resAdm2 = pg_query($con, $sqlAdm2);

                if(pg_num_rows($resAdm2) > 0){
                    $msg_erro['msg'][] = traduz("Já existe um login cadatrado com esse nome #1");
                }

                if (count($msg_erro['msg']) == 0) {

                    $auditorLog = new AuditorLog('insert');
                    $acao = "insert";
                    $sqlAdm3 = "INSERT INTO tbl_admin 
                                    (
                                        login, 
                                        senha,
                                        nome_completo, 
                                        fone,
                                        email,
                                        representante_admin,
                                        fabrica,
                                        ativo,
                                        cliente_admin_master,
                                        privilegios
                                    ) VALUES 
                                    (
                                        '$login_representante_admin',
                                        '$senha_representante_admin',
                                        '$nome',
                                        '$fone',
                                        '$email',
                                        $representante,
                                        $login_fabrica,
                                        't',
                                        't',
                                        '*'
                                    )"; 
                    $resAdm3 = pg_query($con, $sqlAdm3);
                    if (pg_last_error()) {
                        $msg_erro['msg'][] = traduz("Erro ao gravar representante #2" .pg_last_error());
                    } else {

                        $auditorLog->retornaDadosSelect()->enviarLog($acao, 'tbl_admin', trim($login_fabrica.'*'.$representante));
                        $msg = traduz("Representante gravado com sucesso");
                    }
                }

            }else{
                if (count($msg_erro['msg']) == 0) {
                    $auditorLog = new AuditorLog;
                    $auditorLog->retornaDadosSelect("SELECT * FROM tbl_admin WHERE representante_admin =".$representante." AND fabrica = {$login_fabrica}");
                    $acao = "update";
                    $xadmin = pg_fetch_result($resAdm, 0, 'admin');
                    $sqlAdm4 = "SELECT admin FROM tbl_admin WHERE admin <> $xadmin AND login = '$login_representante_admin'  AND fabrica = {$login_fabrica}";
                    $resAdm4 = pg_query($con, $sqlAdm4);

                    if(pg_num_rows($resAdm4) > 0){
                        $msg_erro['msg'][] = traduz("Já existe um login cadatrado com esse nome #2");
                    }

                    if (count($msg_erro['msg']) == 0) {

                        $sqlAdm5 = "UPDATE tbl_admin SET 
                                        login = '$login_representante_admin',
                                        senha = '$senha_representante_admin',
                                        nome_completo = '$nome',
                                        fone = '$fone',
                                        email = '$email',
                                        ativo = 't',
                                        representante_admin_master = 't',
                                        privilegios = '*' 
                                    WHERE representante_admin = {$representante} 
                                        AND fabrica = {$login_fabrica}";

                        $resAdm5 = pg_query($con, $sqlAdm5);
                        if (pg_last_error()) {
                            $msg_erro['msg'][] = traduz("Erro ao gravar representante #3".pg_last_error());
                        } else {

                            $auditorLog->retornaDadosSelect()->enviarLog($acao, 'tbl_admin', trim($login_fabrica.'*'.$representante));
                            $msg = traduz("Representante gravado com sucesso");
                            
                        }
                    }
                }
            }

        }

        if (count($msg_erro['msg']) == 0) {
            //$resB = pg_query($con,"COMMIT TRANSACTION");
            echo $url_redir;
        } else {
            //$resB = pg_query($con,"ROLLBACK TRANSACTION");
        }
    } 
}

if (strlen($representante) > 0 and count($msg_erro["msg"]) == 0 ) {
    $sql = "SELECT *,tbl_admin.login AS login_representante_admin, tbl_admin.senha AS senha_representante_admin
              FROM tbl_representante
              LEFT JOIN tbl_admin ON tbl_representante.representante  = tbl_admin.representante_admin AND tbl_admin.fabrica=$login_fabrica
             WHERE tbl_representante.representante = $representante
               AND tbl_representante.fabrica= $login_fabrica";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $nome        = trim(pg_fetch_result($res,0,'nome'));
        $cnpj        = mascaraCPFCNPJ(trim(pg_fetch_result($res,0,'cnpj')));
        $ie          = trim(pg_fetch_result($res,0,'ie'));
        $endereco    = trim(pg_fetch_result($res,0,'endereco'));
        $numero      = trim(pg_fetch_result($res,0,'numero'));
        $complemento = trim(pg_fetch_result($res,0,'complemento'));
        $bairro      = trim(pg_fetch_result($res,0,'bairro'));
        $cep         = trim(pg_fetch_result($res,0,'cep'));
        $cidade      = trim(pg_fetch_result($res,0,'cidade'));
        $estado      = trim(pg_fetch_result($res,0,'estado'));
        $email       = trim(pg_fetch_result($res,0,'email'));
        $fax         = trim(pg_fetch_result($res,0,'fax'));
        $fone        = trim(pg_fetch_result($res,0,'fone'));
        $contato     = trim(pg_fetch_result($res,0,'contato'));
        $codigo      = trim(pg_fetch_result($res,0,'codigo'));
        $email      = trim(pg_fetch_result($res,0,'email'));
        $desconto      = trim(pg_fetch_result($res,0,'desconto'));
        $login_representante_admin      = trim(pg_fetch_result($res,0,'login_representante_admin'));
        $senha_representante_admin      = trim(pg_fetch_result($res,0,'senha_representante_admin'));

    }
}
function valida_cpf_cnpj($cpf) {
    global $con;

    $cpf = preg_replace("/\D/", "", $cpf);

    if (strlen($cpf) > 0) {
        $sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            return false;
        }else{
            return true;
        }
    }
}

function mascaraCPFCNPJ($documento) {
    if (strlen($documento) == 11) {
        $cpf_cnpj = substr($documento,0,3) .".". substr($documento,3,3) .".". substr($documento,6,3) ."-". substr($documento,9,2);
    } elseif (strlen($documento) == 14) {
        $cpf_cnpj = substr($documento,0,2) .".". substr($documento,2,3) .".". substr($documento,5,3) ."/". substr($documento,8,4) ."-". substr($documento,12,2);
    } else {
        $cpf_cnpj = $documento;
    }
    return  $cpf_cnpj;
}



$title     = traduz("Cadastro de Representante Admin");
$cabecalho = traduz("Cadastro de Representante Admin");

if (isset($_GET['representante'])) {
    $cabecalho = traduz('Alteração de Representantes');
}

$layout_menu = "cadastro";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric",
    "price_format",
    "font_awesome"
);

include("plugin_loader.php");

?>

<style>

	.add-on {
		cursor: pointer;
	}
	h2.titulo_coluna{
		font-size: 18px;
		padding-bottom: 5px;
	}
</style>
<script type='text/javascript'>
    $(function() {
        Shadowbox.init();
		$.dataTableLoad({
	        table : "#listagem"
	    });
        $("#fone").mask("(99)9999-9999");
        $("#celular").mask("(99)99999-9999");
	    $("#cnpj").focus(function(){
	    //   $(this).unmask();
	       $(this).mask("99999999999999");
	    });
	       
	   $("#cnpj").blur(function(){
	       var el = $(this);
	      // el.unmask();
	       
	       if(el.val().length > 11){
		   el.mask("99.999.999/9999-99");
	       }

	       if(el.val().length <= 11){
		   el.mask("999.999.999-99");
	       }
	   });



        $("#cep").mask("99.999-999");
        $(".precos").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });
        $("#lupa-nome").click(function() {
            fnc_representante_pesquisa(document.frm_representante.razao_social,document.frm_representante.cnpj,'nome');
        });

        $("#lupa-cnpj").click(function() {
            fnc_representante_pesquisa(document.frm_representante.razao_social,document.frm_representante.cnpj,'cnpj');
        });

        $("#cep").blur(function(){
            cep = $("#cep").val();
            busca_cep(cep, '', 'webservice');
        });

    });

    function retiraAcentos(palavra){
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
                data: { cep: cep, method: method },
                beforeSend: function() {
                    $("#estado").next("img").remove();
                                    $("#cidade").next("img").remove();
                                    $("#bairro").next("img").remove();
                                    $("#endereco").next("img").remove();

                    $("#estado").hide().after(img.clone());
                    $("#cidade").hide().after(img.clone());
                    $("#bairro").hide().after(img.clone());
                    $("#endereco").hide().after(img.clone());
                },
                error: function(xhr, status, error) {
                                busca_cep(cep, consumidor_revenda, "database");
                        },               
                
                success: function(data) {
                    results = data.split(";");

                    if (results[0] != "ok") {
                        alert(results[0]);
                        $("#cidade").show().next().remove();
                    } else {
                        $("#estado").val(results[4]);

                        //busca_cidade(results[4], consumidor_revenda);
                        results[3] = results[3].replace(/[()]/g, '');

                        $("#cidade").val(retiraAcentos(results[3]).toUpperCase());

                        if (results[2].length > 0) {
                            $("#bairro").val(results[2]);
                        }

                        if (results[1].length > 0) {
                            $("#endereco").val(results[1]);
                        }
                    }

                    $("#estado").show().next().remove();
                    $("#bairro").show().next().remove();
                    $("#endereco").show().next().remove();
                    $("#cidade").show().next().remove();

                    if ($("#bairro").val().length == 0) {
                        $("#bairro").focus();
                    } else if ($("#endereco").val().length == 0) {
                        $("#endereco").focus();
                    } else if ($("#numero").val().length == 0) {
                        $("#numero").focus();
                    }


                    
                    $.ajaxSetup({
                        timeout: 0
                    });
                }
            });
        }
    }

    function fnc_representante_pesquisa (campo, campo2, tipo) {
        if (tipo == "nome" ) {
            var xcampo = campo;
        }

        if (tipo == "cnpj" ) {
            var xcampo = campo2;
        }

        if (xcampo.value != "") {
            url = "representante_admin_pesquisa.php?valor=" + xcampo.value + "&parametro=" + tipo ;
            Shadowbox.open({
                content: url,
                player: "iframe",
                title: "Pequisa de Representantes",
                height: 450,
                width: 800
            });
        }
    }
    function retorna_representante(dados){
    	if (dados.representante != "") {
    		window.location.href='cadastro_representante_admin.php?representante='+dados.representante;
    	}
    }
</script>

<?php if ($msg) { ?>
    <div class="alert alert-success">
        <h4><?php echo $msg; ?></h4>
    </div>
<?php }

if (count($msg_erro["msg"])>0) { ?>
	<div class="alert alert-danger">
        <h4><?php echo implode("<br>",$msg_erro["msg"]);?></h4>
    </div>  
<?php }?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_representante" method="POST" class="form-search form-inline tc_formulario">
    <div class='titulo_tabela '><?=$cabecalho?></div>
 
    <input type="hidden" name="representante" value="<? echo $representante ?>">
    <p>&nbsp;</p>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span2">
                <div class="control-group <?=(in_array("codigo", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="codigo"><?php echo traduz("Código");?></label>
                    <input id="codigo" type="text" class="span12" value="<?=$codigo?>" name="codigo">
                </div>
            </div>
            <div class="span5">
                <div class="control-group <?=(in_array("razao_social", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="razao_social"><?php echo traduz("Razão Social");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
	                    <div class="input-append span12">
	                        <input id="razao_social" type="text" value="<?=$nome?>" class="span10" <?php echo (strlen($representante) > 0) ? "readonly" : "";?> name="razao_social" maxlength="60" />
	                        <span class="add-on" style="<?php echo (strlen($representante) > 0) ? "display:none" : "";?>" id="lupa-nome"><i class="icon-search"></i></span>
	                    </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group <?=(in_array("cnpj", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="cnpj"><?php echo traduz("CPF/CNPJ");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
	                    <div class="input-append span10">
	                        <input id="cnpj" type="text" class="span12" value="<?=$cnpj?>" name="cnpj" <?php echo (strlen($representante) > 0) ? "readonly" : "";?> maxlength="18" />
	                        <span class="add-on" style="<?php echo (strlen($representante) > 0) ? "display:none" : "";?>" id="lupa-cnpj"><i class="icon-search"></i></span>
	                    </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
        	<div class="span1"></div>
            <div class="span2">
                <div class="control-group">
                    <label for="ie"><?php echo traduz("Inscrição Estadual");?></label>
                    <input id="ie" type="text" class="span12" name="ie" value="<?=$ie?>" />
                </div>
            </div>
            <div class="span6">
                <div class="control-group <?=(in_array("email", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="email"><?php echo traduz("E-mail");?></label>
                    <div class="controls controls-row">
                        <h5 class="asteristico">*</h5>
                        <input id="email" type="email" value="<?=$email?>" class="span12" name="email" />
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group '>
                    <label class='control-label' for='desconto'><?php echo traduz("Desconto");?></label>
                    <div class="controls controls-row">
                        <div class="span9 input-append">
                            <input type="text" name="desconto" id="desconto" value="<?php echo $desconto;?>" class="span12 precos ">
                            <span  class="add-on">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>

            <div class="span6">
                <div class="control-group">
                    <label for="contato"><?php echo traduz("Contato");?></label>
                    <div class="controls controls-row">
	                    <input id="contato" type="text" class="span12" maxlength="30" value="<?=$contato?>" name="contato">
	                </div>
                </div>
            </div>
            <div class="span2">
                <div class="control-group <?=(in_array("fone", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="fone"><?php echo traduz("Telefone");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
	                    <input class="span12" type="text" id="fone" name="fone" value="<?=$fone?>">
	                </div>
                </div>
            </div>
            <div class="span2">
                <div class="control-group">
                    <label for="celular"><?php echo traduz("Celular");?></label>
                    <input class="span12" id="celular" name="celular" type="text" value="<?=$fax?>">
                </div>
            </div>
        </div>
        <div class="row-fluid">
        	<div class="span1"></div>
            <div class="span2">
                <div class="control-group <?=(in_array("cep", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="cep"><?php echo traduz("CEP");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
	                    <input class="span12" type="text" id="cep" name="cep" value="<?=$cep?>">
	                </div>
                </div>
            </div>
            <div class="span5">
                <div class="control-group <?=(in_array("endereco", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="endereco"><?php echo traduz("Endereço");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
                    	<input class="span12" type="text" id="endereco" name="endereco" value="<?=$endereco?>">
                	</div>
                </div>
            </div>
            <div class="span1">
                <div class="control-group <?=(in_array("numero", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="numero"><?php echo traduz("Número");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
                    	<input class="span12" id="numero" name="numero" type="text" value="<?=$numero?>">
                	</div>
                </div>
            </div>
            <div class="span2">
                <div class="control-group">
                    <label for="complemento"><?php echo traduz("Complemento");?></label>
                    <input class="span12" id="complemento" name="complemento" type="text" value="<?=$complemento?>">
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span3">
                <div class="control-group <?=(in_array("bairro", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="bairro"><?php echo traduz("Bairro");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
	                    <input class="span12" id="bairro" name="bairro" maxlength="20" type="text" value="<?=$bairro?>">
	                </div>
                </div>
            </div>
            <div class="span5">
                <div class="control-group <?=(in_array("cidade", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="cidade"><?php echo traduz("Cidade");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
	                    <input class="span12" id="cidade" name="cidade" type="text" value="<?=$cidade?>">
	                </div>
                </div>
            </div>
            <div class="span2">
                <div class="control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>">
                    <label for="estado"><?php echo traduz("Estado");?></label>
                    <div class="controls controls-row">
	                    <h5 class="asteristico">*</h5>
	                    <input class="span12" id="estado" maxlength="2" name="estado" type="text" value="<?=$estado?>">
	                </div>
                </div>
            </div>
        </div>
        <strong>Dados de Autenticação</strong>

            <div class="row-fluid">
                <div class="span3"></div>
                <div class="span3">
                    <div class="control-group <?=(in_array("login_representante_admin", $msg_erro["campos"])) ? "error" : ""?>">
                        <label for="login_representante_admin"><?php echo traduz("Login");?></label>
                        <div class="controls controls-row">
                            <h5 class="asteristico">*</h5>
                            <input class="span12" type="text" id="login_representante_admin" name="login_representante_admin" value="<?=$login_representante_admin?>">
                        </div>
                    </div>
                </div>
                <div class="span3">
                    <div class="control-group <?=(in_array("senha_representante_admin", $msg_erro["campos"])) ? "error" : ""?>">
                        <label for="senha_representante_admin"><?php echo traduz("Senha");?></label>
                        <div class="controls controls-row">
                            <h5 class="asteristico">*</h5>
                            <input class="span12" id="senha_representante_admin" name="senha_representante_admin" type="password" value="<?=$senha_representante_admin?>">
                        </div>
                    </div>
                </div>
                <div class="span4"> </div>
            </div>


	    <div class="row-fluid">
	        <div class="span12 tac">
	            <br />
	            <button id="btn_gravar"  class="btn" type="submit" name="btn_acao" value="gravar"><?php echo traduz("Gravar");?></button>
	            <span class="inptc5">&nbsp;</span>
	            <?php if (!isset($_GET['listar'])) {?>
					<a href='cadastro_representante_admin.php?listar=todos' class="btn btn-info"><?php echo traduz("Listar todos");?></a>
				<?php }?>
	        </div>
	    </div>
</form>
<?php 

if ($_GET['listar'] == 'todos') {
    $sql = "SELECT *
              FROM tbl_representante
             WHERE fabrica = $login_fabrica
          ORDER BY nome";
    $res = pg_query($con,$sql);

    echo "
    <h2 class='titulo_coluna'>Relação de Representantes</h2>
    <table id='listagem' class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
			<tr class='titulo_coluna'>
				<th>".traduz("Código")."</th>
				<th>".traduz("Razão Social")."</th>
				<th>".traduz("CPF/CNPJ")."</th>
				<th>".traduz("Inscrição Estadual")."</th>
				<th>".traduz("Contato")."</th>
				<th>".traduz("Telefone")."</th>
				<th>".traduz("Celular")."</th>
                <th>".traduz("Ativo")."</th>
				<th>".traduz("Log")."</th>
			</tr>
		</thead>
		<tbody>
	
    ";
    if (pg_num_rows($res) > 0) {

        foreach (pg_fetch_all($res) as $i => $rows) {
        	$ativo =  ($rows["ativo"] == 't') ? '<img title="'.traduz("Ativo").'" src="imagens/status_verde.png">' : '<img title="'.traduz("Inativo").'" src="imagens/status_vermelho.png">';
        	echo "
				<tr>
					<td class='tac'>".$rows["codigo"]."</td>
					<td><a href='cadastro_representante_admin.php?representante=".$rows["representante"]."'>".$rows["nome"]."</a></td>
					<td class='tac' nowrap><a href='cadastro_representante_admin.php?representante=".$rows["representante"]."'>".mascaraCPFCNPJ($rows["cnpj"])."</a></td>
					<td class='tac'>".$rows["ie"]."</td>
					<td>".$rows["contato"]."</td>
					<td class='tac'>".$rows["fone"]."</td>
					<td class='tac'>".$rows["fax"]."</td>
					<td class='tac'>".$ativo."</td>
                    <td class='tac'><a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_admin&id=".$login_fabrica."*".$rows["representante"]."'><button class='btn btn-mini btn-primary'>Log</button></a>
</td>
				</tr>
        	";
        }
    }
    echo "</tbody>
    </table>";

}

include "rodape.php";

