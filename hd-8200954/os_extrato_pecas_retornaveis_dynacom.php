<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$servico_realizado = trim($_GET['servico_realizado']);
if (strlen($servico_realizado) == 0) $servico_realizado = trim($_POST['servico_realizado']);

$msg_erro = "";

$layout_menu = "os";
$title = "Relação das Peças Para Devolução";


if (strlen($_GET["agrupar"]) > 0) {
	$agrupar = trim($_GET["agrupar"]);
}else{
	$agrupar = "true";
}

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:12px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:12px;
}
.cabecalho {
	background-color: #D9E2EF;
	color: black;
	/*border: 2px SOLID WHITE;*/
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
</style>

<p>
<?


	$query_extratos="SELECT
			tbl_extrato.extrato AS extrato
			FROM        tbl_extrato
			JOIN tbl_extrato_devolucao ON tbl_extrato_devolucao.extrato = tbl_extrato.extrato
			WHERE       tbl_extrato.fabrica = $login_fabrica
			AND         tbl_extrato.posto = $login_posto
			AND data_nf_recebida IS NULL";
	$res_extratos = pg_exec ($con,$query_extratos);
	$res_extratos_qtde=pg_numrows($res_extratos);
	$lista_de_extratos="";
	$ext_unitario=array();
	for ($i=0;$i<$res_extratos_qtde;$i++){
		$lista_de_extratos .= pg_result ($res_extratos,$i,extrato).",";
		array_push($ext_unitario,pg_result ($res_extratos,$i,extrato));
	}
	if ($res_extratos_qtde>0){
		$lista_de_extratos = substr($lista_de_extratos, 0, (strlen($lista_de_extratos)-1));
	}
	else{
		$query_extratos="SELECT
				tbl_extrato.extrato AS extrato
				FROM        tbl_extrato
				WHERE       tbl_extrato.fabrica = $login_fabrica
				AND         tbl_extrato.posto   = $login_posto
				AND tbl_extrato.extrato NOT IN (
					SELECT tbl_extrato_devolucao.extrato
					FROM tbl_extrato_devolucao
					JOIN tbl_extrato USING(extrato)
					WHERE tbl_extrato_devolucao.data_nf_recebida IS NOT NULL
					AND tbl_extrato.posto = $login_posto
					)
				AND tbl_extrato.extrato > 120054
				ORDER BY tbl_extrato.data_geracao DESC";
		$res_extratos = pg_exec ($con,$query_extratos);
		$res_extratos_qtde=pg_numrows($res_extratos);
		$ext_unitario=array();
		for ($i=0;$i<$res_extratos_qtde;$i++){
			$lista_de_extratos .= pg_result ($res_extratos,$i,extrato).",";
			array_push($ext_unitario,pg_result ($res_extratos,$i,extrato));
		}
		if ($res_extratos_qtde>0){
			$lista_de_extratos = substr($lista_de_extratos, 0, (strlen($lista_de_extratos)-1));
		}
		else{
			$lista_de_extratos=0;
		}
	}


if (strlen(trim($_POST['txt_nota_fiscal'])) > 0 AND strlen(trim($_POST['txt_valor'])) > 0) {
	$txt_nota=trim($_POST['txt_nota_fiscal']);
	$txt_valor=trim($_POST['txt_valor']);

	$res = @pg_exec($con,"BEGIN TRANSACTION");
	foreach($ext_unitario as $extrato_unico) {
		$sql = "UPDATE tbl_extrato_devolucao
				SET nota_fiscal='$txt_nota',
					total_nota=$txt_valor,
					serie='FN',
					linha=334,
					data_nf_envio=NOW()
				WHERE extrato=$extrato_unico";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con); 
	}

	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}			
	else {
		//$res = @pg_exec ($con,"ROLLBACK TRANSACTION"); //teste
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		echo "<script language='javascript'> window.location='$PHP_SELF' </script>";
		exit();
	}
}




if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"650\" align='center' border=0>";
	echo "<TR>";
	
	echo "<TD align='center' class='error'>$msg_erro</TD>";
	
	echo "</TR>";
	echo "</TABLE>";
}



echo "<table border='0' align='center' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#485989' width='650'>";
echo "<tr class='Titulo' height='25' >";
echo "<td >PEÇAS COM DEVOLUÇÃO OBRIGATÓRIA</td></tr>";
echo "</table>";	
	
// echo "<center>";
// echo "<p style='font-size:12px;color:#000;width:600px;'>";
// echo "<b style='font-size:14px;color:#000;'>Conforme determina a legislação local</b><BR>\n";
// echo "Para toda nota fiscal de peças enviadas em garantia deve haver nota fiscal de devolução de todas as peças nos mesmos valores, quantidades e com os mesmos destaques de impostos obrigatoriamente.";
// echo "<br>";
// echo "</p>";
// echo "</center>";

//echo "<br>";	
// echo "<TABLE width=\"650\" align='center' border=0>";
// echo "<TR class='menu_top'>\n";
// echo "<TD align='center' width='50%'><a href='$PHP_SELF?agrupar=true'><font color='#000000'>Agrupar por peça</font></a></TD>\n";
// echo "<TD align='center' width='50%'><a href='$PHP_SELF?agrupar=false'><font color='#000000'>Não agrupar</font></a></TD>\n";
// echo "</TR>";
// echo "</TABLE>";

echo "<p>";

	$sql = "SELECT
		tbl_peca.referencia        AS peca_referencia                      ,
		tbl_peca.descricao         AS peca_nome                            ,
		(SELECT preco FROM tbl_tabela_item WHERE peca = tbl_os_item.peca AND tabela = tbl_posto_linha.tabela) AS precoX ,
		sum(tbl_os_item.qtde)      AS qtde,
		tbl_peca.devolucao_obrigatoria AS devolucao
		FROM    tbl_os
		JOIN    tbl_os_extra             ON tbl_os.os                               = tbl_os_extra.os
		JOIN    tbl_produto              ON tbl_os.produto                          = tbl_produto.produto
		JOIN    tbl_os_produto           ON tbl_os.os                               = tbl_os_produto.os
		JOIN    tbl_os_item              ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
		JOIN    tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
		JOIN    tbl_peca                 ON tbl_os_item.peca                        = tbl_peca.peca
		JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
		JOIN    tbl_posto_linha          ON tbl_posto_linha.posto = tbl_os.posto AND (tbl_posto_linha.linha = tbl_produto.linha OR tbl_posto_linha.familia = tbl_produto.familia)
		LEFT JOIN tbl_extrato_devolucao ON tbl_extrato_devolucao.extrato = tbl_extrato.extrato
		WHERE   tbl_os_extra.extrato IN ($lista_de_extratos)
		AND     tbl_os.fabrica  = $login_fabrica
		AND     tbl_os.posto = $login_posto
		AND     tbl_extrato_devolucao.data_nf_recebida IS NULL
		AND     tbl_os_item.liberacao_pedido    IS NOT FALSE
		AND     tbl_servico_realizado.troca_de_peca IS TRUE
		GROUP BY tbl_peca.devolucao_obrigatoria,
					tbl_peca.referencia   ,
					precoX ,
					tbl_peca.descricao
		ORDER BY   devolucao";
		//AND 	tbl_os.os > 2086111 -> para pegar OS somente apartir de 2007-01-01

	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);

	$lista_pecas ="";
	if ($totalRegistros > 0){

		$tmp_total_qtde=0;
		$colspan = "3";

		$lista_pecas .= "<center><table width='700' border='2' cellpadding='3' cellspacing='0' bordercolor='#999' style='border-collapse:collapse;font-size:12px' >";
		$lista_pecas .= "<tr class='cabecalho' height='15' >\n";
		$lista_pecas .= "<TD ><b>PEÇA</b></TD>\n";
		$lista_pecas .= "<TD ><b>QTDE</b></TD>\n";
		$lista_pecas .= "<TD><b>UNITÁRIO</b></TD>\n";
		$lista_pecas .= "<TD ><b>ICMS</b></TD>\n";
		$lista_pecas .= "<TD ><b>TOTAL</b></TD>\n";
		$lista_pecas .= "</TR>\n";	
	
		$soma_preco = 0;
		$imprimi_devolucao_fisicamente=0;
		$imprimi_devolucao_nao_fisicamente=0;
		$qtde_pecas_retornaveis=0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$devolucao_obrigatoria = trim(pg_result ($res,$i,devolucao));
			if ( $devolucao_obrigatoria=='t' AND $imprimi_devolucao_fisicamente==0){
				$imprimi_devolucao_fisicamente=1;
				$lista_pecas .= "<TR class='table_line' bgcolor='#D9E2EF' >\n";
				$lista_pecas .= "<TD align='left' nowrap colspan='5'><b>Peças que precisam ser devolvidas fisicamente</b></TD>\n";
				$lista_pecas .= "</TR>\n";
			}
			if ( $devolucao_obrigatoria=='f' AND $imprimi_devolucao_nao_fisicamente==0){
				$imprimi_devolucao_nao_fisicamente=1;
				$lista_pecas .= "<TR class='table_line' bgcolor='#D9E2EF' >\n";
				$lista_pecas .= "<TD align='left' nowrap colspan='5'><b>Peças que não precisam ser devolvidas fisicamente</b></TD>\n";
				$lista_pecas .= "</TR>\n";
			}
			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$preco_unitario		= trim(pg_result ($res,$i,precoX));
			$qtde				= trim(pg_result ($res,$i,qtde));
			$preco=$preco_unitario;

			if($qtde>1){$preco = $preco_unitario*$qtde;}

			$soma_preco			= $soma_preco + $preco;
			$consumidor			= strtoupper($consumidor);
			$preco				= number_format($preco,2,",",".");
			$preco_unitario			= number_format($preco_unitario,2,",",".");

			if ($devolucao_obrigatoria=='t'){
				$qtde_pecas_retornaveis+=$qtde;
			}

			$tmp_total_qtde+=$qtde;
			$cor = "#FCF9DA";
			$cor = "#FFF";
			$btn = 'amarelo';
			
			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}
			
			if (strlen ($sua_os) == 0) $sua_os = $os;
			
			$lista_pecas .= "<TR class='table_line' style='background-color: $cor;'>\n";
			$lista_pecas .= "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			$lista_pecas .= "<TD align='center' nowrap>$qtde</TD>\n";
			$lista_pecas .= "<TD align='right' nowrap>$preco_unitario</TD>\n";
			$lista_pecas .= "<TD align='center' nowrap>0</TD>\n";
			$lista_pecas .= "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";
			$lista_pecas .= "</TR>\n";
		}
		$lista_pecas .= "<TR class='Conteudo' bgcolor='#D9E2EF' style='padding:10px'>\n";
		$colspan = '1';
		$lista_pecas .= "<TD align='center' nowrap colspan='$colspan'><b>TOTAL</b></TD>\n";
		$lista_pecas .= "<TD align='center' nowrap><b>$tmp_total_qtde</b></TD>\n";
		$lista_pecas .= "<TD align='center' nowrap></TD>\n";
		$lista_pecas .= "<TD align='center' nowrap></TD>\n";
		$lista_pecas .= "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".") ."</TD>\n";
		$lista_pecas .= "</TR>\n";
		$lista_pecas .= "</TABLE></center>\n";
	}
	

	
	$lista_pecas .="<br>";


	if ($qtde_pecas_retornaveis>=7 AND $soma_preco>500){
		echo "<form name='frm_confim' method='post' action='$PHP_SELF' onSubmit='javascript:if (confirm(\"Deseja continuar? A Nota Fiscal não poderá ser alterada\")) return true; else return false;'>";
		//echo "<b style='font-size:14px;color:red'>É NECESSÁRIO ENVIAR ESTAS PEÇAS À FÁBRICA</b><BR>\n";
		echo "<center><h2 style='padding:3px;text-align:center;font-size:13px;color:red;background-color:#FFCCCC;width:630px'>LIMITE DE 7 PEÇAS E R$500,00 ATINGIDO!<br> É NECESSÁRIO O RETORNO DAS PEÇAS PARA  FÁBRICA.</h2></center>\n";

		echo "<b style='font-size:12px;color:#333;'>Emitir Nota Fiscal conforme modelo abaixo e enviar juntamente com as peças.<br>Os extratos estarão disponíveis somente após a devolução.</b><BR><br>\n";
		?>
		
		<center>
		<table width='700' border='2' cellpadding='3' cellspacing='0' bordercolor='#999' style='border-collapse:collapse;font-size:12px' >
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
		<table width='700' border='2' cellpadding='3' cellspacing='0' bordercolor='#999' style='border-collapse:collapse;font-size:12px' >
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
		</center>
		<BR>
		<?
		echo $lista_pecas;

		$query="SELECT DISTINCT nota_fiscal,
				to_char(data_nf_envio, 'DD/MM/YYYY') AS data_envio,
				to_char(data_nf_recebida, 'DD/MM/YYYY') AS data_recebida 
				FROM tbl_extrato_devolucao
				WHERE extrato IN ($lista_de_extratos)";
		$res = pg_exec ($con,$query);
		$res_qtde=pg_numrows($res);
		for ($i=0;$i<$res_qtde;$i++){
			$nota_fiscal = pg_result ($res,$i,nota_fiscal);
			$data_envio = pg_result ($res,$i,data_envio);
			$data_recebido = pg_result ($res,$i,data_recebida);
		}
		if (strlen($data_envio)>0 AND strlen($nota_fiscal)>0){
			if (strlen($data_recebido)>0){
				echo "Nota fiscal de devolução:<b> $nota_fiscal</b><br>Esta nota foi recebido pela fábrica em <b>$data_recebido</b>";
			}
			else{
				echo "Nota fiscal de devolução: <b>$nota_fiscal</b><br>Data do envio: <b>$data_envio</b><br><br><b style='font-weight:normal;font-size:12px;text-decoration:underline'>Aguarde a fábrica confimar seu recebimento e liberar os extratos";
			}

		}else{
			$query="SELECT extrato_devolucao,extrato,nota_fiscal,data_nf_envio,data_nf_recebida
					FROM tbl_extrato_devolucao
					WHERE extrato IN ($lista_de_extratos)";
			$res = pg_exec ($con,$query);
			$res_qtde=pg_numrows($res);
			if ($res_qtde==0){
				foreach($ext_unitario as $txt_extrato) {
					$sql = "INSERT INTO tbl_extrato_devolucao
							(extrato,serie,linha) 
							VALUES ($txt_extrato,'FN',334)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
			//echo "<center><h2 style='padding:3px;text-align:center;font-size:14px;color:black;background-color:#D9E2EF;width:630px'>Preencher a nota no valor de R$ ".number_format($soma_preco,2)."</h2></center>\n";
		
			echo "<b style='font-size:12px'>NOTA FISCAL DE DEVOLUÇÃO: </b><input type='text' value='' name='txt_nota_fiscal' class='inpu' size='8' maxlength='6'> <input type='submit' value='Confirmar' class='butt'>\n";
			echo "<input type='hidden' name='txt_valor' value='$soma_preco' >\n";
			echo "</form>";
		}
		

	}
	else {
		if ($tmp_total_qtde==0){
			//echo "<script language='javascript'> window.location='os_extrato.php';</script>";
			echo "<center><br><br><h2 style='padding:3px;text-align:center;font-size:14px;color:black;background-color:#D9E2EF;width:630px'>Não há peças com devolução obrigatória</h2><br><br></center>\n";
		}
		else{
			echo $lista_pecas;
			if ($login_fabrica == 2){
				echo "<center><h2 style='font-size:12px;background-color:#D9E2EF;color:red;width:630px;text-align:center;padding:2px 5px'>Aguardar acumular 7 peças e valor superior a R$500,00 para devolução para fábrica</h2></center>";

			}
		}
		?>
		<img src="imagens/btn_continuar.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Ver extratos" border='0' style="cursor:pointer;">
		<?
	}
?>

		

<p>
<p>

<? include "rodape.php"; ?>