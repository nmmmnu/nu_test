<?
namespace nu_test;

// using this class,
// in order the file to lose scope completely
class TesterIncluder{
	static function incl($_file){
		if (file_exists($_file)){
			require_once $_file;
		}
	}
}


class Tester{
	private static $methods		= array("test", "Test", "TEST");
	private static $methodsPrefixes	= array("", "t", "T");

	private $_listAll	= array();
	private $_listTest	= array();
	private $_listTest2	= array();

	private $_results	= array();


	function __construct(){
		$this->assertSetup();
	}


	private function assertSetup(){
		assert_options(ASSERT_ACTIVE,   true);
		assert_options(ASSERT_BAIL,     false);
		assert_options(ASSERT_WARNING,  false);
		assert_options(ASSERT_CALLBACK, function($script, $line, $message){
			$this->_results[] = "Condition failed in $script, Line: $line";
		});
	}


	function addClass($classname){
		if (in_array($classname, $this->_listAll))
			return;

		$this->_listAll[] = $classname;

		$reflection  = new \ReflectionClass($classname);

		foreach(self::$methods as $classmethod){
			foreach(self::$methodsPrefixes as $t){
				$classmethod = $t . $classmethod;

				if (! $reflection->hasMethod($classmethod))
					continue;

				$reflectionMethod = $reflection->getMethod($classmethod);
				if (! $reflectionMethod->isStatic())
					continue;

				if ($t == "")
					$this->_listTest[]  = array($classname, $classmethod);
				else
					$this->_listTest2[] = array($classname, $classmethod);

				return;
			}
		}
	}


	function addFile($filename){
		$cl = get_declared_classes();

		TesterIncluder::incl($filename);

		foreach( array_diff(get_declared_classes(), $cl) as $classname )
			$this->addClass($classname);
	}


	function addDirectory($dir){
		$Directory = new \RecursiveDirectoryIterator($dir);
		$Iterator  = new \RecursiveIteratorIterator($Directory);
		$Regex     = new \RegexIterator($Iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

		foreach($Regex as $file)
			$this->addFile( current($file) );
	}


	function start($printing_results = true){
		// check for partial tests
		if ($this->_listTest2)
			$this->_listTest = $this->_listTest2;

		$this->doTests();

		if ($printing_results){
			if (count($this->_results) == 0)
				$this->printOK();
			else
				$this->printFAIL();
		}
	}


	private function doTests(){
		foreach($this->_listTest as $classmethod){
			printf("Testing %s\n", $classmethod[0]);

			$classmethod();
		}
	}


	private function printOK(){
		printf("All %d test(s) passed!!!\n", count($this->_listTest));
		echo "You are awesome :)\n";
	}


	private function printFAIL(){
		printf("Failed %d asserts of %d tests:\n", count($this->_results), count($this->_listTest) );
		$i = 0;
		foreach($this->_results as $result){
			$i++;
			printf("%5d. %s\n", $i, $result);
		}
	}





	function test2($dir, $expected, $failed){
		$this->addDirectory(__DIR__ . "/" . $dir, $expected);

		$list = count($this->_listTest2) > 0 ? $this->_listTest2 : $this->_listTest;

		assert(count($list) == 1);

		assert($list[0][0] ==  $expected);

		$this->start(false);

		assert(count($this->_results) == $failed);

		if (count($this->_results) > $failed)
			$this->printFAIL();
	}


	static function test1($dir, $expected, $failed = 0){
		$t = new self();
		$t->test2($dir, $expected, $failed);
	}


	static function test(){
		self::test1("./tests/a/", "nu_test\\tests\\a\\TestClass1");
		self::test1("./tests/b/", "nu_test\\tests\\b\\TestClass1");
		self::test1("./tests/c/", "nu_test\\tests\\c\\TestClass1", $failed = 1);
	}
}


if ($argv[0] == $_SERVER["SCRIPT_FILENAME"] && count($argv) > 1 ){
	$tester = new Tester();

	foreach(array_slice($argv, 1) as $path){
		if (is_file($path))
			$tester->addFile($path);

		if (is_dir($path))
			$tester->addDirectory($path);
	}

	$tester->start();
}

/*
Reflection Naive Unit Tester

Scans a directory, finds all php files there, checks if there is static method test() and runs it.
If one or more ttest() methods are found, the tester runs only them.

Usage:
	$argv[0] [file|directory] [file|directory] [file|directory] ...

Hint:
	In case of multiple files / directories, put the autoloader first.

END;
*/


