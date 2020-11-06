<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica_nome <> "Dynacom" AND $login_fabrica_nome <> "Tectoy") {
	header ("Location: os_cadastro.php");
	exit;
}

$btn_acao = trim (strtoupper ($_POST['btn_acao']));

#------------ Grava dados Adidionais da OS ---------
if ($btn_acao == "CONTINUAR") {
	$defeito_reclamado = trim ($_POST['defeito_reclamado']);
	$os = $_POST ['os'];
	$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado WHERE os = $os AND posto = $login_posto";
	$res = pg_exec ($con,$sql);

	#----------- Dados do Consumidor -------------
	$cidade = strtoupper (trim ($_POST ['consumidor_cidade']));
	$estado = strtoupper (trim ($_POST ['consumidor_estado']));

	if (strlen ($cidade) > 0 AND strlen ($estado) > 0 ) {

		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$cidade = pg_fetch_result($res, 0, "cidade");
		} else {
			$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
				$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

				$sql = "INSERT INTO tbl_cidade (
							nome, estado
						) VALUES (
							'{$cidade_ibge}', '{$cidade_estado_ibge}'
						) RETURNING cidade";
				$res = pg_query($con, $sql);

				$cidade = pg_fetch_result($res, 0, "cidade");
			} else {
				$cidade = "null";
			}
		}

		$nome		= trim ($_POST['consumidor_nome']) ;
		$cpf		= trim ($_POST['consumidor_cpf']) ;
		$fone		= trim ($_POST['consumidor_fone']) ;
		$endereco	= trim ($_POST['consumidor_endereco']) ;
		$numero		= trim ($_POST['consumidor_numero']) ;
		$complemento= trim ($_POST['consumidor_complemento']) ;
		$bairro		= trim ($_POST['consumidor_bairro']) ;
		$cep		= trim ($_POST['consumidor_cep']) ;

		$cpf = str_replace (".","",$cpf);
		$cpf = str_replace ("-","",$cpf);
		$cpf = str_replace ("/","",$cpf);
		$cpf = str_replace (",","",$cpf);
		$cpf = str_replace (" ","",$cpf);

		$cep = str_replace (".","",$cep);
		$cep = str_replace ("-","",$cep);
		$cep = str_replace ("/","",$cep);
		$cep = str_replace (",","",$cep);
		$cep = str_replace (" ","",$cep);

		$sql = "SELECT cliente FROM tbl_cliente WHERE cpf = '$cpf'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$cliente = pg_result ($res,0,cliente);

			$sql = "UPDATE tbl_cliente SET
						nome		= '$nome' ,
						cpf			= '$cpf' ,
						fone		= '$fone' ,
						endereco	= '$endereco' ,
						numero		= '$numero' ,
						complemento	= '$complemento' ,
						bairro		= '$bairro' ,
						cep			= '$cep' ,
						cidade		= $cidade 
					WHERE tbl_cliente.cliente = $cliente";
			$res = pg_exec ($con,$sql);
		}else{
			$sql = "INSERT INTO tbl_cliente (
						nome,
						cpf,
						fone,
						endereco,
						numero,
						complemento,
						bairro,
						cep,
						cidade
					) VALUES (
						'$nome' ,
						'$cpf' ,
						'$fone' ,
						'$endereco' ,
						'$numero' ,
						'$complemento' ,
						'$bairro' ,
						'$cep' ,
						$cidade 
					)";
			$res = pg_exec ($con,$sql);

			$sql = "SELECT currval ('seq_cliente')";
			$res = pg_exec ($con,$sql);
			$cliente = pg_result ($res,0,0);
		}
		$sql = "UPDATE tbl_os SET cliente = $cliente, consumidor_nome = '$nome', consumidor_cpf = '$cpf' WHERE os = $os AND posto = $login_posto";
		$res = pg_exec ($con,$sql);
	}


	#----------- Dados da Revenda -------------
	$cidade = strtoupper (trim ($_POST ['revenda_cidade']));
	$estado = strtoupper (trim ($_POST ['revenda_estado']));

	if (strlen ($cidade) > 0 AND strlen ($estado) > 0 ) {
	
		$sql = "SELECT * FROM tbl_cidade WHERE nome = '$cidade' AND estado = '$estado'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$cidade = pg_result ($res,0,cidade);
		}else{
			$sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade','$estado')";
			$res = pg_exec ($con,$sql);
			$sql = "SELECT currval ('seq_cidade')";
			$res = pg_exec ($con,$sql);
			$cidade = pg_result ($res,0,0);
		}

		$nome		= trim ($_POST['revenda_nome']) ;
		$cnpj		= trim ($_POST['revenda_cnpj']) ;
		$fone		= trim ($_POST['revenda_fone']) ;
		$endereco	= trim ($_POST['revenda_endereco']) ;
		$numero		= trim ($_POST['revenda_numero']) ;
		$complemento= trim ($_POST['revenda_complemento']) ;
		$bairro		= trim ($_POST['revenda_bairro']) ;
		$cep		= trim ($_POST['revenda_cep']) ;

		$cnpj = str_replace (".","",$cnpj);
		$cnpj = str_replace ("-","",$cnpj);
		$cnpj = str_replace ("/","",$cnpj);
		$cnpj = str_replace (",","",$cnpj);
		$cnpj = str_replace (" ","",$cnpj);

		$cep = str_replace (".","",$cep);
		$cep = str_replace ("-","",$cep);
		$cep = str_replace ("/","",$cep);
		$cep = str_replace (",","",$cep);
		$cep = str_replace (" ","",$cep);


		$sql = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$cnpj'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$revenda = pg_result ($res,0,revenda);

			$sql = "UPDATE tbl_revenda SET
						nome		= '$nome' ,
						cnpj		= '$cnpj' ,
						fone		= '$fone' ,
						endereco	= '$endereco' ,
						numero		= '$numero' ,
						complemento	= '$complemento' ,
						bairro		= '$bairro' ,
						cep			= '$cep' ,
						cidade		= $cidade 
					WHERE tbl_revenda.revenda = $revenda";
			$res = pg_exec ($con,$sql);
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
						'$nome' ,
						'$cnpj' ,
						'$fone' ,
						'$endereco' ,
						'$numero' ,
						'$complemento' ,
						'$bairro' ,
						'$cep' ,
						$cidade 
					)";
			$res = pg_exec ($con,$sql);

			$sql = "SELECT currval ('seq_revenda')";
			$res = pg_exec ($con,$sql);
			$revenda = pg_result ($res,0,0);
		}

		$sql = "UPDATE tbl_os SET revenda = $revenda, revenda_nome = '$nome', revenda_cnpj = '$cnpj' WHERE os = $os AND posto = $login_posto";
		$res = pg_exec ($con,$sql);
	}

	#---------------- Abre janela de Imprimir OS ----------------
	$imprimir_os = $_POST ['imprimir_os'];
	if ($imprimir_os == "imprimir") {
		header ("Location: os_item_dynacom.php?os=$os&imprimir=1");
		exit;
	}else{
		header ("Location: os_item_dynacom.php?os=$os");
		exit;
	}
}


#------------ Le OS da Base de dados ------------#
$os = $_GET['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
					tbl_os.serie                                                     ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.nota_fiscal                                               ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios                                                ,
					tbl_produto.referencia                                           ,
					tbl_produto.linha                                                ,
					tbl_produto.descricao                                           
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			WHERE   tbl_os.os    = $os
			AND     tbl_os.posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$serie                       = pg_result ($res,0,serie);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf              = pg_result ($res,0,consumidor_cpf);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_cnpj                = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$referencia                  = pg_result ($res,0,referencia);
		$descricao                   = pg_result ($res,0,descricao);
		$linha                       = pg_result ($res,0,linha);

		#---------------- pesquisa se consumidor já tem cadastro ---------------#
		$cpf = $consumidor_cpf;
		$cpf = str_replace (".","",$cpf);
		$cpf = str_replace ("-","",$cpf);
		$cpf = str_replace ("/","",$cpf);
		$cpf = str_replace (",","",$cpf);
		$cpf = str_replace (" ","",$cpf);

		$sql = "SELECT 
				tbl_cliente.cliente,
				tbl_cliente.nome,
				tbl_cliente.endereco,
				tbl_cliente.numero,
				tbl_cliente.complemento,
				tbl_cliente.bairro,
				tbl_cliente.cep,
				tbl_cliente.fone,
				tbl_cidade.nome AS cidade,
				tbl_cidade.estado 
				FROM tbl_cliente 
				LEFT JOIN tbl_cidade USING (cidade) WHERE tbl_cliente.cpf = '$cpf'";

		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$consumidor_cliente		= trim (pg_result ($res,0,cliente));
			$consumidor_fone		= trim (pg_result ($res,0,fone));
			$consumidor_endereco	= trim (pg_result ($res,0,endereco));
			$consumidor_numero		= trim (pg_result ($res,0,numero));
			$consumidor_complemento	= trim (pg_result ($res,0,complemento));
			$consumidor_bairro		= trim (pg_result ($res,0,bairro));
			$consumidor_cep			= trim (pg_result ($res,0,cep));
			$consumidor_cidade		= trim (pg_result ($res,0,cidade));
			$consumidor_estado		= trim (pg_result ($res,0,estado));
		}

		#---------------- pesquisa se Revenda já tem cadastro ---------------#
		$cnpj = $revenda_cnpj;
		$cnpj = str_replace (".","",$cnpj);
		$cnpj = str_replace ("-","",$cnpj);
		$cnpj = str_replace ("/","",$cnpj);
		$cnpj = str_replace (",","",$cnpj);
		$cnpj = str_replace (" ","",$cnpj);

		$sql = "SELECT 
				tbl_revenda.revenda,
				tbl_revenda.nome,
				tbl_revenda.endereco,
				tbl_revenda.numero,
				tbl_revenda.complemento,
				tbl_revenda.bairro,
				tbl_revenda.cep,
				tbl_revenda.fone,
				tbl_cidade.nome AS cidade,
				tbl_cidade.estado 
				FROM tbl_revenda
				LEFT JOIN tbl_cidade USING (cidade) WHERE tbl_revenda.cnpj = '$cnpj'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 1) {
			$revenda_revenda	= trim (pg_result ($res,0,revenda));
			$revenda_fone		= trim (pg_result ($res,0,fone));
			$revenda_endereco	= trim (pg_result ($res,0,endereco));
			$revenda_numero		= trim (pg_result ($res,0,numero));
			$revenda_complemento= trim (pg_result ($res,0,complemento));
			$revenda_bairro		= trim (pg_result ($res,0,bairro));
			$revenda_cep		= trim (pg_result ($res,0,cep));
			$revenda_cidade		= trim (pg_result ($res,0,cidade));
			$revenda_estado		= trim (pg_result ($res,0,estado));
		}

	}

}

$title = "Dados Adicionais da Ordem de Serviço";
$layout_menu = 'os';
include "cabecalho.php";
?>

<?

include "javascript_pesquisas.php" ?>

<script language='javascript'>

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (campo, tipo)
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
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
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

/* ============= Função PESQUISA REVENDA ====================
Nome da Função : fnc_pesquisa_REVENDA (campo, tipo)
===========================================================*/
function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
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


/* ============= Função FORMATA CPF =============================
Nome da Função : formata_cpf (cpf, form)
		Formata o Campo de CPF a medida que ocorre a digitação
		Parâm.: cpf (numero), form (nome do form)
=================================================================*/
function formata_cpf(cpf, form){
	var mycpf = '';
		mycpf = mycpf + cpf;
		myrecord = "consumidor_cpf";
		myform = form;
		
		if (mycpf.length == 3){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 7){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 11){
			mycpf = mycpf + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
}


</script>

<style type="text/css">

.txt {
		font: x-small Arial, Verdana, Geneva, Helvetica, sans-serif;
		font-weight: bold;
		text-align: center;
		color: #000000;
}

.top {
		font: x-small Arial, Verdana, Geneva, Helvetica, sans-serif;
		font-weight: bold;
		text-align: center;
		background-color: #D9E2EF;
		color: #000000;
}

.txt1 {
		font: x-small Arial, Verdana, Geneva, Helvetica, sans-serif;
		font-weight: bold;
		text-align: center;
		color: #000000;
}

</style>

<!-- ------------- Formulário ----------------- -->
<!-- ------------- INFORMAÇÕES DA ORDEM DE SERVIÇO------------------ -->
<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="os" value="<? echo $_GET['os'] ?>">
<input type="hidden" name="cliente" value="<? echo $consumidor_cliente ?>">
<input type="hidden" name="revenda" value="<? echo $revenda_revenda ?>">

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr bgcolor='#cccccc'>
	<td class="top">Informações sobre a Ordem de Serviço</td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">OS Fabricante</td>

	<td class="txt">Abertura</td>

	<td class="txt">Produto</td>

	<td class="txt">Nº Série</td>

</tr>

<tr>
	<td class="txt1"><? echo $sua_os ?></td>

	<td  class="txt1"><? echo $data_abertura ?></td>

	<td  class="txt1"><? echo $referencia . " - " . substr ($descricao,0,15) ?></td>

	<td  class="txt1"><? echo $serie ?></td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">Aparência do Produto</td>

	<td class="txt">Acessórios</td>

	<td class="txt">Defeito Reclamado</td>

</tr>

<tr>
	<td class="txt1"><? echo $aparencia_produto ?></td>

	<td class="txt1"><? echo $acessorios ?></td>

	<td class="txt1"><select name="defeito_reclamado" size="1">
			<?
			$sql = "SELECT *
					FROM   tbl_defeito_reclamado
					JOIN   tbl_linha USING (linha)
					WHERE  tbl_defeito_reclamado.linha = $linha
					AND    tbl_linha.fabrica           = $login_fabrica;";
			$res = pg_exec ($con,$sql) ;
			if (pg_numrows ($res) == 0) {
				$sql = "SELECT *
						FROM   tbl_defeito_reclamado
						JOIN   tbl_linha USING (linha)
						WHERE  tbl_linha.fabrica = $login_fabrica;";
				$res = pg_exec ($con,$sql) ;
			}
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option ";
				if ($defeito_reclamado == pg_result ($res,$i,defeito_reclamado) ) echo " selected ";
				echo " value='" . pg_result ($res,$i,defeito_reclamado) . "'>" ;
				echo pg_result ($res,$i,descricao) ;
				echo "</option>";
			}
			?>
			</select>
	</td>
</tr>
</table>

<p>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="top">Informações sobre o Consumidor</td>
</tr>
</table>


<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">Nome</td>

	<td class="txt">CPF</td>

	<td class="txt">Fone</td>

	<td class="txt">CEP</td>
</tr>

<tr>
	<td class="txt1">
			<input class="frm" type="text" name="consumidor_nome" size="30" maxlength="50" value="<? echo $consumidor_nome ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'>
	</td>

	<td class="txt1">
			<input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="14" value="<? echo $consumidor_cpf ?>" onKeyUp="formata_cpf(this.value, 'frm_os')" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'>
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_fone"   size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_cep"   size="10" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');">
	</td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">Endereço</td>

	<td class="txt">Número</td>

	<td class="txt">Compl.</td>

	<td class="txt">Bairro</td>

	<td class="txt">Cidade</td>

	<td class="txt">Estado</td>
</tr>

<tr>
	<td class="txt1">
		<input class="frm" type="text" name="consumidor_endereco"   size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_numero"   size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_complemento"   size="15" maxlength="30" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_bairro"   size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');">
	</td>
</tr>
</table>

<p>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td  class="top">Informações sobre a Revenda</td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">Razão Social</td>

	<td class="txt">CNPJ</td>

	<td class="txt">Fone</td>

	<td class="txt">CEP</td>
</tr>

<tr>
	<td class="txt1">
			<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")'>
	</td>

	<td class="txt1">
			<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")'>
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_fone"   size="15" maxlength="20" value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_cep"   size="10" maxlength="10" value="<? echo $revenda_cep ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');">
	</td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">Endereço</td>

	<td class="txt">Número</td>

	<td class="txt">Compl.</td>

	<td class="txt">Bairro</td>

	<td class="txt">Cidade</td>

	<td class="txt">Estado</td>

</tr>

<tr>
	<td class="txt1">
		<input class="frm" type="text" name="revenda_endereco"   size="30" maxlength="60" value="<? echo $revenda_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço da Revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_numero"   size="10" maxlength="20" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço da revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_complemento"   size="15" maxlength="30" value="<? echo $revenda_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço da revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_bairro"   size="15" maxlength="30" value="<? echo $revenda_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro da revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_cidade"   size="15" maxlength="50" value="<? echo $revenda_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade da revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_estado"   size="2" maxlength="2" value="<? echo $revenda_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado da revenda.');">
	</td>

</tr>
</table>

<p>

<input type='hidden' name='btn_acao' value=''>
<center>
<input type='checkbox' name='imprimir_os' value='imprimir'> Imprimir OS
&nbsp;&nbsp;&nbsp;&nbsp;
<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0'>
</center>

</form>

<p>
<p>

<div id="footer"><hr>
	Telecontrol Networking Ltda & <? echo $login_fabrica_nome ?>- 2004 - Deus é o Provedor.<br>
	<a  href="#">www.telecontrol.com.br</a>
</div>
<BODY>
<html>
