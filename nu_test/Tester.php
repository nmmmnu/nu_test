<?
namespace nu_test;


class Tester{
	private static $methods		= array("test", "Test", "TEST");
	private static $methodsPrefixes	= array("", "t", "T");

	private $_listAll	= array();
	private $_listTest	= array();
	private $_listTest2	= array();

	private $_results	= array();

	private $_inclFunc;


	function __construct(){
		// using anonymous function,
		// in order the file to lose scope completely
		$this->_inclFunc = function($file){
			if (file_exists($file)){
				require_once $file;
			}
		};

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
			foreach(self::$methodsPrefixes as $t)
				if ($reflection->hasMethod($t . $classmethod)){
					if ($t == "")
						$this->_listTest[]  = array($classname, $t . $classmethod);
					else
						$this->_listTest2[] = array($classname, $t . $classmethod);

					return;
				}
		}
	}


	function addFile($filename){
		$cl = get_declared_classes();

		$_inclFunc = $this->_inclFunc;
		$_inclFunc($filename);

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


	function start(){
		// check for partial tests
		if ($this->_listTest2)
			$this->_listTest = $this->_listTest2;

		$this->doTests();

		if (count($this->_results) == 0)
			$this->printOK();
		else
			$this->printFAIL();
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
		printf("Failed %d of %d tests:\n", count($this->_results), count($this->_listTest) );
		$i = 0;
		foreach($this->_results as $result){
			$i++;
			printf("%5d. %s\n", $i, $result);
		}
	}


	static function test(){
		$t = new self();

		$t->addDirectory("../../injector/injector/");

		$t->start();
	}
}

Tester::test();


