<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\HttpKernelInterface;



$api = $app['controllers_factory'];

// $api->before($checkAPIToken);

$api->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
});

$api->post('/sign-up', function(Request $request) use ($app) {
    //get user details and pass to render

    $errors = array();

    if($request->request->get('username') == null){
        $errors['username'] = 'user name is required';
    }
    if($request->request->get('password') == null){
        $errors['password'] = 'Password is required';
    }else if(strlen($request->request->get('password')) < 6){
        $errors['password'] = 'Password should have at least 6 characters';        
    }
    if($request->request->get('email') == null){
        $errors['email'] = 'Email is required';
    }else if(!preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^",$request->request->get('email'))){
        $errors['email'] = 'Email is invalid';
    }
    if(!empty($errors)){
        return $app->json($errors, 401, array('Content-Type' => 'application/json'));
    }


    $user = array(
        'username' => $request->request->get('username'),
        'password' => md5($request->request->get('password')),
        'email' => $request->request->get('email'),
        'account' => 'free',
        'adults' => 0,
        'children' => 0,
        'goal' => 0,
        'name' => null
    );

    $token = bin2hex(openssl_random_pseudo_bytes(100));

    $user['customerToken'] = $token;

    $user = UserUtils::createUser($app, $user);

    if ($user == "email") {
        return $app->json(array('success'=>false,'email'=>'email already exist'), 404, array('Content-Type' => 'application/json'));
    }else if($user == "username"){
        return $app->json(array('success'=>false,'user_name'=>'Username already exist'), 404, array('Content-Type' => 'application/json'));
    }else {
        $userarray = array(
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'account' => $user->getAccount(),
        );
        return $app->json(array('success'=>true,'user'=>$userarray,'token'=>$user->getCustomerToken()), 200, array('Content-Type' => 'application/json'));
    }
   
});


$api->post('/sign-in', function (Request $request) use ($app) {
    
    $username = $request->request->get('username');
    $password = md5($request->request->get('password'));

    //$user = UserUtils::loginUser($app, $username, $password);

    $query = $app['orm.em']->createQuery('SELECT u FROM Entities\User u where (u.userName = :name OR u.email = :email) and u.password = :password');
        $query->setParameters(array(
            'name' => $username,
            'email' => $username,
            'password' => $password,
        ));
        try {
            $user = $query->getSingleResult();

            $user->setLastLoggedIn(date('Ymd'));
            $user->setTimesLoggedIn($user->getTimesLoggedIn()+1);
            $app['orm.em']->persist($user);
            $app['orm.em']->flush();
            // error_log(print_r($user->getId(), TRUE), 0);
        } catch (Doctrine\ORM\NoResultException $e) {
             return $app->json(array('success'=>false,'message'=>'Invalid credentials!'), 404, array('Content-Type' => 'application/json'));
        }

         $token = bin2hex(openssl_random_pseudo_bytes(100));
         $user->setCustomerToken($token);
         $app['orm.em']->persist($user);
         $app['orm.em']->flush();
        
         $userarray = array(
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'account' => $user->getAccount(),
            'adults' => $user->getAdults(),
            'children' => $user->getChildren(),
            'goal' => $user->getGoal()
        );
        return $app->json(array('success'=>true,'user'=>$userarray,'token'=>$user->getCustomerToken()), 200, array('Content-Type' => 'application/json'));

});



$api->post('/user/update', function (Request $request) use ($app) {

    $errors = array();

    if($request->request->get('adults') == null){
        $errors['adults'] = 'Adults is required';
    }
    if($request->request->get('children') == null){
        $errors['children'] = 'Children is required';
    }
    if($request->request->get('goal') == null){
        $errors['goal'] = 'Goal is required';
    }
    if(!empty($errors)){
        return $app->json($errors, 401, array('Content-Type' => 'application/json'));
    }

    $user =  $app['user']; 

    $user->setAdults($request->request->get('adults'));
    $user->setChildren($request->request->get('children'));
    $user->setGoal($request->request->get('goal'));

    $app['orm.em']->persist($user);
    $app['orm.em']->flush();

    StorageUtils::updateFoodTypesByApi($app);
    SupplyUtils::updateSupplyTypesByApi($app);

    $type = 7;
    $msg = NULL;
    // $successMsg = 'Your settings have been updated.';

    //add a user activity        
    UserUtils::addUserActivity($app, $type, $msg, $user->getId());

        
    return $app->json(array('success'=>true), 200, array('Content-Type' => 'application/json'));
   

})->before($checkAPIToken);


$api->get('/food-details', function (Request $request) use ($app) {

    $user = $app['user']; 
    $foodCategories = StorageUtils::getFoodCategories($app, $user->getId());
    UserUtils::updateUserRank($app, $user->getId());
    
    //get user details and pass to render
    $data = array(
        'totalFoodPercentage' => StorageUtils::getTotalFoodPerc($app, $user->getId()),
        'totalSupplyPercentage' => SupplyUtils::getTotalSupplyPerc($app, $user->getId()),
        'goal' => $user->getGoal(),
        'rankId' => $user->getRank()->getId(),
        'rankName' => $user->getRank()->getRankName(),
        'foodCategories' => $foodCategories
    );
   return $app->json($data,200, array('Content-Type' => 'application/json'));

})->before($checkAPIToken);


$api->post('/add-food', function(Request $request) use ($app) {
    $user = $app['user'];
    UserUtils::updateUserRank($app, $user->getId());
    $data = array(
        'foodTypeId' => $request->request->get('foodTypeId'),
        'addFoodAmount' => $request->request->get('foodAmount'),
        'goal' => $user->getGoal(),
        'rankId' => $user->getRank()->getId(),
        'rankName' => $user->getRank()->getRankName(),        
        'people' => mround($user->getAdults() + ($user->getChildren() / 2), 1), 
    );
    $retValue = StorageUtils::updateFoodTypeByApi($app, $data, 'add'); 

    if($retValue){
        $foodType = $retValue['foodType'];
        $result = array(
        'foodTypeId' => $foodType->getId(),
        'userHas' => $foodType->getUserHas(),
        'categoryPerc' => StorageUtils::updateFoodCategoryPerc($app, $foodType->getFoodCategory()->getId()),
        'categoryId' => $foodType->getFoodCategory()->getId(),
        'totalPerc' => StorageUtils::getTotalFoodPerc($app, $user->getId()),
        'rankId' => $user->getRank()->getId(),
        'rankName' => $user->getRank()->getRankName(),
        'successMsg' => $retValue['successMsg'],
    );
    // error_log(print_r($result->getUserHas(), TRUE), 0)


    if ($result) {
        // return new Response($result, 200);
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return $app->json(array('success'=>false,'message'=>'failure'), 404, array('Content-Type' => 'application/json'));
    }
    }
    return $app->json(array('success'=>false,'message'=>'wrong foodType id'), 404, array('Content-Type' => 'application/json'));
})->before($checkAPIToken);

$api->post('/use-food', function(Request $request) use ($app) {
    $user = $app['user'];    
    UserUtils::updateUserRank($app, $user->getId());
    $data = array(
        'foodTypeId' => $request->request->get('foodTypeId'),
        'useFoodAmount' => $request->request->get('foodAmount'),
        'goal' => $user->getGoal(),
        'rankId' => $user->getRank()->getId(),
        'rankName' => $user->getRank()->getRankName(),        
        'people' => mround($user->getAdults() + ($user->getChildren() / 2), 1), 
    );
    $retValue = StorageUtils::updateFoodTypeByApi($app, $data, 'use'); 
    

    if($retValue){
        $foodType = $retValue['foodType'];
        $result = array(
        'foodTypeId' => $foodType->getId(),
        'userHas' => $foodType->getUserHas(),
        'categoryPerc' => StorageUtils::updateFoodCategoryPerc($app, $foodType->getFoodCategory()->getId()),
        'categoryId' => $foodType->getFoodCategory()->getId(),
        'totalPerc' => StorageUtils::getTotalFoodPerc($app, $user->getId()),
        'rankId' => $user->getRank()->getId(),
        'rankName' => $user->getRank()->getRankName(),
        'successMsg' => $retValue['successMsg'],
    );  
    // error_log(print_r($result->getUserHas(), TRUE), 0);

    if ($result) {
        // return new Response($result, 200);
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return $app->json(array('success'=>false,'message'=>'failure'), 404, array('Content-Type' => 'application/json'));
    }
    }
    return $app->json(array('success'=>false,'message'=>'wrong foodType id'), 404, array('Content-Type' => 'application/json'));

})->before($checkAPIToken);


$api->get('/supply-details', function (Request $request) use ($app) {

    $user = $app['user']; 
    UserUtils::updateUserRank($app, $user->getId());
    // $userActivities = UserUtils::getUserActivities($app, $user->getId());
    $supplyCategories = SupplyUtils::getSupplyCategories($app, $user->getId());
    
    //get user details and pass to render
    $data = array(
        'totalFoodPercentage' => StorageUtils::getTotalFoodPerc($app, $user->getId()),
        'totalSupplyPercentage' => SupplyUtils::getTotalSupplyPerc($app, $user->getId()),
        'goal' => $user->getGoal(),
        'rankId' => $user->getRank()->getId(),
        'rankName' => $user->getRank()->getRankName(),
        //'userActivities' => $userActivities,
        'supplyCategories' => $supplyCategories
    );
   return $app->json($data,200, array('Content-Type' => 'application/json'));

})->before($checkAPIToken);

$api->post('/add-supply', function(Request $request) use ($app) {
    $user = $app['user'];
    UserUtils::updateUserRank($app, $user->getId());

    $data = array(
        'supplyTypeId' => $request->request->get('supplyTypeId'),
        'addSupplyAmount' => $request->request->get('supplyAmount'),
    );

   $retValue = SupplyUtils::updateSupplyTypeByApi($app, $data, 'add'); 

 
    if($retValue){
        $supplyType = $retValue['supplyType'];
        $result = array(
            'supplyTypeId' => $supplyType->getId(),
            'userHas' => $supplyType->getUserHas(),
            'categoryPerc' => SupplyUtils::updateSupplyCategoryPerc($app, $supplyType->getSupplyCategory()->getId()),
            'categoryId' => $supplyType->getSupplyCategory()->getId(),
            'totalPerc' => SupplyUtils::getTotalSupplyPerc($app, $user->getId()),
            'rankId' => $user->getRank()->getId(),
            'rankName' => $user->getRank()->getRankName(),
            'successMsg' => $retValue['successMsg']
        );

        if ($result) {
            // return new Response($result, 200);
            return $app->json($result, 200, array('Content-Type' => 'application/json'));
        } else {
            return $app->json(array('success'=>false,'message'=>'failure'), 404, array('Content-Type' => 'application/json'));
        }
    }
    return $app->json(array('success'=>false,'message'=>'wrong supplyType id'), 404, array('Content-Type' => 'application/json'));

})->before($checkAPIToken);


$api->post('/use-supply', function(Request $request) use ($app) {
    $user = $app['user'];
    UserUtils::updateUserRank($app, $user->getId());

    $data = array(
        'supplyTypeId' => $request->request->get('supplyTypeId'),
        'useSupplyAmount' => $request->request->get('supplyAmount'),
    );

   $retValue = SupplyUtils::updateSupplyTypeByApi($app, $data, 'use'); 

 
    if($retValue){
        $supplyType = $retValue['supplyType'];
        $result = array(
            'supplyTypeId' => $supplyType->getId(),
            'userHas' => $supplyType->getUserHas(),
            'categoryPerc' => SupplyUtils::updateSupplyCategoryPerc($app, $supplyType->getSupplyCategory()->getId()),
            'categoryId' => $supplyType->getSupplyCategory()->getId(),
            'totalPerc' => SupplyUtils::getTotalSupplyPerc($app, $user->getId()),
            'rankId' => $user->getRank()->getId(),
            'rankName' => $user->getRank()->getRankName(),
            'successMsg' => $retValue['successMsg'],
        );

        if ($result) {
            // return new Response($result, 200);
            return $app->json($result, 200, array('Content-Type' => 'application/json'));
        } else {
            return $app->json(array('success'=>false,'message'=>'failure'), 404, array('Content-Type' => 'application/json'));
        }
    }
    return $app->json(array('success'=>false,'message'=>'wrong supplyType id'), 404, array('Content-Type' => 'application/json'));

})->before($checkAPIToken);


$api->post('/create-foodcategory', function(Request $request) use ($app) {

    $user = $app['user'];

    $errors = array();

    if($request->request->get('categoryName') == null){
        $errors['categoryName'] = 'Category Name is required';
    }
    if(!empty($errors)){
        return $app->json($errors, 401, array('Content-Type' => 'application/json'));
    }

    //create new category
    $result = StorageUtils::createFoodCategoryByApi($app, $request->request->get('categoryName'), $app['user']->getId());

    $data = array(
        'account' => $user->getAccount(),
        'foodCategory' => $result['foodCategory'],
    );   

    if ($data) {
        return $app->json($data, 200, array('Content-Type' => 'application/json'));
    } else {
        return $app->json(array('success'=>false,'message'=>'failure'), 404, array('Content-Type' => 'application/json'));
    }
})->before($checkAPIToken);


$api->post('/create-food-type/category/{id}', function(Request $request, $id) use ($app) {
    $user = $app['user'];

    $errors = array();

    if($request->request->get('foodTypeName') == null){
        $errors['foodTypeName'] = 'FoodTypeName is required';
    }
    if($request->request->get('measurementId') == null){
        $errors['measurementId'] = 'MeasurementId is required';
    }
    if($request->request->get('myAmount') == null){
        $errors['myAmount'] = 'MyAmount is required';
    }
    if(!empty($errors)){
        return $app->json($errors, 401, array('Content-Type' => 'application/json'));
    }

    $userdeatils = UserUtils::getUser($app, $app['user']->getId());

    $measurement = $app['orm.em']->find('Entities\Measurements', $request->request->get('measurementId'));
    if($measurement == ''){
        return $app->json(array('measurement'=>'wrong measurement id!'), 401, array('Content-Type' => 'application/json'));
    }

    $data = array(
        'categoryId' => $id,
        'userId' => $app['user']->getId(),
        'foodTypeName' => $request->request->get('foodTypeName'),
        'measurementId' => $request->request->get('measurementId'),
        'myAmount' => $request->request->get('myAmount')
    );

    $result = StorageUtils::createFoodTypeByApi($app, $data);

    $data = array(
        'account' => $userdeatils->getAccount(),
        'foodType' => $result['foodType'],
    );

    if ($data) {
        return $app->json($data, 200, array('Content-Type' => 'application/json'));
    } else {
        return $app->json(array('success'=>false,'message'=>'failure'), 404, array('Content-Type' => 'application/json'));
    }

})->before($checkAPIToken);


$api->post('/create-supply-category', function(Request $request) use ($app) {
    $user = $app['user'];
    $errors = array();
    if($request->request->get('categoryName') == null){
        $errors['categoryName'] = 'Category Name is required';
    }
    if(!empty($errors)){
        return $app->json($errors, 401, array('Content-Type' => 'application/json'));
    }
    //create new category
    $result = SupplyUtils::createSupplyCategoryByApi($app, $request->request->get('categoryName'), $app['user']->getId());

    $data = array(
        'account' => $user->getAccount(),
        'supplyCategory' => $result['supplyCategory'],
    );

    if ($result) {
        return $app->json($data, 200, array('Content-Type' => 'application/json'));
    } else {
        return $app->json(array('success'=>false,'message'=>'failure'), 404, array('Content-Type' => 'application/json'));
    }
})->before($checkAPIToken);


$api->post('/create-supply-type/category/{id}', function(Request $request, $id) use ($app) {
    $user = $app['user'];

    $errors = array();

    if($request->request->get('supplyTypeName') == null){
        $errors['supplyTypeName'] = 'SupplyTypeName is required';
    }
    if($request->request->get('measurementId') == null){
        $errors['measurementId'] = 'MeasurementId is required';
    }
    if($request->request->get('myAmount') == null){
        $errors['myAmount'] = 'MyAmount is required';
    }
    if(!empty($errors)){
        return $app->json($errors, 401, array('Content-Type' => 'application/json'));
    }

    $userdeatils = UserUtils::getUser($app, $app['user']->getId());

    $measurement = $app['orm.em']->find('Entities\Measurements', $request->request->get('measurementId'));
    if($measurement == ''){
        return $app->json(array('measurement'=>'wrong measurement id!'), 401, array('Content-Type' => 'application/json'));
    }

    $data = array(
        'categoryId' => $id,
        'userId' => $app['user']->getId(),
        'supplyTypeName' => $request->request->get('supplyTypeName'),
        'measurementId' => $request->request->get('measurementId'),
        'eachFamilyMember' => $request->request->get('eachFamilyMember'),
        'consumedMonthly' => $request->request->get('consumedMonthly'),
        'myAmount' => $request->request->get('myAmount'),
    );

    $result = SupplyUtils::createSupplyTypeByApi($app, $data);

    $data = array(
        'account' => $user->getAccount(),
        'supplyType' => $result['supplyType'],
    );

    if ($data) {
        return $app->json($data, 200, array('Content-Type' => 'application/json'));
    } else {
        return $app->json(array('success'=>false,'message'=>'failure'), 404, array('Content-Type' => 'application/json'));
    }

})->before($checkAPIToken);


$api->get('/food-category-details', function (Request $request) use ($app) {

    $user = $app['user']; 
    $foodCategories = StorageUtils::getFoodCategories($app, $user->getId());
    UserUtils::updateUserRank($app, $user->getId());
    
    //get food categories details and pass to render
   $data = array('foodCategories' => $foodCategories);
   return $app->json($data,200, array('Content-Type' => 'application/json'));

})->before($checkAPIToken);


$api->get('/supply-category-details', function (Request $request) use ($app) {

    $user = $app['user'];
    $supplyCategories = SupplyUtils::getSupplyCategories($app, $user->getId());     
    UserUtils::updateUserRank($app, $user->getId());
    
    //get category details and pass to render
    $data = array('supplyCategories' => $supplyCategories);
    return $app->json($data,200, array('Content-Type' => 'application/json'));

})->before($checkAPIToken);



return $api;
?>