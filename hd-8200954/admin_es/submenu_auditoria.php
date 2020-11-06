<?
echo "<table border='0' height='18' cellpadding='0' cellspacing='0' align='center' >";
echo "<tr align='center' style='font-face:arial ; font-size: 12px ; color: #000000' valign='top'>";


echo "<td onclick=\"javascript: window.location='posto_login.php'\" style='CURSOR: hand;' width='150' align='center'>";
echo "<acronym title='Registrarse en el sistema como el servicio autorizado'>";
echo "Logar";
echo "</acronym>";
echo "</td>";

echo "<td onclick=\"javascript: window.location='postos_usando.php'\" style='CURSOR: hand;' width='150' align='center'>";
echo "<acronym title='Servicios que utilizan actualmente el sistema'>";
echo "Servicios Utilizando";
echo "</acronym>";
echo "</td>";

echo "<td onclick=\"javascript: window.location='gasto_por_posto.php'\" style='CURSOR: hand;' width='150' align='center'>";
echo "<acronym title='Reporte de costos por servicio'>";
echo "Gastos";
echo "</acronym>";
echo "</td>";


echo "</tr>";
echo "</table>";

?>