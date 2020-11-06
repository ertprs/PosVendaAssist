<div class="clear"></div>
</div>
<!--end: wrapper-->
<div class="clear"></div>





<script type="text/javascript">

/****************************************************************
  Carousel de imagenes 2008 1.0. 30-Jul-08
  Autor tunait http://javascript.tunait.com/
  Script de libre uso mientras se mantengan intactos los creditos de autor.
****************************************************************/
function tunObtObj(ide){
	return document.getElementById(ide)
}
var tunaCarousel = function (ideContenedor, desplazamiento, direccion){
	this.contenedor = tunObtObj(ideContenedor);
	if (this.contenedor.style.position == 'static' ||
		this.contenedor.style.position == 'inherit') this.contenedor.style.position = 'relative'
	this.contenedor.style.overflow = 'hidden'
	this.anchoContenedores = 0;
	this.ima_plei = 'plei.jpg'
	this.ima_pausa = 'pausa.jpg'
	var contenedor1 = document.createElement('div');
	contenedor1.setAttribute('id', ideContenedor + "_cont1");
	var Elementos = this.contenedor.childNodes
	var numElementos = Elementos.length;
	this.rec = 10
	this.rec2 = true
	this.direccion  = direccion
	this.mas = this.direccion == 'rtl' ? '+' :'-'
	this.masmas = this.direccion == 'rtl' ? '++' :'--'
	this.menos = this.direccion == 'rtl' ? '-' :'+'
	this.menosmenos = this.direccion == 'rtl' ? '--' :'++'
	this.menosIgual = this.direccion == 'rtl' ? '-=' :'+='
	this.masIgual = this.direccion == 'rtl' ? '+=' :'-='
	
	var arrayImas = new Array();
	for(m = numElementos -1 ; m >= 0; m--){
		if(Elementos[m].tagName == 'IMG' || Elementos[m].tagName == 'A' ){
			if(Elementos[m].nodeType == 1 && Elementos[m].tagName == 'A' && Elementos[m].hasChildNodes()) {
				var elHijos = Elementos[m].childNodes.length;
				for(n = 0; n < elHijos; n++){
					if(Elementos[m].childNodes[n].tagName == 'IMG'){
						this.anchoContenedores += Elementos[m].childNodes[n].clientWidth;
					}
					else if(Elementos[m].childNodes[n].tagName != 'IMG' && Elementos[m].childNodes[n].tagName != 'A'){
						Elementos[m].removeChild(Elementos[m].childNodes[n])
						elHijos--; n--
					}
				}
			}
			else{
				this.anchoContenedores += Elementos[m].clientWidth
			}
			var Nodo = Elementos[m]; 
			var clonNodo = Nodo.cloneNode(true);
			arrayImas[arrayImas.length] = clonNodo
		}
		this.contenedor.removeChild(Elementos[m])
	}
	for(m = arrayImas.length -1 ; m >= 0 ; m--){
		contenedor1.appendChild(arrayImas[m])
		
	}
	with(contenedor1.style){
		width = this.anchoContenedores + "px";
		left = 0 + 'px'
		position = 'absolute'
	}
	this.contenedor.setAttribute('marcha', 1)
	this.contenedor.setAttribute('stop', 0)
	this.contenedor.appendChild(contenedor1)
	this.cont1 = tunObtObj(contenedor1.getAttribute('id'))
	this.pos1 = 0;
	this.pos2 = this.direccion == 'rtl' ? this.anchoContenedores : (this.anchoContenedores * -1)
	contenedor2 = this.cont1.cloneNode(true);
	contenedor2.setAttribute('id', ideContenedor + "_cont2");
	contenedor2.style.left = this.anchoContenedores + 'px'
	this.contenedor.appendChild(contenedor2)
	this.cont2 = tunObtObj(contenedor2.getAttribute('id'))
	
	this.mueve = function (){
		if( this.contenedor.getAttribute('stop') == 1) return false
		if(this.contenedor.getAttribute('marcha') == 1){
			eval('this.pos1 ' + this.menosIgual +' desplazamiento')
			eval('this.pos2 ' + this.menosIgual + 'desplazamiento')
			this.rec = 10
			this.rec2 = true
		}
		else{
			if(this.rec > 0 && this.rec2 == true){
				eval('this.pos1 ' + this.masIgual +' desplazamiento')
				eval('this.pos2 ' + this.masIgual + ' desplazamiento')
				this.rec--
			}
			else if(this.rec == 0){
				this.rec = -10
				this.rec2 = false
			}
			else if(this.rec < 0&& this.rec2 == true){
				eval('this.pos1 ' + this.masIgual + ' desplazamiento')
				eval('this.pos2 ' + this.masIgual + ' desplazamiento')
				this.rec++
			}
		}
		if(this.direccion == 'rtl'){
			if(this.pos1 < (0 - this.anchoContenedores)) this.pos1 = this.pos2 + this.anchoContenedores
			if(this.pos2 < (0 - this.anchoContenedores)) this.pos2 = this.pos1 + this.anchoContenedores
		}
		else{
			if(this.pos1 > (this.anchoContenedores)) this.pos1 = this.pos2 - this.anchoContenedores
			if(this.pos2 > (this.anchoContenedores)) this.pos2 = this.pos1 - this.anchoContenedores
		}
		this.cont1.style.left = this.pos1 + "px"
		this.cont2.style.left = this.pos2 + "px"
	
	}
	this.cont1.onmouseover = function(){
		this.parentNode.setAttribute('marcha', 0)
	}
	this.cont2.onmouseover = function(){
		this.parentNode.setAttribute('marcha', 0)
	}
	this.cont1.onmouseout = function(){
		this.parentNode.setAttribute('marcha', 1)
	}
	this.cont2.onmouseout = function(){
		this.parentNode.setAttribute('marcha', 1)
	}

	this.controles = function(){
		accion = this.getAttribute('accion');
		if(accion == 'pausar'){
			this.parentNode.parentNode.setAttribute('stop', 1)
			this.setAttribute('src', this.getAttribute('ima_plei'));
			this.setAttribute('alt', 'Play');
			this.setAttribute('title', 'Play');
			this.setAttribute('accion', 'plei');
		
		}
		else if(accion == 'plei'){
			this.parentNode.parentNode.setAttribute('stop', 0)
			this.setAttribute('src', this.getAttribute('ima_pausa'));
			this.setAttribute('alt', 'Detener')
			this.setAttribute('title', 'Detener')
			this.setAttribute('accion', 'pausar')
			
		}
	}

	this.controlesCarousel = function(){
		contenedor_controles = document.createElement('span');
		contenedor_controles.style.position = 'absolute';
		contenedor_controles.style.cursor = 'pointer';
		contenedor_controles.setAttribute('id', ideContenedor + '_Controles')
		ima_controles = document.createElement('img')
		ima_controles.setAttribute('src', this.ima_pausa)
		ima_controles.setAttribute('alt', 'Detener')
		ima_controles.setAttribute('title', 'Detener')
		ima_controles.setAttribute('accion', 'pausar')
		ima_controles.setAttribute('ima_pausa', this.ima_pausa)
		ima_controles.setAttribute('ima_plei', this.ima_plei)
		ima_controles.onclick = this.controles
		contenedor_controles.appendChild(ima_controles)
		this.contenedor.appendChild(contenedor_controles)
		
	}

}
</script>

<!--	<div id="logos_clientes">
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_3.jpg' alt='Britânia' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_5.jpg' alt='Mondial' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_6.jpg' alt='Tectoy' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_7.jpg' alt='Filizola' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_8.jpg' alt='Ibratele' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_11.jpg' alt='LennoxSound' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_14.jpg' alt='Intelbras' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_15.jpg' alt='Latina' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_19.jpg' alt='Lorenzetti' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_20.jpg' alt='Bosch' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_20_02.jpg' alt='Skil' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_24.jpg' alt='Suggar' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_30.jpg' alt='Esmaltec' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_35.jpg' alt='Cadence' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_40.jpg' alt='Masterfrio' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_42.jpg' alt='Makita' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_43.jpg' alt='Nova Intelbras' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_45.jpg' alt='NKS' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_47.jpg' alt='Crown Ferramentas' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_50.jpg' alt='Colormaq' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_51.jpg' alt='Ga.Ma Italy' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_52.jpg' alt='Fricon' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_59.jpg' alt='SightGPS' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_66.jpg' alt='Maxcom Intelbras' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_72.jpg' alt='Mallory' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_74.jpg' alt='Atlas Fogões' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_75.jpg' alt='ThermoKing' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_80.jpg' alt='Amvox Precision' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_81_01.jpg' alt='Salton' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_81_02.jpg' alt='George Foreman' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_81_03.jpg' alt='Melitta' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_81_04.jpg' alt='Russell Hobbs' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_81_05.jpg' alt='ToastMaster' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_81_06.jpg' alt='White Westinghouse' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_85.jpg' alt='Gelopar' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_86.jpg' alt='Famastil F-Power' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_87.jpg' alt='Jacto' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_88.jpg' alt='Orbis do Brasil' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_89.jpg' alt='Daiken' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_90.jpg' alt='IBBL' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_91.jpg' alt='Wanke' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_94.jpg' alt='Everest' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_95_01.jpg' alt='LeaderShip' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_95_02.jpg' alt='LeaderShip Feminina' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_95_03.jpg' alt='NoteShip' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_95_04.jpg' alt='GoldShip' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_95_05.jpg' alt='LeaderShip GAMER' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_96.jpg' alt='Bosch Security Systems' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_98.jpg' alt='Dellar' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_99.jpg' alt='Eterny' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_101.jpg' alt='DeLonghi' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_102.jpg' alt='Remington' />
		<img src='/site-wp/wp-content/themes/gadget/logos-cinzas/mono_103.jpg' alt='RayoVac' />
		<img src='/pixel.gif' width='1' height='1' alt='' />
	</div>
-->
	<script type="text/javascript">
		/* Configuração */
		var marq

		function mueveCarousel(){
				marq.mueve();
		}
		onload = function(){
			marq = new tunaCarousel('logos_clientes', 2, 'rtl'); // 2º param.: pixels por cada movimento
			tiempo = setInterval(mueveCarousel, 10); // 2º param.: delay em milisegundos
		}
	</script>
	<style type="text/css">
	#logos_clientes {
		background: #ffffff;
		position: relative;
		bottom: 0;
		left:0;
		width:550px;
		overflow: hide;
		padding: 0 33%;
		height:30px;
	 }
	 #logos_clientes img {
	 	height: 30px;
		padding-left: 2em;
		opacity: 0.7;
        filter: alpha(opacity=80);
		transition: opacity 0.4s;
		-o-transition: opacity 0.4s;
		-ms-transition: opacity 0.4s;
		-moz-transition: opacity 0.4s;
		-webkit-transition: opacity 0.4s;
	 }
	 #logos_clientes img:hover {
		opacity: 1;
        filter: alpha(opacity=100);
	 }
	</style>


<div class="clear"></div>

<div id="footerbg">
  <div id="footer">
    <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Footer') ) : ?>
    <div class="footerwidget left">
      <h3>
        <?php _e("Footer Widget #1", 'themejunkie'); ?>
      </h3>
      <?php _e("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin semper ultrices tortor quis sodales. Proin scelerisque porttitor tellus, vel dignissim tortor varius quis. Proin diam eros, lobortis sit amet viverra id, eleifend ut tellus. Vivamus sed lacus augue.", 'themejunkie'); ?>
    </div>
    <div class="footerwidget left">
      <h3>
        <?php _e("Footer Widget #2", 'themejunkie'); ?>
      </h3>
      <?php _e("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin semper ultrices tortor quis sodales. Proin scelerisque porttitor tellus, vel dignissim tortor varius quis. Proin diam eros, lobortis sit amet viverra id, eleifend ut tellus. Vivamus sed lacus augue.", 'themejunkie'); ?>
    </div>
    <div class="footerwidget left">
      <h3>
        <?php _e("Footer Widget #3", 'themejunkie'); ?>
      </h3>
      <?php _e("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin semper ultrices tortor quis sodales. Proin scelerisque porttitor tellus, vel dignissim tortor varius quis. Proin diam eros, lobortis sit amet viverra id, eleifend ut tellus. Vivamus sed lacus augue.", 'themejunkie'); ?>
    </div>
    <div class="footerwidget left">
      <h3>
        <?php _e("Footer Widget #4", 'themejunkie'); ?>
      </h3>
      <?php _e("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin semper ultrices tortor quis sodales. Proin scelerisque porttitor tellus, vel dignissim tortor varius quis. Proin diam eros, lobortis sit amet viverra id, eleifend ut tellus. Vivamus sed lacus augue.", 'themejunkie'); ?>
    </div>
    <!--end: footerwidget-->
    <?php endif; ?>
    <div class="clear"></div>
  </div>
  <!--end: footer-->
  <div class="clear"></div>
</div>
<!--end: footerbg-->
<div id="bottom">
  <div id="bottomwrapper">
    <div class="center"><a href="<?php bloginfo('siteurl'); ?>">
	&middot; Telecontrol &copy; 2011 &middot; </a>
	<!--
        <?php bloginfo('name'); ?>
        </a> &middot; Direitos reservados &middot; 
        -->
        <a class="rss" href="<?php bloginfo('rss2_url'); ?>">
    	    Artigos
    	</a> 
    	&middot; 
    	<a class="rss" href="<?php bloginfo('comments_rss2_url'); ?>">
    	    Coment&aacute;rios
    	</a> 
    	&middot;
    	
	<?php    
	$PUBLIC_HOSTNAME = `wget -q -O - http://169.254.169.254/latest/meta-data/public-hostname`;                      
        echo "<span class='bottomazul'><br>webserver: $PUBLIC_HOSTNAME<br>Deus &eacute; o Provedor<br>.</span>";
	?>

    	
	<!--
	<div class="right">Designed by <a href="http://www.theme-junkie.com/">Theme Junkie</a> &middot; Powered by <a href="http://www.wordpress.org/">WordPress</a></div>
	-->
    </div>                                                                             
  </div>
  <!--end: bottomwrapper-->
</div>
<!--end: bottom-->
<?php if(get_theme_mod('track') == 'Yes') { ?>
<!--begin: blog tracking-->
<?php echo stripslashes(get_theme_mod('track_code')); ?>
<!--end: blog tracking-->
<?php } else { ?>
<?php } ?>
<?php #wp_footer(); ?>
</body></html>
