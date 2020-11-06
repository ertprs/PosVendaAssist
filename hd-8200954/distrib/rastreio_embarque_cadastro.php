<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'autentica_usuario.php';

include_once '../helpdesk/mlg_funciones.php';

include_once "../class/sms/sms.class.php";

if(strlen($login_unico)>0 AND $login_unico_master <>'t'){
	if($login_unico_distrib_total <>'t') {
		echo "<center><h1>Você não tem autorização para acessar este programa!</h1><br><br><a href='javascript:history.back();'>Voltar</a></center>";
		exit;
	}
}

$re_codigo = '^[A-Z]{2}\d{9}[A-Z]{2}$';


if (isset($_POST['upload_csv'])) {

    $csv     = $_FILES['upload_rastreio'];
    $arquivo = fopen($csv['tmp_name'], 'r');

    $log = [];
    $msg_upload['texto'] = "";
    $tipo = explode('.', $csv['name']);

    if ($tipo[1] != 'csv') {

        $msg_upload['texto'] = " Tipo do Arquivo Inválido <br> ";
    }

    if (strlen($msg_upload['texto']) == 0) {

        for ($i = 0; $linha = fgetcsv($arquivo); $i++) {

            $linha = explode(';', $linha[0]);

            $notaFiscal   = $linha[0];
            $rastreamento = $linha[1];

            $query = "SELECT embarque 
                      FROM tbl_faturamento 
                      WHERE fabrica = 10
                      AND nota_fiscal = '{$notaFiscal}'
                      AND emissao BETWEEN (CURRENT_DATE - interval '1 YEAR') AND CURRENT_DATE";

            $res = pg_query($con, $query);

            $embarque = pg_fetch_result($res, 0, 'embarque');

            if (strlen($embarque) > 0)  {

                $updateEtiqueta = "UPDATE tbl_etiqueta_servico 
                                   SET etiqueta = '{$rastreamento}'
                                   WHERE embarque = {$embarque}";

                $updateFrete = "UPDATE tbl_frete_transportadora 
                                SET codigo_rastreio = '{$rastreamento}'
                                WHERE embarque = {$embarque}";

                $updateFaturamento = "UPDATE tbl_faturamento 
                                      SET conhecimento = '{$rastreamento}'
                                      WHERE embarque = {$embarque}";

                pg_query($con, 'BEGIN'); 

                $resEtiqueta = pg_query($con, $updateEtiqueta); 
                $msg_error = pg_last_error();
                
                $resFrete    = pg_query($con, $updateFrete); 
                $msg_error .= pg_last_error();
                
                $resFaturamento = pg_query($con, $updateFaturamento); 
                $msg_error     .= pg_last_error();

                if (strlen(pg_last_error()) > 0) {
                    
                    pg_query($con, 'ROLLBACK');
                    $log[] = "Embarque : {$embarque} | Nota Fiscal : {$notaFiscal} | Rastreio : {$rastreamento} => Erro ao atualizar " . date() . "\n";
                
                } else {
                    
                    pg_query($con, 'COMMIT');
                    $log[] = "Embarque : {$embarque} | Nota Fiscal : {$notaFiscal} | Rastreio : {$rastreamento} => Sucesso ao atualizar " . date() . "\n";
                }

                unset($embarque);
            
            } else {

                $log[] = "Embarque : NULL    | Nota Fiscal : {$notaFiscal} | Rastreio : {$rastreamento} => Embarque não encontrado" . date() . "\n";
            }
        }

        #$localTxt = '../tmpFiles/testeUpload.txt';
        $localTxt = '/tmp/upload_rastreio.txt';

        $logTxt = fopen($localTxt, 'w+');

        foreach ($log as $linha) {

            fwrite($logTxt, $linha);
        }

        $msg_upload['cor']   = "#90EE90";
        $msg_upload['texto'] = "Upload realizado. <br> Para detalhes do upload, clique <a class='btn btn-success' download href='{$localTxt}'> aqui </a>";

    } else {

        $msg_upload['cor'] = "#FF6347";
    }
}

#exit("End of the line");

if (count($_GET)) {
    $embarque = getPost('embarque');
    if (!is_numeric($embarque))
        die('');

    $existe = pg_num_rows(
        $res = pg_query(
            "SELECT data, embarcar AS data_embarque,
                    tbl_posto.nome AS posto_destino,
                    COALESCE(tbl_etiqueta_servico.etiqueta, tbl_frete_transportadora.codigo_rastreio) as etiqueta,
                    tbl_fabrica.nome AS fabricante
               FROM tbl_embarque
               JOIN tbl_posto            USING(posto)
               JOIN tbl_fabrica          USING(fabrica)
          LEFT JOIN tbl_etiqueta_servico USING(embarque)
          LEFT JOIN tbl_frete_transportadora USING(embarque)
              WHERE embarque = $embarque"
        )
    );

    if (pg_last_error($con))
        die(pg_last_error($con));

    if (!$existe)
        die('Embarque não existe');

    $info = pg_fetch_assoc($res);

    if ($_POST['responseType'] == 'json')
        die(json_encode(array_map('utf8_encode', $info)));

    if ($info['data_embarque']) {
        $data = is_date($info['data_embarque'], 'ISO', 'EUR');
    } else {
        $data = is_date($info['data'], 'ISO', 'EUR');
    }

    $ret = "Embarque do <code>{$data}</code> para <u>{$info['posto_destino']}</u>";

    if (strlen($info['etiqueta']))
        echo("CR:{$info['etiqueta']}|");
    die($ret);

/*
    criar uma tela no distrib onde admin pode informar embarques(varios de 1 vez) e numero de rastreamento

    onde esses dados vao atualizar no tbl_etique_servico.etiqueta e tbl_faturamento.conhecimento
*/
}

if (count($_POST)) {
    $embarques = array_filter($_POST['embarque']);
    $codigos   = array_filter($_POST['codigo_rastreio']);

    foreach ($embarques as $i => $emb) {
        $cr = $codigos[$i];
        if (!preg_match("/$re_codigo/", $cr)) {
            $table[] = array(
                'Núm.' => $i + 1,
                'Embarque' => $emb,
                'Cód. Rastreio' => "<span class='text-danger'>Código <code>$cr</code> inválido!</span>"
            );
            $erros = true;
            continue;
        }

        $ues = pg_query($con, "UPDATE tbl_etiqueta_servico SET etiqueta     = '$cr' WHERE embarque = $emb;
                               UPDATE tbl_frete_transportadora SET codigo_rastreio     = '$cr' WHERE embarque = $emb;");

        $uft = pg_query($con, "UPDATE tbl_faturamento      SET conhecimento = '$cr' WHERE embarque = $emb");

        if (!pg_affected_rows($uft)) {
            $emb .= "<p class='text-error'>Erro ao gravar o código no faturamento!</p>";
            $erros = true;
        } else {
                    //envio de sms para einhell
            $sqlFabrica = "SELECT DISTINCT ON (tbl_os.os)
                                tbl_peca.fabrica,
                                tbl_os.os,
                                tbl_os.consumidor_celular,
                                tbl_os.consumidor_nome,
                                tbl_produto.descricao,
                                tbl_produto.referencia
                           FROM tbl_faturamento_item
                           JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
                           JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                           AND tbl_faturamento.embarque = $emb
                           JOIN tbl_os ON tbl_faturamento_item.os = tbl_os.os
                           JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                           WHERE tbl_faturamento.embarque = $emb
                           ";
            $resFabrica = pg_query($con, $sqlFabrica);

            if (pg_num_rows($resFabrica) > 0) {
                for ($x=0;$x < pg_num_rows($resFabrica);$x++) {

                    $fabrica            = pg_fetch_result($resFabrica, $x, 'fabrica');
                    $celular_consumidor = pg_fetch_result($resFabrica, $x, 'consumidor_celular');
                    $nome_consumidor    = pg_fetch_result($resFabrica, $x, 'consumidor_nome');
                    $os                 = pg_fetch_result($resFabrica, $x, 'os');
                    $produto            = pg_fetch_result($resFabrica, $x, 'referencia')." - ".pg_fetch_result($resFabrica, $x, 'descricao');

                    if (in_array($fabrica, array(160)) && !empty($celular_consumidor)) {
                        $sms = new SMS($fabrica);

                        $msg_sms = "OS {$os} EM ANDAMENTO: A EINHELL enviou a(s) peças(s) solicitada(s) pela AUTORIZADA para reparo do seu produto. Cód.rastreio: {$cr}";

                        $enviar  = $sms->enviarMensagem($celular_consumidor,$os,' ',$msg_sms);
                        if($enviar == false){
                            $sms->gravarSMSPendente($os);
                        }
                    }

                }
            }
        }

        $table[] = array(
            'Núm.' => $i + 1,
            'Embarque' => $emb,
            'Cód. Rastreio' => $cr
        );
    }
}

$title = "Manutenção Rastreio de Embarque";
?>
<html>
<head>
  <title><?php echo $title ?></title>
  <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
  <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
  <link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
  <script src="js/jquery-1.6.1.min.js"></script>
</head>
<body>
<? include 'menu.php' ?>
<?
if (!$erros or !count($_POST) or count($embarques) == 0) {
    $embarques = array('');
    $codigos = array('');
}

?>
<center>
    <h1 style="text-align:center"><?=$title?></h1>

    <div class="csv_upload" style="color: #596D9B;">
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span8" style="background-color: #D9E2EF;">
                <div class="row-fluid">
                    <h4>Upload em massa por CSV</h4>
                    <p>O csv precisa estar definido da seguinte maneira:</p>
                    <p> número da <strong>Nota fiscal</strong> | número de <strong>Rastreamento</strong></p> 
                </div>
                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class="span8">
                        <form id="form_upload" action="rastreio_embarque_cadastro.php" name="form_upload" method="POST" enctype='multipart/form-data'>
                            <div class='control-group' >
                                <h4>Arquivo</h4>
                                <div class="controls">
                                    <input type="hidden" name="upload_csv">
                                    <input type='file' name="upload_rastreio">
                                    <br><br>
                                    <input class="btn btn-info" type="submit" value="Upload">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="span2"></div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
    </div>
    
    <br><br>

    <?php if (isset($msg_upload)) { ?>
        <div class="csv_upload">
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span8" style="background-color: <?=$msg_upload['cor']?>">
                    <h4><?php echo $msg_upload['texto']; ?></h4>
                </div>
                <div class="span2"></div>
            </div>
        </div>
        <br><br>
    <?php } ?>

    <form class='form-search form-horizontal tc_formulario' name="frm_rastreio_embarque" method="post">
<?php foreach ($embarques as $key => $embarque): ?>
        <div class="row-fluid">
            <div class="offset1 span3">
                <div class="control-group">
                    <label class="control-label" for=''>Código do Embarque</label>
                    <div class="controls controls-row tac">
                    <input type='text' class='span10 emb'  required name='embarque[]' pattern="[0-9]+" align="right" value='<?=$embarque?>'>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group">
                    <label class="control-label" for=''>Código de Rastreio</label>
                    <div class="controls controls-row tac">
                    <input type='text' class='span12 cr'  required name='codigo_rastreio[]' pattern="<?=$re_codigo?>" value='<?=$codigos[$key]?>'>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="control-group">
                    <div class="controls-row tac">
                        <button type="button" class="btn clr btn-warning"><i class="icon icon-white icon-repeat"></i>&nbsp;Limpar</button>
                        <!--<button type="button" class="btn cpy btn-success"><i class="icon icon-white icon-share"></i>&nbsp;Copia</button>-->
                        <button type="button" class="btn add btn-info"><i class="icon icon-white icon-plus"></i>&nbsp;Mais</button>
                        <button type="button" class="btn del btn-danger"><i class="icon icon-minus icon-white"></i>&nbsp;Excluir</button>
                    </div>
                </div>
            </div>
        </div>
<?php endforeach; ?>
        <div class="row">
            <button type="submit" id="enviar" class="btn btn-default" name="btn_acao">Atualizar</button>
        </div>
    </form>
</center>
<?php if (count($table)):
    $table['attrs'] = array(
        'caption' => 'EMBARQUES ATUALIZADOS',
        'tableAttrs' => ' class="table table-striped table-hover table-bordered"',
    );
    echo '<div class="container center">',array2table($table), '</div>';
else: ?>
    <div style="height:300px"></div>
<?php endif; ?>
<p>
<? include "rodape.php"; ?>
<script type="text/javascript">
$(function() {
    // Ação dos buttons
    $('form.tc_formulario').delegate('button', 'click', function() {
        if ($(this).hasClass('add')) {
            $(".row-fluid:last").after(
                $(this).parents('.row-fluid')
                    .clone()
                        .find('.text-info').remove().end()
                        .find('input').val('').end()
                        .find('.INS').removeClass('INS').end()
                        .find('input:first').focus().end()
            );
        }
        if ($(this).hasClass('clr')) {
            $(this).parents('.row-fluid').find('input').val('');
            $(this).parents('.row-fluid').find('input').first().focus();
        }
        if ($(this).hasClass('del') && $(".row-fluid").length > 1) {
            $(this).parents('.row-fluid').remove();
        }
    });

    $('.tc_formulario').delegate('input.emb', 'change', function() {
        if ($(this)[0].validState === false) {
            return true;
        }
        var row   = $(this).parents('.row-fluid');
        var embId = $(this).val();

        // Valida embarque
        $.get(document.location.pathname + '?embarque='+embId,
            function(ret) {
                if (ret.indexOf('existe') > 0 && ret.length == 19) {
                    alert(ret);
                    row.find('input').first().val('').focus();
                    return true;
                }
                if (ret.length < 5) return true;

                // Existe o embarque e tem código de rastreio no banco...
                if (ret.indexOf('|') === 16) {
                    var codigo = ret.substr(3,13);
                    ret = ret.substr(ret.indexOf('|')+1);
                    row.find('.cr').val(codigo);
                }

                if ($(row).find('.text-info').length == 0)
                    row.append('<div class="span10 text-info">'+ret+'</div>');
                else
                    row.find('.text-info').html(ret);
            });
    });

    $(".tc_formulario").delegate('input.cr', 'keyup', function() {
        $(this).val($(this).val().toLocaleUpperCase());
    });

    // Bloqueia a digitação de códigos errados.
    $(".tc_formulario").delegate('input.cr', 'keypress', function(e) {
          var t = $(this).val();
          var c = e.charCode;
          if (c >= 32 && c <= 122) {
              if ((t.length < 2 || t.length > 10) && (c > 47 && c < 58))
                  return false;
              if ((t.length > 1 && t.length < 10) && (c < 48 || c > 57))
                  return false;
              if (t.length > 12)
                  return false;
          }
    });

    // Pula para o próximo registro. Deshabilitado por causar mais confusão do que
    // melhorar a usabilidade.
    //$(".tc_formulario").delegate('input.cr', 'blur', function() {
    //     if ($(this).val().length==13 && $(this).hasClass('INS') === false) {
    //         $(this).parents('.row-fluid').find('.add').click();
    //         $(this).addClass('INS');
    //     }
    // });
});
</script>
</html>

