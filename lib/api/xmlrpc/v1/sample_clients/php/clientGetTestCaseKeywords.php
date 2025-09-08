<?php
 /**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Filename clientGetTestCaseKeywords.php
 *
 */
 
require_once 'util.php';
require_once 'sample.inc.php';
show_api_db_sample_msg();

// -------------------------------------------------------------------------------------
$method='getTestCaseKeywords';
$unitTestDescription="Test - {$method} - using testcaseid";

$args=[];
$args["devKey"]='admin';
$args["testcaseid"]=41;

$debug=true;
echo $unitTestDescription . '<br>';
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);


// ---
$unitTestDescription="Test - {$method} - using testcaseexternalid";

$args=[];
$args["devKey"]='admin';
$args["testcaseexternalid"]="TCS-1";

$debug=true;
echo $unitTestDescription . '<br>';
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);

// -------------------------------------------------------------------------------------
$method='getTestCaseKeywords';
$unitTestDescription="Test - {$method} - using array of testcaseid";

$args=[];
$args["devKey"]='admin';
$args["testcaseid"]=[41];

$debug=true;
echo $unitTestDescription . '<br>';
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);

// ---
$unitTestDescription="Test - {$method} - using array of testcaseexternalid";

$args=[];
$args["devKey"]='admin';
$args["testcaseexternalid"] = ["TCS-1","TCS-2","TCS-3"];

$debug=true;
echo $unitTestDescription . '<br>';
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);


