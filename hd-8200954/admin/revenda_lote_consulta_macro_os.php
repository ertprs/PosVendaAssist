<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include '../ajax_cabecalho.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';




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
	});

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
	url = "<?=$PHP_SELF?>?ajax=sim&acao=detalhes" +id+"&cor="+escape(cor) ;

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

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<?
if(strlen($btn_acao) > 0){
	if (strlen($codigo_posto) > 0 ) {
		$sql =	"SELECT tbl_posto.posto
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto = pg_result($res,0,0);
			
		}else{
			$msg_erro .= " Favor informe o posto correto. ";
		}
	}

	if (strlen($revenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
			FROM    tbl_revenda
			WHERE   tbl_revenda.cnpj = '$revenda_cnpj' ";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = pg_result($res,0,0);
		}else{
			$msg_erro .= " Favor informe a revenda correta. ";
		}
	}else $msg_erro .= "Revenda é obrigatório";
}

$qtde_item=0;
echo "<table style=' border:#485989 1px solid; background-color: #F0F4FF' align='center' width='750' border='0' cellspacing='0' class='Conteudo'>\n";

echo "<tr height='20' bgcolor='#BCCBE0'>\n";
echo "<td align='left' colspan='3'><b>Consulta Lote de Produto</b>&nbsp;</td>\n";
echo "<td align='right' class='Conteudo'><a href='revenda_inicial.php'>Menu de Revendas</a></td>\n";
echo "</tr>\n";
echo "<tr><td colspan='4'><br>\n";
$aba = 7;
include "revenda_cabecalho.php";
echo "<br>&nbsp;</td></tr>\n";

echo "<tr height='20'>\n";
echo "<td align='left' colspan='4'>Relatório Macro em Excell </td>\n";
echo "</tr>\n";
echo "<tr height='20'>\n";
echo "<td align='right' ><b>Lote</b>&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='lote' id='lote' value='$lote' class='Caixa'></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right' ><b>Nota Fiscal</b>&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' class='Caixa'></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right'><b>CNPJ da Revenda</b>&nbsp;</td>\n";
echo "<td align='left'><input type='text' name='revenda_cnpj' id='revenda_cnpj'  maxlength='18' value='$revenda_cnpj' class='Caixa' onblur=\"fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\">&nbsp;<img src='../imagens/btn_lupa_novo.gif' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')\"></td>\n";
echo "<td align='right' bgcolor='#F0F4FF' class='Conteudo' ><b>Nome da Revenda</b>&nbsp;</td>\n";
echo "<td align='left' bgcolor='#F0F4FF' class='Conteudo'><input type='text' name='revenda_nome' id='revenda_nome' size='40' maxlength='60' value='$revenda_nome'class='Caixa' >&nbsp;<img src='../imagens/btn_lupa_novo.gif' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')\"></td>\n";
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

echo "<tr heigth='20'>\n";
echo "<td align='right' ><b>Codigo Posto</b>&nbsp;</td>\n";
echo "<td align='left' ><input type='text' name='codigo_posto' maxlength='14' id='codigo_posto' value='$codigo_posto' class='Caixa' onFocus=\"nextfield ='nome_posto'\" onblur=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\">&nbsp;<img src='../imagens/btn_lupa_novo.gif' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\">
</td>\n";
echo "<td align='right' ><b>Nome</b>&nbsp;</td>\n";
echo "<td align='left'><input type='text' name='nome_posto' id='nome_posto' size='40' maxlength='60' value='$nome_posto' class='Caixa' onFocus=\"nextfield ='condicao'\">&nbsp;<img src='../imagens/btn_lupa_novo.gif' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'nome')\"></td>\n";
echo "</tr>\n";

echo "<tr height='20'>\n";
echo "<td align='right' ><b>Quantidade de dias no posto</b>&nbsp;</td>\n";
echo "<td align='left' colspan='3'><input type='text' name='qtde_dias' maxlength='3' size='3' value='$qtde_dias' class='Caixa' > dias";
echo "</tr>\n";
echo "</table>\n";


echo "<table style=' border:#B63434 1px solid; background-color: #EED5D2' align='center' width='750' border='0'height='40'>\n";
echo "<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='btn_acao'  value='Consultar' onClick=\"if (this.value!='Consultar'){ alert('Aguarde');}else {this.value='Consultando...'; /*gravar(this.form,'sim','$PHP_SELF','nao');*/}\" style=\"width: 150px;\"></td>\n";
echo "<td width='300'><div id='saida' style='display:inline;'></div></td>\n";
echo "</tr>\n";
echo "</table>\n";

flush();


if(strlen($btn_acao) > 0 and strlen($msg_erro)==0){

	$lote         = trim($_POST["lote"]);
	$nota_fiscal  = trim($_POST["nota_fiscal"]);
	$codigo_posto = trim($_POST["codigo_posto"]);
	$revenda_cnpj = trim($_POST["revenda_cnpj"]);
	$qtde_dias    = trim($_POST["qtde_dias"]);


	if(strlen($nota_fiscal)>0)  $cond1 = " AND nota_fiscal like '%$nota_fiscal%' ";
	if(strlen($lote) > 0)       $cond2 = " AND lote like '%$lote%' ";

	$sql = "SELECT DISTINCT
			tbl_lote_revenda.lote                                              ,
			tbl_lote_revenda.nota_fiscal                                       ,
			tbl_lote_revenda.lote_revenda                                      ,
			TO_CHAR(data_recebido,'DD/MM/YYYY')               AS data_recebido ,
			tbl_lote_revenda_item.produto
		FROM tbl_lote_revenda
		JOIN tbl_lote_revenda_item USING(lote_revenda)
		WHERE   tbl_lote_revenda.revenda      = $revenda
		AND     tbl_lote_revenda.posto        = $posto
		$cond1 $cond2
		";

	$res = @pg_exec ($con,$sql) ;
	if (pg_numrows($res) > 0) {
		$qtde_item = pg_numrows($res);
		for ($k = 0 ; $k <$qtde_item ; $k++) {
			$lote_revenda[$k]  = trim(pg_result($res,$k,lote_revenda));
			$nota_fiscal[$k]   = trim(pg_result($res,$k,nota_fiscal));
			$data_recebido[$k] = trim(pg_result($res,$k,data_recebido));
			$item_produto[$k]  = trim(pg_result($res,$k,produto));
		}
	}

	$data = date ("d-m-Y-H-i");

	echo `mkdir /tmp/assist`;
	echo `chmod 777 /tmp/assist`;
	//echo `rm /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.xls`;
	echo `rm /tmp/assist/macro-$login_fabrica-$data.html`;
	echo `rm /tmp/assist/macro-$login_fabrica-$data.xls`;
	echo `rm /var/www/assist/www/download/macro-$login_fabrica-$data.zip`;
	$fp = fopen ("/tmp/assist/macro-$login_fabrica-$data.html","w");
	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>Relacionamento de integridade</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");

	if($qtde_item>0){
		for ($k=0;$k<$qtde_item;$k++){
			$sql2 = "SELECT tbl_os.os                                                      ,
							tbl_os.sua_os                                                  ,
							tbl_os.nota_fiscal                                             ,
							tbl_os.produto                                                 ,
							tbl_os.nota_fiscal_saida                                       ,
							tbl_os.conferido_saida                                         ,
							TO_CHAR(tbl_os.data_nf_saida,'dd/mm/YYYY')   AS data_nf_saida  ,
							TO_CHAR(tbl_os.data_abertura,'dd/mm/YYYY')   AS data_abertura  ,
							TO_CHAR(tbl_os.data_fechamento,'dd/mm/YYYY') AS data_fechamento
				FROM tbl_os
				JOIN tbl_os_revenda_item ON tbl_os.os = tbl_os_revenda_item.os_lote
				JOIN tbl_os_revenda      USING(os_revenda)
				WHERE tbl_os.fabrica = $login_fabrica
				AND   lote_revenda   = ".$lote_revenda[$k]."
				AND   tbl_os_revenda.lote_revenda IS NOT NULL
				AND   tbl_os.nota_fiscal = '".$nota_fiscal[$k]."'
				AND   tbl_os.produto     = ".$item_produto[$k];

			$res2 = pg_exec ($con,$sql2);
			$msg_erro .= pg_errormessage($con);
			if(strlen($msg_erro)>0){
				echo $sql2;exit;
			}

			if(pg_numrows($res2)>0){
				fputs ($fp,"<table width='90%' style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0'>");
				fputs ($fp, "<tr  bgcolor='#BCCBE0' style='color: #000000; text-align: center; font-size:12px'>");
				fputs ($fp,"<th>OS</th>");
				fputs ($fp,"<th>Nota Fiscal</th>");
				fputs ($fp,"<th>CNPJ Revenda</th>");
				fputs ($fp,"<th>Código Posto</th>");
				fputs ($fp,"<th>Recebido</th>");
				fputs ($fp,"<th>Abertura</th>");
				fputs ($fp,"<th>Fechamento</th>");
				fputs ($fp,"<th>Referência</th>");
				fputs ($fp,"<th>Descrição</th>");
				if($login_fabrica == 3) fputs ($fp,"<th>Marca</th>");
				fputs ($fp,"<th>NF Saída</th>");
				fputs ($fp,"<th>Data NF Saída</th>");
				fputs ($fp,"<th>Recebido</th>");
				fputs ($fp,"</tr>");

				for($y=0; $y < pg_numrows($res2);$y++){
					$os              = pg_result($res2,$y,os);
					$sua_os          = pg_result($res2,$y,sua_os);
					$nf              = pg_result($res2,$y,nota_fiscal);
					$produto         = pg_result($res2,$y,produto);
					$data_abertura   = pg_result($res2,$y,data_abertura);
					$data_fechamento = pg_result($res2,$y,data_fechamento);
					$nf_saida        = pg_result($res2,$y,nota_fiscal_saida);
					$data_nf_saida   = pg_result($res2,$y,data_nf_saida);
					$conferido_saida = pg_result($res2,$y,conferido_saida);

					if($cor == "#D7E1FF") $cor = '#F0F4FF';
					else                  $cor = '#D7E1FF';

					$pro = "SELECT referencia,descricao,tbl_marca.nome as marca
							FROM   tbl_produto
							JOIN   tbl_linha    USING(linha)
							LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
							WHERE  tbl_produto.produto = $produto
							AND    tbl_linha.fabrica   = $login_fabrica";
					$res_p = pg_exec ($con,$pro);
					$referencia = pg_result($res_p,0,referencia);
					$descricao  = pg_result($res_p,0,descricao);
					$marca      = pg_result($res_p,0,marca);

					if($conferido_saida=='t') $conferido_saida = "Dev.";
					else                      $conferido_saida = " - ";

					fputs ($fp,"<tr style='color: #000000; text-align: center; font-size:10px'>");
					fputs ($fp,"<td><a href='os_press.php?os=$os'>$sua_os</a></td>");
					fputs ($fp,"<td>$nf</td>");
					fputs ($fp,"<td>$revenda_cnpj</td>");
					fputs ($fp,"<td>$codigo_posto</td>");
					fputs ($fp,"<td>$data_recebido[$k]</td>");
					fputs ($fp,"<td>$data_abertura</td>");
					fputs ($fp,"<td>$data_fechamento</td>");
					fputs ($fp,"<td>$referencia</td>");
					fputs ($fp,"<td>$descricao</td>");
					if($login_fabrica == 3) fputs ($fp,"<td>$marca</td>");
					fputs ($fp,"<td>$nf_saida</td>");
					fputs ($fp,"<td>$data_nf_saida</td>");
					fputs ($fp,"<td>$conferido_saida</td>");
					fputs ($fp,"</tr>");
				}
				fputs ($fp,"</table>");
				flush();
			}
		}
	}
	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);

	//gera o xls
	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /tmp/assist/macro-$login_fabrica-$data.xls /tmp/assist/macro-$login_fabrica-$data.html`;

	//gera o zip
	echo `cd /tmp/assist/; rm -rf macro-$login_fabrica-$data.zip; zip -o macro-$login_fabrica-$data.zip macro-$login_fabrica-$data.xls > /dev/null`;

	//move o zip para "/var/www/assist/www/download/"
	echo `mv  /tmp/assist/macro-$login_fabrica-$data.zip /var/www/assist/www/download/macro-$login_fabrica-$data.zip`;
		
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='../download/macro-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo</font></a>.</td>";
	echo "</tr>";
	echo "</table>";
	exit;


}

if(strlen($msg_erro)>0) echo "<div name='erro' class='Erro'>$msg_erro</div>\n";
 include "rodape.php";
?>
