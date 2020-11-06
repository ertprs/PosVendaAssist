<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

?><?

$linha = $_GET['linha'];
$data_inicial = $_GET['data'];

$dia = substr($data,0,2);
$dias = 5;

$data_final = mktime ( 0, 0, 0, date('m'),$dia + $dias, date('Y'));
$data_final = strftime("%d/%m/%Y", $data_final);

$posto = $_GET['posto'];
$fone = $_GET['fone'];

$fnc = pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

if (strlen ( pg_errormessage ($con) ) > 0) {
	$erro = pg_errormessage ($con) ;
}
if (strlen($erro) == 0) {
	$aux_data_inicial = @pg_result ($fnc,0,0);
}

$fnc = pg_exec($con,"SELECT fnc_formata_data('$data_final')");

if (strlen ( pg_errormessage ($con) ) > 0) {
	$erro = pg_errormessage ($con) ;
}




if (strlen($erro) == 0) {
	$aux_data_final = @pg_result ($fnc,0,0);
}

	$sql = "SELECT	tbl_os.os,
					tbl_os.consumidor_nome,
					tbl_os.consumidor_fone,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') as abertura_os,
					tbl_produto.descricao,
					tbl_produto.referencia
		FROM tbl_os
		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND 
		     tbl_os.fabrica = tbl_posto_fabrica.fabrica
		WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.data_abertura between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
";

//echo nl2br($sql);

$res = pg_exec ($con,$sql);

	echo "<table border=1 cellpadding=1 cellspacing=1 style=border-collapse: collapse bordercolor=#d2e4fc align=center width=500>";
		echo "<tr bgcolor=#596d9b>";
		echo "<td><font color=white><b>Os</b></td>";
		echo "<td><font color=white><b>Posto</b></td>";
		echo "<td ><font color=white><b>Data de Abertura</b></td>";
		echo "<td ><font color=white><b>Cliente</b></td>";		
		echo "<td ><font color=white><b>Fone</b></td>";		
		
		echo "<td ><font color=white><b>Produto</b></td>";
		echo "</tr>";
		
	
		$total = pg_numrows($res);
		$total_pecas = 0;
		
		for ($y=0; $y<pg_numrows($res); $y++){

			$os                       = trim(pg_result($res,$y,os));
			$descricao                = trim(pg_result($res,$y,descricao));
			$referencia               = trim(pg_result($res,$y,referencia));
			$cliente                  = trim(pg_result($res,$y,consumidor_nome));
			$consumidor_fone          = trim(pg_result($res,$y,consumidor_fone));
			$consumidor_fone_s_mascara= trim(pg_result($res,$y,consumidor_fone));
			$codigo_posto             = trim(pg_result($res,$y,codigo_posto));
			$nome                     = trim(pg_result($res,$y,nome));
			$data                     = trim(pg_result($res,$y,abertura_os));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			
			$palavras = explode(' ',$consumidor_fone_s_mascara);
			$count = count($palavras);
			for($x=0 ; $x < $count ; $x++){
				if(strlen(trim($palavras[$x]))>0){
					$consumidor_fone_s_mascara = trim($palavras[$x]);
					$consumidor_fone_s_mascara = str_replace (' ','',$consumidor_fone_s_mascara);
					$consumidor_fone_s_mascara = str_replace ('(','',$consumidor_fone_s_mascara);
					$consumidor_fone_s_mascara = str_replace (')','',$consumidor_fone_s_mascara);
				}
			}

//			echo $consumidor_fone_s_mascara; echo $fone;

			echo "<tr  bgcolor=$cor>";
				echo "<td align=center nowrap><a href=os_press.php?os=$os target=_blank>$os</a></td>";
				echo "<td align=left nowrap>$codigo_posto - $nome</td>";
				echo "<td align=left nowrap>$data</td>";
				echo "<td align=left nowrap>$cliente</td>";
				
				?>
				<td align=left nowrap <?if ($consumidor_fone_s_mascara==$fone) { ?>	bgcolor=yellow <?}?> ><?echo $consumidor_fone?></td><?
				echo "<td bgcolor=$cor nowrap>$referencia - $descricao</td>";
			echo "</tr>";
				}
		echo "</table>";






echo "|@".$linha;

?>