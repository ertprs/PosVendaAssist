<?

#if ($login_fabrica == 19 and $login_posto == 14068 and strlen ($_POST['btn_acao']) > 0) echo "aqui <br>";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



//if ($login_fabrica == 1) {
//	echo "<H2>Sistema em manuten��o. Estar� dispon�vel em alguns instantes.</H2>";
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

#-------- Libera digita��o de OS pelo distribuidor ---------------
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

/*======= <PHP> FUN�OES DOS BOT�ES DE A��O =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";
	$os = $_POST['os'];
	$os = $_GET['os'];
if ($btn_acao == "continuar") {



	if (strlen ($defeito_reclamado) == 0 AND $login_fabrica == 5) 
	$defeito_reclamado = "null";
	else if (strlen($defeito_reclamado) == 0 and $login_fabrica <> 5) 
	$msg_erro = "Selecione o defeito reclamado.<BR>";
	
	if ($defeito_reclamado == '0') {
	$msg_erro = "Selecione o defeito reclamado.<BR>";}
	
	$sua_os_offline = $_POST['sua_os_offline'];
	if (strlen (trim ($sua_os_offline)) == 0) {
		$sua_os_offline = 'null';
	}else{
		$sua_os_offline = "'" . trim ($sua_os_offline) . "'";
	}

	$x_motivo_troca = trim ($_POST['motivo_troca']);
	if (strlen($x_motivo_troca) == 0) $x_motivo_troca = "null";

	$sua_os = $_POST['sua_os'];
	if (strlen (trim ($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o n�mero da OS Fabricante.<BR>";
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
#  CUIDADO para OS de Revenda que j� vem com = "-" e a sequencia.
#  fazer rotina para contar 6 caracteres antes do "-"
		}
		$sua_os = "'" . $sua_os . "'" ;
	}

	##### IN�CIO DA VALIDA��O DOS CAMPOS #####
	
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
	if ($xdata_abertura == 'null') $msg_erro .= " Digite a data de abertura da OS.<BR>";
	$cdata_abertura = str_replace("'","",$xdata_abertura);


	if ($login_fabrica == 1) {
		$sdata_abertura = str_replace("-","",$cdata_abertura);

		// liberados pela Fabiola em 05/01/2006
		if($login_posto == 5089){ // liberados pela Fabiola em 20/03/2006
			if ($sdata_abertura < 20050101) 
				$msg_erro = "Erro. Data de abertura inferior a 01/01/2005.<br>Lan�amento restrito �s OSs com data de lan�amento superior a 01/01/2005.";
		}elseif($login_posto == 5059 OR $login_posto == 5212){
			if ($sdata_abertura < 20050502) 
				$msg_erro = "Erro. Data de abertura inferior a 02/05/2005.<br>Lan�amento restrito �s OSs com data de lan�amento superior a 01/05/2005.";
		}else{
			if ($sdata_abertura < 20050901)
				$msg_erro = "Erro. Data de abertura inferior a 01/09/2005.<br>OS deve ser lan�ada no sistema antigo at� 30/09.";
		}
	}
	##############################################################
 
 
#################CONSUMIDOR##################### 

	if (strlen(trim($_POST['consumidor_nome'])) == 0) $xconsumidor_nome = 'null';
	else $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";
	if ($xconsumidor_nome == 'null') $msg_erro .= " Digite o nome do consumidor. <br>";
// 	else $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";
// 	if (strlen($xconsumidor_nome) == 0) $msg_erro .= " Digite o nome do consumidor. <br>";
	
	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	$consumidor_cpf = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf = str_replace(" ","",$consumidor_cpf);
	$consumidor_cpf = trim(substr($consumidor_cpf,0,14));

	if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
	else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";

	if ($consumidor_cpf <> "null" and strlen($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) {
			$msg_erro = 'Tamanho do CPF/CNPJ do cliente inv�lido<BR>';
	}
	
	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
	else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";

	$xconsumidor_endereco	= trim ($_POST['consumidor_endereco']) ;
		if ($login_fabrica == 2 or $login_fabrica == 1 or $login_fabrica == 6) {
			if (strlen($xconsumidor_endereco) == 0) $msg_erro .= " Digite o endere�o do consumidor. <br>";
		}
	
	$xconsumidor_numero      = trim ($_POST['consumidor_numero']);
	if (strlen($consumidor_numero) == 0) $msg_erro .= " Digite o N�mero do Endere�o do consumidor. <br>";
	
	$xconsumidor_complemento = trim ($_POST['consumidor_complemento']) ;
		
	$xconsumidor_bairro      = trim ($_POST['consumidor_bairro']) ;

	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
	else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

		
	if ($login_fabrica == 1) {
		if (strlen($xconsumidor_numero) == 0) $msg_erro .= " Digite o n�mero do consumidor. <br>";
		if (strlen($xconsumidor_bairro) == 0) $msg_erro .= " Digite o bairro do consumidor. <br>";
	}

	if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
	else                                      $contrato	= 'f';
	
	$consumidor_cep         = trim ($_POST['consumidor_cep']) ;
	if (strlen($consumidor_cep) == 0) $msg_erro .= " Digite o CEP do consumidor. <br>";
	$consumidor_cep = str_replace (".","",$consumidor_cep);
	$consumidor_cep = str_replace ("-","",$consumidor_cep);
	$consumidor_cep = str_replace ("/","",$consumidor_cep);
	$consumidor_cep = str_replace (",","",$consumidor_cep);
	$consumidor_cep = str_replace (" ","",$consumidor_cep);
	$consumidor_cep = substr ($consumidor_cep,0,8);



#################REVENDA#####################
	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace(" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);

	if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) $msg_erro .= " Tamanho do CNPJ da revenda inv�lido.<BR>";

	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";



	if (strlen(trim($_POST['revenda_nome'])) == 0) $xrevenda_nome = 'null';
	else $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
	if($login_fabrica==6){
		if ($xrevenda_nome == 'null'){
			$msg_erro = "Digite o nome da revenda.<BR>";
		}
	}

	if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
	else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";

	$revenda_cep = str_replace (".","",$revenda_cep);
	$revenda_cep = str_replace ("-","",$revenda_cep);
	$revenda_cep = str_replace ("/","",$revenda_cep);
	$revenda_cep = str_replace (",","",$revenda_cep);
	$revenda_cep = str_replace (" ","",$revenda_cep);
	$revenda_cep = substr ($revenda_cep,0,8);
	
	
	if (strlen(trim($_POST['revenda_endereco'])) == 0) $xrevenda_endereco = 'null';
	else $xrevenda_endereco = "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";
	
	if (strlen(trim($_POST['revenda_numero'])) == 0) $xrevenda_numero = 'null';
	else $xrevenda_numero = "'".str_replace("'","",trim($_POST['revenda_numero']))."'";
	
	if (strlen(trim($_POST['revenda_complemento'])) == 0) $xrevenda_complemento = 'null';
	else $xrevenda_complemento = "'".str_replace("'","",trim($_POST['revenda_complemento']))."'";
	
	if (strlen(trim($_POST['revenda_bairro'])) == 0) $xrevenda_bairro = 'null';
	else $xrevenda_bairro = "'".str_replace("'","",trim($_POST['revenda_bairro']))."'";
	
	if (strlen(trim($_POST['revenda_cidade'])) == 0) $xrevenda_cidade = 'null';	
	else $xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";
	
	if (strlen(trim($_POST['revenda_estado'])) == 0) $xrevenda_estado = 'null';	
	else $xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";

	
#################REVENDA#####################
	
	
	
	if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
	else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

	if (($login_fabrica == 14) or ($login_fabrica == 6)){
			if ($xnota_fiscal == 'null'){
				$msg_erro = "Digite o n�mero da nota fiscal.";
			}
	}

	$qtde_produtos = trim ($_POST['qtde_produtos']);
	if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";

	if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";
//pedido por Leandro Tectoy, feito por takashi 04/08
	if($login_fabrica==6){
		if (strlen ($_POST['data_nf']) == 0) $msg_erro .= " Digite a data de compra.<BR>";
	}
//pedido por Leandrot tectoy, feito por takashi 04/08
	$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.<BR>";

	if (strlen(trim($_POST['produto_serie'])) == 0) $xproduto_serie = 'null';
	else         $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";

	if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
	else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

	if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
	else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";

	if($login_fabrica==6){
		if (strlen ($_POST['aparencia_produto']) == 0) $msg_erro .= " Digite a aparencia do produto.<BR>";
	}
	
	
	if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
	else             $xacessorios = "'".trim($_POST['acessorios'])."'";

	if($login_fabrica==6){
		if (strlen ($_POST['acessorios']) == 0) $msg_erro .= " Digite os acessorios do produto.<BR>";
	}
	
	
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
			$res = @pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$numero_serie_obrigatorio = trim(pg_result($res,0,numero_serie_obrigatorio));

				if ($numero_serie_obrigatorio == 't') {
					$msg_erro .= "<br>N� de S�rie $produto_referencia � obrigat�rio.<BR>";
				}
			}
		}
	}

	##### FIM DA VALIDA��O DOS CAMPOS #####


#if ($login_fabrica == 19 and $login_posto == 14068) echo "aqui ";
#echo "<br>";
#flush;



	$os_reincidente = "'f'";

	##### Verifica��o se o n� de s�rie � reincidente para a Tectoy #####
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
					WHERE   tbl_os.serie   = '$produto_serie'
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
					$msg_erro .= "N� de S�rie $produto_serie digitado � reincidente.<br>
					Favor reabrir a ordem de servi�o $xxxsua_os e acrescentar itens.";
				}else{
					$os_reincidente = "'t'";
				}
			}
		}
	}

	##### Verifica��o se o n� de s�rie � reincidente para a Brit�nia #####
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
				$msg_erro .= "N� de S�rie $produto_serie digitado � reincidente. Favor verificar.<br>Em caso de d�vida, entre em contato com a F�brica.";
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


	if ($login_fabrica == 7) $xdata_nf = $xdata_abertura;

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inv�lido';

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inv�lido';

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
	}
	$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		$msg_erro = " Produto $produto_referencia n�o cadastrado";
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
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) > 0) $msg_erro =  "Data da NF inv�lida.";

					if (strlen($msg_erro) == 0) {
						if (pg_numrows ($res) > 0) {
							$data_final_garantia = trim(pg_result($res,0,0));
						}

						if ($data_final_garantia < $cdata_abertura) {
							$msg_erro = " Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
						}
					}
				}
			}
		}

	}

	if ($login_fabrica == 1) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
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
				$msg_erro = "Posto $codigo_posto n�o cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_result ($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_exec($con,$sql);
					if (pg_numrows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto n�o pertence a sua regi�o";
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





// 	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$os_offline = $_POST['os_offline'];
	if (strlen ($os_offline) == 0) $os_offline = "null";



	if (strlen($msg_erro) == 0){
		/*================ INSERE NOVA OS =========================*/
// echo"cadastrou";
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
						consumidor_cep                                                 ,
						consumidor_endereco                                            ,
						consumidor_numero                                              ,
						consumidor_complemento                                         ,
						consumidor_bairro                                              ,
						consumidor_cidade                                              ,
						consumidor_estado                                              ,
						revenda_cnpj                                                   ,
						revenda_nome                                                   ,
						revenda_fone                                                 ,
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
						tipo_os                                                        ,
						motivo_troca                                                   ,
						defeito_reclamado
					) VALUES (
						$tipo_atendimento                                              ,
						$posto                                                         ,
						$login_fabrica                                                 ,
						$sua_os                                                        ,
						$sua_os_offline                                                ,
						$xdata_abertura                                                ,
						null                                                           ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)  ,
						$xconsumidor_nome                                              ,
						$xconsumidor_cpf                                               ,
						$xconsumidor_fone                                              ,
						$consumidor_cep                                                ,
						'$xconsumidor_endereco'                                        ,
						$xconsumidor_numero                                            ,
						'$xconsumidor_complemento'                                     ,
						'$xconsumidor_bairro'                                          ,
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
						$x_motivo_troca                                                ,
						$defeito_reclamado
					);";
		}else{
			$sql =	"UPDATE tbl_os SET
						tipo_atendimento            = $tipo_atendimento                 ,
						data_abertura               = $xdata_abertura                   ,
						revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)  ,
						consumidor_nome             = $xconsumidor_nome                 ,
						consumidor_cpf              = $xconsumidor_cpf                  ,
						consumidor_fone             = $xconsumidor_fone                 ,
						consumidor_cidade           = $xconsumidor_cidade               ,
						consumidor_estado           = $xconsumidor_estado               ,
						consumidor_endereco         = '$xconsumidor_endereco'           ,
						consumidor_numero           = $xconsumidor_numero               ,
						consumidor_cep              = $consumidor_cep                  ,
						consumidor_complemento      = '$xconsumidor_complemento'        ,
						consumidor_bairro           = '$xconsumidor_bairro'             ,
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
						tipo_os                     = $x_locacao                        ,
						defeito_reclamado           = $defeito_reclamado
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}
//if ($ip == '201.42.109.101') echo $sql;
		$sql_OS = $sql;
// 		echo nl2br ($sql);
 		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

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
		$msg_erro = pg_errormessage($con);
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
// 			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (strlen ($msg_erro) == 0) {
 				$res = @pg_exec ($con,"COMMIT TRANSACTION");
				$imprimir_os = $_POST ['imprimir_os'];
					if ($imprimir_os == "imprimir") {
						header ("Location: os_item.php?os=$os&imprimir=1");
						exit;
					}else{
						header ("Location: os_item.php?os=$os");
						exit;
					}
			}else{
 				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}else{
 			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}

	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = " Data da compra maior que a data da abertura da Ordem de Servi�o.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digita��o da OS no sistema (data de hoje).";

		if (strpos ($msg_erro,"tbl_os_unico") > 0)
			$msg_erro = " O N�mero da Ordem de Servi�o do fabricante j� esta cadastrado.";

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
					tbl_os.revenda_fone                                              ,
					tbl_os.consumidor_cep                                            ,
					tbl_os.consumidor_endereco                                       ,
					tbl_os.consumidor_numero                                         ,
					tbl_os.consumidor_complemento                                    ,
					tbl_os.consumidor_bairro                                         ,
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
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_cep 	= pg_result ($res,0,consumidor_cep);
		$consumidor_endereco= pg_result ($res,0,consumidor_endereco);
		$consumidor_numero	= pg_result ($res,0,consumidor_numero);
		$consumidor_complemento= pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro	= pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		
		$revenda_fone		= pg_result ($res,0,revenda_fone);	
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
		$consumidor_revenda	= pg_result ($res,0,consumidor_revenda);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
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
	$consumidor_cep		= $_POST['consumidor_cep'];
	$consumidor_endereco= $_POST['consumidor_endereco'];
	$consumidor_numero	= $_POST['consumidor_numero'];
	$consumidor_complemento= $_POST['consumidor_complemento'];
	$consumidor_bairro	= $_POST['consumidor_bairro'];
	$consumidor_cidade	= $_POST['consumidor_cidade'];
	$consumidor_estado	= $_POST['consumidor_estado'];
	$revenda_cnpj		= $_POST['revenda_cnpj'];
	$revenda_nome		= $_POST['revenda_nome'];
	$revenda_fone		= $_POST['revenda_fone'];
	$revenda_cep		= $_POST['revenda_cep'];
	$revenda_endereco	= $_POST['revenda_endereco'];
	$revenda_numero		= $_POST['revenda_numero'];
	$revenda_complemento= $_POST['revenda_complemento'];
	$revenda_bairro		= $_POST['revenda_bairro'];
	$revenda_cidade		= $_POST['revenda_cidade'];
	$revenda_estado		= $_POST['revenda_estado'];
	$nota_fiscal		= $_POST['nota_fiscal'];
	$data_nf			= $_POST['data_nf'];
	$produto_referencia	= $_POST['produto_referencia'];
	$produto_descricao	= $_POST['produto_descricao'];
	$produto_voltagem	= $_POST['produto_voltagem'];
	$produto_serie		= $_POST['produto_serie'];
	$qtde_produtos		= $_POST['qtde_produtos'];
	$cor				= $_POST['cor'];
	$consumidor_revenda	= $_POST['consumidor_revenda'];
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

/* PASSA PAR�METRO PARA O CABE�ALHO (n�o esquecer ===========*/

/* $title = Aparece no sub-menu e no t�tulo do Browser ===== */
$title = "Cadastro de Ordem de Servi�o";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$digita_os = pg_result ($res,0,0);
if ($digita_os == 'f') {
	echo "<H4>Sem permiss�o de acesso.</H4>";
	exit;
}

?>

<!--=============== <FUN��ES> ================================!-->


<? include "javascript_pesquisas.php" ?>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' src='ajax_produto.js'></script>
<script language="JavaScript">

/* ============= Fun��o PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Fun��o : fnc_pesquisa_consumidor_nome (nome, cpf)
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

/* ============= Fun��o FORMATA CNPJ =============================
Nome da Fun��o : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digita��o
		Par�m.: cnpj (numero), form (nome do form)
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



/* ========== Fun��o AJUSTA CAMPO DE DATAS =========================
Nome da Fun��o : ajustar_data (input, evento)
		Ajusta a formata��o da M�scara de DATAS a medida que ocorre
		a digita��o do texto.
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
</script>

<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a exist�ncia de uma OS com o mesmo n�mero e em
		caso positivo passa a mensagem para o usu�rio.
=============================================================== -->
<?
//if ($ip == '201.0.9.216') echo $msg_erro;

if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de servi�o j� foi cadastrada";
?>

<!-- ============= <HTML> COME�A FORMATA��O ===================== -->
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730' style='font-family: verdana; font-size: 10px'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	if ($login_fabrica == 1 AND ( strpos($msg_erro,"� necess�rio informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto n�o � v�lido") !== false ) ) {
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

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" style='font-family: verdana; font-size: 10px'>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="left">

		<? if ($login_fabrica == 1 and 1 == 2) { ?>
<table width='700' border='0' cellspacing='2' cellpadding='5' align='center' style='font-family: verdana; font-size: 12px'>
			<tr>
			<td align='center' bgcolor='#6699FF'>
			<B>Conforme comunicado de 04/01/2006, as OS's abertas at� o dia 31/12/2005 poder�o ser digitadas at� o dia 31/01/2006.<br>Pedimos aten��o especial com rela��o a esse prazo, pois depois do dia 01/02/2006 somente aceitaremos a abertura das OS's com data posterior a 02/01/2006.</B>
			</td>
			</tr>
			</table>

<? 
	if ($login_tipo_posto == 90 OR $login_tipo_posto == 36 OR $login_tipo_posto == 82 OR $login_tipo_posto == 83 OR $login_tipo_posto == 84 and 1 == 2) { 
?>
			<form name="frm_locacao" method="post" action="<? echo $PHP_SELF ?>">
			<input type="hidden" name="btn_acao">
			<fieldset style="padding: 10;">
				<legend align="center"><font color="#000000" size="2">Loca��o</font></legend>
				<br>
				<center>
					<font color="#000000" size="2">N� de S�rie</font>
					<input type="text" name="serie_locacao" size="15" maxlength="20" value="<? echo $serie_locacao; ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o n�mero de s�rie Loca��o e clique no bot�o para efetuar a pesquisa.');">
					<img border="0" src="imagens/btn_continuar.gif" align="absmiddle" onclick="javascript: if (document.frm_locacao.btn_acao.value == '') { document.frm_locacao.btn_acao.value='locacao'; document.frm_locacao.submit(); } else { alert('N�o clique no bot�o voltar do navegador, utilize somente os bot�es da tela'); }" style="cursor: hand" alt="Clique aqui p/ localizar o n�mero de s�rie">
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
		
<table width="100%" border="0" cellspacing="5" cellpadding="0" style='font-family: verdana; font-size: 10px'>
	<tr valign="top">
		<td nowrap>
			Execu��o
			<br>
			<input type="text" name="execucao" size="12" value="<? echo $execucao; ?>"  readonly>
		</td>
		<td nowrap>
			Data Fabrica��o
			<br>
			<input type="text" name="data_fabricacao" size="15" value="<? echo $data_fabricacao; ?>"  readonly>
		</td>
		<td nowrap>
			Pedido
			<br>
			<input type="text" name="pedido" size="12" value="<? echo $pedido; ?>"  readonly>
		</td>
	</tr>
</table>
		<?
		}
	}
}
?>

		<!-- ------------- Formul�rio ----------------- -->

		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input  type="hidden" name="os" value="<? echo $os; ?>">

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
			echo "N�o � permitido abrir Ordens de Servi�o com data de abertura superior a 90 dias.";
			echo "</td>";
			echo "</tr>";
			echo "</table>";

			echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>";
			echo "Conforme comunicado, � obrigat�rio o envio de c�pia da <br>Nota de Compra juntamente com a Ordem de Servi�o.<br>";
			echo "<a href='comunicado_mostra.php?comunicado=735' target='_blank'>Clique para visualizar o Comunicado</a>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
}
?>

		<?
		echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
				echo "<tr>";
				echo "<td valign='bottom'><BR><font color='#A22A26'><u><B>Informa��es do Produto</B></u></font></td>";
				echo "</tr>";
		echo "</table>"
		?>
		<p>
		<? if ($distribuidor_digita == 't') { ?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0" style='font-family: verdana; font-size: 10px'>
	<tr valign='top' style='font-size:10px'>
				<td nowrap>
				Distribuidor pode digitar OS para seus postos.
				<br>
				Digite o c�digo do posto
				<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
				ou deixe em branco para suas pr�prias OS.
				</td>
			</tr>
			</table>
		<? } ?>


		<table width="100%" border="0" cellspacing="3" cellpadding="0" style='font-family: verdana; font-size: 10px'>
		<tr valign='top'>
			<? if ($pedir_sua_os == 't') { ?>
		<td nowrap>
				OS Fabricante
				<br>
				<input  name ="sua_os" class ="frm" type ="text" size ="10" maxlength="20" value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o n�mero da OS do Fabricante.');">
		</td>		
		<?
				} else {
					echo "&nbsp;";
					echo "<input type='hidden' name='sua_os'>";
				}
				?>
			

			<?
			if (trim (strlen ($data_abertura)) == 0 AND $login_fabrica == 7) {
				$data_abertura = $hoje;
			}
			?>

			<? if ($login_fabrica == 6){ ?>
			<td nowrap>
				N. S�rie
				<br>
				<input  type="text" name="produto_serie" size="12" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o n�mero de s�rie do aparelho.'); ">
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
			<td nowrap>
				Qtde.Produtos
				<br>
				<input  type="text" name="qtde_produtos" size="2" maxlength="3" value="<? echo $qtde_produtos ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Quantidade de produtos atendidos nesta O.S.'); ">
			</td>
			<? } ?>

			
			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "C�digo do Produto";
				}else{
					echo "Refer�ncia do Produto";
				}
				?>
				<br>
				<? if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
						<input  type="text" name="produto_referencia" onChange='listaDefeitos(this.value);'  size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly>
				<? }else{ ?>
						<input  type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a refer�ncia do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia, document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem)" style='cursor: hand'>
						
				
						
						
				<? } ?>
			</td>
			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "Modelo do Produto";
				}else{
					echo "Descri��o do Produto";
				}
				?>
				<br>
				<? if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
				<input  type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" readonly>
				<? }else{ ?>
						
						<input  type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)"  style='cursor: pointer'></A>
				<? } ?>
			</td>
			<td nowrap>
				Voltagem
				<br>
				<input  type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" <? if ($login_fabrica != 1 || strlen($tipo_os) > 0) echo "readonly"; ?> >			</td>
			<td nowrap>
				<?
				if ($login_fabrica == 6){
					echo "<font color='#cc0000'>Data de entrada </font>";
				}else{
					echo "Data Abertura";
				}
				?>
				<br>
						
						<input name="data_abertura" size="12" maxlength="10" value="<?echo $data_abertura;?>" type="text" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.'); " tabindex="0"><br>Ex.: <? echo date("d/m/Y"); ?>
			
			</td>
			<? if ($login_fabrica <> 6){ ?>
			<td nowrap>
				N. S�rie
				<br>
				<input  type="text" name="produto_serie" size="8" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o n�mero de s�rie do aparelho.'); "><br><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?>
			</td>
			<? } ?>
		</tr>
		</table>
						
		<table width='100%' border='0' cellspacing='3' cellpadding='0' style='font-family:verdana; font-size:10px'>
						
		<tr>
		<td valign='top' width='80'>
		Nota Fiscal
		<br>
		<input  type="text" name="nota_fiscal"  size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o n�mero da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
		</td>
		<td valign='top' width='100'>
		Data Compra
		<br>
		<input  type="text" name="data_nf" size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto est� dentro do PRAZO DE GARANTIA.');" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?>><br>Ex.: <? echo date("d/m/Y"); ?>
		</td>
		<? if ($login_fabrica <> 5){ ?>
		<td valign='top'>
		Defeito Reclamado
		<br>
		<?
		echo "<select name='defeito_reclamado'  style='width: 250px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' >";
		echo "<option id='opcoes' value='0'></option>";
		echo "</select>";
		}
		?>			
		
						
		</td>	
						
						
						
						
						
		<? if ($login_fabrica == 19) { ?>
		<td valign='top' align='left'>
		
		Tipo de Atendimento<BR>
		<select name="tipo_atendimento" size="1" >
		<option selected></option>
		<?
// 		$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
		$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 ORDER BY tipo_atendimento";
		$res = pg_exec ($con,$sql) ;

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			echo "<option ";
			if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
			echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'>" ;
			echo pg_result ($res,$i,tipo_atendimento) . " - " . pg_result ($res,$i,descricao) ;
			echo "</option>\n";
		}
		?>
		</select>
		</font>
		</td>
		<? } ?>
						
						
						
		<? if ($login_fabrica == 1) { ?>
			<td valign='top'>
				C�digo Fabrica��o
				<br>
				<input  name ="codigo_fabricacao" class ="frm" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o n�mero do C�digo de Fabrica��o.');">
			</td>
			<td  valign='top'>
<!--
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Vers�o/Type</font>
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
		<td valign='top'>
			30 dias Satisfa��o DeWALT
			<br>
			<input name ="satisfacao" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>
		</td>
	
		<? } ?>

		</tr>
		</table>			
						
		<table width="100%" border="0" cellspacing="3" cellpadding="0" style='font-family: verdana; font-size: 10px'>
		<tr>
		<td>Consumidor&nbsp;<input type="radio" name="consumidor_revenda" value='C' <? if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked"; ?>></td>

		<td>ou</td>

		<td>Revenda&nbsp;<input type="radio" name="consumidor_revenda" value='R' <? if ($consumidor_revenda == 'R') echo " checked"; ?>>&nbsp;&nbsp;</td>

		<td>
		Apar�ncia do Produto
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
		echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar�ncia externa do aparelho deixado no balc�o.');\">";
		}
		?>

		</td>
		<? if ($login_fabrica <> 1) { ?>
		<td>
		Acess�rios
		<br>
		<input  type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acess�rios deixados junto ao produto.');">
		</td>
		<? } ?>
		<? if ($login_fabrica == 1) { ?>
		<td valign='top'>
		Laudo t�cnico
		<br>
		<input  name ="laudo_tecnico" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo t�cnico.');">
		</td>
		<? } ?>
						
						
<? if (($login_fabrica == 1 OR $login_fabrica == 3) AND 1==2) { ?>
		<td>
		Troca faturada<BR>
		<input  type="checkbox" name="troca_faturada" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
		</td>
		<? } ?>
		</tr>
		</table>
						
						
						<? if (($login_fabrica == 1 OR $login_fabrica == 3) AND 1==2) { ?>
<table width='700' align='center' border='0' cellspacing='2' cellpadding='2' style='font-family: verdana; font-size: 10px'>
<tr>
<td>Preencher somente se for Troca Faturada</td>
</tr>
<tr>
<td>Motivo Troca</td>
</tr>
<tr>
<td>
<select name="motivo_troca" size="1" style='width:300px'>
<option value=""></option>
<?
$sql = "SELECT tbl_defeito_constatado.*
FROM   tbl_defeito_constatado
WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
$sql .= " ORDER BY tbl_defeito_constatado.descricao";

$res = pg_exec ($con,$sql) ;
for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	echo "<option ";
if ($motivo_troca == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
echo pg_result ($res,$i,codigo) ." - ". pg_result ($res,$i,descricao) ;
echo "</option>";
}
?>
</select>
</td>
</tr>
</table>

<? } ?>

		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">

			
		<?
		echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
		echo "<tr>";
				echo "<td><BR><hr width='95%'><BR></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td valign='bottom'><font color='#A22A26'><u><B>Informa��es do Consumidor</B></u></font><BR></td>";
		echo "</tr>";
		echo "</table>"
		?>


		<table width='100%' align='center' border='0' cellspacing='2' cellpadding='2' style='font-family: verdana; font-size: 10px'>
		<tr >
		<td >Nome Consumidor</td>
		
						<td  width='185'>CPF/CNPJ Consumidor</td>
		
		<!--	<td >RG/IE</td>   -->
		
		<? if ($mostra_contrato == true) echo "<td class=\"txt\">Contrato</td>"; ?>
		
		<td>Fone</td>

		<td >Cep</td>
		</tr>

		<tr>
		<td width='280'>
		<input type='hidden' name='consumidor_cliente' value = ''>
		<input  type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")' style="cursor:pointer;">
		</td>

		<td >
		<input  type="text" name="consumidor_cpf"   size="20" maxlength="18" value="<? echo $consumidor_cpf ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")' style="cursor:pointer;">
		</td>
		<!--
		<td >
		<input  type="text" name="consumidor_rg"   size="15" maxlength="30" value="<? echo $consumidor_rg ?>" >
		</td>
		-->
		<?
		if ($mostra_contrato == true) {
				echo "<td class=\"txt1\">";
			echo "<input class=\"frm\" type=\"checkbox\" name=\"consumidor_contrato\" value=\"t\""; if ($consumidor_contrato == 't') echo " checked "; echo ">";
			echo "</td>";
		}
				?>

	<td >
	<input  type="text" name="consumidor_fone"   size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>

	<td >
	<input  type="text" name="consumidor_cep"   size="12" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');">
	</td>
	</tr>
	</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2' style='font-family: verdana; font-size: 10px'>
	<tr >
	<td >Endere�o</td>

	<td >N�mero</td>

	<td >Compl.</td>

	<td >Bairro</td>

	<td >Cidade</td>

	<td >Estado</td>
	</tr>

	<tr>
	<td width='200'>
	<input  type="text" name="consumidor_endereco"   size="40" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endere�o do consumidor.');">
	</td>

	<td width='62'>
	<input  type="text" name="consumidor_numero"   size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o n�mero do endere�o do consumidor.');">
	</td>

	<td width='80'>
	<input  type="text" name="consumidor_complemento"   size="15" maxlength="30" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere�o do consumidor.');">
	</td>

	<td width='94'>
	<input  type="text" name="consumidor_bairro"   size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
	</td>

	<td width='110'>
	<input  type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
	</td>

	<td >
	<input  type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');">
	</td>
	</tr>

	</table>
<!--
		<table width="100%" border="1" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
				<br>
				<input  type="text" name="consumidor_nome"   size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ do Consumidor</font>
				<br>
				<input  type="text" name="consumidor_cpf"   size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e tra�os.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
				<br>
				<input  type="text" name="consumidor_fone"   size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
		</tr>
		</table>
-->
		


		<input type="hidden" name="revenda_email">
		<?
		echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
		echo "<tr>";
				echo "<td><BR><hr width='95%'><BR></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td valign='bottom'><font color='#A22A26'><u><B>Informa��es da Revenda</B></u></font><BR></td>";
		echo "</tr>";
		echo "</table>"
		?>
	<table width="100%" border="0" cellspacing="2" cellpadding="2" style='font-family: verdana; font-size: 10px'>
		<tr valign='top'>
		<td width='280'>
				Nome Revenda
				<br>
				<input  type="text" name="revenda_nome" size="40" maxlength="50" value="<? echo $revenda_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
			</td>
			<td width='185'>
				CNPJ Revenda
				<br>
				<input  type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o n�mero no Cadastro Nacional de Pessoa Jur�dica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
			<td>Fone<BR>
			<input type="text" name="revenda_fone"   size="15" maxlength="20" value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
			<td >Cep
			<br>
			<input  type="text" name="revenda_cep"   size="12" maxlength="10" value="<? echo $revenda_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');">
			</td>
		</tr>
		</table>
						
	<table width='700' align='center' border='0' cellspacing='2' cellpadding='2' style='font-family: verdana; font-size: 10px'>
		<tr >
		<td>Endere�o</td>

		<td >N�mero</td>

		<td>Compl.</td>

		<td >Bairro</td>

		<td >Cidade</td>

		<td >Estado</td>

		</tr>

		<tr>
		<td  width='200'>
		<input  type="text" name="revenda_endereco"   size="40" maxlength="60" value="<? echo $revenda_endereco ?>" onblur="this.className='frm_os'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endere�o da Revenda.');">
		</td>

		<td  width='62'>
		<input  type="text" name="revenda_numero"   size="10" maxlength="20" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o n�mero do endere�o da revenda.');">
		</td>

		<td  width='80'>
		<input  type="text" name="revenda_complemento"   size="15" maxlength="30" value="<? echo $revenda_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere�o da revenda.');">
		</td>

		<td  width='94'>
		<input  type="text" name="revenda_bairro"   size="15" maxlength="30" value="<? echo $revenda_bairro ?>" onblur="this.className='frm_os'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro da revenda.');">
		</td>

		<td width='110'>
		<input  type="text" name="revenda_cidade"   size="15" maxlength="50" value="<? echo $revenda_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade da revenda.');">
		</td>

		<td >
		<input  type="text" name="revenda_estado"   size="2" maxlength="2" value="<? echo $revenda_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado da revenda.');">
		</td>

		</tr>

		<input type="hidden" name="revenda_email" value="">
		

		</table>



		<hr>

	

		<? if ($pedir_defeito_reclamado_descricao == 't') { ?>

		

		<center>
		Descri��o do Defeito Reclamado pelo Consumidor
		<br>
		<textarea name='defeito_reclamado_descricao' cols='70' rows='5'><? echo $defeito_reclamado_descricao ?></textarea>


		<? }  # Final do IF do Defeito_Reclamado_Descricao ?>


		<?
		if ($login_fabrica == 7) {
		?>


	<table width="100%" border="0" cellspacing="5" cellpadding="0" style='font-family: verdana; font-size: 10px'>
		<tr>
		
			<td>
				Chamado aberto por
				<br>
				<input  type="text" name="quem_abriu_chamado" size="20" maxlength="30" value="<? echo $quem_abriu_chamado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Nome do funcion�rio do cliente que abriu este chamado.');">
			</td>
			<td>
				Observa��es
				<br>
				<input  type="text" name="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Observa��es e dados adicionais desta OS.');">
			</td>
		</tr>
		</table>

		<table width="100%" border="0" cellspacing="5" cellpadding="0" style='font-family: verdana; font-size: 10px'>
		<tr>
			<td>
				Taxa de Visita
				<br>
				<input  type="text" name="taxa_visita" size="8" maxlength="10" value="<? echo $taxa_visita ?>" >
				&nbsp;
				<input  type='checkbox' name='visita_por_km' value='t' <? if ($visita_por_km == 't') echo " checked " ?> >Km
			</td>
			<td>
				Hora T�cnica
				<br>
				<input  type="text" name="hora_tecnica" size="8" maxlength='10' value="<? echo $hora_tecnica ?>" >
			</td>
			<td>
				Regulagem
				<br>
				<input  type="text" name="regulagem_peso_padrao" size="8" maxlength='10' value="<? echo $regulagem_peso_padrao ?>" >
			</td>
			<td>
				Certificado
				<br>
				<input  type="text" name="certificado_conformidade" size="8" maxlength='10' value="<? echo $certificado_conformidade ?>" >
			</td>
			<td>
				Di�ria
				<br>
				<input  type="text" name="valor_diaria" size="8" maxlength='10' value="<? echo $valor_diaria ?>" >
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

<table width="100%" border="0" cellspacing="5" cellpadding="0" style='font-family: verdana; font-size: 10px'>
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
						
			<?
			if ($login_fabrica != 1) {
					echo "<input type='checkbox' name='imprimir_os' value='imprimir'> Imprimir OS&nbsp;&nbsp;&nbsp;";
			}
				?>
						
		<? if ($login_fabrica == 1) { ?>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('N�o clique no bot�o voltar do navegador, utilize somente os bot�es da tela') }" ALT="Continuar com Ordem de Servi�o" border='0' style='cursor: hand;'>
		<? }else { ?>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submiss�o') }" ALT="Continuar com Ordem de Servi�o" border='0' style='cursor: pointer'>
		<? } ?>
	</td>
</tr>
</table>



</form>

<p>

<? include "rodape.php";?>
