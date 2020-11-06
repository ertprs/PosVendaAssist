<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$admin_privilegios = "financeiro";
include "autentica_admin.php";

//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
//			 de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
//			 O array abaixo define quais fábricas estão enquadradas no processo novo
$fabricas_acerto_extrato = array(43, 45);

//HD 237498: Barrar liberação de Extrato caso tenha OS em Intervenção de KM
//A funcao abaixo verifica se o extrato tem OS com KM pendente
$intervencao_km_extrato = array(30, 72);

function verifica_km_pendente_extrato($extrato) {
	global $con;

	//Verifica se a OS em algum momento entrou em intervenção de KM, status 98 | Aguardando aprovação da KM
	$sql = "
	SELECT
	tbl_os_extra.os

	FROM
	tbl_os_extra
	JOIN tbl_os_status ON tbl_os_extra.os=tbl_os_status.os

	WHERE
	tbl_os_extra.extrato=$extrato
	AND tbl_os_status.status_os=98
	";
	$res_km = pg_query($con, $sql);

	if (pg_num_rows($res_km)) {
		//Caso a OS algum dia tenha entrado em intervenção de KM, precisa ser verificado se saiu todas as vezes
		//A OS pode sair da intervenção de KM por um dos status abaixo:
		// 99 | KM Aprovada              
		//100 | KM Aprovada com alteração
		//101 | km Recusada              
		$n_intervencao_km = pg_num_rows($res_km);

		$sql = "
		SELECT
		tbl_os_extra.os

		FROM
		tbl_os_extra
		JOIN tbl_os_status ON tbl_os_extra.os=tbl_os_status.os

		WHERE
		tbl_os_extra.extrato=$extrato
		AND tbl_os_status.status_os IN (99, 100, 101)
		";
		$res_km = pg_query($con, $sql);

		$n_saida_intervencao_km = pg_num_rows($res_km);
		
		//Verifica se o número de vezes que saiu da intervenção é menor do que o número de
		//vezes que entrou, ou seja, se falta atender alguma intervenção para as OS deste extrato
		if ($n_saida_intervencao_km < $n_intervencao_km) {
			$km_pendente = true;
		}
		else {
			$km_pendente = false;
		}
	}
	else {
		$km_pendente = false;
	}

	return($km_pendente);
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto.cnpj = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

if($ajax=='conta'){
			if($login_fabrica==45){//HD 39377 12/9/2008
				$sql = "SELECT count(*) as qtde_os
						FROM tbl_os
						JOIN tbl_os_extra USING(os)
						WHERE tbl_os.mao_de_obra notnull
						and tbl_os.pecas       notnull
						and ((
								SELECT tbl_os_status.status_os
								FROM tbl_os_status
								WHERE tbl_os_status.os = tbl_os.os
								ORDER BY tbl_os_status.data DESC LIMIT 1
								) IS NULL
							OR (SELECT tbl_os_status.status_os
								FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os
								ORDER BY tbl_os_status.data DESC LIMIT 1
								) NOT IN (15)
							)
						and tbl_os_extra.extrato = $extrato";
			}else{
				$sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
			}
			$rres = pg_query($con,$sql);
			if(pg_num_rows($rres)>0){
				$qtde_os = pg_fetch_result($rres,0,qtde_os);
			}
			echo "ok|$qtde_os";
			exit;
}
// AJAX -> solicita a exportação dos extratos
if (strlen($_GET["exportar"])>0){
	//include "../ajax_cabecalho.php";
	//system("/www/cgi-bin/bosch/exporta-extrato.pl",$ret);
	$dados = "$login_fabrica\t$login_admin\t".date("d-m-Y H:m:s");
	exec ("echo '$dados' > /tmp/bosch/exporta/pronto.txt");
	echo "ok|Exportação concluída com sucesso! Dentro de alguns minutos os arquivos de exportação estarão disponíveis no sistema.";
	exit;
}
// FIM DO AJAX -> solicita a exportação dos extratos


// AJAX -> APROVA O EXTRATO SELECIONADO
// ATENÇÃO: NESTE ARQUIVO EXISTEM DUAS ROTINAS PARA APROVAR EXTRATO, UMA COM AJAX E OUTRA SEM
//			QUANDO FOR MODIFICAR UMA, VERIFIQUE SE É NECESSÁRIO MODIFICAR A OUTRA
if ($_GET["ajax"] == "APROVAR" && strlen($_GET["aprovar"])>0 && strlen($_GET["posto"])>0){
	$posto   = $_GET["posto"];
	$aprovar = $_GET["aprovar"];

	$res = pg_query($con,"BEGIN TRANSACTION");

	if ($login_fabrica == 20 || $login_fabrica == 14) {
		$nf_mao_de_obra = $_GET["nf_mao_de_obra"];
		if (strlen(trim($nf_mao_de_obra))==0) {
			$nf_mao_de_obra = 'null';
		}

		$nf_devolucao   = $_GET["nf_devolucao"];
		if (strlen(trim($nf_devolucao))==0) {
			$nf_devolucao = 'null';
		}

		$data_entrega_transportadora = $_GET["data_entrega_transportadora"];
		$data_entrega_transportadora = str_replace (" " , "" , $data_entrega_transportadora);
		$data_entrega_transportadora = str_replace ("-" , "" , $data_entrega_transportadora);
		$data_entrega_transportadora = str_replace ("/" , "" , $data_entrega_transportadora);
		$data_entrega_transportadora = str_replace ("." , "" , $data_entrega_transportadora);
		
		if (strlen ($data_entrega_transportadora) == 6) {
			$data_entrega_transportadora = substr ($data_entrega_transportadora,0,4) . "20" . substr ($data_entrega_transportadora,4,2);
		}
		
		if (strlen ($data_entrega_transportadora) > 0) {
			$data_entrega_transportadora = substr ($data_entrega_transportadora,0,2) . "/" . substr ($data_entrega_transportadora,2,2) . "/" . substr ($data_entrega_transportadora,4,4);
			if (strlen ($data_entrega_transportadora) < 8) $data_entrega_transportadora = date ("d/m/Y");
			$data_entrega_transportadora = substr ($data_entrega_transportadora,6,4) . "-" . substr ($data_entrega_transportadora,3,2) . "-" . substr ($data_entrega_transportadora,0,2);
			} else {
			$data_entrega_transportadora = 'null';
		}

		$sql = "UPDATE tbl_extrato_extra SET
					nota_fiscal_mao_de_obra     = '$nf_mao_de_obra',
					nota_fiscal_devolucao       = '$nf_devolucao',
					data_entrega_transportadora = '$data_entrega_transportadora'
				WHERE extrato = $aprovar";
		#$res = pg_query($con,$sql);
		# Estava comentado , entao descomentei. Pq comentaram?  Não tem a explicacao.
		# Estou liberando. HD 4846
		
		//HD 145478 - Gravando quem aprovou o extrato
		$sql = "
		UPDATE
		tbl_extrato_extra

		SET
		admin = $login_admin

		WHERE
		extrato = $aprovar
		";
		$res = pg_query($con, $sql);
	}

	$sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$aprovar)";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		echo "ok;$aprovar";
	}else{
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}
	exit;
}
// FIM DO AJAX -> APROVA O EXTRATO SELECIONADO

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

if (strlen($_GET["liberar"]) > 0) $liberar = $_GET["liberar"];

if (strlen($liberar) > 0){
	//HD 237498: Barrar liberação de Extrato caso tenha OS em Intervenção de KM
	if (in_array($login_fabrica, $intervencao_km_extrato)) {
		//Verifica se a OS em algum momento entrou em intervenção de KM, status 98 | Aguardando aprovação da KM
		$sql = "
		SELECT
		tbl_os_extra.os

		FROM
		tbl_os_extra
		JOIN tbl_os_status ON tbl_os_extra.os=tbl_os_status.os

		WHERE
		tbl_os_extra.extrato=$liberar
		AND tbl_os_status.status_os=98
		";
		$res_km = pg_query($con, $sql);

		if (pg_num_rows($res_km)) {
			//Caso a OS algum dia tenha entrado em intervenção de KM, precisa ser verificado se saiu todas as vezes
			//A OS pode sair da intervenção de KM por um dos status abaixo:
			// 99 | KM Aprovada              
			//100 | KM Aprovada com alteração
			//101 | km Recusada              
			$n_intervencao_km = pg_num_rows($res_km);

			$sql = "
			SELECT
			tbl_os_extra.os

			FROM
			tbl_os_extra
			JOIN tbl_os_status ON tbl_os_extra.os=tbl_os_status.os

			WHERE
			tbl_os_extra.extrato=$liberar
			AND tbl_os_status.status_os IN (99, 100, 101)
			";
			$res_km = pg_query($con, $sql);

			$n_saida_intervencao_km = pg_num_rows($res_km);
			
			//Verifica se o número de vezes que saiu da intervenção é menor do que o número de
			//vezes que entrou, ou seja, se falta atender alguma intervenção para as OS deste extrato
			if ($n_saida_intervencao_km < $n_intervencao_km) {
				$msg_erro = "Atenção: existem OS em intervenção neste extrato ($liberar). Para que o extrato seja liberado é necessário aprovar ou reprovar todas as intervenções de suas OS antes. Consulte o extrato para maiores detalhes.";
			}
		}
	}

	/*IGOR HD 17677 - 04/06/2008 */
	if($login_fabrica ==11 or $login_fabrica ==25){
		$sql="SELECT recalculo_pendente
				from tbl_extrato
				where extrato=$liberar
				and fabrica=$login_fabrica";
		$res = @pg_query($con,$sql);
		$recalculo_pendente=pg_fetch_result($res,0,recalculo_pendente);
		if($recalculo_pendente=='t'){
			$msg_erro="Este extrato será recalculado de noite e poderá ser liberado amanhã";
		}
	}
	if (strlen($msg_erro)==0){

		$res = pg_query($con,"BEGIN TRANSACTION");

		//HD 205958: Não pode aprovar nenhum extrato na liberação, é uma falha no conceito do negócio.
		//			 antes de atender qualquer solicitação das fábricas concernentes a isto, verificar conceitos
		//			 definidos neste chamado. Apagadas 3 linhas abaixo, verificar nao_sync caso necessário
		if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
		}
		else {
			//HD 205958: Este conceito está errado, um extrato nunca pode ser aprovado na liberação. Esta linha
			//			 está aqui provisóriamente enquanto arrumamos os conceitos das fábricas
			$aprovar_na_liberacao = "aprovado = current_date,";
		}
		$sql = "
		UPDATE
		tbl_extrato
		
		SET
		liberado = current_date,
		$aprovar_na_liberacao
		admin = $login_admin
		
		WHERE extrato = $liberar
		"; //Corrigido! HD 44022
		$res = @pg_query($con,$sql);
		$msg_erro = @pg_errormessage($con);

		//Wellington 14/12/2006 - ENVIA EMAIL PARA O POSTO QDO O EXTRATO É LIBERADO
		/*IGOR HD 17677 - 04/06/2008 */
		/*HD 138813 MLG - Não enviada para alguns postos porque na tbl_posto não tem e-mail.
						  Alterado para pegar das duas, de preferência da tbl_posto_fabrica */
		if (strlen($msg_erro)==0 and in_array($login_fabrica, array(11, 24, 25, 40))) {
			include 'email_comunicado.php';	// Funções para enviar e-mail e inserir comunicado para o Posto
			$sql = "
			SELECT
			CASE
                            WHEN contato_email IS NULL THEN tbl_posto.email
                            ELSE contato_email
                        END AS email,
			tbl_posto_fabrica.posto
			
			FROM 
			tbl_posto_fabrica
                        JOIN tbl_extrato USING (posto,fabrica)
                        JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
			
			WHERE
			extrato = $liberar";

			$res = @pg_query($con,$sql);

			if (@pg_num_rows($res)) {
				//Se tem aviso, pega o valor, tanto se foi por GET como POST...
				$msg_aviso    = $_REQUEST['msg_aviso'];
				$xposto		  = trim(pg_fetch_result($res,0,posto));
				$destinatario = trim(pg_fetch_result($res,0,email));
				$assunto      = "SEU EXTRATO (Nº $liberar) FOI LIBERADO";
				$mensagem     =  "* O EXTRATO Nº".$liberar." ESTÁ LIBERADO NO SITE: www.telecontrol.com.br *<br><br>".$msg_aviso ;

				$r_email    = "<helpdesk@telecontrol.com.br>";
				$remetente  = "TELECONTROL";

				if ($login_fabrica == 11) {
					$r_email    = "<celia@lenoxxsound.com.br>";
					$remetente  = "LENOXXSOUND FINANCEIRO";
				}
				elseif ($login_fabrica == 24) {
					$r_email    = "<suggat@suggar.com.br>";
					$remetente  = "SUGGAR FINANCEIRO";
				}
				elseif ($login_fabrica == 25) {
					$r_email    = "<ronaldo@telecontrol.com.br>";
					$remetente  = "HBFLEX FINANCEIRO";
				}

				$headers = "Return-Path:$r_email \nFrom:".$remetente.
					   "\nBcc:$r_email \nContent-type: text/html\n";

				enviar_email($r_email, $destinatario, $assunto, $mensagem, $remetente, $headers, true);
				gravar_comunicado("Extrato disponível", $assunto, $mensagem, $xposto, true);
			}
	        }

		//wellington liberar
		// Fabio 02/10/2007
		// Alterado por Fabio -> tbl_faturamento.emissao <  '2007-10-21' // HD 600
		// Depois da liberação, alterar para tbl_faturamento.emissao < current_date - interval'15 day'
		/* LENOXX - SETA EXTRATO DE DEVOLUÇÃO PARA OS FATURAMENTOS */
		/*IGOR HD 17677 - 04/06/2008 */
		if (strlen($liberar) > 0 and strlen($msg_erro)==0 and ($login_fabrica==11 OR $login_fabrica == 25 )) {
			if($login_fabrica == 25 ) {
				$sql = "SELECT TO_CHAR(data_geracao-interval '15 days','YYYY-MM-DD') AS data_limite
						FROM tbl_extrato
						WHERE extrato = $liberar;";
			}else{
				$sql = "SELECT TO_CHAR(data_geracao-interval '1 month','YYYY-MM-21') AS data_limite
						FROM tbl_extrato
						WHERE extrato = $liberar;";
			}

			$res = pg_query($con,$sql);
			$data_limite_nf = trim(pg_fetch_result($res,0,data_limite));

			$sql = "UPDATE tbl_faturamento SET extrato_devolucao = $liberar
					WHERE  tbl_faturamento.fabrica = $login_fabrica
					AND    tbl_faturamento.posto   = $xposto
					AND    tbl_faturamento.extrato_devolucao IS NULL
					AND    tbl_faturamento.emissao > '2007-08-30'
					AND    tbl_faturamento.emissao < '$data_limite_nf'
					AND    (tbl_faturamento.cfop ILIKE '%59%' OR tbl_faturamento.cfop ILIKE '%69%')
					";
			// AND    tbl_faturamento.emissao <  current_date - interval'15 day'
			$res = pg_query($con,$sql);

			$sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = $liberar";
			$res = pg_query($con,$sql);

			$sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde) (
				SELECT tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde)
				FROM tbl_extrato
				JOIN tbl_faturamento      ON tbl_extrato.extrato         = tbl_faturamento.extrato_devolucao
				JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.extrato = $liberar
				GROUP BY tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca
				) ;";
			$res = pg_query($con,$sql);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
		}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btnacao == 'liberar_tudo'){
	if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];

	$sql = "begin";
	$res = @pg_query($con,$sql);
	$extrato_km_pendente = array();

	for ($i=0; $i < $total_postos; $i++) {
		$extrato    = $_POST["liberar_".$i];
		$imprime_os = $_POST["imprime_os_".$i];
		$km_pendente = false;

		//HD 237498: Barrar liberação de Extrato caso tenha OS em Intervenção de KM
		if (in_array($login_fabrica, $intervencao_km_extrato) && $extrato) {
			$km_pendente = verifica_km_pendente_extrato($extrato);
		}
		else {
			$km_pendente = false;
		}

		if ($km_pendente) {
			$extrato_km_pendente[] = $extrato;
		}
		else {
			if (strlen($extrato) > 0 AND strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_extrato SET liberado = current_date ";

				//HD 205958: Não pode aprovar nenhum extrato na liberação, é uma falha no conceito do negócio.
				//			 antes de atender qualquer solicitação das fábricas concernentes a isto, verificar conceitos
				//			 definidos neste chamado. Apagadas 3 linhas abaixo, verificar nao_sync caso necessário
				if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
				}
				elseif (in_array($login_fabrica,array(6, 7, 11, 30,14, 15, 24, 25, 35, 43, 45, 46, 50, 51, 59, 66, 74, 80,52))) {
					//HD 205958: Este conceito está errado, um extrato nunca pode ser aprovado na liberação. Esta linha
					//			 está aqui provisóriamente enquanto arrumamos os conceitos das fábricas
					$sql .= ", aprovado = current_date ";
				}

				$sql .= "WHERE  tbl_extrato.extrato = $extrato
						 and    tbl_extrato.fabrica = $login_fabrica";
						 //echo $sql;
				$res = pg_query($con,$sql);
				$msg_erro = @pg_errormessage($con);

				//Wellington 14/12/2006 - ENVIA EMAIL PARA O POSTO QDO O EXTRATO É LIBERADO
				/*IGOR HD 17677 - 04/06/2008 */
				if (strlen($msg_erro)==0 and ($login_fabrica==11 OR $login_fabrica==25)) {
					include 'email_comunicado.php';	// Funções para enviar e-mail e inserir comunicado para o Posto
					$sql = "SELECT CASE
									WHEN contato_email IS NULL
										THEN tbl_posto.email
									ELSE contato_email
									END AS email, tbl_posto_fabrica.posto FROM tbl_posto_fabrica
								JOIN tbl_extrato USING (posto,fabrica)
								JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
							WHERE extrato = $extrato";
					$res = pg_query($con,$sql);

		//			Se tem aviso, pega o valor, tanto se foi por GET como POST...
					$msg_aviso    = (isset($_REQUEST['msg_aviso']))?"AVISO: ".$_REQUEST['msg_aviso']."<BR><BR><BR>":"";
					$xposto		  = trim(pg_fetch_result($res,0,posto));
					$destinatario = trim(pg_fetch_result($res,0,email));
					$assunto      = "SEU EXTRATO (Nº $extrato) FOI LIBERADO";
					$mensagem     =  "* O EXTRATO Nº".$extrato." ESTÁ LIBERADO NO SITE: www.telecontrol.com.br *<br><br>".$msg_aviso ;

					$r_email    = "<helpdesk@telecontrol.com.br>";
					$remetente  = "TELECONTROL";
					if ($login_fabrica == 11) {
						$r_email    = "<celia@lenoxxsound.com.br>";
						$remetente  = "LENOXXSOUND FINANCEIRO";
					}
	// 	            if ($login_fabrica == 24) {
	// 	                $r_email    = "<suggat@suggar.com.br>";
	// 					$remetente  = "SUGGAR FINANCEIRO";
	// 	            }
					if ($login_fabrica == 25) {
						$r_email    = "<ronaldo@telecontrol.com.br>";
						$remetente  = "HBFLEX FINANCEIRO";
					}
					$headers    = "Return-Path:$r_email \nFrom:".$remetente.
								  "\nBcc:$r_email \nContent-type: text/html\n";

					enviar_email($r_email, $destinatario, $assunto, $mensagem, $remetente, $headers, true);
					gravar_comunicado("Extrato disponível", $assunto, $mensagem, $xposto, true);
				}
			}

			//wellington liberar
			/* LENOXX - SETA EXTRATO DE DEVOLUÇÃO PARA OS FATURAMENTOS */
			/*IGOR HD 17677 - 04/06/2008 */
			if (strlen($extrato) > 0 and strlen($msg_erro)==0 and ($login_fabrica==11 OR $login_fabrica==25)) {

				$sql = "SELECT TO_CHAR(data_geracao-interval '1 month','YYYY-MM-21') AS data_limite
						FROM tbl_extrato
						WHERE extrato = $extrato;";
				$res = pg_query($con,$sql);
				$data_limite_nf = trim(pg_fetch_result($res,0,data_limite));

				$sql = "UPDATE tbl_faturamento SET extrato_devolucao = $extrato
						WHERE  tbl_faturamento.fabrica = $login_fabrica
						AND    tbl_faturamento.posto   = $xposto
						AND    tbl_faturamento.extrato_devolucao IS NULL
						AND    tbl_faturamento.emissao >  '2007-08-30'
						AND    tbl_faturamento.emissao < '$data_limite_nf'
						AND    (tbl_faturamento.cfop ILIKE '%59%' OR tbl_faturamento.cfop ILIKE '%69%')
						";
				$res = pg_query($con,$sql);

				$sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = $extrato";
				$res = pg_query($con,$sql);

				$sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde) (
					SELECT tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde)
					FROM tbl_extrato
					JOIN tbl_faturamento      ON tbl_extrato.extrato         = tbl_faturamento.extrato_devolucao
					JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   tbl_extrato.extrato = $extrato
					GROUP BY tbl_extrato.extrato, tbl_extrato.posto, tbl_faturamento_item.peca
					) ;";
				$res = pg_query($con,$sql);
			}

			//HD 12104
			if($login_fabrica==14 and strlen($imprime_os) > 0){
				$sql =" UPDATE tbl_posto_fabrica set imprime_os ='t'
							FROM tbl_extrato
							WHERE tbl_extrato.posto=tbl_posto_fabrica.posto
							AND extrato=$imprime_os
							AND tbl_posto_fabrica.fabrica=$login_fabrica ";
				$res=pg_query($con,$sql);
			}
		}
		//HD 237498: Coloquei esta linha porque depois que aprovava tudo sempre mostrava o último extrato, sozinho, ficando confuso
		$extrato = "";
	}
	$sql = (strlen($msg_erro) == 0) ? "commit" : "rollback";
	$res = @pg_query($con,$sql);
	
	//HD 237498: Esta mensagem de erro tem que ficar depois do commit/rollback, pois é apenas informativa, não deve impedir que a transacao se concretize
	if (count($extrato_km_pendente)) {
		$extrato_km_pendente = implode(", ", $extrato_km_pendente);
		$msg_erro = "ATENÇÃO: Os extratos a seguir possuem OS em Intervenção de KM sem aprovação/reprovação e não serão liberados até que seja definida uma posição da fábrica em relação a esta intervenção.<br>
		Extratos não liberados: $extrato_km_pendente";
	}
}



if ($btnacao == "acumular_tudo") {
	if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];

	$res = pg_query($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $total_postos ; $i++) {
		$extrato = $_POST["acumular_" . $i];

		if (strlen($extrato) > 0) {
			$xextrato = $extrato;
			$sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
		if (strlen($msg_erro) > 0) break;
	}

	$destinatario ="";
	if (strlen($msg_erro)==0 AND $login_fabrica==45){ //HD 66773
		if(strlen($xextrato)>0){
			$sql_email = "	SELECT tbl_posto_fabrica.contato_email
							FROM tbl_extrato
							JOIN tbl_posto_fabrica USING (posto)
							WHERE tbl_posto_fabrica.fabrica = $login_fabrica
							AND   tbl_extrato.extrato       = $xextrato";
			$res_email = pg_query($con, $sql_email);

			if(pg_num_rows($res_email)>0){
				$email_posto = pg_fetch_result($res_email,0,contato_email);
			}
		}
		$mensagem = "At. Responsável,<p>As Ordens de Serviço do extrato " . $xextrato . " foram acumuladas para o próximo mês.</p>\n";
		$mensagem.= "<p style='color:red'>NKS</p>";

		if(strlen($email_posto)>0){
			$destinatario= "$email_posto";
// 			$remetente   = "helpdesk@telecontrol.com.br";
			$remetente   = "maiara@nksonline.com.br";
			$assunto     = "Extrato $xextrato";
			$mensagem    = "<p style='center'>Nota: Este e-mail é gerado automaticamente. <br>".
                           "**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</p>" . $mensagem;
			$headers     ="From:$remetente\r\nContent-type: text/html\r\ncco:gustavo@telecontrol.com.br";
			if(strlen($mensagem)>0) mail($destinatario,$assunto,$mensagem,$headers);
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

// ATENÇÃO: NESTE ARQUIVO EXISTEM DUAS ROTINAS PARA APROVAR EXTRATO, UMA COM AJAX E OUTRA SEM
//			QUANDO FOR MODIFICAR UMA, VERIFIQUE SE É NECESSÁRIO MODIFICAR A OUTRA
if (strlen($_GET["aprovar"]) > 0) $aprovar = $_GET["aprovar"]; // é o numero do extrato

if (strlen($aprovar) > 0){
	//HD 205958: Acrescentado validação com BEGIN, COMMIT, ROLLBACK
	$res = pg_query($con,"BEGIN TRANSACTION");

	$km_pendente = false;

	//HD 237498: Barrar aprovação de Extrato caso tenha OS em Intervenção de KM
	if (in_array($login_fabrica, $intervencao_km_extrato)) {
		$km_pendente = verifica_km_pendente_extrato($aprovar);
	} else {
		$km_pendente = false;
	}

	if ($km_pendente) {
		$msg_erro = "ATENÇÃO: O extrato $aprovar possui OS em Intervenção de KM sem aprovação/reprovação e não serão aprovados até que seja definida uma posição da fábrica em relação a esta intervenção";
	} else {
		//atualiza campos de notas fiscais
		if ($login_fabrica == 20 || $login_fabrica == 14) {
			$nf_mao_de_obra = $_GET["nf_mao_de_obra"];
			if (strlen(trim($nf_mao_de_obra)) == 0) {
				$nf_mao_de_obra = 'null';
			}

			$nf_devolucao   = $_GET["nf_devolucao"];
			if (strlen(trim($nf_devolucao))==0) {
				$nf_devolucao = 'null';
			}

			$data_entrega_transportadora = $_GET["data_entrega_transportadora"];
			$data_entrega_transportadora = str_replace (" " , "" , $data_entrega_transportadora);
			$data_entrega_transportadora = str_replace ("-" , "" , $data_entrega_transportadora);
			$data_entrega_transportadora = str_replace ("/" , "" , $data_entrega_transportadora);
			$data_entrega_transportadora = str_replace ("." , "" , $data_entrega_transportadora);

			if (strlen ($data_entrega_transportadora) == 6) {
				$data_entrega_transportadora = "'".substr ($data_entrega_transportadora,0,4) . "20" . substr ($data_entrega_transportadora,4,2)."'";
			}
			
			if (strlen ($data_entrega_transportadora) > 0) {
				$data_entrega_transportadora = substr ($data_entrega_transportadora,0,2) . "/" . substr ($data_entrega_transportadora,2,2) . "/" . substr ($data_entrega_transportadora,4,4);
				if (strlen ($data_entrega_transportadora) < 8) $data_entrega_transportadora = date ("d/m/Y");
				$data_entrega_transportadora = "'".substr ($data_entrega_transportadora,6,4) . "-" . substr ($data_entrega_transportadora,3,2) . "-" . substr ($data_entrega_transportadora,0,2)."'";
				} else {
				$data_entrega_transportadora = 'null';
			}

			$sql = "UPDATE tbl_extrato_extra SET
						nota_fiscal_mao_de_obra     = '$nf_mao_de_obra',
						nota_fiscal_devolucao       = '$nf_devolucao',
						data_entrega_transportadora = $data_entrega_transportadora
					WHERE extrato = $aprovar";

			$res = pg_query($con,$sql);
			
			if (pg_errormessage($con)) {
				$msg_erro = "Ocorreu um erro na aprovação do extrato $aprovar";
			}
			#  HD 4846 - Colocado!

			$sql = "
			UPDATE
			tbl_extrato_extra

			SET
			admin = $login_admin

			WHERE
			extrato = $aprovar
			";
			$res = pg_query($con, $sql);
			if (pg_errormessage($con)) {
				$msg_erro = "Ocorreu um erro na aprovação do extrato $aprovar";
			}

		}
		
		//PARA A INTELBRÁS DEIXAR ELE APROVAR EXTRATO, POIS ELES ESTÃO EM PROCESSO DE TRANSIÇÃO, SEGUNDO A RAMONNA
		$sql = "SELECT fn_aprova_extrato($posto,$login_fabrica,$aprovar)";
		$res = pg_query($con,$sql);

		if (pg_errormessage($con)) {
			$msg_erro = "Ocorreu um erro na aprovação do extrato $aprovar: " . pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
		} else {
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}

	}

}

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS";

include "cabecalho.php";

?>

<p>

<style type="text/css">
	body{
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12PX;
	}
	.menu_top {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10PX	;
		font-weight: bold;
		border: 1px solid;
	;
		background-color: #D9E2EF
	}

	.table_line {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
		background-color: #D9E2EF
	}

	.table_line2 {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
	}
	.quadro{
		border: 1px solid #596D9B;
		width:450px;
		height:50px;
		padding:10px;

	}

	.botao {
			border-top: 1px solid #333;
				border-left: 1px solid #333;
				border-bottom: 1px solid #333;
				border-right: 1px solid #333;
				font-size: 13px;
				margin-bottom: 10px;
				color: #0E0659;
			font-weight: bolder;
	}

	.texto_padrao {
				font-size: 12px;
	}

	#Formulario tbody th{
		text-align: left;
		font-weight: bold;
	}
	#Formulario tbody td{
		text-align: left;
		font-weight: none;
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

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border:1px solid #ACACAC;
	border-collapse: collapse;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}
</style>
<!--[if lt IE 8]>
<style>
table.tabela{
	empty-cells:show;
    border-collapse:collapse;
	border-spacing: 2px;
}
</style>
<![endif]-->
<? include "javascript_calendario.php";?>

<script type="text/javascript" src="js/date.js"></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type='text/javascript' src="js/bibliotecaAJAX.js"></script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<script type="text/javascript" >
	$(document).ready(function(){
		
		$('#data_inicial').datePicker({startDate : '01/01/2000'});
		$('#data_final').datePicker({startDate : '01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
		$(".data_entrega_transportadora").maskedinput("99/99/9999");
	});

function somente_numero(campo){
		
        var digits="0123456789-./"
        var campo_temp 
        for (var i=0;i<campo.value.length;i++){
          campo_temp=campo.value.substring(i,i+1)       
          if (digits.indexOf(campo_temp)==-1){
                        campo.value = campo.value.substring(0,i);
                        break;
           }
        }
}


function formata_cnpj(cnpj, form){
	
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "posto_codigo";
		myform = form;

		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}


</script>

<script language="JavaScript">

// HD 22752
function refreshTela(tempo){
	window.setTimeout("window.location.href = window.location.href", tempo);
}

$(document).ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
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
		minChars: 3,
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
</script>
<script language="JavaScript">

/* ============= Função PESQUISA DE POSTOS ====================
Nome da Função : fnc_pesquisa_posto (cnpj,nome)
		Abre janela com resultado da pesquisa de Postos pela
		Código ou CNPJ (cnpj) ou Razão Social (nome).
=================================================================*/

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}else{
			alert( 'Preencha toda ou parte da informação para realizar a pesquisa!' );
	}
}

var checkflag = "false";
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
	var largura  = 400;
	var tamanho  = 250;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
function gerarExportacao(but){
	 if (but.value == 'Exportar Extratos' ) {
		if (confirm('Deseja realmente prosseguir com a exportação?\n\nSerá exportado somente os extratos aprovados e liberados.')){
			but.value='Exportando...';
			exportar();
		}
	} else {
		 alert ('Aguarde submissão');
	}

}

function retornaExporta(http) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					alert(results[1]);
				}else{
					alert (results[1]);
				}
			}else{
				alert ("Não existe extratos a serem exportados.");
			}
		}
	}
}

function exportar() {
	url = "<?= $PHP_SELF ?>?exportar=sim";
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaExporta(http) ; } ;
	http.send(null);
}
</script>


<script language='javascript'>

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

var http_data = new Array();
var semafaro=0;

function aprovaExtrato (extrato , posto, aprovar, novo,adicionar,acumular,resposta ) {

	if (semafaro == 1){
		alert('Aguarde alguns instantes antes de aprovar outro extrato.');
		return;
	}

	if (confirm('Deseja aprovar este extrato?')==false){
		return;
	}

	var curDateTime = new Date();
	semafaro = 1;
	url = "<?= $PHP_SELF ?>?ajax=APROVAR&aprovar=" + escape(extrato)+ "&posto=" + escape(posto)+"&data="+curDateTime;

	aprovar   = document.getElementById(aprovar);
	novo      = document.getElementById(novo);
	adicionar = document.getElementById(adicionar);
	acumular  = document.getElementById(acumular);
	resposta  = document.getElementById(resposta);

	http_data[curDateTime] = createRequestObject();
	http_data[curDateTime].open('POST',url,true);

	http_data[curDateTime].onreadystatechange = function(){
		if (http_data[curDateTime].readyState == 4){
			if (http_data[curDateTime].status == 200 || http_data[curDateTime].status == 304){

			var response = http_data[curDateTime].responseText.split(";");

				if (response[0]=="ok"){
					if (aprovar)   aprovar.src   = '/assist/imagens/pixel.gif';
					if (novo)      novo.src      = '/assist/imagens/pixel.gif';
					if (adicionar) adicionar.src = '/assist/imagens/pixel.gif';
					if (acumular)  {acumular.disabled = true; acumular.style.visibility = "hidden";}
					if (resposta)  resposta.innerHTML = "Aprovado";
				}else{
					alert('Extrato não foi aprovado. Tente novamente.');
				}
				semafaro = 0;
			}
		}
	}
	http_data[curDateTime].setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=iso-8859-1");
	http_data[curDateTime].setRequestHeader("Cache-Control", "no-store, no-cache, must-revalidate");
	http_data[curDateTime].setRequestHeader("Cache-Control", "post-check=0, pre-check=0");
	http_data[curDateTime].setRequestHeader("Pragma", "no-cache");
	http_data[curDateTime].send('');
}

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

var conta_tudo = 0;

<?
	/* HD 38185 */
	if ($login_fabrica == 35 or $login_fabrica == 15){
		echo " conta_tudo = 1;";
	}
?>

function conta_os(extrato,div,contador) {

var extrato = extrato;
var div = document.getElementById(div);

	var url = 'conta_os_ajax.php?extrato=' + extrato + '&cache_bypass=<?= $cache_bypass ?>' ;

		$.ajax({
				type: "GET",
				url: "conta_os_ajax.php?extrato=",
				data: 'extrato=' + extrato + '&cache_bypass=<?= $cache_bypass ?>',
				cache: false,
				beforeSend: function() {
					// enquanto a função esta sendo processada, você
					// pode exibir na tela uma
					// msg de carregando
					$(div).html("Espere...");
				},
				success: function(txt) {
					// pego o id da div que envolve o select com
					// name="id_modelo" e a substituiu
					// com o texto enviado pelo php, que é um novo
					//select com dados da marca x
					$(div).html(txt);
				},
				error: function(txt) {
					alert(txt);
				}
			});

	//	$(div).html(qtde);

}

function conta_os_tudo(total) {

	for (i=0;i<total;i++) {

		extrato = document.getElementById('extrato_tudo_'+i).value;

		var div = document.getElementById('qtde_os_'+i);

		$(div).html("Espere...");

		var url = 'conta_os_ajax.php?extrato=' + extrato + '&cache_bypass=<?= $cache_bypass ?>' ;

		var qtde = $.ajax({
						type: "GET",
						url: url,
						cache: false,
						async: false
		 }).responseText;

		$(div).html(qtde);
	}
}

function addCommas(nStr)
{
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}


function somarExtratos(selecionar){
	if (selecionar == 'todos'){
		$("input[@rel='somatorio']").each(function (){
			this.checked = true;
		});
	}
	var total_extratos = 0;
	$("input[@rel='somatorio']:checked").each(function (){
		if(this.checked){
			total_extratos += parseFloat(this.value);
		}
	});
	total_extratos = total_extratos.toFixed(2);
	$('#total_extratos').html('Soma dos extratos selecionados: <b>R$ '+addCommas(total_extratos)+'</b>');
}

</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="js/chili-1.8b.js"></script>
<script type="text/javascript" src="js/docs.js"></script>
<script type='text/javascript'> 
// add new widget called repeatHeaders
	$(function() {
		
		// add new widget called repeatHeaders
		$.tablesorter.addWidget({
			// give the widget a id
			id: "repeatHeaders",
			// format is called when the on init and when a sorting has finished
			format: function(table) {
				// cache and collect all TH headers
				if(!this.headers) {
					var h = this.headers = [];
					$("thead th",table).each(function(col) {
						h.push(
							"<td colspan='"+$(this).attr('colspan')+"'>" + $(this).text() + "</td>"
						);

					});
				}

				// remove appended headers by classname.
				$("tr.repated-header",table).remove();

				// loop all tr elements and insert a copy of the "headers"
				for (var i=0; i < table.tBodies[0].rows.length; i++) {
					// insert a copy of the table head every 10th row
					if((i%20) == 0) {
						if(i!=0){
						$("tbody tr:eq(" + i + ")",table).before(
							$("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

						);
					}}
				}

			}
		});
		$("table").tablesorter({
			widgets: ['zebra','repeatHeaders']
		});

	});



</script>

<?
if($btnacao){

$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

if (strlen($_GET['extrato']) > 0) $extrato = trim($_GET['extrato']);
if (strlen($_POST['extrato']) > 0) $extrato = trim($_POST['extrato']);

if (strlen($_GET['extrato_pago']) > 0)  $extrato_pago = $_GET['extrato_pago'];
if (strlen($_POST['extrato_pago']) > 0) $extrato_pago = $_POST['extrato_pago'];

// HD 49255
if (strlen($_GET['liberado']) > 0)  $xliberado = $_GET['liberado'];
if (strlen($_POST['liberado']) > 0) $xliberado = $_POST['liberado'];

if (strlen($_GET['aguardando_pagamento']) > 0)     $aguardando_pagamento = $_GET['aguardando_pagamento'];
if (strlen($_POST['aguardando_pagamento']) > 0)    $aguardando_pagamento = $_POST['aguardando_pagamento'];

if (strlen($_GET['liberacao']) > 0) $aprovacao = $_GET['liberacao'];
if (strlen($_POST['liberacao']) > 0) $aprovacao = $_POST['liberacao'];

//HD 286780
if (strlen($_POST['estado']) > 0) $estado = $_POST['estado'];
if (strlen($_GET['estado']) > 0)  $estado = $_GET['estado'];


//Início Validação de Datas
	/*if(!$data_inicial OR !$data_final)
		$msg_erro = "Data Inválida"; */
	if(!empty($data_inicial) && !empty($data_final)){
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_final);//tira a barra
			$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($nova_data_final < $nova_data_inicial){
				$msg_erro = "Data Inválida.";
			}

			//Fim Validação de Datas
		}
	}
}

echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<TABLE width='700px' align='center' border='0' cellspacing='3' class='formulario' cellpadding='2'>\n";
echo "<input type='hidden' name='btnacao' value=''>";
if(strlen($msg_erro)>0){
	echo "<TR class='msg_erro'><TD colspan='7'>$msg_erro</TD></TR>";
}
echo "<TR class='titulo_tabela'>\n";
echo "	<TD COLSPAN='7' ALIGN='center'>";
echo "		Parâmetros de Pesquisa";
echo "	</TD>";
echo "</TR>",
"<tr>
	<td>&nbsp;</td>
</tr>",
"<TR align='left'>\n";
echo "<TD width='70'>&nbsp;</TD>";
echo "	<TD ALIGN='left'>";
echo "Nº de extrato </TD><td>";
echo "	Data Inicial </td><td colspan='2'>";
echo "	Data Final </td></tr><tr align='left'><TD width='50'>&nbsp;</TD><td>";
echo "	<input type='text' name='extrato' size='12' value='$extrato' class='frm'>&nbsp;";
echo "	</TD>\n";
echo "	<TD ALIGN='left' width='130'>";

echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' rel='data' value='$data_inicial' class='frm'>\n";
echo "	</TD>\n";

echo "	<TD ALIGN='left'>";

echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' id='data_final' rel='data' value='$data_final' class='frm'>\n";
echo "</TD>";
if($login_fabrica == 6){
echo "	<TD width='20%' nowrap>";

	echo " Liberado <input type='radio' name='liberado' value='liberado'>&nbsp;&nbsp;&nbsp;Não Liberado <input type='radio' name='liberado' value='nao_liberado'>";
	echo "	</TD>";
}

echo "</TR>\n";

#HD 22758
if ($login_fabrica == 24) {

	echo "<tr>\n";
		echo "<td>&nbsp;</td>";
		echo "<td align='left'>";
			//HD 286780
			echo 'Estado <br />';
			echo '<select name="estado" id="estado" style="width:120px; font-size:9px" class="frm">';
				echo '<option value=""   ' . (strlen($estado) == 0   ? " selected " : '') . ' >TODOS OS ESTADOS</option>';
				echo '<option value="AC" ' . ($estado == "AC" ? " selected " : '') . '>AC - Acre</option>';
				echo '<option value="AL" ' . ($estado == "AL" ? " selected " : '') . '>AL - Alagoas</option>';
				echo '<option value="AM" ' . ($estado == "AM" ? " selected " : '') . '>AM - Amazonas</option>';
				echo '<option value="AP" ' . ($estado == "AP" ? " selected " : '') . '>AP - Amapá</option>';
				echo '<option value="BA" ' . ($estado == "BA" ? " selected " : '') . '>BA - Bahia</option>';
				echo '<option value="CE" ' . ($estado == "CE" ? " selected " : '') . '>CE - Ceará</option>';
				echo '<option value="DF" ' . ($estado == "DF" ? " selected " : '') . '>DF - Distrito Federal</option>';
				echo '<option value="ES" ' . ($estado == "ES" ? " selected " : '') . '>ES - Espírito Santo</option>';
				echo '<option value="GO" ' . ($estado == "GO" ? " selected " : '') . '>GO - Goiás</option>';
				echo '<option value="MA" ' . ($estado == "MA" ? " selected " : '') . '>MA - Maranhão</option>';
				echo '<option value="MG" ' . ($estado == "MG" ? " selected " : '') . '>MG - Minas Gerais</option>';
				echo '<option value="MS" ' . ($estado == "MS" ? " selected " : '') . '>MS - Mato Grosso do Sul</option>';
				echo '<option value="MT" ' . ($estado == "MT" ? " selected " : '') . '>MT - Mato Grosso</option>';
				echo '<option value="PA" ' . ($estado == "PA" ? " selected " : '') . '>PA - Pará</option>';
				echo '<option value="PB" ' . ($estado == "PB" ? " selected " : '') . '>PB - Paraíba</option>';
				echo '<option value="PE" ' . ($estado == "PE" ? " selected " : '') . '>PE - Pernambuco</option>';
				echo '<option value="PI" ' . ($estado == "PI" ? " selected " : '') . '>PI - Piauí</option>';
				echo '<option value="PR" ' . ($estado == "PR" ? " selected " : '') . '>PR - Paraná</option>';
				echo '<option value="RJ" ' . ($estado == "RJ" ? " selected " : '') . '>RJ - Rio de Janeiro</option>';
				echo '<option value="RN" ' . ($estado == "RN" ? " selected " : '') . '>RN - Rio Grande do Norte</option>';
				echo '<option value="RO" ' . ($estado == "RO" ? " selected " : '') . '>RO - Rondônia</option>';
				echo '<option value="RR" ' . ($estado == "RR" ? " selected " : '') . '>RR - Roraima</option>';
				echo '<option value="RS" ' . ($estado == "RS" ? " selected " : '') . '>RS - Rio Grande do Sul</option>';
				echo '<option value="SC" ' . ($estado == "SC" ? " selected " : '') . '>SC - Santa Catarina</option>';
				echo '<option value="SE" ' . ($estado == "SE" ? " selected " : '') . '>SE - Sergipe</option>';
				echo '<option value="SP" ' . ($estado == "SP" ? " selected " : '') . '>SP - São Paulo</option>';
				echo '<option value="TO" ' . ($estado == "TO" ? " selected " : '') . '>TO - Tocantins</option>';
			echo '</select>';

		echo "</td>";
		echo "<td colspan='2'></td>";
	echo "</tr>\n";

	echo "<tr>\n";
		echo "<td></td>";
		echo "<td colspan= '2' align='left'>";

			echo "<table align='left'>";
				echo "<tr>\n";
					echo "<td><input type='checkbox' name='extrato_pago' value='t' ".(($extrato_pago=='t')?"checked":"")."> Extratos pagos <span style='color:#515151;font-size:10px' title='É obrigatório digitar a data inicial e a data final'> (Período obrigatório) </span></TD>\n";
				echo "</tr>\n";
				echo "<tr>\n";
					echo "<TD><input type='checkbox' name='aguardando_pagamento' value='t' ".(($aguardando_pagamento=='t')?"checked":"")."> Extratos aguardando pagamento <span style='color:#515151;font-size:10px'> (Período opcional) </span></TD>\n";
				echo "</tr>\n";
			echo "</table>";

		echo "</td>\n";
		echo "<td></td>";
	echo "</tr>\n";

}
echo "<TR align='left'>";
echo "<TD width='50'>&nbsp;</TD>";
echo "	<TD>";
echo "CNPJ</td>", 
"<td colspan='2'>Razão Social</tr><tr align='left'><TD width='50'>&nbsp;</TD><td>";
echo "<input type='text' name='posto_codigo' id='posto_codigo' size='18' value='$posto_codigo' class='frm' onkeypress='javascript:somente_numero(this);' onKeyUp=\"formata_cnpj(this.value, 'frm_extrato');\" maxlength='18'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'cnpj')\"></td><td colspan='2'>";


echo "		<input type='text' name='posto_nome' id='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";


if ($login_fabrica == 15) { ?>
	<tr>
		<td colspan='4' align='left'>
			<input type='checkbox' value='t' name='liberacao' <?PHP if  ($liberacao == 't') {?> checked <?PHP }?>>
				Mostrar somente extratos para liberação.
		</td>
	</tr>
<?PHP }

if($login_fabrica == 20){
// MLG 2009-08-04 HD 136625
	$sql = "SELECT pais,nome FROM tbl_pais where pais <> 'BR'";    $res = pg_query($con,$sql);
	$p_tot = pg_num_rows($res);
	for ($i; $i<$p_tot; $i++) {
		list($p_code,$p_nome) = pg_fetch_row($res, $i);
		$sel_paises .= "\t\t\t\t<option value='$p_code'";
		$sel_paises .= ($pais==$p_code)?" selected":"";
		$sel_paises .= ">$p_nome</option>\n";
	}
?>
	<tr class="Conteudo" bgcolor="#D9E2EF" >
		<td colspan='4' align='center'>País
			<select name='pais' size='1' class='frm'>
			<option value="BR">Brasil</option>
			<?echo $sel_paises;?>
			</select>
		</td>
	</tr>
<?}



echo "<tr><td colspan='4'><input type=\"button\" width:95px; cursor:pointer;\" value=\"Filtrar\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\"></td></tr>";

echo "</form>";
echo "</TABLE>\n";

// INICIO DA SQL
if ($btnacao AND strlen($msg_erro) == 0) {

	$data_inicial = $_POST['data_inicial'];
	if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

	$data_final   = $_POST['data_final'];
	if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

	$posto_codigo = $_POST['posto_codigo'];
	if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
	if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

	$data_inicial = str_replace (" " , "" , $data_inicial);
	$data_inicial = str_replace ("-" , "" , $data_inicial);
	$data_inicial = str_replace ("/" , "" , $data_inicial);
	$data_inicial = str_replace ("." , "" , $data_inicial);

	$data_final = str_replace (" " , "" , $data_final);
	$data_final = str_replace ("-" , "" , $data_final);
	$data_final = str_replace ("/" , "" , $data_final);
	$data_final = str_replace ("." , "" , $data_final);

	if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
	if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

	if (strlen ($data_inicial) > 0) $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
	if (strlen ($data_final)   > 0) $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);

	$pais = $_POST['pais'];
	if (strlen($_GET['pais']) > 0) $pais = $_GET['pais'];

	$cond_extrato = "";

	if (strlen($extrato) > 0) {
		if ($login_fabrica <> 1 AND $login_fabrica <> 19) {
			$cond_extrato = " AND EX.extrato = $extrato";
		} else {
			$cond_extrato = " AND EX.protocolo = '$extrato'";
		}
	}

if (($login_fabrica == 15 AND $liberacao == 't') or strlen ($posto_codigo) > 0 OR (strlen ($data_inicial) > 0 and strlen ($data_final) > 0) OR strlen($extrato) > 0 OR $aguardando_pagamento == 't') {

	if ($login_fabrica == 1) $add_1 = " AND       EX.aprovado IS NULL ";

	//--== INICIO - Consulta por data ===============================================
	// hd 26685
	if(strlen ($data_inicial) > 0 AND strlen ($data_final) > 0 AND strlen($extrato) == 0){
			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	}

	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0 AND strlen($extrato) == 0) {
		$add_2 = " AND      EX.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	}
	//--== FIM - Consulta por data ==================================================

	#HD 22758
	if ($aguardando_pagamento == 't') {
		if (strlen($_GET['data_inicial'])==0 or strlen($_GET['data_final'])==0){
			$data_inicial   = "";
			$data_final     = "";
			$x_data_inicial = "";
			$x_data_final   = "";
			$add_2          = "";
		}
		$add_1 = "  AND       EP.extrato_pagamento IS NULL
					AND       EX.aprovado       IS NOT NULL ";
	}

	#HD 22758
	if ($extrato_pago == 't'){
		$add_5 = " AND       EP.data_pagamento IS NOT NULL ";
	}

	if ($login_fabrica == 15 AND $liberacao == 't') {
			$add_6 = " AND liberado IS NULL";
	}

	if($login_fabrica == 6) {
		if($liberado == 'liberado') {
			$add_6 = " AND liberado IS NOT NULL";
		}
		if($liberado == 'nao_liberado') {
			$add_6 = " AND liberado IS NULL";
		}
	}

	if($login_fabrica == 20) {
		$add_7 = " AND liberado_telecontrol IS not null ";
	}

	if (strlen($estado) > 0) {
		$add_8 = " AND PO.estado = '$estado' ";
	}

	//--== INICIO - Consulta por data ===============================================
	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);

	if (strlen ($posto_codigo) > 0 OR strlen ($posto_nome) > 0 ){
		$sql = "SELECT posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE fabrica = $login_fabrica ";
		if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xposto_codigo' ";
		if (strlen ($posto_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";
//		echo $sql;
		$res = pg_query ($con,$sql);
		$posto = pg_fetch_result($res,0,0);
		$add_3 = " AND EX.posto = $posto " ;
	}
	//--== FIM - Consulta por Posto ==============================================

	if($login_fabrica == 20) $add_4 = " AND PO.pais = '$pais' ";

	$sql = "SELECT DISTINCT
					PO.posto                                                 ,
					PO.nome                                                  ,
					PO.cnpj                                                  ,
					PO.estado                                                ,
					PF.contato_email                                     AS email        ,
					PF.codigo_posto                                          ,
					PF.distribuidor                                          ,
					PF.imprime_os                                            ,
					TP.descricao                                         AS tipo_posto   ,
					EX.extrato                                               ,
					EX.bloqueado                                             ,
					EX.liberado                                              ,
					EX.estoque_menor_20                                      ,
					TO_CHAR (EX.aprovado,'dd/mm/yyyy')                   AS aprovado     ,
					LPAD (EX.protocolo,6,'0')                            AS protocolo    ,
					TO_CHAR (EX.data_geracao,'dd/mm/yyyy')               AS data_geracao ,
					EX.data_geracao                                      AS xdata_geracao,
					EX.total                                                 ,
					EX.pecas                                                 ,
					EX.mao_de_obra                                           ,
					EX.avulso                                             AS avulso       ,
					EX.recalculo_pendente                                    ,
					EP.nf_autorizacao                                        ,
					TO_CHAR (EX.previsao_pagamento,'dd/mm/yyyy')          AS previsao_pagamento,
					TO_CHAR (EX.data_recebimento_nf,'dd/mm/yyyy')         AS data_recebimento_nf,
					TO_CHAR (EP.data_pagamento,'dd/mm/yyyy')              AS baixado      ,
					EP.valor_liquido                                         ,
					EE.nota_fiscal_devolucao                                 ,
					EE.nota_fiscal_mao_de_obra                               ,
					to_char(EE.data_coleta,'dd/mm/yyyy')                 AS  data_coleta     ,
					to_char(EE.data_entrega_transportadora,'dd/mm/yyyy') AS  data_entrega_transportadora,
					to_char(EE.emissao_mao_de_obra,'dd/mm/yyyy')         AS  emissao_mao_de_obra,
					tbl_admin.nome_completo
			INTO    TEMP tmp_extrato_consulta /*hd 39502*/
			FROM      tbl_extrato           EX
			JOIN      tbl_posto             PO USING (posto)
			JOIN      tbl_posto_fabrica     PF ON EX.posto      = PF.posto      AND PF.fabrica = $login_fabrica
			JOIN      tbl_tipo_posto        TP ON TP.tipo_posto = PF.tipo_posto AND TP.fabrica = $login_fabrica
/*			LEFT JOIN tbl_os_extra          OE ON OE.extrato    = EX.extrato*/
			LEFT JOIN tbl_extrato_pagamento EP ON EX.extrato    = EP.extrato
			LEFT JOIN tbl_extrato_extra     EE ON EX.extrato    = EE.extrato
			LEFT JOIN tbl_admin ON EE.admin = tbl_admin.admin
			WHERE     EX.fabrica = $login_fabrica
			AND       PF.distribuidor IS NULL
			$cond_extrato
			$add_1
			$add_2
			$add_3
			$add_4
			$add_5
			$add_6
			$add_7
			$add_8
			";
	$sql .= ($login_fabrica <> 1) ? " ORDER BY PO.nome, EX.data_geracao" : " ORDER BY PF.codigo_posto, EX.data_geracao";
	$res = pg_query ($con,$sql);

	/* hd 39502 */
	if ($login_fabrica==20) {
		$sql = "ALTER table tmp_extrato_consulta add column total_cortesia double precision";
		$res = pg_query ($con,$sql);

		$sql = "UPDATE tmp_extrato_consulta SET
					total_cortesia = (
						SELECT sum(tbl_os.mao_de_obra) + sum(tbl_os.pecas)
						FROM tbl_os
						JOIN tbl_os_extra USING(os)
						WHERE extrato = tmp_extrato_consulta.extrato
						AND   tbl_os.tipo_atendimento = 16
					)";
		$res = pg_query ($con,$sql);
	}

	$sql = "SELECT * FROM tmp_extrato_consulta";
	$res = pg_query ($con,$sql);

	$qtde_extratos = pg_num_rows ($res);

	if ($qtde_extratos == 0) {
		echo "<center><div style='font-family : arial; color: #000000; font-size: 12px'>Não Foram Encontrados Resultados para esta Pesquisa</div></center>";
	}
	if (pg_num_rows ($res) > 0) {

		$legenda_avulso="";
		if($login_fabrica == 20 ) {
			$legenda_avulso=" (Também Identifica Imposto para paises da América Latina)";
		}

		//HD 237498: Marcando os extratos que possuem OS em intervençao de KM em aberto
		if (in_array($login_fabrica, $intervencao_km_extrato)) {
			echo "<table width='700px' class='tabela' border='0' cellspacing='0' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' width='16' bgcolor='#FFCC99'>&nbsp;</td>";
			echo "<td align='left'><&nbsp; OS com Intervenção de KM em aberto</td>";
			echo "</tr><br>";
		}

		echo "<br /><table width='700px' border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'>&nbsp; Extrato Avulso $legenda_avulso</td>";
		echo "</tr>";

		if($login_fabrica==6){//hd 3471
			echo "<tr>";
			echo "<td align='center'>&nbsp;</td>";
			echo "<td align='left'>&nbsp; Extrato com variação superior a 15%</td>";
			echo "</tr>";
		}
		if($login_fabrica==1){
			echo "<tr>";
			echo "<td align='center' width='16'>&nbsp;</td>";
			echo "<td align='left'>&nbsp; Extrato Bloqueado</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='16' >&nbsp;</td>";
			echo "<td align='left'>&nbsp; Extrato do Posto com itens de estoque menor que 20s</td>";
			echo "</tr>";

		}
		echo "</table>";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$posto                   = trim(pg_fetch_result($res,$i,posto));
			$codigo_posto            = trim(pg_fetch_result($res,$i,codigo_posto));
			$nome                    = trim(pg_fetch_result($res,$i,nome));
			$posto_estado            = trim(pg_fetch_result($res,$i,estado));
			$email                   = trim(pg_fetch_result($res,$i,email));
			$tipo_posto              = trim(pg_fetch_result($res,$i,tipo_posto));
			$extrato                 = trim(pg_fetch_result($res,$i,extrato));
			$data_geracao            = trim(pg_fetch_result($res,$i,data_geracao));
			//$qtde_os                 = trim(pg_fetch_result($res,$i,qtde_os));
			$total                   = trim(pg_fetch_result($res,$i,total));
			$nf_autorizacao          = trim(pg_fetch_result($res,$i,nf_autorizacao));
			$previsao_pagamento      = trim(pg_fetch_result($res,$i,previsao_pagamento));
			$data_recebimento_nf     = trim(pg_fetch_result($res,$i,data_recebimento_nf));
			$baixado                 = trim(pg_fetch_result($res,$i,baixado));
			$extrato                 = trim(pg_fetch_result($res,$i,extrato));
			$distribuidor            = trim(pg_fetch_result($res,$i,distribuidor));
			$xtotal                  = round($total);
			$soma_total = $soma_total + $total; //HD 49532
			$total	                 = number_format ($total,2,',','.');

			/* hd 39502 */
			if ($login_fabrica == 20) {
				$total_cortesia = trim(pg_fetch_result($res,$i,total_cortesia));
				$total_cortesia = number_format ($total_cortesia,2,',','.');
			}

			$liberado                    = trim(pg_fetch_result($res,$i,liberado));
			$aprovado                    = trim(pg_fetch_result($res,$i,aprovado));
			$estoque_menor_20            = trim(pg_fetch_result($res,$i,estoque_menor_20));
			$protocolo                   = trim(pg_fetch_result($res,$i,protocolo));
			$nota_fiscal_devolucao       = trim(pg_fetch_result($res,$i,nota_fiscal_devolucao));
			$nota_fiscal_mao_de_obra     = trim(pg_fetch_result($res,$i,nota_fiscal_mao_de_obra));
			$data_coleta                 = trim(pg_fetch_result($res,$i,data_coleta));
			$data_entrega_transportadora = trim(pg_fetch_result($res,$i,data_entrega_transportadora));
			$xdata_geracao               = trim(pg_fetch_result($res,$i,xdata_geracao));
			$bloqueado                   = trim(pg_fetch_result($res,$i,bloqueado));
			$recalculo_pendente          = trim(pg_fetch_result($res,$i,recalculo_pendente));

			$pecas              = trim(pg_fetch_result($res,$i,pecas));
			$mao_de_obra        = trim(pg_fetch_result($res,$i,mao_de_obra));
			$avulso             = trim(pg_fetch_result($res,$i,avulso));

			$pecas       = number_format($pecas,2,',','.');
			$mao_de_obra = number_format($mao_de_obra,2,',','.');
			$avulso      = number_format($avulso,2,',','.');
			
			//HD 145478: Nome do admin que aprovou o extrato
			$auditor = pg_fetch_result($res, $i, nome_completo);

			//HD 12104
			if ($login_fabrica == 14) {
				$imprime_os          = trim(pg_fetch_result($res,$i,imprime_os));
				$emissao_mao_de_obra = trim(pg_fetch_result($res,$i,emissao_mao_de_obra));// HD 209349
			}

			$msg_os_deletadas="";

			if (trim(pg_fetch_result($res,$i,valor_liquido)) <> '') {
				$valor_liquido = number_format (trim(pg_fetch_result($res,$i,valor_liquido)),2,',','.');
			} else {
				$valor_liquido = number_format (trim(pg_fetch_result($res,$i,total)),2,',','.');
			}

			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";

			if ($login_fabrica == 15 or $login_fabrica == 35) {
				$totalreg=pg_num_rows ($res);
				echo "<a href=\"javascript:conta_os_tudo($totalreg);\" id='conta_os_$i'>VER TUDO</a>";
			}

			echo "<table width='700px' align='center' border='0' cellspacing='0' cellpadding='2' class='tabela'>\n";
			echo "<tr class='titulo_coluna'>";
		//	if ($login_fabrica == 14)				
				
				if ($login_fabrica == 24) {
					echo "<td align='center' nowrap>Soma <input type='checkbox' onClick=\"somarExtratos('todos')\"></td>";
				}
				echo "<td align='center'>Código</td>";
				if ($login_fabrica == 51 or $login_fabrica == 81) {
					echo "<td align='center'>Lote/NF</td>";
				}
				echo "<td align='center' class='titulo_coluna' nowrap>Nome do Posto</td>\n";
				echo "<td align='center' class='titulo_coluna' nowrap>UF</td>\n";
				if ($login_fabrica == 1) echo "<td align='center' nowrap>Tipo</td>\n";
				echo ($login_fabrica == 1 OR $login_fabrica == 19) ? "<td align='center'>Protocolo</td>\n" : "<td align='center'>Extrato</td>\n";
				echo "<td align='center'>Data</td>\n";
				echo "<td align='center' nowrap>Qtde OS</td>\n";
				
				if ($login_fabrica == 1) {
					
					echo "<td align='center'>Total Peça</td>\n";
					echo "<td align='center'>Total MO</td>\n";
					echo "<td align='center'>Total Avulso</td>\n";
					echo "<td align='center'>Total Geral</td>\n";
					echo "<td align='center'>Obs.</td>\n";
				
				} else {

					//hd 39502
					if ($login_fabrica == 20) {
						echo "<td align='center'>Total cortesia</td>\n";
						echo "<td align='center'>Total geral</td>\n";
					} else {
						echo "<td align='center'>Total</td>\n";
					}

					if ($login_fabrica == 6) {//hd 3471
						echo "<td align='center'><acronym title='Média de valor pago nos últimos 6 meses' style='cursor: help;'>Média</td>\n";

					}
					// SONO - 04/09/206 exibir valor_liquido para intelbras //
					if ($login_fabrica == 14) {
						echo "<td align='center' nowrap>Total Líquido</td>\n";
					}
				}

				if ($login_fabrica == 20) {
					echo "<td align='center'>N.F.<br />M. De Obra</td>\n";
					echo "<td align='center'>N.F.<br />Remessa</td>\n";
					echo "<td align='center'>Data<br />Coleta</td>\n";
					echo "<td align='center'>Entrega<br />Transportadora</td>\n";
				}
				
				if ($login_fabrica == 14) {//HD 209349
					echo "<td align='center'>N.F.<br />M. De Obra</td>\n";
					echo "<td align='center'>Data<br />Envio NF</td>\n";
					echo "<td align='center'>Data<br />Recebimento NF</td>\n";
				}

				if($login_fabrica == 45) echo "<td align='center' nowrap>Nota Fiscal</td>";

				if($login_fabrica == 20) echo "<td align='center'>Auditado em</td>";
				if($login_fabrica == 20) echo "<td align='center'>Auditor</td>";
				else                     echo "<td align='center'><label title='Data de Pagamento'>Data Baixa</td>\n";
				
				if (in_array($login_fabrica,array(6,7,14, 15, 11 , 24, 25, 35, 40, 50, 43, 51,46, 47, 74, 59, 30)) or $login_fabrica > 51) {
					if ($recalculo_pendente == 't') {
						echo "<td align='center'>*Aguardando recalculo</td>\n";
					} else {
						echo "<td align='center'>Liberar <input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.liberar);'></td>\n";
					}
					if ($login_fabrica == 11 OR $login_fabrica == 25) echo "<td align='center' nowrap>Posto sem<br />email</td>\n";
				}
				
				if ($login_fabrica == 1) {
					echo "<td align='center'>Acumular <input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.acumular);'></td>\n";
				}
				
				echo "<td align='center' colspan='3'>Valores Adicionais ao Extrato</td>\n";
				
				if ($login_fabrica == 50 or $login_fabrica == 15) {
					echo "<td align='center'>Previsao de Pagamento</td>";
					echo "<td align='center'>Data Chegada</td>";
				}
				
				if ($login_fabrica == 45) {//HD 66773
					echo "<td align='center'>Acumular</td>";
				}
				
				// hd 12104
				if ($login_fabrica == 14) {
					echo "<td align='center'>Liberar 10%</td>";
				}
				
				if ($login_fabrica == 35) {
					echo "<td align='center'>Ações</td>";
				}
				echo "</tr>";
				
				echo "<tbody>";
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

			}
			##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

			//HD 237498: Marcando os extratos que possuem OS em intervençao de KM em aberto
			if (in_array($login_fabrica, $intervencao_km_extrato)) {
				$km_pendente = verifica_km_pendente_extrato($extrato);

				if ($km_pendente) {
					$cor = "#FFCC99";
				}
			}

			if ($login_fabrica == 6) {//hd 3471
				$ssql = "SELECT sum(X.total) as total, count(total) as qtde
						FROM (
						select posto,
						total
						from tbl_extrato
						where fabrica = $login_fabrica
						and posto = $posto
						and data_geracao < '$xdata_geracao'
						order by extrato
						desc limit 6) as X";
				$rres = pg_query($con,$ssql);
				if(pg_num_rows($rres)>0){
					$total_acumulado = pg_fetch_result($rres,0,total);
					$qtde = pg_fetch_result($rres,0,qtde);
					if($qtde>0){
						$total_acumulado = $total_acumulado/$qtde;
						if($xtotal>round($total_acumulado*1.15)){//hd 3471
							$cor = "#FFCC99";
						}
					}
				}
			}
			echo "<tr bgcolor='$cor'>\n";

			if ($login_fabrica == 24) {
				echo "<td align='center' nowrap><input type='checkbox' name='extrato_$i' rel='somatorio' value='$xtotal' onClick='somarExtratos()'></td>\n";
			}
			echo "<td align='left'>$codigo_posto</td>\n";
			
			if (strlen($extrato) > 0 and ($login_fabrica == 51 or $login_fabrica ==81)) {
				$sqllote = "SELECT tbl_distrib_lote_os.os, tbl_distrib_lote.lote,
							tbl_distrib_lote_os.nota_fiscal_mo
							FROM tbl_distrib_lote_os
							JOIN tbl_os_extra USING(os)
							JOIN tbl_distrib_lote using(distrib_lote)
						WHERE tbl_os_extra.extrato = $extrato";
				$reslote = pg_query($con,$sqllote);
				if(pg_num_rows($reslote) > 0){
					$lote = trim(pg_fetch_result($reslote,0,lote));
					$nota_fiscal_mo = trim(pg_fetch_result($reslote,0,nota_fiscal_mo));
					echo "<td align='center' nowrap>$lote - $nota_fiscal_mo</td>\n";
				}else{
					echo "<td align='center' nowrap>&nbsp;</td>\n";
				}
			}

			echo "<td align='left' nowrap>".substr($nome,0,20)."</td>\n";
			echo "<td align='center' nowrap>".$posto_estado."</td>\n";
			if ($login_fabrica == 1) echo "<td align='center' nowrap>$tipo_posto</td>\n";
			if($login_fabrica == 20 ){echo "<td align='center'><a href='extrato_os_aprova";
			}else{
				echo "<td align='center' ";
				if($bloqueado == "t" and $login_fabrica == 1){
					echo " bgcolor='#FF9E5E' ";
				}
				echo "><a href='extrato_consulta_os";
			}
			if ($login_fabrica == 14) echo "_intelbras";
			echo ".php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome' target='_blank'>";
			echo ($login_fabrica == 1 OR $login_fabrica == 19 ) ? $protocolo : $extrato;
			echo "</a></td>\n";

			//IGOR - HD 6924 04/03/2008
			$cor_estoque_menor = "";
			if ($estoque_menor_20 == "t" and $login_fabrica == 1) {
				$cor_estoque_menor = " bgcolor='#CCFF66' ";
			}
			echo "<td align='left' $cor_estoque_menor>$data_geracao</td>\n";

			echo "<td align='center' title='Clique aqui para ver a quantidade de OS'><div id='qtde_os_$i'><a href=\"javascript:conta_os($extrato,'qtde_os_$i','".($i+1)."');\" id='conta_os_$i'>VER</a></div><input type='hidden' name='extrato_tudo_$i' id='extrato_tudo_$i' value='$extrato'></td>\n";
			//--== FIM - QTDE de OS no extrato =========================================================

			if ($login_fabrica == 1) {
				$sql =	"SELECT SUM(tbl_os.pecas)       AS total_pecas     ,
								SUM(tbl_os.mao_de_obra) AS total_maodeobra ,
								tbl_extrato.avulso      AS total_avulso
						FROM tbl_os
						JOIN tbl_os_extra USING (os)
						JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
						WHERE tbl_os_extra.extrato = $extrato
						GROUP BY tbl_extrato.avulso;";
				$resT = pg_query($con,$sql);

				if (pg_num_rows($resT) == 1) {
					echo "<td align='right' nowrap> " . number_format(pg_fetch_result($resT,0,total_pecas),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap> " . number_format(pg_fetch_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap> " . number_format(pg_fetch_result($resT,0,total_avulso),2,',','.') . "</td>\n";
				}else{
					echo "<td>&nbsp;$pecas</td>\n";
					echo "<td>&nbsp;$mao_de_obra</td>\n";
					echo "<td>&nbsp;$avulso</td>\n";
				}
			}

			//hd 39502
			if ($login_fabrica==20) {
				echo "<td align='right' nowrap> $total_cortesia</td>\n";
			}

			//TOTAL EXTRATO
			echo "<td align='right' nowrap> $total</td>\n";

			if ($login_fabrica == 6) {//hd 3471
				echo "<td align='center' nowrap>".number_format($total_acumulado,2,',','.') . "</td>";
			}
			
			// SONO - 04/09/206 exibir valor_liquido para intelbras //
			if ($login_fabrica == 14) {
				echo "<td align='right' nowrap> $valor_liquido</td>\n";
			}

			if ($login_fabrica == 1) echo "<td><a href=\"javascript: AbrirJanelaObs('$extrato');\">OBS.</a></td>\n";

			if ($login_fabrica == 20 || $login_fabrica == 14) {
				echo "<td align='center'><INPUT TYPE='text' NAME='nota_fiscal_mao_de_obra_$i' id='nota_fiscal_mao_de_obra_$i' value='$nota_fiscal_mao_de_obra' size='8' maxlength='16'"; if (strlen($aprovado) > 0 && $login_fabrica != 14) echo " readonly"; echo "></td>";
				if ($login_fabrica == 20) {
					echo "<td align='center'><INPUT TYPE='text' NAME='nota_fiscal_devolucao_$i' id='nota_fiscal_devolucao_$i' value='$nota_fiscal_devolucao' size='8' maxlength='16'"; if (strlen($aprovado)>0) echo " readonly"; echo "></td>";
					echo "<td align='center'>$data_coleta</td>"; #HD 219942
				} else {
					echo "<INPUT TYPE='hidden' NAME='nota_fiscal_devolucao_$i' id='nota_fiscal_devolucao_$i' value='$nota_fiscal_devolucao' size='8' maxlength='16'"; if (strlen($aprovado)>0) echo " readonly"; echo ">";
				}
				if ($login_fabrica == 14) {
					echo "<td align='center'>$emissao_mao_de_obra</td>"; #HD 209349
				}
				echo "<td align='center'><INPUT size='12' maxlength='10' TYPE='text' NAME='data_entrega_transportadora_$i' class='data_entrega_transportadora' id='data_entrega_transportadora_$i' rel='data2' value='$data_entrega_transportadora'"; if (strlen($aprovado) > 0 && $login_fabrica != 14) echo " disabled"; echo "></td>";
			}

			if ($login_fabrica == 45) echo "<td align='center'>$nf_autorizacao</td>";

			if ($login_fabrica == 20) echo "<td align='left'>$aprovado</td>";
			else                      echo "<td align='left'>$baixado</td>\n";

			//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
			//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
			//			 de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
			//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS

			if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
				echo "<td align='center' nowrap>";
				//Extrato não aprovado, pode aprovar se já estiver liberado
				if (strlen($aprovado) == 0) {
					if (strlen($liberado) == 0) {
						if ($recalculo_pendente == 't') {
							echo "*Aguardando recalculo\n";
						} else {
							echo "<a href=\"javascript:window.location = '$PHP_SELF?liberar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome&msg_aviso='+document.Selecionar.msg_aviso.value \">Liberar</a>";
//							echo " <input type='checkbox' class='frm' name='liberar_$i' id='liberar' value='$extrato'>";
						}
					} else {
						echo "<a href='$PHP_SELF?aprovar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'><img src='imagens_admin/btn_aprovar_azul.gif' id='img_aprovar_$i' ALT='Aprovar o extrato'></a>";
					}
				} else {//Extrato já aprovado, não pode mais modificar
				}
				echo "</td>\n";
			} elseif (in_array($login_fabrica,array(6,7,14,15,11,24,25,35,40,50,43,51,46,47,74,59,30,45)) or $login_fabrica > 51) {//HD 205958: Rotina antiga
				echo "<td align='center' nowrap>";
				if (strlen($liberado) == 0) {
					if($recalculo_pendente == 't'){
						echo "*Aguardando recalculo\n";
					}else{
						echo "<a href=\"javascript:window.location = '$PHP_SELF?liberar=$extrato&msg_aviso='+document.Selecionar.msg_aviso.value \">Liberar</a>";
						echo " <input type='checkbox' class='frm' name='liberar_$i' id='liberar' value='$extrato'>";
					}
				}
				echo "</td>\n";
			}

			if ($login_fabrica == 11 OR $login_fabrica == 25) {
				echo "<td align='center' nowrap>";
				if (strlen($email) == 0) {?>
					<center>
					<input type='button' value='Imprimir' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;' /><?php
				} else {
					echo "&nbsp;";
				}
				echo "</td>\n";
			}
			if ($login_fabrica == 24) {
				echo "<td align='center' nowrap>";
				if (strlen($email) == 0) {?>
					<center>
					<input type='button' value='Imprimir' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;' /><?php
				} else {
					echo "&nbsp;";
				}
				echo "</td>\n";
			}
			
			if ($login_fabrica == 20){ echo "<td nowrap>$auditor</td>"; }

			if (in_array($login_fabrica,array(1,2,8,20,30,40,47,14))) {
				if ($msg_os_deletadas == "") {
					echo "<td align='center' nowrap>";
					if (strlen($aprovado) == 0 || $login_fabrica == 14) {
						if ($login_fabrica == 20 || $login_fabrica == 14) {
							echo "<a href=\"javascript:if(confirm('Deseja aprovar todas as OS´s deste extrato? '))window.location='$PHP_SELF?aprovar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome&nf_mao_de_obra='+document.getElementById('nota_fiscal_mao_de_obra_$i').value+'&nf_devolucao='+document.getElementById('nota_fiscal_devolucao_$i').value+'&data_entrega_transportadora='+document.getElementById('data_entrega_transportadora_$i').value\">";
							echo "<img src='imagens_admin/btn_aprovar_azul.gif' ALT='Aprovar o extrato'></a>";
						} else {
							if ($login_fabrica == 1) {
								echo "<a href=\"javascript:aprovaExtrato($extrato,$posto,'img_aprovar_$i','img_novo_$i','img_adicionar_$i','acumular_$i','resposta_$i');\"><img src='imagens_admin/btn_aprovar_azul.gif' id='img_aprovar_$i' ALT='Aprovar o extrato'></a><span id='resposta_$i'></span>";
							} else {
								echo "<a href='$PHP_SELF?aprovar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'><img src='imagens_admin/btn_aprovar_azul.gif' id='img_aprovar_$i' ALT='Aprovar o extrato'></a>";
							}
						}
						if ($login_fabrica <> 20 and $login_fabrica <> 47) {
							echo "<input type='checkbox' name='acumular_$i' id='acumular' value='$extrato' class='frm'>\n";
						}
					}
					echo "</td>\n";
				}
			}

			// se o msg_os_deletadas for nulo o extrato não foi cancelado. Se não for nulo, o Extrato foi cancelado
			if ($msg_os_deletadas == "") {
				echo "<td>";
				if (strlen($aprovado) == 0 OR $login_fabrica == 8 or $login_fabrica == 81)
					echo "<a href='extrato_avulso.php'><img src='imagens/btn_novo_azul.gif' id='img_novo_$i' ALT='Cadastrar um Novo Extrato'></a>";
				echo "</td>\n";
				echo "<td>";
				if (strlen($aprovado) == 0 OR $login_fabrica == 8 or $login_fabrica == 81)
					echo "<a href='extrato_avulso.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_adicionar_azul.gif' id='img_adicionar_$i' ALT = 'Lançar itens no extrato'></a>";
				echo "</td>\n";
				if ($login_fabrica == 45) {
					echo "<td>";
					echo (strlen($aprovado)==0) ? "<input type='checkbox' name='acumular_$i' id='acumular' value='$extrato'	class='frm'>\n" : "&nbsp;";
					echo "</td>";
				}
			} else { //só entra aqui se o extrato foi excluido e a fabrica eh 2-  DYNACON
				echo "<td colspan='3' align='center'>";
				echo "<b style='font-size:10px;color:red'>Extrato cancelado!!</b>";
				echo "</td>";
				echo "</tr>";
				echo "<tr>";
				echo		 "<td></td>";
				echo 		"<td colspan=9 align='left'> <b style='font-size:12px;font-weight:normal'>$msg_os_deletadas</b> </td>";
				echo 	"</td>";
			}

			if ($login_fabrica == 50 or $login_fabrica == 15) {
				echo "<td></td><td align='center'>$previsao_pagamento</td>";
				echo "<td align='center'>$data_recebimento_nf</td>";
			}

			// HD12104
			if ($login_fabrica == 14)	{
				//echo "<td align='center' nowrap>&nbsp;</td>";
				echo "<td align='center' nowrap>";
				echo " <input type='checkbox' class='frm' name='imprime_os_$i' value='$extrato'";
				if($imprime_os == 't') echo " checked ";
				echo " >";
				echo "</td>\n";
			}

			if ($login_fabrica == 35) {
				echo "<td></td><td align='center'><a href='os_extrato_pecas_retornaveis_cadence.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_azul.gif'></a></td>";
			}

			echo "</tr>\n";
			flush();
		}

		if ($login_fabrica == 50) { //HD 49532 11/11/2008
			$xsoma_total = number_format($soma_total,2, ",", ".");
			echo "<tr bgcolor='$cor'>\n";
				echo "<td colspan='5' align='right'><B>TOTAL</B></td>\n";
				echo "<td>$xsoma_total</td>\n";
				echo "<td colspan='4' align='right'>&nbsp;</td>\n";
			echo "</tr>\n";
		}

		echo "<tr>\n";
		if ($login_fabrica == 11) {
			echo "<td colspan='7'>
				Quando um extrato é liberado, automaticamente é enviado um email para o posto. Se quiser acrescentar uma mensagem digite no campo abaixo.
				<br>
				<INPUT size='60' TYPE='text' NAME='msg_aviso' value=''>
			</td>\n";
		} elseif ($login_fabrica == 24) {
			echo "<td colspan='5'><span id='total_extratos' style='font-size:14px'></span></td>\n";
			echo "<td colspan='2'></td>\n";
		} else {
			if ($login_fabrica == 14)
				echo "</tr></table><td colspan='7'>&nbsp;<INPUT size='60' TYPE='hidden' NAME='msg_aviso' value=''></td>\n";
			else
				echo "<td colspan='7'>&nbsp;<INPUT size='60' TYPE='hidden' NAME='msg_aviso' value=''></td>\n";
		}

		if (in_array($login_fabrica, array(6,7,11,15,20,24,25,30,35,40,50,51,46,47,59,74,52))) {
			echo "<td align='center'>";
			echo "<a href='javascript: document.Selecionar.btnacao.value=\"liberar_tudo\" ; document.Selecionar.submit() '>Liberar Selecionados</a>";
			echo "<input type='hidden' name='total_postos' value='$i'>";
			echo "</td>\n";
		}

		if ($login_fabrica == 14) {
			echo "<table class='formulario'><tr><td align='center'>";
			echo "<a href='javascript: document.Selecionar.btnacao.value=\"liberar_tudo\" ; document.Selecionar.submit() '>Liberar Selecionados/a>";
			echo "<input type='hidden' name='total_postos' value='$i'>";
			echo "</td>\n";
		}

		if ($login_fabrica == 1 or $login_fabrica == 45) { //HD 66773
			$colspan = ($login_fabrica == 45) ? 4 : 5;
			echo "<td colspan='$colspan'>&nbsp;</td>\n";
			echo "<td align='center'>";
			echo "<a href='javascript: document.Selecionar.btnacao.value=\"acumular_tudo\" ; document.Selecionar.submit() '>Acumular selecionados</a>";
			echo "<input type='hidden' name='total_postos' value='$i'>";
			echo "</td>\n";
		}
		echo "<td colspan='2'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</form>\n";

		echo "<p>Extratos: $qtde_extratos</p>";

	}

	if (strlen($msg_os_deletadas ) >0 and $login_fabrica == 2) {
		echo "<br><div name='os_excluidas' style='border:1px solid #00ffff'><h4>OS excluidas</h4>$msg_os_deletadas;</div>";
	}

	if ($login_fabrica == 3) {

		if (strlen($extrato) > 0) {
			$cond_extrato = " AND tbl_extrato.extrato = $extrato ";
		}

		echo "<br /><br />";

		$sql = "SELECT  tbl_posto.posto               ,
						tbl_posto.nome                ,
						tbl_posto.cnpj                ,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto_fabrica.distribuidor,
						tbl_extrato.extrato           ,
						to_char (tbl_extrato.data_geracao,'dd/mm/yyyy') as data_geracao,
						tbl_extrato.total,
						(SELECT count (tbl_os.os) FROM tbl_os JOIN tbl_os_extra USING (os) WHERE tbl_os_extra.extrato = tbl_extrato.extrato) AS qtde_os,
						to_char (tbl_extrato_pagamento.data_pagamento,'dd/mm/yyyy') as baixado
				FROM    tbl_extrato
				JOIN    tbl_posto USING (posto)
				JOIN    tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				left JOIN    tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
				WHERE   tbl_extrato.fabrica = $login_fabrica
				AND     tbl_posto_fabrica.distribuidor NOTNULL
				$cond_extrato";

		if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

		if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

		if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
		$sql .= " AND      tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

		$xposto_codigo = str_replace (" " , "" , $posto_codigo);
		$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
		$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
		$xposto_codigo = str_replace ("." , "" , $xposto_codigo);

		if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xposto_codigo' ";
		if (strlen ($posto_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";

		$sql .= " GROUP BY tbl_posto.posto ,
						tbl_posto.nome ,
						tbl_posto.cnpj ,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto_fabrica.distribuidor,
						tbl_extrato.extrato ,
						tbl_extrato.liberado ,
						tbl_extrato.total,
						tbl_extrato.data_geracao,
						tbl_extrato_pagamento.data_pagamento
					ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 0) {
			echo "<center><font style='font:bold 12px Arial; color:#000;'>'Não Foram Encontrados Resultados para esta Pesquisa</font></center>";
		}

		if (pg_num_rows ($res) > 0) {
			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

				$posto   = trim(pg_fetch_result($res,$i,posto));
				$codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
				$nome           = trim(pg_fetch_result($res,$i,nome));
				$extrato        = trim(pg_fetch_result($res,$i,extrato));
				$data_geracao   = trim(pg_fetch_result($res,$i,data_geracao));
				$qtde_os        = trim(pg_fetch_result($res,$i,qtde_os));
				$total          = trim(pg_fetch_result($res,$i,total));
				$baixado        = trim(pg_fetch_result($res,$i,baixado));
				$extrato        = trim(pg_fetch_result($res,$i,extrato));
				$distribuidor   = trim(pg_fetch_result($res,$i,distribuidor));
				$total	        = number_format ($total,2,',','.');

				if (strlen($distribuidor) > 0) {
					$sql = "SELECT  tbl_posto.nome                ,
									tbl_posto_fabrica.codigo_posto
							FROM    tbl_posto_fabrica
							JOIN    tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
							WHERE   tbl_posto_fabrica.posto   = $distribuidor
							AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
					$resx = pg_query ($con,$sql);

					if (pg_num_rows($resx) > 0) {
						$distribuidor_codigo = trim(pg_fetch_result($resx,0,codigo_posto));
						$distribuidor_nome   = trim(pg_fetch_result($resx,0,nome));
					}
				}

				if ($i == 0) {
					echo "<table width='700px' class='tabela' align='center' border='1' cellspacing='2'>";
					echo "<tr class='titulo_coluna'>";
					echo "<td align='center'>Código</td>";
					echo "<td align='center' nowrap>Nome do Posto</td>";
					echo "<td align='center'>Extrato</td>";
					echo "<td align='center'>Data</td>";
					echo "<td align='center' nowrap>Qtde. OS</td>";
					echo "<td align='center'>Total</td>";
					echo "<td align='center' colspan='2'>Extrato Vinculado a um Distribuidor</td>";
					echo "</tr>";
				}

				echo "<tr>";

				echo "<td align='left'>";
				echo "$codigo_posto</td>";

				echo "<td align='left' nowrap>$nome</td>";
				echo "<td align='center'>$extrato</td>";

				echo "<td align='left'>$data_geracao</td>";
				echo "<td align='center'>$qtde_os</td>";
				echo "<td align='right' nowrap>R$ $total</td>";
				echo "<td align='left' nowrap>$distribuidor_codigo - $distribuidor_nome</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
}
}
?>

<br>

<? include "rodape.php"; ?>
