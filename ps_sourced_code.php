<?php
/*
* This file contains fragments of code that were borrowed from Prestashop. 
* For copyright reasons they are kept separate from the rest of Prestools Suite.
* The main reason for copying is to keep functionality the same.
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with Prestashop in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
*/

/* the following section was derived from Prestashop's sanitize() function in classes\Search.php */
/* Copied from Drupal search module, except for \x{0}-\x{2f} that has been replaced by \x{0}-\x{2c}\x{2e}-\x{2f} in order to keep the char '-' */
define('PREG_CLASS_SEARCH_EXCLUDE',
'\x{0}-\x{2c}\x{2e}-\x{2f}\x{3a}-\x{40}\x{5b}-\x{60}\x{7b}-\x{bf}\x{d7}\x{f7}\x{2b0}-'.
'\x{385}\x{387}\x{3f6}\x{482}-\x{489}\x{559}-\x{55f}\x{589}-\x{5c7}\x{5f3}-'.
'\x{61f}\x{640}\x{64b}-\x{65e}\x{66a}-\x{66d}\x{670}\x{6d4}\x{6d6}-\x{6ed}'.
'\x{6fd}\x{6fe}\x{700}-\x{70f}\x{711}\x{730}-\x{74a}\x{7a6}-\x{7b0}\x{901}-'.
'\x{903}\x{93c}\x{93e}-\x{94d}\x{951}-\x{954}\x{962}-\x{965}\x{970}\x{981}-'.
'\x{983}\x{9bc}\x{9be}-\x{9cd}\x{9d7}\x{9e2}\x{9e3}\x{9f2}-\x{a03}\x{a3c}-'.
'\x{a4d}\x{a70}\x{a71}\x{a81}-\x{a83}\x{abc}\x{abe}-\x{acd}\x{ae2}\x{ae3}'.
'\x{af1}-\x{b03}\x{b3c}\x{b3e}-\x{b57}\x{b70}\x{b82}\x{bbe}-\x{bd7}\x{bf0}-'.
'\x{c03}\x{c3e}-\x{c56}\x{c82}\x{c83}\x{cbc}\x{cbe}-\x{cd6}\x{d02}\x{d03}'.
'\x{d3e}-\x{d57}\x{d82}\x{d83}\x{dca}-\x{df4}\x{e31}\x{e34}-\x{e3f}\x{e46}-'.
'\x{e4f}\x{e5a}\x{e5b}\x{eb1}\x{eb4}-\x{ebc}\x{ec6}-\x{ecd}\x{f01}-\x{f1f}'.
'\x{f2a}-\x{f3f}\x{f71}-\x{f87}\x{f90}-\x{fd1}\x{102c}-\x{1039}\x{104a}-'.
'\x{104f}\x{1056}-\x{1059}\x{10fb}\x{10fc}\x{135f}-\x{137c}\x{1390}-\x{1399}'.
'\x{166d}\x{166e}\x{1680}\x{169b}\x{169c}\x{16eb}-\x{16f0}\x{1712}-\x{1714}'.
'\x{1732}-\x{1736}\x{1752}\x{1753}\x{1772}\x{1773}\x{17b4}-\x{17db}\x{17dd}'.
'\x{17f0}-\x{180e}\x{1843}\x{18a9}\x{1920}-\x{1945}\x{19b0}-\x{19c0}\x{19c8}'.
'\x{19c9}\x{19de}-\x{19ff}\x{1a17}-\x{1a1f}\x{1d2c}-\x{1d61}\x{1d78}\x{1d9b}-'.
'\x{1dc3}\x{1fbd}\x{1fbf}-\x{1fc1}\x{1fcd}-\x{1fcf}\x{1fdd}-\x{1fdf}\x{1fed}-'.
'\x{1fef}\x{1ffd}-\x{2070}\x{2074}-\x{207e}\x{2080}-\x{2101}\x{2103}-\x{2106}'.
'\x{2108}\x{2109}\x{2114}\x{2116}-\x{2118}\x{211e}-\x{2123}\x{2125}\x{2127}'.
'\x{2129}\x{212e}\x{2132}\x{213a}\x{213b}\x{2140}-\x{2144}\x{214a}-\x{2b13}'.
'\x{2ce5}-\x{2cff}\x{2d6f}\x{2e00}-\x{3005}\x{3007}-\x{303b}\x{303d}-\x{303f}'.
'\x{3099}-\x{309e}\x{30a0}\x{30fb}\x{30fd}\x{30fe}\x{3190}-\x{319f}\x{31c0}-'.
'\x{31cf}\x{3200}-\x{33ff}\x{4dc0}-\x{4dff}\x{a015}\x{a490}-\x{a716}\x{a802}'.
'\x{e000}-\x{f8ff}\x{fb29}\x{fd3e}-\x{fd3f}\x{fdfc}-\x{fdfd}'.
'\x{fd3f}\x{fdfc}-\x{fe6b}\x{feff}-\x{ff0f}\x{ff1a}-\x{ff20}\x{ff3b}-\x{ff40}'.
'\x{ff5b}-\x{ff65}\x{ff70}\x{ff9e}\x{ff9f}\x{ffe0}-\x{fffd}');

define('PREG_CLASS_NUMBERS',
'\x{30}-\x{39}\x{b2}\x{b3}\x{b9}\x{bc}-\x{be}\x{660}-\x{669}\x{6f0}-\x{6f9}'.
'\x{966}-\x{96f}\x{9e6}-\x{9ef}\x{9f4}-\x{9f9}\x{a66}-\x{a6f}\x{ae6}-\x{aef}'.
'\x{b66}-\x{b6f}\x{be7}-\x{bf2}\x{c66}-\x{c6f}\x{ce6}-\x{cef}\x{d66}-\x{d6f}'.
'\x{e50}-\x{e59}\x{ed0}-\x{ed9}\x{f20}-\x{f33}\x{1040}-\x{1049}\x{1369}-'.
'\x{137c}\x{16ee}-\x{16f0}\x{17e0}-\x{17e9}\x{17f0}-\x{17f9}\x{1810}-\x{1819}'.
'\x{1946}-\x{194f}\x{2070}\x{2074}-\x{2079}\x{2080}-\x{2089}\x{2153}-\x{2183}'.
'\x{2460}-\x{249b}\x{24ea}-\x{24ff}\x{2776}-\x{2793}\x{3007}\x{3021}-\x{3029}'.
'\x{3038}-\x{303a}\x{3192}-\x{3195}\x{3220}-\x{3229}\x{3251}-\x{325f}\x{3280}-'.
'\x{3289}\x{32b1}-\x{32bf}\x{ff10}-\x{ff19}');

define('PREG_CLASS_PUNCTUATION',
'\x{21}-\x{23}\x{25}-\x{2a}\x{2c}-\x{2f}\x{3a}\x{3b}\x{3f}\x{40}\x{5b}-\x{5d}'.
'\x{5f}\x{7b}\x{7d}\x{a1}\x{ab}\x{b7}\x{bb}\x{bf}\x{37e}\x{387}\x{55a}-\x{55f}'.
'\x{589}\x{58a}\x{5be}\x{5c0}\x{5c3}\x{5f3}\x{5f4}\x{60c}\x{60d}\x{61b}\x{61f}'.
'\x{66a}-\x{66d}\x{6d4}\x{700}-\x{70d}\x{964}\x{965}\x{970}\x{df4}\x{e4f}'.
'\x{e5a}\x{e5b}\x{f04}-\x{f12}\x{f3a}-\x{f3d}\x{f85}\x{104a}-\x{104f}\x{10fb}'.
'\x{1361}-\x{1368}\x{166d}\x{166e}\x{169b}\x{169c}\x{16eb}-\x{16ed}\x{1735}'.
'\x{1736}\x{17d4}-\x{17d6}\x{17d8}-\x{17da}\x{1800}-\x{180a}\x{1944}\x{1945}'.
'\x{2010}-\x{2027}\x{2030}-\x{2043}\x{2045}-\x{2051}\x{2053}\x{2054}\x{2057}'.
'\x{207d}\x{207e}\x{208d}\x{208e}\x{2329}\x{232a}\x{23b4}-\x{23b6}\x{2768}-'.
'\x{2775}\x{27e6}-\x{27eb}\x{2983}-\x{2998}\x{29d8}-\x{29db}\x{29fc}\x{29fd}'.
'\x{3001}-\x{3003}\x{3008}-\x{3011}\x{3014}-\x{301f}\x{3030}\x{303d}\x{30a0}'.
'\x{30fb}\x{fd3e}\x{fd3f}\x{fe30}-\x{fe52}\x{fe54}-\x{fe61}\x{fe63}\x{fe68}'.
'\x{fe6a}\x{fe6b}\x{ff01}-\x{ff03}\x{ff05}-\x{ff0a}\x{ff0c}-\x{ff0f}\x{ff1a}'.
'\x{ff1b}\x{ff1f}\x{ff20}\x{ff3b}-\x{ff3d}\x{ff3f}\x{ff5b}\x{ff5d}\x{ff5f}-'.
'\x{ff65}');

/**
 * Matches all CJK characters that are candidates for auto-splitting
 * (Chinese, Japanese, Korean).
 * Contains kana and BMP ideographs.
 */
define('PREG_CLASS_CJK', '\x{3041}-\x{30ff}\x{31f0}-\x{31ff}\x{3400}-\x{4db5}\x{4e00}-\x{9fbb}\x{f900}-\x{fad9}');

/* Search::sanitize() in Prestashop */
function sanitize_index_text($string, $id_lang, $indexation = false, $iso_code = false)
{	global $conn;
	if (empty($string)) { /* under php 8.1 trim doesn't accept NULL values */
		return '';
	}
	$string = trim($string);
	if (empty($string)) {
		return '';
	}

    $string = str_replace('>', '> ', $string); /* WMR: prevent words separated by HTML being glued together */
	$string = strtolower(strip_tags($string));
	$string = html_entity_decode($string, ENT_NOQUOTES, 'utf-8');

	$string = preg_replace('/(['.PREG_CLASS_NUMBERS.']+)['.PREG_CLASS_PUNCTUATION.']+(?=['.PREG_CLASS_NUMBERS.'])/u', '\1', $string);
	$string = preg_replace('/['.PREG_CLASS_SEARCH_EXCLUDE.']+/u', ' ', $string);

	if ($indexation) {
		$string = preg_replace('/[._\-]+/', ' ', $string);
	} else {
		$words = explode(' ', $string);
		$processed_words = array();
		// search for aliases for each word of the query
		foreach ($words as $word) {
			$res = dbquery("SELECT search FROM  ". _DB_PREFIX_."alias WHERE alias='".mysqli_real_escape_string($conn, $word)."' AND active=1");
			if(mysqli_num_rows($res) > 0)
			{	$row = mysqli_fetch_assoc($res);
				$processed_words[] = $row["search"];
			} else {
				$processed_words[] = $word;
			}
		}
		$string = implode(' ', $processed_words);
		$string = preg_replace('/[._]+/', '', $string);
		$string = ltrim(preg_replace('/([^ ])\-/', '$1 ', ' '.$string));
		$string = preg_replace('/[._]+/', '', $string);
		$string = preg_replace('/[^\s]-+/', '', $string);
	}

	$blacklist = get_configuration_lang_value('PS_SEARCH_BLACKLIST', $id_lang);
	if (!empty($blacklist)) {
		$string = preg_replace('/(?<=\s)('.$blacklist.')(?=\s)/Su', '', $string);
		$string = preg_replace('/^('.$blacklist.')(?=\s)/Su', '', $string);
		$string = preg_replace('/(?<=\s)('.$blacklist.')$/Su', '', $string);
		$string = preg_replace('/^('.$blacklist.')$/Su', '', $string);
	}

	// If the language is constituted with symbol and there is no "words", then split every chars
	if (in_array($iso_code, array('zh', 'tw', 'ja')) && function_exists('mb_strlen')) {
		// Cut symbols from letters
		$symbols = '';
		$letters = '';
		foreach (explode(' ', $string) as $mb_word) {
			if (strlen(replaceAccentedChars($mb_word)) == mb_strlen(replaceAccentedChars($mb_word))) {
				$letters .= $mb_word.' ';
			} else {
				$symbols .= $mb_word.' ';
			}
		}

		if (preg_match_all('/./u', $symbols, $matches)) {
			$symbols = implode(' ', $matches[0]);
		}

		$string = $letters.$symbols;
	} elseif ($indexation) {
		$minWordLen = (int)get_configuration_value('PS_SEARCH_MINWORDLEN');
		if ($minWordLen > 1) {
			$minWordLen -= 1;
			$string = preg_replace('/(?<=\s)[^\s]{1,'.$minWordLen.'}(?=\s)/Su', ' ', $string);
			$string = preg_replace('/^[^\s]{1,'.$minWordLen.'}(?=\s)/Su', '', $string);
			$string = preg_replace('/(?<=\s)[^\s]{1,'.$minWordLen.'}$/Su', '', $string);
			$string = preg_replace('/^[^\s]{1,'.$minWordLen.'}$/Su', '', $string);
		}
	}
	$string = replaceAccentedChars(trim(preg_replace('/\s+/', ' ', $string)));
	return $string;
}

/*
	 * The following function was copied and adapted from classes\Tools.php
*/
	function str2url($str)
	{ 
		$allow_accented_chars = get_configuration_value('PS_ALLOW_ACCENTED_CHARS_URL');

		if (!is_string($str))
			return false;

		$str = trim($str);

		if (function_exists('mb_strtolower'))
			$str = mb_strtolower($str, 'utf-8');
		if (!$allow_accented_chars)
			$str = replaceAccentedChars($str);

		// Remove all non-whitelist chars.
		if ($allow_accented_chars)
			$str = preg_replace('/[^a-zA-Z0-9\s\'\:\/\[\]\-\pL]/u', '', $str);
		else
			$str = preg_replace('/[^a-zA-Z0-9\s\'\:\/\[\]\-]/','', $str);

		$str = preg_replace('/[\t \r\n\'\:\/\[\]\-]+/', ' ', $str);
		//	$str = preg_replace('/[\s\'\:\/\[\]\-]+/', ' ', $str);
		// the \s in the replace string caused errors with the Israeli script 
		$str = str_replace(array(' ', '/'), '-', $str);

		// If it was not possible to lowercase the string with mb_strtolower, we do it after the transformations.
		// This way we lose fewer special chars.
		if (!function_exists('mb_strtolower'))
			$str = strtolower($str);

		return $str;
	}

	/**
	 * The following function was copied from classes\Tools.php
	 */
	function replaceAccentedChars($str)
	{
		/* One source among others:
			http://www.tachyonsoft.com/uc0000.htm
			http://www.tachyonsoft.com/uc0001.htm
			http://www.tachyonsoft.com/uc0004.htm
		*/
		$patterns = array(

			/* Lowercase */
			/* a  */ '/[\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}\x{0101}\x{0103}\x{0105}\x{0430}\x{00C0}-\x{00C3}\x{1EA0}-\x{1EB7}]/u',
			/* b  */ '/[\x{0431}]/u',
			/* c  */ '/[\x{00E7}\x{0107}\x{0109}\x{010D}\x{0446}]/u',
			/* d  */ '/[\x{010F}\x{0111}\x{0434}\x{0110}]/u',
			/* e  */ '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}\x{0435}\x{044D}\x{00C8}-\x{00CA}\x{1EB8}-\x{1EC7}]/u',
			/* f  */ '/[\x{0444}]/u',
			/* g  */ '/[\x{011F}\x{0121}\x{0123}\x{0433}\x{0491}]/u',
			/* h  */ '/[\x{0125}\x{0127}]/u',
			/* i  */ '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}\x{0438}\x{0456}\x{00CC}\x{00CD}\x{1EC8}-\x{1ECB}\x{0128}]/u',
			/* j  */ '/[\x{0135}\x{0439}]/u',
			/* k  */ '/[\x{0137}\x{0138}\x{043A}]/u',
			/* l  */ '/[\x{013A}\x{013C}\x{013E}\x{0140}\x{0142}\x{043B}]/u',
			/* m  */ '/[\x{043C}]/u',
			/* n  */ '/[\x{00F1}\x{0144}\x{0146}\x{0148}\x{0149}\x{014B}\x{043D}]/u',
			/* o  */ '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{014D}\x{014F}\x{0151}\x{043E}\x{00D2}-\x{00D5}\x{01A0}\x{01A1}\x{1ECC}-\x{1EE3}]/u',
			/* p  */ '/[\x{043F}]/u',
			/* r  */ '/[\x{0155}\x{0157}\x{0159}\x{0440}]/u',
			/* s  */ '/[\x{015B}\x{015D}\x{015F}\x{0161}\x{0441}]/u',
			/* ss */ '/[\x{00DF}]/u',
			/* t  */ '/[\x{0163}\x{0165}\x{0167}\x{0442}]/u',
			/* u  */ '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{0169}\x{016B}\x{016D}\x{016F}\x{0171}\x{0173}\x{0443}\x{00D9}-\x{00DA}\x{0168}\x{01AF}\x{01B0}\x{1EE4}-\x{1EF1}]/u',
			/* v  */ '/[\x{0432}]/u',
			/* w  */ '/[\x{0175}]/u',
			/* y  */ '/[\x{00FF}\x{0177}\x{00FD}\x{044B}\x{1EF2}-\x{1EF9}\x{00DD}]/u',
			/* z  */ '/[\x{017A}\x{017C}\x{017E}\x{0437}]/u',
			/* ae */ '/[\x{00E6}]/u',
			/* ch */ '/[\x{0447}]/u',
			/* kh */ '/[\x{0445}]/u',
			/* oe */ '/[\x{0153}]/u',
			/* sh */ '/[\x{0448}]/u',
			/* shh*/ '/[\x{0449}]/u',
			/* ya */ '/[\x{044F}]/u',
			/* ye */ '/[\x{0454}]/u',
			/* yi */ '/[\x{0457}]/u',
			/* yo */ '/[\x{0451}]/u',
			/* yu */ '/[\x{044E}]/u',
			/* zh */ '/[\x{0436}]/u',

			/* Uppercase */
			/* A  */ '/[\x{0100}\x{0102}\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}\x{0410}]/u',
			/* B  */ '/[\x{0411}]]/u',
			/* C  */ '/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}\x{0426}]/u',
			/* D  */ '/[\x{010E}\x{0110}\x{0414}]/u',
			/* E  */ '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}\x{0415}\x{042D}]/u',
			/* F  */ '/[\x{0424}]/u',
			/* G  */ '/[\x{011C}\x{011E}\x{0120}\x{0122}\x{0413}\x{0490}]/u',
			/* H  */ '/[\x{0124}\x{0126}]/u',
			/* I  */ '/[\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}\x{0418}\x{0406}]/u',
			/* J  */ '/[\x{0134}\x{0419}]/u',
			/* K  */ '/[\x{0136}\x{041A}]/u',
			/* L  */ '/[\x{0139}\x{013B}\x{013D}\x{0139}\x{0141}\x{041B}]/u',
			/* M  */ '/[\x{041C}]/u',
			/* N  */ '/[\x{00D1}\x{0143}\x{0145}\x{0147}\x{014A}\x{041D}]/u',
			/* O  */ '/[\x{00D3}\x{014C}\x{014E}\x{0150}\x{041E}]/u',
			/* P  */ '/[\x{041F}]/u',
			/* R  */ '/[\x{0154}\x{0156}\x{0158}\x{0420}]/u',
			/* S  */ '/[\x{015A}\x{015C}\x{015E}\x{0160}\x{0421}]/u',
			/* T  */ '/[\x{0162}\x{0164}\x{0166}\x{0422}]/u',
			/* U  */ '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{0168}\x{016A}\x{016C}\x{016E}\x{0170}\x{0172}\x{0423}]/u',
			/* V  */ '/[\x{0412}]/u',
			/* W  */ '/[\x{0174}]/u',
			/* Y  */ '/[\x{0176}\x{042B}]/u',
			/* Z  */ '/[\x{0179}\x{017B}\x{017D}\x{0417}]/u',
			/* AE */ '/[\x{00C6}]/u',
			/* CH */ '/[\x{0427}]/u',
			/* KH */ '/[\x{0425}]/u',
			/* OE */ '/[\x{0152}]/u',
			/* SH */ '/[\x{0428}]/u',
			/* SHH*/ '/[\x{0429}]/u',
			/* YA */ '/[\x{042F}]/u',
			/* YE */ '/[\x{0404}]/u',
			/* YI */ '/[\x{0407}]/u',
			/* YO */ '/[\x{0401}]/u',
			/* YU */ '/[\x{042E}]/u',
			/* ZH */ '/[\x{0416}]/u');

			// ö to oe
			// å to aa
			// ä to ae

		$replacements = array(
				'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 'ss', 't', 'u', 'v', 'w', 'y', 'z', 'ae', 'ch', 'kh', 'oe', 'sh', 'shh', 'ya', 'ye', 'yi', 'yo', 'yu', 'zh',
				'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'Y', 'Z', 'AE', 'CH', 'KH', 'OE', 'SH', 'SHH', 'YA', 'YE', 'YI', 'YO', 'YU', 'ZH'
			);

		return preg_replace($patterns, $replacements, $str);
	}


/* update_tag_count() was derived from the Prestashop 1.6.1.4 function updateTagCount() in classes\tag.php */
function update_tag_count($tags_changed)
{ $tags_changed = array_unique($tags_changed);
  $query = 'DELETE FROM `'._DB_PREFIX_.'tag_count` WHERE id_tag IN ('.implode(',', $tags_changed).')';
  $res = dbquery($query);
  
  $query = 'REPLACE INTO `'._DB_PREFIX_.'tag_count` (id_group, id_tag, id_lang, id_shop, counter)
			SELECT cg.id_group, pt.id_tag, pt.id_lang, id_shop, COUNT(pt.id_tag) AS times
				FROM `'._DB_PREFIX_.'product_tag` pt
				INNER JOIN `'._DB_PREFIX_.'product_shop` product_shop
					USING (id_product)
				JOIN (SELECT DISTINCT id_group FROM `'._DB_PREFIX_.'category_group`) cg
				WHERE product_shop.`active` = 1
				AND EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
								LEFT JOIN `'._DB_PREFIX_.'category_group` cgo ON (cp.`id_category` = cgo.`id_category`)
								WHERE cgo.`id_group` = cg.id_group AND product_shop.`id_product` = cp.`id_product`)
				AND pt.id_tag IN ('.implode(',', $tags_changed).')
				GROUP BY pt.id_tag, pt.id_lang, cg.id_group, id_shop ORDER BY NULL';
  $res = dbquery($query);

  $query = 'REPLACE INTO `'._DB_PREFIX_.'tag_count` (id_group, id_tag, id_lang, id_shop, counter)
			SELECT 0, pt.id_tag, pt.id_lang, id_shop, COUNT(pt.id_tag) AS times
				FROM `'._DB_PREFIX_.'product_tag` pt
				INNER JOIN `'._DB_PREFIX_.'product_shop` product_shop
					USING (id_product)
				WHERE product_shop.`active` = 1
					AND pt.id_tag IN ('.implode(',', $tags_changed).')
				GROUP BY pt.id_tag, pt.id_lang, id_shop ORDER BY NULL';
  $res = dbquery($query);
}


/* update_shop_index() does the same thing what "Add missing products to the index" in the backoffice
 * of Prestashop (Preferences->Search->Indexes) does. Many of the changes you make with product-edit
 * (for example changing names or descriptions) can create the need to redo the indexing for the affected
 * products. As we can't count on the users doing it timely we do it here.
 * The relevant Prestashop code can be found in the function indexation() in the file classes\Search.php.
 * This code is deliberately less optimized than in Prestashop in order to make the code better readable.
 * When $langs is not 0 it is assumed that the mentioned langs are the only ones that have been changed. After the indexes for those languages are updated the shop will be set as indexed.
 * $maxtime is in seconds. For all product $productset=''; For all languages and all shops the value is 0. Reset is by default false.
 */
define('PS_SEARCH_MAX_WORD_LENGTH', 15);
function update_shop_index($maxtime, $productset='', $langs=0, $shops=0, $reset=false)
{ global $conn, $verbose, $prestools_settings;

  if(($shops == "") || (is_null($shops))) $shops = 0;
  if(($langs == "") || (is_null($langs))) $langs = 0;
  
  /* calculate the number of shops and langs that are asked. Shops x langs is the minimum number of entries that should be retrieved in one run */
  if($shops == 0)
  { $query=" select GROUP_CONCAT(id_shop) AS shops from ". _DB_PREFIX_."shop";
	$res=dbquery($query);
	list($s) = mysqli_fetch_row($res);
	$shoparray = explode(",", $s);
  }
  else
  { $shoparray = explode(",", $shops);
  }
  $shopcount = sizeof($shoparray);
  $completedprodshops = array();
  foreach($shoparray AS $i)
    $completedprodshops[$i] = [];
  if($langs == 0)
  { $query=" select count(*) AS langcount from ". _DB_PREFIX_."lang";
	$res=dbquery($query);
	list($langcount) = mysqli_fetch_row($res); 
  }
  else
  { $l = explode(",", $langs);
    $langcount = sizeof($l);
  }
  $prodsize = $langcount*$shopcount;
  
  if($maxtime > 25)
	set_time_limit($maxtime+10); /* we need some extra time to conclude a round */

  $weights = array(
    'pname' => get_configuration_value('PS_SEARCH_WEIGHT_PNAME'),
    'reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'supplier_reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_supplier_reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'), 
	'ean13' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_ean13' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'upc' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_upc' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
    'isbn' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
    'pa_isbn' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'mpn' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_mpn' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
    'description_short' => get_configuration_value('PS_SEARCH_WEIGHT_SHORTDESC'),
    'description' => get_configuration_value('PS_SEARCH_WEIGHT_DESC'),
    'cname' => get_configuration_value('PS_SEARCH_WEIGHT_CNAME'),	
	'cnames' => $prestools_settings["PS_SEARCH_WEIGHT_CATNAMES"],  /* all categories */
    'mname' => get_configuration_value('PS_SEARCH_WEIGHT_MNAME'),
    'tags' => get_configuration_value('PS_SEARCH_WEIGHT_TAG'),
    'attributes' => get_configuration_value('PS_SEARCH_WEIGHT_ATTRIBUTE'),
    'features' => get_configuration_value('PS_SEARCH_WEIGHT_FEATURE'));
	
  $p_fields = "p.id_product, pl.id_lang, pl.id_shop, l.iso_code";
  if ((int)$weights['pname'])     			 $p_fields .= ', pl.name AS pname';  
  if ((int)$weights['reference'])    		 $p_fields .= ', p.reference';
  if ((int)$weights['supplier_reference'])   $p_fields .= ', p.supplier_reference';
  if ((int)$weights['ean13'])     			 $p_fields .= ', p.ean13';
  if ((int)$weights['upc'])     			 $p_fields .= ', p.upc';
  if ((int)$weights['description_short'])    $p_fields .= ', pl.description_short';
  if ((int)$weights['description'])		     $p_fields .= ', pl.description';
  if ((int)$weights['cname'])			     $p_fields .= ', cl.name AS cname';
  if ((int)$weights['cnames'])			     $p_fields .= ', GROUP_CONCAT(" ",cl2.name) AS cnames';
  if ((int)$weights['mname'])			     $p_fields .= ', m.name AS mname';  
	
  $pa_fields = "";
  if ((int)$weights['pa_reference'])	     $pa_fields .= ', pa.reference AS pa_reference';
  if ((int)$weights['pa_supplier_reference']) $pa_fields .= ', pa.supplier_reference AS pa_supplier_reference';
  if ((int)$weights['pa_ean13'])		     $pa_fields .= ', pa.ean13 AS pa_ean13';
  if ((int)$weights['pa_upc'])			     $pa_fields .= ', pa.upc AS pa_upc';
  if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
  { if ((int)$weights['isbn'])   			 $p_fields .= ', p.isbn';
    if ((int)$weights['pa_isbn'])			 $pa_fields .= ', pa.isbn AS pa_isbn';
  }
  if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
  { if ((int)$weights['mpn'])   			 $p_fields .= ', p.mpn';
    if ((int)$weights['pa_mpn'])			 $pa_fields .= ', pa.mpn AS pa_mpn';
  }
  
  $isFeaturesActive = get_configuration_value('PS_FEATURE_FEATURE_ACTIVE');
  $isCombinationsActive = get_configuration_value('PS_COMBINATION_FEATURE_ACTIVE');  
  $count_words = 0;
  $query_array3 = array();
  $starttime = time();
  /* note that */
  $batchsize = 30 + $prodsize; /* number of records read at once */
  
  if($reset)
  { $query = 'UPDATE '._DB_PREFIX_.'product_shop SET indexed=0';
    $query .= ' WHERE 1';
	if(strlen($productset) > 0)
	  $query .= ' AND ('.rangetosql($productset, 'id_product').')';
	if(($shops != "") && ($shops != 0))
	  $query .= " AND id_shop IN (".$shops.')';
    $res=dbquery($query);
  }
  
  if($verbose == "true")
    echo "<br>Re-indexing for max ".$maxtime." seconds.";
  while(true)
  { echo "<br>Time=".date("H:i:s")." [starttime was ".date("H:i:s",$starttime)."]<br>";
    $last_product = $last_shop = 0; /* as there can be more than one language we can't update every loop */
    $query = 'SELECT '.$p_fields.'
			FROM '._DB_PREFIX_.'product p
			LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
			LEFT JOIN '._DB_PREFIX_.'product_shop ps ON p.id_product = ps.id_product
			LEFT JOIN '._DB_PREFIX_.'category_lang cl
				ON (cl.id_category = ps.id_category_default AND pl.id_lang = cl.id_lang AND cl.id_shop = ps.id_shop)				
			LEFT JOIN '._DB_PREFIX_.'category_product cp ON cp.id_product = ps.id_product';
	if($weights['cnames'] > 0)
		$query .= '	LEFT JOIN '._DB_PREFIX_.'category_lang cl2
				ON (cp.id_category = cl2.id_category AND pl.id_lang = cl2.id_lang AND cl2.id_shop = ps.id_shop)';
	$query .= ' LEFT JOIN '._DB_PREFIX_.'manufacturer m ON m.id_manufacturer = p.id_manufacturer
			LEFT JOIN '._DB_PREFIX_.'lang l ON l.id_lang = pl.id_lang
			WHERE ps.visibility IN ("both", "search")
			AND ps.`active` = 1
			AND pl.`id_shop` = ps.`id_shop`
			AND ps.indexed = 0';
			if(strlen($productset) > 0)
			  $query .= ' AND ('.rangetosql($productset, 'p.id_product').')';
			if(($shops != "") && ($shops != 0))
			  $query .= ' AND ps.id_shop IN ('.$shops.')';
			if(($langs != "") && ($langs != 0))
			  $query .= ' AND pl.id_lang IN ('.$langs.')';
			$query .= ' GROUP BY id_product,id_shop,id_lang
			ORDER BY l.active DESC, ps.id_product, ps.id_shop, pl.id_lang
			LIMIT '.$batchsize;

    $res=dbquery($query);
	$numrecs = mysqli_num_rows($res);
	if($numrecs==0) return update_unindexed_counter(0); /* quit the eternal loop */
    if($verbose == "true")
       echo "<br>The query delivered ".mysqli_num_rows($res)." rows</br>";
    $x = 0; /* our record number */

    while($product = mysqli_fetch_assoc($res))
	{ /* Prestashop assumes that we finish indexing everything. In Prestools
		however, we index only a few seconds in order to keep the user 
		experience acceptable. This means some extra code: */
	  if($verbose == "true") 
	    echo "<br><b>prod=".$product["id_product"]."-shop=".$product["id_shop"]."-lang=".$product["id_lang"]."</b>,";
	  else
	  { echo $product["id_product"]."-".$product["id_shop"]."-".$product["id_lang"].",";
	    if(!(($x-1)%16)) echo " <br>";
	  }
  	  if((($product["id_product"] != $last_product) || ($product["id_shop"] != $last_shop)))
	  { if(($last_product != 0) && ($last_shop != 0))
		  $completedprodshops[(int)$last_shop][] = (int)$last_product;
		$thistime = time();
		if(($product["id_product"] != $last_product) && (($thistime + 3 - $starttime) >= $maxtime))
		{ echo "TIMEOUT-".$thistime."==".$starttime."==".$maxtime."<br>";
		  break;
		}
		$last_product = $product["id_product"];
		$last_shop = $product["id_shop"]; 
	  }
	  $x++;

	  if ((int)$weights['tags'])
	  { $tquery = 'SELECT GROUP_CONCAT(" ",t.name) AS ptags FROM '._DB_PREFIX_.'product_tag pt
		LEFT JOIN '._DB_PREFIX_.'tag t ON (pt.id_tag = t.id_tag AND t.id_lang = '.(int)$product['id_lang'].')
		WHERE pt.id_product = '.(int)$product['id_product'].'
		GROUP BY pt.id_product';
		$tres = dbquery($tquery);
		if(mysqli_num_rows($tres) > 0)
		{ $trow = mysqli_fetch_assoc($tres);
  		  $product['tags'] = $trow['ptags'];
		}
	  }
      if (((int)$weights['attributes']) && $isCombinationsActive)
	  { $aquery = 'SELECT GROUP_CONCAT(" ",al.name) AS atnames FROM '._DB_PREFIX_.'product_attribute pa
		INNER JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
		INNER JOIN '._DB_PREFIX_.'attribute_lang al ON (pac.id_attribute = al.id_attribute AND al.id_lang = '.(int)$product['id_lang'].')
		INNER JOIN '._DB_PREFIX_.'product_attribute_shop pas ON (pa.id_product_attribute = pas.id_product_attribute AND id_shop='.(int)$product['id_shop'].')
		WHERE pa.id_product = '.(int)$product['id_product'].'
		GROUP BY pa.id_product';
		$ares = dbquery($aquery);
		if(mysqli_num_rows($ares) > 0)
		{ $arow = mysqli_fetch_assoc($ares);
		  $product['attributes'] = $arow['atnames'];
		}
      }
      if (((int)$weights['features']) && $isFeaturesActive)
	  { $fquery = 'SELECT GROUP_CONCAT(" ",fvl.value) AS fvlvalues FROM '._DB_PREFIX_.'feature_product fp
		LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fp.id_feature_value = fvl.id_feature_value AND fvl.id_lang = '.(int)$product['id_lang'].')
		WHERE fp.id_product = '.(int)$product['id_product'].'
		GROUP BY fp.id_product';
		$fres = dbquery($fquery);
		if(mysqli_num_rows($fres) > 0)
		{ $frow = mysqli_fetch_assoc($fres);
		  $product['features'] = $frow['fvlvalues'];
		}
      }
      if ((int)$weights['supplier_reference']) 
	  { $srquery = 'SELECT GROUP_CONCAT(" ",product_supplier_reference) AS srvalues FROM '._DB_PREFIX_.'product_supplier
		WHERE id_product = '.(int)$product['id_product'].'
		GROUP BY id_product';
 		$srres = dbquery($srquery);
		if(mysqli_num_rows($srres) > 0)
		{ $srrow = mysqli_fetch_assoc($srres);
		  $product['supplier_reference'] = $srrow['srvalues'];
		}		  
	  }
	  if ($pa_fields != "") 
	  { $afquery = 'SELECT id_product '.$pa_fields.'
		FROM '._DB_PREFIX_.'product_attribute pa WHERE pa.id_product = '.(int)$product['id_product'];
		$afres = dbquery($afquery);
		if(mysqli_num_rows($afres)>0)
		{ while($afrow = mysqli_fetch_assoc($afres))
		  {	$product['attributes_fields'][] = $afrow;
		  }
		}
	  }
	  // Data must be cleaned of html, bad characters, spaces and anything, then if the resulting words are long enough, they're added to the array
	  $product_array = array(); /* storage for the keywords and their weights */
	  foreach ($product as $key => $value) 
	  {	if ($key == 'attributes_fields')
	    { foreach ($value as $pa_array)
		  {	foreach ($pa_array as $pa_key => $pa_value) 
		    { fillProductArray($product_array, $weights, $pa_key, $pa_value, $product['id_lang'], $product['iso_code']);
			}
		  }
		} 
		else 
		{ fillProductArray($product_array, $weights, $key, $value, $product['id_lang'], $product['iso_code']);
		}
	  }
	  
	  // In contrast to Prestashop we are now looking what is already there and only update what changed
	  $pquery = 'SELECT si.id_word, weight, word FROM '._DB_PREFIX_.'search_index si';
	  $pquery .= ' LEFT JOIN '._DB_PREFIX_.'search_word sw ON (sw.id_word=si.id_word)';
	  $pquery .= ' WHERE si.id_product = '.(int)$product['id_product'].' AND sw.id_shop = '.(int)$product['id_shop'].' AND sw.id_lang = '.(int)$product['id_lang'];
	  $pres = dbquery($pquery);
	  
	  if((mysqli_num_rows($pres) - sizeof($product_array)) > 40)
	  { $dquery = 'DELETE FROM '._DB_PREFIX_.'search_index';
		$dquery .= ' WHERE id_product='.$product['id_product'];
		if(($shops != 0) || ($langs != 0))
		{ $dquery .= ' AND id_word IN (SELECT id_word FROM '._DB_PREFIX_.'search_word';
		  $dquery .= ' WHERE 1';
	
/*	This instead of the previous two lines seems a bit (<5%) less efficient.
		  $dquery .= ' AND id_word IN (SELECT id_word FROM '._DB_PREFIX_.'search_index t1';
		  $dquery .= ' WHERE EXISTS (SELECT id_word FROM '._DB_PREFIX_.'search_word sw';
		  $dquery .= ' WHERE sw.id_word=si.id_word'; 
*/		  
		  if($shops != 0)
			  $dquery .= " AND sw.id_shop IN (".$shops.')';
		  if($langs != 0)
			  $dquery .= " AND sw.id_lang IN (".$langs.')';
		  $dquery .= ')';
		}
		$dres = dbquery($dquery);
	  }
	  else
	  { $deleters = array();
	    while($prow = mysqli_fetch_assoc($pres))
	    { if(isset($product_array[$prow["word"]]))
		  { if($product_array[$prow["word"]] != $prow["weight"])
		    { $uquery = 'UPDATE '._DB_PREFIX_.'search_index SET weight='.$product_array[$prow["word"]];
			  $uquery .= ' WHERE id_word='.$prow["id_word"].' and id_product='.$product['id_product'];
			  $ures = dbquery($uquery);
		    }
		    unset($product_array[$prow["word"]]);		  
		  }
		  else /* not found */
		  { $deleters[] = $prow["id_word"];
		    if(sizeof($deleters) > 50)
			{ $dquery = 'DELETE FROM '._DB_PREFIX_.'search_index';
		      $dquery .= ' WHERE id_word IN ('.implode(",",$deleters).') and id_product='.$product['id_product'];
		      $dres = dbquery($dquery);
			}
		  }
		}
		if(sizeof($deleters) > 0)  /* delete remainder */
		{ $dquery = 'DELETE FROM '._DB_PREFIX_.'search_index';
		  $dquery .= ' WHERE id_word IN ('.implode(",",$deleters).') and id_product='.$product['id_product'];
		  $dres = dbquery($dquery);
		}
	  }
	  
	  // If we find words that need to be indexed, they're added to the word table in the database
	  if (is_array($product_array) && !empty($product_array)) 
	  {	$query_array = $query_array2 = array();
		foreach ($product_array as $word => $weight) 
		{ if ($weight) 
		  { $query_array[$word] = '('.(int)$product['id_lang'].', '.(int)$product['id_shop'].', \''.mysqli_real_escape_string($conn,$word).'\')';
			$query_array2[] = '\''.mysqli_real_escape_string($conn,$word).'\'';
		  }
		}

		if (is_array($query_array) && !empty($query_array)) 
		{	// The words are inserted...
			$swquery = 'INSERT IGNORE INTO '._DB_PREFIX_.'search_word (id_lang, id_shop, word)
			VALUES '.implode(',', $query_array);
			$swres = dbquery($swquery);
		}
		$word_ids_by_word = array();
		if (is_array($query_array2) && !empty($query_array2)) 
		{	// ...then their IDs are retrieved
			$added_words = '';
			$wquery = 'SELECT sw.id_word, sw.word
			FROM '._DB_PREFIX_.'search_word sw
			WHERE sw.word IN ('.implode(',', $query_array2).')
			AND sw.id_lang = '.(int)$product['id_lang'].'
			AND sw.id_shop = '.(int)$product['id_shop'];
			$wres = dbquery($wquery);	
			while($wrow = mysqli_fetch_assoc($wres))
			{ $word_ids_by_word['_'.$wrow['word']] = (int)$wrow['id_word'];
			}
		}
	  } 
	  foreach ($product_array as $word => $weight) 
	  {	if (!$weight) continue;
		if (!isset($word_ids_by_word['_'.$word])) continue;
		$id_word = $word_ids_by_word['_'.$word];
		if (!$id_word) 	continue;
		$query_array3[] = '('.(int)$product['id_product'].','.
			(int)$id_word.','.(int)$weight.')';
		// Force save every 200 words in order to avoid overloading MySQL
		if (++$count_words % 200 == 0)
		{	saveIndex($query_array3);
		}
	  }
	} /* end while fetch product */
	
	if(($numrecs != $batchsize) && ($last_product != 0) && ($last_shop != 0))
	  $completedprodshops[(int)$last_shop][] = (int)$last_product;
  
	// One last save is done at the end in order to save what's left
	saveIndex($query_array3);
	
	/* we update here only the indexed field for ps_product_shop */
	/* updating the outdated indexed field for ps_product happens in update_unindexed_counter() */
	foreach($shoparray AS $id_shop)
	{ if (count($completedprodshops[$id_shop]) > 0) 
	  { $uquery = 'UPDATE '._DB_PREFIX_.'product_shop SET indexed = 1 WHERE id_product IN ('.implode(",", $completedprodshops[$id_shop]).') AND id_shop='.$id_shop;
	    $ures = dbquery($uquery);
		$completedprodshops[$id_shop] = [];
	  }
    }
	$thistime = time();
	echo "<br>TIME=".date("H:i:s")." [starttime was ".date("H:i:s",$starttime)."]<br>";
	if(($thistime + 3 - $starttime) >= $maxtime)
		return update_unindexed_counter(-1);
  } /* end while true */
  return update_unindexed_counter(0);
}

/* write javascript that updates counter of unindexed products on page */
function update_unindexed_counter($unindexedcount)
{ if($unindexedcount == -1)
  { $iquery = "SELECT COUNT(DISTINCT id_product) AS prodcount FROM ". _DB_PREFIX_."product_shop WHERE indexed=0 AND visibility IN ('both', 'search') AND `active` = 1";
      $ires=dbquery($iquery);
      list($unindexedcount) = mysqli_fetch_row($ires); 
  }
  echo '<script>if(parent && parent.update_index) { parent.update_index("'.$unindexedcount.'");}</script>';
  $query = 'UPDATE '._DB_PREFIX_.'product p SET indexed=1 WHERE indexed=0 ';
  $query .= ' AND NOT EXISTS (SELECT NULL FROM '._DB_PREFIX_.'product_shop ps WHERE indexed=0 AND p.id_product=ps.id_product)';
  $res = dbquery($query);
  return true;
}

    function fillProductArray(&$product_array, $weight_array, $key, $value, $id_lang, $iso_code)
    {
        if (strncmp($key, 'id_', 3) && isset($weight_array[$key])) {
            $words = explode(' ', sanitize_index_text($value, (int)$id_lang, true, $iso_code));
            foreach ($words as $word) {
                if (!empty($word)) {
                    $word = tools_substr($word, 0, PS_SEARCH_MAX_WORD_LENGTH);

                    if (!isset($product_array[$word])) {
                        $product_array[$word] = 0;
                    }
                    $product_array[$word] += $weight_array[$key];
                }
            }
        }
	}
	
	/* tools_strlen is a modified copy of Prestashops Tools:strlen */
	function tools_strlen($str, $encoding = 'UTF-8')
	{	$str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
		if (function_exists('mb_strlen'))
			return mb_strlen($str, $encoding);
		return strlen($str);
	}
	
	/* tools_substr is a modified copy of Prestashops Tools:substr */
	function tools_substr($str, $start, $length = false, $encoding = 'utf-8')
	{	if (function_exists('mb_substr'))
			return mb_substr($str, (int)$start, ($length === false ? tools_strlen($str) : (int)$length), $encoding);
		return substr($str, $start, ($length === false ? tools_strlen($str) : (int)$length));
	}
	
    /** $queryArray3 is automatically emptied in order to be reused immediatly */
	/* saveIndex is a modified copy of Prestashops Search::saveIndex */
    function saveIndex(&$queryArray3)
    { if (is_array($queryArray3) && !empty($queryArray3)) 
	  { $query = 'INSERT INTO '._DB_PREFIX_.'search_index (id_product, id_word, weight)
				VALUES '.implode(',', $queryArray3).'
				ON DUPLICATE KEY UPDATE weight = VALUES(weight)';
		$res = dbquery($query);
      }
      $queryArray3 = array();
    }
	
	function isValidName($name, $texttype)
	{ global $errstring;
	  if (empty($name) || !preg_match('/^[^<>={}]*$/u', $name))
	  { $errstring .= "\\n".htmlspecialchars($name)." is not a valid ".$texttype."!";
	    return false;
	  }
	  return true;
	}

	function isCleanHtml($html)
	{
		$events = 'onmousedown|onmousemove|onmmouseup|onmouseover|onmouseout|onload|onunload|onfocus|onblur|onchange';
		$events .= '|onsubmit|ondblclick|onclick|onkeydown|onkeyup|onkeypress|onmouseenter|onmouseleave|onerror|onselect|onreset|onabort|ondragdrop|onresize|onactivate|onafterprint|onmoveend';
		$events .= '|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onmove';
		$events .= '|onbounce|oncellchange|oncontextmenu|oncontrolselect|oncopy|oncut|ondataavailable|ondatasetchanged|ondatasetcomplete|ondeactivate|ondrag|ondragend|ondragenter|onmousewheel';
		$events .= '|ondragleave|ondragover|ondragstart|ondrop|onerrorupdate|onfilterchange|onfinish|onfocusin|onfocusout|onhashchange|onhelp|oninput|onlosecapture|onmessage|onmouseup|onmovestart';
		$events .= '|onoffline|ononline|onpaste|onpropertychange|onreadystatechange|onresizeend|onresizestart|onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onsearch|onselectionchange';
		$events .= '|onselectstart|onstart|onstop';

		if (preg_match('/<[\s]*script/ims', $html) || preg_match('/('.$events.')[\s]*=/ims', $html) || preg_match('/.*script\:/ims', $html))
			return false;

		$allow_iframe = get_configuration_value('PS_ALLOW_HTML_IFRAME');
		if (!$allow_iframe && preg_match('/<[\s]*(i?frame|form|input|embed|object)/ims', $html))
			return false;

		return true;
	}
	