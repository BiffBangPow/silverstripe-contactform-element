<?php

namespace BiffBangPow\Element;

use BiffBangPow\Element\Control\ContactFormElementController;
use DNADesign\Elemental\Models\BaseElement;

class ContactFormElement extends BaseElement
{
    /**
     * @var string
     */
    private static $table_name = 'ElementContactForm';
    private static $singular_name = 'Contact form element';
    private static $plural_name = 'Contact form elements';
    private static $description = 'A simple contact form';
    private static $controller_class = ContactFormElementController::class;
    private static $use_default_js = true;
    private static $use_default_css = true;

    public function ContactForm()
    {
        return $this->getController()->ContactForm();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'Contact Form';
    }

    public function getSimpleClassName()
    {
        return 'bbp-contact-form-element';
    }
}
