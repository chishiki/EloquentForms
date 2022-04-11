<?php

namespace Nickwest\EloquentForms\Test\views\bulma\fields;

use KubAT\PhpSimple\HtmlDomParser;

use Nickwest\EloquentForms\Field;
use Nickwest\EloquentForms\Test\FieldViewBulmaTestCase;
use Nickwest\EloquentForms\Test\ThemeTestInterfaces\fileFieldTestInterface;

class fileFieldTest extends FieldViewBulmaTestCase implements fileFieldTestInterface
{
    protected $test_value = 'yoda.pdf';
    protected $test_type = 'file';

    // Run all basic tests

    public function test_field_has_correct_value_attribute_when_changed()
    {
        $this->Field->attributes->value = $this->test_value;
        $dom = HtmlDomParser::str_get_html($this->Field->makeView()->render());
        $remove_button = current($dom->find($this->test_tag));
        $file_link = current($dom->find('span.file-name'));

        $this->assertEquals('Remove', $remove_button->value);
        $this->assertEquals('yoda.pdf', trim($file_link->plaintext));
    }

    public function test_remove_button_can_have_a_different_value()
    {
        $this->Field->attributes->value = $this->test_value;
        $this->Field->file_delete_button_value = 'Obliterate';
        $dom = HtmlDomParser::str_get_html($this->Field->makeView()->render());
        $remove_button = current($dom->find($this->test_tag));

        $this->assertEquals('Obliterate', $remove_button->value);
    }

    public function test_field_has_correct_class_attribute()
    {
        $dom = HtmlDomParser::str_get_html($this->Field->makeView()->render());
        $input = current($dom->find($this->test_tag));

        $this->assertEquals('file-input', $input->class);
    }

    public function test_field_has_correct_class_attribute_when_one_class_added()
    {
        $this->Field->attributes->addClass('my-class');
        $dom = HtmlDomParser::str_get_html($this->Field->makeView()->render());
        $input = current($dom->find($this->test_tag));

        $this->assertEquals('my-class file-input', trim($input->class));
    }

    public function test_field_has_correct_class_attribute_when_many_classes_added()
    {
        $this->Field->attributes->addClass('my-class');
        $this->Field->attributes->addClass('two');
        $this->Field->attributes->addClass('three');
        $dom = HtmlDomParser::str_get_html($this->Field->makeView()->render());
        $input = current($dom->find($this->test_tag));

        $this->assertEquals('my-class two three file-input', trim($input->class));
    }

    public function test_field_has_correct_class_attribute_when_classes_removed()
    {
        $this->Field->attributes->addClass('my-class');
        $this->Field->attributes->addClass('two');
        $this->Field->attributes->addClass('three');
        $this->Field->attributes->removeClass('two');
        $dom = HtmlDomParser::str_get_html($this->Field->makeView()->render());
        $input = current($dom->find($this->test_tag));

        $this->assertEquals('my-class three file-input', trim($input->class));
    }
}
