<?php

namespace Drupal\usuario_wdls\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;


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
      '#type' => 'file',
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $all_files = $this->getRequest()->files->get('files', []);
    $file = $all_files['upload_file'];
    $file_name = $file->getClientOriginalName();
    $file_path = $file->getRealPath();
    $file_final = file_unmanaged_copy($file_path, 'public://usuarios/'.$file_name);
    $url_file = file_create_url($file_final);
    $csv = array_map('str_getcsv', file($url_file));
    array_walk($csv, function(&$a) use ($csv) {
      $a = array_combine($csv[0], $a);
    });
    array_shift($csv);
    $total = count($csv);

    $batch = [
      'title' => t('Creando usuarios a travez de CSV'),
      'operations' => [],
      'init_message' => t('Import process is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.'),
    ];

    foreach ($csv as $value) {
      $batch['operations'][] = [['\Drupal\usuario_wdls\Form\Importar', 'createUser'], [$value['name']]];           
    }

    batch_set($batch);

    drupal_set_message(t('Se ha creado los usuarios del CSV'), 'status', FALSE);

    $form_state->setRebuild(TRUE);
  }

  function createUser($name, &$context) {
    $database = \Drupal::database();
    $database->insert('myusers')
      ->fields([
        'nombre' => $name,
      ])
      ->execute(); 
    $context['results'][] = $name;
    $context['message'] = t('Created user @title', array('@title' => $name));
  }
}
