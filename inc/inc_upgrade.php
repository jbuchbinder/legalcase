<?php

//
// Execute this file only once
if (defined("_INC_UPGRADE")) return;
define("_INC_UPGRADE", "1");

include('inc/inc_version.php');
include_lcm('inc_meta');
include_lcm('inc_db');

function upgrade_version ($version, $test = true) {
	if ($test) {
		spip_query("REPLACE lcm_meta (name, value) VALUES ('version_lcm', '$version')");
		lcm_log("mise a jour de la base vers $version");
	} else {
		include_lcm('inc_lang');
		echo _T('alerte_maj_impossible', array('version' => $version));
		exit;
	}
}

function upgrade_database() {
	global $lcm_version;

	//
	// Lecture de la version installee
	//
	$version_installee = 0.0;
	// $result = spip_query("SELECT valeur FROM spip_meta WHERE name='version_lcm'");
	// if ($result) if ($row = spip_fetch_array($result)) $version_installee = (double) $row['valeur'];
	$version_installee = lire_meta('version_lcm');
	echo "VERSION = $version \n";

	//
	// Si pas de version mentionnee dans spip_meta, c'est qu'il s'agit d'une nouvelle installation
	//   => ne pas passer par le processus de mise a jour
	//
	//	$version_installee = 1.702; quand on a besoin de forcer une MAJ
	if (!$version_installee) {
		$version_installee = $lcm_version;
		upgrade_version($version_installee);
		return true;
	}


	//
	// Verification des droits de modification sur la base
	//

	/*
	spip_query("DROP TABLE IF EXISTS spip_test");
	spip_query("CREATE TABLE spip_test (a INT)");
	spip_query("ALTER TABLE spip_test ADD b INT");
	spip_query("INSERT INTO spip_test (b) VALUES (1)");
	$result = spip_query("SELECT b FROM spip_test");
	spip_query("ALTER TABLE spip_test DROP b");
	if (!$result) return false;
	*/

	//
	// Selection en fonction de la version
	//
/*
	if ($version_installee < 0.98) {

		spip_query("ALTER TABLE spip_articles ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_articles ADD export VARCHAR(10) DEFAULT 'oui'");
		spip_query("ALTER TABLE spip_articles ADD images TEXT DEFAULT ''");
		spip_query("ALTER TABLE spip_articles ADD date_redac datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_articles DROP INDEX id_article");
		spip_query("ALTER TABLE spip_articles ADD INDEX id_rubrique (id_rubrique)");
		spip_query("ALTER TABLE spip_articles ADD visites INTEGER DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD referers BLOB NOT NULL");

		spip_query("ALTER TABLE spip_auteurs ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_auteurs ADD pgp BLOB NOT NULL");

		spip_query("ALTER TABLE spip_auteurs_articles ADD INDEX id_auteur (id_auteur), ADD INDEX id_article (id_article)");
	
		spip_query("ALTER TABLE spip_rubriques ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_rubriques ADD export VARCHAR(10) DEFAULT 'oui', ADD id_import BIGINT DEFAULT '0'");
	
		spip_query("ALTER TABLE spip_breves ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_breves DROP INDEX id_breve");
		spip_query("ALTER TABLE spip_breves DROP INDEX id_breve_2");
		spip_query("ALTER TABLE spip_breves ADD INDEX id_rubrique (id_rubrique)");
	
		spip_query("ALTER TABLE spip_forum ADD ip VARCHAR(16)");
		spip_query("ALTER TABLE spip_forum ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_forum DROP INDEX id_forum");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_parent (id_parent), ADD INDEX id_rubrique (id_rubrique), ADD INDEX id_article(id_article), ADD INDEX id_breve(id_breve)");
		upgrade_version (0.98);
	}

	if ($version_installee < 0.99) {
	
		$query = "SELECT DISTINCT id_article FROM spip_forum WHERE id_article!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_article);
			$id_article = $row['id_article'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_article=$id_article";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_article[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_article = join(',', $forums_article);
			$query3 = "UPDATE spip_forum SET id_article=$id_article WHERE id_forum IN ($forums_article)";
			spip_query($query3);
		}
	
		$query = "SELECT DISTINCT id_breve FROM spip_forum WHERE id_breve!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_breve);
			$id_breve = $row['id_breve'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_breve=$id_breve";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_breve[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_breve = join(',', $forums_breve);
			$query3 = "UPDATE spip_forum SET id_breve=$id_breve WHERE id_forum IN ($forums_breve)";
			spip_query($query3);
		}
	
		$query = "SELECT DISTINCT id_rubrique FROM spip_forum WHERE id_rubrique!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_rubrique);
			$id_rubrique = $row['id_rubrique'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_rubrique=$id_rubrique";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_rubrique[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_rubrique = join(',', $forums_rubrique);
			$query3 = "UPDATE spip_forum SET id_rubrique=$id_rubrique WHERE id_forum IN ($forums_rubrique)";
			spip_query($query3);
		}
		upgrade_version (0.99);
	}

	if ($version_installee < 0.997) {
		spip_query("DROP TABLE spip_index");
		upgrade_version (0.997);
	}

	if ($version_installee < 0.999) {
		global $htsalt;
		spip_query("ALTER TABLE spip_auteurs CHANGE pass pass tinyblob NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD htpass tinyblob NOT NULL");
		$query = "SELECT id_auteur, pass FROM spip_auteurs WHERE pass!=''";
		$result = spip_query($query);
		while (list($id_auteur, $pass) = spip_fetch_array($result)) {
			$htpass = generer_htpass($pass);
			$pass = md5($pass);
			spip_query("UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur");
		}
		upgrade_version (0.999);
	}
	
	if ($version_installee < 1.01) {
		spip_query("UPDATE spip_forum SET statut='publie' WHERE statut=''");
		upgrade_version (1.01);
	}
	
	if ($version_installee < 1.02) {
		spip_query("ALTER TABLE spip_forum ADD id_auteur BIGINT DEFAULT '0' NOT NULL");
		upgrade_version (1.02);
	}

	if ($version_installee < 1.03) {
		spip_query("DROP TABLE spip_maj");
		upgrade_version (1.03);
	}

	if ($version_installee < 1.04) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3)");
		upgrade_version (1.04);
	}

	if ($version_installee < 1.05) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
		upgrade_version (1.05);
	}

	if ($version_installee < 1.1) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
		upgrade_version (1.1);
	}

	// Correction de l'oubli des modifs creations depuis 1.04
	if ($version_installee < 1.204) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3) NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD id_message bigint(21) NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_message (id_message)");
		spip_query("ALTER TABLE spip_auteurs ADD en_ligne datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD imessage VARCHAR(3) not null");
		spip_query("ALTER TABLE spip_auteurs ADD messagerie VARCHAR(3) not null");
		upgrade_version (1.204);
	}

	if ($version_installee < 1.207) {
		spip_query("ALTER TABLE spip_rubriques DROP INDEX id_rubrique");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX id_parent (id_parent)");
		spip_query("ALTER TABLE spip_rubriques ADD statut VARCHAR(10) NOT NULL");
		// Declencher le calcul des rubriques publiques
		spip_query("REPLACE spip_meta (nom, valeur) VALUES ('calculer_rubriques', 'oui')");
		upgrade_version (1.207);
	}

	[ML] ...... (removed code) ....

	if ($version_installee < 1.414) {
		// Forum par defaut "en dur" dans les spip_articles
		// -> non, prio (priori), pos (posteriori), abo (abonnement)
		include_ecrire ("inc_meta.php3");
		$accepter_forum = substr(lire_meta("forums_publics"),0,3) ;
		$query = "ALTER TABLE spip_articles CHANGE accepter_forum accepter_forum CHAR(3) NOT NULL";
		$result = spip_query($query);
		$query = "UPDATE spip_articles SET accepter_forum='$accepter_forum' WHERE accepter_forum != 'non'";
		$result = spip_query($query);
		upgrade_version (1.414);
	}

	if ($version_installee < 1.417) {
		spip_query("ALTER TABLE spip_syndic_articles DROP date_index");
		upgrade_version (1.417);
	}

	if ($version_installee < 1.418) {
		$query = "SELECT * FROM spip_auteurs WHERE statut = '0minirezo' AND email != '' ORDER BY id_auteur LIMIT 0,1";
		$result = spip_query($query);
		if ($webmaster = spip_fetch_array($result)) {
			include_ecrire("inc_meta.php3");
			ecrire_meta('email_webmaster', $webmaster['email']);
			ecrire_metas();
		}
		upgrade_version (1.418);
	}

	if ($version_installee < 1.419) {
		$query = "ALTER TABLE spip_auteurs ADD alea_actuel TINYTEXT DEFAULT ''";
		spip_query($query);
		$query = "ALTER TABLE spip_auteurs ADD alea_futur TINYTEXT DEFAULT ''";
		spip_query($query);
		$query = "UPDATE spip_auteurs SET alea_futur = FLOOR(32000*RAND())";
		spip_query($query);
		upgrade_version (1.419);
	}

	if ($version_installee < 1.420) {
		$query = "UPDATE spip_auteurs SET alea_actuel='' WHERE statut='nouveau'";
		spip_query($query);
		upgrade_version (1.420);
	}
	
	if ($version_installee < 1.421) {
		$query = "ALTER TABLE spip_articles ADD auteur_modif bigint(21) DEFAULT '0' NOT NULL";
		spip_query($query);
		$query = "ALTER TABLE spip_articles ADD date_modif datetime DEFAULT '0000-00-00 00:00:00' NOT NULL";
		spip_query($query);
		upgrade_version (1.421);
	}

	if ($version_installee < 1.432) {
		spip_query("ALTER TABLE spip_articles DROP referers");
		$query = "ALTER TABLE spip_articles ADD referers INTEGER DEFAULT '0' NOT NULL";
		spip_query($query);
		$query = "ALTER TABLE spip_articles ADD popularite INTEGER DEFAULT '0' NOT NULL";
		spip_query($query);
		upgrade_version (1.432);
	}

	if ($version_installee < 1.436) {
		$query = "ALTER TABLE spip_documents ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL";
		spip_query($query);
		upgrade_version (1.436);
	}

	if ($version_installee < 1.437) {
		spip_query("ALTER TABLE spip_visites ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_visites_referers ADD maj TIMESTAMP");
		upgrade_version (1.437);
	}

	if ($version_installee < 1.438) {
		spip_query("ALTER TABLE spip_articles ADD INDEX id_secteur (id_secteur)");
		spip_query("ALTER TABLE spip_articles ADD INDEX statut (statut, date)");
		upgrade_version (1.438);
	}

	if ($version_installee < 1.439) {
		spip_query("ALTER TABLE spip_syndic ADD INDEX statut (statut, date_syndic)");
		spip_query("ALTER TABLE spip_syndic_articles ADD INDEX statut (statut)");
		spip_query("ALTER TABLE spip_syndic_articles CHANGE url url VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD INDEX url (url)");
		upgrade_version (1.439);
	}

	if ($version_installee < 1.440) {
		spip_query("ALTER TABLE spip_visites_temp CHANGE ip ip INTEGER UNSIGNED NOT NULL");
		upgrade_version (1.440);
	}

	if ($version_installee < 1.441) {
		spip_query("ALTER TABLE spip_visites_temp CHANGE date date DATE NOT NULL");
		spip_query("ALTER TABLE spip_visites CHANGE date date DATE NOT NULL");
		spip_query("ALTER TABLE spip_visites_referers CHANGE date date DATE NOT NULL");
		upgrade_version (1.441);
	}

	if ($version_installee < 1.442) {
		$query = "ALTER TABLE spip_auteurs ADD prefs TINYTEXT NOT NULL";
		spip_query($query);
		upgrade_version (1.442);
	}

	if ($version_installee < 1.443) {
		spip_query("ALTER TABLE spip_auteurs CHANGE login login VARCHAR(255) BINARY NOT NULL");
		spip_query("ALTER TABLE spip_auteurs CHANGE statut statut VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX login (login)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX statut (statut)");
		upgrade_version (1.443);
	}

	if ($version_installee < 1.444) {
		spip_query("ALTER TABLE spip_syndic ADD moderation VARCHAR(3) NOT NULL");
		upgrade_version (1.444);
	}

	if ($version_installee < 1.457) {
		spip_query("DROP TABLE spip_visites");
		spip_query("DROP TABLE spip_visites_temp");
		spip_query("DROP TABLE spip_visites_referers");
		creer_base(); // crade, a ameliorer :-((
		upgrade_version (1.457);
	}

	if ($version_installee < 1.458) {
		spip_query("ALTER TABLE spip_auteurs ADD cookie_oubli TINYTEXT NOT NULL");
		upgrade_version (1.458);
	}

	if ($version_installee < 1.459) {
		$result = spip_query("SELECT type FROM spip_mots GROUP BY type");
		while ($row = spip_fetch_array($result)) {
			$type = addslashes($row['type']);
			$res = spip_query("SELECT * FROM spip_groupes_mots
				WHERE titre='$type'");
			if (spip_num_rows($res) == 0) {
				spip_query("INSERT IGNORE INTO spip_groupes_mots 
					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, 0minirezo, 1comite, 6forum)
					VALUES ('$type', 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
				if ($id_groupe = spip_insert_id()) 
					spip_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type='$type'");
			}
		}
		spip_query("UPDATE spip_articles SET popularite=0");
		upgrade_version (1.459);
	}

	if ($version_installee < 1.460) {
		// remettre les mots dans les groupes dupliques par erreur
		// dans la precedente version du paragraphe de maj 1.459
		// et supprimer ceux-ci
		$result = spip_query("SELECT * FROM spip_groupes_mots ORDER BY id_groupe");
		while ($row = spip_fetch_array($result)) {
			$titre = addslashes($row['titre']);
			if (! $vu[$titre] ) {
				$vu[$titre] = true;
				$id_groupe = $row['id_groupe'];
				spip_query ("UPDATE spip_mots SET id_groupe=$id_groupe WHERE type='$titre'");
				spip_query ("DELETE FROM spip_groupes_mots WHERE titre='$titre' AND id_groupe<>$id_groupe");
			}
		}
		upgrade_version (1.460);
	}

	if ($version_installee < 1.462) {
		spip_query("UPDATE spip_types_documents SET inclus='embed' WHERE inclus!='non' AND extension IN ".
			"('aiff', 'asf', 'avi', 'mid', 'mov', 'mp3', 'mpg', 'ogg', 'qt', 'ra', 'ram', 'rm', 'swf', 'wav', 'wmv')");
		upgrade_version (1.462);
	}

	if ($version_installee < 1.463) {
		spip_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE");
		spip_query("ALTER TABLE spip_visites_temp ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_referers_temp ADD maj TIMESTAMP");
		upgrade_version (1.463);
	}

	// l'upgrade < 1.462 ci-dessus etait fausse, d'ou correctif
	if (($version_installee < 1.464) AND ($version_installee >= 1.462)) {
		$res = spip_query("SELECT id_type, extension FROM spip_types_documents WHERE id_type NOT IN (1,2,3)");
		while ($row = spip_fetch_array($res)) {
			$extension = $row['extension'];
			$id_type = $row['id_type'];
			spip_query("UPDATE spip_documents SET id_type=$id_type
				WHERE fichier like '%.$extension'");
		}
		upgrade_version (1.464);
	}

	if ($version_installee < 1.465) {
		spip_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE NOT NULL");
		upgrade_version (1.465);
	}

	if ($version_installee < 1.466) {
		spip_query("ALTER TABLE spip_auteurs ADD source VARCHAR(10) DEFAULT 'spip' NOT NULL");
		upgrade_version (1.466);
	}

	if ($version_installee < 1.468) {
		spip_query("ALTER TABLE spip_auteurs ADD INDEX en_ligne (en_ligne)");
		spip_query("ALTER TABLE spip_forum ADD INDEX statut (statut, date_heure)");
		upgrade_version (1.468);
	}

	if ($version_installee < 1.470) {
		if ($version_installee >= 1.467) {	// annule les "listes de diff"
			spip_query("DROP TABLE spip_listes");
			spip_query("ALTER TABLE spip_auteurs DROP abonne");
			spip_query("ALTER TABLE spip_auteurs DROP abonne_pass");
		}
		upgrade_version (1.470);
	}

	if ($version_installee < 1.471) {
		if ($version_installee >= 1.470) {	// annule les "maj"
			spip_query("ALTER TABLE spip_auteurs_articles DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_auteurs_rubriques DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_auteurs_messages DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_documents_articles DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_documents_rubriques DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_documents_breves DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_articles DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_breves DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_rubriques DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_syndic DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_forum DROP maj TIMESTAMP");
		}
		upgrade_version (1.471);
	}

	if ($version_installee < 1.472) {
		spip_query("ALTER TABLE spip_referers ADD visites_jour INTEGER UNSIGNED NOT NULL");
		upgrade_version (1.472);
	}

	if ($version_installee < 1.473) {
		spip_query("UPDATE spip_syndic_articles SET url = REPLACE(url, '&amp;', '&')");
		spip_query("UPDATE spip_syndic SET url_site = REPLACE(url_site, '&amp;', '&')");
		upgrade_version (1.473);
	}

	if ($version_installee < 1.600) {
		include_ecrire('inc_index.php3');
		purger_index();
		creer_liste_indexation();
		upgrade_version (1.600);
	}

	if ($version_installee < 1.601) {
		spip_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
		upgrade_version (1.601);
	}

	if ($version_installee < 1.603) {
		// supprimer les fichiers deplaces
		@unlink('inc_meta_cache.php3');
		@unlink('data/engines-list.ini');
		upgrade_version (1.603);
	}

	if ($version_installee < 1.604) {
		spip_query("ALTER TABLE spip_auteurs ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		$u = spip_query("SELECT * FROM spip_auteurs WHERE prefs LIKE '%spip_lang%'");
		while ($row = spip_fetch_array($u)) {
			$prefs = unserialize($row['prefs']);
			$l = $prefs['spip_lang'];
			unset ($prefs['spip_lang']);
			spip_query ("UPDATE spip_auteurs SET lang='".addslashes($l)."',
				prefs='".addslashes(serialize($prefs))."'
				WHERE id_auteur=".$row['id_auteur']);
		}
		upgrade_version (1.604, spip_query("SELECT lang FROM spip_auteurs"));
	}

	if ($version_installee < 1.702) {
		spip_query("ALTER TABLE spip_articles ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_auteurs ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_breves ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_rubriques ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_mots ADD extra longblob NULL");

		// recuperer les eventuels 'supplement' installes en 1.701
		if ($version_installee == 1.701) {
			spip_query ("UPDATE spip_articles SET extra = supplement");
			spip_query ("ALTER TABLE spip_articles DROP supplement");
			spip_query ("UPDATE spip_auteurs SET extra = supplement");
			spip_query ("ALTER TABLE spip_auteurs DROP supplement");
			spip_query ("UPDATE spip_breves SET extra = supplement");
			spip_query ("ALTER TABLE spip_breves DROP supplement");
			spip_query ("UPDATE spip_rubriques SET extra = supplement");
			spip_query ("ALTER TABLE spip_rubriques DROP supplement");
			spip_query ("UPDATE spip_mots SET extra = supplement");
			spip_query ("ALTER TABLE spip_mots DROP supplement");
		}
		upgrade_version (1.702,
			spip_query("SELECT extra FROM spip_articles")
			&& spip_query("SELECT extra FROM spip_auteurs")
			&& spip_query("SELECT extra FROM spip_breves")
			&& spip_query("SELECT extra FROM spip_rubriques")
			&& spip_query("SELECT extra FROM spip_mots")
			);
	}

	if ($version_installee < 1.703) {
		spip_query("ALTER TABLE spip_articles ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		upgrade_version (1.703);
	}

	if ($version_installee < 1.704) {
		spip_query("ALTER TABLE spip_articles ADD INDEX lang (lang)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX lang (lang)");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX lang (lang)");
		upgrade_version (1.704);
	}

	if ($version_installee < 1.705) {
		spip_query("ALTER TABLE spip_articles ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		spip_query("ALTER TABLE spip_rubriques ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		upgrade_version (1.705);
	}

	if ($version_installee < 1.707) {
		spip_query("UPDATE spip_articles SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		spip_query("UPDATE spip_articles SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		spip_query("UPDATE spip_rubriques SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		spip_query("UPDATE spip_rubriques SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		upgrade_version (1.707);
	}

	if ($version_installee < 1.708) {
		spip_query("ALTER TABLE spip_breves ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_breves ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		upgrade_version (1.708);
	}

	if ($version_installee < 1.709) {
		spip_query("ALTER TABLE spip_articles ADD id_trad bigint(21) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX id_trad (id_trad)");
		upgrade_version (1.709);
	}

	if ($version_installee < 1.717) {
		spip_query("ALTER TABLE spip_articles ADD INDEX date_modif (date_modif)");
		upgrade_version (1.717);
	}

	if ($version_installee < 1.718) {
		spip_query("ALTER TABLE spip_referers DROP domaine");
		spip_query("ALTER TABLE spip_referers_articles DROP domaine");
		spip_query("ALTER TABLE spip_referers_temp DROP domaine");
		upgrade_version (1.718);
	}

	if ($version_installee < 1.722) {
		spip_query("ALTER TABLE spip_articles ADD nom_site tinytext NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD url_site VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site)");
		if ($version_installee >= 1.720) {
			spip_query("UPDATE spip_articles SET url_site=url_ref");
			spip_query("ALTER TABLE spip_articles DROP INDEX url_ref");
			spip_query("ALTER TABLE spip_articles DROP url_ref");
		}
		upgrade_version (1.722);
	}

	if ($version_installee < 1.723) {
		if ($version_installee == 1.722) {
			spip_query("ALTER TABLE spip_articles MODIFY url_site VARCHAR(255) NOT NULL");
			spip_query("ALTER TABLE spip_articles DROP INDEX url_site;");
			spip_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site);");
		}
		upgrade_version (1.723);
	}

	if ($version_installee < 1.724) {
		spip_query("ALTER TABLE spip_messages ADD date_fin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		upgrade_version (1.724);
	}

	if ($version_installee < 1.726) {
		spip_query("ALTER TABLE spip_auteurs ADD low_sec tinytext NOT NULL");
		upgrade_version (1.726);
	}

	if ($version_installee < 1.727) {
		// occitans : oci_xx -> oc_xx
		spip_query("UPDATE spip_auteurs SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_rubriques SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_articles SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_breves SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		upgrade_version (1.727);
	}

	// Ici version 1.7 officielle

	if ($version_installee < 1.728) {
		spip_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		upgrade_version (1.728);
	}

	if ($version_installee < 1.730) {
		spip_query("ALTER TABLE spip_articles ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_articles INDEX idx (idx)");
		spip_query("ALTER TABLE spip_auteurs ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_auteurs INDEX idx (idx)");
		spip_query("ALTER TABLE spip_breves ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_breves INDEX idx (idx)");
		spip_query("ALTER TABLE spip_mots ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_mots INDEX idx (idx)");
		spip_query("ALTER TABLE spip_rubriques ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques INDEX idx (idx)");
		spip_query("ALTER TABLE spip_syndic ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic INDEX idx (idx)");
		spip_query("ALTER TABLE spip_forum ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_signatures ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_signatures INDEX idx (idx)");
		upgrade_version (1.730);
	}

	if ($version_installee < 1.731) {	// reindexer les docs allemands et vietnamiens
		spip_query("UPDATE spip_articles SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_rubriques SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_breves SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_auteurs SET idx='1' where lang IN ('de','vi')");
		upgrade_version (1.731);
	}

	if ($version_installee < 1.732) {	// en correction d'un vieux truc qui avait fait sauter le champ inclus sur les bases version 1.415
		spip_query ("ALTER TABLE spip_documents ADD inclus  VARCHAR(3) DEFAULT 'non'");
		upgrade_version (1.732);
	}

	if ($version_installee < 1.733) {
		// spip_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		spip_query("DROP TABLE spip_versions");
		spip_query("DROP TABLE spip_versions_fragments");
		creer_base();
		upgrade_version(1.733);
	}

	if ($version_installee < 1.734) {
		// integrer nouvelles tables auxiliaires du compilateur ESJ
		creer_base();
		upgrade_version(1.734);
	}

	if ($version_installee < 1.801) {
		spip_query("ALTER TABLE spip_rubriques
			ADD statut_tmp VARCHAR(10) NOT NULL,
			ADD date_tmp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		include_ecrire('inc_rubriques.php3');
		calculer_rubriques();
		upgrade_version(1.801);
	}
*/

	return true;
}

?>
