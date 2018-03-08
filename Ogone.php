<?php
namespace App\Libraries\Famous\Ogone;


use Carbon\Carbon;

class Ogone
{

    protected $pspid;
    protected $shaIn;
    protected $shaOut;
    protected $paymentMethod;
    protected $isReccurent = false;
    protected $period;
    protected $isTest = false;
    protected $orderId;
    protected $userFullName;
    protected $userEmail;
    protected $userAddress;
    protected $userZipcode;
    protected $userTown;
    protected $amount;
    protected $inputs = [];

    const PAYMENT_METHOD_BANCONTACT = 'bancontact';
    const PAYMENT_METHOD_VISA = 'visa';
    const PAYMENT_METHOD_MASTERCARD = 'mastercard';

    const PERIOD_MONTH = 'm';
    const PERIOD_DAYLY   = 'd';
    const PERIOD_WEEKLY = 'ww';

    const TEST_URL = 'https://secure.ogone.com/ncol/test/orderstandard_utf8.asp';
    const LIVE_URL = 'https://secure.ogone.com/ncol/prod/orderstandard_utf8.asp';

    public function __construct($config = array()) {

        if(isset($config['pspsid'])) {
            $this->pspid = $config['pspid'];
        } else {
            $this->pspid = env('OGONE_PSPID');
        }

        if(isset($config['shaIn'])) {
            $this->shaIn = $config['shaIn'];
        } else {
            $this->shaIn = env('OGONE_SHAIN');
        }

        if(isset($config['shaOut'])) {
            $this->shaOut = $config['shaOut'];
        } else {
            $this->shaOut = env('OGONE_SHAOUT');
        }

        if( strpos(env('APP_ENV'),'prod') !== FALSE) {
            $this->isTest = true;
        }

        return $this;

    }

    public function getForm() {

        $this->generateData();

        $html = '<form id="ogone-form-'.$this->orderId.'" method="post" action="'. ($this->isTest ? self::TEST_URL : self::LIVE_URL).'">';

        foreach($this->inputs as $name => $value) {
            $html .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
        }

        $html .= '<input id="paymentsubmit" type="submit" value="Pay" /></form>';
        $html .= '<script type="text/javascript">window.document.forms["ogone-form-'.$this->orderId.'"].submit()</script>';

        return $html;
    }

    protected function generateData() {

        $this->inputs = [
            'PSPID'         => $this->pspid,
            'CURRENCY'      => 'EUR',
            'LANGUAGE'      => \LaravelLocalization::getCurrentLocale(),
            'PMLISTTYPE'    => 2,
            'ORDERID'       => $this->orderId,
            'AMOUNT'        => $this->amount,
            'CN'            => $this->userFullName,
            'EMAIL'         => $this->userEmail,
            'OWNERADDRESS'  => $this->userAddress,
            'OWNERZIP'      => $this->userZipcode,
            'OWNERTOWN'     => $this->userTown,
            'OWNERCTY'      => 'Belgium',
            'ACCEPTURL'     => route('ogone.thanks'),
            'DELINEURL'     => route('ogone.cancel'),
            'EXCEPTIONURL'  => route('ogone.cancel'),
            'CANCELURL'     => route('ogone.cancel'),
            'PM'            => $this->getPaymentMethod('PM'),
            'BRAND'         => $this->getPaymentMethod('BRAND'),
        ];

        if($this->isReccurent && !empty($this->period)) {
            $this->inputs = $this->inputs + [

                    'SUBSCRIPTION_ID'   => 'RECCURENT-'.$this->orderId,
                    'SUB_ORDERID'       => 'RECCURENT-'.$this->orderId,
                    'AMOUNT'            => $this->amount,
                    'SUB_AMOUNT'        => $this->amount,
                    'SUB_PERIOD_UNIT'   => $this->period,
                    'SUB_PERIOD_MOMENT' => $this->period == self::PERIOD_MONTH ?  date('j') : date('N'),
                    'SUB_STATUS'        => 1,
                    'SUB_PERIOD_NUMBER' => 1,
                    'SUB_STARTDATE'     => $this->period == self::PERIOD_MONTH ? Carbon::now()->addMonth(1)->toDateString() : Carbon::now()->addWeek(1)->toDateString(),
                ];
        }

        $this->inputs['SHASIGN'] = $this->calculateSha();

        return $this;
    }

    public function checkShaIn($data = array()) {

        $shaIn = $data['SHASIGN'];
        unset($data['SHASIGN']);
        $this->inputs = $data;
        $sha = strtoupper($this->calculateSha());

        return ($shaIn == $sha);
    }

    protected function calculateSha() {
        $inputCopy = [];
        $inputs = $this->inputs;
        ksort($inputs);
        $inputs = array_change_key_case($inputs, CASE_UPPER);

        foreach($inputs as $k=>$value) {
            if(isset($value) && !is_null($value) && !empty($value)) {
                $inputCopy[] = strtoupper($k).'='.$value;
            }
        }

        $toSha = implode($this->shaIn, $inputCopy) . $this->shaOut;
        return sha1($toSha);
    }

    public function getPaymentMethods() {
        return [self::PAYMENT_METHOD_BANCONTACT, self::PAYMENT_METHOD_MASTERCARD, self::PAYMENT_METHOD_VISA];
    }

    protected function getPaymentMethod($key) {
        if(!in_array($this->paymentMethod, $this->getPaymentMethods())) {
            Throw new \Exception('Payment method' . $this->paymentMethod . ' invalid');
        }

        $paymentData = [
            self::PAYMENT_METHOD_BANCONTACT => [
                'PM'    => 'CreditCard',
                'BRAND' => 'Bancontact/Mister Cash'
            ],
            self::PAYMENT_METHOD_VISA => [
                'PM'    => 'CreditCard',
                'BRAND' => 'VISA'
            ],
            self::PAYMENT_METHOD_MASTERCARD => [
                'PM'    => 'CreditCard',
                'BRAND' => 'MASTERCARD'
            ]
        ];

        return $paymentData[$key];
    }

    public function setPaymentMethod($method) {
        if(!in_array($method, $this->getPaymentMethods())) {
            Throw new \Exception('Payment method' . $method . ' invalid');
        }

        $this->paymentMethod = $method;
        return $this;
    }

    public function setRecurrent( $period = self::PERIOD_MONTH) {
        $this->isReccurent = true;
        $this->period = $period;
        return $this;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
        return $this;
    }

    public function setUserFullName($userFullName)
    {
        $this->userFullName = $userFullName;
        return $this;
    }

    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function setUserAddress($userAddress)
    {
        $this->userAddress = $userAddress;
        return $this;
    }

    public function setUserZipcode($userZipcode)
    {
        $this->userZipcode = $userZipcode;
        return $this;
    }

    public function setUserTown($userTown)
    {
        $this->userTown = $userTown;
        return $this;
    }

    public function setAmount($amount) {
        $this->amount = str_replace(',','.', $amount)*100;
        return $this;
    }


}
