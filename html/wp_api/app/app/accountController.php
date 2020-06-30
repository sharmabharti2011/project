<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$account = $app['controllers_factory'];

$account->before($maintainSession);

$account->get('/', function() use ($app) {
    //get user details and pass to render
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'account' => $user->getAccount(),
        'admin' => $user->getAdmin(),
        'adults' => $user->getAdults(),
        'children' => $user->getChildren(),
        'goal' => $user->getGoal()
    );

    return $app['twig']->render('dashboard/settings/settings.html', $data);
})->after($mustBeLoggedInAndValid);

$account->post('/', function(Request $request) use ($app) {
    //get user details and pass to render
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'adults' => $request->request->get('adults'),
        'children' => $request->request->get('children'),
        'goal' => $request->request->get('goal')
    );
    $result = UserUtils::updateUser($app, $user, $data);
    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
})->after($mustBeLoggedInAndValid);

$account->get('/billing', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $plan = UserUtils::getCustomerPlan($app, $user->getCustomerToken());

    if (strlen($user->getCustomerToken()) > 0) {
        $customer = Stripe_Customer::retrieve($user->getCustomerToken());
        $data = array(
            'account' => $user->getAccount(),
            'admin' => $user->getAdmin(),
            'ccType' => $customer->active_card->type,
            'ccNumber' => '************'.$customer->active_card->last4,
            'ccExpDate' => str_pad($customer->active_card->exp_month,2,"0", STR_PAD_LEFT)."/".$customer->active_card->exp_year,
            'plan' => $plan
        );
    } else {
        $customer = UserUtils::getPaypalProfile($app, $user);
        $data = array(
            'account' => $user->getAccount(),
            'admin' => $user->getAdmin(),
            'ccType' => $customer['CREDITCARDTYPE'],
            'ccNumber' => '************'.$customer['ACCT'],
            'ccExpDate' => $customer['EXPDATE'],
            'plan' => $plan
        );
    }    

    return $app['twig']->render('dashboard/settings/billing.html', $data);
})->after($mustBeLoggedInAndValid);

//this route does not have the after filter -- causes too much recursive looping otherwise
$account->get('/update-payment', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    //get user details and pass to render
    if (!is_null($user)) {
        $card = UserUtils::getCustomerCard($app, $user->getCustomerToken());
        $plan = UserUtils::getCustomerPlan($app, $user->getCustomerToken());
        if (!is_null($card)) {
            $data = array(
                'admin' => $user->getAdmin(),
                'cardType' => $card['type'],
                'cardNumber' => '************'.$card['last4'],
                'cardExpMonth' => str_pad($card['exp_month'],2,"0", STR_PAD_LEFT),
                'cardExpYear' => $card['exp_year'],
                'cardCVC' => '',
                'cardName' => $card['name'],
                'cardStreet' => $card['address_line1'],
                'cardCity' => $card['address_city'],
                'cardState' => $card['address_state'],
                'cardZip' => $card['address_zip'],
                'plan' => $plan,
                'successMsg' => '',
            );
        } else {
            $data = array(
                'admin' => $user->getAdmin(),
                'cardType' => '','cardNumber' => '','cardExpMonth' => '','cardExpYear' => '','cardCVC' => '',
                'cardName' => '','cardStreet' => '','cardCity' => '','cardState' => '','cardZip' => '', 'plan' => '',
                'successMsg' => '',
            );
        }
    }
    return $app['twig']->render('dashboard/settings/update-payment.html', $data);
});

//this route does not have the after filter -- causes too much recursive looping otherwise
$account->get('/update-payment/nocard', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    //get user details and pass to render
    if (!is_null($user)) {        
        $data = array(
            'admin' => $user->getAdmin(),
            'cardType' => '','cardNumber' => '','cardExpMonth' => '','cardExpYear' => '','cardCVC' => '',
            'cardName' => '','cardStreet' => '','cardCity' => '','cardState' => '','cardZip' => '',
            'successMsg' => 'It looks like your credit card information no longer exists in the system.<br/> In order to continue keeping track of your food storage, please update<br/> your credit card information below!',
        );
    }
    return $app['twig']->render('dashboard/settings/update-payment.html', $data);
});

//this route does not have the after filter -- causes too much recursive looping otherwise
$account->get('/update-payment/expired', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    //get user details and pass to render
    if (!is_null($user)) {
        $card = UserUtils::getCustomerCard($app, $user->getCustomerToken());
        $data = array(
            'admin' => $user->getAdmin(),
            'cardType' => $card['type'],
            'cardNumber' => '************'.$card['last4'],
            'cardExpMonth' => str_pad($card['exp_month'],2,"0", STR_PAD_LEFT),
            'cardExpYear' => $card['exp_year'],
            'cardCVC' => '',
            'cardName' => $card['name'],
            'cardStreet' => $card['address_line1'],
            'cardCity' => $card['address_city'],
            'cardState' => $card['address_state'],
            'cardZip' => $card['address_zip'],
            'successMsg' => 'It looks like your credit card has expired (or going to expire in the next month).<br/> In order to continue keeping track of your food storage, please update your<br/>credit card information below!',
        );
    }
    return $app['twig']->render('dashboard/settings/update-payment.html', $data);
});

$account->get('/upgrade-account', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    //get user details and pass to render
    if (!is_null($user)) {        
        $data = array(
            'admin' => $user->getAdmin(),
            'cardType' => '','cardNumber' => '','cardExpMonth' => '','cardExpYear' => '','cardCVC' => '',
            'cardName' => '','cardStreet' => '','cardCity' => '','cardState' => '','cardZip' => '', 'plan' => ''
        );
    }
    return $app['twig']->render('dashboard/settings/upgrade-account.html', $data);
})->after($mustBeLoggedInAndValid);

$account->get('/change-plan', function() use ($app) {
    return $app['twig']->render('dashboard/settings/change-plan.html');
})->after($mustBeLoggedInAndValid);

$account->get('/delete-account', function() use ($app) {
    return $app['twig']->render('dashboard/settings/delete-account.html');
})->after($mustBeLoggedInAndValid);

$account->post('/delete-customer', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    //get user details and pass to render
    $result = UserUtils::deleteCustomer($app, $user, false);
    if ($result) {
        return new Response('success', 200);
    } else {
        return new Response('failure', 200);
    }
})->after($mustBeLoggedInAndValid);

$account->delete('/delete-customer', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    //get user details and pass to render
    $result = UserUtils::deleteCustomer($app, $user, true);
    if ($result) {
        return new Response('success', 200);
    } else {
        return new Response('failure', 200);
    }
})->after($mustBeLoggedInAndValid);

$account->post('/remove-customer', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $request->request->get('userId'));
    //get user details and pass to render
    $result = UserUtils::deleteCustomer($app, $user, true);
    
    if ($result) {
        $data = array(
            'successMsg' => "User with ID# ".$request->request->get('userId')." has been removed",
        );        
        return $app->json($data, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
})->after($mustBeLoggedInAndValid);

$account->post('/upgrade-account-friend', function(Request $request) use ($app) {
    UserUtils::addDefaultSupplies($app, $request->request->get('userId'));
    $result = UserUtils::updateAccount($app, $request->request->get('userId'), "friend");
    
    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
})->after($mustBeLoggedInAndValid);

return $account;
?>