<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';

    include 'autentica_admin.php';

    $msg = "";
    $msg_erro = array();


    $title = "GERÊNCIA DE CHAT's DE POSTO";
    $layout_menu = "gerencia";

    include 'funcoes.php';

    $admin_privilegios = "call_center";

    include 'cabecalho_new.php';

    $plugins = array(                
        "mask",
        "dataTable"
    );

    include("plugin_loader.php");


	if ((count($msg_erro["msg"]) > 0) ) {
?>
		<div class="alert alert-error">
			<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
		</div>



<div class="container" style="">
    <table id="relatorio" class='table table-striped table-bordered table-hover' style="width: 100%;">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="7">Postos X Chat</th>
            </tr>            
            <tr class="titulo_coluna">
                <th>#</th>
                <th>Posto</th>
                <th>Ação</th>                
            </tr>
        </thead> 
        <tbody>
        <?php
			}

			#$sql = "SELECT p.posto, pf.codigo_posto, p.nome from tbl_posto p RIGHT JOIN tbl_posto_fabrica pf ON p.posto = pf.posto  where fabrica = $login_fabrica";
			
			#$res = pg_query($con, $sql);

			#$count = pg_num_rows($res);

			for ($i=0; $i < $count; $i++) { 
				$posto = pg_fetch_result($res, $i, "posto");
				$codigo_posto = pg_fetch_result($res, $i, "codigo_posto");
				$nome = pg_fetch_result($res, $i, "nome");
				?>
				<tr>
		        	<td><?=$posto?></td>
		        	<td><?=$codigo_posto?></td>
		        	<td><?=$nome?></td>	        	
	        	</tr>
				<?php	
			}			
		?>	        
        </tbody>        
    </table>
</div>