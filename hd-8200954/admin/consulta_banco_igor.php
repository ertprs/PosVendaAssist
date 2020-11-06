<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao)>0){
	$sql   = $_POST['sql'];
	$sql2 = strtoupper($sql);
	$tem_update =strpos($sql2,'UPDATE');
	if(strlen($tem_update)>0){
		echo "<font color = 'red'>ERRO MESMO: Tem update!!! Não é possível executar UPDATE aqui!</font>";
	}else{
		
		$select = str_replace("\\", "",$sql);

		$export = pg_exec($con, "$select");
		$fields = pg_num_fields($export); 
		$header ="	<table width='700' border='1' cellpadding='1' cellspacing='2' align='center' style='font-family: verdana; font-size: 10px;' >";


		$header .="	<TR BGCOLOR='#33CCFF'>";
		for ($i = 0; $i < $fields; $i++) {
			
			//$header .= pg_field_name($export, $i) . "\t"; 
			$header .= "<TD >".pg_field_name($export, $i)."</TD>";
		}
		$header .="	</TR>";		
		while($row = pg_fetch_row($export)) {
			$line = '';
			$line .="<TR>";
			foreach($row as $value) {
				if ((!isset($value)) OR ($value == "")) {
					$value = "VAZIO";
				} else {
					$value = str_replace('"', '""', $value);
					//$value = '"' . $value . '"' . "\t";
				}
				$line .="<TD NOWRAP>";
				$line .= $value;
				$line .="</TD>";
			}
			$line .="</TR>";
			$data .= trim($line)."\n";
		}
		$data .="</TABLE>";
		$data = str_replace("\r","",$data); 
		
		
		if ($data == "") {
			$data = "\n(0) Records Found!\n";
		}else{
			echo "$header\n$data";  

		/*	$hoje=date("Y_m_j");			  
			header("Content-type: application/x-msdownload; charset=iso-8859-1");
			header("Content-Disposition: attachment; filename=".$nome_excel.".xls");
			header("Pragma: no-cache");
			header("Expires: 0");
			print "$header\n$data";  */
		}
	}
}
?>

<form name="frm_excel" method="post" action="<? echo $PHP_SELF ?>">
<table width='750' align='center' border='0' bgcolor='#dddddd' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 10px; color:#FFFFFF'>
<tr>
<TD ><b>Sql:</b>
<TEXTAREA NAME='sql' ROWS='8' COLS='120'><?echo $_POST['sql'];?></TEXTAREA>
</TD>
</tr>
<tr>
<td align='center'>
<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_excel.btn_acao.value == '' ) { document.frm_excel.btn_acao.value='continuar' ; document.frm_excel.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
</td>
</tr>
</table>
<input type='hidden' name='btn_acao' value=''>
</form>
