<?php
/*
api-docs
A template for creating API documentation, inspired by Stripe
Copyright (c)2015 Aaron Collegeman
MIT Licensed
*/

define('ROOT', realpath(dirname(__FILE__)));

if (!$extConfig = json_decode(file_get_contents(ROOT.'/build.config.json'))) {
	throw new \Exception("Unable to parse build config file");
}

$baseConfig = [
	'templatePath' => ROOT .'/src/templates',
	'layoutPath' => '',
	'outputPath' => ROOT.'/index.html'
];

$config = array_merge($baseConfig, (array) $extConfig);

if (empty($config['layoutPath'])) {
	$config['layoutPath'] = $config['templatePath'].'/layout.html';
}

if (!file_exists($config['layoutPath'])) {
	throw new \Exception("Layout file not found: {$config['layoutPath']}");
}

if (!is_writable($config['outputPath'])) {
	throw new \Exception("Can't write to output path: {$config['outputPath']}");
}

$errors = [];

// the top-level topics (and their subsections)
$topics = [];
// the content markup to drop into the layout
$contentHtml = '';
// the menu markup
$menuHtml = '';
// the navigation markup
$navHtml = '';

function getTemplate($filename) {
	global $config;
	$path = $config['templatePath'].'/'.$filename.'.html';
	if (!file_exists($path)) {
		throw new \Exception("Template file {$filename} not found: ".$path);
	}
	return file_get_contents($path);
}

$menuGroupTemplate = getTemplate('menu-group');
$menuOptionTemplate = getTemplate('menu-option');
$navHeadTemplate = getTemplate('nav-head');
$navListItemTemplate = getTemplate('nav-list-item');
	
function getName($entry) {
	// strip off the preceding number prefix and whitespace
	$prefixed = preg_replace('/^\d+\.?\s*/', '', $entry);
	$suffixed = preg_replace('/\.html?$/', '', $prefixed);
	return $suffixed;
}

function template($template, $data) {
	global $config;

	$data = array_merge($config, $data);

	$tokens = array_map(function($key) {
		return '{{ '.$key.' }}';
	}, array_keys($data));

	// we hush here with @ to ignore array to string conversions
	return @str_replace($tokens, array_values($data), $template);
}

function getSectionId($content) {
	if (!preg_match('#<section\s+.*?(id="(.*?)").*?>#i', $content, $matches)) {
		return false;
	} else {
		return $matches[2];
	}
}

function transformHLJS($content, &$languages) {
	$languages = [];

	return preg_replace_callback('#<hljs\s*(lang="(.*?)")?\s*>(.*?)</hljs>#is', function($matches) use ($languages) {
		$lang = trim($matches[2]);
		if ($lang) {
			$languages[] = $lang;
		}
		$code = $matches[3];
		$firstLineFound = false;
		$lines = explode("\n", $code);
		$whitespace = '';
		$formatted = [];

		// walk lines and normalize leading space
		foreach($lines as $line) {
			if (!$firstLineFound) {
				if (!trim($line)) {
					continue;
				}
				$firstLineFound = true;
				if (preg_match('/^(\s+)/', $line, $matches)) {
					$whitespace = $matches[1];
					// echo "WHITESPACE: |{$whitespace}|\n";
				}
			}

			if ($whitespace) {
				$line = preg_replace("#^{$whitespace}#", '', $line);
			}
			$formatted[] = $line;
		}

		$class = "lang";
		$codeClass = 'nohighlight';

		if ($lang && $lang !== "none") {
			$class .= " lang-{$lang}";
			$codeClass = "language-{$lang}";
		}

		return 
			'<span class="'.$class.'"><pre><code class="'.$codeClass.'">'
				. htmlentities(rtrim(implode("\n", $formatted)))
			. '</code></pre></span>';

	}, $content);

	return $content;
}

function transform($section, &$languages) {
	$content = $section['content'];
	$content = transformHLJS($content, $languages);

	return $content;
}

$contentPath = ROOT.'/src/content';
$dir = opendir($contentPath);

while ($entry = readdir($dir)) {

	if ($entry !== '.' && $entry !== '..') {

		$path = "{$contentPath}/$entry";

		if (is_dir($path)) {

			$topic = [
				'name' => getName($entry),
				'sections' => [],
				'path' => $path
			];

			$topicDirPath = "{$contentPath}/{$entry}";
			$topicDir = opendir($topicDirPath);

			while($entry = readdir($topicDir)) {
				
				if ($entry !== '.' && $entry !== '..') {
					
					$path = "{$topic['path']}/{$entry}";

					if (is_file($path))	{
						$section = [
							'name' => getName($entry),
							'content' => file_get_contents($path),
							'path' => $path
						];

						if (!$section['id'] = getSectionId($section['content'])) {
							$error = "ERROR: Failed to find section id attribute for \"{$section['name']}\" in \"{$topic['name']}\"; skipping.";
							echo $error . "\n";
							$errors[] = $error;
							continue;
						}

						$topic['sections'][] = $section;
					}

				}

			}	

			closedir($topicDir);
			$topics[] = $topic;

		}

	}

}

closedir($dir); // root content dir

// print_r($topics);

$menuGroups = [];
$navHeads = [];
$languages = [];

foreach($topics as $topic) {

	$menuOptions = [];
	$navListItems = [];
	$topicHtml = '';

	foreach($topic['sections'] as $section) {
		$menuOptions[] = template($menuOptionTemplate, $section);
		$navListItems[] = template($navListItemTemplate, $section);
		$transformed = transform($section, $languages);
		$topicHtml .= '<div class="method-group">' . $transformed . '</div>';
	}

	$contentHtml .= $topicHtml;

	$topic['options'] = implode("\n", $menuOptions);
	$topic['items'] = implode("\n", $navListItems);

	$menuGroups[] = template($menuGroupTemplate, $topic);
	$navHeads[] = template($navHeadTemplate, $topic);
}

$menuHtml = implode("\n", $menuGroups);
$navHtml = implode("\n", $navHeads);

$indexHtml = template(file_get_contents($config['layoutPath']), [
	'menu' => $menuHtml,
	'navigation' => $navHtml,
	'content' => $contentHtml
]);

file_put_contents($config['outputPath'], $indexHtml);
echo "Done building" . ($errors ? ', but with errors.' : '.') . "\n";
echo "Your new api-doc is in {$config['outputPath']}\n";