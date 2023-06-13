<?php

namespace Drupal\pfe_med_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Med Connect functionality.
 */
class MedConnectController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MedConnectController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays the Med Connect file upload form.
   *
   * @return array
   *   The form array.
   */
  public function uploadForm() {
    $form['upload_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload Excel File'),
      '#description' => $this->t('Please select the Excel file to upload.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    ];

    return $form;
  }

  /**
   * Processes the uploaded Excel file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function processFile(Request $request) {
    $file = $request->files->get('upload_file');
    $file_path = $file->getRealPath();

    // Clear existing table data.
    $this->database->truncate('med_connect_table')->execute();

    // Read and process the Excel file.
    $data = [];
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Skip the header row.
    array_shift($rows);

    foreach ($rows as $row) {
      // Assuming the columns in the Excel file match the table structure.
      $product = $row[0];
      $therapeutic_area = $row[1];
      $district = $row[2];
      $msl_email = $row[3];
      $backup_email = $row[4];

      $data[] = [
        'product' => $product,
        'therapeutic_area' => $therapeutic_area,
        'district' => $district,
        'msl_email' => $msl_email,
        'backup_email' => $backup_email,
      ];
    }

    // Insert the data into the table.
    $this->database->insert('med_connect_table')
      ->fields(['product', 'therapeutic_area', 'district', 'msl_email', 'backup_email'])
      ->values($data)
      ->execute();

    // Process the email triggers here.
    // ...

    return new RedirectResponse('/pfe-med-connect/upload');
  }

}
