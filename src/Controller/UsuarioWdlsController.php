<?php

namespace Drupal\usuario_wdls\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UsuarioWdlsController.
 */
class UsuarioWdlsController extends ControllerBase {

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
   * Consulta.
   *
   * @return string
   *   Return Hello string.
   */
  public function consulta() {
    $header = array('id', 'nombre');
    $sth = $this->database->select('myusers', 'x')
      ->fields('x', array('id', 'nombre'));
    $data = $sth->execute();
    $query = $data->fetchAll(\PDO::FETCH_OBJ);
    foreach ($query as $item) {
      $rows[] = [
        'id' =>  $item->id,
        'nombre' => $item->nombre,
      ];
    }
    return array(
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    );
  }
  /**
   * Consulta_excel.
   *
   * @return string
   *   Return Hello string.
   */
  public function consulta_excel() {

    $header = array('id', 'nombre');
    $sth = $this->database->select('myusers', 'x')
      ->fields('x', array('id', 'nombre'));
    $data = $sth->execute();
    $query = $data->fetchAll(\PDO::FETCH_OBJ);
    foreach ($query as $item) {
      $rows[] = [
        'id' =>  $item->id,
        'nombre' => $item->nombre,
      ];
    }

    $handle = fopen('php://temp', 'w+');

    fputcsv($handle, $header);

    //fetching the field values
    foreach($rows as $key => $value) {
      fputcsv($handle, array_values($value));      
    }

    // Reset where we are in the CSV.
    rewind($handle);

    // Retrieve the data from the file handler.
    $csv_data = stream_get_contents($handle);

    // Close the file handler since we don't need it anymore.  We are not storing// this file anywhere in the filesystem.
    fclose($handle);

    // This is the "magic" part of the code.  Once the data is built, we can// return it as a response.
    $response = new Response();

    // By setting these 2 header options, the browser will see the URL
    // used by this Controller to return a CSV file called "user-report.csv"
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="user-report.csv"');

    // This line physically adds the CSV data we created 
    $response->setContent($csv_data);
    return $response;
  }
}