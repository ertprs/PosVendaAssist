<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include 'funcoes.php';

if ($_GET['aprOS'] == 1) {

	$os   = $_GET['idOS'];
	$dias = $_GET['dias'];
	$obs = "OS liberada da auditoria pelo fabricante";


	if ($dias == 70) {
		$status_os = 149;
	} else if ($dias == 30) {
    $status_os = 151;
	} else if($dias == 1001 AND $login_fabrica == 101){
    $status_os = 19;
		$obs = "Pedido de Peça aprovado pelo fabricante";
	}else if($dias == 1001 && $login_fabrica == 74){
        $sql = "SELECT sua_os FROM tbl_os WHERE os = $os AND consumidor_revenda = 'R'";
        $res = pg_query($con,$sql);

	$sqlStatus = "SELECT  tbl_os_status.status_os
		FROM    tbl_os_status
		WHERE   os = $os
		AND fabrica_status = $login_fabrica
		AND     status_os = 196
		AND     os NOT IN (
			SELECT tbl_os_status.os
			FROM   tbl_os_status
			WHERE  tbl_os_status.os = $os
			AND    status_os IN (198)
			AND fabrica_status = $login_fabrica

		)";
        $resStatus = pg_query($con,$sqlStatus);

        if(pg_num_rows($resStatus) > 0){
          $statusOS = pg_result($resStatus, 0, 'status_os');
        }

        if(pg_num_rows($res) > 0 AND $statusOS <> 196){

            $sqlR = "   SELECT  tbl_os_status.status_os
                        FROM    tbl_os_status
                        WHERE   os = $os
                        AND     status_os = 176
                        AND     os NOT IN (
                                    SELECT tbl_os_status.os
                                    FROM   tbl_os_status
                                    WHERE  tbl_os_status.os = $os
                                    AND    status_os IN (177)
                                )
            ";
	    $resR = pg_query($con,$sqlR);

            if(pg_num_rows($resR) > 0){
                $status_os = 177;
            }else{


                $status_os = 103;
            }
        }else{
            $sqlLB = "
                SELECT  tbl_os_status.status_os
                FROM    tbl_os_status
                WHERE   os = $os
                AND     status_os = 196
                AND     os NOT IN (
                            SELECT tbl_os_status.os
                            FROM   tbl_os_status
                            WHERE  tbl_os_status.os = $os
                            AND    status_os IN (198)
                        )
            ";
            $resLB = pg_query($con,$sqlLB);
            if(pg_num_rows($resLB) > 0){
                $status_os = 198;
            }else{
                $status_os = 103;
            }
        }
	}else {
    $status_os = 103;
	}
	$reaprovar = $_GET['reaprovar'];

	if($login_fabrica == 101 && $reaprovar == "sim"){

		$sql = "UPDATE tbl_os SET status_os_ultimo = 19 where os = $os";
		$res      = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

	}

	$sql = "INSERT INTO tbl_os_status (
				os,
				status_os,
				admin,
				observacao,
				fabrica_status
			) VALUES (
				$os,
				$status_os,
				$login_admin,
				'$obs',
				$login_fabrica
			)";
	$res      = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		echo "OK|OS Aprovada com Sucesso!";
	} else {
		echo "NO|OS não Aprovada. Erro: $msg_erro";
	}

	exit;

}

if ($_GET['repOS'] == 1) {

	$os        = $_GET['idOS'];
	$posto     = $_GET['posto'];
	$motivo    = $_GET['motivo'];
	$auditoria = $_GET['auditoria'];

	$msg = ($auditoria == 67) ? 'OS Reincidente de Número de Série reprovada pelo fabricante' : 'OS sem Número de Série reprovada pelo fabricante ';

    if($login_fabrica == 74){
        switch ($auditoria) {
            case '67':
                $msg = "OS Reincidente N° de Série";
                break;
            case '163':
                $msg = "Data de Compra acima de 12 meses da Data de Fabricação";
                break;
            case '176':
                $msg = "Auditoria de OS Revenda";
                break;
            case '196':
                $msg = "Peças Alteradas";
                break;
            case '150':
                $msg = "OS Abertas entre 30 e 70 dias";
                break;
            case '148':
                $msg = "OS Abertas a mais de 70 dias";
                break;
            case '102':
                $msg = "Número de serie irregular";
                break;
        }
    }

	if ($login_fabrica == 91) {
		$msg = 'OS reprovada pelo fabricante na auditoria de número de série';
	}

	if($login_fabrica == 101){
		$msg = "Pedido de Peça reprovado pelo fabricante";
	}

	if ($auditoria == 67 && $login_fabrica == 74) { // HD 708057

		$sql = "SELECT email FROM tbl_posto WHERE posto = $posto";
		$res = pg_query($con, $sql);

		$email_posto = @pg_result($res, 0, 0);

		if (!empty($email_posto)) {

			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'To: <'.$email_posto.'>' . "\r\n";
			$headers .= 'From: Suporte Telecontrol <helpdesk@telecontrol.com.br>' . "\r\n";

			$assunto = "OS $os - Reprovada por Auditoria";
			$msg_email = "A Atlas Ind. de Eletrodomésticos LTDA informa que:<br />
							Sua OS $os foi reprovada pelo motivo:<br /><br />
							$motivo <br /><br />
							Maiores dúvidas favor entrar em contato com a Atlas Ind. de Eletrodomésticos LTDA.";

			mail($email_posto, utf8_encode($assunto), utf8_encode($msg_email), $headers);

		}

	}

    $res = pg_query ($con,"BEGIN TRANSACTION");
	if ($login_fabrica == 74) {

		$msg .= ". Motivo: ".$motivo;

		$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin)
				VALUES ($os,156, current_timestamp,'$msg',$login_admin)";

		$res = pg_query($con,$sql);

		$msg_erro .= pg_errormessage($con);
		$sql = "SELECT sua_os FROM tbl_os WHERE os = $os AND consumidor_revenda = 'R'";
		$res = pg_query($con,$sql);

		$msg_erro .= pg_errormessage($con);
		if (pg_num_rows($res)) {
			$sua_os = pg_result($res, 0, 0);
		}

		$sql = "UPDATE tbl_os SET cancelada = TRUE, finalizada = now(), data_fechamento = now() WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);



	} else if($login_fabrica == 101){
		$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin)
				VALUES ($os,81,current_timestamp,'$msg',$login_admin)";

		$res       = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

	} else {

		$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin)
				VALUES ($os,15,current_timestamp,'$msg',$login_admin)";

		$res       = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "UPDATE tbl_os set excluida = 't' where os = $os";
		$res = pg_exec($con,$sql);

		$msg_erro .= pg_errormessage($con);
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin)";
		$res = pg_exec($con, $sql);

		$msg_erro .= pg_errormessage($con);
		#158147 Paulo/Waldir desmarcar se for reincidente
		$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
		$res = pg_exec($con, $sql);

		$msg_erro .= pg_errormessage($con);
	}

	$msg_erro .= pg_errormessage($con);

	$os = (!empty($sua_os)) ? $sua_os : $os;

	$mensagem = "A OS : ".$os." foi reprovada da intervenção técnica <br> <b>Justificativa :</b> ".$motivo;

	$sql = "INSERT INTO tbl_comunicado (
				mensagem         ,
				tipo             ,
				fabrica          ,
				obrigatorio_site ,
				descricao        ,
				posto            ,
				ativo
			) VALUES (
				'$mensagem',
				'Comunicado',
				$login_fabrica,
				't',
				'Reprovação Intervenção Técnica',
				$posto,
				't'
			)";

	$res       = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	$acao = ($login_fabrica == 74) ? 'Reprovada' : 'Excluída';
	if(strpos($msg_erro ,'pedido lan') !== false) {
		$msg_erro = "Ordem de Serviço já possui pedido lançado, não pode ser $acao";
	}

	if (strlen($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");
		echo "OK|OS $acao com Sucesso!";
	} else {
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
		echo "NO|OS não $acao. Erro: $msg_erro";
	}

	exit;

}

function verificaTodasAuditoria($status_oss) {
	$tipos = array(
		"20"  => array(19),
		"67"  => array(128, 131,135,103,139,155),
		"102" => array(103,128),
		"148" => array(149),
		"150" => array(151),
		"163" => array(103,128),
    		"176" => array(103,177),
		"196" => array(197,198),
	);

	TIRARSTATUS:

		foreach($tipos as $tipo_key => $tipo_value ) {
		if(in_array($tipo_key,$status_oss)){
			$os_status = array_search($tipo_key,$status_oss);
			foreach($tipo_value as $tipo_aprovado) {
				if(in_array($tipo_aprovado,$status_oss)){
					$status_os = array_search($tipo_aprovado,$status_oss);
					$conta_status_os = array_count_values($status_oss);

					if($conta_status_os[$tipo_aprovado] > 1){
						$keys_array = array_keys($status_oss);

						foreach($keys_array AS $key => $value){
							if($status_oss[$value] == $tipo_aprovado AND $value > $os_status){
								unset($status_oss[$os_status]);
								unset($status_oss[$value]);
								goto TIRARSTATUS;
							}

						}
					}

					unset($status_oss[$os_status]);
					unset($status_oss[$status_os]);
					if(in_array($tipo_key,$status_oss)){
						goto TIRARSTATUS;
					}

					if($os_status < $status_os ){
						unset($tipos[$tipo_key]);
						goto TIRARSTATUS;
					}
				}
			}
			return false;
		}else{
			unset($tipos[$tipo_key]);
			goto TIRARSTATUS;
		}
	}
	return true;
}

$btn_acao = $_POST['btn_acao'];

if ($btn_acao == "Pesquisar") {

	$os              = $_POST['os'];
	$posto_codigo    = $_POST['posto_codigo'];
	$posto_descricao = $_POST['posto_descricao'];
	$data_inicial    = $_POST['data_inicial'];
	$data_final      = $_POST['data_final'];
	$tipo_auditoria  = $_POST['tipo_auditoria'];

	if($login_fabrica == 101){
		$mostrar_os = $_POST['mostrar_os'];
	}

	/**
	 * HD 854585
	 * @author Brayan
	 */

	if ($tipo_auditoria == 163) {

		$status = 163;
		$condStatus = "13,103, 128,139,163";

	}
	// FIM HD 854585
	else if ($tipo_auditoria == 148) {

		$cond .= " AND   tbl_os_status.status_os IN (148)
				  AND tbl_os.data_abertura::date < current_date-interval '70 days'";

		$condStatus = "148,149";
		$status     = "148";
		$intervalo  = 70;

	} else if ($tipo_auditoria == 150) {

		$cond .= " AND   tbl_os_status.status_os IN (150) ";

		$condStatus = "150,151";
		$status     = "150";
		$intervalo  = 30;

	} else if ($tipo_auditoria == 67) { // HD 708057

		$cond .= " AND   tbl_os_status.status_os IN (67)";

		$condStatus = "13,128, 131,135,67,103,139,155";

		$status     = "67";

	}else if ($tipo_auditoria == 176) {

		$cond .= " AND   tbl_os_status.status_os IN (176)";

		$condStatus = "176,177";

		$status     = "176";

	} else if ($tipo_auditoria == 0) {

		 $cond .= " AND   tbl_os_status.status_os IN (67,102,148,150,163,176)";


         $condStatus = "13,67,102,103,128,131,135,139,148,149,150,151,155,163,176,177,196";
         $status = "67,102,103,128,131,135,139,148,149,150,151,155,163,176,177,196";

	} else if ($tipo_auditoria == 20){
		$condStatus = "13,19, 20, 81";
        $status = "20";
		if($login_fabrica == 101 && strlen($mostrar_os) > 0){
			/*
			Status
			20 - Em Analise pelo Fabricante
			75 - Aguardando Aprovação
			81 - Reprovado
			*/

			if($mostrar_os == "em_aprovacao"){
				$status = "20, 75";
			}else{
				/* Reprovadas */
				$status = "20, 81";
			}
		}
	}else if($tipo_auditoria == 196 ){
        $cond .= "
            AND tbl_os_status.status_os IN (196,197,198)
        ";
        $condStatus = "196,197,198";
        $status = "196";
	}else {
		if ($login_fabrica == 91) {

			if ($tipo_auditoria == 102) {
				$status  = "102";
				$cond   .= " AND tbl_os_status.status_os IN (102,103)";
			} else {
				$status  = "103";
				$cond   .= " AND tbl_os_status.status_os IN (103)";
			}

		} else {
			$cond .= " AND tbl_os_status.status_os IN (102) ";
			$status     = "102";
		}

		// HD 1091578 - PAULO LIN CONFIRMOU PARA TIRAR O 64
		// SE DER PROBLEMA FAVOR VERIFICAR.
		$condStatus = "102,103,128";

	}

	if (!empty($os)) {

		$campo = (strpos($os,'-') ) ? 'sua_os' : 'os';
		$os    = $campo == 'sua_os' ? "'$os'" : $os;

		$sql = "SELECT os FROM tbl_os where $campo = $os AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if (pg_numrows($res) == 0) {
			$msg_erro = "OS não Cadastrada";
		} else {
			$xos = pg_fetch_result($res,0,0);
			$condOS = " AND tbl_os.os = $xos";
		}

	} else {

		if (!empty($posto_codigo)) {

			$sql = "SELECT posto from tbl_posto_fabrica WHERE codigo_posto = '$posto_codigo'";
			$res = pg_query($con,$sql);

			if(pg_numrows($res) == 0){
				$msg_erro = "Posto não Encontrado";
			} else {
				$posto = pg_result($res,0,posto);
				$condPosto = " AND tbl_os.posto = $posto";
			}

		}

		if (!empty($data_inicial) && !empty($data_final)) {

			list($di, $mi, $yi) = explode("/", $data_inicial);

			if (!checkdate($mi,$di,$yi)) {
				$msg_erro = "Data Inválida";
			}

			list($df, $mf, $yf) = explode("/", $data_final);

			if (!checkdate($mf,$df,$yf)){
				$msg_erro = "Data Inválida";
			}

			if (strlen($msg_erro) == 0) {

				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";

				if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
					$msg_erro = "Data Inválida";
				}

			}

			$cond .= " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";

		}

	}

}

$os          = str_replace("'","",$os);
$layout_menu = "auditoria";

if ($login_fabrica == 74) {
	$title = "AUDITORIA DE OS ABERTA A MAIS DE 70 DIAS E Nº SÉRIE";
} else {
	$title = "AUDITORIA DE OS ABERTA COM Nº SÉRIE TEMPORÁRIO";
}

include "cabecalho.php";?>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

#relatorio thead tr th {

	cursor: pointer;

}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.espaco{
	padding: 0 0 0 100px;
}

caption{
	height:25px;
	vertical-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>

<?php
include 'javascript_calendario_new.php';
include '../js/js_css.php';
?>
<script language='javascript'>
$().ready(function() {
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

	$('#data_inicial').datepick({startdate : '01/01/2000'});
	$('#data_final').datepick({startDate : '01/01/2000'});
	$("#data_inicial").mask("99/99/9999");
	$("#data_final").mask("99/99/9999");
});

function SomenteNumero(e){
  var tecla=(window.event)?event.keyCode:e.which;
  if((tecla > 47 && tecla < 58) || tecla == 45 || tecla == 17 || tecla == 118 || tecla == 86 || tecla == 9) return true;
  else{
  if (tecla != 8) return false;
  else return true;
  }
}

function fnc_pesquisa_posto2(campo, campo2, tipo) {

  if (tipo == "codigo" ) {
      var xcampo = campo;
  }

  if (tipo == "nome" ) {
      var xcampo = campo2;
  }

  if (xcampo.value != "") {

      var url = "";

      url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
      janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
      janela.codigo  = campo;
      janela.nome    = campo2;

      if ("<? echo $pedir_sua_os; ?>" == "t") {
          janela.proximo = document.frm_os.sua_os;
      } else {
          janela.proximo = document.frm_os.data_digitacao;
      }

      janela.focus();

  } else {
      alert("Informar toda ou parte da informação para realizar a pesquisa!");
  }

}

function aprovaOS(os,numero,dias,reaprovar) {

	if(reaprovar == 81){
		reaprovar = "sim";
	}else{
		reaprovar = "nao";
	}

	if (confirm('Deseja APROVAR esta Ordem de Serviço?')) {

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?aprOS=1&idOS="+os+"&dias="+dias+"&reaprovar="+reaprovar,
			cache: false,
			success: function(data) {

				retorno = data.split('|');

				if (retorno[0]=="OK") {
					//alert(retorno[1]);
					$('#aprova_'+numero).remove();
					$('#reprova_'+numero).parent('td').remove();

                    $('#cont_'+numero).attr('colspan',2).html("<p><strong style='color:green;'>Aprovada com sucesso.</strong></p>");

                    setTimeout(function() {
                        $('#'+os).remove();
                    }, 3000);

				} else {
					alert(retorno[1]);
				}

			}

		});

	}

}

function abreMotivo(os) {

	$("#linha_motivo_"+os).toggle();

}

function reprovaOS(os,posto,dias,auditoria) {

	var motivo = $("#motivo_"+os).val();

	if (motivo == "") {
		alert("Informe uma justificativa");
	} else {

        <?php if($login_fabrica == 74){ ?>
            var text_reprovar = "Cancelar";
        <?php }else{ ?>
            var text_reprovar = "Reprovar";
        <?php }?>

		if (confirm('Deseja '+text_reprovar+' esta Ordem de Serviço?')) {

			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?repOS=1&idOS="+os+"&posto="+posto+"&motivo="+motivo+"&auditoria="+auditoria,
				cache: false,
				success: function(data) {

					retorno = data.split('|');

					if (retorno[0]=="OK") {
                        //alert(retorno[1]);
                        $('#aprova_'+os).remove();
                        $('#reprova_'+os).parent('td').remove();

                        <?php if($login_fabrica == 74){ ?>
                            $('#cont_'+os).attr('colspan',2).html("<p><strong style='color:red;'>Cancelada com sucesso.</strong></p>");
                        <?php }else{ ?>
                            $('#cont_'+os).attr('colspan',2).html("<p><strong style='color:red;'>Reprovada com sucesso.</strong></p>");
                        <?php }?>                        

                        setTimeout(function() {
                            $('#'+os).remove();
                            $("#linha_motivo_"+os).remove();
                        }, 3000);



					} else {
						alert(retorno[1]);
					}

				}
			});

		}

	}

}

<?php if(in_array($login_fabrica, array(101))){ ?>

function abreInteracao(linha,os,tipo) {

    $.get(
        'ajax_grava_interacao.php',
        {
            linha:linha,
            os:os,
            tipo:tipo
        },
        function (resposta){
            resposta_array = resposta.split("|");
            resposta = resposta_array [0];
            linha = resposta_array [1];
            $('#interacao_'+linha).html(resposta);
            $('#comentario_'+linha).focus;

        }
    )

}


function gravarInteracao(linha,os,tipo) {
    var comentario = $.trim($("#comentario_"+linha).val());

    if (comentario.length == 0) {
        alert("Insira uma mensagem para interagir");
    } else {
        $.ajax({
            url: "ajax_grava_interacao_new.php",
            type: "GET",
            data: {
                linha: linha,
                os: os,
                tipo: tipo,
                comentario: comentario
            },
            beforeSend: function () {
                $("#interacao_"+linha).hide();
                $("#loading_"+linha).show();
            },
            complete: function(data){
                data = data.responseText;

                if (data == "erro") {
                    alert("Ocorreu um erro ao gravar interação");
                } else {
                    $("#loading_"+linha).hide();
                    $("#gravado_"+linha).show();

                    setTimeout(function () {
                        $("#gravado_"+linha).hide();
                    }, 3000);

                    $("#linha_"+linha).css({
                        "background-color": "#FFCC00"
                    });
                }

                $("#comentario_"+linha).val("");
                refreshInteracoes(linha, os);
            }
        });
    }
}

function refreshInteracoes(linha, os) {
    $.ajax({
        url: "ajax_refresh_interacao.php",
        type: "POST",
        data: {
            linha: linha,
            os: os
        },
        complete: function (data) {
            $("#interacao_"+linha).find("td[rel=interacoes]").html(data.responseText);
        }
    })
}

function div_detalhe_carrega (campos) {
    campos_array = campos.split("|");
    resposta = campos_array [1];
    linha = campos_array [2];
    os = campos_array [3];

    if (resposta == 'ok') {
        document.getElementById('interacao_' + linha).innerHTML = "Gravado Com sucesso!!!";
        document.getElementById('btn_interacao_' + linha).innerHTML = "<font color='red'><a href='#' onclick='abreInteracao("+linha+","+os+",\"Mostrar\")'><img src='imagens/btn_interagir_amarelo.gif' title='Aguardando Resposta do Posto'></a></font>";
//      var linha = new Number(linha+1);
        var table = document.getElementById('linha_'+linha);
//      alert(document.getElementById('linha_'+linha).innerHTML);
        table.style.background = "#FFCC00";
    }
}

<?php } ?>

</script>

<div class='texto_avulso'> Este Relatório considera a data de Abertura das OS </div> <br /><?php

if (strlen($msg_erro) > 0) {?>
	<table align='center' width='700' class='msg_erro'>
		<tr><td><? echo $msg_erro; ?> </td></tr>
	</table><?php
}?>

<form name='frm_pesquisa' method='post' action='<? echo $PHP_SELF; ?>'>
	<table align='center' width='700' class='formulario'>
		<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td class='espaco'>
				Nº OS <br />
				<input type='text' name='os' id='os' value='<?= $os; ?>' size='15' class="frm" onkeypress='return SomenteNumero(event)' />
			</td>
		</tr>
		<tr>
			<td class='espaco'>
				Cod Posto <br />
				<input type="text" name="posto_codigo" id="posto_codigo" class="frm" value="<?php echo $posto_codigo; ?>" size="10" maxlength="30" />&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto2 (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_descricao,'codigo')">
			</td>

			<td>
				Nome Posto <br />
				<input type="text" name="posto_descricao" id="posto_descricao" class="frm" value="<?php echo $posto_descricao; ?>" size="50" maxlength="50" />&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto2 (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_descricao,'nome')">
			</td>
		</tr>

		<tr>
			<td class='espaco'>
				Data Inicial <br />
				<input type='text' name='data_inicial' id='data_inicial' size='12' value='<?= $data_inicial; ?>' class="frm">
			</td>

			<td>
				Data Final <br />
				<input type='text' name='data_final' id='data_final' size='12' value='<?= $data_final; ?>' class="frm">
			</td>
		</tr>
		<tr>
			<td colspan='2' class='espaco'>
				Tipo Auditoria <br />
				<select name='tipo_auditoria' class='frm'>
<?php
					if ($login_fabrica == 91) {?>
						<option value='102' <? if ($tipo_auditoria==102) echo "selected";?>>OS Pendentes com Número de Série de autorização</option>
						<option value='103' <? if ($tipo_auditoria==103) echo "selected";?>>OS Aprovada com Número de Série de autorização</option><?php
					} if($login_fabrica == 101){
?>
						<option value='20' <? if ($tipo_auditoria==20) echo "selected";?>>Auditoria de Peças em Garantia</option>
<?
                    }else {
?>
						<option value='150' <? if($tipo_auditoria==150) echo "selected";?>>OS Abertas entre 30 e 70 dias</option>
						<option value='148' <? if($tipo_auditoria==148) echo "selected";?>>OS Abertas a mais de 70 dias</option>
<?php

							$label_sem_serie = ($login_fabrica == 74) ? 'Número de série irregular' : 'OS Abertas sem Número de Série';

?>
						<option value='102' <? if($tipo_auditoria==102) echo "selected";?>><?=$label_sem_serie?></option>
<?php
					}

					// HD 708057

					/**
					 * HD 854585 - Acrescentando novos filtros.
					 * Tambem alterei os filtros para array para melhor manutenção
					 * @author Brayan
					 */
					if ($login_fabrica == 74) {

						$options = array (
							67 			=>	'OS Reincidente N° de Série',
							0 			=>	'Todas em auditoria',
							163			=>	'Data de Compra acima de 12 meses da Data de Fabricação',
							176			=>	'Auditoria de OS Revenda',
							196         =>  'Peças Alteradas'
						);

						foreach ( $options as $key => $value ) {

							$selected = ($tipo_auditoria == $key) ? "selected" : '';

							echo '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';

						}

					}
				?>
				</select>
			</td>
		</tr>
		<?php
		if($login_fabrica == 101){
			?>
			<tr>
				<td colspan='2' class='espaco'>

					Mostrar OS <br />

					<input type="radio" name="mostrar_os" value="em_aprovacao" /> Em Aprovação <br />
					<input type="radio" name="mostrar_os" value="reprovadas" /> Reprovadas

				</td>
			</tr>
			<?php
		}
		?>
		<tr>
			<td colspan='2' align='center' style='padding:20px 0 10px 0;'>
				<input type='hidden' name='btn_acao' value=''>
				<input type="button" value="Pesquisar" onclick="if (document.frm_pesquisa.btn_acao.value == '') { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer;" />
			</td>
		</tr>
	</table>
</form>
<br /><?php

 if (!empty($btn_acao) && empty($msg_erro)) {

	$sql = "SELECT interv_reinc.os INTO temp tmp_status
		FROM (
			  SELECT
				  ultima_reinc.os,
					(SELECT status_os
						 FROM tbl_os_status
						 WHERE fabrica_status = $login_fabrica
						 AND tbl_os_status.os = ultima_reinc.os AND status_os IN ($condStatus) order by data desc LIMIT 1) AS ultimo_reinc_status
				  FROM (SELECT DISTINCT os
				   FROM tbl_os_status
				   JOIN tbl_os USING(os)
				WHERE fabrica_status = $login_fabrica
				  AND tbl_os.fabrica = $login_fabrica
				$condOS
				";


	$sql .= "		AND status_os IN ($condStatus) ) ultima_reinc
			) interv_reinc
		WHERE interv_reinc.ultimo_reinc_status IN ($status);

	SELECT distinct tbl_os.os, tbl_os.sua_os                                    ,
			   TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS  data_digitacao  ,
			   tbl_posto_fabrica.posto                                          ,
			   tbl_posto_fabrica.codigo_posto                                   ,
			   tbl_posto.nome                                                   ,
			   tbl_produto.descricao                                            ,
			   tbl_produto.referencia                                           ,
			   (CURRENT_DATE - tbl_os.data_abertura::date) AS qtde_dias,
			   (select  array_to_string(array_agg(os_status || '|||'||status_os), ',') from tbl_os_status where tbl_os_status.os = tbl_os.os AND fabrica_status = $login_fabrica) as status_os
			FROM tbl_os
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto USING(produto)
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os AND fabrica_status = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.os IN(SELECT os FROM tmp_status)";

		$sql .= " $cond " ;

	$sql .= $condPosto." ".$condOS;

	if ($tipo_auditoria != 103) {
		$sql .=	"		AND tbl_os.finalizada IS NULL ";
	}

  //echo nl2br($sql); exit;

  $res   = pg_exec($con,$sql);
	$total = pg_numrows($res);

	if ($total > 0) {?>

		<table align='center' class='tabela' id="relatorio" cellspacing='1'>

			<?php
				if ($tipo_auditoria != 102) {?>
					<caption class='titulo_tabela'>OS Aberta a mais de <?php echo $intervalo; ?> Dias</caption><?php
				} else {?>
					<caption class='titulo_tabela'>OS Abertas sem Número de Série</caption><?php
				}
			?>

			<thead>
				<tr class="titulo_coluna">
					<th>OS</th>
					<th>Data Abertura</th>
					<th>Posto</th>
					<th>Produto</th>
					<th>Qtde Dias</th>

                    <?php

                    if(in_array($login_fabrica, array(101))){
                        echo "<th> Interação </th>";
                    }

                    ?>

                    <?php

					if ($login_fabrica == 74 || $login_fabrica == 91) {
						echo '<th>Status</th>';
						if ($login_fabrica == 74) {
							echo '<th>Observação</th>';
						}
					}

					if ($tipo_auditoria != 103) {?>
						<th colspan='2'>Ação</th>
					<?php } ?>

				</tr>
			</thead>

			<tbody>

			<?php

			for ($i = 0; $i < $total; $i++) {

				$os           = pg_result($res, $i, 'os');
				$sua_os		  = pg_result($res, $i, 'sua_os');
				$digitacao    = pg_result($res, $i, 'data_digitacao');
				$posto        = pg_result($res, $i, 'posto');
				$codigo_posto = pg_result($res, $i, 'codigo_posto');
				$nome_posto   = pg_result($res, $i, 'nome');
				$produto      = pg_result($res, $i, 'descricao');
				$referencia   = pg_result($res, $i, 'referencia');
				$qtde_dias    = pg_result($res, $i, 'qtde_dias');
				$statuss= array();
				$status_oss    = explode(",",pg_result($res, $i, 'status_os'));
				foreach($status_oss as $valor){
					list($os_status,$status_os) = explode('|||',$valor);
					$statuss[$os_status] = $status_os;
				}

                ksort($statuss);
				if($tipo_auditoria == 0) {
					$verifica_aprovado = verificaTodasAuditoria($statuss);
					if($verifica_aprovado) continue;
				}

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				if ($login_fabrica == 74 || $login_fabrica == 91) {//HD 708057

					$sql2 = "SELECT DISTINCT tbl_status_os.descricao, observacao,status_os
							   FROM tbl_os_status
							   JOIN tbl_status_os USING(status_os)
							  WHERE os = $os
								AND tbl_status_os.status_os IN(67,102,103,163,176)";
					$res2      = pg_query($con, $sql2);
					$status_os = array();
					$obs_os 	= array();

					for ($z = 0; $z < pg_num_rows($res2); $z++) {
                        $id_status = pg_result($res2, $z, 'status_os');
						$status_os[] = pg_result($res2, $z, 'descricao');
						$obs_os[]	 = pg_result($res2, $z, 'observacao');

					}
                    $status_os_desc = implode(' / ', $status_os);
					$obs_os_desc	= implode(' / ', $obs_os);

				}?>

				<tr bgcolor='<? echo $cor; ?>' id='<? echo $os; ?>'>
					<td><a href='os_press.php?os=<?=$os;?>' target='_blank'><?php echo (!empty($sua_os)) ? $sua_os : $os; ?></a></td>
					<td><? echo $digitacao; ?></td>
					<td><? echo $codigo_posto." - ".$nome_posto; ?></td>
					<td><? echo $referencia." - ".$produto; ?></td>
					<td><? echo $qtde_dias; ?></td>

                    <?php 
                    if (in_array($login_fabrica, array(101))) {

                        $x = $i;

                        $sqlint = "SELECT 
                                        os_interacao, 
                                        admin
                                    FROM tbl_os_interacao
                                    WHERE os = {$os}
                                        AND interno IS NOT TRUE
                                    ORDER BY os_interacao DESC
                                    LIMIT 1";
                        $resint = pg_query($con, $sqlint);

                        if (pg_num_rows($resint) == 0) {
                            $botao = "<img src='imagens/btn_interagir_azul.gif' title='Enviar Interação com Posto' />";
                        } else {
                            $admin = pg_fetch_result($resint, 0, "admin");

                            if (strlen($admin) > 0) {
                                $botao = "<img src='imagens/btn_interagir_amarelo.gif' title='Aguardando Resposta do Posto' />";
                            } else {
                                $botao = "<img src='imagens/btn_interagir_verde.gif' title='Posto Respondeu, clique aqui para visualizar' />";
                            }
                        }

                    }
                    ?>

                    <td>
                        <div id="btn_interacao_<?=$x?>" style='cursor: pointer;' onclick='if ($("#interacao_<?=$x?>").is(":visible")) { $("#interacao_<?=$x?>").hide(); } else { $("#interacao_<?=$x?>").show(); }'>
                            <?=$botao?>
                        </div>
                    </td>

					<?php
						if ($login_fabrica == 74 || $login_fabrica == 91) {
							echo '<td>'. $status_os_desc . '</td>';
							if ($login_fabrica == 74) {
								echo '<td>'.$obs_os_desc.'</td>';
							}
						}


					if ($tipo_auditoria == 148) {
						$intervalo = 69;
					} else if ($tipo_auditoria == 150) {
						$intervalo = 30;
					} else {
						$intervalo = 1001;
					}

					if($login_fabrica == 101){
						list($number, $status_osss) = explode("|||", $status_oss[0]);
					}

					if ($tipo_auditoria != 103) {

						if($login_fabrica == 101 && $status_osss == 81){
							?><td><input type='button' value='Aprovar' id='aprova_<?=$os;?>' onclick='aprovaOS(<?=$os;?>, <?=$os;?>, <?=$intervalo;?>, 81);'></td><?php
						}else{
							?><td id="cont_<?=$os;?>"><input type='button' value='Aprovar' id='aprova_<?=$os;?>' onclick='aprovaOS(<?=$os;?>, <?=$os;?>, <?=$intervalo;?>);'></td><?php
						}
					}

					if (in_array($tipo_auditoria,array(0,20,67,102,163,176))) {

                        $btn_reprovar = ($login_fabrica == 74 )? "Cancelar" : "Reprovar";
                    ?>
						<?php
							if($login_fabrica == 101){

								if($status_osss == 81){
									echo "<td><span id='reprova_$os'><strong style='color: #ff0000;'>Reprovado</strong></td>";
								}else{
									?>
								<td><input type='button' value='Reprovar' id='reprova_<? echo $os; ?>' onclick='abreMotivo(<?=$os;?>);'></td>
								<?php
								}

							}else{ ?>
								<td><input type='button' value='<?=$btn_reprovar?>' id='reprova_<? echo $os; ?>' onclick='abreMotivo(<?=$os;?>);'></td>
								<?php
							}

					}?>
				</tr>
				<tr style='display:none;' id='linha_motivo_<?=$os;?>'>
					<td colspan='7'>
						Justificativa: <input type='text' name='motivo_<?=$os;?>' id='motivo_<?=$os;?>' class='frm' size='120'> &nbsp;
						<?php if($login_fabrica == 74){ ?>
                            <input type='button' value='Gravar' onclick='reprovaOS(<?=$os;?>, <?=$posto;?>, <?=$intervalo;?>, <?=$id_status;?>);' />
                        <?php }else{ ?>
                            <input type='button' value='Gravar' onclick='reprovaOS(<?=$os;?>, <?=$posto;?>, <?=$intervalo;?>, <?=$tipo_auditoria;?>);' />
                        <?php } ?>
					</td>
				</tr>

                <?php if(in_array($login_fabrica, array(101))){ ?>

                <tr>
                    <td colspan="12" >
                        <div id="loading_<?=$x?>" style="display: none;"><img src="imagens/ajax-loader.gif" /></div>
                        <div id="gravado_<?=$x?>" style="font-size: 14px; background-color: #669900; color: #FFFFFF; font-weight: bold; display: none;">Interação gravada</div>
                        <div id="interacao_<?=$x?>" style="display: none;">
                            <table align="center" border="0" cellspacing="1" cellpadding="0" class="tabela" style="width: 700px; margin: 0 auto;" >
                                <tr>
                                    <th>INTERAGIR NA OS</th>
                                </tr>
                                <tr>
                                    <td class="conteudo" style="text-align: center;" >
                                        <textarea name="comentario_<?=$x?>" id="comentario_<?=$x?>" style="width: 400px;"></textarea>
                                    </td>
                                </tr>
                                <?php
                                $sql_i = "SELECT
                                            tbl_os_interacao.os_interacao,
                                            to_char(tbl_os_interacao.data,'DD/MM/YYYY HH24:MI') as data,
                                            tbl_os_interacao.comentario,
                                            tbl_os_interacao.interno,
                                            tbl_os.posto,
                                            tbl_posto_fabrica.contato_email as email,
                                            tbl_admin.nome_completo
                                          FROM tbl_os_interacao
                                          JOIN tbl_os            ON tbl_os.os    = tbl_os_interacao.os
                                          JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                                          LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
                                          WHERE tbl_os_interacao.os = $os
                                          AND tbl_os.fabrica = {$login_fabrica}
                                          AND tbl_os_interacao.interno IS NOT TRUE
                                          ORDER BY tbl_os_interacao.os_interacao DESC";
                                $res_i  = pg_query($con, $sql_i);
                                ?>
                                <tr>
                                    <td rel="interacoes">
                                        <?php
                                        if (pg_num_rows($res_i) > 0) {
                                        ?>
                                            <table border="0" cellspacing="1" cellpadding="0" style="width: 700px; margin: 0 auto;" >
                                                <thead>
                                                    <tr>
                                                        <th class="titulo">Nº</th>
                                                        <th class="titulo">Data</th>
                                                        <th class="titulo">Mensagem</th>
                                                        <th class="titulo">Admin</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $k = 1;

                                                    while ($result_i = pg_fetch_array($res_i)) {
                                                        if ($result_i["interno"] == 't') {
                                                            $cor = "style='font-family: Arial; font-size: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
                                                        } else {
                                                            $cor = "class='conteudo'";
                                                        }
                                                        ?>
                                                        <tr>
                                                            <td width="25" <?=$cor?> ><?=$k?></td>
                                                            <td width="90" <?=$cor?> nowrap ><?=$result_i["data"]?></td>
                                                            <td <?=$cor?> ><?=$result_i["comentario"]?></td>
                                                            <td <?=$cor?> nowrap ><?=$result_i["nome_completo"]?></td>
                                                        </tr>
                                                    <?php
                                                        $k++;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        <?php
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                            <br />
                            <img src="imagens/btn_gravar.gif" style="cursor:pointer" onclick="gravarInteracao(<?=$x?>, <?=$os?>, 'Gravar');">
                        </div>
                    </td>
                </tr>

                <?php } ?>

            <?php

			}

		echo '</tbody></table>';

	} else {
		echo "<center>Nenhum Resultado Encontrado</center>";
	}

}

include "rodape.php" ?>
