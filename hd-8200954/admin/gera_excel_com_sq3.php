<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao)>0){
$nome_excel = $_POST['nome_excel'];
$sql   = $_POST['sql'];
    $select = str_replace("\\", "",$sql);

$select = "select tbl_peca.referencia,
	tbl_peca.descricao,
	SUM (tbl_pedido_item.qtde) as qtde,
	extract(month from tbl_pedido.data) AS mes
	FROM tbl_pedido
	JOIN tbl_pedido_item using (pedido) 
	JOIN tbl_peca USING(peca)
	WHERE tbl_pedido.fabrica = 51 
	AND tbl_pedido.posto <> 4311 
	AND tbl_pedido.data between 
	'2009-05-01' AND '2009-08-31'
group by 
tbl_peca.descricao,
tbl_peca.referencia,
mes
order by mes;";


$select = "select tbl_produto.referencia as referencia_produto,tbl_produto.descricao as descricao_produto,tbl_peca.referencia as referencia_peca,tbl_peca.descricao as descricao_peca,count(peca) as qtd_pecas from tbl_lista_basica join tbl_peca using(peca) join tbl_produto using(produto) where tbl_lista_basica.fabrica = 20 group by tbl_produto.referencia,tbl_produto.descricao,tbl_peca.referencia,tbl_peca.descricao having count(peca)>=2 order by tbl_peca.descricao";


$select = "select nome,contato_cidade,contato_estado, contato_fone_comercial,contato_email from tbl_posto join tbl_posto_fabrica using(posto) where contato_estado in ('RR',
'AP',
'AM',
'PA',
'TO',
'AC',
'RO',
'MT',
'MS',
'GO',
'DF',
'MG',
'ES',
'RJ',
'SP',
'PR',
'SC',
'RS') and fabrica = 42";

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
