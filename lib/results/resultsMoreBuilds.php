<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Called from resultsMoreBuildsGUI.php to do the effective job.
 *
 * @filesource	resultsMoreBuilds.php
 * @package 	TestLink
 * @author		Kevin Levy <kevinlevy@users.sourceforge.net>
 * @copyright 	2009,2012 TestLink community
 *
 * @internal revisions
 * @since 1.9.4
 *
 **/
require_once '../../config.inc.php';
require_once 'common.php';
require_once 'users.inc.php';
require_once 'displayMgr.php';

testlinkInitPage($db, false, false, "checkRights");
$templateCfg = templateConfiguration();
$date_format_cfg = config_get('date_format');

$args = initArgs();
$gui = initializeGui($db, $args);
$mailCfg = buildMailCfg($gui);

$smarty = new TLSmarty();
$smarty->assign('gui', $gui);
$smarty->assign('report_type', $args->report_type);
displayReport($templateCfg->template_dir . $templateCfg->default_template,
    $smarty, $args->report_type, $mailCfg);

/**
 * initialize Gui
 *
 * @param database $dbHandler
 * @param stdClass $argsObj
 * @param string $dateFormat
 * @return stdClass
 */
function initializeGui(&$dbHandler, &$argsObj)
{
    $reports_cfg = config_get('reportsCfg');
    $tplan_mgr = new tlTestPlanMetrics($dbHandler);
    $tproject_mgr = new testproject($dbHandler);

    $gui = new stdClass();
    $gui->resultsCfg = config_get('results');
    $gui->title = lang_get('query_metrics_report');
    $gui->tplan_id = $argsObj->tplan_id;
    $gui->tproject_id = $argsObj->tproject_id;

    $tplan_info = $tplan_mgr->get_by_id($gui->tplan_id);
    $tproject_info = $tproject_mgr->get_by_id($gui->tproject_id);
    $gui->tplan_name = $tplan_info['name'];
    $gui->tproject_name = $tproject_info['name'];

    $getOpt = [
        'outputFormat' => 'map'
    ];
    $gui->platformSet = $tplan_mgr->getPlatforms($argsObj->tplan_id, $getOpt);
    $gui->showPlatforms = true;
    if (is_null($gui->platformSet)) {
        $gui->platformSet = null;
        $gui->showPlatforms = false;
    } else {
        $filters['platforms'] = array_keys($gui->platformSet);
    }

    // convert starttime to iso format for database usage
    [$gui->startTime, $gui->endTime] = helper2ISO($_REQUEST);

    $gui_open = config_get('gui_separator_open');
    $gui_close = config_get('gui_separator_close');
    $gui->str_option_any = $gui_open . lang_get('any') . $gui_close;
    $gui->str_option_none = $gui_open . lang_get('nobody') . $gui_close;

    $gui->search_notes_string = $argsObj->search_notes_string;

    $everest = $tplan_mgr->getRootTestSuites($gui->tplan_id, $gui->tproject_id,
        [
            'output' => 'plain'
        ]);
    $tsuites_qty = count($argsObj->testsuitesSelected);

    $filters['top_level_tsuites'] = ($tsuites_qty == 0 ||
        $tsuites_qty == count($everest)) ? null : $argsObj->testsuitesSelected;
    $gui->testsuitesSelected = [];
    foreach ($argsObj->testsuitesSelected as $dmy) {
        $gui->testsuitesSelected[$dmy] = $everest[$dmy]['name'];
    }

    $filters['builds'] = null;
    if (count($argsObj->buildsSelected)) {
        $filters['builds'] = implode(",", $argsObj->buildsSelected);
    }

    $filters['keywords'] = (array) $argsObj->keywordSelected;
    if (in_array(0, $filters['keywords'])) // Sorry for MAGIC 0 => ANY
    {
        $filters['keywords'] = null;
    }

    // Prepare User Feedback
    $gui->totals = new stdClass();
    $gui->totals->items = 0;
    $gui->totals->labels = [];

    foreach ($gui->totals->items as $key => $value) {
        $l18n = $key == 'total' ? 'th_total_cases' : $gui->resultsCfg['status_label'][$key];
        $gui->totals->labels[$key] = lang_get($l18n);
    }

    $gui->keywords = new stdClass();
    $gui->keywords->items[0] = $gui->str_option_any; // Sorry MAGIC 0
    if (! is_null(
        $tplan_keywords_map = $tplan_mgr->get_keywords_map($gui->tplan_id))) {
        $gui->keywords->items += $tplan_keywords_map;
    }
    $gui->keywords->qty = count($gui->keywords->items);
    $gui->keywordSelected = $gui->keywords->items[$argsObj->keywordSelected];

    $gui->builds_html = $tplan_mgr->get_builds_for_html_options($gui->tplan_id);
    $gui->users = getUsersForHtmlOptions($dbHandler, ALL_USERS_FILTER,
        [
            TL_USER_ANYBODY => $gui->str_option_any
        ]);

    $gui->ownerSelected = $gui->users[$argsObj->ownerSelected];
    $gui->executorSelected = $gui->users[$argsObj->executorSelected];
    $gui->buildsSelected = $argsObj->buildsSelected;
    $gui->platformsSelected = $argsObj->platformsSelected;
    $gui->display = $argsObj->display;

    // init display rows attribute and some status localized labels
    $gui->displayResults = [];
    $gui->lastStatus = [];
    foreach ($reports_cfg->exec_status as $verbose => $label) {
        $gui->displayResults[$gui->resultsCfg['status_code'][$verbose]] = false;
    }

    foreach ($gui->resultsCfg['status_label'] as $status_verbose => $label_key) {
        $gui->statusLabels[$gui->resultsCfg['status_code'][$status_verbose]] = lang_get(
            $label_key);
    }

    $lastStatus_localized = null;
    foreach ($argsObj->lastStatus as $status_code) {
        $verbose = $gui->resultsCfg['code_status'][$status_code];
        $gui->displayResults[$status_code] = true;
        $lastStatus_localized[] = lang_get(
            $gui->resultsCfg['status_label'][$verbose]);
    }
    $gui->lastStatus = $lastStatus_localized;

    return $gui;
}

/**
 * Initialize input data
 */
function initArgs()
{
    $iParams = [
        "format" => [
            tlInputParameter::INT_N
        ],
        "report_type" => [
            tlInputParameter::INT_N
        ],
        "tplan_id" => [
            tlInputParameter::INT_N
        ],
        "build" => [
            tlInputParameter::ARRAY_INT
        ],
        "platform" => [
            tlInputParameter::ARRAY_INT
        ],
        "keyword" => [
            tlInputParameter::INT_N
        ],
        "owner" => [
            tlInputParameter::INT_N
        ],
        "executor" => [
            tlInputParameter::INT_N
        ],
        "display_totals" => [
            tlInputParameter::INT_N,
            1
        ],
        "display_query_params" => [
            tlInputParameter::INT_N,
            1
        ],
        "display_test_cases" => [
            tlInputParameter::INT_N,
            1
        ],
        "display_latest_results" => [
            tlInputParameter::INT_N,
            1
        ],
        "display_suite_summaries" => [
            tlInputParameter::INT_N,
            1
        ],
        "lastStatus" => [
            tlInputParameter::ARRAY_STRING_N
        ],
        "testsuite" => [
            tlInputParameter::ARRAY_STRING_N
        ],
        "search_notes_string" => [
            tlInputParameter::STRING_N
        ]
    ];
    $args = new stdClass();

    $_REQUEST = strings_stripSlashes($_REQUEST);
    $pParams = R_PARAMS($iParams);

    $args->format = $pParams["format"];
    $args->report_type = $pParams["report_type"];
    $args->tplan_id = $pParams["tplan_id"];

    $args->tproject_id = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;

    $args->display = new stdClass();
    $args->display->suite_summaries = $pParams["display_suite_summaries"];
    $args->display->totals = $pParams["display_totals"];
    $args->display->query_params = $pParams["display_query_params"];
    $args->display->test_cases = $pParams["display_test_cases"];
    $args->display->latest_results = $pParams["display_latest_results"];

    $args->lastStatus = $pParams["lastStatus"] ? $pParams["lastStatus"] : [];
    $args->keywordSelected = $pParams["keyword"];
    $args->ownerSelected = $pParams["owner"];
    $args->executorSelected = $pParams["executor"];
    $args->buildsSelected = $pParams["build"] ? $pParams["build"] : [];
    $args->platformsSelected = $pParams["platform"] ? $pParams["platform"] : [];
    $args->testsuitesSelected = $pParams["testsuite"] ? $pParams["testsuite"] : [];
    $args->search_notes_string = $pParams['search_notes_string'];

    return $args;
}

/**
 *
 * @param stdClass $guiObj
 * @return stdClass
 */
function buildMailCfg(&$guiObj)
{
    $labels = init_labels([
        'testplan' => null,
        'testproject' => null
    ]);
    $cfg = new stdClass();
    $cfg->cc = '';
    $cfg->subject = $guiObj->title . ' : ' . $labels['testproject'] . ' : ' .
        $guiObj->tproject_name . ' : ' . $labels['testplan'] . ' : ' .
        $guiObj->tplan_name;
    return $cfg;
}

/**
 *
 * @param string $userInput
 * @return array
 */
function helper2ISO($userInput)
{
    $dateFormatMask = config_get('date_format');
    $zy = [];
    $key2loop = [
        'selected_start_date' => 'startTime',
        'selected_end_date' => 'endTime'
    ];
    foreach ($key2loop as $target => $prop) {
        if (isset($userInput[$target]) && $userInput[$target] != '') {
            $dummy = split_localized_date($userInput[$target], $dateFormatMask);
            if ($dummy != null) {
                $zy[$prop] = $dummy['year'] . "-" . $dummy['month'] . "-" .
                    $dummy['day'];
            }
        }
    }

    $dummy = isset($userInput['start_Hour']) ? $userInput['start_Hour'] : "00";
    $zy['startTime'] .= " " . $dummy . ":00:00";
    $dummy = isset($userInput['end_Hour']) ? $userInput['end_Hour'] : "00";
    $zy['endTime'] .= " " . $dummy . ":59:59";

    return [
        $zy['startTime'],
        $zy['endTime']
    ];
}

/**
 *
 * @param database $db
 * @param tlUser $user
 * @return string
 */
function checkRights(&$db, &$user)
{
    return $user->hasRight($db, 'testplan_metrics');
}
?>
