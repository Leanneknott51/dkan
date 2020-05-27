<?php

namespace Drupal\metastore\FileFetcher\Processor;

use Aws\S3\S3UriParser;
use Drupal\metastore\AwsS3Trait;
use Procrastinator\Result;
use FileFetcher\Processor\ProcessorInterface;

/**
 *
 */
class S3 implements ProcessorInterface {
  use \FileFetcher\TemporaryFilePathFromUrl;
  use AwsS3Trait;

  private $awsS3Client;

  /**
   *
   */
  public function __construct() {
    $this->awsS3Client = $this->getS3Client();
  }

  /**
   *
   */
  public function isServerCompatible(array $state): bool {
    if (substr_count($state['source'], "s3://") > 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   *
   */
  public function setupState(array $state): array {
    $state['destination'] = $this->getTemporaryFilePath($state);
    $state['temporary'] = TRUE;
    $state['total_bytes'] = $this->getRemoteFileSize($state['source']);

    if (file_exists($state['destination'])) {
      $state['total_bytes_copied'] = filesize($state['destination']);
    }

    return $state;
  }

  /**
   *
   */
  public function copy(
        array $state,
        Result $result,
        int $timeLimit = PHP_INT_MAX
    ): array {

    if ($state['total_bytes_copied'] >= $state['total_bytes']) {
      $result->setStatus(Result::DONE);
      return ['state' => $state, 'result' => $result];
    }
    $destinationFile = $state['destination'];
    $total = $state['total_bytes_copied'];

    $expiration = time() + $timeLimit;

    while ($chunk = $this->getChunk($state)) {
      $bytesWritten = $this->createOrAppend($destinationFile, $chunk);

      if ($bytesWritten !== strlen($chunk)) {
        throw new \RuntimeException(
        "Unable to fetch {$state['source']}. " .
        " Reason: Failed to write to destination " . $destinationFile,
        0
        );
      }

      $total += $bytesWritten;
      $state['total_bytes_copied'] = $total;

      $currentTime = time();
      if ($currentTime > $expiration) {
        $result->setStatus(Result::STOPPED);
        return ['state' => $state, 'result' => $result];
      }
    }

    $result->setStatus(Result::DONE);
    return ['state' => $state, 'result' => $result];
  }

  /**
   *
   */
  public function isTimeLimitIncompatible(): bool {
    return FALSE;
  }

  /**
   *
   */
  private function getRemoteFileSize($source) {
    $parser = new S3UriParser();
    $info = $parser->parse($source);
    $result = $this->awsS3Client->headObject(
        [
          'Bucket' => $info['bucket'],
          'Key' => urldecode($info['key']),
        ]
    );
    return $result['ContentLength'];
  }

  /**
   *
   */
  private function createOrAppend($filePath, $chunk) {
    if (!file_exists($filePath)) {
      $bytesWritten = file_put_contents($filePath, $chunk);
    }
    else {
      $bytesWritten = file_put_contents($filePath, $chunk, FILE_APPEND);
    }
    return $bytesWritten;
  }

  /**
   *
   */
  private function getChunk(array $state) {

    // 1 MB.
    $bytesToRead = 1024 * 1000;

    $url = $state['source'];
    $start = $state['total_bytes_copied'];
    $end = $start + $bytesToRead;

    if ($end > $state['total_bytes']) {
      $end = $state['total_bytes'];
    }

    if ($start == $end) {
      return FALSE;
    }

    $parser = new S3UriParser();
    $info = $parser->parse($url);
    $result = $this->awsS3Client->getObject(
        [
          'Bucket' => $info['bucket'],
          'Key' => urldecode($info['key']),
          'Range' => "bytes={$start}-{$end}",
        ]
    );
    /* @var $stream Stream */
    $stream = $result->get("Body");

    return $stream->getContents();
  }

}
