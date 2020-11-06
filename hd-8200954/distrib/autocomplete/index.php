<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head runat="server">
    <title>Untitled Page</title>
    <script type="text/javascript" src="javascripts/prototype.js"></script>
    <script type="text/javascript" src="javascripts/autocomplete.js"></script>
    <link rel="stylesheet" type="text/css" href="styles/autocomplete.css" /> 
    <style>
        * {
			font:12px "Segoe UI", Tahoma;	
        }
		h3 {
			font-size:16px;
			font-weight:bold;
		}
    </style>
</head>
<body>

<h3>[Demo1] Update Hidden Value</h3>
<table>
<tr>
<td>Hidden Value</td>
<td><input name="studentID" type="text" id="studentID" size="35"/></td>
</tr>
<tr>
<td>Typing Here</td>
<td><input type="text" name="studentName" size="50"/></td>
</tr>
</table>



<h3>[Demo2] Pagination & Complex Formatting</h3>
<form>
<table>
<tr>
			<td colspan='3' align='center' align='center'>
				<input id="fornID" name="fornID" type="hidden" value=''>
				aki <input type="text" id="fornName" name="fornName" value='' size="70" class='frm' >
				<script type="text/javascript">
					new CAPXOUS.AutoComplete("fornName", function() {
						return "../retorna_forn_ajax.php?typing=" + this.text.value;
					});
				</script>
			</td>

<td><input type="text" name="employeeName" size="24"/></td>
</tr>
</table>
</form>
<script type="text/javascript">
   
    new CAPXOUS.AutoComplete("employeeName", function() {
        return "autocomplete2.php?typing=" + this.text.value;
    });

</script>


</body>
</html>
