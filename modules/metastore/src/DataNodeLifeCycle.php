<?php

namespace Drupal\metastore;

use Drupal\common\AbstractDataNodeLifeCycle;
use Drupal\common\UrlHostTokenResolver;
use Drupal\Core\Entity\EntityInterface;

/**
 * DataNodeLifeCycle.
 */
class DataNodeLifeCycle extends AbstractDataNodeLifeCycle {

  private $fileMapper;

  public function setFileMapper(FileMapper $fileMapper) {
    $this->fileMapper = $fileMapper;
  }

  private function getFileMapper() {
    if (!isset($this->fileMapper)) {
      throw new \Exception("FileMapper not set.");
    }
    return $this->fileMapper;
  }

  /**
   * Presave.
   *
   * Activities to move a data node through during presave.
   */
  public function presave() {

    if (empty($this->getDataType())) {
      $this->setDataType('dataset');
    }

    switch ($this->getDataType()) {
      case 'dataset':
        $this->datasetPresave();
        break;

      case 'distribution':
        $this->distributionPresave();
        break;
    }
  }

  /**
   * Private.
   */
  private function datasetPresave() {
    /* @var $entity \Drupal\node\Entity\Node */
    $entity = $this->node;

    $metadata = $this->getMetaData();

    $title = isset($metadata->title) ? $metadata->title : $metadata->name;

    $entity->setTitle($title);

    // If there is no uuid add one.
    if (!isset($metadata->identifier)) {
      $metadata->identifier = $entity->uuid();
    }
    // If one exists in the uuid it should be the same in the table.
    else {
      $entity->set('uuid', $metadata->identifier);
    }

    $referencer = \Drupal::service("metastore.referencer");
    $metadata = $referencer->reference($this->getMetaData());
    $this->setMetadata($metadata);

    // Check for possible orphan property references when updating a dataset.
    if (isset($entity->original)) {
      $orphanChecker = \Drupal::service("metastore.orphan_checker");
      $orphanChecker->processReferencesInUpdatedDataset(
        json_decode($entity->referenced_metadata),
        $metadata
      );
    }
  }

  /**
   * Private.
   */
  private function distributionPresave() {
    $metadata = $this->getMetaData();
    if (isset($metadata->data->downloadURL)) {
      $metadata->data->downloadURL = $this->getFileMapper()->register($metadata->data->downloadURL);
      $this->setMetadata($metadata);
    }
  }

}
