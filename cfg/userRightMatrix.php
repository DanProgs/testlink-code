<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 *
 * @filesource	userRightMatrix.php
 * @author Andreas Morsing
 *
 * Configuration of the access rights needed for executing pages
 *
 *
 **/
// user right matrix,
// for each file which calls testLinkInitPage it's
// possible to set the rights needed to execute the script
//
// keys are the filenames (lowercase)
// values are the right(s) needed to execute it,
// maybe array : multiple rights needed
// string : exactly one right need
// null : no rights need

// urls
$user_admin_url = 'lib/usermanagement';
$proj_admin_url = 'lib/project';
$test_exec_url = 'lib/execute';
$kword_admin_url = 'lib/keywords';
$tplan_admin_url = 'lib/plan';
$req_admin_url = 'lib/req';
$reports_url = 'lib/result';
$tc_admin_url = 'lib/testcases';
$cf_admin_url = 'lib/cfields';
$print_url = 'lib/print';

//
$user_admin = [
    "{$user_admin_url}/usersassign.php" => [
        "user_role_assignment"
    ]
];
$proj_admin = [
    "{$proj_admin_url}/projectEdit.php" => [
        "mgt_modify_product"
    ]
];
$test_exec = [
    "{$test_exec_url}/execnavigator.php" => [
        "testplan_execute"
    ]
];

$tplan_admin = [
    "{$tplan_admin_url}/planupdatetc.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/planaddtc.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/planaddtcnavigator.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/planedit.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/plannew.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/planpriority.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/planupdatetc.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/planmilestoneedit.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/plantcnavigator.php" => [
        "testplan_planning"
    ],
    "{$tplan_admin_url}/plantcremove.php" => [
        "testplan_planning"
    ]
];

$reports = [
    "{$reports_url}/resultsallbuilds.php" => [
        "testplan_metrics"
    ],
    "{$reports_url}/resultsbugs.php" => [
        "testplan_metrics"
    ],
    "{$reports_url}/resultsbuild.php" => [
        "testplan_metrics"
    ],
    "{$reports_url}/resultsbystatus.php" => [
        "testplan_metrics"
    ],
    "{$reports_url}/resultsgeneral.php" => [
        "testplan_metrics"
    ],
    "{$reports_url}/resultsnavigator.php" => [
        "testplan_metrics"
    ],
    "{$reports_url}/resultssend.php" => [
        "testplan_metrics"
    ],
    "{$reports_url}/resultstc.php" => [
        "testplan_metrics"
    ]
];

$tc_admin = [
    "{$tc_admin_url}/containeredit.php" => [
        "mgt_modify_tc",
        "mgt_view_tc"
    ],
    "{$tc_admin_url}/tcedit.php" => [
        "mgt_modify_tc",
        "mgt_view_tc"
    ],
    "{$tc_admin_url}/tcimport.php" => [
        "mgt_modify_tc",
        "mgt_view_tc"
    ],
    "{$tc_admin_url}/searchform.php" => null,
    "{$tc_admin_url}/searchdata.php" => null,
    "{$tc_admin_url}/listtestcases.php" => null
];

$print_data = [
    "{$print_url}/printdata.php" => null,
    "{$print_url}/selectdata.php" => null
];

$cf_admin = [
    "{$cf_admin_url}/cfieldsEdit.php" => [
        "cfield_management"
    ],
    "{$cf_admin_url}/cfieldsView.php" => [
        "cfield_view"
    ],
    "{$cf_admin_url}/cfieldsTProjectAssign.php" => [
        "cfield_management"
    ]
];

// build rigth matrix
$g_userRights = $user_admin + $proj_admin + $test_exec + $print_data +
    $tplan_admin + $reports + $tc_admin + $cf_admin;
