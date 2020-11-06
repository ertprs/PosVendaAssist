<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";
include "funcoes.php";

$layout_menu = "financeiro";
$title = "APROVAÇÃO TÉCNICA DE PAGAMENTO DE MÃO DE OBRA";


if (strlen($_POST["codigo_posto"]) > 0) $codigo_posto = $_POST["codigo_posto"];
if (strlen($_GET["codigo_posto"])  > 0) $codigo_posto = $_GET["codigo_posto"];

$nome   = $_POST['nome'];
if (strlen($_GET['nome']) > 0) $nome = $_GET['nome'];


$aprovador = array('258','2405','2619','206','173');

$print_codigo = $_POST['print_codigo'];
if(!empty($print_codigo)) {
	$sql4 = "SELECT DISTINCT tbl_extrato.valor_agrupado ,  tbl_extrato.extrato 
						from tbl_extrato
						join tbl_extrato_agrupado using(extrato)
						where tbl_extrato_agrupado.codigo = '$print_codigo' ";
	$res4 = pg_query ($con,$sql4);
	if(pg_num_rows($res4) != 0){
		for ($x4 = 0 ; $x4 < pg_num_rows($res4) ; $x4++){
			$total = trim(pg_fetch_result($res4,$x4,valor_agrupado));
			$extrato = trim(pg_fetch_result($res4,$x4,extrato));
			$xtotal = $xtotal + $total;

			echo "<script>window.open('extrato_posto_mao_obra_novo_britania_print.php?extrato=$extrato&codigo_agrupado=$codigo_agrupado','','height=600, width=750, top=20, left=20, scrollbars=yes')</script>";
		}
	}else{
		echo "<script>window.alert(\"Nenhum resultado encontrado!\")</script>";
	}
	exit;
}

$msg_erro = "";


function aprova_extrato($valor,$tipo,$con,$login_fabrica,$login_admin) {
	global $con;

	$cond = ($tipo == 'extrato') ? " AND tbl_extrato_agrupado.extrato =$valor " : " AND tbl_extrato_agrupado.codigo ='$valor'" ;

	if($tipo == $extrato) {
		$sql = " SELECT DISTINCT codigo
				FROM tbl_extrato_agrupado
				WHERE extrato = $extrato";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$codigo = pg_fetch_result($res,0,'codigo');
		}
	}else{
		$codigo = $valor;
	}

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sqlu = " UPDATE tbl_extrato_agrupado SET 
				admin = $login_admin,
				aprovado = current_timestamp
				WHERE reprovado IS NULL
				AND   aprovado  IS NULL
				$cond";
	$resu = pg_query($con,$sqlu);
	$msg_erro = pg_errormessage($con);

	$sqlt = " SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
			FROM tbl_extrato
			JOIN tbl_extrato_agrupado USING(extrato)
			JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
			JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
			WHERE tbl_extrato.fabrica = $login_fabrica
			and   cancelada IS NOT TRUE
			AND  tbl_extrato_agrupado.aprovado IS NOT NULL
			AND    codigo = '$codigo'";
	$rest = pg_query($con,$sqlt);
	if(pg_num_rows($rest) > 0){
		$total = pg_fetch_result($rest,0,'total');	
	}else{
		$total = 0;
	}
	
	
	$total_avulso = 0;
	$sql_av = " SELECT
		extrato,
		historico,
		valor,
		debito_credito,
		lancamento
	FROM tbl_extrato_lancamento
	JOIN tbl_extrato_agrupado USING(extrato)
	WHERE fabrica = $login_fabrica
	AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))
	AND  tbl_extrato_agrupado.aprovado IS NOT NULL
	AND    codigo = '$codigo'";

	$res_av = pg_query ($con,$sql_av);

	if(pg_num_rows($res_av) > 0){
		for($i=0; $i < pg_num_rows($res_av); $i++){
			$extrato         = trim(pg_fetch_result($res_av, $i, extrato));
			$historico       = trim(pg_fetch_result($res_av, $i, historico));
			$valor           = trim(pg_fetch_result($res_av, $i, valor));
			$debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
			$lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
			
			if($debito_credito == 'D'){ 
				if (($lancamento == 78 or $lancamento == 248) AND $valor>0){
					$valor = $valor * -1;
				}
			}

			$total_avulso = $valor + $total_avulso;
		}
	}else{
		$total_avulso = 0 ;
	}
	
	$total += $total_avulso;

	if($total < 0) {
		$total = 0 ;
	}
	
	$sqlu = " UPDATE tbl_extrato set valor_agrupado = $total
				from tbl_extrato_agrupado
				WHERE tbl_extrato_agrupado.extrato = tbl_extrato.extrato
				AND   valor_agrupado IS NULL
				and   tbl_extrato_agrupado.aprovado IS NOT NULL
				AND   fabrica = $login_fabrica
				$cond";
	$resu = pg_query($con,$sqlu);
	$msg_erro = pg_errormessage($con);

	$total_valor = number_format($total,2,",",".");

	$sql3 = "SELECT DISTINCT to_char(data_geracao,'MM/YYYY') as data_geracao,
							 to_char(data_conferencia,'DD/MM/YYYY') as data_conferencia,
							 tbl_extrato.posto 
					FROM tbl_extrato
					JOIN tbl_extrato_conferencia USING(extrato)
					JOIN tbl_extrato_agrupado using(extrato)
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   cancelada IS NOT TRUE
					AND   tbl_extrato_agrupado.aprovado IS NOT NULL
					$cond
					ORDER BY data_conferencia";
	$res3 = pg_query ($con,$sql3);
	$total_data = "";
	for ($x3 = 0 ; $x3 < pg_num_rows($res3) ; $x3++){
		$data_geracao     = trim(pg_fetch_result($res3,$x3,data_geracao));
		$data_conferencia = trim(pg_fetch_result($res3,$x3,data_conferencia));
		$posto            = trim(pg_fetch_result($res3,$x3,'posto'));
		$total_data .= ($x3 ==0) ? $data_geracao : ",".$data_geracao;
	}
	if(strlen($msg_erro) ==0) {
	
		$aux_descricao = "Extrato Conferido";
		$aux_mensagem  = "<br> 
							Seu lote referente(s) ao(s) extrato(s) <font size=\"4\"> \"$total_data\" </font>foi conferido em $data_conferencia. para maiores detalhes, verificar a <font size=\"4\"><b>Relação de conferência(s) / Previsão de Pagamento</b></font>.
							<br/>
							Em casos em que ocorram divergências serão apontadas em cada extrato, o posto terá como regularizar em 30 dias, Caso não sejam corrigidas as divergências as peças enviadas em garantia serão debitadas.
							<br/>
							Qualquer dúvida, enviar e-mail para: <u>auditoria.at@britania.com.br</u>
							<br/><br/>
							Atenciosamente
							<br/>
							Britania & Philco
							<br/>
							Dpto de Contas a Pagar - AT";
		$aux_tipo      = "Comunicado";
		$aux_obrigatorio_os_produto = "'f'";
		$aux_obrigatorio_site = "'t'";
		$aux_ativo = "'t'";
		$remetente_email = "auditoria.at@britania.com.br";

		$sqlc = "INSERT INTO tbl_comunicado (
			descricao              ,
			mensagem               ,
			tipo                   ,
			fabrica                ,
			obrigatorio_site       ,
			posto                  ,
			ativo                  
			) VALUES (
			'$aux_descricao'            ,
			'$aux_mensagem'             ,
			'$aux_tipo'                 ,
			$login_fabrica              ,
			$aux_obrigatorio_site       ,
			$posto                      ,
			$aux_ativo                  
		);";
		$resc = @pg_query ($con,$sqlc);
		$msg_erro = pg_last_error($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		return 'ok';
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
		return $msg_erro;
	}
}

function reprova_extrato($valor,$tipo,$motivo,$con,$login_fabrica,$login_admin) {
	global $con;

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$cond = ($tipo == 'extrato') ? " AND tbl_extrato_agrupado.extrato =$valor " : " AND tbl_extrato_agrupado.codigo ='$valor'" ;

	$sql = " UPDATE tbl_extrato_agrupado SET
				admin = $login_admin,
				reprovado = CURRENT_TIMESTAMP,
				motivo_reprovacao = '$motivo'
			WHERE reprovado IS NULL
			AND   aprovado  IS NULL
			$cond";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	$sqlt = " SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total,
					 tbl_extrato.extrato
			FROM tbl_extrato
			JOIN tbl_extrato_agrupado USING(extrato)
			JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
			JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
			WHERE tbl_extrato.fabrica = $login_fabrica
			and   cancelada IS NOT TRUE
			AND  tbl_extrato_agrupado.reprovado IS NOT NULL
			$cond
			GROUP BY tbl_extrato.extrato";
	$rest = pg_query($con,$sqlt);
	if(pg_num_rows($rest) > 0){
		for($i =0;$i<pg_num_rows($rest);$i++) {
			$total = pg_fetch_result($rest,$i,'total');	
			$extrato = pg_fetch_result($rest,$i,'extrato');

			$total_avulso = 0;
			$sql_av = " SELECT
				valor,
				debito_credito,
				lancamento
			FROM tbl_extrato_lancamento
			JOIN tbl_extrato_agrupado USING(extrato)
			WHERE fabrica = $login_fabrica
			AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))
			AND  tbl_extrato_agrupado.reprovado IS NOT NULL
			AND   tbl_extrato_lancamento.extrato = $extrato";

			$res_av = pg_query ($con,$sql_av);

			if(pg_num_rows($res_av) > 0){
				for($a=0; $a < pg_num_rows($res_av); $a++){
					$valor           = trim(pg_fetch_result($res_av, $a,'valor'));
					$debito_credito  = trim(pg_fetch_result($res_av, $a,'debito_credito'));
					$lancamento      = trim(pg_fetch_result($res_av, $a,'lancamento'));
					
					if($debito_credito == 'D'){ 
						if (($lancamento == 78 or $lancamento == 248) AND $valor>0){
							$valor = $valor * -1;
						}
					}
					$total_avulso = $valor + $total_avulso;
				}
			}else{
				$total_avulso = 0 ;
			}
			
			$total += $total_avulso;

			if($total < 0) {
				$total = 0 ;
			}

			$sqli = " INSERT INTO tbl_extrato_lancamento (
								posto                ,
								fabrica              ,
								lancamento           ,
								historico            ,
								valor                ,
								admin                ,
								extrato
					  ) SELECT
						posto  ,
						fabrica,
						248    ,
						'PAGAMENTO NÃO AUTORIZADO',
						$total,
						$login_admin,
						extrato
						FROM tbl_extrato
						WHERE extrato = $extrato";
			$resi = pg_query($con,$sqli);
			$msg_erro .= pg_last_error($con);
		}
	}else{
		$total = 0;
	}
	
	if(empty($msg_erro)) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		echo "ok";
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
		echo "Erro ao reprovar o extrato";
	}

}


function reprova_cancela_extrato($valor,$tipo,$motivo,$con,$login_fabrica,$login_admin) {
	global $con;

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$cond = ($tipo == 'extrato') ? " AND tbl_extrato_agrupado.extrato =$valor " : " AND tbl_extrato_agrupado.codigo ='$valor'" ;

	$sql = " UPDATE tbl_extrato_agrupado SET
				admin_cancela_reprova = $login_admin,
				reprovado = null,
				motivo_cancela_reprovacao = '$motivo'
			WHERE reprovado IS NOT NULL
			AND   aprovado  IS NULL
			$cond";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	$sql = "DELETE from tbl_extrato_lancamento
			USING tbl_extrato 
			JOIN  tbl_extrato_agrupado USING(extrato)
			WHERE tbl_extrato.extrato = tbl_extrato_lancamento.extrato
			AND   tbl_extrato_lancamento.lancamento = 248
			$cond";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);
	
	if(empty($msg_erro)) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		echo "ok";
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
		echo "Erro ao reprovar o extrato";
	}

}


$mostra_reprovado = $_GET['mostra_reprovado'];

$aprova_valor       = $_POST['aprova_valor'];
$valor              = $_POST['valor'];
$cancela_valor      = $_POST['cancela_valor'];
$tipo               = $_POST['tipo'];
$motivo             = $_POST['motivo'];

if(!empty($valor) and !empty($tipo)) {
	if(!empty($motivo)) {
		reprova_extrato($valor,$tipo,$motivo,$con,$login_fabrica,$login_admin);
		exit;
	}else{
		echo "Especifique o motivo de reprovação";
		exit;
	}
}

if(!empty($cancela_valor) and !empty($tipo)) {
	if(!empty($motivo)) {
		reprova_cancela_extrato($cancela_valor,$tipo,$motivo,$con,$login_fabrica,$login_admin);
		exit;
	}else{
		echo "Informe o motivo de cancelar a reprovação";
		exit;
	}
}


if(isset($aprova_valor) and !empty($tipo)) {
	$msg_erro = aprova_extrato($aprova_valor,$tipo,$con,$login_fabrica,$login_admin);
	echo $msg_erro;
	exit;
}


include "cabecalho.php";
?>
<style>
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

.td_ordernar {
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border: 0px solid #596D9B;
}


</style>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>


<script language='javascript' src='js/jquery.js'></script>
<script language='javascript' src='js/jquery.tablesorter.new.js'></script>


<script>

$(document).ready(function () {	
	$('input[rel=aprova]').click(function(){
			var tipo= $(this)[0].id;
			var valor = $(this).attr('alt');

			var campo = $(this);
			$.ajax({
				type: "POST",
				url: "<?=$PHP_SELF?>",
				data:"aprova_valor="+valor+"&tipo="+tipo,
				complete: function(resposta){
					if(resposta.responseText=='ok') {
						$(campo).parent().html('Extrato Aprovado');
						$('input[alt='+valor+']').attr('disabled','disabled');
						
						$('span[rel='+valor+']').each(function(indice,elemento){
							if ( $(elemento).children('input').length >0 ){
								$(elemento).html('Extrato Aprovado');
							}
						});

						if (tipo =='codigo') {
							$('div[rel='+valor+'] input').attr('disabled','disabled');
						}

					}else{
						alert(resposta.responseText);
					}
				}
			})
		}
	)

	$('input[rel=reprova]').click(function(){
			var tipo= $(this)[0].id;
			var valor = $(this).attr('alt');
			$(this).parent().html("<br>Motivo <input type='text' id='motivo_"+valor+"' name='motivo_"+valor+"' size='20' maxlength='100' /><input type='button'  value='Gravar' onClick='gravarMotivo("+valor+",document.getElementById(\"motivo_"+valor+"\").value,\""+tipo+"\");' />");
		}
	)

	$('input[rel=cancela_reprova]').click(function(){
			var tipo= $(this)[0].id;
			var valor = $(this).attr('alt');
			$('input[alt='+valor+']').attr('disabled','disabled');
			$(this).parent().html("<br>Motivo<input type='text' id='motivo_"+valor+"' name='motivo_"+valor+"' size='20' maxlength='100' /><input type='button'  value='Gravar' onClick='gravarCancelaMotivo("+valor+",document.getElementById(\"motivo_"+valor+"\").value,\""+tipo+"\");' />");
		}
	)
});


function gravarMotivo(valor,motivo,tipo) {

	var cod_agrupador;
	cod_agrupador = $("#motivo_"+valor).parent().attr('rel');
	if(motivo.length == 0) {
		alert("Informe o motivo da reprovação ");
	}else{
		$.ajax({
			type: "POST",
			url: "<?=$PHP_SELF?>",
			data:"valor="+valor+"&motivo="+motivo+"&tipo="+tipo,
			complete: function(resposta){
				if(resposta.responseText=='ok') {
					
					$("#motivo_"+valor).parent().html("Extrato Reprovado");
					
					if ($('span[rel='+cod_agrupador+'] input').length == 0){
						$('input[alt='+cod_agrupador+']').attr('disabled','disabled');
					}
					
					
					$('input[alt='+valor+']').attr('disabled','disabled');
					if (tipo =='codigo') {
					
						$('span[rel='+valor+']').each(function(indice,elemento){
							if ( $(elemento).children('input').length >0 ){
								$(elemento).html('Extrato Reprovado');
							}
						});
					
						$('div[rel='+valor+'] input').attr('disabled','disabled');
					}
				}else{
					alert(resposta.responseText);
				}
			}
		})
	}
}

function gravarCancelaMotivo(valor,motivo,tipo) {
	if(motivo.length == 0) {
		alert("Informe o motivo de cancelar esta reprovação");
	}else{
		$.ajax({
			type: "POST",
			url: "<?=$PHP_SELF?>",
			data:"cancela_valor="+valor+"&motivo="+motivo+"&tipo="+tipo,
			complete: function(resposta){
				if(resposta.responseText=='ok') {
					$("#motivo_"+valor).parent().html("Reprovação Cancelada");
					if (tipo =='codigo') {
						$('div[rel='+valor+'] input').attr('disabled','disabled');
					}
				}else{
					alert(resposta.responseText);
				}
			}
		})
	}
}

function abrePrint(codigo){
	$.post('<? echo $PHP_SELF; ?>',
		{
			print_codigo: codigo
		},
		function(resposta){
			$('#print').html(resposta);
		}
	)
}



$(function() {
	$(".tabela_ordenacao")
	.tablesorter({widthFixed: true, widgets: ['zebra']});
});


</script>

<?
if(strlen($msg_erro)>0){
	echo "<DIV class='msg_erro' style='width:700px;'>".$msg_erro."</DIV>";
}
?>
<p>
<center>




<?
	echo "<p>";
	if(!empty($mostra_reprovado)) {
		echo "<input type='button' value =' Voltar para Aprovação ' onclick='javascript: window.location=\"$PHP_SELF\";' />";
	}else{
		echo "<input type='button' value =' Cancelar Extratos Reprovados ' onclick='javascript: window.location=\"$PHP_SELF?mostra_reprovado=sim\";' />";
	}
	echo "</p>";

	$condicao = (!empty($mostra_reprovado)) ? " AND tbl_extrato_agrupado.reprovado IS NOT NULL " : " AND  tbl_extrato_agrupado.reprovado IS NULL";
	


	echo "<FORM METHOD='POST' NAME='frm_ordernacao' ACTION='$PHP_SELF'>";
	
	$ordernar_por = $_POST['ordernar_por_valor'];
	
	if($ordernar_por <> '') {
		if($ordernar_por == '1'){
			$check_ordenacao_1 = "checked";
		}else{
			$check_ordenacao_2 = "checked";
		}
	}

	echo "<table width='975' align='center' cellspacing='0' border='0' class='tabela'>
			<tr>
				<th class='td_ordernar'>Ordernar Por</th>
			</tr>
			<tr>
				<th class='td_ordernar'><input type='radio' $check_ordenacao_1 name='ordernar_por_valor' id='ordernar_por_valor' value='1'>Data de Conferência&nbsp;&nbsp;&nbsp;<input type='radio' $check_ordenacao_2 name='ordernar_por_valor' id='ordernar_por_valor' value='2'>Valores (Total do Posto)<br><br></th>
			</tr>
			<tr>
				<th class='td_ordernar'><input type='submit' name='ordernar' id='ordernar' value='Ordernar'><br><br></th>
			</tr>
		 </table>";
		 	
			//echo "<BR><BR>ORDERNADO POR ==".$ordernar_por."<BR><BR>";
			if($ordernar_por <> '') {
				if($ordernar_por == '1'){
					$ordernado = "dt_conf ASC, codigo ,total ";
				}else{
					$ordernado = "total_order DESC,codigo ,total";
				}
			}else{
				$ordernado = " codigo ,posto , dt_conf , extrato ASC";
			}
		echo "</form>";


							 

	$sql = "SELECT  DISTINCT tbl_extrato.extrato                                       ,
					sum(tbl_extrato_conferencia_item.mao_de_obra) as total             ,
					0::float as total_order_anterior											,
					0::float as total_order													,
					to_char(data_geracao,'DD/MM/YYYY') as data_geracao                 ,
					tbl_extrato.posto                                                  ,
					tbl_posto.nome as posto_nome                                       ,
					tbl_posto_fabrica.codigo_posto                                     ,
					tbl_extrato_agrupado.codigo as codigo                              ,
					tbl_extrato_conferencia.obs_fabricante                             ,
					to_char(tbl_extrato_conferencia.data_conferencia, 'DD/MM/YYYY') as data_conferencia,
					to_char(tbl_extrato_conferencia.data_conferencia, 'YYYY-MM-DD')  as dt_conf,
					tbl_admin.login                                                     ,
					(
						SELECT extrato
						FROM tbl_extrato ext
						JOIN tbl_extrato_conferencia USING(extrato)
						WHERE ext.posto = tbl_extrato.posto
						AND   fabrica   = $login_fabrica
						AND   ext.extrato < tbl_extrato.extrato
						AND   cancelada IS NOT TRUE
						ORDER BY ext.extrato DESC LIMIT 1
					) as extrato_anterior 
					into TEMP tmp_relatorio_aprova_pag_mo_$login_admin
				FROM tbl_extrato
				JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
				JOIN tbl_extrato_agrupado ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato
				LEFT JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
				JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_admin ON tbl_admin.admin = tbl_extrato_conferencia.admin
				WHERE   tbl_extrato.fabrica = $login_fabrica
				AND     tbl_extrato_conferencia.cancelada IS NOT TRUE
				AND     tbl_extrato_conferencia.data_conferencia > '2011-01-01 00:00:00'
				AND     tbl_extrato_agrupado.codigo IS NOT NULL
				AND     tbl_extrato_conferencia.nota_fiscal IS NULL
				AND     tbl_extrato_conferencia.caixa IS NOT NULL
				AND     tbl_extrato.valor_agrupado IS NULL
				$condicao
				GROUP BY tbl_extrato.extrato                ,
					tbl_extrato.total                       ,
					data_geracao                            ,
					tbl_extrato.posto                       ,
					tbl_posto.nome                          ,
					tbl_posto_fabrica.codigo_posto          ,
					tbl_extrato_agrupado.codigo             ,
					tbl_extrato_conferencia.data_conferencia,
					tbl_extrato_conferencia.obs_fabricante  ,
					tbl_admin.login
				ORDER BY tbl_extrato_agrupado.codigo ,tbl_extrato.posto , dt_conf , tbl_extrato.extrato ASC";
	//echo nl2br($sql)."<br><br>";
	$res = pg_query ($con,$sql);


	$sql_busca ="SELECT * FROM tmp_relatorio_aprova_pag_mo_$login_admin ORDER BY extrato";
	//echo nl2br($sql_busca)."<br>";
	$res = pg_query ($con,$sql_busca);
	if(pg_num_rows($res) > 0) { 
		for($d = 0 ; $d < pg_num_rows($res) ; $d++) {
			$extrato_1          = trim(pg_fetch_result($res,$d,'extrato'));
			$posto_1            = trim(pg_fetch_result($res,$d,'posto'));
			$codigo_posto_1     = trim(pg_fetch_result($res,$d,'codigo_posto'));
			$posto_nome_1       = trim(pg_fetch_result($res,$d,'posto_nome'));
			$login_1            = trim(pg_fetch_result($res,$d,'login'));
			$data_conferencia_1 = trim(pg_fetch_result($res,$d,'data_conferencia'));
			$data_geracao_1     = trim(pg_fetch_result($res,$d,'data_geracao'));
			$total_1            = trim(pg_fetch_result($res,$d,'total'));
			$codigo_1           = trim(pg_fetch_result($res,$d,'codigo'));
			$observacao_1       = trim(pg_fetch_result($res,$d,'obs_fabricante'));
			$extrato_anterior_1 = trim(pg_fetch_result($res,$d,'extrato_anterior'));
			$total_order_1	  = trim(pg_fetch_result($res,$d,'total_order'));

			$extrato_anterior_1 = ($extrato_anterior_1 == 0 or empty($extrato_anterior_1)) ? 1 : $extrato_anterior_1;
			$posto_nome_1       = substr($posto_nome_1,0,35);




			
			$total_avulso_1 = 0;
			$sql_av = " SELECT
							extrato,
							historico,
							valor,
							admin,
							debito_credito,
							lancamento
						FROM tbl_extrato_lancamento
						WHERE extrato = $extrato_1
						AND fabrica = $login_fabrica
						AND (admin IS NOT NULL OR lancamento in (103,104))";
			$res_av = pg_query ($con,$sql_av);
			if(pg_num_rows($res_av) > 0) {
				for($i=0; $i < pg_num_rows($res_av); $i++) {
					$extrato_1         = trim(pg_fetch_result($res_av, $i, extrato));
					$historico_1       = trim(pg_fetch_result($res_av, $i, historico));
					$valor_1           = trim(pg_fetch_result($res_av, $i, valor));
					$debito_credito_1  = trim(pg_fetch_result($res_av, $i, debito_credito));
					$lancamento_1      = trim(pg_fetch_result($res_av, $i, lancamento));
					
					if($debito_credito_1 == 'D'){ 
						if (($lancamento_1 == 78 or $lancamento_1 == 248) AND $valor_1>0){
							$valor_1 = $valor_1 * -1;
						}
					}

					$total_avulso_1 = $valor_1 + $total_avulso_1;
				}
			}else {
				$total_avulso_1 = 0 ;
			}
			
			$total_1 += $total_avulso_1;
			

			$sqlt = "   SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
						FROM tbl_extrato
						JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
						JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
						WHERE tbl_extrato.extrato = $extrato_anterior_1
						AND   tbl_extrato.fabrica = $login_fabrica
						and   cancelada IS NOT TRUE";
			$rest = pg_query($con,$sqlt);
			$total_anterior_1= pg_fetch_result($rest,0,total);

			$total_avulso_1 = 0;
			$sql_av_1 = "  SELECT
								valor,
								debito_credito,
								lancamento
							FROM tbl_extrato_lancamento
							WHERE tbl_extrato_lancamento.extrato = $extrato_anterior_1
							AND fabrica = $login_fabrica
							AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))";

			$res_av_1 = pg_query ($con,$sql_av_1);

			if(pg_num_rows($res_av_1) > 0) {
				for($i=0; $i < pg_num_rows($res_av_1); $i++){
					$valor_1           = trim(pg_fetch_result($res_av_1, $i, valor));
					$debito_credito_1  = trim(pg_fetch_result($res_av_1, $i, debito_credito));
					$lancamento_1      = trim(pg_fetch_result($res_av_1, $i, lancamento));
					
					if($debito_credito_1 == 'D'){ 
						if ($lancamento_1 == 78 AND $valor_1>0){
							$valor_1 = $valor_1 * -1;
						}
					}

					$total_avulso_anterior_1 += $valor_1 ;
				}
			}else {
				$total_avulso_anterior_1 = 0 ;
			}
			
			$total_anterior_1 += $total_avulso_anterior_1;

			if($total_anterior_1 <= 0) {
				$total_anterior_1 = 1 ;
			}

			if($total_1 <= 0) {
				$total_1= 0 ;
			}

			


			
			if($codigo_anterior_1 != $codigo_1) {
				$total_posto_1 = 0;
				$total_posto_1 = $total_1;
				$conta_posto_1 = 0;
			}else{
				$total_posto_1 += $total_1;
				$conta_posto_1 +=1;
			}

			$sql_atualizar_1 = "UPDATE tmp_relatorio_aprova_pag_mo_$login_admin SET
									total_order_anterior = $total_posto_1  
								WHERE extrato_anterior = $extrato_anterior_1";
			//echo nl2br($sql_atualizar_1).";"."<br><br><br><br><br>";
			$res_atualizar_1 = pg_query($con,$sql_atualizar_1);
			$msg_erro = pg_last_error($con);


		}
	}

	
	$sql_atualizar_2 = "UPDATE tmp_relatorio_aprova_pag_mo_$login_admin SET
							total_order_anterior = x.total
						from (select total,posto from tmp_relatorio_aprova_pag_mo_$login_admin) x
						WHERE x.posto = tmp_relatorio_aprova_pag_mo_$login_admin.posto
						AND tmp_relatorio_aprova_pag_mo_$login_admin.extrato_anterior IS NULL";
	//echo nl2br($sql_atualizar_2)."<br><br><br><br><br>";
	$res_atualizar_2 = pg_query($con,$sql_atualizar_2);
	$msg_erro = pg_last_error($con);



	$sql_atualizar_1 = "UPDATE tmp_relatorio_aprova_pag_mo_$login_admin SET
							total_order = x.total
						from (select sum(total_order_anterior) as total,posto from tmp_relatorio_aprova_pag_mo_$login_admin group by posto) x
						WHERE x.posto = tmp_relatorio_aprova_pag_mo_$login_admin.posto";
	//echo nl2br($sql_atualizar_1)."<br><br><br><br><br>";
	$res_atualizar_1 = pg_query($con,$sql_atualizar_1);
	$msg_erro = pg_last_error($con);
	


	$sql_busca_res = "SELECT  
					    extrato														    ,
						total															,
						data_geracao													,
						posto															,
						posto_nome														,
						codigo_posto													,
						codigo															,
						obs_fabricante													,
						data_conferencia												,
						dt_conf															,
						login															,
						extrato_anterior			  									,
						total_order    
					 FROM tmp_relatorio_aprova_pag_mo_$login_admin 
					ORDER BY   $ordernado ";
	//echo nl2br($sql_busca_res)."<br><br><br>";
	$rest_busca = pg_query($con,$sql_busca_res);
	//include('../helpdesk/mlg_funciones.php');
	//echo array2table(pg_fetch_all($rest_busca));
	//echo "<br><br><br><br><br><br><br><br><br><br><br><br><br>";
	
	if(pg_num_rows($rest_busca) > 0) {
		echo "<table width='975' align='center' cellspacing='1' class='tabela'>";

		echo "<thead>	
				<tr class='titulo_coluna'>
					<th>Data de Conferência</th>
					<th>Cod. Posto</th>
					<th>Posto</th>
					<th>Conferente</th>
					<th>Cod. Agrupador</th>
					<th>Extrato</th>
					<th>Valor</th>
					<th>Observação</th>
					<th>Percentual Variação</th>
					<th width='255px'>Ações</th>
				</tr>
			</thead>";
	   echo "<tbody>";

		for ($x = 0 ; $x < pg_num_rows($rest_busca) ; $x++) {
			$extrato          = trim(pg_fetch_result($rest_busca,$x,'extrato'));
			$posto            = trim(pg_fetch_result($rest_busca,$x,'posto'));
			$codigo_posto     = trim(pg_fetch_result($rest_busca,$x,'codigo_posto'));
			$posto_nome       = trim(pg_fetch_result($rest_busca,$x,'posto_nome'));
			$login            = trim(pg_fetch_result($rest_busca,$x,'login'));
			$data_conferencia = trim(pg_fetch_result($rest_busca,$x,'data_conferencia'));
			$data_geracao     = trim(pg_fetch_result($rest_busca,$x,'data_geracao'));
			$total            = trim(pg_fetch_result($rest_busca,$x,'total'));
			$codigo           = trim(pg_fetch_result($rest_busca,$x,'codigo'));
			$observacao       = trim(pg_fetch_result($rest_busca,$x,'obs_fabricante'));
			$extrato_anterior = trim(pg_fetch_result($rest_busca,$x,'extrato_anterior'));
			$total_order = trim(pg_fetch_result($rest_busca,$x,'total_order'));
			$extrato_anterior = ($extrato_anterior == 0 or empty($extrato_anterior)) ? 1 : $extrato_anterior;
			$posto_nome       = substr($posto_nome,0,35);
			
			if(empty($total)) {
				$total = 0;
			}
			$cor = ($x % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
			
			if ($codigo_anterior != $codigo and !empty($codigo_anterior)){ 
				if($conta_posto >= 1) $total_descricao = "&nbsp;"; else $total_descricao = "Total";
				echo (!empty($codigo_anterior)) ?"<tr class='subtitulo'>
				<td colspan='6' align='left'>$total_descricao</td><td><b>".number_format($total_posto,2,",",".")."</b></td>":"";

				echo "<td colspan='2'>";
					if($conta_posto >= 1)
						echo "AGRUPADO TOTAL";
					else
						echo "&nbsp;";
				echo "</td>";
					echo "<td colspan='1' width='255px' align='center'>";
					if(in_array($login_admin,$aprovador)) {
							if(!empty($mostra_reprovado)) {
								echo "<FORM METHOD='post' NAME='frm_por_codigo' ACTION='$PHP_SELF' style='margin: 0; padding: 0'>
											<input type='hidden' name='codigo_agrupado' value='$codigo_anterior'>
											<input type='button' name='btn_reprova_codigo' value='Cancelar Reprovação' rel='cancela_reprova' alt='$codigo_anterior' id='codigo'>
										</form>";
							}else{
								
								echo "<FORM METHOD='post' NAME='frm_por_codigo' ACTION='$PHP_SELF' style='margin: 0; padding: 0'>";

								if ($conta_posto== 0){
									echo "
										<input type='hidden' name='codigo_agrupado' value='$codigo_anterior'>
										<span><input type='button' name='btn_aprova_codigo' value='Aprovar' rel='aprova' alt='$codigo_anterior' id='codigo' ></span>";
								}

								if($conta_posto >= 1) {
									echo "
											<input type='hidden' name='codigo_agrupado' value='$codigo_anterior'>
											<span><input type='button' name='btn_aprova_codigo' value='Aprovar' rel='aprova' alt='$codigo_anterior' id='codigo' ></span>
											<span><input type='button' name='btn_reprova_codigo' value='Reprovar' rel='reprova' alt='$codigo_anterior' id='codigo'></span>";
								}else{
									echo "&nbsp;";
								}

								echo "</form>";

							}
						}else
							echo "&nbsp;";

					echo "</td>"; 
				echo "</tr>";
			}

			$total_avulso = 0;
			$sql_av = " SELECT
				extrato,
				historico,
				valor,
				admin,
				debito_credito,
				lancamento
			FROM tbl_extrato_lancamento
			WHERE extrato = $extrato
			AND fabrica = $login_fabrica
			AND (admin IS NOT NULL OR lancamento in (103,104))";

			$res_av = pg_query ($con,$sql_av);

			if(pg_num_rows($res_av) > 0){
				for($i=0; $i < pg_num_rows($res_av); $i++){
					$extrato         = trim(pg_fetch_result($res_av, $i, extrato));
					$historico       = trim(pg_fetch_result($res_av, $i, historico));
					$valor           = trim(pg_fetch_result($res_av, $i, valor));
					$debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
					$lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
					
					if($debito_credito == 'D'){ 
						if (($lancamento == 78 or $lancamento == 248) AND $valor>0){
							$valor = $valor * -1;
						}
					}

					$total_avulso = $valor + $total_avulso;
				}
			}else{
				$total_avulso = 0 ;
			}
			
			$total += $total_avulso;
			
			if($total < 0) {
				//HD 283715: O usuário questionou divergência no agrupamento. O problema é que quando o total é negativo não estava mostrando na tela, mas na hora de totalizar ele considera o valor negativo
				//$total = 0 ;
			}

			$sqlt = " SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
									FROM tbl_extrato
									JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
									JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
									WHERE tbl_extrato.extrato = $extrato_anterior
									AND   tbl_extrato.fabrica = $login_fabrica
									and   cancelada IS NOT TRUE";
			$rest = pg_query($con,$sqlt);
			$total_anterior = pg_fetch_result($rest,0,total);

			$total_avulso = 0;
			$sql_av = " SELECT
				valor,
				debito_credito,
				lancamento
			FROM tbl_extrato_lancamento
			WHERE tbl_extrato_lancamento.extrato = $extrato_anterior
			AND fabrica = $login_fabrica
			AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))";

			$res_av = pg_query ($con,$sql_av);

			if(pg_num_rows($res_av) > 0){
				for($i=0; $i < pg_num_rows($res_av); $i++){
					$valor           = trim(pg_fetch_result($res_av, $i, valor));
					$debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
					$lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
					
					if($debito_credito == 'D'){ 
						if ($lancamento == 78 AND $valor>0){
							$valor = $valor * -1;
						}
					}

					$total_avulso_anterior += $valor ;
				}
			}else{
				$total_avulso_anterior = 0 ;
			}
			
			$total_anterior += $total_avulso_anterior;

			if($total_anterior <= 0) {
				$total_anterior = 1 ;
			}

			if($total <= 0) {
				$total= 0 ;
			}

			echo "<tr bgcolor='$cor' >";
			echo "<FORM METHOD='post' NAME='frm_por_extrato' ACTION='$PHP_SELF'>";
			echo "<td>";
				echo (strlen($data_conferencia) != 0) ? $data_conferencia : "&nbsp;";
			echo "</td>";
			echo "<td>";
				echo (strlen($codigo_posto) != 0) ? $codigo_posto : "&nbsp;";
			echo "</td>";
			echo "<td>";
				echo (strlen($posto_nome) != 0) ? $posto_nome : "&nbsp;";
			echo "</td>";
			echo "<td>";
				echo (strlen($login) != 0) ? $login : "&nbsp;";
			echo "</td>";
			echo "<td>";
				echo (strlen($codigo) != 0) ? "<a href='javascript: abrePrint($codigo)'>$codigo</a>" : "&nbsp;";
			echo "</td>";
			echo "<td>";
				echo (strlen($data_geracao) != 0) ? $data_geracao : "&nbsp;";
			echo "</td>";
			echo "<td>";
				echo (strlen($total) != 0) ? number_format($total,2,",",".") : "&nbsp;";
			echo "</td>";
			echo "<td>";
				echo (strlen($observacao) != 0) ? $observacao : "&nbsp;";
			echo "</td>";
			echo "<td>";
				echo number_format(((($total/$total_anterior)*100)-100),2,",",".");
			echo "</td>";
			echo "<td nowrap align='center' width='150px' valign='middle'>";
			if(in_array($login_admin,$aprovador)) {
				if(!empty($mostra_reprovado)) {
					echo "<span rel='$codigo'><input type='button' name='btn_reprova' value='Cancelar Reprovação' rel='cancela_reprova' id='extrato' alt='$extrato'/></span>";
				}else{
						echo "<input type='hidden' name='extrato' value='$extrato'>";
						if ($login_fabrica <> 3){
							echo "<span rel='$codigo'><input type='button' name='btn_aprova' value='Aprovar' rel='aprova' id='extrato' alt='$extrato'></span>&nbsp;";
						}
						echo "<span rel='$codigo'><input type='button' name='btn_reprova' value='Reprovar' rel='reprova' id='extrato' alt='$extrato'/></span>";
				}
			}else
				echo "&nbsp;";
			echo "</td>";

			echo "<INPUT TYPE='hidden' name='codigo_posto$aux_extrato' value='$codigo_posto' >";
			echo "<INPUT TYPE='hidden' name='extrato$aux_extrato' value='$extrato' >";
			echo "<INPUT TYPE='hidden' name='nome$aux_extrato' value='$nome' >";
			echo "</form>";
			echo "</tr>";
			
			if ($codigo_anterior != $codigo){
				$total_posto = 0;
				$total_posto = $total;
				$conta_posto = 0;
			}else{
				$total_posto += $total;
				$conta_posto +=1;
			}
			$codigo_anterior = $codigo;
		}



		if ($x == pg_num_rows($rest_busca)){ 
			echo "<tr class='subtitulo'>
			<td colspan='6' align='left'>&nbsp;</td>
			<td><b>".number_format($total_posto,2,",",".")."</b></td>";
			echo "<td colspan='2' align='left'>AGRUPADO TOTAL</td>";
			echo "<td align='center' colpan='2' valign='middle'>";
			if(in_array($login_admin,$aprovador)) {
				if(!empty($mostra_reprovado) AND $conta_posto >=1) {
					echo "<form METHOD='post' NAME='frm_por_codigo' ACTION='$PHP_SELF' style='margin: 0; padding: 0;'>
							<input type='hidden' name='codigo_agrupado' value='$codigo' />
							<input type='button' name='btn_reprova_codigo' value='Cancelar Reprovação' rel='cancela_reprova' alt='$codigo' id='codigo' />
						</form>";
				}else{
					if($conta_posto ==0){
						echo "<form METHOD='post' NAME='frm_por_codigo' ACTION='$PHP_SELF'  style='margin: 0; padding: 0;'>";
							echo "<input type='hidden' name='codigo_agrupado' value='$codigo' />";
							echo "<span><input type='button' name='btn_aprova_codigo' value='Aprovar' rel='aprova' id='codigo' alt='$codigo' /></span>&nbsp;";
							echo "</form>";
					}
					if($conta_posto >=1) {
						echo "<form METHOD='post' NAME='frm_por_codigo' ACTION='$PHP_SELF'  style='margin: 0; padding: 0;'>";
								echo "<input type='hidden' name='codigo_agrupado' value='$codigo' />";
								echo "<span><input type='button' name='btn_aprova_codigo' value='Aprovar' rel='aprova' id='codigo' alt='$codigo' /></span>&nbsp;";
								echo "<span><input type='button' name='btn_reprova_codigo' value='Reprovar' rel='reprova' alt='$codigo' id='codigo' /></span>";
						echo "</form>";
					}else
						echo "&nbsp;";
				}
			}else
				echo "&nbsp;";
			echo "</td></tr>";
		}
		
		echo "</tbody>";
		echo "</table>";
	}else{
		echo "<h1>Nenhum extrato encontrado</h1>";
	}
	echo "<div id='print'></div>";
?>

<? include "rodape.php"; ?>
