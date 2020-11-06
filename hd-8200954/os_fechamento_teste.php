<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

#if($login_fabrica == 3){
#    echo "<h1>Programa em Manutenção</h1>";
#    exit;
#}


if ($login_posto=='7214') {
	header("location:os_fechamento_posto_intelbras.php");
}
/*	HD 135436(+Mondial) HD 193563 (+Dynacom)
	Para adicionar ou excluir uma fábrica ou posto, alterar só essa condição aqui,
	na os_consulta_lite, os_press, admin/os_press e na admin/os_fechamento, sempre nesta função
*/
#HD 311411 - Adicionado Fábrica 6 (TecToy)
function usaDataConserto($posto, $fabrica) {
	if ($posto == '4311' or (($fabrica <> 11 and $fabrica<>1) and $posto==6359) or
		in_array($fabrica, array(2,3,5,6,7,11,14,15,20,43,45)) or $fabrica >50) {
		return true;
	}
	return false;
}

if (isset($_POST['gravarPac']) AND isset($_POST['os'])){
	$gravarPac = trim($_POST['gravarPac']);
	$os = trim($_POST['os']);
	if (strlen($os)>0){
		$sql = "UPDATE  tbl_os_extra SET
				pac   = '$gravarPac'
			WHERE   tbl_os_extra.os   = $os ";
		$res  = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	exit;
}

if (isset($_POST['gravarDataconserto']) AND isset($_POST['os'])){
	$gravarDataconserto = trim($_POST['gravarDataconserto']);
	$os = trim($_POST['os']);
	if (strlen($os)>0){
		if(strlen($gravarDataconserto ) > 0) {
			$data = $gravarDataconserto.":00 ";
			$aux_ano  = substr ($data,6,4);
			$aux_mes  = substr ($data,3,2);
			$aux_dia  = substr ($data,0,2);
			$aux_hora = substr ($data,11,5).":00";
			$gravarDataconserto ="'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";
		} else {
			$gravarDataconserto ='null';
		}
		$erro = "";

		//hd 24714
		if ($gravarDataconserto != 'null'){
			$sql = "SELECT $gravarDataconserto > CURRENT_TIMESTAMP ";
			$res = @pg_query($con,$sql);
			if (pg_fetch_result($res,0,0) == 't'){
				$erro = traduz("data.de.conserto.nao.pode.ser.superior.a.data.atual", $con, $cook_idioma);
			}
		}

		//hd 24714
		if ($gravarDataconserto != 'null'){
			$sql = "SELECT $gravarDataconserto < tbl_os.data_abertura FROM tbl_os where os=$os";
			$res = @pg_query($con,$sql);
			if (pg_fetch_result($res,0,0) == 't'){
				$erro = traduz("data.de.conserto.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma);
			}

			if($login_fabrica == 7) {
				$sql = " SELECT $gravarDataconserto < hora_chegada_cliente from tbl_os_visita where os=$os order by hora_chegada_cliente asc limit 1;";
				$res = @pg_query($con,$sql);
				if (pg_fetch_result($res,0,0) == 't'){
					$erro = " A Data de Conserto não pode ser anterior a data de visita";
				}
			}
		}

		#HD 161176
		if($login_fabrica == 11){
			$sqlD = "SELECT tbl_os.os
					FROM tbl_os
					WHERE tbl_os.os    = $os
					AND tbl_os.fabrica = $login_fabrica
					AND tbl_os.defeito_constatado IS NOT NULL
					AND tbl_os.solucao_os         IS NOT NULL";
			$resD = @pg_query($con,$sqlD);
			$msg_erro = pg_errormessage($con);
			if(pg_num_rows($resD)==0){
				$erro = traduz("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma);
			}
		}

		if (strlen($erro) == 0) {
			$sql = "UPDATE tbl_os
					SET data_conserto = $gravarDataconserto
					WHERE os=$os
					AND fabrica = $login_fabrica
					AND posto = $login_posto";
			$res = @pg_query($con,$sql);
		} else {
			echo $erro;
		}

		if(strlen($erro) ==0){
			if ((($login_fabrica == 14) || ($login_fabrica == 43) || ($login_fabrica == 66))) {
				$novo_status_os = "CONSERTADO";
				include('os_email_consumidor.php');
			}
		}

		if(strlen($erro) ==0 and $login_fabrica==43){
			$observacao=$_POST['observacao_'.$i];
			$res=pg_query($con,$sql);
			$sqlm="SELECT tbl_os.sua_os          ,
						 tbl_os.consumidor_email,
						 tbl_os.serie           ,
						 tbl_posto.nome         ,
						 tbl_produto.descricao  ,
						 to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
					from tbl_os
					join tbl_produto using(produto)
					join tbl_posto on tbl_os.posto = tbl_posto.posto
					where tbl_os.os=$os
					AND tbl_os.fabrica = $login_fabrica";
			$resm=pg_query($con,$sqlm);
			$msg_erro .= pg_errormessage($con) ;
			$sua_osm           = trim(pg_fetch_result($resm,0,sua_os));
			$consumidor_emailm = trim(pg_fetch_result($resm,0,consumidor_email));
			$seriem            = trim(pg_fetch_result($resm,0,serie));
			$data_fechamentom  = trim(pg_fetch_result($resm,0,data_fechamento));
			$nomem             = trim(pg_fetch_result($resm,0,nome));
			$descricaom        = trim(pg_fetch_result($resm,0,descricao));
			if(strlen($consumidor_emailm) > 0){
				$nome         = "TELECONTROL";
				$email_from   = "helpdesk@telecontrol.com.br";
				$assunto      = "ORDEM DE SERVIÇO FECHADA";
				$destinatario = $consumidor_emailm;
				$boundary = "XYZ-" . date("dmYis") . "-ZYX";
				$mensagem = "A ORDEM DE SERVIÇO $sua_osm REFERENTE AO PRODUTO $descricaom COM NÚMERO DE SÉRIE $seriem FOI FECHADA PELO POSTO $nomem NO DIA $data_fechamentom.";
				$mensagem .= "<br>Observação do Posto: $observacao";
				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
				@mail($destinatario,$assunto,$mensagem,"From: ".$email_from." \n $body_top ");
			}
		}
	}
	exit;
}

$title = traduz("fechamento.de.ordem.de.servico", $con, $cook_idioma);
$layout_menu = 'os';
include "cabecalho.php";
include '_traducao_erro.php';


#------------ Fecha Ordem de Servico ------------#
$btn_acao = strtolower($_POST['btn_acao']);
if ($btn_acao == 'continuar') {

	$data_fechamento = $_POST['data_fechamento'];
	$qtde_os         = $_POST['qtde_os'];

	if (strlen($data_fechamento) == 0){
		$msg_erro = traduz("digite.a.data.de.fechamento", $con, $cook_idioma);
	}else if(strlen($msg_erro) == 0 ) { 
		$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
		if($xdata_fechamento > "'".date("Y-m-d")."'"){
			$msg_erro = traduz("data.de.fechamento.maior.que.a.data.de.hoje", $con, $cook_idioma);
		}

		//HD 9013, hd 36290 - retirado
		# OBS.: Como esta parte do código não está sendo utilizada não foi traduzida ainda
		#   Quando liberar, favor lembrar de traduzir
		if(($login_fabrica==1 or $login_fabrica ==70) and 1==2){
			$conta_ativo=0;
			for ($i = 0 ; $i < $qtde_os ; $i++) {
				$ativo_revenda      = trim($_POST['ativo_revenda_'. $i]);
				$consumidor_revenda = trim($_POST['consumidor_revenda_'. $i]);
				if($ativo_revenda=='t' and $consumidor_revenda=='R'){
					$conta_ativo++;
					$os            = trim($_POST['os_'. $i]);
				}
			}
//echo "$conta_ativo!=$qtde_os";

			if($conta_ativo!=$qtde_os and strlen($os)>0){

				$sql="SELECT codigo_posto,tbl_os.sua_os, tbl_os.os_numero
						from tbl_os
						join tbl_posto_fabrica on tbl_posto_fabrica.posto=tbl_os.posto and tbl_posto_fabrica.fabrica=$login_fabrica
						where tbl_os.os=$os
					      AND tbl_os.fabrica = $login_fabrica
						  AND tbl_os.consumidor_revenda='R'";
				$res = pg_query($con,$sql);
				$codigo_posto  = pg_fetch_result($res,0,codigo_posto);
				$sua_os        = pg_fetch_result($res,0,sua_os);
				$os_numero     = pg_fetch_result($res,0,os_numero);
				$sua_os        = substr ($sua_os,0,5);

				$sql = "SELECT count(*) As qtde_os_revenda
						FROM tbl_os
						WHERE os_numero = $os_numero
						AND   posto     = $login_posto
						AND   fabrica   = $login_fabrica
						AND   consumidor_revenda ='R' ";
				$res = pg_query($con,$sql);
				$qtde_os_revenda  = pg_fetch_result($res,0,qtde_os_revenda);

				if($qtde_os_revenda <> 1){
					$msg_erro="A O.S. DE REVENDA $codigo_posto$sua_os FOI EXPLODIDA PARA VÁRIOS PRODUTOS E O FECHAMENTO PODERÁ SER CONCLUÍDO SOMENTE QUANDO TODOS OS PRODUTOS DESSA O.S. FOREM ENTREGUES PARA O CLIENTE. NESSE CASO, SERÁ NECESSÁRIO EFETUAR O FECHAMENTO DE TODAS AS OS'S DE REVENDA COM ESSE MESMO NÚMERO.  ";
				}
			}
		}


		if ($login_fabrica == 1) { // HD 158420
			for ($i = 0 ; $i < $qtde_os ; $i++) {
				$ativo             = trim($_POST['ativo_'. $i]);
				$os                = trim($_POST['os_' . $i]);
				if($ativo =='t') {
					$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					# esta alteracao foi necessaria devido ao chamado 1419
					# Na verdade o valida os item deve ser realizado quando digitar o item, mas
					# quando a Fabiola/Silvania questionou sobre OS com item que não constavam na
					# lista básica, o Tulio começou a validar os itens no fechamento tambem.
					# começou a causar problemas com o Type, e substituição de peças.
					if (strpos ($msg_erro,"na lista b") > 0 and strpos ($msg_erro,"m o TYPE desta") > 0) $msg_erro = '';
					if (strpos ($msg_erro,"Referência") > 0 and strpos ($msg_erro,"mudou para") > 0) $msg_erro = '';
					if (strpos ($msg_erro,"obsoleta") !==false) $msg_erro = "";
				}
			}
		}

		if (strlen($msg_erro) == 0){
			// HD  27468
			if($login_fabrica ==1 or $login_fabrica ==7 or $login_fabrica == 11){
				$res = pg_query ($con,"BEGIN TRANSACTION");
			}
			
			if($login_fabrica == 11){ #HD 346804
				for ($y = 0 ; $y < $qtde_os ; $y++) {
				$ativo_os_nf = trim($_POST['ativo_' . $y]);

					if($ativo_os_nf == 't'){
						$array_os[] = trim($_POST['os_' . $y]);
					}
				}
			}

			$linha_erro = array(); // HD 101630
			for ($i = 0 ; $i < $qtde_os ; $i++) {
				$erro = "";
				$ativo             = trim($_POST['ativo_'. $i]);
				$os                = trim($_POST['os_' . $i]);
				$serie             = trim($_POST['serie_'. $i]);
				$serie_reoperado   = trim($_POST['serie_reoperado_'. $i]);
				$nota_fiscal_saida = trim($_POST['nota_fiscal_saida_'. $i]);
				$data_nf_saida     = trim($_POST['data_nf_saida_'. $i]);
				$motivo_fechamento = trim($_POST['motivo_fechamento_'. $i]);
				

				if($login_fabrica == 59 && $ativo =='t')  { // HD 337877
					$sql = 'SELECT	defeito_constatado
							FROM	tbl_os
							WHERE	os = ' . $os . '
							AND		fabrica = ' . $login_fabrica . '
							AND		defeito_constatado IS NOT NULL;';
					//echo nl2br($sql);
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) == 0) {
						$nao_fechou .= 'A OS '.$os.' não pode ser fechada, cadastre um defeito constatado.<br />';
						continue;
					}
				}
				
				if($login_fabrica == 74){ //HD 403619
					$sql = 'SELECT	defeito_constatado,
									defeito_reclamado
								FROM	tbl_os
								WHERE	os = ' . $os . '
								AND		fabrica = ' . $login_fabrica ;
						//echo nl2br($sql);
					$res = pg_query($con,$sql);
					$defeito_constatado_aux = pg_result($res,0,defeito_constatado);
					$defeito_reclamado_aux = pg_result($res,0,defeito_reclamado);

					if(empty($defeito_constatado_aux)){
						$erro .= 'A OS '.$os.' não pode ser fechada, cadastre um defeito constatado.<br />';
					}

					if(empty($defeito_reclamado_aux)){
						$erro .= 'A OS '.$os.' não pode ser fechada, cadastre um defeito reclamado.<br />';
					}
				}

				//die ($nao_fechou);
				if($login_fabrica==2) {
					$pac = trim($_POST['pac_'. $i]);
				}

				if($login_fabrica==1) {
					$ativo_revenda             = trim($_POST['ativo_revenda_'. $i]);
				}

				if ($login_fabrica == 11 ) {
					$sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = $os AND tbl_os.fabrica = $login_fabrica";
					$res = pg_query($con,$sql);
					$consumidor_revenda = pg_fetch_result($res,0,consumidor_revenda);
				}

				//hd 24714
				if($ativo =='t' and strlen($erro) == 0) {
					$sql = "SELECT $xdata_fechamento < tbl_os.data_abertura FROM tbl_os where os=$os AND tbl_os.fabrica = $login_fabrica";
					$res = @pg_query($con,$sql);
					if (@pg_fetch_result($res,0,0) == 't'){
						$erro = traduz("data.de.fechamento.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma); /*"Data de fechamento não pode ser anterior a data de abertura.";*/
					}
				}

				if($login_fabrica == 3 AND $ativo == 't' and strlen($erro) == 0){

					$sql = "SELECT tbl_os_item.os_item, tbl_os_item.pedido, tbl_os_item.peca, tbl_os_item.qtde
							FROM tbl_os_produto
							JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
							JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
							LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
							WHERE tbl_os_produto.os = $os
							AND tbl_servico_realizado.gera_pedido IS TRUE
							AND  tbl_faturamento_item.faturamento_item IS NULL
							LIMIT 1";
					$res = @pg_query($con,$sql);

					$cancelado = "";

					//HD 6477
					if(pg_num_rows($res)>0) {
						$xpedido = pg_fetch_result($res,0,pedido);
						$xpeca   = pg_fetch_result($res,0,peca);
						$xqtde   = pg_fetch_result($res,0,qtde);
						if(strlen($xpedido) > 0) {
							$sqlC = "SELECT os
								FROM tbl_pedido_cancelado
								WHERE pedido  = $xpedido
								AND   peca    = $xpeca
								AND   qtde    = $xqtde
								AND   os      = $os
								AND   fabrica = $login_fabrica";
							$resC = @pg_query($con, $sqlC);

							if(pg_num_rows($resC)>0) $cancelado = pg_result($resC,0,0);
						}
					}

					if(pg_num_rows($res)>0 and strlen($cancelado)==0 and strlen($motivo_fechamento)==0){
						$erro .= traduz("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os", $con, $cook_idioma); /*"OS com peças pendentes, favor informar o motivo do fechamento<BR>"; */
						$xmotivo_fechamento = "null";
						array_push($linha_erro,$os);
					}else{
						$xmotivo_fechamento = "'$motivo_fechamento'";
					}
				}else{
					$xmotivo_fechamento = "null";
				}

				if($login_fabrica==3 AND $login_posto==6359){// hd 16018
					$sql = "SELECT aprovado
							FROM tbl_os_atendimento_domicilio
							JOIN tbl_os USING(os)
							WHERE tbl_os.posto   = $login_posto
							AND   tbl_os.fabrica = $login_fabrica
							AND   tbl_os.os      = $os";
					$res = pg_query($con, $sql);
					if(pg_num_rows($res)>0){
						$aprovado = pg_fetch_result($res, 0, aprovado);
						if($aprovado=='f'){
							$erro .= traduz("os.com.atendimento.em.domicilio,.aguardando.aprovacao.do.fabricante", $con, $cook_idioma); /*"OS com atendimento em domicilio, aguardando aprovação do fabricante."; */
						}
					}
				}

				// Verifica se o status da OS for 62 (intervencao da fabrica) // Fábio 02/01/2007
				//Acrescentado $sua_os chamado= 2699 erro recebido no e-mail.
				if ( (in_array($login_fabrica,array(1,3,6,11,51))) AND ($ativo == 't' or $ativo_revenda=='t') and strlen($erro) == 0 ) {
					$sql = "SELECT  status_os, sua_os, posto
							FROM    tbl_os_status
							JOIN tbl_os using(os)
							WHERE   tbl_os_status.os = $os
							AND tbl_os.fabrica = $login_fabrica
							AND tbl_os_status.status_os IN (72,73,62,64,65,87,88,116,117)
							ORDER BY tbl_os_status.data DESC
							LIMIT 1";
					$res = @pg_query($con,$sql);
					if (pg_num_rows($res) > 0) {
						$os_intervencao_fabrica = trim(pg_fetch_result($res,0,status_os));
						$sua_os                 = trim(pg_fetch_result($res,0,sua_os));
						$posto                  = trim(pg_fetch_result($res,0,posto));
						if ($login_fabrica==1){
							$sql2 =	"	SELECT codigo_posto
										FROM tbl_posto_fabrica
										WHERE posto = $posto
										AND fabrica = $login_fabrica";
							$res2 = @pg_query($con,$sql2);
							if (pg_num_rows($res2) > 0) {
								$cod_posto = trim(pg_fetch_result($res2,0,codigo_posto));
								$sua_os = $cod_posto.$sua_os;
							}
						}
						if ($os_intervencao_fabrica == '65') {
							$erro .= traduz("os.%.esta.em.reparo.na.assistencia.tecnica.da.fabrica.nao.pode.ser.fechada", $con, $cook_idioma, $sua_os); /*"OS $sua_os está em reparo na assistência técnica da Fábrica. Não pode ser fechada."; */
						}
						if (in_array($os_intervencao_fabrica,array('62','72','87','116'))) {
							if ($login_fabrica ==51 AND $os_intervencao_fabrica == '62') { // HD 59408
								$sql = " INSERT INTO tbl_os_status
										(os,status_os,data,observacao)
										VALUES ($os,64,current_timestamp,'OS Fechada pelo posto')";
								$res = pg_query($con,$sql);
								$erro .= pg_errormessage($con);

								$sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
										WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
										AND   tbl_os_produto.os = $os";
								$res = pg_query($con,$sql);
								$erro .= pg_errormessage($con);

								$sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
										WHERE tbl_os.os = $os";
								$res = pg_query($con,$sql);
								$erro .= pg_errormessage($con);
							}else{
								$erro .= traduz("os.%.esta.em.intervencao.nao.pode.ser.fechada", $con, $cook_idioma, $sua_os); /*"OS $sua_os está em intervenção. Não pode ser fechada."; */
							}
						}
					}
				}

				if ($login_fabrica == 86) {
					$sql_intervencao = "SELECT status_os
							FROM  tbl_os_status
							WHERE os = $os
							AND   status_os IN (62, 64)
							ORDER BY data DESC
							LIMIT 1";
					$res_intervencao = pg_query($con, $sql_intervencao);
					$os_intervencao_fabrica = pg_result($res_intervencao, 0, 'status_os');
					if($os_intervencao_fabrica == '62'){
						$erro .= traduz("os.%.esta.em.intervencao.nao.pode.ser.fechada", $con, $cook_idioma, $sua_os); /*"OS $sua_os está em intervenção. Não pode ser fechada."; */
					}
				}

				if ($login_fabrica == 52) {
					$sqlAd =	"SELECT interv_reinc.os
							FROM (
								SELECT
									ultima_reinc.os,
										(SELECT status_os FROM tbl_os_status WHERE fabrica_status= $login_fabrica AND tbl_os_status.os = ultima_reinc.os AND status_os IN (98,99,100,101) ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
										FROM (SELECT DISTINCT os FROM tbl_os_status WHERE fabrica_status= $login_fabrica AND status_os IN (98,99,100,101) ) ultima_reinc
										) interv_reinc
								WHERE interv_reinc.ultimo_reinc_status IN (98) and interv_reinc.os = $os";

					$resAd = pg_query($con, $sqlAd);
					if(pg_num_rows($resAd)>0){
							$erro .= traduz("os.%.esta.em.intervencao.nao.pode.ser.fechada", $con, $cook_idioma, $sua_os); /*"OS $sua_os está em intervenção. Não pode ser fechada."; */
					}
				}

				if($login_fabrica==3 AND ($ativo == 't' or $ativo_revenda=='t')){ //HD 56464 - HD 92000
					$sqlAd = "SELECT status_os
							FROM  tbl_os
							JOIN  tbl_os_status USING(os)
							WHERE os=$os
							AND tbl_os.fabrica = $login_fabrica
							AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
							ORDER BY data DESC LIMIT 1";
					$resAd = pg_query($con, $sqlAd);
					if(pg_num_rows($resAd)>0){
						$status_os = pg_fetch_result($resAd, 0, status_os);
						if ($status_os == 120 || $status_os == 122 || $status_os == 126){
							$erro .= traduz("auditoria.de.os.aberta.a.mais.de.90.dias.os.nao.ser.alterada", $con, $cook_idioma, $sua_os);
							/*"Auditoria de OS aberta a mais de 90 dias, OS nao ser alterada."; */
						} else if ($status_os == 140 || $status_os == 141 || $status_os == 143) {
							$erro .= traduz("auditoria.de.os.aberta.a.mais.de.45.dias.os.nao.ser.alterada", $con, $cook_idioma, $sua_os);
							/*"Auditoria de OS aberta a mais de 45 dias, OS nao ser alterada."; */
						}
					}
				}

				$xdata_nf_saida = (strlen($data_nf_saida) == 0) ? 'null' : fnc_formata_data_pg ($data_nf_saida) ;
				$xnota_fiscal_saida = (strlen($nota_fiscal_saida) == 0) ? 'null' : "'".$nota_fiscal_saida."'";

				if ($ativo == 't' or $ativo_revenda=='t'){
					$xserie_reoperado = "null";
					if($login_fabrica == 15){
						//7667 Gustavo 14/2/2008
						$xserie_reoperado = (strlen($serie_reoperado) == 0) ? "null" : "'".$serie_reoperado."'";

						$sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = $os ";
						$res = pg_query($con,$sql);
						$con_rev = pg_fetch_result($res,0,consumidor_revenda);
						if($con_rev == 'R'){
							if($xnota_fiscal_saida == 'null'){
								$erro .= traduz("preencha.o.campo.nota.fiscal.de.saida", $con, $cook_idioma); /*"Preencha o campo Nota Fiscal de Saída.";*/
							}
							if($xdata_nf_saida == 'null'){
								$erro .= traduz("preencha.o.campo.nota.fiscal.de.saida", $con, $cook_idioma); /*" Preencha o campo Data da Nota Fiscal de Saída.";*/
							}
						}
					}

					$xserie= 'null';
					if($login_fabrica == 30 or $login_fabrica ==85){
						//11318 - Igor 15/2/2008
						if (strlen($serie) == 0){
							//$erro .= traduz("preencha.o.campo.numero.de.serie", $con, $cook_idioma); /*"Preencha o Número de Série!";*/
						}else{
							$xserie= "'".$serie."'";
						}
					}

					//hd 6701 - nao deixar o posto 019876-IVO CARDOSO fechar sem lancar NF
					if($login_fabrica == 6 AND $login_posto == 4260){
						if($xnota_fiscal_saida == 'null' or strlen($xnota_fiscal_saida) == 0){
							$erro .= traduz("preencha.o.campo.nota.fiscal.de.saida", $con, $cook_idioma); /*"Preencha o campo Nota Fiscal de Saída.";*/
						}
						if($xdata_nf_saida == 'null'){
							$erro .= traduz("preencha.o.campo.data.nota.fiscal.de.saida", $con, $cook_idioma); /*" Preencha o campo Data da Nota Fiscal de Saída.";*/
						}
					}

					//HD 281072: Como foi retirado da fn_finaliza_os_suggar a validação da OS reincidente, estou incluindo aqui
					if ($login_fabrica == 24 && strlen($erro) == 0) {
						$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
						$res1 = @pg_query($con,$sql);
						$erro = pg_errormessage($con);
					}

					if (strlen ($erro) == 0) {
						// HD 27468
						if($login_fabrica <> 1 and $login_fabrica <> 7 and $login_fabrica <> 11){
							$res = pg_query ($con,"BEGIN TRANSACTION");
						}

						$upd_serie = ($login_fabrica == 30 or $login_fabrica == 85) ? "serie = '$serie'," : "";

						$erro = $msg_erro;
						if (strlen ($erro) == 0) {
							if ($login_fabrica==1){
								$sql = "UPDATE  tbl_os SET
												data_fechamento   = $xdata_fechamento
										WHERE   tbl_os.os         = $os";
							}else{
								
								if($login_fabrica == 11 and strlen($erro) == 0){ #HD 96191
									$sql_nf = "SELECT sua_os,os FROM tbl_os
									WHERE fabrica = $login_fabrica
									AND posto = $login_posto
									AND nota_fiscal_saida = $xnota_fiscal_saida
									ORDER BY finalizada";

									$res_nf = pg_query($con,$sql_nf);
									if (pg_num_rows($res_nf) > 0){
										$sua_os_nf = " ";
										$nf_ja_utilizada = false;

										for ($x = 0 ; $x < pg_num_rows($res_nf); $x++) {
											$os_nf = trim(pg_fetch_result($res_nf,$x,'os'));
											$sua_os_nf = trim(pg_fetch_result($res_nf,$x,'sua_os'));

											if(!in_array($os_nf,$array_os)){
												$sua_os_utilizadas .= $sua_os_nf.'<br />';
												$nf_ja_utilizada = true;
											}

										}

										if($nf_ja_utilizada == true){
											$erro .= "Nota Fiscal já utilizada para devolução da(s) OS<br> ".$sua_os_utilizadas;
										}
									}
								}

								#HD 150828
								$os_troca = false;
								if ($login_fabrica == 11){
									$sql_troca = "SELECT os FROM tbl_os_troca WHERE os = $os";
									$res_troca = pg_query($con,$sql_troca);
									if (pg_num_rows($res_troca) > 0){
										$os_troca = true;
									}
								}

								$sql = "UPDATE  tbl_os SET
												data_fechamento   = $xdata_fechamento  ,
												$upd_serie
												serie_reoperado   = $xserie_reoperado   ,
												nota_fiscal_saida = $xnota_fiscal_saida,
												data_nf_saida     = $xdata_nf_saida
										WHERE   tbl_os.os         = $os";

								$sql_conserto = "SELECT data_conserto from tbl_os where os = $os";

								$res_conserto = pg_query($con,$sql_conserto);

								$data_conserto = pg_fetch_result($res_conserto,0,data_conserto);

								#HD 163061 - OS de troca
								if ( strlen($data_conserto)==0 AND $os_troca == false) {
									$data_conserto = explode("'",$xdata_fechamento);
									$data_conserto = $data_conserto[1];
									$hora_conserto = date('H:i:s');
									$sql_conserto = "UPDATE tbl_os set data_conserto = '$data_conserto $hora_conserto' where os = $os";
									$res_conserto = pg_query($con,$sql_conserto);
								}

							}

							$res  = @pg_query ($con,$sql);
							$erro .= pg_errormessage ($con);
							if($login_fabrica==3){
								$sql = "UPDATE  tbl_os_extra SET
												obs_fechamento   = $xmotivo_fechamento
										WHERE   tbl_os_extra.os         = $os";
								$res  = @pg_query ($con,$sql);
								$erro .= pg_errormessage ($con);
							}
						}

						//HD 204146: Fechamento automático de OS
						if ($login_fabrica == 3) {
							$sql = "UPDATE tbl_os SET sinalizador=20 WHERE os=$os AND sinalizador=18";
							@$res_sinalizador = pg_query($con, $sql);
							$erro = pg_errormessage($con);

							if ($erro) {
								$msg_erro = "Erro no sistema, contate o HelpDesk";
							}
						}

						if (strlen ($erro) == 0) {
							$sql = "SELECT fn_finaliza_os_384011($os, $login_fabrica)";
							$res = @pg_query ($con,$sql);
							$msg_erro = pg_errormessage($con);
							$erro = pg_errormessage($con);
						}

						if (strlen ($erro) == 0 and ($login_fabrica==1 Or $login_fabrica==24 or($login_fabrica == 3 and $login_posto == 6359))) {
							$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
							$res = @pg_query ($con,$sql);
							$erro = pg_errormessage($con);
						}

						//HD 11082 17347
						if((strlen($erro) ==0 and $login_fabrica==11 and $login_posto==14301)){
//							or(strlen($erro) ==0 and $login_fabrica==43)){
							$observacao=$_POST['observacao_'.$i];
//							echo $sql="INSERT INTO tbl_os_interacao (os,comentario) values ($os,'$observacao')";
							$res=pg_query($con,$sql);

							$sqlm="SELECT tbl_os.sua_os          ,
										 tbl_os.consumidor_email,
										 tbl_os.serie           ,
										 tbl_posto.nome         ,
										 tbl_produto.descricao  ,
										 to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
									from tbl_os
									join tbl_produto using(produto)
									join tbl_posto on tbl_os.posto = tbl_posto.posto
									where os=$os
									  AND tbl_os.fabrica = $login_fabrica";
							$resm=pg_query($con,$sqlm);
							$msg_erro .= pg_errormessage($con) ;

							$sua_osm           = trim(pg_fetch_result($resm,0,sua_os));
							$consumidor_emailm = trim(pg_fetch_result($resm,0,consumidor_email));
							$seriem            = trim(pg_fetch_result($resm,0,serie));
							$data_fechamentom  = trim(pg_fetch_result($resm,0,data_fechamento));
							$nomem             = trim(pg_fetch_result($resm,0,nome));
							$descricaom        = trim(pg_fetch_result($resm,0,descricao));

							if(strlen($consumidor_emailm) > 0){
								$nome         = "TELECONTROL";
								$email_from   = "helpdesk@telecontrol.com.br";
								$assunto      = "ORDEM DE SERVIÇO FECHADA";
								$destinatario = $consumidor_emailm;
								$boundary = "XYZ-" . date("dmYis") . "-ZYX";
								$mensagem = "A ORDEM DE SERVIÇO $sua_osm REFERENTE AO PRODUTO $descricaom COM NÚMERO DE SÉRIE $seriem FOI FECHADA PELO POSTO $nomem NO DIA $data_fechamento.";
								$mensagem .= "<br>Observação do Posto: $observacao";
								$body_top = "--Message-Boundary\n";
								$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
								$body_top .= "Content-transfer-encoding: 7BIT\n";
								$body_top .= "Content-description: Mail message body\n\n";
								@mail($destinatario,$assunto,$mensagem,"From: ".$email_from." \n $body_top ");
							}
						}
						if (strlen ($erro) > 0) {
							//echo $erro;
							// HD 27468
							if($login_fabrica <> 1 and $login_fabrica <> 7){
								array_push($linha_erro,$os);
								if($login_fabrica <> 11){
									$res = @pg_query ($con,"ROLLBACK TRANSACTION");
								}
								/* HD 175123 */
								$msg_erro = $erro;
								$msg_ok	 = "";
								$erro = '';
							}

							if($login_fabrica == 1) {
								array_push($linha_erro,$os);
								$msg_erro = $erro;
								$msg_ok	 = "";
								$erro = '';
								break;
							}
						}else{
							if($login_fabrica == 96){ //HD 399700
								
								$sql = "SELECT tbl_cliente_admin.email
												  FROM tbl_os
												  JOIN tbl_hd_chamado USING(hd_chamado)
												  JOIN tbl_cliente_admin ON tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin
												WHERE tbl_os.os = $os";
								$res = pg_query($con,$sql);
								if(pg_num_rows($res) > 0){
									$email = pg_result($res,0,0);
								}

								if(!empty($email)){
									$nome         = "TELECONTROL";
									$email_from   = "helpdesk@telecontrol.com.br";
									$assunto      = "ORDEM DE SERVIÇO FECHADA";
									$destinatario = $email;
									$boundary = "XYZ-" . date("dmYis") . "-ZYX";
									$mensagem = "Prezado,<br /> <br />A Ordem De Serviço {$os} foi fechada pelo Posto Autorizado no dia {$data_fechamento}.<br /><br />--<br />Att,<br />Suporte Telecontrol<br /><b>Essa é uma mensagem automática, não responda este e-mail.</b>";
									
									$body_top = "--Message-Boundary\n";
									$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
									$body_top .= "Content-transfer-encoding: 7BIT\n";
									$body_top .= "Content-description: Mail message body\n\n";
									@mail($destinatario,$assunto,$mensagem,"From: ".$email_from." \n $body_top ");
								}
							}

							// HD 27468
							if($login_fabrica <> 1 and $login_fabrica <> 7 and $login_fabrica <> 11){
								$res = @pg_query ($con,"COMMIT TRANSACTION");
							}
							$data_fechamento   = "";
							$serie             = "";
							$serie_reoperado   = "";
							$nota_fiscal_saida = "";
							$data_nf_saida     = "";
							$msg_ok = 1;
						}
					} else{
						$msg_erro = $erro;
					}
				}//fim if

			}//for

			if($login_fabrica == 11){ #HD 96191
				if (strlen($msg_erro) >0 or strlen($erro) >0) {
					$res = @pg_query ($con,"ROLLBACK TRANSACTION");
					$msg_ok = "";
				}else{
					$res = @pg_query ($con,"COMMIT TRANSACTION");
				}
			}

			// HD 27468
			if (($login_fabrica ==1 or $login_fabrica ==7) and strlen($msg_erro) == 0){

				//HD 36209 - Verifica se todas as OSs revenda que tem o mesmo "pai" foram fechadas
				for ($i = 0 ; $i < $qtde_os ; $i++) {
					$os                = trim($_POST['os_' . $i]);
					$ativo_revenda     = trim($_POST['ativo_revenda_'. $i]);

					if ($ativo_revenda == 't') {
						$sqlr = "SELECT tbl_os.os, substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')) as sua_os, tbl_posto_fabrica.codigo_posto
								FROM tbl_os
								JOIN (
									SELECT os, fabrica, posto, os_numero
									FROM tbl_os
									WHERE os = $os
									AND fabrica = $login_fabrica
									AND posto = $login_posto
								) x ON tbl_os.fabrica = x.fabrica
								AND tbl_os.posto = x.posto
								AND tbl_os.excluida IS NOT TRUE
								AND tbl_os.consumidor_revenda = 'R'
								AND tbl_os.os_numero = x.os_numero
								AND tbl_os.os <> x.os
								AND data_fechamento IS NULL
								JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $login_posto";
						$resr = pg_query ($con,$sqlr);

						if (pg_num_rows($resr)) {
							$sua_os       = pg_fetch_result($resr,0,sua_os);
							$codigo_posto = pg_fetch_result($resr,0,codigo_posto);
							$numero_os = $codigo_posto.$sua_os;
							$msg_erro= traduz ("a.os.de.revenda.%.foi.explodida.para.varios.produtos.e.o.fechamento.podera.ser.concluido.somente.quando.todos.os.produtos.dessa.os.forem.entregues.para.o.cliente.nesse.caso,.sera.necessario.efetuar.o.fechamento.de.todas.as.oss.de.revenda.com.esse.mesmo.numero.", $con, $cook_idioma,$numero_os);
							/*"A O.S. DE REVENDA $codigo_posto$sua_os FOI EXPLODIDA PARA VÁRIOS PRODUTOS E O FECHAMENTO PODERÁ SER CONCLUÍDO SOMENTE QUANDO TODOS OS PRODUTOS DESSA O.S. FOREM ENTREGUES PARA O CLIENTE. NESSE CASO, SERÁ NECESSÁRIO EFETUAR O FECHAMENTO DE TODAS AS OS'S DE REVENDA COM ESSE MESMO NÚMERO.";*/
							$msg_ok="";
							break;
						}
					}
				}

				if (strlen($msg_erro) >0 or strlen($erro) >0) {
					$res = @pg_query ($con,"ROLLBACK TRANSACTION");
					$msg_ok = "";
				}else{
					$res = @pg_query ($con,"COMMIT TRANSACTION");

					//Envia e-mail para o consumidor, avisando da abertura da OS
					//HD 150972
					if (($login_fabrica == 14) || ($login_fabrica == 43) || ($login_fabrica == 66))
					{
						$novo_status_os = "FECHADA";
						include('os_email_consumidor.php');
					}
				}
			} //hd 45142
			 else if (($login_fabrica ==1 or $login_fabrica ==7) and strlen($msg_erro) > 0){
				$res = @pg_query ($con,"ROLLBACK TRANSACTION");
				$msg_ok = "";
			}
		} // if msg_erro
	}//if
}

?>

<script language="JavaScript">
var checkflag = "false";
var filtro_status = -1;
function SelecionaTodos(field) {
    
	if($(".main").is(":checked")){
		$("table.tabela_resultado tbody tr > td  > input[type=checkbox]").attr('checked',false);
		if(filtro_status >= 0){
			$("tr[rel=status_"+filtro_status+"] > td > input[type=checkbox]").attr('checked','checked');
		}else{
			$("table.tabela_resultado tbody tr > td  > input[type=checkbox]").attr('checked','checked');
		}
	}else{
		$("input[type=checkbox].os").attr("checked",false);
	}
}
</script>
 <link rel="stylesheet" href="js/jquery.tooltip.css" />
 <script src="js/jquery-1.3.2.js"></script>
 <script src="js/jquery.maskedinput.js"></script>
 <script src="js/jquery.tooltip.js"           type="text/javascript"></script>
 <script type="text/javascript" src="js/jquery.corner.js"></script>
 <script type="text/javascript">
 $(document).ready(function(){
   $(".tabela_resultado tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
   //$(".tabela_resultado tr:even").addClass("alt");
   $(".tabela_resultado tr[rel='sem_defeito']").addClass("sem_defeito");
   $(".tabela_resultado tr[rel='mais_30']").addClass("mais_30");
   $(".tabela_resultado tr[rel='erro_post']").addClass("erro_post");
   });

	$(document).ready(function(){
		$(".subtitulo").corner("bevel");
		$(".content").corner("dog 10px");

	});
	function formata_data(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}
function mostraDados(peca){
	if (document.getElementById('dados_'+peca)){
		var style2 = document.getElementById('dados_'+peca);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}

 </script>
 <script type="text/javascript" src="js/niftycube.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("input[rel='data_conserto']").maskedinput("99/99/9999 99:99");
		$("input[name='data_fechamento']").maskedinput("99/99/9999");
	});
</script>

<script type="text/javascript">

$().ready(function() {

	var listar = $('#campo_aux').val();
	
	$("input[rel='data_conserto']").blur(function(){
		var campo = $(this);
			$.post('<? echo $PHP_SELF; ?>',
				{
					gravarDataconserto : campo.val(),
					os: campo.attr("alt")
				},
				//24714
				function(resposta) {
					if (resposta.length > 0){
						alert(resposta);
						campo.val('');
					}
				}
			);
	});

	$("input[rel='pac']").blur(function(){
		var campo = $(this);
			$.post('<? echo $PHP_SELF; ?>',
				{
					gravarPac : campo.val(),
					os: campo.attr("alt")
				},
				//24714
				function(resposta) {
					if (resposta.length > 0){
						alert(resposta);
						campo.val('');
					}
				}
			);
	});

});

//HD 234532
function filtrar(status){

	if(status >= 0){
	
		$("table.tabela_resultado tbody tr").hide();
		$("tr[rel=status_"+status+"]").show();

		if($(".main").is(":checked")){
			$("table.tabela_resultado tbody tr > td > input[type=checkbox]").attr('checked',false);	
			$("tr[rel=status_"+status+"] > td > input[type=checkbox]").attr('checked','checked');		
		}

	}else{

		$("table.tabela_resultado tbody tr").show();
		
		if($(".main").is(":checked")){
			$("table.tabela_resultado tbody tr > td > input[type=checkbox]").attr('checked','checked');		
		}
	}
	
	filtro_status = status;

}

</script>


<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>
<style type="text/css">
	table.sample {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
	}
	table.sample th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}
	table.sample td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.sample tr.alt td {
		background: #ecf6fc;
	}
	table.sample tr.over td {
		background: #bcd4ec;
	}
	table.sample tr.clicado td {
		background: #FF9933;
	}
	table.sample tr.sem_defeito td {
		background: #FFCC66;
	}
	table.sample tr.mais_30 td {
		background: #FF0000;
	}
	table.sample tr.erro_post td {
		background: #99FFFF;
	}

	.titulo {
		background:#7392BF;
		width: 650px;
		text-align: center;
		padding: 4px 4px; /* padding greater than corner height|width */
	/*	margin: 1em 0.25em;*/
		font-size:12px;
		color:#FFFFFF;
	}
	.titulo h1 {
		color:white;
		font-size: 120%;
	}

	.subtitulo {
		background:#FCF0D8;
		width: 600px;
		text-align: center;
		padding: 2px 2px; /* padding greater than corner height|width */
		margin: 10px auto;
		color:#392804;
	}
	.subtitulo h1 {
		color:black;
		font-size: 120%;
	}

	.content {
		background:#CDDBF1;
		width: 600px;
		text-align: center;
		padding: 5px 30px; /* padding greater than corner height|width */
		margin: 1em 0.25em;
		color:#000000;
		text-align:left;
	}
	.content h1 {
		color:black;
		font-size: 120%;
	}

	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}
	.Conteudo {
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
	}
	.fechamento{
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 9px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #C50A0A;
	}
	.fechamento_content{
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 9px;
		color: #FFFFFF;
		background-color: #F9DBD0;
	}
	.Relatorio {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.Relatorio th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}
	.Relatorio td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}
	
	.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
	}
	
	.titulo_coluna{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
	}
	
	.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
	}
	
	.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
	}
	
</style>
<?

if($sistema_lingua ) $msg_erro = traducao_erro($msg_erro,$sistema_lingua);
if (strlen ($msg_erro) > 0 || strlen($nao_fechou)>0) {
	//echo $msg_erro;
	if (strpos ($msg_erro,"data_fechamento_anterior_abertura") > 0) $msg_erro = traduz("data.de.fechamento.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma);
	if (strpos ($msg_erro,"Bad date external ") > 0) $msg_erro = traduz("data.de.fechamento.invalida", $con, $cook_idioma); /*"Data de fechamento inválida";*/
	if (strpos ($msg_erro,'"tbl_os" violates check constraint "data_fechamento"') > 0) $msg_erro = traduz("data.de.fechamento.invalida", $con, $cook_idioma); /*"Data de fechamento inválida";*/
	if (strpos ($msg_erro,"É necessário informar a solução na OS") > 0) $msg_solucao = 1;
	if (strpos ($msg_erro,"Para esta solução é necessário informar as peças trocadas") > 0) $msg_solucao = 1;
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		
		#HD 425978
		if($login_fabrica != 95){
			$erro = traduz("foi.detectado.o.seguinte.erro", $con, $cook_idioma); /*"Foi detectado o seguinte erro:*/
		}
		
		$msg_erro = substr($msg_erro, 6);
	}
	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	if($erro == $msg_erro) {
		$erro = "";
	}
?>
<? } ?>
<br />
<?php if (strlen ($msg_erro) > 0 || strlen($nao_fechou)>0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td align="center" class='msg_erro'>
		<? echo $erro." ".$msg_erro . $nao_fechou; ?>
	</td>
</tr>
</table>
<?php } ?>

<? if (strlen ($msg_ok) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class='sucesso'>
<tr>
	<td height="27" valign="middle" align="center">
	<?	echo "<font size='2'><b>";
		fecho ("os.fechada.com.sucesso", $con, $cook_idioma);
		echo "</b></font>";
	?>
	</td>
</tr>
</table>
<? } ?>

<?
if(strlen($msg_erro) > 0){

	#HD 425978
	if($login_fabrica != 95){
	
		echo "<br>";
		echo "<div align='left' style='position: relative; left: 10'>";
		echo "<table width='700' height=15 border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='15' bgcolor='#FF0000'>&nbsp;</td>";
		echo "<td align='left'><font size=1><b>&nbsp;";
		fecho ("erro.na.os", $con, $cook_idioma);
		/*ERRO NA OS*/
		echo "</b></font></td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";
	}
	
}

$sua_os       = trim($_POST['sua_os']);
$codigo_posto = $_POST['codigo_posto'];
if(strlen($sua_os ) == 0 AND $login_fabrica == 15){
	$sua_os       = trim($_GET['sua_os']);
}

$colspan = ($login_fabrica==11) ? 2 : null;
$width = ($login_fabrica==11) ? "50%" : null;

?>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<form name='frm_os_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<table width="700" align="center" border="0" cellspacing="0" cellpadding="0" class='formulario'>

	<tr  height="30">
		<td align="center" class="titulo_tabela" colspan='<?=$colspan?>'>
		<? 
			if($cook_idioma == "ES"){
				fecho ("selecione.os.parametros.para.a.pesquisa", $con, $cook_idioma); 
			}
			else{
				echo "Parâmetros de Pesquisa";
			}
		?>
		</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left' >
		<td align='center' colspan='<?=$colspan?>'><b><? fecho ("numero.da.os", $con, $cook_idioma); ?></b>
		<input type='text' name='sua_os' size='10' value='<? echo $sua_os ?>'></td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<? if($login_fabrica == '11' and $login_posto == '14301' or ($login_fabrica == '11' and  $login_posto == '6359')){?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center' colspan='<?=$colspan?>'><b><? fecho ("box", $con, $cook_idioma);
			
			echo "/";
			fecho ("prateleira", $con, $cook_idioma);  ?></b>
			
			<SELECT NAME="prateleira_box">
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='CONSERTO'><? fecho ("conserto.maiu", $con, $cook_idioma); ?></OPTION>
				<OPTION VALUE='TROCA'><? fecho ("troca.maiu", $con, $cook_idioma); ?></OPTION>
				<OPTION VALUE='REEMBOLSO'><? fecho ("reembolso.maiu", $con, $cook_idioma); ?></OPTION>
			</SELECT>
		</td>
	</tr>
	<? } ?>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	<?$align = ($login_fabrica==11) ? "right" : "center" ;?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='<?=$align?>' width="<?=$width?>">
				<input type="button" value="Listar todas as suas OS's" name="btn_listar_todas_os" id="btn_listar_todas_os" onclick='window.location="<? echo $PHP_SELF."?listar=todas"; ?>"' />	
		</td> 
	<?php if ($login_fabrica == 11) {?>
		
		<td width="<?=$width?>">
			<input type="button" value='Listar OS de Revenda Consertadas' name="btn_listar_os_consertada" id="btn_listar_os_consertada" onclick='window.location="<? echo $PHP_SELF."?listar=consertadas"; ?>"' />
		</td>
	
	<?php }?>
	
	</tr>
	
	
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<? if ($login_e_distribuidor == 't') { ?>

	<tr height="22" bgcolor="#bbbbbb">
		<TD>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? fecho ("listar.todas.as.os.do.posto", $con, $cook_idioma);?> </b>
			</font>
			<input type='text' name='codigo_posto' size='8' value='<? echo $codigo_posto ?>'>
			<input type='submit' value='Listar' name='btn_listar_posto'>
		</TD>
		
		
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>

	<? } ?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center' colspan='<?=$colspan?>'>
			<input type="button" value='Continuar' onclick="javascript: if (document.frm_os_pesquisa.btn_acao_pesquisa.value == '' ) { document.frm_os_pesquisa.btn_acao_pesquisa.value='continuar' ; document.frm_os_pesquisa.submit() } else { alert ('Aguarde submissão') }" style='cursor: pointer' />
			
		</td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
</table>
<input type='hidden' name='btn_acao_pesquisa' value=''>
<input type='hidden' name='campo_aux' id='campo_aux' value='<?=$listar;?>'>
</form>
</table>

<?
$btn_acao_pesquisa = trim($_POST['btn_acao_pesquisa']);
$listar            = trim($_POST['listar']);
$sua_os            = trim($_POST['sua_os']);
$codigo_posto      = trim($_POST['codigo_posto']);

if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = trim($_GET['btn_acao_pesquisa']);
if (strlen($_GET['listar']) > 0)            $listar            = trim($_GET['listar'])           ;
if (strlen($_GET['sua_os']) > 0)            $sua_os            = trim($_GET['sua_os'])           ;
if (strlen($_GET['codigo_posto']) > 0)      $codigo_posto      = trim($_GET['codigo_posto'])     ;

$posto = $login_posto;

if (strlen ($codigo_posto) > 0) {
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
	$posto = pg_fetch_result ($res,0,0);
}

//	HD 135436(+Mondial)
if (usaDataConserto($login_posto, $login_fabrica)) {
	$sql_data_conserto=", to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' )	as data_conserto ";
}
if($login_fabrica==11 and $login_posto==14301){
	$sql_obs=" , tbl_os.consumidor_email ";
}
if ((strlen($sua_os) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0 OR strlen ($codigo_posto) > 0 OR (strlen($prateleira_box) > 0 AND $btn_acao_pesquisa == 'continuar' ) ){
		//Ebano: removi este código e coloquei dentro do FOR para buscar o os_item
		
		if($listar == 'consertadas' and $login_fabrica == 11){
			$fazer_paginacao = 'nao';
		}
		
		if($login_posto == '4311'  or $login_posto == '6359' or $login_posto == '14301') {
			$sql_add2 =", tbl_os.prateleira_box ";
		}

		if($login_fabrica == 19) $sql_adiciona .= " AND tbl_os.consumidor_revenda = 'C' ";

		if($login_fabrica == '11' and $login_posto == '14301' or $login_posto == '6359'){
			if (strlen ($prateleira_box) > 0) {
				$sql_adiciona .= " AND tbl_os.prateleira_box = '$prateleira_box'";
			}
		}
		if ( strlen ($codigo_posto) == 0) {
			$sql_adiciona .= " AND tbl_os.posto = $login_posto ";
		} else {
			$sql_adiciona .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' AND (tbl_os.posto = $login_posto OR tbl_os.posto IN (SELECT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto))";
		}

		//hd 45142
		if ($login_fabrica==1){
			$fazer_paginacao = 'nao';
		}
		if ($login_fabrica == 11 and ($login_posto == 6940 or $login_posto == 4567 or $login_posto == 14236 or $login_posto == 1809 or $login_posto == 27401 or $login_posto == 6945 or $login_posto == 17674 or $login_posto == 5993 or $login_posto == 14254)) {
			$fazer_paginacao = 'nao';
		}

		//HD 18229
		if (strlen($sua_os) > 0) {
			$fazer_paginacao = 'nao';
			if ($login_fabrica == 1) {
				$pos = strpos($sua_os, "-");
				$pos = ($pos === false) ? strlen($sua_os) - 5 : $pos - 5;
				$sua_os = substr($sua_os, $pos,strlen($sua_os));
			}
			$sua_os = strtoupper ($sua_os);

			$pos = strpos($sua_os, "-");
			if ($pos === false) {
				if(!ctype_digit($sua_os)){
					$sql_adiciona .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					$sql_adiciona .= " AND tbl_os.os_numero = '$sua_os' ";
				}
			}else{
				$conteudo = explode("-", $sua_os);
				$os_numero    = $conteudo[0];
				$os_sequencia = $conteudo[1];
				if(!ctype_digit($os_sequencia)){
					$sql_adiciona .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					if($login_fabrica <>1 and $login_fabrica <>7){
						$sql_adiciona .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
					}else{
						//HD 9013 24484
						$sql_adiciona .= " AND tbl_os.os_numero = '$os_numero' ";
					}
				}
			}
		}
		if($login_fabrica==11 and ($login_posto==6359 or $login_posto==14301)){
			$sql_order .= "ORDER BY tbl_os.data_abertura ASC ";
		}else if($login_fabrica==1 and $login_posto==6359){
			$sql_order .= "ORDER BY tbl_os.consumidor_revenda asc,lpad(tbl_os.sua_os::text,20,'0') DESC, lpad(tbl_os.os::text,20,'0') DESC ";
		}else{
			$sql_order .= "ORDER BY lpad(tbl_os.sua_os,20,'0') DESC, lpad(tbl_os.os::text,20,'0') DESC ";
		}

		if($login_fabrica==3){ // HD 53760 2/12/2008
			// Samuel retirou not in tbl_os_status pq atualizou todos os status_os_ultimo que estavam
			// diferente da tbl_os_status.
			$sql_os_cancelada = " AND (tbl_os.status_os_ultimo <> 126 OR tbl_os.status_os_ultimo IS NULL) ";
		}
		$sql_linha = " AND NOT (tbl_produto.linha = 549) ";

		//HD 214236: OS em auditoria não podem ser fechadas
		if ($login_fabrica == 14 || $login_fabrica == 43) {
			$sql_auditoria = "AND tbl_os.os NOT IN (SELECT DISTINCT os FROM tbl_os_auditar WHERE liberado IS FALSE AND cancelada IS FALSE AND tbl_os_auditar.os=tbl_os.os)";
		}

		if ($login_fabrica==3){
			$sql = "SELECT  tbl_os.os                                                  ,
							tbl_os.sua_os                                              ,
							tbl_os.status_checkpoint								   ,
							tbl_os.serie                                               ,
							tbl_produto.referencia                                     ,
							tbl_produto.produto                                        ,
							tbl_produto.descricao                                      ,
							tbl_produto.nome_comercial                                 ,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
							tbl_os.consumidor_nome                                     ,
							tbl_os.consumidor_revenda                                  ,
							tbl_os.defeito_constatado                                  ,
							tbl_os.admin                                               ,
							tbl_os_extra.pac                                           ,
							tbl_os.tipo_atendimento
							$sql_add1
							$sql_add2
							$sql_data_conserto
							$sql_obs
					FROM    tbl_os
					JOIN    tbl_produto            USING (produto)
					JOIN    tbl_os_extra           USING (os)
					JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
					WHERE   tbl_os.fabrica = $login_fabrica
					/* HD 204146: Fechamento automático de OS */
					AND    tbl_os.data_fechamento IS NULL
					AND    tbl_os.excluida        IS NOT TRUE
					$sql_adiciona
					$sql_linha
					$sql_os_cancelada
					UNION
					SELECT  tbl_os.os                                                  ,
						    tbl_os.sua_os                                              ,
							tbl_os.status_checkpoint								   ,
							tbl_os.serie                                               ,
							tbl_produto.referencia                                     ,
							tbl_produto.produto                                        ,
							tbl_produto.descricao                                      ,
							tbl_produto.nome_comercial                                 ,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
							tbl_os.consumidor_nome                                     ,
							tbl_os.consumidor_revenda                                  ,
							tbl_os.defeito_constatado                                  ,
							tbl_os.admin                                               ,
							tbl_os_extra.pac                                           ,
							tbl_os.tipo_atendimento
							$sql_add1
							$sql_add2
							$sql_data_conserto
							$sql_obs
					FROM    tbl_os
					JOIN    tbl_produto            USING (produto)
					JOIN    tbl_os_extra           USING (os)
					JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
					WHERE   tbl_os.fabrica = $login_fabrica
					/* HD 204146: Fechamento automático de OS */
					AND    tbl_os.sinalizador = 18
					AND    tbl_os.excluida        IS NOT TRUE
					$sql_adiciona
					$sql_linha
					$sql_os_cancelada
					ORDER BY 2 DESC, 1 DESC";
		}else{
			$sql = "SELECT  tbl_os.os                                                  ,
							tbl_os.sua_os                                              ,
							tbl_os.status_checkpoint                                   ,
							tbl_os.serie                                               ,
							tbl_produto.referencia                                     ,
							tbl_produto.produto                                        ,
							tbl_produto.descricao                                      ,
							tbl_produto.nome_comercial                                 ,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
							tbl_os.consumidor_nome                                     ,
							tbl_os.consumidor_revenda                                  ,
							tbl_os.defeito_constatado                                  ,
							tbl_os.admin                                               ,
							tbl_os_extra.pac                                           ,
							tbl_os.tipo_atendimento
							$sql_add1
							$sql_add2
							$sql_data_conserto
							$sql_obs
					FROM    tbl_os
					JOIN    tbl_produto            USING (produto)
					JOIN    tbl_os_extra           USING (os)
					JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
					WHERE   tbl_os.fabrica = $login_fabrica";
			
			if ($login_fabrica == 11 and $listar == "consertadas"){ #HD 346804 - Listar os Consertadas para Lenoxx
				$sql .= " 
					AND tbl_os.consumidor_revenda='R' 
					AND tbl_os.data_conserto IS NOT NULL 
				";
			}
		
			$sql .= "
					/* HD 204146: Fechamento automático de OS */
					AND    tbl_os.data_fechamento IS NULL
					AND    tbl_os.excluida        IS NOT TRUE
					/*HD 214236: OS em auditoria não podem ser fechadas*/
					$sql_auditoria
					$sql_adiciona
					$sql_linha
					$sql_os_cancelada
					$sql_order
					";
		}
		// echo nl2br($sql);  
		$res = pg_query ($con,$sql);

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		/* Alterado HD 44973 - Colocado número da Página */
		// definicoes de variaveis
		$max_links = 15;				// máximo de links à serem exibidos
		$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
		/* Nos casos de busca por OS, mostrar paginacao longa, pois a Black precisa mostrar todas as OS na mesma tela  */
		if ($fazer_paginacao == 'nao'){
			$max_res   = 3000;
		}
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		if (pg_num_rows($res) > 0){
			echo "<div id='layout'>";
			echo "<div class='subtitulo'>";
			fecho ("com.o.fechamento.da.os.voce.se.habilita.ao.recebimento.dos.valores.que.serao.pagos.no.proximo.extrato", $con, $cook_idioma);
			echo "</div>";
			echo "</div>";
			echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center'>";
			echo "<tr>";
			echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
			echo "<td valign='top' align='center'>";

			if ($login_fabrica == 1){
				echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
				echo "<tr>";
				echo "<td align='center' width='18' height='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size=1>&nbsp;";
				fecho ("oss.que.excederam.o.prazo.limite.de.30.dias.para.fechamento,.informar.motivo", $con, $cook_idioma);
				echo "</font></td>";
				echo "</tr>";
				echo "<tr height='4'><td colspan='2'></td></tr>";
				echo "<tr>";
				echo "<td align='center' width='18' height='18' bgcolor='#FFCC66'>&nbsp;</td>";
				echo "<td align='left'><font size=1>&nbsp;";
				fecho ("oss.sem.defeito.constatado", $con, $cook_idioma);
				echo "</font></td>";
				echo "</tr>";
				if (strlen($msg_solucao) > 0){
					echo "<tr height='4'><td colspan='2'></td></tr>";
					echo "<tr>";
					echo "<td align='center' width='18' height='18' bgcolor='#99FFFF'>&nbsp;</td>";
					echo "<td align='left'><font size=1>&nbsp;";
					fecho ("oss.sem.solucao.e.sem.itens.lancados", $con, $cook_idioma);
					echo "</font></td>";
					echo "</tr>";
				}
				echo "</table>";
			}

			##### LEGENDAS - INÍCIO - HD 234532 #####
			/*
			 0 | Aberta Call-Center               | #D6D6D6
             1 | Aguardando Analise               | #FF8282
             2 | Aguardando Peças                 | #FAFF73
             3 | Aguardando Conserto              | #EF5CFF
             4 | Aguardando Retirada              | #9E8FFF
             9 | Finalizada                       | #8DFF70
			*/
			
			#Se for Bosh Security modificar a condição para pegar outros status também.
			$condicao_status = ($login_fabrica == 96) ? '0,1,2,3,5,6,7' : '0,1,2,3,4';
			
			$sql_status = "SELECT status_checkpoint,descricao,cor FROm tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.")";
			$res_status = pg_query($con,$sql_status);
			$total_status = pg_num_rows($res_status);

			?>
			<style>
			.status_checkpoint{width:15px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
			.status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;}
			</style>
			<div align='left' style='position: relative; left: 10'>
				<br>
				<table border='0' cellspacing='0' cellpadding='0'>
				<?php
				for($i=0;$i<$total_status;$i++){
					
					$id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
					$cor_status = pg_fetch_result($res_status,$i,'cor');
					$descricao_status = pg_fetch_result($res_status,$i,'descricao');
					
					#Array utilizado posteriormente para definir as cores dos status
					$array_cor_status[$id_status] = $cor_status;
					?>
				
					<tr height='18'>
						<td width='18' >
							<span class="status_checkpoint" style="background-color:<?php echo $cor_status;?>">&nbsp;</span>
						</td>
						<td align='left'>
							<font size='1'>
								<b>
									<a href="javascript:void(0)" onclick="filtrar(<?php echo $id_status;?>);">
										<?php echo $descricao_status;?>
									</a>
								</b>
							</font>
						</td>
					</tr>
				<?php }?>
				<tr height='18'>
					<td width='18' >
						<span class="status_checkpoint">&nbsp;</span>
					</td>
					<td align='left'>
						<font size='1'>
							<b>
								<a href="javascript:void(0)" onclick="filtrar(-1);">
									Listar Todos
								</a>
							</b>
						</font>
					</td>
				</tr>

				</table>
			</div>

			<? $data_fechamento= ($login_fabrica==11) ? date("d/m/Y") : ""; //HD 13239 ?>

		<!-- ------------- Formulário ----------------- -->
		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type='hidden' name='qtde_os' value='<? echo pg_num_rows ($res); ?>'>
		<input type='hidden' name='sua_os' value='<? echo $sua_os; ?>'>
		<input type='hidden' name='btn_acao_pesquisa' value='<? echo $btn_acao_pesquisa ?>'>
		<input type='hidden' name='listar' value='<? echo $listar ?>'>
		<TABLE width="650" border="0" cellpadding="2" cellspacing="0" align="center" >
			<tr>
				<TD width='120' class="fechamento">
					<b>
					<? fecho ("data.de.fechamento", $con, $cook_idioma); ?>
				</TD>
				<TD nowrap  width='530' class="fechamento_content">
					&nbsp;&nbsp;&nbsp;&nbsp;
					<?if (strlen($msg_erro)>0 and $_POST['data_fechamento'] ) $data_fechamento = $_POST['data_fechamento'];?>
					<input class="frm" type='text' name='data_fechamento' size='12' maxlength='10' value='<? echo $data_fechamento ?>'
					<?if($login_fabrica==11){
						echo "readonly='readonly'";
					}?> >
				</TD>
			</TR>
		</TABLE>

		<table width="650" border="0" cellspacing="1" cellpadding="4" align="center" style='font-family: verdana; font-size: 10px' class='tabela_resultado Relatorio'>
		<!-- class='tabela_resultado sample'-->
		<?		//HD 9013
			if($login_fabrica==1 or $login_fabrica ==7){?>
		<caption colspan='100%' style='font-family: verdana; font-size: 20px'><? fecho ("os.de.consumidor", $con, $cook_idioma); /*OS de Consumidor*/?></font><caption>
		<?}?>
		<thead>
		<tr height="20">
			<th nowrap>
				<input type='checkbox' class='frm main' name='marcar' value='tudo' title='<? fecho ("selecione.ou.desmarque.todos", $con, $cook_idioma); /*Selecione ou desmarque todos*/?>' onClick='SelecionaTodos(this.form.ativo);' style='cursor:pointer;'>
			</th>
			<th nowrap><b><? fecho ("os", $con, $cook_idioma); /*OS*/
				if($login_fabrica<>20){ fecho ("fabricante", $con, $cook_idioma); /*Fabricante*/ } ?></b></th>
			<? //HD 23623 ?>
			<? if ($login_fabrica == 11 and $login_posto==14301){ ?><th nowrap><b><? fecho ("box", $con, $cook_idioma);
			echo "/";
			fecho ("prateleira", $con, $cook_idioma);
			/*Box/Prateleira*/ ?></b></th><?}?>
			<th nowrap><b><? fecho ("data.abertura", $con, $cook_idioma); /*if($sistema_lingua == 'ES') echo "Fecha Abertura";else echo "Data Abertura";*/?></b></th>
			<th nowrap><b><? fecho ("consumidor", $con, $cook_idioma);/*if($sistema_lingua == 'ES') echo "Usuário";else echo "Consumidor";*/?></b></th>
			<th nowrap><b><? fecho ("produto", $con, $cook_idioma); /*if($sistema_lingua == 'ES') echo "Producto";else echo "Produto";*/?></b></th>
			<? if ($login_fabrica == 15){ ?><th nowrap><b><? fecho ("n.serie.reoperado", $con, $cook_idioma); /*N. Série Reoperado*/?></b></th><?}?>
			<? if ($login_fabrica == 30 or $login_fabrica == 85){ ?><th nowrap><b><? fecho ("n.serie", $con, $cook_idioma); /*N. Série*/ ?></b></th><?}?>
			<? if ($login_fabrica <> 2 AND $login_fabrica <> 1 AND $login_fabrica<>20){ ?>
			<th nowrap><b><? fecho ("nf.saida", $con, $cook_idioma); /*NF de Saída*/ ?></b></th>
			<th nowrap><b><? fecho ("data.nf.saida", $con, $cook_idioma); /*Data NF de Saída*/ ?></b></th>
<? } ?>
			<?if($login_fabrica==20){?>
			<th nowrap><b><? fecho ("valor.das.pecas", $con, $cook_idioma); /*if($sistema_lingua=='ES')echo "Valor de Piezas";else echo "Valor das Peças";*/?>
			</b></th>
			<th nowrap><b><? fecho ("mao.de.obra", $con, $cook_idioma); /*if($sistema_lingua=='ES')echo "Mano de Obra";else echo "Mão-de-Obra";*/?>
			</b></th>
			<? } ?>
			<?

			if($login_posto=='4311' or $login_posto == '6359' ) {
				echo "<th nowrap><b>";
				fecho ("box", $con, $cook_idioma);
				echo "</b></th>";
			}
			//HD 12521       HD 13239    HD 14121   HD 135436(+Mondial))
			if(usaDataConserto($login_posto, $login_fabrica)) {
				echo "<th nowrap><b>";
				fecho ("data.de.conserto", $con, $cook_idioma); /*Data de conserto*/
				echo "</b></th>";
			} 
			//HD 180939 (Dynacon)
			if($login_fabrica == 2) {
				echo "<th nowrap><b>";
				fecho ("Número PAC", $con, $cook_idioma); /*Número do PAC-Correios*/
				echo "</b></th>";
			} ?>
		</tr>
</thead>

<tbody>
<?
		$total_os = pg_num_rows ($res);
		for ($i = 0 ; $i < $total_os ; $i++) {
			if($login_fabrica == 11){
				$data_nf_saida     = $_POST['data_nf_saida_' . $i];
				$nota_fiscal_saida = $_POST['nota_fiscal_saida_' . $i];
			}
			$flag_cor = "";
			$cor =  ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";
			$consumidor_revenda = trim(pg_fetch_result ($res,$i,consumidor_revenda));
			$referencia         = trim(pg_fetch_result ($res,$i,referencia));
			//HD 9013
			if(($consumidor_revenda=='C' and ($login_fabrica==1 or $login_fabrica==7)) or ($login_fabrica<>1 and $login_fabrica <>7)){
			$os               = trim(pg_fetch_result ($res,$i,os));
			$sua_os           = trim(pg_fetch_result ($res,$i,sua_os));
			$admin            = trim(pg_fetch_result ($res,$i,admin));
			$tipo_atendimento = trim(pg_fetch_result ($res,$i,tipo_atendimento));
			$produto          = trim(pg_fetch_result ($res,$i,produto));
			$status_checkpoint=	pg_fetch_result($res,$i,'status_checkpoint');
			//HD 12521

			if($login_fabrica == 3 or $login_posto == '4311'){
				$sql = "
				SELECT   OI.os_item
				FROM      tbl_os_produto        OP
				JOIN      tbl_os_item           OI ON OP.os_produto        = OI.os_produto
				JOIN      tbl_servico_realizado SR ON OI.servico_realizado = SR.servico_realizado
				LEFT JOIN tbl_faturamento_item  FI ON OI.peca              = FI.peca              AND OI.pedido = FI.pedido
				WHERE OP.os = $os
				AND   SR.gera_pedido      IS TRUE
				AND   FI.faturamento_item IS NULL
				LIMIT 1
				";
				$res_os_item = pg_query($con, $sql);
				if (pg_num_rows($res_os_item)) {
					$os_item          = trim(pg_fetch_result ($res_os_item,0,os_item));
				}
			}
			//HD 13239   HD 135436(+Mondial))
			if(usaDataConserto($login_posto, $login_fabrica)) {
				$data_conserto           = trim(pg_fetch_result ($res,$i,data_conserto));
			}
			//HD 180939
			if($login_fabrica == 2) {
				$pac           = trim(pg_fetch_result ($res,$i,pac));
			}
			//HD 4291 Paulo --- HD 23623 - acrescentado 14301
			if($login_posto=='4311' or $login_posto == 6359 or $login_posto==14301) {
				$prateleira_box          = trim(pg_fetch_result ($res,$i,prateleira_box));
			}
			if($login_fabrica==11 and $login_posto==14301){
				$consumidor_email        = trim(pg_fetch_result ($res,$i,consumidor_email));
			}
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_fetch_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_fetch_result ($res,$i,descricao) ;

			$defeito_constatado = trim(pg_fetch_result ($res,$i,defeito_constatado));

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_query($con,$sql_idioma);
			if (@pg_num_rows($res_idioma) >0) {
				$descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================

			#150828
			$os_troca = false;
			if($login_fabrica ==11){
				$sql = "SELECT os
						FROM   tbl_os
						JOIN   tbl_os_troca USING(os)
						WHERE  os = $os
						AND    tbl_os.fabrica = $login_fabrica";
				$resY = pg_query($con, $sql);
				if (pg_num_rows($resY) > 0) {
					$os_troca = true;
				}
			}

			if ($login_fabrica == 1) {
				$sql = "SELECT os
						FROM   tbl_os
						WHERE  os = $os
						AND    motivo_atraso IS NULL
						AND    consumidor_revenda = 'C'
						AND    (data_abertura + INTERVAL '30 days')::date < current_date;";
				$resY = pg_query($con, $sql);
				if (pg_num_rows($resY) > 0) {
					$cor = "#FF0000";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}

				if (strlen($defeito_constatado) == 0) {
					$cor = "#FFCC66";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}elseif ($flag_bloqueio == "t" AND strlen($defeito_constatado) <> 0){
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}
			}
			//HD 4291 Paulo verificar a peça pendente da os e mudar cor
			
			$erros = (!empty($linha_erro)) ? implode($linha_erro,",") : null;
			if(strpos($erros,$os) > 0 or $erros == $os) {
				$cor = "#FF0000";
			}

		?>

		<tr bgcolor= <?echo $cor;echo " rel='status_$status_checkpoint' ";?> <? if (($erros == $os or strpos($erros,$os) > 0) and strlen ($msg_erro) > 0 )?>>
			<input type='hidden' name='os_<? echo $i ?>' value='<? echo pg_fetch_result ($res,$i,os) ?>' >
			<?if($login_fabrica==1){?>
			<input type='hidden' name='conta_<? echo $i ?>' value='<? echo $i;?>'>
			<input type='hidden' name='consumidor_revenda_<? echo $i ?>' value='<? echo pg_fetch_result ($res,$i,consumidor_revenda)?>'>
			<?}?>
			<td align="center">
<? if($login_fabrica == 3 and strlen($os_item)>0){?>
			<input type='hidden' name='os_item_<? echo $i ?>' value='<? echo "$os_item"; ?>'>
<? } ?>
			<? if (strlen($flag_bloqueio) == 0) { ?>
					<input type="checkbox" class="frm os" name="ativo_<?echo $i?>" id="ativo" value="t" <? if($login_fabrica==3){ ?> onClick='javascript:mostraDados(<?echo $i; ?>);' <? } ?>>
			 <? } ?></td>
			<? //Alterado por Wellington 06/12/2006 a pedido de Luiz Antonio, posto Jundservice (Lenoxx) deve abrir os_item ao clicar na OS ?>
			<?
			//HD 234532
			if(strlen($status_checkpoint)> 0 ) {
				$cor_status_os = '<span class="status_checkpoint" style="background-color:'.$array_cor_status[$status_checkpoint].'">&nbsp;</span>';
			} else {
				$cor_status_os = '<span class="status_checkpoint_sem">&nbsp;</span>';
			}
			?>
		<td>
			<?

			echo $cor_status_os;
			?><a href='<? if ($cor == "#FFCC66" or ($login_fabrica==11 and $login_posto==14254)) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1)echo $login_codigo_posto; echo $sua_os; ?></a></td>
			<? //HD 23623 ?>
			<? if($login_fabrica == 11 and $login_posto == 14301) echo "<td>$prateleira_box</td>"; ?>
			<td><? echo pg_fetch_result ($res,$i,data_abertura); ?></td>
			<td NOWRAP ><? echo substr (pg_fetch_result ($res,$i,consumidor_nome),0,10); ?></td>
			<? if($login_fabrica == 30 or $login_fabrica == 85){
			 $serie = pg_fetch_result ($res,$i,serie);
			?>
			<td NOWRAP><? echo substr ($descricao,0,15); ?></td>
			<? }else{ ?>
			<td NOWRAP><? echo ($login_fabrica== 11) ? pg_fetch_result ($res,$i,serie)." - ".substr($referencia,0,15) : pg_fetch_result ($res,$i,serie) . " - " . substr ($descricao,0,15); ?></td>
			<? } ?>

			<? if ($login_fabrica <> 2 AND $login_fabrica <> 1 AND $login_fabrica<>20){ ?>
			<?
			# Lorenzetti - Quando OS aberta pelo SAC para atendimento em Domicilio, obrigatorio NF de Devolucao
			if ($consumidor_revenda == 'R' OR (1==2 AND $login_fabrica == 19 AND strlen ($admin) > 0 AND $tipo_atendimento == 2) ){
				if($login_fabrica == 15){
					echo "<td><input class='frm' type='text' name='serie_reoperado_$i' size='15' maxlength='20' value='$serie_reoperado'></td>";
				}
				if(($login_fabrica == 30 or $login_fabrica == 85) and strlen($serie)==0 ){
					echo "<td><input class='frm' type='text' name='serie_$i' size='15' maxlength='20' value='$serie'></td>";
				}else if(strlen($serie)>0){
						echo "<td>$serie";
						echo "<input type='hidden' name='serie_$i' value='$serie'>";
						echo "</td>";
				}
				echo "<td><input class='frm' type='text' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'></td>";
				echo "<td><input class='frm' type='text' name='data_nf_saida_$i' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'></td>";
			}else{
				if(($login_fabrica == 30 or $login_fabrica == 85) and strlen($serie)==0){
					echo "<td><input class='frm' type='text' name='serie_$i' size='15' maxlength='20' value='$serie'></td>";
				}else if(strlen($serie)>0){
					echo "<td>$serie";
					echo "<input type='hidden' name='serie_$i' value='$serie'>";
					echo "</td>";
				}
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
			}
			?>
<? } ?>
<?
if ($login_fabrica == "20") {

	$pecas              = 0;
	$mao_de_obra        = 0;
	$tabela             = 0;
	$desconto           = 0;
	$desconto_acessorio = 0;

	$ysql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";
	$yres = pg_query ($con,$ysql);
	if (pg_num_rows ($yres) == 1) {
		$mao_de_obra = pg_fetch_result ($yres,0,mao_de_obra);
	}

	$ysql = "SELECT tabela,desconto,desconto_acessorio FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
	$yres = pg_query ($con,$ysql);

	if (pg_num_rows ($yres) == 1) {
		$tabela             = pg_fetch_result ($yres,0,tabela)            ;
		$desconto           = pg_fetch_result ($yres,0,desconto)          ;
		$desconto_acessorio = pg_fetch_result ($yres,0,desconto_acessorio);
	}
	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {

		$ysql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os
				 AND tbl_os.fabrica = $login_fabrica";
		$yres = pg_query ($con,$ysql);

		if (pg_num_rows ($yres) == 1) {
			$pecas = pg_fetch_result ($yres,0,0);
		}
	}else{
		$pecas = "0";
	}

	$valor_liquido = 0;

	if ($desconto > 0 and $pecas <> 0) {

		$ysql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$yres = pg_query ($con,$ysql);
		if (pg_num_rows ($res) == 1) {
			$produto = pg_fetch_result ($yres,0,0);
		}
		//echo 'peca'.$pecas;
		if( $produto == '20567' ){
			$desconto_acessorio = '0.2238';
			$valor_desconto = round ( (round ($pecas,2) * $desconto_acessorio ) ,2);
		}else{
			$valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
		}
		$valor_liquido = $pecas - $valor_desconto ;
	}
	$acrescimo = 0;
	if($login_pais<>"BR"){
		$ysql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$yres = pg_query ($con,$ysql);

		if (pg_num_rows ($yres) == 1) {
			$valor_liquido = pg_fetch_result ($yres,0,pecas);
			$mao_de_obra   = pg_fetch_result ($yres,0,mao_de_obra);
		}
		$ysql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
		$yres = pg_query ($con,$ysql);

		if (pg_num_rows ($yres) == 1) {
			$imposto_al   = pg_fetch_result ($yres,0,imposto_al);
			$imposto_al   = $imposto_al / 100;
			$acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}
	}

	//HD 9469 - Alteração no cálculo da BOSCH do Brasil HD 48439
	if($login_pais=="BR") {
		$sqlxx = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$resxx = pg_query ($con,$sqlxx);

		if (pg_num_rows ($resxx) == 1) {
			$valor_liquido = pg_fetch_result ($resxx,0,pecas);
			$mao_de_obra   = pg_fetch_result ($resxx,0,mao_de_obra);
		}
	}

	$total = $valor_liquido + $mao_de_obra + $acrescimo;

	$total          = number_format ($total,2,",",".")         ;
	$mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
	$acrescimo      = number_format ($acrescimo ,2,",",".")    ;
	$valor_desconto = number_format ($valor_desconto,2,",",".");
	$valor_liquido  = number_format ($valor_liquido ,2,",",".");

	echo "<td align='center'>" ;
	echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$valor_liquido</font>" ;
	echo "</td>";
	echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$mao_de_obra</font></td>";
}

if($login_posto=='4311' or $login_posto == '6359') {
	echo "<td align='center'>$prateleira_box</td>";
}
//HD 12521 //HD13239 hd 14121 HD 48989(bosch)
if(usaDataConserto($login_posto, $login_fabrica)) {
	echo "<td align='center'>";
	#hd 150828
	if ( ($login_fabrica == 11 AND $os_troca == false) or ($login_fabrica != 11) ){
		echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto' ";
		echo (($login_fabrica == 3 or $login_fabrica ==7) AND strlen($data_conserto)>0) ? " disabled " : "";
		echo ">";
	}
	echo "</td>";
	#hd 180939
	if ( $login_fabrica == 2 ){
		echo "<td align='center'>";
			echo "<input class='frm' type='text' name='pac_$i' alt='$os' rel='pac' size='13' maxlength='13' value='$pac' ";
		echo ">";
		echo "</td>";
	}
}
?>
		</tr>
<? if($login_fabrica == 3 and strlen($os)>0){?>
		<?  //HD 6477
		$sqlp = "SELECT peca, pedido, qtde FROM tbl_os_item WHERE os_item = $os_item;";

		#HD 51236  - gera_pedido IS TRUE
		#HD 160093 - Alterado para verificar se a peça tem faturamento
		$sqlP = "SELECT  tbl_os_item.os_item,
						tbl_os_item.pedido ,
						tbl_os_item.peca   ,
						tbl_os_item.qtde
				FROM tbl_os_produto
				JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
				JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
				WHERE tbl_os_produto.os = $os
				AND tbl_servico_realizado.gera_pedido IS TRUE
				AND  tbl_faturamento_item.faturamento_item IS NULL
				LIMIT 1";
		#echo nl2br($sqlP);
		$resP = pg_query($con, $sqlP);

		if (pg_num_rows($resP) > 0) {
			$pendente = "f";

			$pedido = pg_fetch_result($resP,0,pedido);
			$peca   = pg_fetch_result($resP,0,peca);
			$qtde   = pg_fetch_result($resP,0,qtde);

			if (strlen($pedido) > 0) {
				$sqlC = "SELECT os
						FROM tbl_pedido_cancelado
						WHERE pedido  = $pedido
						AND   peca    = $peca
						AND   qtde    = $qtde
						AND   os      = $os
						AND   fabrica = $login_fabrica";
				$resC = pg_query($con, $sqlC);

				if (pg_num_rows($resC) == 0) $pendente = "t";
			} else {
				$pendente = "t";
			}

			if ($pendente == "t") {?>
				<TR>
					<td colspan='7'>
						<div id='dados_<? echo $i; ?>' style='position:relative; display:none; border: 1px solid #FF6666;background-color: #FFCC99;width:100%; font-size:9px'><? fecho ("esta.os.que.voce.esta.fechando.tem.pecas", $con, $cook_idioma); /*Esta OS que você está fechando tem peças*/?> <strong><? fecho ("pendentes", $con, $cook_idioma); /*pendentes*/?></strong>! <? fecho ("motivo.do.fechamento", $con, $cook_idioma); /*Motivo do Fechamento: */?>
							<input class='frm' type='text' name='motivo_fechamento_<?echo$i;?>' size='30' maxlength='100' value=''>
						</div>
					</td>
				</tr>
			<?}
		}
}?>
<?if($login_fabrica==11 and $login_posto==14301 and strlen($consumidor_email) >0){ ?>
<TR bgcolor="<?echo $cor;?>"><td colspan="100%"><? fecho ("observacao", $con, $cook_idioma); /*Observação*/?>: <input type="text" name="observacao_<?echo $i;?>" size="100" maxlength="200" value="" title="<? fecho ("esta.informacao.sera.inserido.na.interacao.da.os.mandado.junto.com.o.email", $con, $cook_idioma); /*Esta informação será inserido na interação da OS e mandado junto com o email*/?>"></td></TR>
<?
		}
	}
	$os_anterior = $os;
}?>

</tbody>
		</table>
<br><br>

<?//HD 9013
	if($login_fabrica=='1' or $login_fabrica ==7){ ?>
		<table width="650" border="0" cellspacing="1" cellpadding="4" align="center" style='font-family: verdana; font-size: 10px' class='tabela_resultado sample'>
		<caption colspan='100%' style='font-family: verdana; font-size: 20px'>
		<?	if($login_fabrica ==7){
				fecho ("os.de.manutencao", $con, $cook_idioma); /*"OS de Manutenção"*/
			}else{
				if($login_fabrica ==1){
					fecho ("os.de.revenda.geo.metal", $con, $cook_idioma); /*"OS de Revenda / Metal Sanitario"*/
				}else{
					fecho ("os.de.revenda", $con, $cook_idioma); /*"OS de Revenda"*/
				}
			}
		?>
		</font><caption>
		<thead>
		<tr height="20">
			<th>
			<input type='checkbox' class='frm main' name='marcar' value='tudo' title='<? fecho ("selecione.ou.desmarque.todos", $con, $cook_idioma) /*Selecione ou desmarque todos*/;?>' onClick='SelecionaTodos(this.form.ativo_revenda);' style='cursor: hand;'>
			</th>
			<th nowrap><b><? fecho ("os.fabricante", $con, $cook_idioma); /*OS Fabricante*/ ?></b></th>
			<th nowrap><b><? fecho ("data.abertura", $con, $cook_idioma); /*Data Abertura*/ ?></b></th>
			<th nowrap><b><? fecho ("consumidor", $con, $cook_idioma); /*Consumidor*/ ?></b></th>
			<th nowrap><b><? fecho ("produto", $con, $cook_idioma); /*Produto*/ ?></b></th>
			<?
			if($login_fabrica <> 1){
				echo "<th nowrap><strong>";
				fecho ("nf.saida", $con, $cook_idioma); /*NF de Saída*/
				echo "</strong></th>";
				echo "<th nowrap><strong>";
				fecho ("data.nf.saida", $con, $cook_idioma); /*Data NF de Saída*/
				echo "</strong></th>";
			}
			if($login_posto=='4311' or $login_posto == '6359' ) {
				echo "<th nowrap><strong>";
				fecho ("box", $con, $cook_idioma); /*Box*/
				echo "</strong></th>";
			}

			//HD 12521       HD 13239    HD 14121   HD 135436(+Mondial))
			if(usaDataConserto($login_posto, $login_fabrica)) {
				echo "<th nowrap><strong>";
				fecho ("data.de.conserto", $con, $cook_idioma); /*Data de conserto*/
				echo "</strong></th>";
			} ?>
		</tr>
		</thead>
		<tbody>
<?
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$flag_cor = "";
			$cor = ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";
			$consumidor_revenda = trim(pg_fetch_result ($res,$i,consumidor_revenda));
			if($consumidor_revenda=='R' and ($login_fabrica==1 or $login_fabrica ==7)){
			$os               = trim(pg_fetch_result ($res,$i,os));
			$sua_os           = trim(pg_fetch_result ($res,$i,sua_os));
			$admin            = trim(pg_fetch_result ($res,$i,admin));
			$tipo_atendimento = trim(pg_fetch_result ($res,$i,tipo_atendimento));
			$produto          = trim(pg_fetch_result ($res,$i,produto));
			//HD 13239
			//HD 12521       HD 13239    HD 14121   HD 135436(+Mondial))
			if(usaDataConserto($login_posto, $login_fabrica)) {
				$data_conserto           = trim(pg_fetch_result ($res,$i,data_conserto));
			}
			//HD 4291 Paulo
			if($login_posto=='4311'or $login_posto == 6359) {
				$prateleira_box          = trim(pg_fetch_result ($res,$i,prateleira_box));
			}
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_fetch_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_fetch_result ($res,$i,descricao) ;

			$consumidor_revenda = trim(pg_fetch_result ($res,$i,consumidor_revenda));
			$defeito_constatado = trim(pg_fetch_result ($res,$i,defeito_constatado));
			if($login_fabrica ==1){
				$sql = "SELECT os
						FROM   tbl_os
						WHERE  os = $os
						AND    tbl_os.fabrica = $login_fabrica
						AND    motivo_atraso IS NULL
						AND    consumidor_revenda = 'C'
						AND    (data_abertura + INTERVAL '30 days')::date < current_date;";
				$resY = pg_query($con, $sql);
				if (pg_num_rows($resY) > 0) {
					$cor = "#FF0000";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}

				if (strlen($defeito_constatado) == 0) {
					$cor = "#FFCC66";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}elseif ($flag_bloqueio == "t" AND strlen($defeito_constatado) <> 0){
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}
			}
			
			//HD 4291 Fim
			// HD 101630
			$erros = (!empty($linha_erro)) ? implode($linha_erro,",") : null;
			if(strpos($erros,$os) > 0 or $erros == $os) {
				$cor = "#FF0000";
			}
?>
		<tr bgcolor=<? echo $cor;echo " rel='status_$status_checkpoint' ";?> <? if ((strpos($erros,$os) > 0 or $erros == $os) and strlen ($msg_erro) > 0 )?>>
			<input type='hidden' name='os_<? echo $i ?>' value='<? echo pg_fetch_result ($res,$i,os) ?>' >
			<input type='hidden' name='conta_<? echo $i ?>' value='<? echo $i;?>'>
			<input type='hidden' name='consumidor_revenda_<? echo $i ?>' value='<? echo pg_fetch_result ($res,$i,consumidor_revenda)?>'>
			<td align="center">
			<? if (strlen($flag_bloqueio) == 0) { ?><input type="checkbox" class="frm os" name="ativo_revenda_<?echo $i?>" id="ativo_revenda" value="t" ><? } ?></td>

	<td><a href='<? if ($cor == "#FFCC66" or ($login_fabrica==11 and $login_posto==14254)) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1)echo $login_codigo_posto; echo $sua_os; ?></a></td>
			<td><? echo pg_fetch_result ($res,$i,data_abertura) ?></td>
			<td NOWRAP ><? echo substr (pg_fetch_result ($res,$i,consumidor_nome),0,10) ?></td>
			<td NOWRAP><? echo pg_fetch_result ($res,$i,serie) . " - " . substr ($descricao,0,15) ?></td>
<?
	if($login_fabrica <>1){
		echo "<td><input class='frm' type='text' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'></td>";
		echo "<td><input class='frm' type='text' name='data_nf_saida_$i' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'></td>";
	}

	if($login_posto=='4311' or $login_posto == '6359') {
		echo "<td align='center'>$prateleira_box</td>";
	}
//HD 12521       HD 13239    HD 14121   HD 135436(+Mondial))
if(usaDataConserto($login_posto, $login_fabrica)) {
	echo "<td align='center'>";
		if(strlen($data_conserto)>0){
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto' disabled>";
		}else{
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto'>";
		}
	echo "</td>";
	}
?>
	</tr>

<?}
			}?>
</tbody>
</table>

<?}?>
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" background="" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
<?		if($sistema_lingua == "ES"){?>
			<img src='imagens/btn_cerrar_maior.gif' onclick="javascript: 
			if (document.frm_os.btn_acao.value == '' ){
				document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() 
			} else {
				alert ('Aguarde submissão') 
			}" ALT="Continuar con orden de servicio" border='0' style='cursor: pointer'>
		<? }else{ ?>
			<img src='imagens/btn_fechar_azul.gif' onclick="javascript:
			if (document.frm_os.btn_acao.value == '' ){
				document.frm_os.btn_acao.value='continuar';
				document.frm_os.submit();
			} else { 
				alert ('Aguarde submissão') 
			}" ALT=" com Ordem de Serviço" border='0' style='cursor: pointer'>
		<? }?>
		</td>
</tr>
</form>
</table>
<?

		// ##### PAGINACAO ##### //
		// links da paginacao
		echo "<br>";
		echo "<div>";

		if($pagina < $max_links) {
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("todos", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}
		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();
		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array("<b>$resultado_inicial</b>","<b>$resultado_final</b>","<b>$registros</b>"));
			echo "<font color='#cccccc' size='1'>";
			fecho("pagina.%.de.%",$con,$cook_idioma,array("<b>$valor_pagina</b>","<b>$numero_paginas</b>"));
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //

		}else{
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="top" align="center">
		<h4>
		<?
		fecho ("nao.foi.encontrada.os.nao.finalizada", $con, $cook_idioma);
		?>

		</h4>
	</td>
</tr>
</table>
<?
		}
	}
?>
<p>
<? include "rodape.php"; ?>
