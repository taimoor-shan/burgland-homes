<?php
/**
 * Shortcodes Registration and Handling
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Shortcodes
 */
class Burgland_Homes_Shortcodes
{

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('init', array($this, 'register_shortcodes'));
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes()
    {
        add_shortcode('featured_agent', array($this, 'featured_agent_shortcode'));
        add_shortcode('bh_featured_agent', array($this, 'featured_agent_shortcode'));
    }

    /**
     * Featured Agent Shortcode
     * 
     * Displays a featured team member/agent contact card
     * 
     * Usage:
     * [featured_agent]
     * [featured_agent id="123"]
     * [featured_agent heading="Contact Our Team" button_text="Schedule Visit"]
     * [featured_agent heading="Have Questions?" button_text="" button_link=""] // Omit button
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function featured_agent_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => '',
            'heading' => 'Our Online New Home Counselors',
            'button_text' => 'Get Started',
            'button_link' => '#contact',
            'featured' => 'true',
        ), $atts, 'featured_agent');

        $data_provider = Burgland_Homes_Data_Provider::get_instance();
        $team_member = null;

        // If specific ID is provided, get that team member
        if (!empty($atts['id'])) {
            $team_member = $data_provider->get_team_member_data(intval($atts['id']));
        } else {
            // Otherwise, get featured team member
            $featured = filter_var($atts['featured'], FILTER_VALIDATE_BOOLEAN);
            $team_members = $data_provider->get_featured_team_members(array(
                'limit' => 1,
                'featured' => $featured,
            ));

            if (!empty($team_members)) {
                $team_member = $team_members[0];
            }
        }

        // If no team member found, return empty or fallback
        if (empty($team_member)) {
            return '';
        }

        // Prepare template arguments
        $template_args = array(
            'team_member' => $team_member,
            'heading' => $atts['heading'],
            'button_text' => $atts['button_text'],
            'button_link' => $atts['button_link'],
        );

        // Load template
        $template_loader = Burgland_Homes_Template_Loader::get_instance();
        return $template_loader->load_template('single/sidebar-contact.php', $template_args, true);
    }
}
