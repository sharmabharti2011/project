<?php
use Doctrine\ORM\Query\ResultSetMapping;

if (!class_exists("Entities\User", false)) {
    require_once __DIR__."/../bootstrap_models.php";
}

class SupplyUtils {

    public function getTotalSupplyPerc($app, $userId) {
        $query = $app['orm.em']->
            createQuery('SELECT sum(sc.percentage), count(sc.id) FROM Entities\Supplycategories sc where sc.fk_userId = :userId')->
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

    public function getSupplyCategory($app, $categoryId) {
        $supplyCategory = $app['orm.em']->find('Entities\Supplycategories', $categoryId);
        return $supplyCategory;
    }

    public function getSupplyCategories($app, $userId) {
        $user = $app['orm.em']->find('Entities\User', $userId);
        $query = $app['orm.em']->
            createQuery('SELECT sc FROM Entities\Supplycategories sc where sc.fk_userId = :userId ORDER BY sc.id ASC')->
            setParameters(array(
                'userId' => $userId,
            ));
        try {
            $supplyCategories = $query->getResult();
            if($user == ''){
                return null;
            }
            $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
            $countAllMembersAsAdults = $user->getAdults() + $user->getChildren();
        
            $categoriesArray = array();
            foreach($supplyCategories as $supplyCategory) 
            {
                $array = array();
                $array['id'] = $supplyCategory->getId();
                $array['supplyCategory'] = $supplyCategory->getSupplyCategory(); 
                $array['percentage'] = $supplyCategory->getPercentage();               
                $query = $app['orm.em']->
                    createQuery('SELECT st FROM Entities\Supplytypes st where st.supplyCategory = :categoryId ORDER BY st.id ASC')->
                    setParameters(array(
                        'categoryId' => $supplyCategory->getId(),
                    ));
                $suppyTypes = $query->getResult();
                $typesArray = array();
                foreach($suppyTypes as $supplyType) 
                {
                    $consumedMonthly = $supplyType->getConsumedMonthly();
                    $eachFamilyMember = $supplyType->getEachFamilyMember();
                    $oneAdultNeedsMonth = $supplyType->getOneAdultNeedsMonth();
                    $array2 = array();
                    $array2['id'] = $supplyType->getId();
                    $array2['supplyType'] = $supplyType->getSupplyType();
                    $array2['userHas'] = $supplyType->getUserHas();
                    if ($eachFamilyMember == "true") {
                        $uNeed = $oneAdultNeedsMonth * $countAllMembersAsAdults;
                        if ($uNeed < 1) {
                            $array2['userNeeds'] = ceil($uNeed);
                        } else {
                            $array2['userNeeds'] = round($uNeed);
                        }
                    } else {
                        if ($consumedMonthly == "true") {
                            $uNeed = $oneAdultNeedsMonth * $needMultiplier;
                            if ($uNeed < 1) {
                                $array2['userNeeds'] = ceil($uNeed);
                            } else {
                                $array2['userNeeds'] = round($uNeed);
                            }
                        } else {
                            $array2['userNeeds'] = $supplyType->getNeed();
                        }
                    }
                    $array2['measurement'] = $supplyType->getMeasurement()->toArray();
                    array_push($typesArray, $array2);
                    // array_push($typesArray, $supplyType->toArray());
                }
                $array['supplyTypes'] = $typesArray;
                array_push($categoriesArray,$array);
                // array_push($categoriesArray,$supplyCategory->toArray());
            }
            // error_log(print_r($userActivities->getId(), TRUE), 0);
        } catch (Doctrine\ORM\NoResultException $e) {
            // return null;
        }        
        return $categoriesArray;
    }

    public function createSupplyCategory($app, $categoryName, $userId) {
        $supplyCategory = new Entities\Supplycategories();
        $supplyCategory->setSupplyCategory($categoryName);
        $supplyCategory->setFkUserId($userId);
        $supplyCategory->setPercentage(0);

        $app['orm.em']->persist($supplyCategory);
        $app['orm.em']->flush();

        $type = 5;
        $msg = $categoryName." - ";
        $successMsg = 'Your new category "'.$categoryName.'" has been created successfully.';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        
        //send back array of supplyCategory object and success message -- and last seven activities? 
        $result = array(
            "supplyCategory" => $supplyCategory->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }

    public function createSupplyCategoryByApi($app, $categoryName, $userId) {
        $supplyCategory = new Entities\Supplycategories();
        $supplyCategory->setSupplyCategory($categoryName);
        $supplyCategory->setFkUserId($userId);
        $supplyCategory->setPercentage(0);

        $app['orm.em']->persist($supplyCategory);
        $app['orm.em']->flush();

        $type = 5;
        $msg = $categoryName." - ";
        $successMsg = 'Your new category "'.$categoryName.'" has been created successfully.';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['user']->getId());
        
        //send back array of supplyCategory object and success message -- and last seven activities? 
        $result = array(
            "supplyCategory" => $supplyCategory->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }

    public function updateSupplyCategory($app, $categoryId, $categoryName) {
        $supplyCategory = $app['orm.em']->find('Entities\Supplycategories', $categoryId);
        $currentCategoryName = $supplyCategory->getSupplyCategory();
        $supplyCategory->setSupplyCategory($categoryName);
        $app['orm.em']->persist($supplyCategory);
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

    public function deleteSupplyCategory($app, $categoryId, $userId) {
        //also delete associated supply types
        try {
            $app['orm.em']->createQuery('DELETE Entities\Supplytypes st where st.supplyCategory = :categoryId')->
                execute(array('categoryId'=>$categoryId));
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }

        $supplyCategory = $app['orm.em']->find('Entities\Supplycategories', $categoryId);
        $supplyCategoryName = $supplyCategory->getSupplyCategory();
        $app['orm.em']->remove($supplyCategory);
        $app['orm.em']->flush();

        $type = 6;
        $msg = $supplyCategoryName." - ";
        $successMsg = 'The category "'.$supplyCategoryName.'" has been successfully removed.';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        
        //send back array of foodCategory object and success message -- and last seven activities? 
        $result = array(
            "categoryId" => $categoryId,//$foodType->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }
    
    public function updateSupplyPerc($app) {
        $user = $app['orm.em']->find('Entities\User', $app['session']->get('userId'));
        $query = $app['orm.em']->
            createQuery('SELECT sc FROM Entities\Supplycategories sc where sc.fk_userId = :userId')->
            setParameters(array(
                'userId' => $user->getId(),
            ));
        try {
            $supplyCategories = $query->getResult();
            foreach($supplyCategories as $supplyCategory) 
            {
                self::updateSupplyCategoryPerc($app, $supplyCategory->getId());
            }
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }       
    }

    public function updateSupplyPercByApi($app) {
        $user = $app['orm.em']->find('Entities\User', $app['user']->getId());
        $query = $app['orm.em']->
            createQuery('SELECT sc FROM Entities\Supplycategories sc where sc.fk_userId = :userId')->
            setParameters(array(
                'userId' => $user->getId(),
            ));
        try {
            $supplyCategories = $query->getResult();
            foreach($supplyCategories as $supplyCategory) 
            {
                self::updateSupplyCategoryPerc($app, $supplyCategory->getId());
            }
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }       
    }

    public function updateSupplyCategoryPerc($app, $categoryId) {
        $query = $app['orm.em']->
            createQuery('SELECT sum(st.averageHave), count(st.id) FROM Entities\Supplytypes st where st.supplyCategory = :categoryId ORDER BY st.id ASC')->
            setParameters(array(
                'categoryId' => $categoryId,
            ));
        $result = $query->getSingleResult();
        if ($result[2] == 0) {
            $categoryPerc = 0;
        } else {
            $categoryPerc = round($result[1] / $result[2]);
        }
        
        $supplyCategory = $app['orm.em']->find('Entities\Supplycategories', $categoryId);
        $supplyCategory->setPercentage($categoryPerc);
        $app['orm.em']->persist($supplyCategory);
        $app['orm.em']->flush();

        return $categoryPerc;
    }

    public function getMeasurement($app, $supplyTypeId) {
        $supplyType = $app['orm.em']->find('Entities\Supplytypes', $supplyTypeId);
        $measurement = $supplyType->getMeasurement();
        return $measurement->toArray();
    }

    public function getMeasurements($app) {
        $query = $app['orm.em']->
            createQuery('SELECT m FROM Entities\Measurements m');
        $measurements = $query->getResult();
        $measurementsArray = array();
        foreach($measurements as $measurement) 
        {
            array_push($measurementsArray, $measurement->toArray());
        }
        return $measurementsArray;
    }

    public function getSupplyType($app, $supplyTypeId) {
        $supplyType = $app['orm.em']->find('Entities\Supplytypes', $supplyTypeId);
        return $supplyType;
    }

    public function getSupplyTypes($app, $categoryId) {
        $query = $app['orm.em']->
            createQuery('SELECT st FROM Entities\Supplytypes st where st.supplyCategory = :categoryId ORDER BY st.id ASC')->
            setParameters(array(
                'categoryId' => $categoryId,
            ));
        try {                
            $supplyTypes = $query->getResult();
            $typesArray = array();
            foreach($supplyTypes as $supplyType) 
            {
                array_push($typesArray, $supplyType->toArray());
            }
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }       
        // error_log(print_r($typesArray, TRUE), 0); 
        return $typesArray;
    }

    public function createSupplyType($app, $data) {
        $user = $app['orm.em']->find('Entities\User', $app['session']->get('userId'));
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
        $familySize = $user->getAdults() + ($user->getChildren()/2);
        $countAllMembersAsAdults = $user->getAdults() + $user->getChildren();

        error_log(print_r($data['consumedMonthly'], TRUE), 0); 
        error_log(print_r($data['consumedMonthly']=='true', TRUE), 0); 
        error_log(print_r($data['consumedMonthly']==true, TRUE), 0); 
        try {
            $measurement = $app['orm.em']->find('Entities\Measurements', $data['measurementId']);
            $supplyCategory = $app['orm.em']->find('Entities\Supplycategories', $data['categoryId']);

            $supplyType = new Entities\Supplytypes();
            $supplyType->setSupplyType($data['supplyTypeName']);
            $oneAdultNeedsMonth = 0;
            $need = 0;
            if ($data['consumedMonthly']) {
                $supplyType->setConsumedMonthly("true");
                // $oneAdultNeedsMonth = $data['myAmount'] / $familySize;
                if($familySize >=1){
                   $oneAdultNeedsMonth = $data['myAmount'] / $familySize;
                }else{
                    $oneAdultNeedsMonth = $data['myAmount'];
                }
                $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                $supplyType->setNeed(0);
                $supplyType->setEachFamilyMember("false");                    
            } else {
                $supplyType->setConsumedMonthly("false");
                if ($data['eachFamilyMember']) {    //case 1
                    $supplyType->setEachFamilyMember("true");
                    $oneAdultNeedsMonth = $data['myAmount'];
                    $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                    $supplyType->setNeed(0);
                } else {    //case 2
                    $supplyType->setEachFamilyMember("false");
                    $oneAdultNeedsMonth = 0;
                    $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                    $need = $data['myAmount'];
                    $supplyType->setNeed($need);
                }
            }
            $userNeeds = 0;
            if ($data['eachFamilyMember']) {
                $userNeeds = $oneAdultNeedsMonth * $countAllMembersAsAdults;
            } else {
                if ($data['consumedMonthly']) {
                    $userNeeds = $oneAdultNeedsMonth * $needMultiplier;
                    error_log(print_r('userneeds: '.$userNeeds, TRUE), 0);
                } else {
                    $userNeeds = $need;
                }
            }
            $supplyType->setUserNeeds(round($userNeeds));
            $supplyType->setAverageHave(0);
            $supplyType->setUserHas(0);
            $supplyType->setMeasurement($measurement);
            $supplyType->setSupplyCategory($supplyCategory);
            $supplyType->setFkUserId($data['userId']);
            
            $app['orm.em']->persist($supplyType);
            $app['orm.em']->flush();

            self::updateSupplyCategoryPerc($app, $data['categoryId']);

            $type = 11;
            $msg = $data['supplyTypeName']." - ";
            $successMsg = $data['supplyTypeName'].' has been created successfully';

            //add a user activity        
            UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
            
            //send back array of supplytype object and success message -- and last seven activities? 
            $result = array(
                "supplyType" => $supplyType->toArray(),
                "successMsg" => $successMsg,
            );
            return $result;
        } catch (Exception $e) {
            throw new Exception();
            return false;
        }
    }

    public function createSupplyTypeByApi($app, $data) {
        $user = $app['orm.em']->find('Entities\User', $app['user']->getId());
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
        $familySize = $user->getAdults() + ($user->getChildren()/2);
        $countAllMembersAsAdults = $user->getAdults() + $user->getChildren();

        error_log(print_r($data['consumedMonthly'], TRUE), 0); 
        error_log(print_r($data['consumedMonthly']=='true', TRUE), 0); 
        error_log(print_r($data['consumedMonthly']==true, TRUE), 0); 
        try {
            $measurement = $app['orm.em']->find('Entities\Measurements', $data['measurementId']);
            $supplyCategory = $app['orm.em']->find('Entities\Supplycategories', $data['categoryId']);

            $supplyType = new Entities\Supplytypes();
            $supplyType->setSupplyType($data['supplyTypeName']);
            $oneAdultNeedsMonth = 0;
            $need = 0;
            if ($data['consumedMonthly']) {
                $supplyType->setConsumedMonthly("true");
                if($familySize >=1){
                   $oneAdultNeedsMonth = $data['myAmount'] / $familySize;
                }else{
                    $oneAdultNeedsMonth = $data['myAmount'];
                }
                $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                $supplyType->setNeed(0);
                $supplyType->setEachFamilyMember("false");                    
            } else {
                $supplyType->setConsumedMonthly("false");
                if ($data['eachFamilyMember']) {    //case 1
                    $supplyType->setEachFamilyMember("true");
                    $oneAdultNeedsMonth = $data['myAmount'];
                    $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                    $supplyType->setNeed(0);
                } else {    //case 2
                    $supplyType->setEachFamilyMember("false");
                    $oneAdultNeedsMonth = 0;
                    $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                    $need = $data['myAmount'];
                    $supplyType->setNeed($need);
                }
            }
            $userNeeds = 0;
            if ($data['eachFamilyMember']) {
                $userNeeds = $oneAdultNeedsMonth * $countAllMembersAsAdults;
            } else {
                if ($data['consumedMonthly']) {
                    $userNeeds = $oneAdultNeedsMonth * $needMultiplier;
                    error_log(print_r('userneeds: '.$userNeeds, TRUE), 0);
                } else {
                    $userNeeds = $need;
                }
            }
            $supplyType->setUserNeeds(round($userNeeds));
            $supplyType->setAverageHave(0);
            $supplyType->setUserHas(0);
            $supplyType->setMeasurement($measurement);
            $supplyType->setSupplyCategory($supplyCategory);
            $supplyType->setFkUserId($data['userId']);
            
            $app['orm.em']->persist($supplyType);
            $app['orm.em']->flush();

            self::updateSupplyCategoryPerc($app, $data['categoryId']);

            $type = 11;
            $msg = $data['supplyTypeName']." - ";
            $successMsg = $data['supplyTypeName'].' has been created successfully';

            //add a user activity        
            UserUtils::addUserActivity($app, $type, $msg, $app['user']->getId());
            
            //send back array of supplytype object and success message -- and last seven activities? 
            $result = array(
                "supplyType" => $supplyType->toArray(),
                "successMsg" => $successMsg,
            );
            return $result;
        } catch (Exception $e) {
            throw new Exception();
            return false;
        }
    }

    public function updateSupplyTypes($app) {
        $user = $app['orm.em']->find('Entities\User', $app['session']->get('userId'));
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
        $countAllMembersAsAdults = $user->getAdults() + $user->getChildren();

        $query = $app['orm.em']->
            createQuery('SELECT st FROM Entities\Supplytypes st where st.fk_userId = :userId')->
            setParameters(array(
                'userId' => $user->getId(),
            ));
        $supplyTypes = $query->getResult();

        if (!is_null($supplyTypes)) {
            foreach($supplyTypes as $supplyType) {
                $supplyTypeId = $supplyType->getId();
                $consumedMonthly = $supplyType->getConsumedMonthly();
                $need = $supplyType->getNeed();
                $eachFamilyMember = $supplyType->getEachFamilyMember();
                $oneAdultNeedsMonth = $supplyType->getOneAdultNeedsMonth();
                $userHas = $supplyType->getUserHas();

                $userNeeds = 0;
                if ($eachFamilyMember == 'true') {
                    $userNeeds = $oneAdultNeedsMonth * $countAllMembersAsAdults;
                } else {
                    if ($consumedMonthly == 'true') {
                        $userNeeds = $oneAdultNeedsMonth * $needMultiplier;
                    } else {
                        $userNeeds = $need;
                    }
                }

                if ($userNeeds == 0) {
                    $userNeeds = 1;
                }
                $currentSupplyPercentage = $userHas/$userNeeds;
                $currentSupplyPercentage = round($currentSupplyPercentage * 100);
                if($currentSupplyPercentage > 100){
                    $currentSupplyPercentage = 100;
                }
            
                $supplyType->setAverageHave($currentSupplyPercentage);
                $app['orm.em']->persist($supplyType);
                $app['orm.em']->flush();
            }
            self::updateSupplyPerc($app);
        }     
    }
    

    public function updateSupplyTypesByApi($app) {
        $user = $app['orm.em']->find('Entities\User', $app['user']->getId());
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
        $countAllMembersAsAdults = $user->getAdults() + $user->getChildren();

        $query = $app['orm.em']->
            createQuery('SELECT st FROM Entities\Supplytypes st where st.fk_userId = :userId')->
            setParameters(array(
                'userId' => $user->getId(),
            ));
        $supplyTypes = $query->getResult();

        if (!is_null($supplyTypes)) {
            foreach($supplyTypes as $supplyType) {
                $supplyTypeId = $supplyType->getId();
                $consumedMonthly = $supplyType->getConsumedMonthly();
                $need = $supplyType->getNeed();
                $eachFamilyMember = $supplyType->getEachFamilyMember();
                $oneAdultNeedsMonth = $supplyType->getOneAdultNeedsMonth();
                $userHas = $supplyType->getUserHas();

                $userNeeds = 0;
                if ($eachFamilyMember == 'true') {
                    $userNeeds = $oneAdultNeedsMonth * $countAllMembersAsAdults;
                } else {
                    if ($consumedMonthly == 'true') {
                        $userNeeds = $oneAdultNeedsMonth * $needMultiplier;
                    } else {
                        $userNeeds = $need;
                    }
                }

                if ($userNeeds == 0) {
                    $userNeeds = 1;
                }
                $currentSupplyPercentage = $userHas/$userNeeds;
                $currentSupplyPercentage = round($currentSupplyPercentage * 100);
                if($currentSupplyPercentage > 100){
                    $currentSupplyPercentage = 100;
                }
            
                $supplyType->setAverageHave($currentSupplyPercentage);
                $app['orm.em']->persist($supplyType);
                $app['orm.em']->flush();
            }
            self::updateSupplyPercByApi($app);
        }     
    }

    public function updateSupplyType($app, $data, $updateMethod) {
        $user = $app['orm.em']->find('Entities\User', $app['session']->get('userId'));
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
        $countAllMembersAsAdults = $user->getAdults() + $user->getChildren();

        try {
            $supplyType = $app['orm.em']->find('Entities\Supplytypes', $data['supplyTypeId']);
        } catch (Doctrine\ORM\NoResultException $e) {
            throw new Exception();
        }

        $measurement = $supplyType->getMeasurement()->getMeasurement();
        $currentSupplyType = $supplyType->getSupplyType();
        $categoryId = $supplyType->getSupplyCategory()->getId();
        $consumedMonthly = $supplyType->getConsumedMonthly();
        $eachFamilyMember = $supplyType->getEachFamilyMember();
        $need = $supplyType->getNeed();        
        $currentAdultPerMonth = $supplyType->getOneAdultNeedsMonth();
        $userHas = $supplyType->getUserHas();
        $oneAdultNeedsMonth = 0;
        // $userNeeds = 0;

        //update the amount the user has
        $newUserHas = 0;
        switch($updateMethod) {
            case 'add':
                $newUserHas = $userHas + $data['addSupplyAmount'];
                $oneAdultNeedsMonth = $currentAdultPerMonth;
                if ($eachFamilyMember == 'true') {
                    $userNeeds = $oneAdultNeedsMonth * $countAllMembersAsAdults;
                } else {
                    if ($consumedMonthly == 'true') {
                        $userNeeds = $oneAdultNeedsMonth * $needMultiplier;
                    } else {
                        $userNeeds = $need;
                    }
                }
                $type = 9;
                $msg = $data['addSupplyAmount']." ".$measurement." - ".$currentSupplyType." - ";
                $successMsg = $data['addSupplyAmount'].' '.$measurement.' of '.$currentSupplyType.' has been added.';
                break;
            case 'use':
                if ($userHas == 0) {
                    return $supplyType;
                }
                $newUserHas = $userHas - $data['useSupplyAmount'];
                $oneAdultNeedsMonth = $currentAdultPerMonth;
                if ($eachFamilyMember == 'true') {
                    $userNeeds = $oneAdultNeedsMonth * $countAllMembersAsAdults;
                } else {
                    if ($consumedMonthly == 'true') {
                        $userNeeds = $oneAdultNeedsMonth * $needMultiplier;
                    } else {
                        $userNeeds = $need;
                    }
                }
                $type = 10;
                $msg = $data['useSupplyAmount']." ".$measurement." - ".$currentSupplyType." - ";
                $successMsg = $data['useSupplyAmount'].' '.$measurement.' of '.$currentSupplyType.' has been used.';
                break;
            case 'amount':
                $newUserHas = $data['supplyAmount'];
                $needAmount = $data['needAmount'];
                if ($eachFamilyMember == 'true') {
                    $oneAdultNeedsMonth = $needAmount/$countAllMembersAsAdults;
                    $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                    $supplyType->setNeed(0);
                    $userNeeds = round($oneAdultNeedsMonth * $countAllMembersAsAdults);
                } else {
                    if ($consumedMonthly == 'true') {
                        $oneAdultNeedsMonth = round($needAmount/$needMultiplier, 3);
                        $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                        $supplyType->setNeed(0);
                        $userNeeds = round($oneAdultNeedsMonth * $needMultiplier);
                    } else {
                        $oneAdultNeedsMonth = 0;
                        $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                        $need = $needAmount;
                        $supplyType->setNeed($need);
                        $userNeeds = $need;
                    }
                }               
                $supplyType->setSupplyType($data['supplyType']);
                //these are initialized values for the variables in case none of the cases match
                $type = 17;
                $msg = $data['supplyType']." - ";
                $successMsg = 'Your supply type "'.$data['supplyType'].'" has been updated successfully.';
                if ($userHas != $newUserHas) {
                    $type = 17;
                    $msg = $data['supplyType']." - Have ".$newUserHas." - ";
                    $successMsg = 'Your have amount of '.$data['supplyType'].' has been updated successfully to "'.$newUserHas.'".';
                }
                if ($currentAdultPerMonth != $oneAdultNeedsMonth) {
                    $type = 16;
                    $msg = $data['supplyType']." - Need ".$needAmount." - ";
                    $successMsg = 'Your need amount of '.$data['supplyType'].' has been updated successfully to "'.$needAmount.'".';
                }                 
                if ($currentSupplyType != $data['supplyType']) {
                    $type = 17;
                    $msg = $currentSupplyType." -> ".$data['supplyType']." - ";
                    $successMsg = 'Your supply type "'.$data['supplyType'].'" has been updated successfully.';
                }                              
                break;
        }

        if ($userNeeds == 0) {
            $userNeeds = 1;
        }
        $supplyType->setUserNeeds($userNeeds);
        $currentSupplyPercentage = $newUserHas/$userNeeds;
        $currentSupplyPercentage = round($currentSupplyPercentage * 100);
        if ($currentSupplyPercentage > 100) {
            $currentSupplyPercentage = 100;
        }

        $supplyType->setUserHas($newUserHas);
        $supplyType->setAverageHave($currentSupplyPercentage);
        
        $app['orm.em']->persist($supplyType);
        $app['orm.em']->flush();         

        self::updateSupplyCategoryPerc($app, $categoryId); 

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        
        //send back array of supplytype object and success message -- and last seven activities? 
        $result = array(
            "supplyType" => $supplyType,
            "successMsg" => $successMsg,
        );

        return $result;
    }

     public function updateSupplyTypeByApi($app, $data, $updateMethod) {
        $user = $app['orm.em']->find('Entities\User', $app['user']->getId());
        $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
        $countAllMembersAsAdults = $user->getAdults() + $user->getChildren();

        try {
            $supplyType = $app['orm.em']->find('Entities\Supplytypes', $data['supplyTypeId']);
        } catch (Doctrine\ORM\NoResultException $e) {
            throw new Exception();
        }
        // die('gtrdghrt');

        if($supplyType == ''){
            return null;
        }


        $measurement = $supplyType->getMeasurement()->getMeasurement();
        $currentSupplyType = $supplyType->getSupplyType();
        $categoryId = $supplyType->getSupplyCategory()->getId();
        $consumedMonthly = $supplyType->getConsumedMonthly();
        $eachFamilyMember = $supplyType->getEachFamilyMember();
        $need = $supplyType->getNeed();        
        $currentAdultPerMonth = $supplyType->getOneAdultNeedsMonth();
        $userHas = $supplyType->getUserHas();
        $oneAdultNeedsMonth = 0;
        // $userNeeds = 0;

        //update the amount the user has
        $newUserHas = 0;
        switch($updateMethod) {
            case 'add':
                $newUserHas = $userHas + $data['addSupplyAmount'];
                $oneAdultNeedsMonth = $currentAdultPerMonth;
                if ($eachFamilyMember == 'true') {
                    $userNeeds = $oneAdultNeedsMonth * $countAllMembersAsAdults;
                } else {
                    if ($consumedMonthly == 'true') {
                        $userNeeds = $oneAdultNeedsMonth * $needMultiplier;
                    } else {
                        $userNeeds = $need;
                    }
                }
                $type = 9;
                $msg = $data['addSupplyAmount']." ".$measurement." - ".$currentSupplyType." - ";
                $successMsg = $data['addSupplyAmount'].' '.$measurement.' of '.$currentSupplyType.' has been added.';
                break;
            case 'use':
                if ($userHas == 0) {
                    return $supplyType;
                }
                $newUserHas = $userHas - $data['useSupplyAmount'];
                $oneAdultNeedsMonth = $currentAdultPerMonth;
                if ($eachFamilyMember == 'true') {
                    $userNeeds = $oneAdultNeedsMonth * $countAllMembersAsAdults;
                } else {
                    if ($consumedMonthly == 'true') {
                        $userNeeds = $oneAdultNeedsMonth * $needMultiplier;
                    } else {
                        $userNeeds = $need;
                    }
                }
                $type = 10;
                $msg = $data['useSupplyAmount']." ".$measurement." - ".$currentSupplyType." - ";
                $successMsg = $data['useSupplyAmount'].' '.$measurement.' of '.$currentSupplyType.' has been used.';
                break;
            case 'amount':
                $newUserHas = $data['supplyAmount'];
                $needAmount = $data['needAmount'];
                if ($eachFamilyMember == 'true') {
                    $oneAdultNeedsMonth = $needAmount/$countAllMembersAsAdults;
                    $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                    $supplyType->setNeed(0);
                    $userNeeds = round($oneAdultNeedsMonth * $countAllMembersAsAdults);
                } else {
                    if ($consumedMonthly == 'true') {
                        $oneAdultNeedsMonth = round($needAmount/$needMultiplier, 3);
                        $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                        $supplyType->setNeed(0);
                        $userNeeds = round($oneAdultNeedsMonth * $needMultiplier);
                    } else {
                        $oneAdultNeedsMonth = 0;
                        $supplyType->setOneAdultNeedsMonth($oneAdultNeedsMonth);
                        $need = $needAmount;
                        $supplyType->setNeed($need);
                        $userNeeds = $need;
                    }
                }               
                $supplyType->setSupplyType($data['supplyType']);
                //these are initialized values for the variables in case none of the cases match
                $type = 17;
                $msg = $data['supplyType']." - ";
                $successMsg = 'Your supply type "'.$data['supplyType'].'" has been updated successfully.';
                if ($userHas != $newUserHas) {
                    $type = 17;
                    $msg = $data['supplyType']." - Have ".$newUserHas." - ";
                    $successMsg = 'Your have amount of '.$data['supplyType'].' has been updated successfully to "'.$newUserHas.'".';
                }
                if ($currentAdultPerMonth != $oneAdultNeedsMonth) {
                    $type = 16;
                    $msg = $data['supplyType']." - Need ".$needAmount." - ";
                    $successMsg = 'Your need amount of '.$data['supplyType'].' has been updated successfully to "'.$needAmount.'".';
                }                 
                if ($currentSupplyType != $data['supplyType']) {
                    $type = 17;
                    $msg = $currentSupplyType." -> ".$data['supplyType']." - ";
                    $successMsg = 'Your supply type "'.$data['supplyType'].'" has been updated successfully.';
                }                              
                break;
        }

        if ($userNeeds == 0) {
            $userNeeds = 1;
        }
        $supplyType->setUserNeeds($userNeeds);
        $currentSupplyPercentage = $newUserHas/$userNeeds;
        $currentSupplyPercentage = round($currentSupplyPercentage * 100);
        if ($currentSupplyPercentage > 100) {
            $currentSupplyPercentage = 100;
        }

        $supplyType->setUserHas($newUserHas);
        $supplyType->setAverageHave($currentSupplyPercentage);
        
        $app['orm.em']->persist($supplyType);
        $app['orm.em']->flush();         

        self::updateSupplyCategoryPerc($app, $categoryId); 

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['user']->getId());
        
        //send back array of supplytype object and success message -- and last seven activities? 
        $result = array(
            "supplyType" => $supplyType,
            "successMsg" => $successMsg,
        );

        return $result;
    }

    public function deleteSupplyType($app, $supplyTypeId) {
        $supplyType = $app['orm.em']->find('Entities\Supplytypes', $supplyTypeId);
        $supplyTypeName = $supplyType->getSupplyType();
        $categoryId = $supplyType->getSupplyCategory()->getId();
        $app['orm.em']->remove($supplyType);
        $app['orm.em']->flush();

        self::updateSupplyCategoryPerc($app, $categoryId);

        $type = 12;
        $msg = $supplyTypeName." - ";
        $successMsg = $supplyTypeName.' has been removed';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        
        //send back array of supplytype object and success message -- and last seven activities? 
        $result = array(
            "supplyTypeId" => $supplyTypeId,//$foodType->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }
}
?>