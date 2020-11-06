<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao)>0){
$nome_excel = $_POST['nome_excel'];
$sql   = $_POST['sql'];
    $select = str_replace("\\", "",$sql);

/*	$select = "
select	SUA_OS,
	to_char(data_digitacao , 'dd/mm/yyyy') as DATA_DIGITACAO,
	CONSUMIDOR_NOME          ,
	REVENDA_CNPJ             ,
	REVENDA_NOME             ,
	CONSUMIDOR_CIDADE        ,
	CONSUMIDOR_ESTADO        ,
	CONSUMIDOR_FONE          ,
	CONSUMIDOR_ENDERECO      ,
	CONSUMIDOR_NUMERO        ,
	CONSUMIDOR_CEP           ,
	CONSUMIDOR_COMPLEMENTO   ,
	CONSUMIDOR_BAIRRO
FROM tbl_os
JOIN tbl_posto using(posto)
WHERE pais = 'MX'
	AND data_digitacao between '2007-10-10' AND  '2007-10-20';";*/

$select="


SELECT  *
from tmp_relatorio_os_por_posto_maio
";


    $export = pg_exec($con, "$select");
    $fields = pg_num_fields($export);

    for ($i = 0; $i < $fields; $i++) {
        $header .= pg_field_name($export, $i) . "\t";
    }

    while($row = pg_fetch_row($export)) {
        $line = '';
        foreach($row as $value) {
            if ((!isset($value)) OR ($value == "")) {
                $value = "\t";
            } else {
                $value = str_replace('"', '""', $value);
                $value = '"' . $value . '"' . "\t";
            }
            $line .= $value;
        }
        $data .= trim($line)."\n";
    }
    $data = str_replace("\r","",$data);


    if ($data == "") {
        $data = "\n(0) Records Found!\n";
    }
    else{

        $hoje=date("Y_m_j");
        header("Content-type: application/x-msdownload; charset=iso-8859-1");
        header("Content-Disposition: attachment; filename=".$nome_excel.".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        print "$header\n$data";
    }
exit;
}
?>

<form name="frm_excel" method="post" action="<? echo $PHP_SELF ?>">
<table width='300' align='center' border='0' bgcolor='#797b7b' cellpadding='5' cellspacing='1' style='font-family: verdana; font-size: 10px; color:#FFFFFF'>
<tr>
<td><b>Nome relatório:</b>
<input type='text' name='nome_excel' size='60' maxlength='20' value=''>
</td>
</tr>
<tr>
<TD ><b>Sql:</b>
<TEXTAREA NAME='sql' ROWS='5' COLS='60'></TEXTAREA>
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
