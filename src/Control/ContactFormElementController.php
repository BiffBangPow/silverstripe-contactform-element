<?php

namespace BiffBangPow\Element\Control;

use BiffBangPow\Element\ContactFormElement;
use BiffBangPow\Element\Helper\ContactCaptchaHelper;
use DNADesign\Elemental\Controllers\ElementController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\HTML;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;


class ContactFormElementController extends ElementController
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'ContactForm',
        'xhr'
    ];


    protected function init()
    {
        parent::init();
        $themeCSS = ThemeResourceLoader::inst()->findThemedCSS('client/dist/css/elements/contactform');
        if ($themeCSS) {
            Requirements::css($themeCSS, '', ['defer' => true]);
        }

        $useDefaultJS = ContactFormElement::config()->get('use_default_js');
        $useDefaultCSS = ContactFormElement::config()->get('use_default_css');
        if ($useDefaultJS === true) {

            Requirements::javascript('biffbangpow/silverstripe-contactform-element:client/dist/javascript/contactform.js', [
                'type' => false,
                'defer' => true
            ]);

            Requirements::customScript($this->getCallbackScript());

            Requirements::javascript('https://www.google.com/recaptcha/api.js', [
                'type' => false,
                'defer' => true
            ]);
        }
        if ($useDefaultCSS === true) {
            Requirements::css('biffbangpow/silverstripe-contactform-element:client/dist/css/contactform.css');
        }
    }

    private function getCallbackScript()
    {
        $template = 'function submitform%s(token) { submitContactForm(token, "Form_ContactForm-%s"); }';
        return sprintf($template, $this->ID, $this->ID);
    }


    /**
     * @return Form
     */
    public function ContactForm()
    {
        $config = SiteConfig::current_site_config();
        $title = $config->Title;

        $linkHTML = 'By ticking this box you consent to ' . $title . ' contacting you with regards to your enquiry, and are agreeing to the privacy policy';

        $fields = FieldList::create([
            TextField::create('Name')->addExtraClass('col-12 contact-name'),
            EmailField::create('Email')->addExtraClass('col-12 contact-email'),
            TextField::create('PhoneNumber', 'Phone Number')->addExtraClass('col-12 contact-phone'),
            TextareaField::create('Enquiry')->setRows(5)->addExtraClass('col-12 contact-enquiry'),
            CheckboxField::create('ContactConsent', $linkHTML)->addExtraClass('contact-consent'),
            LiteralField::create('recaptcha', HTML::createTag('div', [
                'class' => 'g-recaptcha',
                'data-sitekey' => Environment::getEnv('CAPTCHA_SITE_KEY'),
                'data-size' => 'invisible',
                'data-callback' => 'submitform' . $this->ID,
                'id' => 'captchafield-' . $this->ID
            ]))
        ]);

        $actions = FieldList::create(
            FormAction::create('sendContactForm', 'Submit')
                ->addExtraClass('btn-primary enable-after-recaptcha'),
            LiteralField::create('Message', HTML::createTag('div', [
                'class' => 'form-message py-3'
            ], ''))
        );

        $form = Form::create(
            $this,
            __FUNCTION__ . '-' . $this->ID,
            $fields,
            $actions,
            new RequiredFields([
                    'Name',
                    'PhoneNumber',
                    'Email',
                    'Enquiry',
                    'ContactConsent',
                ]
            ));

        $form->setAttribute('data-formid', $this->ID);


        $current = Controller::curr();
        $form->setFormAction(
            Controller::join_links(
                $current->Link(),
                'element',
                $this->owner->ID,
                'xhr'
            )
        );

        $form->setAttribute('novalidate', true);

        return $form;
    }

    /**
     * @param $data
     * @param Form $form
     * @return HTTPResponse
     */
    public function sendContactForm($data, $form)
    {
        //The only time we get here is if someone submits to the form endpoint directly, which they shouldn't
        //do, so they can sod off
        return $this->httpError(404);
    }


    public function xhr(HTTPRequest $request)
    {
        if (!$request->isAjax()) {
            return $this->httpError(404);
        }

        //Check the security ID
        $tokenCheck = SecurityToken::inst()->checkRequest($request);
        if (!$tokenCheck) {
            return [
                'success' => false,
                'message' => 'Sorry, there was a problem, please refresh the page and try again',
                'debug' => (Director::isDev()) ? 'Token error' : ''
            ];
        }

        //Check the captcha
        $check = ContactCaptchaHelper::validateRequestSecurity($request, 'submit');
        if ($check !== true) {
            return json_encode([
                'success' => false,
                'message' => $check['message'],
                'debug' => $check['debug']
            ]);
        }

        $data = $request->postVars();


        //Do we have consent
        if ((!$data['ContactConsent'] === '1') && (!$data['ContactConsent'] === 1)) {
            return json_encode([
                'success' => false,
                'message' => 'Your enquiry has not been sent, we cannot process your data without your consent',
                'debug' => $check['debug']
            ]);
        }

        //Send the message

        try {

            $config = SiteConfig::current_site_config();
            $recipient = $config->ContactEmail;
            $from = $config->ContactFromEmail;

            $email = Email::create();
            $email->setHTMLTemplate('BiffBangPow/Emails/ContactFormEmail');
            $email->setFrom($from, $config->Title);
            $email->setReplyTo($data['Email']);
            $email->setTo($recipient);
            $email->setSubject('Contact form has been filled in');
            $email->setData($data);
            $email->send();

            return json_encode([
                'success' => true,
                'message' => _t(__CLASS__ . '.sentOK', 'Thank you for your message.  We will respond as soon as possible')
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => 'Sorry, there was a problem, please refresh the page and try again',
                'debug' => (Director::isDev()) ? $e->getMessage() : ''
            ]);
        }

    }

}
