<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';


include __DIR__.'/cabecalho_new.php';

$plugins = array(
);

include __DIR__.'/admin/plugin_loader.php';

$sql = "SELECT DISTINCT
            t.treinamento, 
            t.titulo, 
            TO_CHAR(t.data_inicio,'DD/MM/YYYY') AS data_inicio,
            TO_CHAR(t.data_fim,'DD/MM/YYYY')    AS data_fim,
            t.descricao,
            t.data_finalizado
        FROM tbl_treinamento t 
            JOIN tbl_treinamento_posto tp ON tp.treinamento = t.treinamento
            JOIN tbl_treinamento_tipo tt USING(treinamento_tipo)
        WHERE t.fabrica = $login_fabrica 
            AND t.data_finalizado IS NOT NULL
            AND tt.nome <> 'Palestra'
            AND tp.posto = {$login_posto};";
$res = pg_query($con,$sql);
$num = pg_num_rows($res);
?>

<body>
    <style type="text/css">
        #btn-voltar {
            background-color: white; 
            border-color: white; 
            color: #3a87ad;
            cursor: pointer;
            text-decoration: none;

        }
    </style>
	<div class="container-fluid">
		<div class="row-fluid">
            <?php if ($num > 0) { ?>
			<table class="table table-bordered table-striped" >
                    <thead>
                        <tr class="titulo_coluna" >
                            <th>Treinamento</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th width="60">Mais Informações</th>
                        </tr>
                    </thead>
                    <tbody>
                    	<?php
                    	while ($treinamento = pg_fetch_array($res)) {
                    		?>
                    		<tr>
								<td><?=$treinamento['titulo']?></td>
								<td class="tac"><?=$treinamento['data_inicio']?></td>
								<td class="tac"><?=$treinamento['data_fim']?></td>
								<td class="tac"><a href="visualiza_treinamento_finalizado.php?treinamento=<?=$treinamento['treinamento']?>" class="btn btn-default"><i class="icon-plus-sign"></i></a></td>
							</tr>
                    		<?php
                    	}
                    	?>                        
                    </tbody>
            </table>
            <?php } else { ?>
                <div class="alert alert-info" role="alert">
                    Nenhum Treinamento Finalizado Foi Encontrado. 
                </div>
            <?php } ?>
		</div>
        <div class="alert alert-light" role="alert" id="btn-voltar">
            <a href="treinamento_agenda.php"><< Voltar</a>
        </div>
	</div>
</body>


<?php
include "rodape.php";
?>