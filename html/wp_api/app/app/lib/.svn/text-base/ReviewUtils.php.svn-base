<?php
use Doctrine\ORM\Query\ResultSetMapping;

if (!class_exists("Entities\Review", false)) {
    require_once __DIR__."/../bootstrap_models.php";
}

class ReviewUtils {
    
    public function getReviews($app) {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Entities\Review', 'r');
        $rsm->addFieldResult('r', 'id', 'id');
        $rsm->addFieldResult('r', 'name', 'name');
        $rsm->addFieldResult('r', 'review', 'review');

        $query = $app['orm.em']->createNativeQuery('SELECT id, name, review FROM reviews ORDER BY RAND() LIMIT 3', $rsm);
        try {
            $reviews = $query->getResult();

            $convertedReviews = array(); 
            foreach($reviews as $obj)
            {
                $array = array();
                $array['name']=$obj->getName();
                $array['review']=$obj->getReview();
                array_push($convertedReviews, $array);
            }
            // error_log(print_r($reviews, TRUE), 0);
        } catch (Doctrine\ORM\NoResultException $e) {
            return null;
        }
        return $convertedReviews;
    }
}
?>