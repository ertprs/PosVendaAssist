<?
require '../dbconfig.php';
require '../includes/dbconnect-inc.php';

require 'autentica_revenda.php';
include '../ajax_cabecalho.php';

if(isset($_GET["excluir"])){
	$sql = "SELECT data_recebido FROM tbl_lote_revenda WHERE lote_revenda =".$_GET["excluir"];
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0){
		$data = trim(pg_result($res,0,0));
		if(strlen($data)==0){
			$sql = "DELETE FROM tbl_lote_revenda_item where lote_revenda=".$_GET["excluir"];
			$res = pg_exec ($con,$sql);
		
			$sql = "DELETE FROM tbl_lote_revenda where lote_revenda=".$_GET["excluir"];
			$res = pg_exec ($con,$sql);
		}
	}
}

if($_GET["ver"]=="normal") echo "<link type='text/css' rel='stylesheet' href='css/estilo.css'>\n";

//--====== DETALHES DO TREINAMENTO =================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='detalhes') {
	$lote_revenda = $_GET["lote_revenda"];
	if($_GET["ver"]=="normal"){
		$resposta .= "<style>
			.Conteudo{
				font-family: Arial;
				font-size: 10px;
				color: #333333;
			}
			.Caixa{
				FONT: 8pt Arial ;
				BORDER-RIGHT:     #6699CC 1px solid;
				BORDER-TOP:       #6699CC 1px solid;
				BORDER-LEFT:      #6699CC 1px solid;
				BORDER-BOTTOM:    #6699CC 1px solid;
				BACKGROUND-COLOR: #FFFFFF;
			}
			.Erro{
				font-family: Verdana;
				font-size: 12px;
				color:#FFF;
				border:#485989 1px solid; 
				background-color: #990000;
			}
			
			</style>\n";
	}
	$sql = "SELECT  tbl_lote_revenda.lote                                                ,
					TO_CHAR(tbl_lote_revenda.data_digitacao,'dd/mm/YYYY')  AS data       ,
					tbl_lote_revenda.nota_fiscal                                         ,
					TO_CHAR(tbl_lote_revenda.data_nf,'dd/mm/YYYY')         AS data_nf    ,
					tbl_lote_revenda.responsavel                                         ,
					tbl_posto.nome                                                       ,
					tbl_posto_fabrica.codigo_posto                                       ,
					tbl_revenda.nome                                       AS revenda_nome
			FROM tbl_lote_revenda 
			LEFT JOIN tbl_revenda       USING(revenda)
			JOIN tbl_posto         USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_lote_revenda.fabrica      = $login_fabrica
			AND   tbl_lote_revenda.lote_revenda = $lote_revenda
			ORDER BY tbl_posto.nome";

	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)>0) {

		$lote         = pg_result ($res,0,lote);
		$data         = pg_result ($res,0,data);
		$nota_fiscal  = pg_result ($res,0,nota_fiscal);
		$data_nf      = pg_result ($res,0,data_nf);
		$nome         = pg_result ($res,0,nome);
		$codigo_posto = pg_result ($res,0,codigo_posto);
		$revenda_nome = pg_result ($res,0,revenda_nome);
		$responsavel  = pg_result ($res,0,responsavel);

		if($cor == "#D7E1FF") $cor = '#F0F4FF';
		else                  $cor = '#D7E1FF';

		$resposta .= "<br><table border='0' cellspacing='0' width='700' align='center' style=' border:#485989 1px solid; background-color: #e6eef7'>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> <b>Posto</td>\n";
		$resposta .= "<td align='left' title='$codigo_posto - $nome'> $nome </td>\n";
		$resposta .= "</tr>\n";
/*
		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> <b>Revenda</td>\n";
		$resposta .= "<td align='left'> $revenda_nome </td>\n";
		$resposta .= "</tr>\n";
*/
		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'><b>Data</td>\n";
		$resposta .= "<td align='left'> $data</td>\n";
		$resposta .= "</tr>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> <b>Lote</td>\n";
		$resposta .= "<td align='left'> $lote</td>\n";
		$resposta .= "</tr>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> <b>Nota Fiscal</td>\n";
		$resposta .= "<td align='left'> $nota_fiscal</td>\n";
		$resposta .= "</tr>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> <b>Data Nota Fiscal</td>\n";
		$resposta .= "<td align='left'> $data_nf</td>\n";
		$resposta .= "</tr>\n";
		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> <b>Responsável</td>\n";
		$resposta .= "<td align='left'> $responsavel</td>\n";
		$resposta .= "</tr>\n";
		$resposta .= "</table><br>\n";
	}



	$sql = "SELECT DISTINCT
				tbl_lote_revenda_item.lote_revenda_item                            ,
				tbl_lote_revenda_item.qtde                                         ,
				tbl_lote_revenda_item.conferencia_qtde                             ,
				tbl_produto.produto                                                ,
				tbl_produto.descricao                                              ,
				tbl_revenda_produto.referencia
			FROM      tbl_lote_revenda_item
			LEFT JOIN tbl_produto              USING (produto)
			LEFT JOIN tbl_revenda_produto      ON tbl_produto.produto =  tbl_revenda_produto.produto AND tbl_revenda_produto.revenda = $login_revenda
			WHERE   tbl_lote_revenda_item.lote_revenda = $lote_revenda
			ORDER BY tbl_lote_revenda_item.lote_revenda_item;";

	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		
		$qtde_item = pg_numrows($res);
		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$item_lote_revenda[$k]       = trim(pg_result($res,$k,lote_revenda_item));
			$item_produto[$k]            = trim(pg_result($res,$k,produto));
			$item_referencia[$k]         = trim(pg_result($res,$k,referencia));
			$item_qtde[$k]               = trim(pg_result($res,$k,qtde));
			$conferencia_qtde[$k]        = trim(pg_result($res,$k,conferencia_qtde));
			$item_descricao[$k]          = trim(pg_result($res,$k,descricao));

			if(strlen($descricao[$k])==0) $descricao[$k] = $item_descricao[$k];

			/*Neste ponto eu verifico quais itens já constam nota fiscal para devolução*/
			$sql2 = "SELECT count(*) FROM tbl_os
					JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
					JOIN tbl_os_revenda      USING(os_revenda)
					WHERE lote_revenda = $lote_revenda
					AND   tbl_os.produto = ".$item_produto[$k]."
					AND data_nf_saida IS NOT NULL";
			$res2 = pg_exec ($con,$sql2) ;
			$item_devolvido[$k] = trim(pg_result($res2,0,0));
		}
	}



	if($qtde_item>0){
		$resposta .= "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0' id='tbl_pecas'>\n";
		$resposta .= "<thead>\n";
		$resposta .= "<tr height='25' bgcolor='#BCCBE0'>\n";
		$resposta .= "<td align='center' class='Conteudo' height='25'><b>Código</b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo'><b>Descrição</b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde <acronym title='Quantidade de produtos enviados ao posto autorizado'>[?]</acronym></b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Recebida <acronym title='Quantidade de produtos que foi recebida pelo posto autorizado'>[?]</acronym></b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Devolução <acronym title='Quantidade devolvida pelo posto autorizado a revenda'>[?]</acronym></b>&nbsp;</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Saldo <acronym title='Quantidade de produtos que o posto deve retornar para a revenda'>[?]</acronym></b>&nbsp;</td>\n";

		$resposta .= "</tr>\n";
		$resposta .= "</thead>\n";

		$resposta .= "<tbody>\n";
		for ($k=0;$k<$qtde_item;$k++){
			$saldo = $conferencia_qtde[$k]-$item_devolvido[$k];
			$resposta .= "<tr style='color: #000000; text-align: center; font-size:10px'>\n";
			$resposta .="<td>$item_referencia[$k]";

			$resposta .="<input type='hidden' name='lote_revenda_item_$k'  id='lote_revenda_item_$k'  value='$item_lote_revenda[$k]'>\n";
			$resposta .="<input type='hidden' name='referencia_produto_$k' id='referencia_produto_$k' value='$item_referencia[$k]'>\n";
			$resposta .="<input type='hidden' name='produto_qtde_$k'       id='produto_qtde_$k'       value='$item_qtde[$k]'>\n";
			$resposta .="<input type='hidden' name='produto_$k'            id='produto_$k'            value='$item_peca[$k]'>\n";

			$resposta .="</td>\n";
			$resposta .="<td style=' text-align: left;'>$item_descricao[$k]</td>\n";
			$resposta .="<td>$item_qtde[$k]</td>\n";
			$resposta .="<td>$conferencia_qtde[$k]</td>\n";
			$resposta .="<td>$item_devolvido[$k]</td>\n";
			$resposta .="<td>".$saldo."</td>\n";

			$total_item = $item_qtde[$k];
			$valor_total_itens += $total_item;
			$total_item2 = $conferencia_qtde[$k];
			$valor_total_itens2 += $total_item2;
			$total_item3 = $item_devolvido[$k];
			$valor_total_itens3 += $total_item3;
			$total_item4 = $saldo;
			$valor_total_itens4 += $total_item4;

			

			$resposta .="</tr>\n";
		}
	}
	$resposta .="</tbody>\n";

	$resposta .="<tfoot>\n";
	$resposta .="<tr height='12' bgcolor='#BCCBE0'>\n";
	$resposta .="<td align='center' class='Conteudo' colspan='2'><b>Total</b>&nbsp;</td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens2</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens3</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens4</span></td>\n";

	$resposta .="</tr>\n";
	$resposta .="</tfoot>\n";
	$resposta .="</table>\n";

	if($_GET["ver"]<>"normal")echo "ok|$resposta";
	else echo "$resposta";
	exit;
}

$aba = 3;
$title = "Consulta de Notas Fiscais de Lotes";
include 'cabecalho.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>
<script language='javascript' src='../ajax.js'></script>
<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

function retornaCrm (http , componente ) {
	com = document.getElementById(componente);

	com.innerHTML   ="Carregando<br><img src='../imagens/carregar2.gif'>\n";
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = results[1];

					//mostrar_interacao(results[1],'interacao_'+results[1]);
				}else{
					alert ('Erro ao abrir lote da revenda' );
					alert(results[0]);
				}
			}
		}
	}
}

function pegaCrm (id,dados,cor) {
	url = "<?=$PHP_SELF?>?ajax=sim&acao=detalhes&lote_revenda=" + escape(id)+"&cor="+escape(cor) ;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaCrm (http , dados) ; } ;
	http.send(null);
}

function MostraEsconde(dados,hd_chamado,imagem,cor)
{
	if (document.getElementById)
	{
		// this is the way the standards work
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			style2.innerHTML   ="";
			img.src='../imagens/mais.gif';

			}
		else{
			style2.style.display = "block";
			img.src='../imagens/menos.gif';
			pegaCrm(hd_chamado,dados,cor);
		}

	}
}


</script>
<style>
.Conteudo{
	font-family: Arial;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; 
	background-color: #990000;
}

</style>
<br>
<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<?
$qtde_item=0;
echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' cellspacing='0'>\n";

echo "<tr height='20' bgcolor='#BCCBE0'>\n";
echo "<td align='left' colspan='4' ><b>Consulta Lote de Produto</b>&nbsp;</td>\n";
echo "</tr>\n";

echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>\n";
echo "<td align='right' width='200'><b>Lote</b>&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='lote' id='lote' value='$lote' class='Caixa'></td>\n";
echo "</tr>\n";

echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>\n";
echo "<td align='right' width='200'><b>Nota Fiscal</b>&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' class='Caixa'></td>\n";
echo "</tr>\n";

echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>\n";
echo "<td align='right' width='200'><b>Quantidade de dias no posto</b>&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='qtde_dias' maxlength='3' size='3' value='$qtde_dias' class='Caixa' > dias</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "<table style=' border:#B63434 1px solid; background-color: #EED5D2' align='center' width='750' border='0'height='40'>\n";
echo "<tr>\n";
echo "<td valign='middle' align='LEFT' class='Label' >\n";
echo "</td>\n";
	echo "<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='btn_acao'  value='Consultar' onClick=\"if (this.value!='Consultar'){ alert('Aguarde');}else {this.value='Consultando...'; /*gravar(this.form,'sim','$PHP_SELF','nao');*/}\" style=\"width: 150px;\"></td>\n";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>\n";
echo "</tr>\n";
echo "</table>\n";


flush();


if(strlen($btn_acao) > 0){

	$lote         = trim($_POST["lote"]);
	$nota_fiscal  = trim($_POST["nota_fiscal"]);
	$codigo_posto = trim($_POST["codigo_posto"]);
	$qtde_dias    = trim($_POST["qtde_dias"]);

	if(strlen($qtde_dias)>0){
		$cond1 = " AND data_recebido::date - data_digitacao::date >= $qtde_dias ";
	}
	if(strlen($lote) > 0){
		$cond2 = " AND lote like '%$lote%' ";
	}
	if(strlen($nota_fiscal) > 0){
		$cond3 = " AND nota_fiscal like '%$nota_fiscal%' ";
	}

	if(strlen($msg_erro) == 0){
		$sql = "SELECT  
					tbl_lote_revenda.lote_revenda                                          ,
					tbl_lote_revenda.lote                                                  ,
					TO_CHAR(tbl_lote_revenda.data_digitacao,'dd/mm/YYYY')  AS data         ,
					tbl_lote_revenda.nota_fiscal                                           ,
					TO_CHAR(tbl_lote_revenda.data_nf,'dd/mm/YYYY')         AS data_nf      ,
					TO_CHAR(tbl_lote_revenda.data_recebido,'dd/mm/YYYY')   AS data_recebido,
					tbl_posto.nome                                                         ,
					tbl_posto_fabrica.codigo_posto                                         ,
					tbl_revenda.nome                                       AS revenda_nome ,
					tbl_revenda.cnpj                                       AS revenda_cnpj
			FROM tbl_lote_revenda 
			LEFT JOIN tbl_revenda  USING(revenda)
			JOIN tbl_posto         USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_lote_revenda.fabrica = $login_fabrica
			AND   tbl_lote_revenda.revenda = $login_revenda
			$cond1 $cond2 $cond3 $cond4
			ORDER BY tbl_lote_revenda.lote_revenda;";

		$res = pg_exec ($con,$sql) ;
		if (pg_numrows($res) > 0) {

			echo "<br><table class='HD' align='center' width='98%' border='0' cellspacing='0'>\n";

			echo "<tr  class='Titulo'>\n";
			echo "<td align='left'> <b> </td>\n";
			echo "<td align='left'> <b>Posto</td>\n";
			echo "<td align='left'> <b>Data Lançamento</td>\n";
			echo "<td align='left'> <b>Lote</td>\n";
			echo "<td align='left'> <b>Nota Fiscal</td>\n";
			echo "<td align='left'> <b>Data Nota Fiscal</td>\n";
			echo "<td align='left'> <b>Data Recebimento</td>\n";
			echo "<td align='left'> <b>Ação</td>\n";
			echo "</tr>\n";

			$qtde_item = pg_numrows($res);
			for ($i = 0 ; $i<$qtde_item ; $i++) {
				$lote_revenda  = pg_result ($res,$i,lote_revenda);
				$lote          = pg_result ($res,$i,lote);
				$data          = pg_result ($res,$i,data);
				$nota_fiscal   = pg_result ($res,$i,nota_fiscal);
				$data_nf       = pg_result ($res,$i,data_nf);
				$data_recebido = pg_result ($res,$i,data_recebido);
				$nome          = pg_result ($res,$i,nome);
				$codigo_posto  = pg_result ($res,$i,codigo_posto);
				$revenda_nome  = pg_result ($res,$i,revenda_nome);
				$revenda_cnpj  = pg_result ($res,$i,revenda_cnpj);

				$cor = "#ffffff";
				if ($i % 2 == 0) $cor = "#FFEECC";

				echo "<tr bgcolor='$cor' class='Conteudo'>\n";
				echo "<td align='center' width='20'>\n";
				echo  "<img src='../imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','$lote_revenda','visualizar_$i','$cor');\" align='absmiddle'>\n";
				echo "</td>\n";
				echo "<td align='left'><a href=\"javascript:MostraEsconde('dados_$i','$lote_revenda','visualizar_$i','$cor');\">$codigo_posto - $nome </td>\n";
				echo "<td align='left'> $data</td>\n";
				echo "<td align='left'> $lote</td>\n";
				echo "<td align='left'> $nota_fiscal</td>\n";
				echo "<td align='left'> $data_nf</td>\n";
				echo "<td align='left'> $data_recebido</td>\n";
				echo "<td align='left'> ";
				if(strlen($data_recebido)==0) echo "<a href='$PHP_SELF?excluir=$lote_revenda'>Excluir</a>";
				echo "</td>\n";
				echo "</tr>\n";

					echo "<tr heigth='1' class='Conteudo' bgcolor='$cor'><td colspan='8'>\n";
					echo "<DIV class='exibe' id='dados_$i' value='1' align='center'>\n";
					echo "</DIV>\n";
					echo "</td></tr>\n";
			}
			echo "</table><br>\n";
		}else{
			echo "<center><font color='#FF0000'>Nenhum lote encontrado</font></center>\n";
		}
	}
}
if(strlen($msg_erro)>0) echo "<div name='erro' class='Erro'>$msg_erro</div>\n";
?>








<? include "rodape.php"; ?>
