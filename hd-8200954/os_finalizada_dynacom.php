<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


#------------ Le OS da Base de dados ------------#
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                  ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_os.consumidor_nome                                         ,
					tbl_os.consumidor_cidade                                       ,
					tbl_os.consumidor_fone                                         ,
					tbl_os.consumidor_estado                                       ,
					tbl_os.revenda_cnpj                                            ,
					tbl_os.revenda_nome                                            ,
					tbl_os.nota_fiscal                                             ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
					tbl_os.defeito_reclamado
			FROM    tbl_os
			WHERE   tbl_os.os = $os
			AND     tbl_os.posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$data_fechamento	= pg_result ($res,0,data_fechamento);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
		$defeito_reclamado	= pg_result ($res,0,defeito_reclamado);
	}
}





$title = "Confirmação de Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";

?>


<!-- ------------- INFORMAÇÕES DA ORDEM DE SERVIÇO------------------ -->
<div id="container">
	<div id="page">
		<h2>
		Informações sobre a Ordem de Serviço
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft2" style="width: 200px; ">
				OS FABRICANTE
			</div>
			<div id="contentleft2" style="width: 200px; ">
				DATA DE ABERTURA
			</div>
			<div id="contentleft2" style="width: 200px; ">
				DATA DE FECHAMENTO
			</div>
		</div>
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 200px; ">
				<? echo $sua_os ?>
			</div>
			<div id="contentleft" style="width: 200px; ">
				<? echo $data_abertura ?>
			</div>
			<div id="contentleft" style="width: 200px; ">
				<? echo $data_fechamento ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<!-- ------------- INFORMAÇÕES DO CONSUMIDOR ------------------ -->
<div id="container">
	<div id="page">
		<h2>
		Informações sobre o CONSUMIDOR
		<div id="contentcenter" style="width: 600px;">
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
		
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 250px; ">
				<? echo $consumidor_nome ?>
			</div>
			<div id="contentleft" style="width: 150px; ">
				<? echo $consumidor_cidade ?>
			</div>
			<div id="contentleft" style="width: 80px; ">
				<? echo $consumidor_estado ?>
			</div>
			<div id="contentleft" style="width: 130px; ">
				<? echo $consumidor_fone ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<!-- ------------- INFORMAÇÕES DA REVENDA------------------ -->
<div id="container">
	<div id="page">
		<h2>
		Informações sobre a REVENDA
		<div id="contentcenter" style="width: 600px;">
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
		
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 150px; ">
				<? echo $revenda_cnpj ?>
			</div>
			<div id="contentleft" style="width: 150px; ">
				<? echo $revenda_nome ?>
			</div>
			<div id="contentleft" style="width: 150px; ">
				<? echo $nota_fiscal ?>
			</div>
			<div id="contentleft" style="width: 130px; ">
				<? echo $data_nf ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<!-- ------------- INFORMAÇÕES DO DEFEITO ------------------ -->

<div id="container">
	<div id="page">
		<h2>
		Informações sobre o DEFEITO
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft2">
				<?
				if (strlen($defeito_reclamado) > 0) {
					$sql = "SELECT tbl_defeito_reclamado.descricao
							FROM   tbl_defeito_reclamado
							WHERE  tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado";
					$res = pg_exec ($con,$sql);
					
					if (pg_numrows($res) > 0) {
						$descricao_defeito = trim(pg_result($res,0,descricao));
						echo $descricao_defeito;
					}
				}
				?>
			</div>
		</div>
		</h2>
	</div>
</div>




<!-- =========== FINALIZA TELA NOVA============== -->

<div id="container">
	<div id="page">
		<h2>
		Diagnóstico - Componentes - Manutenções Executadas
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft2" style="width: 160px;">
				Equipamento
			</div>
			<div id="contentleft2" style="width: 300px; ">
				Componente
			</div>
			<div id="contentleft2" style="width: 90px; ">
				Defeito
			</div>
		</div>
<?
$sql = "SELECT  tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_os_produto.serie,
				tbl_os_produto.versao,
				tbl_os_item.serigrafia,
				tbl_defeito.descricao AS defeito,
				tbl_peca.referencia   AS referencia_peca,
				tbl_peca.descricao    AS descricao_peca
		FROM    tbl_os_produto
		JOIN    tbl_os_item USING (os_produto)
		JOIN    tbl_produto USING (produto)
		JOIN    tbl_peca    USING (peca)
		JOIN    tbl_defeito USING (defeito)
		WHERE   tbl_os_produto.os = $os
		ORDER BY tbl_produto.referencia;";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
?>
	<div id="contentcenter" style="width: 600px;">
		<div id="contentleft2" style="width: 160px;font:75% Tahoma, Verdana, Arial, Helvetica, Sans-Serif">
			<? echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) ?>
		</div>
		<div id="contentleft2" style="width: 300px;font:75% Tahoma, Verdana, Arial, Helvetica, Sans-Serif">
			<? echo pg_result ($res,$i,referencia_peca) . " - " . pg_result ($res,$i,descricao_peca) ?>
		</div>
		<div id="contentleft2" style="width: 90px;font:75% Tahoma, Verdana, Arial, Helvetica, Sans-Serif">
			<? echo pg_result ($res,$i,defeito) ?>
		</div>
	</div>
<?
}
?>

		</h2>
	</div>
</div>


<div id='container'>
	&nbsp;
</div>

<div id='container'>
	<div id="contentleft2" style="width: 150px;">
		&nbsp;
	</div>

	<div id="contentleft2" style="width: 150px;">
	<a href="os_cadastro_dynacom.php"><img src="imagens/btn_lancanovaos.gif"></a>
	</div>
	<div id="contentleft2" style="width: 150px;">
	<a href="os_print_dynacom.php?os=<? echo $os ?>"><img src="imagens/btn_imprimir.gif"></a>
	</div>
</div>

<div id='container'>
	&nbsp;
</div>

<? include "rodape.php"; ?>

</div>
<BODY>
