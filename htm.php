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
session_start();
$_SESSION['timestamp']=date('U');

error_reporting(E_ALL);

//========================================================================
// VARIABLES DE BASE (CERTAINES DOIVENT PASSER AU FICHIER CFG)
//========================================================================
// include_once("./cfg/cfg_vote.php");
if ($_SERVER['HTTP_HOST'] == "votation.union-populaire-republicaine.fr") include_once("./cfg/cfg_upr.php");
if ($_SERVER['HTTP_HOST'] == "votation.democratiedirectesinonrien.fr") include_once("./cfg/cfg_ddsr.php");
if (file_exists("./".$directory."/servers.php")) include_once("./".$directory."/servers.php"); else $TabserverIPs = array();
$http_url = "https://".$_SERVER['HTTP_HOST'];


//========================================================================
// FAIRE PATIENTER LE CLIENT
//========================================================================
usleep(rand(100000, 500000));


//========================================================================
// CONNEXION AU SERVEUR DE BDD 
//========================================================================
$dblink = mysql_connect($masterhost, $masterlogin, $masterpasswd) or die("\t<error>Erreur MySQL ".mysql_error()."</error>\r\n</root>"); 
mysql_select_db($masterbase, $dblink) or die("\t<error>Erreur MySQL : ".mysql_error()."</error>\r\n</root>");
$masterhost='';
$masterlogin='';
$masterpasswd='';


//========================================================================
// ENTETES DIVERSES
//========================================================================
header('Last-Modified: ' . date('r', filectime(__FILE__)));
header("Expires: ".date('r', time()+3600)); // date('D, d M Y H:i:s')." GMT" // Last-Modified: Tue, 15 Nov 1994 12:45:26 GMT
header("Content-Disposition: inline; filename='".str_replace("/", "", $_SERVER["REQUEST_URI"])."'"); 
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("content-language: ".$goltog_lang);
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'utf-8');
set_time_limit(1);


//========================================================================
// RENVOYER LES RESULTATS
//========================================================================
$_RESULTS = mysql_query("SELECT * FROM `".$resultstable."`") or die (mysql_error()) or die (mysql_error());
$_RESULT = implode("|", mysql_fetch_row($_RESULTS));
?>

<html>
<head>
	<meta charset="UTF-8">
	<title>Resultat votation <?php echo $$VotationDate ?></title>
	<meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0' >
	<link rel='apple-touch-icon-precomposed' sizes='120x120' href='./img/Logo_120px.png' >
	<meta property='og:url' content='<?php echo $http_url; ?>'>
	<meta property='og:type' content='website'>
	<meta property='og:title' content='Voter Oui ! Elire Non !'>
	<meta property='og:description' content='Un outil au service de la démocratie.'>
	<meta property='fb:app_id' content='https://www.facebook.com/votation.fr/'>
	<meta property='og:image' content='<?php echo $http_url; ?>/img/OgLogo.png'>
	<meta property='og:image:type' content='image/png'>
	<meta property='og:image:width' content='200'>
	<meta property='og:image:height' content='200'>
	<style>

#Wrap { background-color: #DDD; font-size:12px; text-align:center; max-width:660px; margin-left:auto; margin-right:auto; background-image:url(/img/Mariane.png); background-repeat: no-repeat; background-position: bottom right; background-size: contain; }
#Header, #ResultsList { display:block; padding:10px; margin:20px; border:solid 1px grey; border-radius:5px; background-color:white; }

li { list-style-type: none; }
a { text-decoration: underline; cursor:pointer; color:#333; }

#ResultsList li { min-width:600px; max-width:600px; padding:0px; font-size:10px; text-align:left; background-color:#FFFFFF; }
.RES { background-color:#FFFFFF; text-align:right; color:black; display:inline-block; height:12px; width:250px; margin:0px 0px 3px 0px; padding:1px 1px 1px 0px;}
.OUI { background-color:#234D9B; text-align:right; color:white; display:inline-block; height:12px; margin:0px 0px 3px 0px; padding:1px 1px 1px 0px; }
.ABS { background-color:#DDDDDD; text-align:center; color:#888; display:inline-block; height:12px; margin:0px 0px 3px 0px; padding:1px 0px 1px 0px; }
.NON { background-color:#E53916; text-align:left; color:white; display:inline-block; height:12px; margin:0px 0px 3px 0px; padding:1px 0px 1px 1px; }
.X { font-size:16px; height:18px; padding-top:2px; padding-bottom:2px; }

	</style>
<script type='text/javascript' >

//============================================
// Fonction de remplacement à document.getElementById()
//============================================
function dge(id)
{
	return document.getElementById(id);
}
//============================================
// AFFICHER LES RESULTATS D'UNE QUESTION / SUJET
//============================================
function displayResult(R, OUI, ABS, NON, S, label, unit)
{
	OUI = parseFloat(OUI);
	ABS = parseFloat(ABS);
	NON = parseFloat(NON);
	
	var TOT = OUI + ABS + NON;
	var OUI100 = Math.round(100 * OUI / TOT);
	var ABS100 = Math.round(100 * ABS / TOT);
	var NON100 = Math.round(100 * NON / TOT);
	
	var LEN = 300;
	var OUILEN = Math.round(LEN * OUI / TOT);
	var NONLEN = Math.round(LEN * NON / TOT);
	var ABSLEN = LEN - OUILEN - NONLEN;
	
	console.log("Rank="+R+" OUI="+OUI+" ABS="+ABS+" NON="+NON+" TOT="+TOT+" {"+S+" "+label+"}");
	console.log("OUILEN="+OUILEN+" ABSLEN="+ABSLEN+" NONLEN="+NONLEN);

	dge("RES"+R+S).innerHTML = label;

	if (OUI100>5) dge("OUI"+R+S).innerHTML = OUI100+"%";
	else dge("OUI"+R+S).innerHTML = "-";
		
	if (ABS100>5) dge("ABS"+R+S).innerHTML = ABS100+"%";
	else dge("ABS"+R+S).innerHTML = "-";

	if (NON100>5) dge("NON"+R+S).innerHTML = NON100+"%";
	else dge("NON"+R+S).innerHTML = "-";

	dge("OUI"+R+S).title = "OUI : " + OUI + unit + " ("+OUI100+"%)";
	dge("ABS"+R+S).title = "ABS : " + ABS + unit + " ("+ABS100+"%)";
	dge("NON"+R+S).title = "NON : " + NON + unit + " ("+NON100+"%)";

	dge("OUI"+R+S).style.width = OUILEN+"px";
	dge("ABS"+R+S).style.width = ABSLEN+"px";
	dge("NON"+R+S).style.width = NONLEN+"px";
}
//============================================
// FONCTION AFFICHER TOUS LES RESULTATS TEMPORAIRES
//============================================
function displayResults()
{
	var resultString = '<?php echo $_RESULT; ?>';
	var Tab = resultString.split('|');
	var count = <?php echo count($TabProjects); ?>;
	
	// Si on a bien le bon nombre de valeurs
	if (Tab.length = 15 * count + 1)
	{
		// Pour chaque question / sujet
		for (var i=0; i<count; i++)
		{
			var oui = parseInt(Tab[15*i+0]);
			var abs = parseInt(Tab[15*i+1]);
			var non = parseInt(Tab[15*i+2]);
			dge("voterCount_"+(i+1)).innerHTML = "(" + (oui+non) + " décisions sur " + (oui+abs+non) + " votants)";
		
			displayResult(i+1, Tab[15*i+0], Tab[15*i+1], Tab[15*i+2], "T", "Par nombre de voix exprimées", " voix exprimées");
			displayResult(i+1, Tab[15*i+3], Tab[15*i+4], Tab[15*i+5], "I", "Pondéré par certification", " voix pondérées");
			displayResult(i+1, Tab[15*i+6], Tab[15*i+7], Tab[15*i+8], "U", "Pondéré par compréhension", " voix pondérées");
			displayResult(i+1, Tab[15*i+9], Tab[15*i+10], Tab[15*i+11], "F", "Pondéré par total les dons", "€");
			displayResult(i+1, Tab[15*i+12], Tab[15*i+13], Tab[15*i+14], "X", "Pondérés par certification et compréhension", " voix pondérées");
		}
	}
}

</script>

</head>

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/fr_FR/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

<body id='Wrap' >

<!-- Debut du contenu -->
<div id='Header' >
<img src='./img/EnteteVotation_BetaTest.png' style='width:100%; max-width:600px;' alt='Voter Oui Elire Non' >
<h1>Résultats temporaires de la votation du <?php echo $VotationDate; ?> :</h1>
</div>
<ol id='ResultsList' >
<?php $I=1; // LES RESULTATS DES PROJETS
foreach ($TabProjects as $_PROJECT)
{
	echo "<li><h3>".$_PROJECT." <span id='voterCount_".$I."' ></span></h3>\r\n";
	echo "<div class='RES' id='RES".$I."T' ></div>";
	echo "<div class='OUI' id='OUI".$I."T' ></div>";
	echo "<div class='ABS' id='ABS".$I."T' ></div>";
	echo "<div class='NON' id='NON".$I."T' ></div>";
	echo "<br>";
	echo "<div class='RES' id='RES".$I."I' ></div>";
	echo "<div class='OUI' id='OUI".$I."I' ></div>";
	echo "<div class='ABS' id='ABS".$I."I' ></div>";
	echo "<div class='NON' id='NON".$I."I' ></div>";
	echo "<br>";
	echo "<div class='RES' id='RES".$I."U' ></div>";
	echo "<div class='OUI' id='OUI".$I."U' ></div>";
	echo "<div class='ABS' id='ABS".$I."U' ></div>";
	echo "<div class='NON' id='NON".$I."U' ></div>";
	echo "<br>";
	echo "<div class='RES' id='RES".$I."F' ></div>";
	echo "<div class='OUI' id='OUI".$I."F' ></div>";
	echo "<div class='ABS' id='ABS".$I."F' ></div>";
	echo "<div class='NON' id='NON".$I."F' ></div>";
	echo "<br>";
	echo "<div class='RES' id='RES".$I."X' ></div>";
	echo "<div class='OUI X' id='OUI".$I."X' ></div>";
	echo "<div class='ABS X' id='ABS".$I."X' ></div>";
	echo "<div class='NON X' id='NON".$I."X' ></div>";
	echo "</li>\r\n";
	$I++;
} ?>
</ol>
<div class="fb-like" data-href="http://www.votation.fr" data-send="true" data-width="450" data-show-faces="true"></div>

<div id='footer' ><a href='https://www.votation.fr/votation.htm' target='_blank' >La page de vote</a> | <a href='https://www.votation.fr/index2.php' target='_blank' >La plateforme coopérative</a> | <a href='tel:0606576757' >Support téléphonique</a> | <a href='mailto:contact@votation.fr' >Support courrier</a> </div>
<script type='text/javascript' >displayResults();</script>
</body>
</html>



<?php
mysql_close($dblink);
?>

