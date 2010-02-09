<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<link href="doc/phpxmldb.css.php" rel="stylesheet" type="text/css">

	<title>PhpXmlDb</title>

</head>

<body>
	
	<table border="0" width="100%" cellspacing="0">
		<tr bgcolor="#000000">
			<th>
				<font size="5" color="#ffffff" face="Arial">
					PhpXmlDb Documentation Index
				</font>
			</th>
		</tr>
	</table>

	<div class="container">
		<p>A PHP class for handling XML databases. An XML database contains one or more tables 
		that can each contain records. Suitable for small-medium databases, where SQL database 
		hosting is not viable. Does not require DOM. Built upon the 
		<a href="/scripts/php/xpath/">Php.XPath class</a>.</p>

		<h2>The database implementations:</h2>
		<ul>
			<li><a href="doc/phpxmldbDocumentation.php">PhpXmlDb Documentation</a> 
			- XML implementation of the API</li>
			<li><a href="dbasedb/doc/phpdbasedbDocumentation.php">PhpDbaseDb Documentation</a> 
			- Dbase implementation of the API</li>
		</ul>

		<h2>Supporting scripts:</h2>
		<ul>
			<li><a href="manager">Database Manager</a> 
			- Generic database manager for management of databases created by the API.</li>
			<li>Gui Examples
				<ul>
					<li><a href="dbutils/doc/XmlDbGuiDocumentation.php">Gui Template class</a>
					- Template class to ease the construction of database guis.</li>
					<li><a href="dbutils/examplegui/carols.php">Carols Database</a>
					- Simple example of the Gui Template class in action</li>
					<li><a href="manager">Database Manager</a> 
					- Further customised examples of the Gui Template class</li>
				</ul>
			</li>
			<li><a href="TestBench/ValidationTests/">Validation Tests</a> 
			- Unit test to prove the integrity of the implementation</li>
			<li><a href="dbutils/doc/">Database Utilities</a> 
			- Various API related utility scripts that are likely to come in useful.</li>		
		</ul>
	</div>
		
</body>

</html>