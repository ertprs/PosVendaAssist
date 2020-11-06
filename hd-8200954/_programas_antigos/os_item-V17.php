<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

/*
if((($login_posto=='6359') or ($login_posto=='465'))and ($login_fabrica==6)){
$os=$_GET['os'];
	header ("Location: os_item_new.php?os=$os");
exit;
}
*/
#liberado takashi 06-11-2006 09:28 segundo email andre ricardo, angelica, leandro

/*
if($login_fabrica==11){
	$os=$_GET['os'];
	if (strlen($os) == 0) $os=$_POST['os'];

	$sql = "SELECT  tbl_os.data_digitacao
			FROM    tbl_os
			WHERE   tbl_os.os = $os";
	$res = pg_exec ($con,$sql) ;

	$data_digitacao = pg_result ($res,0,data_digitacao);
	
	if ( ($login_posto==6) or
		 ($data_digitacao>'2006-12-06 00:00:00' and $login_posto==1967) or  //Petel
		 ($data_digitacao>'2006-12-07 00:00:00' and $login_posto==14252) or //Jesualdo
		 ($data_digitacao>'2006-12-07 15:00:00' and $login_posto==14301) ){ //Aulik
		header ("Location: os_item_new.php?os=$os");
		exit;
	}
}
*/
if(strlen($_GET['os'])>0){
	$os=$_GET['os'];
	$sql = "SELECT motivo_atraso,observacao,os_reincidente FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	$motivo_atraso = pg_result($res,0,motivo_atraso);
	$observacao    = pg_result($res,0,observacao);
	$os_reincidente = pg_result($res,0,os_reincidente);
	if($os_reincidente=='t' AND ( strlen($motivo_atraso )==0 OR strlen($observacao)==0))
		header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
}

if (strlen($_GET['reabrir']) > 0)  $reabrir = $_GET['reabrir'];

//liberado para Lenoxx - Wellington  2006-12-20 14:02:53.073356-02
if ($login_fabrica==11){
	header ("Location: os_item_new.php?os=$os&reabrir=$reabrir");
	exit;
}


if($login_fabrica==6){
$os=$_GET['os'];
	header ("Location: os_item_new.php?os=$os&reabrir=$reabrir");
exit;
}

if( ( ($login_posto=='6359') OR ($login_posto == '11668') OR ($login_posto=='2405') ) AND ($login_fabrica==15) ){
$os=$_GET['os'];
	header ("Location: os_item_new.php?os=$os&reabrir=$reabrir");
exit;
}

//liberado Takashi 03-01 09:21 segundo HD 302
if(($login_fabrica==3) and ($login_posto=='5037' OR $login_posto=='595')){
$os=$_GET['os'];
	header ("Location: os_item_new.php?os=$os&reabrir=$reabrir");
exit;
}


$qtde_visita=4;
//echo "$login_fabrica";
if(($login_fabrica==24)){
$os=$_GET['os'];
	header ("Location: os_item_new.php?os=$os&reabrir=$reabrir");
exit;
}
$msg_erro = "";
$msg_previsao = "";

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_causa_defeito_os_item = pg_result ($res,0,pedir_causa_defeito_os_item);
$pedir_defeito_constatado_os_item = pg_result ($res,0,pedir_defeito_constatado_os_item);
$ip_fabricante = trim (pg_result ($res,0,ip_fabricante));
$ip_acesso     = $_SERVER['REMOTE_ADDR'];
$os_item_admin = "null";

#if ($login_fabrica == 3 AND strpos ($ip_acesso,$ip_fabricante) !== false ) $os_item_admin = "273";
#if ($login_fabrica == 3 AND strpos ($ip_acesso,"201.0.9.216") !== false ) $os_item_admin = "273";

if (strlen($_GET['reabrir']) > 0)     $reabrir = $_GET['reabrir'];
if (strlen($_GET['os']) > 0)          $os = $_GET['os'];
if (strlen($_POST['os']) > 0)         $os = $_POST['os'];

//VERIFICA SE É COMPRESSOR - - TAKASHI 26/10
if ($login_fabrica == 1) {
	$sql =	"SELECT tipo_os_cortesia
			FROM  tbl_os
			WHERE fabrica = $login_fabrica
			AND   os = $os;";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$tipo_os_cortesia = pg_result($res,0,tipo_os_cortesia);
		if ($tipo_os_cortesia == "Compressor") {
			$compressor='t';
		}
	}
}
//VERIFICA SE É COMPRESSOR - - TAKASHI 26/10
$sql = "SELECT  tbl_os.sua_os,
				tbl_os.fabrica,
				tbl_os.tipo_atendimento
		FROM    tbl_os
		WHERE   tbl_os.os = $os";
$res = pg_exec ($con,$sql) ;


if (@pg_numrows($res) > 0) {
	if (pg_result ($res,0,fabrica) <> $login_fabrica ) {
		header ("Location: os_cadastro.php");
		exit;
	}
	//validacao para bosch quando a OS for uma troca de produto não deverá vir para essa tela
	if($login_fabrica==20){
		$tipo_atendimento = pg_result($res,0,tipo_atendimento) ;
		if ($tipo_atendimento==13){
			header ("Location: os_finalizada_troca.php?os=$os");
			exit;
			echo $tipo_atendimento;
		}
	}
}

$sua_os = trim(pg_result($res,0,sua_os));

if (strlen($reabrir) > 0) {
	$sql = "UPDATE tbl_os SET data_fechamento = null, finalizada = null
			WHERE  tbl_os.os      = $os
			AND    tbl_os.fabrica = $login_fabrica
			AND    tbl_os.posto   = $login_posto;";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
}



//adicionado por Fabio 29/11/2006 - numero de itens na OS
$qtde_itens_mostrar="";
if (isset($_GET['n_itens']) AND strlen($_GET['n_itens'])>0){
	$qtde_itens_mostrar = $_GET['n_itens'];
	if ($qtde_itens_mostrar>10)$qtde_itens_mostrar=10;
	if ($qtde_itens_mostrar<0)$qtde_itens_mostrar=3;
}else {
	$qtde_itens_mostrar=3;
}
$numero_pecas_faturadas=0;
// fim do numero de linhas - Fabio 29/11/2006


//modificado por Fernando 02/08/2006 - Exclusao do item na OS qdo o mesmo estiver abaixo dos 30%.
//verifica se tem os_item amarrado na os_produto se nao tiver ele apaga os_produto.

$os_item = trim($_GET ['os_item']);

if($os_item > 0){
	
	if($os_item_old != $os_item){
		
		$os_item_old = $os_item;
		//seleciona a os_produto que contem a os_item quem não geraam pedido
		$sql = "SELECT os_produto FROM tbl_os_item WHERE os_item = $os_item AND pedido IS NULL";
		
		$res = pg_exec ($con,$sql);

		if(pg_numrows($res) == 1){
		
			$os_produto = pg_result($res,0,os_produto);
		
			$sql = "DELETE FROM tbl_os_item WHERE os_item = $os_item ";

			$res = pg_exec ($con,$sql);
		
	//verifica se tem os_item amarrada ao os_produto - caso nao tenha ele apaga o produto
			$sql = "SELECT count(os_produto) as os_produto_count FROM tbl_os_item WHERE os_produto = '$os_produto'; " ;

			$res = pg_exec($con,$sql);

			$os_produto_count = pg_result($res,0,os_produto_count);

			if( $os_produto_count == 0 ){
			
				$sql = "DELETE FROM tbl_os_produto WHERE os_produto = '$os_produto' AND os = '$os' ; " ;

				$res = pg_exec($con,$sql);
				$msg_erro_item .= "Item excluido com sucesso!";
			}

		}else{
			$msg_erro_item .= "Não foi encontrado o item.";
		}
	}else{ $msg_erro_item .= "Não foi encontrado o item."; }
}

$btn_acao = strtolower ($_POST['btn_acao']);


//$msg_erro = "";

if ($btn_acao == "gravar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	$defeito_constatado = $_POST ['defeito_constatado'];

	//Samuel 18-08 a pedido do Fabricio da Britania o campo Defeito constatado e solucao passam a ser obrigatorios
	if($login_fabrica==3){
		if(strlen($defeito_constatado)==0){
			$msg_erro .= "Informar o defeito constatado.<BR>";
		}
		if(strlen($solucao_os)==0){
			$msg_erro .= "Informar a solução.<BR>";
		}
	}



	//para a fabrica 11 é obrigatório aparencia_produto e acessorios, para as outras é mostrado na tela /os_cadastro.php
	if($login_fabrica==11){

		//APARENCIA
		if (strlen(trim($aparencia_produto)) == 0){
			$aparencia_produto = 'null';
			$msg_erro .= "Informar a Aparência do Produto.<BR>";
		}else{
			$aparencia_produto= "'".trim($aparencia_produto)."'";
			$sql = "UPDATE tbl_os SET aparencia_produto = $aparencia_produto
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		//ACESSORIOS
		if(strlen(trim($acessorios))==0){
			$acessorios = 'null';
			$msg_erro .= "Informar os Acessórios do produto.<BR>";
		}else{
			$acessorios= "'".trim($acessorios)."'";
			$sql = "UPDATE tbl_os SET acessorios = $acessorios
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}
	
	//Samuel 18-08 a pedido do Fabricio da Britania o campo Defeito constatado e solucao passam a ser obrigatorios
//echo "$msg_erro";
	if (strlen($defeito_constatado) == 0) $defeito_constatado = 'null';

	if (strlen ($defeito_constatado) > 0) {
		$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	//echo "asd $msg_erro";
	
	if (strlen ($msg_erro) == 0) {
		$xcausa_defeito = $_POST ['causa_defeito'];
		if (strlen ($xcausa_defeito) == 0) $xcausa_defeito = "null";
		if (strlen ($xcausa_defeito) > 0) {
			$sql = "UPDATE tbl_os SET causa_defeito = $xcausa_defeito
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";
			$res = @pg_exec ($con,$sql);
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
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	$obs = trim($_POST["obs"]);
	if (strlen($obs) > 0) $obs = "'".$obs."'";
	else                   $obs = "null";
	//takashi 07-08 a pedido do andre da tectoy o campo observação passa a ser obrigatorio
	if($login_fabrica==6){
		if(strlen($obs)==0){
		$msg_erro .= "Por favor preencher o campo Observação<BR>";
		}
	}
	//takashi 07-08 a pedido do andre da tectoy o campo observação passa a ser obrigatorio
	
	
	$tecnico_nome = trim($_POST["tecnico_nome"]);
		if (strlen($tecnico_nome) > 0) $tecnico_nome = "'".$tecnico_nome."'";
	else                   $tecnico_nome = "null";

if($login_fabrica<>1){
	$valores_adicionais = trim($_POST["valores_adicionais"]);
	$valores_adicionais = str_replace (",",".",$valores_adicionais);
	if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

	$justificativa_adicionais = trim($_POST["justificativa_adicionais"]);
	if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
	else                   $justificativa_adicionais = "null";

	$qtde_km = trim($_POST["qtde_km"]);
	$qtde_km = str_replace (",",".",$qtde_km);
	if (strlen($qtde_km) == 0) $qtde_km = "0";

	$qtde_visitas = trim($_POST["qtde_visitas"]);
	if (strlen($qtde_visitas) == 0) $qtde_visitas = "0";
	
	$qtde_hora = trim($_POST["qtde_hora"]);
	$qtde_hora = str_replace (",",".",$qtde_hora);
	if (strlen($qtde_hora) == 0) $qtde_hora = "0";
/*
	$laudo_tecnico = trim($_POST['laudo_tecnico']);
	if(strlen($laudo_tecnico) > 0) $laudo_tecnico = "'".$laudo_tecnico."'";
	else $laudo_tecnico = 'null';
,
					laudo_tecnico = $laudo_tecnico
*/
	$sql = "UPDATE	tbl_os SET obs = $obs, 
					tecnico_nome = $tecnico_nome, 
					qtde_km      = $qtde_km     ,
					qtde_hora = $qtde_hora,
					valores_adicionais = $valores_adicionais, 
					justificativa_adicionais = $justificativa_adicionais,
					qtde_visitas = $qtde_visitas
			WHERE  tbl_os.os    = $os
			AND    tbl_os.posto = $login_posto;";
	$res = @pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
//echo "a:$sql";
}

//echo ".$compressor";
//visita compressores takashi
if($login_fabrica==1 and $compressor=='t' and $login_posto == 6359){
for ( $i = 0 ; $i < $qtde_visita ; $i++ ) {
	$xos_visita            = trim($_POST['os_visita_'. $i]);
	$xdata                 = fnc_formata_data_pg(trim($_POST['visita_data_'. $i]));
	$xxdata                = str_replace("'","",$xdata);
	$xhora_chegada_cliente = trim($_POST['visita_hr_inicio_'. $i]);
	$xhora_saida_cliente   = trim($_POST['visita_hr_fim_'. $i]);
	$xkm_chegada_cliente   = trim($_POST['visita_km_'. $i]);
	$xkm_chegada_cliente = str_replace (",",".",$xkm_chegada_cliente);
	$valores_adicionais   = trim($_POST['valores_adicionais_'. $i]);
	$justificativa_adicionais   = trim($_POST['justificativa_adicionais_'. $i]);


	$valores_adicionais = str_replace (",",".",$valores_adicionais);
	if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

	if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
	else                   $justificativa_adicionais = "null";



	$xhora_chegada_cliente = "'$xxdata ".$xhora_chegada_cliente."'";
	$xhora_saida_cliente   = "'$xxdata ".$xhora_saida_cliente."'";
	//echo "$i data:$xxdata,inicio $xhora_chegada_cliente,fim $xhora_saida_cliente, km: $xkm_chegada_cliente os $xos_visita<BR>";
	if((strlen($xxdata)>0) and (strlen($xhora_chegada_cliente)>0) and (strlen($xhora_saida_cliente)>0) and (strlen($xkm_chegada_cliente)>0) and (strlen($xos_visita)==0)){
		$sql = "INSERT INTO tbl_os_visita (
							os                   ,
							data                 ,
							hora_chegada_cliente ,
							hora_saida_cliente   ,
							km_chegada_cliente   ,
							hora_chegada_sede    ,
							hora_saida_sede      ,
							valor_adicional      ,
							justificativa_valor_adicional
						) VALUES (
							$os                    ,
							$xdata                 ,
							$xhora_chegada_cliente ,
							$xhora_saida_cliente   ,
							$xkm_chegada_cliente   ,
							current_timestamp      ,
							current_timestamp      ,
							$valores_adicionais    ,
							$justificativa_adicionais
						)";
		$res = @pg_exec ($con,$sql);
	//	echo "inseriu $sql<BR>";
	}
	if((strlen($xxdata)>0) and (strlen($xhora_chegada_cliente)>0) and (strlen($xhora_saida_cliente)>0) and (strlen($xkm_chegada_cliente)>0) and (strlen($xos_visita)>0)){
		$sql = "UPDATE tbl_os_visita set
						data                 = $xdata                 ,
						hora_chegada_cliente = $xhora_chegada_cliente ,
						hora_saida_cliente   = $xhora_saida_cliente   ,
						km_chegada_cliente   = $xkm_chegada_cliente   ,
						valor_adicional      = $valores_adicionais    ,
						justificativa_valor_adicional = $justificativa_adicionais
					WHERE os = $os
					AND   os_visita = $xos_visita";
//	echo "atualiza $sql";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if((strlen($xos_visita)>0) and ($xxdata=="null")){
		$sql = "DELETE FROM tbl_os_visita
						WHERE  tbl_os_visita.os        = $os
						AND    tbl_os_visita.os_visita = $xos_visita;";
	//	echo "apaga: $sql";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	
	}
}
		$sql = "UPDATE tbl_os_extra set 
									valor_por_km=0.65,
									valor_total_hora_tecnica=0.4
						WHERE os=$os";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
//if($ip=="201.42.91.92") echo $sql;
}
//visita compressores takashi

	if (strlen ($type) > 0) $type = "'".trim($_POST['type'])."'";
	else                    $type = 'null';
	if (strlen ($type) > 0) {
		$sql = "UPDATE tbl_os SET type = $type
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $login_posto;";
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

/* ################################################################
	if (strlen ($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_os_produto
				WHERE  tbl_os_produto.os            = tbl_os.os
				AND    tbl_os_item.os_produto       = tbl_os_produto.os_produto
				AND    tbl_os_item.pedido           IS NULL
				AND    tbl_os_item.liberacao_pedido IS NULL
				AND    tbl_os_produto.os = $os";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
################################################################ */

	if (strlen ($msg_erro) == 0) {
		$qtde_item = $_POST['qtde_item'];
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$xos_item        = $_POST['os_item_'        . $i];
			$xos_produto     = $_POST['os_produto_'     . $i];
			$xproduto        = $_POST['produto_'        . $i];
			$xserie          = $_POST['serie_'          . $i];
			$xposicao        = $_POST['posicao_'        . $i];
			$xpeca           = $_POST['peca_'           . $i];
			$xqtde           = $_POST['qtde_'           . $i];
			$xdefeito        = $_POST['defeito_'        . $i];
			$xservico        = $_POST['servico_'        . $i];
			$xpcausa_defeito = $_POST['pcausa_defeito_' . $i];
			//takashi 03-01-06 HD 934
			$admin_peca      = $_POST["admin_peca_"     . $i]; //aqui
			if(strlen($admin_peca)==0) $admin_peca ="null"; //aqui
			//takashi 03-01-06 HD 934
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

			if (strlen($xposicao) == 0) $xposicao = 'null';
			else                        $xposicao = "'" . $xposicao . "'";

/*			if ($login_fabrica == 5 and strlen($causa_defeito) == 0)
				$msg_erro = "Selecione a causa do defeito";
			elseif ($login_fabrica <> 5 and strlen($causa_defeito) == 0)
				$causa_defeito = 'null';*/

			if($login_fabrica == 20){
				$xdefeito = "141";  //Danificado
				$xservico = "258";  //Troca de Peça
			}

			if (strlen ($xos_produto) > 0 AND strlen($xpeca) == 0) {
				$sql = "DELETE FROM tbl_os_produto
						WHERE  tbl_os_produto.os         = $os
						AND    tbl_os_produto.os_produto = $xos_produto";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}else{
				if ($login_fabrica == 3 && strlen($xpeca) > 0) {
					$sqlX = "SELECT referencia, TO_CHAR (previsao_entrega,'DD/MM/YYYY') AS previsao
							 FROM tbl_peca
							 WHERE UPPER(referencia_pesquisa) = UPPER('$xpeca')
							 AND   fabrica = $login_fabrica
							 AND   previsao_entrega > date(current_date + INTERVAL '20 days');";
					$resX = pg_exec($con,$sqlX);
					if (pg_numrows($resX) > 0) {
						$peca_previsao = pg_result($resX,0,referencia);
						$previsao      = pg_result($resX,0,previsao);

						$msg_previsao  = "O pedido da peça $peca_previsao foi efetivado. A previsão de disponibilidade desta peça será em $previsao. A fábrica tomará as medidas necessárias par o atendimento ao consumidor.";
					}
				}

				if (strlen($xpeca) > 0 and strlen($msg_erro) == 0) {
					$xpeca    = strtoupper ($xpeca);

					if (strlen ($xqtde) == 0) $xqtde = "1";
					
					if ($login_fabrica == 1 && intval($xqtde) == 0) $msg_erro .= " O item $xpeca está sem quantidade, por gentileza informe a quantidade para este item. ";

					if (strlen ($xproduto) == 0) {
						$sql = "SELECT tbl_os.produto
								FROM   tbl_os
								WHERE  tbl_os.os      = $os
								AND    tbl_os.fabrica = $login_fabrica;";
						$res = pg_exec ($con,$sql);

						if (pg_numrows($res) > 0) {
							$xproduto = pg_result ($res,0,0);
						}
					}else{
						$sql = "SELECT tbl_produto.produto
								FROM   tbl_produto
								JOIN   tbl_linha USING (linha)
								WHERE  tbl_produto.referencia_pesquisa = '$xproduto'
								AND    tbl_linha.fabrica = $login_fabrica";
						$res = pg_exec ($con,$sql);

						if (pg_numrows ($res) == 0) {
							$msg_erro .= "Produto $xproduto não cadastrado";
							$linha_erro = $i;
						}else{
							$xproduto = pg_result ($res,0,produto);
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
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							
							$res = pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
							$xos_produto  = pg_result ($res,0,0);
						}else{
							$sql = "UPDATE tbl_os_produto SET
										os      = $os      ,
										produto = $xproduto,
										serie   = $xserie
									WHERE os_produto = $xos_produto;";
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
						if (strlen ($msg_erro) > 0) {
							break ;
						}else{

							$xpeca = strtoupper ($xpeca);

							if (strlen($xpeca) > 0) {
								$sql = "SELECT tbl_peca.*
										FROM   tbl_peca
										WHERE  UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca')
										AND    tbl_peca.fabrica = $login_fabrica;";
								$res = pg_exec ($con,$sql);

								if (pg_numrows ($res) == 0) {
									$msg_erro .= "Peça $xpeca não cadastrada";
									$linha_erro = $i;
								}else{
									$xpeca = pg_result ($res,0,peca);
								}

								if (strlen($xdefeito) == 0) $msg_erro .= "Informar o defeito da peça"; #$defeito = "null";
								if (strlen($xservico) == 0) $msg_erro .= "Informar o serviço realizado"; #$servico = "null";

								//if ($login_fabrica == 5 and strlen($xcausa_defeito) == 0) $msg_erro = "Selecione a causa do defeito.";
								//elseif(strlen($xcausa_defeito) == 0)					$xcausa_defeito = 'null';

								if(strlen($xpcausa_defeito) == 0) $xpcausa_defeito = 'null';

								if (strlen ($msg_erro) == 0) {
									if (strlen($xos_item) == 0){
									/*######## TAKASHI 06-12 ##############
									# CONFORME HD 853 -FABRICIO BRITANIA  #
									# Fica proibido o lançamento de peças #
									# em OS a mais de 30 dias abertas.    #
									#####################################*/
									
									if($login_fabrica==3){
										
										$sql_bloqueio = "SELECT SUM(current_date - data_abertura)as bloqueio FROM tbl_os WHERE os=$os";
										$res_bloqueio = pg_exec($con, $sql_bloqueio);
										
										$dias_bloqueio = pg_result($res_bloqueio,0,bloqueio);
										
										if($dias_bloqueio>30 and $xservico==20){
													$sql = "SELECT  tbl_os.sua_os,
																	to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura
															FROM    tbl_os
															WHERE   tbl_os.os = $os";
													$res = pg_exec ($con,$sql) ;
													
													$sua_os = pg_result($res,0,sua_os);
													$data_abertura = pg_result($res,0,data_abertura);
													
													$msg_bloqueio = "<center><font size='2' face='verdana'>OS ($sua_os) com data de abertura ($data_abertura) </font><font size='2' face='verdana' color='#000000'>superior a 30 dias</font><font size='2' face='verdana'>: para inserir peças, favor enviar e-mail para <B>assistenciatecnica@britania.com.br</b> informando o número da OS, código da peça e justificativa.</font></center>";
													$msg_erro .= " ";
									
										}
									
									}
									/*################## TAKASHI 06-12    #*/

									if(strlen($msg_bloqueio)==0){
//takashi 03-01-06 HD 934
										$sql = "INSERT INTO tbl_os_item (
													os_produto        ,
													posicao           ,
													peca              ,
													qtde              ,
													defeito           ,
													causa_defeito     ,
													servico_realizado ,
													admin
												)VALUES(
													$xos_produto    ,
													$xposicao       ,
													$xpeca          ,
													$xqtde          ,
													$xdefeito       ,
													$xpcausa_defeito,
													$xservico       ,
													$admin_peca
											);";
//echo "1: $sql";
										$res = @pg_exec ($con,$sql);
										$msg_erro .= pg_errormessage($con);
									}
									}else{
										$sql = "UPDATE tbl_os_item SET
													os_produto        = $xos_produto    ,
													posicao           = $xposicao       ,
													peca              = $xpeca          ,
													qtde              = $xqtde          ,
													defeito           = $xdefeito       ,
													causa_defeito     = $xpcausa_defeito,
													servico_realizado = $xservico       ,
													admin             = $admin_peca
												WHERE os_item = $xos_item;";
//takashi 03-01-06 HD 934
										$res = @pg_exec ($con,$sql);
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

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res      = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		//$msg_erro .= "SELECT fn_valida_os_item($os, $login_fabrica)";
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: os_finalizada.php?os=$os");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
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
					tbl_linha.nome AS linha_nome   ,
					tbl_posto_fabrica.codigo_posto ,
					tbl_os_extra.orientacao_sac    ,
					tbl_os_extra.os_reincidente AS reincidente_os
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_produto USING (produto)
			LEFT JOIN    tbl_linha   ON tbl_produto.linha = tbl_linha.linha
			WHERE   tbl_os.os = $os";
//echo "$sql";
	$res = pg_exec ($con,$sql) ;

	$defeito_constatado = pg_result ($res,0,defeito_constatado);
	$aparencia_produto	= pg_result ($res,0,aparencia_produto);
	$acessorios			= pg_result ($res,0,acessorios);
	$causa_defeito      = pg_result ($res,0,causa_defeito);
	$linha              = pg_result ($res,0,linha);
	$linha_nome         = pg_result ($res,0,linha_nome);
	$consumidor_nome    = pg_result ($res,0,consumidor_nome);
	$sua_os             = pg_result ($res,0,sua_os);
	$type               = pg_result ($res,0,type);
	$produto_os         = pg_result ($res,0,produto);
	$produto_referencia = pg_result ($res,0,referencia);
	$produto_descricao  = pg_result ($res,0,descricao);
	$produto_voltagem   = pg_result ($res,0,voltagem);
	$produto_serie      = pg_result ($res,0,serie);
	$qtde_produtos      = pg_result ($res,0,qtde_produtos);
	$obs                = pg_result ($res,0,obs);
	$codigo_posto       = pg_result ($res,0,codigo_posto);
	$defeito_reclamado       = pg_result ($res,0,defeito_reclamado);
	$os_reincidente     = pg_result ($res,0,reincidente_os);
	$consumidor_revenda = pg_result ($res,0,consumidor_revenda);
	$solucao_os         = pg_result ($res,0,solucao_os);
	$tecnico_nome       = pg_result ($res,0,tecnico_nome);
	$valores_adicionais = pg_result ($res,0,valores_adicionais);
	$justificativa_adicionais = pg_result ($res,0,justificativa_adicionais);
	$qtde_km            = pg_result ($res,0,qtde_km);
	$qtde_hora            = pg_result ($res,0,qtde_hora);
	$orientacao_sac	= pg_result ($res,0,orientacao_sac);
#	$orientacao_sac = html_entity_decode ($orientacao_sac,ENT_QUOTES);
#	$orientacao_sac = str_replace ("<br />","",$orientacao_sac);
	//$laudo_tecnico	= pg_result ($res,0,laudo_tecnico);
	$qtde_visitas= pg_result ($res,0,qtde_visitas);
	$revenda_cnpj= pg_result ($res,0,revenda_cnpj);	




//if ($ip == '201.0.9.216') echo $sql;
	if (strlen($os_reincidente) > 0) {
		$sql = "SELECT tbl_os.sua_os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os_reincidente
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = @pg_exec ($con,$sql) ;

		if (pg_numrows($res) > 0) $sua_os_reincidente = trim(pg_result($res,0,sua_os));
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
$resX = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result ($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';

	$pergunta_qtde_os_item = pg_result ($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';

	$os_item_serie = pg_result ($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';

	$os_item_aparencia = pg_result ($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';

	$qtde_item = pg_result ($resX,0,qtde_item_os);
	if (strlen ($qtde_item) == 0) $qtde_item = 5;
}

$resX = pg_exec ($con,"SELECT item_aparencia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica");
$posto_item_aparencia = pg_result ($resX,0,0);

$title = "Telecontrol - Assistência Técnica - Ordem de Serviço";
$body_onload = "javascript: document.frm_os.defeito_constatado.focus()";

$layout_menu = 'os';
include "cabecalho.php";

$imprimir = $_GET['imprimir'];
if (strlen ($os) == 0) $os = $_GET['os'];

if (strlen ($imprimir) > 0 AND strlen ($os) > 0 ) {
	echo "<script language='javascript'>";
	echo "window.open ('os_print.php?os=$os','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
	echo "</script>";
}

include "javascript_pesquisas.php"
?>

<script language="JavaScript">
//funcao lista basica tectoy, posicao, serie inicial, serie final
function fnc_pesquisa_lista_basica2 (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
		if (tipo == "tudo") {
			url = "<? echo $url; ?>2.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}

		if (tipo == "referencia") {
			url = "<? echo $url; ?>2.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}

		if (tipo == "descricao") {
			url = "<? echo $url; ?>2.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		}
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto          = produto_referencia;
		janela.referencia       = peca_referencia;
		janela.descricao        = peca_descricao;
		janela.preco            = peca_preco;
		janela.qtde                     = peca_qtde;
		janela.focus();

}





function fnc_pesquisa_lista_basica (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
        var url = "";
        if (tipo == "tudo") {
                url = "<? echo $url; ?>.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "referencia") {
                url = "<? echo $url; ?>.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }

        if (tipo == "descricao") {
                url = "<? echo $url; ?>.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
        }
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.produto          = produto_referencia;
        janela.referencia       = peca_referencia;
        janela.descricao        = peca_descricao;
        janela.preco            = peca_preco;
        janela.qtde                     = peca_qtde;
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
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo;
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



function limpa_campo(entrada,id){
	if(entrada.value==''){
		var descricao =  document.getElementById("descricao_"+id);
		var qtde      =  document.getElementById("qtde_"+id);
		descricao.value = '';
		qtde.value = '';
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
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}
</style>
<p>

<?
$os_item = trim($_GET['os_item']);
if($os_item > 0){
	echo "<FONT COLOR=\"#FF0033\"><B>$msg_erro_item</B></FONT>";
	$msg_erro_item = 0;
}
?>


<?
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
	//$laudo_tecnico                  = $_POST["laudo_tecnico"];

	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
	if (strpos ($msg_erro,"Type informado") > 0) $msg_erro = "Type informado para o produto não é válido";

	echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
	echo "<tr>";
	echo "<td height='27' valign='middle' align='center'>";
	echo "<b><font face='Arial, Helvetica, sans-serif' color='#FF3333'>";

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectada a seguinte divergência: <br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro . $msg_bloqueio;

	echo "</font></b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
} 
if($login_fabrica==19){
	echo "<table width='600' border='0' cellpadding='3' cellspacing='5' align='center' bgcolor='#B4D6E1'>";
	echo "<tr>";
	echo "<td valign='middle' align='center'>";
	echo "<b><font face='Arial, Helvetica, sans-serif' color='#465357' size='1'>Caso algum tipo de
defeito Constatado não esteja relacionado nas opções, favor informar o Depto de Assistência Técnica
através do DDG 0800-160212 ou (011) 6165-7521 ou mesmo através do E-mail :
osg@lorenzetti.com.br</font></b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

if (strlen ($msg_previsao) > 0) {

	echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
	echo "<tr>";
	echo "<td height='27' valign='middle' align='center'>";
	echo "<b><font face='Arial, Helvetica, sans-serif' color='##3333FF'>";

	echo $msg_previsao ;

	echo "</font></b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
} 


?>


<?
#------------ Pedidos via Distribuidor -----------#
$resX = pg_exec ($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
if (pg_result ($resX,0,0) == 't') {
	$resX = pg_exec ($con,"SELECT tbl_posto.nome FROM tbl_posto JOIN tbl_posto_linha ON tbl_posto_linha.distribuidor = tbl_posto.posto WHERE tbl_posto_linha.posto = $login_posto AND tbl_posto_linha.linha = $linha");
	if (pg_numrows ($resX) > 0) {
		echo "<center>Atenção! Peças da linha <b>$linha_nome</b> serão atendidas pelo distribuidor.<br><font size='+1'>" . pg_result ($resX,0,nome) . "</font></center><p>";
	}else{
		echo "<center>Peças da linha <b>$linha_nome</b> serão atendidas pelo fabricante.</center><p>";
	}
}

if (strlen($sua_os_reincidente) > 0 and $login_fabrica == 6) {
	echo "<br><br>";

	echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
	echo "<tr>";

	echo "<td valign='middle' align='center'>";
	echo "<font face='Verdana,Arial, Helvetica, sans-serif' color='#FF3333' size='2'><b>";
	echo "ESTA ORDEM DE SERVIÇO É REINCIDENTE MENOR QUE 30 DIAS.<br>
	O NÚMERO DE SÉRIE É O MESMO UTILIZADO NA ORDEM DE SERVIÇO: $sua_os_reincidente.<br>
	NÃO SERÁ PAGO O VALOR DE MÃO-DE-OBRA PARA A ORDEM DE SERVIÇO ATUAL.<BR>
	ELA SERVIRÁ APENAS PARA PEDIDO DE PEÇAS.";
	echo "</b></font>";
	echo "</td>";

	echo "</tr>";
	echo "</table>";

	echo "<br><br>";
}

?>


<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="os"        value="<?echo $os?>">
		<input type="hidden" name="voltagem"  value="<?echo $produto_voltagem?>">
		<input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>

		<p>


<?

if($compressor<>'t'){
//echo "<input type='hidden' name='laudo_tecnico'        value='$laudo_tecnico'>";
}

?>
<? if ($login_fabrica == 1) { ?>
<?	if($revenda_cnpj=="53296273000191"){	
		echo "<script>alert('"._("Produtos de estoque de revenda deverão ser digitados na opção CADASTRO DE ORDEM DE SERVIÇO DE REVENDA. Se o produto em questão for de estoque de revenda, gentileza digitar nessa opção. Pois em caso de digitações incorretas, a B&D fará a exclusão da OS.")."')</script>";
	
	}
?>
		<table border="0" cellspacing="0" cellpadding="0" align="center">
		<tr>
			<td nowrap><a href="os_print.php?os=<? echo $os ?>" target="_blank" alt="Imprimir OS"><img src="imagens/btn_imprimir.gif"></a></td>
		</tr>
		</table>
<? } ?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
<?
		if ($login_fabrica == 1) echo $codigo_posto;
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

			<? if ($login_fabrica == 19) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
				<?
				echo $qtde_produtos;
				?>
				</b>
				</font>
			</td>
			<? } ?>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo "$produto_referencia - $produto_descricao"; ?></b>
				</font>
			</td>

			<td nowrap>
			<?
			if ($login_fabrica == 1) {
				echo "<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\">Versão/Type</font>";
				echo "<br>";
				echo "<select name='type' class ='frm'>\n";
				echo "<option value=''></option>\n";
				echo "<option value='Tipo 1'"; if($type == 'Tipo 1') echo " selected"; echo " >Tipo 1</option>\n";
				echo "<option value='Tipo 2'"; if($type == 'Tipo 2') echo " selected"; echo " >Tipo 2</option>\n";
				echo "<option value='Tipo 3'"; if($type == 'Tipo 3') echo " selected"; echo " >Tipo 3</option>\n";
				echo "<option value='Tipo 4'"; if($type == 'Tipo 4') echo " selected"; echo " >Tipo 4</option>\n";
				echo "<option value='Tipo 5'"; if($type == 'Tipo 5') echo " selected"; echo " >Tipo 5</option>\n";
				echo "<option value='Tipo 6'"; if($type == 'Tipo 6') echo " selected"; echo " >Tipo 6</option>\n";
				echo "<option value='Tipo 7'"; if($type == 'Tipo 7') echo " selected"; echo " >Tipo 7</option>\n";
				echo "<option value='Tipo 8'"; if($type == 'Tipo 8') echo " selected"; echo " >Tipo 8</option>\n";
				echo "<option value='Tipo 9'"; if($type == 'Tipo 9') echo " selected"; echo " >Tipo 9</option>\n";
				echo "<option value='Tipo 10'"; if($type == 'Tipo 10') echo " selected"; echo " >Tipo 10</option>\n";
				echo "<\select>&nbsp;";
			}
			?>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>
		</tr>
		</table>

<?





?>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
	
		<?
//  		echo "<tr>";
//  		echo "<td>$defeito_reclamado</td>";
//  		echo "</tr>";

		
		
		if($login_fabrica==11){
			//aparencia do produto
			echo "<tr>";
	 		echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Aparência do Produto<br></font><input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\" </font></td>";
		
			//acessórios
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, 				san-serif'>Acessórios<br></font><input class='frm' type='text' name='acessorios' size='30' value='$acessorios' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\" </td>";
	 		echo "</tr>";
		}
		?>
		<tr>
			<?
			if($login_fabrica==20 AND ($tipo_atendimento==11 OR $tipo_atendimento==12)){}else{
				if ($pedir_defeito_constatado_os_item <> 'f') {

			?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if($login_fabrica=='20'){echo "Reparo";}else { echo "Defeito Constatado";} ?></font>
				<br>
				<select name="defeito_constatado" size="1" class="frm">
					<option selected></option>
				<?
				$sql = "SELECT defeito_constatado_por_familia, defeito_constatado_por_linha FROM tbl_fabrica WHERE fabrica = $login_fabrica";
				
 //echo "<br>".nl2br($sql)."<br>";
				$res = pg_exec ($con,$sql);
				$defeito_constatado_por_familia = pg_result ($res,0,0) ;
				$defeito_constatado_por_linha   = pg_result ($res,0,1) ;

				if ($defeito_constatado_por_familia == 't') {
					$sql = "SELECT familia FROM tbl_produto WHERE produto = $produto_os";

//echo "<br>".nl2br($sql)."<br>";
					$res = pg_exec ($con,$sql);
					$familia = pg_result ($res,0,0) ;

					if ($login_fabrica == 1){

						$sql = "SELECT tbl_defeito_constatado.* FROM tbl_familia  JOIN   tbl_familia_defeito_constatado USING(familia) JOIN   tbl_defeito_constatado USING(defeito_constatado) ";
						if ($linha == 198) $sql .= " JOIN tbl_produto_defeito_constatado USING(defeito_constatado) ";
						$sql .= " WHERE  tbl_defeito_constatado.fabrica = $login_fabrica AND tbl_familia_defeito_constatado.familia = $familia";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						if ($linha == 198) $sql .= " AND tbl_produto_defeito_constatado.produto = $produto_os ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}else{
						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_familia
								JOIN   tbl_familia_defeito_constatado USING(familia)
								JOIN   tbl_defeito_constatado         USING(defeito_constatado)
								WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
									    AND ativo = 't' 
								AND    tbl_familia_defeito_constatado.familia = $familia";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}
				}else{

					if ($defeito_constatado_por_linha == 't') {
						$sql   = "SELECT linha FROM tbl_produto WHERE produto = $produto_os";
						$res   = pg_exec ($con,$sql);
						$linha = pg_result ($res,0,0) ;

						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_defeito_constatado
								JOIN   tbl_linha USING(linha)
								WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
								AND    ativo = 't'
								AND    tbl_linha.linha = $linha";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}else{
						$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_defeito_constatado
							WHERE  tbl_defeito_constatado.fabrica = $login_fabrica
								AND    ativo = 't' ";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						if ($login_fabrica ==11){$sql .= " ORDER BY tbl_defeito_constatado.codigo";
						}else{$sql .= " ORDER BY tbl_defeito_constatado.descricao";}
					}
				}
//adicionado para listar todos os defeitos constatados para a Latina
//Modificado por Fernando
				if ($login_fabrica == "15" or $login_fabrica == "11" or $login_fabrica=="2") {
					$sql = "SELECT tbl_defeito_constatado.* 
							FROM tbl_defeito_constatado 
							WHERE fabrica = $login_fabrica
							ORDER BY tbl_defeito_constatado.descricao";
						//echo $sql;
				}


				#--------- Bosch ----------
				if ($login_fabrica == "20") {
					$sql = "SELECT tbl_defeito_constatado.* 
							FROM tbl_defeito_constatado 
							JOIN tbl_produto_defeito_constatado 
								ON  tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado 
								AND tbl_produto_defeito_constatado.produto = $produto_os
							WHERE fabrica = $login_fabrica
							ORDER BY tbl_defeito_constatado.descricao";
						//echo $sql;
				}

				$res = pg_exec ($con,$sql) ;

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option ";
					if ($defeito_constatado == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
					echo pg_result ($res,$i,codigo) ." - ". pg_result ($res,$i,descricao) ;
					echo "</option>";
				}
				?>
				</select>

			</td>
			<? 
if($ip=="201.42.46.223"){echo "$sql";}
} 
//if ($ip=="201.26.143.50") echo $sql;
			?>

			<?if ($pedir_causa_defeito_os_item <> 'f' and $login_fabrica <> 5 ) { ?>
			<td nowrap>
				<?
				if ($login_fabrica == 1){
					echo "<INPUT TYPE='hidden' name='name='causa_defeito' value='149'>";
				}else{
				?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito</font>
				<br>
				<select name="causa_defeito" size="1" class="frm">
					<option selected></option>
<?
					$sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica ORDER BY codigo, descricao";
					$res = pg_exec ($con,$sql) ;

					for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
						echo "<option ";
						if ($causa_defeito == pg_result ($res,$i,causa_defeito) ) echo " selected ";
						echo " value='" . pg_result ($res,$i,causa_defeito) . "'>" ;
						echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
						echo "</option>\n";
					}
?>
				</select>
				<? } ?>
			</td>
			<? } ?>
		
		</tr>
		</table>
<?//identificacao?>
		<?if ($pedir_solucao_os_item <> 'f') { ?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<?
					if($login_fabrica<>20) {echo "Solução";}
					else echo "Identificação";
				?>
				</font>
				<br>
				<select name="solucao_os" size="1" class="frm">
					<option value=""></option>
				<?
		
					$sql = "SELECT *
							FROM   tbl_servico_realizado
							WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

					if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 15) {
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					}

					if ($login_fabrica == 1) {
						if ($login_reembolso_peca_estoque == 't') {
							$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'Troca de pe%' ";
							$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
							if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
						}else{
							$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
							if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
						}
					}
					if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS NOT TRUE ";

					
					$sql .= " AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
//					if ($ip == "201.0.9.216") { echo nl2br($sql);}
					$res = pg_exec ($con,$sql) ;

					if (pg_numrows($res) == 0) {
						$sql = "SELECT *
								FROM   tbl_servico_realizado
								WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

						if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
							$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						}

						if ($login_fabrica == 1) {
							if ($login_reembolso_peca_estoque == 't') {
								$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'Troca de pe%' ";
								$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
							}else{
								$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
								$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
							}
						}

						$sql .=	" AND tbl_servico_realizado.linha IS NULL
								AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
						//if ($ip == "201.0.9.216") { echo nl2br($sql);exit;}
						$res = pg_exec ($con,$sql) ;
					}

					for ($x = 0 ; $x < pg_numrows($res) ; $x++ ) {
						echo "<option ";
						if ($solucao_os == pg_result ($res,$x,servico_realizado)) echo " selected ";
						echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
						echo pg_result ($res,$x,descricao) ;
						if (pg_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
						echo "</option>";
					}

				?>
				</select>


			</td>
					<?
						if ($login_fabrica==3){
					?>
					<td align="right" nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Itens</font><br>
					
						<select name="n_itens" size="1" class="frm" onchange='javascript:document.location.href="<? echo $PHP_SELF ?>?os=<? echo $os ?>&n_itens="+this.value'>
							<option value='3' <? if ($qtde_itens_mostrar==3)echo " selected"; ?>>3</option>
							<option value='5' <? if ($qtde_itens_mostrar==5)echo " selected"; ?>>5</option>
						</select>
					
					<? } ?>
		</tr>
		</table>
		<? } 
		}?>



<?
		
// SOMENTE LORENZETTI
if ($login_fabrica == 19){
?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Técnico</font>
				<br>
				<input type='text' name='tecnico_nome' size='20' maxlength='20' value='<? echo $tecnico_nome ?>'>
			</td>
		</tr>
		</table>
<?
}
?>

		
		
		<?
		### LISTA ITENS DA OS QUE POSSUEM PEDIDOS
		if(strlen($os) > 0){
		//takashi 03-01-06 HD 934
			$sql = "SELECT  tbl_os_item.pedido                                  ,
							tbl_pedido.pedido_blackedecker  AS pedido_blackedecker,
							tbl_os_item.posicao                                 ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.causa_defeito                           ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_causa_defeito.descricao AS causa_defeito_descricao,
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
					JOIN    tbl_pedido                 ON tbl_os_item.pedido       = tbl_pedido.pedido
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_causa_defeito     ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido NOTNULL
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_exec ($con,$sql) ;

			if(pg_numrows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
				echo "<tr height='20' bgcolor='#666666'>";

				echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedidos já enviados ao fabricante</b></font></td>";

				echo "</tr>";
				echo "<tr height='20' bgcolor='#666666'>";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>";
				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";
				}
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";

				echo "</tr>";

				$numero_pecas_faturadas=pg_numrows($res);

				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$faturado      = pg_numrows($res);
						$fat_pedido    = pg_result($res,$i,pedido);
						$fat_pedido_blackedecker = pg_result($res,$i,pedido_blackedecker);
						$posicao       = pg_result($res,$i,posicao);
						$fat_peca      = pg_result($res,$i,referencia);
						$fat_descricao = pg_result($res,$i,descricao);
						$fat_qtde      = pg_result ($res,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>";
						if ($login_fabrica == 1) echo $fat_pedido_blackedecker; else echo $fat_pedido;
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
		//takashi 03-01-06 HD 934
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.posicao                                 ,
							tbl_os_item.admin  as admin_peca                    ,
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
					AND     tbl_os_item.liberacao_pedido NOTNULL
					AND     tbl_os_item.liberacao_pedido IS FALSE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_exec ($con,$sql) ;

			if(pg_numrows($res) > 0) {
				$col = 4;
				if($login_fabrica == 14){ $col = 6; }
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				if ($login_fabrica <> 6) {
					echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia</b></font></td>\n";
				}else{
					echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças pendentes</b></font></td>\n";
				}

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";
				if($login_fabrica == 14){ echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Excluir</b></font></td>\n";	}
				echo "</tr>\n";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$recusado      = pg_numrows($res);
						$rec_item      = pg_result($res,$i,os_item);
						$rec_obs       = pg_result($res,$i,obs);
						$posicao       = pg_result($res,$i,posicao);
						$rec_peca      = pg_result($res,$i,referencia);
						$rec_descricao = pg_result($res,$i,descricao);
						$rec_qtde      = pg_result($res,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						if ($login_fabrica == 14) {
							echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$posicao</font></td>";
						}
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";
						if($login_fabrica == 14){ echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='$PHP_SELF?os=$os&os_item=$rec_item'><IMG SRC=\"imagens/btn_excluir.gif\" ALT=\"Excluir\"></font></a></td>";	}

						echo "</tr>\n";

				}
				echo "</table>\n";
			}
		}

		### LISTA ITENS DA OS FORAM LIBERADAS E AINDA NÃO POSSEM PEDIDO
		if(strlen($os) > 0){
		//takashi 03-01-06 HD 934
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.admin  as admin_peca                    ,
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
					AND     tbl_os_item.liberacao_pedido NOTNULL
					AND     tbl_os_item.liberacao_pedido IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";


			$res = pg_exec ($con,$sql) ;

			if(pg_numrows($res) > 0) {
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

				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$recusado      = pg_numrows($res);
						$rec_item      = pg_result($res,$i,os_item);
						$rec_obs       = pg_result($res,$i,obs);
						$rec_peca      = pg_result($res,$i,referencia);
						$rec_descricao = pg_result($res,$i,descricao);
						$rec_qtde      = pg_result($res,$i,qtde);

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
				$resX = @pg_exec($con,$sql);
				$inicio_itens = @pg_numrows($resX);
			}else{
				$inicio_itens = 0;
			}
//takashi 03-01-06 HD 934
			$sql = "SELECT  tbl_os_item.os_item                                                ,
							tbl_os_item.pedido                                                 ,
							tbl_os_item.qtde                                                   ,
							tbl_os_item.causa_defeito                                          ,
							tbl_os_item.posicao                                                ,
							tbl_os_item.admin  as admin_peca                                   ,
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
					AND     tbl_os_item.liberacao_pedido ISNULL
					ORDER BY tbl_os_item.os_item;";
			$res = pg_exec ($con,$sql) ;

			if (pg_numrows($res) > 0) {
				$fim_itens = $inicio_itens + pg_numrows($res);
				$i = 0;
				for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
					$os_item[$k]                 = pg_result($res,$i,os_item);
					$os_produto[$k]              = pg_result($res,$i,os_produto);
					$pedido[$k]                  = pg_result($res,$i,pedido);
					$peca[$k]                    = pg_result($res,$i,referencia);
					$qtde[$k]                    = pg_result($res,$i,qtde);
					$produto[$k]                 = pg_result($res,$i,subconjunto);
					$serie[$k]                   = pg_result($res,$i,serie);
					$posicao[$k]                 = pg_result($res,$i,posicao);
					$descricao[$k]               = pg_result($res,$i,descricao);
					$defeito[$k]                 = pg_result($res,$i,defeito);
					$pcausa_defeito[$k]          = pg_result($res,$i,causa_defeito);
					$causa_defeito_descricao[$k] = pg_result($res,$i,causa_defeito_descricao);
					$defeito_descricao[$k]       = pg_result($res,$i,defeito_descricao);
					$servico[$k]                 = pg_result($res,$i,servico_realizado);
					$servico_descricao[$k]       = pg_result($res,$i,servico_descricao);
					$admin_peca[$k]              = pg_result($res,$i,admin_peca);//takashi 03-01-06 HD 934
					$i++;
				}
			}else{
				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$os_item[$i]        = $_POST["os_item_"        . $i];
					$os_produto[$i]     = $_POST["os_produto_"     . $i];
					$produto[$i]        = $_POST["produto_"        . $i];
					$serie[$i]          = $_POST["serie_"          . $i];
					$posicao[$i]        = $_POST["posicao_"        . $i];
					$peca[$i]           = $_POST["peca_"           . $i];
					$qtde[$i]           = $_POST["qtde_"           . $i];
					$defeito[$i]        = $_POST["defeito_"        . $i];
					$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
					$servico[$i]        = $_POST["servico_"        . $i];
					$admin_peca[$i]     = $_POST["admin_peca_"     . $i]; //takashi 03-01-06 HD 934
					if (strlen($peca[$i]) > 0) {
						$sql = "SELECT  tbl_peca.referencia,
										tbl_peca.descricao
								FROM    tbl_peca
								WHERE   tbl_peca.fabrica    = $login_fabrica
								AND     tbl_peca.referencia = $peca[$i];";
						$resX = @pg_exec ($con,$sql) ;

						if (@pg_numrows($resX) > 0) {
							$descricao[$i] = trim(pg_result($resX,0,descricao));
						}
					}
				}
			}
		}else{
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$os_item[$i]        = $_POST["os_item_"        . $i];
				$os_produto[$i]     = $_POST["os_produto_"     . $i];
				$produto[$i]        = $_POST["produto_"        . $i];
				$serie[$i]          = $_POST["serie_"          . $i];
				$posicao[$i]        = $_POST["posicao_"        . $i];
				$peca[$i]           = $_POST["peca_"           . $i];
				$qtde[$i]           = $_POST["qtde_"           . $i];
				$defeito[$i]        = $_POST["defeito_"        . $i];
				$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
				$servico[$i]        = $_POST["servico_"        . $i];
				$admin_peca[$i]     = $_POST["admin_peca_"     . $i];//takashi 03-01-06 HD 934
				if (strlen($peca[$i]) > 0) {
					$sql = "SELECT  tbl_peca.referencia,
									tbl_peca.descricao
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica    = $login_fabrica
							AND     tbl_peca.referencia = '$peca[$i]';";
					$resX = @pg_exec ($con,$sql) ;
					if (@pg_numrows($resX) > 0) {
						$descricao[$i] = trim(pg_result($resX,0,descricao));
					}
				}
			}
		}
		if ($login_fabrica == 3) {
			
			echo "<table width='100%' border='0' cellspacing='3' cellpadding='0'>";
			echo "<tr height='10' bgcolor='#666666'>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#00FF00'><b>Apenas uma peça. OS com pedido aprovado</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#FFFF00'><b>Duas peças. OS e pedidos sujeitos a análise</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#FF0000'><b>Três ou mais peças. OS e pedidos sujeitos a auditoria</b></font></td>";
		}

		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
		echo "<tr height='20' bgcolor='#666666'>";

		if ($os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto</b></font></td>";
		}

		if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>N. Série</b></font></td>";
		}

		if ($login_fabrica == 14) echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";

		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>&nbsp; Código &nbsp;</b></font>";
                /*
                echo "<acronym title=\"Clique para abrir a lista básica do produto.\"><a class='lnk' href='peca_consulta_por_produto";
		if ($login_fabrica == 14) echo "_subconjunto";
		echo ".php?produto=$produto_os' target='_blank'>LISTA BÁSICA<img src='imagens/btn_lista.gif'></a></acronym>";*/
                echo "</td>";
             /*   echo "<td width='60' align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'>LISTA BÁSICA</FONT></TD>";*/
					echo "<td align='center'><acronym title=\"Clique para abrir a lista básica do produto.\"><a class='lnk' href='peca_consulta_por_produto";
		if ($login_fabrica == 14) echo "_subconjunto";
		echo ".php?produto=$produto_os' target='_blank'>LISTA BÁSICA</a></acronym></td>";
				
				
				
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";

		if ($pergunta_qtde_os_item == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";
		}

		if ($pedir_causa_defeito_os_item == 't' AND $login_fabrica<>20) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Causa</b></font></td>";
		}
		if($login_fabrica <> 20 ){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Defeito</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Serviço</b></font></td>";
		}

		echo "</tr>";

		$loop = $qtde_item;

		if ($login_fabrica==3){
			$sql = "SELECT  count(*) as contador
					FROM    tbl_os
					JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					JOIN tbl_os_item      ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					WHERE   tbl_os.os  = $os
					AND     tbl_os.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$num = pg_result($res,0,contador) - $numero_pecas_faturadas;
			$loop = $qtde_itens_mostrar - $numero_pecas_faturadas;
			if ($loop<$num)
				$loop = $num;
		}
		
#		if (strlen($faturado) > 0) $loop = $qtde_item - $faturado;

		$offset = 0;
		for ($i = 0 ; $i < $loop ; $i++) {
			
			$cor="";
			if ($login_fabrica==3){
				$cor=" bgcolor='#FF6666'";
				if ($i==0) {
					$cor=" bgcolor='#99FF99'";
					if ($numero_pecas_faturadas==1) $cor=" bgcolor='#FFFF99'";
				}
				if ($i==1){
					 $cor=" bgcolor='#FFFF99'";
					if ($numero_pecas_faturadas==1) $cor=" bgcolor='#FF6666'";
				}
				if ($numero_pecas_faturadas>=2) $cor=" bgcolor='#FF6666'";
			}

			echo "<tr$cor>";
			echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>\n";
			echo "<input type='hidden' name='os_item_$i'    value='$os_item[$i]'>\n";
			echo "<input type='hidden' name='descricao'>";
			echo "<input type='hidden' name='preco'>";
			echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//takashi 03-01-06 HD 934

			if ($os_item_subconjunto == 'f') {
				echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
			}else{
				echo "<td align='center' nowrap>";
				echo "<select class='frm' size='1' name='produto_$i'>";
				#echo "<option></option>";

				$sql = "SELECT  tbl_produto.produto   ,
								tbl_produto.referencia,
								tbl_produto.descricao
						FROM    tbl_subproduto
						JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
						WHERE   tbl_subproduto.produto_pai = $produto_os
						ORDER BY tbl_produto.referencia;";
				$resX = pg_exec ($con,$sql) ;

				echo "<option value='$produto_referencia' ";
				if ($produto[$i] == $produto_referencia) echo " selected ";
				echo " >$produto_descricao</option>";

				for ($x = 0 ; $x < pg_numrows ($resX) ; $x++ ) {
					$sub_produto    = trim (pg_result ($resX,$x,produto));
					$sub_referencia = trim (pg_result ($resX,$x,referencia));
					$sub_descricao  = trim (pg_result ($resX,$x,descricao));

					if ($login_fabrica == 14 AND substr ($sub_referencia,0,3) == "499" ){
						$sql = "SELECT  tbl_produto.produto   ,
										tbl_produto.referencia,
										tbl_produto.descricao
								FROM    tbl_subproduto
								JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
								WHERE   tbl_subproduto.produto_pai = $sub_produto
								ORDER BY tbl_produto.referencia;";
						$resY = pg_exec ($con,$sql) ;
						echo "<optgroup label='" . $sub_referencia . " - " . substr($sub_descricao,0,25) . "'>" ;
						for ($y = 0 ; $y < pg_numrows ($resY) ; $y++ ) {
							$sub_produto    = trim (pg_result ($resY,$y,produto));
							$sub_referencia = trim (pg_result ($resY,$y,referencia));
							$sub_descricao  = trim (pg_result ($resY,$y,descricao));

							echo "<option ";
							if (trim ($produto[$i]) == $sub_referencia) echo " selected ";
							echo " value='" . $sub_referencia . "'>" ;
							echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
							echo "</option>";
						}
						echo "</optgroup>";
					}else{
						echo "<option ";
						if (trim ($produto[$i]) == $sub_referencia) echo " selected ";
						echo " value='" . $sub_referencia . "'>" ;
						echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
						echo "</option>";
					}
				}

				echo "</select>";
				if ($login_fabrica == 14) {
					echo " <img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista_sub (document.frm_os.produto_$i.value, document.frm_os.posicao_$i, document.frm_os.peca_$i, document.frm_os.descricao_$i)' alt='Clique para abrir a lista básica do produto selecionado' style='cursor:pointer;'>";
				}
				echo "</td>\n";
			}

			if ($os_item_subconjunto == 'f') {
				$xproduto = $produto[$i];
				echo "<input type='hidden' name='serie_$i'>\n";
			}else{
				if ($os_item_serie == 't') {
					echo "<td align='center'><input class='frm' type='text' name='serie_$' size='9' value='$serie[$i]'></td>\n";
				}
			}

			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca      ,
								tbl_peca.referencia,
								tbl_peca.descricao ,
								tbl_lista_basica.qtde
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia
						LIMIT 1 OFFSET $offset;";
				$resX = @pg_exec ($con,$sql) ;

				if (@pg_numrows($resX) > 0) {
					$xpeca       = trim(pg_result($resX,0,peca));
					$xreferencia = trim(pg_result($resX,0,referencia));
					$xdescricao  = trim(pg_result($resX,0,descricao));
					$xqtde       = trim(pg_result($resX,0,qtde));

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

					if ($login_fabrica == 6) {
					    if (strlen ($defeito[$i]) == 0) $defeito[$i] = 78 ;
					    if (strlen ($servico[$i]) == 0) $servico[$i] = 1 ;
					}
				}else{
					echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]' id='peca_$i' onblur=\"javascript:limpa_campo(this,$i)\">&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"tudo\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
                         
//takashi chamado 300 12-07               
             	 	echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";         
                         
//takashi chamado 300 12-07                                   
					echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' id='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
					if ($pergunta_qtde_os_item == 't') {
						echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]' id='qtde_$i'></td>\n";
					}
				}
			}else{
				if ($login_fabrica == 14) {
					echo "<td align='center'><input class='frm' type='text' name='posicao_$i' size='5' maxlength='5' value='$posicao[$i]'></td>\n";
				}else{
					echo "<input type='hidden' name='posicao_$i'>\n";
				}

				echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]' id='peca_$i' onblur=\"javascript:limpa_campo(this,$i)\">&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"referencia\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")'";
				echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
                                
//takashi 11-07 chamado 300
                 /*
 echo "<img src='imagens/btn_lista.gif' border='0' align='absmiddle'                         onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'>";*/
                
                


                echo "</td>\n";
				
				if($login_fabrica ==6){
				echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica2(document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
				}else{
				
				
                echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
                //takashi 11-07 chamado 300      
				}
				echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"descricao\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )'";
				echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
				if ($pergunta_qtde_os_item == 't') {
					echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>\n";
				}
			}

			#------------------- Causa do Defeito no Item --------------------
			if ($pedir_causa_defeito_os_item == 't' and $login_fabrica<>20) {
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='pcausa_defeito_$i'>";
				echo "<option selected></option>";

				$sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica ORDER BY codigo, descricao";
				$res = pg_exec ($con,$sql) ;

				for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
					echo "<option ";
					if ($pcausa_defeito[$i] == pg_result ($res,$x,causa_defeito)) echo " selected ";
					echo " value='" . pg_result ($res,$x,causa_defeito) . "'>" ;
					echo pg_result ($res,$x,codigo) ;
					echo " - ";
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}

				echo "</select>";
				echo "</td>\n";
			}

			#------------------- Defeito no Item --------------------
			if($login_fabrica <> 20){
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='defeito_$i'>";
				echo "<option selected></option>";
	
				$sql = "SELECT *
						FROM   tbl_defeito
						WHERE  tbl_defeito.fabrica = $login_fabrica
						AND    tbl_defeito.ativo IS TRUE
						ORDER BY descricao";
				$res = pg_exec ($con,$sql) ;
	
				for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
					echo "<option ";
					if ($defeito[$i] == pg_result ($res,$x,defeito)) echo " selected ";
					echo " value='" . pg_result ($res,$x,defeito) . "'>" ;
	
					if (strlen (trim (pg_result ($res,$x,codigo_defeito))) > 0) {
						echo pg_result ($res,$x,codigo_defeito) ;
						echo " - " ;
					}
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}
	
				echo "</select>";
				echo "</td>\n";
	
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='servico_$i'>";
				echo "<option selected></option>";
	
				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
	
				if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
					$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
				}
	
				if ($login_fabrica == 1) {
					if ($login_reembolso_peca_estoque == 't') {
						$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
						if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
					}else{
						$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
						$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
						if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
					}
				}
				if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";
	
				$sql .= " AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
				$res = pg_exec ($con,$sql) ;
	//if ($ip == '201.0.9.216') echo $sql;
	$teste=$sql;
				if (pg_numrows($res) == 0) {
					$sql = "SELECT *
							FROM   tbl_servico_realizado
							WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
	
					if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					}
	
					if ($login_fabrica == 1) {
						if ($login_reembolso_peca_estoque == 't') {
							$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
						}else{
							$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
						}
					}
					if($login_fabrica==20) $sql .=" tbl_servico_realizado.solucao IS TRUE ";
	
					$sql .=	" AND tbl_servico_realizado.linha IS NULL
							AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
	// echo $sql;
					$teste2=$sql;
					$res = pg_exec ($con,$sql) ;
				}
	
				for ($x = 0 ; $x < pg_numrows($res) ; $x++ ) {
					echo "<option ";
					if ($servico[$i] == pg_result ($res,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
					echo pg_result ($res,$x,descricao) ;
					if (pg_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
					echo "</option>";
				}
	
				echo "</select>";
				echo "</td>\n";
			}	
			echo "</tr>\n";

			$offset = $offset + 1;
		}
// echo "$teste<BR>2: $teste2";
		echo "</table>";
		?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<?
//TAKASHI 26/10
if($compressor=='t' and $login_posto == '6359'){


	// por km é 0,40 centavos
	// por hora é 24 reais, 0,40 por minuto
	//COMPRESSOR TEM UM DIFERENCIAL
#TAKASHI LIBERAR PARA SILVANIA DA BLACK OS COMPRESSOR
#TAKASHI LIBERAR PARA SILVANIA DA BLACK OS COMPRESSOR	

echo "<table width='600' border='1' align='center'  cellpadding='1' cellspacing='3 class='border'>";
		echo "<tr>";
		echo "<td nowrap colspan='6' class='menu_top'><B><font size='2' face='Geneva, Arial, Helvetica, san-serif'>OUTRAS DESPESAS</font></b></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td nowrap class='menu_top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data</font></td>";
		echo "<td nowrap class='menu_top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora início</font></td>";
		echo "<td nowrap class='menu_top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora fim</font></td>";
		echo "<td nowrap class='menu_top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
		echo "<td nowrap class='menu_top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>";
		echo "<td nowrap class='menu_top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>";
		echo "</tr>";
		$sql  = "SELECT tbl_os_visita.os_visita ,
				to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data             ,
				to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente ,
				to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente   ,
				tbl_os_visita.km_chegada_cliente                                               ,
				tbl_os_visita.justificativa_valor_adicional                                    ,
				tbl_os_visita.valor_adicional
			FROM    tbl_os_visita
			WHERE   tbl_os_visita.os = $os
			ORDER BY tbl_os_visita.os_visita;";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		for ($y=0;$qtde_visita>$y;$y++){
			$os_visita            = trim(@pg_result($res,$y,os_visita));
			$visita_data          = trim(@pg_result($res,$y,data));
			$hr_inicio            = trim(@pg_result($res,$y,hora_chegada_cliente));
			$hr_fim               = trim(@pg_result($res,$y,hora_saida_cliente));
			$visita_km            = trim(@pg_result($res,$y,km_chegada_cliente));
			$justificativa_adicionais   = trim(@pg_result($res,$y,justificativa_valor_adicional));
			$valores_adicionais         = trim(@pg_result($res,$y,valor_adicional));
			echo "<tr>";
			echo "<td nowrap align='center' width='200'><INPUT TYPE='text' NAME='visita_data_$y' value='$visita_data' size='10' maxlength='10' class='frm'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>dd/mm/aaaa</font></td>";
			echo "<td nowrap align='center'><INPUT TYPE='text' NAME='visita_hr_inicio_$y' value='$hr_inicio' size='5' maxlength='5' class='frm'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>09:23</font></td>";
			echo "<td nowrap align='center'><INPUT TYPE='text' NAME='visita_hr_fim_$y' value='$hr_fim' size='5' maxlength='5' class='frm'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>14:51</font></td>";
			echo "<td nowrap align='center'><INPUT TYPE='text' NAME='visita_km_$y' value='$visita_km' size='4' maxlength='4' class='frm'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Km</font></td>";
			echo "<td nowrap align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>R$ </font><INPUT TYPE='text' NAME='valores_adicionais_$y' value='$valores_adicionais' size='5' maxlength='5' class='frm'></td>";
			echo "<td nowrap align='center'><INPUT TYPE='text' NAME='justificativa_adicionais_$y' value='$justificativa_adicionais' size='10' maxlength='50' class='frm'></td>";
			echo "<input type='hidden' name='os_visita_$y' value='$os_visita'>";	
			echo "</tr>";
		}
	echo "</table>";

}
//TAKASHI 26/10
?>

<table width='650' align='center' border='0' cellspacing='0' cellpadding='5'>
<? if ($login_fabrica == 19) { ?>
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Valores Adicionais:</FONT> 
		<br>
		<FONT SIZE="1">R$ </FONT> 
		<INPUT TYPE="text" NAME="valores_adicionais" value="<? echo $valores_adicionais ?>" size="10" maxlength="10" class="frm">
		<br><br>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Justificativa dos Valores Adicionais:</FONT>
		<br>
		<INPUT TYPE="text" NAME="justificativa_adicionais" value="<? echo $justificativa_adicionais ?>" size="30" maxlength="100" class="frm">
		<br><br>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Quilometragem:</FONT>
		<br>
		<INPUT TYPE="text" NAME="qtde_km" value="<? echo $qtde_km ?>" size="5" maxlength="10" class="frm">
		<br><br>
	</td>
</tr>
<? } ?>


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
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;">
	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php";?>
