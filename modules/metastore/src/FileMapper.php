<?php

namespace Drupal\metastore;

use Drupal\common\Storage\JobStoreFactory;
use Drupal\common\Util\DrupalFiles;
use FileFetcher\FileFetcher;
use FileFetcher\Processor\Remote;
use Procrastinator\Result;

class FileMapper {

  private $jobStore;
  private $drupalFiles;

  private $fileFetcherProcessors;

  public function __construct(JobStoreFactory $jobStoreFactory, DrupalFiles $drupalFiles) {
    $this->jobStore = $jobStoreFactory->getInstance(FileMapper::class);
    $this->drupalFiles = $drupalFiles;
    $this->fileFetcherProcessors = [
      Remote::class
    ];
  }

  public function setFileFetcherProcessors(array $fileFetcherProcessors) {
    $this->fileFetcherProcessors = $fileFetcherProcessors;
  }

  public function register(string $url) : string {
    $uuid = md5($url);

    if (!$this->exists($uuid)) {
      $directory = $this->getLocalDirectory($uuid);
      $this->drupalFiles->getFilesystem()->prepareDirectory($directory);

      $this->getFileFetcher($uuid, $url);

      return $uuid;
    }
    throw new \Exception("URL already registered.");
  }

  public function getLocalUrl(string $uuid) : ?string {
    if ($this->exists($uuid)) {
      $ourselves = $this->getFileFetcher($uuid);
      if ($ourselves->getResult()->getStatus() == Result::DONE) {
        return $this->drupalFiles->fileCreateUrl($ourselves->getStateProperty("destination"));
      }
    }
    throw new \Exception("Unknown URL.");
  }

  public function getFileFetcher($uuid, $url = '') {
    $fileFetcherConfig = [
      'filePath' => $url,
      'processors' => $this->fileFetcherProcessors,
      'temporaryDirectory' => $this->getLocalDirectory($uuid),
    ];

    return FileFetcher::get($uuid, $this->jobStore, $fileFetcherConfig);
  }

  private function exists($uuid) {
    $instance = $this->jobStore->retrieve($uuid);
    return isset($instance);
  }

  private function getLocalDirectory($uuid) {
    $publicPath = $this->drupalFiles->getPublicFilesDirectory();
    return $publicPath . '/distributions/' . $uuid;
  }

}
