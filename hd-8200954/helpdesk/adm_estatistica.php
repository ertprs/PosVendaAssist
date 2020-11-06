<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$TITULO = "Estatísticas - Telecontrol Hekp-Desk";

include "menu.php";


#NOVO#
$sqlnovo = "SELECT count(*) AS total_novo
	FROM tbl_hd_chamado
	WHERE (status ILIKE 'novo' OR status ILIKE '$status'  OR status IS NULL) 
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica ";
if($atendente_busca <> ''){
	$sqlnovo .= " AND atendente = '$atendente_busca'";
	}
if($fabrica_busca<>''){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

}
	$resnovo = @pg_exec ($con,$sqlnovo);
//echo "$sqlnovo<BR>";
if (@pg_numrows($resnovo) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_novo           = pg_result($resnovo,0,total_novo);
	}
//echo "$xtotal_novo";

#ANALISE#
$sqlanalise = "SELECT count(*) AS total_analise
	FROM tbl_hd_chamado
	WHERE status ILIKE 'análise' 
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica ";
if($atendente_busca <> ''){
	$sqlanalise .= " AND atendente = '$atendente_busca'";
	}
	if($fabrica_busca<>''){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

	}
	$resanalise = @pg_exec ($con,$sqlanalise);
//echo "$sqlanalise<BR>";
if (@pg_numrows($resanalise) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_analise           = pg_result($resanalise,0,total_analise);
	}
//echo "$xtotal_analise";
#APROVACAO#
$sqlaprovacao = "SELECT count(*) AS total_aprovacao
	FROM tbl_hd_chamado
	WHERE status ILIKE 'aprovação' 
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica ";
if($atendente_busca <> ''){
	$sqlaprovacao .= " AND atendente = '$atendente_busca'";
	}
	if($fabrica_busca<>''){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

}
	$resaprovacao = @pg_exec ($con,$sqlaprovacao);
//echo "$sqlaprovacao<BR>";
if (@pg_numrows($resnovo) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_aprovacao          = pg_result($resaprovacao,0,total_aprovacao);
	}
//echo "$xtotal_aprovacao";

#RESOLVIDO#
$sqlresolvido = "SELECT count(*) AS total_resolvido
	FROM tbl_hd_chamado
	WHERE status ILIKE 'resolvido' 
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica ";
if($atendente_busca <> ''){
	$sqlresolvido .= " AND atendente = '$atendente_busca'";
	}
	if($fabrica_busca<>''){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

}
	$resresolvido = @pg_exec ($con,$sqlresolvido);
//echo "$sqlresolvido<BR>";
if (@pg_numrows($resresolvido) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_resolvido         = pg_result($resresolvido,0,total_resolvido);
	}
//echo "$xtotal_resolvido";

#EXECUCAO#
$sqlexecucao = "SELECT count(*) AS total_execucao
	FROM tbl_hd_chamado
	WHERE status ILIKE 'execução' 
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica ";
if($atendente_busca <> ''){
	$sqlexecucao .= " AND atendente = '$atendente_busca'";
	}
	if($fabrica_busca<>''){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

}
	$resexecucao = @pg_exec ($con,$sqlexecucao);
//echo "$sqlexecucao<BR>";
if (@pg_numrows($resexecucao) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_execucao         = pg_result($resexecucao,0,total_execucao);
	}
//echo "$xtotal_execucao";
#CANCELADO#
$sqlcancelado = "SELECT count(*) AS total_cancelado
	FROM tbl_hd_chamado
	WHERE status ILIKE 'cancelado' 
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica";
if($atendente_busca <> ''){
	$sqlcancelado .= " AND atendente = '$atendente_busca'";
	}
	if($fabrica_busca<>''){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

}
	$rescancelado = @pg_exec ($con,$sqlcancelado);
//echo "$sqlcancelado<BR>";
if (@pg_numrows($rescancelado) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_cancelado         = pg_result($rescancelado,0,total_cancelado);
	}
//echo "$xtotal_cancelado";
#TODOS#
$sqltodos = "SELECT count(*) AS total_todos
	FROM tbl_hd_chamado 
	WHERE 1=1 
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica ";
if($atendente_busca <> ''){
	$sqltodos .= " AND atendente = '$atendente_busca'";
	}
	if($fabrica_busca<>''){
	$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";

}
	$restodos = @pg_exec ($con,$sqltodos);
//echo "$sqltodos<BR>";
if (@pg_numrows($restodos) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_todos         = pg_result($restodos,0,total_todos);
	$xtotal_aberto        = $xtotal_todos - $xtotal_cancelado - $xtotal_resolvido  - $xtotal_aprovacao;
	}
$sql_fabrica =  "SELECT nome FROM tbl_fabrica where fabrica=$login_fabrica";

$res_fabrica = @pg_exec ($con,$sql_fabrica);
if (@pg_numrows($res_fabrica) > 0) {
	$nome         = pg_result($res_fabrica,0,nome);
}


$vTotal =$xtotal_novo+$xtotal_analise+$xtotal_aprovacao+$xtotal_cancelado+$xtotal_execucao+$xtotal_resolvido ;
$v1 = $xtotal_novo;
$v2 = $xtotal_analise;
$v3 = $xtotal_aprovacao;
$v4 = $xtotal_cancelado;
$v5 = $xtotal_execucao;
$v6 = $xtotal_resolvido;

$Per_opt1 = number_format($v1 / $vTotal * 100,1);
$Per_opt2 = number_format($v2 / $vTotal * 100,1);
$Per_opt3 = number_format($v3 / $vTotal * 100,1);
$Per_opt4 = number_format($v4 / $vTotal * 100,1);
$Per_opt5 = number_format($v5 / $vTotal * 100,1);
$Per_opt6 = number_format($v6 / $vTotal * 100,1);


?>
<br><table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Estatística de Chamadas da <?=$nome?></b></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td nowrap colspan="9"></td>
	<td nowrap></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap ><CENTER>Novo: <B><? echo $xtotal_novo ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap ><CENTER>Análise: <B><? echo $xtotal_analise ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER>Aprovação: <B><? echo $xtotal_aprovacao ?></B></CENTER></td>
	<td nowrap><CENTER>Resolvido: <B><? echo $xtotal_resolvido ?></B></CENTER></td>
	<td nowrap><CENTER>Execução: <B><? echo $xtotal_execucao ?></B></CENTER></td>
	<td nowrap><CENTER>Cancelado: <B><? echo $xtotal_cancelado ?></B></CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap ><CENTER><B><? echo $Per_opt1 ?>%</B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap ><CENTER><B><? echo $Per_opt2 ?>%</B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER><B><? echo $Per_opt3 ?>%</B></CENTER></td>
	<td nowrap><CENTER><B><? echo $Per_opt6 ?>%</B></CENTER></td>
	<td nowrap><CENTER><B><? echo $Per_opt5 ?>%</B></CENTER></td>
	<td nowrap><CENTER><B><? echo $Per_opt4 ?>%</B></CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap colspan='8'><CENTER>
		<FORM METHOD=POST >
		<INPUT TYPE="submit" value='Clique Aqui para gerar o gráfico' onClick="javascript:popUp('<? echo "pizza.php?titulo=Chamados da $nome&dp=Novos;Análise;Aprovação;Cancelados;Execução;Resolvido&parcela=$xtotal_novo;$xtotal_analise;$xtotal_aprovacao;$xtotal_cancelado;$xtotal_execucao;$xtotal_resolvido"?>')">

</FORM>
	</CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
</table>
<center>
<?
$sql="
	SELECT count(*)  AS total_pessoal,
			nome_completo AS atendente
	FROM       tbl_hd_chamado
	JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
	WHERE      status ILIKE 'resolvido'
	AND        tbl_admin.ativo
	AND        tbl_admin.admin not in (24,435)
	AND        tbl_admin.responsabilidade IS NOT NULL
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica 
	GROUP BY nome_completo
	ORDER BY total_pessoal DESC";


$res_individual = @pg_exec ($con,$sql);
$passa_atendente='';
if (@pg_numrows($res_individual) > 0) {
	for ($i = 0 ; $i < pg_numrows ($res_individual) ; $i++) {
		$atendente = pg_result($res_individual,$i,atendente);
		$total_pessoal = pg_result($res_individual,$i,total_pessoal);
		if($i==0){
			$passa_atendente = $atendente;
			$total           = $total_pessoal;
		}else{
			$passa_atendente = $passa_atendente.';'.$atendente;
			$total           = $total.';'.$total_pessoal;
		}
	}
	echo"<FORM METHOD='POST' ><INPUT TYPE='submit' value='Gráfico de Chamados resolvidos por atendente' onClick=\"javascript:popUp('pizza.php?titulo=Chamdos Resolvidos&dp=$passa_atendente&parcela=$total')\"></FORM>";
}

$sql="
	SELECT count(hd_chamado)  AS total_pessoal,
			nome_completo as atendente
	FROM       tbl_hd_chamado
	JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
	WHERE      tbl_admin.ativo
	AND        tbl_admin.admin not in (24,435)
	AND        tbl_admin.responsabilidade IS NOT NULL
	and tbl_hd_chamado.fabrica_responsavel = $login_fabrica 
	GROUP BY nome_completo
	ORDER BY total_pessoal DESC";

$res_individual = @pg_exec ($con,$sql);
if (@pg_numrows($res_individual) > 0) {
	for ($i = 0 ; $i < pg_numrows ($res_individual) ; $i++) {
		$atendente = pg_result($res_individual,$i,atendente);
		$total_pessoal = pg_result($res_individual,$i,total_pessoal);
		if($i==0){
			$passa_atendente = $atendente;
			$total           = $total_pessoal;
		}else{
			$passa_atendente = $passa_atendente.';'.$atendente;
			$total           = $total.';'.$total_pessoal;
		}
	}
	echo"<FORM METHOD='POST' ><INPUT TYPE='submit' value='Gráfico de Chamadas por atendente' onClick=\"javascript:popUp('pizza.php?titulo=Total de Chamados&dp=$passa_atendente&parcela=$total')\"></FORM>";
}

?>
</center>