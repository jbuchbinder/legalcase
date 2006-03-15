<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

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

	$Id: inc_obj_reportgen.php,v 1.2 2006/03/15 15:11:41 mlutfy Exp $
*/

include_lcm('inc_obj_generic');

class LcmReportGen extends LcmObject {
	var $id_report;
	
	var $line_key_field;

	var $query;
	var $where;
	var $lines;
	var $columns;
	var $headers; // arrays with 'description', 'filter' and 'enum_type'
	var $totals;  // total for each column of the report

	var $options;
	var $journal;
	var $debug;
	var $line_count;

	function LcmReportGen($my_id_report, $my_debug = false) {
		$this->id_report = $my_id_report;

		$this->line_key_field = '';

		$this->query = '';
		$this->where = array();
		$this->lines = array();
		$this->columns = array();
		$this->headers = array();
		$this->totals = array();
		$this->specials = array();
		$this->special_count = 0;
		
		$this->options = array();
		$this->journal = array();
		$this->debug = $my_debug;
		$this->line_count = 0;
	}

	function getId() {
		if ((! isset($this->id_report)) || (! $this->id_report))
			lcm_panic("id_report is not set:" . htmlspecialchars($this->id_report));

		return $this->id_report;
	}

	function setLineKeyField($field) {
		$this->line_key_field = $field;

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getLineKeyField() {
		return $this->line_key_field;
	}

	function addSQL($string) {
		$this->query .= $string;

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getSQL() {
		return $this->query;
	}

	function addLine($string) {
		array_push($this->lines, $string);

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getLines() {
		return $this->lines;
	}

	function addColumn($string) {
		array_push($this->columns, $string);

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getColumns() {
		return $this->columns;
	}

	function addSpecial($string) {
		array_push($this->specials, $string);

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();

		return $this->special_count++;
	}

	function getSpecial($number) {
		if ($number > $this->special_count)
			lcm_panic("requested special is > " . $this->special_count);

		if (! isset($this->specials[$number]))
			lcm_panic("special # $number does not exist");

		return $this->specials[$number];
	}

	function getSpecialCount() {
		return $this->special_count;
	}

	function addHeader($description, $filter = '', $enum_type = '', $filter_special = '', $field_name = '') {
		$h = array(
				'description' => $description,
				'filter' => $filter, 
				'enum_type' => $enum_type,
				'filter_special' => $filter_special,
				'field_name' => $field_name
			);

		array_push($this->headers, $h);

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getHeaders() {
		return $this->headers;
	}

	function addWhere($string) {
		array_push($this->where, $string);

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getWhere() {
		return $this->where;
	}

	function addTotal($col, $value) {
		if (isset($this->totals[$col]))
			$this->totals[$col] += $value;
		else
			$this->totals[$col] = $value;

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getTotal($col) {
		return $this->totals[$col];
	}

	function setOption($name, $value) {
		$this->option[$name] = $value;

		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getOption($name) {
		if (isset($this->option[$name]))
			return $this->option[$name];
	
		return "";
	}

	function addComment($string) {
		if ($this->debug)
			$this->journal[] = lcm_getbacktrace();
	}

	function getJournal() {
		return $this->journal;
	}

	function incrementLine() {
		$this->line_count++;
	}

	function getLineCount() {
		return $this->line_count;
	}
}

class LcmReportGenUI extends LcmReportGen {
	var $exporter;
	var $ui;

	function LcmReportGenUI($my_id_report, $my_export = '', $my_debug = false) {
		$this->ui = $my_export;

		if ($my_export == 'csv') {
			include_lcm('inc_obj_export_csv');
			$this->exporter = new LcmExportCSV();
		} else {
			include_lcm('inc_obj_export_html');
			$this->exporter = new LcmExportHtml();
		}

		$this->LcmReportGen($my_id_report, $my_debug);
	}

	function printValue($val, $h, $css) {
		// TODO: Some preprocessing on the headers should be done 
		// here instead of in $exporter->printValue()
		
		$this->exporter->printValue($val, $h, $css);
	}

	function printStartLine() {
		$this->exporter->printStartLine();
	}

	function printEndLine() {
		$this->exporter->printEndLine();
	}
}

?>
