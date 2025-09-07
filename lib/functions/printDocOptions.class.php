<?php

/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 */
class printDocOptions
{

    protected $doc;

    protected $reqSpec;

    protected $testSpec;

    protected $exec;

    /**
     */
    public function __construct()
    {
        $this->doc = [];

        // element format
        //
        // 'value' => 'toc','description' => 'opt_show_toc','checked' => 'n'
        // 'value': will be used to get the value
        // 'description': label id, to be used for localization
        //
        // if checked is not present => 'checked' => 'n'
        $this->doc[] = [
            'value' => 'toc',
            'description' => 'opt_show_toc'
        ];
        $this->doc[] = [
            'value' => 'headerNumbering',
            'description' => 'opt_show_hdrNumbering'
        ];

        // Specific for Documents regarding Requirement Specifications
        $this->reqSpec = [];
        $key2init = [
            'req_spec_scope',
            'req_spec_author',
            'req_spec_overwritten_count_reqs',
            'req_spec_type',
            'req_spec_cf',
            'req_scope',
            'req_author',
            'req_status',
            'req_type',
            'req_cf',
            'req_relations',
            'req_linked_tcs',
            'req_coverage',
            'displayVersion'
        ];

        foreach ($key2init as $key) {
            $yn = isset($key2init2yes[$key]) ? $key2init2yes[$key] : 'n';
            $this->reqSpec[] = [
                'value' => $key,
                'checked' => $yn,
                'description' => 'opt_' . $key
            ];
        }

        $this->testSpec = [];
        $this->testSpec[] = [
            'value' => 'header',
            'description' => 'opt_show_suite_txt'
        ];
        $this->testSpec[] = [
            'value' => 'summary',
            'description' => 'opt_show_tc_summary',
            'checked' => 'y'
        ];
        $this->testSpec[] = [
            'value' => 'body',
            'description' => 'opt_show_tc_body'
        ];
        $this->testSpec[] = [
            'value' => 'author',
            'description' => 'opt_show_tc_author'
        ];
        $this->testSpec[] = [
            'value' => 'keyword',
            'description' => 'opt_show_tc_keys'
        ];
        $this->testSpec[] = [
            'value' => 'cfields',
            'description' => 'opt_show_cfields'
        ];
        $this->testSpec[] = [
            'value' => 'requirement',
            'description' => 'opt_show_tc_reqs'
        ];

        $this->exec = [];
        $this->exec[] = [
            'value' => 'execResultsByCFOnExecCombination',
            'description' => 'opt_cfexec_comb'
        ];

        $this->exec[] = [
            'value' => 'notes',
            'description' => 'opt_show_tc_notes'
        ];

        $this->exec[] = [
            'value' => 'step_exec_notes',
            'description' => 'opt_show_tcstep_exec_notes'
        ];

        $this->exec[] = [
            'value' => 'passfail',
            'description' => 'opt_show_passfail',
            'checked' => 'y'
        ];

        $this->exec[] = [
            'value' => 'step_exec_status',
            'description' => 'opt_show_tcstep_exec_status',
            'checked' => 'y'
        ];

        $this->exec[] = [
            'value' => 'build_cfields',
            'description' => 'opt_show_build_cfields',
            'checked' => 'n'
        ];
        $this->exec[] = [
            'value' => 'metrics',
            'description' => 'opt_show_metrics'
        ];
    }

    /**
     */
    public function getDocOpt()
    {
        return $this->doc;
    }

    /**
     */
    public function getTestSpecOpt()
    {
        return $this->testSpec;
    }

    /**
     */
    public function getReqSpecOpt()
    {
        return $this->reqSpec;
    }

    /**
     */
    public function getExecOpt()
    {
        return $this->exec;
    }

    /**
     */
    public function getAllOptVars()
    {
        $ov = [];
        $prop = [
            'doc',
            'testSpec',
            'reqSpec',
            'exec'
        ];
        foreach ($prop as $pp) {
            foreach ($this->$pp as $ele) {
                $ov[$ele['value']] = isset($ele['checked']) ? $ele['checked'] : 'n';
                $ov[$ele['value']] = ($ov[$ele['value']] == 'y') ? 1 : 0;
            }
        }

        return $ov;
    }

    /**
     */
    public function getJSPrintPreferences()
    {
        $ov = [];
        $prop = [
            "doc",
            "testSpec",
            "reqSpec",
            "exec"
        ];
        foreach ($prop as $pp) {
            foreach ($this->$pp as $ele) {
                $ov[] = $ele['value'];
            }
        }
        return implode(',', $ov);
    }
}
