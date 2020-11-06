<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = "financeiro";
$title = "Valores adicionais de Ordem de Serviço de Troca";


$btn_acao = $_POST["btn_acao"];
$os       = $_POST["os"];

if (strlen($btn_acao)>0){

	$os = $_POST["os"];
	$custo_produto_troca_faturada = $_POST["custo_produto_troca_faturada"];
	$mao_de_obra = $_POST["mao_de_obra"];
	$mao_de_obra = str_replace (",",".",$mao_de_obra);
	$custo_produto_troca_faturada = str_replace (",",".",$custo_produto_troca_faturada);
	$xsql = "UPDATE 	tbl_os_extra 
						set
						custo_produto_troca_faturada=$custo_produto_troca_faturada 
					where os=$os";
//	$xres = pg_exec($con,$xsql);//adiciona a mao de obra na tabela_os_extra
//	$msg_erro="Valor adicional inserido!";

	$total= $custo_produto_troca_faturada;
	
	$sql = "SELECT posto, sua_os FROM tbl_os WHERE os = $os ;";
	$res = pg_exec($con,$sql);

	$posto_origem    = pg_result($res,0,posto);
	$sua_os_destino  = pg_result($res,0,sua_os);

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$obs = "Troca faturada os: $sua_os_destino";

	$total_neg = $total *(-1);
	if(strlen($msg_erro) == 0){
		$sql = "INSERT INTO tbl_os_sedex (
									fabrica          ,
									posto_origem     ,
									posto_destino    ,
									sua_os_destino   ,
									data             ,
									finalizada       ,
									despesas         ,
									admin            ,
									obs
							) VALUES (
									$login_fabrica    ,
									'$posto_origem'   ,
									'6900'            ,
									'$sua_os_destino' ,
									current_date      ,
									current_timestamp ,
									'$total_neg'      ,
									$login_admin      ,
									'$obs'
							);";
		$res      = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

//echo "$sql<br>";
//VERIFICAR STATUS_OS ESTÁ COMO INTERVENÇAO DA FÁBRICA.
	$sql = "SELECT os_sedex 
				FROM tbl_os_sedex 
			WHERE posto_destino = '6900' 
			AND posto_origem = $posto_origem 
			ORDER BY os_sedex DESC limit 1 ";
	$res = pg_exec($con,$sql);
	$os_sedex = pg_result($res,0,0);
	$obs = "Valor referente a troca faturada de produto na OS $sua_os_destino";
	if(strlen($msg_erro) == 0){
		$sql = "INSERT INTO tbl_os_status (
								os           ,
								status_os    ,
								data         ,
								observacao   ,
								os_sedex
						) VALUES (
								$os          ,
								'64'         ,
								current_date ,
								'$obs'       ,
								$os_sedex
						);";
		//64 = 64 | Liberado da intervenção Fabric |
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if(strlen($msg_erro) == 0){
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?os=$os");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$os = $_GET["os"];
if(strlen($os) == 0){$os = $_POST["os"];}

if(strlen($os) > 0){
	$sql = "SELECT 	tbl_os.os,
				tbl_os.sua_os                                                     ,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao    ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
				to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
				tbl_os.tipo_atendimento                                           ,
				tbl_tipo_atendimento.descricao             AS nome_atendimento    ,
				tbl_os.consumidor_nome                                            ,
				tbl_os.consumidor_fone                                            ,
				tbl_os.consumidor_endereco                                        ,
				tbl_os.consumidor_numero                                          ,
				tbl_os.consumidor_complemento                                     ,
				tbl_os.consumidor_bairro                                          ,
				tbl_os.consumidor_cep                                             ,
				tbl_os.consumidor_cidade                                          ,
				tbl_os.consumidor_estado                                          ,
				tbl_os.nota_fiscal                                                ,
				to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
				tbl_os.consumidor_revenda                                         ,
				tbl_os.obs                                                        ,
				tbl_os.excluida                                                   ,
				tbl_produto.referencia                                            ,
				tbl_produto.descricao                                             ,
				tbl_produto.voltagem                                              ,
				tbl_os.qtde_produtos                                              ,
				tbl_os.serie                                                      ,
				tbl_os.posto                                                      ,
				tbl_os.codigo_fabricacao                                          ,
				tbl_os.troca_garantia                                             ,
				tbl_os.troca_via_distribuidor                                     ,
				tbl_os.troca_garantia_admin                                       ,
				to_char(tbl_os.troca_garantia_data,'DD/MM/YYYY') AS troca_garantia_data ,
				tbl_posto_fabrica.codigo_posto               AS posto_codigo      ,
				tbl_posto.nome                               AS posto_nome        ,
				tbl_posto.posto                               AS codigo_posto     ,
				tbl_os_extra.os_reincidente                                       ,
				tbl_os.ressarcimento                                              ,
				tbl_os_extra.mao_de_obra                                          ,
				tbl_os_extra.custo_produto_troca_faturada                        
			FROM tbl_os
			JOIN tbl_os_extra using(os)
			JOIN tbl_produto using(produto)
			JOIN tbl_tipo_atendimento using (tipo_atendimento)
			JOIN    tbl_posto                   ON tbl_posto.posto         = tbl_os.posto
			JOIN    tbl_posto_fabrica           ON  tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os.os=$os 
		AND tbl_os.fabrica=$login_fabrica ";
//echo "$sql";
	$res = pg_exec($con,$sql);
	$posto                       = pg_result ($res,0,posto);
	$sua_os                      = pg_result ($res,0,sua_os);
	$data_digitacao              = pg_result ($res,0,data_digitacao);
	$data_abertura               = pg_result ($res,0,data_abertura);
	$data_fechamento             = pg_result ($res,0,data_fechamento);
	$data_finalizada             = pg_result ($res,0,finalizada);
	$consumidor_nome             = pg_result ($res,0,consumidor_nome);
	$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
	$consumidor_numero           = pg_result ($res,0,consumidor_numero);
	$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
	$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
	$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
	$consumidor_estado           = pg_result ($res,0,consumidor_estado);
	$consumidor_cep              = pg_result ($res,0,consumidor_cep);
	$consumidor_fone             = pg_result ($res,0,consumidor_fone);
	$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
	$data_nf                     = pg_result ($res,0,data_nf);
	$produto_referencia          = pg_result ($res,0,referencia);
	$produto_descricao           = pg_result ($res,0,descricao);
	$produto_voltagem            = pg_result ($res,0,voltagem);
	$serie                       = pg_result ($res,0,serie);
	$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
	$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
	$posto_codigo                = pg_result ($res,0,posto_codigo);
	$posto_nome                  = pg_result ($res,0,posto_nome);
	$obs                         = pg_result ($res,0,obs);
	$qtde_produtos               = pg_result ($res,0,qtde_produtos);
	$excluida                    = pg_result ($res,0,excluida);
	$troca_garantia              = trim(pg_result($res,0,troca_garantia));
	$troca_garantia_data         = trim(pg_result($res,0,troca_garantia_data));
	$troca_garantia_admin        = trim(pg_result($res,0,troca_garantia_admin));
	
	$tipo_atendimento            = trim(pg_result($res,0,tipo_atendimento));
	$nome_atendimento            = trim(pg_result($res,0,nome_atendimento));
	
	$ressarcimento               = trim(pg_result($res,0,ressarcimento));
	$codigo_posto                = trim(pg_result($res,0,posto));
	
	$custo_produto_troca_faturada    = trim(pg_result($res,0,custo_produto_troca_faturada ));
	$mao_de_obra                 = trim(pg_result($res,0,mao_de_obra));

	if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
	else 
	if ($consumidor_revenda == 'C')
	$consumidor_revenda = 'CONSUMIDOR';
}

$sql = "SELECT os_status FROM tbl_os_status WHERE os = $os AND status_os = 64;";
$res = pg_exec($con,$sql);
if(pg_numrows($res) > 0){
	$valor_adicional = pg_result($res,0,os_status);
}

?>
<head>
<style type="text/css">

body {
	margin: 0px;
}

body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.titulo3 {
	font-family: Arial;
	font-size: 7pt;
	text-align: left;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
}
</style>

</head>

<body>

<? 

$sql = "SELECT tbl_os_sedex.os_sedex, tbl_os_sedex.despesas FROM tbl_os_status JOIN tbl_os_sedex ON tbl_os_sedex.os_sedex = tbl_os_status.os_sedex WHERE os = $os AND status_os = 64 ";
$res = pg_exec($con,$sql);
if(pg_numrows($res) > 0){
	$sedex_despesas = pg_result($res,0,despesas);
	$sedex_sedex    = pg_result($res,0,os_sedex);
	$msg_erro = "Valor do produto gravado.<br>Será debitado no próximo extrato do posto.<br>Valor: R$ $sedex_despesas ";
}

if(strlen($msg_erro)>0){ ?>

<TABLE width="500" border="0" cellspacing="1" cellpadding="0" bgcolor='#485989' align='center'>
<TR>
	<TD  bgcolor="#FF6F6A"><? echo $msg_erro ?></TD>
</TR>
</TABLE>

<? } ?>

<? if ($excluida == "t") { ?>
<TABLE width="500" border="0" cellspacing="1" cellpadding="0" bgcolor='#485989' align='center'>
<TR>
	<TD  bgcolor="#FFE1E1" height='20'>ORDEM DE SERVIÇO EXCLUÍDA</TD>
</TR>
</TABLE>
<?} ?>

<?
if ((strlen($tipo_atendimento)>0) and $login_fabrica==1) { 
?>
<center>
<TABLE width="500" border="0" cellspacing="1" cellpadding="0" bgcolor='#485989' align='center'>
<TR>
	<TD class="inicio" height='20' width='100'>&nbsp;&nbsp;Troca de Produto:</TD>
	<TD class="conteudo" height='20'><? echo " &nbsp;&nbsp;$nome_atendimento"; ?></TD>
</TR>
</TABLE>
</center>
<?
}  
?>
<TABLE width="500" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
		<TR>
			<TD class="inicio" height='20' width='100'>&nbsp;&nbsp;POSTO: </TD>
			<TD class="conteudo"><? echo "&nbsp; $posto_codigo - $posto_nome"; ?></TD>
		</TR>
</TABLE>

<table width='500' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr >
		<td rowspan='4' class='conteudo' width='200' ><center>OS FABRICANTE<br>&nbsp;<b>
			<?
			if ($login_fabrica == 1) echo "<FONT SIZE='6' COLOR='#C67700'>".$posto_codigo;
			if (strlen($consumidor_revenda) > 0) echo $sua_os ."</FONT> - ". $consumidor_revenda;
			else echo $sua_os;
			if(strlen($sua_os_offline)>0){ 
			echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
			echo "<tr >";
			echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>OS Off Line - $sua_os_offline</center></td>";
			echo "</tr>";
			echo "</table>";
			}
			?>
			</b></center>
		</td>
		<td class='inicio' height='15' colspan='4'>&nbsp;DATAS DA OS</td>
	</TR>
	<TR>
		<td class='titulo' width='100' height='15'>ABERTURA&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
		<td class='titulo' width='100' height='15'>DIGITAÇÃO&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
	</tr>
	<tr>
		<td class='titulo' width='100' height='15'>FECHAMENTO&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
		<td class='titulo' width='100' height='15'>FINALIZADA&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_finalizada ?></td>

	</tr>
	<tr>
		<TD class="titulo"  height='15'>DATA DA NF&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
		<td class='titulo' width='100' height='15'>FECHADO EM &nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;
		<? 
		if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
			$total_de_dias_do_conserto=$data_fechamento-$data_abertura;
			if($total_de_dias_do_conserto==0) echo 'no mesmo dia' ;
			else echo $total_de_dias_do_conserto;
			if($total_de_dias_do_conserto==1) echo ' dia' ;
			if($total_de_dias_do_conserto>1)  echo ' dias' ;
}else{
			echo "NÃO FINALIZADO";
}
		?>
		</td>
	</tr>
</table>
<table width='500' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr>
		<td class='inicio' height='15' colspan='4'>&nbsp;INFORMAÇÕES DO PRODUTO&nbsp;</td>
	</tr>
	<tr >
		<TD class="titulo" height='15' width='90'>REFERÊNCIA&nbsp;</TD>
		<TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?></TD>
		<TD class="titulo" height='15' width='90'>DESCRIÇÃO&nbsp;</TD>
		<TD class="conteudo" height='15' >&nbsp;<? echo $produto_descricao ?></TD>
		<TD class="titulo" height='15' width='90'>NÚMERO DE SÉRIE&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $serie ?></TD>
	</tr>
	<? if ($login_fabrica == 1) { ?>
	<tr>
		<TD class="titulo" height='15' width='90'>VOLTAGEM&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $produto_voltagem ?></TD>
		<TD class="titulo" height='15' width='110'>CÓDIGO FABRICAÇÃO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $codigo_fabricacao ?></TD>
		<TD colspan='2' height='15' bgcolor='#FFFFFF' width='90'>&nbsp;</TD>
	</tr>
	<? } ?>
</table>
<TABLE width="500px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DO CONSUMIDOR&nbsp;</td>
	</tr>
	<TR>
		<TD class="titulo" width='90' height='15'>NOME&nbsp;</TD>
		<TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_nome ?></TD>
		<TD class="titulo" width='80'>FONE&nbsp;</TD>
		<TD class="conteudo"height='15'>&nbsp;<? echo $consumidor_fone ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>CPF&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cpf ?></TD>
		<TD class="titulo" height='15'>CEP&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cep ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>ENDEREÇO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_endereco ?></TD>
		<TD class="titulo" height='15'>NÚMERO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_numero ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>COMPLEMENTO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_complemento ?></TD>
		<TD class="titulo" height='15'>BAIRRO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_bairro ?></TD>
	</TR>
	<TR>
		<TD class="titulo">CIDADE&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_cidade ?></TD>
		<TD class="titulo">ESTADO&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_estado ?></TD>
	</TR>
</TABLE>
<?

$sql = "SELECT os_status FROM tbl_os_status WHERE os = $os AND status_os = 64; ";
$res = pg_exec($con,$sql);

if(1==2){
//if($tipo_atendimento == 18 AND pg_numrows($res) == 0){
?>
	<FORM name='frm_valor' METHOD='POST' ACTION='<? echo $PHP_SELF ?>'>
	<TABLE width="500px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='2' height='15'>&nbsp;VALORES ADICIONAIS PARA O POSTO&nbsp;</td>
	</tr>
	<TR>
		<!--<TD class="titulo" height='25'>MÃO DE OBRA:&nbsp;</TD>
		<TD class="conteudo" height='25'>&nbsp; R$ 
		<input class="frm" type="text" name="mao_de_obra"   size="10" maxlength="10" value="<? echo $mao_de_obra ?>"></TD>-->
		<TD class="titulo" height='25'>VALOR ADICIONAL:&nbsp;</TD>
		<TD class="conteudo" height='25'>&nbsp; R$ <input class="frm" type="text" name="custo_produto_troca_faturada" size="10" maxlength="10" value="<? echo $custo_produto_troca_faturada ?>" <? if($valor_adicional > 0){echo "readonly";}?>></TD>
	</TR>
	<input type='hidden' name='os' value='<? echo $os ?>'>
	<TR><TD COLSPAN='2' BGCOLOR='#FFFFFF' align='center' height='30'>
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_adicionar_azul.gif' onclick="javascript: if (document.frm_valor.btn_acao.value == '' ) { document.frm_valor.btn_acao.value='continuar' ; document.frm_valor.submit() } else { alert ('Não clique no botão voltar do navegador, utilize somente os botões da tela') }" ALT="Adicionar valor a Ordem de Serviço" border='0' style='cursor: hand;'>
		</TD>
	</TR>
	</TABLE>
	</FORM>
<? /*}else{
	<p align='center' style='font-family: verdana; font-size: 10px'>*Valor adicional apenas quando for Troca Faturada</p>
*/} ?>