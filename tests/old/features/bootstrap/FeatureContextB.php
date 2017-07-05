<?php

// @codingStandardsIgnoreFile
//use Behat\MinkExtension\Context\RestContext;
use Zitec\ApiZitecExtension\Context\RestContext;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Data\Data;
use Behat\Mink\Exception\ExpectationException;
use Behat\Gherkin\Node\TableNode;
/**
 * Features context.
 * @SuppressWarnings(PHPMD)
 */
class FeatureContextB extends RestContext {

    protected $debug = true;
    protected static $savedFromMailtrap = array();
    protected static $utilities = array();
    protected $exactFilters = array();
    protected $partialFilters = array();
    /**
     * @var Storage
     */
    protected $storage;
    /**
     * @var Data
     */
    protected $data;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct($parameters)
    {
        parent::__construct($parameters);
        $this->storage = Storage::getInstance();
        $this->data = Data::getInstance();
    }

    /**
     * Pauses the scenario until the user presses a key.
     * Useful when debugging a scenario.
     *
     * @Then /^(?:|I )put a breakpoint$/
     */
    public function iPutABreakpoint() {
        fwrite(STDOUT, "\033[s    \033[93m[Breakpoint] Press \033[1;93m[RETURN]\033[0;93m to continue...\033[0m");
        while (fgets(STDIN, 1024) == '') {

        }
        fwrite(STDOUT, "\033[u");

        return;
    }
    
    /**
     * @Given /^I login as (shopper|superadmin|company)$/
     * @Given /^I login as "([^"]*)"$/
     */
    public function iLoginAs($userType) {
        $type = 'login'; // this is used if the user should login or not.

        switch ($userType) {
            case "guest":
                $apiUser = "test_mobile";
                $apiKey = "abcdefghijklmn";
                $dataSet = "";
                $type = 'not_login';
                break;
            
            case "admin_guest":
                $apiUser = "test_admin";
                $apiKey = "abcdefghijklmn";
                $dataSet = "";
                $type = 'not_login';
                break;

            case "superadmin":
                $apiUser = "test_admin";
                $apiKey = "abcdefghijklmn";
                $dataSet = "cosquare_superadmin";
                break;
            case "shopper":
                $apiUser = "test_mobile";
                $apiKey = "abcdefghijklmn";
                $dataSet = "cosquare_shopper";
                break;
            case "company":
                $apiUser = "test_admin";
                $apiKey = "abcdefghijklmn";
                $dataSet = "cosquare_company";
                break;
            case "cosquare_gifter":
                $apiUser = "test_mobile";
                $apiKey = "abcdefghijklmn";
                $dataSet = "cosquare_gifter";
                break;
            case "cosquare_giftee":
                $apiUser = "test_mobile";
                $apiKey = "abcdefghijklmn";
                $dataSet = "cosquare_giftee";
                break;
            case "mailman":
                $apiUser = "test_mobile";
                $apiKey = "abcdefghijklmn";
                $dataSet = "cosquare_addresses";
                break;
            case "company_nexus":
                $apiUser = "test_admin";
                $apiKey = "abcdefghijklmn";
                $dataSet = "company_nexus";
                break;
            case "company_nexus_api":
                $apiUser = "nexus_7e4e5801f1cc";
                $apiKey = "b853596a24780256b87d7edb7484f39579fbab9740709cda72c42e446c443459";
                $dataSet = "";
                $type = 'not_login';
                break;
            case "company_nikon":
                $apiUser = "test_admin";
                $apiKey = "abcdefghijklmn";
                $dataSet = "company_nikon";
                break;
            default:
                throw new Exception("You cannot login as " . $userType);
        }

        if($type=='login') {
            $this->iSetTheApiKeyAndApiuser($apiKey, $apiUser);
            $this->iSetTheRequestMethod("POST");
            $this->iResetTheAccessTokens();
            $this->iLoadDataFromFile("login");
            $this->iRequest("access-tokens", $dataSet);
            $this->extractAccessTokenFromResponse();
        }
        if($type == 'not_login')
        {
            $this->iSetTheApiKeyAndApiuser($apiKey, $apiUser);
            $this->iResetTheAccessTokens();
        }
    }

    /**
     * @Then /^get a random address from user$/
     */
    public function getARandomAddressFromUser()
    {
        $this->iSetTheRequestMethod('GET');
        $this->iRequest('/addresses');
        $response = json_decode($this->storage->getLastResponse(), true);

        if (!empty($response['data'])) {
            $rand = array_rand($response['data']);
            $address = $response['data'][$rand];
            $id = $address['id'];
        } else {
            throw new ExpectationException("There are no addresses for this user.", $this->getSession()->getDriver());
        }
        $this->storage->storeValue('address_id', $id);
    }

    /**
     * @Given /^i get a random country$/
     */
    public function iGetARandomCountry()
    {
        $this->iSetTheRequestMethod('GET');
        $this->iRequest('/countries');
        $response = json_decode($this->storage->getLastResponse(), true);

        if (!empty($response['data'])) {
            $rand = array_rand($response['data']);
            $country = $response['data'][$rand];
            $id = $country['id'];
        } else {
            throw new ExpectationException("There are no countries for this user.", $this->getSession()->getDriver());
        }
        $this->storage->storeValue('country_id', $id);
    }

    /**
     * @Given /^I check there are (10) suggested entities$/
     */
    public function iCheckThereAre10SuggestedEntities($records)
    {
        $response = $this->storage->getLastResponse();
        if (count($response['users']) > $records) {
            throw new Exception('There are too many users returned.');
        }

        if (count($response['companies']) > $records) {
            throw new Exception('There are too many companies returned.');
        }
    }

    /**
     * @Then /^I get a random shopper$/
     */
    public function iGetARandomShopper()
    {
        $this->iSetTheRequestMethod('GET');
        $this->iRequest('/users');
        $response = json_decode($this->storage->getLastResponse(), true);

        if (!empty($response['data'])) {
            $rand = array_rand($response['data']);
            $users = $response['data'][$rand];
            $id = $users['id'];
        } else {
            throw new ExpectationException("There are no users found.", $this->getSession()->getDriver());
        }
        $this->storage->storeValue('user_id', $id);
    }


    /**
     * @Given /^I use "([^"]*)" from storage as "([^"]*)" in dataset "([^"]*)"$/
     */
    public function iUseFromStorageAsInDataset($storageKey, $key, $dataset)
    {
        if (!empty($this->storage->getValue($storageKey))) {
            $value = $this->storage->getValue($storageKey);
        } else {
            throw new Exception('The key ' . $storageKey . ' is not set.');
        }

        $values = array($key => $value );

        $this->data->addDataToDataSet($dataset, $values);

    }

    /**
     * @Then /^I get a random product review(?:| from company with email "([^"]*)")$/
     * @Then /^I get a random product review from (cosquare_company)$/
     */
    public function iGetARandomProductReview($company)
    {
        switch($company){
            case "cosquare_company":
                $company_email='company@cosquare.com';
                $login_user='cosquare_company';
                break;
            default:
                throw new Exception("Log in details for company $company are not found.");
        }
        $this->iLoginAs($login_user);
        $this->searchCompanyByEmailReturnCompanyID($company_email);
        $this->iSetTheRequestMethod('GET');
        $company_id=$this->storage->getValue('company_id');
        $this->iRequest("/companies/$company_id/reviews");
        $response = json_decode($this->storage->getLastResponse(), true);

        if (!empty($response['data'])) {
            $rand = array_rand($response['data']);
            $reviews = $response['data'][$rand];
            $id = $reviews['id'];
        } else {
            throw new ExpectationException("There are no reviews found for company with email $company.", $this->getSession()->getDriver());
        }
        $this->storage->storeValue('review_id', $id);
    }

    public function searchCompanyByEmailReturnCompanyID($company_email)
    {
        $this->iSetTheRequestMethod('GET');
        $this->iRequest('/companies?email='.$company_email);

        $response = json_decode($this->storage->getLastResponse(), true);

        if (!empty($response['data'])) {
            $rand = array_rand($response['data']);
            $companies = $response['data'][$rand];
            $id = $companies['id'];
        } else {
            throw new ExpectationException("There are no companies with email $company_email found.", $this->getSession()->getDriver());
        }
        $this->storage->storeValue('company_id', $id);
    }

    /**
     * @Given /^I get a random store$/
     */
    public function iGetARandomStore()
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }

    /**
     * @Then /^(?:|I )search for a random entity from (users|addresses|countries|stores)$/
     *  @Then /^(?:|I )search for a random entity from "([^"]*)"$/
     */
    public function searchRandomStuff($entity)
    {

        $details = $this->storedMethodDetails($entity);
        $this->getRandomStuff($details);
    }


    public function storedMethodDetails($request)
    {
        $request_details= array(
            'addresses' => array('request'=>"/addresses",'key_from_response'=>'id','value_to_be_stored'=>'address_id','error_message'=>'There are no addresses found for this user.'),
            'users'     => array('request'=>"/users"    ,'key_from_response'=>'id','value_to_be_stored'=>'user_id',   'error_message'=>'There are no users found.'),
            'countries' => array('request'=>"/countries",'key_from_response'=>'id','value_to_be_stored'=>'country_id','error_message'=>'There are no countries found.'),
            'stores'    => array('request'=>"/stores"   ,'key_from_response'=>'id','value_to_be_stored'=>'store_id',  'error_message'=>'There are no stores found for this company.'),
            'company'   => array('request'=>"/companies?email=zitec@cosquare.com",'key_from_response'=>'id','value_to_be_stored'=>'company_id','error_message'=>'There are no companies found.'),
//            'company_countries' => array('request'=>"/companies/".$this->storage->getValue("company_id")."/countries",'key_from_response'=>'id','value_to_be_stored'=>'country_id','error_message'=>'There are no countries found found.')
        );
        return $request_details[$request];
    }


    public function getRandomStuff($array)
    {
        $this->iSetTheRequestMethod('GET');
        $this->iRequest($array['request']);
        $response = json_decode($this->storage->getLastResponse(), true);

        if (!empty($response['data'])) {
            $rand = array_rand($response['data']);
            $random_data = $response['data'][$rand];
        } else {
            throw new ExpectationException($array['error message'], $this->getSession()->getDriver());
        }
        $this->storage->storeValue($array['value_to_be_stored'], $random_data[$array['key_from_response']]);
    }

    /**
     * @Then /^I search for a random (country|shipping_option) from "([^"]*)"$/
     */
    public function iSearchForARandomCountryFrom($entity,$arg)
    {
        if (empty($this->storage->getValue("company_id")))
        {throw new ExpectationException("The 'company_id' is not set.", $this->getSession()->getDriver());}
        $request='/companies/'.$this->storage->getValue("company_id");

        switch($entity){
            case "country":
                $request .= "/countries";
                $r_key = 'country_id';
                $storage_key = 'country_id';
                break;
            case "shipping_option":
                $request .= "/shipping-options";
                $r_key = 'id';
                $storage_key = 'shipping_option_id';
                break;
            default:
                throw new Exception("'".$entity."' not found.");
        }

        $this->iSetTheRequestMethod('GET');
        $this->iRequest($request);
        $response = json_decode($this->storage->getLastResponse(), true);

        if (!empty($response['data'])) {
            $rand = array_rand($response['data']);
            $random = $response['data'][$rand];
            $id = $random[$r_key];
        } else {
            throw new ExpectationException("There are no $request found for this company.", $this->getSession()->getDriver());
        }
        $this->storage->storeValue($storage_key, $id);
    }

    /**
     * @Given /^I save the "([^"]*)" key from response as "([^"]*)"$/
     */
    public function iSaveTheAs($responseKey, $varKey)
    {
        /**
         * $responseKey is the key of the element from the response you want to save.
         * $varKey is the key of the array where you'll store the response
         */
        $response = $this->storage->getLastResponse();
        $this->storage->storeValue($varKey, $response[$responseKey]);
    }

    /**
     * @When /^I create (\d+) requests to "([^"]*)" with dataset "([^"]*)"$/
     */
    public function iRequestsToWithDataset($numberOfRequests, $requestUrl, $dataset)
    {
        for($i=0; $i<=$numberOfRequests;$i++)
        {
            $this->iLoadDataFromFile("generate_data");
            $this->iRequest($requestUrl,$dataset);
            print 'ad';
            print $this->getSession()->getStatusCode();
//            var_dump("asd",$this->getSession()->getStatusCode());
        }

    }


}
