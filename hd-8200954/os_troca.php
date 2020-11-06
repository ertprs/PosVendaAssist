<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once 'funcoes.php';
include_once '_traducao_erro.php';

include_once 'anexaNF_inc.php';


####################### Importante, quando alterar o texto do motivo, deve alterar ##############
########################Deve alterar na os_press.php tanto do admin e do posto#############


$sql = "SELECT finalizada FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND finalizada IS NOT NULL";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	if($login_fabrica == 20){
		header("Location: os_press.php?os=$os");
	}else{
		header("Location: os_finalizada.php?os=$os");
	}
	exit;
}
if($btn_acao=='gravar'){
	$cortesia_comercial = trim($_POST['cortesia_comercial']);
	$causa_defeito1     = trim($_POST['causa_defeito1']);
	$identificacao1     = trim($_POST['identificacao1']);
	$defeito_constatado = trim($_POST["defeito_constatado"]);
	$bloqueio           = trim($_POST["bloqueio"]);
	$observacao2 = trim($_POST["observacao2"]);

	$sql = "SELECT  tbl_os.sua_os,
					tbl_os.fabrica,
					tbl_os.tipo_atendimento
			FROM    tbl_os
			WHERE   tbl_os.os = $os";

	$res = pg_query ($con,$sql) ;

	$tipo_atendimento = pg_fetch_result($res, 0, 'tipo_atendimento');

	//if ($login_pais =='BR') $bloqueio = 2; // HD 65904
	if(strlen($causa_defeito1)==0){
		$msg_erro.= traduz("informe.o.defeito.troca", $con, $cook_idioma).'<br>';
	}
	if(strlen($identificacao1)==0 ){
		$msg_erro.= traduz("informe.a.identificacao.para.troca", $con, $cook_idioma).'<br>';
	}

	if(strlen($observacao2)==0 ){
		$msg_erro.= traduz("digite.a.informacao.complementar", $con, $cook_idioma).'<br>';
	}

	if(strlen($defeito_constatado)==0 and $login_pais =='BR'){
		$msg_erro.= 'Informe '.traduz("reparo", $con, $cook_idioma).'<br>';
	}
	if($login_fabrica == 20 and $login_pais =='BR' and   in_array($tipo_atendimento,array(10,11,12,13))){//hd_chamado=2808833
		$link_nf = temNF($os, 'count');
		if($link_nf == 0){
			if((empty($_FILES["foto_nf"]["name"]))){
	      		$msg_erro .= "Por favor inserir anexo da Nota Fiscal";
	  		}
  		}
  	}

	if (strlen ($msg_erro) == 0) {

		if ($anexaNotaFiscal and $_FILES['foto_nf']['tmp_name']) {
			$anexou = anexaNF($os, $_FILES['foto_nf']);
			if ($anexou !== 0) $msg_erro = (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
		}
	}

	//if(strlen($bloqueio)==0) $msg_erro = traduz("escolha.um.motivo.para.a.troca", $con, $cook_idioma);
	if(strlen($msg_erro) == 0) {

		$envia_email = 0;

		$res = @pg_exec($con,"BEGIN TRANSACTION");

		if(strlen($cortesia_comercial)>0){
			$sqlC = "UPDATE tbl_os_extra SET tipo_troca = $cortesia_comercial WHERE os = $os";
			$resC = pg_exec($con, $sqlC);
		}



		if(strlen($msg_erro)==0){
			$motivo2 = "'Informações complementares'";
			$sql = "
					SELECT os_troca_motivo
					FROM tbl_os_troca_motivo
					WHERE os     = $os
					AND   motivo = $motivo2";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)==0){
				$sql = "INSERT INTO tbl_os_troca_motivo(
							os               ,
							motivo           ,
							observacao
						)VALUES(
							$os              ,
							$motivo2         ,
							'$observacao2'
						)";
				$envia_email = 1;
			}else{
				$os_troca_motivo2 = pg_result($res,0,os_troca_motivo);
				$sql = "UPDATE tbl_os_troca_motivo SET
							observacao        = '$observacao2'
						WHERE os              = $os
						AND   os_troca_motivo = $os_troca_motivo2";
			}
			$res = pg_exec($con,$sql);
		}


		if(strlen($defeito_constatado) > 0){
			$sql_defeito = " , defeito_constatado=$defeito_constatado";
		}

		/*HD: 107958 - LIBEREI PRA TODOS OS PAISES*/
		//if ($login_pais =='BR') {
			$sql_fecha = " , data_fechamento = CURRENT_TIMESTAMP ";
		//}


		if(strlen($msg_erro)==0){
			$sql = "UPDATE tbl_os SET causa_defeito=$causa_defeito1, solucao_os = $identificacao1 $sql_defeito $sql_fecha WHERE os = $os AND fabrica = $login_fabrica ";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con) ;

			/*HD: 107958 - AQUI TBME FOI LIBERADO TAMBÉM PRA AL.*/
			if (strlen ($msg_erro) == 0 ) {
				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con) ;
			}
			if (strpos($msg_erro,"CONTEXT:")) {
				$x = explode('CONTEXT:',$msg_erro);
				$msg_erro = $x[0];
			}

			if(strlen($msg_erro)==0){
				$res = @pg_exec ($con,"COMMIT TRANSACTION");

				# HD 39511 - A rotina de enviar email foi mudada
				#   da os_cadastro para cá, para OS Troca
				if($login_fabrica == 20 and $envia_email == 1){ // 1
					$sql = "SELECT  tbl_posto.nome,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.email,
							tbl_os.consumidor_nome,
							tbl_os.promotor_treinamento,
							tbl_os.tipo_atendimento,
							tbl_produto.referencia,
							tbl_produto.descricao
						FROM  tbl_os
						JOIN  tbl_posto         USING(posto)
						JOIN  tbl_produto       USING(produto)
						JOIN  tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE os = $os and tipo_atendimento = 13";
					$res = pg_exec($con,$sql);
					if (pg_numrows($res) > 0) {//2
						$posto_nome      = trim(pg_result($res,0,nome));
						$codigo_posto    = trim(pg_result($res,0,codigo_posto));
						$consumidor_nome = trim(pg_result($res,0,consumidor_nome));
						$produto_ref     = trim(pg_result($res,0,referencia));
						$produto_nome    = trim(pg_result($res,0,descricao));
						$email           = trim(pg_result($res,0,email));
						$x_promotor_treinamento = trim(pg_result($res,0,promotor_treinamento));
						$tipo_atendimento = trim(pg_result($res,0,tipo_atendimento));
						//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO


							if( strlen($x_promotor_treinamento) > 0  and $x_promotor_treinamento <>'null'){ //4
								$sql = "SELECT email,nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $x_promotor_treinamento;";
								$res2 = pg_exec($con,$sql);
								$promotor_nome  = trim(pg_result($res2,0,nome));
								$promotor_email = trim(pg_result($res2,0,email));

								if(strlen($promotor_email) > 0 ){
									$email_origem  = "pt.garantia@br.bosch.com";
									$email_destino = "$promotor_email";
									$assunto       = "Novo OS de Cortesia";

									#Liberado: HD 18323
									if ($tipo_atendimento==13){
										if ($login_pais =='BR') {
											$assunto       = "Solicitação de Troca de Produto";
											$corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
											$corpo.="<br>O posto autorizado <b>$posto_nome</b>, código $codigo_posto, acaba cadastrar uma troca de produto e necessita de sua autorização.\n\n";
											$corpo.="<br>Troca de produto para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</ib>\n";
											$corpo.="<br><br>Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação de Troca. O número da OS é <b>$os</b>\n";
										}else{
											$assunto       = "Solicitação de Troca de Produto";
											$corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
											$corpo.="<br>El Servicio Técnico <b>$posto_nome</b>, código $codigo_posto, acaba de dar de alta un cambio de productoy solicita su autorización.\n\n";
											$corpo.="<br>Cambio de producto para el consumidor <b>$consumidor_nome</b> , referente a la máquina: <b>$produto_ref - $produto_nome</ib>\n";
											$corpo.="<br><br>Para aprovar/rechazar la OS, acceda al sistema assist, MENU Auditoria / Aprueba OS de Cambio. El número de la OS es el <b>$os</b>\n";
										}
									}else{
										if ($login_posto=='6359' OR 1==1){

											#if ($x_promotor_treinamento<>96){
											#	$email_destino = "fabio@telecontrol.com.br";
											#}

											$corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
											$corpo.="<br>O posto autorizado <b>$posto_nome</b>, código $codigo_posto, acaba de cadastrar uma cortesia e necessita de sua autorização.\n\n";
											$corpo.="<br>Cortesia para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</ib>\n";
											$corpo.="<br><br>Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação das OS de Cortesia. O número da OS é <b>$os</b>\n";
										}else{
											$corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
											$corpo.="<br>Você acaba de autorizar uma cortesia para o posto autorizado <b>$posto_nome</b>, código do posto: $codigo_posto\n\n";
											$corpo.="<br>Cortesia concedida para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</ib>\n";
											$corpo.="<br>Verificar a OS <b>$os</b>\n";
										}
									}
									$body_top = "--Message-Boundary\n";
									$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
									$body_top .= "Content-transfer-encoding: 7BIT\n";
									$body_top .= "Content-description: Mail message body\n\n";

									if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
										$enviou = 'ok';
									}
								}

							}//4
					} //2
				} //1

				if($login_fabrica == 20){
					header("Location: os_press.php?os=$os");
				}else{
					header("Location: os_finalizada.php?os=$os");
				}
				exit;
			}else{
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}
$title = traduz("cadastro.de.troca", $con, $cook_idioma);
include 'cabecalho.php';
?>
<style>
.Tabela{
	font-family: Verdana, sans;
	font-size: 12px;
	text-align: left;
}
.Tabela th{
	background: #AAAAAA;
	color: #FFFFFF;
}
</style>
<?
include "javascript_pesquisas.php";
include "admin/javascript_calendario.php";

?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_pedido').datePicker({startDate:'01/01/2000'});
		$("#data_pedido").maskedinput("99/99/9999");
	});
</script>
<?
$os=trim($_GET['os']) ;
$nova_tela = trim($_GET['nova_tela']) ;
if(strlen($os)>0){
	if(strlen($msg_erro)>0) {
		echo "<div style='color:#FFFFFF;background:#FF0000; width:700px;'>$msg_erro</div>";
	}

	$sql = "
			SELECT
				OS.sua_os                               ,
				OS.os 									,
				PR.referencia      AS produto_referencia,
				PR.descricao       AS produto_descricao ,
				OS.consumidor_nome                      ,
				OS.causa_defeito 						,
				OS.defeito_constatado 					,
				OS.solucao_os 							,
				OS.serie                                ,
				PT.nome            AS promotor_nome     ,
				OS.tipo_atendimento
			FROM tbl_os                   OS
			JOIN tbl_produto              PR USING(produto)
			LEFT JOIN tbl_promotor_treinamento PT USING (promotor_treinamento)
			WHERE OS.fabrica = $login_fabrica
			AND   OS.posto   = $login_posto
			AND   OS.os      = $os
			";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0){
		$sua_os             	= pg_result($res,0,sua_os);
		$produto_referencia 	= pg_result($res,0,produto_referencia);
		$produto_descricao  	= pg_result($res,0,produto_descricao);
		$consumidor_nome    	= pg_result($res,0,consumidor_nome);
		$promotor_nome      	= pg_result($res,0,promotor_nome);
		$serie              	= pg_result($res,0,serie);
		$tipo_atendimento   	= pg_result($res,0,tipo_atendimento);
		$id_os   				= pg_result($res,0,os);
		$causa_defeito_os 		= pg_result($res,0,causa_defeito);
		$defeito_constatado_os 	= pg_result($res,0,defeito_constatado);
		$solucao_os 			= pg_result($res,0,solucao_os);
	}

	$motivo1 = "Não são fornecidas peças de reposição para este produto.";
	$motivo2 = "Outros";
	$xmotivo1 = traduz("nao.sao.fornecidas.pecas.de.reposicao.para.este.produto.",$con,$cook_idioma);
	$xmotivo2 = traduz("outros",$con,$cook_idioma);
	if(strlen($msg_erro)==0){
		$sql = "SELECT servico_realizado,causa_defeito
				FROM   tbl_os_troca_motivo
				WHERE os     = $os
				AND   motivo = '$motivo1'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)==1){
			$identificacao1 = pg_result($res,0,servico_realizado);
			$causa_defeito1 = pg_result($res,0,causa_defeito);
		}

		$sql = "SELECT observacao
				FROM   tbl_os_troca_motivo
				WHERE os     = $os
				AND   motivo = '$motivo2'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)==1){
			$observacao2    = pg_result($res,0,observacao);
		}

	}

	echo "<form method='post' name='frm_troca' enctype='multipart/form-data'>";
	echo "<table class='Tabela' width='700' celspancign='0'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>OS</th>";
	echo "<th>";
	fecho("consumidor", $con, $cook_idioma);
	echo "</th>";
	echo "<th>";
	fecho("produto", $con, $cook_idioma);
	echo "</th>";
	echo "<th>Série</th>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	echo "<tr>";
	echo "<td>$sua_os</td>";
	echo "<td>$consumidor_nome</td>";
	echo "<td>$produto_referencia - $produto_descricao</td>";
	echo "<td>$serie</td>";
	echo "</tr>";
	echo "</tbody>";
	echo "</table>";

	echo "<table class='Tabela'>";
	echo "<tr>";
	echo "<td colspan='100%' align='center'><b>";
	fecho("motivo.da.troca",$con,$cook_idioma);
	echo "</b></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='100%' align='center'>&nbsp;</td>";
	echo "</tr>";
		echo "<tr>";
		echo "<td> ";
		echo ($sistema_lingua =='ES') ? "Identificación" : "Identificação";
		echo "<br>";

		echo "<select name='identificacao1' id='identificacao1' size='1' class='frm' rel=\"block\">";
		echo "<option value=''></option>";

		if ($login_fabrica == 20 and $tipo_atendimento == 12) {
			$where_adc = "AND tbl_servico_realizado.garantia_acessorio IS TRUE";
		} else {
			$where_adc = "AND tbl_servico_realizado.garantia_acessorio IS NOT TRUE";
		}

		$sql = "SELECT *
				FROM   tbl_servico_realizado
				WHERE  tbl_servico_realizado.fabrica = $login_fabrica
				AND    tbl_servico_realizado.solucao IS NOT TRUE
				AND    tbl_servico_realizado.ativo   IS     TRUE
				$where_adc
				ORDER BY descricao ";
		$res = pg_exec ($con,$sql) ;
		for ($x = 0 ; $x < pg_numrows($res) ; $x++ ) {
			$xsr_desc1 = pg_result ($res,$x,descricao);
			$xsr_id1   = pg_result ($res,$x,servico_realizado);

			$sql_idioma = "
							SELECT *
							FROM tbl_servico_realizado_idioma
							WHERE servico_realizado = $xsr_id1
							AND   UPPER(idioma)     = '$sistema_lingua'";
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) $xsr_desc1  = trim(@pg_result($res_idioma,0,descricao));

			echo "<option ";
			if($nova_tela == "success"){
				if ($xsr_id1 == $solucao_os) echo " SELECTED ";
			}else{
				if ($xsr_id1 == $identificacao1) echo " SELECTED ";
			}
			echo " value='$xsr_id1'>" ;
			echo $xsr_desc1 ;
			echo "</option>";
		}
		echo "</select>";

		echo "</td>";
		echo "<td> ";
		fecho("defeito", $con, $cook_idioma);
		echo "<br>";

		echo "<select name='causa_defeito1' id='causa_defeito1' size='1' class='frm' rel=\"block\">";
		echo "<option value=''></option>";
		$sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica ORDER BY codigo, descricao";
		$res = pg_exec ($con,$sql) ;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$xde_id1    = pg_result ($res,$i,causa_defeito);
			$xde_cod1   = pg_result ($res,$i,codigo);
			$xde_desc1  = pg_result ($res,$i,descricao);

			$sql_idioma = "
							SELECT *
							FROM tbl_causa_defeito_idioma
							WHERE causa_defeito = $xde_id1
							AND   UPPER(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) $xde_desc1 = trim(@pg_result($res_idioma,0,descricao));

			echo "<option ";
			if($nova_tela == "success"){
				if ($causa_defeito_os == $xde_id1 ) echo " selected ";
			}else{
				if ($causa_defeito1 == $xde_id1 ) echo " selected ";
			}
			echo " value='$xde_id1'>" ;
			echo "$xde_cod1 - $xde_desc1";
			echo "</option>\n";
		}
		echo "</select>";

		echo "</td>";
		echo "</tr>";
		//if($sistema_lingua) {


		echo "<tr>";
		echo "<td>";
		echo ($sistema_lingua =='ES') ? "Reparación" : "Reparo";
		echo "<br>";
		echo "<select name='defeito_constatado' size='1' class='frm' style='width: 200px;'>";
		if ($login_fabrica == 20) {
			$whr_defeito_constatado= " and tbl_defeito_constatado.defeito_constatado = 12845 ";
		}
		echo "<option selected></option>";
					$sql = "SELECT tbl_defeito_constatado.*
							FROM tbl_defeito_constatado
							JOIN tbl_produto_defeito_constatado
								ON  tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
								AND tbl_produto_defeito_constatado.produto in (
									SELECT produto FROM tbl_os WHERE os=$os
								)
							WHERE fabrica = $login_fabrica
							$whr_defeito_constatado
							ORDER BY tbl_defeito_constatado.descricao";

				$res = pg_exec ($con,$sql) ;

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

					$descricao_d = pg_result ($res,$i,descricao);

					//--=== Tradução para outras linguas ============================= Raphael HD:1212
					$sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma WHERE defeito_constatado = ".pg_result ($res,$i,defeito_constatado)." AND upper(idioma) = '$sistema_lingua'";

					$res_idioma = @pg_exec($con,$sql_idioma);
					if (@pg_numrows($res_idioma) >0) {
						$descricao_d  = trim(@pg_result($res_idioma,0,descricao));
					}
					//--=== Tradução para outras linguas ================================================

					echo "<option ";

					if($nova_tela == "success"){
						if ($defeito_constatado_os == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
					}else{
						if ($defeito_constatado == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
					}
					echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
					echo pg_result ($res,$i,codigo) ." - ". $descricao_d ;
					echo "</option>";
				}
			echo "</select>";
		echo "</td>";
		echo "</tr>";
		//}
	echo "</table>";
	echo "<table class='Tabela' width='700' celspancign='2'>";
	echo "<tbody>";

	if ($login_pais <> 'BR' and 1==2) { // HD 65904
		#Motivo da Troca 1
		echo "<tr>";
		echo "<th><input type='radio' name='bloqueio' value='1' onclick='javascript:bloqueia(1);'> $xmotivo1</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td><b>&nbsp;<BR><BR></b></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td>";

		echo "</td>";
		echo "</tr>";

		#Motivo da Troca 2

		echo "<tr>";
		echo "<th><input type='radio' name='bloqueio' value='2' onclick='javascript:bloqueia(2);'> $xmotivo2:</th>";
		echo "</tr>";
	}
	echo "<tr>";
	echo "<td>";
		echo "<table class='Tabela'>";
		echo "<tr>";
		echo "<td >";
		echo "<input type='hidden' name='bloqueio' value='2' >";
		fecho("informacoes.complementares",$con,$cook_idioma);
		echo ":<br>";
		if ($login_pais == 'BR') {
			echo " <font size='1'>(Descrever em detalhes o motivo da solicitação para evitar reprovação da solicitação)</font><br>";
		}
		echo "<textarea name='observacao2' id='observacao2' cols='80' rows='3' class='frm' rel=\"block\">$observacao2</textarea>";
		echo "</td>";
		if($tipo_atendimento==13 AND $login_fabrica <> 20){
			echo "<td>";
				echo "<INPUT TYPE='checkbox' NAME='cortesia_comercial' value='1'>";
				echo "Cortesia Comercial";
			echo "</td>";
		}
		echo "</tr>";
		echo "</table>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td align='center'>";
	echo "<input type='hidden' name='btn_acao' value=''>";
	if ($anexaNotaFiscal) {
		p_echo("&nbsp;");
		$temImg = temNF($os, 'count');

		if($temImg) {
			echo temNF($os, 'link');
			echo $include_imgZoom;
		}
		if (($anexa_duas_fotos and $temImg < LIMITE_ANEXOS) or $temImg == 0) {
			echo "<div id='foto_nf'>";//hd_chamado=2808833
				echo $inputNotaFiscal;
			echo "</div>";//hd_chamado=2808833
		}
		p_echo("&nbsp;");
	}

	if ($sistema_lingua=='ES') {
		echo "<img src='imagens/btn_guardar.gif' onclick=\"javascript: if (document.frm_troca.btn_acao.value == '' ) { document.frm_troca.btn_acao.value='gravar' ; document.frm_troca.submit() } else { alert ('Aguarde ') }\" ALT='Guardar itenes de la orden de servicio' border='0' style='cursor:pointer;'>";
	} else {
		echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_troca.btn_acao.value == '' ) { document.frm_troca.btn_acao.value='gravar' ; document.frm_troca.submit() } else { alert ('Aguarde ') }\" ALT='Gravar itens da Ordem de Serviço' border='0' style='cursor:pointer;'>";
	}
	echo "</td>";
	echo "</tr>";

	echo "</tbody>";
	echo "</table>";
	echo "</form>";
}else{
	echo "Nenhuma OS informada";
}
include "rodape.php";
?>
