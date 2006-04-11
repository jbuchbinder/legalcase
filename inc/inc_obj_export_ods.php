<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

	This program is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published by the
	Free Software Foundation; either version 2 of the License, or (at your
	option) any later version.

	This program is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
	or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
	for more details.

	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	59 Temple Place, Suite 330, Boston, MA  02111-1307, USA

	$Id: inc_obj_export_ods.php,v 1.4 2006/04/11 23:27:59 mlutfy Exp $
*/

// Not needed for now, but maybe later?
// include_lcm('inc_obj_export_generic');

include_lcm('inc_access'); // for create_random_password()

class LcmExportODS /* extends LcmExportObject */ {

	var $mimetype;
	var $dir;
	var $fcontent;
	var $zipfile;
	var $zipname;

	function LcmExportODS() {
		// $this->LcmExportObject();

		global $author_session;

		$this->mimetype = 'application/vnd.oasis.opendocument.spreadsheet';

		// Create a random temporary directory
		do {
			$id = create_random_password(15, time());
			$file = 'inc/data/report_' . $author_session['username'] . '_' . $id;
			// TODO: use LcmDataDir ?
		} while (! mkdir($file));

		$this->dir = $file;

		if (! mkdir($file . '/META-INF'))
			lcm_panic("Could not create dir: $file/META-INF. " . $GLOBALS['lcm_errormsg']);

		if (! ($f = fopen($file . '/META-INF/manifest.xml', 'w')))
			lcm_panic("Could not create META-INF/manifest.xml. " . $GLOBALS['lcm_errormsg']);

		$contents = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<manifest:manifest xmlns:manifest=\"urn:oasis:names:tc:opendocument:xmlns:manifest:1.0\">
<manifest:file-entry manifest:media-type=\"application/vnd.oasis.opendocument.spreadsheet\" manifest:full-path=\"/\"/>
<manifest:file-entry manifest:media-type=\"text/xml\" manifest:full-path=\"content.xml\"/>
<manifest:file-entry manifest:media-type=\"text/xml\" manifest:full-path=\"styles.xml\"/>
<manifest:file-entry manifest:media-type=\"text/xml\" manifest:full-path=\"meta.xml\"/>
</manifest:manifest>\n";

		fwrite($f, $contents);
		fclose($f);

		// write the 'mimetype' file
		if (! ($f = fopen($this->dir . '/mimetype', 'w')))
			lcm_panic("Could not create 'mimetype' file. " . $GLOBALS['lcm_errormsg']);

		fwrite($f, $this->mimetype);
		fclose($f);

		// write the meta.xml file
		$meta = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<office:document-meta'
			. ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
			. ' xmlns:xlink="http://www.w3.org/1999/xlink"'
			. ' xmlns:dc="http://purl.org/dc/elements/1.1/"'
			. ' xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"'
			. ' xmlns:ooo="http://openoffice.org/2004/office" office:version="1.0">'
			. '<office:meta>'
				. '<meta:generator>Legal Case Management '
					. $GLOBALS['lcm_version_shown'] . '/'
					. $GLOBALS['lcm_version'] . '/'
					. 'db' . $GLOBALS['lcm_db_version']
				. '</meta:generator>'
				. '<meta:creation-date>2006-04-05T19:41:19</meta:creation-date>' // FIXME
				. '<dc:date>2006-04-05T19:42:20</dc:date>' // FIXME
				. '<dc:language>en-US</dc:language>' // FIXME
			. '</office:meta>'
			. '</office:document-meta>' . "\n";

		if (! ($f = fopen($this->dir . '/meta.xml', 'w')))
			lcm_panic("Could not create meta.xml. " . $GLOBALS['lcm_errormsg']);

		fwrite($f, $meta);
		fclose($f);

	}

	function printStartDoc($title, $description, $helpref) {
		$title = trim($title);
		$description = trim($description);

		// Prepare the ZIP file
		/*
		@include("pear/Archive/Zip.php");

		if (! class_exists("Archive_Zip"))
			lcm_panic("You must have PEAR installed (Archive/Zip.php)");
		*/
		include_lcm('inc_pclzip');

		// Zip filename must use random ID, to avoid overwriting existing reports
		// not catastrophic if that happens, but annoyance nonetheless.
		$this->zipname = $this->dir . '.ods';
		// $this->zipfile = new Archive_Zip($this->zipname);
		$this->zipfile = new PclZip($this->zipname);

		$filename = preg_replace('/\s+/', '_', $title);

		header("Content-Type: " . $this->mimetype);
		header('Content-Disposition: filename="' . $filename . '.ods"');
		header("Content-Description: " . ($description ? $description : $title));
		header("Content-Transfer-Encoding: binary");

		// TODO: show description in the XML, near $title

		// 
		// Write the styles.xml file
		//
		$styles = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<office:document-styles'
			. ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
			. ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
			. ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
			. ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
			. ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'
			. ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
			. ' xmlns:xlink="http://www.w3.org/1999/xlink"'
			. ' xmlns:dc="http://purl.org/dc/elements/1.1/"'
			. ' xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"'
			. ' xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"'
			. ' xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"'
			. ' xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0"'
			. ' xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0"'
			. ' xmlns:math="http://www.w3.org/1998/Math/MathML"'
			. ' xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0"'
			. ' xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0"'
			. ' xmlns:ooo="http://openoffice.org/2004/office"'
			. ' xmlns:ooow="http://openoffice.org/2004/writer"'
			. ' xmlns:oooc="http://openoffice.org/2004/calc"'
			. ' xmlns:dom="http://www.w3.org/2001/xml-events"'
			. ' office:version="1.0">' . "\n";

		$styles .= '<office:styles>'
			. ' <number:currency-style style:name="N106P0" style:volatile="true">'
			. ' <number:number number:decimal-places="2" number:min-integer-digits="1" number:grouping="true" />'
			. ' <number:text></number:text>'
			// FIXME : country codes (may need to include in lang files?)
			. ' <number:currency-symbol'
				. ' number:language="' . $GLOBALS['lcm_lang'] . '"'
				. ' number:country="' . strtoupper($GLOBALS['lcm_lang']) . '">'
			. read_meta('currency')
			. '</number:currency-symbol>'
			. '</number:currency-style>'
			. '<number:currency-style style:name="N106">'
			. '<style:text-properties fo:color="#ff0000" />'
			. '<number:text>-</number:text>'
			. '<number:number number:decimal-places="2" number:min-integer-digits="1" number:grouping="true" />'
			. '<number:text></number:text>'
			. ' <number:currency-symbol'
				. ' number:language="' . $GLOBALS['lcm_lang'] . '"'
				. ' number:country="' . strtoupper($GLOBALS['lcm_lang']) . '">'
			. read_meta('currency')
			. '</number:currency-symbol>'
			. '<style:map style:condition="value()&gt;=0" style:apply-style-name="N106P0" />'
			. '</number:currency-style>'
			. '</office:styles>';

		$styles .= '</office:document-styles>' . "\n";

		$f = fopen($this->dir . '/styles.xml', 'w');
		fwrite($f, $styles);
		fclose($f);

		// content
		$content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
			. ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
			. ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
			. ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
			. ' xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'
			. ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
			. ' xmlns:xlink="http://www.w3.org/1999/xlink"'
			. ' xmlns:dc="http://purl.org/dc/elements/1.1/"'
			. ' xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"'
			. ' xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"'
			. ' xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"'
			. ' xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0"'
			. ' xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0"'
			. ' xmlns:math="http://www.w3.org/1998/Math/MathML"'
			. ' xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0"'
			. ' xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0"'
			. ' xmlns:ooo="http://openoffice.org/2004/office"'
			. ' xmlns:ooow="http://openoffice.org/2004/writer"'
			. ' xmlns:oooc="http://openoffice.org/2004/calc"'
			. ' xmlns:dom="http://www.w3.org/2001/xml-events"'
			. ' xmlns:xforms="http://www.w3.org/2002/xforms"'
			. ' xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
			. ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			. ' office:version="1.0">';

		$content .= '<office:scripts />'
			. '<office:font-face-decls>'
				. '<style:font-face style:name="Verdana1" svg:font-family="Verdana" style:font-pitch="variable" />'
				. '<style:font-face style:name="Verdana" svg:font-family="Verdana" style:font-family-generic="swiss" style:font-pitch="variable" />'
			. '</office:font-face-decls>'
			. '<office:automatic-styles>'
				. '<style:style style:name="co1" style:family="table-column">'
					. '<style:table-column-properties fo:break-before="auto" style:column-width="2.267cm" />'
				. '</style:style>'
				. '<style:style style:name="ro1" style:family="table-row">'
					. '<style:table-row-properties style:row-height="0.453cm" fo:break-before="auto" style:use-optimal-row-height="true" />'
				. '</style:style>'
				. '<style:style style:name="ta1" style:family="table" style:master-page-name="Default">'
					. '<style:table-properties table:display="true" style:writing-mode="lr-tb" />'
				. '</style:style>'
				. '<style:style style:name="ce1" style:family="table-cell" style:parent-style-name="Default" style:data-style-name="N0" />'
			. '</office:automatic-styles>';

		$content .= '<office:body>'
			. '<office:spreadsheet>'
			. '<table:table table:name="Sheet1" table:style-name="ta1" table:print="false">'
				. '<table:table-column table:style-name="co1" table:number-columns-repeated="4" table:default-cell-style-name="Default" />'
				. '<table:table-row table:style-name="ro1">'
					. '<table:table-cell office:value-type="string">'
						. '<text:p>' . $title . '</text:p>'
					. '</table:table-cell>'
					. '<table:table-cell table:number-columns-repeated="3" />'
				. '</table:table-row>'
				. '<table:table-row table:style-name="ro1">'
					. '<table:table-cell table:number-columns-repeated="4" />'
				. '</table:table-row>';

		if (! ($this->fcontent = fopen($this->dir . '/content.xml', 'w')))
			lcm_panic("Could not open content.xml. " . $GLOBALS['lcm_errormsg']);

		fwrite($this->fcontent, $content);

		// leave $fcontent open until we are finished
	}

	function printHeaderValueStart() {
		$this->printStartLine();
	}

	function printHeaderValue($val) {
		$h = array('filter' => 'text');
		$this->printValue($val, $h);
	}

	function printHeaderValueEnd() {
		$this->printEndLine();
	}

	function printValue($val, $h, $css = '') {
		$xml = '<table:table-cell ';

		$align = '';
		$format = 'office:value-type="string"';

		// Maybe formalise 'time_length' filter, but check SQL pre-filter also
		if ($h['filter_special'] == 'time_length') {
			// $val = format_time_interval_prefs($val);
			$val = format_time_interval($val, true, '%.2f');
			if (! $val)
				$val = 0;
		} elseif ($h['description'] == 'time_input_length') {
			$val = format_time_interval($val, true, '%.2f');
			if (! $val)
				$val = 0;
		}

		switch ($h['filter']) {
			case 'date':
				if ($val)
					$val = format_date($val, 'short'); // not tested XXX
				break;
			case 'currency':
				if ($val)
					$val = format_money($val); // not tested XXX
				else
					$val = 0;
				break;
			case 'number':
				if (! $val)
					$val = 0;

				$align = 'align="right"'; // not used
				$format = 'office:value-type="float" office:value="' . $val . '"';

				break;
		}

		$xml .= $format . '>';
		$xml .= '<text:p>' . $val . '</text:p>';
		$xml .= '</table:table-cell>';

		fwrite($this->fcontent, $xml);
	}

	function printStartLine() {
		fwrite($this->fcontent, '<table:table-row table:style-name="ro1">');
	}

	function printEndLine() {
		fwrite($this->fcontent, '</table:table-row>');
	}
	
	function printEndDoc() {
		// TODO: show report footer?
	
		$content =  '</table:table>'
			. '</office:spreadsheet>'
			. '</office:body>'
			. '</office:document-content>' . "\n"; 

		fwrite($this->fcontent, $content);
		fclose($this->fcontent);

		$all_files = array (
			$this->dir . '/content.xml',
			$this->dir . '/META-INF/',
			$this->dir . '/meta.xml',
			$this->dir . '/mimetype',
			$this->dir . '/styles.xml'
		);

		/*
		$params = array (
			'remove_path' => $this->dir
		);

		$this->zipfile->create($all_files, $params);
		*/
		$this->zipfile->create($all_files, '', $this->dir);

		
		// Send it to the user for download
		if (! ($f = fopen($this->zipname, 'r'))) 
			lcm_panic("Failed to open " . $this->zipname . ": " . $GLOBALS['lcm_errormsg']);

		while (($data = fread($f, filesize($this->zipname))))
			echo $data;

		fclose($f);

		// TODO: Delete temporary files
		unlink($this->dir . '/content.xml');
		unlink($this->dir . '/styles.xml');
		unlink($this->dir . '/meta.xml');
		unlink($this->dir . '/mimetype');
		unlink($this->dir . '/META-INF/manifest.xml');
		rmdir($this->dir  . '/META-INF/');
		rmdir($this->dir);

	}
}

?>
