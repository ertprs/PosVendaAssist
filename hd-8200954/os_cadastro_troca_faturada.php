<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_POST["os"]) > 0) $os = trim($_POST["os"]);
if (strlen($_GET["os"]) > 0)  $os = trim($_GET["os"]);

$btn_acao = trim (strtoupper ($_POST['btn_acao']));

$msg_erro = "";

#------------ Grava dados da Troca ---------
if ($btn_acao == "CONTINUAR") {
	
	$defeito_reclamado = trim ($_POST['defeito_reclamado']);
	if (strlen ($defeito_reclamado) == 0) $defeito_reclamado = "null";
	
	$motivo_troca = trim ($_POST['motivo_troca']);
	if (strlen ($motivo_troca) == 0) $motivo_troca = "null";
	
	$sql = "UPDATE tbl_os SET
					defeito_reclamado = $defeito_reclamado,
					motivo_troca      = $motivo_troca
			WHERE  tbl_os.os      = $os
			AND    tbl_os.posto   = $login_posto
			and    tbl_os.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
	
	if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);
	
	if (strlen ($msg_erro) == 0){
		header("Location:os_finalizada.php?os=$os");
		exit;
	}
	
}

$title = "Dados da Troca faturada da Ordem de Serviço";
$layout_menu = 'os';
include "cabecalho.php";

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                   ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura   ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento ,
					tbl_os.serie                                                    ,
					tbl_os.codigo_fabricacao                                        ,
					tbl_os.consumidor_nome                                          ,
					tbl_os.consumidor_cpf                                           ,
					tbl_os.consumidor_fone                                          ,
					tbl_os.revenda_nome                                             ,
					tbl_os.revenda_cnpj                                             ,
					tbl_os.nota_fiscal                                              ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf         ,
					tbl_os.aparencia_produto                                        ,
					tbl_os.acessorios                                               ,
					tbl_os.defeito_reclamado_descricao                              ,
					tbl_os.defeito_reclamado                                        ,
					tbl_defeito_reclamado.descricao  AS descricao_defeito_reclamado ,
					tbl_os.defeito_constatado                                       ,
					tbl_defeito_constatado.descricao AS descricao_defeito_constatado,
					tbl_os.motivo_troca                                             ,
					tbl_motivo_troca.descricao       AS descricao_motivo_troca      ,
					tbl_os.consumidor_revenda                                       ,
					tbl_produto.produto                                             ,
					tbl_produto.referencia                                          ,
					tbl_produto.linha                                               ,
					tbl_produto.descricao                                           ,
					tbl_produto.voltagem                                            ,
					tbl_posto_fabrica.codigo_posto                                  
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto_fabrica ON  tbl_os.posto              = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			LEFT JOIN tbl_defeito_reclamado                   ON tbl_defeito_reclamado.defeito_reclamado   = tbl_os.defeito_reclamado
			LEFT JOIN tbl_defeito_constatado                  ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			LEFT JOIN tbl_defeito_constatado tbl_motivo_troca ON tbl_motivo_troca.defeito_constatado       = tbl_os.motivo_troca
			WHERE   tbl_os.os    = $os
			AND     tbl_os.posto = $login_posto
			AND     tbl_os.troca_faturada IS TRUE";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$sua_os                       = pg_result ($res,0,sua_os);
		$data_abertura                = pg_result ($res,0,data_abertura);
		$serie                        = pg_result ($res,0,serie);
		$codigo_fabricacao            = pg_result ($res,0,codigo_fabricacao);
		$consumidor_nome              = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf               = pg_result ($res,0,consumidor_cpf);
		$consumidor_fone              = pg_result ($res,0,consumidor_fone);
		$revenda_cnpj                 = pg_result ($res,0,revenda_cnpj);
		$revenda_cnpj                 = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		$revenda_nome                 = pg_result ($res,0,revenda_nome);
		$nota_fiscal                  = pg_result ($res,0,nota_fiscal);
		$data_nf                      = pg_result ($res,0,data_nf);
		$aparencia_produto            = pg_result ($res,0,aparencia_produto);
		$acessorios                   = pg_result ($res,0,acessorios);
		$produto                      = pg_result ($res,0,produto);
		$referencia                   = pg_result ($res,0,referencia);
		$descricao                    = pg_result ($res,0,descricao);
		$voltagem                     = pg_result ($res,0,voltagem);
		$linha                        = pg_result ($res,0,linha);
		$defeito_reclamado_descricao  = pg_result ($res,0,defeito_reclamado_descricao);
		$defeito_reclamado            = pg_result ($res,0,defeito_reclamado);
		$descricao_defeito_reclamado  = pg_result ($res,0,descricao_defeito_reclamado);
		$defeito_constatado           = pg_result ($res,0,defeito_constatado);
		$descricao_defeito_constatado = pg_result ($res,0,descricao_defeito_constatado);
		$motivo_troca                 = pg_result ($res,0,motivo_troca);
		$descricao_motivo_troca       = pg_result ($res,0,descricao_motivo_troca);
		$consumidor_revenda           = pg_result ($res,0,consumidor_revenda);
		$codigo_posto                 = pg_result ($res,0,codigo_posto);
		
		#---------------- pesquisa se consumidor já tem cadastro ---------------#
		
		$cpf = $consumidor_cpf;
		$cpf = str_replace (".","",$cpf);
		$cpf = str_replace ("-","",$cpf);
		$cpf = str_replace ("/","",$cpf);
		$cpf = str_replace (",","",$cpf);
		$cpf = str_replace (" ","",$cpf);
		
		if (strlen ($cpf) > 0) {
			$sql = "SELECT	tbl_cliente.cliente,
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
					WHERE tbl_cliente.cpf = '$cpf'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$consumidor_cliente		= trim (pg_result ($res,0,cliente));
				$consumidor_fone		= trim (pg_result ($res,0,fone));
				$consumidor_nome		= trim (pg_result ($res,0,nome));
				$consumidor_endereco	= trim (pg_result ($res,0,endereco));
				$consumidor_numero		= trim (pg_result ($res,0,numero));
				$consumidor_complemento	= trim (pg_result ($res,0,complemento));
				$consumidor_bairro		= trim (pg_result ($res,0,bairro));
				$consumidor_cep			= trim (pg_result ($res,0,cep));
				$consumidor_rg			= trim (pg_result ($res,0,rg));
				$consumidor_cidade		= trim (pg_result ($res,0,cidade));
				$consumidor_estado		= trim (pg_result ($res,0,estado));
				$consumidor_contrato	= trim (pg_result ($res,0,contrato));
			}
		}
		
		#---------------- pesquisa se Revenda já tem cadastro ---------------#
		$cnpj = $revenda_cnpj;
		$cnpj = str_replace (".","",$cnpj);
		$cnpj = str_replace ("-","",$cnpj);
		$cnpj = str_replace ("/","",$cnpj);
		$cnpj = str_replace (",","",$cnpj);
		$cnpj = str_replace (" ","",$cnpj);
		
		if (strlen ($cnpj) > 0) {
			$sql = "SELECT 	tbl_revenda.revenda,
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
				$revenda_nome		= trim (pg_result ($res,0,nome));
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
?>

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

<?
if (strlen ($msg_erro) > 0){
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="middle" align="center" class='error'>
<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?
}
?>

<!-- ------------- Formulário ----------------- -->

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="os" value="<? echo $os ?>">

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">OS</td>
	<td class="txt">Consumidor</td>
	<td class="txt">Produto</td>
	<td class="txt">N. Série</td>
</tr>
<tr class="top">
	<td><? echo $codigo_posto.$sua_os; ?></td>
	<td class="txt" align="left"><? echo $$consumidor_nome; ?></td>
	<td class="txt"><? echo $referencia." - ".$descricao." ".$voltagem; ?></td>
	<td class="txt"><? echo $serie; ?></td>
</tr>
</table>

<br>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr bgcolor='#cccccc'>
	<td class="top">Para que seja efetuada a troca faturada, preencha os dados abaixo.</td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">Defeito reclamado</td>
	<td align='left'>
<?
	$sql = "SELECT  tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao        
			FROM    tbl_defeito_reclamado
			JOIN    tbl_linha   ON tbl_linha.linha     = tbl_defeito_reclamado.linha
			JOIN    tbl_familia ON tbl_familia.familia = tbl_defeito_reclamado.familia
			JOIN    tbl_produto ON tbl_produto.familia = tbl_familia.familia
			WHERE   tbl_defeito_reclamado.familia = tbl_familia.familia
			AND     tbl_familia.fabrica           = $login_fabrica
			AND     tbl_produto.produto           = $produto";
	$resD = pg_exec ($con,$sql) ;
	
	if (@pg_numrows ($resD) > 0) {
		echo "<select name='defeito_reclamado' size='1'>";
		for ($i = 0 ; $i < pg_numrows ($resD) ; $i++ ) {
			echo "<option ";
			if ($defeito_reclamado == pg_result ($resD,$i,defeito_reclamado) ) echo " selected ";
			echo " value='" . pg_result ($resD,$i,defeito_reclamado) . "'>" ;
			echo pg_result ($resD,$i,descricao) ;
			echo "</option>";
		}
		echo "</select>";
	}
?>
	</td>
</tr>

<tr class="top">
	<td class="txt">Motivo da troca</td>
	<td align='left'>
		<select name="motivo_troca" size="1" style='width:550px'>
			<option selected></option>
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

<p>

<input type='hidden' name='btn_acao' value=''>
<center>
<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style="cursor:pointer;">
</center>

</form>

<p>
<?
	}else{
		echo "<BR>OS DE TROCA FATURADA NÃO ENCONTRADA.<BR>";
	}
}
?>
<p>
<? include "rodape.php";?>