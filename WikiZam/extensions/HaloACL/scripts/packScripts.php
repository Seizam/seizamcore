<?php
/*  Copyright 2009, ontoprise GmbH
 *  This file is part of the halo-Extension.
 *
 *   The halo-Extension is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   The halo-Extension is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Created on 16.12.2009
 *
 * Author: Kai
 *
 * Used to pack javascript files to one big file.
 * 
 *Usage: php packScripts.php -o hacl-packed.js -l scriptList.txt
 *  scriptList.txt enthält alle skripte die gepackt werden in der gegeb. Reihenfolge. Eines pro Zeile
 
 */

for( $arg = reset( $argv ); $arg !== false; $arg = next( $argv ) ) {

	//-o => packed output file
	if ($arg == '-o') {
		$outputFile = next($argv);
		continue;
	}

	// scripts directory
	if ($arg == '-s') {
		$scriptDir = next($argv);
		if (substr($scriptDir,-1)!='/'){
			$scriptDir .= '/';
		}
		continue;
	}

	// scripts list
	if ($arg == '-l') {
		$scriptList = next($argv);
		 
		continue;
	}

}

$scripts = array();
echo "Collect scripts...!\r\n";
if (isset($scriptList)) {
	$scriptFiles = explode("\n", str_replace("\r", "", file_get_contents($scriptList)));
	foreach($scriptFiles as $f) {
		$scripts[] = file_get_contents(trim($f));
		echo trim($f)."\n";
	}
} else {
	collectScripts($scriptDir, $scripts, array($outputFile));
}
echo "Write file...\r\n";
writeFile($outputFile, $scripts);
echo "Done!\r\n";


function collectScripts($botDir, & $scripts, $toSkip = array()) {

	$handle = @opendir($botDir);
	if (!$handle) {
		trigger_error("\nDirectory '$botDir' could not be opened.\n");
	}

	while ( ($entry = readdir($handle)) !== false ){
		if ($entry[0] == '.'){
			continue;
		}

		if (is_dir($botDir."/".$entry)) {
			// Unterverzeichnis
			collectScripts($botDir."/".$entry);

		} else{

			if (strpos($botDir.$entry, ".js") !== false && !in_array($entry, $toSkip)) {

				$text = file_get_contents($botDir."/".$entry);
				$scripts[] = $text;
			}
		}
	}
}

function writeFile($file, $scripts) {
	$handle = fopen($file,"wb");
	echo "Write in output file: ".$file."\r\n";
	foreach($scripts as $script){
		fwrite($handle, $script);
	}
	fclose($handle);

}
