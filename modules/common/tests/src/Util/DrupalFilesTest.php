<?php

use Drupal\common\Util\DrupalFiles;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use MockChain\Chain;
use MockChain\Options;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DrupalFilesTest extends TestCase {

  public function test() {
    $drupalFiles = DrupalFiles::create($this->getContainer());
    $drupalFiles->retrieveFile(
      "file://" . __DIR__ . "/../../files/hello.txt",
      "public://tmp");
    $this->assertTrue(file_exists("/tmp/hello.txt"));
  }

  private function getContainer(): ContainerInterface {
    $options = (new Options())
      ->add('file_system', FileSystemInterface::class)
      ->add('stream_wrapper_manager', StreamWrapperManager::class)
      ->index(0);

    $container = (new Chain($this))
      ->add(ContainerInterface::class, 'get', $options)
      ->add(FileSystemInterface::class, 'realpath', "/tmp")
      ->getMock();

    return $container;
  }

  protected function tearDown() {
    unlink("/tmp/hello.txt");
  }


}
