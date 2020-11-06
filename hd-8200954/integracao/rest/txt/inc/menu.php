<div id='menu'>
	<ul style="" aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu">
		<?php if(!empty($_SESSION['header'])):?>
			<li><a href="config.php" tabindex="-1">Header Parametros</a></li>
			<li><a href="peca.php" tabindex="-1">Peça</a></li>
			<li class="divider"></li>
		<?php elseif(!strpos($_SERVER ['PHP_SELF'], 'config.php')) : ?>
			<?php  header('Location: config.php'); ?>
		<?php endif;?>
		<li><a href="logout.php" tabindex="-1">Sair</a></li>
	</ul>
</div>