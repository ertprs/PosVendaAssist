<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include_once('plugins/fileuploader/TdocsMirror.php');

$tdocsMirror      = new TdocsMirror();

// Filtro $os
$os = $_GET['os'];

if ($fabricaFileUploadOS) {
    if (!empty($os)) {
        $tempUniqueId = $os;
        $anexoNoHash = null;
    } else if (strlen(getValue("anexo_chave")) > 0) {
        $tempUniqueId = getValue("anexo_chave");
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

// SELECT OS COM TODAS AS PROPRIEDADES NECESSÁRIAS
$sql = "
		SELECT dados.*,
			   CASE
			   	  WHEN dados.defeitos_constatados = '<br />'
			   	  THEN (
			   	  	SELECT tbl_defeito_constatado.descricao
			   	  	FROM tbl_os_defeito_reclamado_constatado
			   	  	LEFT JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			   	  	WHERE tbl_os_defeito_reclamado_constatado.os = :os
			   	  	LIMIT 1
			   	  )
			   	  ELSE dados.defeitos_constatados
			   END AS defeitos_os
		FROM (
			SELECT tbl_os.consumidor_nome,
				   tbl_os.serie,
				   tbl_os.defeito_reclamado_descricao,
				   TO_CHAR(tbl_os.data_abertura, 'dd/mm/yyyy') as data_abertura,
				   tbl_produto.referencia,
				   tbl_produto.descricao,
				   string_agg('<br />', tbl_defeito_constatado.descricao) as defeitos_constatados,
				   tbl_laudo_tecnico_os.observacao as solucao,
				   tbl_laudo_tecnico_os.laudo_tecnico_os
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			LEFT JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os
			LEFT JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
			LEFT JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			WHERE tbl_os.os = :os
			AND tbl_os.posto = :posto
			AND tbl_os.fabrica = :fabrica
			GROUP BY tbl_os.consumidor_nome,
				     tbl_os.serie,
				     tbl_os.defeito_reclamado_descricao,
				     tbl_os.data_abertura,
				     tbl_produto.referencia,
				     tbl_produto.descricao,
				     tbl_laudo_tecnico_os.observacao,
				     tbl_laudo_tecnico_os.laudo_tecnico_os
		) AS dados";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':os', $os);
$stmt->bindValue(':posto', $login_posto);
$stmt->bindValue(':fabrica', $login_fabrica);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);
// FIM DO SELECT DA OS

if ($data == false) {

	exit("OS não pertence ao posto ou fábrica");

}

// VERIFICA SE FOI SOLICITADO ALGUMA GRAVAÇÃO DA SOLUÇÃO
if( !empty($_POST['solucao']) ){
	$solucao = filter_input(INPUT_POST, 'solucao', FILTER_SANITIZE_STRING);

	// VERIFICA SE É UMA ATUALIZAÇÃO
	if(!empty($data['solucao'])){
		$sql = "UPDATE tbl_laudo_tecnico_os SET observacao = :observacao WHERE os = :os";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':observacao', $solucao, PDO::PARAM_STR);
		$stmt->bindValue(':os', $os, PDO::PARAM_INT);
		
		if($stmt->execute()){
			$data['solucao'] = $solucao;
			echo "<script type='text/javascript'> alert('Laudo atualizado') </script>";
		}

	}else{
		$titulo  = "Laudo de recusa HIKARI";

		$sql = "INSERT INTO tbl_laudo_tecnico_os (fabrica, titulo, os, observacao) VALUES (:fabrica, :titulo, :os, :observacao)";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':titulo', $titulo, PDO::PARAM_STR);
		$stmt->bindValue(':os', $os, PDO::PARAM_INT);
		$stmt->bindValue(':observacao', $solucao, PDO::PARAM_STR);
		$stmt->bindValue(':fabrica', $login_fabrica, PDO::PARAM_INT);

		if($stmt->execute()){
			echo "<script type='text/javascript'> 
					alert('Laudo gravado')
					window.location.href = 'laudo_recusa_hikari.php?os={$os}' 
				  </script>";
		}
	}

}
// FIM DA SOLICITAÇÃO DA GRAVAÇÃO DA SOLUÇÃO
?>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src='plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />
<link type="text/css" rel="stylesheet" href="admin/css/css.css">
<link type="text/css" rel="stylesheet" href="admin/css/tooltips.css">
<script type="text/javascript">
	$(function(){
		  Shadowbox.init();

		  $("#btnImprimir").click(function(){

		  	location.href = "laudo_recusa_hikari.php?os="+'<?= $os ?>'+"&print=true";

		  });

		  $("#btnSalvar").click(function(){

		  	$("#form_solucao").submit();

		  });

	});
</script>
<style>
	
	.float-img {
		float: left;
		margin: 20px;
	}

	@media print {

		@page {
            size: A4;
            margin: 5mm;
        }

		.float-img {
			float: left;
			max-width: 430px;
		}
		.floatr-img {
			float: right;
			max-width: 430px;
		}

	}

	 #tabela_principal {
		margin: 0 auto;
		width: 860px;
		border-collapse: collapse;
		border: 1px solid #000000;
		font-family: sans-serif;
		font-size: 12px;
	}

	#tabela_principal td{
		padding: 7.5px;
		width: 25%;
	}

	#logo_fabrica {
		background-color: 	#003878;
	}
	.label {
		width: 8%;
		font-weight: bolder;
	}
	.separador {
		border-collapse: collapse;
		border: solid 1px #000000;
	}
	.j_menu{
		width: 200px;
		display: flex;
		margin: 15px auto;
		justify-content: space-around;
	}
	.j_menu button{
		padding: 7px;
		margin-left: 15px;
	}
</style>

	<table id='tabela_principal'>
	<td id="logo_fabrica" colspan='4'>
		<img src="logos/logo_hikari.jpg" alt="Hikari" style="max-height:50px; max-width:180px; width:auto" />
	</td>
	</tr>
	<tr>
		<td>
			<strong>Número Laudo: </strong>  <?= $data['laudo_tecnico_os'] ?>
		</td>
		<td style="border-right: 1px black solid;"> 
			<strong>OS:</strong>
			<?= $os ?>
		</td>
	</tr>
	<tr>
		<td>
			<strong>Data:</strong>
			<?= $data['data_abertura'] ?>
		</td>
		<td style="border-right: 1px black solid;">
			<strong>Produto:</strong>
			<?= $data['referencia'] ?> - <?= $data['descricao'] ?>
		</td>
	</tr>
	<tr>
		<td>
			<strong>Cliente:</strong>
			<?= $data['consumidor_nome'] ?>
		</td>
		<td style="border-right: 1px black solid;">
			<strong>Série:</strong>
			<?= $data['serie'] ?>
		</td>
	</tr>
	<tr class="separador">
		<td colspan='2' style="border-right: 1px black solid;">
	</tr>
	<tr>
		<td colspan='2' style="border-right: 1px black solid;">
			<strong>Defeito Apresentado:</strong>
			<?= $data['defeito_reclamado_descricao'] ?>
		</td>
	</tr>
	<tr>
		<td colspan='2' style="border-right: 1px black solid;">
			<strong>Causa:</strong> 
			<?= $data['defeitos_os'] ?>
		</td>
	</tr>
	<tr>
		<td colspan='2' style="border-right: 1px black solid;">
			<strong>Solução:</strong><br>
			<form method="POST" action="laudo_recusa_hikari.php?os=<?=$os?>" id="form_solucao">
				<textarea name="solucao" id="solucao" style="width: 100%" rows="5"><?= $data['solucao'] ?? ''?></textarea>
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="border-right: 1px black solid;">
			<?php
			if (!isset($_GET['print'])) {
				if ($fabricaFileUploadOS) { ?>
					<div style="width: 70%;left: 15%;position: relative;">
						<?php
					    $boxUploader = array(
					        "div_id" 	=> "div_anexos",
					        "div_class" => "div_anexos",
					        "prepend" 	=> $anexo_prepend,
					        "context" 	=> "laudo_recusa",
					        "bootstrap" => false,
					        "unique_id" => $tempUniqueId,
					        "reference_id" => $os
					    );

				    	include "box_uploader.php";
				    	?>
			    	</div>
			    <?php
				}
			} else { ?>

				<table class="area">
					<?php
						$sqlUniqueId = "SELECT tdocs_id FROM tbl_tdocs WHERE referencia = 'laudo_recusa' and referencia_id = {$os}";
						$resUniqueId = pg_query($con, $sqlUniqueId);
						$contador = 0;
						while ($result = pg_fetch_object($resUniqueId)) {

							$info = $tdocsMirror->get($result->tdocs_id);
							 
							if ($contador == 0 || ($contador % 2) == 0) { $fim = false;?>
						
								<tr>
									<td>
										<img style="max-width: 450px;" src="<?= $info['link'] ?>" class="<?php echo 'float-img'; ?>" />
									</td>
						
					<?php } else { $fim = true; ?>
									
									<td>
										<img style="max-width: 450px;" src="<?= $info['link'] ?>" class="<?php echo 'float-img'; ?>" />
									</td>
								</tr>
				 	<?php } 
							$contador++;
						} 
						
						if (!$fim) echo "</tr>";
					?>
				</table>
			<?php
			} ?>
		</td>
	</tr>
</table>
<?php
if (!isset($_GET['print'])) {
?>
<div class='j_menu'>
	<button type="button" id="btnSalvar">Gravar</button>
</div>
<?php
} else { ?>
<script>window.print();</script>
<?php
}