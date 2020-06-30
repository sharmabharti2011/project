<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

$supply = $app['controllers_factory'];

$supply->before($maintainSession);
$supply->after($mustBeLoggedInAndValid);

// error_log(print_r($result, TRUE), 0);
$supply->get('/', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    UserUtils::updateUserRank($app, $user->getId());
    $userActivities = UserUtils::getUserActivities($app, $user->getId());
    $supplyCategories = SupplyUtils::getSupplyCategories($app, $user->getId());

    //get user details and pass to render
    $data = array(
        'account' => $user->getAccount(),
        'admin' => $user->getAdmin(),
        'totalFoodPercentage' => StorageUtils::getTotalFoodPerc($app, $user->getId()),
        'totalSupplyPercentage' => SupplyUtils::getTotalSupplyPerc($app, $user->getId()),
        'goal' => $user->getGoal(),
        'rankId' => $user->getRank()->getId(),
        'rankName' => $user->getRank()->getRankName(),
        'rankImg' => $user->getRank()->getRankImg(),
        'userActivities' => $userActivities,
        'supplyCategories' => $supplyCategories,
        'successMsg' => $app['session']->get('successMsg'),
    );
    if (!is_null($app['session']->get('successMsg'))) {
        $app['session']->set('successMsg', null);
    }
    // var_dump($data);
    return $app['twig']->render('dashboard/supply/supply.html', $data);
});

$supply->get('/add-supply', function() use ($app) {
    $supplyCategories = SupplyUtils::getSupplyCategories($app, $app['session']->get('userId'));
    $data = array(
        'supplyCategories' => $supplyCategories,
        'supplyTypeId' => '',
        'consumedMonthly' => '',
    );
    return $app['twig']->render('dashboard/supply/add-supply.html', $data);
});

$supply->post('/add-supply', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'supplyTypeId' => $request->request->get('supplyTypeId'),
        'addSupplyAmount' => $request->request->get('supplyAmount'),
    );
    $retValue = SupplyUtils::updateSupplyType($app, $data, 'add'); 
    $supplyType = $retValue['supplyType'];
    $result = array(
        'supplyTypeId' => $supplyType->getId(),
        'userHas' => $supplyType->getUserHas(),
        'categoryPerc' => SupplyUtils::updateSupplyCategoryPerc($app, $supplyType->getSupplyCategory()->getId()),
        'categoryId' => $supplyType->getSupplyCategory()->getId(),
        'totalPerc' => SupplyUtils::getTotalSupplyPerc($app, $user->getId()),
        'successMsg' => $retValue['successMsg'],
    );
    // error_log(print_r($result->getUserHas(), TRUE), 0);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$supply->get('/add-to-supply/{id}', function($id) use ($app) {
    $supplyType = SupplyUtils::getSupplyType($app, $id); 
    $data = array(
        'supplyCategories' => '',
        'supplyTypeId' => $id,
        'supplyType' => $supplyType->getSupplyType(),
        'measurement' => $supplyType->getMeasurement()->getMeasurement(),
        'consumedMonthly' => $supplyType->getConsumedMonthly(),
    );
    return $app['twig']->render('dashboard/supply/add-supply.html', $data);
});

$supply->get('/use-supply', function() use ($app) {
    $supplyCategories = SupplyUtils::getSupplyCategories($app, $app['session']->get('userId'));
    $data = array(
        'supplyCategories' => $supplyCategories,
        'supplyTypeId' => '',
        'consumedMonthly' => '',
    );
    return $app['twig']->render('dashboard/supply/use-supply.html', $data);
});

$supply->post('/use-supply', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'supplyTypeId' => $request->request->get('supplyTypeId'),
        'useSupplyAmount' => $request->request->get('supplyAmount'),
    );
    $retValue = SupplyUtils::updateSupplyType($app, $data, 'use'); 
    $supplyType = $retValue['supplyType'];
    $result = array(
        'supplyTypeId' => $supplyType->getId(),
        'userHas' => $supplyType->getUserHas(),
        'categoryPerc' => SupplyUtils::updateSupplyCategoryPerc($app, $supplyType->getSupplyCategory()->getId()),
        'categoryId' => $supplyType->getSupplyCategory()->getId(),
        'totalPerc' => SupplyUtils::getTotalSupplyPerc($app, $user->getId()),
        'successMsg' => $retValue['successMsg'],
    );
    // error_log(print_r($result->getUserHas(), TRUE), 0);

    if ($result) {
        // return new Response($result, 200);
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$supply->get('/use-from-supply/{id}', function($id) use ($app) {
    $supplyType = SupplyUtils::getSupplyType($app, $id); 
    $data = array(
        'supplyCategories' => '',
        'supplyTypeId' => $id,
        'supplyType' => $supplyType->getSupplyType(),
        'measurement' => $supplyType->getMeasurement()->getMeasurement(),
        'consumedMonthly' => $supplyType->getConsumedMonthly(),
    );
    return $app['twig']->render('dashboard/supply/use-supply.html', $data);
});

$supply->post('/change-supply', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'supplyTypeId' => $request->request->get('foodTypeId'),
        'supplyType' => $request->request->get('foodType'),
        'supplyAmount' => $request->request->get('foodAmount'),
        'needAmount' => $request->request->get('needAmount'),
    );
    $retValue = SupplyUtils::updateSupplyType($app, $data, 'amount'); 
    $supplyType = $retValue['supplyType'];
    $result = array(
        'foodTypeId' => $supplyType->getId(),
        'foodType' => $supplyType->getsupplyType(),
        'userHas' => $supplyType->getUserHas(),
        'userNeeds' => $supplyType->getUserNeeds(),
        'categoryPerc' => SupplyUtils::updateSupplyCategoryPerc($app, $supplyType->getSupplyCategory()->getId()),
        'categoryId' => $supplyType->getSupplyCategory()->getId(),
        'totalPerc' => SupplyUtils::getTotalSupplyPerc($app, $user->getId()),
        'successMsg' => $retValue['successMsg'],
    );
    // error_log(print_r($result->getUserHas(), TRUE), 0);

    if ($result) {
        // return new Response($result, 200);
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$supply->post('/supply-types', function(Request $request) use ($app) {
    $supplyCategoryId = $request->request->get('supplyCategoryId');
    $supplyTypes = SupplyUtils::getSupplyTypes($app, $supplyCategoryId);
    $html = '';
    foreach($supplyTypes as $supplyType) {
        $html .= '<option value="'.$supplyType['id'].'">'.$supplyType['supplyType'].'</option>\n';
    }
    return new Response($html, 200);
});

$supply->post('/measurement', function(Request $request) use ($app) {
    $supplyTypeId = $request->request->get('supplyTypeId');
    $measurement = SupplyUtils::getMeasurement($app, $supplyTypeId);
    return new Response($measurement['measurement'], 200);
});

$supply->get('/create-supply-category', function() use ($app) {
    return $app['twig']->render('dashboard/supply/create-supply-category.html');
});

$supply->post('/create-supply-category', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    //create new category
    $result = SupplyUtils::createSupplyCategory($app, $request->request->get('categoryName'), $app['session']->get('userId'));
    $result['supplyCategory']['supplyTypes'] = array();
    if (!is_null($result['successMsg'])) {
        $app['session']->set('successMsg', $result['successMsg']);
    }
    $data = array(
        'account' => $user->getAccount(),
        'supplyCategory' => $result['supplyCategory'],
    );    
    $result['supplyCategoryHtml'] = $app['twig']->render('dashboard/supply/supply-category.html', $data);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$supply->post('/edit-category', function(Request $request) use ($app) {
    $id = $request->request->get('categoryId');
    $val = $request->request->get('category');
    // error_log(print_r($request->request->get('value'), TRUE), 0);
    $result = SupplyUtils::updateSupplyCategory($app, $id, $val);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$supply->get('/delete-supply-category/{id}', function($id) use ($app) {
    $category = SupplyUtils::getSupplyCategory($app, $id);
    $data = array(
        'categoryName' => $category->getSupplyCategory(),
        'categoryId' => $id
    );
    return $app['twig']->render('dashboard/supply/delete-supply-category.html', $data);
});

$supply->post('/delete-supply-category/{id}', function($id) use ($app) {
    $result = SupplyUtils::deleteSupplyCategory($app, $id, $app['session']->get('userId'));
    if (!is_null($result['successMsg'])) {
        $app['session']->set('successMsg', $result['successMsg']);
    }
    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$supply->get('/create-supply-type/category/{id}', function($id) use ($app) {
    $measurements = StorageUtils::getMeasurements($app);
    $data = array(
        'categoryId' => $id,
        'measurements' => $measurements,
    );
    return $app['twig']->render('dashboard/supply/create-supply-type.html', $data);
});

$supply->post('/create-supply-type/category/{id}', function(Request $request, $id) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'categoryId' => $id,
        'userId' => $app['session']->get('userId'),
        'supplyTypeName' => $request->request->get('supplyTypeName'),
        'measurementId' => $request->request->get('measurementId'),
        'eachFamilyMember' => $request->request->get('eachFamilyMember'),
        'consumedMonthly' => $request->request->get('consumedMonthly'),
        'myAmount' => $request->request->get('myAmount'),
    );
    $result = SupplyUtils::createSupplyType($app, $data);
    // error_log(print_r($result['foodType'], TRUE), 0);
    $data = array(
        'account' => $user->getAccount(),
        'supplyType' => $result['supplyType'],
    );    
    $result['supplyTypeHtml'] = $app['twig']->render('dashboard/supply/supply-type.html', $data);
    // error_log(print_r($result, TRUE), 0);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$supply->get('/delete-supply-type/{id}', function($id) use ($app) {
    $type = SupplyUtils::getSupplyType($app, $id);
    $data = array(
        'supplyTypeName' => $type->getSupplyType(),
        'supplyTypeId' => $id
    );
    return $app['twig']->render('dashboard/supply/delete-supply-type.html', $data);
});

$supply->post('/delete-supply-type/{id}', function($id) use ($app) {
    $result = SupplyUtils::deleteSupplyType($app, $id);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

return $supply;
?>