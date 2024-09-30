<?php

namespace BiffBangPow\Element\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;

class CaptchaHelper
{
    use Configurable;

    private static string $verify_endpoint = 'https://www.google.com/recaptcha/api/siteverify';
    private static float $minimum_score = 0.3;


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

        if (Director::isDev()) {
            Injector::inst()->get(LoggerInterface::class)->info(print_r($captchaResponse, true));
        }


        //Check that our captcha worked
        if ($captchaResponse['status'] !== 1) {
            return json_encode([
                'status' => 5,
                'debug' => (Director::isDev()) ? $captchaResponse['message'] : '',
                'message' => _t(__CLASS__ . '.captchaError', 'Sorry, there was a problem.  Please refresh the page and try again')
            ]);
        }

        //Check that we have a good response
        //A score of 0, an explicit fail or an incorrect action gets nowhere... give them a meaningless error
        $gRes = $captchaResponse['response'];
        if (($gRes['success'] != 1) || ($gRes['score'] == 0)) {
            return json_encode([
                'status' => 4,
                'message' => _t(__CLASS__ . '.captchaFailed', 'Sorry, there was a problem.  Please refresh the page and try again'),
                'debug' => (Director::isDev()) ? print_r($captchaResponse, true) : ''
            ]);
        }

        //A score below the threshold is suspect, give them a nice message, but still fail them
        $threshold = self::config()->get('min_threshold');
        if ($gRes['score'] < $threshold) {
            return json_encode([
                'status' => 4,
                'message' => _t(
                    __CLASS__ . '.captchaSuspect',
                    'Sorry, there was an issue with your request.  Please contact us ({Email}) and we will assist you.',
                    [
                        'Email' => SiteConfig::current_site_config()->ContactEmail
                    ]
                )
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
