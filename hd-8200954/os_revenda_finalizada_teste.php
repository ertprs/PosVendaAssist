<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica == 1){
	include("os_revenda_finalizada_blackedecker.php");
	exit;
}

include 'funcoes.php';

$msg_erro = "";
if ($login_fabrica == 6) {
	if (strlen($_GET['os_revenda']) == 0 ) {
		$msg_erro = "Sem número de OS....";
	}
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if ($btn_acao == "explodir" and strlen ($msg_erro) == 0) {
	// executa funcao de explosao
	$sql = "SELECT fn_explode_os_revenda($os_revenda,$login_fabrica)";
	//if ($ip=='201.76.85.4') {echo $sql; exit;}
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strpos($msg_erro,"ERROR:")) {
		$x = explode('ERROR:',$msg_erro);
		$msg_erro = $x[1];
	}
	

	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}


	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT  sua_os
				FROM	tbl_os_revenda
				WHERE	os_revenda = $os_revenda
				AND		fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);
		$sua_os = pg_result($res,0,0);

		// redireciona para os_revenda_explodida.php
		header("Location: os_revenda_explodida_teste.php?sua_os=$sua_os");
		exit;
	}
}

if(strlen($os_revenda) > 0 and strlen ($msg_erro) == 0 ){
	// seleciona do banco de dados
	$sql = "SELECT   tbl_os_revenda.sua_os                                                ,
					 tbl_os_revenda.obs                                                   ,
					 to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					 to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					 tbl_os_revenda.tipo_atendimento                                      ,
					 tbl_revenda.nome  AS revenda_nome                                    ,
					 tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					 tbl_revenda.fone  AS revenda_fone                                    ,
					 tbl_revenda.email AS revenda_email                                   ,
					 tbl_os_revenda.nota_fiscal                                           ,
					 tbl_posto_fabrica.codigo_posto                                       ,
					 tbl_os_revenda.consumidor_nome                                       ,
					 tbl_os_revenda.consumidor_cnpj                                       ,
					 tbl_os_revenda.tipo_os
			FROM	 tbl_os_revenda
			JOIN	 tbl_revenda 
			ON		 tbl_os_revenda.revenda = tbl_revenda.revenda
			JOIN	 tbl_fabrica USING (fabrica)
			LEFT JOIN tbl_posto USING (posto)
			LEFT JOIN tbl_posto_fabrica
			ON		 tbl_posto_fabrica.posto = tbl_posto.posto
			AND		 tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	 tbl_os_revenda.os_revenda = $os_revenda
			AND		 tbl_os_revenda.posto      = $login_posto
			AND		 tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os           = pg_result($res,0,sua_os);
		$data_abertura    = pg_result($res,0,data_abertura);
		$data_digitacao   = pg_result($res,0,data_digitacao);
		$tipo_atendimento = pg_result($res,0,tipo_atendimento);
		$revenda_nome     = pg_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_result($res,0,revenda_fone);
		$revenda_email    = pg_result($res,0,revenda_email);
		$obs              = pg_result($res,0,obs);
		$codigo_posto     = pg_result($res,0,codigo_posto);
		$nota_fiscal      = pg_result($res,0,nota_fiscal);
		$consumidor_nome  = pg_result($res,0,consumidor_nome);
		$consumidor_cnpj  = pg_result($res,0,consumidor_cnpj);
		$tipo_os          = pg_result($res,0,tipo_os);
	}else{
		header('Location: os_revenda_teste.php');
		exit;
	}
}

$title			= "Cadastro de Ordem de Serviço - Revenda"; 
$layout_menu	= 'os';

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
		<?	if (strpos($msg_erro,'Favor informar o telefone do consumidor ou da revenda') > 0) {
				$sqlr = "SELECT revenda from  tbl_os_revenda where os_revenda = $os_revenda";
				$resr = pg_exec($con, $sqlr);
				$revendax = pg_result($resr,0,0);
				$msg_erro = "Revenda sem telefone. Atualize os dados da Revenda antes de efetuar a explosão da OS.<BR>
							Para cadastrar o telefone clique <a href = 'revenda_cadastro.php?revenda=$revendax' target='blank_'>aqui</a>.";
			}
			echo $msg_erro ?>
		</font></b>
	</td>
</tr>
</table>
<?
}
?>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>?os_revenda=<?=$os_revenda?>">
<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<? if ($login_fabrica == 19) { 
				$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 and tipo_atendimento=$tipo_atendimento ORDER BY tipo_atendimento";
				$res = pg_exec ($con,$sql) ;
				if(strlen($tipo_os)>0){
				$sqll = "SELECT descricao from tbl_tipo_os where tipo_os = $tipo_os";
				$ress = pg_exec ($con,$sqll) ;
				}
				?>
				<tr class="menu_top">
					<td nowrap colspan='2'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo Atendimento</font>
					</td>
					<td nowrap colspan='2'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Motivo</font>
					</td>
				</tr>
				<tr>
					<td nowrap align='center' colspan='2'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
						<? echo pg_result ($res,0,tipo_atendimento) . " - " . pg_result ($res,0,descricao) ;?>
						</font>
					</td>
					<td nowrap align='center' colspan='2'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
						<? if(strlen($tipo_os)>0){echo pg_result ($ress,0,descricao);}?>
						</font>
					</td>
				</tr>
				<?}?>
				<tr class="menu_top">
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Digitação</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
					</td>
				</tr>
				<tr>
					<td nowrap align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
						<? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?>
						</font>
					</td>
					<td nowrap align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $data_abertura ?></font>
					</td>
					<td nowrap align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $data_digitacao ?></font>
					</td>
					<td nowrap align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $nota_fiscal ?></font>
					</td>
				</tr>
				<tr>
					<td colspan='4' class="table_line2" height='20'></td>
				</tr>
			</table>
<? if($login_fabrica== 19) {$aux_revenda = "do atacado";} else {$aux_revenda = "da revenda";}?>	
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome <?=$aux_revenda;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ <?=$aux_revenda;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone <?=$aux_revenda;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">e-Mail <?=$aux_revenda;?></font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_nome ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_cnpj ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_fone ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $revenda_email ?></font>
					</td>
				</tr>
			</table>
<?if($login_fabrica ==19){?>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome da Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ da Revenda</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $consumidor_nome ?></font>
					</td>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $consumidor_cnpj ?></font>
					</td>
				</tr>
			</table>
<?}?>
			
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $obs ?></font>
					</td>
				</tr>
			</table>

		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<table width="550" border="0" cellpadding="2" cellspacing="3" align="center" bgcolor="#ffffff">
	<TR>
		<TD colspan="4"><br></TD>
	</TR>
	<tr class="menu_top">
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição do produto</font></td>
		<?if($login_fabrica<>19){?>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número de série</font></td>
		<?}?>
		<? if ($login_fabrica == 1) { ?>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Type</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Embalagem Original</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Sinal de Uso</font></td>
		<? } ?>
		<? if ($login_fabrica == 7) { ?>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Capacidade</font></td>
		<? }else{ 
			if($login_fabrica<>19){?>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número da NF</font> <br> <img src="imagens/selecione_todas.gif" border=0 onclick="javascript:TodosNF()" ALT="Selecionar todas" style="cursor:pointer;"></td>
		<? }} ?>
		<? if ($login_fabrica==19){?>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde</font></td>
		<? } ?>
	</tr>
<?
	// monta o FOR
	$qtde_item = 20;

		if ($os_revenda){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os_revenda_item.os_revenda_item    ,
							 tbl_os_revenda_item.produto            ,
							 tbl_os_revenda_item.serie              ,
							 tbl_os_revenda_item.nota_fiscal        ,
							 tbl_os_revenda_item.capacidade         ,
							 tbl_os_revenda_item.type               ,
							 tbl_os_revenda_item.embalagem_original ,
							 tbl_os_revenda_item.sinal_de_uso       ,
							 tbl_os_revenda_item.qtde               ,
							 tbl_produto.referencia                 ,
							 tbl_produto.descricao                  
					FROM	 tbl_os_revenda
					JOIN	 tbl_os_revenda_item
					ON		 tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	 tbl_produto
					ON		 tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	 tbl_os_revenda.os_revenda = $os_revenda
					AND		 tbl_os_revenda.posto      = $login_posto
					AND		 tbl_os_revenda.fabrica    = $login_fabrica ";
			$res = pg_exec($con, $sql);

			for ($i=0; $i<pg_numrows($res); $i++)
			{
				$referencia_produto = pg_result($res,$i,referencia);
				$produto_descricao  = pg_result($res,$i,descricao);
				$produto_serie      = pg_result($res,$i,serie);
				$nota_fiscal        = pg_result($res,$i,nota_fiscal);
				$capacidade         = pg_result($res,$i,capacidade);
				$type               = pg_result($res,$i,type);
				$embalagem_original = pg_result($res,$i,embalagem_original);
				$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
				$qtde               = pg_result($res,$i,qtde);
?>
	<tr>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $referencia_produto ?></font>
		</td>
		<td align="left" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_descricao ?></font>
		</td>
		<?if($login_fabrica<>19){?>
		<td align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
		</td>
		<?}?>
		<? if ($login_fabrica == 1) { ?>
			<td align='center' nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $type ?></font>
			</td>
			<td align='center' nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if ($embalagem_original == 't') echo "Sim"; else echo "Não"; ?></font>
			</td>
			<td align='center' nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if ($sinal_de_uso == 't') echo "Sim"; else echo "Não"; ?></font>
			</td>
		<? } 
		if($login_fabrica<>19){?>
		<td>
		<? if ($login_fabrica == 7) { ?>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $capacidade ?></font>
		<? }else{ 
				
				?>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $nota_fiscal ?></font>
		<? 
		} 
		?>
		</td>
		<? }if ($login_fabrica==19){?>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=$qtde?></font></td>
		<? } ?>
		
	</tr>
<?
			}
		}
?>
</table>

<br>

<input type='hidden' name='btn_acao' value=''>
<center>
<img src='imagens/btn_alterarcinza.gif'  onclick="javascript: document.location='os_revenda.php?os_revenda=<? echo $os_revenda; ?>'" ALT="Alterar" border='0' style="cursor:pointer;">
<img src='imagens/btn_explodir<?if($login_fabrica==19){echo "_2";}?>.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='explodir' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Explodir" border='0' style="cursor:pointer;">
<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('os_revenda_print.php?os_revenda=<? echo $os_revenda; ?>','osrevenda');" ALT="Imprimir" border='0' style="cursor:pointer;">
</center>

<br>

<center><a href="os_revenda_consulta.php?<?echo $_COOKIE['cookget']; ?>"><img src="imagens/btn_voltarparaconsulta.gif"></a></center>

</form>
<br>

<? include 'rodape.php'; ?>