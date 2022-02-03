<?php

namespace Innoweb\SpamProtectionHoneypot\Tests;

use Innoweb\SpamProtectionHoneypot\FormField\HoneypotField;
use Innoweb\SpamProtectionHoneypot\SpamProtector\HoneypotSpamProtector;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;
use SilverStripe\SpamProtection\Extension\FormSpamProtectionExtension;

class HoneypotFieldTest extends FunctionalTest
{
    protected static $extra_controllers = [
        HoneypotFieldTestController::class,
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Config::inst()->update(FormSpamProtectionExtension::class, 'default_spam_protector', HoneypotSpamProtector::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // wait between tests to make sure file system is ready for cache calls. windows weirdness...
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            sleep(3);
        }
    }

    public function testFieldValues()
    {
        $form = $this->getForm();

        $fields = $form->Fields();
        $this->assertEquals(_t(HoneypotField::class . '.DefaultValue', 'Please leave this as is.'), $fields->dataFieldByName('Captcha[_val]')->Value());
        $this->assertIsInt($fields->dataFieldByName('Captcha[_ts]')->Value());
        $this->assertEmpty($fields->dataFieldByName('Captcha[_ctrl]')->Value());

        $time = time();
        $requestData = [
            'key1' => 'val1',
            'Captcha[_ts]' => $time
        ];
        $form->loadDataFrom($requestData);

        $this->assertEquals('val1', $fields->dataFieldByName('key1')->Value());
        $this->assertEquals($time, $fields->dataFieldByName('Captcha[_ts]')->Value());

    }

    public function getForm(): Form
    {
        $form = new Form(
            Controller::curr(),
            'Form',
            new FieldList(
                new TextField('key1'),
            ),
            new FieldList()
        );
        $form->enableSpamProtection();
        return $form;
    }

    public function testValidSubmission()
    {
        $this->get('HoneypotFieldTest_Controller');

        $timeLimit = HoneypotField::config()->time_limit;
        $time = time() - $timeLimit - 1; // set to before time limit
        $response = $this->submitForm(
            'Form_Form',
            'action_doSubmit',
            [
                'Email' => 'test@example.com',
                'SomeField' => 'Test Content',
                'Captcha[_ts]' => $time,
                'Captcha[_val]' => _t(HoneypotField::class . '.DefaultValue', 'Please leave this as is.'),
                'Captcha[_ctrl]' => '',
            ]
        );

        $this->assertPartialMatchBySelector(
            '#Form_Form_error',
            [
                'Test save was successful'
            ],
            'Valid form submission shows success message'
        );

        $this->assertStringContainsString(
            'Test save was successful',
            $response->getBody(),
            'Form shows success message on submit'
        );
        $this->assertStringNotContainsString(
            _t(HoneypotField::class . '.SPAM', 'Your submission has been rejected because it was treated as spam.'),
            $response->getBody(),
            'Form does not show error message on submit'
        );
    }

    public function testRemovedCaptchaField()
    {
        $this->get('HoneypotFieldTest_Controller');

        $timeLimit = HoneypotField::config()->time_limit;
        $time = time() - $timeLimit - 1; // set to before time limit
        $response = $this->submitForm(
            'Form_Form',
            'action_doSubmit',
            [
                'Email' => 'test@example.com',
                'SomeField' => 'Test Content',
                // remove captcha fields
            ]
        );

        $this->assertStringNotContainsString(
            'Test save was successful',
            $response->getBody(),
            'Form does not show success message on submit'
        );
        $this->assertStringContainsString(
            _t(HoneypotField::class . '.SPAM', 'Your submission has been rejected because it was treated as spam.'),
            $response->getBody(),
            'Form shows error message on submit'
        );
    }

    public function testChangedValueField()
    {
        $this->get('HoneypotFieldTest_Controller');

        $timeLimit = HoneypotField::config()->time_limit;
        $time = time() - $timeLimit - 1; // set to before time limit
        $response = $this->submitForm(
            'Form_Form',
            'action_doSubmit',
            [
                'Email' => 'test@example.com',
                'SomeField' => 'Test Content',
                'Captcha[_ts]' => $time,
                'Captcha[_val]' => 'Some other text', // change default text
                'Captcha[_ctrl]' => '',
            ]
        );

        $this->assertStringNotContainsString(
            'Test save was successful',
            $response->getBody(),
            'Form does not show success message on submit'
        );
        $this->assertStringContainsString(
            _t(HoneypotField::class . '.SPAM', 'Your submission has been rejected because it was treated as spam.'),
            $response->getBody(),
            'Form shows error message on submit'
        );
    }

    public function testChangedControlField()
    {
        $this->get('HoneypotFieldTest_Controller');

        $timeLimit = HoneypotField::config()->time_limit;
        $time = time() - $timeLimit - 1; // set to before time limit
        $response = $this->submitForm(
            'Form_Form',
            'action_doSubmit',
            [
                'Email' => 'test@example.com',
                'SomeField' => 'Test Content',
                'Captcha[_ts]' => $time,
                'Captcha[_val]' => _t(HoneypotField::class . '.DefaultValue', 'Please leave this as is.'),
                'Captcha[_ctrl]' => 'text', // add text to this field
            ]
        );

        $this->assertStringNotContainsString(
            'Test save was successful',
            $response->getBody(),
            'Form does not show success message on submit'
        );
        $this->assertStringContainsString(
            _t(HoneypotField::class . '.SPAM', 'Your submission has been rejected because it was treated as spam.'),
            $response->getBody(),
            'Form shows error message on submit'
        );
    }

    public function testTooFastSumbission()
    {
        $this->get('HoneypotFieldTest_Controller');

        $timeLimit = HoneypotField::config()->time_limit;
        $time = time() - $timeLimit + 3; // set to within time limit
        $response = $this->submitForm(
            'Form_Form',
            'action_doSubmit',
            [
                'Email' => 'test@example.com',
                'SomeField' => 'Test Content',
                'Captcha[_ts]' => $time,
                'Captcha[_val]' => _t(HoneypotField::class . '.DefaultValue', 'Please leave this as is.'),
                'Captcha[_ctrl]' => '',
            ]
        );

        $this->assertStringNotContainsString(
            'Test save was successful',
            $response->getBody(),
            'Form does not show success message on submit'
        );
        $this->assertStringContainsString(
            _t(HoneypotField::class . '.SPAM', 'Your submission has been rejected because it was treated as spam.'),
            $response->getBody(),
            'Form shows error message on submit'
        );
    }

    public function testTooSlowSumbission()
    {
        $this->get('HoneypotFieldTest_Controller');

        $timeLimit = HoneypotField::config()->time_limit;
        $time = time() - (60 * 60) - 1; // set to older than an hour
        $response = $this->submitForm(
            'Form_Form',
            'action_doSubmit',
            [
                'Email' => 'test@example.com',
                'SomeField' => 'Test Content',
                'Captcha[_ts]' => $time,
                'Captcha[_val]' => _t(HoneypotField::class . '.DefaultValue', 'Please leave this as is.'),
                'Captcha[_ctrl]' => '',
            ]
        );

        $this->assertStringNotContainsString(
            'Test save was successful',
            $response->getBody(),
            'Form does not show success message on submit'
        );
        $this->assertStringContainsString(
            _t(HoneypotField::class . '.SPAM', 'Your submission has been rejected because it was treated as spam.'),
            $response->getBody(),
            'Form shows error message on submit'
        );
    }

    public function testTextInTimeField()
    {
        $this->get('HoneypotFieldTest_Controller');

        $timeLimit = HoneypotField::config()->time_limit;
        $response = $this->submitForm(
            'Form_Form',
            'action_doSubmit',
            [
                'Email' => 'test@example.com',
                'SomeField' => 'Test Content',
                'Captcha[_ts]' => 'some text', // replace time value with text
                'Captcha[_val]' => _t(HoneypotField::class . '.DefaultValue', 'Please leave this as is.'),
                'Captcha[_ctrl]' => '',
            ]
        );

        $this->assertStringNotContainsString(
            'Test save was successful',
            $response->getBody(),
            'Form does not show success message on submit'
        );
        $this->assertStringContainsString(
            _t(HoneypotField::class . '.SPAM', 'Your submission has been rejected because it was treated as spam.'),
            $response->getBody(),
            'Form shows error message on submit'
        );
    }

    public function testEmptyTimeField()
    {
        $this->get('HoneypotFieldTest_Controller');

        $timeLimit = HoneypotField::config()->time_limit;
        $response = $this->submitForm(
            'Form_Form',
            'action_doSubmit',
            [
                'Email' => 'test@example.com',
                'SomeField' => 'Test Content',
                'Captcha[_ts]' => '', // replace time value with empty field
                'Captcha[_val]' => _t(HoneypotField::class . '.DefaultValue', 'Please leave this as is.'),
                'Captcha[_ctrl]' => '',
            ]
        );

        $this->assertStringNotContainsString(
            'Test save was successful',
            $response->getBody(),
            'Form does not show success message on submit'
        );
        $this->assertStringContainsString(
            _t(HoneypotField::class . '.SPAM', 'Your submission has been rejected because it was treated as spam.'),
            $response->getBody(),
            'Form shows error message on submit'
        );
    }
}
