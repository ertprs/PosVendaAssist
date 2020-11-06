<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if($_POST['btn_acao']) $btn_acao = $_POST['btn_acao'];
if($_POST['os_total']) $os_total = $_POST['os_total'];
if($_POST['btn_pesquisar']) $btn_pesquisar = $_POST['btn_pesquisar'];

/*HD: 135222 - IGOR - 02/08/2009*/
/*NÃO LIBERAR A TELA PARA OUTRO FABRICANTE, POIS JÁ DEU PROBLEMAS*/
/*if(['btn_acao'] == 0){

}*/

if ($_POST["jaAnexou"]) {

	$extrato = $_POST["extrato"];

	$sqlValidaAnexos = "SELECT tbl_tdocs.obs
	                   FROM tbl_tdocs
	                   WHERE referencia_id = '{$extrato}'
	                   AND situacao = 'ativo'
	                   AND fabrica = {$login_fabrica}";
	$resValidaAnexos = pg_query($con, $sqlValidaAnexos);

	$anexosInseridos = [];
	while ($dadosTdocs = pg_fetch_object($resValidaAnexos)) {

	    $arrObs = json_decode($dadosTdocs->obs, true);

	    $anexosInseridos[] = $arrObs[0]["typeId"];

	}

    if (in_array("nota_fiscal_mo", $anexosInseridos) || in_array("peca_prod_trocados", $anexosInseridos)) {
    	echo json_encode(["success"=>"ja anexou"]);
		exit();
    } 

    echo json_encode(["error"=>"nao anexou"]);
	exit();
}

if ($_POST["aceitarConcluirProcesso"]) {
	$extrato = $_POST["extrato"];

	if (!empty($extrato)) {
		$sql = " SELECT extrato FROM tbl_extrato_status WHERE extrato = $extrato AND fabrica = $login_fabrica ";
		$res = pg_query($con, $sql);
		
		if (pg_num_rows($res) > 0) {
			$sql = "UPDATE tbl_extrato_status SET obs = 'Anexos Enviados' WHERE extrato = $extrato AND fabrica = $login_fabrica ";
		} else {
			$sql = " INSERT INTO tbl_extrato_status (extrato, obs, fabrica, data) VALUES ($extrato, 'Anexos Enviados', $login_fabrica, CURRENT_TIMESTAMP) ";
		}

		$res = pg_query($con, $sql);
		if (pg_last_error()) {
			echo json_encode(["error"=>"Erro ao Concluir Processo"]);
			exit();
		} else {
			echo json_encode(["success"=>"Processo Concluido com Sucesso"]);
			exit();
		}
	}

	echo json_encode(["error"=>"Extrato não encontrado"]);
	exit();
}

if (isset($_REQUEST["gravar_caixas"])) {

	$extrato = $_REQUEST["extrato"];

	$qtde_caixas = $_REQUEST['qtde_caixas'];

  	$sqlAtualizaExtra = "UPDATE tbl_extrato_extra
  						 SET lote_extrato = {$qtde_caixas}
  						 WHERE extrato = {$extrato}";
  	pg_query($con, $sqlAtualizaExtra);

  	if (pg_last_error()) {
		echo json_encode(["error"=>"Erro ao Grava Qtde de Caixas"]);
		exit();
	} else {
		echo json_encode(["success"=>"Qtde de Caixas Gravado com Sucesso"]);
		exit();
	}
}

if($login_fabrica <> 20 && $login_fabrica != 1){
	echo "<br><b><font color = 'red'>Acesso não permitido</font></b><br><br>";
	exit;
}

if(strlen($btn_pesquisar) > 0 and strlen($_POST['buscarOS']) > 0){
	unset($btn_acao);
}

if (!empty($extrato)) {
    $tempUniqueId = $extrato;
    $anexoNoHash = null;
} else if (strlen($_POST["anexo_chave"]) > 0) {
    $tempUniqueId = $_POST["anexo_chave"];
    $anexoNoHash = true;
} else {
    
    $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
  
    $anexoNoHash = true;
}

$sql = "SELECT valor_minimo_extrato
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica";

$res = pg_query($con,$sql);

$valor_minimo = pg_result($res,0,0);

if($login_fabrica == 1){
	$sql = "SELECT tbl_extrato.protocolo
				 FROM tbl_extrato
				 INNER JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato AND tbl_extrato_extra.baixado ISNULL
				 WHERE tbl_extrato.fabrica = $login_fabrica
				 AND tbl_extrato.posto = $login_posto
				 AND tbl_extrato.aprovado isnull
				 ORDER BY tbl_extrato.extrato DESC LIMIT 1";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$extrato_pendente = pg_fetch_result($res, 0, 'protocolo');
	}
	if(!empty($extrato_pendente)){
		$msg_erro .= "O extrato $extrato_pendente está aguardando aprovação";
	}
}

if(strlen($btn_acao)>0 AND $os_total > 0 and empty($msg_erro)){
	/* HD 684863 - Retirada a regra para todos os postos, de poder finalizar apenas um extrato por dia.. erros de duplicidade foi tratado nesse mesmo chamado */
	$ativo_arr = $_POST["ativo"];

	$res = pg_exec($con,"BEGIN TRANSACTION");

	$sql = "SELECT posto_interno
			FROM tbl_tipo_posto
			JOIN tbl_posto_fabrica ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			WHERE tbl_posto_fabrica.posto = $login_posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	$posto_interno = pg_fetch_result($res, 0, 'posto_interno');

	if($login_fabrica == 1) {
		$sql = "SELECT fn_fechamento_extrato_black($login_posto,$login_fabrica)";
	}else{
		$sql = "INSERT INTO tbl_extrato (posto, fabrica, avulso, total) VALUES ($login_posto,$login_fabrica, 0, 0) RETURNING extrato";
	}

	$res      = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	$extrato  = pg_fetch_result($res,0,0);

	if($extrato){

		// for( $i=0 ; $i < $os_total ; $i++){

			foreach ($ativo_arr as $key => $value) {
				$os = $value;

				$sql = "UPDATE tbl_os_extra SET extrato = $extrato WHERE os = $os AND extrato isnull;

					UPDATE tbl_os_revenda
						SET extrato_revenda = $extrato
					FROM tbl_os_revenda_item
					WHERE  tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					AND    tbl_os_revenda.os_geo             IS TRUE
					AND    tbl_os_revenda.extrato_revenda    IS NULL
					AND    tbl_os_revenda.data_fechamento    IS NOT NULL
					AND    tbl_os_revenda.finalizada         IS NOT NULL
					AND	tbl_os_revenda_item.os_lote = $os ;";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if($login_fabrica == 1){
					$sql = "SELECT fn_calcula_os_item_black($os ,$login_fabrica);";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		// }
	}

	if($login_fabrica == 1) {
		$sql = "INSERT INTO tbl_extrato_lancamento (
					fabrica   ,
					posto     ,
					extrato   ,
					automatico,
					lancamento,
					valor     ,
					os_sedex
			)
				SELECT  $login_fabrica  ,
					$login_posto    ,
					$extrato  ,
					true      ,
					42         ,
					CASE WHEN total_pecas_destino IS NOT NULL THEN total_pecas_destino * (-1) ELSE 0 END,
					os_sedex
				FROM    tbl_os_sedex
				JOIN    tbl_os USING(os)
				JOIN    tbl_os_extra USING(os)
				WHERE   tbl_os_sedex.extrato_destino  IS NULL
				AND     tbl_os_extra.extrato = $extrato
				AND     tbl_os_sedex.produto          IS NOT TRUE
				AND     tbl_os_sedex.finalizada       IS NOT NULL
				AND    (tbl_os_sedex.extrato          IS NULL OR tbl_os_sedex.extrato = $extrato)
				AND     tbl_os_sedex.posto_destino    = $login_posto
				AND     tbl_os_sedex.posto_destino    NOT IN (6900 , 6901 )
				AND     tbl_os_sedex.fabrica          = $login_fabrica
				AND     tbl_os_sedex.finalizada::date <= current_date 	;

				UPDATE tbl_os_sedex SET
						extrato_destino = $extrato
				FROM tbl_os
				JOIN tbl_os_extra using(os)
				WHERE   tbl_os_sedex.extrato_destino  IS NULL
				AND     tbl_os_sedex.produto          IS NOT TRUE
				AND      tbl_os.os = tbl_os_sedex.os
				AND      tbl_os_extra.extrato = $extrato
				AND     tbl_os_sedex.finalizada       IS NOT NULL
				AND    (tbl_os_sedex.extrato          IS NULL OR tbl_os_sedex.extrato = $extrato)
				AND     tbl_os_sedex.posto_destino    = $login_posto
				AND     tbl_os_sedex.fabrica          = $login_fabrica
				AND     tbl_os_sedex.finalizada::date <= CURRENT_DATE; ";
		$res = pg_query($con,$sql);
	}

	if($posto_interno == 't'){
		$sql = "UPDATE tbl_extrato_extra SET exportado = CURRENT_TIMESTAMP WHERE extrato = $extrato";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if (in_array($login_fabrica, [20])) {
		  //Verifica avulsos pendentes de extrato
		  $sqlTotalAvulsosPendentes = "SELECT * FROM (
			  							   SELECT SUM(tbl_extrato_lancamento.valor) as total_avulsos
			                               FROM tbl_extrato_lancamento
			                               WHERE tbl_extrato_lancamento.posto = {$login_posto}
			                               AND tbl_extrato_lancamento.extrato IS NULL
			                               AND tbl_extrato_lancamento.fabrica = {$login_fabrica}
			                           ) as avulso
			                           WHERE avulso.total_avulsos IS NOT NULL";
		  $resTotalAvulsosPendentes = pg_query($con, $sqlTotalAvulsosPendentes);

		  if (pg_num_rows($resTotalAvulsosPendentes) > 0) {

		    // Recalcula o total do extrato antes de inserir os avulsos
		    $total_avulsos = pg_fetch_result($resTotalAvulsosPendentes, 0, 'total_avulsos');

		    $sqlTotalExtrato = "SELECT tbl_extrato.total as total_extrato
		                        FROM tbl_extrato 
		                        WHERE extrato = {$extrato}";
		    $resTotalExtrato = pg_query($con, $sqlTotalExtrato);

		    $total_extrato = pg_fetch_result($resTotalExtrato, 0, 'total_extrato');
		    
		    $totalRecalculado = $total_extrato + $total_avulsos;

		    //Valor total do extrato não pode ser negativo
		    if ($totalRecalculado > 0) {

		      $sqlAtualizaAvulso = "
		            UPDATE tbl_extrato_lancamento SET extrato = {$extrato}
		            WHERE tbl_extrato_lancamento.fabrica = {$login_fabrica}
		            AND tbl_extrato_lancamento.extrato IS NULL
		            AND tbl_extrato_lancamento.posto = {$login_posto};

		            UPDATE tbl_extrato
		            SET total = {$totalRecalculado}, 
		                avulso = avulso + {$total_avulsos}
		            WHERE tbl_extrato.extrato = {$extrato}
		            ";
		      $resAtualizaAvulso = pg_query($con, $sqlAtualizaAvulso);

		    }

		  }

	}

	if (strlen($msg_erro) == 0) {
		$sql1 = "select count(os) from tbl_os_extra where extrato = $extrato;";
		$res1 = @pg_query($con,$sql1);
		if(@pg_fetch_result($res1,0,0) == 0){
			$msg_erro .= "Extrato sem nenhuma Ordem de Serviço, favor repetir o procedimento!";
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}else{
			$valor_total = 70;

			if ($login_fabrica == 1) {
				$query_total = pg_query($con, "SELECT total FROM tbl_extrato WHERE extrato = $extrato");
				$valor_total = 0;

				if (pg_num_rows($query_total) > 0) {
					$valor_total = (int) pg_fetch_result($query_total, 0, 'total');
				}
			}

			if ($valor_total < 70) {
				$msg_erro.= "Valor mínimo para gerar extrato: R$ 70,00.";
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			} else {
				$res = pg_query($con,"COMMIT TRANSACTION");

				require_once dirname(__FILE__) . '/class/email/mailer/class.phpmailer.php';

				$sql = "SELECT
						tbl_admin.admin,
						tbl_admin.nome_completo,
						tbl_admin.email,
						tbl_os.sua_os,
						tbl_posto.nome AS nome_posto,
						tbl_posto_fabrica.codigo_posto,
						tbl_extrato.protocolo,
						tbl_extrato_lancamento.os,
						tbl_extrato_lancamento.extrato,
						tbl_extrato_lancamento.valor
					FROM tbl_extrato_lancamento
						INNER JOIN tbl_admin ON tbl_admin.admin = tbl_extrato_lancamento.admin AND tbl_admin.fabrica = {$login_fabrica}
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_extrato_lancamento.posto
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato_lancamento.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_lancamento.extrato AND tbl_extrato.fabrica = {$login_fabrica}
						INNER JOIN tbl_os ON tbl_os.os = tbl_extrato_lancamento.os AND tbl_os.fabrica = {$login_fabrica}
					WHERE tbl_extrato_lancamento.fabrica = {$login_fabrica}
						AND tbl_extrato_lancamento.extrato = $extrato
					ORDER BY admin";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					$admin       = 0;
					$admin_email = "";
					$titulo      = "Extrato Avulso";
					$mail        = new PHPMailer();
	                $mail->IsHTML(true);

					while($objeto_extrato_avulso = pg_fetch_object($res)){
						if($admin == 0){
							$admin = $objeto_extrato_avulso->admin;
							$admin_email = $objeto_extrato_avulso->email;

							$mensagem = "Prezado(a) ".$objeto_extrato_avulso->nome_completo;
							$mensagem .= "<br/><br/>Lançamento avulso entrou no extrato.<br/>";
							$mensagem .= "<br/>Foi lançado o avulso referente a ";
						}

						if($admin != $objeto_extrato_avulso->admin){
							$mail->ClearAllRecipients();

						    $mail->From     = $admin_email;
			                $mail->FromName = $admin_email;
			                $mail->Subject  = $titulo;
			                $mail->Body     = $mensagem;

			                if (!$mail->Send()) {
			                    $msg_erro = 'Erro ao enviar email: ' . $mail->ErrorInfo;
			                }

					        $mensagem = "Prezado(a) ".$objeto_extrato_avulso->nome_completo;
							$mensagem .= "<br/><br/>Lançamento avulso entrou no extrato.<br/>";
							$mensagem .= "<br/> Foi lançado o avulso referente a ";

							$admin       = $objeto_extrato_avulso->admin;
							$admin_email = $objeto_extrato_avulso->email;
						}

						$mensagem .= "<br/><b>OS</b> ".$objeto_extrato_avulso->codigo_posto.$objeto_extrato_avulso->sua_os."<br/>";
						$mensagem .= "<b>Cód. Posto </b>".$objeto_extrato_avulso->codigo_posto."<br/>";
						$mensagem .= "<b>Posto </b>".$objeto_extrato_avulso->nome_posto."<br/>";
						$mensagem .= "<b>Protocolo</b> ".$objeto_extrato_avulso->protocolo."<br/>";
						$mensagem .= "<b>Valor</b> ".number_format($objeto_extrato_avulso->valor, 2, ",", ".")."<br/>";
					}

					$mail->ClearAllRecipients();
	                $mail->From     = $admin_email;
	                $mail->FromName = $admin_email;
	                $mail->Subject  = $titulo;
	                $mail->Body     = $mensagem;

	                $mail->AddAddress($admin_email);

	                if (!$mail->Send()) {
	                    $msg_erro = 'Erro ao enviar email: ' . $mail->ErrorInfo;
	                }
				}

				if($login_fabrica == 1){
					header ("Location: $PHP_SELF");
				}else{
					header ("Location: $PHP_SELF?extrato=$extrato&op=novo");
				}
				exit;
			}
		}
	} else {
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

if(strlen($_POST["extrato"])>0) {
	$extrato      = $_POST["extrato"]     ;
	$nf_devolucao = $_POST["nf_devolucao"];
	$nf_mo        = $_POST["nf_mo"]       ;
	if( strlen($nf_mo)>0 OR strlen($nf_devolucao)>0 ){
		$sql = "UPDATE tbl_extrato_extra SET nota_fiscal_mao_de_obra = '$nf_mo', nota_fiscal_devolucao = '$nf_devolucao'
			WHERE extrato = $extrato";
	}
	$res = @pg_exec($con,$sql);
}

$layout_menu = 'os';
$title = ($sistema_lingua == 'ES') ? "Cierre de Extracto/lote" : "Fechamento de Extrato/Lote";
include 'cabecalho.php';
?>

<style type="text/css">
.tabela_extratos td {
	height: 50px;
}

div .box-uploader-anexo {
	height: 240px !important;
}

.btn-delete-file {
	height: 27px !important;
}

.anexar_nfe, .imprimir {
  color: white;
  cursor: pointer;
  padding: 10px;
  border-radius: 3px;
  border: none;
  font-family: sans-serif;
  font-size: 12px;
  font-weight: bolder;
}
.anexar_nfe {
  background-color: darkblue;
}
.anexar_nfe:hover {
  background-color: #0024f2;
  transition: 0.25s ease;
}
.imprimir {
  background-color: #bab100;
}
.imprimir:hover {
  background-color: #bdb51e;
}

#box-uploader-app {
	width: 700px !important;
	background-color: white !important;
}

.Tabela{
	border:1px solid #d2e4fc;
	background-color:#d2e4fc;
	}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.titulo3 {
	font-family: Arial;
	font-size: 7pt;
	text-align: left;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	color: #000000;
}
.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;

}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Conteudo2 {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.Principal{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
div.exibe{
	padding:8px;
	color:  #555555;
	display:none;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
    width:700px;
    margin:auto;
}
.table_line2 {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    border: 0px solid;
    background-color: #D9E2EF
}
.table_line {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    background-color: #D9E2EF
}
</style>

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<script src="js/jquery.maskedinput.js"></script>

<!-- Ajax TULIO -->
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<script type="text/javascript" src='plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />

<script type="text/javascript" charset="utf-8">
	function gravaDatacoleta (data_coleta,extrato,linha) {
		requisicaoHTTP ('GET','extrato_coleta_ajax.php?extrato=' + escape(extrato) + '&data_coleta=' + data_coleta + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
	}

	$(window).load(function() {
		Shadowbox.init();

		$("#concluir_precesso").click(function() {

			let extrato = '<?=$extrato?>';
			let bodyMsg = '';

			$.ajax({
	                type: 'POST',
	                url: "extrato_fechamento.php",
	                data: { jaAnexou:true, extrato:extrato  },
	                dataType : "json",
	                async: false,
	                success: function(data){
	                 	if (data.error) {
	                      bodyMsg = "<div style='background:#FFFFFF;height:100%;text-align:center;'><br><div style='background:#58699a;'><p style='font-size:20px;font-weight:bold;color:white'>Termos e Condições</p></div><br /><div><b>Para Concluir o Processo é Necessário Inserir os Anexos.</b></div><br /><br /><input type='button' value='Fechar' style='padding: 5px; width:80px;' onclick=\"javascript:Shadowbox.close();\"></p></div>"
	                    } else if (data.success) {
	                    	bodyMsg = "<div style='background:#FFFFFF;height:100%;text-align:center;'><br><div style='background:#58699a;'><p style='font-size:20px;font-weight:bold;color:white'>Termos e Condições</p></div><br /><div><b>O Posto Autorizado confirma e assume que as peças reportadas estão todas identificadas e disponíveis para avaliação ou coleta, conforme fotos anexadas.</b></div><br /><br /><p><input type='button' value='Aceitar' style='padding: 5px; width:80px;' onclick=\"javascript:aceitarConcluirProcesso("+extrato+"); Shadowbox.close();\">&nbsp &nbsp <input type='button' value='Recusar' style='padding: 5px; width:80px;' onclick=\"javascript:Shadowbox.close();\"></p></div>";
	                    }
	                }
	        });

			Shadowbox.open({
		        content:    bodyMsg,
		        player:     "html",
		        title:      "Concluir Processo.",
		        width:      600,
		        height:     250
		    });

		});

		<?php if ($login_fabrica == 20 && !empty($_GET["extrato"]) && !anexoExtratoEnviadoBosch($extrato)) { ?>
	            window.addEventListener('beforeunload', (event) => {
				    event.preventDefault();
				    event.returnValue = 'Deseja realmente sair da página sem concluir o Processo ?';
				});
		<?php } ?>

		<?php if(!empty($extrato_pendente)){ ?>
				var extrato_pendente = <?=$extrato_pendente?>;
		<?php } ?>

		<?php if ( !empty($valor_minimo) ) : ?>

			total_extrato = 0;
			minimo = <?=$valor_minimo?>;
			submit = false;

			function calcula_valor_extrato( obj ) {

				valor_mo = parseFloat( $(obj).parent().find('input[type=hidden].mo_os').val() );

				if ( $(obj).is(':checked') ) {

					total_extrato += valor_mo;

				} else if( parseFloat(total_extrato) > 0){

					if ( total_extrato - valor_mo > 0) {

						total_extrato -= valor_mo;

					} else
						total_extrato = 0;

				}

				total_extrato = parseFloat(total_extrato);
				total_extrato = Math.round(total_extrato*100) / 100;
				//total_extrato = total_extrato.toFixed(2);

				$("#total_lote").hide()
								.css('color', 'green')
								.html(total_extrato.toFixed(2))
								.fadeIn("slow", function() {

									$("#total_lote").css('color', 'inherit');
				});

			}

			$("input[type=checkbox].check_os").each(function(){

				if ( $(this).is(':checked') )
					calcula_valor_extrato($(this));

			});

			$("input#ativo").click(function(){

				$("input[type=checkbox].check_os").each(function(){

                                	        calcula_valor_extrato($(this));
				});
			});

			$("input[type=checkbox].check_os").click(function(){

				calcula_valor_extrato( $(this) );

			});

			$("#btn_acao").click(function(e) {

				if (submit === true) {
					alert('Aguarde Submissão');
				}

				if(extrato_pendente != ""){
					alert('O extrato '+extrato_pendente+' está aguardando aprovação');
					return false;
				}

				if ( total_extrato < minimo ) {

					alert('O extrato deve ter no mínimo R$ ' + minimo.toFixed(2));
					e.preventDefault();
					return false;

				}

				if ( confirm ("Confirma o fechamento do lote?") ) {

					submit = true;

					$("form[name=frm_extrato_os]").submit();

				}

			});

		<?php endif; ?>
		<?php
		if (!empty($extrato)) { ?>
			$("#qtde_caixas").numeric();
		<?php
		}
		?>
		
		$("input[rel='data_coleta']").maskedinput("99/99/9999");

	});

	$(function(){

		$("#alterar_caixas").click(function(){

			let extrato 	= $(this).data("extrato");
			let qtde_caixas = $("#qtde_caixas").val();

			if (qtde_caixas.length == 0 || qtde_caixas <= 0) {
				alert("Informe a quantidade de caixas");
				return;
			}

			//location.href = "extrato_fechamento.php?gravar_caixas=true&qtde_caixas="+qtde_caixas+"&extrato="+extrato;

			$.ajax({
	                type: 'POST',
	                url: "extrato_fechamento.php",
	                data: { gravar_caixas:true, qtde_caixas:qtde_caixas, extrato:extrato  },
	                dataType : "json",
	                beforeSend: function(){
	                    $('#alterar_caixas').html("&nbsp;&nbsp;Salvando...&nbsp;&nbsp;<br><img src='imagens/loading_bar.gif'> ");
	                },
	                success: function(data){
	                	$('#alterar_caixas').html("Salvar");
	                 	if (data.error) {
	                      alert(data.error)
	                    } else if (data.success) {
	                    	alert(data.success)
	                    }
	                }
	        });
		});

	})

	function aceitarConcluirProcesso(extrato) {
		 $.ajax({
                type: 'POST',
                url: "extrato_fechamento.php",
                data: { aceitarConcluirProcesso:true, extrato:extrato },
                dataType : "json",
                success: function(data){
                 	if (data.error) {
                      alert(data.error)
                    } else if (data.success) {
                    	alert(data.success)
                    	window.location.href = 'extrato_fechamento.php';
                    }
                }
        });
	}

</script>

<script language='javascript'>

var totalMo = 0;
$(document).ready(function(){

	$(".check_os").click(function(){

		calcula_mo($(this));

	});

	$("input.check_os").change(function(){
		if($("input.check_os:checked").length == 0){
			$('#btn_acao').attr('disabled', true);
		}else{
			$('#btn_acao').removeAttr('disabled');
		}
	});

	<?php
	if (in_array($login_fabrica, [20])) { ?>
		$("#btn_acao").click(function(){

			$("form[name=frm_extrato_os]").submit();

		});

	<?php
	} ?>

});

function retornaOS (data , componente ) {
	results = data.split("|");
	if (typeof (results[0]) != 'undefined') {
		if (results[0] == 'ok') {
			var com = '#'+componente;
			$(com).html(results[1]);
		}else{
			alert ('Erro ao abrir OS' );
		}
	}else{
		alert ('Fechamento nao processado');
	}
}

function pegaOS (os,dados,cor) {
	$.ajax({
	    url: "ajax_os_press.php?op=ver&os=" + escape(os)+"&cor="+escape(cor),
	    cache: false,
	    success: function(data) {
	        retornaOS (data , dados) ;
	    }
    });
	// url = "ajax_os_press.php?op=ver&os=" + escape(os)+"&cor="+escape(cor) ;
	// http.open("GET", url , true);
	// http.onreadystatechange = function () { retornaOS (http , dados) ; } ;
	// http.send(null);
}

function MostraEsconde(dados,os,imagem,cor){
	if (document.getElementById){
		// this is the way the standards work
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			img.src='imagens/mais.gif';
		} else {
			style2.style.display = "block";
			img.src='imagens/menos.gif';
			pegaOS(os,dados,cor);
		}
	}
}

function SelecionaTodos(that) {

	let checked = $(that).is(":checked") ? true : false;

	totalMo = 0;

	$("#total_mo").text("0");
	$("#total_mo_extrato").val("0");

	$(".check_os").each(function(){

		$(this).attr("checked", checked);

		if (checked) {
			calcula_mo($(this));
		}

	});

}

function calcula_mo(that) {

	let os = $(that).val();

	let valorMo = parseFloat($("#"+os+"_mo").attr('data-mo'));

	if ($(that).is(":checked")) {

		totalMo += valorMo;

	} else {

		totalMo -= valorMo;

	}

	$("#total_mo").text(totalMo);
	$("#total_mo_extrato").val(totalMo);

}

function listarTodosExtratos(){
	window.location = 'extrato_fechamento.php';
 }


</script>
<?php
	echo "<table width='700' height'16' border'0' cellspacing='0' cellpadding='5' align='center'>";
	echo"<tr>";
	echo"<td align='center' width='16' bgcolor='FFE1E1'>&nbsp;</td>";
	echo"<td align='left'><font size=1><b>$nbsp ";
	fecho ("extrato.avulso",$con, $cook_idioma);
	echo"</b></font></td>";
	echo"</tr>";
	echo"<table>";
	echo"<br>";

	echo"<form method='post' name='frm_extrato_os' action=\"$PHP_SELF\">";
	?>
<?php
if($login_pais == 'BR' ){

echo "<table width='540' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>";
	echo "<tr>";
		echo"<td align='left'><font size=1 ><b>&nbsp;";?>
			<?php
			echo ("Buscar por Número da OS "); ?>
			<?php
				echo"</b></font>";
			echo"<input type='text' name='buscarOS' id='buscarOS'>";
		echo"</td>";
		echo"<td>";
			echo"<input type='submit' name='btn_pesquisar' value='Pesquisar'>";
		echo"</td>";
		echo"<td>";
			if($login_fabrica != 20){
				echo"<input type='button' name='btn_listar_extratos' value='Listar Todos Extratos' onclick='listarTodosExtratos()'>";
			}
		echo"</td>";
}
	echo"</tr>";
echo"</table>";
?>
<?php
//echo nl2br($sql);
?>
<?php
echo"<table width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
echo "<tr class='table_line' style='background-color: #D9E2EF;'>\n";
echo "<td align='center' colspan='2' nowrap><b>";
echo "<tr class='table_line'>\n";
echo "<td align=\"center\">";
fecho("nenhum.extrato.foi.encontrado",$con, $cook_idioma);
echo "</td>\n";
echo "</tr>\n";
echo "</table>";

if ($login_pais == "BR" && !empty($extrato) && in_array($login_fabrica, [20]) && $extrato > 4289724) {

	/*$sqlValidaAnexos = "SELECT tbl_tdocs.obs
	                   FROM tbl_tdocs
	                   WHERE referencia_id = '{$extrato}'
	                   AND situacao = 'ativo'
	                   AND fabrica = {$login_fabrica}";
	$resValidaAnexos = pg_query($con, $sqlValidaAnexos);

	while ($dadosTdocs = pg_fetch_object($resValidaAnexos)) {

	    $arrObs = json_decode($dadosTdocs->obs, true);

	    $anexosInseridos[] = $arrObs[0]["typeId"];

	}*/

	if (!anexoExtratoEnviadoBosch($extrato)) { ?>
		<br />
		<div style="color: red;background-color: #dea4a0;width: 700px;height: auto;">
			<strong>Atenção: Favor, anexar a Nota Fiscal de Mão-de-obra e Foto das caixas no extrato</strong>
		</div>
	<?php
	}

?>

<?php
}
echo "<table>";
echo "<tr>\n";
echo "<td align=\"center\">";
echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";
?>


<?php if (!empty($_GET['msg'])) : ?>

	<div class="sucesso"><?=$_GET['msg']?></div>

<?php endif; ?>

<?

if(strlen($msg_erro)>0){
	echo "<div style='background-color: #eba99d;height: 100px;width: 80%;'><br><b><font color = 'red'>$msg_erro</font></b><br><br></div>";
}

$extrato = $_GET['extrato'];
$op      = $_GET['op'];

if($extrato){
	$sql = "SELECT posto_interno
			FROM tbl_tipo_posto
			JOIN tbl_posto_fabrica ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			WHERE tbl_posto_fabrica.posto = $login_posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	$posto_interno = pg_fetch_result($res, 0, 'posto_interno');

	if($posto_interno != 't'){
		$cond = "AND tbl_extrato.aprovado  IS NULL
				 AND tbl_extrato.liberado  IS NULL";
	}

	$sql = "SELECT DISTINCT to_char (data_geracao,'DD/MM/YYYY')          AS data        ,
				tbl_extrato_extra.lote_extrato                              ,
				tbl_extrato.extrato                                         ,
				tbl_extrato.mao_de_obra                                     ,
				tbl_extrato.pecas                                           ,
				tbl_extrato.avulso                                          ,
				tbl_extrato.posto                                           ,
				tbl_extrato.fabrica                                         ,
				tbl_extrato.total                                           ,
				tbl_extrato_extra.nota_fiscal_mao_de_obra    AS nf_mo       ,
				tbl_extrato_extra.nota_fiscal_devolucao      AS nf_devolucao,
				tbl_posto.nome                               AS nome_posto  ,
				tbl_posto_fabrica.codigo_posto                              ,
				( 	 SELECT count(os)
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					WHERE tbl_os_extra.extrato = tbl_extrato.extrato
				)AS total_os
		FROM tbl_extrato
		JOIN tbl_posto            USING(posto)
		JOIN tbl_os_extra         USING(extrato)
		JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $login_posto
		JOIN tbl_extrato_extra    ON tbl_extrato_extra.extrato = tbl_extrato.extrato
		WHERE tbl_extrato.extrato = $extrato
		AND tbl_extrato.posto     = $login_posto
		AND tbl_extrato.fabrica   = $login_fabrica
		$cond";

	$res = pg_exec($con,$sql);

	$extrato            = trim(pg_result ($res,0,extrato))      ;
	$data_geracao       = trim(pg_result ($res,0,data))         ;
	$mao_de_obra        = trim(pg_result ($res,0,mao_de_obra))  ;
	$pecas              = trim(pg_result ($res,0,pecas))        ;
	$avulso             = trim(pg_result ($res,0,avulso))       ;
	$total              = trim(pg_result ($res,0,total))        ;
	$posto              = trim(pg_result ($res,0,posto))        ;
	$fabrica            = trim(pg_result ($res,0,fabrica))      ;
	$total_os           = trim(pg_result ($res,0,total_os))     ;
	$nome_posto         = trim(pg_result ($res,0,nome_posto))   ;
	$codigo_posto       = trim(pg_result ($res,0,codigo_posto)) ;
	$nf_mo              = trim(pg_result ($res,0,nf_mo))        ;
	$nf_devolucao       = trim(pg_result ($res,0,nf_devolucao)) ;
	$lote_extrato       = trim(pg_result ($res,0,lote_extrato)) ;

	$mao_de_obra = number_format($mao_de_obra,2,",",".");
	$pecas       = number_format($pecas,2,",",".")      ;
	$total       = number_format($total,2,",",".")      ;
	$avulso      = number_format($avulso,2,",",".")     ;

	echo "<table border='1' width ='700'align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
	echo "<tr class='Principal' background='admin/imagens_admin/azul.gif' height='25'><td colspan='4'><font size='2'>";
	echo ($sistema_lingua == 'ES') ? "EXTRACTO N° " : "EXTRATO N° ";
	echo "$extrato</FONT></td></tr>";
	echo "<tr class='Conteudo'>";
	echo "<td bgcolor='#F1F4FA' ><b>";
	echo ($sistema_lingua == 'ES') ? "SERVICIO" : "POSTO";
	echo "</b></td>";
	echo "<td width='150'> $codigo_posto - $nome_posto</td>";
	echo "<td bgcolor='#F1F4FA' ><b>";
	echo ($sistema_lingua == 'ES') ? "TOTAL DE MANO DE OBRA" : "TOTAL DE MÃO DE OBRA";
	echo "</b></td>";
	echo "<td align='right'> $mao_de_obra</td>";
	echo "</tr>";
	echo "<tr class='Conteudo'>";
	echo "<td bgcolor='#F1F4FA'><b>";
	echo ($sistema_lingua == 'ES') ? "CIERRA GERACIÓN" : "DATA GERAÇÃO";
	echo "</b></td>";
	echo "<td > $data_geracao</td>";
	echo "<td bgcolor='#F1F4FA'><b>";
	echo ($sistema_lingua == 'ES') ? "TOTAL DE PIEZAS" : "TOTAL DE PEÇAS";
	echo "</b></td>";
	echo "<td align='right'> $pecas</td>";
	echo "</tr>";
	echo "<tr class='Conteudo'>";
	echo "<td bgcolor='#F1F4FA'><b>";
	echo ($sistema_lingua == 'ES') ? "CTD OS" : "QTDE OS";
	echo "</b></td>";
	echo "<td > $total_os</td>";
	echo "<td bgcolor='#F1F4FA'><b>";
	echo ($sistema_lingua == 'ES') ? "TOTAL SUELTO" : "TOTAL AVULSO";
	echo "</b></td>";
	echo "<td align='right'> $avulso</td>";
	echo "</tr>";
	if (in_array($login_fabrica, [20]) && $login_pais == "BR") {
		echo "<tr class='Conteudo'>";
		echo "<td bgcolor='#F1F4FA'><b>";
		echo traduz("QTDE. CAIXAS");
		echo "</b></td>";
		echo "<td align='left' width='150'><b>{$lote_extrato}</b></td>";
		echo "<td colspan='2' bgcolor='#F1F4FA'></td>";
		echo "</tr>";
	}
	echo "<tr class='Conteudo'>";
	echo "<td colspan='3' bgcolor='#F1F4FA'><b>";
	echo ($sistema_lingua == "ES") ? "TOTAL GENERAL" : "TOTAL GERAL";
	echo "</b></td>";
	echo "<td align='right' width='150'><b> $total</b></td>";
	echo "</tr>";
	echo "</table>";

	if ($login_fabrica == 1) {
		$cond_peca_devolucao = " AND (tbl_os.tipo_atendimento IS NULL OR tbl_os.tipo_atendimento <> 334) ";
	}

	$sql = "SELECT  tbl_os.posto                                             ,
			tbl_os.sua_os                                                    ,
			tbl_os.os                                                        ,
			tbl_os.mao_de_obra                                               ,
			tbl_os.consumidor_revenda                                        ,
			tbl_os.pecas                                                     ,
			tbl_os.consumidor_nome                                           ,
			tbl_os.consumidor_fone                                           ,
			to_char (tbl_os.data_abertura ,'DD/MM/YY')      AS abertura      ,
			tbl_os.data_fechamento                                           ,
			tbl_os.tipo_atendimento                                          ,
			tbl_os_extra.os_reincidente                                      ,
			tbl_produto.produto                                              ,
			tbl_produto.referencia                                           ,
			tbl_produto.descricao
		FROM        tbl_os
		JOIN        tbl_os_extra      ON tbl_os_extra.os     = tbl_os.os AND tbl_os_extra.i_fabrica = tbl_os.fabrica
		JOIN        tbl_produto       ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
		JOIN        tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato
		WHERE       tbl_extrato.extrato  = $extrato
		AND         tbl_os.posto         = $posto
		AND         tbl_os.fabrica       = $fabrica
		AND         tbl_os.excluida      IS NOT TRUE
		AND         tbl_extrato.aprovado IS NULL
		$cond_peca_devolucao
		ORDER BY   lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-'))::text,20,'0')               ASC,
		replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0')::text,'-','')            ASC";
	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);

	if ($totalRegistros > 0){
		echo  "<table width='700' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";

		echo "<tr class='Principal' align='center' background='admin/imagens_admin/azul.gif'>";
		echo "<td>OS</td>";
		echo "<td>ABERTURA</td>";
		echo "<td >";
		echo ($sistema_lingua == 'ES') ? "PRODUCTO" : "PRODUTO";
		echo "</td>";
		echo "<td width='70'>";
		echo ($sistema_lingua == 'ES') ? "PIEZAS" : "PEÇAS";
		echo "</td>";
		echo "<td width='70'>";
		echo ($sistema_lingua == 'ES') ? "MANO DE OBRA" : "MÃO DE OBRA";
		echo "</td>";
		if($t_pais<>'BR'){
			echo "<td width='70'>";
			echo ($sistema_lingua == 'ES') ? "IMPUESTO IVA" : "IMPOSTO IVA";
			echo "</td>";
		}
		echo "<td width='70'>total</td>";
		echo "</tr>";

		for ($i = 0 ; $i < $totalRegistros; $i++){
			$os                 = trim(pg_result ($res,$i,os));
			$sua_os             = trim(pg_result ($res,$i,sua_os));
			$abertura           = trim(pg_result ($res,$i,abertura));
			$sua_os             = trim(pg_result ($res,$i,sua_os));
			$tipo_atendimento   = trim(pg_result ($res,$i,tipo_atendimento));
			$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
			$consumidor_fone    = trim(pg_result ($res,$i,consumidor_fone));
			$produto            = trim(pg_result ($res,$i,produto));
			$produto_nome       = trim(pg_result ($res,$i,descricao));
			$produto_referencia = trim(pg_result ($res,$i,referencia));
			$os_reincidente     = trim(pg_result ($res,$i,os_reincidente));
			$total_pecas        = trim(pg_result ($res,$i,pecas));
			$total_mo           = trim(pg_result ($res,$i,mao_de_obra));

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$produto_nome  = trim(@pg_result($res_idioma,0,descricao));
			}

			$cor = ($i % 2) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td nowrap height='30'>$sua_os</td>\n";
			echo "<td align='center'>$abertura</td>\n";
			echo "<td nowrap><acronym title='$produto_referencia - $produto_nome'>";
			echo $produto_referencia. ' - ';
			echo substr($produto_nome,0,17);
			echo "</acronym></td>\n";

			$sql2 = "SELECT imposto_al
					FROM tbl_posto_fabrica
					WHERE posto = $login_posto
					AND fabrica = $login_fabrica";
			$res2 = pg_exec ($con,$sql2);

			if (pg_numrows ($res2) == 1) {
				$imposto_al   = pg_result ($res2,0,imposto_al);
				$imposto_al   = $imposto_al / 100;
				$acrescimo     = ($total_pecas + $total_mo) * $imposto_al;
			}
			$total_os = $total_pecas + $total_mo + $acrescimo;

			echo "<td align='right' nowrap> " . number_format($total_pecas,2,",",".") . "</td>\n";
			echo "<td align='right' nowrap> " . number_format($total_mo,2,",",".")    . "</td>\n";
			if($t_pais<>'BR') echo "<td align='right' nowrap> " . number_format($acrescimo,2,",",".")    . "</td>\n";
			echo "<td align='right' nowrap> " . number_format($total_os,2,",",".")    . "</td>\n";
			echo "</tr>";
		}
	echo "</table>";

	echo "<form method='post' name='frm_extrato_os' onsubmit='return confirm(\" ";
	echo ($sistema_lingua == "ES") ? "DESEA REALMENTE GUARDAR EL NÚMERO DE FACTURA COMERCIAL PARA EL LOTE? " : "DESEJA REALMENTE GRAVAR O NÚMERO DA NOTA FISCAL PARA O LOTE?";
	echo " $extrato?\")'>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo  "<table width='700' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
	echo "<tr class='Principal' align='center' background='admin/imagens_admin/azul.gif'>";
	echo "<td colspan='5'>";
	echo ($sistema_lingua == 'ES') ? "Facturas de devolución" : "Notas de Devolucao";
	echo "</TD>";
	echo "</tr>";

	echo "<tr class='Conteudo'>";
	echo "<td bgcolor='#F1F4FA' width='130'><b>";
	echo ($sistema_lingua == 'ES') ? "FACTURA DE MANO DE OBRA" : "NF DE MÃO DE OBRA";
	echo "</b></td>";
	echo "<td ><input name='nf_mo' type='text' maxlength='16' value = '$nf_mo' ";
	if(strlen($nf_mo)>0) echo " READONLY";
	echo "></td>";
	echo "<td bgcolor='#F1F4FA' width='100'><b>";
	echo ($sistema_lingua == 'ES') ? "FACTURA REMESA" : "NF REMESSA";
	echo "</b></td>";
	echo "<td align='LEFT'><input name='nf_devolucao' type='text' maxlength='16' value='$nf_devolucao' ";
	if(strlen($nf_devolucao)>0) echo " READONLY";
	echo ">";
	echo "</td>";
	echo "<td align='LEFT'>";
	if( strlen($nf_mo) == 0 and strlen($nf_devolucao) == 0){
		echo "<input name='gravar_nf' type='submit' value='";
		echo ($sistema_lingua == 'ES') ? "Grabar" : "Gravar";
		echo "'>";
	}else{
		echo "<font color='#990000'>";
		echo ($sistema_lingua == "ES") ? "No pueden ser cambiados" : "Não podem ser  alterados";
		echo "</font>";
	}
	echo "</td>";
	echo "</tr>";
	?>
	<tr>
		<td colspan="100%" align="center" style="padding: 20px;">
			<?php
			if ($login_pais == "BR" && in_array($login_fabrica, [20]) && $extrato > 4289724) { ?>
				<?php
				if (empty($lote_extrato)) {
				?>
					<strong style="color: red;">Informar a quantidade de caixas referente ao extrato</strong>
				<?php
				}
				?>
				<br />
				&nbsp;&nbsp;Qtde. de Caixas:
				<input style='width: 75px;' type='text' id="qtde_caixas" value='<?= $lote_extrato ?>' name='qtde_caixas' />
				<button style="cursor: pointer;" type="button" id="alterar_caixas" data-extrato="<?= $extrato ?>">Salvar</button>
			<?php
			} ?>
		</td>
	</tr>
	<?php
	echo "</table>";
	echo "</form>";
	echo "<br><img src='imagens/btn_imprimir.gif' onclick=\"javascript: janela=window.open('os_extrato_detalhe_print.php?extrato=$extrato','extrato');\" ALT='Imprimir' border='0' style='cursor:pointer;'>";
	}

		if (in_array($login_fabrica, [20]) && $login_pais == "BR" && $extrato > 4289724) {

		    $anexo_append = "<strong>".traduz("Anexar a Nota Fiscal de Mão de Obra” e “Anexar fotos da(s) caixa(s) referente ao extrato contendo as peças/máquinas trocadas, para que possa ser avaliado o processo de segregação e identificação dos itens")."</strong>";

		    $boxUploader = array(
		        "titulo_tabela" => traduz("Anexar Nota Fiscal de Serviço"),
		        "div_id" => "div_anexos",
		        "append" => $anexo_append,
		        "context" => "extrato",
		        "unique_id" => $tempUniqueId,
		        "hash_temp" => $anexoNoHash,
		        "bootstrap" => false,
		        "hidden_button" => $hidden_button
		    );

		    include "box_uploader.php";
?>
				<button type="button" class="btn btn-success" name="concluir_precesso" id="concluir_precesso">Concluir Processo</button>
<?php 
		}

}else{
	if($login_fabrica == 1){
		$cond_baixado = " AND tbl_extrato_extra.baixado IS NULL ";
		$order = " ORDER BY tbl_extrato.extrato DESC LIMIT 1 ";
	}

	if($login_fabrica == 20){
		$data_atual = date("Y-m-d"); 
		$data_12    = date('Y-m-d', strtotime("-12 month",strtotime($data_atual)));

		$condData = " and tbl_extrato.data_geracao between '$data_12 00:00:00' and  '$data_atual 23:59:59' ";
	}

	$sql = "SELECT DISTINCT to_char (data_geracao,'DD/MM/YYYY')  AS data  ,
				tbl_extrato.extrato                                       ,
				tbl_extrato.protocolo                                     ,
				tbl_extrato.mao_de_obra                                   ,
				tbl_extrato.pecas                                         ,
				tbl_extrato.avulso                                        ,
				tbl_extrato.posto                                         ,
				tbl_extrato.fabrica                                       ,
				tbl_extrato.total                                         ,
				tbl_posto.nome                          AS nome_posto     ,
				tbl_posto_fabrica.codigo_posto                            ,
				( 	 SELECT count(os)
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					WHERE tbl_os_extra.extrato = tbl_extrato.extrato
				)AS total_os                                              ,
				tbl_extrato_extra.nota_fiscal_devolucao                   ,
				tbl_extrato_extra.nota_fiscal_mao_de_obra                 ,
				tbl_extrato_extra.data_coleta
		FROM tbl_extrato
		JOIN tbl_posto            USING(posto)
		JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $login_posto
		JOIN tbl_os_extra USING(extrato)
		JOIN tbl_extrato_extra on tbl_extrato.extrato = tbl_extrato_extra.extrato $cond_baixado
		WHERE tbl_extrato.posto     = $login_posto
		AND   tbl_extrato.fabrica   = $login_fabrica
		AND   tbl_extrato.aprovado  IS NULL
		AND   tbl_extrato.liberado  IS NULL
		$condData
		$order";

		if(isset($_POST['buscarOS'])){
            if($_POST['buscarOS'] != ""){
                $sql .= " AND tbl_os_extra.os = ".$_POST['buscarOS']." ";
            }
        }

//echo nl2br($sql);
	$res = pg_exec ($con,$sql);

	$totalRegistros =  pg_numrows($res);

	if ($totalRegistros > 0){
		echo  "<table width='700' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc' class='tabela_extratos'>";

		echo "<tr class='Principal' align='center' background='admin/imagens_admin/azul.gif' height='25'>";

 		echo "<td colspan='100%'>";
		echo ($sistema_lingua == 'ES') ? "EXTRACTO ENIADO A LA PLANTA PARA ANÁLISIS" : "EXTRATOS ENVIADOS A FÁBRICA PARA ANÁLISE";
		echo "</td>";

		echo "</tr>";

		echo "<tr class='Principal' align='center'>";

		echo "<td>";
		echo ($sistema_lingua == 'ES') ? "EXTRACTO" : "EXTRATO";
		echo "</td>";
		echo "<td>";
		echo ($sistema_lingua == 'ES') ? "GERECIÓN" : "GERAÇÃO";
		echo "</td>";

		if($login_fabrica != 1){
			echo "<td title = 'Valor total de Mão de Obra do extrato'>";
			echo "MO";
			echo "</td>";
			echo "<td title = 'Valor total de Peças do extrato'>";
			echo ($sistema_lingua == 'ES') ? "PIEZAS" : "PEÇAS";
			echo "</td>";
			echo "<td title = 'Valor total de pagamentos Avulsos do extrato'>";
			echo ($sistema_lingua == 'ES') ? "SUELTO" : "AVULSO";
			echo "</td>";
			echo "<td>TOTAL</td>";
			echo "<td width='15%'>";
			echo ($sistema_lingua == 'ES') ? "Factura de mano de obra" : "N.F. M. DE OBRA";
			echo "</td>";
			echo "<td width='15%'>";
			echo ($sistema_lingua == 'ES') ? "Factura de remesa" : "N.F. REMESSA";
			echo "</td>";
			echo "<td width='15%'>";
			echo ($sistema_lingua == 'ES') ? "Fecha de recogida" : "DATA COLETA";
			echo "</td>";

			echo "<td colspan='2'>";
			echo ($sistema_lingua == 'ES') ? "ACCIONES" : "AÇÕES";
			echo "</td>";

			if ($login_pais == "BR" && in_array($login_fabrica, [20])) {

				$oneExtrato = trim(pg_result ($res,0,'extrato'));

				if ($oneExtrato > 4289724) {
					echo "<td>Status NFe</td>";
				}
			}

		}else{
			echo "<td>";
			echo "STATUS";
			echo "</td>";
		}

		echo "</tr>";

		for ($i = 0 ; $i < $totalRegistros; $i++){

			$extrato                 = trim(pg_result ($res,$i,extrato))      ;
			$protocolo               = trim(pg_result ($res,$i,protocolo))    ;
			$data_geracao            = trim(pg_result ($res,$i,data))         ;
			$mao_de_obra             = trim(pg_result ($res,$i,mao_de_obra))  ;
			$pecas                   = trim(pg_result ($res,$i,pecas))        ;
			$avulso                  = trim(pg_result ($res,$i,avulso))       ;
			$total                   = trim(pg_result ($res,$i,total))                   ;
			$posto                   = trim(pg_result ($res,$i,posto))                   ;
			$fabrica                 = trim(pg_result ($res,$i,fabrica))                 ;
			$total_os                = trim(pg_result ($res,$i,total_os))                ;
			$nome_posto              = trim(pg_result ($res,$i,nome_posto))              ;
			$codigo_posto            = trim(pg_result ($res,$i,codigo_posto))            ;
			$nota_fiscal_devolucao   = trim(pg_result ($res,$i,nota_fiscal_devolucao))   ;
			$nota_fiscal_mao_de_obra = trim(pg_result ($res,$i,nota_fiscal_mao_de_obra)) ;
			$data_coleta             = mostra_data(trim(pg_result ($res,$i,data_coleta)));

			$mao_de_obra = number_format($mao_de_obra,2,",",".");
			$pecas       = number_format($pecas,2,",",".")      ;
			$total       = number_format($total,2,",",".")      ;
			$avulso      = number_format($avulso,2,",",".")     ;

			$extrato_exibe = ($login_fabrica == 1) ? $protocolo : $extrato;
			$cor = ($i % 2) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			if(empty($extrato_pendente)){
				echo "<td align='center'><a href='$PHP_SELF?extrato=$extrato'>$extrato_exibe</a></td>";
			}else{
				echo "<td align='center'>$extrato_exibe</td>";
			}
			echo "<td align='center'>$data_geracao</td>";
			#HD 219942 #DH 248108
			if($login_fabrica != 1){
					echo "<td align='right'> $mao_de_obra</td>";
					echo "<td align='right'> $pecas</td>";
					echo "<td align='right'> $avulso</td>";
					echo "<td align='right'> $total</td>";
					echo "<td align='right'> $nota_fiscal_mao_de_obra</td>";
					echo "<td align='right'> $nota_fiscal_devolucao</td>";
				#if(strlen($data_coleta)>0){
				#	echo "<td align='center'>$data_coleta</td>";
				#}else{
					echo "<td><INPUT TYPE='text' NAME='data_coleta' rel='data_coleta' size='11' maxlength='11' value='$data_coleta' onblur='gravaDatacoleta(this.value,$extrato,$i)'></td>";
				#}

					if (in_array($login_fabrica, [20]) && $login_pais == "BR" && $extrato > 4289724) { ?>
						<td>
					    	<a href="<?= $PHP_SELF."?extrato={$extrato}" ?>" class="anexar_nfe">Visualizar</a>
					    	<br />
					    </td>
					<?php
					}

					echo "<td align='center' width='70'><a class='imprimir' href='os_extrato_detalhe_print.php?extrato=$extrato' target='_blank'>Imprimir</a><br /></td>";

					if (in_array($login_fabrica, [20]) && $login_pais == "BR" && $extrato > 4289724) {

							/*$sqlValidaAnexos = "SELECT tbl_tdocs.obs
							                   FROM tbl_tdocs
							                   WHERE referencia_id = '{$extrato}'
							                   AND situacao = 'ativo'
							                   AND fabrica = {$login_fabrica}";
							$resValidaAnexos = pg_query($con, $sqlValidaAnexos);

							$anexosInseridos = [];
							while ($dadosTdocs = pg_fetch_object($resValidaAnexos)) {

							    $arrObs = json_decode($dadosTdocs->obs, true);

							    $anexosInseridos[] = $arrObs[0]["typeId"];

							}*/

							if (anexoExtratoEnviadoBosch($extrato)) { 

								$statusNf = "<span style='color: darkgreen;font-weight: bolder;'>Anexado</span>";
								

							} else {

								$statusNf = "<span style='color: red;font-weight: bolder;'>Pendente</span>";

							}

							echo "<td>{$statusNf}</td>";

					}


			}else{
				echo "<td align='center'>Aguardando aprovação</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}

	// HD 132513
	$cond_1 = ($login_pais =='CO') ? " tipo_atendimento = 13" : " 1=2 ";

	if ($login_fabrica == 1) {
		$cond_1 = " (tbl_produto.mao_de_obra+tbl_os.pecas) > 0 ";
        $cond2 = " AND extrato_geracao IS NULL ";
        $cond_recusada = '';
        $cond_peca_devolucao = " AND (tbl_os.tipo_atendimento IS NULL OR tbl_os.tipo_atendimento <> 334) ";

    } else {
        $cond_recusada  = 'AND     coalesce((select status_os from tbl_os_status where tbl_os_status.fabrica_status=tbl_os.fabrica AND tbl_os_status.os = tbl_os.os order by os_status desc limit 1),0)<>13';
    }

	echo "<form method='post' name='frm_extrato_os' action=$PHP_SELF>";
	$sql = "/* Programa: $PHP_SELF  Posto: $login_posto - Fabrica: $login_fabrica */
			SELECT  lpad (tbl_os.sua_os::text,10,'0')                  AS ordem             ,
			tbl_os.os                                                                       ,
			tbl_os.sua_os                                                                   ,
			to_char (tbl_os.data_abertura ,'DD/MM/YY')                 AS abertura          ,
			tbl_os.consumidor_nome                                                          ,
			tbl_os.consumidor_fone                                                          ,
			tbl_os.pecas                                               AS total_pecas       ,
			tbl_os.mao_de_obra                                         AS total_mo          ,
			tbl_os.tipo_atendimento                                                         ,
			tbl_produto.produto                                                             ,
			tbl_produto.referencia                                                          ,
			tbl_produto.descricao
			FROM        tbl_os
			LEFT JOIN tbl_os_extra          ON  tbl_os_extra.os     = tbl_os.os AND tbl_os_extra.i_fabrica=tbl_os.fabrica
			JOIN      tbl_produto           ON  tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
			WHERE	tbl_os.fabrica = $login_fabrica
			AND     tbl_os.posto   = $login_posto
			AND     tbl_os_extra.extrato IS NULL
			AND     tbl_os.finalizada    IS NOT NULL
			AND     tbl_os.excluida      is not TRUE
            $cond_recusada
			AND     coalesce((select status_os from tbl_os_status where tbl_os_status.fabrica_status=tbl_os.fabrica AND tbl_os_status.os = tbl_os.os and tbl_os_status.status_os IN (92,93,94) order by os_status desc limit 1),0) NOT IN (92,94)
			AND     ((tbl_os.mao_de_obra + tbl_os.pecas)>0 or $cond_1 )
			$cond2
			$cond_peca_devolucao
			ORDER BY lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-'))::text,20,'0')               ASC,
				replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-'))::text,20,'0'),'-','') ASC";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){

		echo  "<br><table width='700' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
		if($login_pais<>'CO'){
			echo "<thead>";
			echo "<tr>";
			echo "<td>";

			if (empty($valor_minimo)){
				if($login_fabrica == 20){
					echo " <font color='#ff0000'> ATENÇÃO! OS's que tiverem um período de abertura maior do que 6 meses não
						   serão pagas conforme orientações da <a href='http://api2.telecontrol.com.br/tdocs/document/id/63590b8cc7b53dc10f10c34da032b32f44e675e2a442980b39f6ccf7a2c3cbc7' target='_blank'><b>CI 07/2016</b></a>.Após a geração do extrato, as peças deverão ser armazenadas e devidamente identificadas pelas OS's na Assistência por um período de 3 meses para auditoria a contar da data de aprovação do extrato na Bosch.</font>";
				}else{
					echo "ATENÇÃO:<br>Todas as OSs posteriores a 90 dias poderão ser selecionadas para fechar extrato, mas as OSs com menos de 90 dias serão selecionadas automáticamente!";
				}
			}else {
				echo "ATENÇÃO:<br>Somente poderão ser fechados lotes que tenham o valor mínimo de R$ ".number_format($valor_minimo, 2, ',', '.').".";

			}
			echo "</td>";
			echo "</tr>";
			echo "</thead>";
		}
		echo "<tr class='Conteudo2' align='center'>";
		echo "<td>";

			echo "<table width='100%'>";
			echo "<tr>";
			echo "<td><img src='imagens/botoes/extrato.jpg' align='absmiddle'></td>";
			echo "<td class='Conteudo'><b>";
			echo ($sistema_lingua == 'ES') ? "Seleccione las OS's que deseas crear un lote<br>Usted puede incluir solo OS's que ya estan cerradas" : "Selecione as OS's que você deseja criar um Lote<br>Você pode apenas incluir OS's que já estão fechadas";
			echo "</b></td>";
			if ($login_fabrica != 1) {
				echo "<td>";

					echo "<table align='center' class='Conteudo'><tr><td ><img src='admin/imagens_admin/status_vermelho.gif'></td><td align='left'>";
					echo ($sistema_lingua == 'ES') ? "OS con valor de mano de obra o piezas zero" : "OS com valor de mão de obra ou peças zero";
					echo "</td></tr>";

					echo "<tr><td><img src='admin/imagens_admin/status_amarelo.gif'></td><td align='left'>";
					echo ($sistema_lingua == 'ES') ? "OS diferenciadas" : "OS diferenciais";
					echo "</td></tr>";

					echo "<tr><td><img src='admin/imagens_admin/status_verde.gif'></td><td align='left'>";
					echo ($sistema_lingua == 'ES') ? "OS sin problema " : "OS sem nenhum problema";
					echo "</td></tr></table>";

				echo "</td>";
			}
			echo "</tr>";
			echo "</table>";

		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td>";

		echo "<table width='680' border='1' align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";

		echo "<tr class='Principal' align='center' background='admin/imagens_admin/azul.gif'>";

		echo "<td><input type='checkbox' class='frm' name='marcar' id='ativo' value='tudo' title='Selecione ou desmarque todos' onClick='SelecionaTodos(this);' style='cursor: hand;'> </td>";
		if ($login_fabrica != 1)
			echo "<td>VER</td>";
		echo "<td>OS</td>";
		echo "<td>Abertura</td>";
		echo "<td>";
		echo ($sistema_lingua == 'ES') ? "Producto" : "Produto";
		echo "</td>";
		echo "<td width='70'>";
		echo ($sistema_lingua == 'ES') ? "Piezas" : "Peças";
		echo "</td>";
		echo "<td width='70'>";
		echo ($sistema_lingua == 'ES') ? "Mano de Obra" : "Mão de Obra";
		echo "</td>";
		if($t_pais<>'BR'){
			echo "<td width='70'>";
			echo ($sistema_lingua == 'ES') ? "Impuesto IVA" : "Imposto IVA";
			echo "</td>";
		}
		echo "<td width='70'>Total</td>";
		if ($login_fabrica != 1)
			echo "<td > </td>";

		echo "</tr>";

		$os_total = pg_numrows ($res);
		$disab_botao = 't';

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$os                 = trim(pg_result ($res,$i,os))              ;
			$sua_os             = trim(pg_result ($res,$i,sua_os))          ;
			if ( $login_fabrica == 1 )
				$sua_os = $login_codigo_posto . $sua_os;
			$abertura           = trim(pg_result ($res,$i,abertura))        ;
			$tipo_atendimento   = trim(pg_result ($res,$i,tipo_atendimento));
			$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome)) ;
			$consumidor_fone    = trim(pg_result ($res,$i,consumidor_fone)) ;
			$produto            = trim(pg_result ($res,$i,produto))         ;
			$produto_nome       = trim(pg_result ($res,$i,descricao))       ;
			$produto_referencia = trim(pg_result ($res,$i,referencia))      ;
			$total_pecas        = trim(pg_result ($res,$i,total_pecas))     ;
			$total_mo           = trim(pg_result ($res,$i,total_mo))        ;

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			if($login_fabrica == 1) {
				$sql2 = "SELECT pedido FROM tbl_os_item JOIN tbl_os_produto using(os_produto) where os = $os and pedido notnull";
				$res2 = pg_query($con,$sql2);

				if(pg_num_rows($res2) > 0) {
					$total_pecas = 0 ;
				}
			}
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$produto_nome  = trim(@pg_result($res_idioma,0,descricao));
			}

			
			if ($login_fabrica == 20) {
				$total_dias = "'180 days'";
			}
			else {
				$total_dias = "'90 days'";
			}

			// HD 61323
			$sqlx = "SELECT os
					FROM tbl_os
					WHERE tbl_os.os = $os
					AND   CURRENT_DATE - tbl_os.data_digitacao >  INTERVAL $total_dias";
			$resx = pg_exec($con,$sqlx);

			if (in_array($login_fabrica, [20]) && pg_num_rows($resx) > 0) {
				continue;
			}

			$selecionado = "";
			if(pg_numrows($resx) > 0 and $login_pais<>'CO'){
				$disab_botao = 'f';
				
				if ($login_fabrica == 20 ) {
					$selecionado = " disabled='true' onclick='return false' ";
				}
				else {
					$selecionado = $login_fabrica == 1 ? 'id="ativo"' : "checked onclick='return false' ";	
				}

			}else{
				$selecionado = " id='ativo' ";
			}

			$cor = ($i % 2) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr class='Conteudo' bgcolor='$cor'>";

			if ($login_fabrica == 1) {
				$total_mo_os = $total_mo + $total_pecas;
			} else {
				$total_mo_os = $total_mo;
			}

			$checked = "";
			if (in_array($os, $ativo_arr)) {

				$checked = "checked";

			}

			echo "<td nowrap height='30' align='center'>
			<input type='checkbox' {$checked} name='ativo[]' class='check_os' value='$os' $selecionado>
			<input type='hidden' name='os[]' value='$os' />
			<input type='hidden' name='total_mo_os' class='mo_os' value='$total_mo_os' />
			</td>";
			if ($login_fabrica != 1) {
				echo "<td nowrap height='30' align='center'>";
				echo " <a href=\"javascript:MostraEsconde('dados_$i','$os','visualizar_$i','$cor');\">VER</a>";
				echo "<img src='imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','$os','visualizar_$i','$cor');\" align='absmiddle'>";
				echo "</td>";
			}

			echo "<td nowrap height='30'>$sua_os</td>\n";
			echo "<td align='center'>$abertura</td>\n";
			echo "<td nowrap><acronym title='$produto_referencia - $produto_nome'>";
			echo $produto_referencia. ' - ';
			echo substr($produto_nome,0,17);
			echo "</acronym></td>\n";

			$sql2 = "SELECT imposto_al
					FROM tbl_posto_fabrica
					WHERE posto = $login_posto
					AND fabrica = $login_fabrica";
			$res2 = pg_exec ($con,$sql2);

			if (pg_numrows ($res2) == 1) {
				$imposto_al   = pg_result ($res2,0,imposto_al);
				$imposto_al   = $imposto_al / 100;
				$acrescimo     = ($total_pecas + $total_mo) * $imposto_al;
			}
			$total_os = $total_pecas + $total_mo + $acrescimo;

			echo "<td align='right' nowrap> " . number_format($total_pecas,2,",",".") . "</td>\n";
			echo "<td align='right' class='coluna-mo' id='{$os}_mo' data-mo='{$total_mo}' nowrap> " . number_format($total_mo,2,",",".")    . "</td>\n";
			if($t_pais<>'BR')echo "<td align='right' nowrap> " . number_format($acrescimo,2,",",".")    . "</td>\n";
			echo "<td align='right' nowrap> " . number_format($total_os,2,",",".")    . "</td>\n";
			if ($login_fabrica != 1) {
				echo "<td align='center' nowrap width='30'>";
				//SE a peça estiver zerada ou for troca de produto
				if($total_pecas=='0' OR $total_pecas==NULL OR $tipo_atendimento==13){
					echo "<img src='admin/imagens_admin/status_vermelho.gif'>";
				}else{
					//SE TIVER UMA OS COM O MESMO NUMERO DE S?IE COM INTERVALO DE 90 DIAS OU FOR GARANTIA DE CONSERTO
					if ($tipo_atendimento  == 14 OR $tipo_atendimento  == 15 OR $tipo_atendimento  == 16 ){
						echo "<img src='admin/imagens_admin/status_amarelo.gif'>";
					}else{
						//DEMAIS CASOS
						echo "<img src='admin/imagens_admin/status_verde.gif'>";
					}
				}
				echo "</td>\n";
			}
			echo "</tr>";

			echo "<tr heigth='1' class='Conteudo'><td colspan='";
			echo ($t_pais<>'BR') ? "10" : "9";
			echo"'>";
			echo "<div class='exibe' id='dados_$i' value='1' align='center'>";
			echo "<b>Carregando...</b><br><img src='imagens/carregar_os.gif'>";
			echo "</div>";
			echo "</td></tr>";
		}
	echo "</table>";

	echo "<input type='hidden' name='os_total' value='$os_total'>";
	echo "<input type='hidden' name='btn_acao' value='gravar'>";


	if (in_array($login_fabrica, [20])) { 

		if (!empty($_POST['total_mo_extrato'])) {

			$total_mo_extrato = $_POST['total_mo_extrato'];

		} else {

			$total_mo_extrato = 0;

		}

		?>
		<tr>
			<td colspan="100%" align="center">
				<strong>Total M.O: </strong> R$ <span id='total_mo'><?= $total_mo_extrato ?></span>
				<input type="hidden" name="total_mo_extrato" id="total_mo_extrato" value="<?= $total_mo_extrato ?>" />
			</td>
		</tr>
	<?php
	}

	if (!in_array($login_fabrica, [20])) {
		if (!empty($valor_minimo))
			echo "<tr><td align='center'><span style='float:left;'>Total: R$&nbsp;</span><span id='total_lote' style='float:left;'></span><input type='button' name='btn_acao2' id='btn_acao' value=";
		else
			echo "<tr><td align='center'><input type='button' name='btn_acao2' id='btn_acao' value=";
		echo ($sistema_lingua == 'ES') ? "'Crear lote'" : "'Criar Lote'";

			echo " onClick=\"javascript:
			if (this.value=='Aguarde...'){
				alert('Aguarde');
			}else {
				this.value='Aguarde...';
				if (confirm('";
				echo ($sistema_lingua=='ES') ? "Desea crear el nuevo extracto/lote con las OSs seleccionadas?" : "Deseja criar um novo Extrato/Lote com as OSs selecionadas?";
				echo "') == true) {
					document.frm_extrato_os.submit();
				}else{
					this.value='Gravar';
				}
			}\"";

		if ($login_fabrica == 1) {
			echo " disabled='disabled' ";
		}


		echo " /></td></tr>";
	}

	echo "</table><br /><br />";

	if (in_array($login_fabrica, [20])) { ?>
		<input type='button' name='btn_acao2' id='btn_acao' value="Gravar" /><br /><br />
		<a href="extrato_fechamento.php">
			<button type="button" style="background-color: darkred;color: white;font-weight: bolder;cursor: pointer;">
				Desfazer
			</button>
		</a>
	<?php
	}

	echo "</form>";
	}
}

include "rodape.php";
?>
