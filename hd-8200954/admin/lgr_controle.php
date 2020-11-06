<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";




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
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}



function converte_data($date){
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

if (strlen($_POST["filtroTodos"]) > 0) $filtroTodos = $_POST["filtroTodos"];
if (strlen($_GET["filtroTodos"])  > 0) $filtroTodos = $_GET["filtroTodos"];

if (strlen($_POST["filtroObrigatorio"]) > 0) $filtroObrigatorio = $_POST["filtroObrigatorio"];
if (strlen($_GET["filtroObrigatorio"])  > 0) $filtroObrigatorio = $_GET["filtroObrigatorio"];

if (strlen($_POST["filtroNaoObrigatorio"]) > 0) $filtroNaoObrigatorio = $_POST["filtroNaoObrigatorio"];
if (strlen($_GET["filtroNaoObrigatorio"])  > 0) $filtroNaoObrigatorio = $_GET["filtroNaoObrigatorio"];

if (strlen($_POST["data_inicial"]) > 0) $data_inicial = $_POST["data_inicial"];
if (strlen($_GET["data_inicial"])  > 0) $data_inicial = $_GET["data_inicial"];

if (strlen($_POST["data_final"]) > 0) $data_final = $_POST["data_final"];
if (strlen($_GET["data_final"])  > 0) $data_final = $_GET["data_final"];

if (strlen($_POST["filtroRecebidos"]) > 0) $filtroRecebidos = $_POST["filtroRecebidos"];
if (strlen($_GET["filtroRecebidos"])  > 0) $filtroRecebidos = $_GET["filtroRecebidos"];

if (strlen($_POST["nota_devolucao"]) > 0) $nota_devolucao = $_POST["nota_devolucao"];
if (strlen($_GET["nota_devolucao"])  > 0) $nota_devolucao = $_GET["nota_devolucao"];

if (strlen($_POST["posto_devolucao"]) > 0) $posto_devolucao = $_POST["posto_devolucao"];
if (strlen($_GET["posto_devolucao"])  > 0) $posto_devolucao = $_GET["posto_devolucao"];

if (strlen($_POST["nf"]) > 0) $nf = $_POST["nf"];
if (strlen($_GET["nf"])  > 0) $nf = $_GET["nf"];

if (strlen($_POST["data_nf_envio"]) > 0) $data_nf_envio = $_POST["data_nf_envio"];
if (strlen($_GET["data_nf_envio"])  > 0) $data_nf_envio = $_GET["data_nf_envio"];

if (strlen($_POST["posto_devendo"]) > 0) $posto_devendo = $_POST["posto_devendo"];
if (strlen($_GET["posto_devendo"])  > 0) $posto_devendo = $_GET["posto_devendo"];

if (strlen($_GET["devolucao"])  > 0) $devolucao = $_GET["devolucao"];

if (strlen($_GET["pendencias_postos"])  > 0) $pendencias_postos = $_GET["pendencias_postos"];

$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
//if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];


$layout_menu = "financeiro";
$title = "Consulta Notas de Devolu��o";

$agrupar = "true";

if (isset($_POST["btn_acao"])){
	$btn_acao = trim($_POST["btn_acao"]);

	if ($btn_acao == "gravar"){

		$qtde_linhas = trim($_POST['qtde_notas']);
		$acao        = trim($_POST['acao']);

		if (strlen($qtde_linhas)>0 AND strlen($acao)>0 ){

			$res = @pg_exec($con,"BEGIN TRANSACTION");

			for ($i=0 ; $i < $qtde_linhas ; $i++){
				
				$faturamento = trim($_POST['faturamento_'.$i]);

				if (strlen($faturamento)==0) continue;

				if ($acao == "confirmar"){
					$sql = "UPDATE tbl_faturamento 
								SET conferencia = CURRENT_TIMESTAMP,
								cancelada = NULL,
								devolucao_concluida = 't'
							WHERE faturamento = $faturamento
							AND fabrica = $login_fabrica
							AND distribuidor IS NOT NULL";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "UPDATE tbl_faturamento_item 
							SET qtde_inspecionada = qtde
							WHERE faturamento = $faturamento";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if ($acao == "nao_recebida"){

					$sql = "SELECT distribuidor,nota_fiscal,emissao,extrato_devolucao
							FROM tbl_faturamento
							WHERE faturamento = $faturamento
							AND fabrica = $login_fabrica";
					$res = pg_exec($con,$sql);
					if (pg_numrows($res)>0){
						$posto       = pg_result($res,0,distribuidor); # distribuidor � o posto
						$nota_fiscal = pg_result($res,0,nota_fiscal);
						$emissao     = pg_result($res,0,emissao);
						$extrato     = pg_result($res,0,extrato_devolucao);
					}else{
						continue;
					}

					$sql = "UPDATE tbl_faturamento 
							SET 
								conferencia = CURRENT_TIMESTAMP,
								cancelada   = CURRENT_DATE,
								devolucao_concluida = 'f'
							WHERE faturamento = $faturamento
							AND fabrica = $login_fabrica
							AND distribuidor IS NOT NULL";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if (1==2){#nao precisa disso - Fabio
						$sql = "INSERT INTO tbl_lgr_cancelado 
								(posto,nota_fiscal,data_cancelamento,data_nf,usuario,fabrica,foi_cancelado)
								VALUES 
								($posto,$nota_fiscal,CURRENT_DATE,'$emissao',$login_admin,$login_fabrica,'t')";
						//echo "<hr>".nl2br($sql);
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql3="SELECT peca,qtde FROM tbl_faturamento_item WHERE faturamento = $faturamento";
						$res2 = pg_exec($con,$sql3);
						echo "<hr>".nl2br($sql3);

						for ($j=0; $j< pg_numrows($res2); $j++){
							$peca = pg_result($res2,$j,peca);
							$qtde = pg_result($res2,$j,qtde);

							$sqlAtualiza = "UPDATE tbl_extrato_lgr
											SET qtde_nf = qtde_nf - $qtde
											WHERE peca	= $peca
											AND extrato = $extrato";
							$resA = pg_exec($con,$sqlAtualiza);
							echo "<hr>".nl2br($sqlAtualiza);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}

			if (strlen($msg_erro)>0){
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}			
			else {
				$res = @pg_exec ($con,"COMMIT TRANSACTION");
				$msg = "Gravado com sucesso!";
			}
		}
	}
}	

$verPopup    = trim($_GET['pop_up']);
$xNotaFiscal = trim($_GET['nota_fiscal']);

#########################################################################################################
if ($verPopup == "sim"){

	$faturamento = $xNotaFiscal;
	$btn_acao = trim($_POST['btn_acao']);

	if (strlen($faturamento)==0){
		echo "Nenhuma nota.";
		//echo "<script languague='javascript'>this.close();</script>";
		exit;
	}

	$sql = "SELECT  faturamento,
			extrato_devolucao,
			TO_CHAR(emissao,'DD/MM/YYYY') as emissao,
			TO_CHAR(tbl_faturamento.conferencia,'DD/MM/YYYY HH24:MI') AS conferencia,
			TO_CHAR(tbl_faturamento.cancelada,'DD/MM/YYYY') AS cancelada,
			devolucao_concluida,
			nota_fiscal,
			distribuidor,
			posto,
			movimento,
			cfop
		FROM tbl_faturamento
		WHERE distribuidor IS NOT NULL
		AND fabrica        = $login_fabrica
		AND faturamento    = $faturamento";
	$resD = pg_exec ($con,$sql);
	$qtde_for=pg_numrows ($resD);

	if ($qtde_for == 0) {
		echo "Nenhuma nota encontrada.";
		echo "<script languague='javascript'>this.close();</script>";
		exit;
	}

	$nota_fiscal = pg_result($resD,0,nota_fiscal);
	$posto       = pg_result($resD,0,distribuidor); // distrib eh posto

	if ($btn_acao=="gravar_conferencia"){

		$resX = pg_exec ($con,"BEGIN TRANSACTION");

		$qtde_linhas = trim($_POST['qtde_linhas']);
		$faturamento = trim($_POST['faturamento']);

		if (strlen($qtde_linhas)==0){
			$qtde_linhas = 0;
		}

		$qtde_nao_devolvidas = 0;

		for ($i = 0; $i < $qtde_linhas ; $i++){

			$peca       = trim($_POST["peca_$i"]);
			$preco      = trim($_POST["preco_$i"]);
			$qtde_total = trim($_POST["qtde_$i"]);
			$qtde_total_inspecionada = trim($_POST["qtde_insp_$i"]);

			$sql = "UPDATE tbl_faturamento_item SET
						qtde_inspecionada = NULL
					FROM tbl_faturamento
					WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
						AND tbl_faturamento.fabrica     = $login_fabrica
						AND tbl_faturamento.faturamento = $faturamento
						AND tbl_faturamento_item.peca   = $peca
						AND tbl_faturamento_item.preco  = $preco";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($qtde_total_inspecionada)==0 OR strlen($peca)==0 OR strlen($qtde_total)==0){
				continue;
			}

			if ($qtde_total_inspecionada > $qtde_total){
				$qtde_total_inspecionada = $qtde_total;
			}

			$qtde_total_inspecionada_aux = $qtde_total_inspecionada;

			$qtde_nao_devolvidas += $qtde_total - $qtde_total_inspecionada;

			#Se digitou a quantidade inspecionada
			if ( $qtde_total_inspecionada >0 ){
				$sql = "SELECT
							tbl_faturamento_item.faturamento,
							tbl_faturamento_item.faturamento_item,
							tbl_faturamento_item.peca,
							tbl_faturamento_item.qtde,
							tbl_faturamento_item.qtde_inspecionada
						FROM tbl_faturamento
						JOIN tbl_faturamento_item USING(faturamento)
						WHERE tbl_faturamento.fabrica       = $login_fabrica
							AND tbl_faturamento.faturamento = $faturamento
							AND tbl_faturamento_item.qtde - 
								CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL
									THEN 0
									ELSE tbl_faturamento_item.qtde_inspecionada
								END
								> 0
							AND tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.preco = $preco
						ORDER BY tbl_faturamento.emissao ASC";
				$res = pg_exec ($con,$sql);
				for ( $j=0; $j< pg_numrows($res); $j++ ){

					if ( $qtde_total_inspecionada_aux <= 0 ){
						break;
					}

					$faturamento_item	= pg_result ($res,$j,faturamento_item);
					$peca				= pg_result ($res,$j,peca);
					$qtde				= pg_result ($res,$j,qtde);
					$qtde_inspecionada	= pg_result ($res,$j,qtde_inspecionada);

					if (strlen($qtde_inspecionada)==0){
						$qtde_inspecionada = 0;
					}

					$qtde = $qtde - $qtde_inspecionada;

					if ( $qtde  - $qtde_total_inspecionada_aux < 0 ){
						$qtde_atualizar = $qtde;
					}else{
						$qtde_atualizar = $qtde_total_inspecionada_aux;
					}

					$sql2 = "UPDATE tbl_faturamento_item
							SET qtde_inspecionada = (CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END) + $qtde_atualizar
							WHERE faturamento = $faturamento
							AND faturamento_item = $faturamento_item
							AND peca = $peca";
					//echo nl2br($sql2);
					//echo "<hr>";
					$res2 = pg_exec ($con,$sql2);
					$msg_erro .= pg_errormessage($con);

					$qtde_total_inspecionada_aux = $qtde_total_inspecionada_aux - $qtde_atualizar;

					if (strlen($msg_erro)>0){
						break;
					}
				}
			}
			#Se digitou ZERO - n�o inspecionou
			if ( $qtde_total_inspecionada == 0 ){
				$sql = "UPDATE tbl_faturamento_item SET
							qtde_inspecionada = 0
						FROM tbl_faturamento
						WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_faturamento.fabrica     = $login_fabrica
							AND tbl_faturamento.faturamento = $faturamento
							AND tbl_faturamento_item.peca   = $peca
							AND tbl_faturamento_item.preco = $preco";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		# Marca como conferida toda nota que o usuario clicar em Gravar
		$sql = "UPDATE tbl_faturamento
				SET conferencia = CURRENT_TIMESTAMP
				WHERE faturamento = $faturamento
				AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		# Verifica se falta alguma pe�a para devolver desta nota
		$sql = "SELECT SUM( tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END )
				FROM tbl_faturamento
				JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				WHERE tbl_faturamento.fabrica   = $login_fabrica
				AND tbl_faturamento.faturamento = $faturamento
				AND (tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END)>0";
		$res = pg_exec ($con,$sql);
		$qtde_faltante = trim(pg_result($res,0,0));

		# Se n�o tiver mais pe�as para devolver, grava como conclu�da
		if ($qtde_faltante == 0 OR strlen($qtde_faltante)==0){
			$sql = "UPDATE tbl_faturamento
					SET devolucao_concluida = 't'
					WHERE faturamento = $faturamento
					AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}else{
			# Envia um comunicado para o PA
			$sql = "INSERT INTO tbl_comunicado (
						descricao              ,
						mensagem               ,
						tipo                   ,
						fabrica                ,
						obrigatorio_os_produto ,
						obrigatorio_site       ,
						posto                  ,
						ativo                  
					) VALUES (
						'Nota Fiscal de Devolu��o - LGR',
						'O Fabricante acusou o recebimento parcial de sua NF. n� $nota_fiscal, favor entrar em contato urgente c/ o Sr. Alberto TEL:071 3379-1997, para sua regulariza��o',
						'LGR',
						$login_fabrica,
						'f',
						't',
						6359,
						't'
					);";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$resX = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Confer�ncia gravada com Sucesso!";
		}else{
			$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
		echo "<script language='javascript'>opener.window.location = opener.window.location; </script>";
	}


	if ($btn_acao=="liberar_provisorio"){
		$aux_extrato = trim($_POST['extrato']);
		if (strlen($aux_extrato)>0){
			$sql = "UPDATE tbl_extrato
					SET admin_lgr = $login_admin
					WHERE extrato = $aux_extrato
					AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	if ($btn_acao=="bloquear_provisorio"){
		$aux_extrato = trim($_POST['extrato']);
		if (strlen($aux_extrato)>0){
			$sql = "UPDATE tbl_extrato
					SET admin_lgr = NULL
					WHERE extrato = $aux_extrato
					AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}


	$faturamento         = trim (pg_result ($resD,0,faturamento));
	$distribuidor        = trim (pg_result ($resD,0,distribuidor));
	$posto               = trim (pg_result ($resD,0,posto));
	$emissao             = trim (pg_result ($resD,0,emissao));
	$nota_fiscal         = trim (pg_result ($resD,0,nota_fiscal));
	$extrato_devolucao	 = trim (pg_result ($resD,0,extrato_devolucao));
	$cfop                = trim (pg_result ($resD,0,cfop));
	$conferencia         = trim (pg_result ($resD,0,conferencia));
	$devolucao_concluida = trim (pg_result ($resD,0,devolucao_concluida));
	$movimento           = trim (pg_result ($resD,0,movimento));
	$cancelada           = trim (pg_result ($resD,0,cancelada));


	if ($movimento=="RETORNAVEL"){
		$devolucao = "RETORN�VEL";
	}else{
		$devolucao = "N�O RETORN�VEL";
	}

	if (strlen ($posto) > 0) {
		$sql  = "SELECT * FROM tbl_posto WHERE posto = $posto";
		$resX = pg_exec ($con,$sql);

		$estado   = pg_result ($resX,0,estado);
		$razao    = pg_result ($resX,0,nome);
		$endereco = trim (pg_result ($resX,0,endereco)) . " " . trim (pg_result ($resX,0,numero));
		$cidade   = pg_result ($resX,0,cidade);
		$estado   = pg_result ($resX,0,estado);
		$cep      = pg_result ($resX,0,cep);
		$fone     = pg_result ($resX,0,fone);
		$cnpj     = pg_result ($resX,0,cnpj);
		$ie       = pg_result ($resX,0,ie);
	}

	$cabecalho  = "";

	$cabecalho  = "<style>font-size:14px;font-family:'Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif';</style>";
	$cabecalho  .= "<form name='conferencia' action='$PHP_SELF?pop_up=sim&nota_fiscal=$xNotaFiscal' method='POST'>";

	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

	$cabecalho .= "<caption style='font-size:14px;padding:3px;color:#FFF;background-color:#596D9B'>\n";
	$cabecalho .= "<b>CONFER�NCIA</b>\n";
	$cabecalho .= "</caption>\n";

	$cabecalho .= "<tr align='left'  height='16'>\n";
	$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
	$cabecalho .= "<b>&nbsp;<b>$produto_acabado<br>$devolucao </b><br>\n";
	$cabecalho .= "</td>\n";
	$cabecalho .= "</tr>\n";

	$cabecalho .= "<tr>\n";
	$cabecalho .= "<td>Natureza <br> <b>Devolu��o de Garantia</b> </td>\n";
	$cabecalho .= "<td>CFOP <br> <b>$cfop</b> </td>\n";
	$cabecalho .= "<td>Emissao <br> <b>$emissao</b> </td>\n";
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	$cabecalho .= "<tr>\n";
	$cabecalho .= "<td>Raz�o Social <br> <b>$razao</b> </td>\n";
	$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
	$cabecalho .= "<td>Inscri��o Estadual <br> <b>$ie</b> </td>\n";
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	$cabecalho .= "<tr>\n";
	$cabecalho .= "<td>Endere�o <br> <b>$endereco </b> </td>\n";
	$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
	$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
	$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	$topo ="";
	$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";

	$topo .=  "<thead>\n";
	$topo .=  "<tr align='center'>\n";
	$topo .=  "<td><b>C�digo</b></td>\n";
	$topo .=  "<td><b>Descri��o</b></td>\n";
	$topo .=  "<td><b>Qtde.</b></td>\n";
	$topo .=  "<td><b>Pre�o</b></td>\n";
	$topo .=  "<td><b>Total</b></td>\n";
	$topo .=  "<td><b>% ICMS</b></td>\n";
	$topo .=  "<td><b>% IPI</b></td>\n";
	$topo .=  "<td><b>Qtde. Insp.</b></td>\n";
	$topo .=  "</tr>\n";
	$topo .=  "</thead>\n";

	$sql = "SELECT  
			tbl_peca.peca, 
			tbl_peca.referencia, 
			tbl_peca.descricao, 
			tbl_peca.ipi, 
			CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
			tbl_peca.devolucao_obrigatoria,
			tbl_faturamento_item.aliq_icms,
			tbl_faturamento_item.aliq_ipi,
			tbl_faturamento_item.preco,
			SUM (tbl_faturamento_item.qtde) as qtde,
			SUM (tbl_faturamento_item.qtde_inspecionada) as qtde_inspecionada,
			SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco) as total,
			SUM (tbl_faturamento_item.base_icms) AS base_icms, 
			SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
			SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
			SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING (faturamento)
			JOIN tbl_peca             USING (peca)
			WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.faturamento  = $faturamento
			GROUP BY
				tbl_peca.peca, 
				tbl_peca.referencia, 
				tbl_peca.descricao,
				tbl_peca.devolucao_obrigatoria, 
				tbl_peca.produto_acabado, 
				tbl_peca.ipi,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco
			ORDER BY tbl_peca.referencia";

	$resX = pg_exec ($con,$sql);

	$notas_fiscais=array();
	$qtde_peca=0;

	//if (pg_numrows ($resX)==0) continue;

	echo $cabecalho;
	echo $topo;

	$total_base_icms  = 0;
	$total_valor_icms = 0;
	$total_base_ipi   = 0;
	$total_valor_ipi  = 0;
	$total_nota       = 0;
	$aliq_final       = 0;

	for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

		$peca                = pg_result ($resX,$x,peca);
		$peca_referencia     = pg_result ($resX,$x,referencia);
		$peca_descricao      = pg_result ($resX,$x,descricao);
		$ipi                 = pg_result ($resX,$x,ipi);
		$peca_produto_acabado= pg_result ($resX,$x,produto_acabado);
		$peca_devolucao_obrigatoria = pg_result ($resX,$x,devolucao_obrigatoria);
		$aliq_icms           = pg_result ($resX,$x,aliq_icms);
		$aliq_ipi            = pg_result ($resX,$x,aliq_ipi);
		$peca_preco          = pg_result ($resX,$x,preco);

		$base_icms           = pg_result ($resX,$x,base_icms);
		$valor_icms          = pg_result ($resX,$x,valor_icms);
		$base_ipi            = pg_result ($resX,$x,base_ipi);
		$valor_ipi           = pg_result ($resX,$x,valor_ipi);

		$total               = pg_result ($resX,$x,total);
		$qtde                = pg_result ($resX,$x,qtde);
		$qtde_inspecionada   = pg_result ($resX,$x,qtde_inspecionada);
		

		if ($qtde==0)
			$peca_preco       =  $peca_preco;
		else
			$peca_preco       =  $total / $qtde;
		
		$total_item  = $peca_preco * $qtde;

		if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

		if ($aliq_icms==0){
			$base_icms=0;
			$valor_icms=0;
		}
		else{
			$base_icms  = $total_item;
			$valor_icms = $total_item * $aliq_icms / 100;
		}

		if (strlen($aliq_ipi)==0) $aliq_ipi=0;

		if ($aliq_ipi==0) 	{
			$base_ipi=0;
			$valor_ipi=0;
		}
		else {
			$base_ipi=$total_item;
			$valor_ipi = $total_item*$aliq_ipi/100;
		}

		$total_base_icms  += $base_icms;
		$total_valor_icms += $valor_icms;
		$total_base_ipi   += $base_ipi;
		$total_valor_ipi  += $valor_ipi;
		$total_nota       += $total_item;

		echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
		echo "<td align='left'>";
		echo "$peca_referencia";
		echo "</td>\n";
		echo "<td align='left'>$peca_descricao</td>\n";

		echo "<td align='center' bgcolor='#E2E7E4'>$qtde</td>\n";
		echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
		echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
		echo "<td align='right'>$aliq_icms</td>\n";
		echo "<td align='right'>$aliq_ipi</td>\n";
		if ($qtde==$qtde_inspecionada){
			$cor_qtde = "#CDFED0";
		}else{
			$cor_qtde = "#E2E7E4";
		}
		echo "<td align='center' bgcolor='$cor_qtde'>
				<input type='text'   name='qtde_$x'      value='$qtde_inspecionada' size='2' maxlength='4'>
				<input type='hidden' name='peca_$x'      value='$peca'>
				<input type='hidden' name='preco_$x'     value='$peca_preco'>
				<input type='hidden' name='qtde_insp_$x' value='$qtde'></td>\n";

		echo "</tr>\n";
		flush();
	}
	echo "</table>\n";


	echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
	echo "<tr>";
	echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
	echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
	echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
	echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
	echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
	echo "</tr>";
	echo "</table>";

	echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	echo "<tr>\n";
	echo "<td><h4><center>N� da NF de Devolu��o: $nota_fiscal</center></h4></td>\n";
	echo "</tr>";

	if (strlen($conferencia)>0 AND strlen($cancelada)==0){
		echo "<tr>\n";
		echo "<td><center>";
		echo "<h5 style='color:#0000FF'>Nota Fiscal conferida em $conferencia</h5>";
		if ($devolucao_concluida!='t'){
			echo "<h5 style='color:#7A7C85'>Devolu��o Parcial</h5>";
		}else{
			echo "<h5 style='color:#007900'>Devolu��o Completa</h5>";
		}
		echo "</center></td>";
		echo "</tr>";
	}elseif(strlen($cancelada)>0){
		echo "<tr>\n";
		echo "<td><center><h5 style='color:#FF0000'>Nota Fiscal cancelada em $cancelada</h5></center></td>";
		echo "</tr>";
	}

	echo"	<input type='hidden' name='qtde_linhas' value='$x'>
			<input type='hidden' name='faturamento' value='$faturamento'>
			<input type='hidden' name='btn_acao'    value=''>";

	if(strlen($cancelada)==0){
		echo "<tr>\n";
		echo "<td><h4><center>
		<input type='button' name='gravar' value='Gravar Confer�ncia' onClick=\"javascript:
		if (this.form.btn_acao.value == ''){
			this.form.btn_acao.value = 'gravar_conferencia';
			this.form.submit();
		}else{
			alert('Aguarde submiss�o');
		}
		\"></center></h4></td>\n";
		echo "</tr>";
	}

	if ($devolucao_concluida!='t' AND strlen($cancelada)==0){
		$sql = "SELECT tbl_extrato.extrato, tbl_extrato.admin_lgr 
				FROM tbl_faturamento 
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
				WHERE tbl_faturamento.faturamento = $faturamento
				AND tbl_extrato.fabrica = $login_fabrica";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX)>0){
			$extrato    = pg_result ($resX,0,extrato);
			$admin_lgr  = pg_result ($resX,0,admin_lgr);
			if (strlen($admin_lgr)==0){
				echo "<tr><td>&nbsp;</td></tr>\n";
				echo "<tr style='background-color:#D9E8FF'>\n";
				echo "<td><h5 style='color:#39842F'><center>
				<input type='hidden' name='extrato' value='$extrato'>
				<input type='button' name='gravar' value='Liberar Provisoriamente' onClick=\"javascript:
				if (this.form.btn_acao.value == ''){
					this.form.btn_acao.value = 'liberar_provisorio';
					this.form.submit();
				}else{
					alert('Aguarde submiss�o');
				}
				\"></center></h5><b>* Liberando provisoriamente, este Posto Autorizado poder� visualizar o pr�ximo extrato mesmo sem a confirma��o de recebimento das Notas de Devolu��o do m�s anterior.<br>** N�o � necess�rio liberar em todas as Notas de Devolu��o.</b></td>\n";
				echo "</tr>";
			}else{
				echo "<tr><td>&nbsp;</td></tr>\n";
				echo "<tr style='background-color:#E4FFD9'>\n";
				echo "<td><center>
				<h3>Extrato $extrato liberado provisoriamente</h3>
				<h5 style='color:#39842F'>
				<input type='hidden' name='extrato' value='$extrato'>
				<input type='button' name='gravar' value='Bloquear Visualiza��o do Extrato' onClick=\"javascript:
				if (this.form.btn_acao.value == ''){
					this.form.btn_acao.value = 'bloquear_provisorio';
					this.form.submit();
				}else{
					alert('Aguarde submiss�o');
				}
				\"></center></h45><b>* Bloqueando, o Posto Autorizado n�o poder� visualizar o pr�ximo extrato sem a confirma��o do recebimento das Notas de Devolu��o referente a este extrato.</b></td>\n";
				echo "</tr>";
			}
		}
	}

	echo "</table>";
	echo "</form>";

	exit;
}
########################################################################################################





include "cabecalho.php";



?>

<script src="js/jquery-1.1.2.pack.js"        type="text/javascript"></script>
<script src="js/jquery.bgiframe.js"          type="text/javascript"></script>
<script src="js/jquery.dimensions.tootip.js" type="text/javascript"></script>
<script src="js/chili-1.7.pack.js"           type="text/javascript"></script>
<script src="js/jquery.tooltip.pack.js"           type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />

<script type="text/javascript">
	$(function() {
		$("a[@rel='ajuda'],span[@rel='ajuda']").Tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.85,
			showBody: " - ",
			extraClass: "ajuda"
		});

		
	});
</script>


<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
	background-color: #D9E2EF
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #FFFFFF
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
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:12px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:12px;
}
</style>
<script language='javascript' src='../ajax.js'></script>
<? include "javascript_pesquisas.php"; ?>
<script language="JavaScript">

/* ============= Fun��o PESQUISA DE POSTOS ====================
Nome da Fun��o : fnc_pesquisa_posto (cnpj,nome)
		Abre janela com resultado da pesquisa de Postos pela
		C�digo ou CNPJ (cnpj) ou Raz�o Social (nome).
=================================================================*/


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
	var largura  = 500;
	var tamanho  = 300;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}

function verNota(nota_fiscal){
	var largura  = 700;
	var tamanho  = 400;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "<?=$PHP_SELF?>?pop_up=sim&nota_fiscal=" + nota_fiscal;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=yes, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}

</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 02-10-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#datai').datePicker({startDate:'01/01/2000'});
		$('#dataf').datePicker({startDate:'01/01/2000'});
		$('#datai').maskedinput("99/99/9999");
		$('#dataf').maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo C�digo */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
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
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>
<?

if (strlen($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>\n";
	echo "<tr>";
	echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>\n";
}

if(strlen($msg)>0){
	echo "<center><b style='font-size:12px;border:1px solid #999;padding:10px;background-color:#dfdfdf'>$msg</b></center><br>";
}

echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
?>

<input type='hidden' name='btnacao'>
<table class='Tabela' width='500' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>
	
	<tr >
		<td class="Titulo" height='20'>Controle de Devolu��o de Pe�as</td>
	</tr>
	<tr>
		<td bgcolor='#F3F8FE'>
			<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='2'>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'><br>C�digo Posto&nbsp;</td>
						<td><br><input type="text" name="posto_codigo" id="posto_codigo" size="16" value="<? echo $posto_codigo ?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome, 'codigo')"></td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Nome do Posto&nbsp;</td>
						<td>
							<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome, 'nome')">
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Nota de Devolu��o &nbsp;</td>
						<td><input type="text" name="nota_devolucao" size="10"  value="<?echo $nota_devolucao?>" class="frm">
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Data do Envio &nbsp;</td>
						<td><input type="text" name="data_inicial" id="datai" size="12"  value="<?echo $data_inicial?>" class="frm"> &nbsp;&nbsp;&nbsp;&nbsp;at�
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>&nbsp;</td>
						<td><input type="text" name="data_final" id="dataf" size="12"  value="<?echo $data_final?>" class="frm">
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF">
						<td align='right'>
							<input type='checkbox' name='filtroObrigatorio' value='checked' <? echo $filtroObrigatorio; ?> />
						<td align='left'>
							Notas de Retorno Obrigat�rio
						</TD>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF">
						<td align='right'>
							<input type='checkbox' name='filtroNaoObrigatorio' value='checked' <? echo $filtroNaoObrigatorio; ?> />
						<td align='left'>
							Notas de Retorno N�o Obrigat�rio
						</TD>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF">
						<td align='right'>
							<input type='checkbox' name='filtroTodos' value='checked' <? echo $filtroTodos; ?> />
						<td align='left'>
							Notas Com Recebimento Pendente
						</TD>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF">
						<td align='right'>
							<input type='checkbox' name='filtroRecebidos' value='checked' <? echo $filtroRecebidos; ?> />
						<td align='left'>
							Notas Recebidas
						</TD>
					</tr>
					<tr><td colspan='2' bgcolor="#D9E2EF" align='center'><img src="imagens_admin/btn_filtrar.gif" onclick="javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() " ALT="Filtrar" border='0' style="cursor:pointer;"><br></td></tr>

					<?
					if ($login_fabrica==2){
						echo "<tr><td colspan='2' bgcolor='#D9E2EF' align='center'><a href='$PHP_SELF?posto_devendo=todos'>Clique aqui para ver todos posto com envio pendentes</a></td></tr>";
					}
					?>



			</TABLE>
		</td>
	</tr>
	<tr><td bgcolor='#D9E2EF'>
		</td>
	</tr>

</table>
</form>


<?

	if ($btnacao=='filtrar' && (strlen($filtroRecebidos)>0 || strlen($filtroTodos)>0 || strlen($posto_codigo)>0  || strlen($nota_devolucao)>0)){

		if (strlen($posto_codigo)>0){
			$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
		}
		if (strlen($filtroTodos)>0 || strlen($filtroRecebidos)>0){
			if (strlen($filtroTodos)>0){
				$sql_adicional_2 = " AND tbl_faturamento.conferencia IS NULL ";
			}
			if (strlen($filtroRecebidos)>0){
				$sql_adicional_2 = " AND tbl_faturamento.conferencia IS NOT NULL ";
			}
			if (strlen($filtroTodos)>0 && strlen($filtroRecebidos)>0){
				$sql_adicional_2 = "";
			}
		}

		if (strlen($filtroObrigatorio)>0 AND strlen($filtroNaoObrigatorio)==0){
			$sql_adicional_5  = " AND tbl_faturamento.movimento = 'RETORNAVEL' ";
		}

		if (strlen($filtroObrigatorio)==0 AND strlen($filtroNaoObrigatorio)>0){
			$sql_adicional_5  = " AND tbl_faturamento.movimento = 'NAO_RETOR.' ";
		}

		if (strlen($data_inicial)>0 AND strlen($data_final)>0){
			$tmp_data_inicial = converte_data($data_inicial);
			$tmp_data_final   = converte_data($data_final);
			$sql_adicional_3  = " AND tbl_faturamento.emissao BETWEEN '$tmp_data_inicial' AND '$tmp_data_final' ";
		}

		if ((strlen($data_inicial)==0 AND strlen($data_final)>0) OR (strlen($data_inicial)>0 AND strlen($data_final)==0)){
			$msg_erro .= "Preencha as duas datas";
		}

		if (strlen($nota_devolucao)>0){
			$sql_adicional_4 = " AND tbl_faturamento.nota_fiscal like '%$nota_devolucao' ";
		}
		
		if ($login_fabrica==11){
			$posto_da_fabrica = "20321";
		}

		 $sql = "SELECT tbl_faturamento.faturamento,
						to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
						to_char(tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia,
						to_char(tbl_faturamento.cancelada,'DD/MM/YYYY') AS cancelada,
						tbl_faturamento.devolucao_concluida,
						tbl_faturamento.nota_fiscal,
						tbl_faturamento.extrato_devolucao,
						tbl_faturamento.total_nota            AS total_nota,
						tbl_faturamento.valor_ipi             AS valor_ipi,
						tbl_posto.nome                        AS nome_posto,
						tbl_posto.posto                       AS posto,
						tbl_posto_fabrica.codigo_posto        AS codigo_posto,
						tbl_extrato.admin_lgr
				FROM tbl_faturamento
				JOIN tbl_posto ON tbl_faturamento.distribuidor = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
				WHERE tbl_faturamento.distribuidor IS NOT NULL
				AND tbl_faturamento.posto = $posto_da_fabrica
				AND tbl_faturamento.fabrica  = $login_fabrica
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				$sql_adicional
				$sql_adicional_2
				$sql_adicional_3
				$sql_adicional_4
				$sql_adicional_5
				ORDER BY faturamento DESC
			";
		#echo nl2br( $sql);
		#exit;
		$res_notas = pg_exec ($con,$sql);
		$qtde_notas=pg_numrows($res_notas);
	
		$lista  = "";

		$lista .= "<form method='post' name='frm_notas' action='$PHP_SELF'>";

		$lista .=  "<center><table border='0' cellpadding='4' cellspacing='0'  width='600px'>";

		$lista .=  "<tr ><td class='Titulo' colspan='8'>Notas Fiscais de Devolu��o</td></tr>";

		$lista .=  "<tr class='menu_top' height='20'>";

		$lista .=  "<td></td>";
//		$lista .=  "<td></td>";
		$lista .=  "<td>POSTO</td>";
		$lista .=  "<td>EXTRATO</td>";
		$lista .=  "<td>NOTA<br>FISCAL</td>";
		$lista .=  "<td>DATA<br>EMISS�O</td>";
		$lista .=  "<td>DATA<br>CONFERENCIA</td>";
		$lista .=  "<td>VALOR<br>NOTA</td>";
		$lista .=  "<td>CANCELADA</td>";
		$lista .=  "</tr>";

		for ($i=0;$i<$qtde_notas;$i++){
			$faturamento			= pg_result($res_notas,$i,faturamento);
			$emissao				= pg_result($res_notas,$i,emissao);
			$conferencia			= pg_result($res_notas,$i,conferencia);
			$cancelada				= pg_result($res_notas,$i,cancelada);
			$devolucao_concluida	= pg_result($res_notas,$i,devolucao_concluida);
			$nota_fiscal			= pg_result($res_notas,$i,nota_fiscal);
			$nf_total_nota			= pg_result($res_notas,$i,total_nota);
			$nf_valor_ipi			= pg_result($res_notas,$i,valor_ipi);
			$nf_total_nota			= $nf_total_nota + $nf_valor_ipi;
			$nf_total_nota			= number_format($nf_total_nota,2,",",".");
			$extrato_devolucao		= pg_result($res_notas,$i,extrato_devolucao);
			$nome_posto_1			= pg_result($res_notas,$i,nome_posto);
			$cod_posto				= pg_result($res_notas,$i,posto);
			$codigo_posto_1			= pg_result($res_notas,$i,codigo_posto);
			$admin_lgr				= pg_result($res_notas,$i,admin_lgr);
			

			$cor = ($i%2==0) ? '#E9E9E9' : '#ffffff';

			if (strlen($admin_lgr)>0){
				$admin_lgr = "*";
			}

			$lista .= "<tr class='Conteudo' height='20' bgcolor='$cor' align='left'  >";
			//$lista .= "<td align='center'><input type='checkbox' name='faturamento_$i' value='$faturamento'></td>";

			if (strlen($conferencia)>0 AND $devolucao_concluida=='t' AND strlen($cancelada)==0){
				$lista .= "<td nowrap  align='center'><img src='imagens/status_verde.gif' alt='NF conferida em $conferencia'></td>";
			}elseif (strlen($cancelada)>0){
				$lista .= "<td nowrap  align='center'><img src='imagens/status_vermelho.gif' alt='NF cancelada em $cancelada'></td>";
			}elseif (strlen($conferencia)>0){
					$lista .= "<td nowrap  align='center'><img src='imagens_admin/status_amarelo.gif' alt='Confer�ncia parcial da NF'></td>";
			}else{
					$lista .= "<td nowrap  align='center'><img src='imagens/status_cinza.gif' alt='Aguardando confer�ncia'></td>";
			}

			//$lista .= "<td nowrap  align='left' title='$codigo_posto_1 - $nome_posto_1'><a href='$PHP_SELF?btnacao=filtrar&posto_codigo=$codigo_posto_1&posto_nome=$nome_posto_1&filtroTodos=$filtroTodos&filtroRecebidos=$filtroRecebidos'>$codigo_posto_1 - $nome_posto_1</a></td>";
			$lista .= "<td nowrap  align='left' title='$codigo_posto_1 - $nome_posto_1'>$codigo_posto_1 - $nome_posto_1</td>";
			
			if (strlen($admin_lgr)>0){
				$lista .= "<td nowrap align='center'><span rel='ajuda' title='Extrato liberado provisoriamente. O PA poder� visualizar o pr�ximo extrato sem a confirma��o de recebimento das notas de devolu��o desse m�s.'>$extrato_devolucao*</span></td>";
			}else{
				$lista .= "<td nowrap  align='center' title='Esta nota � referente ao extrato $extrato_devolucao'>$extrato_devolucao </td>";
			}
			
			$lista .= "<td nowrap align='center'><a href='javascript:verNota($faturamento)'>$nota_fiscal</a></td>";
			$lista .= "<td nowrap  align='center' title='Nota emitida em $emissao'>$emissao</td>";
			$lista .= "<td nowrap  align='center' title='Conferida em $conferencia'>$conferencia</td>";
			$lista .= "<td nowrap  align='right'  title='Valor da Nota: R$ $nf_total_nota'>$nf_total_nota</td>";
			$lista .= "<td nowrap  align='center' title='Se estiver data, esta nota foi cancelada'>$cancelada</td>";
			$lista .= "</tr>";
		}

		$lista .=  "<tr align='left' >";
		$lista .=  "<td bgColor='#6B7EAB' colspan='8' align='left'><font style='font-size:12px;color:#FFFFFF'><img  rel='ajuda' src='imagens/help1.gif' width='24' height='16' align='absmiddle' border='0' > <span rel='ajuda' title='Se o extrato estiver como liberado provisoriamente, o PA poder� visualizar o pr�ximo extrato mesmo sem a F�brica ter confirmado o recebimento da Nota Fiscal de Devolu��o'>(*) Extrato liberado provisoriamente</acronym></span> </font>";

		
		/*
		$lista .=  "<select name='acao' style='font-weight:bold;font-size:12px'>
						<option value='confirmar' selected>CONFIRMAR RECEBIMENTO</option>
						<option value='nao_recebida'>N�O RECEBIDA (CANCELAR)</option>
					</select>
					<input type='hidden' name='qtde_notas' value='$i'>
					<input type='hidden' name='btn_acao' value=''>
					<input type='button' name='btn' value='Gravar' onClick=\"this.form.btn_acao.value='gravar';this.form.submit()\">
					";
		*/
		$lista .=  "</td></tr>";

		$lista .=  "</table>";
		$lista .= "</form>";
	
		if ($qtde_notas==0){
			echo "<center><h2 style='font-size:12px;background-color:#D9E2EF;color:black;width:550px'>Nenhum registro encontrado</h2></center>";
		}else {
			//echo "<hr><br><b style='font-size:14px'>Postos Que Enviaram Pe�as Sem Confirma��o de Recebimento da F�brica</b><br>";
			echo "<br><br>";
			echo $lista;
			echo "<br>";
		}
	}

######### FIM ##################################################

echo "<br>";

?>

<br>

<? include "rodape.php"; ?>