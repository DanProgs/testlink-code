<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return static function (App $app): void {

    // @20201124 - I do not understand this
    // $app->get('/',World::class . ':hello');

    // using array(), was the way in Slim3 and
    // still seems valid
    $app->get('/whoAmI', [
        $app->restApi,
        'whoAmI'
    ]);

    $app->get('/testprojects', [
        $app->restApi,
        'testprojects'
    ]);
    $app->get('/testprojects/{id}', [
        $app->restApi,
        'testprojects'
    ]);

    $app->get('/testprojects/{id}/testcases',
        [
            $app->restApi,
            'getProjectTestCases'
        ]);
    $app->get('/testprojects/{mixedID}/testplans',
        [
            $app->restApi,
            'getProjectTestPlans'
        ]);

    $app->get('/testplans/{tplanApiKey}/builds',
        [
            $app->restApi,
            'getPlanBuilds'
        ]);

    $app->post('/executions', [
        $app->restApi,
        'createTestCaseExecution'
    ]);

    $app->post('/builds', [
        $app->restApi,
        'createBuild'
    ]);

    $app->post('/keywords', [
        $app->restApi,
        'createKeyword'
    ]);

    $app->post('/testcases', [
        $app->restApi,
        'createTestCase'
    ]);

    $app->post('/testplans', [
        $app->restApi,
        'createTestPlan'
    ]);

    $app->post('/testprojects', [
        $app->restApi,
        'createTestProject'
    ]);

    $app->post('/testsuites', [
        $app->restApi,
        'createTestSuite'
    ]);

    // Update Routes
    // Following advice from
    // https://restfulapi.net/rest-put-vs-post/
    //
    $app->put('/builds/{id}', [
        $app->restApi,
        'updateBuild'
    ]);

    $app->put('/testplans/{id}', [
        $app->restApi,
        'updateTestPlan'
    ]);

    $app->put('/testplans/{tplan_id}/platforms',
        [
            $app->restApi,
            'addPlatformsToTestPlan'
        ]);
};
