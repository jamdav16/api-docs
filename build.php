<?php
/*
api-docs
A template for creating API documentation, inspired by Stripe
Copyright (c)2015 Aaron Collegeman
MIT Licensed
*/

define('ROOT', realpath(dirname(__FILE__)));

$layoutPath = ROOT.'/src/templates/layout.html';
if (!file_exists($layoutPath)) {
	throw new \Exception("Layout missing: {$layoutPath}");
}

$outputPath = ROOT.'/index.html';
if (!is_writable($outputPath)) {
	throw new \Exception("Can't write to {$outputPath}");
}

// the top-level topics (and their subsections)
$topics = [];
// the content markup to drop into the layout
$contentHtml = '';
// the menu markup
$menuHtml = '';
// the navigation markup
$navHtml = '';

$menuGroupTemplate = '
	<optgroup label="{{ name }}">
    {{ options }}
  </optgroup>
';

$menuOptionTemplate = '
	<option value="{{ id }}">{{ name }}</option>
';

$navHeadTemplate = '
  <h5 class="sidebar-nav-heading">{{ name }}</h5>
  <ul class="sidebar-nav-items">
  	{{ items }}
  </ul>
';

$navListItemTemplate = '
	<li>
	    <a class="sidebar-nav-item" href="#{{ id }}">{{ name }}</a>
	</li>
';

function getName($entry) {
	// strip off the preceding number prefix and whitespace
	$prefixed = preg_replace('/^\d+\.?\s*/', '', $entry);
	$suffixed = preg_replace('/\.html?$/', '', $prefixed);
	return $suffixed;
}

function template($template, $data) {
	$tokens = array_map(function($key) {
		return '{{ '.$key.' }}';
	}, array_keys($data));

	// we hush here with @ to ignore array to string conversions
	return @str_replace($tokens, array_values($data), $template);
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

						if (!preg_match('#<section\s+.*?(id="(.*?)").*?>#i', $section['content'], $matches)) {
							throw new \Exception("Failed to find section id attribute for {$section['name']} in {$topic['name']}");
						}

						$section['id'] = $matches[2];

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

foreach($topics as $topic) {

	$menuOptions = [];
	$navListItems = [];

	foreach($topic['sections'] as $section) {
		$menuOptions[] = template($menuOptionTemplate, $section);
		$navListItems[] = template($navListItemTemplate, $section);
		$contentHtml .= $section['content'];
	}

	$topic['options'] = implode("\n", $menuOptions);
	$topic['items'] = implode("\n", $navListItems);

	$menuGroups[] = template($menuGroupTemplate, $topic);
	$navHeads[] = template($navHeadTemplate, $topic);
}

$menuHtml = implode("\n", $menuGroups);
$navHtml = implode("\n", $navHeads);

$indexHtml = template(file_get_contents($layoutPath), [
	'menu' => $menuHtml,
	'navigation' => $navHtml,
	'content' => $contentHtml
]);

file_put_contents($outputPath, $indexHtml);
echo "Your new api-doc is in {$outputPath}\n";