CMB2 Widget
==============

_Lets you use CMB2 in widgets_

Extend the abstract widget class.

```php
use NSRosenqvist\CMB2\Widgets\CMB2_Widget;

class MyWidget extends CMB2_Widget
{
    protected $fields = [
        // You can set ID both as the key and in the array
        'title' => [
			'name' 			   => __('Title', 'theme'),
			'type'             => 'text',
		],
    ];

    function __construct($id_base, $name, $widget_options = [], $control_options = [])
    {
        parent::__construct(
            // Base ID of widget
            'my_widget'
            // Widget name will appear in UI
            'My Widget',
            // Widget description
            $widget_options,
            // Widget options
            $control_options
        );
    }
}
```
