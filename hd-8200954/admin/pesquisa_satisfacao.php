<?php
/**
 * @author Brayan L. Rastelli
 * @description Pesquisa de satisfacao - HD 674943 - Apenas um modelo para usar no callcenter
 * @param Array $topicos - Colunas para as fabricas
 * Precisa ter jQuery na página em que irá incluir, NAO inclua nesse arquivo.
 * @todo Comentar includes de conexao ao por em produção
 */
/*	
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
*/ 
	$topicos[85]['A'] = 'A) AVALIAÇÃO DO CLIENTE EM RELAÇÃO AO TELEATENDIMENTO (FÁBRICA)';
	$topicos[85]['T'] = 'B) AVALIAÇÃO DO CLIENTE EM RELAÇÃO AO TÉCNICO';
	
?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 10px "Arial";
	color:#FFFFFF;
}
.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.nota{cursor:pointer;}
#pesquisa_satisfacao tbody, #sem_resposta {display:none;}
</style>

<div class="formulario">
	<table width="100%"id="pesquisa_satisfacao" class="tabela" cellspacing="1" cellpadding="0">
		<thead>
			<tr>
				<th colspan="3" id="ver_pesquisa" style="cursor:pointer;" class="titulo_tabela">Pesquisa de Satisfação do Cliente <img src=imagens/mais.bmp" id="pesquisa_satisfacao_img" alt="Ver" /></th>
			</tr>
		</thead>
		<tbody>
		<?php 
			
			foreach($topicos as $k => $v) : 
				foreach($v as $key => $value) :
					
					$cond = " AND sigla_relacao = '$key' ";
						
					echo '<tr>
							<td class="titulo_coluna" align="left" colspan="3">'.$value.'</td>
						  </tr>';
						
					$sql = "SELECT tbl_pergunta.descricao, tbl_tipo_pergunta.descricao as fator, pergunta
							FROM tbl_pergunta
							JOIN tbl_tipo_pergunta ON tbl_pergunta.tipo_pergunta = tbl_tipo_pergunta.tipo_pergunta AND tbl_tipo_pergunta.ativo
							JOIN tbl_tipo_relacao USING(tipo_relacao)
							WHERE tbl_pergunta.fabrica = $login_fabrica
							AND tbl_pergunta.ativo
							$cond
							ORDER BY tbl_pergunta.descricao;";
					$res = pg_query($con,$sql);
							
					for($i = 0; $i < pg_num_rows($res); $i++) {
					
						if ($i == 0) {
						
							echo '<tr class="titulo_coluna">
									<td>Fator</td>
									<td>Requisitos - (Cliente) Dê sua opinião sobre</td>
									<td>&nbsp;</td>
								  </tr>';
						
						}
					
						$pergunta = pg_result($res,$i,'pergunta');
					
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						
						if (empty($hd_chamado))
							$hd_chamado = $callcenter;
						
						if (!empty($hd_chamado)) {
							$sql2 = "SELECT nota
									 FROM tbl_resposta
									 WHERE hd_chamado = $hd_chamado
									 AND pergunta = $pergunta";
							$res2 = pg_query($con,$sql2);
							if(pg_num_rows($res2)) {
								$nota = pg_result($res2,0,'nota');
							}
							else $nota = null;
						}
						$respostas = array();
						
						for($j = 0; $j < 10; $j++) {
							$id_resp = $j + 1;							
							if (isset($_POST['nota_'.$pergunta]) && $_POST['nota_'.$pergunta] == $id_resp ) {
								$checked = ' checked="yes" ';
								$desmarcar = true;
							}
							else if ( !isset($_POST['nota_'.$pergunta]) ) {
								$checked = ($nota == $id_resp) ? ' checked="yes" ' : '';
							}
							else
								$checked= '';
							$respostas[] = '<input type="radio" class="nota" '.$checked.' id="nota_'.$pergunta.$id_resp.'" name="nota_'.$pergunta.'" value="' .$id_resp. '" /> <label class="nota" for="nota_'.$pergunta.$id_resp.'">' . $id_resp . '</label> &nbsp; ';
						}
						
						echo '<tr bgcolor="'.$cor.'">
								<td>'.pg_result($res,$i,'fator').'</td>
								<td>'.pg_result($res,$i,'descricao').'</td>
								<td style="min-width:380px;">
									'.implode(' ',$respostas).'
								</td>
							  </tr>';
					
					}

				endforeach;
			endforeach; 
		?>
		</tbody>
	</table>
	<?php
    	if (!empty($hd_chamado)) {

        	$sql = "SELECT resposta FROM tbl_resposta WHERE hd_chamado = $hd_chamado";
        	$res = pg_query($con,$sql);
			$total = pg_num_rows($res);

			$sql = "SELECT recusou_pesquisa, cliente_nao_encontrado FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
			$recusou = pg_result($res,0,0);
			$nao_enc = pg_result($res,0,1);
			if ($desmarcar !== true) {
				$recusou = $recusou == 't' ? ' checked ' : '';
				$nao_enc = $nao_enc == 't' ? ' checked ' : '';
			}

		}
        if ( $total == 0 || empty($hd_chamado) ) {
			if ( $_POST['sem_resposta'] == 'recusou_pesquisa' && $desmarcar !== true ) {
				$recusou = ' checked ';
				$nao_enc = '';
			}
			else if ( $_POST['sem_resposta'] == 'cliente_nao_encontrado' && $desmarcar !== true ) {

				$recusou = '';
				$nao_enc = ' checked ';

			}

	?>
			<table id="sem_resposta" style="padding:5px;">
				<tr>
					<td>
						<span><label for="recusou_pesquisa">Cliente se recusou a responder as perguntas</label></span>
					</td>
					<td>
						<input type="radio" <?=$recusou?> name="sem_resposta" value="recusou_pesquisa" class="opc_sem_pesquisa" id="recusou_pesquisa" />
					</td>
				</tr>
				<tr>
					<td>
						<span><label for="nao_encontrado">Cliente não encontrado</label></span>
					</td>
					<td>
						<input type="radio" name="sem_resposta" <?=$nao_enc?> value="cliente_nao_encontrado" id="nao_encontrado" class="opc_sem_pesquisa" />
					</td>
				</tr>
				<tr>
					<td align="center" colspan="2"><button id="responder_pesquisa" style="display:none;">Resetar</button></td>
				</tr>

	</table>
	<?php
		}
	?>
	
</div>

<!--  @todo Comentar include do jQuery quando por em produção -->
<!--<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>-->

<script type="text/javascript">
	
		var click = false;
	
		$("#ver_pesquisa").click(function () {
		
			if ( click === false ) {
			
				click = true;
				$("#pesquisa_satisfacao > tbody, #sem_resposta").show();
				$("#pesquisa_satisfacao_img").attr('src','http://ww2.telecontrol.com.br/manuallayout/menos.bmp');
			
			}
			else {
			
				click = false;
				$("#pesquisa_satisfacao > tbody, #sem_resposta").hide();
				$("#pesquisa_satisfacao_img").attr('src','http://ww2.telecontrol.com.br/manuallayout/mais.bmp');
			
			}
		
		});
		

		$().ready(function () {
		
			$("#nao_encontrado, #recusou_pesquisa").click(function(){
	
				$("#responder_pesquisa").show();
		
				$("input.nota").each(function () {
				
					$(this).attr('disabled','disabled');
			
				});
			
			});
		
		
				
			if ( $(".opc_sem_pesquisa:checked").val() !== undefined ) {
			
				$("#responder_pesquisa").show();

				$("input.nota").each(function () {
					
					$(this).attr('disabled','disabled');
				
				});
			
			}

			$("#responder_pesquisa").click(function(e){
			
				$(".opc_sem_pesquisa, .nota").each(function(){
				
					$(this).removeAttr('checked');
					$(this).removeAttr('disabled');

				});
				
				e.preventDefault();

			});
		
		}); 

</script>
