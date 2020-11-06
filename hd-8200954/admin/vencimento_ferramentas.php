<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'auditoria,cadastros';

include 'autentica_admin.php';

$layout_menu = 'auditoria';
$title       = 'Ferramentas';
$title_page  = 'Parâmetros de Pesquisa';

include 'cabecalho_new.php';

$plugins = array(
    'shadowbox',
    'select2'
);
include 'plugin_loader.php';
?>

</div>

<?php
$sqlVencimento = "
    SELECT pf.*, gf.descricao AS grupo_ferramenta_descricao, p.nome AS nome_posto, psf.codigo_posto, (pf.validade_certificado - CURRENT_DATE) AS dias_vencimento
    FROM tbl_posto_ferramenta pf
    INNER JOIN tbl_grupo_ferramenta gf ON gf.grupo_ferramenta = pf.grupo_ferramenta AND gf.fabrica = {$login_fabrica}
    INNER JOIN tbl_posto_fabrica psf ON psf.posto = pf.posto AND psf.fabrica = {$login_fabrica}
    INNER JOIN tbl_posto p ON p.posto = psf.posto
    WHERE pf.fabrica = {$login_fabrica}
    AND ((pf.validade_certificado - CURRENT_DATE) <= 60)
    AND pf.aprovado IS NOT NULL
    AND pf.ativo IS TRUE
    ORDER BY dias_vencimento ASC, pf.data_input ASC
";
$resVencimento = pg_query($con, $sqlVencimento);

if (pg_num_rows($resVencimento) > 0) {
?>
    <table class='table table-striped table-bordered table-hover table-normal table-center' >
        <thead>
            <tr class='error' >
                <th colspan='10' >Ferramentas com o certificado próximo do vencimento</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Descrição</th>
                <th>Grupo da Ferramenta</th>
                <th>Posto Autorizado</th>
                <th>Fabricante</th>
                <th>Modelo</th>
                <th>Número de Série</th>
                <th>Certificado</th>
                <th>Validade do Certificado</th>
                <th>Dias para Vencer</th>
                <th>Anexo(s)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = pg_fetch_object($resVencimento)) {
            ?>
                <tr>
                    <td><?=$row->descricao?></td>
                    <td><?=$row->grupo_ferramenta_descricao?></td>
                    <td><?=$row->codigo_posto." - ".$row->nome_posto?></td>
                    <td><?=$row->fabricante?></td>
                    <td><?=$row->modelo?></td>
                    <td><?=$row->numero_serie?></td>
                    <td><?=$row->certificado?></td>
                    <td><?=date("d/m/Y", strtotime($row->validade_certificado))?></td>
                    <td class='tac' ><?=($row->dias_vencimento > 0) ? $row->dias_vencimento : "<span class='label label-important'>Vencido</span>"?></td>
                    <td class='tac' nowrap >
                    <?php
                    $boxUploader = array(
                        'context' => 'ferramenta',
                        'titulo' => traduz('Ferramenta')." {$row->descricao} - ".traduz('Anexo(s)'),
                        'unique_id' => $row->posto_ferramenta,
                        'div_id' => $row->posto_ferramenta
                    );
                    include 'box_uploader_viewer.php';
                    ?>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
}
?>

<?php
include 'rodape.php';
?>
