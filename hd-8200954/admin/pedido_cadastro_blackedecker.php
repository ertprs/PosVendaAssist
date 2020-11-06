<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include "funcoes.php";
include "../helpdesk.inc.php";
include_once __DIR__ . '/../class/AuditorLog.php';

include_once '../class/communicator.class.php';
include_once '../email_pedido.php';

if ($login_fabrica != 1) {
	if($login_fabrica != 3){
		header ("Location: pedido_cadastro.php");
		exit;
	}
}

if (isset($_POST['ajax_busca_condicao'])) {
	$posto_codigo   = $_POST['posto_codigo'];
	$condicao_atual = $_POST['condicao_atual'];

	$sql   = "SELECT posto
			  FROM tbl_posto_fabrica
			  WHERE fabrica = $login_fabrica
			  AND codigo_posto = '$posto_codigo'";
	$res   = pg_query($con,$sql);
	if (pg_num_rows($res) == 0) {
		exit("erro");
	} else {
		$posto = pg_fetch_result($res, 0, 'posto');

		$sql = "SELECT
					posto
				FROM tbl_black_posto_condicao
				WHERE posto = $posto
				and id_condicao = 1905";

		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			$condAntecipado = true;
		}
		$sql = "SELECT
					posto_bloqueio, desbloqueio
				FROM tbl_posto_bloqueio
				WHERE  tbl_posto_bloqueio.fabrica = {$login_fabrica}
				AND tbl_posto_bloqueio.posto = {$posto}
				AND tbl_posto_bloqueio.pedido_faturado = true
				ORDER BY posto_bloqueio DESC
				LIMIT 1";

		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$desbloqueio = pg_fetch_result($res, 0 , 'desbloqueio') ;
		}

		if($condAntecipado or $desbloqueio == 'f') {
			$sql = "SELECT condicao,codigo_condicao,descricao FROM tbl_condicao WHERE fabrica = {$login_fabrica} AND (descricao = 'Pagamento Antecipado' OR descricao = 'Garantia')";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				$options = "<option value=''></option>";

				for($i = 0; $i < pg_num_rows($res); $i++){

					$condicao        = pg_fetch_result($res, $i, "condicao");
					$codigo_condicao = pg_fetch_result($res, $i, "codigo_condicao");
					$descricao       = pg_fetch_result($res, $i, "descricao");

					$selected = ($condicao_atual == $condicao) ? "SELECTED" : "";

					$options .= "<option $selected value='{$condicao}'> {$descricao} </option>";

				}

			}
		} else {
			$sql = "SELECT
						condicao,
						codigo_condicao,
						descricao
					FROM tbl_condicao
					WHERE
						fabrica = {$login_fabrica}
					ORDER BY lpad(trim(tbl_condicao.codigo_condicao),10,'0');";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {

				$options = "<option value=''></option>";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

					$cond_pagamento  = pg_fetch_result($res, $i, "condicao");
					$codigo_condicao = pg_fetch_result($res, $i, "codigo_condicao");
					$descricao       = pg_fetch_result($res, $i, "descricao");

					$selected = ($condicao_atual == $cond_pagamento) ? "SELECTED" : "";

					if(in_array($login_admin, array(155,232,2319)) && !in_array($cond_pagamento, array(51,62))){
						$options .= "<option $selected value='{$cond_pagamento}'> {$descricao} </option>";
					} else if(in_array($cond_pagamento, array(51,62))){
						$options .= "<option $selected value='{$cond_pagamento}'> {$descricao} </option>";
					}
				}
			}
		}
	}

	exit(($options));
}

if($_GET['ajax_chamado']){

	$codigo_posto = $_GET['posto'];
	$chamado 	  = $_GET['chamado'];
	list($hd_chamado,$num) = explode('-',$chamado);

	if($num){
		$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado_anterior = $hd_chamado ORDER BY hd_chamado";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$hd_chamado = pg_fetch_result($res, $num - 1, 'hd_chamado');
		}
		echo "ok|$hd_chamado";
	}else{
		$sql = "SELECT hd_chamado
				FROM tbl_hd_chamado
				JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE hd_chamado = $chamado
				AND tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$atendimento = pg_fetch_result($res, 0, 'hd_chamado');
		}
		echo "ok|$atendimento";
	}

	exit;
}

if($_GET['ajax_pedido']){
	$peca 		  = $_GET['peca'];
	$codigo_posto = $_GET['posto'];

	$sql = "SELECT  tbl_pedido.pedido,
					tbl_pedido.seu_pedido,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data
			FROM tbl_pedido
			JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
			JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
			JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto
			AND tbl_posto_fabrica.fabrica  = $login_fabrica
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'
			AND tbl_peca.referencia = '$peca'
			AND tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '1 year' AND CURRENT_DATE
			ORDER BY pedido DESC LIMIT 2";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$retorno = "<table align='center' width='300'>
						<tr style='text-align: center;font-size: x-small;font-weight: bold;border: 1px solid;color:#ffffff;background-color: #596D9B'>
							<td colspan='2'>&Uacute;LTIMOS PEDIDOS PARA A PECA $peca</td>
						</tr>
						<tr style='text-align: center;font-size: x-small;font-weight: bold;border: 1px solid;color:#ffffff;background-color: #596D9B'>
							<th>Pedido</th>
							<th>Data</th>
						</tr>";
		for($i = 0; $i < pg_num_rows($res); $i++){
			$retorno .= "<tr bgcolor='#F7F5F0'>
							<td style='font-size: 10px;font-weight: normal;border: 1px solid;'><a href='pedido_admin_consulta.php?pedido=".pg_fetch_result($res, $i, 'pedido')."' target='_blank'>".preg_replace("/[^0-9]/", "",pg_fetch_result($res, $i, 'seu_pedido'))."</a></td>
							<td style='font-size: 10px;font-weight: normal;border: 1px solid;'>".pg_fetch_result($res, $i, 'data')."</td>
						</tr>";
		}
		$retorno .= "	<tr>
							<td colspan='2' align='right'>
								<a href='javascript:void(0);' onclick='Shadowbox.close();' style='float:right;'>X</a>
							</td>
						<tr>
					</table>";
		echo $retorno;
	}
	exit;
}


if($_GET['valida_multiplo'] == 'sim'){ //hd_chamado=2543280 - monteiro
	$peca = $_GET['peca'];
	$qtde_antiga = $_GET['qtde'];

	$sqlMultiplo = "SELECT tbl_peca.multiplo
						FROM tbl_peca
						WHERE fabrica = $login_fabrica
						AND referencia = '$peca'";
	$resMultiplo = pg_query($con, $sqlMultiplo);

	if(pg_num_rows($resMultiplo) > 0){
		$qtde_multiplo = pg_fetch_result($resMultiplo, 0, 'multiplo');

		$var1 = $qtde_antiga % $qtde_multiplo;
		$var1 = floor($var1);

		if($var1 > 0){
			$peca_qtde_mult = $qtde_antiga-$var1+$qtde_multiplo;

			if($qtde_antiga <> $peca_qtde_mult){
				$peca_qtde = $peca_qtde_mult;
			}
		}else{
			$peca_qtde = $qtde_antiga;
		}
	}
	echo "ok|".$peca_qtde;
	exit;
}


if(isset($_POST["verificaDemanda"])){

	$qtde_solicitada = $_POST["qtde_solicitada"];
	$referencia 	 = $_POST["referencia"];

	$sql = "SELECT referencia, parametros_adicionais FROM tbl_peca WHERE referencia = '$referencia' and fabrica = $login_fabrica AND JSON_FIELD('qtde_demanda', parametros_adicionais) NOTNULL";
	$res = pg_query($con, $sql);
	
	$qtde_demanda = '';
	
	if (pg_num_rows($res) > 0) {
		$parametros_adicionais 	= pg_fetch_result($res, 0, 'parametros_adicionais');

		$parametros_adicionais 	= json_decode($parametros_adicionais, true);
		$qtde_demanda 			= $parametros_adicionais["qtde_demanda"];
	}
	echo $qtde_demanda;

	exit;
}


$btn_acao = trim(strtolower($_POST['btn_acao']));

$msg_erro = "";

$qtde_item = 10;

if (strlen($_GET['pedido']) > 0)  $pedido = trim($_GET['pedido']);
if (strlen($_POST['pedido']) > 0) $pedido = trim($_POST['pedido']);

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];

##### E X C L U I R   P E D I D O #####
if ($btn_acao == "apagar") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fnc_pedido_delete ($pedido, $login_fabrica, $login_admin)";
		$res = pg_query ($con,$sql);

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro .= pg_errormessage ($con);
		}else{
			$sql = "UPDATE tbl_pedido SET fabrica = 0
			WHERE  pedido = $pedido";
			$res = @pg_query ($con,$sql);
		}

		if (strlen(pg_errormessage($con)) > 0) {
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
			$msg_erro = pg_errormessage($con);
		}else{
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF");
			exit;
		}
	}
}

##### F I N A L I Z A R   P E D I D O #####
if ($btn_acao == "finalizar") {

	$auditoria_observacao = $_POST["auditoria_observacao"];
	$auditoria_tipo = $_POST["auditoria_tipo"];

	if(strlen(trim($auditoria_tipo))==0){
		$auditoria_tipo = null;
	}else{
		$aprovaAutomaticoAdmin = true;
	}

	$sql = "UPDATE tbl_pedido set aprovacao_tipo = '$auditoria_tipo' WHERE pedido = $pedido ";
	$res = pg_query($con, $sql);

	$sql = " SELECT total, condicao, upper(contato_estado) as estado
		FROM tbl_pedido
		JOIN tbl_posto_fabrica using(posto, fabrica)
		WHERE pedido = $pedido ;";
	$res = pg_exec ($con,$sql);

	if (strlen(pg_errormessage($con)) > 0) {
		$msg_erro = pg_errormessage($con);
	}

	if(pg_num_rows($res) > 0) {
		$xtotal_pedido = pg_fetch_result($res,0,'total');
		$xcondicao     = pg_fetch_result($res,0,'condicao');
		$posto_uf    = pg_fetch_result($res,0,'estado');
	}

	if (strlen($msg_erro) == 0) {

		$res = pg_exec($con,"BEGIN TRANSACTION");
		$retorno = DividePedidos($pedido);

		//Precisa para saber os pedidos que o admin tem que aprovar e não prejudicar as aprovações de demanda. 
		$sql_aprova_admin = " UPDATE tbl_pedido set valores_adicionais = jsonb_set(valores_adicionais::jsonb, '{pendencia_aprovacao_admin}','true') where pedido = $pedido ";
		$res_aprova_admin = pg_query($con, $sql_aprova_admin);

		if(!array_key_exists('erro', $retorno)){
			foreach ($retorno as $pedido) {
				$sql =	"UPDATE tbl_pedido SET
							unificar_pedido = 't'
						WHERE tbl_pedido.pedido = $pedido
						AND   tbl_pedido.unificar_pedido ISNULL;";
				$res = pg_exec ($con,$sql);

				

				if (strlen(pg_errormessage($con)) > 0) {
					$msg_erro['erro'][] = pg_errormessage($con);
				}

				if (strlen($msg_erro['erro']) == 0) {
					$sql = "INSERT INTO tbl_pedido_alteracao (
								pedido
							)VALUES(
								$pedido
							);";
					$res = pg_exec($con,$sql);

					if (strlen(pg_errormessage($con)) > 0) {
						$msg_erro['erro'][] = pg_errormessage($con) ;
					}
				}

				$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
				$res = @pg_exec($con,$sql);
				if (strlen(pg_errormessage($con)) > 0) {
					$msg_erro['erro'][] = pg_errormessage($con) ;
				}

				$sql = "SELECT fn_pedido_suframa($pedido,$login_fabrica);";
				$res = @pg_exec($con,$sql);
				if (strlen(pg_errormessage($con)) > 0) {
					$msg_erro['erro'][] = pg_errormessage($con) ;
				}

				VerificaDemanda($pedido, $auditoria_observacao, $auditoria_tipo, $aprovaAutomaticoAdmin);
			}
		}



		if((!array_key_exists('erro', $retorno)) AND (!array_key_exists('erro', $retorno))){
			$res = pg_exec($con,"COMMIT TRANSACTION");
			$qtdePedidos = count($retorno);

			$sql_blackedecker = "SELECT seu_pedido FROM tbl_pedido WHERE pedido = $pedido";
			$res_blackedecker = pg_query($con, $sql_blackedecker);

			$pedido_blackedecker_final = pg_fetch_result($res_blackedecker, 'seu_pedido');

			if($qtdePedidos > 1){
				$msg = $msg_erro;
				if(!empty($pedido_blackedecker_final)){
					$sql_posto = "SELECT 
									tbl_posto_fabrica.contato_email as contato_email,
									tbl_fabrica.nome as fabrica_nome,
									tbl_posto.nome as posto_nome 
								FROM tbl_posto_fabrica 
								JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica) 
								JOIN tbl_posto on (tbl_posto.posto = tbl_posto_fabrica.posto) 
								where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		
		
					$res_posto = pg_query($con, $sql_posto);
		
					$contato_email = pg_fetch_result($res_posto, 0, 'contato_email');
					$fabrica_nome = pg_fetch_result($res_posto, 0, 'fabrica_nome');
					$posto_nome = pg_fetch_result($res_posto, 0, 'posto_nome');
	
					$assunto       = "Pedido nº ".$pedido_blackedecker_final. " - ". $fabrica_nome;
					$corpo         = email_pedido_blackeredecker($posto_nome, $fabrica_nome, $pedido, $pedido_blackedecker_final, $cook_login, true);

					$mailTc = new TcComm($externalId);
					$res = $mailTc->sendMail(
						$contato_email,
						$assunto,
						utf8_encode($corpo),
						$externalEmail
					);
				}

				header ("Location: pedido_finalizado_desmembrado.php?pedido=".implode(",", $retorno));
			}else{
				if(!empty($pedido_blackedecker_final)){
					$sql_posto = "SELECT 
									tbl_posto_fabrica.contato_email as contato_email,
									tbl_fabrica.nome as fabrica_nome,
									tbl_posto.nome as posto_nome 
								FROM tbl_posto_fabrica 
								JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica) 
								JOIN tbl_posto on (tbl_posto.posto = tbl_posto_fabrica.posto) 
								where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		
		
					$res_posto = pg_query($con, $sql_posto);
		
					$contato_email = pg_fetch_result($res_posto, 0, 'contato_email');
					$fabrica_nome = pg_fetch_result($res_posto, 0, 'fabrica_nome');
					$posto_nome = pg_fetch_result($res_posto, 0, 'posto_nome');
	
					$assunto       = "Pedido nº ".$pedido_blackedecker_final. " - ". $fabrica_nome;
					$corpo         = email_pedido_blackeredecker($posto_nome, $fabrica_nome, $pedido, $pedido_blackedecker_final, $cook_login, true);

					$mailTc = new TcComm($externalId);
					$res = $mailTc->sendMail(
						$contato_email,
						$assunto,
						utf8_encode($corpo),
						$externalEmail
					);
				}

				header ("Location: pedido_admin_consulta.php?pedido=".$retorno[0]."&imprimir=sim");
			}
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
			$msg_erro['erro'][] = "Erro ao finalizar pedido.";
		}
	}

}

##### D E L E T A R   I T E M   D O   P E D I D O #####
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);

	if (empty($pedido)) {
        /*Inicia o AuditorLog Pedido */

        $objLog = new AuditorLog('insert');
        $objItem = new AuditorLog('insert');
        $tpAuditor = "insert";
    } else {
        /*Inicia o AuditorLog Pedido */
        $objLog = new AuditorLog();
        $objItem = new AuditorLog();
        $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica) );
        
        $objItem->retornaDadosSelect("  SELECT  pi.pedido_item,
												pi.peca,
												pi.qtde AS qtde_peca_pedida,
												pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
												p.referencia,
												p.descricao,
												JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
										FROM tbl_pedido_item pi
										JOIN tbl_peca p ON pi.peca = p.peca
										WHERE p.fabrica = $login_fabrica 
										AND pi.pedido_item = $delete");
        $tpAuditor = "update";
    }

	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido_item = $delete";
	$res = @pg_exec($con,$sql);

	//DELETA PEDIDO SEM ITEM - HD 21009 27/5/2008
	$sqlP = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
	$resP = @pg_exec ($con,$sqlP);
	if(pg_numrows($resP)==0){
		$sql = "SELECT fnc_pedido_delete ($pedido, $login_fabrica, $login_admin)";
		$res = pg_query ($con,$sql);

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro .= pg_errormessage ($con);
		}else{
			$sql = "UPDATE tbl_pedido SET fabrica = 0
			WHERE  pedido = $pedido";
			$res = @pg_query ($con,$sql);
		}
	}

	if (strlen(pg_errormessage($con) ) > 0) {
		$msg_erro = pg_errormessage($con) ;
	}else{
		$pedido = $_GET["pedido"];

		if ($tpAuditor == 'insert') {
            $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica))
                    ->enviarLog($tpAuditor, 'tbl_pedido', $login_fabrica.'*'.$pedido);
            $objItem->retornaDadosSelect("  SELECT  pi.pedido_item,
													pi.peca,
													pi.qtde AS qtde_peca_pedida,
													pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
													p.referencia,
													p.descricao,
													JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
											FROM tbl_pedido_item pi
											JOIN tbl_peca p ON pi.peca = p.peca
											WHERE p.fabrica = $login_fabrica 
											AND pi.pedido_item = $delete")
                    ->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
        } else {
            $objLog->retornaDadosTabela()->enviarLog($tpAuditor, "tbl_pedido", $login_fabrica."*".$pedido);
            $objItem->retornaDadosSelect(" SELECT   pi.pedido_item,
													pi.peca,
													pi.qtde AS qtde_peca_pedida,
													pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
													p.referencia,
													p.descricao,
													JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
											FROM tbl_pedido_item pi
											JOIN tbl_peca p ON pi.peca = p.peca
											WHERE p.fabrica = $login_fabrica 
											AND pi.pedido_item = $delete")->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
        }

		header ("Location: $PHP_SELF?pedido=$pedido");
		exit;
	}
}

##### D E L E T A R   T O D O S   O S   I T E N S   D O   P E D I D O #####
if ($_GET["excluir"] == "tudo") {
	$pedido = trim($_GET["pedido"]);

	if (empty($pedido)) {
        /*Inicia o AuditorLog Pedido */

        $objLog = new AuditorLog('insert');
        $objItem = new AuditorLog('insert');
        $tpAuditor = "insert";
    } else {
        /*Inicia o AuditorLog Pedido */
        $objLog = new AuditorLog();
        $objItem = new AuditorLog();
        $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica) );
        
        $objItem->retornaDadosSelect("  SELECT  pi.pedido_item,
												pi.peca,
												pi.qtde AS qtde_peca_pedida,
												pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
												p.referencia,
												p.descricao,
												JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
										FROM tbl_pedido_item pi
										JOIN tbl_peca p ON pi.peca = p.peca
										WHERE p.fabrica = $login_fabrica 
										AND pi.pedido = $pedido");
        $tpAuditor = "update";
    }

	$sql = "DELETE FROM tbl_pedido_item
		USING  tbl_pedido
		WHERE  tbl_pedido.pedido = tbl_pedido_item.pedido
		AND    tbl_pedido_item.pedido  = $pedido
		AND    tbl_pedido.fabrica = $login_fabrica;";

	$res = @pg_exec($con,$sql);

	if (strlen(pg_errormessage($con) ) > 0) {
		$msg_erro = pg_errormessage($con) ;
	}else{

		if ($tpAuditor == 'insert') {
            $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica))
                    ->enviarLog($tpAuditor, 'tbl_pedido', $login_fabrica.'*'.$pedido);
            $objItem->retornaDadosSelect("  SELECT  pi.pedido_item,
													pi.peca,
													pi.qtde AS qtde_peca_pedida,
													pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
													p.referencia,
													p.descricao,
													JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
											FROM tbl_pedido_item pi
											JOIN tbl_peca p ON pi.peca = p.peca
											WHERE p.fabrica = $login_fabrica 
											AND pi.pedido = $pedido")
                    ->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
        } else {
            $objLog->retornaDadosTabela()->enviarLog($tpAuditor, "tbl_pedido", $login_fabrica."*".$pedido);
            $objItem->retornaDadosSelect(" SELECT   pi.pedido_item,
													pi.peca,
													pi.qtde AS qtde_peca_pedida,
													pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
													p.referencia,
													p.descricao,
													JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
											FROM tbl_pedido_item pi
											JOIN tbl_peca p ON pi.peca = p.peca
											WHERE p.fabrica = $login_fabrica 
											AND pi.pedido = $pedido")->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
        }

		header ("Location: $PHP_SELF?pedido=$pedido");
		exit;
	}
}

##### G R A V A R   P E D I D O #####
if ($btn_acao == "gravar") {

    $xcodigo_posto      = filter_input(INPUT_POST,'codigo_posto');
    $xnome_posto        = filter_input(INPUT_POST,'descricao_posto');
    $chamado_sac        = filter_input(INPUT_POST,'chamado_sac');
    $categoria_pedido   = filter_input(INPUT_POST,'categoria_pedido');
    $xcondicao          = filter_input(INPUT_POST,'condicao');

	$chamado_sac = (!empty($chamado_sac)) ? $chamado_sac : "";

    if (!empty($categoria_pedido)) {
        $categoria = array("categoria_pedido" => $categoria_pedido);
    } else {
    	$campos_erro["campos"][] = "categoria_pedido";
    	$msg_erro .= " Favor selecione a categoria do pedido. <br>";
    }

	if (strlen($xcodigo_posto) > 0 OR strlen($xnome_posto) > 0) {
		$sql =	"SELECT tbl_posto.posto, upper(tbl_posto_fabrica.contato_estado) AS contato_estado
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE	tbl_posto_fabrica.fabrica = $login_fabrica";
		if (strlen($xcodigo_posto) > 0)
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$xcodigo_posto' ";
		if (strlen($xnome_posto) > 0 and strlen($xcodigo_posto) == 0 )
			$sql .= " AND tbl_posto.nome ILIKE '%$xnome_posto%' ";

		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto = "'".pg_result($res,0,'posto')."'";
			$posto_uf = pg_result($res,0,'contato_estado');
		}else{
			$posto = "null";
			$msg_erro .= " Favor informe o posto correto. <br/>";
			$campos_erro["campos"][] = "posto";
		}
	}else{
		$posto = "null";
		$msg_erro .= " Favor informe o posto. <br/>";
		$campos_erro["campos"][] = "posto";
	}

	if (strlen($xcondicao) == 0) {
		$xcondicao = "null";
		$msg_erro .= " Favor informe a condição de pagamento. <br/>";
		$campos_erro["campos"][] = "condicao";
	}

	$total_pedido  = trim($_POST['total_pedido']);
	$xtotal_pedido  = str_replace(".", "", $total_pedido);
	$xtotal_pedido  = str_replace(",", ".", $xtotal_pedido);


	$justificativa = trim($_POST['justificativa']);
	$justificativa = str_replace("'", "", $justificativa);
	if (strlen($justificativa) == 0) {
		$campos_erro["campos"][] = "justificativa";
		$msg_erro .= " Favor informe a justificativa. <br/>";
	}

	##### VERIFICA SE A PEÇA FOI DIGITADA COM A QTDE #####
	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$peca_referencia = trim($_POST["peca_referencia_" . $i]);
		$peca_qtde       = trim($_POST["peca_qtde_"       . $i]);

		if (strlen($peca_referencia) > 0 AND strlen($peca_qtde) == 0) {
			$msg_erro .= " Favor informe quantidade da Peça $peca_referencia. <br/>";
			$linha_erro[] = $i;
		}
	}

	##### VERIFICA TIPO PEDIDO #####
	$sql =	"SELECT tipo_pedido
			FROM	tbl_tipo_pedido
			WHERE	fabrica = $login_fabrica";
	if ($xcondicao == '62')
		$sql .= " AND UPPER(TRIM(descricao)) = 'GARANTIA'";
	else
		$sql .= " AND (UPPER(TRIM(descricao)) = 'FATURADO' OR (UPPER(TRIM(descricao)) = 'VENDA' AND fabrica=3))";
	$res = @pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$xtipo_pedido = "'".pg_result($res,0,0)."'";
	}else{
		$xtipo_pedido = "null";
	}

	##### VERIFICA TABELA #####
	$sql =	"SELECT tabela
			FROM	tbl_tabela";
	if ($xcondicao == '62')
		if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2017-03-06 00:00:00"))) {
			$sql .= " WHERE UPPER(TRIM(sigla_tabela)) = 'GARAN6'";
		}else{
			$sql .= " WHERE UPPER(TRIM(sigla_tabela)) = 'GARAN7'";
		}
	else
		if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2017-03-06 00:00:00"))) {
			$sql .= " WHERE UPPER(TRIM(sigla_tabela)) = 'BASE6'";
		}else{
			$sql .= " WHERE UPPER(TRIM(sigla_tabela)) = 'BASE7'";
		}
	$res = @pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$xtabela = "'".pg_result($res,0,0)."'";
	}else{
		$xtabela = "null";
	}


	if ($xcondicao == '62') $xnatureza_operacao = "'SN-GART'";
	else                    $xnatureza_operacao = "'VN-COML'";

	if (strlen($msg_erro) == 0) {

		if (empty($pedido)) {
            /*Inicia o AuditorLog Pedido */

            $objLog = new AuditorLog('insert');
            $objItem = new AuditorLog('insert');
            $tpAuditor = "insert";
        } else {
            /*Inicia o AuditorLog Pedido */
            $objLog = new AuditorLog();
            $objItem = new AuditorLog();
            $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica) );
            
            $objItem->retornaDadosSelect("  SELECT  pi.pedido_item,
													pi.peca,
													pi.qtde AS qtde_peca_pedida,
													pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
													p.referencia,
													p.descricao,
													JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
											FROM tbl_pedido_item pi
											JOIN tbl_peca p ON pi.peca = p.peca
											WHERE p.fabrica = $login_fabrica 
											AND pi.pedido = $pedido");
            $tpAuditor = "update";
        }


		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($pedido) == 0) {
			########## I N S E R E   P E D I D O ##########
			$sql =	"INSERT INTO tbl_pedido (
						posto             ,
						fabrica           ,
						admin             ,
						condicao          ,
						tabela            ,
						tipo_pedido       ,
						bloco_os          ,
						natureza_operacao ,
						pedido_sedex      ,
						status_pedido     ,
						valores_adicionais,
						obs
					) VALUES (
						$posto              ,
						$login_fabrica      ,
						$login_admin        ,
						'$xcondicao'        ,
						$xtabela            ,
						$xtipo_pedido       ,
						'0'                 ,
						$xnatureza_operacao ,
						't'					,
						18                  ,
						E'".json_encode($categoria)."',
						'$chamado_sac'
					) RETURNING pedido";
		} else {
            $sqlAdicionais = "
                SELECT  valores_adicionais
                FROM    tbl_pedido
                WHERE   pedido = $pedido
            ";
            $resAdicionais = pg_query($con,$sqlAdicionais);

            $categoriaPedido = json_decode(pg_fetch_result($resAdicionais,0,valores_adicionais),TRUE);

            $categoriaPedido['categoria_pedido'] = $categoria_pedido;
			########## A L T E R A   P E D I D O ##########
			$sql =	"UPDATE tbl_pedido SET
						posto             = $posto              ,
						fabrica           = $login_fabrica      ,
						condicao          = $xcondicao          ,
						tabela            = $xtabela            ,
						tipo_pedido       = $xtipo_pedido       ,
						bloco_os          = '0'                 ,
						exportado         = null                ,
						finalizado        = null                ,
						natureza_operacao = $xnatureza_operacao ,
						valores_adicionais = E'".json_encode($categoriaPedido)."'

					WHERE tbl_pedido.pedido  = $pedido
					AND   tbl_pedido.fabrica = $login_fabrica";

		}
// exit(nl2br($sql));
		$res = pg_query ($con,$sql);
		$msg_erro = pg_last_error($con);
// echo "->>".$msg_erro;exit;
		if (strlen($msg_erro) == 0 AND strlen($pedido) == 0) {
			$pedido = pg_result($res,0,0);
			$msg_erro = pg_last_error($con);
// echo $pedido;exit;
			$sql = "INSERT INTO tbl_pedido_status(pedido,observacao,admin,status) VALUES($pedido,'$justificativa',$login_admin,18)";
			$res = pg_query ($con,$sql);
		}else{
			$sql = "UPDATE tbl_pedido_status SET observacao = '$justificativa', admin = $login_admin WHERE pedido = $pedido";
			$res = pg_query ($con,$sql);
		}

		if (strlen($msg_erro) == 0) {
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$peca_referencia = trim($_POST['peca_referencia_' . $i]);
				$peca_descricao  = trim($_POST['peca_descricao_' . $i]);
				$peca_qtde       = trim($_POST['peca_qtde_'       . $i]);
				$peca_os         = trim($_POST["os_". $i. "_hidden"]);
				$obs_hidden      = trim($_POST["obs_". $i. "_hidden"]);

				if(strlen($peca_referencia) == 0 and $i == 0) {
					$msg_erro = "Favor informar as peças do pedido ";break;
				}
				$peca_os = (!empty($peca_os)) ? $peca_os : "";

				$sua_os 	  = trim($_POST['os_'. $i]);

				if(strlen(trim($sua_os))> 0 ){
					$obs_sua_os = "OS: $sua_os";
				}

				$obs = "$obs_sua_os <br> $obs_hidden ";

                if (strlen($peca_referencia) > 0 OR strlen($peca_descricao) > 0) {
                    $xpeca_referencia = strtoupper($peca_referencia);
                    $xpeca_referencia = str_replace("-","",$xpeca_referencia);
                    $xpeca_referencia = str_replace(".","",$xpeca_referencia);
                    $xpeca_referencia = str_replace("/","",$xpeca_referencia);
                    $xpeca_referencia = str_replace(" ","",$xpeca_referencia);

                    $xpeca_descricao  = strtoupper($peca_descricao);

                    $sql =	"SELECT tbl_peca.peca
                            FROM    tbl_peca
                            WHERE   tbl_peca.fabrica = $login_fabrica ";
                    if (strlen($xpeca_referencia) > 0) $sql .= " AND (tbl_peca.referencia_pesquisa = '$xpeca_referencia' OR  tbl_peca.referencia = '$peca_referencia')";
                    //if (strlen($xpeca_descricao) > 0)  $sql .= " AND tbl_peca.descricao = '$xpeca_descricao' ";
                    $res = pg_query($con,$sql);
                    if (pg_numrows($res) == 1) {
                        $peca = pg_result($res,0,peca);
                    }else{
                        $msg_erro = " Peça $peca_referencia não cadastrada. ";
                        $linha_erro[] = $i;
                    }

                    if(strlen($msg_erro) == 0) {//bloquear peças já cadastradas e-mail do suporte.
                        $sql2 = "SELECT peca FROM tbl_pedido_item WHERE pedido = $pedido AND peca = $peca; ";
                        $res2 = pg_exec($con,$sql2);
                        $verificador = @pg_result($res2,0,peca);

                        if(strlen($verificador) > 0){
                            $msg_erro = " Peça $peca_referencia em destaque em duplicidade, favor retirar!";
                            $linha_erro[] = $i;
                            $verificador = '';
                        }
                    }

                    if(!empty($sua_os)){
                        $pos = strpos($sua_os, "-");

                        if ($pos === false) {
							if(strlen ($sua_os) > 12) {
								$pos = strlen($sua_os) - (strlen($sua_os)-6);
							}else if(strlen ($sua_os) > 11){
								$pos = strlen($sua_os) - (strlen($sua_os)-5);
							} elseif(strlen ($sua_os) > 10) {
								$pos = strlen($sua_os) - (strlen($sua_os)-6);
							} elseif(strlen ($sua_os) > 9) {
								$pos = strlen($sua_os) - (strlen($sua_os)-5);
							}else{
								$pos = strlen($sua_os);
							}
						}else{

                            if(strlen (substr($sua_os,0,$pos)) > 11){#47506
                                $pos = $pos - 7;
                            } else if(strlen (substr($sua_os,0,$pos)) > 10) {
                                $pos = $pos - 6;
                            } elseif(strlen ($sua_os) > 9) {
                                $pos = $pos - 5;
                            }
                        }
                        if(strlen ($sua_os) > 9) {
                            $xsua_os = substr($sua_os, $pos,strlen($sua_os));
                        }

                        $xsua_os = trim($xsua_os);

                        $sql = "SELECT tbl_os.os,tbl_os.sua_os
                                FROM tbl_os
                                JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto
                                AND tbl_posto_fabrica.fabrica = $login_fabrica
                                WHERE tbl_os.sua_os = '$xsua_os'
                                AND tbl_os.fabrica = $login_fabrica
                                AND tbl_posto_fabrica.posto = $posto";
                        $res = pg_query($con,$sql);

                        if(pg_num_rows($res) > 0){

                            $peca_os 	= pg_fetch_result($res, 0, 'os');
                            $sua_os = pg_fetch_result($res, 0, 'sua_os');
                            $sua_os = $xcodigo_posto.$sua_os;

                            $sql = "SELECT  tbl_os_item.pedido,
                                            tbl_peca.referencia,
                                            tbl_pedido.seu_pedido
                                    FROM tbl_os_produto
                                    JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                    JOIN tbl_servico_realizado USING(servico_realizado)
                                    JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
                                    LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
                                    AND tbl_pedido.posto = $posto
                                    AND tbl_pedido.fabrica = $login_fabrica
                                    WHERE tbl_os_produto.os = $peca_os
                                    AND   tbl_servico_realizado.gera_pedido
                                    AND tbl_peca.peca = $peca";
                            $res = pg_query($con,$sql);
                            if(pg_num_rows($res) > 0){
                                $pedido = preg_replace("/[^0-9]/", "",pg_fetch_result($res, 0, 'seu_pedido'));
                                $referencia = pg_fetch_result($res, 0, 'referencia');
                                $erro = "A peça $referencia já consta na OS $sua_os e precisa ser retirada ou o pedido cancelado <br />";
                            }

                            if (strlen ($erro) > 0) {
                                $msg_erro .= $erro."<br>";
                                $linha_erro[] = $i;
                            }
                        }else{
                            $msg_erro .= "OS não encontrada no sistema";
                            $linha_erro[] = $i;
                        }
                    }

                    if (strlen($msg_erro) == 0) {
                        $sql =	"INSERT INTO tbl_pedido_item (
                                    pedido ,
                                    peca   ,
                                    qtde   ,
                                    obs
                                ) VALUES (
                                    $pedido    ,
                                    $peca      ,
                                    $peca_qtde ,
                                    '$obs'
                                ) RETURNING pedido_item";
                        $res = pg_query($con,$sql);
                        $mg_erro = pg_last_error($con);
                        if (!empty($mg_erro)) {
                            	$ms_erro  = explode(":", $mg_erro);
                            	$ms_erro  = explode("!", $ms_erro[1]);
                            	$msg_erro = $ms_erro[0];
                            }
// echo $sql;exit;
                        if (strlen($msg_erro) == 0) {
                            $pedido_item = pg_result($res,0,0);
                            $msg_erro = pg_last_error($con);
                        }else{
                            break;
                        }
                        if (strlen($msg_erro) == 0) {
                            $sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
                            $res = @pg_exec($con,$sql);
                            $msg_erro = pg_errormessage($con);
                        }

                        if (strlen ($msg_erro) > 0) {
                            $linha_erro[] = $i;
                            break;
                        }
                    }
                }
			}
		}

		/*$sqlPedidoStatus = "select pedido_status from tbl_pedido_status WHERE pedido = $pedido";
		$resPedidoStatus = pg_query($con, $sqlPedidoStatus);
		if(pg_num_rows($resPedidoStatus)==0){
			$sql = "INSERT INTO tbl_pedido_status(pedido,observacao,admin,status) VALUES($pedido,'Pedido entrou em auditoria de demanda',$login_admin,18)";			
		}else{
			$sql = "UPDATE tbl_pedido_status SET observacao = 'Pedido entrou em auditoria de demanda', admin = $login_admin WHERE pedido = $pedido";	
		}		
		$res = pg_query ($con,$sql);*/
	}

	if (strlen($msg_erro) == 0 ) {
        $sql = "SELECT fn_finaliza_pedido_blackedecker ($pedido,$login_fabrica)";
		$res = @pg_exec($con,$sql);
		$trata_erro = pg_errormessage($con);
		$txt = '/bloqueado para compra/';
		if (preg_match($txt, $trata_erro)) {
			$ref_peca = explode("Item", $trata_erro);
			$ref_peca = explode("em", $trata_erro);
			$msg_erro = "A Peça ".trim($ref_peca[1])." está bloqueada para venda.";	
		} else {
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");

		if ($tpAuditor == 'insert') {
            $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica))
                    ->enviarLog($tpAuditor, 'tbl_pedido', $login_fabrica.'*'.$pedido);
            $objItem->retornaDadosSelect("  SELECT  pi.pedido_item,
													pi.peca,
													pi.qtde AS qtde_peca_pedida,
													pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
													p.referencia,
													p.descricao,
													JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
											FROM tbl_pedido_item pi
											JOIN tbl_peca p ON pi.peca = p.peca
											WHERE p.fabrica = $login_fabrica 
											AND pi.pedido = $pedido")
                    ->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
        } else {
            $objLog->retornaDadosTabela()->enviarLog($tpAuditor, "tbl_pedido", $login_fabrica."*".$pedido);
            $objItem->retornaDadosSelect()->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
        }

		header("Location: $PHP_SELF?pedido=$pedido");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}


#------------ Le Pedido da Base de dados ------------#
if (strlen($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido_blackedecker                                   ,
					tbl_pedido.seu_pedido                                            ,
					tbl_pedido.condicao                                              ,
					JSON_FIELD('categoria_pedido',tbl_pedido.valores_adicionais)     AS categoria_pedido,
					tbl_posto_fabrica.codigo_posto                                   ,
					tbl_posto.nome                                     AS nome_posto ,
					to_char(tbl_pedido.exportado,'DD/MM/YYYY HH24:MI') AS exportado  ,
					tbl_pedido_status.observacao
			FROM    tbl_pedido
			JOIN    tbl_posto USING (posto)
			JOIN	tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_pedido_status USING(pedido)
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$pedido_blackedecker = "00000".trim(pg_result($res,0,pedido_blackedecker));
		$pedido_blackedecker = substr($pedido_blackedecker,strlen($pedido_blackedecker)-5,strlen($pedido_blackedecker));
		$seu_pedido          = trim(pg_result($res,0,seu_pedido));
		$condicao            = trim(pg_result($res,0,condicao));
		$codigo_posto        = trim(pg_result($res,0,codigo_posto));
		$nome_posto          = trim(pg_result($res,0,nome_posto));
		$exportado           = trim(pg_result($res,0,exportado));
		$categoria_pedido    = trim(pg_result($res,0,categoria_pedido));
		$justificativa           = trim(pg_result($res,0,'observacao'));

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}
	}
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido              = $_POST['pedido'];
	$pedido_blackedecker = $_POST['pedido_blackedecker'];
	$condicao            = $_POST['condicao'];
	$codigo_posto        = $_POST['codigo_posto'];
	$nome_posto          = $_POST['descricao_posto'];
}

$layout_menu = "callcenter";
$title       = "Cadastro de Pedidos de Peças";
$body_onload = "javascript: document.frm_pedido.condicao.focus()";

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
<!--<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">-->
<!--<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>-->
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>

<script type="text/javascript">

$(function() {
	
	Shadowbox.init({
		skipSetup	: true,
		enableKeys	: false,
		modal 		: true
	});

	$("#visualiza_log_item").click(function () {
		
		let pedido_id = $(this).attr("data-pedido")
		let url_log = "relatorio_log_alteracao_new.php?parametro=tbl_pedido_item&id="+pedido_id

        Shadowbox.open({
            content: url_log,
            player: "iframe",
        });
	})

	$("#total_pedido").numeric({allow:".,"});

	$("span[rel=lupa]").click(function () {

		var posto_codigo = $("#codigo_posto").val();
        var posto_nome   = $("#descricao_posto").val();
        var tabela       = $("#tabela").val();
        var tipo_pedido       = $("#tipo_pedido").val();

		$(this).next().attr("posto_codigo", posto_codigo);
        $(this).next().attr("posto_nome", posto_nome);
        $(this).next().attr("tipo-pedido",tipo_pedido);
        $(this).next().attr("tabela",tabela);

		var parametros_lupa_peca = ["posicao", "preco", "posto_codigo" ,"posto_nome", "tipo-pedido", "tabela"];
		$.lupa($(this), parametros_lupa_peca)
        //$.lupa($(this));
    });

    if ($("#descricao_posto").val() != "" && $("#descricao_posto").val() != undefined && $("#codigo_posto").val() != "" && $("#codigo_posto").val() != undefined) {
    	$(".cond_pag").css("display","block");
    } else {
		$(".cond_pag").css("display","none");
	}	
    

	$("#codigo_posto").change(function(){
    	if ($("#codigo_posto").val() != "" && $("#codigo_posto").val() != undefined ) {
			$(".cond_pag").css("display","block");
		} else {
			$(".cond_pag").css("display","none");
		}	
	});

	$("#codigo_posto").blur(function(){
		if ($("#codigo_posto").val() != "" && $("#codigo_posto").val() != undefined ) {
			$(".cond_pag").css("display","block");
		} else {
			$(".cond_pag").css("display","none");
		}	
	});

	$("#descricao_posto").change(function(){
    	if ($("#descricao_posto").val() != "" && $("#descricao_posto").val() != undefined) {
			$(".cond_pag").css("display","block");
		} else {
			$(".cond_pag").css("display","none");
		}		
	}); 

	$("#descricao_posto").blur(function(){
		if ($("#descricao_posto").val() != "" && $("#descricao_posto").val() != undefined) {
			$(".cond_pag").css("display","block");
		} else {
			$(".cond_pag").css("display","none");
		}
	});

	$("#cancelar_finalizar").click(function(){
		$(".modal_finalizar").hide();
	});

	$("#gravar_modal_finalizar").click(function(){
		var auditoria_demanda = $("#auditoria_demanda").val();
		var observacao_demanda = $("#observacao_demanda").val();
		var auditoria_observacao = "";

		if(auditoria_demanda.length == 0){
			$("#obrigatorio").text("Informe um auditoria");
			return false;
		}

		auditoria_observacao = auditoria_demanda + " - " + observacao_demanda;

		$("#auditoria_observacao").val(observacao_demanda);
		$("#auditoria_tipo").val(auditoria_demanda);

		$("input[name=btn_acao]").val('finalizar');

		document.frm_pedido.submit();
	});
});

$(document).ready(function() {
	if ($("#codigo_posto").val() != "" && $("#codigo_posto").val() != undefined && $("#descricao_posto").val() != "" && $("#descricao_posto").val() != undefined) {
		$(".cond_pag").css("display","block");
	} else {
		$(".cond_pag").css("display","none");
	}	
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);

    if ($("input[name=codigo_posto]").val() != "") {
		busca_condicao($("input[name=codigo_posto]").val(), $('#condicao_atual').val());
	}
}

function gravar_pedido(qtde_item){

	if ($('#codigo_posto').val() == "" || $('#codigo_posto').val() == undefined || $('#descricao_posto').val() == "" || $('#descricao_posto').val() == undefined || $('#justificativa_text').val() == "" || $('#justificativa_text').val() == undefined) {
		alert('Preencha todos os campos obrigatórios !');
		return false;
	} 

	var qtde_item = qtde_item;
	var msg = "";
	for (i = 0 ; i < qtde_item ; i++) {
		var referencia = $("input[name=peca_referencia_"+i+"]").val();
		var solicitada = parseInt($("input[name=peca_qtde_"+i+"]").val());
		if(referencia.length > 0){
			var qtde_demanda = VerificaDemanda(referencia);

			if(qtde_demanda.length > 0 ){

				if(solicitada > qtde_demanda && qtde_demanda >= 0 && solicitada > 3 ) {
					msg += "QUANTIDADE DA PEÇA "+referencia+" ULTRAPASSOU A DEMANDA DE "+qtde_demanda+" PEÇAS. \n";
					$("#obs_"+i+"_hidden").val("A QUANTIDADE DE PEÇA "+referencia+" PEDIDA FOI DE "+solicitada+" E ULTRAPASSOU A DEMANDA DE "+qtde_demanda+" PEÇAS");
				}
			}
		}
		referencia = "";
		solicitada = "";
	}

	if(msg.length > 0 ){

		$(".msg_modal").append("<p>"+msg+"</p>");  
		$('.continuar_pedido').show();
		$(".msg_gravar_pedido").show();
		$(".btns").show();
		$(".radios").hide();
	
		$('#cancelar_modal').click(function() {
			$('.continuar_pedido').hide();
			$(".msg_modal").html("");
			$("input[name=btn_acao]").val("");
		});

		$('#continuar_modal').click(function() {
			$('.continuar_pedido').hide();
			$(".msg_modal").html("");
            if (document.frm_pedido.btn_acao.value == '') {
                document.frm_pedido.btn_acao.value='gravar'; efetuaPedido();
            }else{
                alert('Aguarde submissão')
            }
		});
	
	}else{
		if (document.frm_pedido.btn_acao.value == '') {
			document.frm_pedido.btn_acao.value='gravar'; efetuaPedido();
		}else{
			alert('Aguarde submissão')
		}
	}
}

function VerificaDemanda(referencia){

	var valor;
	$.ajax({
		url: "pedido_cadastro_blackedecker.php",
		type: "post",
		async: false,
		data: { verificaDemanda: true, referencia: referencia},
		success: function(retorno){
			valor = retorno;
		}
	});
	return valor;
}


function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

function fnc_pesquisa_peca (campo, campo2, peca_preco, peca_qtde, tipo) {

    var descricao;
    var referencia;
    //var preco;

	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
        peca_referencia  = campo;
        peca_descricao   = campo2;
        preco       = peca_preco;
		janela.focus();
	}
	$(peca_qtde).val("").next("input").val("");
}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?cad_pedido_black=t&campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

nextfield = "codigo_posto"; // coloque o nome do primeiro campo do form
netscape = "";
ver = navigator.appVersion; len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");

function keyDown(DnEvents) {
	// ve quando e o netscape ou IE
	k = (netscape) ? DnEvents.which : window.event.keyCode;
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			return true; // envia quando termina os campos
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frm_pedido.' + nextfield + '.focus()');
			return false;
		}
	}
}

function fnc_calcula_total (linha_form) {
	var total = 0;
	preco = document.getElementById('peca_preco_'+linha_form).value;
	qtde = document.getElementById('peca_qtde_'+linha_form).value;

	if (preco.search(/\d{1,3},\d{1,4}$/) != -1) { // Se o preço estiver formatado...
		preco = preco.replace('.','');
		preco = preco.replace(',','.');
	}

	if (qtde && preco){
		total = qtde * preco;
		total = total.toFixed(2);
		total = total.replace('.',',');
	}

	document.getElementById('peca_total_'+linha_form).value = total;

	//Totalizador
	var total_pecas = 0;
	$("input[rel='total_pecas']").each(function(){
		if ($(this).val()){
			tot = $(this).val();
			tot = tot.replace('.','');
			tot = tot.replace(',','.');
			tot = parseFloat(tot);
			total_pecas += tot;
		}
	});

	total_pecas = total_pecas.toFixed(2);
	total_pecas = total_pecas.replace('.',',');
	document.getElementById('total_pedido').value = total_pecas;

}

function busca_condicao(posto, condicao_atual = null){

	$.ajax({
        url: 'pedido_cadastro_blackedecker.php',
        type: "POST",
        data: {ajax_busca_condicao: true, posto_codigo: posto, condicao_atual: condicao_atual},
        timeout: 7000
    }).fail(function(){
        alert('Falha ao buscar condição');
    }).done(function(data){

        if (data != "erro") {

        	$("#condicao").prop("disabled", false);
            $("#condicao").html(data);
            $("#texto_posto").hide();

        }
    });
}

$(document).ready(function(){

	if ($("input[name=codigo_posto]").val() != "") {
		busca_condicao($("input[name=codigo_posto]").val(), $('#condicao_atual').val());
	}

	$("input[name^=peca_qtde_]").blur(function(){
		var posto = $("input[name=codigo_posto]").val();
		var linha = $(this).attr("rel");
		var peca = $("input[name^=peca_referencia_"+linha+"]").val();
		var verificou = $(this).next("input[name^=verificou_pedido_]").val();
		var campo = $(this).next("input[name^=verificou_pedido_]");
		var descricao = $("input[name^=peca_descricao_"+linha+"]").val();

		var qtde = $(this).val();
		var qtde_new = $(this);
		var qtde_peca_antiga = $("input[name^=peca_qtde_antiga_"+linha+"]");
		if(verificou == ""){
			if(posto == ""){
				alert("Informe um Posto Autorizado");
			}else{
				$.ajax({
					url: "pedido_cadastro_blackedecker.php",
					dataType: "GET",
					data: "ajax_pedido=sim&posto="+posto+"&peca="+peca,
					success: function(retorno){
						if(retorno != ""){
							Shadowbox.open({
								content:"<div style='background:#FFFFFF;height:100%;text-align:center;'>"+retorno+"</div>",
								player:	"html",
								title:	"Efetuar Pedido",
								width:	300,
								height:	110,
								options: {onFinish: function(){
									$("#sb-nav-close").hide();
								},
										overlayColor:'#fcfcfc' }
							});
							$(campo).val("sim");
						}
					}
				});
			}
		}

		$.ajax({ //hd_chamado=2543280
			url: "pedido_cadastro_blackedecker.php",
			dataType: "GET",
			data: "valida_multiplo=sim&posto="+posto+"&peca="+peca+"&qtde="+qtde,
			success: function(retorno){
				var resposta = retorno.responseText;
				resposta = retorno.split("|");
				if(resposta[0] == "ok"){
					//qtde_peca_antiga.val("<td>Peça:   <strong>"+descricao+"</strong> MUDOU A QUANTIDADE DE <strong>"+qtde+"</strong> PARA: <strong>"+resposta[1]+"</strong></td>");
					qtde_peca_antiga.val(descricao+"|"+qtde+"|"+resposta[1]);
					qtde_new.val(resposta[1]);
					setTimeout(fnc_calcula_total(linha),1000);
				}
			}
		});

		setTimeout(fnc_calcula_total(linha),1000);
	});

	$("#chamado_sac").blur(function(){
		var posto = $("input[name=codigo_posto]").val();
		var chamado = $(this).val();
		if(posto == ""){
			alert("Informe um Posto Autorizado");
		}else{
			if(chamado != ""){
				$.ajax({
					url: "pedido_cadastro_blackedecker.php",
					dataType: "GET",
					data: "ajax_chamado=sim&posto="+posto+"&chamado="+chamado,
					success: function(retorno){
						var data = retorno.split('|');
						if(data[0] == "ok"){
							if(data[1] == ""){
								var link = 'Atendimento não encontrado';
								$("input[name=chamado_sac]").val("");
							}else{
								var chamado_link = data[1];
								var link = "<a href='helpdesk_cadastrar.php?hd_chamado="+chamado_link+"' target='_blank'>"+chamado+"</a>";
							}
							$("#hd_chamado_sac_span").html(link).show();
						}
					}
				});
			}else{
				$("#hd_chamado_sac_span").html("").hide();
			}
		}
	});
});

function efetuaPedido () {
	var total = $("input[name=total_pedido]").val();
	total = total.replace('.','');
	total = total.replace(',','.');
	if(total < 100){

		msg_valor = 'ESTE PEDIDO TEM VALOR ABAIXO DE R$ 100,00. DESEJA REALMENTE GRAVAR ESTE PEDIDO?';

		$('.continuar_pedido').show();
		$(".msg_modal").append("<p>"+msg_valor+"</p>"); 
		$(".radios").show();
		$(".btns").hide();
		$(".msg_gravar_pedido").hide();

		$('#prosseguir_modal').click(function() {
			$('.continuar_pedido').hide();
			$(".msg_modal").html("");
			confirmaPedido();
		});

		$('#cancelar_prosseguir').click(function() {
			$('.continuar_pedido').hide();
			$(".msg_modal").html("");
			$("input[name=btn_acao]").val("");
		});

		$("input[name=confirma_pedido]").click(function() {
			opcao_pedido($("input[name=confirma_pedido]").val());			
		});

	}else{
		confirmaMultiplo();
		//document.frm_pedido.submit();
	}
}

function finalizar(){
	var posicao = 0;
	var referencia = "";
	var qtdeDemanda = "";
	var auditoria = false;
	let pedido_id = "";
	$(".referencia_peca_finalizar").each(function(){
		referencia = $(this).text();
		quantidade = parseInt($('.qtde_peca_finalizar:eq('+posicao+')').text());
		qtdeDemanda = parseInt(VerificaDemanda(referencia));

		if( (quantidade > qtdeDemanda) && quantidade > 3 ){
			auditoria = true;
		}
		posicao++;
	});
	if(auditoria){
		pedido_id = $("#pedido_id").val();
		Shadowbox.open({
			content: 'motivo_demanda.php?pedido='+pedido_id,
			player:	"iframe",
			title: 	"Motivo Demanda",
			width:	1200,
			height:	600
		});
		//$(".modal_finalizar").show();	
	}else{
		$("input[name=btn_acao]").val('finalizar');

		document.frm_pedido.submit();
	}
}

function submitFormDemanda() {
	$("input[name=btn_acao]").val('finalizar');
	document.frm_pedido.submit();
}

function confirmaMultiplo(){ //hd_chamado=2543280
	var msg = "";
	var result = "";
	var td = "";
	$("input[name^='peca_qtde_antiga_']").each(function(){
		var msg_valor = $(this).val();
		if(msg_valor != ''){
			result = $(this).val();
			msg = result.split("|");

			if(msg[1] != msg[2]){
				td +='<tr height="20"><td>'+msg[0]+'</td><td>'+msg[1]+'</td><td>'+msg[2]+'</td></tr>';
			}
		}
	});

	if (td == "") {
		finalizarPedido('t');
	} else {

		Shadowbox.open({
			content:"<div style='background:#FFFFFF;height:100%;text-align:center;'>\
							<br><p style='font-size:14px;font-weight:bold'>Peças com Quantidade Multiplas</p>\
							<p style='font-weight:bold;'>\
							<table border='1' width='800' id='resultado' cellspacing='1' cellpadding='0' align='center'>\
							<tr height='20' class='menu_top'>\
								<td>Descrição</td><td>Qdte Digitada</td><td>Qtde Alterada</td>\
							</tr>\
								"+td+"\
							</table>\
								Deseja gravar o pedido com estas quantidades?\
								<input type='radio' name='confirma_pedido' value='t' checked> SIM\
								<input type='radio' name='confirma_pedido' value='f'> NÃO\
							</p>\
							<p>\
								<input type='button' value='Prosseguir' onclick=\"javascript:finalizarPedido(); Shadowbox.close();\">\
							</p>\
						</div>",
			player:	"html",
			title:	"Confirmar multiplo",
			width:	1000,
			height:	600,
			options: {onFinish: function(){
				$("#sb-nav-close").hide();
			},
					overlayColor:'#000000' }
		});
	}
}

var confirmar = "";

function opcao_pedido(opt){
	confirmar = opt;
}

function confirmaPedido(){
	//var confirmar = $("input[name=confirma_pedido]:checked").val();

	if(confirmar == "t"){ //hd_chamado=2543280
		setTimeout(function(){
			var submit = true;
			$("input[name^='peca_qtde_antiga_']").each(function(){
				var valores = '';
				var qtde_antiga = $(this).val();
				if(qtde_antiga != '' && qtde_antiga != undefined){
					valores = qtde_antiga.split("|");
					if(valores[1] != valores[2]){
						submit = false;
						confirmaMultiplo();
						return;
					}else{
						submit = true;
					}
				}
			});
			if(submit == true){
				document.frm_pedido.submit();
			}
		},1000);
	}else{
		$("input[name=btn_acao]").val("");
	}
}
function finalizarPedido(conf = null){
	var confirmar = "";
	if (conf == 't') {
		confirmar = 't';
	} else {
		confirmar = $("input[name=confirma_pedido]:checked").val();
	}

	if(confirmar == "t"){
		document.frm_pedido.submit();
	}else{
		Shadowbox.close();
		$("input[name=btn_acao]").val("");
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP);
// Fim -->
</script>

<style type="text/css">

#obrigatorio{
	color: red;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
   font-size:14px;
}

.linha2 {
	font: bold 14px "Arial";
    color: #FFFFFF;
	border-top: 1px solid #ffffff;
	text-align: center;
	background: #596d9b;
	margin-left: 0px;
	padding: 5px;
}

.linha {
	font: bold 14px "Arial";
    color: #FFFFFF;
	border-top: 1px solid #ffffff;
	border-left: 1px solid #ffffff;
	text-align: center;
	background: #596d9b;
	margin-left: -20px;
	padding: 5px;
}

.listra_branca {
	background: #ffffff !important;
}

.listra_azul {
	background: #D9E2EF !important;
}

#conteudo  input {
	margin-top: 14px;
}

#conteudo  span {
	margin-top: 14px;
}

.conteudo {
	margin-top: -33px;
}

</style>

<? if (strlen ($msg_erro) > 0) { ?>
<br>
<div class="alert alert-danger" id='alertaErro'><h4>
			<?
			if (strpos($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0)
				$msg_erro = "Esta ordem de serviço já foi cadastrada";

			echo $msg_erro;
			?>
</h4></div>
<? } ?>

<? if (strlen($exportado) > 0) { ?>
<br>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align="center" width="100%" class="table_line1" bgcolor='#F4F4F4'>
		<p align='justify'><font size='1'><b>
		<font color='#FF0000'>O SEU PEDIDO <? echo $pedido_blackedecker ?> FOI EXPORTADO EM <? echo $exportado ?></font>, SE NECESSÁRIO, INCLUA OS ITENS FALTANTES E FINALIZE NOVAMENTE, AGUARDANDO A PRÓXIMA EXPORTAÇÃO.
		</b></font></p>
	</td>
</tr>
</table>
<? } ?>

<?
#if (strlen($pedido) > 0) {
?>
<!--<br>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align="center" width="100%" class="table_line1" bgcolor='#F4F4F4'>
		<p align='justify'><font size=1>
		<font color='#FF0000'><b>O SEU PEDIDO NÚMERO: <? echo $pedido_blackedecker ?> SERÁ EXPORTADO ÀS 13h55</font>, SE NECESSÁRIO, INCLUA OS ITENS FALTANTES E FINALIZE NOVAMENTE. SE O PEDIDO NÃO FOR FINALIZADO APÓS A INCLUSÃO DE NOVOS ITENS, SERÁ EXPORTADO PARA A BLACK & DECKER APENAS O PEDIDO FINALIZADO INICIALMENTE</b>.<br>
		</font></p>
	</td>
</tr>
</table>-->
<?# } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>" >
<div class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Itens do Pedido</div>
    <br/>
	<input type="hidden" name="pedido" id="pedido_id" value="<? echo $pedido ?>">
	<input type="hidden" name="resposta_shadow" id="resposta_shadow" value="">
	<input type="hidden" name="pedido_blackedecker" value="<? echo $pedido_blackedecker ?>">

	<div class='row-fluid'>
		<div class='span1'></div>

        <div class='span2'>
            <div class='control-group <?=(in_array("posto", $campos_erro["campos"])) ? "error" : ""?>'>
            	<h5 class="asteristico">*</h5>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $campos_erro["campos"])) ? "error" : ""?>'>
            	<h5 class="asteristico">*</h5>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $nome_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2 cond_pag' style="display: none;">
        	<div class='control-group <?=(in_array("condicao", $campos_erro["campos"])) ? "error" : ""?>'>
        		<h5 class="asteristico">*</h5>
                <label class='control-label' for='descricao_posto'>Condição Pagamento</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <?
							if((strlen($msg_erro) > 0 && $_POST["condicao"] == "1905") || (strlen($pedido) > 0 && $condicao == "1905")){

								$sql = "SELECT condicao
										FROM tbl_condicao
										WHERE
											fabrica = {$login_fabrica}
										AND (descricao = 'Pagamento Antecipado' OR descricao = 'Garantia')
										";

							}else{

								$sql = "SELECT condicao
										FROM tbl_condicao
										WHERE fabrica = $login_fabrica
										ORDER BY lpad(trim(tbl_condicao.codigo_condicao),10,'0');";
							}

							$res = pg_exec($con,$sql);

							if (pg_numrows($res) > 0) {

								if ($login_fabrica == 1) {
									$disabled = "disabled";
								}

								if (!empty($pedido)) { ?>
									<input type='hidden' value='<?= $condicao ?>' id='condicao_atual' />
								<?php
								}

								echo "
								<select $disabled name='condicao' id='condicao' size='1' class='span12'  onFocus=\"nextfield ='peca_referencia_0'\">";
								echo "<option value=''></option>";
								for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
									$cond_pagamento = pg_result($res,$i,condicao);

									if($_POST["condicao"] == "1905" || $condicao == "1905"){
										echo "<option value='".$cond_pagamento."'";
										if ($_POST["condicao"] == $cond_pagamento || $condicao == $cond_pagamento) echo " selected";
										echo ">".pg_result($res,$i,descricao)."</option>";
									}else{
										if(in_array($login_admin,array(155,232,2319)) AND !in_array($cond_pagamento,array(51,62))){
											echo "<option value='".$cond_pagamento."'";
											if ($condicao == $cond_pagamento ) echo " selected";
											echo ">".pg_result($res,$i,descricao)."</option>";
										} else{
											if(in_array($cond_pagamento,array(51,62))){
												echo "<option value='".$cond_pagamento."'";
												if ($condicao == $cond_pagamento ) echo " selected";
												echo ">".pg_result($res,$i,descricao)."</option>";
											}
										}
									}
								}
								echo "</select>";
							}

							if ($login_fabrica == 1) {
							?>
								<span id="texto_posto" style="color: darkred;">Preencha o posto</span>
					<?php
							}
					?>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
        	<div class='control-group <?=(in_array("categoria_pedido", $campos_erro["campos"])) ? "error" : ""?>'>
        		<h5 class="asteristico">*</h5>
                <label class='control-label' for='descricao_posto'>Categoria do Pedido</label>
                <div class='controls controls-row'>
                    <div class='span12 '>
                        <select name="categoria_pedido" class="span12">
				            <option value="">SELECIONE</option>
				            <option <?=($categoria_pedido == "cortesia") ? "selected" : ""?> value="cortesia">CORTESIA</option>
				            <option <?=($categoria_pedido == "credito_bloqueado") ? "selected" : ""?> value="credito_bloqueado">CRÉDITO BLOQUEADO</option>
				            <option <?=($categoria_pedido == "erro_pedido") ? "selected" : ""?> value="erro_pedido">ERRO DE PEDIDO</optiuon>
				            <option <?=($categoria_pedido == "kit") ? "selected" : ""?> value="kit">KIT DE REPARO</option>
				            <option <?=($categoria_pedido == "midias") ? "selected" : ""?> value="midias">MÍDIAS</option>
				            <option <?=($categoria_pedido == "outros") ? "selected" : ""?> value="outros">OUTROS</option>
				            <option <?=($categoria_pedido == "valor_minimo") ? "selected" : ""?> value="valor_minimo">VALOR MÍNIMO</option>
				            <option <?=($categoria_pedido == "vsg") ? "selected" : ""?> value="vsg">VSG</option>
				            <option <?=($categoria_pedido == "divergencia") ? "selected" : ""?> value="divergencia">DIVERGÊNCIAS LOGÍSTICA/ESTOQUE</option>
				            <option <?=($categoria_pedido == "problema_distribuidor") ? "selected" : ""?> value="problema_distribuidor">PROBLEMAS COM DISTRIBUIDOR</option>
				            <option <?=($categoria_pedido == "acessorios") ? "selected" : ""?> value="acessorios">ACESSÓRIOS</option>
				            <option <?=($categoria_pedido == "item_similar") ? "selected" : ""?> value="item_similar">ITEM SIMILAR </option>
				        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<br>
<div class=' form-inline tc_formulario'>
	<div class='titulo_tabela '>Peças</div>
    <div class="row-fluid" style="line-height: 35px !important">
        <div class="span2 tac" ><div class="linha2">Referência</div></div>
        <div class="span3 tac" ><div class="linha">Descrição</div></div>
		<div class="span1 tac" ><div class="linha">Qtde</div></div>
		<div class="span2 tac" ><div class="linha">Valor Unit.</div></div>
		<div class="span2 tac" ><div class="linha">Valor Total</div></div>
		<div class="span2 tac" ><div class="linha">OS</div>    </div>
    </div>
    <div class="conteudo" id="conteudo"> 
	    <?php
	    for ($i = 0 ; $i < $qtde_item ; $i++) {
			if (strlen($msg_erro) > 0) {
				$peca_referencia = $_POST["peca_referencia_" . $i];
				$peca_descricao  = $_POST["peca_descricao_"  . $i];
				$peca_qtde       = $_POST["peca_qtde_"       . $i];
				$peca_preco      = $_POST["peca_preco_"      . $i];
				$peca_total      = $_POST["peca_total_"      . $i];
				$peca_os		 = $_POST["os_".$i."_hidden"];
				$peca_sua_os	 = $_POST["os_".$i];
			}

			$display = ($peca_os) ? "" : "display:none;";
			$link = "<a href='os_press.php?os={$peca_os}' target='_blank'>{$peca_sua_os}</a>";

			echo "<input type='hidden' name='item_$i' value='$item'>\n";

			$cor = ($i % 2 == 0) ? "#F7F5F0": "#F1F4FA";
			if (in_array($i,$linha_erro) and strlen ($msg_erro) > 0) $cor = "#FFCCCC";

			echo "<input type='hidden' name='peca_qtde_antiga_$i' value=''>\n"; //

			?>
	    <div class='row-fluid <?php echo ($i % 2 == 0) ? "listra_azul" : "listra_branca"; ?>'>

	        <div class='span2'>
	            <div class='control-group'>
	                <div class='controls controls-row'>
	                    <div class='span11  input-append'>
	                    	<input type="text" id="peca_referencia_<?=$i?>" name="peca_referencia_<?=$i?>" class='span12'  maxlength="20" value="<? echo $peca_referencia ?>" >
							<span class='add-on' ><i class='icon-search' onclick="fnc_pesquisa_peca (window.document.frm_pedido.peca_referencia_<?=$i?>, window.document.frm_pedido.peca_descricao_<?=$i?>, window.document.frm_pedido.peca_preco_<?=$i?>,window.document.frm_pedido.peca_qtde_<?=$i?>,'referencia')" ></i></span>

	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span3'>
	            <div class='control-group'>
	                <div class='controls controls-row'>
	                    <div class='span11  input-append'>
	                    	<input type="text" id="peca_descricao_<?=$i?>"  name="peca_descricao_<?=$i?>" class='span12' value="<? echo $peca_descricao ?>" >
							<span class='add-on' ><i class='icon-search' onclick="fnc_pesquisa_peca (window.document.frm_pedido.peca_referencia_<?=$i?>, window.document.frm_pedido.peca_descricao_<?=$i?>, window.document.frm_pedido.peca_preco_<?=$i?>,window.document.frm_pedido.peca_qtde_<?=$i?>,'descricao')" ></i></span>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span1'>
	            <div class='control-group'>
	                <div class='controls controls-row'>
	                    <div class='span12'>
					        <?php
					        echo "<input type='text' name='peca_qtde_$i' id='peca_qtde_$i' rel='$i' size='5'  value='$peca_qtde' class='span12'";
							if ($prox <= $done) { echo " onFocus=\"nextfield ='peca_referencia_$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}
							echo " style='text-align:center;'>";
							echo "<input type='hidden' name='verificou_pedido_{$i}' value=''>";

					        ?>
					    </div>
					</div>
				</div>
			</div>
			<div class='span2'>
	            <div class='control-group'>
	                <div class='controls controls-row'>
	                    <div class='span12'>
					        <?php echo "<input type='text' name='peca_preco_$i' id='peca_preco_$i' size='5'  value='$peca_preco' class='span12' style='text-align:right;' readonly>";?>

					    </div>
					</div>
				</div>
			</div>
			<div class='span2'>
	            <div class='control-group'>
	                <div class='controls controls-row'>
	                    <div class='span12'>
					        <?php echo "<input type='text' name='peca_total_$i' id='peca_total_$i' rel='total_pecas' size='5'  value='$peca_total' class='span12' style='text-align:right;' readonly>"; ?>

					    </div>
					</div>
				</div>
			</div>
			<div class='span2' id='<?php echo "os_{$i}_col"; ?>' >
	            <div class='control-group'>
	                <div class='controls controls-row'>
	                    <div class='span12'>
					        <?php 
								echo "<input type='text' name='os_$i' id='os_$i' size='13'  value='$peca_sua_os' class='span12'>";
								echo "<br/><span style='{$display}font-size:12px;' id='os_{$i}_link'>$link</span>";
								echo "<input type='hidden' name='os_{$i}_hidden' id='os_{$i}_hidden' value='$peca_os'>";
								echo "<input type='hidden' name='obs_{$i}_hidden' id='obs_{$i}_hidden' value=''>";

					        ?>

					    </div>
					</div>
				</div>
			</div>



	    </div>

	    <?php } ?>
	</div>
    <div class="row-fluid titulo_tabela">
    		<div class="span8" style="text-align: right;">Total</div>
    		<div class="span4">
    			<input type='text' name='total_pedido' id='total_pedido' class='span12' value='<?=$total_pedido?>' size='5'>
    		</div>
    </div>
    <div class="row-fluid">
    	<div class="span1"> </div>
    	<div class="span10">
    		<input type='hidden' name='qtde_item' value='<?=$qtde_item?>'>
    		<input type='hidden' name='tabela' id="tabela" value='1053'>
    		<input type='hidden' name='tipo_pedido' id="tipo_pedido" value='86'>
			Justificativa <br />
			<div class='control-group <?=(in_array("justificativa", $campos_erro["campos"])) ? "error" : ""?>'>
            	<h5 class="asteristico">*</h5>
				<textarea name='justificativa' id='justificativa_text' class='span12'><?=$justificativa?></textarea>
    		</div>
    	</div>
    </div>
    <br />
    <div class="row-fluid">
    	<div class="span1"></div>
    	<div class="span4">		
    		<label class='control-label' for='chamado_sac'>CHAMADO SUPORTE/SAC</label>
			<input type='text' name='chamado_sac' id='chamado_sac' value='<?=$chamado_sac?>' size='7' class='frm'>
			<span id="hd_chamado_sac_span" style='font-size:12px;'></span>
		</div>
	</div>
	<div class="row-fluid">
    	<div class="span12 tac"> 
			<input type='hidden' name='btn_acao' value=''>
			<button class="btn btn-primary" type="button" onclick="gravar_pedido(<?=$qtde_item?>)" style="cursor:pointer;">Gravar</button>
		</div>
	</div>
<br>
<table border="0" width='700' id='resultado' cellspacing="1" cellpadding="0" align='center'>
<br>
<div class="row-fluid">
	<div class="span1"> </div>
	<div class="span10"> 
		<p align='justify'><font size='1'><b>PARA CONTINUAR A DIGITAR ITENS NESTE PEDIDO, BASTA GRAVAR E EM SEGUIDA CONTINUAR DIGITANDO.</b></font></p>
	</div>
	<div class="span1"> </div>
</div>
<div class="row-fluid">
	<div class="span1"> </div>
	<div class="span10"> 
		<p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.</b></font></p>
	</div>
	<div class="span1"> </div>
</div>


<br>

<? if (strlen($pedido) > 0) { ?>



<table width="700" border="0" cellpadding="3" cellspacing="1" align="center" class='table table-striped table-bordered table-fixed'>
<tr class="titulo_coluna">
	<th colspan="6" align="center" >
		<b>Resumo do Pedido</b>
	</th>
</tr>
<tr class="titulo_coluna">
	<th width="25%" align='center' >
		<b>Referência</b>
	</th>
	<th width="50%" align='center' >
		<b>Descrição</b>
	</th>
	<th width="15%" align='center' >
		<b>Quantidade</b>
	</th>
	<th width="10%" align='center' >
		<b>Preço</b>
	</th>
	<!--
	<td width="10%" align='center' class="menu_top">
		<b>Estoque</b>
	</td>
	<td width="10%" align='center' class="menu_top">
		<b>Previsão</b>
	</td>
	-->
</tr>
<?
	$sql = "SELECT	a.*        ,
					referencia ,
					descricao,
					tbl_peca.parametros_adicionais
			FROM	tbl_peca
			JOIN	(
						SELECT	*
						FROM	tbl_pedido_item
						WHERE	pedido = $pedido
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";
	$res = @pg_exec($con,$sql);
	$total = 0;
	for ($i = 0 ; $i < @pg_numrows($res) ; $i++) {

		$parametros_adicionais = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);

		$estoque 	= ucfirst($parametros_adicionais["estoque"]);
		$previsao 	= mostra_data($parametros_adicionais["previsao"]);

		if($estoque == "Disponivel" or $estoque == "Disponível"){
			$previsao = " - ";
		}

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor' >";

		echo "<td width='25%' align='left' class='table_line1' nowrap>";

		echo "<a href='$PHP_SELF?delete=" . pg_result ($res,$i,pedido_item) . "&pedido=$pedido'>";
		echo "<button class='btn btn-danger' align='absmiddle' hspace='' type='button'>Excluir</button>";
		echo "</a>";
		echo "&nbsp;";
		echo "<span class='referencia_peca_finalizar'>";
		echo pg_result ($res,$i,referencia);
		echo "</span>";
		echo "</td>";

		echo "<td width='50%' align='left' class='table_line1'>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='15%' class='table_line1'>";
		echo "<span class='qtde_peca_finalizar'>";
		echo pg_result ($res,$i,qtde);
		echo "</span>";
		echo "</td>";

		echo "<td width='10%' style='text-align:right' class='table_line1'>";
		echo number_format (pg_result ($res,$i,preco),2,",",".");
		echo "</td>";

		/*echo "<td width='10%' align='right' class='table_line1'>";
		echo $estoque;
		echo "</td>";

		echo "<td width='10%' align='center' class='table_line1'>";
		echo $previsao;
		echo "</td>";*/

		echo "</tr>";

		$total = $total + (pg_result ($res,$i,preco) * pg_result ($res,$i,qtde));
	}
?>

<tr>
	<td align="center" colspan="3" >
		<b>T O T A L</b>
	</td>
	<td align='right'  style='text-align:right'>
		<b>
		<? echo number_format ($total,2,",",".") ?>
		</b>
	</td>
	<!--<td  class="menu_top"></td>
	<td  class="menu_top"></td>-->
</tr>
<? if (strlen($exportado) == 0) { ?>
<tr>
	<td colspan="4" class='table_line1' align='left'>
		<a href="<? echo $PHP_SELF ?>?excluir=tudo&pedido=<?echo $pedido?>"><font color="#FF0000">Excluir Todos Itens</font></a>
	</td>
</tr>
<? } ?>
</table>

<br>

<center>
<!--<a href="<? echo $PHP_SELF ?>?pedido=<? echo $pedido ?>&finalizar=1&unificar=t">-->

<!--<button class="btn btn-success" type="button" style="cursor: hand;" onclick="javascript: document.frm_pedido.btn_acao.value='finalizar'; document.frm_pedido.submit();">Finalizar</button>-->

<input type="hidden" name="auditoria_observacao" id="auditoria_observacao" value="">
<input type="hidden" name="auditoria_tipo" id="auditoria_tipo" value="">

<button class="btn btn-success" type="button" style="cursor: hand;" onclick="finalizar();">Finalizar</button>
<button class="btn btn-danger" type="button" style="cursor: hand;" onclick="javascript: document.frm_pedido.btn_acao.value='apagar'; document.frm_pedido.submit();">Apagar</button>
<a href="<? echo $PHP_SELF ?>"><button class="btn btn-primary" type="button">Lançar Novo Pedido</button></a>
<!-- <a rel='shadowbox' name="btnAuditorLog" href='relatorio_log_alteracao_new.php?parametro=tbl_pedido&id=<?=$pedido?>'><button class="btn btn-warning" type="button">Log Pedido</button></a> -->
<!-- <button class="btn btn-warning" type="button" id="visualiza_log_item" data-pedido="<?=$pedido?>" >Log Itens Pedido</button> -->
</center>
</form>
<br>
<br>
<? } ?>
</table>
<!-- Modal Gravar -->
<div class="modal continuar_pedido" style="display: none;" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="text-align: center;">
        <h5 class="modal-title">ATENÇÃO !</h5>
      </div>
      <div class="modal-body msg_modal" style="text-align: center;">
      </div>
      <div class="modal-body msg_gravar_pedido" style="text-align: center;">
      	<p>DESEJA REALMENTE GRAVAR ESSE PEDIDO ?</p>
  	  </div>
      <div class="modal-footer btns">
        <button type="button" id="cancelar_modal" class="btn btn-danger">Cancelar</button>
        <button type="button" id="continuar_modal" class="btn btn-success">Continuar</button>
      </div>
      <div class="modal-footer radios" style="text-align: center;">
      	<input type='radio' name='confirma_pedido' value='t' />&nbsp;SIM&nbsp;&nbsp;&nbsp;
      	<input type='radio' name='confirma_pedido' value='f' />&nbsp;NÃO
      	<br /><br /><br />
      	<button type="button" id="prosseguir_modal" class="btn btn-primary">Prosseguir</button>
      	<button type="button" id="cancelar_prosseguir" class="btn btn-danger">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Finalizar -->
<div class="modal modal_finalizar" style="display: none;" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="text-align: center;">
        <h5 class="modal-title">ATENÇÃO !</h5>
      </div>
      <div class="modal-body msg_demanda" style="text-align: center;">
      	<p>A quantidade solicitada de peças excede a demanda mensal para toda a rede autorizada, por favor, verifique se a quantidade está correta e corrija se necessário. Se a quantidade estiver correta informe o motivo para controlarmos melhor nossa demanda e evitarmos possíveis faltas. Essa informação será reportada para a área de supply.</p>
  	  </div>
  	  <div>
  	  	<table >
  	  		<tr>
	  	  		<td>
	  	  			Auditoria: 
	  	  		</td>
	  	  		<td>
	  	  			<span id="obrigatorio"></span><br>
	  	  			<select name="" id="auditoria_demanda"> 
	  	  				<option value="">Auditoria:</option>
	  	  				<option value="Pontual">Pontual</option>
	  	  				<option value="Rotineira">Rotineira</option>
	  	  			</select>
	  	  		</td>
	  	  	</tr>
	  	  	<tr>
	  	  		<td>
	  	  			Observação: 
	  	  		</td>
	  	  		<td>
						<textarea name='observacao' id="observacao_demanda" style="width: 400px; height: 100px;" ></textarea>
	  	  		</td>
	  	  	</tr>
  	  	</table>
  	  </div>
      <div class="modal-footer btns">
        <button type="button" id="cancelar_finalizar" class="btn btn-danger">Cancelar</button>
        <button type="button" id="gravar_modal_finalizar" class="btn btn-success">Gravar</button>
      </div>
    </div>
  </div>
</div>




<? include "rodape.php"; ?>
