<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';


/* inicio exclusao de integridade */
	if (isset($_GET['excluir']) ) {
		$resBegin = pg_query($con,'BEGIN TRANSACTION');
		$id = (int) $_GET['excluir'];
		if(!empty($id)) {
		
			$sql = "Select tipo_atendimento from tbl_tipo_atendimento where tipo_atendimento=$id";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res)==0){

				$msg_erro = "O tipo de atendimento escolhido para exclusão não existe";

			}else{

				$sql = pg_query($con, "DELETE FROM tbl_tipo_atendimento WHERE tipo_atendimento = $id and fabrica=$login_fabrica" );
			
				$msg_erro = pg_errormessage($con);
			
			
			}
		}
		
		if ($msg_erro){
	
			$resBegin = pg_query($con,'ROLLBACK TRANSACTION');
	
		}else{

			$resBegin = pg_query($con,'COMMIT TRANSACTION');
			header("Location: $PHP_SELF?sucesso=ok");

		}
		
	}
/* fim exclusao */


$btn_acao = isset($_POST['btn_acao']);


//ATUALIZAÇÃO DOS DADOS DO RELACIONAMENTO - inicio
if ($btn_acao == 'atualizar'){

	$resBegin = pg_query($con,'BEGIN TRANSACTION');
	
	$sqlZ = "
			SELECT 	
					familia

			FROM 	tbl_familia

			WHERE	fabrica=$login_fabrica

			ORDER BY tbl_familia.descricao

	";
	$resZ = pg_query($con,$sqlZ);
	
	for ($i = 0; $i < pg_numrows($resZ); $i++)
	{
		
		
		$familia 	= pg_result($resZ,$i,'familia');
	
		$sqlA = "SELECT 	
				tbl_tipo_atendimento.tipo_atendimento,
				tbl_tipo_atendimento.descricao,
				tbl_tipo_atendimento.familia,
				tbl_tipo_atendimento.qtde_horas,
				tbl_tipo_atendimento.ordem,
				tbl_tipo_atendimento.meses_horas,
				tbl_tipo_atendimento.hora_maxima,
				tbl_tipo_atendimento.hora_minima,
				tbl_familia.descricao as desc_familia
	
				FROM	tbl_tipo_atendimento 
				
				JOIN 	tbl_familia using(familia)
	
				WHERE	tbl_familia.fabrica  = $login_fabrica 
				AND 	tbl_tipo_atendimento.fabrica  = $login_fabrica 
				AND     tbl_tipo_atendimento.familia=$familia
	
				ORDER BY tbl_tipo_atendimento.descricao";
		$resA = pg_query($con,$sqlA);
	
		for ($y = 0; $y < pg_numrows($resA); $y++)
		{
		
			//Recebe os dados da consulta
			$tipo_atendimento	= pg_result($resA,$y,'tipo_atendimento');
			$descricao			= pg_result($resA,$y,'descricao');
			$qtde_horas			= pg_result($resA,$y,'qtde_horas');
			$ordem 				= pg_result($resA,$y,'ordem');
			$meses_horas		= pg_result($resA,$y,'meses_horas');
			$hora_maxima		= pg_result($resA,$y,'hora_maxima');
			$hora_minima 		= pg_result($resA,$y,'hora_minima');
			$desc_familia       = pg_result($resA,$y,'desc_familia');
			$familia_tipo_atendimento = pg_result($resA,$y,'familia');
			
			//Recebe os dados do POST
			$x_familia						= $_POST[ 'familia_'.$familia ];
			$x_tipo_atendimento				= $_POST[ 'tipo_atendimento_'.$tipo_atendimento ];
			$x_tipo_atendimento_qtde_horas	= $_POST[ 'tipo_atendimento_qtde_horas_'.$tipo_atendimento ];
			$x_tipo_atendimento_ordem		= $_POST[ 'tipo_atendimento_ordem_'.$tipo_atendimento ];
			$x_tipo_atendimento_meses_horas = $_POST[ 'tipo_atendimento_meses_horas_'.$tipo_atendimento ];
			$x_tipo_atendimento_hora_maxima = $_POST[ 'tipo_atendimento_hora_maxima_'.$tipo_atendimento ];
			$x_tipo_atendimento_hora_minima = $_POST[ 'tipo_atendimento_hora_minima_'.$tipo_atendimento ];
			
			$ordem_diferente = ($ordem != $x_tipo_atendimento_ordem and strlen($x_tipo_atendimento_ordem)>0) ? "t" : "f";
			
			//Só irá fazer o update se os dados foram alterados
			if ($tipo_atendimento == $x_tipo_atendimento and $x_familia == $familia_tipo_atendimento )
			{
			
				$x_tipo_atendimento_qtde_horas  = (strlen($x_tipo_atendimento_qtde_horas)==0)  ? 'null' : $x_tipo_atendimento_qtde_horas;
				$x_tipo_atendimento_ordem  		= (strlen($x_tipo_atendimento_ordem)==0) 	   ? 'null' : $x_tipo_atendimento_ordem;
				$x_tipo_atendimento_meses_horas	= (strlen($x_tipo_atendimento_meses_horas)==0) ? 'null' : $x_tipo_atendimento_meses_horas;
				$x_tipo_atendimento_hora_maxima = (strlen($x_tipo_atendimento_hora_maxima)==0) ? 'null' : $x_tipo_atendimento_hora_maxima;
				$x_tipo_atendimento_hora_minima = (strlen($x_tipo_atendimento_hora_minima)==0) ? 'null' : $x_tipo_atendimento_hora_minima;
				
				//verifica se ja existe a ordem digitada para outra família
				$sql_ordem = "SELECT ordem from tbl_tipo_atendimento where familia = $x_familia and tipo_atendimento <> $x_tipo_atendimento and fabrica=$login_fabrica and ordem is not null order by ordem";
				
				$res_ordem = pg_query($con,$sql_ordem);
				
				if (pg_num_rows($res_ordem)>0){
					for ($z = 0;$z < pg_num_rows($res_ordem); $z++){
					
						$ordem_z = pg_result($res_ordem,$z,'ordem');
						if ($ordem_diferente == 't' and $ordem_z == $x_tipo_atendimento_ordem ){
							$msg_erro .= "A Ordem ($x_tipo_atendimento_ordem) Digitada ja existe na família '$desc_familia'<br /> ";
							break;
						}
						
					
					}
				}
				
				
				
				if (strlen($msg_erro)==0){
					
					$sqlUpdate = "
						UPDATE 	tbl_tipo_atendimento 

						SET qtde_horas  = $x_tipo_atendimento_qtde_horas,
							ordem       = $x_tipo_atendimento_ordem,
							meses_horas = $x_tipo_atendimento_meses_horas,
							hora_maxima = $x_tipo_atendimento_hora_maxima,
							hora_minima = $x_tipo_atendimento_hora_minima

						WHERE 	tipo_atendimento = $x_tipo_atendimento 
						AND 	fabrica 		 = $login_fabrica 
						AND 	familia 		 = $familia
					";

					$resUpdate = pg_query($con,$sqlUpdate);

					$msg_erro  .= pg_errormessage($con);

				}
			} // end if

		} // end for $y

	} // end for $i

	if (strlen($msg_erro)>0){
		$resBegin = pg_query($con,'ROLLBACK TRANSACTION');

	}else{

		$resBegin = pg_query($con,'COMMIT TRANSACTION');
		$_GET['sucesso'] = "Gravado com sucesso";
	
	}
	
}

//ATUALIZAÇÃO DOS DADOS DO RELACIONAMENTO - fim

$btn_acao2 = $_POST['btn_acao2'];
//INSERÇÃO DE NOVA INTEGRIDADE - inicio
if ($btn_acao2 == 'Gravar'){
	
	$resBegin = pg_query($con,'BEGIN TRANSACTION');
	
	$qtde_novos = $_POST['qtde_novo'];
	
	for ($i = 0; $i <= $qtde_novos; $i++)
	{
    
		$n_familia 		= ($_POST['novo_familia_'.$i])		? trim($_POST['novo_familia_'.$i]) 		: "null" ;
		$n_horas   		= ($_POST['novo_horas_'.$i])		? trim($_POST['novo_horas_'.$i]) 		: "null" ;
		$n_ordem 		= ($_POST['novo_ordem_'.$i])		? trim($_POST['novo_ordem_'.$i]) 		: "null" ;
		$n_meses_horas	= ($_POST['novo_meses_horas_'.$i])	? trim($_POST['novo_meses_horas_'.$i])	: "null" ;
		$n_hora_maxima	= ($_POST['novo_horas_maxima_'.$i])	? trim($_POST['novo_horas_maxima_'.$i])	: "null" ;
		$n_hora_minima	= ($_POST['novo_horas_minima_'.$i])	? trim($_POST['novo_horas_minima_'.$i])	: "null" ;
		
        if(trim($_POST['novo_tipo_os_'.$i])){
            $n_tipo_os 		= trim($_POST['novo_tipo_os_'.$i]) ;
        }else{
            $msg_erro = "Informe a descrição do tipo de OS na família {$n_familia}";
        }

        if(empty($msg_erro)){

            $sql_ins = "
                
                INSERT into tbl_tipo_atendimento (
                    
                    fabrica,
                    descricao,
                    familia,
                    qtde_horas,
                    ordem,
                    meses_horas,
                    hora_maxima,
                    hora_minima
                
                )values(
                
                    $login_fabrica,
                    '$n_tipo_os',
                    $n_familia,
                    $n_horas,
                    $n_ordem,
                    $n_meses_horas,
                    $n_hora_maxima,
                    $n_hora_minima
                )
                
            ";
            
            $res_ins = pg_query($con,$sql_ins);
            
            $msg_erro = pg_errormessage($con);
		}
	}

	if ($msg_erro){
	
		$resBegin = pg_query($con,'ROLLBACK TRANSACTION');
		
	}else{
		$resBegin = pg_query($con,'COMMIT TRANSACTION');
		$_GET['sucesso'] = "Gravado com sucesso";
	
	}

}

//INSERÇÃO DE NOVA INTEGRIDADE - fim

$title       = "Manutenção de Tipo de OS X Familia";
$layout_menu = 'cadastro';

include "cabecalho.php";
?>

<html>

<head>
	<style type="text/css">
	
		#tabela{display:none;}
		
		.formulario{
			background-color:#D9E2EF;
			font:11px Arial;
			text-align:left;
		}
		
		.msg_erro{
			background-color:#FF0000;
			font: bold 16px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
		.titulo_tabela{
			background-color:#596d9b;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
		.titulo_coluna{
			background-color:#596d9b;
			font: bold 11px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
		table.tabela tr td{
			font-family: verdana;
			font-size: 11px;
			border-collapse: collapse;
			border:1px solid #596d9b;
		}
		
		.sucesso{
			background-color:#008000;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
		.texto_avulso{
			font: 14px Arial; color: rgb(89, 109, 155);
			background-color: #d9e2ef;
			text-align: center;
			width:700px;
			margin: 0 auto;
			border-collapse: collapse;
			border:1px solid #596d9b;
		}

	</style>
	
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
	<script type="text/javascript">
		$(function(){
			$(".horas").numeric();
		});
		
		function addDefeito() {

				var tipo_os = $('#novo_tipo_os').val();
				var familia = $("#novo_familia").val();
				var horas = $("#novo_horas").val();
				var ordem = $("#novo_ordem").val();
				var meses_horas = $("#novo_meses_horas").val();
				var horas_minima = $("#novo_horas_minima").val();
				var horas_maxima = $("#novo_horas_maxima").val();
				
				var txt_tipo_os = $('#novo_tipo_os').find('option').filter(':selected').text();
				var txt_familia = $('#novo_familia').find('option').filter(':selected').text();

				var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";

				var htm_input;
				
				htm_input  = "<tr id='" +i+ "' bgcolor='" +cor+ "'>";
					
					htm_input += "<td align='center'><input type='hidden' value='" + tipo_os +"' name='novo_tipo_os_"+ i +"' / >" + txt_tipo_os +"</td>";
					htm_input += "<td align='center'><input type='hidden' value='" + familia + "' name='novo_familia_"+ i +"' / > " + txt_familia + "</td>";
					htm_input += "<td align='center'><input type='hidden' value='" + horas + "' name='novo_horas_"+ i +"' / > " + horas + "</td>";
					htm_input += "<td align='center'><input type='hidden' value='" + ordem + "' name='novo_ordem_"+ i +"' / > " + ordem + "</td>";
					htm_input += "<td align='center'><input type='hidden' value='" + meses_horas + "' name='novo_meses_horas_"+ i +"' / > " + meses_horas + "</td>";
					htm_input += "<td align='center'><input type='hidden' value='" + horas_minima + "' name='novo_horas_minima_"+ i +"' / > " + horas_minima + "</td>";
					htm_input += "<td align='center'><input type='hidden' value='" + horas_maxima + "' name='novo_horas_maxima_"+ i +"' / > " + horas_maxima + "</td>";
					htm_input += "<td align='center'><input type='button' onclick='deletaitem("+i+")' value='Remover'  / ></td>";
				
				htm_input += "</tr>";
				
				$("#qtde_novo").val(i);
				

				if (familia  === '') {
					alert('Escolha uma Família');
					return false;
				}
				
				if (tipo_os  === '') {
					alert('Escolha um tipo de os');
					return false;
				}		
				else {
					i++;
					$("#tabela").css("display","block");
					$(htm_input).appendTo("#integracao");
				}
				
			}

		function deletaitem(id) {

			$("#"+id).remove();

		}
			
		function deletaintegridade(id){
			
			if ( confirm("Deseja mesmo excluir essa integridade?") ){
				window.location.href = "?excluir="+id;
			}
			else{ 
				return false;
			}
	
		}
		
	</script>

</head>

<body>

<table class="texto_avulso" width="700px">
	<tr>
		<td>
			Para inserir tipos de OSs favor abrir um chamado no help desk.
		</td>
	</tr>
</table>

<br />

<? //Se gravou com sucesso... Exibe msg de gravado com sucesso.
if ($_GET['sucesso']){
?>

	<table class="sucesso" width="700px" align="center">
	
		<tr>
			
			<td>
			
				<? if ($_GET['sucesso']=='ok') {
					echo "Excluído com Sucesso";
					
				}else{
					echo $_GET['sucesso'];
				} ?>
			
			</td>
	
		</tr>
	
	</table>
	
<? //Se ocorreu algum erro... Exibe msg de erro
}else if ($msg_erro) {
?>

<table class="msg_erro" width="700px" align="center">
	
	<tr>
	
		<td>
		
			<? echo $msg_erro ?>
		
		</td>
		
	</tr>
	
</table>

<?
}
?>
<table class="formulario" width="700px" align="center" cellpadding="0" cellspacing="0">
	<tr>
		<td class="titulo_tabela">Cadastro de novo Relacionamento: Tipos de OS X Família</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td>
			<table width="600px" align="center" cellpadding="2" cellspacing="2">
				<tr class='titulo_coluna'>
					<th>Tipo OS</th>
					<th>Família</th>
					<th>Horas</th>
					<th>Ordem</th>
					<th>Meses/Horas</th>
					<th>Horas Mínima</th>
					<th>Horas Máxima</th>
					<th>Ações</th>
				</tr>
				<tr bgcolor='#FFFFCC'>
					<td align="center">
						<select name="novo_tipo_os" id="novo_tipo_os" clas="frm horas">
							<option value=""></option>
							
						<?
						
						$sql_tipo_os = "
							SELECT 	
								DISTINCT(tbl_tipo_atendimento.descricao) as descricao
				
							FROM 	tbl_tipo_atendimento 
				
							WHERE	fabrica=$login_fabrica
				
							ORDER BY tbl_tipo_atendimento.descricao 
						";
						$res_tipo_os = pg_query($con,$sql_tipo_os);
						for ($i = 0; $i < pg_num_rows($res_tipo_os); $i++)
						{
							$descricao_novo_tipo_os = pg_result($res_tipo_os,$i,'descricao');
						?>	
							<option value="<?=$descricao_novo_tipo_os?>"><?=$descricao_novo_tipo_os?></option>
						<?	
						}
						
						?>
						</select>
					</td>
					
					<td align="center">
						
						<select name="novo_familia" id="novo_familia" clas="frm horas">
							<option value=""></option>
							
						<?
						
						$sql_familia = "
							SELECT 	
								familia,descricao
				
							FROM 	tbl_familia
				
							WHERE	fabrica=$login_fabrica
				
							ORDER BY tbl_familia.descricao 
						";
						$res_familia = pg_query($con,$sql_familia);
						for ($x = 0; $x < pg_num_rows($res_familia); $x++)
						{
							$nova_familia = pg_result($res_familia,$x,'familia');
							$descricao_nova_familia = pg_result($res_familia,$x,'descricao');
						?>	
							<option value="<?=$nova_familia?>"><?=$descricao_nova_familia?></option>
						<?	
						}
						
						?>
						</select>
						
					</td>
					
					<td align="center">
						<input type="text" name="novo_horas" id="novo_horas" value='' class="frm horas" style="width:50px" />
					</td>
					
					<td align="center">
						<input type="text" id="novo_ordem" name="novo_ordem"  class="frm horas" style="width:50px" />
					</td>
					
					<td align="center">
						<input type="text" id="novo_meses_horas" name="novo_meses_horas" class="frm horas" style="width:50px" />
					</td>
					
					<td align="center">
						<input type="text" id="novo_horas_minima" name="novo_horas_minima" class="frm horas" style="width:50px"  />
					</td>
					
					<td align="center">
						<input type="text" id="novo_horas_maxima" name="novo_horas_maxima" class="frm horas" style="width:50px" />
					</td>
					<td align="center">
						<input type="button" value="Adicionar" onclick="addDefeito()" />
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td>
			
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
				<div id="tabela">
					<br />
					<table id="integracao" class="tabela" width="100%" cellspacing="1">
						<thead>
							<tr class="titulo_coluna">
								<th>Tipo OS</th>
								<th>Família</th>
								<th>Horas</th>
								<th>Ordem</th>
								<th>Meses/Horas</th>
								<th>Horas <br /> Mínima</th>
								<th>Horas <br /> Máxima</th>
								<th>Ações</th>
							</tr>
						</thead>
					</table>
					<input type="hidden" name="qtde_novo" id="qtde_novo" value='' />
					<center><input type="submit" value="Gravar" name="btn_acao2" /></center>
				</div>
			</form>
			
		</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
</table>

<br /><br />

<form method="post" name="frm_atualizar" action="<?=$PHP_SELF?>">


	<table class="formulario" width="700px" cellpadding="0" cellspacing="0" align="center" >

		<tr>
			<td class="titulo_tabela">Listagem dos Tipos de OS X Familia</td>
		</tr>
	
		<tr>
			<td>&nbsp;</td>
		</tr>
	
		<tr>
	
			<td>
		
				<table class="tabela" width="100%" cellpadding="0" cellspacing="1">
			
					<?php
					//Verifica os tipos de atendimentos na tabela para a fabrica
					$sqlX = "
						SELECT 	
								familia,
								descricao
					
						FROM 	tbl_familia
					
						WHERE	fabrica=$login_fabrica
					
						ORDER BY tbl_familia.descricao
					
					";
					$resX = pg_query($con,$sqlX);
				
					for ($i = 0; $i < pg_numrows($resX); $i++)

					{
					
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						$familia 	= pg_result($resX,$i,'familia');
						$descricao_familia 	= pg_result($resX,$i,'descricao');
					
						//monta HTML
						?>
						<tr class="titulo_coluna">
						
							<td>Família</td>
						
							<td colspan="7">&nbsp;</td>
							
						</tr>
						
						<tr style="background-color:<?=$cor?>">

							<td colspan="8" nowrap>

								<input type="hidden" name="familia_<?=$familia?>" value="<?=$familia?>" />
								<label style="font:bold 13px Arial !important;margin-left:5px"> <?echo $descricao_familia?> </label>

							</td>
							
							
						</tr>
						
						<tr class="titulo_coluna">
							
							<td style="border:0px solid;background-color:#D9E2EF">&nbsp;</td>
							
							<td>Tipo Atendimento</td>
							
							<td>Horas</td>
							
							<td>Ordem</td>
							<td>Meses/Horas</td>
							<td>Hora Mínima</td>
							<td>Hora Máxima</td>
							<td>Ações</td>
							
						</tr>
						
						<?
						
						$sqlY = "SELECT 	
								tbl_tipo_atendimento.tipo_atendimento,
								tbl_tipo_atendimento.descricao,
								tbl_tipo_atendimento.qtde_horas,
								tbl_tipo_atendimento.meses_horas,
								tbl_tipo_atendimento.hora_minima,
								tbl_tipo_atendimento.hora_maxima,
								tbl_tipo_atendimento.ordem
					
								FROM	tbl_tipo_atendimento 
								JOIN	tbl_familia using(familia)
					
								WHERE	tbl_familia.fabrica=$login_fabrica 
								AND 	tbl_tipo_atendimento.fabrica=$login_fabrica
								AND     tbl_tipo_atendimento.familia='$familia'
					
								ORDER BY tbl_tipo_atendimento.ordem";
							
						$resY = pg_query($con,$sqlY);
						
						for ($x = 0; $x < pg_numrows($resY); $x++)
						{
							
							
							$tipo_atendimento 	= pg_result($resY,$x,'tipo_atendimento');
							$qtde_horas			= pg_result($resY,$x,'qtde_horas');
							$descricao 			= pg_result($resY,$x,'descricao');
							$meses_horas 		= pg_result($resY,$x,'meses_horas');
							$hora_minima 		= pg_result($resY,$x,'hora_minima');
							$hora_maxima 		= pg_result($resY,$x,'hora_maxima');
							$ordem 				= pg_result($resY,$x,'ordem');
							
							$corY = ($x % 2) ? "#EEEEEE" : "#FFFFCC";
							
						?>
							<tr style="background-color:<?=$corY?>">
							
								<td width="15%" style="border:0px solid;background-color:#D9E2EF">
									<input type="hidden" name="tipo_atendimento_<?echo $tipo_atendimento?>" value="<?echo $tipo_atendimento?>"/>
									
									&nbsp;
								</td>
							
								<td align="left" width="60%">
									
									<label style="margin-left:5px"><?echo $descricao?></label>
									
								</td>
						
								<td align="center" width="10%">
							
									<input type="text" name="tipo_atendimento_qtde_horas_<?echo $tipo_atendimento?>" value="<? echo $qtde_horas ?>" class="frm horas" style="width:90%"/>

								</td>
								
								<td align="center">
								
									<input type="text" name="tipo_atendimento_ordem_<?echo $tipo_atendimento?>" value="<? echo $ordem ?>" class="frm horas" style="width:90%"/>
								
								</td>
								
								<td align="center">
									
									<input type="text" name="tipo_atendimento_meses_horas_<?echo $tipo_atendimento?>" value="<? echo $meses_horas ?>" class="frm horas" style="width:90%"/>
									
								</td>
								
								<td align="center">
								
									<input type="text" name="tipo_atendimento_hora_minima_<?echo $tipo_atendimento?>" value="<? echo $hora_minima ?>" class="frm horas" style="width:90%"/>
								
								</td>
								
								<td align="center">
								
									<input type="text" name="tipo_atendimento_hora_maxima_<?echo $tipo_atendimento?>" value="<? echo $hora_maxima ?>" class="frm horas" style="width:90%"/>
								
								</td>
								
								<td>
									<button onclick="deletaintegridade(<?=$tipo_atendimento?>)" type="button">Excluir</button>
								</td>
								
							</tr>
						<?
						}
						?>
						
						<tr>
							<td style="border:0px solid;background-color:#D9E2EF">&nbsp;</td>
						</tr>
						
					
					<?
					}
				
					?>
				
				</table>
			
			</td>
		
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td align="center">
				<input type="hidden" name="btn_acao" value="">
				<input type="button" value="Gravar" onclick="if(document.frm_atualizar.btn_acao.value == ''){ document.frm_atualizar.btn_acao.value='atualizar';document.frm_atualizar.submit()}else{alert('Aguarde submissão') }" alt="Gravar formulário" style="cursor:pointer">
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
	</table>

</form>

<? include "rodape.php";?>

</body>

</html>


