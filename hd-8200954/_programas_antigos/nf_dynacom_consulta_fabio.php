<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$extrato = $_GET['extrato'];

if ($login_fabrica <> 2 OR strlen($extrato) == 0){
	header("Location: os_extrato.php");
	exit;
}

$title = "DADOS PARA EMISSÃO DE NOTA FISCAL";

$layout_menu = "os";
include "cabecalho.php";

?>

<style type='text/css'>
body {
	text-align: center;

		}

.cabecalho {
	background-color: #D9E2EF;
	color: black;
	border: 2px SOLID WHITE;
	font-weight: normal;
	font-size: 10px;
	text-align: left;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 11px;
	font-weight: bold;
	text-align: justify;
}


/*========================== MENU ===================================*/

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}

</style>

<SCRIPT>
	displayText("<center><br><font color='#ff0000'>EMITIR NOTA FISCAL CONFORME MODELO ABAIXO E ENVIAR JUNTAMENTE COM AS PEÇAS.</font><br><br></center>");
</SCRIPT>
<br />

<!-- //##################################### -->

<table width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD COLSPAN='3'><B>DADOS DA EMPRESA DESTINATÁRIA</B></TD>
</TR>
<TR class='cabecalho'>
	<TD>RAZÃO SOCIAL</TD>
	<TD>CNPJ</TD>
	<TD>IE</TD>
</TR>
<TR class='descricao'>
	<TD>Prodtel Comércio Ltda</TD>
	<TD>04.789.310/0001-98</TD>
	<TD>116.594.848.117</TD>
</TR>
<TR class='cabecalho'>
	<TD>ENDEREÇO</TD>
	<TD>CEP</TD>
	<TD>BAIRRO</TD>
</TR>
<TR class='descricao'>
	<TD>Rua Forte do Rio Branco,762 </TD>
	<TD>08340-140</TD>
	<TD>Pq. Industrial São Lourenço</TD>
</TR>
<TR class='cabecalho'>
	<TD>MUNICIPIO</TD>
	<TD>ESTADO</TD>
	<TD>TELEFONE</TD>
</TR>
<TR class='descricao'>
	<TD>São Paulo</TD>
	<TD>SP</TD>
	<TD>(11) 6117-2336</TD>
</TR>
</TABLE>
<BR>
<table width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD COLSPAN='2'><B>DADOS IMPORTANTES PARA A NOTA</B></TD>
</TR>
<TR class='cabecalho'>
	<TD>NATUREZA DA OPERAÇÃO</TD>
	<TD>CFOP</TD>
</TR>
<TR class='descricao'>
	<TD>DEVOLUÇÃO DE REPOSIÇÃO</TD>
	<TD>5949 ( dentro de São Paulo ) 6949 ( fora de São Paulo )</TD>
</TR>
<TR class='cabecalho'>
	<TD colspan=2>ICMS</TD>
</TR>
<TR class='descricao'>
	<TD colspan=2>Se não for isento, preencher conforme aliquota interestadual.</TD>
</TR>
</TABLE>
<BR>

<table width='700' border='1' cellpadding='0' cellspacing='2' bordercolor='#D9E2EF'>
<TR class='cabecalho'>
	<TD COLSPAN='5'><B>DADOS DOS ITENS DA NOTA FISCAL</B></TD>
</TR>
<?
// ITENS
if (strlen ($extrato) > 0) {
// alteração aqui
	if	($login_fabrica == 2) {
			$sql = "SELECT	tbl_peca.referencia    ,
							tbl_peca.descricao     ,
							(SELECT preco FROM tbl_tabela_item WHERE peca = tbl_os_item.peca AND tabela = tbl_posto_linha.tabela) AS preco ,
							tbl_os_item.qtde                                               ,
							to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
					FROM    tbl_os
					JOIN    tbl_os_extra             ON tbl_os.os                                = tbl_os_extra.os
					JOIN    tbl_produto              ON tbl_os.produto                           = tbl_produto.produto
					JOIN    tbl_os_produto           ON tbl_os.os                                = tbl_os_produto.os
					JOIN    tbl_os_item              ON tbl_os_produto.os_produto                = tbl_os_item.os_produto
					JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado  = tbl_os_item.servico_realizado
					JOIN    tbl_peca                 ON tbl_os_item.peca                         = tbl_peca.peca
					JOIN    tbl_extrato              ON tbl_extrato.extrato                      = tbl_os_extra.extrato
					JOIN    tbl_posto_linha          ON tbl_posto_linha.posto = tbl_os.posto AND (tbl_posto_linha.linha = tbl_produto.linha OR tbl_posto_linha.familia = tbl_produto.familia)
					WHERE   tbl_os_extra.extrato = $extrato
					AND     tbl_extrato.fabrica  = $login_fabrica
					AND     tbl_os_item.liberacao_pedido    IS NOT FALSE
					AND     tbl_peca.devolucao_obrigatoria      IS TRUE
					AND     tbl_servico_realizado.gera_pedido   IS TRUE
					AND     tbl_servico_realizado.troca_de_peca IS TRUE
					ORDER BY tbl_os_item.preco;";
// termino da alteracao
		} else {
		$sql = "SELECT	tbl_peca.referencia,
					tbl_peca.descricao ,
					SUM (tbl_os_item.qtde) AS qtde,
					SUM (tbl_os_item.preco) AS preco
			FROM    tbl_os
			JOIN    tbl_os_extra          ON tbl_os.os                               = tbl_os_extra.os
			JOIN    tbl_produto           ON tbl_os.produto                          = tbl_produto.produto
			JOIN    tbl_os_produto        ON tbl_os.os                               = tbl_os_produto.os
			JOIN    tbl_os_item           ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
			JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			JOIN    tbl_peca              ON tbl_os_item.peca                        = tbl_peca.peca
			JOIN    tbl_extrato           ON tbl_extrato.extrato                     = tbl_os_extra.extrato
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_extrato.fabrica  = $login_fabrica
			AND     tbl_os_item.liberacao_pedido    IS NOT FALSE
			AND     tbl_peca.devolucao_obrigatoria      IS TRUE
			AND     tbl_servico_realizado.gera_pedido   IS TRUE
			AND     tbl_servico_realizado.troca_de_peca IS TRUE
			GROUP BY tbl_peca.referencia, tbl_peca.descricao, tbl_os_item.preco
			ORDER BY SUM (tbl_os_item.qtde);";
		}
//	echo $sql;
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {

		echo "<TR class='cabecalho'>";
		echo "<TD>DESCRIÇÃO</TD>";
		echo "<TD>QTDE</TD>";
		echo "<TD>UNITÁRIO</TD>";
		echo "<TD>ICMS</TD>";
		echo "<TD>TOTAL</TD>";
		echo "</TR>";

		$total_nota = 0;

		for ($i=0; $i< pg_numrows ($res); $i++){
			$referencia = pg_result ($res,$i,referencia);
			$descricao  = pg_result ($res,$i,descricao);
			$qtde       = pg_result ($res,$i,qtde);
			$preco      = pg_result ($res,$i,preco);
			$icms       = 0;
			$total_peca = $qtde * $preco;

			echo "<TR class='descricao'>";
			echo "<TD>$descricao&nbsp;</TD>";
			echo "<TD>$qtde&nbsp;</TD>";
			echo "<TD>$preco&nbsp;</TD>";
			echo "<TD>$icms&nbsp;</TD>";
			echo "<TD>$total_peca&nbsp;</TD>";
			echo "</TR>";

			$total_nota = $total_nota + $total_peca;

		}

		echo "<TR class='descricao'>";
		echo "<TD colspan=4>Total da Nota Fiscal</TD>";
		echo "<TD>$total_nota &nbsp;</TD>";
		echo "</TR>";

	}else{
		echo "<TR class='descricao'>";
		echo "<TD colspan=5><center>Extrato sem itens para emissão de Nota Fiscal</center></TD>";
		echo "</TR>";
	}

echo "</TABLE>";


##################### CONFIRMA NOTA DE DEVOLUCAO
if (strlen(trim($_POST['txt_nota_fiscal'])) > 0 AND strlen(trim($_POST['txt_valor'])) > 0) {
	$txt_nota=trim($_POST['txt_nota_fiscal']);
	$txt_valor=trim($_POST['txt_valor']);

	$res = @pg_exec($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_extrato_devolucao
				(extrato,nota_fiscal,total_nota,serie,linha) 
				VALUES ($extrato,'$txt_nota',$txt_valor,'FN',335)";
	$res = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}			
	else {
		//$res = @pg_exec ($con,"ROLLBACK TRANSACTION"); //teste
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		echo "<script language='javascript'> opener.location='os_extrato_pecas_retornaveis_fabio.php'; this.close() </script>";
		exit();
	}
}
##################### FIM CONFIRMA NOTA DE DEVOLUCAO


	$query="SELECT extrato_devolucao,nota_fiscal FROM tbl_extrato_devolucao WHERE extrato=$extrato";
	$res_devolucao = pg_exec ($con,$query);
	if (pg_numrows($res_devolucao)==1){
		$ext  = pg_result ($res_devolucao,0,extrato_devolucao);
		$nota_fiscal  = pg_result ($res_devolucao,0,nota_fiscal);
	}
	if (strlen($nota_fiscal)>0){
		echo "<br>Nota Fiscal de Devolução:<b> $nota_fiscal</b>";
	}
	else{
		echo "<form name='frm_confim' method='post' action='$PHP_SELF?extrato=$extrato' onSubmit='javascript:if (confirm(\"Deseja continuar? A Nota Fiscal não poderá ser alterada\")) return true; else return false;'>";
		echo "<center><h2 style='padding:3px;text-align:center;font-size:14px;color:red;background-color:#FFCCCC;width:630px'>DEVOLUÇÃO DE PEÇAS OBRIGATÓRIA</h2></center>\n";
	
		echo "<b style='font-size:11px;color:#666;font-weight:normal'>É necessário o retorno dessas peças para à fábrica. <br>Os extratos estarão disponíveis somente após o retorno</b><br><b style='font-size:11px;color:#666;'>Emitir nota fiscal conforme modelo acima e enviar juntamente com as peças</b><BR>\n";
	
		echo "<center><h2 style='padding:3px;text-align:center;font-size:14px;color:black;background-color:#D9E2EF;width:630px'>Total Nota Fiscal R$ ".number_format($total_nota,2)."</h2></center>\n";

	if (strlen($msg_erro) > 0) {
	echo "<b style='color:red;padding 0px 10px;font-size:12px'>Erro: $msg_erro</b><br><br>";
	}
		echo "<b style='font-size:12px'>NOTA FISCAL DE DEVOLUÇÃO: </b><input type='text' value='$txt_nota' name='txt_nota_fiscal' class='inpu' size='8' maxlength='6'> <input type='submit' value='Confirmar' class='butt'>\n";
		echo "<input type='hidden' name='txt_valor' value='$total_nota' >\n";
		echo "</form>";
	}


}
?>


<br>

<? include "rodape.php";?>