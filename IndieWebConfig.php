<?php namespace ProcessWire;

/**
 * Class IndieWebConfig
 */
class IndieWebConfig extends ModuleConfig {

  /**
   * array Default config values
   */
  public function getDefaults() {
    return array(
      'consumerKey' => '',
      'consumerSecret' => '',
      'accessToken' => '',
      'accessTokenSecret' => '',
      'twitterHandle' => ''
    );
  }

  /**
   * Retrieves the list of config input fields
   * Implementation of the ConfigurableModule interface
   *
   * @return InputfieldWrapper
   */
  public function getInputfields() {
    $modules = $this->modules;
    $inputfields = parent::getInputfields();

    $field = $modules->get('InputfieldText');
    $field->label = __('Consumer Key');
    $field->attr('name', 'consumerKey');
    $field->attr('size', 50);
    $field->columnWidth = '50';
    $inputfields->add($field);

    $field = $modules->get('InputfieldText');
    $field->label = __('Consumer Secret');
    $field->attr('name', 'consumerSecret');
    $field->attr('size', 50);
    $field->columnWidth = '50';
    $inputfields->add($field);

    $field = $modules->get('InputfieldText');
    $field->label = __('Access Token');
    $field->attr('name', 'accessToken');
    $field->attr('size', 50);
    $field->columnWidth = '50';
    $inputfields->add($field);

    $field = $modules->get('InputfieldText');
    $field->label = __('Access Token Secret');
    $field->attr('name', 'accessTokenSecret');
    $field->attr('size', 50);
    $field->columnWidth = '50';
    $inputfields->add($field);

    $field = $modules->get('InputfieldText');
    $field->label = __('Twitter Handle');
    $field->attr('name', 'twitterHandle');
    $field->attr('size', 50);
    $field->columnWidth = '50';
    $inputfields->add($field);

    return $inputfields;
  }

}
