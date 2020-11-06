<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> New Document </TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
</HEAD>

<BODY>
<form name='frm_teste' action='<? echo $PHP_SELF ?>' method='post' >
<table width="500" border="0" cellpadding="0" cellspacing="0">
<tr>	
<td>Forma de pagamento: 
<select name="forma" size="1">
		<option value=''></option>
		<option value='cartao' >Cartão de Crédito</option>
		<option value='cheque' >Cheque</option>
</select>
</td>
<td>
<input type='submit' name='btn_acao' value='Escolher'>
</td>
</tr>

<?
$forma= $_POST['forma'];
if(strlen($btn_acao)>0){
	if($forma =='cheque'){
//QDO FOR CHEQUE, COLOCAR O CODIGO AQUI.... O QUE DEVE APARECER
echo"<tr>";
echo"<td>Opção escolhida foi cheque";
echo"</td>";
echo "</tr>";
	}else{

//QDO FOR CARTAO DE CRÉDITO, COLOCAR O CODIGO AQUI.... O QUE DEVE APARECER

		echo"<tr>";
echo"<td>Opção escolhida foi Cartao de crédito";
echo"</td>";
echo "</tr>";};

}
?>



</table>
</form>
</body>
</html>




