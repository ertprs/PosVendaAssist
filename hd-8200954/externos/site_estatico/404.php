<?php $pagetitle = "Página não encontrada" ?>

<?php include('header.php') ?>
<script>$('body').addClass('pg thx-page pg404')</script>

<section class="table h-img">
	<?php include('menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Erro 404</h2></div>
		<h3>Página não encontrada.</h3>
		<a class="back" href="<?php echo $url; ?>"><i class="fa fa-angle-left"></i>Voltar à página inicial.</a>
	</div>
</section>

<script type="text/javascript" src="<?php echo $url; ?>/js/fmc.js"></script>

</body>
</html>