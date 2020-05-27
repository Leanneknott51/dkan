<?php

namespace Drupal\metastore;

use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\common\Storage\JobStoreFactory;
use Drupal\harvest\Utility\FileHelper;
use FileFetcher\FileFetcher;
use Procrastinator\Result;

class FileMapper {

  private $jobStore;
  private $fileFetcherProcessors;
  private $fileHelper;
  private $streamWrapperManager;

  public function __construct(JobStoreFactory $jobStoreFactory, FileHelper $fileHelper, StreamWrapperManagerInterface $streamWrapperManager, array $fileFetcherProcessors) {
    $this->jobStore = $jobStoreFactory->getInstance(FileMapper::class);
    $this->fileFetcherProcessors = $fileFetcherProcessors;
    $this->fileHelper = $fileHelper;
    $this->streamWrapperManager = $streamWrapperManager;
  }

  public function register(string $url) : string {
    $uuid = md5($url);

    if (!self::exists($uuid)) {
      $directory = $this->getLocalDirectory($uuid);
      $this->fileHelper->prepareDir($directory);

      $this->getFileFetcher($uuid, $url);

      return $uuid;
    }
    throw new \Exception("URL already registered.");
  }

  public function getLocalUrl(string $uuid) : ?string {
    if ($this->exists($uuid)) {
      $ourselves = $this->getFileFetcher($uuid);
      if ($ourselves->getResult()->getStatus() == Result::DONE) {
        return $this->streamWrapperManager->getViaUri($ourselves->getStateProperty("destination"));
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
    $publicPath = $this->fileHelper->defaultSchemeDirectory();
    return $publicPath . '/distributions/' . $uuid;
  }

}
