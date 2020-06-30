<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

$dashboard = $app['controllers_factory'];

$dashboard->before($maintainSession);
$dashboard->after($mustBeLoggedInAndValid);

$dashboard->get('/', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    UserUtils::updateUserRank($app, $user->getId());
    $userActivities = UserUtils::getUserActivities($app, $user->getId());
    $foodCategories = StorageUtils::getFoodCategories($app, $user->getId());
    
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
        'foodCategories' => $foodCategories,
        'successMsg' => $app['session']->get('successMsg'),
    );
    if (!is_null($app['session']->get('successMsg'))) {        
        $app['session']->set('successMsg', null);
    }
    return $app['twig']->render('dashboard/home.html', $data);
});

$dashboard->get('/get_percentage', function() use ($app) {
     
    $query = $app['orm.em']->createQuery("SELECT u FROM Entities\User u");
    $users = $query->getResult();
    //count($users);
    $url = 'https://api.sendgrid.com/';
    $sendgrid_user='jasonbarron';
	$sendgrid_apikey='D3v3lopm3nt!';
	$template_id = 'd764c993-566f-4843-af5c-b456c1a93a8f';
    foreach($users as $user) {

        $percentage = StorageUtils::getTotalFoodPerc($app, $user->getId()).'%';
        $email = $user->getEmail();
        $goal = $user->getGoal();
        $full_name = $user->getName();
        $exp=explode(' ', $full_name);
        $name=$exp[0];
        if($percentage==0){
			$custom_message='Looks like you need some work on your food storage!';
		}else if($percentage > 0 && $percentage <= 25){
			$custom_message='You have a good start, keep going!';
		}else if($percentage > 25 && $percentage <= 50){
			$custom_message='You’re almost halfway there';
		}else if($percentage > 50 && $percentage <= 75){
			$custom_message='Keep going, you are almost there';
		}else if($percentage > 75 && $percentage <= 99){
			$custom_message='You are so close, you got this!';
		}

        $js = array(
					'sub' => array(':Firstname' => array(ucwords($name)),
					  			':custom_message' => array($custom_message),
					  			':percentage' => array($percentage),
					  			':months_goal'=> array($goal)),
					'filters' => array('templates' => array('settings' => array('enable' => 1, 'template_id' => $template_id)))
					);

        $params = array(
						'api_user'  => $sendgrid_user,
						'api_key'   => $sendgrid_apikey,
					    'to'        => $email,
					    'toname'    => $name,
					    'from'      => "info@stockupfood.com",
					    'fromname'  => "stockupfood",
					    'subject'   => " ",
					    'text'      => " ",
					    'html'      => json_encode($js),
					    'x-smtpapi' => json_encode($js),
					  );

        $request =  $url.'api/mail.send.json';
		$param = http_build_query($params);
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.sendgrid.com/api/mail.send.json",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $param,
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "content-type: application/x-www-form-urlencoded"
		    ),
		));

		$response = curl_exec($curl);

		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  echo $response;
		  
		}

	}
	die();
  
});

$dashboard->get('/resources', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'admin' => $user->getAdmin(),
    );
    return $app['twig']->render('dashboard/resources.html', $data);
});

$dashboard->get('/admin', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $numPaid = UserUtils::getPaidUsers($app);
    $numUsers = UserUtils::getTotalUsers($app);
    $data = array(
        'admin' => $user->getAdmin(),
        'numPaid' => $numPaid,
        'numUsers' => $numUsers,
    );
    return $app['twig']->render('dashboard/admin.html', $data);
});

$dashboard->get('/add-food', function() use ($app) {
    $foodCategories = StorageUtils::getFoodCategories($app, $app['session']->get('userId'));
    $data = array(
        'foodCategories' => $foodCategories,
        'foodTypeId' => '',
    );
    return $app['twig']->render('dashboard/storage/add-food.html', $data);
});

$dashboard->post('/add-food', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'foodTypeId' => $request->request->get('foodTypeId'),
        'addFoodAmount' => $request->request->get('foodAmount'),
        'goal' => $user->getGoal(),        
        'people' => mround($user->getAdults() + ($user->getChildren() / 2), 1), 
    );
    $retValue = StorageUtils::updateFoodType($app, $data, 'add'); 
    $foodType = $retValue['foodType'];
    $result = array(
        'foodTypeId' => $foodType->getId(),
        'userHas' => $foodType->getUserHas(),
        'categoryPerc' => StorageUtils::updateFoodCategoryPerc($app, $foodType->getFoodCategory()->getId()),
        'categoryId' => $foodType->getFoodCategory()->getId(),
        'totalPerc' => StorageUtils::getTotalFoodPerc($app, $user->getId()),
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

$dashboard->get('/add-to-food/{id}', function($id) use ($app) {
    $foodType = StorageUtils::getFoodType($app, $id); 
    $data = array(
        'foodCategories' => '',
        'foodTypeId' => $id,
        'foodType' => $foodType->getFoodType(),
    );
    return $app['twig']->render('dashboard/storage/add-food.html', $data);
});

$dashboard->get('/use-food', function() use ($app) {
    $foodCategories = StorageUtils::getFoodCategories($app, $app['session']->get('userId'));
    $data = array(
        'foodCategories' => $foodCategories,
        'foodTypeId' => '',
    );
    return $app['twig']->render('dashboard/storage/use-food.html', $data);
});

$dashboard->post('/use-food', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'foodTypeId' => $request->request->get('foodTypeId'),
        'useFoodAmount' => $request->request->get('foodAmount'),
        'goal' => $user->getGoal(),        
        'people' => mround($user->getAdults() + ($user->getChildren() / 2), 1), 
    );
    $retValue = StorageUtils::updateFoodType($app, $data, 'use'); 
    $foodType = $retValue['foodType'];
    $result = array(
        'foodTypeId' => $foodType->getId(),
        'userHas' => $foodType->getUserHas(),
        'categoryPerc' => StorageUtils::updateFoodCategoryPerc($app, $foodType->getFoodCategory()->getId()),
        'categoryId' => $foodType->getFoodCategory()->getId(),
        'totalPerc' => StorageUtils::getTotalFoodPerc($app, $user->getId()),
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

$dashboard->get('/use-from-food/{id}', function($id) use ($app) {
    $foodType = StorageUtils::getFoodType($app, $id); 
    $data = array(
        'foodCategories' => '',
        'foodTypeId' => $id,
        'foodType' => $foodType->getFoodType(),
    );
    return $app['twig']->render('dashboard/storage/use-food.html', $data);
});

$dashboard->post('/change-food', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'foodTypeId' => $request->request->get('foodTypeId'),
        'foodType' => $request->request->get('foodType'),
        'foodAmount' => $request->request->get('foodAmount'),
        'needAmount' => $request->request->get('needAmount'),
        'goal' => $user->getGoal(),        
        'people' => mround($user->getAdults() + ($user->getChildren() / 2), 1), 
    );
    $retValue = StorageUtils::updateFoodType($app, $data, 'amount'); 
    $foodType = $retValue['foodType'];
    $result = array(
        'foodTypeId' => $foodType->getId(),
        'foodType' => $foodType->getFoodType(),
        'userHas' => $foodType->getUserHas(),
        'userNeeds' => mround($foodType->getOneAdultNeedsMonth() * $data['goal'] * $data['people']),
        'categoryPerc' => StorageUtils::updateFoodCategoryPerc($app, $foodType->getFoodCategory()->getId()),
        'categoryId' => $foodType->getFoodCategory()->getId(),
        'totalPerc' => StorageUtils::getTotalFoodPerc($app, $user->getId()),
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

$dashboard->post('/food-types', function(Request $request) use ($app) {
    $foodCategoryId = $request->request->get('foodCategoryId');
    $foodTypes = StorageUtils::getFoodTypes($app, $foodCategoryId);
    $html = '';
    foreach($foodTypes as $foodType) {
        $html .= '<option value="'.$foodType['id'].'">'.$foodType['foodType'].'</option>\n';
    }
    return new Response($html, 200);
});

$dashboard->post('/measurement', function(Request $request) use ($app) {
    $foodTypeId = $request->request->get('foodTypeId');
    $measurement = StorageUtils::getMeasurement($app, $foodTypeId);
    return new Response($measurement['measurementName'], 200);
});

$dashboard->get('/create-food-category', function() use ($app) {
    return $app['twig']->render('dashboard/storage/create-food-category.html');
});

$dashboard->post('/create-food-category', function(Request $request) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    //create new category
    $result = StorageUtils::createFoodCategory($app, $request->request->get('categoryName'), $app['session']->get('userId'));
    $result['foodCategory']['foodTypes'] = array();
    if (!is_null($result['successMsg'])) {
        $app['session']->set('successMsg', $result['successMsg']);
    }
    $data = array(
        'account' => $user->getAccount(),
        'foodCategory' => $result['foodCategory'],
    );    
    $result['foodCategoryHtml'] = $app['twig']->render('dashboard/storage/food-category.html', $data);
    // error_log(print_r($result, TRUE), 0);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$dashboard->post('/edit-category', function(Request $request) use ($app) {
    $id = $request->request->get('categoryId');
    $val = $request->request->get('category');
    // error_log(print_r($request->request->get('value'), TRUE), 0);
    $result = StorageUtils::updateFoodCategory($app, $id, $val);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$dashboard->get('/delete-food-category/{id}', function($id) use ($app) {
    $category = StorageUtils::getFoodCategory($app, $id);
    $data = array(
        'categoryName' => $category->getFoodCategory(),
        'categoryId' => $id
    );
    return $app['twig']->render('dashboard/storage/delete-food-category.html', $data);
});

$dashboard->post('/delete-food-category/{id}', function($id) use ($app) {
    $result = StorageUtils::deleteFoodCategory($app, $id, $app['session']->get('userId'));
    if (!is_null($result['successMsg'])) {
        $app['session']->set('successMsg', $result['successMsg']);
    }
    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$dashboard->get('/create-food-type/category/{id}', function($id) use ($app) {
    $measurements = StorageUtils::getMeasurements($app);
    $data = array(
        'categoryId' => $id,
        'measurements' => $measurements,
    );
    return $app['twig']->render('dashboard/storage/create-food-type.html', $data);
});

$dashboard->post('/create-food-type/category/{id}', function(Request $request, $id) use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $data = array(
        'categoryId' => $id,
        'userId' => $app['session']->get('userId'),
        'foodTypeName' => $request->request->get('foodTypeName'),
        'measurementId' => $request->request->get('measurementId'),
        'myAmount' => $request->request->get('myAmount'),
    );
    $result = StorageUtils::createFoodType($app, $data);
    // error_log(print_r($result['foodType'], TRUE), 0);
    $data = array(
        'account' => $user->getAccount(),
        'foodType' => $result['foodType'],
    );    
    $result['foodTypeHtml'] = $app['twig']->render('dashboard/storage/food-type.html', $data);
    // error_log(print_r($result, TRUE), 0);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$dashboard->get('/delete-food-type/{id}', function($id) use ($app) {
    $type = StorageUtils::getFoodType($app, $id);
    $data = array(
        'foodTypeName' => $type->getFoodType(),
        'foodTypeId' => $id
    );
    return $app['twig']->render('dashboard/storage/delete-food-type.html', $data);
});

$dashboard->post('/delete-food-type/{id}', function($id) use ($app) {
    $result = StorageUtils::deleteFoodType($app, $id);

    if ($result) {
        return $app->json($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new Response('failure', 200);
    }
});

$dashboard->get('/refreshActivities', function() use ($app) {
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    $userActivities = UserUtils::getUserActivities($app, $user->getId());
    
    $data = array(
        'userActivities' => $userActivities,
        'userRank' => $user->getRank()->getId(),
    );
    return $app['twig']->render('dashboard/userActivities.html', $data);
});

$dashboard->get('/refreshRank', function() use ($app) {
    UserUtils::updateUserRank($app, $app['session']->get('userId'));
    $user = UserUtils::getUser($app, $app['session']->get('userId'));
    
    $data = array(
        'account' => $user->getAccount(),
        'rankName' => $user->getRank()->getRankName(),
        'rankImg' => $user->getRank()->getRankImg(),
        'rankId' => $user->getRank()->getId(),
    );
    return $app['twig']->render('dashboard/rank.html', $data);
});

return $dashboard;
?>