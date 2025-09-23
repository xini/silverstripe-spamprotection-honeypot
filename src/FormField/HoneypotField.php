<?php

namespace Innoweb\SpamProtectionHoneypot\FormField;

use Override;
use SilverStripe\Core\Validation\ValidationResult;
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
        $title = isset($title) && strlen($title) > 0 ? $title : _t(self::class . '.CHECK', 'Check');


        // naming with underscores to prevent values from actually being saved somewhere
        $children = FieldList::create(
            $this->valueField = TextField::create(
                $name . '[_val]',
                $title
            ),
            $this->timestampField = TextField::create(
                $name . '[_ts]',
                _t(self::class . '.TIMESTAMP', "What's the time?")
            ),
            $this->controlField = TextareaField::create(
                $name . '[_ctrl]',
                _t(self::class . '.CONTROL', 'Control')
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
        $value = isset($value) && strlen($value) > 0 ? $value : _t(self::class . '.DefaultValue', 'Please leave this as is.');
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
    #[Override]
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
    #[Override]
    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();
        $submission = $this->getForm()->getController()->getRequest()->postVar($this->getName());
        if (!$submission || !is_array($submission)) {
            $result->addError(
                _t(
                    self::class . '.SPAM',
                    'Your submission has been rejected because it was treated as spam.'
                )
            );
            return $result;
        }

        $defaultValue = _t(self::class . '.DefaultValue', 'Please leave this as is.');
        $timeLimit = $this->config()->time_limit;

        $value = $submission['_val'] ?? null;
        $timestamp = isset($submission['_ts']) ? (int) $submission['_ts'] : null;
        $control = $submission['_ctrl'] ?? null;

        if (
            // pre-set value has been modified
            (!isset($value)) || (isset($value) && $value !== $defaultValue)
            // timestamp newer than limit or older than an hour
            || (!isset($timestamp)) || (isset($timestamp) && $timeLimit > 0 && (($timestamp + $timeLimit) > time()) || ($timestamp < (time() - (60 * 60))))
            // empty field has been filled
            || (isset($control) && strlen($control) > 0)
        ) {
            $result->addError(
                _t(
                    self::class . '.SPAM',
                    'Your submission has been rejected because it was treated as spam.'
                )
            );
            return $result;
        }

        return $result;
    }

    #[Override]
    public function setTitle($title)
    {
        $this->valueField->setTitle($title);
        return parent::setTitle($title);
    }

    #[Override]
    public function setValue($value, $data = null)
    {
        $this->valueField->setValue($value);
        return $this;
    }

    #[Override]
    public function getColumnCount()
    {
        return null;
    }

    #[Override]
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
    #[Override]
    public function Type()
    {
        return 'hpsp';
    }
}
