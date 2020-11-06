<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

// somente a fabrica 3
/*
if($login_fabrica <> 3){
	header("Location: pedido_cadastro.php");
	exit;
}
*/
$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if (strlen($_GET['pedido']) > 0)  $pedido = trim($_GET['pedido']);
if (strlen($_POST['pedido']) > 0) $pedido = trim($_POST['pedido']);

if ($btn_acao == "gravar") {
	if (strlen($_POST['emissao']) > 0)
		$xemissao = "'". formata_data($_POST['emissao']) ."'";
	else
		$msg_erro = "Digite a data de emissão";
	
	if (strlen($_POST['saida']) > 0)
		$xsaida = "'". formata_data($_POST['saida']) ."'";
	else
		$msg_erro = "Digite a data de saída";

	if (strlen($_POST['previsao_chegada']) > 0)
		$xprevisao_chegada = "'". formata_data($_POST['previsao_chegada']) ."'";
	else
		$xprevisao_chegada = 'null';
	
	if (strlen($_POST['transportadora']) > 0)
		$xtransportadora = "'". $_POST['transportadora'] ."'";
	else
		$msg_erro = "Selecione a transportadora.";
	
	if (strlen($_POST['cfop']) > 0)
		$xcfop = "'". $_POST['cfop'] ."'";
	else
		$xcfop = 'null';
	
	if (strlen($_POST['total_nota']) > 0)
		$xtotal_nota = "'". str_replace(',','.',$_POST['total_nota']) ."'";
	else
		$msg_erro = "Digite o total da Nota Fiscal.";
	
	if (strlen($_POST['nota_fiscal']) > 0)
		$xnota_fiscal = "'". $_POST['nota_fiscal'] ."'";
	else
		$msg_erro = "Digite o número da nota fiscal.";

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen ($faturamento) == 0) {
			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_faturamento (
						fabrica          ,
						emissao          ,
						saida            ,
						transportadora   ,
						pedido           ,
						posto            ,
						previsao_chegada ,
						total_nota       ,
						cfop             ,
						nota_fiscal      
					) VALUES (
						$login_fabrica     ,
						$xemissao          ,
						$xsaida            ,
						$xtransportadora   ,
						$pedido            ,
						$posto             ,
						$xprevisao_chegada ,
						$xtotal_nota       ,
						$xcfop             ,
						$xnota_fiscal      
					)";
		}else{
			$sql = "UPDATE tbl_faturamento SET
						fabrica          = $login_fabrica,
						emissao          = $xemissao,
						saida            = $xsaida,
						transportadora   = $xtransportadora,
						pedido           = $pedido,
						posto            = $posto,
						previsao_chegada = $xprevisao_chegada,
						total_nota       = $xtotal_nota,
						cfop             = $xcfop,
						nota_fiscal      = $xnota_fiscal
					WHERE faturamento    = $faturamento
					AND   fabrica        = $login_fabrica";
		}
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 and strlen($faturamento) == 0) {
			$res = pg_exec ($con,"SELECT CURRVAL ('seq_faturamento')");
			$faturamento = pg_result ($res,0,0);
			$msg_erro    = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$qtde_item = $_POST['qtde_item'];
			
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$peca             = $_POST['peca_'.$i];
				$preco            = str_replace(',','.',$_POST['preco_'.$i]);
				$qtde             = $_POST['qtde_'.$i];
				$qtde_faturamento = $_POST['qtde_faturamento_'.$i];
				$pendente         = $_POST['pendente_'.$i];
				
				if (strlen($pendente) == 0){
					$xpendente = $qtde - $qtde_faturamento;
				}else{
					$xpendente = $pendente - $qtde_faturamento;
				}

				if(strlen($qtde_faturamento) > 0 AND strlen($peca) > 0 AND strlen($msg_erro) == 0){

					$sql = "INSERT INTO tbl_faturamento_item (
								faturamento,
								peca       ,
								preco      ,
								qtde       ,
								pendente   
							) VALUES (
								$faturamento     ,
								$peca            ,
								$preco           ,
								$qtde_faturamento,
								$xpendente        
							)";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro) > 0) $linha_erro = $i;
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_posicao_pedido($login_fabrica)";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: pedido_posto_relacao.php");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

#------------ Le Pedido da Base de dados ------------#
if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_posto.posto               ,
					tbl_posto.cnpj                ,
					tbl_posto.nome                ,
					tbl_pedido.condicao           ,
					tbl_pedido.tabela             ,
					tbl_pedido.obs                ,
					tbl_pedido.tipo_pedido        ,
					tbl_pedido.tipo_frete         ,
					tbl_pedido.pedido_cliente     ,
					tbl_pedido.validade           ,
					tbl_pedido.entrega            ,
					tbl_pedido.linha              ,
					tbl_pedido.transportadora     ,
					tbl_pedido.pedido_distribuidor,
					tbl_linha.nome            AS nome_linha,
					tbl_tipo_pedido.descricao AS nome_tipo_pedido,
					tbl_tabela.descricao      AS nome_tabela,
					tbl_condicao.descricao    AS nome_condicao
			FROM    tbl_pedido
			JOIN    tbl_posto            USING (posto)
			LEFT JOIN    tbl_linha       USING (linha)
			LEFT JOIN    tbl_tipo_pedido USING (tipo_pedido)
			LEFT JOIN    tbl_tabela      USING (tabela)
			LEFT JOIN    tbl_condicao    USING (condicao)
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) > 0) {
		$posto               = trim(pg_result ($res,0,posto));
		$cnpj                = trim(pg_result ($res,0,cnpj));
		$cnpj                = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		$nome                = trim(pg_result ($res,0,nome));
		$obs                 = trim(pg_result ($res,0,obs));
		$tipo_frete          = trim(pg_result ($res,0,tipo_frete));
		$pedido_cliente      = trim(pg_result ($res,0,pedido_cliente));
		$pedido_distribuidor = trim(pg_result ($res,0,pedido_distribuidor));
		$validade            = trim(pg_result ($res,0,validade));
		$entrega             = trim(pg_result ($res,0,entrega));
		$nome_linha          = trim(pg_result ($res,0,nome_linha));
		$nome_tipo_pedido    = trim(pg_result ($res,0,nome_tipo_pedido));
		$nome_tabela         = trim(pg_result ($res,0,nome_tabela));
		$nome_condicao       = trim(pg_result ($res,0,nome_condicao));
		$transportadora      = trim(pg_result ($res,0,transportadora));
#		$referencia          = trim(pg_result ($res,0,referencia));
#		$descricao           = trim(pg_result ($res,0,descricao));
	}

}


#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$cnpj           = $_POST['cnpj'];
	$nome           = $_POST['nome'];
	$condicao       = $_POST['condicao'];
	$tipo_frete     = $_POST['tipo_frete'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$validade       = $_POST['validade'];
	$entrega        = $_POST['entrega'];
	$tabela         = $_POST['tabela'];
	$cnpj           = $_POST['cnpj'];
	$obs            = $_POST['obs'];
	$linha          = $_POST['linha'];
}

$layout_menu = "pedido";
$title       = "Cadastro de Pedidos dos Postos";

include "cabecalho.php";

?>

<script type="text/javascript" src="alphaAPI.js"></script>

<script language="JavaScript">
function fnc_pesquisa_transportadora (xcampo, tipo)
{
	if (xcampo.value != "") {
		var url = "";
		url = "pesquisa_transportadora.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.transportadora = document.frm_pedido.transportadora;
		janela.nome           = document.frm_pedido.transportadora_nome;
		janela.cnpj           = document.frm_pedido.transportadora_cnpj;
		janela.focus();
	}
}
</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<? 
if (strlen ($msg_erro) > 0) {
?>
<table class="table" width="730" border="0" cellpadding="0" cellspacing="0" >
<tr>
<!-- class="menu_top" -->
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } 
//echo $msg_debug ;
?>


<!-- ------------- Formulário ----------------- -->
<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="pedido" value="<? echo $pedido ?>">
<input type="hidden" name="posto" value="<? echo $posto ?>">
<!-- input type="hidden" name="faturamento" value="<? echo $faturamento ?>" -->

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<font face='arial, verdana, times' color='#ffffff'><b>
		Código ou CNPJ
		</b></font>
	</td>
	<td align='center'>
		<font face='arial, verdana, times' color='#ffffff'><b>
		Razão Social
		</b></font>
	</td>
</tr>

<tr class="table_line">
	<td align='center'>
		<? echo $cnpj ?>&nbsp;
	</td>
	<td align='center'>
		<? echo $nome ?>&nbsp;
	</td>
</tr>
</table>

<!--
<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Linha
		</b>
	</td>
	<td align='center'>
		<b>
		Referência do Produto
		</b>
	</td>
	<td align='center'>
		<b>
		Descrição do Produto
		</b>
	</td>
</tr>

<tr class="table_line">
	<td align='center'>
		<? echo $nome_linha; ?>&nbsp;
	</td>
	<td align='center'>
		<? echo $referencia ?>&nbsp;
	</td>
	<td align='center'>
		<? echo $descricao ?>&nbsp;
	</td>
</tr>
</table>
-->

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
			Tipo do Pedido
		</b>
	</td>
	<td align='center'>
		<b>
			Tabela de Preços
		</b>
	</td>
	<td align='center'>
		<b>
			Condição de Pagamento
		</b>
	</td>
	<td align='center'>
		<b>
			Tipo de Frete
		</b>
	</td>
</tr>

<tr class="table_line">
	<td align='center'>
		<? echo $nome_tipo_pedido; ?>&nbsp;
	</td>
	<td align='center'>
		<? echo $nome_tabela; ?>&nbsp;
	</td>
	<td align='center'>
		<? echo $nome_condicao; ?>&nbsp;
	</td>
	<td align='center'>
		<? echo $tipo_frete; ?>&nbsp;
	</td>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Pedido Distribuidor<!-- Cliente -->
		</b>
	</td>
	<td align='center'>
		<b>
		Validade
		</b>
	</td>
	<td align='center'>
		<b>
		Entrega
		</b>
	</td>
</tr>

<tr class="table_line">
	<td align='center'>
		<? 
		echo $pedido_distribuidor; //$pedido_cliente 
		?>&nbsp;
	</td>
	<td align='center'>
		<? echo $validade ?>&nbsp;
	</td>
	<td align='center'>
		<? echo $entrega ?>&nbsp;
	</td>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Mensagem
		</b>
	</td>
</tr>
<tr>
	<td>
		<? echo $obs; ?>&nbsp;
	</td>
</tr>
</table>

<BR>
<HR width="600">
<BR>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center' width='33%'>
		<b>
		Emissão
		</b>
	</td>
	<td align='center' width='33%'>
		<b>
		Saída
		</b>
	</td>
	<td align='center' width='34%'>
		<b>
		Previsão de chegada
		</b>
	</td>
</tr>
<tr>
	<td>
		<INPUT TYPE="text" NAME="emissao" value="<? echo $emissao ?>" size="10" maxlength="10">
	</td>
	<td>
		<INPUT TYPE="text" NAME="saida" value="<? echo $saida ?>" size="10" maxlength="10">
	</td>
	<td>
		<INPUT TYPE="text" NAME="previsao_chegada" value="<? echo $previsao_chegada ?>" size="10" maxlength="10">
	</td>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		CFOP
		</b>
	</td>
	<td align='center'>
		<b>
		Transportadora
		</b>
	</td>
</tr>
<tr>
	<td>
		<INPUT TYPE="text" NAME="cfop" value="<? echo $cfop ?>" size="10">
	</td>
	<td>
<?
	echo "		<input type='hidden' name='transportadora' value=''>";
	echo "		<input type='hidden' name='transportadora_codigo' value='$transportadora_codigo'>";
	echo "		CNPJ <input type='text' name='transportadora_cnpj' size='14' maxlength='10' value='$transportadora_cnpj' class='textbox' >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_cnpj,'cnpj')\" style='cursor:pointer;'>";
	echo "		Nome <input type='text' name='transportadora_nome' size='30' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";

/*
	$sql = "SELECT	tbl_transportadora.transportadora        ,
					tbl_transportadora.cnpj                  ,
					tbl_transportadora.nome                  ,
					tbl_transportadora_fabrica.codigo_interno
			FROM	tbl_transportadora
			JOIN	tbl_transportadora_fabrica USING(transportadora)
			WHERE	tbl_transportadora_fabrica.fabrica = $login_fabrica
			AND		tbl_transportadora_fabrica.ativo  = 't' ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		echo "		<select name='transportadora'>";
		echo "			<option selected></option>";
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			echo "<option value='".pg_result($res,$i,transportadora)."' ";
			if ($transportadora == pg_result($res,$i,transportadora) ) echo " selected ";
			echo ">";
			echo pg_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
			echo "</option>\n";
		}
		echo "		</select>";
	}else{
		echo "&nbsp;";
	}
*/
?>
	</td>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Nota Fiscal
		</b>
	</td>
	<td align='center'>
		<b>
		Total Nota Fiscal
		</b>
	</td>
</tr>
<tr>
	<td>
		<INPUT TYPE="text" NAME="nota_fiscal" value="<? echo $nota_fiscal; ?>" size="12">
	</td>
	<td>
		<INPUT TYPE="text" NAME="total_nota" value="<? echo $total_nota; ?>" size="12" style="text-align:right">
	</td>
</tr>
</table>

<p>
		<table width="600" border="0" cellspacing="3" cellpadding="0" align='center'>
		<tr height="20" class="menu_top">
			<td colspan='6'>Se o item não foi atendido pela nota fiscal, favor deixar a quantidade faturada sem preenchimento.</td>
		</tr>

		<tr height="20" class="menu_top">
			<td align='center'>Referência Componente</td>
			<td align='center'>Descrição Componente</td>
			<td align='center'>Qtde</td>
			<td align='center'>Pendente</td>
			<td align='center'>Qtde NF</td>
			<td align='center'>Valor</td>
		</tr>
		
<?
		if (strlen($pedido) > 0) {
			$sql = "SELECT      tbl_peca.peca
					FROM        tbl_pedido_item
					JOIN        tbl_peca   USING (peca)
					JOIN        tbl_pedido USING (pedido)
					WHERE       tbl_pedido_item.pedido = $pedido
					ORDER BY    tbl_pedido_item.pedido_item;";
			$ped = pg_exec ($con,$sql);
			$qtde_item = @pg_numrows($ped);
		}

		if (strlen($pedido) > 0) {
			echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
			
			$botao = 0;

			for ($i = 0 ; $i < $qtde_item ; $i++) {
				if (strlen($pedido) > 0) {
					if (@pg_numrows($ped) > 0) {
						$peca = trim(@pg_result($ped,$i,peca));
					}
/*
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco      ,
									tbl_peca.referencia        ,
									tbl_peca.descricao
							FROM    tbl_pedido_item
							JOIN    tbl_peca USING (peca)
							WHERE   tbl_pedido_item.pedido = $pedido
							AND     tbl_pedido_item.peca   = $peca";
*/
					$sql = "SELECT  tbl_pedido_item.pedido_item                    ,
									tbl_pedido_item.qtde                           ,
									tbl_pedido_item.preco                          ,
									tbl_peca.referencia                            ,
									tbl_peca.descricao                             ,
									tbl_faturamento.nota_fiscal                    ,
									tbl_faturamento_item.pendente  AS pendente     ,
									sum(tbl_faturamento_item.qtde) AS qtde_faturada
							FROM    tbl_pedido_item
							JOIN    tbl_peca USING (peca)
							LEFT JOIN  tbl_faturamento_item
									ON tbl_faturamento_item.peca = tbl_pedido_item.peca
							WHERE   tbl_pedido_item.pedido = $pedido
							AND     tbl_pedido_item.peca   = $peca
							GROUP BY tbl_pedido_item.pedido_item   ,
									tbl_pedido_item.qtde           ,
									tbl_pedido_item.preco          ,
									tbl_peca.referencia            ,
									tbl_peca.descricao             ,
									tbl_faturamento.nota_fiscal    ,
									tbl_faturamento_item.pendente";
					$aux_ped = pg_exec ($con,$sql);
					
					if (pg_numrows($aux_ped) > 0) {
						$nota_fiscal     = trim(pg_result($aux_ped,0,nota_fiscal));
						$item            = trim(pg_result($aux_ped,0,pedido_item));
						$peca_referencia = trim(pg_result($aux_ped,0,referencia));
						$peca_descricao  = trim(pg_result($aux_ped,0,descricao));
						$qtde            = trim(pg_result($aux_ped,0,qtde));
						$preco           = trim(pg_result($aux_ped,0,preco));
						$pendente        = trim(pg_result($aux_ped,0,pendente));
						$qtde_faturada   = trim(pg_result($aux_ped,0,qtde_faturada));
					}

					if (strlen($pendente) > 0)
						$xpendente = $pendente;
					else
						$xpendente = $qtde;
				}
				echo "<input type='hidden' name='item$i' value='$item'>\n";

				echo "<tr class='table_line'";
				if ($linha_erro == $i and strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'";
				echo ">\n";
				echo "<td align='left'>$peca_referencia</td>\n";
				echo "<td align='left'>$peca_descricao</td>\n";
				echo "<td align='center'>\n";
				echo $qtde;
				echo "<input type='hidden' name='peca_$i' size=5 value='$peca'>\n";
				echo "<input type='hidden' name='qtde_$i' size=5 value='$qtde'>\n";
				echo "<input type='hidden' name='pendente_$i' size=5 value='$xpendente'>\n";
				echo "<input type='hidden' name='qtde_faturada_$i' size=5 value='$qtde_faturada'>\n";
				echo "<input type='hidden' name='preco_$i' size='5' value='$preco'>\n";
				echo "</td>\n";
				if ($qtde == $qtde_faturada){
					echo "<td align='center'>0</td>\n";
				}else{
					echo "<td align='center'>$xpendente</td>\n";
				}
				
				if ($pendente == 0 AND strlen($pendente) > 0){
					echo "<td align='center'>$qtde</td>\n";
					echo "<td align='right' style='padding-right:10px;'>".number_format($preco,'2',',','.')."</td>\n";
					$botao++;
				}else{
					echo "<td align='center'><input class='frm' type='text' name='qtde_faturamento_$i' size=5 value='$qtde_faturamento'></td>\n";
					echo "<td align='center'><input type='text' class='frm' name='preco_$i' size='5' value='".number_format($preco,'2',',','.')."' style='text-align:right;'></td>\n";
				}

				echo "</tr>\n";
			}
		}
?>
		</table>
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<BR>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
<?
	if ($qtde_item <> $botao){
?>
		<input type='hidden' name='btn_acao' value=''>
		<img src="imagens/btn_gravar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
<?
	}else{
?>
		<img src="imagens/btn_voltar.gif" onclick="javascript: history.back();" ALT="Voltar" border='0' style="cursor:pointer;">
<?
	}
?>
	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php"; ?>