<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'cadastros') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}
	$sql = "SELECT  empregado,
					programa 
				FROM tbl_erp_programa_restrito 
				WHERE programa = '$PHP_SELF' AND fabrica = $login_empresa AND empregado = $login_empregado";
	$res = pg_exec ($con,$sql);
	
	if(pg_numrows($res) > 0) {
		$programa  = pg_result ($res,0,programa);
		$empregado = pg_result ($res,0,empregado);
	}

//---------------------------------------------------

echo "<H1>cadastros</H1>";
include "rodape.php"; ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Table Sorting, Filtering, Etc from JavascriptToolbox.com</title>
<link rel="stylesheet" type="text/css" href="/css.php" media="all">
<script type="text/javascript" src="/main.js"></script>
<script type="text/javascript" src="/includes/jquery.js"></script><script type="text/javascript" src="/includes/toggleDiv.js"></script>
<script type="text/javascript" src="/libsource.php/table/combined/table.js"></script>
<link rel="stylesheet" type="text/css" href="table.css" media="all">
</head>
<body>

<table class="columns" cellspacing="0" border="0">
<tr>
	<td class="left" rowspan="2">
		<div style="text-align:center;"><a href="/" title="Home"><img src="/logo5.gif" alt="Logo"></a></div>

		<div class='portlet'>
			<h5>Navigation</h5>
			<div class='pBody'>
				<a  href="/" title="Home">Home</a>
				<a  class="selected" href="/lib/" title="Libraries">Libraries</a>
				<a  href="/bestpractices/" title="Best Practices">Best Practices</a>
				<a  href="/jquery/" title="jQuery Tips">jQuery Tips</a>
				<a  href="/support/" title="Support">Support</a>
				<a  href="/resources/" title="Javascript Resources">Resources / Links</a>
				<a  href="/search/" title="Javascript Knowledge Base Search">JS Knowledge Base</a>
				<a  href="/donate/" title="Donate To Support This Site">Donate!</a>
				<a  href="/aboutme/" title="About Me">About Me</a>
				<a href="http://www.AjaxToolbox.com/" title="AJAX" target="_blank">AJAX Toolbox</a>
			</div>
		</div>

		<div class='portlet'>
			<h5>Libraries</h5>
			<div class='pBody'>
				<a  href="/lib/calendar/" title="">Calendar Popup</a>
				<a  href="/lib/checkboxgroup/" title="">Checkbox Group</a>
				<a  href="/lib/datadumper/" title="">Data Dumper</a>
				<a  href="/lib/validations/" title="">Data Validation</a>
				<a  href="/lib/date/" title="">Date Functions</a>
				<a  href="/lib/mktree/" title="">DHTML Tree</a>
				<a  href="/lib/dragiframe/" title="">Draggable Iframes</a>
				<a  href="/lib/dynamicoptionlist/" title="">Dependent Select Boxes</a>
				<a  href="/lib/form/" title="">Form Functions</a>
				<a  href="/lib/objectposition/" title="">Object Position</a>
				<a  href="/lib/optiontransfer/" title="">Option Transfer</a>
				<a  href="/lib/popup/" title="">Popups (In-Page DIV)</a>
				<a  href="/lib/selectbox/" title="">Selectbox Functions</a>
				<a  class="selected" href="/lib/table/" title="">Table Sorting And Utils</a>
				<a  href="/lib/util/" title="">Util/DOM/CSS/Event</a>
			</div>
		</div>

		<h5>doctype [<a href="#" onClick="alert('Since some web browsers use the DOCTYPE of a document to switch between Quirks or Standards mode, some code may have different behavior depending on the DOCTYPE specified. These links allow pages to be tested using strict, loose, or no doctype at all for compatability testing.'); return false;" title="What's This?">?</a>]</h5>
		[<a href="?doctype=strict">strict</a>]
		[<a href="?doctype=loose">loose</a>]
		[<a href="?doctype=none">none</a>]

	</td>
	<td class="header" colspan="2">
		<div id="searchbox"><form method="get" action="http://www.google.com/custom"><input type="hidden" name="domains" value="javascripttoolbox.com"><input class="search" type="text" name="q" size="15" maxlength="255" value="Search!" onClick="this.style.color='black';this.value='';"><input type="submit" name="sa" value="go"><input type="hidden" name="sitesearch" value="javascripttoolbox.com"><input type="hidden" name="client" value="pub-9155030588311591"><input type="hidden" name="forid" value="1"><input type="hidden" name="ie" value="ISO-8859-1"><input type="hidden" name="oe" value="ISO-8859-1"><input type="hidden" name="cof" value="GALT:#008000;GL:1;DIV:#AAAAAA;VLC:663399;AH:center;BGC:FFFFFF;LBGC:C2DDF4;ALC:002BB8;LC:002BB8;T:000000;GFNT:AAAAAA;GIMP:AAAAAA;LH:50;LW:588;L:http://www.javascripttoolbox.com/search_header.gif;S:http://www.JavascriptToolbox.com/;FORID:1;"><input type="hidden" name="hl" value="en"></form></div>
		<h1>Table Sorting, Filtering, Etc - <span class="beta_tag">BETA!</span></h1>	</td>
</tr>
<tr>
	<td class="middle"><div class="corner cornerul"></div><div class="body">
	<div class="tabContainer">
	<div><a href="index.php"><img src="/images/tabs/overview.gif" alt=""> Overview</a></div>
	<div class="selected"><img src="/images/tabs/examples.gif" alt=""> Examples</div>
	<div><a href="documentation.php"><img src="/images/tabs/documentation.gif" alt=""> Documentation</a></div>
	<div><a href="jquery.php"><img src="/images/tabs/jquery.gif" alt=""> jQuery</a></div>
	<div><a href="test.php"><img src="/images/tabs/test.gif" alt=""> Test Cases</a></div>
	<div><a href="notes.php"><img src="/images/tabs/notes.gif" alt=""> Notes</a></div>
	<div><a href="source.php"><img src="/images/tabs/source.gif" alt=""> Source</a></div>
</div>
<div class="tabcontent"><br>




<h2>Client-Side Table Sorting Basic Example</h2>

<p>
Click on column headers to sort by the column. Sorting is done within the two tbody sections, not across them, and the header and footer are not changed.
The table is auto-striped and auto-sorted on the first column on page load.
</p>

<table class="example table-autosort:0 table-stripeclass:alternate">
<thead>
	<tr>
		<th class="table-sortable:numeric">Index</th>
		<th class="table-sortable:numeric">Numeric</th>
		<th class="table-sortable:default">Text</th>
		<th class="table-sortable:currency">Currency</th>
		<th class="table-sortable:date">Date</th>
		<th class="table-sortable:default">Checkbox</th>
	</tr>
</thead>
<tbody class="table-nosort">
	<tr class="tbody_header">
		<th colspan="6">tbody #1</th>
	</tr>
</tbody>
<tbody>
	<tr>
		<td>0</td>
		<td>273.2</td>
		<td>Bill</td>
		<td>$55.935</td>
		<td>2007-11-12</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
	<tr>
		<td>1</td>
		<td>3.1</td>
		<td>Joe</td>
		<td>$63.735</td>
		<td>2008-04-05</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
	<tr>
		<td>2</td>
		<td>560.8</td>
		<td>Bob</td>
		<td>$18.825</td>
		<td>2010-01-23</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
	<tr>
		<td>3</td>
		<td>943.8</td>
		<td>Matt</td>
		<td>$31.265</td>
		<td>2010-10-05</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
	<tr>
		<td>4</td>
		<td>527.1</td>
		<td>Mark</td>
		<td>$80.885</td>
		<td>2010-10-19</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
</tbody>
<tbody class="table-nosort">
	<tr class="tbody_header">
		<th colspan="6">tbody #2</th>
	</tr>
</tbody>
<tbody>
	<tr>
		<td>5</td>
		<td>575.9</td>
		<td>Tom</td>
		<td>$50.645</td>
		<td>2009-11-11</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
	<tr>
		<td>6</td>
		<td>57.8</td>
		<td>Jake</td>
		<td>$31.125</td>
		<td>2010-06-13</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
	<tr>
		<td>7</td>
		<td>878.6</td>
		<td>Greg</td>
		<td>$52.215</td>
		<td>2008-06-12</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
	<tr>
		<td>8</td>
		<td>357.5</td>
		<td>Adam</td>
		<td>$81.635</td>
		<td>2010-08-14</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
	<tr>
		<td>9</td>
		<td>76.1</td>
		<td>Steve</td>
		<td>$40.665</td>
		<td>2009-04-01</td>
		<td><input type="checkbox" name="cb" value="1"></td>
	</tr>
</tbody>
<tfoot>
	<tr>
		<td>tfoot</td>
		<td>tfoot</td>
		<td>tfoot</td>
		<td>tfoot</td>
		<td>tfoot</td>
		<td>tfoot</td>
	</tr>
</tfoot>
</table>

<h2>Client-Side Sorting With Large Table</h2>

<p>
See the <a href="example_big_table.php">Client-Side Sorting With 500 Rows</a> example to see how fast it sorts on your computer.
</p>

<h2>Rowspan/Colspan Correction</h2>

<p>
When cells span rows or columns, their cellIndex property is misleading. A cell may be the first td in a row, but actually occupy the second position in the table "grid" because a cell in a row above it has a rowspan > 1. Since the column to sort is calculated automatically based on the cell clicked, the correct column index must be computed rather than relying blindly on the cellIndex property of the clicked cell. The example below demonstrates this complex requirement.
<br>
Also, it shows that only classes that are marked as "sortable" will receive the sorted class names. This is important, because you don't want <i>every</i> header cell that is in the same column as the sorted column to be marked as sorted - only the ones you specify.
</p>

<table class="example table-autosort:0 table-stripeclass:alternate">
<thead>
	<tr>
		<th colspan="5">Rowspan/Colspan Correction</th>
	</tr>
	<tr>
		<th class="table-sortable:numeric" rowspan="2">Index</th>
		<th colspan="2">First Two Columns</th>
		<th colspan="2">Second Two Columns</th>
	</tr>
	<tr>
		<th class="table-sortable:numeric">Numeric</th>
		<th class="table-sortable:default">Text</th>
		<th class="table-sortable:currency">Currency</th>
		<th class="table-sortable:date">Date</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>0</td>
		<td>577.8</td>
		<td>Bill</td>
		<td>$67.975</td>
		<td>2007-11-08</td>
	</tr>
	<tr class="alternate">
		<td>1</td>
		<td>648.8</td>
		<td>Joe</td>
		<td>$68.275</td>
		<td>2009-11-14</td>
	</tr>
	<tr>
		<td>2</td>
		<td>844.9</td>
		<td>Bob</td>
		<td>$24.355</td>
		<td>2010-06-20</td>
	</tr>
	<tr class="alternate">
		<td>3</td>
		<td>609.9</td>
		<td>Matt</td>
		<td>$18.725</td>
		<td>2008-04-15</td>
	</tr>
	<tr>
		<td>4</td>
		<td>595.2</td>
		<td>Mark</td>
		<td>$71.435</td>
		<td>2007-09-07</td>
	</tr>
	<tr class="alternate">
		<td>5</td>
		<td>591.8</td>
		<td>Tom</td>
		<td>$29.025</td>
		<td>2009-04-15</td>
	</tr>
	<tr>
		<td>6</td>
		<td>293.7</td>
		<td>Jake</td>
		<td>$34.805</td>
		<td>2010-04-10</td>
	</tr>
	<tr class="alternate">
		<td>7</td>
		<td>179.9</td>
		<td>Greg</td>
		<td>$22.655</td>
		<td>2008-10-04</td>
	</tr>
	<tr>
		<td>8</td>
		<td>435</td>
		<td>Adam</td>
		<td>$58.405</td>
		<td>2008-03-05</td>
	</tr>
	<tr class="alternate">
		<td>9</td>
		<td>375.2</td>
		<td>Steve</td>
		<td>$66.005</td>
		<td>2009-06-19</td>
	</tr>
</tbody>
</table>

<h2>Client-Side Table Filtering</h2>

<p>
Client-side table filtering works by scanning each row in the table and matching it against the criteria passed into the filter. Filter values are stored, so adding or removing another filter maintains any other filters that still apply.
<br>
The filters in this example were created manually, not using the auto-filter functionality. The first filter is a manually-create select list, the second is a text input for entering free-form text, and the third uses custom functions to do the filtering.
</p>

<table class="example table-stripeclass:alternate">
<thead>
	<tr>
		<th colspan="4">Table Filtering</th>
	</tr>
	<tr>
		<th class="filterable">Index</th>
		<th class="filterable">Number</th>
		<th class="filterable">Name</th>
		<th class="filterable">Amount</th>
	</tr>
	<tr>
		<th>Filter:</th>
		<th><select onchange="Table.filter(this,this)"><option value="">All</option><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></th>
		<th><input name="filter" size="8" onkeyup="Table.filter(this,this)"></th>
		<th><select onchange="Table.filter(this,this)"><option value="function(){return true;}">All</option><option value="function(val){return parseFloat(val.replace(/\$/,''))>1;}">&gt; $1</option><option value="function(val){return parseFloat(val.replace(/\$/,''))<=1;}">&lt;= $1</option></th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>0</td>
		<td>0</td>
		<td>Bill</td>
		<td>$1.83</td>
	</tr>
	<tr class="alternate">
		<td>1</td>
		<td>1</td>
		<td>Joe</td>
		<td>$0.53</td>
	</tr>
	<tr>
		<td>2</td>
		<td>2</td>
		<td>Bob</td>
		<td>$0.53</td>
	</tr>
	<tr class="alternate">
		<td>3</td>
		<td>3</td>
		<td>Matt</td>
		<td>$1.93</td>
	</tr>
	<tr>
		<td>4</td>
		<td>4</td>
		<td>Mark</td>
		<td>$1.83</td>
	</tr>
	<tr class="alternate">
		<td>5</td>
		<td>0</td>
		<td>Tom</td>
		<td>$1.93</td>
	</tr>
	<tr>
		<td>6</td>
		<td>1</td>
		<td>Jake</td>
		<td>$1.33</td>
	</tr>
	<tr class="alternate">
		<td>7</td>
		<td>2</td>
		<td>Greg</td>
		<td>$1.53</td>
	</tr>
	<tr>
		<td>8</td>
		<td>3</td>
		<td>Bill</td>
		<td>$0.43</td>
	</tr>
	<tr class="alternate">
		<td>9</td>
		<td>4</td>
		<td>Joe</td>
		<td>$1.13</td>
	</tr>
	<tr>
		<td>10</td>
		<td>0</td>
		<td>Bob</td>
		<td>$0.73</td>
	</tr>
	<tr class="alternate">
		<td>11</td>
		<td>1</td>
		<td>Matt</td>
		<td>$0.73</td>
	</tr>
	<tr>
		<td>12</td>
		<td>2</td>
		<td>Mark</td>
		<td>$1.53</td>
	</tr>
	<tr class="alternate">
		<td>13</td>
		<td>3</td>
		<td>Tom</td>
		<td>$1.93</td>
	</tr>
	<tr>
		<td>14</td>
		<td>4</td>
		<td>Jake</td>
		<td>$0.23</td>
	</tr>
	<tr class="alternate">
		<td>15</td>
		<td>0</td>
		<td>Greg</td>
		<td>$1.53</td>
	</tr>
	<tr>
		<td>16</td>
		<td>1</td>
		<td>Bill</td>
		<td>$1.13</td>
	</tr>
	<tr class="alternate">
		<td>17</td>
		<td>2</td>
		<td>Joe</td>
		<td>$0.83</td>
	</tr>
	<tr>
		<td>18</td>
		<td>3</td>
		<td>Bob</td>
		<td>$0.63</td>
	</tr>
	<tr class="alternate">
		<td>19</td>
		<td>4</td>
		<td>Matt</td>
		<td>$1.63</td>
	</tr>
	<tr>
		<td>20</td>
		<td>0</td>
		<td>Mark</td>
		<td>$1.53</td>
	</tr>
	<tr class="alternate">
		<td>21</td>
		<td>1</td>
		<td>Tom</td>
		<td>$0.23</td>
	</tr>
	<tr>
		<td>22</td>
		<td>2</td>
		<td>Jake</td>
		<td>$0.03</td>
	</tr>
	<tr class="alternate">
		<td>23</td>
		<td>3</td>
		<td>Greg</td>
		<td>$1.93</td>
	</tr>
	<tr>
		<td>24</td>
		<td>4</td>
		<td>Bill</td>
		<td>$1.03</td>
	</tr>
	<tr class="alternate">
		<td>25</td>
		<td>0</td>
		<td>Joe</td>
		<td>$0.93</td>
	</tr>
	<tr>
		<td>26</td>
		<td>1</td>
		<td>Bob</td>
		<td>$1.13</td>
	</tr>
	<tr class="alternate">
		<td>27</td>
		<td>2</td>
		<td>Matt</td>
		<td>$1.33</td>
	</tr>
	<tr>
		<td>28</td>
		<td>3</td>
		<td>Mark</td>
		<td>$1.63</td>
	</tr>
	<tr class="alternate">
		<td>29</td>
		<td>4</td>
		<td>Tom</td>
		<td>$0.43</td>
	</tr>
	<tr>
		<td>30</td>
		<td>0</td>
		<td>Jake</td>
		<td>$0.43</td>
	</tr>
	<tr class="alternate">
		<td>31</td>
		<td>1</td>
		<td>Greg</td>
		<td>$1.43</td>
	</tr>
	<tr>
		<td>32</td>
		<td>2</td>
		<td>Bill</td>
		<td>$0.93</td>
	</tr>
	<tr class="alternate">
		<td>33</td>
		<td>3</td>
		<td>Joe</td>
		<td>$1.03</td>
	</tr>
	<tr>
		<td>34</td>
		<td>4</td>
		<td>Bob</td>
		<td>$1.33</td>
	</tr>
</tbody>
</table>


<h2>Table Striping</h2>

<p>
If a table has alternate rows highlighted and it is sorted, filtered, or otherwise manipulated, the sorted row colors can be mixed up. This function simply re-stripes rows by applying a class name to each odd row and removing it from each even row.
</p>

<table class="example">
<thead>
	<tr>
		<th colspan="5" onclick="Table.stripe(this,'alternate')" style="cursor:pointer">
		   Table Striping<br>
		   <b><span style="background-color:yellow;">(Click here to shade alternate rows in this table)</span></b>
		</th>
	</tr>
	<tr>
		<th>Index</th>
		<th>Numeric</th>
		<th>Text</th>
		<th>Currency</th>
		<th>Date</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>0</td>
		<td>315.9</td>
		<td>Bill</td>
		<td>$41.04</td>
		<td>2008-08-02</td>
	</tr>
	<tr>
		<td>1</td>
		<td>47.2</td>
		<td>Joe</td>
		<td>$59.24</td>
		<td>2010-04-27</td>
	</tr>
	<tr>
		<td>2</td>
		<td>388.4</td>
		<td>Bob</td>
		<td>$96.14</td>
		<td>2009-07-14</td>
	</tr>
	<tr>
		<td>3</td>
		<td>324.7</td>
		<td>Matt</td>
		<td>$4.43</td>
		<td>2008-10-15</td>
	</tr>
	<tr>
		<td>4</td>
		<td>852.7</td>
		<td>Mark</td>
		<td>$41.84</td>
		<td>2009-09-11</td>
	</tr>
	<tr>
		<td>5</td>
		<td>674.4</td>
		<td>Tom</td>
		<td>$13.94</td>
		<td>2010-01-25</td>
	</tr>
	<tr>
		<td>6</td>
		<td>675.9</td>
		<td>Jake</td>
		<td>$8.73</td>
		<td>2008-05-22</td>
	</tr>
	<tr>
		<td>7</td>
		<td>112.3</td>
		<td>Greg</td>
		<td>$61.84</td>
		<td>2010-06-02</td>
	</tr>
	<tr>
		<td>8</td>
		<td>923.9</td>
		<td>Adam</td>
		<td>$81.04</td>
		<td>2007-12-07</td>
	</tr>
	<tr>
		<td>9</td>
		<td>618.5</td>
		<td>Steve</td>
		<td>$23.94</td>
		<td>2009-06-06</td>
	</tr>
</tbody>
</table>

<h2>Client-Side Table Paging</h2>

<p>
Although paging through results or records is usually done by making a trip back to the server, it can sometimes be helpful to page through results on the client side.
</p>

<table class="example table-autopage:5 table-stripeclass:alternate" id="page">
<thead>
	<tr>
		<th>Index</th>
		<th>Name</th>
		<th>Date</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>0</td>
		<td>Bill</td>
		<td>2008-06-21</td>
	</tr>
	<tr>
		<td>1</td>
		<td>Joe</td>
		<td>2009-05-25</td>
	</tr>
	<tr>
		<td>2</td>
		<td>Bob</td>
		<td>2010-09-23</td>
	</tr>
	<tr>
		<td>3</td>
		<td>Matt</td>
		<td>2009-06-02</td>
	</tr>
	<tr>
		<td>4</td>
		<td>Mark</td>
		<td>2009-07-19</td>
	</tr>
	<tr>
		<td>5</td>
		<td>Tom</td>
		<td>2009-06-08</td>
	</tr>
	<tr>
		<td>6</td>
		<td>Jake</td>
		<td>2008-12-06</td>
	</tr>
	<tr>
		<td>7</td>
		<td>Greg</td>
		<td>2010-10-11</td>
	</tr>
	<tr>
		<td>8</td>
		<td>Adam</td>
		<td>2009-04-24</td>
	</tr>
	<tr>
		<td>9</td>
		<td>Steve</td>
		<td>2007-08-28</td>
	</tr>
	<tr>
		<td>10</td>
		<td>George</td>
		<td>2008-08-21</td>
	</tr>
	<tr>
		<td>11</td>
		<td>John</td>
		<td>2009-06-14</td>
	</tr>
	<tr>
		<td>12</td>
		<td>Phil</td>
		<td>2008-10-21</td>
	</tr>
	<tr>
		<td>13</td>
		<td>Jack</td>
		<td>2008-03-03</td>
	</tr>
	<tr>
		<td>14</td>
		<td>Paul</td>
		<td>2010-10-10</td>
	</tr>
	<tr>
		<td>15</td>
		<td>Rob</td>
		<td>2007-09-11</td>
	</tr>
	<tr>
		<td>16</td>
		<td>Walt</td>
		<td>2010-04-23</td>
	</tr>
	<tr>
		<td>17</td>
		<td>Nathan</td>
		<td>2008-01-17</td>
	</tr>
	<tr>
		<td>18</td>
		<td>Dan</td>
		<td>2010-02-14</td>
	</tr>
	<tr>
		<td>19</td>
		<td>Jeff</td>
		<td>2009-04-13</td>
	</tr>
	<tr>
		<td>20</td>
		<td>Bill</td>
		<td>2008-04-26</td>
	</tr>
	<tr>
		<td>21</td>
		<td>Joe</td>
		<td>2007-09-15</td>
	</tr>
	<tr>
		<td>22</td>
		<td>Bob</td>
		<td>2009-08-21</td>
	</tr>
	<tr>
		<td>23</td>
		<td>Matt</td>
		<td>2010-04-12</td>
	</tr>
	<tr>
		<td>24</td>
		<td>Mark</td>
		<td>2010-06-26</td>
	</tr>
	<tr>
		<td>25</td>
		<td>Tom</td>
		<td>2009-05-24</td>
	</tr>
	<tr>
		<td>26</td>
		<td>Jake</td>
		<td>2009-09-03</td>
	</tr>
	<tr>
		<td>27</td>
		<td>Greg</td>
		<td>2010-10-11</td>
	</tr>
	<tr>
		<td>28</td>
		<td>Adam</td>
		<td>2008-03-09</td>
	</tr>
</tbody>
<tfoot>
	<td colspan="3">
		<a href="#" onclick="pageexample('previous'); return false;">&lt;&lt;&nbsp;Previous</a>
		<a href="#" id="page1" class="pagelink" onclick="pageexample(0); return false;">1</a>
		<a href="#" id="page2" class="pagelink" onclick="pageexample(1); return false;">2</a>
		<a href="#" id="page3" class="pagelink" onclick="pageexample(2); return false;">3</a>
		<a href="#" id="page4" class="pagelink" onclick="pageexample(3); return false;">4</a>
		<a href="#" id="page5" class="pagelink" onclick="pageexample(4); return false;">5</a>
		<a href="#" id="page6" class="pagelink" onclick="pageexample(5); return false;">6</a>
		<a href="#" onclick="pageexample('next'); return false;">Next&nbsp;&gt;&gt;
	</td>
</tfoot>
</table>
<script type="text/javascript">
function pageexample(page) {
	var t = document.getElementById('page');
	var res;
	if (page=="previous") {
		res=Table.pagePrevious(t);
	}
	else if (page=="next") {
		res=Table.pageNext(t);
	}
	else {
		res=Table.page(t,page);
	}
	var currentPage = res.page+1;
	$('.pagelink').removeClass('currentpage');
	$('#page'+currentPage).addClass('currentpage');
}
</script>

<h2>Alternate Styles Examples</h2>

<p>
This example shows how simply changing your css can change the sort interface without any code changes necessary.
<br>
Click on an icon set to try it on.
</p>

<table border="0" class="icons">
<tr>
	<td class="iconset" onclick="seticon('01')"><img src="icons/01_unsorted.gif"><img src="icons/01_ascending.gif"><img src="icons/01_descending.gif"></td>
	<td class="iconset" onclick="seticon('02')"><img src="icons/02_ascending.gif"><img src="icons/02_descending.gif"></td>
	<td class="iconset" onclick="seticon('03')"><img src="icons/03_ascending.gif"><img src="icons/03_descending.gif"></td>
	<td class="iconset" onclick="seticon('04')"><img src="icons/04_ascending.gif"><img src="icons/04_descending.gif"></td>
</tr>
<tr>
	<td class="iconset" onclick="seticon('05')"><img src="icons/05_unsorted.gif"><img src="icons/05_ascending.gif"><img src="icons/05_descending.gif"></td>
	<td class="iconset" onclick="seticon('06')"><img src="icons/06_ascending.gif"><img src="icons/06_descending.gif"></td>
	<td class="iconset" onclick="seticon('07')"><img src="icons/07_ascending.gif"><img src="icons/07_descending.gif"></td>
	<td class="iconset" onclick="seticon('08')"><img src="icons/08_ascending.gif"><img src="icons/08_descending.gif"></td>
</tr>
<tr>
	<td class="iconset" onclick="seticon('09')"><img src="icons/09_ascending.gif"><img src="icons/09_descending.gif"></td>
	<td class="iconset" onclick="seticon('10')"><img src="icons/10_unsorted.gif"><img src="icons/10_ascending.gif"><img src="icons/10_descending.gif"></td>
	<td class="iconset" onclick="seticon('11')"><img src="icons/11_ascending.gif"><img src="icons/11_descending.gif"></td>
	<td class="iconset" onclick="seticon('12')"><img src="icons/12_ascending.gif"><img src="icons/12_descending.gif"></td>
</tr>
<tr>
	<td class="iconset" onclick="seticon('13')"><img src="icons/13_ascending.gif"><img src="icons/13_descending.gif"></td>
	<td class="iconset" onclick="seticon('15')"><img src="icons/15_ascending.gif"><img src="icons/15_descending.gif"></td>
	<td class="iconset" onclick="seticon('16')"><img src="icons/16_ascending.gif"><img src="icons/16_descending.gif"></td>
	<td class="iconset" onclick="seticon('17')"><img src="icons/17_ascending.gif"><img src="icons/17_descending.gif"></td>
</tr>
<tr>
	<td class="iconset" onclick="seticon('18')"><img src="icons/18_ascending.gif"><img src="icons/18_descending.gif"></td>
	<td class="iconset" onclick="seticon('19')"><img src="icons/19_unsorted.gif"><img src="icons/19_ascending.gif"><img src="icons/19_descending.gif"></td>
</tr>
</table>
<a href="icons/">Browse icon files for download...</a>
<br>
<br>
<script type="text/javascript">
function seticon(n) {
	$('#altstyle').removeClass('sort01 sort02 sort03 sort04 sort05 sort06 sort07 sort08 sort09 sort10 sort11 sort12 sort13 sort14 sort15 sort16 sort17 sort18 sort19').addClass('sort'+n);
}
</script>

<table id="altstyle" class="example sort01 table-autosort:0 table-autostripe table-stripeclass:alternate">
<thead>
	<tr style="height:35px">
		<th class="table-filterable table-sortable:numeric">Index</th>
		<th class="table-sortable:numeric">Numeric</th>
		<th class="table-filterable table-sortable:default">Text</th>
		<th class="table-sortable:currency">Currency</th>
		<th class="table-sortable:date">Date</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>0</td>
		<td>881.5</td>
		<td>Bill</td>
		<td>$55.54</td>
		<td>2009-01-07</td>
	</tr>
	<tr>
		<td>1</td>
		<td>436.5</td>
		<td>Joe</td>
		<td>$53.04</td>
		<td>2010-10-19</td>
	</tr>
	<tr>
		<td>2</td>
		<td>38.7</td>
		<td>Bob</td>
		<td>$9.63</td>
		<td>2008-12-03</td>
	</tr>
	<tr>
		<td>3</td>
		<td>29.2</td>
		<td>Matt</td>
		<td>$62.44</td>
		<td>2008-12-09</td>
	</tr>
	<tr>
		<td>4</td>
		<td>344.4</td>
		<td>Mark</td>
		<td>$19.54</td>
		<td>2010-02-08</td>
	</tr>
</tbody>
</table>


	</div>
	</div>
	</td>
	<td class="right" id="googleAds">
		<script type="text/javascript">
		google_ad_client = "pub-9155030588311591";
		google_ad_width = 120;
		google_ad_height = 600;
		google_ad_format = "120x600_as";
		google_color_border = "000000";
		google_color_bg = "FFFFFF";
		google_color_link = "000000";
		google_color_url = "000000";
		google_color_text = "000000";
		</script>
		<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
	</td>
</tr>
<tr>
	<td class="footer" colspan="3"><img src="/bullet-bolt.gif" alt="Logo">&nbsp;All Contents Copyright &copy; <a href="http://www.mattkruse.com/">Matt Kruse</a></td>
</table>


</body>
</html>
