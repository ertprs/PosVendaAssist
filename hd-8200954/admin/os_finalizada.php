<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$sql = "SELECT  tbl_fabrica.os_item_subconjunto
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result ($res,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

#------------ Le OS da Base de dados ------------#
$os = $_GET['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.consumidor_cpf                                       	 ,
					tbl_os.consumidor_celular                                   	 ,
					tbl_os.consumidor_fone_comercial                            	 ,
					tbl_os.consumidor_cep                                       	 ,
					tbl_os.consumidor_endereco                                  	 ,
					tbl_os.consumidor_numero                                    	 ,
					tbl_os.consumidor_complemento                               	 ,
					tbl_os.consumidor_bairro                                    	 ,
					tbl_os.consumidor_email                                     	 ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.cliente                                                   ,
					tbl_os.revenda                                                   ,
					tbl_os.serie                                                     ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_os.obs                                                       ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios                                                ,
					tbl_produto.referencia                                           ,
					tbl_produto.descricao                                            ,
					tbl_produto.voltagem                                             ,
					tbl_os.serie                                                     ,
					tbl_os.codigo_fabricacao                                         ,
					tbl_os.cortesia                                                  ,
					tbl_os.type                                                      ,
					tbl_os.troca_faturada                                            ,
					tbl_os.motivo_troca                                              ,
					tbl_posto_fabrica.codigo_posto                                   
			FROM    tbl_os
			LEFT JOIN    tbl_defeito_reclamado using (defeito_reclamado)
			LEFT JOIN    tbl_produto      USING (produto)
			JOIN		tbl_posto         USING (posto)
			JOIN		tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										  AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_os.os      = $os
			AND     tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$cliente                     = pg_result ($res,0,cliente);
		$revenda                     = pg_result ($res,0,revenda);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$consumidor_nome           	 = pg_fetch_result ($res,0,consumidor_nome);
		$consumidor_fone           	 = pg_fetch_result ($res,0,consumidor_fone);
		$consumidor_celular        	 = pg_fetch_result ($res,0,consumidor_celular);//15091
		$consumidor_fone_comercial 	 = pg_fetch_result ($res,0,consumidor_fone_comercial);
		$consumidor_cep            	 = trim (pg_fetch_result ($res,0,consumidor_cep));
		$consumidor_endereco       	 = trim (pg_fetch_result ($res,0,consumidor_endereco));
		$consumidor_numero         	 = trim (pg_fetch_result ($res,0,consumidor_numero));
		$consumidor_complemento    	 = trim (pg_fetch_result ($res,0,consumidor_complemento));
		$consumidor_bairro         	 = trim (pg_fetch_result ($res,0,consumidor_bairro));
		$consumidor_cidade         	 = pg_fetch_result ($res,0,consumidor_cidade);
		$consumidor_estado         	 = pg_fetch_result ($res,0,consumidor_estado);
		$consumidor_cpf         	 = trim(pg_result ($res,0,consumidor_cpf));
		$consumidor_cpf         	 = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
		$consumidor_email            = pg_fetch_result ($res,0,consumidor_email);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_cnpj                = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$obs                         = pg_result ($res,0,obs);
		$produto_referencia          = pg_result ($res,0,referencia);
		$produto_descricao           = pg_result ($res,0,descricao);
		$produto_voltagem            = pg_result ($res,0,voltagem);
		$serie                       = pg_result ($res,0,serie);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$cortesia                    = pg_result ($res,0,cortesia);
		$type                        = pg_result ($res,0,type);
		$troca_faturada              = pg_result($res,0,troca_faturada);
		$motivo_troca                = pg_result($res,0,motivo_troca);
		$codigo_posto                = pg_result ($res,0,codigo_posto);
	}
	
	if (strlen($cliente) > 0 and $login_fabrica != 15) {
		$sql = "SELECT  tbl_cliente.endereco   ,
						tbl_cliente.numero     ,
						tbl_cliente.complemento,
						tbl_cliente.bairro     ,
						tbl_cliente.cep        ,
						tbl_cliente.cpf        ,
						tbl_cliente.rg
				FROM    tbl_cliente
				WHERE   tbl_cliente.cliente = $cliente;";
		$res1 = pg_exec ($con,$sql);
		
		if (pg_numrows($res1) > 0) {
			$consumidor_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
			$consumidor_numero      = trim(pg_result ($res1,0,numero));
			$consumidor_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
			$consumidor_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
			$consumidor_cep         = trim(pg_result ($res1,0,cep));
			$consumidor_cep         = substr($consumidor_cep,0,2) .".". substr($consumidor_cep,2,3) ."-". substr($consumidor_cep,5,3);
			$consumidor_cpf         = trim(pg_result ($res1,0,cpf));
			$consumidor_cpf         = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
			$consumidor_rg          = trim(pg_result ($res1,0,rg));
		}
	}
	
	if (strlen($revenda) > 0) {
		$sql = "SELECT  tbl_revenda.endereco   ,
						tbl_revenda.numero     ,
						tbl_revenda.complemento,
						tbl_revenda.bairro     ,
						tbl_revenda.cep
				FROM    tbl_revenda
				WHERE   tbl_revenda.revenda = $revenda;";
		$res1 = pg_exec ($con,$sql);
		
		if (pg_numrows($res1) > 0) {
			$revenda_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
			$revenda_numero      = trim(pg_result ($res1,0,numero));
			$revenda_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
			$revenda_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
			$revenda_cep         = trim(pg_result ($res1,0,cep));
			$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
		}
	}
}

$title = "Finalização de Ordem de Serviço";

$layout_menu = 'callcenter';
include "cabecalho.php";

?>

<style type='text/css'>
body {
	text-align: center;

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


/*========================== MENU ===================================*/

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}

</style>

<br />

<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
	<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="os" value="<? echo $os; ?>">
<tr class='cabecalho'>
	<td width='120'>OS FABRICANTE</td>
	<td width='120'>DATA DE ABERTURA</td>
	<td>CONSUMIDOR</td>
</tr>
<tr class='descricao'>
	<td>
	<?
	if ($login_fabrica == 1 or $login_fabrica == 15) {
		echo "<font size='3'>".$codigo_posto.$sua_os."</font>";
	}else{
		echo $sua_os;
	}
	?>
	</td>
	<td><? echo $data_abertura ?></td>
	<td><? echo $consumidor_nome ?></td>
</tr>
<table>

<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td width='30%'>CPF</td>
	<td width='30%'><?=$login_fabrica != 15 ? 'RG' : '' ?></td>
	<td width='30%'>FONE</td>
</tr>
<tr class='descricao'>
	<td><? echo $consumidor_cpf ?></td>
	<td><? echo $consumidor_rg ?></td>
	<td><? echo $consumidor_fone ?></td>
</tr>
<table>

<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td>ENDEREÇO</td>
	<td width='70'>NUMERO</td>
	<td>COMPLEMENTO</td>
</tr>
<tr class='descricao'>
	<td><? echo $consumidor_endereco ?></td>
	<td><? echo $consumidor_numero ?></td>
	<td><? echo $consumidor_complemento ?></td>
</tr>
<table>

<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td >BAIRRO</td>
	<td >CIDADE</td>
	<td width='25'>ESTADO</td>
	<td>CEP</td>

</tr>
<tr class='descricao'>
	<td><? echo $consumidor_bairro ?></td>
	<td><? echo $consumidor_cidade ?></td>
	<td><? echo $consumidor_estado ?></td>
	<td><? echo $consumidor_cep ?></td>
</tr>
</table>

<br />

<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td colspan='5'>INFOMAÇÃO DA REVENDA</td>
</tr>

<tr class='cabecalho'>
	<td width='150'>CNPJ REVENDA</td>
	<td width='150'>NOME DA REVENDA</td>
	<td>NF Nº</td>
	<td>DATA DA NF</td>

</tr>
<tr class='descricao'>
	<td><? echo $revenda_cnpj ?></td>
	<td><? echo $revenda_nome ?></td>
	<td><? echo $nota_fiscal ?></td>
	<td><? echo $data_nf ?></td>
</tr>
<table>

<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td width='250'>ENDEREÇO</td>
	<td width='70'>NUMERO</td>
	<td>COMPLEMENTO</td>
	<td>BAIRRO</td>
	<td>CEP</td>
</tr>
<tr class='descricao'>
	<td><? echo $revenda_endereco ?></td>
	<td><? echo $revenda_numero ?></td>
	<td><? echo $revenda_complemento ?></td>
	<td><? echo $revenda_bairro ?></td>
	<td><? echo $revenda_cep ?></td>
</tr>
</table>

<br>

<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td colspan='<? if ($login_fabrica == 1) echo '5'; else echo '3'; ?>'>INFOMAÇÃO DO PRODUTO</td>
</tr>
<tr class='cabecalho'>
	<td>REFERÊNCIA</td>
	<td>DESCRIÇÃO</td>
	<? if ($login_fabrica == 1) { ?>
	<? if ($cortesia == 't') echo "<td>Type</td>"; ?>
	<td>CÓDIGO FABRICAÇÃO</td>
	<? } ?>
	<td>SÉRIE</td>
</tr>
<tr class='descricao'>
	<td><? echo $produto_referencia ?>&nbsp;</td>
	<td><? echo $produto_descricao; if (strlen($produto_voltagem) > 0) echo " - ".$produto_voltagem; ?>&nbsp;</td>
	<? if ($login_fabrica == 1) { ?>
	<? if ($cortesia == 't') echo "<td>$type&nbsp;</td>"; ?>
	<td><? echo $codigo_fabricacao ?>&nbsp;</td>
	<? } ?>
	<td><? echo $serie ?>&nbsp;</td>
</tr>
</table>

<br />
<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td>Defeito Apresentado pelo Cliente</td>
</tr>
<tr class='descricao'>
	<td><? echo $defeito_reclamado . " " . $defeito_reclamado_descricao ?></td>
</tr>
</table>

<br />
<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td>Aparência Geral do Produto</td>
</tr>
<tr class='descricao'>
	<td><? echo $aparencia_produto ?></td>
</tr>
</table>

<br />
<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td>Acessórios Deixados pelo Cliente</td>
</tr>
<tr class='descricao'>
	<td><? echo $acessorios; ?></td>
</tr>
</table>

<br />
<?
if (strlen($troca_faturada) == 0) {

	// ITENS
	if (strlen($os) > 0) {

		$sql = "SELECT  tbl_os_produto.os_produto                                      ,
						tbl_os_item.qtde                                               ,
						tbl_os_item.posicao                                            ,
						tbl_os_item.peca_original                                      ,
						tbl_defeito.descricao           AS defeito_descricao           ,
						tbl_servico_realizado.descricao AS servico_realizado_descricao ,
						tbl_peca.referencia                                            ,
						tbl_peca.descricao                                             ,
						tbl_produto.referencia          AS subproduto_referencia       ,
						tbl_produto.descricao           AS subproduto_descricao        
				FROM	tbl_os_produto
				JOIN	tbl_os_item           ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN	tbl_peca              ON  tbl_peca.peca             = tbl_os_item.peca
				JOIN	tbl_defeito           USING (defeito)
				JOIN	tbl_servico_realizado USING (servico_realizado)
				JOIN	tbl_produto           ON  tbl_os_produto.produto    = tbl_produto.produto
				WHERE   tbl_os_produto.os = $os";

		$res = pg_exec ($con,$sql);
		
		echo "<table width='700' border='0' align='center'>";
		echo "<tr bgcolor='#d9e2ef'>";
		if($os_item_subconjunto == 't') {
			echo "<td align='center'><b><font color='#000000' face='arial' size='-1'>Subconjuto</font></b></td>";
			echo "<td align='center'><b><font color='#000000' face='arial' size='-1'>Posição</font></b></td>";
		}
		echo "<td align='center'><b><font color='#000000' face='arial' size='-1'>Referência</font></b></td>";
		echo "<td align='center'><b><font color='#000000' face='arial' size='-1'>Descrição</font></b></td>";
		echo "<td align='center'><b><font color='#000000' face='arial' size='-1'>Defeito</font></b></td>";
		echo "<td align='center'><b><font color='#000000' face='arial' size='-1'>Serviço</font></b></td>";

		if (pg_numrows ($res) > 0) {
			for ($i=0; $i< pg_numrows ($res); $i++){
				$qtde                  = pg_result ($res,$i,qtde);
				$referencia            = pg_result ($res,$i,referencia);
				$descricao             = pg_result ($res,$i,descricao);
				$defeito               = pg_result ($res,$i,defeito_descricao);
				$servico               = pg_result ($res,$i,servico_realizado_descricao);
				$subproduto_referencia = pg_result ($res,$i,subproduto_referencia);
				$subproduto_descricao  = pg_result ($res,$i,subproduto_descricao);
				$posicao               = pg_result ($res,$i,posicao);
				$peca_original         = pg_result ($res,$i,peca_original);

				echo "<tr>";

				if($os_item_subconjunto == 't'){
					echo "<td><font face='arial' size='-2'> $subproduto_referencia - $subproduto_descricao </font></td>";
					echo "<td><font face='arial' size='-2'> $posicao </font></td>";
				}

				echo "<td>";
				echo "<font face='arial' size='-2'>";
				echo $referencia;
				echo "</font>";
				echo "</td>";

				echo "<td align='left'>";
				echo "<font face='arial' size='-2'>";
				echo $descricao;
				echo "</font>";
				echo "</td>";

				echo "<td align='left'>";
				echo "<font face='arial' size='-2'>";
				echo $defeito;
				echo "</font>";
				echo "</td>";

				echo "<td align='left'>";
				echo "<font face='arial' size='-2'>";
				echo $servico;
				echo "</font>";
				echo "</td>";

				echo "</tr>";

				if (strlen($peca_original) > 0) {
					$sql = "SELECT referencia from tbl_peca
					where peca = $peca_original and fabrica = $login_fabrica";
					$resOriginal = pg_exec ($con,$sql);
					$referencia_original = pg_result
					($resOriginal,0,referencia);
					echo "<tr>";

					echo "<td colspan='5' align='left'>";
					echo "<font face='Verdana' size='1' color='#CC0066'>";
					echo "A peça <B>$referencia_original</B> digitada pelo posto foi substituída automaticamente pela peça <B>$referencia</B>";
					echo "</font>";
					echo "</td>";
					echo "</tr>";
				}
			}
		}
		echo "</table>";
	}

}else{
	if (strlen($motivo_troca) > 0) {
		$sql =	"SELECT tbl_defeito_constatado.codigo, tbl_defeito_constatado.descricao
				FROM    tbl_defeito_constatado
				WHERE   tbl_defeito_constatado.fabrica = $login_fabrica
				AND     tbl_defeito_constatado.defeito_constatado = $motivo_troca";
		$res_defeito = pg_exec ($con,$sql);
		$motivo_troca = pg_result ($res_defeito,0,codigo) ." - ". pg_result ($res_defeito,0,descricao);
	}
echo "<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>";
echo "<tr class='cabecalho'>";
echo "<td>Motivo Troca</td>";
echo "</tr>";
echo "<tr class='descricao'>";
echo "<td>$motivo_troca</td>";
echo "</tr>";
echo "</table>";

}
?>

<br />

<table width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' align='center'>
<tr class='cabecalho'>
	<td>Observações</td>
</tr>
<tr class='descricao'>
	<td><? 
			 if($obs == 'null' OR $obs == "NULL"){
		        $obs = "";
		     }
			echo nl2br($obs); 
		?>
	</td>
</tr>
</table>

<br>

<center>
		<? if ($cortesia == 't' AND $login_fabrica == 1) { ?>
		<a href='os_cortesia_cadastro.php?os=<? echo $os?>'>
		<? }else{ ?>
		<a href='os_cadastro.php?os=<? echo $os?>'>
		<? } ?>
		<img src='imagens/btn_alterarcinza.gif' style='cursor: hand;'></a>

		<? if ($cortesia == 't' AND $login_fabrica == 1) { ?>
		<a href="os_cortesia_cadastro.php">
		<? }else{ ?>
		<a href="os_cadastro.php">
		<? } ?>
		<img src="imagens/btn_lancanovaos.gif"></a>
</center>

</form>

</table>
</div>
<p>

<p>
<? include "rodape.php";?>