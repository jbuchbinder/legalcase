<?php

// Execute this file only once
if (defined("_INC_UPGRADE")) return;
define("_INC_UPGRADE", "1");

include('inc/inc_version.php');
include_lcm('inc_meta');
include_lcm('inc_db');

function upgrade_version ($version, $test = true) {
	if ($test) {
		lcm_query("REPLACE lcm_meta (name, value) VALUES ('version_lcm', '$version')");
		lcm_log("upgrading database to version: $version");
	} else {
		include_lcm('inc_lang');
		echo _T('install_warning_update_impossible', array('version' => $version));
		exit;
	}
}

function upgrade_database() {
	global $lcm_version;
	$log = "";

	// Read the current version
	$lcm_version_current = 0.0;
	// $result = lcm_query("SELECT valeur FROM spip_meta WHERE name='version_lcm'");
	// if ($result) if ($row = spip_fetch_array($result)) $lcm_version_current = (double) $row['valeur'];
	$lcm_version_current = read_meta('version_lcm');
	// echo "VERSION = $version \n";

	// If there is no version mentioned in lcm_meta, then it is a new installation
	// and therefore there is no need to upgrade.
	if (!$lcm_version_current) {
		$lcm_version_current = $lcm_version;
		upgrade_version($lcm_version_current);
		return $log;
	}

	//
	// Verify the rights to modify the database
	//

	lcm_query("DROP TABLE IF EXISTS spip_test");
	lcm_query("CREATE TABLE lcm_test (a INT)");
	lcm_query("ALTER TABLE lcm_test ADD b INT");
	lcm_query("INSERT INTO lcm_test (b) VALUES (1)");
	$result = lcm_query("SELECT b FROM lcm_test");
	lcm_query("ALTER TABLE lcm_test DROP b");

	if (!$result) {
		$log .= "User does not have the right to modify the database\n";
		return $log;
	}

	//
	// Upgrade the database accordingly to the current version
	//

/* [ML] I'm leaving this because it can provide us with interesting ideas
	if ($lcm_version_current < 0.98) {
		lcm_query("ALTER TABLE spip_articles ADD maj TIMESTAMP");
		lcm_query("ALTER TABLE spip_articles ADD export VARCHAR(10) DEFAULT 'oui'");
		lcm_query("ALTER TABLE spip_articles ADD images TEXT DEFAULT ''");
		lcm_query("ALTER TABLE spip_articles ADD date_redac datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		lcm_query("ALTER TABLE spip_articles DROP INDEX id_article");
		lcm_query("ALTER TABLE spip_articles ADD INDEX id_rubrique (id_rubrique)");
		lcm_query("ALTER TABLE spip_articles ADD visites INTEGER DEFAULT '0' NOT NULL");
		lcm_query("ALTER TABLE spip_articles ADD referers BLOB NOT NULL");

		lcm_query("ALTER TABLE spip_auteurs ADD maj TIMESTAMP");
		lcm_query("ALTER TABLE spip_auteurs ADD pgp BLOB NOT NULL");

		lcm_query("ALTER TABLE spip_auteurs_articles ADD INDEX id_auteur (id_auteur), ADD INDEX id_article (id_article)");
	
		lcm_query("ALTER TABLE spip_rubriques ADD maj TIMESTAMP");
		lcm_query("ALTER TABLE spip_rubriques ADD export VARCHAR(10) DEFAULT 'oui', ADD id_import BIGINT DEFAULT '0'");
	
		lcm_query("ALTER TABLE spip_breves ADD maj TIMESTAMP");
		lcm_query("ALTER TABLE spip_breves DROP INDEX id_breve");
		lcm_query("ALTER TABLE spip_breves DROP INDEX id_breve_2");
		lcm_query("ALTER TABLE spip_breves ADD INDEX id_rubrique (id_rubrique)");
	
		lcm_query("ALTER TABLE spip_forum ADD ip VARCHAR(16)");
		lcm_query("ALTER TABLE spip_forum ADD maj TIMESTAMP");
		lcm_query("ALTER TABLE spip_forum DROP INDEX id_forum");
		lcm_query("ALTER TABLE spip_forum ADD INDEX id_parent (id_parent), ADD INDEX id_rubrique (id_rubrique), ADD INDEX id_article(id_article), ADD INDEX id_breve(id_breve)");
		upgrade_version (0.98);
	}

	if ($lcm_version_current < 0.99) {
	
		$query = "SELECT DISTINCT id_article FROM spip_forum WHERE id_article!=0 AND id_parent=0";
		$result = lcm_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_article);
			$id_article = $row['id_article'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_article=$id_article";
			for (;;) {
				$result2 = lcm_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_article[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_article = join(',', $forums_article);
			$query3 = "UPDATE spip_forum SET id_article=$id_article WHERE id_forum IN ($forums_article)";
			lcm_query($query3);
		}
	
		$query = "SELECT DISTINCT id_breve FROM spip_forum WHERE id_breve!=0 AND id_parent=0";
		$result = lcm_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_breve);
			$id_breve = $row['id_breve'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_breve=$id_breve";
			for (;;) {
				$result2 = lcm_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_breve[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_breve = join(',', $forums_breve);
			$query3 = "UPDATE spip_forum SET id_breve=$id_breve WHERE id_forum IN ($forums_breve)";
			lcm_query($query3);
		}
	
		$query = "SELECT DISTINCT id_rubrique FROM spip_forum WHERE id_rubrique!=0 AND id_parent=0";
		$result = lcm_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_rubrique);
			$id_rubrique = $row['id_rubrique'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_rubrique=$id_rubrique";
			for (;;) {
				$result2 = lcm_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_rubrique[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_rubrique = join(',', $forums_rubrique);
			$query3 = "UPDATE spip_forum SET id_rubrique=$id_rubrique WHERE id_forum IN ($forums_rubrique)";
			lcm_query($query3);
		}
		upgrade_version (0.99);
	}

	if ($lcm_version_current < 0.997) {
		lcm_query("DROP TABLE spip_index");
		upgrade_version (0.997);
	}

	if ($lcm_version_current < 0.999) {
		global $htsalt;
		lcm_query("ALTER TABLE spip_auteurs CHANGE pass pass tinyblob NOT NULL");
		lcm_query("ALTER TABLE spip_auteurs ADD htpass tinyblob NOT NULL");
		$query = "SELECT id_auteur, pass FROM spip_auteurs WHERE pass!=''";
		$result = lcm_query($query);
		while (list($id_auteur, $pass) = spip_fetch_array($result)) {
			$htpass = generer_htpass($pass);
			$pass = md5($pass);
			lcm_query("UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur");
		}
		upgrade_version (0.999);
	}
	
	if ($lcm_version_current < 1.01) {
		lcm_query("UPDATE spip_forum SET statut='publie' WHERE statut=''");
		upgrade_version (1.01);
	}
	
	if ($lcm_version_current < 1.02) {
		lcm_query("ALTER TABLE spip_forum ADD id_auteur BIGINT DEFAULT '0' NOT NULL");
		upgrade_version (1.02);
	}

	if ($lcm_version_current < 1.03) {
		lcm_query("DROP TABLE spip_maj");
		upgrade_version (1.03);
	}

	if ($lcm_version_current < 1.04) {
		lcm_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3)");
		upgrade_version (1.04);
	}

	if ($lcm_version_current < 1.05) {
		lcm_query("DROP TABLE spip_petition");
		lcm_query("DROP TABLE spip_signatures_petition");
		upgrade_version (1.05);
	}

	if ($lcm_version_current < 1.1) {
		lcm_query("DROP TABLE spip_petition");
		lcm_query("DROP TABLE spip_signatures_petition");
		upgrade_version (1.1);
	}

	// Correction de l'oubli des modifs creations depuis 1.04
	if ($lcm_version_current < 1.204) {
		lcm_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3) NOT NULL");
		lcm_query("ALTER TABLE spip_forum ADD id_message bigint(21) NOT NULL");
		lcm_query("ALTER TABLE spip_forum ADD INDEX id_message (id_message)");
		lcm_query("ALTER TABLE spip_auteurs ADD en_ligne datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		lcm_query("ALTER TABLE spip_auteurs ADD imessage VARCHAR(3) not null");
		lcm_query("ALTER TABLE spip_auteurs ADD messagerie VARCHAR(3) not null");
		upgrade_version (1.204);
	}

	if ($lcm_version_current < 1.207) {
		lcm_query("ALTER TABLE spip_rubriques DROP INDEX id_rubrique");
		lcm_query("ALTER TABLE spip_rubriques ADD INDEX id_parent (id_parent)");
		lcm_query("ALTER TABLE spip_rubriques ADD statut VARCHAR(10) NOT NULL");
		// Declencher le calcul des rubriques publiques
		lcm_query("REPLACE spip_meta (nom, valeur) VALUES ('calculer_rubriques', 'oui')");
		upgrade_version (1.207);
	}

	[ML] ...... (removed code) ....

	if ($lcm_version_current < 1.414) {
		// Forum par defaut "en dur" dans les spip_articles
		// -> non, prio (priori), pos (posteriori), abo (abonnement)
		include_ecrire ("inc_meta.php3");
		$accepter_forum = substr(lire_meta("forums_publics"),0,3) ;
		$query = "ALTER TABLE spip_articles CHANGE accepter_forum accepter_forum CHAR(3) NOT NULL";
		$result = lcm_query($query);
		$query = "UPDATE spip_articles SET accepter_forum='$accepter_forum' WHERE accepter_forum != 'non'";
		$result = lcm_query($query);
		upgrade_version (1.414);
	}

	if ($lcm_version_current < 1.417) {
		lcm_query("ALTER TABLE spip_syndic_articles DROP date_index");
		upgrade_version (1.417);
	}

	if ($lcm_version_current < 1.418) {
		$query = "SELECT * FROM spip_auteurs WHERE statut = 'admin' AND email != '' ORDER BY id_auteur LIMIT 0,1";
		$result = lcm_query($query);
		if ($webmaster = spip_fetch_array($result)) {
			include_ecrire("inc_meta.php3");
			ecrire_meta('email_webmaster', $webmaster['email']);
			ecrire_metas();
		}
		upgrade_version (1.418);
	}

	if ($lcm_version_current < 1.419) {
		$query = "ALTER TABLE spip_auteurs ADD alea_actuel TINYTEXT DEFAULT ''";
		lcm_query($query);
		$query = "ALTER TABLE spip_auteurs ADD alea_futur TINYTEXT DEFAULT ''";
		lcm_query($query);
		$query = "UPDATE spip_auteurs SET alea_futur = FLOOR(32000*RAND())";
		lcm_query($query);
		upgrade_version (1.419);
	}

	if ($lcm_version_current < 1.420) {
		$query = "UPDATE spip_auteurs SET alea_actuel='' WHERE statut='nouveau'";
		lcm_query($query);
		upgrade_version (1.420);
	}
	
	if ($lcm_version_current < 1.421) {
		$query = "ALTER TABLE spip_articles ADD auteur_modif bigint(21) DEFAULT '0' NOT NULL";
		lcm_query($query);
		$query = "ALTER TABLE spip_articles ADD date_modif datetime DEFAULT '0000-00-00 00:00:00' NOT NULL";
		lcm_query($query);
		upgrade_version (1.421);
	}

	if ($lcm_version_current < 1.432) {
		lcm_query("ALTER TABLE spip_articles DROP referers");
		$query = "ALTER TABLE spip_articles ADD referers INTEGER DEFAULT '0' NOT NULL";
		lcm_query($query);
		$query = "ALTER TABLE spip_articles ADD popularite INTEGER DEFAULT '0' NOT NULL";
		lcm_query($query);
		upgrade_version (1.432);
	}

	if ($lcm_version_current < 1.436) {
		$query = "ALTER TABLE spip_documents ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL";
		lcm_query($query);
		upgrade_version (1.436);
	}

	if ($lcm_version_current < 1.437) {
		lcm_query("ALTER TABLE spip_visites ADD maj TIMESTAMP");
		lcm_query("ALTER TABLE spip_visites_referers ADD maj TIMESTAMP");
		upgrade_version (1.437);
	}

	if ($lcm_version_current < 1.438) {
		lcm_query("ALTER TABLE spip_articles ADD INDEX id_secteur (id_secteur)");
		lcm_query("ALTER TABLE spip_articles ADD INDEX statut (statut, date)");
		upgrade_version (1.438);
	}

	if ($lcm_version_current < 1.439) {
		lcm_query("ALTER TABLE spip_syndic ADD INDEX statut (statut, date_syndic)");
		lcm_query("ALTER TABLE spip_syndic_articles ADD INDEX statut (statut)");
		lcm_query("ALTER TABLE spip_syndic_articles CHANGE url url VARCHAR(255) NOT NULL");
		lcm_query("ALTER TABLE spip_syndic_articles ADD INDEX url (url)");
		upgrade_version (1.439);
	}

	if ($lcm_version_current < 1.440) {
		lcm_query("ALTER TABLE spip_visites_temp CHANGE ip ip INTEGER UNSIGNED NOT NULL");
		upgrade_version (1.440);
	}

	if ($lcm_version_current < 1.441) {
		lcm_query("ALTER TABLE spip_visites_temp CHANGE date date DATE NOT NULL");
		lcm_query("ALTER TABLE spip_visites CHANGE date date DATE NOT NULL");
		lcm_query("ALTER TABLE spip_visites_referers CHANGE date date DATE NOT NULL");
		upgrade_version (1.441);
	}

	if ($lcm_version_current < 1.442) {
		$query = "ALTER TABLE spip_auteurs ADD prefs TINYTEXT NOT NULL";
		lcm_query($query);
		upgrade_version (1.442);
	}

	if ($lcm_version_current < 1.443) {
		lcm_query("ALTER TABLE spip_auteurs CHANGE login login VARCHAR(255) BINARY NOT NULL");
		lcm_query("ALTER TABLE spip_auteurs CHANGE statut statut VARCHAR(255) NOT NULL");
		lcm_query("ALTER TABLE spip_auteurs ADD INDEX login (login)");
		lcm_query("ALTER TABLE spip_auteurs ADD INDEX statut (statut)");
		upgrade_version (1.443);
	}

	if ($lcm_version_current < 1.444) {
		lcm_query("ALTER TABLE spip_syndic ADD moderation VARCHAR(3) NOT NULL");
		upgrade_version (1.444);
	}

	if ($lcm_version_current < 1.457) {
		lcm_query("DROP TABLE spip_visites");
		lcm_query("DROP TABLE spip_visites_temp");
		lcm_query("DROP TABLE spip_visites_referers");
		creer_base(); // crade, a ameliorer :-((
		upgrade_version (1.457);
	}

	if ($lcm_version_current < 1.458) {
		lcm_query("ALTER TABLE spip_auteurs ADD cookie_oubli TINYTEXT NOT NULL");
		upgrade_version (1.458);
	}

	if ($lcm_version_current < 1.459) {
		$result = lcm_query("SELECT type FROM spip_mots GROUP BY type");
		while ($row = spip_fetch_array($result)) {
			$type = addslashes($row['type']);
			$res = lcm_query("SELECT * FROM spip_groupes_mots
				WHERE titre='$type'");
			if (spip_num_rows($res) == 0) {
				lcm_query("INSERT IGNORE INTO spip_groupes_mots 
					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, admin, 1comite, 6forum)
					VALUES ('$type', 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
				if ($id_groupe = spip_insert_id()) 
					lcm_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type='$type'");
			}
		}
		lcm_query("UPDATE spip_articles SET popularite=0");
		upgrade_version (1.459);
	}

	if ($lcm_version_current < 1.460) {
		// remettre les mots dans les groupes dupliques par erreur
		// dans la precedente version du paragraphe de maj 1.459
		// et supprimer ceux-ci
		$result = lcm_query("SELECT * FROM spip_groupes_mots ORDER BY id_groupe");
		while ($row = spip_fetch_array($result)) {
			$titre = addslashes($row['titre']);
			if (! $vu[$titre] ) {
				$vu[$titre] = true;
				$id_groupe = $row['id_groupe'];
				lcm_query ("UPDATE spip_mots SET id_groupe=$id_groupe WHERE type='$titre'");
				lcm_query ("DELETE FROM spip_groupes_mots WHERE titre='$titre' AND id_groupe<>$id_groupe");
			}
		}
		upgrade_version (1.460);
	}

	if ($lcm_version_current < 1.462) {
		lcm_query("UPDATE spip_types_documents SET inclus='embed' WHERE inclus!='non' AND extension IN ".
			"('aiff', 'asf', 'avi', 'mid', 'mov', 'mp3', 'mpg', 'ogg', 'qt', 'ra', 'ram', 'rm', 'swf', 'wav', 'wmv')");
		upgrade_version (1.462);
	}

	if ($lcm_version_current < 1.463) {
		lcm_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE");
		lcm_query("ALTER TABLE spip_visites_temp ADD maj TIMESTAMP");
		lcm_query("ALTER TABLE spip_referers_temp ADD maj TIMESTAMP");
		upgrade_version (1.463);
	}

	// l'upgrade < 1.462 ci-dessus etait fausse, d'ou correctif
	if (($lcm_version_current < 1.464) AND ($lcm_version_current >= 1.462)) {
		$res = lcm_query("SELECT id_type, extension FROM spip_types_documents WHERE id_type NOT IN (1,2,3)");
		while ($row = spip_fetch_array($res)) {
			$extension = $row['extension'];
			$id_type = $row['id_type'];
			lcm_query("UPDATE spip_documents SET id_type=$id_type
				WHERE fichier like '%.$extension'");
		}
		upgrade_version (1.464);
	}

	if ($lcm_version_current < 1.465) {
		lcm_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE NOT NULL");
		upgrade_version (1.465);
	}

	if ($lcm_version_current < 1.466) {
		lcm_query("ALTER TABLE spip_auteurs ADD source VARCHAR(10) DEFAULT 'spip' NOT NULL");
		upgrade_version (1.466);
	}

	if ($lcm_version_current < 1.468) {
		lcm_query("ALTER TABLE spip_auteurs ADD INDEX en_ligne (en_ligne)");
		lcm_query("ALTER TABLE spip_forum ADD INDEX statut (statut, date_heure)");
		upgrade_version (1.468);
	}

	if ($lcm_version_current < 1.470) {
		if ($lcm_version_current >= 1.467) {	// annule les "listes de diff"
			lcm_query("DROP TABLE spip_listes");
			lcm_query("ALTER TABLE spip_auteurs DROP abonne");
			lcm_query("ALTER TABLE spip_auteurs DROP abonne_pass");
		}
		upgrade_version (1.470);
	}

	if ($lcm_version_current < 1.471) {
		if ($lcm_version_current >= 1.470) {	// annule les "maj"
			lcm_query("ALTER TABLE spip_auteurs_articles DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_auteurs_rubriques DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_auteurs_messages DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_documents_articles DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_documents_rubriques DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_documents_breves DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_mots_articles DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_mots_breves DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_mots_rubriques DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_mots_syndic DROP maj TIMESTAMP");
			lcm_query("ALTER TABLE spip_mots_forum DROP maj TIMESTAMP");
		}
		upgrade_version (1.471);
	}

	if ($lcm_version_current < 1.472) {
		lcm_query("ALTER TABLE spip_referers ADD visites_jour INTEGER UNSIGNED NOT NULL");
		upgrade_version (1.472);
	}

	if ($lcm_version_current < 1.473) {
		lcm_query("UPDATE spip_syndic_articles SET url = REPLACE(url, '&amp;', '&')");
		lcm_query("UPDATE spip_syndic SET url_site = REPLACE(url_site, '&amp;', '&')");
		upgrade_version (1.473);
	}

	if ($lcm_version_current < 1.600) {
		include_ecrire('inc_index.php3');
		purger_index();
		creer_liste_indexation();
		upgrade_version (1.600);
	}

	if ($lcm_version_current < 1.601) {
		lcm_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
		upgrade_version (1.601);
	}

	if ($lcm_version_current < 1.603) {
		// supprimer les fichiers deplaces
		@unlink('inc_meta_cache.php3');
		@unlink('data/engines-list.ini');
		upgrade_version (1.603);
	}

	if ($lcm_version_current < 1.604) {
		lcm_query("ALTER TABLE spip_auteurs ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		$u = lcm_query("SELECT * FROM spip_auteurs WHERE prefs LIKE '%spip_lang%'");
		while ($row = spip_fetch_array($u)) {
			$prefs = unserialize($row['prefs']);
			$l = $prefs['spip_lang'];
			unset ($prefs['spip_lang']);
			lcm_query ("UPDATE spip_auteurs SET lang='".addslashes($l)."',
				prefs='".addslashes(serialize($prefs))."'
				WHERE id_auteur=".$row['id_auteur']);
		}
		upgrade_version (1.604, lcm_query("SELECT lang FROM spip_auteurs"));
	}

	if ($lcm_version_current < 1.702) {
		lcm_query("ALTER TABLE spip_articles ADD extra longblob NULL");
		lcm_query("ALTER TABLE spip_auteurs ADD extra longblob NULL");
		lcm_query("ALTER TABLE spip_breves ADD extra longblob NULL");
		lcm_query("ALTER TABLE spip_rubriques ADD extra longblob NULL");
		lcm_query("ALTER TABLE spip_mots ADD extra longblob NULL");

		// recuperer les eventuels 'supplement' installes en 1.701
		if ($lcm_version_current == 1.701) {
			lcm_query ("UPDATE spip_articles SET extra = supplement");
			lcm_query ("ALTER TABLE spip_articles DROP supplement");
			lcm_query ("UPDATE spip_auteurs SET extra = supplement");
			lcm_query ("ALTER TABLE spip_auteurs DROP supplement");
			lcm_query ("UPDATE spip_breves SET extra = supplement");
			lcm_query ("ALTER TABLE spip_breves DROP supplement");
			lcm_query ("UPDATE spip_rubriques SET extra = supplement");
			lcm_query ("ALTER TABLE spip_rubriques DROP supplement");
			lcm_query ("UPDATE spip_mots SET extra = supplement");
			lcm_query ("ALTER TABLE spip_mots DROP supplement");
		}
		upgrade_version (1.702,
			lcm_query("SELECT extra FROM spip_articles")
			&& lcm_query("SELECT extra FROM spip_auteurs")
			&& lcm_query("SELECT extra FROM spip_breves")
			&& lcm_query("SELECT extra FROM spip_rubriques")
			&& lcm_query("SELECT extra FROM spip_mots")
			);
	}

	if ($lcm_version_current < 1.703) {
		lcm_query("ALTER TABLE spip_articles ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_rubriques ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		upgrade_version (1.703);
	}

	if ($lcm_version_current < 1.704) {
		lcm_query("ALTER TABLE spip_articles ADD INDEX lang (lang)");
		lcm_query("ALTER TABLE spip_auteurs ADD INDEX lang (lang)");
		lcm_query("ALTER TABLE spip_rubriques ADD INDEX lang (lang)");
		upgrade_version (1.704);
	}

	if ($lcm_version_current < 1.705) {
		lcm_query("ALTER TABLE spip_articles ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		lcm_query("ALTER TABLE spip_rubriques ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		upgrade_version (1.705);
	}

	if ($lcm_version_current < 1.707) {
		lcm_query("UPDATE spip_articles SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		lcm_query("UPDATE spip_articles SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		lcm_query("UPDATE spip_rubriques SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		lcm_query("UPDATE spip_rubriques SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		upgrade_version (1.707);
	}

	if ($lcm_version_current < 1.708) {
		lcm_query("ALTER TABLE spip_breves ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_breves ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		upgrade_version (1.708);
	}

	if ($lcm_version_current < 1.709) {
		lcm_query("ALTER TABLE spip_articles ADD id_trad bigint(21) DEFAULT '0' NOT NULL");
		lcm_query("ALTER TABLE spip_articles ADD INDEX id_trad (id_trad)");
		upgrade_version (1.709);
	}

	if ($lcm_version_current < 1.717) {
		lcm_query("ALTER TABLE spip_articles ADD INDEX date_modif (date_modif)");
		upgrade_version (1.717);
	}

	if ($lcm_version_current < 1.718) {
		lcm_query("ALTER TABLE spip_referers DROP domaine");
		lcm_query("ALTER TABLE spip_referers_articles DROP domaine");
		lcm_query("ALTER TABLE spip_referers_temp DROP domaine");
		upgrade_version (1.718);
	}

	if ($lcm_version_current < 1.722) {
		lcm_query("ALTER TABLE spip_articles ADD nom_site tinytext NOT NULL");
		lcm_query("ALTER TABLE spip_articles ADD url_site VARCHAR(255) NOT NULL");
		lcm_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site)");
		if ($lcm_version_current >= 1.720) {
			lcm_query("UPDATE spip_articles SET url_site=url_ref");
			lcm_query("ALTER TABLE spip_articles DROP INDEX url_ref");
			lcm_query("ALTER TABLE spip_articles DROP url_ref");
		}
		upgrade_version (1.722);
	}

	if ($lcm_version_current < 1.723) {
		if ($lcm_version_current == 1.722) {
			lcm_query("ALTER TABLE spip_articles MODIFY url_site VARCHAR(255) NOT NULL");
			lcm_query("ALTER TABLE spip_articles DROP INDEX url_site;");
			lcm_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site);");
		}
		upgrade_version (1.723);
	}

	if ($lcm_version_current < 1.724) {
		lcm_query("ALTER TABLE spip_messages ADD date_fin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		upgrade_version (1.724);
	}

	if ($lcm_version_current < 1.726) {
		lcm_query("ALTER TABLE spip_auteurs ADD low_sec tinytext NOT NULL");
		upgrade_version (1.726);
	}

	if ($lcm_version_current < 1.727) {
		// occitans : oci_xx -> oc_xx
		lcm_query("UPDATE spip_auteurs SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		lcm_query("UPDATE spip_rubriques SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		lcm_query("UPDATE spip_articles SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		lcm_query("UPDATE spip_breves SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		upgrade_version (1.727);
	}

	// Ici version 1.7 officielle

	if ($lcm_version_current < 1.728) {
		lcm_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		upgrade_version (1.728);
	}

	if ($lcm_version_current < 1.730) {
		lcm_query("ALTER TABLE spip_articles ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_articles INDEX idx (idx)");
		lcm_query("ALTER TABLE spip_auteurs ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_auteurs INDEX idx (idx)");
		lcm_query("ALTER TABLE spip_breves ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_breves INDEX idx (idx)");
		lcm_query("ALTER TABLE spip_mots ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_mots INDEX idx (idx)");
		lcm_query("ALTER TABLE spip_rubriques ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_rubriques INDEX idx (idx)");
		lcm_query("ALTER TABLE spip_syndic ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_syndic INDEX idx (idx)");
		lcm_query("ALTER TABLE spip_forum ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		lcm_query("ALTER TABLE spip_signatures ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		lcm_query("ALTER TABLE spip_signatures INDEX idx (idx)");
		upgrade_version (1.730);
	}

	if ($lcm_version_current < 1.731) {	// reindexer les docs allemands et vietnamiens
		lcm_query("UPDATE spip_articles SET idx='1' where lang IN ('de','vi')");
		lcm_query("UPDATE spip_rubriques SET idx='1' where lang IN ('de','vi')");
		lcm_query("UPDATE spip_breves SET idx='1' where lang IN ('de','vi')");
		lcm_query("UPDATE spip_auteurs SET idx='1' where lang IN ('de','vi')");
		upgrade_version (1.731);
	}

	if ($lcm_version_current < 1.732) {	// en correction d'un vieux truc qui avait fait sauter le champ inclus sur les bases version 1.415
		lcm_query ("ALTER TABLE spip_documents ADD inclus  VARCHAR(3) DEFAULT 'non'");
		upgrade_version (1.732);
	}

	if ($lcm_version_current < 1.733) {
		// lcm_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		lcm_query("DROP TABLE spip_versions");
		lcm_query("DROP TABLE spip_versions_fragments");
		creer_base();
		upgrade_version(1.733);
	}

	if ($lcm_version_current < 1.734) {
		// integrer nouvelles tables auxiliaires du compilateur ESJ
		creer_base();
		upgrade_version(1.734);
	}

	if ($lcm_version_current < 1.801) {
		lcm_query("ALTER TABLE spip_rubriques
			ADD statut_tmp VARCHAR(10) NOT NULL,
			ADD date_tmp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		include_ecrire('inc_rubriques.php3');
		calculer_rubriques();
		upgrade_version(1.801);
	}
*/

	return $log;
}

?>
