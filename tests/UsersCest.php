<?php

class UsersCest
{
    public function _before(ApiTester $I)
    {
        $I->amHttpAuthenticated('apitester', 'apitester');
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
