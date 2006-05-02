<?php

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

function tester_mail() {
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
		return @email('webmaster', $email, $subject, $texte);
	default:
		return @mail($email, $subject, $texte, $headers);
	}
}

function nettoyer_titre_email($titre) {
	$titre = ereg_replace("\n", ' ', supprimer_tags($titre));
	return ($titre);
}



?>
