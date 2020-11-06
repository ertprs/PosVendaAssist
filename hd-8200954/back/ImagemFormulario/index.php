<?
session_start();
if(isset($_POST["submit"])){
   if($_POST["textimage"] != $_SESSION["valor"]){
      echo "<script>alert('Por favor , digite o que você vê escrito na imagem .')</script>";    
   }else{
      echo "<script>alert('OK, você digitou o texto corretamente .')</script>";   
   }
}

?>
<form method="post" action="<?=$_SERVER["PHP_SELF"];?>">
<table width="339" border="0">
  <tr>
    <td width="163"><img src="imagem.php" width="160" height="60"></td>
    <td width="166"><B>Digite no campo abaixo o que você vê escrito na imagem ao lado:</B></td>
  </tr>
  <tr>
    <td colspan="2">
      <input type="text" name="textimage"><input type="submit" name="submit" value="Submit"></td>
    </tr>
</table>
</form>