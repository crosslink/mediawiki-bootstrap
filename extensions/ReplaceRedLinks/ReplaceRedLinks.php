<?php

//Extension supports replacing "red links" to Wikipedia for wikification in other projects.
if ( ! defined( 'MEDIAWIKI' ) )	die();
 
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
 
class ReplaceRedLinks{
	var $SwitchOff = false;
	var $lang = "en";
	var $exclusions=array();
 
	function ReplaceRedLinks() { //Constructor
		$this->setHooks();
	}
 
	function setHooks() {
		global $wgParser, $wgHooks;
 
		//Hook for tag <ReplaceRedLinks> 
		//see http://www.mediawiki.org/wiki/Manual:Tag_extensions for details
// 		$wgParser->setHook( 'ReplaceRedLinks' , array( &$this, 'fnReplaceRedLinks' ) );
		//function fnReplaceRedLinks) - is below
 
		//Hook to ParserBeforeTidy event - "Used to process the nearly-rendered html code for the page (but before any html tidying occurs)"
		//see also http://www.mediawiki.org/wiki/Manual:Hooks/ParserBeforeTidy
		//http://www.mediawiki.org/wiki/Manual:Hooks
		$wgHooks['ParserBeforeTidy'][] = array( &$this, 'fnParserBeforeTidy' );
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
 
	public function fnParserBeforeTidy(&$parser, &$text){
		global $IP;
		if($this->SwitchOff == true){
			return true;
		}

		//process links
		// 			(\sclass\=\"new\")			# class=new
// 			(\stitle\=\"[^\"]*\")		# title=
		$text=preg_replace_callback('/(\"\/w\/index\.php\?title\=)([^\&]+)(\&amp\;action\=edit\&amp\;redlink\=1\")/x', 
			array( __CLASS__, 'ParseRedLinkCallback' ), 
			$text);
	
// 		$this->SwitchOff = true; //to prevent drawing it in footer
		return true;
	}
 
 
	public static function ParseRedLinkCallback($matches){
		$s=$matches[2];
		$tt=trim(urldecode($s));
		$tt=str_replace("_", " ", $tt); 
		
		if (in_array(strtolower($tt), $this->exclusions)){
			//there is exclusion
			//do not modify anything
			return $matches[0];
		}

		return '"http://'. 'en' .'.wikipedia.org/wiki/'.$s.'" class="external text" title="'.$tt.' (Wikipedia)"';
	}
}
 
function wfReplaceRedLinks() {
	new ReplaceRedLinks;
	return true;
}