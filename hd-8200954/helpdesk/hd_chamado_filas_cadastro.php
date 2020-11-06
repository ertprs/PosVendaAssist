<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$backlog = 1;

$sql = "select hd_chamado_filas from tbl_fabrica where fabrica = $login_fabrica";
$res = @pg_exec ($con,$sql);
if (@pg_numrows($res) > 0) {
	$backlog = pg_result($res,0,hd_chamado_filas);
}



//VERIFICA SE O USUÁRIO É SUPERVISOR
$sql="  SELECT * FROM tbl_admin
		WHERE admin=$login_admin
		AND help_desk_supervisor='t'";

$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	$supervisor = true;
	$nome_completo=pg_result($res,0,nome_completo);
}

$colsTblHDs = 7;		//'colspan' tabela HDs em andamento
$colsTblHDsAprova = 8;	//'colspan' tabela HDs em espera

if ($login_fabrica == 3 and $supervisor) {
	$prioriza_hds = true;
	$ordemPR = 'prioridade_supervisor ,';	// Para ordenar as queries por prioridade
	$colPR = "<th title='Prioridade deste chamado.'>Prioridade</th>\n";// Coluna 'Prioridade'
	$colsTblHDs = 8;			//'colspan' tabela HDs em andamento
	$colsTblHDsAprova = 9;		//'colspan' tabela HDs em espera
}


//PEGA O NOME DA FABRICA
$sql = "SELECT   *
		FROM     tbl_fabrica
		WHERE    fabrica=$login_fabrica
		ORDER BY nome";
$res = pg_exec ($con,$sql);
$nome      = trim(pg_result($res,0,nome));

$menu_cor_fundo="EEEEEE";
$menu_cor_linha="BBBBBB";


$btn_acao = $_POST['atualizar'];
if(strlen($btn_acao)>0){
	$quantidade = $_POST['quantidade'];
	$fabrica = 	$_POST['fabrica'];
	
	if(strlen($quantidade) > 0 && strlen($fabrica) > 0){
		$sql = "UPDATE tbl_fabrica SET
					hd_chamado_filas = $quantidade 
				WHERE fabrica = $fabrica";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
				$msg = "ATUALIZADO COM SUCESSO";
	}else{
		$msg_erro = "INFORME A FABRICA E QUANTIDADE";
	}
}
?>

<html>
<head>
	<title>Telecontrol - Help Desk</title>
	<link type="text/css" rel="stylesheet" href="css/css.css">
	<style>
	.negrito	{font-weight:bold!important;}
	.vermelho	{color: red!important}

	.supervisor{
		font-size: 12px;
	}

	.supervisor ul{
		list-style-type:none;
		margin:0px;
	}

	thead, tr.header, caption {
		background-color:#D9E8FF;
		color: #666;
		height: 24px;
		font: normal bold 14px arial, helvetica, sans-serif;
		text-align: center;
	}
	
	thead, tr.header_resposta, caption {
		color: #666;
		height: 24px;
		font: normal bold 14px arial, helvetica, sans-serif;
		text-align: center;
	}
	

	.resposta:hover {
		background-color:white;
	}
	
	.resposta{
		text-align: left;
		font-size:12px;
		font-weight:normal;
		cursor:		default;	
	}
	
	
    .over {
        background: #E1E4EA;
    }
    
	table table.tabela td {
		text-align: left;
		font-size:12px;
		font-weight:normal;
		cursor:		default;
	}
	table.tabela {margin-bottom: 1em}

	table#tbl_franquia td,
	table#tbl_fr_ant td {font-size: 14px;font-weight:bold;text-align:center;}

	table#tbl_hd_ativos td.Conteudo {font-size: 9px;}
	table#tbl_hd_ativos td

	table.tabela tr:hover,
	#regrasHD td p:hover,
	#regrasHD td ol li:hover {
		background-color:#D9E8FF!important;
	}
	#regrasHD td ol li {
		margin-bottom: 1.2em;
	}
	</style>
	<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
	
	<script type="text/javascript">
	
	$().ready(function() {
		
		$('input[id^=hd_prioridade],input[id^=hda_prioridade]').change(function () {
			var nova_prioridade = $(this).val().toUpperCase();
			var numHD			= $(this).attr('hd');
			var inputPR         = $(this);
// 			if(nova_prioridade=='') return true; // Não faz nada especial se o campo estiver vazio
			if (nova_prioridade != $(this).val()) $(this).val(nova_prioridade);
			$(this).css('background-color', '#ccf');
			$.post(location.pathname,
					{
						'ajax':	'prioridade',
						'hd':	numHD,
						'pr':   nova_prioridade
					},
					function(data) {
						resposta = data.split('|');
						if(resposta[0]=='KO') {
							alert(resposta[1]);
							inputPR.css('background-color', '#fcc');
							return false;
						}
						if(resposta[0]=='OK') {
// 							inputPR.parent().html(nova_prioridade);
							inputPR.css('background-color', '#cfc');
						}
				     });
    	});

		
		$('.header_resposta').mouseenter(function(){
	        $(this).addClass('over');
	    }).mouseleave(function(){
	    	$(this).removeClass('over');
	    });



		$('.td_resposta').click(function(){
			var cod_fabrica = $(this).attr('rel');
			var qtd_fabrica = $(this).attr('alt');
			$(".quantidade").val(qtd_fabrica);
			$("#fabrica").val(cod_fabrica).attr('selected', true);
		});


		$('.limpar').click(function(){
			$(".quantidade").val("");
			$("#fabrica").val("").attr('selected', true);
		});
	    
	  });
	
	function verificaNumero(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	}

	$(document).ready(function() {
		$("#quantidade").keypress(verificaNumero);
	});
	
    </script>
</head>
<body>
<!--[if lt IE 9]>
	<style type="text/css">
	.mouseOver {background-color:#D9E8FF!important;}
    </style>
	<script type="text/javascript">
	$().ready(function() {
    	$('table.tabela tbody tr, #regrasHD td p, #regrasHD td ol li').hover(function() {
			$(this).toggleClass('mouseOver');
		});
    });
    </script>
<![endif]-->
<?
include "menu.php";
?>
<script type="text/javascript" src="../js/jquery.alphanumeric.js"></script>
<script type='text/javascript'>
	$().ready(function() {
		$('input[id^=hd_prioridade],input[id^=hda_prioridade]').numeric();
});
</script>

<form name="frm" id="frm"  method='POST' action="<?php echo $PHP_SELF;?>">


<table width="700" align="center" bgcolor="#FFFFFF" border='0'>
	<tr>
		<td>
			<table width="700" align="center" cellpadding="0" cellspacing="0" border="0" id="lista_sup" class='tabela'>
					<tr class="header">
						<td></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif" colspan="3">
							<center>Cadastro  Filas de Chamados</center>
						</td>
						<td></td>
					</tr>
			</table>
				
			<table width="700" align="center" cellpadding="0" cellspacing="0" border="0" id="lista_sup" class='tabela'>
				<tr style="font-family: arial;font-size: 12px;cursor:pointer;background-color:" height="25">
					<td width="180">&nbsp;<br>
					<td width="250">Fabrica<br>
						<select name="fabrica" id="fabrica" class="fabrica">
							<option>Selecione uma Fabrica</option>
							<?php 
							$sql = "select fabrica,nome from tbl_fabrica where ativo_fabrica = true order by nome";
							$res = pg_exec ($con,$sql);
							if (pg_numrows($res) > 0) {
								for($i=0;$i<pg_numrows($res);$i++){
									$cod_fabrica 	= pg_result($res,$i,fabrica);
									$nome_fabrica 	= pg_result($res,$i,nome);
									?>
									<option value="<?php echo $cod_fabrica;?>"><?php echo $nome_fabrica;?></option>
									<?php 
								}
							}
							?>
						</select>
					</td>
					<td width="220">Quantidade<br>
						<input type="text" name="quantidade" id="quantidade" class="quantidade" size="5">
					</td>
					<td width="50">&nbsp;<br>
				</tr>

				<tr>
					<td></td>
					<td></td>
					<td></td>
				</tr>
			</table>
			
			<table width="700" align="center" cellpadding="0" cellspacing="0" border="0" id="lista_sup" class='tabela'>
				<tr>
					<td>
						<center><input type="submit" name="atualizar" id="atualizar" value="Atualizar" /><input type="button" name="limpar" id="limpar" class="limpar" value="Limpar" /></center>
					</td>
				</tr>
			</table>
			<?php 
				$sql = "select fabrica,nome,hd_chamado_filas from tbl_fabrica where ativo_fabrica = true order by nome";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res) > 0) {
					?>
					
					<table width="700" align="center" cellpadding="0" cellspacing="0" border="0" class='tabela'>
						<tbody>
							<tr class="header" background="/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif" colspan="3">
								<td width="100">&nbsp;Fabrica</td>
								<td width="500">&nbsp;Nome</td>
								<td width="100">&nbsp;Quantidade</td>
							</tr>
							<?php 
								if(pg_numrows($res) > 0) {
									for($i=0;$i<pg_numrows($res);$i++){
										$cod_fabrica 		= pg_result($res,$i,fabrica);
										$nome_fabrica 		= pg_result($res,$i,nome);
										$hd_chamado_fabrica = pg_result($res,$i,hd_chamado_filas);
										$cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
							?>
									<tr bgcolor="<?php echo $cor;?>" class="header_resposta" colspan="3" style="cursor:pointer;">
										<td class="td_resposta" rel="<?php echo $cod_fabrica;?>" alt="<?php echo $hd_chamado_fabrica;?>">&nbsp;<?php echo $cod_fabrica;?></td>
										<td class="td_resposta" rel="<?php echo $cod_fabrica;?>" alt="<?php echo $hd_chamado_fabrica;?>">&nbsp;<?php echo $nome_fabrica;?></td>
										<td class="td_resposta" rel="<?php echo $cod_fabrica;?>" alt="<?php echo $hd_chamado_fabrica;?>">&nbsp;<?php echo $hd_chamado_fabrica;?></td>
									</tr>
							<?php 
									}
								}
							?>
						</tbody>
					</table>
				<?php 
				}
				?>
<?php 	

echo "</td>";
echo "</tr>";

echo "</table>";
echo "</form>";
// if ($supervisor) echo "Fábrica $login_fabrica, usuário: $login_admin ($login_login)<br>Banco $dbnome";
include "rodape.php" ?>
</body>
</html>
