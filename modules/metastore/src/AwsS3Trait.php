<?php

namespace Drupal\metastore;

use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

/**
 *
 */
trait AwsS3Trait {

  private $s3BucketUrl;
  private $filesystem;

  /**
   *
   */
  public function getS3Client() {
    $c = CredentialProvider::env();
    $composed = CredentialProvider::chain($c);
    /* @var $promise \GuzzleHttp\Promise\Promise */
    $promise = $composed();
    $awsCredentials = $promise->wait();
    return new S3Client(
        [
          'version'     => 'latest',
          'region'      => 'us-east-1',
          'credentials' => $awsCredentials,
        ]
    );
  }

  /**
   *
   */
  public function setAwsS3FileSystem(Filesystem $filesystem) {
    $this->filesystem = $filesystem;
  }

  /**
   *
   */
  private function getAwsS3Filesystem(): Filesystem {
    if (is_null($this->filesystem)) {
      $this->filesystem = new Filesystem(new AwsS3Adapter($this->getS3Client(), $this->s3BucketUrl));
    }
    return $this->filesystem;
  }

}
