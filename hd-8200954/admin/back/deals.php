<?

echo "Entrou { ".$_GET['cboprobaagilent']." }";
if (strlen($_GET['cboprobaagilent']) > 0) 
	$cboprobaagilent = $_GET['cboprobaagilent'];
?>

<html>
<head>
<title>Forecast Data Manager</title>
<body>
<Form Name="deals" Method="GET" >
  <div id="Layer1" style="position:absolute; left:9px; top:16px; width:586px; height:83px; z-index:1"> 
    <table width="98%" border="0">
    <tr> 
      <td>Deal&nbsp;:&nbsp;</td>
      <td>Entry Date&nbsp;:&nbsp;</td>
    </tr>
    <tr> 
      <td colspan="2">Account&nbsp;:&nbsp;</td>
    </tr>
    <tr> 
      <td height="24" colspan="2">Contact&nbsp;:&nbsp;</td>
    </tr>
  </table>
</div>
  <div id="Layer2" style="position:absolute; left:598px; top:16px; width:388px; height:84px; z-index:2">dados 
    do cliente</div>
  <div id="Layer3" style="position:absolute; left:15px; top:103px; width:589px; height:55px; z-index:3">
    <p>Area :&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
      <select name="cboarea" size=1>
      </select>
      <br>
      Lab Type :&nbsp;&nbsp; 
      <select name="cbolabtype" size=1>
      </select>
    <br>
	<hr>
	Purchase Date&nbsp;:&nbsp;
	<select name="cbopurchase" size=1>
	<option Value="Janeiro">Janeiro</option>
	<option Value="Fevereiro">Fevereiro</option>
	<option Value="Marco">Marco</option>
	<option Value="Abril">Abril</option>
	<option Value="Maio">Maio</option>
	<option Value="Junho">Junho</option>
	<option Value="Julho">Julho</option>
	<option Value="Agosto">Agosto</option>
	<option Value="Setembro">Setembro</option>
	<option Value="Outubro">Outubro</option>
	<option Value="Novembro">Novembro</option>
	<option Value="Dezembro">Dezembro</option>
	</select>
	<select name="cbopurchaseano" size=1>
	<option Value="2004">2004</option>
	<option Value="2005">2005</option>
	</select>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	Deal Value (US$K)&nbsp;:&nbsp;<input type="text" name="txtdealvalue" disabled size="8">
	<br>
	<br>
	<input type='hidden' name='btn_acao' value=''>

    <img src="images/salve.gif" border="0" ALT="Salve Deal" border="0" style="cursor:pointer;" onclick="javascript: if (document.deals.btn_acao.value == '' ) { document.deals.btn_acao.value='gravar' ; document.deals.submit() } else { alert ('teste') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
    <img src="images/insert_product.gif" border="0" ALT="Insert Product" border="0" style="cursor:pointer;">
    <img src="images/comments.gif" border="0" ALT="Open Deal Comments" border="0" style="cursor:pointer;">
    <img src="images/related.gif" border="0" ALT="Open Related Documents To This Deal" border="0" style="cursor:pointer;">
    <img src="images/print.gif" border="0" ALT="Print The Current Deal Information" border="0" style="cursor:pointer;">
    <img src="images/find.gif" border="0" ALT="Find a Deal In Teh List" border="0" style="cursor:pointer;">
	<hr>
	<div align="center">Products
	</div>
    
  </div>
<div id="Layer4" style="position:absolute; left:605px; top:104px; width:380px; height:130px; z-index:4">
  Nome do Vendedor
  <hr>
  <img src="images/trofeu.gif" border="0" ALT="Chance Deal Status To WON" border="0" style="cursor:pointer;">
  <img src="images/file.gif" border="0" ALT="File This Deal" border="0" style="cursor:pointer;">
  <img src="images/trofeu.gif" border="0" ALT="Chance Deal Status To LOST" border='0' style="cursor:pointer;">  
  <hr>
  <br>
  <br>
  <br>  
   Last Update&nbsp;:&nbsp; <br>
      Last Contact&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
      <input type="text" name="txtlastcontact" size="10">
      <br>
      Follow Up Date&nbsp;:&nbsp; 
      <input type="text" name="txtfollowupdate" size="10">
      <br>
      Mkt Event&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
      <input type="text" name="txtmkevent" size="20">
  </div>

   <br>
  <br>
  <br>
  <br>
    
  <hr>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>

<div id="Layer5" style="position:absolute; left:608px; top:220px; width:376px; height:61px; z-index:5">Probability(%) 
  <br>
  Agilent&nbsp;:&nbsp; 
  <select name="cboprobaagilent" size="1" >
    <option value="100" <? if (strlen($cboprobaagilent) == 0) echo 'selected' ?>>-</option>
    <option value="100"<? if ($cboprobaagilent == 100) echo 'selected' ?>>100</option>
    <option value="75" <? if ($cboprobaagilent == 75) echo 'selected' ?>>75</option>
    <option value="50" <? if ($cboprobaagilent == 50) echo 'selected' ?>>50</option>
    <option value="25" <? if ($cboprobaagilent == 25) echo 'selected' ?>>25</option>
  </select>
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Amount&nbsp;:&nbsp; 
  <select name="cboprobaamount" size="1"  >
    <option value="100"<? if ($cboprobaamount == 100) echo 'selected' ?>>100</option>
    <option value="75" <? if ($cboprobaamount == 75) echo 'selected' ?>>75</option>
    <option value="50" <? if ($cboprobaamount == 50) echo 'selected' ?>>50</option>
    <option value="25" <? if ($cboprobaamount == 25) echo 'selected' ?>>25</option>
  </select>
  <hr>
</div>  
</form>
</body>
</html>
