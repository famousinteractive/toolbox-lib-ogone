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
        //TODO
    }


}
