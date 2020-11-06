<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';



#------------ Le OS da Base de dados ------------#
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.cliente                                                   ,
					tbl_os.revenda                                                   ,
					tbl_os.serie                                                     ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios
			FROM    tbl_os
			LEFT JOIN    tbl_defeito_reclamado using (defeito_reclamado)
			WHERE   tbl_os.os      = $os
			AND     tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$cliente                     = pg_result ($res,0,cliente);
		$revenda                     = pg_result ($res,0,revenda);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_cnpj                = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
	}
	
	if (strlen($cliente) > 0) {
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

$layout_menu = 'os';
include "cabecalho.php";

?>

<div id="container">
<!-- ------------- Formulário ----------------- -->
<!-- ------------- INFORMAÇÕES DA ORDEM DE SERVIÇO------------------ -->
	<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="os" value="<? echo $HTTP_GET_VARS['os'] ?>">
	<div id="page">
		<h2>Informações sobre a Ordem de Serviço
		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; ">
				OS FABRICANTE
			</div>
			<div id="contentleft2" style="width: 200px; ">
				DATA DE ABERTURA
			</div>
		</div>

		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 200px;font:75%">
				<? echo $sua_os ?>
			</div>
			<div id="contentleft" style="width: 200px;font:75%">
				<? echo $data_abertura ?>
			</div>
		</div>
		</h2>
	</div>

</div>

<!-- ------------- INFORMAÇÕES DO CONSUMIDOR ------------------ -->
<div id="container">
<div id="page">
	<h2>Informações sobre o CONSUMIDOR
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 250px; ">
			NOME DO CONSUMIDOR
		</div>
		<div id="contentleft2" style="width: 150px; ">
			CIDADE
		</div>
		<div id="contentleft2" style="width: 80px; ">
			ESTADO
		</div>
		<div id="contentleft2" style="width: 130px; ">
			FONE
		</div>
	</div>

	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 250px;font:75%">
			<? echo $consumidor_nome ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $consumidor_cidade ?>
		</div>
		<div id="contentleft" style="width: 80px;font:75%">
			<? echo $consumidor_estado ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $consumidor_fone ?>
		</div>
	</div>
	
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 250px; ">
			ENDEREÇO
		</div>
		<div id="contentleft2" style="width: 150px; ">
			NÚMERO
		</div>
		<div id="contentleft2" style="width: 80px; ">
			COMPLEMENTO
		</div>
		<div id="contentleft2" style="width: 130px; ">
			BAIRRO
		</div>
	</div>
	
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 250px;font:75%">
			<? echo $consumidor_endereco ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $consumidor_numero ?>
		</div>
		<div id="contentleft" style="width: 80px;font:75%">
			<? echo $consumidor_complemento ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $consumidor_bairro ?>
		</div>
	</div>

	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 250px; ">
			CEP
		</div>
		<div id="contentleft2" style="width: 150px; ">
			CPF
		</div>
		<div id="contentleft2" style="width: 80px; ">
			RG
		</div>
	</div>
	
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 250px;font:75%">
			<? echo $consumidor_cep ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $consumidor_cpf ?>
		</div>
		<div id="contentleft" style="width: 80px;font:75%">
			<? echo $consumidor_rg ?>
		</div>
	</div>
	
	</h2>
</div>

</div>
<!-- ------------- INFORMAÇÕES DA REVENDA------------------ -->

<div id="container">
<div id="page">
	<h2>Informações sobre a REVENDA
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 150px; ">
			CNPJ REVENDA
		</div>
		<div id="contentleft2" style="width: 150px; ">
			NOME DA REVENDA
		</div>
		<div id="contentleft2" style="width: 150px; ">
			NOTA FISCAL N.
		</div>
		<div id="contentleft2" style="width: 130px; ">
			DATA DA N.F.
		</div>
	</div>
	
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_cnpj ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_nome ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $nota_fiscal ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $data_nf ?>
		</div>
	</div>
	
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 150px; ">
			ENDEREÇO
		</div>
		<div id="contentleft2" style="width: 150px; ">
			NÚMERO
		</div>
		<div id="contentleft2" style="width: 150px; ">
			COMPLEMENTO
		</div>
		<div id="contentleft2" style="width: 130px; ">
			BAIRRO
		</div>
		<div id="contentleft2" style="width: 130px; ">
			CEP
		</div>
	</div>
	
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_endereco ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_numero ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_complemento ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $revenda_bairro ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $revenda_cep ?>
		</div>
	</div>
	
	</h2>
</div>

</div>

<!-- ------------- INFORMAÇÕES DO DEFEITO ------------------ -->

<div id="container">
<div id="page">
	<h2>Defeito Apresentado pelo Cliente
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 650px;font:75%">
			<? echo $defeito_reclamado . " " . $defeito_reclamado_descricao ?>
		</div>
	</div>
	</h2>
</div>
</div>
<div id="container">
<div id="page">
	<h2>Aparência Geral do Produto
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 650px;font:75%">
			<? echo $aparencia_produto ?>
		</div>
	</div>
	</h2>
</div>
</div>
<div id="container">
<div id="page">
	<h2>Acessórios Deixados pelo Cliente
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 650px;font:75%">
			<? echo $acessorios; ?>
		</div>
	</div>
	</h2>
</div>
</div>

<div id="container">
	<div id="page">
<?
// ITENS
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os_produto.os_produto,
					tbl_os_item.qtde         ,
					tbl_defeito.descricao AS defeito_descricao ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao ,
					tbl_peca.referencia      ,
					tbl_peca.descricao       
			FROM	tbl_os_produto
			JOIN	tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN	tbl_peca ON tbl_peca.peca = tbl_os_item.peca
			JOIN    tbl_defeito USING (defeito)
			JOIN    tbl_servico_realizado USING (servico_realizado)
			WHERE   tbl_os_produto.os = $os ";
	$res = pg_exec ($con,$sql);
	
	echo "<table width='100%' border='0'>";
	echo "<tr bgcolor='#cccccc'>";
	echo "<td align='center'><b><font color='#ffffff' face='arial' size='-1'>Referência</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='arial' size='-1'>Descrição</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='arial' size='-1'>Defeito</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='arial' size='-1'>Serviço</font></b></td>";

	if (pg_numrows ($res) > 0) {
		for ($i=0; $i< pg_numrows ($res); $i++){
			$qtde       = pg_result ($res,$i,qtde);
			$referencia = pg_result ($res,$i,referencia);
			$descricao  = pg_result ($res,$i,descricao);
			$defeito    = pg_result ($res,$i,defeito_descricao);
			$servico    = pg_result ($res,$i,servico_realizado_descricao);

			echo "<tr>";

			echo "<td>";
			echo "<font face='arial' size='-1'>";
			echo $referencia;
			echo "</font>";
			echo "</td>";

			echo "<td>";
			echo "<font face='arial' size='-1'>";
			echo $descricao;
			echo "</font>";
			echo "</td>";

			echo "<td>";
			echo "<font face='arial' size='-1'>";
			echo $defeito;
			echo "</font>";
			echo "</td>";

			echo "<td>";
			echo "<font face='arial' size='-1'>";
			echo $servico;
			echo "</font>";
			echo "</td>";

			echo "</tr>";
		}
	}
	echo "</table>";
}
?>
	</div>
</div>

<br><br><br>

<div id='container'>
	<div id="contentleft2" style="width: 150px;">
		&nbsp;
	</div>

	<div id="contentleft2" style="width: 150px;">
	<a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a>
	</div>
</div>

</form>


</table>
</div>
<p>


<p>
<? include "rodape.php";?>



<BODY>
