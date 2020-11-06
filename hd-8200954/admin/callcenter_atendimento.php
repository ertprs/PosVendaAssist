<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "ACOMPANHAMENTO DE ATENDIMENTOS ABERTOS";

if ($_POST['liberar_admin']) {
    $atendente = $_POST['admin'];

    if (strlen($atendente) > 0) {
        pg_query($con, "BEGIN");

        $updateAdminAtendi = "UPDATE tbl_atendimento SET hd_chamado = NULL WHERE atendente = {$atendente} AND fabrica = {$login_fabrica};";
        $resUpAdminAtendi = pg_query($con, $updateAdminAtendi);

        if (strlen(pg_last_error()) == 0) {
            pg_query($con, "COMMIT");
            $return = array("success" => utf8_encode("Protocolo Liberado do Atendente!"));
        } else {
            pg_query($con, "ROLLBACK");
            $return = array("error" => utf8_encode("Ocorreu um erro na Liberação do Protocolo, tente novamente!"));
        }
    } else {
        $return = array("error" => utf8_encode("Ocorreu um erro na seleção do Admin, entre em contato com a Telecontrol!"));
    }
    echo json_encode($return);
    exit(0);
}

if ($_POST['atualizar_tabela']) {
    if (in_array($login_fabrica, array(169,170))) {
	$sqlAdminsAtivos = "
	    SELECT tbl_admin.admin, tbl_admin.nome_completo, tbl_atendimento.hd_chamado, tbl_admin.login, tbl_supervisor_atendente.supervisor
            FROM tbl_admin
            LEFT JOIN tbl_atendimento ON tbl_atendimento.atendente = tbl_admin.admin AND tbl_atendimento.fabrica = {$login_fabrica}
	    JOIN tbl_supervisor_atendente ON tbl_supervisor_atendente.supervisor = $login_admin AND tbl_supervisor_atendente.atendente = tbl_admin.admin
	    JOIN tbl_admin as admin_supervisor ON admin_supervisor.admin = tbl_supervisor_atendente.supervisor AND admin_supervisor.ativo IS TRUE AND admin_supervisor.callcenter_supervisor IS TRUE
            WHERE tbl_admin.fabrica = {$login_fabrica}
            AND tbl_admin.ativo IS TRUE
            ORDER BY tbl_admin.nome_completo ASC;
	";
	$resAdminsAtivos = pg_query($con, $sqlAdminsAtivos);

	$adminsAtivos = array();

	foreach(pg_fetch_all($resAdminsAtivos) as $admin) {
		if (!isset($adminsAtivos[$admin["supervisor"]])) {
			$adminsAtivos[$admin["supervisor"]] = array();
		}

		$adminsAtivos[$admin["supervisor"]][] = $admin;
        }

	$sqlAdminsAtivos = "
            SELECT tbl_admin.admin, tbl_admin.nome_completo, tbl_atendimento.hd_chamado, tbl_admin.login, tbl_supervisor_atendente.supervisor
            FROM tbl_admin
            LEFT JOIN tbl_atendimento ON tbl_atendimento.atendente = tbl_admin.admin AND tbl_atendimento.fabrica = {$login_fabrica}
            JOIN tbl_supervisor_atendente ON tbl_supervisor_atendente.supervisor NOT IN($login_admin) AND tbl_supervisor_atendente.atendente = tbl_admin.admin
	    JOIN tbl_admin as admin_supervisor ON admin_supervisor.admin = tbl_supervisor_atendente.supervisor AND admin_supervisor.ativo IS TRUE AND admin_supervisor.callcenter_supervisor IS TRUE
            WHERE tbl_admin.fabrica = {$login_fabrica}
            AND tbl_admin.ativo IS TRUE
            ORDER BY tbl_admin.nome_completo ASC;
        ";
        $resAdminsAtivos = pg_query($con, $sqlAdminsAtivos);

        foreach(pg_fetch_all($resAdminsAtivos) as $admin) {
                if (!isset($adminsAtivos[$admin["supervisor"]])) {
                        $adminsAtivos[$admin["supervisor"]] = array();
                }

                $adminsAtivos[$admin["supervisor"]][] = $admin;
        }

	$sqlSupervisor = "
		SELECT admin, nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND ativo IS TRUE AND callcenter_supervisor IS TRUE
	";
	$resSupervisor = pg_query($con, $sqlSupervisor);

	$supervisores = array();

	foreach (pg_fetch_all($resSupervisor) as $supervisor) {
		$supervisores[$supervisor['admin']] = $supervisor['nome_completo'];
	}

	$linha = 0;

	foreach ($adminsAtivos as $supervisor => $admins) {
		echo "
			<tr>
				<th class='titulo_coluna' colspan='4' style='background-color: #596d9b;' >SUPERVISOR ".$supervisores[$supervisor]."</th>
			</tr>
			<tr>
				<th colspan='4' >Atendentes/Chamados</th>	
			</tr>
			<tr>
				<td class='tac' >Login</td>
				<td class='tac' >Atendente</td>
				<td class='tac' >Chamado</td>
				<td class='tac' >Liberar</td>
			</tr>
		";

		foreach ($admins as $admin) {
			echo '<tr>
        	        	<input type="hidden" name="admin_'.$linha.'" value="'.$admin['admin'].'" />
	                	<td class="tac">'.$admin['login'].'</td>
               			<td class="tac">'.$admin['nome_completo'].'</td>
                		<td class="tal">'.$admin['hd_chamado'].'</td>
        	        	<td class="tac"><button class="btn btn-success" name="btn_liberar_admin" rel="'.$linha.'" '.$disabled[$linha].'>Liberar</button></td>
	                </tr>';
			$linha++;
		}

		echo "
			<tr><td colspan='4'>&nbsp;</td></tr>
		";
	}
    } else {
        $sqlAdminsAtivos = "
            SELECT admin, nome_completo, hd_chamado,login
            FROM tbl_admin
            LEFT JOIN tbl_atendimento ON tbl_atendimento.atendente = tbl_admin.admin AND tbl_atendimento.fabrica = {$login_fabrica}
            WHERE tbl_admin.fabrica = {$login_fabrica}
            AND ativo IS TRUE
            ORDER BY nome_completo ASC;
        ";

        $resAdminsAtivos = pg_query($con, $sqlAdminsAtivos);

        $adminsAtivos = pg_fetch_all($resAdminsAtivos);

        $return = "";
        foreach ($adminsAtivos as $linha => $admin) {
            $disabled[$linha] = (strlen($admin['hd_chamado']) == 0) ? "disabled" : "";
            $return .= '
                <tr>
                    <input type="hidden" name="admin_'.$linha.'" value="'.$admin['admin'].'" />
                    <td class="tac">'.$admin['login'].'</td>
                    <td class="tac">'.$admin['nome_completo'].'</td>
                    <td class="tal">'.$admin['hd_chamado'].'</td>
                    <td class="tac"><button class="btn btn-success" name="btn_liberar_admin" rel="'.$linha.'" '.$disabled[$linha].'>Liberar</button></td>
                </tr>
            ';
        }
    }

    echo $return;
    exit(0);
}

include "cabecalho_new.php";
?>

<script type="text/javascript">

    $(function() {
        $(document).on("click", "button[name=btn_liberar_admin]", function(){
            var linha = $(this).attr("rel");
            var admin = $("input[name=admin_"+linha+"]").val();
            var segundos = 3;

            $.ajax({
                url: 'callcenter_atendimento.php',
                type: 'POST',
                data: {liberar_admin: true, admin: admin}
            }).done(function(data) {
                data = $.parseJSON(data);
                if (data.error) {
                    $("#msg_erro").html("<h4>"+data.error+"</h4>");
                    $("#msg_erro").fadeIn();
                    setTimeout(function(){
                        $('#msg_erro').fadeOut();
                    }, segundos * 1000)
                } else {
                    $("#msg_success").html("<h4>"+data.success+"</h4>");
                    $("#msg_success").fadeIn();
                    setTimeout(function(){
                        $('#msg_success').fadeOut();
                    }, segundos * 1000)
                    refreshTable();
                }
            })
        });
    });

    function refreshTable() {
        var segundos = 3;
        $.ajax({
            url: 'callcenter_atendimento.php',
            type: 'POST',
            data: {atualizar_tabela : true}
        }). done(function(data) {
            $("#callcenter_atendimento tbody").html(data);
        });
    }

</script>

<div id="msg_erro" class="alert alert-error" style="display:none;"></div>
<div id="msg_success" class="alert alert-success" style="display:none;"></div>

<?
$sqlAdminsAtivos = "SELECT admin, nome_completo, hd_chamado, login
                                    FROM tbl_admin
                                    LEFT JOIN tbl_atendimento ON tbl_atendimento.atendente = tbl_admin.admin AND tbl_atendimento.fabrica = {$login_fabrica}
                                    WHERE tbl_admin.fabrica = {$login_fabrica}
                                    AND ativo IS TRUE
                                    ORDER BY nome_completo ASC;";

$resAdminsAtivos = pg_query($con, $sqlAdminsAtivos);
if (pg_num_rows($resAdminsAtivos) > 0) {
    $adminsAtivos = pg_fetch_all($resAdminsAtivos);
?>

<table id="callcenter_atendimento" class='table table-striped table-bordered table-large' >
    <?php
    if (!in_array($login_fabrica, array(169,170))) {
    ?>
        <thead>
            <tr class='titulo_coluna'>
                <th colspan="4">ATENDENTES / CHAMADOS</th>
            </tr>
            <tr>
                <th>Login</th>
                <th>Atendente</th>
                <th>Chamado</th>
                <th>Liberar</th>
            </tr >
        </thead>
    <?php
    }
    ?>
    <tbody>
    <? foreach ($adminsAtivos as $linha => $admin) { ?>
        <tr>
            <input type="hidden" name="admin_<?= $linha; ?>" value="<?= $admin['admin']; ?>" />
            <td class="tac"><?= $admin['login']; ?></td>
            <td class="tac"><?= $admin['nome_completo']; ?></td>
            <td class="tal"><?= $admin['hd_chamado']; ?></td>
            <td class="tac"><button class="btn btn-success" name="btn_liberar_admin" rel="<?= $linha; ?>" <?= (strlen($admin['hd_chamado']) == 0) ? "disabled" : ""; ?>>Liberar</button></td>
        </tr>
    <? } ?>
    </tbody>
</table>
<script>window.setInterval(refreshTable(), 30000);</script>
<br />
<?php }else{
?>
<div class="alert"><h4>Não foi localizado nenhum atendente relacionado</h4></div>
<?php } ?>
<? include "rodape.php" ?>
