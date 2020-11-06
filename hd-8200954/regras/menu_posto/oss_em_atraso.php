<script>
	$().ready(function(){
		$('.read-more').click(function (e) {
            var text = $("p").hasClass('expanded') ? 'Leia Mais' : 'Esconder';
            $(this).text(text);
            $('.message p').toggleClass('expanded');
            e.preventDefault();
        });

        $('a.close').click(function (e) {
            $("a.close i").toggleClass('fa-minus fa-plus');
            $('.message .the-info').slideToggle();
            e.preventDefault();
        });

        $(document).keyup(function (e) {
            if (e.keyCode == 27) {
                $('a.close i').removeClass('fa-minus').addClass('fa-plus');
                $('.message .the-info').slideUp();
            }
        });
	});
</script>

<?php
	if (in_array($login_fabrica, array(1,2,11,15,24,30,35,45,51,81,91,86,104,151))) {?>
		<div class="message">
			<div class="main2 tar">
				<a class="close"><i class="btn fa fa-minus"></i></a>
			</div>
			<div class="main2 p-tb tac the-info">
				<?php include_once APP_DIR.'os_risco.php' ?>
				<hr>
				<a class="btn read-more">Leia Mais</a>
			</div>
		</div>
	<?php
	}
?>
