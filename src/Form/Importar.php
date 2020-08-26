<?php

namespace Drupal\usuario_wdls\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Class Importar.
 */
class Importar extends FormBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usuario_wdls_form_importar';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $validators = array(
      'file_validate_extensions' => array('csv'),
      'file_validate_size' => array(file_upload_max_size()),
    );
    $form['upload_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload file'),
      '#upload_location' => 'public://usuarios/'.date("Y-m-d-H-i-s"),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->file = file_save_upload('upload_file', $form['upload_file']['#upload_validators'], FALSE, 0);
    // Ensure we have the file uploaded.
    if (!$this->file) {
      //$form_state->setErrorByName('upload_file', $this->t('File to import not found.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $form_file = $form_state->getValue('upload_file', 0);
    if (isset($form_file[0]) && !empty($form_file[0])) {
      $file = File::load($form_file[0]);
      $file->setPermanent();
      $file->save();
    }
    $handle = file_get_contents($file->url());
    $csv = array_map('str_getcsv', file($file->url()));
    array_walk($csv, function(&$a) use ($csv) {
      $a = array_combine($csv[0], $a);
    });
    array_shift($csv);
    foreach ($csv as $value) {
      $this->database->insert('myusers')
      ->fields([
        'nombre' => $value['nombre'],
      ])
      ->execute();
      drupal_set_message(t('Se ha creado el usuario '.$value['nombre']), 'status', FALSE);
    }
  }
}
