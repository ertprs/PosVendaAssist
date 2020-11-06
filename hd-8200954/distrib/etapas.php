
<style>

	#etapas{
		margin:0 auto;
		text-align:center;
		width: 600px;
		font-size:14px;
	}

	#etapas ul {
		padding:5px;
		margin:0;
		float: left;
		background-color:#DAE2FE;
		list-style:none;
	}

	#etapas ul a{
		color:#7F7F7F;
		font-weight:normal;
	}

	#etapas ul li {
		padding:0 10px;
		display: inline; 
		color: #959595;
	}

	#etapas ul li.ativo a{
		color: #000000;
		font-weight:bold;
	}
</style>

<?
if (strlen($etapa)==0){
	$etapa = 1;
}
?>

<div id='etapas'>
	<ul>
		<li <?=($etapa==1)?"class='ativo'":""?>><a href='embarque_geral_conferencia_novo.php'>1) Seleção de Embarque</a></li>
		<li <?=($etapa==2)?"class='ativo'":""?>><a href='embarque_geral_conferencia_novo.php?btn_acao=embarcar'>2) Efetivar embarque</a></li>
		<li <?=($etapa==3)?"class='ativo'":""?>><a href='embarque_faturamento_teste.php'>3) Faturar embarque</a></li>
	</ul>
</div>
