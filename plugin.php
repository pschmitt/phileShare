<?php

/**
 * Social plugin for Phile CMS
 * Adds social media buttons to posts and pages
 * Port of pico-share by Narcis Radu
 *
 * @author Philipp Schmitt
 * @link http://lxl.io
 * @license http://opensource.org/licenses/MIT
 */
class PhileShare extends \Phile\Plugin\AbstractPlugin implements \Phile\EventObserverInterface {

    private $templates = array(
            'twitter' => 'https://twitter.com/intent/tweet?text=__TITLE__&url=__URL__',
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=__URL__',
            'google' => 'https://plus.google.com/share?url=__URL__',
            'linkedin' => 'http://www.linkedin.com/shareArticle?mini=true&url=__URL__&title=__TITLE__&summary=__EXCERPT__&source=__URL__'
        );

    private $config;
    private $class_prefix;
    private $output;
    private $services;
    private $share_div_id;

    private $test = 'before_parse_content';

    public function __construct() {
        \Phile\Event::registerEvent('config_loaded', $this);
        \Phile\Event::registerEvent($this->test, $this);
        $this->config = \Phile\Registry::get('Phile_Settings');
    }

    public function on($eventKey, $data = null) {
        if ($eventKey == 'config_loaded') {
            $this->config_loaded();
        } else if ($eventKey == $this->test) {
            $title = $data['page']->getTitle();
            $description = $data['page']->getMeta()->get('description');
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
        } else {
            $this->services = array('twitter' => true, 'facebook' => true, 'google' => true, 'linkedin' => true);
        }
        if (isset($this->config['share_output'])) {
            $this->output = $this->config['share_output'];
        } else {
            $this->output = 'link';
        }
        if (isset($this->config['share_class_prefix'])) {
            $this->class_prefix = $this->config['share_class_prefix'];
        } else {
            $this->class_prefix = 'btn-';
        }
        if (isset($this->config['share_div_id'])) {
            $this->share_div_id = $this->config['share_div_id'];
        } else {
            $this->share_div_id = 'share';
        }
    }

    private function export_twig_vars($title, $description, $relative_uri) {
        if (\Phile\Registry::isRegistered('templateVars')) {
            $twig_vars = \Phile\Registry::get('templateVars');
        } else {
            $twig_vars = array();
        }
        // var_dump($twig_vars);

        // $pageTitle = $twig_vars['current_page']['title'];
        // $pageURL = $twig_vars['current_page']['url'];
        // $pageExcerpt = $twig_vars['current_page']['excerpt'];
        $activeServices = array();

        foreach($this->services as $key => $value) {
            if(is_bool($value) && $value) {
                $activeServices[$key] = '<a class="'.$this->class_prefix.$key.'" target="_blank" href="'.
                preg_replace(array('/__TITLE__/', '/__URL__/', '/__EXCERPT__/'), array($title, $this->config['base_url'] . '/' . $relative_uri, $description), $this->templates[$key]).
                '">'.$key.'</a>';
            }
        }
        switch($this->output) {
            case 'link':
                $twig_vars['social_share'] = '<div id="' . $this->share_div_id . '">'.implode('', array_values($activeServices)).'</div>';
                break;
            default:
                //show as list by default
                $twig_vars['social_share'] = '<ul id="' . $this->share_div_id . '"><li>'.implode('</li><li>', array_values($activeServices)).'</li></ul>';
                break;
        }
        \Phile\Registry::set('templateVars', $twig_vars);
    }
}

?>