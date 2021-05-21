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

    private static $singular_name = 'contact form element';

    private static $plural_name = 'contact form elements';

    private static $description = 'A simple contact form';

    private static $controller_class = ContactFormElementController::class;

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
        return 'contact-form-element';
    }
}
