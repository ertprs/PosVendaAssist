<?php 

    $areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include_once '../class/sms/sms.class.php';

    if ($areaAdmin === true) {
        include "autentica_admin.php";
    } else {
        include "autentica_usuario.php";
    }

    include "funcoes.php";

    if(isset($_POST['enviar'])){
        
        $consumidor_celular = $_POST['consumidor_celular'];
        $msg_sms            = $_POST['sms_mensagem'];
        $os                 = $_POST["os"];

        $sms = new SMS();

        $enviar = $sms->enviarMensagem($consumidor_celular, 
            $os, 
            ' ', 
            $msg_sms);

        if($enviar == false){
            $sms->gravarSMSPendente($os);
            $retorno = array("erro" => "Erro ao enviar SMS.");
        }else{
            #$sql = "INSERT INTO tbl_os_interacao (os, admin, sms, comentario, fabrica) values ($os, $login_admin, true, '$msg_sms', $login_fabrica) ";
            #$res = pg_query($con, $sql);

            $retorno = array("sucesso" => utf8_encode("SMS enviado com sucesso."));
        }             

        exit(json_encode($retorno));
    }

?>

<!DOCTYPE html />
<html>
<head>
    <meta http-equiv=pragma content=no-cache />
    <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/glyphicon.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="bootstrap/js/bootstrap.js" ></script>
    <script src="plugins/shadowbox_lupa/lupa.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="plugins/jquery.mask.js"></script>

    <script>

    $(function() {
        $("#sms_submit").on("click", function() {
            var consumidor_celular  = $("#consumidor_celular").val();
            var sms_mensagem        = $("#sms_mensagem").val();
            var numos               = $("#os").val();

            if(consumidor_celular.length == 0 ){
                alert('OS sem número de celular do consumidor.');
                return false;
            }

            if(sms_mensagem.length == 0 ){
                alert('Informe a mensagem SMS.');
                return false;
            }


            //$(btn).button("loading");

            $.ajax({
                url: "sms_consumidor.php",
                type: "POST",
                data: {enviar:true, consumidor_celular:consumidor_celular,sms_mensagem:sms_mensagem, os:numos},
                dataType: "json",
                success : function(resultado) {
                    console.log(resultado);
                    if (resultado.sucesso) {
                        $(".mensagem-sucesso-sms").show();
                        $("#mensagem-sucesso-sms").html(resultado.sucesso);
                        $("#sms_mensagem").val("");
                        setTimeout(function(){ location.reload(); }, 500);
                    } else {
                        $(".mensagem-erro-sms").show();
                        $("#mensagem-erro-sms").html(resultado.erro);
                        setTimeout(function(){ location.reload(); }, 500);
                    }
                }, error: function(result){                    
                    $(".mensagem-erro").show();
                    $("#mensagem-erro").html('Falha ao enviar SMS');
                    setTimeout(function(){ location.reload(); }, 500);
                }
            });



        });
    });


    $(window).on("load", function() {
        changeHeight();
    });

    function changeHeight() {
        $("#sms_submit").button("reset");
        if (typeof window.parent.changeIframeHeight != "undefined") {
            var height = $(document).height();
            window.parent.changeIframeHeight("iframe_enviar_sms", height);

            height = $("#container_lupa").height();
            $("#container_lupa").css({ height: height+"px" });
        }
    }

    </script>
</head>
<body>

<div class="alert alert-success mensagem-sucesso-sms" style="display:none">
    <button type="button" class="close" data-dismiss="alert" >&times;</button>
    <strong id="mensagem-sucesso-sms"><?=$msg_sucesso?></strong>
</div>

<div class="alert alert-danger mensagem-erro-sms" style="display:none">
    <button type="button" class="close" data-dismiss="alert" >&times;</button>
    <strong id="mensagem-erro-sms"><?=implode("<br />", $msg_erro)?></strong>
</div>

<?php if($login_fabrica != 104){ ?>
<form class="tc_formulario" role="form">
    <div class='titulo_tabela '>Enviar SMS para Consumidor</div>
    <div class="row-fluid" >
        <div class="span1"></div>
        <div class="span10" >
            <div class="control-group" >
                <label class="control-label" for="sms_mensagem" >Mensagem</label>
                <div class="controls controls-row" >
                    <textarea id="sms_mensagem" name="sms_mensagem" class="span12" ><?=$_POST['sms_mensagem']?></textarea>
                </div>
            </div>
        </div>
        <div class="span1"></div>
    </div>
    <div class="row-fluid" >
        <div class="span1"></div>
        <div class="span10 tac">
            <input type="hidden" name="consumidor_celular" id="consumidor_celular" value="<?=$consumidor_celular?>">
             <input type="hidden" name="os" id="os" value="<?=$os?>">
            <button type="button" data-iframe="<?=$iframe?>" data-os="<?=$os?>" id="sms_submit" name="sms_submit" class="btn btn-success" data-loading-text="Enviando..." ><i class="icon-comment icon-white" ></i> Enviar</button>
        </div>
        <div class="span1"></div>
    </div>       
    </form>

<?php }
    $colspan = ($login_fabrica == 104) ? "5": "4";
 ?>

    
    <table class="table table-striped table-bordered" >
        <thead>
            <tr>
                <td class="titulo_tabela  tac" colspan="<?=$colspan?>" >Histórico de SMS</td>
            </tr>
            <tr>
                <th class="titulo_coluna">Nº</th>
                <th class="titulo_coluna">Data</th>
                <?php if($login_fabrica == 104){?>
                <th class="titulo_coluna">Origem do Envio</th>
                <th class="titulo_coluna">Status da Mensagem</th>
                <th class="titulo_coluna">Destinatário</th>
                <?php }else{ ?>
                <th class="titulo_coluna">Mensagem</th>
                <th class="titulo_coluna">Admin</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php  
                $sql = "SELECT texto_sms, data, tbl_admin.admin, nome_completo, status_sms, origem, destinatario, os, hd_chamado 
                        FROM tbl_sms 
                        LEFT JOIN tbl_admin on tbl_admin.admin = tbl_sms.admin and tbl_admin.fabrica = $login_fabrica
			WHERE tbl_sms.os = $os 
			AND tbl_sms.fabrica = $login_fabrica 
			ORDER BY data";
                $res = pg_query($con, $sql);
                for($b=0; $b<pg_num_rows($res); $b++){
                    $mensagem   = utf8_decode(pg_fetch_result($res, $b, texto_sms));
                    $data       = mostra_data( substr(pg_fetch_result($res, $b, data),0,16) );
                    $nome_completo      = pg_fetch_result($res, $b, nome_completo);
                    $destinatario       = pg_fetch_result($res, $b, destinatario);

                    $status_sms         = pg_fetch_result($res, $b, status_sms);
                    $origem             = pg_fetch_result($res, $b, origem);
                    $os                 = pg_fetch_result($res, $b, os);
                    $hd_chamado         = pg_fetch_result($res, $b, hd_chamado);

                    if (empty($status_sms)) {
                        $status_sms = "Aguardando Envio";
                    }

                    if(strlen($origem)==0){                        
                        if (empty($os) && !empty($hd_chamado)) {
                            $origem_status = "Call-Center";
                        } else if (empty($hd_chamado) && !empty($os)) {
                            $origem_status =  "Ordem de Serviço";
                        } else if (empty($os) && empty($hd_chamado)) {
                            $origem_status =  "Treinamento";
                        } else {
                            $origem_status =  "";
                        }
                    }else{
                        $origem_status =  $t_origem_envio;
                    }


                    echo "<tr>
                            <td class='tac' style='width:50px'>".($b+1)."</td>
                            <td class='tac' style='width:150px'>$data</td>";
                    if($login_fabrica == 104){
                        echo "<td class='tac'>$origem_status</td>";
                        echo "<td class='tac'>$status_sms</td>";
                        echo "<td class='tac'>".phone_format($destinatario)."</td>";
                    }else{
                        echo "<td>$mensagem</td>
                            <td>$nome_completo</td>
                        </tr>";
                    }
                }

            ?>
            
        </tbody>


</body>
</html>
