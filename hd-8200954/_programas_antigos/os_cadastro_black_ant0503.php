<?

#if ($login_fabrica == 19 and $login_posto == 14068 and strlen ($_POST['btn_acao']) > 0) echo "aqui <br>";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



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

#-------- Libera digitação de OS pelo distribuidor ---------------
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
	$imprimir_os = $_POST["imprimir_os"];

	$sua_os_offline = $_POST['sua_os_offline'];
	if (strlen (trim ($sua_os_offline)) == 0) {
		$sua_os_offline = 'null';
	}else{
		$sua_os_offline = "'" . trim ($sua_os_offline) . "'";
	}




	$sua_os = $_POST['sua_os'];
	if (strlen (trim ($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	}else{
		if ($login_fabrica <> 1) {
			if ($login_fabrica <> 3 and strlen($sua_os) < 7) {
				$sua_os = "000000" . trim ($sua_os);
				$sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
			}

			# inserido pelo Ricardo - 04/07/2006
			if ($login_fabrica == 3) {
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


	if (strlen($_POST['consumidor_revenda']) == 0) $msg_erro .= " Selecione consumidor ou revenda.";
	else           $xconsumidor_revenda = "'".$_POST['consumidor_revenda']."'";

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

	if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
	else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
	else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";
	
##takashi 02-09
		$xconsumidor_endereco	= trim ($_POST['consumidor_endereco']) ;
		if ($login_fabrica == 2 OR ($login_fabrica == 1 AND $xconsumidor_revenda<>"'R'")) {
			if (strlen($xconsumidor_endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br>";
		}
		$xconsumidor_numero      = trim ($_POST['consumidor_numero']);
		$xconsumidor_complemento = trim ($_POST['consumidor_complemento']) ;
		$xconsumidor_bairro      = trim ($_POST['consumidor_bairro']) ;
		$xconsumidor_cep         = trim ($_POST['consumidor_cep']) ;

		if ($login_fabrica == 1 AND $xconsumidor_revenda<>"'R'") {
			if (strlen($xconsumidor_numero) == 0) $msg_erro .= " Digite o número do consumidor. <br>";
			if (strlen($xconsumidor_bairro) == 0) $msg_erro .= " Digite o bairro do consumidor. <br>";
}

		if (strlen($xconsumidor_complemento) == 0) $xconsumidor_complemento = "null";
		else                           $xconsumidor_complemento = "'" . $xconsumidor_complemento . "'";

		if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
		else                                      $contrato	= 'f';

		$xconsumidor_cep = str_replace (".","",$xconsumidor_cep);
		$xconsumidor_cep = str_replace ("-","",$xconsumidor_cep);
		$xconsumidor_cep = str_replace ("/","",$xconsumidor_cep);
		$xconsumidor_cep = str_replace (",","",$xconsumidor_cep);
		$xconsumidor_cep = str_replace (" ","",$xconsumidor_cep);
		$xconsumidor_cep = substr ($consumidor_cep,0,8);

		if (strlen($xconsumidor_cep) == 0) $xconsumidor_cep = "null";
		else                   $xconsumidor_consumidor_cep = "'" . $cep . "'";
##takashi 02-09
	
	
	
	
	
	
	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace(" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);
	
	
	/*Se o posto digitar a revenda Black & Decker - cnpj 53296273/0001-91 o sistema deverá apresentar uma mensagem de alerta: Produtos de estoque de revenda deverão ser digitados na opção CADASTRO DE ORDEM DE SERVIÇO DE REVENDA. Se o produto em questão for de estoque de revenda, gentileza digitar nessa opção. Pois em caso de digitações incorretas, a B&D fará a exclusão da OS.*/
	
	if($revenda_cnpj=="53296273000191" and 1==2){	
		echo "<script>alert('"._("Produtos de estoque de revenda deverão ser digitados na opção CADASTRO DE ORDEM DE SERVIÇO DE REVENDA. Se o produto em questão for de estoque de revenda, gentileza digitar nessa opção. Pois em caso de digitações incorretas, a B&D fará a exclusão da OS.")."')</script>";
	
	}
	if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) $msg_erro .= " Tamanho do CNPJ da revenda inválido.<BR>";
	//if (($login_fabrica==11) and ($login_fabrica==6)){
	if ($login_fabrica==11){
		if (strlen($revenda_cnpj) == 0)  $msg_erro .= " Insira o CNPJ da Revenda.<BR>";
		else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

	}else{
	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";
	}
	if (strlen(trim($_POST['revenda_nome'])) == 0) $msg_erro .= " Digite o nome da revenda. <br>";
	else $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";

	if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
	else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";

	
//=====================revenda
	$xrevenda_cep = trim ($_POST['revenda_cep']) ;
	$xrevenda_cep = str_replace (".","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("-","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("/","",$xrevenda_cep);
	$xrevenda_cep = str_replace (",","",$xrevenda_cep);
	$xrevenda_cep = str_replace (" ","",$xrevenda_cep);
	$xrevenda_cep = substr ($xrevenda_cep,0,8);
	/*takashi HD 931  21-12*/
	if (strlen ($_POST['revenda_cnpj']) == 0 and $login_fabrica==3) $msg_erro .= " Digite o CNPJ da 	Revenda.<BR>";
	if (strlen($xrevenda_cep) == 0) $xrevenda_cep = "null";
	else $xrevenda_cep = "'" . $xrevenda_cep . "'";

	//if (strlen(trim($_POST['revenda_cep'])) == 0) $xrevenda_cep = 'null';
	//else $xrevenda_cep = "'".str_replace("'","",trim($_POST['revenda_cep']))."'";
	
	if (strlen(trim($_POST['revenda_endereco'])) == 0) $xrevenda_endereco = 'null';
	else $xrevenda_endereco = "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";
	
	if (strlen(trim($_POST['revenda_numero'])) == 0) $xrevenda_numero = 'null';
	else $xrevenda_numero = "'".str_replace("'","",trim($_POST['revenda_numero']))."'";
	
	if (strlen(trim($_POST['revenda_complemento'])) == 0) $xrevenda_complemento = 'null';
	else $xrevenda_complemento = "'".str_replace("'","",trim($_POST['revenda_complemento']))."'";
	
	if (strlen(trim($_POST['revenda_bairro'])) == 0) $xrevenda_bairro = 'null';
	else $xrevenda_bairro = "'".str_replace("'","",trim($_POST['revenda_bairro']))."'";
	
if (strlen(trim($_POST['revenda_cidade'])) == 0) $msg_erro .= " Digite a cidade da revenda. <br>";
	else $xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";
	
	if (strlen(trim($_POST['revenda_estado'])) == 0) $msg_erro .= " Digite o estado da revenda. <br>";
	else $xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";	
//=====================revenda	

	if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
	else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

	if (($login_fabrica == 14) or ($login_fabrica == 6) or ($login_fabrica == 24) or ($login_fabrica == 11)){
		if ($xnota_fiscal == 'null'){
			$msg_erro = "Digite o número da nota fiscal.";
		}
	}

	$qtde_produtos = trim ($_POST['qtde_produtos']);
	if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";

	if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";
//pedido por Leandro Tectoy, feito por takashi 04/08
	if(($login_fabrica==6) or ($login_fabrica == 24) or ($login_fabrica == 11)){
		if (strlen ($_POST['data_nf']) == 0) $msg_erro .= " Digite a data de compra.";
	}
//pedido por Leandrot tectoy, feito por takashi 04/08
	$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";


	
	if (strlen(trim($_POST['produto_serie'])) == 0) $xproduto_serie = 'null';
	else         $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";
	
	if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
	else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

	if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
	else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";
//pedido leandro tectoy
	if($login_fabrica==6 OR $login_fabrica==1){
		if (strlen ($_POST['aparencia_produto']) == 0) $msg_erro .= " Digite a aparencia do produto.<BR>";
	}
// 
	if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
	else             $xacessorios = "'".trim($_POST['acessorios'])."'";
//pedido leandro tectoy	
	if($login_fabrica==6){
		if (strlen ($_POST['acessorios']) == 0) $msg_erro .= " Digite os acessorios do produto.<BR>";
	}
	
	if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0) $xdefeito_reclamado_descricao = 'null';
	else             $xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";

	if (strlen(trim($_POST['obs'])) == 0) $xobs = 'null';
	else             $xobs = "'".trim($_POST['obs'])."'";

	if (strlen(trim($_POST['quem_abriu_chamado'])) == 0) $xquem_abriu_chamado = 'null';
	else             $xquem_abriu_chamado = "'".trim($_POST['quem_abriu_chamado'])."'";


	//if (strlen($_POST['type']) == 0) $xtype = 'null';
	//else             $xtype = "'".$_POST['type']."'";

	if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
	else             $xsatisfacao = "'".$_POST['satisfacao']."'";

	if (strlen ($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
	else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

//takashi 22/12 HD 925
/*A satisfação 30 dias é gerada somente para consumidores. Em nenhuma hipótese poderá ser permitido a digitação do laudo técnico para OS's em que foi selecionada a opção revenda.*/

	if($xconsumidor_revenda=="'R'" and $xsatisfacao=="'t'"){
		$msg_erro .= "Ordem de Serviço de Revenda não pode ser 30 dias Satisfação DeWALT.<Br>";
	}
//takashi 22/12 HD 925
	$defeito_reclamado = trim ($_POST['defeito_reclamado']);
	
//if ($ip == '201.0.9.216') echo "[ $defeito_reclamado ] e ".strlen($defeito_reclamado);
//	$os = $_POST['os'];
	
	if (strlen ($defeito_reclamado) == 0 AND $login_fabrica == 5) 
		$defeito_reclamado = "null";
	else if (strlen($defeito_reclamado) == 0 and $login_fabrica <> 5) 
		$msg_erro .= "Selecione o defeito reclamado.";
	
	if ($defeito_reclamado == '0') {
	$msg_erro .= "Selecione o defeito reclamado.<BR>";}
	
	

	if ($login_fabrica == 14 ){
		if (strlen($produto_referencia) > 0 AND (strlen($xproduto_serie) == 0 OR $xproduto_serie == 'null')) {
			$sql = "SELECT  tbl_produto.numero_serie_obrigatorio
					FROM    tbl_produto
					JOIN    tbl_linha on tbl_linha.linha = tbl_produto.linha
					WHERE   upper(tbl_produto.referencia) = upper($produto_referencia)
					AND     tbl_linha.fabrica = $login_fabrica";
			$res = @pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$numero_serie_obrigatorio = trim(pg_result($res,0,numero_serie_obrigatorio));

				if ($numero_serie_obrigatorio == 't') {
					$msg_erro .= "<br>Nº de Série $produto_referencia é obrigatório.";
				}
			}
		}
	}

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
		$sqlX = "SELECT to_char (current_date - INTERVAL '90 days', 'YYYY-MM-DD')";
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
					AND     tbl_produto.linha=3
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = @pg_exec($con,$sql);
//linha 3, pois é a linha audio e video
			if (pg_numrows($res) > 0) {
			$xxxos      = trim(pg_result($res,0,os));
				$os_reincidente = "'t'";
				//$msg_erro .= "Nº de Série $produto_serie digitado é reincidente. Favor verificar.<br>Em caso de dúvida, entre em contato com a Fábrica.";
			}
		}
	}
/*TAKASHI 18-12 HD-854*/





	if ($login_fabrica == 7) $xdata_nf = $xdata_abertura;

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

	$produto = 0;

	if (strlen($_POST['produto_voltagem']) == 0)	$voltagem = "null";
	else	$voltagem = "'". $_POST['produto_voltagem'] ."'";

	$sql = "SELECT tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) ";
	if ($login_fabrica == 1) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
//		if($login_posto<>6359){ $sql .= " AND tbl_produto.familia <> 347";}
	}
	$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		$msg_erro .= " Produto $produto_referencia não cadastrado";
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
							$msg_erro .= " Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
						}
					}
				}
			}
		}

	}
//takashi 22-12 chamado 925
	if ($login_fabrica == 1) {
		if($xconsumidor_revenda=="'R'"){
		/*Quando o posto de serviços marcar a opção revenda poderá gravar somente uma OS com um mesmo número de nota fiscal e CNPJ(revenda). Muitos postos estão usando essa opção para digitar notas fiscais de revenda com mais de um produto. Portanto, se o posto de serviços escolher a opção revenda, digitar uma OS e depois tentar digitar uma outra OS com o CNPJ e nota fiscal já digitados anteriormente, o sistema deverá apresentar uma mensagem de erro: Dados de nota fiscal já informados anteriormente na OS.........Sempre que a nota fiscal de estoque da revenda tiver mais que um produto da B&D discriminado, o posto de serviços deverá fazer a digitação na opção: Cadastro OS revenda.*/
			$sql = "SELECT 	os, 
							sua_os 
						FROM tbl_os 
						WHERE fabrica=$login_fabrica
						AND ltrim(nota_fiscal,0) = $xnota_fiscal
						AND revenda_cnpj = $xrevenda_cnpj
						AND excluida IS NOT TRUE
						order by os desc
						limit 1";
			$res = pg_exec($con, $sql);
			if(pg_numrows($res)>0){
				$os_revenda = trim(pg_result($res,0,os));
				$sua_os_revenda = trim(pg_result($res,0,sua_os));
				$msg_erro .= "Dados de nota fiscal já informados anteriormente na OS $os_revenda. Sempre que a nota fiscal de estoque da revenda tiver mais que um produto da B&D discriminado, o posto de serviços deverá fazer a digitação na opção: Cadastro OS revenda.<Br>";
			}
		}
		/*Alguns postos marcam a opção consumidor, porém digitam a revenda no campo nome consumidor, o que caracteriza também uma OS de revenda. Nesse caso, a Telecontrol deverá sempre comparar o campo o nome consumidor com o campo nome revenda e se nos dois campos os dados forem iguais deverá apresentar a mensagem de erro: Gentileza cadastrar esse produto na opção CADASTRO DE ORDEM DE SERVIÇO DE REVENDA, pois se trata de um produto de estoque da revenda.*/
		if(trim(strtoupper($xconsumidor_nome)) == trim(strtoupper($xrevenda_nome))){
			$msg_erro .= "Gentileza cadastrar esse produto na opção CADASTRO DE ORDEM DE SERVIÇO DE REVENDA, pois se trata de um produto de estoque da revenda.<BR>";
		}

		
	}
//takashi 22-12 chamado 925




	if ($login_fabrica == 1) {
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
						consumidor_endereco                                            ,
						consumidor_numero                                              ,
						consumidor_complemento                                         ,
						consumidor_bairro                                              ,
						consumidor_cep                                                 ,
						consumidor_cidade                                              ,
						consumidor_estado                                              ,
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
						tipo_os,
						defeito_reclamado
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
						'$xconsumidor_endereco'                                        ,
						'$xconsumidor_numero'                                          ,
						$xconsumidor_complemento                                       ,
						'$xconsumidor_bairro'                                          ,
						$xconsumidor_cep                                               ,
						$xconsumidor_cidade                                            ,
						$xconsumidor_estado                                            ,
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
						$x_locacao                                                     ,
						$defeito_reclamado
					);";
		}else{
			$sql =	"UPDATE tbl_os SET
						tipo_atendimento            = $tipo_atendimento                 ,
						data_abertura               = $xdata_abertura                   ,
						revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
						consumidor_nome             = $xconsumidor_nome                 ,
						consumidor_cpf              = $xconsumidor_cpf                  ,
						consumidor_fone             = $xconsumidor_fone                 ,
						consumidor_endereco         = '$xconsumidor_endereco'           ,
						consumidor_numero           = '$xconsumidor_numero'             ,
						consumidor_complemento      = $xconsumidor_complemento          ,
						consumidor_bairro           = '$xconsumidor_bairro'             ,
						consumidor_cep              = $xconsumidor_cep                  ,
						consumidor_cidade           = $xconsumidor_cidade               ,
						consumidor_estado           = $xconsumidor_estado               ,
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
						tipo_os                     = $x_locacao,
						defeito_reclamado           = $defeito_reclamado
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}
//if ($ip == '201.43.11.131') echo $sql;
// 		 echo "$sql";
		$sql_OS = $sql;
		$res = @pg_exec ($con,$sql);
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
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
		}
		#--------- grava OS_EXTRA ------------------
		if (strlen ($msg_erro) == 0) {
		
				//===============================REVENDAAAAAAA

//revenda_cnpj
if (strlen($msg_erro) == 0 AND strlen ($revenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 and strlen ($xrevenda_estado) > 0 ) {
//if (strlen($msg_erro) == 0 AND strlen ($xrevenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 and strlen ($xrevenda_estado) > 0 ) {
$sql = "SELECT fnc_qual_cidade ($xrevenda_cidade,$xrevenda_estado)";
$res = pg_exec ($con,$sql);
$monta_sql .= "9: $sql<br>$msg_erro<br><br>";

$xrevenda_cidade = pg_result ($res,0,0);



$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
$res1 = pg_exec ($con,$sql);

$monta_sql .= "10: $sql<br>$msg_erro<br><br>";

if (pg_numrows($res1) > 0) {
	$revenda = pg_result ($res1,0,revenda);
	$sql = "UPDATE tbl_revenda SET
				nome		= $xrevenda_nome          ,
				cnpj		= $xrevenda_cnpj          ,
				fone		= $xrevenda_fone          ,
				endereco	= $xrevenda_endereco      ,
				numero		= $xrevenda_numero        ,
				complemento	= $xrevenda_complemento   ,
				bairro		= $xrevenda_bairro        ,
				cep			= $xrevenda_cep           ,
				cidade		= $xrevenda_cidade
			WHERE tbl_revenda.revenda = $revenda";
	$res3 = @pg_exec ($con,$sql);

if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);
$monta_sql .= "11: $sql<br>$msg_erro<br><br>";
}else{
$sql = "INSERT INTO tbl_revenda (
			nome,
			cnpj,
			fone,
			endereco,
			numero,
			complemento,
			bairro,
			cep,
			cidade
		) VALUES (
			$xrevenda_nome ,
			$xrevenda_cnpj ,
			$xrevenda_fone ,
			$xrevenda_endereco ,
			$xrevenda_numero ,
			$xrevenda_complemento ,
			$xrevenda_bairro ,
			$xrevenda_cep ,
			$xrevenda_cidade
		)";
$res3 = @pg_exec ($con,$sql);

if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);

$monta_sql .= "12: $sql<br>$msg_erro<br><br>";

$sql = "SELECT currval ('seq_revenda')";
$res3 = @pg_exec ($con,$sql);
$revenda = @pg_result ($res3,0,0);
}

			$sql = "UPDATE tbl_os SET revenda = $revenda WHERE os = $os AND fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);
$monta_sql .= "13: $sql<br>$msg_erro<br><br>";
//echo "$sql";
}

//===============================REVENDAAAA
		
		
		
		
		
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

//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
/*
			$email_origem  = "takashi@telecontrol.com.br";
			$email_destino = "takashi@telecontrol.com.br";
			$assunto       = "URGENTE - inserida OS com a tela os_cadastro_tudo";
			$corpo.="<br>Consultar OS ($os) do posto ($login_codigo_posto - $login_posto - $login_nome), fabrica ($login_fabrica), e verificar se está OK.\n\n";
			$corpo.="<br>_______________________________________________\n";
			$corpo.="<br><br>Telecontrol\n";
			$corpo.="<br>www.telecontrol.com.br\n";

			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
//$corpo = $body_top.$corpo;

			if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
				//$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
			}else{
				$msg_erro .= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
			}
//FIM DO ENVIAR EMAIL
				*/
				
				
					if ($imprimir_os == "imprimir") {
						header ("Location: os_item.php?os=$os&imprimir=1");
						exit;
					}else{
						header ("Location: os_item.php?os=$os");

						exit;
					}
				
				
				
				
				/*header ("Location: os_item_new.php?os=$os");
				exit;*/
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


/*
//ENVIA EMAIL caso tenha erro
//ENVIA EMAIL caso tenha erro
			$email_origem  = "takashi@telecontrol.com.br";
			$email_destino = "takashi@telecontrol.com.br";
			$assunto       = "ERROOOO - inserida OS com a tela os_cadastro_tudo";
			$corpo.="<br>ERROOOO ao cadastrar OS, posto ($login_codigo_posto - $login_posto - $login_nome), fabrica ($login_fabrica)\n\n";
			$corpo.="<br> SQL = $sql_OS\n";
			$$corpo.="<br> msg = $msg_erro\n";
			$corpo.="<br>_______________________________________________\n";
			$corpo.="<br><br>Telecontrol\n";
			$corpo.="<br>www.telecontrol.com.br\n";

			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
//$corpo = $body_top.$corpo;

			if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
				//$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
			}else{
				$msg_erro .= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
			}
//FIM DO ENVIAR EMAIL

*/

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
					tbl_os.consumidor_endereco                                       ,
					tbl_os.consumidor_numero                                         ,
					tbl_os.consumidor_complemento                                    ,
					tbl_os.consumidor_bairro                                         ,
					tbl_os.consumidor_cep                                            ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda                                                   ,
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
					tbl_os.acessorios                                                ,
					tbl_os.tipo_os                                                   ,
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
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cpf 	= pg_result ($res,0,consumidor_cpf);
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		//takashi 02-09
		$consumidor_endereco	= pg_result ($res,0,consumidor_endereco);
		$consumidor_numero	= pg_result ($res,0,consumidor_numero);
		$consumidor_complemento	= pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro	= pg_result ($res,0,consumidor_bairro);
		$consumidor_cep	= pg_result ($res,0,consumidor_cep);
		//takashi 02-09		
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
		$consumidor_revenda	= pg_result ($res,0,consumidor_revenda);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
		$acessorios	= pg_result ($res,0,acessorios);
		$codigo_fabricacao	= pg_result ($res,0,codigo_fabricacao);
		$type				= pg_result ($res,0,type);
		$satisfacao			= pg_result ($res,0,satisfacao);
		$laudo_tecnico		= pg_result ($res,0,laudo_tecnico);
		$tipo_os_cortesia	= pg_result ($res,0,tipo_os_cortesia);
		$produto_serie		= pg_result ($res,0,serie);
		$qtde_produtos		= pg_result ($res,0,qtde_produtos);
		$produto_referencia	= pg_result ($res,0,produto_referencia);
		$produto_descricao	= pg_result ($res,0,produto_descricao);
		$produto_voltagem	= pg_result ($res,0,produto_voltagem);
		$troca_faturada		= pg_result ($res,0,troca_faturada);
		$codigo_posto		= pg_result ($res,0,codigo_posto);
		$tipo_os		= pg_result ($res,0,tipo_os);
		$xxxrevenda =pg_result($res,0, revenda);

		$xsql  = "SELECT tbl_revenda.revenda,
						tbl_revenda.nome, 
						tbl_revenda.cnpj, 
						tbl_revenda.fone, 
						tbl_revenda.endereco, 
						tbl_revenda.numero, 
						tbl_revenda.complemento, 
						tbl_revenda.bairro, 
						tbl_revenda.cep, 
						tbl_cidade.nome AS cidade,	
						tbl_cidade.estado 
						FROM tbl_revenda 
						LEFT JOIN tbl_cidade USING (cidade) 
						WHERE tbl_revenda.revenda = $xxxrevenda";
		$res1 = pg_exec ($con,$xsql);
		//echo "$xsql";
		if (pg_numrows($res1) > 0) {
			$revenda_nome = pg_result ($res1,0,nome);
			$revenda_cnpj = pg_result ($res1,0,cnpj);
			$revenda_fone = pg_result ($res1,0,fone);
			$revenda_endereco = pg_result ($res1,0,endereco);
			$revenda_numero = pg_result ($res1,0,numero);
			$revenda_complemento = pg_result ($res1,0,complemento);
			$revenda_bairro = pg_result ($res1,0,bairro);
			$revenda_cep = pg_result ($res1,0,cep);
			$revenda_cidade = pg_result ($res1,0,cidade);
			$revenda_estado = pg_result ($res1,0,estado);
		}

	}
	
}



/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {
	$os					= $_POST['os'];
	$sua_os				= $_POST['sua_os'];
	$data_abertura		= $_POST['data_abertura'];
	$consumidor_nome	= $_POST['consumidor_nome'];
	$consumidor_cpf 	= $_POST['consumidor_cpf'];
	$consumidor_cidade	= $_POST['consumidor_cidade'];
	$consumidor_fone	= $_POST['consumidor_fone'];
	$consumidor_estado	= $_POST['consumidor_estado'];
	//takashi 02-09
	$consumidor_endereco= $_POST['consumidor_endereco'];
	$consumidor_numero	= $_POST['consumidor_numero'];
	$consumidor_complemento	= $_POST['consumidor_complemento'];
	$consumidor_bairro	= $_POST['consumidor_bairro'];
	$consumidor_cep	= $_POST['consumidor_cep'];
	//takashi 02-09
	$revenda_cnpj		= $_POST['revenda_cnpj'];
	$revenda_nome		= $_POST['revenda_nome'];
	$nota_fiscal		= $_POST['nota_fiscal'];
	$data_nf			= $_POST['data_nf'];
	$produto_referencia	= $_POST['produto_referencia'];
	$produto_descricao	= $_POST['produto_descricao'];
	$produto_voltagem	= $_POST['produto_voltagem'];
	$produto_serie		= $_POST['produto_serie'];
	$qtde_produtos		= $_POST['qtde_produtos'];
	$cor				= $_POST['cor'];
	$consumidor_revenda	= $_POST['consumidor_revenda'];
	$acessorios	= $_POST['acessorios'];
	$type				= $_POST['type'];
	$satisfacao			= $_POST['satisfacao'];
	$laudo_tecnico		= $_POST['laudo_tecnico'];

	$obs				= $_POST['obs'];
//	$chamado			= $_POST['chamado'];
	$quem_abriu_chamado = $_POST['quem_abriu_chamado'];
	$taxa_visita				= $_POST['taxa_visita'];
	$visita_por_km				= $_POST['visita_por_km'];
	$hora_tecnica				= $_POST['hora_tecnica'];
	$regulagem_peso_padrao		= $_POST['regulagem_peso_padrao'];
	$certificado_conformidade	= $_POST['certificado_conformidade'];
	$valor_diaria				= $_POST['valor_diaria'];
	$codigo_posto				= $_POST['codigo_posto'];
	
	$locacao					= $_POST['locacao'];
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Cadastro de Ordem de Serviço";

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
<script language='javascript' src='ajax_cep.js'></script>
<script type="text/javascript" src="javascript/prototype.js"></script>
<script type="text/javascript" src="javascript/autocomplete.js"></script>
<script language="JavaScript">

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
	
//ajax defeito_reclamado
function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
	document.forms[0].defeito_reclamado.options.length = 1;
	//opcoes é o nome do campo combo
	idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_produto_antigo.php?produto_referencia="+valor, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(ajax.responseXML);//após ser processado-chama fun
			} else {idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
					}
		}
	}
	//passa o código do produto escolhido
	var params = "produto_referencia="+valor;
	ajax.send(null);
	}
}

function montaCombo(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
	for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
		 var item = dataArray[i];
		//contéudo dos campos no arquivo XML
		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
		var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
		idOpcao.innerHTML = "Selecione o defeito";
		//cria um novo option dinamicamente  
		var novo = document.createElement("option");
		novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
		novo.value = codigo;		//atribui um valor
		novo.text  = nome;//atribui um texto
		document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
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

if ($login_fabrica == 15) { ?>
	<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>
	<tr>
		<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>
			A partir de 01/01/2007, será obrigatório o CPF do cunsumidor no cadastro das Ordens de Serviço.
		</td>
	</tr>
	</table>
<? } ?>


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
		<td valign='top'>
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


		<table width="100%" border="0" cellspacing="5" cellpadding="2">
		<tr>
		<? if ($pedir_sua_os == 't') { ?>	
		<td nowrap  valing='top'>
				
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
				<br>
				<input  name ="sua_os" class ="frm" type ="text" size ="10" maxlength="20" value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
				<?
				} else {
					echo "&nbsp;";
					echo "<input type='hidden' name='sua_os'>";
				
				?>
			</td>
			<?}?>
			<?
			if (trim (strlen ($data_abertura)) == 0 AND $login_fabrica == 7) {
				$data_abertura = $hoje;
			}
			?>

			<? if ($login_fabrica == 6){ ?>
		<td nowrap  valign='top'>
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
			<td nowrap align='center'  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<input class="frm" type="text" name="qtde_produtos" size="2" maxlength="3" value="<? echo $qtde_produtos ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Quantidade de produtos atendidos nesta O.S.'); ">
			</td>
			<? } ?>

			
<td nowrap  valign='top'>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>";
				}
				// verifica se tem comunicado para este produto (só entra aqui se for abrir a OS) - FN 07/12/2006
				$arquivo_comunicado="";
				if (strlen ($produto_referencia) >0) {
					$sql ="SELECT *
						FROM  tbl_comunicado JOIN tbl_produto USING(produto)
						WHERE tbl_produto.referencia = '$produto_referencia'
						AND tbl_comunicado.fabrica = $login_fabrica
						AND tbl_comunicado.ativo IS TRUE";
					$res = pg_exec($con,$sql);
					if (pg_numrows($res) > 0) 
						$arquivo_comunicado= "HÁ ".pg_numrows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
				}
				?>
				<br>
				<? if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly>
				<? }else{ ?>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');checarComunicado(<? echo $login_fabrica ?>);" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem) " style='cursor: hand'>
				<? } ?>
				<img src='imagens/botoes/vista.jpg' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0' 
					align='absmiddle'  title="COMUNICADOS"  
					onclick="javascript:abreComunicado()"
					style='cursor: pointer;'>
				<input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>">
			</td>
						<td nowrap  valign='top'>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font>";
				}
				?>
				<br>
				<? if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
				<!--<input class="frm" type="text" name="produto_descricao" size="30" value="<?// echo $produto_descricao ?>" readonly>-->
				1<input type="text" id="produto_descricao" name="produto_descricao" value='<?echo $nome;?>' size="60" class='frm'>
				<script type="text/javascript">
			   
				new CAPXOUS.AutoComplete("produto_descricao", function() {
					return "ajax_busca_produto.php?typing=" + this.text.value;
				});
				</script>
				<? }else{ ?>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');checarComunicado(<? echo $login_fabrica ?>);" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)"  style='cursor: pointer'></A>
				<? } ?>
			</td>
						<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Voltagem</font>
				<br>
				<input class="frm" type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" <? if ($login_fabrica != 1 || strlen($tipo_os) > 0) echo "readonly"; ?> >			</td>
						<td nowrap  valign='top'>
<?
if ($login_fabrica == 6){
	echo "				<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" color='#cc0000'>Data de entrada </font>";
}else{
	echo "				<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\">Data Abertura </font>";
}
?>
				<br>
<?
//				if (strlen($data_abertura) == 0 and $login_fabrica <> 1) $data_abertura = date("d/m/Y"); 
?>
				<input name="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura; ?>" type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.'); " tabindex="0"><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<? if ($login_fabrica <> 6){ ?>
				<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="8" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); "><br><font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
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
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">30 dias Satisfação DeWALT</font>
				<br>
				<input name ="satisfacao" class ="frm" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo técnico</font>
				<br>
				<input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo técnico.');">
			</td>
	</tr>
		</table>
		<? } ?>

		<table width="100%" border="0" cellspacing="5" cellpadding="2">
		<tr valign='top'>
		<td width='100' valign='top' nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
		<br>
		<input class="frm" type="text" name="nota_fiscal"  size="10"  maxlength="8"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
		</td>
		<td width='110' valign='top' nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
		<br>
		<input class="frm" type="text" name="data_nf"    size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?>><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
		</td>
		<? if ($login_fabrica <> 5){ ?>
		<td valign='top' align='left'>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
		<br>
		<?
		echo "<select name='defeito_reclamado'  style='width: 220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' >";
		echo "<option id='opcoes' value='0'></option>";
		echo "</select>";
		echo "</td>";
		}
		?>
		<? if (($login_fabrica == 19) or ($login_fabrica == 20)) { ?>
		<td align='left'>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">
		Tipo de Atendimento</font><BR>
		<select name="tipo_atendimento" style='width:220px;'>
		<option></option>
		<?
		$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
	//	$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 ORDER BY tipo_atendimento";
		$res = pg_exec ($con,$sql) ;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option ";
			if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
			echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'>" ;
			echo pg_result ($res,$i,tipo_atendimento) . " - " . pg_result ($res,$i,descricao) ;
			echo "</option>";
		}
		?>
		</select>
		</td>
		<? } ?>
		</tr>
		</table>
		

		<hr>
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">
						<!--
						<input type="hidden" name="consumidor_endereco">
						<input type="hidden" name="consumidor_numero">
						<input type="hidden" name="consumidor_complemento">
						<input type="hidden" name="consumidor_bairro">
						<input type="hidden" name="consumidor_cep">
						<input type="hidden" name="consumidor_cidade">
						<input type="hidden" name="consumidor_estado">
						-->

		<table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_nome"   size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
			</td>
			<? if($login_fabrica<>19){ ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ do Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
			</td>
			<? } ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
				<br>
				<input class="frm" type="text" name="consumidor_fone"   size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
			<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
			<br>
			<input class="frm" type="text" name="consumidor_cep"   size="12" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');">
			</td>
		</tr>
		</table>
	<table width='750' align='center' border='0' cellspacing='5' cellpadding='2'>
	<tr>
	<td align='left' nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font><BR>
	<input class="frm" type="text" name="consumidor_endereco"   size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
	</td>

	<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font><BR>
	<input class="frm" type="text" name="consumidor_numero"   size="5" maxlength="10" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
	</td>

	<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font><BR>
	<input class="frm" type="text" name="consumidor_complemento"   size="10" maxlength="30" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço do consumidor.');">
	</td>

	<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font><BR>
	<input class="frm" type="text" name="consumidor_bairro"   size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
	</td>

	<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font><BR>
	<input class="frm" type="text" name="consumidor_cidade" size="12" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');"></td>

	<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font><BR>
	<input class="frm" type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');">
	</td>
	</tr>

	</table>
		<hr>

		<?
		if ($login_fabrica == 7) {
#			echo "<!-- ";
		}
		?>

		<table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
			<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
			<br>
			<input class="frm" type="text" name="revenda_fone"   size="15" maxlength="20" value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
			<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
			<br>
			<input class="frm" type="text" name="revenda_cep"   size="10" maxlength="10" value="<? echo $revenda_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');">
			</td>
		</tr>
		</table>
		<table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
		<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font>
		<br>
		<input class="frm" type="text" name="revenda_endereco"   size="30" maxlength="60" value="<? echo $revenda_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço da Revenda.');">
		</td>

		<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font>
		<br>
		<input class="frm" type="text" name="revenda_numero"   size="5" maxlength="10" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço da revenda.');">
		</td>

		<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
		<br>
		<input class="frm" type="text" name="revenda_complemento"   size="15" maxlength="30" value="<? echo $revenda_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço da revenda.');">
		</td>

		<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font>
		<br>
		<input class="frm" type="text" name="revenda_bairro"   size="13" maxlength="30" value="<? echo $revenda_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro da revenda.');">
		</td>

		<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font>
		<br>
		<input class="frm" type="text" name="revenda_cidade"   size="15" maxlength="50" value="<? echo $revenda_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade da revenda.');">
		</td>

		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font>
		<br>
		<input class="frm" type="text" name="revenda_estado"   size="2" maxlength="2" value="<? echo $revenda_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado da revenda.');">
		</td>
		</tr>
		</table>
						<!--
						<input type='hidden' name = 'revenda_fone'>
						<input type='hidden' name = 'revenda_cep'>
						<input type='hidden' name = 'revenda_endereco'>
						<input type='hidden' name = 'revenda_numero'>
						<input type='hidden' name = 'revenda_complemento'>
						<input type='hidden' name = 'revenda_bairro'>
						<input type='hidden' name = 'revenda_cidade'>
						<input type='hidden' name = 'revenda_estado'>
						-->
						<input type='hidden' name = 'revenda_email'>			
		<?
		if ($login_fabrica == 7) {
#			echo " -->";
		}
		?>

		<hr>

		<table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
		<tr>
			<?
			if ($login_fabrica <> 19 and $login_fabrica <> 1) {
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				echo "Consumidor</font>&nbsp;";
				echo "<input type='radio' name='consumidor_revenda' value='C' " ;
				if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked"; 
				echo "></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>ou</font></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Revenda</font>&nbsp;";
				echo "<input type='radio' name='consumidor_revenda' value='R' ";
				if ($consumidor_revenda == 'R') echo " checked"; 
				echo ">&nbsp;&nbsp;</td>";
			}else{
					echo "<input type='hidden' name='consumidor_revenda' value='C'>";
			}
			?>
			<td>
				<?
				if($login_fabrica == 11){
					//NAO IMPRIME NADA
					echo "<td width='440px'>&nbsp;";
				}else{
					echo "<td>";
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
					echo "Aparência do Produto";
					echo "</font>";
				}
				?>

				<br>
				<? if ($login_fabrica == 20) {
					echo "<select name='aparencia_produto' size='1'>";
					echo "<option value=''></option>";

					echo "<option value='NEW' ";
					if ($aparencia_produto == "NEW") echo " selected ";
					echo "> Bom Estado </option>";

					echo "<option value='USL' ";
					if ($aparencia_produto == "USL") echo " selected ";
					echo "> Uso intenso </option>";

					echo "<option value='USN' ";
					if ($aparencia_produto == "USN") echo " selected ";
					echo "> Uso Normal </option>";

					echo "<option value='USH' ";
					if ($aparencia_produto == "USH") echo " selected ";
					echo "> Uso Pesado </option>";

					echo "<option value='ABU' ";
					if ($aparencia_produto == "ABU") echo " selected ";
					echo "> Uso Abusivo </option>";

					echo "<option value='ORI' ";
					if ($aparencia_produto == "ORI") echo " selected ";
					echo "> Original, sem uso </option>";

					echo "<option value='PCK' ";
					if ($aparencia_produto == "PCK") echo " selected ";
					echo "> Embalagem </option>";
					
					echo "</select>";
				}else{
					if($login_fabrica==11){
						echo "<input type='hidden' type='text' name='aparencia_produto' value='$aparencia_produto'>";
					}else{
						echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\">";
					}
				}
				?>

			</td>
<? if ($login_fabrica <> 1) { 
	if($login_fabrica == 11){
		//nao mostra acessórios
	}else{ ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acessórios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acessórios deixados junto ao produto.');">
			</td>
	<?}
 } ?>
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


		<table width="100%" border="1" cellspacing="5" cellpadding="0">
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


		<table width="100%" border="1" cellspacing="5" cellpadding="0">
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
		<?
		if ($login_fabrica != 1) {
		echo "<input type='checkbox' name='imprimir_os' value='imprimir'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>";
		}
		?>
		<? if ($login_fabrica == 1) { ?>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Não clique no botão voltar do navegador, utilize somente os botões da tela') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: hand;'>
		<? }else { ?>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
		<? } ?>
	</td>
</tr>
</table>




</form>

<p>

<? include "rodape.php";?>
