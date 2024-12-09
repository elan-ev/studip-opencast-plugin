<?php

class UsersCest
{
    private $dozent_name;

    public function _before(ApiTester $I)
    {
        $config = $I->getConfig();

        $this->dozent_name = $config['dozent_name'];

        $I->amHttpAuthenticated($config['dozent_name'], $config['dozent_password']);
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
                'username' => $this->dozent_name,
            ]
        ]);
    }


}
