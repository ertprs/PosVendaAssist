<div id="menu-lateral"> 
    <h3 id="titulo-menu"class="text-center">Menu</h3>
    <ul id="ul-menu-lateral" class="nav nav-list bs-docs-sidenav">
        <li>
            <a href="config.php">
                <i class="icon-cog"></i>
                <span>Autenticação</span>
            </a>
        </li>
        <?php 
        if(!empty($_SESSION['header'])){

        	?>
        	<li>
	            <a href="client.php">
	                <i class="icon-inbox"></i>
	                <span>Integrador</span>
	            </a>
        	</li>                                
        	<?php


        }

        ?>
        
    </ul>                                                
    <ul id="env-logout" class="nav nav-list bs-docs-sidenav">
        <li>
            <a href="../../logout_2.php">
                <i class="icon-cog"></i>
                <span>Logout</span>
            </a>
        </li>                            
    </ul>                                                
</div>
