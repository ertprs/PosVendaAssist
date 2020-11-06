<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'autentica_usuario.php';

$lista = pg_copy_to($con,tbl_admin);

while (list ($id, $nome, $salario) = mysql_fetch_row ($resultado)) {
print (" <tr>\n".
" <td><a href="info.php3?id=$id">$nome</a></td>\n".
" <td>$salario</td>\n".
" </tr>\n");
}
?>