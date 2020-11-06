function parseSamples()
{
	$("pre.sample").each(
		function()
		{
			if(this.firstChild.data)
			{
				this.fn = function()
				{
					eval(this.firstChild.data);
					return false;
				};
				$(this).append("<button class='runsnip' onclick='this.parentNode.fn()'>&raquo; Run</button><br />")
			}
		}
	);
	$("code.sample").wrap("<a href='#'></a>")
	.click(
		function()
		{
			if(this.firstChild.data)
			{
				eval(this.firstChild.data);
			}
			return false;
		}
	);
}