<?php

namespace Phile\Plugin\Pschmitt\Share;

/**
 * Social plugin for Phile CMS
 * Adds social media buttons to posts and pages
 * Port of pico-share by Narcis Radu
 *
 * @author Philipp Schmitt
 * @link http://lxl.io
 * @link https://github.com/pschmitt/phileShare
 * @license http://opensource.org/licenses/MIT
 * @package Phile\Plugin\Pschmitt\Share
 */

class Plugin extends \Phile\Plugin\AbstractPlugin implements \Phile\Gateway\EventObserverInterface {

    private $templates = array(
            'twitter'  => 'https://twitter.com/intent/tweet?text=__TITLE__&amp;url=__URL__',
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=__URL__',
            'google'   => 'https://plus.google.com/share?url=__URL__',
            'linkedin' => 'http://www.linkedin.com/shareArticle?mini=true&amp;url=__URL__&amp;title=__TITLE__&amp;summary=__EXCERPT__&amp;source=__URL__'
    );

    private $config;
    private $class_prefix;
    private $output;
    private $services;
    private $share_div_id;

    public function __construct() {
        \Phile\Event::registerEvent('config_loaded', $this);
        \Phile\Event::registerEvent('before_parse_content', $this);
        $this->config = \Phile\Registry::get('Phile_Settings');

        // init - default values
        $this->services = array('twitter' => true, 'facebook' => true, 'google' => true, 'linkedin' => true);
        $this->output = 'link';
        $this->class_prefix = 'btn-';
        $this->share_div_id = 'share';
    }

    public function on($eventKey, $data = null) {
        if ($eventKey == 'config_loaded') {
            $this->config_loaded();
        } else if ($eventKey == 'before_parse_content') {
            $title = rawurlencode($data['page']->getTitle());
            $description = rawurlencode($data['page']->getMeta()->get('description'));
            $relative_uri = $data['page']->getUrl();
            $this->export_twig_vars($title, $description, $relative_uri);
        }
    }

    private function config_loaded() {
        // merge the arrays to bind the settings to the view
        // Note: this->config takes precedence
        $this->config = array_merge($this->settings, $this->config);
        if (isset($this->config['share_services'])) {
            $this->services = $this->config['share_services'];
        }
        if (isset($this->config['share_output'])) {
            $this->output = $this->config['share_output'];
        }
        if (isset($this->config['share_class_prefix'])) {
            $this->class_prefix = $this->config['share_class_prefix'];
        }
        if (isset($this->config['share_div_id'])) {
            $this->share_div_id = $this->config['share_div_id'];
        }
    }

    private function export_twig_vars($title, $description, $relative_uri) {
        if (\Phile\Registry::isRegistered('templateVars')) {
            $twig_vars = \Phile\Registry::get('templateVars');
        } else {
            $twig_vars = array();
        }
        $activeServices = array();

        foreach($this->services as $key => $value) {
            if(is_bool($value) && $value) {
                $activeServices[$key] = '<a class="'.$this->class_prefix.$key.'" target="_blank" href="'.
                preg_replace(array('/__TITLE__/', '/__URL__/', '/__EXCERPT__/'), array($title, $this->config['base_url'] . '/' . $relative_uri, $description), $this->templates[$key]).
                '">'.$key.'</a>';
            }
        }
        switch($this->output) {
            case 'list':
                $twig_vars['social_share'] = '<ul id="' . $this->share_div_id . '"><li>'.implode('</li><li>', array_values($activeServices)).'</li></ul>';
                break;
            default:
                //show as link by default
                $twig_vars['social_share'] = '<div id="' . $this->share_div_id . '">'.implode('', array_values($activeServices)).'</div>';
                break;
        }
        \Phile\Registry::set('templateVars', $twig_vars);
    }
}

?>
