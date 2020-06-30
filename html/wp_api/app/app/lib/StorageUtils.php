<?php
use Doctrine\ORM\Query\ResultSetMapping;

if (!class_exists("Entities\User", false)) {
    require_once __DIR__."/../bootstrap_models.php";
}

class StorageUtils {

    public function getTotalFoodPerc($app, $userId) {
        $query = $app['orm.em']->
            createQuery('SELECT sum(fc.percentage), count(fc.id) FROM Entities\Foodcategories fc where fc.fk_userId = :userId')->
            setParameters(array(
                'userId' => $userId,
            ));
        $result = $query->getSingleResult();
        if ($result[2] == 0) {
            $totalPerc = 0;
        } else {
            $totalPerc = round($result[1] / $result[2]);
        }     

        return $totalPerc;
    }

    public function getFoodCategory($app, $categoryId) {
        $foodCategory = $app['orm.em']->find('Entities\Foodcategories', $categoryId);
        return $foodCategory;
    }

    public function getFoodCategories($app, $userId) {
        $user = $app['orm.em']->find('Entities\User', $userId);
        $query = $app['orm.em']->
            createQuery('SELECT fc FROM Entities\Foodcategories fc where fc.fk_userId = :userId ORDER BY fc.id ASC')->
            setParameters(array(
                'userId' => $userId,
            ));
        try {
            $foodCategories = $query->getResult();
            if($user == ''){
                return null;
            }
            $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
            $categoriesArray = array();
            foreach($foodCategories as $foodCategory) 
            {
                // var_dump($userActivity->getActivity()->getClass());
                $array = array();
                $array['id'] = $foodCategory->getId();
                $array['foodCategory'] = $foodCategory->getFoodCategory(); 
                $array['percentage'] = $foodCategory->getPercentage();               
                $query = $app['orm.em']->
                    createQuery('SELECT ft FROM Entities\Foodtypes ft where ft.foodCategory = :categoryId ORDER BY ft.id ASC')->
                    setParameters(array(
                        'categoryId' => $foodCategory->getId(),
                    ));
                $foodTypes = $query->getResult();
                $typesArray = array();
                foreach($foodTypes as $foodType) 
                {
                    $array2 = array();
                    $array2['id'] = $foodType->getId();
                    $array2['foodType'] = $foodType->getFoodType();
                    $array2['userHas'] = round($foodType->getUserHas());
                    $uNeed = $foodType->getOneAdultNeedsMonth() * $needMultiplier;
                    if ($uNeed < 1) {
                        $array2['userNeeds'] = ceil($uNeed);
                    } else {
                        $array2['userNeeds'] = round($uNeed);
                    }                    
                    $array2['measurement'] = $foodType->getMeasurement()->toArray();
                    array_push($typesArray, $array2);
                    // array_push($typesArray, $foodType->toArray());
                }
                $array['foodTypes'] = $typesArray;
                array_push($categoriesArray,$array);
                // array_push($categoriesArray,$foodCategory->toArray());
            }
            // error_log(print_r($userActivities->getId(), TRUE), 0);
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }        
        return $categoriesArray;
    }

    public function createFoodCategory($app, $categoryName, $userId) {
        $foodCategory = new Entities\Foodcategories();
        $foodCategory->setFoodCategory($categoryName);
        $foodCategory->setFkUserId($userId);
        $foodCategory->setPercentage(0);

        $app['orm.em']->persist($foodCategory);
        $app['orm.em']->flush();

        $type = 5;
        $msg = $categoryName." - ";
        $successMsg = 'Your new category "'.$categoryName.'" has been created successfully.';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        
        //send back array of foodCategory object and success message -- and last seven activities? 
        $result = array(
            "foodCategory" => $foodCategory->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }

    public function createFoodCategoryByApi($app, $categoryName, $userId) {
        $foodCategory = new Entities\Foodcategories();
        $foodCategory->setFoodCategory($categoryName);
        $foodCategory->setFkUserId($userId);
        $foodCategory->setPercentage(0);

        $app['orm.em']->persist($foodCategory);
        $app['orm.em']->flush();

        $type = 5;
        $msg = $categoryName." - ";
        $successMsg = 'Your new category "'.$categoryName.'" has been created successfully.';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['user']->getId());
        
        //send back array of foodCategory object and success message -- and last seven activities? 
        $result = array(
            "foodCategory" => $foodCategory->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }

    public function updateFoodCategory($app, $categoryId, $categoryName) {
        $foodCategory = $app['orm.em']->find('Entities\Foodcategories', $categoryId);
        $currentCategoryName = $foodCategory->getFoodCategory();
        $foodCategory->setFoodCategory($categoryName);
        $app['orm.em']->persist($foodCategory);
        $app['orm.em']->flush();

        $type = 15;
        $msg = $currentCategoryName." -> ".$categoryName." - ";
        $successMsg = 'Your category "'.$categoryName.'" has been updated successfully.';
        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));

        $result = array(
            "categoryName" => $categoryName,
            "successMsg" => $successMsg,
        );
        return $result;
    }

    public function deleteFoodCategory($app, $categoryId, $userId) {
        //also delete associated food types
        try {
            $app['orm.em']->createQuery('DELETE Entities\Foodtypes ft where ft.foodCategory = :categoryId')->
                execute(array('categoryId'=>$categoryId));
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }

        $foodCategory = $app['orm.em']->find('Entities\Foodcategories', $categoryId);
        $foodCategoryName = $foodCategory->getFoodCategory();
        $app['orm.em']->remove($foodCategory);
        $app['orm.em']->flush();

        $type = 6;
        $msg = $foodCategoryName." - ";
        $successMsg = 'The category "'.$foodCategoryName.'" has been successfully removed.';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        
        //send back array of foodCategory object and success message -- and last seven activities? 
        $result = array(
            "categoryId" => $categoryId,//$foodType->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }
    
    public function updateFoodPerc($app) {
        $user = $app['orm.em']->find('Entities\User', $app['session']->get('userId'));
        $query = $app['orm.em']->
            createQuery('SELECT fc FROM Entities\Foodcategories fc where fc.fk_userId = :userId')->
            setParameters(array(
                'userId' => $user->getId(),
            ));
        try {
            $foodCategories = $query->getResult();
            foreach($foodCategories as $foodCategory) 
            {
                self::updateFoodCategoryPerc($app, $foodCategory->getId());
            }
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }       
    }

    public function updateFoodPercByApi($app) {
        $user = $app['orm.em']->find('Entities\User', $app['user']->getId());
        $query = $app['orm.em']->
            createQuery('SELECT fc FROM Entities\Foodcategories fc where fc.fk_userId = :userId')->
            setParameters(array(
                'userId' => $user->getId(),
            ));
        try {
            $foodCategories = $query->getResult();
            foreach($foodCategories as $foodCategory) 
            {
                self::updateFoodCategoryPerc($app, $foodCategory->getId());
            }
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }       
    }

    public function updateFoodCategoryPerc($app, $categoryId) {
        $query = $app['orm.em']->
            createQuery('SELECT sum(ft.averageHave), count(ft.id) FROM Entities\Foodtypes ft where ft.foodCategory = :categoryId ORDER BY ft.id ASC')->
            setParameters(array(
                'categoryId' => $categoryId,
            ));
        $result = $query->getSingleResult();
        if ($result[2] == 0) {
            $categoryPerc = 0;
        } else {
            $categoryPerc = round($result[1] / $result[2]);
        }
        
        $foodCategory = $app['orm.em']->find('Entities\Foodcategories', $categoryId);
        $foodCategory->setPercentage($categoryPerc);
        $app['orm.em']->persist($foodCategory);
        $app['orm.em']->flush();

        return $categoryPerc;
    }

    public function getMeasurement($app, $foodTypeId) {
        $foodType = $app['orm.em']->find('Entities\Foodtypes', $foodTypeId);
        $measurement = $foodType->getMeasurement();
        $array = array();
        $array['measurementId'] = $measurement->getId();
        $array['measurementName'] = $measurement->getMeasurement();
        return $array;
    }

    public function getMeasurements($app) {
        $query = $app['orm.em']->
            createQuery('SELECT m FROM Entities\Measurements m');
        $measurements = $query->getResult();
        $measurementsArray = array();
        foreach($measurements as $measurement) 
        {
            $array = array();
            $array['measurementId'] = $measurement->getId();
            $array['measurementName'] = $measurement->getMeasurement();
            array_push($measurementsArray, $array);
        }
        return $measurementsArray;
    }

    public function getFoodType($app, $foodTypeId) {
        $foodType = $app['orm.em']->find('Entities\Foodtypes', $foodTypeId);
        return $foodType;
    }

    public function getFoodTypes($app, $categoryId) {
        $query = $app['orm.em']->
            createQuery('SELECT ft FROM Entities\Foodtypes ft where ft.foodCategory = :categoryId ORDER BY ft.id ASC')->
            setParameters(array(
                'categoryId' => $categoryId,
            ));
        try {                
            $foodTypes = $query->getResult();
            $typesArray = array();
            foreach($foodTypes as $foodType) 
            {
                // error_log(print_r($foodType->getFoodType(), TRUE), 0); 
                $array = array();
                $array['id'] = $foodType->getId();
                $array['foodType'] = $foodType->getFoodType();
                $array['userHas'] = $foodType->getUserHas();
                // $array['userNeeds'] = $foodType->getUserNeeds();
                $array['oneAdultNeedsMonth'] = round($foodType->getOneAdultNeedsMonth(),0);
                $array['measurement'] = $foodType->getMeasurement()->getMeasurement();
                array_push($typesArray, $array);
            }
            // error_log(print_r($userActivities->getId(), TRUE), 0);
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }       
        // error_log(print_r($typesArray, TRUE), 0); 
        return $typesArray;
    }

    public function createFoodType($app, $data) {
        $user = $app['orm.em']->find('Entities\User', $app['session']->get('userId'));
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
        $familySize = $user->getAdults() + ($user->getChildren()/2);
        try {
            $measurement = $app['orm.em']->find('Entities\Measurements', $data['measurementId']);
            $foodCategory = $app['orm.em']->find('Entities\Foodcategories', $data['categoryId']);

            // $amount = $data['myAmount']/$familySize;
            if($familySize >=1){
                $amount = $data['myAmount']/$familySize;
            }else{
                $amount = $data['myAmount'];
            }
            $foodType = new Entities\Foodtypes();
            $foodType->setFoodType($data['foodTypeName']);
            $foodType->setOneAdultNeedsMonth($amount);
            $foodType->setUserHas(0);
            $foodType->setUserNeeds(round($amount * $needMultiplier));
            $foodType->setAverageHave(0);
            $foodType->setMeasurement($measurement);
            $foodType->setFoodCategory($foodCategory);
            $foodType->setFkUserId($data['userId']);

            $app['orm.em']->persist($foodType);
            $app['orm.em']->flush();

            self::updateFoodCategoryPerc($app, $data['categoryId']);

            $type = 3;
            $msg = $data['foodTypeName']." - ";
            $successMsg = $data['foodTypeName'].' has been created successfully';

            //add a user activity        
            UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
            
            //send back array of foodtype object and success message -- and last seven activities? 
            $result = array(
                "foodType" => $foodType->toArray(),
                "successMsg" => $successMsg,
            );
            return $result;
        } catch (Exception $e) {
            throw new Exception();
            return false;
        }
    }

        public function createFoodTypeByApi($app, $data) {
        $user = $app['orm.em']->find('Entities\User', $app['user']->getId());
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
        $familySize = $user->getAdults() + ($user->getChildren()/2);
        try {
            $measurement = $app['orm.em']->find('Entities\Measurements', $data['measurementId']);
            $foodCategory = $app['orm.em']->find('Entities\Foodcategories', $data['categoryId']);

            if($familySize >=1){
            $amount = $data['myAmount']/$familySize;
           }else{
            $amount = $data['myAmount'];
           }
            $foodType = new Entities\Foodtypes();
            $foodType->setFoodType($data['foodTypeName']);
            $foodType->setOneAdultNeedsMonth($amount);
            $foodType->setUserHas(0);
            $foodType->setUserNeeds(round($amount * $needMultiplier));
            $foodType->setAverageHave(0);
            $foodType->setMeasurement($measurement);
            $foodType->setFoodCategory($foodCategory);
            $foodType->setFkUserId($data['userId']);

            $app['orm.em']->persist($foodType);
            $app['orm.em']->flush();

            self::updateFoodCategoryPerc($app, $data['categoryId']);

            $type = 3;
            $msg = $data['foodTypeName']." - ";
            $successMsg = $data['foodTypeName'].' has been created successfully';

            //add a user activity        
            UserUtils::addUserActivity($app, $type, $msg, $app['user']->getId());
            
            //send back array of foodtype object and success message -- and last seven activities? 
            $result = array(
                "foodType" => $foodType->toArray(),
                "successMsg" => $successMsg,
            );
            return $result;
        } catch (Exception $e) {
            throw new Exception();
            return false;
        }
    }

    public function updateFoodTypes($app) {
        $user = $app['orm.em']->find('Entities\User', $app['session']->get('userId'));
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));

        $query = $app['orm.em']->
            createQuery('SELECT ft FROM Entities\Foodtypes ft where ft.fk_userId = :userId')->
            setParameters(array(
                'userId' => $user->getId(),
            ));
        $foodTypes = $query->getResult();

        if (!is_null($foodTypes)) {
            foreach($foodTypes as $foodType) {
                $userNeeds = round($foodType->getOneAdultNeedsMonth() * $needMultiplier);
                if ($userNeeds == 0) {
                    $userNeeds = 1;
                }
                $currentFoodPercentage = $foodType->getUserHas()/$userNeeds;
                $currentFoodPercentage = round($currentFoodPercentage * 100);
                if($currentFoodPercentage > 100){
                    $currentFoodPercentage = 100;
                }
            
                $foodType->setAverageHave($currentFoodPercentage);
                $app['orm.em']->persist($foodType);
                $app['orm.em']->flush();
            }
            self::updateFoodPerc($app);
        }        
    }

    public function updateFoodTypesByApi($app) {
        $user = $app['orm.em']->find('Entities\User', $app['user']->getId());
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));

        $query = $app['orm.em']->
            createQuery('SELECT ft FROM Entities\Foodtypes ft where ft.fk_userId = :userId')->
            setParameters(array(
                'userId' => $user->getId(),
            ));
        $foodTypes = $query->getResult();

        if (!is_null($foodTypes)) {
            foreach($foodTypes as $foodType) {
                $userNeeds = round($foodType->getOneAdultNeedsMonth() * $needMultiplier);
                if ($userNeeds == 0) {
                    $userNeeds = 1;
                }
                $currentFoodPercentage = $foodType->getUserHas()/$userNeeds;
                $currentFoodPercentage = round($currentFoodPercentage * 100);
                if($currentFoodPercentage > 100){
                    $currentFoodPercentage = 100;
                }
            
                $foodType->setAverageHave($currentFoodPercentage);
                $app['orm.em']->persist($foodType);
                $app['orm.em']->flush();
            }
            self::updateFoodPercByApi($app);
        }        
    }

    public function updateFoodType($app, $data, $updateMethod) {
        try {
            $foodType = $app['orm.em']->find('Entities\Foodtypes', $data['foodTypeId']);
        } catch (Doctrine\ORM\NoResultException $e) {
            throw new Exception();
        }

        if($foodType == ''){
            return null;
        }

        $currentAdultPerMonth = $foodType->getOneAdultNeedsMonth();
        $oneAdultNeedsMonth = $foodType->getOneAdultNeedsMonth();
        $userHas = $foodType->getUserHas();
        $measurement = $foodType->getMeasurement()->getMeasurement();
        $goal = $data['goal'];
        $people = $data['people'];
        $currentFoodType = $foodType->getFoodType();
        $categoryId = $foodType->getFoodCategory()->getId();

        //update the amount the user has
        switch($updateMethod) {
            case 'add':
                $newUserHas = $userHas + $data['addFoodAmount'];
                $type = 1;
                $msg = $data['addFoodAmount']." ".$measurement." - ".$currentFoodType." - ";
                $successMsg = $data['addFoodAmount'].' '.$measurement.' of '.$currentFoodType.' has been added.';
                break;
            case 'use':
                if ($userHas == 0) {
                    return $foodType;
                }
                $newUserHas = $userHas - $data['useFoodAmount'];
                $type = 2;
                $msg = $data['useFoodAmount']." ".$measurement." - ".$currentFoodType." - ";
                $successMsg = $data['useFoodAmount'].' '.$measurement.' of '.$currentFoodType.' has been used.';
                break;
            case 'amount':
                $newUserHas = $data['foodAmount'];
                $needAmount = $data['needAmount'];
                $oneAdultNeedsMonth = round($needAmount / ($goal * $people), 3);
                $foodType->setFoodType($data['foodType']);
                if ($userHas != $newUserHas) {
                    $type = 14;
                    $msg = $data['foodType']." - Have ".$newUserHas." - ";
                    $successMsg = 'Your have amount of '.$data['foodType'].' has been updated successfully to "'.$newUserHas.'".';
                }
                if ($currentAdultPerMonth != $oneAdultNeedsMonth) {
                    $type = 16;
                    $msg = $data['foodType']." - Need ".$needAmount." - ";
                    $successMsg = 'Your need food amount of '.$data['foodType'].' has been updated successfully to "'.$needAmount.'".';
                }                 
                if ($currentFoodType != $data['foodType']) {
                    $type = 14;
                    $msg = $currentFoodTypeName." -> ".$data['foodType']." - ";
                    $successMsg = 'Your food type "'.$data['foodType'].'" has been updated successfully.';
                }                              
                break;
        }

        if ($oneAdultNeedsMonth > 0) {
            $userNeeds = round($oneAdultNeedsMonth * $goal * $people);
            if ($userNeeds == 0) {
                $userNeeds = 1;
            }
            // error_log(print_r('oneAdultNeedsMonth ------ '.$oneAdultNeedsMonth, TRUE), 0);
            // error_log(print_r('userNeeds ------ '.$userNeeds, TRUE), 0);
            $currentFoodPercentage = $newUserHas/$userNeeds;
            $currentFoodPercentage = round($currentFoodPercentage * 100);
        } elseif ($oneAdultNeedsMonth == 0) {
            if ($needAmount > 0 || $newUserHas > 0) {
                $currentFoodPercentage = 100;
            }
        }
        if ($currentFoodPercentage > 100) {
            $currentFoodPercentage = 100;
        }

        $foodType->setUserHas($newUserHas);
        $foodType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
        $foodType->setAverageHave($currentFoodPercentage);
        
        $app['orm.em']->persist($foodType);
        $app['orm.em']->flush();         

        self::updateFoodCategoryPerc($app, $categoryId); 

        //add a user activity    
        if (!is_null($type)){
            UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        }
        
        //send back array of foodtype object and success message -- and last seven activities? 
        $result = array(
            "foodType" => $foodType,
            "successMsg" => $successMsg,
        );

        return $result;
    }

    public function updateFoodTypeByApi($app, $data, $updateMethod) {
        try {
            $foodType = $app['orm.em']->find('Entities\Foodtypes', $data['foodTypeId']);
        } catch (Doctrine\ORM\NoResultException $e) {
            throw new Exception();
        }

        if($foodType == ''){
            return null;
        }

        $currentAdultPerMonth = $foodType->getOneAdultNeedsMonth();
        $oneAdultNeedsMonth = $foodType->getOneAdultNeedsMonth();
        $userHas = $foodType->getUserHas();
        $measurement = $foodType->getMeasurement()->getMeasurement();
        $goal = $data['goal'];
        $people = $data['people'];
        $currentFoodType = $foodType->getFoodType();
        $categoryId = $foodType->getFoodCategory()->getId();

        //update the amount the user has
        switch($updateMethod) {
            case 'add':
                $newUserHas = $userHas + $data['addFoodAmount'];
                $type = 1;
                $msg = $data['addFoodAmount']." ".$measurement." - ".$currentFoodType." - ";
                $successMsg = $data['addFoodAmount'].' '.$measurement.' of '.$currentFoodType.' has been added.';
                break;
            case 'use':
                if ($userHas == 0) {
                    return $foodType;
                }
                $newUserHas = $userHas - $data['useFoodAmount'];
                $type = 2;
                $msg = $data['useFoodAmount']." ".$measurement." - ".$currentFoodType." - ";
                $successMsg = $data['useFoodAmount'].' '.$measurement.' of '.$currentFoodType.' has been used.';
                break;
            case 'amount':
                $newUserHas = $data['foodAmount'];
                $needAmount = $data['needAmount'];
                $oneAdultNeedsMonth = round($needAmount / ($goal * $people), 3);
                $foodType->setFoodType($data['foodType']);
                if ($userHas != $newUserHas) {
                    $type = 14;
                    $msg = $data['foodType']." - Have ".$newUserHas." - ";
                    $successMsg = 'Your have amount of '.$data['foodType'].' has been updated successfully to "'.$newUserHas.'".';
                }
                if ($currentAdultPerMonth != $oneAdultNeedsMonth) {
                    $type = 16;
                    $msg = $data['foodType']." - Need ".$needAmount." - ";
                    $successMsg = 'Your need food amount of '.$data['foodType'].' has been updated successfully to "'.$needAmount.'".';
                }                 
                if ($currentFoodType != $data['foodType']) {
                    $type = 14;
                    $msg = $currentFoodTypeName." -> ".$data['foodType']." - ";
                    $successMsg = 'Your food type "'.$data['foodType'].'" has been updated successfully.';
                }                              
                break;
        }

        if ($oneAdultNeedsMonth > 0) {
            $userNeeds = round($oneAdultNeedsMonth * $goal * $people);
            if ($userNeeds == 0) {
                $userNeeds = 1;
            }
            // error_log(print_r('oneAdultNeedsMonth ------ '.$oneAdultNeedsMonth, TRUE), 0);
            // error_log(print_r('userNeeds ------ '.$userNeeds, TRUE), 0);
            $currentFoodPercentage = $newUserHas/$userNeeds;
            $currentFoodPercentage = round($currentFoodPercentage * 100);
        } elseif ($oneAdultNeedsMonth == 0) {
            if ($needAmount > 0 || $newUserHas > 0) {
                $currentFoodPercentage = 100;
            }
        }
        if ($currentFoodPercentage > 100) {
            $currentFoodPercentage = 100;
        }

        $foodType->setUserHas($newUserHas);
        $foodType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
        $foodType->setAverageHave($currentFoodPercentage);
        
        $app['orm.em']->persist($foodType);
        $app['orm.em']->flush();         

        self::updateFoodCategoryPerc($app, $categoryId); 

        //add a user activity    
        if (!is_null($type)){
            UserUtils::addUserActivity($app, $type, $msg, $app['user']->getId());
        }
        
        //send back array of foodtype object and success message -- and last seven activities? 
        $result = array(
            "foodType" => $foodType,
            "successMsg" => $successMsg,
        );

        return $result;
    }


    public function deleteFoodType($app, $foodTypeId) {
        $foodType = $app['orm.em']->find('Entities\Foodtypes', $foodTypeId);
        $foodTypeName = $foodType->getFoodType();
        $categoryId = $foodType->getFoodCategory()->getId();
        $app['orm.em']->remove($foodType);
        $app['orm.em']->flush();

        self::updateFoodCategoryPerc($app, $categoryId);

        $type = 4;
        $msg = $foodTypeName." - ";
        $successMsg = $foodTypeName.' has been removed';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        
        //send back array of foodtype object and success message -- and last seven activities? 
        $result = array(
            "foodTypeId" => $foodTypeId,//$foodType->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }
}
?>