<?php

namespace BiffBangPow\Element\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;

class ContactCaptchaHelper
{
    use Configurable;

    private static string $verify_endpoint = 'https://www.google.com/recaptcha/api/siteverify';
    

    /**
     * Gets the recaptcha key from the environment
     * @return mixed
     */
    public static function getRecaptchaKey()
    {
        return Environment::getEnv('CAPTCHA_SITE_KEY');
    }


    /**
     * Check the security token and captcha for the request
     * @param $request
     * @param $captchaAction
     * @return bool|string
     * @throws GuzzleException
     */
    public static function validateRequestSecurity($request, $captchaAction)
    {

        //Now check the captcha
        $captchaToken = $request->postVar('g-recaptcha-response');
        $captchaResponse = self::verifyCaptcha($captchaToken);

        //Check that our captcha worked
        if ($captchaResponse['status'] !== 1) {
            return json_encode([
                'status' => false,
                'debug' => (Director::isDev()) ? $captchaResponse['message'] : '',
                'message' => _t(__CLASS__ . '.captchaError', 'Sorry, there was a problem.  Please refresh the page and try again')
            ]);
        }

        //Check that we have a good response
        //A score of 0, an explicit fail or an incorrect action gets nowhere... give them a meaningless error
        $gRes = $captchaResponse['response'];
        if ($gRes['success'] != 1) {
            return json_encode([
                'status' => false,
                'message' => _t(__CLASS__ . '.captchaFailed', 'Sorry, there was a problem.  Please refresh the page and try again'),
                'debug' => (Director::isDev()) ? print_r($captchaResponse, true) : ''
            ]);
        }

        return true;
    }

    /**
     * Checks the captcha token with Google
     * Returns an array of the response data, or complains
     * @param $token
     * @return array
     * @throws GuzzleException
     */
    public static function verifyCaptcha($token): array
    {
        try {
            $client = new Client();
            $response = $client->post(self::config()->get('verify_endpoint'), [
                'form_params' => [
                    'secret' => Environment::getEnv('CAPTCHA_SECRET_KEY'),
                    'response' => $token
                ]
            ]);

            if ($response->getStatusCode() < 300) {
                $body = $response->getBody();
                $responseParams = json_decode($body, true);

                if ($responseParams !== false) {
                    return [
                        'status' => 1,
                        'message' => 'OK',
                        'response' => $responseParams
                    ];
                }

                return [
                    'status' => -1,
                    'message' => 'Error decoding response - ' . print_r($responseParams, true)
                ];

            }

            return [
                'status' => -1,
                'message' => 'HTTP request failed.  Error code ' . $response->getStatusCode()
            ];
        } catch (ClientException $e) {
            return [
                'status' => -1,
                'message' => $e->getMessage()
            ];
        }
    }
}
