<?
 
#if ($login_fabrica == 19 and $login_posto == 14068 and strlen ($_POST['btn_acao']) > 0) echo "aqui <br>";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

/* LIBERADO POR WELLINGTON 20/12/2006 - 14:05:00 */
$os_offline = $_POST['os_offline'];
if ($login_fabrica==11 and $os_offline <> '1'){
	include 'os_cadastro_tudo.php';
	exit;
}
//liberado takashi 06-02-07 10:11 HD 1141
if ($login_fabrica==3 and $os_offline <> '1'){
	include 'os_cadastro_tudo.php';
	exit;
}
//liberado takashi 27-11 15:43 para fabricio testar integridade
/*if(($login_posto=='6359') and ($login_fabrica==3)){
include 'os_cadastro_tudo.php';
exit;
}*/
//postos de teste para a latina, enviado pelo Rafael - Latinatec. Liberado dia 14/12/2006
//  Posto Teste - Geni Peres - MHM Abrantes
if( ( ($login_posto == '11668') OR ($login_posto=='2405') ) AND ($login_fabrica==15) ){
include 'os_cadastro_tudo.php';
exit;
}

##liberado segunda06-11-2006 09:13 para tectoy conforme email enviado por angelica, andre ricardo e leandro - Takashi
if($login_fabrica==6){
include 'os_cadastro_tudo.php';
exit;
}
//HD 11419
if($login_fabrica ==1) {
	$sql = "SELECT os FROM tbl_os_troca WHERE os=$os";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		header ("Location: os_cadastro_troca.php?os=$os");
		exit;
	}
}
if((($login_posto=='2252') or ($login_posto=='11722'))and ($login_fabrica==24)){
include 'os_cadastro_tudo.php';
exit;
}

if($login_fabrica==24){
include 'os_cadastro_tudo.php';
exit;
}
//liberado Takashi 03-01 09:21 segundo HD 302
if(($login_fabrica==3) and ($login_posto=='5037' OR $login_posto=='595')){
include 'os_cadastro_tudo.php';
exit;
}
//takashi 28-02 15:00 liberado conforme contato da fabiola HD 1367

/*
if($login_fabrica==5 and $login_posto=='6359'){
include 'os_cadastro_tudo.php';
exit;
}
*/

//if ($login_fabrica == 1) {
//	echo "<H2>Sistema em manutenção. Estará disponível em alguns instantes.</H2>";
//	exit;
//}

if ($login_fabrica == 1 AND ($login_tipo_posto == 90 OR $login_tipo_posto == 36 OR $login_tipo_posto == 82 OR $login_tipo_posto == 83 OR $login_tipo_posto == 84) ) {
	header ("Location: os_cadastro_locacao.php");
	exit;
}

if ($login_fabrica == 14) {
	header ("Location: os_cadastro_intelbras.php");
	exit;
}

include 'funcoes.php';

#-------- Libera digitação de OS pelo distribuidor --------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
	$sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	$distribuidor_digita = pg_result ($res,0,0);
	if (strlen ($posto) == 0) $posto = $login_posto;
}
#----------------------------------------------------------------

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_result ($res,0,pedir_defeito_reclamado_descricao);

/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "continuar") {
	$os = $_POST['os'];


	$sua_os_offline = $_POST['sua_os_offline'];
	if (strlen (trim ($sua_os_offline)) == 0) {
		$sua_os_offline = 'null';
	}else{
		$sua_os_offline = "'" . trim ($sua_os_offline) . "'";
	}




	$sua_os = $_POST['sua_os'];
	if (strlen(trim($sua_os))==0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't' and $login_posto <> 6359) {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	}else{
		//WELLINGTON 04/01/2007
		 if ($login_fabrica <> 1 and $login_fabrica <> 11) {
		//if ($login_fabrica <> 1) {
			if ($login_fabrica <> 3 and $login_fabrica <> 5 and strlen($sua_os) < 7) {
				$sua_os = "000000" . trim ($sua_os);
				$sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
			}

			# inserido pelo Ricardo - 04/07/2006
			if ($login_fabrica == 3 or $login_fabrica == 5) {
				if (is_numeric($sua_os)) {
					// retira os ZEROS a esquerda
					$sua_os = intval(trim($sua_os));
				}
			}

#			if (strlen($sua_os) > 6) {
#				$sua_os = substr ($sua_os, strlen ($sua_os) - 6 , 6) ;
#			}
#  CUIDADO para OS de Revenda que já vem com = "-" e a sequencia.
#  fazer rotina para contar 6 caracteres antes do "-"
		}
		$sua_os = "'" . $sua_os . "'" ;
	}

	##### INÍCIO DA VALIDAÇÃO DOS CAMPOS #####
	
	$locacao = trim($_POST["locacao"]);
	if (strlen($locacao) > 0) {
		$x_locacao = "7";
	}else{
		$x_locacao = "null";
	}

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen (trim ($tipo_atendimento)) == 0) $tipo_atendimento = 'null';



	$produto_referencia = strtoupper(trim($_POST['produto_referencia']));



	//BOSCH - REGRAS DE VALICAÇÃO
	//regra: caso ele escolho um dois tipos de atendimento abaixo o produto vai ser  sempre os designados
	if($login_fabrica ==20){
		if($tipo_atendimento==11){    //garantia de peças
			$produto_referencia='0000002';
			$xproduto_serie = '999';
		}
		if($tipo_atendimento==12){    //garantia de acessórios
			$produto_referencia='0000001';

			$xproduto_serie = '999';
		}
	}
	//BOSCH - REGRAS DE VALICAÇÃO



	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);

	if (strlen($produto_referencia) == 0) {
		$produto_referencia = 'null';
		$msg_erro .= " Digite o produto.";
	}else{
		$produto_referencia = "'".$produto_referencia."'" ;
	}

	$xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));
	if ($xdata_abertura == 'null') $msg_erro .= " Digite a data de abertura da OS.";
	$cdata_abertura = str_replace("'","",$xdata_abertura);




	##############################################################
	# AVISO PARA POSTOS DA BLACK & DECKER
	# Verifica se data de abertura da OS é inferior a 01/09/2005
	//if($login_posto == 13853 OR $login_posto == 13854 OR $login_posto == 13855 OR $login_posto == 11847 OR $login_posto == 13856 OR $login_posto ==  1828 OR $login_posto ==  1292 OR $login_posto ==  1472 OR $login_posto ==  1396 OR $login_posto == 13857 OR $login_posto ==  1488 OR $login_posto == 13858 OR $login_posto == 13750 OR $login_posto == 13859 OR $login_posto == 13860 OR $login_posto == 13861 OR $login_posto == 13862 OR $login_posto == 13863 OR $login_posto == 13864 OR $login_posto == 13865 OR $login_posto == 5260 OR $login_posto == 2472 OR $login_posto == 5258 OR $login_posto == 5352){
	##############################################################

	if ($login_fabrica == 1) {
		$sdata_abertura = str_replace("-","",$cdata_abertura);

		// liberados pela Fabiola em 05/01/2006
		if($login_posto == 5089){ // liberados pela Fabiola em 20/03/2006
			if ($sdata_abertura < 20050101) 
				$msg_erro = "Erro. Data de abertura inferior a 01/01/2005.<br>Lançamento restrito às OSs com data de lançamento superior a 01/01/2005.";
		}elseif($login_posto == 5059 OR $login_posto == 5212){
			if ($sdata_abertura < 20050502) 
				$msg_erro = "Erro. Data de abertura inferior a 02/05/2005.<br>Lançamento restrito às OSs com data de lançamento superior a 01/05/2005.";
		}else{
			if ($sdata_abertura < 20050901)
				$msg_erro = "Erro. Data de abertura inferior a 01/09/2005.<br>OS deve ser lançada no sistema antigo até 30/09.";
		}
	}
	##############################################################


	if (strlen(trim($_POST['consumidor_nome'])) == 0) $xconsumidor_nome = 'null';
	else $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";

	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	$consumidor_cpf = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf = str_replace(" ","",$consumidor_cpf);
	$consumidor_cpf = trim(substr($consumidor_cpf,0,14));

	if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
	else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";

	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
	else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
	else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";

	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace(" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);
/*takashi HD 931  21-12*/
	if (strlen ($_POST['revenda_cnpj']) == 0 and $login_fabrica==3) $msg_erro .= " Digite o CNPJ da Revenda.<BR>";
	if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) $msg_erro .= " Tamanho do CNPJ da revenda inválido.";

	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

	if (strlen(trim($_POST['revenda_nome'])) == 0) $xrevenda_nome = 'null';
	else $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";

	if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
	else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";

	if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
	else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

//if ($ip == '201.42.112.110') echo "AQUI-->"; echo $xnota_fiscal; exit;

	if (($login_fabrica == 14) or ($login_fabrica == 6)){
		if ($xnota_fiscal == 'null' ) {
			$msg_erro = "Digite o número da nota fiscal.";
		}
	}

	$qtde_produtos = trim ($_POST['qtde_produtos']);
	if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";

	if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";
//pedido por Leandro Tectoy, feito por takashi 02/08 Alterado por Raphael para a bosch tb
	if($login_fabrica==6 OR $login_fabrica==20){
		if (strlen ($_POST['data_nf']) == 0) $msg_erro .= " Digite a data de compra.";
	}
//pedido por Leandro tectoy, feito por takashi 04/08
	$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";


	
	if (strlen(trim($_POST['produto_serie'])) == 0) $xproduto_serie = 'null';
	else         $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";

	if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
	else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

	if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
	else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";

	if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
	else             $xacessorios = "'".trim($_POST['acessorios'])."'";

	if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0) $xdefeito_reclamado_descricao = 'null';
	else             $xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";

	if (strlen(trim($_POST['obs'])) == 0) $xobs = 'null';
	else             $xobs = "'".trim($_POST['obs'])."'";

	if (strlen(trim($_POST['quem_abriu_chamado'])) == 0) $xquem_abriu_chamado = 'null';
	else             $xquem_abriu_chamado = "'".trim($_POST['quem_abriu_chamado'])."'";

	if (strlen($_POST['consumidor_revenda']) == 0) $msg_erro .= " Selecione consumidor ou revenda.";
	else                                $xconsumidor_revenda = "'".$_POST['consumidor_revenda']."'";

	//if (strlen($_POST['type']) == 0) $xtype = 'null';
	//else             $xtype = "'".$_POST['type']."'";

	if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
	else             $xsatisfacao = "'".$_POST['satisfacao']."'";

	if (strlen ($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
	else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";


	if ($login_fabrica == 14 ){
		if (strlen($produto_referencia) > 0 AND (strlen($xproduto_serie) == 0 OR $xproduto_serie == 'null')) {
			$sql = "SELECT  tbl_produto.numero_serie_obrigatorio
					FROM    tbl_produto
					JOIN    tbl_linha on tbl_linha.linha = tbl_produto.linha
					WHERE   upper(tbl_produto.referencia) = upper($produto_referencia)
					AND     tbl_linha.fabrica = $login_fabrica";
					//echo $sql;
			$res = @pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$numero_serie_obrigatorio = trim(pg_result($res,0,numero_serie_obrigatorio));

				if ($numero_serie_obrigatorio == 't') {
					$msg_erro .= "<br>Nº de Série $produto_referencia é obrigatório.";
				}
			}
		}
	}

//BOSCH - REGRAS DE VALICAÇÃO - RAPHAEL GIOVANINI
	if($login_fabrica==20 AND ($tipo_atendimento==14)){
		$aux_data      = explode('/',$data_nf);

		$xsoma = 100;
		$xdia = $aux_data[0];
		$xmes = $aux_data[1];
		$xano = $aux_data[2];

		$bosch_nf_data = date("Ymd", mktime(0,0,0,$xmes,$xdia+$xsoma,$xano));
		$bosch_data_abertura=explode('/',$data_abertura );
		$bosch_data_abertura=$bosch_data_abertura[2].$bosch_data_abertura[1].$bosch_data_abertura[0];

		if($bosch_data_abertura >$bosch_nf_data)
			$msg_erro =  "Prazo de Garantia de Conserto expirado" ;
	}
	if($login_fabrica==20 AND ($tipo_atendimento==15 )){

		$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.referencia = $produto_referencia";

		$res = @pg_exec ($con,$sql);
		if (@pg_numrows($res) == 0) {
			$msg_erro = " Produto $produto_referencia sem garantia";
		}

		if (strlen($msg_erro) == 0) {
			$garantia = trim(@pg_result($res,0,garantia));

			$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date;";

			$res = @pg_exec ($con,$sql);
			$data_final_garantia = trim(pg_result($res,0,0));

//echo "$data_final_garantia > $cdata_abertura";

			if($data_final_garantia >$cdata_abertura){
				$msg_erro="O produto ainda está na garantia, e não pode ser do tipo CORTESIA";
			}
		}
	}
//BOSCH - REGRAS DE VALICAÇÃO


	##### FIM DA VALIDAÇÃO DOS CAMPOS #####


#if ($login_fabrica == 19 and $login_posto == 14068) echo "aqui ";
#echo "<br>";
#flush;



	$os_reincidente = "'f'";

	##### Verificação se o nº de série é reincidente para a Tectoy #####
	if ($login_fabrica == 6) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = @pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);

		$sqlX = "SELECT to_char (current_date + INTERVAL '1 day', 'YYYY-MM-DD')";
		$resX = @pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao,
							tbl_os_extra.extrato
					FROM    tbl_os
					JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					WHERE   UPPER(tbl_os.serie)   = UPPER('$produto_serie')
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os.posto   = $posto
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = @pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$xxxos      = trim(pg_result($res,0,os));
				$xxxsua_os  = trim(pg_result($res,0,sua_os));
				$xxxextrato = trim(pg_result($res,0,extrato));

				if (strlen($xxxextrato) == 0) {
					$msg_erro .= "Nº de Série $produto_serie digitado é reincidente.<br>
					Favor reabrir a ordem de serviço $xxxsua_os e acrescentar itens.";
				}else{
					$os_reincidente = "'t'";
				}
			}
		}
	}

	##### Verificação se o nº de série é reincidente para a Britânia #####
	if ($login_fabrica == 3 and 1 == 2) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = @pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);

		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = @pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = @pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$msg_erro .= "Nº de Série $produto_serie digitado é reincidente. Favor verificar.<br>Em caso de dúvida, entre em contato com a Fábrica.";
			}
		}
	}

/* VER PARA LIBERAR */
	
	if ($login_fabrica == 3 AND 1 == 2) {
		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = @pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$os_reincidente = "'t'";
			}
		}
	}
/* VER PARA LIBERAR */




/*TAKASHI 18-12 HD-854*/
	if ($login_fabrica == 3 and $login_posto==6359 and 1==2) {
		$sqlX = "SELECT to_char ($xdata_abertura::date - INTERVAL '90 days', 'YYYY-MM-DD')";
		$resX = @pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);
//echo $sqlX;
		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = @pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao, 
							tbl_os.finalizada,
							tbl_os.data_fechamento
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_produto.linha=3
					ORDER BY tbl_os.data_abertura DESC
					LIMIT 1";
			$res = @pg_exec($con,$sql);
//if ($ip=="201.42.46.223"){ echo "$sql"; }
					//AND     tbl_os.data_fechamento::date BETWEEN '$data_inicial' AND '$data_final'
//linha 3, pois é a linha audio e video
			if (pg_numrows($res) > 0) {
				$xxxos      = trim(pg_result($res,0,os));
				$xxfinalizada   = trim(pg_result($res,0,finalizada));
				$xx_sua_os   = trim(pg_result($res,0,sua_os));
				$xxdata_fechamento =   trim(pg_result($res,0,data_fechamento));
		
				if(strlen($xxfinalizada)==0){ //aberta 
					$os_reincidente = "'t'";
					$msg_erro .= "OS $xx_sua_os com este número de série ainda está aberta, por favor consulta-la.";
				}else{//fechada
					if($data_inicio<$xxdata_fechamento and $data_final>=$xxdata_fechamento){
					//if(($xxdata_fechamento > $data_inicial) and ($xxdata_fechamento < $data_final)){
						$os_reincidente = "'t'";
					}
				}
			}
		}
	}
/*TAKASHI 18-12 HD-854*/




	if ($login_fabrica == 7) $xdata_nf = $xdata_abertura;

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

	$produto = 0;

	if (strlen($_POST['produto_voltagem']) == 0)	$voltagem = "null";
	else											$voltagem = "'". $_POST['produto_voltagem'] ."'";

	$sql = "SELECT tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) ";
	if ($login_fabrica == 1) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
		//if($login_posto<>6359){ $sql .= " AND tbl_produto.familia <> 347";}
	}
	$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		$msg_erro = " Produto $produto_referencia não cadastrado.";
	}else{
		$produto = @pg_result ($res,0,produto);
		$linha   = @pg_result ($res,0,linha);
	}

	if ($xtroca_faturada <> "'t'") { // verifica troca faturada para a Black

		if (1 == 2) {
			if (strlen($msg_erro) == 0) {

				$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
				$res = @pg_exec ($con,$sql);

				if (@pg_numrows($res) == 0) {
					$msg_erro = " Produto $produto_referencia sem garantia";
				}

				if (strlen($msg_erro) == 0) {
					$garantia = trim(@pg_result($res,0,garantia));

					$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date;";
					$res = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage($con)) > 0) {
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) > 0) $msg_erro =  "Data da NF inválida.";

					if (strlen($msg_erro) == 0) {
						if (pg_numrows ($res) > 0) {
							$data_final_garantia = trim(pg_result($res,0,0));
						}

						if ($data_final_garantia < $cdata_abertura) {
							if($login_fabrica==20 AND ($tipo_atendimento==15 OR $tipo_atendimento==16)){
							}else{
							//$msg_erro = " Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
							}
						}
					}
				}
			}
		}

	}
//compressor
	if ($login_fabrica == 1 and $login_posto == 6359) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.linha   = 198
				AND   tbl_produto.produto = $produto;";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			$xtipo_os_cortesia = "'Compressor'";
		}else{
			$xtipo_os_cortesia = 'null';
		}
	}else{
		$xtipo_os_cortesia = 'null';
	}



	#----------- OS digitada pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";
	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_exec($con,$sql);
			if (pg_numrows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_result ($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_exec($con,$sql);
					if (pg_numrows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					}else{
						$posto = pg_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	#------------------------------------------------------





	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$os_offline = $_POST['os_offline'];
	if (strlen ($os_offline) == 0) $os_offline = "null";

	if (strlen($msg_erro) == 0){
		/*================ INSERE NOVA OS =========================*/

		if (strlen($os) == 0) {
			$sql =	"INSERT INTO tbl_os (
						tipo_atendimento                                               ,
						posto                                                          ,
						fabrica                                                        ,
						sua_os                                                         ,
						sua_os_offline                                                 ,
						data_abertura                                                  ,
						cliente                                                        ,
						revenda                                                        ,
						consumidor_nome                                                ,
						consumidor_cpf                                                 ,
						consumidor_fone                                                ,
						revenda_cnpj                                                   ,
						revenda_nome                                                   ,
						revenda_fone                                                   ,
						nota_fiscal                                                    ,
						data_nf                                                        ,
						produto                                                        ,
						serie                                                          ,
						qtde_produtos                                                  ,
						codigo_fabricacao                                              ,
						aparencia_produto                                              ,
						acessorios                                                     ,
						defeito_reclamado_descricao                                    ,
						obs                                                            ,
						quem_abriu_chamado                                             ,
						consumidor_revenda                                             ,
						satisfacao                                                     ,
						laudo_tecnico                                                  ,
						tipo_os_cortesia                                               ,
						troca_faturada                                                 ,
						os_offline                                                     ,
						os_reincidente                                                 ,
						digitacao_distribuidor                                         ,
						tipo_os
					) VALUES (
						$tipo_atendimento                                              ,
						$posto                                                         ,
						$login_fabrica                                                 ,
						$sua_os                                                        ,
						$sua_os_offline                                                ,
						$xdata_abertura                                                ,
						null                                                           ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
						$xconsumidor_nome                                              ,
						$xconsumidor_cpf                                               ,
						$xconsumidor_fone                                              ,
						$xrevenda_cnpj                                                 ,
						$xrevenda_nome                                                 ,
						$xrevenda_fone                                                 ,
						$xnota_fiscal                                                  ,
						$xdata_nf                                                      ,
						$produto                                                       ,
						$xproduto_serie                                                ,
						$qtde_produtos                                                 ,
						$xcodigo_fabricacao                                            ,
						$xaparencia_produto                                            ,
						$xacessorios                                                   ,
						$xdefeito_reclamado_descricao                                  ,
						$xobs                                                          ,
						$xquem_abriu_chamado                                           ,
						$xconsumidor_revenda                                           ,
						$xsatisfacao                                                   ,
						$xlaudo_tecnico                                                ,
						$xtipo_os_cortesia                                             ,
						$xtroca_faturada                                               ,
						$os_offline                                                    ,
						$os_reincidente                                                ,
						$digitacao_distribuidor                                        ,
						$x_locacao
					);";
		}else{
			$sql =	"UPDATE tbl_os SET
						tipo_atendimento            = $tipo_atendimento                 ,
						data_abertura               = $xdata_abertura                   ,
						revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)  ,
						consumidor_nome             = $xconsumidor_nome                 ,
						consumidor_cpf              = $xconsumidor_cpf                  ,
						consumidor_fone             = $xconsumidor_fone                 ,
						revenda_cnpj                = $xrevenda_cnpj                    ,
						revenda_nome                = $xrevenda_nome                    ,
						revenda_fone                = $xrevenda_fone                    ,
						nota_fiscal                 = $xnota_fiscal                     ,
						data_nf                     = $xdata_nf                         ,
						serie                       = $xproduto_serie                   ,
						qtde_produtos               = $qtde_produtos                    ,
						codigo_fabricacao           = $xcodigo_fabricacao               ,
						aparencia_produto           = $xaparencia_produto               ,
						defeito_reclamado_descricao = $xdefeito_reclamado_descricao     ,
						consumidor_revenda          = $xconsumidor_revenda              ,
						satisfacao                  = $xsatisfacao                      ,
						laudo_tecnico               = $xlaudo_tecnico                   ,
						troca_faturada              = $xtroca_faturada                  ,
						tipo_os_cortesia            = $xtipo_os_cortesia                ,
						tipo_os                     = $x_locacao
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}
//if ($ip == '201.43.247.108') echo $sql."<br><br>";
		$sql_OS = $sql;
		$res = @pg_exec ($con,$sql);
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}


		if ($login_fabrica == 1 && strlen($os) > 0 && strlen($msg_erro) == 0) {
			$sqlOs = "SELECT sua_os FROM tbl_os WHERE fabrica=$login_fabrica AND os=$os";
			$resOS = pg_query($con, $sqlOs);
			$sua_os = pg_fetch_result($resOS, 0, sua_os);
			$suaos = explode("-", $sua_os);

			$sql="SELECT tbl_os.sua_os,
						 tbl_posto_fabrica.codigo_posto
				 FROM tbl_os
				 JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica=$login_fabrica
				WHERE tbl_os.nota_fiscal::float = (
								SELECT nota_fiscal::float
								  FROM tbl_os
								 WHERE os = $os
								   AND posto = $login_posto
								   AND fabrica = $login_fabrica
								)
					AND revenda = (
								SELECT revenda
								  FROM tbl_os
								 WHERE os = $os
								   AND posto = $login_posto
								   AND fabrica = $login_fabrica
								)
					AND tbl_os.os <> $os
					AND tbl_os.sua_os NOT LIKE '".$suaos[0]."%'
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_posto_fabrica.posto = $login_posto
					AND tbl_os.fabrica = $login_fabrica";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				$sua_os       = pg_fetch_result($res,0,sua_os);
				$codigo_posto = pg_fetch_result($res,0,codigo_posto);
				$msg_erro = "Nota fiscal já foi informada na OS $codigo_posto$sua_os. O sistema permite a digitação de apenas uma OS de revenda para cada nota fiscal, pois é possível incluir na mesma OS a quantidade total de produtos que serão atendidos em garantia.";
			}
		}


		if (strlen ($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_result ($res,0,0);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		if (strlen($os) == 0) {
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os')");
			$os  = pg_result ($res,0,0);
		}

		$res      = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
if ($ip == '201.43.247.108') echo $msg_erro;
		}
		#--------- grava OS_EXTRA ------------------
		if (strlen ($msg_erro) == 0) {
			$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
			$visita_por_km				= trim ($_POST['visita_por_km']);
			$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));
			$regulagem_peso_padrao		= str_replace (",",".",trim ($_POST['regulagem_peso_padrao']));
			$certificado_conformidade	= str_replace (",",".",trim ($_POST['certificado_conformidade']));
			$valor_diaria				= str_replace (",",".",trim ($_POST['valor_diaria']));

			if (strlen ($taxa_visita)				== 0) $taxa_visita					= '0';
			if (strlen ($visita_por_km)				== 0) $visita_por_km				= 'f';
			if (strlen ($hora_tecnica)				== 0) $hora_tecnica					= '0';
			if (strlen ($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
			if (strlen ($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
			if (strlen ($valor_diaria)				== 0) $valor_diaria					= '0';

			$sql = "UPDATE tbl_os_extra SET
						taxa_visita              = $taxa_visita             ,
						visita_por_km            = '$visita_por_km'         ,
						hora_tecnica             = $hora_tecnica            ,
						regulagem_peso_padrao    = $regulagem_peso_padrao   ,
						certificado_conformidade = $certificado_conformidade,
						valor_diaria             = $valor_diaria ";

			if ($os_reincidente == "'t'") $sql .= ", os_reincidente = $xxxos ";

			$sql .= "WHERE tbl_os_extra.os = $os";
#if ( $ip == '201.0.9.216' OR $login_posto == 14068) echo nl2br($sql)."<br><br>";
#if ( $ip == '201.0.9.216' OR $login_posto == 14068) flush();
			$res = @pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0 ) {
				$msg_erro = pg_errormessage($con);
			}

			if (strlen ($msg_erro) == 0) {
				$res = @pg_exec ($con,"COMMIT TRANSACTION");

//BOSCH - ENVIAR EMAIL
				if($login_fabrica == 20){
					$sql = "SELECT nome,codigo_posto,email FROM tbl_os JOIN tbl_posto_fabrica USING(posto) JOIN tbl_posto USING(POSTO) WHERE os = $os and tipo_atendimento IN (15,16)";
					//echo $sql;
					$res = pg_exec($con,$sql);

					if (pg_numrows($res) > 0) {
						$nome           = trim(pg_result($res,$i,nome));
						$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
						$email   = trim(pg_result($res,$i,email));
					//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

						$email_origem  = "helpdesk@telecontrol.com.br";
						$email_destino = "pt.garantia@br.bosch.com";
						$assunto       = "Novo OS de Cortesia";
						$corpo.="<br>Foi inserido uma nova OS n°$os no sistema TELECONTROL ASSIST.\n\n";
						$corpo.="<br>Chamado n°: $hd_chamado\n\n";
						$corpo.="<br>Codigo do Posto: $codigo_posto<br>Posto: $nome <br>Email: $email\n\n";
						$corpo.="<br><br>Telecontrol\n";
						$corpo.="<br>www.telecontrol.com.br\n";
						$corpo.="<br>_______________________________________________\n";
						$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";
					//$corpo = $body_top.$corpo;

						if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
							header ("Location: os_item.php?os=$os");
							exit;
						}
					}
				}


				// se o produto tiver TROCA OBRIGATORIA, bloqueia a OS para intervencao da fabrica // fabio 17/01/2007
				if ($login_fabrica == 3 AND 1==1){
					$sql = "SELECT  troca_obrigatoria
							FROM    tbl_produto
							WHERE   upper(tbl_produto.referencia) = upper($produto_referencia)";
					$res = @pg_exec($con,$sql);
					if (pg_numrows($res) > 0) {
						$troca_obrigatoria = trim(pg_result($res,0,troca_obrigatoria));
						if ($troca_obrigatoria == 't') {
							$sql_intervencao = "SELECT * FROM  tbl_os_status WHERE os=$os AND status_os=62";
							$res_intervencao = pg_exec($con, $sql_intervencao);
							if (pg_numrows ($res_intervencao) == 0){
								$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O Produto desta O.S. necessita de troca.')";
								$res = @pg_exec ($con,$sql);
								$msg_intervencao .= "<br>A produto $produto_referencia precisa de Intervenção da Assistência Técnica da Fábrica. Aguarde o contato da fábrica";
							}
							// envia email teste para avisar
							$email_origem  = "fabio@telecontrol.com.br";
							$email_destino = "fabio@telecontrol.com.br";
							$assunto       = "TROCA OBRIGATORIA - OS cadastrada";
							$corpo.="<br>OS: $os \n";
							$body_top = "--Message-Boundary\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 7BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";
							@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem);
							// fim
						}
					}
				}// fim TROCA OBRIGATORIA



				if($login_fabrica == 20 and $reabrir='ok'){
					header ("Location: os_cadastro_adicional.php?os=$os&reabrir=ok");exit;
				}
				header ("Location: os_item.php?os=$os");
				exit;
			}else{
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}

	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

		if (strpos ($msg_erro,"tbl_os_unico") > 0)
			$msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";

		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

/*================ LE OS DA BASE DE DADOS =========================*/
if (strlen($_GET['os'] ) > 0) $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen ($os) > 0) {
	$sql =	"SELECT tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.revenda_nome                                              ,
					tbl_os.nota_fiscal                                               ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.codigo_fabricacao                                         ,
					tbl_os.type                                                      ,
					tbl_os.satisfacao                                                ,
					tbl_os.laudo_tecnico                                             ,
					tbl_os.tipo_os_cortesia                                          ,
					tbl_os.serie                                                     ,
					tbl_os.qtde_produtos                                             ,
					tbl_os.troca_faturada                                            ,
					tbl_os.tipo_os                                                   ,
					tbl_os.tipo_atendimento                                          ,
					tbl_produto.produto                                              ,
					tbl_produto.referencia                     AS produto_referencia ,
					tbl_produto.descricao                      AS produto_descricao  ,
					tbl_produto.voltagem                       AS produto_voltagem   ,
					tbl_posto_fabrica.codigo_posto
			FROM tbl_os
			JOIN      tbl_produto  ON tbl_produto.produto       = tbl_os.produto
			JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $posto
			LEFT JOIN tbl_os_extra ON tbl_os.os                 = tbl_os_extra.os
			WHERE tbl_os.os = $os
			AND   tbl_os.posto = $posto
			AND   tbl_os.fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os			= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cpf 	= pg_result ($res,0,consumidor_cpf);
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf		= pg_result ($res,0,data_nf);
		$consumidor_revenda	= pg_result ($res,0,consumidor_revenda);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
		$codigo_fabricacao	= pg_result ($res,0,codigo_fabricacao);
		$type			= pg_result ($res,0,type);
		$satisfacao		= pg_result ($res,0,satisfacao);
		$laudo_tecnico		= pg_result ($res,0,laudo_tecnico);
		$tipo_os_cortesia	= pg_result ($res,0,tipo_os_cortesia);
		$produto_serie		= pg_result ($res,0,serie);
		$qtde_produtos		= pg_result ($res,0,qtde_produtos);
		$produto                = pg_result ($res,0,produto);
		$produto_referencia	= pg_result ($res,0,produto_referencia);
		$produto_descricao	= pg_result ($res,0,produto_descricao);
		$produto_voltagem	= pg_result ($res,0,produto_voltagem);
		$troca_faturada		= pg_result ($res,0,troca_faturada);
		$codigo_posto		= pg_result ($res,0,codigo_posto);
		$tipo_os		= pg_result ($res,0,tipo_os);
		$tipo_atendimento	= pg_result ($res,0,tipo_atendimento);

		//--=== Tradução para outras linguas ============================= Raphael HD:1212
		
		$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
		
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
		
		//--=== Tradução para outras linguas ================================================

	}
}


/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {
	$os				= $_POST['os'];
	$sua_os				= $_POST['sua_os'];
	$data_abertura			= $_POST['data_abertura'];
	$consumidor_nome		= $_POST['consumidor_nome'];
	$consumidor_cpf 		= $_POST['consumidor_cpf'];
	$consumidor_cidade		= $_POST['consumidor_cidade'];
	$consumidor_fone		= $_POST['consumidor_fone'];
	$consumidor_estado		= $_POST['consumidor_estado'];
	$revenda_cnpj			= $_POST['revenda_cnpj'];
	$revenda_nome			= $_POST['revenda_nome'];
	$nota_fiscal			= $_POST['nota_fiscal'];
	$data_nf			= $_POST['data_nf'];
	$produto_referencia		= $_POST['produto_referencia'];
	$produto_descricao		= $_POST['produto_descricao'];
	$produto_voltagem		= $_POST['produto_voltagem'];
	$produto_serie			= trim($_POST['produto_serie']);
	$qtde_produtos			= $_POST['qtde_produtos'];
	$cor				= $_POST['cor'];
	$consumidor_revenda		= $_POST['consumidor_revenda'];

	$type				= $_POST['type'];
	$satisfacao			= $_POST['satisfacao'];
	$laudo_tecnico			= $_POST['laudo_tecnico'];

	$obs				= $_POST['obs'];
//	$chamado			= $_POST['chamado'];
	$quem_abriu_chamado 		= $_POST['quem_abriu_chamado'];
	$taxa_visita			= $_POST['taxa_visita'];
	$visita_por_km			= $_POST['visita_por_km'];
	$hora_tecnica			= $_POST['hora_tecnica'];
	$regulagem_peso_padrao		= $_POST['regulagem_peso_padrao'];
	$certificado_conformidade	= $_POST['certificado_conformidade'];
	$valor_diaria			= $_POST['valor_diaria'];
	$codigo_posto			= $_POST['codigo_posto'];
	$tipo_atendimento		= $_POST['tipo_atendimento'];
	
	$locacao			= $_POST['locacao'];
}

if($login_fabrica == 20 AND strlen($os)>0) $desabilita = "SIM";


$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Cadastro de Ordem de Serviço";
if($sistema_lingua == "ES")$title = "Catrastro de órdene de servicio";
/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$digita_os = pg_result ($res,0,0);
if ($digita_os == 'f') {
	echo "<H4>Sem permissão de acesso.</H4>";
	exit;
}

?>

<!--=============== <FUNÇÕES> ================================!-->


<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_os.consumidor_cliente;
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
	janela.rg			= document.frm_os.consumidor_rg;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.focus();
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.email		= document.frm_os.revenda_email;
	janela.focus();
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "revenda_cnpj";
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



/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8;
	var DEL=  46;
	var FRENTE=  39;
	var TRAS=  37;
	var key;
	var tecla;
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true;
			}
		if ( tecla == 13) return false;
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla);
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}




/* ============= <PHP> VERIFICA SE HÁ COMUNICADOS =============
		VERIFICA SE TEM COMUNICADOS PARA ESTE PRODUTO E SE TIVER, RETORNA UM
		LINK PARA VISUALIZAR-LO
		Fábio 07/12/2006
=============================================================== */

function retornaComunicado (http) {
	var imagem = document.getElementById('img_comunicado');
	if (http.readyState == 4) {
alert("estado 4");
		if (http.status == 200) {
alert("estado 200");
			results = http.responseText;
			alert("Resultado="+results);
			if (typeof (results) != 'undefined') {
				if (results!="sem"){
					imagem.style.visibility = "visible";
					document.frm_os.link_comunicado.value=results;
					alert("OK="+results);
				}
				else {
					imagem.style.visibility = "hidden";
					document.frm_os.link_comunicado.value="";
					alert("SEM="+results);
				}
			}else{
				
				imagem.style.visibility = "hidden";
				document.frm_os.link_comunicado.value="";
					alert("SEM_1="+results);
			}
		}
	}
}

function checarComunicado2 (fabrica) {
	var ref = document.frm_os.produto_referencia.value;
	var imagem = document.getElementById('img_comunicado');

	imagem.style.visibility = "hidden";
	document.frm_os.link_comunicado.value="";

	if (ref.length>0){
		url = "ajax_os_cadastro_comunicado.php?fabrica="+fabrica+"&produto="+escape(ref);
		alert(url);
		http.open("GET", url , true);
		http.onreadystatechange = function () { retornaComunicado (http) ; } ;
		http.send(null);
	}else{
	}
}
function trim(str)
{  while(str.charAt(0) == (" ") )
  {  str = str.substring(1);
  }
  while(str.charAt(str.length-1) == " " )
  {  str = str.substring(0,str.length-1);
  }
  return str;
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
			
var http = new Array();
function checarComunicado(fabrica){
	var imagem = document.getElementById('img_comunicado');
	var ref = document.frm_os.produto_referencia.value;

	//imagem.style.visibility = "hidden";
	document.frm_os.link_comunicado.value="";
	imagem.title = "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
	ref = trim(ref);

	if (ref.length>0){
		var curDateTime = new Date();
		http[curDateTime] = createRequestObject();
		url = "ajax_os_cadastro_comunicado.php?fabrica="+fabrica+"&produto="+escape(ref);
		http[curDateTime].open('get',url);
		http[curDateTime].onreadystatechange = function(){
			if (http[curDateTime].readyState == 4) 
			{
				if (http[curDateTime].status == 200 || http[curDateTime].status == 304) 
				{
					var response = http[curDateTime].responseText;
					if (response=="ok"){
						document.frm_os.link_comunicado.value="HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
						imagem.title = "HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
					}
					else {
						document.frm_os.link_comunicado.value="";
						imagem.title = "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
					}
				}
			}
		}
		http[curDateTime].send(null);
	}
}

function abreComunicado(){
	var ref = document.frm_os.produto_referencia.value;
	var desc = document.frm_os.produto_descricao.value;
	if (document.frm_os.link_comunicado.value!=""){
		url = "pesquisa_comunicado.php?produto=" + ref +"&descricao="+desc;
		window.open(url,"comm","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
	}
}

function MostraEsconde(dados) 
{ 

    if (document.getElementById) 
    { 
        var style2 = document.getElementById(dados); 
        if (style2==false) return; 
        if (style2.style.display=="block"){ 
            style2.style.display = "none"; 
            } 
        else{ 
            style2.style.display = "block"; 
        } 
     } 
}

</script>

<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a existência de uma OS com o mesmo número e em
		caso positivo passa a mensagem para o usuário.
=============================================================== -->
<?
//if ($ip == '201.0.9.216') echo $msg_erro;

if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	if ($login_fabrica == 1 AND ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false ) ) {
		$produto_referencia = trim($_POST["produto_referencia"]);
		$produto_voltagem   = trim($_POST["produto_voltagem"]);
		$sqlT =	"SELECT tbl_lista_basica.type
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia')
				AND   tbl_produto.voltagem = '$produto_voltagem'
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type
				ORDER BY tbl_lista_basica.type;";
		$resT = @pg_exec ($con,$sqlT);
		if (pg_numrows($resT) > 0) {
			$s = pg_numrows($resT) - 1;
			for ($t = 0 ; $t < pg_numrows($resT) ; $t++) {
				$typeT = pg_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			$msg_erro .= "<br>Selecione o Type: $result_type";
		}
	}

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo "<!-- ERRO INICIO -->";
	//echo $erro . $msg_erro . "<br><!-- " . $sql . "<br>" . $sql_OS . " -->";
	echo $erro . $msg_erro;
	echo "<!-- ERRO FINAL -->";
?>
	</td>
</tr>
</table>

<? } ?>


<?
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = @pg_exec ($con,$sql);
$hoje = @pg_result ($res,0,0);

?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="left">

		<? if ($login_fabrica == 1 and 1 == 2) { ?>
			<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>
			<tr>
			<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>
			<B>Conforme comunicado de 04/01/2006, as OS's abertas até o dia 31/12/2005 poderão ser digitadas até o dia 31/01/2006.<br>Pedimos atenção especial com relação a esse prazo, pois depois do dia 01/02/2006 somente aceitaremos a abertura das OS's com data posterior a 02/01/2006.</B>
			</td>
			</tr>
			</table>

<? 
	if ($login_tipo_posto == 90 OR $login_tipo_posto == 36 OR $login_tipo_posto == 82 OR $login_tipo_posto == 83 OR $login_tipo_posto == 84 and 1 == 2) { 
?>
			<form name="frm_locacao" method="post" action="<? echo $PHP_SELF ?>">
			<input type="hidden" name="btn_acao">
			<fieldset style="padding: 10;">
				<legend align="center"><font color="#000000" size="2">Locação</font></legend>
				<br>
				<center>
					<font color="#000000" size="2">Nº de Série</font>
					<input class="frm" type="text" name="serie_locacao" size="15" maxlength="20" value="<? echo $serie_locacao; ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número de série Locação e clique no botão para efetuar a pesquisa.');">
					<img border="0" src="imagens/btn_continuar.gif" align="absmiddle" onclick="javascript: if (document.frm_locacao.btn_acao.value == '') { document.frm_locacao.btn_acao.value='locacao'; document.frm_locacao.submit(); } else { alert('Não clique no botão voltar do navegador, utilize somente os botões da tela'); }" style="cursor: hand" alt="Clique aqui p/ localizar o número de série">
				</center>
			</fieldset>
			</form>
<?
			}
			if ($tipo_os == "7" && strlen($os) > 0) {
				$sql =	"SELECT TO_CHAR(data_fabricacao,'DD/MM/YYYY') AS data_fabricacao ,
								pedido                                                   ,
								execucao
						FROM tbl_locacao
						WHERE serie       = '$produto_serie'
						AND   nota_fiscal = '$nota_fiscal';";
				$res = pg_exec($con,$sql);
				
				if (pg_numrows($res) == 1) {
					$data_fabricacao    = trim(pg_result($res,0,data_fabricacao));
					$pedido             = trim(pg_result($res,0,pedido));
					$execucao           = trim(pg_result($res,0,execucao));
?>
				<table width="100%" border="0" cellspacing="5" cellpadding="0">
					<tr valign="top">
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">Execução</font>
							<br>
							<input type="text" name="execucao" size="12" value="<? echo $execucao; ?>" class="frm" readonly>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Fabricação</font>
							<br>
							<input type="text" name="data_fabricacao" size="15" value="<? echo $data_fabricacao; ?>" class="frm" readonly>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">Pedido</font>
							<br>
							<input type="text" name="pedido" size="12" value="<? echo $pedido; ?>" class="frm" readonly>
						</td>
					</tr>
				</table>
				<?
				}
			}
		}
		?>
		
		<?
		// Será obrigatorio o CPF do consumidor no cadastro de Ordem de Serviço.
		//Modificado por Fernando.
		if ($login_fabrica == 15) { ?>
			<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>
			<tr>
				<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>
					A partir de 01/01/2007, será obrigatório o CPF do consumidor no cadastro das Ordens de Serviço.
				</td>
			</tr>
			</table>
		<?}?>

		<!-- ------------- Formulário ----------------- -->

		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $os; ?>">

		<?
		if ($login_fabrica == 1 && $tipo_os == "7") {
			echo "<input type='hidden' name='locacao' value='$tipo_os'>";
		}
		?>
		
<?
		if ($login_fabrica == 3) {
			echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' bgcolor='#66FF99' style='font-color:#ffffff ; font-size:12px'>";
			echo "Não é permitido abrir Ordens de Serviço com data de abertura superior a 90 dias.";
			echo "</td>";
			echo "</tr>";
			echo "</table>";

			echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>";
			echo "Conforme comunicado, é obrigatório o envio de cópia da <br>Nota de Compra juntamente com a Ordem de Serviço.<br>";
			echo "<a href='comunicado_mostra.php?comunicado=735' target='_blank'>Clique para visualizar o Comunicado</a>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}
?>

		<p>
		<? if ($distribuidor_digita == 't') { ?>
			<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr valign='top' style='font-size:12px'>
				<td nowrap>
				Distribuidor pode digitar OS para seus postos.
				<br>
				Digite o código do posto
				<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
				ou deixe em branco para suas próprias OS.
				</td>
			</tr>
			</table>
		<? } ?>

		<br>

		<? if ($login_fabrica == 19 OR $login_fabrica == 20) { ?>
		<center>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">
		<? if($sistema_lingua == 'ES') echo "Tipo de atendimiento";else echo "Tipo de Atendimento";?>

		<select name="tipo_atendimento" size="1" class="frm">
			<option selected></option>
			<?
			$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
			if ($login_fabrica == 19) {
				$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND codigo in (0,2,5) ORDER BY codigo";
			}
			$res = pg_exec ($con,$sql) ;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				$descricao_atendimento = pg_result ($res,$i,descricao);
				$x_tipo_atendimento    = pg_result ($res,$i,tipo_atendimento);

				//--=== Tradução para outras linguas ============================= Raphael HD:1356
				
				$sql_idioma = "SELECT * FROM tbl_tipo_atendimento_idioma WHERE tipo_atendimento = $x_tipo_atendimento AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				
				if (@pg_numrows($res_idioma) >0) {
					$descricao_atendimento  = trim(@pg_result($res_idioma,0,descricao));
				}
				
				//--=== Tradução para outras linguas ================================================
				
				echo "<option ";
				if ($tipo_atendimento == $x_tipo_atendimento ) echo " selected ";
				echo " value='$x_tipo_atendimento'>" ;
				echo pg_result ($res,$i,codigo) . " - " .$descricao_atendimento  ;
				echo "</option>\n";
			}
			?>
		</select>
		<?
//BOSCH 
		if($login_fabrica == 20){
			echo "<br><b><FONT SIZE='' COLOR='#FF9900'>";
			if($sistema_lingua == 'ES') echo "En caso de garantía  de piezas o acesorios no es necesario inserir el producto en la OS";else echo "Nos casos de Garantia de Peças ou  Acessórios não é necessário lançar o Produto na OS.";
			echo "</FONT></b>";	
		}
		?>
		</font>
		<? } ?>

		<table width="100%" border="0" cellspacing="10" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<? if ($pedir_sua_os == 't') { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
				<br>
				<input  name ="sua_os" class ="frm" type ="text" size ="10" maxlength="20" value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
				<?
				} else {
					echo "&nbsp;";
					echo "<input type='hidden' name='sua_os'>";
				}
				?>
			</td>

			<?
			if (trim (strlen ($data_abertura)) == 0 AND ($login_fabrica == 7 OR $login_fabrica == 20)) {
				$data_abertura = $hoje;
			}
			?>

			<? if ($login_fabrica == 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="12" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); ">
				&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'></A>
				<script>
				<!--
				function fnc_pesquisa_produto_serie (campo,form) {
					if (campo.value != "") {
						var url = "";
						url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
						janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
						janela.focus();
					}
				}
				-->
				</script>
			</td>
			<? } ?>



			<? if ($login_fabrica == 19){ ?>
			<td nowrap align='center'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<input class="frm" type="text" name="qtde_produtos" size="2" maxlength="3" value="<? echo $qtde_produtos ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Quantidade de produtos atendidos nesta O.S.'); ">
			</td>
			<? } ?>

			
			<td nowrap>
				<?
				if ($login_fabrica == 3) {
 					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
					if($sistema_lingua == 'ES') echo "Referencia del producto";else echo "Referência do Produto";
					echo "</font>";
				}

				// verifica se tem comunicado para este produto (só entra aqui se for abrir a OS) - FN 07/12/2006
				$arquivo_comunicado="";
				if (strlen ($produto_referencia) >0) {
					$sql ="SELECT tbl_comunicado.comunicado, tbl_comunicado.extensao
						FROM  tbl_comunicado JOIN tbl_produto USING(produto)
						WHERE tbl_produto.referencia = '$produto_referencia'
						AND tbl_comunicado.fabrica = $login_fabrica
						AND tbl_comunicado.ativo IS TRUE";
					$res = pg_exec($con,$sql);
					if (pg_numrows($res) > 0) 
						$arquivo_comunicado= "HÁ ".pg_numrows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
				}
/*visibility:<? if ($arquivo_comunicado) echo "visible;";else echo "hidden;"; ?>*/
				?>
				<br>
				<? if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly>
				<? }else{ ?>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20"
					value="<? echo $produto_referencia ?>" 
					onblur="this.className='frm'; displayText('&nbsp;');checarComunicado(<? echo $login_fabrica ?>);<? if($login_fabrica==15)echo "fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem);"; ?>"
					onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?> <?if($desabilita)echo " readonly";?>>&nbsp;
				<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle'
					onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem) " height='22px' style='cursor: pointer'>
				<? } ?>
				<img src='imagens/botoes/vista.jpg' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0' 
					align='absmiddle'  title="COMUNICADOS"  
					onclick="javascript:abreComunicado()"
					style='cursor: pointer;'>
				<input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>">
			</td>
			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
					if($sistema_lingua == 'ES') echo "Descripción del producto";else echo "Descrição do Produto";
					echo "</font>";
				}
				?>
				
				<br>
				<? if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" readonly>
				<? }else{ ?>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');checarComunicado(<? echo $login_fabrica ?>);" <? if (strlen($locacao) > 0) echo "readonly"; ?><?if($desabilita)echo " disabled";?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)"  style='cursor: pointer' <?if($desabilita)echo "disabled";?>></A>
				<? } ?>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Voltaje";else echo "Voltagem";?></font>
				<br>
				<input class="frm" type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" <? if ($login_fabrica != 1 || strlen($tipo_os) > 0) echo "readonly"; ?> >			</td>
			<td nowrap>
<?
if ($login_fabrica == 6){
	echo "				<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" color='#cc0000'>Data de entrada </font>";
}else{
	echo "				<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\">";
					if($sistema_lingua == 'ES') echo "Fecha de abertura";else echo "Data Abertura";
					echo "</font>";
}
?>
				<br>
				<input name="data_abertura" size="12" maxlength="10" value="
<?
//				if (strlen($data_abertura) == 0 and $login_fabrica <> 1) $data_abertura = date("d/m/Y"); 
				echo $data_abertura; 
?>" <?if($login_fabrica==20)echo "readonly ";?> type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.'); " tabindex="0"><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<? if ($login_fabrica <> 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "N. Serie";else echo "N. Série";?></font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="8" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');<? if($login_fabrica==3 and $login_posto==6359){ echo " MostraEsconde('dados_1');";} ?>" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.');<? if($login_fabrica==3 and $login_posto==6359){ echo " MostraEsconde('dados_1');";} ?> "><br><font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
				<div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
				Mascara: LLNNNNNNLNNL<br>
				L: Letra<BR>
				N: Número
				</div> 
			</td>
			<? } ?>
		</tr>
		</table>

		<? if ($login_fabrica == 1) { ?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font>
				<br>
				<input  name ="codigo_fabricacao" class ="frm" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');">
			</td>
			<td nowrap>
<!--
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Versão/Type</font>
				<br>
-->
<?
/*
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
				echo "<\select>&nbsp;";
*/
?>
			</td>

				<input  name ="laudo_tecnico" class ="frm" type ="hidden" >
				<input name ="satisfacao" class ="frm" type ="hidden">
		</tr>
		</table>
		<? } ?>

		<hr>
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_endereco">
		<input type="hidden" name="consumidor_numero">
		<input type="hidden" name="consumidor_complemento">
		<input type="hidden" name="consumidor_bairro">
		<input type="hidden" name="consumidor_cep">
		<input type="hidden" name="consumidor_cidade">
		<input type="hidden" name="consumidor_estado">
		<input type="hidden" name="consumidor_rg">

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Nombre consumidor ";else echo "Nome Consumidor";?></font>
				<br>
				<input class="frm" type="text" name="consumidor_nome"   size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
			</td>
			<td>
						
				<? if ($login_fabrica <> 19) { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Identificación del consumidor";else echo "CPF/CNPJ do Consumidor";?></font>
				<br>
				<input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
			<? }else{
					echo "<input type='hidden' name='consumidor_cpf'>";
				} ?>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Teléfono";else echo "Fone";?></font>
				<br>
				<input class="frm" type="text" name="consumidor_fone"   size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
		</tr>
		</table>

		<hr>

		<?
		if ($login_fabrica == 7) {
#			echo "<!-- ";
		}
		?>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Nombre distribuidor";else echo "Nome Revenda";?></font>
				<br>
				<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "ID distribuidor";else echo "CNPJ Revenda";?></font>
				<br>
				<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Factura comercial";else echo "Nota Fiscal";?></font>
				<br>
				<input class="frm" type="text" name="nota_fiscal"  size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Fecha compra";else echo "Data Compra/NF";?> </font>
				<br>
				<input class="frm" type="text" name="data_nf"    size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?>><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
		</tr>
		</table>
		<?
		if ($login_fabrica == 7) {
#			echo " -->";
		}
		?>

		<hr>

		<table width="100%" border="0" cellspacing="5" cellpadding="2">
		<tr>
			<?
			if ($login_fabrica ==1) {
				echo "<td ><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				echo "</td>";

					echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
	
					echo "</font></td>";

					echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";

					if($sistema_lingua == 'ES') echo "Distribuidor";else echo "Revenda";
	
					echo "</font>&nbsp;";
					echo "<input type='radio' name='consumidor_revenda' value='R' ";
					if ($consumidor_revenda == 'R') echo " checked"; 
					echo ">&nbsp;&nbsp;</td>";


				echo "<input type='hidden' name='consumidor_revenda' value='R'>";
			}
			

			if($login_fabrica==11){
				//NAO IMPRIME NADA
				echo "<td width='440px'>&nbsp;";
			}else{
				echo "<td>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				if($sistema_lingua == 'ES') echo "Apariencia del producto";else echo "Aparência do Produto";
				echo "</font>";
			}
			echo "<br>";

			if ($login_fabrica == 20) {
				echo "<select name='aparencia_produto' size='1'>";
				echo "<option value=''></option>";

				echo "<option value='NEW' ";
				if ($aparencia_produto == "NEW") echo " selected ";
				echo ">";
				if($sistema_lingua == 'ES') echo "Buena aparencia";else echo "Bom Estado";
				echo "</option>";

				echo "<option value='USL' ";
				if ($aparencia_produto == "USL") echo " selected ";
				echo ">";
				if($sistema_lingua == 'ES') echo "Uso continuo";else echo "Uso intenso";
				echo " </option>";

				echo "<option value='USN' ";
				if ($aparencia_produto == "USN") echo " selected ";
				echo ">";
				if($sistema_lingua == 'ES') echo "Uso normal";else echo "Uso Normal";
				echo "</option>";

				echo "<option value='USH' ";
				if ($aparencia_produto == "USH") echo " selected ";
				echo ">";
				if($sistema_lingua == 'ES') echo "Uso Pesado";else echo "Uso Pesado";
				echo "</option>";

				echo "<option value='ABU' ";
				if ($aparencia_produto == "ABU") echo " selected ";
				echo ">";
				if($sistema_lingua == 'ES') echo "Uso Abusivo";else echo "Uso Abusivo";
				echo "</option>";

				echo "<option value='ORI' ";
				if ($aparencia_produto == "ORI") echo " selected ";
				echo ">";
				if($sistema_lingua == 'ES') echo "Original, sin uso";else echo "Original, sem uso";
				echo "</option>";

				echo "<option value='PCK' ";
				if ($aparencia_produto == "PCK") echo " selected ";
				echo ">";
				if($sistema_lingua == 'ES') echo "Embalaje";else echo "Embalagem";
				echo "</option>";
				
				echo "</select>";
			}else{
				if($login_fabrica==11){
					echo "<input type='hidden' type='text' name='aparencia_produto' value='$aparencia_produto'>";
				}else{
					echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\">";
				}
			}
			
			echo "</td>";
			if ($login_fabrica <> 1) { 
				if($login_fabrica == 11){
					//nao mostra acessórios
				}else{ ?>

			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Accesorios";else echo "Acessórios";?></font>
				<br>
				<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acessórios deixados junto ao produto.');">
			</td>
		<? } 
		}?>
<? if ($login_fabrica == 1 
// 						OR $login_fabrica == 3 
						//conforme e-mail de Samuel (sirlei) a partir de 21/08 nao tem troca de produto para britania, somente ressarcimento financeiro
) { ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Troca faturada</font><br>
				<input class="frm" type="checkbox" name="troca_faturada" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
			</td>
<? } ?>
		</tr>

		</table>


		<? if ($pedir_defeito_reclamado_descricao == 't') { ?>

		<hr>

		<center>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">
		Descrição do Defeito Reclamado pelo Consumidor
		</font>
		<br>
		<textarea class='frm' name='defeito_reclamado_descricao' cols='70' rows='5'><? echo $defeito_reclamado_descricao ?></textarea>


		<? }  # Final do IF do Defeito_Reclamado_Descricao ?>


		<?
		if ($login_fabrica == 7) {
		?>


		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
		<hr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Chamado aberto por</font>
				<br>
				<input class="frm" type="text" name="quem_abriu_chamado" size="20" maxlength="30" value="<? echo $quem_abriu_chamado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Nome do funcionário do cliente que abriu este chamado.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
				<br>
				<input class="frm" type="text" name="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
			</td>
		</tr>
		</table>


		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Taxa de Visita</font>
				<br>
				<input class="frm" type="text" name="taxa_visita" size="8" maxlength="10" value="<? echo $taxa_visita ?>" >
				&nbsp;
				<input class="frm" type='checkbox' name='visita_por_km' value='t' <? if ($visita_por_km == 't') echo " checked " ?> >Km
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Hora Técnica</font>
				<br>
				<input class="frm" type="text" name="hora_tecnica" size="8" maxlength='10' value="<? echo $hora_tecnica ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Regulagem</font>
				<br>
				<input class="frm" type="text" name="regulagem_peso_padrao" size="8" maxlength='10' value="<? echo $regulagem_peso_padrao ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Certificado</font>
				<br>
				<input class="frm" type="text" name="certificado_conformidade" size="8" maxlength='10' value="<? echo $certificado_conformidade ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Diária</font>
				<br>
				<input class="frm" type="text" name="valor_diaria" size="8" maxlength='10' value="<? echo $valor_diaria ?>" >
			</td>
		</tr>
		</table>

		<?
		}
		?>

	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>




<hr width='700'>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<? if ($login_fabrica == 1) { ?>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Não clique no botão voltar do navegador, utilize somente os botões da tela') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: hand;'>
		<? }else { ?>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
		<? } ?>
	</td>
</tr>
</table>


<input type='hidden' name = 'revenda_fone'>
<input type='hidden' name = 'revenda_cidade'>
<input type='hidden' name = 'revenda_estado'>
<input type='hidden' name = 'revenda_endereco'>
<input type='hidden' name = 'revenda_numero'>
<input type='hidden' name = 'revenda_complemento'>
<input type='hidden' name = 'revenda_bairro'>
<input type='hidden' name = 'revenda_cep'>
<input type='hidden' name = 'revenda_email'>

</form>

<p>

<? include "rodape.php";?>
