<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\Famous\Ogone\Ogone;
use Illuminate\Http\Request;

class OgoneController extends Controller
{

    public function redirectToOgone(Request $request) {

        try {
            $ogone = new Ogone();
            $ogone->setAmount(10)
                ->setOrderId(uniqid())
                ->setPaymentMethod(Ogone::PAYMENT_METHOD_VISA)
                ->setUserEmail('user@email.com')
                ->setUserFullName('Jean dupont');

            echo $ogone->getForm();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        die;
    }

    public function thanks(Request $request) {

    }

    public function cancel(Request $request) {

    }

    public function callback(Request $request) {

        /**
         * In case of recurrent payement, the only way to know is to check if the PAYID already exists (matching with a DB)
         *
         *
         */

        $ogone = new Ogone();

        if($ogone->checkShaIn( $request->all())) {

            $orderId = $request->get('orderID');
            $payId = $request->get('PAYID');
            $status = $request->get('STATUS');

            if (!in_array($status, [5, 9])) {
                die('ERROR');
            }

        }

        die('ERROR INVALID SHASIGN');
    }


}
