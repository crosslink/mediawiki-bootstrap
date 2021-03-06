<?php

//Extension supports replacing "red links" to Wikipedia for wikification in other projects.
if ( ! defined( 'MEDIAWIKI' ) )	die();

$wgRedLinkExclusion = array('Category:All_articles_with_dead_external_links');
$wgRedLinkExclusionKeywords = array();

$replaceRedLinkInstance = null;
$redlinkPatern = "#href=[\"|']([^\&]+)(\&amp\;action\=edit\&amp\;redlink\=1)#";
$wgExternalWikiHost = 'http://en.wikipedia.org';

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
	
	$url = $matches[1];
	
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

// 	if (in_array(strtolower($tt), $wgRedLinkExclusion)){
// 		//there is exclusion
// 		//do not modify anything
// 		return $matches[0];
// 	}
	
	// 		return '"http://'.$this->lang.'.wikipedia.org/wiki/'.$s.'" title="'.$tt.' (Wikipedia)"';
	return " class=\"external text\" href=\"" . $wgExternalWikiHost . "/wiki/$tt";
// 	return "<a ref=\"nofollow\" class=\"external text\" href=\"" . $wgExternalWikiHost . "/wiki/$tt\">";
}

class ReplaceRedLinks {
	var $switchOff = false;
	
	var $lang = "en";
	var $exclusions=array();
	

	function ReplaceRedLinks() { //Constructor
		// 		if (strpos($currentPageTitle, "Special:")		
		if (RequestContext::getMain()->getTitle()->getNamespace() === 0) {

			$this->setHooks();
		}
	}

	function setHooks() {
		global $wgParser, $wgHooks;

		//Hook for tag html tag
		//see http://www.mediawiki.org/wiki/Manual:Tag_extensions for details
// 		$wgParser->setHook( 'p' , array( &$this, 'redlinkReplaceTrigger' ) );
// 		$parser->setHook( 'mw' , 'ReplaceRedLinks::redlinkReplaceTrigger');
		//function redlinkReplaceTrigger) - is below

		//Hook to ParserBeforeTidy event - "Used to process the nearly-rendered html code for the page (but before any html tidying occurs)"
		//see also http://www.mediawiki.org/wiki/Manual:Hooks/ParserBeforeTidy
		//http://www.mediawiki.org/wiki/Manual:Hooks
// 		$wgHooks['ParserBeforeTidy'][] = array( &$this, 'onParserBeforeTidy' );
// 		$wgHooks['ParserBeforeStrip'][] = array( &$this, 'onParserBeforeStrip' );
// 		$wgHooks['OutputPageBeforeHTML'][] = array( &$this, 'onOutputPageBeforeHTML');
		$wgHooks['LinkBegin'][] = array( &$this, 'onLinkBegin');
// 		$wgHooks['ParserBeforeTidy'][] = 'ReplaceRedLinks::onParserBeforeTidy';
// 		$wgHooks['ParserAfterTidy'][] = 'ReplaceRedLinks::onParserAfterTidy';
// 		$wgHooks['ParserSectionCreate'][] = 'ReplaceRedLinks::onParserSectionCreate';
		//function fnParserBeforeTidy() - is below
	}

	//
	public function redlinkReplaceTrigger( $input, array $args, Parser $parser, PPFrame $frame ){
		//tag <ReplaceRedLinks/> found
		$this->$switchOff = false;

// 		//parameter "lang"
// 		@$s=$argv['lang'];
// 		if($s){
// 			if (preg_match("/[a-z][a-z]/i", $s)) {
// 				$this->lang = $s;
// 			}else{
// 				//unproper language - needed 2 letters - ru, en etc.
// 			}
// 		}

// 		//parameter "exclusions"
// 		@$s=$argv['exclusions'];
// 		$arr=array();
// 		if($s){
// 			$arr = explode("|", $s);
// 		}
// 		foreach($arr as $el){
// 			$this->exclusions[]=trim(strtolower($el));
// 		}
		return htmlspecialchars( $input );
// 		return $parser->recursiveTagParse($input);
	}
	
	public function replaceAllRedLinks(&$text) {
		if($this->switchOff === true){
			return true;
		}
		
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
		// 				/* 'this->ParseRedLinkCallback', */
		// 				array( __CLASS__, 'ParseRedLinkCallback' ),
		// 				$text);
		$beforeText = $text;
		
		$text = preg_replace_callback($redlinkPatern,
				'redlinkHandler',
				$text);
		
		if (strlen($beforeText) != strlen($text)) {
			wfDebug("Before: \n" . $text);
			wfDebug("After: \n" . $text);
		}

// 		this->$switchOff = true; //to prevent drawing it in footer
		return true;
	}
	
	public function onParserSectionCreate( $parser, $section, &$sectionContent, $showEditLinks ) {
		global $redlinkPatern;
		$sectionContent = preg_replace_callback($redlinkPatern,
				'redlinkHandler',
				$sectionContent);
	}
	
	public function onParserAfterTidy(&$parser, &$text) {
		return $this->replaceAllRedLinks($text);
	}
	
	public function onParserBeforeTidy(&$parser, &$text) {
		return $this->replaceAllRedLinks($text);
	}
	
	public function onParserBeforeStrip( &$parser, &$text, &$strip_state ) {
		return $this->replaceAllRedLinks($text);
	}
	
	public function onOutputPageBeforeHTML(OutputPage &$out, &$text ) {
		$this->replaceAllRedLinks($text);
		return $out;
	}
	
	public function onLinkBegin( $dummy, $target, &$html, &$customAttribs, &$query,
			&$options, &$ret ) {
		if (!$target->isKnown()) {
			global $wgMetaNamespace, $wgExternalWikiHost, $wgRedLinkExclusionKeywords;
			$dbKey = $target->getPrefixedDBkey();
			
			if (strlen($dbKey) > 0) {
				$title = str_replace( '_', ' ', $dbKey ); //$target->getDBKey();
				
				$unRedIt = true;
				
				foreach ($wgRedLinkExclusionKeywords as $value) {
					
					if (stripos($title, $value) !== false) {
						$unRedIt = false;
						break;
					}
				}
					
				if ( $unRedIt === true
						&& strpos($title, $wgMetaNamespace) === false
						 && $title[0] !== '#') {
					$ret = Html::rawElement ( 'a', 
							array ( 'href' => $wgExternalWikiHost . '/wiki/' . $title, 
									'class' => 'external text',
									'rel' => 'nofollow',
									'title' => $title . ' (Wikipedia)'
									),
							$title );
					return false;
				}			
				
			}
		}
		return true;
	}

	function fnParserBeforeTidy(&$parser, &$text){
		global $IP;
		if($this->$switchOff == true){
			return true;
		}

		self::onParserBeforeTidy($parser, $text);
		
		$this->$switchOff = true; //to prevent drawing it in footer
		return true;
	}
}

function wfReplaceRedLinks() {
		$replaceRedLinkInstance = new ReplaceRedLinks();
// 		$wgHooks['ParserBeforeTidy'][] = array( &$replaceRedLinkInstance, 'onParserBeforeTidy' );
	return true;
}