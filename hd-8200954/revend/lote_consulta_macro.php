<?
require '../dbconfig.php';
require '../includes/dbconnect-inc.php';

require 'autentica_revenda.php';
include '../ajax_cabecalho.php';



if($_GET["ver"]=="normal") echo "<link type='text/css' rel='stylesheet' href='css/estilo.css'>";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	$busca_revenda = $_GET["busca_revenda"];	

	if (strlen($q)>3){

		if(strlen($busca_revenda)>0){
			$sql = "SELECT  tbl_revenda.revenda,
					tbl_revenda.nome,
					tbl_revenda.cnpj
				FROM tbl_revenda
				JOIN   tbl_revenda_fabrica USING (revenda)
				WHERE  tbl_revenda_fabrica.fabrica = $login_fabrica
				";
			if ($busca_revenda == "codigo"){
				$sql .= " AND tbl_revenda.cnpj = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_revenda.nome) LIKE UPPER('%$q%') ";
			}
			$sql .= " LIMIT 50 ";
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$revenda = trim(pg_result($res,$i,revenda));
					echo "$cnpj|$nome|$revenda";
					echo "\n";
				}
			}

		}else{

			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_revenda_posto USING(posto)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_posto.cnpj = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}
	
			$sql .= " LIMIT 50 ";
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$codigo_posto|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}
//--====== DETALHES DO TREINAMENTO =================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='detalhes') {
	$produto = $_GET["produto"];
	$revenda = $_GET["revenda"];
	$posto   = $_GET["posto"];


	$sql = "SELECT DISTINCT
				tbl_lote_revenda.lote                                              ,
				tbl_lote_revenda.nota_fiscal                                       ,
				tbl_lote_revenda.lote_revenda                                      ,
				data_recebido::date - data_digitacao::date    AS dias_recebimento  ,
				tbl_lote_revenda_item.lote_revenda_item                            ,
				tbl_lote_revenda_item.qtde                                         ,
				tbl_lote_revenda_item.conferencia_qtde                             ,
				tbl_produto.produto                                                ,
				tbl_produto.descricao                                              ,
				tbl_produto.referencia
			FROM      tbl_lote_revenda_item
			JOIN      tbl_lote_revenda USING (lote_revenda)
			LEFT JOIN tbl_produto      USING (produto)
			WHERE   tbl_lote_revenda_item.produto = $produto
			AND     tbl_lote_revenda.posto        = $posto
			AND     tbl_lote_revenda.revenda      = $login_revenda
			ORDER BY tbl_lote_revenda_item.lote_revenda_item;";

	$res = @pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		
		$qtde_item = pg_numrows($res);
		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$lote[$k]                    = trim(pg_result($res,$k,lote));
			$nota_fiscal[$k]             = trim(pg_result($res,$k,nota_fiscal));
			$lote_revenda[$k]            = trim(pg_result($res,$k,lote_revenda));
			$dias_recebimento[$k]        = trim(pg_result($res,$k,dias_recebimento));
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
					WHERE lote_revenda   = ".$lote_revenda[$k]."
					AND   tbl_os.produto = ".$item_produto[$k]."
					AND data_nf_saida IS NOT NULL";
			$res2 = pg_exec ($con,$sql2) ;
			$item_devolvido[$k] = trim(pg_result($res2,0,0));

			$sql2 = "SELECT count(*) 
				FROM tbl_os
				JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
				JOIN tbl_os_revenda      USING(os_revenda)
				WHERE tbl_os.produto = $produto
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $posto
				AND    lote_revenda         = ".$lote_revenda[$k]."
				AND   tbl_os_revenda.lote_revenda IS NOT NULL
				AND   tbl_os.data_nf_saida        IS NOT NULL
				AND   tbl_os.conferido_saida      IS TRUE";

			$res2 = pg_exec ($con,$sql2);
			$item_devolvido_recebido[$k] = trim(pg_result($res2,0,0));
		}
	}



	if($qtde_item>0){

$resposta .= "
<script type=\"text/javascript\" src=\"js/jquery-latest.pack.js\"></script>
<script type=\"text/javascript\" src=\"js/thickbox.js\"></script>
<link rel=\"stylesheet\" href=\"js/thickbox.css\" type=\"text/css\" media=\"screen\" />";

		$resposta .= "<font size='2'><b>$item_referencia[0] - $item_descricao[0]</b></font><table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' id='tbl_pecas'>";
		$resposta .= "<thead>";
		$resposta .= "<tr height='25' bgcolor='#BCCBE0'>";
		$resposta .= "<td align='center' class='Conteudo'><b>Lote.</b></td>";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Env.</b></td>";
		$resposta .= "<td align='center'> <b>Div.</td>";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Rec.</b></td>";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Dev.</b></td>";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Saldo</b></td>";
		$resposta .= "<td align='center' class='Conteudo' width='100'><b>Qtde Ret.</b></td>";
		$resposta .= "<td align='center' class='Conteudo' width='100'> <b>Qtde Div. Ret.</td>";
		$resposta .= "</tr>";
		$resposta .= "</thead>";

		$resposta .= "<tbody>";
		for ($k=0;$k<$qtde_item;$k++){

			$saldo = $conferencia_qtde[$k] - $item_devolvido[$k];
			$dive1 = $conferencia_qtde[$k] - $item_qtde[$k];             if($dive1==0)$dive1='';
			$dive2 = $item_devolvido_recebido[$k] - $item_devolvido[$k]; if($dive2==0)$dive2='';
	
			$resposta .= "<tr style='color: #000000; text-align: center; font-size:10px'>";
			$resposta .="<td><a href=\"lote_consulta.php?ajax=sim&acao=detalhes&ver=normal&lote_revenda=$lote_revenda[$k]&keepThis=trueTB_iframe=true&height=450&width=700\" title='Detalhado' class=\"thickbox\">$lote[$k] / NF $nota_fiscal[$k]</a></td>";
			$resposta .="<td>$item_qtde[$k]</td>";
			$resposta .="<td><font color='#FF0000'>$dive1</font></td>";
			$resposta .="<td>$conferencia_qtde[$k]</td>";
			$resposta .="<td>$item_devolvido[$k]</td>";
			$resposta .="<td><font color='#009900'>".$saldo."</td>";
			$resposta .="<td>".$item_devolvido_recebido[$k]."</td>";
			$resposta .="<td><font color='#FF0000'>$dive2</font></td>";

			$total_item  = $item_qtde[$k];               $valor_total_itens  += $total_item;
			$total_item2 = $conferencia_qtde[$k];        $valor_total_itens2 += $total_item2;
			$total_item3 = $item_devolvido[$k];          $valor_total_itens3 += $total_item3;
			$total_item4 = $saldo;                       $valor_total_itens4 += $total_item4;
			$total_item5 = $item_devolvido_recebido[$k]; $valor_total_itens5 += $total_item5;
			$total_dive1 += $dive1;
			$total_dive2 += $dive2;
			$resposta .="</tr>";
		}
	}
	$resposta .="</tbody>";

	$resposta .="<tfoot>";
	$resposta .="<tr height='12' bgcolor='#BCCBE0' height='25'>";
	$resposta .="<td align='center' class='Conteudo'><b>Total</b></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#FF0000'>$total_dive1</font></span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens2</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens3</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#009900'>$valor_total_itens4</font></span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens5</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'><font color='#FF0000'>$total_dive2</font></span></td>\n";

	$resposta .="</tr>\n";
	$resposta .="</tfoot>";
	$resposta .="</table>\n";

	echo "ok|$resposta";
	exit;
}

$aba = 7;
$title = "Consulta de Notas Fiscais de Lotes";
include 'cabecalho.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>
<script language='javascript' src='../ajax.js'></script>


<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#nome_posto").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#nome_posto").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
		//alert(data[2]);
	});


	/* Busca pelo Código */
	$("#revenda_cnpj").autocomplete("<?echo $PHP_SELF.'?busca_revenda=codigo'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#revenda_cnpj").result(function(event, data, formatted) {
		$("#revenda_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#revenda_nome").autocomplete("<?echo $PHP_SELF.'?busca_revenda=nome'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#revenda_nome").result(function(event, data, formatted) {
		$("#revenda_cnpj").val(data[0]) ;
		//alert(data[2]);
	});


});
function bloqueia(){
	$("*[@rel='corpo']").show("slow");
}
</script>
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

	com.innerHTML   ="Carregando<br><img src='../imagens/carregar2.gif'>";
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = results[1];
					tb_init('a.thickbox, area.thickbox, input.thickbox');

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
	url = "<?=$PHP_SELF?>?ajax=sim&acao=detalhes&" + id+"&cor="+escape(cor) ;

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
echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' cellspacing='0'>";

echo "<tr height='20' bgcolor='#BCCBE0'>";
echo "<td align='left' colspan='4'><b>Consulta Lote de Produto</b></td>";
echo "</tr>";
echo "<tr><td colspan='4' bgcolor='#F0F4FF'>";

echo "<br>&nbsp;</td></tr>";
echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>";
echo "<td align='right' ><b>Lote</b></td>";
echo "<td align='left' colspan='3'><input type='text' name='lote' id='lote' value='$lote' class='Caixa'></td>";
echo "</tr>";

echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>";
echo "<td align='right' ><b>Nota Fiscal</b></td>";
echo "<td align='left' colspan='3'><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' class='Caixa'></td>";
echo "</tr>";


echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>";
echo "<td align='right' ><b>Codigo Posto</b></td>";
echo "<td align='left' ><input type='text' name='codigo_posto' maxlength='14' id='codigo_posto' value='$codigo_posto' class='Caixa' onFocus=\"nextfield ='nome_posto'\" onblur=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\"></td>";
echo "<td align='right' ><b>Nome</b></td>";
echo "<td align='left'><input type='text' name='nome_posto' id='nome_posto' size='50' maxlength='60' value='$nome_posto' class='Caixa' onFocus=\"nextfield ='condicao'\"></td>";
echo "</tr>";

echo "<tr height='20' bgcolor='#F0F4FF' class='Conteudo'>";
echo "<td align='right' ><b>Quantidade de dias no posto</b></td>";
echo "<td align='left' colspan='3'><input type='text' name='qtde_dias' maxlength='3' size='3' value='$qtde_dias' class='Caixa' > dias";
echo "</tr>";
echo "</table>";


echo "<table style=' border:#B63434 1px solid; background-color: #EED5D2' align='center' width='750' border='0'height='40'>";
echo "<tr>";
echo "<td valign='middle' align='LEFT' class='Label' >";
echo "</td>";
	echo "<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='btn_acao'  value='Consultar' onClick=\"if (this.value!='Consultar'){ alert('Aguarde');}else {this.value='Consultando...'; /*gravar(this.form,'sim','$PHP_SELF','nao');*/}\" style=\"width: 150px;\"></td>";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>";
echo "</tr>";
echo "</table>";

flush();


if(strlen($btn_acao) > 0){

	$lote         = trim($_POST["lote"]);
	$nota_fiscal  = trim($_POST["nota_fiscal"]);
	$codigo_posto = trim($_POST["codigo_posto"]);
	$revenda_cnpj = trim($_POST["revenda_cnpj"]);
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
	if (strlen($codigo_posto) > 0 ) {
		$sql =	"SELECT tbl_posto.posto
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE	tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto = "'".pg_result($res,0,0)."'";
			$cond4 = " AND tbl_lote_revenda.posto = $posto";
		}else{
			$msg_erro .= " Favor informe o posto correto. ";
		}
	}

	if(strlen($msg_erro) == 0){
		$sql = "SELECT DISTINCT
					tbl_posto.nome                                                         ,
					tbl_posto_fabrica.codigo_posto                                         ,
					tbl_revenda.revenda                                                    ,
					tbl_revenda.nome                                       AS revenda_nome ,
					tbl_revenda.cnpj                                       AS revenda_cnpj ,
					sum(tbl_lote_revenda_item.qtde)      AS qtde                           ,
					sum(tbl_lote_revenda_item.conferencia_qtde) as conferencia_qtde        ,
					tbl_produto.produto,
					tbl_posto.posto,
					tbl_produto.referencia                                                 ,
					tbl_produto.descricao
			FROM tbl_lote_revenda 
			JOIN tbl_lote_revenda_item USING(lote_revenda)
			JOIN tbl_produto           USING(produto)
			LEFT JOIN tbl_revenda      USING(revenda)
			JOIN tbl_posto             USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_lote_revenda.fabrica = $login_fabrica
			AND   tbl_lote_revenda.revenda = $login_revenda
			$cond1 $cond2 $cond3 $cond4
			GROUP BY tbl_revenda.revenda,tbl_posto.nome,tbl_posto.posto,codigo_posto,revenda_nome,revenda_cnpj,referencia,descricao,tbl_produto.produto;";

		$res = pg_exec ($con,$sql) ;
		if (pg_numrows($res) > 0) {

			echo "<br><table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='98%' border='0' cellspacing='0'>";
			echo "<caption><center><a href='javascript: bloqueia();'>Clique aqui para ver detalhes por produtos</a></center></caption>";

			echo "<thead>";
			echo "<tr bgcolor='#BCCBE0' class='Conteudo' height='25'>";
			echo "<td align='left'> <b> </td>";
			echo "<td align='left'> <b>Posto</td>";
			echo "<td align='left'> <b>Produto</td>";
			echo "<td align='right'> <b>Qtde Env.</td>";
			echo "<td align='right'> <b>Div.</td>";
			echo "<td align='right'> <b>Qtde Rec.</td>";
			echo "<td align='right'> <b>Qtde Dev.</td>";
			echo "<td align='right'> <b>Saldo</td>";
			echo "<td align='right'> <b>Qtde Ret.</td>";
			echo "<td align='right'> <b>Qtde Div. Ret.</td>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody rel='corpo' style='display:none;'>";
			$qtde_item = pg_numrows($res);
			for ($i = 0 ; $i<$qtde_item ; $i++) {
				$nome             = pg_result ($res,$i,nome);
				$codigo_posto     = pg_result ($res,$i,codigo_posto);
				$revenda_nome     = pg_result ($res,$i,revenda_nome);
				$revenda_cnpj     = pg_result ($res,$i,revenda_cnpj);
				$produto          = pg_result ($res,$i,produto);
				$referencia       = pg_result ($res,$i,referencia);
				$descricao        = pg_result ($res,$i,descricao);
				$qtde             = pg_result ($res,$i,qtde);
				$revenda          = pg_result ($res,$i,revenda);
				$posto             = pg_result ($res,$i,posto);
				$conferencia_qtde = pg_result ($res,$i,conferencia_qtde);

				if($cor == "#D7E1FF") $cor = '#F0F4FF';
				else                  $cor = '#D7E1FF';

				$sql2 = "SELECT count(*) 
					FROM tbl_os
					JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
					JOIN tbl_os_revenda      USING(os_revenda)
					WHERE tbl_os.produto = $produto
					AND   tbl_os.fabrica = $login_fabrica
					AND   tbl_os.posto   = $posto
					AND   tbl_os_revenda.lote_revenda IS NOT NULL
					AND   tbl_os.data_nf_saida        IS NOT NULL";
				$res2 = pg_exec ($con,$sql2);
				$item_devolvido = trim(pg_result($res2,0,0));

				$sql2 = "SELECT count(*) 
					FROM tbl_os
					JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
					JOIN tbl_os_revenda      USING(os_revenda)
					WHERE tbl_os.produto = $produto
					AND   tbl_os.fabrica = $login_fabrica
					AND   tbl_os.posto   = $posto
					AND   tbl_os_revenda.lote_revenda IS NOT NULL
					AND   tbl_os.data_nf_saida        IS NOT NULL
					AND   tbl_os.conferido_saida      IS TRUE";
				$res2 = pg_exec ($con,$sql2);
				$item_devolvido_recebido = trim(pg_result($res2,0,0));

				$dive1 = $conferencia_qtde - $qtde;                  if($dive1==0)$dive1='';
				$dive2 = $item_devolvido_recebido - $item_devolvido; if($dive2==0)$dive2='';
				$saldo = $conferencia_qtde-$item_devolvido;

				echo "<tr bgcolor='$cor' class='Conteudo'>";
				echo "<td align='center' width='20' height='20'>";
				echo  "<img src='../imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','&produto=$produto&revenda=$revenda&posto=$posto','visualizar_$i','$cor');\" align='absmiddle'>";
				echo "</td>";
				echo "<td align='left'><a href=\"javascript:MostraEsconde('dados_$i','&produto=$produto&revenda=$revenda&posto=$posto','visualizar_$i','$cor');\"title='$codigo_posto - $nome'>$nome </td>";
				echo "<td align='left' title='$referencia - $descricao'>&nbsp;&nbsp;$descricao</td>";
				echo "<td align='right'title='Quantidade Enviada pela Revenda'> $qtde</td>";
				echo "<td align='right' title='Divergência entre a qtde enviada pela revenda e a quantidade recebida pelo posto'><font color='#FF0000'>$dive1</font></td>";
				echo "<td align='right' title='Quantidade recebida pelo posto'> $conferencia_qtde</td>";
				echo "<td align='right' title='Quantidade devolvida pelo posto'> $item_devolvido</td>";
				echo "<td align='right' title='Total de produtos presente no posto'><font color='#009900'>$saldo</font></td>";
				echo "<td align='right' title='Itens recebidos pela revenda'>$item_devolvido_recebido</td>";
				echo "<td align='right' title='Divergência entre a qtde enviada pelo posto e a recebida pela revenda'><font color='#FF0000'>$dive2</font></td>";
				echo "</tr>";

					echo "<tr heigth='1' class='Conteudo' bgcolor='$cor'><td colspan='12'>";
					echo "<DIV class='exibe' id='dados_$i' value='1' align='center'>";
					echo "</DIV>";
					echo "</td></tr>";

				$total_dive1 += $dive1;
				$total_dive2 += $dive2;
				$total_saldo += $saldo;
				$total_qtde  += $qtde;
				$total_cq    += $conferencia_qtde;
				$total_id    += $item_devolvido;
				$total_idr   += $item_devolvido_recebido;
			}
			echo "</tbody>";
			echo "<tfoot>";
			echo "<tr bgcolor='#BCCBE0' class='Conteudo'>";
			echo "<td align='center' width='20' height='20' colspan='3'><b>TOTAL</b></td>";
			echo "<td align='right'title='Quantidade Enviada pela Revenda'><b> $total_qtde</b></td>";
			echo "<td align='right' title='Divergência entre a qtde enviada pela revenda e a quantidade recebida pelo posto'><font color='#FF0000'><b>$total_dive1</b></font></td>";
			echo "<td align='right' title='Quantidade recebida pelo posto'><b>$total_cq</b></td>";
			echo "<td align='right' title='Quantidade devolvida pelo posto'><b> $total_id</b></td>";
			echo "<td align='right' title='Total de produtos presente no posto'><font color='#009900'><b>$total_saldo</b></font></td>";
			echo "<td align='right' title='Itens recebidos pela revenda'><b>$total_idr</b></td>";
			echo "<td align='right' title='Divergência entre a qtde enviada pelo posto e a recebida pela revenda'><font color='#FF0000'><b>$total_dive2</b></font></td>";
			echo "</tr>";
			echo "</tfoot>";

			echo "</table><br>";
		}else{
			echo "<center><font color='#FF0000'>Nenhum lote encontrado</font></center>";
		}
	}
}
if(strlen($msg_erro)>0) echo "<div name='erro' class='Erro'>$msg_erro</div>";

include "rodape.php";

?>
