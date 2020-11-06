<HTML>
<HEAD>
<TITLE> Relatório de Quantidade de OS </TITLE>

</HEAD>

<BODY>

<TABLE border='0' align='center' cellspacing='0' cellpadding='0' width='500' >

<tr>
	<td background='fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='3'><img src='pixel.gif' width='9'></td>
	<td background='fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><B>HOBBY: Quantidade de OS´s Aprovadas</B></td>
	<td background='fundo_tabela_top_direito_azul_claro.gif'  rowspan='3'><img src='pixel.gif' width='9'></td>
</tr>

<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td colspan='6' align='center'></td>
</tr>

<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td align='center' colspan='2'><strong>Posto</strong></td>
	<td align='center'><strong>Fevereiro</strong></td>
	<td align='center'><strong>Março</strong></td>
	<td align='center'><strong>Abril</strong></td>
	<td align='center'><strong>Total/Posto</strong></td>
</tr>

<? for ($i = 0 ; $i < 3 ; $i++){ ?>
<tr  style='font-family: arial ; font-size: 12px ; ' height='25' bgcolor='#FFFFFF' >
	<td background='fundo_tabela_centro_esquerdo.gif' ><img src='pixel.gif' width='9'></td>
	<td align='center' colspan='2'><strong><? $posto ?>8</strong></td>
	<td align='center'><strong><? $qtde_mes3_HO="10"; ?>10</strong></td>
	<td align='center'><strong><? $qtde_mes2_HO="10"; ?>10</strong></td>
	<td align='center'><strong><? $qtde_mes1_HO="10" ?>10</strong></td>
	<? $total_mes_HO= $qtde_mes3_HO + $qtde_mes2_HO + $qtde_mes1_HO ?>
	<td align='center'><strong> <? echo "$total_mes_HO"; ?> </strong></td>
	<td background='fundo_tabela_centro_direito.gif' ><img src='pixel.gif' width='9'></td>
	<?	$total_HO=$total_mes_HO + $total_HO; 
		$total_mes3_HO=$qtde_mes3_HO + $total_mes3_HO;
		$total_mes2_HO=$qtde_mes2_HO + $total_mes2_HO;
		$total_mes1_HO=$qtde_mes1_HO + $total_mes1_HO;
	?>

</tr>
<? } ?>
<!-- ==================ULIMA LINHA DA TABELA TOTAL DA TABELA======================================- -->

<tr  bgcolor='#F0F7FF' style='font-family: arial ; color: #666666' >
	<td background='fundo_tabela_centro_esquerdo.gif' ><img src='pixel.gif' width='9'></td>
	<td colspan='2' align='left'><strong>Total/Mês</strong></td>
	<td align='center'><strong><? echo "$total_mes3_HO"; ?></strong></td>
	<td align='center'><strong><? echo "$total_mes2_HO"; ?></strong></td>
	<td align='center'><strong><? echo "$total_mes1_HO"; ?></strong></td>
	<td align='center'><strong><? echo "$total_HO"; ?></strong></td>
	<td background='fundo_tabela_centro_direito.gif' ><img src='pixel.gif' width='9'></td>
</tr>

<tr>
	<td background='fundo_tabela_baixo_esquerdo.gif'><img src='pixel.gif' width='9'></td>
	<td background='fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' ></td>
	<td background='fundo_tabela_baixo_direito.gif'><img src='pixel.gif' width='9'></td>
</tr>

<!-- ==================ULIMA LINHA DA TABELA TOTAL DA TABELA======================================- -->

</TABLE>

<br><br>

<TABLE border='0' align='center' cellspacing='0' cellpadding='0' width='500' >

<tr>
	<td background='fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='3'><img src='pixel.gif' width='9'></td>
	<td background='fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><B>DEWALT: Quantidade de OS´s Aprovadas</B></td>
	<td background='fundo_tabela_top_direito_azul_claro.gif'  rowspan='3'><img src='pixel.gif' width='9'></td>
</tr>

<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td colspan='6' align='center'></td>
</tr>

<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td align='center' colspan='2'><strong>Posto</strong></td>
	<td align='center'><strong>Fevereiro</strong></td>
	<td align='center'><strong>Março</strong></td>
	<td align='center'><strong>Abril</strong></td>
	<td align='center'><strong>Total/Posto</strong></td>
</tr>



<? for ($i = 0 ; $i < 3 ; $i++){ ?>
<tr  style='font-family: arial ; font-size: 12px ; ' height='25' bgcolor='#FFFFFF' >
	<td background='fundo_tabela_centro_esquerdo.gif' ><img src='pixel.gif' width='9'></td>
	<td align='center' colspan='2'><strong><? $posto ?>8</strong></td>
	<td align='center'><strong><? $qtde_mes3_DW="5"; ?>5</strong></td>
	<td align='center'><strong><? $qtde_mes2_DW="5"; ?>5</strong></td>
	<td align='center'><strong><? $qtde_mes1_DW="5"; ?>5</strong></td>
	<? $total_mes_DW= $qtde_mes3_DW + $qtde_mes2_DW + $qtde_mes1_DW ?>
	<td align='center'><strong> <? echo "$total_mes_DW"; ?> </strong></td>
	<td background='fundo_tabela_centro_direito.gif' ><img src='pixel.gif' width='9'></td>
	<?	$total_DW=$total_mes_DW + $total_DW; 
		$total_mes3_DW=$qtde_mes3_DW + $total_mes3_DW;
		$total_mes2_DW=$qtde_mes2_DW + $total_mes2_DW;
		$total_mes1_DW=$qtde_mes1_DW + $total_mes1_DW;
	?>

</tr>
<? } ?>


<!-- ==================ULIMA LINHA DA TABELA TOTAL DA TABELA======================================- -->

<tr  bgcolor='#F0F7FF' style='font-family: arial ; color: #666666' >
	<td background='fundo_tabela_centro_esquerdo.gif' ><img src='pixel.gif' width='9'></td>
	<td colspan='2' align='left'><strong>Total/Mês</strong></td>
	<td align='center'><strong><? echo "$total_mes3_DW"; ?></strong></td>
	<td align='center'><strong><? echo "$total_mes2_DW"; ?></strong></td>
	<td align='center'><strong><? echo "$total_mes1_DW"; ?></strong></td>
	<td align='center'><strong><? echo "$total_DW"; ?></strong></td>

	<td background='fundo_tabela_centro_direito.gif' ><img src='pixel.gif' width='9'></td>
</tr>

<tr>
	<td background='fundo_tabela_baixo_esquerdo.gif'><img src='pixel.gif' width='9'></td>
	<td background='fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' ></td>
	<td background='fundo_tabela_baixo_direito.gif'><img src='pixel.gif' width='9'></td>
</tr>

<!-- ==================ULIMA LINHA DA TABELA TOTAL DA TABELA======================================- -->

</TABLE>

<br><br>

<!-- ======INICIO=======Estatísticas por mes HOBBY/DEWALT================================= -->

<TABLE border='0' align='center' cellspacing='0' cellpadding='0' width='500' >

<tr>
	<td background='fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='3'><img src='pixel.gif' width='9'></td>
	<td background='fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><B>Estatísticas HOBBY/DEWALT</B></td>
	<td background='fundo_tabela_top_direito_azul_claro.gif'  rowspan='3'><img src='pixel.gif' width='9'></td>
</tr>

<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td colspan='6' align='center'></td>
</tr>

<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td align='center' colspan='2'><strong>Posto</strong></td>
	<td align='center'><strong>Fevereiro</strong></td>
	<td align='center'><strong>Março</strong></td>
	<td align='center'><strong>Abril</strong></td>
	<td align='center'><strong>Total/Posto</strong></td>
</tr>

<? for( $i= 0; $i < 3; $i++){ ?>
<tr  style='font-family: arial ; font-size: 12px ; ' height='25' bgcolor='#FFFFFF' >
	<td background='fundo_tabela_centro_esquerdo.gif' ><img src='pixel.gif' width='9'></td>
	<td align='center' colspan='2'><strong><? $posto ?>8</strong></td>
	<td align='center'><strong><? $qtde_mes3=$total_mes3_HO+$total_mes3_DW; echo "$qtde_mes3"; ?></strong></td>
	<td align='center'><strong><? $qtde_mes2=$total_mes2_HO+$total_mes2_DW; echo "$qtde_mes2"; ?></strong></td>
	<td align='center'><strong><? $qtde_mes1=$total_mes1_HO+$total_mes1_DW; echo "$qtde_mes1"; ?></strong></td>
	<? $total_mes= $qtde_mes3 + $qtde_mes2 + $qtde_mes1 ?>
	<td align='center'><strong><? echo "$total_mes"; ?></strong></td>
	<td background='fundo_tabela_centro_direito.gif' ><img src='pixel.gif' width='9'></td>
	<?	$total=$total_mes + $total; 
		$total_mes3=$qtde_mes3 + $total_mes3;
		$total_mes2=$qtde_mes2 + $total_mes2;
		$total_mes1=$qtde_mes1 + $total_mes1;
	?>
</tr>
<?}?>
<!-- ==================ULIMA LINHA DA TABELA TOTAL DA TABELA======================================- -->

<tr  bgcolor='#F0F7FF' style='font-family: arial ; color: #666666' >
	<td background='fundo_tabela_centro_esquerdo.gif' ><img src='pixel.gif' width='9'></td>
	<td colspan='2' align='left'><strong>Total/Mês</strong></td>
	<td align='center'><strong><? echo "$total_mes3"; ?></strong></td>
	<td align='center'><strong><? echo "$total_mes2"; ?></strong></td>
	<td align='center'><strong><? echo "$total_mes1"; ?></strong></td>
	<td align='center'><strong><? echo "$total"; ?></strong></td>

	<td background='fundo_tabela_centro_direito.gif' ><img src='pixel.gif' width='9'></td>
</tr>

<tr>
	<td background='fundo_tabela_baixo_esquerdo.gif'><img src='pixel.gif' width='9'></td>
	<td background='fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' ></td>
	<td background='fundo_tabela_baixo_direito.gif'><img src='pixel.gif' width='9'></td>
</tr>

<!-- ==================ULIMA LINHA DA TABELA TOTAL DA TABELA======================================- -->

<!-- =================FIM=======Estatísticas por mes HOBBY/DEWALT================================= -->


</TABLE>


</BODY>
</HTML>
