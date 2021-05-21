<?php

namespace BiffBangPow\Element\MyElement\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class ContactFormConfigExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'ContactEmail' => 'Varchar(200)',
        'ContactFromEmail' => 'Varchar(200)'
    ];

    /**
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.Contact', [
            TextField::create('ContactEmail'),
            TextField::create('ContactFromEmail'),
        ]);
    }
}