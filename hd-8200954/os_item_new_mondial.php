<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include 'ajax_cabecalho.php';


if($ajax=='peca'){
	if(strlen(trim($referencia))>4) {
		$sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_query ($con,$sql) ;
		$produto = pg_fetch_result ($res,0,produto);

		$sql = "SELECT peca,referencia,descricao
				FROM tbl_peca
				JOIN tbl_lista_basica USING (peca)
				WHERE referencia               = '$referencia'
				AND   tbl_peca.fabrica         = $login_fabrica
				AND   tbl_lista_basica.produto = $produto
				AND   tbl_peca.ativo  IS     TRUE
				AND   produto_acabado IS NOT TRUE;";

		$sql = "SELECT peca,referencia,descricao
				FROM tbl_peca
				WHERE referencia               = '$referencia'
				AND   tbl_peca.fabrica         = $login_fabrica
				AND   tbl_peca.ativo  IS     TRUE
				AND   produto_acabado IS NOT TRUE;";
		$res = pg_query ($con,$sql) ;
		if (pg_num_rows($res)>0) {
			$descricao = pg_fetch_result ($res,0,descricao);
			echo "ok|$descricao";
		}else echo "NO|NO";
	}else echo "NO|NO";
	exit;
}

if($ajax=='defeito_constatado'){
	if(strlen(trim($defeito_constatado))>2){

		$sql = "SELECT  tbl_linha.linha    ,
						tbl_familia.familia
				FROM tbl_os
				JOIN tbl_produto USING(produto)
				JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
				JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.os      = $os";
		$res = pg_query ($con,$sql) ;
		$produto_linha   = pg_fetch_result ($res,0,linha);
		$produto_familia = pg_fetch_result ($res,0,familia);

		$sql = "
			SELECT DISTINCT (tbl_diagnostico.defeito_constatado),
					tbl_defeito_constatado.descricao,
					tbl_defeito_constatado.codigo
			FROM tbl_diagnostico
			JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			WHERE tbl_diagnostico.linha   = $produto_linha
			AND   tbl_diagnostico.familia = $produto_familia
			AND   tbl_diagnostico.ativo   = 't'
			AND   tbl_defeito_constatado.codigo = '$defeito_constatado'
			ORDER BY tbl_defeito_constatado.descricao";
		$res = pg_query ($con,$sql) ;
		if (pg_num_rows($res)>0){
			$defeito_constatado = pg_fetch_result ($res,0,defeito_constatado);
			$codigo             = pg_fetch_result ($res,0,codigo);
			$descricao          = pg_fetch_result ($res,0,descricao);
			echo "ok|$defeito_constatado|$codigo|$descricao";
		}else echo "NO|NO $sql";
	}else echo "NO|NO";
	exit;
}

if($ajax=='defeito_constatado_solucao'){
	if(strlen(trim($defeito_constatado))>2 ){

		$sql = "SELECT  tbl_linha.linha    ,
						tbl_familia.familia
				FROM tbl_os
				JOIN tbl_produto USING(produto)
				JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
				JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.os      = $os";
		$res = pg_query ($con,$sql) ;
		$produto_linha   = pg_fetch_result ($res,0,linha);
		$produto_familia = pg_fetch_result ($res,0,familia);

		$sql = "
			SELECT DISTINCT (tbl_diagnostico.defeito_constatado),
					tbl_defeito_constatado.descricao,
					tbl_defeito_constatado.codigo,
					tbl_solucao.solucao,
					tbl_solucao.descricao as solucao_descricao
			FROM tbl_diagnostico
			JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			JOIN tbl_solucao            on tbl_diagnostico.solucao            = tbl_solucao.solucao
			WHERE tbl_diagnostico.linha   = $produto_linha
			AND   tbl_diagnostico.familia = $produto_familia
			AND   tbl_diagnostico.ativo   = 't'
			AND   tbl_defeito_constatado.codigo = '$defeito_constatado'
			tbl_
			ORDER BY tbl_defeito_constatado.descricao";
		$res = pg_query ($con,$sql) ;
		if (pg_num_rows($res)>0){
			$defeito_constatado = pg_fetch_result ($res,0,defeito_constatado);
			$codigo             = pg_fetch_result ($res,0,codigo);
			$descricao          = pg_fetch_result ($res,0,descricao);
			echo "ok|$defeito_constatado|$codigo|$descricao";
		}else echo "NO|NO $sql";
	}else echo "NO|NO";
	exit;
}
if(strlen($_GET["peca_referencia"]) >0 AND $_GET["peca_troca"]=="sim"){
	$referencia = trim($_GET["peca_referencia"]);
	$sql  = "SELECT peca
			FROM tbl_peca
			WHERE fabrica = $login_fabrica
			AND   referencia ='$referencia'
			AND   troca_obrigatoria IS TRUE";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		echo "sim";
	}
	exit;
}


$atualiza_serie_trocada = $_GET['atualiza_serie_trocada'];
if(strlen($atualiza_serie_trocada)==0){
	$atualiza_serie_trocada = $_POST['atualiza_serie_trocada'];
}

if(strlen($atualiza_serie_trocada)>0){
	$os_item        = $_GET["os_item"];
	$serie_trocada  = $_GET["serie_trocada"];
	if(strlen($os_item)>0 and strlen($serie_trocada)>0){
		$sql = "UPDATE tbl_os_item SET peca_serie_trocada ='$serie_trocada'
				WHERE os_item = $os_item";
		//echo $sql;exit;
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro)==0){
			echo "Atualizado com Sucesso!";
		}else{
			echo "Ocorreu o seguinte erro $msg_erro";
		}
	}
exit;
}


if(strlen($os)>0 and $defeito=='defeito'){
	echo "<h2 style='font-family:Verdana'>Defeito Constatado</h2>";
	if(strlen(trim($defeito_constatado_codigo))>1 OR strlen(trim($defeito_constatado_descricao))>2 OR $login_fabrica==43){

		$sql = "SELECT  tbl_linha.linha    ,
						tbl_familia.familia
				FROM tbl_os
				JOIN tbl_produto USING(produto)
				JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
				JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.os      = $os";
		$res = pg_query ($con,$sql) ;
		$produto_linha   = pg_fetch_result ($res,0,linha);
		$produto_familia = pg_fetch_result ($res,0,familia);
		if(strlen($defeito_constatado_codigo)>0)
		$sql_a1 = " AND  tbl_defeito_constatado.codigo like '%$defeito_constatado_codigo%' ";
		if(strlen($defeito_constatado_descricao)>0)
		$sql_a1 = " AND  upper(tbl_defeito_constatado.descricao) like upper('%$defeito_constatado_descricao%') ";


		$sql = "
			SELECT DISTINCT (tbl_diagnostico.defeito_constatado),
					tbl_defeito_constatado.descricao,
					tbl_defeito_constatado.codigo
			FROM tbl_diagnostico
			JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			WHERE tbl_diagnostico.linha   = $produto_linha
			AND   tbl_diagnostico.familia = $produto_familia
			AND   tbl_diagnostico.ativo   = 't'
			$sql_a1
			ORDER BY tbl_defeito_constatado.descricao";

		$res = pg_query ($con,$sql) ;
		if (pg_num_rows($res)>0){

			echo "<table style='font-family:verdana;font-size:12px;'>";
			echo "<tr bgcolor='#336699' style='color:#FFFFFF'>";

			if ($login_fabrica<>43) echo "<Th>Código</th>";

			echo "<Th>Descrição</th>";
			echo "</tr>";
			for($i=0;$i<pg_num_rows($res);$i++){

				$defeito_constatado = pg_fetch_result ($res,$i,defeito_constatado);
				$codigo             = pg_fetch_result ($res,$i,codigo);
				$descricao          = pg_fetch_result ($res,$i,descricao);
				echo "<tr>";

				if ($login_fabrica<>43) echo "<td><a href=\"javascript: defeito_constatado.value='$defeito_constatado';defeito_constatado_codigo.value='$codigo';defeito_constatado_descricao.value='$descricao';this.close();\">$codigo</a></td>";

				echo "<td><a href=\"javascript: defeito_constatado.value='$defeito_constatado';defeito_constatado_codigo.value='$codigo';defeito_constatado_descricao.value='$descricao';this.close();\">$descricao</a></td>";
				echo "</tr>";

			}
			echo "</table>";
		}else if ($login_fabrica<>43) {
			echo "<h4 style='color:#FF0000'>Nenhum defeito com o código: $defeito_constatado_codigo</h4>";
		} else {
			echo "<h4 style='color:#FF0000'>Nenhum defeito com a descrição: $defeito_constatado_descricao</h4>";
		}
	}else if ($login_fabrica<>43) {
		echo "<h4 style='color:#FF0000'>Nenhum defeito com o código: $defeito_constatado_codigo</h4>";
	} else {
		echo "<h4 style='color:#FF0000'>Nenhum defeito com a descrição: $defeito_constatado_descricao</h4>";
	}
	echo "<br><center><a href='javascript:this.close();'>[Fechar]</a></center>";
	exit;
}

$msg_erro = "";
$msg_previsao = "";

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query ($con,$sql);
$pedir_causa_defeito_os_item       = pg_fetch_result ($res,0,pedir_causa_defeito_os_item);
$pedir_defeito_constatado_os_item  = pg_fetch_result ($res,0,pedir_defeito_constatado_os_item);
$pedir_defeito_reclamado_descricao = pg_fetch_result ($res,0,pedir_defeito_reclamado_descricao);
$pedir_defeito_reclamado_descricao = 'f';
$ip_fabricante = trim (pg_fetch_result ($res,0,ip_fabricante));
$ip_acesso     = $_SERVER['REMOTE_ADDR'];
$os_item_admin = "null";

if (strlen($_GET['reabrir']) > 0)     $reabrir = $_GET['reabrir'];
if (strlen($_GET['os']) > 0)          $os = $_GET['os'];
if (strlen($_POST['os']) > 0)         $os = $_POST['os'];
if (strlen($_POST['os_int']) > 0)         $os = $_POST['os_int'];

$defeito_reclamado= $_POST['xxdefeito_reclamado'];
$sql = "SELECT  tbl_os.sua_os,
				tbl_os.fabrica,
				tipo_atendimento
		FROM    tbl_os
		WHERE   tbl_os.os = $os";
$res = pg_query ($con,$sql) ;

if (@pg_num_rows($res) > 0) {
	if (pg_fetch_result ($res,0,fabrica) <> $login_fabrica ) {
		header ("Location: os_cadastro.php");
		exit;
	}
}

$tipo_atendimento = pg_fetch_result($res,0,tipo_atendimento);
$sua_os = trim(pg_fetch_result($res,0,sua_os));


if($login_fabrica==1 AND strlen($os)>0){
	header("Location: os_item.php?os=$os");
	exit;
}


if(strlen($_GET['os'])>0){
	$os=$_GET['os'];
	$sql = "SELECT  motivo_atraso ,
					observacao    ,
					os_reincidente,
					obs_reincidencia
			FROM tbl_os
			WHERE os = $os
			AND fabrica = $login_fabrica";

	$res = pg_query($con,$sql);

	$motivo_atraso    = pg_fetch_result($res,0,motivo_atraso);
	$observacao       = pg_fetch_result($res,0,observacao);
	$os_reincidente   = pg_fetch_result($res,0,os_reincidente);
	$obs_reincidencia = pg_fetch_result($res,0,obs_reincidencia);

	if($os_reincidente=='t' AND strlen($obs_reincidencia )==0 ){
		header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
	}
}


if (strlen($reabrir) > 0) {
	$sql = "SELECT count(*)
				FROM tbl_os_item
				JOIN tbl_os_produto USING(os_produto)
				JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				WHERE os = $os
				AND fabrica = $login_fabrica
				AND tbl_servico_realizado.troca_produto IS TRUE;";
	$res = pg_query ($con,$sql) ;
	if (pg_fetch_result ($res,0,0) == 0) {
		$sql = "UPDATE tbl_os SET data_fechamento = null, finalizada = null
				WHERE  tbl_os.os      = $os
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	else{
		$msg_erro .= "Esta OS não pode ser reaberta pois a solução foi a troca do produto.";
		echo "<script language='javascript'>alert('Esta os não pode ser reaberta pois o produto foi trocado pela fábrica'); history.go(-1);</script>";
		exit();
	}
}


if (strlen($_POST['$qtde_itens_mostrar']) > 0) $qtde_itens_mostrar = $_POST['$qtde_itens_mostrar'];

//adicionado por Fabio 02/01/2007- numero de itens na OS
if($login_fabrica<>30) $qtde_itens_mostrar="";

if (isset($_GET['n_itens']) AND strlen($_GET['n_itens'])>0){
	$qtde_itens_mostrar = $_GET['n_itens'];
	if($login_fabrica <> 15){
		if ($qtde_itens_mostrar>10)$qtde_itens_mostrar=10;
		if ($qtde_itens_mostrar<0)$qtde_itens_mostrar=3;
	}else{
		if ($qtde_itens_mostrar>20)$qtde_itens_mostrar=20;
		if ($qtde_itens_mostrar<0)$qtde_itens_mostrar=5;
	}
}else if($login_fabrica<>30){
	$qtde_itens_mostrar=3;
}

$numero_pecas_faturadas=0;

$os_item = trim($_GET ['os_item']);

if($os_item > 0){

	if($os_item_old != $os_item){

		$os_item_old = $os_item;
		//seleciona a os_produto que contem a os_item quem não geraam pedido
		$sql = "SELECT os_produto FROM tbl_os_item WHERE os_item = $os_item AND pedido IS NULL";

		$res = pg_query ($con,$sql);

		if(pg_num_rows($res) == 1){

			$os_produto = pg_fetch_result($res,0,os_produto);

			#HD 15489
			$sql = "UPDATE tbl_os_produto SET
						os = 4836000
					WHERE os_produto = $os_produto";
			$res = pg_query ($con,$sql);

			#HD 15489
			if (1==2){
				$sql = "DELETE FROM tbl_os_item WHERE os_item = $os_item ";
				$res = pg_query ($con,$sql);

				//verifica se tem os_item amarrada ao os_produto - caso nao tenha ele apaga o produto
				$sql = "SELECT count(os_produto) as os_produto_count FROM tbl_os_item WHERE os_produto = '$os_produto'; " ;

				$res = pg_query($con,$sql);

				$os_produto_count = pg_fetch_result($res,0,os_produto_count);

				if( $os_produto_count == 0 ){

					$sql = "DELETE FROM tbl_os_produto WHERE os_produto = '$os_produto' AND os = '$os' ; " ;

					$res = pg_query($con,$sql);
					$msg_erro_item .= "Item excluido com sucesso!";
				}
			}

		}else{
			$msg_erro_item .= "Não foi encontrado o item.";
		}
	}else{ $msg_erro_item .= "Não foi encontrado o item."; }
}

$btn_acao     = strtolower ($_POST['btn_acao']);
$btn_imprimir = strtolower ($_POST['btn_imprimir']);

if ($btn_acao == "gravar") {
	$res = pg_query ($con,"BEGIN TRANSACTION");
	$defeito_constatado = $_POST ['defeito_constatado'];
	$data_fechamento = $_POST['data_fechamento'];
	if (strlen($data_fechamento) > 0){
		$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
		if($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro = "Data de fechamento maior que a data de hoje.";
	}
//
	if (strlen($data_fechamento) > 0){
		$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
		if($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro = "Data de fechamento maior que a data de hoje.";
	}


	if (strlen ($msg_erro) == 0) {
		if (strlen($defeito_constatado) == 0) $defeito_constatado = 'null';

		if(strlen($msg_erro)==0){
			if (strlen ($defeito_constatado) > 0) {
				$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
						WHERE  tbl_os.os    = $os
						AND    tbl_os.posto = $login_posto;";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	

		if(strlen($msg_erro)==0){
			$peca_pai = $_POST['peca_pai'];
			if (strlen ($peca_pai) > 0) {
				$sql = "UPDATE tbl_os_extra SET peca_pai = $peca_pai
						WHERE  tbl_os_extra.os    = $os;";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		$defeito_reclamado = $_POST['defeito_reclamado'];
		if($pedir_defeito_reclamado_descricao == 't') {
			if(strlen($defeito_reclamado) == 0) {
				$defeito_reclamado = 'null';
			}
		}

		if(strlen($msg_erro)==0){
			if (strlen ($defeito_reclamado) > 0) {
					$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado
							WHERE  tbl_os.os    = $os
							AND    tbl_os.posto = $login_posto;";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
			}
		}
	}


	if (strlen ($msg_erro) == 0) {
		$xcausa_defeito = $_POST ['causa_defeito'];
		if (strlen ($xcausa_defeito) == 0) $xcausa_defeito = "null";
		if (strlen ($xcausa_defeito) > 0) {
			$sql = "UPDATE tbl_os SET causa_defeito = $xcausa_defeito
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		$x_solucao_os = $_POST['solucao_os'];
		if (strlen($x_solucao_os) == 0) $x_solucao_os = 'null';
		else                            $x_solucao_os = "'".$x_solucao_os."'";
		$sql = "UPDATE tbl_os SET solucao_os = $x_solucao_os
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";
		$res = @pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	$obs = trim($_POST["obs"]);
	if (strlen($obs) > 0) $obs = "'".$obs."'";
	else                   $obs = "null";

	$tecnico_nome = trim($_POST["tecnico_nome"]);
	if (strlen($tecnico_nome) > 0) $tecnico_nome = "'".$tecnico_nome."'";
	else                   $tecnico_nome = "null";

	if(($pedir_defeito_reclamado_descricao == 't') AND $defeito_reclamado == 'null' ){
		$defeito_reclamado_descricao_os = $_POST["defeito_reclamado_descricao_os"];
		if(strlen($defeito_reclamado_descricao_os) == 0) {
			$msg_erro = "Por favor, preencha o campo do defeito reclamado.";
		}
	}else{//PARA OUTRAS FÁBRICAS SETA NULL
		$defeito_reclamado_descricao_os = 'null';
	}

	$valores_adicionais = trim($_POST["valores_adicionais"]);
	$valores_adicionais = str_replace (",",".",$valores_adicionais);
	if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

	$justificativa_adicionais = trim($_POST["justificativa_adicionais"]);
	if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
	else                   $justificativa_adicionais = "null";

	if (strlen ($type) > 0) $type = "'".trim($_POST['type'])."'";
	else                    $type = 'null';

	$qtde_km = trim($_POST["qtde_km"]);
	$qtde_km = str_replace (",",".",$qtde_km);

	if($login_fabrica==3){
		if (strlen($qtde_km) == 0) $qtde_km = "0";
	}else{
		if (strlen($qtde_km) == 0) $qtde_km = " qtde_km ";
	}

	$justificativa_autorizacao = "NULL";

	if(strlen($msg_erro)==0){
		$sql = "UPDATE  tbl_os SET obs              = $obs                             ,
						tecnico_nome                = $tecnico_nome                    ,
						codigo_fabricacao           = '$codigo_fabricacao'             ,
						fabricacao_produto          = '$fabricacao_produto'            ,
						valores_adicionais          = $valores_adicionais              ,
						justificativa_adicionais    = $justificativa_adicionais        ,
						defeito_reclamado_descricao = '$defeito_reclamado_descricao_os',
						qtde_km                     = $qtde_km                         ,
						type                        = $type
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";
		$res = @pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if(strlen($msg_erro)==0){ //HD 79185
		#HD 13618
		$sqlT = "SELECT tbl_produto.troca_obrigatoria
				FROM   tbl_produto
				JOIN   tbl_os ON tbl_os.produto = tbl_produto.produto
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";
		#echo nl2br($sql);
		$resT = pg_query($con,$sqlT);
		if (pg_num_rows($resT) > 0) {
			$troca_obrigatoria = pg_fetch_result($resT,0,troca_obrigatoria);
		}
	}

	$orcamento_garantia = "";

	if (strlen ($msg_erro) == 0) {

		$qtde_item = $_POST['qtde_item'];

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$xos_item        = $_POST['os_item_'        . $i];
			$xorcamento_item = $_POST['orcamento_item_' . $i];
			$xos_produto     = $_POST['os_produto_'     . $i];
			$xproduto        = $_POST['produto_'        . $i];
			$xserie          = $_POST['serie_'          . $i];
			$xposicao        = $_POST['posicao_'        . $i];
			$xpeca           = $_POST['peca_'           . $i];
			$xqtde           = $_POST['qtde_'           . $i];
			$xpeca_serie     = $_POST['peca_serie_'     . $i];
			$xpeca_serie_trocada = $_POST['peca_serie_trocada_' . $i];

			//FIXO PARA MONDIAL
			$xdefeito        = $_POST['defeito_'.$i       ]; 
			$xservico        = $_POST['servico_'.$i       ];
			$xpcausa_defeito = $_POST['pcausa_defeito' ];

			$xpreco_orcamento= $_POST['preco_orcamento_' . $i]; 

			$xpreco_orcamento = str_replace ("," , "." ,$xpreco_orcamento);
			$xpreco_orcamento = str_replace ("-" , "" , $xpreco_orcamento);
			$xpreco_orcamento = str_replace ("/" , "" , $xpreco_orcamento);
			$xpreco_orcamento = str_replace (" " , "" , $xpreco_orcamento);

			if ($xservico<>643 AND $xservico<>644){
				$xpreco_orcamento = "";
			}

			$xproduto = str_replace ("." , "" , $xproduto);
			$xproduto = str_replace ("-" , "" , $xproduto);
			$xproduto = str_replace ("/" , "" , $xproduto);
			$xproduto = str_replace (" " , "" , $xproduto);

			$xpeca    = str_replace ("." , "" , $xpeca);
			$xpeca    = str_replace ("-" , "" , $xpeca);
			$xpeca    = str_replace ("/" , "" , $xpeca);
			$xpeca    = str_replace (" " , "" , $xpeca);

			if (strlen($xserie) == 0) $xserie = 'null';
			else                      $xserie = "'" . $xserie . "'";

			if (strlen($xpeca_serie) == 0) $xpeca_serie = 'null';
			else                           $xpeca_serie = "'" . $xpeca_serie . "'";

			if (strlen($xpeca_serie_trocada) == 0) $xpeca_serie_trocada = 'null';
			else                                   $xpeca_serie_trocada = "'" . $xpeca_serie_trocada . "'";

			if (strlen($xposicao) == 0) $xposicao = 'null';
			else                        $xposicao = "'" . $xposicao . "'";

			$xadmin_peca      = $_POST["admin_peca_"     . $i]; //aqui
			if(strlen($xadmin_peca)==0) $xadmin_peca ="null"; //aqui
			if($xadmin_peca=="P")$xadmin_peca ="null"; //aqui

			if ((strlen ($xos_produto) > 0 or strlen($xorcamento_item)>0) AND strlen($xpeca) == 0) {
				if (strlen ($xos_produto) > 0){
					$sql = "DELETE FROM tbl_os_produto
							WHERE  tbl_os_produto.os         = $os
							AND    tbl_os_produto.os_produto = $xos_produto";
					#HD 15489
					$sql = "UPDATE tbl_os_produto SET
								os = 4836000
							WHERE os         = $os
							AND   os_produto = $xos_produto";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
				if (strlen($orcamento)>0 AND strlen ($xorcamento_item) > 0){
					$sql = "DELETE FROM tbl_orcamento_item
							WHERE  orcamento_item = $xorcamento_item
							AND orcamento = $orcamento";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$query = "UPDATE tbl_orcamento SET
								total_pecas	 = (SELECT SUM(preco*qtde)
												FROM tbl_orcamento_item
												WHERE orcamento = $orcamento)
							WHERE orcamento = $orcamento
							AND empresa = $login_fabrica";
					$orca = pg_query($con, $query);
					$msg_erro .= pg_errormessage($con);
				}
			}else{
				if ($login_fabrica == 3 && strlen($xpeca) > 0) {
					$sqlX = "SELECT referencia, TO_CHAR (previsao_entrega,'DD/MM/YYYY') AS previsao
							 FROM tbl_peca
							 WHERE UPPER(referencia_pesquisa) = UPPER('$xpeca')
							 AND   fabrica = $login_fabrica
							 AND   previsao_entrega > date(current_date + INTERVAL '20 days');";
					$resX = pg_query($con,$sqlX);
					if (pg_num_rows($resX) > 0) {
						$peca_previsao = pg_fetch_result($resX,0,referencia);
						$previsao      = pg_fetch_result($resX,0,previsao);

						$msg_previsao  = "O pedido da peça $peca_previsao foi efetivado. A previsão de disponibilidade desta peça será em $previsao. A fábrica tomará as medidas necessárias par o atendimento ao consumidor.";
					}
				}

				if (strlen($xpeca) > 0 and strlen($msg_erro) == 0) {
					$xpeca    = strtoupper ($xpeca);

					if (strlen ($msg_erro) > 0) {
					}

					if (strlen ($xqtde) == 0) $xqtde = "1";

					if (strlen ($xproduto) == 0) {
						$sql = "SELECT tbl_os.produto
								FROM   tbl_os
								WHERE  tbl_os.os      = $os
								AND    tbl_os.fabrica = $login_fabrica;";
						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) > 0) {
							$xproduto = pg_fetch_result ($res,0,0);
						}
					}else{
						$sql = "SELECT tbl_produto.produto
								FROM   tbl_produto
								JOIN   tbl_linha USING (linha)
								WHERE  tbl_produto.referencia_pesquisa = '$xproduto'
								AND    tbl_linha.fabrica = $login_fabrica
								";
								
								/*HD: 79762 03/03/2009 DEIXAR APENAS A BUSCA PELA LISTA BÁSICA MESMO QUANDO O PRODUTOS ESTIVER INATIVO*/
								if($login_fabrica <> 3 ) $sql .= " AND tbl_produto.ativo IS TRUE " ;

						$res = pg_query ($con,$sql);

						if (pg_num_rows ($res) == 0) {
							$msg_erro .= "Produto $xproduto não cadastrado";
							$linha_erro = $i;
						}else{
							$xproduto = pg_fetch_result ($res,0,produto);
						}
					}

					if (strlen ($msg_erro) == 0) {
						if (strlen($xos_produto) == 0){
							$sql = "INSERT INTO tbl_os_produto (
										os     ,
										produto,
										serie
									)VALUES(
										$os     ,
										$xproduto,
										$xserie
								);";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$res = pg_query ($con,"SELECT CURRVAL ('seq_os_produto')");
							$xos_produto  = pg_fetch_result ($res,0,0);
						}else{
							$sql = "UPDATE tbl_os_produto SET
										os      = $os      ,
										produto = $xproduto,
										serie   = $xserie
									WHERE os_produto = $xos_produto;";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}

						// Delete Orçamento caso exista
						if (strlen($xorcamento_item)>0){
							$sql = "DELETE FROM tbl_orcamento_item
									WHERE orcamento = $orcamento
									AND orcamento_item = $xorcamento_item";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}

						if (strlen ($msg_erro) > 0) {
							break ;
						}else{

							$xpeca = strtoupper ($xpeca);
							//HD 21425 não pode gravar produto acabado
							if (strlen($xpeca) > 0) {
								$sql = "SELECT tbl_peca.*
										FROM   tbl_peca
										WHERE  UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca')
										AND    tbl_peca.fabrica = $login_fabrica
										AND    tbl_peca.produto_acabado IS NOT TRUE;";
								$res = pg_query ($con,$sql);

								if (pg_num_rows ($res) == 0) {
									$msg_erro .= "Peça $xpeca não cadastrada";
									$linha_erro = $i;
								}else{
									$xpeca                    = pg_fetch_result ($res,0,peca);
									$intervencao_fabrica_peca = pg_fetch_result ($res,0,retorna_conserto);
									$troca_obrigatoria_peca   = pg_fetch_result ($res,0,troca_obrigatoria);
									$bloqueada_garantia_peca  = pg_fetch_result ($res,0,bloqueada_garantia);
									$bloqueada_peca_critica   = pg_fetch_result ($res,0,peca_critica);
									$intervencao_carteira     = pg_fetch_result ($res,0,intervencao_carteira);
									$previsao_entrega_peca    = pg_fetch_result ($res,0,previsao_entrega);
									$gera_troca_produto       = pg_fetch_result ($res,0,gera_troca_produto);
								}

								if (strlen($xdefeito) == 0) $msg_erro .= "Favor informar o defeito da peça"; #$defeito = "null";
								if (strlen($xservico) == 0) $msg_erro .= "Favor informar o serviço realizado"; #$servico = "null";

								if(strlen($xpcausa_defeito) == 0) $xpcausa_defeito = 'null';

								if (strlen ($msg_erro) == 0) {
									if (strlen($xos_item) == 0){
										$sql = "INSERT INTO tbl_os_item (
													os_produto        ,
													posicao           ,
													peca              ,
													qtde              ,
													defeito           ,
													causa_defeito     ,
													servico_realizado ,
													admin             ,
													peca_serie        ,
													peca_serie_trocada
												)VALUES(
													$xos_produto    ,
													$xposicao       ,
													$xpeca          ,
													$xqtde          ,
													$xdefeito       ,
													$xpcausa_defeito,
													$xservico       ,
													$xadmin_peca    ,
													$xpeca_serie    ,
													$xpeca_serie_trocada
											);";
										$res = @pg_query ($con,$sql);
										$msg_erro .= pg_errormessage($con);
									}else{
										$sql = "UPDATE tbl_os_item SET
													os_produto        = $xos_produto    ,
													posicao           = $xposicao       ,
													peca              = $xpeca          ,
													qtde              = $xqtde          ,
													defeito           = $xdefeito       ,
													causa_defeito     = $xpcausa_defeito,
													servico_realizado = $xservico       ,
													admin             = $xadmin_peca    ,
													peca_serie        = $xpeca_serie    ,
													peca_serie_trocada = $xpeca_serie_trocada
												WHERE os_item = $xos_item;";
										$res = @pg_query ($con,$sql);
										$msg_erro .= pg_errormessage($con);
									}

									if (strlen ($msg_erro) > 0) {
										break ;
									}
								}
							}
						}
						
					}
				}
			}
		}
	}

	# Caso gravou peças de orçamento, registrar...
	if (strlen($orcamento)>0 AND $gravou_pecas_orcamento == "sim"){
		$gravou_pecas_orcamento = "";

		$valor_mo_orcamento = trim($_POST["valor_mo_orcamento"]);
		$orcamento_aprovado = trim($_POST["orcamento_aprovado"]);

		$valor_mo_orcamento = str_replace ("," , "." ,$valor_mo_orcamento);
		$valor_mo_orcamento = str_replace ("-" , "" , $valor_mo_orcamento);
		$valor_mo_orcamento = str_replace ("/" , "" , $valor_mo_orcamento);
		$valor_mo_orcamento = str_replace (" " , "" , $valor_mo_orcamento);

		if (strlen($valor_mo_orcamento)==0){
			$valor_mo_orcamento = " NULL ";
		}

		if (strlen($orcamento_aprovado)>0){
			$orcamento_aprovado = "'t'";
		}else{
			$orcamento_aprovado = "NULL";
		}

		if (strlen($orcamento)==0){
			$query = "INSERT INTO tbl_orcamento (empresa,os,total_mao_de_obra,aprovado) VALUES ($login_fabrica,$os,$valor_mo_orcamento,$orcamento_aprovado)";
			$orca = pg_query($con, $query);
			$msg_erro .= pg_errormessage($con);
			$query = "SELECT currval('tbl_orcamento_orcamento_seq') AS orcamento";
			$orca = pg_query($con, $query);
			$orcamento = pg_fetch_result($orca,0,orcamento);
		}else{
			$query = "UPDATE tbl_orcamento SET
						total_mao_de_obra= $valor_mo_orcamento,
						total_pecas	 = (SELECT
									SUM(preco*qtde)
									FROM tbl_orcamento_item
									WHERE orcamento=$orcamento
									),
						aprovado = $orcamento_aprovado
					WHERE orcamento = $orcamento
					AND empresa = $login_fabrica";
			$orca = pg_query($con, $query);
			$msg_erro .= pg_errormessage($con);
		}

		#Criar Help-Desk
		$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento = $orcamento";
		$res_chamado = pg_query($con, $sql);
		if(pg_num_rows($res_chamado)>0){
			$hd_chamado = pg_fetch_result($res_chamado,0,hd_chamado);
		}else{
			$sql = "INSERT INTO tbl_hd_chamado (posto,titulo,orcamento) VALUES ($login_posto,'Orçamento da OS Nº $sua_os',$orcamento)";
			$res_chamado = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT currval('seq_hd_chamado')";
			$res_chamado = pg_query($con, $sql);
			$hd_chamado = pg_fetch_result($res_chamado,0,0);
		}

		$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado,comentario) VALUES ($hd_chamado,'Ordem de Serviço alterada. Aguardando aprovação')";
		$res_chamado = pg_query($con, $sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($orcamento)>0){
			$query = "UPDATE tbl_orcamento SET
						aprovado = NULL,
						data_aprovacao = NULL,
						data_reprovacao = NULL,
						motivo_reprovacao = NULL
					WHERE orcamento = $orcamento
					AND empresa = $login_fabrica";
			$orca = pg_query($con, $query);
			$msg_erro .= pg_errormessage($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res      = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($data_fechamento) > 0){
			if (strlen ($msg_erro) == 0) {
				$sql = "UPDATE tbl_os SET data_fechamento   = $xdata_fechamento
						WHERE  tbl_os.os    = $os
						AND    tbl_os.posto = $login_posto;";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}


	//HD 4291-Paulo
	if($login_posto == 4311) {
		$prateleira_box = strtoupper(trim($_POST['prateleira_box']));
		if (strlen ($prateleira_box) == 0) $prateleira_box = " ";
		if (strlen ($msg_erro) == 0 and strlen($prateleira_box) > 0) {
			$sql= "UPDATE tbl_os SET
						prateleira_box = '$prateleira_box'
						WHERE os=$os
						AND posto=$login_posto";
			$res = pg_query ($con,$sql);

			$msg_erro .= pg_errormessage($con);
		}
	}
	//HD 4291 Fim
	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		if ($login_fabrica<>2 AND $login_fabrica<>51 AND ($os_bloqueada_garantia=='t' OR $peca_mais_30_dias=='t' OR $os_com_intervencao=='t' OR $intervencao_previsao=='t' OR $os_com_intervencao_carteira=='t')) {
			header ("Location: os_justificativa_garantia.php?os=$os");
		}else{
			if($login_fabrica == 3 AND $btn_imprimir == 'imprimir'){
				header ("Location: os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar");
			}else{
				header ("Location: os_finalizada.php?os=$os");
			}
		}
		exit;
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($os) > 0) {
	#----------------- Le dados da OS --------------
	$sql = "SELECT  tbl_os.*                       ,
					tbl_produto.produto            ,
					tbl_produto.referencia         ,
					tbl_produto.descricao          ,
					tbl_produto.voltagem           ,
					tbl_produto.linha              ,
					tbl_produto.familia            ,
					tbl_produto.troca_obrigatoria  ,
					tbl_linha.nome AS linha_nome   ,
					tbl_posto_fabrica.codigo_posto ,
					tbl_os_extra.orientacao_sac    ,
					tbl_os_extra.peca_pai          ,
					tbl_os_extra.os_reincidente AS reincidente_os,
					tbl_os.prateleira_box                        ,
					tbl_os_extra.obs_adicionais,
					tbl_linha.informatica
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_produto USING (produto)
			LEFT JOIN    tbl_linha   ON tbl_produto.linha = tbl_linha.linha
			WHERE   tbl_os.os = $os";
	$res = pg_query ($con,$sql) ;

	$defeito_constatado = pg_fetch_result ($res,0,defeito_constatado);
	$aparencia_produto	= pg_fetch_result ($res,0,aparencia_produto);
	$acessorios			= pg_fetch_result ($res,0,acessorios);
	$causa_defeito      = pg_fetch_result ($res,0,causa_defeito);
	$linha              = pg_fetch_result ($res,0,linha);
	$informatica        = pg_fetch_result ($res,0,informatica);
	$linha_nome         = pg_fetch_result ($res,0,linha_nome);
	$consumidor_nome    = pg_fetch_result ($res,0,consumidor_nome);
	$sua_os             = pg_fetch_result ($res,0,sua_os);
	$type               = pg_fetch_result ($res,0,type);
	$produto_os         = pg_fetch_result ($res,0,produto);
	$produto_referencia = pg_fetch_result ($res,0,referencia);
	$produto_descricao  = pg_fetch_result ($res,0,descricao);
	$produto_voltagem   = pg_fetch_result ($res,0,voltagem);
	$produto_serie      = pg_fetch_result ($res,0,serie);
	$produto_serie_db   = pg_fetch_result ($res,0,serie);
	$qtde_produtos      = pg_fetch_result ($res,0,qtde_produtos);
	$obs                = pg_fetch_result ($res,0,obs);
	$codigo_posto       = pg_fetch_result ($res,0,codigo_posto);
	$defeito_reclamado  = pg_fetch_result ($res,0,defeito_reclamado);
	$defeito_reclamado_descricao_os = pg_fetch_result ($res,0,defeito_reclamado_descricao);
	$peca_pai           = pg_fetch_result ($res,0,peca_pai);
	$os_reincidente     = pg_fetch_result ($res,0,reincidente_os);
	$consumidor_revenda = pg_fetch_result ($res,0,consumidor_revenda);
	$solucao_os         = pg_fetch_result ($res,0,solucao_os);
	$tecnico_nome       = pg_fetch_result ($res,0,tecnico_nome);
	$codigo_fabricacao  = pg_fetch_result ($res,0,codigo_fabricacao);
	$valores_adicionais = pg_fetch_result ($res,0,valores_adicionais);
	$justificativa_adicionais = pg_fetch_result ($res,0,justificativa_adicionais);
	$qtde_km            = pg_fetch_result ($res,0,qtde_km);
	$produto_familia    = pg_fetch_result ($res,0,familia);
	$produto_linha      = pg_fetch_result ($res,0,linha);
	$troca_obrigatoria  = pg_fetch_result ($res,0,troca_obrigatoria);
	$fabricacao_produto = pg_fetch_result ($res,0,fabricacao_produto);
	//hd 24288
	//$autorizacao_domicilio = pg_fetch_result ($res,0,autorizacao_domicilio);


	$orientacao_sac	= pg_fetch_result ($res,0,orientacao_sac);
#	$orientacao_sac = html_entity_decode ($orientacao_sac,ENT_QUOTES);
#	$orientacao_sac = str_replace ("<br />","",$orientacao_sac);

	if($login_posto==4311){
		$prateleira_box = pg_fetch_result($res,0, prateleira_box);
	}

	if (strlen($os_reincidente) > 0) {
		$sql = "SELECT tbl_os.sua_os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os_reincidente
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = @pg_query ($con,$sql) ;

		if (pg_num_rows($res) > 0) $sua_os_reincidente = trim(pg_fetch_result($res,0,sua_os));
	}
}

#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto  ,
				tbl_fabrica.pergunta_qtde_os_item,
				tbl_fabrica.os_item_serie        ,
				tbl_fabrica.os_item_aparencia    ,
				tbl_fabrica.qtde_item_os
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$resX = pg_query ($con,$sql);
if (pg_num_rows($resX) > 0) {
	$os_item_subconjunto = pg_fetch_result ($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';

	$pergunta_qtde_os_item = pg_fetch_result ($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';

	$os_item_serie = pg_fetch_result ($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';

	$os_item_aparencia = pg_fetch_result ($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';

	$qtde_item = pg_fetch_result ($resX,0,qtde_item_os);
	if (strlen ($qtde_item) == 0) $qtde_item = 5;
}

$resX = pg_query ($con,"SELECT item_aparencia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica");
$posto_item_aparencia = pg_fetch_result ($resX,0,0);

$title = "Telecontrol - Assistência Técnica - Ordem de Serviço";
$body_onload = "javascript: document.frm_os.defeito_constatado.focus(); listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value); ";

$layout_menu = 'os';
include "cabecalho.php";



$imprimir        = $_GET['imprimir'];
$qtde_etiquetas  = $_GET['qtde_etiq'];

if (strlen ($os) == 0) $os = $_GET['os'];

if (strlen ($imprimir) > 0 AND strlen ($os) > 0 ) {
	echo "<script language='javascript'>";
	echo "window.open ('os_print.php?os=$os&qtde_etiquetas=$qtde_etiquetas','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
	echo "</script>";
}

include "javascript_pesquisas.php";
?>

<script type="text/javascript" src="js/jquery-1.4.2.js"></script>


<?
# Se a OS for uma OS de revenda, entra
if (strlen($os_revenda)>0 or $login_fabrica==3){
?>
<script type="text/javascript">

	function EscondeDiv(x){
		var campo = document.getElementById('retorno_serie_'+x);
		campo.style.display = "none";
	}
var http3 = new Array();
function atualizaserietrocada(os_item, serietrocada,x){

	os_item      = document.getElementById(os_item).value;
	serietrocada = document.getElementById(serietrocada).value;

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('retorno_serie_'+x);

	if (!campo) {
		return;
	}

	url = "<?$PHP_SELF;?>?atualiza_serie_trocada=true&os_item="+os_item+"&serie_trocada="+serietrocada;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
				campo.style.display = "block";
				window.setTimeout('EscondeDiv('+x+')',2000);
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}

function mostraDomicilio(campo,destino){
	if(campo.checked){
		document.getElementById(destino).style.display = "block";
	}else{
		document.getElementById(destino).style.display = "none";
	}
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

$().ready(function() {
	$("select[rel=servicos_realizados]").change(function(){
		var campo = $(this);
		$("#orcamento_mostra_"+campo.attr("alt")).hide();
	});
});
</script>

<?}?>


<?php include "javascript_calendario_new.php"; ?>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' src='ajax_produto.js'></script>
<script language="JavaScript">

$(function(){
		$("#fabricacao_produto").maskedinput("99/9999");
	});


function trim(str){
	while(str.charAt(0) == (" ") ){
		str = str.substring(1);
	}
	while(str.charAt(str.length-1) == " " ){
		str = str.substring(0,str.length-1);
	}
	return str;
}

function abreComunicadoPeca(i){
	var referencia = document.getElementById('peca_'+i).value;
	url = "pesquisa_comunicado_peca.php?referencia=" + referencia;
	window.open(url,"Comunicado","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
}

var http5 = new Array();

function checarComunicado(i){
	var imagem     = document.getElementById('imagem_comunicado_'+i);
	var referencia = document.getElementById('peca_'+i).value;
	imagem.style.visibility = "hidden";
	imagem.title = "Não há comunicados para esta peça.";
	if (referencia.length > 0){
		var curDateTime = new Date();
		http5[curDateTime] = createRequestObject();
		url = "os_item_comunicado_ajax.php?referencia="+escape(referencia);
		http5[curDateTime].open('get',url);
		http5[curDateTime].onreadystatechange = function(){
			if (http5[curDateTime].readyState == 4)
			{
				if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304)
				{
					var response = http5[curDateTime].responseText;
					if (response=="ok"){
						imagem.title = "Há comunicados para esta peça. Clique aqui para ler.";
						imagem.style.visibility = "visible";
					}
					else {
						imagem.title = "Não há comunicados para esta peça.";
						imagem.style.visibility = "hidden";
					}
				}
			}
		}
		http5[curDateTime].send(null);
	}
}


	function atualizaQtde(campo,campo2){
		if(campo && campo2){
			if ( campo.value.length == 0){
				campo2.value = '';
			}
			if ( campo.value.length > 0 && campo2.value.length == 0 ){
				campo2.value = 1;
			}
		}
	}


	function fnc_troca(os){
		alert('A troca de produto deve ser feita somente quando o reparo do produto necessita de troca de peças.');
		if (confirm('A Fábrica irá fazer a troca do produto. Confirmar a troca?')){
			window.location='<?=$PHP_SELF?>?os='+os+'&troca=1';
		}
	}


//funcao lista basica tectoy, posicao, serie inicial, serie final
function fnc_pesquisa_lista_basica2 (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
		if (tipo == "tudo") {
			url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}

		if (tipo == "referencia") {
			url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}

		if (tipo == "descricao") {
			url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto          = produto_referencia;
		janela.referencia       = peca_referencia;
		janela.descricao        = peca_descricao;
		janela.preco            = peca_preco;
		janela.qtde                     = peca_qtde;
		janela.focus();

}




function fnc_pesquisa_lista_basica_suggar (produto_referencia, peca_referencia, peca_descricao, peca_posicao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto          = produto_referencia;
        janela.referencia       = peca_referencia;
        janela.descricao        = peca_descricao;
        janela.posicao          = peca_posicao;
        janela.preco            = peca_preco;
        janela.qtde             = peca_qtde;
        janela.focus();

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

function pega_peca(os,referencia,descricao) {
	var ref = document.getElementById(referencia).value;
	if(document.getElementById(referencia).value.length > 0){
		url = "<?=$PHP_SELF?>?ajax=peca&referencia="+ref+"&os="+os;
		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4)
			{
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
				{
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
						document.getElementById(descricao).value = response[1];
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}
}
function pega_dc(os,id,referencia,descricao){
	var ref = document.getElementById(referencia).value;
	if(document.getElementById(referencia).value.length > 0){
		url = "<?=$PHP_SELF?>?ajax=defeito_constatado&defeito_constatado="+ref+"&os="+os;

		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4)
			{
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
				{
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
						document.getElementById(id).value = response[1];
						document.getElementById(referencia).value = response[2];
						document.getElementById(descricao).value = response[3];
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}
}
function fnc_pesquisa_dc (os, defeito_constatado, defeito_constatado_codigo, defeito_constatado_descricao) {
	var url = "";
	if (defeito_constatado != '') {
		url = "<?$PHP_SELF?>?defeito=defeito&os=" + os+"&defeito_constatado_codigo="+defeito_constatado_codigo.value+"&defeito_constatado_descricao="+defeito_constatado_descricao.value;

		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.defeito_constatado           = defeito_constatado          ;
		janela.defeito_constatado_codigo    = defeito_constatado_codigo   ;
		janela.defeito_constatado_descricao = defeito_constatado_descricao;
		janela.focus();
	}
}

function fnc_pesquisa_lista_basica (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto          = produto_referencia;
        janela.referencia       = peca_referencia;
        janela.descricao        = peca_descricao;
        janela.preco            = peca_preco;
        janela.qtde             = peca_qtde;
        janela.focus();

}

function fnc_pesquisa_peca_lista_mondial (produto_referencia, peca_referencia, peca_descricao, defeito, voltagem, tipo, peca_qtde) {
	var url = "peca_pesquisa_lista.php";
	if (tipo == "tudo") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&defeito=" + defeito.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim";
	}

	if (tipo == "referencia") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&defeito=" + defeito.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim";
	}

	if (tipo == "descricao") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim";
	}
<? if ($login_fabrica <> 2) { ?>
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
<? } ?>
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.defeito		= defeito;
		janela.qtde			= peca_qtde;
		janela.focus();
<? if ($login_fabrica <> 2) { ?>
	}else{
		alert("<? if($sistema_lingua == "ES"){ 
echo "Digite al minus 3 caracters";
}else{
echo "Digite pelo menos 3 caracteres!";
 } ?>");
	}
<? } ?>
}
function fnc_pesquisa_lista_basica_mondial (produto_referencia, peca_referencia, peca_descricao, defeito, voltagem, tipo, peca_qtde) {
        var url = "";
        if (tipo == "tudo") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value+ "&defeito=" + defeito.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto          = produto_referencia;
        janela.referencia       = peca_referencia;
        janela.descricao        = peca_descricao;
        janela.preco            = peca_preco;
        janela.qtde             = peca_qtde;
        janela.focus();

}
function fnc_pesquisa_peca_lista_sub (produto_referencia, peca_posicao, peca_referencia, peca_descricao) {
	var url = "";
	if (produto_referencia != '') {
		url = "peca_pesquisa_lista_subconjunto.php?produto=" + produto_referencia;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.posicao		= peca_posicao;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.focus();
	}
}

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&faturado=sim";
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&faturado=sim";
	}
	if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.posicao		= peca_posicao;
		janela.focus();
	}else{
		alert("Digite pelo menos 4 caracteres!");
	}
}

/* FUNÇÃO PARA buscar número de série e referencia do produto*/
function fnc_pesquisa_peca_serie (serie,peca_referencia,peca_descricao) {
	var url = "peca_pesquisa_serie.php?serie=" + serie;
	if (serie.length > 0 ) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.focus();
	}else{
		alert("Digite o número de série!");
	}
}

function listaSolucao(defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
		catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
			catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
		}
	}
		if(ajax) {
			document.forms[0].solucao_os.options.length = 1;
			idOpcao  = document.getElementById("opcoes");
			ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			ajax.onreadystatechange = function() {
				if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
				if(ajax.readyState == 4 ) {
					if(ajax.responseXML) {
						montaComboSolucao(ajax.responseXML);//após ser processado-chama fun
					} else {
						idOpcao.innerHTML = "Selecione o defeito constatado";//caso não seja um arquivo XML emite a mensagem abaixo
					}
				}
			}
			var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
			ajax.send(null);
		}
}

function montaComboSolucao(obj){
	var dataArray   = obj.getElementsByTagName("produto");
	if(dataArray.length > 0) {
		for(var i = 0 ; i < dataArray.length ; i++) {
			var item = dataArray[i];
			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "";
			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes");
			novo.value = codigo;
			novo.text  = nome;
			document.forms[0].solucao_os.options.add(novo);
		}
	} else {
		idOpcao.innerHTML = "Nenhuma solução encontrada";
	}
}


// Defeito Constatado - Combo
function listaConstatado(linha,familia, defeito_reclamado,defeito_constatado) {
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) {
			try {ajax = new XMLHttpRequest();}
			catch(exc) {
				alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;
			}
		}
	}
	if(ajax) {
		defeito_constatado.options.length = 1;
		idOpcao  = document.getElementById("opcoes2");
		ajax.open("GET","ajax_defeito_constatado.php?defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		ajax.onreadystatechange = function() {
			if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
			if(ajax.readyState == 4 ) {
				if(ajax.responseXML) {
					montaComboConstatado(ajax.responseXML,defeito_constatado);
				}
				else {
					idOpcao.innerHTML = "Selecione o defeito reclamado";
				}
			}
		}
		ajax.send(null);
	}
}

function montaComboConstatado(obj,defeito_constatado){
	var dataArray   = obj.getElementsByTagName("produto");

	if(dataArray.length > 0) {
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];

			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";

			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes2");
			novo.value = codigo;
			novo.text  = nome  ;
			defeito_constatado.options.add(novo);//adiciona
		}
	} else {
		idOpcao.innerHTML = "Selecione o defeito";
	}
}

function defeitoLista(peca,linha,os) {
	try {
		ajax = new ActiveXObject("Microsoft.XMLHTTP");
	}catch(e) {
		try {
			ajax = new ActiveXObject("Msxml2.XMLHTTP");
		}catch(ex) {
			try {
				ajax = new XMLHttpRequest();
			}catch(exc) {
				alert("Esse browser não tem recursos para uso do Ajax");
				ajax = null;
			}
		}
	}

	var defeito = "defeito_"+linha;
	var op = "op_"+linha;
	eval("document.forms[0]."+defeito+".options.length = 1;");
	idOpcao  = document.getElementById(op);

	if(peca.length > 0) {
		if(ajax) {
			ajax.open("GET","ajax_defeito2.php?peca="+peca+"&os="+os);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

			ajax.onreadystatechange = function() {
				if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
				if(ajax.readyState == 4 ) {
					if(ajax.responseXML) {
						montaComboDefeito(ajax.responseXML,linha);
					}
				}
			}
			ajax.send(null);
		}
	}else{
		idOpcao.innerHTML = "Selecione a peça";
	}
}

function montaComboDefeito(obj,linha){
	var defeito = "defeito_"+linha;
	var op = "op_"+linha;
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto

	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];

		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";

		var novo = document.createElement("option");
			novo.setAttribute("id", op);//atribui um ID a esse elemento
			novo.value = codigo;		//atribui um valor
			novo.text  = nome;//atribui um texto
			eval("document.forms[0]."+defeito+".options.add(novo);");//adiciona
		}
	} else {
		idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

function servicoLista(peca,linha) {
	try {ajax2 = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax2 = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax2 = new XMLHttpRequest();}
			catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax2 = null;}
		}
	}

	if(peca.length > 0) {

		if(ajax2) {
			var servico = "servico_"+linha;
 			var op_servico = "op_"+linha;
			eval("document.forms[0]."+servico+".options.length = 1;");

			idOpcao_servico  = document.getElementById(op_servico);

			ajax2.open("GET","ajax_servico.php?peca="+peca);
			ajax2.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

			ajax2.onreadystatechange = function() {
				if(ajax2.readyState == 1) {idOpcao_servico.innerHTML = "Carregando...!";}
				if(ajax2.readyState == 4 ) {
					if(ajax2.responseXML) {
						montaComboServico(ajax2.responseXML,linha);
					}else {
						idOpcao_servico.innerHTML = "Selecione a peça";
					}
				}
			}
			ajax2.send(null);
		}
	}
}

function montaComboServico(obj,linha){
	var servico = "servico_"+linha;
	var op_servico = "op_"+linha;
	var dataArray   = obj.getElementsByTagName("produto");
	if(dataArray.length > 0) {
		for(var i = 0 ; i < dataArray.length ; i++) {
			var item = dataArray[i];
			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;

			idOpcao_servico.innerHTML = "Selecione o defeito";

			var novo = document.createElement("option");

			novo.setAttribute("id_servico", op_servico);
			novo.value = codigo;
			novo.text  = nome;
			eval("document.forms[0]."+servico+".options.add(novo);");
		}
	} else {
		idOpcao_servico.innerHTML = "Selecione o defeito";
	}
}

function adicionaIntegridade() {

		//if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}
		if(document.getElementById('defeito_constatado_codigo').value =="" && document.getElementById('defeito_constatado_descricao').value== "") {
			alert('Selecione o defeito constatado');
			return false;
		}
		<? if($login_fabrica == 43) { ?>
				if(document.getElementById('solucao_os').options[document.getElementById('solucao_os').selectedIndex].text==""){
					alert('Selecione a solução');
					return false
				}
		<? } ?>
		var tbl = document.getElementById('tbl_integridade');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;


		if (iteration>0){
			document.getElementById('tbl_integridade').style.display = "inline";
		}


		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// COLUNA 1 - LINHA
		var celula = //criaCelula(document.getElementById('defeito_constatado').options[document.getElementById('defeito_constatado').selectedIndex].text);
		criaCelula(document.getElementById('defeito_constatado_codigo').value + '-'+document.getElementById('defeito_constatado_descricao').value);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
		el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
		el.setAttribute('value',document.getElementById('defeito_constatado').value);
		celula.appendChild(el);

		linha.appendChild(celula);

		// coluna 6 - botacao
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade(this);};
		celula.appendChild(el);
		linha.appendChild(celula);



		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);

		//document.getElementById('solucao').selectedIndex=0;

		if(document.getElementById('defeito_constatado_descricao').value != ""){
			document.getElementById('defeito_constatado_descricao').value = "";
		}
	}

	function adicionaIntegridade2(indice,tabela,defeito_reclamado,defeito_reclamado_desc,defeito_constatado) {

		var parar = 0;
		//alert(defeito_reclamado.value);
		//alert(defeito_constatado.value);
		$("input[@rel='defeito_constatado_"+indice+"']").each(function (){
			//alert($(this).val() + '-'+ defeito_constatado.value);
			if ($(this).val() == defeito_constatado.value){
				parar++;
			}
		});

		if (parar>0){
			alert('Defeito constatado '+defeito_constatado.options[defeito_constatado.selectedIndex].text+' já inserido')
			return false;
		}

		//if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}
		var tbl       = document.getElementById(tabela);
		var lastRow   = tbl.rows.length;
		var iteration = lastRow;

		if (iteration>0){
			document.getElementById(tabela).style.display = "inline";
		}
		//Cria Linha
		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// Cria Coluna/
		var celula = document.createElement('td');
		var celula = criaCelula(defeito_constatado.options[defeito_constatado.selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000';
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'i_defeito_constatado_' +indice+'_'+ iteration);
		el.setAttribute('rel', 'defeito_constatado_' +indice);
		el.setAttribute('id', 'i_defeito_constatado_' +indice+'_'+ iteration);
		el.setAttribute('value',defeito_constatado.value);
		celula.appendChild(el);
		linha.appendChild(celula);


		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';
		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade2(this,tabela);};
		celula.appendChild(el);
		linha.appendChild(celula);



		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);

}


function removerIntegridade(iidd){
	var tbl = document.getElementById('tbl_integridade');
	tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

}
function removerIntegridade2(iidd,tabela){
	var tbl = document.getElementById(tabela);
	tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function abreComunicado(){
	var ref = document.frm_os.produto_referencia.value;
	if (document.frm_os.produto_referencia.value!=""){
		url = "pesquisa_comunicado.php?produto=" + ref;
		window.open(url,"comm","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
	}
}

function verificaServico(servico,peca){
	var data = new Date();
	if (servico.value == '673' && peca.value.length > 0){
		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			data: 'peca_referencia='+peca.value+'&peca_troca=sim&data='+data.getTime(),
			complete: function(http) {
				results = http.responseText;
				if (results =='sim'){
					if(!confirm('Caso seja necessário esta peça para consertar o produto, o produto será trocado e a mão de obra, será de R$ 2,00. Caso consiga consertar o produto sem necessidade de troca desta peça, anote o serviço como ajuste, ou limpeza, ou ressoldagem, e a mão-de-obra será paga integral.')){
						servico.value="";
					}
				}
			}
		});
	}
}


function limpaPecas() {

	for (i=0;i<=20;i++) { 
		var peca = document.getElementById('peca_'+i);
		peca.length = 0;
	}

}

</script>

<style>
a.lnk:link{
	font-size: 10px;
	font-weight: bold;
	text-decoration: underline;
	color:#FFFF33;
}
a.lnk:visited{
	font-size: 10px;
	font-weight: bold;
	text-decoration: underline;
	color:#FFFF33;
}
/*	Para link da Lenoxx [Manuel]	*/
p.c1{
	color:white;
	font:bold 80% Arial,Helvetica,sans-serif;
}
a#lnx:link{
	font-weight: bold;
	text-decoration:none;
	color:white;
}
a#lnx:hover{
	font-weight: bold;
	text-decoration:underline;
	color:#FFFFAA;
}
a#lnx:visited{
	font-weight: bold;
	text-decoration:none;
	color:#FFFFAA;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}
.btn_altera{
    font:bold 11px tahoma,verdana,helvetica;
    padding-left:3px;
    padding-right:3px;
    cursor:pointer;
    overflow:visible;
    outline:0 none;
	background-position: center;
	background-repeat: no-repeat;
}
</style>
<p>

<?
$os_item = trim($_GET['os_item']);
if($os_item > 0){
	echo "<FONT COLOR=\"#FF0033\"><B>$msg_erro_item</B></FONT>";
	$msg_erro_item = 0;
}


if (strlen ($msg_erro) > 0) {
	##### Recarrega Form em caso de erro #####
	$os                       = $_POST["os"];
	$defeito_reclamado        = $_POST["defeito_reclamado"];
	$causa_defeito            = $_POST["causa_defeito"];
	$obs                      = $_POST["obs"];
	$aparencia_produto		  = $_POST["aparencia_produto"];
	$acessorios				  = $_POST["acessorios"];
	$defeito_constatado       = $_POST["defeito_constatado"];
	$solucao_os               = $_POST["solucao_os"];
	$type                     = $_POST["type"];
	$tecnico_nome             = $_POST["tecnico_nome"];
	$valores_adicionais       = $_POST["valores_adicionais"];
	$justificativa_adicionais = $_POST["justificativa_adicionais"];
	$qtde_km                  = $_POST["qtde_km"];
	$peca_serie               = $_POST["peca_serie"];
	$peca_serie_trocada       = $_POST["peca_serie_trocada"];
	//hd 24288
	//$autorizacao_domicilio    = $_POST["autorizacao_domicilio"];
	$justificativa_autorizacao = $_POST["justificativa_autorizacao"];
	$fabricacao_produto        = $_POST["fabricacao_produto"];
	$codigo_fabricacao         = $_POST["codigo_fabricacao"];


	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";

	echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
	echo "<tr>";
	echo "<td height='27' valign='middle' align='center'>";
	echo "<b><font face='Arial, Helvetica, sans-serif' color='#FF3333'>";

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectada a seguinte divergência: <br>";
		$msg_erro .= substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;

	echo "</font></b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}


if (strlen ($msg_previsao) > 0) {
	echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
	echo "<tr>";
	echo "<td height='27' valign='middle' align='center'>";
	echo "<b><font face='Arial, Helvetica, sans-serif' color='#3333FF'>";
	echo $msg_previsao ;
	echo "</font></b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}


#------------ Pedidos via Distribuidor -----------#
$resX = pg_query ($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
if (pg_fetch_result ($resX,0,0) == 't') {
	$resX = pg_query ($con,"SELECT tbl_posto.nome FROM tbl_posto JOIN tbl_posto_linha ON tbl_posto_linha.distribuidor = tbl_posto.posto WHERE tbl_posto_linha.posto = $login_posto AND tbl_posto_linha.linha = $linha");
	if (pg_num_rows ($resX) > 0) {
		echo "<center>Atenção! Peças da linha <b>$linha_nome</b> serão atendidas pelo distribuidor.<br><font size='+1'>" . pg_fetch_result ($resX,0,nome) . "</font></center><p>";
	}else{
		echo "<center>Peças da linha <b>$linha_nome</b> serão atendidas pelo fabricante.</center><p>";
	}
}


?>
	<!-- ------------- Formulário ----------------- -->
<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">

		<input type="hidden" name="os"        value="<?echo $os?>">
		<input type="hidden" name="voltagem"  value="<?echo $produto_voltagem?>">
		<input type='hidden' name='produto_referencia' value='<? echo $produto_referencia ?>'>
		<p>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
<?
		echo $sua_os;
?>
				</b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>


			<? if($login_fabrica <> 30) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><?
				echo "$produto_referencia - $produto_descricao"; ?></b>
				</font>
			</td>
			<? } ?>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					N. Série
				</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>

		</tr>
		</table>
<?
//relacionamento de integridade comeca aqui....
echo "<INPUT TYPE='hidden' name='xxproduto_linha' value='$produto_linha'>";
echo "<INPUT TYPE='hidden' name='xxproduto_familia' value='$produto_familia'>";


//se tiver o defeito reclamado ativo
echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";
	echo "<tr>";
	echo "<td>";
	echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><BR>";

	if (strlen($defeito_reclamado)>0 ) {
	$sql = "SELECT defeito_reclamado,
					descricao as defeito_reclamado_descricao
			FROM tbl_defeito_reclamado
			WHERE defeito_reclamado= $defeito_reclamado";

	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		$xdefeito_reclamado = pg_fetch_result($res,0,defeito_reclamado);
		$xdefeito_reclamado_descricao = pg_fetch_result($res,0,defeito_reclamado_descricao);
	}
	echo "<INPUT TYPE='text' name='xxdefeito_reclamado' size='30' value='$xdefeito_reclamado - $xdefeito_reclamado_descricao' disabled>";
	echo "<INPUT TYPE='hidden' name='defeito_reclamado' value='$xdefeito_reclamado'>";
	}
	else {
	echo "<INPUT TYPE='text' name='xxdefeito_reclamado' size='30' value='$defeito_reclamado_descricao_os' disabled>";
	}
	echo "</td>";
	if($login_fabrica<>19){
		echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Constatado</font><BR>";
		if($pedir_defeito_reclamado_descricao == 'f'){
			$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
							tbl_defeito_constatado.descricao
					FROM tbl_diagnostico
					JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
					WHERE tbl_diagnostico.linha = $produto_linha";
					if (strlen($defeito_reclamado)>0) {
						$sql .= " AND tbl_diagnostico.defeito_reclamado=$defeito_reclamado ";
					}

					$sql .= " AND tbl_diagnostico.ativo='t' ";
			if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
			$sql.=" ORDER BY tbl_defeito_constatado.descricao";
		}else{
			$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
							tbl_defeito_constatado.descricao
					FROM tbl_diagnostico
					JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
					WHERE tbl_diagnostico.linha = $produto_linha
					AND tbl_diagnostico.ativo='t' ";
			if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
			$sql.=" ORDER BY tbl_defeito_constatado.descricao";
		}
		//echo nl2br($sql);
		$res = pg_query($con,$sql);

		echo "<select name='defeito_constatado' id='defeito_constatado' size='1' class='frm'";
		echo ">";

		echo "<option value=''></option>";
		for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$xxdefeito_constatado = pg_fetch_result ($res,$y,defeito_constatado) ;
			$defeito_constatado_descricao = pg_fetch_result ($res,$y,descricao) ;

			echo "<option value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_descricao</option>";
		}
		echo "</select>";
		echo "</td>";
	}


	echo "<td>";
	echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";

	echo "<select name='solucao_os' class='frm'  style='width:200px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";

	$sql = "SELECT 	solucao,
					descricao
			FROM tbl_solucao
			WHERE fabrica=$login_fabrica
			AND solucao=$solucao_os";
	$res = pg_query($con, $sql);
	$solucao_descricao = pg_fetch_result ($res,0,descricao);

	echo "<option id='opcoes' value='$solucao_os'>$solucao_descricao</option>";
	echo "</select>";

	echo "</td>";
	echo "</tr>";
	//HD 4291 Paulo
	if($login_posto== 4311 ) {
		echo "<tr><td>";
		echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Box/Prateleira</font><BR>";
		echo "<input type='text' name='prateleira_box' class='frm' value='$prateleira_box' size='8' maxlength='20'>";
		echo "</td>";
		echo "</tr>";
	}
	//Fim
	echo "</table>";
	echo "<BR><BR>";

//caso nao achar defeito reclamado
if (strlen($defeito_reclamado)==0 ){
	echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";

	if($pedir_defeito_reclamado_descricao == 't'){
		echo "<tr>";
		echo "<td valign='top' align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";
		if(strpos($sua_os,'-') == FALSE){//SE FOR DE CONSUMIDOR
			if(strlen($defeito_reclamado_descricao_os) > 0){
				echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao_os' value='$defeito_reclamado_descricao_os'>";
			}else{
				echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
				echo "<INPUT TYPE='text' name='defeito_reclamado_descricao_os' value='$defeito_reclamado_descricao_os'>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
			}
		}else{//SE FOR DE REVENDA
			if(strlen($defeito_reclamado_descricao_os) == 0 ){
				echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
				echo "<INPUT TYPE='text' name='defeito_reclamado_descricao_os' value='$defeito_reclamado_descricao_os'>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
			}else{
				echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao_os' value='$defeito_reclamado_descricao_os'>";
			}
		}
		echo "</td>";
	}else{
		echo "<tr>";
		echo "<td valign='top' align='left'>";
		echo "<input type='hidden' name='defeito_reclamado'  class='frm' style='width:220px;' ";
		echo ">";
		echo "</td>";
	}


	if($tipo_atendimento == 22){
		echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Constatado</font><BR><font color=green size=1><b>Instalação de Purificador</b></font></td>";
		echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR><font color=green size=1><b>Instalação de Purificador</b></font></td>";
		echo "<input type='hidden' name='defeito_constatado' id='defeito_constatado' value='11261'>";
		echo "<input type='hidden' name='solucao_os' id='solucao_os' value='459'>";
	}else{
		//CONSTATADO
		if($login_fabrica<>19){
			if ($pedir_defeito_constatado_os_item <> 'f' OR $login_fabrica<>5) {
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Constatado</font><BR>";
				if($login_fabrica<>30 AND $login_fabrica <> 43){
					echo "<select name='defeito_constatado' id='defeito_constatado' class='frm' style='width: 220px;' onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado.value,this);' >";

					if($pedir_defeito_reclamado_descricao == 't' AND strlen($defeito_constatado) > 0 AND ($login_fabrica == 45 OR $login_fabrica == 15 OR $login_fabrica == 35 OR $login_fabrica == 2 OR $login_fabrica == 43 OR $login_fabrica == 46 OR $login_fabrica == 51 OR $login_fabrica == 30 OR $login_fabrica == 56 OR $login_fabrica == 50 OR $login_fabrica > 56)){
						$sql_cons = "SELECT defeito_constatado, descricao
										FROM tbl_defeito_constatado
										WHERE defeito_constatado = $defeito_constatado
										AND fabrica = $login_fabrica; ";
						$res_cons = pg_query($con, $sql_cons);
						if(pg_num_rows($res_cons) > 0){
							$defeito_constatado_desc = pg_fetch_result($res_cons,0,descricao);
							echo "<option id='opcoes2' value='$defeito_constatado'>$defeito_constatado_desc</option>";
						}else{
							echo "<option id='opcoes2' value=''></option>";
						}
					}else{
						echo "<option id='opcoes2' value=''></option>";
					}
					echo "</select>";
				}else{
					$sql_cons = "SELECT defeito_constatado, descricao ,codigo
									FROM tbl_defeito_constatado
									WHERE defeito_constatado = $defeito_constatado
									AND fabrica = $login_fabrica; ";
					$res_cons = @pg_query($con, $sql_cons);
					if(@pg_num_rows($res_cons) > 0){
						$defeito_constatado_descricao = pg_fetch_result($res_cons,0,descricao);
						$defeito_constatado_codigo    = pg_fetch_result($res_cons,0,codigo);
						$defeito_constatado_id        = pg_fetch_result($res_cons,0,defeito_constatado);
					}
					echo "<input type='hidden' name='defeito_constatado' id='defeito_constatado' value='$defeito_constatado_id'>";

					//hd 46589
					echo "<input ";if ($login_fabrica<>43) echo "type='text'"; else echo "type='hidden'"; echo " name='defeito_constatado_codigo' id='defeito_constatado_codigo' size='5' onblur=\" pega_dc('$os','defeito_constatado','defeito_constatado_codigo','defeito_constatado_descricao'); \">";if ($login_fabrica<>43) echo "<img src='imagens/btn_lupa.gif' onclick='fnc_pesquisa_dc(\"$os\",document.frm_os.defeito_constatado,document.frm_os.defeito_constatado_codigo,document.frm_os.defeito_constatado_descricao)'>&nbsp;";

					echo "<input type='text' name='defeito_constatado_descricao' id='defeito_constatado_descricao'";if ($login_fabrica==43) echo " size='30'";echo " ><img src='imagens/btn_lupa.gif' onclick='fnc_pesquisa_dc(\"$os\",document.frm_os.defeito_constatado,document.frm_os.defeito_constatado_codigo,document.frm_os.defeito_constatado_descricao)'>&nbsp;";
				}
				echo "</td>";
			}
		}
		//CONSTATADO
		//SOLUCAO
		echo "<td>";
			if ($pedir_solucao_os_item <> 'f' or $login_fabrica <> 5 ) {
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";
				echo "<select name='solucao_os' id='solucao_os' class='frm'  style='width:200px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";
				if($pedir_defeito_reclamado_descricao == 't' AND strlen($solucao_os) > 0 AND (in_array($login_fabrica,array(45,43,15,35,2,46,51,30,50,56)) OR $login_fabrica>56)){
					$sql_cons = "SELECT solucao, descricao
									FROM tbl_solucao
									WHERE solucao = $solucao_os
									AND fabrica = $login_fabrica; ";
					$res_cons = pg_query($con, $sql_cons);
					if(pg_num_rows($res_cons) > 0){
						$solucao_os_desc = pg_fetch_result($res_cons,0,descricao);
						echo "<option id='opcoes' value='$solucao_os'>$solucao_os_desc</option>";
					}else{
						echo "<option id='opcoes' value=''></option>";
					}
				}else{
					echo "<option id='opcoes' value=''></option>";
				}
				echo "</select>";
			}
		echo "</td>";
	}
	echo "</tr>";
	echo "</table>";
}

if($tipo_atendimento==22 ) {
	echo "<center><font color=red size='1'>Não é possível lançar peças numa OS de Instalção</font></center>";
}else{

		### LISTA ITENS DA OS QUE POSSUEM PEDIDOS
		if(strlen($os) > 0){
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.pedido                                  ,
							tbl_pedido.pedido_blackedecker  AS pedido_blackedecker,
							tbl_os_item.qtde                                    ,
							tbl_os_item.causa_defeito                           ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_peca.devolucao_obrigatoria                      ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_causa_defeito.descricao AS causa_defeito_descricao,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao,
							tbl_os_item.peca_serie_trocada
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
					JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
					JOIN    tbl_pedido                 ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_causa_defeito     ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido IS NOT NULL
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
				echo "<tr height='20' bgcolor='#666666'>";
				$colspan = 4;
				echo "<td align='center' colspan='$colspan'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedidos enviados ao fabricante</b></font></td>";

				echo "</tr>";
				echo "<tr height='20' bgcolor='#666666'>";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";

				echo "</tr>";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$faturado      = pg_num_rows($res);
						$fat_pedido    = pg_fetch_result($res,$i,pedido);
						$fat_pedido_blackedecker = pg_fetch_result($res,$i,pedido_blackedecker);
						$fat_peca      = pg_fetch_result($res,$i,referencia);
						$fat_descricao = pg_fetch_result($res,$i,descricao);
						$fat_qtde      = pg_fetch_result ($res,$i,qtde);

						$peca_serie_trocadax = pg_fetch_result ($res,$i,peca_serie_trocada);
						$os_item       = pg_fetch_result($res,$i,os_item);

						$devolucao_obrigatoria = pg_fetch_result($res,$i,devolucao_obrigatoria);

						if ($devolucao_obrigatoria == "t"){
							$devolucao_obrigatoria = "Sim";
						}else{
							$devolucao_obrigatoria = "Não";
						}

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>";
						echo $fat_pedido;
						echo "</font></td>";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_peca</font></td>";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_descricao</font></td>";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_qtde</font></td>";
						echo "</tr>";
				}
				echo "</table>";
			}
		}

		### LISTA ITENS DA OS QUE ESTÃO COMO NÃO LIBERADAS PARA PEDIDO EM GARANTIA
		if(strlen($os) > 0){
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
					JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
					LEFT JOIN    tbl_pedido            ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.liberacao_pedido           IS FALSE
					AND     tbl_os_item.liberacao_pedido_analisado IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			$sqlOrcamento = "SELECT tbl_orcamento_item.orcamento_item           ,
							tbl_orcamento_item.qtde                             ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os.produto                                      ,
							tbl_os.serie                                        ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_orcamento              ON tbl_orcamento.os = tbl_os.os
					JOIN    tbl_orcamento_item         ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
					JOIN    tbl_produto                ON tbl_os.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_orcamento_item.peca   = tbl_peca.peca
					LEFT JOIN    tbl_pedido            ON tbl_orcamento_item.pedido = tbl_pedido.pedido
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_orcamento_item.pedido  IS NOT NULL
					ORDER BY tbl_orcamento_item.orcamento_item ASC;";
			$resOrca = pg_query ($con,$sqlOrcamento) ;

			if(pg_num_rows($res) > 0 OR pg_num_rows($resOrca) > 0) {
				$col = 4;
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia </b></font></td>\n";

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";
				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
					$recusado      = pg_num_rows($res);
					$rec_item      = pg_fetch_result($res,$i,os_item);
					$rec_obs       = pg_fetch_result($res,$i,obs);
					$rec_peca      = pg_fetch_result($res,$i,referencia);
					$rec_descricao = pg_fetch_result($res,$i,descricao);
					$rec_qtde      = pg_fetch_result($res,$i,qtde);

					echo "<tr height='20' bgcolor='#FFFFFF'>";
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
					echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";
					echo "</tr>\n";
				}

				for ($i = 0 ; $i < pg_num_rows($resOrca) ; $i++) {
						$recusado      = pg_num_rows($resOrca);
						$rec_item      = pg_fetch_result($resOrca,$i,orcamento_item);
						$rec_obs       = pg_fetch_result($resOrca,$i,obs);
						$rec_peca      = pg_fetch_result($resOrca,$i,referencia);
						$rec_descricao = pg_fetch_result($resOrca,$i,descricao);
						$rec_qtde      = pg_fetch_result($resOrca,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}

		### LISTA ITENS DA OS FORAM LIBERADAS E AINDA NÃO POSSEM PEDIDO
		if(strlen($os) > 0){
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
					JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
					LEFT JOIN    tbl_pedido            ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido           ISNULL
					AND     tbl_os_item.liberacao_pedido IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças aprovadas aguardando pedido</b></font></td>\n";

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$recusado      = pg_num_rows($res);
						$rec_item      = pg_fetch_result($res,$i,os_item);
						$rec_peca      = pg_fetch_result($res,$i,referencia);
						$rec_descricao = pg_fetch_result($res,$i,descricao);
						$rec_qtde      = pg_fetch_result($res,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}

		if(strlen($os) > 0 AND strlen ($msg_erro) == 0){
			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia;";
				$resX = @pg_query($con,$sql);
				$inicio_itens = @pg_num_rows($resX);
			}else{
				$inicio_itens = 0;
			}

			$sql = "SELECT  tbl_os_item.os_item                                                ,
							tbl_os_item.pedido                                                 ,
							tbl_os_item.qtde                                                   ,
							tbl_os_item.causa_defeito                                          ,
							tbl_os_item.posicao                                                ,
							tbl_os_item.admin              as admin_peca                       ,
							tbl_os_item.peca_serie                                             ,
							tbl_os_item.peca_serie_trocada                                     ,
							tbl_peca.referencia                                                ,
							tbl_peca.descricao                                                 ,
							tbl_defeito.defeito                                                ,
							tbl_defeito.descricao                   AS defeito_descricao       ,
							tbl_causa_defeito.descricao             AS causa_defeito_descricao ,
							tbl_produto.referencia                  AS subconjunto             ,
							tbl_os_produto.os_produto                                          ,
							tbl_os_produto.produto                                             ,
							tbl_os_produto.serie                                               ,
							tbl_servico_realizado.servico_realizado                            ,
							tbl_servico_realizado.descricao         AS servico_descricao
					FROM    tbl_os
					JOIN   (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
					JOIN    tbl_os_produto             ON tbl_os.os = tbl_os_produto.os
					JOIN    tbl_os_item                ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN    tbl_produto                ON tbl_os_produto.produto = tbl_produto.produto
					JOIN    tbl_peca                   ON tbl_os_item.peca = tbl_peca.peca
					LEFT JOIN    tbl_pedido                 ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_causa_defeito     ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido           ISNULL
					AND     tbl_os_item.liberacao_pedido IS FALSE
					ORDER BY tbl_os_item.os_item;";
			$res = pg_query ($con,$sql) ;
			$fim_itens = 0;
			if (pg_num_rows($res) > 0) {
				$fim_itens = $inicio_itens + pg_num_rows($res);
				//$qtde_item = $qtde_item + $inicio_itens ;

				$i = 0;

				//hd 44118 - tem que zerar a variável, senão o php não entende que é um array, pois já foi usada anteriormente variável com este nome
				// MLG - apaga a variável da memória
				unset($os_item);
				for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
					$os_item[$k]                 = pg_fetch_result($res,$i,os_item);
					$os_produto[$k]              = pg_fetch_result($res,$i,os_produto);
					$pedido[$k]                  = pg_fetch_result($res,$i,pedido);
					$peca[$k]                    = pg_fetch_result($res,$i,referencia);
					$qtde[$k]                    = pg_fetch_result($res,$i,qtde);
					$produto[$k]                 = pg_fetch_result($res,$i,subconjunto);
					$serie[$k]                   = pg_fetch_result($res,$i,serie);
					$posicao[$k]                 = pg_fetch_result($res,$i,posicao);
					$descricao[$k]               = pg_fetch_result($res,$i,descricao);
					$defeito[$k]                 = pg_fetch_result($res,$i,defeito);
					$pcausa_defeito[$k]          = pg_fetch_result($res,$i,causa_defeito);
					$causa_defeito_descricao[$k] = pg_fetch_result($res,$i,causa_defeito_descricao);
					$defeito_descricao[$k]       = pg_fetch_result($res,$i,defeito_descricao);
					$servico[$k]                 = pg_fetch_result($res,$i,servico_realizado);
					$peca_serie[$k]              = pg_fetch_result($res,$i,peca_serie);
					$peca_serie_trocada[$k]      = pg_fetch_result($res,$i,peca_serie_trocada);
					$servico_descricao[$k]       = pg_fetch_result($res,$i,servico_descricao);
					$admin_peca[$k]              = pg_fetch_result($res,$i,admin_peca);//aqui
					if(strlen($admin_peca[$k])==0) { $admin_peca[$k]="P"; }
					$i++;
				}
			}else{
				// HD 73196 - MLG - A variável tem que ser apagada também para esta iteração
				unset($os_item);
				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$os_item[$i]        = $_POST["os_item_"        . $i];
					$orcamento_item[$i] = $_POST["orcamento_item_" . $i];
					$os_produto[$i]     = $_POST["os_produto_"     . $i];
					$produto[$i]        = $_POST["produto_"        . $i];
					$serie[$i]          = $_POST["serie_"          . $i];
					$posicao[$i]        = $_POST["posicao_"        . $i];
					$peca[$i]           = $_POST["peca_"           . $i];
					$qtde[$i]           = $_POST["qtde_"           . $i];
					$defeito[$i]        = $_POST["defeito_"        . $i];
					$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
					$servico[$i]        = $_POST["servico_"        . $i];
					$peca_serie[$i]     = $_POST["peca_serie_"     . $i];
					$peca_serie_trocada[$i] = $_POST["peca_serie_trocada_" . $i];
					$admin_peca[$i]     = $_POST["admin_peca_"     . $i]; //aqui

					if (strlen($peca[$i]) > 0) {
						$sql = "SELECT  tbl_peca.referencia,
										tbl_peca.descricao
								FROM    tbl_peca
								WHERE   tbl_peca.fabrica    = $login_fabrica
								AND     tbl_peca.referencia = $peca[$i];";
						$resX = @pg_query ($con,$sql) ;

						if (@pg_num_rows($resX) > 0) {
							$descricao[$i] = trim(pg_fetch_result($resX,0,descricao));
						}
					}
				}
			}

			# Pega itens do Orçamento
			$sql = "SELECT  tbl_orcamento_item.orcamento_item                                  ,
							tbl_orcamento_item.peca                                            ,
							tbl_orcamento_item.qtde                                            ,
							tbl_orcamento_item.preco                                           ,
							tbl_orcamento_item.defeito                                         ,
							tbl_orcamento_item.servico_realizado                               ,
							tbl_peca.referencia                                                ,
							tbl_peca.descricao                                                 ,
							tbl_defeito.descricao                   AS defeito_descricao
					FROM    tbl_orcamento
					JOIN    tbl_orcamento_item         ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
					JOIN    tbl_peca                   ON tbl_peca.peca                = tbl_orcamento_item.peca
					LEFT JOIN    tbl_defeito           ON tbl_defeito.defeito          = tbl_orcamento_item.defeito
					WHERE   tbl_orcamento.os      = $os
					AND     tbl_orcamento.empresa = $login_fabrica
					AND     tbl_orcamento_item.pedido IS NULL
					ORDER BY tbl_orcamento_item.orcamento_item;";
			$res = pg_query ($con,$sql) ;

			if (pg_num_rows($res) > 0) {
				$qtde_itens_orcado = pg_num_rows($res);
				$i = 0;
				for ($j = $fim_itens; $j < $fim_itens + $qtde_itens_orcado ; $j++) {

					$orcamento_item[$j]          = trim(pg_fetch_result($res,$i,orcamento_item));
					$os_item[$j]                 = "";
					$os_produto[$j]              = "";
					$pedido[$j]                  = "";
					$peca[$j]                    = trim(pg_fetch_result($res,$i,referencia));
					$qtde[$j]                    = trim(pg_fetch_result($res,$i,qtde));
					$preco[$j]                   = trim(pg_fetch_result($res,$i,preco));
					$produto[$j]                 = "";
					$serie[$j]                   = "";
					$posicao[$j]                 = "";
					$descricao[$j]               = trim(pg_fetch_result($res,$i,descricao));
					$defeito[$j]                 = trim(pg_fetch_result($res,$i,defeito));
					$pcausa_defeito[$j]          = "";
					$causa_defeito_descricao[$j] = "";
					$defeito_descricao[$j]       = "";
					$servico[$j]                 = trim(pg_fetch_result($res,$i,servico_realizado));
					$servico_descricao[$j]       = "";
					$admin_peca[$j]              = "";
					if(strlen($admin_peca[$j])==0) { $admin_peca[$j]="P"; }
					$i++;
				}
			}
		}else{
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$os_item[$i]        = $_POST["os_item_"        . $i];
				$orcamento_item[$i] = $_POST["orcamento_item_" . $i];
				$os_produto[$i]     = $_POST["os_produto_"     . $i];
				$produto[$i]        = $_POST["produto_"        . $i];
				$serie[$i]          = $_POST["serie_"          . $i];
				$posicao[$i]        = $_POST["posicao_"        . $i];
				$peca[$i]           = $_POST["peca_"           . $i];
				$qtde[$i]           = $_POST["qtde_"           . $i];
				$defeito[$i]        = $_POST["defeito_"        . $i];
				$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
				$servico[$i]        = $_POST["servico_"        . $i];
				$peca_serie[$i]     = $_POST["peca_serie_"     . $i];
				$peca_serie_trocada[$i] = $_POST["peca_serie_trocada_" . $i];
				$admin_peca[$i]     = $_POST["admin_peca_"     . $i];//aqui
				if (strlen($peca[$i]) > 0) {
					$sql = "SELECT  tbl_peca.referencia,
									tbl_peca.descricao
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica    = $login_fabrica
							AND     tbl_peca.referencia = '$peca[$i]';";
					$resX = @pg_query ($con,$sql) ;
					if (@pg_num_rows($resX) > 0) {
						$descricao[$i] = trim(pg_fetch_result($resX,0,descricao));
					}
				}
			}
		}

		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
		echo "<tr height='20' bgcolor='#666666'>";

		if ($os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto</b></font></td>";
		}

		if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "N. Série";
			echo "</b></font></td>";
		}

		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peça Pai </b></font></td>";

		if ($pedir_causa_defeito_os_item == 't' AND $login_fabrica<>20) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Causa</b></font></td>";
		}
		echo "</tr>";

		$loop = $qtde_item;


		$i=0;
		echo "<tr >";
		#------------------- Causa do Defeito no Item --------------------
		
		$sql = "SELECT DISTINCT 
								tbl_lista_basica.peca_pai, 
								tbl_peca.referencia,
								tbl_peca.descricao
						FROM 	tbl_lista_basica 
						JOIN	tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca_pai where produto = $produto_os 
						AND tbl_lista_basica.peca_pai IS NOT NULL
						AND 1=2";
		
		$res = pg_query($con,$sql);

		echo "<td align='center'>";
		echo "<select name='peca_pai' id='peca_pai' class='frm' onchange=limpaPecas()"; if (strlen($peca_pai)>0) echo " disabled "; echo ">";
		echo "<option> </option>";

		for ($c = 0;$c<pg_num_rows($res);$c++) {

			$xpeca_pai       = pg_fetch_result($res,$c,peca_pai);
			$descricao_pai  = pg_fetch_result($res,$c,descricao);
			$referencia_pai  = pg_fetch_result($res,$c,referencia);

			echo "<option value='$xpeca_pai'"; if ($xpeca_pai == $peca_pai) echo "SELECTED"; echo">$referencia_pai - $descricao_pai</option>";
		}

		echo "</select>";
		echo "</td>";
		//echo $pedir_causa_defeito_os_item; 
		if ($pedir_causa_defeito_os_item == 't' and $login_fabrica<>20) {
			if($login_fabrica==5  and $c > 0 ){
				echo "<td align='center'>&nbsp;</td>";
			}else{
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='pcausa_defeito'>";
				echo "<option selected></option>";

				# HD 44571
				if ($login_fabrica == 5){
					$cond_mondialcausa = "AND causa_defeito = 1";
				}

				$sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica $cond_mondialcausa ORDER BY codigo, descricao";
				
				$res = pg_query ($con,$sql) ;

				for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
					echo "<option ";
					if ($pcausa_defeito[$i] == pg_fetch_result ($res,$x,causa_defeito)) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$x,causa_defeito) . "'>" ;
					echo pg_fetch_result ($res,$x,codigo) ;
					echo " - ";
					echo pg_fetch_result ($res,$x,descricao) ;
					echo "</option>";
				}

				echo "</select>";
				//echo nl2br($sql);
				echo "</td>\n";
			}
		}

		#------------------- Defeito no Item --------------------
		echo "<td align='center'>";
		if($login_fabrica==5 and $i > 0){
			echo "&nbsp;";
		}else{

		}
		echo "</td>\n";

		echo "<td align='center'>";
		/*INTEGRIDADE DE PEÇAS x SOLUÇÃO COMECA AQUI - TAKASHI HD 2504*/
		if($login_fabrica==5 and $i > 0){
			echo "&nbsp;";
		}
		echo "</td>\n";
		echo "</tr>";

		echo "<tr>";
		echo "<td>\n";
		echo "<BR>";
		echo "</td>\n";
		echo "</tr>";
		echo "<tr bgcolor='#666666'>";
	
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>&nbsp; Código &nbsp;</b></font></td>";
	
		echo "<td align='center'><acronym title=\"Clique para abrir a lista básica do produto.\"><a class='lnk' href='peca_consulta_por_produto";
		echo "' target='_blank'>LISTA BÁSICA</a></acronym></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";

		if ($pergunta_qtde_os_item == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";
		}
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Defeito</b></font></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Serviço</b></font></td>";
		echo "</tr>";

		//USADO PARA FAZER O LAÇO QUANDO EXISTIR UM ITEM DE APARENCIA E INCREMENTAR EM QTDE_ITENS
		$qtde_item_aparencia="0";

		$offset = 0;

		for ($i = 0 ; $i < $loop ; $i++) {
			$cor="";
			echo "<tr $cor>";
			echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>\n";
			echo "<input type='hidden' name='os_item_$i'    value='$os_item[$i]'>\n";
			echo "<input type='hidden' name='orcamento_item_$i' value='$orcamento_item[$i]'>\n";
			echo "<input type='hidden' name='descricao'>";
			echo "<input type='hidden' name='preco'>";
			echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//aqui

			if ($os_item_subconjunto == 'f') {
				echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
			}

			if ($os_item_subconjunto == 'f') {
				$xproduto = $produto[$i];
				echo "<input type='hidden' name='serie_$i'>\n";
			}else {
				if ($os_item_serie == 't') {
					echo "<td align='center'><input class='frm' type='text' name='serie_$' size='9' value='$serie[$i]'></td>\n";
				}
			}

			/* Rotina para verificação de comunicados por Peça - HD 19052 */
			if (strlen($peca[$i])>0){
				$sql ="SELECT count(*)
						FROM  tbl_comunicado
						LEFT JOIN tbl_comunicado_peca USING(comunicado)
						LEFT JOIN tbl_peca PC_1  ON PC_1.peca = tbl_comunicado_peca.peca
						LEFT JOIN tbl_peca PC_2  ON PC_2.peca = tbl_comunicado.peca
						WHERE tbl_comunicado.fabrica = $login_fabrica
						AND   tbl_comunicado.ativo  IS TRUE
						AND ( tbl_comunicado.posto = $login_posto OR tbl_comunicado.posto IS NULL)
						AND (PC_1.referencia = '$peca[$i]' OR PC_2.referencia = '$peca[$i]')";
				$resComunicado = @pg_query ($con,$sql) ;
				$tem_comunicado = trim(pg_fetch_result($resComunicado,0,0));
			}else{
				$tem_comunicado = 0;
			}


			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {	// HD 7033 16/11/2007
				$sql = "SELECT  tbl_peca.peca      ,
								tbl_peca.referencia,
								tbl_peca.descricao ,
								tbl_lista_basica.qtde
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica";
						$sql .= " AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia
						LIMIT 1 OFFSET $offset;";
				$resX = @pg_query ($con,$sql) ;

				if (@pg_num_rows($resX) > 0) {

					$qtde_item_aparencia++;
					$xpeca       = trim(pg_fetch_result($resX,0,peca));
					$xreferencia = trim(pg_fetch_result($resX,0,referencia));
					$xdescricao  = trim(pg_fetch_result($resX,0,descricao));
					$xqtde       = trim(pg_fetch_result($resX,0,qtde));

					if ($peca[$i] == $xreferencia)
						$check = " checked ";
					else
						$check = "";

					if ($login_posto == 427) $check = " checked ";

					echo "<td align='center'><input class='frm' type='checkbox' name='peca_$i' value='$xreferencia' $check>&nbsp;<font face='arial' size='-2' color='#000000'>$xreferencia</font></td>\n";

					echo "<td width='60' align='center'>";
					//echo "<img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'>";
					echo "</TD>";
					echo "<td align='center'><font face='arial' size='-2' color='#000000'>$xdescricao</font></td>\n";
					echo "<td align='center'><font face='arial' size='-2' color='#000000'>$xqtde</font><input type='hidden' name='qtde_$i' value='$xqtde'></td>\n";

				}else{

					echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]' alt='LISTA BÁSICA'> teste&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"tudo\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
					//takashi chamado 300 12-07
					echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\",document.frm_os.qtde_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
					//takashi chamado 300 12-07
					echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
					if ($pergunta_qtde_os_item == 't') {
						echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>\n";
					}
				}
			}else{
				echo "<input type='hidden' name='posicao_$i'>\n";

				echo "<td align='center' nowrap>
				<input class='frm' type='text' name='peca_$i' id='peca_peca_$i'size='15' value='$peca[$i]'"; echo " "; if($login_fabrica==30 or $login_fabrica ==43 or $login_fabrica ==5) echo " onblur=\"javascript: pega_peca('$os','peca_peca_$i','descricao_$i'); atualizaQtde(document.frm_os.peca_$i,document.frm_os.qtde_$i);\" ";
				echo ">&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle'  ";
				if ($login_fabrica == 14  or $login_fabrica == 24 or $login_fabrica == 5) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"referencia\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista_mondial (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.defeito_$i, document.frm_os.voltagem, \"referencia\", document.frm_os.qtde_$i)'";	
				echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
				/* Rotina para verificação de comunicados por Peça - HD 19052 */
				if($tem_comunicado==0){
					$style_img_comunicado = "visibility:hidden;";
				}else{
					$style_img_comunicado = "";
				}
				echo "<img id='imagem_comunicado_$i' src='imagens/warning.png' style='$style_img_comunicado cursor: pointer;' aling='absmiddle' onclick='javascript:abreComunicadoPeca($i)'>";
				echo "</td>\n";
				echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\",document.frm_os.qtde_$i)' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
				echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";

				if ($pergunta_qtde_os_item == 't') {
					echo "<td align='center'>".
						"<input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]' ".
						"onChange=\"if (parseInt(this.value) < 0) {this.value = Math.abs(this.value);this.style.color = 'red';}else{this.style.color = 'black';}\"".
						"></td>\n";
				}
				echo "<td>
					<select class='frm' size='1' style='width: 150px' name='defeito_$i' id='defeito_$i' onfocus='defeitoLista(document.frm_os.peca_$i.value,$i,$os)'>";
					$cond_defeito = "";
					if (strlen(trim($defeito[$i]))>0){
						$cond_defeito = " and tbl_defeito.defeito = ".$defeito[$i] ;
						$sql = "SELECT *
							FROM   tbl_defeito
							WHERE  tbl_defeito.fabrica = $login_fabrica
							AND    tbl_defeito.ativo IS TRUE
							$cond_defeito 
								ORDER BY descricao";
						$res = pg_query ($con,$sql) ;

						for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
							echo "<option id='op_$i' ";
							if ($defeito[$i] == pg_fetch_result ($res,$x,defeito)) echo " selected ";
							echo " value='" . pg_fetch_result ($res,$x,defeito) . "'>" ;

							if (strlen (trim (pg_fetch_result ($res,$x,codigo_defeito))) > 0) {
								echo pg_fetch_result ($res,$x,codigo_defeito) ;
								echo " - " ;
							}
							echo pg_fetch_result ($res,$x,descricao) ;
							echo "</option>";
						}
						echo "</select>";
					}else{
						echo "<option id='op_$i'></option>";
					}
				echo "</td>";

				echo "<td>
				<select class='frm' size='1' name='servico_$i' rel='servicos_realizados' alt='$i'>";
				echo "<option selected></option>";
				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica";
				$sqlmostra = "select case when current_date > '2009-12-15'::date then 't' else 'f' end as mostra;";
				$res = pg_query($con,$sqlmostra);
				if ( pg_fetch_result($res,0,mostra) == 'f'){
					$sql .= " AND tbl_servico_realizado.ativo is true";
				}
				$sql .= " ORDER BY descricao ";
				// Termino da Gambiara

				$res = pg_query ($con,$sql) ;

				for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
					echo "<option ";
					if ($servico[$i] == pg_fetch_result ($res,$x,servico_realizado) ) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$x,servico_realizado) . "'>" ;
					echo pg_fetch_result ($res,$x,descricao) ;
					echo "</option>";
				}

				echo "</select></td>";

			}

			echo "</tr>\n";

			$offset = $offset + 1;
		}
		echo "</table>";
		?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>
<?}//tipo atendimento de instalação termina aqui?>

<table>
<tr>
	<td><BR></td>
</tr>
</table>


<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Observação:</FONT> <INPUT TYPE="text" NAME="obs" value="<? echo $obs; ?>" size="70" maxlength="255" class="frm">
		<br><br>
		<FONT SIZE="1" COLOR="#ff0000">O campo "Observação" é somente para o controle do posto autorizado. <br>O fabricante não se responsabilizará pelos dados aqui digitados.</FONT>
		<br><br>
	</td>
</tr>
<? if (strlen ($orientacao_sac) > 0) { ?>
<tr>
	<td valign="middle" align="center" colspan="3" bgcolor="#eeeeee">
		<FONT SIZE="1"><b>Orientação do SAC ao Posto Autorizado</b></FONT>
		<p>
		<? echo $orientacao_sac ?>
		<br><br>
	</td>
</tr>
<? } ?>

<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>
		<input type="hidden" name="btn_acao" value="">
		<input type="hidden" name="btn_imprimir" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;">
	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php";?>

