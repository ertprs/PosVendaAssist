<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$sql = "SELECT extrato, 
				to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as data_geracao,
				tbl_extrato.total
			FROM tbl_extrato
			WHERE fabrica = 45
			AND  tbl_extrato.posto = $login_posto";
$res = pg_exec($con,$sql);

if(pg_numrows($res) > 0){
	echo "<table align='center'>
		<tr>
			<td>Extrato</td>
			<td>Data Gereção</td>
			<td>Total</td>
		</tr>";

		for($i=0;$i<pg_numrows($res);$i++){
			$extrato      = pg_result($res,$i,extrato);
			$data_geracao = pg_result($res,$i,data_geracao);
			$total        = pg_result($res,$i,total);
			
			echo "<tr>
			<td><a href='$PHP_SELF?extrato=$extrato'>$extrato</a></td>
			<td>$data_geracao</td>
			<td>$total</td>
			</tr>";
		}
	echo "</table>";
}




$extrato = $_GET['extrato'];

if(strlen($extrato) > 0){
	$sql = "select tbl_os.sua_os                                           ,
		tbl_os.rg_produto                                      ,
		tbl_produto.referencia                                 ,
		tbl_produto.descricao                                  ,
		to_char(tbl_os.data_abertura,'DD/MM/YYYY') as entrada  ,
		to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as saida  ,
		(select count(*) from tbl_os_item join tbl_os_produto using(os_produto) join tbl_peca using(peca) where tbl_os_produto.os = tbl_os.os and produto_acabado is not true) as total_pecas,
		tbl_os.mao_de_obra
	from tbl_os_extra
	join tbl_os using(os)
	join tbl_extrato using(extrato)
	join tbl_produto on tbl_produto.produto = tbl_os.produto
	where extrato = 359141;";

	$res = pg_exec($con,$sql);

	if(pg_numrows($res) > 0){

		echo "<table align='center' style='font-size: 10px'>
		<tr>
			<td align='center'><b>OS</b></td>
			<td align='center'><b>RG PRODUTO</b></td>
			<td align='center'><b>REFERÊNCIA</b></td>
			<td align='center'><b>DESCRIÇÃO</b></td>
			<td align='center'><b>ENTRADA</b></td>
			<td align='center'><b>SAÍDA</b></td>
			<td align='center'><b>QTDE PEÇAS</b></td>
			<td align='center'><b>MÃO-DE-OBRA</b></td>
		</tr>";

		for($i=0;$i<pg_numrows($res);$i++){
			$sua_os      = pg_result($res,$i,sua_os);
			$rg_produto  = pg_result($res,$i,rg_produto);
			$referencia  = pg_result($res,$i,referencia);
			$descricao   = pg_result($res,$i,descricao);
			$entrada     = pg_result($res,$i,entrada);
			$saida       = pg_result($res,$i,saida);
			$mao_de_obra = pg_result($res,$i,mao_de_obra);
			$total_pecas = pg_result($res,$i,total_pecas);

			echo "<tr>
				<td align='center'>$sua_os</td>
				<td align='center'>$rg_produto</td>
				<td align='center'>$referencia</td>
				<td align='center'>$descricao</td>
				<td align='center'>$entrada</td>
				<td align='center'>$saida</td>
				<td align='center'>$total_pecas</td>
				<td align='center'>". number_format($mao_de_obra,2,',','.') ."</td>
			</tr>";
		}
		echo "</table>";
	}
}else{
	$msg_erro = "Selecione o Extrato";
}


?>