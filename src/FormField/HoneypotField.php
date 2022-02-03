<?php

namespace Innoweb\SpamProtectionHoneypot\FormField;

use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

class HoneypotField extends CompositeField
{
    /**
     * The number of seconds before you can submit a valid request.
     *
     * @var int
     * @config
     */
    private static $time_limit = 8;

    /**
     * @var TextField
     */
    protected $valueField = null;

    /**
     * @var TextField
     */
    protected $timestampField = null;

    /**
     * @var TextField
     */
    protected $controlField = null;

    /**
     * @param string $name
     * @param string $title
     * @param mixed $value
     */
    public function __construct(
        $name,
        $title = null,
        $value = ""
    ) {

        // Set field title
        $title = isset($title) && strlen($title) > 0 ? $title : _t(__CLASS__ . '.CHECK', 'Check');


        // naming with underscores to prevent values from actually being saved somewhere
        $children = FieldList::create(
            $this->valueField = TextField::create(
                "{$name}[_val]",
                $title
            ),
            $this->timestampField = TextField::create(
                "{$name}[_ts]",
                _t(__CLASS__ . '.TIMESTAMP', "What's the time?")
            ),
            $this->controlField = TextareaField::create(
                "{$name}[_ctrl]",
                _t(__CLASS__ . '.CONTROL', 'Control')
            )
        );
        parent::__construct($children);

        // update value field
        $this->valueField->setAttribute('autocomplete', 'off');
        $this->valueField->setAttribute('tabindex', '-1');
        $this->valueField->addExtraClass('hpspi');

        // update timestamp field
        $this->timestampField->setAttribute('autocomplete', 'off');
        $this->timestampField->setAttribute('tabindex', '-1');
        $this->timestampField->addExtraClass('hpspi');
        $this->timestampField->setValue(time());

        // update control field
        $this->controlField->setAttribute('autocomplete', 'off');
        $this->controlField->addExtraClass('hpsph');

        // set field value
        $value = isset($value) && strlen($value) > 0 ? $value : _t(__CLASS__ . '.DefaultValue', 'Please leave this as is.');
        $this->setValue($value);

        // set field name
        $this->setName($name);
    }

    /**
     * Override the Field to add the custom css.
     *
     * @codeCoverageIgnore
     *
     * @param array $properties
     *
     * @return string
     */
    public function Field($properties = [])
    {
        Requirements::css('innoweb/silverstripe-spamprotection-honeypot:client/dist/css/spamprotection-honeypot.css');
        return parent::Field($properties);
    }

    /**
     * Reject the field if the honeypot has been filled or if the form has been submitted too quickly.
     *
     * @param $validator
     *
     * @return bool
     */
    public function validate($validator)
    {
        $submission = $this->getForm()->getController()->getRequest()->postVar($this->getName());
        if (!$submission || !is_array($submission)) {
            $validator->validationError(
                null,
                _t(
                    __CLASS__ . '.SPAM',
                    'Your submission has been rejected because it was treated as spam.'
                ),
                'error'
            );
            return false;
        }

        $defaultValue = _t(__CLASS__ . '.DefaultValue', 'Please leave as is.');
        $timeLimit = $this->config()->time_limit;

        $value = isset($submission['_val']) ? $submission['_val'] : null;
        $timestamp = isset($submission['_ts']) ? (int) $submission['_ts'] : null;
        $control = isset($submission['_ctrl']) ? $submission['_ctrl'] : null;

        if (
            // pre-set value has been modified
            (!isset($value)) || (isset($value) && $value !== $defaultValue)
            // timestamp newer than limit or older than an hour
            || (!isset($timestamp)) || (isset($timestamp) && $timeLimit > 0 && (($timestamp + $timeLimit) > time()) || ($timestamp < (time() - (60 * 60))))
            // empty field has been filled
            || (isset($control) && strlen($control) > 0)
        ) {
            $validator->validationError(
                null,
                _t(
                    __CLASS__ . '.SPAM',
                    'Your submission has been rejected because it was treated as spam.'
                ),
                'error'
            );
            return false;
        }

        return true;
    }

    public function setTitle($title)
    {
        $this->valueField->setTitle($title);
        return parent::setTitle($title);
    }

    public function setValue($value, $data = null)
    {
        $this->valueField->setValue($value);
        return $this;
    }

    public function getColumnCount()
    {
        return null;
    }

    public function getTag()
    {
        return 'div';
    }

    /**
     * Override the Type to remove the class namespace.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function Type()
    {
        return 'hpsp';
    }
}
