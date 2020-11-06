<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Nº Série.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<!--<img src="imagens/pesquisa_revenda<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">-->
<div style="float:left;width:96%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
</div>
<div style="float:right;width:4%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
	<a href="#" onclick="closeMessage('')" width="50px" height="30px" alt="Fechar" title="Fechar"><img src="css/modal/excluir.png"/></a>
</div>

<?
//produto_serie
if (strlen($HTTP_GET_VARS["produto_serie"]) > 5) {
	if ($login_fabrica <> 43) {
		$produto_serie = strtoupper (trim ($HTTP_GET_VARS["produto_serie"]));
	} else {
		$produto_serie = trim ($HTTP_GET_VARS["produto_serie"]);
	}?>

	<div style="float:left;color:#596d9b;width:100%;background:;height:47px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');float:left;">
	<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Serie do Produto</b><i><?php echo $produto_serie;?></i></font>
	</div>
	<p>

	<?php
	$sql = "SELECT 
				cnpj,
				referencia_produto,
				to_char(data_venda, 'dd/mm/yyyy') as data_venda,
				to_char(data_fabricacao, 'dd/mm/yyyy') as data_fabricacao
			FROM tbl_numero_serie  
			WHERE fabrica = $login_fabrica
				AND serie = trim('$produto_serie')";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Nº Série '$produto_serie' não encontrado. Favor preencher as informações de produto e de revenda manualmente. </h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

}else{
	echo "<h1>Digite ao menos 6 digitos para o número de série.</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
}


if (pg_numrows ($res) > 0 ) {

	$cnpj_revenda       = trim(pg_result($res,0,cnpj));
	$referencia_produto = trim(pg_result($res,0,referencia_produto));
	$data_venda         = trim(pg_result($res,0,data_venda));
	$data_fabricacao    = trim(pg_result($res,0,data_fabricacao));

	$referencia_produto = str_replace (".","",$referencia_produto);
	$referencia_produto = str_replace (",","",$referencia_produto);
	$referencia_produto = str_replace ("-","",$referencia_produto);
	$referencia_produto = str_replace ("/","",$referencia_produto);

	$sql = "
			SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica
			JOIN     tbl_familia ON tbl_familia.familia = tbl_produto.familia and tbl_familia.fabrica = $login_fabrica
			WHERE    tbl_produto.referencia_pesquisa = '$referencia_produto'
			AND      tbl_linha.ativo IS TRUE
			AND      tbl_familia.ativo IS TRUE
			AND      tbl_produto.ativo IS TRUE
			AND      tbl_produto.produto_principal ";

	$res_produto = pg_exec ($con,$sql);

	if (pg_numrows ($res_produto) == 0) {
		echo "<h1>A série foi encontrada, mas o produto '$referencia' não está cadastrado na Telecontrol, entrar em contato com a Fábrica.</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
		
	$produto    = trim(pg_result($res_produto,0,produto));
	$descricao  = trim(pg_result($res_produto,0,descricao));
	$voltagem   = trim(pg_result($res_produto,0,voltagem));
	$referencia = trim(pg_result($res_produto,0,referencia));
	$descricao = str_replace ('"','',$descricao);
	$descricao = str_replace ("'","",$descricao);




	$sql = "SELECT      tbl_revenda.nome              ,
						tbl_revenda.revenda           ,
						tbl_revenda.cnpj              ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado              
			FROM        tbl_revenda
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_estado using(estado)
			WHERE       tbl_revenda.cnpj ='$cnpj_revenda' ";

	$res_revenda = pg_exec ($con,$sql);

	if (pg_numrows ($res_revenda) == 0) {
		echo "<h1>Revenda não encontrada para a série: '$produto_serie'.</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}

	?>
<div style="background:transparent;height: 420px;width:100%;overflow:auto;float:left;">
	 <table width='99%' cellpadding="0" cellspacing="0" border="0" class="display" id="modal_2">
        <thead>
        	<tr style="text-align:left;background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;width:100%;">
        		<th width="15%">CNPJ Revenda</th>
        		<th width="30%">Nome Revenda</th>
        		<th width="25%">Bairro</th>
        		<th width="25%">Cidade</th>
        	</tr>
        </thead>
		<tbody>	
	<?php
	$revenda    = trim(pg_result($res_revenda,0,revenda));
	$nome       = trim(pg_result($res_revenda,0,nome));
	$cnpj       = trim(pg_result($res_revenda,0,cnpj));
	$bairro     = trim(pg_result($res_revenda,0,bairro));
	$cidade     = trim(pg_result($res_revenda,0,nome_cidade));

	echo "<tr>\n";	
	echo "<td>\n";
	if ($login_fabrica <> 43) {
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cnpj</font>\n";
	}
	else {
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$referencia</font>\n";
	}
	echo "</td>\n";
	
	echo "<td>\n";
	if ($_GET['forma'] == 'reload') {
		echo "<a href=\"javascript: opener.document.location = retorno + '?revenda=$revenda' ; this.close() ;\" > " ;
	}else{
		if ($login_fabrica <> 43) {
			?>
			
			<a href="#" onclick="retorna_dados_serie('<?php echo pg_result ($res_revenda,0,nome);?>','<?php echo pg_result ($res_revenda,0,cnpj);?>','<?php echo $cidade;?>','<?php echo pg_result ($res_revenda,0,fone);?>','<?php echo pg_result ($res_revenda,0,endereco);?>','<?php echo pg_result ($res_revenda,0,numero);?>','<?php echo pg_result ($res_revenda,0,complemento);?>','<?php echo pg_result ($res_revenda,0,bairro);?>','<?php echo pg_result ($res_revenda,0,cep);?>','<?php echo pg_result ($res_revenda,0,estado);?>','<?php echo pg_result ($res_revenda,0,email);?>', '<?php echo pg_result ($res_revenda,0,nome);?>','<?php echo pg_result ($res_revenda,0,cnpj);?>','<?php echo $cidade;?>','<?php echo pg_result ($res_revenda,0,fone);?>','<?php echo pg_result ($res_revenda,0,endereco);?>','<?php echo pg_result ($res_revenda,0,numero);?>','<?php echo pg_result ($res_revenda,0,complemento);?>','<?php echo pg_result ($res_revenda,0,bairro);?>','<?php echo pg_result ($res_revenda,0,cep);?>','<?php echo pg_result ($res_revenda,0,estado);?>','<?php echo $data_venda;?>','<?php echo pg_result ($res_produto,0,referencia);?>','<?php echo pg_result ($res_produto,0,descricao);?>','<?php echo pg_result ($res_produto,0,voltagem);?>','<?php echo $data_fabricacao;?>','<?php echo $_GET['revenda_fixo'];?>');"/><font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'><?php echo $nome;?></font></a>
			
			<?php
		} else {
		?>
			<a href="#" onclick="retorna_dados_serie('<?php echo pg_result ($res_produto,0,referencia);?>','<?php echo pg_result ($res_produto,0,descricao);?>','<?php echo pg_result ($res_produto,0,voltagem);?>');" /><font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'><?php echo $descricao;?></font></a>
		<?php
		}
	}

	echo "</td>\n";	
	echo "<td>\n";
	if ($login_fabrica <> 43) {
	echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$bairro</font>\n";
	echo "</td>\n";
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cidade</font>\n";
	echo "</td>\n";
	}
	echo "</tr>\n</tbody>";
	echo "</table>\n";
}
?>
</body>
</html>