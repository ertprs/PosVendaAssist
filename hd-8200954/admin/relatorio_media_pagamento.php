<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "financeiro";
include "autentica_admin.php";

include "funcoes.php";
include_once '../class/tdocs.class.php';
include_once '../class/communicator.class.php';
include_once "../classes/mpdf61/mpdf.php";

$classProtocolo = new \Posvenda\Fabricas\_1\Protocolo($login_fabrica, $con);

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

$msg = "";

function avisoEmailProtocolo($con,$login_fabrica,$protocolo,$arquivoProtocolo)
{
    $mailer = new TcComm('smtp@posvenda');
    $mailer->setEmailFrom('"Sistema Telecontrol" <helpdesk@telecontrol.com.br>');
    $sqlDest = "
        SELECT  email
        FROM    tbl_admin
        WHERE   fabrica = $login_fabrica
        AND     JSON_FIELD('aprova_protocolo',parametros_adicionais)::BOOL IS TRUE
    ";
    $resDest = pg_query($con,$sqlDest);

    //emails em cópia
    $emails = [
        "gustavo.dominici@sbdinc.com",
        "marilma.amancio@sbdinc.com",
        "pagamento.garantia@sbdinc.com"
    ];
    while($email = pg_fetch_object($resDest)) {
        $emails[] = $email->email;
    }

    $lista = implode(",",$emails);
    $mailer->addEmailDest($lista);
    $mailer->setEmailSubject("EXTRATOS: Protocolos de Aprovação para Envio ao Financeiro");
    $corpo = "
        Prezado(a),

        Protocolo nº $protocolo foi gerado e aguarda aprovação.

    ";
    $mailer->setEmailBody($corpo);
    if ($mailer->sendMail()) {
        return true;
    }

    return false;

}

function gerarProtocoloArquivo($con,$login_fabrica,$protocolo)
{
    global $login_admin, $login_nome_completo;

    $tDocs  = new TDocs($con, $login_fabrica,"protocolo");
    $pdf    = new mPDF;
    $total_extrato = 0;

    $topo = "<table autosize='1' cellspacing='0' cellpadding='0' style='width:100%;'>
                <tr>
                    <th rowspan='2' style='width:6.5cm;'>
                        <img src='../logos/logo_black_2017.png' style='max-height:55px;max-width:240px;' border='0' />
                    </th>
                    <th nowrap='nowrap' width='65%'>RELATÓRIO DE APROVAÇÃO DA DIRETORIA FINANCEIRA</th>
                    <th>Página {PAGENO}/{nb}</th>
                </tr>
                <tr>
                    <th nowrap='nowrap'>CONTROLE DE GARANTIAS Nº $protocolo ".date('d/m/Y')."</th>
                </tr>
            </table>
            <br /><br />
            ";

    $cabecalho = "
    <br /><br />
        <table autosize='1' cellspacing='0' cellpadding='0' style='border:1px solid #000;width:100%;'>
            <thead>
                <tr>
                    <th style='border:1px solid #000;'>CÓDIGO</th>
                    <th style='border:1px solid #000;'>POSTO</th>
                    <th style='border:1px solid #000;'>DATA GERAÇÃO</th>
                    <th style='border:1px solid #000;'>EXTRATO</th>
                    <th style='border:1px solid #000;'>NF AUTORIZADO</th>
                    <th style='border:1px solid #000;'>TOTAL</th>
                </tr>
            </thead>
            <tbody>
    ";

    $sqlGeracao = "
        SELECT  tbl_posto_fabrica.codigo_posto,
                tbl_posto.nome,
                TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
                tbl_extrato.protocolo AS numero_extrato,
                tbl_extrato_extra.nota_fiscal_mao_de_obra AS nf_autorizada,
                tbl_extrato.total,
                tbl_extrato.extrato
        FROM    tbl_extrato_agrupado
        JOIN    tbl_extrato         USING(extrato)
        JOIN    tbl_extrato_extra   USING(extrato)
        JOIN    tbl_posto_fabrica   USING(fabrica,posto)
        JOIN    tbl_posto           USING(posto)
        WHERE   tbl_extrato.fabrica         = $login_fabrica
        AND     tbl_extrato_agrupado.codigo = '$protocolo'
  ORDER BY      tbl_posto_fabrica.codigo_posto
    ";
    $resGeracao = pg_query($con,$sqlGeracao);

    while ($extratos = pg_fetch_object($resGeracao)) {
        $bold = ($extratos->total > 3000) ? "font-weight:bold;" : "";
        $total = $extratos->total;
        $extrato = $extratos->extrato;
        if(!empty($extrato)) {
               $totalTx =  somaTxExtratoBlack($extrato); 
               $total+=$totalTx;
        }

        $conteudo .= "
            <tr>
                <td style='border:1px solid #000;".$bold."'>".$extratos->codigo_posto."</td>
                <td style='border:1px solid #000;".$bold."'>".$extratos->nome."</td>
                <td style='border:1px solid #000;".$bold."'>".$extratos->data_geracao."</td>
                <td style='border:1px solid #000;".$bold."'>".$extratos->numero_extrato."</td>
                <td style='border:1px solid #000;".$bold."'>".$extratos->nf_autorizada."</td>
                <td style='border:1px solid #000;".$bold."'>R$ ".number_format($total,2,',','.')."</td>
            </tr>
        ";
        $total_extrato += $total;
    }

    $tDocsAss = new TDocs($con, $login_fabrica, 'assinatura');

    $img_assinatura = $tDocsAss->getDocumentsByRef($login_admin)->url;

    $conteudo .= "
        </tbody>
        <tfoot>
            <tr>
                <td colspan='6' style='font-weight:bold;text-align:right;'>TOTAL EXTRATOS: R$".number_format($total_extrato,2,',','.')."</td>
            </tr>
        </tfoot>
    </table>
    <br /><br />
    <table autosize='1' cellspacing='0' cellpadding='0' style='width:100%;'>
                <tr>
                    <td align='center'>
                        <img id='imagem_firma' src='$img_assinatura' height='96' />
                        <br />
                        $login_nome_completo
                        <br />
                        Analista Comercial
                        <br />
                        ".date('d/m/Y')."
                    </td>
                    <td align='center'>
                        ____________________________________
                        <br />
                        Gerente de Pós-venda
                        <br />
                        ____/____/________
                    </td>
                </tr>
                <tr>
                    <td align='center'>
                        <br /><br />
                        ____________________________________
                        <br />
                        Gerente de Contas a Pagar
                        <br />
                        ____/____/________
                    </td>
                </tr>
            </table>
    ";

    $topo = utf8_encode($topo);

    $arquivo = utf8_encode($cabecalho).utf8_encode($conteudo);
    $caminho = "xls/protocolo_".$login_fabrica."_".$protocolo.".pdf";

    $pdf->allow_html_optional_endtags = true;
    $pdf->setAutoTopMargin = 'stretch';
    $pdf->SetTitle("PROTOCOLO ".$protocolo);
    $pdf->SetDisplayMode('fullpage');
    $pdf->AddPage('L');
    $pdf->SetHTMLHeader($topo,'O',true);
    $pdf->WriteHTML($arquivo);
    $pdf->Output($caminho,'F');

    $retorno = $tDocs->uploadFileS3($caminho,$protocolo,false);

    return $retorno;
}

function baixarProtocoloArquivo($con,$login_fabrica,$protocolo)
{

    $tDocs = new TDocs($con, $login_fabrica);

    $retorno =  $tDocs->getDocumentsByName('protocolo_'.$login_fabrica.'_'.$protocolo.'.pdf')->url;

    return $retorno;
}

function gerarProtocolo($con,$login_fabrica,$login_admin,$extratos,$baixar) {
    global $classProtocolo;

    $sql = "
        SELECT  codigo
        FROM    tbl_extrato_agrupado
        JOIN    tbl_extrato USING(extrato)
        WHERE   tbl_extrato.fabrica = $login_fabrica
  ORDER BY      tbl_extrato_agrupado.data_agrupa DESC
        LIMIT   1;
    ";
    $res = pg_query($con,$sql);

    if (pg_fetch_result($res,0,codigo) == "") {
        $codigo = "0";
    } else {
        $codigo = pg_fetch_result($res,0,codigo);
    }

    $acresce = (int)$codigo + 1;

    $protocolo = str_pad($acresce,6,"0",STR_PAD_LEFT);

    pg_query($con,"BEGIN TRANSACTION");

    $sqlIns = "INSERT INTO tbl_extrato_agrupado (extrato,codigo,admin_agrupa) VALUES \n";

    $total_extratos = count($extratos);
    $conta = 0;
    foreach ($extratos as $extrato) {
        $conta++;
        $sqlIns .= "($extrato,'$protocolo',$login_admin)";
        $sqlIns .= ($conta == $total_extratos) ? ";" : ",\n";
    }

    $resIns = pg_query($con,$sqlIns);

    $classProtocolo->insereStatusProtocolo($protocolo, 'ge_pv');

    if (pg_last_error($con)) {
        $erro = pg_last_error($con);
        pg_query($con,"ROLLBACK TRANSACTION");
        return "erro: ".$erro;
    }

    pg_query($con,"COMMIT TRANSACTION");

    $retorno = gerarProtocoloArquivo($con,$login_fabrica,$protocolo);

    $arquivoProtocolo = baixarProtocoloArquivo($con,$login_fabrica,$protocolo);

    $envios = avisoEmailProtocolo($con,$login_fabrica,$protocolo,$arquivoProtocolo);

    return json_encode(array("ok"=>true,'arquivo' => $retorno, "baixar"=>$baixar, 'arquivoProtocolo' => $arquivoProtocolo,"email"=>$envios));
}

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $tipo       = filter_input(INPUT_POST,'tipo');
    $baixar     = filter_input(INPUT_POST,'baixar',FILTER_VALIDATE_BOOLEAN);
    $extratos   = filter_input(INPUT_POST,'extratos',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);

    if ($tipo == "gerar_protocolo") {
        echo gerarProtocolo($con,$login_fabrica,$login_admin,$extratos,$baixar);
    }
    exit;
}


if($_POST["btn_acao"] == "submit"){
	##### Pesquisa entre datas #####
	$data_inicial = $_POST["data_inicial"];
	$data_final   = $_POST["data_final"];

	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$x_data_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$x_data_inicial = str_replace("'","",$x_data_inicial);
	}else{
	        $msg_erro["msg"][]    ="Data Inválida";
	        $msg_erro["campos"][] = "data_inicial";
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
	        $x_data_final =  fnc_formata_data_pg(trim($data_final));
	        $x_data_final = str_replace("'","",$x_data_final);
	}else{
	        $msg_erro["msg"][]    ="Data Inválida";
	        $msg_erro["campos"][] = "data_final";
	}

	if($xdata_inicial > $xdata_final) { 
		$msg_erro["msg"][]    ="Data Inicial maior que final";
		$msg_erro["campos"][] = "data_inicial";
	}

	##### Pesquisa de produto #####
	$posto_codigo  = trim($_POST["codigo_posto"]);
	$posto_nome    = trim($_POST["descricao_posto"]);

	if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome                ,
						tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND   tbl_posto_fabrica.codigo_posto = '{$posto_codigo}';";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
			$posto        = pg_fetch_result($res,0,'posto');
			$posto_codigo = pg_fetch_result($res,0,'codigo_posto');
			$posto_nome   = pg_fetch_result($res,0,'nome');
		}else{
			$msg_erro["msg"][] = " Posto não encontrado. ";
		}

	}

	##### Situação do Extrato #####
	$situacao = $_POST["situacao"];
	$agrupado = $_POST["agrupado"];;

    $sql_posto = "1=1";
    if (strlen($posto) > 0) $sql_posto = " tbl_posto.posto = $posto ";

    if ($agrupado == "APROVADOS") {
        $sqlProtocolo = "\nAND tbl_extrato_agrupado.aprovado IS NOT NULL\n";
    } else if ($agrupado == "REPROVADOS") {
        $sqlProtocolo = "
            AND tbl_extrato_agrupado.aprovado IS NULL
            AND tbl_extrato_agrupado.reprovado IS NOT NULL
        ";
    }

    $sql = "
    SELECT  DISTINCT tbl_extrato.extrato,
            tbl_extrato.protocolo AS extrato_numero,
            tbl_extrato.total ,
            tbl_extrato_agrupado.codigo AS codigo_protocolo,
            TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_extrato ,
            TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY') AS data_aprovado ,
            TO_CHAR(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY') AS data_financeiro ,
            (TO_CHAR(tbl_extrato_financeiro.data_envio,'YYYY-MM-DD')::date - TO_CHAR(tbl_extrato.data_geracao,'YYYY-MM-DD')::date) AS dias ,
	    tbl_posto_fabrica.codigo_posto AS posto_codigo ,
	    tbl_posto_fabrica.contato_cidade,
	    tbl_posto_fabrica.contato_estado,
            tbl_posto.nome AS posto_nome ,
            tbl_extrato_extra.nota_fiscal_mao_de_obra ,
            xx.qtde_os AS qtd_os,
            tbl_extrato_financeiro.admin_pagto,
            status_protocolo.codigo as codigo_status,
            status_protocolo.descricao as descricao_status
    FROM    (
                SELECT ext.extrato, COUNT(os) AS qtde_os FROM
                (
                    SELECT  tbl_extrato.extrato
                    FROM    tbl_extrato
               LEFT JOIN    tbl_extrato_financeiro USING (extrato)";
		if ($situacao == "FINANCEIRO") $sql .= " WHERE tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
												AND tbl_extrato_financeiro.data_envio IS NOT NULL";

		if ($situacao == "GERACAO") $sql .= " WHERE tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
											AND tbl_extrato.aprovado IS NULL";

		if ($situacao == "APROVACAO") $sql .= " WHERE tbl_extrato.aprovado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
												AND tbl_extrato_financeiro.data_envio IS NULL
												AND tbl_extrato.aprovado IS NOT NULL";
	   $sql .= " AND tbl_extrato.fabrica = $login_fabrica ) ext
		LEFT JOIN tbl_os_extra ON tbl_os_extra.extrato = ext.extrato
		GROUP BY ext.extrato
	) xx
	JOIN tbl_extrato ON xx.extrato = tbl_extrato.extrato
	LEFT JOIN tbl_extrato_lancamento ON tbl_extrato.extrato = tbl_extrato_lancamento.extrato
	LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
	LEFT JOIN tbl_extrato_agrupado ON tbl_extrato_agrupado.extrato = tbl_extrato.extrato
	JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
	JOIN tbl_posto_fabrica ON tbl_extrato.fabrica = tbl_posto_fabrica.fabrica AND tbl_extrato.posto = tbl_posto_fabrica.posto
	JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
    LEFT JOIN LATERAL (

        SELECT tbl_status_extrato_agrupado.codigo,
               tbl_status_extrato_agrupado.descricao
        FROM tbl_status_extrato_agrupado
        JOIN tbl_extrato_agrupado_status USING(status_extrato_agrupado)
        WHERE tbl_extrato_agrupado_status.extrato_agrupado_codigo = tbl_extrato_agrupado.codigo
        ORDER BY tbl_extrato_agrupado_status.data_input DESC
        LIMIT 1

    ) status_protocolo ON true
	WHERE $sql_posto
	$sqlProtocolo
	ORDER BY tbl_posto_fabrica.codigo_posto
	; ";

    $resSubmit = pg_query($con,$sql);

    if(isset($_POST['gerar_excel'])){

	    $data = date("d-m-Y-H:i");
	    $filename = "relatorio-media-pagamento-{$data}.csv";
	    $file = fopen("/tmp/{$filename}", "w");
	    
	    $header = "";

	    if ($situacao == "FINANCEIRO") {
		    $header .= "Protocolo;Status;";
	    }

	    $header .= "Extrato;Código;Posto;";

	    if($login_fabrica == 1){
		    $header .= "Cidade;Estado;";
	    }

	    $header .= "Período Digitação;NF Autorizado;Total;Geração;Aprovação;Financeiro;Dias;Qtde OS";

	    if($login_fabrica == 1){
		    $header .= ";Admin";
	    }

	    $header .= "\n"; 

	    fwrite($file,$header);

	    $contador_submit = pg_num_rows($resSubmit);
	    
	    $body = "";

	    for ($i = 0; $i < $contador_submit; $i++) {

		$extrato           = trim(pg_fetch_result($resSubmit,$i,'extrato'));
		$extrato_numero    = trim(pg_fetch_result($resSubmit,$i,'extrato_numero'));
		$codigo_protocolo  = trim(pg_fetch_result($resSubmit,$i,'codigo_protocolo'));
		$total             = trim(pg_fetch_result($resSubmit,$i,'total'));
		$qtd_os            = trim(pg_fetch_result($resSubmit,$i,'qtd_os'));
		if(strlen($qtd_os) == 0) $qtd_os = 0;
		$data_extrato      = trim(pg_fetch_result($resSubmit,$i,'data_extrato'));
		$data_aprovado     = trim(pg_fetch_result($resSubmit,$i,'data_aprovado'));
		$data_financeiro   = trim(pg_fetch_result($resSubmit,$i,'data_financeiro'));
		$dias              = trim(pg_fetch_result($resSubmit,$i,'dias'));
		$dias              = str_replace("days", "dias", $dias);
		$dias              = str_replace("day", "dia", $dias);
		$posto_codigo      = trim(pg_fetch_result($resSubmit,$i,'posto_codigo'));
		$admin_pagto       = trim(pg_fetch_result($resSubmit,$i,'admin_pagto'));
		$posto_nome        = trim(pg_fetch_result($resSubmit,$i,'posto_nome'));
		$nf_mao_de_obra    = trim(pg_fetch_result($resSubmit,$i,'nota_fiscal_mao_de_obra'));
		$cidade            = trim(pg_fetch_result($resSubmit,$i,'contato_cidade'));
		$estado            = trim(pg_fetch_result($resSubmit,$i,'contato_estado'));

		if (strlen($extrato) > 0) {

			$sql = "SELECT count(*) as existe
				FROM   tbl_extrato_lancamento
				WHERE  extrato = $extrato
				and    fabrica = $login_fabrica";
    			$res_avulso = pg_query($con,$sql);

    			if (pg_num_rows($res_avulso) > 0) {
	    			if (pg_fetch_result($res_avulso, 0, 'existe') > 0) $cor = "#FFE1E1";
			}

		   	$sql = "SELECT
			    TO_CHAR(MIN(tbl_os.data_digitacao),'DD/MM/YYYY') AS digitacao_inicial ,
				    TO_CHAR(MAX(tbl_os.data_digitacao),'DD/MM/YYYY') AS digitacao_final
				    FROM tbl_os
				    JOIN tbl_os_extra USING(os)
				    WHERE extrato = $extrato";
		    	$res_data = pg_query($con,$sql);

		    	if (pg_num_rows($res_data) > 0) {
				$digitacao_inicial = trim(pg_fetch_result($res_data,0,'digitacao_inicial'));
			    	$digitacao_final   = trim(pg_fetch_result($res_data,0,'digitacao_final'));
		    	}

		}

		if($login_fabrica == 1){
			$sql_admin = "select login from tbl_admin where admin = $admin_pagto";
			$res_admin = pg_query($con, $sql_admin);
			$nome_admin_pagto = pg_fetch_result($res_admin, 0, 'login');

			$totalTx =  somaTxExtratoBlack($extrato);
			$total+=$totalTx;
		}

		$total_final = $total + $total_final;
		$total_qtd_os = $total_qtd_os + $qtd_os;

		if ($situacao == "FINANCEIRO") {

			$body .= "$codigo_protocolo;";

			$tDocs          = new TDocs($con, $login_fabrica,"protocolo");
			$realProtocolo  = (int)$codigo_protocolo;
			$qtdeAnexos     = $tDocs->getDocumentsByRef($realProtocolo)->attachListInfo;

			$escreve = "";
			foreach ($qtdeAnexos as $anexos) {
				$nome_anexo = $anexos['filename'];
				if (strstr($nome_anexo,'aprovar')) {
					$escreve = $anexos['link'];
				} else if (strstr($nome_anexo,'reprovar')) {
					$escreve = "REPROVADO";
				} else {
					$escreve =  "PENDENTE";
				}
			}
			$body .= "$escreve;";
		}

		$body .= "$extrato_numero;$posto_codigo;$posto_nome;";
 
		if($login_fabrica == 1){
			$body .= "$cidade;$estado;";
		}

		if (strlen($digitacao_inicial) && strlen($digitacao_final) > 0){
			$body .= "$digitacao_inicial a $digitacao_final;";
		}else{
			$body .= "$data_extrato;";
		}

		$body .= "$nf_mao_de_obra;$total;$data_extrato;$data_aprovado;$data_financeiro;$dias;$qtd_os";

		if($login_fabrica == 1){
			$body .= ";$nome_admin_pagto";
		}

		$body .= "\n";
	    }

	    fwrite($file,$body);

	    if (file_exists("/tmp/{$filename}")) {
		    system("mv /tmp/{$filename} xls/{$filename}");
		    echo "xls/{$filename}";
	    }

	    exit;
    }
}


$layout_menu = "auditoria";
$title = "RELATÓRIO DE PAGAMENTOS";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
	);

include("plugin_loader.php");

?>

<script type="text/javascript">


$(function(){
	$("#data_inicial").datepicker();
	$("#data_final").datepicker();

        Shadowbox.init();

		//$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));

		$("#marcar_todas").click(function(){
		    if ($(this).is(":checked")) {
			$(".agrupar").prop("checked","checked");
		    } else {
			$(".agrupar").prop("checked","");
		    }
		});

        $(".auditar").click(function(){

            let protocolo = $(this).attr("protocolo");

            Shadowbox.open({
                content: "detalhe_protocolo.php?protocolo="+protocolo+"&tipo=auditar",
                player: "iframe",
                title: "Detalhes do protocolo "+protocolo,
                height: 1000,
                width: 2000
            });

        });

		$("#gerar_protocolo").click(function(e){
            e.preventDefault();

            var extratos = [];
            var protocolos = $("input[name^=agrupar_]:checked").serializeArray();
            var baixar = false;

            $.each(protocolos,function(k,v){
                extratos.push(v.value);
            });

            if (confirm("Deseja baixar o arquivo do protocolo?")) {
                baixar = true;
            }

            $.ajax({
                url:"relatorio_media_pagamento.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    tipo:"gerar_protocolo",
                    baixar:baixar,
                    extratos
                },
                beforeSend:function(jqXHR){
                    $("#gerar_protocolo").attr("disabled",true);
                    if (extratos.length == 0) {
                        alert("Por favor, escolha um extrato para fazer a ação");
                        jqXHR.abort();
                        $("#gerar_protocolo").attr("disabled",false);
                        return false;
                    }

                }
            })
            .done(function(data){
                if (!data.email) {
                    alert("Erro ao enviar email");
                }
                if(data.baixar) {
                    window.open(data.arquivoProtocolo);
                }
                $("input[name=acao]").val("PESQUISAR");
                $("form[name=frm_consulta]").submit();
            })
            .fail(function(){
                alert("Não foi possível gerar o protocolo.");
                $("#gerar_protocolo").attr("disabled",false);
            });
		});
	});
	function Redirect(pedido) {
		window.open('detalhe_pedido.php?pedido=' + pedido,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<br>


<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>

<form name="frm_consulta" method="POST" action="<?=$PHP_SELF?>" class='form-search form-inline tc_formulario'>

	<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div> <br>

	<div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />                                             
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'><?=traduz('Nome Posto')?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $posto_nome ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
		<div class='span2'></div>
		<div class='span8'>
			 <?php echo traduz("Situação do Extrato");?>                                                                    
		</div>
		<div class='span2'></div>
    </div>

    <div class='row-fluid'>                                                                                                        
		<div class='span2'></div>
		<div class='span2'>
			<label class="radio">
				<input type="radio" name="situacao" value="GERACAO" <?if($situacao=="GERACAO") echo "checked";?>>
				 <?php echo traduz("Aberto");?>
			</label>
		</div>
		<div class='span3'>
			<label class="radio">
				<input type="radio" name="situacao" value="APROVACAO" <?if($situacao=="APROVACAO") echo "checked";?>>
				<?php echo traduz("Aprovado");?>
			</label>
		</div>
		<div class='span3'>
				<label class="radio">
				<input type="radio" name="situacao" value="FINANCEIRO" <?if($situacao =="FINANCEIRO" or $situacao=="") echo "checked";?> >
				<?php echo traduz("Enviado p/ Financeiro");?>
			</label>
		</div>
		<div class='span2'></div>
    </div>

    <div class='row-fluid'>
		<div class='span2'></div>
		<div class='span8'>
			 <?php echo traduz("Situação do Protocolo");?>                                                                    
		</div>
		<div class='span2'></div>
    </div>

    <div class='row-fluid'>                                                                                                        
		<div class='span2'></div>
		<div class='span2'>
			<label class="radio">
				<input type="radio" name="agrupado" value="TODOS" <?if($agrupado == "TODOS" or $agrupado == "") echo "checked";?>>
				 <?php echo traduz("TODOS");?>
			</label>
		</div>
		<div class='span3'>
			<label class="radio">
				<input type="radio" name="agrupado" value="APROVADOS" <?if($agrupado == "APROVADOS") echo "checked";?>>
				<?php echo traduz("Aprovado");?>
			</label>
		</div>
		<div class='span3'>
				<label class="radio">
				<input type="radio" name="agrupado" value="REPROVADOS" <?if($agrupado =="REPROVADOS") echo "checked";?> >
				<?php echo traduz("Reprovado");?>
			</label>
		</div>
		<div class='span2'></div>
    </div>

    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>

</form>
</div>
<?php
if (isset($resSubmit)) {


	if (pg_num_rows($resSubmit) > 0) {
?>
		<table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna'>
					<th></th>
					<th>Extrato Avulso</th>
				</tr>
			</thead>
		</table>

		<table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna'>
					<?php
						if ($situacao == "FINANCEIRO") {
					?>
							<th>TODOS <br> <input type='checkbox' name='marcar_todas' id='marcar_todas' /></th>
							<th>Protocolo</th>
							<th>Status</th>
					<?php
						}
					?>

					<th>Extrato</th>
					<th>Código</th>
					<th>Posto</th>

					<?php
						if($login_fabrica == 1){
					?>
							<th>Cidade</th>
							<th>Estado</th>

					<?php
						}
					?>

					<th>Período Digitação</th>
					<th>NF Autorizado</th>
					<th>Total</th>
					<th>Geração</th>
					<th>Aprovação</th>
					<th>Financeiro</th>
					<th>Dias</th>
					<th>Qtde OS</th>

					<?php
						if($login_fabrica == 1){
					?>
							<th>Admin</th>
					<?php
						}
					?>
                    <th>Ações</th>
				</tr>
			</thead>
<?php
		$total_final = "";
						$total_qtd_os = 0;
		echo "<tbody>";

		for ($x = 0; $x < pg_num_rows($resSubmit); $x++) {
			$extrato           = trim(pg_fetch_result($resSubmit,$x,'extrato'));
			$extrato_numero         = trim(pg_fetch_result($resSubmit,$x,'extrato_numero'));
			$codigo_protocolo    = trim(pg_fetch_result($resSubmit,$x,'codigo_protocolo'));
			$total             = trim(pg_fetch_result($resSubmit,$x,'total'));
			$qtd_os             = trim(pg_fetch_result($resSubmit,$x,'qtd_os'));
			if(strlen($qtd_os) == 0) $qtd_os = 0;
			$data_extrato      = trim(pg_fetch_result($resSubmit,$x,'data_extrato'));
			$data_aprovado     = trim(pg_fetch_result($resSubmit,$x,'data_aprovado'));
			$data_financeiro   = trim(pg_fetch_result($resSubmit,$x,'data_financeiro'));
			$dias              = trim(pg_fetch_result($resSubmit,$x,'dias'));
			$dias              = str_replace("days", "dias", $dias);
			$dias              = str_replace("day", "dia", $dias);

			$posto_codigo      = trim(pg_fetch_result($resSubmit,$x,'posto_codigo'));
			$admin_pagto	   = trim(pg_fetch_result($resSubmit,$x,'admin_pagto'));
			$posto_nome        = trim(pg_fetch_result($resSubmit,$x,'posto_nome'));
			$nf_mao_de_obra    = trim(pg_fetch_result($resSubmit,$x,'nota_fiscal_mao_de_obra'));
			$cidade 	   = trim(pg_fetch_result($resSubmit,$x,'contato_cidade'));
			$estado 	   = trim(pg_fetch_result($resSubmit,$x,'contato_estado'));
            $codigo_status     = trim(pg_result($resSubmit,$x,'codigo_status'));

			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    fabrica = $login_fabrica";
				$res_avulso = pg_query($con,$sql);

				if (pg_num_rows($res_avulso) > 0) {
					if (pg_fetch_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
				}
				$sql = "SELECT
					TO_CHAR(MIN(tbl_os.data_digitacao),'DD/MM/YYYY') AS digitacao_inicial ,
					TO_CHAR(MAX(tbl_os.data_digitacao),'DD/MM/YYYY') AS digitacao_final
					FROM tbl_os
					JOIN tbl_os_extra USING(os)
					WHERE extrato = $extrato";
    				$res_data = pg_query($con,$sql);

				if (pg_num_rows($res_data) > 0) {
					$digitacao_inicial = trim(pg_fetch_result($res_data,0,digitacao_inicial));
					$digitacao_final   = trim(pg_fetch_result($res_data,0,digitacao_final));
				}

			}

			if($login_fabrica == 1){
				$sql_admin = "select login from tbl_admin where admin = $admin_pagto";
				$res_admin = pg_query($con, $sql_admin);
				$nome_admin_pagto = pg_fetch_result($res_admin, 0, 'login');

                		$totalTx =  somaTxExtratoBlack($extrato); 
				$total+=$totalTx;
			}

			$total_final = $total + $total_final;
			$total_qtd_os = $total_qtd_os + $qtd_os;

			$bold = ($total > 3000) ? "style='font-weight:bold;'" : "";
			echo "<tr height='15' bgcolor='$cor' $bold>";
			if ($situacao == "FINANCEIRO") {
                echo "<td>";
                if (empty($codigo_protocolo)) {
                    echo "<input type='checkbox' name='agrupar_$x' value='$extrato' class='agrupar' />";
                }
                echo "</td>";

                echo "<td nowrap>$codigo_protocolo</td>";

                $tDocs          = new TDocs($con, $login_fabrica,"protocolo");
                $realProtocolo  = (int)$codigo_protocolo;
                $qtdeAnexos     = $tDocs->getDocumentsByRef($realProtocolo)->attachListInfo;

                echo "<td>";
                $escreve = "";
                foreach ($qtdeAnexos as $anexos) {
                    $nome_anexo = $anexos['filename'];
                    if (strstr($nome_anexo,'aprovar')) {
                        echo "<a href='".$anexos['link']."' target='_blank'><img src='../imagens/icone_pdf.jpg' border='0' style='width:35px;' /></a>";
                        $escreve = "";
                    } else if (strstr($nome_anexo,'reprovar')) {
                        $escreve = "REPROVADO";
                    } else {
                        $escreve =  "PENDENTE";
                    }
                }
                echo $escreve;
                echo "</td>";
            }
			echo "<td nowrap>" .  $extrato_numero . "</td>";
			echo "<td nowrap>" . $posto_codigo . "</td>";
			echo "<td nowrap align='left'>" . $posto_nome . "</td>";

			if($login_fabrica == 1){
				echo "<td nowrap>" .  $cidade . "</td>";
				echo "<td nowrap>" .  $estado . "</td>";
			}

			echo "<td nowrap>";
			if (strlen($digitacao_inicial) && strlen($digitacao_final) > 0) echo $digitacao_inicial . " a " . $digitacao_final;
			else                                                            echo $data_extrato;
			echo "</td>";
			echo "<td nowrap>" . $nf_mao_de_obra . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($total,2,",",".") . "</td>";
			echo "<td nowrap>" . $data_extrato . "</td>";
			echo "<td nowrap>" . $data_aprovado . "</td>";
			echo "<td nowrap>" . $data_financeiro . "</td>";

			echo "<td nowrap>" . $dias . "</td>";
			echo "<td nowrap>" . $qtd_os . "</td>";
			if($login_fabrica == 1){
				echo "<td>$nome_admin_pagto</td>";
			}else{
				echo "<td></td>";
			}

            $permissoesLogin = $classProtocolo->getPermissoesLogin($login_admin);

            if ($codigo_status == "an_pv" && in_array("analista_posvenda", $permissoesLogin)) { ?>
                <td>
                    <button type="button" class="auditar" protocolo="<?= $codigo_protocolo ?>">
                        Auditar
                    </button>
                </td>
            <?php
            } else {
                echo "<td></td>";
            }
			echo "</tr>";

		}

		echo "</tbody>";

		echo "<tfoot>";
		echo "<tr height='15' bgcolor='$cor'>";
				if ($situacao == "FINANCEIRO") {
			echo "<td nowrap>";
			if ($agrupado == "TODOS") {
			   echo "<button id='gerar_protocolo' name='gerar_protocolo'>Gerar Protocolo</button>";
			}
			echo "</td>";
			echo "<td nowrap> &nbsp; </td>";
			echo "<td nowrap> &nbsp; </td>";
				}
		    echo "<td nowrap> &nbsp; </td>";
				echo "<td nowrap align='left'>Total</td>";
				echo "<td nowrap>&nbsp;</td>";
				echo "<td nowrap>&nbsp;</td>";
				echo "<td nowrap>&nbsp;</td>";

				if($login_fabrica == 1){
					echo "<td nowrap>&nbsp;</td>";
					echo "<td nowrap>&nbsp;</td>";
				}

				echo "<td nowrap align='right'>R$ " . number_format($total_final,2,",",".") . "</td>";
				echo "<td nowrap>&nbsp;</td>";
				echo "<td nowrap>&nbsp;</td>";
				echo "<td nowrap>&nbsp;</td>";
				echo "<td nowrap>&nbsp;</td>";
				echo "<td nowrap>" . $total_qtd_os . "</td>";
				echo "<td nowrap>&nbsp;</td>";
				echo "</tr>";
			echo "</tfoot>";

				echo "</table>";

				$jsonPOST = excelPostToJson($_POST);

	?>

			<br />

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo CSV</span>
			</div>
<?php
	}else{
		echo '
                 <div class="container">
	                   <div class="alert">
	                           <h4>Nenhum resultado encontrado</h4>
	                   </div>
	         </div>';
	}
}

echo "<br>";

include "rodape.php";
?>
