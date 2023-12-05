<?php

namespace BiffBangPow\Element\Control;

use DNADesign\Elemental\Controllers\ElementController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;


class ContactFormElementController extends ElementController
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'ContactForm',
    ];


    protected function init()
    {
        parent::init();
        $themeCSS = ThemeResourceLoader::inst()->findThemedCSS('client/dist/css/elements/contactform');
        if ($themeCSS) {
            Requirements::css($themeCSS, '', ['defer' => true]);
        }
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
            TextField::create('Name')->addExtraClass('col-12'),
            TextField::create('PhoneNumber', 'Phone Number')->addExtraClass('col-12'),
            EmailField::create('Email')->addExtraClass('col-12'),
            TextareaField::create('Enquiry')->setRows(5)->addExtraClass('col-12'),
            CheckboxField::create('ContactConsent', $linkHTML),
        ]);

        $actions = FieldList::create(
            FormAction::create('sendContactForm', 'Submit')
                ->addExtraClass('btn-primary enable-after-recaptcha')
        );

        $form = Form::create(
            $this,
            __FUNCTION__,
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

        $form->enableSpamProtection();

        $current = Controller::curr();
        $form->setFormAction(
            Controller::join_links(
                $current->Link(),
                'element',
                $this->owner->ID,
                'ContactForm'
            )
        );

        return $form;
    }

    /**
     * @param $data
     * @param Form $form
     * @return HTTPResponse
     */
    public function sendContactForm($data, $form)
    {
        $config = SiteConfig::current_site_config();
        $recipient = $config->ContactEmail;
        $from = $config->ContactFromEmail;

        $data = $form->getData();

        if ($data['ContactConsent'] === '1' || $data['ContactConsent'] === 1) {

            $email = Email::create();
            $email->setHTMLTemplate('BiffBangPow/Emails/ContactFormEmail');
            $email->setFrom($from);
            $email->setReplyTo($data['Email']);
            $email->setTo($recipient);
            $email->setSubject('Contact form has been filled in');
            $email->setData($data);
            $email->send();

            $this->flashMessage('Thank you for your enquiry, we will be in touch soon', 'good');
        } else {
            $this->flashMessage('Your enquiry has not been sent, we cannot process your data without your consent', 'danger');
        }

        $link = $current = Controller::curr()->Link();

        // Using get page is better as it does not include the id of the element
        if ($this->hasMethod('getPage')) {
            if ($page = $this->getPage()) {
                $link = $page->Link();
            }
        }

        return $this->redirect($link . '#contact-form');
    }

}
