<?php

include('inc/inc_version.php');
include_lcm('inc_presentation');
include_lcm('inc_db');

use_language_of_visitor();

// Test if the software is already installed
if (@file_exists('inc/config/inc_connect.php')) {
	install_html_start();
	echo "<p><font face='Verdana,Arial,Sans,sans-serif' size='4'>"._T('avis_espace_interdit')."</font>";
	install_html_end();

	exit;
}

//
// Usual installation steps
//

if ($etape == 6) {
	install_html_start();

	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size='3'>"._T('info_derniere_etape')."</b></font>";
	echo "<p>";
	echo "<b>"._T('info_code_acces')."</b>";
	echo "<p>"._T('info_utilisation_spip');

	include_config('inc_connect_install');
	include_lcm('inc_meta');
	include_lcm('inc_access');

	if ($login) {
		$nom = addslashes($nom);
		$query = "SELECT id_author FROM lcm_author WHERE username=\"$login\"";
		$result = spip_query_db($query);
		unset($id_auteur);
		while ($row = spip_fetch_array($result)) $id_auteur = $row['id_auteur'];

		$mdpass = md5($pass);
		$htpass = generer_htpass($pass);

		// [ML] TODO: name_first, name_middle, name_last, email, etc..
		if ($id_auteur) {
			$query = "UPDATE lcm_author SET name_first=\"$nom\", username=\"$login\", password=\"$mdpass\", alea_actuel='', alea_futur=FLOOR(32000*RAND()), htpass=\"$htpass\", status=\"admin\" WHERE id_author=$id_auteur";
		} else {
			$query = "INSERT INTO lcm_author (name_first, username, password, htpass, alea_futur, status) VALUES(\"$nom\",\"$login\",\"$mdpass\",\"$htpass\",FLOOR(32000*RAND()),\"admin\")";
		}
		spip_query_db($query);

		// insert email as main system administrator
		ecrire_meta('email_sysadmin', $email);
	}

	include_lcm('inc_defaults');
	init_default_config();
	init_languages();

	// Block public access to the 'data' subdirectory
	// [ML] Moved data + config under inc, and blocked inc instead
	// [ML] But for now, we can ignore it. Why not just simply ship with a .htaccess included?
	/* ecrire_acces();
	$protec = "deny from all\n";
	$myFile = fopen('inc/.htaccess', 'w');
	fputs($myFile, $protec);
	fclose($myFile);
	*/

	@unlink('inc/data/inc_meta_cache.php');
	if (!@rename('inc/config/inc_connect_install.php', 'inc/config/inc_connect.php')) {
		copy('inc/config/inc_connect_install.php', 'inc/config/inc_connect.php');
		@unlink('inc/config/inc_connect_install.php');
	}

	echo "<form action='index.php' method='post'>";
	echo "<div align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";
	echo "</form>";

	ecrire_metas();

	install_html_end();
}

else if ($etape == 5) {
	install_html_start();

	include_config('inc_connect_install');

	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size='3'>"._T('info_informations_personnelles')."</font>";
	echo "<p>";

	echo "<b>"._T('texte_informations_personnelles_1')."</b>";
	echo aide ("install5");
	echo "<p>"._T('texte_informations_personnelles_2')." ";
	echo _T('info_laisser_champs_vides');

	echo "\n<form action='install.php' method='post'>\n";
	echo "<input type='hidden' name='etape' value='6'>\n";

	echo "<fieldset><label><b>"._T('info_identification_publique')."</b><br></label>";
	echo "<b>"._T('entree_signature')."</b><br>";
	echo _T('entree_nom_pseudo_1')."<br>";
	echo "<input type='text' name='nom' class='formo' value=\"$nom\" size='40'><p>";

	echo "<B>"._T('entree_adresse_email')."</B><BR>";
	echo "<input type='text' name='email' class='formo' value=\"$email\" size='40'></fieldset><P>\n";

	echo "<fieldset><label><B>"._T('entree_identifiants_connexion')."</B><BR></label>";
	echo "<B>"._T('entree_login')."</B><BR>";
	echo _T('info_plus_trois_car')."<BR>";
	echo "<input type='text' name='login' class='formo' value=\"$login\" size='40'><P>\n";

	echo "<B>"._T('entree_mot_passe')."</B> <BR>";
	echo _T('info_plus_cinq_car_2')."<BR>";
	echo "<input type='password' name='pass' class='formo' value=\"$pass\" size='40'></fieldset><P>\n";

	echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";
	echo "</form>";
	echo "<p>";

	if ($flag_ldap AND !$ldap_present) {
		echo "<div style='border: 1px solid #404040; padding: 10px; text-align: left;'>";
		echo "<b>"._T('info_authentification_externe')."</b>";
		echo "<p>"._T('texte_annuaire_ldap_1');
		echo "<form action='install.php' method='post'>";
		echo "<input type='hidden' name='etape' value='ldap1'>";
		echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value=\""._T('bouton_acces_ldap')."\">";
		echo "</form>";
	}

	install_html_end();
}

else if ($etape == 4) {
	install_html_start();

	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_creation_tables')."</FONT>";
	echo "<P>";

	$link = mysql_connect("$adresse_db", "$login_db", "$pass_db");

	// echo "<"."!--";

	if ($choix_db == "new_spip") {
		$sel_db = $table_new;
		mysql_query("CREATE DATABASE $sel_db");
	} else {
		$sel_db = $choix_db;
	}
	mysql_select_db("$sel_db");

	// Test if the software was already installed
	@spip_query_db("SELECT COUNT(*) FROM lcm_meta");
	$deja_installe = !spip_sql_errno();

	include_lcm('inc_dbmgnt');
	include_lcm('inc_upgrade');

	create_database();
	$upg_ok = upgrade_database();

	// TODO XXX
	// test the structure of the tables

	/*
	$query = "SELECT COUNT(*) FROM lcm_case";
	$result = spip_query_db($query);
	$result_ok = (spip_num_rows($result) > 0);
	if (!$deja_installe) {
		$query = "INSERT lcm_meta (nom, valeur) VALUES ('nouvelle_install', 'oui')";
		spip_query_db($query);
		$result_ok = !spip_sql_errno();
	}
	*/
	$result_ok = 1;

	// echo '-->';


	if ($result_ok && $upg_ok) {
		$conn = '<' . '?php' . "\n";
		$conn .= "if (defined('_CONFIG_INC_CONNECT')) return;\n";
		$conn .= "define('_CONFIG_INC_CONNECT', '1');\n";
		$conn .= "\$GLOBALS['lcm_connect_version'] = 0.1;\n";
		$conn .= "include_lcm('inc_db');\n";
		$conn .= "@spip_connect_db('$adresse_db','','$login_db','$pass_db','$sel_db');\n";
		$conn .= "\$GLOBALS['db_ok'] = !!@spip_num_rows(@spip_query_db('SELECT COUNT(*) FROM lcm_meta'));\n";
		$conn .= '?'.'>';
		$myFile = fopen('inc/config/inc_connect_install.php', 'wb');
		fputs($myFile, $conn);
		fclose($myFile);

		echo "<B>"._T('info_base_installee')."</B><P>"._T('info_etape_suivante_1');

		echo "<form action='install.php' method='post'>";
		echo "<input type='hidden' name='etape' value='5'>";

		echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";

		echo "</FORM>";
	}
	else if ($result_ok) {
		echo _T('alerte_maj_impossible', array('version' => $lcm_version));
	}
	else {
		echo "<B>"._T('avis_operation_echec')."</B> "._T('texte_operation_echec');
	}

	install_html_end();
}

else if ($etape == 3) {
	install_html_start();

	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_choix_base')." <B>"._T('menu_aide_installation_choix_base')."</b></font>";

	echo aide ("install2");
	echo "<p>";

	echo "<form action='install.php' method='post'>";
	echo "<input type='hidden' name='etape' value='4'>";
	echo "<input type='hidden' name='adresse_db'  value=\"$adresse_db\" size='40'>";
	echo "<input type='hidden' name='login_db' value=\"$login_db\">";
	echo "<input type='hidden' name='pass_db' value=\"$pass_db\"><P>";

	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$result = @mysql_list_dbs();

	echo "<fieldset><label><b>"._T('texte_choix_base_1')."</b><br></label>";

	if ($result AND (($n = @mysql_num_rows($result)) > 0)) {
		echo "<b>"._T('texte_choix_base_2')."</b><p> "._T('texte_choix_base_3');
		echo "<ul>";
		$bases = "";
		for ($i = 0; $i < $n; $i++) {
			$table_nom = mysql_dbname($result, $i);
			$base = "<input name='choix_db' value='".$table_nom."' type='radio' id='tab$i'";
			$base_fin = "><label for='tab$i'>".$table_nom."</label><br>\n";
			if ($table_nom == $login_db) {
				$bases = "$base CHECKED$base_fin".$bases;
				$checked = true;
			}
			else {
				$bases .= "$base$base_fin\n";
			}
		}
		echo $bases."</ul>";
		echo _T('info_ou')." ";
	}
	else {
		echo "<B>"._T('avis_lecture_noms_bases_1')."</B>
		"._T('avis_lecture_noms_bases_2')."<P>";
		if ($login_db) {
			echo _T('avis_lecture_noms_bases_3');
			echo "<UL>";
			echo "<input name=\"choix_db\" value=\"".$login_db."\" type=Radio id='stand' CHECKED>";
			echo "<label for='stand'>".$login_db."</label><BR>\n";
			echo "</UL>";
			echo _T('info_ou')." ";
			$checked = true;
		}
	}
	echo "<input name=\"choix_db\" value=\"new_spip\" type=Radio id='nou'";
	if (!$checked) echo " CHECKED";
	echo "> <label for='nou'>"._T('info_creer_base')."</label> ";
	echo "<input type='text' name='table_new' class='fondo' value=\"lcm\" size='20'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";


	echo "</form>";

	install_html_end();
}

else if ($etape == 2) {
	install_html_start();

	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_connexion_base')."</FONT>";

	echo "<!--";
	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$db_connect = mysql_errno();
	echo "-->";

	echo "<P>";

	if (($db_connect=="0") && $link){
		echo "<B>"._T('info_connexion_ok')."</B><P> "._T('info_etape_suivante_2');

		echo "<form action='install.php' method='post'>";
		echo "<input type='hidden' name='etape' value='3'>";
		echo "<input type='hidden' name='adresse_db'  value=\"$adresse_db\" size='40'>";
		echo "<input type='hidden' name='login_db' value=\"$login_db\">";
		echo "<input type='hidden' name='pass_db' value=\"$pass_db\"><P>";

		echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_echec_2');
		echo "<P><FONT size=2>"._T('avis_connexion_echec_3')."</FONT>";
	}

	install_html_end();
}

else if ($etape == 1) {
	install_html_start();

	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_connexion_mysql')."</FONT>";

	echo "<P>"._T('texte_connexion_mysql');

	echo aide ("install1");

	$adresse_db = 'localhost';
	$login_db = $login_hebergeur;
	$pass_db = '';

	// Recuperer les anciennes donnees pour plus de facilite (si presentes)
	if (@file_exists('inc/config/inc_connect_install.php')) {
		$s = @join('', @file('inc/config/inc_connect_install.php'));
		if (ereg("mysql_connect\([\"'](.*)[\"'],[\"'](.*)[\"'],[\"'](.*)[\"']\)", $s, $regs)) {
			$adresse_db = $regs[1];
			$login_db = $regs[2];
		}
		else if (ereg("spip_connect_db\('(.*)','(.*)','(.*)','(.*)','(.*)'\)", $s, $regs)) {
			$adresse_db = $regs[1];
			if ($port_db = $regs[2]) $adresse_db .= ':'.$port_db;
			$login_db = $regs[3];
		}
	}

	echo "<p><form action='install.php' method='post'>";
	echo "<input type='hidden' name='etape' value='2'>";
	echo "<fieldset><label><B>"._T('entree_base_donnee_1')."</B><BR></label>";
	echo _T('entree_base_donnee_2')."<BR>";
	echo "<input type='text' name='adresse_db' class='formo' value=\"$adresse_db\" size='40'></fieldset><P>";

	echo "<fieldset><label><B>"._T('entree_login_connexion_1')."</B><BR></label>";
	echo _T('entree_login_connexion_2')."<BR>";
	echo "<input type='text' name='login_db' class='formo' value=\"$login_db\" size='40'></fieldset><P>";

	echo "<fieldset><label><B>"._T('entree_mot_passe_1')."</B><BR></label>";
	echo _T('entree_mot_passe_2')."<BR>";
	echo "<input type='password' name='pass_db' class='formo' value=\"$pass_db\" size='40'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";


	echo "</FORM>";

	install_html_end();

}

else if ($etape == 'dirs') {
	header("Location: lcm_test_dirs.php");
}

else if (!$etape) {
	$menu_lang = menu_languages('var_lang_lcm_all');

	//if (!$menu_lang)
	//	header("Location: lcm_test_dirs.php");
	// else 
	{
		install_html_start();

		// TODO TRANSLATE
		echo "<p>&nbsp;</p>\n";
		echo "<table border='0' cellspacing='0' width='300' height='170' style=\"background-image: url('images/lcm/logo_lcm-170.png'); border: 0\">\n";
		echo "<tr><td align='center'><p style='font-size: 130%;'>" .  _T('title_software') . "</p></td></tr>\n";
		echo "</table>\n";

		echo "<p>&nbsp;</p><p>" . _T('install_select_langue');

		echo "<p><div align='center'>".$menu_lang."</div>";

		echo "<p><form action='install.php' method='get'>";
		echo "<input type='hidden' name='etape' value='dirs'>";
		echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";
		echo "</form>";

		install_html_end();
	}
}


//
// Etapes de l'installation LDAP
//

else if ($etape == 'ldap5') {
	install_html_start();

	include_ecrire('inc_connect_install.php3');
	include_ecrire('inc_meta.php3');
	ecrire_meta("ldap_statut_import", $statut_ldap);
	ecrire_metas();

	echo "<B>"._T('info_ldap_ok')."</B>";
	echo "<P>"._T('info_terminer_installation');

	echo "<form action='install.php' method='post'>";
	echo "<input type='hidden' name='etape' value='5'>";

	echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";

	echo "</FORM>";
}

else if ($etape == 'ldap4') {
	install_html_start();

	if (!$base_ldap) $base_ldap = $base_ldap_text;

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	// Essayer de verifier le chemin fourni
	$r = @ldap_compare($ldap_link, $base_ldap, "objectClass", "");
	$fail = (ldap_errno($ldap_link) == 32);

	if ($fail) {
		echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_chemin_acces_annuaire')."</B></FONT>";
		echo "<P>";

		echo "<B>"._T('avis_operation_echec')."</B> "._T('avis_chemin_invalide_1')." (<tt>".htmlspecialchars($base_ldap);
		echo "</tt>) "._T('avis_chemin_invalide_2');
	}
	else {
		echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_reglage_ldap')."</FONT>";
		echo "<P>";

		$conn = join('', file("inc_connect_install.php3"));
		if ($p = strpos($conn, '?'.'>')) 
			$conn = substr($conn, 0, $p);
		if (!strpos($conn, 'spip_connect_ldap')) {
			$conn .= "function spip_connect_ldap() {\n";
			$conn .= "\t\$GLOBALS['ldap_link'] = @ldap_connect(\"$adresse_ldap\",\"$port_ldap\");\n";
			$conn .= "\t@ldap_bind(\$GLOBALS['ldap_link'],\"$login_ldap\",\"$pass_ldap\");\n";
			$conn .= "\treturn \$GLOBALS['ldap_link'];\n";
			$conn .= "}\n";
			$conn .= "\$GLOBALS['ldap_base'] = \"$base_ldap\";\n";
			$conn .= "\$GLOBALS['ldap_present'] = true;\n";
		}
		$conn .= "?".">";
		$myFile = fopen("inc_connect_install.php3", "wb");
		fputs($myFile, $conn);
		fclose($myFile);

		echo "<p><form action='install.php' method='post'>";
		echo "<input type='hidden' name='etape' value='ldap5'>";
		echo "<fieldset><label><B>"._T('info_statut_utilisateurs_1')."</B></label><BR>";
		echo _T('info_statut_utilisateurs_2')." ";
		echo "<p>";
		echo "<input type='Radio' name='statut_ldap' value=\"6forum\" id='visit'>";
		echo "<label for='visit'><b>"._T('info_visiteur_1')."</b></label> "._T('info_visiteur_2')."<br>";
		echo "<input type='Radio' name='statut_ldap' value=\"1comite\" id='redac' CHECKED>";
		echo "<label for='redac'><b>"._T('info_redacteur_1')."</b></label> "._T('info_redacteur_2')."<br>";
		echo "<input type='Radio' name='statut_ldap' value=\"0minirezo\" id='admin'>";
		echo "<label for='admin'><b>"._T('info_administrateur_1')."</b></label> "._T('info_administrateur_2')."<br>";
	
		echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";

		echo "</FORM>";
	}

	install_html_end();
}

else if ($etape == 'ldap3') {
	install_html_start();

	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_chemin_acces_1')."</FONT>";

	echo "<P>"._T('info_chemin_acces_2');

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	$result = @ldap_read($ldap_link, "", "objectclass=*", array("namingContexts"));
	$info = @ldap_get_entries($ldap_link, $result);

	echo "<form action='install.php' method='post'>";
	echo "<input type='hidden' name='etape' value='ldap4'>";
	echo "<input type='hidden' name='adresse_ldap' value=\"$adresse_ldap\">";
	echo "<input type='hidden' name='port_ldap' value=\"$port_ldap\">";
	echo "<input type='hidden' name='login_ldap' value=\"$login_ldap\">";
	echo "<input type='hidden' name='pass_ldap' value=\"$pass_ldap\">";

	echo "<fieldset>";

	$checked = false;

	if (is_array($info) AND $info["count"] > 0) {
		echo "<P>"._T('info_selection_chemin_acces');
		echo "<UL>";
		$n = 0;
		for ($i = 0; $i < $info["count"]; $i++) {
			$names = $info[$i]["namingcontexts"];
			if (is_array($names)) {
				for ($j = 0; $j < $names["count"]; $j++) {
					$n++;
					echo "<input name=\"base_ldap\" value=\"".htmlspecialchars($names[$j])."\" type='Radio' id='tab$n'";
					if (!$checked) {
						echo " CHECKED";
						$checked = true;
					}
					echo ">";
					echo "<label for='tab$n'>".htmlspecialchars($names[$j])."</label><BR>\n";
				}
			}
		}
		echo "</UL>";
		echo _T('info_ou')." ";
	}
	echo "<input name=\"base_ldap\" value=\"\" type='Radio' id='manuel'";
	if (!$checked) {
		echo " CHECKED";
		$checked = true;
	}
	echo ">";
	echo "<label for='manuel'>"._T('entree_chemin_acces')."</label> ";
	echo "<input type='text' name='base_ldap_text' class='formo' value=\"ou=users, dc=mon-domaine, dc=com\" size='40'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";
	echo "</FORM>";

	install_html_end();

}

else if ($etape == 'ldap2') {
	install_html_start();

	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size='3'>"._T('titre_connexion_ldap')."</font>";
	echo "<p>";

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	$r = @ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	if ($ldap_link && ($r || !$login_ldap)) {
		echo "<B>"._T('info_connexion_ldap_ok');

		echo "<form action='install.php' method='post'>";
		echo "<input type='hidden' name='etape' value='ldap3'>";
		echo "<input type='hidden' name='adresse_ldap' value=\"$adresse_ldap\">";
		echo "<input type='hidden' name='port_ldap' value=\"$port_ldap\">";
		echo "<input type='hidden' name='login_ldap' value=\"$login_ldap\">";
		echo "<input type='hidden' name='pass_ldap' value=\"$pass_ldap\">";

		echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_ldap_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_ldap_echec_2');
		echo "<br>"._T('avis_connexion_ldap_echec_3');
	}

	install_html_end();
}

else if ($etape == 'ldap1') {
	install_html_start();

	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size=3>"._T('titre_connexion_ldap')."</font>";

	echo "<P>"._T('entree_informations_connexion_ldap');

	$adresse_ldap = 'localhost';
	$port_ldap = 389;

	// Recuperer les anciennes donnees (si presentes)
	if (@file_exists("inc_connect_install.php3")) {
		$s = @join('', @file("inc_connect_install.php3"));
		if (ereg('ldap_connect\("(.*)","(.*)"\)', $s, $regs)) {
			$adresse_ldap = $regs[1];
			$port_ldap = $regs[2];
		}
	}

	echo "<p><form action='install.php' method='post'>";
	echo "<input type='hidden' name='etape' value='ldap2'>";
	echo "<fieldset><label><B>"._T('entree_adresse_annuaire')."</B><BR></label>";
	echo _T('texte_adresse_annuaire_1')."<BR>";
	echo "<input type='text' name='adresse_ldap' class='formo' value=\"$adresse_ldap\" size='20'><P>";

	echo "<label><B>"._T('entree_port_annuaire')."</B><BR></label>";
	echo _T('texte_port_annuaire')."<BR>";
	echo "<input type='text' name='port_ldap' class='formo' value=\"$port_ldap\" size='20'><P></fieldset>";

	echo "<p><fieldset>";
	echo _T('texte_acces_ldap_anonyme_1')." ";
	echo "<label><B>"._T('entree_login_ldap')."</B><BR></label>";
	echo _T('texte_login_ldap_1')."<br>";
	echo "<input type='text' name='login_ldap' class='formo' value=\"\" size='40'><P>";

	echo "<label><B>"._T('entree_passe_ldap')."</B><BR></label>";
	echo "<input type='password' name='pass_ldap' class='formo' value=\"\" size='40'></fieldset>";

	echo "<p><DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Valider' value='"._T('bouton_suivant')." >>'>";

	echo "</form>";

	install_html_end();
}


?>
