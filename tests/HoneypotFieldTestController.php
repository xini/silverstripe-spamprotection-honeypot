<?php

namespace Innoweb\SpamProtectionHoneypot\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\View\SSViewer;

/**
 * @skipUpgrade
 */
class HoneypotFieldTestController extends Controller implements TestOnly
{
    public function __construct()
    {
        parent::__construct();
        if (Controller::has_curr()) {
            $this->setRequest(Controller::curr()->getRequest());
        }
    }

    private static $allowed_actions = ['Form'];

    private static $url_handlers = [
        '$Action//$ID/$OtherID' => "handleAction",
    ];

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links(
            'HoneypotFieldTest_Controller',
            $this->getRequest()->latestParam('Action'),
            $this->getRequest()->latestParam('ID'),
            $action
        );
    }

    public function Form()
    {
        $form = new Form(
            $this,
            'Form',
            new FieldList(
                new EmailField('Email'),
                new TextField('SomeField')
            ),
            new FieldList(
                FormAction::create('doSubmit'),
            ),
            new RequiredFields(
                'Email'
            )
        );
        $form->disableSecurityToken(); // Disable CSRF protection for easier form submission handling
        $form->enableSpamProtection();

        return $form;
    }

    public function doSubmit($data, $form, $request)
    {
        $form->sessionMessage('Test save was successful', 'good');
        return $this->redirectBack();
    }

    public function getViewer($action = null)
    {
        return new SSViewer('BlankPage');
    }
}
