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

	$Id: inc_obj_reportgen.php,v 1.6 2006/04/11 23:29:12 mlutfy Exp $
*/

include_lcm('inc_obj_generic');

class LcmReportGen extends LcmObject {
	var $id_report;
	
	var $line_key_field;
	var $rep_info;

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
	var $col_count;

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
		$this->col_count = 0;

		// Get report info
		$q = "SELECT *
				FROM lcm_report
				WHERE id_report = " . $my_id_report;
		
		$result = lcm_query($q);
		
		if (! ($this->rep_info = lcm_fetch_array($result)))
			lcm_panic("Report # $my_id_report doest not exist.");
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

	function setupReportLines() {
		$this->addComment("setupReportLines() called.");

		$q = "SELECT *
				FROM lcm_rep_line as l, lcm_fields as f
				WHERE id_report = " . $this->getId() . "
				AND l.id_field = f.id_field
				ORDER BY col_order, id_line ASC";
		
		$result = lcm_query($q);
		
		while ($row = lcm_fetch_array($result)) {
			$my_line_table = $row['table_name'];
			$this->addLine(prefix_field($row['table_name'], $row['field_name']));
			$this->addHeader(_Th($row['description']), $row['filter'], $row['enum_type'], '', $row['field_name']);
		
			if ($row['field_name'] == 'count(*)')
				$this->setOption('do_grouping', 'yes');
				// $do_grouping = true;
		}
		
		if (count($this->getLines()))
			return;

		//
		// No fields were specified: show them all (avoids errors)
		//
		if ($this->rep_info['line_src_type'] == 'table') {
			$q = "SELECT * 
					FROM lcm_fields 
					WHERE table_name = 'lcm_" . $this->rep_info['line_src_name'] . "'
					  AND field_name != 'count(*)'";
			$result = lcm_query($q);
	
			while ($row = lcm_fetch_array($result)) {
				$this->addLine(prefix_field($row['table_name'], $row['field_name']));
				$this->addHeader(_Th($row['description']), $row['filter'], $row['enum_type'], '', $row['field_name']);
			}
		} elseif ($this->rep_info['line_src_type'] == 'keyword') {
			$kwg = get_kwg_from_name($this->rep_info['line_src_name']);
			$this->addLine("k.title as 'TRAD'");
			$this->addHeader(_Th(remove_number_prefix($kwg['title'])), $kwg['filter'], $kwg['enum_type'], '', 'k.id_keyword'); // XXX not sure about id_keyword
		}
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

	function getRowCount() {
		return $this->line_count;
	}
}

class LcmReportGenUI extends LcmReportGen {
	var $exporter;
	var $ui;

	function LcmReportGenUI($my_id_report, $my_export = 'html', $my_debug = 0) {
		$this->ui = $my_export;

		switch ($my_export) {
			case 'csv':
				  include_lcm('inc_obj_export_csv');
				  $this->exporter = new LcmExportCSV();
				  break;
			case 'ods':
				  include_lcm('inc_obj_export_ods');
				  $this->exporter = new LcmExportODS();
				  break;
			default:
				  include_lcm('inc_obj_export_html');
				  $this->exporter = new LcmExportHtml();
		}

		$this->LcmReportGen($my_id_report, $my_debug);
	}

	function printStartDoc($title, $description, $helpref) {
		if ($this->ui == 'html') {
			$title = _Ti('title_rep_run') . $title;
			$this->setOption('headers_sent', 'yes');
		}
	
		$this->exporter->printStartDoc(remove_number_prefix($title), $description, $helpref);
	}

	function printHeaderValueStart() {
		$this->exporter->printHeaderValueStart();
	}

	function printHeaderValue($val) {
		$this->exporter->printHeaderValue($val);
	}

	function printHeaderValueEnd() {
		$this->exporter->printHeaderValueEnd();
	}

	function printValue($val, $h = array(), $css = '') {
		// TODO: Some preprocessing on the headers should be done 
		// here instead of in $exporter->printValue()

		if (! count($h))
			$h = $this->headers[$this->col_count];
		
		$this->exporter->printValue($val, $h, $css);
		$this->col_count++;
	}

	function printStartLine() {
		$this->exporter->printStartLine();
	}

	function printEndLine() {
		$this->exporter->printEndLine();
		$this->col_count = 0;
		// $this->line_count++; ? (may be better than incrementing explicitely)
	}

	function printEndDoc() {
		$this->exporter->printEndDoc();
	}
}

?>
