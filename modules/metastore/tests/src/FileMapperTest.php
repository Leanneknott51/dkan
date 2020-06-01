<?php

namespace Drupal\Tests\metastore;

use Contracts\Mock\Storage\JsonObjectMemory;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\common\Storage\JobStoreFactory;
use Drupal\common\Util\DrupalFiles;
use Drupal\metastore\FileMapper;
use Drupal\Tests\metastore\Unit\ProcessorMock;
use MockChain\Chain;
use PHPUnit\Framework\TestCase;


class FileMapperTest extends TestCase {

  private function getFileMapper($processors = []) {
    $store = new JsonObjectMemory();

    $jobStoreFactory = (new Chain($this))
      ->add(JobStoreFactory::class, "getInstance", $store)
      ->getMock();


    $streamWrapperManager = (new Chain($this))
      ->add(StreamWrapperManager::class, 'getViaUri', "yep")
      ->getMock();

    $drupalFiles = (new Chain($this))
      ->add(DrupalFiles::class, "getFilesystem", FileSystemInterface::class)
      ->add(FileSystemInterface::class, 'prepareDirectory', null)
      ->add(DrupalFiles::class, "getPublicFilesDirectory", __DIR__)
      ->add(DrupalFiles::class, 'fileCreateUrl', 'localfoobar')
      ->getMock();

    $fileMapper = new FileMapper($jobStoreFactory, $drupalFiles);
    $fileMapper->setFileFetcherProcessors($processors);
    return $fileMapper;
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
