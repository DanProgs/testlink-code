<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * @filesource  keywordsExport.php
 * @package     TestLink
 * @copyright   2005,2019 TestLink community
 * @link        http://www.testlink.org/
 *
 */
require_once '../../config.inc.php';
require_once 'common.php';
require_once 'csv.inc.php';
require_once 'xml.inc.php';
require_once 'keywordsEnv.php';

testlinkInitPage($db);
$templateCfg = templateConfiguration();
$args = initArgs($db);
$gui = initializeGui($args);

if ($args->doAction === "do_export") {
    do_export($db, $smarty, $args);
}

$smarty = new TLSmarty();
$smarty->assign('gui', $gui);
$smarty->display($templateCfg->template_dir . $templateCfg->default_template);

/**
 * Get input from user and return it in some sort of namespace
 *
 * @param database $dbHandler
 * @return stdClass object returns the arguments for the page
 */
function initArgs(&$dbHandler)
{
    $ipcfg = [
        "doAction" => [
            "GET",
            tlInputParameter::STRING_N,
            0,
            50
        ],
        "tproject_id" => [
            "GET",
            tlInputParameter::INT_N
        ],
        "export_filename" => [
            "POST",
            tlInputParameter::STRING_N,
            0,
            255
        ],
        "exportType" => [
            "POST",
            tlInputParameter::STRING_N,
            0,
            255
        ]
    ];

    $args = new stdClass();
    I_PARAMS($ipcfg, $args);

    if ($args->tproject_id <= 0) {
        throw new Exception("Error Invalid Test Project ID", 1);
    }

    // Check rights before doing anything else
    // Abort if rights are not enough
    $args->user = $_SESSION['currentUser'];
    $env['tproject_id'] = $args->tproject_id;
    $env['tplan_id'] = 0;

    $check = new stdClass();
    $check->items = [
        'mgt_view_key'
    ];
    $check->mode = 'and';
    checkAccess($dbHandler, $args->user, $env, $check);

    $tproj_mgr = new testproject($dbHandler);
    $dm = $tproj_mgr->get_by_id($args->tproject_id, [
        'output' => 'name'
    ]);
    $args->tproject_name = $dm['name'];

    return $args;
}

/**
 * do_export
 * generate export file
 *
 * @param database $db
 * @param TLSmarty $smarty
 * @param stdClass $args
 */
function do_export(&$db, &$smarty, &$args)
{
    $pfn = null;
    $pfx = null;
    switch ($args->exportType) {
        case 'iSerializationToCSV':
            $pfn = null;
            $pfx = "exportKeywordsToCSV";
            break;

        case 'iSerializationToXML':
            $pfn = "exportKeywordsToXML";
            break;
    }

    if (null != $pfn) {
        $tprojectMgr = new testproject($db);
        $content = $tprojectMgr->$pfn($args->tproject_id);
        downloadContentsToFile($content, $args->export_filename);
        exit();
    }

    if (null != $pfx) {
        $cu = getKeywordsEnv($db, $args->user, $args->tproject_id,
            [
                'usage' => 'csvExport'
            ]);

        $content = exportKeywordsToCSV($cu->kwOnTCV);
        downloadContentsToFile($content, $args->export_filename);
        exit();
    }
}

/**
 * Initialisiert die GUI
 *
 * @param stdClass $argsObj
 * @return stdClass
 */
function initializeGui(&$argsObj)
{
    $kw = new tlKeyword();
    $gui = new stdClass();
    $gui->tproject_id = $argsObj->tproject_id;
    $gui->exportTypes = $kw->getSupportedSerializationInterfaces();
    $gui->main_descr = lang_get('testproject') . TITLE_SEP .
        $argsObj->tproject_name;
    $gui->export_filename = is_null($argsObj->export_filename) ? 'keywords.xml' : $argsObj->export_filename;
    $gui->action_descr = lang_get('export_keywords');

    $gui->actionUrl = "lib/keywords/keywordsExport.php?doAction=do_export&tproject_id={$gui->tproject_id}";
    $gui->cancelUrl = "lib/keywords/keywordsView.php?tproject_id={$gui->tproject_id}";
    return $gui;
}

/**
 * Export keywords to CSV
 *
 * @param array $kwSet
 * @return string in csv format
 */
function exportKeywordsToCSV($kwSet)
{
    $keys = [
        "keyword",
        "notes",
        "tcv_qty"
    ];
    return exportDataToCSV($kwSet, $keys, $keys, [
        'addHeader' => 1
    ]);
}
