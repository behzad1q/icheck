<?php
/**
 * @file
 * Contains \Drupal\movie_filter\Form\MovieFilterForm.
 */

namespace Drupal\icheck\Forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;

class IcheckSettingForm extends ConfigFormBase {

  /**
  * Constructor for ComproCustomForm.
  *
  * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
  * The factory for configuration objects.
  */
    public function __construct(ConfigFactoryInterface $config_factory) {
     parent::__construct($config_factory);
  }

  public function getFormId() {
    return 'icheck_settings_form';
  }

  /**
  * Gets the configuration names that will be editable.
  *
  * @return array
  * An array of configuration object names that are editable if called in
  * conjunction with the trait's config() method.
  */
  protected function getEditableConfigNames() {
    return ['config.icheck_settings'];
  }



  public function buildForm(array $form, FormStateInterface $form_state) {

    $ichecked = $this->config('config.icheck_settings');

    $input = $form_state->getUserInput();

    $form['ichecked_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('iCheck Enabled'),
      '#default_value' => $ichecked->get('ichecked_enable') ? $ichecked->get('ichecked_enable') : FALSE,
    );
    $form['ichecked_enable_admin'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('iCheck Enabled on admin theme'),
      '#default_value' => $ichecked->get('ichecked_enable_admin') ? $ichecked->get('ichecked_enable_admin') : FALSE,
    );
    $form['icheck_skin'] = array(
      '#title' => $this->t('Skin'),
      '#type' => 'select',
      '#options' => icheck_skin_options(),
      '#default_value' => $ichecked->get('icheck_skin') ? $ichecked->get('icheck_skin') : 'minimal',
      '#ajax' => array(
        'callback' => array($this, 'icheck_skin_color_ajax'),
        'wrapper' => 'icheck-skin-color-wrapper',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
    );

    $skins = array();
    if (!is_null($form_state->getValue(array('icheck_skin')))) {
      $skins = icheck_skin_color_options($form_state->getValue(array('icheck_skin')));
    }

    $form['icheck_skin_color'] = array(
      '#title' => $this->t('Skin Color'),
      '#type' => 'select',
      '#options' => !empty($skins) ? $skins : icheck_skin_color_options($ichecked->get('icheck_skin')),
      '#default_value' => $ichecked->get('icheck_skin_color') ? $ichecked->get('icheck_skin_color') : 'red',
      '#validated' => TRUE, // Drupal does not rebuild options properly when using ajax so we skip validation
      '#prefix' => '<div id="icheck-skin-color-wrapper">',
      '#suffix' => '</div>',
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('config.icheck_settings')
      ->set('ichecked_enable', $form_state->getValue(array('ichecked_enable')))
      ->set('ichecked_enable_admin', $form_state->getValue(array('ichecked_enable_admin')))
      ->set('icheck_skin', $form_state->getValue(array('icheck_skin')))
      ->set('icheck_skin_color', $form_state->getValue(array('icheck_skin_color')))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Select callback for updating color options.
   */
  function icheck_skin_color_ajax(&$form, $form_state) {
    return $form['icheck_skin_color'];
  }

}


/**
 * Build #options list of available skins.
 */
function icheck_skin_options() {
  $options = array();
  $skins_dir = libraries_get_path('iCheck') . '/skins';
  $skins = file_scan_directory($skins_dir, '/.*/', array('recurse' => FALSE));

  foreach ($skins as $skin) {
    if (is_dir($skin->uri)) {
      $options[$skin->name] = $skin->name;
    }
  }

  return $options;
}

/**
 * Build #options list of available colorschemes.
 */
function icheck_skin_color_options($skin) {
  $options = array();
  $skin_css_dir = libraries_get_path('iCheck') . '/skins/' . $skin;
  $colors = file_scan_directory($skin_css_dir, '/^[^.].*\.css$/', array('nomask' => "/($skin|_all)\.css$/"));

  if (empty($colors)) {
    return array('' => '-- colors unsupported --');
  }

  foreach ($colors as $color) {
    $options[$color->name] = $color->name;
  }

  return $options;
}
