 <?
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
$layout_menu = "callcenter";
include "cabecalho.php";
?>
<script type='text/javascript'>
function fnc_pesquisa_produto2 (campo, campo2, tipo, mapa_linha) {
	var xcampo = null;
	if (tipo == "tudo" ) {
		var xcampo = campo;
	}
	
	
	

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.produto 		= document.getElementById( 'produto' );
		janela.focus();
	}else{
			alert( 'Informe parte da informação para realizar a pesquisa!' );
		
		}
}
</script>
<style>
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

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
}
</style>

<?

if($_GET['msg']){
	$msg = $_GET[ 'msg' ];
	var_dump($msg);
}

if ($_POST['btn_acao']) {

	$hd_chamado_alert = $_POST['hd_chamado_alert'];

	if (strlen($hd_chamado_alert)==0) {
		$msg_erro = 'Chamado Inválido';
	}

		########################################################################
		//Rotinas de inserção
		$res = pg_exec ($con,"BEGIN TRANSACTION;");
		
		if( strlen( $msg_erro ) > 0)
		{
			$msg_erro = pg_errormessage($con);
		}
		
		
	$sql = "INSERT INTO tbl_hd_chamado (
       hd_chamado            ,
       admin                 ,
       cliente_admin         ,
       data                  ,
       status                ,
       atendente             ,
       fabrica_responsavel   ,
       titulo                ,
       categoria             ,
       fabrica
      )values(
       DEFAULT                 ,
       2977                    ,
       3890                    ,
       current_timestamp       ,
       'Aberto'                ,
       2977                    ,
       30                      ,
       'Atendimento interativo',
       'reclamacao_produto'    ,
       30) RETURNING hd_chamado";

//echo nl2br($sql) . '<br />';

        $res_chamado = pg_exec($con,$sql);
        
        if( strlen( $msg_erro ) > 0)
		{
			$msg_erro = pg_errormessage($con);
		}
        
        
	    $hd_chamado = pg_result($res_chamado,0,0 );
		$hd_chamado_alert 	= $_POST[ 'hd_chamado_alert' ];
		$admin				= $_POST[ 'admin' ];
		$nome				= $_POST[ 'tinclinome' ];
		$sobrenome			= $_POST[ 'tinclisobrenome' ];
		$fone				= $_POST[ 'tinclifone1' ];
		$email				= $_POST[ 'tincliemail' ];
		$cidade				= $_POST[ 'tinclicidade' ];
		$estado				= $_POST[ 'tinestado' ];
		$endereco			= $_POST[ 'tinendereco' ];
		$numero				= $_POST[ 'tinnumero' ];
		$bairro				= $_POST[ 'tinbairro' ];
		$complemento		= $_POST[ 'tincomplemento' ];
		$cep				= $_POST[ 'tincep' ] ;
		$rg_freezer			= $_POST[ 'tinrgfreezer' ];
		$sintomas			= $_POST[ 'tinsintomas' ];
		$tincaso			= $_POST[ 'tincaso' ];
		$produto = $_POST['produto'];
		if( strlen( trim($rg_freezer) ) == 0 )
		{
			$rg_freezer = 'null';
		}

                $sql_cidade = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
                $res_cidade = pg_query($con, $sql_cidade);

                if(pg_num_rows($res_cidade) == 0){
                     $sql_cidade = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
                     $res_cidade = pg_query($con, $sql_cidade);

                     if(pg_num_rows($res_cidade) > 0){
                        $cidade = pg_fetch_result($res_cidade, 0, 'cidade');
                        $estado = pg_fetch_result($res_cidade, 0, 'estado');

			$sql = "INSERT INTO tbl_cidade(nome, estado)values(upper('$cidade'),upper('$estado'))";
		        $res = pg_query($con,$sql);
                	$msg_erro .= pg_errormessage($con);
	                $res    = pg_query ($con,"SELECT CURRVAL ('seq_cidade')");
        	        $id_cidade = pg_fetch_result ($res,0,0);
                    }
                }else{
                        $id_cidade = pg_fetch_result($res_cidade, 0, 'cidade');
                     }

     $sqlins = "INSERT INTO tbl_hd_chamado_extra (
           hd_chamado,
           produto,
           nome,
           endereco,
           numero,
           bairro,
           cep,
           fone,
           cidade)
           VALUES (
           $hd_chamado,
           $produto,
           '$nome',
           '$endereco',
           '$numero',
           '$bairro',
           '$cep',
           '$fone',
           '$id_cidade')";

     $resins = pg_exec ($con,$sqlins);
     if( strlen( $msg_erro ) > 0){
			$msg_erro .= pg_errormessage($con);
		}

//echo nl2br($sqlins)  . '<br />';
	if (strlen($msg_erro)==0) {
			$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado_item,
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								produto      ,
								serie        ,
								defeito_reclamado_descricao,
								status_item,
								tincaso
								)values(
								DEFAULT                           ,
								$hd_chamado                       ,
								current_timestamp                 ,
								'Insercao de Produto para Os' ,
								$login_admin                      ,
								't'                              ,
								'$produto'                       ,
								'$rg_freezer'                          ,
								'$sintomas'                ,
								'Aberto',
								'$tincaso'
								)
								RETURNING hd_chamado_item";
		$res_item = pg_exec( $sql );
		if (strlen( $msg_erro) > 0){
			$msg_erro .= pg_errormessage($con);
		}
//echo nl2br($sql)  . '<br />';
################### fim da inserção
		
		if (strlen( $msg_erro) > 0){
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				$msg_erro = pg_errormessage($con);
				echo "<script language='javascript'>
				window.location = '$PHP_SELF?msg=$msg_erro';
				</script>";
		}else{
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$sql = "UPDATE tbl_hd_chamado_alert set admin = $login_admin where hd_chamado_alert = $hd_chamado_alert";
			$res = pg_exec($con,$sql);
			//var_dump($msg_erro);
			echo "<script language='javascript'>
				window.location = '$PHP_SELF?msg=Gravado com Sucesso!';
			</script>";
		}
} 
}

if (strlen($_GET['hd_chamado_alert'])>0) {
	$hd_chamado_alert = $_GET['hd_chamado_alert'];
	$sql = "SELECT *from tbl_hd_chamado_alert where hd_chamado_alert = $hd_chamado_alert";
	$res = pg_exec($con,$sql);

	if (pg_num_rows($res)>0) {
		
		$num_field = pg_num_fields($res); 
		
		$colunas = "4";

		if ($num_field>0) {
		echo "<form method='post' name='frm_principal' action=''>";
		echo "<table width='100%' align='center' class='formulario'>";
			for($i=0;$i<pg_num_fields($res);$i++) {
				
				$valor = pg_result($res,0,$i);
				$label = pg_field_name($res,$i);
				$label2 = pg_field_name($res,$i);

				$label = str_replace('tin','',$label);
				$label = str_replace('_',' ',$label);
				$label = str_replace('cli','Cliente ',$label);
				

				if (($i%$colunas)==0) {
					echo "</tr><tr valign='top'>";
				}

				echo "	<td width='300' align='left'><div class='titulo_tabela' width='100%'>$label</div><br>";
				if (strlen($valor)<=50) {
					
					/*if( $label2 == 'tinmodelofreezer' )
					{
						$readonly = '';
					}
					else{
						$readonly = "readonly = true";
						}
					*/
					echo "<input type='text' name='$label2' value='$valor' id='$label2' size='40' class='frm' $readonly>";
					if( $label2 == 'tinmodelofreezer' )
					{
						echo "<input type='hidden' name='produto' id='produto' />";
						echo "<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick=\"javascript: fnc_pesquisa_produto2 (document.frm_principal.$label2,document.frm_principal.$label2,'tudo')\">";
					}
					echo "</td>";
				} else {
					echo "<textarea rows='10' cols='35' class='frm' readonly='true'>$valor</textarea>";
				}
				
			}
		}
		echo "<tr><td colspan='4'><input type='submit' value='Confirmar' name='btn_acao'></td></tr>";
		echo "</table></form>";
	}
}

if ($login_admin <> '3033') {
	$sql = "SELECT *,to_char(data_leitura,'dd/mm/yyyy') as data_leitura2 from tbl_hd_chamado_alert where admin is null";

	$res = pg_exec($con,$sql);

	if (pg_num_rows($res)>0){
		echo"<br>";
		echo "<table class='tabela' cellpading='3' cellspacing='1' align='center' width='700px'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>Data/Hora</td>";
		echo "<td>Cliente Nome</td>";
		echo "<td>Cidade</td>";
		echo "<td>UF</td>";
		echo "</tr>";

		for ($i=0;$i<pg_num_rows($res);$i++) {

			$hd_chamado_alert  = pg_result($res,$i,'hd_chamado_alert');
			$data_hora         = pg_result($res,$i,'data_leitura2');
			$cliente_nome      = pg_result($res,$i,'tinclinome');
			$cidade            = pg_result($res,$i,'tinclicidade');
			$estado            = pg_result($res,$i,'tinestado');

			$cor = ($i%2)  ? "#F7F5F0" : "#F1F4FA";

			echo "<tr onclick='javascript:window.location = \"$PHP_SELF?hd_chamado_alert=$hd_chamado_alert\"' style='cursor:pointer' bgcolor=$cor>";
			echo "<td>$data_hora</td>";
			echo "<td>$cliente_nome</td>";
			echo "<td>$cidade</td>";
			echo "<td>$estado</td>";
			echo "</tr>";
			
		}
	echo '</table>';
	}
	?>
	<?php
		################TABELA DOS ERROS DE INTEGRAÇÃO QUE FORAM CORRIGIDOS#######################

		$sql = "SELECT tbl_hd_chamado_item.hd_chamado,tbl_hd_chamado_item.hd_chamado_item, to_char(tbl_hd_chamado_item.data,'dd/mm/yyyy') as data_abertura, tbl_hd_chamado_extra.nome, 
					   tbl_cidade.nome as cidade, tbl_cidade.estado, tbl_hd_chamado_item.tincaso
				FROM tbl_hd_chamado_item 
				JOIN tbl_hd_chamado USING(hd_chamado)
				JOIN tbl_hd_chamado_extra on tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				WHERE tbl_hd_chamado.fabrica = $login_fabrica and tbl_hd_chamado_item.tincaso IS NOT NULL AND tbl_hd_chamado_item.os IS NULL";
		$res = pg_exec( $con,$sql );
		if( pg_num_rows( $res ) >0 ) {?>
	<br />
	<table class='tabela' width='700px' align='center'>
		<tr class='subtitulo'>
			<td colspan='5'>Erros de integração corrigidos, sem ordem de serviço</td>
		</tr>
		<tr class='titulo_coluna'>
			<td>HD Chamado</td>
			<td>Abertura</td>
			<td>Consumidor</td>
			<td>Cidade</td>
			<td>UF</td>
		</tr>
		
		<?php
			for( $i=0; $i< pg_num_rows($res);  $i++ ) {
				$hd_chamado 		= pg_result($res,$i,'hd_chamado');
				$hd_chamado_item  	= pg_result($res,$i,'hd_chamado_item');
				$abertura			= pg_result($res,$i,'data_abertura');
				$consumidor			= pg_result($res,$i,'nome');
				$cidade				= pg_result($res,$i,'cidade');
				$uf					= pg_result($res,$i,'estado');
				$tincaso			= pg_result($res,$i,'tincaso');
				
				$cor = ($i%2) ? "#F7F5F0" : "#F1F4FA";
		?>
		<tr bgcolor="<?=$cor?>">
			<td><a href='pre_os_cadastro_sac_esmaltec.php?callcenter=<?=$hd_chamado?>&categoria=reclamacao_produto&tincaso=<?=$tincaso?>&hd_chamado_item=<?=$hd_chamado_item?>'><?=$hd_chamado ?></a></td>
			<td><?=$abertura?></td>
			<td><?=$consumidor?></td>
			<td><?=$cidade?></td>
			<td><?=$uf?></td>
		</tr>
		<?php }   ?>
	</table>
	<?php
		}
}
?>


<?php
	################TABELA OS SEM ATENDIMENTO OU FINALIZACAO#######################

	$sql = "SELECT	tbl_os.os,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_produto.referencia,
					tbl_produto.descricao,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data_digitacao,
					to_char(tbl_os.visita_agendada,'DD/MM/YYYY') as visita_agendada,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_estado
			FROM tbl_os
			JOIN tbl_hd_chamado_item on tbl_hd_chamado_item.os = tbl_os.os
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.finalizada isnull
			AND tincaso is not null";

	$res = pg_exec( $con,$sql );
	if( pg_num_rows( $res ) >0 ) {?>
<br />
<table class='tabela' width='700px' align='center'>
	<tr class='subtitulo'>
		<td colspan='7'>Ordens de Serviço AMBEV não finalizadas</td>
	</tr>
	<tr class='titulo_coluna'>
		<td>OS</td>
		<td>Data Digitação</td>
		<td>Posto</td>
		<td>Produto</td>
		<td>Data Agendamento</td>
		<td>Cidade Cliente</td>
		<td>Estado Cliente</td>
	</tr>
	
	<?php
		for( $i=0; $i< pg_num_rows($res);  $i++ ) {
			$os			 		= pg_result($res,$i,'os');
			$nome				= pg_result($res,$i,'nome');
			$codigo_posto		= pg_result($res,$i,'codigo_posto');
			$data_digitacao		= pg_result($res,$i,'data_digitacao');
			$consumidor_cidade	= pg_result($res,$i,'consumidor_cidade');
			$consumidor_estado	= pg_result($res,$i,'consumidor_estado');
			$visita_agendada	= pg_result($res,$i,'visita_agendada');
			$referencia			= pg_result($res,$i,'referencia');
			$descricao			= pg_result($res,$i,'descricao');
			
			$cor = ($i%2) ? "#F7F5F0" : "#F1F4FA";
	?>
	<tr bgcolor="<?=$cor?>">
		<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os ?></a></td>
		<td><?=$data_digitacao;?></td>
		<td><?=$codigo_posto.'-'.$nome?></td>
		<td><?=$referencia.'-'.$descricao?></td>
		<td><?=$visita_agendada;?></td>
		<td><?=$consumidor_cidade?></td>
		<td><?=$consumidor_estado?></td>
	</tr>
	<?php }   ?>
</table>
<?php
	}
?>

<? include "rodape.php";?>
