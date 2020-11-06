<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

include __DIR__."/class/tdocs.class.php";

include __DIR__."/class/ComunicatorMirror.php";
$comunicatorMirror = new ComunicatorMirror();

$msg_erro    = '';
if($telecontrol_distrib or in_array($login_fabrica, array(91,152,154,171,178,180,181,182,183,184,191,200))){
    include_once S3CLASS;
    $s3_extrato = new AmazonTC("extrato", (int) $login_fabrica);
}

$btn_acao = $_REQUEST['btn_acao'];

if(isset($_POST['removeAnexo'])==true){
    $nome_arquivo = $_POST['nome_arquivo'];
    $extrato1     = $_POST['extrato'];

    if(!empty($nome_arquivo) and $telecontrol_distrib){
        $sql_pendente = " SELECT pendente FROM tbl_extrato_status 
                          WHERE extrato = $extrato1
			  AND fabrica = $login_fabrica 
                          ORDER BY data DESC LIMIT 1 ";
        $res_pendente = pg_query($con, $sql_pendente);
            if(pg_num_rows($res_pendente) > 0) {
                $pendente1 = pg_fetch_result($res_pendente, 0, 'pendente');
                    if ($pendente1 == 't') {
                        $sql_nf_recebida = " update tbl_extrato set nf_recebida = 'f' where extrato = $extrato1 and fabrica = $login_fabrica ";
                        $res_nf_recebida = pg_query($con, $sql_nf_recebida);
                        echo json_encode(array('retorno' => 'ok')); 
                        $s3_extrato->deleteObject("$nome_arquivo");
                    }else{
                        echo json_encode(array('retorno' => 'erro'));                    
                    }
            }           
	}elseif(!empty($nome_arquivo)){

        if(in_array($login_fabrica, array(152,180,181,182))) {

          if ($usaNotaFiscalServico) {
             $statusObs = 'Aguardando Envio da Nota Fiscal';
          } else {
             $statusObs = 'Aguardando Nota Fiscal do Posto';
          }

          $sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia) 
                      VALUES ($login_fabrica, $extrato1, now(), 'Aguardando Nota Fiscal do Posto', false) ";
          $res_extrato_status = pg_query($con, $sql_extrato_status);
          if(strlen(pg_last_error($con))>0){
            $msg_erro .= "Falha ao liberar o extrato $extrato1. <br> ";
          }else{
            $sql = "SELECT posto FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$extrato1}
            ";
            $res = pg_query($con, $sql);

            $posto = pg_fetch_result($res, 0, "posto");
            $sql_comunicado = "INSERT INTO tbl_comunicado
                (
                    fabrica,
                    posto,
                    obrigatorio_site,
                    tipo,
                    ativo,
                    descricao,
                    mensagem
                )
                VALUES
                (
                    {$login_fabrica},
                    {$posto},
                    true,
                    'Com. Unico Posto',
                    true,
                    'Extrato Liberado',
                    'A Nota Fiscal do extrato $extrato1 foi excluída e está aguardando nova nota fiscal. '
                )";

            $res_comunicado = pg_query($con, $sql_comunicado);

            if (strlen(pg_last_error()) > 0) {
              $msg_erro = "Erro ao liberar o extrato";
            }
          }
        }
		echo json_encode(array('retorno' => 'ok')); 
		$s3_extrato->deleteObject("$nome_arquivo");      
	}else{
        echo json_encode(array('retorno' => 'erro'));
      
    }

    exit;
}

if(!empty($_POST['salvarnfedatanf'])){
    $notaFiscal = $_POST['notaFiscal'];
    $dataNotaFiscal = formata_data($_POST['dataNotaFiscal']);
    $posto_login = $_POST['posto_login'];
    $extrato = $_POST['extrato'];

    if ($login_fabrica == 183){
        $sql_valida_data = "SELECT now() WHERE '$dataNotaFiscal' BETWEEN to_char(now(),'YYYY-MM-01')::date AND now()";
        $res_valida_data = pg_query($con, $sql_valida_data);

        if (pg_num_rows($res_valida_data) == 0){
            $return = array("error" => utf8_encode(traduz("Data da Nota Fiscal deve estar dentro do mês vigente")));
            exit(json_encode($return));
        }
    }


    $sql  = "SELECT extrato_pagamento
        FROM    tbl_extrato_pagamento
        JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
        WHERE   tbl_extrato_pagamento.extrato = $extrato
        AND     tbl_extrato.posto   = $login_posto
        AND     tbl_extrato.fabrica = $login_fabrica";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        $extrato_pagameto = pg_fetch_result($res, 0, extrato_pagamento);
        $sql = "UPDATE tbl_extrato_pagamento set data_nf = '$dataNotaFiscal 00:00:01' , nf_peca = '$notaFiscal' where extrato_pagamento = $extrato_pagameto";
        $res = pg_query($con,$sql);
        if(strlen(pg_last_error())){
            $return = array("error" => utf8_encode(traduz("Ocorreu um erro inesperado! Entre em contato com a Telecontrol")));
            // $return = array("error" => utf8_encode("Ocorreu um erro inesperado! Entre em contato com a Telecontrol"));
        }else{
            $return = array("success" => utf8_encode(traduz("Dados Alterados!")));
        }
    }else{
        $sql = "INSERT INTO tbl_extrato_pagamento(data_nf,nf_peca,admin,extrato)
                    VALUES ( '$dataNotaFiscal 00:00:01' , '$notaFiscal'  , (select admin from tbl_extrato where extrato = $extrato),$extrato)";
        $res = pg_query($con,$sql);
        if(strlen(pg_last_error())){
            // $return = array("error" => utf8_encode("Ocorreu um erro inesperado! Entre em contato com a Telecontrol"));
            $return = array("error" => utf8_encode($sql.traduz("Ocorreu um erro inesperado! Entre em contato com a Telecontrol")));
        }else{
            $return = array("success" => utf8_encode(traduz("Dados Gravados com Sucesso!")));
        }
    }
    exit(json_encode($return));
}
if (strlen($_GET['extrato']) == 0 && !in_array($login_fabrica, [169,170])) {
    header("Location: os_extrato.php");
    exit;
}

if ($btn_acao == 'gravar_nota') {
    $postoId = $_REQUEST['posto'];
    $extratoId = $_REQUEST['extrato'];
    $nfPosto = $_REQUEST['nota_fiscal'];
    $dtEmissao = $_REQUEST['emissao'];

    try {

        if (empty($nfPosto) || empty($dtEmissao)) {
            throw new Exception("Nota fiscal e data de emissão são obrigatórios");
        }
        
        if (!empty($dtEmissao)) {
            list($d, $m, $y) = explode("/", $dtEmissao);
            $configDtEmissao = "{$y}-{$m}-{$d}";
            $auxDtEmissao = date("Y-m-d", strtotime($configDtEmissao));
        }

        if (count($_FILES) > 0) {
            $tem_anexos = array();
            $Ntem_pdf = true;

            foreach($_FILES as $key => $value) {
                $type = strtolower(preg_replace("/.+\//", "", $value["type"]));
                
                $path = $value['name'];
                $extPath = pathinfo($path, PATHINFO_EXTENSION);

                if (!empty($value['name'])) {
                    $tem_anexos[] = "ok";
    
					if ($type == "pdf" || strtolower($extPath) == "pdf") {
						$Ntem_pdf = false;
					}

					if ($key == 'nota_fiscal_pdf' && strtoupper($type) != 'PDF' && strtoupper($extPath) != 'PDF') {
						throw new Exception("Formato do arquivo inválido, formato do arquivo deve ser PDF");
					}

					if ($key == 'nota_fiscal_xml' && strtoupper($type) != 'XML' && strtoupper($extPath) != 'XML') {
						throw new Exception("Formato do arquivo inválido, formato do arquivo deve ser XML");   
					}
				}
            }
    
            if ($Ntem_pdf) {
                throw new Exception("Um anexo deve ser um PDF da Nota Fiscal");
            }
        } else {
            throw new Exception("Ao menos o anexo do PDF da Nota Fiscal é obrigatório");
        }

        $jsonParametrosAdicionais = json_encode([
            "notaFiscal" => $nfPosto
        ]);

        pg_query($con, "BEGIN;");
        pg_query($con, "INSERT INTO tbl_extrato_status (extrato,data,obs,fabrica,admin_conferiu, pendente, parametros_adicionais) 
                        VALUES ({$extratoId},current_timestamp,'NF Enviada',{$login_fabrica},null, false, '{$jsonParametrosAdicionais}')");

        pg_query($con, "UPDATE tbl_extrato SET nf_recebida = TRUE WHERE extrato = {$extratoId};");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Ocorreu um erro atualizando dados do Extrato {$extratoId} #001");
        }

        pg_query($con, "UPDATE tbl_extrato_pagamento SET data_recebimento_nf = now(), data_nf = '{$auxDtEmissao}', nf_autorizacao = '{$nfPosto}', justificativa = NULL WHERE extrato = {$extratoId};");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Ocorreu um erro atualizando dados do Extrato {$extratoId} #002");
        }

        unset($amazonTC, $image, $types);
        $amazonTC = new TDocs($con, $login_fabrica);
        $amazonTC->setContext("extrato", "nf_autorizacao");
        $types = array("xml", "pdf");
        $erro_anexo = "";
        $caminhoUrlPdf = '';
        
        foreach($_FILES as $key => $imagem) {
            if ((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)) {
                $type = strtolower(preg_replace("/.+\//", "", $imagem["type"]));

                $path = $imagem['name'];
                $extPath = pathinfo($path, PATHINFO_EXTENSION);

                if (!in_array($type, $types) && !in_array($extPath, $types)) {
                    $erro_anexo .= "Formato inválido, são aceitos os seguintes formatos: pdf e xml<br />";
                    continue;
				} else	{
                    $imagem['name'] = "nf_autorizacao_{$extrato}_{$login_fabrica}_{$key}.$type";

                    if (strtolower($type) == 'pdf' || strtolower($extPath) == 'pdf') {
                        $caminhoUrlPdf = "nf_autorizacao_{$extrato}_{$login_fabrica}_{$key}.$type";
                    }

                    if (!$amazonTC->uploadFileS3($imagem, $extrato, false)) {
                        $erro_anexo .= "Erro ao gravar o anexo {$key}<br />";
                    }
                }
            }
        }

        if (strlen($erro_anexo) > 0) {
            throw new Exception($erro_anexo);
        }

        unset($amazonTC, $anexos, $types);
        $amazonTC = new TDocs($con, $login_fabrica);
        $amazonTC->setContext("extrato", "nf_autorizacao");
        $anexo = array();

        $anexo["nome"] = "nf_autorizacao_{$extrato}_{$login_fabrica}_nota_fiscal_xml";
        $anexo["url"] = $amazonTC->getDocumentsByName($anexo["nome"],null, $extrato)->url;

        if (!empty($caminhoUrlPdf) && !empty($amazonTC->getDocumentsByName($caminhoUrlPdf,null, $extrato)->url)) {
            pg_query($con, "COMMIT;");
            
            unset($dtEmissao, $nfPosto);
            $msg_sucesso = "Nota Fiscal cadastrada com sucesso";
        } else {
            throw new Exception("Ocorreu um erro buscar o anexo do PDF #003");
        }
    
        if (strlen($anexo["url"]) > 0) {
            $sqlPst = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = {$postoId} AND fabrica = {$login_fabrica};";
            $resPst = pg_query($con, $sqlPst);
            if (pg_num_rows($resPst) > 0) {
                $postoCodigo = pg_fetch_result($resPst, 0, 'codigo_posto');
                $assunto = "Nota Fiscal de Serviçoes Posto: {$postoCodigo}, Extrato: {$extrato}, NF: {$nfPosto}";
                $mensagem = "
                    Olá,<br /><br />
                    Segue Nota Fiscal de serviços do posto {$postoCodigo}<br />
                    <b>NF:</b> {$nfPosto}<br />
                    <b>Emissão:</b> {$dtEmissao}<br />
                    <b>Link NF</b><br />
                    <a href='{$anexo['url']}' target='_blank'>XML</a><br />
                    <br />
                ";

                $comunicatorMirror->post('nfe@mideacarrier.com', utf8_encode($assunto), utf8_encode($mensagem), 'noreply@tc', 'noreply@telecontrol.com.br');
            }
        }

    } catch (Exception $e) {
        pg_query($con, "ROLLBACK;");
        $msg_erro = $e->getMessage();
    }

}
                
if($telecontrol_distrib){
    $sql = "SELECT * from tbl_extrato_status WHERE extrato = $extrato order by extrato desc limit 1";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $pendente = pg_fetch_result($res, 0, 'pendente');
        $conferido = pg_fetch_result($res, 0, 'conferido');
        $admin_conferiu = pg_fetch_result($res, 0, 'admin_conferiu');
    }
}

if(isset($_FILES['arquivo_nota_fiscal_servico'])){

    $extrato = $_GET['extrato'];
    $cont = 0;
    foreach($_FILES['arquivo_nota_fiscal_servico']['name'] as $file){

        if(strlen($_FILES['arquivo_nota_fiscal_servico']['name'][$cont])==0){
            continue;
        }

        $arquivo['name'] = $_FILES['arquivo_nota_fiscal_servico']['name'][$cont];
        $arquivo['type'] = $_FILES['arquivo_nota_fiscal_servico']['type'][$cont];
        $arquivo['tmp_name'] = $_FILES['arquivo_nota_fiscal_servico']['tmp_name'][$cont];
        $arquivo['error'] = $_FILES['arquivo_nota_fiscal_servico']['error'][$cont];
        $arquivo['size'] = $_FILES['arquivo_nota_fiscal_servico']['size'][$cont];

        if($telecontrol_distrib || in_array($login_fabrica, [157,183])){
            $types = array("pdf");
            $msg_formatos = traduz("Formato inválido, favor inserir um arquivo no formato PDF");
        }else{
            $types = array("png", "jpg", "pdf", "doc", "docx", "bmp");
            $msg_formatos = traduz("Formato inválido, são aceitos os seguintes formatos: png, jpg, pdf, doc, docx, bmp");
        }
        
        $type  = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));
        
        if ($type == "jpeg") {
            $type = "jpg";
        }
        
        if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["size"] > 0) {
            if (!in_array($type, $types)) {
                $msg_erro = $msg_formatos;
            } else {
                if ($login_fabrica == 183){
                    include_once __DIR__.'/plugins/fileuploader/TdocsMirror.php';

                    $arquivo["name"] = str_replace(" ", "_", $arquivo["name"]);
                    $nome_arquivo = retira_acentos($arquivo["name"]);

                    $nomePDF = $arquivo['tmp_name'];
                    
                    $tdocsMirror = new TdocsMirror();
                    $response    = $tdocsMirror->post($nomePDF);
                    
                    foreach ($response[0] as $key => $value) {
                        $unique_id = $value["unique_id"];
                        $obs[]= array(
                            "acao" => "anexar",
                            "filename" => $nome_arquivo,
                            "data" => $value["date"],
                            "fabrica" => $login_fabrica,
                            "usuario" => "$login_admin",
                            "typeId" => "nfe_servico",
                            "page" => "os_extrato_detalhe.php"
                        );
                    }

                    if (!empty($response)){
                        $obs = json_encode($obs);
                        $sql = "
                            INSERT INTO tbl_tdocs(
                                tdocs_id,
                                fabrica,
                                contexto,
                                situacao,
                                obs,
                                referencia,
                                referencia_id
                            )VALUES(
                                '$unique_id', 
                                $login_fabrica, 
                                'extrato', 
                                'ativo', 
                                '$obs', 
                                'extrato',
                                $extrato
                            )"; 
                        $res = pg_query($con, $sql); 
                    }
                }else{
                    $filess = $s3_extrato->getObjectList("{$extrato}-", false);            
                    $qtdeAnexos = !count($filess) ? '' : '-'.count($filess);
                    $s3_extrato->upload("{$extrato}-nota_fiscal_servico{$qtdeAnexos}", $arquivo);
                }
            }

            if($telecontrol_distrib AND strlen($msg_erro) == 0){
                $sql = "UPDATE tbl_extrato set nf_recebida = true WHERE extrato = $extrato";
                $res = pg_query($con, $sql);
                $sql_extrato_status = " insert into tbl_extrato_status (data, obs, pendente, fabrica, extrato, arquivo) values (now(), 'Envio de NFe', true, $login_fabrica, $extrato, '$extrato-nota_fiscal_servico') ";
                $res_extrato_status = pg_query($con, $sql_extrato_status);
            }

            if(in_array($login_fabrica, [152,180,181,182,183]) AND strlen($msg_erro) == 0){
        	   $sql_extrato_status = " insert into tbl_extrato_status (data, obs, pendente, fabrica, extrato) values (now(), 'Aguardando Aprovação de Nota Fiscal', true, $login_fabrica, $extrato) ";
        	   $res_extrato_status = pg_query($con, $sql_extrato_status);                
    	    }
        } else {
            $msg_erro = traduz("Erro ao fazer o upload do arquivo");
        }
        $cont++;
    }

    if(in_array($login_fabrica, array(152,180,181,182))) {
        $sql_extrato_status = " insert into tbl_extrato_status (data, obs, pendente, fabrica, extrato) values (now(), 'Aguardando Aprovação de Nota Fiscal', true, $login_fabrica, $extrato) ";
        $res_extrato_status = pg_query($con, $sql_extrato_status);                
    }

}


if($login_fabrica == 11 OR $login_fabrica == 126){
    if ($S3_sdk_OK) {
        include_once S3CLASS;
        $s3ve = new anexaS3('ve', (int) $login_fabrica);
        $S3_online = is_object($s3ve);
    }
    $s3 = new AmazonTC("os", $login_fabrica, true);
}
$extrato = trim($_POST['extrato']);

if(in_array($login_fabrica, array(11,172))){

    if(strlen($extrato) > 0){

        $sql_fabrica = "SELECT fabrica FROM tbl_extrato WHERE extrato = {$extrato}";
        $res_fabrica = pg_query($con, $sql_fabrica);

        if(pg_num_rows($res_fabrica) > 0){

            $fabrica_os = pg_fetch_result($res_fabrica, 0, "fabrica");

            if($fabrica_os != $login_fabrica){

                $self = $_SERVER['PHP_SELF'];
                $self = explode("/", $self);

                unset($self[count($self)-1]);

                $page = implode("/", $self);
                $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
                $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?extrato={$extrato}";

                $params = "?cook_admin=&cook_fabrica={$fabrica_os}&page_return={$pageReturn}";
                $page = $page.$params;

                header("Location: {$page}");
                exit;

            }

        }

    }

}

if(strlen($_GET['extrato']) > 0) $extrato = trim($_GET['extrato']);
$posto = trim($_POST['posto']);
if (strlen($_GET['posto']) > 0) $posto = trim($_GET['posto']);
$layout_menu = "os";
$title = ($sistema_lingua == "ES") ? "Extracto - Detallado" : "Extrato - Detalhado";
//HD 209349
if (trim($_POST['bt_acao']) == 'GRAVAR') {
    /**
    * comentado pois por causa do chamado 216338, apos implantar este continua a aprovação do HD 209349
    *
    *$emissao_mao_de_obra = explode('/',trim($_POST['emissao_mao_de_obra']));
    *$emissao_mao_de_obra = $emissao_mao_de_obra[2].'-'.$emissao_mao_de_obra[1].'-'.$emissao_mao_de_obra[0];
    *
    *$res = pg_query($con,"BEGIN TRANSACTION");
    *
    *$sql = "UPDATE tbl_extrato_extra
    *           SET nota_fiscal_mao_de_obra = '".trim($_POST['nota_fiscal_mao_de_obra'])."'
    *             , emissao_mao_de_obra = '".$emissao_mao_de_obra."'
    *         WHERE extrato = " . $extrato;
    *
    *$result = @pg_query($con,$sql);
    *
    *$msg_erro = pg_last_error($con);
    *
    *$res = (strlen($msg_erro) == 0) ? pg_query($con,"COMMIT TRANSACTION") : pg_exec($con,"ROLLBACK TRANSACTION");
    */
}
include "cabecalho.php";

include "javascript_calendario_new.php";
include "js/js_css.php"; ?>

<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
<link href="plugins/shadowbox/shadowbox.css" rel="stylesheet">

<script type="text/javascript">
    $(function() {

        $(".removeAnexo").click(function(){            
            var nome_arquivo = $(this).data('nomearquivo');
            var extrato = $(this).data('extrato');
            var posicao = $(this).data('qtde');
            

            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                data: { removeAnexo: true, nome_arquivo: nome_arquivo, extrato: extrato },
                complete: function(data) {
                    data = JSON.parse(data.responseText);
                    if (data.retorno == 'ok') {
                        $(".anexos_"+posicao).remove();
                    }
                    if(data.retorno == 'erro'){
                        alert("<?= traduz('Falha ao excluir anexo') ?>");
                    }
                }
            });
        });

        $("#adicionar_arquivo").click(function(){
            var campo = $(".arquivo_nota:first").clone();
            $('#local_arquivo_nota').append(campo);
            $("#local_arquivo_nota:last").find("input").attr({value:''})
        });

        $("#emissao_mao_de_obra").maskedinput("99/99/9999");
        $('input.salvar_nota').click(function(){
            var nf = $('#arquivo_nota_fiscal_servico').val();
            if(nf.length > 0){
                <?php if($telecontrol_distrib or in_array($login_fabrica, array(152,154,180,181,182,183))){ ?>
                var result = confirm("Realmente deseja enviar a NFe selecionada para fábrica?");
                if (result == true) {
                    $(this).parents("#form_nota_fiscal_servico").submit();
                }
                <?php } else{ ?>
                    $(this).parents("#form_nota_fiscal_servico").submit();
                <?php } ?>
            }else{
                alert("<?= traduz('Selecione o arquivo da nota fiscal de serviço') ?>");
            }
        });
    });
    function verificaData(data) {
        if (new Date(data.replace(/(\d\d).(\d\d).(\d{4})/, '$3-$2-$1')) == 'Invalid Date') {
            // return 'Data inválida!'; // opção simples...
            // Formato dd/mm/yyyy
            var vet = data.split(/\W/);
            var dia = parseInt(vet[0]);
            var mes = parseInt(vet[1]);
            var ano = parseInt(vet[2]);
            var msg = "";
            if (mes < 1 || mes > 12) {
                msg = "Mês inválido";
            } else if (mes == 2 && (dia > 28 + (ano%4 == 0 && ano != 2000))) { // fev. e ano Bissexto?
                msg = "Dia inválido" + ((dia==29) ? ", ano não é Bissexto":'');
            } else if (dia < 1 || dia > 30 + ([4,6,9,11].indexOf(mes) == -1)) { // finalmente os meses de 30
                msg = "Dia inválido";
            }
            return msg;
        }
        return 'OK';
    }
    function valida() {
        var msg = '';
        if (document.getElementById('nota_fiscal_mao_de_obra').value == '') {
            msg += "NF Mão de Obra\n";
        }
        if (document.getElementById('emissao_mao_de_obra').value == '') {
            msg += "Data Emissão\n";
        } else {
            msg += verificaData(document.getElementById('emissao_mao_de_obra').value);
        }
        if (msg != '') {
            alert(msg);
            return false;
        } else {
            return true;
        }
    }
</script>
<style type="text/css">
    .menu_top4 {
        text-align: center;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: x-small;
        font-weight: bold;
        border: 1px solid;
        color:#ffffff;
        background-color: #CC3333;
    }
    .menu_top {
        text-align: center;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: x-small;
        font-weight: bold;
        border: 1px solid;
        color:#000000;
        background-color: #d9e2ef
    }
    .table_line,.table_line2 {
        text-align: left;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 10px;
        font-weight: normal;
    }
    .table_line {
        border: 0px solid;
        background-color: #D9E2EF;
    }
    .table_line3 {
        text-align: center;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 12px;
        font-weight: normal;
        border: 0px solid;
        background-color: #FE918D
    }
    #tbl_os_filtra {
        border-collapse: collapse;
        text-align: center
    }
    #tbl_os_filtra tr:first-child {text-transform: uppercase;}
    #tbl_os_filtra td:last-of-type {
        text-align: right
    }
    #tbl_os_filtra tr:nth-of-type(even) {   /* No-IE < v8 */
        background-color: #F1F4FA
    }
    #tbl_os_filtra TD {
        padding: 1px 1ex 1px 1ex;
        border: 1px white solid;
    }
    .msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.msg_sucesso{
background-color:#00FF00;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}
/*tabela*/
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.espaco{
    padding: 0 0 0 50px;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.group_table_line{
    cursor: pointer;
    cursor: hand;
}
td table {
    border-collapse: collapse;
    text-align: center
}
td table tr:first-child {text-transform: uppercase;}
td table td:last-of-type {
    text-align: right
}
td table tr:nth-of-type(even) {   /* No-IE < v8 */
    background-color: #F1F4FA
}
td table td {
    padding: 1px 1ex 1px 1ex;
    border: 1px white solid;
}
<?php /* HD 2416981 */ ?>
table#tabela_obs_ad td {
  vertical-align: top;
  padding: 1ex 1ex;
}
table#tabela_obs_ad thead,
table#tabela_obs_ad caption {
    border-color: #fff;
    height: 22px;
    line-height: 22px;
    text-transform: uppercase;
}
table#tabela_obs_ad thead th {text-transform: uppercase;line-height:22px}
table#tabela_obs_ad td p.servico {margin: 0}
table#tabela_obs_ad td p.servico:not(:last-of-type) {margin-bottom: 0.5ex}
td p.servico>span {display: inline-block; width: 7em;font-weight:bold}
</style>
<?php if (strlen($msg_erro) > 0) {
    echo "<TABLE width=\"600\" align='center' border=0>";
        echo "<TR>";
            echo "<TD align='center' class='msg_erro'>$msg_erro</TD>";
        echo "</TR>";
    echo "</TABLE>";
} else if (!empty($msg_sucesso)) { ?>
    <table width="600" align="center" border="0">
        <tr>
            <td align="center" class="msg_sucesso"><?= $msg_sucesso; ?></td>
        </tr>
    </table>
<?php }
if ($login_fabrica == 51) {//HD 51812 13/11/2008
    echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
        echo "<TR>";
            echo "<TD colspan='10' class='menu_top4'><div align='center' style='font-size:16px'><b>";
                echo "ATENÇÃO!";
            echo "</b></div></TD>";
        echo "</TR>";
    echo "</table>";
    echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
        echo "<tr class='table_line3'>\n";
            echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE MÃO DE OBRA PARA:</B><BR>
                BRASITECH INDUSTRIA E COMERCIO DE APARELHOS PARA BELEZA LTDA.<br>
                RUA Vemag, 629 Ipiranga - São Paulo / SP<br>
                CEP:04217-050 <br>
                CNPJ: 07.293.118/0004-47 <br>
                INSC.ESTADUAL: 147.618.438.110<br> \n";
            echo "</td>\n";
        echo "</tr>\n";
        echo "<tr class='table_line3'>\n";
            echo "<td align=\"center\"><B>ENVIAR AS OS's JUNTO COM A NF DE MÃO DE OBRA PARA:</b><BR>
                    TELECONTROL NETWORKING LTDA.<br>
                    AV. Carlos Artêncio, 420 B - Fragata C<br>
                    Marília, SP, CEP 17519-255 <br>
                    CNPJ: 04.716.427/0001-41 ";
            echo "</td>\n";
        echo "</tr>\n";
    echo "</table>";
    echo "<BR>";
}

if (in_array($login_fabrica, array(169,170))) {
        echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
                echo "<TR>";
                        echo "<TD colspan='10' class='menu_top4'><div align='center' style='font-size:16px'><b>";
                                echo "ATENÇÃO!";
                        echo "</b></div></TD>";
                echo "</TR>";
        echo "</table>";

        echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
                echo "<tr class='table_line3'>\n";
                        echo "<td align=\"center\"><b>EMITIR NOTA FISCAL DE MÃO DE OBRA PARA:</b><br />
                                SPRINGER CARRIER LTDA.<br>
                                RUA BERTO CÍRIO, 521 - SÃO LUIS - CANOAS / RS<br />
                                CEP: 92420-030 <br>
                                CNPJ: 10.948.651/0001-61<br />
                                INSC. MUNICIPAL: 13740<br />
                                INSC. ESTADUAL: 0240114736<br />
                                <b>OBS.: Somente serão aceitas as NFs de serviços emitidas e enviadas do dia 01 ao dia 20 do mês vigente.<b>
                                \n";
                        echo "</td>\n";
                echo "</tr>\n";
        echo "</table>";
}

if ($login_fabrica == 187) {
	echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
                echo "<TR>";
                        echo "<TD colspan='10' class='menu_top4'><div align='center' style='font-size:16px'><b>";
                                echo "ATENÇÃO!";
                        echo "</b></div></TD>";
                echo "</TR>";
        echo "</table>";

        echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
                echo "<tr class='table_line3'>\n";
                        echo "<td align=\"center\"><b>EMITIR NOTA FISCAL DE MÃO DE OBRA PARA:</b><br />
                                ACACIAELETRO PAULISTA EIRELI - EPP<br>
                                AV. CARLOS ARTENCIO, 420 A - FRAGATA - MARÍLIA / SP<br />
                                CEP: 17.519-255<br>
                                CNPJ: 66.494.691/0001-35<br />
                                INSC. MUNICIPAL: 21323<br />
                                INSC. ESTADUAL: 438258480116<br />
                                \n";
                        echo "</td>\n";
                echo "</tr>\n";
        echo "</table>";
}

if ($telecontrol_distrib and !$controle_distrib_telecontrol and $login_fabrica != 187){
    echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
        echo "<TR>";
            echo "<TD colspan='10' class='menu_top4'><div align='center' style='font-size:16px'><b>";
                echo "ATENÇÃO!";
            echo "</b></div></TD>";
        echo "</TR>";
    echo "</table>";
    echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
    echo "<tr class='table_line3'>\n";
            if($login_fabrica == 153) {
                echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE MÃO DE OBRA:</B><BR>
                        PST ELETRONICA S.A<br />
                        CNPJ: 84.496.066/0002-95<br />
                        IE:  244.586.795.119<br />
                        Av. Alan Turing, 385 - Cidade Universitária<br />
                        CEP 13083-898<br />
                        Campinas/SP";
	    }else if($login_fabrica == 160){
	    	echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE MÃO DE OBRA:</B><BR>
	    		CNPJ 67.647.412/0004-31<br />
			ANCORA CHUMBADORES LTDA<br />
			LOGRADOURO: AV BENEDITO STORANI (JARDIM ALVES NOGUEIRA)<br />
			NÚMERO 1345 <br />
			CEP 13.289-004 <br />
			BAIRRO/DISTRITO SANTA ROSA <br />
			MUNICÍPIO VINHEDO/SP <br />
			TELEFONE (11) 2066-4788";
	    }else{
                echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE MÃO DE OBRA:</B><BR>
                        ACACIAELETRO PAULISTA - EIRELI - EPP<br />
                        CNPJ: 66.494.691/0001-35<br />
                        IE: 438.258.480.116<br />
                        Av. Carlos Artêncio, 420 A - Fragata<br />
                        CEP 17519-255<br />
                        Marília/SP";
            }
            echo "</td>\n";
	    echo "</tr>\n";
	if($login_fabrica != 160 and !$replica_einhell){
		echo "<tr class='table_line3'>\n";
		echo "<td align=\"center\">
			    <B>NF-e DEVERÁ SER FEITO O UPLOAD ATRAVÉS DO SISTEMA TELECONTROL. <br />APENAS NOTAS FISCAIS DE TALÃO DEVERÃO SER ENVIADAS PELOS CORREIOS PARA O ENDEREÇO ABAIXO.</B><BR>
			    ACACIAELETRO PAULISTA - EIRELI - EPP<br />
			    Av. Carlos Artêncio, 420 A - Fragata<br />
			    CEP 17519-255<br />
			    Marília/SP";
		    echo "</td>\n";
		echo "</tr>\n";
	}
    echo "</table>";
    echo "<BR>";
}
if ($login_fabrica == 51 or $login_fabrica == 81 or $telecontrol_distrib) {
    #mostra legenda das OS recusadas
    echo "<table width='300' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
        echo "<tr>";
            echo "<td colspan='2' align='center'><font size='1'><B>Legenda</B></font></td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td align='center' bgcolor='#FFCA99' width='50' heigth='25'>&nbsp;</td>";
            echo "<td align='left'>&nbsp;<font size='1'>";
                echo ($sistema_lingua == 'ES') ? "OS rechazada por el proveedor" : "OS recusada pelo fabricante";
                echo "</font>";
            echo "</td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td align='center' bgcolor='#FFA390' heigth='25'>&nbsp;</td>";
            echo "<td align='left'>&nbsp;<font size='1'>";
                echo ($sistema_lingua == 'ES') ? "Retirada del extracto" : "Retirada do extrato";
                echo "</font>";
            echo "</td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td align='center' bgcolor='#B44747' heigth='25'>&nbsp;</td>";
            echo "<td align='left'>&nbsp;<font size='1'>";
                echo ($sistema_lingua == 'ES') ? "OS excluída por el proveedor" : "OS excluida pelo fabricante";
                echo "</font>";
            echo "</td>";
        echo "</tr>";
    echo "</table>";
    echo "<br />";
    #As OS recusadas estão sendo mostradas numa rotina abaixo para Gama Italy
} else {
    if (in_array($login_fabrica, array(30))) {
        echo "<br /><table width=\"700\" align='center' border=0>";
            echo "<tr style='font-size:12px'>";
                echo "<td bgcolor='#87CEFA'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>".traduz('OS COM CARÊNCIA DE 90 DIAS')."</b></td>";
            echo "</tr>";
        echo "</table>";
            echo "<table width=\"700\" align='center' border=0>";
                echo "<tr style='font-size:12px'>";
                    echo "<td bgcolor='#98FB98'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign='middle' align='left'>&nbsp;<b>".traduz('OS REPROVADA DA AUDITORIA NÚMERO DE SÉRIE / REINCIDÊNCIA')."</b></td>";
                echo "</tr>";
            echo "</table>";
    }
    echo "<br /><center><a href='os_extrato_detalhe_rejeitadas.php?extrato=$extrato&posto=$login_posto'><B>";
    echo ($sistema_lingua == "ES") ? "Haga click aquí para verificar la(s) OS(s) que no entraron en el extracto" : "Clique aqui para verificar a(s) OS(s) que não entraram no Extrato";
    echo "</B></a></center><br />";
}

if ( in_array($login_fabrica, array(11,172)) ) {

    $sql_fabrica_extrato = "SELECT fabrica as fabrica_extrato FROM tbl_extrato where extrato =$extrato";
    $res_fabrica_extrato = pg_query($con, $sql_fabrica_extrato);
    if(pg_num_rows($res_fabrica_extrato)>0){
        $fabrica_extrato = pg_fetch_result($res_fabrica_extrato, 0, fabrica_extrato);
    }

    if($fabrica_extrato == 11){

        ?>

        <div class="box-extrato-informacao">
            <strong>EMITIR NOTA FISCAL:</strong> <br />
            Aulik Industria e Comercio Ltda. <br />
            Rua  João Chagas Ortins Freitas, nº 187 <br />
            Bairro - Buraquinho <br />
            Lauro de Freitas / BA. CEP 42710-610 <br />
            CNPJ: 05.256.426/0001-24 <br />
            INSCR.EST. : 62.942.325 <br /> <br />
            <strong>ENVIAR PARA:</strong> <br />
            Aulik Industria e Comercio Ltda. <br />
            Rua Bela Cintra, 967 - 6 Andar - BELA VISTA <br />
            São Paulo / SP. CEP 01415-905
        </div>

        <?php

    }else{

        ?>

        <div class="box-extrato-informacao">
            <strong>EMITIR NOTA FISCAL:</strong> <br />
            Razão Social: Pacific Indústria e Comércio LTDA <br />
			CNPJ: 11.416.596/0001-21 <br />
            INSCR.EST. : 138286651 <br /> <br />
            Endereço: Rua João Chagas Ortins de Freitas, nº 207 <br />
            Bairro Buraquinho - Lauro de Freitas - BA <br />
            CEP: 42710-610 <br /> <br />
            <strong>ENVIAR PARA:</strong> <br />
            Razão Social: Pacific Indústria e Comércio LTDA <br />
            Rua Bela Cintra, 967 - 6 Andar - BELA VISTA <br />
			São Paulo / SP. CEP 01415-905
		</div>

        <?php

    }

}

if (in_array($login_fabrica, [169,170])) {

    $sqlNfRecebida = "SELECT nf_recebida FROM tbl_extrato WHERE extrato = {$extrato};";
    $resNfRecebida = pg_query($con, $sqlNfRecebida);

    $nfRecebida = 'f';
    if (pg_num_rows($resNfRecebida) > 0) {
        $nfRecebida = pg_fetch_result($resNfRecebida, 0, 'nf_recebida');
    }

    # Notas de serviço só podem ser enviadas do dia 1 ao 20 para a Midea
    $diaMes = (int) date('d');

    if ($nfRecebida != 't' && $diaMes >= 1 && $diaMes <= 20) { ?>

        <form name="frm_retorno_extrato" method="POST" action="<?= $PHP_SELF; ?>" enctype="multipart/form-data">
            <input type="hidden" id="extrato" name="extrato" value="<?= $extrato; ?>" />
            <input type="hidden" id="posto" name="posto" value="<?= $login_posto; ?>" />
            <table width="700px" align="center" border="0" cellspacing="2" cellpadding="1" bgcolor='#D9E2EF' class='formulario'>
                <caption class='titulo_tabela'><? fecho("informar.nota.fiscal", $con, $cook_idioma); ?></caption>
                <tr>
                    <td width='150px'>&nbsp;</td>
                    <td width='200px'>&nbsp;</td>
                    <td width='200px'>&nbsp;</td>
                    <td width='150px'>&nbsp;</td>
                </tr>
                <tr>
                    <td></td>
                    <td><?= traduz("numero.nota.fiscal", $con, $cook_idioma); ?></td>
                    <td><?= traduz("emissao", $con, $cook_idioma); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="text" name="nota_fiscal" id='nota_fiscal' size="18" maxlength="20" value="<?= $nfPosto; ?>" /></td>
                    <td><input type="text" name="emissao" id='emissao' size="10" maxlength="10" value="<?= $dtEmissao; ?>" /></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td><?= traduz("nota.fiscal.(pdf)", $con, $cook_idioma); ?></td>
                    <td><?= traduz("nota.fiscal.(xml)", $con, $cook_idioma); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="file" name="nota_fiscal_pdf" id='nota_fiscal_pdf' /></td>
                    <td><input type="file" name="nota_fiscal_xml" id='nota_fiscal_pdf' /></td>
                    <td></td>
                </tr>
                <tr>
    			    <td colspan='4' style='text-align:center' valign='middle' nowrap>
                        <br />
                        <input type="hidden" name="btn_acao" value="" />
                        <input id="btn_gravar" name="btn_gravar" type="image" src="<?php if($sistema_lingua == 'ES') echo "admin_es/"?>imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_retorno_extrato.btn_acao.value == '' ) { document.frm_retorno_extrato.btn_acao.value = 'gravar_nota' ; document.frm_retorno_extrato.submit(); } else { alert ('Aguarde'); }" alt="<?php fecho("gravar.formulario",$con,$cook_idioma); ?>" border="0" />
                        <input id="btn_limpar" name="btn_limpar" type="image" src="<?php if ($sistema_lingua =='ES') echo "admin_es/";?>imagens/btn_limpar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_retorno_extrato.btn_acao.value == '' ) { document.frm_retorno_extrato.btn_acao.value = 'reset' ; document.frm_retorno_extrato.reset(); window.location='<?= $PHP_SELF; ?>' } else { alert ('Aguarde') }" alt="<?fecho ("limpar.campos", $con, $cook_idioma);?>" border="0" />
                        <br /><br />
                    </td>
                </tr>
            </table>
        </form>
        <br />
    <?php }
}

if(!in_array($login_fabrica,[157,183])){
    if($login_fabrica == 42){
        $camposMakita = "
            tbl_extrato_pagamento.valor_nf_peca,
            tbl_extrato_pagamento.nf_peca,
            tbl_extrato_pagamento.acrescimo_nf_peca,
            tbl_extrato_pagamento.desconto_nf_peca,
            tbl_extrato_pagamento.data_pagamento,
            tbl_extrato_pagamento.valor_liquido,
        ";
    }
    # seleciona dados de OS pagamento
    $sql = "SELECT  tbl_extrato_pagamento.valor_total       ,
                    tbl_extrato_pagamento.acrescimo         ,
                    tbl_extrato_pagamento.desconto          ,
                    tbl_extrato_pagamento.valor_liquido     ,
                    tbl_extrato_pagamento.autorizacao_pagto ,
                    tbl_extrato_pagamento.nf_autorizacao    ,
                    tbl_extrato_pagamento.data_nf    ,
                    tbl_extrato_pagamento.serie_nf    ,
                    to_char(tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento,
                    to_char(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY') AS data_pagamento  ,
                    tbl_extrato_pagamento.obs               ,
		    {$camposMakita}
                    tbl_extrato_pagamento.baixa_extrato
            FROM tbl_extrato_pagamento
            JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
            WHERE   tbl_extrato_pagamento.extrato = $extrato
            AND     tbl_extrato.posto   = $login_posto
            AND     tbl_extrato.fabrica = $login_fabrica
            ORDER BY tbl_extrato_pagamento.extrato_pagamento ASC";
    $res = @pg_query($con,$sql);
    if (@pg_num_rows($res) > 0) {
        if($login_fabrica == 42){
            $vlr_nf_servico     =   number_format(pg_fetch_result($res,$i,valor_total),2,',','.');
            $acrescimo_servico  =   number_format(pg_fetch_result($res,$i,acrescimo),2,',','.');
            $desconto_servico   =   number_format(pg_fetch_result($res,$i,desconto),2,',','.');
            $vlr_nf_pecas       =   number_format(pg_fetch_result($res,$i,valor_nf_peca),2,',','.');
            $nro_nf_pecas       =   pg_fetch_result($res,$i,nf_peca);
            $acrescimo_pecas    =   number_format(pg_fetch_result($res,$i,acrescimo_nf_peca),2,',','.');
            $desconto_pecas     =   number_format(pg_fetch_result($res,$i,desconto_nf_peca),2,',','.');
            $data_pagamento     =   pg_fetch_result($res,$i,data_pagamento);
            $vlr_liquido        =   number_format(pg_fetch_result($res,$i,valor_liquido),2,',','.');
        }   
            $nro_nf_servico     =   pg_fetch_result($res,$i,nf_autorizacao);
            $baixa_extrato      =   pg_fetch_result($res,$i,baixa_extrato);
            $justificativa      =   pg_fetch_result($res,$i,justificativa);

            $colspan = in_array($login_fabrica, [169,170]) ? "6" : "5";

            echo "<TABLE width=\"700\" align='center' border=0>";
            echo "<TR class='menu_top'>";
                echo "<TD align='center' colspan='{$colspan}'>";
                    echo ($sistema_lingua == "ES") ? "DATOS REFERENTES AL PAGO DEL EXTRACTO" : "DADOS REFERENTES AO PAGAMENTO DO EXTRATO";
                echo "</TD>";
            echo "</TR>";
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $ord = $i + 1;
            echo "<TR class='menu_top'>";
                echo "<TD align='center' rowspan='6'>$ord</TD>";
                echo "<TD align='center'>";
                    echo ($sistema_lingua == "ES") ? "Autorización nº" : "Autorização nº";
                echo "</TD>";
                echo "<TD align='center'>";
                    echo ($sistema_lingua == "ES") ? "Fra. Autorización" : "NF autorização";
                echo "</TD>";

                if (in_array($login_fabrica, [169,170])) {
                    echo "<TD align='center'>NF Cadastrada</TD>";
                }

                echo "<TD align='center'>";
                    echo ($sistema_lingua == "ES") ? "Fecha de Autorización" : (in_array($login_fabrica, [169,170])) ? "Data emissão NF" : "Data de autorização";
                echo "</TD>";
                echo "<TD align='center'>";
                    echo ($sistema_lingua == "ES") ? "Fecha de pago" : (($login_fabrica==43) ? "Data prevista de pagamento" : "Data de pagamento");
                echo "</TD>";
            echo "</TR>";
            echo "<TR class='table_line'>";
                echo "<TD style='background-color: #F1F4FA' align='center'>".pg_fetch_result($res,$i,autorizacao_pagto)."</TD>";
                echo "<TD style='background-color: #F1F4FA' align='center'>".pg_fetch_result($res,$i,nf_autorizacao)."</TD>";

                if (in_array($login_fabrica, [169,170])) {

                    $sqlAprovada = "SELECT data FROM tbl_extrato_status
                                    WHERE fabrica = {$login_fabrica}
                                    AND obs = 'Nota Fiscal Aprovada'
                                    AND extrato = {$extrato}
                                    ORDER BY data DESC 
                                    LIMIT 1";
                    $resAprovada = pg_query($con, $sqlAprovada);

                    echo "<TD style='background-color: #F1F4FA' align='center'>".mostra_data_hora(pg_fetch_result($resAprovada, 0, 'data'))."</TD>";

                }

                echo "<TD style='background-color: #F1F4FA' align='center'>".((in_array($login_fabrica, [169,170])) ? pg_fetch_result($res,$i,data_nf) : pg_fetch_result($res,$i,data_vencimento))."</TD>";
                echo "<TD style='background-color: #F1F4FA' align='center'>".pg_fetch_result($res,$i,data_pagamento)."</TD>";
            echo "</TR>";
            echo "<TR class='menu_top'>";

                $colspanTotal = in_array($login_fabrica, [169,170]) ? "2" : "1";

                echo "<TD align='center' colspan='{$colspanTotal}'>";
                    echo ($sistema_lingua == "ES") ? "Valor total" : "Valor total";
                echo "</TD>";
                echo "<TD align='center'>";
                    echo ($sistema_lingua == "ES") ? "Incremento" : "Acréscimo";
                echo "</TD>";
                echo "<TD align='center'>";
                    echo ($sistema_lingua == "ES") ? "Descuento" : "Desconto";
                echo "</TD>";
                echo "<TD align='center'>";
                    echo ($sistema_lingua == "ES") ? "Valor total neto" : "Valor total líquido";
                echo "</TD>";
            echo "</TR>";
            echo "<TR class='table_line'>";
                echo "<TD style='background-color: #F1F4FA' align='center' colspan='{$colspanTotal}'>".number_format(pg_fetch_result($res,$i,valor_total),2,',','.')."</TD>";
                echo "<TD style='background-color: #F1F4FA' align='center'>".number_format(pg_fetch_result($res,$i,acrescimo),2,',','.')."</TD>";
                echo "<TD style='background-color: #F1F4FA' align='center'>".number_format(pg_fetch_result($res,$i,desconto),2,',','.')."</TD>";
                echo "<TD style='background-color: #F1F4FA' align='center'>".number_format(pg_fetch_result($res,$i,valor_liquido),2,',','.')."</TD>";
            echo "</TR>";
            if($login_fabrica == 151){
                $data_nf = pg_fetch_result($res,$i,data_nf);
                if(strlen($data_nf) > 0){
                    list($ano, $mes, $dia) = explode("-", pg_fetch_result($res,$i,data_nf));
                    $data_nf = $dia."/".$mes."/".$ano;
                }
                echo "<TR class='menu_top'>";
                    echo "<TD align='center'>";
                        echo "Série";
                    echo "</TD>";
                    echo "<TD align='center'>";
                        echo "Data de Emissão";
                    echo "</TD>";
                    echo "<td colspan='2'></td>";
                echo "</TR>";
                echo "<TR class='table_line'>";
                    echo "<TD style='background-color: #F1F4FA' align='center'>&nbsp;".pg_fetch_result($res,$i,serie_nf)."</TD>";
                    echo "<TD style='background-color: #F1F4FA' align='center'>&nbsp;".$data_nf."</TD>";
                    echo "<ts style='background-color: #F1F4FA' align='center' colspan='2'></td>";
                echo "</TR>";
            }
            echo "<TR class='menu_top'>";
                echo "<TD align='center' colspan='5'>";
                    echo ($sistema_lingua == "ES") ? "Observación" : "Observações";
                echo "</TD>";
            echo "</TR>";
            echo "<TR class='table_line'>";
                echo "<TD style='background-color: #F1F4FA' align='left' colspan='5'>&nbsp;".pg_fetch_result($res,$i,obs)."</TD>";
            echo "</TR>";
        }

        if (in_array($login_fabrica, [169,170])) { ?>
            <tr class='menu_top'>
                <td align='left' colspan="6" style="text-align: center;">Anexo Nota Fiscal</td>
            </tr>
            <tr class='table_line'>
                <td colspan="6" style="padding-left:8px;text-align: center;">
                    <?php
                    unset($amazonTC, $anexos, $types);
                    $amazonTC = new TDocs($con, $login_fabrica);
                    $amazonTC->setContext("extrato", "nf_autorizacao");
                    $anexo = array();

                    $anexo["nome"] = "nf_autorizacao_{$extrato}_{$login_fabrica}_nota_fiscal_pdf";
                    $anexo["url"] = $amazonTC->getDocumentsByName($anexo["nome"], null , $extrato)->url;
                    if (strlen($anexo["url"]) > 0) { ?>
                        <a href="<?= $anexo['url']; ?>" target="_blank"><img height="90" src="imagens/pdf_transparente.jpg" /></a>
                    <?php } ?>
                </td>
            </tr>
        <?php
        }

        echo "</TABLE>";

        if (in_array($login_fabrica, [169,170])) { 

            $sqlHistorico = "SELECT CASE
                                        WHEN pendente THEN 'Nota Fiscal Reprovada'
                                        WHEN obs = 'NF Enviada' THEN 'NF Enviada'
                                        ELSE 'Nota Fiscal Aprovada'
                                    END as acao,
                                    data,
                                    nome_completo,
                                    obs,
                                    tbl_extrato_status.parametros_adicionais
                             FROM tbl_extrato_status
                             LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_extrato_status.admin_conferiu
                             AND tbl_admin.fabrica = {$login_fabrica}
                             WHERE extrato = {$extrato}";
            $resHistorico = pg_query($con, $sqlHistorico);
            ?>
            <table class="tabela" width="700" cellspacing="1" cellpadding="1" border="0" align="center">
                <thead>
                    <tr class="titulo_tabela">
                        <th colspan="5">Histórico de Ações</th>
                    </tr>
                    <tr class="titulo_coluna">
                        <th>Ação</th>
                        <th>Data</th>
                        <th>Nº Nota Fiscal</th>
                        <th>Mensagem</th>
                        <th>Admin</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (pg_num_rows($resHistorico) == 0) { 

                        ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Nenhum resultado encontrado</td>
                        </tr>
                    <?php
                    }

                    while ($dados = pg_fetch_object($resHistorico)) { 

                        $arrParametrosAdicionais = json_decode($dados->parametros_adicionais, true);

                        ?>
                        <tr>
                            <td style="text-align: center;"><?= $dados->acao ?></td>
                            <td style="text-align: center;"><?= mostra_data_hora($dados->data) ?></td>
                            <td style="text-align: center;"><?= $arrParametrosAdicionais["notaFiscal"] ?></td>
                            <td><?= $dados->obs ?></td>
                            <td><?= $dados->nome_completo ?></td>
                        </tr>
                    <?php
                    } ?>
                </tbody>
            </table>
        <?php
        }
    }
}
if(in_array($login_fabrica,[157,183])){
    $sql = "SELECT  tbl_extrato_pagamento.data_nf    ,
                tbl_extrato_pagamento.nf_peca
            FROM    tbl_extrato_pagamento
            JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
            WHERE   tbl_extrato_pagamento.extrato = $extrato
            AND     tbl_extrato.posto   = $login_posto
            AND     tbl_extrato.fabrica = $login_fabrica
            ORDER BY tbl_extrato_pagamento.extrato_pagamento ASC LIMIT 1";
    $res = pg_query($con,$sql);
    echo "<TABLE width=\"700\" align='center' border=0>";
    echo "<TR class='menu_top'>";
        echo "<TD align='center' colspan='2'>";
            echo ($sistema_lingua == "ES") ? "DATOS REFERENTES AL EXTRACTO" : "DADOS REFERENTES AO EXTRATO";
        echo "</TD>";
    echo "</TR>";
    echo "<TR class='menu_top'>";
        echo "<TD align='center'>";
            echo  "Data da Nota Fiscal de Serviço";
        echo "</TD>";
        echo "<TD align='center'>";
            echo  "Nota Fiscal de Serviço";
        echo "</TD>";
    echo "</TR>";
    if(pg_num_rows($res)>0){
        $data_nf        =   pg_fetch_result($res,$i,data_nf);
        $nro_nf_pecas       =   pg_fetch_result($res,$i,nf_peca);
        $ord = $i + 1;
        echo "<TR class='table_line'>";
            echo "<TD style='background-color: #F1F4FA' align='center'><input type=\"text\" name=\"data_nf_pagto\" id=\"data_nf_pagto\" value=\"".mostra_data(pg_fetch_result($res,$i,data_nf))."\" /></TD>";
            echo "<TD style='background-color: #F1F4FA' align='center'> <input type=\"text\" name=\"nf_autorizacao\" id=\"nf_autorizacao\" value=\"".pg_fetch_result($res,$i,nf_peca)."\" /></TD>";
            echo "</TR>";
        echo "<TR class='table_line' >";
            echo "<input type='hidden' value='{$login_posto}' id='posto_login'>";
            echo "<input type='hidden' value='{$extrato}' id='posto_extrato'>";
            echo "<TD style='background-color: #F1F4FA' align='center' colspan='2' ><input type=\"button\" name=\"salvarnfedatanf\"  value=\"Salvar\"/></TD>";
        echo "</TR>";
    }else{
        echo "<TR class='table_line'>";
            echo "<TD style='background-color: #F1F4FA' align='center'><input type=\"text\" name=\"data_nf_pagto\" id=\"data_nf_pagto\" /></TD>";
            echo "<TD style='background-color: #F1F4FA' align='center'><input type=\"text\" name=\"nf_autorizacao\" id=\"nf_autorizacao\"/></TD>";
        echo "</TR>";
        echo "<TR class='table_line' >";
            echo "<input type='hidden' value='{$login_posto}' id='posto_login'>";
            echo "<input type='hidden' value='{$extrato}' id='posto_extrato'>";
            echo "<TD style='background-color: #F1F4FA' align='center' colspan='2' ><input type=\"button\" name=\"salvarnfedatanf\"  value=\"Salvar\"/></TD>";
        echo "</TR>";
    echo "</TABLE>";
    echo "<br>";
    }
    ?>

    <script type="text/javascript">
        $(function() {
            $("#data_nf_pagto").maskedinput("99/99/9999");
        });
        $("input[name='salvarnfedatanf']").click(function(){
            var data_nf_pagto = $("#data_nf_pagto").val();
            var nf_autorizacao = $("#nf_autorizacao").val();
            var posto_login = $("#posto_login").val();
            var extrato = $("#posto_extrato").val();
            if(data_nf_pagto.length == 0 ){
                alert('Preencher data da nota!');
                return;
            }
            if(nf_autorizacao.length == 0 ){
                alert('Preencher nota fiscal !');
                return;
            }
            $.ajax({
                async: false,
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                data: { salvarnfedatanf: true, dataNotaFiscal: data_nf_pagto, notaFiscal: nf_autorizacao, posto_login:posto_login, extrato:extrato },
                complete: function(data) {
                    data = JSON.parse(data.responseText);
                    if (data.error) {
                        alert(data.error);
                        <?php if ($login_fabrica == 183){ ?>
                            $("#data_nf_pagto").val("");
                        <?php } ?>
                    }
                    if(data.success){
                        alert(data.success);
                    }
                }
            });
        });
    </script>

    <?
}

if ($login_fabrica == 203 AND strtolower($login_tipo_posto_descricao) == "autorizada premium"){
?>
    <table width='700' border='0' cellspacing='1' cellpadding='5' align='center'>
        <tr>
            <td align='left'>
                <span style ='background-color: #93c9a6; padding: 0px 18px 0px 0px;'></span> <p style='margin-left: 5px;'>RECEBIDO VIA CORREIO</p>
            </td>
        </tr>
    </table>
<?php
}

if (!in_array($login_fabrica, array(6,14))) {
    if(isset($novaTelaOs)){
        $join_tbl_produto = " JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto ";
    }else{
        $join_tbl_produto = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";
    }
    $sql = "SELECT  count(distinct tbl_os.os) as qtde,
                    tbl_linha.nome
            FROM   tbl_os
            JOIN   tbl_os_extra  ON tbl_os_extra.os     = tbl_os.os
                                AND tbl_os.fabrica = $login_fabrica
            $join_tbl_produto
            JOIN   tbl_linha     ON tbl_linha.linha     = tbl_produto.linha
                                AND tbl_linha.fabrica   = $login_fabrica
            WHERE  tbl_os_extra.extrato = $extrato ";
    if ($login_fabrica == 45) {
        $sql .= "
        and    tbl_os.mao_de_obra notnull
        and    tbl_os.pecas       notnull
        and    ((SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15)) ";
    }
    $sql .= " GROUP BY tbl_linha.nome ORDER BY count(*)";
    $resx = pg_query($con,$sql);

    if (pg_num_rows($resx) > 0) {
        echo "<TABLE width='700' border='0' cellspacing='1' cellpadding='5' align='center'>";
        echo "<TR class='menu_top'>";
        echo "<TD align='left'>";
            echo ($sistema_lingua == "ES") ? "LÍNEA" : "LINHA";
        echo "</TD>";
        echo "<TD align='center'>";
            echo ($sistema_lingua == "ES") ? "CTD OS" : "QTDE OS";
        echo "</TD>";
        echo "</TR>";
        for ($i = 0 ; $i < pg_num_rows($resx) ; $i++) {
            $cor = ($i % 2 == 0) ? '#F1F4FA' : "#d9e2ef";
            $linha = trim(pg_fetch_result($resx,$i,nome));
            $qtde  = trim(pg_fetch_result($resx,$i,qtde));
            echo "<TR class='table_line' style='background-color: $cor;'>";
            echo "<TD align='left' style='padding-right:5px'>$linha</TD>";
            echo "<TD align='center' style='padding-right:5px'>$qtde</TD>";
            echo "</TR>";
        }
        echo "</TABLE>";
        echo "<br>";
    }
}
# ----------------------------------------- #
# -- VERIFICA SE É POSTO OU DISTRIBUIDOR -- #
# ----------------------------------------- #
$sql = "SELECT  DISTINCT
                tbl_tipo_posto.tipo_posto     ,
                tbl_posto.estado
        FROM    tbl_tipo_posto
        JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                                    AND tbl_posto_fabrica.posto      = $login_posto
                                    AND tbl_posto_fabrica.fabrica    = $login_fabrica
        JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
        WHERE   tbl_tipo_posto.distribuidor IS TRUE
        AND     tbl_posto_fabrica.fabrica = $login_fabrica
        AND     tbl_tipo_posto.fabrica    = $login_fabrica
        AND     tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_query($con,$sql);
$tipo_posto = (pg_num_rows($res) == 0) ? "P" : "D";
$subSelectStatusOS = '';
if (in_array($login_fabrica, array(30))) {
    $subSelectStatusOS = '(SELECT status_os FROM tbl_os_status WHERE os = tbl_os.os ORDER BY os_status DESC LIMIT 1) AS status_os, ';
}
    if($login_fabrica == 74 ){
        $campo_cancelada = " tbl_os.cancelada,  ";
    }
$sql = "SELECT DISTINCT  tbl_os.posto                                                 ,
        tbl_os.sua_os                                                ,
        tbl_os.os                                                    ,
        case when status_os_ultimo = 15 then 0 else tbl_os.mao_de_obra end as mao_de_obra                                         , ";
if (strlen($posto) == 0) $sql .= "tbl_os.mao_de_obra_distribuidor, ";
else                     $sql .= "(tbl_os.mao_de_obra + tbl_familia.mao_de_obra_adicional_distribuidor) AS mao_de_obra_distribuidor, ";
//takashi colocou 020207 HD 1049  tbl_os.tipo_os
if(isset($novaTelaOs)){
    $join_tbl_produto = "JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto ";
}else{
    $join_tbl_produto = "JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";
}
if(in_array($login_fabrica, array(152,180,181,182))) {
    $join_tbl_produto = "LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto ";
}
if($login_fabrica == 145){
    $complemento_join = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto ";
    $complemento_campo = " tbl_produto.descricao as descricao_produto, ";
}

if ($login_fabrica == 203){
    $campo_os_campo_extra = "tbl_os_campo_extra.campos_adicionais::jsonb->>'produto_recebido_via_correios' AS recebido_via_correios ,";
    $join_os_campo_extra = "JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica";
}

if($telecontrol_distrib){
    $complemento_campo = " tbl_extrato.nf_recebida, ";
}

$sql .= "       tbl_os.consumidor_revenda                                        ,
            tbl_os.tipo_os                                                   ,
            tbl_os.pecas                                                     ,
            tbl_os.consumidor_nome                                           ,
            {$campo_os_campo_extra}
            tbl_os.revenda_nome                                              ,
            tbl_os.data_abertura                                             ,
            tbl_os.data_fechamento                                           ,
            tbl_os.tipo_atendimento                                          ,
            to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')  AS data_geracao ,
            to_char(tbl_extrato.liberado,'DD/MM/YYYY')       AS liberado     ,
            tbl_extrato.liberado::date                       AS data_liberado,
            tbl_extrato.data_geracao                         AS geracao      ,
            to_char(tbl_extrato_extra.baixado,'DD/MM/YYYY')  AS baixado      ,
            tbl_os_extra.os_reincidente                                      ,
            tbl_os_extra.obs_adicionais                                      ,
	    tbl_os_extra.valor_total_hora_tecnica                            ,
            round(tbl_os_extra.taxa_visita::numeric, 2)                                         ,
            round(tbl_os_extra.valor_total_deslocamento::numeric, 2)       AS entrega_tecnica                ,
            tbl_extrato.protocolo                                            ,
            tbl_extrato.avulso                                                  ,
            tbl_extrato_extra.obs                                            ,
            $complemento_campo
            $campo_cancelada
            round(tbl_os_extra.mao_de_obra::numeric, 2)                         AS extra_mo     ,
            round(tbl_os_extra.mao_de_obra_desconto::numeric, 2)                AS mao_de_obra_desconto     ,
            round(tbl_os_extra.custo_pecas::numeric, 2)                         AS extra_pecas  ,
            ";
            if (in_array($login_fabrica, array(125))) {
                $sql .= " round(tbl_os.taxa_visita::numeric, 2)                         AS extra_instalacao     ,
                ";
            }else{
                $sql .= " round(tbl_os_extra.taxa_visita::numeric, 2)                         AS extra_instalacao     ,
                ";
            }
            $sql .= " round(tbl_os_extra.deslocamento_km::numeric, 2)                     AS extra_deslocamento,
            round(tbl_os.qtde_km_calculada::numeric,3)                         AS qtde_km_calculada,
            COALESCE(tbl_os.pedagio, 0)                      AS pedagio,
            $subSelectStatusOS
            round(tbl_os.valores_adicionais::numeric, 2) as valores_adicionais
            " .(($login_fabrica == 42) ? ", tbl_produto.referencia AS produto_referencia" : "");

        if(in_array($login_fabrica, [11,172])){
            $sql .= "      
        
            FROM        tbl_os_extra
            JOIN        tbl_os            ON tbl_os.os               = tbl_os_extra.os
             AND        tbl_os.fabrica = $fabrica_extrato
            $join_tbl_produto
            JOIN        tbl_extrato       ON tbl_extrato.extrato     = tbl_os_extra.extrato
             AND        tbl_extrato.fabrica =  $fabrica_extrato 
            JOIN        tbl_extrato_extra ON tbl_extrato.extrato     = tbl_extrato_extra.extrato
            LEFT JOIN   tbl_familia       ON tbl_familia.familia     = tbl_produto.familia
            WHERE       tbl_os_extra.i_fabrica = tbl_os.fabrica
            AND         tbl_os_extra.extrato = $extrato  ";
        }else{
            $sql .= "      
        
            FROM        tbl_os_extra
            JOIN        tbl_os            ON tbl_os.os               = tbl_os_extra.os
             AND        tbl_os.fabrica = $login_fabrica
            $join_tbl_produto
            {$join_os_campo_extra}
            JOIN        tbl_extrato       ON tbl_extrato.extrato     = tbl_os_extra.extrato
             AND        tbl_extrato.fabrica =  $login_fabrica 
            JOIN        tbl_extrato_extra ON tbl_extrato.extrato     = tbl_extrato_extra.extrato
            LEFT JOIN   tbl_familia       ON tbl_familia.familia     = tbl_produto.familia
            WHERE       tbl_os_extra.i_fabrica = tbl_os.fabrica
            AND         tbl_os_extra.extrato = $extrato  ";
        }
if ($login_fabrica == 6 and 1 == 2) {
    $sql .= "AND tbl_os_extra.os_reincidente IS NULL ";
}
if (strlen($posto) == 0) $sql .= "AND tbl_os.posto = $login_posto "; // DISTRIBUIDOR
else                     $sql .= "AND tbl_os.posto = $posto ";       // POSTO
if ($login_fabrica == 45) {//HD 39933 11/9/2008
    $sql .= "   and         tbl_os.mao_de_obra notnull
                and         tbl_os.pecas       notnull
                and     (     (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL
                       OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15)
                    ) ";
}
if ($login_fabrica == 148) {
    $sql_yanmar = $sql." AND tbl_os.tipo_atendimento = 217 ";
    $sql .= " AND tbl_os.tipo_atendimento <> 217 ";
}
if(!in_array($login_fabrica, array(141,142,144,145))){
    $sql .= "ORDER BY   tbl_os.sua_os asc ";
    if ($login_fabrica == 148) {
        $sql_yanmar .= "ORDER BY   lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
                    replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
    }
}
$res = pg_query($con,$sql);
if ($login_fabrica == 148) {
    $res_yanmar = pg_query($con, $sql_yanmar);
    $total_yanmar = pg_num_rows($res_yanmar);
}


$totalRegistros = pg_num_rows($res);
if ($totalRegistros > 0) {
    $ja_baixado    = false ;
    $posto         = pg_fetch_result($res, 0, 'posto');
    $data_geracao  = pg_fetch_result($res, 0, 'data_geracao');
    $liberado      = pg_fetch_result($res, 0, 'liberado');
    $data_liberado = pg_fetch_result($res, 0, 'data_liberado');
    $protocolo     = pg_fetch_result($res, 0, 'protocolo');
    $geracao       = pg_fetch_result($res, 0, 'geracao');
    $mao_de_obra_desconto = pg_fetch_result($res, 0, 'mao_de_obra_desconto');
    $sql = "SELECT  tbl_posto.nome,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_fabrica.parametros_adicionais
            FROM    tbl_posto_fabrica
            JOIN    tbl_posto   ON tbl_posto.posto     = tbl_posto_fabrica.posto
            JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
            WHERE   tbl_posto_fabrica.posto   = $login_posto
            AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
    $resx = pg_query($con,$sql);
    if (pg_num_rows($resx) > 0) {
        $posto_codigo = trim(pg_fetch_result($resx, 0, 'codigo_posto'));
        $posto_nome   = trim(pg_fetch_result($resx, 0, 'nome'));
        // if($login_fabrica == 74){//HD-3141903
        //  $parametros_adicionais = json_decode(pg_fetch_result($resx,0,parametros_adicionais),TRUE);
  //           $valor_km_fixo = $parametros_adicionais['valor_km_fixo'];
  //           if(strlen(trim($valor_km_fixo)) > 0){
  //               $usa_km_fixo = "true";
  //           }
        // }
    }
    if ($login_fabrica == 11) {
        $mes_liberado = substr($liberado,3,2);
        $ano_liberado = substr($liberado,6,4);
        echo "<TABLE align='center'>";
            echo "<tr class='menu_top'>\n";
            $Mes = array("Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");
            echo "<td align=\"center\"><B>Descrição da Nota Fiscal:</b><BR> *Serviços prestados em aparelhos de sua comercialização, sob garantia durante o mês de ".$Mes[$mes_liberado-1]." de ".$ano_liberado.".*";
            echo "</tr>\n";
        echo "</TABLE>";
    }
    if ($login_fabrica == 6) {
        echo "<BR><BR>";
        echo "<table width='300' border='0' align='center'>";
            echo "<TR>";
                echo "<td bgcolor='#e5af8a' width='35'>&nbsp;</td>";
                echo "<td><font size='1'>OS com 'PCI enviada para Tectoy'</font></td>";
            echo "</TR>";
        echo "</table>";
        echo "<BR><BR>";
    }
    if ($login_fabrica == 30) {
        $sqlServ = "select  count(tbl_os.os) as qtde,
                            tbl_os_extra.extrato,
                            tbl_esmaltec_item_servico.esmaltec_item_servico,
                            tbl_esmaltec_item_servico.codigo,
                            tbl_esmaltec_item_servico.descricao,
                            SUM(tbl_os.mao_de_obra) AS valor,
                            tbl_os_extra.mao_de_obra_desconto
                      FROM tbl_os
                      JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                      JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                      JOIN tbl_extrato_extra USING(extrato)
                      JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                      LEFT join tbl_esmaltec_item_servico ON tbl_esmaltec_item_servico.esmaltec_item_servico = tbl_defeito_constatado.esmaltec_item_servico
                     WHERE tbl_os.fabrica=$login_fabrica
                     AND tbl_extrato.extrato = $extrato
                     AND (tbl_os.mao_de_obra > 0 or tbl_os.qtde_km_calculada > 0)
                        GROUP by tbl_os_extra.extrato,
                        tbl_esmaltec_item_servico.esmaltec_item_servico,
                        tbl_esmaltec_item_servico.codigo,
                        tbl_os_extra.mao_de_obra_desconto,
                        tbl_esmaltec_item_servico.descricao ";
            $resServ     = pg_query($con,$sqlServ);
            
            $registros   = pg_num_rows($resServ);
            $valor_total = 0;
            if ($registros > 0) { ?>

                <table align='center' width='700' cellspacing='1'>
                    <caption class='menu_top'><?=  traduz('Itens de Serviço') ?> </caption>
                    <tr class='menu_top'>
                        <?php if ($login_fabrica <> 30 ){?>
                        <th><?= traduz('Código') ?></th>
                        <?php } ?>
                        <th><?= traduz('Descrição') ?></th>
                        <th><?= traduz('Qtde') ?></th>
                        <th><?= traduz('Preço') ?></th>
                        <th><?= traduz('Valor') ?></th>
                    </tr><?php
                    for ($i = 0; $i < $registros; $i++) {
                        $item_servico          = pg_fetch_result($resServ,$i,codigo);
                        $descricao             = pg_fetch_result($resServ,$i,descricao);
                        $qtde                  = pg_fetch_result($resServ,$i,qtde);
                        $valor                 = pg_fetch_result($resServ,$i,valor);
                        $esmaltec_item_servico = pg_fetch_result($resServ,$i,esmaltec_item_servico);
                        $mao_de_obra_desconto  = pg_fetch_result($resServ,$i,mao_de_obra_desconto);
                        $sql = "SELECT COALESCE(
                                                SUM(
                                                    CASE tbl_lancamento.debito_credito
                                                    WHEN 'C' THEN 1
                                                    WHEN 'D' THEN -1
                                                    END * COALESCE(tbl_extrato_lancamento.valor, 0)
                                                ), 0
                                            ) AS total_avulso_item
                                    FROM tbl_lancamento
                                    JOIN tbl_extrato_lancamento ON tbl_lancamento.lancamento=tbl_extrato_lancamento.lancamento
                                    WHERE tbl_extrato_lancamento.extrato     = $extrato
                                        AND tbl_lancamento.esmaltec_item_servico = $esmaltec_item_servico
                                        AND tbl_lancamento.fabrica               = $login_fabrica
                                        AND tbl_extrato_lancamento.fabrica       = $login_fabrica ";
                        $resAvulsoItens = pg_query($con, $sql);
                        $total_avulso_item = pg_fetch_result($resAvulsoItens, 0, 0);
                        $valor += $total_avulso_item;
                        $preco = $valor / $qtde;
                        $valor_total += $valor;
                        $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0"; ?>

                        <tr style='background-color: <?echo $cor; ?>' class='table_line'>
                            <?php if ($login_fabrica <> 30 ){?>
                            <td><? echo $item_servico; ?></td>
                            <?php }?>
                            <td><? echo $descricao; ?></td>
                            <td align='right'><? echo $qtde; ?></td>
                            <td align='right'><? echo number_format($preco,2,',','.'); ?></td>
                            <td align='right'><? echo number_format($valor,2,',','.'); ?></td>
                        </tr><?php
                    }
                $sqlS = "SELECT    tbl_esmaltec_item_servico.codigo,
                                   tbl_esmaltec_item_servico.descricao,
                                   tbl_esmaltec_item_servico.valor
                                 FROM tbl_esmaltec_item_servico
                                WHERE esmaltec_item_servico = 35";
                $resS         = pg_query($con, $sqlS);
                $preco        = pg_fetch_result($resS, 0, 'valor');
                $item_servico = pg_fetch_result($resS, 0, 'codigo');
                $descricao    = pg_fetch_result($resS, 0, 1);
                $sqlPeca = "SELECT tbl_extrato.pecas
                                  FROM tbl_os
                                  JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                                  JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                                  JOIN tbl_extrato_extra USING(extrato)
                                  JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                                WHERE tbl_os.fabrica=$login_fabrica
                                AND tbl_extrato.extrato = $extrato
                                AND tbl_os.pecas > 0
                                GROUP by tbl_os_extra.extrato,tbl_extrato.pecas ";
                $resPeca   = pg_query($con, $sqlPeca);
                $registros = pg_num_rows($resPeca);
                if ($registros > 0) {
                    $qtde = pg_fetch_result($resPeca, 0, 'pecas');
                }else{
                    $qtde = 0;
                }
                $sql = " SELECT SUM(COALESCE(tbl_extrato_lancamento.valor, 0))
                            FROM tbl_lancamento
                            JOIN tbl_extrato_lancamento ON tbl_lancamento.lancamento=tbl_extrato_lancamento.lancamento
                            WHERE tbl_extrato_lancamento.extrato         = $extrato
                                AND tbl_lancamento.esmaltec_item_servico = 35
                                AND tbl_lancamento.fabrica               = $login_fabrica
                                AND tbl_extrato_lancamento.fabrica       = $login_fabrica ";
                $resAvulsoItens = pg_query($con, $sql);
                if((pg_num_rows($resAvulsoItens) > 0 && !empty(pg_fetch_result($resAvulsoItens, 0, 0))) OR $registros > 0){
                    $total_avulso_item = pg_fetch_result($resAvulsoItens, 0, 0);
                    $qtde += $total_avulso_item;
                    $valor = $qtde;
                    $valor_total += $valor;
                    $cor = ($cor == "#F7F5F0") ? "#F1F4FA" : "#F7F5F0";?>

                    <tr style='background-color: <?echo $cor; ?>' class='table_line'>
                        <?php if ($login_fabrica <> 30 ){?>
                            <td><? echo $item_servico; ?></td>
                        <?php }?>
                        <td><? echo $descricao; ?></td>
                        <td align='right'><? echo number_format($qtde,2,',','.'); ?></td>
                        <td align='right'><? echo number_format($preco,2,',','.'); ?></td>
                        <td align='right'><? echo number_format($valor,2,',','.'); ?></td>
                    </tr><?php
                }
                $sqlS = "SELECT    tbl_esmaltec_item_servico.codigo,
                                   tbl_esmaltec_item_servico.descricao,
                                   tbl_esmaltec_item_servico.valor
                                 FROM tbl_esmaltec_item_servico
                                WHERE esmaltec_item_servico = 36";
                $resS = pg_query($con,$sqlS);
                $preco        = pg_fetch_result($resS, 0, 'valor');
                $item_servico = pg_fetch_result($resS, 0, 'codigo');
                $descricao    = pg_fetch_result($resS, 0, 1);

                $sqlPeca = "SELECT tbl_extrato.deslocamento
                                  FROM tbl_os
                                  JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                                  JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                                  JOIN tbl_extrato_extra USING(extrato)
                                  JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                                WHERE tbl_os.fabrica=$login_fabrica
                                AND tbl_extrato.extrato = $extrato
                                AND tbl_os.qtde_km > 0
                                GROUP by tbl_os_extra.extrato,tbl_extrato.deslocamento ";
                $resPeca   = pg_query($con, $sqlPeca);
                $registros = pg_num_rows($resPeca);
                if ($registros > 0) {
                    for ($i = 0; $i < $registros; $i++) {
                        $deslocamento = pg_fetch_result($resPeca, $i, 'deslocamento');
 
                        $sql = "
                        SELECT
                        COALESCE (
                            SUM(
                                CASE
                                WHEN tbl_lancamento.debito_credito = 'C' or tbl_lancamento.debito_credito isnull THEN 1
                                WHEN tbl_lancamento.debito_credito = 'D' and tbl_extrato_lancamento.valor > 0 THEN -1
                                ELSE 1
                                END * COALESCE(tbl_extrato_lancamento.valor, 0)
                            ),
                            0
                        ) AS total_avulso_item
                        FROM
                        tbl_lancamento
                        JOIN tbl_extrato_lancamento ON tbl_lancamento.lancamento=tbl_extrato_lancamento.lancamento
                        WHERE
                        tbl_extrato_lancamento.extrato=$extrato
                        AND tbl_lancamento.esmaltec_item_servico=36
                        AND tbl_lancamento.fabrica=$login_fabrica
                        AND tbl_extrato_lancamento.fabrica=$login_fabrica
                        ";
                        $resAvulsoItens = pg_query($con, $sql);
                        $total_avulso_item = pg_fetch_result($resAvulsoItens, 0, 0);
                        $deslocamento += $total_avulso_item;
                        $qtde = $deslocamento / $preco;
                        $cor = ($cor == "#F7F5F0") ? "#F1F4FA" : "#F7F5F0";?>
                        <tr style='background-color: <?echo $cor; ?>' class='table_line'>
                            <?php if ($login_fabrica <> 30 ){?>
                                <td><? echo $item_servico; ?></td>
                            <?php }?>
                            <td><? echo $descricao; ?></td>
                            <td align='right'><? echo number_format($qtde,2,',','.'); ?></td>
                            <td align='right'><? echo number_format($preco,2,',','.'); ?></td>
                            <td align='right'><? echo number_format($deslocamento,2,',','.'); ?></td>
                        </tr><?php
                    }

                }else{
                    $sqle = "SELECT regexp_replace( regexp_replace(parametros_adicionais, '^.+\"valor_km_fixo\":\"', ''), E'\"(.*)', '') AS valor_km_fixo,
                                    deslocamento
                            FROM    tbl_posto_fabrica
                            JOIN    tbl_extrato USING(posto,fabrica)
                            WHERE   tbl_extrato.fabrica = $login_fabrica
                            AND     extrato = $extrato
                            AND     deslocamento > 0
                            AND     parametros_adicionais ~* 'valor_km_fixo'";
                    $rese = pg_query($con,$sqle);
                    if(pg_num_rows($rese) > 0 ) {
                        $valor_km_fixo = pg_fetch_result($res,0,0);
                        $deslocamento = pg_fetch_result($rese,0,deslocamento);
            ?>
                        <tr style='background-color: <?echo $cor; ?>' class='table_line'>
                            <td><? echo $descricao; ?></td>
                            <td align='right'>0</td>
                            <td align='right'><? echo number_format($preco,2,',','.'); ?></td>
                            <td align='right'><? echo number_format($deslocamento,2,',','.'); ?></td>
                        </tr>
                <?
                    }
                }
                $valor_total += $deslocamento;
                $colspan = ($login_fabrica == 30) ? 3 : 4;
                ?>
                    <tr class='menu_top'>
                        <td colspan='<?php echo $colspan?>'> Total Geral</td>
                        <td align='right'><? echo number_format($valor_total,2,',','.'); ?> </td>
                    </tr>
                </table>
                <br /><?php
            }
        }
        ##### LANÇAMENTO DE AUDITORIA OS FINALIZADA - INÍCIO - MONTEIRO HD-2163607 #####
        if ($login_fabrica == 85) {
            $sqlOS = "
                 SELECT tbl_os_extra.os as os_comentario,
                        tbl_os_extra.obs_adicionais,
                        tbl_os.mao_de_obra,
                        tbl_os.qtde_km_calculada,
                        tbl_os.pedagio,
                        tbl_extrato_lancamento.valor
                   FROM tbl_os_extra
                   JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
              LEFT JOIN tbl_extrato_lancamento ON tbl_os_extra.extrato = tbl_extrato_lancamento.extrato AND tbl_extrato_lancamento.os = tbl_os_extra.os
                  WHERE tbl_os_extra.extrato = $extrato
                    AND tbl_os_extra.obs_adicionais IS NOT NULL
                    AND tbl_os_extra.obs_adicionais <> 'null'
                    AND tbl_os.posto = $login_posto";
            $resOS = pg_query($con, $sqlOS);
            if(pg_num_rows($resOS) > 0){
                $countOS = pg_num_rows($resOS);
                $comentario_os  = trim(pg_fetch_result($resOS, $p, 'obs_adicionais'));
                if (strlen($comentario_os) > 0) {
                    // HD 2416981 - suporte: deixar sinalizado como lançamento avulso, para não
                    // confundir o Posto Autorizado.
                    //LEGENDA
                    echo "
                        <table width='700' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>
                        <tr>
                            <td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>
                            <td style='text-align:left;font-weight:bold'>&nbsp;".traduz("extrato.avulso",$cook_idioma)."</td>
                        </tr>
                        </table>
                        <br>";
                    echo "<table id='tabela_obs_ad' width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
                    echo '<caption class="menu_top">'.traduz('avulsos.lançados.no.extrato', $cook_idioma)."</caption>\n";
                    echo "<thead><tr class='menu_top'>\n";
                    echo "<th width='18%'>";
                    fecho('ordem.de.servico', $cook_idioma);
                    //echo ($sistema_lingua == "ES") ? "DESCRIPCIÓN" : "DESCRIÇÃO";
                    echo "</th>\n";
                    echo "<th>".traduz('historico', $cook_idioma)."</th>\n";
                    echo "<th width='10%'>".traduz('valor', $cook_idioma)."</th>\n";
                    echo "</tr></thead>\n";
                    for ($p=0; $p < $countOS; $p++) {
                        $osExtrato      = pg_fetch_result($resOS, $p, 'os_comentario');
                        $comentario_os  = pg_fetch_result($resOS, $p, 'obs_adicionais');
                        $valor_pedagio  = pg_fetch_result($resOS, $p, 'pedagio');
                        $valor_km       = pg_fetch_result($resOS, $p, 'qtde_km_calculada');
                        $valor_mao_obra = pg_fetch_result($resOS, $p, 'mao_de_obra');
                        $valor_avulso   = pg_fetch_result($resOS, $p, 'valor');
                        $comentario_os  = str_replace('\\u00', '\u00', preg_replace('/u00([0-9a-f]{2})/', "\\u00$1", $comentario_os));
                        $colunaValorComentario = '';
                        $comentario_os = json_decode($comentario_os, true);
                        foreach ($comentario_os as $key => $value) {
                            if(!in_array($key,array('mao_de_obra','km','pedagio','avulso'))){
                                unset($comentario_os[$key]);
                            }
                        }
                        if (count($comentario_os)>1)
                            $spanrows = 'rowspan="'.count($comentario_os).'"';
                        else $spanrows = ' ';
                        // HD 2416981 - suporte: deixar sinalizado como lançamento avulso, para não
                        // confundir o Posto Autorizado.
                        //$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
                        $cor =  '#FFE1E1';
                        foreach ($comentario_os as $key => $value) {
                            $value = utf8_decode($value);
                            switch ($key) {
                            case 'mao_de_obra':
                                $key = "Mão de Obra";
                                $valorComentario = number_format($valor_mao_obra, 2, ',', '.');
                                break;
                            case 'km':
                                $key = "KM";
                                $valorComentario = number_format($valor_km, 2, ',', '.');
                                break;
                            case 'pedagio':
                                $key = "Pedágio";
                                $valorComentario = number_format($valor_pedagio, 2, ',', '.');
                                break;
                            case 'avulso':
                                $key = "Avulso";
                                $valorComentario = number_format($valor_avulso, 2, ',', '.');
                                break;
                            }
                            if (strlen($spanrows)) {
                                echo "<tr class='table_line' style='background-color: $cor;'>\n";
                                echo "<td $spanrows align='right'>$osExtrato</td>";
                                $spanrows = ''; // exclui a primeira TD para o resto do TR
                            } else {
                                echo "<tr class='table_line' style='background-color: $cor;'>";
                            }
                            echo "<td ><p class='servico'><span class='servico'>$key</span>$value</p></td>";
                            echo "<td align='right'>$valorComentario</td>";
                            echo '</tr>';
                        }
                    }
                    echo "</table><p />";
                }
            }
        }
        #####  LANÇAMENTO DE AUDITORIA OS FINALIZADA - FIM HD-2163607 ######
    if ($login_fabrica == 42) {
        $sql_ta_et = "select tbl_tipo_atendimento.entrega_tecnica
                      from tbl_extrato
                      join tbl_os_extra on tbl_os_extra.extrato = tbl_extrato.extrato
                      join tbl_os on tbl_os.os = tbl_os_extra.os
                      join tbl_tipo_atendimento on tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                      where
                      tbl_extrato.fabrica = $login_fabrica
                      and tbl_extrato.extrato = $extrato
                      group by tbl_tipo_atendimento.entrega_tecnica";
        $res_ta_et = pg_query($con, $sql_ta_et);
        $tipo_atendimento_et = pg_fetch_result($res_ta_et, 0, "entrega_tecnica");
        if ($tipo_atendimento_et == "t") {
        echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>
                <tr class='msg_erro' >
                    <td>
                        ".traduz('EMITIR NOTA FISCAL DE SERVIÇO NO VALOR FINAL DO EXTRATO. NÃO DEVE-SE ENVIAR ESTES VALORES JUNTO DO EXTRATO E NOTA FISCAL DE GARANTIAS')."
                    </td>
                </tr>
              </table>";
        }
    }
    

		##### LANÇAMENTO DE AUDITORIA OS FINALIZADA - INÍCIO - MONTEIRO HD-2163607 #####

		if ($login_fabrica == 85) {
			$sqlOS = "
				 SELECT tbl_os_extra.os as os_comentario,
						tbl_os_extra.obs_adicionais,
						tbl_os.mao_de_obra,
						tbl_os.qtde_km_calculada,
						tbl_os.pedagio,
						tbl_extrato_lancamento.valor
				   FROM tbl_os_extra
				   JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
			  LEFT JOIN tbl_extrato_lancamento ON tbl_os_extra.extrato = tbl_extrato_lancamento.extrato AND tbl_extrato_lancamento.os = tbl_os_extra.os
				  WHERE tbl_os_extra.extrato = $extrato
					AND tbl_os_extra.obs_adicionais IS NOT NULL
					AND tbl_os_extra.obs_adicionais <> 'null'
                    AND tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado'
					AND tbl_os.posto = $login_posto";
			$resOS = pg_query($con, $sqlOS);
// echo "OI!!";
			if(pg_num_rows($resOS) > 0){

				$countOS = pg_num_rows($resOS);
				$comentario_os  = trim(pg_fetch_result($resOS, $p, 'obs_adicionais'));

				if (strlen($comentario_os) > 0) {

					// HD 2416981 - suporte: deixar sinalizado como lançamento avulso, para não
					// confundir o Posto Autorizado.
					//LEGENDA
					echo "
						<table width='700' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>
						<tr>
							<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>
							<td style='text-align:left;font-weight:bold'>&nbsp;".traduz("extrato.avulso",$cook_idioma)."</td>
						</tr>
						</table>
						<br>";

					echo "<table id='tabela_obs_ad' width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
					echo '<caption class="menu_top">'.traduz('avulsos.lançados.no.extrato', $cook_idioma)."</caption>\n";
					echo "<thead><tr class='menu_top'>\n";
					echo "<th width='18%'>";
					fecho('ordem.de.servico', $cook_idioma);
					//echo ($sistema_lingua == "ES") ? "DESCRIPCIÓN" : "DESCRIÇÃO";
					echo "</th>\n";
					echo "<th>".traduz('historico', $cook_idioma)."</th>\n";
					echo "<th width='10%'>".traduz('valor', $cook_idioma)."</th>\n";
					echo "</tr></thead>\n";

					for ($p=0; $p < $countOS; $p++) {

						$osExtrato      = pg_fetch_result($resOS, $p, 'os_comentario');
						$comentario_os  = pg_fetch_result($resOS, $p, 'obs_adicionais');
						$valor_pedagio  = pg_fetch_result($resOS, $p, 'pedagio');
						$valor_km       = pg_fetch_result($resOS, $p, 'qtde_km_calculada');
						$valor_mao_obra = pg_fetch_result($resOS, $p, 'mao_de_obra');
						$valor_avulso   = pg_fetch_result($resOS, $p, 'valor');
						$comentario_os  = str_replace('\\u00', '\u00', preg_replace('/u00([0-9a-f]{2})/', "\\u00$1", $comentario_os));

						$colunaValorComentario = '';
						$comentario_os = json_decode($comentario_os, true);

						foreach ($comentario_os as $key => $value) {
							if(!in_array($key,array('mao_de_obra','km','pedagio','avulso'))){
								unset($comentario_os[$key]);
							}
						}

						if (count($comentario_os)>1)
							$spanrows = 'rowspan="'.count($comentario_os).'"';
						else $spanrows = ' ';

						// HD 2416981 - suporte: deixar sinalizado como lançamento avulso, para não
						// confundir o Posto Autorizado.
						//$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
						$cor =  '#FFE1E1';
						foreach ($comentario_os as $key => $value) {
							$value = utf8_decode($value);
							switch ($key) {
							case 'mao_de_obra':
								$key = "Mão de Obra";
								$valorComentario = number_format($valor_mao_obra, 2, ',', '.');
								break;
							case 'km':
								$key = "KM";
								$valorComentario = number_format($valor_km, 2, ',', '.');
								break;
							case 'pedagio':
								$key = "Pedágio";
								$valorComentario = number_format($valor_pedagio, 2, ',', '.');
								break;
							case 'avulso':
								$key = "Avulso";
								$valorComentario = number_format($valor_avulso, 2, ',', '.');
								break;
							}

							if (strlen($spanrows)) {
								echo "<tr class='table_line' style='background-color: $cor;'>\n";
								echo "<td $spanrows align='right'>$osExtrato</td>";
								$spanrows = ''; // exclui a primeira TD para o resto do TR
							} else {
								echo "<tr class='table_line' style='background-color: $cor;'>";
							}
							echo "<td ><p class='servico'><span class='servico'>$key</span>$value</p></td>";
							echo "<td align='right'>$valorComentario</td>";
							echo '</tr>';
						}
					}
					echo "</table><p />";
				}
			}
		}
		#####  LANÇAMENTO DE AUDITORIA OS FINALIZADA - FIM HD-2163607 ######

	if ($login_fabrica == 42) {

		$sql_ta_et = "select tbl_tipo_atendimento.entrega_tecnica
					  from tbl_extrato
					  join tbl_os_extra on tbl_os_extra.extrato = tbl_extrato.extrato
					  join tbl_os on tbl_os.os = tbl_os_extra.os
					  join tbl_tipo_atendimento on tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
					  where
					  tbl_extrato.fabrica = $login_fabrica
					  and tbl_extrato.extrato = $extrato
					  group by tbl_tipo_atendimento.entrega_tecnica";
		$res_ta_et = pg_query($con, $sql_ta_et);

		$tipo_atendimento_et = pg_fetch_result($res_ta_et, 0, "entrega_tecnica");

		if ($tipo_atendimento_et == "t") {

		echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>
				<tr class='msg_erro' >
				    <td>
				       	".traduz('EMITIR NOTA FISCAL DE SERVIÇO NO VALOR FINAL DO EXTRATO. NÃO DEVE-SE ENVIAR ESTES VALORES JUNTO DO EXTRATO E NOTA FISCAL DE GARANTIAS')."
				    </td>
				</tr>
			  </table>";
		}

	}

	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='5'>\n";
	echo "<tr class='menu_top'>";

	echo "<td colspan='";
	echo (in_array($login_fabrica,array(19,35,72,15,90,85,87,120,201,125,142,145))) ? '100%' : '11';
	echo "' align='center'>";
    fecho('extrato', $cook_idioma);
	echo ($login_fabrica == 1 or $login_fabrica==19) ? ": ".$protocolo : ": ".$extrato;
	if ($login_fabrica == 11) echo " LIBERADO EM $liberado <br> $posto_codigo - $posto_nome";
	else {
		echo ($sistema_lingua == "ES") ? " GENERADO EN " : " GERADO EM ";
		echo "$data_geracao <br> $posto_codigo - $posto_nome";
	}
	if ($login_fabrica == 158) {
		echo "<br />Tipo: $protocolo";
	}
	echo "</td>";

	echo "</tr>";
	echo "<tr class='menu_top'>\n";

		//Igor incluiu 28/03/2007 - HD: 1683
		if ($login_fabrica == 19) echo "<td align='center'>#</td>";

		echo "<td align='center' width='17%' >OS</td>\n";
		if($login_fabrica==11 OR $login_fabrica == 126){
			echo "<td align='center' width='60' >Anexos</td>\n";
		}
		//O penha faz upload de OS e não aparece a sua os na tela de upload
		//Por enqto será mostrada a OS WEB que é a do nosso banco.
		if ($login_posto == 1537) echo "<td align='center' width='17%'>OS WEB</td>\n";

		if ($login_fabrica == 20) {
			echo "<td align='center'>";
				echo ($sistema_lingua == "ES") ? "TIPO DE ATENDIMIENTO" : "TIPO ATENDIMENTO";
			echo "</td>\n";
		}

		echo "<td align='center'>";
			echo ($sistema_lingua == "ES") ? "CONSUMIDOR" : "CLIENTE";
		echo "</td>\n";

		if (in_array($login_fabrica, array(42,145))) {
			echo "<td align='center'>";
				echo "PRODUTO";
			echo "</td>\n";
		}

		if (in_array($login_fabrica, array(169,170))) {
			echo "<td align='center'>".traduz('Valor OS SAP')."</td>\n";
		} else if ($login_fabrica == 6) {
            if (strlen($liberado) > 0) {
                echo "<td align='center'>MO</td>\n";
                echo "<td align='center'>MO REVENDA</td>\n";
                echo "<td align='center'>PEÇAS</td>\n";
                echo "<td align='center'>PEÇAS REVENDA</td>\n";
            }
        } else if ($login_fabrica == 19) {
            echo "<td align='center'>MO</td>\n";
            echo "<td align='center'>".traduz('PEÇAS')."</td>\n";
            echo "<td align='center'>".traduz('INSTALAÇÃO')."</td>\n";
            echo "<td align='center'>".traduz('DESLOCAMENTO')."</td>\n";
            echo "<td align='center'>".traduz('TOTAL')."</td>\n";
        } else if (in_array($login_fabrica,array(50,94,115,116,125,128,129,131,140)) || isset($novaTelaOs)) {
            if (isset($novaTelaOs)) {
                $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
            }

            if (!in_array($login_fabrica, array(143))) {
                echo "<td colspan=2 align='center'>MO</td>\n";
            }
            if($login_fabrica == 128) echo "<td colspan='2'>".traduz('Visita Técnica')."</td>";
            if (!isset($novaTelaOs) || (isset($novaTelaOs) && !$nao_calcula_km)) {
                echo "<td colspan=2 align='center'>KM</td>\n";
            }
            if(in_array($login_fabrica, array(125))){
                echo "<td colspan=2 align='center'>".traduz('Taxa Visita')."</td>\n";
            }
            if (isset($novaTelaOs) && !$nao_calcula_peca && !in_array($login_fabrica,array(191,193))) {
                echo "<td colspan=2 align='center'>".traduz('Peças')."</td>\n";
            }

        } else if (in_array($login_fabrica, array(52,85))) {

            echo "<td colspan=2 align='center'>MO</td>\n";
            if ($login_fabrica == 85) {
                echo "<td colspan=2 align='center'>".traduz('Bonificação')."</td>\n";
            }
            echo "<td colspan=2 align='center'>".traduz('Pedágio')."</td>\n";
            echo "<td colspan=2 align='center'>KM</td>\n";

        } else if (in_array($login_fabrica,array(30,35,72,90,91,15,74,120,201))) {

            echo "<td colspan=2 align='center'>MO</td>\n";
            echo "<td colspan=2 align='center'>".traduz('PEÇAS')."</td>\n";
            echo "<td colspan=2 align='center'>KM</td>\n";

			/*if ($login_fabrica == 30) {
				echo "<td colspan=2 align='center'>Taxa de Entrega</td>\n";
			}*/

            if($login_fabrica == 74){
                echo "<td colspan=2 align='center'>".traduz('Situação')."</td>\n";
                echo "<td colspan=2 align='center'>".traduz('Observação')."</td>\n";
            }

            echo ($login_fabrica == 90) ? "<td align='center'>VISITA</td>\n":"";

        } else if ($login_fabrica == 24 && $login_tipo_posto == 256) {

            echo "<td colspan=2 align='center'>MO</td>\n";
            echo "<td colspan=2 align='center'>KM</td>\n";

        } else if ($login_fabrica == 87) {

            echo '
                <td>Tipo OS</td>
                <td>Qtde Horas</td>
                <td>Valor/Hora</td>
                <td colspan=2 align="center">MO</td>
                <td colspan=2 align="center">KM</td>
            ';

        } else {
            echo "<td colspan=2 align='center'>MO</td>\n";

            if ($login_fabrica == 134) {
                echo "<TD width='130'>".traduz('Total Peças')."</TD>";
                echo "<TD width='130'>Total MO + Peças</TD>";
            }


            if (!in_array($login_fabrica,array(11,51,81,99,101,106,108,111,121,123,124,125,127,128,134,136,139))) {
                echo "<td colspan=2 align='center'>";
                    echo ($sistema_lingua == "ES") ? "PIEZAS" : "PEÇAS";
                echo "</td>\n";
            }
            if ($login_fabrica == 42 && $prestacao_servico == 't') {
?>
                <td>TAXA ADM.</td>
<?php
            }
        }

        if($login_fabrica == 140){
            echo "<td>Entrega T&eacute;cnica</td>";
        }

        if (!in_array($login_fabrica, array(169,170)) && ($inf_valores_adicionais || in_array($login_fabrica, array(142,145)) || isset($fabrica_usa_valor_adicional))) {
            echo "<td>".traduz('Valor Adicional')."</td>";
        }

        if($login_fabrica == 145){
            echo "<td>".traduz('Total')."</td>";
            echo "<td>".traduz('Tipo Atendimento')."</td>";
        }

        if (in_array($login_fabrica, array(51,81))) {
            echo "<td colspan=1 align='center'>".traduz('Observação')."</td>\n";
        }

    echo "</tr>\n";

    $total                     = 0;
    $total_mao_de_obra         = 0;
    $total_pedagio             = 0;
    $total_mao_de_obra_revenda = 0;
    $total_pecas               = 0;
    $total_pecas_revenda       = 0;
    $total_km                  = 0;
    $total_bonificacao         = 0;

    $total_extra_mo		       = 0;
    $total_extra_pecas         = 0;
    $total_extra_instalacao    = 0;
    $total_extra_deslocamento  = 0;
    $total_extra_total         = 0;
    $total_visita              = 0;

    for ($i = 0 ; $i < $totalRegistros; $i++) {

        $os                       = trim(pg_fetch_result($res, $i, 'os'));
        if($telecontrol_distrib){
            $nf_recebida              = trim(pg_fetch_result($res, $i, 'nf_recebida'));
        }
        $sua_os                   = trim(pg_fetch_result($res, $i, 'sua_os'));
        $mo                       = trim(pg_fetch_result($res, $i, 'mao_de_obra'));
        $valor_total_hora_tecnica = trim(pg_fetch_result($res, $i, 'valor_total_hora_tecnica'));
        $avulso                   = trim(pg_fetch_result($res, $i, 'avulso'));
        $mao_de_obra_distribuidor = trim(pg_fetch_result($res, $i, 'mao_de_obra_distribuidor'));
        $pecas                    = trim(pg_fetch_result($res, $i, 'pecas'));
        $qtde_km_calculada        = trim(pg_fetch_result($res, $i, 'qtde_km_calculada'));
        $pedagio                  = trim(pg_fetch_result($res, $i, 'pedagio'));
        $consumidor_nome          = strtoupper(trim(pg_fetch_result($res, $i, 'consumidor_nome')));
        $produto_descricao        = trim(pg_fetch_result($res, $i, 'descricao_produto'));
        $consumidor_str           = substr($consumidor_nome, 0, 23);
        $data_abertura            = trim(pg_fetch_result($res, $i, 'data_abertura'));
        $data_fechamento          = trim(pg_fetch_result($res, $i, 'data_fechamento'));
        $baixado                  = trim(pg_fetch_result($res, 0, 'baixado'));
        $obs                      = trim(pg_fetch_result($res, 0, 'obs'));
        $consumidor_revenda       = trim(pg_fetch_result($res, $i, 'consumidor_revenda'));
        $tipo_atendimento         = trim(pg_fetch_result($res, $i, 'tipo_atendimento'));
        $revenda_nome             = trim(pg_fetch_result($res, $i, 'revenda_nome'));
        $tipo_os                  = trim(pg_fetch_result($res, $i, 'tipo_os'));//takashi colocou 020207 HD 1049
        $taxa_visita              = trim(pg_fetch_result($res, $i, 'extra_instalacao'));
        $entrega_tecnica          = trim(pg_fetch_result($res, $i, 'entrega_tecnica'));
        $valores_adicionais       = trim(pg_fetch_result($res, $i, 'valores_adicionais'));
        $comentarios              = pg_fetch_result($res, $i, 'obs_adicionais');
        $valor_total_hora_tecnica = pg_fetch_result($res, $i, 'valor_total_hora_tecnica');
        $mao_de_obra_desconto	  = pg_fetch_result($res, $i, 'mao_de_obra_desconto');
        $statusOs 		= trim(pg_fetch_result($res, $i, status_os)); // Regras para auditoria de NS e Reincidência HD 2539696

        if ($login_fabrica == 42) {
            $produto_referencia  = pg_fetch_result($res, $i, "produto_referencia");
            $taxa_administrativa = pg_fetch_result($res, $i, custo_peca);
            $pecas = pg_fetch_result($res, $i, custo_peca);
        }

        if ($login_fabrica == 203){
            $recebido_via_correios = pg_fetch_result($res, $i, 'recebido_via_correios');
        }

        $valores_adicionais = ($valores_adicionais) ? $valores_adicionais : 0;

		if(in_array($login_fabrica,array(30, 128,183))) $total_adicional += $valores_adicionais;

        if ($login_fabrica == 19) {

            $extra_mo           = trim(pg_fetch_result($res, $i, 'extra_mo'));
            $extra_pecas        = trim(pg_fetch_result($res, $i, 'extra_pecas'));
            $extra_instalacao   = trim(pg_fetch_result($res, $i, 'extra_instalacao'));
            $extra_deslocamento = trim(pg_fetch_result($res, $i, 'extra_deslocamento'));

            $extra_total = $extra_mo + $extra_pecas + $extra_instalacao + $extra_deslocamento;

            $total_extra_mo           += $extra_mo;
            $total_extra_pecas        += $extra_pecas;
            $total_extra_instalacao   += $extra_instalacao;
            $total_extra_deslocamento += $extra_deslocamento;
            $total_extra_total        += $extra_total;

        }

        if ($consumidor_revenda == "R" and $login_fabrica == 6) {
            $consumidor_str = $revenda_nome;
        }

        if(empty($qtde_km_calculada)){
            $qtde_km_calculada = 0;
        }

        $total_km += $qtde_km_calculada;

        if (strlen($baixado) > 0) $ja_baixado = true ;

        if($login_fabrica == 24) {
            $pecas = 0 ;
        }

        if($login_fabrica == 74) {
            $justificativa_canceladas = "";
            $descricao_cancelada = "";
            $cancelada = pg_fetch_result($res, $i, "cancelada");

            if($cancelada == 't'){
            $descricao_cancelada = "Cancelada";
            $sql_obs_canceladas = "SELECT observacao
                                   from tbl_os_status
                                   where os = $os
                                   and status_os = 156
                                   and fabrica_status = $login_fabrica";
            $res_obs_canceladas = pg_query($con, $sql_obs_canceladas);

            $justificativa_canceladas = pg_fetch_result($res_obs_canceladas, 0, "observacao");
            }
        }


        # soma valores
        if ($login_fabrica == 6) {

            if ($consumidor_revenda == 'R') {

                $mao_de_obra         = '0,00';
                $mao_de_obra_revenda = $mo;
                $pecas_posto         = '0,00';
                $pecas_revenda       = $pecas;

                if ($tipo_posto == "P") $total_mao_de_obra_revenda += $mao_de_obra_revenda;
                else                    $total_mao_de_obra_revenda += $mao_de_obra_distribuidor;
            } else {

                $mao_de_obra         = $mo;
                $mao_de_obra_revenda = '0,00';
                $pecas_posto         = $pecas;
                $pecas_revenda       = '0,00';

                if ($tipo_posto == "P") $total_mao_de_obra += $mo;
                else                    $total_mao_de_obra += $mao_de_obra_distribuidor;
            }

            $total_pecas         += $pecas_posto;
            $total_pecas_revenda += $pecas_revenda;

        } else {

            if ($login_fabrica == 80){
                $mao_de_obra_desconto = trim(pg_fetch_result($res,$i,'mao_de_obra_desconto'));
                $mo = ( $mao_de_obra_desconto > 0 and strlen($mao_de_obra_desconto)>0 ) ? $mo - $mao_de_obra_desconto : $mo;
            }
            $total_mao_de_obra  += $mo;
            $total_pedagio      += $pedagio;
            $mao_de_obra         = $mo;
            $pecas_posto         = (in_array($login_fabrica,[40,90,120,201])) ? 0 : $pecas;
            $total_pecas        += (in_array($login_fabrica,[40,90,120,201])) ? 0 : $pecas;
            $total_visita       += ($login_fabrica == 90) ? $taxa_visita : 0;
            $total_taxa_visita       += ($login_fabrica == 125) ? $taxa_visita : 0;

        }

        $cor = ($i % 2 == 0) ? '#F1F4FA' : '#d9e2ef';
        $btn = ($i % 2 == 0) ? 'azul' : 'amarelo';

        if (in_array($login_fabrica, array(30)) && in_array($statusOs, array(104,190))) {
            $cor = '#98FB98';
        }

        if (strstr($matriz, ";" . $i . ";")) {
            $cor = '#E49494';
        }

        //takashi colocou 020207 HD 1049
        if ($login_fabrica == 6 and $tipo_os == '8') {
            $cor='#e5af8a';
        }


        if((!empty($mao_de_obra_desconto)) and (empty($mao_de_obra)) and $login_fabrica == 30) {
            $cor90 = "#87CEFA";
        }else{
            $cor90 = "";
        }

        if ($login_fabrica == 203 AND strtolower($login_tipo_posto_descricao) == "autorizada premium" AND $recebido_via_correios == "t"){
            $cor = "#93c9a6";
        }


        echo "<tr class='table_line' style='background-color: $cor; background-color: $cor90; '>\n";

        //Igor incluiu 28/03/2007 - HD: 1683
        if ($login_fabrica == 19) echo "<td align='center'>".($i+1)."</td>";

        echo "<td align='center' style='white-space : nowrap'><acronym title=\"Abertura: $data_abertura | Fechamento: $data_fechamento \"><a href=\"os_press.php?os=$os\" target='_blank'><font color='#000000'>";
        if ($login_fabrica == 1) echo $posto_codigo;
        echo "$sua_os</font></a></acronym>";
        echo    "</td>\n";
        if (in_array($login_fabrica, array(11,126))) {
            //verifica anexos os_item
            $prefix_os_item = "anexo_os_item_{$login_fabrica}_{$os}_img_os_item_";

            $s3->getObjectList($prefix_os_item, "false","","");
            $anexos_os_item = $s3->files;

            //verifica anexos os_cadastro
            $prefix_os_cadastro = "anexo_os_{$login_fabrica}_{$os}_img_os_";
            $s3->getObjectList($prefix_os_cadastro, "false","","");
            $anexos_os = $s3->files;
            echo "<td align='center'>";
            echo "<a id='anexar_img_os' rel='$os' target='_blank' href='visualiza_anexos_os.php?os=$os'> <img src='imagens/clips.gif' title='Anexar Imagem na OS'/> </a>";

            echo "</td>";
        }
        if ($login_posto == 1537) echo "<td align='center'><font color='#000000'>$os</font>";

        if (in_array($login_fabrica, array(20,145))) {

            if ($tipo_atendimento > 0) {

                $sql2 = "SELECT descricao FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tipo_atendimento AND fabrica = $login_fabrica;";

                if ($sistema_lingua == "ES") {
                    $sql2 = "SELECT descricao FROM tbl_tipo_atendimento_idioma WHERE idioma = 'ES' AND tipo_atendimento = $tipo_atendimento";
                }

                $res2      = pg_query($con, $sql2);
                $descricao = (pg_num_rows($res2) == 1) ? pg_fetch_result($res2,0,descricao) : "Não Consta";

                if($login_fabrica == 145){
                    $tipo_atendimento_fabrimar = pg_fetch_result($res2,0,descricao);
                }

            }
            if($login_fabrica != 145){
            echo "<td align='left'>$descricao</td>\n";
            }

        }

        echo "<td align='left' nowrap>";
            echo ($login_fabrica == 5 and strlen(trim($consumidor_nome)) == 0) ? $revenda_nome : "<acronym title=\"$consumidor\">$consumidor_str</acronym>";
        echo "</td>\n";

        if($login_fabrica == 145){
            echo "<td align='left' nowrap> $produto_descricao </td>";
        }

        if ($login_fabrica == 42) {
            echo "<td align='left'>";
                echo $produto_referencia;
            echo "</td>\n";
        }

        if (in_array($login_fabrica, array(169,170))) {
			echo "<td align='right' style='padding-right:5px'> " . number_format($valor_total_hora_tecnica,2,",",".") . "</td>\n";
		} else if ($login_fabrica == 6) {
            if (strlen($liberado) > 0) {
                if ($tipo_posto == "P") {
                    echo "<td align='right' style='padding-right:5px'> " . number_format($mao_de_obra,2,",",".") . "</td>\n";
                    echo "<td align='right' style='padding-right:5px'> " . number_format($mao_de_obra_revenda,2,",",".") . "</td>\n";
                } else {
                    echo "<td align='right' style='padding-right:5px'> " . number_format($mao_de_obra_distribuidor,2,",",".") . "</td>\n";
                    echo "<td align='right' style='padding-right:5px'> " . number_format($mao_de_obra_revenda,2,",",".") . "</td>\n";
                }
            }
            if (strlen($liberado) > 0) {
                echo "<td align='right' style='padding-right:5px'> " . number_format($pecas_posto,2,",",".") . "</td>\n";
                echo "<td align='right' style='padding-right:5px'> " . number_format($pecas_revenda,2,",",".") . "</td>\n";
            }
        } else if ($login_fabrica == 19) {
            echo "<td align='right' style='padding-right:5px'> " . number_format($extra_mo,2,",",".") . "</td>\n";
            echo "<td align='right' style='padding-right:5px'> " . number_format($extra_pecas,2,",",".") . "</td>\n";
            echo "<td align='right' style='padding-right:5px'> " . number_format($extra_instalacao,2,",",".") . "</td>\n";
            echo "<td align='right' style='padding-right:5px'> " . number_format($extra_deslocamento,2,",",".") . "</td>\n";
            echo "<td align='right' style='padding-right:5px'> " . number_format($extra_total,2,",",".") . "</td>\n";
        } else if (in_array($login_fabrica,array(50,94,115,116,125,128,129,131,140)) || isset($novaTelaOs)) {
            if (isset($novaTelaOs)) {
                $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
            }

            $mao_de_obra = (empty($mao_de_obra)) ? 0 : $mao_de_obra;
            $qtde_km_calculada = (empty($qtde_km_calculada)) ? 0 : $qtde_km_calculada;

            if (!in_array($login_fabrica, array(143))) {
                echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($mao_de_obra,2,",",".") . "</td>\n";
            }

            if($login_fabrica == 128) echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($valores_adicionais,2,",",".") . "</td>\n";

            if (!isset($novaTelaOs) || (isset($novaTelaOs) && !$nao_calcula_km)) {
                echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($qtde_km_calculada,2,",",".") . "</td>\n";
            }

            if(in_array($login_fabrica, array(125))){
                $taxa_visita = (strlen($taxa_visita) ==0) ? "0" : $taxa_visita;
                echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($taxa_visita,2,",",".") . "</td>\n";
            }

            if (isset($novaTelaOs) && !$nao_calcula_peca && !in_array($login_fabrica,array(191,193))) {
                echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($pecas,2,",",".") . "</td>\n";
            }

            if($login_fabrica == 140){
                echo "<td align='right'>".number_format ($entrega_tecnica,2,",",".") . "</td>\n";
                $valor_adicional_total += $entrega_tecnica;
            }
        } else if (in_array($login_fabrica, array(52,85))){

            echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($mao_de_obra,2,",",".") . "</td>\n";
            if ($login_fabrica == 85) {
                $sqlValorBonificacao = "
                    SELECT  tbl_extrato_lancamento.valor
                    FROM    tbl_extrato_lancamento
                    WHERE   fabrica = $login_fabrica
                    AND     os = $os
                    AND     descricao ILIKE '%diferenciado%'
                    LIMIT   1
                ";
                $resValorBonificacao = pg_query($con,$sqlValorBonificacao);
                $valor_adicional = pg_fetch_result($resValorBonificacao,0,valor);

                $total_bonificacao += $valor_adicional;
                echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($valor_adicional,2,",",".") . "</td>\n";
            }
            echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($pedagio,2,",",".") . "</td>\n";
            echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($qtde_km_calculada,2,",",".") . "</td>\n";

        } else if (in_array($login_fabrica,array(30,35,72,90,91,15,74,120,201))){

            echo "<td colspan=2  align='right' style='padding-right:5px'>" . number_format($mao_de_obra,2,",",".") . "</td>\n";
            echo "<td colspan=2 align='right' style='padding-right:5px'>"  . number_format($pecas_posto,2,",",".") . "</td>\n";
            echo "<td colspan=2  align='right' style='padding-right:5px'>" . number_format($qtde_km_calculada,2,",",".") . "</td>\n";
			/*if ($login_fabrica == 30) {
				echo "<td colspan=2  align='right' style='padding-right:5px'>" . number_format($valores_adicionais,2,",",".")."</td>";
			}*/
            if($login_fabrica == 74){
                echo "<td  align='right' style='padding-right:5px'>$descricao_cancelada</td>\n";
                echo "<td colspan=2  align='right' style='padding-right:5px'>" . $justificativa_canceladas . "</td>\n";

            }

            echo ($login_fabrica == 90) ?"<td align='right' style='padding-right:5px'> " . number_format ($taxa_visita,2,",",".") . "</td>\n":"";

        } else if ($login_fabrica == 24) {
            echo "<td colspan=2  align='right' style='padding-right:5px'>" . number_format($mao_de_obra,2,",",".") . "</td>\n";
            if ($login_tipo_posto == 256) {
                echo "<td colspan=2  align='right' style='padding-right:5px'>" . number_format($qtde_km_calculada,2,",",".") . "</td>\n";
            }
        } else if (in_array($login_fabrica, array(87))) {

            $sql2 = "
                SELECT
                    tbl_tipo_atendimento.descricao,
                    tbl_os_extra.qtde_horas_atendimento,
                    tbl_os_extra.valor_por_hora
                FROM tbl_os
                JOIN tbl_os_extra USING(os)
                JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                WHERE tbl_os.os = {$os}
                AND tbl_os.fabrica = {$login_fabrica};
            ";

            $res2 = pg_query($con,$sql2);

            echo '<td nowrap>'. @pg_fetch_result($res2,0,0) .'</td>
                  <td align="center">'. @pg_fetch_result($res2,0,1) .'</td>
                  <td align="right">'. number_format( @pg_fetch_result($res2,0,2),2,',','.' ).'</td>';
            echo "<td colspan=2  align='right' style='padding-right:5px'>" . number_format($mao_de_obra,2,",",".") . "</td>\n";
            echo "<td colspan=2  align='right' style='padding-right:5px'>" . number_format($qtde_km_calculada,2,",",".") . "</td>\n";
        } else {

            echo "<td colspan=2";
                echo (in_array($login_fabrica, array(51,81,106,108,111))) ? " align='center'" : " align='right' style='padding-right:5px'";
            echo " > " . number_format ($mao_de_obra,2,",",".") . "</td>\n";

            if ($login_fabrica == 134) { ?>
                <td align="right"><?= number_format ($pecas,2,",",".") ?></td>
                <td align="right"><?= number_format ($mao_de_obra + $pecas,2,",",".")  ?></td>
            <?php
            }

            if (!in_array($login_fabrica,array(11,51,81,99,101,106,108,111,121,123,124,125,127,128,134,136,139))) {//HD 416354
                echo "<td colspan=2";
            echo (in_array($login_fabrica, array(51,81))) ? " align='center'" : " align='right' style='padding-right:5px'";
                echo " > " . number_format ($pecas_posto,2,",",".") . "</td>\n";
            }

            if ($login_fabrica == 42 && $prestacao_servico == 't') {
                $total_taxa += $taxa_administrativa;
?>
                <td style="text-align:right;padding-right:5px;"><?=number_format($taxa_administrativa,2,',','.')?></td>
<?php
			}

		}

		if (!in_array($login_fabrica, array(169,170)) && ($inf_valores_adicionais || in_array($login_fabrica, array(142,145)) || isset($fabrica_usa_valor_adicional))) {
			echo "<td align='right'>".number_format ($valores_adicionais,2,",",".") . "</td>\n";
			$valor_adicional_total += $valores_adicionais;
		}

		if ($login_fabrica == 145) {
			$total_fabrimar = $valores_adicionais + $mao_de_obra + $qtde_km_calculada + $pecas;
			echo "<td align='right'>".number_format ($total_fabrimar,2,",",".")."</td>";
			echo "<td align='right'>".$tipo_atendimento_fabrimar."</td>";
		}

		if (in_array($login_fabrica, array(51,81))) {

			$sql_reincidente = "SELECT os_reincidente FROM tbl_os_extra WHERE os = {$os} AND os_reincidente IS NOT NULL;";
			$res_reincidente = pg_query($con,$sql_reincidente);
			$registros_reincidente = pg_num_rows($res_reincidente);
			if ($registros_reincidente > 0) {
				echo "<td colspan=1 align='left' style='padding-right:5px'>OS reincidente.</td>\n";
			}else{
				echo "<td colspan=1 align='right' style='padding-right:5px'>&nbsp;</td>\n";
			}

		}

		if ($login_fabrica == 142) {
			$total_extra_total += ($qtde_km_calculada + $mao_de_obra + $valores_adicionais);
		} else if ($login_fabrica == 145) {
			$total_extra_total += ($qtde_km_calculada + $mao_de_obra + $valores_adicionais + $pecas);
		}
		echo "</tr>\n";

        if ($login_fabrica == 52 && strlen($comentarios) > 0) { ?>
            <tr>
                <td colspan="7" class='table_line'>
                    <b>SOBRE KM: </b><?=$comentarios?>
                </td>
            </tr>

		<? }
	}

	$cor = ($i % 2 == 0) ? '#F1F4FA' : '#d9e2ef';

	//takashi colocou 020207 HD 1049
	if ($login_fabrica == 6 and $tipo_os == '8') {
		$cor = '#d9e2ef';
	}

	//Igor incluiu 28/03/2007 - HD: 1683 (or $login_fabrica == 19)
	if (in_array($login_fabrica, array(19,20,24))) {

		echo "<td colspan=\"3\"></td>\n";

	} else {

		if ($login_fabrica == 94) {

			echo '<td colspan="2" align="center"><b>'.traduz('SUBTOTAL').'</b></td>';

		}

	}

	if ($login_fabrica == 6) {

		if (strlen($liberado) > 0) {

			echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
			echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra_revenda,2,",",".") . "</b></td>\n";
			echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_pecas,2,",",".") . "</b></td>\n";
			echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_pecas_revenda,2,",",".") . "</b></td>\n";

		}

	} else if ($login_fabrica == 19) {

		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_mo,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_pecas,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_instalacao,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_deslocamento,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_total,2,",",".") . "</b></td>\n";

	} else if (in_array($login_fabrica, array(50,94))) {

		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_km,2,",",".") . "</b></td>\n";

	} else if ($login_fabrica == 183) {
                echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b>TOTAL</b></td>\n";

		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
                echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_km,2,",",".") . "</b></td>\n";
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_adicional,2,",",".") . "</b></td>\n";
	} else if (in_array($login_fabrica, array(15,30,72,90,91))) {

		$total_km = ($total_km <> $deslocamento and $deslocamento > 0) ? $deslocamento : $total_km;
		echo "<td colspan=4 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_pecas,2,",",".") . "</b></td>\n";
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_km,2,",",".") . "</b></td>\n";
		//echo ($login_fabrica == 30) ? "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_adicional,2,",",".") . "</b></td>\n":"";
		if ($login_fabrica == 30) {
			$total_adicional = 0;
		}
		echo ($login_fabrica == 90) ? "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_visita,2,",",".") . "</b></td>\n":"";

	} elseif ($login_fabrica == 24 && $login_tipo_posto == 256) {

		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_km,2,",",".") . "</b></td>\n";

	} elseif (in_array($login_fabrica, array(52,85))) {
		echo "<tr class='table_line'>\n";
			echo "<td bgcolor='$cor' style='padding-right:5px'>&nbsp;</td>";
			echo "<td bgcolor='$cor' style='padding-right:5px'>&nbsp;</td>";
			echo "<td colspan='2' align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
            if ($login_fabrica == 85) {
                echo "<td colspan='2'  align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_bonificacao,2,",",".") . "</b></td>\n";
            }
			echo "<td colspan='2'  align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_pedagio,2,",",".") . "</b></td>\n";

			echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_km,2,",",".") . "</b></td>\n";
	}

	if (!in_array($login_fabrica, array(51,81,99))) {//HD 416354
		echo "</tr>\n";
	}

	echo "<tr class='table_line' style='background-color: $cor;'>\n";

	if ($login_fabrica == 19) {

		echo "<td colspan=\"1\" align=\"center\" style='padding-right:10px'><b>TOTAL DE OS</b></td>\n";
		echo "<td colspan='1' align='center' style='padding-right:10px'><b>".($i) ."</b></td>\n";
		echo "<td colspan=\"1\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças)</b></td>\n";
		echo "<td colspan=\"5\" bgcolor='$cor' align='center'><b> " . number_format ($total_extra_total,2,",",".") . "</b></td>\n";

	} else if($inf_valores_adicionais && !in_array($login_fabrica, array(139)) && !isset($novaTelaOs)){
		if(!in_array($login_fabrica,array(125))){
			echo "<td colspan='2' align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças + KM + Valor Adicional)</b></td>\n";
			echo "<td colspan='50%' bgcolor='$cor' align='center' style='padding-right:5px'><b>";
			echo number_format($total_mao_de_obra + $total_pecas + $valor_adicional_total + $total_km,2,",",".");
			echo "</b></td>\n";
		} elseif (in_array($login_fabrica,array(125))) {
			echo "<td colspan='2' align='center' style='padding-right:10px'><b>TOTAL (MO + Peças + KM + Taxa Visita + Valor Adicional)</b></td>\n";
			echo "<td colspan='50%' bgcolor='$cor' align='center' style='padding-right:5px'><b>";
			echo number_format($total_mao_de_obra + $total_pecas + $valor_adicional_total + $total_taxa_visita + $total_km,2,",",".");
			echo "</b></td>\n";
		} else{
			echo "<td colspan='2' align=\"center\" style='padding-right:10px'><b>TOTAL (MO + KM + Valor Adicional)</b></td>\n";
			echo "<td colspan='50%' bgcolor='$cor' align='center' style='padding-right:5px'><b>";
			echo number_format($total_mao_de_obra + $valor_adicional_total + $total_km,2,",",".");
			echo "</b></td>\n";
		}
	}else {

		if ($login_fabrica == 20) {

			echo "<td colspan=\"3\" align=\"center\" style='padding-right:10px'><b>";
				echo ($sistema_lingua == "ES") ? "TOTAL (MO + PIEZAS)" : "TOTAL (MO + Peças)";
			echo "</b></td>\n";

		} else if (!in_array($login_fabrica,array(6,51,81))) {

			if (in_array($login_fabrica,array(50,94,115,116,129,131))) {

				echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + KM)</b></td>\n";

			} else if (in_array($login_fabrica,array(15,72,90,91))) {

				echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças + KM)</b></td>\n";
			}else if (in_array($login_fabrica, array(30))) {
				echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças + KM)</b></td>\n";
			} else if (in_array($login_fabrica,array(11,52,85,99,104,105,106,108,111,121,134,136,140,139))) {

				echo "<td colspan=\"3\" align=\"right\" style='padding-right:10px;font-size:1.2em'><b>TOTAL</b></td>\n";

			} else if ($login_fabrica == 87) {

				echo "<td colspan=\"5\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + KM)</b></td>\n";

			} elseif($login_fabrica == 128){

				echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + VT + KM)</b></td>\n";

			} elseif ($login_fabrica == 24) {
				if ($login_tipo_posto == 256) {
					echo "<td colspan=\"5\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + KM)</b></td>\n";
				}else{
					echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>TOTAL</b></td>\n";
				}
			} else {

				if(!isset($novaTelaOs) && $login_fabrica != 74){
                    if ($login_fabrica == 42 && $prestacao_servico == 't') {
                        $texto_soma = "TOTAL (MO + Tx. Adm.)";
                    } else {
                        $texto_soma = "TOTAL (MO + Peças)";

                    }
					echo "<td colspan=\"" . (($login_fabrica == 42) ? 3 : 2) . "\" align=\"center\" style='padding-right:10px'><b>$texto_soma</b></td>\n";
				}

			}

			if (in_array($login_fabrica,array(42,72,90,104,105))) {
                if ($login_fabrica == 42 && $prestacao_servico == 't') {
                    $total_total = $total_mao_de_obra + $total_taxa;
                } else {
                    $total_total = $total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda + $total_km;
                }

				echo "<td colspan='50%' bgcolor='$cor' align='right' style='padding-right:5px'><b>";
				echo number_format($total_total,2,",",".");
				echo "</b></td>\n";

			}else if (in_array($login_fabrica, array(30))) {
				echo "<td colspan='50%' bgcolor='$cor' align='right' style='padding-right:5px'><b>";
				echo number_format($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda + $total_km+$total_visita+$total_adicional,2,",",".");
				echo "</b></td>\n";

			} else if ($login_fabrica == 74) {//HD 416354

				 echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>Avulso</b></td>\n";
				echo "<td colspan='6' bgcolor='$cor' align='right' style='padding-right:5px'><b>";
				echo number_format($avulso,2,",",".");
				echo "</b></td><td colspan='3'></td>\n";

                echo "</tr>";
                echo "<tr class='table_line'>";
                echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças + KM + Avulso)</b></td>\n";
                if($usa_km_fixo == "true"){//HD-3141903
                    $total_km = 0;
                }
                echo "<td colspan='6' bgcolor='$cor' align='right' style='padding-right:5px'><b>";
                echo number_format($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda + $total_km+$total_visita + $avulso,2,",",".");
                echo "</b></td><td colspan='3' bgcolor='$cor'></td>";

            } else if ($login_fabrica == 99) { //HD 416354
                echo "<td colspan=\"6\" bgcolor='$cor' align='right'><b>";
                echo number_format($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas_revenda + $total_km,2,",",".");
                echo "</b></td>\n";
            } else if (in_array($login_fabrica,array(11,115,116,131))) {
                    echo "<td colspan='6' bgcolor='$cor' align='center'><b>".number_format(($total_km + $total_mao_de_obra), 2, ",", ".")."</b></td>";
            } else if (in_array($login_fabrica, array(52))) {
                echo "<td colspan='6' style='background:$cor;font-weight:bold;font-size:1.2em' align='center'><b>".number_format(($total_km + $total_mao_de_obra + $total_pedagio), 2, ",", ".")."</b></td>";
            } else if ($login_fabrica == 85) {
                echo "<td colspan='8' style='background:$cor;font-weight:bold;font-size:1.2em' align='center'><b>".number_format(($total_km + $total_bonificacao + $total_mao_de_obra + $total_pedagio), 2, ",", ".")."</b></td>";

            } else if($login_fabrica == 128){
                echo "<td colspan='6' bgcolor='$cor' align='center'><b>".number_format(($total_km + $total_mao_de_obra + $total_adicional), 2, ",", ".")."</b></td>";
            } else if($login_fabrica == 140){
                echo "<td colspan='6' bgcolor='$cor' align='center'><b>".number_format(($total_km + $total_mao_de_obra + $entrega_tecnica), 2, ",", ".")."</b></td>";
            } else if($login_fabrica == 139) {
                echo "<td colspan='6' bgcolor='$cor' align='center'><b>".number_format($total_mao_de_obra + $valor_adicional_total,2,",",".")."</b></td>";
            } else {
                if ($login_fabrica == 134) {
                    $colspan = 2;
                } else {
                    $colspan = 6;
                }

                if (isset($novaTelaOs)) {
                    $sqlTotal = "SELECT total FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND extrato = {$extrato}";
                    $resTotal = pg_query($con, $sqlTotal);

                    $total_extrato = pg_fetch_result($resTotal, 0, "total");
                } else {
                    $total_extrato = ($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas_revenda + $total_km + $total_pedagio);

                }

                if (!isset($novaTelaOs)) {
                    echo "<td colspan=$colspan bgcolor='$cor' align='center'><b> " . number_format ($total_extrato ,2,",",".") . "</b></td>\n";

                    if ($login_fabrica == 134) { ?>
                        <td bgcolor='<?= $cor ?>' align='right'><?= number_format ($total_pecas,2,",",".") ?></td>
                        <td bgcolor='<?= $cor ?>' align='right'><?= number_format ($total_pecas + $total_extrato,2,",",".")?> </td>
                    <?php
                    }
                }
            }

        } else if (!in_array($login_fabrica, array(51,81,99))) {

            if (strlen($liberado) > 0) {//HD 6134

                if ($login_fabrica <> 6 OR ($login_fabrica == 6 and $data_liberado < '2007-10-16')) {
                    echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças)</b></td>\n";
                    echo "<td colspan=\"4\" bgcolor='$cor' align='center'><b>".number_format($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda,2,",",".") . "</b></td>\n";
                }

            }

        }

    }
    echo "</tr>\n";
}
if ($total_yanmar > 0) {
    $ja_baixado    = false ;
    $posto         = pg_fetch_result($res_yanmar, 0, 'posto');
    $data_geracao  = pg_fetch_result($res_yanmar, 0, 'data_geracao');
    $liberado      = pg_fetch_result($res_yanmar, 0, 'liberado');
    $data_liberado = pg_fetch_result($res_yanmar, 0, 'data_liberado');
    $protocolo     = pg_fetch_result($res_yanmar, 0, 'protocolo');
    $geracao       = pg_fetch_result($res_yanmar, 0, 'geracao');
    $mao_de_obra_desconto = pg_fetch_result($res_yanmar, 0, 'mao_de_obra_desconto');
    $sql = "SELECT  tbl_posto.nome,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_fabrica.parametros_adicionais
            FROM    tbl_posto_fabrica
            JOIN    tbl_posto   ON tbl_posto.posto     = tbl_posto_fabrica.posto
            JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
            WHERE   tbl_posto_fabrica.posto   = $login_posto
            AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
    $resx = pg_query($con,$sql);
    if (pg_num_rows($resx) > 0) {
        $posto_codigo = trim(pg_fetch_result($resx, 0, 'codigo_posto'));
        $posto_nome   = trim(pg_fetch_result($resx, 0, 'nome'));
    }
    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='5'>\n";
    echo "<tr class='menu_top'>";
    echo "<td colspan='";
    echo (in_array($login_fabrica,array(19,35,72,15,90,85,87,120,201,125,142,145))) ? '100%' : '11';
    echo "' align='center'>";
    fecho('extrato', $cook_idioma);
    echo ($login_fabrica == 1 or $login_fabrica==19) ? ": ".$protocolo : ": ".$extrato;
    echo ($sistema_lingua == "ES") ? " GENERADO EN " : " GERADO EM ";
    echo "$data_geracao <br> $posto_codigo - $posto_nome";
    echo "<br>".traduz('Valores de Entrega Técnica');
    echo "</td>";
    echo "</tr>";
    echo "<tr class='menu_top'>\n";
    echo "<td align='center' width='17%' >OS</td>\n";
    if ($login_posto == 1537) echo "<td align='center' width='17%'>OS WEB</td>\n";
    echo "<td align='center'>";
    echo ($sistema_lingua == "ES") ? "CONSUMIDOR" : "CLIENTE";
    echo "</td>\n";
    if (in_array($login_fabrica,array(50,94,115,116,125,128,129,131,140)) || isset($novaTelaOs)) {
        if (isset($novaTelaOs)) {
            $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
            $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
        }
        if (!in_array($login_fabrica, array(143))) {
            echo "<td colspan=2 align='center'>".traduz('Valor Entrega Tecnica')."</td>\n";
        }
        if (!isset($novaTelaOs) || (isset($novaTelaOs) && !$nao_calcula_km)) {
            echo "<td colspan=2 align='center'>KM</td>\n";
        }
        if (isset($novaTelaOs) && !$nao_calcula_peca) {
            echo "<td colspan=2 align='center'>".traduz('Peças')."</td>\n";
        }
    } else {
        echo "<td colspan=2 align='center'>MO</td>\n";
        if (!in_array($login_fabrica,array(11,51,81,99,101,106,108,111,121,123,124,125,127,128,134,136,139))) {
            echo "<td colspan=2 align='center'>";
                echo ($sistema_lingua == "ES") ? "PIEZAS" : "PEÇAS";
            echo "</td>\n";
        }
    }
    if($inf_valores_adicionais || in_array($login_fabrica, array(142,145)) || isset($fabrica_usa_valor_adicional)){
        echo "<td>".traduz('Valor Adicional')."</td>";
    }
    echo "</tr>\n";
    $total                     = 0;
    $total_mao_de_obra         = 0;
    $total_pedagio             = 0;
    $total_mao_de_obra_revenda = 0;
    $total_pecas               = 0;
    $total_pecas_revenda       = 0;
    $total_km                  = 0;
    $total_extra_mo            = 0;
    $total_extra_pecas         = 0;
    $total_extra_instalacao    = 0;
    $total_extra_deslocamento  = 0;
    $total_extra_total         = 0;
    $total_visita              = 0;
    for ($i = 0 ; $i < $total_yanmar; $i++) {
        $os                       = trim(pg_fetch_result($res_yanmar, $i, 'os'));
        $sua_os                   = trim(pg_fetch_result($res_yanmar, $i, 'sua_os'));
        $mo                       = trim(pg_fetch_result($res_yanmar, $i, 'mao_de_obra'));
        $avulso                   = trim(pg_fetch_result($res_yanmar, $i, 'avulso'));
        $mao_de_obra_distribuidor = trim(pg_fetch_result($res_yanmar, $i, 'mao_de_obra_distribuidor'));
        $pecas                    = trim(pg_fetch_result($res_yanmar, $i, 'pecas'));
        $qtde_km_calculada        = trim(pg_fetch_result($res_yanmar, $i, 'qtde_km_calculada'));
        $pedagio                  = trim(pg_fetch_result($res_yanmar, $i, 'pedagio'));
        $consumidor_nome          = strtoupper(trim(pg_fetch_result($res_yanmar, $i, 'consumidor_nome')));
        $produto_descricao        = trim(pg_fetch_result($res_yanmar, $i, 'descricao_produto'));
        $consumidor_str           = substr($consumidor_nome, 0, 23);
        $data_abertura            = trim(pg_fetch_result($res_yanmar, $i, 'data_abertura'));
        $data_fechamento          = trim(pg_fetch_result($res_yanmar, $i, 'data_fechamento'));
        $baixado                  = trim(pg_fetch_result($res_yanmar, 0, 'baixado'));
        $obs                      = trim(pg_fetch_result($res_yanmar, 0, 'obs'));
        $consumidor_revenda       = trim(pg_fetch_result($res_yanmar, $i, 'consumidor_revenda'));
        $tipo_atendimento         = trim(pg_fetch_result($res_yanmar, $i, 'tipo_atendimento'));
        $revenda_nome             = trim(pg_fetch_result($res_yanmar, $i, 'revenda_nome'));
        $tipo_os                  = trim(pg_fetch_result($res_yanmar, $i, 'tipo_os'));//takashi colocou 020207 HD 1049
        $taxa_visita              = trim(pg_fetch_result($res_yanmar, $i, 'extra_instalacao'));
        $entrega_tecnica          = trim(pg_fetch_result($res_yanmar, $i, 'entrega_tecnica'));
        $valores_adicionais       = trim(pg_fetch_result($res_yanmar, $i, 'valores_adicionais'));
        $comentarios              = pg_fetch_result($res_yanmar, $i, 'obs_adicionais');
        $mao_de_obra_desconto     = pg_fetch_result($res_yanmar, $i, 'mao_de_obra_desconto');
        $statusOs       = trim(pg_fetch_result($res_yanmar, $i, status_os)); // Regras para auditoria de NS e Reincidência HD 2539696
        $valores_adicionais = ($valores_adicionais) ? $valores_adicionais : 0;
        if ($consumidor_revenda == "R" and $login_fabrica == 6) {
            $consumidor_str = $revenda_nome;
        }
        if(empty($qtde_km_calculada)){
            $qtde_km_calculada = 0;
        }
        $total_km += $qtde_km_calculada;
        if (strlen($baixado) > 0) $ja_baixado = true ;
        $total_mao_de_obra  += $mo;
        $total_pedagio      += $pedagio;
        $mao_de_obra         = $mo;
        $pecas_posto         = ($login_fabrica == 40 or $login_fabrica == 90 ) ? 0 : $pecas;
        $total_pecas        += ($login_fabrica == 40 or $login_fabrica == 90 ) ? 0 :$pecas ;
        $total_visita       += ($login_fabrica == 90) ? $taxa_visita : 0;
        $total_taxa_visita       += ($login_fabrica == 125) ? $taxa_visita : 0;
        $cor = ($i % 2 == 0) ? '#F1F4FA' : '#d9e2ef';
        $btn = ($i % 2 == 0) ? 'azul' : 'amarelo';
        if (strstr($matriz, ";" . $i . ";")) {
            $cor = '#E49494';
        }
        if((!empty($mao_de_obra_desconto)) and (empty($mao_de_obra)) and $login_fabrica == 30) {
            $cor90 = "#87CEFA";
        }else{
            $cor90 = "";
        }
        echo "<tr class='table_line' style='background-color: $cor; background-color: $cor90; '>\n";
        echo "<td align='center' style='white-space : nowrap'><acronym title=\"Abertura: $data_abertura | Fechamento: $data_fechamento \"><a href=\"os_press.php?os=$os\" target='_blank'><font color='#000000'>";
        echo "$sua_os</font></a></acronym>";
        echo    "</td>\n";
        if ($login_posto == 1537) echo "<td align='center'><font color='#000000'>$os</font>";
        echo "<td align='left' nowrap>";
            echo ($login_fabrica == 5 and strlen(trim($consumidor_nome)) == 0) ? $revenda_nome : "<acronym title=\"$consumidor\">$consumidor_str</acronym>";
        echo "</td>\n";
        if (in_array($login_fabrica,array(50,94,115,116,125,128,129,131,140)) || isset($novaTelaOs)) {
            if (isset($novaTelaOs)) {
                $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
            }
            $mao_de_obra = (empty($mao_de_obra)) ? 0 : $mao_de_obra;
            $qtde_km_calculada = (empty($qtde_km_calculada)) ? 0 : $qtde_km_calculada;
            if (!in_array($login_fabrica, array(143))) {
                echo "<td colspan=2  align='center' style='padding-right:5px'> " . number_format($mao_de_obra,2,",",".") . "</td>\n";
            }
            if (!isset($novaTelaOs) || (isset($novaTelaOs) && !$nao_calcula_km)) {
                echo "<td colspan=2  align='center' style='padding-right:5px'> " . number_format($qtde_km_calculada,2,",",".") . "</td>\n";
            }
            if (isset($novaTelaOs) && !$nao_calcula_peca) {
                echo "<td colspan=2  align='right' style='padding-right:5px'> " . number_format($pecas,2,",",".") . "</td>\n";
            }
        } else {
            echo "<td colspan=2";
                echo (in_array($login_fabrica, array(51,81,106,108,111))) ? " align='center'" : " align='right' style='padding-right:5px'";
            echo " > " . number_format ($mao_de_obra,2,",",".") . "</td>\n";
            if (!in_array($login_fabrica,array(11,51,81,99,101,106,108,111,121,123,124,125,127,128,134,136,139))) {//HD 416354
                echo "<td colspan=2";
            echo (in_array($login_fabrica, array(51,81))) ? " align='center'" : " align='right' style='padding-right:5px'";
                echo " > " . number_format ($pecas_posto,2,",",".") . "</td>\n";
            }
        }
        if ($inf_valores_adicionais || in_array($login_fabrica, array(142,145)) || isset($fabrica_usa_valor_adicional)) {
            echo "<td align='right'>".number_format ($valores_adicionais,2,",",".") . "</td>\n";
            $valor_adicional_total += $valores_adicionais;
        }
        echo "</tr>\n";
    }
    $cor = ($i % 2 == 0) ? '#F1F4FA' : '#d9e2ef';
    if (!in_array($login_fabrica, array(51,81,99))) {//HD 416354
        echo "</tr>\n";
    }
    echo "<tr class='table_line' style='background-color: $cor;'>\n";
    if($inf_valores_adicionais && !in_array($login_fabrica, array(139)) && !isset($novaTelaOs)){
        if(!in_array($login_fabrica,array(125))){
            echo "<td colspan='2' align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças + KM + Valor Adicional)</b></td>\n";
            echo "<td colspan='50%' bgcolor='$cor' align='center' style='padding-right:5px'><b>";
            echo number_format($total_mao_de_obra + $total_pecas + $valor_adicional_total + $total_km,2,",",".");
            echo "</b></td>\n";
        } else{
            echo "<td colspan='2' align=\"center\" style='padding-right:10px'><b>TOTAL (MO + KM + Valor Adicional)</b></td>\n";
            echo "<td colspan='50%' bgcolor='$cor' align='center' style='padding-right:5px'><b>";
            echo number_format($total_mao_de_obra + $valor_adicional_total + $total_km,2,",",".");
            echo "</b></td>\n";
        }
    }else {
        if (!in_array($login_fabrica,array(6,51,81))) {
            if(!isset($novaTelaOs) && $login_fabrica != 74){
                echo "<td colspan=\"" . (($login_fabrica == 42) ? 3 : 2) . "\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças)</b></td>\n";
            }
            $colspan = 6;
            if (isset($novaTelaOs)) {
                $sqlTotal = "SELECT total FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND extrato = {$extrato}";
                $resTotal = pg_query($con, $sqlTotal);
                $total_extrato = pg_fetch_result($resTotal, 0, "total");
            } else {
                $total_extrato = ($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas_revenda + $total_km + $total_pedagio);
            }
            if (!isset($novaTelaOs)) {
                echo "<td colspan=$colspan bgcolor='$cor' align='center'><b> " . number_format ($total_extrato ,2,",",".") . "</b></td>\n";
            }
        }
    }
    echo "</tr>\n";
}
#a pedido do Túlio o extrato da Gama Italy tem que aparecer as OS recusadas
if (in_array($login_fabrica, array(51,81))) {
    $sql51 = "SELECT    tbl_os.os                                     ,
                        tbl_os.sua_os                                             ,
                        tbl_os.consumidor_nome                                    ,
                        tbl_os.mao_de_obra                                        ,
                        tbl_os.pecas                                              ,
                        tbl_os_status.status_os as status_da_os                   ,
                        to_char(tbl_os_status.data, 'DD/MM/YYYY')  AS data_recusa ,
                        tbl_os_status.extrato                                     ,
                        tbl_os_status.observacao
            FROM tbl_os_status
            JOIN tbl_os ON tbl_os.os=tbl_os_status.os
             AND tbl_os.fabrica = $login_fabrica
            WHERE tbl_os_status.extrato=$extrato
        AND tbl_os_status.os NOT IN (
                                    SELECT tbl_os_extra.os
                                        FROM tbl_os_extra
                                        WHERE tbl_os_extra.extrato=$extrato)
        ORDER BY sua_os, os";
    $res51 = pg_query($con,$sql51);
    if (pg_num_rows($res51) > 0) {
        $xtotal_mao_de_obra = 0;
        $xtotal_pecas       = 0;
        for ($x = 0; $x < pg_num_rows($res51); $x++) {
            $sua_os             = pg_fetch_result($res51, $x, 'sua_os');
            $os                 = pg_fetch_result($res51, $x, 'os');
            $status_da_os       = pg_fetch_result($res51, $x, 'status_da_os');
            $data_recusa        = pg_fetch_result($res51, $x, 'data_recusa');
            $extrato            = pg_fetch_result($res51, $x, 'extrato');
            $consumidor_nome    = pg_fetch_result($res51, $x, 'consumidor_nome');
            $mao_de_obra        = pg_fetch_result($res51, $x, 'mao_de_obra');
            $pecas              = pg_fetch_result($res51, $x, 'pecas');
            $observacao         = pg_fetch_result($res51, $x, 'observacao');
            if ($status_da_os <> 15) {
                $xtotal_mao_de_obra += $mao_de_obra;
                $xtotal_pecas       += $pecas;
            }
            $cor = "#d9e2ef";
            if ($status_da_os == '13') {
                $cor = "#FFCA99";
            }
            if ($status_da_os == '14') {
                $cor = "#FFA390";
            }
            if ($status_da_os == '15') {
                $cor = "#B44747";
            }
            echo "<tr class='menu_top' style='background-color: $cor'>\n";
            echo "<td align='center' style='color:black' width='17%'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>\n";
            echo "<td align='left' nowrap>$consumidor_nome</td>\n";
            echo "<td colspan='2' align='center'>".number_format($mao_de_obra,2,",",".")."</td>\n";
            echo "<td colspan='2' align='center'>".number_format($pecas,2,",",".")."</td>\n";
            echo "<td align='left' style='padding-left: 5px'>$observacao</td>\n";
            echo "</tr>";
        }
    } else {
        if ($sistema_lingua == 'ES') {
            echo "<center>No se rechaz&oacute; ninguna Orden de Servicio</center>";
        } else {
            echo "<center>Nenhuma Ordem de serviço foi rejeitada</center>";
        }
    }
}
#a pedido do Túlio o extrato da Gama Italy tem que aparecer as OS recusadas
echo "</TABLE>\n";
echo "<br/>";
if (in_array($login_fabrica, array(169, 170))) {
	$avulso_descricao = "tbl_lancamento.descricao || ' - ' || tbl_extrato_lancamento.descricao AS descricao,";
} else {
	$avulso_descricao = "tbl_lancamento.descricao,";
}
##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
$sql =  "SELECT $avulso_descricao
                tbl_extrato.total,
                tbl_extrato_lancamento.historico ,
                tbl_extrato_lancamento.valor,
                to_char(tbl_extrato_lancamento.data_lancamento,'DD/MM/YYYY') AS data_lancamento,
                tbl_extrato_lancamento.os AS os_avulso
        FROM tbl_extrato_lancamento
        JOIN tbl_lancamento USING (lancamento)
        JOIN tbl_extrato USING (extrato)
        WHERE tbl_extrato_lancamento.extrato = $extrato
        AND   tbl_lancamento.fabrica = $login_fabrica";
if ($login_fabrica == 6) {
    $sql .= " AND tbl_extrato.liberado notnull";
}
if ($login_fabrica == 51 or $login_fabrica == 81) {#Gama não mostra o valor de sedex para PA não colocar na Nota. HD49451
    $sql .= " AND tbl_extrato_lancamento.lancamento NOT IN (121,96) ";
}

if ($login_fabrica == 85) {
    $sql .= "\nAND (tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado' or tbl_extrato_lancamento.descricao is null)\n";
}
$res_avulso = pg_query($con, $sql);

if (pg_num_rows($res_avulso) > 0) {
    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
        echo "<tr class='menu_top'>\n";
            echo "<td colspan='3'>";
                echo ($sistema_lingua == "ES") ? "LANZAMIENTO DE EXTRACTO MANUAL" : "LANÇAMENTO DE EXTRATO AVULSO";
            echo "</td>\n";
        echo "</tr>\n";
    echo "<tr class='menu_top'>\n";
            if($login_fabrica == 85){
                echo "<td>OS</td>";
            }
            echo "<td>";
                echo ($sistema_lingua == "ES") ? "DESCRIPCIÓN" : "DESCRIÇÃO";
            echo "</td>\n";
            echo "<td>HISTÓRICO</td>\n";
            echo "<td>VALOR</td>\n";
        echo "</tr>\n";
        for ($j = 0 ; $j < pg_num_rows($res_avulso) ; $j++) {
            $cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
            echo "<tr class='table_line' style='background-color: $cor;'>\n";
                if($login_fabrica == 85){
                    echo "<td width='5%'>".pg_fetch_result($res_avulso, $j, 'os_avulso')."</td>";
                }
		echo "<td width='45%'>".pg_fetch_result($res_avulso, $j, 'descricao')."</td>";
                echo "<td width='45%'>".pg_fetch_result($res_avulso, $j, 'historico')."</td>";
                echo "<td width='10%' align='right'>".number_format(pg_fetch_result($res_avulso, $j, 'valor'), 2, ',', '.')."</td>";
            echo "</tr>";
        }
    echo "</table>\n";
    echo "<br />\n";
    if ($login_fabrica <> 19 and !isset($novaTelaOs)) {
        echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
            echo "<tr class='menu_top'>\n";
                echo "<td colspan='3'";
                if ($sistema_lingua == "ES") {
                    echo ">TOTAL ";
                } else {
                    if (in_array($login_fabrica, array(51,81))) {
                        $exflag = 0;
                        echo " style='font-size: 12px;'>VALOR TOTAL DO EXTRATO";
                    } else {
                        echo ">".traduz('TOTAL GERAL');
                    }
                }
                echo "</td>\n";
            echo "</tr>\n";
            echo "<tr class='menu_top' 'table_line' style='background-color: #F1F4FA'>\n";
                echo "<td ";
                if (in_array($login_fabrica, array(51,81))) {
                    echo " style='font-size: 13px;'";
                }
                echo "> ". number_format(pg_fetch_result($res_avulso,0,total), 2, ',', '.') ."</td>\n";
            echo "</tr>\n";
        echo "</table>";
    }
}
if (isset($novaTelaOs) or $login_fabrica == 19) {
    $sqlTotal = "SELECT total FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND extrato = {$extrato}";
    $resTotal = pg_query($con, $sqlTotal);
    $total_extrato = pg_fetch_result($resTotal, 0, "total");
  
 if(in_array($login_fabrica, array(190))){
            $sqlCont = "SELECT DISTINCT tbl_contrato_os.contrato, tbl_contrato.campo_extra
                        FROM tbl_os
                        JOIN tbl_os_extra on tbl_os_extra.os=tbl_os.os
                        JOIN tbl_contrato_os on tbl_contrato_os.os=tbl_os.os
                        JOIN tbl_contrato on tbl_contrato_os.contrato=tbl_contrato.contrato AND tbl_contrato.fabrica = $login_fabrica
                       WHERE (tbl_os_extra.extrato = $extrato OR tbl_os_extra.extrato_recebimento = $extrato)
                         AND tbl_os.fabrica = $login_fabrica";

            $resCont = pg_query($con,$sqlCont);
            if (pg_num_rows($resCont) > 0) {
                foreach (pg_fetch_all($resCont) as $key => $value) {
                    $xcampoExtra = json_decode($value['campo_extra'],1);
                    if (isset($xcampoExtra["valor_mao_obra_fixa"])) {
                        $valor_mao_obra_fixa += $xcampoExtra["valor_mao_obra_fixa"];
                    } else {
                        $valor_mao_obra_fixa += 0;
                    }
                }
             }

            $total_extrato = ($total_extrato+$valor_mao_obra_fixa);
        }


    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
        echo "<tr class='menu_top'>\n";
            echo "<td colspan='3'";
                echo ">".traduz('TOTAL GERAL');
            echo "</td>\n";
        echo "</tr>\n";
        echo "<tr class='menu_top' 'table_line' style='background-color: #F1F4FA'>\n";
            echo "<td ";
            echo "> ". number_format($total_extrato, 2, ',', '.') ."</td>\n";
        echo "</tr>\n";
    echo "</table>";
}

if ($login_fabrica == 183){
    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
        echo "<tr class='menu_top'>\n";
            echo "<td colspan='3' style='font-size: 16px; color: #ff0c00'>";
                echo traduz('Obs: Emitir a nota fiscal somente com o valor total da mão de obra ')."( R$ ".number_format($total_mao_de_obra, 2, ',', '.')." )";
            echo "</td>\n";
        echo "</tr>\n";
    echo "</table>";
}

# Para a Gama deixar sempre o TOTAL por último, com o intuito de simplificar para os Postos
if ((in_array($login_fabrica, array(51,81)) || ($telecontrol_distrib and !$controle_distrib_telecontrol)) and $login_fabrica <> 160 and !$replica_einhell) {
    $valor_os_recusada = 0;
    $sql4 = "SELECT SUM(valor) FROM tbl_extrato_lancamento WHERE extrato = $extrato AND lancamento in(122);";
    $res4 = pg_query($con, $sql4);
    if (pg_num_rows($res4) > 0) {
        $valor_os_recusada = pg_fetch_result($res4,0,0);
    }
    $sql4 = "SELECT pecas FROM tbl_extrato WHERE extrato = $extrato";
    $res4 = pg_query($con, $sql4);
    if (pg_num_rows($res4) > 0) {
        $total_pecas= pg_fetch_result($res4,0,0);
    }
    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
    if (!isset($exflag)) {
        if (in_array($login_fabrica, array(125))) {
            $valor_original = $xtotal_mao_de_obra + $xtotal_pecas + $total_mao_de_obra + $total_pecas+$valor_adicional_total + $total_km + $total_adicional + $total_taxa_visita;
        }else{
            $valor_original = $xtotal_mao_de_obra + $xtotal_pecas + $total_mao_de_obra + $total_pecas+$valor_adicional_total + $total_km + $total_adicional;
        }
        $valor_liquido  = $total_mao_de_obra + $total_pecas;
        if($login_fabrica != 153){
            echo "<tr class='menu_top'>";
                echo "<td align='center' style='font-size: 10px;'>".traduz('VALOR TOTAL DO EXTRATO')."</td>";
            echo "</tr>";
            echo "<tr class='menu_top'>";
                echo "<td align='center' style='font-size: 13px; background-color: #F1F4FA;'><strong>".number_format($valor_original,2,",",".")."</strong></td>\n";
            echo "</tr>";
        }
    }
    $valor_recusado = $xtotal_mao_de_obra + $xtotal_pecas + $valor_os_recusada;
    echo "<tr class='menu_top'>";
        echo "<td align='center' style='padding-right:5px; font-size: 10px;'>".traduz("VALOR A SER DEBITADO NO PRÓXIMO EXTRATO")."</td>";
    echo "</tr>";
    echo "<tr class='menu_top'>";
        echo "<td align='center' style='padding-right:5px; font-size: 13px; color:red; background-color: #F1F4FA;'>".number_format($valor_recusado,2,",",".")."</td>\n";
    echo "</tr>";
    echo "<tr><td>&nbsp;</td></tr>";
    echo "<tr><td align='center' style='color: red'><strong>";
    echo "* ".traduz('ATENÇÃO').": ".traduz("Favor preencher a Nota Fiscal de Mão-de-Obra com o mesmo valor que está em \"VALOR TOTAL DO EXTRATO\"");
    echo "</strong></td></tr>";
    echo "</TABLE>\n";
}
if ($login_fabrica == 45) {
    $sql = "SELECT  tbl_excecao_mobra.excecao_mobra ,
                    tbl_posto_fabrica.codigo_posto          ,
                    tbl_posto.cnpj                          ,
                    tbl_posto.nome                          ,
                    tbl_produto.produto                     ,
                    tbl_produto.referencia                  ,
                    tbl_produto.descricao                   ,
                    tbl_linha.nome              AS linha    ,
                    tbl_excecao_mobra.familia                ,
                    tbl_familia.descricao AS familia_descricao,
                    tbl_excecao_mobra.mao_de_obra           ,
                    tbl_excecao_mobra.adicional_mao_de_obra ,
                    tbl_excecao_mobra.percentual_mao_de_obra
            FROM    tbl_excecao_mobra
            JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_excecao_mobra.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN    tbl_posto            ON tbl_posto.posto           = tbl_posto_fabrica.posto
            LEFT JOIN tbl_produto        ON tbl_produto.produto       = tbl_excecao_mobra.produto
            LEFT JOIN tbl_linha AS l1    ON l1.linha                  = tbl_produto.linha
            AND l1.fabrica                = $login_fabrica
            LEFT JOIN tbl_familia AS ff    ON ff.familia              = tbl_produto.familia
            AND l1.fabrica                = $login_fabrica
            LEFT JOIN tbl_linha          ON tbl_linha.linha           = tbl_excecao_mobra.linha
            AND tbl_linha.fabrica         = $login_fabrica
            LEFT JOIN tbl_familia          ON tbl_familia.familia     = tbl_excecao_mobra.familia
            AND tbl_familia.fabrica         = $login_fabrica
            WHERE   tbl_excecao_mobra.fabrica = $login_fabrica
            AND     tbl_excecao_mobra.posto   = $login_posto
            ORDER BY tbl_posto.nome;";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>";
            echo "<tr>";
                echo "<td class='menu_top' align='center' colspan='6'>Exceção de Mão de Obra</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<td  class='menu_top' align='center'>LINHA</td>";
                echo "<td  class='menu_top' align='center'>FAMÍLIA</td>";
                echo "<td class='menu_top' align='center'>PRODUTO</td>";
                echo "<td class='menu_top' align='center'>MÃO-DE-OBRA</td>";
                echo "<td class='menu_top' align='center'>ADICIONAL</td>";
                echo "<td class='menu_top' align='center'>PERCENTUAL</td>";
            echo "</tr>";
        for ($z = 0; $z < pg_num_rows($res); $z++) {
            $cor = ($z % 2 == 0) ? '#F1F4FA' : '#E2E9F5';
            $excecao_mobra     = trim(pg_fetch_result($res, $z, 'excecao_mobra'));
            $cnpj              = trim(pg_fetch_result($res, $z, 'cnpj'));
            $cnpj              = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
            $codigo_posto      = trim(pg_fetch_result($res, $z, 'codigo_posto'));
            $posto             = trim(pg_fetch_result($res, $z, 'nome'));
            $produto           = trim(pg_fetch_result($res, $z, 'produto'));
            $produto_descricao = trim(pg_fetch_result($res, $z, 'referencia')) ."-". trim(pg_fetch_result($res, $z, 'descricao'));
            //if (strlen($produto) == 1) $produto = "TODOS";
            $linha             = trim(pg_fetch_result($res, $z, 'linha'));
            $familia           = trim(pg_fetch_result($res, $z, 'familia'));
            $familia_descricao = trim(pg_fetch_result($res, $z, 'familia_descricao'));
            if (strlen($familia_descricao) == 0) $familia_descricao = "<i style='color: #959595'>TODAS</i>";
            $mobra             = trim(pg_fetch_result($res, $z, 'mao_de_obra'));
            $adicional_mobra   = trim(pg_fetch_result($res, $z, 'adicional_mao_de_obra'));
            $percentual_mobra  = trim(pg_fetch_result($res, $z, 'percentual_mao_de_obra'));
            if (strlen($linha) > 0) {
                $familia_descricao = "<i style='color: #959595;'>TODAS DA LINHA ESCOLHIDA</i>";
                $produto_descricao = "<i style='color: #959595'>TODOS DA FAMILIA ESCOLHIDA</i>";
            }
            if (strlen($familia) > 0) {
                $linha             = "&nbsp;";
                $produto_descricao = "<i style='color: #959595'>TODOS DA FAMILIA ESCOLHIDA</i>";
            }
            if (strlen($produto) > 0) {
                $linha             = "&nbsp;";
                $familia           = "&nbsp;";
            }
            if (strlen($linha) == 0 AND strlen($familia) == 0 AND strlen($produto) == 0) {
                $linha             = "<i style='color: #959595;'>TODAS</i>";
                $familia_descricao = "<i style='color: #959595;'>TODAS DA LINHA ESCOLHIDA</i>";
                $produto_descricao = "<i style='color: #959595;'>TODOS DA FAMILIA ESCOLHIDA</i>";
            }
            echo "<tr>";
                echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$linha</font></td>";
                echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$familia_descricao</font></td>";
                echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$produto_descricao</font></td>";
                echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>".number_format($mobra,2,",",".")."</font></td>";
                echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>".number_format($adicional_mobra,2,",",".")."</font></td>";
                echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>".number_format($percentual_mobra,2,",",".")."</font></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####
if ($login_fabrica == 6) {
    $wsql = "SELECT tbl_os_status.os_status,
                    tbl_os_status.os       ,
                    tbl_os.sua_os,
                    tbl_os_status.status_os  ,
                    tbl_os_status.data as data_order,
                    to_char(tbl_os_status.data,'DD/MM/YYYY') as data      ,
                    tbl_os_status.observacao ,
                    tbl_os_status.extrato    ,
                    tbl_os_status.os_sedex   ,
                    tbl_os_status.admin
            from tbl_os_status
            JOIN tbl_os on tbl_os.os = tbl_os_status.os and tbl_os.fabrica = $login_fabrica
            join tbl_extrato on tbl_os_status.extrato =tbl_extrato.extrato
            where tbl_extrato.extrato=$extrato
            and tbl_extrato.liberado notnull
            and status_os=90
            order by sua_os, data_order;";
    $wres = pg_query($con, $wsql);
    if (pg_num_rows($wres) > 0) {
        echo "<BR><BR><table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
        echo "<tr class='menu_top'>\n";
            echo "<td colspan='3'>";
            echo "OBSERVAÇÕES FEITAS PELO FABRICANTE";
            echo "</td>\n";
        echo "</tr>\n";
        echo "<tr class='menu_top' style='background-color: $cor;'>\n";
            echo "<td class='menu_top'>OS</td>";
            echo "<td class='menu_top'>DATA</td>";
            echo "<td class='menu_top'>OBSERVAÇÃO</td>";
        echo "</tr>";
        for ($i = 0; pg_num_rows($wres) > $i; $i++) {
            $sua_os     = pg_fetch_result($wres, $i, 'sua_os');
            $data       = pg_fetch_result($wres, $i, 'data');
            $observacao = pg_fetch_result($wres, $i, 'observacao');
            echo "<tr class='table_line' style='background-color: $cor;'>\n";
                echo "<td align='center'>$sua_os</td>";
                echo "<td align='center'>$data</td>";
                echo "<td align='left'>$observacao</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
echo '<br />';
if ($ja_baixado == true && $login_fabrica != 11) {//HD 226679 ?>
    <table width='600' border='0' cellspacing='1' cellpadding='0' align='center'>
        <tr>
            <td height='20' class="table_line" colspan='4'><?php echo ($sistema_lingua == "ES") ? "PAGO" : "PAGAMENTO"; ?></td>
        </tr>
        <tr>
            <td align='left' class="table_line" width='20%'> <?php echo ($sistema_lingua == "ES") ? "EXTRACTO PAGADO EL:" : "EXTRATO PAGO EM:"; ?></td>
            <td class="table_line" width='15%'><?php echo $baixado; ?></td>
            <td align='left' class="table_line" width='15%'><center><?php echo ($sistema_lingua == "ES") ? "Observaciones:" : "OBSERVAÇÃO"; ?> :</center></td>
            <td width='50%'><?php echo $obs;?></td>
        </tr>
    </table><?php
}

if (in_array($login_fabrica, [169,170]) && !empty($justificativa)) { ?>
    <table width='700' border='0' cellspacing='1' cellpadding='0' align='center'>
        <tr class="menu_top">
            <td height='20'><?= ($sistema_lingua == "ES") ? "JUSTIFICACIÓN" : "JUSTIFICATIVA"; ?></td>
        </tr>
        <tr>
            <td height='20' class="table_line" style="background-color:#F1F4FA;"><?= $justificativa; ?></td>
        </tr>
    </table>
<?php }

//tulio - samel solicitou retirada
if ($login_fabrica == 20 and $sistema_lingua <> "ES") {
    echo "<FORM name='frm_atendimento' METHOD=POST ACTION='$PHP_SELF?extrato=$extrato&posto=$posto'>";
    echo "<TABLE WIDTH='500' border='0' align='center'>";
        echo "<TR>";
            echo "<td class='table_line'> &nbsp; ";
            echo ($sistema_lingua == "ES") ? "OS por tipo atendimiento: " : "OS por tipo atendimento: ";
            echo "</td>";
            echo "<td class='table_line' ALIGN='left'>";
                echo "<select class='frm' size='1' name='tipo_atendimento'>";
                    echo "<option selected></option>";
                    $sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica;";
                    $res = pg_query($con, $sql) ;
                    if ($sistema_lingua == 'ES')
                        $sql = "SELECT tbl_tipo_atendimento.tipo_atendimento, tbl_tipo_atendimento_idioma.*
                                FROM   tbl_tipo_atendimento
                                JOIN   tbl_tipo_atendimento_idioma using(tipo_atendimento)
                                WHERE  fabrica = $login_fabrica
                                AND    idioma = '$sistema_lingua';";
                        $res = @pg_query($con,$sql) ;
                    for ($x = 0; $x < pg_num_rows($res); $x++) {
                            echo "<option ";
                            if ($tipo_atendimento == pg_fetch_result($res,$x,tipo_atendimento)) echo " SELECTED ";
                            echo " value='" . pg_fetch_result($res,$x,tipo_atendimento) . "'>" ;
                            echo pg_fetch_result($res,$x,descricao) ;
                            echo "</option>";
                    }
                echo "</select>";
            echo "</td>";
            echo "<td class='table_line' WIDTH='93'>";
                echo "<input type='hidden' name='btn_acao' value=''>";
                echo "<img src='imagens/btn_filtrar.gif' onclick=\"if (document.frm_atendimento.btn_acao.value == '' ) { document.frm_atendimento.btn_acao.value='filtrar' ; document.frm_atendimento.submit() } else { alert ('Aguarde submissão') }\" ALT='Confirmar filtro por Tipo de Atendimento' border='0' style='cursor:pointer;'>";
            echo "</td>";
        echo "</tr>";
    echo "</TABLE>";
    echo "</FORM>";
} else if($sistema_lingua <> "ES") {?>

    <br />

    <FORM name='frm_servico' METHOD=POST ACTION="<? echo $PHP_SELF."?extrato=".$extrato."&posto=".$posto."#servicos"; ?>">
        <TABLE WIDTH='600'  align='center'>
            <TR>
            <td class='table_line'><?=traduz('os.por.servico.realizado', $cook_idioma)?></td>
                <td ALIGN='CENTER'><?php
                    echo "<select class='frm' size='1' name='servico_realizado'>";
                    echo "<option selected></option>";
                    $sql = "SELECT * FROM tbl_servico_realizado WHERE fabrica = $login_fabrica;";
                    $res = pg_query($con,$sql) ;
                    for ($x = 0; $x < pg_num_rows($res); $x++) {
                        if ($login_fabrica == 3 AND $linha <> 3 AND pg_fetch_result($res, $x, 'servico_realizado') == 20) {
                        } else {
                            echo "<option ";
                            if ($servico_realizado == pg_fetch_result($res,$x,servico_realizado)) echo " selected ";
                            echo " value='" . pg_fetch_result($res,$x,servico_realizado) . "'>" ;
                            echo pg_fetch_result($res,$x,descricao) ;
                            echo "</option>";
                        }
                    }
                    if(in_array($login_fabrica,array(134))){
                        $selected = ($servico_realizado == 'total_de_pecas')?'selected':'';
                        echo '<option '.$selected.' value="total_de_pecas" >Total de Peças</option>';
                    }
                    echo "</select>";?>

                </td>
                <td WIDTH='93'>
                    <input type="hidden" name="btn_acao" value="">
                    <img src='imagens/btn_filtrar.gif' onclick="javascript: if (document.frm_servico.btn_acao.value == '' ) { document.frm_servico.btn_acao.value='filtrar' ; document.frm_servico.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar filtro por serviço realizado" border='0' style="cursor:pointer;">
                </td>
            </tr>
            <?php if($login_fabrica == 42){ // hd-1059101 Makita?>
                <TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario'>
                    <TR class='titulo_tabela'>
                        <TD height='20' colspan='4'>Pagamento</TD>
                    </TR>
                    <tr><td colspan='4'>&nbsp;</td></tr>
                    <TR>
                        <TD align='left' class='espaco'>Nº NF M.O. </TD>
                        <TD align='left'>Valor NF M.O. (R$)</TD>
                        <TD align='left'>Acréscimo M.O. (R$)</TD>
                        <TD align='left'>Desconto M.O. (R$)</TD>
                    </tr>
                    <tr>
                        <td class='espaco'> <INPUT readonly='readonly' TYPE='text' NAME='nro_nf_servico'    size='15' maxlength='20' value="<?=$nro_nf_servico ?>"  class='frm'></td>
                        <td>                <INPUT readonly='readonly' TYPE='text' NAME='vlr_nf_servico'    size='15' maxlength='20' value="<?=$vlr_nf_servico?>"   class='frm'></td>
                        <td>                <INPUT readonly='readonly' TYPE='text' NAME='acrescimo_servico' size='15' maxlength='20' value="<?=$acrescimo_servico?>" class='frm'></td>
                        <td>                <INPUT readonly='readonly' TYPE='text' NAME='desconto_servico'  size='15' maxlength='20' value="<?=$desconto_servico?>"     class='frm'></td>
                    </tr>
                    <TR>
                        <TD align='left' class='espaco'>Valor NF Peças (R$)</TD>
                        <TD align='left'>Nº NF Peças</TD>
                        <TD align='left'>Acréscimo Peças (R$)</TD>
                        <TD align='left'>Desconto Peças (R$)</TD>
                    </tr>
                    <tr>
                        <td class='espaco'> <INPUT readonly='readonly' TYPE='text' NAME='vlr_nf_pecas'      size='15' maxlength='20' value="<?=$vlr_nf_pecas?>"     class='frm'></td>
                        <td>                <INPUT readonly='readonly' TYPE='text' NAME='nro_nf_pecas'      size='15' maxlength='20' value="<?=$nro_nf_pecas?>"     class='frm'></td>
                        <td>                <INPUT readonly='readonly' TYPE='text' NAME='acrescimo_pecas'   size='15' maxlength='20' value="<?=$acrescimo_pecas?>"  class='frm'></td>
                        <td>                <INPUT readonly='readonly' TYPE='text' NAME='desconto_pecas'    size='15' maxlength='20' value="<?=$desconto_pecas?>"   class='frm'></td>
                    </tr>
                    <TR>
                        <TD align='left' class='espaco'>Data de Pagamento</TD>
                    </tr>
                    <tr>
                        <td class='espaco'> <INPUT readonly='readonly' TYPE='text' NAME='data_pagamento'    size='15' maxlength='20' value="<?=$data_pagamento?>"   class='frm'></td>

                    </tr>
                    <tr>
                        <TD class='espaco' colspan='4' align='left'>Justificativa</TD>
                    </tr>
                    <tr>
                        <!-- <td class='espaco' colspan='4' > <INPUT readonly='readonly' TYPE='text' NAME='justificativa' style='width: 93.1%;' maxlength='255' value="<?=$justificativa?>"     class='frm'></td> -->
                        <td class='espaco' colspan='4'> <textarea readonly='readonly' TYPE='text' NAME='justificativa' style='width: 93.1%;' maxlength='255'  class='frm' rows="6" cols="100">
                                <?=$justificativa?>
                        </textarea></td>
                    </tr>
                </table>
            <?php } ?>
        </TABLE>
    </FORM><?php
}
$tipo_atendimento  = $_POST['tipo_atendimento'];
$servico_realizado = $_POST['servico_realizado'];
$btn_acao          = $_POST['btn_acao'];
if($btn_acao == 'filtrar' && $servico_realizado == 'total_de_pecas' && in_array($login_fabrica,array(134))){
    montaTotalDePecas(empty($posto)?$login_posto:$posto,$extrato);
}
elseif ($btn_acao == 'filtrar' AND (strlen($servico_realizado) > 0 OR strlen($tipo_atendimento) > 0)) {
    $sql = "SELECT      tbl_peca.referencia          ,
                        tbl_peca.descricao           ,
                        sum(tbl_os_item.qtde) AS qtde,
                        tbl_os_item.custo_peca AS preco,
                        tbl_os.sua_os
            FROM        tbl_os
            JOIN        tbl_os_produto   ON (tbl_os_produto.os      = tbl_os.os)
            JOIN        tbl_os_item      ON (tbl_os_item.os_produto = tbl_os_produto.os_produto)
            JOIN        tbl_peca         ON (tbl_peca.peca          = tbl_os_item.peca)
            JOIN        tbl_os_extra     ON (tbl_os_extra.os        = tbl_os.os)
            JOIN        tbl_extrato      USING (extrato)
            WHERE       tbl_os.fabrica = $login_fabrica";
    if (strlen($tipo_atendimento) > 0)  $sql .= " AND tbl_os.tipo_atendimento = $tipo_atendimento";
    if (strlen($servico_realizado) > 0) $sql .= " AND tbl_os_item.servico_realizado = $servico_realizado ";
    if (strlen($posto) == 0)  $sql .= "AND tbl_os.posto = $login_posto ";
    else $sql .= " AND tbl_os.posto = $posto ";
    $sql .= " AND tbl_extrato.extrato = $extrato
            GROUP BY tbl_peca.referencia,
                     tbl_peca.descricao,
                     tbl_os_item.custo_peca,
                     tbl_os.sua_os
            ORDER BY tbl_peca.descricao";
    $res       = pg_query($con, $sql);
    $registros = pg_num_rows($res);
    if ($registros > 0) {?>
        <br />
        <TABLE align="center" id='tbl_os_filtra' width="700px">
            <TR class='menu_top'>
                <TD>OS</TD>
                <TD><?=traduz('descricao', $cook_idioma)?></TD>
                <TD><?=traduz('descricao.da.peca', $cook_idioma)?></TD>
                <TD><?=traduz('qtde', $cook_idioma)?></TD>
                <?php if($login_fabrica != 134){ ?><TD><?=strtoupper(traduz('valor', $cook_idioma))?></TD><?php } ?>
            </TR><?php
            for ($i = 0; $i < $registros; $i++) {
                $mlg_os     = pg_fetch_result($res, $i, 'sua_os');
                $referencia = pg_fetch_result($res, $i, 'referencia');
                $descricao  = pg_fetch_result($res, $i, 'descricao');
                $qtde       = pg_fetch_result($res, $i, 'qtde');
                $preco      = pg_fetch_result($res, $i, 'preco');
                echo "<TR class='table_line'>\n";
                    echo "<TD><A href=\"os_press.php?os=$mlg_os\" target='_blank'>$mlg_os</A></TD>\n";
                    echo "<TD>$referencia</TD>\n";
                    echo "<TD>$descricao</TD>\n";
                    echo "<TD>$qtde</TD>\n";
                    if ($login_fabrica != 134) {
                        if ($login_fabrica == 120 or $login_fabrica == 201) {
                            echo "<TD>".number_format(0,2,',','.')."</TD>\n";
                        } else {
                            echo "<TD>".number_format($preco,2,',','.')."</TD>\n";
                        }
                    } else {
                        echo "";
                    }
                echo "</TR>\n";
            }
        echo '</table>';
    }
}?>

<br /><br />
<?php if($login_fabrica == 42){ ?>
<table width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario'>
    <TR class='titulo_tabela'>
        <TD height='20' colspan='4'>Atenção!</TD>
    </TR>
    <tr>
        <td align='center' style='color: red'>

            <p style='font-size: 12px'> <?= traduz('Gere o "Extrato Detalhado" no botão abaixo e emita as Notas Fiscais de mão de obra e peças de acordo com o mesmo.') ?> </p>
            <p style='font-size: 12px'><?= traduz('Não esqueça de enviar todas peças juntamente com o extrato detalhado e as notas fiscais.</p>') ?>

        </td>
    </tr>
</table>
<?php
}

if(($telecontrol_distrib or in_array($login_fabrica, array(154,171,178,183,184,191,200))) and !$controle_distrib_telecontrol){
    $nota_fiscal_servico = $s3_extrato->getObjectList($extrato."-nota_fiscal_servico");
    if(count($nota_fiscal_servico) > 0){
        $nota_fiscal_servico = basename($nota_fiscal_servico[0]);
        ?>
    <table>
        <thead>
            <tr>
                <th><?= traduz('Nota Fiscal de Serviço') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                                
                <?php 

                $anexos = $s3_extrato->getObjectList("{$extrato}-", false);

                $cont = 1;
                if(count($anexos)>0){
                    foreach($anexos as $anexo){                   
                        $ext = preg_replace("/.+\./", "", $anexo);
                        //$nome_arquivo = "$extrato-nota_fiscal_servico-"."$cont". ".$ext";
                        $nome_arquivo =basename($anexo);
                        
                        if(!in_array($ext, array("pdf", "doc", "docx"))){
                            $thumb_nota_fiscal_servico = $s3_extrato->getLink("thumb_".$nome_arquivo);
                            if(strlen(trim($thumb_nota_fiscal_servico))==0){
                                $nome_arquivo = "$extrato-nota_fiscal_servico". ".$ext";;
                                $thumb_nota_fiscal_servico = $s3_extrato->getLink("thumb_".$nome_arquivo);
                            }
                        }else{
                            switch ($ext) {
                                case 'pdf':
                                    $thumb_nota_fiscal_servico = 'admin/imagens/pdf_icone.png';
                                    break;
                                case 'doc':
                                case 'docx':
                                    $thumb_nota_fiscal_servico = 'admin/imagens/docx_icone.png';
                                    break;
                            }
                        }
                        $nota_fiscal_servico = $s3_extrato->getLink($nome_arquivo);
                        ?>
                            <td align='center' class='anexos_<?=$cont?>'>
                                <a href="<?=$nota_fiscal_servico?>" target='_blank'><img src="<?=$thumb_nota_fiscal_servico?>" style="border:1px solid; margin:5px;" /></a>
                                <br>
								<? if($pendente != 'f') { ?>
								<button type='button' class='removeAnexo' data-qtde="<?=$cont?>" data-nomearquivo='<?=$nome_arquivo?>' data-extrato='<?=$extrato?>' ><?= traduz('Excluir') ?></button>
								<? } ?>
                            </td>
                    <?php  
                    $cont++;
                    }
                }

                 ?>
                <br>
                <br>
            </tr>
        </tbody>
    </table>
    <?php
    }

    if ($login_fabrica == 183){
        $sqlNfeVisualizada = " SELECT obs, data
            FROM tbl_extrato_status 
            WHERE fabrica = {$login_fabrica}
            AND extrato = {$extrato}
            AND obs = 'Nota Fiscal Aprovada'
            ORDER BY data DESC
            LIMIT 1";
        $resNfeVisualizada = pg_query($con, $sqlNfeVisualizada);

        $display_nf = "";
        if (pg_num_rows($resNfeVisualizada) > 0) {
            $display_nf = "style='display:none;'";
        }
    }else{
        $display_nf = "";
    }

    if(((!$telecontrol_distrib) OR ( $telecontrol_distrib AND $nf_recebida != 't' and !$controle_distrib_telecontrol)) && !in_array($login_fabrica, [152,180,181,182])) {
    ?>
    <form name="form_nota_fiscal_servico" id="form_nota_fiscal_servico" method="POST" enctype="multipart/form-data" <?=$display_nf?> >
        <?if($login_fabrica == 191){?>
        <label style='font-size: 18px; color:red;'><b>Favor quando emitir a NF de serviço informar todas as OS's correspondentes</b></label><Br><br>
        <?php } ?>

        <label>Anexar Nota Fiscal de Serviço</label><Br>
        <?if(in_array($login_fabrica, array(152,180,181,182))) {?>
        <button type='button' id="adicionar_arquivo"><?= traduz('Adicionar Arquivo') ?></button>
        <?php } ?>


        <div class="controls controls-row arquivo_nota">
            <input title='Selecione o arquivo da nota fiscal de serviço'
                    type='file' name='arquivo_nota_fiscal_servico[]' id="arquivo_nota_fiscal_servico" size='18' >
        </div>
        <div id='local_arquivo_nota'>

        </div>

        <br/>
        <div class="controls controls-row">
            <input type="button" name="upload_arquivo_nota_fiscal_servico" id="upload_arquivo_nota_fiscal_servico" value="<?php echo $telecontrol_distrib ? "Salvar Arquivo": "Salvar Imagem"; ?>" class="salvar_nota"/>
        </div>
    </form>
<?php  } } ?>
<div style="width: 40%;">
<?php
if ($usaNotaFiscalServico) {
	if($login_fabrica == 183){
		$hidden_button = true;
	}

    if(in_array($login_fabrica, array(152,180,181,182))){
        $sqlNF90dias = "SELECT extrato, posto, data_geracao, fabrica
                            FROM tbl_extrato
                            WHERE fabrica = {$login_fabrica}
                            AND posto = {$login_posto}
                            and extrato = {$extrato}
                            AND data_geracao < CURRENT_DATE -90";

        //die(nl2br($sqlNF90dias));
        $resNF90Ddias = pg_query($con, $sqlNF90dias);
        $contador90dias = pg_num_rows($resNF90Ddias);

        if($contador90dias > 0){
            $hidden_button = true;             
            $msg90dias = "<br><font style='background-color:#FF0000; font:bold 16px arial; color:#FFFFFF; text-align:center;'>&nbsp;" . utf8_encode(traduz('anexos.bloqueados,.extrato.a.mais.de.90.dias')) . "&nbsp;</font><br><br>";
        } else {
            $hidden_button = false;
        }
        
    }

    $tempUniqueId = $extrato;
    $boxUploader = array(
        "titulo_tabela" => "Anexar Nota Fiscal de Serviço",
        "div_id" => "div_anexos",
        "prepend" => $anexo_prepend,
        "context" => "extrato",
        "unique_id" => $tempUniqueId,
        "hash_temp" => $anexoNoHash,
        "bootstrap" => false,
        "hidden_button" => $hidden_button
    );
    
    echo $msg90dias;
    include "box_uploader.php";
}
?>
</div>
<table align='center' >

    <tr align='center'>
        <td>
            <br /><?php
            if ($login_fabrica == 42) {
                $print_pecas = "_pecas";
            }
            $botao_voltar = ($sistema_lingua == "ES") ? "btn_volver.gif" : "btn_voltar.gif";
            $url = ($login_fabrica == 1) ? "os_extrato_detalhe_print_blackedecker.php" : "os_extrato_detalhe_print$print_pecas.php"; ?>
            <?php
            if ($login_fabrica == 42){
            ?>

                <input type='button' value='Voltar' onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
                &nbsp;&nbsp;
                <input type='button' value='Extrato Detalhado' onclick="janela=window.open('<?=$url;?>?extrato=<?=$extrato;?>','extrato');" ALT='Extrato Detalhado'>
            <?php }else { ?>

                <img src="imagens/<?=$botao_voltar?>" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
                &nbsp;&nbsp;
                <img src="imagens/btn_imprimir.gif" onclick="janela=window.open('<?=$url;?>?extrato=<?=$extrato;?>','extrato');" ALT="Imprimir" border='0' style="cursor:pointer;">
            <?php } ?>
        </td>
    </tr>
</table>

<? include "rodape.php";
function montaTotalDePecas($posto,$extrato){
    global $login_fabrica;
    global $con;
    try{
        $result = consultaTotalDePecas($posto,$extrato);
        exibeTotalDePecas($result);
    }
    catch(Exception $ex){
        ?>
        <div class="error"> <?php echo $ex->getMessage(); ?></div>
        <?php
    }
}
function exibeTotalDePecas($data){
    global $login_fabrica;
    $total_preco = 0;
    $total_qtde = 0;
    foreach ($data as  $value) {
        $total_preco += $value['valor_total'];
        $total_qtde += $value['qtde_total'];
    }
    ?>
    <script type="text/javascript">
        $(function(){
            $('tr.group_table_line').click(function(){
                var tr = $(this);
                var next = tr.next('tr');
                console.debug();
                if(next.css('display') == 'none'){
                    next.show();
                }
                else{
                    next.hide();
                }
            });
        });
    </script>
    <br />
    <div style="width:700px;padding:5px;background-color:#ffc;" >
        <h3  style="text-align:center;color:#000;">
            Para visualizar as OS's clique na linha desejada.
        </h3>
    </div>
    <br />
    <table align="center" width="700px" id="tbl_os_filtra">
        <tbody>
            <tr class="menu_top">
                <td><?= traduz('REFERÊNCIA') ?></td>
                <td><?= traduz('DESCRIÇÃO DA PEÇA') ?></td>
                <td><?= traduz('QTDE') ?></td>
                <td><?= traduz('VALOR') ?></td>
            </tr>
    <?php
    foreach($data as $key => $val){
        ?>
        <tr class="table_line group_table_line">
            <td>
                <?php echo $val['referencia'] ?>
            </td>
            <td>
                <?php echo $val['descricao'] ?>
            </td>
            <td>
                <?php echo $val['qtde_total'] ?>
            </td>
            <td>
                <?php echo number_format($val['valor_total'],2,',','.'); ?>
            </td>
        </tr>
        <tr style="display:none;" >
            <td colspan="4">
            <table align="center" class="tabela" >
                <tbody>
                    <tr class="menu_top">
                        <td>OS</td>
                        <td><?= traduz('QTDE') ?></td>
                        <td><?= traduz('VALOR') ?></td>
                    </tr>
                    <?php
                        foreach($val as $key => $v){
                            if(!is_numeric($key))
                                continue;
                            ?>
                            <tr>
                                <td>
                                    <a target="_blank" href="os_press?os=<?php echo $v['sua_os'] ?>">
                                        <?php echo $v['sua_os']?>
                                    </a>
                                </td>
                                <td><?php echo $v['qtde']?></td>
                                <td><?php echo number_format($v['preco'],2,',','.') ?></td>
                            </tr>
                            <?php
                        }
                    ?>
                </tbody>
            </table>
            </td>
        </tr>
        <tr style="display:none;" ></tr>
        <?php
    }
    ?>
            <tr class="table_line">
                <td colspan="2" align="right" style="padding-right:10px;font-size:1.2em" >
                    <?= traduz('TOTAL') ?>
                </td>
                <td colspan="1" style="text-align:center;font-size:1.2em">
                    <b><?php echo  $total_qtde ?></b>
                </td>
                <td colspan="1" style="text-align:center;font-size:1.2em">
                    <b><?php echo number_format($total_preco,2,',','.') ?></b>
                </td>
            </tr>
        </tbody>
    </table>
    <br />
    <?php
}
function consultaTotalDePecas($posto,$extrato){
    global $con;
    global $login_fabrica;
    global $login_posto;
    $params = array($login_fabrica,$posto,$extrato);
    $sql = 'SELECT
                tbl_peca.referencia,
                tbl_peca.descricao,
                SUM(tbl_os_item.qtde) AS qtde,
                round(tbl_os_item.custo_peca::numeric,2) AS preco,
                tbl_os.sua_os
            FROM tbl_os
            INNER JOIN tbl_os_produto
                ON (tbl_os_produto.os = tbl_os.os)
            INNER JOIN tbl_os_item
                ON (tbl_os_item.os_produto = tbl_os_produto.os_produto)
            INNER JOIN tbl_peca
                ON (tbl_peca.peca = tbl_os_item.peca)
            INNER JOIN tbl_os_extra
                ON (tbl_os_extra.os = tbl_os.os)
            INNER JOIN tbl_extrato
                USING (extrato)
            WHERE
                tbl_os.fabrica = $1
            AND tbl_os.posto = $2
            AND tbl_extrato.extrato = $3
            GROUP BY tbl_peca.referencia,
                     tbl_peca.descricao,
                     tbl_os_item.custo_peca,
                     tbl_os.sua_os
            ORDER BY tbl_peca.descricao';
    $result = pg_query_params($con,$sql,$params);
    if(!$result)
        throw new Exception(pg_last_error($con));
    $fetch = pg_fetch_all($result);
    pg_free_result($result);
    $result = array();
    foreach($fetch as $row){
        if(!isset($result[$row['referencia']]))
            $result[$row['referencia']] = array('valor_total'=>0,'qtde_total'=>0);
        $result[$row['referencia']][] = $row;
        $result[$row['referencia']]['referencia'] = $row['referencia'];
        $result[$row['referencia']]['descricao'] = $row['descricao'];
        $result[$row['referencia']]['valor_total'] += $row['preco'];
        $result[$row['referencia']]['qtde_total'] += $row['qtde'];
    }
    return $result;
}
