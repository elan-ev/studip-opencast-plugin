<?php

class UsersCest
{
    public function _before(ApiTester $I)
    {
        $config = $I->getConfig();
        $I->amHttpAuthenticated($config['user'], $config['password']);
    }

    // tests
    public function testUserRoute(ApiTester $I)
    {
        $response = $I->sendGet('/user', [ 'status' => 'pending' ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'type' => 'user',
            'data' => [
                'username' => 'apitester'
            ]
        ]);
    }


}
