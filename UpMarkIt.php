<?php

error_reporting(0);

class markdownParser {

	function parseFile($filename, $outputFile, $includeStyling) {
		if(file_exists($filename)) {
			$markdown = file_get_contents($filename);
			$html = $this->parse($markdown, $includeStyling);

			if($outputFile) {
				file_put_contents($outputFile, $html);
				echo "Success! Markdown parsed to html and saved to $outputFile.";
			}
			else echo $html;
		} else echo "Error: file does not exist.";
	}


	function parseString($markdown, $outputFile, $includeStyling) {
		$html = $this->parse($markdown, $includeStyling);
		if($outputFile) {
			file_put_contents($outputFile, $html);
			echo "Success! Markdown parsed to html and saved to $outputFile.";
		}
		else echo $html;
	}


	protected function parse($markdown, $includeStyling) {
		$blockquoteLevel = 0;
		$lines = $this->prepareInput($markdown);
		$html = $this->processLines($lines);
		if($includeStyling == "true") $html .= "<style>blockquote{display:inline-block;border-left:4px solid grey;margin-left:30px;padding:10px;background:#d8d8d8;}code{border-radius:2px;border:1px solid lightslategrey;padding:6px;}pre{margin-bottom:30px;margin-top:30px;}</style>";
		return $html;
	}


	protected function prepareInput($markdown) {
		// Split lines into array snd return
		return explode(PHP_EOL, $markdown);
	}


	protected function processLines($lines) {

		$html = "";
		$skipNextLine = false;
		$paragraphOpen = false;
		$miscLineData = array('blockquoteLevel'=>0, 'listLevel'=>0, 'codeLevel'=>0);

		// Loop through each line
		foreach ($lines as $key=>$line) {

			$isHeading = false;

			// Skip to the next line if the current one is blank
			if($this->isLineBlank($line) || $skipNextLine == true) {
				$skipNextLine = false;
				continue;
			}

			// Process the current line
			list($processedLine, $lineContents, $miscLineData) = $this->processLine($line, $miscLineData);

			// Assess current and next line type
			$currentLineType = $this->getLineType($lines[$key]);
			$nextLineType = $this->getLineType($lines[$key + 1]);

			// Open and close the correct tags around the processed line
			list($html, $paragraphOpen, $skipNextLine, $miscLineData) = $this->openCloseTags($html, $paragraphOpen, $processedLine, $lineContents, $miscLineData, $currentLineType, $nextLineType);

		}

		if($paragraphOpen) $html .= "</p>";

		return $html;
	}


	protected function regexTest($regex, $line) {
		// Simply return true or false for regex tests instead of 1, 0, or false
		return preg_match($regex, $line) === 1 ? true : false;
	}

	protected function regexMatches($regex, $line) {
		// Same as above but returns matches as well
		$success = preg_match($regex, $line, $matches) === 1 ? true : false;
		return array($success, $matches);
	}


	protected function isLineBlank($line) {
		// Use regex to assess if line is blank and return true if so
		return $this->regexTest("/^\s*$/", $line);
	}


	protected function isLineBlockquote($line) {
		// Use regex to assess if line is blockquote, returning a boolean and any matches
		return $this->regexMatches("/^ {0,3}>(.*)$/", $line, $matches);
	}


	protected function isLineList($line) {
		return $this->regexMatches("/^((?: {4}|\t)*)\s*[+\-*](.*)$/", $line, $matches);
	}


	protected function isLineCode($line) {
		return $this->regexTest("/^((?: {4}|\t)*).*$/", $line);
	}


	protected function openBlockquotes($line, $prevLevel, $level=0) {
		$openBlockquotes = "";
		$remainingLine = $line;
		list($isLineBlockquote, $matches) = $this->isLineBlockquote($line);

		if($isLineBlockquote) {
			$level++;
			if($level > $prevLevel) $openBlockquotes = "<blockquote>";

			// Recursively call protected function until all the blockquotes have been found
			$recursion = $this->openBlockquotes($matches[1], $prevLevel, $level);
			$openBlockquotes .= $recursion[0];
			$remainingLine = $recursion[1];
			$level = $recursion[2];
		}
		return array($openBlockquotes, $remainingLine, $level);
	}


	protected function closeElements($elements, $levels) {
		$closingTags = "";
		for ($i=0; $i < $levels; $i++) { 
			$closingTags .= $elements;
		}
		return $closingTags;
	}


	protected function openListElements($line, $miscLineData) {
		$level = 0;
		$html = "";
		$remainingLine = $line;
		list($isLineList, $matches) = $this->isLineList($line);

		// If line is a list element
		if($isLineList) {
			$prevLevel = $miscLineData['listLevel'];
			// Count the number of indentations to find the list level
			$level = substr_count($matches[1], "\t") + floor(substr_count($matches[1], " ") / 4) + 1;
			$remainingLine = $matches[2];

			// COmpare the new list level to the previous list level
			$levelDiff = $level - $prevLevel;
			// Code any code blocks
			$html .= $this->closeElements("</code></pre>", $miscLineData['codeLevel']);	
			$miscLineData['codeLevel'] = 0;
			switch ($levelDiff) {
				case 0:
					if($prevLevel > 0) $html .= "</li>";
					else $html .= "<ul>";
					$html .= "<li>";
					if($prevLevel == 0) $miscLineData['listLevel'] = 1;
					break;
				case 1:
					$html .= "<ul><li>";
					$miscLineData['listLevel'] += 1;
					break;
				case 2:
					list($openCode, $miscLineData) = $this->openCode($line, $miscLineData);
					$html .= $openCode;
					break;
				default:
					if($levelDiff < 0) {
						$html .= $this->closeElements("</li></ul>", abs($levelDiff));
						$miscLineData['listLevel'] -= abs($levelDiff);
						$html .= "</li><li>";
					}
					break;
			}
		}

		return array($html, $remainingLine, $miscLineData);
	}


	protected function openCode($line, $miscLineData) {
		$miscLineData['codeLevel']++;
		return array("<pre><code>$line", $miscLineData);
	}


	protected function isNextLineBlank($lines, $currentKey) {
		// Check if next line is blank or null and return a boolean
		$nextLineType = $this->getLineType($lines[$currentKey + 1]);
		return $nextLineType == "null" || $nextLineType == "blank" ? true : false;
	}


	protected function getLineType($line) {

		// Test if line doesn't exist
		if(is_null($line)) return "null";

		// Test if line is blank
		else if($this->isLineBlank($line)) return "blank";

		// Test if line is blockquote
		else if($this->isLineBlockquote($line)[0]) return "blockquote";

		// Test if line is list
		else if($this->isLineList($line)[0]) return "list";

		// Test if line consists of just whitespace and either equals or dashes, returning the heading qualifier type if so
		else if(preg_match('/^\s*(-|=)\1*\s*$/', $line, $qualifierType)) {
			if(strpos($qualifierType[1], "=") !== false) return "primaryHeadingQualifier";
			else return "secondaryHeadingQualifier";
		}

		// Test if line is code
		else if($this->isLineList($line)[0]) return "code";

		else return "text";
	}


	protected function areAnyValuesInArray($valuesArray, $fullArray) {
		$sharedValues = array_intersect($valuesArray, $fullArray);
		return count($sharedValues) === 0 ? false : true;
	}


	protected function inlineHeader($line, $lineContents) {
		// Check for any hashes (disregarding any whitespace starting the line) and store them in a variable if they exist
		// The regex doesn't count the hashes if there are more than 6
		if(preg_match("/^\s*(#{1,6})(?!#)(.*)/", $line, $matches)) {

			// Count the number of hashes to find the heading level
			$headingLevel = strlen($matches[1]);

			// Open header tag
			$line = "<h".$headingLevel.">".trim($matches[2])."</h".$headingLevel.">";

			// Add header to the line contents
			array_push($lineContents, "heading");

		}

		return array($line, $lineContents);
	}


	/*protected function inlineCode($line, $lineContents) {
		$line = preg_replace('/`(.*?)`/', '<pre><code>$1</code></pre>', $line);
		array_push($lineContents, "code");
		return array($line, $lineContents);
	}*/


	protected function otherInlineElements($line) {
		// Bold
		$line = preg_replace('/([*_])\1+([^\1]*?)\1+/', '<strong>$2</strong>', $line);
		// Italic
		$line = preg_replace('/([*_])([^\1]*?)\1/', '<em>$2</em>', $line);
		// Inline images with a title
		$line = preg_replace('/!\[(.+?)\]\((.+?) ("|\\\')(.*)\3\)/', '<img src="$2" alt="$1" title="$4"/>', $line);
		// Inline images without a title
		$line = preg_replace('/!\[(.+?)\]\((.+?)\)/', '<img src="$2" alt="$1"/>', $line);
		// Inline links with a title
		$line = preg_replace('/\[(.+?)\]\((.+?) ("|\\\')(.*)\3\)/', '<a href="$2" title="$3">$1</a>', $line);
		// Inline links without a title
		$line = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $line);
		// Inline code
		$line = preg_replace('/`(.*?)`/', '<pre><code>$1</code></pre>', $line);
		return $line;
	}


	protected function processInlineElements($html, $line, $lineContents) {
		// Inline images with a title
		$line = preg_replace('/!\[(.+?)\]\((.+?) ("|\\\')(.*)\3\)/', '<img src="$2" alt="$1" title="$4"/>', $line);
		// Inline images without a title
		$line = preg_replace('/!\[(.+?)\]\((.+?)\)/', '<img src="$2" alt="$1"/>', $line);
		// Inline links with a title
		$line = preg_replace('/\[(.+?)\]\((.+?) ("|\\\')(.*)\3\)/', '<a href="$2" title="$4">$1</a>', $line);
		// Inline links without a title
		$line = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $line);
		// Bold
		$line = preg_replace('/([*_])\1+([^\1]*?)\1+/', '<strong>$2</strong>', $line);
		// Italic
		$line = preg_replace('/([*_])([^\1]*?)\1/', '<em>$2</em>', $line);
		// Replacing any converted underscors in images and URLs
		$line = preg_replace('/(<(?:a href|img src)="[^>]*?)<em>(.*?)<\/em>/', '$1_$2_', $line);
		$line = preg_replace('/(<(?:a href|img src)="[^>]*?)<strong>(.*?)<\/strong>/', '$1__$2__', $line);
		// Inline header
		list($line, $lineContents) = $this->inlineHeader($line, $lineContents);
		// Inline code
		$line = preg_replace('/`(.*?)`/', '<pre><code>$1</code></pre>', $line);

		return array($line, $lineContents);
	}


	protected function processLine($line, $miscData) {
		$html = "";
		$lineContents = array();

		$prevBlockquoteLevel = $miscData['blockquoteLevel'];
		list($openBlockquotes, $line, $blockquoteLevel) = $this->openBlockquotes($line, $prevBlockquoteLevel);
		$html.= $openBlockquotes;


		// If previous blockquote level was over 0 now it isn't set it to be 1 
		if($prevBlockquoteLevel > 0 && $blockquoteLevel == 0) $blockquoteLevel = 1;
		$levelDiff = $prevBlockquoteLevel - $blockquoteLevel;
		// Close blockquotes that have now ended
		if($levelDiff > 0) $html .= $this->closeElements("</blockquote>", $levelDiff);
		$miscData['blockquoteLevel'] = $blockquoteLevel;

		list($openElements, $line, $miscData) = $this->openListElements($line, $miscData);
		$html .= $openElements;
		

		// Check if the line begins with 4 spaces or a tab
		//regexTest("^ {4}|\t", $line)

		list($inlineHtml, $lineContents) = $this->processInlineElements($html, $line, $lineContents);

		return array($html.$inlineHtml, $lineContents, $miscData);
	}


	protected function processHeadingQualifier($processedLine, $lineContents, $nextLineType) {
		// Check current line isn't a blockquote or list
		$canLineBeHeading = !$this->areAnyValuesInArray(array("blockquote", "list", "heading"), $lineContents);

		// Make line a heading if not and return the relevant objects
		if($canLineBeHeading) {
			$isHeading = true;
			$skipNextLine = true;
			$headingType = $nextLineType == "primaryHeadingQualifier" ? "h1" : "h2";
			$processedLine = "<".$headingType.">".$processedLine."</".$headingType.">";

			return array($isHeading, $skipNextLine, $processedLine);
		} else return array(false, false, $processedLine);
	}


	protected function openCloseTags($html, $paragraphOpen, $processedLine, $lineContents, $miscLineData, $currentLineType, $nextLineType) {
		// Set heading flag if line is already a heading
		if(in_array("heading", $lineContents)) $isHeading = true;

		// Otherwise check if next line is a qualifier for a heading
		else if($nextLineType == "primaryHeadingQualifier" || $nextLineType == "secondaryHeadingQualifier") {
			list($isHeading, $skipNextLine, $processedLine) = $this->processHeadingQualifier($processedLine, $lineContents, $nextLineType);
		}

		// If line isn't a heading, no paragraph is open, and the line isn't a quote or list, open a paragraph tag
		$isLineQuoteOrList = in_array($currentLineType, array('blockquote', 'list', 'code'));
		if(!$isHeading && !$paragraphOpen && !$isLineQuoteOrList) {
			$html .= "<p>";
			$paragraphOpen = true;
		// Otherwise if it is a heading and a paragraph is open, close it
		} else if($isHeading && $paragraphOpen) {
			$html .= "</p>";
			$paragraphOpen = false;
		// Finally if it's not a header but a paragraph is open, put a space between the lines
		} else if($paragraphOpen) $html .= " ";

		$html .= $processedLine;

		// Close the relevant tags based on the type of the next line
		switch ($nextLineType) {
			case "blockquote":
			case "list":
			case "code":
				if($paragraphOpen) {
					$html .= "</p>";
					$paragraphOpen = false;
				}
				break;
			
			case "null":
			case "blank";
				if($paragraphOpen) {
					$html .= "</p>";
					$paragraphOpen = false;
				}

				$html .= $this->closeElements("</code></pre>", $miscLineData['codeLevel']);
				$html .= $this->closeElements("</li></ul>", $miscLineData['listLevel']);
				$html .= $this->closeElements("</blockquote>", $miscLineData['blockquoteLevel']);
				$miscLineData = array('blockquoteLevel'=>0, 'listLevel'=>0, 'codeLevel'=>0);
				break;
		}

		if($nextLineType == "null" || $nextLineType == "blank") {
		}

		return array($html, $paragraphOpen, $skipNextLine, $miscLineData);
	}

}

$markdownParser = new markdownParser;

echo "\n---------------\n";

$options = getopt('f:s:o::c::');

if($options['f']) $markdownParser->parseFile($options['f'], $options['o'], $options['c']);
else if($options['s']) $markdownParser->parseString($options['s'], $options['o'], $options['c']);
else echo "Error: incorrect parameters supplied.";

echo "\n---------------\n";

?>
