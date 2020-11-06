<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (!count($msg_erro["msg"])) {
        $resSubmit = true;
        
        $sql = "
            SELECT 
                hce.nome, 
                hce.cpf, 
                c.nome AS cidade, 
                c.estado, 
                CASE WHEN hce.fone2 IS NULL THEN hce.fone ELSE hce.fone2 END AS telefone_celular,
                hce.email,
                pf.codigo_posto AS codigo_posto_credenciado,
                p.nome AS nome_posto_credenciado,
                hce.hd_chamado AS numero_atendimento,
                o.os AS numero_os,
                hcs.descricao AS classificacao,
                hml.descricao AS providencia
            FROM tbl_hd_chamado hc
            INNER JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado
            INNER JOIN tbl_cidade c ON c.cidade = hce.cidade
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = hce.posto AND pf.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto p ON p.posto = pf.posto
            INNER JOIN tbl_os o ON (o.hd_chamado = hc.hd_chamado OR hce.os = o.os) AND o.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_classificacao hcs ON hcs.hd_classificacao = hc.hd_classificacao AND hcs.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_motivo_ligacao hml ON hml.hd_motivo_ligacao = hce.hd_motivo_ligacao AND hml.fabrica = {$login_fabrica}
            WHERE hc.fabrica = {$login_fabrica}
            AND hc.fabrica_responsavel = {$login_fabrica}
            AND o.finalizada IS NOT NULL
            AND o.excluida IS NOT TRUE
            AND hcs.hd_classificacao = 185
            AND hml.hd_motivo_ligacao IN(374, 447)
            AND pf.posto NOT IN(6359)
            AND (hc.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59');
        ";
        $res_nps_rede = pg_query($con, $sql);

        $sql = "
            SELECT
                hce.nome, 
                hce.cpf, 
                c.nome AS cidade, 
                c.estado, 
                CASE WHEN hce.fone2 IS NULL THEN hce.fone ELSE hce.fone2 END AS telefone_celular,
                hce.email,
                pf.codigo_posto AS codigo_posto_credenciado,
                p.nome AS nome_posto_credenciado,
                hce.hd_chamado AS numero_atendimento,
                hcs.descricao AS classificacao,
                hml.descricao AS providencia
            FROM tbl_hd_chamado hc
            INNER JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado
            INNER JOIN tbl_cidade c ON c.cidade = hce.cidade
            LEFT JOIN tbl_posto_fabrica pf ON pf.posto = hce.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN tbl_posto p ON p.posto = pf.posto
            INNER JOIN tbl_hd_classificacao hcs ON hcs.hd_classificacao = hc.hd_classificacao AND hcs.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_motivo_ligacao hml ON hml.hd_motivo_ligacao = hce.hd_motivo_ligacao AND hml.fabrica = {$login_fabrica}
            WHERE hc.fabrica = {$login_fabrica}
            AND hc.fabrica_responsavel = {$login_fabrica}
            AND hcs.hd_classificacao IN(190, 184, 189, 183, 188, 186, 195, 194, 181, 182, 191, 193)
            AND (pf.posto IS NULL OR pf.posto NOT IN(6359))
            AND (hc.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')
            AND hc.status = 'Resolvido';
        ";
        $res_nps_sac = pg_query($con, $sql);
	}
}

$layout_menu = "callcenter";
$title = "Pesquisa NPS Tracksale";
include 'cabecalho_new.php';


$plugins = array(
	"datepicker",
    "mask"
);

include("plugin_loader.php");
?>

<script>

$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
});

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>

		<br />

		<div class='row-fluid'>
			<div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
		</div>

		<p>
            <br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p>

        <br />
</form>

<?php
if ($resSubmit) {
    if (pg_num_rows($res_nps_rede) > 0 || pg_num_rows($res_nps_sac) > 0) {
    ?>
        <br />

        <div class='row-fluid'>
			<div class='span2'></div>

            <?php
            if (pg_num_rows($res_nps_rede) > 0) {
                $nome_arquivo = "relatorio_nps_rede_".date("c").".csv";
                $arquivo = fopen("/tmp/{$nome_arquivo}", "w");

                fwrite($arquivo, "'nome';'cpf';'cidade';'uf';'telefone celular';'email';'código do posto credenciado';'nome do posto credenciado';'número do atendimento';'número da os';'classificação';'providência'\n");

                while ($row = pg_fetch_object($res_nps_rede)) {
                    fwrite($arquivo, "'{$row->nome}';'{$row->cpf}';'{$row->cidade}';'{$row->estado}';'{$row->telefone_celular}';'{$row->email}';'{$row->codigo_posto_credenciado}';'{$row->nome_posto_credenciado}';'{$row->numero_atendimento}';'{$row->numero_os}';'{$row->classificacao}';'{$row->providencia}'\n");
                }

                fclose($arquivo);
                system("mv /tmp/{$nome_arquivo} xls/{$nome_arquivo}");
                ?>
                <div class='span4'>
                    <button type="button" onclick="window.open('xls/<?=$nome_arquivo?>');" class="btn btn-success"><i class="icon-download icon-white" ></i> Download arquivo NPS REDE</button>
                </div>
            <?php
            }

            if (pg_num_rows($res_nps_sac) > 0) {
                $nome_arquivo = "relatorio_nps_sac_".date("c").".csv";
                $arquivo = fopen("/tmp/{$nome_arquivo}", "w");

                fwrite($arquivo, "'nome';'cpf';'cidade';'uf';'telefone celular';'email';'código do posto credenciado';'nome do Posto credenciado';'número do atendimento';'classificação';'providência'\n");

                while ($row = pg_fetch_object($res_nps_sac)) {
                    fwrite($arquivo, "'{$row->nome}';'{$row->cpf}';'{$row->cidade}';'{$row->estado}';'{$row->telefone_celular}';'{$row->email}';'{$row->codigo_posto_credenciado}';'{$row->nome_posto_credenciado}';'{$row->numero_atendimento}';'{$row->classificacao}';'{$row->providencia}'\n");
                }

                fclose($arquivo);
                system("mv /tmp/{$nome_arquivo} xls/{$nome_arquivo}");
                ?>
                <div class='span4'>
                    <button type="button" onclick="window.open('xls/<?=$nome_arquivo?>');" class="btn btn-success"><i class="icon-download icon-white" ></i> Download arquivo NPS SAC</button>
                </div>
            <?php
            }
            ?>
        </div>
    <?php
    }else{
    ?>
        <div class="container">
            <div class="alert">
                    <h4>Nenhum resultado encontrado</h4>
            </div>
        </div>
    <?php
    }
}

include 'rodape.php';