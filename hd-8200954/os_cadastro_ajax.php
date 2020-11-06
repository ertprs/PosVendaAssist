<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_usuario.php';
include 'funcoes.php';

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);

if (strlen($_POST['os']) > 0)    $os     = trim($_POST['os'])    ;
if (strlen($_GET['os']) > 0)     $os     = trim($_GET['os'])     ;
if (strlen($_POST['sua_os']) > 0)$sua_os = trim($_POST['sua_os']);
if (strlen($_GET['sua_os']) > 0) $sua_os = trim($_GET['sua_os']) ;
if (strlen($_GET['reabrir']) > 0)$reabrir= $_GET['reabrir'];

#-------- Libera digitação de OS pelo distribuidor ---------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
	$sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	$distribuidor_digita = pg_result ($res,0,0);
	if (strlen ($posto) == 0) $posto = $login_posto;
}
#----------------------------------------------------------------


if (strlen($reabrir) > 0) {
	$sql = "SELECT count(*)
			FROM tbl_os
			JOIN tbl_os_produto USING(os)
			JOIN tbl_os_item USING(os_produto)
			WHERE os=$os
			AND fabrica=$login_fabrica
			AND ((tbl_os_item.servico_realizado IN (SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica=$login_fabrica AND troca_produto)) AND (SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica=$login_fabrica AND troca_produto) IS NOT NULL)";
	$res = pg_exec ($con,$sql) ;
	if (pg_result ($res,0,0) == 0) {
		$sql = "UPDATE tbl_os SET data_fechamento = null, finalizada = null
				WHERE  tbl_os.os      = $os
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	else{
		$msg_erro .= "Esta OS não pode ser reaberta pois a solução foi a troca do produto.";
		echo "<script language='javascript'>alert('Esta os não pode ser reaberta pois o produto foi trocado pela fábrica'); history.go(-1);</script>";
		exit();
	}
}

// fabio 17/01/2007 - verifica o status das OS da britania 
if (strlen($os)>0 AND ($login_fabrica==3 OR $login_fabrica==11)){
	$sql = "SELECT  status_os,observacao
			FROM    tbl_os_status
			WHERE   os = $os
			ORDER BY data DESC LIMIT 1";
	$res = pg_exec ($con,$sql) ;
	if (@pg_numrows($res) > 0) {
		$status=pg_result ($res,0,status_os);
		$observacao=pg_result ($res,0,observacao);
		if ($status=='62') {
			if (strpos($observacao,"troca")>0) {
				$msg_intervencao .= "<b style='color:#FF3333'>OS com intervenção da assistência técnica da Fábrica</b><br><b style='color:#000;font-size:12px'>O produto selecionado deve ser trocado.<br> Selecione o Defeito Constatado e a Solução para continuar</b>";
				header ("Location: os_finalizada.php?os=$os");
				exit;
			}
			else {
				header ("Location: os_finalizada.php?os=$os");
				exit;
				//adicionado para digitar constatado e solucao
			}
		}
		if ($status=='65') {
			header ("Location: os_press.php?os=$os");
			exit;
		}
		if ($status=='72') {
			header ("Location: os_finalizada.php?os=$os");
			exit;
			//adicionado para digitar constatado e solucao
		}
 	}
}


//adicionado por Fabio 02/01/2007- numero de itens na OS
$qtde_itens_mostrar="";
if (isset($_GET['n_itens']) AND strlen($_GET['n_itens'])>0){
	$qtde_itens_mostrar = $_GET['n_itens'];
	if ($qtde_itens_mostrar>10)$qtde_itens_mostrar=10;
	if ($qtde_itens_mostrar<0)$qtde_itens_mostrar=3;
}else {
	$qtde_itens_mostrar=3;
}
$numero_pecas_faturadas=0;
// fim do numero de linhas - Fabio 02/01/2007

function crossUrlDecode($source) {
	$decodedStr = '';
	$pos = 0;
	$len = strlen($source);
	 
	while ($pos <$len) {
		$charAt = substr ($source, $pos, 1);
		if ($charAt == '?') {
			$char2 = substr($source, $pos, 2);
			$decodedStr .= htmlentities(utf8_decode($char2),ENT_QUOTES,'ISO-8859-1');
			$pos += 2;
		}
		elseif(ord($charAt)> 127) {
			$decodedStr .= "&#".ord($charAt).";";
			$pos++;
		}
		elseif($charAt == '%') {
			$pos++;
			$hex2 = substr($source, $pos, 2);
			$dechex = chr(hexdec($hex2));
			if($dechex == '?') {
				$pos += 2;
				if(substr($source, $pos, 1) == '%') {
					$pos++;
					$char2a = chr(hexdec(substr($source, $pos, 2)));
					$decodedStr .= htmlentities(utf8_decode($dechex . $char2a),ENT_QUOTES,'ISO-8859-1');
				}
				else {
					$decodedStr .= htmlentities(utf8_decode($dechex));
				}
			}
			else {
				$decodedStr .= $dechex;
			}
			$pos += 2;
		}
		else {
			$decodedStr .= $charAt;
			$pos++;
		}
	}
	return $decodedStr;
}

## AJAX para pegar a descrição do produto
if ($_POST['getProduto']=="sim"){
	$produto_referencia = $_POST['produto_referencia'];
	if (strlen($produto_referencia)>0){

		$sql="SELECT tbl_produto.referencia,tbl_produto.descricao
				FROM tbl_produto 
				JOIN tbl_linha USING(linha) 
				WHERE upper(referencia)=upper('$produto_referencia') 
				AND tbl_linha.fabrica = $login_fabrica LIMIT 1";

		if($login_fabrica==24){
			$sql="SELECT tbl_produto.referencia,tbl_produto.descricao
					FROM tbl_produto 
					JOIN tbl_linha USING(linha) 
					WHERE referencia like '$produto_referencia' LIMIT 1";
		}
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res)>0){
			$produto_referencia    = pg_result ($res,0,'referencia') ;
			$produto_descricao     = pg_result ($res,0,'descricao') ;
			echo "$produto_referencia - $produto_descricao";
		}else{
			echo "Selecione o produto";
		}
	}else{
		echo "Selecione o produto";
	}
	exit;
}


# Busca o defeito reclamado, constatado e solução por AJAX!!!!!
if (strlen($_GET['busca']) > 0){
	$tipo_busca = trim($_GET['busca']);

	if ($tipo_busca=='defeito_reclamado'){
		$produto_referencia = $_POST["produto_referencia"]; 
		$sql="SELECT familia, 
					fabrica, 
					produto, 
					linha 
				FROM tbl_produto 
				JOIN tbl_linha USING(linha) 
				WHERE upper(referencia)=upper('$produto_referencia') 
				AND tbl_linha.fabrica = $login_fabrica LIMIT 1";
		if($login_fabrica==24){
			$sql="SELECT familia,
						fabrica,
						produto,
						linha
					FROM tbl_produto 
					JOIN tbl_linha USING(linha) 
					WHERE referencia like '$produto_referencia' LIMIT 1";
		}
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res)>0){
			$familia        = pg_result ($res,0,'familia') ;
			$linha          = pg_result ($res,0,'linha') ;
			$cod_produto    = pg_result ($res,0,'produto') ;
		}

			$sql = "SELECT  defeito_constatado_por_familia,
						defeito_constatado_por_linha
				FROM    tbl_fabrica
				WHERE   tbl_fabrica.fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$defeito_constatado_por_familia = pg_result ($res,0,0) ;
		$defeito_constatado_por_linha   = pg_result ($res,0,1) ;
		$defeito_constatado_fabrica = "NAO";
		if ($defeito_constatado_por_familia == 't') {
			$defeito_constatado_fabrica = "SIM";
			$sql = "SELECT	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
					FROM     tbl_defeito_reclamado
					JOIN     tbl_familia USING (familia)
					WHERE    tbl_defeito_reclamado.familia = $familia
					AND      tbl_familia.fabrica           = $login_fabrica
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_exec ($con,$sql) ;
			if (pg_numrows ($resD) == 0) {
			$sql = "SELECT	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
						FROM     tbl_defeito_reclamado
						JOIN     tbl_familia USING (familia)
						WHERE    tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql) ;
			}
		}
		$row = pg_numrows ($resD); 
		$xml = "";
		if($row>0) {
			$xml = "<option value=''>Selecione o defeito reclamado</option>";
			for($i=0; $i<$row; $i++) {
				$defeito_reclamado	= pg_result($resD, $i, 'defeito_reclamado');
				$descricao			= pg_result($resD, $i, 'descricao');
				$xml .= "<option value='$defeito_reclamado'>\n";
				$xml .= $descricao."\n";
				$xml .= "</option>\n";
			}
		}
		echo $xml;
	}

	if ($tipo_busca=='defeito_constatado'){

		$defeito_reclamado = $_POST["defeito_reclamado"];
		$produto_referencia= $_POST["produto_referencia"]; 

		if (strlen($defeito_reclamado)>0){
			if (strlen($produto_referencia)>0 AND $login_fabrica==15){
				$sql="SELECT familia, 
							fabrica, 
							produto, 
							linha 
						FROM tbl_produto 
						JOIN tbl_linha USING(linha) 
						WHERE upper(referencia)=upper('$produto_referencia') 
						AND tbl_linha.fabrica = $login_fabrica
						LIMIT 1";
				if($login_fabrica==24){
					$sql="SELECT familia,
								fabrica,
								produto,
								linha
							FROM tbl_produto 
							JOIN tbl_linha USING(linha) 
							WHERE referencia like '$produto_referencia'
							LIMIT 1";
				}
				#echo $sql;
				$res = pg_exec ($con,$sql);
				#echo "numero=".pg_numrows ($res);
				if (pg_numrows ($res)>0){
					$familia        = pg_result ($res,0,'familia') ;
					$linha          = pg_result ($res,0,'linha') ;
					$cod_produto    = pg_result ($res,0,'produto') ;
				}
			}

			$sql ="SELECT 	DISTINCT(tbl_diagnostico.defeito_constatado),
							tbl_defeito_constatado.descricao
					FROM tbl_diagnostico
					JOIN tbl_defeito_constatado ON tbl_diagnostico.defeito_constatado=tbl_defeito_constatado.defeito_constatado
					WHERE 1=1";

			if($login_fabrica<>15)  {$sql.=" AND tbl_diagnostico.defeito_reclamado=$defeito_reclamado ";}
			if(strlen($linha)>0)	{$sql.=" AND tbl_diagnostico.linha=$linha";}
			if(strlen($familia)>0)	{$sql.=" AND tbl_diagnostico.familia=$familia";}
		
			$sql .=" ORDER BY tbl_defeito_constatado.descricao";

			$resD = pg_exec ($con,$sql) ;
			$row = pg_numrows ($resD);

			$xml="";
			if($row>0) {
				$xml = "<option value=''>Selecione o defeito constatado</option>";
				for($i=0; $i<$row; $i++) {  
					$def_conta = pg_result($resD, $i, 'defeito_constatado');
					$descricao = crossUrlDecode(pg_result($resD, $i, 'descricao'));
					$xml .= "<option value='$def_conta'>\n";
					$xml .= "$descricao\n";
					$xml .= "</option>\n";
				}
			}else{
				$xml = "<option value=''>Nenhum defeito constatado encontrado</option>";
			}
			echo $xml;
		}
	}
	if ($tipo_busca=='solucao'){

		$defeito_constatado = $_POST["defeito_constatado"]; 
		$defeito_reclamado	= $_POST["defeito_reclamado"]; 
		$produto_referencia = $_POST["produto_referencia"]; 

		if (strlen($produto_referencia)>0){
			$sql="SELECT familia, 
						fabrica, 
						produto, 
						linha 
					FROM tbl_produto 
					JOIN tbl_linha USING(linha) 
					WHERE upper(referencia)=upper('$produto_referencia') 
					AND tbl_linha.fabrica = $login_fabrica
					LIMIT 1";
			if($login_fabrica==24){
				$sql="SELECT familia,
							fabrica,
							produto,
							linha
						FROM tbl_produto 
						JOIN tbl_linha USING(linha) 
						WHERE referencia like '$produto_referencia'
						LIMIT 1";
			}
			#echo $sql;
			$res = pg_exec ($con,$sql);
			#echo "numero=".pg_numrows ($res);
			if (pg_numrows ($res)>0){
				$familia        = pg_result ($res,0,'familia') ;
				$linha          = pg_result ($res,0,'linha') ;
				$cod_produto    = pg_result ($res,0,'produto') ;
			}
		}

		if($login_fabrica <> 15){
			$sql ="SELECT DISTINCT(tbl_diagnostico.solucao), 
						tbl_solucao.descricao 
						FROM tbl_diagnostico 
						JOIN tbl_solucao on tbl_diagnostico.solucao = tbl_solucao.solucao 
						WHERE tbl_diagnostico.defeito_constatado = $defeito_constatado 
						AND   tbl_diagnostico.defeito_reclamado = $defeito_reclamado 
						AND   tbl_diagnostico.linha=$linha";
			if(strlen($familia)>0){$sql.=" and tbl_diagnostico.familia=$familia";}
			$sql .=" ORDER BY tbl_solucao.descricao";
		}else{
			$sql ="SELECT DISTINCT (tbl_solucao.descricao),
							tbl_diagnostico.solucao
						FROM tbl_diagnostico
						JOIN tbl_solucao on tbl_diagnostico.solucao = tbl_solucao.solucao 
						WHERE tbl_diagnostico.defeito_constatado = $defeito_constatado 
						AND   tbl_diagnostico.linha = $linha
						AND   tbl_diagnostico.familia = $familia
						ORDER BY tbl_solucao.descricao";
		}

		$resD = pg_exec ($con,$sql) ;
		$row = pg_numrows ($resD);
		if($row>0) {
			$xml = "<option value=''>Selecione a solução</option>";
			for($i=0; $i<$row; $i++) {  
				$solucao    = pg_result($resD, $i, 'solucao'); 
				$descricao  = crossUrlDecode(pg_result($resD, $i, 'descricao'));
				$xml .= "<option value='$solucao'>\n";
				$xml .= "$descricao\n";
				$xml .= "</option>\n";
			}
		}else{
				$xml = "<option value=''>Nenhum defeito constatado encontrado</option>";
		}
		echo $xml;
	}
	exit;
} // fim do AJAX de defeito reclamado, constatado e solução;

if($_POST["pegaMascara"]=="sim"){
	$referencia = $_POST["produto_referencia"];
	if (strlen($referencia)>0){
		$sql = "SELECT linha 
			FROM tbl_produto 
			JOIN tbl_linha USING(linha) 
			WHERE fabrica  = $login_fabrica 
			AND referencia ='$referencia' ";
		$res = pg_exec($con,$sql);
		$linha = pg_result ($res,0,0);
		if($linha==3 AND $login_fabrica==3){
			echo "Mascara: LLNNNNNNLNNL<br>L: Letra<BR>N: Número";
		}
	}
	exit;
}

$btn_acao = $_POST['btn_acao'];

#############################################################################################################
####################                  INICIO DA GRAVAÇÃO                         ############################
#############################################################################################################

if ($btn_acao == "gravar") {

	//echo "Gravando";

	$os						= trim($_POST['os']);
	$sua_os					= trim($_POST['sua_os']);
	$imprimir_os			= trim($_POST["imprimir_os"]);
	$sua_os_offline			= trim($_POST['sua_os_offline']);
	$os_offline				= trim($_POST['os_offline']);
	$locacao				= trim($_POST["locacao"]);
	$tipo_atendimento		= trim($_POST['tipo_atendimento']);
	$xdata_abertura			= fnc_formata_data_pg(trim($_POST['data_abertura']));

	$xconsumidor_nome		= trim($_POST['consumidor_nome']);
	$xconsumidor_cpf		= trim($_POST['c16/8/2007r_cpf']);
	$xconsumidor_cidade		= trim($_POST['consumidor_cidade']);
	$xconsumidor_estado		= trim($_POST['consumidor_estado']);
	$xconsumidor_fone		= trim($_POST['consumidor_fone']);
	$xconsumidor_endereco	= trim($_POST['consumidor_endereco']);
	$xconsumidor_numero		= trim($_POST['consumidor_numero']);
	$xconsumidor_complemento= trim($_POST['consumidor_complemento']) ;
	$xconsumidor_bairro		= trim($_POST['consumidor_bairro']) ;
	$xconsumidor_cep		= trim($_POST['consumidor_cep']) ;
	$xcontrato				= trim($_POST['consumidor_contrato']);

	$revenda_cnpj			= trim($_POST['revenda_cnpj']);
	$xrevenda_nome			= trim($_POST['revenda_nome']);
	$xrevenda_fone			= trim($_POST['revenda_fone']);
	$xrevenda_cep			= trim($_POST['revenda_cep']);
	$xrevenda_bairro		= trim($_POST['revenda_bairro']);
	$xrevenda_complemento	= trim($_POST['revenda_complemento']);
	$xrevenda_numero		= trim($_POST['revenda_numero']);
	$xrevenda_endereco		= trim($_POST['revenda_endereco']);
	$xrevenda_cidade		= trim($_POST['revenda_cidade']);
	$xrevenda_estado		= trim($_POST['revenda_estado']);
	
	$xnota_fiscal			= trim($_POST['nota_fiscal']);
	$xdata_nf				= fnc_formata_data_pg(trim($_POST['data_nf']));

	$qtde_produtos			= trim($_POST['qtde_produtos']);
	$xtroca_faturada		= trim($_POST['troca_faturada']);
	$xproduto_serie			= trim($_POST['produto_serie']);
	$xcodigo_fabricacao		= trim($_POST['codigo_fabricacao']);
	$voltagem				= trim($_POST['produto_voltagem']);
	$xaparencia_produto		= trim($_POST['aparencia_produto']);
	$xacessorios			= trim($_POST['acessorios']);

	$xdefeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);
	$xobs					= trim($_POST['obs']);
	$xquem_abriu_chamado	= trim($_POST['quem_abriu_chamado']);
	$xconsumidor_revenda	= trim($_POST['consumidor_revenda']);
	$xsatisfacao			= trim($_POST['satisfacao']);
	$xlaudo_tecnico			= trim($_POST['laudo_tecnico']);
	$defeito_reclamado		= trim($_POST['defeito_reclamado']);
	$defeito_constatado		= trim($_POST['defeito_constatado']);
	$data_fechamento		= trim($_POST['data_fechamento']);


	$cartao_clube			= trim($_POST['cartao_clube']);


	if (strlen($sua_os_offline) == 0) {
		$sua_os_offline = 'null';
	}else{
		$sua_os_offline = "'" . trim ($sua_os_offline) . "'";
	}

	if (strlen($sua_os) == 0) {
		$sua_os = 'null';
		//hd 4617
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	}else{
		//ALTERAR DIA 04/01/2007 - WELLINGTON
		//hd 4617
		if ($login_fabrica <> 1 and $login_fabrica <> 11 and $login_fabrica<>3) {
			if ($login_fabrica <> 3 and strlen($sua_os) < 7) {
				$sua_os = "000000" . trim ($sua_os);
				$sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
			}

			# inserido pelo Ricardo - 04/07/2006
			//hd 4617
			/* if ($login_fabrica == 3 and $login_posto<>6359) {
				if (is_numeric($sua_os)) {
					// retira os ZEROS a esquerda
					$sua_os = intval(trim($sua_os));
				}
			}
			*/

#			if (strlen($sua_os) > 6) {
#				$sua_os = substr ($sua_os, strlen ($sua_os) - 6 , 6) ;
#			}
#  CUIDADO para OS de Revenda que já vem com = "-" e a sequencia.
#  fazer rotina para contar 6 caracteres antes do "-"
		}
		$sua_os = "'" . $sua_os . "'" ;
	}


	##### INÍCIO DA VALIDAÇÃO DOS CAMPOS #####

	if (strlen($locacao) > 0) {
		$x_locacao = "7";
	}else{
		$x_locacao = "null";
	}

	if (strlen (trim ($tipo_atendimento)) == 0){
		$tipo_atendimento = 'null';
	}

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


	if ($xdata_abertura == 'null'){
		$msg_erro .= " Digite a data de abertura da OS.";
	}

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
######################## CONSUMIDOR ##########################
##############################################################
	
	if($login_fabrica==6){
		if (strlen($consumidor_nome) == 0) {
			$msg_erro .= " Digite o nome do consumidor. <br>";
		}else{
			$xconsumidor_nome = "'".str_replace("'","",$consumidor_nome)."'"; 
		}
	}else{
		if (strlen($consumidor_nome) == 0){
			$xconsumidor_nome = 'null';
		}
		else{
			$xconsumidor_nome = "'".str_replace("'","",$consumidor_nome)."'";
		}
	}

	$xconsumidor_cpf = str_replace("-","",$xconsumidor_cpf);
	$xconsumidor_cpf = str_replace(".","",$xconsumidor_cpf);
	$xconsumidor_cpf = str_replace("/","",$xconsumidor_cpf);
	$xconsumidor_cpf = str_replace(" ","",$xconsumidor_cpf);
	$xconsumidor_cpf = trim(substr($xconsumidor_cpf,0,14));
	if (strlen($xconsumidor_cpf) == 0){
		$xconsumidor_cpf = 'null';
	}else{
		$xconsumidor_cpf = "'".$xconsumidor_cpf."'";
	}

	if (strlen($xconsumidor_cidade) == 0){
		$xconsumidor_cidade = 'null';
	}else{
		$xconsumidor_cidade = "'".$xconsumidor_cidade."'";
	}

	if (strlen($xconsumidor_estado) == 0){
		$xconsumidor_estado = 'null';
	}else{
		$xconsumidor_estado = "'".$xconsumidor_estado."'";
	}

	if (strlen($xconsumidor_fone) == 0){
		$xconsumidor_fone = 'null';
	}else{
		$xconsumidor_fone = "'".$xconsumidor_fone."'";
	}

	if ($login_fabrica==14 AND $xconsumidor_fone=='null'){
		$msg_erro .= " Digite o telefone do consumidor.<br>";
	}
	if ($login_fabrica==14 AND $xconsumidor_cidade=='null'){
		$msg_erro .= " Digite a cidade do consumidor.<br>";
	}

	if ($login_fabrica==14 AND $xconsumidor_estado=='null'){
		$msg_erro .= " Digite o estado do consumidor.<br>";
	}

	
	if ($login_fabrica == 2 || $login_fabrica == 1) {
		if (strlen($xconsumidor_endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br>";
	}

	if ($login_fabrica == 1) {
		if (strlen($xconsumidor_numero) == 0) {
			$msg_erro .= " Digite o número do consumidor. <br>";
		}
		if (strlen($xconsumidor_bairro) == 0){
			$msg_erro .= " Digite o bairro do consumidor. <br>";
		}
	}

	if (strlen($xconsumidor_complemento) == 0) {
		$xconsumidor_complemento = "null";
	}else{
		$xconsumidor_complemento = "'" . $xconsumidor_complemento . "'";
	}

	if($xcontrato == 't'){
		$contrato = 't';
	}else{
		$contrato = 'f';
	}

	$xconsumidor_cep = str_replace (".","",$xconsumidor_cep);
	$xconsumidor_cep = str_replace ("-","",$xconsumidor_cep);
	$xconsumidor_cep = str_replace ("/","",$xconsumidor_cep);
	$xconsumidor_cep = str_replace (",","",$xconsumidor_cep);
	$xconsumidor_cep = str_replace (" ","",$xconsumidor_cep);
	$xconsumidor_cep = substr ($xconsumidor_cep,0,8);

	if (strlen(trim($xconsumidor_cep)) == 0){
		$xconsumidor_cep = "null";
	}else{
		$xconsumidor_consumidor_cep = "'" . $cep . "'";
	}
	##takashi 02-09

#########################################################
#################### REVENDA ############################
#########################################################

	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace(" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);

	if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14){
		$msg_erro .= " Tamanho do CNPJ da revenda inválido.<BR>";
	}

	if (strlen($revenda_cnpj) == 0){
		if ($login_fabrica==11 OR $login_fabrica==3){	/*takashi HD 931  21-12*/ // modificado por Fabio 15/08/2007
			$msg_erro .= " Insira o CNPJ da Revenda.<BR>";
		}else{
			$xrevenda_cnpj = 'null';
		}
	}else{
		$xrevenda_cnpj = "'".$revenda_cnpj."'";
	}

	if (strlen($xrevenda_nome) == 0){
		if ($login_fabrica==14){
			$xrevenda_nome = "NULL";
		}else{
			$msg_erro .= " Digite o nome da revenda. <br>";
		}
	}else{
		$xrevenda_nome = "'".str_replace("'","",$xrevenda_nome)."'";
	}

	if (strlen($xrevenda_fone) == 0) {
		$xrevenda_fone = 'null';
	}else {
		$xrevenda_fone = "'".str_replace("'","",$xrevenda_fone)."'";
	}
	
	$xrevenda_cep = str_replace (".","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("-","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("/","",$xrevenda_cep);
	$xrevenda_cep = str_replace (",","",$xrevenda_cep);
	$xrevenda_cep = str_replace (" ","",$xrevenda_cep);
	$xrevenda_cep = substr ($xrevenda_cep,0,8);
	
	if (strlen($xrevenda_cep) == 0){
		$xrevenda_cep = "null";
	}else{
		$xrevenda_cep = "'" . $xrevenda_cep . "'";
	}
	
	if (strlen($xrevenda_endereco) == 0){
		$xrevenda_endereco = 'null';
	}else {
		$xrevenda_endereco = "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";
	}

	if (strlen($xrevenda_numero) == 0) {
		$xrevenda_numero = 'null';
	}else {
		$xrevenda_numero = "'".str_replace("'","",$xrevenda_numero)."'";
	}

	if (strlen($xrevenda_complemento) == 0) {
		$xrevenda_complemento = 'null';
	}else {
		$xrevenda_complemento = "'".str_replace("'","",$xrevenda_complemento)."'";
	}

	if (strlen($xrevenda_bairro) == 0) {
		$xrevenda_bairro = 'null';
	}else {
		$xrevenda_bairro = "'".str_replace("'","",$xrevenda_bairro)."'";
	}

	if (strlen($xrevenda_cidade) == 0) {
		if ($login_fabrica==14){
			$xrevenda_cidade='null';
		}else{
			$msg_erro .= " Digite a cidade da revenda. <br>";
		}
	}else{
		$xrevenda_cidade = "'".str_replace("'","",$xrevenda_cidade)."'";
	}
	
	if (strlen($xrevenda_estado) == 0){
		if ($login_fabrica==14){
			$xrevenda_estado='null';
		}else{
			$msg_erro .= " Digite o estado da revenda. <br>";
		}
	}else{
		$xrevenda_estado = "'".str_replace("'","",$xrevenda_estado)."'";	
	}

	if (strlen($xnota_fiscal) == 0){
		if (($login_fabrica == 14) or ($login_fabrica == 6) or ($login_fabrica == 24) or ($login_fabrica == 11)){
			$msg_erro .= "Digite o número da nota fiscal.";
		}else{
			$xnota_fiscal = 'null';
		}
	}else{
		$xnota_fiscal = "'".$xnota_fiscal."'";
	}

############################## // FIM DA REVENDA

	################ QTDR PRODUTOS
	if (strlen ($qtde_produtos) == 0){
		$qtde_produtos = "1";
	}

	################ TROCA FATURADA
	if (strlen ($xtroca_faturada) == 0){
		$xtroca_faturada = 'null';
	}else{
		$xtroca_faturada = "'".$xtroca_faturada."'";
	}

	################ NOTA FISCAL 
	//pedido por Leandro Tectoy, feito por takashi 04/08
	if(($login_fabrica==6) or ($login_fabrica == 24) or ($login_fabrica == 11)){
		if (strlen($xdata_nf)== 0){
			$msg_erro .= " Digite a data de compra.";
		}
	}
	//pedido por Leandrot tectoy, feito por takashi 04/08
	if ($xdata_nf == null AND $xtroca_faturada <> 't'){
		$msg_erro .= " Digite a data de compra.";
	}
	
	################ NUMERO SERIE 
	if (strlen($xproduto_serie) == 0){
		if ($login_fabrica==11) {
			$msg_erro .= " Digite o Número de Série.<BR>";
		}else{
			$xproduto_serie = 'null';
		}
	}else{
		$xproduto_serie = "'". strtoupper($xproduto_serie) ."'";
	}
	
	################ CODIGO FABRICAÇÃO 
	if (strlen($xcodigo_fabricacao) == 0){
		$xcodigo_fabricacao = 'null';
	}else{
		$xcodigo_fabricacao = "'".$xcodigo_fabricacao."'";
	}

	################ PRODUTO APARENCIA 
	if (strlen($xaparencia_produto) == 0){
		if($login_fabrica==6){//pedido leandro tectoy
			$msg_erro .= " Digite a aparencia do produto.<BR>";
		}else{
			$xaparencia_produto = 'null';
		}
	}else{
		$xaparencia_produto = "'".$xaparencia_produto."'";
	}

	################ PRODUTO VOLTAGEM
	if (strlen($voltagem) == 0){
		$voltagem = "null";
	}else{
		$voltagem = "'".$voltagem."'";
	}

	################ PRODUTO ACESSÓRIO
	if (strlen($xacessorios) == 0){
		if($login_fabrica==6){ //pedido leandro tectoy	
			$msg_erro .= " Digite os acessorios do produto.<BR>";
		}else{
			$xacessorios = 'null';
		}
	}else{
		$xacessorios = "'".$xacessorios."'";
	}
	
	######### DEFEITO RECLAMADO DESCRIÇÃO
	if (strlen($xdefeito_reclamado_descricao) == 0){
		$xdefeito_reclamado_descricao = 'null';
	}else{
		$xdefeito_reclamado_descricao = "'".$xdefeito_reclamado_descricao."'";
	}

	######### OBS
	if (strlen($xobs) == 0){
		$xobs = 'null';
	}else{
		$xobs = "'".$xobs."'";
	}

	######### QUEM ABRIU O CHAMADO
	if (strlen($xquem_abriu_chamado) == 0){
		$xquem_abriu_chamado = 'null';
	}else{
		$xquem_abriu_chamado = "'".$xquem_abriu_chamado."'";
	}

	######### CONSUMIDOR REVENDA
	if (strlen($xconsumidor_revenda) == 0){
		$msg_erro .= " Selecione consumidor ou revenda.";
	}else{
		$xconsumidor_revenda = "'".$xconsumidor_revenda."'";
	}

	######### SATISFAÇÃO
	if (strlen($xsatisfacao) == 0){
		$xsatisfacao = "'f'";
	}else{
		$xsatisfacao = "'".$xsatisfacao."'";
	}

	######### LAUDO TECNICO
	if (strlen ($xlaudo_tecnico) == 0){
		$xlaudo_tecnico = 'null';
	}else{
		$xlaudo_tecnico = "'".$xlaudo_tecnico."'";
	}
	
	######### DEFEITO RECLAMADO
	if (strlen ($defeito_reclamado) == 0 AND $login_fabrica == 95) {
		$defeito_reclamado = "null";
	}else{
		if (strlen($defeito_reclamado) == 0 and $login_fabrica <> 95) {
			$msg_erro .= "Selecione o defeito reclamado.";
		}
	}
	if ($defeito_reclamado == '0' OR strlen($defeito_reclamado)==0) {
		$msg_erro .= "Selecione o defeito reclamado.<BR>";
	}
	
	
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

	//Chamado 2354
	if($login_fabrica == 15){
		if($consumidor_revenda == 'C'){
			if (strlen($xconsumidor_endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br>";
			if (strlen($xconsumidor_numero)   == 0) $msg_erro .= " Digite o número do consumidor. <br>";
			if (strlen($xconsumidor_bairro)   == 0) $msg_erro .= " Digite o bairro do consumidor. <br>";
		}
	}

	##### FIM DA VALIDAÇÃO DOS CAMPOS #####

	$os_reincidente = "'f'";

	##### Verificação se o nº de série é reincidente para a Tectoy #####
	if ($login_fabrica == 6 and 1==2) {
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
	if ($login_fabrica == 3 and $login_posto==6359) {
		$sqlX = "SELECT to_char ($xdata_abertura::date - INTERVAL '90 days', 'YYYY-MM-DD')";
		$resX = @pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);

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
			//linha 3, pois é a linha audio e video
			if (pg_numrows($res) > 0) {
				$xxxos      = trim(pg_result($res,0,os));
				$xxfinalizada   = trim(pg_result($res,0,finalizada));
				$xx_sua_os   = trim(pg_result($res,0,sua_os));
				$xxdata_fechamento =   trim(pg_result($res,0,data_fechamento));
		
				if(strlen($xxfinalizada)==0){ //aberta 
					$os_reincidente = "'t'";
					$msg_erro .= "Este Produto já possui ordem de serviço em aberto. Por favor consultar OS $xx_sua_os.";
				}else{//fechada
					if(($xxdata_fechamento > $data_inicial) and ($xxdata_fechamento < $data_final)){
						$os_reincidente = "'t'";
					}//se a data de fechamento da ultima OS estiver no periodo de 90 dias.. seta como reincidente
				}
			}
		}
	}
	/*TAKASHI 18-12 HD-854*/

	if ($login_fabrica == 7) {
		$xdata_nf = $xdata_abertura;
	}

	#if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14){ $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

	#if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

	$produto = 0;

	$sql = "SELECT tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) ";

	if ($login_fabrica == 1) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
	}
	$sql .= "	AND    tbl_linha.fabrica      = $login_fabrica
				AND    tbl_produto.ativo IS TRUE";

	$res = @pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		$msg_erro .= " Produto $produto_referencia não cadastrado";
	}else{
		$produto = @pg_result ($res,0,produto);
		$linha   = @pg_result ($res,0,linha);
	}

	############### TROCA FATURADA - BLACK
	if (strlen($msg_erro)==0 AND $xtroca_faturada <> "'t'" AND 1 == 2) { // verifica troca faturada para a Black
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
						$msg_erro = " Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
					}
				}
			}
		}
	}

	############### TIPO OS CORTESIA
	if (strlen($msg_erro)==0 AND $login_fabrica == 1) {
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



	############### OS DIGITADA PELO DISTRIBUIDOR
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

	############# CARTÃO CLUBE - LATINATEC

	$cc = 0;
	if($login_fabrica == 15 AND strlen($cartao_clube) > 0 AND strlen($msg_erro) == 0){
		$sql_5 = "SELECT cartao_clube      ,
						dt_nota_fiscal   ,
						dt_garantia
					FROM tbl_cartao_clube 
					WHERE cartao_clube = '$cartao_clube' 
					AND produto = '$produto' ; ";
		$res_5 = pg_exec($con,$sql_5);
		if(pg_numrows($res_5) > 0){
			$cc = "OK";
		}else{
			$msg_erro = "Verifique o produto do Cartão Clube com o da OS.";
		}
	}


	$res = @pg_exec($con,"BEGIN TRANSACTION");

	if (strlen ($os_offline) == 0) {
		$os_offline = "null";
	}

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
						tipo_os                     = $x_locacao                        ,
						defeito_reclamado           = $defeito_reclamado
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}

		$sql_OS = $sql;

		//echo nl2br($sql);
		 if (strlen($os) == 0) { // nao permite alterar os campos PROVISORIO
			$res = @pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0 ) {
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
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

		//CARTAO CLUBE - LATINATEC
		if($login_fabrica == 15 AND $cc == "OK"){
			$sql_cc = "UPDATE tbl_cartao_clube SET os = $os WHERE cartao_clube = '$cartao_clube' ";
			$res = pg_exec($con,$sql_cc);
			$msg_erro .= pg_errormessage($con);
		}

		$res = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro .= pg_errormessage($con);
		}

		#--------- grava OS_EXTRA ------------------
		if (strlen ($msg_erro) == 0) {
			
			//=============================== REVENDA
			//revenda_cnpj
			//if (strlen($msg_erro) == 0 AND strlen ($xrevenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 and strlen ($xrevenda_estado) > 0 ) {
			if (strlen($msg_erro) == 0 AND strlen ($revenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 and strlen ($xrevenda_estado) > 0 ) {

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

					if (strlen (pg_errormessage($con)) > 0){
						$msg_erro = pg_errormessage ($con);
					}
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

					if (strlen (pg_errormessage($con)) > 0){
						$msg_erro = pg_errormessage ($con);
					}
					$monta_sql .= "12: $sql<br>$msg_erro<br><br>";
					$sql = "SELECT currval ('seq_revenda')";
					$res3 = @pg_exec ($con,$sql);
					$revenda = @pg_result ($res3,0,0);
				}

				$sql = "UPDATE tbl_os SET revenda = $revenda WHERE os = $os AND fabrica = $login_fabrica";
				$res = @pg_exec ($con,$sql);
				$monta_sql .= "13: $sql<br>$msg_erro<br><br>";
			}

		
			$taxa_visita				= str_replace (",",".",trim($_POST['taxa_visita']));
			$visita_por_km				=                      trim($_POST['visita_por_km']);
			$hora_tecnica				= str_replace (",",".",trim($_POST['hora_tecnica']));
			$regulagem_peso_padrao		= str_replace (",",".",trim($_POST['regulagem_peso_padrao']));
			$certificado_conformidade	= str_replace (",",".",trim($_POST['certificado_conformidade']));
			$valor_diaria				= str_replace (",",".",trim($_POST['valor_diaria']));

			if (strlen($taxa_visita)				== 0) $taxa_visita					= '0';
			if (strlen($visita_por_km)				== 0) $visita_por_km				= 'f';
			if (strlen($hora_tecnica)				== 0) $hora_tecnica					= '0';
			if (strlen($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
			if (strlen($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
			if (strlen($valor_diaria)				== 0) $valor_diaria					= '0';

			$sql = "UPDATE tbl_os_extra SET
						taxa_visita              = $taxa_visita             ,
						visita_por_km            = '$visita_por_km'         ,
						hora_tecnica             = $hora_tecnica            ,
						regulagem_peso_padrao    = $regulagem_peso_padrao   ,
						certificado_conformidade = $certificado_conformidade,
						valor_diaria             = $valor_diaria ";

			if ($os_reincidente == "'t'") {
				$sql .= ", os_reincidente = $xxxos ";
			}

			$sql .= "WHERE tbl_os_extra.os = $os";
			$res = @pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0 ) {
				$msg_erro = pg_errormessage($con);
			}

			if (strlen ($msg_erro) > 0) {
				$os = "";
			}

			if (strlen ($msg_erro) == 0) {
				#$res = @pg_exec ($con,"COMMIT TRANSACTION"); // nao da comiit aki, soh quando terminar os itens

				//hd chamado 3371
				if ($login_fabrica == 1 and $pedir_sua_os == 'f') {
					$sua_os_repetiu = 't';
					while ($sua_os_repetiu == 't') {
						//veriica se esta sua_os é repetida
						$sql_sua_os = " SELECT sua_os
										FROM   tbl_os
										WHERE  fabrica =  $login_fabrica
										AND    posto   =  $login_posto
										AND    sua_os  =  (SELECT sua_os from tbl_os where os = $os)
										AND    os      <> $os";
						$res_sua_os = pg_exec($con, $sql_sua_os);

						if (pg_numrows($res_sua_os) > 0) {
							$sql_sua_os = " SELECT sua_os 
											FROM tbl_posto_fabrica
											WHERE fabrica = $login_fabrica
											AND   posto   = $login_posto";
							$res_sua_os = pg_exec($con, $sql_sua_os);
							$sua_os_atual = pg_result($res_sua_os,0,0);

							$sua_os_atual = $sua_os_atual + 1;

							$sql_sua_os = " UPDATE tbl_posto_fabrica SET sua_os = $sua_os_atual
											where  tbl_posto_fabrica.fabrica = $login_fabrica
											and    tbl_posto_fabrica.posto   = $login_posto";
							$res_sua_os = pg_exec($con, $sql_sua_os);
											
							$sql_sua_os = " UPDATE tbl_os set sua_os = lpad ($sua_os_atual,5,''0'') 
											WHERE  tbl_os.os      = $os
											and    tbl_os.fabrica = $login_fabrica";
							$res_sua_os = pg_exec($con, $sql_sua_os);
						} else {
							$sua_os_repetiu = 'f';
						}
					}
				}
				// se o produto tiver TROCA OBRIGATORIA, bloqueia a OS para intervencao da fabrica // fabio 17/01/2007 - alterado em 04/07/2007
				if ($login_fabrica == 3){ # AND $login_fabrica == 11
					$sql = "SELECT  troca_obrigatoria
							FROM    tbl_produto
							WHERE   upper(tbl_produto.referencia) = upper($produto_referencia)";
					$res = @pg_exec($con,$sql);
					if (pg_numrows($res) > 0) {
						$troca_obrigatoria = trim(pg_result($res,0,troca_obrigatoria));
						if ($troca_obrigatoria == 't') {
							$sql_intervencao = "SELECT * 
												FROM  tbl_os_status 
												WHERE os=$os 
												AND status_os=62";
							$res_intervencao = pg_exec($con, $sql_intervencao);
							if (pg_numrows ($res_intervencao) == 0){
								$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O Produto desta O.S. necessita de troca.')";
								$res = @pg_exec ($con,$sql);
								$msg_intervencao .= "<br>A produto $produto_referencia precisa de Intervenção da Assistência Técnica da Fábrica. Aguarde o contato da fábrica";
							}
							// envia email teste para avisar
							$email_origem  = "fabio@telecontrol.com.br";
							$email_destino = "fabio@telecontrol.com.br";
							$assunto       = "TROCA OBRIGATORIA - OS CADASTRO TUDO cadastrada";
							$corpo ="OS: $os \n";
							@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); 
							// fim
						}
					}
				}// fim TROCA OBRIGATORIA

				if ($imprimir_os == "imprimir") {
					#header ("Location: os_item_new.php?os=$os&imprimir=1");
					#exit;
				}else{
					#header ("Location: os_item_new.php?os=$os");
					#exit;
				}
				/*header ("Location: os_item_new.php?os=$os");
				exit;*/
			}else{
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
				$os = "";
			}
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$os = "";
		}

	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

		if (strpos ($msg_erro,"tbl_os_unico") > 0)
			$msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";

		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$os = "";
	}

	######### FIM DA VALIDAÇÃO DA OS ########
	##########################################################################################
	########## VALIDAÇÂO DOS ITENS ##########

	if (strlen($msg_erro)==0){
		$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$pedir_causa_defeito_os_item		= trim(pg_result ($res,0,pedir_causa_defeito_os_item));
		$pedir_defeito_constatado_os_item	= trim(pg_result ($res,0,pedir_defeito_constatado_os_item));
		$ip_fabricante						= trim(pg_result ($res,0,ip_fabricante));
		$ip_acesso							= $_SERVER['REMOTE_ADDR'];
		$os_item_admin						= "null";
		$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
		$res1 = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($data_fechamento) > 0){
		$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
		if($xdata_fechamento > "'".date("Y-m-d")."'") {
			$msg_erro = "Data de fechamento maior que a data de hoje.";
		}
	}

	if(($login_fabrica==3) or ($login_fabrica==6) or ($login_fabrica == 24)){
		if(strlen($defeito_constatado)==0){
			$msg_erro .= "Por favor preencher o campo defeito constatado.<BR>";
		}
		if(strlen($solucao_os)==0){
			$msg_erro .= "Por favor preencher o campo solução.<BR>";
		}
	}
	if($login_fabrica==24){
		$produto_serie = trim($_POST['produto_serie']);
			$sql = "UPDATE tbl_os set serie = '$produto_serie' 
					WHERE os = $os 
					AND posto = $login_posto";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

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

	if (strlen ($msg_erro) == 0) {
		if (strlen($defeito_constatado) == 0) $defeito_constatado = 'null';

		if (strlen ($defeito_constatado) > 0) {
			$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $login_posto;";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
/*		//CASO DEFEITO RECLAMADO ESTEJA VAZIO

		$defeito_reclamado = $_POST['defeito_reclamado'];
		
		if (strlen($defeito_reclamado) == 0 ) $msg_erro = "Informe o defeito reclamado.";
		if (strlen ($defeito_reclamado) > 0) {
				$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado
						WHERE  tbl_os.os    = $os
						AND    tbl_os.posto = $login_posto;";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
		}
		//CASO DEFEITO RECLAMADO ESTEJA VAZIO
*/
	}

	
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
	if (strlen($obs) > 0){
		$obs = "'".$obs."'";
	}else{
		//takashi 07-08 a pedido do andre da tectoy o campo observação passa a ser obrigatorio
		if($login_fabrica==6){
			if(strlen($obs)==0){
				$msg_erro .= "Por favor preencher o campo Observação<BR>";
			}
		}else{
			$obs = "null";
		}
	}
	
	$tecnico_nome = trim($_POST["tecnico_nome"]);
	if (strlen($tecnico_nome) > 0){
		$tecnico_nome = "'".$tecnico_nome."'";
	}else{
		$tecnico_nome = "null";
	}

	$valores_adicionais = trim($_POST["valores_adicionais"]);
	$valores_adicionais = str_replace (",",".",$valores_adicionais);

	if (strlen($valores_adicionais) == 0){
		$valores_adicionais = "0";
	}

	$justificativa_adicionais = trim($_POST["justificativa_adicionais"]);
	if (strlen($justificativa_adicionais) > 0) {
		$justificativa_adicionais = "'".$justificativa_adicionais."'";
	}else{
		$justificativa_adicionais = "null";
	}

	$qtde_km = trim($_POST["qtde_km"]);
	$qtde_km = str_replace (",",".",$qtde_km);

	if (strlen($qtde_km) == 0) {
		$qtde_km = "0";
	}

	$sql = "UPDATE	tbl_os
				SET obs                      = $obs                     , 
					tecnico_nome             = $tecnico_nome            ,
					qtde_km                  = $qtde_km                 ,
					valores_adicionais       = $valores_adicionais      ,
					justificativa_adicionais = $justificativa_adicionais
			WHERE  tbl_os.os    = $os
			AND    tbl_os.posto = $login_posto;";
	$res = @pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	$type = trim($_POST['type']);
	if (strlen($type) > 0){
		$type = "'".$type."'";
	}else{
		$type = 'null';
	}

	$sql = "UPDATE tbl_os
			SET    type = $type
			WHERE  tbl_os.os    = $os
			AND    tbl_os.posto = $login_posto;";
	$res = @pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);


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

//CONTROLE LATINATEC
	$latina_troca_peca = 'f';

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

			$xproduto = str_replace ("." , "" , $xproduto);
			$xproduto = str_replace ("-" , "" , $xproduto);
			$xproduto = str_replace ("/" , "" , $xproduto);
			$xproduto = str_replace (" " , "" , $xproduto);

			$xpeca    = str_replace ("." , "" , $xpeca);
			$xpeca    = str_replace ("-" , "" , $xpeca);
			$xpeca    = str_replace ("/" , "" , $xpeca);
			$xpeca    = str_replace (" " , "" , $xpeca);

			if (strlen($xserie) == 0){
				$xserie = 'null';
			}else{
				$xserie = "'" . $xserie . "'";
			}

			if (strlen($xposicao) == 0){
				$xposicao = 'null';
			}else{
				$xposicao = "'" . $xposicao . "'";
			}

			$xadmin_peca      = $_POST["admin_peca_"     . $i]; 
			if(strlen($xadmin_peca)==0){
				$xadmin_peca ="null"; 
			}
			if($xadmin_peca=="P"){
				$xadmin_peca ="null";
			}
			
/*			if ($login_fabrica == 5 and strlen($causa_defeito) == 0)
				$msg_erro = "Selecione a causa do defeito";
			elseif ($login_fabrica <> 5 and strlen($causa_defeito) == 0)
				$causa_defeito = 'null';*/

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
							 WHERE referencia_pesquisa = UPPER('$xpeca')
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
									$xpeca                    = pg_result ($res,0,peca);
									$intervencao_fabrica_peca = pg_result ($res,0,retorna_conserto);
									$troca_obrigatoria_peca   = pg_result ($res,0,troca_obrigatoria);
									$bloqueada_garantia_peca  = pg_result ($res,0,bloqueada_garantia);
									$previsao_entrega_peca    = pg_result ($res,0,previsao_entrega);
								}
								if (1==2){
									if ($login_fabrica==11 AND strlen($previsao_entrega_peca)>0){
										$sqlInter = "SELECT '$previsao_entrega_peca' - data_abertura
													FROM tbl_os 
													WHERE os = $os";
										$resInter = pg_exec($con, $sqlInter);
										$qtde_dias_previsao = pg_result($resInter,0,0);
										if ($qtde_dias_previsao >= 15 ) {
											$intervencao_previsao='t';
											$gravou_peca="sim";
										}
									}
								}
								if ($login_fabrica==6){//HD 3475
									$ssql = "SELECT (current_date - data_abertura) as dias from tbl_os where os=$os";
									$ress = pg_exec($con, $ssql);
										if(pg_numrows($ress)>0){
											if(pg_result($ress,0,0)>40)$msg_erro = "PARA SOLICITAÇÃO DE PEÇA DESTA ORDEM DE SERVIÇO, FAVOR ENTRAR EM CONTATO COM O DEPTO. TÉCNICO - TEC TOY";
										}

								}
								if (strlen($xdefeito) == 0){
									$msg_erro .= "Favor informar o defeito da peça"; #$defeito = "null";
								}
								if (strlen($xservico) == 0){
									$msg_erro .= "Favor informar o serviço realizado"; #$servico = "null";
								}

								//if ($login_fabrica == 5 and strlen($xcausa_defeito) == 0) $msg_erro = "Selecione a causa do defeito.";
								//elseif(strlen($xcausa_defeito) == 0)					$xcausa_defeito = 'null';

								if(strlen($xpcausa_defeito) == 0){
									$xpcausa_defeito = 'null';
								}

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
													admin
												)VALUES(
													$xos_produto    ,
													$xposicao       ,
													$xpeca          ,
													$xqtde          ,
													$xdefeito       ,
													$xpcausa_defeito,
													$xservico       ,
													$xadmin_peca
											);";
										$res = @pg_exec ($con,$sql);
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
													admin             = $xadmin_peca
												WHERE os_item = $xos_item;";
										$res = @pg_exec ($con,$sql);
										$msg_erro .= pg_errormessage($con);
									}

									if($login_fabrica == 15){
										$sql_t = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = $login_fabrica and servico_realizado = $xservico and troca_de_peca is true; ";
										$res_t = pg_exec($con,$sql_t);
										$latina_troca_peca = @pg_result($res_t,0,0);
										if(pg_numrows($res_t) > 0){
											$latina_troca_peca = 't';
										}
									}

									if (strlen ($msg_erro) > 0) {
										break ;
									}

									// BRITANIA - se a peça estiver como bloqueada garantia, a os é cadastrada, a peça tbm, mas o pedido da peça nao eh feito. Somente após a autorizacao o pedido da peça eh feioto. -- Fabio 07/03/2007
									if (($login_fabrica==3  AND $xservico=="20" AND $bloqueada_garantia_peca=='t') 
										OR ($login_fabrica==11 AND $xservico=="61" AND $bloqueada_garantia_peca=='t')){ 
										// envia email teste para avisar
										$os_bloqueada_garantia ='t';
										$msg_intervencao .= "<br>O pedido da peça $xpeca precisa de análise antes do envio. Aguarde o contato da fábrica";	
										$gravou_peca="sim";
									}
									// BRITANIA - se a PEÇA tiver intervencao da fabrica e for troca de peca gerando pedido, alterar status da OS para Intervenção da Assistência Técnica da Fábrica PENDENTE ( tbl_status_os -> 62)
									elseif 
										((($login_fabrica==3  AND $xservico=="20")OR($login_fabrica==11  AND $xservico=="61")) 
										AND ($intervencao_fabrica_peca=='t' OR $troca_obrigatoria_peca=='t') ){ // 20 trocar depois
												$os_com_intervencao='t';
												$gravou_peca="sim";
									}
								}
							}
						}
					}
				}
			}
		}
		if($login_fabrica == 6){ //HD 2599
			$pre_total = $_POST['pre_total'];
		
			for ($i = 0 ; $i < $pre_total ; $i++) {
			$pre_peca = $_POST['pre_peca_'.$i];
				if(strlen($pre_peca)>0){
				//echo "<BR>$pre_peca";
				$pre_defeito = $_POST['pre_defeito_'.$i];
				$pre_servico = $_POST['pre_servico_'.$i];
				$pre_qtde    = $_POST['pre_qtde_'   .$i];
				//echo "<BR>$pre_defeito";
				//echo "<BR>$pre_servico";
				if(strlen($pre_defeito)== 0)$msg_erro .= "Favor informar o defeito da peça<BR>";
				if(strlen($pre_servico)== 0)$msg_erro .= "Favor informar o serviço realizado<BR>"; 

				$sql = "select produto from tbl_os where os=$os and fabrica = $login_fabrica";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$pre_produto = pg_result($res,0,0);
				}
				if(strlen($msg_erro)==0){
						$sql = "INSERT INTO tbl_os_produto (
										os     ,
										produto
									)VALUES(
										$os     ,
										$pre_produto
								);";
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							//echo "1- ".$sql; exit;
							$res = pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
							$xos_produto  = pg_result ($res,0,0);
				}
				if (strlen ($msg_erro) == 0) {
						$sql = "INSERT INTO tbl_os_item (
									os_produto        ,
									peca              ,
									qtde              ,
									defeito           ,
									servico_realizado 
								)VALUES(
									$xos_produto    ,
									$pre_peca       ,
									$pre_qtde       ,
									$pre_defeito    ,
									$pre_servico    
							);";
						$res = @pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
						//echo "2- ".$sql;
				}
				}
			}
		}
	}

	if($login_fabrica == 15){
		$sql_t = "SELECT solucao FROM tbl_solucao WHERE fabrica = $login_fabrica and descricao ilike 'troca de peça%' AND solucao = $solucao_os ; ";
		$res_t = @pg_exec($con,$sql_t);

		if(pg_numrows($res_t) > 0 AND $latina_troca_peca == 'f'){
			$msg_erro = "Especificar a peça que foi feita a troca. O serviço deve ser troca de peça.";
		}
	}


	if (strlen ($msg_erro) == 0) {
	//echo "FAZZ-validacao ";
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res      = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		//$msg_erro .= "SELECT fn_valida_os_item($os, $login_fabrica)";
		if (strlen($data_fechamento) > 0){
			if (strlen ($msg_erro) == 0) {
					$sql = "UPDATE tbl_os SET data_fechamento   = $xdata_fechamento 
							WHERE  tbl_os.os    = $os
							AND    tbl_os.posto = $login_posto;";
					$res = @pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
						
					$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
			}
		}
	}


	if (strlen ($msg_erro) == 0 and ($login_fabrica==3 OR $login_fabrica==11) AND $gravou_peca=="sim") {
		// quando a peça estiver sob intervenção da assistencia técnica, $os_com_intervencao==t		
		// então inseri um status 62 para bloquear a OS
		if ($os_com_intervencao=='t'){

				// envia email teste para avisar
				$sql_intervencao = "SELECT sua_os,to_char(data_digitacao,'DD/MM/YYYY') AS data_digitacao, nome
								FROM  tbl_os
								JOIN tbl_posto USING(posto)
								WHERE tbl_os.os=$os";
				$res_Y = @pg_exec ($con,$sql_intervencao);
				$y_sua_os = pg_result ($res_Y,0,sua_os);
				$y_data = pg_result ($res_Y,0,data_digitacao);
				$y_nome = pg_result ($res_Y,0,nome);
				
				if ($login_fabrica==3 and 1==2){ # manda email para Britania
					$email_origem  = "helpdesk@telecontrol.com.br";
					$email_destino = "nelson.antunes@britania.com.br";
					$assunto       = "PROCESSO MP3 - OS com intervenção cadastrada";
					$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\n OS: $y_sua_os \nData: $y_data\nPosto: $y_nome\nProduto: $xproduto\nPeca: $xpeca";
					$body_top = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";
					#@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); 
				}
				if ($login_fabrica==11 AND 1==2){ # manda email para Lenox
						# Nelson pediu para nw mandar mais email HD 2937
						$email_origem  = "helpdesk@telecontrol.com.br";
						$email_destino = "nelson@lenoxxsound.com.br";
						$assunto       = "OS com intervenção cadastrada";
						$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\n OS: $y_sua_os \nData: $y_data\nPosto: $y_nome\nProduto: $xproduto\nPeca: $xpeca";
						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";
						#@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); 
				}
				$sql_intervencao = "SELECT * 
								FROM  tbl_os_status
								WHERE os=$os
								ORDER BY data DESC LIMIT 1";
				$res_intervencao = pg_exec($con, $sql_intervencao);
				$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'Peça da O.S. com intervenção da fábrica.')";
				if (pg_numrows ($res_intervencao) == 0){	
					$res = @pg_exec ($con,$sql);
				}
				else {
					$status_os = pg_result($res_intervencao,0,status_os);
					if ($status_os!=62){
						$res = @pg_exec ($con,$sql);
					}
				}
		}
		else{
			// se a peça cadastrada estiver bloqueada para garantia, $os_bloqueada_garantia==t
			// então ele inseri o status 72 para bloquear a OS para o SAP
			if ($os_bloqueada_garantia=='t'){ //no caso se tiver peça bloqueada para garantia, deve justificar.
				$sql_intervencao = "SELECT sua_os,to_char(data_digitacao,'DD/MM/YYYY') AS data_digitacao, nome
				FROM  tbl_os
				JOIN tbl_posto USING(posto)
				WHERE tbl_os.os=$os";
				$res_Y = @pg_exec ($con,$sql_intervencao);
				$y_sua_os = pg_result ($res_Y,0,sua_os);
				$y_data = pg_result ($res_Y,0,data_digitacao);
				$y_nome = pg_result ($res_Y,0,nome);
				
				if ($login_fabrica==11){
					$sql = "SELECT 
									email_sap,
									email_assistencia
							FROM tbl_configuracao
							WHERE fabrica=$login_fabrica";
					$res_conf = pg_exec($con,$sql);
					$resultado = pg_numrows($res_conf);
					if ($resultado>0){
						$email_sap          = trim(pg_result($res_conf,0,email_sap));
						$email_assistencia  = trim(pg_result($res_conf,0,email_assistencia));
					}
					if (strlen($email_sap)>0){
						$email_origem  = "helpdesk@telecontrol.com.br";
						$email_destino = "$email_sap";
						$assunto       = "OS com Intervenção SAP ($login_posto)";
						$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\n
						OS necessita de autorização para o envio das peças\n\n
						PA: $y_nome\n
						OS $y_sua_os \n
						Data: $y_data\n
						\nO posto aguarda autorização para dar prosseguimento ao conserto do produto</b>";
						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";
						@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem);
					}
				}

				$sql_intervencao = "SELECT * 
						FROM  tbl_os_status
						WHERE os=$os
						ORDER BY data DESC LIMIT 1";
				$res_intervencao=pg_exec($con,$sql_intervencao);
		
				$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,72,current_timestamp,'Peça da OS bloqueada para garantia')";
				if (pg_numrows($res_intervencao)== 0){	
					$res = @pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
				else{
					$status_os = pg_result($res_intervencao,0,status_os);
					if ($status_os!=72){
						$res = @pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			} else {
				if ($login_fabrica==11 AND $intervencao_previsao=='t'){
				
					$sql_intervencao = "SELECT * 
										FROM  tbl_os_status
										WHERE os=$os
										ORDER BY data DESC LIMIT 1";
					$res_intervencao=pg_exec($con,$sql_intervencao);
					$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,72,current_timestamp,'Peça da OS com previsão para entrega superior a 15 dias')";
					if (pg_numrows($res_intervencao)== 0){
						$res = @pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
					else{
						$status_os = pg_result($res_intervencao,0,status_os);
						if ($status_os!="72"){
							$res = pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}elseif ($login_fabrica==3){
					$sqld = "SELECT current_date - data_abertura
							FROM tbl_os 
							WHERE os = $os";
					$resd = pg_exec($con, $sqld);
					$data_aberturax = pg_result($resd,0,0);
					if ($data_aberturax >= 30 ) {
						$sql_intervencao = "SELECT  sua_os,
													to_char(data_digitacao,'DD/MM/YYYY') AS data_digitacao, 
													nome
											FROM  tbl_os
											JOIN tbl_posto USING(posto)
											WHERE tbl_os.os=$os";
						$res_Y = @pg_exec ($con,$sql_intervencao);
						$y_sua_os = pg_result ($res_Y,0,sua_os);
						$y_data = pg_result ($res_Y,0,data_digitacao);
						$y_nome = pg_result ($res_Y,0,nome);
						
						$email_origem  = "helpdesk@telecontrol.com.br";
						$email_destino = "fabio@telecontrol.com.br";
						$assunto       = "OS INTERVENÇÃO SAP - Os cadastrada";
						$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\nOS com peça lançada a mais de 30 dias da data de abertura foi cadastrado no sistema. O posto aguarda contato para dar prosseguimento.\n\n OS: $y_sua_os \nData: $y_data\nPosto: $y_nome\n";
						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";
						//@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); 
						// fim
						$sql_intervencao = "SELECT * 
											FROM  tbl_os_status
											WHERE os=$os
											ORDER BY data DESC LIMIT 1";
						$res_intervencao=pg_exec($con,$sql_intervencao);

						$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,72,current_timestamp,'Peça da OS bloqueada para garantia')";
						if (pg_numrows($res_intervencao)== 0){	
							$res = pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							$peca_mais_30_dias='t';
						}
						else{
							$status_os = pg_result($res_intervencao,0,status_os);

							if ($status_os!=72){
								$res = pg_exec ($con,$sql);
								$msg_erro .= pg_errormessage($con);
								$peca_mais_30_dias='t';
							}
						}
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		if ($os_bloqueada_garantia=='t' OR $peca_mais_30_dias=='t' OR $os_com_intervencao=='t' OR $intervencao_previsao=='t'){ //no caso se tiver peça bloqueada para garantia, deve justificar.
			header ("Location: os_justificativa_garantia.php?os=$os");
		}
		else{
			header ("Location: os_finalizada.php?os=$os");
		}
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}



/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen ($os) > 0) {
	$sql = "SELECT	tbl_os.os                                           ,
			tbl_os.tipo_atendimento                                     ,
			tbl_os.posto                                                ,
			tbl_posto.nome                             AS posto_nome    ,
			tbl_os.sua_os                                               ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
			tbl_os.produto                                              ,
			tbl_produto.referencia                                      ,
			tbl_produto.descricao                                       ,
			tbl_produto.voltagem                                        ,
			tbl_os.serie                                                ,
			tbl_os.qtde_produtos                                        ,
			tbl_os.defeito_reclamado                                    ,
			tbl_os.defeito_constatado                                   ,
			tbl_os.solucao_os                                           ,
			tbl_os.cliente                                              ,
			tbl_os.consumidor_nome                                      ,
			tbl_os.consumidor_cpf                                       ,
			tbl_os.consumidor_fone                                      ,
			tbl_os.consumidor_cidade                                    ,
			tbl_os.consumidor_estado                                    ,
			tbl_os.consumidor_cep                                       ,
			tbl_os.consumidor_endereco                                  ,
			tbl_os.consumidor_numero                                    ,
			tbl_os.consumidor_complemento                               ,
			tbl_os.consumidor_bairro                                    ,
			tbl_os.revenda                                              ,
			tbl_os.revenda_cnpj                                         ,
			tbl_os.revenda_nome                                         ,
			tbl_os.nota_fiscal                                          ,
			to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
			tbl_os.aparencia_produto                                    ,
			tbl_os_extra.orientacao_sac                                 ,
			tbl_os_extra.admin_paga_mao_de_obra                        ,
			tbl_os.acessorios                                           ,
			tbl_os.fabrica                                              ,
			tbl_os.quem_abriu_chamado                                   ,
			tbl_os.obs                                                  ,
			tbl_os.consumidor_revenda                                   ,
			tbl_os_extra.extrato                                        ,
			tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
			tbl_os.codigo_fabricacao                                    ,
			tbl_os.satisfacao                                           ,
			tbl_os.laudo_tecnico                                        ,
			tbl_os.troca_faturada                                       ,
			tbl_os.admin                                                ,
			tbl_os.troca_garantia
			FROM	tbl_os
			JOIN	tbl_produto          ON tbl_produto.produto       = tbl_os.produto
			JOIN	tbl_posto            ON tbl_posto.posto           = tbl_os.posto
			JOIN	tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_os.fabrica
			JOIN	tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
										AND tbl_fabrica.fabrica       = $login_fabrica
			LEFT JOIN	tbl_os_extra     ON tbl_os.os                 = tbl_os_extra.os
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$os					= pg_result ($res,0,os);
		$tipo_atendimento	= pg_result ($res,0,tipo_atendimento);
		$posto				= pg_result ($res,0,posto);
		$posto_nome			= pg_result ($res,0,posto_nome);
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$produto_referencia	= pg_result ($res,0,referencia);
		$produto_descricao	= pg_result ($res,0,descricao);
		$produto_serie		= pg_result ($res,0,serie);
		$voltagem			= pg_result ($res,0,voltagem);
		$qtde_produtos		= pg_result ($res,0,qtde_produtos);
		$defeito_reclamado	= pg_result ($res,0,defeito_reclamado);
		$defeito_constatado	= pg_result ($res,0,defeito_constatado);
		$solucao_os			= pg_result ($res,0,solucao_os);

		$cliente			= pg_result ($res,0,cliente);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cpf		= pg_result ($res,0,consumidor_cpf);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_cep		= trim (pg_result ($res,0,consumidor_cep));
		$consumidor_endereco= trim (pg_result ($res,0,consumidor_endereco));
		$consumidor_numero	= trim (pg_result ($res,0,consumidor_numero));
		$consumidor_complemento	= trim (pg_result ($res,0,consumidor_complemento));
		$consumidor_bairro	= trim (pg_result ($res,0,consumidor_bairro));
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
				
		$revenda			= pg_result ($res,0,revenda);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);

		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
		$acessorios			= pg_result ($res,0,acessorios);
		$fabrica			= pg_result ($res,0,fabrica);
		$posto_codigo		= pg_result ($res,0,posto_codigo);
		$extrato			= pg_result ($res,0,extrato);
		$quem_abriu_chamado	= pg_result ($res,0,quem_abriu_chamado);
		$obs				= pg_result ($res,0,obs);
		$consumidor_revenda = pg_result ($res,0,consumidor_revenda);
		$codigo_fabricacao	= pg_result ($res,0,codigo_fabricacao);
		$satisfacao			= pg_result ($res,0,satisfacao);
		$laudo_tecnico		= pg_result ($res,0,laudo_tecnico);
		$troca_faturada		= pg_result ($res,0,troca_faturada);
		$troca_garantia		= pg_result ($res,0,troca_garantia);
		$admin_os			= trim(pg_result ($res,0,admin));

		$orientacao_sac		= pg_result ($res,0,orientacao_sac);
		$orientacao_sac		= html_entity_decode ($orientacao_sac,ENT_QUOTES);
		$orientacao_sac		= str_replace ("<br />","",$orientacao_sac);

		$admin_paga_mao_de_obra = pg_result ($res,0,admin_paga_mao_de_obra);
		
		$sql =	"SELECT tbl_os_produto.produto ,
						tbl_os_item.pedido     
				FROM    tbl_os 
				JOIN    tbl_produto using (produto)
				JOIN    tbl_posto using (posto)
				JOIN    tbl_fabrica using (fabrica)
				JOIN    tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										  AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica 
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item
				ON      tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE   tbl_os.os = $os
				AND     tbl_os.fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);

		if(pg_numrows($res) > 0){
			$produto = pg_result($res,0,produto);
			$pedido  = pg_result($res,0,pedido);
		}

		$sql = "SELECT * FROM tbl_os_extra WHERE os = $os";
		$res = pg_exec($con,$sql);
	
		if (pg_numrows($res) == 1) {
			$taxa_visita              = pg_result ($res,0,taxa_visita);
			$visita_por_km            = pg_result ($res,0,visita_por_km);
			$hora_tecnica             = pg_result ($res,0,hora_tecnica);
			$regulagem_peso_padrao    = pg_result ($res,0,regulagem_peso_padrao);
			$certificado_conformidade = pg_result ($res,0,certificado_conformidade);
			$valor_diaria             = pg_result ($res,0,valor_diaria);
		}
		
		//SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA OS
		if (strlen($consumidor_cidade)==0){
			if (strlen($cpf) > 0 OR strlen($cliente) > 0 ) {
				$sql = "SELECT
						tbl_cliente.cliente,
						tbl_cliente.nome,
						tbl_cliente.endereco,
						tbl_cliente.numero,
						tbl_cliente.complemento,
						tbl_cliente.bairro,
						tbl_cliente.cep,
						tbl_cliente.rg,
						tbl_cliente.fone,
						tbl_cliente.contrato,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado
						FROM tbl_cliente
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE 1 = 1";
				if (strlen($cpf) > 0) $sql .= " AND tbl_cliente.cpf = '$cpf'";
				if (strlen($cliente) > 0) $sql .= " AND tbl_cliente.cliente = '$cliente'";

				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) == 1) {
					$consumidor_cliente     = trim (pg_result ($res,0,cliente));
					$consumidor_fone        = trim (pg_result ($res,0,fone));
					$consumidor_nome        = trim (pg_result ($res,0,nome));
					$consumidor_endereco    = trim (pg_result ($res,0,endereco));
					$consumidor_numero      = trim (pg_result ($res,0,numero));
					$consumidor_complemento = trim (pg_result ($res,0,complemento));
					$consumidor_bairro      = trim (pg_result ($res,0,bairro));
					$consumidor_cep         = trim (pg_result ($res,0,cep));
					$consumidor_rg          = trim (pg_result ($res,0,rg));
					$consumidor_cidade      = trim (pg_result ($res,0,cidade));
					$consumidor_estado      = trim (pg_result ($res,0,estado));
					$consumidor_contrato    = trim (pg_result ($res,0,contrato));
				}
			}
		}

		if (strlen($revenda)>0){
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
							WHERE tbl_revenda.revenda = $revenda";
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
}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0 and $btn_troca <> "trocar") {

	$os                 = $_POST['os'];
	$tipo_atendimento   = $_POST['tipo_atendimento'];
	$sua_os             = $_POST['sua_os'];
	$data_abertura      = $_POST['data_abertura'];
	$cliente            = $_POST['cliente'];
	$consumidor_nome    = $_POST['consumidor_nome'];
	$consumidor_cpf     = $_POST['consumidor_cpf'];
	$consumidor_fone    = $_POST['consumidor_fone'];
	$revenda            = $_POST['revenda'];
	$revenda_cnpj       = $_POST['revenda_cnpj'];
	$revenda_nome       = $_POST['revenda_nome'];
	$nota_fiscal        = $_POST['nota_fiscal'];
	$data_nf            = $_POST['data_nf'];
	$produto_referencia = $_POST['produto_referencia'];
	$cor                = $_POST['cor'];
	$acessorios         = $_POST['acessorios'];
	$aparencia_produto  = $_POST['aparencia_produto'];
	$obs                = $_POST['obs'];
	$orientacao_sac     = $_POST['orientacao_sac'];
	$consumidor_revenda = $_POST['consumidor_revenda'];
	$qtde_produtos      = $_POST['qtde_produtos'];
	$produto_serie      = $_POST['produto_serie'];
	$voltagem           = $_POST['voltagem'];

	$codigo_fabricacao  = $_POST['codigo_fabricacao'];
	$satisfacao         = $_POST['satisfacao'];
	$laudo_tecnico      = $_POST['laudo_tecnico'];
	$troca_faturada     = $_POST['troca_faturada'];

	$quem_abriu_chamado       = $_POST['quem_abriu_chamado'];
	$taxa_visita              = $_POST['taxa_visita'];
	$visita_por_km            = $_POST['visita_por_km'];
	$hora_tecnica             = $_POST['hora_tecnica'];
	$regulagem_peso_padrao    = $_POST['regulagem_peso_padrao'];
	$certificado_conformidade = $_POST['certificado_conformidade'];
	$valor_diaria             = $_POST['valor_diaria'];

	$sql =	"SELECT descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia = UPPER ('$produto_referencia')
			AND     tbl_linha.fabrica      = $login_fabrica
			AND     tbl_produto.ativo IS TRUE";
	$res = pg_exec ($con,$sql);
	$produto_descricao = @pg_result ($res,0,0);
}


if(strlen($os)==0) $body_onload = "onload = 'javascript: document.frm_os.data_abertura.focus()'";
$title       = "Cadastro de Ordem de Serviço"; 
$layout_menu = 'os';

include "cabecalho.php";

?>

<!--=============== <FUNÇÕES> ================================!-->

<?php
	include 'js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script type="text/javascript">
	$(document).ready(function()
	{
		$("#data_abertura").mask("99/99/9999");
		$("#data_nf").mask("99/99/9999");
		$("#revenda_cep, #consumidor_cep").mask("99.999-999");
		$("#revenda_cnpj").mask("99.999.999/9999-99");
	});


// AJAX para defeito RECLAMADO
$().ready(function() {

	<?php ## quando for abrir um OS, nao pode alterar o defeito reclamado
		if (strlen($os)>0){
			echo "return true;";
		}
	?>

	$("input[name=produto_referencia]").blur(function(){
		getInformacoesProduto();
		if ($('#produto_referencia').val() == '' ) return false;
		
		$('select[name=defeito_reclamado]').html('<option value="">Aguarde........</option>');
		$.post('os_cadastro_ajax.php?busca=defeito_reclamado', 
			{ produto_referencia : $(this).val()}, 
			function(resposta){
				$('select[name=defeito_reclamado]').html(resposta);
			}
			
		);
	});

	$("input[name=produto_descricao]").blur(function(){
		getInformacoesProduto();
		if ($('#produto_referencia').val() == '' ) return false;

		$('select[name=defeito_reclamado]').html('<option value="">Aguarde........</option>');
		$.post('os_cadastro_ajax.php?busca=defeito_reclamado', 
			{ produto_referencia : $(this).val()}, 
			function(resposta){
				$('select[name=defeito_reclamado]').html(resposta);
			}
			
		);
	});
});

// AJAX para defeito CONSTATADO
$().ready(function() {
	$("select[name=defeito_reclamado]").change(function(){
		if ($('#produto_referencia').val() == '' ) return false;
		$('select[name=defeito_constatado]').html('<option value="">Aguarde........</option>');
		$.post('os_cadastro_ajax.php?busca=defeito_constatado', 
			{ defeito_reclamado : $(this).val(),produto_referencia: $('#produto_referencia').val()}, 
			function(resposta){
				$('select[name=defeito_constatado]').html(resposta);
			}
			
		);
	});
});

// AJAX para SOLUÇÃO
$().ready(function() {
	$("select[name=defeito_constatado]").change(function(){
		$('select[name=solucao_os]').html('<option value="">Aguarde........</option>');
		$.post('os_cadastro_ajax.php?busca=solucao', 
			{
				defeito_reclamado : $('#defeito_reclamado').val(),
				defeito_constatado : $(this).val(),
				produto_referencia: $('#produto_referencia').val()
			}, 
			function(resposta){
				$('select[name=solucao_os]').html(resposta);
			}
			
		);
	});
});

function verificarForm(){
	if ($("select[name=solucao_os]").val()>0){
		desbloquear();
	}else{
		bloquear();
	}
}

function os_digitada(){

	$("input[rel=os]").attr({ readonly: "readonly", title: "Não é permitido alteração!" });
	$("select[rel=os]").attr({ readonly: "readonly", title: "Não é permitido alteração!" });
	$("img[rel=os]").attr({ disabled: "disabled", title: "Não é permitido alteração!" });

	$("select[name=defeito_reclamado]").change(function(){});
	$("select[name=defeito_reclamado]").attr({title: "Após o cadastro da OS, não é mais possível alterar" });
}

function desbloquear(){
	$("input[rel=texto]").removeAttr("disabled");
	$("input[rel=texto]").removeAttr("title");
	$("input[rel=texto]").attr({title: "Lançamento Liberado" });

	$("select[rel=combo]").removeAttr("disabled");
	$("select[rel=combo]").removeAttr("title");
	$("select[rel=combo]").attr({title: "Lançamento Liberado" });

	$("img[rel=imagem]").removeAttr("disabled");
	$("img[rel=imagem]").removeAttr("alt");
	$("img[rel=imagem]").attr({alt: "Lançamento Liberado" });

	$('#tbl_pecas').unblock();
}

function bloquear(){
	$("input[rel=texto]").attr({ disabled: "disabled", title: "Lançamento bloqueado. Preencha os campos acima!" });
	$("select[rel=combo]").attr({ disabled: "disabled", title: "Lançamento bloqueado. Preencha os campos acima!" });
	$("img[rel=imagem]").attr({ disabled: "disabled", alt: "Lançamento bloqueado. Preencha os campos acima!" });
	$('#tbl_pecas').block('<h1>Lançamento de Peças Bloqueado</h1>', { border: '2px solid #727272' }); 
}

$().ready(function() {
	getInformacoesProduto();
	bloquear();
	verificarForm();
});


<?php
	if (strlen ($os) > 0) {
		echo '
			$().ready(function() {
				os_digitada();
			});		
		';
	}
?>
</script>

<script type="text/javascript">

function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}
}

// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		if ("<?php echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.sua_os;
		}else{
			janela.proximo = document.frm_os.data_abertura;
		}
		janela.focus();
	}
}

// ========= Função PESQUISA DE PRODUTO POR REFERÊNCIA OU DESCRIÇÃO ========= //

function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.produto_serie;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}
}



/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento){
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
	for (var i = 0; i<input.value.length;i++ ){
		if (temp.length==2) temp=temp+"/";
		if (temp.length==5) temp=temp+"/";
		if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
			temp=temp+input.value.substr(i,1);
		}
	}
	input.value = temp.substr(0,10);
	return false;
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

function getInformacoesProduto(){
	$.ajax({
		type: "POST",
		url: "<? echo $PHP_SELF ?>",
		data: "getProduto=sim&produto_referencia="+$('#produto_referencia').val(),
		success: function(msg){
			document.getElementById('dados').innerHTML=msg;
	}
	});
}

function pegaMascara(){

	var style2 = document.getElementById('mascara'); 
	if (style2==false) return; 
	if (style2.style.display=="block"){
		style2.style.display = "none";
	}else{
		style2.style.display = "block";
		$.ajax({
			type: "POST",
			url: "<? echo $PHP_SELF ?>",
			data: "pegaMascara=sim&produto_referencia="+$('#produto_referencia').val(),
			success: function(msg){
				style2.innerHTML = msg;
			}
		});
	}
}

</script>

<!--========================= AJAX ==================================.-->

<?php //include "javascript_pesquisas_ronald.php" ?>

<style>
.Label{
font-family: Verdana;
font-size: 10px;
}
.Titulo{
font-family: Verdana;
font-size: 12px;
font-weight: bold;
}
.Erro{
font-family: Verdana;
font-size: 12px;
color:#FFF;
border:#485989 1px solid; background-color: #990000;
}
</style>

<br>

<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0){
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
	}
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
<br>

<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>" onSubmit="alert('Já');">
<input class="frm" type="hidden" name="os" value="<? echo $os ?>">

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<!--
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
		<tr>
			<td nowrap class='Label'>Código do Posto</td>
			<td><input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" onblur="fnc_pesquisa_posto2(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" >&nbsp;<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')">
			</td>

			<td nowrap class='Label'>Nome do Posto</td>
			<td><input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
			</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td>
		<img height="2" width="16" src="imagens/spacer.gif">
	</td>
</tr>
-->
<tr>
	<td valign="top" align="left">

		<!-- Informações da OS  -->
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0' cellspacing=0 cellpadding=0>


		<? if (($login_fabrica == 19) or ($login_fabrica == 20)) { ?>
			<tr bgcolor='#FCEAA3'>
				<td class='Label' align='center' colspan='4' style='font-size:12px;padding:4px'>Tipo de Atendimento
					<select class='frm' name="tipo_atendimento" style='width:220px;'>
					<option></option>
					<?
					$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
					$res = pg_exec ($con,$sql) ;
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
						$Xtipo_atendimento		= pg_result($res,$i,tipo_atendimento);
						$Xatendimento_descricao = pg_result ($res,$i,descricao);
						echo "<option ";
						if ($tipo_atendimento == $Xtipo_atendimento) echo " selected ";
						echo " value='$Xtipo_atendimento'>" ;
						echo "$Xtipo_atendimento - $Xatendimento_descricao";
						echo "</option>";
					}
					?>
					</select>
				</td>
			</tr>
			<tr><td><img height="8" width="16" src="imagens/spacer.gif"></td></tr>

		<? } ?>

		<tr>
			<td class='Label'>OS Fabricante</td>
			<td><input name ="sua_os" class ="frm" type = "text" size = "15" maxlength = "20" rel='os' value ="<? echo $sua_os ?>" > </td>
			<td nowrap class='Label'>Data Abertura</td>
			<td><input name="data_abertura" id="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0"   rel='os'><font size='-3' COLOR='#000099'> Ex.: <?=date('d/m/Y');?></td>

		</tr>
		</table>

	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

		<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'>
		<tr>
			<td class='Titulo' align='left' colspan='2'>Produto</td>
		</tr>

		<tr>
			<td nowrap class='Label'>Referência do Produto</td>
			<td><input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" rel='os' value="<? echo $produto_referencia ?>" >&nbsp;<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' rel='os' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.voltagem)"></td>
			<td nowrap class='Label'>Descrição do Produto</td>
			<td><input class="frm"  rel='os' type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>"  >&nbsp;<img src='imagens/btn_lupa_novo.gif' rel='os' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto2(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.voltagem)">
			</td>
		</tr>
		<tr>
			<td nowrap class='Label'>N. Série.</td>
			<td><input class="frm"  rel='os' type="text" name="produto_serie" size="15" maxlength="20" value="<? echo $produto_serie ?>" 
			onfocus='<? if ($login_fabrica==3) echo "pegaMascara();"?>'
			onBlur='<? if ($login_fabrica==3) echo "pegaMascara();"?>'
			>
			<div id='mascara' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
			</td>
			<td nowrap class='Label'>Voltagem</td>
			<td><input class="frm"  rel='os' type="text" name="voltagem" size="6" maxlength="6" value="<? echo $voltagem ?>"></td>
		</tr>


			<tr>
			<td nowrap class='Label'>Aparência do Produto</td>
			<td><input class="frm"  rel='os' type="text" name="aparencia_produto" size="15" maxlength="20" value="<? echo $aparencia_produto ?>"></td>
			<td nowrap class='Label'>Acessórios</td>
			<td><input class="frm"  rel='os' type="text" name="acessorios" size="15" maxlength="20" value="<? echo $acessorios ?>"></td>
			</tr>


		<tr>
		<? if ($login_fabrica==15) {?>
			<td nowrap class='Label'>Cartão Clube</td>
			<td><input class="frm"  rel='os' type="text" name="cartao_clube" size="15" maxlength="20" value="<? echo $cartao_clube ?>"></td>
		<? } ?>

		<? if ($login_fabrica == 19) { ?>
			<td nowrap class='Label'>Qtde. Produtos</td>
			<td>
			<input class="frm" type="text" name="qtde_produtos" size="2" maxlength="3" value="<? echo $qtde_produtos ?>">
			</td>
		<? } ?>

		<?
		if ($login_fabrica <> 19 and $login_fabrica<>24) {
			echo "<td nowrap class='Label'>";
			echo "Consumidor&nbsp;";
			echo "<input type='radio' name='consumidor_revenda' rel='os' value='C' " ;
			if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked"; 
			echo ">";
			echo "</td>";
			echo "<td nowrap class='Label'>";
			echo "Revenda&nbsp;";
			echo "<input type='radio' name='consumidor_revenda'  rel='os' value='R' ";
			if ($consumidor_revenda == 'R') echo " checked"; 
			echo ">&nbsp;</td>";
		}else{
			echo "<input type='hidden' name='consumidor_revenda' value='C'>";
		}
		?>
		</tr>

		</table>
		<!-- Informações da OS - FIM  -->

	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

		<!-- Informações do Consumidor  -->
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">

		<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'>
		<tr>
			<td class='Titulo' align='left' colspan='2'>Consumidor</td>
		</tr>
		<tr>
			<td class='Label'>Nome Consumidor:</td>
			<td><input class="frm" type="text" name="consumidor_nome" rel='os' size="40" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)"></td>
			<td class='Label'>Telefone:</td>
			<td><input class="frm telefone" type="text" name="consumidor_fone" id="consumidor_fone" rel='os' size="15" maxlength="50" value="<? echo $consumidor_fone ?>"></td>
			<td class='Label'>CEP:</td>
			<td><input class="frm" type="text" name="consumidor_cep" id="consumidor_cep" rel='os' size="10" maxlength="10" value="<? echo $consumidor_cep ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Endereço:</td>
			<td><input class="frm" type="text" name="consumidor_endereco" rel='os' size="40" maxlength="50" value="<? echo $consumidor_endereco ?>"></td>
			<td class='Label'>Número:</td>
			<td><input class="frm" type="text" name="consumidor_numero" rel='os' size="4" maxlength="10" value="<? echo $consumidor_numero ?>"></td>
			<td class='Label'>Bairro:</td>
			<td><input class="frm" type="text" name="consumidor_bairro" rel='os' size="15" maxlength="40" value="<? echo $consumidor_estado ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Complemento:</td>
			<td><input class="frm" type="text" name="consumidor_complemento" rel='os' size="20" maxlength="20" value="<? echo $consumidor_complemento ?>"></td>
			<td class='Label'>Cidade:</td>
			<td><input class="frm" type="text" name="consumidor_cidade" rel='os' size="15" maxlength="50" value="<? echo $consumidor_cidade ?>"></td>
			<td class='Label'>Estado:</td>
			<td><input class="frm" type="text" name="consumidor_estado" rel='os' size="2" maxlength="2" value="<? echo $consumidor_estado ?>"></td>
		</tr>

		</table>
		<!-- Informações do Consumidor - FIM -->
	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

		<!-- Informações da Revenda  -->
		<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0'>
		<tr>
			<td class='Titulo' align='left' colspan='2'>Revenda</td>
		</tr>
		<tr>
			<td class='Label'>Nome Revenda:</td>
			<td><input class="frm" type="text" name="revenda_nome" rel='os' size="27" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">
			<img src='imagens/btn_lupa_novo.gif' rel='os' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
			</td>
			<td class='Label'>Nota Fiscal:</td>
			<td><input class="frm" type="text" name="nota_fiscal" rel='os' size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" ></td>
			<td class='Label'>Data Compra:</td>
			<td><input class="frm" type="text" name="data_nf" id="data_nf" rel='os' size="12" maxlength="10" value="<? echo $data_nf ?>" tabindex="0" title='Ex.: 25/10/2006'></td>
		</tr>
		<tr>
			<td class='Label'>CNPJ:</td>
			<td><input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" rel='os' size="20" maxlength="18" value="<? echo $revenda_cnpj ?>">
			<img src='imagens/btn_lupa_novo.gif' rel='os' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
			<td class='Label'>Telefone:</td>
			<td><input class="frm telefone" type="text" name="revenda_fone" id="revenda_fone" rel='os' size="15" maxlength="20" value="<? echo $revenda_cidade ?>"></td>
			<td class='Label'>CEP:</td>
			<td><input class="frm" type="text" name="revenda_cep" id="revenda_cep" rel='os' size="10" maxlength="9" value="<? echo $revenda_cep ?>"></td>
		</tr>
		<tr>
			<td class='Label'>Endereço:</td>
			<td><input class="frm" type="text" name="revenda_endereco" rel='os' size="30" maxlength="50" value="<? echo $revenda_endereco ?>"></td>
			<td class='Label'>Número:</td>
			<td><input class="frm" type="text" name="revenda_numero" rel='os' size="8" maxlength="40" value="<? echo $revenda_numero ?>"></td>
			<td class='Label'>Complet.:</td>
			<td><input class="frm" type="text" name="revenda_complemento" rel='os' size="10" maxlength="20" value="<? echo $revenda_complemento ?>">
			<input type='hidden' name='revenda_email' value=''>
			</td>
		</tr>
		<tr>
			<td class='Label'>Bairro:</td>
			<td><input class="frm" type="text" name="revenda_bairro" rel='os' size="8" maxlength="40" value="<? echo $revenda_bairro ?>"></td>
			<td class='Label'>Cidade:</td>
			<td><input class="frm" type="text" name="revenda_cidade" rel='os' size="15" maxlength="40" value="<? echo $revenda_cidade ?>">
			</td>
			<td class='Label'>Estado:</td>
			<td><input class="frm" type="text" name="revenda_estado" rel='os' size="4" maxlength="2" value="<? echo $revenda_estado ?>">
			<input type='hidden' name='revenda_email' value=''>
			</td>
		</tr>
		</table>
		<!-- Informações da Revenda  FIM -->

	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

<?
	if(strlen($os)>0){
		$sql = "SELECT tbl_os.produto,tbl_linha.linha,tbl_familia.familia 
			FROM tbl_os 
			JOIN tbl_produto USING(produto) 
			JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
			JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
			WHERE tbl_os.os = $os
			AND   tbl_os.fabrica = $login_fabrica";
		$res = pg_exec($con,$sql) ;
		$produto = pg_result($res,0,produto);
		$familia = pg_result($res,0,familia);
		$linha   = pg_result($res,0,linha);
	}

?>
	<input type='hidden' name='produto' id='produto' value='<?=$produto?>'>
	<input type='hidden' name='linha'   id='linha'   value='<?=$linha?>'>
	<input type='hidden' name='familia' id='familia' value='<?=$familia?>'>

<?

//--==== Defeito Reclamado ===============================================================================
echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='750' border='0'>";
echo "<tr>";
echo "<td class='Titulo' align='left' colspan='2'>Análise de Produto: <span id='dados' style='display:inline;font-weight:normal'><i><u> Não informado</i></u></span>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td class='Label' align='left' >Defeito Reclamado:</td>";
echo "<td>";
echo "<select name='defeito_reclamado' id='defeito_reclamado' rel='os' class='frm' style='width:220px;'>";


if (strlen($os)>0 AND strlen($defeito_reclamado)>0){
	$sql = "SELECT  tbl_defeito_reclamado.descricao, 
					tbl_defeito_reclamado.defeito_reclamado 
				FROM tbl_defeito_reclamado 
				WHERE tbl_defeito_reclamado.fabrica = $login_fabrica 
				AND tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado";
	$resD = pg_exec ($con,$sql) ;
	$row = pg_numrows ($resD); 
	if($row>0) {
		$defeito_reclamado_aux	= pg_result($resD, 0, 'defeito_reclamado');
		$descricao_aux			= pg_result($resD, 0, 'descricao');
		echo "<option value='$defeito_reclamado_aux'>$descricao_aux</option>";
	}
}

echo "</select>";
echo "</td>";
echo "</tr>";


//--==== Defeito Constatado ==============================================================================
if ($pedir_defeito_constatado_os_item != "f") {
	echo "<tr>";
	echo "<td class='Label' align='left'>";
	echo "Defeito Constatado:";
	#echo "<a href=\"javascript:Integridade(document.frm_os.linha.value,document.frm_os.familia.value,document.frm_os.defeito_reclamado.value);\"><img src='imagens/mais.gif' id='img_inte'></a>";
  echo"<div id='integrigade' style='position: absolute;visibility:hidden; opacity:.90;filter: Alpha(Opacity=90);width:401px; border: #555555 1px solid; background-color: #EFEFEF'></div>";
  echo "</td>";
	echo "<td>";
	echo "<select name='defeito_constatado' id='defeito_constatado'  class='frm' style='width: 220px;'>";


		if (strlen($os)>0){
			$sql ="SELECT 	DISTINCT(tbl_diagnostico.defeito_constatado),
							tbl_defeito_constatado.descricao
					FROM tbl_diagnostico
					JOIN tbl_defeito_constatado ON tbl_diagnostico.defeito_constatado=tbl_defeito_constatado.defeito_constatado
					WHERE 1=1";

			if($login_fabrica<>15)  {$sql.=" AND tbl_diagnostico.defeito_reclamado=$defeito_reclamado ";}
			if(strlen($linha)>0)	{$sql.=" AND tbl_diagnostico.linha=$linha";}
			if(strlen($familia)>0)	{$sql.=" AND tbl_diagnostico.familia=$familia";}
		
			$sql .=" ORDER BY tbl_defeito_constatado.descricao";

			$resD = pg_exec ($con,$sql) ;
			$row = pg_numrows ($resD);

			if($row>0) {
				echo "<option value=''>Selecione o defeito constatado</option>";
				for($i=0; $i<$row; $i++) {  
					$def_consta       = pg_result($resD, $i, 'defeito_constatado');
					$descricao_consta = pg_result($resD, $i, 'descricao');

					if ($defeito_constatado == $def_consta) $tmp_def = " SELECTED "; else  $tmp_def = "";

					echo "<option value='$def_consta' $tmp_def>\n";
					echo "$descricao_consta\n";
					echo "</option>\n";
				}
			}else{
				echo "<option value=''>Nenhum defeito constatado encontrado</option>";
			}
		}

	echo "</select>";
	echo "</td>";
	echo "</tr>";
}


if ($pedir_solucao_os_item <> 'f') {
	echo "<tr>";
	echo "<td class='Label'align='left' >";
	echo "Solução:";
	echo "</td>";

	echo "<td>";
	echo "<select name='solucao_os' id='solucao_os' class='frm'  style='width:200px;' onblur='javascript:verificarForm()'>";

	if (strlen($os)>0 AND strlen($defeito_constatado)>0 AND (strlen($defeito_reclamado)>0 OR $login_fabrica<>15)){

		if($login_fabrica <> 15){
			$sql ="SELECT DISTINCT(tbl_diagnostico.solucao), 
						tbl_solucao.descricao 
						FROM tbl_diagnostico 
						JOIN tbl_solucao on tbl_diagnostico.solucao = tbl_solucao.solucao 
						WHERE tbl_diagnostico.defeito_constatado = $defeito_constatado 
						AND   tbl_diagnostico.defeito_reclamado = $defeito_reclamado 
						AND   tbl_diagnostico.linha=$linha";
			if(strlen($familia)>0){$sql.=" and tbl_diagnostico.familia=$familia";}
			$sql .=" ORDER BY tbl_solucao.descricao";
		}else{
			$sql ="SELECT DISTINCT (tbl_solucao.descricao),
							tbl_diagnostico.solucao
						FROM tbl_diagnostico
						JOIN tbl_solucao on tbl_diagnostico.solucao = tbl_solucao.solucao 
						WHERE tbl_diagnostico.defeito_constatado = $defeito_constatado 
						AND   tbl_diagnostico.linha = $linha
						AND   tbl_diagnostico.familia = $familia
						ORDER BY tbl_solucao.descricao";
		}

		$resD = pg_exec ($con,$sql) ;
		$row = pg_numrows ($resD);
		if($row>0) {
			echo "<option value=''>Selecione a solução</option>";
			for($i=0; $i<$row; $i++) {  
				$solucao_aux   = pg_result($resD, $i, 'solucao'); 
				$desc_sol      = pg_result($resD, $i, 'descricao');

				if ($solucao_os == $solucao_aux) $tmp_def = " SELECTED "; else  $tmp_def = "";

				echo "<option value='$solucao_aux' $tmp_def>\n";
				echo "$desc_sol\n";
				echo "</option>\n";
			}
		}else{
				echo "<option value=''>Nenhum defeito constatado encontrado</option>";
		}
	}



	echo "</select>";
	echo "</td>";
	echo "</tr>";

}
echo "</table>";

?>

	</td>
</tr>
<tr><td><img height="0" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">

<?



if(strlen($os)==0 AND 1==2){
	echo " <table width='750'align='center' border='0'cel>";
	echo "<tr>";
	echo "<td align='left'>";
	echo "<div id='esconde'  style='position: absolute;visibility:visible; opacity:.90;filter: Alpha(Opacity=90);height: 300px; width: 750px; border: #555555 1px solid; background-color: #EFEFEF'> Lançamento Bloqueado </div>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}


	echo '<div id="tbl_pecas" class="blockMe">';




if (strlen($os) > 0) {
	$sql = "SELECT  tbl_os.*                                      ,
			tbl_produto.referencia                        ,
			tbl_produto.descricao                         ,
			tbl_produto.voltagem                          ,
			tbl_produto.linha                             ,
			tbl_produto.familia                           ,
			tbl_os_extra.os_reincidente AS reincidente_os ,
			tbl_posto_fabrica.codigo_posto                ,
			tbl_posto_fabrica.reembolso_peca_estoque      
		FROM    tbl_os
		JOIN    tbl_os_extra USING (os)
		JOIN    tbl_produto  USING (produto)
		JOIN    tbl_posto         USING (posto)
		JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
			  AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_os.os = $os";
	$res = @pg_exec ($con,$sql) ;

	if (@pg_numrows($res) > 0) {
		$login_posto                 = pg_result($res,0,posto);
		$linha                       = pg_result($res,0,linha);
		$familia                     = pg_result($res,0,familia);
		$consumidor_nome             = pg_result($res,0,consumidor_nome);
		$sua_os                      = pg_result($res,0,sua_os);
		$produto_os                  = pg_result($res,0,produto);
		$produto_referencia          = pg_result($res,0,referencia);
		$produto_descricao           = pg_result($res,0,descricao);
		$produto_voltagem            = pg_result($res,0,voltagem);
		$produto_serie               = pg_result($res,0,serie);
		$qtde_produtos               = pg_result($res,0,qtde_produtos);
		$produto_type                = pg_result($res,0,type);
		$defeito_reclamado           = pg_result($res,0,defeito_reclamado);
		$defeito_constatado          = pg_result($res,0,defeito_constatado);
		$causa_defeito               = pg_result($res,0,causa_defeito);
		$posto                       = pg_result($res,0,posto);
		$obs                         = pg_result($res,0,obs);
		$os_reincidente              = pg_result($res,0,reincidente_os);
		$codigo_posto                = pg_result($res,0,codigo_posto);
		$reembolso_peca_estoque      = pg_result($res,0,reembolso_peca_estoque);
		$consumidor_revenda          = pg_result($res,0,consumidor_revenda);
		$troca_faturada              = pg_result($res,0,troca_faturada);
		$motivo_troca                = pg_result($res,0,motivo_troca);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);

	}
}



#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto   ,
				tbl_fabrica.pergunta_qtde_os_item ,
				tbl_fabrica.os_item_serie         ,
				tbl_fabrica.os_item_aparencia     ,
				tbl_fabrica.qtde_item_os
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$resX = pg_exec ($con,$sql);

if (pg_numrows($resX) > 0) {
	$os_item_subconjunto = pg_result($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
	
	$pergunta_qtde_os_item = pg_result($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';
	
	$os_item_serie = pg_result($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';
	
	$os_item_aparencia = pg_result($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';
	
	$qtde_item = pg_result($resX,0,qtde_item_os);
	if (strlen ($qtde_item) == 0) $qtde_item = 5;
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

	$sql = "SELECT  tbl_os_item.os_item                                                ,
					tbl_os_item.pedido                                                 ,
					tbl_os_item.qtde                                                   ,
					tbl_os_item.causa_defeito                                          ,
					tbl_os_item.posicao                                                ,
					tbl_os_item.admin              as admin_peca                       ,
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
	$res = pg_exec ($con,$sql) ;
	
	$pedido = array();
	if (pg_numrows($res) > 0) {
		$fim_itens = $inicio_itens + pg_numrows($res);
		$i = 0;
		for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
			$os_item[$k]                 = pg_result($res,$i,os_item);
			$os_produto[$k]              = pg_result($res,$i,os_produto);
			$pedido[$k]                  = pg_result($res,$i,pedido);
			$peca[$k]                    = pg_result($res,$i,referencia);
			$qtde[$k]                    = pg_result($res,$i,qtde);
			$posicao[$k]                 = pg_result($res,$i,posicao);
			$produto[$k]                 = pg_result($res,$i,subconjunto);
			$serie[$k]                   = pg_result($res,$i,serie);
			$descricao[$k]               = pg_result($res,$i,descricao);
			$defeito[$k]                 = pg_result($res,$i,defeito);
			$defeito_descricao[$k]       = pg_result($res,$i,defeito_descricao);
			$pcausa_defeito[$k]          = pg_result($res,$i,causa_defeito);
			$causa_defeito_descricao[$k] = pg_result($res,$i,causa_defeito_descricao);
			$servico[$k]                 = pg_result($res,$i,servico_realizado);
			$servico_descricao[$k]       = pg_result($res,$i,servico_descricao);
			$admin_peca[$k]              = pg_result($res,$i,admin_peca);//aqui
			if(strlen($admin_peca[$k])==0) $admin_peca[$k]="P";
			$i++;
		}
	}else{
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$produto[$i]        = $_POST["produto_"        . $i];
			$serie[$i]          = $_POST["serie_"          . $i];
			$posicao[$i]        = $_POST["posicao_"        . $i];
			$peca[$i]           = $_POST["peca_"           . $i];
			$qtde[$i]           = $_POST["qtde_"           . $i];
			$defeito[$i]        = $_POST["defeito_"        . $i];
			$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
			$servico[$i]        = $_POST["servico_"        . $i];
			$admin_peca[$i]     = $_POST["admin_peca_"     . $i];
			
			if (strlen($peca[$i]) > 0) {
				$sql = "SELECT  tbl_peca.referencia,
							tbl_peca.descricao
					FROM    tbl_peca
					WHERE   tbl_peca.fabrica    = $login_fabrica
					AND     tbl_peca.referencia = $peca[$i];";
				$resX = @pg_exec ($con,$sql) ;
				
				if (@pg_numrows($resX) > 0) $descricao[$i] = trim(pg_result($resX,0,descricao));
			}
		}
	}
}else{//ok
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
		$admin_peca[$i]     = $_POST["admin_peca_"     . $i];//aqui

		if (strlen($peca[$i]) > 0) {
			$sql = "SELECT  tbl_peca.referencia,
						tbl_peca.descricao
				FROM    tbl_peca
				WHERE   tbl_peca.fabrica    = $login_fabrica
				AND     tbl_peca.referencia = '$peca[$i]';";
			$resX = @pg_exec ($con,$sql) ;
			
			if (@pg_numrows($resX) > 0) $descricao[$i] = trim(pg_result($resX,0,descricao));
		}
	}
}

//--===== Lançamento das Peças da OS ====================================================================
echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<table style=' border:#76D176 1px solid; background-color: #EFFAEF' align='center' width='750' border='0' cellspacing='1' cellpadding='0'>";
echo "<tr>";
echo "<td class='Titulo' align='left' colspan='2'>Peças</td>";
echo "<td align='right' colspan='3'><p style='font-weight:normal;font-size:9px;color:gray;display:inline'>Opção para digitar peças somente depois dos campos obrigatórios forem preenchidos</p></td>";
echo "</tr>";
echo "<tr height='20' bgcolor='#76D176'>";
echo "<td align='center' class='Titulo'><b>Código</b></td>";
echo "<td align='center' class='Titulo'><b style='font-size:10px'>Lista<br>Básica</b></td>";
echo "<td align='center' class='Titulo'><b>Descrição</b></td>";
echo "<td align='center' class='Titulo'><b>Defeito</b></td>";
echo "<td align='center' class='Titulo'><b>Serviço</b></td>";
echo "</tr>";

		$loop = $qtde_item;
		if ($login_fabrica==3){
			if (strlen($os)>0){
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
			}else{
				$loop = 3;
			}
		}

		if($login_fabrica == 6 AND $posto_item_aparencia == 't'){
			$loop = $loop+7;
		}

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


			echo "<tr $cor>";
			echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>\n";
			echo "<input type='hidden' name='os_item_$i'    value='$os_item[$i]'>\n";
			echo "<input type='hidden' name='descricao'>";
			echo "<input type='hidden' name='preco'>";
			echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//aqui
			
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
					echo "<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista_sub (document.frm_os.produto_$i.value, document.frm_os.posicao_$i, document.frm_os.peca_$i, document.frm_os.descricao_$i)' alt='Clique para abrir a lista básica do produto selecionado' style='cursor:pointer;'>";
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
//echo $sql."<BR>";
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
				

					echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]' alt='LISTA BÁSICA'><img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"tudo\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
//takashi chamado 300 12-07
					echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";         
//takashi chamado 300 12-07                                   
					echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'><img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>\n";
					if ($pergunta_qtde_os_item == 't') {
						echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>\n";
					}
				}
			}else{
				if ($login_fabrica == 14) {
					echo "<td align='center'><input class='frm' type='text' name='posicao_$i' size='5' maxlength='5' value='$posicao[$i]'></td>\n";
				}else{
					echo "<input type='hidden' name='posicao_$i'>\n";
				}

				//takashi 04-04-07 hd 1819
				echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'";
				if($login_fabrica==24) {
					echo "onblur='fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"tudo\" )'";
				}
				echo " ><img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14){
					echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"referencia\")'";
				}else{
					echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")'";
				}
				echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
				echo "</td>\n";
				
				if($login_fabrica ==6){
					echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica2(document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
				}else{
					echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_lista_basica (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\")' alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";
				}
				echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'><img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle'";
				
				if ($login_fabrica == 14){
					echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"descricao\")'";
				}else{
					echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )'";
				}
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
			echo "<td align='center'>";
/*INTEGRIDADE DE PEÇAS COMECA AQUI - TAKASHI HD 1950*/
			if($login_fabrica==24 or $login_fabrica==6){

				echo "<select name='defeito_$i'  class='frm' style='width:";if($login_fabrica==6){echo "170px;";}else{echo "150px;";} echo "' onfocus='defeitoLista(document.frm_os.peca_$i.value,$i,$os);' >";
				echo "<option id='op_$i' value=''></option>";
				$sql = "SELECT tbl_defeito.defeito, tbl_defeito.descricao
						FROM   tbl_defeito
						WHERE  tbl_defeito.fabrica = $login_fabrica
						AND    tbl_defeito.defeito = $defeito[$i]
						AND    tbl_defeito.ativo IS TRUE
						ORDER BY descricao";
				$res = pg_exec ($con,$sql) ;
				if(pg_numrows($res)>0){
					echo "<option value='" . pg_result ($res,0,defeito) . "' SELECTED>".pg_result ($res,0,descricao)."</option>";
				
				}
	
				echo "</select>";
			}else{
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
			}
/*INTEGRIDADE DE PEÇAS TERMINA AQUI - TAKASHI HD 1950*/
			echo "</td>\n";

			echo "<td align='center'>";
/*INTEGRIDADE DE PEÇAS x SOLUÇÃO COMECA AQUI - TAKASHI HD 2504*/
			if($login_fabrica==6){
				echo "<select class='frm' size='1' name='servico_$i'  style='width:150px;' onfocus='servicoLista(document.frm_os.peca_$i.value,$i);' >";
				echo "<option id_servico='op_$i' value=''></option>";
				echo "<option selected></option>";
				$sql = "SELECT 	tbl_servico_realizado.descricao, 
								tbl_servico_realizado.servico_realizado
						FROM tbl_servico_realizado 
						WHERE tbl_servico_realizado.fabrica = $login_fabrica
						AND tbl_servico_realizado.ativo IS TRUE 
						AND tbl_servico_realizado.servico_realizado = $servico[$i]
						ORDER BY descricao";
				$res = pg_exec ($con,$sql) ;
				if(pg_numrows($res)>0){
					echo "<option value='" . pg_result ($res,0,servico_realizado) . "' SELECTED>".pg_result ($res,0,descricao)."</option>";
				
				}
				echo "</select>";
			}else{
				echo "<select class='frm' size='1' name='servico_$i'>";
				echo "<option selected></option>";
	
				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
	
				if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 24 and $login_fabrica<>15) {
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
	
					if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1 AND $login_fabrica <> 24 and $login_fabrica<>15) {
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
echo "</table>";
//--===== FIM - Lançamento de Peças =====================================================================

?>
	</div>
	</td>
</tr>
<tr><td><img height="2" width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td valign="top" align="left">
<?

//--===== Data Fechamento da OS =========================================================================
echo "<table style=' border:#B63434 1px solid; background-color: #cfc0c0' align='center' width='750' border='0'height='40'>";
echo "<tr>";
#echo "<td valign='middle' align='RIGHT' class='Label'>Data Fechamento:</td>";
#echo "<td valign='middle' align='LEFT' class='Label' >";
#echo "<INPUT TYPE='text' NAME='data_fechamento' value='$data_fechamento' size='12' maxlength='10' class='frm'> dd/mm/aaaa";
#echo "</td>";
echo "<td width='50' valign='middle' align='LEFT'>";

echo "<input type='hidden' name='btn_acao' value=''>";
echo "<input type='button' name='btn' value='Gravar' onClick=\"
	if (document.frm_os.btn_acao.value != ''){
		alert('Aguarde');
	}else{
		document.frm_os.btn_acao.value='gravar'; 
		alert('Gravando...');
		document.frm_os.submit();
	}\">";

echo "</td>";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>";
echo "</tr>";
echo "</table>";
//--=====================================================================================================
?>
	</td>

</tr>
</table>
</form>

<? if (strlen($msg_erro)>0){?>
	<div id='erro' style='opacity:.85;' class='Erro'><? echo $msg_erro ?></div>
<? } ?>

<p>
<p>
</table></table>
<? include "rodape.php";?>
