<?php 
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

include_once 'funcoes.php';

include 'cabecalho.php';
?>
<style>
	.texto_avulso{
	   font: 20px;
	   text-align: center;
	   width:900px;
	   margin: 0 auto;
	}
	.acesso_sistema{
		font-size: 20px;
		font-weight: bold;
		color: #117688;
	}
	.video_msi{
		font-size: 20px;
		font-weight: bold;
		color: #0e4e88;
	}
</style>	
<form name="msi_comunicado" id="msi_comunicado">
	<div id="layout">
		<div class="texto_avulso" style="width: 700px; padding:10px">
		Est� dispon�vel exclusivamente � rede de Assist�ncias T�cnicas Autorizadas o acesso ao <b>MSI Online</b>, ferramenta utilizada para visualiza��o de Vistas Explodidas, busca de equipamentos que utilizam determinado c�digo, realiza��o de or�amentos, visualiza��o de manuais de reparo, informativos t�cnicos, c�digos alternativos e muito mais! A grande vantagem desta nova vers�o � que <b>a atualiza��o � autom�tica</b>.<br>
		</div>
	</div>
	<div class="acessar">
		<a href="https://msi.makita.co.jp/MsiWW/MsiWW/MSIW0010.aspx" target="_blank"><h4 class="acesso_sistema" style="padding: 10px;">ACESSE O SISTEMA</h4></a>
		<a href="https://msi.makita.co.jp/MsiWW/MsiWW/MSIW0010.aspx" target="_blank"><img src="image/makita_msi/vetor_msi_makita.png" alt="Acesse o sistema" height="120" width="auto" style="padding:10px"></a>
	</div>
	<div style="padding:10px;">
		<div class="menu2">
			<div class="main">
			<ul>
				<li>
					<a href="helpdesk_cadastrar.php?makita_msi=1"><h2><i class="fa fa-fw fa-plus"></i> Solicitar Acesso</h2><span>Op��o para quem n�o tem acesso ao MSI Online</span>
					</a>
				</li>
			</ul>
			</div>
		</div>
	</div>

	<div>
		<h4 class="video_msi" style="padding: 10px">Assista o v�deo abaixo para saber como acessar e utilizar o MSI Online</h4>
		<iframe width="560" height="315" src="https://www.youtube.com/embed/aeEeV0cUdnM" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</div>
</form>

<?php
include "rodape.php";
?>
