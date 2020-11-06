<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$sql = $_POST['query_gera'];
$sql = str_replace("\\","",$sql);
if(strlen($_POST['tipo'])>0){
$tipo = $_POST['tipo'];
}else{
$tipo = $_POST['tipox'];
}
if($tipo=='txt')$apli = "xml/tabela";
if($tipo=='xls')$apli = "application/msexcel";
$arquivo = "tabela".$tipo;
//echo $sql;

$res = pg_exec ($con,$sql);

	if($tipo=='xls'){
		header("Content-type: $apli");
		header("Content-Disposition: attachment; filename=tabela.$tipo");
		header('Expires: 0'); 
		header('Pragma: no-cache');
		echo "<table width='600' align='center' cellspacing='3' border='0'>";
		echo "<tr>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Peça</b></font></td>";
							echo "<td bgcolor='#007711' align='left'><font face='arial' color='#ffffff'><b>Descrição</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Unidade</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>IPI</b></font></td>";
							echo "</tr>";
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$produto_referencia = trim(@pg_result ($res,$i,produto_referencia));
			$produto_descricao  = trim(@pg_result ($res,$i,produto_descricao));
			$prox_refer         = trim(@pg_result ($res,$i-1,produto_referencia));
			$prox_descr         = trim(@pg_result ($res,$i-1,produto_descricao));
			$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
			$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
			$unidade            = trim(pg_result ($res,$i,unidade));
			$preco              = trim(pg_result ($res,$i,preco));
			$ipi                = trim(pg_result ($res,$i,ipi));
			
			$cor = '#ffffff';
			if ($i % 2 == 0) $cor = '#f8f8f8';
			if ($mostraTopo <> 'n'){
				if ($prox_refer <> $produto_referencia OR $prox_descr <> $produto_descricao) {
					echo "<tr>";
					echo "<td bgcolor='#007711' align='center' colspan='5'><font face='arial' color='#ffffff'><b>$produto_referencia - $produto_descricao $volt_prod</b></font></td>";
					echo "</tr>";
				}
			}
			
			echo "<tr bgcolor='$cor'>";
			
			echo "<td>";
			echo "<font face='arial' size='-2'>";
			echo $peca_referencia;
			echo "</font>";
			echo "</td>";
			
			echo "<td align='left'>";
			echo "<font face='arial' size='-2'>";
			echo $peca_descricao;
			echo "</font>";
			echo "</td>";
			
			echo "<td>";
			echo "<font face='arial' size='-2'>";
			echo $unidade;
			echo "</font>";
			echo "</td>";
			
			echo "<td align='right'>";
			echo "<font face='arial' size='-2'>";
			echo number_format ($preco,2,",",".");
			echo "</font>";
			echo "</td>";
			
			echo "<td align='right'>";
			echo "<font face='arial' size='-2'>";
			echo $ipi;
			echo "</font>";
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}else{
		$arquivo = "/tmp/assist/tabela.txt";
		$fp = fopen ($arquivo,"w");
		fputs ($fp,"Referência da peça");
		fputs ($fp,"\t");
		fputs ($fp,"Descrição da peça");
		fputs ($fp,"\t");
		fputs ($fp,"Unidade");
		fputs ($fp,"\t");
		fputs ($fp," Preço");
		fputs ($fp,"\t");
		fputs ($fp," IPI");
		fputs ($fp,"\n");

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$produto_referencia = trim(@pg_result ($res,$i,produto_referencia));
			$produto_descricao  = trim(@pg_result ($res,$i,produto_descricao));
			$prox_refer         = trim(@pg_result ($res,$i-1,produto_referencia));
			$prox_descr         = trim(@pg_result ($res,$i-1,produto_descricao));
			$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
			$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
			$unidade            = trim(pg_result ($res,$i,unidade));
			$preco              = trim(pg_result ($res,$i,preco));
			$ipi                = trim(pg_result ($res,$i,ipi));
			
			fputs ($fp,$peca_referencia);
			fputs ($fp,"\t");
			fputs ($fp,$peca_descricao);
			fputs ($fp,"\t");
			fputs ($fp,$unidade);
			fputs ($fp,"\t");
			fputs ($fp,number_format ($preco,2,",","."));
			fputs ($fp,"\t");
			fputs ($fp,$ipi);
			fwrite ($fp,"\n");

		}
		fclose ($fp);
		flush();
		echo `mv  /tmp/assist/tabela.txt /var/www/assist/www/download/tabela.txt`;

		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='../download/tabela.txt'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em TXT</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
?>
