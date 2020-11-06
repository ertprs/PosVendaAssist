<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";

if (strlen(trim($_GET["erro"])) > 0) $erro = trim($_GET["erro"]);

if (strlen(trim($_POST["btn_acao"])) > 0)             $btn_acao             = strtoupper(trim($_POST["btn_acao"]));
if (strlen(trim($_POST["sua_os"])) > 0)               $sua_os               = trim($_POST["sua_os"]);
if (strlen(trim($_POST["posto_codigo_oridem"])) > 0)  $posto_codigo_oridem  = trim($_POST["posto_codigo_oridem"]);
if (strlen(trim($_POST["posto_nome_oridem"])) > 0)    $posto_nome_oridem    = trim($_POST["posto_nome_oridem"]);
if (strlen(trim($_POST["posto_codigo_destino"])) > 0) $posto_codigo_destino = trim($_POST["posto_codigo_destino"]);
if (strlen(trim($_POST["posto_nome_destino"])) > 0)   $posto_nome_destino   = trim($_POST["posto_nome_destino"]);

if ($btn_acao == "ALTERAR") {
	if (strlen(trim($_POST["os"])) > 0)    $os = trim($_POST["os"]);
	if (strlen(trim($_POST["posto"])) > 0) $posto = trim($_POST["posto"]);
	if (strlen($os) == 0) $erro .= " Pesquise a OS Fabricante a ser transferida. ";
	if (strlen($posto) == 0) $erro .= " Pesquise o Posto a ser transferido. ";

	if (strlen($erro) == 0) {
		$res = pg_exec($con, "SELECT posto FROM tbl_posto WHERE posto = $posto;");
		if (pg_numrows($res) == 1) {
			$posto = pg_result($res,0,0);
		}else{
			$erro .= " Posto não encontrado. ";
		}
	}

	if (strlen($erro) == 0) {
		$sql =	"SELECT tbl_os.os,
						tbl_os.data_fechamento,
						tbl_os_extra.extrato
				FROM tbl_os
				JOIN tbl_os_extra USING (os)
				WHERE tbl_os.os = $os;";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) == 1) {
			$os              = pg_result($res,0,os);
			$data_fechamento = pg_result($res,0,data_fechamento);
			$extrato         = pg_result($res,0,extrato);
		}else{
			$erro .= " OS não encontrada. ";
		}
	}

	if (strlen($extrato) > 0) {
		$sql =	"SELECT extrato,
						to_char(data_geracao,'DD/MM/YYYY') AS data_geracao
				FROM tbl_extrato
				WHERE extrato = $extrato;";
		$res = pg_exec($con, $sql);
		if (pg_numrows($res) == 1) {
			$extrato      = pg_result($res,0,extrato);
			$data_geracao = pg_result($res,0,data_geracao);
		}
		$erro .= " OS $os está no extrato $extrato gerado em $data_geracao. ";
	}

	if (strlen($erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql =	"UPDATE tbl_os SET
					posto = $posto
				WHERE os      = $os
				AND   fabrica = $login_fabrica;";
		$res = @pg_exec($con, $sql);

		$erro = pg_errormessage($con);
		$erro = substr($msg_erro,6);

		if (strlen($erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			$erro = "<br> Transferência efetuada com sucesso! <br><br>";
			header ("Location: $PHP_SELF?erro=$erro");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title = "Transferência de Ordem de Serviço"; 

$layout_menu = 'os';

include "cabecalho.php";
?>

<style type="text/css">
.Menu {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596D9B;
	background-color: #D9E2EF;
}
.Conteudo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
	background-color: #FFFFFF;
}
.Tabela {
	border: 1px solid #CED7E7;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<? if (strlen($erro) > 0) { ?>
<br>
<table width="650" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="middle" align="center" class="error"><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_tranferencia" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="btn_acao" value="">

<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="3">INFORMAÇÕES PARA CONSULTA</td>
	</tr>
	<tr class="Menu">
		<td>OS FABRICANTE</td>
		<td>CÓDIGO DO POSTO</td>
		<td>NOME DO POSTO</td>
	</tr>
	<tr>
		<td><input type="text" name="sua_os" size="20" value="<?echo $sua_os?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui a OS do Fabricante.');"></td>
		<td><input type="text" name="posto_codigo_origem" size="15" value="<?echo $posto_codigo_origem?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o código do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_origem, document.frm_tranferencia.posto_nome_origem, 'codigo')" style="cursor: pointer;"></td>
		<td><input type="text" name="posto_nome_origem" size="50" value="<?echo $posto_nome_origem?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o nome do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_origem, document.frm_tranferencia.posto_nome_origem, 'nome')" style="cursor: pointer;"></td>
	</tr>
</table>

<br>

<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="javascript: if (document.frm_tranferencia.btn_acao.value == '' ) { document.frm_tranferencia.btn_acao.value='PESQUISA' ; document.frm_tranferencia.submit() } else { alert ('Aguarde submissão') }" ALT="Consultar OS Fabricante" style="cursor: pointer;">

<br>

<?
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
					tbl_tipo_posto.descricao                     AS posto_tipo         ,
					tbl_produto.referencia                       AS produto_referencia ,
					tbl_produto.descricao                        AS produto_descricao  
			FROM  tbl_os
			JOIN  tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
			JOIN  tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
			JOIN  tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_tipo_posto ON  tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
									 AND tbl_tipo_posto.fabrica    = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica";

	if (strlen($sua_os) > 0)       $sql .= " AND tbl_os.os = $sua_os";
	if (strlen($posto_codigo_origem) > 0) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo_origem'";

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
		$posto_tipo         = pg_result($res,0,posto_tipo);
		$produto_referencia = pg_result($res,0,produto_referencia);
		$produto_descricao  = pg_result($res,0,produto_descricao);
?>
<br>
<input type="hidden" name="os" value="<?echo $os?>">
<table width="750" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="5">INFORMAÇÕES DA OS</td>
	</tr>
	<tr class="Menu">
		<td>OS FABRICANTE</td>
		<td>CÓDIGO DO POSTO</td>
		<td colspan="2">NOME DO POSTO</td>
		<td>TIPO DO POSTO</td>
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
		<td nowrap colspan="2"><b>&nbsp;<?echo $posto_nome?></b></td>
		<td nowrap><b>&nbsp;<?echo $posto_tipo?></b></td>
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
		<td nowrap>&nbsp;<?echo $data_fechamento?></td>
	</tr>
</table>
<? }else{ ?>
<br>
<table width="750" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td>OS NÃO ENCONTRADA</td>
	</tr>
</table>
<?
	}
}else{
?>
<br>
<table width="750" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td>PREENCHA O CAMPO OS FABRICANTE</td>
	</tr>
</table>
<?
}
?>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="3">INFORMAÇÕES PARA CONSULTA</td>
	</tr>
	<tr class="Menu">
		<td>CÓDIGO DO POSTO</td>
		<td>NOME DO POSTO</td>
	</tr>
	<tr>
		<td><input type="text" name="posto_codigo_destino" size="15" value="<?echo $posto_codigo_destino?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o código do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_destino, document.frm_tranferencia.posto_nome_destino, 'codigo')" style="cursor: pointer;"></td>
		<td><input type="text" name="posto_nome_destino" size="50" value="<?echo $posto_nome_destino?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o nome do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_destino, document.frm_tranferencia.posto_nome_destino, 'nome')" style="cursor: pointer;"></td>
	</tr>
</table>

<br>

<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="javascript: if (document.frm_tranferencia.btn_acao.value == '' ) { document.frm_tranferencia.btn_acao.value='PESQUISA' ; document.frm_tranferencia.submit() } else { alert ('Aguarde submissão') }" ALT="Consultar Posto" style="cursor: pointer;">

<br>

<?
if (strlen($posto_codigo_destino) > 0) {
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto         ,
					tbl_posto.posto                        ,
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
		$posto        = pg_result($res,0,posto);
		$codigo_posto = pg_result($res,0,codigo_posto);
		$tipo_posto   = pg_result($res,0,tipo_posto);
		$ie           = pg_result($res,0,ie);
		$cnpj         = pg_result($res,0,cnpj);
		$nome         = pg_result($res,0,nome);
		$fone         = pg_result($res,0,fone);
?>
<br>
<input type="hidden" name="posto" value="<?echo $posto?>">
<table width="750" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="6">INFORMAÇÕES DO POSTO</td>
	</tr>
	<tr class="Menu">
		<td>CÓDIGO</td>
		<td colspan="4">RAZÃO SOCIAL</td>
		<td>TIPO DO POSTO</td>
	</tr>
	<tr class="Conteudo">
		<td><b>&nbsp;<?echo $codigo_posto?></b></td>
		<td colspan="4"><b>&nbsp;<?echo $nome?></b></td>
		<td><b>&nbsp;<?echo $tipo_posto?></b></td>
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

<br>

<? if (strlen($os) > 0 AND strlen($posto) > 0) { ?>
<img border="0" src="imagens/btn_alterarcinza.gif" onclick="javascript: if (document.frm_tranferencia.btn_acao.value == '' ) { document.frm_tranferencia.btn_acao.value='ALTERAR' ; document.frm_tranferencia.submit() } else { alert ('Aguarde submissão') }" ALT="Transferir OS" style="cursor: pointer;">
<? } ?>

</form>

<br>

<? include "rodape.php";?>