<?php namespace NSRosenqvist\CMB2\Widgets;

use WP_Widget;
use CMB2_hookup;
use CMB2;

abstract class CMB2_Widget extends WP_Widget
{
	protected $_instance = [];
	protected $fields = null;
	protected $defaults = null;
	protected $keyMap = []; // TODO: Remove this if not needed

    static $init = false;
	static $adminInit = false;

	function __construct($class, $title, $widget_options = [], $control_options = [])
	{
		$class = str_replace('\\', '-', $class);

        parent::__construct(
            // Base ID of widget
            $class,
            // Widget name will appear in UI
            $title,
            // Widget options
			array_merge([
				'classname' => $class,
				'customize_selective_refresh' => true,
				'description' => __('A CMB2 widget boilerplate description.', 'cmb2-widget'),
			], $widget_options),
            // Control Options
            $control_options
        );

		if (! is_null($this->fields)) {
			$this->processFields($this->fields);
		}

		add_filter('cmb2_show_on', [$this, 'show_on'], 10, 2);
		add_action('admin_init',   [self::class, 'adminInit']);
		add_action('init',         [self::class, 'init']);
	}

	protected function processFields(array $fields)
	{
		// Supporting either defining the fields in the typical CMB2 style
		// but also by having the keys as id's instead of as a field for
		// greater readability
		if ($this->is_assoc($fields)) {
			foreach ($fields as $id => $field) {
				$fields[$id]['id'] = $id;
			}
		}
		else {
			foreach ($fields as $key => $field) {
				unset($fields[$key]);
				$fields[$field['id']] = $field;
			}
		}

		// We store defaults definitions from fields in here in case they
		// haven't been defined in $this->defaults
		$defaults = [];

		foreach ($fields as $id => $field) {
			// Extract default value
			if (isset($field['default'])) {
				if (! isset($this->defaults[$id])) {
					$this->defaults[$id] = $field['default'];
				}

				// Remove default from field definition (messes up widget)
				unset($fields[$id]['default']);
			}
		}

		// Build a keymap and set id_key
		foreach ($fields as $id => $field) {
			// Add id_key if it hasn't been set
			if (! isset($field['id_key'])) {
				$fields[$id]['id_key'] = $id;
			}

			// Map id to field name
			$this->keyMap[$id] = $this->get_field_name($id);
		}

		// Set fields
		$this->fields = $fields;
	}

	protected function getFields()
	{
		return $this->fields;
	}

	protected function getDefaults()
	{
		return $this->defaults;
	}

	protected function getKeyMap()
	{
		return $this->keyMap;
	}

	protected function getCMB2Id()
	{
		return $this->option_name .'_box';
	}

	public function cmb2($saving = false)
	{
		// Create a new box in the class
		$cmb2 = new CMB2([
			'id'      => $this->getCMB2Id(), // Option name is taken from the WP_Widget class.
			'hookup'  => false,
			'show_on' => [
				'key'   => 'options-page', // Tells CMB2 to handle this as an option
				'value' => [$this->option_name],
			],
		], $this->option_name);

		// Add fields to form
		foreach ($this->getFields() as $field) {
			// Translate the id to a widget form field name if we're saving the data
			if (! $saving) {
				$field['id'] = $this->get_field_name($field['id']);
			}

			// Add classes
			if (isset($field['classes'])) {
				if (! is_array($field['classes'])) {
					$field['classes'] = [$field['classes']];
				}
			}

			$field['classes'][] = 'cmb2-widgets';

			// FIXME: Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
			if ($field['type'] == 'group') {
                // Update group fields default_cb
                foreach ($field['fields'] as $group_field_index => $group_field) {
                    $group_field['default_cb'] = [$this, 'default_cb'];

                    $field['fields'][$group_field_index] = $group_field;
                }
            }

			// Add callback and then add the field
			$field['default_cb'] = [$this, 'default_cb'];
			$cmb2->add_field($field);
		}
		return $cmb2;
	}

	static function adminInit()
	{
		// Only run this once
		if (self::$adminInit) {
			return;
		}

		self::$adminInit = true;

		// Include assets
		self::includes();
	}

	static function init()
	{
		// Only run this once
		if (self::$init) {
			return;
		}

		self::$init = true;
	}

	public function cmb2_override_meta_value($value, $object_id, $args, $field)
	{
		// FIXME: Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
        if ($field->group || 'group' === $field->type()) {
            if (isset($field->args['id_key'])) {
                $id_key = $field->args['id_key'];

                if (isset($this->_instance[$id_key])) {
                    $value = $this->_instance[$id_key];
                }
            }
        }

        return $value;
    }

	function show_on($display, $meta_box)
	{
        if ( ! isset($meta_box['show_on']['key'], $meta_box['show_on']['value'])) {
			return $display;
		}
		if ( ! $meta_box['show_on']['key'] != 'widget' ) {
    		return $display;
    	}

    	if ($meta_box['show_on']['value'] == $this->option_name) {
    		return true;
    	}

    	return $display;
    }

    static function includes()
	{
		if (defined('DOING_AJAX') && DOING_AJAX) {
			return;
		}

		if (defined('CMB2_LOADED')) {
			// Enqueue CMB assets
			CMB2_hookup::enqueue_cmb_css();
			CMB2_hookup::enqueue_cmb_js();
		}

		// Register assets
		add_action('admin_enqueue_scripts', function() {
			wp_register_style('cmb2_widgets', self::plugins_url('cmb2-widget', '/assets/cmb2-widgets.css', __FILE__, 1), false, '1.0.0');
			wp_register_script('cmb2_widgets', self::plugins_url('cmb2-widget', '/assets/cmb2-widgets.js', __FILE__, 1), ['jquery'], '1.0.0');

		    wp_enqueue_style('cmb2_widgets');
			wp_enqueue_script('cmb2_widgets');
		});
	}

	public function update($new_instance, $old_instance)
	{
		$fields = $this->getFields();
		$sanitized = $this->cmb2(true)->get_sanitized_values($new_instance);

		// FIXME: Workaround for file id fields not saving properly
		foreach ($new_instance as $id => $value) {
			if ($fields[$id]['type'] == 'file') {
				$sanitized[$id.'_id'] = $file_id = intval($value);
				$sanitized[$id] = wp_get_attachment_url($file_id);
			}
		}

		return $sanitized;
	}

	/**
	 * Back-end widget form with defaults.
	 *
	 * @param  array  $instance  Current settings.
	 */
	public function form($instance)
	{
		// FIXME: Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
		add_filter('cmb2_override_meta_value', [$this, 'cmb2_override_meta_value'], 11, 4);

		// If there are no settings, set up defaults
		$this->_instance = wp_parse_args((array) $instance, $this->getDefaults());
		$cmb2 = $this->cmb2();
		$cmb2->object_id($this->option_name);
		$cmb2->show_form();

		remove_filter('cmb2_override_meta_value', [$this, 'cmb2_override_meta_value']);
	}

	public function widget($args, $instance)
	{
		parent::widget($args, $instance);
	}

	public function default_cb($field_args, $field)
	{
		// FIXME: Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
       if ($field->group) {
           if (isset($this->_instance[$field->group->args('id_key')])) {
               $data = $this->_instance[$field->group->args('id_key')];

               return (is_array($data) && isset($data[$field->group->index][$field->args('id_key')]))
                   ? $data[$field->group->index][$field->args('id_key')]
                   : null;
           }
		   else {
               return null;
           }
       }

       return (isset($this->_instance[$field->args('id_key')]))
           ? $this->_instance[$field->args('id_key')]
           : null;
   }

	private function is_assoc(array $arr)
	{
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

	static function plugins_url($name, $file, $__FILE__, $depth = 0)
	{
		// Traverse up to root
		$dir = dirname($__FILE__);

		for ($i = 0; $i < $depth; $i++) {
			$dir = dirname($dir);
		}

		$root = $dir;
		$plugins = dirname($root);

		// Compare plugin directory with our found root
		if ($plugins !== WP_PLUGIN_DIR || $plugins !== WPMU_PLUGIN_DIR) {
			// Must be a symlink, guess location based on default directory name
			$resource = $name.'/'.$file;

			if (file_exists(WPMU_PLUGIN_DIR.'/'.$resource)) {
				return WPMU_PLUGIN_URL.'/'.$resource;
			}
			elseif (file_exists(WP_PLUGIN_DIR.'/'.$resource)) {
				return WP_PLUGIN_URL.'/'.$resource;
			}
		}

		return plugins_url($file, $root);
	}
}
