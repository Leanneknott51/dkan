<?php

namespace Drupal\Tests\metastore;

use Contracts\Mock\Storage\JsonObjectMemory;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\datastore\Storage\JobStoreFactory;
use Drupal\harvest\Utility\FileHelper;
use Drupal\metastore\FileMapper;
use Drupal\Tests\metastore\Unit\ProcessorMock;
use FileFetcher\FileFetcher;
use MockChain\Chain;
use PHPUnit\Framework\TestCase;


class FileMapperTest extends TestCase {

  private function getFileMapper($processors = []) {
    $store = new JsonObjectMemory();
    $jobStoreFactory = (new Chain($this))
      ->add(JobStoreFactory::class, "getInstance", $store)
      ->getMock();

    $fileHelper = (new Chain($this))
      ->add(FileHelper::class, "prepareDir", null)
      ->add(FileHelper::class, "defaultSchemeDirectory", __DIR__)
      ->getMock();

    $streamWrapperManager = (new Chain($this))
      ->add(StreamWrapperManager::class, 'getViaUri', "yep")
      ->getMock();


    return new FileMapper($jobStoreFactory, $fileHelper, $streamWrapperManager, $processors);
  }

  public function testRegister() {

    $url = 's3://bucket/filename.ext';
    $uuid = $this->getFileMapper()->register($url);
    $this->assertEquals(md5($url), $uuid);
  }

  public function testRegisterAlreadyExisting() {
    $fileMapper = $this->getFileMapper();

    $fileMapper->register('s3://bucket/filename.ext');
    $this->expectExceptionMessage('URL already registered.');
    $fileMapper->register('s3://bucket/filename.ext');
  }

  public function testRetrieveLocalUrlNonExistent() {
    $this->expectExceptionMessage('Unknown URL.');
    $this->getFileMapper()->getLocalUrl('foobar');
  }

  public function testRetrieveLocalUrl() {
    $fileMapper = $this->getFileMapper([ProcessorMock::class]);

    $originalUrl = 'foobar';
    $uuid = $fileMapper->register($originalUrl);

    $fileMapper->getFileFetcher($uuid)->run();

    $localUrl = $fileMapper->getLocalUrl($uuid);

    $this->assertNotEquals($originalUrl, $localUrl);
  }

}
