<?php

namespace SocialKit;

class UI {
	private $filename;

	public static function view($file)
	{
		global $config;
	    
		$page = 'themes/' . $config['theme'] . '/layout/' . $file . '.phtml';
		$contentOpen = fopen($page, 'r');
		$content = @fread($contentOpen, filesize($page));
		fclose($contentOpen);

		$content = preg_replace_callback(
	        '/@([a-zA-Z0-9_]+)@/',

	        function ($matches)
	        {
	        	global $lang;
	        	$matches[1] = strtolower($matches[1]);
	        	return (isset($lang[$matches[1]]) ? $lang[$matches[1]] : "");
	        },

	        $content
	    );

	    $content = preg_replace_callback(
	        '/{{([A-Z0-9_]+)}}/',

	        function ($matches)
	        {
	        	global $themeData;
	        	$matches[1] = strtolower($matches[1]);
	        	return (isset($themeData[$matches[1]]) ? $themeData[$matches[1]] : "");
	        },

	        $content
	    );
		
		return $content;
	}
}