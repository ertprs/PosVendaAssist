<?



include 'dbconfig.php';

include 'includes/dbconnect-inc.php';



$admin_privilegios="gerencia,call_center";

include 'autentica_admin.php';



if (strlen($_POST["btn_acao"]) > 0) $btn_acao = $_POST["btn_acao"];

else $btn_acao = $_GET["btn_acao"];



if (strlen($_POST["numero_os"]) > 0) $numero_os = $_POST["numero_os"];

else $numero_os = $_GET["numero_os"];



if (strlen($_POST["numero_nf"]) > 0) $numero_nf = $_POST["numero_nf"];

else $numero_nf = $_GET["numero_nf"];



if (strlen($_POST["numero_pedido"]) > 0) $numero_pedido = $_POST["numero_pedido"];

else $numero_pedido = $_GET["numero_pedido"];



if (strlen($_GET["acao"]) > 0) $acao = $_GET["acao"];



$layout_menu = "callcenter";

$title = "Consulta Simplificada";



include "cabecalho.php";



?>



<style type="text/css">

.menu_top {

	text-align: center;

	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;

	font-size: x-small;

	font-weight: bold;

	border: 1px solid;

	color:#ffffff;

	background-color: #596D9B

}

.table_line {

	text-align: left;

	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;

	font-size: 10px;

	font-weight: normal;

	border: 0px solid;

	background-color: #D9E2EF

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

</style>



<? include "javascript_pesquisas.php" ?>



<FORM NAME="frm_consulta" METHOD="POST" ACTION="<? $PHP_SELF ?>">

<TABLE width="450" align="center" border="0" cellspacing="0" cellpadding="2">

	<TR CLASS="menu_top">

		<TD colspan="5" align="center"><B>Consulta Simplificada</B></TD>

	</TR>

	<? if (strlen($btn_acao) > 0) { ?>

	<TR CLASS="table_line">

		<TD>&nbsp;</TD>

		<TD>Número da OS<br><b><? echo $numero_os ?></b>&nbsp;</TD>

		<TD>Número do Pedido<br><b><? echo $numero_pedido ?></b>&nbsp;</TD>

		<TD>Número da NF<br><b><? echo $numero_nf ?></b>&nbsp;</TD>

		<TD>&nbsp;</TD>

	</TR>

	<? }else{ ?>

	<TR CLASS="table_line">

		<TD>&nbsp;</TD>

		<TD>Número da OS<br><INPUT TYPE="text" NAME="numero_os" size="17" value="<? echo $numero_os ?>"></TD>

		<TD>Número do Pedido<br><INPUT TYPE="text" NAME="numero_pedido" size="17" value="<? echo $numero_pedido ?>"></TD>

		<TD>Número da NF<br><INPUT TYPE="text" NAME="numero_nf" size="17" value="<? echo $numero_nf ?>"></TD>

		<TD>&nbsp;</TD>

	</TR>

	<TR CLASS="table_line">

		<TD colspan="5" align="center">

			<INPUT TYPE="hidden" NAME="btn_acao" value="">

			<IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript: if (document.frm_consulta.btn_acao.value=='') { document.frm_consulta.btn_acao.value='exibir'; document.frm_consulta.submit() } else { alert('Aguarde submissão') }" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar" border='0' style='cursor:pointer;'>

		</TD>

	</TR>

	<? } ?>

</TABLE>

</FORM>



<?

if ($btn_acao == 'exibir') {

	echo "<p align='center'><a href='$PHP_SELF'>Clique aqui para efetuar nova consulta</a></p>";

	if (strlen($numero_os) == 0 AND strlen($numero_pedido) == 0 AND strlen($numero_nf) == 0) {

		echo "<TABLE width='700' height='50'>\n<TR>\n<TD align='center'>Nenhum campo foi preenchido.</TD>\n</TR>\n</TABLE>";

	}else{

		$sql =	"SELECT DISTINCT tbl_os.os          ,
						tbl_os.sua_os               ,
						tbl_faturamento.nota_fiscal ,
						tbl_os_item.pedido
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_faturamento ON tbl_os_item.pedido = tbl_faturamento.pedido
				WHERE tbl_os.fabrica=$login_fabrica";

		if (strlen($numero_os) > 0)

			$sql .= " AND tbl_os.sua_os ILIKE '%$numero_os%'";

		if (strlen($numero_pedido) > 0)

			$sql .= " AND tbl_os_item.pedido ILIKE '%$numero_pedido%'";

		if (strlen($numero_nf) > 0)

			$sql .= " AND tbl_faturamento.nota_fiscal ILIKE '%$numero_nf%'";

#		echo nl2br($sql);

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0) {

			echo "<TABLE width='700' height='50'>\n<TR>\n<TD align='center'>Nenhum resultado encontrado.</TD>\n</TR>\n</TABLE>";

		}else{

			if (pg_numrows($res) == 1 OR $acao == 'mostrar') {

				

				$sqlX =	"SELECT  tbl_fabrica.os_item_subconjunto
						FROM    tbl_fabrica
						WHERE   tbl_fabrica.fabrica = $login_fabrica";
				$resX = pg_exec ($con,$sqlX);

				if (pg_numrows($resX) > 0) {

					$os_item_subconjunto = pg_result ($resX,0,os_item_subconjunto);

					if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';

				}



				$sql =	"SELECT tbl_os.os                                           ,
								tbl_os.sua_os                                               ,
								to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
								tbl_os.consumidor_nome                                      ,
								tbl_os.consumidor_fone                                      ,
								tbl_os.consumidor_cidade                                    ,
								tbl_os.consumidor_estado                                    ,
								tbl_os.cliente                                              ,
								tbl_os.revenda                                              ,
								tbl_os.revenda_cnpj                                         ,
								tbl_os.revenda_nome                                         ,
								tbl_os.nota_fiscal                                          ,
								to_char(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf             ,
								tbl_os.defeito_reclamado                                    ,
								tbl_os.defeito_reclamado_descricao                          ,
								tbl_os.aparencia_produto                                    ,
								tbl_os.acessorios                                           ,
								tbl_os.obs
						FROM tbl_os
						WHERE tbl_os.os=".pg_result($res,0,os);

#				echo nl2br($sql);

				$res = pg_exec($con,$sql);

				$os                          = pg_result($res,0,os);

				$sua_os                      = pg_result($res,0,sua_os);

				$data_abertura               = pg_result($res,0,data_abertura);

				$consumidor_nome             = pg_result($res,0,consumidor_nome);

				$consumidor_fone             = pg_result($res,0,consumidor_fone);

				$consumidor_cidade           = pg_result($res,0,consumidor_cidade);

				$consumidor_estado           = pg_result($res,0,consumidor_estado);

				$cliente                     = pg_result($res,0,cliente);

				$revenda                     = pg_result($res,0,revenda);

				$revenda_cnpj                = pg_result($res,0,revenda_cnpj);

				$revenda_nome                = pg_result($res,0,revenda_nome);

				$nota_fiscal                 = pg_result($res,0,nota_fiscal);

				$data_nf                     = pg_result($res,0,data_nf);

				$defeito_reclamado           = pg_result($res,0,defeito_reclamado);

				$defeito_reclamado_descricao = pg_result($res,0,defeito_reclamado_descricao);

				$aparencia_produto           = pg_result($res,0,aparencia_produto);

				$acessorios                  = pg_result($res,0,acessorios);

				$obs                         = pg_result($res,0,obs);



				if (strlen($cliente) > 0) {

					$sql =	"SELECT tbl_cliente.rg  ,
									tbl_cliente.endereco    ,
									tbl_cliente.numero      ,
									tbl_cliente.complemento ,
									tbl_cliente.bairro      ,
									tbl_cliente.cep         ,
									tbl_cliente.cpf
							FROM tbl_cliente
							WHERE tbl_cliente.cliente=$cliente";

					$res = pg_exec($con,$sql);

					$cliente_rg          = pg_result($res,0,rg);

					$cliente_endereco    = pg_result($res,0,endereco);

					$cliente_numero      = pg_result($res,0,numero);

					$cliente_complemento = pg_result($res,0,complemento);

					$cliente_bairro      = pg_result($res,0,bairro);

					$cliente_cep         = pg_result($res,0,cep);

					$cliente_cpf         = pg_result($res,0,cpf);

				}



				if (strlen($revenda) > 0) {

					$sql =	"SELECT tbl_revenda.endereco ,
									tbl_revenda.numero           ,
									tbl_revenda.complemento      ,
									tbl_revenda.bairro           ,
									tbl_revenda.cep
							FROM tbl_revenda
							WHERE tbl_revenda.revenda=$revenda";
					$res = pg_exec($con,$sql);

					$revenda_endereco    = pg_result($res,0,endereco);

					$revenda_numero      = pg_result($res,0,numero);

					$revenda_complemento = pg_result($res,0,complemento);

					$revenda_bairro      = pg_result($res,0,bairro);

					$revenda_cep         = pg_result($res,0,cep);

				}

				?>

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<td>OS FABRICANTE</td>

						<td>DATA DE ABERTURA</td>

						<td>CONSUMIDOR</td>

					</tr>

					<tr class='descricao'>

						<td><? echo $sua_os; ?>&nbsp;</td>

						<td><? echo $data_abertura; ?>&nbsp;</td>

						<td><? echo $consumidor_nome; ?>&nbsp;</td>

					</tr>

				<table>

				<table border='0' cellpadding='0' cellspacing='0'>

					<tr>

						<td height='4'></td>

					</tr>

				<table>

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<td>CPF</td>

						<td>RG</td>

						<td>TELEFONE</td>

					</tr>

					<tr class='descricao'>

						<td><? if (strlen($cliente_cpf) > 0) echo substr($cliente_cpf,0,3).".".substr($cliente_cpf,3,3).".".substr($cliente_cpf,6,3)."-".substr($cliente_cpf,9,2); ?>&nbsp;</td>

						<td><? echo $cliente_rg; ?>&nbsp;</td>

						<td><? echo $consumidor_fone; ?>&nbsp;</td>

					</tr>

				<table>

				<table border='0' cellpadding='0' cellspacing='0'>

					<tr>

						<td height='4'></td>

					</tr>

				<table>

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<td>ENDEREÇO</td>

						<td>NÚMERO</td>

						<td>COMPLEMENTO</td>

					</tr>

					<tr class='descricao'>

						<td><? echo $cliente_endereco; ?>&nbsp;</td>

						<td><? echo $cliente_numero; ?>&nbsp;</td>

						<td><? echo $cliente_complemento; ?>&nbsp;</td>

					</tr>

				<table>

				<table border='0' cellpadding='0' cellspacing='0'>

					<tr>

						<td height='4'></td>

					</tr>

				<table>

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<td>BAIRRO</td>

						<td>CIDADE</td>

						<td>ESTADO</td>

						<td>CEP</td>

					</tr>

					<tr class='descricao'>

						<td><? echo $cliente_bairro; ?>&nbsp;</td>

						<td><? echo $consumidor_cidade; ?>&nbsp;</td>

						<td><? echo $consumidor_estado; ?>&nbsp;</td>

						<td><? if (strlen($cliente_cep) > 0) echo substr($cliente_cep,0,2).".".substr($cliente_cep,2,3)."-".substr($cliente_cep,5,3); ?>&nbsp;</td>

					</tr>

				<table>

				<br>

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<td colspan='5' align='center'>INFOMAÇÃO DA REVENDA</td>

					</tr>

					<tr class='cabecalho'>

						<td>CNPJ REVENDA</td>

						<td>NOME DA REVENDA</td>

						<td>NF Nº</td>

						<td>DATA DA NF</td>



					</tr>

					<tr class='descricao'>

						<td><? echo $revenda_cnpj; ?>&nbsp;</td>

						<td><? echo $revenda_nome; ?>&nbsp;</td>

						<td><? echo $nota_fiscal; ?>&nbsp;</td>

						<td><? echo $data_nf; ?>&nbsp;</td>

					</tr>

				<table>

				<table border='0' cellpadding='0' cellspacing='0'>

					<tr>

						<td height='4'></td>

					</tr>

				<table>

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<td>ENDEREÇO</td>

						<td>NUMERO</td>

						<td>COMPLEMENTO</td>

						<td>BAIRRO</td>

						<td>CEP</td>

					</tr>

					<tr class='descricao'>

						<td><? echo $revenda_endereco; ?>&nbsp;</td>

						<td><? echo $revenda_numero; ?>&nbsp;</td>

						<td><? echo $revenda_complemento; ?>&nbsp;</td>

						<td><? echo $revenda_bairro; ?>&nbsp;</td>

						<td><? echo $revenda_cep; ?>&nbsp;</td>

					</tr>

				<table>

				<br>

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<td>Defeito Apresentado pelo Cliente</td>

						<td>Aparência Geral do Produto</td>

						<td>Acessórios Deixados pelo Cliente</td>

					</tr>

					<tr class='descricao'>

						<td><? echo $defeito_reclamado." ".$defeito_reclamado_descricao; ?>&nbsp;</td>

						<td><? echo $aparencia_produto; ?>&nbsp;</td>

						<td><? echo $acessorios; ?>&nbsp;</td>

					</tr>

				<table>

				<br>

				<?

				$sql = "SELECT  tbl_os_produto.os_produto                                     ,
								tbl_os_item.qtde                                              ,
								tbl_defeito.descricao           AS defeito_descricao          ,
								tbl_servico_realizado.descricao AS servico_realizado_descricao,
								tbl_peca.referencia                                           ,
								tbl_peca.descricao                                            ,
								tbl_produto.referencia          AS subproduto_referencia      ,
								tbl_produto.descricao           AS subproduto_descricao       
					FROM	tbl_os_produto
					JOIN	tbl_os_item           ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN	tbl_peca              ON tbl_peca.peca = tbl_os_item.peca
					JOIN    tbl_defeito           USING (defeito)
					JOIN    tbl_servico_realizado USING (servico_realizado)
					JOIN    tbl_produto           ON tbl_os_produto.produto = tbl_produto.produto
					WHERE   tbl_os_produto.os = $os";
				$res = pg_exec ($con,$sql);



				?>

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<? if($os_item_subconjunto == 't') echo "<td>Subconjuto</td>"; ?>

						<td>Referência</td>

						<td>Descrição</td>

						<td>Defeito</td>

						<td>Serviço</td>

					</tr>

				<?

				if (pg_numrows ($res) > 0) {

					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

						$referencia                  = pg_result ($res,$i,referencia);

						$descricao                   = pg_result ($res,$i,descricao);

						$defeito_descricao           = pg_result ($res,$i,defeito_descricao);

						$servico_realizado_descricao = pg_result ($res,$i,servico_realizado_descricao);

						$subproduto_referencia       = pg_result ($res,$i,subproduto_referencia);

						$subproduto_descricao        = pg_result ($res,$i,subproduto_descricao);

				?>

					<tr class='descricao'>

						<? if($os_item_subconjunto == 't') echo "<td>".$subproduto_referencia." - ".$subproduto_descricao."&nbsp;</td>"; ?>

						<td><? echo $referencia; ?>&nbsp;</td>

						<td><? echo $descricao; ?>&nbsp;</td>

						<td><? echo $defeito_descricao; ?>&nbsp;</td>

						<td><? echo $servico_realizado_descricao; ?>&nbsp;</td>

					</tr>

					<?

					}

				}

				?>

				<table>

				<br>

				

				<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>

					<tr class='cabecalho'>

						<td>Observações</td>

					</tr>

					<tr class='descricao'>

						<td><? echo $obs; ?>&nbsp;</td>

					</tr>

				<table>

				<?

			}else{

				echo "<TABLE align='center' border='0' cellspacing='1' cellpadding='1'>\n";

				echo "	<TR class='menu_top'>\n";

				echo "		<TD>OS</TD>\n";

				echo "		<TD>PEDIDO</TD>\n";

				echo "		<TD>NOTAL FISCAL</TD>\n";

				echo "		<TD>AÇÕES</TD>\n";

				echo "	</TR>\n";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++){

					$cor = "#F7F5F0"; 

					$btn = 'amarelo';

					if ($i % 2 == 0) 

					{

						$cor = '#F1F4FA';

						$btn = 'azul';

					}

					echo "	<TR class='table_line' style='background-color: $cor;'>\n";

					echo "		<TD nowrap>".pg_result($res,$i,sua_os)."</TD>\n";

					echo "		<TD nowrap>".pg_result($res,$i,pedido)."</TD>\n";

					echo "		<TD nowrap>".pg_result($res,$i,nota_fiscal)."</TD>\n";

					echo "		<TD><a href='$PHP_SELF?numero_os=".pg_result($res,$i,sua_os)."&numero_nf=".pg_result($res,$i,nota_fiscal)."&numero_pedido=".pg_result($res,$i,pedido)."&acao=mostrar&btn_acao=exibir'><img src='imagens/btn_consultar_".$btn.".gif'></a></TD>\n";

					echo "	</TR>";

				}

				echo "</TABLE>";

			}

		}

	}

}

?>



<? include "rodape.php" ?>
