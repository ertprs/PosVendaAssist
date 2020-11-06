<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$os = $_POST["os"];

$sql_i = "SELECT 
			tbl_os_interacao.os_interacao,
		  	to_char(tbl_os_interacao.data,'DD/MM/YYYY HH24:MI') as data,
			tbl_os_interacao.comentario,
			tbl_os_interacao.interno,
			tbl_os.posto,
            tbl_posto_fabrica.contato_email as email,
			tbl_admin.nome_completo
		  FROM tbl_os_interacao
		  JOIN tbl_os            ON tbl_os.os    = tbl_os_interacao.os
		  JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
		  LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
		  WHERE tbl_os_interacao.os = $os
		  AND tbl_os.fabrica = {$login_fabrica}
		  ORDER BY tbl_os_interacao.os_interacao DESC";
$res_i  = pg_query($con, $sql_i);

if (pg_num_rows($res_i) > 0) {
?>
    <table border="0" cellspacing="1" cellpadding="0" style="width: 700px; margin: 0 auto;" >
        <thead>
            <tr>
                <th class="titulo">Nº</th>
                <th class="titulo">Data</th>
                <th class="titulo">Mensagem</th>
                <th class="titulo">Admin</th>
            </tr>
        </thead>
        <tbody>
			<?php
			$k = 1;

			while ($result_i = pg_fetch_array($res_i)) {
				if ($result_i["interno"] == 't') {
					$cor = "style='font-family: Arial; font-size: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
				} else {
					$cor = "class='conteudo'";
				}
				?>
                <tr>
                    <td width="25" <?=$cor?> ><?=$k?></td>
                    <td width="90" <?=$cor?> nowrap ><?=$result_i["data"]?></td>
                    <td <?=$cor?> ><?=$result_i["comentario"]?></td>
                    <td <?=$cor?> nowrap ><?=$result_i["nome_completo"]?></td>
                </tr>
			<?php
				$k++;
			}
			?>
        </tbody>
    </table>
<?php
}
?>