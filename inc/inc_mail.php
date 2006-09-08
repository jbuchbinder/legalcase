<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

	This file is a derivative of the SPIP 1.8 inc/inc_mail.php3
	(http://www.spip.net). Licensed under the GNU GPL (C) 2001-2005 
	Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James

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

	$Id: inc_mail.php,v 1.7 2006/09/08 14:29:55 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_MAIL')) return;
define('_INC_MAIL', '1');

$GLOBALS['queue_mails'] = '';

function envoyer_queue_mails() {
	global $queue_mails;
	if (!$queue_mails) return;
	reset($queue_mails);
	while (list(, $val) = each($queue_mails)) {
		$email = $val['email'];
		$sujet = $val['sujet'];
		$texte = $val['texte'];
		$headers = $val['headers'];
		@mail($email, $sujet, $texte, $headers);
	}
}

function server_can_send_email() {
	global $hebergeur;
	$test_mail = true;
	if ($hebergeur == 'free') $test_mail = false;
	return $test_mail;
}

function send_email($email, $subject, $texte, $from = "", $headers = "") {
	global $hebergeur, $queue_mails, $flag_wordwrap, $os_serveur;
	include_lcm('inc_filters');

	if (!$from) {
		$email_envoi = read_meta("email_sender");
		$from = is_valid_email($email_envoi) ? $email_envoi : $email;
	}

	if (!is_valid_email($email)) return false;

	lcm_debug("mail ($email): $subject");
	$charset = read_meta('charset');

	$headers = "From: $from\n".
		"MIME-Version: 1.0\n".
		"Content-Type: text/plain; charset=$charset\n".
		"Content-Transfer-Encoding: 8bit\n$headers";

	$texte = filtrer_entites($texte);
	$subject = filtrer_entites($subject);

	// fignoler ce qui peut l'etre...
	if ($charset <> 'utf-8') {
		$texte = str_replace("&#8217;", "'", $texte);
		$subject = str_replace("&#8217;", "'", $subject);
	}

	// encoder le sujet si possible selon la RFC
	if($GLOBALS['flag_multibyte'] AND @mb_internal_encoding($charset))
		$subject = mb_encode_mimeheader($subject, $charset, 'Q');

	if ($flag_wordwrap) $texte = wordwrap($texte);

	if ($os_serveur == 'windows') {
		$texte = ereg_replace ("\r*\n","\r\n", $texte);
		$headers = ereg_replace ("\r*\n","\r\n", $headers);
	}

	switch($hebergeur) {
	case 'lycos':
		$queue_mails[] = array(
			'email' => $email,
			'sujet' => $subject,
			'texte' => $texte,
			'headers' => $headers);
		return true;
	case 'free':
		return false;
	case 'online':
		if (! ($ret = @email('webmaster', $email, $subject, $texte)))
			lcm_log("ERROR mail: (online) returned false");

		return $ret;
	default:
		if (! ($ret = @mail($email, $subject, $texte, $headers)))
			lcm_log("ERROR mail: (default) returned false");

		return $ret;
	}
}

function nettoyer_titre_email($titre) {
	$titre = ereg_replace("\n", ' ', supprimer_tags($titre));
	return ($titre);
}



?>
