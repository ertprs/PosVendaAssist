<?php

?>
<link href="/assist/imagens/tc_2009.ico" rel="shortcut icon">

<style>

body {
  text-align: center;
  font-family: Arial, Helvetica, sans-serif;
  margin: 0px;
  margin-top:5px;
  padding: 0px;

}

#footer {
  width: 100%;
  bottom: 0;
  position: relative;
}

a{
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12px;
  text-decoration: none;
  color: #000099;
  font-weight: bold;
}
a:hover{
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12px;
  color: #0066ff;
  font-weight: bold;
}

td,th {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12px;
}
h1{
  font-family: Arial, Helvetica, sans-serif;
  background-color: #0099CC;
  color: #FFFFFF
  /*#FFCC00*/

}
h6{
  font-family: Arial, Helvetica, sans-serif;
  background-color: #FFCC00;
  color: #FFFFFF

}
form{
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12px;
  font-weight: bold;
}


.frm {
  BORDER-RIGHT: #888888 1px solid;
  BORDER-TOP: #888888 1px solid;
  FONT-WEIGHT: bold;
  FONT-SIZE: 8pt;
  BORDER-LEFT: #888888 1px solid;
  BORDER-BOTTOM: #888888 1px solid;
  FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif;
  BACKGROUND-COLOR: #f0f0f0
}
.frm-on {
  BORDER-RIGHT: rgb(70,90,128) 1px solid;
  BORDER-TOP: rgb(70,90,128) 1px solid;
  FONT-WEIGHT: bold;
  FONT-SIZE: 8pt;
  BORDER-LEFT: rgb(70,90,128) 1px solid;
  COLOR: rgb(70,90,128);
  BORDER-BOTTOM: rgb(70,90,128) 1px solid;
  FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif;
  BACKGROUND-COLOR: #FFDF5E
}
.pontilhado{
  margin-bottom: 5px;
  margin-right:  5px;
  margin-left:   5px;
  padding:      10px;
  voice-family: "\"}\"";
  voice-family:inherit;
  border: 1px dotted silver;
}

.vermelho{
  font-family: Arial, Helvetica, sans-serif;
  color:#ff0000;
}

#menu ul {
  padding:0;
  margin: 0;
  background-color:#EDEDED;
  list-style:none;
  font:80% Tahoma;
  display: flex;
  flex-flow: row nowrap;
}

#menu ul li {display: inline-block; text-align: center; flex: 1;}

#menu ul li a {
  background-color:inherit;
  color: #333;
}

#menu ul li:hover {
  background-color:#0099CC;
  color: #fff;
}
#menu ul li:hover a {
  color: white;
}

#Cabeca{
  font-family: Verdana, sans-serif;
  font-size: 14pt;
  color: #FFFFFF;
  font-weight: bold;
}
</style>

<?
	if(strlen($login_unico)>0 ){
	echo "<table width='100%' id='Cabeca' cellspacing='0'><tr><td align='left' bgcolor='#879BC0'>&nbsp;DISTRIB - LOGIN ÚNICO</td> <td align='right' bgcolor='#879BC0'>$login_unico_nome | $login_unico_email | <a href='../login_unico_logout.php'>Sair</a>&nbsp;&nbsp;</td></tr></table>";
}
?>

	<div id="menu">
		<ul>
			<li><a href="index.php">MENU</a></li>
			<li><a href="../login_unico.php">LOGIN ÚNICO</a></li>
			<li><a href="estoque_consulta.php">CONSULTA ESTOQUE</a></li>
			<li><a href="embarque_geral_conferencia_novo.php">CONFERENCIA</a></li>
			<li><a href="conferencia_erp_sistema.php">CONFERENCIA ERP</a></li>
			<li><a href="nf_entrada.php">NF ENTRADA</a></li>
			<!--<li><a href="embarque.php">EMBARQUE</a></li> HD 7889 -->
			<li><a href="pendencia_posto.php">PENDENCIA POSTO</a></li>
		</ul>
	</div>
<?
$sql = "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
$res = pg_exec ($con,$sql);

$sql_master = "SELECT master from tbl_login_unico where login_unico = $login_unico";
$res_master = pg_query($con,$sql_master);
$master_login_unico = (pg_num_rows($res_master)>0) ? pg_fetch_result($res_master, 0, 'master') :'';

if ($master_login_unico == 'f'){


	echo "<center><font size='-2'>" . pg_result ($res,0,nome) . "</font></center>";

	$sql = "  SELECT programa
					FROM tbl_programa_restrito
				   WHERE tbl_programa_restrito.programa = '$PHP_SELF'";
		$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$programa = pg_fetch_result($res,0,programa); //HD 72857


			$sql = "SELECT programa
					FROM   tbl_programa_restrito
					JOIN   tbl_login_unico USING (login_unico)
					WHERE  tbl_programa_restrito.programa = '$PHP_SELF'
					AND    tbl_programa_restrito.login_unico    = $login_unico
					AND    tbl_programa_restrito.fabrica  is null ";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0) {
				echo "<p><hr><center><h1>*Sem permissão para acessar este programa</h1></center><p><hr>";
				include "rodape.php";
				exit;
			}

	}
}
?>

