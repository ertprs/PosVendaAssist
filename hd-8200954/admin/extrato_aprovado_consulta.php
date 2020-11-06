<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
include_once '../class/communicator.class.php';

include_once "../class/tdocs.class.php";
$admin_privilegios="financeiro";
include 'autentica_admin.php';



$msg_erro = "";

if(isset($_POST["recusaExtrato"])){

	foreach( $_POST['aprova'] as $k => $aprovar ) {

		$sql = "DELETE from tbl_extrato_status where extrato = $aprovar ";
		$res = pg_query($con, $sql);
		$msg_erro = pg_errormessage($con);

		$sql = " update tbl_extrato_extra set admin = null where extrato = $aprovar ";
		$res = pg_query($con, $sql);
		$msg_erro .= pg_errormessage($con);

		$sql = " update tbl_extrato set aprovado = null where extrato = $aprovar ";
		$res = pg_query($con, $sql);
		$msg_erro .= pg_errormessage($con);

		$sql = " DELETE  from tbl_os_status WHERE  extrato = $aprovar ";
		$res = pg_query($con, $sql);
		$msg_erro .= pg_errormessage($con);
	}
		echo empty($msg_erro) ? 'ok' : 'Erro ao aprovar extrato';
		return;
}


if (filter_input(INPUT_POST,'aprovaExtrato')) {
// print_r($_POST);exit;
	if (count($_POST['aprova']) == 0) {

		echo 'Nenhum extrato para aprovar';
		return;

	}
    $tipo = filter_input(INPUT_POST,'tipo');

	$sql = "select extract(DOW from current_timestamp ) as data";
	$res = pg_query($con,$sql);
	$data = pg_fetch_result($res,0,data);
	$day = "1 day";
	if ($data == 5) { $day = "3 day";}
	if ($data == 6) { $day = "2 day";}

	foreach( $_POST['aprova'] as $k => $aprovar ) {
        $sqlP = "SELECT protocolo FROM tbl_extrato WHERE extrato = $aprovar";
        $resP = pg_query($con,$sqlP);
        $protocoloGrava = pg_fetch_result($resP,0,protocolo);

		pg_query($con, 'BEGIN TRANSACTION');

        if ($tipo == "liberadas") {
            $sql = "SELECT extrato
                    FROM tbl_extrato_financeiro
                    WHERE extrato = $aprovar" ;
                $res = pg_query($con,$sql);

            if (pg_num_rows($res) ==  0) {

                $sql = "INSERT INTO tbl_extrato_financeiro (
                        extrato   ,
                        valor     ,
                        data_envio,
                        admin_pagto
                    ) VALUES (
                        $aprovar,
                    (
                        SELECT to_char(tbl_extrato.total, '999999990.99') AS total
                        FROM   tbl_extrato
                        WHERE  tbl_extrato.extrato = $aprovar
                        AND    tbl_extrato.fabrica = $login_fabrica
                    )::float ,
                    current_timestamp + interval '$day',
                    $login_admin
                );";
            } else {
                $sql = "UPDATE tbl_extrato_financeiro SET
                    valor = to_char(tbl_extrato.total, '999999990.99')::float  ,
                    data_envio = current_timestamp + interval '$day',
                    admin_pagto = {$login_admin}
                    FROM tbl_extrato
                    WHERE tbl_extrato.extrato = $aprovar
                    AND   tbl_extrato.extrato = tbl_extrato_financeiro.extrato ";

            }
        } else {

            $nf_autorizado       = $_POST["nf_autorizado_$aprovar"];
            if (empty($nf_autorizado)) {
                $msg_erro[] = "Não foi encontrado NF do autorizado no extrato $protocoloGrava";
            } else {
                $sql = "UPDATE tbl_extrato_extra SET
                        nota_fiscal_mao_de_obra = '$nf_autorizado'
                        WHERE extrato = $aprovar";

                $res = pg_query($con,$sql);
                if (pg_last_error($con)) {
                    $msg_erro[] = "Não foi possível gravar NF do autorizado no extrato $protocoloGrava";
                }

                $sql = "
                    INSERT INTO tbl_extrato_status (
                        extrato,
                        fabrica,
                        data,
                        obs
                    ) VALUES (
                        $aprovar,
                        $login_fabrica,
                        CURRENT_TIMESTAMP,
                        'Aguardando aprovação online'
                    )";
                $extratoEmail[] = $protocoloGrava;
            }

        }
        $res = pg_query($con,$sql);

        if (pg_last_error($con)) {
            $msg_erro[] = "Erro ao atualizar extrato $protocoloGrava";
        } else {

            $sql = (empty($msg_erro) ? 'COMMIT' :  'ROLLBACK');
            pg_query($con, $sql);
        }
    }

    if (count($msg_erro) > 0) {
        $erro = implode("\n",$msg_erro);
    }

    if ($login_fabrica == 1 && empty($erro) && $tipo == "aprovadas") {
        $sqlMail = "
            SELECT  email
            FROM    tbl_admin
            WHERE   fabrica = $login_fabrica
            AND     JSON_FIELD('aprova_extrato',parametros_adicionais) = 't';
        ";

        $resMail = pg_query($con,$sqlMail);
        $emails = pg_fetch_all_columns($resMail,0);
        $sqlAdmin = "
            SELECT  nome_completo
            FROM    tbl_admin
            WHERE   admin = $login_admin
        ";
        $resAdmin = pg_query($con,$sqlAdmin);
        $nomeAdmin = pg_fetch_result($resAdmin,0,nome_completo);

        $listMail = implode(",",$emails);
        $listExtratos = implode(", ",$extratoEmail);

        $mailer = new TcComm($externalId);
        $body = "Os extratos abaixo foram aprovados por ".$nomeAdmin." e estão aguardando assinatura eletrônica:<br />".$listExtratos.".";
//             $mailer->addEmailDest($listMail);



        foreach($emails as $email) {
	        if (!$mailer->sendMail("$email","APROVAÇÃO DE EXTRATO",$body,"noreply@telecontrol.com.br")) {
	            $erro = "Não foi possível enviar o email";
	        }
    	}
    }

    echo empty($erro) ? 'ok' : $erro ;
    return;

}

/*HD 15001 - Não contar mais OS*/
if($ajax=='conta'){
			$sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
			$rres = pg_query($con,$sql);
			if(pg_num_rows($rres)>0){
				$qtde_os = pg_fetch_result($rres,0,qtde_os);
			}
			echo "ok|$qtde_os";
			exit;
}
$desbloquear = $_GET['desbloquear'];
$bloquear = $_GET['bloquear'];
if(strlen($bloquear)>0 or strlen($desbloquear)>0){
	if(strlen($bloquear)>0){
		$acao ="'t'";
		$extrato = $bloquear;
	}
	if(strlen($desbloquear)>0){
		$acao ="'f'";
		$extrato = $desbloquear;
	}
	$sql = "UPDATE tbl_extrato set bloqueado = $acao
			where extrato = $extrato
			and fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	if($acao =="'t'"){
		$xsql = "SELECT tbl_posto.email, tbl_posto_fabrica.codigo_posto from tbl_posto join tbl_extrato using(posto) join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica= $login_fabrica where extrato = $extrato";
		$xres = pg_query($con,$xsql);
		$xemail_posto = pg_fetch_result($xres,0,email);
		$xcodigo_posto= pg_fetch_result($xres,0,codigo_posto);

		$xsql = "SELECT protocolo from tbl_extrato where extrato = $extrato";
		$xres = pg_query($con,$xsql);
		$xprotocolo = pg_fetch_result($xres,0,protocolo);

		$xsql = "SELECT nome_completo, fone, email from tbl_admin where admin=$login_admin and fabrica=$login_fabrica";
		$xres = pg_query($con,$xsql);
		$xnome_completo = pg_fetch_result($xres,0,nome_completo);
		$xfone = pg_fetch_result($xres,0,fone);
		$xemail_admin = pg_fetch_result($xres,0,email);
		//$xemail_admin = "fabiola.oliveira@bdk.com";
		$xemail_admin = "ellen_batista@blackedecker.com.br"; // HD 310052 - Interação 1

		$remetente    = "Black&Decker <$xemail_admin>";
		$destinatario = "$xemail_posto";
		$assunto      = "Posto $xcodigo_posto seu extrato $xprotocolo foi bloqueado";

		$msg_email = "Prezado Assistente,<BR><BR>Recebemos a documentação referente ao extrato $xprotocolo, porém temos extrato(s) gerado(s) anteriormente para o seu posto que ainda estão em aberto no sistema. Gentileza entrar no link extrato e verificar os extratos com o status Pendente / Aguardando documentação.
		<BR><BR>
		O extrato $xprotocolo será bloqueado até recebermos um posicionamento sobre os outros extratos em aberto.
		<BR><Obrigado>BR,
		<BR><BR>
		Departamento de Pagamento em Garantia<BR>
		Stanley Black & Decker<BR>
		Telefone: (34) 3318-3921<BR>
		E-mail: pagamento.garantia@sbdbrasil.com.br<BR>";
		$headers="Return-Path: <$xemail_admin>\r\nFrom:".$remetente."\r\nBcc:$xemail_admin \r\nContent-type: text/html\n";

		if ( mail($destinatario, utf8_encode($assunto), utf8_encode($msg_email), $headers) ) {
			/*echo "<script language='JavaScript'>\n";
			echo "window.close();";
			echo "</script>";		*/
		}else{
			echo "erro";
		}
	}

}
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>3){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$sql .= " LIMIT 50 ";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];
if (strlen($_GET["extrato"])  > 0) $extrato = trim($_GET["extrato"]);

if($btnacao=="filtrar"){
	if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);
	if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0)  $data_final = trim($_GET["data_final"]);


	if($login_fabrica == 1){

        $estados   = $_GET['estados'];
        $regiao    = $_GET['regiao'];
        $status_extrato = filter_input(INPUT_GET,"status_extrato");

        $count_regiao = count($regiao);
        $i=1;
        foreach($regiao as $linha){
            $dados .= $linha;

            if($i < $count_regiao){
                $dados .= ", ";
            }
            $i++;
        }
        $dados = str_replace(', ', "', '", "$dados");
        $dados = "'$dados'";


        $count_estados = count($estados);
        $e=1;
        foreach ($estados as $linha_estados) {
            $dados_estados .= $linha_estados;

            if($e < $count_estados){
                $dados_estados .= ", ";
            }
            $e++;
        }
        $dados_estados = str_replace(', ', "', '", "$dados_estados");
        $dados_estados = "'$dados_estados'";

        if($count_estados > 0 and $count_regiao > 0){
            $conteudo = $dados . ", ". $dados_estados;
        }elseif($count_regiao > 0){
            $conteudo = $dados;
        }elseif($count_estados > 0){
            $conteudo = $dados_estados;
        }

        if(strlen(trim($conteudo))>0){
            $where_estado_regiao = " and tbl_posto_fabrica.contato_estado in ($conteudo) ";
        }
    }

	if (strlen($data_inicial) > 0 AND strlen($data_final) > 0) {
	//Início Validação de Datas
		if(strlen($msg_erro)==0){
			list($d,$m,$y) = explode ("/", $data_inicial );//tira a barra
            if (!checkdate($m,$d,$y)) {
                $msg_erro = "Data Inválida";
            } else {
                $x_data_inicial = $y."-".$m."-".$d;
            }
		}
		if (strlen($msg_erro)==0) {
			list($d,$m,$y) = explode ("/", $data_final );//tira a barra
            if (!checkdate($m,$d,$y)) {
                $msg_erro = "Data Inválida";
            } else {
                $x_data_final = $y."-".$m."-".$d;
            }
		}

        if ($x_data_final < $x_data_inicial) {
            $msg_erro = "Data Inválida.";
        }
	} else {
		if (empty($_REQUEST['posto_codigo'])) {
            if ($login_fabrica == 1) {
                if ( empty($_REQUEST["extrato"])) {
                    $msg_erro = "Data inválida";
                }
            } else {
                $msg_erro = "Data inválida";
            }
		}
	}
}
##### Pesquisa de produto #####
if (!empty($_POST["posto_codigo"])) $posto_codigo  = trim($_POST["posto_codigo"]);
if (!empty($_GET["posto_codigo"]))  $posto_codigo  = trim($_GET["posto_codigo"]);
if (!empty($_POST["posto_nome"]))   $posto_nome    = trim($_POST["posto_nome"]);
if (!empty($_GET["posto_nome"]) )   $posto_nome    = trim($_GET["posto_nome"]);
if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {

	$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto.posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
	if (strlen($posto_codigo) > 0) $sql .= " AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
	if (strlen($posto_nome) > 0)   $sql .= " AND   tbl_posto.nome ILIKE '%$posto_nome%';";

	$res = pg_query($con,$sql);
	if (pg_num_rows($res) == 1) {
		$posto        = pg_fetch_result($res,0,posto);
		$posto_codigo = pg_fetch_result($res,0,codigo_posto);
		$posto_nome   = pg_fetch_result($res,0,nome);
		$msg_erro = '';
	}else{
		$msg_erro .= " Posto não encontrado. ";
	}
}

if($btnacao=="filtrar" AND empty($data_inicial) AND empty($data_final) AND empty($posto_codigo) AND empty($extrato) ){
	$msg_erro = "Informe mais parâmetros para pesquisa";
}

if (strlen($_GET["aprovar"]) > 0) $aprovar = $_GET["aprovar"]; // é o numero do extrato

$btn_aprova = $_POST['btn_aprova'];
if(strlen($btn_aprova)>0){
    $aprovar = filter_input(INPUT_POST,'extrato_aprovado');
    $liberar = filter_input(INPUT_POST,'extrato_liberar');
    $nf_autorizado = filter_input(INPUT_POST,'nf_autorizado');
}

if (strlen($aprovar) > 0 ) {
	$sql = "select extract(DOW from current_timestamp ) as data";
	$res = pg_query($con,$sql);
	$data = pg_fetch_result($res,0,data);
	$day = "1 day";
	if ($data == 5) { $day = "3 day";}
	if ($data == 6) { $day = "2 day";}

	$sql = "SELECT extrato FROM tbl_extrato_status WHERE extrato = $aprovar AND pendente IS FALSE and obs like '%online' and admin_conferiu IS NOT NULL";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) == 0){
		$sql = "UPDATE tbl_extrato_extra SET
			nota_fiscal_mao_de_obra = '$nf_autorizado'
			WHERE extrato = $aprovar";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro) == 0){
			$sql = "
			    INSERT INTO tbl_extrato_status (
				extrato,
				fabrica,
				data,
				obs
			    ) VALUES (
				$aprovar,
				$login_fabrica,
				CURRENT_TIMESTAMP,
				'Aguardando aprovação online'
			    )";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}else{
		$sql = "SELECT extrato
			FROM tbl_extrato_financeiro
			WHERE extrato = $aprovar" ;
		$res = pg_query($con,$sql);

		$res = pg_query($con,"BEGIN TRANSACTION");

		if(pg_num_rows($res) ==  0) {
			$sql = "INSERT INTO tbl_extrato_financeiro (
						extrato   ,
						valor     ,
						data_envio,
			    admin_pagto
					) VALUES (
						$aprovar,
						(
							SELECT to_char(tbl_extrato.total, '999999990.99') AS total
							FROM   tbl_extrato
							WHERE  tbl_extrato.extrato = $aprovar
							AND    tbl_extrato.fabrica = $login_fabrica
						)::float ,
						current_timestamp + interval '$day',
			    {$login_admin}
					);";
		}else{
			$sql = "UPDATE tbl_extrato_financeiro SET
					    valor = to_char(tbl_extrato.total, '999999990.99')::float  ,
						data_envio = current_timestamp + interval '$day',
			    admin_pagto = {$login_admin}
				FROM tbl_extrato
				WHERE tbl_extrato.extrato = $aprovar
				AND   tbl_extrato.extrato = tbl_extrato_financeiro.extrato ";
		}

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
    if (empty($msg_erro)) {
        $res = pg_query($con,"COMMIT TRANSACTION");
    } else {
        $msg_erro = 'Erro ao tentar aprovar este extrato';
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

if (!empty($liberar)) {
    pg_query($con,"BEGIN TRANSACTION");
    $nf_autorizado       = filter_input(INPUT_POST,'nf_autorizado');
    if (empty($nf_autorizado)) {
        $msg_erro = "Obrigatório Nota Fiscal do autorizado";
    } else {
        $sqlP = "SELECT protocolo FROM tbl_extrato WHERE extrato = $liberar";
        $resP = pg_query($con,$sqlP);
        $protocolo = pg_fetch_result($resP,0,protocolo);

        $sql = "UPDATE tbl_extrato_extra SET
                nota_fiscal_mao_de_obra = '$nf_autorizado'
                WHERE extrato = $liberar";
        $res = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);

        $sql = "
            INSERT INTO tbl_extrato_status (
                extrato,
                fabrica,
                data,
                obs
            ) VALUES (
                $liberar,
                $login_fabrica,
                CURRENT_TIMESTAMP,
                'Aguardando aprovação online'
            )";
        $res = pg_query($con,$sql);
    }

    if (pg_last_error($con) || !empty($msg_erro)) {
        pg_query($con, "ROLLBACK TRANSACTION");
    } else {
        pg_query($con,"COMMIT TRANSACTION");

        if ($login_fabrica == 1) {
            $sqlMail = "
                SELECT  email
                FROM    tbl_admin
                WHERE   fabrica = $login_fabrica
                AND     JSON_FIELD('aprova_extrato',parametros_adicionais) = 't';
            ";
            $resMail = pg_query($con,$sqlMail);
            $emails = pg_fetch_all_columns($resMail,0);

            $sqlAdmin = "
                SELECT  nome_completo
                FROM    tbl_admin
                WHERE   admin = $login_admin
            ";
            $resAdmin = pg_query($con,$sqlAdmin);
            $nomeAdmin = pg_fetch_result($resAdmin,0,nome_completo);

            $listMail = implode(",",$emails);

            $mailer2 = new TcComm($externalId);
            $body = "O extrato abaixo foi aprovado por ".$nomeAdmin." e está aguardando assinatura eletrônica:<br />".$protocolo.".";
            if (!$mailer2->sendMail("$listMail","APROVAÇÃO DE EXTRATO",$body,"noreply@telecontrol.com.br")) {
                $erro = "Não foi possível enviar o email";
            }
        }
    }
}

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS APROVADOS";

include "cabecalho.php";

include "../js/js_css.php";

?>

<p>

<style type="text/css">

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


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

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

.ms-choice {
	border-radius: 0px !important;
	border-color: #888 !important;
	border-style: solid;
	border-width: 1px !important;
	background-color:#F0F0F0 !important;
	height: 18px !important;
}
</style>

<link rel="stylesheet" href="css/multiple-select.css" />
<script src="js/jquery.multiple.select.js"></script>
<script language="JavaScript">
    $(function() {
        $('#ms').change(function() {
            console.log($(this).val());
        }).multipleSelect({
            width: '130px'
        });
    });

    $(function() {
        $('#estados').change(function() {
            console.log($(this).val());
        }).multipleSelect({
            width: '150px'
        });
    });
</script>


<script language="JavaScript">
var checkflag = "false";
function AbrirJanelaObs (extrato) {
	var largura  = 750;
	var tamanho  = 550;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status_aprovado.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
function check(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}
function AbrirJanelaObs (extrato) {
	var largura  = 750;
	var tamanho  = 550;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status_aprovado.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>

<? //include "javascript_pesquisas.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<script language="JavaScript">
$().ready(function() {

	marcado = false;

	$("#marca_todos").click(function(e) {

		if ( marcado === false ) {

			$("input[type=checkbox][name^=aprova]").each(function(){

				$(this).attr('checked',true);

			});

			marcado = true;

		} else {

			$("input[type=checkbox][name^=aprova]").each(function(){

				$(this).attr('checked',false);

			});

			marcado = false;

		}

	});

	$("#aprova_extratos").click(function(e) {

		if ( confirm("Deseja mesmo aprovar os extratos selecionados?") ) {

			$.post('<?=$PHP_SELF?>', $(".check_aprova").serialize() + '&tipo=<?=$status_extrato?>&aprovaExtrato=true&' + $(".nf_autorizado").serialize(), function(data){

				if (data == 'ok') {

					alert('Extratos Aprovados com sucesso');
					window.location='<?=$PHP_SELF?>';

				} else {

					alert(data);

				}

			});

		}

		e.preventDefault();

	});


	$("#recusa_extratos").click(function(e) {
		if ( confirm("Deseja mesmo recusar os extratos selecionados?") ) {

			$.post('<?=$PHP_SELF?>', $(".check_aprova").serialize() + '&recusaExtrato=true&' + $(".nf_autorizado").serialize(), function(data){
				if (data == 'ok') {
					alert('Extratos Recusados com sucesso');
					window.location='<?=$PHP_SELF?>';
				} else {
					alert(data);
				}
			});
		}
		e.preventDefault();
	});



	$("input[name^=nf_autorizado_]").change(function(e){

		if ($(this).val() != '') {

			$(this).addClass('nf_autorizado');

		} else {

			$(this).removeClass('nf_autorizado');

		}

	});

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[0]) ;
		//alert(data[2]);
	});

});

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http_forn = new Array();
			/*HD 15001 - Não contar mais OS*/
function conta_os(extrato,div) {
	var ref = document.getElementById(div);
	ref.innerHTML = "Espere...";
	url = "<?=$PHP_SELF?>?ajax=conta&extrato="+extrato;
	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4)
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
						ref.innerHTML = response[1];
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}

function mostraDados(posto,linha){
	var classe = posto+"_"+linha;
	if($("."+classe).is(":visible")){
		$("."+classe).hide();
	}else{
		$("."+classe).show();
	}
}

</script>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<?
$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

if (strlen($msg_erro) > 0) {
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>";
	echo "<tr>";
	echo "<td>" . $msg_erro . "</td>";
	echo "</tr>";
	echo "</table>";

}
echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario' >\n";
echo"<tr class='titulo_tabela'><td colspan='4'>Parâmetros de Pesquisa</td></tr>";
echo "<TR class='subtitulo'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Postos com Extratos Fechados por Período";
echo "	</TD>";
echo "<TR>\n";

if($login_fabrica == 1){
	echo "<TR>\n";
	echo "<TD width='130'>&nbsp;</TD>";
	echo "<TD width='130'   valign='bottom'>Extrato<br>";
	echo "	<INPUT size='12' TYPE='text' NAME='extrato' id='extrato' value='$extrato' class='frm'>\n </td>";
	echo "</TR>";
}

echo "<TR>";
echo "<TD width='130'>&nbsp;</TD>";
echo "	<TD width='130'   valign='bottom'>Data Inicial<br>";

echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='$data_inicial' class='frm'>\n";
echo "	</TD>\n";

echo "	<TD width='130' colspan='2' valign='bottom'>Data Final <br>";

echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' id='data_final' value='$data_final' class='frm'>\n";
echo "</TD>\n";
echo "</TR>\n";

if($login_fabrica == 1){
	foreach($estadosBrasil as $linha => $indice){
		$selected = (in_array($linha,$estados)) ? "SELECTED" : "";
	        $estados_brasil .="<option value='$linha' $selected>$indice</option>";
    }

    $sql_regiao = "select descricao, estados_regiao from tbl_regiao where fabrica = $login_fabrica";
    $res_regiao = pg_query($con, $sql_regiao);
    for($i=0; $i<pg_num_rows($res_regiao); $i++){
        $descricao  = pg_fetch_result($res_regiao, $i, 'descricao');
	$estados    = pg_fetch_result($res_regiao, $i, 'estados_regiao');

	$selected = (in_array($estados,$regiao)) ? "SELECTED" : "";

        $regioes .= "<option value='$estados' $selected>$descricao</option>";
    }
?>
    <TR align='left'>
        <TD width='25'>&nbsp;</TD>
        <TD width='15'>Região</TD>
        <td>Estado</td>
    </TR>
    <tr>
        <TD width='25'>&nbsp;</TD>
        <td width='15' align='left'>
            <select name='regiao[]' id='ms' multiple='multiple' size='4' style='width:120px;'>
                <?=$regioes?>
            </select>
        </td>
        <td width='130' align='left'>
            <select name='estados[]' id='estados' multiple='multiple' size='4'>
                <?=$estados_brasil?>
            </select>
        </td>
    </TR>
    <TR>
        <TD width='25'>&nbsp;</TD>
        <TD colspan="2">

            <input type="radio" name="status_extrato" value="aprovadas" <?=$checked = ($btn_acao == "" || $status_extrato == "aprovadas") ? "checked='checked'" : ""?>/>Aprovados
            <input type="radio" name="status_extrato" value="liberadas" <?=$checked = ($status_extrato == "liberadas") ? "checked='checked'" : ""?>/>Liberados
            <input type="radio" name="status_extrato" value="reprovadas" <?=$checked = ($status_extrato == "reprovadas") ? "checked='checked'" : ""?>/>Reprovados
        </TD>
    </TR>
<?php
}


echo "<tr><td colspan='4'>&nbsp;</td></tr>";
echo "<TR class='subtitulo'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Somente Extratos do Posto";
echo "	</TD>";
echo "<TR>\n";

echo "<tr >\n";
echo "<TD width='80'>&nbsp;</TD>";
echo "	<TD nowrap>";
echo "Código do Posto <br> ";
echo "<input type='text' name='posto_codigo' id='posto_codigo' size='12' value='$posto_codigo' class='frm'>";
echo "<img src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome,'codigo');\">";
echo "</TD>";
echo "<TD colspan='2'>";
echo "Nome do Posto <br>";
echo "<input type='text' name='posto_nome' id='posto_nome' size='30' value='$posto_nome' class='frm'>";
echo "<img src='imagens/lupa.png' style='cursor: pointer;'' align='absmiddle' alt='Clique aqui para pesquisas postos pelo nome' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome,'nome');\">";
//echo "		<input type='text' name='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "</TD>";
echo "</tr>\n";
if ($login_fabrica == 1) {
	echo '<tr>
			<td>&nbsp;</td>
			<td colspan="2">
				<input type="checkbox" name="extratos_eletronicos" value="t" id="extratos_eletronicos" '. ( isset ($_GET['extratos_eletronicos']) ? 'checked' : '' ) .' />
				<label for="extratos_eletronicos">Somente Extratos Eletrônicos</a>
			</td>
		  </tr>';
  	echo '<tr>
			  <td>&nbsp;</td>
			  <td colspan="2">
				  <input type="checkbox" name="extratos_pendentes" value="t" id="extratos_pendentes" '. ( isset ($_GET['extratos_pendentes']) ? 'checked' : '' ) .' />
				  <label for="extratos_pendentes">Somente Extratos Pendentes com o Posto</a>
			  </td>
		  </tr>';
	  echo '<tr>
			  <td>&nbsp;</td>
			  <td colspan="2">
				  <input type="checkbox" name="data_anexo" value="t" id="data_anexo" '. ( isset ($_GET['data_anexo']) ? 'checked' : '' ) .' />
				  <label for="data_anexo">Somente data do Anexo</a>
			  </td>
		  </tr>';
}
echo "<TR>";
echo "<TD align='center' colspan='4'>";

echo "<br><input type=\"button\" value=\"Filtrar\"
 onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' >\n";
echo "</TD>";
echo "</TR>";
echo "</TABLE>\n";


echo "</form>";
echo "<br />";

function cmp($a,$b) {
     if ($a == $b) return 0;
     return (pathinfo($a, PATHINFO_FILENAME) < pathinfo($b, PATHINFO_FILENAME)) ? -1 : 1;
}

// INICIO DA SQL
if (strlen($posto) > 0 OR (strlen($x_data_inicial) > 0 and strlen($x_data_final) > 0) OR (!empty($extrato)) ) {
	$data_anexo = $_GET['data_anexo'];

	$cond = ($login_fabrica == 1 ) ? "AND tbl_extrato_financeiro.data_envio IS NULL":"";

	if ( isset($_GET['extratos_eletronicos']) ) {

		$join_eletronico = "JOIN tbl_tipo_gera_extrato ON tbl_posto_fabrica.posto = tbl_tipo_gera_extrato.posto AND tbl_posto_fabrica.fabrica = tbl_tipo_gera_extrato.fabrica AND envio_online AND tipo_envio_nf = 'online_possui_nfe'";

	}

    $sql = "SELECT ";
    if ($login_fabrica == 1 && !empty($data_anexo)) {
        $sql .= " DISTINCT ";
    }
    $sql .= "		tbl_posto.posto                                                ,
					tbl_posto.nome                                                 ,
					tbl_posto.cnpj                                                 ,
					tbl_posto_fabrica.codigo_posto                                 ,
					tbl_posto_fabrica.banco 									   ,
					tbl_posto_fabrica.agencia 									   ,
					tbl_posto_fabrica.nomebanco 								   ,
					tbl_posto_fabrica.favorecido_conta 							   ,
					tbl_posto_fabrica.cpf_conta 								   ,
					tbl_posto_fabrica.tipo_conta                                   ,
					tbl_posto_fabrica.conta 									   ,
					tbl_tipo_posto.descricao                       AS tipo_posto   ,
					tbl_extrato.extrato                                            ,
					tbl_extrato_extra.admin 							  AS aprovado_por  ,
					tbl_extrato.aprovado                                           ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra                      ,
					LPAD(tbl_extrato.protocolo,6,'0')              AS protocolo    ,
					TO_CHAR(tbl_extrato.data_geracao,'dd/mm/yyyy')   AS data_geracao ,
					tbl_extrato.bloqueado                                          ,
					tbl_extrato.pecas                                              ,
					tbl_extrato.mao_de_obra                                        ,
					tbl_extrato.avulso                                             ,
					tbl_extrato.total,
					tbl_extrato_financeiro.data_envio
			FROM      tbl_extrato
			JOIN      tbl_posto              ON tbl_posto.posto           = tbl_extrato.posto
			JOIN      tbl_posto_fabrica      ON tbl_extrato.posto         = tbl_posto_fabrica.posto
											AND tbl_extrato.fabrica       = tbl_posto_fabrica.fabrica
											AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_tipo_posto         ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
											AND tbl_tipo_posto.fabrica    = $login_fabrica
			$join_eletronico
			$join_pendencia
			JOIN      tbl_extrato_extra      ON tbl_extrato_extra.extrato = tbl_extrato.extrato
			LEFT JOIN tbl_extrato_financeiro ON tbl_extrato.extrato       = tbl_extrato_financeiro.extrato
			WHERE     tbl_extrato.fabrica = $login_fabrica
			$where_estado_regiao
			AND       tbl_extrato.aprovado NOTNULL



			$cond ";

    if ((strlen($x_data_inicial) > 0 && strlen ($x_data_final) > 0) AND empty($data_anexo)) {
        $sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
    }elseif ((strlen($x_data_inicial) > 0 && strlen ($x_data_final) > 0) AND !empty($data_anexo)) {
        $sql .= " AND tbl_extrato.data_recebimento_nf BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
    }

    if (strlen($posto) > 0) $sql .= " AND tbl_extrato.posto = $posto ";
    if($login_fabrica == 1)    {
        if ((strlen($posto) == 0) and strlen($extrato) > 0) {
            $sql .= " AND tbl_extrato.posto = ( SELECT posto from tbl_extrato WHERE protocolo = '$extrato' AND tbl_extrato.fabrica = {$login_fabrica} ) ";
        }
    }
    if ($login_fabrica <> 1 ) {
        $sql .= " ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
    } else {
		$sql .= " ORDER BY tbl_posto_fabrica.codigo_posto,tbl_extrato.extrato";
    }
#echo nl2br($sql); exit;
#if ($ip == '201.0.9.216') { echo nl2br($sql); exit; }

	$res = pg_query ($con,$sql);
	if (pg_num_rows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}else{

		echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
		echo "</tr>";
		if($login_fabrica==1){

			echo "<tr>";
			echo "<td align='center' width='16' bgcolor='#FF9E5E'>&nbsp;</td>";
			echo "<td align='left'><font size=1><b>&nbsp; Extrato Bloqueado</b></font></td>";
			echo "</tr>";

            echo "<tr>";
			echo "<td align='center' width='16' bgcolor='#fa8989'>&nbsp;</td>";
			echo "<td align='left'><font size=1><b>&nbsp; Pendente</b></font></td>";
			echo "</tr>";
            $cor_pendente = "#fa8989";
		}
		echo "</table>";

		include_once '../anexaNF_inc.php';

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$posto   = trim(pg_fetch_result($res,$i,posto));
			$codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
			$nome           = trim(pg_fetch_result($res,$i,nome));
            $nome           = ($login_fabrica==1) ? substr($nome,0,15) : "";
			$tipo_posto     = trim(pg_fetch_result($res,$i,tipo_posto));
			$extrato        = trim(pg_fetch_result($res,$i,extrato));
			$data_geracao   = trim(pg_fetch_result($res,$i,data_geracao));
			//$qtde_os        = trim(pg_fetch_result($res,$i,qtde_os));
			$total          = trim(pg_fetch_result($res,$i,total));
			$extrato        = trim(pg_fetch_result($res,$i,extrato));
			$aprovado       = trim(pg_fetch_result($res,$i,aprovado));
			$protocolo      = trim(pg_fetch_result($res,$i,protocolo));
			$nf_mobra       = trim(pg_fetch_result($res,$i,nota_fiscal_mao_de_obra));
			$pecas          = trim(pg_fetch_result($res,$i,pecas));
			$mao_de_obra    = trim(pg_fetch_result($res,$i,mao_de_obra));
			$avulso         = trim(pg_fetch_result($res,$i,avulso));
			$bloqueado      = trim(pg_fetch_result($res,$i,bloqueado));
			$aprovado_por	= trim(pg_fetch_result($res,$i,aprovado_por));

			$banco 				= trim(pg_fetch_result($res,$i,banco));
			$agencia 			= trim(pg_fetch_result($res,$i,agencia));
			$nomebanco 			= trim(pg_fetch_result($res,$i,nomebanco));
			$favorecido_conta 	= trim(pg_fetch_result($res,$i,favorecido_conta));
			$cpf_conta 			= trim(pg_fetch_result($res,$i,cpf_conta));
			$tipo_conta         = trim(pg_fetch_result($res,$i,tipo_conta));
			$conta 				= trim(pg_fetch_result($res,$i,conta));

			$data_envio 	= trim(pg_fetch_result($res,$i,data_envio));

			$sql_admin = "select  nome_completo from tbl_admin where admin = $aprovado_por";
			$res_admin = pg_query($con, $sql_admin);
            $nome_aprovado_por = pg_fetch_result($res_admin,0,nome_completo);

			$sql = "SELECT sum(tbl_os.pecas * (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float -1))) 
				from tbl_os
				join tbl_os_extra using(os)
				join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and campos_adicionais ~'TxAdm'
				where tbl_os_extra.extrato = $extrato
				and tbl_os.pecas > 0
				and (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float)) > 0
";
			$resX = pg_query($con, $sql);
			$totalTx = pg_fetch_result($resX,0, 0); 
			$total += $totalTx;
			$total	        = number_format ($total,2,',','.');
			$sql = "SELECT posto
					FROM tbl_tipo_gera_extrato
					WHERE fabrica = $login_fabrica
					AND posto = $posto
					AND envio_online
					AND tipo_envio_nf = 'online_possui_nfe'";

			$res2 = pg_query($con, $sql);

			$anexaNFServicos = pg_num_rows($res2);

			if (strlen($aprovado) > 0 AND strlen($data_envio) == 0) $status = "Aguardando documentação";
			/*HD 1163*/
			/*if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 and $pendente=='t' AND $confirmacao_pendente<>'t') $status = "Pendente, vide observação";*/

			if (strlen($aprovado) > 0 AND strlen($data_envio)  > 0) $status = "Enviado para o financeiro";
			$pendente_extrato = "";
			if ( isset ( $_GET['extratos_pendentes'] ) ) {

				$sql = "SELECT pendencia, pendente
						FROM tbl_extrato_status
						WHERE extrato = $extrato
						ORDER BY data DESC
						LIMIT 1";

				$res2 = pg_query($con,$sql);

				$pendencia = pg_result($res2,0,0);
				$pendente = pg_result($res2,0,1);

				if ($pendencia != 't' || $pendente != 't') {
					$pendente_extrato = 't';
				}

			}

			if ($login_fabrica == 1) {

				$sql = "SELECT posto
						FROM tbl_tipo_gera_extrato
						WHERE fabrica = $login_fabrica
						AND posto = $posto
						AND envio_online
						AND tipo_envio_nf = 'online_possui_nfe'";

				$res2 = pg_query($con, $sql);

				$extratoEletronico = (bool) pg_num_rows($res2);

			}

			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";
				echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela'>\n";
				echo "<tr class = 'titulo_coluna'>\n";
				echo '<td>
						<input type="checkbox" name="marca_todos" id="marca_todos" />
					  </td>';
				echo "<td align='center'>Código</td>\n";
				echo "<td align='center' nowrap>Nome do Posto</td>\n";
				echo "<td align='center'>Tipo</td>\n";
				echo "<td align='center'>Extrato</td>\n";
				echo "<td align='center'>Data</td>\n";
				echo "<td align='center' nowrap>OS</td>\n";
				echo "<td align='center'>Total Peça</td>\n";
				echo "<td align='center'>Total MO</td>\n";
				echo "<td align='center'>Total Avulso</td>\n";
//if ($login_fabrica == 1) echo "<td align='center' nowrap>Pendência</td>\n";
				echo "<td align='center'>Total Geral</td>\n";
				echo "<td align='center'>NF Autorizado</td>\n";
				echo "<td align='center'>";
				echo $login_fabrica == 1
                    ? (($status_extrato == "liberadas") ? 'Financeiro' : "Assinatura")
                    : 'Ações';
				echo "</td>\n";
				// HD 42973

				if($login_fabrica == 120 or $login_fabrica == 201) echo "<td align='center'>Dados Bancários</td>\n";

				if($login_fabrica == 1) {

					echo "<td align='center'>Valor Adicional</td>\n";

					echo "<td align='center'>Pendência</td>
						  <td>Status</td>";
                    if($login_fabrica == 1){
                        echo "<td>Aprovado Por</td>";
                        echo "<td>Conferido </td>";
                    }
                    if ($status_extrato == "reprovadas") {
                        echo "<td>Motivo Recusa</td>";
                    }
                    echo "<td>Anexos</td>";

				}
				echo "</tr>\n";

				$excel .= "<table border='1'>";
				$excel .= "<tr>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Código</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Nome do Posto</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Tipo</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Extrato</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Data</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>OS</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Total Peça</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Total MO</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Total Avulso</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Total Geral</b></font></th>";
				$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>NF Autorizado</b></font></th>";

				if($login_fabrica == 120 or $login_fabrica == 201){
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Banco</b></font></th>";
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Agência</b></font></th>";
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Conta</b></font></th>";
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Tipo da Conta</b></font></th>";
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>CPF/CNPJ do Favorecido</b></font></th>";
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Nome do Favorecido</b></font></th>";
				} else if ($login_fabrica == 1) {
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Status</b></font></th>";
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Aprovado Por</b></font></th>";
					$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Conferido</b></font></th>";
					if ($login_fabrica == 1)
						$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Data Último Anexo</b></font></th>";
					if ($status_extrato == "reprovadas") {
						$excel .= "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Motivo Recusa</b></font></th>";
					}
				}
			}

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";


			##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####

			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    fabrica = $login_fabrica";
				$res_avulso = pg_query($con,$sql);

				if (@pg_num_rows($res_avulso) > 0) {
					if (@pg_fetch_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
				}
				$sql = "SELECT extrato
						FROM   tbl_extrato_financeiro
						WHERE  extrato = $extrato";
				$res_f = pg_query($con,$sql);

				if (pg_num_rows($res_f) > 0) {
					$bloqueia = true;
				}else{
					$bloqueia = false;
				}
			}
			##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

			if($pendente_extrato == 't') continue;

			if($login_fabrica == 1) {


                //verifica se admin conferiu
                $verificaConferido = "SELECT conferido
                                      FROM tbl_extrato_status
                                      WHERE extrato = {$extrato} AND conferido is not null and obs='Conferido'";
                $resVerificaConferido = pg_query($con,$verificaConferido);


                if(pg_num_rows($resVerificaConferido) > 0){

                    $conferido = pg_fetch_result($resVerificaConferido, 0, "conferido");

                    if(strlen($conferido) > 0){
                        $conferido = true;
                    }else{
                        $conferido = false;
                    }
                }else{
                    $conferido = false;
                }

                # verifica tipo de envio de extrato
                $sqlTpoEnvio = "SELECT JSON_FIELD('tipo_de_envio',obs) AS tipo_de_envio
                                FROM tbl_extrato_extra
                                JOIN tbl_extrato USING(extrato)
                                WHERE fabrica={$login_fabrica} AND
                                extrato = {$extrato}";

                $resTpoEnvio = pg_query($con,$sqlTpoEnvio);

                if(pg_num_rows($resTpoEnvio) > 0){
                    $tpoEnvio = pg_fetch_result($resTpoEnvio,0,"tipo_de_envio");
                }

				// Inicio verificacao status do extrato
				$ysql = "SELECT tbl_extrato_status.obs                 ,
						tbl_extrato_status.pendente            ,
						tbl_extrato_status.confirmacao_pendente,
						tbl_extrato_status.advertencia         ,
						tbl_extrato_status.pendente,
						tbl_extrato_status.pendencia,
						tbl_extrato_status.admin_conferiu,
						tbl_extrato_status.arquivo AS motivo_recusa
				FROM tbl_extrato_status
				WHERE tbl_extrato_status.extrato = $extrato
				AND fabrica = $login_fabrica
				ORDER BY data DESC
				LIMIT 1";
// echo nl2br($ysql);
				$yres = pg_exec($con,$ysql);

				if(pg_numrows($yres)>0){

                    $pendente       = trim(pg_result($yres,0,'pendente'));
                    $pendencia      = trim(pg_result($yres,0,'pendencia'));
                    $advertencia    = pg_result($yres,0,'advertencia');
                    $obs            = pg_result($yres,0,'obs');
                    $admin_conferiu = pg_result($yres,0,'admin_conferiu');
                    $motivo_recusa  = pg_result($yres,0,'motivo_recusa');

                    if ($login_fabrica == 1) {
                        if ($status_extrato == "aprovadas" && $obs == "Aguardando aprovação online") {
                            continue;
                        } else if ($status_extrato == "liberadas") {

                            if ($obs == "Aguardando aprovação online" && (empty($pendente) || $pendente == 't')) {
                                continue;
			    } else if ($obs != "Aguardando aprovação online" OR strlen($obs) == 0) {
                                continue;
                            }
                        } else if ($status_extrato == "reprovadas") {
                            if ($obs == "Aguardando aprovação online" && $pendente != 't') {
                                continue;
                            } else if ($obs != "Aguardando aprovação online") {
                                continue;
                            }
                        }
                    }

					if ( strlen($aprovado) > 0 AND strlen($data_envio) == 0 && $anexaNFServicos && $pendencia != 't' ) {
						$status = "Aguardando envio para o financeiro";

					} else if (strlen($aprovado) > 0 AND strlen($data_envio) == 0  && ($obs != 'Aguardando NF de serviços' && $obs != "Conferido") ) {
						$status = "Pendente";
                        $cor = $cor_pendente;
					}

					if($advertencia == 't'){
						$status = "Alerta";
					} else if ( strlen($aprovado) > 0 && $pendente == 't' && $pendencia == 't' && $anexaNFServicos ) {

						$sqlNfAnexada = "SELECT referencia_id 
                				 FROM tbl_tdocs 
                				 WHERE fabrica = $login_fabrica
                				 AND referencia_id = $extrato
                				 AND referencia = 'osextrato' 
                				 AND situacao = 'ativo'";
                		$resNfAnexada = pg_query($con, $sqlNfAnexada);

						if ($status != 'Pendente' || pg_num_rows($resNfAnexada) == 0)
							$status = "Aguardando NF de serviços";
					}

				}else{
					if ($status_extrato == "liberadas" OR $status_extrato == "reprovadas") continue;
				}

				// fim verificacao do status do extrato

			echo "<tr bgcolor='$cor'>\n";

			echo '<td>
					<input type="checkbox" name="aprova[]" value="'.$extrato.'" class="check_aprova" />
				  </td>';
			echo "<td align='left'>$codigo_posto</td>\n";
			echo "<td align='left' nowrap>".substr($nome,0,20)."</td>\n";
			echo "<td align='center' nowrap>$tipo_posto</td>\n";
			echo "<td align='center' ";
			if($bloqueado == "t" and $login_fabrica == 1){
				echo " bgcolor='#FF9E5E' ";
				}
			echo "><a href='extrato_consulta_os.php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome' target='_blank'>";
			echo ($login_fabrica == 1) ? $protocolo : $extrato;
			echo "</a></td>\n";
			echo "<td align='left'>$data_geracao</td>\n";
			/*HD 15001 - Não contar mais OS*/
			echo "<td align='center' title='Clique aqui para ver a quantidade de OS'><div id='qtde_os_$i'><a href=\"javascript:conta_os('$extrato','qtde_os_$i');\">VER</div></td>";
            $sql =	"SELECT COUNT(tbl_os.os)          AS total_os         ,
                            SUM(tbl_os.pecas)       AS total_pecas     ,
							SUM(tbl_os.mao_de_obra) AS total_maodeobra ,
							tbl_extrato.avulso      AS total_avulso
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
					WHERE tbl_os_extra.extrato = $extrato
					GROUP BY tbl_extrato.avulso;";
			$resT = pg_query($con,$sql);
#if ($ip == '201.0.9.216') { echo nl2br($sql); exit; }

			if (pg_num_rows($resT) == 1) {
				echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_pecas),2,',','.') . " </td>\n";
				echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
				echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_avulso),2,',','.') . "</td>\n";
			}else{
				echo "<td>&nbsp;</td>\n";
				echo "<td>&nbsp;</td>\n";
				echo "<td>&nbsp;</td>\n";
			}

			echo "<td align='right' nowrap>R$ $total</td>\n";
			echo "<td align='right' nowrap>";
			echo "<input type='text' name='nf_autorizado_$extrato' size='8' maxlength='20' style='font-size:10px' value='$nf_mobra'";
			if(strlen($nf_mobra)>0) echo "readonly class='nf_autorizado'";
			echo " class='frm'>";
			echo "</td>\n";
			echo "<td align='center' nowrap >";
			if (strlen($aprovado) > 0 and (($login_fabrica == 1 && $status_extrato == "liberadas") or (in_array($login_fabrica,[120,201]) and !$bloqueia))) {
					echo "<img src='imagens_admin/btn_aprovar_azul.gif' style=\"cursor:pointer\" onclick=\"javascript: if (document.Selecionar.btn_aprova.value == '' ) { document.Selecionar.btn_aprova.value='aprovado' ;document.Selecionar.extrato_aprovado.value='$extrato' ; document.Selecionar.nf_autorizado.value=document.Selecionar.nf_autorizado_$extrato.value ;  document.Selecionar.submit() } else { alert ('Aguarde submissão') } \" ALT=\"Aprovar extrato\" border='0'> ";
			}
			if($login_fabrica == 1) {
                if ($status_extrato == "aprovadas") {
?>
                    <img src='imagens_admin/btn_aprovar_azul.gif' style="cursor:pointer" onclick="javascript: if (document.Selecionar.btn_aprova.value == '' ) { document.Selecionar.btn_aprova.value='liberado' ;document.Selecionar.extrato_liberar.value='<?=$extrato?>' ; document.Selecionar.nf_autorizado.value=document.Selecionar.nf_autorizado_<?=$extrato?>.value ;  document.Selecionar.submit() } else { alert ('Aguarde submissão') } " alt="Enviar extrato para liberação" border='0' />
<?php
                }
				if($bloqueado <> "t"){
					echo " <a href='$PHP_SELF?bloquear=$extrato&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final&posto_codigo=$posto_codigo&posto_nome=$posto_nome'><img src='imagens_admin/btn_bloquear_vermelho.gif' ALT='Bloquear o extrato'></a>";
				}else{

					echo " <a href='$PHP_SELF?desbloquear=$extrato&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final&posto_codigo=$posto_codigo&posto_nome=$posto_nome'><img src='imagens_admin/btn_desbloquear_azul.gif' ALT='Desbloquear o extrato'></a>";
				}
			}
			echo "</td>\n";

				echo "<td>
					    <a href='extrato_avulso.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_adicionar_azul.gif' id='img_adicionar_$i' ALT = 'Lançar itens no extrato'></a>
					  </td>\n
					  <td><a href=\"javascript: AbrirJanelaObs('$extrato');\"><font size='1'>Abrir</font></a></td>
					  <td>&nbsp;$status</td>";
                if($login_fabrica == 1){
                	echo "<td>$nome_aprovado_por</td>";
                    if($conferido){
                        echo "<td>Conferido </td>";
                    }else{
                        echo "<td> - </td>";
                    }

                    if ($status_extrato == "reprovadas") {
                        echo "<td>$motivo_recusa</td>";
                    }
                }

				if ( $extratoEletronico ) {
					if (file_exists('/aws-amazon/sdk/sdk.class.php')) {

						$htmlAnexo =  temNF('e_' . $extrato, 'link');

						$sql_data_anexo = "SELECT JSON_FIELD('date', obs) as data_anexo, tdocs
											 FROM tbl_tdocs
											WHERE fabrica       = $login_fabrica
											  AND referencia    = 'osextrato'
											  AND referencia_id = $extrato
										 	ORDER BY data_input DESC
											LIMIT 1";
						$res_data_anexo = pg_query($con, $sql_data_anexo);					
						
						$data_anexo = pg_fetch_result($res_data_anexo, 0, 'data_anexo');

						if (!empty($data_anexo)) {
							list($data, $hora)     = explode("T", $data_anexo);
	                        list($ano, $mes, $dia) = explode("-", $data);
	                        $data                  = $dia."/".$mes."/".$ano." ".$hora;
	                        $data_anexo            = $data;
                    	}
                        //Caso não encontre na função temNF, monta manualmente a tabela com imagens
						if (strlen($htmlAnexo) == 0) {
							$id_tdos  = pg_fetch_result($res_data_anexo, 0, 'tdocs');

								$tDocsobj = new TDocs($con, $login_fabrica);
								$url 	  		  = $tDocsobj->getDocumentLocation($id_tdos);		

								$explode_url = explode('.',$url);

								if (in_array('pdf', $explode_url)) {
									$thumb = '../imagens/icone_PDF.png';
								} else {
									$thumb = $url;
								}

								list($nome_arquivo, $tipo_arquivo) = explode(".", $arquivo_detalhes->filename);
								//echo "<td><a href='$url' target='blank'><img src='../helpdesk/imagem/clips.gif' border='0'></td>";

								?>
								<td>
								<?php
								if (!empty($id_tdos)) {
								?>	
									<table id="anexos" class="tabela" align="center">
										<thead class="Tabela inicio">
										<tr>
											<th></th>
										</tr>
										</thead>
										<tbody>
											<tr class="conteudo">
												<td style="vertical-align:middle;text-align:center">Modificado em <?= $data_anexo ?></td>
											</tr>
											<tr class="conteudo">
												<td style="vertical-align:middle;text-align:center"><center><a href="<?= $url ?>" target="_blank"><img src="<?= $thumb ?>" title="Para ver a imagem completa, clique com o botão direito e selecione &quot;Abrir link em uma nova janela&quot; ou &quot;Mostrar Imagem&quot;" style="display:block;zoom:1;max-height:150px;max-width:150px;height:150px;"></a></center>
												</td>
											</tr>
										</tbody>
									</table>
								<?php
								} else {
								?>
									<strong>Sem anexos</strong>
								<?php
								}
								?>	
								</td>
								<?

						}else{
							echo "<td>$htmlAnexo</td>";
						}

					} else {
						$fabrica = $login_fabrica >= 10 ? $login_fabrica : 0 . $login_fabrica;
						$data_anexo = array_reverse(explode('/',$data_geracao));

						$data_anexo = $data_anexo[0] . '_' . $data_anexo[1];
						$file = "../nf_digitalizada/$fabrica/$data_anexo/e_$extrato*";

						$files = glob( $file );

						usort($files, 'cmp');

						echo "<td nowrap>";

						if (!empty($files)) {
							foreach($files as $k => $f) {

								echo '<a href="'.$f.'" target="_blank">Anexo '.($k+1).'</a><br />';

							}
						}

						echo "</td>";

					}

				} else {

					echo '<td>&nbsp;</td>';

				}

			}

			if($login_fabrica == 120 or $login_fabrica == 201) {
				echo "<td>";
					echo "<a href='javascript:void(0);' onclick='mostraDados($posto,$i)'>Exibir dados</a>";
				echo "</td>\n";
			}

			echo "</tr>\n";

			if($login_fabrica == 120 or $login_fabrica == 201) {
				echo "<tr class='".$posto."_".$i."' style='display:none;'>";
				echo "<td colspan='100%'>";
				echo "<table align='center' width='100%'>";
				echo "<tr class='titulo_coluna'>";
				echo "<td align='center'>Banco</td>";
				echo "<td align='center'>Agência</td>";
				echo "<td align='center'>Conta</td>";
				echo "<td align='center'>Tipo da Conta</td>";
				echo "<td align='center'>CPF/CNPJ do Favorecido</td>";
				echo "<td align='center'>Nome do Favorecido</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td align='left' >$banco - $nomebanco</td>";
				echo "<td align='center' >$agencia</td>";
				echo "<td align='center' >$conta</td>";
				echo "<td align='left' >$tipo_conta</td>";
				echo "<td align='left' >$cpf_conta</td>";
				echo "<td align='left' >$favorecido_conta</td>";
				echo "</tr>";
				echo "</table>";
				echo "</td>";
				echo "</tr>";
			}

			$excel .= "<tr>";
			$excel .= "<td align='left'>$codigo_posto</td>";
			$excel .= "<td align='left' >$nome</td>";
			$excel .= "<td align='center' >$tipo_posto</td>";
			$excel .= "<td align='center'>".(($login_fabrica == 1) ? $protocolo : $extrato)."</td>";
			$excel .= "<td align='left'>$data_geracao</td>";
			$excel .= "<td align='right' nowrap>" .pg_fetch_result($resT,0,total_os). " </td>\n";
			$excel .= "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_pecas),2,',','.') . " </td>\n";
			$excel .= "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
			$excel .= "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_avulso),2,',','.') . "</td>\n";
			$excel .= "<td align='right' >R$ " . $total . "</td>\n";
			$excel .= "<td align='right' >$nf_mobra</td>";
			if($login_fabrica == 120 or $login_fabrica == 201){
				$excel .= "<td align='left' >$banco - $nomebanco</td>";
				$excel .= "<td align='center' >$agencia</td>";
				$excel .= "<td align='center' >$conta</td>";
				$excel .= "<td align='left' >$tipo_conta</td>";
				$excel .= "<td align='left' >$cpf_conta</td>";
				$excel .= "<td align='left' >$favorecido_conta</td>";
			} else if ($login_fabrica == 1) {
				$excel .= "<td align='left' >$status</td>";
				$excel .= "<td align='left' >$nome_aprovado_por</td>";
				$excel .= "<td align='left' >".(($conferido) ? "Conferido" : "-")."</td>";
				if ($login_fabrica == 1)
					$excel .= "<td align='left'>$data_anexo</td>";
                    unset($data_anexo);
				if ($status_extrato == "reprovadas") {
					$excel .= "<td align='left' >$motivo_recusa</td>";
				}
			}
		}
		$excel .= "</table>";

		if($login_fabrica == 1){
			echo "<tr>\n";
			echo "<td colspan='7'>&nbsp;</td>\n";
			echo "<td colspan='2'>&nbsp;</td>\n";
			echo "</tr>\n";
		}

		echo "<tr>\n";
		echo "<td colspan='7' align='left'>
				Com marcados &nbsp;
				<button id='aprova_extratos'>Aprovar</button>
				<button type='button' id='recusa_extratos'>Recusar</button>

			  </td>\n";
		echo "<td colspan='2'>&nbsp;</td>\n";
		echo "</tr>\n";

		echo "</table>\n";
		echo "<input type='hidden' name='btn_aprova' value=''>";
		echo "<input type='hidden' name='extrato_aprovado' value=''>";
		echo "<input type='hidden' name='extrato_liberar' value=''>";
		echo "<input type='hidden' name='nf_autorizado' value=''>";
		echo "</form>\n";

		if(in_array($login_fabrica,array(1,120,201))){
			echo "<br>";
			$arquivo = "xls/relatorio-extrato-aprovado-$login_fabrica-".date('Y-m-d').".xls";
			$fp = fopen($arquivo,"w");
			fwrite($fp,$excel);
			fclose($fp);

			echo "<input type='button' onclick=\"window.open('$arquivo')\" value='Download Excel'>";
		}
	}
}

?>
<p>
<p>
<? include "rodape.php"; ?>
