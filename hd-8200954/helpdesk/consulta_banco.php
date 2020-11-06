<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


include 'consulta_banco_pid.php';

flush();

$btn_acao = $_POST['btn_acao'];
$sql   = $_POST['sql'];
if(strlen($sql) == 0){
	$sql =$_GET['sql'];
}


$export = pg_exec($con, "select current_timestamp") ;


/*
CREATE TABLE tbl_consulta_banco(
consulta_banco serial,
sql text, 
data timestamp,
admin int4);

ALTER TABLE tbl_consulta_banco ADD CONSTRAINT consulta_banco_pk PRIMARY KEY (consulta_banco);
ALTER TABLE tbl_consulta_banco  ADD CONSTRAINT admin_fk FOREIGN KEY (admin) REFERENCES tbl_admin(admin) ;
*/

if(strlen($btn_acao)>0 or strlen($sql )>0){
	if(strlen($_POST['sql'])>0 or strlen($sql )>0) {

		$sql2 = strtoupper($sql);
		$tem_update =strpos($sql2,'UPDATE');
		if(strlen($tem_update)>0){
			echo "<font color = 'red'>ERRO MESMO: Tem update!!! Não é possível executar UPDATE aqui!</font>";
		}else{
			$res = pg_exec ($con,"insert into tbl_consulta_banco( sql,data, admin) values('$sql', current_timestamp, $login_admin)");
			
			$select = str_replace("\\", "",$sql);

			$export = pg_exec($con, "select current_timestamp;") ;
			$hora_inicio = pg_result($export ,0,0);

			$export = pg_exec($con, "$select");




			$res1= pg_exec($con, "select current_timestamp - '$hora_inicio'::timestamp") ;
			$tempo_exec= pg_result($res1,0,0);


/*			$meta = pg_meta_data($con,'tbl_pedido');
			if (is_array ($meta)) {
				echo '<pre>';
				var_dump ($meta);
				echo '</pre>';
			}
*/

			$fields = pg_num_fields($export); 
			$pg_rows= pg_numrows($export );
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
			$data .="<tr><td colspan='$fields' nowrap>Linhas: $pg_rows - Tempo de execução: $tempo_exec - PID DESSA CONSULTA: $pid</td></tr>";
						
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
	}else{
		echo "<font color = 'red'>SQL em braco!</font>";
		
	}
}
?>

<form name="frm_excel" method="post" action="<? echo $PHP_SELF ?>">
<table width='750' align='center' border='0' bgcolor='#dddddd' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 10px; color:#FFFFFF'>
<tr>
<TD ><b>Sql:</b>
<TEXTAREA NAME='sql' ROWS='8' COLS='120'><?echo $sql;?></TEXTAREA>
</TD>
</tr>
<tr>
<td align='center'>
<input type = 'button' onclick="javascript: if (document.frm_excel.btn_acao.value == '' ) { document.frm_excel.btn_acao.value='continuar' ; document.frm_excel.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" value = 'Consultar' style='cursor: pointer'>
</td>
</tr>
</table>
<input type='hidden' name='btn_acao' value=''>
</form>

<?

$sql = "
	SELECT  *
	FROM tbl_consulta_banco
	WHERE admin = $login_admin

	ORDER BY data desc
	LIMIT 35;";
$res = pg_exec ($con,$sql);
echo "<table width='750' align='center' border='0' bgcolor='#dddddd' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 10px; color:#FFFFFF'>";
echo "<tr bgcolor='#ffffff'>";
echo "<td nowrap style='font-family: verdana; font-color: blue; font-size: 14px;' ><font color = blue><b> Historico de Consultas</b></font></td>";
echo "</tr>";
if(pg_numrows($res)> 0){
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = "#FFFFFF";
		if ($i % 2 == 0) $cor = '#F1F4FA';
		$sql             = pg_result ($res,$i,sql);
		echo "<tr bgcolor='$cor'>";
		echo "<td class='table_line1' nowrap><font color = 'blue'><a href='$PHP_SELF?sql=$sql'>$sql</a></font></td>";
		echo "</tr>";
	}
}
echo "</table>";
?>

