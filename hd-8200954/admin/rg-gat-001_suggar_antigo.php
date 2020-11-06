<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';

$visita_posto    = trim($_GET['visita_posto']);
if(strlen($visita_posto) == 0) $visita_posto    = trim($_POST['visita_posto']);

$btn_acao = $_POST['btn_acao'];

if(strlen($visita_posto) > 0){

	$sql="SELECT nome_completo FROM tbl_visita_posto JOIN tbl_admin ON tbl_admin.admin =tbl_visita_posto.admin WHERE visita_posto=$visita_posto";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) >0){
		$nome_completo            = trim(pg_result($res,0,'nome_completo'));
	}

} else {

	$sql="SELECT nome_completo FROM tbl_admin where admin=$login_admin";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) >0){
		$nome_completo            = trim(pg_result($res,0,'nome_completo'));
	}
}
if(strlen($posto) > 0){

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_posto_fabrica.contato_endereco    AS endereco,
			tbl_posto_fabrica.contato_numero      AS numero,
			tbl_posto_fabrica.contato_complemento AS complemento,
			tbl_posto_fabrica.contato_bairro      AS bairro,
			tbl_posto_fabrica.contato_cep         AS cep,
			tbl_posto_fabrica.contato_cidade      AS cidade,
			tbl_posto_fabrica.contato_estado      AS estado,
			tbl_posto_fabrica.contato_email       AS email,
			tbl_posto.fone,
			tbl_posto.contato
		FROM	tbl_posto
		LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		WHERE   tbl_posto.posto           = $posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$codigo           = trim(pg_result($res,0,'codigo_posto'));
		$nome             = trim(pg_result($res,0,'nome'));
		$endereco         = trim(pg_result($res,0,'endereco'));
		$endereco         = str_replace("\"","",$endereco);
		$numero           = trim(pg_result($res,0,'numero'));
		$complemento      = trim(pg_result($res,0,'complemento'));
		$cidade           = trim(pg_result($res,0,'cidade'));
		$estado           = trim(pg_result($res,0,'estado'));
		$email            = trim(pg_result($res,0,'email'));
		$fone             = trim(pg_result($res,0,'fone'));
		$contato          = trim(pg_result($res,0,'contato'));
	}
}

if(strlen($btn_acao) > 0 and strlen($visita_posto) == 0 ) {
	
	$msg_erro="";
	$msg="";
	$posto                  = trim($_POST['posto']);
	$obtencao_informacao    = trim($_POST['obtencao_informacao']);
	$atendimento_reclamacao = trim($_POST['atendimento_reclamacao']);
	$facilidade_contato_fone= trim($_POST['facilidade_contato_fone']);
	$pontualidade_entrega   = trim($_POST['pontualidade_entrega']);
	$comentario_posto       = trim($_POST['comentario_posto']);
	$parecer_tecnico        = trim($_POST['parecer_tecnico']);
	$responsavel_pa         = trim($_POST['responsavel_pa']);
	$visita_posto           = trim($_POST['visita_posto']);
	$data_visita            = $_POST['data_visita'];
	if(strlen($data_visita)==0) 	$data_visita      = $_GET['data_visita'];
	$fnc            = pg_exec($con,"SELECT fnc_formata_data('$data_visita')");
	if (strlen($msg_erro) == 0) $aux_data= pg_result ($fnc,0,0);

	if(strlen($posto) > 0) 
		$xposto                   = "'".$posto."'";
	else 
		$msg_erro .= "Por favor escolhe o posto a ser pesquisado<BR>";

	if(strlen($aux_data) > 0) 
		$xdata = "'".$aux_data."'";
	else 
		$msg_erro .= "Por favor digitar a data<BR>";

	if(strlen($obtencao_informacao) > 0)
		$xobtencao_informacao = "'".$obtencao_informacao."'";
	else 
		$msg_erro .= "Escolhe a nota para a Obtenção de Informação<BR>";

	if(strlen($atendimento_reclamacao) > 0) 
		$xatendimento_reclamacao = "'".$atendimento_reclamacao."'";
	else 
		$msg_erro .= "Escolhe a nota para o Atendimento de Reclamações<BR>";

	if(strlen($facilidade_contato_fone) > 0) 
		$xfacilidade_contato_fone = "'".$facilidade_contato_fone."'";
	else 
		$msg_erro .= "Escolhe a nota para a Facilidade ao Estabelecer Contato Telefônico<BR>";

	if(strlen($pontualidade_entrega) > 0) 
		$xpontualidade_entrega = "'".$pontualidade_entrega."'";
	else
		$msg_erro .= "Escolhe a nota para a Pontualidade na Entrega de Componentes<BR>";

	if(strlen($comentario_posto) > 0) 
		$xcomentario_posto = "'".$comentario_posto."'";
	else 
		$msg_erro .= "Por favor coloque o comentário<BR>";

	if(strlen($parecer_tecnico) > 0) 
		$xparecer_tecnico                      = "'".$parecer_tecnico."'";
	else 
		$msg_erro .= "Por favor coloque o Parecer Técnico<BR>";

	if(strlen($responsavel_pa) > 0) 
		$xresponsavel_pa = "'".$responsavel_pa."'";
	else 
		$msg_erro .= "Por favor coloque o nome do responsável do Posto<BR>";


	if(strlen($msg_erro) ==0 ){
		$resX = pg_exec ($con,"BEGIN TRANSACTION");
		$sql="INSERT INTO tbl_visita_posto (
				posto                       ,
				fabrica                     ,
				data                        ,
				obtencao_informacao         ,
				atendimento_reclamacao      ,
				facilidade_contato_fone     ,
				pontualidade_entrega        ,
				comentario_posto            ,
				parecer_tecnico             ,
				responsavel_pa              ,
				admin                       
				) VALUES (
				$xposto                     ,
				$login_fabrica              ,
				$xdata                      ,
				$xobtencao_informacao       ,
				$xatendimento_reclamacao    ,
				$xfacilidade_contato_fone   ,
				$xpontualidade_entrega      ,
				$xcomentario_posto          ,
				$xparecer_tecnico           ,
				$xresponsavel_pa            ,
				$login_admin                
				) ";

		$res=pg_exec($con,$sql);

		$msg_erro .= pg_errormessage($con);
		if (strlen($msg_erro) == 0) {
			$resX = pg_exec ($con,"COMMIT TRANSACTION");
			$sql = "SELECT CURRVAL ('tbl_visita_posto_visita_posto_seq') as visita_posto";
			$res = pg_exec($con,$sql);
			$visita_posto = trim(pg_result($res,0,'visita_posto'));
			$msg="Gravado com sucesso";
			//header("Location: $PHP_SELF?visita_posto=$visita_posto&mensagem=$msg");
            header("Location: $PHP_SELF?sucesso=$msg");
		}else{
			$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro="Erro ao gravar.";
			//header("Location: $PHP_SELF?erro=$msg_erro");
		}
		
	}
	
} else {

	if(strlen($btn_acao) > 0 and strlen($visita_posto) > 0 ) {
		$sql="SELECT * 
				FROM tbl_visita_posto
				WHERE visita_posto=$visita_posto
				AND   admin=$login_admin";
		$res=pg_exec($con,$sql);

		if(pg_numrows($res) > 0) {
		
			$comentario_posto       = trim($_POST['comentario_posto']);
			$parecer_tecnico        = trim($_POST['parecer_tecnico']);
			$responsavel_pa         = trim($_POST['responsavel_pa']);
			
			if(strlen($comentario_posto)                > 0) $xcomentario_posto                  = "'".$comentario_posto."'";
			else $msg_erro .= "Por favor coloque o comentário<BR>";
			if(strlen($parecer_tecnico)                 > 0) $xparecer_tecnico                   = "'".$parecer_tecnico."'";
			else $msg_erro .= "Por favor coloque o Parecer Técnico<BR>";
			if(strlen($responsavel_pa)                  > 0) $xresponsavel_pa                    = "'".$responsavel_pa."'";
			else $msg_erro .= "Por favor coloque o nome do responsável do Posto<BR>";

			if(strlen($msg_erro) == 0) {
				$resX = pg_exec ($con,"BEGIN TRANSACTION");
				$sql="UPDATE tbl_visita_posto SET
						obtencao_informacao     =$obtencao_informacao     ,
						atendimento_reclamacao  =$atendimento_reclamacao  ,
						facilidade_contato_fone =$facilidade_contato_fone ,
						pontualidade_entrega    =$pontualidade_entrega    ,
						comentario_posto        =$xcomentario_posto       ,
						parecer_tecnico         =$xparecer_tecnico        ,
						responsavel_pa          =$xresponsavel_pa         
						WHERE visita_posto=$visita_posto
						AND   admin       =$login_admin";
				$res=pg_exec($con,$sql);

				$msg_erro .= pg_errormessage($con);
				if (strlen($msg_erro) == 0) {
					$resX = pg_exec ($con,"COMMIT TRANSACTION");
					$msg="Alterado com sucesso";
					//header("Location: $PHP_SELF?visita_posto=$visita_posto&erro=$msg");
                    header("Location: $PHP_SELF?sucesso=$msg");
				}else{
					$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
					$msg_erro="Erro ao alterar.";
					//header("Location: $PHP_SELF?erro=$msg_erro");
				}
			}
		} else {
			$msg_erro="Só o próprio inspetor que pode alterar este formulário";
		}
	}
}

$title       = "FORMULÁRIO RG - GAT - 001";
$cabecalho   = "FORMULÁRIO RG - GAT - 001"; 
$layout_menu = "tecnica";
include 'cabecalho.php';

?>
<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$("#data_visita").datePicker({startDate:"01/01/2000"});
		$("#data_visita").maskedinput("99/99/9999");
		$("#fone").maskedinput("(99) 9999-9999");
	});
</script>

<script language="JavaScript">
function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (tipo == "codigo" ) {
		var xcampo = campo3;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
		janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}
</script>

<style type="text/css">
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
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_sucesso{
	background-color:#1FA53C;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
    margin: 0 auto;
    width: 700px;
    padding: 4px 0;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.espaco{
	padding-left:110px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
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
<?

$msg = $_GET['erro'];
$sucesso = @$_GET['sucesso'];
if(strlen($msg_erro) == 0 AND strlen($sucesso) > 0){
	echo "<div class='msg_sucesso'>$sucesso</div>";
}

if(strlen($msg) > 0){
	echo "<font color='blue'>$msg</font>"; 
}
if (strlen($msg_erro)>0) { ?>
	<table class='msg_erro' align='center' width="700px">
		<tr>
			<td><?echo $msg_erro?></td>
		</tr>
	</table>
<?
}

if(strlen($visita_posto) >0) {
	$sql="SELECT tbl_visita_posto.visita_posto             ,
				 tbl_visita_posto.posto                    ,
				 tbl_visita_posto.obtencao_informacao      ,
				 tbl_visita_posto.atendimento_reclamacao   ,
				 tbl_visita_posto.facilidade_contato_fone  ,
				 tbl_visita_posto.pontualidade_entrega     ,
				 tbl_visita_posto.comentario_posto         ,
				 tbl_visita_posto.parecer_tecnico          ,
				 tbl_visita_posto.responsavel_pa           ,
				 tbl_visita_posto.admin                    ,
				 to_char(tbl_visita_posto.data,'DD/MM/YYYY') as data_visita                  ,
				 tbl_posto.nome                            ,
				 tbl_posto.endereco                        ,
				 tbl_posto.numero                          ,
				 tbl_posto.complemento                     ,
				 tbl_posto.bairro                          ,
				 tbl_posto.cep                             ,
				 tbl_posto.cidade                          ,
				 tbl_posto.estado                          ,
				 tbl_posto.email                           ,
				 tbl_posto.fone                            ,
				 tbl_posto.contato                         
			FROM tbl_visita_posto
			JOIN tbl_posto ON tbl_posto.posto=tbl_visita_posto.posto
			WHERE visita_posto=$visita_posto";

	$res=pg_exec($con,$sql);

	if(pg_numrows($res) > 0){
		$visita_posto             = trim(pg_result($res,0,'visita_posto'));
		$posto                    = trim(pg_result($res,0,'posto'));
		$obtencao_informacao      = trim(pg_result($res,0,'obtencao_informacao'));
		$atendimento_reclamacao   = trim(pg_result($res,0,'atendimento_reclamacao'));
		$facilidade_contato_fone  = trim(pg_result($res,0,'facilidade_contato_fone'));
		$pontualidade_entrega     = trim(pg_result($res,0,'pontualidade_entrega'));
		$comentario_posto         = trim(pg_result($res,0,'comentario_posto'));
		$parecer_tecnico          = trim(pg_result($res,0,'parecer_tecnico'));
		$responsavel_pa           = trim(pg_result($res,0,'responsavel_pa'));
		$nome                     = trim(pg_result($res,0,'nome'));
		$endereco                 = trim(pg_result($res,0,'endereco'));
		$endereco                 = str_replace("\"","",$endereco);
		$numero                   = trim(pg_result($res,0,'numero'));
		$complemento              = trim(pg_result($res,0,'complemento'));
		$cidade                   = trim(pg_result($res,0,'cidade'));
		$estado                   = trim(pg_result($res,0,'estado'));
		$email                    = trim(pg_result($res,0,'email'));
		$fone                     = trim(pg_result($res,0,'fone'));
		$contato                  = trim(pg_result($res,0,'contato'));
		$data_visita              = trim(pg_result($res,0,'data_visita'));
	
	}

}	
?>
<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
	
	<input type="hidden" name="posto" value="<? echo $posto ?>">
	<input type="hidden" name="visita_posto" value="<? echo $visita_posto ?>">
	
	<table width='700' align='center' border='0' cellspacing="0" class="formulario">
		<tr>
			<td rowspan="5">
				<img src="/assist/logos/suggar.jpg" alt='<?php echo $login_fabrica_site;?>' border="0" height="40">
			</td>
			<td rowspan="5" align="center">
				<font size='5'><strong>Relatório Visita ao Posto Autorizado</strong></font>
			</td>
			<td>Elaboração</td>
		</tr>
		<tr>
			<td width="200">
				<input type="text" class="frm" name="nome_completo" size="30" maxlength="40" value="<? echo $nome_completo; ?>" readonly>	
			</td>
		</tr>
		<tr>
			<td>Data</td>
		</tr>
		<tr>
			<td>
				<input type="text" class="frm" name="data_visita" id="data_visita" size="12" maxlength="10" value="<? echo $data_visita; ?>" >
			</td>
		</tr>
	</table>

	<table class="formulario" width='700' align='center' border='0' cellspacing="1">
		<tr>
			<td align="left">
				Responsável Posto
			</td>
			<td>
				<input type="text" name="responsavel_pa" class="frm" size="40" maxlength="50" value="<? echo $responsavel_pa; ?>">
			</td>
			<td colspan="2">
				RG - GAT - 001
			</td>
		</tr>
		<tr>
			<td align='left'>Posto Autorizado</td>
			<td align='left'>
				<input type="text" class="frm" name="nome" size="40" maxlength="60" value="<? echo $nome ?>">
				<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'nome')">
			</td>
			<td align='left'>Contato</td>
			<td align='left'>
				<input type="text" class="frm" name="contato" size="20" maxlength="50" value="<? echo $contato ?>" style="width:200px">
			</td>
		</tr>
		<tr>
			<td align='left'>Endereço</td>
			<td align='left'><input type="text" class="frm" name="endereco" size="40" maxlength="60" value="<? echo $endereco.'&nbsp;'.$numero; ?>"></td>
			<td align='left'>Telefone</td>
			<td align='left'><input type="text" class="frm" name="fone" id="fone" size="20" maxlength="20" value="<? echo $fone ?>" style="width:200px"></td>
		</tr>
		<tr>
			<td align='left'>Cidade/Estado:&nbsp;</td>
			<td align="left"><input type="text" class="frm" name="cidade" size="40" maxlength="60" value="<? echo "$cidade  &nbsp; $estado"; ?>" ></td>
			<td align='left'>E-mail:   </td>
			<td align='left'><input type="text" class="frm" name="email" size="20" maxlength="20" value="<? echo $email ?>" style="width:200px"></td>
		</tr>
		
	</table>
	
	<table class="formulario" width='700' align='center' border='0' cellspacing="1">
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan='4' align='center' class="texto_avulso">
				<strong>
					A Suggar Eletrodomésticos em busca da melhoria da qualidade de seus serviços e 
					<br>produtos, com uma meta de melhorar cada vez mais, busca seus comentários, criticas e
					<br>recomendações que são muito importantes para nós.
				</strong>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
	</table>
	
	<table class="formulario" width='700' align='center' border="1" cellspacing="1">

		<tr class="subtitulo">
			<td align='center' colspan='7'><b>Quadro a ser Preenchido Pelo Posto Autorizado</b></td>
		</tr>
		
		<tr class="titulo_coluna">
			<td align="center" nowrap>Assistencia Técnica</td>
			<td align="center">Excelente<BR><center>5</center></td>
			<td align="center">Muito Bom<BR><center>4</center></td>
			<td align="center">Bom<BR><center>3</center></td>
			<td align="center">Regular<BR><center>2</center></td>
			<td align="center">Fraco<BR><center>1</center></td>
			<td align="center">Não<BR><center>Aplicável</center></td>
		</tr>
		
		<tr class="table_line">
			<td align='left' nowrap>1 - Obtenção de Informação</td>
			<td align='center'>
				<input type='radio' name='obtencao_informacao' class="frm" value='5' <? if($obtencao_informacao=='5')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='obtencao_informacao' class="frm" value='4' <? if($obtencao_informacao=='4')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='obtencao_informacao' class="frm" value='3' <? if($obtencao_informacao=='3')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='obtencao_informacao' class="frm" value='2' <? if($obtencao_informacao=='2')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='obtencao_informacao' class="frm" value='1' <? if($obtencao_informacao=='1')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='obtencao_informacao' class="frm" value='0' <? if($obtencao_informacao=='0')  echo " checked";?> >
			</td>
		</tr>
		
		<tr>
			<td align='left'>2 - Atendimento de Reclamações</td>
			<td align='center'>
				<input type='radio' name='atendimento_reclamacao' class="frm" value='5' <? if($atendimento_reclamacao=='5')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='atendimento_reclamacao' class="frm" value='4' <? if($atendimento_reclamacao=='4')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='atendimento_reclamacao' class="frm" value='3' <? if($atendimento_reclamacao=='3')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='atendimento_reclamacao' class="frm" value='2' <? if($atendimento_reclamacao=='2')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='atendimento_reclamacao' class="frm" value='1' <? if($atendimento_reclamacao=='1')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='atendimento_reclamacao' class="frm" value='0' <? if($atendimento_reclamacao=='0')  echo " checked";?> >
			</td>
		</tr>
		
		<tr>
			<td align='left' nowrap>3 - Facilidade ao Estabelecer Contato Telefônico</td>
			<td align='center'>
				<input type='radio' name='facilidade_contato_fone' class="frm" value='5' <? if($facilidade_contato_fone=='5')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='facilidade_contato_fone' class="frm" value='4' <? if($facilidade_contato_fone=='4')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='facilidade_contato_fone' class="frm" value='3' <? if($facilidade_contato_fone=='3')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='facilidade_contato_fone' class="frm" value='2' <? if($facilidade_contato_fone=='2')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='facilidade_contato_fone' class="frm" value='1' <? if($facilidade_contato_fone=='1')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='facilidade_contato_fone' class="frm" value='0' <? if($facilidade_contato_fone=='0')  echo " checked";?> >
			</td>
		</tr>
		
		<tr>
			<td align='left'>4 - Pontualidade na Entrega de Componentes</td>
			<td align='center'>
				<input type='radio' name='pontualidade_entrega' class="frm" value='5' <? if($pontualidade_entrega=='5')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='pontualidade_entrega' class="frm" value='4' <? if($pontualidade_entrega=='4')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='pontualidade_entrega' class="frm" value='3' <? if($pontualidade_entrega=='3')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='pontualidade_entrega' class="frm" value='2' <? if($pontualidade_entrega=='2')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='pontualidade_entrega' class="frm" value='1' <? if($pontualidade_entrega=='1')  echo " checked";?> >
			</td>
			<td align='center'>
				<input type='radio' name='pontualidade_entrega' class="frm" value='0' <? if($pontualidade_entrega=='0')  echo " checked";?> >
			</td>
		</tr>
	</table>

	<table class="formulario" width='700' align='center' border='0' cellspacing="1">
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr class="subtitulo">
			<td align='center'>
				Comentários - Posto Autorizado
			</td>
		</tr>
		<tr>
			<td align="center">
				<textarea name='comentario_posto' class="frm" rows='10' cols='100'><? echo $comentario_posto; ?></textarea>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr class="subtitulo">
			<td align='center'>
				Parecer Técnico Inspeção - Suggar
			</td>
		</tr>
		<tr>
			<td align="center">
				<textarea name='parecer_tecnico' class="frm" rows='10' cols='100'><? echo $parecer_tecnico; ?></textarea>
			</td>
		</tr>
	</table>
	
	<table class="formulario" width='700' align='center' border='0' cellspacing="1">
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
		<tr>
			<td align='center' colspan="3">
				<input type='submit' name="btn_acao" value="Gravar">
			</td>
		</tr>
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
	</table>

</form>

<? include "rodape.php" ?>