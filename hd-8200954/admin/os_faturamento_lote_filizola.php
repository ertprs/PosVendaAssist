<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica != 7 ){
	header("Location: menu_os.php");
	exit;
}

$msg_erro = "";

$btn_acao = $_POST['btn_acao'];

if ($btn_acao == 'gravar'){
	$os_faturamento = $_POST['os_faturamento'];
	$cliente        = $_POST['cliente'];
	$revenda        = $_POST['revenda'];
	$obs            = $_POST['obs'];

	if(strlen($cliente) > 0)
		$xcliente = "'".$_POST['cliente']."'";
	else
		$xcliente = 'null';

	if(strlen($revenda) > 0)
		$xrevenda = "'".$_POST['revenda']."'";
	else
		$xrevenda = 'null';

	if(strlen($obs) > 0)
		$xobs = "'".$_POST['obs']."'";
	else
		$xobs = 'null';

	$res = pg_exec($con,'BEGIN TRANSACTION');

	// grava em tbl_os_faturamento
	if (strlen($os_faturamento) == 0){
		// insert
		$sql = "INSERT INTO tbl_os_faturamento (
					data_abertura,
					obs          ,
					revenda      ,
					cliente
				)VALUES(
					current_timestamp,
					$xobs             ,
					$xrevenda         ,
					$xcliente
				)";
	}else{
		// update
		$sql = "UPDATE tbl_os_faturamento SET
					obs     = $xobs    ,
					revenda = $xrevenda,
					cliente = $xcliente
				WHERE os_faturamento = $os_faturamento";
	}
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	$msg_erro = substr($msg_erro,6);

	if (strlen($os_faturamento) == 0){
		$res = pg_exec ($con,"SELECT CURRVAL ('seq_os_faturamento')");
		$os_faturamento = pg_result ($res,0,0);
	}

	if (strlen($msg_erro) == 0){
		// grava em tbl_os_extra
		for($i=1; $i<$total_os; $i++){
			$aux_os = $_POST['aux_os'.$i];
			$os     = $_POST['os'.$i];

			if (strlen($msg_erro) == 0){
				if (strlen($os) == 0 AND strlen($aux_os) > 0){
					$sql = "DELETE FROM tbl_os_extra WHERE os = $aux_os";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}
			}

			if (strlen($msg_erro) == 0){
				if (strlen($os) < 0){
					$sql = "UPDATE tbl_os_extra SET
								os_faturamento = $os_faturamento
							WHERE os = $aux_os";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}
			}
		}
	}

	if (strlen($msg_erro) == 0){
		// direciona para os_filizola_valores.php
		$res = pg_exec($con,'COMMIT TRANSACTION');
		header("Location: os_filizola_valores.php?os=$os");
		exit;
	}else{
		$res = pg_exec($con,'ROLLBACK TRANSACTION');
	}
}

$title = "Ordem de Serviço - Lote de Agrupamento para Faturamento";
$layout_menu = "callcenter";
include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
}

input {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
}

TEXTAREA {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

</style>

<? if (strlen($msg_erro) > 0){ ?>
<TABLE>
<TR>
	<TD class='error'><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?}?>

<?
// seleciona os dados
$cliente_cpf  = $_POST['cliente_cpf'];
$cliente_cpf  = str_replace(".","",$cliente_cpf);
$cliente_cpf  = str_replace("-","",$cliente_cpf);
$cliente_cpf  = str_replace("/","",$cliente_cpf);
$cliente_cpf  = str_replace(" ","",$cliente_cpf);
$cliente_nome = $_POST['cliente_nome'];
$revenda_cnpj = $_POST['revenda_cnpj'];
$revenda_cnpj  = str_replace(".","",$revenda_cnpj);
$revenda_cnpj  = str_replace("-","",$revenda_cnpj);
$revenda_cnpj  = str_replace("/","",$revenda_cnpj);
$revenda_cnpj  = str_replace(" ","",$revenda_cnpj);
$revenda_nome = $_POST['revenda_nome'];

if (strlen($revenda_cnpj) > 0) $cnpj_cpf = $revenda_cnpj;
if (strlen($cliente_cpf) > 0) $cnpj_cpf = $cliente_cpf;
	
if (strlen($cliente_cpf) OR strlen($cliente_nome)){
	$sql = "SELECT cliente, nome FROM tbl_cliente WHERE ";
	if (strlen($cliente_cpf) > 0){
		$sql .= "cpf = '".trim($cliente_cpf)."'";
	}elseif (strlen($cliente_nome) > 0) {
		$sql .= "nome = '".trim($cliente_nome)."'";
	}
	$res = pg_exec($con,$sql);
	$cliente      = pg_result($res,0,0);
	$cliente_nome = pg_result($res,0,1);
}

if (strlen($revenda_cnpj) OR strlen($revenda_nome)){
	$sql = "SELECT revenda, nome FROM tbl_revenda WHERE ";
	if (strlen($revenda_cnpj) > 0){
		$sql .= "cnpj = '".trim($revenda_cnpj)."'";
	}elseif (strlen($cliente_nome) > 0) {
		$sql .= "nome = '".trim($revenda_nome)."'";
	}
	$res = pg_exec($con,$sql);
	$revenda = pg_result($res,0,0);
	$revenda_nome = pg_result($res,0,1);
}

if (strlen($revenda_nome) > 0) $nome = $revenda_nome;
if (strlen($cliente_nome) > 0) $nome = $cliente_nome;

?>
<form name='frm_os' action='<? echo $PHP_SELF; ?>' method="POST">
<input type='hidden' name='os_faturamento' value='<? echo $os_faturamento; ?>'>
<input type='hidden' name='cliente' value='<? echo $cliente; ?>'>
<input type='hidden' name='revenda' value='<? echo $revenda; ?>'>

<?
if (strlen($os_faturamento) > 0){
	$sql  = "SELECT * FROM (
				(
				SELECT  tbl_os_faturamento.os_faturamento ,
						tbl_os_faturamento.obs            ,
						tbl_cliente.nome       AS nome    ,
						tbl_cliente.cpf        AS cnpj_cpf
				FROM    tbl_os_faturamento
				JOIN    tbl_cliente USING (cliente)
				WHERE   tbl_os_faturamento.os_faturamento = $os_faturamento
				) UNION (
				SELECT  tbl_os_faturamento.os_faturamento ,
						tbl_os_faturamento.obs            ,
						tbl_revenda.nome       AS nome    ,
						tbl_revenda.cnpj       AS cnpj_cpf
				FROM    tbl_os_faturamento
				JOIN    tbl_revenda USING (revenda)
				WHERE   tbl_os_faturamento.os_faturamento = $os_faturamento
				)
			) AS x ORDER BY x.nome;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		$os_faturamento = pg_result($res,0,os_faturamento);
		$obs            = pg_result($res,0,obs);
		$cnpj_cpf       = trim(pg_result($res,$i,cnpj_cpf));
		$nome           = trim(pg_result($res,$i,nome));
	}
}

?>
<table class="border" width='700' align='center' border='0' cellpadding="3" cellspacing="3">
	<tr>
		<td colspan=3 class="menu_top">LOTE DE FATURAMENTO</td>
	</tr>
	<tr>
		<td class="menu_top">LOTE</td>
		<td class="menu_top">CNPJ/CPF</td>
		<td class="menu_top">NOME</td>
	</tr>
	<tr>
		<TD class="table_line2"><center><? echo $os_faturamento; ?></center></TD>
		<TD class="table_line2"><center><? echo $cnpj_cpf; ?></center></TD>
		<TD class="table_line2"><center><? echo $nome; ?></TD>
	</tr>
	<tr>
		<td colspan=3 class="menu_top">OBSERVAÇÕES</td>
	</tr>
	<tr>
		<TD colspan=3 class="table_line2"><center><textarea name='obs' rows='5' cols='100%' class='frm'><? echo $obs ?></textarea></center></TD>
	</tr>
</table>

<BR>

<DIV><a href='os_filizola_faturamento_matricial.php?os_faturamento=<? echo $os_faturamento; ?>'>IMPRIMIR FOLHA DE FATURAMENTO</a></DIV>

<?
if (strlen($cnpj_cpf) > 0)  $sub_cnpj_cpf  = substr($cnpj_cpf ,0,8);

if (strlen($sub_cnpj_cpf) > 0){

	$sql  = "SELECT * FROM (
				(
				SELECT  tbl_os.os                                                       ,
						tbl_os.sua_os                                                   ,
						to_char(tbl_os.data_abertura, 'DD/MM/YYYY')   AS data_abertura  ,
						to_char(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento
				FROM    tbl_os
				JOIN    tbl_os_extra  USING(os)
				JOIN    tbl_cliente USING (cliente)
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_os_extra.os_faturamento IS NULL 
				AND     tbl_os.data_abertura > '2004-11-01 00:00:00'
				AND tbl_cliente.cpf  ILIKE '$sub_cnpj_cpf%' 
				) UNION (
				SELECT  tbl_os.os                                                       ,
						tbl_os.sua_os                                                   ,
						to_char(tbl_os.data_abertura, 'DD/MM/YYYY')   AS data_abertura  ,
						to_char(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento
				FROM    tbl_os
				JOIN    tbl_os_extra  USING(os)
				JOIN    tbl_revenda USING (revenda)
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_os_extra.os_faturamento IS NULL
				AND     tbl_os.data_abertura > '2004-11-01 00:00:00'
				AND tbl_revenda.cnpj  ILIKE '$sub_cnpj_cpf%'
				)
			) AS x ORDER BY x.sua_os;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
?>
	<br>

	<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
		<tr>
			<td colspan='6' class="menu_top">&nbsp;</td>
		</tr>
		<tr>
			<td class="menu_top">OS</td>
			<td class="menu_top">ABERTURA</td>
			<td class="menu_top">FECHAMENTO</td>
			<td class="menu_top">TOTAL</td>
			<td class="menu_top">&nbsp;</td>
		</tr>
	<?
		for ($i=0; $i<pg_numrows($res); $i++) {
			$os              = trim(pg_result($res,$i,os));
			$sua_os          = trim(pg_result($res,$i,sua_os));
			$data_abertura   = trim(pg_result($res,$i,data_abertura));
			$data_fechamento = trim(pg_result($res,$i,data_fechamento));

			$cor = (strlen($data_fechamento) == 0) ? $cor = '#eeeeee' : $cor = '#ffffff';

			echo "<tr>\n";
			echo "	<TD bgcolor='$cor' class='table_line2'><a href='os_filizola_faturamento.php?os=$os' target='_black'>$sua_os</a></TD>\n";
			echo "	<TD bgcolor='$cor' align='center' class='table_line2'>$data_abertura</TD>\n";
			echo "	<TD bgcolor='$cor' align='center' class='table_line2'>$data_fechamento</TD>\n";

// os extra
			$sql = "SELECT  tbl_os_extra.desconto_peca           ,
							tbl_os_extra.desconto_peca_recuperada,
							tbl_os.visita_por_km                 ,
							tbl_os.qtde_km                       ,
							tbl_os.diaria                        ,
							tbl_os.qtde_diaria                   ,
							tbl_os.hora_tecnica                  ,
							tbl_os.qtde_hora                     ,
							tbl_os.taxa_visita                   ,
							tbl_cliente.consumidor_final         ,
							tbl_os.cliente
					FROM tbl_os
					JOIN tbl_os_extra     ON tbl_os_extra.os = tbl_os.os
					LEFT JOIN tbl_cliente USING(cliente)
					WHERE tbl_os.os      = $os
					AND   tbl_os.fabrica = $login_fabrica";
			$resA = @pg_exec ($con,$sql);

			$desconto_peca            = @pg_result($resA,0,desconto_peca);
			$desconto_peca_recuperada = @pg_result($resA,0,desconto_peca_recuperada);
			$visita_por_km            = @pg_result($resA,0,visita_por_km);
			$qtde_km                  = @pg_result($resA,0,qtde_km);
			$diaria                   = @pg_result($resA,0,diaria);
			$qtde_diaria              = @pg_result($resA,0,qtde_diaria);
			$hora_tecnica             = @pg_result($resA,0,hora_tecnica);
			$qtde_hora                = @pg_result($resA,0,qtde_hora);
			$taxa_visita              = @pg_result($resA,0,taxa_visita);
			$consumidor_final         = @pg_result($resA,0,consumidor_final);

			$total_os_extra = ($visita_por_km * $qtde_km) + ($diaria * $qtde_diaria) + ($hora_tecnica * $qtde_hora) + $taxa_visita;

// os produto
			$sql = "SELECT	sum (tbl_os_produto.regulagem_peso_padrao)    AS regulagem_peso_padrao   ,
							sum (tbl_os_produto.certificado_conformidade) AS certificado_conformidade,
							sum (tbl_os_produto.mao_de_obra)              AS mao_de_obra             
					FROM	tbl_os_produto
					JOIN	tbl_produto           USING (produto)
					JOIN	tbl_defeito_reclamado USING (defeito_reclamado)
					WHERE	tbl_os_produto.os = $os";
			$resB = @pg_exec ($con,$sql);

			$total_os_produto = @pg_result($resB,0,regulagem_peso_padrao) + @pg_result($resB,0,certificado_conformidade) + @pg_result($resB,0,mao_de_obra);

// os item recuperada

			$sql = "SELECT	tbl_os_item.qtde                   ,
							tbl_tabela_item.preco AS preco_item
					FROM	tbl_os
					JOIN	tbl_os_produto ON tbl_os_produto.os       = tbl_os.os 
					JOIN	tbl_os_item    ON tbl_os_item.os_produto  = tbl_os_produto.os_produto 
					LEFT JOIN tbl_peca     ON tbl_peca.peca           = tbl_os_item.peca 
					JOIN	tbl_tabela     ON tbl_tabela.tabela       = 29
					LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
											  AND tbl_tabela_item.tabela = tbl_tabela.tabela 
					WHERE	tbl_os.os      = $os
					AND		tbl_os.fabrica = $login_fabrica
					AND tbl_os_item.servico_realizado = 36";
			$resC = @pg_exec ($con,$sql);

			$total_pecas_recuperadas = 0;
			$total_pecas             = 0;

			for($j=0; $j<@pg_numrows($resC); $j++){
				$qtde  = @pg_result($resC,$j,qtde);
				$preco = @pg_result($resC,$j,preco_item);

				$total_pecas = $preco * $qtde;

				if (strlen($desconto_peca_recuperada) > 0)
					$total_pecas_recuperadas += $total_pecas - ($total_pecas * ($desconto_peca_recuperada / 100));
			}

// os item nova

			$sql = "SELECT  distinct
							tbl_os_item.qtde                   ,
							tbl_peca.ipi                       ,
							tbl_tabela_item.preco AS preco_item
					FROM    tbl_os_item
					LEFT JOIN tbl_peca USING (peca)
					LEFT JOIN tbl_tabela ON tbl_tabela.tabela = 26
					LEFT JOIN tbl_tabela_item USING (peca)
					left JOIN tbl_os_produto USING(os_produto)
					left JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.servico_realizado NOT IN (12, 36) ";
			$resC = pg_exec ($con,$sql);

			$total_pecas_novas = 0;
			$total_pecas       = 0;

			for($j=0; $j<@pg_numrows($resC); $j++){
				$qtde  = @pg_result($resC,$j,qtde);
				$preco = @pg_result($resC,$j,preco_item);
				$ipi   = @pg_result($resC,$j,ipi);

				$preco_sem_ipi = $qtde * $preco;

				$com_ipi = 0;

				if ($consumidor_final <> 'f') {
					$com_ipi = ($preco_sem_ipi * $ipi / 100);
				}

				$total_pecas = ($preco * $qtde) + $com_ipi;
	
				if (strlen($desconto_peca) > 0)
					$total_pecas_novas += $total_pecas - ($total_pecas * ($desconto_peca / 100));
				else
					$total_pecas_novas += $total_pecas;
			}

// total
			$total_mo_pecas = $total_os_extra + $total_os_produto + $total_pecas_recuperadas + $total_pecas_novas;

			echo "	<TD bgcolor='$cor' align='right' class='table_line2'>".number_format($total_mo_pecas,2,",",".")."</TD>\n";
			echo "	<TD bgcolor='$cor' class='table_line2' align='center'>";
			if (strlen($data_fechamento) <> 0){
				echo "<input type='hidden' name='aux_os_$i' value='$os'>\n";
				echo "<input type='checkbox' name='os_$i' value='$os'>\n";
			}
			echo "	</TD>\n";
			echo "</tr>\n";

			$total_lote += $total_mo_pecas;
		}

		echo "<tr>\n";
		echo "	<TD colspan=3 class='menu_top'>TOTAL</TD>\n";
		echo "	<TD align='right' class='menu_top'>".number_format($total_lote,2,",",".")."</TD>\n";
		echo "	<TD class='menu_top'></TD>\n";
		echo "</tr>\n";

		echo"<tr>";
		echo"	<td align='center' colspan='6'>";
		echo"		<input type='hidden' name='total_os' value='$i'>";
		echo"		<input type='hidden' name='btn_acao' value=''>";
		echo"		<img src='imagens/btn_gravar.gif' style='cursor: pointer;' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } \" ALT='Confirmar' border='0'>";
		echo"	</td>";
		echo"</tr>";
	}
}
?>

</table>

</form>

<br>

<?
include 'rodape.php';
?>