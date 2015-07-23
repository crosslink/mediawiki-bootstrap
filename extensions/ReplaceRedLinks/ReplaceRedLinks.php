<?php

//Extension supports replacing "red links" to Wikipedia for wikification in other projects.
if ( ! defined( 'MEDIAWIKI' ) )	die();

$wgRedLinkExclusion = array('Category:All_articles_with_dead_external_links');

$replaceRedLinkInstance = null;
$redlinkPatern = "/<a\s+[^<>]*href=[\"|']([^\&]+)(\&amp\;action\=edit\&amp\;redlink\=1)[^<>]*>/i";
$wgExternalWikiHost = 'https://en.wikipedia.org';

if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'wfReplaceRedLinks';
} else {
	$wgExtensionFunctions[] = 'wfReplaceRedLinks';
}

// Extension credits that will show up on the page [[Special:Version]]
$wgExtensionCredits['parserhook'][] = array(
		'path'         => __FILE__,
		'name'         => 'ReplaceRedLinks',
		'version'      => '1.1',
		'author'       => 'X-romix',
		'url'          => 'https://www.mediawiki.org/wiki/Extension:ReplaceRedLinks',
		'description'  => 'Allows replacing "red links" to Wikipedia for wikification in other projects'
);

function redlinkHandler($matches) {
	global $wgExternalWikiHost, $wgRedLinkExclusion;
	
	$url=$matches[1];
	
	$pos = 0;
	
	if (($pos = stripos($url, '//')) !== false) {
		$pos += 2;
	}
	
	if ($pos < strlen($url) && ($pos = stripos($url, '/')) !== false) {
		$path = substr($url, $pos);
	}
	else {
		$path = '/';
	} 
	
	if (($pos = stripos($path, "title=")) !== false) {
		$s = substr($path, $pos + 6);
	}
	else {
		$pos = strrchr($path, '/');
		
		if ($pos === false)
			$s = $path;
		else 
			$s = substr($path, $pos + 1);
	}
	
	$tt=trim(urldecode($s));
	$tt=str_replace("_", " ", $tt);

	if (in_array(strtolower($tt), $wgRedLinkExclusion)){
		//there is exclusion
		//do not modify anything
		return $matches[0];
	}
	
	// 		return '"http://'.$this->lang.'.wikipedia.org/wiki/'.$s.'" title="'.$tt.' (Wikipedia)"';
	return '<a rel="nofollow" class="external text" href="'. $wgExternalWikiHost . "/wiki/$tt\">";
}

class ReplaceRedLinks {
	var $SwitchOff = true;
	var $lang = "en";
	var $exclusions=array();

	function ReplaceRedLinks() { //Constructor
		$this->setHooks();
	}

	function setHooks() {
		global $wgParser, $wgHooks;

		//Hook for tag html tag
		//see http://www.mediawiki.org/wiki/Manual:Tag_extensions for details
// 		$wgParser->setHook( 'Re' , array( &$this, 'fnReplaceRedLinks' ) );
		//function fnReplaceRedLinks) - is below

		//Hook to ParserBeforeTidy event - "Used to process the nearly-rendered html code for the page (but before any html tidying occurs)"
		//see also http://www.mediawiki.org/wiki/Manual:Hooks/ParserBeforeTidy
		//http://www.mediawiki.org/wiki/Manual:Hooks
// 		$wgHooks['ParserBeforeTidy'][] = array( &$this, 'fnParserBeforeTidy' );
		$wgHooks['ParserBeforeTidy'][] = 'ReplaceRedLinks::onParserBeforeTidy';
		//function fnParserBeforeTidy() - is below
	}

	//
	function fnReplaceRedLinks( $str, $argv, $parser ){
		//tag <ReplaceRedLinks/> found
		$this->SwitchOff = false;

		//parameter "lang"
		@$s=$argv['lang'];
		if($s){
			if (preg_match("/[a-z][a-z]/i", $s)) {
				$this->lang = $s;
			}else{
				//unproper language - needed 2 letters - ru, en etc.
			}
		}

		//parameter "exclusions"
		@$s=$argv['exclusions'];
		$arr=array();
		if($s){
			$arr = explode("|", $s);
		}
		foreach($arr as $el){
			$this->exclusions[]=trim(strtolower($el));
		}

		return $parser->recursiveTagParse($str);
	}
	
	public static function onParserBeforeTidy(&$parser, &$text) {
		global $redlinkPatern;
		//process links
// 		/
// 		(\"\/index\.php\?title\=)	# start of link
// 		([^\&]+) 					# any text before &
// 		(\&amp\;action\=edit)		# action=edit
// 		(\&amp\;redlink\=1\")		# redlink=1
// 			(\sclass\=\"new\")			# class=new
// 			(\stitle\=\"[^\"]*\")		# title=
// 			/x",
// 		$redlinkPatern = "/(<a\s+[^<>]*href=\"|').*?redlink\=1.*?([^\"'#]+)([^<>]*<\/a>)/i";
// 		$redlinkPatern = "/<a\s+[^<>]*href=[\"|']([^\&]+)(\&amp\;action\=edit\&amp\;redlink\=1).*?<\/a>/i";
// 		$text=preg_replace_callback($redlinkPatern,
// 				/* 'ReplaceRedLinks::ParseRedLinkCallback', */
// 				array( __CLASS__, 'ParseRedLinkCallback' ),
// 				$text);
		
		$text = preg_replace_callback($redlinkPatern,
				'redlinkHandler',
				$text);
	}

	function fnParserBeforeTidy(&$parser, &$text){
		global $IP;
		if($this->SwitchOff == true){
			return true;
		}

		self::onParserBeforeTidy($parser, $text);
		
		$this->SwitchOff = true; //to prevent drawing it in footer
		return true;
	}


	function ParseRedLinkCallback($matches){
		return "";
// 		$s=$matches[2];
// 		$tt=trim(urldecode($s));
// 		$tt=str_replace("_", " ", $tt);

// // 		if (in_array(strtolower($tt), $this->exclusions)){
// // 			//there is exclusion
// // 			//do not modify anything
// // 			return $matches[0];
// // 		}

// 		return '"http://'.$this->lang.'.wikipedia.org/wiki/'.$s.'" title="'.$tt.' (Wikipedia)"';
	}
}

function wfReplaceRedLinks() {
// 	if ($replaceRedLinkInstance === null)
		$replaceRedLinkInstance = new ReplaceRedLinks;
// 		apc_clear_cache();
	return true;
}