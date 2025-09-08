<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Never Run, means on ALL ACTIVE BUILDS by Test Plan and Platform
 *
 * @filesource  neverRunByPP.php
 * @package     TestLink
 * @copyright   2007-2019, TestLink community
 * @link        http://www.testlink.org
 *
 */
require_once '../../config.inc.php';
require_once '../../third_party/codeplex/PHPExcel.php'; // Must be included BEFORE common.php
require_once 'common.php';
require_once 'displayMgr.php';
require_once 'users.inc.php';
require_once 'exttable.class.php';
require_once 'exec.inc.php'; // used for bug string lookup

// IMPORTANT NOTICE/WARNING about XLS generation
// Seams that \n are not liked
// http://stackoverflow.com/questions/5960242/how-to-make-new-lines-in-a-cell-using-phpexcel
//

$tplCfg = templateConfiguration();

[$tplan_mgr, $args] = initArgsForReports($db);
if (null == $tplan_mgr) {
    $tplan_mgr = new testplan($db);
}
$tcaseMgr = new testcase($db);

$gui = initializeGui($db, $args, $tplan_mgr);
$labels = &$gui->labels;

$testCaseCfg = config_get('testcase_cfg');

// done here in order to get some config about images
$smarty = new TLSmarty();

$doIt = false;
$doChoice = true;

$metrics = null;

if ($args->doAction == 'result') {
    $metrics = getMetrics($db, $args, $gui);
}

if ($args->doAction == 'result' && ! empty($metrics)) {

    $doIt = true;
    $doChoice = false;

    $tpl = $tplCfg->default_template;

    $urlSafeString = [];
    $urlSafeString['tprojectPrefix'] = urlencode($gui->tproject_info['prefix']);
    $urlSafeString['basehref'] = str_replace(" ", "%20", $args->basehref);

    $out = [];
    $pathCache = null;
    $topCache = null;
    $levelCache = null;
    $nameCache = initNameCache($gui);

    $odx = 0;
    foreach ($metrics as &$elem) {
        // do some decode work, using caches
        if (! isset($pathCache[$elem['tcase_id']])) {
            $du = $tcaseMgr->getPathLayered([
                $elem['tcase_id']
            ]);
            $pathCache[$elem['tcase_id']] = $du[$elem['tsuite_id']]['value'];
            $levelCache[$elem['tcase_id']] = $du[$elem['tsuite_id']]['level'];
            $ky = current(array_keys($du));
            $topCache[$elem['tcase_id']] = $ky;
        }

        // IMPORTANT NOTICE:
        //
        // Column ORDER IS CRITIC
        // testTitle CCA-15708: RSRSR-150
        // platformName XXXX <<< ONlY is platforms have been used on
        // Test plan under analisys
        //
        // $out[$odx]['suiteName'] = $pathCache[$exec['tcase_id']];
        $zipper = '';
        switch ($args->format) {
            case FORMAT_HTML:
                $out[$odx]['testTitle'] = "<!-- " .
                    sprintf("%010d", $elem['external_id']) . " -->";
                $zipper = '';
                break;

            case FORMAT_XLS:
                $out[$odx]['testTitle'] = '';
                break;

            default:
                $out[$odx]['testTitle'] = '<a href="' .
                    $urlSafeString['basehref'] . 'linkto.php?tprojectPrefix=' .
                    $urlSafeString['tprojectPrefix'] . '&item=testcase&id=' .
                    urlencode($exec['full_external_id']) . '">';
                $zipper = '</a>';
                break;
        }

        // See IMPORTANT NOTICE/WARNING about XLS generation
        $out[$odx]['testTitle'] .= $elem['full_external_id'] . ':' .
            $elem['name'] . $zipper;

        // Insert order on out is CRITIC, because order is used on buildMatrix
        if ($gui->show_platforms) {
            $out[$odx]['platformName'] = $nameCache['platform'][$elem['platform_id']];
        }

        $odx ++;
    }
    $gui->dataSet = $out;
    unset($out);
}

$gui->urlSendExcelByEmail = $args->basehref . "lib/results/neverRunByPP.php?" .
    "format=" . FORMAT_XLS . "&tplan_id=$gui->tplan_id" .
    "&tproject_id=$gui->tproject_id&doAction=result";

if ($doIt) {
    switch ($args->format) {
        case FORMAT_XLS:
            createSpreadsheet($gui, $args, $args->getSpreadsheetBy, $cfSet);
            break;

        default:
            $tableOpt = [
                'format' => $args->format,
                'show_platforms' => $gui->show_platforms
            ];

            $gui->tableSet[] = buildMatrix($gui->dataSet, $args,
                $gui->platformSet, $tableOpt);
            break;
    }
}

$smarty = new TLSmarty();
$smarty->assign('gui', $gui);

if ($doChoice) {
    $tpl = 'neverRunByPPLauncher.tpl';
    $gui->url2call = $args->basehref .
        "lib/results/neverRunByPP.php?tplan_id=$gui->tplan_id" .
        "&tproject_id=$gui->tproject_id&format=$gui->format&doAction=result";
}

displayReport($tplCfg->template_dir . $tpl, $smarty, $args->format,
    $gui->mailCfg);

/**
 *
 * @param database $dbHandler
 * @return stdClass
 */
function initArgs(&$dbHandler)
{
    $iP = [
        "apikey" => [
            tlInputParameter::STRING_N,
            32,
            64
        ],
        "tproject_id" => [
            tlInputParameter::INT_N
        ],
        "tplan_id" => [
            tlInputParameter::INT_N
        ],
        "format" => [
            tlInputParameter::INT_N
        ],
        "type" => [
            tlInputParameter::STRING_N,
            0,
            1
        ],
        "platSet" => [
            tlInputParameter::ARRAY_INT
        ],
        "doAction" => [
            tlInputParameter::STRING_N,
            5,
            10
        ]
    ];

    $args = new stdClass();
    R_PARAMS($iP, $args);

    $cx = 'sendSpreadSheetByMail_x';
    $args->getSpreadsheetBy = isset($_REQUEST[$cx]) ? 'email' : null;
    if (is_null($args->getSpreadsheetBy)) {
        $cx = 'exportSpreadSheet_x';
        $args->getSpreadsheetBy = isset($_REQUEST[$cx]) ? 'download' : null;
    }

    $args->addOpAccess = true;
    if (! is_null($args->apikey)) {
        $cerbero = new stdClass();
        $cerbero->args = new stdClass();
        $cerbero->args->tproject_id = $args->tproject_id;
        $cerbero->args->tplan_id = $args->tplan_id;

        if (strlen($args->apikey) == 32) {
            $cerbero->args->getAccessAttr = true;
            $cerbero->method = 'checkRights';
            $cerbero->redirect_target = "../../login.php?note=logout";
            setUpEnvForRemoteAccess($dbHandler, $args->apikey, $cerbero);
        } else {
            $args->addOpAccess = false;
            $cerbero->method = null;
            setUpEnvForAnonymousAccess($dbHandler, $args->apikey, $cerbero);
        }
    } else {
        testlinkInitPage($dbHandler, true, false, "checkRights");
        $args->tproject_id = isset($_SESSION['testprojectID']) ? intval(
            $_SESSION['testprojectID']) : 0;
    }

    $args->user = $_SESSION['currentUser'];
    $args->basehref = $_SESSION['basehref'];

    return $args;
}

/**
 * initializeGui
 *
 * @param database $dbh
 * @param stdClass $argsObj
 * @param testplan $tplanMgr
 * @return stdClass
 */
function initializeGui(&$dbh, &$argsObj, &$tplanMgr)
{
    $tprojectMgr = new testproject($dbh);

    $guiObj = new stdClass();

    $guiObj->labels = init_labels(
        [
            'deleted_user' => null,
            'design' => null,
            'execution' => null,
            'nobody' => null,
            'execution_history' => null,
            'info_notrun_tc_report' => null,
            'title' => 'neverRunByPP_title'
        ]);

    $guiObj->title = $guiObj->labels['title'];
    $guiObj->pageTitle = $guiObj->title;
    $guiObj->report_context = '';
    $guiObj->info_msg = '';

    $guiObj->tplan_info = $tplanMgr->get_by_id($argsObj->tplan_id);
    $guiObj->tproject_info = $tprojectMgr->get_by_id($argsObj->tproject_id);
    $guiObj->tplan_name = $guiObj->tplan_info['name'];
    $guiObj->tproject_name = $guiObj->tproject_info['name'];

    $guiObj->format = $argsObj->format;
    $guiObj->tproject_id = $argsObj->tproject_id;
    $guiObj->tplan_id = $argsObj->tplan_id;
    $guiObj->apikey = $argsObj->apikey;

    $guiObj->dataSet = null;
    $guiObj->type = $argsObj->type;
    $guiObj->warning_msg = '';

    // needed to decode
    $getOpt = [
        'outputFormat' => 'map',
        'addIfNull' => true
    ];
    $guiObj->platformSet = $tplanMgr->getPlatforms($argsObj->tplan_id, $getOpt);

    $guiObj->show_platforms = true;
    $pqy = count($guiObj->platformSet);
    if ($pqy == 0 || ($pqy == 1) && isset($guiObj->platformSet[0])) {
        $guiObj->show_platforms = false;
    }

    // will be used when sending mail o creating spreadsheet
    $guiObj->platSet = [];
    if (! empty($argsObj->platSet)) {
        $pp = array_flip($argsObj->platSet);
    }
    if (! isset($pp[0])) {
        // we have platforms
        foreach ($argsObj->platSet as $pk) {
            $guiObj->platSet[$pk] = $pk;
        }
    }

    $guiObj->mailCfg = buildMailCfg($guiObj);

    return $guiObj;
}

/**
 *
 * @param database $db
 * @param tlUser $user
 * @param stdClass $context
 * @return string
 */
function checkRights(&$db, &$user, $context = null)
{
    if (is_null($context)) {
        $context = new stdClass();
        $context->tproject_id = null;
        $context->tplan_id = null;
        $context->getAccessAttr = false;
    }
    return $user->hasRightOnProj($db, 'testplan_metrics', $context->tproject_id,
        $context->tplan_id, $context->getAccessAttr);
}

/**
 *
 * @param stdClass $guiObj
 * @return stdClass
 */
function buildMailCfg(&$guiObj)
{
    $labels = [
        'testplan' => lang_get('testplan'),
        'testproject' => lang_get('testproject')
    ];
    $cfg = new stdClass();
    $cfg->cc = '';
    $cfg->subject = $guiObj->title . ' : ' . $labels['testproject'] . ' : ' .
        $guiObj->tproject_name . ' : ' . $labels['testplan'] . ' : ' .
        $guiObj->tplan_name;

    return $cfg;
}

/**
 * Builds ext-js rich table to display matrix results
 *
 * @param array $dataSet
 *            data to be displayed on matrix
 * @param stdClass $args
 * @param array $options
 * @param array $platforms
 * @param array $customFieldColumns
 * @return tlExtTable|tlHTMLTable
 */
function buildMatrix($dataSet, &$args, $platforms, $options = [])
{
    $default_options = [
        'show_platforms' => false,
        'format' => FORMAT_HTML
    ];
    $options = array_merge($default_options, $options);

    $l18n = init_labels([
        'platform' => null
    ]);
    $columns = [];
    $columns[] = [
        'title_key' => 'title_test_case_title',
        'width' => 80,
        'type' => 'text'
    ];

    if ($options['show_platforms']) {
        $columns[] = [
            'title_key' => 'platform',
            'width' => 60,
            'filter' => 'list',
            'filterOptions' => $platforms
        ];
    }

    if ($options['format'] == FORMAT_HTML) {

        // IMPORTANT DEVELOPMENT NOTICE
        // columns and dataSet are deeply related this means that inside
        // dataSet order has to be identical that on columns or table will be a disaster
        //
        $matrix = new tlExtTable($columns, $dataSet,
            'tl_table_results_by_status');

        // if not run report: sort by test suite
        // blocked, failed report: sort by platform (if enabled) else sort by date
        $sort_name = 0;
        $sort_name = $options['show_platforms'] ? $l18n['platform'] : '';

        $matrix->setSortByColumnName($sort_name);
        $matrix->addCustomBehaviour('text', [
            'render' => 'columnWrap'
        ]);

        // define table toolbar
        $matrix->showToolbar = true;
        $matrix->toolbarExpandCollapseGroupsButton = true;
        $matrix->toolbarShowAllColumnsButton = true;
    } else {
        $matrix = new tlHTMLTable($columns, $dataSet,
            'tl_table_results_by_status');
    }
    return $matrix;
}

/**
 *
 * @param stdClass $guiObj
 * @return NULL[]|string
 */
function initNameCache($guiObj)
{
    $safeItems = [
        'platform' => null
    ];

    if ($guiObj->show_platforms) {
        foreach ($guiObj->platformSet as $id => $name) {
            $safeItems['platform'][$id] = htmlspecialchars($name);
        }
    }

    return $safeItems;
}

/**
 *
 * @param stdClass $gui
 * @param stdClass $args
 * @param string $media
 */
function createSpreadsheet($gui, $args, $media)
{
    $lbl = initLblSpreadsheet();
    $cellRange = range('A', 'Z');
    $style = initStyleSpreadsheet();

    $objPHPExcel = new PHPExcel();
    $lines2write = xlsStepOne($objPHPExcel, $style, $lbl, $gui);

    // Step 2
    // data is organized with following columns$dataHeader[]
    // Test case
    // [Platform]
    //
    // This is HOW gui->dataSet is organized
    // THIS IS CRITIC ??
    //
    // testTitle PTRJ-76:Create issue tracker - no conflict
    // [platformName]
    //
    $dataHeader = [
        $lbl['title_test_case_title']
    ];
    if (property_exists($gui, 'platformSet') && ! is_null($gui->platformSet) &&
        ! isset($gui->platformSet[0])) {
        $dataHeader[] = $lbl['platform'];
    }

    $startingRow = count($lines2write) + 2;
    $cellArea = "A{$startingRow}:";
    foreach ($dataHeader as $zdx => $field) {
        $cellID = $cellRange[$zdx] . $startingRow;
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellID, $field);
        $cellAreaEnd = $cellRange[$zdx];
    }
    $cellArea .= "{$cellAreaEnd}{$startingRow}";
    $objPHPExcel->getActiveSheet()
        ->getStyle($cellArea)
        ->applyFromArray($style['DataHeader']);

    // Now process data
    $startingRow ++;
    $qta_loops = count($gui->dataSet);
    for ($idx = 0; $idx < $qta_loops; $idx ++) {
        $line2write = $gui->dataSet[$idx];
        $colCounter = 0;
        foreach ($line2write as $field) {
            $cellID = $cellRange[$colCounter] . $startingRow;
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellID,
                html_entity_decode($field));
            $colCounter ++;
        }
        $startingRow ++;
    }

    // Final step
    $objPHPExcel->setActiveSheetIndex(0);

    $xlsType = 'Excel5';
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $xlsType);

    $codex = 'neverRunByPP';
    $tmpfname = tempnam(config_get('temp_dir'), "{$codex}.tmp");
    $objWriter->save($tmpfname);

    if ($args->getSpreadsheetBy == 'email') {
        require_once 'email_api.php';

        $ema = new stdClass();
        $ema->from_address = config_get('from_email');
        $ema->to_address = $args->user->emailAddress;
        $ema->subject = $gui->mailCfg->subject;
        $ema->message = $gui->mailCfg->subject;

        $dum = uniqid("{$codex_}") . '.xls';
        $oops = [
            'attachment' => [
                'file' => $tmpfname,
                'newname' => $dum
            ],
            'exit_on_error' => true,
            'htmlFormat' => true
        ];
        $email_op = email_send_wrapper($ema, $oops);
        unlink($tmpfname);
        exit();
    }
    downloadXls($tmpfname, $xlsType, $gui, "{$codex_}");
}

/**
 *
 * @param database $dbh
 * @param stdClass $args
 * @param stdClass $gui
 * @return array
 */
function getMetrics(&$dbh, &$args, &$gui)
{
    $metricsMgr = new tlTestPlanMetrics($dbh);

    $met = $metricsMgr->getNeverRunByPlatform($args->tplan_id, $args->platSet);

    $gui->notRunReport = true;
    $gui->info_msg = $gui->labels['info_notrun_tc_report'];
    $gui->notesAccessKey = 'summary';
    $gui->userAccessKey = 'user_id';

    return $met;
}

/**
 */
function initLblSpreadsheet()
{
    return init_labels(
        [
            'title_test_suite_name' => null,
            'platform' => null,
            'build' => null,
            'th_bugs_id_summary' => null,
            'title_test_case_title' => null,
            'version' => null,
            'testproject' => null,
            'generated_by_TestLink_on' => null,
            'testplan' => null,
            'title_execution_notes' => null,
            'th_date' => null,
            'th_run_by' => null,
            'assigned_to' => null,
            'summary' => null
        ]);
}

/**
 *
 * @return array
 */
function initStyleSpreadsheet()
{
    $sty = [];
    $sty['ReportContext'] = [
        'font' => [
            'bold' => true
        ]
    ];
    $sty['DataHeader'] = [
        'font' => [
            'bold' => true
        ],
        'borders' => [
            'outline' => [
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM
            ],
            'vertical' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN
            ]
        ],
        'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'startcolor' => [
                'argb' => 'FF9999FF'
            ]
        ]
    ];

    return $sty;
}

/**
 *
 * @param PHPExcel $oj
 * @param array $style
 * @param array $lbl
 * @param stdClass $gui
 * @return array
 */
function xlsStepOne($oj, $style, $lbl, $gui)
{
    $dummy = '';
    $lines2write = [
        [
            $gui->title,
            ''
        ],
        [
            $lbl['testproject'],
            $gui->tproject_name
        ],
        [
            $lbl['testplan'],
            $gui->tplan_name
        ],
        [
            $lbl['generated_by_TestLink_on'],
            localize_dateOrTimeStamp(null, $dummy, 'timestamp_format', time())
        ],
        [
            $gui->report_context,
            ''
        ]
    ];

    $cellArea = "A1:";
    foreach ($lines2write as $zdx => $fields) {
        $cdx = $zdx + 1;
        $oj->setActiveSheetIndex(0)
            ->setCellValue("A{$cdx}", current($fields))
            ->setCellValue("B{$cdx}", end($fields));
    }
    $cellArea .= "A{$cdx}";
    $oj->getActiveSheet()
        ->getStyle($cellArea)
        ->applyFromArray($style['ReportContext']);

    return $lines2write;
}
