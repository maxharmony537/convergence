<?php

//start time
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;


//lockdown


	function lockdown() {
		//flock-exclusive all important stuff, if already locked or in maintence array, do nothing.
	}

//lockdown();


//functions


	function sweeper($path) {
		$directories = array();
		$dirlist = scandir($path);
		$total = (int)count($dirlist);
		$k = 0;
		for($i = 1; $i <= $total; $i++) {
			$is_dir = is_dir("{$path}/{$dirlist[$k]}");
			if($is_dir == 1) {
				$directories[] = $dirlist[$k];
			} 
			$k++;
		}

		//return
		return array_splice($directories, 2);
	}


		function check_useragent() {
			require_once '../php-user-agent-master/lib/phpUserAgent.php';
			require_once '../php-user-agent-master/lib/phpUserAgentStringParser.php';
			$userAgent = new phpUserAgent();
			$userAgent->getBrowserName();
			$userAgent->getBrowserVersion();
			$userAgent->getOperatingSystem();
			$userAgent->getEngine();
			return $userAgent;
		}


		function check_browser() {
			$modules_installed = sweeper('view');
			foreach($modules_installed as $module_name) {
				//$browsers[$module_name] = file_get_contents('view/'.$module_name.'/browsers.json');
			}
			$useragent = check_useragent();
			$active_view_module = "default";
			if (empty($active_view_module)) {
				$active_view_module = "default";
			}
			return $active_view_module;
		}


		function check_includes($active_view_module) {
			if(!file_exists('view/'.$active_view_module.'/includes.json')) {
				gen_includes($active_view_module);
			}
			return json_decode(file_get_contents('view/'.$active_view_module.'/includes.json'), true);
		}


		function check_atomol($input, $function) {
			//$dimension and $module	
			$parameter = explode(':', $input);
			if(isset($parameter[0]) && isset($parameter[1])) {
				$dimensions_installed = array('scope', 'trust', 'love', 'user' ,'view', 'target');
				$dimension = $parameter[0];
				if(!in_array($dimension, $dimensions_installed)) {
					exit("Invalid \$dimension! {$dimension} does not exit. {$function}(\"<b>INVALID</b>\")!");
				}
				$module = $parameter[1];
				$modules_installed = sweeper($dimension);
				if(!in_array($module, $modules_installed)) {
					exit("Invalid \$module! {$module} does not exist. {$function}(\"{$dimension}.<b>INVALID</b>\")");
				}
			} else {
				exit("Specify \$dimension and \$module! {$function}(\"<b>SPECIFY</b>.<b>SPECIFY</b>\")");
			}
			//$atom or $molecule
			if(file_exists("{$dimension}/{$module}/{$function}s") != 1) {
				mkdir("{$dimension}/{$module}/{$function}s", 0775);
			}
			$atomols_installed = array_values(preg_grep("/\.v\.php$/", scandir("{$dimension}/{$module}/{$function}s/")));
			if(empty($atomols_installed)) {
				exit("There are no available {$function}s in the {$dimension}/{$module}/{$function}s/ directory.");
			}
			//$version
			if(isset($parameter[3])) {
				$atomol = $parameter[2];
				$version = $parameter[3];
				$atomols = array_values(preg_grep("/^$atomol.*$version/", $atomols_installed));
				if(empty($atomols)) {
					exit("Invalid \$version! {$version} does not exist. {$function}(\"{$dimension}.{$module}.{$atomol}.<b>INVALID</b>\")");
				}
			} else {
				if(isset($parameter[2])) {
					$atomol = $parameter[2];
					$atomols = array_values(preg_grep("/^$atomol.*/", $atomols_installed));
					if(empty($atomols)) {
						exit("Invalid \${$function}! {$atomol} does not exist. {$function}(\"{$dimension}.{$module}.<b>INVALID</b>\")");
					}
				} else {
					$first_atomol = array_slice($atomols_installed, 0, 1);
					$atomols = array($first_atomol[0]);
				}
				if(empty($atomols)) {
					exit("There are no available versions in the {$dimension}/{$module}/{$function}/ directory.");
				}
			} 
			//return
			$file = array_slice($atomols, 0, 1);
			$return = array($dimension, $module, $file[0], $function);
			return $return;
		}


		function get_funcargs($path, $function) {
			$file = file_get_contents($path);
			$regexd = '/(?<='.$function.'\(\").*?(?=\"\))/';
			$regexs = "/(?<=".$function."\(\').*?(?=\'\))/";
			preg_match_all($regexd, $file, $funcargsd);
			preg_match_all($regexs, $file, $funcargss);
			$funcargs = array_merge($funcargsd[0], $funcargss[0]);
			return $funcargs;
		}


		function gen_includes($view_module) {
			$view_module_path = "view/{$view_module}/{$view_module}.v.php";
			$molecules_present = get_funcargs($view_module_path, 'molecule'); 
			$atoms_present = get_funcargs($view_module_path, 'atom');
			$molecule_paths = array();
			$atom_paths = array();
			$molecule_array = array();
			$atom_array = array();
			$weak_array = array();
			$strong_array = array();


			//check for atoms and molecules in the view index and get their paths
			$total = (int)count($molecules_present);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$molecule_paths[] = check_atomol($molecules_present[$k], 'molecule');
				$k++;
			}
			$total = (int)count($atoms_present);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$atom_paths[] = check_atomol($atoms_present[$k], 'atom');
				$k++;
			}
			//check for molecules and atoms in molecules and get their paths
			$total = (int)count($molecule_paths);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$check_path = "{$molecule_paths[$k][0]}/{$molecule_paths[$k][1]}/molecules/{$molecule_paths[$k][2]}";
				$molecule_checked = get_funcargs($check_path, 'molecule');
				$subtotal = (int)count($molecule_checked);
				$l = 0;
				for($j = 1; $j <= $subtotal; $j++) {
					$molecule_paths[] = check_atomol($molecule_checked[$l], 'molecule');
					$l++;
				}
				$k++;
			}
			$total = (int)count($molecule_paths);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$check_path = "{$molecule_paths[$k][0]}/{$molecule_paths[$k][1]}/molecules/{$molecule_paths[$k][2]}";
				$atom_checked = get_funcargs($check_path, 'atom');
				$subtotal = (int)count($atom_checked);
				$l = 0;
				for($j = 1; $j <= $subtotal; $j++) {
					$atom_paths[] = check_atomol($atom_checked[$l], 'atom');
					$l++;
				}
				$k++;
			}
			//build array of atom and molecule paths
			$total = (int)count($atom_paths);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$atom_array[] = "{$atom_paths[$k][0]}/{$atom_paths[$k][1]}/atoms/{$atom_paths[$k][2]}";
				$k++;
			}
			$total = (int)count($molecule_paths);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$molecule_array[] = "{$molecule_paths[$k][0]}/{$molecule_paths[$k][1]}/molecules/{$molecule_paths[$k][2]}";
				$k++;
			}

			//check for strongs and weaks in atoms
			$total = (int)count($atom_paths);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$check_path = "{$atom_paths[$k][0]}/{$atom_paths[$k][1]}/atoms/{$atom_paths[$k][2]}";

				$atom_weaks = get_funcargs($check_path, 'weak');
				$subtotal = (int)count($atom_weaks);
				$l = 0;
				for($j = 1; $j <= $subtotal; $j++) {
					$weak_array[] = "{$atom_paths[$k][0]}/{$atom_paths[$k][1]}/atoms/forces/{$atom_weaks[$l]}";
					$l++;
				}
				$atom_strongs = get_funcargs($check_path, 'strong');
				$subtotal = (int)count($atom_strongs);
				$l = 0;
				for($j = 1; $j <= $subtotal; $j++) {
					$strong_array[] = "{$atom_paths[$k][0]}/{$atom_paths[$k][1]}/atoms/forces/{$atom_strongs[$l]}";
					$l++;
				}
				$k++;
			}

			//check for strongs and weaks in molecules
			$total = (int)count($molecule_paths);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$check_path = "{$molecule_paths[$k][0]}/{$molecule_paths[$k][1]}/molecules/{$molecule_paths[$k][2]}";

				$molecule_weaks = get_funcargs($check_path, 'weak');
				$subtotal = (int)count($molecule_weaks);
				$l = 0;
				for($j = 1; $j <= $subtotal; $j++) {
					$weak_array[] = "{$molecule_paths[$k][0]}/{$molecule_paths[$k][1]}/molecules/forces/{$molecule_weaks[$l]}";
					$l++;
				}
				$molecule_strongs = get_funcargs($check_path, 'strong');
				$subtotal = (int)count($molecule_strongs);
				$l = 0;
				for($j = 1; $j <= $subtotal; $j++) {
					$strong_array[] = "{$molecule_paths[$k][0]}/{$molecule_paths[$k][1]}/molecules/forces/{$molecule_strongs[$l]}";
					$l++;
				}
				$k++;
			}


			//verify arrays dont contain duplicate includes
			$atoms_included = array_unique($atom_array);
			$molecules_included = array_unique($molecule_array);
			$weaks_included = array_unique($weak_array);
			$strongs_included = array_unique($strong_array);


			//generate json file for includes
			$includes = array("atoms" => $atoms_included, "molecules" => $molecules_included, "strongs" => $strongs_included, "weaks" => $weaks_included);
			file_put_contents("view/{$view_module}/includes.json", json_encode($includes, JSON_PRETTY_PRINT));


		}


		function init($view, $includes) {
			$atoms = $includes['atoms'];
			$molecules = $includes['molecules'];

			$atomols = array_merge($atoms, $molecules);
			$total = (int)count($atomols);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				$modules[] = array_slice(explode('/', $atomols[$k]), 0, 2);
				$k++;
			}

			$modules = array_values(array_unique($modules, SORT_REGULAR));

			//remove view and/or scope $dimension
			$k = 0;
			$total = (int)count($modules);
			for($i = 1; $i <= $total; $i++) {
				if($modules[$k][0] == "view" || $modules[$k][0] == "scope") {
					unset($modules[$k]);
				}
				$k++;
			}

			$modules = array_values($modules);
			//include $modules, relevant scope controller and models (if one exists)	
			$k = 0;		
			$total = (int)count($modules);

			for($i = 1; $i <= $total; $i++) {
				$scope = explode('_', $modules[$k][1]);
				require_once("scope/{$scope[0]}/{$scope[0]}.c.php");
				$model_path = "{$modules[$k][0]}/{$modules[$k][1]}/{$modules[$k][1]}.m.php";
				if(file_exists($model_path)) {
					include($model_path);
				}
				$k++;
			}


			include("view/{$view}/{$view}.v.php");

		}







	function strong($catch = "aggregate") {
		if($catch == "aggregate") {
			global $includes;
			$strongs = $includes['strongs'];
			$total = (int)count($strongs);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				echo "<script src=\"{$strongs[$k]}\"></script>";
				$k++;
			}
		} else {
			echo "<!-- strong(\"{$catch}\") -->";
		}
	}
	
	
	function weak($catch = "aggregate") {
		if($catch == "aggregate") {
			global $includes;
			$weaks = $includes['weaks'];
			$total = (int)count($weaks);
			$k = 0;
			for($i = 1; $i <= $total; $i++) {
				echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$weaks[$k]}\">";
				$k++;
			}
		} else {
			echo "<!-- weak(\"{$catch}\") -->";
		}
	}


	function atom($catch) {
		global $includes;
		$atoms = $includes['atoms'];
		$called_atom = explode(':', $catch);
		if(empty($called_atom[2])) {
			$called_atom[2] = '.';
		}
		if(empty($called_atom[3])) {
			$called_atom[3] = '.';
		}
		$atom = array_values(preg_grep('/^'.$called_atom[0].'\/'.$called_atom[1].'\/atoms\/'.$called_atom[2].'.*'.$called_atom[3].'.*\.v\.php/', $atoms));
		if(!empty($atom[0])) {
			$GLOBALS['relpath'] = "{$called_atom[0]}/{$called_atom[1]}/atoms";
			include($atom[0]);
			echo "<!-- atom(\"{$catch}\") -->";
		}

	}


	function molecule($catch) {
		global $includes;
		$molecules = $includes['molecules'];
		$called_molecule = explode(':', $catch);

		if(empty($called_molecule[2])) {
			$called_molecule[2] = '.';
		}
		if(empty($called_molecule[3])) {
			$called_molecule[3] = '.';
		}
		$molecule = array_values(preg_grep('/^'.$called_molecule[0].'\/'.$called_molecule[1].'\/molecules\/'.$called_molecule[2].'.*'.$called_molecule[3].'.*\.v\.php/', $molecules));
		if(!empty($molecule[0])) {
			$GLOBALS['relpath'] = "{$called_molecule[0]}/{$called_molecule[1]}/molecules";
			include($molecule[0]);
			echo "<!-- molecule(\"{$catch}\") -->";
		}

	}


	function photon($catch) {
		global $relpath;
		echo "{$relpath}/photons/{$catch}";
	}


	function wormhole($catch=0) {
	$model = $module;

	}


//do the stuff
$relpath = '';
$view = check_browser();
$includes = check_includes($view);
init($view, $includes);



//print time elapsed
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$finish = $time;
		$total_time = round(($finish - $start), 4);
		echo '<br><br><hr><br>Page generated in '.$total_time.' seconds.';

?>
