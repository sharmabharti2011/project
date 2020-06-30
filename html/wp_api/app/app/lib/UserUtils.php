<?php
use Doctrine\ORM\Query\ResultSetMapping;

if (!class_exists("Entities\User", false)) {
    require_once __DIR__."/../bootstrap_models.php";
}
require_once __DIR__.'/bis.pp.func.inc.php';

class UserUtils {
    
    public function loginUser($app, $username, $password) {
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
            return null;
        }
        return $user;
    }

    public function sendPasswordEmail($app, $email) {
        $userName = null;
        $hash = null;

        $query = $app['orm.em']->createQuery('SELECT u FROM Entities\User u where u.email = :email');
        $query->setParameters(array(
            'email' => $email,
        ));
        try {
            $user = $query->getSingleResult();
            $userName = $user->getUserName();
        } catch (Doctrine\ORM\NoResultException $e) {
            // return error message that the email doesn't exist
            // $_SESSION['errorMsg'] = 'That email does not exist. Please try again.';
            $app['session']->set('successMsg', "No user found with that email address.");
            return null;
        }

        $query = $app['orm.em']->createQuery("SELECT tpr FROM Entities\TempPasswordReset tpr WHERE tpr.email = :email");
        $query->setParameters(array(
            'email' => $email,
        ));
        try {
            $tprExists = $query->getSingleResult();
            if ($tprExists) {
                $pr = $app['orm.em']->find('Entities\TempPasswordReset', $tprExists->getId());
                $app['orm.em']->remove($pr);
                $app['orm.em']->flush();
            }
                       
            $hash = md5( rand(0,1000) ); /* Random code generated for their email. */   
            $tpr = new Entities\TempPasswordReset();
            $tpr->setEmail($email);
            $tpr->setHash($hash);
            $app['orm.em']->persist($tpr);
            $app['orm.em']->flush();
        } catch (Doctrine\ORM\NoResultException $e) {
            // return error message that the email doesn't exist
            $hash = md5( rand(0,1000) ); /* Random code generated for their email. */   
            $tpr = new Entities\TempPasswordReset();
            $tpr->setEmail($email);
            $tpr->setHash($hash);
            $app['orm.em']->persist($tpr);
            $app['orm.em']->flush();
        }

        $body = '
        There has been a request made to reset your password on stockupfood.com.
        
        Your current username is: '.$userName.'
        
        To reset your password, please click the link below:
        http://www.stockupfood.com/app/forgot-password/verify?email='.urlencode($email).'&hash='.$hash.'
        
        ';
        $message = \Swift_Message::newInstance()
            ->setSubject("Password reset link | stockupfood")
            ->setFrom(array('contact@stockupfood.com'))
            ->setTo(array($email))
            ->setBody($body);

        $app['mailer']->send($message);

        $app['session']->set('successMsg', "An email has been sent with instructions on how to reset your password.");
    }

    public function isValidResetRequest($app, $email, $hash) {
        $query = $app['orm.em']->createQuery("SELECT tpr FROM Entities\TempPasswordReset tpr WHERE tpr.email = :email and tpr.hash = :hash");
        $query->setParameters(array(
            'email' => $email,
            'hash' => $hash
        ));
        $tprExists = $query->getSingleResult();
        if ($tprExists) {
            $query = $app['orm.em']->createQuery('SELECT u FROM Entities\User u where u.email = :email');
            $query->setParameters(array(
                'email' => $email,
            ));
            $user = $query->getSingleResult();
            return $user;
        } else {
            return null;
        }
    }

    public function updateUserPassword($app, $userId, $password) {
        $user = self::getUser($app, $userId);
        $user->setPassword($password);
        $app['orm.em']->persist($user);
        $app['orm.em']->flush();

        $query = $app['orm.em']->createQuery("SELECT tpr FROM Entities\TempPasswordReset tpr WHERE tpr.email = :email");
        $query->setParameters(array(
            'email' => $user->getEmail(),
        ));
        $tprExists = $query->getSingleResult();
        if ($tprExists) {
            $pr = $app['orm.em']->find('Entities\TempPasswordReset', $tprExists->getId());
            $app['orm.em']->remove($pr);
            $app['orm.em']->flush();
        }
        return true;
    }

    public function getUser($app, $userId) {
        $user = $app['orm.em']->find('Entities\User', $userId);
        return $user;
    }

    public function getUserByUsername($app, $username) {
        error_log(print_r($username, TRUE), 0);
        $query = $app['orm.em']->createQuery('SELECT u FROM Entities\User u where u.userName = :name');
        $query->setParameters(array(
            'name' => $username
        ));
        try {
            $user = $query->getSingleResult();
            // error_log(print_r($user->getId(), TRUE), 0);
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }
        return $user;
    }

    public function getUserActivities($app, $userId) {
        $query = $app['orm.em']->
            createQuery('SELECT ua FROM Entities\UserActivity ua where ua.fk_userId = :userId ORDER BY ua.date DESC')->
            setParameters(array(
                'userId' => $userId,
            ))->
            setMaxResults(7);
        try {
            $userActivities = $query->getResult();
            $activitiesArray = array();
            foreach($userActivities as $userActivity) 
            {
                // var_dump($userActivity->getActivity()->getClass());
                $array = array();
                $array['activityClass'] = $userActivity->getActivity()->getClass();
                $array['activityDesc'] = $userActivity->getActivity()->getActivity()." ".$userActivity->getExtra();
                $array['activityDate'] = date('M j, Y',strtotime($userActivity->getDate()));
                array_push($activitiesArray,$array);
            }
            // error_log(print_r($userActivities->getId(), TRUE), 0);
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }        
        return $activitiesArray;
    }

    public function createUser($app, $user) {
        $userName = $user['username'];
        $email = $user['email'];
        $query = $app['orm.em']->createQuery('SELECT u FROM Entities\User u where u.userName = :name');
        $query->setParameters(array(
            'name' => $userName,
        ));
        try {
            $userExists = $query->getResult();
            // if the query finds something, we should return a null...
            // otherwise, it will hit the catch and continue on
            if (count($userExists) > 0) {
                return "username";
            }
        } catch (Doctrine\ORM\NoResultException $e) {
            // no user with either name or email has been found -- continue
        }
        $query = $app['orm.em']->createQuery('SELECT u FROM Entities\User u where u.email = :email');
        $query->setParameters(array(
            'email' => $email,
        ));
        try {
            $userExists = $query->getResult();
            // if the query finds something, we should return a null...
            // otherwise, it will hit the catch and continue on
            if (count($userExists) > 0) {
                return "email";
            }
        } catch (Doctrine\ORM\NoResultException $e) {
            // no user with either name or email has been found -- continue
        }

        $rank = $app['orm.em']->find('Entities\Rank', 1);
        $hash = md5(rand(0,1000));
        date_default_timezone_set('America/Denver');
        $now = new DateTime();

        //what do we need to check for when adding a user
        // what is unique - email, username, etc
        $newUser = new Entities\User();
        $newUser->setUserName($user['username']);
        $newUser->setPassword($user['password']);
        $newUser->setName($user['name']);
        $newUser->setEmail($user['email']);
        $newUser->setAdults($user['adults']);
        $newUser->setChildren($user['children']);
        $newUser->setGoal($user['goal']);
        $newUser->setRegisteredDate($now->format('Ymd'));
        $newUser->setActive(0);     //set at 0 until the user verifies their email
        $newUser->setRank($rank);
        $newUser->setAccount('free');
        $newUser->setLastLoggedIn($now->format('Ymd'));
        $newUser->setTimesLoggedIn(1);
        if(isset($user['customerToken']) && $user['customerToken'] !=null){
            $newUser->setCustomerToken($user['customerToken']);
        }else{
            $newUser->setCustomerToken(null);
        }
        $newUser->setEmailVerifyHash($hash);
        
// echo "<pre>";
// print_r($newUser);
// die('here');



        
        $app['orm.em']->persist($newUser);
        $app['orm.em']->flush();
        $newUserId = $newUser->getId();

        //add default food categories and types
        self::addDefaultFoods($app, $newUserId);
        self::sendWelcomeEmail($app, $newUser->getEmail(), $hash);

        return $newUser;
    }

    public function addorUpdateCustomer($app, $user, $token, $plan, $coupon) {
        try {
            $existingUser = $app['orm.em']->find('Entities\User', $user->getId());
        } catch (Doctrine\ORM\NoResultException $e) {
            throw new Exception();
        }

        $result = array();
        if (!is_null($existingUser->getCustomerToken())) {
            //update customer
            try {
                $cu = Stripe_Customer::retrieve($existingUser->getCustomerToken());
                // $cu->description = "Stockupfood customer - ".$existingUser->getUserName();
                // $cu->email = $existingUser->getEmail();
                $cu->card = $token;
                $cu->plan = $plan;
                $cu->coupon = $coupon;
                $cu->save();
            } catch (Exception $e) {
                $result['user'] = null;
                //$result['msg'] = "There was a problem with the credit card information you entered.  Please try again.";
                //$result['msg'] = "There was a problem with information you entered.  Please try again.";
                $result['msg'] = $e;
                return $result;
            }
        } else {
            //create stripe customer
            try {
                $data = array(
                  "description" => "Stockupfood customer - ".$user->getUserName(),
                  "email" => $user->getEmail(),
                  "card" => $token,
                  "coupon" => $coupon,
                  "plan" => $plan
                );
                $bd = $existingUser->getBillingDate();
                if (!is_null($bd)) {
                    $data['trial_end'] = self::getTrialEndDate($bd);
                }
                $customerToken = Stripe_Customer::create($data);
                $existingUser->setCustomerToken($customerToken['id']);
                date_default_timezone_set('America/Denver');
                $billingDate = new DateTime();
                $existingUser->setBillingDate($billingDate->format('Y-m-d'));
                $existingUser->setAccount('paid');
                $existingUser->setSubscription($plan);

                //check to see if they have a paypal account that should be deleted
                UserUtils::deletePaypalProfile($app, $existingUser);
            } catch (Exception $e) {
                $result['user'] = null;
                //$result['msg'] = "There wasbcvcbfcb. a problem creating your user -- you may entered invalid credit card information.  Please try again.";
                //$result['msg'] = "There was a problem with information you entered.  Please try again.";
                $result['msg'] = $e;
                return $result;
            }
        }
        
        $app['orm.em']->persist($existingUser);
        $app['orm.em']->flush(); 

        //add default supply categories and types - or re-add if user is upgrading   
        self::addDefaultSupplies($app, $existingUser->getId());   

        $result['user'] = $existingUser;
        return $result;
    }

    public function updateUser($app, $user, $data) {
        try {
            $existingUser = $app['orm.em']->find('Entities\User', $user->getId());
        } catch (Doctrine\ORM\NoResultException $e) {
            throw new Exception();
        }

        $existingUser->setAdults($data['adults']);
        $existingUser->setChildren($data['children']);
        $existingUser->setGoal($data['goal']);
        
        $app['orm.em']->persist($existingUser);
        $app['orm.em']->flush();

        StorageUtils::updateFoodTypes($app);
        SupplyUtils::updateSupplyTypes($app);

        $type = 7;
        $msg = "";
        $successMsg = 'Your settings have been updated.';

        //add a user activity        
        UserUtils::addUserActivity($app, $type, $msg, $app['session']->get('userId'));
        
        $result = array(
            "user" => $existingUser->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }

    public function isCardExpired($app, $userId) {
        $user = self::getUser($app, $userId);
        //if the user is not a paid user, ignore this check -- but what about if the user is earned?
        if ($user->getCustomerToken() != 'paid') {
            return false;
        }
        // error_log(print_r('id-'.$user->id, TRUE), 0);

        date_default_timezone_set('America/Denver');
        $now = new DateTime();
        $app['monolog']->addInfo(sprintf("User '%s' registered.", $user->getCustomerToken()));
        //determine if user is stripe or paypal
        if (is_null($user->getCustomerToken())) {
            //user is paypal, do the check
            $arrRV2 = parent::getPaypalProfile($app, $user);
            $ccExpDate = $arrRV2['EXPDATE'];
            date_default_timezone_set('America/Denver');
            $expDate = new DateTime($ccExpDate);
        } else {
            //user is stripe, do the check
            $card = parent::getCustomerCard($app, $user->getCustomerToken());
            $exp_month = str_pad($card['exp_month'],2,"0", STR_PAD_LEFT);
            $dateStr = $card['exp_year'].$exp_month."01";
            date_default_timezone_set('America/Denver');
            $expDate = new DateTime($dateStr);
        }
        if ($expDate < $now) {
            return true;
        } else {
            return false;
        }               
    }

    public function userHasNoCard($app, $userId) {
        $user = self::getUser($app, $userId);
        //if the user is not a paid user, ignore this check -- but what about if the user is earned?
        if ($user->getAccount() == 'paid' or $user->getAccount() == 'earned') {
            // error_log(print_r('id-'.$user->id, TRUE), 0);
            if (is_null($user->getCustomerToken()) and is_null(self::getPaypalProfile($app, $user))) {
                return true;    //user has neither stripe customer or paypal profile, user has no card
            } else {
                return false;    //user has either a stripe customer or a paypal profile, so user has a card
            }   
        } else {
            return false;
        }
    }
    
    public function deleteCustomer($app, $user, $deleteAccount) {
        try {
            $existingUser = $app['orm.em']->find('Entities\User', $user->getId());
        } catch (Doctrine\ORM\NoResultException $e) {
            throw new Exception();
        }

        try {
            if (strlen($user->getCustomerToken()) > 0) {
                $cu = Stripe_Customer::retrieve($user->getCustomerToken());
                $cu->delete();
            } else {
                //user is paypal, delete their account
                $result = self::deletePaypalProfile($app, $user);
                //need to check what is returned and how to react to errors
                if ($result == 'Success') {
                    //paypal user successfully cancelled
                }
            }       

            if ($deleteAccount) {
                self::deleteUser($app, $existingUser);
            } else {
                $existingUser->setCustomerToken(null);
                $existingUser->setBillingDate(null);
                $existingUser->setAccount('free');
                $app['orm.em']->persist($existingUser);
                $app['orm.em']->flush();
            }                        
            
            return true;
        } catch (Exception $e) {
            error_log(print_r($e, TRUE), 0);
            throw new Exception();
            return false;
        }           
    }

    public function getCustomerCard($app, $token) {
        try {
            $customer = Stripe_Customer::retrieve($token);
        } catch (Exception $e) {
            return null;
        }           

        return $customer['active_card'];
    }

    public function getCustomerPlan($app, $token) {
        try {
            $customer = Stripe_Customer::retrieve($token);
        } catch (Exception $e) {
            return null;
        }
        $test = $customer['subscription'];
        $plan = $test['plan'];
        
        return $plan['name'];
    }

    public function getPaypalProfile($app, $user) {
        try {
            $rsm = new ResultSetMapping;
            $rsm->addEntityResult('Entities\PaypalProfile', 'p');
            $rsm->addFieldResult('p', 'pp_rp_id', 'pp_rp_id');

            $query = $app['orm.em']->createNativeQuery('SELECT pp_rp_id FROM tbl_pp_recurring_profiles WHERE fk_userId='.$user->getId(), $rsm);
            $profile = $query->getSingleResult();
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }
        // Combine the profile ID along with the method to allow for the other queries on the API
        $method = "GetRecurringPaymentsProfileDetails";
        $nvp_str = "&PROFILEID=".$profile->getPpRpId();
        // $nvp_str = "&PROFILEID=I-UWS14VRSTFC7";

        $arrRV2 = hash_call( $method, $nvp_str );
        return $arrRV2;
    }

    public function deletePaypalProfile($app, $user) {
        try {
            $rsm = new ResultSetMapping;
            $rsm->addEntityResult('Entities\PaypalProfile', 'p');
            $rsm->addFieldResult('p', 'pp_rp_id', 'pp_rp_id');

            $query = $app['orm.em']->createNativeQuery('SELECT pp_rp_id FROM tbl_pp_recurring_profiles WHERE fk_userId='.$user->getId(), $rsm);
            $profile = $query->getSingleResult();
        } catch (Doctrine\ORM\NoResultException $e) {
            error_log(print_r('Unable to find paypal profile for this user', TRUE), 0);
            return null;
        }
        // $pp_rp_id = "I-UWS14VRSTFC7";

        $method = "ManageRecurringPaymentsProfileStatus";
        // $nvp_str = "&PROFILEID=".$pp_rp_id."&ACTION=Cancel&NOTE=CANCELED_SERVICE";
        $nvp_str = "&PROFILEID=".$profile->getPpRpId()."&ACTION=Cancel&NOTE=CANCELED_SERVICE";
        $arrRV = hash_call( $method, $nvp_str );
        // error_log(print_r($arrRV, TRUE), 0);
        
        return $arrRV['ACK'];
    }

    public function deleteUser($app, $user) {
        $userId = $user->getId();

        try {
            $app['orm.em']->createQuery('DELETE Entities\UserActivity ua where ua.fk_userId = :userId')->execute(array('userId'=>$userId));
            $app['orm.em']->createQuery('DELETE Entities\Foodtypes ft where ft.fk_userId = :userId')->execute(array('userId'=>$userId));
            $app['orm.em']->createQuery('DELETE Entities\Foodcategories fc where fc.fk_userId = :userId')->execute(array('userId'=>$userId));
            $app['orm.em']->createQuery('DELETE Entities\Supplytypes st where st.fk_userId = :userId')->execute(array('userId'=>$userId));
            $app['orm.em']->createQuery('DELETE Entities\Supplycategories sc where sc.fk_userId = :userId')->execute(array('userId'=>$userId));
            $app['orm.em']->createQuery('DELETE Entities\PaypalProfile p where p.user = :userId')->execute(array('userId'=>$userId));
            $app['orm.em']->createQuery('DELETE Entities\TempPasswordReset tpr where tpr.email = :email')->execute(array('email'=>$user->getEmail()));            
            $app['orm.em']->createQuery('DELETE Entities\User u where u.id = :userId')->execute(array('userId'=>$userId));
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    public function updateUserRank($app, $userId) {
        $user = $app['orm.em']->find('Entities\User', $userId);

        $goal = $user->getGoal();
        $people = $user->getAdults() + ($user->getChildren() / 2);
        $currentRankId = $user->getRank()->getId();

        $query = $app['orm.em']->
            createQuery('SELECT COUNT(fc) as cnt, AVG(fc.percentage) as perc FROM Entities\Foodcategories fc where fc.fk_userId = :userId')->
            setParameters(array(
                'userId' => $userId,
            ));
        $result = $query->getSingleResult();
        $numFoodCategories = $result['cnt'];
        $totalFoodPercentage = $result['perc'];

        $query = $app['orm.em']->
            createQuery('SELECT AVG(sc.percentage) as perc FROM Entities\Supplycategories sc where sc.fk_userId = :userId')->
            setParameters(array(
                'userId' => $userId,
            ));
        $result = $query->getSingleResult();
        $totalSupplyPercentage = $result['perc'];

        $query = $app['orm.em']->
            createQuery('SELECT COUNT(ft) as cnt, SUM(ft.oneAdultNeedsMonth) as adultNeeds, SUM(ft.averageHave) as sumAverageHave, SUM(ft.userHas) as has FROM Entities\Foodtypes ft where ft.fk_userId = :userId')->
            setParameters(array(
                'userId' => $userId,
            ));
        $result = $query->getSingleResult();
        $numFoodTypes = $result['cnt'];
        $sumOneAdultNeedsMonth = $result['adultNeeds'];
        $sumUserHas = $result['has'];
        $sumAverageHave = $result['sumAverageHave'];

        // $sumUserNeeds = mround($sumOneAdultNeedsMonth * $goal * $people);
        $sumUserNeeds = $sumOneAdultNeedsMonth * $goal * $people;
        // error_log(print_r('sumOneAdultNeedsMonth ------ '.$sumOneAdultNeedsMonth, TRUE), 0);
        // error_log(print_r('sumUserNeeds ------ '.$sumUserNeeds, TRUE), 0);

        $totalPercentageCombined = 0;
        if ($user->getAccount() == "free") {
            $totalPercentageCombined = $totalFoodPercentage;
        } else {
            $totalPercentageCombined = ($totalFoodPercentage + $totalSupplyPercentage) / 2;
        }
        
        $rankId = "1";

        // Make sure they at least have 3 food categories
        if ($numFoodCategories >= 3 and $numFoodTypes >= 15 and $sumUserNeeds >= 350) {
            // Now that we know they reach the criteria ( At least 3 categories, 15 food types, and need at least 400, we will give their ranks)
            switch(TRUE) {
                case ($totalPercentageCombined >= 100):
                    $rankId = "12";
                    break;
                case ($totalPercentageCombined >= 90):
                    $rankId = "11";
                    break;
                case ($totalPercentageCombined >= 80):
                    $rankId = "10";
                    break;
                case ($totalPercentageCombined >= 70):
                    $rankId = "9";
                    break;
                case ($totalPercentageCombined >= 60):
                    $rankId = "8";
                    break;
                case ($totalPercentageCombined >= 50):
                    $rankId = "7";
                    break;
                case ($totalPercentageCombined >= 40):
                    $rankId = "6";
                    break;
                case ($totalPercentageCombined >= 30):
                    $rankId = "5";
                    break;
                case ($totalPercentageCombined >= 20):
                    $rankId = "4";
                    break;
                case ($totalPercentageCombined >= 10):
                    $rankId = "3";
                    break;
                case ($totalPercentageCombined >= 1):
                    $rankId = "2";
                    break;
            }
        }
        // If the variable $upgradedRank has something in it, it means they upgraded. Now update their activity.
        $rank = $app['orm.em']->find('Entities\Rank', $rankId);
        if($rankId > $currentRankId){            
            $type = 8;
            $msg = '"'.$rank->getRankName().'"'.' - ';
            self::addUserActivity($app, $type, $msg, $userId);
        }
        $user->setRank($rank);
        $app['orm.em']->persist($user);
        $app['orm.em']->flush();   

        // Check to see if the difference between what the user needs and what the user has is within the threshold.
        // Need to do this to accomodate the potential of a user having 100% of their needs but the calculations are off by a tiny decimal portion.
        // Anything over what the user needs is also accepted of course.
        $isInThreshold = ((floor($sumUserNeeds*10)/10) - $sumUserHas) <= 0.1;
        // Also, a user can't just double up on another food type and hope to achieve the free version.  They must have 100% in each category too.
        $allFoodTypesFull = $sumAverageHave/$numFoodTypes == 100;

        //check to see if they meet 'earned' status
        $earnedCriteriaResult = ($goal >= 12 and $numFoodCategories >= 3 and $numFoodTypes >= 15 and $sumUserNeeds >= 400 and $isInThreshold and $allFoodTypesFull);

        self::updateEarnedStatus($app, $user, $earnedCriteriaResult);        
    }

    // $result is true if the user has met all the requirements for moving to the 
    // "earned" status.  This is caluclated in $earnedCriteriaResult in updateUserRank()
    public function updateEarnedStatus($app, $user, $result) {
        if ($result == true and $user->getAccount() == 'paid') {
            $user->setAccount('earned');
            $app['orm.em']->persist($user);
            $app['orm.em']->flush();

            //check if the user is a paypal user
            $ppUser = self::getPaypalProfile($app, $user);
            if (!is_null($ppUser)) {
                //delete pp profile
                self::deletePaypalProfile($app, $user);
            }

            if (!is_null($user->getCustomerToken())) {
                $c = Stripe_Customer::retrieve($user->getCustomerToken());
                $c->updateSubscription(array("plan" => "free", "prorate" => false));
            }
        } elseif ($result == false and $user->getAccount() == 'earned') {
            $user->setAccount('paid');
            $app['orm.em']->persist($user);
            $app['orm.em']->flush();

            //check if the user is a paypal user
            $ppUser = self::getPaypalProfile($app, $user);
            if (!is_null($ppUser)) {
                //delete pp profile
                self::deletePaypalProfile($app, $user);
            } else {
                $c = Stripe_Customer::retrieve($user->getCustomerToken());
                $bd = $user->getBillingDate();
                if (!is_null($bd)) {
                    $trial_end = self::getTrialEndDate($bd);
                    $c->updateSubscription(array("plan" => $user->getSubscription(), "trial_end" => $trial_end, "prorate" => false));   
                } else {
                    $c->updateSubscription(array("plan" => $user->getSubscription(), "prorate" => false));   
                }
            }
        }
    }

    public function getTrialEndDate($bd) {
        date_default_timezone_set('America/Denver');
        $billingDate = DateTime::createFromFormat('Y-m-d', $bd);
        $now = new DateTime();
        $day = date("d", $billingDate->getTimestamp());
        $month = date("m", $now->getTimestamp());
        $year = date("Y", $now->getTimestamp());
        $finalDate = date('Y-m-d', strtotime($year.'-'.$month.'-'.$day. ' + 1 months'));
        // error_log(print_r($finalDate, TRUE), 0);
        $trialDate = DateTime::createFromFormat('Y-m-d', $finalDate);
        $trial_end = $trialDate->getTimestamp();
        // error_log(print_r($trial_end, TRUE), 0);
        return $trial_end;
    }

    public function addUserActivity($app, $activityId, $msg, $userId) {
        $activity = $app['orm.em']->find('Entities\Activity', $activityId);

        $userActivity = new Entities\UserActivity();
        $userActivity->setActivity($activity);
        $userActivity->setFkUserId($userId);
        $userActivity->setExtra($msg);
        $userActivity->setDate(date("Y-m-d H:i:s"));

        $app['orm.em']->persist($userActivity);
        $app['orm.em']->flush();
    }

    public function sendWelcomeEmail($app, $email, $hash) {
        $body = '
        Thanks for signing up!
        
        Your account has been created and is waiting for you...
        
        To log in to your account, please click the link below:
        https://www.stockupfood.com/login
        
        ';
        $message = \Swift_Message::newInstance()
            ->setSubject("Welcome to Stockupfood.com! | stockupfood")
            ->setFrom(array('contact@stockupfood.com'))
            ->setTo(array($email))
            ->setBody($body);

        $app['mailer']->send($message);
    }

    public function updateAccount($app, $userId, $type) {
        try {
            $existingUser = $app['orm.em']->find('Entities\User', $userId);
        } catch (Doctrine\ORM\NoResultException $e) {
            throw new Exception();
        }

        $existingUser->setAccount($type);        
        $app['orm.em']->persist($existingUser);
        $app['orm.em']->flush();

        $successMsg = "User with ID# ".$userId." has been upgraded";

        $result = array(
            "user" => $existingUser->toArray(),
            "successMsg" => $successMsg,
        );
        return $result;
    }

    public function getPaidUsers($app) {
        $query = $app['orm.em']->createQuery('SELECT count(u) FROM Entities\User u where u.account = :type');
        $query->setParameters(array(
            'type' => 'paid',
        ));
        try {
            $users = $query->getSingleResult();
        } catch (Doctrine\ORM\NoResultException $e) {
            return 0;
        }
        return $users[1];
    }

    public function getTotalUsers($app) {
        $query = $app['orm.em']->createQuery('SELECT count(u) FROM Entities\User u');
        try {
            $total = $query->getSingleResult();
        } catch (Doctrine\ORM\NoResultException $e) {
            return 0;
        }
        return $total[1];
    }

    public function recalculateAllFoodPercentages($app, $user) {
        if (!is_null($user)) {
            $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
            $query = $app['orm.em']->
                createQuery('SELECT fc FROM Entities\Foodcategories fc where fc.fk_userId = :userId')->
                setParameters(array(
                    'userId' => $user->getId(),
                ));
            $foodCategories = $query->getResult();
            foreach($foodCategories as $foodCategory) {
                $categoryId = $foodCategory->getId();
                $query = $app['orm.em']->
                    createQuery('SELECT ft FROM Entities\Foodtypes ft where ft.foodCategory = :categoryId ORDER BY ft.id ASC')->
                    setParameters(array(
                        'categoryId' => $categoryId,
                    ));
                $foodTypes = $query->getResult();
                $currentFoodPercentage = 0;
                $userNeeds = 0;
                $userHas = 0;
                foreach($foodTypes as $foodType) {
                    $userNeeds = $foodType->getOneAdultNeedsMonth() * $needMultiplier;
                    $userHas = $foodType->getUserHas();
                    if ($userNeeds == 0) {
                        $userNeeds = 1;
                    }
                    // error_log(print_r('oneAdultNeedsMonth ------ '.$oneAdultNeedsMonth, TRUE), 0);
                    // error_log(print_r('userNeeds ------ '.$userNeeds, TRUE), 0);
                    $currentFoodPercentage = ($userHas/$userNeeds) * 100;
                    if ($currentFoodPercentage > 100) {
                        $currentFoodPercentage = 100;
                    }
                    $foodType->setAverageHave($currentFoodPercentage);
                    
                    $app['orm.em']->persist($foodType);
                    $app['orm.em']->flush();         

                    StorageUtils::updateFoodCategoryPerc($app, $categoryId); 
                }
            }
            return true;
        }

        return false;
    }

    public function recalculateAllUsersAllFoodPercentages($app) {
        $query = $app['orm.em']->createQuery('SELECT u FROM Entities\User u');
        $users = $query->getResult();
        foreach($users as $user) {
            $needMultiplier = $user->getGoal() * ($user->getAdults() + ($user->getChildren()/2));
            $query = $app['orm.em']->
                createQuery('SELECT fc FROM Entities\Foodcategories fc where fc.fk_userId = :userId')->
                setParameters(array(
                    'userId' => $user->getId(),
                ));
            $foodCategories = $query->getResult();
            foreach($foodCategories as $foodCategory) {
                $categoryId = $foodCategory->getId();
                $query = $app['orm.em']->
                    createQuery('SELECT ft FROM Entities\Foodtypes ft where ft.foodCategory = :categoryId ORDER BY ft.id ASC')->
                    setParameters(array(
                        'categoryId' => $categoryId,
                    ));
                $foodTypes = $query->getResult();
                $currentFoodPercentage = 0;
                $userNeeds = 0;
                $userHas = 0;
                foreach($foodTypes as $foodType) {
                    $userNeeds = mround($foodType->getOneAdultNeedsMonth() * $needMultiplier);
                    $userHas = $foodType->getUserHas();
                    if ($userNeeds == 0) {
                        $userNeeds = 1;
                    }
                    // error_log(print_r('oneAdultNeedsMonth ------ '.$oneAdultNeedsMonth, TRUE), 0);
                    // error_log(print_r('userNeeds ------ '.$userNeeds, TRUE), 0);
                    $currentFoodPercentage = mround(($userHas/$userNeeds) * 100);
                    if ($currentFoodPercentage > 100) {
                        $currentFoodPercentage = 100;
                    }
                    $foodType->setAverageHave($currentFoodPercentage);
                    
                    $app['orm.em']->persist($foodType);
                    $app['orm.em']->flush();         

                    StorageUtils::updateFoodCategoryPerc($app, $categoryId); 
                }
            }
            error_log(print_r('completed updating ------ '.$user->getUserName(), TRUE), 0);
        }
    }

    public function addDefaultFoods($app, $userId) {
        $meas1 = $app['orm.em']->find('Entities\Measurements', 1);
        $meas2 = $app['orm.em']->find('Entities\Measurements', 2);
        $meas3 = $app['orm.em']->find('Entities\Measurements', 3);
        $meas4 = $app['orm.em']->find('Entities\Measurements', 4);

        $defaultCategories = array();
        $types = array();

        //add grains
        array_push($types, array('type'=>'Wheat','adultNeeds'=>'12.5','measurement'=>$meas1));
        array_push($types, array('type'=>'Flour','adultNeeds'=>'2.08','measurement'=>$meas1));
        array_push($types, array('type'=>'Cornmeal','adultNeeds'=>'2.08','measurement'=>$meas1));
        array_push($types, array('type'=>'Oats','adultNeeds'=>'2.08','measurement'=>$meas1));
        array_push($types, array('type'=>'Rice','adultNeeds'=>'4.16','measurement'=>$meas1));
        array_push($types, array('type'=>'Pasta','adultNeeds'=>'2.08','measurement'=>$meas1));
        array_push($defaultCategories, array('category'=>'Grains', 'image'=>'cat-grains', 'types'=>$types));

        //add fats and oils
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Vegetable Oil','adultNeeds'=>'.2','measurement'=>$meas2));
        array_push($types, array('type'=>'Shortening','adultNeeds'=>'.33','measurement'=>$meas1));
        array_push($types, array('type'=>'Mayonnaise','adultNeeds'=>'.2','measurement'=>$meas3));
        array_push($types, array('type'=>'Salad dressing','adultNeeds'=>'.083','measurement'=>$meas3));
        array_push($types, array('type'=>'Peanut butter','adultNeeds'=>'.33','measurement'=>$meas1));
        array_push($defaultCategories, array('category'=>'Fats and Oils', 'image'=>'cat-fats', 'types'=>$types));

        //add legumes
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Beans, dry','adultNeeds'=>'2.5','measurement'=>$meas1));
        array_push($types, array('type'=>'Lima beans','adultNeeds'=>'.416','measurement'=>$meas1));
        array_push($types, array('type'=>'Soy beans','adultNeeds'=>'.83','measurement'=>$meas1));
        array_push($types, array('type'=>'Split peas','adultNeeds'=>'.416','measurement'=>$meas1));
        array_push($types, array('type'=>'Lentils','adultNeeds'=>'.416','measurement'=>$meas1));
        array_push($types, array('type'=>'Dry soup mix','adultNeeds'=>'.416','measurement'=>$meas1));
        array_push($defaultCategories, array('category'=>'Legumes', 'image'=>'cat-legumes', 'types'=>$types));

        //add cooking items
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Baking powder','adultNeeds'=>'.083','measurement'=>$meas1));
        array_push($types, array('type'=>'Baking soda','adultNeeds'=>'.083','measurement'=>$meas1));
        array_push($types, array('type'=>'Yeast','adultNeeds'=>'.04','measurement'=>$meas1));
        array_push($types, array('type'=>'Salt','adultNeeds'=>'.416','measurement'=>$meas1));
        array_push($types, array('type'=>'Vinegar','adultNeeds'=>'.042','measurement'=>$meas2));
        array_push($defaultCategories, array('category'=>'Cooking items', 'image'=>'cat-cooking', 'types'=>$types));

        //add dairy
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Dry milk','adultNeeds'=>'5','measurement'=>$meas1));
        array_push($types, array('type'=>'Evaporated milk','adultNeeds'=>'1','measurement'=>$meas4));
        array_push($types, array('type'=>'Other dairy','adultNeeds'=>'1.083','measurement'=>$meas1));
        array_push($defaultCategories, array('category'=>'Dairy', 'image'=>'cat-dairy', 'types'=>$types));

        //add water
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Water','adultNeeds'=>'15.5','measurement'=>$meas2));
        array_push($types, array('type'=>'Bleach','adultNeeds'=>'.00195','measurement'=>$meas2));
        array_push($defaultCategories, array('category'=>'Water', 'image'=>'cat-water', 'types'=>$types));

        //add sugars
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Honey','adultNeeds'=>'.25','measurement'=>$meas1));
        array_push($types, array('type'=>'Sugar','adultNeeds'=>'3.33','measurement'=>$meas1));
        array_push($types, array('type'=>'Brown sugar','adultNeeds'=>'.25','measurement'=>$meas1));
        array_push($types, array('type'=>'Molasses','adultNeeds'=>'.083','measurement'=>$meas1));
        array_push($types, array('type'=>'Corn syrup','adultNeeds'=>'.25','measurement'=>$meas1));
        array_push($types, array('type'=>'Jams','adultNeeds'=>'.25','measurement'=>$meas1));
        array_push($types, array('type'=>'Fruit drink powder','adultNeeds'=>'.5','measurement'=>$meas1));
        array_push($types, array('type'=>'Flavored gelatin','adultNeeds'=>'.083','measurement'=>$meas1));
        array_push($defaultCategories, array('category'=>'Sugars', 'image'=>'cat-sugars', 'types'=>$types));
        
        foreach($defaultCategories as $dc) {
            $foodCategory = new Entities\Foodcategories();
            $foodCategory->setFoodCategory($dc['category']);
            $foodCategory->setImage($dc['image']);
            $foodCategory->setPercentage(0);
            $foodCategory->setFkUserId($userId);
            $app['orm.em']->persist($foodCategory);
            $app['orm.em']->flush();

            foreach($dc['types'] as $dt) {
                $foodType = new Entities\Foodtypes();
                $foodType->setFoodType($dt['type']);
                $foodType->setOneAdultNeedsMonth($dt['adultNeeds']);
                $foodType->setUserHas(0);
                $foodType->setAverageHave(0);
                $foodType->setMeasurement($dt['measurement']);
                $foodType->setFoodCategory($foodCategory);
                $foodType->setFkUserId($userId);
                $app['orm.em']->persist($foodType);
                $app['orm.em']->flush();
            }
        }
    }

    public function addDefaultSupplies($app, $userId) {
        $meas1 = $app['orm.em']->find('Entities\Measurements', 1);
        $meas2 = $app['orm.em']->find('Entities\Measurements', 2);
        $meas3 = $app['orm.em']->find('Entities\Measurements', 3);
        $meas4 = $app['orm.em']->find('Entities\Measurements', 4);
        $meas5 = $app['orm.em']->find('Entities\Measurements', 5);
        $meas6 = $app['orm.em']->find('Entities\Measurements', 6);
        $meas7 = $app['orm.em']->find('Entities\Measurements', 7);
        $meas8 = $app['orm.em']->find('Entities\Measurements', 8);
        $meas9 = $app['orm.em']->find('Entities\Measurements', 9);
        $meas10 = $app['orm.em']->find('Entities\Measurements', 10);

        $defaultSupplies = array();
        $types = array();

        // EXAMPLE - Each family member needs at least 1, but it NOT consumed monthly.
        // Is this item consumed monthly?
        //$consumedMonthly = 'false';
        // This is 0 if $consumedMonthly = true
        // or if each family member needs at least 1
        //$need = '0';
        // Does each family member need one?
        //$eachFamilyMember = 'true';
        // This is usually 0 if $consumedMonthly = false 
        // unless each family member needs at least 1, it is the number one person needs
        //$adultNeeds = '0';        

        //add tools
        array_push($types, array('type'=>'Whistle','consumedMonthly'=>'false','need'=>'0','eachFamilyMember'=>'true','adultNeeds'=>'1','measurement'=>$meas7));
        array_push($types, array('type'=>'Crowbar','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Medicine dropper','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Thread needle','consumedMonthly'=>'false','need'=>'5','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Thread','consumedMonthly'=>'false','need'=>'5','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas8));
        array_push($types, array('type'=>'Multi-tool','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Compass','consumedMonthly'=>'false','need'=>'2','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Matches','consumedMonthly'=>'false','need'=>'2','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas6));
        array_push($types, array('type'=>'Knife','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Rope','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Can opener','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Mess kit','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Duct tape','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas8));
        array_push($types, array('type'=>'Tire patch kit','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Local map','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Hammer','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($defaultSupplies, array('category'=>'Tools', 'image'=>'cat-tools', 'types'=>$types));

        //add first aid kit
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Scissors','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Thermometer','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Tweezers','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Sunscreen','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas9));
        array_push($types, array('type'=>'Soap','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas9));
        array_push($types, array('type'=>'Latex gloves, pair','consumedMonthly'=>'false','need'=>'2','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Moist towelettes','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas5));
        array_push($types, array('type'=>'Safety pins','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas6));
        array_push($types, array('type'=>'2" gauze','consumedMonthly'=>'false','need'=>'4','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'4" gauze','consumedMonthly'=>'false','need'=>'4','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Lubricant','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas9));
        array_push($types, array('type'=>'Band-aids','consumedMonthly'=>'false','need'=>'3','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas6));
        array_push($defaultSupplies, array('category'=>'First aid kit', 'image'=>'cat-firstaid', 'types'=>$types));

        //add essentials
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Battery radio','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Flashlight','consumedMonthly'=>'false','need'=>'2','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Batteries AA','consumedMonthly'=>'false','need'=>'20','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Batteries AAA','consumedMonthly'=>'false','need'=>'20','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Batteries D','consumedMonthly'=>'false','need'=>'20','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($types, array('type'=>'Ponchos','consumedMonthly'=>'false','need'=>'0','eachFamilyMember'=>'true','adultNeeds'=>'1','measurement'=>$meas7));
        array_push($types, array('type'=>'Sleeping bags','consumedMonthly'=>'false','need'=>'0','eachFamilyMember'=>'true','adultNeeds'=>'1','measurement'=>$meas7));
        array_push($types, array('type'=>'Flare','consumedMonthly'=>'false','need'=>'3','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($defaultSupplies, array('category'=>'Essentials', 'image'=>'cat-essentials', 'types'=>$types));

        //add medication
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Laxative','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas6));
        array_push($types, array('type'=>'Anti-diarrhea','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas6));
        array_push($types, array('type'=>'Pain reliever','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas6));
        array_push($types, array('type'=>'Anti-acid','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas6));
        array_push($types, array('type'=>'Activated charcoal','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas9));
        array_push($types, array('type'=>'Syrup of ipecac','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas9));
        array_push($defaultSupplies, array('category'=>'Medication', 'image'=>'cat-medication', 'types'=>$types));

        //add sanitation
        unset($types);
        $types = array();
        array_push($types, array('type'=>'Disinfectant','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas4));
        array_push($types, array('type'=>'Liquid soap','consumedMonthly'=>'false','need'=>'2','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas9));
        array_push($types, array('type'=>'Hand santizer','consumedMonthly'=>'false','need'=>'1','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas9));
        array_push($types, array('type'=>'Toilet paper','consumedMonthly'=>'true','need'=>'0','eachFamilyMember'=>'false','adultNeeds'=>'1','measurement'=>$meas8));
        array_push($types, array('type'=>'Garbage bags','consumedMonthly'=>'false','need'=>'3','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas6));
        array_push($types, array('type'=>'Plastic bucket','consumedMonthly'=>'false','need'=>'2','eachFamilyMember'=>'false','adultNeeds'=>'0','measurement'=>$meas7));
        array_push($defaultSupplies, array('category'=>'Sanitation', 'image'=>'cat-sanitation', 'types'=>$types));
        
        foreach($defaultSupplies as $ds) {
            $supplyCategory = new Entities\Supplycategories();
            $supplyCategory->setsupplyCategory($ds['category']);
            $supplyCategory->setImage($ds['image']);
            $supplyCategory->setPercentage(0);
            $supplyCategory->setFkUserId($userId);
            $app['orm.em']->persist($supplyCategory);
            $app['orm.em']->flush();

            foreach($ds['types'] as $dt) {
                $supplyType = new Entities\Supplytypes();
                $supplyType->setSupplyType($dt['type']);
                $supplyType->setConsumedMonthly($dt['consumedMonthly']);
                $supplyType->setNeed($dt['need']);
                $supplyType->setEachFamilyMember($dt['eachFamilyMember']);
                $supplyType->setOneAdultNeedsMonth($dt['adultNeeds']);
                $supplyType->setUserHas(0);
                $supplyType->setAverageHave(0);
                $supplyType->setMeasurement($dt['measurement']);
                $supplyType->setSupplyCategory($supplyCategory);
                $supplyType->setFkUserId($userId);
                $app['orm.em']->persist($supplyType);
                $app['orm.em']->flush();
            }
        }
    }
}
?>