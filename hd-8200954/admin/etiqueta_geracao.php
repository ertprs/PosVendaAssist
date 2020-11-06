<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";
include "funcoes.php";

$admin_privilegios="gerencia";
include "autentica_admin.php";

$msg = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($acao) > 0 && $acao == "GERAR") {
	$sql =	"SELECT tbl_os.consumidor_revenda                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura    ,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_posto.nome                             AS posto_nome       ,
					tbl_os.cliente                                                 ,
					tbl_os.revenda                                                 ,
					tbl_cliente.nome                           AS cliente_nome     ,
					tbl_cliente.endereco                       AS cliente_endereco ,
					tbl_cliente.numero                         AS cliente_numero   ,
					tbl_cliente.bairro                         AS cliente_bairro   ,
					tbl_cliente.cep                            AS cliente_cep      ,
					tbl_cidade_cliente.nome                    AS cliente_cidade   ,
					tbl_cidade_cliente.estado                  AS cliente_estado   ,
					tbl_revenda.nome                           AS revenda_nome     ,
					tbl_revenda.endereco                       AS revenda_endereco ,
					tbl_revenda.numero                         AS revenda_numero   ,
					tbl_revenda.bairro                         AS revenda_bairro   ,
					tbl_revenda.cep                            AS revenda_cep      ,
					tbl_cidade_revenda.nome                    AS revenda_cidade   ,
					tbl_cidade_revenda.estado                  AS revenda_estado
			FROM tbl_etiqueta_os
			JOIN tbl_os ON tbl_os.os = tbl_etiqueta_os.os
						AND tbl_os.fabrica = $login_fabrica
			JOIN tbl_produto ON  tbl_produto.produto = tbl_os.produto
			JOIN tbl_posto   ON  tbl_posto.posto     = tbl_os.posto
			LEFT JOIN tbl_cliente on tbl_cliente.cliente = tbl_os.cliente
			LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_os.revenda
			LEFT JOIN tbl_cidade tbl_cidade_cliente on tbl_cidade_cliente.cidade =  tbl_cliente.cidade
			LEFT JOIN tbl_cidade tbl_cidade_revenda on tbl_cidade_revenda.cidade =  tbl_revenda.cidade
			WHERE tbl_os.fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);

	$total_etiqueta = pg_numrows($res);
	
	if ($total_etiqueta > 0) {
		flush();

		$data = date("Y_m_d-H_i_s");

		$arq = fopen("/tmp/assist/etiqueta-$login_fabrica-$data.html","w");
		
		fputs($arq,"<html>");
		fputs($arq,"<body>");
		
		for($j = 0 ; $j < $total_etiqueta ; $j++) {
			$consumidor_revenda = trim(pg_result($res,$j,consumidor_revenda));
			$data_abertura      = trim(pg_result($res,$j,data_abertura));
			$produto_referencia = trim(pg_result($res,$j,referencia));
			$produto_descricao  = trim(pg_result($res,$j,descricao));
			$posto_nome         = trim(pg_result($res,$j,posto_nome));
			$cliente            = trim(pg_result($res,$j,cliente));
			$revenda            = trim(pg_result($res,$j,revenda));

			if ($consumidor_revenda == "C" AND strlen($cliente) > 0) {
				$nome     = trim(pg_result($res,$j,cliente_nome));
				$endereco = trim(pg_result($res,$j,cliente_endereco));
				$numero   = trim(pg_result($res,$j,cliente_numero));
				$bairro   = trim(pg_result($res,$j,cliente_bairro));
				$cidade   = trim(pg_result($res,$j,cliente_cidade));
				$cep      = trim(pg_result($res,$j,cliente_cep));
				$estado   = trim(pg_result($res,$j,cliente_estado));
			}else{
				$nome     = trim(pg_result($res,$j,revenda_nome));
				$endereco = trim(pg_result($res,$j,revenda_endereco));
				$numero   = trim(pg_result($res,$j,revenda_numero));
				$bairro   = trim(pg_result($res,$j,revenda_bairro));
				$cidade   = trim(pg_result($res,$j,revenda_cidade));
				$cep      = trim(pg_result($res,$j,revenda_cep));
				$estado   = trim(pg_result($res,$j,revenda_estado));
			}

			$produto_referencia = strtoupper($produto_referencia);
			$produto_descricao  = strtoupper($produto_descricao);
			$posto_nome         = strtoupper($posto_nome);
			$nome               = strtoupper($nome);
			$endereco           = strtoupper($endereco);
			$bairro             = strtoupper($bairro);
			$cidade             = strtoupper($cidade);
			$estado             = strtoupper($estado);
			
			if ($j % 10 == 0) {
				if ($j == 0) {
					fputs($arq,"<table border='0'>");
				}else{
					fputs($arq,"</table>");
					fputs($arq,"<table border='0'>");
				}
			}
			
			fputs($arq,"<tr>");
			fputs($arq,"<td width='56%' nowrap>Sr.(a) <B>$nome</B><br>");
			fputs($arq,"Seu produto - <B>$produto_descricao</B><br>");
			fputs($arq,"Modelo <B>$produto_referencia</B><br>");
			fputs($arq,"Posto - <B>$posto_nome</B><br>");
			fputs($arq,"Data: <B>$data_abertura</B></TD>");
			fputs($arq,"<TD nowrap>Cons.: <B>$nome</B><br>");
			fputs($arq,"Endereço: <B>$endereco</B> &nbsp; Nº: <B>$numero</B> <br>Bairro: <B>$_bairro</B><br>");
			fputs($arq,"Cidade: <B>$cidade / $estado</B><br>");
			fputs($arq,"CEP: <B>".substr($cep,0,5)."-".substr($cep,5,3)."</B></TD>");
			fputs($arq,"</TR>");
			fputs($arq,"<tr><td colspan='2' height='10'>&nbsp;</td>");
			fputs($arq,"</tr>");
		}
		fputs($arq,"</body>");
		fputs($arq,"</html>");
		fclose($arq);
		
		echo `htmldoc --webpage --size 216x279mm --fontsize 11 --left 4mm --top -16pt -f /www/assist/www/admin/xls/etiqueta-$login_fabrica-$data.pdf /tmp/assist/etiqueta-$login_fabrica-$data.html`;

		echo "<br>";
		echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#000000'><b>Etiqueta gerada com sucesso!<br><a href='xls/etiqueta-$login_fabrica-$data.pdf' target='_blank'>Clique aqui</a> para fazer o download do arquivo.</b></font></p>";
		exit;
	}
}

$layout_menu = "gerencia";
$title = "Cadastro de Produção";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.Menu {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}
</style>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="600" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="FormEtiqueta" method="post" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<?
$sql =	"SELECT COUNT(etiqueta_os) AS qtde_etiqueta
		FROM tbl_etiqueta_os;";
$res = pg_exec($con,$sql);

echo "Quantidade de OS enviada p/ geração de etiqueta: " . trim(pg_result($res,0,qtde_etiqueta));
?>

<br><br>

<center>
<button type="button" name="botao" title="Clique aqui para gerar as etiquetas" onclick="javascript: document.FormEtiqueta.acao.value='GERAR'; document.FormEtiqueta.submit();">Gerar Etiqueta</button>
</center>

</form>

<?
include "rodape.php";
?>
