<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "anexaNFDevolucao_inc.php";

if ($login_posto==1537){ // provisorio
//	header("Location: extrato_posto.php");
//	exit();
}

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0){
	$extrato = trim($_POST['extrato']);
}

if (empty($extrato)) {
	header('Location: menu_inicial.php');
	exit;
}

if($extrato > 0 and $_POST['sem_lgr'] == 'nf_recebida') {
	$sql_nf = "update tbl_extrato set nf_recebida = true where fabrica = $login_fabrica and posto = $login_posto and extrato = $extrato";
	$res_nf = pg_query($con,$sql_nf);
}

$sql_data_geracao_extrato = "SELECT data_geracao::date FROM tbl_extrato WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
$res_data_geracao_extrato = pg_query($con, $sql_data_geracao_extrato);

$data_geracao_extrato = pg_fetch_result($res_data_geracao_extrato, 0, "data_geracao");

$sql = "SELECT fabrica FROM tbl_extrato where fabrica = $login_fabrica AND extrato = $extrato";
$res = @pg_exec($con, $sql);
if (@pg_num_rows($res) == 0) {
	header('Location: menu_inicial.php');
	exit;
}

$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$ok_aceito="nao";
$tem_mais_itens='nao';
$contadorrr=0;

###### HABILITAR ESTE IF APÓS A EFETIVAÇÃO #######
if ($extrato<144000){
//	header("Location: extrato_posto.php");
//	exit();
}

/*
POSTOS QUE PODEM ACESSAR ESTA TELA

Martello      :  2073 -   595 - OK - falta exprotar
Penha         : 80039 -  1537 -      notas zuadas
Janaína       : 80330 -  1773 - OK - falta exportar
Bertolucci    : 80568 -  7080 - OK - falta exportar - ok
Tecservi      : 80459 -  5037 - OK - falta exportar - ok
NL            : 80636 - 13951 - OK - falta exportar (jah estava digitada)- ok
Telecontrol   : 93509 -  4311
A.Carneiro    :  1256 -   564 - OK - falta exportar (jah estava digitada) -
Centerservice : 80150 -  1623 - OK - falta exportar (jah estava digitada)- ok
Visiontec     : 80200 -  1664 - NAO DIGITOU AINDA- ok

###### NOVOS POSTOS
Nipon -           80437  - 2506
MR -              80539  - 6458
Bom Jesus -       80002  - 1511
Eletro Center -  601049  - 1870
Multitécnica -    38086  - 1266
Central B & B -   80540  - 6591
Edivideo -        80462  - 5496
Maria Suzana -    80685  - 14296
Moacir Florêncio  80492  - 6140
Luiz Claudio -    32051  - 1161
JC & M -          80424  - 1962

*/
//header("Location: extrato_posto.php");
//exit();

#$postos_permitidos = array(0 => 'LIXO', 1 => '1537', 2 => '1773', 3 => '7080', 4 => '5037', 5 => '13951', 6 => '4311', 7 => '564', 8 => '1623', 9 => '1664',10 => '595',11 => '2506', 12 => '6458', 13 => '1511', 14 => '1870', 15 => '1266', 16 => '6591', 17 => '5496', 18 => '14296', 19 => '6140', 20 => '1161', 21 => '1962');

$postos_permitidos = array(
	0 => 'LIXO',
	'1537',  '1773',  '7080',  '5037',  '13951', '4311',  '564',   '1623',  '1664', '595',
	'2506',  '6458',  '1511',  '1870',  '1266',  '6591',  '5496',  '14296', '6140', '1161',
	'708',   '710',   '14119', '898',   '6379',  '5024',  '388',   '2508',  '1172', '1261',
	'19724', '1523',  '1567',  '1581',  '1713',  '1740',  '1752',  '1754',  '1766', '115',
	'1799',  '1806',  '1814',  '1891',  '6432',  '6916',  '6917',  '7245',  '7256', '13850',
	'4044',  '14182', '14297', '14282', '14260', '18941', '18967', '1962',  '5419'
);

#Postos que devem aparecer somente os produtos - HD 13651 - Apartir de 01/03/2008
#80039 - Penha TV Color
#93509 - Telecontrol
#80305 - Ntek
#80539 -  MR
#80615 - EletroNews
#80459 - Tecservi
#38273 -   N.Goes
#80831 - Vakassugui
#10493 - Centrolar
#25356 - Kalkmann

#Para estes postos devem ser mostrados somente os produto - HD 13651

/*RETIRADO CONFORME SOLICITADO PELO TULIO (VISITA A BRITANIA) 01/06/2009 -  CONVERSA NO CHAT COM IGOR*/
//$postos_permitidos_novo_processo = array(0 => 'LIXO',1 => '6976', 2 => '20397', 3 => '4044', 4 => '1267', 5 => '6458', 6 => '710', 7 => '5037', 8 => '1752', 9 => '4311', 10 => '1537',11 => '6359');
$postos_permitidos_novo_processo = array(0 => 'LIXO');

if ($login_e_distribuidor == 't' AND $extrato < 185731) {
	header ("Location: new_extrato_distribuidor_retornaveis.php?extrato=$extrato");
	exit;
}

if ($extrato < 185731){# liberado para toda a rede Solicitado por Sergio Mauricio 31/08/2007 - Fabio
	if (array_search($login_posto, $postos_permitidos)==0){ //verifica se o posto tem permissao
		header("Location: extrato_posto.php");
		exit();
	}
}

if (strlen($extrato)==0){
	header("Location: extrato_posto.php");
}

$pecas_pendentes = trim($_GET['pendentes']);
if (strlen($pecas_pendentes)==0)
	$pecas_pendentes = trim($_POST['pendentes']);


$query = "SELECT count(*) FROM tbl_extrato_lgr WHERE extrato=$extrato AND posto=$login_posto AND qtde-qtde_nf>0";
$res = pg_query ($con,$query);
if ( pg_fetch_result ($res,0,0)>0){
	$tem_mais_itens='sim';
}

if($login_fabrica == 3){
	$join_agrupado = " LEFT JOIN tbl_extrato_agrupado ON tbl_faturamento.extrato_devolucao = tbl_extrato_agrupado.extrato  ";
}

// verificaÃ§Ã£o se o posto quer ver a Mao de obra mas ele ainda nï¿½o preencheu as notas
$mao = trim($_GET['mao']);
if (strlen($mao)>0 AND $mao=='sim'){

		$sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
		FROM    tbl_faturamento
		JOIN    tbl_faturamento_item USING (faturamento)
		JOIN    tbl_peca             USING (peca)
		WHERE   tbl_faturamento.extrato_devolucao < $extrato
		AND     tbl_faturamento.fabrica = $login_fabrica
		AND     tbl_faturamento.posto             = $login_posto
		AND     tbl_faturamento.distribuidor IS NULL
		AND     (tbl_faturamento_item.devolucao_obrig IS TRUE or tbl_peca.produto_acabado       IS TRUE)
		AND    (tbl_faturamento.cfop IN (
						'594906',
						'594916',
						'594919',
						'594920',
						'594923',
						'594972',
						'594973',
						'594979',
						'594980',
						'594981',
						'594982',
						'694902',
						'694913',
						'694919',
						'694921',
						'694922',
						'694923',
						'694925',
						'694926',
						'694928',
						'694972',
						'694977',
						'694978',
						'694979',
						'694980',
						'694981',
						'694982'
					)
				)
		ORDER BY  tbl_faturamento.extrato_devolucao DESC ";
		$ress = pg_query ($con,$sqls);
		$res_qtdes = pg_num_rows ($ress);
		$resultados = pg_fetch_all($ress);
		if ($res_qtdes == 0){
			$msg_erro = "";
		}else{
			$extratos = array();
			foreach($resultados as $chave => $valor) {
				$sqlD="SELECT extrato_devolucao
					FROM   tbl_faturamento
					WHERE  distribuidor = $login_posto
					AND    extrato_devolucao in( $valor[extrato_devolucao]);";
				$resD = pg_query($con,$sqlD);
				if(pg_num_rows($resD) == 0){
					$sqld = " SELECT to_char(data_geracao,'DD/MM/YYYY') as data_extrato, tbl_extrato_agrupado.aprovado
							FROM tbl_extrato
							LEFT JOIN tbl_extrato_agrupado USING (extrato)
							WHERE tbl_extrato.extrato IN ($valor[extrato_devolucao])
							AND   tbl_extrato.fabrica = $login_fabrica
							AND   tbl_extrato.posto   = $login_posto
							AND   tbl_extrato.data_geracao > '2010-01-01 00:00:00'
							ORDER BY tbl_extrato.extrato DESC limit 1;";
					$resd = pg_query($con,$sqld);
					if(pg_num_rows($resd) > 0){
						$data_extrato = pg_fetch_result($resd,0,'data_extrato');
						$extr_aprovado = pg_fetch_result($resd, 0, 'aprovado');

						if (empty($extr_aprovado)) {
							$msg_erro="Devem ser preenchidas as Notas Fiscais de devolução de Produtos e peças dos extratos anteriores para liberar a tela de consulta de valores de mão-de-obra - extrato $data_extrato";
						}
					}
				}else{
					$msg_erro = "";
				}
				if(!empty($msg_erro)) {
					break;
				}
			}
		}

		if(empty($msg_erro)) {

			$sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
			FROM    tbl_faturamento
			JOIN    tbl_faturamento_item USING (faturamento)
			JOIN    tbl_peca             USING (peca)
			$join_agrupado
			WHERE   tbl_faturamento.extrato_devolucao = $extrato
			AND     tbl_faturamento.fabrica = $login_fabrica
			AND     tbl_faturamento.posto             = $login_posto
			AND     tbl_faturamento.distribuidor IS NULL
			AND     (tbl_faturamento_item.devolucao_obrig IS TRUE or tbl_peca.produto_acabado       IS TRUE)
			AND    (tbl_faturamento.cfop IN (
							'594906',
							'594916',
							'594919',
							'594920',
							'594923',
							'594972',
							'594973',
							'594979',
							'594980',
							'594981',
							'594982',
							'694902',
							'694913',
							'694919',
							'694921',
							'694922',
							'694923',
							'694925',
							'694926',
							'694928',
							'694972',
							'694977',
							'694978',
							'694979',
							'694980',
							'694981',
							'694982'
						)
					)
			$and_agrupado
			ORDER BY  tbl_faturamento.extrato_devolucao DESC
			";
			$ress = pg_query($con,$sqls);
			if(pg_num_rows($ress) > 0){
				$sql = "SELECT  faturamento,
						extrato_devolucao,
						nota_fiscal,
						distribuidor,
						NULL as produto_acabado,
						NULL as devolucao_obrigatoria
					FROM tbl_faturamento
					WHERE posto IN (13996,4311)
					AND distribuidor=$login_posto
					AND fabrica=$login_fabrica
					AND extrato_devolucao=$extrato
					ORDER BY faturamento ASC";
				$res = pg_exec ($con,$sql);
				$jah_digitado=pg_numrows ($res);
				if ($jah_digitado>0){
					header("Location: new_extrato_posto_mao_obra.php?extrato=$extrato");
					exit();
				}else{
					$msg_erro="Devem ser preenchidas as Notas Fiscais de devolução de Produtos e peças para liberar a tela de consulta de valores de mão-de-obra - extrato";
				}
			}
		}


	if(strlen($msg_erro) == 0) {
		$sql = "SELECT  faturamento,
				extrato_devolucao,
				nota_fiscal,
				distribuidor,
				NULL as produto_acabado,
				NULL as devolucao_obrigatoria
			FROM tbl_faturamento
			WHERE posto IN (13996,4311)
			AND distribuidor=$login_posto
			AND fabrica=$login_fabrica
			AND extrato_devolucao=$extrato
			ORDER BY faturamento ASC";
		//echo $sql;
		$res = pg_query ($con,$sql);
		$jah_digitado=pg_num_rows ($res);
		if ($jah_digitado>0){
			header("Location: new_extrato_posto_mao_obra.php?extrato=$extrato");
			exit();
		}else{

			$sql = "SELECT  faturamento,
				tbl_faturamento.extrato_devolucao,
				nota_fiscal,
				distribuidor,
				NULL as produto_acabado,
				NULL as devolucao_obrigatoria
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING(faturamento)
			JOIN tbl_peca USING(peca)
			$join_agrupado
			WHERE posto =$login_posto
			AND tbl_faturamento.fabrica=$login_fabrica
			AND tbl_faturamento.extrato_devolucao=$extrato
			AND     tbl_faturamento.distribuidor IS NULL
			AND     (tbl_faturamento_item.devolucao_obrig IS TRUE or tbl_peca.produto_acabado       IS TRUE)
			AND    (tbl_faturamento.cfop IN (
							'594906',
							'594916',
							'594919',
							'594920',
							'594923',
							'594972',
							'594973',
							'594979',
							'594980',
							'594981',
							'594982',
							'694902',
							'694913',
							'694919',
							'694921',
							'694922',
							'694923',
							'694925',
							'694926',
							'694928',
							'694972',
							'694977',
							'694978',
							'694979',
							'694980',
							'694981',
							'694982'
						)
					)
			$and_agrupado
			ORDER BY  tbl_faturamento.extrato_devolucao DESC LIMIT 1;";
            //echo nl2br($sql);
			$res = pg_query ($con,$sql);
			if(pg_num_rows($res) == 0){
				header("Location: new_extrato_posto_mao_obra.php?extrato=$extrato");
				exit;
			}
		}
	}
}

$ok_aceito = trim($_POST['ok_aceito']);
if ($ok_aceito=='Concordo')
	$numero_linhas = trim($_POST['qtde_linha']);

$btn_acao = trim($_POST['botao_acao']);

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_qtde") {

	$sql_update = "UPDATE tbl_extrato_lgr
			SET qtde_pedente_temp = null
			WHERE extrato=$extrato";
	$res_update = pg_query ($con,$sql_update);
	$msg_erro .= pg_errormessage($con);

	$numero_linhas   = trim($_POST['qtde_linha']);
	$qtde_pecas      = trim($_POST['qtde_pecas']);
	$pecas_pendentes = trim($_POST['pendentes']);

	$resX = pg_query ($con,"BEGIN TRANSACTION");

	for($i=1;$i<=$qtde_pecas;$i++){

		$extrato_lgr = trim($_POST["item_$i"]);
		$peca_tem = trim($_POST["peca_tem_$i"]);
		$peca = trim($_POST["peca_$i"]);
		$qtde_pecas_devolvidas = trim($_POST["$extrato_lgr"]);

		if ($peca_tem>$qtde_pecas_devolvidas){
			$diminuiu='sim';
		}

		if (strlen($qtde_pecas_devolvidas)>0){
				$sql_update = "UPDATE tbl_extrato_lgr
						SET qtde_pedente_temp = $qtde_pecas_devolvidas
						WHERE extrato=$extrato
						AND peca=$peca";
				$res_update = pg_query ($con,$sql_update);
				$msg_erro .= pg_errormessage($con);
		}
		else{
			//$msg_erro="Informe a quantidade de peças que serão devolvidas!";
		}
		if (strlen($msg_erro)>0) break;
	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_query ($con,"COMMIT TRANSACTION");
	}else{
		$resX = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_as_notas") {

	$qtde_pecas = trim($_POST['qtde_pecas']);
	$numero_linhas = trim($_POST['qtde_linha']);
	$numero_de_notas = trim($_POST['numero_de_notas']);
	$numero_de_notas_tc = trim($_POST['numero_de_notas_tc']); # para a telecontrol
	$data_preenchimento = date("Y-m-d");
	$array_notas = array();
	$array_notas_tc = array();

	$resX = pg_query ($con,"BEGIN TRANSACTION");
	$notas_fiscais_anteriores = array();
	for($i=0;$i<$numero_de_notas;$i++){
		$nota_fiscal = trim($_POST["nota_fiscal_$i"]);
		$serie       = trim($_POST["serie_$i"]);


		if($login_posto == 4311) {
			/*IGOR- Copiei do embarque_nota_fiscal.php - Para não gerar nota errada*/
			# Fabio Nowaki - 24/01/2008
			$sql = "SELECT MAX (nota_fiscal::integer) AS nota_fiscal FROM tbl_faturamento WHERE distribuidor = $login_posto AND nota_fiscal::integer < 111111 ";
			$res = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$nota_fiscal = pg_fetch_result ($res,0,0);

			if (strlen ($nota_fiscal) == 0) {
				$nota_fiscal = "000000";
			}

			$nota_fiscal = $nota_fiscal + 1 ;
			$nota_fiscal = "000000" . $nota_fiscal;
			$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);
		}else{
			$nota_fiscal = str_replace(".","",$nota_fiscal);
			$nota_fiscal = str_replace(",","",$nota_fiscal);
			$nota_fiscal = str_replace("-","",$nota_fiscal);
			$nota_fiscal = str_replace("/","",$nota_fiscal);

			$nota_fiscal = ltrim ($nota_fiscal, "0");
			#echo "Numero de notas: $nota_fiscal <br> $msg_erro";
			if (strlen($nota_fiscal)==0){
				$msg_erro='Digite todas as notas fiscais!';
				break;
			}



			if (!is_numeric($nota_fiscal)) {
				$msg_erro .= "O número das notas fiscais devem ter somente números!";
			}
		}

		if ($login_fabrica == 3){

			if (in_array($nota_fiscal, $notas_fiscais_anteriores)){
				$msg_erro = "O número de nota digitada: '$nota_fiscal', já foi digitata para este extrato e não pode ser repetida ";
			}else{
				$notas_fiscais_anteriores[] = $nota_fiscal;
			}

			$nota_fiscal = str_pad($nota_fiscal, 6,'0',STR_PAD_LEFT);

		}
		if ($nota_fiscal==0) {
			$msg_erro .= "O número das notas fiscais devem ter somente números!";
		}

		array_push($array_notas,$nota_fiscal);

		$total_nota = trim($_POST["id_nota_$i-total_nota"]);
		$base_icms  = trim($_POST["id_nota_$i-base_icms"]);
		$valor_icms = trim($_POST["id_nota_$i-valor_icms"]);
		$base_ipi   = trim($_POST["id_nota_$i-base_ipi"]);
		$valor_ipi  = trim($_POST["id_nota_$i-valor_ipi"]);
		$cfop       = trim($_POST["id_nota_$i-cfop"]);
		$movimento  = trim($_POST["id_nota_$i-movimento"]);

		$qtde_peca_na_nota = trim($_POST["id_nota_$i-qtde_itens"]);

		$cfop = (strlen($cfop)>0) ? " '$cfop' " : " NULL ";

		if (strlen($msg_erro)==0){
			$sql = "INSERT INTO tbl_faturamento
					(fabrica, emissao,saida, posto, distribuidor, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs,cfop, movimento)
					VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',13996,$login_posto,$total_nota,'$nota_fiscal','$serie','Devolução de Garantia', $base_icms, $valor_icms, $base_ipi, $valor_ipi, $extrato, 'Devolução de peças do posto para a Fábrica',$cfop,'$movimento')";

			$res = pg_query ($con,$sql);

			$sql = "SELECT CURRVAL ('seq_faturamento')";
			$resZ = pg_query ($con,$sql);
			$faturamento_codigo = pg_fetch_result ($resZ,0,0);

			if($login_fabrica == 3){

				$imagem = $_FILES["nota_$i"];
				if(!empty($imagem['name']) > 0){

					$msg_erro .= anexaNFDevolucao($imagem,$extrato,$faturamento_codigo,$nota_fiscal);
					//echo $msg_erro."<br>";
					//echo "$imagem,$extrato,$faturamento_codigo,$nota_fiscal";exit;

				} else {
					$msg_erro .= "Imagem da Nota Fiscal $nota_fiscal é obrigatória <br />";
				}
			}

			#echo "$faturamento_codigo - ";
			for($x=1;$x<=$qtde_peca_na_nota;$x++){

				$lgr                = trim($_POST["id_item_LGR_$x-$i"]);
				$peca               = trim($_POST["id_item_peca_$x-$i"]);
				$peca_preco         = trim($_POST["id_item_preco_$x-$i"]);
				$peca_qtde_total_nf = trim($_POST["id_item_qtde_$x-$i"]);
				$peca_aliq_icms     = trim($_POST["id_item_icms_$x-$i"]);
				$peca_aliq_ipi      = trim($_POST["id_item_ipi_$x-$i"]);
				$peca_total_item    = trim($_POST["id_item_total_$x-$i"]);

				$sql_update = "UPDATE tbl_extrato_lgr
						SET qtde_nf = (CASE WHEN qtde_nf IS NULL THEN 0 ELSE qtde_nf END) + $peca_qtde_total_nf
						WHERE extrato=$extrato
						AND peca=$peca";
				$res_update = pg_query ($con,$sql_update);
				$msg_erro .= pg_errormessage($con);

				$condAliqICMS = " AND tbl_faturamento_item.aliq_icms > 0";
				if ($login_fabrica == 3) {//hd-3690693 faturamento sem pedido
					$sqlFatSemPedido = "SELECT tbl_faturamento.faturamento
									     FROM tbl_faturamento
									    WHERE tbl_faturamento.fabrica = $login_fabrica
									      AND tbl_faturamento.posto   = $login_posto
									      AND tbl_faturamento.pedido IS NULL
									      AND tbl_faturamento.extrato_devolucao = $extrato";
					$resFatSemPedido = pg_query ($con, $sqlFatSemPedido);
					if (pg_num_rows($resFatSemPedido) > 0) {
						$condAliqICMS = "";
					}
				}

				$sql_nf = "SELECT
								tbl_faturamento_item.faturamento_item,
								tbl_faturamento.nota_fiscal,
								tbl_faturamento.pedido,
								tbl_faturamento_item.qtde,
								tbl_faturamento_item.peca,
								tbl_faturamento_item.preco,
								tbl_faturamento_item.aliq_icms,
								tbl_faturamento_item.aliq_ipi,
								tbl_faturamento_item.base_icms,
								tbl_faturamento_item.valor_icms,
								tbl_faturamento_item.linha,
								tbl_faturamento_item.base_ipi,
								tbl_faturamento_item.valor_ipi,
								tbl_faturamento_item.sequencia,
								tbl_faturamento_item.base_subs_trib,
								tbl_faturamento_item.valor_subs_trib
							FROM tbl_faturamento_item
							JOIN tbl_faturamento      USING (faturamento)
							WHERE tbl_faturamento.fabrica = $login_fabrica
							AND   tbl_faturamento.posto   = $login_posto
							AND   tbl_faturamento.extrato_devolucao = $extrato
							AND   tbl_faturamento_item.peca=$peca
							AND   tbl_faturamento_item.preco=$peca_preco
							AND   tbl_faturamento.distribuidor IS NULL
							{$condAliqICMS}
							ORDER BY tbl_faturamento.nota_fiscal";
				$resNF = pg_query ($con,$sql_nf);
				$qtde_peca_inserir=0;
				if (pg_num_rows ($resNF)==0){
					$msg_erro .= "Erro.";
					# Nelson pediu para nw mandar mais email HD 2937
					$email_origem  = "helpdesk@telecontrol.com.br";
					$email_destino = 'helpdesk@telecontrol.com.br';
					$assunto       = "Extrato com erro";
					$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\n $msg_erro \n Insert: $sql \n Update:$sql_update \n Select: $sql_nf";
					$body_top = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";
					@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem);
					break;
				}
				for ($w = 0 ; $w < pg_num_rows ($resNF) ; $w++) {

					if ($qtde_peca_inserir < $peca_qtde_total_nf){

						$faturamento_item= pg_fetch_result ($resNF,$w,faturamento_item);
						$peca_nota       = pg_fetch_result ($resNF,$w,nota_fiscal);
						$peca_qtde       = pg_fetch_result ($resNF,$w,qtde);
						$peca_peca       = pg_fetch_result ($resNF,$w,peca);
						$peca_preco      = pg_fetch_result ($resNF,$w,preco);
						$peca_aliq_icms  = pg_fetch_result ($resNF,$w,aliq_icms);
						$peca_base_icms  = pg_fetch_result ($resNF,$w,base_icms);
						$peca_valor_icms = pg_fetch_result ($resNF,$w,valor_icms);
						$peca_linha      = pg_fetch_result ($resNF,$w,linha);
						$peca_aliq_ipi   = pg_fetch_result ($resNF,$w,aliq_ipi);
						$peca_base_ipi   = pg_fetch_result ($resNF,$w,base_ipi);
						$peca_valor_ipi  = pg_fetch_result ($resNF,$w,valor_ipi);
						$sequencia       = pg_fetch_result ($resNF,$w,sequencia);
						$pedido          = pg_fetch_result ($resNF,$w,pedido);
						$base_subs_trib  = pg_fetch_result ($resNF,$w,'base_subs_trib');
						$valor_subs_trib = pg_fetch_result ($resNF,$w,'valor_subs_trib');

						if ($login_fabrica == 3 && empty($pedido)) {//hd-3690693 faturamento sem pedido
							$peca_aliq_icms     = (empty($peca_aliq_icms))  ? 0 : $peca_aliq_icms;
							$peca_base_icms     = (empty($peca_base_icms))  ? 0 : $peca_base_icms;
							$peca_valor_icms    = (empty($peca_valor_icms)) ? 0 : $peca_valor_icms;
							$peca_aliq_ipi      = (empty($peca_aliq_ipi))   ? 0 : $peca_aliq_ipi;
							$peca_base_ipi      = (empty($peca_base_ipi))   ? 0 : $peca_base_ipi;
							$peca_valor_ipi     = (empty($peca_valor_ipi))  ? 0 : $peca_valor_ipi;
							$base_subs_trib     = (empty($base_subs_trib))  ? 0 : $base_subs_trib;
							$valor_subs_trib    = (empty($valor_subs_trib)) ? 0 : $valor_subs_trib;
						}

						if(empty($base_subs_trib)) {
							$base_subs_trib = 0;
						}

						if(empty($valor_subs_trib)) {
							$valor_subs_trib = 0;
						}

						$qtde_peca_inserir += $peca_qtde;

						if ($qtde_peca_inserir > $peca_qtde_total_nf){
							$peca_base_icms  = 0;
							$peca_valor_icms = 0;
							$peca_base_ipi   = 0;
							$peca_valor_ipi  = 0;
//							$peca_qtde       = $peca_qtde-$qtde_peca_inserir;
							$peca_qtde       = $peca_qtde - ($qtde_peca_inserir-$peca_qtde_total_nf);

							if ($peca_aliq_icms>0){
								$peca_base_icms = $peca_qtde_total_nf*$peca_preco;
								$peca_valor_icms= $peca_qtde_total_nf*$peca_preco*$peca_aliq_icms/100;
							}
							if ($peca_aliq_ipi>0){
								$peca_base_ipi = $peca_qtde_total_nf*$peca_preco;
								$peca_valor_ipi= $peca_qtde_total_nf*$peca_preco*$peca_aliq_ipi/100;
							}
						}

						$sql = "INSERT INTO tbl_faturamento_item
								(faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, linha, base_ipi, valor_ipi,nota_fiscal_origem,sequencia,base_subs_trib,valor_subs_trib)
								VALUES ($faturamento_codigo, $peca,$peca_qtde, $peca_preco, $peca_aliq_icms, $peca_aliq_ipi, $peca_base_icms, $peca_valor_icms, $peca_linha, $peca_base_ipi, $peca_valor_ipi,'$peca_nota','$sequencia',$base_subs_trib,$valor_subs_trib)";
						$res = pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}else{
						break; //echo "<br>Break<br>";
					}
				}
			}
		}
	}

#######################################################
############## NOTAS DA TELECONTROL ################### VERIFICAR POIS PODE DAR CONFLITOS
#######################################################
if (1==1) {
	for($i=0;$i<$numero_de_notas_tc;$i++){
		$nota_fiscal_tc = trim($_POST["nota_fiscal_tc_$i"]);

		if (strlen($nota_fiscal_tc)==0){
			$msg_erro .= 'Digite todas as notas fiscais!';
			break;
		}

		$nota_fiscal_tc = str_replace(".","",$nota_fiscal_tc);
		$nota_fiscal_tc = str_replace(",","",$nota_fiscal_tc);
		$nota_fiscal_tc = str_replace("-","",$nota_fiscal_tc);
		$nota_fiscal_tc = str_replace("/","",$nota_fiscal_tc);

		$nota_fiscal_tc = ltrim ($nota_fiscal_tc, "0");

		if (!is_numeric($nota_fiscal_tc)) {
			$msg_erro .= "O número das notas fiscais devem ter somente números!";
		}

		array_push($array_notas_tc,$nota_fiscal_tc);

		$total_nota = trim($_POST["id_nota_tc_$i-total_nota"]);
		$base_icms  = trim($_POST["id_nota_tc_$i-base_icms"]);
		$valor_icms = trim($_POST["id_nota_tc_$i-valor_icms"]);
		$base_ipi   = trim($_POST["id_nota_tc_$i-base_ipi"]);
		$valor_ipi  = trim($_POST["id_nota_tc_$i-valor_ipi"]);
		$cfop       = trim($_POST["id_nota_tc_$i-cfop"]);

		//$linha_nota = trim($_POST["id_nota_$i-linha"]);

		$cfop = (strlen($cfop)>0) ? " '$cfop' " : " NULL ";

		$qtde_peca_na_nota = trim($_POST["id_nota_tc_$i-qtde_itens"]);

		$sql = "INSERT INTO tbl_faturamento
					(
						fabrica           ,
						emissao           ,
						saida             ,
						posto             ,
						distribuidor      ,
						total_nota        ,
						nota_fiscal       ,
						serie,
						natureza   ,
						base_icms         ,
						valor_icms        ,
						base_ipi          ,
						valor_ipi         ,
						extrato_devolucao ,
						obs               ,
						cfop
					) VALUES (
						$login_fabrica         ,
						'$data_preenchimento'  ,
						'$data_preenchimento'  ,
						4311                   ,
						$login_posto           ,
						$total_nota            ,
						'$nota_fiscal_tc'      ,
						'2'                    ,
						'Devolução de Garantia',
						$base_icms             ,
						$valor_icms            ,
						$base_ipi              ,
						$valor_ipi             ,
						$extrato               ,
						'Devolução de peças do posto para à  Fábrica',
						$cfop
					)";
		$res = pg_query ($con,$sql);

		$sql = "SELECT CURRVAL ('seq_faturamento')";
		$resZ = pg_query ($con,$sql);
		$faturamento_codigo = pg_fetch_result ($resZ,0,0);

		for($x=0;$x<$qtde_peca_na_nota;$x++){

			$lgr                = trim($_POST["id_item_tc_LGR_$x-$i"]);
			$peca               = trim($_POST["id_item_tc_peca_$x-$i"]);
			$peca_preco         = trim($_POST["id_item_tc_preco_$x-$i"]);
			$peca_qtde_total_nf = trim($_POST["id_item_tc_qtde_$x-$i"]);
			$peca_aliq_icms     = trim($_POST["id_item_tc_icms_$x-$i"]);
			$peca_aliq_ipi      = trim($_POST["id_item_tc_ipi_$x-$i"]);
			$peca_total_item    = trim($_POST["id_item_tc_total_$x-$i"]);

			$sql_nf = "SELECT
							tbl_faturamento_item.faturamento_item,
							tbl_faturamento.nota_fiscal,
							tbl_faturamento_item.qtde,
							tbl_faturamento_item.peca,
							tbl_faturamento_item.preco,
							tbl_faturamento_item.aliq_icms,
							tbl_faturamento_item.aliq_ipi,
							tbl_faturamento_item.base_icms,
							tbl_faturamento_item.valor_icms,
							tbl_faturamento_item.linha,
							tbl_faturamento_item.base_ipi,
							tbl_faturamento_item.valor_ipi,
							tbl_faturamento_item.sequencia
						FROM tbl_faturamento_item
						JOIN tbl_faturamento      USING (faturamento)
						JOIN tbl_peca             USING (peca)
						WHERE tbl_faturamento.fabrica = $login_fabrica
						AND   tbl_faturamento.posto   = $login_posto
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND   tbl_faturamento_item.peca=$peca
						AND   tbl_faturamento_item.preco=$peca_preco
						AND   tbl_faturamento.distribuidor = 4311
						AND   tbl_peca.fabrica             = $login_fabrica
						AND   tbl_faturamento_item.aliq_icms>0
						ORDER BY tbl_faturamento.nota_fiscal";
			$resNF = pg_query ($con,$sql_nf);
			$qtde_peca_inserir=0;
			if (pg_num_rows ($resNF)==0){
				$msg_erro .= "Erro.";
				# Nelson pediu para nw mandar mais email HD 2937
				$email_origem  = "helpdesk@telecontrol.com.br";
				$email_destino = 'fabio@telecontrol.com.b';
				$assunto       = "Extrato com erro";
				$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\n $msg_erro \n $sql_nf";
				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
				@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem);
				break;
			}
			for ($w = 0 ; $w < pg_num_rows ($resNF) ; $w++) {
				$faturamento_item= pg_fetch_result ($resNF,$w,faturamento_item);
				$peca_nota       = pg_fetch_result ($resNF,$w,nota_fiscal);
				$peca_qtde       = pg_fetch_result ($resNF,$w,qtde);
				$peca_peca       = pg_fetch_result ($resNF,$w,peca);
				$peca_preco      = pg_fetch_result ($resNF,$w,preco);
				$peca_aliq_icms  = pg_fetch_result ($resNF,$w,aliq_icms);
				$peca_base_icms  = pg_fetch_result ($resNF,$w,base_icms);
				$peca_valor_icms = pg_fetch_result ($resNF,$w,valor_icms);
				$peca_linha      = pg_fetch_result ($resNF,$w,linha);
				$peca_aliq_ipi   = pg_fetch_result ($resNF,$w,aliq_ipi);
				$peca_base_ipi   = pg_fetch_result ($resNF,$w,base_ipi);
				$peca_valor_ipi  = pg_fetch_result ($resNF,$w,valor_ipi);
				$sequencia       = pg_fetch_result ($resNF,$w,sequencia);

				if ($peca_aliq_icms>0){
					$peca_base_icms = $peca_qtde_total_nf*$peca_preco;
					$peca_valor_icms= $peca_qtde_total_nf*$peca_preco*$peca_aliq_icms/100;
				}else{
					$peca_aliq_icms=0;
					$peca_base_icms=0;
					$peca_valor_icms=0;
				}
				if ($peca_aliq_ipi>0){
					$peca_base_ipi = $peca_qtde_total_nf*$peca_preco;
					$peca_valor_ipi= $peca_qtde_total_nf*$peca_preco*$peca_aliq_ipi/100;
				}else{
					$peca_aliq_ipi=0;
					$peca_base_ipi=0;
					$peca_valor_ipi=0;
				}

				$sql = "INSERT INTO tbl_faturamento_item
						(faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, linha, base_ipi, valor_ipi,nota_fiscal_origem,sequencia)
						VALUES ($faturamento_codigo, $peca,$peca_qtde, $peca_preco, $peca_aliq_icms, $peca_aliq_ipi, $peca_base_icms, $peca_valor_icms, $peca_linha, $peca_base_ipi, $peca_valor_ipi,'$peca_nota','$sequencia')";
				$res = pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}
}

	# Rotina para Ressarcimento

	$ressarcimento               = $_POST['ressarcimento'];
	$qtde_produtos_ressarcimento = $_POST['qtde_produtos_ressarcimento'];
	$ressarcimento_nota_fiscal   = $_POST['ressarcimento_nota_fiscal'];
	$ressarcimento_cfop          = $_POST['ressarcimento_cfop'];
	$ressarcimento_natureza      = $_POST['ressarcimento_natureza'];

	if (strlen($msg_erro)==0 AND strlen($ressarcimento)>0 AND $ressarcimento==$extrato AND $qtde_produtos_ressarcimento>0){
		if (strlen($ressarcimento_nota_fiscal)>0){


			$sql = "INSERT INTO tbl_faturamento
					(fabrica, emissao,saida, posto, distribuidor, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs, cfop)
					VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',13996,$login_posto,0,'$ressarcimento_nota_fiscal','2','$ressarcimento_natureza', 0, 0, 0, 0, $extrato, 'Devolução de peças do posto para à Fábrica - Ressarcimento', '$ressarcimento_cfop')";
			$res = pg_query ($con,$sql);

			$sql = "SELECT CURRVAL ('seq_faturamento')";
			$resZ = pg_query ($con,$sql);
			$faturamento_codigo = pg_fetch_result ($resZ,0,0);

			if($login_fabrica == 3){

				$imagem = $_FILES["ressarcimento_nota_fiscal_img"];
				if(!empty($imagem['name']) > 0){

					$msg_erro .= anexaNFDevolucao($imagem,$extrato,$faturamento_codigo,$ressarcimento_nota_fiscal);

				} else {
					$msg_erro .= "Imagem da Nota Fiscal $ressarcimento_nota_fiscal é obrigatória <br />";
				}
			}

			for ( $x = 0 ; $x < $qtde_produtos_ressarcimento ; $x++ ){
				$ressarcimento_produto = trim($_POST['ressarcimento_produto_'.$x]);
				$ressarcimento_os      = trim($_POST['ressarcimento_os_'.$x]);


				if (strlen($ressarcimento_os)==0){
					$ressarcimento_os = " NULL ";
				}

				if (strlen($ressarcimento_produto)>0){

					$sql2 = "SELECT referencia,descricao,ipi
							FROM tbl_produto
							WHERE produto = $ressarcimento_produto";
					$resPeca = pg_query ($con,$sql2);

					if (pg_num_rows($resPeca) > 0) {
						$referencia = pg_fetch_result($resPeca,0,referencia);
						$descricao  = pg_fetch_result($resPeca,0,descricao);
						$ipi        = pg_fetch_result($resPeca,0,ipi);
						# HD 31398 - Francisco Ambrozio (5/8/08)
						if (strlen($ipi) == 0) {
							$ipi = 0;
						}
					}else {
						$msg_erro .= "Produto não encontrado.";
					}

					$sql2 = "SELECT peca
							FROM tbl_peca
							WHERE fabrica = $login_fabrica
							AND produto_acabado IS TRUE
							AND referencia = '$referencia'
							LIMIT 1";
					$resPeca = pg_query ($con,$sql2);

					if (pg_num_rows($resPeca) > 0) {
						$peca = pg_fetch_result($resPeca,0,0);
					}else{
						$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, ipi, origem, produto_acabado)
								VALUES ($login_fabrica, '$referencia', '$descricao' , $ipi , 'NAC','t')" ;
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "SELECT CURRVAL ('seq_peca')";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
						$peca = pg_fetch_result($res,0,0);
					}

					if (strlen($peca)>0){
						$sql = "INSERT INTO tbl_faturamento_item
								(faturamento, peca, qtde,preco, os)
								VALUES ($faturamento_codigo, $peca,1,0, $ressarcimento_os)";
						$res = pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}
		}else{
			$msg_erro .= "Digite a nota fiscal de devolução dos produtos ressarcidos.";
		}
	}

	### Produtos Trocado enviado DIRETO ao CONSUMIDOR ###
	# HD 13316
	$envio_consumidor               = trim($_POST['envio_consumidor']);
	$qtde_produtos_envio_consumidor = trim($_POST['qtde_produtos_envio_consumidor']);
	$envio_consumidor_nota_fiscal   = trim($_POST['envio_consumidor_nota_fiscal']);
	$envio_consumidor_cfop          = trim($_POST['envio_consumidor_cfop']);
	$envio_consumidor_natureza      = trim($_POST['envio_consumidor_natureza']);

	if (strlen($msg_erro)==0 AND strlen($envio_consumidor)>0 AND $envio_consumidor==$extrato AND $qtde_produtos_envio_consumidor>0){
		if (strlen($envio_consumidor_nota_fiscal)>0){
			$sql = "INSERT INTO tbl_faturamento
					(fabrica, emissao,saida, posto, distribuidor, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs, cfop)
					VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',13996,$login_posto,0,'$envio_consumidor_nota_fiscal','2','$envio_consumidor_natureza', 0, 0, 0, 0, $extrato, 'Devolução de produtos do posto para à Fábrica', '$envio_consumidor_cfop')";
			$res = pg_query ($con,$sql);

			$sql = "SELECT CURRVAL ('seq_faturamento')";
			$resZ = pg_query ($con,$sql);
			$faturamento_codigo = pg_fetch_result ($resZ,0,0);

			for ( $x = 0 ; $x < $qtde_produtos_envio_consumidor ; $x++ ){
				$envio_consumidor_produto = trim($_POST['envio_consumidor_produto_'.$x]);
				$envio_consumidor_os      = trim($_POST['envio_consumidor_os_'.$x]);

				if (strlen($envio_consumidor_os)==0){
					$envio_consumidor_os = " NULL ";
				}

				if (strlen($envio_consumidor_produto)>0){

					$sql2 ="SELECT referencia,descricao,ipi
							FROM tbl_produto
							WHERE produto = $envio_consumidor_produto";
					$resPeca = pg_query ($con,$sql2);
					if (pg_num_rows($resPeca) > 0) {
						$referencia = pg_fetch_result($resPeca,0,referencia);
						$descricao  = pg_fetch_result($resPeca,0,descricao);
						$ipi        = pg_fetch_result($resPeca,0,ipi);
						if (strlen($ipi)==0){
							$ipi = "0";
						}
					} else {
						$msg_erro .= "Produto não encontrado.";
					}

					$sql2 = "SELECT peca
								FROM tbl_peca
								WHERE fabrica = $login_fabrica
								AND produto_acabado IS TRUE
								AND referencia = '$referencia'
								LIMIT 1";
					$resPeca = pg_query ($con,$sql2);

					if (pg_num_rows($resPeca) > 0) {
						$peca = pg_fetch_result($resPeca,0,0);
					}else{
						$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, ipi, origem, produto_acabado)
								VALUES ($login_fabrica, '$referencia', '$descricao' , $ipi , 'NAC','t')" ;
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "SELECT CURRVAL ('seq_peca')";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
						$peca = pg_fetch_result($res,0,0);
					}

					if (strlen($peca)>0){
						$sql = "INSERT INTO tbl_faturamento_item
								(faturamento, peca, qtde,preco, os)
								VALUES ($faturamento_codigo, $peca,1,0, $envio_consumidor_os)";
						$res = pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}
		}else{
			$msg_erro .= "Digite a nota fiscal de devolução dos produtos.";
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql_update = "UPDATE tbl_extrato_lgr
				SET qtde_pedente_temp = null
				WHERE extrato=$extrato";
		$res_update = pg_query ($con,$sql_update);
		$msg_erro .= pg_errormessage($con);
	}

	/*if (strlen($msg_erro) == 0) {
		if (count(array_unique($array_notas))<>$numero_de_notas){
			$msg_erro .= "Erro: não é permitido digitar número de notas iguais. Preencha novamente as notas.";
		}
	}*/

	if (strlen($msg_erro) == 0) {
		$resX = pg_query ($con,"COMMIT TRANSACTION");
	}else{
		$resX = pg_query ($con,"ROLLBACK TRANSACTION");
	}
	$nota_fiscal = "";
}

$jah_digitado_tc = 1;
$sql_tc = "SELECT *
		FROM tbl_faturamento_item
		JOIN tbl_faturamento      USING (faturamento)
		JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
		WHERE tbl_faturamento.fabrica = $login_fabrica
		AND   tbl_faturamento.posto   = $login_posto
		AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
		AND   tbl_faturamento.extrato_devolucao = $extrato
		AND   tbl_faturamento.distribuidor = 4311
		AND   tbl_faturamento_item.aliq_icms > 0";
		//echo $sql;
$resTC = pg_query ($con,$sql_tc);
$jah_digitado_tmp = pg_num_rows ($resTC);

if ($jah_digitado_tmp>0){
	$sql = "SELECT  faturamento
		FROM tbl_faturamento
		WHERE posto = 4311
		AND distribuidor=$login_posto
		AND fabrica=$login_fabrica
		AND extrato_devolucao=$extrato
		ORDER BY faturamento ASC";
	$res = pg_query ($con,$sql);
	$jah_digitado_tc =pg_num_rows ($res);
}

$sql = "SELECT  faturamento,
		extrato_devolucao,
		nota_fiscal,
		distribuidor,
		NULL as produto_acabado,
		NULL as devolucao_obrigatoria
	FROM tbl_faturamento
	WHERE posto IN (13996,4311)
	AND distribuidor=$login_posto
	AND fabrica=$login_fabrica
	AND extrato_devolucao=$extrato
	ORDER BY faturamento ASC";
	//echo $sql;
$res = pg_query ($con,$sql);
$jah_digitado=pg_num_rows ($res);

if ($jah_digitado>0 AND $pecas_pendentes!= 'sim'){
	header("location: extrato_posto_devolucao_lgr_itens.php?extrato=$extrato");
	exit();
}

// redirecionar para a pagina antiga se a nota ja foi digitada. Novas notas irao para esta pagina
if (strlen($extrato)>0 and 1==2){
	$sql = "SELECT	*
		FROM tbl_faturamento
		JOIN tbl_faturamento_item USING(faturamento)
		WHERE tbl_faturamento.extrato_devolucao = $extrato
		AND tbl_faturamento.fabrica= $login_fabrica
		AND tbl_faturamento.posto=$login_posto
		AND tbl_faturamento_item.extrato_devolucao is not null";
	$res = pg_query ($con,$sql);
	$qntos_digitou = pg_num_rows($res);

	if ($qntos_digitou==0){
		$sql = "SELECT	*
			FROM tbl_extrato_devolucao
			WHERE extrato = $extrato";
		$res = pg_query ($con,$sql);
		$qntos_tem = pg_num_rows($res);

		$sql = "SELECT	*
			FROM tbl_extrato_devolucao
			WHERE extrato = $extrato
			AND nota_fiscal is not null";
		$res = pg_query ($con,$sql);
		$qntos_falta = pg_num_rows($res);

		if ($qntos_falta == $qntos_tem AND $qntos_tem>0) {
			header("Location: extrato_posto_devolucao.php?extrato=$extrato");
			exit();
		}
	}
}

$msg = "";

$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
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

.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: red
}
.menu_top3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #FA8072
}


.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
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
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>


<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>

<script type="text/javascript">


<?php if($login_fabrica == 3){ ?>

window.onload = function(){
	Shadowbox.init({
		skipSetup	: true,
		enableKeys	: false,
		modal 		: true
	});

	var conteudo_shadowbox = "<table bgcolor='#ffffff' width='360' height='190'>";
	conteudo_shadowbox += "<tr>";
	conteudo_shadowbox += "<td style='background-color:#ff0000; font-size:18px; text-align:center; line-height:20px;'>Atenção</td>";
	conteudo_shadowbox += "</tr>";
	conteudo_shadowbox += "<tr>";
	conteudo_shadowbox += "<td style='font-size: 14px; text-align:justify;'>";
	conteudo_shadowbox += "Informamos que houve alteração no CNPJ desde 01/03/2017. Favor se atentar a mudança e seguir conforme mostra no Extrato.";
	conteudo_shadowbox += "</td>";
	conteudo_shadowbox += "</tr>";
	conteudo_shadowbox += "<tr>";
	conteudo_shadowbox += "<td style='text-align:center;'>";
	conteudo_shadowbox += "<button type='button' onclick=\"Shadowbox.close();\" style='width:250px'>OK</button>";
	conteudo_shadowbox += "</td>";
	conteudo_shadowbox += "</tr>";
	conteudo_shadowbox += "</table>";

	Shadowbox.open({
		content: conteudo_shadowbox,
		player:	"html",
		title:	"Alerta",
		width:	360,
		height:	200,
		options: {onFinish: function(){
			$("#sb-nav-close").hide();
		},
			overlayColor:'#fcfcfc' }
	});
}

<?php } ?>
function verificar(forrr){
	var theform = document.getElementById('frm_devolucao');
	var returnval=true;
	for (i=0; i<theform.elements.length; i++){
		if (theform.elements[i].type=="text"){
			if (theform.elements[i].value==""){ //if empty field
				alert("Por favor, informe todas as notas!");
				theform.botao_acao.value='';
				returnval=false;
				break;
			}
		}
	}
	return returnval;
}

</script>

<br><br>
<?

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato ";
$res = pg_query ($con,$sql);
$data    = pg_fetch_result ($res,0,data);
$periodo = pg_fetch_result ($res,0,periodo);
$nome    = pg_fetch_result ($res,0,nome);
$codigo  = pg_fetch_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='-1' face='arial'>$codigo - $nome</font>";

if(!empty($extrato)) {


	if($login_fabrica == 3){
		$data_corte_britania = " AND tbl_extrato.data_geracao >= '2017-08-01 00:00:00'  ";
		$join_extrato = " join tbl_extrato on tbl_extrato.extrato = tbl_faturamento.extrato_devolucao ";
	}

	$sql = " SELECT  tbl_faturamento.extrato_devolucao
			FROM    tbl_faturamento
			JOIN    tbl_faturamento_item USING (faturamento)
			JOIN    tbl_peca             USING (peca)
			$join_extrato
			LEFT    JOIN tbl_faturamento lgr ON tbl_faturamento.fabrica = lgr.fabrica AND tbl_faturamento.posto = lgr.distribuidor AND tbl_faturamento.extrato_devolucao = lgr.extrato_devolucao
			WHERE   tbl_faturamento.extrato_devolucao < $extrato
			AND     tbl_faturamento.fabrica = $login_fabrica
			AND     tbl_faturamento.posto             = $login_posto
			AND     tbl_faturamento.distribuidor IS NULL

			$data_corte_britania

			AND     lgr.faturamento IS NULL
			AND    (tbl_faturamento_item.devolucao_obrig IS TRUE )
			AND    (tbl_faturamento.cfop IN (
							'594906',
							'594916',
							'594919',
							'594920',
							'594923',
							'594972',
							'594973',
							'594979',
							'594980',
							'594981',
							'594982',
							'694902',
							'694913',
							'694919',
							'694921',
							'694922',
							'694923',
							'694925',
							'694926',
							'694928',
							'694972',
							'694977',
							'694978',
							'694979',
							'694980',
							'694981',
							'694982'
						)
					)";
	$res = pg_query($con,$sql);
	
	if(pg_num_rows($res) > 0){
		$extrato_devolucao = pg_fetch_result($res,0,'extrato_devolucao');
		$sqle = " SELECT to_char(data_geracao,'DD/MM/YYYY') as data_geracao, tbl_extrato_agrupado.aprovado
				FROM tbl_extrato
				LEFT JOIN tbl_extrato_agrupado USING(extrato)
				
				WHERE tbl_extrato.extrato = $extrato_devolucao
				$data_corte_britania  ";
		$rese = pg_query($con,$sqle);

		if(pg_num_rows($rese) > 0){
			$data_geracao = pg_fetch_result($rese,0,'data_geracao');
			$extr_aprovado = pg_fetch_result($rese, 0, 'aprovado');

			if (empty($extr_aprovado)) {
				echo "<p><font size='+1' face='arial'>Ainda falta o posto preencher e devolver o lgr dos seguintes extratos ($data_geracao),<br/> tão logo seja preenchido o extrato atual será liberado</font>";
			}
		}
	}
}

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<?php
	if($login_fabrica == 3) {

		 $sql = "SELECT   extrato
					FROM  tbl_extrato
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   tbl_extrato.posto = $login_posto
					ORDER BY  tbl_extrato.extrato DESC LIMIT 1";
		$res = pg_query($con,$sql);

		if(pg_numrows($res) > 0){

			$ultimo_extrato = pg_result($res,0,extrato);



			$sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						JOIN    tbl_peca             USING (peca)
						WHERE   tbl_faturamento.extrato_devolucao <= $ultimo_extrato
						AND     tbl_faturamento.fabrica = $login_fabrica
						AND     tbl_faturamento.posto             = $login_posto
						AND     tbl_faturamento.distribuidor IS NULL
						AND     (tbl_faturamento_item.devolucao_obrig IS TRUE or tbl_peca.produto_acabado       IS TRUE)
						AND    (tbl_faturamento.cfop IN (
										'594906',
										'594916',
										'594919',
										'594920',
										'594923',
										'594972',
										'594973',
										'594979',
										'594980',
										'594981',
										'594982',
										'694902',
										'694913',
										'694919',
										'694921',
										'694922',
										'694923',
										'694925',
										'694926',
										'694928',
										'694972',
										'694977',
										'694978',
										'694979',
										'694980',
										'694981',
										'694982'
									)
								)
						AND     tbl_faturamento.extrato_devolucao NOT IN (
									SELECT  distinct
								extrato_devolucao
								FROM tbl_faturamento
								WHERE posto IN (13996,4311)
								AND distribuidor=$login_posto
								AND fabrica=$login_fabrica
								AND extrato_devolucao < $ultimo_extrato
								AND extrato_devolucao <> $extrato
						)
						ORDER BY  tbl_faturamento.extrato_devolucao DESC";
			$ress = pg_query ($con,$sqls);
			$res_qtdes = pg_num_rows ($ress);

			if ($res_qtdes> 0){

				$extrato_aux = pg_result($ress,0,extrato_devolucao);

				 $sqlD="SELECT extrato_devolucao
					FROM   tbl_faturamento
					WHERE  distribuidor = $login_posto
					AND    extrato_devolucao = $extrato_aux
					AND    fabrica = $login_fabrica;";
				$resD = pg_query($con,$sqlD);

				if(pg_num_rows($resD) == 0){
					$sqld = " SELECT tbl_extrato.extrato,to_char(data_geracao,'DD/MM/YYYY') as data_extrato, tbl_extrato_agrupado.aprovado
							FROM tbl_extrato
							LEFT JOIN tbl_extrato_agrupado USING (extrato)
							WHERE tbl_extrato.extrato = $extrato_aux
							AND   tbl_extrato.fabrica = $login_fabrica
							AND   tbl_extrato.posto   = $login_posto
							AND   tbl_extrato.data_geracao > '2010-01-01 00:00:00'
							ORDER BY extrato DESC limit 1;";
					$resd = pg_query($con,$sqld);
					if(pg_num_rows($resd) > 0){
						$extrato_dev = pg_fetch_result($resd,0,'extrato');
						$aprovado_dev = pg_fetch_result($resd, 0, 'aprovado');

						if (empty($aprovado_dev)) {
							echo "<td><a href='extratos_pendentes_britania.php'>Ver extratos pendentes</a></td>";
						} else {
							echo "<td align='center' width='33%'><a href='$PHP_SELF?mao=sim&extrato=$extrato'>Ver Mão-de-Obra</a></td>";
						}
					} else {
						echo "<td align='center' width='33%'><a href='$PHP_SELF?mao=sim&extrato=$extrato'>Ver Mão-de-Obra</a></td>";
					}

				}else {
					echo "<td align='center' width='33%'><a href='$PHP_SELF?mao=sim&extrato=$extrato'>Ver Mão-de-Obra</a></td>";
				}
			} else {
				echo "<td align='center' width='33%'><a href='$PHP_SELF?mao=sim&extrato=$extrato'>Ver Mão-de-Obra</a></td>";
			}
		}
	} else {
?>
<td align='center' width='33%'><a href='<?php echo $PHP_SELF ?>?mao=sim&extrato=<? echo $extrato ?>'>Ver Mão-de-Obra</a></td>
<?php } ?>
<td align='center' width='33%'><a href='extrato_posto.php'>Ver outro extrato</a></td>
</tr>
</table>

<div id='loading'></div>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro ?></td>
	</tr>
</table>
<? } ?>

<center>

<? if (strlen($numero_linhas) > 0 AND $diminuiu=='sim' AND $ok_aceito!='Concordo') { ?>
<br>

<form method='post' action='<? echo $PHP_SELF; ?>#notas_d' name='frm_confirmar' id='frm_confirmar' >
<input type='hidden' name='extrato' value='<? echo $extrato; ?>'>
<input type='hidden' name='qtde_linha' value='<? echo $numero_linhas; ?>'>
<input type='hidden' name='ok_aceito' value='Concordo'>
<input type='hidden' name='pendentes' value='sim'>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD colspan="10" class="menu_top2" ><div align="center" style='font-size:16px'>
	<b>
	ATENÇÃO
	</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="menu_top3" style='padding:10px;'>
	As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de consulta de pendências. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
	<br><br>
	</td>
</tr>
<TR>
	<TD colspan='8' class="menu_top2" align='center'>
	<center>
	<input type='button' name='ok' value='Concordo' class='frm' onclick="javascript:if (this.value=='Concordo.'){altert('Aguarde submissão.');}else{if(confirm('Deseja continuar?')){this.value='Concordo.';document.frm_confirmar.submit();}}">
	<input type='button' value='Voltar' name='voltar' onclick="javascript:
				if(confirm('Deseja voltar?')) window.location='<? echo $PHP_SELF; ?>?extrato=<? echo $extrato; ?>';">
	</center>
	</TD>
</TR>
</table>
</form>
<? exit(); } ?>

<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top" ><div align="center" style='font-size:16px'>
	<b>
	<? echo ($pecas_pendentes=="sim") ? "DEVOLUÇÃO PENDENTE" : "ATENÇÃO"; ?>
	</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de consulta de pendências. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
<br><br>
<? //HD 15408 ?>
<b style='font-size:14px;font-weight:normal'>Emitir as NF de devolução nos mesmos valores e impostos, referenciando NF de origem, e postagem da NF de acordo com o cabeçalho de cada nota fiscal.</b>

<?php if($login_fabrica ==3 ){?>
<br><br>
<b style='font-size:14px;font-weight:normal'>
"O prazo para anexar o Comprovante de Envio do LGR (Correios ou Transportadora) é de 30 dias, o não cumprimento deste requisito poderá implicar em bloqueio dos próximos extratos, até regularização." </b>
<?php }?>

	</TD>
</TR>
</table>

<?
if($login_fabrica ==3 ){
?>
<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">

<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
<b style='font-size:14px;font-weight:normal'>
Prezados Postos.<br>
A Britânia reduziu o número de peças de devolução obrigatória.<br>
Favor realizar as devoluções de peças de acordo com o mostrado nas telas de acesso ao extrato financeiro.<br>
Não haverá devolução de Nota Fiscal sem o envio físico das peças.<br>
Atenção para o prazo de armazenamento de peças para vistoria, conforme informações do Telecontrol.

<br>
<div class="span8 tac">
	<a href='lgr_vistoria_itens.php' class="btn btn-primary">VISTORIA</a>
</div>
<br>

Todos os produtos trocados devem ser enviados à Britânia no físico e Nota Fiscal, de acordo com o apresentado pelo Telecontrol.
<br>

</b>
	</TD>
</TR>
</table>
<br>
<script type="text/javascript">alert(")</script>
<?
	$sqlnf = "select extrato from tbl_extrato where nf_recebida is not true and extrato = $extrato";
	$resnf = pg_query($con, $sqlnf);
	if($res_qtde == 0 and pg_num_rows($resnf) > 0 ) {
		echo "<h2>Atenção: Não há produtos ou peças para devolução</h2><br>";
		echo "<input type='button' value='OK' name='btn_sem_lgr' onclick=\"document.frm_devolucao.submit();\">";
	}


} ?>
<br>

<?

if ($login_fabrica == 3) {

    $tDocs = new TDocs($con, $login_fabrica);
    $temAnexo = array();

    	$sql = "SELECT distinct tbl_extrato.extrato
		FROM tbl_extrato
		JOIN tbl_faturamento ON tbl_faturamento.extrato_devolucao = tbl_extrato.extrato
		AND tbl_faturamento.fabrica = $login_fabrica
		AND tbl_faturamento.distribuidor = $login_posto
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND tbl_extrato.posto = $login_posto
		AND data_geracao >= '2017-08-01 00:00:00'
		AND tbl_faturamento.baixa IS NULL
		ORDER BY extrato DESC";
    	$res = pg_query($con, $sql);
	$extratos = pg_fetch_all($res);
	$totalExtratos = count($extratos);

	if ($totalExtratos > 3) {

		foreach ($extratos as $kExtrato => $vExtrato) {

    		$anexo = $tDocs->getDocumentsByRef($vExtrato['extrato'],'comprovantelgr')->attachListInfo;
			if (!empty($anexo)) {
				$temAnexo[] = $vExtrato['extrato'];
			}

		}

		$totalSemAnexo = count($extratos)-count($temAnexo);

	    if ($totalSemAnexo > 3) {
	    	echo '
	    	    <table width="650" align="center" border="0" cellspacing="0" cellpadding="2">
                  	<tr>
		                <td class="msg_erro" style="padding:10px">
							Devem ser anexados os Comprovantes de Envio do LGR dos extratos anteriores para liberar a tela de consulta de valores de mão-de-obra.
						</td>
					</tr>
				</table>';
			include "rodape.php";
	    	exit;
	    }

	}

}

$sql = "UPDATE tbl_faturamento_item SET linha = (SELECT tbl_produto.linha FROM tbl_produto
				JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_faturamento_item.peca = tbl_lista_basica.peca LIMIT 1)
		FROM tbl_faturamento
		WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		AND tbl_faturamento.fabrica = $login_fabrica
		AND tbl_faturamento.extrato_devolucao = $extrato";
$res = pg_query ($con,$sql);

if ($login_fabrica == 3) {
	$sql = "UPDATE tbl_faturamento_item SET linha = 2
			FROM tbl_faturamento
			WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			AND tbl_faturamento.fabrica = $login_fabrica
			AND tbl_faturamento.extrato_devolucao = $extrato
			AND tbl_faturamento_item.linha IS NULL";
	$res = pg_query ($con,$sql);
}

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_query ($con,$sql);
$estado_origem = pg_fetch_result ($resX,0,estado);

$sql = "SELECT  tbl_faturamento.extrato_devolucao,
		tbl_faturamento.distribuidor,
		CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
		tbl_faturamento_item.devolucao_obrig
	FROM    tbl_faturamento
	JOIN    tbl_faturamento_item USING (faturamento)
	JOIN    tbl_peca             USING (peca)
	WHERE   tbl_faturamento.extrato_devolucao = $extrato
	AND     tbl_faturamento.posto             = $login_posto
	AND     tbl_faturamento.distribuidor IS NULL
	AND    (tbl_faturamento.cfop IN (
					'594906',
					'594916',
					'594919',
					'594920',
					'594923',
					'594972',
					'594973',
					'594979',
					'594980',
					'594981',
					'594982',
					'694902',
					'694913',
					'694919',
					'694921',
					'694922',
					'694923',
					'694925',
					'694926',
					'694928',
					'694972',
					'694977',
					'694978',
					'694979',
					'694980',
					'694981',
					'694982'
				)
			)
	 LIMIT 1 ";

$res = pg_query ($con,$sql);
$res_qtde = pg_num_rows ($res);

if (in_array($login_fabrica, [3]) && $res_qtde == 0) { ?>
<?php	
}

if ($res_qtde > 0 OR 1==1) {

	echo "<form method='post' action='$PHP_SELF' name='frm_devolucao' id='frm_devolucao' enctype='multipart/form-data'>";
	echo "<input type='hidden' name='notas_d' value=''>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo ($res_qtde == 0 ) ? "<input type='hidden' name='sem_lgr' value='nf_recebida'>": "";
	echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";

	$contador=0;

	$qtde_for = 4;

	for($xx=1;$xx< $qtde_for ;$xx++) {

		$extrato_devolucao = $extrato;
		# HD 270701 Separar linha informática com outras linhas
		switch ($xx) {
			case 1:
					$devolucao = " RETORNO OBRIGATÓRIO ";
					$movimento = "RETORNAVEL";
					$pecas_produtos = "PRODUTOS";
					$condicao_2 = " AND tbl_peca.produto_acabado IS TRUE
					AND tbl_peca.peca NOT IN (
						SELECT peca
						FROM tbl_peca
						JOIN tbl_produto USING(referencia)
						WHERE fabrica = $login_fabrica
						AND   linha = 528
					)
					";
					$sql_adicional_peca2 = "";
				break;
			case 2:
					$devolucao = " RETORNO OBRIGATÓRIO ";
					$movimento = "RETORNAVEL";
					$pecas_produtos = "PRODUTOS";
					$condicao_2 = " AND tbl_peca.produto_acabado IS TRUE
						AND tbl_peca.peca IN (
						SELECT peca
						FROM tbl_peca
						JOIN tbl_produto USING(referencia)
						WHERE fabrica = $login_fabrica
						AND   linha = 528
					)
					";
					$sql_adicional_peca2 = "";
				break;
			case 3:
					$devolucao = " RETORNO OBRIGATÓRIO ";
					$movimento = "RETORNAVEL";
					$pecas_produtos = "PEÇAS";
					$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
					$sql_adicional_peca2 = " AND tbl_faturamento_item.devolucao_obrig  = 't'";
				break;
			case 4:
				$devolucao = " NÃO RETORNÁVEIS ";
				$movimento = "NAO_RETOR.";
				$pecas_produtos = "PEÇAS";
				$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
				$sql_adicional_peca2 = " AND tbl_faturamento_item.devolucao_obrig  = 'f'";
				break;
		}

		if ($pecas_produtos=='PEÇAS') {
			#Ignora os postos abaixo - não mostra as peças
			if ($extrato > 240000 AND array_search($login_posto, $postos_permitidos_novo_processo)>0) {
				continue;
			}
		}

		//HD43448
		if(strtotime($data_geracao_extrato) >= strtotime("2017-03-01")){

			$razao    = "BRITANIA ELETRONICOS SA";
			$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
			$cidade   = "Joinville";
			$estado   = "SC";
			$cep      = "89239-270";
			$fone     = "(41) 2102-7700";
			$cnpj     = "07019308000128";
			$ie       = "254.861.660";

		}else{

			$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
			$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
			$cidade   = "Joinville";
			$estado   = "SC";
			$cep      = "89239270";
			$fone     = "(41) 2102-7700";
			$cnpj     = "76492701000742";
			$ie       = "254.861.652";

		}

		$distribuidor = "null";
		$condicao_1 = " AND tbl_faturamento.distribuidor IS NULL ";

		$sql_adicional_peca = ($numero_linhas!=5000) ? " AND tbl_extrato_lgr.qtde_pedente_temp>0 " : "";

		#hd 253517
		#$sqli = " SELECT linha FROM tbl_posto_linha
		#			WHERE posto=$login_posto
		#			AND   linha =528";
		//echo $sqli;
		#$resi = pg_query($con,$sqli);

		############ SERIE 20 HD 213666#########################
		$sql = "SELECT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ipi,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				tbl_faturamento_item.devolucao_obrig as devolucao_obrigatoria,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento.serie,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco,
				sum(tbl_faturamento_item.qtde) as qtde_real,
				tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END AS qtde_total_item,
				tbl_extrato_lgr.qtde_nf AS qtde_total_nf,
				tbl_extrato_lgr.qtde_pedente_temp AS qtde_pedente_temp,
				tbl_extrato_lgr.extrato_lgr AS extrato_lgr,
				(tbl_extrato_lgr.qtde_pedente_temp * tbl_faturamento_item.preco) AS total_item,
				tbl_faturamento.cfop,
				SUM (tbl_faturamento_item.base_icms) AS base_icms,
				SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
				SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
				SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi,
				SUM (tbl_faturamento_item.base_subs_trib) AS base_subs_trib,
				SUM (tbl_faturamento_item.valor_subs_trib) AS valor_subs_trib
				FROM tbl_peca
				JOIN tbl_faturamento_item USING (peca)
				JOIN tbl_faturamento      USING (faturamento)
				JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato=tbl_faturamento.extrato_devolucao AND tbl_extrato_lgr.peca=tbl_faturamento_item.peca
				$join_agrupado
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   tbl_faturamento.posto=$login_posto";
				$sql .= " AND (tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END)>0
						AND    (tbl_faturamento.cfop IN (
						'594906',
						'594916',
						'594919',
						'594920',
						'594923',
						'594972',
						'594973',
						'594979',
						'594980',
						'594981',
						'594982',
						'694902',
						'694913',
						'694919',
						'694921',
						'694922',
						'694923',
						'694925',
						'694926',
						'694928',
						'694972',
						'694977',
						'694978',
						'694979',
						'694980',
						'694981',
						'694982'))
				$condicao_1
				$condicao_2
				$sql_adicional_peca
				$sql_adicional_peca2
				AND   tbl_faturamento.emissao > '2005-10-01'
				AND   tbl_faturamento.distribuidor IS NULL
				$and_agrupado";

		#d 253517 - Não gerar retorno para posto que não tenha linha informática
		#if(pg_num_rows($resi) > 0){
			$sql .= " AND 1 = 1 ";
		#}else{
		#	$sql .= " AND 1 = 2 ";
		#}
		$sql .= " GROUP BY tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_faturamento_item.devolucao_obrig,
					tbl_peca.produto_acabado,
					tbl_peca.ipi,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_faturamento.cfop,
					tbl_faturamento.serie,
					tbl_extrato_lgr.qtde,
					total_item,
					qtde_total_nf,
					qtde_pedente_temp,
					extrato_lgr
				ORDER BY tbl_peca.referencia";
//		echo nl2br($sql);
		$notas_fiscais=array();
		$qtde_peca=0;

		$resX = pg_query ($con,$sql);
		#echo nl2br($sql);
		#if (pg_num_rows ($resX)==0) {
		#	continue ;
		#}

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$total_valor_subs_trib  = 0;
		$total_base_subs_trib  = 0;
		$aliq_final       = 0;
		$peca_ant="";
		$qtde_acumulada=0;
		$lista_pecas = array();

		$z=0;
		$total_qtde = pg_num_rows ($resX);

				$cabecalho  = "<br><br>\n";
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

		$cabecalho .= "<tr align='left'  height='16'>\n";
		$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
		$cabecalho .= "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
		$cabecalho .= "</td>\n";
		$cabecalho .= "</tr>\n";

		#$c = "AAAA ICFOP23432423FCFOP EEEEE";
		# modify email addess and link with this:
		#$l="CORRETO";
		#$c=ereg_replace("ICFOP([?])*FCFOP",$l,$c);

		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
		$cabecalho .= "<td>CFOP <br> <b> (CFOP) </b> </td>\n";
		$cabecalho .= "<td>Emissão <br> <b>$data</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		if($login_fabrica == 3){
			$corBritania = " style='background-color:#ff0000;' ";
		}

		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
		$cabecalho .= "<td $corBritania >CNPJ <br> <b>$cnpj</b> </td>\n";
		$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
		$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
		$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
		$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$topo ="";
		$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
		$topo .=  "<thead>\n";

		if ($numero_linhas==5000){
			$topo .=  "<tr align='left'>\n";
			$topo .=  "<td bgcolor='#E3E4E6' colspan='4' style='font-size:18px'>\n";
			$topo .=  "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
			$topo .=  "</td>\n";
			$topo .=  "</tr>\n";
		}

		$topo .=  "<tr align='center'>\n";
		$topo .=  "<td><b>Código</b></td>\n";
		$topo .=  "<td><b>Descrição</b></td>\n";
		$topo .=  "<td><b>Qtde.</b></td>\n";

		if ($numero_linhas==5000){
			$topo .=  "<td><b>Qtde. Devolução</b></td>\n";
		}
		else{
			$topo .=  "<td><b>Preço</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			$topo .=  "<td><b>% IPI</b></td>\n";
		}
		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";



		for ($x = 0 ; $x < $total_qtde ; $x++) {

			$tem_mais_itens='sim';

			$contador++;
			$item_nota++;
			$z++;

			$peca                = pg_fetch_result ($resX,$x,peca);
			$peca_referencia     = pg_fetch_result ($resX,$x,referencia);
			$peca_descricao      = pg_fetch_result ($resX,$x,descricao);
			$peca_preco          = pg_fetch_result ($resX,$x,preco);
			$qtde_real           = pg_fetch_result ($resX,$x,qtde_real);
			$qtde_total_item     = pg_fetch_result ($resX,$x,qtde_total_item);
			$qtde_total_nf       = pg_fetch_result ($resX,$x,qtde_total_nf);
			$qtde_pedente_temp   = pg_fetch_result ($resX,$x,qtde_pedente_temp);
			$qtde_pedente_temp_AUX= pg_fetch_result ($resX,$x,qtde_pedente_temp);
//			$qtde_restatante     = pg_fetch_result ($resX,$x,qtde_restatante);
			$extrato_lgr         = pg_fetch_result ($resX,$x,extrato_lgr);
			$total_item          = pg_fetch_result ($resX,$x,total_item);
			$base_icms           = pg_fetch_result ($resX,$x,base_icms);
			$valor_icms          = pg_fetch_result ($resX,$x,valor_icms);
			$aliq_icms           = pg_fetch_result ($resX,$x,aliq_icms);
			$base_ipi            = pg_fetch_result ($resX,$x,base_ipi);
			$aliq_ipi            = pg_fetch_result ($resX,$x,aliq_ipi);
			$valor_ipi           = pg_fetch_result ($resX,$x,valor_ipi);
			$ipi                 = pg_fetch_result ($resX,$x,ipi);
			$cfop                = pg_fetch_result ($resX,$x,cfop);
			$serie               = pg_fetch_result ($resX,$x,serie);
			$peca_produto_acabado= pg_fetch_result ($resX,$x,produto_acabado);
			$peca_devolucao_obrigatoria= pg_fetch_result ($resX,$x,devolucao_obrigatoria);
			$base_subs_trib      = pg_fetch_result ($resX,$x,'base_subs_trib');
			$valor_subs_trib     = pg_fetch_result ($resX,$x,'valor_subs_trib');

			if(empty($base_subs_trib)) {
				$base_subs_trib = 0;
			}

			if(empty($valor_subs_trib)) {
				$valor_subs_trib = 0;
			}
			if ($qtde_pedente_temp>$qtde_real AND $numero_linhas!=5000){
				$qtde_pedente_temp=$qtde_real;
			}

			if ($peca_ant==$peca) {
				if ($numero_linhas==5000){
					$peca_ant=$peca;
					continue;
				}
				if ($peca_ok==1){
					$peca_ant=$peca;
					$contador--;
					$item_nota--;
					$z--;
					continue;
				}
			}

			if ($peca_ant!=$peca) {
				$qtde_acumulada = $qtde_real;
				$peca_ok = 0;
			} else {
				$qtde_acumulada += $qtde_real;
			}

			if ($qtde_acumulada >= $qtde_pedente_temp_AUX) {
				$qtde_real = $qtde_pedente_temp_AUX - ($qtde_acumulada - $qtde_real);
				$peca_ok = 1;
			}

			$peca_ant=$peca;

			if (strlen($qtde_pedente_temp)==0){
				$qtde_pedente_temp=$qtde_total_item;
			}

			array_push($lista_pecas,$peca);


			$total_item  = $peca_preco * $qtde_real;

			if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

			if ($aliq_icms==0) {
				$base_icms=0;
				$valor_icms=0;
			} else {
				$base_icms=$total_item;
				$valor_icms = $total_item * $aliq_icms / 100;
			}

			if ($peca_produto_acabado=='NOT TRUE') { # se for peca, IPI = 0
				$aliq_ipi=0;
			}

			if (strlen($aliq_ipi)==0) $aliq_ipi=0;

			if ($aliq_ipi==0) {
				$base_ipi=0;
				$valor_ipi=0;
			} else {
				$base_ipi=$total_item;
				$valor_ipi = $total_item*$aliq_ipi/100;
			}

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi   += $base_ipi;
			$total_valor_ipi  += $valor_ipi;
			$total_nota       += $total_item;
			$total_base_subs_trib  += $base_subs_trib;
			$total_valor_subs_trib  += $valor_subs_trib;

			if ($x == 0){
				if ($numero_linhas!=5000) {
					/* HD 40994 */
					$x_cabecalho = str_replace("(CFOP)",substr($cfop,0,4),$cabecalho);
					echo $x_cabecalho;
				}
				echo $topo;
			}

			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
			echo "<td align='left'>";
			echo "$peca_referencia";
			echo "<input type='hidden' name='id_item_LGR_$item_nota-$numero_nota' value='$extrato_lgr'>\n";
			echo "<input type='hidden' name='id_item_peca_$item_nota-$numero_nota' value='$peca'>\n";
			echo "<input type='hidden' name='id_item_preco_$item_nota-$numero_nota' value='$peca_preco'>\n";
			echo "<input type='hidden' name='id_item_qtde_$item_nota-$numero_nota' value='$qtde_real'>\n";
			echo "<input type='hidden' name='id_item_icms_$item_nota-$numero_nota' value='$aliq_icms'>\n";
			echo "<input type='hidden' name='id_item_ipi_$item_nota-$numero_nota' value='$aliq_ipi'>\n";
			echo "<input type='hidden' name='id_item_total_$item_nota-$numero_nota' value='$total_item'>\n";
			echo "</td>\n";
			echo "<td align='left'>$peca_descricao</td>\n";

			if ($numero_linhas==5000) {
				echo "<td align='center'>$qtde_total_item</td>\n";
				echo "<td align='center' bgcolor='#FAE7A5'>\n
						<input type='hidden' name='item_$contador' value='$extrato_lgr'>\n
						<input type='hidden' name='peca_tem_$contador' value='$qtde_total_item'>\n
						<input type='hidden' name='peca_$contador' value='$peca'>\n
						<input style='text-align:right' type='text' size='4' maxlength='4' name='$extrato_lgr' value='$qtde_pedente_temp' onblur='javascript:if (this.value > $qtde_total_item || this.value==\"\" ) {alert(\"Quantidade superior!\");this.value=\"$qtde_total_item\"}'>\n
						</td>\n";
			} else {

				echo "<td align='center'>$qtde_real</td>\n";
				echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
				echo "<td align='right'>$aliq_icms</td>\n";
				echo "<td align='right'>$aliq_ipi</td>\n";
			}
			echo "</tr>\n";

			if ($numero_linhas!=5000){
				if ($z%$numero_linhas==0 AND $z>0 AND ($x+1 < $total_qtde)){
					$total_geral=$total_nota+$total_valor_ipi;
					echo "</table>\n";
					echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
					echo "<tr>\n";
					echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
					echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
					echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
					echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";
					echo "<td>Base Subst. Trib. <br> <b> " . number_format ($total_base_subs_trib,2,",",".") . " </b> </td>";
					echo "<td>Valor Subst. Trib.<br> <b> " . number_format ($total_valor_subs_trib,2,",",".") . " </b> </td>";
					echo "<td>Total da Nota <br> <b> " . number_format ($total_geral+$total_valor_subs_trib,2,",",".") . " </b> </td>\n";
					echo "</tr>\n";

					if (count ($lista_pecas) >0){
						$notas_fiscais = array();
						$sql_nf = "SELECT tbl_faturamento.nota_fiscal
									FROM tbl_faturamento_item
									JOIN tbl_faturamento      USING (faturamento)
									WHERE tbl_faturamento.fabrica = $login_fabrica
									AND   tbl_faturamento.posto   = $login_posto
									AND   tbl_faturamento.extrato_devolucao = $extrato
									AND tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
									ORDER BY tbl_faturamento.nota_fiscal";
						$resNF = pg_query ($con,$sql_nf);
						for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
							array_push($notas_fiscais,pg_fetch_result ($resNF,$y,nota_fiscal));
						}
						$notas_fiscais = array_unique($notas_fiscais);

						if (count($notas_fiscais)>0){
							$bg_color = "";
							if ($login_fabrica == 3) {
								$bg_color = " style='background:yellow;font-weight:bold;padding-left:10px;font-size:15px;'";
							}
							echo "<tfoot>";
							echo "<tr>";
							echo "<td {$bg_color} colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
							echo "</tr>";
							$bg_color2 = "";
							if ($login_fabrica == 3) {
								$bg_color2 = " style='background:#FAE7A5;font-weight:bold;padding-left:10px;font-size:15px;'";
								echo "<tr>";
								echo "<td {$bg_color2} colspan='8'> Deve ser informado o número das NFs referentes (NF origem) em dados adicionais da sua nota fiscal.</td>";
								echo "</tr>";
							}

							echo "</tfoot>";
						}
					}
					$notas_fiscais=array();
					$lista_pecas = array();
					$qtde_peca="";
					echo "</table>\n";
					if (strlen ($nota_fiscal)==0) {
						echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
						echo "<tr>";
						echo "<td>";
						echo "\n<br>";
						echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-base_icms'  value='$total_base_icms'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi'   value='$total_base_ipi'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi'  value='$total_valor_ipi'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-cfop'       value='$cfop'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-movimento'  value='$movimento'>\n";
						echo "<center>";
						echo "<b>Preencha este Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
						if ($msg_erro){
							$nota_fiscal_a = $_POST['nota_fiscal_'.$numero_nota];
						}
						$num_nf = ($login_fabrica == 3) ? 10 : 6;
						echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='$num_nf' value='$nota_fiscal_a'>";
						if($login_fabrica == 3){
							echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Imagem da Nota <input type='file' name='nota_$numero_nota' class='frm'>";
						}
						echo "<br><br>";
						echo "</td>";
						echo "</tr>";
						echo "</table>";
						$numero_nota++;

					}else{
						if (strlen ($nota_fiscal) >0){
							echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
							echo "<tr>\n";
							echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
							echo "</tr>";
							echo "</table>";
						}
					}
					/* HD 40994 */
					$x_cabecalho = str_replace("(CFOP)",substr($cfop,0,4),$cabecalho);
					echo $x_cabecalho;
					echo $topo;

					$total_base_icms  = 0;
					$total_valor_icms = 0;
					$total_base_ipi   = 0;
					$total_valor_ipi  = 0;
					$total_nota       = 0;
					$total_base_subs_trib  = 0;
					$total_valor_subs_trib = 0;
					$item_nota=0;
				}
			}
			flush();

		}

		if (count ($lista_pecas) >0){
			$notas_fiscais = array();
			$sql_nf = "SELECT tbl_faturamento.nota_fiscal,
							  tbl_faturamento.serie
						FROM tbl_faturamento_item
						JOIN tbl_faturamento      USING (faturamento)
						WHERE tbl_faturamento.fabrica = $login_fabrica
						AND   tbl_faturamento.posto   = $login_posto
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
						ORDER BY tbl_faturamento.nota_fiscal";
			$resNF = pg_query ($con,$sql_nf);
			for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
				array_push($notas_fiscais,pg_fetch_result ($resNF,$y,nota_fiscal).' - Serie: '.pg_fetch_result ($resNF,$y,serie));
			}
			$notas_fiscais = array_unique($notas_fiscais);

			if (count($notas_fiscais)>0){
				$bg_color = "";
				if ($login_fabrica == 3) {
					$bg_color = " style='background:yellow;font-weight:bold;padding-left:10px;font-size:15px;'";
				}
				echo "<tfoot>";
				echo "<tr>";
				echo "<td colspan='8' {$bg_color}> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
				echo "</tr>";
				$bg_color2 = "";
				if ($login_fabrica == 3) {
					$bg_color2 = " style='background:#FAE7A5;font-weight:bold;padding-left:10px;font-size:15px;'";
					echo "<tr>";
					echo "<td {$bg_color2} colspan='8'> Deve ser informado o número das NFs referentes (NF origem) em dados adicionais da sua nota fiscal.</td>";
					echo "</tr>";
				}
				echo "</tfoot>";
			}
		}

		echo "</table>\n";

//		$total_valor_icms = $total_base_icms * $aliq_final / 100;

		if ($numero_linhas!=5000 and ($total_qtde > 0)) {
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
			echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
			echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
			echo "<td>Base Subst. Trib. <br> <b> " . number_format ($total_base_subs_trib,2,",",".") . " </b> </td>";
			echo "<td>Valor Subst. Trib.<br> <b> " . number_format ($total_valor_subs_trib,2,",",".") . " </b> </td>";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi+$total_valor_subs_trib,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";
		}

		if ($numero_linhas!=5000 AND strlen ($nota_fiscal) == 0 and ($total_qtde > 0)) {

			$total_geral=$total_nota+$total_valor_ipi;
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>";
			echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-base_icms' value='$total_base_icms'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi' value='$total_base_ipi'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi' value='$total_valor_ipi'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-cfop'      value='$cfop'>\n";
			echo "<center>";
			echo "<b>Preencha este Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
			if ($msg_erro){
				$nota_fiscal_b = $_POST['nota_fiscal_'.$numero_nota];
			}
			$num_nf = ($login_fabrica == 3) ? 10 : 6;
			echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='$num_nf' value='$nota_fiscal_b'>";
			if($login_fabrica == 3){
				echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Imagem da Nota <input type='file' name='nota_$numero_nota' class='frm'>";
			}
			echo "<input type='hidden' name='serie_$numero_nota' value='$serie'>\n";
			echo "</td>";
			echo "</tr>";
			echo "</table>";

			$item_nota=0;
			$numero_nota++;
		}else{
			if (strlen ($nota_fiscal)>0){
				echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				echo "<tr>\n";
				echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
				echo "</tr>";
				echo "</table>";
			}
		}
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$total_base_subs_trib  = 0;
		$total_valor_subs_trib = 0;
	}

## PEÇAS RETORNAVEIS DA TELECONTROL ##

	if ($numero_linhas!=5000 AND $jah_digitado==0){
		$sql = "SELECT  tbl_faturamento.faturamento,
						tbl_peca.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.ipi,
						tbl_faturamento_item.aliq_icms,
						tbl_faturamento_item.aliq_ipi,
						tbl_faturamento.cfop,
						SUM (tbl_faturamento_item.qtde) AS qtde,
						SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco ) AS total_item,
						SUM (tbl_faturamento_item.base_icms) AS base_icms,
						SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
						SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi,
						SUM (tbl_faturamento_item.base_ipi) AS base_ipi
				FROM tbl_peca
				JOIN tbl_faturamento_item USING (peca)
				JOIN tbl_faturamento      USING (faturamento)
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.posto   = $login_posto
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
				AND   tbl_faturamento.distribuidor=4311
				AND   tbl_faturamento_item.aliq_icms > 0
				AND   tbl_faturamento.emissao > '2005-10-01'
				GROUP BY tbl_faturamento.faturamento, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento_item.aliq_ipi
				ORDER BY tbl_peca.referencia ";

		$sql = "SELECT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ipi,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				tbl_faturamento_item.devolucao_obrig as devolucao_obrigatoria,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco,
				sum(tbl_faturamento_item.qtde) as qtde,
				sum(tbl_faturamento_item.qtde)*tbl_faturamento_item.preco as total_item,
				tbl_faturamento.cfop,
				SUM (tbl_faturamento_item.base_icms) AS base_icms,
				SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
				SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
				SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
				FROM tbl_peca
				JOIN tbl_faturamento_item USING (peca)
				JOIN tbl_faturamento      USING (faturamento)
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   tbl_faturamento.posto=$login_posto
				AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
				AND   tbl_faturamento.distribuidor=4311
				AND   tbl_peca.fabrica = $login_fabrica
				AND   tbl_faturamento_item.aliq_icms > 0
				AND   tbl_faturamento.emissao > '2005-10-01' ";

		#Ignora os postos abaixo - não mostra as peças
		if ($extrato > 240000 AND array_search($login_posto, $postos_permitidos_novo_processo)>0){
			$sql .= " AND tbl_peca.produto_acabado IS TRUE ";
		}

		$sql .= " GROUP BY tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					tbl_faturamento_item.devolucao_obrig,
					tbl_peca.produto_acabado,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_faturamento.cfop
				ORDER BY produto_acabado DESC , devolucao_obrigatoria DESC";

		$resX = pg_query ($con,$sql);
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;
		$total_base_subs_trib  = 0;
		$total_valor_subs_trib = 0;

		$distribuidor=4311;
		$notas_fiscais=0;
		$numero_nota_tc = 0;

		//echo nl2br($sql);
		if ( pg_num_rows ($resX)>0){

			if (strlen ($distribuidor) > 0) {
				$sql_2  = "SELECT * FROM tbl_posto WHERE posto = $distribuidor";
				$resY = pg_query ($con,$sql_2);

				$estado   = pg_fetch_result ($resY,0, 'estado');
				$razao    = pg_fetch_result ($resY,0, 'nome');
				$endereco = trim(pg_fetch_result ($resY,0, 'endereco')) . " " . trim(pg_fetch_result ($resY,0, 'numero'));
				$cidade   = pg_fetch_result ($resY,0, 'cidade');
				$estado   = pg_fetch_result ($resY,0, 'estado');
				$cep      = pg_fetch_result ($resY,0, 'cep');
				$fone     = pg_fetch_result ($resY,0, 'fone');
				$cnpj     = pg_fetch_result ($resY,0, 'cnpj');
				$ie       = pg_fetch_result ($resY,0, 'ie');

				$condicao_1 = " tbl_faturamento.distribuidor = $distribuidor ";
				$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
			}
			$cfop      = pg_fetch_result ($resX,0,cfop);

			$cabecalho  = "<br><br>\n";
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

			$cabecalho .= "<tr align='left'  height='16'>\n";
			$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			$cabecalho .= "<b>DEVOLUÇÃO TELECONTROL&nbsp;</b><br>\n";
			$cabecalho .= "</td>\n";
			$cabecalho .= "</tr>\n";

			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>aNatureza <br> <b>Devolução de Garantia</b> </td>\n";
			$cabecalho .= "<td>CFOP <br> <b>$cfop</b> </td>\n";
			$cabecalho .= "<td>Emissão <br> <b>$data</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
			$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
			$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
			$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
			$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
			$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$topo ="";
			$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
			$topo .=  "<thead>\n";

			$topo .=  "<tr align='center'>\n";
			$topo .=  "<td><b>Código</b></td>\n";
			$topo .=  "<td><b>Descrição</b></td>\n";
			$topo .=  "<td><b>Qtde.</b></td>\n";
			$topo .=  "<td><b>Preço</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			$topo .=  "<td><b>% IPI</b></td>\n";
			$topo .=  "</tr>\n";
			$topo .=  "</thead>\n";

			echo $cabecalho;
			echo $topo;

			for ($x = 0 ; $x < pg_num_rows ($resX) ; $x++) {
				$peca        = pg_fetch_result ($resX,$x,peca);
				$qtde        = pg_fetch_result ($resX,$x,qtde);
				$total_item  = pg_fetch_result ($resX,$x,total_item);
				$base_icms   = pg_fetch_result ($resX,$x,base_icms);
				$valor_icms  = pg_fetch_result ($resX,$x,valor_icms);
				$aliq_icms   = pg_fetch_result ($resX,$x,aliq_icms);
				$base_ipi    = pg_fetch_result ($resX,$x,base_ipi);
				$aliq_ipi    = pg_fetch_result ($resX,$x,aliq_ipi);
				$valor_ipi   = pg_fetch_result ($resX,$x,valor_ipi);
				$ipi         = pg_fetch_result ($resX,$x,ipi);
				$preco       = round ($total_item / $qtde,2);

				if (strlen ($base_icms)  == 0) $base_icms = $total_item ;
				if (strlen ($valor_icms) == 0) $valor_icms = round ($total_item * $aliq_icms / 100,2);

				if (strlen($aliq_ipi)==0) $aliq_ipi=0;
				if ($aliq_ipi==0) 	{
					$base_ipi=0;
					$valor_ipi=0;
				}
				else {
					$base_ipi=$total_item;
					$valor_ipi = $total_item*$aliq_ipi/100;
				}

				if ($base_icms > $total_item) $base_icms = $total_item;
				if ($aliq_final == 0) $aliq_final = $aliq_icms;
				if ($aliq_final <> $aliq_icms) $aliq_final = -1;

				if ($x%$numero_linhas==0 AND $x>0){
					$sql_nf = "SELECT DISTINCT tbl_faturamento.nota_fiscal
							FROM tbl_faturamento_item
							JOIN tbl_faturamento      USING (faturamento)
							JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
							WHERE tbl_faturamento.fabrica = $login_fabrica
							AND   tbl_faturamento.posto   = $login_posto
							AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
							AND   tbl_faturamento.extrato_devolucao = $extrato
							AND   tbl_faturamento.distribuidor=4311
							AND   tbl_faturamento_item.aliq_icms > 0";
					$resZ = pg_query ($con,$sql_nf);
					$notas_fiscais    = array();
					for ($y = 0 ; $y < pg_num_rows ($resZ) ; $y++) {
						array_push($notas_fiscais,pg_fetch_result ($resZ,$y,nota_fiscal));
					}
					if (count($notas_fiscais)>0){

						$bg_color = "";
						if ($login_fabrica == 3) {
							$bg_color = " style='background:yellow;font-weight:bold;padding-left:10px;font-size:15px;'";
						}
						echo "<tfoot>";
						echo "<tr>";
						echo "<td {$bg_color } colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
						echo "</tr>";
						$bg_color2 = "";
						if ($login_fabrica == 3) {
							$bg_color2 = " style='background:#FAE7A5;font-weight:bold;padding-left:10px;font-size:15px;'";
							echo "<tr>";
							echo "<td {$bg_color2} colspan='8'> Deve ser informado o número das NFs referentes (NF origem) em dados adicionais da sua nota fiscal.</td>";
							echo "</tr>";
						}
						echo "</tfoot>";
					}
					//$total_valor_icms = $total_base_icms * $aliq_final / 100;
					$total_geral=$total_nota+$total_valor_ipi;
					echo "</table>\n";
					echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
					echo "<tr>\n";

					echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-qtde_itens' value='$item_nota'>\n";
					echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-total_nota' value='$total_geral'>\n";
					echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-base_icms'  value='$total_base_icms'>\n";
					echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-valor_icms' value='$total_valor_icms'>\n";
					echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-base_ipi'   value='$total_base_ipi'>\n";
					echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-valor_ipi'  value='$total_valor_ipi'>\n";
					echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-valor_ipi'  value='$total_valor_ipi'>\n";
					echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-cfop'       value='$cfop'>\n";

					echo "<td>Base ICMS<br> <b> "      . number_format ($total_base_icms,2,",",".")  . " </b> </td>\n";
					echo "<td>Valor ICMS <br> <b> "    . number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
					echo "<td>Base IPI <br> <b> "      . number_format ($total_base_ipi,2,",",".")   . " </b> </td>\n";
					echo "<td>Valor IPI <br> <b> "     . number_format ($total_valor_ipi,2,",",".")  . " </b> </td>\n";
					echo "<td>Total da Nota <br> <b> " . number_format ($total_geral,2,",",".")      . " </b> </td>\n";
					echo "</tr>\n";

					echo "</table>\n";

					echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
					echo "<tr>";
					echo "<td>";
					echo "\n<br>";
					echo "<center>";
					echo "<b>Preencha este Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
					if ($msg_erro){
						echo "WOHO";
					}
					$num_nf = ($login_fabrica == 3) ? 10 : 6;
					echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_tc_$numero_nota_tc' size='10' maxlength='$num_nf' value='$nota_fiscal'>";
					if($login_fabrica == 3){
							echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Imagem da Nota <input type='file' name='nota_$numero_nota' class='frm'>";
						}
					echo "<br><br>";
					echo "</td>";
					echo "</tr>";
					echo "</table>";

					echo $cabecalho;
					echo $topo;

					$total_base_icms  = 0;
					$total_valor_icms = 0;
					$total_base_ipi   = 0;
					$total_valor_ipi  = 0;
					$total_nota       = 0;
					$total_base_subs_trib  = 0;
					$total_valor_subs_trib = 0;

					$item_nota=0;
					$numero_nota_tc++;
				}

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";

				echo "<input type='hidden' name='id_item_tc_peca_$item_nota-$numero_nota_tc'  value='$peca'>\n";
				echo "<input type='hidden' name='id_item_tc_preco_$item_nota-$numero_nota_tc' value='$preco'>\n";
				echo "<input type='hidden' name='id_item_tc_icms_$item_nota-$numero_nota_tc'  value='$aliq_icms'>\n";
				echo "<input type='hidden' name='id_item_tc_ipi_$item_nota-$numero_nota_tc'   value='$aliq_ipi'>\n";
				echo "<input type='hidden' name='id_item_tc_total_$item_nota-$numero_nota_tc' value='$total_item'>\n";

				echo "<td align='left'>" . pg_fetch_result ($resX,$x,referencia) . "</td>";
				echo "<td align='left'>" . pg_fetch_result ($resX,$x,descricao) . "</td>";
				echo "<td align='right'>" . pg_fetch_result ($resX,$x,qtde) . "</td>";
				echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>";
				echo "<td align='right'>" . $aliq_icms . "</td>";
				echo "<td align='right'>" . $aliq_ipi. "</td>";
				echo "</tr>";

				$total_base_icms  += $base_icms;
				$total_valor_icms += $valor_icms;
				$total_base_ipi   += $base_ipi;
				$total_valor_ipi  += $valor_ipi;
				$total_nota       += $total_item;
				$item_nota++;
			}

			$sql_nf = "SELECT DISTINCT tbl_faturamento.nota_fiscal
					FROM tbl_faturamento_item
					JOIN tbl_faturamento      USING (faturamento)
					JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.posto   = $login_posto
					AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   tbl_faturamento.distribuidor=4311
					AND   tbl_faturamento_item.aliq_icms > 0";
			$resZ = pg_query ($con,$sql_nf);
			$notas_fiscais    = array();
			for ($x = 0 ; $x < pg_num_rows ($resZ) ; $x++) {
				array_push($notas_fiscais,pg_fetch_result ($resZ,$x,nota_fiscal));
			}
			if (count($notas_fiscais)>0){
				$bg_color = "";
				if ($login_fabrica == 3) {
					$bg_color = " style='background:yellow;font-weight:bold;padding-left:10px;font-size:15px;'";
				}
				echo "<tfoot>";
				echo "<tr>";
				echo "<td {$bg_color} colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
				echo "</tr>";
				$bg_color2 = "";
				if ($login_fabrica == 3) {
					$bg_color2 = " style='background:#FAE7A5;font-weight:bold;padding-left:10px;font-size:15px;'";
					echo "<tr>";
					echo "<td {$bg_color2} colspan='8'> Deve ser informado o número das NFs referentes (NF origem) em dados adicionais da sua nota fiscal.</td>";
					echo "</tr>";
				}
				echo "</tfoot>";
			}
			//$total_valor_icms = $total_base_icms * $aliq_final / 100;
			$total_geral=$total_nota+$total_valor_ipi;
			echo "</table>\n";
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			echo "<tr>\n";

			echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-qtde_itens' value='$item_nota'>\n";
			echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-total_nota' value='$total_geral'>\n";
			echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-base_icms'  value='$total_base_icms'>\n";
			echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-valor_icms' value='$total_valor_icms'>\n";
			echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-base_ipi'   value='$total_base_ipi'>\n";
			echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-valor_ipi'  value='$total_valor_ipi'>\n";
			echo "<input type='hidden' name='id_nota_tc_$numero_nota_tc-cfop'       value='$cfop'>\n";

			echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
			echo "<td>Valor ICMS <br> <b> ". number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
			echo "<td>Base IPI <br> <b> "  . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_geral,2,",",".") . " </b> </td>\n";
			echo "</tr>\n";

			echo "</table>\n";

			$total_base_icms  = 0;
			$total_valor_icms = 0;
			$total_base_ipi   = 0;
			$total_valor_ipi  = 0;
			$total_nota       = 0;

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>";
			echo "\n<br>";
			echo "<center>";
			echo "<b>Preencha este Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
			$num_nf = ($login_fabrica == 3) ? 10 : 6;
			echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_tc_$numero_nota_tc' size='10' maxlength='$num_nf' value='$nota_fiscal'>";
			if($login_fabrica == 3){
				echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Imagem da Nota <input type='file' name='nota_$numero_nota' class='frm'>";
			}
			echo "<br><br>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			$numero_nota_tc++;
			$item_nota=0;
		}
	}

	### Produtos Ressarcidos ###
	/*HD: 126697 - Quando o posto não tem faturamento, não estrava na impressão da nota de ressarcimento*/
	if ($numero_linhas<>5000 or $res_qtde ==0 ){
		#Tirei as partes de faturamento - Fabio - 31-03-2008
		$sql = "SELECT  DISTINCT
						tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_ressarcimento,
						tbl_produto.produto                          AS produto           ,
						tbl_produto.referencia                       AS produto_referencia,
						tbl_produto.descricao                        AS produto_descricao ,
						tbl_admin.login
			FROM tbl_os
			JOIN tbl_os_extra   USING(os)
			JOIN tbl_extrato    ON tbl_extrato.fabrica = tbl_os.fabrica and tbl_extrato.posto = tbl_os.posto
			LEFT JOIN tbl_admin            ON tbl_os.troca_garantia_admin = tbl_admin.admin
			LEFT JOIN tbl_produto          ON tbl_os.produto              = tbl_produto.produto
			LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.os     = tbl_os.os and tbl_faturamento_item.pedido isnull
			LEFT JOIN tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento and tbl_faturamento.distribuidor = $login_posto
			WHERE to_char(tbl_os.finalizada,'MMYYYY') = to_char((tbl_extrato.data_geracao - interval '1 month'),'MMYYYY')
			AND  tbl_extrato.extrato   = $extrato
			AND  tbl_os.fabrica        = $login_fabrica
			AND  tbl_os.posto          = $login_posto
			AND  tbl_os.ressarcimento   IS TRUE
			AND  (tbl_faturamento_item.faturamento_item IS NULL)
			AND  tbl_os.troca_garantia  IS TRUE
			";
		$resX = pg_query ($con,$sql);
		$qtde_produtos_ressarcimento = pg_num_rows ($resX);
		if($qtde_produtos_ressarcimento>0){

			//HD43448
			if(strtotime($data_geracao_extrato) >= strtotime("2017-03-01")){

				$razao    = "BRITANIA ELETRONICOS SA";
				$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
				$cidade   = "Joinville";
				$estado   = "SC";
				$cep      = "89239-270";
				$fone     = "(41) 2102-7700";
				$cnpj     = "07019308000128";
				$ie       = "254.861.660";

			}else{

				$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
				$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
				$cidade   = "Joinville";
				$estado   = "SC";
				$cep      = "89239270";
				$fone     = "(41) 2102-7700";
				$cnpj     = "76492701000742";
				$ie       = "254.861.652";

			}

			$natureza_operacao = "Simples Remessa";

			# HD 13354
			$cfop = "6949";
			$sql = "SELECT contato_estado
					FROM tbl_posto_fabrica
					WHERE fabrica = $login_fabrica
					AND posto = $login_posto";
			$resW = pg_query ($con,$sql);
			if (pg_num_rows ($resW)>0){
				$estado_posto = strtoupper(trim(pg_fetch_result($resW,0,contato_estado)));
				if ($estado_posto=='SC'){
					$cfop = "5949";
				}
			}

			echo "<input type='hidden' name='ressarcimento' value='$extrato'>\n";

			echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";

			echo "<tr align='left'  height='16'>\n";
			echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			echo "<b>&nbsp;<b>RESSARCIMENTO FINANCEIRO </b><br>\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>";
			echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
			echo "<td>CFOP <br> <b>$cfop</b> </td>";
			echo "<td>Emissão <br> <b>$data</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Razão Social <br> <b>$razao</b> </td>";
			echo "<td $corBritania >CNPJ <br> <b>$cnpj</b> </td>";
			echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
			echo "</tr>";
			echo "</table>";


			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Endereço <br> <b>$endereco </b> </td>";
			echo "<td>Cidade <br> <b>$cidade</b> </td>";
			echo "<td>Estado <br> <b>$estado</b> </td>";
			echo "<td>CEP <br> <b>$cep</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr align='center' style='font-weight:bold'>";
			echo "<td>Código</td>";
			echo "<td>Descrição</td>";
			echo "<td>Ressarcimento</td>";
			echo "<td>Responsavel</td>";
			echo "<td>OS</td>";
			echo "</tr>";

			for ($x = 0 ; $x < $qtde_produtos_ressarcimento ; $x++) {

				$os                 = pg_fetch_result ($resX,$x,os);
				$sua_os             = pg_fetch_result ($resX,$x,sua_os);
				$produto            = pg_fetch_result ($resX,$x,produto);
				$produto_referencia = pg_fetch_result ($resX,$x,produto_referencia);
				$produto_descricao  = pg_fetch_result ($resX,$x,produto_descricao);
				$data_ressarcimento = pg_fetch_result ($resX,$x,data_ressarcimento);
				$quem_trocou        = pg_fetch_result ($resX,$x,login);

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
				echo "<input type='hidden' name='ressarcimento_produto_".$x."' value='$produto'>";
				echo "<input type='hidden' name='ressarcimento_os_".$x."' value='$os'>";
				echo "<td align='left'>$produto_referencia</td>";
				echo "<td align='left'>$produto_descricao</td>";
				echo "<td align='left'>$data_ressarcimento</td>";
				echo "<td align='right'>$quem_trocou</td>";
				echo "<td align='right'>$sua_os</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<input type='hidden' name='qtde_produtos_ressarcimento' value='$qtde_produtos_ressarcimento'>";
			echo "<input type='hidden' name='ressarcimento_natureza' value='$natureza_operacao'>";
			echo "<input type='hidden' name='ressarcimento_cfop' value='$cfop'>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>";
			echo "<center>";
			echo "<b>Preencha este Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";

			echo "<br><div style='margin:0 auto; text-align:center;color:#D90000;font-weight:bold'>* Para o preenchimento da Nota Fiscal de Simples Remessa, utilizar o mesmo valor da Nota Fiscal de compra do consumidor</div>";
			$num_nf = ($login_fabrica == 3) ? 10 : 6;
			echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='ressarcimento_nota_fiscal' size='10' maxlength='$num_nf' value='$nota_fiscal'>";
			if($login_fabrica == 3){
				echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Imagem da Nota <input type='file' name='ressarcimento_nota_fiscal_img' class='frm'>";
			}
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}
	}


	### Produtos Trocado enviado DIRETO ao CONSUMIDOR ###
	# HD 13316
	if ($numero_linhas<>5000){
		#hd 253517
		#$sqli = " SELECT linha FROM tbl_posto_linha
		#			WHERE posto=$login_posto
		#			AND   linha =528";
		//echo $sqli;
		#$resi = pg_query($con,$sqli);
		$sql = "SELECT  tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
						tbl_produto.produto                          AS produto           ,
						tbl_produto.referencia                       AS produto_referencia,
						tbl_produto.descricao                        AS produto_descricao ,
						tbl_admin.login
			FROM tbl_os
			JOIN tbl_os_extra   ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_os_troca   ON tbl_os_troca.os = tbl_os.os
			LEFT JOIN tbl_admin            ON tbl_os.troca_garantia_admin = tbl_admin.admin
			LEFT JOIN tbl_produto          ON tbl_os.produto              = tbl_produto.produto
			LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.os     = tbl_os.os
			LEFT JOIN tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			WHERE tbl_os_extra.extrato = $extrato
			AND  tbl_os.fabrica        = $login_fabrica
			AND  tbl_os.posto          = $login_posto
			AND  tbl_os.ressarcimento  IS NOT TRUE
			AND  tbl_os_troca.envio_consumidor IS TRUE
			AND  (tbl_faturamento_item.faturamento_item IS NULL OR (tbl_faturamento.cancelada IS NOT NULL AND tbl_faturamento.distribuidor = $login_posto)) ";
		#d 253517 - Não gerar retorno para posto que não tenha linha informática
		#if(pg_num_rows($resi) > 0){
			$sql .= " AND 1 = 1 ";
		#}else{
		#	$sql .= " AND 1 = 2 ";
		#}
		//echo $sql; exit;
		$resX = pg_query ($con,$sql);
		$qtde_produtos_envio_consumidor = pg_num_rows ($resX);
		if($qtde_produtos_envio_consumidor>0){

			//HD43448
			if(strtotime($data_geracao_extrato) >= strtotime("2017-03-01")){

				$razao    = "BRITANIA ELETRONICOS SA";
				$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
				$cidade   = "Joinville";
				$estado   = "SC";
				$cep      = "89239-270";
				$fone     = "(41) 2102-7700";
				$cnpj     = "07019308000128";
				$ie       = "254.861.660";

			}else{

				$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
				$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
				$cidade   = "Joinville";
				$estado   = "SC";
				$cep      = "89239270";
				$fone     = "(41) 2102-7700";
				$cnpj     = "76492701000742";
				$ie       = "254.861.652";

			}

			$natureza_operacao = "Simples Remessa";

			# HD 13354
			$cfop = "6949";
			$sql = "SELECT contato_estado
					FROM tbl_posto_fabrica
					WHERE fabrica = $login_fabrica
					AND posto = $login_posto";
			$resW = pg_query ($con,$sql);
			if (pg_num_rows ($resW)>0){
				$estado_posto = strtoupper(trim(pg_fetch_result($resW,0,contato_estado)));
				if ($estado_posto=='SC'){
					$cfop = "5949";
				}
			}

			echo "<input type='hidden' name='envio_consumidor' value='$extrato'>\n";

			echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";

			echo "<tr align='left'  height='16'>\n";
			echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			echo "<b>&nbsp;<b>PRODUTOS - RETORNO OBRIGATÓRIO</b><br>\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>";
			echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
			echo "<td>CFOP <br> <b>$cfop</b> </td>";
			echo "<td>Emissão <br> <b>$data</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Razão Social <br> <b>$razao</b> </td>";
			echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
			echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
			echo "</tr>";
			echo "</table>";


			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Endereço <br> <b>$endereco </b> </td>";
			echo "<td>Cidade <br> <b>$cidade</b> </td>";
			echo "<td>Estado <br> <b>$estado</b> </td>";
			echo "<td>CEP <br> <b>$cep</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr align='center'>";
			echo "<td><b>Código</b></td>";
			echo "<td><b>Descrição</b></td>";
			echo "<td><b>Data</b></td>";
			echo "<td><b>Responsavel</b></td>";
			echo "<td><b>OS</b></td>";
			echo "</tr>";

			for ($x = 0 ; $x < $qtde_produtos_envio_consumidor ; $x++) {

				$os                 = pg_fetch_result ($resX,$x,os);
				$sua_os             = pg_fetch_result ($resX,$x,sua_os);
				$produto            = pg_fetch_result ($resX,$x,produto);
				$produto_referencia = pg_fetch_result ($resX,$x,produto_referencia);
				$produto_descricao  = pg_fetch_result ($resX,$x,produto_descricao);
				$data_fechamento    = pg_fetch_result ($resX,$x,data_fechamento);
				$quem_trocou        = pg_fetch_result ($resX,$x,login);

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
				echo "<input type='hidden' name='envio_consumidor_produto_".$x."' value='$produto'>";
				echo "<input type='hidden' name='envio_consumidor_os_".$x."' value='$os'>";
				echo "<td align='left'>$produto_referencia</td>";
				echo "<td align='left'>$produto_descricao</td>";
				echo "<td align='left'>$data_fechamento</td>";
				echo "<td align='right'>$quem_trocou</td>";
				echo "<td align='right'>$sua_os</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<input type='hidden' name='qtde_produtos_envio_consumidor' value='$qtde_produtos_envio_consumidor'>";
			echo "<input type='hidden' name='envio_consumidor_natureza' value='$natureza_operacao'>";
			echo "<input type='hidden' name='envio_consumidor_cfop' value='$cfop'>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>";
			echo "<center>";
			echo "<b>Preencha este Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";

			echo "<br><div style='margin:0 auto; text-align:center;color:#D90000;font-weight:bold'>* Para o preenchimento da Nota Fiscal de Simples Remessa, utilizar o mesmo valor da Nota Fiscal de compra do consumidor</div>";
			$num_nf = ($login_fabrica == 3) ? 10 : 6;
			echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='envio_consumidor_nota_fiscal' size='10' maxlength='$num_nf' value='$nota_fiscal'>";
			if($login_fabrica == 3){
				echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Imagem da Nota <input type='file' name='nota_$numero_nota' class='frm'>";
			}
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}
	}

	if($numero_linhas==5000) {
		# HD 145909
		#hd 253517
		#$sqli = " SELECT linha FROM tbl_posto_linha
		#			WHERE posto=$login_posto
		#			AND   linha =528";
		//			echo $sqli;
		#$resi = pg_query($con,$sqli);

		$sql_res = " SELECT tbl_os.os
						FROM tbl_os
						JOIN tbl_os_extra   USING(os)
						JOIN tbl_extrato    ON tbl_extrato.fabrica = tbl_os.fabrica and tbl_extrato.posto = tbl_os.posto
						WHERE to_char(tbl_os.finalizada,'MMYYYY') = to_char((tbl_extrato.data_geracao - interval '1 month'),'MMYYYY')
						AND  tbl_os.fabrica        = $login_fabrica
						AND  tbl_os.posto          = $login_posto
						AND  tbl_extrato.extrato   = $extrato
						AND  tbl_os.ressarcimento   IS TRUE
						AND  tbl_os.troca_garantia  IS TRUE";
		#d 253517 - Não gerar retorno para posto que não tenha linha informática
		#if(pg_num_rows($resi) > 0){
			$sql_res .= " AND 1 = 1 ";
		#}else{
		#	$sql_res .= " AND 1 = 2 ";
		#}
		//echo $sql_res;
		$res_res = pg_query($con,$sql_res);
		if(pg_num_rows($res_res) > 0){
			$ressarcimento_qtde = 1;
			$os = pg_fetch_result($res_res,0,'os');
			$sql_re = " SELECT tbl_faturamento_item.os
						FROM tbl_faturamento
						JOIN tbl_faturamento_item USING(faturamento)
						WHERE os = $os
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND   tbl_faturamento.distribuidor = $login_posto
						AND   tbl_faturamento.fabrica = $login_fabrica";
			$res_re = pg_query($con,$sql_re);
			if(pg_num_rows($res_re) == 0){
				$tem_mais_itens = 'sim';
			}
		}
	}

	/*HD: 126697 - Quando o posto não tem faturamento, não estrava na impressão da nota de ressarcimento*/
	if ($numero_linhas==5000 ){
		if(($res_qtde >0 or $ressarcimento_qtde > 0)) {
			if ($tem_mais_itens=='nao' AND $jah_digitado_tc>0){
				if($login_fabrica == 3){
					#echo "<b>Não há mais peças para devolução.<br><br>";
					#HD 311290
					if($res_qtde == 0 and $qtde_produtos_ressarcimento>0) {
						echo "<br><br><br>
							<input type='hidden' name='qtde_linha' value='$numero_linhas'>
							<input type='hidden' name='numero_de_notas' value='$numero_nota'>
							<input type='hidden' name='numero_de_notas_tc' value='$numero_nota_tc'>

							<b>Preencha TODAS as notas acima e clique no botão abaixo para confirmar!</b>
							<br><br>
							<input type='button' value='Confirmar notas de devolução' name='gravar' onclick=\"javascript:
								if (document.frm_devolucao.botao_acao.value=='digitou_as_notas') {
									alert('Aguarde Submissão');
								}else{
									if(confirm('Deseja continuar? Prezado posto, pedimos que seja averiguado a(s) quantidade(s) de produto(s) ou peça(s), valor(es), nº de nota, pois após a sua confirmação não será possível cancelar o registro da nota fiscal.')){
										if (verificar('frm_devolucao')){
											document.frm_devolucao.botao_acao.value='digitou_as_notas';
											document.frm_devolucao.submit();
										}
									}
								}
								\">
							<br>";
					}

				}else{
					echo "<b>Não há mais peças para devolução.<br><br>";
				}
				echo "<a href='extrato_posto_devolucao_lgr_itens.php?extrato=$extrato'>Clique aqui para consultar as notas de devolução</a></b>";
			}else{
				if ($pecas_pendentes=='sim'){
					echo "<input type='hidden' name='pendentes' value='sim'>";
				}

				echo "<br>
						<input type='hidden' name='qtde_pecas' value='$contador'>
						<IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>
						<b style='font-size:12px'>


						<b>Informar a quantidade de linhas no formulário de Nota Fiscal do Posto Autorizado:</b>
						<input type='text' size='5' maxlength='3' value='' name='qtde_linha'><br>
						Essa informação definirá a quantidade de NFs que o posto autorizado deverá emitir e enviar
						<br><br>
						<input type='button' id='fechar' value='Gerar Nota Fiscal de Devolução' name='gravar' onclick=\"javascript:
						if(document.frm_devolucao.qtde_linha.value=='' || document.frm_devolucao.qtde_linha.value=='0')
								alert('Informe a quantidade de itens!!');
						else{
							if (document.frm_devolucao.botao_acao.value=='digitou_qtde'){
								alert('Aguarde submissão');
							}
							else{
								document.frm_devolucao.botao_acao.value='digitou_qtde';
								this.form.submit();
							}
						} \"><br><br> ";
			}
		}
	}else{
		if($login_fabrica == 3){
			echo "<br><br><br>
					<input type='hidden' name='qtde_linha' value='$numero_linhas'>
					<input type='hidden' name='numero_de_notas' value='$numero_nota'>
					<input type='hidden' name='numero_de_notas_tc' value='$numero_nota_tc'>

					<b>Preencha TODAS as notas acima e clique no botão abaixo para confirmar!</b>
					<br><br>
					<input type='button' value='Confirmar notas de devolução' name='gravar' onclick=\"javascript:
						if (document.frm_devolucao.botao_acao.value=='digitou_as_notas') {
							alert('Aguarde Submissão');
						}else{
							if(confirm('Deseja continuar? Prezado posto, pedimos que seja averiguado a(s) quantidade(s) de produto(s) ou peça(s), valor(es), nº de nota, pois após a sua confirmação não será possível cancelar o registro da nota fiscal.')){
								if(confirm('Prezado posto, para liberação das próximas telas, favor escanear e anexar a nota ao sistema.')){
									if (verificar('frm_devolucao')){
										document.frm_devolucao.botao_acao.value='digitou_as_notas';
										document.frm_devolucao.submit();
									}
								}
							}
						}
						\">
					<br>";
		} else {
			echo "<br><br><br>
					<input type='hidden' name='qtde_linha' value='$numero_linhas'>
					<input type='hidden' name='numero_de_notas' value='$numero_nota'>
					<input type='hidden' name='numero_de_notas_tc' value='$numero_nota_tc'>

					<b>Preencha TODAS as notas acima e clique no botão abaixo para confirmar!</b>
					<br><br>
					<input type='button' value='Confirmar notas de devolução' name='gravar' onclick=\"javascript:
						if (document.frm_devolucao.botao_acao.value=='digitou_as_notas') {
							alert('Aguarde Submissão');
						}else{
							if(confirm('Deseja continuar? As notas de devolução não poderão ser alteradas!')){
								if (verificar('frm_devolucao')){
									document.frm_devolucao.botao_acao.value='digitou_as_notas';
									document.frm_devolucao.submit();
								}
							}
						}
						\">
					<br>";
		}
			echo "<br><br><input type='button' value='Voltar a Tela Anterior' name='gravar' onclick=\"javascript:
				if(confirm('Deseja voltar?')) window.location='$PHP_SELF?extrato=$extrato';\">";
	}
	echo "</form>";
}else{
	echo "<h1><center> Extrato de Mão-de-obra Liberado. Recarregue a página. </center></h1>";
}

?>

<? include "rodape.php"; ?>
