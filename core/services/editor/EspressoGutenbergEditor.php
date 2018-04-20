<?php

namespace EventEspresso\core\services\editor;

use EventEspresso\core\domain\entities\custom_post_types\CustomPostTypeDefinitions;
use EventEspresso\core\domain\entities\editor\BlockCollection;
use EventEspresso\core\services\request\RequestInterface;
use WP_Post_Type;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class EspressoGutenbergEditor
 * Activates the Gutenberg editor for Event Espresso Custom Post Types
 *
 * @package EventEspresso\core\services\editor
 * @author  Brent Christensen
 * @since   $VID:$
 */
class EspressoGutenbergEditor extends BlockManager
{

    /**
     * @var CustomPostTypeDefinitions $custom_post_types
     */
    protected $custom_post_types;


    /**
     * EspressoGutenbergEditor constructor.
     *
     * @param CustomPostTypeDefinitions $custom_post_types
     * @param BlockCollection           $blocks
     * @param RequestInterface          $request
     */
    public function __construct(
        CustomPostTypeDefinitions $custom_post_types,
        BlockCollection $blocks,
        RequestInterface $request
    ) {
        $this->custom_post_types = $custom_post_types;
        parent::__construct($blocks, $request);
    }



    /**
     *  Returns the name of a hookpoint to be used to call initialize()
     *
     * @return string
     */
    public function init_hook()
    {
        return 'AHEE__EE_System__load_CPTs_and_session__complete';
    }


    /**
     * Perform any early setup required for block editors to functions
     *
     * @return void
     */
    public function initialize()
    {
        $custom_post_types   = $this->custom_post_types->getCustomPostTypeSlugs();
        $espresso_post_types = $custom_post_types;
        $espresso_post_types[] = 'espresso_registrations';
        if (
            ($this->action === 'edit' || $this->action === 'create_new' || $this->action === 'edit_attendee')
            && in_array($this->page, $espresso_post_types, true)
        ) {
            $this->loadCustomPostTypeBlockEditor($custom_post_types);
        }
        add_action('admin_url', array($this, 'coerceEeCptEditorUrlForGutenberg'), 10, 3);/**/
    }


    public function loadCustomPostTypeBlockEditor(array $custom_post_types)
    {
        $this->modifyWpPostTypes($custom_post_types);
        // add_action('admin_enqueue_scripts', array($this, 'registerAdminScripts'), 20);
        add_filter('FHEE__EE_Admin_Page_CPT___create_new_cpt_item__replace_editor', 'gutenberg_init', 10, 2);
    }


    /**
     * Manipulate globals related to EE Post Type so gutenberg loads.
     *
     * @param array $custom_post_types
     */
    private function modifyWpPostTypes(array $custom_post_types)
    {
        global $wp_post_types, $_wp_post_type_features;
        foreach ($custom_post_types as $post_type) {
            $_wp_post_type_features[ $post_type ]['editor'] = true;
            if (isset($wp_post_types[ $post_type ]) && $wp_post_types[ $post_type ] instanceof WP_Post_Type) {
                $post_type_object               = $wp_post_types[ $post_type ];
                $post_type_object->show_in_rest = true;
                $post_type_object->template     = array();
                foreach ($this->blocks as $block) {
                    if ($block->appliesToPostType($post_type)) {
                        $post_type_object->template[] = $block->getEditorContainer();
                    }
                }
            }
        }
    }


    public function coerceEeCptEditorUrlForGutenberg($url, $path, $blog_id)
    {
        if (
            $this->page === 'espresso_events'
            && ($this->action === 'edit' || $this->action === 'create_new')
            && strpos($path, 'post.php') !== false
        ) {
            return add_query_arg(
                array(
                    'page'   => $this->page,
                    'action' => $this->action
                ),
                get_site_url($blog_id)
            );
        }
        return $url;
    }
}
