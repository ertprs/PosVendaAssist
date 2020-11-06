<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if (strlen(trim($_GET["sua_os"])) > 0)       $sua_os = trim($_GET["sua_os"]);
if (strlen(trim($_GET["posto_codigo"])) > 0) $posto_codigo = trim($_GET["posto_codigo"]);

if (strlen($sua_os) > 0) {
	$sql =	"SELECT tbl_os.os                                                          ,
					tbl_os.sua_os                                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura      ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento    ,
					tbl_os.serie                                                       ,
					tbl_os.consumidor_nome                                             ,
					tbl_os.consumidor_cpf                                              ,
					tbl_os.consumidor_fone                                             ,
					tbl_os.revenda_nome                                                ,
					tbl_os.revenda_cnpj                                                ,
					tbl_os.nota_fiscal                                                 ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf            ,
					tbl_os.consumidor_revenda                                          ,
					tbl_os.aparencia_produto                                           ,
					tbl_os.acessorios                                                  ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo       ,
					tbl_posto.nome                               AS posto_nome         ,
					tbl_produto.referencia                       AS produto_referencia ,
					tbl_produto.descricao                        AS produto_descricao  
			FROM  tbl_os
			JOIN  tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
			JOIN  tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
			JOIN  tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica";

	if (strlen($sua_os) > 0)       $sql .= " AND tbl_os.os = $sua_os";
	if (strlen($posto_codigo) > 0) $sql .= " AND tbl_posto_fabrica.codigo_posto = $posto_codigo";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$os                 = pg_result($res,0,os);
		$sua_os             = pg_result($res,0,sua_os);
		$data_abertura      = pg_result($res,0,data_abertura);
		$data_fechamento    = pg_result($res,0,data_fechamento);
		$serie              = pg_result($res,0,serie);
		$consumidor_nome    = pg_result($res,0,consumidor_nome);
		$consumidor_cpf     = pg_result($res,0,consumidor_cpf);
		$consumidor_fone    = pg_result($res,0,consumidor_fone);
		$revenda_nome       = pg_result($res,0,revenda_nome);
		$revenda_cnpj       = pg_result($res,0,revenda_cnpj);
		$nota_fiscal        = pg_result($res,0,nota_fiscal);
		$data_nf            = pg_result($res,0,data_nf);
		$consumidor_revenda = pg_result($res,0,consumidor_revenda);
		$aparencia_produto  = pg_result($res,0,aparencia_produto);
		$acessorios         = pg_result($res,0,acessorios);
		$posto_codigo       = pg_result($res,0,posto_codigo);
		$posto_nome         = pg_result($res,0,posto_nome);
		$produto_referencia = pg_result($res,0,produto_referencia);
		$produto_descricao  = pg_result($res,0,produto_descricao);
?>
<br>
<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="5">INFORMAÇÕES DA OS</td>
	</tr>
	<tr class="Menu">
		<td>OS FABRICANTE</td>
		<td>CÓDIGO DO POSTO</td>
		<td colspan="3">NOME DO POSTO</td>
	</tr>
	<tr class="Conteudo">
		<td nowrap>&nbsp;
		<?
		echo $os;
		if (strtoupper($consumidor_revenda) == "C")     echo " - CONSUMIDOR";
		elseif (strtoupper($consumidor_revenda) == "R") echo " - REVENDA";
		?>
		</td>
		<td nowrap><b>&nbsp;<?echo $posto_codigo?></b></td>
		<td nowrap colspan="3"><b>&nbsp;<?echo $posto_nome?></b></td>
	</tr>
	<tr class="Menu">
		<td>DATA DE ABERTURA</td>
		<td>REFERÊNCIA DO PRODUTO</td>
		<td colspan="2">DESCRIÇÃO DO PRODUTO</td>
		<td>Nº SÉRIE</td>
	</tr>
	<tr class="Conteudo">
		<td nowrap>&nbsp;<?echo $data_abertura?></td>
		<td nowrap>&nbsp;<?echo $produto_referencia?></td>
		<td nowrap colspan="2">&nbsp;<?echo $produto_descricao?></td>
		<td nowrap>&nbsp;<?echo $serie?></td>
	</tr>
	<tr class="Menu">
		<td colspan="2">NOME DO CONSUMIDOR</td>
		<td>CPF/CNPJ DO CONSUMIDOR</td>
		<td colspan="2">TELEFONE DO CONSUMIDOR</td>
	</tr>
	<tr class="Conteudo">
		<td nowrap colspan="2">&nbsp;<?echo $consumidor_nome?></td>
		<td nowrap>&nbsp;<?echo $consumidor_cpf?></td>
		<td nowrap colspan="2">&nbsp;<?echo $consumidor_fone?></td>
	</tr>
	<tr class="Menu">
		<td colspan="2">NOME DA REVENDA</td>
		<td>CNPJ REVENDA</td>
		<td>NOTA FISCAL</td>
		<td>DATA COMPRA</td>
	</tr>
	<tr class="Conteudo">
		<td nowrap colspan="2">&nbsp;<?echo $consumidor_nome?></td>
		<td nowrap>&nbsp;<?echo $consumidor_cpf?></td>
		<td nowrap>&nbsp;<?echo $nota_fiscal?></td>
		<td nowrap>&nbsp;<?echo $data_nf?></td>
	</tr>
	<tr class="Menu">
		<td colspan="2">APARÊNCIA DO PRODUTO</td>
		<td colspan="2">ACESSÓRIOS</td>
		<td>DATA DE FECHAMENTO</td>
	</tr>
	<tr class="Conteudo">
		<td nowrap colspan="2">&nbsp;<?echo $aparencia_produto?></td>
		<td nowrap colspan="2">&nbsp;<?echo $acessorios?></td>
		<td nowrap>&nbsp;<b><?echo $data_fechamento?></b></td>
	</tr>
</table>
<? }else{ ?>
<br>
<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td>OS NÃO ENCONTRADA</td>
	</tr>
</table>
<?
	}
}

if (strlen(trim($_GET["posto_codigo_destino"])) > 0) $posto_codigo_destino = trim($_GET["posto_codigo_destino"]);
if (strlen(trim($_GET["posto_nome_destino"])) > 0)   $posto_nome_destino = trim($_GET["posto_nome_destino"]);

if (strlen($posto_codigo_destino) > 0) {
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto         ,
					tbl_posto.ie                           ,
					tbl_posto.cnpj                         ,
					tbl_posto.nome                         ,
					tbl_posto.fone                         ,
					tbl_tipo_posto.descricao AS tipo_posto 
			FROM  tbl_posto
			JOIN  tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									 AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_tipo_posto ON  tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
									 AND tbl_tipo_posto.fabrica    = $login_fabrica
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
	if (strlen($posto_codigo_destino) > 0) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo_destino'";
	if (strlen($posto_nome_destino) > 0) $sql .= " AND tbl_posto.nome = '$posto_nome_destino'";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$codigo_posto = pg_result($res,0,codigo_posto);
		$tipo_posto   = pg_result($res,0,tipo_posto);
		$ie           = pg_result($res,0,ie);
		$cnpj         = pg_result($res,0,cnpj);
		$nome         = pg_result($res,0,nome);
		$fone         = pg_result($res,0,fone);
?>
<br>
<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="6">INFORMAÇÕES DO POSTO</td>
	</tr>
	<tr class="Menu">
		<td>CÓDIGO</td>
		<td colspan="4">RAZÃO SOCIAL</td>
		<td>TIPO DO POSTO</td>
	</tr>
	<tr class="Conteudo">
		<td>&nbsp;<?echo $codigo_posto?></td>
		<td colspan="4">&nbsp;<?echo $nome?></td>
		<td>&nbsp;<?echo $tipo_posto?></td>
	</tr>
	<tr class="Menu">
		<td colspan="2">CNPJ/CPF</td>
		<td colspan="2">I.E.</td>
		<td colspan="2">TELEFONE</td>
	</tr>
	<tr class="Conteudo">
		<td colspan="2">&nbsp;<?echo $cnpj?></td>
		<td colspan="2">&nbsp;<?echo $ie?></td>
		<td colspan="2">&nbsp;<?echo $fone?><input type="hidden" name="fone" value="<?echo $fone?>"></td>
	</tr>
</table>
<?
	}
}
?>

