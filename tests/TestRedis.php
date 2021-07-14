<?php define('PHPREDIS_TESTRUN', true);

require_once(dirname($_SERVER['PHP_SELF'])."/TestSuite.php");
require_once(dirname($_SERVER['PHP_SELF'])."/RedisTest.php");
require_once(dirname($_SERVER['PHP_SELF'])."/RedisArrayTest.php");
require_once(dirname($_SERVER['PHP_SELF'])."/RedisClusterTest.php");
require_once(dirname($_SERVER['PHP_SELF'])."/RedisSentinelTest.php");

/* Make sure errors go to stdout and are shown */
error_reporting(E_ALL);
ini_set( 'display_errors','1');

/* Grab options */
$arr_args = getopt('', ['host:', 'port:', 'class:', 'test:', 'nocolors', 'user:', 'auth:']);

/* Class name to Test class name lookup table */
$arr_valid_classes = [
    'redis'         => 'Redis_Test',
    'redisarray'    => 'Redis_Array_Test',
    'rediscluster'  => 'Redis_Cluster_Test',
    'redissentinel' => 'Redis_Sentinel_Test'
];

/* Grab the test the user is trying to run */
$str_class = isset($arr_args['class']) ? strtolower($arr_args['class']) : 'redis';
$boo_colorize = !isset($arr_args['nocolors']);

/* Get our test filter if provided one */
$str_filter = isset($arr_args['test']) ? $arr_args['test'] : NULL;

/* Grab override host/port if it was passed */
$str_host = isset($arr_args['host']) ? $arr_args['host'] : '127.0.0.1';
$i_port = isset($arr_args['port']) ? intval($arr_args['port']) : 6379;

/* Get optional username and auth (password) */
$str_user = isset($arr_args['user']) ? $arr_args['user'] : NULL;
$str_auth = isset($arr_args['auth']) ? $arr_args['auth'] : NULL;

/* Massage the actual auth arg */
$auth = NULL;
if ($str_user && $str_auth) {
    $auth = [$str_user, $str_auth];
} else if ($str_auth) {
    $auth = $str_auth;
} else if ($str_user) {
    echo TestSuite::make_warning("User passed without a password, ignoring!\n");
}

/* If the user specified a specific test class and it's not one of the
 * built-in classes, attempt to load it into the namespace */
if (isset($arr_args['class']) && ! in_array($str_class, $arr_valid_classes))
    $str_class = TestSuite::loadTestClass($str_class);

/* Validate the class is known */
if (!isset($arr_valid_classes[$str_class])) {
    if ( ! Testsuite::loadTestClass($str_class)) {
        echo "Error:  Valid test classes are Redis, RedisArray, RedisCluster and RedisSentinel!\n";
        exit(1);
    }
} else {
    $str_class = $arr_valid_classes[$str_class];
}

/* Toggle colorization in our TestSuite class */
TestSuite::flagColorization($boo_colorize);

/* Let the user know this can take a bit of time */
echo "Note: these tests might take up to a minute. Don't worry :-)\n";
echo "Using PHP version " . PHP_VERSION . " (" . (PHP_INT_SIZE*8) . " bits)\n";

/* Depending on the classes being tested, run our tests on it */
echo "Testing class ";
if ($str_class == 'redisarray') {
    echo TestSuite::make_bold("RedisArray") . "\n";
    global $useIndex;
    foreach(array(true, false) as $useIndex) {
        echo "\n".($useIndex?"WITH":"WITHOUT"). " per-node index:\n";

        /* The various RedisArray subtests we can run */
        $arr_ra_tests = [
            'Redis_Array_Test', 'Redis_Rehashing_Test', 'Redis_Auto_Rehashing_Test',
            'Redis_Multi_Exec_Test', 'Redis_Distributor_Test'
        ];

        foreach ($arr_ra_tests as $str_test) {
            /* Run until we encounter a failure */
            if (run_tests($str_test, $str_filter, $str_host, $auth) != 0) {
                exit(1);
            }
        }
    }
} else {
    echo TestSuite::make_bold($str_class) . "\n";
    exit(TestSuite::run("$str_class", $str_filter, $str_host, $i_port, $auth));
}
?>
