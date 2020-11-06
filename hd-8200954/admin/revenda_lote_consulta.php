<center>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include '../ajax_cabecalho.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

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
			$msg_erro .= " Favor Informe o Posto Correto. ";
		}
	}

	if (strlen($revenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
			FROM    tbl_revenda
			WHERE   tbl_revenda.cnpj = '$revenda_cnpj' ";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = "'".pg_result($res,0,0)."'";
			$cond5   = " AND tbl_lote_revenda.revenda = $revenda";
		}else{
			$msg_erro .= " Favor Informe a Revenda Correta. ";
		}
	}else $msg_erro .= "Revenda é Obrigatório";
}

//--====== DETALHES DO TREINAMENTO =================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='detalhes') {
	$lote_revenda = $_GET["lote_revenda"];
	$ok           = $_GET["ok"];

	if($ok=='no'){
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
					tbl_posto.nome                                                       ,
					tbl_posto_fabrica.codigo_posto                                       ,
					tbl_lote_revenda.revenda                                             ,
					tbl_revenda.nome                                       AS revenda_nome
			FROM tbl_lote_revenda 
			LEFT JOIN tbl_revenda       USING(revenda)
			JOIN tbl_posto         USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_lote_revenda.fabrica = $login_fabrica
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
		$revenda      = pg_result ($res,0,revenda);
		$revenda_nome = pg_result ($res,0,revenda_nome);

		if($cor == "#D7E1FF") $cor = '#F0F4FF';
		else                  $cor = '#D7E1FF';

		$resposta .= "<br><table border='0' cellspacing='0' width='700' align='center' style=' border:#485989 1px solid; background-color: #e6eef7'>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> Posto</td>\n";
		$resposta .= "<td align='left' title='$codigo_posto - $nome'> $nome </td>\n";
		$resposta .= "</tr>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> Revenda</td>\n";
		$resposta .= "<td align='left'> $revenda_nome </td>\n";
		$resposta .= "</tr>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'>Data</td>\n";
		$resposta .= "<td align='left'> $data</td>\n";
		$resposta .= "</tr>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> Lote</td>\n";
		$resposta .= "<td align='left'> $lote</td>\n";
		$resposta .= "</tr>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> Nota Fiscal</td>\n";
		$resposta .= "<td align='left'> $nota_fiscal</td>\n";
		$resposta .= "</tr>\n";

		$resposta .= "<tr bgcolor='#FFFFFF' class='Conteudo'>\n";
		$resposta .= "<td align='left' width='100'> Data Nota Fiscal</td>\n";
		$resposta .= "<td align='left'> $data_nf</td>\n";
		$resposta .= "</tr>\n";
		$resposta .= "</table><br>\n";
	}



	$sql = "SELECT DISTINCT
				tbl_lote_revenda_item.lote_revenda_item                            ,
				tbl_lote_revenda_item.qtde                                         ,
				tbl_lote_revenda_item.conferencia_qtde                             ,
				tbl_produto.produto                                                ,
				tbl_produto.descricao                                              ,
				tbl_produto.referencia
			FROM      tbl_lote_revenda_item
			LEFT JOIN tbl_produto              USING (produto)
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
			$sql2 = "
					SELECT count(*) FROM tbl_os
					JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
					JOIN tbl_os_revenda      USING(os_revenda)
					WHERE lote_revenda = $lote_revenda
					AND   tbl_os.produto = ".$item_produto[$k]."
					AND data_nf_saida IS NOT NULL";
			$res2 = pg_exec ($con,$sql2) ;
			$item_devolvido[$k] = trim(pg_result($res2,0,0));
			//$resposta .= "$sql2; ";
		}
	}



	if($qtde_item>0){
		$resposta .= "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0' id='tbl_pecas'>\n";
		$resposta .= "<thead>\n";
		$resposta .= "<tr height='25' bgcolor='#BCCBE0'>\n";
		$resposta .= "<td align='center' class='Conteudo' height='25'>Código</td>\n";
		$resposta .= "<td align='center' class='Conteudo'>Descrição</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'>Qtde</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'>Qtde Recebida</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'>Qtde Devolução</td>\n";
		$resposta .= "<td align='center' class='Conteudo' width='100'>Saldo</td>\n";
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
	$resposta .="<td align='center' class='Conteudo' colspan='2'>Total</td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens2</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens3</span></td>\n";
	$resposta .="<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens4</span></td>\n";

	$resposta .="</tr>\n";
	$resposta .="<tr height='12' bgcolor='#BCCBE0'>\n";
	$resposta .="<td align='center' class='Conteudo' colspan='6'><a href='revenda_lote_consulta_macro.php?revenda=$revenda&nf=$nota_fiscal&keepThis=trueTB_iframe=true&height=450&width=750\" title='Detalhado' class=\"thickbox\">Mais detalhes</a></td>\n";

	$resposta .="</tr>\n";
	$resposta .="</tfoot>\n";
	$resposta .="</table>\n";

	if($ok<>'no')echo "ok|$resposta";
	else         echo "$resposta";
	exit;
}

include 'cabecalho.php';

?>
<script language='javascript' src='../ajax.js'></script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=codigo'; ?>", {
		minChars: 3,
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
	$("#nome_posto").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
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
	$("#revenda_cnpj").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=codigo'; ?>", {
		minChars: 3,
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
	$("#revenda_nome").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=nome'; ?>", {
		minChars: 3,
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
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
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
			janela.proximo		= document.frm_os.codigo_posto;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
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
	height:20px;
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

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<?
$qtde_item=0;
if(strlen($msg_erro)>0) echo "<div name='erro' class='msg_erro' style='width:700px; margin-top:0px;'>$msg_erro</div>\n";
echo "<table class='formulario' align='center' width='700' border='0' cellspacing='0' class='Conteudo'>\n";

echo "<tr height='20' class='titulo_tabela'>\n";
echo "<td align='right' colspan='3'>Consulta Lote de Produto</td>\n";
echo "<td align='right' class='Conteudo'><a href='revenda_inicial.php'>Menu de Revendas</a></td>\n";
echo "</tr>\n";
echo "<tr><td colspan='4'><br>\n";
$aba = 3;
include "revenda_cabecalho.php";
echo "<br>&nbsp;</td></tr>\n";
echo "<tr height='20'>\n";
echo "<td align='right' >Lote&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='lote' id='lote' value='$lote' class='frm'></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right'>Nota Fiscal&nbsp;</td>\n";
echo "<td align='left'colspan='3'><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' class='frm'></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right'>CNPJ da Revenda&nbsp;</td>\n";
echo "<td align='left'><input type='text' name='revenda_cnpj' id='revenda_cnpj'  maxlength='18' value='$revenda_cnpj' class='frm'>&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\"></td>\n";
echo "<td align='right'>Nome da Revenda&nbsp;</td>\n";
echo "<td align='left'><input type='text' name='revenda_nome' id='revenda_nome' size='40' maxlength='60' value='$revenda_nome'class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')\"></td>\n";
echo "</tr>\n";
	echo "<input type='hidden' name='revenda_fone'>\n";
	echo "<input type='hidden' name='revenda_cidade'>\n";
	echo "<input type='hidden' name='revenda_estado'>\n";
	echo "<input type='hidden' name='revenda_endereco'>\n";
	echo "<input type='hidden' name='revenda_numero'>\n";
	echo "<input type='hidden' name='revenda_complemento'>\n";
	echo "<input type='hidden' name='revenda_bairro'>\n";
	echo "<input type='hidden' name='revenda_cep'>\n";
	echo "<input type='hidden' name='revenda_email'>\n";

echo "<tr height='20'>\n";
echo "<td align='right' >Codigo Posto&nbsp;</td>\n";
echo "<td align='left' ><input type='text' name='codigo_posto' maxlength='14' id='codigo_posto' value='$codigo_posto' class='frm' onFocus=\"nextfield ='nome_posto'\" >&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\"></td>\n";
echo "<td align='right' >Nome&nbsp;</td>\n";
echo "<td align='left'><input type='text' name='nome_posto' id='nome_posto' size='40' maxlength='60' value='$nome_post' class='frm' onFocus=\"nextfield ='condicao'\">&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'nome')\"></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right' >Quantidade de dias no posto&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='qtde_dias' maxlength='3' size='3' value='$qtde_dias' class='frm' > dias";
echo "</tr>\n";
echo "</table>\n";

echo "<table class='formulario' align='center' width='700' border='0'height='40'>\n";
echo "<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='btn_acao'  value='Consultar' onClick=\"if (this.value!='Consultar'){ alert('Aguarde');}else {this.value='Consultando...'; /*gravar(this.form,'sim','$PHP_SELF','nao');*/}\" style=\"width: 150px;\"></td>\n";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>\n";
echo "</tr>\n";
echo "</table>\n";

flush();


if(strlen($btn_acao) > 0 ){

	
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
			$cond1 $cond2 $cond3 $cond4 $cond5
			ORDER BY tbl_lote_revenda.lote_revenda;";

		$res = pg_exec ($con,$sql) ;
		if (pg_numrows($res) > 0) {

			echo "<br><table class='tabela' align='center' width='98%' border='0' cellspacing='0'>\n";

			echo "<tr class='titulo_coluna'>\n";
			echo "<td align='left'></td>\n";
			echo "<td align='left'> Revenda</td>\n";
			echo "<td align='left'> Posto</td>\n";
			echo "<td align='left'> Data Lançamento</td>\n";
			echo "<td align='left'> Lote</td>\n";
			echo "<td align='left'> Nota Fiscal</td>\n";
			echo "<td align='left'> Data Nota Fiscal</td>\n";
			echo "<td align='left'> Data Recebimento</td>\n";
			echo "<td align='left'> Ação</td>\n";
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

				if($cor == "#F7F5F0") $cor = '#F1F4FA';
				else                  $cor = '#F7F5F0';

				echo "<tr bgcolor='$cor' >\n";
				echo "<td align='center' width='20'>\n";
				echo  "<img src='../imagens/mais.gif' id='visualizar_$i' style='cursor: pointer' onclick=\"javascript:MostraEsconde('dados_$i','$lote_revenda','visualizar_$i','$cor');\" align='absmiddle'>\n";
				echo "</td>\n";
				echo "<td align='left'><a href=\"javascript:MostraEsconde('dados_$i','$lote_revenda','visualizar_$i','$cor');\"> $revenda_nome </td>\n";
				echo "<td align='left' title='$codigo_posto - $nome'>$codigo_posto - $nome </td>\n";
				echo "<td align='left'> $data</td>\n";
				echo "<td align='left'> $lote</td>\n";
				echo "<td align='left'> $nota_fiscal</td>\n";
				echo "<td align='left'> $data_nf</td>\n";
				echo "<td align='left'> $data_recebido</td>\n";
				echo "<td align='left'> ";
				if(strlen($data_recebido)==0) echo "<a href='$PHP_SELF?excluir=$lote_revenda'>Excluir</a>";
				echo "</td>\n";
				echo "</tr>\n";

					echo "<tr heigth='1' class='Conteudo' bgcolor='$cor'><td colspan='9'>\n";
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

?>








<? include "rodape.php"; ?>
