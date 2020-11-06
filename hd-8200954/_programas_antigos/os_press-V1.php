<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 7) {
	header ("Location: os_press_filizola.php?os=$os");
	exit;
}

$sql = "SELECT  tbl_fabrica.os_item_subconjunto
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result ($res,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

#------------ Le OS da Base de dados ------------#
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                    ,
					tbl_admin.login                              AS admin            ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao   ,
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
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_defeito_constatado.descricao             AS defeito_constatado,
					tbl_causa_defeito.descricao                  AS causa_defeito    ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios                                                ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os.obs                                                       ,
					tbl_os.excluida                                                  ,
					tbl_produto.referencia                                           ,
					tbl_produto.descricao                                            ,
					tbl_produto.voltagem                                             ,
					tbl_os.serie                                                     ,
					tbl_os.codigo_fabricacao                                         ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo     ,
					tbl_posto.nome                               AS posto_nome       ,
					tbl_os_extra.os_reincidente
			FROM    tbl_os
			JOIN    tbl_posto         ON tbl_posto.posto         = tbl_os.posto
			JOIN    tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_os.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_os_extra ON tbl_os.os               = tbl_os_extra.os
			LEFT JOIN    tbl_admin              ON tbl_os.admin  = tbl_admin.admin
			LEFT JOIN    tbl_defeito_reclamado  ON tbl_os.defeito_reclamado  = tbl_defeito_reclamado.defeito_reclamado
			LEFT JOIN    tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN    tbl_causa_defeito      ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
			LEFT JOIN    tbl_produto            ON tbl_os.produto            = tbl_produto.produto
			WHERE   tbl_os.os = $os ";

	if ($login_e_distribuidor == "t") {
#		$sql .= "AND (tbl_os_extra.distribuidor = $login_posto OR tbl_os.posto = $login_posto) ";
	}else{
		$sql .= "AND tbl_os.posto = $login_posto ";
	}

	$res = pg_exec ($con,$sql);
#	echo $sql . "<br>- ". pg_numrows ($res);

	if (pg_numrows ($res) > 0) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$admin                       = pg_result ($res,0,admin);
		$data_digitacao              = pg_result ($res,0,data_digitacao);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$cliente                     = pg_result ($res,0,cliente);
		$revenda                     = pg_result ($res,0,revenda);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$produto_referencia          = pg_result ($res,0,referencia);
		$produto_descricao           = pg_result ($res,0,descricao);
		$produto_voltagem            = pg_result ($res,0,voltagem);
		$serie                       = pg_result ($res,0,serie);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$defeito_constatado          = pg_result ($res,0,defeito_constatado);
		$causa_defeito               = pg_result ($res,0,causa_defeito);
		$posto_codigo                = pg_result ($res,0,posto_codigo);
		$posto_nome                  = pg_result ($res,0,posto_nome);
		$obs                         = pg_result ($res,0,obs);
		$excluida                    = pg_result ($res,0,excluida);
		$os_reincidente              = trim(pg_result ($res,0,os_reincidente));
		
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
				if (strlen($consumidor_cpf) == 14){
					$consumidor_cpf = substr($consumidor_cpf,0,2) .".". substr($consumidor_cpf,2,3) .".". substr($consumidor_cpf,5,3) ."/". substr($consumidor_cpf,8,4) ."-". substr($consumidor_cpf,12,2);
				}elseif(strlen($consumidor_cpf) == 11){
					$consumidor_cpf = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
				}
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
		if (strlen($revenda_cnpj) == 14){
			$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
		}

	}
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = "Confirmação de Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";

?>
<style type="text/css">

body {
	margin: 0px;
}

.titulo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: xx-small;
	text-align: center;
	color: #596d9b;
	background: #ced7e7;
	border-top: double #596d9b;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: xx-small;
	text-align: center;
	background: #d9e2ef;
}

</style>
<p>

<?
if (strlen($os_reincidente) > 0) {
	$sql = "SELECT  tbl_os.sua_os,
					tbl_os.serie
			FROM    tbl_os
			WHERE   tbl_os.os = $os_reincidente;";
	$res1 = pg_exec ($con,$sql);
	
	$sos   = trim(pg_result($res1,0,sua_os));
	$serie = trim(pg_result($res1,0,serie));
	
	echo "<table width='700px' border='0' cellspacing='1' cellpadding='0'>";
	echo "<tr>";
	echo "<td class='titulo'>ANTENÇÃO</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='titulo'>ORDEM DE SERVIÇO COM NÚMERO DE SÉRIE: $serie REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: $sos</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";
}

if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else 
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD colspan="4"><img src="imagens/top_esq.gif"></TD>
</TR>

<? if ($excluida == "t") { ?>
<TR>
	<TD colspan="4" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
</TR>
<? } ?>

<?
 ##############################################
# se é um distribuidor da Britania consultando #
# exibe o posto                                #
 ##############################################
if ($login_fabrica == 3 AND $login_e_distribuidor == "t"){
?>
<TR>
	<TD class="titulo" colspan="4">POSTO</TD>
</TR>
<TR>
	<TD class="conteudo" colspan="4"><? echo "$posto_codigo - $posto_nome"; ?></TD>
</TR>
<?
}
?>

<TR>
	<TD class="titulo" colspan="2">DATA IMPLANTAÇÃO</TD>
	<TD class="titulo" colspan='2'>
		<? if (strlen($admin) > 0) { ?>USUÁRIO<? } ?>&nbsp;
	</TD>
</TR>
<TR>
	<TD class="conteudo" colspan="2"><? echo $data_digitacao ?></TD>
	<TD class="conteudo" colspan='2'>
		<? if (strlen($admin) > 0) echo strtoupper($admin); ?>&nbsp;
	</TD>
</TR>

<TR>
	<TD class='titulo' colspan='2'>OS FABRICANTE</TD>
	<TD class='titulo' colspan='2'>DATA DE ABERTURA</TD>
</TR>
<TR>
	<TD class='conteudo' colspan='2'>
		<?
		if ($login_fabrica == 1) echo $posto_codigo;
		if (strlen($consumidor_revenda) > 0) echo $sua_os ." - ". $consumidor_revenda;
		else echo $sua_os;
		?>
	</TD>
	<TD class='conteudo' colspan='2'><?echo $data_abertura?></TD>
</TR>

<TR>
	<TD class="titulo">NOME DO CONSUMIDOR</TD>
	<TD class="titulo">CIDADE</TD>
	<TD class="titulo">ESTADO</TD>
	<TD class="titulo">FONE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">NÚMERO</TD>
	<TD class="titulo">COMPLEMENTO</TD>
	<TD class="titulo">BAIRRO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
	<TD class="conteudo"><? echo $consumidor_numero ?></TD>
	<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
	<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">CEP</TD>
	<TD class="titulo">CPF/CNPJ DO CONSUMIDOR</TD>
	<TD class="titulo">RG</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
	<TD class="conteudo"><? echo $consumidor_rg ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">CNPJ REVENDA</TD>
	<TD class="titulo">NOME DA REVENDA</TD>
	<TD class="titulo">NF NÚMERO</TD>
	<TD class="titulo">DATA DA NF</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_cnpj ?></TD>
	<TD class="conteudo"><? echo $revenda_nome ?></TD>
	<TD class="conteudo"><? echo $nota_fiscal ?></TD>
	<TD class="conteudo"><? echo $data_nf ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">NÚMERO</TD>
	<TD class="titulo">COMPLEMENTO</TD>
	<TD class="titulo">BAIRRO</TD>
	<TD class="titulo">CEP</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_endereco ?></TD>
	<TD class="conteudo"><? echo $revenda_numero ?></TD>
	<TD class="conteudo"><? echo $revenda_complemento ?></TD>
	<TD class="conteudo"><? echo $revenda_bairro ?></TD>
	<TD class="conteudo"><? echo $revenda_cep ?></TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="titulo">PRODUTO REFERÊNCIA</TD>
	<TD class="titulo">DESCRIÇÃO DO PRODUTO</TD>
	<? if ($login_fabrica == 1) { ?>
	<TD class="titulo">VOLTAGEM</TD>
	<TD class="titulo">CÓDIGO FABRICAÇÃO</TD>
	<? } ?>
	<TD class="titulo">SÉRIE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $produto_referencia ?></TD>
	<TD class="conteudo"><? echo $produto_descricao ?></TD>
	<? if ($login_fabrica == 1) { ?>
	<TD class="conteudo"><? echo $produto_voltagem ?></TD>
	<TD class="conteudo"><? echo $codigo_fabricacao ?></TD>
	<? } ?>
	<TD class="conteudo"><? echo $serie ?></TD>
</TR>
</TABLE>

<p>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><img src="imagens/top_defeitocliente.gif"></TD>
</TR>
<TR>
	<TD class="conteudo">Defeito reclamado: &nbsp;<? echo $defeito_reclamado_descricao ?></TD>
</TR>
<TR>
	<TD class="conteudo">Defeito constatado: &nbsp;<? echo $defeito_constatado ?></TD>
</TR>
<TR>
	<TD class="conteudo">Causa do defeito: &nbsp;<? echo $causa_defeito ?></TD>
</TR>
</TABLE>

<p>

<? if (strlen($aparencia_produto) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><img src="imagens/top_aparencia.gif"></TD>
</TR>
<TR>
	<TD class="conteudo">&nbsp;<? echo $aparencia_produto ?></TD>
</TR>
</TABLE>
<? } ?>

<p>

<? if (strlen($acessorios) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><img src="imagens/top_acessorios.gif"></TD>
</TR>
<TR>
	<TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
</TR>
</TABLE>
<? } ?>

<p>

<? if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><img src="imagens/top_info.gif"></TD>
</TR>
<TR>
	<TD class="conteudo">&nbsp;
<?
if (strlen($defeito_reclamado) > 0) {
	$sql = "SELECT tbl_defeito_reclamado.descricao
			FROM   tbl_defeito_reclamado
			WHERE  tbl_defeito_reclamado.descricao = '$defeito_reclamado'";
			//WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$descricao_defeito = trim(pg_result($res,0,descricao));
		echo $descricao_defeito ." - ".$defeito_reclamado_descricao;
	}
}
?>
	</TD>
</TR>
</TABLE>
<? } ?>

<p>

<? //if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD colspan="7"><img src="imagens/top_diagnostico.gif"></TD>
</TR>
<TR>
<!-- 	<TD class="titulo">EQUIPAMENTO</TD> -->
	<?
	if($os_item_subconjunto == 't') {
		echo"<TD class=\"titulo\">SUBCONJUNTO</TD>";
		echo"<TD class=\"titulo\">POSIÇÃO</TD>";
	}
	?>
	<TD class="titulo">COMPONENTE</TD>
	<TD class="titulo">QTDE</TD>
	<TD class="titulo">DIGIT.</TD>
	<TD class="titulo">DEFEITO</TD>
	<TD class="titulo">SERVIÇO</TD>
	<TD class="titulo">PEDIDO</TD>
	<TD class="titulo">NOTA FISCAL</TD>
</TR>
<?
	$sql = "SELECT  tbl_produto.referencia                                        ,
					tbl_produto.descricao                                         ,
					tbl_os_produto.serie                                          ,
					tbl_os_produto.versao                                         ,
					tbl_os_item.serigrafia                                        ,
					tbl_os_item.pedido    AS pedido_item                          ,
					tbl_os_item.peca                                              ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
					tbl_defeito.descricao AS defeito                              ,
					tbl_peca.referencia   AS referencia_peca                      ,
					tbl_os_item_nf.nota_fiscal                                    ,
					tbl_peca.descricao    AS descricao_peca                       ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao,
					tbl_status_pedido.descricao     AS status_pedido              ,
					tbl_produto.referencia          AS subproduto_referencia      ,
					tbl_produto.descricao           AS subproduto_descricao       ,
					tbl_lista_basica.posicao        
			FROM	tbl_os_produto
			JOIN	tbl_os_item USING (os_produto)
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_peca    USING (peca)
			JOIN	tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_os_produto.produto
									       AND tbl_lista_basica.peca    = tbl_peca.peca
			LEFT JOIN    tbl_defeito USING (defeito)
			LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN    tbl_os_item_nf    ON  tbl_os_item.os_item      = tbl_os_item_nf.os_item
			LEFT JOIN    tbl_pedido        ON  tbl_os_item.pedido       = tbl_pedido.pedido
			LEFT JOIN    tbl_status_pedido ON  tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE   tbl_os_produto.os = $os
			ORDER BY tbl_peca.descricao";

	$sql = "SELECT  tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_os_produto.serie                                           ,
					tbl_os_produto.versao                                          ,
					tbl_os_item.serigrafia                                         ,
					tbl_os_item.pedido              AS pedido_item                 ,
					tbl_os_item.peca                                               ,
					tbl_os_item.posicao                                            ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
					tbl_pedido.pedido_blackedecker  AS pedido_blackedecker         ,
					tbl_pedido.distribuidor                                        ,
					tbl_defeito.descricao           AS defeito                     ,
					tbl_peca.referencia             AS referencia_peca             ,
					tbl_os_item_nf.nota_fiscal                                     ,
					tbl_peca.descricao              AS descricao_peca              ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao ,
					tbl_status_pedido.descricao     AS status_pedido               ,
					tbl_produto.referencia          AS subproduto_referencia       ,
					tbl_produto.descricao           AS subproduto_descricao        ,
					tbl_os_item.qtde                                               
			FROM	tbl_os_produto
			JOIN	tbl_os_item USING (os_produto)
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_peca    USING (peca)
			LEFT JOIN tbl_defeito USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_os_item_nf    ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
			LEFT JOIN tbl_pedido        ON tbl_os_item.pedido       = tbl_pedido.pedido
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE   tbl_os_produto.os = $os
			ORDER BY tbl_peca.descricao";

	$res = pg_exec($con,$sql);
	$total = pg_numrows($res);

	for ($i = 0 ; $i < $total ; $i++) {
		$pedido        = trim(pg_result($res,$i,pedido_item));
		$pedido_blackedecker = trim(pg_result($res,$i,pedido_blackedecker));
		
		$peca          = trim(pg_result($res,$i,peca));
		$nota_fiscal   = trim(pg_result($res,$i,nota_fiscal));
		$status_pedido = trim(pg_result($res,$i,status_pedido));
		$distribuidor  = trim(pg_result($res,$i,distribuidor));
		$digitacao     = trim(pg_result($res,$i,digitacao_item));

		if ($login_fabrica == 3 AND 1==2 ) {
			$nf = $status_pedido;
		}else{
			if (strlen ($nota_fiscal) == 0) {
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca;";
					$resx = pg_exec ($con,$sql);
					
					if (pg_numrows ($resx) > 0) {
						$nf = trim(pg_result($resx,0,nota_fiscal));
						$link = 1;
					}else{
						$condicao_01 = " 1=1 ";
						if (strlen ($distribuidor) > 0) {
							$condicao_01 = " tbl_faturamento.distribuidor = $distribuidor ";
						}
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca = $peca
								AND     $condicao_01 ";
						$resx = pg_exec ($con,$sql);
						
						if (pg_numrows ($resx) > 0) {
							$nf = trim(pg_result($resx,0,nota_fiscal));
							$link = 1;
						}else{
							$nf = "Pendente";
							$link = 1;
						}
					}
				}else{
					$nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}
		}



?>
<TR>
<!-- 	<TD class="conteudo" style="text-align:left;"><? echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao); ?></TD> -->
	<?
	if($os_item_subconjunto == 't') {
		echo"<TD class=\"conteudo\" style=\"text-align:left;\">".pg_result($res,$i,subproduto_referencia) . " - " . pg_result($res,$i,subproduto_descricao)."</TD>";
		echo "<TD class=\"conteudo\" style=\"text-align:center;\">".pg_result($res,$i,posicao)."</TD>";
	}
	?>
	<TD class="conteudo" style="text-align:left;"><? echo pg_result($res,$i,referencia_peca) . " - " . pg_result($res,$i,descricao_peca) ?></TD>
	<TD class="conteudo" style="text-align:center;"><? echo pg_result($res,$i,qtde) ?></TD>
	<TD class="conteudo" style="text-align:center;"><? echo pg_result($res,$i,digitacao_item) ?></TD>
	<TD class="conteudo" style="text-align:left;"><? echo pg_result($res,$i,defeito) ?></TD>
	<TD class="conteudo" style="text-align:left;"><? echo pg_result($res,$i,servico_realizado_descricao) ?></TD>
	<TD class="conteudo" style="text-align:CENTER;"><a href='pedido_finalizado.php?pedido=<? echo $pedido ?>' target='_blank'><? if ($login_fabrica == 1) echo $pedido_blackedecker; else echo $pedido; ?></a>&nbsp;</TD>
	<TD class="conteudo" style="text-align:CENTER;">
	<?
		if (strtolower($nf) <> 'pendente'){
			if ($link == 1) 
				echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
			else
				echo "$nf";
			//echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
		}else{
			echo "$nf &nbsp;";
		}
	?>
	</TD>
</tr>
<?
	}
?>
</TABLE>
<? //} ?>

<BR>

<? if (strlen($obs) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD class="conteudo"><b>OBS:</b>&nbsp;<? echo $obs; ?></TD>
</TR>
</TABLE>
<? } ?>

<BR><BR>
<!-- =========== FINALIZA TELA NOVA============== -->

<div id='container'>
	<div id="contentleft2" style="width: 150px;">
		&nbsp;
	</div>

	<div id="contentleft2" style="width: 150px;">
		<a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a>
	</div>
	<div id="contentleft2" style="width: 150px;">
		<a href="os_print.php?os=<? echo $os ?>" target="_blank"><img src="imagens/btn_imprimir.gif"></a>
	</div>
</div>

<div id='container'>
	&nbsp;
</div>

<? include "rodape.php"; ?>