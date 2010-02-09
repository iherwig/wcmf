//////////////////////////////////////////////////////////////////
// xmldbutils.js
//
// Javascript functions to assist with the client side output from 
// the xmldb.php library of functions.
//
// Author: Nigel Swinson
// Date: July 2001
// Copyright (c) 2001

// Markup flat strings as html by inserting <p> tags at double line breaks and
// <br> tags at single line breaks.
function MarkupParagraphs(PlainTextString) {
	// Quick way out.
	if (!PlainTextString) return "";

	// Replace all \n\n combinations with </p><p>
	var MarkedUpString = PlainTextString.replace(/\n\n/g, "</p><p>");

	// Replace all \n occurences with <br>
	MarkedUpString = MarkedUpString.replace(/\n/g,"<br>\n");

	// If we have a string, then prefix it with <p> and postfix it with </p>
	MarkedUpString = "<p>" + MarkedUpString + "</p>";

	// ###  It might be a good idea ot preserve as much preformatted text as possible,
	// you can do this by catching all lines that start with a space, and treat them
	// as prefromatted text.
	// Also any lines containing only whitespace should be modified to contain nothing
	// And then three blank lines in a row is a <br> between paragraphs.

	// All done :o)
	return MarkedUpString;
}


