<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";

include "funcoes.php";
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);
    
    require "../classes/form/GeraComboType.php";
} 
$msg = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($_POST["locacao"]) > 0) $locacao = trim($_POST["locacao"]);

if ($acao == "GRAVAR") {
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);
	$produto_voltagem   = trim($_POST["produto_voltagem"]);
	$type               = trim($_POST["type"]);
	$serie              = trim($_POST["serie"]);
	$codigo_fabricacao    = trim($_POST["codigo_fabricacao"]);
	$pedido             = trim($_POST["pedido"]);
	$nota_fiscal        = trim($_POST["nota_fiscal"]);
	$data_emissao       = trim($_POST["data_emissao"]);
	$data_vencimento    = trim($_POST["data_vencimento"]);
	$codigo_posto       = trim($_POST["codigo_posto"]);


	if (strlen($produto_referencia) > 0 || strlen($produto_voltagem) > 0) {
		$sql =	"SELECT tbl_produto.produto, tbl_produto.linha
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica = $login_fabrica";
		if (strlen($produto_referencia) > 0) {
			$x_produto_referencia = str_replace(".","",$produto_referencia);
			$x_produto_referencia = str_replace("-","",$x_produto_referencia);
			$x_produto_referencia = str_replace("/","",$x_produto_referencia);
			$x_produto_referencia = str_replace(" ","",$x_produto_referencia);
			$sql .= " AND tbl_produto.referencia_pesquisa = '$x_produto_referencia'";
		}
		if (strlen($produto_voltagem) > 0) {
			$sql .= " AND tbl_produto.voltagem = '$produto_voltagem'";
		}
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0) {
			$msg .= " Produto digitado não encontrado. ";
		} else if (pg_numrows($res) == 1) {
			$produto = trim(pg_result($res,0,produto));
			$linha   = pg_fetch_result($res, 0, 'linha');
		} else if (pg_numrows($res) > 0) {
			$msg .= " Favor preencher mais campos referente ao Produto. ";
		}
	}else{
		$msg .= " Favor preencher os campos referente ao Produto. ";
	}

	if (strlen($serie) == 0) {
		$msg .= " Favor preencher o campo Número de Série. ";
	}else{
		$x_serie = "'" . $serie . "'";

		$sqlX = "SELECT locacao
				FROM tbl_locacao
				WHERE serie = $x_serie";
		if (strlen($locacao) > 0) $sqlX .= " AND locacao NOT IN ($locacao);";
		$resX = pg_exec($con,$sqlX);

		if (pg_numrows($resX) > 0) {
			$msg .= " Número de Série já cadastrado. ";
		}
	}

	/*$x_data_fabricacao = fnc_formata_data_pg($data_fabricacao);
	if (strlen($x_data_fabricacao) == 0 || $x_data_fabricacao == "null") {
		$msg .= " Favor preencher o campo Data de Fabricação. ";
	}*/
	if (strlen($codigo_fabricacao) == 0) {
		$msg .= " Favor preencher o campo Código de fabricação. ";
	}

	if (strlen($pedido) == 0) {
		$msg .= " Favor preencher o campo Número do AE. ";
	}else{
		$x_pedido = "'" . $pedido . "'";
	}

	if (strlen($nota_fiscal) == 0) {
		$msg .= " Favor preencher o campo Nota Fiscal da B&D. ";
	}else{
		$x_nota_fiscal = "'" . $nota_fiscal . "'";
	}

	$x_data_emissao = fnc_formata_data_pg($data_emissao);
	if (strlen($x_data_emissao) == 0 || $x_data_emissao == "null") {
		$msg .= " Favor preencher o campo Data de Emissão. ";
	}

	if (1 == 2) {
		$x_data_vencimento = fnc_formata_data_pg($data_vencimento);
		if (strlen($x_data_vencimento) == 0 || $x_data_vencimento == "null") {
			$msg .= " Favor preencher o campo Data de Vencimento. ";
		}else{
			$data_abertura = date("Y-m-d");

			$sql = "SELECT ($x_data_emissao::date + (('6 months')::interval))::date;";
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				$data_final_garantia = trim(pg_result($res,0,0));
			}

			if ($data_final_garantia < $data_abertura) {
				$msg .= " Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4) . ". ";
			}
		}
	}

	if (strlen($produto) > 0) {
		$sql =	"SELECT tbl_lista_basica.lista_basica
				FROM    tbl_lista_basica
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto
				AND     tbl_lista_basica.type IS NOT NULL;";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			$sql =	"SELECT tbl_lista_basica.lista_basica
					FROM    tbl_lista_basica
					WHERE   tbl_lista_basica.fabrica = $login_fabrica
					AND     tbl_lista_basica.produto = $produto
					AND     tbl_lista_basica.type    = '$type';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 0) {
				$msg .= " Type informado não pertence a este produto. ";
			}else{
				$x_type = "'" . $type . "'";
			}
		}else{
			$x_type = "null";
		}
	}

	if(strlen($codigo_posto) > 0) {
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE codigo_posto = '$codigo_posto'
				AND   fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0) {
			$posto = pg_result($res,0,posto);
		}else{
			$posto = 'null';
		}
	}
	if (strlen($msg) == 0) {
		if ($linha == 687) {
			$prazo_garantia = '1 year';
		} else {
			$prazo_garantia = '6 months';
		}


		$res = pg_exec($con,"BEGIN TRANSACTION");

		if (strlen($locacao) == 0) {
			$sql =	"INSERT INTO tbl_locacao (
						produto         ,
						type            ,
						serie           ,
						data_fabricacao ,
						codigo_fabricacao,
						pedido          ,
						nota_fiscal     ,
						data_emissao    ,
						data_vencimento ,
						execucao        ,
						posto
					) VALUES (
						$produto           ,
						$x_type            ,
						$x_serie           ,
						current_date ,
						'$codigo_fabricacao',
						$x_pedido          ,
						$x_nota_fiscal     ,
						$x_data_emissao    ,
						($x_data_emissao::date + (('$prazo_garantia')::interval))::date,
						'Locação'          ,
						$posto
					);";
		}else{
			$sql =	"UPDATE tbl_locacao SET
						produto         = $produto           ,
						type            = $x_type            ,
						serie           = $x_serie           ,
						data_fabricacao = current_date       ,
						codigo_fabricacao = '$codigo_fabricacao',
						pedido          = $x_pedido          ,
						nota_fiscal     = $x_nota_fiscal     ,
						data_emissao    = $x_data_emissao    ,
						data_vencimento = ($x_data_emissao::date + (('$prazo_garantia')::interval))::date          ,
						posto           = $posto
					WHERE locacao = $locacao;";
		}
		$res = pg_exec($con,$sql);
		$msg = pg_errormessage($con);

		if (strlen($msg) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header("Location: $PHP_SELF");
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($acao == "APAGAR") {
	$res = pg_exec($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_locacao WHERE locacao = $locacao;";
	$res = pg_exec($con,$sql);
	$msg = pg_errormessage($con);

	if (strlen($msg) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title = "PEDIDO DE LOCAÇÃO";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: left;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<br>

<?
if (strlen($locacao) > 0 && strlen($msg) == 0) {
	$sql =	"SELECT tbl_produto.referencia                                               ,
					tbl_produto.descricao                                                ,
					tbl_produto.voltagem                                                 ,
					tbl_locacao.type                                                     ,
					tbl_locacao.serie                                                    ,
					tbl_locacao.codigo_fabricacao          AS codigo_fabricacao          ,
					tbl_locacao.pedido                                                   ,
					tbl_locacao.nota_fiscal                                              ,
					TO_CHAR(tbl_locacao.data_emissao,'DD/MM/YYYY')    AS data_emissao    ,
					TO_CHAR(tbl_locacao.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
					tbl_posto.nome                         AS nome_posto                 ,
					tbl_posto_fabrica.codigo_posto
			FROM tbl_locacao
			JOIN tbl_produto USING (produto)
			JOIN tbl_posto ON tbl_posto.posto  = tbl_locacao.posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_locacao.locacao = $locacao;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$produto_referencia = trim(pg_result($res,0,referencia));
		$produto_descricao  = trim(pg_result($res,0,descricao));
		$produto_voltagem   = trim(pg_result($res,0,voltagem));
		$type               = trim(pg_result($res,0,type));
		$serie              = trim(pg_result($res,0,serie));
		$codigo_fabricacao    = trim(pg_result($res,0,codigo_fabricacao));
		$pedido             = trim(pg_result($res,0,pedido));
		$nota_fiscal        = trim(pg_result($res,0,nota_fiscal));
		$data_emissao       = trim(pg_result($res,0,data_emissao));
		$data_vencimento    = trim(pg_result($res,0,data_vencimento));
		$nome_posto         = trim(pg_result($res,0,nome_posto));
		$codigo_posto       = trim(pg_result($res,0,codigo_posto));
	}
}

if (strlen($msg) > 0) {
?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<?
}
?>

<form name="frm_locacao" method="post" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<input type="hidden" name="locacao" value="<? echo $locacao; ?>">

<table width="500" border="0" cellpadding="2" cellspacing="1"  align='center'>
	<tr class="Titulo" bgcolor="#D9E2EF">
		<td>Referência</td>
		<td>Descrição</td>
		<td>Voltagem</td>
		<td>Type</td>
	</tr>
	<tr>
		<td>
			<input type="text" name="produto_referencia" size="12" value="<? echo $produto_referencia; ?>" class="frm">
			&nbsp;
			<img src="imagens/btn_lupa.gif" border="0" align="absmiddle" style="cursor: hand;" onclick="javascript: fnc_pesquisa_produto (document.frm_locacao.produto_referencia, document.frm_locacao.produto_descricao, 'referencia', document.frm_locacao.produto_voltagem)">
		</td>
		<td>
			<input type="text" name="produto_descricao" size="25" value="<? echo $produto_descricao; ?>" class="frm">
			&nbsp;
			<img src="imagens/btn_lupa.gif" border="0" align="absmiddle" style="cursor: hand;" onclick="javascript: fnc_pesquisa_produto (document.frm_locacao.produto_referencia, document.frm_locacao.produto_descricao, 'descricao', document.frm_locacao.produto_voltagem)">
		</td>
		<td>
			<input type="text" name="produto_voltagem" size="8" value="<? echo $produto_voltagem; ?>" class="frm">
		</td>
		<td>
		    <? 
		     GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null, array("class"=>"frm"));
      		     echo GeraComboType::getElement();
		    ?>
			
		</td>
	</tr>
</table>

<br>

<table width="500" border="0" cellpadding="2" cellspacing="1" align='center'>
	<tr class="Titulo" bgcolor="#D9E2EF">
		<td>Execução</td>
		<td>Número de Série</td>
		<td>Código de Fabricação</td>
	</tr>
	<tr>
		<td><input type="text" name="execucao" size="12" value="Locação" class="frm" readonly></td>
		<td><input type="text" name="serie" size="12" value="<? echo $serie; ?>" class="frm"></td>
		<td><input type="text" name="codigo_fabricacao" size="12" value="<? echo $codigo_fabricacao; ?>" class="frm"></td>
	</tr>
</table>

<br>

<table width="500" border="0" cellpadding="2" cellspacing="1"  align='center'>
	<tr class="Titulo" bgcolor="#D9E2EF">
		<td>Número do AE</td>
		<td>Nota Fiscal da B&D</td>
		<td>Data de Emissão</td>
		<?if (strlen($data_vencimento) > 0) {?>
		<td>Data de Vencimento</td>
		<?}?>
	</tr>
	<tr>
		<td><input type="text" name="pedido" size="12" value="<? echo $pedido; ?>" class="frm"></td>
		<td><input type="text" name="nota_fiscal" size="12" value="<? echo $nota_fiscal; ?>" class="frm"></td>
		<td><input type="text" name="data_emissao" size="12" value="<? echo $data_emissao; ?>" class="frm"></td>
		<?if (strlen($data_vencimento) > 0) {?>
		<td><input type="text" name="data_vencimento" size="12" value="<? echo $data_vencimento; ?>" class="frm" readonly></td>
		<?}?>
	</tr>
</table>

<br>

<table width="500" border="0" cellpadding="2" cellspacing="1"  align='center'>
	<tr class="Titulo" bgcolor="#D9E2EF">
		<td align='center'>Código do posto</td>
		<td align='center'>Razão Social</td>
	</tr>
	<tr>
		<td><input type="text" name="codigo_posto" size="12" value="<? echo $codigo_posto; ?>" class="frm"></td>
		<td><input type="text" name="nome_posto" size="50" value="<? echo $nome_posto; ?>" class="frm"></td>
	</tr>
</table>

<br>

<center>
<img border='0' src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_locacao.acao.value == '') { document.frm_locacao.acao.value='GRAVAR'; document.frm_locacao.submit(); } else { alert('Aguarde submissão'); }" alt="Gravar Pedido" style="cursor: hand;">
<? if (strlen($locacao) > 0) { ?>
<img border='0' src="imagens_admin/btn_apagar.gif" onclick="javascript: if (confirm('Deseja realmente apagar este pedido?') == true) { if (document.frm_locacao.acao.value == '') { document.frm_locacao.acao.value='APAGAR'; document.frm_locacao.submit(); } else { alert('Aguarde submissão'); } }" alt="Apagar Pedido" style="cursor: hand;">
<? } ?>
<img border='0' src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_locacao.acao.value == '') { document.frm_locacao.acao.value='LIMPAR'; window.location='<? echo $PHP_SELF ?>'; } else { alert('Aguarde submissão'); }" alt="Limpar Campos" style="cursor: hand;">
</center>

</form>

<br>

<?
include "rodape.php";
?>
