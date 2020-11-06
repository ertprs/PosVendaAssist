<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";


$extrato = trim($_GET['extrato']);

// para redirecionar para a pagina antiga se a nota já foi digitada. Novas notas irão para esta pagina
if (strlen($extrato)>0){
	$sql = "SELECT	*
		FROM tbl_faturamento 
		JOIN tbl_faturamento_item USING(faturamento)
		WHERE tbl_faturamento.extrato_devolucao = $extrato
		AND tbl_faturamento.fabrica= $login_fabrica
		AND tbl_faturamento.posto=$login_posto
		AND tbl_faturamento_item.extrato_devolucao is not null";
	$res = pg_exec ($con,$sql);
	$qntos_digitou = pg_numrows($res);

	if ($qntos_digitou==0){
		$sql = "SELECT	*
			FROM tbl_extrato_devolucao 
			WHERE extrato = $extrato";
		$res = pg_exec ($con,$sql);
		$qntos_tem = pg_numrows($res);
		
		$sql = "SELECT	*
			FROM tbl_extrato_devolucao 
			WHERE extrato = $extrato
			AND nota_fiscal is not null";
		$res = pg_exec ($con,$sql);
		$qntos_falta = pg_numrows($res);
		
		if ($qntos_falta == $qntos_tem AND $qntos_tem>0) {
			header("Location: new_extrato_posto_devolucao.php?extrato=$extrato");
			exit();
		}
	}
}

$msg = "";

if (strlen($_GET['extrato'])>0 AND strlen($_GET['linha'])>0 AND strlen($_GET['distrib'])>0 AND strlen($_GET['serie'])>0 AND strlen($_GET['pa'])>0 AND strlen($_GET['proximo'])>0 AND strlen($_GET['extrato_devolucao'])>0){

	//header("Content-Type: text/html; charset=ISO-8859-1",true);
	//Header("Content-type: application/xml; charset=UTF-8"); 
	header("Content-type: application/xml; charset=ISO-8859-1"); 
	
	$extrato		= trim($_GET['extrato']);
	$extrato_devolucao	= trim($_GET['extrato_devolucao']);
	$distribuidor		= trim($_GET['distribuidor']);
	$linha			= trim($_GET['linha']);
	$serie			= trim($_GET['serie']);
	$produto_acabado	= trim($_GET['pa']);
	$proximo		= trim($_GET['proximo']);
	$gravar			= trim($_GET['gravar']);	
	$quantidade_cancelada	= trim($_GET['qtde']);
	$quantidade_cancelada_aux=$quantidade_cancelada;

	if (strlen($gravar)>0){
		if ($gravar!='sim') $gravar = "nao";
	}
	else{
		$gravar = "nao";
	}


	if (strlen($distribuidor)>0){
		$condicao_1 = " tbl_faturamento.distribuidor = $distribuidor ";
		$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
	}else{
		if ($serie == "2") {
			$distribuidor = "null";
			$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '2' ";
			$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
		}else{
			$distribuidor = "null";
			$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '$serie' ";
			$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
		}
	}

	$sql = "SELECT  tbl_faturamento.faturamento, 
		tbl_faturamento.nota_fiscal, 
		tbl_peca.peca, 
		tbl_peca.referencia, 
		tbl_peca.descricao, 
		tbl_peca.ipi, 
		tbl_faturamento_item.aliq_icms,
		tbl_faturamento_item.aliq_ipi,
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
		AND   tbl_faturamento_item.linha = $linha
		AND   tbl_faturamento.extrato_devolucao = $extrato
		AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
		AND   $condicao_1
		AND   $condicao_2
		AND   tbl_faturamento_item.aliq_icms > 0
		AND   tbl_faturamento.emissao > '2005-10-01'
		AND   tbl_faturamento.serie = '$serie'
		AND   tbl_faturamento_item.extrato_devolucao IS NULL
		GROUP BY tbl_faturamento.faturamento, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento.nota_fiscal,tbl_faturamento_item.aliq_ipi
		ORDER BY tbl_peca.referencia,tbl_faturamento.nota_fiscal
		LIMIT 1";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows($res) > 0) {
		$x=0;
		$peca		= pg_result ($res,$x,peca);
		$referencia	= pg_result ($res,$x,referencia);
		$descricao	= pg_result ($res,$x,descricao);
		$nota_fiscal	= pg_result ($res,$x,nota_fiscal);
		$qtde		= pg_result ($res,$x,qtde);
		$total_item 	= pg_result ($res,$x,total_item);
		$total_item	= number_format($total_item,2,",",".");
		$base_icms	= pg_result ($res,$x,base_icms);
		$valor_icms	= pg_result ($res,$x,valor_icms);
		$aliq_icms	= pg_result ($res,$x,aliq_icms);
		$base_ipi	= pg_result ($res,$x,base_ipi);
		$aliq_ipi	= pg_result ($res,$x,aliq_ipi);
		$valor_ipi	= pg_result ($res,$x,valor_ipi);
		$ipi		= pg_result ($res,$x,ipi);
		$preco		= number_format($total_item/$qtde,2,",",".");
		$nota_fiscal	= pg_result ($res,$x,nota_fiscal);
		$faturamento	= pg_result ($res,$x,faturamento);

		if ((strlen($quantidade_cancelada)==0 || $quantidade_cancelada==-1) || $qtde==$quantidade_cancelada){
			$quantidade_cancelada=-1; // nao cancela nenhum item
			$quantidade_cancelada_aux = $qtde;
		}
		else{
			$quantidade_cancelada_aux = $quantidade_cancelada;
			$quantidade_cancelada = $qtde - $quantidade_cancelada;
			if ($quantidade_cancelada<0){
				echo "Erro";
				exit();
			}
		}

		$resX = pg_exec ($con,"BEGIN TRANSACTION");

		if ($gravar=='sim'){
			$gravar="nao";
			if ($quantidade_cancelada==-1) {   // não cancela nenhum item
				$sql_update = "UPDATE tbl_faturamento_item 
						SET extrato_devolucao = $extrato_devolucao
						FROM tbl_faturamento
						WHERE tbl_faturamento.faturamento=tbl_faturamento_item.faturamento
						AND tbl_faturamento.faturamento = $faturamento
						AND tbl_faturamento.nota_fiscal = '$nota_fiscal'
						AND tbl_faturamento.extrato_devolucao = $extrato
						AND tbl_faturamento_item.peca = $peca";
				$res_update = pg_exec ($con,$sql_update);
				$msg .= pg_errormessage($con);
				if (strlen($msg) == 0) $gravar='sim';
			}
			else
			if ($quantidade_cancelada>0) { // cancelamento parcial
				$sql_update = "SELECT   tbl_faturamento_item.faturamento_item AS item,
							tbl_faturamento_item.qtde AS qtde
						FROM tbl_faturamento
						JOIN tbl_faturamento_item USING(faturamento)
						WHERE tbl_faturamento.nota_fiscal = '$nota_fiscal'
						AND tbl_faturamento.extrato_devolucao = $extrato
						AND tbl_faturamento_item.peca = $peca
						AND tbl_faturamento_item.extrato_devolucao IS NULL";
				$res_update = pg_exec ($con,$sql_update);
				$qtde_pecas = pg_numrows($res_update);
				if ($qtde_pecas>0){
					for($i=0;$i<$qtde_pecas;$i++){
						$item      = pg_result ($res_update,$i,item);
						$qtde_peca = pg_result ($res_update,$i,qtde);
						if ($qtde_peca-$quantidade_cancelada<=0){
							$sql_upc = "UPDATE tbl_faturamento_item 
									SET extrato_devolucao = 0
									WHERE faturamento_item=$item";
							$quantidade_cancelada = $quantidade_cancelada-$qtde_peca;
							$res_upc = pg_exec ($con,$sql_upc);
							$msg .= pg_errormessage($con);
							if (strlen($msg) == 0) $gravar='sim';
							else                   $gravar='nao';
						}
						else{
							if ($qtde_peca - $quantidade_cancelada>0){
								$tmp = $qtde_peca-$quantidade_cancelada;
								$sql_upc = "UPDATE tbl_faturamento_item 
								SET qtde = $quantidade_cancelada,
								extrato_devolucao = 0,
				valor_icms = CASE WHEN aliq_icms = 0 THEN 0 ELSE (aliq_icms*$quantidade_cancelada*preco)/100 END,
				base_icms = CASE WHEN aliq_icms = 0 THEN 0 ELSE preco*$quantidade_cancelada END,
				valor_ipi = CASE WHEN aliq_ipi = 0 THEN 0 ELSE (aliq_ipi*$quantidade_cancelada*preco)/100 END,
				base_ipi = CASE WHEN aliq_ipi = 0 THEN 0 ELSE preco*$quantidade_cancelada END
								WHERE tbl_faturamento_item.faturamento_item=$item";
								//$res_upc = pg_exec ($con,$sql_upc);
								$msg .= pg_errormessage($con);

								$sql_upc = "INSERT INTO tbl_faturamento_item
								(faturamento,peca,qtde,preco,pendente,pedido,os,qtde_estoque,aliq_icms,aliq_ipi,aliq_reducao,situacao_tributaria,os_item,pedido_item,qtde_quebrada,base_icms,valor_icms,linha,devolucao_origem,base_ipi,valor_ipi,extrato_devolucao)
								SELECT 
								faturamento,peca,$tmp,preco,pendente,pedido,os,qtde_estoque,aliq_icms,aliq_ipi,aliq_reducao,situacao_tributaria,os_item,pedido_item,qtde_quebrada,
						CASE WHEN aliq_icms = 0 THEN 0 ELSE preco*$tmp END,
						CASE WHEN aliq_icms = 0 THEN 0 ELSE (aliq_icms*$tmp*preco)/100 END,
								linha,
								devolucao_origem,
						CASE WHEN aliq_ipi = 0 THEN 0 ELSE preco*$tmp END,
						CASE WHEN aliq_ipi = 0 THEN 0 ELSE (aliq_ipi*$tmp*preco)/100 END,
								$extrato_devolucao 
								FROM tbl_faturamento_item
								WHERE faturamento_item = $item";
								//$res_upc = pg_exec ($con,$sql_upc);
								$msg .= pg_errormessage($con);
								if (strlen($msg) == 0) $gravar='sim';
								else                   $gravar='nao';
								$quantidade_cancelada=0;
							}
						}
						if ($quantidade_cancelada==0){
							break;
						}
						if ($quantidade_cancelada<0){ // precalção: no caso se der algum erro
							echo "Erro";	
							$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
							exit();
							break;
						}
					}
					$sql_update = "UPDATE tbl_faturamento_item 
							SET extrato_devolucao = $extrato_devolucao
							FROM tbl_faturamento
							WHERE tbl_faturamento.faturamento=tbl_faturamento_item.faturamento
							AND tbl_faturamento.nota_fiscal = '$nota_fiscal'
							AND tbl_faturamento.extrato_devolucao = $extrato
							AND tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.extrato_devolucao IS NULL";
					$res_update = pg_exec ($con,$sql_update);
					$msg .= pg_errormessage($con);
					if (strlen($msg) == 0) $gravar='sim';
				}
			}
		}else{
			$gravar='nao';
		}

		if ($gravar=="sim"){
			$qtde = $quantidade_cancelada_aux;
			$total_item = number_format($qtde * $preco,2,",",".");
		}

		$lista_pecas = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<pecas>\n";
		$lista_pecas .= "   <peca>\n";
		$lista_pecas .= "      <referencia>$referencia</referencia>\n";
		$lista_pecas .= "      <descricao>$descricao</descricao>\n";
		$lista_pecas .= "      <nf>$nota_fiscal</nf>\n";
		$lista_pecas .= "      <qtde>$qtde</qtde>\n";
		$lista_pecas .= "      <preco>$preco</preco>\n";
		$lista_pecas .= "      <total>$total_item</total>\n";
		$lista_pecas .= "      <icms>$aliq_icms</icms>\n";
		//$lista_pecas .= "      <ipi>$sql_update</ipi>\n";
		$lista_pecas .= "      <ipi>$aliq_ipi</ipi>\n";
		$lista_pecas .= "      <gravou>$gravar</gravou>\n";
		$lista_pecas .= "      <msg>$msg</msg>\n";
		$lista_pecas .= "   </peca>\n";
		$lista_pecas .= "</pecas>\n";

		if (strlen($msg) == 0) {
			$resX = pg_exec ($con,"COMMIT TRANSACTION");
			echo $lista_pecas;
		}else{
			$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
		}


		//echo "$peca|$qtde|$total_item|$base_icms|$valor_icms|$aliq_icms|$base_ipi|$aliq_ipi|$valor_ipi|$ipi|$ipi|$preco|$total_item|$nota_fiscal|$faturamento";
	}
	else{
		echo "fim";
	}
	exit;
}



if (strlen($_GET['fechar_nota'])>0 AND strlen($_GET['extrato'])>0 AND strlen($_GET['extrato_devolucao'])>0){

	//header("Content-Type: text/html; charset=ISO-8859-1",true);
	//Header("Content-type: application/xml; charset=UTF-8"); 
	header("Content-type: application/xml; charset=ISO-8859-1"); 
	
	$extrato		= trim($_GET['extrato']);
	$extrato_devolucao	= trim($_GET['extrato_devolucao']);

	$sql = "SELECT  tbl_faturamento.faturamento, 
			tbl_faturamento_item.faturamento_item AS faturamento_item,
		tbl_faturamento.nota_fiscal, 
		tbl_peca.peca, 
		tbl_peca.referencia, 
		tbl_peca.descricao, 
		tbl_peca.ipi, 
		tbl_faturamento_item.aliq_icms,
		tbl_faturamento_item.aliq_ipi,
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
		AND   tbl_faturamento_item.extrato_devolucao =$extrato_devolucao
		GROUP BY tbl_faturamento.faturamento,tbl_faturamento_item.faturamento_item, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento.nota_fiscal,tbl_faturamento_item.aliq_ipi";
	$res = pg_exec ($con,$sql);

	$total_base_icms  = 0;
	$total_valor_icms = 0;
	$total_base_ipi   = 0;
	$total_valor_ipi  = 0;
	$total_nota       = 0;

	if (pg_numrows($res) > 0) {
		for ($x=0;$x<pg_numrows($res);$x++){
			$faturamento_item= pg_result ($res,$x,faturamento_item);
			$peca		= pg_result ($res,$x,peca);
			$referencia	= pg_result ($res,$x,referencia);
			$descricao	= pg_result ($res,$x,descricao);
			$nota_fiscal	= pg_result ($res,$x,nota_fiscal);
			$qtde		= pg_result ($res,$x,qtde);
			$total_item 	= pg_result ($res,$x,total_item);
			$base_icms	= pg_result ($res,$x,base_icms);
			$valor_icms	= pg_result ($res,$x,valor_icms);
			$aliq_icms	= pg_result ($res,$x,aliq_icms);
			$base_ipi	= pg_result ($res,$x,base_ipi);
			$aliq_ipi	= pg_result ($res,$x,aliq_ipi);
			$valor_ipi	= pg_result ($res,$x,valor_ipi);
			$ipi		= pg_result ($res,$x,ipi);
			$preco		= $total_item / $qtde;
			$total_item	= $preco * $qtde;
			$faturamento	= pg_result ($res,$x,faturamento);
	
			if (strlen ($base_icms)  == 0) $base_icms = $total_item ;
			if (strlen ($valor_icms) == 0) $valor_icms = $total_item * $aliq_icms / 100;
	
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
	
			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi  += $base_ipi;
			$total_valor_ipi += $valor_ipi;
			$total_nota       += $total_item;
		}
		$total_base_icms  = number_format($total_base_icms,2,",",".");
		$total_valor_icms = number_format($total_valor_icms,2,",",".");
		$total_base_ipi   = number_format($total_base_ipi,2,",",".");
		$total_valor_ipi  = number_format($total_valor_ipi,2,",",".");
		$total_nota       = number_format($total_nota,2,",",".");

		$lista_pecas = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<valores>\n";
		$lista_pecas .= "   <valor>\n";
		$lista_pecas .= "      <base_icms>$total_base_icms</base_icms>\n";
		$lista_pecas .= "      <valor_icms>$total_valor_icms</valor_icms>\n";
		$lista_pecas .= "      <base_ipi>$total_base_ipi</base_ipi>\n";
		$lista_pecas .= "      <valor_ipi>$total_valor_ipi</valor_ipi>\n";
		$lista_pecas .= "      <total>$total_nota</total>\n";
		$lista_pecas .= "   </valor>\n";
		$lista_pecas .= "</valores>\n";
		echo $lista_pecas;
	}
	else{
		/*$lista_pecas = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<valores>\n";
		$lista_pecas .= "</valores>\n";*/
		echo $lista_pecas;
	}
	exit;
}




$btn_acao = $_POST['botao_acao'];
if (strlen($btn_acao) > 0) {
	$extrato = $_POST['extrato'];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$extrato  = trim($_POST['extrato']);
	$extrato_devolucao = trim($_POST['extrato_devolucao']);
	$nota_fiscal = str_replace(",",".",trim($_POST['nota_fiscal_'.$extrato_devolucao]));

	$base_icms  = str_replace(".","",trim($_POST['base_icms']));
	$base_icms  = str_replace(",",".",trim($base_icms));

	$valor_icms   = str_replace(".","",trim($_POST['valor_icms']));
	$valor_icms   = str_replace(",",".",trim($valor_icms));

	$base_ipi  = str_replace(".","",trim($_POST['base_ipi']));
	$base_ipi  = str_replace(",",".",trim($base_ipi));

	$valor_ipi  = str_replace(".","",trim($_POST['valor_ipi']));
	$valor_ipi  = str_replace(",",".",trim($valor_ipi));

	$total_nota  = str_replace(".","",trim($_POST['total_nota']));
	$total_nota  = str_replace(",",".",trim($total_nota));

	if (strlen($nota_fiscal) == 0) {
		$msg = " Favor informar o número de todas as Notas de Devolução.";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		// adicionado por Fabio - somente para ver se deu erro na hora de confirmar as notas de devolucao
		$email_origem  = "fabio@telecontrol.com.br";
		$email_destino = "fabio@telecontrol.com.br";
		$assunto       = "URGENTE - EXTRATO POSTO DEVOLUCAO";
		$corpo ="<br>Extrato: $extrato\n\n Posto não conseguiu confirmar Nota de Devolução. VERIFICAR URGENTE\n\n";
		//@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); 
		// fim
	}


	$sql_mais = "SELECT	distribuidor,
				linha,
				CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado_2,
				produto_acabado,
				serie
			FROM tbl_extrato_devolucao 
			WHERE extrato_devolucao=$extrato_devolucao
			AND extrato=$extrato";
	$res_mais = pg_exec ($con,$sql_mais);

	$distribuidor      = trim (pg_result ($res_mais,0,distribuidor));
	$produto_acabado_2 = trim (pg_result ($res_mais,0,produto_acabado_2));
	$produto_acabado   = trim (pg_result ($res_mais,0,produto_acabado));
	$serie             = trim (pg_result ($res_mais,0,serie));
	$linha		   = trim (pg_result ($res_mais,0,linha));


	if (strlen ($distribuidor) > 0) {
		$condicao_1 = " tbl_faturamento.distribuidor = $distribuidor ";
		$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado_2 ";

	}else{
		if ($serie == "2") {
			$distribuidor = "null";
			$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '2' ";
			$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado_2 ";
			
		}else{
			$distribuidor = "null";
			$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '$serie' ";
			$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado_2 ";
		}
	}

	$sql = "SELECT  tbl_faturamento.faturamento, 
			tbl_faturamento.nota_fiscal, 
			tbl_peca.peca, 
			tbl_peca.referencia, 
			tbl_peca.descricao, 
			tbl_peca.ipi, 
			tbl_faturamento_item.aliq_icms,
			tbl_faturamento_item.aliq_ipi,
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
			AND   tbl_faturamento_item.linha = $linha
			AND   tbl_faturamento.extrato_devolucao = $extrato
			AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
			AND   $condicao_1
			AND   $condicao_2
			AND   tbl_faturamento_item.aliq_icms > 0
			AND   tbl_faturamento.emissao > '2005-10-01'
			AND   tbl_faturamento.serie = '$serie'
			AND   tbl_faturamento_item.extrato_devolucao IS NULL
			GROUP BY tbl_faturamento.faturamento, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento.nota_fiscal,tbl_faturamento_item.aliq_ipi
			ORDER BY tbl_peca.referencia,tbl_faturamento.nota_fiscal";
	$resX = pg_exec ($con,$sql);
	$quantos_itens_falta=pg_numrows ($resX);
	if ($quantos_itens_falta>0){
		$sql_1 = "INSERT INTO tbl_extrato_devolucao 
				(extrato,distribuidor,linha,produto_acabado,serie) 
				VALUES ($extrato,$distribuidor,'$linha','$produto_acabado',$serie)
				";
		$res_1 = pg_exec ($con,$sql_1);
		$msg .= pg_errormessage($con);
	}else
	//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	//exit();

	
	$nota_fiscal = str_replace(".","",$nota_fiscal);
	$nota_fiscal = str_replace(",","",$nota_fiscal);
	$nota_fiscal = str_replace("-","",$nota_fiscal);
	
	$nota_fiscal = "000000" . $nota_fiscal;
	$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);
	
	if (strlen ($msg) == 0) {
		$sql =	"UPDATE tbl_extrato_devolucao SET
				nota_fiscal             = '$nota_fiscal'      ,
				total_nota              = $total_nota         ,
				base_icms               = $base_icms          ,
				valor_icms              = $valor_icms,
				base_ipi               = $base_ipi          ,
				valor_ipi              = $valor_ipi,
				data_nf_envio              = current_date
			WHERE extrato_devolucao = $extrato_devolucao";
		#echo nl2br($sql);
		$resX = @pg_exec ($con,$sql);
		$msg = pg_errormessage($con);
	}
	//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	//exit();
	if (strlen($msg) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?extrato=$extrato");
		exit;
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


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

</style>
<script language='javascript'>

var Ajax = new Object();
var id_tabela="";
var total_nota_devolucao="";
var id_total_nota="";

Ajax.Request = function(url,callbackMethod){
	
	Page.getPageCenterX();
	Ajax.request = Ajax.createRequestObject();
	Ajax.request.onreadystatechange = callbackMethod;
	Ajax.request.open("POST", url, true);
	Ajax.request.send(url);
}

Ajax.FecharNota = function (){
	if(Ajax.CheckReadyState(Ajax.request))	{
		var	resposta = Ajax.request.responseXML;
		var aDados = resposta;
		var pecas = aDados.getElementsByTagName('valores');

		for (var i = 0; i < pecas.length; i++) {
			cat = pecas[i];
			base_icms	= cat.getElementsByTagName('base_icms')[0].firstChild.nodeValue;
			valor_icms 	= cat.getElementsByTagName('valor_icms')[0].firstChild.nodeValue;
			base_ipi 	= cat.getElementsByTagName('base_ipi')[0].firstChild.nodeValue;
			valor_ipi 	= cat.getElementsByTagName('valor_ipi')[0].firstChild.nodeValue;
			total	 	= cat.getElementsByTagName('total')[0].firstChild.nodeValue;

			document.getElementById('total_nota').value=total;
			document.getElementById('base_icms').value=base_icms;
			document.getElementById('valor_icms').value=valor_icms;
			document.getElementById('base_ipi').value=base_ipi;
			document.getElementById('valor_ipi').value=valor_ipi;

			var total_nota = document.getElementById(total_nota_devolucao).style.display='block';
			document.getElementById('fechar').style.display='none'
			document.getElementById('next').style.display='none'

			adicionaTotal(base_icms, valor_icms, base_ipi, valor_ipi, total);
		}
		if (pecas.length==0){
			
			//document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=gray><tr><td align=center class=loaded height=45 bgcolor=#ffffff>Não há itens para esta nota</td></tr></table>";
			//setTimeout('Page.loadOut()',1000);
			document.getElementById('fechar').style.display='';
			alert("Por favor, inclua no mínimo 1 item para a nota de devolução. \n\nClique sobre o botão 'Incluir mais um item' para incluir o item na nota");
			return true;
		}
	//}
	}
}

Ajax.Response = function (){
	if(Ajax.CheckReadyState(Ajax.request))	{
		var	verificacao = Ajax.request.responseText;
		alert(verificacao);
		if (verificacao=='Erro'){
			alert('Ocorreu um erro inesperado. Por favor, reabra esta tela e tente novamente. Caso o erro persista, contate o Suporte Telecontrol');
			return false;
		}
		var	resposta = Ajax.request.responseXML;
		var aDados = resposta;
		var pecas = aDados.getElementsByTagName('pecas');
		document.getElementById('loading').innerHTML ="";

		for (var i = 0; i < pecas.length; i++) {
			cat = pecas[i];
			referencia = cat.getElementsByTagName('referencia')[0].firstChild.nodeValue;
			descricao = cat.getElementsByTagName('descricao')[0].firstChild.nodeValue;
			nf = cat.getElementsByTagName('nf')[0].firstChild.nodeValue;
			qtde = cat.getElementsByTagName('qtde')[0].firstChild.nodeValue;
			preco = cat.getElementsByTagName('preco')[0].firstChild.nodeValue;
			total = cat.getElementsByTagName('total')[0].firstChild.nodeValue;
			icms = cat.getElementsByTagName('icms')[0].firstChild.nodeValue;
			ipi = cat.getElementsByTagName('ipi')[0].firstChild.nodeValue;
			gravou = cat.getElementsByTagName('gravou')[0].firstChild.nodeValue;
			

			if (qtde==0){
				alert("Cancelado\n\nPeça: "+referencia+" - "+descricao);
				break;
			}

			//if (document.getElementById('next_gravar').value=='sim'){
			if (gravou=='sim'){
				document.getElementById('next_gravar').value='nao';
				document.getElementById('next_qtde').value='';
				adicionaLinha(referencia, descricao, nf, qtde, preco, total, icms, ipi);
			}
			else{
				var msg1="";
				var msg2="";
				if (qtde==1) {
					msg1 = "Você tem essa peça para a devolução?";
					msg2 = "Você tem essa peça para a devolução?";
				}
				if (qtde>1){
					msg1 = "Você tem essas "+qtde+" peças para a devolução?";
					msg2 = "Você tem essas "+qtde+" peças para a devolução?";
				}

				if (confirm("Item a ser incluido nesta nota\n\nReferencia: "+referencia+"\nDescrição: "+descricao+"\nNota Fiscal: "+nf+"\nQuantidade: "+qtde+"\n\n"+msg1+"\n\nOK: Sim\nCancelar: Não\n\n")){
					document.getElementById('next_gravar').value='sim';
					document.getElementById('next_qtde').value='-1';
					document.getElementById('next2').click();
					document.getElementById('next').disabled=false;
					return true;
				}
				else{
					var erros=0;
					var pecas_para_devolver=0;
					if (qtde==1) {
						var qtdetem = confirm("Referencia: "+referencia+"\nDescrição: "+descricao+"\nNota Fiscal: "+nf+"\nQuantidade: "+qtde+"\n\nDeseja realmente retirar este item da devolução?");
						if ( qtdetem==false || qtdetem==null){
							alert('Operação cancelada');
							erros++;
						}
						else{
							pecas_para_devolver=0;
						}
					}
					if (qtde>1) {
						var qtdetem = prompt("Referencia: "+referencia+"\nDescrição: "+descricao+"\nNota Fiscal: "+nf+"\nQuantidade: "+qtde+"\n\nDas "+qtde+" peças, quantas você possui?\n\n\n0 = Não tenho nenhuma\n\nQuantidade:","");
						if ( qtdetem=='' || qtdetem==null || qtdetem.length==0 ){
							alert('Você não digitou a quantidade de item');
							erros++;
						}
						if (qtdetem<0 || qtdetem>qtde){
							alert('Quantidade inválida');
							erros++;
						}
						pecas_para_devolver=qtdetem;
					}
					if (erros==0){
						document.getElementById('next_gravar').value='sim';
						document.getElementById('next_qtde').value=pecas_para_devolver;
						document.getElementById('next').click();
					}
				}
			}
		}
		document.getElementById('next').disabled=false;

		if (pecas.length==0 && verificacao=='fim'){
			document.getElementById('next').style.display='none';
			//document.getElementById('loading').innerHTML = "<table border=0 cellpadding=9 cellspacing=1 width=200 bgcolor=#FF6666><tr><td align=center class=loaded height=45 bgcolor=#FFCCCC><b>Não há mais itens para esta nota.</b> <br><b>Finalizar esta nota.</b></td></tr></table>";
			//setTimeout('Page.loadOut()',2000);
			alert("Não há mais itens para esta nota. Clique no botão 'Finalizar esta nota'");
			return true;
		}
	}
}

Ajax.createRequestObject = function(){
	var obj;
	if(window.XMLHttpRequest)	{
		obj = new XMLHttpRequest();
	}
	else if(window.ActiveXObject)	{
		obj = new ActiveXObject("MSXML2.XMLHTTP");
	}
	return obj;
}

Ajax.CheckReadyState = function(obj){
	if(obj.readyState < 4) {
		document.getElementById('loading').style.top = (Page.top + Page.height/2)-100;
		document.getElementById('loading').style.left = Page.width/2-75;
		document.getElementById('loading').style.position = "absolute";
		document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=#AAA><tr><td align=center class=loading height=45 bgcolor=#FFFFFF>Aguarde.....<br><br><img src='imagens/carregar_os'></td></tr></table>";  
	}
	if(obj.readyState == 4)	{
		if(obj.status == 200){
			//document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=gray><tr><td align=center class=loaded height=45 bgcolor=#ffffff>Informações carregadas com sucesso!</td></tr></table>";
			document.getElementById('loading').innerHTML = "";
			setTimeout('Page.loadOut()',1);
			return true;
		}
		else{
			document.getElementById('loading').innerHTML = "HTTP " + obj.status;
			setTimeout('Page.loadOut()',3000);
		}
	}
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('loading').innerHTML ='';	
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;		
	//For old IE browsers 
	if(document.all) { 
		fWidth = document.body.clientWidth; 
		fHeight = document.body.clientHeight; 
	} 
	//For DOM1 browsers 
	else if(document.getElementById &&!document.all){ 
			fWidth = innerWidth; 
			fHeight = innerHeight; 
		} 
		else if(document.getElementById) { 
				fWidth = innerWidth; 
				fHeight = innerHeight; 		
			} 
			//For Opera 
			else if (is.op) { 
					fWidth = innerWidth; 
					fHeight = innerHeight; 		
				} 
				//For old Netscape 
				else if (document.layers) { 
						fWidth = window.innerWidth; 
						fHeight = window.innerHeight; 		
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}
/* ############################################################## */


function proximo_item(botao,extrato,extrato_devolucao,pa,linha,distrib,serie,tabela){

	var gravar     = document.getElementById('next_gravar').value;
	var quantidade = document.getElementById('next_qtde').value;

if (gravar=='sim'){
	alert('gravando...');
}
else{
	alert(gravar);
}

	botao.disabled=true;
	id_tabela=tabela;


Ajax.Request('new_extrato_posto_devolucao_fabio5.php?proximo=item&extrato='+extrato+'&extrato_devolucao='+extrato_devolucao+'&pa='+pa+'&linha='+linha+'&distrib='+distrib+'&serie='+serie+'&gravar='+gravar+'&qtde='+quantidade, Ajax.Response);
}

function adicionaTotal(base_icms, valor_icms, base_ipi, valor_ipi, total) {

   var tabela = document.createElement('table');
   tabela.setAttribute('border','1px');
   tabela.setAttribute('cellspacing','0');
   tabela.setAttribute('cellpadding','0');
   tabela.setAttribute('width','650px');
   tabela.style.cssText = "border-collapse:collapse;font-size:12px'";

   var linha = document.createElement('tr');
   linha.style.cssText = 'color: #000000; text-align: left; font-size:12px';

   var celula = criaCelula('Base ICMS');
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px;';
   linha.appendChild(celula);

   var celula = criaCelula('Valor ICMS');
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px;';
   linha.appendChild(celula);

   var celula = criaCelula('Base IPI');
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px;';
   linha.appendChild(celula);

   var celula = criaCelula('Valor IPI');
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px;';
   linha.appendChild(celula);

   var celula = criaCelula('Total');
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px;';
   linha.appendChild(celula);

   var tbody = document.createElement('TBODY');
   tbody.appendChild(linha);
   tabela.appendChild(tbody);

   var linha = document.createElement('tr');
   linha.style.cssText = 'color: #000000; text-align: left; font-size:12px; font-weight:bold;';

   var celula = criaCelula(base_icms);
   celula.style.cssText = 'text-align: left; color: #000000; font-size:12px; font-weight:bold;';
   linha.appendChild(celula);

   var celula = criaCelula(valor_icms);
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px; font-weight:bold;';
   linha.appendChild(celula);

   var celula = criaCelula(base_ipi);
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px; font-weight:bold;';
   linha.appendChild(celula);

   var celula = criaCelula(valor_ipi);
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px; font-weight:bold;';
   linha.appendChild(celula);

   var celula = criaCelula(total);
   celula.style.cssText = 'text-align: left;color: #000000; font-size:12px; font-weight:bold;';
   linha.appendChild(celula);

   var tbody = document.createElement('TBODY');
   tbody.appendChild(linha);
   tabela.appendChild(tbody);
   document.getElementById(id_total_nota).appendChild(tabela);
}

function adicionaLinha(referencia, descricao, nf, qtde, preco, total, icms, ipi) {

   var linha = document.createElement('tr');
   linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

   var celula = criaCelula(referencia);
   celula.style.cssText = 'text-align: left;color: #000000; font-size:10px';
   linha.appendChild(celula);

   var celula = criaCelula(descricao);
   celula.style.cssText = 'text-align: left;color: #000000; font-size:10px';
   linha.appendChild(celula);

   celula = criaCelula(nf);
   celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';
   linha.appendChild(celula);

   celula = criaCelula(qtde);
   celula.style.cssText = 'text-align: right;color: #000000; font-size:10px';
   linha.appendChild(celula);

   celula = criaCelula(preco);
   celula.style.cssText = 'text-align: right;color: #000000; font-size:10px';
   linha.appendChild(celula);

   celula = criaCelula(total);
   celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';
   linha.appendChild(celula);

   celula = criaCelula(icms);
   celula.style.cssText = 'text-align: right;color: #000000; font-size:10px';
   linha.appendChild(celula);

   celula = criaCelula(ipi);
   celula.style.cssText = 'text-align: right;color: #000000; font-size:10px';
   linha.appendChild(celula);

   var tbody = document.createElement('TBODY');
   tbody.appendChild(linha);

   //linha.style.cssText = 'color: #404e2a;';
   document.getElementById(id_tabela).appendChild(tbody);

}

function criaCelula(texto) {
   var celula = document.createElement('td');
   var textoNode = document.createTextNode(texto);
   celula.appendChild(textoNode);
   return celula;
}


function removeRowFromTable(){
	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	if (lastRow > 2) tbl.deleteRow(lastRow - 1);
}

function concluir_nota(extrato,extrato_devolucao,id_campo_nota,id_total){
	total_nota_devolucao	= id_campo_nota;
	id_total_nota		= id_total;
	Ajax.Request('new_extrato_posto_devolucao_fabio5.php?fechar_nota=nota&extrato='+extrato+'&extrato_devolucao='+extrato_devolucao, Ajax.FecharNota);
}

function fechar_nota(extrato,extrato_devolucao,produto_acabado,linha,distribuidor,serie){
//	var nota = document.getElementById('nota_fiscal_'+extrato_devolucao).value;
	//Ajax.Request('new_extrato_posto_devolucao_fabio5.php?fechar_nota=nota&extrato='+extrato+'&extrato_devolucao='+extrato_devolucao, Ajax.FecharNota);
}
function verificar_nota(nota_dev){
	document.getElementById('btn_acao').disabled=true;
	if (document.getElementById(nota_dev).value=='' || document.getElementById(nota_dev).value==' '  || document.getElementById(nota_dev).value=='  ') {
		document.getElementById('btn_acao').disabled=false;
		alert('Preencha com a nota fiscal');
		return false;
	}
	if (!confirm('Deseja continuar?\n\nUma vez preenchida, a nota fiscal não poderá mais ser alterada.')){
		document.getElementById('btn_acao').disabled=false;
		return false;
	}
	return true;
}

</script>
<div id='loading'></div>

<? if (strlen($msg) > 0) { ?>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<? } ?>

<center>
<br>
<table width='550' align='center'>
<tr><td>
<b>Conforme determina a legislação local</b><p>
Para toda nota fiscal de peças enviadas em garantia deve haver nota fiscal de devolução de todas as peças nos mesmos valores, quantidades e com os mesmos destaques de impostos obrigatoriamente.
<br>
O valor da mão-de-obra será exibido somente após confirmação da Nota Fiscal de Devolução.
<br>
TODAS as peças de Áudio e Vídeo devem retornar junto com esta Nota fiscal.
<br>
As peças das linhas de eletroportáteis e branca devem ficar no posto por 90 dias para inspeção ou de acordo com os procedimentos definidos por seu DISTRIBUIDOR.
<br>
</td></tr></table>
<br>

<?

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato ";
$res = pg_exec ($con,$sql);
$data = pg_result ($res,0,data);
$periodo = pg_result ($res,0,periodo);
$nome = pg_result ($res,0,nome);
$codigo = pg_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='+0' face='arial'>$codigo - $nome</font>";

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='33%'><a href='new_extrato_posto_mao_obra.php?extrato=<? echo $extrato ?>'>Ver Mão-de-Obra</a></td>
<td align='center' width='33%'><a href='new_extrato_posto.php'>Ver outro extrato</a></td>
</tr>
</table>

	<br>
	<font face='arial' size='+1' color='#330066'>Você deve emitir uma Nota Fiscal com os dados abaixo.</font>
	<br>
	<font face='arial' size='+0' color='#330066'>O valor da mão-de-obra só será exibido <br> depois que você confirmar a emissão da Nota de Devolução.</font>
	<br>
	<font face='arial' size='+0' color='#330066'>As peças de Áudio e Vídeo devem <b>todas</b> retornar fisicamente junto com esta Nota fiscal.</font>
	<br>
	<font face='arial' size='+0' color='#330066'>As peças de Eletro e linha branca devem ficar no posto por 90 dias para inspeção.</font><br><br>

<br>
<TABLE width="550" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="3" class="menu_top"><div align="center"><b>Mudanças no Preenchimento de Notas Fiscais de Devolução</b></div></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" class="table_line"><br>
	Para o preenchimento das notas de devolução, primeiro preencha o cabeçalho da nota e em seguinda clique sobre o botão 'Incluir mais um item' para incluir os itens.<br>
	Após incluir todos os itens que couber na sua nota, clique em 'Finalizar Esta Nota'. Aparecerá o campo para o preenchimento da nota. Preencha com o número da nota e clique no botão de confirmação.<br>
	<br>Siga este procedimento para todas a notas.<br>Caso não tenha algum item, não inclua a nota<br>
	</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
</table>
<br>

<?

if (strlen ($extrato) == 0) $extrato = trim($_GET['extrato']);

$sql  = "SELECT COUNT(*) FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NULL";
$resY = pg_exec ($con,$sql);
$qtde = pg_result ($resY,0,0);
if ($qtde > 0) {

}

?>

<?
$sql = "UPDATE tbl_faturamento_item SET linha = (SELECT tbl_produto.linha FROM tbl_produto 
				JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_faturamento_item.peca = tbl_lista_basica.peca LIMIT 1)
		FROM tbl_faturamento
		WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		AND tbl_faturamento.fabrica = $login_fabrica 
		AND tbl_faturamento.extrato_devolucao = $extrato";
$res = pg_exec ($con,$sql);

if ($login_fabrica == 3) {
	$sql = "UPDATE tbl_faturamento_item SET linha = 2
			FROM tbl_faturamento
			WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			AND tbl_faturamento.fabrica = $login_fabrica 
			AND tbl_faturamento.extrato_devolucao = $extrato
			AND tbl_faturamento_item.linha IS NULL";
	$res = pg_exec ($con,$sql);
}

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

$sql = "SELECT count(*) FROM tbl_extrato_devolucao WHERE extrato = $extrato";
$res = pg_exec ($con,$sql);
$qtde_devolucao = pg_result ($resX,0,0);

$sql = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao,
			tbl_faturamento.distribuidor,
			tbl_faturamento_item.linha,
			CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
			tbl_faturamento.serie
		FROM    tbl_faturamento 
		JOIN    tbl_faturamento_item USING (faturamento) 
		JOIN    tbl_peca             USING (peca)
		WHERE   tbl_faturamento.extrato_devolucao = $extrato
		AND     tbl_faturamento.posto             = $login_posto
		AND     (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
		AND     tbl_faturamento_item.aliq_icms > 0 
		ORDER BY extrato,produto_acabado,linha,serie,distribuidor,extrato_devolucao";

$sql = "SELECT	extrato AS extrato,
		distribuidor,
		linha,
		CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
		serie,
		extrato_devolucao,
		nota_fiscal
	FROM tbl_extrato_devolucao 
	WHERE extrato = $extrato
	ORDER BY extrato,produto_acabado,linha,serie,distribuidor,extrato_devolucao";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res)==0){

	$sql_1 = "INSERT INTO tbl_extrato_devolucao (extrato,distribuidor,linha,produto_acabado,serie) 
			SELECT  DISTINCT tbl_faturamento.extrato_devolucao,
				CASE WHEN tbl_faturamento.distribuidor IS NOT NULL THEN tbl_faturamento.distribuidor ELSE null END AS distribuidor,
				tbl_faturamento_item.linha,
				CASE WHEN produto_acabado IS TRUE THEN TRUE ELSE FALSE END AS produto_acabado,
				tbl_faturamento.serie
			FROM    tbl_faturamento 
			JOIN    tbl_faturamento_item USING (faturamento) 
			JOIN    tbl_peca             USING (peca)
			WHERE   tbl_faturamento.extrato_devolucao = $extrato
			AND     tbl_faturamento.posto             = $login_posto
			AND     (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
			AND     tbl_faturamento_item.aliq_icms > 0 
			ORDER BY produto_acabado, linha";
	$res_1 = pg_exec ($con,$sql_1);
	$sql = "SELECT	extrato AS extrato,
			distribuidor,
			linha,
			CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
			serie,
			extrato_devolucao,
			nota_fiscal
		FROM tbl_extrato_devolucao 
		WHERE extrato = $extrato
		ORDER BY extrato,produto_acabado,linha,serie,distribuidor";
	$res = pg_exec ($con,$sql);
}

if (pg_numrows ($res) > 0) {
	$qtde_for = pg_numrows ($res);
	for ($i=0; $i < $qtde_for; $i++) {

		$distribuidor     = trim (pg_result ($res,$i,distribuidor));
		$produto_acabado  = trim (pg_result ($res,$i,produto_acabado));
		$serie            = trim (pg_result ($res,$i,serie));
		$linha		  = trim (pg_result ($res,$i,linha));
		$extrato_devolucao= trim (pg_result ($res,$i,extrato_devolucao));
		$nota_fiscal      = trim (pg_result ($res,$i,nota_fiscal));


		$sql = "SELECT * FROM tbl_linha WHERE linha = $linha" ;
		$resZ = pg_exec ($con,$sql);
		$linha_nome = pg_result ($resZ,0,nome);

		if (strlen ($distribuidor) > 0) {
			$sql  = "SELECT * FROM tbl_posto WHERE posto = $distribuidor";
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

			$condicao_1 = " tbl_faturamento.distribuidor = $distribuidor ";
			$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";

		}else{
			if ($serie == "2") {
				$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
				$endereco = "Rua Dona Francisca, 8300 - Mod.4 e 5 - Bloco A";
				$cidade   = "Joinville";
				$estado   = "SC";
				$cep      = "89239270";
				$fone     = "(41) 2102-7700";
				$cnpj     = "76492701000742";
				$ie       = "254.861.652";

				$distribuidor = "null";
				$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '2' ";
				$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
				
			}else{
				$sql  = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
				$resX = pg_exec ($con,$sql);

				$razao    = pg_result ($resX,0,razao_social);
				$endereco = pg_result ($resX,0,endereco);
				$cidade   = pg_result ($resX,0,cidade);
				$estado   = pg_result ($resX,0,estado);
				$cep      = pg_result ($resX,0,cep);
				$fone     = pg_result ($resX,0,fone);
				$cnpj     = pg_result ($resX,0,cnpj);
				$ie       = pg_result ($resX,0,ie);

				$distribuidor = "null";
				$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '$serie' ";
				$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
			}
		}

		$cfop = '6949';
		if ($estado_origem == $estado) $cfop = '5949';
	
		$pecas_produtos = "PEÇAS";
		if ($produto_acabado == "TRUE") $pecas_produtos = "PRODUTOS";

		echo "Nota de Devolução de <b>$pecas_produtos</b> da Linha: $linha_nome" ;
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Natureza <br> <b>Devolução de Garantia</b> </td>";
		echo "<td>CFOP <br> <b>$cfop</b> </td>";
		echo "<td>Emissao <br> <b>$data</b> </td>";
		echo "</tr>";
		echo "</table>";

		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Razão Social <br> <b>$razao</b> </td>";
		echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
		echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
		echo "</tr>";
		echo "</table>";

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Endereço <br> <b>$endereco </b> </td>";
		echo "<td>Cidade <br> <b>$cidade</b> </td>";
		echo "<td>Estado <br> <b>$estado</b> </td>";
		echo "<td>CEP <br> <b>$cep</b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>";
		echo "<thead>";
		echo "<tr align='center'>";
		echo "<td><b>Código</b></td>";
		echo "<td><b>Descrição</b></td>";
		echo "<td><b>NF Origem</b></td>";
		echo "<td><b>Qtde.</b></td>";
		echo "<td><b>Unitário</b></td>";
		echo "<td><b>Total</b></td>";
		echo "<td><b>% ICMS</b></td>";
		echo "<td><b>% IPI</b></td>";
		//echo "<td><b>X</b></td>";
		echo "</tr>";
		echo "</thead>";

		$sql = "SELECT DISTINCT tbl_faturamento.nota_fiscal
				FROM tbl_faturamento_item 
				JOIN tbl_faturamento      USING (faturamento)
				JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.posto   = $login_posto
				AND   tbl_faturamento_item.linha = $linha
				AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   $condicao_1
				AND   $condicao_2
				AND   tbl_faturamento_item.aliq_icms > 0
				AND   tbl_faturamento.emissao > '2005-10-01'
				AND   tbl_faturamento_item.extrato_devolucao IS NOT NULL
				AND   tbl_faturamento.serie = '$serie'
				ORDER BY tbl_faturamento.nota_fiscal ";
				//echo $sql;
		$resX = pg_exec ($con,$sql);
		$notas_fiscais    = "";
		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
			$notas_fiscais .= pg_result ($resX,$x,nota_fiscal) . ", ";
		}
		

		$sql = "SELECT  tbl_faturamento.faturamento, 
				tbl_faturamento.nota_fiscal, 
				tbl_peca.peca, 
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.ipi, 
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
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
				AND   tbl_faturamento_item.linha = $linha
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
				AND   $condicao_1
				AND   $condicao_2
				AND   tbl_faturamento_item.aliq_icms > 0
				AND   tbl_faturamento.emissao > '2005-10-01'
				AND   tbl_faturamento.serie = '$serie'
				AND   tbl_faturamento_item.extrato_devolucao IS NULL
				GROUP BY tbl_faturamento.faturamento, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento.nota_fiscal,tbl_faturamento_item.aliq_ipi
				ORDER BY tbl_peca.referencia,tbl_faturamento.nota_fiscal";
		$resX = pg_exec ($con,$sql);
		$quantos_itens_falta=pg_numrows ($resX);

		$sql = "SELECT  tbl_faturamento.faturamento, 
				tbl_faturamento.nota_fiscal, 
				tbl_peca.peca, 
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.ipi, 
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
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
				AND   tbl_faturamento_item.linha = $linha
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
				AND   $condicao_1
				AND   $condicao_2
				AND   tbl_faturamento_item.aliq_icms > 0
				AND   tbl_faturamento.emissao > '2005-10-01'
				AND   tbl_faturamento.serie = '$serie'
				AND   tbl_faturamento_item.extrato_devolucao =$extrato_devolucao
				GROUP BY tbl_faturamento.faturamento, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento.nota_fiscal,tbl_faturamento_item.aliq_ipi
				ORDER BY tbl_peca.referencia,tbl_faturamento.nota_fiscal";
		//AND   tbl_faturamento_item.extrato_devolucao IS NOT NULL
		//echo "<br><br>$sql";
		$resX = pg_exec ($con,$sql);
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi  = 0;
		$total_valor_ipi = 0;
		$total_nota       = 0;
		$aliq_final       = 0;

		echo "<tbody>";
		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

			$peca        = pg_result ($resX,$x,peca);
			$qtde        = pg_result ($resX,$x,qtde);
			$total_item  = pg_result ($resX,$x,total_item);
			$base_icms   = pg_result ($resX,$x,base_icms);
			$valor_icms  = pg_result ($resX,$x,valor_icms);
			$aliq_icms   = pg_result ($resX,$x,aliq_icms);
			$base_ipi   = pg_result ($resX,$x,base_ipi);
			$aliq_ipi   = pg_result ($resX,$x,aliq_ipi);
			$valor_ipi   = pg_result ($resX,$x,valor_ipi);
			$ipi = pg_result ($resX,$x,ipi);
			$preco       =  $total_item / $qtde;
			$total_item  = $preco * $qtde;
			$nota_fiscal_item = pg_result ($resX,$x,nota_fiscal);
			$faturamento = pg_result ($resX,$x,faturamento);

			if (strlen ($base_icms)  == 0) $base_icms = $total_item ;
			if (strlen ($valor_icms) == 0) $valor_icms = $total_item * $aliq_icms / 100;


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

			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
			echo "<td align='left'>" . pg_result ($resX,$x,referencia) . "</td>\n";
			echo "<td align='left'>" . pg_result ($resX,$x,descricao) . "</td>\n";
			echo "<td align='left'>" . pg_result ($resX,$x,nota_fiscal) . "</td>\n";
			echo "<td align='right'>" . pg_result ($resX,$x,qtde) . "</td>\n";
			echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>\n";
			echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
			echo "<td align='right'>" . $aliq_icms . "</td>\n";
			echo "<td align='right'>" . $aliq_ipi. "</td>\n";
			//echo "<td align='right'><a href=\"javascript:removerItem('$peca','$extrato_devolucao');\"><b>X</b></td>";
			echo "</tr>\n";

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi  += $base_ipi;
			$total_valor_ipi += $valor_ipi;
			$total_nota       += $total_item;
		}
// 		echo "</tbody>";
// 		echo "<tfoot>";
// 		echo "<tr>";
// 		echo "<td colspan='8'> Referente suas NFs. " . $notas_fiscais . " Referente a Série $serie</td>";
// 		echo "</tr>";
// 		echo "</tfoot>";

		if ($nota_fiscal==0){
			echo "<tfoot>\n";
			echo "<tr>\n";
			echo "<td colspan='8' align='left'>\n";
			echo "<input type='hidden' name='next_gravar' id='next_gravar' value='nao'>\n";
			echo "<input type='hidden' name='next_qtde'   id='next_qtde'   value=''>\n";
			echo "<input type='button' name='next2' id='next2' value='+ Incluir mais um item' onClick=\"proximo_item(this,'$extrato','$extrato_devolucao','$produto_acabado','$linha','$distribuidor','$serie','tbl_pecas_$i');return true;\" style='display:none'>\n";
			echo "<input type='button' name='next' id='next' value='+ Incluir mais um item' onClick=\"proximo_item(this,'$extrato','$extrato_devolucao','$produto_acabado','$linha','$distribuidor','$serie','tbl_pecas_$i');return true;\">\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</tfoot>\n";
		}

		echo "</table>\n";

		if ($aliq_final > 0) $total_valor_icms = $total_base_icms * $aliq_final / 100;

	

		if (strlen ($distribuidor) > 0 AND $distribuidor <> "null" ) {
			$condicao_1 = " tbl_extrato_devolucao.distribuidor = $distribuidor ";
		}else{
			$condicao_1 = " tbl_extrato_devolucao.distribuidor IS NULL ";
			$distribuidor = "null";
		}

		if ($produto_acabado == "TRUE") {
			$condicao_2 = " tbl_extrato_devolucao.produto_acabado IS TRUE ";
			$pa = "'t'";
		}else{
			$condicao_2 = " tbl_extrato_devolucao.produto_acabado IS NOT TRUE ";
			$pa = "'f'";
		}


		//$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato AND $condicao_1 AND $condicao_2 AND linha = $linha AND (((serie='$serie' OR serie IS NULL) and nota_fiscal IS NULL) OR ( (serie='$serie' OR serie IS NULL) and nota_fiscal IS NOT NULL))";

// 		$sql = "SELECT nota_fiscal FROM tbl_extrato_devolucao WHERE extrato = $extrato AND $condicao_1 AND $condicao_2 AND linha = $linha AND (((serie='$serie' OR serie IS NULL) and nota_fiscal IS NULL) OR ( (serie='$serie' OR serie IS NULL) and nota_fiscal IS NOT NULL))";
// 
// 		$resNF = pg_exec ($con,$sql);
// 		$numero_linhas=pg_numrows ($resNF);
// 
// 		if ($numero_linhas == 0) {
// 			if ($extrato >= 30437) {/*
// 				$sql = "INSERT INTO tbl_extrato_devolucao (extrato, linha, distribuidor,produto_acabado,serie) VALUES ($extrato,$linha,$distribuidor,$pa,'$serie')";
// 				$resZ = pg_exec ($con,$sql);
// 
// 				$sql = "SELECT CURRVAL ('seq_extrato_devolucao')";
// 				$resZ = pg_exec ($con,$sql);
// 				$extrato_devolucao = pg_result ($resZ,0,0);
// 				$nota_fiscal = "";*/
// 			}
// 
// 		}else{
// 			$nota_fiscal = pg_result ($resNF,0,nota_fiscal);
// 			$extrato_devolucao = pg_result ($resNF,0,extrato_devolucao);
// 		}
// 
// 		$extdev = $extrato_devolucao ;

// 		$que = "SELECT count(*) FROM tbl_extrato_devolucao 
// 			WHERE extrato_devolucao = $extrato_devolucao
// 			AND nota_fiscal IS NOT NULL";
// 		$resQ = pg_exec ($con,$que);
// 		$digitou_nota = pg_result ($resQ,0,0);

		echo "<div id='total_nota_$i' style='display:inline'>";
		if (strlen($nota_fiscal)>0){
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
			echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
			echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";
		}
		echo "</div>";	

		if ($nota_fiscal==0){
			echo "<input type='button' id='fechar' value='Finalizar Esta Nota' name='fechar' onclick=\"javascript:concluir_nota('$extrato','$extrato_devolucao','rodape_$i','total_nota_$i');this.style.display='none';return false;\"><br>";
		}

		if (strlen ($nota_fiscal) == 0) {
			echo "\n";
			echo "<div id='rodape_$i' style='display:none'>";
			echo "<form method='post' action='$PHP_SELF' name='frm_devol' onSubmit=\"return verificar_nota('nota_fiscal_$extrato_devolucao')\">";
			echo "<input type='hidden' name='extrato' value='$extrato'>";
			echo "<input type='hidden' name='extrato_devolucao' value='$extrato_devolucao'>";
			echo "<input type='hidden' id='total_nota' name='total_nota' value='$total_nota'>\n";
			echo "<input type='hidden' id='base_icms' name='base_icms' value='$total_base_icms'>\n";
			echo "<input type='hidden' id='valor_icms' name='valor_icms' value='$total_valor_icms'>\n";
			echo "<input type='hidden' id='base_ipi' name='base_ipi' value='$total_base_ipi'>\n";
			echo "<input type='hidden' id='valor_ipi' name='valor_ipi' value='$total_valor_ipi'>\n";
			echo "<input type='hidden' id='botao_acao' name='botao_acao' value='$extrato_devolucao'>\n";
			echo "<center>";
			echo "<b>Confirme a emissão da sua Nota de Devolução</b><br>Este número não poderá ser alterado<br>";
			echo "<br>Número da Nota: <input type='text' name='nota_fiscal_$extrato_devolucao' size='6' maxlength='6' value='$nota_fiscal'>";
			echo "<p><input type='submit' name='btn_acao' id='btn_acao' value='Confirmar Nota de Devolução'>";
			//echo " <input type='button' value='Fechar esta Nota' name='fechar_nota' onclick=\"javascript:fechar_nota('$extrato','$extdev','$produto_acabado','$linha','$distribuidor','$serie');return false;\">";
			echo "</form>";
			echo "</div>";	
			echo "<br><hr><br>";
			$botao = 1 ;
		}else{
			echo "<h1><center>Nota de Devolução $nota_fiscal</center></h1>";
			echo "<p>";
			echo "<hr>";
			$botao = 0 ;
		}
		if (strlen($nota_fiscal)==0) {
  			$i=1000;
  			break;
		}
	}


	$sql = "SELECT  tbl_os.os                                                         ,
			tbl_os.sua_os                                                     ,
			TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_ressarcimento,
			tbl_produto.referencia                       AS produto_referencia,
			tbl_produto.descricao                        AS produto_descricao ,
			tbl_admin.login
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING(os_produto)
		JOIN tbl_os_extra   USING(os)
		LEFT JOIN tbl_admin      ON tbl_os.troca_garantia_admin   = tbl_admin.admin
		LEFT JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_os_extra. extrato = $extrato
		AND  tbl_os.ressarcimento  IS TRUE
		AND  tbl_os.troca_garantia IS TRUE";

	$resX = pg_exec ($con,$sql);
	if(pg_numrows($resX)>0 AND strlen($nota_fiscal)>0){

		echo "Nota de Devolução de <b>Produtos Ressarcidos</b>" ;
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
		echo "<td>CFOP <br> <b>$cfop</b> </td>";
		echo "<td>Emissao <br> <b>$data</b> </td>";
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
		echo "<td><b>Ressarcimento</b></td>";
		echo "<td><b>Responsavel</b></td>";
		echo "<td><b>OS</b></td>";
		echo "</tr>";
	
		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
	
			$sua_os             = pg_result ($resX,$x,sua_os);
			$produto_referencia = pg_result ($resX,$x,produto_referencia);
			$produto_descricao  = pg_result ($resX,$x,produto_descricao);
			$data_ressarcimento = pg_result ($resX,$x,data_ressarcimento);
			$quem_trocou        = pg_result ($resX,$x,login);
	
			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
			echo "<td align='left'>$produto_referencia</td>";
			echo "<td align='left'>$produto_descricao</td>";
			echo "<td align='left'>$data_ressarcimento</td>";
			echo "<td align='right'>$quem_trocou</td>";
			echo "<td align='right'>$sua_os</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

}else{

	echo "<h1><center> Extrato de Mão-de-obra Liberado. Recarregue a página. </center></h1>";
	$sql =	"UPDATE tbl_extrato_extra SET
				nota_fiscal_devolucao              = '000000' ,
				valor_total_devolucao              = 0        ,
				base_icms_devolucao                = 0        ,
				valor_icms_devolucao               = 0        ,
				nota_fiscal_devolucao_distribuidor = '000000' ,
				valor_total_devolucao_distribuidor = 0        ,
				base_icms_devolucao_distribuidor   = 0        ,
				valor_icms_devolucao_distribuidor  = 0
			WHERE extrato = $extrato;";
	//$res = pg_exec ($con,$sql);

}
?>

<p><p>

<? include "rodape.php"; ?>
