<?php


/**
	twitterURLMatch v1.0 by Stephen Beckett, stevebeckett.com

	--- LICENSE ---
	
	Copyright 2013 Stephen Beckett (http://www.stevebeckett.com, steve@stevebeckett.com)
	
	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at
	
		http://www.apache.org/licenses/LICENSE-2.0
	
	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.
	
	--- ABOUT ---
	
	A stripped down PHP implementation of the URL matching features in 
	the Twitter Javascript library. Useful if you want to post arbitrary
	length URLs to Twitter's API that could get shortened to t.co URLs.
	
	Features:
	* Find URLs in strings exactly like Twitter, yay!
	* Fully UTF-8 compatible! Brilliant!
	
	Based on May 07th 2013 version of twitter-text:
	https://github.com/twitter/twitter-text-js/blob/8fc58b631d6f56ca1817d4aedb60d214f2df770b/twitter-text.js
	
	PHP v5.4+ requried for anonymous functions (with $this calls) in regexSupplant() and stringSupplant().
	If that's a problem you could rewrite the functions to use regular callbacks:
	http://php.net/manual/en/language.types.callable.php
	
	--- USAGE ---
	
	Just call:
	match($query, $withIndicies = true);
	
*/

class twitterURLMatch {
	//The various character lists
	protected $UNICODE_SPACES, $INVALID_CHARS, $latinAccentChars, $regexen;

	/**
	 *	construct twitterURLMatcher object
	 */
	public function __construct() {
		mb_internal_encoding('UTF-8'); 
	
		$this->setupCharClasses();
	}
	
	/**
	 * Find all URLs in $query string
	 * Enabling $withIndicies will include the start position of each URL part,
	 * note that this will change the format of the 'results' array (test it with print_r and see)
	 *
	 * @returns an associative array with the found URLs listed under the 'results' key
	 */
	public function match($query, $withIndicies = true) {
		$results = new stdClass();
		$results->matchCount = preg_match_all('/'.$this->regexen->extractUrl.'/i', $query, $matches, (($withIndicies) ? PREG_OFFSET_CAPTURE : NULL));
		
		$results->hasIndicies = $withIndicies;
		$results->query = $query;
		$results->results = $matches;
		
		//print_r($results);
		
		return $results;
	}
	
	/**
	 * Print out the extractUrl regex and stop at the specified problem point ($charnum)
	 * For debugging regex problems
	 */
	public function debug_char($charnum) {
		for ($i = 0; $i < mb_strlen($this->regexen->extractUrl); $i++) {
			echo ($this->regexen->extractUrl[$i]);
			if ($i == $charnum) break;
		}
	}
	
	
	/**
	 * Create all the various character lists
	 */
	protected function setupCharClasses() {
		$this->regexen = new stdClass(); 
	
		$this->UNICODE_SPACES = array (
			$this->fromCode(0x0020), // White_Space # Zs       SPACE
		    $this->fromCode(0x0085), // White_Space # Cc       <control-0085>
		    $this->fromCode(0x00A0), // White_Space # Zs       NO-BREAK SPACE
		    $this->fromCode(0x1680), // White_Space # Zs       OGHAM SPACE MARK
		    $this->fromCode(0x180E), // White_Space # Zs       MONGOLIAN VOWEL SEPARATOR
		    $this->fromCode(0x2028), // White_Space # Zl       LINE SEPARATOR
		    $this->fromCode(0x2029), // White_Space # Zp       PARAGRAPH SEPARATOR
		    $this->fromCode(0x202F), // White_Space # Zs       NARROW NO-BREAK SPACE
		    $this->fromCode(0x205F), // White_Space # Zs       MEDIUM MATHEMATICAL SPACE
		    $this->fromCode(0x3000)  // White_Space # Zs       IDEOGRAPHIC SPACE
	    );
	    $this->addCharsToCharClass($this->UNICODE_SPACES, 0x009, 0x00D); // White_Space # Cc   [5] <control-0009>..<control-000D>
	    $this->addCharsToCharClass($this->UNICODE_SPACES, 0x2000, 0x200A); // White_Space # Zs  [11] EN QUAD..HAIR SPACE
	    
	    $this->regexen->spaces_group = $this->regexSupplant(implode('', $this->UNICODE_SPACES));
	    
		$this->INVALID_CHARS = array(
			$this->fromCode(0xFFFE),
		    $this->fromCode(0xFEFF), // BOM
		    $this->fromCode(0xFFFF) // Special
		);
		
		$this->regexen->punct = "\!'#%&'\(\)*\+\\,\-\.\/:;<=>\?@\[\]\^_{|}~\$"; //Changed
		
		$this->addCharsToCharClass($this->INVALID_CHARS, 0x202A, 0x202E); // Directional change
		
		$this->regexen->invalid_chars_group = $this->regexSupplant(implode('', $this->INVALID_CHARS));
		
		
		// Latin accented characters (subtracted 0xD7 from the range, it's a confusable multiplication sign. Looks like "x")
		$this->addCharsToCharClass($this->latinAccentChars, 0x00c0, 0x00d6);
		$this->addCharsToCharClass($this->latinAccentChars, 0x00d8, 0x00f6);
		$this->addCharsToCharClass($this->latinAccentChars, 0x00f8, 0x00ff);
		  // Latin Extended A and B
		$this->addCharsToCharClass($this->latinAccentChars, 0x0100, 0x024f);
		  // assorted IPA Extensions
		$this->addCharsToCharClass($this->latinAccentChars, 0x0253, 0x0254);
		$this->addCharsToCharClass($this->latinAccentChars, 0x0256, 0x0257);
		$this->addCharsToCharClass($this->latinAccentChars, 0x0259, 0x0259);
		$this->addCharsToCharClass($this->latinAccentChars, 0x025b, 0x025b);
		$this->addCharsToCharClass($this->latinAccentChars, 0x0263, 0x0263);
		$this->addCharsToCharClass($this->latinAccentChars, 0x0268, 0x0268);
		$this->addCharsToCharClass($this->latinAccentChars, 0x026f, 0x026f);
		$this->addCharsToCharClass($this->latinAccentChars, 0x0272, 0x0272);
		$this->addCharsToCharClass($this->latinAccentChars, 0x0289, 0x0289);
		$this->addCharsToCharClass($this->latinAccentChars, 0x028b, 0x028b);
		  // Okina for Hawaiian (it *is* a letter character)
		$this->addCharsToCharClass($this->latinAccentChars, 0x02bb, 0x02bb);
		  // Combining diacritics
		$this->addCharsToCharClass($this->latinAccentChars, 0x0300, 0x036f);
		  // Latin Extended Additional
		$this->addCharsToCharClass($this->latinAccentChars, 0x1e00, 0x1eff);
		$this->regexen->latinAccentChars = $this->regexSupplant(implode('', $this->latinAccentChars));
		
		
		// URL related regex collection
		$this->regexen->validUrlPrecedingChars = $this->regexSupplant('(?:[^A-Za-z0-9@＠$#＃#{invalid_chars_group}]|^)');
		$this->regexen->invalidUrlWithoutProtocolPrecedingChars = '/[-_.\/]$/';
		$this->regexen->invalidDomainChars = $this->stringSupplant("#{punct}#{spaces_group}#{invalid_chars_group}", $this->regexen);
		$this->regexen->validDomainChars = $this->regexSupplant('[^#{invalidDomainChars}]');
		$this->regexen->validSubdomain = $this->regexSupplant('(?:(?:#{validDomainChars}(?:[_-]|#{validDomainChars})*)?#{validDomainChars}\.)');
		$this->regexen->validDomainName = $this->regexSupplant('(?:(?:#{validDomainChars}(?:-|#{validDomainChars})*)?#{validDomainChars}\.)');
		$this->regexen->validGTLD = $this->regexSupplant('(?:(?:aero|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|xxx)(?=[^0-9a-zA-Z]|$))');
		//Used to be Regex'd
		$this->regexen->validCCTLD = $this->regexSupplant(
		    "(?:(?:ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|".
		    "ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|".
		    "ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|".
		    "ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|".
		    "na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|".
		    "sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|".
		    "ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|za|zm|zw)(?=[^0-9a-zA-Z]|$))");
		$this->regexen->validPunycode = $this->regexSupplant('(?:xn--[0-9a-z]+)');
		$this->regexen->validDomain = $this->regexSupplant('(?:#{validSubdomain}*#{validDomainName}(?:#{validGTLD}|#{validCCTLD}|#{validPunycode}))');
		$this->regexen->validAsciiDomain = $this->regexSupplant('(?:(?:[\-a-z0-9#{latinAccentChars}]+)\.)+(?:#{validGTLD}|#{validCCTLD}|#{validPunycode})', 'gi');
		$this->regexen->invalidShortDomain = $this->regexSupplant('^#{validDomainName}#{validCCTLD}$');
		
		$this->regexen->validPortNumber = $this->regexSupplant('[0-9]+');
		
		$this->regexen->validGeneralUrlPathChars = $this->regexSupplant('[a-z0-9!\*\';:=\+,\.\$\/%#\[\]\-_~@|&#{latinAccentChars}]', 'i');
		// Allow URL paths to contain balanced parens
		//  1. Used in Wikipedia URLs like /Primer_(film)
		//  2. Used in IIS sessions like /S(dfd346)/
		$this->regexen->validUrlBalancedParens = $this->regexSupplant('\(#{validGeneralUrlPathChars}+\)', 'i');
		// Valid end-of-path chracters (so /foo. does not gobble the period).
		// 1. Allow =&# for empty URL parameters and other URL-join artifacts
		$this->regexen->validUrlPathEndingChars = $this->regexSupplant('[\+\-a-z0-9=_#\/#{latinAccentChars}]|(?:#{validUrlBalancedParens})', 'i');
		// Allow @ in a url, but only in the middle. Catch things like http://example.com/@user/
		$this->regexen->validUrlPath = $this->regexSupplant('(?:'.
		'(?:'.
		  '#{validGeneralUrlPathChars}*'.
		    '(?:#{validUrlBalancedParens}#{validGeneralUrlPathChars}*)*'.
		    '#{validUrlPathEndingChars}'.
		  ')|(?:@#{validGeneralUrlPathChars}+\/)'.
		')', 'i'); //Changed
		
		$this->regexen->validUrlQueryChars = '[a-z0-9!?\*\'@\(\);:&=\+\$\/%#\[\]\-_\.,~|]'; //i
		$this->regexen->validUrlQueryEndingChars = '[a-z0-9_&=#\/]'; //i
		$this->regexen->extractUrl = $this->regexSupplant(
		'('                                                            . // $1 total match
		  '(#{validUrlPrecedingChars})'                                . // $2 Preceeding character
		  '('                                                          . // $3 URL
		    '(https?:\\/\\/)?'                                         . // $4 Protocol (optional)
		    '(#{validDomain})'                                         . // $5 Domain(s)
		    '(?::(#{validPortNumber}))?'                               . // $6 Port number (optional)
		    '(\\/#{validUrlPath}*)?'                                   . // $7 URL Path
		    '(\\?#{validUrlQueryChars}*#{validUrlQueryEndingChars})?'  . // $8 Query String
		  ')'                                                          .
		')',
		'gi'); //Changed

	}
	
	/**
	 * Convert a Unicode hex code point in to a character
	 *
	 * @returns UTF-8 encoded character
	 */
	protected function fromCode ($code) {	
		return mb_convert_encoding('&#x'.str_pad(dechex($code), 4, '0', STR_PAD_LEFT).';', 'UTF-8', 'HTML-ENTITIES');
	}
	
	/**
	 * Add a single Unicode code point, or a range, to a specified character list
	 */
	protected function addCharsToCharClass(&$charClass, $start, $end) {
	
		$s = $this->fromCode($start);
		if ($end !== $start) {
			$s = $s.'-'.$this->fromCode($end); //. $this->fromCode($end);
		}
		$charClass[] = $s;
	}
	
	/**
	 * Replace all placeholders with their respective regex strings
	 * Placeholders are in the format #{placeholderName}
	 * 
	 * @returns A regex string with regex instead of placeholders
	 */
	protected function regexSupplant($regex, $flags = '') {
		//Flags are ignored in this version
		
		return preg_replace_callback(
				'/#\{(\w+)\}/', 
				function($matches) {
					if (isset($this->regexen->$matches[1])) {
						//echo('Found: '.$matches[1].'<br>');
						return $this->regexen->$matches[1];
					} else {
						echo('regexSupplant(): Not found: '.$matches[1].'<br>');
						return '';
					}
				},
				$regex);
	}
	
	/**
	 * Replace all placeholders with their respective strings from the given array
	 * Placeholders are in the format #{placeholderName}
	 * 
	 * @returns A regex string with regex instead of placeholders
	 */
	protected function stringSupplant($str, &$values) {
		return preg_replace_callback(
				'/#\{(\w+)\}/', 
				function($matches) use (&$values) {
					if (isset($values->$matches[1])) {
						//echo('Found: '.$matches[1].'<br>');
						return $values->$matches[1];
					} else {
						echo('stringSupplant(): Not found: '.$matches[1].'<br>');
						return '';
					}
				},
				$str);	
	}

}

?>