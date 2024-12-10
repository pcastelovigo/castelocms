<?php
//https://gist.github.com/Xeoncross/dc2ebf017676ae946082
function prefered_language($available_languages, $http_accept_language) {
	
	$available_languages = array_flip($available_languages);

	$langs = array();
	preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~', strtolower($http_accept_language), $matches, PREG_SET_ORDER);
	foreach($matches as $match) {
		
		list($a, $b) = explode('-', $match[1]) + array('', '');
		$value = isset($match[2]) ? (float) $match[2] : 1.0;

		if(isset($available_languages[$match[1]])) {
			$langs[$match[1]] = $value;
			continue;
		}

		if(isset($available_languages[$a])) {
			$langs[$a] = $value - 0.1;
		}

	}
	if($langs) {
		arsort($langs);
		return key($langs);
	}
}