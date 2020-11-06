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
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet"
);

include __DIR__.'/admin/plugin_loader.php';





$treinamento = $_GET['treinamento'];

$sql = "SELECT t.treinamento, t.titulo, to_char(t.data_inicio,'DD/MM/YYYY') as data_inicio, to_char(t.data_fim,'DD/MM/YYYY') as data_fim, t.descricao, to_char(t.data_finalizado,'DD/MM/YYYY') as data_finalizado, 
		l.nome as linha ,
		c.nome as cidade
		FROM tbl_treinamento t		
		LEFT JOIN tbl_linha l USING(linha)
		LEFT JOIN tbl_cidade c USING(cidade)				
		WHERE t.treinamento = $1 AND t.fabrica = $2";


$res_treinamento = pg_query_params($con,$sql,array($treinamento,$login_fabrica));
$res_treinamento = pg_fetch_array($res_treinamento);


$sql = "SELECT tp.treinamento_posto, te.tecnico, te.nome, tp.aprovado, tp.nota_tecnico, tp.participou, to_char(tp.data_inscricao,'DD/MM/YYYY'), p.pesquisa, r.resposta
		FROM tbl_treinamento_posto tp
		JOIN tbl_treinamento t USING(treinamento)
		LEFT JOIN tbl_tecnico te USING(tecnico)
		LEFT JOIN tbl_pesquisa p USING(treinamento)
		LEFT JOIN tbl_resposta r ON r.tecnico = te.tecnico AND r.pesquisa = p.pesquisa
		WHERE t.treinamento = $1 AND t.fabrica = $2 AND tp.posto = $3
		";
$res_treinamento_posto = pg_query_params($con,$sql,array($treinamento, $login_fabrica, $login_posto));
?>

<body>
	<div class="container-fluid">
		<div class="row-fluid">
			<div class="span12">
				<div class="hero-unit">
					<div class="row">
						<div class="span12">
					  		<h3><?=$res_treinamento['titulo']?></h3>
					  	</div>
					</div>
				  	<div class="row">
					  	<div class="span6">
					  		<b>Inicio:</b>
					  		<p><?=$res_treinamento['data_inicio']?></p>				  		
					  	</div>
					  	<div class="span6">
					  		<b>Fim:</b>
					  		<p><?=$res_treinamento['data_fim']?></p>				  		
					  	</div>				  	
				  	</div>				  
				  	<div class="row">
				  		<div class="span6">
				  			<b>Linha:</b>
					  		<p><?=$res_treinamento['linha']?></p>				  		
				  		</div>
				  		<div class="span6">
				  			<b>Local:</b>
					  		<p><?=$res_treinamento['cidade']?></p>				  		
				  		</div>
				  	</div>
				  	<div class="row">
					  	<div class="span6">
					  		<b>Descricao:</b>
					  		<p><?=$res_treinamento['descricao']?></p>				  		
					  	</div>
					  	<div class="span6">
					  		<b>Resultados liberados em: </b>
					  		<p><?=$res_treinamento['data_finalizado']?></p>				  		
					  	</div>				  	
				  </div>				  
				</div>
			</div>	
		</div>
		<div class="row-fluid">
			<table class="table table-bordered table-striped" >
                    <thead>
                        <tr class="titulo_coluna" >
                            <th>Técnico</th>
                            <th>Participou?</th>
                            <th>Aprovado?</th>
                            <th>Nota</th>                            
                            <th width="60">Download do Certificado</th>
                        </tr>
                    </thead>
                    <tbody>
                    	<?php
                    	while ($treinamento_posto = pg_fetch_array($res_treinamento_posto)) {
                    		?>
                    		<tr>
								<td><?=$treinamento_posto['nome']?></td>
								<?php  if (in_array($login_fabrica, array(169,170))){ ?>
                                        <td class="tac"><?php echo ($treinamento_posto['participou'] == 't') ? "<i class='icon-ok-sign'></i>":"<i class='icon-remove-sign'></i>"?></td>
                                <?php }else{ ?>
                                        <td class="tac"><?=$treinamento_posto['participou']? "<i class='icon-ok-sign'></i>":"<i class='icon-remove-sign'></i>"?></td>
                                <?php } ?>
                                <?php  if (in_array($login_fabrica, array(169,170))){ ?>
                                        <td class="tac"><?php echo ($treinamento_posto['aprovado'] == 't') ? "<i class='icon-ok-sign'></i>":"<i class='icon-remove-sign'></i>"?></td>
                                <?php }else{ ?>
                                        <td class="tac"><?=$treinamento_posto['aprovado']? "<i class='icon-ok-sign'></i>":"<i class='icon-remove-sign'></i>"?></td>
                                <?php } ?>
								<td class="tac"><?=$treinamento_posto['nota_tecnico']?></td>								
								<td class="tac"><button type="button" data-treinamento="<?=$res_treinamento['treinamento']?>" data-tecnico="<?=$treinamento_posto['tecnico']?>" class="btn btn-default btn-open-cert-modal"><i class="icon-plus-sign"></i></button></td>
							</tr>
                    		<?php
                    	}
                    	?>                        
                    </tbody>
                </table>
		</div>
	</div>
</body>


<script type="text/javascript">
	

	$(function(){
		Shadowbox.init();

		$(".btn-open-cert-modal").click(function(){

			var treinamento  = $(this).data("treinamento");
			var tecnico  = $(this).data("tecnico");

			Shadowbox.open({
                content: "visualiza_certificado_pesquisa.php?treinamento="+treinamento+"&tecnico="+tecnico,
                player: 'iframe',
                width: 1024,
                height: 600
            });
		});
	});
</script>



<?php
include "rodape.php";
?>
