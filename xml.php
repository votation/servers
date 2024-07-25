<?php

// ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? //
//                              ---   VOTATION.FR   ---                            //
//                                                                                 //
// Copyright (c) 2016 David Espic. Tous droits Réservés.                           //
// Permission d'utiliser, copier, modifier et distribuer le logiciel               //
// ainsi que sa documentation pour toute application gratuitement                  //
// sous condition que le présent copyright soit apparant sur chaque copie.         //
//                                                                                 //
// Remerciements spécial à Sylvain Viart (SysAdmin) sylvain@opensource-expert.com  //
// Remerciements aussi à Bruno Miguel (Web Design) antunes@geedesign.fr            //
//                                                                                 //
// Remerciements à Linus Torvalds (Linux) et Rasmus Lerdorf (PHP) et toute         //
// la comunauté open-source pour les outils (W3C / Mozilla / Chromium...)          //
//                                                                                 //
// ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? //


//========================================================================
// DEBUT DU CHRONOMETRE DE SCRIPT
//========================================================================
$executionstarttime=microtime();


//========================================================================
// MICROTIME AS FLOAT
//========================================================================
function microdelai($starttime){ 
		list($usec, $sec) = explode(" ", $starttime);
		$deb = ((float)$usec + (float)$sec);
		list($usec, $sec) = explode(" ", microtime());
		$fin = ((float)$usec + (float)$sec);
		return substr($fin - $deb, 0, 5);
} 


//========================================================================
// PREVENIR DES INJECTIONS SQL DANS POST ET GET
//========================================================================
$pregs = array();
$pregs['/\s\s+/'] = ' ';
$pregs['/\bSELECT\b/i'] = 'SELеCт';
$pregs['/\bINSERT\b/i'] = 'INSеRт';
$pregs['/\bUPDATE\b/i'] = 'UPDΑтE';
$pregs['/\bTRUNCATE\b/i'] = 'TRUNCΑтE';
$pregs['/\bDROP TABLE\b/i'] = 'DROPтΑBLE';
foreach ($pregs as $pattern => $replacement)
{
	foreach ($_POST as $key => $value)
		$_POST[$key] = preg_replace($pattern, $replacement, $_POST[$key]);
	foreach ($_GET as $key => $value)
		$_GET[$key] = preg_replace($pattern, $replacement, $_GET[$key]);
}

//========================================================================
// SETUP
//========================================================================
if (isset($_GET['setup']))
{
	echo "<html><body>";

	// TESTER OU CREER LE REPERTOIRE DE CONFIGURATION (+ .HTACCESS)
	// SECTION À SUPPRIMER UNE FOIS QUE VOUS PASSEZ EN PRODUCTION
	if (isset($_GET['login']) && isset($_GET['passwd']) && isset($_GET['base']))
	{
		echo "<h1>MAKE/CHECK ./cfg</h1>\r\n";
		if (!is_dir("./cfg")) mkdir("./cfg", 0755, true);
	
		echo "<h1>MAKE/CHECK ./cfg/.htaccess</h1>\r\n";
		$fd=fopen ("./cfg/.htaccess", "w+");
		fwrite ($fd, "Order deny,allow\r\n");
		fwrite ($fd, "allow from 127.0.0.1\r\n");
		fwrite ($fd, "deny from all\r\n");
		fclose ($fd);
		echo "<textarea>".file_get_contents("./cfg/.htaccess")."</textarea><br>\r\n";

		if (!file_exists("./.htaccess"))
		{
			echo "<h1>MAKE/CHECK ./.htaccess</h1>\r\n";
			$fd=fopen ("./.htaccess", "w+");
			fwrite ($fd, "Options +FollowSymlinks\r\n");
			fwrite ($fd, "RewriteEngine On\r\n");
			fwrite ($fd, "RewriteRule ^index\.xml$ /xml.php [L]\r\n");
			fwrite ($fd, "RewriteRule ^index\.htm$ /htm.php [L]\r\n");
			fclose ($fd);
			echo "<textarea>".file_get_contents("./.htaccess")."</textarea><br>\r\n";
		}
		$dblink = mysql_connect("localhost", $_GET['login'], $_GET['passwd']) or die("Setup Erreur MySQL ".mysql_error());
		if (mysql_select_db($_GET['base'], $dblink))
		{
			echo "<h1>MAKE/CHECK ./cfg/cfg_vote.php</h1>\r\n";
			$fd=fopen ("./cfg/cfg_vote.php", "w+");
			fwrite ($fd, "<?php\r\n");
			fwrite ($fd, "// LES ADRESSES IP DE VOTATION.FR\r\n");
			fwrite ($fd, "\$VotationServerIps = array('91.134.134.242');\r\n");
			fwrite ($fd, "// MOT DE PASSE DU SERVEUR votation.fr POUR POSTER LES CLEFS\r\n");
			if (!isset($_GET['keycode'])) $_GET['keycode'] = hash("sha256", "As1mplePassVV0rD".rand(1111, 9999).date('u'));
			fwrite ($fd, "\$VotationKeyCode = '".$_GET['keycode']."';\r\n");
			fwrite ($fd, "\r\n");
			fwrite ($fd, "// Accès base de donnée\r\n");
			fwrite ($fd, "\$masterhost='localhost';\r\n");
			fwrite ($fd, "\$masterlogin='".$_GET['login']."';\r\n");
			fwrite ($fd, "\$masterpasswd='".$_GET['passwd']."';\r\n");
			fwrite ($fd, "\$masterbase='".$_GET['base']."';\r\n");
			fwrite ($fd, "?>\r\n");
			fclose ($fd);
			echo "<textarea>".file_get_contents("./cfg/cfg_vote.php")."</textarea><br>\r\n";
			include_once("./cfg/cfg_vote.php");
		}
	}


//	if (($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"]) && ($_SERVER["REMOTE_ADDR"] != '127.0.0.1')) die("\t<error>PRIVATE_SCRIPT</error>\r\n</root>");

	// POUR DEBUG
	ini_set('error_reporting', E_ALL);
	ini_set("display_errors", 1); 
	
	// RECUPERATION LISTE SERVEURS SUR VOTATION.FR
	echo "<h1>GET CONFIG FROM www.votation.fr</h1>\r\n";
	$data = file_get_contents("https://www.votation.fr/index.xml?config");
	$data = str_replace('<?xml version="1.0" encoding="UTF-8"?>', "", $data);
	$data = str_replace(array("\r", "\n", "\t"), "", $data);
	
	// SI ON A UNE BALISE ERREUR ON ARRETE...
	if (($deb = strpos($data, "<error>"))) {
		$fin = strpos($data, "</error>", $deb);
		die("VOTATION SERVER RETURNED AN ERROR : ".substr($data, $deb+7, $fin-$deb-7));
	}
	
	// PARSER LE XML
	$serverUrls = array(); $fin = 0;
	while (($deb = strpos($data, "<serverUrl>", $fin))) {
		$fin = strpos($data, "</serverUrl>", $deb);
		$url = substr($data, $deb+11, $fin-$deb-11);
		$serverUrls[] = $url;
	}
	
	$me = "https://".$_SERVER["HTTP_HOST"].substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "?"));
	$MyRank = array_search($me, $serverUrls);
	echo "MYRANK=".$MyRank." (".$me.")<br>\r\n";
	if ($MyRank > 0) $PreviousServer = $serverUrls[$MyRank-1]; else $PreviousServer = '';
	if ($MyRank < (count($serverUrls)-1)) $NextServer = $serverUrls[$MyRank+1]; else $NextServer = '';

	// LES SUJETS / QUESTIONS
	$projects = array(); $fin = 0;
	while (($deb = strpos($data, "<Titre>", $fin))) {
		$fin = strpos($data, "</Titre>", $deb);
		$Titre = substr($data, $deb+7, $fin-$deb-7);
		$projects[] = $Titre;
	}
	
	// LE NOM DE LA VOTATION
	$deb = strpos($data, "<Date>");
	$fin = strpos($data, "</Date>", $deb);
	$Date = substr($data, $deb+6, $fin-$deb-6);

	// LE NOMBRE DE PROJETS DE LA VOTATION
	$deb = strpos($data, "<ProjectsCount>");
	$fin = strpos($data, "</ProjectsCount>", $deb);
	$VotationProjectCount = substr($data, $deb+15, $fin-$deb-15);

	// LE REPERTOIRE DE LA VOTATION
	$deb = strpos($data, "<Dir>");
	$fin = strpos($data, "</Dir>", $deb);
	$Dir = substr($data, $deb+5, $fin-$deb-5);
	if (is_dir("./".$Dir) || mkdir("./".$Dir, 0755, true))
		echo "MKDIR : ./".$Dir."<br>\r\n";

	// ECRIRE LE FICHIER servers.php
	$fd=fopen ("./cfg/servers.php", "w+");
	fwrite ($fd, "<?php\r\n");
	fwrite ($fd, "// MyUrl = ".$me."\r\n");
	fwrite ($fd, "// MyRank = ".$MyRank."\r\n");
	fwrite ($fd, "// Updated ".date('Y-m-d H:i:s')."\r\n");
	fwrite ($fd, "\$VotationDate = '".$Date."';\r\n");
	fwrite ($fd, "\$VotationProjectCount = '".$VotationProjectCount."';\r\n");
	fwrite ($fd, "\$VotationDir = '".$Dir."';\r\n");
	fwrite ($fd, "\$PreviousServer = '".$PreviousServer."';\r\n");
	fwrite ($fd, "\$NextServer = '".$NextServer."';\r\n");
	fwrite ($fd, "\$TabServersList = array(\r\n");
	for ($I=0; $I<count($serverUrls); $I++)
		fwrite ($fd, "\t\"".$serverUrls[$I]."\",\r\n");
	fwrite ($fd, ");\r\n");
	fwrite ($fd, "?>\r\n");
	fclose ($fd);
	echo "<textarea>".file_get_contents("./cfg/servers.php")."</textarea><br>\r\n";


	// CREATION DE LA TABLE BULLETINS EN BASE DE DONNEES
	echo "<h1>CREATE BALLOTS TABLE</h1>\r\n";
	$sql = <<<EOD
	CREATE TABLE IF NOT EXISTS `ballots` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`IP` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Adresse IP du votant',
		`UID` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Identifiant unique du votant',
		`USK` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Mot de passe temporaire du votant',
		`IDS` int(11) DEFAULT NULL COMMENT 'Le score de certification',
		`UNS` int(11) DEFAULT NULL COMMENT 'Le score de comprehension',
		`DON` int(11) DEFAULT NULL COMMENT 'Montants des dons',
		`V1` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`V2` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`V3` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`V4` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`V5` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`V6` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`V7` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`V8` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`V9` enum('OUI','ABS','NON') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ABS',
		`MD5` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Signature MD5 de la Photo',
		`AddTime` datetime DEFAULT NULL COMMENT 'Date d''ajout UID',
		`PostTime` datetime DEFAULT NULL COMMENT 'Reception du bulletin',
		PRIMARY KEY (`ID`),
		KEY `UID` (`UID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOD;
	mysql_query($sql) or die (mysql_error());
	echo "<textarea>".$sql."</textarea>\r\n";
	mysql_query("CHECK TABLE `ballots`;") or die (mysql_error());
	echo "<h3>SQL CHECK : `ballots`</h3>\r\n";
	mysql_query("OPTIMIZE TABLE  `ballots`;") or die (mysql_error());
	echo "<h3>SQL OPTIMIZE : `ballots`</h3>\r\n";


	// CREATION DE LA TABLE RESULTATS EN BASE DE DONNEES
	echo "<h1>CREATE RESULTS TABLE</h1>\r\n";
	$sql = "CREATE TABLE IF NOT EXISTS `results` (\r\n";
	for ($I=1; $I<($VotationProjectCount+1); $I++)
	{
		$sql.= "`T".$I."OUI` int(11) DEFAULT 1 COMMENT 'Total OUI ".$I."',\r\n";
		$sql.= "`T".$I."ABS` int(11) DEFAULT 1 COMMENT 'Total ABS ".$I."',\r\n";
		$sql.= "`T".$I."NON` int(11) DEFAULT 1 COMMENT 'Total NON ".$I."',\r\n";
		$sql.= "`I".$I."OUI` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Id OUI ".$I."',\r\n";
		$sql.= "`I".$I."ABS` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Id ABS ".$I."',\r\n";
		$sql.= "`I".$I."NON` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Id NON ".$I."',\r\n";
		$sql.= "`U".$I."OUI` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Comp OUI ".$I."',\r\n";
		$sql.= "`U".$I."ABS` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Comp ABS ".$I."',\r\n";
		$sql.= "`U".$I."NON` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Comp NON ".$I."',\r\n";
		$sql.= "`F".$I."OUI` DECIMAL(11,2) DEFAULT 1 COMMENT 'Total Dons OUI ".$I."',\r\n";
		$sql.= "`F".$I."ABS` DECIMAL(11,2) DEFAULT 1 COMMENT 'Total Dons ABS ".$I."',\r\n";
		$sql.= "`F".$I."NON` DECIMAL(11,2) DEFAULT 1 COMMENT 'Total Dons NON ".$I."',\r\n";
		$sql.= "`X".$I."OUI` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Id x Comp OUI ".$I."',\r\n";
		$sql.= "`X".$I."ABS` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Id x Comp ABS ".$I."',\r\n";
		$sql.= "`X".$I."NON` DECIMAL(11,2) DEFAULT 1 COMMENT 'Score Id x Comp NON ".$I."',\r\n";
	}
	$sql.= "`TMP` datetime DEFAULT NULL COMMENT 'Dernier increment'\r\n";
	$sql.= ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

//	echo "\t<debug><![CDATA[SQL THIS : ".$sql."]]></debug>\r\n";

	mysql_query($sql) or die (mysql_error());
	echo "<textarea>".$sql."</textarea>\r\n";
	mysql_query("CHECK TABLE `results`;") or die (mysql_error());
	echo "<h3>SQL CHECK : `results`</h3>\r\n";
	mysql_query("OPTIMIZE TABLE  `results`;") or die (mysql_error());
	echo "<h3>SQL OPTIMIZE : `results`</h3>\r\n";
	// INSERT LE SEUL ENREGISTREMENT
	if (mysql_num_rows(mysql_query("SELECT * FROM `results`")) == 0)
		mysql_query("INSERT INTO `results` (TMP) VALUES (NOW())") or die (mysql_error());

	die("</body></html>");
}


//========================================================================
// LE FICHIER CONFIG DU SERVEUR DE STOCKAGE
//========================================================================
include_once("./cfg/cfg_vote.php");
if (file_exists("./cfg/".$servercfg)) include_once("./cfg/".$servercfg);
$http_url = "https://".$_SERVER['HTTP_HOST'];

//========================================================================
// CONNEXION AU SERVEUR DE BDD 
//========================================================================
$dblink = mysql_connect($masterhost, $masterlogin, $masterpasswd) or die("\t<error>Erreur MySQL ".mysql_error()."</error>\r\n</root>"); 
mysql_select_db($masterbase, $dblink) or die("\t<error>Erreur MySQL : ".mysql_error()."</error>\r\n</root>");
$masterhost='';
$masterlogin='';
$masterpasswd='';


//========================================================================
// FAIRE PATIENTER LE VOTANT (ANTI FLOOD / BRUTAL HACKING) SAUF VOTATION POUR LES CLEFS ET LES SERVEURS OFFICIELS
//========================================================================
if (($_SERVER["REMOTE_ADDR"] != "127.0.0.1") && !in_array($_SERVER["REMOTE_ADDR"], $VotationServerIps)) usleep(rand(100000, 500000));


//========================================================================
// ACCEPTER LE CROSS-DOMAIN DEPUIS https://www.votation.fr
//========================================================================
if (isset($_SERVER['HTTP_ORIGIN'])) switch ($_SERVER['HTTP_ORIGIN']) {
	case 'https://www.votation.fr':
	header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
	header('Access-Control-Max-Age: 1000');
	header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
	break;
}


//========================================================================
// ENTETE ET CONFIGURATION
//========================================================================
ini_set('default_charset', 'utf-8');
header('Content-Type: text/xml; charset=utf-8');
header('Last-Modified: ' . date('r'));
header("Expires: ".date('D, d M Y H:i:s')." GMT");
header("Content-Disposition: inline; filename='".str_replace("/", "", $_SERVER["REQUEST_URI"])."'"); 
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");


//========================================================================
// DEBUT DU XML
//========================================================================
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";
echo "<root>\r\n";

//========================================================================
// POUR UNE NOUVELLE CLEF PUBLIQUE VERIF QUE CA VIENT DU SERVEUR VOTATION.FR
//========================================================================
if (isset($_POST['UID']) && isset($_POST['IDS']) && isset($_POST['UNS']) && isset($_POST['DON']))
{
	if (in_array($_SERVER["REMOTE_ADDR"], $VotationServerIps) || ($_SERVER["REMOTE_ADDR"] == "127.0.0.1"))
	{
		// VERIF QUE VOTATION PASSE LE BON KEYCODE
		if ($_POST['KeyCode'] == $VotationKeyCode)
		{
			$UID = preg_replace("/[^A-Za-z0-9]/", '', $_POST['UID']);
			$IDS = 1 * $_POST['IDS'];
			$UNS = 1 * $_POST['UNS'];
			$DON = 1 * $_POST['DON'];
			$USK = rand(100000, 999999);
			// ON TEST UNE UPDATE SI LE UID EXISTE DEJA SINON ON AJOUTE
			mysql_query("UPDATE `ballots` SET USK='".$USK."', IDS='".$IDS."', UNS='".$UNS."', DON='".$DON."' WHERE UID='".$UID."' AND PostTime IS NULL") or die (mysql_error());
			if (mysql_affected_rows() == 0)
				mysql_query("INSERT INTO `ballots` (`UID`, `USK`, `IDS`, `UNS`, `DON`, `AddTime`) VALUES ('".$UID."', '".$USK."', '".$IDS."', '".$UNS."', '".$DON."', NOW())") or die (mysql_error());
			echo "\t<USK>".$USK."</USK>\r\n";
		}
		else die("\t<error>WRONG_KEYS_CODE</error>\r\n</root>");
	}
	else die("\t<error>VOTATION_SERVER_ONLY</error>\r\n</root>");
}


//========================================================================
// UN BULLETIN DE VOTE ARRIVE DEPUIS UNE ADRESSE DIFFÉRENTE
//========================================================================
//echo "\t<debug>POST=".print_r($_POST, true)."</debug>\r\n";
if (isset($_POST['UID']) && isset($_POST['USK']) && isset($_POST['PNG']) && isset($_POST['VOT']))
{
	if (!in_array($_SERVER["REMOTE_ADDR"], $VotationServerIps))
	{
		if ($_VOTER = mysql_fetch_assoc(mysql_query("SELECT * FROM `ballots` WHERE `UID`='".addslashes($_POST['UID'])."' AND `USK`='".addslashes($_POST['USK'])."'")) or die (mysql_error()))
		{
			$VOT = explode("|", $_POST['VOT']);
			if (count($VOT) == $VotationProjectCount)
			{
				// Concatener les choix : créer le répertoire et écrire la requete
				$reqarg = "";
				for ($I=0; $I<$VotationProjectCount; $I++)
				{
					$VotationDir.="/".($I+1).$VOT[$I];
					$reqarg.="V".($I+1)."='".$VOT[$I]."', ";
				}
				// Créer ou vérifier la presence du répertoire
				if (is_dir("./".$VotationDir) || mkdir("./".$VotationDir, 0755, true))
				{
					// Suprimer l'entête
					$data = urldecode($_POST['PNG']);
					$data = str_replace(' ', '+', $data);
					$data = str_replace('data:image/png;base64,', '', $data);
					$md5 = md5($data);
					if (strlen($data) < 600000)
					{
						$data = base64_decode($data);
						$file = "/".$VotationDir."/".$md5.".png";
						$fileUrl = $http_url.$file;
						$newimage = imagecreatefromstring($data);
						if ($newimage !== false)
						{
							//imagesavealpha($newimage, true);
							imagepng($newimage, ".".$file, 9);
							imagedestroy($newimage);
							if (file_exists(".".$file))
							{
								// INCREMENTER LES COMPTEURS
								$sql = "UPDATE `results` SET ";
								for ($I=0; $I<$VotationProjectCount; $I++)
								{
									$sql.= "`T".($I+1).$VOT[$I]."` = `T".($I+1).$VOT[$I]."` + 1, ";
									$sql.= "`I".($I+1).$VOT[$I]."` = `I".($I+1).$VOT[$I]."` + ".($_VOTER['IDS']/100).", ";
									$sql.= "`U".($I+1).$VOT[$I]."` = `U".($I+1).$VOT[$I]."` + ".($_VOTER['UNS']/100).", ";
									$sql.= "`F".($I+1).$VOT[$I]."` = `F".($I+1).$VOT[$I]."` + ".($_VOTER['DON']).", ";
									$sql.= "`X".($I+1).$VOT[$I]."` = `X".($I+1).$VOT[$I]."` + ".round($_VOTER['IDS']*$_VOTER['UNS']/10000, 2).", ";
								}
								$sql.= "`TMP` = NOW() WHERE 1=1";
								echo "\t<sql>".$sql."</sql>\r\n";
								mysql_query($sql) or die (mysql_error());

//								echo "\t<debug><![CDATA[".$data."]]></debug>\r\n";
								echo "\t<success>".$fileUrl."</success>\r\n";
								mysql_query("UPDATE `ballots` SET MD5='".$md5."', ".$reqarg." PostTime=NOW() WHERE UID='".addslashes($_POST['UID'])."'") or die (mysql_error());
								
								
								// RENVOYER LES RESULTATS
								$results = mysql_query("SELECT * FROM `results`") or die (mysql_error());
								echo "\t<resultString>".implode("|", mysql_fetch_row($results))."</resultString>\r\n";
							}
							else echo "\t<error>L'image ".$file." n'a pas pu être enregistrée.</error>\r\n";
						}
						else echo "\t<error>L'image n'a pas pu être crée.</error>\r\n";
					}
					else echo "\t<error>L'image est trop volumineuse. (>0.5Mo) La taille recomandées est 250x250.</error>\r\n";
				}
				else echo "\t<error>Impossible de créer le répertoire.</error>\r\n";
			}
			else echo "\t<error>".$VotationProjectCount." projets sont à voter. Vous devez vous prononcer pour (OUI), contre (NON), ou abstention (ABS).</error>\r\n";
		}
		else die ("\t<error>VOTER_UID-USK_MISMATCH</error>\r\n</root>");
	}
	else die("\t<error>VOTATION_CUSTOMER_ONLY</error>\r\n</root>");
}


//========================================================================
// AFFICHER LES RESULTATS EN COURS
//========================================================================
if (isset($_GET['results'])) 
{
	$results = mysql_query("SELECT * FROM `results`") or die (mysql_error());
	echo "\t<results>\r\n";
	foreach(mysql_fetch_assoc($results) as $key => $value)
		echo "\t\t<".$key.">".$value."</".$key.">\r\n";
	echo "\t</results>\r\n";
}

//========================================================================
// SURVEILLANCE DU FORMULAIRE, LIBRAIRIES, CONFIG DE votation.fr
// COMPARER MD5 AVEC MD5 DE LA COPIE LOCALE (SE FAIT EN LOCALHOST)
//========================================================================
/*
if (isset($_GET['track']) && (($_SERVER["REMOTE_ADDR"] == "127.0.0.1") || ($_SERVER["REMOTE_ADDR"] == $_SERVER['SERVER_ADDR'])))
{
	$ListeOfficielle = Array (
		'Form' => 'http://www.votation.fr',
		'Lib' => 'http://www.votation.fr/lib/votation.js',
		'Config' => 'http://www.votation.fr/xml.php?config',
	);

	foreach ($ListeOfficielle as $key => $value)
	{
		$source = file_get_contents($value);
		echo "\t<Script>\r\n";
		echo "\t\t<Name>".$key."</Name>\r\n";
		echo "\t\t<Url>".$value."</Url>\r\n";
		echo "\t\t<Len>".strlen($source)."</Len>\r\n";
	//	echo "\t\t<Deb><![CDATA[".substr($source, 0, 30)."]]></Deb>\r\n";
	//	echo "\t\t<Fin><![CDATA[".substr($source, -30)."]]></Fin>\r\n";
		echo "\t\t<Crc>".md5($source)."</Crc>\r\n";
		echo "\t</Script>\r\n";

		// Historiser les modifications éventuelles et signaler en cas de changement du contenu pendant période de lecture
	}
}
*/


//========================================================================
// INFO DE FIN DE PAGE
//========================================================================
echo "\t<info>\r\n";
echo "\t\t<sql>ballots</sql>\r\n";
echo "\t\t<res>results</res>\r\n";
echo "\t\t<dir>".$VotationDir."</dir>\r\n";
echo "\t\t<cfg>".$servercfg."</cfg>\r\n";
echo "\t</info>\r\n";
echo "\t<time>".date('Y-m-d H:i:s')."</time>\r\n";
echo "\t<ms>".microdelai($executionstarttime)."</ms>\r\n";
echo "\t<url>".$_SERVER["REQUEST_URI"]."</url>\r\n";
echo "\t<you>".$_SERVER["REMOTE_ADDR"]."</you>\r\n";
echo "\t<me>".$_SERVER['SERVER_ADDR']."</me>\r\n";
echo "\t<uri>".$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"]."</uri>\r\n";
echo "\t<php>".__FILE__."</php>\r\n";
echo "</root>\r\n";


?>
