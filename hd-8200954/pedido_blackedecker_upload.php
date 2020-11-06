<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($posto == "42308" and $cod_posto == "1824") {
	header ("Location: index.php");
	exit;
}

if (strlen ($arquivo) > 0) {
	echo `/var/www/blackedecker/perl/importa-pedidos.pl $arquivo > /tmp/erros-importacao.txt 2>&1 `;
	
	$msg = `cat /tmp/erros-importacao.txt`;
	
	if (strpos ($msg,"Fail to add null value in not null attribute preco") > 0) {
		$msg = substr ($msg,0,20) . " - Peça não encontrada na Tabela de Preços";
	}
	if (strpos ($msg,"Fail to add null value in not null attribute peca") > 0) {
		$msg = substr ($msg,0,20) . " - Peça não cadastrada";
	}
}

$title = "Upload de Pedido de Peças";
$cabecalho = "Upload de Pedido de Peças";
$layout_menu = "pedido";

include "cabecalho.php";

?>

<!-- ---------------------- Inicio do HTML -------------------- -->

<center>
<font face='arial, verdana' size='+1' color='#ff9999'>
<? 
echo $msg ;
echo "<p>";
?>
</font>
</center>



<table width="600" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		Para enviar seu arquivo de pedidos de peças, digite o caminho completo do arquivo no seu
		computador, ou clique no botão para localizá-lo. Depois clique em FINALIZAR.
		<br>
		Seu pedido será submetido e avaliado no site, e se tudo estiver correto, ele já irá aparecer
		no relatório de pedidos. Se houver algum erro, acerte o problema, e reenvie o novo arquivo.
		</font>
	<td>
</tr>

<tr>
	<td>
		<font face='arial,verdana' size='-1' color='#FF0000'>
		<b>
		<br>
		ATENÇÃO:
		<br>
		Foi acrescentada a forma de pagamento no cabeçalho do pedido:  Sequência 5.
		<br>
		Foi acrescentada a unificação do pedido no cabeçalho do pedido:  Sequência 6.
		<br><br>
		Solicitamos que efetuem esta modificação em seus programas de geração do arquivo.
		</b>
		</font>
	<td>
</tr>

<tr>
	<td align="center">
		<br><br>
		<font face='arial,verdana' size='-1'>
		<form method='post' action='<? echo $PHP_SELF ?>' enctype='multipart/form-data'>
		Arquivo de Pedidos a enviar: <input name="arquivo" type="file">
		<p>
		<input type="image" src="imagens/btn_finalizar.gif" alt="Enviar">
		</form>
		</font>
	<td>
</tr>
</table>


<p>


<table width="600" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor='#9999ff' align='center' colspan='7'>
		<font face='arial,verdana' color='#ffffff'><b>
		Lay-Out do arquivo texto.
		</b></font>
	</td>
</tr>

<tr>
	<td bgcolor='#eeeeee' align='center' colspan='7'>
		<font face='arial,verdana' color='#0000ff'><b>
		Registro HEADER - Cabeçalho do arquivo
		</b></font>
	</td>
</tr>

<tr>
	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Seq.
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Campo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Início
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Final
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tamanho
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tipo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Exemplo
		</font>
	</td>
</tr>







<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		1
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Tipo Registro
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		001
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		HEA
		</font>
	</td>
</tr>



<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		2
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		004
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		023
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		020
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>



<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		3
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		CNPJ do Posto
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		024
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		037
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		014
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Num
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		02494691000130
		</font>
	</td>
</tr>


<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		4
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		038
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		042
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		005
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>

<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		5
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Condição de Pagamento
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		043
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		062
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		020
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		<?
		$sql = "SELECT  tbl_condicao.condicao,
						tbl_condicao.descricao
				FROM    tbl_condicao
				ORDER BY lpad(trim(tbl_condicao.codigo_condicao), 10, 0) LIMIT 1";
		$res = pg_exec ($con,$sql);
		
		for ($x=0; $x < @pg_numrows($res); $x++) {
			$condicao = trim(pg_result($res,$x,descricao));
			echo "$condicao<br>";
		}
		?>
		</font>
	</td>
</tr>


<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		6
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Unificar Pedido
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		063
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		065
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		sim ou não (minúsculo)
		</font>
	</td>
</tr>




<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		7
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		066
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		208
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		143
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>








<tr>
	<td bgcolor='#eeeeee' align='center' colspan='7'>
		<font face='arial,verdana' color='#0000ff'><b>
		Registro DETALHE - Itens do pedido
		</b></font>
	</td>
</tr>

<tr>
	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Seq.
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Campo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Início
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Final
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tamanho
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tipo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Exemplo
		</font>
	</td>
</tr>







<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		1
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Tipo Registro
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		001
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		DET
		</font>
	</td>
</tr>


<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		2
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		004
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		009
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		006
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>





<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		3
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Referência da Peça
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		010
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		029
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		020
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		612407 - Espaços a Direita
		</font>
	</td>
</tr>



<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		4
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		030
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		082
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		053
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>


<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		5
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Quantidade
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		083
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		094
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		012
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Num
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		000000000021 (21 peças)
		</font>
	</td>
</tr>




<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		6
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		095
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		208
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		114
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>











<tr>
	<td bgcolor='#eeeeee' align='center' colspan='7'>
		<font face='arial,verdana' color='#0000ff'><b>
		Registro TRAILLER - Final da Transmissão
		</b></font>
	</td>
</tr>

<tr>
	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Seq.
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Campo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Início
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Final
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tamanho
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Tipo
		</font>
	</td>

	<td  bgcolor='#9999ff' align='center' >
		<font face='arial,verdana' size='-1'>
		Exemplo
		</font>
	</td>
</tr>







<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		1
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Tipo Registro
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		001
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		003
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		FTP
		</font>
	</td>
</tr>




<tr>
	<td>
		<font face='arial,verdana' size='-1'>
		2
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Brancos
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		004
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		208
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		205
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		Alfa
		</font>
	</td>

	<td>
		<font face='arial,verdana' size='-1'>
		&nbsp;
		</font>
	</td>
</tr>





</table>


<p>


<?include "rodape.php";?>
